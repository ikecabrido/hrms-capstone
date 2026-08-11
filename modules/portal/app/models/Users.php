<?php

namespace App\Models;

use App\Config\Database;
use PDO;
class Users
{
    private $conn;
    private $table = "ep_users";
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all()
    {
        $stmt = $this->conn->prepare("
                    SELECT *
            FROM {$this->table}
        ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function login($username)
    {
        $query = "
        SELECT
            id,
            username,
            password,
            email,
            role,
            is_admin,
            is_active,
            theme,
            created_at
        FROM {$this->table}
        WHERE username = :username
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            SET password_reset_token = :token,
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
            SET password_reset_token = NULL,
                password_reset_expires = NULL
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => $userId
        ]);
    }
    public function findById($id)
    {
        $sql = "
        SELECT *
        FROM {$this->table}
        WHERE id = :id
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
            (
                role,
                username,
                email,
                password
            )
            VALUES
            (
                :role,
                :username,
                :email,
                :password
            )";

        $stmt = $this->conn->prepare($sql);

        $success = $stmt->execute([
            ':role' => $data['role'],
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password']
        ]);

        if ($success) {
            return $this->conn->lastInsertId();
        }

        return false;
    }
    public function filterSelf($excludeUserId = null)
    {
        $query = "
        SELECT
            u.*,
            e.employee_id
        FROM {$this->table} u
        LEFT JOIN employees e
            ON e.user_id = u.id
    ";

        if ($excludeUserId) {
            $query .= "
            WHERE u.id != :user_id
            ORDER BY u.id DESC
        ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $excludeUserId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $query .= " ORDER BY u.id DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function toggleAdmin($id)
    {
        $query = "UPDATE users
              SET is_admin = NOT is_admin
              WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':id' => $id
        ]);
    }
    public function update($id, $username, $email)
    {
        $sql = "
        UPDATE {$this->table}
        SET
            username = :username,
            email = :email
        WHERE id = :id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':username' => $username,
            ':email' => $email
        ]);
    }
    public function updateActiveStatus(int $id, int $status)
    {
        $sql = "UPDATE users
            SET is_active = :status
            WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }
}
