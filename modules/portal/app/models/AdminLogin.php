<?php

namespace App\Models;

use App\Config\Database;
use PDO;
class AdminLogin
{
    private $conn;
    private $table = "ep_users";
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT *
            FROM ep_users
            WHERE email = :email
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}