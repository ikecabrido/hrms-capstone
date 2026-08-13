<?php

include_once __DIR__ . '/../app/core/TimeDatabase.php';

class TimeTemplateEmployee
{
    private $conn;
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
            $database = TimeDatabase::getInstance();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Get all employees
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
                    e.status
                FROM hrms_employee AS e
                LEFT JOIN hrms_department AS d
                    ON e.department = d.department_id
                LEFT JOIN hrms_position AS p
                    ON e.position = p.position_id
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

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT first_name, last_name
                    FROM hrms_employee
                    WHERE employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return htmlspecialchars(
                    $employee['first_name'] . ' ' . $employee['last_name']
                );
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

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT p.position_name
                    FROM hrms_employee AS e
                    LEFT JOIN hrms_position AS p
                        ON e.position = p.position_id
                    WHERE e.employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return htmlspecialchars($employee['position_name']);
            }
        }

        return 'Unknown Position';
    }
}
