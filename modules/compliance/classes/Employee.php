<?php

include_once __DIR__ . '/../../../database/db.php';

class Employee
{
    private ?PDO $conn = null;
    private $employeeid;
    private $firstname;
    private $lastname;
    private $middlename;
    private $department;
    private $position;
    private $status;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Get all em_employees
     */
    public function getEmployees()
    {
        $sql = "SELECT 
                    e.employee_id,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    d.department_name,
                    p.position_name,
                    e.employment_status
                FROM em_employees AS e
                LEFT JOIN em_departments AS d
                    ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p
                    ON e.position_id = p.position_id
                ORDER BY e.employee_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get logged-in employee ID
     */
    public function getEmployeeId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['employee_id'] ?? null;
    }

    /**
     * Get logged-in employee name
     */
    public function getEmployeeName()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['employee_name'])) {
            return $_SESSION['employee_name'];
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT first_name, last_name
                    FROM em_employees
                    WHERE employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return $employee['first_name'] . ' ' . $employee['last_name'];
            }
        }

        return 'Unknown User';
    }

    /**
     * Get logged-in employee position
     */
    public function getEmployeePosition()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['position_name'])) {
            return $_SESSION['position_name'];
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT p.position_name
                    FROM em_employees AS e
                    LEFT JOIN em_positions AS p
                        ON e.position_id = p.position_id
                    WHERE e.employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee && !empty($employee['position_name'])) {
                return $employee['position_name'];
            }
        }

        return 'Unknown Position';
    }
}

