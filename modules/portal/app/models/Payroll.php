<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Payroll
{
    private $conn;
    private $table = "pr_payslips";
    private $tableRequest = "ep_payroll_request";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getPayroll(int $employee_id): array
    {
        $query = "
        SELECT *
        FROM {$this->table}
        WHERE employee_id = :employee_id
        ORDER BY generated_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPayrollRequests(int $employee_id): array
    {
        $query = "
            SELECT *
            FROM {$this->tableRequest}
            WHERE employee_id = :employee_id
            ORDER BY requested_at DESC
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createPayrollRequest(int $employee_id,array $data): bool {
        $query = "
            INSERT INTO {$this->tableRequest} (
                employee_id,
                request_type,
                purpose,
                payroll_period_start,
                payroll_period_end,
                status,
                requested_at
            )
            VALUES (
                :employee_id,
                :request_type,
                :purpose,
                :payroll_period_start,
                :payroll_period_end,
                :status,
                NOW()
            )
        ";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':employee_id' => $employee_id,
            ':request_type' => $data['request_type'],
            ':purpose' => $data['subject'],
            ':payroll_period_start' => $data['period_from'],
            ':payroll_period_end' => $data['period_to'],
            ':status' => 'pending'
        ]);
    }


}