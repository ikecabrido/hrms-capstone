<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * Department
 *
 * Wraps read operations against `em_departments`.
 * Columns (verified): department_id, department_code, department_name,
 * description, status, created_at, updated_at
 */
class Department
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

    public function getAllDepartments($activeOnly = true)
    {
        $sql = "SELECT department_id, department_code, department_name, description, status
                FROM em_departments";
        if ($activeOnly) {
            $sql .= " WHERE status = 'Active'";
        }
        $sql .= " ORDER BY department_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartmentById($departmentId)
    {
        $stmt = $this->conn->prepare(
            "SELECT department_id, department_code, department_name, description, status
             FROM em_departments WHERE department_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $departmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Count active (non-archived) employees per department, keyed by department_id.
     */
    public function countEmployeesByDepartment()
    {
        $stmt = $this->conn->query(
            "SELECT department_id, COUNT(*) AS cnt
             FROM em_employees
             WHERE is_archived = 0 AND department_id IS NOT NULL
             GROUP BY department_id"
        );
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['department_id']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Insert a new department into em_departments.
     * department_id is NOT assigned manually — it follows the table's own
     * AUTO_INCREMENT/primary-key behavior. New departments always start Active.
     *
     * @return int The new department_id.
     */
    public function createDepartment(array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO em_departments (department_code, department_name, description, status)
             VALUES (:department_code, :department_name, :description, 'Active')"
        );
        $stmt->execute([
            ':department_code' => $data['department_code'] ?? null,
            ':department_name' => $data['department_name'] ?? '',
            ':description'     => ($data['description'] ?? '') !== '' ? $data['description'] : null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Update an existing department's editable fields.
     * Never touches department_id (primary key) or status — status changes
     * go through updateDepartmentStatus() instead.
     *
     * @return bool True if the department existed and the update ran.
     */
    public function updateDepartment($departmentId, array $data)
    {
        if (!$this->getDepartmentById($departmentId)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE em_departments
             SET department_code = :department_code,
                 department_name = :department_name,
                 description = :description
             WHERE department_id = :id"
        );
        $stmt->execute([
            ':department_code' => $data['department_code'] ?? null,
            ':department_name' => $data['department_name'] ?? '',
            ':description'     => ($data['description'] ?? '') !== '' ? $data['description'] : null,
            ':id'              => $departmentId,
        ]);

        return true;
    }

    /**
     * Activate or deactivate a department. Only ever touches the `status`
     * column — the department record itself, and any employees already
     * assigned to it, are left completely untouched.
     *
     * @return bool True if the department existed and the update ran.
     */
    public function updateDepartmentStatus($departmentId, $status)
    {
        if (!in_array($status, ['Active', 'Inactive'], true)) {
            return false;
        }
        if (!$this->getDepartmentById($departmentId)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE em_departments SET status = :status WHERE department_id = :id"
        );
        $stmt->execute([':status' => $status, ':id' => $departmentId]);

        return true;
    }
}
