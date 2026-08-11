<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PasswordReset
{
    private $conn;
    private $table = "ep_users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEmail($email)
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function savePasswordResetToken($userId, $token, $expires)
    {
        $sql = "
            UPDATE {$this->table}
            SET
                password_reset_token = :token,
                password_reset_expires = :expires
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':token' => $token,
            ':expires' => $expires,
            ':id' => $userId
        ]);
    }

    public function findByResetToken($token)
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE password_reset_token = :token
            AND password_reset_expires > NOW()
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':token' => $token
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($userId, $hashedPassword)
    {
        $sql = "
            UPDATE {$this->table}
            SET password = :password
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    public function clearPasswordResetToken($userId)
    {
        $sql = "
            UPDATE {$this->table}
            SET
                password_reset_token = NULL,
                password_reset_expires = NULL
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => $userId
        ]);
    }
}