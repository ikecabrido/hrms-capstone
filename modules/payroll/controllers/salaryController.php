<?php

require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../models/payrollModel.php';
require_once __DIR__ . '/../models/payslipModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';

class SalaryController
{
    private PDO $db;
    private PayrollModel $payrollModel;
    private PayslipModel $payslipModel;
    private EmployeeModel $employeeModel;

    public function __construct()
    {
        // Correct connection
        $this->db = Database::getInstance()->getConnection();
        $this->payslipModel = new PayslipModel($this->db);
        $this->payrollModel = new PayrollModel($this->db);
        $this->employeeModel = new EmployeeModel($this->db);
    }

    public function index(?int $periodId = null, ?string $employmentType = null)
    {
        return $this->payrollModel->getSalaryOverview($periodId, $employmentType);
    }

    public function viewPayslip(int $payslipId): ?array
    {
        return $this->payrollModel->getPayslipById($payslipId);
    }
    public function getDashboardStats(?int $periodId = null): array
    {
        return [
            'total_gross' => $this->payslipModel->getTotalGrossPay($periodId),
            'total_deductions' => $this->payslipModel->getTotalDeductions($periodId),
            'total_net' => $this->payslipModel->getTotalNetPay($periodId),
            'employee_count' => $this->employeeModel->getCount()
        ];
    }
}
