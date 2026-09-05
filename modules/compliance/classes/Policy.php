<?php

include_once __DIR__ . '/../../../database/db.php';
include_once __DIR__ . '/Employee.php';

class Policy
{
    private $conn;
    private $employee;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
        $this->employee = new Employee($this->conn);
    }

    public function getCategories()
    {
        $sql = "SELECT MIN(id) AS id, name, MIN(sort_order) AS sort_order, MIN(is_active) AS is_active
                FROM lc_policy_categories
                WHERE is_active = 1
                GROUP BY name
                ORDER BY sort_order, name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPolicies($filters = [], $limit = null, $offset = null)
    {
        $sql = "SELECT p.*, c.name AS category_name,
                       u.first_name AS created_by_name,
                       a.first_name AS approved_by_name
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                 LEFT JOIN em_employees u ON p.created_by = u.employee_id
                 LEFT JOIN em_employees a ON p.approved_by = a.employee_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE :search OR p.policy_code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['ack_status'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM lc_policy_assignments a
                WHERE a.policy_id = p.id AND a.status = :ack_status
            )";
            $params[':ack_status'] = $filters['ack_status'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPoliciesCount($filters = [])
    {
        $sql = "SELECT COUNT(*) AS total
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE :search OR p.policy_code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['ack_status'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM lc_policy_assignments a
                WHERE a.policy_id = p.id AND a.status = :ack_status
            )";
            $params[':ack_status'] = $filters['ack_status'];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getPolicyById($id)
    {
        $sql = "SELECT p.*, c.name AS category_name
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE p.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => (int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPolicyByCode($code, $version = null)
    {
        $sql = "SELECT p.*, c.name AS category_name
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE p.policy_code = :code";
        $params = [':code' => $code];

        if ($version !== null) {
            $sql .= " AND p.version = :version";
            $params[':version'] = $version;
        } else {
            $sql .= " ORDER BY p.version DESC";
        }

        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createPolicy($data)
    {
        $sql = "INSERT INTO lc_policies
                (policy_code, title, category_id, description, content, version,
                 effective_date, acknowledgement_deadline, status, requires_acknowledgement,
                 attachment_path, attachment_name, created_by, approved_by, published_at)
                VALUES
                (:policy_code, :title, :category_id, :description, :content, :version,
                 :effective_date, :acknowledgement_deadline, :status, :requires_acknowledgement,
                 :attachment_path, :attachment_name, :created_by, :approved_by, :published_at)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':policy_code' => $data['policy_code'],
            ':title' => $data['title'],
            ':category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            ':description' => $data['description'] ?? null,
            ':content' => $data['content'] ?? null,
            ':version' => $data['version'] ?? '1.0',
            ':effective_date' => $data['effective_date'] ?? null,
            ':acknowledgement_deadline' => $data['acknowledgement_deadline'] ?? null,
            ':status' => $data['status'] ?? 'Draft',
            ':requires_acknowledgement' => !empty($data['requires_acknowledgement']) ? 1 : 0,
            ':attachment_path' => $data['attachment_path'] ?? null,
            ':attachment_name' => $data['attachment_name'] ?? null,
            ':created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
            ':approved_by' => !empty($data['approved_by']) ? (int) $data['approved_by'] : null,
            ':published_at' => $data['published_at'] ?? null,
        ]);
    }

    public function updatePolicy($id, $data)
    {
        $fields = [];
        $params = [':id' => (int) $id];

        $allowed = [
            'policy_code', 'title', 'category_id', 'description', 'content', 'version',
            'effective_date', 'acknowledgement_deadline', 'status', 'requires_acknowledgement',
            'attachment_path', 'attachment_name', 'approved_by', 'published_at', 'archived_at'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (in_array($field, ['category_id', 'approved_by'], true) && $value !== null) {
                    $value = $value !== '' ? (int) $value : null;
                }
                $fields[] = "`$field` = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE lc_policies SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function assignPolicy($policyId, $employeeIds, $dueDate = null)
    {
        if (empty($employeeIds)) {
            return true;
        }

        $sql = "INSERT IGNORE INTO lc_policy_assignments (policy_id, employee_id, due_date, status)
                VALUES (:policy_id, :employee_id, :due_date, 'Pending')";
        $stmt = $this->conn->prepare($sql);

        foreach ((array) $employeeIds as $employeeId) {
            $stmt->execute([
                ':policy_id' => (int) $policyId,
                ':employee_id' => (int) $employeeId,
                ':due_date' => $dueDate,
            ]);
        }

        return true;
    }

    public function unassignPolicy($policyId, $employeeId = null)
    {
        if ($employeeId !== null) {
            $stmt = $this->conn->prepare("DELETE FROM lc_policy_assignments WHERE policy_id = :policy_id AND employee_id = :employee_id");
            return $stmt->execute([
                ':policy_id' => (int) $policyId,
                ':employee_id' => (int) $employeeId,
            ]);
        }

        $stmt = $this->conn->prepare("DELETE FROM lc_policy_assignments WHERE policy_id = :policy_id");
        return $stmt->execute([':policy_id' => (int) $policyId]);
    }

    public function getAssignments($policyId, $filters = [])
    {
        $sql = "SELECT a.*, e.employee_code AS employee_no, e.first_name, e.middle_name, e.last_name,
                       d.department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM lc_policy_assignments a
                INNER JOIN em_employees e ON a.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
                WHERE a.policy_id = :policy_id";
        $params = [':policy_id' => (int) $policyId];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search OR e.employee_code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY a.assigned_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAcknowledgements($policyId, $filters = [])
    {
        $sql = "SELECT a.*, e.first_name, e.middle_name, e.last_name,
                       d.department_name, COALESCE(p.position_name, 'N/A') AS position_name,
                       ack.date_acknowledged
                FROM lc_policy_assignments a
                INNER JOIN em_employees e ON a.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
                LEFT JOIN lc_policies pol ON pol.id = a.policy_id
                LEFT JOIN lc_policy_acknowledgments ack ON ack.policy_id = a.policy_id
                    AND ack.employee_id = a.employee_id
                WHERE a.policy_id = :policy_id";
        $params = [':policy_id' => (int) $policyId];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY a.assigned_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMyPolicies($employeeId, $filters = [])
    {
        $sql = "SELECT p.*, a.id AS assignment_id, a.assigned_at, a.due_date, a.status AS assignment_status,
                       ack.date_acknowledged
                FROM lc_policy_assignments a
                INNER JOIN lc_policies p ON a.policy_id = p.id
                LEFT JOIN lc_policy_acknowledgments ack ON ack.policy_id = p.id
                    AND ack.employee_id = a.employee_id
                WHERE a.employee_id = :employee_id AND p.status = 'Published'";
        $params = [':employee_id' => (int) $employeeId];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY a.assigned_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acknowledgePolicy($policyId, $employeeId, $policyVersion, $ipAddress = null, $userId = null)
    {
        $sql = "INSERT INTO lc_policy_acknowledgments
                (policy_id, employee_id, date_acknowledged, ip_address)
                VALUES (:policy_id, :employee_id, NOW(), :ip_address)
                ON DUPLICATE KEY UPDATE
                date_acknowledged = NOW(),
                ip_address = VALUES(ip_address),
                updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':policy_id' => (int) $policyId,
            ':employee_id' => (int) $employeeId,
            ':ip_address' => $ipAddress,
        ]);

        if ($result) {
            $update = $this->conn->prepare("
                UPDATE lc_policy_assignments
                SET status = 'Acknowledged'
                WHERE policy_id = :policy_id AND employee_id = :employee_id
            ");
            $update->execute([
                ':policy_id' => (int) $policyId,
                ':employee_id' => (int) $employeeId,
            ]);
        }

        return $result;
    }

    public function getAcknowledgementStats($policyId)
    {
        $sql = "SELECT
                    COUNT(*) AS total_assigned,
                    SUM(CASE WHEN a.status = 'Acknowledged' THEN 1 ELSE 0 END) AS acknowledged,
                    SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN a.status = 'Overdue' THEN 1 ELSE 0 END) AS overdue
                FROM lc_policy_assignments a
                WHERE a.policy_id = :policy_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':policy_id' => (int) $policyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats()
    {
        $stats = [];

        $sql = "SELECT
                    COUNT(*) AS total_policies,
                    SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) AS draft
                FROM lc_policies";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['policies'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql = "SELECT
                    COUNT(*) AS total_assignments,
                    SUM(CASE WHEN status = 'Acknowledged' THEN 1 ELSE 0 END) AS acknowledged,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) AS overdue
                FROM lc_policy_assignments";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['assignments'] = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int) ($stats['assignments']['total_assignments'] ?? 0) > 0) {
            $stats['assignments']['rate'] = round(
                (int) ($stats['assignments']['acknowledged'] ?? 0) / (int) ($stats['assignments']['total_assignments'] ?? 1) * 100,
                1
            );
        } else {
            $stats['assignments']['rate'] = 0;
        }

        return $stats;
    }

    public function sendReminder($policyId, $employeeId, $sentBy = null, $notes = null)
    {
        $sql = "INSERT INTO lc_policy_reminders (policy_id, employee_id, sent_by, reminder_type, notes)
                VALUES (:policy_id, :employee_id, :sent_by, 'system', :notes)";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':policy_id' => (int) $policyId,
            ':employee_id' => (int) $employeeId,
            ':sent_by' => $sentBy !== null ? (int) $sentBy : null,
            ':notes' => $notes,
        ]);

        if ($result) {
            $update = $this->conn->prepare("
                UPDATE lc_policy_assignments
                SET reminder_sent_at = NOW(), reminder_count = reminder_count + 1
                WHERE policy_id = :policy_id AND employee_id = :employee_id
            ");
            $update->execute([
                ':policy_id' => (int) $policyId,
                ':employee_id' => (int) $employeeId,
            ]);
        }

        return $result;
    }

    public function getReminders($policyId, $employeeId = null)
    {
        $sql = "SELECT r.*, p.title AS policy_title, e.first_name, e.last_name
                FROM lc_policy_reminders r
                INNER JOIN lc_policies p ON r.policy_id = p.id
                INNER JOIN em_employees e ON r.employee_id = e.employee_id
                WHERE r.policy_id = :policy_id";
        $params = [':policy_id' => (int) $policyId];

        if ($employeeId !== null) {
            $sql .= " AND r.employee_id = :employee_id";
            $params[':employee_id'] = (int) $employeeId;
        }

        $sql .= " ORDER BY r.sent_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentUserEmployeeId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return (int) ($_SESSION['employee_id'] ?? 0);
    }

    public function getCurrentUserName()
    {
        return $this->employee->getEmployeeName();
    }

      public function getEmployeesForAssignment($search = '')
      {
          $sql = "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name,
                         d.department_name, COALESCE(p.position_name, 'N/A') AS position_name, e.employment_status
                  FROM em_employees e
                  LEFT JOIN em_departments d ON e.department_id = d.department_id
                  LEFT JOIN em_positions p ON e.position_id = p.position_id
                  WHERE e.employment_status NOT IN ('Resigned', 'Terminated')";
         $params = [];

        if ($search !== '') {
            $sql .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search OR e.employee_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY e.last_name, e.first_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments()
    {
        $sql = "SELECT department_id AS id, department_name FROM em_departments WHERE status = 'Active' ORDER BY department_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositions()
    {
        $sql = "SELECT position_id, position_name FROM em_positions ORDER BY position_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


