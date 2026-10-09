<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/grade.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $grade = new Grade($pdo);

    $items = $grade->getByLearner($learnerId);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'course_id' => (int) $item['course_id'],
                'course_title' => $item['course_title'],
                'final_score' => (float) $item['final_score'],
                'status' => $item['status'],
                'issued_at' => $item['issued_at'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load grades.',
        'error' => $e->getMessage(),
    ]);
}