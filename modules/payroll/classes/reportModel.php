<?php

class ReportModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ============================================================
       ITEM CATEGORIES
       ============================================================
       Every payslip earning/deduction belongs to one category. New
       payslips store it in pr_payslip_items.category; rows without one
       (older payslips, or before the migration is run) are classified
       from their description, using the same rules as the migration's
       backfill, so old and new data report identically.
       ============================================================ */

    public const EARNING_CATEGORIES = [
        'basic'         => 'Basic Pay',
        'leave'         => 'Paid Leave',
        'holiday'       => 'Holiday Pay',
        'overload'      => 'Overload Pay',
        'allowance'     => 'Allowances',
        'other_earning' => 'Other Earnings',
    ];

    public const DEDUCTION_CATEGORIES = [
        'sss'        => 'SSS',
        'philhealth' => 'PhilHealth',
        'pagibig'    => 'Pag-IBIG',
        'tax'        => 'Withholding Tax',
        'late'       => 'Late',
        'absence'    => 'Absence',
        'adjustment' => 'Other Deductions',
    ];

    /**
     * Resolve the category of one payslip item.
     */
    public static function categorize(
        string $itemType,
        ?string $stored,
        string $description
    ): string {

        $isEarning = $itemType === 'earning';
        $valid = $isEarning
            ? self::EARNING_CATEGORIES
            : self::DEDUCTION_CATEGORIES;

        if ($stored !== null && isset($valid[$stored])) {
            return $stored;
        }

        $d = trim($description);

        if ($isEarning) {
            if (
                stripos($d, 'Daily Rate') === 0
                || stripos($d, 'Part-time Hours') === 0
                || stripos($d, 'Faculty Regular Teaching Pay') === 0
                || stripos($d, 'No scheduled teaching load') === 0
                || stripos($d, 'No SMS faculty') === 0
            ) {
                return 'basic';
            }
            if (
                stripos($d, 'Paid Leave') === 0
                || stripos($d, 'Faculty Regular Paid Leave') === 0
            ) {
                return 'leave';
            }
            if (
                stripos($d, 'Holiday Pay') === 0
                || stripos($d, 'Faculty Regular Holiday Pay') === 0
            ) {
                return 'holiday';
            }
            if (stripos($d, 'Faculty Overload Pay') === 0) {
                return 'overload';
            }
            if (stripos($d, 'OJT Training Allowance') === 0) {
                return 'allowance';
            }
            return 'other_earning';
        }

        if ($d === 'SSS') {
            return 'sss';
        }
        if ($d === 'PhilHealth') {
            return 'philhealth';
        }
        if ($d === 'Pag-IBIG') {
            return 'pagibig';
        }
        if ($d === 'Withholding Tax') {
            return 'tax';
        }
        if (stripos($d, 'Late (') === 0) {
            return 'late';
        }
        if (stripos($d, 'Unexcused Absence') === 0) {
            return 'absence';
        }
        return 'adjustment';
    }

    /**
     * Display label for a category key.
     */
    public static function categoryLabel(string $category): string
    {
        return self::EARNING_CATEGORIES[$category]
            ?? self::DEDUCTION_CATEGORIES[$category]
            ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Whether pr_payslip_items.category exists yet. Checked once.
     */
    private function itemsHaveCategoryColumn(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $stmt = $this->db->query(
                "SHOW COLUMNS FROM pr_payslip_items LIKE 'category'"
            );
            $cache = (bool)($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }

    /**
     * All payslip items of the finalized payroll for a period, each with
     * its resolved category.
     *
     * @return array<int, array{payslip_id:int,item_type:string,category:string,amount:float}>
     */
    private function getPeriodItems(int $periodId): array
    {
        $categoryCol = $this->itemsHaveCategoryColumn()
            ? 'i.category'
            : 'NULL';

        $stmt = $this->db->prepare("
            SELECT
                i.payslip_id,
                i.item_type,
                {$categoryCol} AS category,
                i.description,
                i.amount

            FROM pr_payslip_items i

            INNER JOIN pr_payslips p
                ON p.payslip_id = i.payslip_id

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = [
                'payslip_id' => (int)$row['payslip_id'],
                'item_type' => $row['item_type'],
                'category' => self::categorize(
                    (string)$row['item_type'],
                    $row['category'] !== null
                        ? (string)$row['category']
                        : null,
                    (string)($row['description'] ?? '')
                ),
                'amount' => (float)$row['amount']
            ];
        }

        return $items;
    }


    /* ============================================================
       PAYROLL PERIODS
       ============================================================ */

    /**
     * Get all payroll periods.
     */
    public function getPeriods(): array
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
            ORDER BY start_date DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get one payroll period.
     */
    public function getPeriodById(int $periodId): ?array
    {
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

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }


    /* ============================================================
       PAYROLL SUMMARY
       ============================================================ */

    /**
     * Get payroll summary for a finalized payroll period.
     *
     * Values come directly from pr_payslips.
     */
    public function getPayrollSummary(int $periodId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(p.payslip_id) AS total_employees,

                COALESCE(
                    SUM(p.gross_pay),
                    0
                ) AS total_gross,

                COALESCE(
                    SUM(p.total_deductions),
                    0
                ) AS total_deductions,

                COALESCE(
                    SUM(p.net_pay),
                    0
                ) AS total_net,

                COALESCE(
                    AVG(p.net_pay),
                    0
                ) AS average_net

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_employees' =>
            (int)($summary['total_employees'] ?? 0),

            'total_gross' =>
            (float)($summary['total_gross'] ?? 0),

            'total_deductions' =>
            (float)($summary['total_deductions'] ?? 0),

            'total_net' =>
            (float)($summary['total_net'] ?? 0),

            'average_net' =>
            (float)($summary['average_net'] ?? 0)
        ];
    }


    /* ============================================================
       PAYROLL REGISTER
       ============================================================ */

    /**
     * Get detailed payroll register for a finalized period.
     *
     * This is the main report.
     */
    public function getPayrollOverview(int $periodId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.payslip_id,

                e.employee_id,
                e.employee_code,

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

                pos.position_name,
                d.department_name,

                e.employment_type,

                p.gross_pay,
                p.total_deductions,
                p.net_pay,

                p.generated_at

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            LEFT JOIN em_positions pos
                ON pos.position_id = e.position_id

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'

            ORDER BY
                e.last_name ASC,
                e.first_name ASC,
                e.employee_id ASC
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Per-payslip totals by category, for the register's columns.
        $byPayslip = [];
        foreach ($this->getPeriodItems($periodId) as $item) {
            $pid = $item['payslip_id'];
            $cat = $item['category'];
            $byPayslip[$pid][$cat] =
                ($byPayslip[$pid][$cat] ?? 0.0) + $item['amount'];
        }

        foreach ($rows as &$row) {

            $cats = $byPayslip[(int)$row['payslip_id']] ?? [];
            $gross = (float)$row['gross_pay'];
            $totalDed = (float)$row['total_deductions'];

            $basic = round(
                ($cats['basic'] ?? 0)
                    + ($cats['leave'] ?? 0)
                    + ($cats['holiday'] ?? 0),
                2
            );
            $sss = round($cats['sss'] ?? 0, 2);
            $phic = round($cats['philhealth'] ?? 0, 2);
            $hdmf = round($cats['pagibig'] ?? 0, 2);
            $tax = round($cats['tax'] ?? 0, 2);
            $lateAbs = round(
                ($cats['late'] ?? 0) + ($cats['absence'] ?? 0),
                2
            );

            // The two "other" columns are what is left of the payslip's
            // own totals, so every row always adds up to its gross and
            // net even when a payslip has no itemised lines.
            $row['basic_pay'] = $basic;
            $row['other_earnings'] = round(
                max(0.0, $gross - $basic),
                2
            );
            $row['sss'] = $sss;
            $row['philhealth'] = $phic;
            $row['pagibig'] = $hdmf;
            $row['tax'] = $tax;
            $row['late_absence'] = $lateAbs;
            $row['other_deductions'] = round(
                max(0.0, $totalDed - ($sss + $phic + $hdmf + $tax + $lateAbs)),
                2
            );

            $row['payslip_id'] =
                (int)$row['payslip_id'];

            $row['employee_id'] =
                (int)$row['employee_id'];

            $row['gross_pay'] =
                (float)$row['gross_pay'];

            $row['total_deductions'] =
                (float)$row['total_deductions'];

            $row['net_pay'] =
                (float)$row['net_pay'];
        }

        return $rows;
    }


    /* ============================================================
       EMPLOYEE PAYROLL HISTORY
       ============================================================ */

    /**
     * Get payroll history for one employee.
     *
     * This can be used by the employee payroll history report.
     */
    public function getEmployeePayrollHistory(
        int $employeeId
    ): array {

        $stmt = $this->db->prepare("
            SELECT

                p.payslip_id,

                pp.period_id,
                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,

                p.gross_pay,
                p.total_deductions,
                p.net_pay,

                p.generated_at

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN pr_periods pp
                ON pp.period_id = r.period_id

            WHERE p.employee_id = :employee_id
              AND r.status = 'finalized'

            ORDER BY
                pp.start_date DESC,
                pp.period_id DESC
        ");

        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['payslip_id'] =
                (int)$row['payslip_id'];

            $row['period_id'] =
                (int)$row['period_id'];

            $row['gross_pay'] =
                (float)$row['gross_pay'];

            $row['total_deductions'] =
                (float)$row['total_deductions'];

            $row['net_pay'] =
                (float)$row['net_pay'];
        }

        return $rows;
    }


    /* ============================================================
       EMPLOYEE INFORMATION
       ============================================================ */

    /**
     * Get one employee.
     */
    public function getEmployeeById(
        int $employeeId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT

                e.employee_id,
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

                e.employment_type,
                e.employment_status,

                pos.position_name,
                d.department_name

            FROM em_employees e

            LEFT JOIN em_positions pos
                ON pos.position_id = e.position_id

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE e.employee_id = :employee_id
            LIMIT 1
        ");

        $stmt->execute([
            ':employee_id' => $employeeId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }


    /**
     * Search employees.
     *
     * Supports:
     *
     * EMP-000035
     * 35
     * 000035
     * employee name
     */
    public function searchEmployees(
        string $search = ''
    ): array {

        $search = trim($search);

        $sql = "
            SELECT

                e.employee_id,
                e.employee_code,

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

                pos.position_name,
                d.department_name

            FROM em_employees e

            LEFT JOIN em_positions pos
                ON pos.position_id = e.position_id

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE e.employment_status IN (
                'Active',
                'Probationary'
            )
        ";

        $params = [];

        if ($search !== '') {

            $sql .= "
                AND (
                    e.employee_code LIKE :search
                    OR e.employee_code LIKE :code_suffix
                    OR e.first_name LIKE :name_search
                    OR e.middle_name LIKE :name_search
                    OR e.last_name LIKE :name_search
                    OR CONCAT(
                        e.first_name,
                        ' ',
                        e.last_name
                    ) LIKE :full_name_search
                )
            ";

            $params[':search'] =
                '%' . $search . '%';

            /*
             * Allows:
             *
             * 35
             *
             * to find:
             *
             * EMP-000035
             */
            $params[':code_suffix'] =
                '%'
                . ltrim($search, '0')
                . '%';

            $params[':name_search'] =
                '%' . $search . '%';

            $params[':full_name_search'] =
                '%' . $search . '%';
        }

        $sql .= "
            ORDER BY
                e.last_name ASC,
                e.first_name ASC

            LIMIT 50
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       PAYSLIP BREAKDOWN
       ============================================================ */

    /**
     * Get earning and deduction breakdown
     * for a payslip.
     *
     * Uses pr_payslip_items.
     */
    public function getPayslipBreakdown(
        int $payslipId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                payslip_item_id,
                payslip_id,
                item_type,
                description,
                amount

            FROM pr_payslip_items

            WHERE payslip_id = :payslip_id

            ORDER BY
                item_type ASC,
                payslip_item_id ASC
        ");

        $stmt->execute([
            ':payslip_id' => $payslipId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['payslip_item_id'] =
                (int)$row['payslip_item_id'];

            $row['payslip_id'] =
                (int)$row['payslip_id'];

            $row['amount'] =
                (float)$row['amount'];
        }

        return $rows;
    }


    /* ============================================================
       PERIOD DEDUCTION / EARNING SUMMARY
       ============================================================ */

    /**
     * Get payroll earning and deduction breakdown
     * for a finalized period.
     *
     * Example:
     *
     * Basic Salary
     * Allowance
     * SSS
     * PhilHealth
     * Pag-IBIG
     * Tax
     * Loan
     */
    public function getPeriodItemSummary(
        int $periodId
    ): array {

        $items = $this->getPeriodItems($periodId);

        // [item_type][category] => totals
        $groups = [];

        foreach ($items as $item) {
            $type = $item['item_type'];
            $cat = $item['category'];

            if (!isset($groups[$type][$cat])) {
                $groups[$type][$cat] = [
                    'amount' => 0.0,
                    'item_count' => 0,
                    'payslips' => []
                ];
            }

            $groups[$type][$cat]['amount'] += $item['amount'];
            $groups[$type][$cat]['item_count']++;
            $groups[$type][$cat]['payslips'][$item['payslip_id']] = true;
        }

        $rows = [];

        $order = [
            'earning' => array_keys(self::EARNING_CATEGORIES),
            'deduction' => array_keys(self::DEDUCTION_CATEGORIES)
        ];

        foreach ($order as $type => $categories) {
            foreach ($categories as $cat) {
                if (!isset($groups[$type][$cat])) {
                    continue;
                }

                $g = $groups[$type][$cat];
                $label = self::categoryLabel($cat);

                $rows[] = [
                    'item_type' => $type,
                    'category' => $cat,
                    'label' => $label,
                    // Kept so older front-end code that reads
                    // "description" still shows something sensible.
                    'description' => $label,
                    'employee_count' => count($g['payslips']),
                    'item_count' => $g['item_count'],
                    'total_amount' => round($g['amount'], 2)
                ];
            }
        }

        return $rows;
    }


    /* ============================================================
       DEPARTMENT PAYROLL SUMMARY
       ============================================================ */

    /**
     * Get payroll totals grouped by department.
     */
    public function getDepartmentSummary(
        int $periodId
    ): array {

        $stmt = $this->db->prepare("
            SELECT

                d.department_id,
                d.department_name,

                COUNT(p.payslip_id)
                    AS total_employees,

                COALESCE(
                    SUM(p.gross_pay),
                    0
                ) AS total_gross,

                COALESCE(
                    SUM(p.total_deductions),
                    0
                ) AS total_deductions,

                COALESCE(
                    SUM(p.net_pay),
                    0
                ) AS total_net

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'

            GROUP BY
                d.department_id,
                d.department_name

            ORDER BY
                total_net DESC,
                d.department_name ASC
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['department_id'] =
                $row['department_id'] !== null
                ? (int)$row['department_id']
                : null;

            $row['total_employees'] =
                (int)$row['total_employees'];

            $row['total_gross'] =
                (float)$row['total_gross'];

            $row['total_deductions'] =
                (float)$row['total_deductions'];

            $row['total_net'] =
                (float)$row['total_net'];
        }

        return $rows;
    }


    /* ============================================================
       EMPLOYMENT TYPE SUMMARY
       ============================================================ */

    /**
     * Get payroll totals grouped by employment type
     * (Full-time, Part-time, OJT/Training, ...).
     */
    public function getEmploymentTypeSummary(
        int $periodId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                e.employment_type,

                COUNT(p.payslip_id)
                    AS total_employees,

                COALESCE(SUM(p.gross_pay), 0)
                    AS total_gross,

                COALESCE(SUM(p.total_deductions), 0)
                    AS total_deductions,

                COALESCE(SUM(p.net_pay), 0)
                    AS total_net

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'

            GROUP BY
                e.employment_type

            ORDER BY
                total_net DESC,
                e.employment_type ASC
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['employment_type'] =
                $row['employment_type'] ?: 'Unspecified';
            $row['total_employees'] = (int)$row['total_employees'];
            $row['total_gross'] = (float)$row['total_gross'];
            $row['total_deductions'] = (float)$row['total_deductions'];
            $row['total_net'] = (float)$row['total_net'];
        }
        unset($row);

        return $rows;
    }


    /* ============================================================
       VALIDATION / STATUS
       ============================================================ */

    /**
     * Check whether a period has a finalized payroll run.
     */
    public function hasFinalizedPayroll(
        int $periodId
    ): bool {

        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM pr_runs

            WHERE period_id = :period_id
              AND status = 'finalized'
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }


    /**
     * Get the latest finalized payroll run
     * for a period, including who finalized it.
     *
     * pr_runs.processed_at is stamped again by finalizeRun(), so for a
     * finalized run it is the finalization time.
     */
    public function getFinalizedRun(
        int $periodId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                r.run_id,
                r.period_id,
                r.processed_at,
                r.status,
                r.finalized_by,

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
                ) AS finalized_by_name

            FROM pr_runs r

            LEFT JOIN em_employees e
                ON e.employee_id = r.finalized_by

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'

            ORDER BY
                r.processed_at DESC,
                r.run_id DESC

            LIMIT 1
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['run_id'] =
            (int)$row['run_id'];

        $row['period_id'] =
            (int)$row['period_id'];

        $row['finalized_at'] =
            $row['processed_at'];

        if ($row['finalized_by'] !== null) {
            $row['finalized_by'] =
                (int)$row['finalized_by'];
        }

        if (
            $row['finalized_by_name'] === null
            || trim($row['finalized_by_name']) === ''
        ) {
            $row['finalized_by_name'] = null;
        }

        return $row;
    }
}
