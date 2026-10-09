<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningPathAccess.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$items = $input['items'] ?? [];
if (!is_array($items)) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Invalid item list.']); exit; }
$pdo=(new Database())->getConnection();
$itemIds=array_values(array_unique(array_filter(array_map(static fn($item)=>(int)($item['id']??0),$items))));
if ($itemIds) {
	$placeholders=implode(',',array_fill(0,count($itemIds),'?'));
	$pathStmt=$pdo->prepare("SELECT DISTINCT learning_path_id FROM ld_learning_path_item WHERE id IN ($placeholders)");
	$pathStmt->execute($itemIds);
	$pathIds=array_map('intval',$pathStmt->fetchAll(PDO::FETCH_COLUMN));
	if (count($pathIds)!==1||!LearningPathAccess::canManage($pdo,$pathIds[0],(int)($_SESSION['employee_id']??0))) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'You cannot manage this learning path.']); exit; }
	$updateStmt=$pdo->prepare('UPDATE ld_learning_path_item SET order_index=:order_index WHERE id=:id AND learning_path_id=:path_id');
	foreach ($items as $item) { $updateStmt->execute([':order_index'=>(int)($item['order_index']??0),':id'=>(int)($item['id']??0),':path_id'=>$pathIds[0]]); }
}
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
