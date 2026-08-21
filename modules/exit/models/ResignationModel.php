<?php

require_once 'ExitManagementModel.php';
require_once 'SettlementModel.php';

class ResignationModel extends ExitManagementModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureExitResignationsAutoIncrement();
        $this->ensureResignationSchema();
        $this->ensurePayrollClearancesTable();
    }

    private function ensureExitResignationsAutoIncrement(): void
    {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM exit_resignations LIKE 'id'");
        $stmt->execute();
        $column = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($column && stripos($column['Extra'] ?? '', 'auto_increment') === false) {
            $zeroRows = $this->db->query("SELECT id FROM exit_resignations WHERE id = 0")->fetchAll(PDO::FETCH_ASSOC);
            if ($zeroRows) {
                $maxId = (int)$this->db->query("SELECT MAX(id) as max_id FROM exit_resignations")->fetchColumn();
                $nextId = $maxId + 1;
                foreach ($zeroRows as $row) {
                    $this->db->exec("UPDATE exit_resignations SET id = $nextId WHERE id = 0 LIMIT 1");
                    $nextId++;
                }
            }
            $this->db->exec("ALTER TABLE exit_resignations MODIFY id int(11) NOT NULL AUTO_INCREMENT");
        }
    }

    private function ensureResignationSchema(): void
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'exit_resignations'");
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return;
            }

            $desiredStatusEnum = "'pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn'";
            $statusStmt = $this->db->prepare("SHOW COLUMNS FROM exit_resignations LIKE 'status'");
            $statusStmt->execute();
            $statusColumn = $statusStmt->fetch(PDO::FETCH_ASSOC);

            if ($statusColumn && stripos($statusColumn['Type'], 'pending_review') === false) {
                $this->db->exec("UPDATE exit_resignations SET status = 'pending_review' WHERE status = 'pending_hr_review'");
                $this->db->exec("UPDATE exit_resignations SET status = 'rejected' WHERE status = 'rejected_by_hr'");
                $this->db->exec("ALTER TABLE exit_resignations MODIFY status ENUM($desiredStatusEnum) NOT NULL DEFAULT 'pending_review'");
            }

            $requiredColumns = [
                'resignation_letter_path' => 'varchar(500) DEFAULT NULL',
                'hr_approved_by' => 'int(11) DEFAULT NULL',
                'hr_approved_at' => 'datetime DEFAULT NULL',
                'hr_approval_comments' => 'text DEFAULT NULL',
                'reviewed_by' => 'int(11) DEFAULT NULL',
                'reviewed_at' => 'datetime DEFAULT NULL',
                'review_remarks' => 'text DEFAULT NULL',
                'legal_approved_by' => 'int(11) DEFAULT NULL',
                'legal_approved_at' => 'datetime DEFAULT NULL',
                'legal_approval_comments' => 'text DEFAULT NULL'
            ];

            foreach ($requiredColumns as $column => $definition) {
                $columnStmt = $this->db->prepare("SHOW COLUMNS FROM exit_resignations LIKE ?");
                $columnStmt->execute([$column]);
                if (!$columnStmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->db->exec("ALTER TABLE exit_resignations ADD COLUMN $column $definition");
                }
            }
        } catch (Exception $e) {
            // Ignore schema migration errors and preserve existing table behavior
        }
    }

    private function ensurePayrollClearancesTable(): void
    {
        $stmt = $this->db->query("SHOW TABLES LIKE 'payroll_clearances'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("CREATE TABLE IF NOT EXISTS payroll_clearances (
                id int(11) NOT NULL AUTO_INCREMENT,
                settlement_id int(11) NOT NULL,
                requested_by int(11) DEFAULT NULL,
                requested_at datetime NOT NULL DEFAULT current_timestamp(),
                status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                approved_by int(11) DEFAULT NULL,
                approved_at datetime DEFAULT NULL,
                comments text DEFAULT NULL,
                last_updated datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_settlement_id (settlement_id),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payroll clearance requests linked to exit settlements'");

            try {
                $this->db->exec("ALTER TABLE payroll_clearances ADD CONSTRAINT fk_clearance_settlement FOREIGN KEY (settlement_id) REFERENCES exit_employee_settlements (id) ON DELETE CASCADE");
            } catch (Exception $e) {
                // Foreign key may already exist or exit_employee_settlements may not be available yet
            }
        } else {
            $columnStmt = $this->db->prepare("SHOW COLUMNS FROM payroll_clearances LIKE 'id'");
            $columnStmt->execute();
            $column = $columnStmt->fetch(PDO::FETCH_ASSOC);

            if ($column && stripos($column['Extra'] ?? '', 'auto_increment') === false) {
                $zeroRows = $this->db->query("SELECT id FROM payroll_clearances WHERE id = 0")->fetchAll(PDO::FETCH_ASSOC);
                if ($zeroRows) {
                    $maxId = (int)$this->db->query("SELECT MAX(id) as max_id FROM payroll_clearances")->fetchColumn();
                    $nextId = $maxId + 1;
                    foreach ($zeroRows as $row) {
                        $this->db->exec("UPDATE payroll_clearances SET id = $nextId WHERE id = 0 LIMIT 1");
                        $nextId++;
                    }
                }

                $this->db->exec("ALTER TABLE payroll_clearances MODIFY id int(11) NOT NULL AUTO_INCREMENT");
            }
        }
    }

    /**
     * Submit a resignation request
     */
    public function submitResignation(array $data): int
    {
        try {
            // Build insert columns conditionally to support schemas without resignation_type
            $columns = ['employee_id'];
            $placeholders = ['?'];
            $values = [$data['employee_id']];

            if ($this->columnExists('exit_resignations', 'resignation_type')) {
                $columns[] = 'resignation_type';
                $placeholders[] = '?';
                $values[] = $data['resignation_type'];
            }

            $columns = array_merge($columns, ['reason', 'notice_date', 'last_working_date', 'comments', 'resignation_letter_path', 'submitted_by', 'status', 'created_at']);
            $placeholders = array_merge($placeholders, ['?', '?', '?', '?', '?', '?', "'pending_review'", 'NOW()']);

            // Values for non-dynamic columns
            $values[] = $data['reason'];
            $values[] = $data['notice_date'];
            $values[] = $data['last_working_date'];
            $values[] = $data['comments'] ?? null;
            $values[] = $data['resignation_letter_path'] ?? null;
            $values[] = $data['submitted_by'] ?? null;

            $sql = sprintf(
                "INSERT INTO exit_resignations (%s) VALUES (%s)",
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->db->prepare($sql);

            $this->db->beginTransaction();

            $result = $stmt->execute($values);

            if (!$result) {
                throw new Exception('Failed to insert resignation');
            }

            $resignationId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $resignationId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Create payroll clearance request linked to a settlement
     */
    public function createPayrollClearanceRequest(int $settlementId, ?int $requestedBy = null, ?string $comments = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payroll_clearances (settlement_id, requested_by, requested_at, status, comments, last_updated)
             VALUES (?, ?, NOW(), 'pending', ?, NOW())"
        );

        $stmt->execute([$settlementId, $requestedBy, $comments]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get resignation by ID
     */
    public function getResignationById(int $resignationId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, e.employee_id AS emp_id,
                                     e.email, e.department,
                                     CONCAT(p.first_name, ' ', p.last_name) AS preclearance_desk_person_name
            FROM exit_resignations r
            LEFT JOIN em_employees e ON r.employee_id = e.employee_id
            LEFT JOIN hrms_employee p ON r.preclearance_desk_person = p.employee_id
            WHERE r.id = ?
        ");
        $stmt->execute([$resignationId]);
        $resignation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$resignation) {
            return null;
        }

        $resignation['resignation_request_id'] = null;
        $resignation['resignation_letter_path'] = $resignation['resignation_letter_path'] ?? null;

        // The portal request tables are optional to the Exit Management
        // record. Do not let an attachment lookup prevent the review modal
        // from loading the resignation itself.
        try {
            $attachmentStmt = $this->db->prepare("
                SELECT req.id, req.attachment
                FROM requests req
                INNER JOIN request_types rt ON rt.id = req.request_type_id
                INNER JOIN em_employees e ON e.user_id = req.user_id
                WHERE e.employee_id = ?
                  AND LOWER(rt.name) LIKE '%resignation%'
                  AND req.attachment IS NOT NULL
                  AND req.attachment <> ''
                ORDER BY req.created_at DESC
                LIMIT 1
            ");
            $attachmentStmt->execute([$resignation['employee_id']]);
            $attachment = $attachmentStmt->fetch(PDO::FETCH_ASSOC);
            if ($attachment) {
                $resignation['resignation_request_id'] = $attachment['id'];
                if (empty($resignation['resignation_letter_path'])) {
                    $resignation['resignation_letter_path'] = $attachment['attachment'];
                    // Backfill the dedicated exit record column for legacy
                    // portal requests so future reads do not depend on the
                    // generic requests table lookup.
                    $updateAttachment = $this->db->prepare(
                        'UPDATE exit_resignations SET resignation_letter_path = ? WHERE id = ?'
                    );
                    $updateAttachment->execute([$attachment['attachment'], $resignationId]);
                }
            }
        } catch (Exception $e) {
            error_log('Optional resignation letter lookup skipped: ' . $e->getMessage());
        }

        return $resignation;
    }

    /**
     * Get all resignations (active by default, archived optional)
     */
    /**
     * Get resignations with pagination support
     */
    public function getResignations(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        if ($status === null || $status === '') {
            $status = 'active';
        }

        $offset = ($page - 1) * $limit;
        $hasArchivedFromStatus = $this->columnExists('exit_resignations', 'archived_from_status');

        if ($status === 'archived') {
            if (!$this->tableExists('exit_archive')) {
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => 0
                ];
            }
            // Query from exit_archive for archived records
            $sql = "
                SELECT
                    a.id as archive_id,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.id')) as id,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) as employee_id,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.resignation_type')) as resignation_type,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.reason')) as reason,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.notice_date')) as notice_date,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.last_working_date')) as last_working_date,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.comments')) as comments,
                    'archived' as status,
                    NULL as archived_from_status,
                    JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.created_at')) as created_at,
                    a.archived_at as updated_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                    e.email,
                    e.department,
                    e.position,
                    CONCAT(p.first_name, ' ', p.last_name) AS preclearance_desk_person_name,
                    a.archived_at,
                    a.archive_reason
                FROM exit_archive a
                LEFT JOIN em_employees e ON JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) = e.employee_id
                LEFT JOIN hrms_employee p ON JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.preclearance_desk_person')) = p.employee_id
                WHERE a.archive_type = 'resignation' AND a.restored = 0
            ";

            $countSql = "
                SELECT COUNT(*) as total
                FROM exit_archive a
                WHERE a.archive_type = 'resignation' AND a.restored = 0
            ";

            $params = [];
            $whereClause = "";

            // Add search condition if provided
            if (!empty($search)) {
                $searchCondition = " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.reason')) LIKE :search2 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.resignation_type')) LIKE :search3)";
                $sql .= $searchCondition;
                $countSql .= $searchCondition;
                $searchParam = "%$search%";
                $params['search0'] = $searchParam;
                $params['search1'] = $searchParam;
                $params['search2'] = $searchParam;
                $params['search3'] = $searchParam;
            }

            $sql .= ' ORDER BY a.archived_at DESC LIMIT :limit OFFSET :offset';
        } else {
            // Query from exit_resignations for active records
            $resignationTypeSelect = $this->columnExists('exit_resignations', 'resignation_type') ? 'r.resignation_type' : "NULL AS resignation_type";
            $archivedFromStatusSelect = $hasArchivedFromStatus ? 'r.archived_from_status' : 'NULL AS archived_from_status';

            $sql = "
                SELECT
                    r.id,
                    r.employee_id,
                    " . $resignationTypeSelect . ",
                    r.reason,
                    r.notice_date,
                    r.last_working_date,
                    r.comments,
                    r.status,
                    " . $archivedFromStatusSelect . ",
                    r.created_at,
                    r.updated_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                    e.email,
                    e.department,
                    e.position,
                    CONCAT(p.first_name, ' ', p.last_name) AS preclearance_desk_person_name
                FROM exit_resignations r
                LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                LEFT JOIN hrms_employee p ON r.preclearance_desk_person = p.employee_id
            ";

            $countSql = "
                SELECT COUNT(*) as total
                FROM exit_resignations r
                LEFT JOIN em_employees e ON r.employee_id = e.employee_id
            ";

            $params = [];
            $whereClause = "";

            if ($status === 'all') {
                $whereClause = "";
            } elseif ($status === 'pending') {
                $whereClause = " WHERE r.status IN ('pending_review', 'pending_legal_review')";
            } elseif ($status) {
                $whereClause = " WHERE r.status = :status";
                $params['status'] = $status;
            } else {
                $whereClause = " WHERE r.status != 'archived'";
            }

            // Add search condition if provided
            if (!empty($search)) {
                $searchParam = "%$search%";
                // Include resignation_type in search only if column exists
                if ($this->columnExists('exit_resignations', 'resignation_type')) {
                    $searchCondition = " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR r.reason LIKE :search2 OR r.resignation_type LIKE :search3)";
                    $whereClause .= $searchCondition;
                    $params['search0'] = $searchParam;
                    $params['search1'] = $searchParam;
                    $params['search2'] = $searchParam;
                    $params['search3'] = $searchParam;
                } else {
                    $searchCondition = " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR r.reason LIKE :search2)";
                    $whereClause .= $searchCondition;
                    $params['search0'] = $searchParam;
                    $params['search1'] = $searchParam;
                    $params['search2'] = $searchParam;
                }
            }

            $sql .= $whereClause . ' ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset';
            $countSql .= $whereClause;
        }

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
     * Update resignation
     */
    public function updateResignation(int $resignationId, array $data): bool
    {
        // Build update query conditionally if resignation_type exists
        $setParts = ['employee_id = ?'];
        $values = [$data['employee_id']];

        if ($this->columnExists('exit_resignations', 'resignation_type')) {
            $setParts[] = 'resignation_type = ?';
            $values[] = $data['resignation_type'];
        }

        $setParts[] = 'reason = ?';
        $values[] = $data['reason'];

        $setParts[] = 'notice_date = ?';
        $values[] = $data['notice_date'];

        $setParts[] = 'last_working_date = ?';
        $values[] = $data['last_working_date'];

        $setParts[] = 'comments = ?';
        $values[] = $data['comments'] ?? null;

        $setParts[] = 'updated_at = NOW()';

        $sql = "UPDATE exit_resignations SET " . implode(', ', $setParts) . " WHERE id = ?";
        $values[] = $resignationId;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Update resignation status
     */
    public function updateResignationStatus(int $resignationId, string $status, ?int $approverId = null, ?string $comments = null): bool
    {
        $allowedStatuses = [
            'pending_review',
            'pending_legal_review',
            'approved',
            'rejected',
            'rejected_by_legal',
            'withdrawn'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid resignation status: ' . $status);
        }

        if ($status === 'pending_review' || $status === 'withdrawn') {
            $stmt = $this->db->prepare("
                UPDATE exit_resignations
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $resignationId]);
        }

        if ($status === 'pending_legal_review' || $status === 'rejected') {
            $stmt = $this->db->prepare("
                UPDATE exit_resignations
                SET status = ?, hr_approved_by = ?, hr_approved_at = NOW(), hr_approval_comments = ?, reviewed_by = ?, reviewed_at = NOW(), review_remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $approverId, $comments, $resignationId]);
        }

        if ($status === 'approved') {
            $stmt = $this->db->prepare("
                UPDATE exit_resignations
                SET status = ?, legal_approved_by = ?, legal_approved_at = NOW(), legal_approval_comments = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $approverId, $resignationId]);
        }

        if ($status === 'rejected_by_legal') {
            $stmt = $this->db->prepare("
                UPDATE exit_resignations
                SET status = ?, legal_approved_by = ?, legal_approved_at = NOW(), legal_approval_comments = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $resignationId]);
        }

        return false;
    }

    /**
     * Get resignations by employee ID
     */
    public function getResignationsByEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM exit_resignations
            WHERE employee_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all resignations
     */
    public function getAllResignations(): array
    {
        return $this->getResignations();
    }

    /**
     * Check if employee is eligible for resignation
     */
    public function checkEmployeeEligibility(string $employeeId): array
    {
        // Check if employee exists in em_employees table
        $stmt = $this->db->prepare("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS full_name, employment_status FROM em_employees WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            return [
                'eligible' => false,
                'reason' => 'Employee not found in the system.'
            ];
        }

        // Check employment status
        if (strtolower($employee['employment_status']) !== 'active') {
            return [
                'eligible' => false,
                'reason' => 'Employee is not currently active.'
            ];
        }

        // Check for existing pending resignation
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_resignations WHERE employee_id = ? AND status IN ('pending_review','pending_legal_review')");
        $stmt->execute([$employeeId]);
        $pendingCount = (int)$stmt->fetchColumn();

        if ($pendingCount > 0) {
            return [
                'eligible' => false,
                'reason' => 'Employee already has an active resignation request in review.'
            ];
        }

        // Check for unresolved settlements
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_employee_settlements WHERE employee_id = ? AND status IN ('pending_approval', 'approved')");
        $stmt->execute([$employeeId]);
        $settlementCount = (int)$stmt->fetchColumn();

        if ($settlementCount > 0) {
            return [
                'eligible' => false,
                'reason' => 'Employee has unresolved settlement processes.'
            ];
        }

        // Check for scheduled exit interviews
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_interviews WHERE employee_id = ? AND status IN ('scheduled', 'completed')");
        $stmt->execute([$employeeId]);
        $interviewCount = (int)$stmt->fetchColumn();

        if ($interviewCount > 0) {
            return [
                'eligible' => false,
                'reason' => 'Employee has ongoing or completed exit interview processes.'
            ];
        }

        // Check for active knowledge transfer plans
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_knowledge_transfer_plans WHERE employee_id = ? AND status = 'active'");
        $stmt->execute([$employeeId]);
        $transferCount = (int)$stmt->fetchColumn();

        if ($transferCount > 0) {
            return [
                'eligible' => false,
                'reason' => 'Employee has active knowledge transfer processes.'
            ];
        }

        return [
            'eligible' => true,
            'reason' => 'Employee is eligible for resignation.'
        ];
    }

    /**
     * Archive resignation
     */
    public function archiveResignation(int $resignationId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full resignation data
        $stmt = $this->db->prepare("SELECT * FROM exit_resignations WHERE id = ?");
        $stmt->execute([$resignationId]);
        $resignation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resignation) {
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

            $title = "Resignation - Employee " . ($resignation['employee_id'] ?? 'Unknown');
            $description = "Archived resignation record";
            $content = json_encode($resignation); // Store full data as JSON
            $archivedBy = $_SESSION['employee_id'] ?? 1; // Default to 1 if not set

            $result = $archiveStmt->execute([
                'resignation',
                $resignationId,
                $resignation['employee_id'],
                $title,
                $description,
                $content,
                $resignation['status'],
                $resignation['submitted_by'] ?? null,
                $archivedBy,
                $archiveReason,
                $content
            ]);

            if (!$result) {
                throw new Exception("Failed to insert into exit_archive");
            }

            // Delete from exit_resignations
            $deleteStmt = $this->db->prepare("DELETE FROM exit_resignations WHERE id = ?");
            $deleteResult = $deleteStmt->execute([$resignationId]);

            if (!$deleteResult) {
                throw new Exception("Failed to delete from exit_resignations");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Archive error: " . $e->getMessage());
            error_log("Archive data keys: " . implode(', ', array_keys($resignation)));
            return false;
        }
    }

    /**
     * Unarchive resignation
     */
    public function unarchiveResignation(int $resignationId): bool
    {
        // Get archived data
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'resignation' AND original_id = ?");
        $stmt->execute([$resignationId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data
            $resignationData = json_decode($archive['archive_data'], true);
            if (!$resignationData) {
                return false;
            }

            // Insert back into exit_resignations
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_resignations (
                    id, employee_id, resignation_type, reason, notice_date, last_working_date,
                    comments, resignation_letter_path, submitted_by, preclearance_desk_person, status, approved_by,
                    approved_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $resignationData['id'],
                $resignationData['employee_id'],
                $resignationData['resignation_type'],
                $resignationData['reason'],
                $resignationData['notice_date'],
                $resignationData['last_working_date'],
                $resignationData['comments'],
                $resignationData['resignation_letter_path'] ?? null,
                $resignationData['submitted_by'],
                $resignationData['preclearance_desk_person'],
                $resignationData['status'] ?? 'pending',
                $resignationData['approved_by'],
                $resignationData['approved_at'],
                $resignationData['created_at'],
                date('Y-m-d H:i:s') // updated_at now
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
            error_log("Unarchive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last attendance date for an employee from ta_attendance table
     * This fetches the most recent attendance_date from time_attendance module
     */
    public function getEmployeeLastAttendanceDate(string $employeeId): ?string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT MAX(attendance_date) as last_attendance_date
                FROM ta_attendance
                WHERE employee_id = ? AND attendance_date IS NOT NULL
            ");
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['last_attendance_date'] ?? null;
        } catch (Exception $e) {
            error_log('Error getting last attendance date: ' . $e->getMessage());
            return null;
        }
    }
}