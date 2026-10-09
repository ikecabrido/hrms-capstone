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
        SELECT id, title, status, order_index
        FROM ld_module
        WHERE course_id = ?
        ORDER BY order_index ASC, created_at DESC
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
