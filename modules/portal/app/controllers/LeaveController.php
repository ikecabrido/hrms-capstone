<?php

namespace App\Controllers;

use Exception;
use App\Core\Session;
use App\Helper\Helper;
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
            $employeeAttendance['employee_id']
        );

        $leaveTypes = $this->leaveModel->allTypes();

        $title = "Employee Leave Request";
        $content = __DIR__ . '/../views/employee-portal/leave/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function store()
    {
        try {

            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $employee = $this->employeeModel->getByUserId($userId);

            if (!$employee) {
                throw new Exception('Employee record not found.');
            }

            $employeeId = (int) $employee['employee_id'];
            
            $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $details = trim($_POST['details'] ?? '');

            if (
                empty($leaveTypeId) ||
                empty($startDate) ||
                empty($endDate) ||
                empty($details)
            ) {
                throw new Exception('Please complete all required fields.');
            }

            if ($endDate < $startDate) {
                throw new Exception(
                    'End date cannot be earlier than start date.'
                );
            }

            $documentName = null;

            if (
                isset($_FILES['supporting_document']) &&
                $_FILES['supporting_document']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES['supporting_document'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Failed to upload supporting document.');
                }

                // Maximum file size: 5 MB
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception(
                        'Supporting document must not exceed 5 MB.'
                    );
                }

                // Allowed extensions
                $allowedExtensions = [
                    'pdf',
                    'jpg',
                    'jpeg',
                    'png'
                ];

                $extension = strtolower(
                    pathinfo($file['name'], PATHINFO_EXTENSION)
                );

                if (!in_array($extension, $allowedExtensions, true)) {
                    throw new Exception(
                        'Invalid document format. Allowed: PDF, JPG, JPEG, PNG.'
                    );
                }

                // Verify actual MIME type
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                $allowedMimeTypes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png'
                ];

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    throw new Exception(
                        'Invalid supporting document.'
                    );
                }

                $uploadDirectory =
                    __DIR__ . '/../../public/assets/uploads/leave/';

                if (!is_dir($uploadDirectory)) {
                    if (!mkdir($uploadDirectory, 0755, true)) {
                        throw new Exception(
                            'Unable to create upload directory.'
                        );
                    }
                }
                $documentName =
                    'leave_' .
                    $employeeId . '_' .
                    date('YmdHis') . '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $extension;

                $documentPath =
                    $uploadDirectory . $documentName;
                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $documentPath
                    )
                ) {
                    throw new Exception(
                        'Unable to save supporting document.'
                    );
                }
            }

            $success = $this->leaveModel->create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'details' => $details,
                'supporting_document' => $documentName
            ]);

            if (!$success) {

                // Remove uploaded file if database insertion fails
                if ($documentName) {
                    $uploadedFile =
                        __DIR__ .
                        '/../public/assets/uploads/leave/' .
                        $documentName;

                    if (file_exists($uploadedFile)) {
                        unlink($uploadedFile);
                    }
                }

                throw new Exception(
                    'Failed to submit leave request.'
                );
            }

            $_SESSION['success'] =
                'Leave request submitted successfully.';

        } catch (\Throwable $e) {

            error_log(
                "LEAVE REQUEST ERROR\n" .
                "Message: " . $e->getMessage() . "\n" .
                "File: " . $e->getFile() . "\n" .
                "Line: " . $e->getLine() . "\n" .
                "Trace:\n" . $e->getTraceAsString() . "\n" .
                "POST: " . print_r($_POST, true) . "\n" .
                "FILES: " . print_r($_FILES, true)
            );

            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: index.php?url=leave-request');
        exit;
    }
    public function adminIndex()
    {
        $leaveHistory = $this->leaveModel->all();

        $title = "Employee Leave Request";
        $content = __DIR__ . '/../views/admin-portal/leave/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function cancel()
    {
        try {
            $leaveRequestId = (int) ($_POST['leave_request_id'] ?? 0);

            if (!$leaveRequestId) {
                throw new Exception('Invalid leave request.');
            }

            if (!$this->leaveModel->cancel($leaveRequestId)) {
                throw new Exception('Unable to cancel leave request.');
            }

            $_SESSION['success'] = 'Leave request cancelled successfully.';

        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: index.php?url=leave-request');
        exit;
    }

    public function reject()
    {
        try {
            $leaveId = (int) ($_POST['leave_request_id'] ?? 0);
            $reason = trim($_POST['reject_reason'] ?? '');

            if ($leaveId <= 0 || $reason === '') {
                throw new Exception('Leave request and rejection reason are required.');
            }

            $success = $this->leaveModel->rejectLeave($leaveId, $reason);

            if (!$success) {
                throw new Exception('Failed to reject leave request.');
            }

            Session::set('success', 'Leave request rejected successfully.');

        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        Helper::redirect('index.php?url=admin-leave-request');
    }
    public function approve()
    {
        try {
            $leaveId = (int) ($_POST['leave_request_id'] ?? 0);

            if ($leaveId <= 0) {
                throw new Exception('Leave request is required.');
            }

            $success = $this->leaveModel->approveLeave($leaveId);

            if (!$success) {
                throw new Exception('Failed to approve leave request.');
            }

            Session::set('success', 'Leave request approved successfully.');

        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        Helper::redirect('index.php?url=admin-leave-request');
    }

}