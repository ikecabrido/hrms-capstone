<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/quiz.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $quiz = new Quiz($pdo);

    $moduleId = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;
    $items = $quiz->getList($moduleId);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load quizzes.',
        'error' => $e->getMessage()
    ]);
}
