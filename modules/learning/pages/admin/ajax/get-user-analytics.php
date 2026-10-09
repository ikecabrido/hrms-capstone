<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$uid = (int)($_GET['user_id']??0);
if ($uid<=0) { http_response_code(422); echo json_encode(['success'=>false]); exit; }
$stmt = $pdo->prepare('SELECT en.*, c.title FROM ld_enrollment en JOIN ld_course c ON c.id=en.course_id WHERE en.learner_id=:uid ORDER BY en.enrolled_at DESC');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
