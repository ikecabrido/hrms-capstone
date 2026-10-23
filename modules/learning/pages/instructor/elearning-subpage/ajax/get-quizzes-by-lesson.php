<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'items' => []]));
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $lessonId = isset($_GET['lesson_id']) ? (int) $_GET['lesson_id'] : 0;
    $moduleId = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;

    if ($lessonId > 0) {
        $stmt = $pdo->prepare("
            SELECT q.id, q.title, q.status
            FROM ld_quiz q
            INNER JOIN ld_lesson l ON l.module_id = q.module_id
            WHERE l.id = :lesson_id
            ORDER BY q.created_at DESC
        ");
        $stmt->execute([':lesson_id' => $lessonId]);
    } elseif ($moduleId > 0) {
        // The structure builder also lists quizzes that belong directly to a
        // module. Previously it sent lesson_id=0 for these, which this endpoint
        // rejected with a 400 and surfaced as a console/network error.
        $stmt = $pdo->prepare("
            SELECT q.id, q.title, q.status
            FROM ld_quiz q
            WHERE q.module_id = :module_id
            ORDER BY q.created_at DESC
        ");
        $stmt->execute([':module_id' => $moduleId]);
    } else {
        http_response_code(400);
        die(json_encode(['success' => false, 'items' => []]));
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'items' => [], 'error' => $e->getMessage()]);
}
?>
