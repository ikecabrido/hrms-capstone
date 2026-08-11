<?php

class DashboardModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function safeQuery(string $sql)
    {
        try {
            return $this->db->query($sql);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function safePrepare(string $sql, array $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            return false;
        }
    }

    /* ================= EMPLOYEES ================= */

    public function getEmployeeCount()
    {
        return $this->db
            ->query("SELECT COUNT(*) FROM employees")
            ->fetchColumn();
    }


    /* ================= PERIOD ================= */

    public function getLatestPeriod()
    {
        $sql = "
            SELECT *
            FROM pr_periods
            ORDER BY start_date DESC
            LIMIT 1
        ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return null;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }


    /* ================= PAYROLL ================= */

    public function getTotalPayroll($runId)
    {
        $sql = "
            SELECT IFNULL(SUM(net_pay),0)
            FROM pr_payslips
            WHERE payroll_run_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$runId]);

        return $stmt->fetchColumn();
    }


    public function getPendingCount($runId)
    {
        $sql = "
            SELECT COUNT(*)
            FROM pr_payslips p
            JOIN pr_runs r ON p.payroll_run_id = r.run_id
            WHERE p.payroll_run_id = ?
            AND r.status = 'draft'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$runId]);

        return $stmt->fetchColumn() ?? 0;
    }


    public function getPaidCount($runId)
    {
        $sql = "
            SELECT COUNT(*)
            FROM pr_payslips p
            JOIN pr_runs r ON p.payroll_run_id = r.run_id
            WHERE p.payroll_run_id = ?
            AND r.status = 'finalized'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$runId]);

        return $stmt->fetchColumn() ?? 0;
    }


    /* ================= CHART ================= */

    public function getMonthlyTotals()
    {
        $sql = "
            SELECT 
                DATE_FORMAT(pp.start_date,'%Y-%m') AS month,
                SUM(ps.net_pay) AS total
            FROM pr_periods pp
            JOIN pr_runs pr ON pr.payroll_period_id = pp.id
            JOIN pr_payslips ps ON ps.payroll_run_id = pr.id
            WHERE pr.status != 'draft'
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return [];
        }

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ================= LIFETIME ================= */

    public function getLifetimePayroll()
    {
        return $this->db
            ->query("SELECT SUM(net_pay) FROM pr_payslips p JOIN pr_runs r ON p.payroll_run_id = r.run_id WHERE r.status='finalized'")
            ->fetchColumn() ?? 0;
    }


    /* ================= UTILITIES ================= */
    public function getActivePeriod()
    {
        $sql = "
        SELECT *
        FROM pr_periods
        WHERE status = 'open'
        LIMIT 1
    ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return null;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }
    public function getCurrentRun($periodId)
    {
        $sql = "
        SELECT *
        FROM pr_runs
        WHERE payroll_period_id = ?
        AND status IN ('draft','finalized')
        ORDER BY id DESC
        LIMIT 1
    ";

        $stmt = $this->safePrepare($sql, [$periodId]);
        if ($stmt === false) {
            return null;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getRunProgress($runId)
    {
        $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN p.net_pay > 0 THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN p.net_pay = 0 THEN 1 ELSE 0 END) AS pending
        FROM pr_payslips p
        WHERE p.payroll_run_id = ?
    ";

        $stmt = $this->safePrepare($sql, [$runId]);
        if ($stmt === false) {
            return ['total' => 0, 'processed' => 0, 'pending' => 0];
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getPendingRuns()
    {
        $sql = "
        SELECT COUNT(*) 
        FROM pr_runs
        WHERE status = 'draft'
    ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return 0;
        }

        return (int)$result->fetchColumn();
    }
    public function getLatestFinalizedRun()
    {
        $sql = "SELECT IFNULL(SUM(ps.net_pay),0) AS total
        FROM pr_runs pr
        JOIN pr_payslips ps ON ps.payroll_run_id = pr.run_id
        WHERE pr.status = 'finalized'
        AND pr.run_id = (
            SELECT run_id
            FROM pr_runs
            WHERE status = 'finalized'
            ORDER BY run_id DESC
            LIMIT 1
        )";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return 0;
        }

        return $result->fetchColumn() ?? 0;
    }

    public function getLatestFinalizedRunWithDetails()
    {
        $sql = "
            SELECT
                pr.run_id,
                pp.period_name,
                pp.start_date,
                pp.end_date,
                COUNT(p.payslip_id) as total_employees,
                SUM(CASE WHEN p.net_pay > 0 THEN 1 ELSE 0 END) AS processed,
                SUM(CASE WHEN p.net_pay = 0 THEN 1 ELSE 0 END) AS pending,
                SUM(p.net_pay) as total_payroll
            FROM pr_runs pr
            JOIN pr_periods pp ON pr.payroll_period_id = pp.period_id
            JOIN pr_payslips p ON p.payroll_run_id = pr.run_id
            WHERE pr.status = 'finalized'
            AND pr.run_id = (
                SELECT run_id
                FROM pr_runs
                WHERE status = 'finalized'
                ORDER BY run_id DESC
                LIMIT 1
            )
            GROUP BY pr.run_id, pp.period_name, pp.start_date, pp.end_date
        ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return null;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /* ================= ADDITIONAL STATS ================= */

    public function getAverageSalary()
    {
        $sql = "
            SELECT AVG(net_pay)
            FROM pr_payslips p
            JOIN pr_runs r ON p.payroll_run_id = r.id
            WHERE r.status = 'finalized'
        ";

        $result = $this->safeQuery($sql);
        if ($result === false) {
            return 0;
        }

        return $result->fetchColumn() ?? 0;
    }

    public function getTotalAllowances($periodId = null)
    {
        $sql = "
            SELECT SUM(amount)
            FROM pr_employee_adjustments
            WHERE type = 'allowance'
        ";

        $params = [];

        if ($periodId) {
            $sql .= " AND payroll_period_id = ?";
            $params[] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() ?? 0;
    }

    public function getTotalDeductions($periodId = null)
    {
        $sql = "
            SELECT SUM(amount)
            FROM pr_employee_adjustments
            WHERE type = 'deduction'
        ";

        $params = [];

        if ($periodId) {
            $sql .= " AND payroll_period_id = ?";
            $params[] = $periodId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() ?? 0;
    }
}
