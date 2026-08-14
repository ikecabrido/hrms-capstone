<?php

namespace App\Controllers;

use Exception;
use App\Models\Payroll;
use App\Models\Employee;

class PayrollController
{
    private Payroll $payrollModel;
    private Employee $employeeModel;
    public function __construct()
    {
        $this->payrollModel = new Payroll();
        $this->employeeModel = new Employee();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'];
        $employeeAttendance = $this->employeeModel->getByUserId($userId);

        $payrollHistory = $this->payrollModel->getPayroll($employeeAttendance['id']);
        $payrollRequests = $this->payrollModel->getPayrollRequests($employeeAttendance['id']);

        $title = "Employee Payroll";
        $content = __DIR__ . '/../views/employee-portal/payroll/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function store()
    {
        $userId = (int) $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);

        $employee_id = (int) $employee['id'];

        $data = [
            'request_type' => trim($_POST['request_type'] ?? ''),
            'period_from' => trim($_POST['period_from'] ?? ''),
            'period_to' => trim($_POST['period_to'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        // Validation
        if (
            empty($data['request_type']) ||
            empty($data['period_from']) ||
            empty($data['period_to']) ||
            empty($data['subject']) ||
            empty($data['description'])
        ) {
            $_SESSION['error'] = 'Please complete all required fields.';
            header('Location: index.php?url=payroll');
            exit;
        }

        // Validate date range
        if ($data['period_from'] > $data['period_to']) {
            $_SESSION['error'] = 'The payroll period is invalid.';
            header('Location: index.php?url=payroll');
            exit;
        }

        $payrollModel = new \App\Models\Payroll();

        try {

            $created = $payrollModel->createPayrollRequest(
                (int) $employee_id,
                $data
            );

            if ($created) {
                $_SESSION['success'] = 'Payroll request submitted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to submit payroll request.';
            }

        } catch (\PDOException $e) {

            $_SESSION['error'] = 'An error occurred while submitting the payroll request.';
        }

        header('Location: index.php?url=payroll');
        exit;
    }

}