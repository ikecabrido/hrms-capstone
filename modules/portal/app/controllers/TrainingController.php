<?php

namespace App\Controllers;

use App\Models\Training;
use App\Models\Course;
use App\Models\Employee;

class TrainingController
{
    private Training $trainingModel;
    private Employee $employeeModel;
    private Course $courseModel;

    public function __construct()
    {
        $this->trainingModel = new Training();
        $this->employeeModel = new Employee();
        $this->courseModel = new Course();
    }

    public function index()
    {
        $allTrainingCourses = $this->trainingModel->allCourse();

        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/employee-portal/training/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function store()
    {
        $userId = (int) $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);
        $learner_id = $employee['employee_id'];

        $data = [
            'requested_title' => trim($_POST['requested_title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];
        try {
            $created = $this->trainingModel->createTrainingRequest((int) $learner_id, $data);
            if ($created) {
                $_SESSION['success'] = 'Training request submitted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to submit training request.';
            }
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
        header('Location: index.php?url=training');
        exit;
    }
    public function adminIndex()
    {
        $allTrainingCourses = $this->trainingModel->allCourse();
        $showInstructorsCourse = $this->courseModel->getInstructors();
        $skills = $this->courseModel->getSkills();
        foreach ($allTrainingCourses as &$course) {

            $course['instructors'] = [];

            foreach ($showInstructorsCourse as $instructor) {

                if (
                    isset($instructor['course_id']) &&
                    (int) $instructor['course_id'] === (int) $course['id']
                ) {
                    $course['instructors'][] = $instructor;
                }
            }
        }

        unset($course);

        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/admin-portal/training/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function viewRequest()
    {
        $perPage = 5;

        $page = isset($_GET['page'])
            ? max(1, (int) $_GET['page'])
            : 1;

        $totalRequests = $this->trainingModel->countTrainingRequests();

        $totalPages = max(
            1,
            (int) ceil($totalRequests / $perPage)
        );

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $requests = $this->trainingModel->getPaginatedRequests(
            $page,
            $perPage
        );

        $start = $totalRequests > 0
            ? (($page - 1) * $perPage) + 1
            : 0;

        $end = min(
            $page * $perPage,
            $totalRequests
        );

        $title = "Training Requests";
        $content = __DIR__ . '/../views/admin-portal/training/request/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function toggleRequest()
    {
        $requestId = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? null;

        if (!$requestId || !is_numeric($requestId)) {
            $_SESSION['error'] = 'Invalid training request.';
            header('Location: index.php?url=view-training-request');
            exit;
        }

        $allowedStatuses = [
            'pending',
            'reviewed',
            'archived'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $_SESSION['error'] = 'Invalid training request status.';
            header('Location: index.php?url=view-training-request');
            exit;
        }

        // Update status
        $updated = $this->trainingModel->updateTrainingRequestStatus(
            (int) $requestId,
            $status
        );

        if ($updated) {
            $_SESSION['success'] = 'Training request status updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update training request status.';
        }

        // Return to training requests page
        header('Location: index.php?url=view-training-request');
        exit;
    }
}