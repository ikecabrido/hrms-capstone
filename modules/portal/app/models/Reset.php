<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Reset
{
    private $conn;
    private $table = "ep_users";
    private $employeeTable = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT
                    u.id,
                    u.email,
                    CONCAT(
                        COALESCE(e.first_name, ''),
                        ' ',
                        COALESCE(e.last_name, '')
                    ) AS full_name
                FROM {$this->table} u
                LEFT JOIN {$this->employeeTable} e
                    ON e.user_id = u.id
                WHERE u.email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function savePasswordResetToken(
        int $userId,
        string $token
    ): bool {
        $sql = "UPDATE {$this->table}
            SET
                password_reset_token = :token,
                password_reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)
            WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':token' => $token,
            ':id' => $userId
        ]);
    }

    public function findByResetToken(string $token): ?array
    {
        $sql = "SELECT
                u.id,
                u.email,
                u.password_reset_token,
                u.password_reset_expires,
                CONCAT(
                    COALESCE(e.first_name, ''),
                    ' ',
                    COALESCE(e.last_name, '')
                ) AS full_name
            FROM {$this->table} u
            LEFT JOIN {$this->employeeTable} e
                ON e.user_id = u.id
            WHERE u.password_reset_token = :token
            AND u.password_reset_expires > NOW()
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':token' => trim($token)
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool {
        $sql = "UPDATE {$this->table}
                SET password = :password
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    public function clearPasswordResetToken(int $userId): bool
    {
        $sql = "UPDATE {$this->table}
                SET
                    password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => $userId
        ]);
    }
}