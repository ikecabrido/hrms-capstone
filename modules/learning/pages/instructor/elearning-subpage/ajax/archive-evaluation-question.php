<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$id = (int)($_POST['id']??0);
$pdo->prepare('UPDATE ld_quiz_question SET status=' . ' . 'archived' . ' . ' WHERE id=:id')->execute(['id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
