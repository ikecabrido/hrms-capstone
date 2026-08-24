<?php

require_once __DIR__ . '/../model/TrainingDevelopmentModel.php';

class TrainingDevelopmentController
{
    private TrainingDevelopmentModel $model;

    public function __construct($pdo = null)
    {
        $this->model = new TrainingDevelopmentModel($pdo);
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'create_recommendation') {
            $data = [
                'employee_id' => (int) ($_POST['employee_id'] ?? 0),
                'training_program_id' => !empty($_POST['training_program_id']) ? (int) $_POST['training_program_id'] : 0,
                'development_area' => trim((string) ($_POST['development_area'] ?? '')),
                'performance_gap' => trim((string) ($_POST['performance_gap'] ?? '')),
                'recommendation_reason' => trim((string) ($_POST['recommendation_reason'] ?? '')),
                'priority_level' => trim((string) ($_POST['priority_level'] ?? 'Medium')),
                'status' => trim((string) ($_POST['status'] ?? 'Pending')),
                'recommendation_date' => trim((string) ($_POST['recommendation_date'] ?? date('Y-m-d'))),
                'target_completion_date' => trim((string) ($_POST['target_completion_date'] ?? '')),
                'recommended_by' => trim((string) ($_SESSION['employee_name'] ?? 'System')),
            ];

            $errors = $this->validateRecommendation($data);
            if (!empty($errors)) {
                $_SESSION['training_error'] = $errors[0];
                $this->redirect();
            }

            if ($this->model->createRecommendation($data)) {
                $_SESSION['training_success'] = 'Training recommendation saved successfully.';
            } else {
                $_SESSION['training_error'] = 'Unable to create the training recommendation.';
            }

            $this->redirect();
        }

        $_SESSION['training_error'] = 'Unsupported action.';
        $this->redirect();
    }

    private function validateRecommendation(array $data): array
    {
        $errors = [];

        if ((int) ($data['employee_id'] ?? 0) <= 0) {
            $errors[] = 'Please select an employee.';
        }

        if (trim((string) ($data['development_area'] ?? '')) === '') {
            $errors[] = 'Training title is required.';
        }

        if (trim((string) ($data['recommendation_reason'] ?? '')) === '') {
            $errors[] = 'Please provide the reason for the recommendation.';
        }

        if (!empty($data['recommendation_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['recommendation_date'])) {
            $errors[] = 'Recommendation date must be valid.';
        }

        return $errors;
    }

    private function redirect(): void
    {
        header('Location: ?page=training-development');
        exit;
    }

    public function getDashboardData(array $filters = []): array
    {
        return [
            'stats' => $this->model->getDashboardStats(),
            'recommendations' => $this->model->getRecommendations($filters),
            'employees' => $this->model->getEmployees(),
            'programs' => $this->model->getPrograms(),
            'upcoming' => $this->model->getUpcomingTraining(),
            'categorySummary' => $this->model->getCategorySummary(),
        ];
    }

    public function getMessages(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $success = $_SESSION['training_success'] ?? '';
        $error = $_SESSION['training_error'] ?? '';

        unset($_SESSION['training_success'], $_SESSION['training_error']);

        return ['success' => $success, 'error' => $error];
    }
}
