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

    // Include the parent module name for display (the parent module selector)
    if (!empty($lessonData['module_id'])) {
        $modStmt = $pdo->prepare('SELECT title FROM ld_module WHERE id = :mid LIMIT 1');
        $modStmt->execute([':mid' => (int) $lessonData['module_id']]);
        $modTitle = $modStmt->fetchColumn();
        $lessonData['module_name'] = $modTitle ?: '';
    } else {
        $lessonData['module_name'] = '';
    }

    // ld_quiz stores quizzes at module level (no lesson_id column), so there is
    // no reliable per-lesson quiz count available here.
    $lessonData['quiz_count'] = 0;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $lessonData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

