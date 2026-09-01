<?php
session_start();
require_once __DIR__ . '/../auth/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/autoload.php';

use App\Models\Announcement;

$fileId = (int)($_GET['id'] ?? 0);

if (!$fileId) {
    http_response_code(400);
    die('File ID is required.');
}

$announcement = new Announcement();
$file = $announcement->getSharedFileById($fileId);

if (!$file) {
    http_response_code(404);
    die('File not found.');
}

$filePath = __DIR__ . '/../' . ltrim($file['file_path'], '/\\');

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File does not exist on server.');
}

if (!is_file($filePath)) {
    http_response_code(400);
    die('Invalid file.');
}

$fileName = basename($filePath);
$fileSize = filesize($filePath);

$mimeType = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
} elseif (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
    finfo_close($finfo);
} else {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'txt' => 'text/plain',
    ];
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: attachment; filename="' . str_replace('"', '\"', $fileName) . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;
?>
