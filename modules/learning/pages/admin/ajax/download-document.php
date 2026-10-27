<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once dirname(__FILE__, 6) . '/database/db.php';

$docId = (int)($_GET['doc_id'] ?? 0);
if ($docId <= 0) {
    http_response_code(400);
    die('Document ID is required');
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        SELECT ed.*, emp.employee_id AS emp_id
        FROM employee_documents ed
        LEFT JOIN em_employees emp ON emp.employee_id = ed.employee_id
        WHERE ed.document_id = :did
        LIMIT 1
    ");
    $stmt->execute([':did' => $docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die('Document not found');
    }

    $filePath = dirname(__DIR__, 3) . '/' . $doc['file_path'];

    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        die('File not found on disk');
    }

    $fileName = $doc['file_name'] ?: basename($filePath);
    $fileSize = $doc['file_size'] ? (int)$doc['file_size'] : filesize($filePath);
    $mimeType = $doc['mime_type'] ?: mime_content_type($filePath) ?: 'application/octet-stream';

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($filePath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    die('Download failed: ' . $e->getMessage());
}
