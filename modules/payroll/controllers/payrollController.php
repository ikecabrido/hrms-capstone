<?php
require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../models/payrollModel.php';
require_once __DIR__ . '/../models/payrollPeriodModel.php';
require_once __DIR__ . '/../models/payslipModel.php';

class PayrollController
{
    private PDO $db;
    private PayrollModel $payrollModel;
    private PayrollPeriodModel $periodModel;
    private PayslipModel $payslipModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->payrollModel = new PayrollModel($this->db);
        $this->periodModel = new PayrollPeriodModel($this->db);
        $this->payslipModel = new PayslipModel($this->db);
    }

    // Show payroll processing page
    public function index()
    {
        $periods = $this->payrollModel->getPayrollPeriods();
        return $periods;
    }

    // Calculate payroll for selected period
    public function calculate(int $periodId)
    {
        if ($this->isClosed($periodId)) {
            throw new Exception('Payroll period is already closed. Cannot recalculate.');
        }

        $employees = $this->payrollModel->getEmployeesForPayroll($periodId);

        if (empty($employees)) {
            throw new Exception("No employee salary data available for this period.");
        }
        $results = [];

        foreach ($employees as $emp) {
            $payroll = $this->payrollModel->calculateEmployeePayroll($emp['employee_id'], $periodId);
            $results[] = array_merge($emp, $payroll);
        }
        // $this->payrollModel->createPayrollRun($periodId);


        return $results;
    }
    public function isClosed(int $periodId): bool
    {
        return $this->payrollModel->isPeriodClosed($periodId);
    }
    public function getEmployees(): array
    {
        return $this->payrollModel->getAllEmployees();
    }

    // Finalize payroll
    public function finalize(int $periodId)
    {

        $runId = $this->payrollModel->createPayrollRun($periodId);
        $employees = $this->payrollModel->getEmployeesForPayroll($periodId);

        foreach ($employees as $emp) {
            $payroll = $this->payrollModel->calculateEmployeePayroll($emp['employee_id'], $periodId);
            $this->payrollModel->generatePayslip($runId, $emp['employee_id'], $payroll);
        }

        $this->payrollModel->closePayrollPeriod($periodId);
        $this->payrollModel->finalizeRun($runId);

        return $runId;
    }

    public function getPeriods()
    {
        return $this->periodModel->getAll();
    }
    public function previewPayroll($periodId)
    {
        return $this->payrollModel->getPayrollPreview($periodId);
    }
}
