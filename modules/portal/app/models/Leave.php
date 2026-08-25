<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Leave
{
    private $conn;
    private $table = "ta_leave_requests";
    private $leaveTypeTable = "ta_leave_types";
    private $employeesTable = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function allTypes()
    {
        $query = "SELECT * FROM {$this->leaveTypeTable} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function all(): array
    {
        $sql = "
    SELECT 
        lr.*,
        e.first_name,
        e.last_name,
        lt.leave_type_name,
        lt.description AS leave_type_description,
        lt.days_per_year,
        lt.is_deductible,
        lt.requires_approval
    FROM {$this->table} lr
    INNER JOIN {$this->employeesTable} e
        ON lr.employee_id = e.employee_id
    INNER JOIN {$this->leaveTypeTable} lt
        ON lr.leave_type_id = lt.leave_type_id
    ORDER BY lr.date_submitted DESC
";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getLeave($employeeId)
    {
        $sql = "
        SELECT
            lr.*,
            lt.leave_type_name,
            lt.description AS leave_type_description,
            lt.days_per_year,
            lt.is_deductible,
            lt.requires_approval
        FROM {$this->table} lr
        INNER JOIN {$this->leaveTypeTable} lt
            ON lr.leave_type_id = lt.leave_type_id
        WHERE lr.employee_id = :employee_id
        ORDER BY lr.date_submitted DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getLeaveTypes(): array
    {
        $query = "
            SELECT
                leave_type_id,
                leave_type_name,
                description,
                days_per_year,
                is_deductible,
                requires_approval,
                created_at
            FROM {$this->leaveTypeTable}
            ORDER BY leave_type_name ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById(int $id): ?array
    {
        $query = "
            SELECT
                lr.id,
                lr.employee_id,
                lr.leave_type_id,
                lt.leave_type_name,
                lt.description AS leave_type_description,
                lt.days_per_year,
                lt.is_deductible,
                lt.requires_approval,
                lr.start_date,
                lr.end_date,
                lr.date_submitted,
                lr.updated_at,
                lr.status,
                lr.details,
                lr.supporting_document,
                lr.reject_reason
            FROM {$this->table} lr
            LEFT JOIN {$this->leaveTypeTable} lt
                ON lr.leave_type_id = lt.leave_type_id
            WHERE lr.id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':id' => $id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
    public function create(array $data): bool
    {
        $sql = "
        INSERT INTO {$this->table}
        (
            employee_id,
            leave_type_id,
            start_date,
            end_date,
            details,
            supporting_document
        )
        VALUES
        (
            :employee_id,
            :leave_type_id,
            :start_date,
            :end_date,
            :details,
            :supporting_document
        )
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':leave_type_id' => $data['leave_type_id'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':details' => $data['details'],
            ':supporting_document' => $data['supporting_document'] ?? null
        ]);
    }
    public function findLeaveType(int $id)
    {
        $sql = "SELECT * FROM {$this->leaveTypeTable} WHERE leave_type_id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function cancel(int $leaveRequestId): bool
    {
        $sql = "
        UPDATE {$this->table}
        SET status = 'CANCELLED'
        WHERE id = :id
        AND status = 'PENDING'
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id' => $leaveRequestId
        ]);
    }
    public function rejectLeave($leaveId, $reason)
    {
        $sql = "
        UPDATE {$this->table}
        SET 
            status = 'rejected',
            reject_reason = :reason,
            updated_at = NOW()
        WHERE id = :leave_id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':leave_id' => $leaveId,
            ':reason' => $reason
        ]);
    }
    public function approveLeave($leaveId)
    {
        $sql = "
        UPDATE {$this->table}
        SET status = 'approved'
        WHERE id = :leave_id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':leave_id' => $leaveId
        ]);
    }
}