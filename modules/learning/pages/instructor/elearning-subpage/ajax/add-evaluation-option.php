<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$pdo->prepare('INSERT INTO ld_quiz_question_option (question_id,option_text,is_correct) VALUES (:qid,:ot,0)')->execute(['qid'=>$input['question_id']??0,'ot'=>$input['option_text']??'']);
echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
