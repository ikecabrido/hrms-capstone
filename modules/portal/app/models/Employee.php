<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Employee
{
    private $conn;
    private $table = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getByEmployeeNum($employee_num)
    {
        $query = "SELECT e.*, 
                     eu.username,
                     eu.password,
                     eu.is_admin,
                     eu.is_active
              FROM {$this->table} e
              LEFT JOIN ep_users eu ON e.user_id = eu.id
              WHERE e.employee_num = :employee_num
              LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':employee_num' => $employee_num
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
        JOIN ep_users u
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
}
