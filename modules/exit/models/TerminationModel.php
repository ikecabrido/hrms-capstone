<?php

require_once 'ExitManagementModel.php';

class TerminationModel extends ExitManagementModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureExitTerminationsAutoIncrement();
        $this->ensureTerminationSchema();
    }

    private function ensureExitTerminationsAutoIncrement(): void
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM exit_terminations LIKE 'id'");
            $stmt->execute();
            $column = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($column && stripos($column['Extra'] ?? '', 'auto_increment') === false) {
                $zeroRows = $this->db->query("SELECT id FROM exit_terminations WHERE id = 0")->fetchAll(PDO::FETCH_ASSOC);
                if ($zeroRows) {
                    $maxId = (int)$this->db->query("SELECT MAX(id) as max_id FROM exit_terminations")->fetchColumn();
                    $nextId = max(1, $maxId + 1);
                    foreach ($zeroRows as $row) {
                        $this->db->exec("UPDATE exit_terminations SET id = $nextId WHERE id = 0 LIMIT 1");
                        $nextId++;
                    }
                }
                $this->db->exec("ALTER TABLE exit_terminations MODIFY id int(11) NOT NULL AUTO_INCREMENT");
            }
        } catch (Exception $e) {
            // Table may not exist yet
        }
    }

    public function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    private function ensureTerminationSchema(): void
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'exit_terminations'");
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->db->exec("CREATE TABLE IF NOT EXISTS exit_terminations (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    employee_id varchar(50) NOT NULL,
                    termination_reason text NOT NULL,
                    effective_date date NOT NULL,
                    comments text,
                    submitted_by int(11) DEFAULT NULL,
                    status enum('pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn') NOT NULL DEFAULT 'pending_review',
                    reviewed_by int(11) DEFAULT NULL,
                    reviewed_at datetime DEFAULT NULL,
                    review_remarks text DEFAULT NULL,
                    legal_approved_by int(11) DEFAULT NULL,
                    legal_approved_at datetime DEFAULT NULL,
                    legal_approval_comments text DEFAULT NULL,
                    approved_by int(11) DEFAULT NULL,
                    approved_at datetime DEFAULT NULL,
                    created_at timestamp NOT NULL DEFAULT current_timestamp(),
                    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (id),
                    KEY fk_termination_employee (employee_id),
                    KEY fk_termination_submitted_by (submitted_by),
                    KEY fk_termination_approved_by (approved_by)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                return;
            }

            $desiredStatusEnum = "'pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn'";
            $statusStmt = $this->db->prepare("SHOW COLUMNS FROM exit_terminations LIKE 'status'");
            $statusStmt->execute();
            $statusColumn = $statusStmt->fetch(PDO::FETCH_ASSOC);

            if ($statusColumn && stripos($statusColumn['Type'], 'pending_review') === false) {
                $this->db->exec("ALTER TABLE exit_terminations MODIFY status ENUM($desiredStatusEnum) NOT NULL DEFAULT 'pending_review'");
            }

            $requiredColumns = [
                'reviewed_by' => 'int(11) DEFAULT NULL',
                'reviewed_at' => 'datetime DEFAULT NULL',
                'review_remarks' => 'text DEFAULT NULL',
                'legal_approved_by' => 'int(11) DEFAULT NULL',
                'legal_approved_at' => 'datetime DEFAULT NULL',
                'legal_approval_comments' => 'text DEFAULT NULL',
                'approved_by' => 'int(11) DEFAULT NULL',
                'approved_at' => 'datetime DEFAULT NULL'
            ];

            foreach ($requiredColumns as $column => $definition) {
                $columnStmt = $this->db->prepare("SHOW COLUMNS FROM exit_terminations LIKE ?");
                $columnStmt->execute([$column]);
                if (!$columnStmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->db->exec("ALTER TABLE exit_terminations ADD COLUMN $column $definition");
                }
            }
        } catch (Exception $e) {
            // Ignore schema migration errors
        }
    }

    public function submitTermination(array $data): int
    {
        $stmt = $this->db->prepare(" 
            INSERT INTO exit_terminations (employee_id, termination_reason, effective_date, comments, submitted_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending_review', NOW())
        ");

        $this->db->beginTransaction();

        $result = $stmt->execute([
            $data['employee_id'],
            $data['termination_reason'],
            $data['effective_date'],
            $data['comments'] ?? null,
            $data['submitted_by'] ?? null
        ]);

        if (!$result) {
            $this->db->rollBack();
            throw new Exception('Failed to insert termination');
        }

        $terminationId = (int)$this->db->lastInsertId();
        $this->db->commit();

        // After creating the termination record, also generate and save a termination letter
        // as an HTML document and create a document record linked to this termination case.
        try {
            $this->saveTerminationLetter($terminationId, $data);
        } catch (Exception $e) {
            // Do not fail the termination submission if document save fails; log for debugging.
            error_log('Failed to auto-save termination letter: ' . $e->getMessage());
        }

        return $terminationId;
    }

    /**
     * Generate termination letter HTML, save to uploads, and create a document record
     */
    protected function saveTerminationLetter(int $terminationId, array $data): void
    {
        // Build a simple HTML letter. Keep it consistent with settlement header used elsewhere.
        $employeeId = $data['employee_id'] ?? '';
        $employeeName = '';
        try {
            $stmt = $this->db->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM em_employees WHERE employee_id = ? LIMIT 1");
            $stmt->execute([$employeeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $employeeName = $row['full_name'];
        } catch (Exception $e) {
            // ignore
        }

        $effectiveDate = $data['effective_date'] ?? '';
        $reason = $data['termination_reason'] ?? '';
        $comments = $data['comments'] ?? '';

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Termination Letter</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:24px;color:#172b4d;} .school-header{display:flex;align-items:center;border-bottom:2px solid #1f5fbf;padding-bottom:14px;margin-bottom:20px;} .school-header img{width:86px;height:86px;object-fit:contain;margin-right:18px;} .school-name{font-size:20px;font-weight:700;color:#174a8b;} .school-details{font-size:12px;line-height:1.6;color:#333;margin-top:4px;} .content{font-size:14px;line-height:1.7;color:#1f2937;}</style>' .
            '</head><body>' .
            '<div class="school-header"><img src="/capstone_hr_management_system2/assets/pics/bcpLogo.png" alt="BCP logo"><div><div class="school-name">Bestlink College of the Philippines - Bulacan Campus</div><div class="school-details">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan.<br>Tel. No.: (044)792-1992</div></div></div>' .
            '<h2>Termination Letter</h2>' .
            '<div class="content">' .
            '<p>This letter serves as formal notice that <strong>' . htmlspecialchars($employeeName, ENT_QUOTES) . '</strong> (Employee ID: ' . htmlspecialchars($employeeId, ENT_QUOTES) . ') is being terminated effective <strong>' . htmlspecialchars($effectiveDate, ENT_QUOTES) . '</strong>.</p>' .
            '<p><strong>Reason for termination:</strong> ' . nl2br(htmlspecialchars($reason, ENT_QUOTES)) . '</p>' .
            ($comments ? '<p><strong>Additional notes:</strong> ' . nl2br(htmlspecialchars($comments, ENT_QUOTES)) . '</p>' : '') .
            '<p>Issued by HR Management</p>' .
            '</div></body></html>';

        // Ensure uploads directory exists
        $uploadDir = __DIR__ . '/../uploads/documents/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // Prefer to generate a PDF using Dompdf if available
        $pdfFileName = 'termination_' . time() . '_' . $terminationId . '.pdf';
        $filePathRelative = 'uploads/documents/' . $pdfFileName;
        $fullPath = __DIR__ . '/../' . $filePathRelative;

        $pdfGenerated = false;
        // Try to load Dompdf from payroll vendor (existing installation)
        $dompdfAutoload = __DIR__ . '/../payroll/vendor/autoload.php';
        if (file_exists($dompdfAutoload)) {
            try {
                require_once $dompdfAutoload;
                if (class_exists('\Dompdf\Dompdf')) {
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->render();
                    $pdfOutput = $dompdf->output();
                    file_put_contents($fullPath, $pdfOutput);
                    $pdfGenerated = true;
                }
            } catch (Exception $e) {
                error_log('Dompdf generation failed: ' . $e->getMessage());
                $pdfGenerated = false;
            }
        }

        // Fallback: save HTML if PDF couldn't be created
        if (!$pdfGenerated) {
            $fileName = 'termination_' . time() . '_' . $terminationId . '.html';
            $filePathRelative = 'uploads/documents/' . $fileName;
            $fullPath = __DIR__ . '/../' . $filePathRelative;
            file_put_contents($fullPath, $html);
        }

        // Create document record linking to this termination
        require_once __DIR__ . '/DocumentationModel.php';
        $docModel = new DocumentationModel();

        $docData = [
            'employee_id' => $employeeId,
            'exit_case_type' => 'termination',
            'exit_case_id' => $terminationId,
            'document_type' => 'other',
            'title' => 'Termination Letter',
            'file_path' => $filePathRelative,
            'uploaded_by' => $_SESSION['employee_id'] ?? null
        ];

        $docModel->createDocument($docData);
    }

    public function getTerminationById(int $terminationId): ?array
    {
        if (!$this->tableExists('exit_terminations')) {
            return null;
        }

        $stmt = $this->db->prepare(" 
            SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, e.email, COALESCE(d.department_name, '') AS department, COALESCE(p.position_name, '') AS position
            FROM exit_terminations t
            LEFT JOIN em_employees e ON t.employee_id = e.employee_id
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE t.id = ?
        ");
        $stmt->execute([$terminationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTerminations(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        try {
            if (!$this->tableExists('exit_terminations')) {
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => 0
                ];
            }

            $offset = max(0, ($page - 1) * $limit);

            $baseSelect = "
                SELECT
                    t.id,
                    t.employee_id,
                    t.termination_reason,
                    t.effective_date,
                    t.comments,
                    t.status,
                    t.created_at,
                    t.updated_at,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                        e.email,
                        COALESCE(d.department_name, '') AS department,
                        COALESCE(p.position_name, '') AS position
                    FROM exit_terminations t
                    LEFT JOIN em_employees e ON t.employee_id = e.employee_id
                    LEFT JOIN em_departments d ON e.department_id = d.department_id
                    LEFT JOIN em_positions p ON e.position_id = p.position_id
            ";

            $baseCount = "
                SELECT COUNT(*) as total
                FROM exit_terminations t
                LEFT JOIN em_employees e ON t.employee_id = e.employee_id
            ";

            $params = [];
            $whereClause = "";

            if ($status === 'all') {
                // no filter
            } elseif ($status === 'active') {
                $whereClause = " WHERE t.status != 'archived'";
            } elseif ($status) {
                $whereClause = " WHERE t.status = :status";
                $params['status'] = $status;
            }

            if (!empty($search)) {
                $searchCondition = $whereClause ? " AND" : " WHERE";
                $searchCondition .= " (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR t.termination_reason LIKE :search2)";
                $whereClause .= $searchCondition;
                $params['search0'] = "%$search%";
                $params['search1'] = "%$search%";
                $params['search2'] = "%$search%";
            }

            // Use integer limits directly in SQL to avoid DB driver binding issues for LIMIT/OFFSET
            $limitInt = (int)$limit;
            $offsetInt = (int)$offset;

            $sql = $baseSelect . $whereClause . " ORDER BY t.created_at DESC LIMIT $limitInt OFFSET $offsetInt";
            $countSql = $baseCount . $whereClause;

            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $totalCount = $countRow ? (int)$countRow['total'] : 0;

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limitInt,
                'total_pages' => $limitInt > 0 ? ceil($totalCount / $limitInt) : 0
            ];
        } catch (Exception $e) {
            error_log('getTerminations error: ' . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getArchivedTerminations(int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $sql = "
            SELECT
                a.id AS archive_id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.id')) AS id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) AS employee_id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.termination_reason')) AS reason,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.effective_date')) AS effective_date,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.comments')) AS comments,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) AS status,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.created_at')) AS created_at,
                a.archived_at AS updated_at,
                CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                e.email,
                COALESCE(d.department_name, '') AS department,
                COALESCE(p.position_name, '') AS position,
                a.archive_reason
            FROM exit_archive a
            LEFT JOIN em_employees e ON JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) = e.employee_id
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE a.archive_type = 'termination' AND a.restored = 0
        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_archive a
            WHERE a.archive_type = 'termination' AND a.restored = 0
        ";

        $params = [];
        if (!empty($search)) {
            $sql .= " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.termination_reason')) LIKE :search2)";
            $countSql .= " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :search0 OR e.email LIKE :search1 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.termination_reason')) LIKE :search2)";
            $params['search0'] = "%$search%";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }

        $sql .= " ORDER BY a.archived_at DESC LIMIT :limit OFFSET :offset";

        try {
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(':' . $key, $value);
            }
            $countStmt->execute();
            $totalCount = (int)($countStmt->fetchColumn() ?? 0);

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $limit > 0 ? (int)ceil($totalCount / $limit) : 0
            ];
        } catch (Exception $e) {
            error_log('getArchivedTerminations error: ' . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateTerminationStatus(int $terminationId, string $status, ?int $approverId = null, ?string $comments = null): bool
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
            throw new Exception('Invalid termination status: ' . $status);
        }

        if ($status === 'pending_review' || $status === 'withdrawn') {
            $stmt = $this->db->prepare(" 
                UPDATE exit_terminations
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $terminationId]);
        }

        if ($status === 'pending_legal_review' || $status === 'rejected') {
            $stmt = $this->db->prepare(" 
                UPDATE exit_terminations
                SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $terminationId]);
        }

        if ($status === 'approved') {
            $stmt = $this->db->prepare(" 
                UPDATE exit_terminations
                SET status = ?, legal_approved_by = ?, legal_approved_at = NOW(), legal_approval_comments = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $approverId, $terminationId]);
        }

        if ($status === 'rejected_by_legal') {
            $stmt = $this->db->prepare(" 
                UPDATE exit_terminations
                SET status = ?, legal_approved_by = ?, legal_approved_at = NOW(), legal_approval_comments = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $approverId, $comments, $terminationId]);
        }

        return false;
    }

    public function checkEmployeeEligibility(string $employeeId): array
    {
        if (empty($employeeId)) {
            return ['eligible' => false, 'reason' => 'Employee ID is required.'];
        }

        $stmt = $this->db->prepare("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS full_name, employment_status FROM em_employees WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            return ['eligible' => false, 'reason' => 'Employee not found in the system.'];
        }

        if (strtolower($employee['employment_status']) !== 'active') {
            return ['eligible' => false, 'reason' => 'Employee is not currently active.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_resignations WHERE employee_id = ? AND status IN ('pending_review','pending_legal_review','approved')");
        $stmt->execute([$employeeId]);
        $resignationCount = (int)$stmt->fetchColumn();

        if ($resignationCount > 0) {
            return ['eligible' => false, 'reason' => 'Employee already has an active resignation process.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_terminations WHERE employee_id = ? AND status IN ('pending_review','pending_legal_review','approved')");
        $stmt->execute([$employeeId]);
        $terminationCount = (int)$stmt->fetchColumn();

        if ($terminationCount > 0) {
            return ['eligible' => false, 'reason' => 'Employee already has an active termination process.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_employee_settlements WHERE employee_id = ? AND status IN ('pending_approval', 'approved')");
        $stmt->execute([$employeeId]);
        $settlementCount = (int)$stmt->fetchColumn();

        if ($settlementCount > 0) {
            return ['eligible' => false, 'reason' => 'Employee has unresolved settlement processes.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_interviews WHERE employee_id = ? AND status IN ('scheduled', 'completed')");
        $stmt->execute([$employeeId]);
        $interviewCount = (int)$stmt->fetchColumn();

        if ($interviewCount > 0) {
            return ['eligible' => false, 'reason' => 'Employee has ongoing or completed exit interview processes.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exit_knowledge_transfer_plans WHERE employee_id = ? AND status = 'active'");
        $stmt->execute([$employeeId]);
        $transferCount = (int)$stmt->fetchColumn();

        if ($transferCount > 0) {
            return ['eligible' => false, 'reason' => 'Employee has active knowledge transfer processes.'];
        }

        return ['eligible' => true, 'reason' => 'Employee is eligible for termination initiation.'];
    }

    public function archiveTermination(int $terminationId, string $archiveReason = 'Manual archive'): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM exit_terminations WHERE id = ?");
        $stmt->execute([$terminationId]);
        $termination = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$termination) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $archiveStmt = $this->db->prepare(" 
                INSERT INTO exit_archive (
                    archive_type, original_id, employee_id, title, description, content,
                    status, original_created_by, archived_by, archive_reason, archive_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $title = "Termination - Employee " . ($termination['employee_id'] ?? 'Unknown');
            $description = "Archived termination record";
            $content = json_encode($termination);
            $archivedBy = $_SESSION['employee_id'] ?? 1;

            $result = $archiveStmt->execute([
                'termination',
                $terminationId,
                $termination['employee_id'],
                $title,
                $description,
                $content,
                $termination['status'],
                $termination['submitted_by'] ?? null,
                $archivedBy,
                $archiveReason,
                $content
            ]);

            if (!$result) {
                throw new Exception('Failed to insert into exit_archive');
            }

            $deleteStmt = $this->db->prepare("DELETE FROM exit_terminations WHERE id = ?");
            $deleteResult = $deleteStmt->execute([$terminationId]);

            if (!$deleteResult) {
                throw new Exception('Failed to delete from exit_terminations');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Termination archive error: ' . $e->getMessage());
            return false;
        }
    }

    public function unarchiveTermination(int $terminationId): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'termination' AND original_id = ?");
        $stmt->execute([$terminationId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        $terminationData = json_decode($archive['archive_data'], true);
        if (!$terminationData) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $insertStmt = $this->db->prepare(" 
                INSERT INTO exit_terminations (
                    id, employee_id, termination_reason, effective_date, comments,
                    submitted_by, status, approved_by, approved_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $terminationData['id'],
                $terminationData['employee_id'],
                $terminationData['termination_reason'],
                $terminationData['effective_date'],
                $terminationData['comments'],
                $terminationData['submitted_by'],
                $terminationData['status'] ?? 'pending_review',
                $terminationData['approved_by'],
                $terminationData['approved_at'],
                $terminationData['created_at'],
                date('Y-m-d H:i:s')
            ]);

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
            error_log('Termination unarchive error: ' . $e->getMessage());
            return false;
        }
    }
}
