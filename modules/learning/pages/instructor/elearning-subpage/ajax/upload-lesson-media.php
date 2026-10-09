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

// Determine upload directory relative to project root
$uploadDir = dirname(__FILE__, 5) . '/assets/uploads/lesson-media/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
$allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);

$mediaType = $_POST['media_type'] ?? 'image';

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
        ];
        $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($errorCodes[$errCode] ?? 'Unknown upload error.');
    }

    $file = $_FILES['file'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new Exception('File type "' . $mimeType . '" is not allowed. Allowed: images (JPEG, PNG, GIF, WebP, SVG) and videos (MP4, WebM, OGG).');
    }

    // Max sizes: 10MB for images, 50MB for videos
    $maxSize = in_array($mimeType, $allowedImageTypes) ? 10 * 1024 * 1024 : 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $maxMB = $maxSize / (1024 * 1024);
        throw new Exception('File size exceeds the limit of ' . $maxMB . ' MB.');
    }

    // Generate a unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    if ($safeExt === '') {
        $safeExt = in_array($mimeType, $allowedVideoTypes) ? 'mp4' : 'jpg';
    }
    $fileName = bin2hex(random_bytes(16)) . '.' . $safeExt;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Return the relative URL for use in the editor
    $relativeUrl = 'assets/uploads/lesson-media/' . $fileName;

    echo json_encode([
        'success'  => true,
        'url'      => $relativeUrl,
        'fileName' => $file['name'],
        'mimeType' => $mimeType,
        'size'     => $file['size'],
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
    exit;
}
