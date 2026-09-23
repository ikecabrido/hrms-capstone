<?php
require_once __DIR__ . '/../model/EvaluationModel.php';

class EvaluationController
{
    private EvaluationModel $model;

    public function __construct($pdo = null)
    {
        $this->model = new EvaluationModel($pdo);
    }

    public function handleRequest(): void
    {
        if (isset($_GET['action']) && $_GET['action'] === 'search_employees') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->model->searchEmployees((string) ($_GET['q'] ?? '')));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['evaluation_error'] = 'Security token expired. Please try again.';
            $this->redirect();
        }

        $action = trim((string) ($_POST['action'] ?? ''));

        switch ($action) {
            case 'create_evaluation':
                $data = $this->prepareEvaluationData(true);
                $errors = $this->validateEvaluation($data);
                if ($errors) {
                    $_SESSION['evaluation_error'] = $errors[0];
                    $this->redirect();
                }

                $evaluationId = $this->model->createEvaluation($data);
                if ($evaluationId) {
                    $criteria = $this->prepareCriteria($_POST);
                    $this->model->saveCriteria($evaluationId, $criteria);
                    $_SESSION['evaluation_success'] = 'Evaluation submitted successfully.';
                } else {
                    $_SESSION['evaluation_error'] = 'Unable to save evaluation.';
                }
                $this->redirect();
                break;

            case 'update_evaluation':
                $data = $this->prepareEvaluationData(false);
                $errors = $this->validateEvaluation($data, true);
                if ($errors) {
                    $_SESSION['evaluation_error'] = $errors[0];
                    $this->redirect('?page=evaluation&evaluation_id=' . (int) ($data['evaluation_id'] ?? 0));
                }

                $updated = $this->model->updateEvaluation($data);
                if ($updated) {
                    $criteria = $this->prepareCriteria($_POST);
                    $this->model->saveCriteria((int) $data['evaluation_id'], $criteria);
                    $_SESSION['evaluation_success'] = 'Evaluation updated successfully.';
                } else {
                    $_SESSION['evaluation_error'] = 'Unable to update evaluation.';
                }
                $this->redirect();
                break;

            case 'delete_evaluation':
                $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
                if ($evaluationId > 0 && $this->model->deleteEvaluation($evaluationId)) {
                    $_SESSION['evaluation_success'] = 'Evaluation deleted successfully.';
                } else {
                    $_SESSION['evaluation_error'] = 'Unable to delete evaluation.';
                }
                $this->redirect();
                break;

            default:
                $_SESSION['evaluation_error'] = 'Unsupported action.';
                $this->redirect();
                break;
        }
    }

    public function getDashboardData(): array
    {
        return [
            'stats' => $this->model->getDashboardStats(),
            'employees' => $this->model->getEmployees(),
            'evaluations' => $this->model->getEvaluations($this->getFilters()),
            'departments' => $this->model->getDepartments(),
            'cycles' => $this->model->getCycles(),
            'statuses' => ['Not Evaluated', 'In Progress', 'Completed'],
        ];
    }

    public function getMessages(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $messages = [
            'success' => $_SESSION['evaluation_success'] ?? '',
            'error' => $_SESSION['evaluation_error'] ?? '',
        ];

        unset($_SESSION['evaluation_success'], $_SESSION['evaluation_error']);

        return $messages;
    }

    public function getFilters(): array
    {
        return [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'department' => trim((string) ($_GET['department'] ?? '')),
            'cycle' => trim((string) ($_GET['evaluation_cycle'] ?? ($_GET['cycle'] ?? ''))),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];
    }

    public function getEmployees(): array
    {
        return $this->model->getEmployees();
    }

    public function getEvaluations(array $filters = []): array
    {
        return $this->model->getEvaluations($filters);
    }

    public function getEvaluationById(int $evaluationId): ?array
    {
        return $this->model->getEvaluationById($evaluationId);
    }

    public function getEvaluationCriteria(int $evaluationId): array
    {
        return $this->model->getEvaluationCriteria($evaluationId);
    }

    public function getPerformanceData(int $employeeId): array
    {
        return $this->model->getPerformanceData($employeeId);
    }

    public function getEmployeeSuggestions(string $term): array
    {
        return $this->model->searchEmployees($term, 10);
    }

    public function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['evaluation_csrf_token'])) {
            $_SESSION['evaluation_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['evaluation_csrf_token'];
    }

    public function isValidCsrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['evaluation_csrf_token'] ?? '', $token);
    }

    private function prepareEvaluationData(bool $isCreate): array
    {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $employee = $this->model->getEmployeeById($employeeId);

        $evaluationCycle = trim((string) ($_POST['evaluation_cycle'] ?? ''));
        $starts = trim((string) ($_POST['evaluation_period_start'] ?? ''));
        $ends = trim((string) ($_POST['evaluation_period_end'] ?? ''));
        $evaluatorName = trim((string) ($_POST['evaluator_name'] ?? ''));
        $evaluatorId = !empty($_POST['evaluator_id']) ? (int) $_POST['evaluator_id'] : null;
        $evaluationDate = trim((string) ($_POST['evaluation_date'] ?? date('Y-m-d')));
        $strengths = trim((string) ($_POST['strengths'] ?? ''));
        $improvements = trim((string) ($_POST['areas_for_improvement'] ?? ''));
        $training = trim((string) ($_POST['recommended_training'] ?? ''));
        $remarks = trim((string) ($_POST['final_remarks'] ?? ''));

        $criteria = $this->prepareCriteria($_POST);
        $overallScore = $this->calculateOverallScore($criteria);
        $overallRating = $this->calculateOverallRating($overallScore);

        $data = [
            'employee_id' => $employeeId,
            'employee_name' => $employee['employee_name'] ?? trim((string) ($_POST['employee_name'] ?? '')),
            'department' => $employee['department'] ?? trim((string) ($_POST['department'] ?? '')),
            'position' => $employee['position'] ?? trim((string) ($_POST['position'] ?? '')),
            'evaluation_cycle' => $evaluationCycle,
            'evaluation_period_start' => $starts,
            'evaluation_period_end' => $ends,
            'evaluator_id' => $evaluatorId,
            'evaluator_name' => $evaluatorName !== '' ? $evaluatorName : ($_SESSION['employee_name'] ?? 'System'),
            'evaluation_date' => $evaluationDate,
            'overall_score' => $overallScore,
            'overall_rating' => $overallRating,
            'status' => 'Completed',
            'strengths' => $strengths,
            'areas_for_improvement' => $improvements,
            'recommended_training' => $training,
            'final_remarks' => $remarks,
        ];

        if (!$isCreate) {
            $data['evaluation_id'] = (int) ($_POST['evaluation_id'] ?? 0);
        }

        return $data;
    }

    private function prepareCriteria(array $post): array
    {
        $criteria = [];
        $names = $post['criterion_name'] ?? [];
        $ratings = $post['criterion_rating'] ?? [];
        $comments = $post['criterion_comment'] ?? [];

        if (!is_array($names)) {
            return $criteria;
        }

        foreach ($names as $index => $name) {
            $criterionName = trim((string) $name);
            if ($criterionName === '') {
                continue;
            }

            $rating = (int) ($ratings[$index] ?? 0);
            $score = max(0, min(100, ($rating * 20)));
            $criteria[] = [
                'criterion_name' => $criterionName,
                'rating' => $rating,
                'score' => $score,
                'comments' => trim((string) ($comments[$index] ?? '')),
            ];
        }

        return $criteria;
    }

    private function calculateOverallScore(array $criteria): float
    {
        if (empty($criteria)) {
            return 0.0;
        }

        $total = 0;
        foreach ($criteria as $criterion) {
            $total += (int) ($criterion['rating'] ?? 0);
        }

        return round(($total / count($criteria)) * 20, 2);
    }

    private function calculateOverallRating(float $score): string
    {
        if ($score >= 90) {
            return 'Outstanding';
        }
        if ($score >= 80) {
            return 'Exceeds Expectations';
        }
        if ($score >= 70) {
            return 'Very Good';
        }
        if ($score >= 60) {
            return 'Good';
        }
        if ($score >= 40) {
            return 'Needs Improvement';
        }
        return 'Unsatisfactory';
    }

    private function validateEvaluation(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate && (int) ($data['employee_id'] ?? 0) <= 0) {
            $errors[] = 'Please select an employee.';
        }

        if ((int) ($data['employee_id'] ?? 0) <= 0) {
            $errors[] = 'Employee is required.';
        }

        if (trim((string) ($data['evaluation_cycle'] ?? '')) === '') {
            $errors[] = 'Evaluation cycle is required.';
        }

        if (trim((string) ($data['evaluation_date'] ?? '')) === '') {
            $errors[] = 'Evaluation date is required.';
        }

        if (empty($this->prepareCriteria($_POST))) {
            $errors[] = 'At least one performance criterion is required.';
        }

        return $errors;
    }

    private function redirect(?string $url = null): void
    {
        header('Location: ' . ($url ?? '?page=evaluation'));
        exit;
    }
}
