<?php
/**
 * WorkforceIntegration — read side and status management for the data the
 * Workforce Analytics module hands to Learning & Development.
 *
 * Four signals arrive from Workforce (see
 * rainz/integration mds/INTEGRATION_WORKFORCE_ANALYTICS.md):
 *
 *   engagement     ld_training_engagement        training participation / activity records
 *   turnover_risk  ld_turnover_risk              Workforce-assessed turnover risk
 *   competency     ld_competency_requirement     predicted skill / competency needs
 *   reporting      ld_reporting_requirement      reporting requirements & targets
 *
 * Turnover risk also has a Learning-computed side: ld_engagement_snapshot, which
 * the engagement cron materialises per learner (risk_score + risk_factors). The
 * two are shown together on the turnover-risk tab.
 *
 * Pages must not explode when a table is missing — the migration in
 * sql/workforce_integration.sql is run by hand on this project — so every read
 * routes a failure through DbError::capture() and returns an empty result, which
 * Page::render() surfaces as the standard database-error banner.
 */
include_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/dberror.php';

class WorkforceIntegration
{
    /** dataset key => [table, human label]. */
    public const DATASETS = [
        'engagement'    => ['table' => 'ld_training_engagement',     'label' => 'Training engagement'],
        'turnover_risk' => ['table' => 'ld_turnover_risk',           'label' => 'Turnover risk'],
        'competency'    => ['table' => 'ld_competency_requirement',  'label' => 'Predicted skill / competency needs'],
        'reporting'     => ['table' => 'ld_reporting_requirement',   'label' => 'Reporting requirements'],
    ];

    /** dataset key => [status value => label] — the only transitions this class writes. */
    public const STATUSES = [
        'engagement'    => ['received' => 'Received', 'monitoring' => 'Monitoring', 'completed' => 'Completed'],
        'turnover_risk' => ['received' => 'Received', 'monitoring' => 'Monitoring', 'mitigated' => 'Mitigated', 'escalated' => 'Escalated'],
        'competency'    => ['received' => 'Received', 'planned' => 'Planned', 'completed' => 'Completed'],
        'reporting'     => ['received' => 'Received', 'in_progress' => 'In progress', 'completed' => 'Completed'],
    ];

    /** Columns searched by the free-text filter, per dataset. */
    private const SEARCH_COLUMNS = [
        'engagement'    => ['learner_name', 'department', 'engagement_data', 'recommended_action'],
        'turnover_risk' => ['employee_name', 'department', 'position_name', 'risk_factors', 'recommended_action'],
        'competency'    => ['skill_name', 'target_department', 'target_proficiency', 'expected_learning_output', 'notes'],
        'reporting'     => ['target_name', 'expected_output', 'notes'],
    ];

    /** The department a dataset can be scoped by (null = no department column). */
    private const DEPARTMENT_COLUMNS = [
        'engagement'    => 'department',
        'turnover_risk' => 'department',
        'competency'    => 'target_department',
        'reporting'     => null,
    ];

    /** What "still needs attention" means, per dataset (drives the open counts). */
    private const OPEN_CONDITIONS = [
        'engagement'    => "status <> 'completed'",
        'turnover_risk' => "status NOT IN ('mitigated')",
        'competency'    => "status <> 'completed'",
        'reporting'     => "status <> 'completed'",
    ];

    /** Datasets an admin can raise follow-up training from. */
    public const REQUEST_SOURCES = [
        'competency'    => 'Skill / competency need',
        'turnover_risk' => 'Turnover risk',
    ];

    /** Columns the outbound triage feed exposes for each dataset. */
    private const FEED_COLUMNS = [
        'engagement'    => 'id, source_module, external_reference_id, learner_id, learner_name, department, engagement_status, status, created_at, updated_at',
        'turnover_risk' => 'id, source_module, external_reference_id, employee_id, employee_name, department, risk_level, risk_score, status, created_at, updated_at',
        'competency'    => 'id, source_module, skill_name, target_department, priority_level, employees_needing_training, status, created_at',
        'reporting'     => 'id, source_module, target_name, reporting_period_end, status, created_at',
    ];

    /**
     * Every column the matching list method selects — what one record is fetched with.
     * Kept beside those SELECTs so a row loaded for a deep link renders like any other.
     */
    private const RECORD_COLUMNS = [
        'engagement'    => 'id, source_module, external_reference_id, learner_team_type, learner_id, learner_name, department, training_activity, engagement_status, engagement_data, recommended_action, notes, status, created_at, updated_at',
        'turnover_risk' => 'id, source_module, external_reference_id, employee_id, employee_name, department, position_name, risk_level, risk_score, risk_factors, recommended_action, assessment_date, status, created_at, updated_at',
        'competency'    => 'id, source_module, skill_name, target_department, priority_level, employees_needing_training, target_proficiency, expected_learning_output, notes, status, created_by, created_at',
        'reporting'     => 'id, source_module, target_name, reporting_period_start, reporting_period_end, reporting_frequency, completion_target, expected_output, notes, status, created_by, created_at',
    ];

    /** Audit item types this class writes (and the feed reads back). */
    private const AUDIT_STATUS_ITEM = 'workforce_integration';
    private const AUDIT_REQUEST_ITEM = 'workforce_integration_request';

    /**
     * Records that page the L&D team the moment they arrive, and the column that makes
     * them urgent. Everything else waits for an admin to open the page.
     */
    private const ALERT_COLUMNS = [
        'turnover_risk' => ['risk_level', 'critical'],
        'competency'    => ['priority_level', 'critical'],
    ];

    /** Notification type for an arriving critical record (see Notification::ICONS). */
    public const ALERT_NOTIFICATION_TYPE = 'workforce_alert';

    /**
     * Roles that own this inbox, read from user_account (the table that really carries
     * role_id — em_employees has none):
     *   1  System Administrator      organisation-wide owner
     *   7  Learning and Development  triage the records and deliver the training
     */
    private const ALERT_ROLE_IDS = [1, 7];

    /**
     * Legacy L&D admin ids that Page::resolveLearningRoleFromDb() also treats as admins.
     * Keep in step with that method: they hold admin access in this module whatever
     * their role row says, so a critical record has to reach them too.
     */
    private const LD_ADMIN_EMPLOYEE_IDS = [35, 1018];

    private PDO $conn;
    private string $sourceModule = 'workforce';

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function isDataset(string $dataset): bool
    {
        return isset(self::DATASETS[$dataset]);
    }

    public static function datasetLabel(string $dataset): string
    {
        return self::DATASETS[$dataset]['label'] ?? ucwords(str_replace('_', ' ', $dataset));
    }

    public static function statusLabel(string $dataset, ?string $status): string
    {
        $status = (string) $status;
        return self::STATUSES[$dataset][$status]
            ?? ucwords(str_replace('_', ' ', $status !== '' ? $status : 'received'));
    }

