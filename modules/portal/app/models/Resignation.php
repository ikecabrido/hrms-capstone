<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Resignation
{
    private $conn;
    private string $table = 'ep_resignation_requests';
    private string $employeeTable = 'em_employees';
    private string $userTable = 'ep_users';
    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }
    public function all(): array
    {
        $sql = "
        SELECT
            r.*,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM {$this->table} r
        INNER JOIN {$this->employeeTable} e
            ON r.employee_id = e.employee_id
        ORDER BY r.created_at DESC
    ";

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
    public function approve(
        int $resignationId,
        string $hrRemarks,
        ?int $reviewedBy
    ): bool {
        try {
            $this->conn->beginTransaction();
            $sql = "SELECT
                    r.resignation_id,
                    r.employee_id,
                    e.id AS employee_record_id,
                    e.user_id,
                    u.id AS user_id,
                    u.is_active
                FROM {$this->table} r
                LEFT JOIN {$this->employeeTable} e
                    ON e.id = r.employee_id
                LEFT JOIN {$this->userTable} u
                    ON u.id = e.user_id
                WHERE r.resignation_id = :resignation_id
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':resignation_id' => $resignationId
            ]);

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$employee) {
                throw new Exception('Resignation request not found.');
            }
            if (empty($employee['employee_record_id'])) {
                throw new Exception('Employee record not found.');
            }
            if (empty($employee['user_id'])) {
                throw new Exception('Employee user account not found.');
            }
            $sql = "UPDATE {$this->table}
                SET
                    status = 'Approved',
                    hr_remarks = :hr_remarks,
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                WHERE resignation_id = :resignation_id";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':hr_remarks' => $hrRemarks ?: null,
                ':reviewed_by' => $reviewedBy,
                ':resignation_id' => $resignationId
            ]);
            $sql = "UPDATE {$this->userTable}
                SET is_active = 0
                WHERE id = :user_id";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':user_id' => $employee['user_id']
            ]);
            if ($stmt->rowCount() === 0) {
            }

            $this->conn->commit();

            return true;

        } catch (\Throwable $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Resignation approval failed: ' . $e->getMessage());

            return false;
        }
    }
    public function reject(
        int $resignationId,
        string $hrRemarks,
        ?int $reviewedBy
    ): bool {

        try {

            $sql = "UPDATE {$this->table}
                SET
                    status = 'Rejected',
                    hr_remarks = :hr_remarks,
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                WHERE resignation_id = :resignation_id
                AND status = 'Pending'";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':hr_remarks' => $hrRemarks,
                ':reviewed_by' => $reviewedBy,
                ':resignation_id' => $resignationId
            ]);

            return $stmt->rowCount() > 0;

        } catch (\Throwable $e) {

            error_log(
                'Resignation Model Reject Error: ' .
                $e->getMessage()
            );

            return false;
        }
    }
}