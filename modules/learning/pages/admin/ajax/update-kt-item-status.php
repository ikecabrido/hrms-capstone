<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    $itemId = (int)($input['item_id'] ?? 0);
    $newStatus = $input['status'] ?? '';
    $currentEmployeeId = $_SESSION['employee_id'] ?? 0;

    if ($itemId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Item ID required.']);
        exit;
    }

    $validStatuses = ['pending', 'in_progress', 'completed'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status.']);
        exit;
    }

    // Verify the current user is the successor on the plan
    $checkStmt = $pdo->prepare("
        SELECT i.id, p.successor_id, p.status AS plan_status
        FROM exit_knowledge_transfer_items i
        JOIN exit_knowledge_transfer_plans p ON p.id = i.plan_id
        WHERE i.id = :iid
    ");
    $checkStmt->execute([':iid' => $itemId]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Item not found.']);
        exit;
    }

    if ((int)$row['successor_id'] !== $currentEmployeeId) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to update this item.']);
        exit;
    }

    if ($row['plan_status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Cannot update items on a closed plan.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE exit_knowledge_transfer_items SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $itemId]);

    echo json_encode(['success' => true, 'message' => 'Item status updated.']);
} catch (Throwable $e) {
    error_log('update-kt-item-status error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
