<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$uid = (int)($_GET['user_id']??0);
$stmt = $pdo->prepare('SELECT employee_id,first_name,last_name,email FROM em_employees WHERE employee_id=:id LIMIT 1');
$stmt->execute(['id'=>$uid]);
echo json_encode(['success'=>true,'item'=>$stmt->fetch(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
