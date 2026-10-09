<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/evaluation.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $evaluation = new Evaluation($pdo);

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
    $items = $evaluation->getList($courseId);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load evaluations.',
        'error' => $e->getMessage()
    ]);
}
