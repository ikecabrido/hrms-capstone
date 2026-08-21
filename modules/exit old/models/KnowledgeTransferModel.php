<?php

require_once 'ExitManagementModel.php';

class KnowledgeTransferModel extends ExitManagementModel
{
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
                CONCAT(s.first_name, ' ', s.last_name) as successor_name
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