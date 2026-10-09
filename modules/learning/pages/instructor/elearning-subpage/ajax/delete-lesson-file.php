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

    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = isset($input['id']) ? (int) $input['id'] : 0;

    if ($fileId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'File ID is required.']);
        exit;
    }

    // Get file path before deleting so we can remove the physical file
    $stmt = $pdo->prepare("SELECT file_path FROM ld_lesson_file WHERE id = :id");
    $stmt->execute([':id' => $fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        exit;
    }

    // Delete the record
    $stmt = $pdo->prepare("DELETE FROM ld_lesson_file WHERE id = :id");
    $stmt->execute([':id' => $fileId]);

    // Try to delete the physical file (best effort)
    $physicalPath = dirname(__FILE__, 6) . '/' . $file['file_path'];
    if (file_exists($physicalPath)) {
        @unlink($physicalPath);
    }

    echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete file: ' . $e->getMessage()]);
}
