<?php

namespace App\Controllers;

use Exception;
use App\Models\Employee;
use App\Models\Complaint;

class ComplaintController
{
    private Employee $employeeModel;
    private Complaint $complaintModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->complaintModel = new Complaint();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            throw new Exception('User session not found.');
        }

        $employee = $this->employeeModel->getByUserId($userId);

        if (!$employee) {
            throw new Exception('Employee profile not found.');
        }

        $employees = $this->employeeModel->getAllExcept(
            $employee['employee_id']
        );

        $reporterName = trim(
            ($employee['first_name'] ?? '') . ' ' .
            ($employee['middle_name'] ?? '') . ' ' .
            ($employee['last_name'] ?? '')
        );

        $reporterName = preg_replace('/\s+/', ' ', $reporterName);

        $complaintHistory = $this->complaintModel->getComplaint(
            $reporterName
        );

        $title = "Employee Complaint";
        $content = __DIR__ . '/../views/employee-portal/complaint/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }

    public function store()
    {
        try {
            // Get logged-in user
            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception('User session not found.');
            }

            // Get logged-in employee profile
            $employee = $this->employeeModel->getByUserId($userId);

            if (!$employee) {
                throw new Exception('Employee profile not found.');
            }

            // Reporter information
            $reporterId = $employee['employee_id'] ?? null;

            if (!$reporterId) {
                throw new Exception('Employee ID not found.');
            }

            // Reporter name
            $reporterName = trim(
                ($employee['first_name'] ?? '') . ' ' .
                ($employee['middle_name'] ?? '') . ' ' .
                ($employee['last_name'] ?? '')
            );

            $reporterName = preg_replace('/\s+/', ' ', $reporterName);

            if ($reporterName === '') {
                throw new Exception('Employee name could not be determined.');
            }

            // Reporter department
            $reporterDepartment = $employee['department_name']
                ?? $employee['department']
                ?? null;

            // Respondent employee
            $respondentId = $_POST['employee_id'] ?? null;

            if (!$respondentId) {
                throw new Exception('Respondent employee is required.');
            }

            // Complaint type
            $type = trim($_POST['type'] ?? '');

            if ($type === '') {
                throw new Exception('Complaint type is required.');
            }

            // Severity
            $severity = trim($_POST['severity'] ?? '');

            if ($severity === '') {
                throw new Exception('Severity is required.');
            }

            $allowedSeverity = ['Low', 'Medium', 'High'];

            if (!in_array($severity, $allowedSeverity, true)) {
                throw new Exception('Invalid complaint severity.');
            }

            // Incident date
            $incidentDate = $_POST['incident_date'] ?? null;

            if (!$incidentDate) {
                throw new Exception('Incident date is required.');
            }

            // Incident time
            $incidentTime = $_POST['incident_time'] ?? null;

            if (!$incidentTime) {
                throw new Exception('Incident time is required.');
            }

            // Location
            $location = trim($_POST['location'] ?? '');

            if ($location === '') {
                throw new Exception('Incident location is required.');
            }

            // Complaint title
            $title = trim($_POST['title'] ?? '');

            if ($title === '') {
                throw new Exception('Complaint title is required.');
            }

            // Complaint description
            $description = trim($_POST['description'] ?? '');

            if ($description === '') {
                throw new Exception('Complaint description is required.');
            }

            // Factual confirmation
            if (!isset($_POST['factual_confirmation'])) {
                throw new Exception(
                    'You must confirm that the information provided is factual.'
                );
            }

            // Complaint data
            $data = [
                'employee_id' => $respondentId,
                'reporter_name' => $reporterName,
                'reporter_department' => $reporterDepartment,
                'type' => $type,
                'severity' => $severity,
                'status' => 'under_initial_review',
                'incident_date' => $incidentDate,
                'incident_time' => $incidentTime,
                'location' => $location,
                'title' => $title,
                'description' => $description,
                'assigned_to' => null,
                'assigned_name' => null,
                'employee_response' => null,
                'employee_response_date' => null
            ];

            // Create complaint
            $success = $this->complaintModel->create($data);

            if (!$success) {
                throw new Exception('Complaint could not be created.');
            }

            // Success
            $_SESSION['success'] = 'Complaint submitted successfully.';

            header('Location: index.php?url=complaint');
            exit;

        } catch (\Throwable $e) {
            echo '<pre>';
            echo "Complaint submission error:\n\n";
            echo htmlspecialchars($e->getMessage());
            echo "\n\nFile: ";
            echo htmlspecialchars($e->getFile());
            echo "\nLine: ";
            echo htmlspecialchars((string) $e->getLine());
            echo '</pre>';
            exit;
        }
    }
}