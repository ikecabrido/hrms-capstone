<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$s = [];
foreach (['enrolled','in_progress','completed','withdrawn'] as $st) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ld_enrollment WHERE status=:s');
    $stmt->execute(['s'=>$st]);
    $s[$st] = (int)$stmt->fetchColumn();
}
echo json_encode(['success'=>true,'stats'=>$s]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
