<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$pdo->prepare('UPDATE ld_announcement SET title=:t,message=:m WHERE id=:id')->execute(['t'=>$input['title']??'','m'=>$input['message']??'','id'=>(int)($input['id']??0)]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
