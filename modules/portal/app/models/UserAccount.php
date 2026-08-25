<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class UserAccount
{
    private $conn;
    private $table = "ep_users";
    private $employeesTable = "em_employees";
    private $attendanceTable = "ta_attendance";
    private $departmentTable = "em_departments";
    private $positionTable = "em_positions";
    private $userTable = "ep_users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->employeesTable} ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllEmployees(): array
    {
        $sql = "
        SELECT
            e.*,
            p.position_name,
            d.department_name,
            u.profile_image
        FROM {$this->employeesTable} e

        LEFT JOIN {$this->positionTable} p
            ON e.position_id = p.position_id

        LEFT JOIN {$this->departmentTable} d
            ON e.department_id = d.department_id

        LEFT JOIN {$this->userTable} u
            ON e.user_id = u.id

        ORDER BY e.created_at DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
            (username, email, password, role)
            VALUES
            (:username, :email, :password, :role)";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':role' => $data['role']
        ]);

        return $this->conn->lastInsertId();
    }
    public function getByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} 
            WHERE username = :username 
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':username' => $username
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} 
            WHERE email = :email 
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAllAttendance()
    {
        $sql = "
        SELECT
            a.*,
            e.employee_code,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM {$this->attendanceTable} a
        INNER JOIN {$this->employeesTable} e
            ON e.employee_id = a.employee_id
        ORDER BY a.attendance_date DESC, a.time_in DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getInactiveUsers(): array
    {
        $sql = "
        SELECT
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_active,
            e.employee_id,
            e.first_name,
            e.middle_name,
            e.last_name
        FROM ep_users u
        INNER JOIN {$this->employeesTable} e
            ON e.user_id = u.id
        WHERE u.is_active = 0
        ORDER BY e.last_name ASC, e.first_name ASC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function setActive(int $userId): bool
    {
        $sql = "
        UPDATE {$this->userTable}
        SET is_active = 1
        WHERE id = :user_id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':user_id' => $userId
        ]);
    }
}