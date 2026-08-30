<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$eid = (int)($_GET['evaluation_id']??0);
$stmt = $pdo->prepare('SELECT * FROM ld_quiz_question WHERE quiz_id=:eid AND module_id=0 ORDER BY id ASC');
$stmt->execute(['eid'=>$eid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
