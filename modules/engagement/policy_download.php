<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if (empty($_SESSION['employee_id']) && empty($_SESSION['user_id']) && empty($_SESSION['user'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$policyId = trim((string)($_GET['id'] ?? ''));
$isLcm = !empty($_GET['lcm']);
if ($policyId === '') {
    http_response_code(400);
    exit('Policy ID is required.');
}

$basePolicyId = explode('|', $policyId, 2)[0];
$database = new Database();
$db = $database->getConnection();
$policy = null;

if ($isLcm) {
    $tables = ['lc_policies', 'lc_philippine_laws', 'lc_policy_documents', 'lc_policy_docs', 'lc_policy'];
    foreach ($tables as $table) {
        $tableExists = $db->prepare('SHOW TABLES LIKE :table_name');
        $tableExists->execute(['table_name' => $table]);
        if (!$tableExists->fetchColumn()) {
            continue;
        }

        $statement = $db->prepare("SELECT * FROM `{$table}` WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $basePolicyId]);
        $policy = $statement->fetch(PDO::FETCH_ASSOC);
        if ($policy) {
            break;
        }
    }
} else {
    $statement = $db->prepare('SELECT * FROM eer_policies WHERE eer_policy_id = :id LIMIT 1');
    $statement->execute(['id' => (int)$basePolicyId]);
    $policy = $statement->fetch(PDO::FETCH_ASSOC);
}

if (!$policy) {
    http_response_code(404);
    exit('Policy not found.');
}

$attachmentPath = (string)($policy['attachment_path'] ?? $policy['document_path'] ?? $policy['file_path'] ?? '');
$filePath = null;
if ($attachmentPath !== '') {
    $relativePath = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $attachmentPath), DIRECTORY_SEPARATOR);
    $candidatePaths = [
        __DIR__ . DIRECTORY_SEPARATOR . $relativePath,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . $relativePath,
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $relativePath,
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'compliance' . DIRECTORY_SEPARATOR . $relativePath,
    ];

    foreach ($candidatePaths as $candidatePath) {
        $resolvedPath = realpath($candidatePath);
        if ($resolvedPath && is_file($resolvedPath)) {
            $filePath = $resolvedPath;
            break;
        }
    }
}

if ($filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    $mimeType = function_exists('mime_content_type')
        ? (mime_content_type($filePath) ?: 'application/octet-stream')
        : 'application/octet-stream';

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($filePath);
    exit;
}

$title = trim((string)($policy['title'] ?? $policy['name'] ?? 'Policy'));
$content = trim((string)($policy['content'] ?? $policy['description'] ?? ''));
$downloadName = preg_replace('/[^a-z0-9]+/i', '-', $title) ?: 'policy';
$downloadBody = $title . "\r\n\r\n" . $content . "\r\n";

header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . strlen($downloadBody));
header('Content-Disposition: attachment; filename="' . $downloadName . '.txt"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo $downloadBody;
exit;
