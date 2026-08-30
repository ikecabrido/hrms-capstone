<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->query("SELECT id, title, description, category, created_by, module_count, lesson_count, quiz_count, created_at FROM ld_course_template ORDER BY created_at DESC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $templates]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
