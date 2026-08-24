<?php
require_once __DIR__ . '/../controller/FeedbackDashboardController.php';

class FeedbackController
{
    private FeedbackDashboardController $dashboardController;

    public function __construct($pdo = null)
    {
        $this->dashboardController = new FeedbackDashboardController($pdo);
    }

    public function handleRequest(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['employee_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
            exit;
        }
    }

    public function getDashboardData(array $filters = []): array
    {
        return $this->dashboardController->getDashboardData($filters);
    }

    public function getMessages(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $success = $_SESSION['feedback_success'] ?? '';
        $error = $_SESSION['feedback_error'] ?? '';
        unset($_SESSION['feedback_success'], $_SESSION['feedback_error']);

        return ['success' => $success, 'error' => $error];
    }
}
