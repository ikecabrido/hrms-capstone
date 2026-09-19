<?php

class PayslipModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ============================================================
       GET ALL PAYSLIPS
       ============================================================ */

    /**
     * Get finalized/generated payslips.
     *
     * Optional filters:
     * - periodId
     * - employeeId
     *
     * Schema:
     * pr_payslips
     * pr_runs
     * pr_periods
     * em_employees
     */
    public function getAll(
        ?int $periodId = null,
        ?int $employeeId = null
    ): array {

        $sql = "
            SELECT
                p.payslip_id,
                p.run_id,
                p.employee_id,

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

                r.run_id,
                r.period_id,
                r.status AS payroll_status,
                r.processed_at,

                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.status AS period_status,

                p.gross_pay,
                p.total_deductions,
                p.net_pay,
                p.generated_at,

                p.is_exit_settlement,
                p.settlement_id,
                p.resignation_id

            FROM pr_payslips p

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN pr_periods pp
                ON pp.period_id = r.period_id

            WHERE 1 = 1
        ";

        $params = [];

        /*
         * Filter by payroll period.
         */
        if ($periodId !== null) {
            $sql .= "
                AND r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        /*
         * Filter by employee.
         */
        if ($employeeId !== null) {
            $sql .= "
                AND p.employee_id = :employee_id
            ";

            $params[':employee_id'] = $employeeId;
        }

        /*
         * Most recent payroll period first,
         * then employee name.
         */
        $sql .= "
            ORDER BY
                pp.start_date DESC,
                e.last_name ASC,
                e.first_name ASC,
                p.payslip_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       GET SINGLE PAYSLIP
       ============================================================ */

    /**
     * Get one complete payslip.
     *
     * Includes:
     * - Employee information
     * - Payroll run
     * - Payroll period
     * - Gross pay
     * - Total deductions
     * - Net pay
     * - Earnings
     * - Deductions
     */
    public function getById(
        int $payslipId
    ): ?array {

        /*
         * --------------------------------------------------------
         * MAIN PAYSLIP
         * --------------------------------------------------------
         */

        $stmt = $this->db->prepare("
            SELECT
                p.payslip_id,
                p.run_id,
                p.employee_id,

                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.email,
                e.employment_type,
                e.employment_status,
                e.graduate_level,
                e.negotiated_salary,

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

                r.run_id,
                r.period_id,
                r.status AS payroll_status,
                r.processed_at,
                r.finalized_by,

                pp.period_name,
                pp.start_date,
                pp.end_date,
                pp.status AS period_status,

                p.gross_pay,
                p.total_deductions,
                p.net_pay,
                p.generated_at,

                p.is_exit_settlement,
                p.settlement_id,
                p.resignation_id

            FROM pr_payslips p

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            INNER JOIN pr_periods pp
                ON pp.period_id = r.period_id

            WHERE p.payslip_id = :payslip_id

            LIMIT 1
        ");

        $stmt->execute([
            ':payslip_id' => $payslipId
        ]);

        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payslip) {
            return null;
        }


        /*
         * --------------------------------------------------------
         * PAYSLIP ITEMS
         * --------------------------------------------------------
         */

        $itemStmt = $this->db->prepare("
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

        $itemStmt->execute([
            ':payslip_id' => $payslipId
        ]);

        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


        /*
         * --------------------------------------------------------
         * SEPARATE EARNINGS AND DEDUCTIONS
         * --------------------------------------------------------
         */

        $payslip['earnings'] = [];
        $payslip['deductions'] = [];

        foreach ($items as $item) {

            if ($item['item_type'] === 'earning') {

                $payslip['earnings'][] = $item;
            } elseif ($item['item_type'] === 'deduction') {

                $payslip['deductions'][] = $item;
            }
        }


        /*
         * --------------------------------------------------------
         * NORMALIZE NUMERIC VALUES
         * --------------------------------------------------------
         */

        $payslip['gross_pay'] =
            (float)$payslip['gross_pay'];

        $payslip['total_deductions'] =
            (float)$payslip['total_deductions'];

        $payslip['net_pay'] =
            (float)$payslip['net_pay'];

        $payslip['employee_id'] =
            (int)$payslip['employee_id'];

        $payslip['run_id'] =
            (int)$payslip['run_id'];

        $payslip['period_id'] =
            (int)$payslip['period_id'];


        return $payslip;
    }


    /* ============================================================
       GET PAYSLIP BY EMPLOYEE AND PERIOD
       ============================================================ */

    /**
     * Get a specific employee's payslip for a payroll period.
     *
     * This is useful for:
     * - Employee payslip page
     * - "View Payslip" button
     * - Employee self-service
     */
    public function getByEmployeeAndPeriod(
        int $employeeId,
        int $periodId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                p.payslip_id

            FROM pr_payslips p

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            WHERE p.employee_id = :employee_id
              AND r.period_id = :period_id

            ORDER BY p.payslip_id DESC

            LIMIT 1
        ");

        $stmt->execute([
            ':employee_id' => $employeeId,
            ':period_id' => $periodId
        ]);

        $payslipId = $stmt->fetchColumn();

        if ($payslipId === false) {
            return null;
        }

        return $this->getById((int)$payslipId);
    }


    /* ============================================================
       PAYSLIP COUNTS
       ============================================================ */

    /**
     * Get number of payslips.
     */
    public function getCount(
        ?int $periodId = null
    ): int {

        $sql = "
            SELECT COUNT(*)
            FROM pr_payslips p
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                INNER JOIN pr_runs r
                    ON r.run_id = p.run_id
                WHERE r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }


    /* ============================================================
       TOTAL GROSS PAY
       ============================================================ */

    public function getTotalGrossPay(
        ?int $periodId = null
    ): float {

        $sql = "
            SELECT
                COALESCE(SUM(p.gross_pay), 0)

            FROM pr_payslips p
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                INNER JOIN pr_runs r
                    ON r.run_id = p.run_id
                WHERE r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       TOTAL DEDUCTIONS
       ============================================================ */

    public function getTotalDeductions(
        ?int $periodId = null
    ): float {

        $sql = "
            SELECT
                COALESCE(SUM(p.total_deductions), 0)

            FROM pr_payslips p
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                INNER JOIN pr_runs r
                    ON r.run_id = p.run_id
                WHERE r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       TOTAL NET PAY
       ============================================================ */

    public function getTotalNetPay(
        ?int $periodId = null
    ): float {

        $sql = "
            SELECT
                COALESCE(SUM(p.net_pay), 0)

            FROM pr_payslips p
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                INNER JOIN pr_runs r
                    ON r.run_id = p.run_id
                WHERE r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }


    /* ============================================================
       PAYSLIP SUMMARY
       ============================================================ */

    /**
     * Get summary values for the payslip page.
     */
    public function getSummary(
        ?int $periodId = null
    ): array {

        $sql = "
            SELECT
                COUNT(*) AS payslip_count,

                COALESCE(
                    SUM(p.gross_pay),
                    0
                ) AS total_gross_pay,

                COALESCE(
                    SUM(p.total_deductions),
                    0
                ) AS total_deductions,

                COALESCE(
                    SUM(p.net_pay),
                    0
                ) AS total_net_pay

            FROM pr_payslips p
        ";

        $params = [];

        if ($periodId !== null) {

            $sql .= "
                INNER JOIN pr_runs r
                    ON r.run_id = p.run_id

                WHERE r.period_id = :period_id
            ";

            $params[':period_id'] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'payslip_count' =>
            (int)($summary['payslip_count'] ?? 0),

            'total_gross_pay' =>
            (float)($summary['total_gross_pay'] ?? 0),

            'total_deductions' =>
            (float)($summary['total_deductions'] ?? 0),

            'total_net_pay' =>
            (float)($summary['total_net_pay'] ?? 0)
        ];
    }
    public function getFilterEmployees(): array
    {

        $stmt = $this->db->query("
            SELECT DISTINCT
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
                ) AS employee_name

            FROM pr_payslips p

            INNER JOIN em_employees e
                ON e.employee_id = p.employee_id

            ORDER BY
                e.last_name ASC,
                e.first_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
