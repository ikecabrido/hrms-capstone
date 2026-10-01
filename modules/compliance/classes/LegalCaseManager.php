<?php

require_once __DIR__ . '/../../../database/db.php';

class LegalCaseManager
{
    private $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getNextCaseNumber(int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $prefix = 'LC-' . $year . '-';
        $stmt = $this->conn->prepare("
            SELECT case_number FROM lc_legal_cases
            WHERE case_number LIKE :prefix
            ORDER BY case_id DESC LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) ($parts[2] ?? 0) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function getNextExternalCaseNumber(int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $prefix = 'EXT-' . $year . '-';
        $stmt = $this->conn->prepare("
            SELECT case_number FROM lc_legal_cases
            WHERE case_number LIKE :prefix
            ORDER BY case_id DESC LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) ($parts[2] ?? 0) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createCase(array $data): array
    {
        $required = ['case_type', 'case_source'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $caseTitle = $data['case_title'] ?? '';
        if (!$caseTitle) {
            $parts = array_filter([$data['external_agency'] ?? '', $data['case_type'] ?? '']);
            $caseTitle = !empty($parts) ? implode(' — ', $parts) : 'External Case';
        }
        $data['case_title'] = $caseTitle;

        $caseNumber = $data['case_number'] ?? null;
        if (!$caseNumber && !empty($data['external_agency'])) {
            $caseNumber = $this->getNextExternalCaseNumber();
        }
        if (!$caseNumber) {
            $caseNumber = $this->getNextCaseNumber();
        }

        $stmt = $this->conn->prepare("
            INSERT INTO lc_legal_cases
                (case_number, case_title, case_type, specific_case_type, case_source, complaint_id, employee_id,
                 external_agency, external_reference_no, docket_no, description,
                 date_received, date_filed, priority, current_status, current_stage,
                 assigned_to, created_by)
            VALUES
                (:case_number, :case_title, :case_type, :specific_case_type, :case_source, :complaint_id, :employee_id,
                 :external_agency, :external_reference_no, :docket_no, :description,
                 :date_received, :date_filed, :priority, :current_status, :current_stage,
                 :assigned_to, :created_by)
        ");

        $stmt->execute([
            ':case_number'       => $caseNumber,
            ':case_title'        => $caseTitle,
            ':case_type'         => $data['case_type'],
            ':specific_case_type' => $data['specific_case_type'] ?? null,
            ':case_source'       => $data['case_source'],
            ':complaint_id'      => $data['complaint_id'] ?? null,
            ':employee_id'       => $data['employee_id'] ?? null,
            ':external_agency'   => $data['external_agency'] ?? null,
            ':external_reference_no' => $data['external_reference_no'] ?? null,
            ':docket_no'         => $data['docket_no'] ?? null,
            ':description'       => $data['description'] ?? null,
            ':date_received'     => $data['date_received'] ?? null,
            ':date_filed'        => $data['date_filed'] ?? null,
            ':priority'          => $data['priority'] ?? 'Medium',
            ':current_status'    => $data['current_status'] ?? 'Draft',
            ':current_stage'     => $data['current_stage'] ?? 'Case Intake',
            ':assigned_to'       => $data['assigned_to'] ?? null,
            ':created_by'        => $data['created_by'] ?? ($_SESSION['employee_id'] ?? null),
        ]);

        $caseId = (int) $this->conn->lastInsertId();
        return $this->getCase($caseId);
    }

    public function getCase(int $caseId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM lc_legal_cases WHERE case_id = :id LIMIT 1");
        $stmt->execute([':id' => $caseId]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);
        return $case ?: null;
    }

    public function getCaseByNumber(string $caseNumber): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM lc_legal_cases WHERE case_number = :cn LIMIT 1");
        $stmt->execute([':cn' => $caseNumber]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);
        return $case ?: null;
    }

    public function updateCase(int $caseId, array $data): ?array
    {
        $allowed = [
            'case_title', 'case_type', 'case_source', 'complaint_id', 'employee_id',
            'external_agency', 'external_reference_no', 'docket_no', 'description',
            'date_received', 'date_filed', 'priority', 'current_status', 'current_stage',
            'assigned_to', 'resolution', 'date_resolved', 'date_closed', 'due_date',
            'specific_case_type'
        ];

        $sets = [];
        $params = [':id' => $caseId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field] !== null ? $data[$field] : null;
                if (is_string($data[$field]) && $data[$field] === '') {
                    $params[":{$field}"] = null;
                }
            }
        }

        if (empty($sets)) {
            return $this->getCase($caseId);
        }

        $sql = 'UPDATE lc_legal_cases SET ' . implode(', ', $sets) . ' WHERE case_id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $this->getCase($caseId);
    }

    public function listCases(array $filters = [], int $page = 1, int $pageSize = 10): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(lc.case_number LIKE :search OR lc.case_title LIKE :search OR e.first_name LIKE :search OR e.last_name LIKE :search OR lc.external_agency LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['case_type'])) {
            $where[] = 'lc.case_type = :case_type';
            $params[':case_type'] = $filters['case_type'];
        }
        if (!empty($filters['case_source'])) {
            $where[] = 'lc.case_source = :case_source';
            $params[':case_source'] = $filters['case_source'];
        }
        if (!empty($filters['current_status'])) {
            $where[] = 'lc.current_status = :current_status';
            $params[':current_status'] = $filters['current_status'];
        }
        if (!empty($filters['current_stage'])) {
            $where[] = 'lc.current_stage = :current_stage';
            $params[':current_stage'] = $filters['current_stage'];
        }
        if (!empty($filters['external_agency'])) {
            if ($filters['external_agency'] === '__has_value__') {
                $where[] = 'lc.external_agency IS NOT NULL AND lc.external_agency <> ""';
            } else {
                $where[] = 'lc.external_agency = :external_agency';
                $params[':external_agency'] = $filters['external_agency'];
            }
        }
        if (!empty($filters['priority'])) {
            $where[] = 'lc.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'lc.assigned_to = :assigned_to';
            $params[':assigned_to'] = (int) $filters['assigned_to'];
        }
        if (!empty($filters['employee_id'])) {
            $where[] = 'lc.employee_id = :employee_id';
            $params[':employee_id'] = (int) $filters['employee_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'lc.date_received >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'lc.date_received <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['overdue'])) {
            $where[] = 'lc.due_date IS NOT NULL AND lc.due_date < CURDATE() AND lc.current_status NOT IN (\'Resolved\',\'Closed\',\'Cancelled\')';
        }

        $sql = 'SELECT lc.*, e.first_name, e.last_name, e.employee_code,
                       CONCAT(ee.first_name, " ", ee.last_name) AS assigned_name
                 FROM lc_legal_cases lc
                 LEFT JOIN em_employees e ON lc.employee_id = e.employee_id
                 LEFT JOIN em_employees ee ON lc.assigned_to = ee.employee_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY lc.created_at DESC';

        $countSql = 'SELECT COUNT(*) FROM lc_legal_cases lc
                     LEFT JOIN em_employees e ON lc.employee_id = e.employee_id
                     WHERE ' . implode(' AND ', $where);

        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;

        $stmt = $this->conn->prepare($sql . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => $total > 0 ? (int) ceil($total / $pageSize) : 0,
        ];
    }

