<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    $planId = (int)($input['plan_id'] ?? 0);
    $lpId = !empty($input['lp_id']) ? (int) $input['lp_id'] : null;

    if ($planId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Plan ID required.']);
        exit;
    }

    $pdo->beginTransaction();

    // Delete associated learning path items and path
    if ($lpId) {
        $pdo->prepare('DELETE FROM ld_learning_path_item WHERE learning_path_id = :lpid')->execute([':lpid' => $lpId]);
        $pdo->prepare('DELETE FROM ld_learning_path WHERE id = :lpid')->execute([':lpid' => $lpId]);
    }

    // Delete transfer items
    $pdo->prepare('DELETE FROM exit_knowledge_transfer_items WHERE plan_id = :pid')->execute([':pid' => $planId]);

    // Delete plan
    $pdo->prepare('DELETE FROM exit_knowledge_transfer_plans WHERE id = :pid')->execute([':pid' => $planId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Plan deleted.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('delete-kt-plan error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
