<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Payroll
{
    private $conn;
    private $table = "pr_payslips";
    private $tableRequest = "ep_payroll_request";
    private $employeeTable = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all()
    {
        $sql = "
        SELECT
            pr.*,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM {$this->tableRequest} pr
        INNER JOIN {$this->employeeTable} e
            ON pr.employee_id = e.employee_id
        ORDER BY pr.created_at DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function createPayrollRequest(int $employee_id, array $data): bool
    {
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
    public function approveWithDocument(
        $requestId,
        $documentPath,
        $processedBy
    ) {
        $sql = "
        UPDATE {$this->tableRequest}
        SET
            document_path = :document_path,
            status = 'Approved',
            processed_at = NOW(),
            processed_by = :processed_by,
            rejection_reason = NULL
        WHERE id = :id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':document_path' => $documentPath,
            ':processed_by' => $processedBy,
            ':id' => $requestId
        ]);
    }
    public function rejectRequest(
        $requestId,
        $reason,
        $processedBy
    ) {
        $sql = "
        UPDATE {$this->tableRequest}
        SET
            status = 'Rejected',
            rejection_reason = :reason,
            processed_at = NOW(),
            processed_by = :processed_by
        WHERE id = :id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':reason' => $reason,
            ':processed_by' => $processedBy,
            ':id' => $requestId
        ]);
    }

}