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
    public function adminIndex()
    {
        $allComplaints = $this->complaintModel->all();

        $title = "Admin Complaint Module";
        $content = __DIR__ . '/../views/admin-portal/complaint/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }

    public function store()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception('User session not found.');
            }

            $employee = $this->employeeModel->getByUserId($userId);

            if (!$employee) {
                throw new Exception('Employee profile not found.');
            }

            $reporterId = $employee['employee_id'] ?? null;

            if (!$reporterId) {
                throw new Exception('Employee ID not found.');
            }

            $reporterName = trim(
                ($employee['first_name'] ?? '') . ' ' .
                ($employee['middle_name'] ?? '') . ' ' .
                ($employee['last_name'] ?? '')
            );

            $reporterName = preg_replace('/\s+/', ' ', $reporterName);

            if ($reporterName === '') {
                throw new Exception('Employee name could not be determined.');
            }

            $reporterDepartment =
                $employee['department_name']
                ?? $employee['department']
                ?? null;

            $respondentId = $_POST['respondent_employee_id'] ?? null;

            if (!$respondentId) {
                throw new Exception('Respondent employee is required.');
            }

            $respondent = $this->employeeModel->getByEmployeeId($respondentId);

            if (!$respondent) {
                throw new Exception('Selected respondent employee was not found.');
            }

            if ((string) $respondentId === (string) $reporterId) {
                throw new Exception('You cannot submit a complaint against yourself.');
            }

            $respondentName = trim(
                ($respondent['first_name'] ?? '') . ' ' .
                ($respondent['middle_name'] ?? '') . ' ' .
                ($respondent['last_name'] ?? '')
            );

            $respondentName = preg_replace('/\s+/', ' ', $respondentName);

            if ($respondentName === '') {
                throw new Exception('Respondent employee name could not be determined.');
            }

            $type = trim($_POST['type'] ?? '');

            if ($type === '') {
                throw new Exception('Complaint type is required.');
            }

            $allowedTypes = [
                'Misconduct',
                'Harassment',
                'Absenteeism',
                'Workplace Conflict',
                'Discrimination',
                'Other'
            ];

            if (!in_array($type, $allowedTypes, true)) {
                throw new Exception('Invalid complaint type.');
            }

            $incidentDate = trim($_POST['incident_date'] ?? '');

            if ($incidentDate === '') {
                throw new Exception('Incident date is required.');
            }

            $incidentTime = trim($_POST['incident_time'] ?? '');

            if ($incidentTime === '') {
                throw new Exception('Incident time is required.');
            }

            $location = trim($_POST['location'] ?? '');

            if ($location === '') {
                throw new Exception('Incident location is required.');
            }

            $title = trim($_POST['title'] ?? '');

            if ($title === '') {
                throw new Exception('Complaint title is required.');
            }

            $description = trim($_POST['description'] ?? '');

            if ($description === '') {
                throw new Exception('Complaint description is required.');
            }

            if (!isset($_POST['factual_confirmation'])) {
                throw new Exception(
                    'You must confirm that the information provided is factual.'
                );
            }

            $data = [
                'employee_id' => $reporterId,
                'reporter_name' => $reporterName,
                'reporter_department' => $reporterDepartment,

                'respondent_employee_id' => $respondentId,
                'respondent_name' => $respondentName,

                'type' => $type,
                'severity' => null,

                'incident_date' => $incidentDate,
                'incident_time' => $incidentTime,
                'location' => $location,
                'title' => $title,
                'description' => $description,

                'status' => 'under_initial_review',
                'current_step_key' => 'initial_review',
                'current_state' => 'under_initial_review',
                'version' => 1,

                'assigned_to' => null,
                'assigned_name' => null,
                'workflow_progress' => null,

                'employee_response' => null,
                'employee_response_date' => null,

                'termination_reply' => null,
                'termination_review_status' => null
            ];

            $success = $this->complaintModel->create($data);

            if (!$success) {
                throw new Exception('Complaint could not be created.');
            }

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