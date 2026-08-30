<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$pdo->prepare('INSERT INTO ld_quiz_question (quiz_id,module_id,question_text,question_type,status) VALUES (:qid,0,:qt,:qt,' . ' . 'active' . ' . ')')->execute(['qid'=>$input['quiz_id']??0,'qt'=>$input['question_text']??'','qt'=>$input['question_type']??'rating']);
echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
