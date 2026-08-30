<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
foreach (($input['items']??[]) as $item) { $pdo->prepare('UPDATE ld_learning_path_item SET order_index=:o WHERE id=:id')->execute(['o'=>(int)($item['order_index']??0),'id'=>(int)($item['id']??0)]); }
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
