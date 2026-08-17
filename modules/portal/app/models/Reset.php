<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Reset
{
    private $conn;
    private $table = "ep_users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE email = :email
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
        string $token,
        string $expiresAt
    ): bool {
        $sql = "UPDATE {$this->table}
                SET
                    password_reset_token = :token,
                    password_reset_expires_at = :expires_at
                WHERE id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':user_id' => $userId
        ]);
    }
    public function findByResetToken(string $token): ?array
    {
        $sql = "SELECT *
            FROM {$this->table}
            WHERE password_reset_token = :token
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':token' => $token
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}