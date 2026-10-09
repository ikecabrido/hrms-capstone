<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$id = (int)($_POST['id']??0);
$uid = (int)($_SESSION['employee_id']??0);
$pdo->prepare('UPDATE ld_notification SET is_read=1 WHERE id=:id AND user_id=:uid')->execute(['id'=>$id,'uid'=>$uid]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
