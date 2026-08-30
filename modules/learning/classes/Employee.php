<?php

include_once __DIR__ . '/../../../database/db.php';

class Employee
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

    private function getSessionEmployeeId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['employee_id'])) {
            return (int) $_SESSION['employee_id'];
        }

        if (!empty($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
            $stmt = $this->conn->prepare('SELECT employee_id FROM em_employees WHERE user_id = :user_id LIMIT 1');
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return (int) $row['employee_id'];
            }
        }

        return null;
    }

    public function getEmployees()
    {
        $sql = "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name, e.employment_status AS status,
                       d.department_name, p.position_name
                FROM em_employees AS e
                LEFT JOIN em_departments AS d ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                ORDER BY e.employee_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeId()
    {
        return $this->getSessionEmployeeId();
    }

    public function getEmployeeName()
    {
        $employeeId = $this->getSessionEmployeeId();
        if (!$employeeId) {
            return 'Unknown User';
        }

        $stmt = $this->conn->prepare('SELECT first_name, last_name FROM em_employees WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute([':employee_id' => $employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            return htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')));
        }

        return 'Unknown User';
    }

    public function getEmployeeFirstName()
    {
        $employeeId = $this->getSessionEmployeeId();
        if (!$employeeId) {
            return 'User';
        }

        $stmt = $this->conn->prepare('SELECT first_name FROM em_employees WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute([':employee_id' => $employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            return htmlspecialchars($employee['first_name'] ?? 'User');
        }

        return 'User';
    }

    public function getGreeting()
    {
        $firstName = $this->getEmployeeFirstName();
        $hour = (int) date('G');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } elseif ($hour < 18) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        return sprintf('%s, %s!', $greeting, $firstName);
    }

    public function getEmployeePosition()
    {
        $employeeId = $this->getSessionEmployeeId();
        if (!$employeeId) {
            return 'Unknown Position';
        }

        $stmt = $this->conn->prepare('SELECT p.position_name FROM em_employees AS e LEFT JOIN em_positions AS p ON e.position_id = p.position_id WHERE e.employee_id = :employee_id LIMIT 1');
        $stmt->execute([':employee_id' => $employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee && !empty($employee['position_name'])) {
            return htmlspecialchars($employee['position_name']);
        }

        return 'Unknown Position';
    }
}
