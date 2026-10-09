<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $sessionId = (int) ($_GET['session_id'] ?? 0);

    if ($sessionId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Session ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $quizSession = new QuizSession($pdo);

    $result = $quizSession->getResult($sessionId, $learnerId);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(404);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load evaluation result.',
        'error' => $e->getMessage(),
    ]);
}