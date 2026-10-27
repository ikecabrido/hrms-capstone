<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 6) . '/database/db.php';
try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $itemId = (int)($input['item_id'] ?? 0);
    if ($itemId <= 0) { http_response_code(422); echo json_encode(['success'=>false, 'message'=>'Item ID required.']); exit; }
    $pdo->prepare('DELETE FROM ld_learning_path_item WHERE id = :id')->execute([':id' => $itemId]);
    echo json_encode(['success' => true, 'message' => 'Item removed.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
