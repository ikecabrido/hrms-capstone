<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/quiz.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $quiz = new Quiz($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Quiz ID required']);
        exit;
    }

    $quizData = $quiz->getById($id);

    if (!$quizData) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found']);
        exit;
    }

    // Get real counts from database
    $attemptCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_grade WHERE quiz_id = {$id}")->fetchColumn();
    $passCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_grade WHERE quiz_id = {$id} AND status = 'passed'")->fetchColumn();
    $quizData['attempt_count'] = $attemptCount;
    $quizData['pass_count'] = $passCount;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $quizData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

