<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$qid = (int)($_GET['quiz_id']??0);
$stmt = $pdo->prepare('SELECT qq.*, GROUP_CONCAT(DISTINCT qqo.id) AS option_ids FROM ld_quiz_question qq LEFT JOIN ld_quiz_question_option qqo ON qqo.question_id=qq.id WHERE qq.quiz_id=:qid GROUP BY qq.id ORDER BY qq.id ASC');
$stmt->execute(['qid'=>$qid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
