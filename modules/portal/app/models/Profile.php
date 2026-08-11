<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Profile
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
}