<?php
class ReportModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Get payroll overview for a period (auto-generated table)
    public function getPayrollOverview(int $periodId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                e.employee_id AS employee_id,
                e.full_name AS employee_name,
                e.position AS position,
                e.employment_status AS employment_type,
                p.gross_pay, 
                p.total_deductions, 
                p.net_pay
            FROM pr_payslips p
            JOIN employees e ON p.employee_id = e.employee_id
            JOIN pr_runs pr ON pr.run_id = p.payroll_run_id
            WHERE pr.payroll_period_id = :pid
            ORDER BY e.full_name ASC
        ");
        $stmt->execute([':pid' => $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPeriods(): array
    {
        $stmt = $this->db->query("
            SELECT 
                period_id,
                period_name,
                start_date,
                end_date,
                status
            FROM pr_periods
            ORDER BY start_date DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPeriodById(int $periodId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM pr_periods
            WHERE period_id = ?
        ");

        $stmt->execute([$periodId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
    // Get payroll summary for a period
    public function getPayrollSummary(int $periodId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(p.payslip_id) AS total_employees,
                SUM(p.gross_pay) AS total_gross,
                SUM(p.total_deductions) AS total_deductions,
                SUM(p.net_pay) AS total_net,
                AVG(p.net_pay) AS average_net
            FROM pr_payslips p
            JOIN pr_runs pr ON pr.run_id = p.payroll_run_id
            WHERE pr.payroll_period_id = :pid
        ");

        $stmt->execute([':pid' => $periodId]);

        return $stmt->fetch() ?: [];
    }
}
