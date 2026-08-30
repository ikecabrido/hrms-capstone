<?php
session_start();
require_once dirname(__FILE__, 6) . '/database/db.php';
try {
$pdo = (new Database())->getConnection();
$id = (int)($_GET['id']??0);
$stmt = $pdo->prepare('SELECT file_path,verification_code FROM ld_certificate WHERE id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);
if ($cert && !empty($cert['file_path'])) { header('Location: ' . $cert['file_path']); exit; }
http_response_code(404);
} catch (Throwable $e) { echo 'Error'; }
