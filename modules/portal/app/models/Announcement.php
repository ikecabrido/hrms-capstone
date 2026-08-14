<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Announcement
{
    private $conn;
    private string $table = 'eer_announcements';
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare("
        SELECT *
        FROM eer_announcements
        WHERE eer_announcements_id = :id
        LIMIT 1
    ");

        $stmt->execute([
            'id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
