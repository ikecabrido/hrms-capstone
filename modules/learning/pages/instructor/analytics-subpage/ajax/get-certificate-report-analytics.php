<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$stmt = $pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status=' . ' . 'active' . ' . ' THEN 1 ELSE 0 END) AS active FROM ld_certificate');
echo json_encode(['success'=>true,'stats'=>$stmt->fetch(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