    public function isStatusForDataset(string $dataset, string $status): bool
    {
        return isset(self::STATUSES[$dataset][$status]);
    }

    // ─────────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────────

    /**
     * Record counts for every dataset plus the open/actionable counts the pages
     * show on their stat cards. Missing tables count as 0.
     *
     * Passing a department scopes the three department-aware datasets to it (the
     * instructor view); reporting requirements are organisation-wide, so they stay
     * unscoped whatever is passed.
     */
    public function getSummary(?string $department = null): array
    {
        $summary = [];
        foreach (array_keys(self::DATASETS) as $dataset) {
            $summary[$dataset] = 0;
            $summary[$dataset . '_open'] = 0;
        }

        $department = $department !== null ? trim($department) : '';

        foreach (array_keys(self::DATASETS) as $dataset) {
            $table  = self::DATASETS[$dataset]['table'];
            $column = self::DEPARTMENT_COLUMNS[$dataset] ?? null;
            $scoped = $department !== '' && $column !== null;

            try {
                if ($scoped) {
                    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :dept");
                    $stmt->execute([':dept' => $department]);
                    $summary[$dataset] = (int) $stmt->fetchColumn();

                    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :dept AND " . self::OPEN_CONDITIONS[$dataset]);
                    $stmt->execute([':dept' => $department]);
                    $summary[$dataset . '_open'] = (int) $stmt->fetchColumn();
                } else {
                    $summary[$dataset] = (int) $this->conn->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                    $summary[$dataset . '_open'] = (int) $this->conn->query("SELECT COUNT(*) FROM {$table} WHERE " . self::OPEN_CONDITIONS[$dataset])->fetchColumn();
                }
            } catch (Throwable $e) {
                DbError::capture($e, "workforce integration summary: {$dataset}");
            }
        }

        return $summary;
    }

    /**
     * Department options for the filter select: the shared department list plus
     * any department named only inside a Workforce-sent record.
     */
    public function getDepartmentOptions(): array
    {
        $departments = [];

        try {
            $rows = $this->conn->query("
                SELECT department_name FROM em_departments
                WHERE department_name IS NOT NULL AND TRIM(department_name) <> ''
                ORDER BY department_name ASC
            ")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $row) {
                $departments[trim((string) $row)] = true;
            }
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration departments');
        }

        foreach ([
            'SELECT department FROM ld_training_engagement',
            'SELECT target_department FROM ld_competency_requirement',
            'SELECT department FROM ld_turnover_risk',
        ] as $sql) {
            try {
                foreach ($this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $row) {
                    $row = trim((string) $row);
                    if ($row !== '') {
                        $departments[$row] = true;
                    }
                }
            } catch (Throwable $e) {
                // Table not migrated yet — the shared list above still works.
            }
        }

        $list = array_keys($departments);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }

    // ─────────────────────────────────────────────────────────────
    // Datasets
    // ─────────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function getTrainingEngagement(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        [$where, $params] = $this->buildFilters('engagement', $filters);

