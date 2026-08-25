<?php

namespace App\Controllers;

use Exception;
use App\Models\Payroll;
use App\Models\Employee;
use App\Core\Session;
use App\Helper\Helper;


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

        $payrollHistory = $this->payrollModel->getPayroll($employeeAttendance['employee_id']);
        $payrollRequests = $this->payrollModel->getPayrollRequests($employeeAttendance['employee_id']);
        
        $title = "Employee Payroll";
        $content = __DIR__ . '/../views/employee-portal/payroll/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $payrollList = $this->payrollModel->all();

        $title = "Employee Payroll";
        $content = __DIR__ . '/../views/admin-portal/payroll/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function store()
    {
        $userId = (int) $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);

        $employee_id = (int) $employee['employee_id'];

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

        try {

            $created = $this->payrollModel->createPayrollRequest(
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
    public function upload()
    {
        try {
            $requestId = (int) ($_POST['request_id'] ?? 0);
            $file = $_FILES['document'] ?? null;

            if ($requestId <= 0) {
                throw new Exception('Invalid payroll request.');
            }

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please select a valid document.');
            }

            $allowed = [
                'pdf',
                'doc',
                'docx',
                'xls',
                'xlsx'
            ];

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed, true)) {
                throw new Exception('Invalid document type.');
            }

            $uploadDir = __DIR__ . '/../../public/assets/uploads/payroll/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName =
                'payroll_' .
                $requestId . '_' .
                time() . '.' .
                $extension;

            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Failed to upload document.');
            }

            $documentPath =
                'assets/uploads/payroll/' . $fileName;

            $success = $this->payrollModel->approveWithDocument(
                $requestId,
                $documentPath,
                $_SESSION['user_id'] ?? null
            );

            if (!$success) {
                throw new Exception('Failed to update payroll request.');
            }

            Session::set(
                'success',
                'Payroll document uploaded and request approved.'
            );

        } catch (Exception $e) {

            Session::set(
                'error',
                $e->getMessage()
            );
        }

        Helper::redirect(
            'index.php?url=admin-payroll'
        );
    }
    public function reject()
    {
        try {
            $requestId = (int) ($_POST['request_id'] ?? 0);
            $reason = trim($_POST['rejection_reason'] ?? '');

            if ($requestId <= 0) {
                throw new Exception('Invalid payroll request.');
            }

            if ($reason === '') {
                throw new Exception(
                    'Rejection reason is required.'
                );
            }

            $success = $this->payrollModel->rejectRequest(
                $requestId,
                $reason,
                $_SESSION['user_id'] ?? null
            );

            if (!$success) {
                throw new Exception(
                    'Failed to reject payroll request.'
                );
            }

            Session::set(
                'success',
                'Payroll request rejected successfully.'
            );

        } catch (Exception $e) {

            Session::set(
                'error',
                $e->getMessage()
            );
        }

        Helper::redirect(
            'index.php?url=admin-payroll'
        );
    }

}