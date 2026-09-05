<?php

require_once __DIR__ . '/../../../database/db.php';

class SalaryAdjustmentModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(string $status = '', string $search = ''): array
    {
        try {
            $where = ['1=1'];
            $params = [];

            if ($status !== '' && $status !== 'all') {
                $where[] = 'sa.status = :status';
                $params[':status'] = $status;
            }

            if ($search !== '') {
                $where[] = '(CONCAT(e.first_name, " ", e.last_name) LIKE :search OR sa.adjustment_type LIKE :search OR sa.adjustment_id LIKE :search)';
                $params[':search'] = '%' . $search . '%';
            }

            $whereSql = implode(' AND ', $where);
            $sql = "
                SELECT sa.adjustment_id, sa.employee_id, sa.contract_id, sa.adjustment_type,
                       sa.reason, sa.previous_salary, sa.new_salary, sa.effective_date,
                       sa.approval_date, sa.application_date, sa.status,
                       sa.approved_by, sa.applied_by, sa.created_by, sa.created_by_role,
                       sa.created_at, sa.updated_at, sa.notes,
                       CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                       e.employee_code AS employee_no,
                       d.department_name,
                       COALESCE(p.position_name, 'N/A') AS position_name,
                       c.contract_number,
                       CONCAT(apr.first_name, ' ', apr.last_name) AS approver_name
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                LEFT JOIN lc_contracts c ON c.contract_id = sa.contract_id
                LEFT JOIN em_employees apr ON apr.employee_id = sa.approved_by
                WHERE {$whereSql}
                ORDER BY sa.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('SalaryAdjustmentModel::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $adjustmentId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT sa.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                       e.employee_code AS employee_no, e.department_id,
                       d.department_name,
                       COALESCE(p.position_name, 'N/A') AS position_name
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE sa.adjustment_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $adjustmentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('SalaryAdjustmentModel::getById error: ' . $e->getMessage());
            return null;
        }
    }

    public function getByEmployeeId(int $employeeId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT sa.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                       c.contract_number
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                LEFT JOIN lc_contracts c ON c.contract_id = sa.contract_id
                WHERE sa.employee_id = :eid
                ORDER BY sa.created_at DESC
            ");
            $stmt->execute([':eid' => $employeeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('SalaryAdjustmentModel::getByEmployeeId error: ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        $errors = [];

        $employeeId   = (int) ($data['employee_id'] ?? 0);
        $contractId   = !empty($data['contract_id']) ? (int) $data['contract_id'] : null;
        $adjustmentType = trim((string) ($data['adjustment_type'] ?? ''));
        $reason       = trim((string) ($data['reason'] ?? ''));
        $previousSalary = (float) ($data['previous_salary'] ?? 0);
        $newSalary     = (float) ($data['new_salary'] ?? 0);
        $effectiveDate = trim((string) ($data['effective_date'] ?? ''));
        $createdBy     = (int) ($data['created_by'] ?? 0);
        $createdByRole = trim((string) ($data['created_by_role'] ?? 'hr'));
        $notes         = trim((string) ($data['notes'] ?? ''));

        if ($employeeId <= 0) {
            $errors[] = 'Invalid employee.';
        }
        if ($adjustmentType === '') {
            $errors[] = 'Adjustment type is required.';
        }
        if ($previousSalary <= 0) {
            $errors[] = 'Previous salary must be greater than zero.';
        }
        if ($newSalary <= 0) {
            $errors[] = 'New salary must be greater than zero.';
        }
        if ($newSalary <= $previousSalary && $adjustmentType !== 'Demotion') {
            $errors[] = 'New salary should be higher than previous salary for this adjustment type.';
        }
        if ($effectiveDate === '') {
            $errors[] = 'Effective date is required.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO lc_salary_adjustments
                    (employee_id, contract_id, adjustment_type, reason,
                     previous_salary, new_salary, effective_date, status,
                     created_by, created_by_role, notes, created_at, updated_at)
                VALUES
                    (:employee_id, :contract_id, :adjustment_type, :reason,
                     :previous_salary, :new_salary, :effective_date, 'Draft',
                     :created_by, :created_by_role, :notes, NOW(), NOW())
            ");
            $stmt->execute([
                ':employee_id'     => $employeeId,
                ':contract_id'     => $contractId,
                ':adjustment_type' => $adjustmentType,
                ':reason'          => $reason ?: null,
                ':previous_salary' => $previousSalary,
                ':new_salary'      => $newSalary,
                ':effective_date'  => $effectiveDate,
                ':created_by'      => $createdBy,
                ':created_by_role' => $createdByRole,
                ':notes'           => $notes ?: null,
            ]);

            $adjustmentId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return ['success' => true, 'adjustment_id' => $adjustmentId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('SalaryAdjustmentModel::create error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to create salary adjustment: ' . $e->getMessage()]];
        }
    }

    public function approve(int $adjustmentId, int $approvedBy, string $approvalDate = ''): array
    {
        $errors = [];
        $approvalDate = $approvalDate ?: date('Y-m-d');

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT sa.*, e.negotiated_salary AS current_employee_salary
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                WHERE sa.adjustment_id = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => $adjustmentId]);
            $adjustment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$adjustment) {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment not found.']];
            }

            $currentStatus = $adjustment['status'];

            if ($currentStatus === 'Applied') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment has already been applied.']];
            }

            if ($currentStatus === 'Rejected') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment has been rejected and cannot be approved.']];
            }

            $stmt = $this->db->prepare("
                UPDATE lc_salary_adjustments
                SET status = 'Approved', approval_date = :approval_date, approved_by = :approved_by, updated_at = NOW()
                WHERE adjustment_id = :id
            ");
            $stmt->execute([
                ':id' => $adjustmentId,
                ':approval_date' => $approvalDate,
                ':approved_by' => $approvedBy,
            ]);

            $this->writeAuditTrail(
                'lc_salary_adjustments',
                $adjustmentId,
                'UPDATE',
                'hr',
                "Salary adjustment #{$adjustmentId} approved for employee #{$adjustment['employee_id']}. New salary: " . number_format($adjustment['new_salary'], 2)
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Salary adjustment approved successfully.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('SalaryAdjustmentModel::approve error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to approve salary adjustment: ' . $e->getMessage()]];
        }
    }

    public function apply(int $adjustmentId, int $appliedBy, string $applicationDate = ''): array
    {
        $errors = [];
        $applicationDate = $applicationDate ?: date('Y-m-d');

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT sa.*, e.negotiated_salary AS current_employee_salary
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                WHERE sa.adjustment_id = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => $adjustmentId]);
            $adjustment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$adjustment) {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment not found.']];
            }

            $currentStatus = $adjustment['status'];

            if ($currentStatus === 'Applied') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment has already been applied.']];
            }

            if ($currentStatus === 'Draft' || $currentStatus === 'Pending') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment must be approved before it can be applied. Current status: ' . $currentStatus]];
            }

            if ($currentStatus === 'Rejected') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment has been rejected and cannot be applied.']];
            }

            $currentEmployeeSalary = (float) ($adjustment['current_employee_salary'] ?? 0);
            $expectedPreviousSalary = (float) $adjustment['previous_salary'];

            if ($currentEmployeeSalary !== $expectedPreviousSalary) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => [
                        "The employee's current salary has changed since this adjustment was created.",
                        "Expected previous salary: ₱" . number_format($expectedPreviousSalary, 2),
                        "Current employee salary: ₱" . number_format($currentEmployeeSalary, 2),
                        "Please refresh the salary adjustment before applying it."
                    ]
                ];
            }

            $employeeId = (int) $adjustment['employee_id'];
            $newSalary = (float) $adjustment['new_salary'];

            $stmt = $this->db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :new_salary, updated_at = NOW()
                WHERE employee_id = :employee_id
            ");
            $stmt->execute([
                ':new_salary' => $newSalary,
                ':employee_id' => $employeeId,
            ]);

            $stmt = $this->db->prepare("
                UPDATE lc_salary_adjustments
                SET status = 'Applied', application_date = :application_date, applied_by = :applied_by, updated_at = NOW()
                WHERE adjustment_id = :id
            ");
            $stmt->execute([
                ':id' => $adjustmentId,
                ':application_date' => $applicationDate,
                ':applied_by' => $appliedBy,
            ]);

            $this->writeAuditTrail(
                'lc_salary_adjustments',
                $adjustmentId,
                'UPDATE',
                'hr',
                "Salary adjustment #{$adjustmentId} applied for employee #{$employeeId}. Salary updated from ₱" . number_format($expectedPreviousSalary, 2) . " to ₱" . number_format($newSalary, 2)
            );

            $this->writeAuditTrail(
                'em_employees',
                $employeeId,
                'UPDATE',
                'hr',
                "Employee #{$employeeId} negotiated_salary updated from ₱" . number_format($expectedPreviousSalary, 2) . " to ₱" . number_format($newSalary, 2) . " via salary adjustment #{$adjustmentId}"
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Salary adjustment applied successfully. Employee salary updated to ₱' . number_format($newSalary, 2) . '.'
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('SalaryAdjustmentModel::apply error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to apply salary adjustment: ' . $e->getMessage()]];
        }
    }

    public function reject(int $adjustmentId, int $rejectedBy, string $reason = ''): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT sa.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name
                FROM lc_salary_adjustments sa
                LEFT JOIN em_employees e ON e.employee_id = sa.employee_id
                WHERE sa.adjustment_id = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => $adjustmentId]);
            $adjustment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$adjustment) {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment not found.']];
            }

            $currentStatus = $adjustment['status'];

            if ($currentStatus === 'Applied') {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Salary adjustment has already been applied and cannot be rejected.']];
            }

            $stmt = $this->db->prepare("
                UPDATE lc_salary_adjustments
                SET status = 'Rejected', updated_at = NOW(), notes = CONCAT(IFNULL(notes, ''), '\nRejected: ', :reason)
                WHERE adjustment_id = :id
            ");
            $stmt->execute([
                ':id' => $adjustmentId,
                ':reason' => $reason ?: 'No reason provided',
            ]);

            $this->writeAuditTrail(
                'lc_salary_adjustments',
                $adjustmentId,
                'UPDATE',
                'hr',
                "Salary adjustment #{$adjustmentId} rejected for employee #{$adjustment['employee_id']}. Reason: " . ($reason ?: 'No reason provided')
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Salary adjustment rejected.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('SalaryAdjustmentModel::reject error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to reject salary adjustment: ' . $e->getMessage()]];
        }
    }

    public function applyDirectAdjustment(array $data): array
    {
        $errors = [];

        $employeeId     = (int) ($data['employee_id'] ?? 0);
        $contractId     = !empty($data['contract_id']) ? (int) $data['contract_id'] : null;
        $adjustmentType = trim((string) ($data['adjustment_type'] ?? ''));
        $reason         = trim((string) ($data['reason'] ?? ''));
        $previousSalary = (float) ($data['previous_salary'] ?? 0);
        $newSalary      = (float) ($data['new_salary'] ?? 0);
        $effectiveDate  = trim((string) ($data['effective_date'] ?? ''));
        $appliedBy      = (int) ($data['applied_by'] ?? 0);
        $applicationDate = date('Y-m-d');

        if ($employeeId <= 0) {
            $errors[] = 'Invalid employee.';
        }
        if ($adjustmentType === '') {
            $errors[] = 'Adjustment basis is required.';
        }
        if ($newSalary <= 0) {
            $errors[] = 'New salary must be greater than zero.';
        }
        if ($effectiveDate === '') {
            $errors[] = 'Effective date is required.';
        }
        if ($newSalary <= $previousSalary && $adjustmentType !== 'Demotion') {
            $errors[] = 'New salary should be higher than previous salary for this adjustment type.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT negotiated_salary
                FROM em_employees
                WHERE employee_id = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => $employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->db->rollBack();
                return ['success' => false, 'errors' => ['Employee not found.']];
            }

            $currentSalary = (float) ($employee['negotiated_salary'] ?? 0);

            if ($currentSalary !== $previousSalary) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => [
                        "The employee's current salary has changed since this page was loaded.",
                        "Expected: ₱" . number_format($previousSalary, 2),
                        "Current: ₱" . number_format($currentSalary, 2),
                        "Please refresh the employee information before applying this adjustment."
                    ]
                ];
            }

            $stmt = $this->db->prepare("
                INSERT INTO lc_salary_adjustments
                    (employee_id, contract_id, adjustment_type, reason,
                     previous_salary, new_salary, effective_date,
                     application_date, status,
                     applied_by, created_by, created_by_role,
                     created_at, updated_at)
                VALUES
                    (:employee_id, :contract_id, :adjustment_type, :reason,
                     :previous_salary, :new_salary, :effective_date,
                     :application_date, 'Applied',
                     :applied_by, :applied_by, 'hr',
                     NOW(), NOW())
            ");
            $stmt->execute([
                ':employee_id'     => $employeeId,
                ':contract_id'     => $contractId,
                ':adjustment_type' => $adjustmentType,
                ':reason'          => $reason ?: null,
                ':previous_salary' => $previousSalary,
                ':new_salary'      => $newSalary,
                ':effective_date'  => $effectiveDate,
                ':application_date' => $applicationDate,
                ':applied_by'      => $appliedBy,
            ]);

            $adjustmentId = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :new_salary, updated_at = NOW()
                WHERE employee_id = :employee_id
            ");
            $stmt->execute([
                ':new_salary' => $newSalary,
                ':employee_id' => $employeeId,
            ]);

            $this->writeAuditTrail(
                'lc_salary_adjustments',
                $adjustmentId,
                'INSERT',
                'hr',
                "Salary adjustment #{$adjustmentId} applied for employee #{$employeeId}. Salary updated from ₱" . number_format($previousSalary, 2) . " to ₱" . number_format($newSalary, 2) . " via {$adjustmentType}"
            );

            $this->writeAuditTrail(
                'em_employees',
                $employeeId,
                'UPDATE',
                'hr',
                "Employee #{$employeeId} negotiated_salary updated from ₱" . number_format($previousSalary, 2) . " to ₱" . number_format($newSalary, 2) . " via salary adjustment #{$adjustmentId}"
            );

            $this->db->commit();

            return [
                'success' => true,
                'adjustment_id' => $adjustmentId,
                'message' => 'Salary adjustment applied successfully. Employee salary updated to ₱' . number_format($newSalary, 2) . '.'
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('SalaryAdjustmentModel::applyDirectAdjustment error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to apply salary adjustment: ' . $e->getMessage()]];
        }
    }

    public function getStatusCounts(): array
    {
        try {
            $sql = "
                SELECT status, COUNT(*) AS cnt
                FROM lc_salary_adjustments
                GROUP BY status
            ";
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return [
                'Draft'     => (int) ($rows['Draft'] ?? 0),
                'Pending'   => (int) ($rows['Pending'] ?? 0),
                'Approved'  => (int) ($rows['Approved'] ?? 0),
                'Applied'   => (int) ($rows['Applied'] ?? 0),
                'Rejected'  => (int) ($rows['Rejected'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['Draft' => 0, 'Pending' => 0, 'Approved' => 0, 'Applied' => 0, 'Rejected' => 0];
        }
    }

    private function writeAuditTrail(string $tableName, int $recordId, string $action, string $userType, string $description): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO lc_audit_trail (table_name, record_id, action, user_type, description, created_at)
                VALUES (:table_name, :record_id, :action, :user_type, :description, NOW())
            ");
            $stmt->execute([
                ':table_name' => $tableName,
                ':record_id' => $recordId,
                ':action' => $action,
                ':user_type' => $userType,
                ':description' => $description,
            ]);
        } catch (Throwable $e) {
            error_log('SalaryAdjustmentModel::writeAuditTrail error: ' . $e->getMessage());
        }
    }
}
