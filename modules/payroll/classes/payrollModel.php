<?php
class PayrollModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Get TA metrics for a period
    public function getTimeAttendanceMetrics($employeeId, $startDate, $endDate): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(regular_hours) AS total_regular_hours,
                SUM(overtime_hours) AS total_overtime_hours,
                SUM(late_minutes) AS total_late_minutes,
                SUM(early_out_minutes) AS total_early_out_minutes,
                SUM(total_hours_worked) AS total_hours_worked,
                COUNT(CASE WHEN status='PRESENT' THEN 1 END) AS present_days,
                COUNT(CASE WHEN status='ABSENT' THEN 1 END) AS absent_days,
                COUNT(CASE WHEN status='LATE' THEN 1 END) AS late_days
            FROM ta_attendance
            WHERE employee_id = :eid
              AND attendance_date BETWEEN :start AND :end
        ");
        $stmt->execute([
            ':eid' => $employeeId,
            ':start' => $startDate,
            ':end' => $endDate
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Existing method for overview
    public function getSalaryOverview(?int $periodId = null, ?string $employmentType = null): array
    {
        $sql = "
        SELECT 
            p.payslip_id AS payroll_id, 
            e.full_name AS employee_name,
            e.position AS position,
            e.employment_status AS employment_type,
            pp.period_name, 
            p.gross_pay, 
            p.total_deductions, 
            p.net_pay,
            pr.status as payroll_status
        FROM pr_payslips p
        JOIN employees e ON p.employee_id = e.employee_id
        LEFT JOIN pr_runs pr ON p.payroll_run_id = pr.run_id
        LEFT JOIN pr_periods pp ON pr.payroll_period_id = pp.period_id
        WHERE e.employment_status = 'Active'
    ";

        $params = [];

        if ($periodId !== null) {
            $sql .= " AND pp.period_id = :periodId";
            $params[':periodId'] = $periodId;
        }
        if ($employmentType !== null && $employmentType !== '') {
            $sql .= " AND e.employment_status = :employmentType";
            $params[':employmentType'] = $employmentType;
        }

        $sql .= " ORDER BY pp.start_date DESC, e.full_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getEmploymentTypes(): array
    {
        // Return available employment status types from new schema
        return ['Regular', 'Contract', 'Part-Time', 'Casual'];
    }
    public function isPeriodClosed(int $periodId): bool
    {
        $stmt = $this->db->prepare("SELECT status FROM pr_periods WHERE period_id = ?");
        $stmt->execute([$periodId]);
        $status = $stmt->fetchColumn();

        return $status === 'closed';
    }
    // New: get single payslip with breakdown
    public function getPayslipById(int $payslipId): ?array
    {
        // Main payslip info
        $stmt = $this->db->prepare("
    SELECT 
        p.*, 
        e.full_name, 
        e.position,
        e.employment_status AS employment_type
    FROM pr_payslips p
    JOIN employees e ON p.employee_id = e.employee_id
    WHERE p.payslip_id = :id
");

        $stmt->execute([':id' => $payslipId]);
        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payslip) return null;

        // Breakdown items (earnings/deductions)
        $stmt2 = $this->db->prepare("
            SELECT item_type, description, amount
            FROM pr_payslip_items
            WHERE payslip_id = :id
        ");
        $stmt2->execute([':id' => $payslipId]);
        $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Separate earnings and deductions
        $payslip['earnings'] = array_filter($items, fn($i) => $i['item_type'] === 'earning');
        $payslip['deductions'] = array_filter($items, fn($i) => $i['item_type'] === 'deduction');

        return $payslip;
    }

    public function getPayrollPeriods(): array
    {
        $stmt = $this->db->query("SELECT * FROM pr_periods ORDER BY start_date DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllEmployees(): array
    {
        $stmt = $this->db->query("
        SELECT employee_id as id,
               full_name AS name
        FROM employees
        WHERE employment_status = 'Active'
        ORDER BY full_name
    ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeesForPayroll(int $periodId): array
    {
        $stmt = $this->db->prepare("
        SELECT e.employee_id, e.full_name AS name, e.position
        FROM employees e
        WHERE e.employment_status='Active'
        AND NOT EXISTS (
            SELECT 1
            FROM pr_payslips p
            JOIN pr_runs pr ON pr.run_id = p.payroll_run_id
            WHERE p.employee_id = e.employee_id
            AND pr.payroll_period_id = :periodId
        )
    ");
        $stmt->execute(['periodId' => $periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActiveEmployeesForPeriod(int $periodId): array
    {
        $stmt = $this->db->prepare("
        SELECT e.employee_id, e.full_name AS name, e.position
        FROM employees e
        WHERE e.employment_status='Active'
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calculateEmployeePayroll(string $employeeId, int $periodId): array
    {
        /* ==============================
       Get Payroll Period Dates
    ============================== */
        $stmtPeriod = $this->db->prepare("
        SELECT start_date, end_date
        FROM pr_periods
        WHERE period_id = :pid
    ");
        $stmtPeriod->execute([':pid' => $periodId]);
        $period = $stmtPeriod->fetch(PDO::FETCH_ASSOC);
        if (!$period) {
            return [];
        }
        $start = $period['start_date'];
        $end   = $period['end_date'];

        // Initialize variables
        $hoursWorked = 0;
        $daysWorked = 0;
        $overtimeHours = 0;
        $overtimePay = 0;

        /* ==============================
       Get Employee Info
    ============================== */
        $stmtEmp = $this->db->prepare("
        SELECT e.*
        FROM employees e
        WHERE e.employee_id = :eid
    ");
        $stmtEmp->execute([':eid' => $employeeId]);
        $employee = $stmtEmp->fetch(PDO::FETCH_ASSOC);
        $isPartTime = ($employee['employment_status'] === 'Part-Time');

        /* ==============================
       Get Basic Salary (Simplified)
    ============================== */
        // Use default salary for now - can be enhanced with proper salary tables later
        $basicSalary = 15000.00; // Default semi-monthly salary (full attendance)
        $allowances = 2000.00;   // Default allowances
        $deductions = 500.00;    // Default deductions

        // Calculate based on attendance (with 15 expected workdays per half-month)
        $dailyRate = $basicSalary / 15;
        $basicSalary = $dailyRate * $daysWorked;

        /* ==============================
       Attendance (from TA table when available, otherwise 0)
    ==============================
        */
        $attendance = $this->getTimeAttendanceMetrics($employeeId, $start, $end);
        $daysWorked = 0;
        $absentDays = 0;
        $hoursWorked = 0;
        $overtimeHours = 0;

        if ($attendance) {
            $daysWorked = (int)($attendance['present_days'] ?? 0);
            $absentDays = (int)($attendance['absent_days'] ?? 0);
            $hoursWorked = (float)($attendance['total_hours_worked'] ?? 0);
            $overtimeHours = (float)($attendance['total_overtime_hours'] ?? 0);
        }

        /* ==============================
   Get Adjustments (Per Period)
============================== */
        $stmtAdj = $this->db->prepare("
    SELECT type, description, amount
    FROM pr_employee_adjustments
    WHERE employee_id = :eid
      AND payroll_period_id = :pid
");
        $stmtAdj->execute([
            ':eid' => $employeeId,
            ':pid' => $periodId
        ]);

        $adjustments = $stmtAdj->fetchAll(PDO::FETCH_ASSOC);


        /* ==============================
       Simplified Overtime (Default for now)
    ============================== */
        $overtimeHours = 0;
        $overtimePay = 0;

        /* ==============================
       Compute Earnings
    ============================== */

        $deductions = [];
        $totalDeductions = 0;
        $earnings = [
            [
                'description' => 'Basic Salary',
                'amount' => $basicSalary
            ]
        ];
        $grossPay = $basicSalary;
        // Apply adjustments
        foreach ($adjustments as $adj) {

            if ($adj['type'] === 'allowance') {

                $earnings[] = [
                    'description' => $adj['description'],
                    'amount' => (float)$adj['amount']
                ];

                $grossPay += (float)$adj['amount'];
            } else { // deduction

                $deductions[] = [
                    'description' => $adj['description'],
                    'amount' => (float)$adj['amount']
                ];

                $totalDeductions += (float)$adj['amount'];
            }
        }


        // Add overtime
        if ($overtimePay > 0) {
            $earnings[] = [
                'description' => 'Overtime Pay',
                'amount' => $overtimePay
            ];
            $grossPay += $overtimePay;
        }

        /* ==============================
   STOP IF NO PAY
============================== */
        if ($grossPay <= 0) {
            return [
                'gross_pay' => 0,
                'net_pay' => 0,
                'total_deductions' => 0,
                'earnings' => $earnings,
                'deductions' => []
            ];
        }


        /* ==============================
       Compute PHILIPPINE DEDUCTIONS
    ============================== */


        // Monthly salary for contribution calculation (based on earned salary)
        $monthlySalary = ($basicSalary > 0) ? ($basicSalary * 2) : 0;

        // 1. SSS Contribution (2024 rates)
        $sssContribution = $this->calculateSSS($monthlySalary);
        if ($sssContribution > 0) {
            $deductions[] = [
                'description' => 'SSS',
                'amount' => $sssContribution
            ];
            $totalDeductions += $sssContribution;
        }

        // 2. PhilHealth Contribution (2024 rates)
        $philhealthContribution = $this->calculatePhilHealth($monthlySalary);
        if ($philhealthContribution > 0) {
            $deductions[] = [
                'description' => 'PhilHealth',
                'amount' => $philhealthContribution
            ];
            $totalDeductions += $philhealthContribution;
        }

        // 3. Pag-IBIG Contribution (2024 rates)
        $pagibigContribution = $this->calculatePagIBIG($monthlySalary);
        if ($pagibigContribution > 0) {
            $deductions[] = [
                'description' => 'Pag-IBIG',
                'amount' => $pagibigContribution
            ];
            $totalDeductions += $pagibigContribution;
        }

        // 4. Withholding Tax (TRAIN Law)
        $taxableIncome = ($grossPay * 2) - ($sssContribution * 2) - ($philhealthContribution * 2) - ($pagibigContribution * 2);
        $withholdingTax = $this->calculateWithholdingTax($taxableIncome) / 2; // Semi-monthly

        if ($withholdingTax > 0) {
            $deductions[] = [
                'description' => 'Withholding Tax',
                'amount' => $withholdingTax
            ];
            $totalDeductions += $withholdingTax;
        }

        // 5. Absence Deduction
        if (!$isPartTime && $absentDays > 0) {
            $dailyRate = $basicSalary / 11; // 11 working days in semi-monthly
            $absenceDeduction = $absentDays * $dailyRate;

            $deductions[] = [
                'description' => "Absence ({$absentDays} days)",
                'amount' => $absenceDeduction
            ];
            $totalDeductions += $absenceDeduction;
        }

        /* ==============================
       Net Pay
    ============================== */
        $netPay = $grossPay - $totalDeductions;

        return [
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
            'total_deductions' => $totalDeductions,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'hours_worked' => $hoursWorked,
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'overtime_multiplier' => 1.25,
            'days_worked' => $daysWorked,
            'absent_days' => $absentDays
        ];
    }

    /* ==============================
   PHILIPPINE CONTRIBUTION CALCULATORS
============================== */

    private function calculateSSS(float $monthlySalary): float
    {
        // 2024 SSS Contribution Table (Employee share only)
        if ($monthlySalary < 4250) return 180.00;
        if ($monthlySalary < 4750) return 202.50;
        if ($monthlySalary < 5250) return 225.00;
        if ($monthlySalary < 5750) return 247.50;
        if ($monthlySalary < 6250) return 270.00;
        if ($monthlySalary < 6750) return 292.50;
        if ($monthlySalary < 7250) return 315.00;
        if ($monthlySalary < 7750) return 337.50;
        if ($monthlySalary < 8250) return 360.00;
        if ($monthlySalary < 8750) return 382.50;
        if ($monthlySalary < 9250) return 405.00;
        if ($monthlySalary < 9750) return 427.50;
        if ($monthlySalary < 10250) return 450.00;
        if ($monthlySalary < 10750) return 472.50;
        if ($monthlySalary < 11250) return 495.00;
        if ($monthlySalary < 11750) return 517.50;
        if ($monthlySalary < 12250) return 540.00;
        if ($monthlySalary < 12750) return 562.50;
        if ($monthlySalary < 13250) return 585.00;
        if ($monthlySalary < 13750) return 607.50;
        if ($monthlySalary < 14250) return 630.00;
        if ($monthlySalary < 14750) return 652.50;
        if ($monthlySalary < 15250) return 675.00;
        if ($monthlySalary < 15750) return 697.50;
        if ($monthlySalary < 16250) return 720.00;
        if ($monthlySalary < 16750) return 742.50;
        if ($monthlySalary < 17250) return 765.00;
        if ($monthlySalary < 17750) return 787.50;
        if ($monthlySalary < 18250) return 810.00;
        if ($monthlySalary < 18750) return 832.50;
        if ($monthlySalary < 19250) return 855.00;
        if ($monthlySalary < 19750) return 877.50;
        if ($monthlySalary >= 19750) return 900.00; // Maximum

        return 0;
    }

    private function calculatePhilHealth(float $monthlySalary): float
    {
        // 2024 PhilHealth: 5% of basic salary (2.5% employee share)
        // Minimum: ₱10,000, Maximum: ₱100,000
        $baseSalary = max(10000, min($monthlySalary, 100000));
        return ($baseSalary * 0.05) / 2; // Employee share is half
    }

    private function calculatePagIBIG(float $monthlySalary): float
    {
        // 2024 Pag-IBIG: 2% of monthly salary
        // Maximum salary ceiling: ₱5,000
        if ($monthlySalary <= 1500) {
            return $monthlySalary * 0.01; // 1% if ≤ ₱1,500
        } else {
            $baseSalary = min($monthlySalary, 5000);
            return $baseSalary * 0.02; // 2% capped at ₱5,000
        }
    }

    private function calculateWithholdingTax(float $annualIncome): float
    {
        // TRAIN Law 2024 Tax Table (Annual)
        if ($annualIncome <= 250000) {
            return 0; // Tax exempt
        } elseif ($annualIncome <= 400000) {
            return ($annualIncome - 250000) * 0.15;
        } elseif ($annualIncome <= 800000) {
            return 22500 + (($annualIncome - 400000) * 0.20);
        } elseif ($annualIncome <= 2000000) {
            return 102500 + (($annualIncome - 800000) * 0.25);
        } elseif ($annualIncome <= 8000000) {
            return 402500 + (($annualIncome - 2000000) * 0.30);
        } else {
            return 2202500 + (($annualIncome - 8000000) * 0.35);
        }
    }

    public function createPayrollRun(int $periodId): int
    {
        $stmt = $this->db->prepare("INSERT INTO pr_runs (payroll_period_id, processed_at, status) VALUES (:pid, NOW(), 'draft')");
        $stmt->execute([':pid' => $periodId]);
        return (int)$this->db->lastInsertId();
    }
    public function generatePayslip(int $runId, string $employeeId, array $data): void
    {
        if ($data['gross_pay'] <= 0) {
            return; // Do not insert empty payslips
        }

        // Insert main payslip
        $stmt = $this->db->prepare("
        INSERT INTO pr_payslips (payroll_run_id, employee_id, gross_pay, total_deductions, net_pay)
        VALUES (:run, :eid, :gross, :ded, :net)
    ");
        $stmt->execute([
            ':run' => $runId,
            ':eid' => $employeeId,
            ':gross' => $data['gross_pay'],
            ':ded' => $data['total_deductions'],
            ':net' => $data['net_pay']
        ]);

        $payslipId = (int)$this->db->lastInsertId();

        // Insert earnings & deductions
        $stmt2 = $this->db->prepare("
        INSERT INTO pr_payslip_items (payslip_id, item_type, description, amount)
        VALUES (:pid, :type, :desc, :amt)
    ");

        foreach ($data['earnings'] as $e) {
            $stmt2->execute([
                ':pid' => $payslipId,
                ':type' => 'earning',
                ':desc' => $e['description'],
                ':amt' => $e['amount']
            ]);
        }

        foreach ($data['deductions'] as $d) {
            $stmt2->execute([
                ':pid' => $payslipId,
                ':type' => 'deduction',
                ':desc' => $d['description'],
                ':amt' => $d['amount']
            ]);
        }
    }
    public function closePayrollPeriod(int $periodId): bool
    {
        $stmt = $this->db->prepare("UPDATE pr_periods SET status='closed' WHERE period_id=:pid");
        return $stmt->execute([':pid' => $periodId]);
    }
    public function finalizeRun($runId)
    {
        $stmt = $this->db->prepare("UPDATE pr_runs SET status='finalized' WHERE run_id=:rid AND status='draft' ");
        return $stmt->execute([':rid' => $runId]);
    }
    // Preview payroll before processing
    public function getPayrollPreview(int $periodId): array
    {
        $employees = $this->getAllActiveEmployeesForPeriod($periodId);
        $preview = [];

        foreach ($employees as $emp) {
            $payroll = $this->calculateEmployeePayroll($emp['employee_id'], $periodId);

            if (!isset($payroll['gross_pay'])) {
                continue;
            }

            $preview[] = array_merge($emp, $payroll);
        }

        return $preview;
    }
}
