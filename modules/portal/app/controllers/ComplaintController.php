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
        $userId = $_SESSION['user_id'];

        $employee = $this->employeeModel->getByUserId($userId);

        $employees = $this->employeeModel->getAllExcept($employee['id']);

        $complaintHistory = $this->complaintModel->getComplaint($employee['id']);

        $title = "Employee Complaint";
        $content = __DIR__ . '/../views/employee-portal/complaint/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }

    public function store()
    {
        try {

            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception('User session not found.');
            }

            $employee = $this->employeeModel->getByUserId($userId);

            $incidentType = $_POST['incident_type'] ?? '';

            if (empty($incidentType)) {
                throw new Exception('Incident type is required.');
            }

            $title = ucfirst(str_replace('_', ' ', $incidentType));

            $data = [
                // Reporter
                'reporter_employee_id' => $employee['id'],

                // Respondent
                'respondent_employee_id' => !empty($_POST['respondent_employee_id'])
                    ? (int) $_POST['respondent_employee_id']
                    : null,

                'respondent_relationship' =>
                    $_POST['respondent_relationship'] ?? null,

                // Incident
                'incident_type' => $incidentType,

                'type' => $_POST['type'] ?? 'other',

                'severity' => $_POST['severity'] ?? 'medium',

                'title' => $title,

                'incident_date' =>
                    $_POST['incident_date'] ?? null,

                'incident_time' =>
                    $_POST['incident_time'] ?? null,

                'location' =>
                    trim($_POST['location'] ?? ''),

                'description' =>
                    trim($_POST['description'] ?? ''),

                'status' => 'submitted',

                'reporter_role' => 'reporter',

                'reporter_type' => 'employee'
            ];

            /*
            |--------------------------------------------------------------------------
            | Create Complaint
            |--------------------------------------------------------------------------
            */
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
            echo $e->getMessage();
            echo "\n\nFile: ";
            echo $e->getFile();
            echo "\nLine: ";
            echo $e->getLine();
            echo '</pre>';

            exit;
        }
    }
}