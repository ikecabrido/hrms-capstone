<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$employeeId = (int)($_POST['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Employee ID is required']));
}

$documentName = trim($_POST['document_name'] ?? '');
if ($documentName === '') {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Document name is required']));
}

$documentType = trim($_POST['document_type'] ?? 'Other');
$category = trim($_POST['category'] ?? 'Other');
$expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $fileError = $_FILES['file']['error'] ?? 'No file uploaded';
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'File upload failed: ' . $fileError]));
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileSize = $file['size'];
// Derive a reliable mime from the uploaded file's magic bytes.
    $fileMime = $file['type'] ?: (mime_content_type($fileTmp) ?: 'application/octet-stream');

// Validate file size (max 10MB)
$maxSize = 10 * 1024 * 1024;
if ($fileSize > $maxSize) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']));
}

// Whitelist mime types
$allowedMimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/png', 'image/jpeg', 'image/gif',
    'text/plain',
];
if (!in_array($fileMime, $allowedMimes, true)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'File type not allowed']));
}

try {
    $pdo = (new Database())->getConnection();

    // Verify employee exists
    $stmt = $pdo->prepare("SELECT employee_id FROM em_employees WHERE employee_id = :eid LIMIT 1");
    $stmt->execute([':eid' => $employeeId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Employee not found']));
    }

    // Storage path: project-root/assets/documents/YYYY/MM/
    // (Matches existing employee_documents seed data which uses ../../assets/documents/)
    // The DB stores '../../assets/documents/...' which resolves from modules/learning/
    // up two levels to the project root's assets/documents/. The upload must store
    // at the same location so the download endpoint (dirname(__DIR__,3) + file_path)
    // can find it.
    $moduleRoot = dirname(__DIR__, 3);   // modules/learning/
    $docRoot = realpath($moduleRoot . '/../../assets/documents')
              ?: ($moduleRoot . '/../../assets/documents');
    if (!is_dir($docRoot)) {
        mkdir($docRoot, 0755, true);
    }
    $year = date('Y');
    $month = date('m');
    $uploadDir = "$docRoot/$year/$month";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $safeExt = in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'gif']) ? $ext : 'bin';
    $uniqueName = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $safeExt;
    $destPath = "$uploadDir/$uniqueName";

    if (!move_uploaded_file($fileTmp, $destPath)) {
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => 'Failed to save uploaded file']));
    }

    // Store relative to modules/learning/ (../../assets/documents/...) so download
    // (which does dirname(__DIR__,3) . '/' . file_path) resolves correctly.
    $relativePath = "../../assets/documents/$year/$month/$uniqueName";

    $stmt = $pdo->prepare("
        INSERT INTO employee_documents
        (employee_id, document_name, document_type, file_path, file_name, file_size, mime_type, category, expiry_date, uploaded_by)
        VALUES
        (:eid, :name, :type, :path, :fname, :size, :mime, :cat, :expiry, :uploader)
    ");
    $stmt->execute([
        ':eid' => $employeeId,
        ':name' => $documentName,
        ':type' => $documentType,
        ':path' => $relativePath,
        ':fname' => $fileName,
        ':size' => (string)$fileSize,
        ':mime' => $fileMime,
        ':cat' => $category,
        ':expiry' => $expiryDate,
        ':uploader' => (int)$_SESSION['employee_id'],
    ]);

    $documentId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $documentId,
        'message' => 'Document uploaded successfully',
        'document_name' => $documentName,
    ]);
} catch (Throwable $e) {
    // Clean up uploaded file if DB insert failed
    if (isset($destPath) && file_exists($destPath)) {
        @unlink($destPath);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
