<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningPathAccess.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id=(int)($input['id']??0); $ord=(int)($input['order_index']??0);
$pdo=(new Database())->getConnection();
$pathStmt=$pdo->prepare('SELECT learning_path_id FROM ld_learning_path_item WHERE id=:id LIMIT 1');
$pathStmt->execute([':id'=>$id]);
$pathId=(int)$pathStmt->fetchColumn();
if ($id<=0||$pathId<=0||!LearningPathAccess::canManage($pdo,$pathId,(int)($_SESSION['employee_id']??0))) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'You cannot manage this learning path.']); exit; }
$pdo->prepare('UPDATE ld_learning_path_item SET order_index=:o WHERE id=:id')->execute(['o'=>$ord,'id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
