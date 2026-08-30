<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$type=$_POST['type']??''; $id=(int)($_POST['id']??0);
$tables=['course'=>'ld_course','module'=>'ld_module','lesson'=>'ld_lesson','quiz'=>'ld_quiz','program'=>'ld_program','skill'=>'ld_skill'];
if (!isset($tables[$type])||$id<=0) { http_response_code(422); echo json_encode(['success'=>false]); exit; }
$pdo->prepare('UPDATE ' . $tables[$type] . ' SET status=' . chr(39) . 'active' . chr(39) . ',updated_at=NOW() WHERE id=:id')->execute(['id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
