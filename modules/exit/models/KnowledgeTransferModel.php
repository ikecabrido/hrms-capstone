<?php

require_once 'ExitManagementModel.php';

class KnowledgeTransferModel extends ExitManagementModel
{
    private ?bool $ldIntegrationAvailable = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureKnowledgeTransferWorkflowSchema();
    }

    private function ensureKnowledgeTransferWorkflowSchema(): void
    {
        try {
            if ($this->tableExists('exit_knowledge_transfer_plans')) {
                $requiredColumns = [
                    'ld_status' => 'VARCHAR(50) NOT NULL DEFAULT "not_requested"',
                    'ld_requested_at' => 'DATETIME NULL DEFAULT NULL',
                    'ld_requested_by' => 'INT(11) NULL DEFAULT NULL',
                    'ld_last_synced_at' => 'DATETIME NULL DEFAULT NULL',
                    'ld_progress_percent' => 'INT(11) NOT NULL DEFAULT 0',
                    'ld_approved_by' => 'INT(11) NULL DEFAULT NULL',
                    'ld_approved_at' => 'DATETIME NULL DEFAULT NULL',
                    'ld_last_sync_notes' => 'TEXT NULL DEFAULT NULL',
                    'ld_path_id' => 'INT(11) NULL DEFAULT NULL',
                    'ld_path_title' => 'VARCHAR(255) NULL DEFAULT NULL',
                    'ld_path_external_ref' => 'VARCHAR(255) NULL DEFAULT NULL',
                ];

                foreach ($requiredColumns as $column => $definition) {
                    if (!$this->columnExists('exit_knowledge_transfer_plans', $column)) {
                        $this->db->exec("ALTER TABLE exit_knowledge_transfer_plans ADD COLUMN {$column} {$definition}");
                    }
                }
            }

            $this->db->exec("CREATE TABLE IF NOT EXISTS exit_kt_ld_requests (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plan_id INT(11) NOT NULL,
                requested_by INT(11) DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'requested',
                progress_percent INT(11) NOT NULL DEFAULT 0,
                requested_at DATETIME DEFAULT NULL,
                last_synced_at DATETIME DEFAULT NULL,
                completed_at DATETIME DEFAULT NULL,
                external_path_id INT(11) DEFAULT NULL,
                external_path_title VARCHAR(255) DEFAULT NULL,
                external_path_ref VARCHAR(255) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_exit_kt_ld_requests_plan (plan_id),
                KEY idx_exit_kt_ld_requests_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->db->exec("CREATE TABLE IF NOT EXISTS exit_kt_ld_status_log (
                id INT(11) NOT NULL AUTO_INCREMENT,
                plan_id INT(11) NOT NULL,
                request_id INT(11) DEFAULT NULL,
                old_status VARCHAR(50) DEFAULT NULL,
                new_status VARCHAR(50) DEFAULT NULL,
                changed_by INT(11) DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_exit_kt_ld_status_log_plan (plan_id),
                KEY idx_exit_kt_ld_status_log_request (request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->ensureTableAutoIncrement('exit_kt_ld_requests');
            $this->ensureTableAutoIncrement('exit_kt_ld_status_log');
        } catch (Throwable $e) {
            error_log('KnowledgeTransferModel schema ensure failed: ' . $e->getMessage());
        }
    }

    private function getLdGateway(): ?object
    {
        if (!file_exists(__DIR__ . '/../classes/LdKnowledgeTransferGateway.php')) {
            return null;
        }

        require_once __DIR__ . '/../classes/LdKnowledgeTransferGateway.php';

        if (!class_exists('LdKnowledgeTransferGateway')) {
            return null;
        }

        return new LdKnowledgeTransferGateway($this->db);
    }

    private function resolveLdStatusFromProgress(int $progressPercent, ?string $gatewayStatus = null): string
    {
        $gatewayStatus = strtolower(trim((string)($gatewayStatus ?? '')));
        if ($gatewayStatus === 'completed' || $progressPercent >= 100) {
            return 'completed';
        }
        if ($gatewayStatus === 'in_progress' || $progressPercent > 0) {
            return 'in_progress';
        }
        if ($gatewayStatus === 'requested') {
            return 'requested';
        }
        return 'not_requested';
    }

    private function setLdWorkflowState(int $planId, string $newStatus, ?string $reason = null, ?int $actorId = null): bool
    {
        if ($planId <= 0) {
            return false;
        }

        $plan = $this->db->prepare("SELECT ld_status, ld_requested_at, ld_last_synced_at, ld_progress_percent FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $plan->execute([$planId]);
        $existing = $plan->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return false;
        }

        $oldStatus = strtolower(trim((string)($existing['ld_status'] ?? 'not_requested')));
        $newStatus = strtolower(trim($newStatus));

        if ($oldStatus === $newStatus && empty($reason)) {
            return true;
        }

        $sql = "UPDATE exit_knowledge_transfer_plans
                SET ld_status = ?,
                    ld_progress_percent = COALESCE(ld_progress_percent, 0),
                    ld_last_synced_at = COALESCE(ld_last_synced_at, NOW()),
                    ld_approved_by = CASE WHEN ? = 'approved' THEN COALESCE(ld_approved_by, ?) ELSE ld_approved_by END,
                    ld_approved_at = CASE WHEN ? = 'approved' THEN COALESCE(ld_approved_at, NOW()) ELSE ld_approved_at END,
                    updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $updated = $stmt->execute([
            $newStatus,
            $newStatus,
            $actorId ?? 0,
            $newStatus,
            $planId,
        ]);

        if (!$updated) {
            return false;
        }

        $latestRequest = $this->db->prepare("SELECT id FROM exit_kt_ld_requests WHERE plan_id = ? ORDER BY created_at DESC LIMIT 1");
        $latestRequest->execute([$planId]);
        $requestId = (int)($latestRequest->fetchColumn() ?: 0);

        if ($oldStatus !== $newStatus) {
            $logStmt = $this->db->prepare("INSERT INTO exit_kt_ld_status_log (plan_id, request_id, old_status, new_status, changed_by, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $logStmt->execute([
                $planId,
                $requestId > 0 ? $requestId : null,
                $oldStatus,
                $newStatus,
                $actorId ?? 0,
                $reason,
            ]);
        }

        return true;
    }

    /**
     * Check whether the required L&D tables and schema columns are available on this server.
     * This is a read-only feature check and never attempts schema changes.
     */
    public function ldKnowledgeTransferIntegrationAvailable(): bool
    {
        if ($this->ldIntegrationAvailable !== null) {
            return $this->ldIntegrationAvailable;
        }

        $requiredTables = ['ld_enrollment', 'ld_course', 'ld_learning_path', 'ld_learning_path_item'];
        foreach ($requiredTables as $tableName) {
            if (!$this->tableExists($tableName)) {
                $this->ldIntegrationAvailable = false;
                return false;
            }
        }

        $checks = [
            'ld_course' => ['id', 'title', 'status'],
            'ld_enrollment' => ['learner_id', 'course_id', 'status', 'enrolled_at'],
            'ld_learning_path' => ['id', 'instructor_id', 'title', 'description', 'assigned_to', 'status', 'type', 'kt_plan_id', 'is_public', 'created_at', 'updated_at'],
            'ld_learning_path_item' => ['learning_path_id', 'item_type', 'reference_id', 'order_index', 'status'],
        ];

        foreach ($checks as $tableName => $columns) {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $stmt = $this->db->prepare(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME IN ($placeholders)"
            );
            $params = [$tableName];
            foreach ($columns as $columnName) {
                $params[] = $columnName;
            }
            $stmt->execute($params);

            if ((int)$stmt->fetchColumn() < count($columns)) {
                $this->ldIntegrationAvailable = false;
                return false;
            }
        }

        $uniqueEnrollmentIndexStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM (
                 SELECT INDEX_NAME
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ld_enrollment'
                   AND NON_UNIQUE = 0
                   AND COLUMN_NAME IN ('learner_id', 'course_id')
                 GROUP BY INDEX_NAME
                 HAVING COUNT(DISTINCT COLUMN_NAME) = 2
             ) AS unique_indexes"
        );
        $uniqueEnrollmentIndexStmt->execute();

        $this->ldIntegrationAvailable = ((int)$uniqueEnrollmentIndexStmt->fetchColumn()) > 0;
        return $this->ldIntegrationAvailable;
    }

    public function getSuccessorCandidates(?int $excludeEmployeeId = null, ?int $includeEmployeeId = null): array
    {
        $excludeEmployeeId = $excludeEmployeeId ?? 0;

        $sql = "
            SELECT
                e.employee_id AS id,
                CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                e.employee_code AS username,
                e.email,
                COALESCE(d.department_name, 'Unknown') AS department
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE LOWER(TRIM(e.employment_status)) = 'active'
              AND (
                    (e.employee_id <> ? AND (e.position_id IS NULL OR e.position_id = 0 OR p.position_id IS NULL))
                    OR (? IS NOT NULL AND e.employee_id = ?)
              )
            ORDER BY e.first_name, e.last_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $excludeEmployeeId,
            $includeEmployeeId,
            $includeEmployeeId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a knowledge transfer plan
     */
    public function createTransferPlan(array $data): int
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO exit_knowledge_transfer_plans (employee_id, successor_id, start_date,
                                                    end_date, status, created_by, created_at)
                VALUES (?, ?, ?, ?, 'active', ?, NOW())
            ");

            $result = $stmt->execute([
                $data['employee_id'],
                $data['successor_id'],
                $data['start_date'],
                $data['end_date'],
                $data['created_by'] ?? 0
            ]);

            if (!$result) {
                throw new Exception('Failed to insert knowledge transfer plan');
            }

            $planId = (int)$this->db->lastInsertId();

            // Insert transfer items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $this->addTransferItems($planId, $data['items']);
            }

            return $planId;
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Update a knowledge transfer plan
     */
    public function updateTransferPlan(int $planId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE exit_knowledge_transfer_plans
            SET employee_id = ?, successor_id = ?, start_date = ?,
                end_date = ?, updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['employee_id'],
            $data['successor_id'],
            $data['start_date'],
            $data['end_date'],
            $planId
        ]);
    }

    /**
     * Add items to a transfer plan
     */
    public function addTransferItems(int $planId, array $items): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO exit_knowledge_transfer_items (plan_id, item_type, title,
                                               description, notes, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        foreach ($items as $item) {
            if (empty($item['type']) || empty($item['title'])) {
                continue;
            }

            $stmt->execute([
                $planId,
                $item['type'],
                $item['title'],
                $item['description'] ?? null,
                $item['notes'] ?? null,
                $item['priority'] ?? 'medium'
            ]);
        }

        return true;
    }

    /**
     * Delete transfer items for a plan
     */
    public function deleteTransferItemsByPlanId(int $planId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM exit_knowledge_transfer_items WHERE plan_id = ?");
        return $stmt->execute([$planId]);
    }

    /**
     * Get transfer plan by ID
     */
    public function getTransferPlanById(int $planId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ktp.id,
                ktp.employee_id,
                ktp.successor_id,
                ktp.start_date,
                ktp.end_date,
                ktp.status,
                ktp.created_at,
                ktp.updated_at,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id as emp_id,
                CONCAT(s.first_name, ' ', s.last_name) as successor_name
            FROM exit_knowledge_transfer_plans ktp
            JOIN em_employees e ON ktp.employee_id = e.employee_id
            LEFT JOIN em_employees s ON ktp.successor_id = s.employee_id
            WHERE ktp.id = ?
        ");
        $stmt->execute([$planId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get transfer plans by employee
     */
    public function getTransferPlansByEmployee(string $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ktp.id,
                ktp.employee_id,
                ktp.successor_id,
                ktp.start_date,
                ktp.end_date,
                ktp.status,
                ktp.created_at,
                ktp.updated_at,
                CONCAT(s.first_name, ' ', s.last_name) as successor_name
            FROM exit_knowledge_transfer_plans ktp
            LEFT JOIN em_employees s ON ktp.successor_id = s.employee_id
            WHERE ktp.employee_id = ?
            ORDER BY ktp.created_at DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get transfer items by plan ID
     */
    public function getTransferItems(int $planId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM exit_knowledge_transfer_items
            WHERE plan_id = ?
            ORDER BY priority DESC, created_at ASC
        ");
        $stmt->execute([$planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get transfer item by ID
     */
    public function getTransferItem(int $itemId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM exit_knowledge_transfer_items WHERE id = ?");
        $stmt->execute([$itemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Update transfer item status
     */
    public function updateItemStatus(int $itemId, string $status, ?string $notes = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE exit_knowledge_transfer_items
            SET status = ?, completed_at = ?, notes = ?
            WHERE id = ?
        ");

        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

        return $stmt->execute([$status, $completedAt, $notes, $itemId]);
    }

    /**
     * Complete transfer plan
     */
    public function completeTransferPlan(int $planId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE exit_knowledge_transfer_plans
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$planId]);
    }

    /**
     * Check whether a schema column exists on a database table.
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
            $stmt->execute([$columnName]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get eligible L&D courses for the resignee, limited to completed/in-progress enrollments.
     */
    public function getResigneeCoursesForKnowledgeTransfer(int $employeeId): array
    {
        if (!$this->ldKnowledgeTransferIntegrationAvailable()) {
            return ['courses' => [], 'skipped_inactive_count' => 0];
        }

        $activeStmt = $this->db->prepare(
            "SELECT DISTINCT e.course_id, c.title AS course_title, c.status AS course_status, e.status AS enrollment_status
             FROM ld_enrollment e
             INNER JOIN ld_course c ON c.id = e.course_id
             WHERE e.learner_id = :employee_id
               AND e.status IN ('completed', 'in_progress')
               AND c.status = 'active'
             ORDER BY c.title ASC"
        );
        $activeStmt->execute([':employee_id' => $employeeId]);
        $courses = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

        $inactiveStmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT e.course_id) AS skipped_inactive_count
             FROM ld_enrollment e
             INNER JOIN ld_course c ON c.id = e.course_id
             WHERE e.learner_id = :employee_id
               AND e.status IN ('completed', 'in_progress')
               AND c.status <> 'active'"
        );
        $inactiveStmt->execute([':employee_id' => $employeeId]);
        $inactiveCount = (int)($inactiveStmt->fetch(PDO::FETCH_ASSOC)['skipped_inactive_count'] ?? 0);

        foreach ($courses as &$course) {
            $course['id'] = (int)($course['course_id'] ?? 0);
            $course['title'] = $course['course_title'] ?? 'Untitled course';
            $course['is_default_selected'] = in_array(($course['enrollment_status'] ?? ''), ['completed', 'in_progress'], true);
        }
        unset($course);

        return ['courses' => $courses, 'skipped_inactive_count' => $inactiveCount];
    }

    /**
     * Get any generated L&D learning path linked to a KT plan.
     */
    public function getLearningPathForPlan(int $planId): ?array
    {
        if (!$this->ldKnowledgeTransferIntegrationAvailable()) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT lp.id,
                    lp.title,
                    lp.description,
                    lp.assigned_to,
                    lp.status,
                    lp.type,
                    lp.kt_plan_id,
                    lp.is_public,
                    lp.created_at,
                    lp.updated_at,
                    CONCAT(s.first_name, ' ', s.last_name) AS successor_name
             FROM ld_learning_path lp
             LEFT JOIN em_employees s ON s.employee_id = lp.assigned_to
             WHERE lp.kt_plan_id = :plan_id
               AND lp.type = 'knowledge_transfer'
             ORDER BY lp.id DESC
             LIMIT 1"
        );
        $stmt->execute([':plan_id' => $planId]);
        $path = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$path) {
            return null;
        }

        $itemStmt = $this->db->prepare(
            "SELECT lpi.id,
                    lpi.learning_path_id,
                    lpi.item_type,
                    lpi.reference_id,
                    lpi.order_index,
                    lpi.status,
                    c.title AS course_title,
                    c.status AS course_status,
                    COALESCE(en.status, 'enrolled') AS successor_status
             FROM ld_learning_path_item lpi
             LEFT JOIN ld_course c ON c.id = lpi.reference_id
             LEFT JOIN ld_enrollment en ON en.course_id = lpi.reference_id AND en.learner_id = :assigned_to
             WHERE lpi.learning_path_id = :path_id
             ORDER BY lpi.order_index ASC, lpi.id ASC"
        );
        $itemStmt->execute([
            ':assigned_to' => (int)($path['assigned_to'] ?? 0),
            ':path_id' => (int)($path['id'] ?? 0),
        ]);
        $path['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        $path['status_summary'] = $this->getLearningPathRequestStatus($planId);

        return $path;
    }

    /**
     * Create an L&D learning path from the KT plan's selected courses.
     */
    public function generateLearningPathForPlan(int $planId, array $selectedCourseIds): array
    {
        return $this->requestLdProcessing($planId, [
            'course_ids' => $selectedCourseIds,
            'requested_by' => (int)($_SESSION['employee_id'] ?? 0),
            'notes' => 'Legacy L&D generation request stored as Exit-owned workflow.',
        ]);
    }

    /**
     * Toggle the public/private visibility of the generated L&D path for a KT plan.
     * This workflow is now handled via the Exit-owned KT/L&D status log instead of writing directly to L&D tables.
     */
    public function toggleLearningPathPublic(int $planId, bool $public): array
    {
        $plan = $this->db->prepare("SELECT id, ld_status FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $plan->execute([$planId]);
        $existing = $plan->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $status = $public ? 'approved' : 'requested';
        $updated = $this->setLdWorkflowState($planId, $status, $public ? 'Learning path visibility approved in Exit workflow.' : 'Learning path visibility reset to requested status.', (int)($_SESSION['employee_id'] ?? 0));

        return [
            'success' => $updated,
            'message' => $public ? 'Learning path visibility updated in Exit workflow.' : 'Learning path visibility reset in Exit workflow.',
            'is_public' => $public ? 1 : 0,
        ];
    }

    public function requestLdProcessing(int $planId, array $requestData = []): array
    {
        if ($planId <= 0) {
            return ['success' => false, 'message' => 'Invalid knowledge transfer plan ID.'];
        }

        $planStmt = $this->db->prepare("SELECT * FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $actorId = (int)($requestData['requested_by'] ?? $_SESSION['employee_id'] ?? 0);
        $notes = trim((string)($requestData['notes'] ?? ''));
        $courseIds = array_values(array_filter(array_values(array_unique(array_map('intval', (array)($requestData['course_ids'] ?? [])))), static fn ($id) => $id > 0));

        $this->db->beginTransaction();
        try {
            $requestStmt = $this->db->prepare("INSERT INTO exit_kt_ld_requests (plan_id, requested_by, status, progress_percent, requested_at, external_path_id, external_path_title, external_path_ref, notes, created_at, updated_at)
                VALUES (?, ?, 'requested', 0, NOW(), NULL, NULL, NULL, ?, NOW(), NOW())");
            $requestOk = $requestStmt->execute([
                $planId,
                $actorId > 0 ? $actorId : null,
                $notes !== '' ? $notes : 'Requested from Exit workflow.',
            ]);

            if (!$requestOk) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to log the L&D request.'];
            }

            $requestId = (int)$this->db->lastInsertId();
            $oldStatus = strtolower(trim((string)($plan['ld_status'] ?? 'not_requested')));
            $newStatus = 'requested';

            $planUpdateStmt = $this->db->prepare("UPDATE exit_knowledge_transfer_plans
                SET ld_status = ?, ld_requested_at = NOW(), ld_requested_by = ?, ld_last_synced_at = NOW(), ld_progress_percent = 0, ld_last_sync_notes = ?, updated_at = NOW()
                WHERE id = ?");
            $planUpdateStmt->execute([
                $newStatus,
                $actorId > 0 ? $actorId : null,
                $notes !== '' ? $notes : 'Requested from Exit workflow.',
                $planId,
            ]);

            if ($oldStatus !== $newStatus) {
                $logStmt = $this->db->prepare("INSERT INTO exit_kt_ld_status_log (plan_id, request_id, old_status, new_status, changed_by, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $logStmt->execute([$planId, $requestId, $oldStatus, $newStatus, $actorId > 0 ? $actorId : null, 'Request sent to L&D']);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'L&D request created successfully.',
                'plan_id' => $planId,
                'request_id' => $requestId,
                'status' => $newStatus,
                'course_count' => count($courseIds),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('requestLdProcessing failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to request L&D processing.'];
        }
    }

    public function getLdProgress(int $planId): array
    {
        $planStmt = $this->db->prepare("SELECT id, employee_id, successor_id, status, ld_status, ld_progress_percent, ld_path_title, ld_path_id, ld_last_sync_notes FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $gateway = $this->getLdGateway();
        $data = [
            'success' => true,
            'plan_id' => $planId,
            'status' => strtolower(trim((string)($plan['ld_status'] ?? 'not_requested'))),
            'progress_percent' => (int)($plan['ld_progress_percent'] ?? 0),
            'path_id' => $plan['ld_path_id'] ?? null,
            'path_title' => $plan['ld_path_title'] ?? null,
            'notes' => $plan['ld_last_sync_notes'] ?? null,
            'items' => [],
        ];

        if (!$gateway || !$this->ldKnowledgeTransferIntegrationAvailable()) {
            return array_merge($data, ['message' => 'L&D integration is not available on this server']);
        }

        try {
            $progress = $gateway->getProgress($planId, (int)($plan['successor_id'] ?? 0));
            $progressPercent = (int)($progress['progress_percent'] ?? $data['progress_percent']);
            $status = $this->resolveLdStatusFromProgress($progressPercent, (string)($progress['status'] ?? $data['status']));
            $data['status'] = $status;
            $data['progress_percent'] = $progressPercent;
            $data['path_id'] = $progress['path_id'] ?? $data['path_id'];
            $data['path_title'] = $progress['path_title'] ?? $data['path_title'];
            $data['items'] = $progress['items'] ?? [];
            $data['successor_name'] = $progress['successor_name'] ?? null;
            $data['course_count'] = (int)($progress['course_count'] ?? count($data['items']));
            $data['completed_count'] = (int)($progress['completed_count'] ?? 0);
            $data['message'] = 'L&D progress retrieved.';
            return $data;
        } catch (Throwable $e) {
            error_log('getLdProgress failed: ' . $e->getMessage());
            return array_merge($data, ['message' => 'Failed to read L&D progress.']);
        }
    }

    public function syncLdProgress(int $planId): array
    {
        $planStmt = $this->db->prepare("SELECT id, successor_id, ld_status, ld_progress_percent FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $currentStatus = strtolower(trim((string)($plan['ld_status'] ?? 'not_requested')));
        $gateway = $this->getLdGateway();
        $progressStatus = $currentStatus;
        $progressPercent = (int)($plan['ld_progress_percent'] ?? 0);
        $pathId = null;
        $pathTitle = null;
        $items = [];

        if ($gateway && $this->ldKnowledgeTransferIntegrationAvailable()) {
            try {
                $progress = $gateway->getProgress($planId, (int)($plan['successor_id'] ?? 0));
                $progressStatus = $this->resolveLdStatusFromProgress((int)($progress['progress_percent'] ?? 0), (string)($progress['status'] ?? $currentStatus));
                $progressPercent = (int)($progress['progress_percent'] ?? 0);
                $pathId = $progress['path_id'] ?? null;
                $pathTitle = $progress['path_title'] ?? null;
                $items = $progress['items'] ?? [];
            } catch (Throwable $e) {
                error_log('syncLdProgress failed: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to sync L&D progress.'];
            }
        }

        $requestStmt = $this->db->prepare("SELECT id FROM exit_kt_ld_requests WHERE plan_id = ? ORDER BY created_at DESC LIMIT 1");
        $requestStmt->execute([$planId]);
        $requestId = (int)($requestStmt->fetchColumn() ?: 0);

        $stmt = $this->db->prepare("UPDATE exit_knowledge_transfer_plans SET ld_status = ?, ld_progress_percent = ?, ld_last_synced_at = NOW(), ld_last_sync_notes = ?, ld_path_id = COALESCE(?, ld_path_id), ld_path_title = COALESCE(?, ld_path_title), updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            $progressStatus,
            $progressPercent,
            $this->ldKnowledgeTransferIntegrationAvailable() ? 'Synced from L&D gateway' : 'L&D integration unavailable; retaining Exit workflow state.',
            $pathId,
            $pathTitle,
            $planId,
        ]);

        if ($requestId > 0 && $progressStatus !== $currentStatus) {
            $logStmt = $this->db->prepare("INSERT INTO exit_kt_ld_status_log (plan_id, request_id, old_status, new_status, changed_by, reason, created_at) VALUES (?, ?, ?, ?, 0, 'Synced from L&D gateway', NOW())");
            $logStmt->execute([$planId, $requestId, $currentStatus, $progressStatus]);
        }

        if ($requestId > 0) {
            $this->db->prepare("UPDATE exit_kt_ld_requests SET status = ?, progress_percent = ?, last_synced_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$progressStatus, $progressPercent, $requestId]);
        }

        return [
            'success' => true,
            'plan_id' => $planId,
            'status' => $progressStatus,
            'progress_percent' => $progressPercent,
            'items' => $items,
            'path_id' => $pathId,
            'path_title' => $pathTitle,
        ];
    }

    public function syncAllPendingLdProgress(): array
    {
        $planStmt = $this->db->query("SELECT id FROM exit_knowledge_transfer_plans WHERE ld_status IN ('not_requested', 'requested', 'in_progress') ORDER BY id ASC");
        $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);
        $processed = 0;
        $results = [];

        foreach ($plans as $plan) {
            $result = $this->syncLdProgress((int)($plan['id'] ?? 0));
            $results[] = $result;
            if (!empty($result['success'])) {
                $processed++;
            }
        }

        return ['success' => true, 'processed' => $processed, 'results' => $results];
    }

    public function approveKtPlan(int $planId, int $actorId = 0, string $notes = ''): array
    {
        $plan = $this->db->prepare("SELECT id, ld_status FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $plan->execute([$planId]);
        if (!$plan->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $updated = $this->setLdWorkflowState($planId, 'approved', $notes !== '' ? $notes : 'Approved by Exit staff.', $actorId > 0 ? $actorId : (int)($_SESSION['employee_id'] ?? 0));
        $this->db->prepare("UPDATE exit_knowledge_transfer_plans SET status = 'completed', completed_at = COALESCE(completed_at, NOW()), updated_at = NOW() WHERE id = ?")->execute([$planId]);

        return [
            'success' => $updated,
            'message' => $updated ? 'Knowledge transfer plan approved.' : 'Failed to approve the knowledge transfer plan.',
            'status' => 'approved',
        ];
    }

    public function returnKtToLd(int $planId, int $actorId = 0, string $notes = ''): array
    {
        $plan = $this->db->prepare("SELECT id, ld_status FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $plan->execute([$planId]);
        if (!$plan->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
        }

        $updated = $this->setLdWorkflowState($planId, 'requested', $notes !== '' ? $notes : 'Returned for L&D revision.', $actorId > 0 ? $actorId : (int)($_SESSION['employee_id'] ?? 0));

        return [
            'success' => $updated,
            'message' => $updated ? 'Knowledge transfer plan returned to L&D for revision.' : 'Failed to return the knowledge transfer plan to L&D.',
            'status' => 'requested',
        ];
    }

    public function getLearningPathRequestStatus(int $planId): array
    {
        $plan = $this->db->prepare("SELECT id, ld_status, ld_progress_percent, ld_path_id, ld_path_title, ld_last_sync_notes, successor_id FROM exit_knowledge_transfer_plans WHERE id = ? LIMIT 1");
        $plan->execute([$planId]);
        $planRow = $plan->fetch(PDO::FETCH_ASSOC);

        if (!$planRow) {
            return [
                'status' => 'not_requested',
                'path_id' => null,
                'title' => null,
                'successor_name' => null,
                'course_count' => 0,
                'is_public' => 0,
                'completion_percent' => 0,
                'courses' => [],
            ];
        }

        $status = strtolower(trim((string)($planRow['ld_status'] ?? 'not_requested')));
        $progressPercent = (int)($planRow['ld_progress_percent'] ?? 0);
        $successorName = $this->db->prepare("SELECT CONCAT(first_name, ' ', last_name) AS successor_name FROM em_employees WHERE employee_id = ? LIMIT 1");
        $successorName->execute([(int)($planRow['successor_id'] ?? 0)]);
        $successor = $successorName->fetch(PDO::FETCH_ASSOC);

        $gateway = $this->getLdGateway();
        $items = [];

        if ($gateway && $this->ldKnowledgeTransferIntegrationAvailable()) {
            try {
                $gatewayProgress = $gateway->getProgress($planId, (int)($planRow['successor_id'] ?? 0));
                $status = $this->resolveLdStatusFromProgress((int)($gatewayProgress['progress_percent'] ?? $progressPercent), (string)($gatewayProgress['status'] ?? $status));
                $progressPercent = (int)($gatewayProgress['progress_percent'] ?? $progressPercent);
                $items = $gatewayProgress['items'] ?? [];
            } catch (Throwable $e) {
                error_log('getLearningPathRequestStatus fallback: ' . $e->getMessage());
            }
        }

        return [
            'status' => $status,
            'path_id' => $planRow['ld_path_id'] ?? null,
            'title' => $planRow['ld_path_title'] ?? 'Knowledge Transfer',
            'successor_name' => $successor['successor_name'] ?? 'Successor',
            'course_count' => count($items),
            'is_public' => 0,
            'completion_percent' => $progressPercent,
            'courses' => $items,
        ];
    }

    /**
     * Get active transfer plans
     */
    public function getActiveTransferPlans(): array
    {
        $stmt = $this->db->query("
            SELECT 
                ktp.id,
                ktp.employee_id,
                ktp.successor_id,
                ktp.start_date,
                ktp.end_date,
                ktp.status,
                ktp.created_at,
                ktp.updated_at,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                CONCAT(s.first_name, ' ', s.last_name) as successor_name
            FROM exit_knowledge_transfer_plans ktp
            JOIN em_employees e ON ktp.employee_id = e.employee_id
            LEFT JOIN em_employees s ON ktp.successor_id = s.employee_id
            WHERE ktp.status = 'active'
            ORDER BY ktp.end_date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all transfer plans with optional status filter and pagination
     */
    public function getAllTransferPlans(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                ktp.id,
                ktp.employee_id,
                ktp.successor_id,
                ktp.start_date,
                ktp.end_date,
                ktp.status,
                ktp.created_at,
                ktp.updated_at,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                CONCAT(s.first_name, ' ', s.last_name) as successor_name,
                (SELECT lp.id FROM ld_learning_path lp WHERE lp.kt_plan_id = ktp.id AND lp.type = 'knowledge_transfer' ORDER BY lp.id DESC LIMIT 1) AS ld_path_id,
                (SELECT lp.title FROM ld_learning_path lp WHERE lp.kt_plan_id = ktp.id AND lp.type = 'knowledge_transfer' ORDER BY lp.id DESC LIMIT 1) AS ld_path_title,
                (SELECT CASE WHEN EXISTS (SELECT 1 FROM ld_learning_path lp WHERE lp.kt_plan_id = ktp.id AND lp.type = 'knowledge_transfer') THEN 'Path created' ELSE 'Not requested' END) AS ld_status_label,
                (SELECT lp.is_public FROM ld_learning_path lp WHERE lp.kt_plan_id = ktp.id AND lp.type = 'knowledge_transfer' ORDER BY lp.id DESC LIMIT 1) AS ld_is_public,
                (SELECT COUNT(*) FROM ld_learning_path_item lpi WHERE lpi.learning_path_id = (SELECT lp2.id FROM ld_learning_path lp2 WHERE lp2.kt_plan_id = ktp.id AND lp2.type = 'knowledge_transfer' ORDER BY lp2.id DESC LIMIT 1)) AS ld_course_count
            FROM exit_knowledge_transfer_plans ktp
            JOIN em_employees e ON ktp.employee_id = e.employee_id
            LEFT JOIN em_employees s ON ktp.successor_id = s.employee_id
        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_knowledge_transfer_plans ktp
            JOIN em_employees e ON ktp.employee_id = e.employee_id
            LEFT JOIN em_employees s ON ktp.successor_id = s.employee_id
        ";

        $params = [];
        $whereClause = "";

        if ($status && $status !== 'all') {
            $whereClause = " WHERE ktp.status = :status";
            $params['status'] = $status;
        }

        // Add search condition if provided
        if (!empty($search)) {
            $searchCondition = $whereClause ? " AND" : " WHERE";
            $searchCondition .= " (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR CONCAT(s.first_name, ' ', s.last_name) LIKE :search1)";
            $whereClause .= $searchCondition;
            $searchParam = "%$search%";
            $params['search0'] = $searchParam;
            $params['search1'] = $searchParam;
        }

        // Get total count
        $countStmt = $this->db->prepare($countSql . $whereClause);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated data
        $stmt = $this->db->prepare($sql . $whereClause . " ORDER BY ktp.created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Get archived transfer plans from exit_archive with pagination and new-count metadata
     */
    public function getArchivedTransferPlans(int $page = 1, int $limit = 10, string $search = ''): array
    {
        if (!$this->tableExists('exit_archive')) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'new_count' => 0
            ];
        }

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                a.id as archive_id,
                a.original_id as plan_id,
                a.employee_id,
                COALESCE(
                    CONCAT(e.first_name, ' ', e.last_name),
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.employee_id'))
                ) as employee_name,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.start_date')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.start_date'))
                ) as start_date,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.end_date')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.end_date'))
                ) as end_date,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')),
                    JSON_UNQUOTE(JSON_EXTRACT(a.content, '$.status'))
                ) as status,
                a.archived_at,
                a.archive_reason,
                IF(a.archived_at >= DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 0) as is_new
            FROM exit_archive a
            LEFT JOIN em_employees e ON a.employee_id = e.employee_id
            WHERE a.archive_type = 'transfer_plan' AND a.restored = 0
        ";

        $countSql = "SELECT COUNT(*) as total FROM exit_archive a WHERE a.archive_type = 'transfer_plan' AND a.restored = 0";
        $newCountSql = "SELECT COUNT(*) as new_count FROM exit_archive a WHERE a.archive_type = 'transfer_plan' AND a.restored = 0 AND a.archived_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";

        $params = [];
        if (!empty($search)) {
            $searchCondition = " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.start_date')) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.end_date')) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) LIKE :search OR a.archive_reason LIKE :search)";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
            $newCountSql .= $searchCondition;
            $params['search'] = "%$search%";
        }

        $sql .= ' ORDER BY a.archived_at DESC LIMIT :limit OFFSET :offset';

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $newCountStmt = $this->db->prepare($newCountSql);
        foreach ($params as $key => $value) {
            $newCountStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $newCountStmt->execute();
        $newCount = (int)$newCountStmt->fetch(PDO::FETCH_ASSOC)['new_count'];

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'new_count' => $newCount
        ];
    }

    /**
     * Delete a knowledge transfer plan and its associated items
     */
    public function deleteTransferPlan(int $planId): bool
    {
        try {
            // Delete associated transfer items first (cascade)
            $stmt = $this->db->prepare("
                DELETE FROM exit_knowledge_transfer_items
                WHERE plan_id = ?
            ");
            $stmt->execute([$planId]);

            // Delete the transfer plan
            $stmt = $this->db->prepare("
                DELETE FROM exit_knowledge_transfer_plans
                WHERE id = ?
            ");
            return $stmt->execute([$planId]);
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Archive transfer plan
     */
    public function archiveTransferPlan(int $planId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full transfer plan data
        $stmt = $this->db->prepare("SELECT * FROM exit_knowledge_transfer_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
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

            $title = "Knowledge Transfer Plan #{$planId}";
            $description = "Archived knowledge transfer plan for employee {$plan['employee_id']}";
            $content = json_encode($plan);
            $archivedBy = $_SESSION['employee_id'] ?? 1;

            $archiveStmt->execute([
                'transfer_plan',
                $planId,
                $plan['employee_id'],
                $title,
                $description,
                $content,
                $plan['status'],
                $plan['created_by'],
                $archivedBy,
                $archiveReason,
                $content
            ]);

            // Delete from exit_knowledge_transfer_plans
            $deleteStmt = $this->db->prepare("DELETE FROM exit_knowledge_transfer_plans WHERE id = ?");
            $deleteStmt->execute([$planId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Transfer plan archive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive transfer item
     */
    public function archiveTransferItem(int $itemId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full transfer item data and owner plan details
        $stmt = $this->db->prepare("SELECT iti.*, ktp.employee_id FROM exit_knowledge_transfer_items iti JOIN exit_knowledge_transfer_plans ktp ON iti.plan_id = ktp.id WHERE iti.id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
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

            $title = "Transfer Item - " . ($item['title'] ?? 'Unknown Item');
            $description = "Archived knowledge transfer item";
            $content = json_encode($item);
            $archivedBy = $_SESSION['employee_id'] ?? 1;

            $archiveStmt->execute([
                'transfer_item',
                $itemId,
                $item['employee_id'] ?? null,
                $title,
                $description,
                $content,
                $item['status'] ?? null,
                null,
                $archivedBy,
                $archiveReason,
                $content
            ]);

            // Delete from exit_knowledge_transfer_items
            $deleteStmt = $this->db->prepare("DELETE FROM exit_knowledge_transfer_items WHERE id = ?");
            $deleteStmt->execute([$itemId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Transfer item archive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unarchive transfer plan
     */
    public function unarchiveTransferPlan(int $planId): bool
    {
        // Get the latest non-restored archived record for this transfer plan
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'transfer_plan' AND original_id = ? AND restored = 0 ORDER BY archived_at DESC LIMIT 1");
        $stmt->execute([$planId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data
            $planData = json_decode($archive['archive_data'], true);
            if (!$planData) {
                return false;
            }

            // Insert back into exit_knowledge_transfer_plans
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_knowledge_transfer_plans (
                    id, employee_id, successor_id, start_date, end_date, status, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $planData['id'],
                $planData['employee_id'],
                $planData['successor_id'] ?? null,
                $planData['start_date'] ?? null,
                $planData['end_date'] ?? null,
                $planData['status'] ?? 'active',
                $planData['created_by'] ?? null,
                $planData['created_at'] ?? date('Y-m-d H:i:s'),
                $planData['updated_at'] ?? date('Y-m-d H:i:s')
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
            error_log("Transfer plan unarchive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unarchive transfer item
     */
    public function unarchiveTransferItem(int $itemId): bool
    {
        // Get the latest non-restored archived record for this transfer item
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'transfer_item' AND original_id = ? AND restored = 0 ORDER BY archived_at DESC LIMIT 1");
        $stmt->execute([$itemId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data
            $itemData = json_decode($archive['archive_data'], true);
            if (!$itemData) {
                return false;
            }

            // Insert back into exit_knowledge_transfer_items
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_knowledge_transfer_items (
                id, plan_id, item_type, title, description, notes, priority, status, completed_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $itemData['id'],
                $itemData['plan_id'],
                $itemData['item_type'] ?? $itemData['type'],
                $itemData['title'],
                $itemData['description'] ?? null,
                $itemData['notes'] ?? null,
                $itemData['priority'] ?? 'medium',
                $itemData['status'] ?? 'pending',
                $itemData['completed_at'] ?? null,
                $itemData['created_at'] ?? date('Y-m-d H:i:s')
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
            error_log("Transfer item unarchive error: " . $e->getMessage());
            return false;
        }
    }
}