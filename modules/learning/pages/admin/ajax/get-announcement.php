<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$stmt = $pdo->prepare('SELECT * FROM ld_announcement WHERE expires_at IS NULL OR expires_at > NOW() ORDER BY created_at DESC LIMIT 20');
$stmt->execute();
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
