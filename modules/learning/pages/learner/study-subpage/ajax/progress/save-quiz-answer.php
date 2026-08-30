<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $questionId = (int) ($_POST['question_id'] ?? 0);
    $selectedOptionId = isset($_POST['selected_option_id']) && $_POST['selected_option_id'] !== ''
        ? (int) $_POST['selected_option_id']
        : null;

    if ($sessionId <= 0 || $questionId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Session ID and Question ID are required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $quizSession = new QuizSession($pdo);

    $result = $quizSession->saveAnswer($sessionId, $learnerId, $questionId, $selectedOptionId);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save answer.',
        'error' => $e->getMessage(),
    ]);
}
exit;