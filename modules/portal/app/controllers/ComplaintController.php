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
        $employees = $this->employeeModel->getAllExcept($employee['employee_id']);
        $complaintHistory = $this->complaintModel->getComplaint($employee['employee_id']);

        $title = "Employee Complaint";
        $content = __DIR__ . '/../views/employee-portal/complaint/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }

    public function store()
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Get Logged-in User
            |--------------------------------------------------------------------------
            */
            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception('User session not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Get Employee Profile
            |--------------------------------------------------------------------------
            */
            $employee = $this->employeeModel->getByUserId($userId);

            if (!$employee) {
                throw new Exception('Employee profile not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Validate Incident Type
            |--------------------------------------------------------------------------
            */
            $incidentType = trim($_POST['incident_type'] ?? '');

            if ($incidentType === '') {
                throw new Exception('Incident type is required.');
            }
            $title = ucfirst(str_replace('_', ' ', $incidentType));

            /*
            |--------------------------------------------------------------------------
            | Reporter Employee ID
            |--------------------------------------------------------------------------
            |
            | Your employee table uses employee_id.
            |
            */
            $reporterId = $employee['employee_id'] ?? null;

            if (!$reporterId) {
                throw new Exception('Reporter employee ID not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | Respondent
            |--------------------------------------------------------------------------
            */
            $respondentId = $_POST['respondent_employee_id'] ?? null;

            if (!$respondentId) {
                throw new Exception('Respondent employee is required.');
            }


            /*
            |--------------------------------------------------------------------------
            | Complaint Data
            |--------------------------------------------------------------------------
            */
            $data = [

                // Reporter
                'reporter_id' => $reporterId,

                // Respondent
                'respondent_employee_id' => $respondentId,

                // Incident information
                'type' =>
                    !empty($_POST['type'])
                    ? trim($_POST['type'])
                    : null,

                'incident_type' =>
                    $incidentType,

                'severity' =>
                    $_POST['severity'] ?? 'medium',

                'title' => $title,

                'description' =>
                    trim($_POST['description'] ?? ''),

                'incident_date' =>
                    $_POST['incident_date'] ?? null,

                'location' =>
                    !empty($_POST['location'])
                    ? trim($_POST['location'])
                    : null,

                // Privacy
                'is_confidential' =>
                    isset($_POST['is_confidential'])
                    ? 1
                    : 0,

                // Deadlines
                'nte_deadline' =>
                    !empty($_POST['nte_deadline'])
                    ? $_POST['nte_deadline']
                    : null,

                'explanation_deadline' =>
                    !empty($_POST['explanation_deadline'])
                    ? $_POST['explanation_deadline']
                    : null,

                // User who created the complaint
                'created_by' =>
                    $userId
            ];


            /*
            |--------------------------------------------------------------------------
            | Create Complaint
            |--------------------------------------------------------------------------
            */
            $success = $this->complaintModel->create($data);

            if (!$success) {
                throw new Exception(
                    'Complaint could not be created.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */
            $_SESSION['success'] =
                'Complaint submitted successfully.';

            header(
                'Location: index.php?url=complaint'
            );

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