<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$lpId=(int)($input['learning_path_id']??0); $itemType=$input['item_type']??''; $refId=(int)($input['reference_id']??0); $ord=(int)($input['order_index']??0);
if ($lpId<=0||empty($itemType)||$refId<=0) { http_response_code(422); echo json_encode(['success'=>false]); exit; }
$pdo->prepare('INSERT INTO ld_learning_path_item (learning_path_id,item_type,reference_id,order_index) VALUES (:lp,:t,:r,:o)')->execute(['lp'=>$lpId,'t'=>$itemType,'r'=>$refId,'o'=>$ord]);
echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
