<?php

include_once __DIR__ . '/../../../database/db.php';

class Employee
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
            $database = new Database();
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
                    e.employee_code,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    d.department_name,
                    p.position_name,
                    e.employment_status
                FROM em_employees AS e
                LEFT JOIN em_departments AS d ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                WHERE e.is_archived = 0
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

        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
        if (!empty($userId)) {
            $stmt = $this->conn->prepare('SELECT e.employee_id
                FROM user_account ua
                INNER JOIN em_employees e ON e.employee_id = ua.employee_id
                WHERE ua.user_id = :user_id
                LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $employeeId = $stmt->fetchColumn();
            if (!empty($employeeId)) {
                $_SESSION['employee_id'] = (int)$employeeId;
                return (int)$employeeId;
            }
        }

        foreach ([$_SESSION['user']['employee_id'] ?? null, $_SESSION['employee_id'] ?? null] as $employeeId) {
            if (empty($employeeId)) {
                continue;
            }

            $stmt = $this->conn->prepare('SELECT employee_id FROM em_employees WHERE employee_id = :employee_id LIMIT 1');
            $stmt->execute(['employee_id' => $employeeId]);
            $validEmployeeId = $stmt->fetchColumn();
            if (!empty($validEmployeeId)) {
                $_SESSION['employee_id'] = (int)$validEmployeeId;
                return (int)$validEmployeeId;
            }
        }

        return null;
    }

    /**
     * Get logged-in employee name
     */
    public function getEmployeeName()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $this->getEmployeeId();

        if ($employeeId) {
            $sql = "SELECT 
                        first_name,
                        last_name
                    FROM em_employees
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

        $employeeId = $this->getEmployeeId();

        if ($employeeId) {
            $sql = "SELECT 
                        p.position_name
                    FROM em_employees AS e

                    LEFT JOIN em_positions AS p
                        ON e.position_id = p.position_id

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
