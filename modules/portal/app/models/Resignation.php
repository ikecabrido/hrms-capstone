<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Resignation
{
    private $conn;
    private $table = 'ep_resignation_requests';
    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getResignation(int $employee_id): array
    {
        $query = "
        SELECT *
        FROM {$this->table}
        WHERE employee_id = :employee_id
        ORDER BY created_at DESC ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function hasPendingResignation(int $employeeId): bool
    {
        $sql = "
        SELECT resignation_id
        FROM ep_resignation_requests
        WHERE employee_id = :employee_id
        AND LOWER(status) = 'pending'
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create(array $data): bool
    {
        $sql = "
        INSERT INTO ep_resignation_requests (
            employee_id,
            resignation_type,
            resignation_reason,
            attachment,
            date_submitted,
            intended_last_working_day,
            status,
            employee_remarks,
            hr_remarks,
            reviewed_by,
            reviewed_at,
            created_at,
            updated_at
        ) VALUES (
            :employee_id,
            :resignation_type,
            :resignation_reason,
            :attachment,
            :date_submitted,
            :intended_last_working_day,
            :status,
            :employee_remarks,
            :hr_remarks,
            :reviewed_by,
            :reviewed_at,
            :created_at,
            :updated_at
        )
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':resignation_type' => $data['resignation_type'],
            ':resignation_reason' => $data['resignation_reason'],
            ':attachment' => $data['attachment'],
            ':date_submitted' => $data['date_submitted'],
            ':intended_last_working_day' => $data['intended_last_working_day'],
            ':status' => $data['status'],
            ':employee_remarks' => $data['employee_remarks'],
            ':hr_remarks' => $data['hr_remarks'],
            ':reviewed_by' => $data['reviewed_by'],
            ':reviewed_at' => $data['reviewed_at'],
            ':created_at' => $data['created_at'],
            ':updated_at' => $data['updated_at']
        ]);
    }
}