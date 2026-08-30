<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$c = [];
foreach (['ld_course'=>'course','ld_module'=>'module','ld_lesson'=>'lesson','ld_quiz'=>'quiz'] as $tbl=>$k) {
    $c[$k] = (int)$pdo->query('SELECT COUNT(*) FROM ' . $tbl . ' WHERE status=' . ' . 'archived' . ')->fetchColumn();
}
echo json_encode(['success'=>true,'counts'=>$c]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