        try {
            $stmt = $this->conn->prepare("
                SELECT id, source_module, external_reference_id, learner_team_type, learner_id,
                       learner_name, department, training_activity, engagement_status,
                       engagement_data, recommended_action, notes, status, created_at, updated_at
                FROM ld_training_engagement
                {$where}
                ORDER BY created_at DESC, id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: training engagement');
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getTurnoverRisk(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        [$where, $params] = $this->buildFilters('turnover_risk', $filters);

        try {
            $stmt = $this->conn->prepare("
                SELECT id, source_module, external_reference_id, employee_id, employee_name,
                       department, position_name, risk_level, risk_score, risk_factors,
                       recommended_action, assessment_date, status, created_at, updated_at
                FROM ld_turnover_risk
                {$where}
                ORDER BY FIELD(risk_level, 'critical', 'high', 'medium', 'low'), risk_score DESC, created_at DESC, id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: turnover risk');
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getCompetencyRequirements(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        [$where, $params] = $this->buildFilters('competency', $filters);

        try {
            $stmt = $this->conn->prepare("
                SELECT id, source_module, skill_name, target_department, priority_level,
                       employees_needing_training, target_proficiency, expected_learning_output,
                       notes, status, created_by, created_at
                FROM ld_competency_requirement
                {$where}
                ORDER BY FIELD(priority_level, 'critical', 'high', 'medium', 'low'), created_at DESC, id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: competency requirements');
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getReportingRequirements(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        [$where, $params] = $this->buildFilters('reporting', $filters);

        try {
            $stmt = $this->conn->prepare("
                SELECT id, source_module, target_name, reporting_period_start, reporting_period_end,
                       reporting_frequency, completion_target, expected_output, notes, status,
                       created_by, created_at
                FROM ld_reporting_requirement
                {$where}
                ORDER BY reporting_period_end DESC, created_at DESC, id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: reporting requirements');
            return [];
        }
    }

    /** Total matching rows for one dataset (drives the "showing x of y" line). */
    public function countRecords(string $dataset, array $filters = []): int
    {
        if (!$this->isDataset($dataset)) {
            return 0;
        }

        [$where, $params] = $this->buildFilters($dataset, $filters);
        $table = self::DATASETS[$dataset]['table'];

        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$table} {$where}");
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            DbError::capture($e, "workforce integration: count {$dataset}");
            return 0;
        }
    }

    /**
     * One record by id, in the same shape the list methods return, or null when it does
     * not exist.
     *
     * This is what a notification deep link fetches: the record it announced may sit on
     * the list's third page, outside the current filters, or in a department the reader
     * is not scoped to, and the page still has to show the row it was told about.
     */
    public function getRecord(string $dataset, int $id): ?array
    {
        if ($id <= 0 || !$this->isDataset($dataset)) {
            return null;
        }

        try {
            $stmt = $this->conn->prepare(
                'SELECT ' . self::RECORD_COLUMNS[$dataset]
                . ' FROM ' . self::DATASETS[$dataset]['table']
                . ' WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (Throwable $e) {
            DbError::capture($e, "workforce integration: {$dataset} record");
            return null;
        }
    }

    /**
     * The department a record belongs to, or '' for the datasets without one (reporting
     * requirements are organisation-wide). Lets a scoped page check whether a deep-linked
     * record is inside its scope before showing it.
     *
     * @param array<string, mixed> $row a row from getRecord() / a list method
     */
    public static function recordDepartment(string $dataset, array $row): string
    {
        $column = self::DEPARTMENT_COLUMNS[$dataset] ?? null;

        return $column === null ? '' : trim((string) ($row[$column] ?? ''));
    }

    // ─────────────────────────────────────────────────────────────
    // Learning-computed engagement / turnover-risk snapshot
    // ─────────────────────────────────────────────────────────────

    /** Most recent snapshot date in ld_engagement_snapshot, or null. */
    public function getLatestSnapshotDate(): ?string
    {
        try {
            $date = $this->conn->query('SELECT MAX(snapshot_date) FROM ld_engagement_snapshot')->fetchColumn();
            return $date ? (string) $date : null;
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: engagement snapshot date');
            return null;
        }
    }

    /**
     * Risk bands for the latest snapshot:
     * ['date' => string|null, 'total' => int, 'avg_risk' => float, 'bands' => [high|medium|low => count]]
     */
    public function getSnapshotRiskSummary(?string $department = null): array
    {
        $summary = ['date' => null, 'total' => 0, 'avg_risk' => 0.0, 'bands' => ['high' => 0, 'medium' => 0, 'low' => 0]];

        $date = $this->getLatestSnapshotDate();
        if ($date === null) {
            return $summary;
        }
        $summary['date'] = $date;

        // Scoping a snapshot by department needs the learner's employee record.
        $department = $department !== null ? trim($department) : '';
        $scope = '';
        $params = [':d' => $date];
        $joins = '';
        if ($department !== '') {
            $joins = ' LEFT JOIN em_employees emp ON emp.employee_id = es.learner_id'
                . ' LEFT JOIN em_departments dep ON dep.department_id = emp.department_id';
            $scope = ' AND dep.department_name = :dept';
            $params[':dept'] = $department;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT
                    CASE
                        WHEN es.risk_score >= 70 THEN 'high'
                        WHEN es.risk_score >= 40 THEN 'medium'
                        ELSE 'low'
                    END AS band,
                    COUNT(*) AS total,
                    ROUND(AVG(COALESCE(es.risk_score, 0)), 1) AS avg_risk
                FROM ld_engagement_snapshot es{$joins}
                WHERE es.snapshot_date = :d{$scope}
                GROUP BY band
            ");
            $stmt->execute($params);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $band = strtolower((string) $row['band']);
                if (isset($summary['bands'][$band])) {
                    $summary['bands'][$band] = (int) $row['total'];
                    $summary['total'] += (int) $row['total'];
                }
            }

            $stmt = $this->conn->prepare("SELECT ROUND(AVG(COALESCE(es.risk_score, 0)), 1) FROM ld_engagement_snapshot es{$joins} WHERE es.snapshot_date = :d{$scope}");
            $stmt->execute($params);
            $summary['avg_risk'] = (float) ($stmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: risk bands');
        }

        return $summary;
    }

    /**
     * Learners on the latest snapshot at or above the watch threshold (risk 40+),
     * joined to their employee record for a readable name and department.
     */
    public function getAtRiskLearners(int $limit = 25, ?string $department = null): array
    {
        $limit = max(1, min($limit, 200));
        $date = $this->getLatestSnapshotDate();
        if ($date === null) {
            return [];
        }

        $department = $department !== null ? trim($department) : '';
        $scope = '';
        $params = [':d' => $date];
        if ($department !== '') {
            $scope = ' AND d.department_name = :dept';
            $params[':dept'] = $department;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT
                    es.learner_id,
                    es.risk_score,
                    es.risk_factors,
                    es.active_days_30,
                    es.courses_in_progress,
                    es.courses_completed_ytd,
                    es.days_since_last_activity,
                    es.expired_certificates,
                    es.snapshot_date,
                    CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
                    COALESCE(d.department_name, 'Unassigned') AS department_name
                FROM ld_engagement_snapshot es
                LEFT JOIN em_employees emp ON emp.employee_id = es.learner_id
                LEFT JOIN em_departments d ON d.department_id = emp.department_id
                WHERE es.snapshot_date = :d AND COALESCE(es.risk_score, 0) >= 40{$scope}
                ORDER BY es.risk_score DESC, es.days_since_last_activity DESC
                LIMIT {$limit}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: at-risk learners');
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Status management
    // ─────────────────────────────────────────────────────────────

    /**
     * Move one received record along its status flow.
     *
     * $actorId is written to the audit trail alongside the change, because the
     * reception tables only hold the current status — two of them have no
     * updated_at column at all, so the audit row is the only "when and by whom".
     *
     * @return array{success: bool, message: string, status?: string, label?: string}
     */
    public function updateStatus(string $dataset, int $id, string $status, int $actorId = 0): array
    {
        if (!$this->isDataset($dataset)) {
            return ['success' => false, 'message' => 'Unknown dataset.'];
        }

        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid record id.'];
        }

        if (!$this->isStatusForDataset($dataset, $status)) {
            return ['success' => false, 'message' => 'That status is not valid for ' . self::datasetLabel($dataset) . '.'];
        }

        $table = self::DATASETS[$dataset]['table'];

        try {
            // Read the previous value first: it is what the audit trail records.
            $before = $this->conn->prepare("SELECT status FROM {$table} WHERE id = :id");
            $before->execute([':id' => $id]);
            $previous = $before->fetchColumn();

            if ($previous === false) {
                return ['success' => false, 'message' => 'The record no longer exists.'];
            }

            $stmt = $this->conn->prepare("UPDATE {$table} SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            // Treat "already at this status" as success too (rowCount is 0 then).
            $exists = $this->conn->prepare("SELECT status FROM {$table} WHERE id = :id");
            $exists->execute([':id' => $id]);
            $stored = $exists->fetchColumn();

            if ($stored === false) {
                return ['success' => false, 'message' => 'The record no longer exists.'];
            }

            if ((string) $previous !== (string) $stored) {
                $this->auditEvent(self::AUDIT_STATUS_ITEM, 'edit', $id, [
                    'dataset' => $dataset,
                    'from'    => (string) $previous,
                    'to'      => (string) $stored,
                ], $actorId);
            }

            return [
                'success' => true,
                'message' => self::datasetLabel($dataset) . ' #' . $id . ' is now ' . self::statusLabel($dataset, (string) $stored) . '.',
                'status'  => (string) $stored,
                'label'   => self::statusLabel($dataset, (string) $stored),
            ];
        } catch (Throwable $e) {
            DbError::capture($e, "workforce integration: update {$dataset} status");
            return ['success' => false, 'message' => 'The status could not be saved.'];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Follow-up raised against a received record
    // ─────────────────────────────────────────────────────────────

    /**
     * Raise a training request from a record Workforce sent — the point where this
     * inbox turns into actual learning follow-up. Every field is prefilled from the
     * source record and may be overridden by the caller.
     *
     * @param array $input dataset, id, actor_id, optional title/message/department/priority
     * @return array{success: bool, message: string, request_id?: int, status_change?: array|null, notified?: int}
     */
    public function createTrainingRequest(array $input): array
    {
        $dataset  = strtolower(trim((string) ($input['dataset'] ?? '')));
        $recordId = (int) ($input['id'] ?? 0);
        $actorId  = (int) ($input['actor_id'] ?? 0);

        if (!isset(self::REQUEST_SOURCES[$dataset])) {
            return ['success' => false, 'message' => 'Follow-up training can only be raised from a competency need or a turnover risk.'];
        }
        if ($recordId <= 0) {
            return ['success' => false, 'message' => 'Invalid record id.'];
        }

        $table = self::DATASETS[$dataset]['table'];

        try {
            $stmt = $this->conn->prepare("SELECT * FROM {$table} WHERE id = :id");
            $stmt->execute([':id' => $recordId]);
            $source = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, "workforce integration: read {$dataset} for follow-up");
            return ['success' => false, 'message' => 'The record could not be read.'];
        }

        if (!$source) {
            return ['success' => false, 'message' => 'That record no longer exists.'];
        }

        // One open request per source record: the same need must not spawn duplicates.
        // Reraising is allowed once the previous request is resolved.
        try {
            $open = $this->conn->prepare(
                "SELECT id FROM ld_training_requests WHERE external_reference_id = :ref AND status IN ('pending', 'accepted') ORDER BY id ASC LIMIT 1"
            );
            $open->execute([':ref' => $dataset . ':' . $recordId]);
            $openId = $open->fetchColumn();
            if ($openId !== false) {
                return ['success' => false, 'message' => 'Training request #' . (int) $openId . ' is already open for this record.'];
            }
        } catch (Throwable $e) {
            // No ld_training_requests yet — the insert below reports that clearly.
        }

        [$defaultTitle, $defaultDepartment, $defaultPriority, $defaultMessage, $learnerId] = $this->requestDefaults($dataset, $source);

        $title      = $this->optionalText($input['title'] ?? null, 150) ?? $defaultTitle;
        $message    = $this->optionalText($input['message'] ?? null) ?? $defaultMessage;
        $department = $this->optionalText($input['department'] ?? null, 150) ?? $this->optionalText($defaultDepartment, 150);
        $priority   = $this->oneOf($input['priority'] ?? '', ['low', 'medium', 'high', 'critical'], $defaultPriority);

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ld_training_requests
                    (learner_id, skill_id, skill_name, message, target_department, priority_level,
                     status, source_module, external_reference_id, created_by, created_at)
                VALUES
                    (:learner_id, NULL, :skill_name, :message, :department, :priority,
                     'pending', :source_module, :reference, :created_by, NOW())
            ");
            $stmt->execute([
                ':learner_id'    => $learnerId,
                ':skill_name'    => $title,
                ':message'       => $message,
                ':department'    => $department,
                ':priority'      => $priority,
                ':source_module' => $this->sourceModule,
                ':reference'     => $dataset . ':' . $recordId,
                ':created_by'    => $actorId > 0 ? $actorId : null,
            ]);
            $requestId = (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: raise training request');
            return ['success' => false, 'message' => 'The training request could not be created (is ld_training_requests migrated?).'];
        }

        $this->auditEvent(self::AUDIT_REQUEST_ITEM, 'create', $requestId, [
            'dataset'    => $dataset,
            'record'     => $recordId,
            'title'      => $title,
            'department' => $department,
            'priority'   => $priority,
            'learner_id' => $learnerId,
        ], $actorId);

        $notified = $this->notifyInstructors($title, $department);

        // The record it came from is being acted on now, so advance it if it has not moved yet.
        $statusChange = null;
        if ((string) $source['status'] === 'received') {
            $statusChange = $this->updateStatus($dataset, $recordId, $dataset === 'competency' ? 'planned' : 'monitoring', $actorId);
        }

        return [
            'success'       => true,
            'message'       => 'Training request #' . $requestId . ' raised'
                . ($notified > 0 ? ' and ' . $notified . ' instructor' . ($notified === 1 ? '' : 's') . ' notified' : '') . '.',
            'request_id'    => $requestId,
            'status_change' => $statusChange,
            'notified'      => $notified,
        ];
    }

    /**
     * The prefilled request fields for a row, named for the page:
     * ['title', 'department', 'priority', 'message', 'learner_id']. Public so the
     * view can prefill its form without a second query per row.
     *
     * @return array{title: string, department: string, priority: string, message: string, learner_id: int|null}
     */
    public function requestDraft(string $dataset, array $source): array
    {
        if (!isset(self::REQUEST_SOURCES[$dataset])) {
            return ['title' => '', 'department' => '', 'priority' => 'medium', 'message' => '', 'learner_id' => null];
        }

        [$title, $department, $priority, $message, $learnerId] = $this->requestDefaults($dataset, $source);

        return [
            'title'      => $title,
            'department' => $department,
            'priority'   => $priority,
            'message'    => $message,
            'learner_id' => $learnerId,
        ];
    }

    /**
     * The prefilled request fields for a source record:
     * [title, department, priority, message, learner_id].
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: int|null}
     */
    private function requestDefaults(string $dataset, array $source): array
    {
        if ($dataset === 'turnover_risk') {
            $employeeName = trim((string) ($source['employee_name'] ?? ''));
            $level = (string) ($source['risk_level'] ?? 'medium');

            $title = 'Retention follow-up — ' . ($employeeName !== '' ? $employeeName : 'employee #' . (int) ($source['employee_id'] ?? 0));
            $factors = array_filter([
                trim((string) ($source['risk_factors'] ?? '')),
                trim((string) ($source['recommended_action'] ?? '')),
            ]);
            $message = 'Turnover risk: ' . $level . ' (score ' . (string) ($source['risk_score'] ?? 'n/a') . '). ' . implode(' ', $factors);

            return [
                $title,
                (string) ($source['department'] ?? ''),
                in_array($level, ['critical', 'high'], true) ? 'high' : 'medium',
                trim($message),
                $this->optionalId($source['employee_id'] ?? null),
            ];
        }

        $message = 'Predicted skill need for ' . (int) ($source['employees_needing_training'] ?? 0) . ' learner(s).'
            . ' Target proficiency: ' . (string) ($source['target_proficiency'] ?? '-') . '.'
            . ' Expected learning output: ' . (string) ($source['expected_learning_output'] ?? '-') . '.';

        return [
            (string) ($source['skill_name'] ?? 'Skill gap follow-up'),
            (string) ($source['target_department'] ?? ''),
            $this->oneOf($source['priority_level'] ?? '', ['low', 'medium', 'high', 'critical'], 'medium'),
            trim($message),
            null,
        ];
    }

    /**
     * Tell the instructors who own active courses, mirroring what the learner-facing
     * request flow does. A notification failure never fails the request itself.
     */
    private function notifyInstructors(string $title, ?string $department, int $limit = 100): int
    {
        $sent = 0;

        try {
            $instructorIds = $this->conn->query(
                "SELECT DISTINCT instructor_id FROM ld_course WHERE instructor_id IS NOT NULL AND status = 'active' LIMIT {$limit}"
            )->fetchAll(PDO::FETCH_COLUMN);

            if (!$instructorIds) {
                return 0;
            }

            $message = 'New training request from a Workforce Analytics record: "' . $title . '"'
                . ($department !== null && $department !== '' ? ' (' . $department . ')' : '') . '.';

            foreach ($instructorIds as $instructorId) {
                if ($this->pushNotification((int) $instructorId, 'training_request', 'New Training Request', $message)) {
                    $sent++;
                }
            }
        } catch (Throwable $e) {
            error_log('workforce integration: instructor notification skipped — ' . $e->getMessage());
        }

        return $sent;
    }

    /**
     * The people a critical Workforce signal has to reach without anyone opening a page:
     * the L&D admins who own this inbox and the L&D staff who deliver the training.
     * Read from user_account, the table that carries role_id, plus the legacy L&D admin
     * ids this module's role rules honour.
     *
     * @return array<int, int> employee ids, ascending
     */
    public function getAlertAudience(): array
    {
        $audience = [];

        try {
            $in = implode(', ', array_fill(0, count(self::ALERT_ROLE_IDS), '?'));
            $stmt = $this->conn->prepare(
                "SELECT DISTINCT employee_id FROM user_account"
                . " WHERE account_status = 'Active' AND role_id IN ({$in})"
            );
            $stmt->execute(self::ALERT_ROLE_IDS);

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $audience[(int) $id] = true;
            }
        } catch (Throwable $e) {
            error_log('workforce integration: alert audience lookup failed — ' . $e->getMessage());
        }

        foreach (self::LD_ADMIN_EMPLOYEE_IDS as $id) {
            $audience[(int) $id] = true;
        }

        $ids = array_keys($audience);
        sort($ids);

        return $ids;
    }

    /**
     * Tell the L&D team about a record that cannot wait for someone to open the page:
     * a critical turnover risk or a critical competency need, as it arrives.
     *
     * Runs inside the inbound receive path, so a notification failure must never fail the
     * insert Workforce Analytics is waiting on: pushNotification() logs and carries on.
     *
     * @param array<string, mixed> $values the stored columns of the new record
     * @return int recipients notified
     */
    private function notifyCriticalSignal(string $dataset, array $values, int $recordId): int
    {
        $alert = self::ALERT_COLUMNS[$dataset] ?? null;
        if ($alert === null || $recordId <= 0) {
            return 0;
        }

        [$column, $urgentValue] = $alert;
        if (strtolower(trim((string) ($values[$column] ?? ''))) !== $urgentValue) {
            return 0;
        }

        $referenceType = 'workforce_' . $dataset;

        // The same record must not page the team twice (re-sent payloads, re-migrated rows).
        try {
            $dupe = $this->conn->prepare(
                'SELECT id FROM ld_notification WHERE type = :type AND reference_type = :ref_type AND reference_id = :ref_id LIMIT 1'
            );
            $dupe->execute([
                ':type'     => self::ALERT_NOTIFICATION_TYPE,
                ':ref_type' => $referenceType,
                ':ref_id'   => $recordId,
            ]);
            if ($dupe->fetchColumn() !== false) {
                return 0;
            }
        } catch (Throwable $e) {
            // Notification table missing — pushNotification() below reports that clearly.
        }

        if ($dataset === 'turnover_risk') {
            $title = 'Critical turnover risk';
            $message = 'Workforce Analytics flagged '
                . ($this->optionalText($values['employee_name'] ?? null, 150) ?? 'an employee')
                . ($this->optionalText($values['department'] ?? null, 150) !== null ? ' (' . $values['department'] . ')' : '')
                . ' as a critical turnover risk'
                . ($values['risk_score'] !== null ? ' (score ' . $values['risk_score'] . ')' : '')
                . '. Triage it on Workforce Integration.';
        } else {
            $title = 'Critical skill need';
            $message = 'Workforce Analytics sent a critical skill need: "'
                . $this->optionalText($values['skill_name'] ?? null, 150) . '" for '
                . $this->optionalText($values['target_department'] ?? null, 150)
                . ' (' . (int) ($values['employees_needing_training'] ?? 0) . ' learner(s) need training).'
                . ' Triage it on Workforce Integration.';
        }

        $sent = 0;
        foreach ($this->getAlertAudience() as $userId) {
            if ($this->pushNotification($userId, self::ALERT_NOTIFICATION_TYPE, $title, $message, $referenceType, $recordId)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Insert one bell notification, optionally tied back to the record it is about.
     * Never throws: the caller's own write is the work that matters.
     */
    private function pushNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id, is_read, created_at)'
                . ' VALUES (:uid, :type, :title, :message, :reference_type, :reference_id, 0, NOW())'
            );
            $stmt->execute([
                ':uid'            => $userId,
                ':type'           => $type,
                ':title'          => $title,
                ':message'        => $message,
                ':reference_type' => $referenceType,
                ':reference_id'   => $referenceId,
            ]);

            return true;
        } catch (Throwable $e) {
            error_log('workforce integration: notification skipped — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Training requests raised in Learning — learner-raised and Workforce follow-up.
     * Passing a department limits them to the ones aimed at that department.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrainingRequests(?string $department = null, int $limit = 100, int $offset = 0): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);
        $department = $department !== null ? trim($department) : '';

        $where = $department !== '' ? 'WHERE tr.target_department = :dept' : '';
        $params = $department !== '' ? [':dept' => $department] : [];

        try {
            $stmt = $this->conn->prepare("
                SELECT tr.id, tr.learner_id, tr.skill_name, tr.message, tr.target_department,
                       tr.priority_level, tr.status, tr.source_module, tr.external_reference_id,
                       tr.instructor_id, tr.instructor_note, tr.created_at, tr.updated_at,
                       CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
                FROM ld_training_requests tr
                LEFT JOIN em_employees emp ON emp.employee_id = tr.learner_id
                {$where}
                ORDER BY FIELD(tr.status, 'pending', 'accepted', 'rejected', 'completed'), tr.created_at DESC, tr.id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: training requests');
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Outbound triage feed (api/outbound/get-workforce-integration-status.php)
    // ─────────────────────────────────────────────────────────────

    /**
     * What Workforce needs to agree with Learning: the current status of every
     * received record, the follow-up requests raised from them, and the audit trail
     * of the decisions.
     *
     * $since filters the audit entries only — the record lists always report the
     * current state, because the reception tables keep no history of their own.
     *
     * @return array{generated_at: string, counts: array<string, int>, datasets: array, requests: array, events: array}
     */
    public function getTriageFeed(?string $since = null, int $limit = 200): array
    {
        $limit = max(1, min($limit, 1000));
        $datasets = [];

        foreach (array_keys(self::DATASETS) as $dataset) {
            $table = self::DATASETS[$dataset]['table'];

            try {
                $rows = $this->conn->query(
                    'SELECT ' . self::FEED_COLUMNS[$dataset] . " FROM {$table} ORDER BY id ASC LIMIT {$limit}"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                DbError::capture($e, "workforce integration feed: {$dataset}");
                $rows = [];
            }

        foreach ($rows as $index => $row) {
            $rows[$index]['status_label'] = self::statusLabel($dataset, (string) $row['status']);
        }

        $datasets[$dataset] = [
                'label'   => self::DATASETS[$dataset]['label'],
                'records' => $rows,
            ];
        }

        return [
            'generated_at' => date('c'),
            'counts'       => $this->getSummary(),
            'datasets'     => $datasets,
            'requests'     => $this->getTrainingRequests(null, $limit),
            'events'       => $this->getTriageEvents($since, $limit),
        ];
    }

    /**
     * Triage decisions recorded in ld_audit_log, newest first, with the stored JSON
     * details decoded back into an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTriageEvents(?string $since = null, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $sinceDate = $this->normalizeDate($since);

        try {
            $sql = 'SELECT id, user_id, role, action, item_type, reference_id, details, created_at'
                . ' FROM ld_audit_log'
                . ' WHERE item_type IN (:status_item, :request_item)';
            $params = [
                ':status_item'  => self::AUDIT_STATUS_ITEM,
                ':request_item' => self::AUDIT_REQUEST_ITEM,
            ];

            if ($sinceDate !== null) {
                $sql .= ' AND created_at >= :since';
                $params[':since'] = $sinceDate . ' 00:00:00';
            }

            $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: triage events');
            return [];
        }

        foreach ($rows as $index => $row) {
            $rows[$index]['reference_id'] = (int) $row['reference_id'];
            $rows[$index]['user_id'] = (int) $row['user_id'];
            $rows[$index]['details'] = $row['details'] !== null ? json_decode((string) $row['details'], true) : null;
        }

        return $rows;
    }

    /**
     * Who moved these records, and when: the triage history the reception tables cannot
     * hold, read back out of ld_audit_log and returned per record, newest first.
     *
     *   item_type workforce_integration          reference_id = the record, details from/to
     *   item_type workforce_integration_request  reference_id = the request, details.record
     *                                            points back at the record it came from
     * So the status events match on their own reference, while request events are matched
     * through the request's external reference ("<dataset>:<id>") — no JSON SQL needed.
     *
     * @param array<int, int> $recordIds record ids on the page being rendered
     * @return array<int, array<int, array<string, mixed>>> record id => events
     */
    public function getRecordHistory(string $dataset, array $recordIds, int $limit = 500): array
    {
        if (!$this->isDataset($dataset)) {
            return [];
        }

        $recordIds = array_values(array_unique(array_filter(
            array_map('intval', $recordIds),
            static function (int $id): bool {
                return $id > 0;
            }
        )));
        if (!$recordIds) {
            return [];
        }

        $limit = max(1, min($limit, 1000));

        // Which follow-up requests were raised from these records?
        $requestIds = [];
        try {
            $references = array_map(static function (int $id) use ($dataset): string {
                return $dataset . ':' . $id;
            }, $recordIds);
            $in = implode(', ', array_fill(0, count($references), '?'));
            $stmt = $this->conn->prepare("SELECT id FROM ld_training_requests WHERE external_reference_id IN ({$in})");
            $stmt->execute($references);
            $requestIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            // No follow-up requests table — the status events below are still readable.
        }

        $rows = [];
        $queries = [self::AUDIT_STATUS_ITEM => $recordIds];
        if ($requestIds) {
            $queries[self::AUDIT_REQUEST_ITEM] = $requestIds;
        }

        foreach ($queries as $itemType => $ids) {
            try {
                $in = implode(', ', array_fill(0, count($ids), '?'));
                $stmt = $this->conn->prepare(
                    'SELECT id, user_id, role, action, item_type, reference_id, details, created_at'
                    . " FROM ld_audit_log WHERE item_type = ? AND reference_id IN ({$in})"
                    . ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit
                );
                $stmt->execute(array_merge([$itemType], $ids));
                $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (Throwable $e) {
                DbError::capture($e, "workforce integration: history of {$dataset}");
            }
        }

        $history = [];
        $actorIds = [];

        foreach ($rows as $row) {
            $details = $row['details'] !== null ? json_decode((string) $row['details'], true) : null;
            if (!is_array($details) || (string) ($details['dataset'] ?? '') !== $dataset) {
                continue;
            }

            $isRequest = (string) $row['item_type'] === self::AUDIT_REQUEST_ITEM;
            $recordId = $isRequest ? (int) ($details['record'] ?? 0) : (int) $row['reference_id'];
            if ($recordId <= 0 || !in_array($recordId, $recordIds, true)) {
                continue;
            }

            $actorId = (int) $row['user_id'];
            $actorIds[$actorId] = true;

            $history[$recordId][] = [
                'id'          => (int) $row['id'],
                'kind'        => $isRequest ? 'request' : 'status',
                'action'      => (string) $row['action'],
                'from'        => isset($details['from']) ? (string) $details['from'] : null,
                'to'          => isset($details['to']) ? (string) $details['to'] : null,
                'request_id'  => $isRequest ? (int) $row['reference_id'] : null,
                'title'       => isset($details['title']) ? (string) $details['title'] : null,
                'priority'    => isset($details['priority']) ? (string) $details['priority'] : null,
                'department'  => isset($details['department']) ? (string) $details['department'] : null,
                'user_id'     => $actorId,
                'actor'       => null,
                'created_at'  => (string) $row['created_at'],
            ];
        }

        $names = $this->resolveActorNames(array_keys($actorIds));
        $labels = [];

        foreach ($history as $recordId => $events) {
            foreach ($events as $index => $event) {
                $actorId = (int) $event['user_id'];
                if (!isset($labels[$actorId])) {
                    // ld_audit_log carries no name, and an L&D admin id need not have an
                    // employee row, so fall back to the id rather than showing nothing.
                    $labels[$actorId] = $names[$actorId] ?? ('Admin #' . $actorId);
                }
                $history[$recordId][$index]['actor'] = $labels[$actorId];
            }

            usort($history[$recordId], static function (array $a, array $b): int {
                return [$b['created_at'], $b['id']] <=> [$a['created_at'], $a['id']];
            });
        }

        return $history;
    }

    /**
     * Employee names for the ids in an audit trail, keyed by employee id.
     *
     * @param array<int, int|string> $actorIds
     * @return array<int, string>
     */
    public function resolveActorNames(array $actorIds): array
    {
        $actorIds = array_values(array_unique(array_filter(
            array_map('intval', $actorIds),
            static function (int $id): bool {
                return $id > 0;
            }
        )));
        if (!$actorIds) {
            return [];
        }

        try {
            $in = implode(', ', array_fill(0, count($actorIds), '?'));
            $stmt = $this->conn->prepare(
                "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name FROM em_employees WHERE employee_id IN ({$in})"
            );
            $stmt->execute($actorIds);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: actor names');
            return [];
        }

        $names = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = trim((string) $row['name']);
            if ($name !== '') {
                $names[(int) $row['employee_id']] = $name;
            }
        }

        return $names;
    }

    /**
     * Append one triage decision to the shared audit trail ('edit' for a status move,
     * 'create' for a request). A failure here never blocks the write the caller asked for.
     */
    private function auditEvent(string $itemType, string $action, int $referenceId, array $details, int $actorId = 0): void
    {
        if ($actorId <= 0 || $referenceId <= 0) {
            return;
        }

        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO ld_audit_log (user_id, role, action, item_type, reference_id, details)'
                . ' VALUES (:user_id, :role, :action, :item_type, :reference_id, :details)'
            );
            $stmt->execute([
                ':user_id'      => $actorId,
                ':role'         => 'admin',
                ':action'       => in_array($action, ['create', 'edit', 'archive', 'restore', 'review'], true) ? $action : 'edit',
                ':item_type'    => $itemType,
                ':reference_id' => $referenceId,
                ':details'      => json_encode($details, JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $e) {
            DbError::capture($e, 'workforce integration: audit trail');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Inbound writes (called by api/inbound/receive-*.php)
    // ─────────────────────────────────────────────────────────────

    /**
     * Store a training-engagement record sent by Workforce.
     *
     * @return array{success: bool, duplicate: bool, id: int|null, message: string}
     */
    public function receiveTrainingEngagement(array $input): array
    {
        $department = trim((string) ($input['department'] ?? ''));
        if ($department === '') {
            return $this->receiveFailure('department is required');
        }

        $teamType = $this->oneOf($input['learner_team_type'] ?? '', ['individual-learner', 'department-team', 'role-group'], 'individual-learner');
        $activity = $this->oneOf($input['training_activity'] ?? '', ['course', 'learning-path', 'workshop', 'compliance-training', 'other'], 'other');
        $signal   = $this->oneOf($input['engagement_status'] ?? '', ['active', 'in-progress', 'low', 'inactive'], 'active');

        return $this->insertRecord(
            'ld_training_engagement',
            $input,
            [
                'learner_team_type' => $teamType,
                'learner_id'        => $this->optionalId($input['learner_id'] ?? null),
                'learner_name'      => $this->optionalText($input['learner_name'] ?? null, 150),
                'department'        => $this->optionalText($department, 150),
                'training_activity' => $activity,
                'engagement_status' => $signal,
                'engagement_data'   => $this->optionalText($input['engagement_data'] ?? null),
                'recommended_action'=> $this->optionalText($input['recommended_action'] ?? null),
                'notes'             => $this->optionalText($input['notes'] ?? null),
            ]
        );
    }

    /**
     * Store a turnover-risk assessment sent by Workforce.
     *
     * @return array{success: bool, duplicate: bool, id: int|null, message: string}
     */
    public function receiveTurnoverRisk(array $input): array
    {
        $employeeId   = $this->optionalId($input['employee_id'] ?? null);
        $employeeName = $this->optionalText($input['employee_name'] ?? null, 150);

        if ($employeeId === null && ($employeeName === null || $employeeName === '')) {
            return $this->receiveFailure('employee_id or employee_name is required');
        }

        $riskLevel  = $this->oneOf($input['risk_level'] ?? '', ['low', 'medium', 'high', 'critical'], 'medium');
        $riskScore  = $this->optionalPercentage($input['risk_score'] ?? null);
        $assessedOn = $this->optionalDate($input['assessment_date'] ?? null);

        $result = $this->insertRecord(
            'ld_turnover_risk',
            $input,
            [
                'employee_id'        => $employeeId,
                'employee_name'      => $employeeName,
                'department'         => $this->optionalText($input['department'] ?? null, 150),
                'position_name'      => $this->optionalText($input['position_name'] ?? null, 150),
                'risk_level'         => $riskLevel,
                'risk_score'         => $riskScore,
                'risk_factors'       => $this->optionalText($input['risk_factors'] ?? null),
                'recommended_action' => $this->optionalText($input['recommended_action'] ?? null),
                'assessment_date'    => $assessedOn,
            ]
        );

        // A critical assessment pages the L&D team as it arrives, not when someone looks.
        if ($result['success'] && !$result['duplicate'] && $result['id'] !== null) {
            $this->notifyCriticalSignal('turnover_risk', [
                'employee_name' => $employeeName,
                'department'    => $this->optionalText($input['department'] ?? null, 150),
                'risk_level'    => $riskLevel,
                'risk_score'    => $riskScore,
            ], (int) $result['id']);
        }

        return $result;
    }

    /**
     * Store a predicted skill / competency need sent by Workforce.
     *
     * @return array{success: bool, duplicate: bool, id: int|null, message: string}
     */
    public function receiveCompetencyRequirement(array $input): array
    {
        $required = [
            'skill_name'              => 'skill_name',
            'target_department'       => 'target_department',
            'target_proficiency'      => 'target_proficiency',
            'expected_learning_output'=> 'expected_learning_output',
        ];
        foreach ($required as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                return $this->receiveFailure("{$label} is required");
            }
        }

        $employeesNeedingTraining = filter_var($input['employees_needing_training'] ?? null, FILTER_VALIDATE_INT);
        if ($employeesNeedingTraining === false || $employeesNeedingTraining < 0) {
            return $this->receiveFailure('employees_needing_training must be zero or a positive whole number');
        }

        $columns = [
            'skill_name'                => $this->optionalText($input['skill_name'], 150),
            'target_department'         => $this->optionalText($input['target_department'], 150),
            'priority_level'            => $this->oneOf($input['priority_level'] ?? '', ['low', 'medium', 'high', 'critical'], 'medium'),
            'employees_needing_training'=> (int) $employeesNeedingTraining,
            'target_proficiency'        => $this->optionalText($input['target_proficiency'], 50),
            'expected_learning_output'  => $this->optionalText($input['expected_learning_output'], 255),
            'notes'                     => $this->optionalText($input['notes'] ?? null),
        ];

        $result = $this->insertRecord('ld_competency_requirement', $input, $columns);

        // A critical need pages the L&D team as it arrives, not when someone looks.
        if ($result['success'] && !$result['duplicate'] && $result['id'] !== null) {
            $this->notifyCriticalSignal('competency', $columns, (int) $result['id']);
        }

        return $result;
    }

    /**
     * Store a reporting requirement / target sent by Workforce.
     *
     * @return array{success: bool, duplicate: bool, id: int|null, message: string}
     */
    public function receiveReportingRequirement(array $input): array
    {
        $targetName = trim((string) ($input['target_name'] ?? ''));
        if ($targetName === '') {
            return $this->receiveFailure('target_name is required');
        }

        $periodStart = $this->normalizeDate($input['reporting_period_start'] ?? ($input['period_start'] ?? null));
        $periodEnd   = $this->normalizeDate($input['reporting_period_end'] ?? ($input['period_end'] ?? null));
        if ($periodStart === null || $periodEnd === null) {
            return $this->receiveFailure('reporting_period_start and reporting_period_end must be valid YYYY-MM-DD dates');
        }
        if ($periodEnd < $periodStart) {
            return $this->receiveFailure('reporting_period_end cannot be before reporting_period_start');
        }

        $completionTarget = filter_var($input['completion_target'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($completionTarget === false || $completionTarget < 0 || $completionTarget > 100) {
            return $this->receiveFailure('completion_target must be a number from 0 to 100');
        }

        $expectedOutput = trim((string) ($input['expected_output'] ?? ''));
        if ($expectedOutput === '') {
            return $this->receiveFailure('expected_output is required');
        }

        return $this->insertRecord(
            'ld_reporting_requirement',
            $input,
            [
                'target_name'            => $this->optionalText($targetName, 150),
                'reporting_period_start' => $periodStart,
                'reporting_period_end'   => $periodEnd,
                'reporting_frequency'    => $this->oneOf($input['reporting_frequency'] ?? '', ['weekly', 'monthly', 'quarterly', 'annual'], 'monthly'),
                'completion_target'      => round((float) $completionTarget, 2),
                'expected_output'        => $this->optionalText($expectedOutput, 255),
                'notes'                  => $this->optionalText($input['notes'] ?? null),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Shared insert for the four reception tables: adds the source marker and an
     * optional sender reference, then skips a record that was already received.
     *
     * @return array{success: bool, duplicate: bool, id: int|null, message: string}
     */
    private function insertRecord(string $table, array $input, array $columns): array
    {
        // Only the two tables this module created carry a sender reference; the
        // shared competency / reporting tables keep the Workforce column list.
        $supportsExternalReference = in_array($table, ['ld_training_engagement', 'ld_turnover_risk'], true);

        $externalReference = $supportsExternalReference
            ? $this->optionalText($input['external_reference_id'] ?? null, 120)
            : null;
        $sourceModule = $this->optionalText($input['source_module'] ?? null, 80) ?? $this->sourceModule;
        $createdBy = $this->optionalId($input['created_by'] ?? null);

        try {
            if ($externalReference !== null) {
                $exists = $this->conn->prepare("SELECT id FROM {$table} WHERE source_module = :module AND external_reference_id = :ref LIMIT 1");
                $exists->execute([':module' => $sourceModule, ':ref' => $externalReference]);
                $existingId = $exists->fetchColumn();
                if ($existingId !== false) {
                    return [
                        'success'   => true,
                        'duplicate' => true,
                        'id'        => (int) $existingId,
                        'message'   => 'Already received',
                    ];
                }
            }

            $leading = ['source_module' => $sourceModule];
            if ($supportsExternalReference) {
                $leading['external_reference_id'] = $externalReference;
            }
            $leading['created_by'] = $createdBy;

            $columns = array_merge($leading, $columns);

            $names = array_keys($columns);
            $placeholders = array_map(static function (string $name): string {
                return ':' . $name;
            }, $names);
            $params = [];
            foreach ($columns as $name => $value) {
                $params[':' . $name] = $value;
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO ' . $table . ' (' . implode(', ', $names) . ')'
                . ' VALUES (' . implode(', ', $placeholders) . ')'
            );
            $stmt->execute($params);

            return [
                'success'   => true,
                'duplicate' => false,
                'id'        => (int) $this->conn->lastInsertId(),
                'message'   => 'Received',
            ];
        } catch (Throwable $e) {
            DbError::capture($e, "workforce integration: receive into {$table}");
            return [
                'success'   => false,
                'duplicate' => false,
                'id'        => null,
                'message'   => 'The record could not be stored.',
            ];
        }
    }

    /** @return array{success: false, duplicate: false, id: null, message: string} */
    private function receiveFailure(string $message): array
    {
        return ['success' => false, 'duplicate' => false, 'id' => null, 'message' => $message];
    }

    /** Return $value when it is one of $allowed, otherwise $fallback. */
    private function oneOf($value, array $allowed, string $fallback): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function optionalId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return ($id === false || $id <= 0) ? null : (int) $id;
    }

    /** Trimmed, length-capped text; null when empty. */
    private function optionalText($value, int $maxLength = 0): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        return $maxLength > 0 ? mb_substr($text, 0, $maxLength) : $text;
    }

    /** 0-100 score (e.g. risk_score) or null when absent/invalid. */
    private function optionalPercentage($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $number = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($number === false) {
            return null;
        }
        return round(max(0.0, min(100.0, (float) $number)), 2);
    }

    private function optionalDate($value): ?string
    {
        $date = $this->normalizeDate($value);
        return $date;
    }

    /** Accepts YYYY-MM-DD (or anything strtotime understands); returns YYYY-MM-DD or null. */
    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = trim((string) $value);
        $date = DateTime::createFromFormat('Y-m-d', $text);
        if ($date instanceof DateTime && $date->format('Y-m-d') === $text) {
            return $text;
        }
        $timestamp = strtotime($text);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Build the shared WHERE clause (free-text search + department + status) for
     * one dataset. Returns ["WHERE ..."|"", [param => value]].
     */
    private function buildFilters(string $dataset, array $filters): array
    {
        $clauses = [];
        $params = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $searchParts = [];
            foreach (self::SEARCH_COLUMNS[$dataset] ?? [] as $index => $column) {
                $key = ':q' . $index;
                $searchParts[] = "{$column} LIKE {$key}";
                $params[$key] = '%' . $search . '%';
            }
            if ($searchParts) {
                $clauses[] = '(' . implode(' OR ', $searchParts) . ')';
            }
        }

        $department = trim((string) ($filters['dept'] ?? ''));
        if ($department !== '' && !in_array($dataset, ['reporting'], true)) {
            $column = $dataset === 'competency' ? 'target_department' : 'department';
            $clauses[] = "{$column} = :dept";
            $params[':dept'] = $department;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && $this->isStatusForDataset($dataset, $status)) {
            $clauses[] = 'status = :status';
            $params[':status'] = $status;
        }

        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }
}
