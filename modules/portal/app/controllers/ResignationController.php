<?php

namespace App\Controllers;

use App\Helper\Helper;
use App\Models\Employee;
use App\Models\Resignation;

class ResignationController
{
    private Employee $employeeModel;
    private Resignation $resignationModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->resignationModel = new Resignation();
    }

    public function index()
    {
        $userId = $_SESSION;
        $employee = $this->employeeModel->getByUserId($userId['user_id']);
        $resignations = $this->resignationModel->getResignation($employee['employee_id']);

        $title = "Employee Resignation";
        $content = __DIR__ . '/../views/employee-portal/resignation/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $resignations = $this->resignationModel->all();

        $title = "Employee Resignation";
        $content = __DIR__ . '/../views/admin-portal/resignation/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function store()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION;
            $employee = $this->employeeModel->getByUserId($userId['user_id']);
            $employeeId = (int) ($employee['employee_id']);

            if (!$employeeId) {
                $_SESSION['error'] = 'Employee session not found.';
                Helper::redirect('index.php?url=resignation');
                return;
            }
            $resignationType = trim($_POST['resignation_type'] ?? '');
            $lastWorkingDay = trim($_POST['intended_last_working_day'] ?? '');
            $reason = trim($_POST['resignation_reason'] ?? '');
            $employeeRemarks = trim($_POST['employee_remarks'] ?? '');

            // VALIDATION
            if (
                $resignationType === '' ||
                $lastWorkingDay === '' ||
                $reason === '' ||
                $employeeRemarks === ''
            ) {
                $_SESSION['error'] = 'Please complete all required fields.';
                Helper::redirect('index.php?url=resignation');
                return;
            }

            if (!in_array($resignationType, ['With Notice', 'Immediate'], true)) {
                $_SESSION['error'] = 'Invalid resignation type.';
                Helper::redirect('index.php?url=resignation');
                return;
            }

            // CHECK EXISTING PENDING RESIGNATION
            if ($this->resignationModel->hasPendingResignation($employeeId)) {
                $_SESSION['error'] =
                    'You already have an existing resignation request. Please wait for the HR Admin to review it.';

                Helper::redirect('index.php?url=resignation');
                return;
            }

            // INSERT RESIGNATION
            $data = [
                'employee_id' => $employeeId,
                'resignation_type' => $resignationType,
                'resignation_reason' => $reason,
                'attachment' => null,
                'date_submitted' => date('Y-m-d H:i:s'),
                'intended_last_working_day' => $lastWorkingDay,
                'status' => 'Pending',
                'employee_remarks' => $employeeRemarks,
                'hr_remarks' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // OPTIONAL FILE UPLOAD
            if (
                isset($_FILES['attachment']) &&
                $_FILES['attachment']['error'] === UPLOAD_ERR_OK
            ) {
                $file = $_FILES['attachment'];

                if ($file['size'] > 5 * 1024 * 1024) {
                    $_SESSION['error'] = 'Attachment must not exceed 5 MB.';
                    Helper::redirect('index.php?url=resignation');
                    return;
                }

                $extension = strtolower(
                    pathinfo($file['name'], PATHINFO_EXTENSION)
                );

                if ($extension !== 'pdf') {
                    $_SESSION['error'] = 'Only PDF files are allowed.';
                    Helper::redirect('index.php?url=resignation');
                    return;
                }

                $uploadDir = __DIR__ . '/../../public/assets/uploads/resignation/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = 'resignation_' . $employeeId . '_' . time() . '.pdf';
                $filePath = $uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    $_SESSION['error'] = 'Failed to upload resignation letter.';
                    Helper::redirect('index.php?url=resignation');
                    return;
                }

                $data['attachment'] =
                    'uploads/resignation/' . $fileName;
            }

            if ($this->resignationModel->create($data)) {

                $_SESSION['success'] =
                    'Your resignation request has been submitted successfully.';

            } else {

                $_SESSION['error'] =
                    'Failed to submit resignation request.';
            }

        } catch (\Throwable $e) {

            error_log($e->getMessage());

            $_SESSION['error'] =
                'An unexpected error occurred while submitting your resignation.';
        }

        Helper::redirect('index.php?url=resignation');
    }
    public function approve()
    {
        try {

            $resignationId = (int) ($_POST['resignation_id'] ?? 0);
            $hrRemarks = trim($_POST['hr_remarks'] ?? '');
            $reviewedBy = $_SESSION['user_id'] ?? null;

            if ($resignationId <= 0) {
                $_SESSION['error'] = 'Invalid resignation request.';
                Helper::redirect('index.php?url=admin-resignation');
                return;
            }

            $success = $this->resignationModel->approve(
                $resignationId,
                $hrRemarks,
                $reviewedBy
            );

            if ($success) {
                $_SESSION['success'] =
                    'Resignation approved and employee account deactivated.';
            } else {
                $_SESSION['error'] =
                    'Failed to approve resignation request.';
            }

        } catch (\Throwable $e) {

            error_log('Approve Controller Error: ' . $e->getMessage());

            $_SESSION['error'] =
                'Failed to process resignation request.';
        }

        Helper::redirect('index.php?url=admin-resignation');
    }
    public function reject()
    {
        try {

            $resignationId = (int) ($_POST['resignation_id'] ?? 0);
            $hrRemarks = trim($_POST['hr_remarks'] ?? '');
            $reviewedBy = $_SESSION['user_id'] ?? null;

            if ($resignationId <= 0) {
                $_SESSION['error'] = 'Invalid resignation request.';
                Helper::redirect('index.php?url=admin-resignation');
                return;
            }

            if ($hrRemarks === '') {
                $_SESSION['error'] = 'Rejection reason is required.';
                Helper::redirect('index.php?url=admin-resignation');
                return;
            }

            $success = $this->resignationModel->reject(
                $resignationId,
                $hrRemarks,
                $reviewedBy
            );

            if ($success) {

                $_SESSION['success'] =
                    'Resignation request rejected successfully.';

            } else {

                $_SESSION['error'] =
                    'Failed to reject resignation request.';
            }

        } catch (\Throwable $e) {

            error_log(
                'Resignation Reject Error: ' . $e->getMessage()
            );

            $_SESSION['error'] =
                'Failed to process resignation request.';
        }

        Helper::redirect('index.php?url=admin-resignation');
    }

}