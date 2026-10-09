<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$id = (int)($_POST['id']??0); $response = $_POST['response']??'';
$pdo->prepare('UPDATE ld_report SET instructor_response=:r,instructor_responded_at=NOW() WHERE id=:id')->execute(['r'=>$response,'id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
