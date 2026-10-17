<?php

require_once 'ExitManagementModel.php';

class KnowledgeTransferModel extends ExitManagementModel
{
    private ?bool $ldIntegrationAvailable = null;

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
                   AND TABLE_NAME = :table_name
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

    public function getLearningPathRequestStatus(int $planId): array
    {
        if (!$this->ldKnowledgeTransferIntegrationAvailable()) {
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

        $stmt = $this->db->prepare(
            "SELECT lp.id,
                    lp.title,
                    lp.assigned_to,
                    lp.is_public,
                    lp.type,
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

        $itemStmt = $this->db->prepare(
            "SELECT lpi.reference_id,
                    c.title AS course_title,
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
        $courses = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $courseCount = count($courses);
        $completedCount = 0;
        foreach ($courses as $course) {
            if (strtolower((string)($course['successor_status'] ?? 'enrolled')) === 'completed') {
                $completedCount++;
            }
        }

        return [
            'status' => 'path_created',
            'path_id' => (int)($path['id'] ?? 0),
            'title' => $path['title'] ?? 'Knowledge Transfer Path',
            'successor_name' => $path['successor_name'] ?? 'Successor',
            'course_count' => $courseCount,
            'is_public' => (int)($path['is_public'] ?? 0),
            'completion_percent' => $courseCount > 0 ? (int)round(($completedCount / $courseCount) * 100) : 0,
            'courses' => $courses,
        ];
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
        if (!$this->ldKnowledgeTransferIntegrationAvailable()) {
            return ['success' => false, 'message' => 'L&D integration is not available on this server'];
        }

        $courseIds = array_values(array_filter(array_values(array_unique(array_map('intval', $selectedCourseIds))), static fn ($id) => $id > 0));

        if (empty($courseIds)) {
            return ['success' => false, 'message' => 'Select at least one eligible course to generate the learning path.'];
        }

        $createdBy = (int)($_SESSION['employee_id'] ?? 0);
        if ($createdBy <= 0) {
            return ['success' => false, 'message' => 'Unable to identify the current user for the learning path.'];
        }

        try {
            $this->db->beginTransaction();

            $employeeName = $this->db->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM em_employees WHERE employee_id = :employee_id LIMIT 1");
            $successorName = $this->db->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM em_employees WHERE employee_id = :successor_id LIMIT 1");

            $planLockStmt = $this->db->prepare(
                "SELECT id
                 FROM exit_knowledge_transfer_plans
                 WHERE id = :plan_id
                 FOR UPDATE"
            );
            $planLockStmt->execute([':plan_id' => $planId]);
            $lockedPlan = $planLockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lockedPlan) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
            }

            $planStmt = $this->db->prepare(
                "SELECT employee_id, successor_id, status
                 FROM exit_knowledge_transfer_plans
                 WHERE id = :plan_id
                 LIMIT 1"
            );
            $planStmt->execute([':plan_id' => $planId]);
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Knowledge transfer plan not found.'];
            }

            $planEmployeeId = (int)($plan['employee_id'] ?? 0);
            $planSuccessorId = (int)($plan['successor_id'] ?? 0);
            $planStatus = strtolower(trim((string)($plan['status'] ?? '')));

            if ($planEmployeeId <= 0 || $planSuccessorId <= 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'The plan must have both a resignee and successor to generate a learning path.'];
            }

            if (in_array($planStatus, ['archived', 'cancelled', 'completed'], true) || $planSuccessorId === $planEmployeeId) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This knowledge transfer plan is not eligible for generation.'];
            }

            $eligibleCoursesStmt = $this->db->prepare(
                "SELECT DISTINCT e.course_id
                 FROM ld_enrollment e
                 INNER JOIN ld_course c ON c.id = e.course_id AND c.status = 'active'
                 WHERE e.learner_id = :employee_id
                   AND e.status IN ('completed', 'in_progress')
                   AND e.course_id IN (" . implode(',', array_fill(0, count($courseIds), '?')) . ")"
            );
            $eligibleCoursesStmt->execute(array_merge([$planEmployeeId], $courseIds));
            $eligibleCourseIds = array_map('intval', array_column($eligibleCoursesStmt->fetchAll(PDO::FETCH_ASSOC), 'course_id'));

