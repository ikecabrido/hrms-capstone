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
                    e.employee_id,
                    e.employee_code,
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

        return $_SESSION['employee_id'] ?? null;
    }

    private function getProfileRecord()
    {
        $employeeId = $this->getEmployeeId();

        if (!$employeeId) {
            return null;
        }

        $sql = "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name,
                       COALESCE(d.department_name, '') AS department,
                       COALESCE(p.position_name, '') AS position
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
                WHERE e.employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($employee) {
            return $employee;
        }

        $sql = "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name, e.department AS department, p.position_name AS position
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

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

        $employeeId = $_SESSION['employee_id'] ?? null;

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

    public function getById($employee_id)
    {
        $employee_id = (int) $employee_id;

        $queries = [
            "SELECT e.employee_id,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    e.department,
                    p.position_name AS position
             FROM hrms_employee e
             LEFT JOIN hrms_position p ON p.position_id = e.position
             WHERE e.employee_id = :employee_id
             LIMIT 1",
                "SELECT e.employee_id,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    COALESCE(d.department_name, '') AS department,
                    COALESCE(p.position_name, '') AS position
                 FROM em_employees e
                 LEFT JOIN em_departments d ON e.department_id = d.department_id
                 LEFT JOIN em_positions p ON e.position_id = p.position_id
                 WHERE e.employee_id = :employee_id
                 LIMIT 1"
        ];

        foreach ($queries as $sql) {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':employee_id', $employee_id, PDO::PARAM_INT);
                $stmt->execute();
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($employee) {
                    return $employee;
                }
            } catch (PDOException $e) {
                continue;
            }
        }

        return null;
    }

    public function getByUserId($user_id)
    {
        $user_id = (int) $user_id;

        $stmt = $this->conn->prepare("SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['employee_id'])) {
            return null;
        }

        return $this->getById((int) $row['employee_id']);
    }

    public function getAll($status = 'Active', $limit = 100, $offset = 0, $search = '')
    {
        $status = trim($status);
        $sql = "SELECT e.employee_id,
                   e.first_name,
                   e.middle_name,
                   e.last_name,
                   CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                   COALESCE(d.department_name, '') AS department,
                   COALESCE(p.position_name, '') AS position,
                   e.employment_status AS status
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE LOWER(e.employment_status) = LOWER(:status)";

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
