<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Performance
{
    private $conn;
    private $table = "pm_feedback_360_entries";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPerformance(int $employee_id): array
    {
        $query = "
        SELECT * FROM {$this->table}
        WHERE employee_id = :employee_id
        ORDER BY created_at DESC;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":employee_id" => $employee_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}