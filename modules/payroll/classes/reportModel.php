<?php

class ReportModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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

        foreach ($rows as &$row) {

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

        $stmt = $this->db->prepare("
            SELECT

                i.item_type,
                i.description,

                COUNT(i.payslip_item_id) AS item_count,

                COALESCE(
                    SUM(i.amount),
                    0
                ) AS total_amount

            FROM pr_payslip_items i

            INNER JOIN pr_payslips p
                ON p.payslip_id = i.payslip_id

            INNER JOIN pr_runs r
                ON r.run_id = p.run_id

            WHERE r.period_id = :period_id
              AND r.status = 'finalized'

            GROUP BY
                i.item_type,
                i.description

            ORDER BY
                i.item_type ASC,
                total_amount DESC,
                i.description ASC
        ");

        $stmt->execute([
            ':period_id' => $periodId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {

            $row['item_count'] =
                (int)$row['item_count'];

            $row['total_amount'] =
                (float)$row['total_amount'];
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
     * for a period.
     */
    public function getFinalizedRun(
        int $periodId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                run_id,
                period_id,
                processed_at,
                status,
                finalized_by

            FROM pr_runs

            WHERE period_id = :period_id
              AND status = 'finalized'

            ORDER BY
                processed_at DESC,
                run_id DESC

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

        if ($row['finalized_by'] !== null) {
            $row['finalized_by'] =
                (int)$row['finalized_by'];
        }

        return $row;
    }
}
