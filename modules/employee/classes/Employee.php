<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * Employee
 *
 * Wraps CRUD + lookup operations against the `em_employees` table
 * (the authoritative employee master table in the current `hrms` schema).
 *
 * Columns (verified against current hrms.sql):
 * employee_id, employee_code, user_id, first_name, middle_name, last_name,
 * suffix, gender, birth_date, birth_place, civil_status, citizenship,
 * religion, email, mobile_no, phone_no, current_address, permanent_address,
 * department_id, position_id, hire_date, regular_date, employment_status,
 * employment_type, unit_load, graduate_level, ranking, credentials,
 * faculty_notes, negotiated_salary, created_at, updated_at,
 * is_archived, archived_at, archived_date
 */
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

    /**
     * Get all active (non-archived) employees with department/position names.
     */
    public function getEmployees()
    {
        $sql = "SELECT
                    e.employee_id,
                    e.employee_code,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    e.suffix,
                    e.email,
                    e.mobile_no,
                    e.department_id,
                    e.position_id,
                    e.employment_status,
                    e.employment_type,
                    e.hire_date,
                    d.department_name,
                    p.position_name
                FROM em_employees AS e
                LEFT JOIN em_departments AS d ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                WHERE e.is_archived = 0
                ORDER BY e.last_name, e.first_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search / filter employees (used by the Employee Database page).
     *
     * @param string|null $keyword    matches name or employee_code
     * @param int|null    $departmentId
     * @param int|null    $positionId
     * @param string|null $status     employment_status enum value
     * @param bool        $includeArchived
     */
    public function searchEmployees($keyword = null, $departmentId = null, $positionId = null, $status = null, $includeArchived = false)
    {
        $sql = "SELECT
                    e.employee_id,
                    e.employee_code,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    e.suffix,
                    e.email,
                    e.mobile_no,
                    e.department_id,
                    e.position_id,
                    e.employment_status,
                    e.employment_type,
                    e.hire_date,
                    e.is_archived,
                    d.department_name,
                    p.position_name
                FROM em_employees AS e
                LEFT JOIN em_departments AS d ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                WHERE 1 = 1";

        $params = [];

        if (!$includeArchived) {
            $sql .= " AND e.is_archived = 0";
        }

        if (!empty($keyword)) {
            $sql .= " AND (e.first_name LIKE :kw
                         OR e.middle_name LIKE :kw
                         OR e.last_name LIKE :kw
                         OR e.employee_code LIKE :kw)";
            $params[':kw'] = '%' . $keyword . '%';
        }

        if (!empty($departmentId)) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }

        if (!empty($positionId)) {
            $sql .= " AND e.position_id = :position_id";
            $params[':position_id'] = $positionId;
        }

        if (!empty($status)) {
            $sql .= " AND e.employment_status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY e.last_name, e.first_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single employee's core record (with department/position names).
     */
    public function getEmployeeById($employeeId)
    {
        $sql = "SELECT
                    e.*,
                    d.department_name,
                    p.position_name
                FROM em_employees AS e
                LEFT JOIN em_departments AS d ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                WHERE e.employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    /**
     * Generate the next employee_code in the EMP-000001 sequence.
     */
    public function generateNextEmployeeCode()
    {
        $stmt = $this->conn->prepare(
            "SELECT employee_code FROM em_employees
             WHERE employee_code LIKE 'EMP-%'
             ORDER BY employee_id DESC LIMIT 1"
        );
        $stmt->execute();
        $last = $stmt->fetchColumn();

        $nextNumber = 1;
        if ($last && preg_match('/EMP-(\d+)/', $last, $m)) {
            $nextNumber = ((int) $m[1]) + 1;
        }

        return 'EMP-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new employee record. Returns the new employee_id.
     *
     * $data keys map directly to em_employees columns; only known
     * columns are accepted (whitelist below).
     */
    public function createEmployee(array $data)
    {
        $employeeCode = $this->generateNextEmployeeCode();

        $sql = "INSERT INTO em_employees (
                    employee_code, first_name, middle_name, last_name, suffix,
                    gender, birth_date, birth_place, civil_status, citizenship,
                    religion, email, mobile_no, phone_no, current_address,
                    permanent_address, department_id, position_id, hire_date,
                    regular_date, employment_status, employment_type, unit_load,
                    graduate_level, ranking, credentials, faculty_notes,
                    negotiated_salary
                ) VALUES (
                    :employee_code, :first_name, :middle_name, :last_name, :suffix,
                    :gender, :birth_date, :birth_place, :civil_status, :citizenship,
                    :religion, :email, :mobile_no, :phone_no, :current_address,
                    :permanent_address, :department_id, :position_id, :hire_date,
                    :regular_date, :employment_status, :employment_type, :unit_load,
                    :graduate_level, :ranking, :credentials, :faculty_notes,
                    :negotiated_salary
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':employee_code'     => $employeeCode,
            ':first_name'        => $data['first_name'] ?? '',
            ':middle_name'       => $data['middle_name'] ?? null,
            ':last_name'         => $data['last_name'] ?? '',
            ':suffix'            => $data['suffix'] ?? null,
            ':gender'            => $data['gender'] ?? null,
            ':birth_date'        => $data['birth_date'] ?: null,
            ':birth_place'       => $data['birth_place'] ?? null,
            ':civil_status'      => $data['civil_status'] ?? null,
            ':citizenship'       => $data['citizenship'] ?? null,
            ':religion'          => $data['religion'] ?? null,
            ':email'             => $data['email'] ?? '',
            ':mobile_no'         => $data['mobile_no'] ?? null,
            ':phone_no'          => $data['phone_no'] ?? null,
            ':current_address'   => $data['current_address'] ?? null,
            ':permanent_address' => $data['permanent_address'] ?? null,
            ':department_id'     => $data['department_id'] ?: null,
            ':position_id'       => $data['position_id'] ?: null,
            ':hire_date'         => $data['hire_date'] ?: null,
            ':regular_date'      => $data['regular_date'] ?: null,
            ':employment_status' => $data['employment_status'] ?? 'Probationary',
            ':employment_type'   => $data['employment_type'] ?? null,
            ':unit_load'         => $data['unit_load'] ?: null,
            ':graduate_level'    => $data['graduate_level'] ?? 'None',
            ':ranking'           => $data['ranking'] ?? null,
            ':credentials'       => $data['credentials'] ?? null,
            ':faculty_notes'     => $data['faculty_notes'] ?? null,
            ':negotiated_salary' => $data['negotiated_salary'] ?: null,
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Update an existing employee's core record.
     * Returns an array of [field => [old, new]] for fields that actually changed
     * (used by the controller to write employee_change_history entries).
     */
    public function updateEmployee($employeeId, array $data)
    {
        $existing = $this->getEmployeeById($employeeId);
        if (!$existing) {
            return false;
        }

        $fields = [
            'first_name', 'middle_name', 'last_name', 'suffix', 'gender',
            'birth_date', 'birth_place', 'civil_status', 'citizenship', 'religion',
            'email', 'mobile_no', 'phone_no', 'current_address', 'permanent_address',
            'department_id', 'position_id', 'hire_date', 'regular_date',
            'employment_status', 'employment_type', 'unit_load', 'graduate_level',
            'ranking', 'credentials', 'faculty_notes', 'negotiated_salary',
        ];

        $set = [];
        $params = [':employee_id' => $employeeId];
        $changes = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $newValue = $data[$field] === '' ? null : $data[$field];
            $oldValue = $existing[$field] ?? null;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = [$oldValue, $newValue];
            }

            $set[] = "$field = :$field";
            $params[":$field"] = $newValue;
        }

        if (empty($set)) {
            return $changes; // nothing to update
        }

        $sql = "UPDATE em_employees SET " . implode(', ', $set) . " WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $changes;
    }

    /**
     * Soft-archive an employee (no hard delete).
     */
    public function archiveEmployee($employeeId)
    {
        $sql = "UPDATE em_employees
                SET is_archived = 1,
                    archived_at = CURRENT_TIMESTAMP,
                    archived_date = CURRENT_TIMESTAMP
                WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':employee_id' => $employeeId]);
    }

    /**
     * Restore a previously archived employee.
     */
    public function restoreEmployee($employeeId)
    {
        $sql = "UPDATE em_employees
                SET is_archived = 0,
                    archived_at = NULL,
                    archived_date = NULL
                WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':employee_id' => $employeeId]);
    }

    /**
     * Get logged-in employee ID from the session.
     */
    public function getEmployeeId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['employee_id'] ?? null;
    }

    /**
     * Get logged-in employee's display name.
     */
    public function getEmployeeName()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
                return htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
            }
        }

        return 'Unknown User';
    }

    /**
     * Get logged-in employee's position name.
     */
    public function getEmployeePosition()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT p.position_name
                    FROM em_employees AS e
                    LEFT JOIN em_positions AS p ON e.position_id = p.position_id
                    WHERE e.employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return htmlspecialchars($employee['position_name'] ?? 'Unknown Position');
            }
        }

        return 'Unknown Position';
    }

    /**
     * Dashboard stats: total active, by status, by department, recent hires.
     */
    public function getDashboardStats()
    {
        $stats = [];

        $stats['total_active'] = (int) $this->conn->query(
            "SELECT COUNT(*) FROM em_employees WHERE is_archived = 0"
        )->fetchColumn();

        $stats['total_archived'] = (int) $this->conn->query(
            "SELECT COUNT(*) FROM em_employees WHERE is_archived = 1"
        )->fetchColumn();

        $statusStmt = $this->conn->query(
            "SELECT employment_status, COUNT(*) AS cnt
             FROM em_employees
             WHERE is_archived = 0
             GROUP BY employment_status"
        );
        $stats['by_status'] = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        $deptStmt = $this->conn->query(
            "SELECT d.department_name, COUNT(e.employee_id) AS cnt
             FROM em_departments d
             LEFT JOIN em_employees e ON e.department_id = d.department_id AND e.is_archived = 0
             GROUP BY d.department_id, d.department_name
             ORDER BY d.department_name"
        );
        $stats['by_department'] = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        $recentStmt = $this->conn->prepare(
            "SELECT employee_id, employee_code, first_name, last_name, hire_date
             FROM em_employees
             WHERE is_archived = 0
             ORDER BY hire_date DESC, employee_id DESC
             LIMIT 5"
        );
        $recentStmt->execute();
        $stats['recent_hires'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }
}
