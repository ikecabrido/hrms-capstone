<?php

namespace App\Controllers;

use Exception;
use App\Core\Session;
use App\Helper\Helper;
use App\Models\Leave;
use App\Models\Employee;
use App\Services\LeaveAccessValidationService;

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


    public function create(): void
    {
        $title = "Employee Create Leave Request";

        $content = __DIR__ . '/../views/employee-portal/leave/content.php';

        // Initialize variables to prevent undefined variable warnings.
        $employee = null;
        $leaveHistory = [];
        $leaveTypes = [];
        $leaveAccess = null;

        try {

            // 1. Check authentication.
            $userId = $_SESSION['user_id'] ?? null;

            if (empty($userId)) {
                $_SESSION['error'] = 'Your session has expired. Please log in again.';

                error_log('[LEAVE ACCESS] Missing user_id in session.');

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            // 2. Retrieve employee profile.
            $employee = $this->employeeModel->getByUserId((int) $userId);

            if (empty($employee)) {
                $_SESSION['error'] = 'Your employee profile could not be found. Please contact HR.';

                error_log(
                    '[LEAVE ACCESS] Employee profile not found. User ID: ' . $userId
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            if (empty($employee['employee_id'])) {
                $_SESSION['error'] = 'Your employee ID is missing from your profile. Please contact HR.';

                error_log(
                    '[LEAVE ACCESS] Missing employee_id. User ID: ' . $userId
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            $employeeId = (int) $employee['employee_id'];

            // 3. Validate required employee information.
            $requiredFields = [
                'hire_date' => 'Hire date',
                'employment_status' => 'Employment status',
                'employment_type' => 'Employment type',
            ];

            $missingFields = [];

            foreach ($requiredFields as $field => $label) {
                if (
                    !array_key_exists($field, $employee) ||
                    $employee[$field] === null ||
                    trim((string) $employee[$field]) === ''
                ) {
                    $missingFields[] = $label;
                }
            }

            if (!empty($missingFields)) {
                $missingData = implode(', ', $missingFields);

                $_SESSION['error'] =
                    'Your employee profile has incomplete information: ' .
                    $missingData .
                    '. Please contact HR to update your records.';

                error_log(
                    '[LEAVE ACCESS] Missing employee information. ' .
                    'Employee ID: ' . $employeeId .
                    ' | Missing fields: ' . $missingData
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            // 4. Retrieve leave history.
            $leaveHistory = $this->leaveModel->getLeave($employeeId);

            if (!is_array($leaveHistory)) {
                throw new \UnexpectedValueException(
                    'Unable to retrieve employee leave history.'
                );
            }

            // 5. Retrieve available leave types.
            $leaveTypes = $this->leaveModel->allTypes();

            if (!is_array($leaveTypes) || empty($leaveTypes)) {
                $_SESSION['error'] =
                    'No leave types are currently available. Please contact HR.';

                error_log(
                    '[LEAVE ACCESS] No leave types found. Employee ID: ' . $employeeId
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            // 6. Perform eligibility validation.
            $validationService = new LeaveAccessValidationService();

            $validation = $validationService->validate(
                $employee,
                $leaveTypes
            );

            // Check whether the validation service returned a valid result.
            if (
                !is_array($validation) ||
                !array_key_exists('can_access', $validation) ||
                !array_key_exists('message', $validation) ||
                !array_key_exists('eligible_leave_types', $validation)
            ) {
                throw new \UnexpectedValueException(
                    'Leave eligibility service returned an invalid response.'
                );
            }

            // 7. Handle failed eligibility validation.
            if (!$validation['can_access']) {

                $reason = $validation['message']
                    ?: 'You are currently not eligible to submit a leave request.';

                $_SESSION['error'] = $reason;

                error_log(
                    '[LEAVE ACCESS] Eligibility validation failed. ' .
                    'Employee ID: ' . $employeeId .
                    ' | Reason: ' . $reason .
                    ' | Restrictions: ' . json_encode(
                        $validation['restrictions'] ?? [],
                        JSON_UNESCAPED_SLASHES
                    )
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            // 8. Check eligible leave types.
            $leaveTypes = $validation['eligible_leave_types'];

            if (empty($leaveTypes)) {
                $_SESSION['error'] =
                    'You do not currently have any eligible leave types. Please contact HR.';

                error_log(
                    '[LEAVE ACCESS] No eligible leave types. Employee ID: ' . $employeeId
                );

                require __DIR__ . '/../views/employee-portal/index.php';
                return;
            }

            // 9. Pass validation information to the view.
            $leaveAccess = $validation;

            // 10. Display leave request form.
            $content = __DIR__ . '/../views/employee-portal/leave/create-leave.php';

        } catch (\Throwable $e) {

            // Record detailed technical information in the server error log.
            error_log(
                "[LEAVE ACCESS ERROR]\n" .
                "User ID: " . ($userId ?? 'Unknown') . "\n" .
                "Employee ID: " . ($employee['employee_id'] ?? 'Unknown') . "\n" .
                "Error: " . $e->getMessage() . "\n" .
                "File: " . $e->getFile() . "\n" .
                "Line: " . $e->getLine() . "\n" .
                "Trace: " . $e->getTraceAsString()
            );

            // Display a safe message to the employee.
            $_SESSION['error'] =
                'We encountered a problem while checking your leave eligibility. ' .
                'Please try again later or contact HR if the problem persists.';

            // Return to Leave History instead of displaying an incomplete form.
            $content = __DIR__ . '/../views/employee-portal/leave/content.php';
        }

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