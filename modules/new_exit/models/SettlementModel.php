<?php

require_once 'ExitManagementModel.php';

class SettlementModel extends ExitManagementModel
{
    protected ?array $settlementTableColumns = null;

    /**
     * Get settlement table columns once and cache them.
     */
    protected function getSettlementTableColumns(): array
    {
        if ($this->settlementTableColumns !== null) {
            return $this->settlementTableColumns;
        }

        $stmt = $this->db->prepare("SHOW COLUMNS FROM exit_employee_settlements");
        $stmt->execute();
        $this->settlementTableColumns = array_map(static fn(array $column): string => $column['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        return $this->settlementTableColumns;
    }

    protected function settlementColumnExists(string $columnName): bool
    {
        return in_array($columnName, $this->getSettlementTableColumns(), true);
    }

    protected function appendOptionalSettlementField(array &$columns, array &$values, array $data, string $field, $default = 0): void
    {
        if ($this->settlementColumnExists($field)) {
            $columns[] = $field;
            $values[] = $data[$field] ?? $default;
        }
    }

    protected function appendOptionalSettlementFieldUpdate(array &$fields, array &$values, array $data, string $field, $default = 0): void
    {
        if ($this->settlementColumnExists($field)) {
            $fields[] = "{$field} = ?";
            $values[] = $data[$field] ?? $default;
        }
    }

    /**
     * Create a settlement record
     */
    public function createSettlement(array $data): int
    {
        $hasPaymentDate = $this->settlementColumnExists('payment_date');
        $hasExitCaseType = $this->settlementColumnExists('exit_case_type');
        $hasExitCaseId = $this->settlementColumnExists('exit_case_id');

        $columns = ['employee_id'];
        $values = [$data['employee_id']];

        if ($this->settlementColumnExists('resignation_id')) {
            $columns[] = 'resignation_id';
            $values[] = $data['resignation_id'] ?? null;
        }

        if ($hasExitCaseType) {
            $columns[] = 'exit_case_type';
            $values[] = $data['exit_case_type'] ?? null;
        }

        if ($hasExitCaseId) {
            $columns[] = 'exit_case_id';
            $values[] = $data['exit_case_id'] ?? null;
        }

        $columns[] = 'basic_salary';
        $values[] = $data['basic_salary'];

        foreach ([
            'remaining_salary',
            'unused_leave_conversion',
            'overtime_pay',
            'holiday_pay',
            'bonuses',
            'commission',
            'hra',
            'conveyance',
            'lta',
            'medical_allowance',
            'other_allowances',
            'separation_pay',
            'tax',
            'sss',
            'philhealth',
            'pagibig',
            'cash_advance',
            'company_loan',
            'equipment_damage',
            'missing_assets',
            'late_deductions',
            'absence_deductions',
            'provident_fund',
            'gratuity',
            'notice_pay',
            'outstanding_loans',
            'other_deductions'
        ] as $field) {
            $this->appendOptionalSettlementField($columns, $values, $data, $field);
        }

        $columns[] = 'net_payable';
        $values[] = $data['net_payable'];
        $columns[] = 'settlement_date';
        $values[] = $data['settlement_date'];

        if ($hasPaymentDate) {
            $columns[] = 'payment_date';
            $values[] = $data['payment_date'] ?? null;
        }

        $columns[] = 'status';
        $columns[] = 'created_by';
        $values[] = $data['status'] ?? 'pending_approval';
        $values[] = $data['created_by'];

        $placeholderString = implode(', ', array_fill(0, count($columns), '?'));
        $columnString = implode(', ', $columns);

        $stmt = $this->db->prepare("INSERT INTO exit_employee_settlements ({$columnString}, created_at) VALUES ({$placeholderString}, NOW())");
        $stmt->execute($values);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update a settlement record
     */
    public function updateSettlement(int $settlementId, array $data): bool
    {
        $hasPaymentDate = $this->settlementColumnExists('payment_date');
        $hasExitCaseType = $this->settlementColumnExists('exit_case_type');
        $hasExitCaseId = $this->settlementColumnExists('exit_case_id');

        $fields = ['employee_id = ?'];
        $values = [$data['employee_id']];

        if ($this->settlementColumnExists('resignation_id')) {
            $fields[] = 'resignation_id = ?';
            $values[] = $data['resignation_id'] ?? null;
        }

        if ($hasExitCaseType) {
            $fields[] = 'exit_case_type = ?';
            $values[] = $data['exit_case_type'] ?? null;
        }

        if ($hasExitCaseId) {
            $fields[] = 'exit_case_id = ?';
            $values[] = $data['exit_case_id'] ?? null;
        }

        $fields[] = 'basic_salary = ?';
        $values[] = $data['basic_salary'] ?? 0;

        foreach ([
            'remaining_salary',
            'unused_leave_conversion',
            'overtime_pay',
            'holiday_pay',
            'bonuses',
            'commission',
            'hra',
            'conveyance',
            'lta',
            'medical_allowance',
            'other_allowances',
            'separation_pay',
            'tax',
            'sss',
            'philhealth',
            'pagibig',
            'cash_advance',
            'company_loan',
            'equipment_damage',
            'missing_assets',
            'late_deductions',
            'absence_deductions',
            'provident_fund',
            'gratuity',
            'notice_pay',
            'outstanding_loans',
            'other_deductions'
        ] as $field) {
            $this->appendOptionalSettlementFieldUpdate($fields, $values, $data, $field);
        }

        $fields[] = 'net_payable = ?';
        $values[] = $data['net_payable'] ?? 0;
        $fields[] = 'settlement_date = ?';
        $values[] = $data['settlement_date'] ?? null;

        if ($hasPaymentDate) {
            $fields[] = 'payment_date = ?';
            $values[] = $data['payment_date'] ?? null;
        }

        $fields[] = 'status = ?';
        $values[] = $data['status'] ?? 'pending_approval';
        if ($this->settlementColumnExists('updated_by')) {
            $fields[] = 'updated_by = ?';
            $values[] = $data['updated_by'] ?? null;
        }
        $fields[] = 'updated_at = NOW()';

        $fieldStr = implode(',\n                ', $fields);

        $stmt = $this->db->prepare(
            "UPDATE exit_employee_settlements
             SET {$fieldStr}
             WHERE id = ?"
        );

        $values[] = $settlementId;

        return $stmt->execute($values);
    }

    /**
     * Get settlement by ID
     */
    public function getSettlementById(int $settlementId): ?array
    {
        // Include resignation_type only if column exists in exit_resignations
        $resignationTypeSelect = $this->columnExists('exit_resignations', 'resignation_type') ? 'r.resignation_type' : 'NULL AS resignation_type';

        $stmt = $this->db->prepare("
             SELECT s.*, COALESCE(NULLIF(s.status, ''), 'pending_approval') AS status,
                 e.full_name, e.employee_id as emp_id,
                   " . $resignationTypeSelect . ", r.last_working_date
            FROM exit_employee_settlements s
            JOIN employees e ON s.employee_id = e.employee_id
            LEFT JOIN exit_resignations r ON s.resignation_id = r.id
            WHERE s.id = ?
        ");
        $stmt->execute([$settlementId]);
        $settlement = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($settlement && empty($settlement['exit_case_type']) && !empty($settlement['resignation_id'])) {
            $settlement['exit_case_type'] = 'resignation';
            $settlement['exit_case_id'] = $settlement['resignation_id'];
        }

        return $settlement;
    }

    /**
     * Get settlements by employee
     */
    public function getSettlementsByEmployee(string $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM exit_employee_settlements
            WHERE employee_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate gratuity
     */
    public function calculateGratuity(float $basicSalary, int $yearsOfService): float
    {
        // Gratuity = (Basic Salary × 15 × Number of years of service) / 26
        return ($basicSalary * 15 * $yearsOfService) / 26;
    }

    /**
     * Calculate provident fund
     */
    public function calculateProvidentFund(float $basicSalary, float $da = 0): float
    {
        // Employee contribution: 12% of (Basic + DA)
        return ($basicSalary + $da) * 0.12;
    }

    /**
     * Calculate notice pay
     */
    public function calculateNoticePay(float $basicSalary, int $noticeDays): float
    {
        // Notice pay = (Basic Salary / 30) × Number of notice days
        return ($basicSalary / 30) * $noticeDays;
    }

    /**
     * Check if a resignation already has a settlement
     */
    public function hasExistingSettlementForResignation(int $resignationId, ?int $excludeSettlementId = null): bool
    {
        return $this->hasExistingSettlementForExitCase('resignation', $resignationId, $excludeSettlementId);
    }

    public function hasExistingSettlementForExitCase(string $exitCaseType, int $exitCaseId, ?int $excludeSettlementId = null): bool
    {
        $hasExitCaseType = $this->columnExists('exit_employee_settlements', 'exit_case_type');
        $hasExitCaseId = $this->columnExists('exit_employee_settlements', 'exit_case_id');

        if ($hasExitCaseType && $hasExitCaseId) {
            $sql = "SELECT COUNT(*) FROM exit_employee_settlements WHERE exit_case_type = ? AND exit_case_id = ?";
            $params = [$exitCaseType, $exitCaseId];

            if ($excludeSettlementId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeSettlementId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }
        }

        if ($exitCaseType === 'resignation' && $this->columnExists('exit_employee_settlements', 'resignation_id')) {
            $sql = "SELECT COUNT(*) FROM exit_employee_settlements WHERE resignation_id = ?";
            $params = [$exitCaseId];

            if ($excludeSettlementId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeSettlementId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return (bool)$stmt->fetchColumn();
        }

        return false;
    }

    /**
     * Determine whether a settlement record is eligible for archiving
     */
    public function isSettlementArchivable(int $settlementId): bool
    {
        $stmt = $this->db->prepare("SELECT status FROM exit_employee_settlements WHERE id = ?");
        $stmt->execute([$settlementId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return false;
        }

        return in_array($status, ['approved', 'paid', 'rejected'], true);
    }

    /**
     * Update settlement status
     */
    public function updateSettlementStatus(int $settlementId, string $status, ?string $approvedBy = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE exit_employee_settlements
            SET status = ?, approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $approvedBy, $settlementId]);
    }

    /**
     * Get pending settlements
     */
    public function getPendingSettlements(): array
    {
        $stmt = $this->db->query("
            SELECT s.*, e.full_name, e.employee_id as emp_id
            FROM exit_employee_settlements s
            JOIN employees e ON s.employee_id = e.employee_id
            WHERE s.status = 'pending_approval'
            ORDER BY s.settlement_date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all settlements with optional status filter and pagination
     */
    public function getAllSettlements(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        // Build a schema-aware select list: include column if present, otherwise return NULL AS column
        $baseSelect = [
            's.id',
            's.employee_id',
            'e.full_name AS employee_name',
            'e.department',
            'e.position',
            's.resignation_id',
            'r.last_working_date',
            's.settlement_date'
        ];

        $settlementCols = [
            'payment_date', 'basic_salary', 'remaining_salary', 'unused_leave_conversion', 'overtime_pay', 'holiday_pay',
            'bonuses', 'commission', 'hra', 'conveyance', 'lta', 'medical_allowance', 'other_allowances', 'separation_pay',
            'tax', 'sss', 'philhealth', 'pagibig', 'cash_advance', 'company_loan', 'equipment_damage', 'missing_assets',
            'late_deductions', 'absence_deductions', 'provident_fund', 'gratuity', 'notice_pay', 'outstanding_loans',
            'other_deductions', 'net_payable'
        ];

        foreach ($settlementCols as $col) {
            if ($this->columnExists('exit_employee_settlements', $col)) {
                $baseSelect[] = "s.{$col}";
            } else {
                $baseSelect[] = "NULL AS {$col}";
            }
        }

        // Always include status and timestamps (may be present or will be NULL if missing)
        $baseSelect[] = "COALESCE(NULLIF(s.status, ''), 'pending_approval') AS status";
        $baseSelect[] = "s.created_at";
        $baseSelect[] = "s.updated_at";

        $selectList = implode(",\n                ", $baseSelect);

        $sql = "\n            SELECT\n                {$selectList}\n            FROM exit_employee_settlements s\n            JOIN employees e ON s.employee_id = e.employee_id\n            LEFT JOIN exit_resignations r ON s.resignation_id = r.id\n        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_employee_settlements s
            JOIN employees e ON s.employee_id = e.employee_id
            LEFT JOIN exit_resignations r ON s.resignation_id = r.id
        ";

        $params = [];
        $whereClause = "";

        if ($status && $status !== 'all') {
            $whereClause = " WHERE s.status = :status";
            $params['status'] = $status;
        }

        // Add search condition if provided
        if (!empty($search)) {
            $searchCondition = $whereClause ? " AND" : " WHERE";
            $searchCondition .= " (e.full_name LIKE :search0 OR s.settlement_date LIKE :search1 OR e.employee_id LIKE :search2 OR r.last_working_date LIKE :search3)";
            $whereClause .= $searchCondition;
            $searchParam = "%$search%";
            $params['search0'] = $searchParam;
            $params['search1'] = $searchParam;
            $params['search2'] = $searchParam;
            $params['search3'] = $searchParam;
        }

        // Get total count
        $countStmt = $this->db->prepare($countSql . $whereClause);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get paginated data
        $stmt = $this->db->prepare($sql . $whereClause . " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Calculate total settlement amount
     */
    public function calculateTotalSettlement(array $components): float
    {
        $earnings = ($components['basic_salary'] ?? 0) +
                   ($components['remaining_salary'] ?? 0) +
                   ($components['unused_leave_conversion'] ?? 0) +
                   ($components['overtime_pay'] ?? 0) +
                   ($components['holiday_pay'] ?? 0) +
                   ($components['bonuses'] ?? 0) +
                   ($components['commission'] ?? 0) +
                   ($components['hra'] ?? 0) +
                   ($components['conveyance'] ?? 0) +
                   ($components['lta'] ?? 0) +
                   ($components['medical_allowance'] ?? 0) +
                   ($components['other_allowances'] ?? 0) +
                   ($components['separation_pay'] ?? 0) +
                   ($components['gratuity'] ?? 0) +
                   ($components['notice_pay'] ?? 0);

        $deductions = ($components['tax'] ?? 0) +
                     ($components['sss'] ?? 0) +
                     ($components['philhealth'] ?? 0) +
                     ($components['pagibig'] ?? 0) +
                     ($components['cash_advance'] ?? 0) +
                     ($components['company_loan'] ?? 0) +
                     ($components['equipment_damage'] ?? 0) +
                     ($components['missing_assets'] ?? 0) +
                     ($components['late_deductions'] ?? 0) +
                     ($components['absence_deductions'] ?? 0) +
                     ($components['outstanding_loans'] ?? 0) +
                     ($components['other_deductions'] ?? 0) +
                     ($components['provident_fund'] ?? 0);

        return $earnings - $deductions;
    }

    /**
     * Get archived settlements from the archive table
     */
    public function getArchivedSettlements(int $page = 1, int $limit = 10, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $sql = "
            SELECT
                a.id AS archive_id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.id')) AS original_id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) AS employee_id,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.settlement_date')) AS settlement_date,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.net_payable')) AS net_payable,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) AS status,
                JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.created_at')) AS created_at,
                a.archived_at AS archived_at,
                a.archive_reason,
                e.full_name AS employee_name
            FROM exit_archive a
            LEFT JOIN employees e ON JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.employee_id')) = e.employee_id
            WHERE a.archive_type = 'settlement' AND a.restored = 0
        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_archive a
            WHERE a.archive_type = 'settlement' AND a.restored = 0
        ";

        $params = [];
        if (!empty($search)) {
            $sql .= " AND (e.full_name LIKE :search0 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.settlement_date')) LIKE :search1 OR a.archive_reason LIKE :search2 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) LIKE :search3)";
            $countSql .= " AND (e.full_name LIKE :search0 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.settlement_date')) LIKE :search1 OR a.archive_reason LIKE :search2 OR JSON_UNQUOTE(JSON_EXTRACT(a.archive_data, '$.status')) LIKE :search3)";
            $searchParam = "%$search%";
            $params['search0'] = $searchParam;
            $params['search1'] = $searchParam;
            $params['search2'] = $searchParam;
            $params['search3'] = $searchParam;
        }

        $sql .= " ORDER BY a.archived_at DESC LIMIT :limit OFFSET :offset";

        try {
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(':' . $key, $value);
            }
            $countStmt->execute();
            $totalCount = (int)($countStmt->fetchColumn() ?? 0);

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $limit > 0 ? (int)ceil($totalCount / $limit) : 0
            ];
        } catch (Exception $e) {
            error_log('getArchivedSettlements error: ' . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Archive settlement
     */
    public function archiveSettlement(int $settlementId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full settlement data
        $stmt = $this->db->prepare("SELECT * FROM exit_employee_settlements WHERE id = ?");
        $stmt->execute([$settlementId]);
        $settlement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$settlement) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Insert into exit_archive
            $archiveStmt = $this->db->prepare("
                INSERT INTO exit_archive (
                    archive_type, original_id, employee_id, title, description, content,
                    status, original_created_by, archived_by, archive_reason, archive_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $title = "Settlement - Employee " . ($settlement['employee_id'] ?? 'Unknown');
            $description = "Archived settlement record";
            $content = json_encode($settlement);
            $archivedBy = $_SESSION['employee_id'] ?? 1;

            $archiveStmt->execute([
                'settlement',
                $settlementId,
                $settlement['employee_id'],
                $title,
                $description,
                $content,
                $settlement['status'],
                $settlement['created_by'],
                $archivedBy,
                $archiveReason,
                $content
            ]);

            // Delete from exit_employee_settlements
            $deleteStmt = $this->db->prepare("DELETE FROM exit_employee_settlements WHERE id = ?");
            $deleteStmt->execute([$settlementId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Settlement archive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unarchive settlement
     */
    public function unarchiveSettlement(int $settlementId): bool
    {
        // Get archived data
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'settlement' AND original_id = ?");
        $stmt->execute([$settlementId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data
            $settlementData = json_decode($archive['archive_data'], true);
            if (!$settlementData) {
                return false;
            }

            // Insert back into exit_employee_settlements
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_employee_settlements (
                    id, employee_id, resignation_id, basic_salary, hra, conveyance, lta,
                    medical_allowance, other_allowances, provident_fund, gratuity,
                    notice_pay, outstanding_loans, other_deductions, net_payable,
                    settlement_date, status, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $settlementData['id'],
                $settlementData['employee_id'],
                $settlementData['resignation_id'],
                $settlementData['basic_salary'],
                $settlementData['hra'],
                $settlementData['conveyance'],
                $settlementData['lta'],
                $settlementData['medical_allowance'],
                $settlementData['other_allowances'],
                $settlementData['provident_fund'],
                $settlementData['gratuity'],
                $settlementData['notice_pay'],
                $settlementData['outstanding_loans'],
                $settlementData['other_deductions'],
                $settlementData['net_payable'],
                $settlementData['settlement_date'],
                $settlementData['status'] ?? 'pending_approval',
                $settlementData['created_by'],
                $settlementData['created_at'],
                date('Y-m-d H:i:s')
            ]);

            // Update archive record to mark as restored
            $updateStmt = $this->db->prepare("
                UPDATE exit_archive
                SET restored = 1, restored_by = ?, restored_at = NOW()
                WHERE id = ?
            ");
            $restoredBy = $_SESSION['employee_id'] ?? 1;
            $updateStmt->execute([$restoredBy, $archive['id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Settlement unarchive error: " . $e->getMessage());
            return false;
        }
    }
}