<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Leave
{
    private $conn;
    private $table = "leave_requests";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getLeave(int $employee_id): array
    {
        $query = "
        SELECT *
        FROM {$this->table}
        WHERE employee_id = :employee_id
        ORDER BY created_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO {$this->table}
            (
                employee_id,
                leave_type,
                start_date,
                end_date,
                reason,
                status,
                created_at,
                updated_at
            )
            VALUES
            (
                :employee_id,
                :leave_type,
                :start_date,
                :end_date,
                :reason,
                :status,
                NOW(),
                NOW()
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':leave_type' => $data['leave_type'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':reason' => $data['reason'],
            ':status' => $data['status'] ?? 'PENDING'
        ]);
    }


}