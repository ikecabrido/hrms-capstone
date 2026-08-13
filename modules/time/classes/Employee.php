<?php

include_once __DIR__ . '/../app/core/TimeDatabase.php';

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
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    department,
                    position,
                    employment_status AS status
                FROM em_employees
                ORDER BY last_name, first_name";

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

    private function getProfileRecord()
    {
        $employeeId = $this->getEmployeeId();

        if (!$employeeId) {
            return null;
        }

        $sql = "SELECT employee_id, first_name, middle_name, last_name, department, position
                FROM em_employees
                WHERE employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($employee) {
            return $employee;
        }

        $sql = "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name, e.department, p.position_name AS position
                FROM hrms_employee e
                LEFT JOIN hrms_position p ON p.position_id = e.position
                WHERE e.employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get logged-in employee name
     */
    public function getEmployeeName()
    {
        $employee = $this->getProfileRecord();

        if ($employee) {
            $firstName = trim($employee['first_name'] ?? '');
            $middleName = trim($employee['middle_name'] ?? '');
            $lastName = trim($employee['last_name'] ?? '');

            $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
            return $fullName !== '' ? htmlspecialchars($fullName) : 'Unknown User';
        }

        return 'Unknown User';
    }

    /**
     * Get logged-in employee position
     */
    public function getEmployeePosition()
    {
        $employee = $this->getProfileRecord();

        if ($employee) {
            $position = trim((string) ($employee['position'] ?? ''));
            if ($position !== '') {
                return htmlspecialchars($position);
            }
        }

        return 'Unknown Position';
    }

    public function getAll($status = 'Active', $limit = 100, $offset = 0, $search = '')
    {
        $status = trim($status);
        $sql = "SELECT employee_id,
                       first_name,
                       middle_name,
                       last_name,
                       CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name,
                       department,
                       position,
                       employment_status AS status
                FROM em_employees
                WHERE LOWER(employment_status) = LOWER(:status)";

        if ($search !== '') {
            $sql .= " AND (CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE :search OR employee_id LIKE :search)";
        }

        $sql .= " ORDER BY last_name, first_name LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        if ($search !== '') {
            $searchValue = '%' . $search . '%';
            $stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount($status = 'Active')
    {
        $status = trim($status);
        $sql = "SELECT COUNT(*) AS total FROM em_employees WHERE LOWER(employment_status) = LOWER(:status)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }
}

class TimeTemplateEmployee extends Employee
{
}
