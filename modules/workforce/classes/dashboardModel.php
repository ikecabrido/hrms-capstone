<?php

class DashboardModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* ============================================================
       SAFE DATABASE HELPERS
       ============================================================ */

    private function safePrepare(
        string $sql,
        array $params = []
    ): ?PDOStatement {

        try {

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {

            return null;
        }
    }


    private function safeQuery(
        string $sql
    ): ?PDOStatement {

        try {

            return $this->db->query($sql);
        } catch (PDOException $e) {

            return null;
        }
    }


    /* ============================================================
       EMPLOYEES
       ============================================================ */

    /**
     * Get total number of active employees.
     */
    public function getActiveEmployeeCount(): int
    {
        $stmt = $this->safeQuery("
            SELECT COUNT(*)
            FROM em_employees
            WHERE employment_status = 'Active'
              AND is_archived = 0
        ");

        if (!$stmt) {
            return 0;
        }

        return (int)$stmt->fetchColumn();
    }


    /**
     * Get employee distribution by department.
     *
     * Used by the Employee Distribution by Department chart.
     */
    public function getEmployeeDistributionByDepartment(): array
    {
        $stmt = $this->safeQuery("
            SELECT

                COALESCE(
                    d.department_name,
                    'Unassigned'
                ) AS department_name,

                COUNT(e.employee_id) AS employee_count

            FROM em_employees e

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE e.employment_status = 'Active'
              AND e.is_archived = 0

            GROUP BY
                d.department_id,
                d.department_name

            ORDER BY
                employee_count DESC,
                department_name ASC
        ");

        if (!$stmt) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['employee_count'] =
                (int)$row['employee_count'];
        }

        return $rows;
    }


    /**
     * Get active employees by employment type.
     */
    public function getEmployeeDistributionByEmploymentType(): array
    {
        $stmt = $this->safeQuery("
            SELECT

                COALESCE(
                    employment_type,
                    'Unspecified'
                ) AS employment_type,

                COUNT(*) AS employee_count

            FROM em_employees

            WHERE employment_status = 'Active'
              AND is_archived = 0

            GROUP BY employment_type

            ORDER BY employee_count DESC
        ");

        if (!$stmt) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['employee_count'] =
                (int)$row['employee_count'];
        }

        return $rows;
    }


    /* ============================================================
       PAYROLL PERIOD
       ============================================================ */

    /**
     * Get the latest open payroll period.
     */
    public function getActivePeriod(): ?array
    {
        $stmt = $this->safeQuery("
            SELECT

                period_id,
                period_name,
                start_date,
                end_date,
                pay_date,
                status

            FROM pr_periods

            WHERE status IN ('open', 'processing')

            ORDER BY
                start_date DESC

            LIMIT 1
        ");

        if (!$stmt) {
            return null;
        }

        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        return $period ?: null;
    }


    /**
     * Get latest payroll period regardless of status.
     */
    public function getLatestPeriod(): ?array
    {
        $stmt = $this->safeQuery("
            SELECT

                period_id,
                period_name,
                start_date,
                end_date,
                pay_date,
                status

            FROM pr_periods

            ORDER BY
                start_date DESC

            LIMIT 1
        ");

        if (!$stmt) {
            return null;
        }

        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        return $period ?: null;
    }


    /* ============================================================
       PAYROLL RUN
       ============================================================ */

    /**
     * Get the latest payroll run for a payroll period.
     */
    public function getCurrentRun(
        int $periodId
    ): ?array {

        $stmt = $this->safePrepare("
            SELECT

                run_id,
                period_id,
                processed_at,
                finalized_by,
                status

            FROM pr_runs

            WHERE period_id = :period_id

            ORDER BY
                run_id DESC

            LIMIT 1
        ", [
            ':period_id' => $periodId
        ]);

        if (!$stmt) {
            return null;
        }

        $run = $stmt->fetch(PDO::FETCH_ASSOC);

        return $run ?: null;
    }


    /**
     * Count payroll runs still in draft status.
     */
    public function getPendingRunCount(): int
    {
        $stmt = $this->safeQuery("
            SELECT COUNT(*)
            FROM pr_runs
            WHERE status = 'draft'
        ");

        if (!$stmt) {
            return 0;
        }

        return (int)$stmt->fetchColumn();
    }


    /**
     * Get count of finalized payroll runs.
     */
    public function getFinalizedRunCount(): int
    {
        $stmt = $this->safeQuery("
            SELECT COUNT(*)
            FROM pr_runs
            WHERE status = 'finalized'
        ");

        if (!$stmt) {
            return 0;
        }

        return (int)$stmt->fetchColumn();
    }


    /* ============================================================
       PAYROLL PROCESSING PROGRESS
       ============================================================ */

    /**
     * Get payroll processing progress.
     */
    public function getRunProgress(
        int $runId
    ): array {

        $stmt = $this->safePrepare("
            SELECT

                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN net_pay > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS processed,

                SUM(
                    CASE
                        WHEN net_pay <= 0
                        THEN 1
                        ELSE 0
                    END
                ) AS pending

            FROM pr_payslips

            WHERE run_id = :run_id
        ", [
            ':run_id' => $runId
        ]);

        if (!$stmt) {

            return [
                'total' => 0,
                'processed' => 0,
                'pending' => 0
            ];
        }

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [

            'total' =>
            (int)($result['total'] ?? 0),

            'processed' =>
            (int)($result['processed'] ?? 0),

            'pending' =>
            (int)($result['pending'] ?? 0)
        ];
    }


    /* ============================================================
       PAYROLL TOTALS
       ============================================================ */

    /**
     * Get totals for a payroll run.
     */
    public function getRunTotals(
        int $runId
    ): array {

        $stmt = $this->safePrepare("
            SELECT

                COALESCE(
                    SUM(gross_pay),
                    0
                ) AS gross_pay,

                COALESCE(
                    SUM(total_deductions),
                    0
                ) AS deductions,

                COALESCE(
                    SUM(net_pay),
                    0
                ) AS net_pay

            FROM pr_payslips

            WHERE run_id = :run_id
        ", [
            ':run_id' => $runId
        ]);

        if (!$stmt) {

            return [
                'gross_pay' => 0,
                'deductions' => 0,
                'net_pay' => 0
            ];
        }

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [

            'gross_pay' =>
            (float)($result['gross_pay'] ?? 0),

            'deductions' =>
            (float)($result['deductions'] ?? 0),

            'net_pay' =>
            (float)($result['net_pay'] ?? 0)
        ];
    }


    /**
     * Get the latest finalized payroll.
     */
    public function getLatestFinalizedPayroll(): array
    {
        $stmt = $this->safeQuery("
            SELECT

                pr.run_id,

                pp.period_id,
                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,

                COUNT(ps.payslip_id)
                    AS employee_count,

                COALESCE(
                    SUM(ps.gross_pay),
                    0
                ) AS gross_pay,

                COALESCE(
                    SUM(ps.total_deductions),
                    0
                ) AS deductions,

                COALESCE(
                    SUM(ps.net_pay),
                    0
                ) AS net_pay

            FROM pr_runs pr

            INNER JOIN pr_periods pp
                ON pp.period_id = pr.period_id

            INNER JOIN pr_payslips ps
                ON ps.run_id = pr.run_id

            WHERE pr.status = 'finalized'

              AND pr.run_id = (
                    SELECT run_id
                    FROM pr_runs
                    WHERE status = 'finalized'
                    ORDER BY run_id DESC
                    LIMIT 1
              )

            GROUP BY

                pr.run_id,
                pp.period_id,
                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date
        ");

        if (!$stmt) {
            return [];
        }

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [];
        }

        return [

            'run_id' =>
            (int)$result['run_id'],

            'period_id' =>
            (int)$result['period_id'],

            'period_name' =>
            $result['period_name'],

            'start_date' =>
            $result['start_date'],

            'end_date' =>
            $result['end_date'],

            'pay_date' =>
            $result['pay_date'],

            'employee_count' =>
            (int)$result['employee_count'],

            'gross_pay' =>
            (float)$result['gross_pay'],

            'deductions' =>
            (float)$result['deductions'],

            'net_pay' =>
            (float)$result['net_pay']
        ];
    }


    /* ============================================================
       GRAPH 1
       PAYROLL TREND
       ============================================================ */

    /**
     * Get payroll totals for the latest 12 months.
     *
     * Includes only finalized payroll runs.
     *
     * Chart:
     * - Gross Pay
     * - Deductions
     * - Net Pay
     */
    public function getPayrollTrend(): array
    {
        $stmt = $this->safeQuery("
            SELECT

                DATE_FORMAT(
                    pp.start_date,
                    '%Y-%m'
                ) AS month,

                DATE_FORMAT(
                    pp.start_date,
                    '%b %Y'
                ) AS month_label,

                COALESCE(
                    SUM(ps.gross_pay),
                    0
                ) AS gross_pay,

                COALESCE(
                    SUM(ps.total_deductions),
                    0
                ) AS deductions,

                COALESCE(
                    SUM(ps.net_pay),
                    0
                ) AS net_pay

            FROM pr_periods pp

            INNER JOIN pr_runs pr
                ON pr.period_id = pp.period_id

            INNER JOIN pr_payslips ps
                ON ps.run_id = pr.run_id

            WHERE pr.status = 'finalized'

            GROUP BY
                DATE_FORMAT(
                    pp.start_date,
                    '%Y-%m'
                )

            ORDER BY month DESC

            LIMIT 12
        ");

        if (!$stmt) {
            return [];
        }

        $rows =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = array_reverse($rows);

        foreach ($rows as &$row) {

            $row['gross_pay'] =
                (float)$row['gross_pay'];

            $row['deductions'] =
                (float)$row['deductions'];

            $row['net_pay'] =
                (float)$row['net_pay'];
        }

        return $rows;
    }


    /* ============================================================
       GRAPH 2
       PAYROLL COMPOSITION
       ============================================================ */

    /**
     * Get gross pay, deductions and net pay
     * from the latest finalized payroll.
     */
    public function getPayrollComposition(): array
    {
        $latest =
            $this->getLatestFinalizedPayroll();

        if (!$latest) {

            return [
                'gross_pay' => 0,
                'deductions' => 0,
                'net_pay' => 0
            ];
        }

        return [

            'gross_pay' =>
            $latest['gross_pay'],

            'deductions' =>
            $latest['deductions'],

            'net_pay' =>
            $latest['net_pay']
        ];
    }


    /* ============================================================
       GRAPH 3
       DEDUCTION BREAKDOWN
       ============================================================ */

    /**
     * Get deduction breakdown from the latest finalized payroll.
     *
     * Uses pr_payslip_items because the payslip table only stores
     * the total deductions.
     *
     * Categories:
     * - SSS
     * - PhilHealth
     * - Pag-IBIG
     * - Withholding Tax
     * - Loans
     * - Other
     */
    public function getDeductionBreakdown(): array
    {
        $latest =
            $this->getLatestFinalizedPayroll();

        if (!$latest) {
            return [];
        }

        $runId =
            (int)$latest['run_id'];

        $stmt = $this->safePrepare("
            SELECT

                CASE

                    WHEN LOWER(description)
                        LIKE '%sss%'
                    THEN 'SSS'

                    WHEN LOWER(description)
                        LIKE '%philhealth%'
                    THEN 'PhilHealth'

                    WHEN LOWER(description)
                        LIKE '%pag-ibig%'
                         OR LOWER(description)
                            LIKE '%pagibig%'
                    THEN 'Pag-IBIG'

                    WHEN LOWER(description)
                        LIKE '%withholding tax%'
                         OR LOWER(description)
                            LIKE '%tax%'
                    THEN 'Withholding Tax'

                    WHEN LOWER(description)
                        LIKE '%loan%'
                         OR LOWER(description)
                            LIKE '%advance%'
                    THEN 'Loans'

                    ELSE 'Other'

                END AS deduction_category,

                COALESCE(
                    SUM(psi.amount),
                    0
                ) AS total_amount

            FROM pr_payslip_items psi

            INNER JOIN pr_payslips ps
                ON ps.payslip_id = psi.payslip_id

            WHERE ps.run_id = :run_id

              AND psi.item_type = 'deduction'

            GROUP BY deduction_category

            ORDER BY total_amount DESC
        ", [
            ':run_id' => $runId
        ]);

        if (!$stmt) {
            return [];
        }

        $rows =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['total_amount'] =
                (float)$row['total_amount'];
        }

        return $rows;
    }


    /* ============================================================
       GRAPH 4
       PAYROLL COST BY DEPARTMENT
       ============================================================ */

    /**
     * Get latest finalized payroll cost by department.
     *
     * Uses net pay as the primary payroll cost shown to the user.
     */
    public function getPayrollByDepartment(): array
    {
        $latest =
            $this->getLatestFinalizedPayroll();

        if (!$latest) {
            return [];
        }

        $runId =
            (int)$latest['run_id'];

        $stmt = $this->safePrepare("
            SELECT

                COALESCE(
                    d.department_name,
                    'Unassigned'
                ) AS department_name,

                COUNT(ps.payslip_id)
                    AS employee_count,

                COALESCE(
                    SUM(ps.gross_pay),
                    0
                ) AS gross_pay,

                COALESCE(
                    SUM(ps.total_deductions),
                    0
                ) AS deductions,

                COALESCE(
                    SUM(ps.net_pay),
                    0
                ) AS net_pay

            FROM pr_payslips ps

            INNER JOIN em_employees e
                ON e.employee_id = ps.employee_id

            LEFT JOIN em_departments d
                ON d.department_id = e.department_id

            WHERE ps.run_id = :run_id

            GROUP BY

                d.department_id,
                d.department_name

            ORDER BY
                net_pay DESC
        ", [
            ':run_id' => $runId
        ]);

        if (!$stmt) {
            return [];
        }

        $rows =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['employee_count'] =
                (int)$row['employee_count'];

            $row['gross_pay'] =
                (float)$row['gross_pay'];

            $row['deductions'] =
                (float)$row['deductions'];

            $row['net_pay'] =
                (float)$row['net_pay'];
        }

        return $rows;
    }


    /* ============================================================
       MANUAL ADJUSTMENTS
       ============================================================ */

    /**
     * Get manual deduction totals.
     *
     * Current schema only supports deduction adjustments.
     */
    public function getAdjustmentTotals(
        ?int $periodId = null
    ): array {

        $sql = "
            SELECT

                COALESCE(
                    SUM(amount),
                    0
                ) AS deductions

            FROM pr_employee_adjustments

            WHERE type = 'deduction'
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                AND period_id = :period_id
            ";

            $params[':period_id'] =
                $periodId;
        }

        $stmt =
            $this->safePrepare(
                $sql,
                $params
            );

        if (!$stmt) {

            return [
                'allowances' => 0,
                'deductions' => 0
            ];
        }

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [

            /*
             * There is currently no allowance
             * type in the new schema.
             */
            'allowances' => 0,

            'deductions' =>
            (float)($result['deductions'] ?? 0)
        ];
    }


    /* ============================================================
       AVERAGE NET PAY
       ============================================================ */

    /**
     * Average net pay from latest finalized payroll.
     */
    public function getAverageNetPay(): float
    {
        $stmt = $this->safeQuery("
            SELECT

                COALESCE(
                    AVG(ps.net_pay),
                    0
                )

            FROM pr_payslips ps

            INNER JOIN pr_runs pr
                ON pr.run_id = ps.run_id

            WHERE pr.status = 'finalized'

              AND pr.run_id = (
                    SELECT run_id
                    FROM pr_runs
                    WHERE status = 'finalized'
                    ORDER BY run_id DESC
                    LIMIT 1
              )
        ");

        if (!$stmt) {
            return 0;
        }

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       RECENT PAYROLL RUNS
       ============================================================ */

    /**
     * Get recent finalized payroll runs.
     */
    public function getRecentPayrollRuns(
        int $limit = 5
    ): array {

        $limit =
            max(
                1,
                min($limit, 10)
            );

        $sql = "
            SELECT

                pr.run_id,

                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,

                COUNT(ps.payslip_id)
                    AS employee_count,

                COALESCE(
                    SUM(ps.gross_pay),
                    0
                ) AS gross_pay,

                COALESCE(
                    SUM(ps.total_deductions),
                    0
                ) AS deductions,

                COALESCE(
                    SUM(ps.net_pay),
                    0
                ) AS net_pay,

                pr.status

            FROM pr_runs pr

            INNER JOIN pr_periods pp
                ON pp.period_id = pr.period_id

            LEFT JOIN pr_payslips ps
                ON ps.run_id = pr.run_id

            WHERE pr.status = 'finalized'

            GROUP BY

                pr.run_id,
                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.pay_date,
                pr.status

            ORDER BY

                pp.start_date DESC,
                pr.run_id DESC

            LIMIT {$limit}
        ";

        $stmt =
            $this->safeQuery($sql);

        if (!$stmt) {
            return [];
        }

        $rows =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['run_id'] =
                (int)$row['run_id'];

            $row['employee_count'] =
                (int)$row['employee_count'];

            $row['gross_pay'] =
                (float)$row['gross_pay'];

            $row['deductions'] =
                (float)$row['deductions'];

            $row['net_pay'] =
                (float)$row['net_pay'];
        }

        return $rows;
    }


    /* ============================================================
       LIFETIME PAYROLL
       ============================================================ */

    /**
     * Get lifetime finalized net payroll.
     */
    public function getLifetimeNetPayroll(): float
    {
        $stmt = $this->safeQuery("
            SELECT

                COALESCE(
                    SUM(ps.net_pay),
                    0
                )

            FROM pr_payslips ps

            INNER JOIN pr_runs pr
                ON pr.run_id = ps.run_id

            WHERE pr.status = 'finalized'
        ");

        if (!$stmt) {
            return 0;
        }

        return (float)$stmt->fetchColumn();
    }
}