            if (count($eligibleCourseIds) !== count($courseIds)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'One or more selected courses are not eligible for the resignee.'];
            }

            $duplicateCheckStmt = $this->db->prepare(
                "SELECT id
                 FROM ld_learning_path
                 WHERE kt_plan_id = :plan_id
                   AND type = 'knowledge_transfer'
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $duplicateCheckStmt->execute([':plan_id' => $planId]);
            $existingPath = $duplicateCheckStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingPath) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'A learning path already exists for KT plan #' . (int)($planId) . ' and cannot be generated again.'];
            }

            $employeePathCheckStmt = $this->db->prepare(
                "SELECT ktp.id
                 FROM exit_knowledge_transfer_plans ktp
                 INNER JOIN ld_learning_path lp ON lp.kt_plan_id = ktp.id AND lp.type = 'knowledge_transfer'
                 WHERE ktp.employee_id = :employee_id
                   AND ktp.status <> 'cancelled'
                   AND ktp.id <> :plan_id
                 ORDER BY ktp.id DESC
                 LIMIT 1"
            );
            $employeePathCheckStmt->execute([
                ':employee_id' => $planEmployeeId,
                ':plan_id' => $planId,
            ]);
            $employeeExistingPlan = $employeePathCheckStmt->fetch(PDO::FETCH_ASSOC);
            if ($employeeExistingPlan) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This employee already has a learning path on KT plan #' . (int)($employeeExistingPlan['id'] ?? 0) . '.'];
            }

            $employeeName->execute([':employee_id' => $planEmployeeId]);
            $employeeNameText = trim((string)$employeeName->fetchColumn());

            $successorName->execute([':successor_id' => $planSuccessorId]);
            $successorNameText = trim((string)$successorName->fetchColumn());

            $pathTitle = "Knowledge Transfer \u{2014} " . ($employeeNameText ?: 'Employee');
            $pathDescription = 'Generated automatically from Exit Knowledge Transfer plan #' . $planId . ' for successor handoff to ' . ($successorNameText ?: 'Successor') . '.';

            $pathInsertSql = "
                INSERT INTO ld_learning_path (instructor_id, title, description, assigned_to, status, type, kt_plan_id, is_public, created_at, updated_at)
                VALUES (:instructor_id, :title, :description, :assigned_to, 'active', 'knowledge_transfer', :kt_plan_id, 0, NOW(), NOW())
            ";
            $pathStmt = $this->db->prepare($pathInsertSql);
            $pathStmt->execute([
                ':instructor_id' => $createdBy,
                ':title' => $pathTitle,
                ':description' => $pathDescription,
                ':assigned_to' => $planSuccessorId,
                ':kt_plan_id' => $planId,
            ]);

            $pathId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                "INSERT INTO ld_learning_path_item (learning_path_id, item_type, reference_id, order_index, status)
                 VALUES (:learning_path_id, 'course', :reference_id, :order_index, 'active')"
            );

            foreach ($courseIds as $index => $courseId) {
                $itemStmt->execute([
                    ':learning_path_id' => $pathId,
                    ':reference_id' => $courseId,
                    ':order_index' => $index + 1,
                ]);
            }

            $enrollmentInsertStmt = $this->db->prepare(
                "INSERT INTO ld_enrollment (learner_id, course_id, status, enrolled_at, created_at)
                 VALUES (:learner_id, :course_id, 'enrolled', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE id = id"
            );

            foreach ($courseIds as $courseId) {
                $enrollmentInsertStmt->execute([
                    ':learner_id' => $planSuccessorId,
                    ':course_id' => $courseId,
                ]);
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Knowledge transfer learning path generated successfully.', 'path_id' => $pathId, 'course_count' => count($courseIds)];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('generateLearningPathForPlan PDOException: ' . $e->getMessage());

            switch ((int)($e->errorInfo[1] ?? 0)) {
                case 1062:
                    return ['success' => false, 'message' => 'already generated or in progress'];
                case 1213:
                case 1205:
                    return ['success' => false, 'message' => 'Another request is processing this plan, please retry'];
                case 1452:
                    return ['success' => false, 'message' => 'A referenced employee or course no longer exists'];
                default:
                    return ['success' => false, 'message' => 'Failed to generate the learning path.'];
            }
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('generateLearningPathForPlan failure: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate the learning path.'];
        }
    }

    /**
     * Toggle the public/private visibility of the generated L&D path for a KT plan.
     */
    public function toggleLearningPathPublic(int $planId, bool $public): array
    {
        if (!$this->ldKnowledgeTransferIntegrationAvailable()) {
            return ['success' => false, 'message' => 'L&D integration is not available on this server'];
        }

        $lookupStmt = $this->db->prepare(
            "SELECT id
             FROM ld_learning_path
             WHERE kt_plan_id = :plan_id
               AND type = 'knowledge_transfer'
             ORDER BY id DESC
             LIMIT 1"
        );
        $lookupStmt->execute([':plan_id' => $planId]);

        if (!$lookupStmt->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'message' => 'No knowledge-transfer learning path was found for this plan.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE ld_learning_path
             SET is_public = :is_public, updated_at = NOW()
             WHERE kt_plan_id = :plan_id
               AND type = 'knowledge_transfer'"
        );

        $stmt->execute([
            ':is_public' => $public ? 1 : 0,
            ':plan_id' => $planId,
        ]);

        return ['success' => true, 'message' => $public ? 'Learning path published.' : 'Learning path made private.', 'is_public' => $public ? 1 : 0];
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