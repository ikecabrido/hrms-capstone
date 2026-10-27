<?php
/**
 * AJAX — move a workforce-integration record along its status flow.
 *
 * POST JSON: { dataset: engagement|turnover_risk|competency|reporting, id: int, status: string }
 * Header:    X-CSRF-Token (window.CSRF_TOKEN from index.php)
 *
 * Used by pages/admin/workforce-integration.php.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/csrf.php';
require_once dirname(__FILE__, 4) . '/classes/workforceintegration.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Admins only — this is the L&D side of a cross-module handoff.
    if (empty($_SESSION['employee_id']) || (($_SESSION['learning_role'] ?? '') !== 'admin' && ($_SESSION['is_admin'] ?? 0) != 1)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only administrators can update these records.']);
        exit;
    }

    CSRF::requireValid();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
        exit;
    }

    $dataset = strtolower(trim((string) ($input['dataset'] ?? '')));
    $recordId = (int) ($input['id'] ?? 0);
    $status = strtolower(trim((string) ($input['status'] ?? '')));

    // Whoever is logged in owns the entry in the audit trail.
    $integration = new WorkforceIntegration($pdo);
    $result = $integration->updateStatus($dataset, $recordId, $status, (int) ($_SESSION['employee_id'] ?? 0));

    if (!$result['success']) {
        http_response_code(422);
    }

    echo json_encode($result);
} catch (Throwable $e) {
    error_log('workforce-integration-update-status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
