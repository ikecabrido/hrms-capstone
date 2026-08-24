<?php
require_once __DIR__ . '/Goal.php';
require_once __DIR__ . '/GoalService.php';

class GoalController
{
    private GoalService $goalService;

    public function __construct($pdo = null)
    {
        $this->goalService = new GoalService($pdo);
    }

    public function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['goal_csrf_token'])) {
            $_SESSION['goal_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['goal_csrf_token'];
    }

    public function isValidCsrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['goal_csrf_token'] ?? '', $token);
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['goal_error'] = 'Security token expired. Please retry.';
            $this->redirect();
        }

        $action = $_POST['action'] ?? '';
        $currentUserId = $_SESSION['employee_id'] ?? '';
        $currentUserName = $_SESSION['employee_name'] ?? 'System';

        try {
            switch ($action) {
                case 'create_goal':
                    $data = $_POST;
                    $data['supervisor_id'] = $data['supervisor_id'] ?? $currentUserId;
                    $data['supervisor_name'] = $data['supervisor_name'] ?? $currentUserName;
                    if ($this->validateGoalData($data)) {
                        $result = $this->goalService->getGoals();
                        $saved = $this->goalService->getOverview();
                        $goalModel = new Goal();
                        if ($goalModel->saveGoal($data)) {
                            $_SESSION['goal_success'] = 'Goal created successfully.';
                            $this->redirect();
                        }
                    }
                    $_SESSION['goal_error'] = 'Unable to save the goal.';
                    $this->redirect();
                    break;

                case 'update_goal':
                    $data = $_POST;
                    $data['supervisor_id'] = $data['supervisor_id'] ?? $currentUserId;
                    $data['supervisor_name'] = $data['supervisor_name'] ?? $currentUserName;
                    if ($this->validateGoalData($data, true)) {
                        $goalModel = new Goal();
                        if ($goalModel->updateGoal($data)) {
                            $_SESSION['goal_success'] = 'Goal updated successfully.';
                            $this->redirect();
                        }
                    }
                    $_SESSION['goal_error'] = 'Unable to update the goal.';
                    $this->redirect();
                    break;

                case 'update_progress':
                    $goalId = (int) ($_POST['goal_id'] ?? 0);
                    $progress = max(0, min(100, (int) ($_POST['progress_percentage'] ?? 0)));
                    $comment = trim((string) ($_POST['progress_notes'] ?? ''));
                    $goalModel = new Goal();
                    if ($goalId > 0 && $goalModel->updateProgress($goalId, $progress, $comment, $currentUserName)) {
                        $_SESSION['goal_success'] = 'Goal progress updated successfully.';
                    } else {
                        $_SESSION['goal_error'] = 'Unable to update goal progress.';
                    }
                    $this->redirect();
                    break;

                case 'update_status':
                    $goalId = (int) ($_POST['goal_id'] ?? 0);
                    $status = trim((string) ($_POST['status'] ?? ''));
                    $comment = trim((string) ($_POST['status_comment'] ?? ''));
                    $goalModel = new Goal();
                    if ($goalId > 0 && $status !== '' && $goalModel->updateStatus($goalId, $status, $comment)) {
                        $_SESSION['goal_success'] = 'Goal status updated successfully.';
                    } else {
                        $_SESSION['goal_error'] = 'Unable to update the goal status.';
                    }
                    $this->redirect();
                    break;

                default:
                    $this->redirect();
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['goal_error'] = 'An unexpected error occurred.';
            $this->redirect();
        }
    }

    private function validateGoalData(array $data, bool $isUpdate = false): bool
    {
        $employeeId = trim((string) ($data['employee_id'] ?? ''));
        $goalTitle = trim((string) ($data['goal_title'] ?? ''));
        $goalDescription = trim((string) ($data['goal_description'] ?? ''));
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $dueDate = trim((string) ($data['due_date'] ?? ''));
        $progressPercent = (int) ($data['progress_percentage'] ?? 0);

        if ($goalTitle === '') {
            $_SESSION['goal_error'] = 'Goal title cannot be empty.';
            return false;
        }

        if ($employeeId === '') {
            $_SESSION['goal_error'] = 'Employee must be selected.';
            return false;
        }

        if ($goalDescription === '') {
            $_SESSION['goal_error'] = 'Goal description is required.';
            return false;
        }

        if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $_SESSION['goal_error'] = 'Start date must be valid.';
            return false;
        }

        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $_SESSION['goal_error'] = 'Target date must be valid.';
            return false;
        }

        if ($startDate !== '' && $dueDate !== '' && strtotime($dueDate) < strtotime($startDate)) {
            $_SESSION['goal_error'] = 'Target date cannot be earlier than the start date.';
            return false;
        }

        if ($progressPercent < 0 || $progressPercent > 100) {
            $_SESSION['goal_error'] = 'Progress must remain within the range of 0 to 100.';
            return false;
        }

        return true;
    }

    public function redirect(): void
    {
        $location = '?page=goal-setting';
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryParts);
            unset($queryParts['goal_id']);
            $location = '?page=goal-setting' . (!empty($queryParts) ? '&' . http_build_query($queryParts) : '');
        }

        header('Location: ' . $location);
        exit;
    }

    public function getDashboardData(): array
    {
        return $this->goalService->getOverview();
    }

    public function getGoals(array $filters = []): array
    {
        return $this->goalService->getGoals($filters);
    }

    public function getEmployees(): array
    {
        return $this->goalService->getEmployees();
    }

    public function getSelectedGoal(?int $goalId = null): ?array
    {
        $goalId = $goalId ?? ($_GET['goal_id'] ?? null);
        if ($goalId === null || (int) $goalId <= 0) {
            return null;
        }

        return $this->goalService->getGoalById((int) $goalId);
    }

    public function getSelectedGoalHistory(int $goalId): array
    {
        return $this->goalService->getGoalHistory($goalId);
    }

    public function getSelectedGoalProgressEntries(int $goalId): array
    {
        return $this->goalService->getGoalProgressEntries($goalId);
    }
}
