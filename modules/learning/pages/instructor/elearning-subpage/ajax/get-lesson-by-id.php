<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/lesson.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $lesson = new Lesson($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Lesson ID required']);
        exit;
    }

    $lessonData = $lesson->getById($id);

    if (!$lessonData) {
        http_response_code(404);
        echo json_encode(['error' => 'Lesson not found']);
        exit;
    }

    // Get real counts from database
    $quizCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_quiz WHERE lesson_id = {$id}")->fetchColumn();
    $lessonData['quiz_count'] = $quizCount;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $lessonData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