    public function countCases(array $filters = []): int
    {
        $result = $this->listCases($filters, 1, 1);
        return $result['total'];
    }

    public function addWorkflow(int $caseId, array $data): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_legal_case_workflow
                (case_id, stage, action, status, assigned_to, due_date, remarks, created_by)
            VALUES
                (:case_id, :stage, :action, :status, :assigned_to, :due_date, :remarks, :created_by)
        ");

        $stmt->execute([
            ':case_id'     => $caseId,
            ':stage'       => $data['stage'] ?? 'Case Intake',
            ':action'      => $data['action'] ?? null,
            ':status'      => $data['status'] ?? null,
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':due_date'    => $data['due_date'] ?? null,
            ':remarks'     => $data['remarks'] ?? null,
            ':created_by'  => $data['created_by'] ?? ($_SESSION['employee_id'] ?? null),
        ]);

        return $this->getWorkflow($caseId);
    }

    public function getWorkflow(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT w.*, CONCAT(e.first_name, ' ', e.last_name) AS assigned_name
            FROM lc_legal_case_workflow w
            LEFT JOIN em_employees e ON w.assigned_to = e.employee_id
            WHERE w.case_id = :case_id
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addConference(int $caseId, array $data): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_legal_case_conferences
                (case_id, conference_type, conference_date, location_or_mode,
                 participants, agenda, minutes, outcome, next_action, next_date, created_by)
            VALUES
                (:case_id, :conference_type, :conference_date, :location_or_mode,
                 :participants, :agenda, :minutes, :outcome, :next_action, :next_date, :created_by)
        ");

        $stmt->execute([
            ':case_id'          => $caseId,
            ':conference_type'  => $data['conference_type'] ?? null,
            ':conference_date'  => $data['conference_date'] ?? null,
            ':location_or_mode' => $data['location_or_mode'] ?? null,
            ':participants'     => $data['participants'] ?? null,
            ':agenda'           => $data['agenda'] ?? null,
            ':minutes'          => $data['minutes'] ?? null,
            ':outcome'          => $data['outcome'] ?? null,
            ':next_action'      => $data['next_action'] ?? null,
            ':next_date'        => $data['next_date'] ?? null,
            ':created_by'       => $data['created_by'] ?? ($_SESSION['employee_id'] ?? null),
        ]);

        return $this->getConferences($caseId);
    }

    public function getConferences(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, CONCAT(e.first_name, ' ', e.last_name) AS created_by_name
            FROM lc_legal_case_conferences c
            LEFT JOIN em_employees e ON c.created_by = e.employee_id
            WHERE c.case_id = :case_id
            ORDER BY c.conference_date DESC, c.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addDocument(int $caseId, array $file, array $data): array
    {
        $uploadDir = __DIR__ . '/../../assets/uploads/legal_cases/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException('File type not allowed.');
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new InvalidArgumentException('File exceeds 10MB limit.');
        }

        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName);
        $unique = time() . '_' . bin2hex(random_bytes(3)) . ($ext ? '.' . $ext : '');
        $savePath = 'assets/uploads/legal_cases/' . $unique;
        $fullSavePath = $uploadDir . $unique;

        if (!move_uploaded_file($file['tmp_name'], $fullSavePath)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO lc_legal_case_documents
                    (case_id, document_type, document_name, file_path, document_date, description, uploaded_by)
                VALUES
                    (:case_id, :document_type, :document_name, :file_path, :document_date, :description, :uploaded_by)
            ");
            $stmt->execute([
                ':case_id'      => $caseId,
                ':document_type' => $data['document_type'] ?? null,
                ':document_name' => $file['name'],
                ':file_path'     => $savePath,
                ':document_date' => $data['document_date'] ?? null,
                ':description'   => $data['description'] ?? null,
                ':uploaded_by'   => $data['uploaded_by'] ?? ($_SESSION['employee_id'] ?? null),
            ]);
        } catch (Throwable $e) {
            @unlink($fullSavePath);
            throw $e;
        }

        return $this->getDocuments($caseId);
    }

    public function getDocuments(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT d.*, CONCAT(e.first_name, ' ', e.last_name) AS uploaded_by_name
            FROM lc_legal_case_documents d
            LEFT JOIN em_employees e ON d.uploaded_by = e.employee_id
            WHERE d.case_id = :case_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDocument(int $documentId, int $caseId): bool
    {
        $stmt = $this->conn->prepare("SELECT file_path FROM lc_legal_case_documents WHERE document_id = :id AND case_id = :case_id LIMIT 1");
        $stmt->execute([':id' => $documentId, ':case_id' => $caseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['file_path'])) {
            $fullPath = realpath(__DIR__ . '/../../' . $row['file_path']);
            $uploadDir = realpath(__DIR__ . '/../../assets/uploads/legal_cases/');
            if ($fullPath && $uploadDir && strpos($fullPath, $uploadDir) === 0 && file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $del = $this->conn->prepare("DELETE FROM lc_legal_case_documents WHERE document_id = :id AND case_id = :case_id");
        $del->execute([':id' => $documentId, ':case_id' => $caseId]);
        return $del->rowCount() > 0;
    }

    public function addExternalUpdate(int $caseId, array $data): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_legal_case_external_updates
                (case_id, update_type, reference_no, date_received, date_sent,
                 sender, recipient, summary, attachment, created_by)
            VALUES
                (:case_id, :update_type, :reference_no, :date_received, :date_sent,
                 :sender, :recipient, :summary, :attachment, :created_by)
        ");

        $stmt->execute([
            ':case_id'       => $caseId,
            ':update_type'   => $data['update_type'] ?? null,
            ':reference_no'  => $data['reference_no'] ?? null,
            ':date_received' => $data['date_received'] ?? null,
            ':date_sent'     => $data['date_sent'] ?? null,
            ':sender'        => $data['sender'] ?? null,
            ':recipient'     => $data['recipient'] ?? null,
            ':summary'       => $data['summary'] ?? null,
            ':attachment'    => $data['attachment'] ?? null,
            ':created_by'    => $data['created_by'] ?? ($_SESSION['employee_id'] ?? null),
        ]);

        return $this->getExternalUpdates($caseId);
    }

    public function getExternalUpdates(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT u.*, CONCAT(e.first_name, ' ', e.last_name) AS created_by_name
            FROM lc_legal_case_external_updates u
            LEFT JOIN em_employees e ON u.created_by = e.employee_id
            WHERE u.case_id = :case_id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function closeCase(int $caseId, string $resolution, int $closedBy = null, ?string $dateResolved = null): ?array
    {
        $dateResolved = $dateResolved ?: date('Y-m-d');
        $stmt = $this->conn->prepare("
            UPDATE lc_legal_cases
            SET current_status = 'Closed', current_stage = 'Case Closure',
                resolution = :resolution, date_resolved = :date_resolved, date_closed = :date_closed, updated_at = NOW()
            WHERE case_id = :id
        ");
        $stmt->execute([
            ':id' => $caseId,
            ':resolution' => $resolution,
            ':date_resolved' => $dateResolved,
            ':date_closed' => $dateResolved,
        ]);

        return $this->getCase($caseId);
    }

    public function searchEmployees(string $query, int $limit = 20): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $params = [];
        $conds = [];
        $conds[] = 'e.first_name LIKE :q OR e.last_name LIKE :q OR e.employee_code LIKE :q OR e.email LIKE :q OR p.position_name LIKE :q';
        $params[':q'] = '%' . $q . '%';

        $sql = 'SELECT e.employee_id, e.first_name, e.last_name, e.employee_code AS employee_no,
                       e.email, d.department_name, p.position_name
                FROM em_employees e
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
                WHERE (' . implode(' OR ', $conds) . ')
                  AND e.email IS NOT NULL AND e.email <> \'\'
                ORDER BY e.first_name ASC, e.last_name ASC
                LIMIT ' . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            return [
                'employee_id' => (int) ($r['employee_id'] ?? 0),
                'full_name'   => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'employee_no' => (string) ($r['employee_no'] ?? ''),
                'email'       => (string) ($r['email'] ?? ''),
                'department'  => (string) ($r['department_name'] ?? ''),
                'position'    => (string) ($r['position_name'] ?? ''),
            ];
        }, $rows);
    }

    public function getEmployeeName(int $employeeId): string
    {
        if ($employeeId <= 0) {
            return '';
        }
        $stmt = $this->conn->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM em_employees WHERE employee_id = :id LIMIT 1");
        $stmt->execute([':id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['full_name'] : '';
    }

    public function getLegalCaseStats(): array
    {
        $stats = [
            'open'       => 0,
            'external'   => 0,
            'action'     => 0,
            'overdue'    => 0,
            'monitoring' => 0,
            'conferences'=> 0,
        ];

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_cases
                WHERE current_status NOT IN ('Closed','Cancelled','Resolved')
            ");
            $stmt->execute();
            $stats['open'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_cases
                WHERE external_agency IS NOT NULL AND external_agency <> ''
                  AND current_status NOT IN ('Closed','Cancelled','Resolved')
            ");
            $stmt->execute();
            $stats['external'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_cases
                WHERE current_status IN ('Open','Under Assessment','Under Investigation','Awaiting Documents','Awaiting External Action','Conference Scheduled','In Conference','Compliance Action Required')
            ");
            $stmt->execute();
            $stats['action'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_cases
                WHERE due_date IS NOT NULL AND due_date < CURDATE()
                  AND current_status NOT IN ('Closed','Cancelled','Resolved')
            ");
            $stmt->execute();
            $stats['overdue'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_cases
                WHERE current_status = 'Monitoring'
            ");
            $stmt->execute();
            $stats['monitoring'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM lc_legal_case_conferences
                WHERE conference_date >= CURDATE()
                  AND conference_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $stats['conferences'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {}

        return $stats;
    }

    public function createNotification(int $employeeId, string $title, string $message, string $type = 'info', string $module = 'compliance', ?string $email = null): void
    {
        if ($employeeId <= 0) {
            return;
        }

        $userId = (int) ($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 0);
        $recipientId = $employeeId;

        $stmt = $this->conn->prepare("
            INSERT INTO lc_notifications
                (employee_id, user_id, recipient_id, title, message, type, notification_type, status, module, email, sender_email, is_read, created_at, updated_at)
            VALUES
                (:employee_id, :user_id, :recipient_id, :title, :message, :type, :notification_type, :status, :module, :email, :sender_email, 0, NOW(), NOW())
        ");
        $stmt->execute([
            ':employee_id'      => $employeeId,
            ':user_id'          => $userId > 0 ? $userId : null,
            ':recipient_id'     => $recipientId,
            ':title'            => $title,
            ':message'          => $message,
            ':type'             => $type,
            ':notification_type'=> $type,
            ':status'           => 'unread',
            ':module'           => $module,
            ':email'            => $email,
            ':sender_email'     => null,
        ]);
    }

    public function getDashboardStats(array $filters = []): array
    {
        $where = ["current_status NOT IN ('Closed','Cancelled','Resolved')"];
        $params = [];

        if (!empty($filters['external_agency'])) {
            $where[] = 'external_agency = :external_agency';
            $params[':external_agency'] = $filters['external_agency'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = :assigned_to';
            $params[':assigned_to'] = (int) $filters['assigned_to'];
        }

        $whereSql = implode(' AND ', $where);

        $stats = [
            'open'          => 0,
            'investigating' => 0,
            'monitoring'    => 0,
            'overdue'       => 0,
            'closed'        => 0,
            'total_active'  => 0,
        ];

        try {
            $sql = "
                SELECT
                    SUM(current_status = 'Open') AS open,
                    SUM(current_status = 'Under Investigation') AS investigating,
                    SUM(current_status = 'Monitoring') AS monitoring,
                    SUM(due_date IS NOT NULL AND due_date < CURDATE() AND current_status NOT IN ('Resolved','Closed','Cancelled')) AS overdue,
                    SUM(current_status = 'Closed') AS closed,
                    COUNT(*) AS total_active
                FROM lc_legal_cases
                WHERE {$whereSql}
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $stats['open']         = (int) ($row['open'] ?? 0);
                $stats['investigating'] = (int) ($row['investigating'] ?? 0);
                $stats['monitoring']    = (int) ($row['monitoring'] ?? 0);
                $stats['overdue']       = (int) ($row['overdue'] ?? 0);
                $stats['closed']        = (int) ($row['closed'] ?? 0);
                $stats['total_active']  = (int) ($row['total_active'] ?? 0);
            }
        } catch (Throwable $e) {
            error_log('LegalCaseManager::getDashboardStats error: ' . $e->getMessage());
        }

        return $stats;
    }

    public function addCaseHistory(int $caseId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_case_history
                (case_id, previous_status, new_status, previous_stage, new_stage, action, performed_by, remarks)
            VALUES
                (:case_id, :previous_status, :new_status, :previous_stage, :new_stage, :action, :performed_by, :remarks)
        ");

        return (bool) $stmt->execute([
            ':case_id'         => $caseId,
            ':previous_status' => $data['previous_status'] ?? null,
            ':new_status'      => $data['new_status'] ?? null,
            ':previous_stage'  => $data['previous_stage'] ?? null,
            ':new_stage'       => $data['new_stage'] ?? null,
            ':action'          => $data['action'] ?? 'update',
            ':performed_by'    => $data['performed_by'] ?? ($_SESSION['employee_id'] ?? null),
            ':remarks'         => $data['remarks'] ?? null,
        ]);
    }

    public function getCaseHistory(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT h.*, CONCAT(e.first_name, ' ', e.last_name) AS performed_by_name
            FROM lc_case_history h
            LEFT JOIN em_employees e ON h.performed_by = e.employee_id
            WHERE h.case_id = :case_id
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExternalCaseEvents(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT e.*, CONCAT(em.first_name, ' ', em.last_name) AS created_by_name
            FROM lc_external_case_events e
            LEFT JOIN em_employees em ON e.created_by = em.employee_id
            WHERE e.case_id = :case_id
            ORDER BY e.event_date ASC, e.start_time ASC, e.created_at ASC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addExternalCaseEvent(int $caseId, array $data): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_external_case_events
                (case_id, event_type, title, description, event_date, start_time, end_time, location, status, created_by)
            VALUES
                (:case_id, :event_type, :title, :description, :event_date, :start_time, :end_time, :location, :status, :created_by)
        ");
        $stmt->execute([
            ':case_id'     => $caseId,
            ':event_type'  => $data['event_type'] ?? 'Other',
            ':title'       => $data['title'] ?? '',
            ':description' => $data['description'] ?? null,
            ':event_date'  => $data['event_date'] ?? null,
            ':start_time'  => $data['start_time'] ?? null,
            ':end_time'    => $data['end_time'] ?? null,
            ':location'    => $data['location'] ?? null,
            ':status'      => $data['status'] ?? 'Scheduled',
            ':created_by'  => $data['created_by'] ?? ($_SESSION['employee_id'] ?? null),
        ]);

        $newEventId = (int) $this->conn->lastInsertId();
        $newEventType = strtolower((string) ($data['event_type'] ?? ''));
        $milestoneTypes = [
            'case created',
            'external notice received',
            'document submission',
            'response submitted',
            'decision/resolution received',
            'follow-up',
        ];

        if (in_array($newEventType, $milestoneTypes, true)) {
            $update = $this->conn->prepare("
                UPDATE lc_external_case_events
                SET status = 'Completed'
                WHERE case_id = :case_id
                  AND event_id < :event_id
                  AND status NOT IN ('Completed', 'Cancelled')
            ");
            $update->execute([
                ':case_id' => $caseId,
                ':event_id' => $newEventId,
            ]);
        }

        return $this->getExternalCaseEvents($caseId);
    }

    public function updateExternalCaseEvent(int $eventId, int $caseId, array $data): array
    {
        $allowed = ['event_type', 'title', 'description', 'event_date', 'start_time', 'end_time', 'location', 'status'];
        $sets = [];
        $params = [':id' => $eventId, ':case_id' => $caseId];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field] !== null ? $data[$field] : null;
            }
        }
        if (empty($sets)) {
            return $this->getExternalCaseEvents($caseId);
        }
        $sql = 'UPDATE lc_external_case_events SET ' . implode(', ', $sets) . ' WHERE event_id = :id AND case_id = :case_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $this->getExternalCaseEvents($caseId);
    }

    public function deleteExternalCaseEvent(int $eventId, int $caseId): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM lc_external_case_events WHERE event_id = :id AND case_id = :case_id');
        $stmt->execute([':id' => $eventId, ':case_id' => $caseId]);
        return $stmt->rowCount() > 0;
    }

    public function getExternalCaseNotes(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT n.*, CONCAT(e.first_name, ' ', e.last_name) AS created_by_name
            FROM lc_external_case_notes n
            LEFT JOIN em_employees e ON n.created_by = e.employee_id
            WHERE n.case_id = :case_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addExternalCaseNote(int $caseId, string $note, ?int $createdBy = null): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_external_case_notes (case_id, note, created_by)
            VALUES (:case_id, :note, :created_by)
        ");
        $stmt->execute([
            ':case_id'    => $caseId,
            ':note'       => $note,
            ':created_by' => $createdBy ?? ($_SESSION['employee_id'] ?? null),
        ]);
        return $this->getExternalCaseNotes($caseId);
    }

    public function deleteExternalCaseNote(int $noteId, int $caseId): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM lc_external_case_notes WHERE note_id = :id AND case_id = :case_id');
        $stmt->execute([':id' => $noteId, ':case_id' => $caseId]);
        return $stmt->rowCount() > 0;
    }

    public function getExternalCaseReferences(int $caseId): array
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, lr.title, lr.short_title, lr.reference_number, lr.issuing_authority, lr.category_id,
                   lc.name AS category_name,
                   CONCAT(e.first_name, ' ', e.last_name) AS created_by_name
            FROM lc_external_case_references r
            LEFT JOIN lc_labor_law_references lr ON lr.id = r.reference_id
            LEFT JOIN lc_labor_law_categories lc ON lc.id = lr.category_id
            LEFT JOIN em_employees e ON r.created_by = e.employee_id
            WHERE r.case_id = :case_id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':case_id' => $caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function attachExternalCaseReference(int $caseId, int $referenceId, string $relationType = 'Attached', ?string $notes = null, ?int $createdBy = null): array
    {
        $stmt = $this->conn->prepare("
            INSERT INTO lc_external_case_references (case_id, reference_id, relation_type, notes, created_by)
            VALUES (:case_id, :reference_id, :relation_type, :notes, :created_by)
            ON DUPLICATE KEY UPDATE relation_type = VALUES(relation_type), notes = VALUES(notes)
        ");
        $stmt->execute([
            ':case_id'      => $caseId,
            ':reference_id' => $referenceId,
            ':relation_type' => $relationType,
            ':notes'        => $notes,
            ':created_by'   => $createdBy ?? ($_SESSION['employee_id'] ?? null),
        ]);
        return $this->getExternalCaseReferences($caseId);
    }

    public function detachExternalCaseReference(int $caseId, int $referenceId): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM lc_external_case_references WHERE case_id = :case_id AND reference_id = :reference_id');
        $stmt->execute([':case_id' => $caseId, ':reference_id' => $referenceId]);
        return $stmt->rowCount() > 0;
    }

    public function getExternalCaseStats(): array
    {
        $stats = [
            'total'    => 0,
            'open'     => 0,
            'scheduled'=> 0,
            'hearing'  => 0,
            'awaiting' => 0,
            'closed'   => 0,
        ];
        try {
            $stmt = $this->conn->prepare("
                SELECT current_status, COUNT(*) AS cnt
                FROM lc_legal_cases
                WHERE external_agency IS NOT NULL AND external_agency <> ''
                GROUP BY current_status
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $s = strtolower((string) ($r['current_status'] ?? ''));
                $cnt = (int) ($r['cnt'] ?? 0);
                $stats['total'] += $cnt;
                if (strpos($s, 'closed') !== false || strpos($s, 'resolved') !== false || strpos($s, 'withdrawn') !== false || strpos($s, 'archived') !== false) {
                    $stats['closed'] += $cnt;
                } elseif (strpos($s, 'scheduled') !== false) {
                    $stats['scheduled'] += $cnt;
                } elseif (strpos($s, 'hearing') !== false || strpos($s, 'conference') !== false) {
                    $stats['hearing'] += $cnt;
                } elseif (strpos($s, 'awaiting') !== false) {
                    $stats['awaiting'] += $cnt;
                } else {
                    $stats['open'] += $cnt;
                }
            }
        } catch (Throwable $e) {}
        return $stats;
    }

    public function searchLaborLawReferences(string $query, int $limit = 20, array $relatedContext = []): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $words = array_values(array_filter(array_map('trim', explode(' ', $q)), function ($word) {
            return $word !== '' && preg_match('/[a-zA-Z0-9]/', $word);
        }));

        $sql = "
            SELECT
                r.id,
                r.reference_number,
                r.title,
                r.short_title,
                r.category_id,
                c.name AS category_name,
                r.issuing_authority,
                r.keywords,
                r.description
            FROM lc_labor_law_references r
            LEFT JOIN lc_labor_law_categories c ON c.id = r.category_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($words)) {
            $sql .= ' AND (';
            $first = true;

            foreach ($words as $i => $word) {
                if (!$first) {
                    $sql .= ' OR ';
                }

                $placeholders = [
                    ':q' . $i . '_title',
                    ':q' . $i . '_short_title',
                    ':q' . $i . '_reference_number',
                    ':q' . $i . '_keywords',
                    ':q' . $i . '_authority',
                    ':q' . $i . '_description',
                ];

                $sql .= '(' .
                    'r.title LIKE ' . $placeholders[0] .
                    ' OR r.short_title LIKE ' . $placeholders[1] .
                    ' OR r.reference_number LIKE ' . $placeholders[2] .
                    ' OR r.keywords LIKE ' . $placeholders[3] .
                    ' OR r.issuing_authority LIKE ' . $placeholders[4] .
                    ' OR r.description LIKE ' . $placeholders[5] .
                ')';

                foreach ($placeholders as $placeholder) {
                    $params[$placeholder] = '%' . $word . '%';
                }

                $first = false;
            }

            $sql .= ')';
        }

        $limit = max(1, min(100, (int) $limit));
        $sql .= ' ORDER BY r.title ASC LIMIT ' . $limit;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCases(): int
    {
        try {
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getClosedCases(): int
    {
        try {
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE current_status IN ('Closed','Resolved')")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getHighPriorityCases(): int
    {
        try {
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE priority = 'High'")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getStatusBreakdown(): array
    {
        try {
            $stmt = $this->conn->query("SELECT current_status, COUNT(*) as cnt FROM lc_legal_cases GROUP BY current_status ORDER BY cnt DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getAgencyBreakdown(): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT external_agency, COUNT(*) as cnt
                FROM lc_legal_cases
                WHERE external_agency IS NOT NULL AND external_agency <> ''
                GROUP BY external_agency
                ORDER BY cnt DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getRecentCases(int $limit = 5): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT case_id, case_number, case_title, external_agency, priority, current_status, date_received, created_at
                FROM lc_legal_cases
                ORDER BY created_at DESC
                LIMIT :limit
            ");
                        $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getCaseChatResponse(string $question): array
    {
        $lower = strtolower($question);
        $total = $this->getTotalCases();

        if (preg_match('/how many|count|total|number of cases/', $lower)) {
            if (preg_match('/closed|resolved/', $lower)) {
                $count = $this->getClosedCases();
                return [
                    'message' => $count === 1
                        ? 'There is currently 1 closed or resolved legal case.'
                        : "There are currently {$count} closed or resolved legal cases.",
                ];
            }
            if (preg_match('/open|active|pending/', $lower)) {
                $count = (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE current_status NOT IN ('Closed','Resolved','Cancelled')")->fetchColumn();
                return [
                    'message' => $count === 1
                        ? 'There is currently 1 open legal case.'
                        : "There are currently {$count} open legal cases.",
                ];
            }
            if (preg_match('/high priority|priority.*high|critical/', $lower)) {
                $count = $this->getHighPriorityCases();
                return [
                    'message' => $count === 1
                        ? 'There is currently 1 high-priority legal case.'
                        : "There are currently {$count} high-priority legal cases.",
                ];
            }
            if (preg_match('/external|agency|dole|sss|philhealth|pag-ibig|bir/', $lower)) {
                $count = (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE external_agency IS NOT NULL AND external_agency <> ''")->fetchColumn();
                return [
                    'message' => $count === 1
                        ? 'There is currently 1 external/agency-related legal case.'
                        : "There are currently {$count} external/agency-related legal cases.",
                ];
            }
            return [
                'message' => $total === 1
                    ? 'There is currently 1 legal case recorded in the system.'
                    : "There are currently {$total} legal cases recorded in the system.",
            ];
        }

        if (preg_match('/open|any open|show open/', $lower)) {
            $stmt = $this->conn->prepare("
                SELECT case_number, case_title, current_status, priority, external_agency
                FROM lc_legal_cases
                WHERE current_status NOT IN ('Closed','Resolved','Cancelled')
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                return ['message' => 'There are no open legal cases at this time.'];
            }
            $lines = [];
            foreach ($rows as $r) {
                $lines[] = "- {$r['case_number']}: {$r['case_title']} ({$r['current_status']}, " . ($r['external_agency'] ?: 'Internal') . ")";
            }
            return ['message' => "Open legal cases:\n" . implode("\n", $lines)];
        }

        if (preg_match('/closed|resolved/', $lower)) {
            $stmt = $this->conn->prepare("
                SELECT case_number, case_title, current_status, date_resolved, date_closed
                FROM lc_legal_cases
                WHERE current_status IN ('Closed','Resolved')
                ORDER BY date_resolved DESC, date_closed DESC, created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                return ['message' => 'There are no closed or resolved legal cases.'];
            }
            $lines = [];
            foreach ($rows as $r) {
                $date = $r['date_resolved'] ?: $r['date_closed'];
                $lines[] = "- {$r['case_number']}: {$r['case_title']}" . ($date ? " (closed " . date('M j, Y', strtotime($date)) . ")" : "");
            }
            return ['message' => "Closed/resolved legal cases:\n" . implode("\n", $lines)];
        }

        if (preg_match('/high priority|priority.*high|critical/', $lower)) {
            $stmt = $this->conn->prepare("
                SELECT case_number, case_title, current_status, external_agency, priority
                FROM lc_legal_cases
                WHERE priority = 'High'
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                return ['message' => 'There are no high-priority legal cases.'];
            }
            $lines = [];
            foreach ($rows as $r) {
                $lines[] = "- {$r['case_number']}: {$r['case_title']} ({$r['current_status']}, " . ($r['external_agency'] ?: 'Internal') . ")";
            }
            return ['message' => "High-priority legal cases:\n" . implode("\n", $lines)];
        }

        if (preg_match('/dole|sss|philhealth|pag-ibig|pagibig|bir|nlrc|agency/', $lower)) {
            $agency = null;
            if (preg_match('/dole/', $lower)) $agency = 'DOLE';
            elseif (preg_match('/sss/', $lower)) $agency = 'SSS';
            elseif (preg_match('/philhealth/', $lower)) $agency = 'PhilHealth';
            elseif (preg_match('/pag-ibig|pagibig/', $lower)) $agency = 'Pag-IBIG';
            elseif (preg_match('/bir/', $lower)) $agency = 'BIR';
            elseif (preg_match('/nlrc/', $lower)) $agency = 'NLRC';

            if ($agency) {
                $stmt = $this->conn->prepare("
                    SELECT case_number, case_title, current_status, priority, date_received, resolution, date_resolved, date_closed
                    FROM lc_legal_cases
                    WHERE external_agency = :agency
                    ORDER BY created_at DESC
                ");
                $stmt->execute([':agency' => $agency]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($rows)) {
                    return ['message' => "There are currently no {$agency}-related legal cases recorded."];
                }
                $lines = [];
                foreach ($rows as $r) {
                    $lines[] = "- {$r['case_number']}: {$r['case_title']}\n  Status: {$r['current_status']}\n  Priority: {$r['priority']}";
                    if ($r['date_received']) {
                        $lines[] = "  Received: " . date('M j, Y', strtotime($r['date_received']));
                    }
                    if ($r['current_status'] === 'Closed' || $r['current_status'] === 'Resolved') {
                        $lines[] = "  Resolution: " . ($r['resolution'] ?: 'Not specified');
                    }
                }
                return ['message' => "Yes. There is currently " . count($rows) . " {$agency}-related case" . (count($rows) > 1 ? 's' : '') . ":\n\n" . implode("\n", $lines)];
            }
        }

        if (preg_match('/recent|latest|show cases|list cases|what cases|our cases/', $lower)) {
            $stmt = $this->conn->prepare("
                SELECT case_number, case_title, external_agency, priority, current_status, date_received
                FROM lc_legal_cases
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                return ['message' => 'No legal cases have been recorded yet.'];
            }
            $lines = [];
            foreach ($rows as $r) {
                $lines[] = "- {$r['case_number']}: {$r['case_title']} ({$r['current_status']}, " . ($r['external_agency'] ?: 'Internal') . ")";
            }
            return ['message' => "Recent legal cases:\n" . implode("\n", $lines)];
        }

        if (preg_match('/need attention|require action|overdue|action needed|what.*happened|status/', $lower)) {
            $stmt = $this->conn->prepare("
                SELECT case_number, case_title, current_status, priority, external_agency, due_date, date_received, resolution, date_resolved, date_closed
                FROM lc_legal_cases
                WHERE current_status NOT IN ('Closed','Resolved','Cancelled')
                ORDER BY
                    CASE priority WHEN 'Critical' THEN 4 WHEN 'High' THEN 3 WHEN 'Medium' THEN 2 ELSE 1 END DESC,
                    due_date ASC,
                    created_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                return ['message' => 'There are no legal cases requiring attention at this time.'];
            }
            $lines = [];
            foreach ($rows as $r) {
                $lines[] = "- {$r['case_number']}: {$r['case_title']}";
                $lines[] = "  Status: {$r['current_status']} | Priority: {$r['priority']} | Agency: " . ($r['external_agency'] ?: 'Internal');
                if ($r['date_received']) {
                    $lines[] = "  Received: " . date('M j, Y', strtotime($r['date_received']));
                }
                if ($r['due_date']) {
                    $lines[] = "  Due: " . date('M j, Y', strtotime($r['due_date']));
                }
                $lines[] = "";
            }
            return ['message' => "Cases requiring attention:\n" . implode("\n", $lines)];
        }

        if (preg_match('/what happened|tell me about|details about|summary of/', $lower)) {
            if (preg_match('/dole/', $lower)) {
                $stmt = $this->conn->prepare("
                    SELECT case_number, case_title, case_type, case_source, external_agency, external_reference_no,
                           description, date_received, priority, current_status, resolution, date_resolved, date_closed
                    FROM lc_legal_cases
                    WHERE external_agency = 'DOLE'
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$r) {
                    return ['message' => 'There are no DOLE cases recorded.'];
                }
                $msg = "The recorded DOLE case is {$r['case_number']}, a {$r['case_type']}.\n\n";
                if ($r['date_received']) {
                    $msg .= "It was received on " . date('F j, Y', strtotime($r['date_received'])) . " and is currently marked {$r['current_status']}.\n";
                }
                if ($r['current_status'] === 'Closed' || $r['current_status'] === 'Resolved') {
                    $msg .= "The recorded resolution is \"" . ($r['resolution'] ?: 'Not specified') . "\".";
                }
                return ['message' => $msg];
            }
        }

        return [
            'message' => "I can help analyze the legal cases recorded in the system. Try asking about case status, agencies, priority, recent cases, or cases requiring action.",
        ];
    }
}
