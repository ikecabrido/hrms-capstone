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

    $lessonId = isset($_GET['lesson_id']) ? (int) $_GET['lesson_id'] : 0;

    if ($lessonId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'lesson_id is required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, lesson_id, file_path, title, uploaded_at FROM ld_lesson_file WHERE lesson_id = :lesson_id ORDER BY uploaded_at ASC");
    $stmt->execute([':lesson_id' => $lessonId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $files]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get files: ' . $e->getMessage()]);
}
