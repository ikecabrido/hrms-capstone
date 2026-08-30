<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/module.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $module = new Module($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Module ID required']);
        exit;
    }

    $moduleData = $module->getById($id);

    if (!$moduleData) {
        http_response_code(404);
        echo json_encode(['error' => 'Module not found']);
        exit;
    }

    // Get real counts from database
    $lessonCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_lesson WHERE module_id = {$id}")->fetchColumn();
    $quizCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_quiz WHERE module_id = {$id}")->fetchColumn();
    $moduleData['lesson_count'] = $lessonCount;
    $moduleData['quiz_count'] = $quizCount;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $moduleData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
