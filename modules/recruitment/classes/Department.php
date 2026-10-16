<?php

class Department
{
    private $conn;
    private $department_id;
    private $department_name;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getAllDepartments()
    {
        $stmt = $this->conn->prepare("SELECT department_id, department_name FROM em_departments");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countDepartmentEmployees($department_id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM em_employees WHERE department_id = :department_id");
        $stmt->execute([':department_id' => $department_id]);
        return $stmt->fetchColumn();
    }

    public function getDepartmentsWithDetails()
    {
        $sql = "SELECT 
                    d.department_id,
                    d.department_name,
                    CONCAT(e.first_name, ' ', e.last_name) AS department_head_name,
                    COUNT(emp.employee_id) AS employee_count
                FROM em_departments d
                LEFT JOIN em_employees e 
                    ON d.department_head = e.employee_id
                LEFT JOIN em_employees emp 
                    ON emp.department_id = d.department_id
                GROUP BY d.department_id, d.department_name, e.first_name, e.last_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addDepartment($department_name)
    {
        $stmt = $this->conn->prepare("INSERT INTO em_departments (department_name) VALUES (:department_name)");
        return $stmt->execute([':department_name' => $department_name]);
    }
}
