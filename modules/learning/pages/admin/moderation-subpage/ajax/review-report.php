<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$id = (int)($_POST['id']??0);
$uid = (int)($_SESSION['employee_id']??0);
$pdo->prepare('UPDATE ld_report SET status=' . chr(39) . 'reviewed' . chr(39) . ',reviewed_by=:uid,reviewed_at=NOW() WHERE id=:id')->execute(['uid'=>$uid,'id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
