<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$limit = min(100, (int)($_GET['limit']??50));
$stmt = $pdo->prepare('SELECT al.*, CONCAT(e.first_name,chr(32),e.last_name) AS user_name FROM ld_audit_log al LEFT JOIN em_employees e ON e.employee_id=al.user_id ORDER BY al.created_at DESC LIMIT :l');
$stmt->bindValue('l', $limit, PDO::PARAM_INT);
$stmt->execute();
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
