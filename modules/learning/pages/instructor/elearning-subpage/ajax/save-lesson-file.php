<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $lessonId = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
    $filePath = trim((string) ($_POST['file_path'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));

    if ($lessonId <= 0 || $filePath === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'lesson_id and file_path are required.']);
        exit;
    }

    if ($title === '') {
        $title = basename($filePath);
    }

    $stmt = $pdo->prepare("INSERT INTO ld_lesson_file (lesson_id, file_path, title) VALUES (:lesson_id, :file_path, :title)");
    $stmt->execute([
        ':lesson_id' => $lessonId,
        ':file_path' => $filePath,
        ':title' => $title,
    ]);

    $fileId = (int) $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $fileId,
        'file_path' => $filePath,
        'title' => $title,
        'message' => 'File saved successfully.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file: ' . $e->getMessage()]);
}
