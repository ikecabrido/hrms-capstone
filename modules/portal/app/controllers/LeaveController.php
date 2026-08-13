<?php

namespace App\Controllers;

use Exception;
use App\Models\Leave;
use App\Models\Employee;

class LeaveController
{
    private Leave $leaveModel;

    private Employee $employeeModel;
    public function __construct()
    {
        $this->leaveModel = new Leave();
        $this->employeeModel = new Employee();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'];

        $employeeAttendance = $this->employeeModel->getByUserId($userId);

        $leaveHistory = $this->leaveModel->getLeave(
            $employeeAttendance['id']
        );

        $title = "Employee Leave Request";
        $content = __DIR__ . '/../views/employee-portal/leave/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function store()
    {
        try {
            $userId = (int) $_SESSION['user_id'];
            $employee = $this->employeeModel->getByUserId($userId);

            $employeeId = (int) $employee['id'];

            $leaveType = trim($_POST['leave_type'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (
                empty($leaveType) ||
                empty($startDate) ||
                empty($endDate) ||
                empty($reason)
            ) {
                throw new Exception('Please complete all required fields.');
            }

            if ($endDate < $startDate) {
                throw new Exception('End date cannot be earlier than start date.');
            }

            $success = $this->leaveModel->create([
                'employee_id' => $employeeId,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'status' => 'PENDING'
            ]);

            if (!$success) {
                throw new Exception('Failed to submit leave request.');
            }

            // SUCCESS MESSAGE
            $_SESSION['success'] = 'Leave request submitted successfully.';

            header('Location: index.php?url=leave-request');
            exit;

        } catch (\Throwable $e) {

            // ERROR MESSAGE
            $_SESSION['error'] = $e->getMessage();

            header('Location: index.php?url=leave-request');
            exit;
        }
    }



}