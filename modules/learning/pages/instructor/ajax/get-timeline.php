<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$uid = (int)($_SESSION['employee_id']??0);
$stmt = $pdo->prepare('SELECT * FROM ld_audit_log WHERE user_id=:uid ORDER BY created_at DESC LIMIT 50');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
