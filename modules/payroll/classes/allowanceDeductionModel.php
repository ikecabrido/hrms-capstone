<?php

class AllowanceDeductionModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ============================================================
       GET ALL EMPLOYEE DEDUCTION ADJUSTMENTS
       ============================================================ */

    /**
     * Get manually entered employee deduction adjustments.
     *
     * Optional filters:
     * - periodId
     * - employeeId
     * - deductionSubtype
     *
     * This uses pr_employee_adjustments.
     *
     * IMPORTANT:
     * This is NOT for statutory deductions such as:
     * - SSS
     * - PhilHealth
     * - Pag-IBIG
     * - Withholding Tax
     *
     * Those are calculated by the payroll processing logic.
     */
    public function getAll(
        ?int $periodId = null,
        ?int $employeeId = null,
        ?string $deductionSubtype = null
    ): array {

        $sql = "
            SELECT
                a.adjustment_id,
                a.employee_id,
                a.period_id,
                a.type,
                a.description,
                a.amount,
                a.created_at,
                a.file_path,
                a.deduction_subtype,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name,

                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,
                pp.status AS period_status

            FROM pr_employee_adjustments a

            INNER JOIN em_employees e
                ON e.employee_id = a.employee_id

            INNER JOIN pr_periods pp
                ON pp.period_id = a.period_id

            WHERE a.type = 'deduction'
        ";

        $params = [];

        /*
         * Filter by payroll period.
         */
        if ($periodId !== null) {
            $sql .= "
                AND a.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        /*
         * Filter by employee.
         */
        if ($employeeId !== null) {
            $sql .= "
                AND a.employee_id = :employee_id
            ";

            $params[':employee_id'] = $employeeId;
        }

        /*
         * Filter by subtype.
         *
         * Allowed database values:
         * - loans
         * - other
         */
        if ($deductionSubtype !== null) {

            if (!in_array(
                $deductionSubtype,
                ['loans', 'other'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid deduction subtype.'
                );
            }

            $sql .= "
                AND a.deduction_subtype = :deduction_subtype
            ";

            $params[':deduction_subtype'] =
                $deductionSubtype;
        }

        $sql .= "
            ORDER BY
                pp.start_date DESC,
                e.last_name ASC,
                e.first_name ASC,
                a.adjustment_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['adjustment_id'] = (int)$row['adjustment_id'];
            $row['employee_id'] = (int)$row['employee_id'];
            $row['period_id'] = (int)$row['period_id'];
            $row['amount'] = (float)$row['amount'];
        }

        return $rows;
    }


    /* ============================================================
       GET ONE ADJUSTMENT
       ============================================================ */

    /**
     * Get one employee deduction adjustment.
     */
    public function getById(
        int $adjustmentId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                a.adjustment_id,
                a.employee_id,
                a.period_id,
                a.type,
                a.description,
                a.amount,
                a.created_at,
                a.file_path,
                a.deduction_subtype,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,

                CONCAT(
                    e.first_name,
                    CASE
                        WHEN e.middle_name IS NOT NULL
                             AND e.middle_name <> ''
                        THEN CONCAT(' ', e.middle_name)
                        ELSE ''
                    END,
                    ' ',
                    e.last_name
                ) AS employee_name,

                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,
                pp.status AS period_status

            FROM pr_employee_adjustments a

            INNER JOIN em_employees e
                ON e.employee_id = a.employee_id

            INNER JOIN pr_periods pp
                ON pp.period_id = a.period_id

            WHERE a.adjustment_id = :adjustment_id
              AND a.type = 'deduction'

            LIMIT 1
        ");

        $stmt->execute([
            ':adjustment_id' => $adjustmentId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['adjustment_id'] = (int)$row['adjustment_id'];
        $row['employee_id'] = (int)$row['employee_id'];
        $row['period_id'] = (int)$row['period_id'];
        $row['amount'] = (float)$row['amount'];

        return $row;
    }


    /* ============================================================
       CREATE ADJUSTMENT
       ============================================================ */

    /**
     * Create a manual employee deduction adjustment.
     *
     * Examples:
     * - Employee Loan
     * - Uniform Replacement
     * - Equipment Charge
     * - Property Damage
     * - Other authorized employee deduction
     *
     * The deduction is tied directly to a payroll period.
     */
    public function create(
        int $employeeId,
        int $periodId,
        string $description,
        float $amount,
        string $deductionSubtype = 'other',
        ?string $filePath = null
    ): int {

        /*
         * Validate subtype.
         */
        if (!in_array(
            $deductionSubtype,
            ['loans', 'other'],
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid deduction subtype.'
            );
        }

        /*
         * Amount must be positive.
         */
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Deduction amount must be greater than zero.'
            );
        }

        /*
         * Description is required.
         */
        $description = trim($description);

        if ($description === '') {
            throw new InvalidArgumentException(
                'Deduction description is required.'
            );
        }

        /*
         * Verify employee exists.
         */
        $employeeStmt = $this->db->prepare("
            SELECT employee_id
            FROM em_employees
            WHERE employee_id = :employee_id
            LIMIT 1
        ");

        $employeeStmt->execute([
            ':employee_id' => $employeeId
        ]);

        if ($employeeStmt->fetchColumn() === false) {
            throw new InvalidArgumentException(
                'Employee not found.'
            );
        }

        /*
         * Verify payroll period exists.
         */
        $periodStmt = $this->db->prepare("
            SELECT period_id
            FROM pr_periods
            WHERE period_id = :period_id
            LIMIT 1
        ");

        $periodStmt->execute([
            ':period_id' => $periodId
        ]);

        if ($periodStmt->fetchColumn() === false) {
            throw new InvalidArgumentException(
                'Payroll period not found.'
            );
        }

        /*
         * Do not allow manual adjustments
         * to be added to a closed payroll period.
         */
        $periodStatusStmt = $this->db->prepare("
            SELECT status
            FROM pr_periods
            WHERE period_id = :period_id
            LIMIT 1
        ");

        $periodStatusStmt->execute([
            ':period_id' => $periodId
        ]);

        $periodStatus =
            $periodStatusStmt->fetchColumn();

        if ($periodStatus === 'closed') {
            throw new RuntimeException(
                'Cannot add a deduction to a closed payroll period.'
            );
        }

        /*
         * Insert adjustment.
         */
        $stmt = $this->db->prepare("
            INSERT INTO pr_employee_adjustments
            (
                employee_id,
                period_id,
                type,
                description,
                amount,
                file_path,
                deduction_subtype
            )
            VALUES
            (
                :employee_id,
                :period_id,
                'deduction',
                :description,
                :amount,
                :file_path,
                :deduction_subtype
            )
        ");

        $stmt->execute([
            ':employee_id' =>
            $employeeId,

            ':period_id' =>
            $periodId,

            ':description' =>
            $description,

            ':amount' =>
            $amount,

            ':file_path' =>
            $filePath,

            ':deduction_subtype' =>
            $deductionSubtype
        ]);

        return (int)$this->db->lastInsertId();
    }


    /* ============================================================
       UPDATE ADJUSTMENT
       ============================================================ */

    /**
     * Update an existing employee deduction adjustment.
     */
    public function update(
        int $adjustmentId,
        int $employeeId,
        int $periodId,
        string $description,
        float $amount,
        string $deductionSubtype = 'other',
        ?string $filePath = null
    ): bool {

        if (!in_array(
            $deductionSubtype,
            ['loans', 'other'],
            true
        )) {
            throw new InvalidArgumentException(
                'Invalid deduction subtype.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Deduction amount must be greater than zero.'
            );
        }

        $description = trim($description);

        if ($description === '') {
            throw new InvalidArgumentException(
                'Deduction description is required.'
            );
        }

        /*
         * Get existing adjustment and verify
         * its payroll period.
         */
        $existing = $this->getById($adjustmentId);

        if (!$existing) {
            throw new RuntimeException(
                'Deduction adjustment not found.'
            );
        }

        /*
         * Do not modify closed payroll periods.
         */
        $statusStmt = $this->db->prepare("
            SELECT status
            FROM pr_periods
            WHERE period_id = :period_id
            LIMIT 1
        ");

        $statusStmt->execute([
            ':period_id' => $existing['period_id']
        ]);

        $status = $statusStmt->fetchColumn();

        if ($status === 'closed') {
            throw new RuntimeException(
                'Cannot modify a deduction from a closed payroll period.'
            );
        }

        /*
         * If no new file was supplied,
         * preserve the existing file.
         */
        if ($filePath === null) {

            $stmt = $this->db->prepare("
                UPDATE pr_employee_adjustments
                SET
                    employee_id = :employee_id,
                    period_id = :period_id,
                    description = :description,
                    amount = :amount,
                    deduction_subtype = :deduction_subtype

                WHERE adjustment_id = :adjustment_id
                  AND type = 'deduction'
            ");

            return $stmt->execute([
                ':employee_id' =>
                $employeeId,

                ':period_id' =>
                $periodId,

                ':description' =>
                $description,

                ':amount' =>
                $amount,

                ':deduction_subtype' =>
                $deductionSubtype,

                ':adjustment_id' =>
                $adjustmentId
            ]);
        }

        /*
         * A new file was supplied.
         */
        $stmt = $this->db->prepare("
            UPDATE pr_employee_adjustments
            SET
                employee_id = :employee_id,
                period_id = :period_id,
                description = :description,
                amount = :amount,
                file_path = :file_path,
                deduction_subtype = :deduction_subtype

            WHERE adjustment_id = :adjustment_id
              AND type = 'deduction'
        ");

        return $stmt->execute([
            ':employee_id' =>
            $employeeId,

            ':period_id' =>
            $periodId,

            ':description' =>
            $description,

            ':amount' =>
            $amount,

            ':file_path' =>
            $filePath,

            ':deduction_subtype' =>
            $deductionSubtype,

            ':adjustment_id' =>
            $adjustmentId
        ]);
    }


    /* ============================================================
       DELETE ADJUSTMENT
       ============================================================ */

    /**
     * Delete a manual employee deduction adjustment.
     *
     * Closed payroll periods cannot be modified.
     */
    public function delete(
        int $adjustmentId
    ): bool {

        $existing = $this->getById($adjustmentId);

        if (!$existing) {
            throw new RuntimeException(
                'Deduction adjustment not found.'
            );
        }

        /*
         * Prevent modification after payroll closure.
         */
        $statusStmt = $this->db->prepare("
            SELECT status
            FROM pr_periods
            WHERE period_id = :period_id
            LIMIT 1
        ");

        $statusStmt->execute([
            ':period_id' =>
            $existing['period_id']
        ]);

        $status = $statusStmt->fetchColumn();

        if ($status === 'closed') {
            throw new RuntimeException(
                'Cannot delete a deduction from a closed payroll period.'
            );
        }

        $stmt = $this->db->prepare("
            DELETE FROM pr_employee_adjustments
            WHERE adjustment_id = :adjustment_id
              AND type = 'deduction'
        ");

        return $stmt->execute([
            ':adjustment_id' =>
            $adjustmentId
        ]);
    }


    /* ============================================================
       GET EMPLOYEES
       ============================================================ */

    /**
     * Get employees for the deduction form.
     */
    public function getEmployees(): array
    {
        $stmt = $this->db->query("
            SELECT
                employee_id,
                employee_code,
                first_name,
                middle_name,
                last_name,

                CONCAT(
                    first_name,
                    CASE
                        WHEN middle_name IS NOT NULL
                             AND middle_name <> ''
                        THEN CONCAT(' ', middle_name)
                        ELSE ''
                    END,
                    ' ',
                    last_name
                ) AS employee_name

            FROM em_employees

            WHERE employment_status = 'ACTIVE'

            ORDER BY
                last_name ASC,
                first_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       GET PAYROLL PERIODS
       ============================================================ */

    /**
     * Get payroll periods that can receive
     * manual deduction adjustments.
     *
     * Closed periods are excluded.
     */
    public function getOpenPeriods(): array
    {
        $stmt = $this->db->query("
            SELECT
                period_id,
                period_name,
                start_date,
                end_date,
                pay_date,
                status

            FROM pr_periods

            WHERE status <> 'closed'

            ORDER BY
                start_date DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       GET ONE PAYROLL PERIOD
       ============================================================ */

    public function getPeriod(
        int $periodId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                period_id,
                period_name,
                start_date,
                end_date,
                pay_date,
                status

            FROM pr_periods

            WHERE period_id = :period_id

            LIMIT 1
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        return $period ?: null;
    }


    /* ============================================================
       GET TOTAL ADJUSTMENTS
       ============================================================ */

    /**
     * Get total manual deductions for a payroll period.
     */
    public function getTotal(
        ?int $periodId = null,
        ?string $deductionSubtype = null
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(a.amount),
                    0
                )

            FROM pr_employee_adjustments a

            WHERE a.type = 'deduction'
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                AND a.period_id = :period_id
            ";

            $params[':period_id'] =
                $periodId;
        }

        if ($deductionSubtype !== null) {

            if (!in_array(
                $deductionSubtype,
                ['loans', 'other'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid deduction subtype.'
                );
            }

            $sql .= "
                AND a.deduction_subtype = :deduction_subtype
            ";

            $params[':deduction_subtype'] =
                $deductionSubtype;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       GET SUMMARY
       ============================================================ */

    /**
     * Get deduction adjustment summary.
     */
    public function getSummary(
        ?int $periodId = null
    ): array {

        $sql = "
            SELECT

                COUNT(*) AS adjustment_count,

                COALESCE(
                    SUM(a.amount),
                    0
                ) AS total_amount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN a.deduction_subtype = 'loans'
                            THEN a.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_loans,

                COALESCE(
                    SUM(
                        CASE
                            WHEN a.deduction_subtype = 'other'
                            THEN a.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_other

            FROM pr_employee_adjustments a

            WHERE a.type = 'deduction'
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                AND a.period_id = :period_id
            ";

            $params[':period_id'] =
                $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $summary =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'adjustment_count' =>
            (int)($summary['adjustment_count'] ?? 0),

            'total_amount' =>
            (float)($summary['total_amount'] ?? 0),

            'total_loans' =>
            (float)($summary['total_loans'] ?? 0),

            'total_other' =>
            (float)($summary['total_other'] ?? 0)
        ];
    }


    /* ============================================================
       GET EMPLOYEE ADJUSTMENTS FOR PERIOD
       ============================================================ */

    /**
     * Get all manual deductions for one employee
     * in one payroll period.
     *
     * This is useful for payroll calculation and
     * employee payroll breakdown.
     */
    public function getEmployeeAdjustments(
        int $employeeId,
        int $periodId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                adjustment_id,
                employee_id,
                period_id,
                type,
                description,
                amount,
                created_at,
                file_path,
                deduction_subtype

            FROM pr_employee_adjustments

            WHERE employee_id = :employee_id
              AND period_id = :period_id
              AND type = 'deduction'

            ORDER BY
                deduction_subtype ASC,
                adjustment_id ASC
        ");

        $stmt->execute([
            ':employee_id' =>
            $employeeId,

            ':period_id' =>
            $periodId
        ]);

        $rows =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['adjustment_id'] =
                (int)$row['adjustment_id'];

            $row['employee_id'] =
                (int)$row['employee_id'];

            $row['period_id'] =
                (int)$row['period_id'];

            $row['amount'] =
                (float)$row['amount'];
        }

        return $rows;
    }


    /* ============================================================
       GET EMPLOYEE ADJUSTMENT TOTAL
       ============================================================ */

    /**
     * Get total manual deductions for one employee
     * in one payroll period.
     *
     * This is the value that payroll processing can
     * incorporate into total deductions.
     */
    public function getEmployeeAdjustmentTotal(
        int $employeeId,
        int $periodId
    ): float {

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(
                    SUM(amount),
                    0
                )

            FROM pr_employee_adjustments

            WHERE employee_id = :employee_id
              AND period_id = :period_id
              AND type = 'deduction'
        ");

        $stmt->execute([
            ':employee_id' =>
            $employeeId,

            ':period_id' =>
            $periodId
        ]);

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       SEARCH EMPLOYEES
       ============================================================ */

    /**
     * Forgiving employee search for the Deductions filter bar.
     *
     * Supports:
     * - Full or partial employee code, with or without the
     *   "EMP-" prefix (e.g. "35", "000035", "EMP-000035")
     * - First name, last name, or full name (case-insensitive)
     *
     * Does not require the database schema to change; the
     * numeric employee-code suffix is derived from the existing
     * employee_code column at query time.
     */
    public function searchEmployees(
        string $search
    ): array {

        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $sql = "
            SELECT
                employee_id,
                employee_code,
                first_name,
                middle_name,
                last_name,

                CONCAT(
                    first_name,
                    CASE
                        WHEN middle_name IS NOT NULL
                             AND middle_name <> ''
                        THEN CONCAT(' ', middle_name)
                        ELSE ''
                    END,
                    ' ',
                    last_name
                ) AS employee_name

            FROM em_employees

            WHERE employment_status = 'ACTIVE'
              AND (
                    employee_code LIKE :like_search
                 OR first_name LIKE :like_search
                 OR last_name LIKE :like_search
                 OR CONCAT(first_name, ' ', last_name) LIKE :like_search
                 OR CAST(
                        SUBSTRING_INDEX(employee_code, '-', -1)
                        AS UNSIGNED
                    ) = :numeric_search
              )

            ORDER BY
                last_name ASC,
                first_name ASC

            LIMIT 25
        ";

        /*
         * Numeric suffix comparison (e.g. "35" -> EMP-000035).
         * When the search term isn't numeric, this simply
         * won't match anything via that branch.
         */
        $numericSearch = ctype_digit($search)
            ? (int)$search
            : -1;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':like_search' => '%' . $search . '%',
            ':numeric_search' => $numericSearch
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['employee_id'] = (int)$row['employee_id'];
        }

        return $rows;
    }


    /* ============================================================
       SEARCH PAYROLL PERIODS
       ============================================================ */

    /**
     * Forgiving payroll period search for the Deductions filter
     * bar. Unlike getOpenPeriods(), this includes closed periods
     * so that existing deduction records remain searchable.
     *
     * Supports matching against the period name, the start/end
     * dates, and the pay date.
     */
    public function searchPeriods(
        string $search
    ): array {

        $search = trim($search);

        if ($search === '') {
            return [];
        }

        /*
         * Build one searchable "haystack" per period out of the
         * period name plus several common date representations
         * (ISO, US slash format, and long month/day form), so
         * searches like "August", "August 1", "2026", and
         * "08/01/2026" all have a chance to match without
         * requiring the user to know the exact stored format.
         */
        $stmt = $this->db->prepare("
            SELECT
                period_id,
                period_name,
                start_date,
                end_date,
                pay_date,
                status

            FROM pr_periods

            WHERE CONCAT_WS(
                ' ',
                period_name,
                start_date,
                end_date,
                pay_date,
                DATE_FORMAT(start_date, '%m/%d/%Y'),
                DATE_FORMAT(end_date, '%m/%d/%Y'),
                DATE_FORMAT(pay_date, '%m/%d/%Y'),
                DATE_FORMAT(start_date, '%M %e, %Y'),
                DATE_FORMAT(end_date, '%M %e, %Y'),
                DATE_FORMAT(pay_date, '%M %e, %Y')
            ) LIKE :like_search

            ORDER BY
                start_date DESC

            LIMIT 25
        ");

        $stmt->execute([
            ':like_search' => '%' . $search . '%'
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['period_id'] = (int)$row['period_id'];
        }

        return $rows;
    }
}
