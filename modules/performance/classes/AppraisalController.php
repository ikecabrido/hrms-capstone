<?php
require_once __DIR__ . '/AppraisalService.php';

class AppraisalController
{
    private AppraisalService $appraisalService;

    public function __construct($pdo = null)
    {
        $this->appraisalService = new AppraisalService($pdo);
    }

    public function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['appraisal_csrf_token'])) {
            $_SESSION['appraisal_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['appraisal_csrf_token'];
    }

    public function isValidCsrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['appraisal_csrf_token'] ?? '', $token);
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['appraisal_error'] = 'Security token expired. Please retry.';
            $this->redirect();
        }

        $action = $_POST['action'] ?? '';
        $currentUserName = $_SESSION['employee_name'] ?? 'System';
        $currentUserId = $_SESSION['employee_id'] ?? '';

        try {
            switch ($action) {
                case 'create_cycle':
                    if ($this->validateCycleData($_POST) && $this->appraisalService->createReviewCycle($_POST)) {
                        $_SESSION['appraisal_success'] = 'Review cycle created successfully.';
                    } else {
                        $_SESSION['appraisal_error'] = 'Unable to create review cycle.';
                    }
                    $this->redirect();
                    break;

                case 'create_appraisal':
                    $data = $this->prepareAppraisalData($_POST, $currentUserId, $currentUserName);
                    if ($this->validateAppraisalData($data) && $this->appraisalService->createAppraisal($data)) {
                        $_SESSION['appraisal_success'] = 'Appraisal created successfully.';
                    } else {
                        $_SESSION['appraisal_error'] = 'Unable to create appraisal.';
                    }
                    $this->redirect();
                    break;

                case 'update_appraisal':
                    $data = $this->prepareAppraisalData($_POST, $currentUserId, $currentUserName, true);
                    if ($this->validateAppraisalData($data, true) && $this->appraisalService->updateAppraisal($data)) {
                        $_SESSION['appraisal_success'] = 'Appraisal updated successfully.';
                    } else {
                        $_SESSION['appraisal_error'] = 'Unable to update appraisal.';
                    }
                    $this->redirect(isset($_POST['appraisal_id']) ? '?page=appraisals-review&appraisal_id=' . (int) $_POST['appraisal_id'] : null);
                    break;

                case 'update_status':
                    $appraisalId = (int) ($_POST['appraisal_id'] ?? 0);
                    $status = trim((string) ($_POST['status'] ?? ''));
                    $comment = trim((string) ($_POST['status_comment'] ?? ''));
                    if ($appraisalId > 0 && $status !== '' && $this->appraisalService->updateStatus($appraisalId, $status, $currentUserName, $comment)) {
                        $_SESSION['appraisal_success'] = 'Appraisal status updated successfully.';
                    } else {
                        $_SESSION['appraisal_error'] = 'Unable to update appraisal status.';
                    }
                    $this->redirect('?page=appraisals-review&appraisal_id=' . $appraisalId);
                    break;

                case 'save_ratings':
                    $appraisalId = (int) ($_POST['appraisal_id'] ?? 0);
                    $items = $this->parseRatingItems($_POST);
                    if ($appraisalId > 0 && $this->appraisalService->saveAppraisalItems($appraisalId, $items, $currentUserName)) {
                        $_SESSION['appraisal_success'] = 'Appraisal ratings saved successfully.';
                    } else {
                        $_SESSION['appraisal_error'] = 'Unable to save appraisal ratings.';
                    }
                    $this->redirect('?page=appraisals-review&appraisal_id=' . $appraisalId);
                    break;

                default:
                    $this->redirect();
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['appraisal_error'] = 'An unexpected error occurred.';
            $this->redirect();
        }
    }

    private function validateCycleData(array $data): bool
    {
        if (trim((string) ($data['title'] ?? '')) === '') {
            $_SESSION['appraisal_error'] = 'Cycle title is required.';
            return false;
        }

        return true;
    }

    private function validateAppraisalData(array $data, bool $isUpdate = false): bool
    {
        if ($isUpdate && (int) ($data['appraisal_id'] ?? 0) <= 0) {
            $_SESSION['appraisal_error'] = 'Invalid appraisal selected.';
            return false;
        }

        if ((int) ($data['employee_id'] ?? 0) <= 0) {
            $_SESSION['appraisal_error'] = 'Employee must be selected.';
            return false;
        }

        if (trim((string) ($data['employee_name'] ?? '')) === '') {
            $_SESSION['appraisal_error'] = 'Employee name is required.';
            return false;
        }

        return true;
    }

    private function prepareAppraisalData(array $post, $currentUserId, string $currentUserName, bool $isUpdate = false): array
    {
        $employees = $this->appraisalService->getEmployees();
        $employeeId = (int) ($post['employee_id'] ?? 0);
        $employeeName = trim((string) ($post['employee_name'] ?? ''));
        $department = trim((string) ($post['department'] ?? ''));
        $reviewerId = trim((string) ($post['reviewer_id'] ?? ''));
        $reviewerName = trim((string) ($post['reviewer_name'] ?? ''));

        foreach ($employees as $employee) {
            if ((int) ($employee['employee_id'] ?? 0) === $employeeId) {
                if ($employeeName === '') {
                    $employeeName = trim((string) ($employee['employee_name'] ?? ''));
                }
                if ($department === '') {
                    $department = trim((string) ($employee['department'] ?? ''));
                }
                break;
            }
        }

        if ($reviewerId === '' && $reviewerName === '') {
            $reviewerId = (string) $currentUserId;
            $reviewerName = $currentUserName;
        }

        $data = [
            'employee_id' => $employeeId,
            'employee_name' => $employeeName,
            'department' => $department,
            'reviewer_id' => $reviewerId !== '' ? (int) $reviewerId : null,
            'reviewer_name' => $reviewerName !== '' ? $reviewerName : $currentUserName,
            'status' => trim((string) ($post['status'] ?? 'Not Started')),
            'overall_rating' => ($post['overall_rating'] ?? '') !== '' ? (float) $post['overall_rating'] : null,
            'due_date' => trim((string) ($post['due_date'] ?? '')),
            'review_cycle_id' => !empty($post['review_cycle_id']) ? (int) $post['review_cycle_id'] : null,
            'created_by' => $currentUserName,
            'updated_by' => $currentUserName,
        ];

        if ($isUpdate) {
            $data['appraisal_id'] = (int) ($post['appraisal_id'] ?? 0);
        }

        return $data;
    }

    private function parseRatingItems(array $post): array
    {
        $criteria = $post['criterion'] ?? [];
        $ratings = $post['rating'] ?? [];
        $comments = $post['comments'] ?? [];
        $items = [];

        if (!is_array($criteria)) {
            return $items;
        }

        foreach ($criteria as $index => $criterion) {
            $items[] = [
                'criterion' => $criterion,
                'rating' => $ratings[$index] ?? '',
                'comments' => $comments[$index] ?? '',
            ];
        }

        return $items;
    }

    private function redirect(?string $url = null): void
    {
        header('Location: ' . ($url ?? '?page=appraisals-review'));
        exit;
    }

    public function getDashboardData(): array
    {
        return [
            'stats' => $this->appraisalService->getDashboardStats(),
            'statusSummary' => $this->appraisalService->getStatusSummary(),
            'cycles' => $this->appraisalService->getReviewCycles(),
        ];
    }

    public function getEmployees(): array
    {
        return $this->appraisalService->getEmployees();
    }

    public function getAppraisals(array $filters = []): array
    {
        return $this->appraisalService->getAppraisals($filters);
    }

    public function getSelectedAppraisal(int $appraisalId): ?array
    {
        return $this->appraisalService->getAppraisalById($appraisalId);
    }

    public function getAppraisalItems(int $appraisalId): array
    {
        $items = $this->appraisalService->getAppraisalItems($appraisalId);
        if (!empty($items)) {
            return $items;
        }

        $defaults = $this->appraisalService->getDefaultCriteria();
        return array_map(static fn($criterion) => [
            'criterion' => $criterion,
            'rating' => null,
            'comments' => '',
        ], $defaults);
    }

    public function getAppraisalHistory(int $appraisalId): array
    {
        return $this->appraisalService->getAppraisalHistory($appraisalId);
    }

    public function getReviewCycles(): array
    {
        return $this->appraisalService->getReviewCycles();
    }

    public function getMessages(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $messages = [
            'success' => $_SESSION['appraisal_success'] ?? '',
            'error' => $_SESSION['appraisal_error'] ?? '',
        ];

        unset($_SESSION['appraisal_success'], $_SESSION['appraisal_error']);
        return $messages;
    }
}
