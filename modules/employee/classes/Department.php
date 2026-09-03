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
}
