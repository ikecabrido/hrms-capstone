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

    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

    if ($courseId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'items' => []]));
    }

    $stmt = $pdo->prepare("
        SELECT l.id, l.title, l.status, l.order_index
        FROM ld_lesson l
        INNER JOIN ld_module m ON l.module_id = m.id
        WHERE m.course_id = ?
        ORDER BY l.order_index ASC, l.created_at DESC
    ");

    $stmt->execute([$courseId]);
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
