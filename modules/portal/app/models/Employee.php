<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Employee
{
    private $conn;
    private $table = "em_employees";
    private $usersTable = "ep_users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getByEmployeeNum($employee_code)
    {
        $query = "SELECT e.*, 
                     eu.username,
                     eu.password,
                     eu.is_admin,
                     eu.is_active
              FROM {$this->table} e
              LEFT JOIN {$this->usersTable} eu ON e.user_id = eu.id
              WHERE e.employee_code = :employee_code
              LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':employee_code' => $employee_code
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getByUserId($user_id)
    {
        $query = "
        SELECT 
            e.*,
            u.username,
            u.is_admin,
            p.position_name AS position
        FROM {$this->table} e
        JOIN {$this->usersTable} u
            ON e.user_id = u.id
        LEFT JOIN em_positions p
            ON e.position_id = p.position_id
        WHERE e.user_id = :user_id
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function findByUserId($user_id)
    {
        $query = "SELECT * FROM $this->table WHERE user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAllExcept(int $employeeId): array
    {
        $sql = "
        SELECT id, employee_num,
        first_name, last_name, middle_name
        FROM {$this->table}
        WHERE id != :employee_id
          AND employment_status = 'active'
        ORDER BY created_at ASC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getWithoutUserAccount(): array
    {
        $sql = "
        SELECT e.*
        FROM {$this->table} e
        LEFT JOIN {$this->usersTable} u
            ON e.user_id = u.id
        WHERE u.id IS NULL
        ORDER BY e.created_at ASC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByEmployeeId($employeeId)
    {
        $sql = "SELECT *
            FROM {$this->table}
            WHERE id = :employee_id
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateUserId($employeeId, $userId)
    {
        $sql = "UPDATE {$this->table}
            SET user_id = :user_id
            WHERE id = :employee_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':user_id' => $userId,
            ':employee_id' => $employeeId
        ]);
    }
}
