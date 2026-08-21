<?php

require_once 'ExitManagementModel.php';

class ExitInterviewModel extends ExitManagementModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureExitInterviewSchema();
        $this->ensureHrAssessmentTable();
    }

    /**
     * Ensure HR assessment table exists for storing post-interview HR notes
     */
    protected function ensureHrAssessmentTable(): void
    {
        $db = $this->db;
        $db->exec("CREATE TABLE IF NOT EXISTS exit_interview_hr_assessments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            interview_id INT NOT NULL,
            summary TEXT,
            key_findings TEXT,
            hr_recommendations TEXT,
            follow_up_actions TEXT,
            rehire_eligibility ENUM('yes','no','conditional') DEFAULT NULL,
            knowledge_transfer_required TINYINT(1) DEFAULT 0,
            clearance_recommendation ENUM('clear','not_clear','pending') DEFAULT 'pending',
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            INDEX (interview_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    protected function ensureExitInterviewSchema(): void
    {
        if (!$this->tableExists('exit_interviews')) {
            return;
        }

        $requiredColumns = [
            'exit_case_type' => "ENUM('resignation','termination') DEFAULT NULL",
            'exit_case_id' => 'INT(11) DEFAULT NULL',
            'completed_at' => 'TIMESTAMP NULL DEFAULT NULL'
        ];

        foreach ($requiredColumns as $column => $definition) {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM exit_interviews LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->db->exec("ALTER TABLE exit_interviews ADD COLUMN {$column} {$definition}");
            }
        }

        $this->ensureTableAutoIncrement('exit_interviews');
    }

    /**
     * Get user details by ID.
     */
    public function getUserById(int $userId): ?array
    {
        return parent::getUserById($userId);
    }

    /**
     * Validate that a selected exit case exists and is approved.
     */
    public function getApprovedExitCase(string $exitCaseType, int $exitCaseId): ?array
    {
        if ($exitCaseType === 'resignation') {
            $stmt = $this->db->prepare("SELECT id, employee_id FROM exit_resignations WHERE id = ? AND status = 'approved'");
        } elseif ($exitCaseType === 'termination') {
            $stmt = $this->db->prepare("SELECT id, employee_id FROM exit_terminations WHERE id = ? AND status = 'approved'");
        } else {
            return null;
        }

        $stmt->execute([$exitCaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Schedule an exit interview
     */
    public function scheduleInterview(array $data): int
    {
        if ($this->hasExistingActiveInterview($data['exit_case_type'], (int)$data['exit_case_id'])) {
            throw new Exception('An active exit interview already exists for the selected exit case');
        }

        $stmt = $this->db->prepare("
            INSERT INTO exit_interviews (employee_id, exit_case_type, exit_case_id, interviewer_id, scheduled_date,
                                       scheduled_time, location, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
        ");

        $stmt->execute([
            $data['employee_id'],
            $data['exit_case_type'],
            $data['exit_case_id'],
            $data['interviewer_id'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            $data['location'] ?? 'Virtual',
            $data['notes'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an exit interview
     */
    public function updateInterview(int $interviewId, array $data): bool
    {
        if ($this->hasExistingActiveInterview($data['exit_case_type'], (int)$data['exit_case_id'], $interviewId)) {
            throw new Exception('Another active exit interview already exists for the selected exit case');
        }

        $stmt = $this->db->prepare("
            UPDATE exit_interviews
            SET employee_id = ?, exit_case_type = ?, exit_case_id = ?, interviewer_id = ?, scheduled_date = ?,
                scheduled_time = ?, location = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['employee_id'],
            $data['exit_case_type'],
            $data['exit_case_id'],
            $data['interviewer_id'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            $data['location'] ?? 'Virtual',
            $data['notes'] ?? null,
            $interviewId
        ]);
    }

    /**
     * Check for an existing active interview for the same exit case
     */
    public function hasExistingActiveInterview(string $exitCaseType, int $exitCaseId, int $excludeInterviewId = 0): bool
    {
        $query = "SELECT COUNT(*) FROM exit_interviews WHERE exit_case_type = ? AND exit_case_id = ? AND status = 'scheduled'";
        if ($excludeInterviewId > 0) {
            $query .= " AND id != ?";
        }

        $stmt = $this->db->prepare($query);
        if ($excludeInterviewId > 0) {
            $stmt->execute([$exitCaseType, $exitCaseId, $excludeInterviewId]);
        } else {
            $stmt->execute([$exitCaseType, $exitCaseId]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get exit interview by ID
     */
    public function getInterviewById(int $interviewId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                ei.*,
                COALESCE(e.employee_id, ei.employee_id) AS employee_id,
                COALESCE(CONCAT(e.first_name, ' ', e.last_name), '') AS employee_full_name,
                e.department AS employee_department,
                e.position AS employee_position,
                e.hire_date AS employee_date_hired,
                e.employment_status AS employee_employment_status,
                '' AS manager_name,
                CONCAT(iu.first_name, ' ', iu.last_name) AS interviewer_name,
                CASE WHEN ei.exit_case_type = 'resignation' THEN r.reason ELSE t.termination_reason END AS exit_reason,
                CASE WHEN ei.exit_case_type = 'resignation' THEN r.last_working_date ELSE t.effective_date END AS exit_date,
                r.notice_date,
                t.effective_date AS termination_effective_date,
                COALESCE(r.approved_by, t.approved_by) AS case_approved_by,
                COALESCE(r.approved_at, t.approved_at) AS case_approved_at
            FROM exit_interviews ei
            LEFT JOIN em_employees e ON ei.employee_id = e.employee_id
            LEFT JOIN hrms_employee iu ON ei.interviewer_id = iu.employee_id
            LEFT JOIN exit_resignations r ON ei.exit_case_type = 'resignation' AND ei.exit_case_id = r.id
            LEFT JOIN exit_terminations t ON ei.exit_case_type = 'termination' AND ei.exit_case_id = t.id
            WHERE ei.id = ?
        ");
        $stmt->execute([$interviewId]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($interview) {
            $interview['hr_assessment'] = $this->getHrAssessmentByInterview($interviewId);
        }

        return $interview;
    }

    /**
     * Get HR assessment for an interview
     */
    public function getHrAssessmentByInterview(int $interviewId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM exit_interview_hr_assessments WHERE interview_id = ? LIMIT 1");
        $stmt->execute([$interviewId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Save or update HR assessment for an interview
     */
    public function saveHrAssessment(int $interviewId, array $data, ?int $userId = null): bool
    {
        $existing = $this->getHrAssessmentByInterview($interviewId);

        if ($existing) {
            $stmt = $this->db->prepare("UPDATE exit_interview_hr_assessments SET
                summary = ?, key_findings = ?, hr_recommendations = ?, follow_up_actions = ?,
                rehire_eligibility = ?, knowledge_transfer_required = ?,
                updated_at = NOW()
                WHERE interview_id = ?");

            return (bool)$stmt->execute([
                $data['summary'] ?? null,
                $data['key_findings'] ?? null,
                $data['hr_recommendations'] ?? null,
                $data['follow_up_actions'] ?? null,
                $data['rehire_eligibility'] ?? null,
                !empty($data['knowledge_transfer_required']) ? 1 : 0,
                $interviewId
            ]);
        }

        $stmt = $this->db->prepare("INSERT INTO exit_interview_hr_assessments (
            interview_id, summary, key_findings, hr_recommendations, follow_up_actions,
            rehire_eligibility, knowledge_transfer_required, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        return (bool)$stmt->execute([
            $interviewId,
            $data['summary'] ?? null,
            $data['key_findings'] ?? null,
            $data['hr_recommendations'] ?? null,
            $data['follow_up_actions'] ?? null,
            $data['rehire_eligibility'] ?? null,
            !empty($data['knowledge_transfer_required']) ? 1 : 0,
            $userId
        ]);
    }

    /**
     * Get interviews by employee ID
     */
    public function getInterviewsByEmployee(string $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT ei.*, CONCAT(i.first_name, ' ', i.last_name) as interviewer_name
            FROM exit_interviews ei
            LEFT JOIN hrms_employee i ON ei.interviewer_id = i.employee_id
            WHERE ei.employee_id = ?
            ORDER BY ei.scheduled_date DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all interviews with optional status filter and pagination support
     */
    public function getAllInterviews(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                ei.id,
                ei.employee_id,
                ei.exit_case_type,
                ei.exit_case_id,
                ei.interviewer_id,
                ei.scheduled_date,
                ei.scheduled_time,
                ei.location,
                ei.notes,
                ei.status,
                ei.created_at,
                ei.updated_at,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                CONCAT(u.first_name, ' ', u.last_name) as interviewer_name,
                CASE WHEN h.id IS NOT NULL THEN 1 ELSE 0 END AS has_hr_assessment
            FROM exit_interviews ei
            JOIN em_employees e ON ei.employee_id = e.employee_id
            LEFT JOIN hrms_employee u ON ei.interviewer_id = u.employee_id
            LEFT JOIN exit_interview_hr_assessments h ON ei.id = h.interview_id
        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_interviews ei
            JOIN em_employees e ON ei.employee_id = e.employee_id
            LEFT JOIN hrms_employee u ON ei.interviewer_id = u.employee_id
        ";

        $params = [];
        $whereClause = "";

        if ($status && $status !== 'all') {
            $whereClause = " WHERE ei.status = :status";
            $params['status'] = $status;
        }

        // Add search condition if provided
        if (!empty($search)) {
            $searchCondition = $whereClause ? " AND" : " WHERE";
            $searchCondition .= " (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search1 OR ei.location LIKE :search2)";
            $whereClause .= $searchCondition;
            $searchParam = "%$search%";
            $params['search0'] = $searchParam;
            $params['search1'] = $searchParam;
            $params['search2'] = $searchParam;
        }

        $sql .= $whereClause . ' ORDER BY ei.scheduled_date DESC LIMIT :limit OFFSET :offset';
        $countSql .= $whereClause;

        // Get total count
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated results
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ];
    }

    /**
     * Submit interview feedback
     */
    public function submitFeedback(int $interviewId, array $feedback): bool
    {
        if (!$this->tableExists('exit_interview_feedback')) {
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update interview status
            $stmt = $this->db->prepare("
                UPDATE exit_interviews
                SET status = 'completed', completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$interviewId]);

            // Insert feedback
            $stmt = $this->db->prepare("
                INSERT INTO exit_interview_feedback (interview_id, overall_satisfaction,
                                                   work_environment_rating, management_rating,
                                                   compensation_rating, work_life_balance_rating,
                                                   reason_for_leaving, suggestions, would_recommend,
                                                   additional_comments, submitted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $interviewId,
                $feedback['overall_satisfaction'],
                $feedback['work_environment_rating'],
                $feedback['management_rating'],
                $feedback['compensation_rating'],
                $feedback['work_life_balance_rating'],
                $feedback['reason_for_leaving'],
                $feedback['suggestions'] ?? null,
                $feedback['would_recommend'],
                $feedback['additional_comments'] ?? null
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get feedback by interview ID
     */
    public function getFeedbackByInterview(int $interviewId): ?array
    {
        if (!$this->tableExists('exit_interview_feedback')) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM exit_interview_feedback WHERE interview_id = ?
        ");
        $stmt->execute([$interviewId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all scheduled interviews
     */
    public function getScheduledInterviews(): array
    {
        $stmt = $this->db->query("
            SELECT ei.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_id as emp_id,
                   CONCAT(u.first_name, ' ', u.last_name) as interviewer_name,
                   ei.exit_case_type,
                   ei.exit_case_id
            FROM exit_interviews ei
            JOIN em_employees e ON ei.employee_id = e.employee_id
            LEFT JOIN hrms_employee u ON ei.interviewer_id = u.employee_id
            WHERE ei.status = 'scheduled'
            ORDER BY ei.scheduled_date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update interview status
     */
    public function updateInterviewStatus(int $interviewId, string $status): bool
    {
        if ($status === 'completed') {
            $stmt = $this->db->prepare("
                UPDATE exit_interviews
                SET status = ?, completed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
        } else {
            $stmt = $this->db->prepare("
                UPDATE exit_interviews
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
        }
        return $stmt->execute([$status, $interviewId]);
    }

    /**
     * Check if the interview has an HR assessment with at least one non-empty field.
     */
    public function hasHrAssessmentContent(int $interviewId): bool
    {
        $stmt = $this->db->prepare("SELECT summary, key_findings, hr_recommendations, follow_up_actions, rehire_eligibility, knowledge_transfer_required FROM exit_interview_hr_assessments WHERE interview_id = ? LIMIT 1");
        $stmt->execute([$interviewId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            return false;
        }

        return (
            trim((string)($assessment['summary'] ?? '')) !== '' ||
            trim((string)($assessment['key_findings'] ?? '')) !== '' ||
            trim((string)($assessment['hr_recommendations'] ?? '')) !== '' ||
            trim((string)($assessment['follow_up_actions'] ?? '')) !== '' ||
            trim((string)($assessment['rehire_eligibility'] ?? '')) !== '' ||
            (string)($assessment['knowledge_transfer_required'] ?? '') === '1'
        );
    }

    /**
     * Archive interviews completed more than the configured number of days ago.
     */
    public function archiveDueCompletedInterviews(int $days = 3): int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM exit_interviews
            WHERE status = 'completed' AND completed_at IS NOT NULL
              AND completed_at <= DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $archivedCount = 0;
        foreach ($interviews as $interview) {
            if ($this->archiveInterview((int)$interview['id'], 'Auto-archived after 3 days of completion')) {
                $archivedCount++;
            }
        }

        return $archivedCount;
    }

    /**
     * Get archived interviews from exit_archive.
     */
    public function getArchivedInterviews(int $page = 1, int $limit = 10, string $search = ''): array
    {
        if (!$this->tableExists('exit_archive')) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0,
                'new_count' => 0
            ];
        }

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                a.id as archive_id,
                a.original_id,
                a.employee_id,
                COALESCE(
                    CONCAT(e.first_name, ' ', e.last_name),
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.employee_id'))
                ) as employee_name,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), '') as archived_by_name,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.scheduled_date')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.scheduled_date'))
                ) as scheduled_date,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.scheduled_time')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.scheduled_time'))
                ) as scheduled_time,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.status'))
                ) as status,
                a.archived_at,
                a.archive_reason,
                IF(a.archived_at >= DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 0) as is_new
            FROM exit_archive a
            LEFT JOIN em_employees e ON a.employee_id = e.employee_id
            LEFT JOIN hrms_employee u ON a.archived_by = u.employee_id
            WHERE a.archive_type = 'interview' AND a.restored = 0
        ";

        $countSql = "SELECT COUNT(*) as total FROM exit_archive a WHERE a.archive_type = 'interview' AND a.restored = 0";
        $newCountSql = "SELECT COUNT(*) as new_count FROM exit_archive a WHERE a.archive_type = 'interview' AND a.restored = 0 AND a.archived_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";

        $params = [];
        if (!empty($search)) {
            $searchCondition = " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.archive_reason')) LIKE :search)";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
            $newCountSql .= $searchCondition;
            $params['search'] = "%$search%";
        }

        $sql .= ' ORDER BY a.archived_at DESC LIMIT :limit OFFSET :offset';

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':'.$key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $newCountStmt = $this->db->prepare($newCountSql);
        foreach ($params as $key => $value) {
            $newCountStmt->bindValue(':'.$key, $value, PDO::PARAM_STR);
        }
        $newCountStmt->execute();
        $newCount = (int)$newCountStmt->fetch(PDO::FETCH_ASSOC)['new_count'];

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':'.$key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit),
            'new_count' => $newCount
        ];
    }

    /**
     * Archive interview
     */
    public function archiveInterview(int $interviewId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full interview data
        $stmt = $this->db->prepare("SELECT * FROM exit_interviews WHERE id = ?");
        $stmt->execute([$interviewId]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$interview) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Insert into exit_archive
            $archiveStmt = $this->db->prepare("
                INSERT INTO exit_archive (
                    archive_type, original_id, employee_id, title, description, content,
                    status, original_created_by, archived_by, archive_reason, archive_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $title = "Exit Interview - Employee " . ($interview['employee_id'] ?? 'Unknown');
            $description = "Archived exit interview record";
            $content = json_encode($interview);
            $archivedBy = $_SESSION['employee_id'] ?? 1;
            $originalCreatedBy = $interview['created_by'] ?? null;

            $executed = $archiveStmt->execute([
                'interview',
                $interviewId,
                $interview['employee_id'],
                $title,
                $description,
                $content,
                $interview['status'],
                $originalCreatedBy,
                $archivedBy,
                $archiveReason,
                $content
            ]);

            if ($executed === false) {
                $errorInfo = $archiveStmt->errorInfo();
                $message = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown archive insert error';
                throw new Exception('Archive insert failed: ' . $message);
            }

            // Delete from exit_interviews
            $deleteStmt = $this->db->prepare("DELETE FROM exit_interviews WHERE id = ?");
            $deleteExecuted = $deleteStmt->execute([$interviewId]);
            if ($deleteExecuted === false) {
                $errorInfo = $deleteStmt->errorInfo();
                $message = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown delete error';
                throw new Exception('Interview delete failed: ' . $message);
            }

            if ($deleteStmt->rowCount() === 0) {
                throw new Exception('Failed to delete interview after archiving');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Interview archive error: " . $e->getMessage());
            throw new Exception('Interview archive failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Unarchive interview
     */
    public function unarchiveInterview(int $interviewId): bool
    {
        // Get the latest non-restored archived row by original interview id or archive row id
        $stmt = $this->db->prepare(
            "SELECT * FROM exit_archive WHERE archive_type = 'interview' AND restored = 0 AND (original_id = ? OR id = ?) ORDER BY archived_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$interviewId, $interviewId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data from archive_data, falling back to content
            $interviewData = json_decode($archive['archive_data'] ?? '', true);
            if (!$interviewData) {
                $interviewData = json_decode($archive['content'] ?? '', true);
            }
            if (!$interviewData) {
                return false;
            }

            // Insert back into exit_interviews using the current schema
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_interviews (
                    id, employee_id, exit_case_type, exit_case_id, interviewer_id,
                    scheduled_date, scheduled_time, location, notes, status,
                    completed_at, feedback, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    employee_id = VALUES(employee_id),
                    exit_case_type = VALUES(exit_case_type),
                    exit_case_id = VALUES(exit_case_id),
                    interviewer_id = VALUES(interviewer_id),
                    scheduled_date = VALUES(scheduled_date),
                    scheduled_time = VALUES(scheduled_time),
                    location = VALUES(location),
                    notes = VALUES(notes),
                    status = VALUES(status),
                    completed_at = VALUES(completed_at),
                    feedback = VALUES(feedback),
                    created_at = VALUES(created_at),
                    updated_at = VALUES(updated_at)
            ");

            $insertStmt->execute([
                $interviewData['id'] ?? $interviewId,
                $interviewData['employee_id'] ?? '',
                $interviewData['exit_case_type'] ?? 'resignation',
                $interviewData['exit_case_id'] ?? 0,
                $interviewData['interviewer_id'] ?? null,
                $interviewData['scheduled_date'] ?? date('Y-m-d'),
                $interviewData['scheduled_time'] ?? '00:00:00',
                $interviewData['location'] ?? 'Virtual',
                $interviewData['notes'] ?? null,
                $interviewData['status'] ?? 'scheduled',
                $interviewData['completed_at'] ?? null,
                $interviewData['feedback'] ?? null,
                $interviewData['created_at'] ?? date('Y-m-d H:i:s'),
                $interviewData['updated_at'] ?? date('Y-m-d H:i:s')
            ]);

            // Update archive record to mark as restored
            $updateStmt = $this->db->prepare("
                UPDATE exit_archive
                SET restored = 1, restored_by = ?, restored_at = NOW()
                WHERE id = ?
            ");
            $restoredBy = $_SESSION['employee_id'] ?? 1;
            $updateStmt->execute([$restoredBy, $archive['id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Interview unarchive error: " . $e->getMessage());
            return false;
        }
    }
}