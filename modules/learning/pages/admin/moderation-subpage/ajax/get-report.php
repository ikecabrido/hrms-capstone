<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$status = $_GET['status'] ?? 'pending';
$stmt = $pdo->prepare('SELECT r.*, CONCAT(e.first_name,chr(32),e.last_name) AS reporter_name FROM ld_report r LEFT JOIN em_employees e ON e.employee_id=r.learner_id WHERE r.status=:s ORDER BY r.created_at DESC');
$stmt->execute(['s'=>$status]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
