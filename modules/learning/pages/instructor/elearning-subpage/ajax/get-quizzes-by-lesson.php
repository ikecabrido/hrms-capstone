<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'items' => []]));
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

    if ($lessonId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'items' => []]));
    }

    $stmt = $pdo->prepare("
        SELECT q.id, q.title, q.status
        FROM ld_quiz q
        INNER JOIN ld_lesson l ON l.module_id = q.module_id
        WHERE l.id = :lesson_id
        ORDER BY q.created_at DESC
    ");

    $stmt->execute([':lesson_id' => $lessonId]);
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
