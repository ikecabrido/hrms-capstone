<?php
/**
 * Inbound: receive-turnover-risk.php
 * Receives workforce-assessed turnover-risk records from Workforce Analytics and
 * stores them in ld_turnover_risk. The Learning turnover-risk page shows these
 * next to the engagement snapshot it computes itself (ld_engagement_snapshot).
 *
 * Source page: modules/workforce/pages/turnover-risk-indicator.php
 *
 * POST /api/inbound/receive-turnover-risk.php
 * Header: X-API-Key: <workforce-analytics key>
 * Body (JSON): {
 *   external_reference_id, employee_id, employee_name, department, position_name,
 *   risk_level, risk_score, risk_factors, recommended_action, assessment_date, created_by
 * }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 3) . '/classes/workforceintegration.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

$endpoint = 'receive-turnover-risk';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Authenticate
    ApiAuth::requireAuth($pdo, 'workforce-analytics');

    // 2. Parse payload
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    // 3. Idempotency — the sender's own record id, when provided
    $log = new IntegrationLog($pdo);
    $reference = trim((string) ($input['external_reference_id'] ?? ''));
    $extRef = $reference !== '' ? 'wa-turnover-' . $reference : null;
    if ($extRef !== null && $log->isDuplicate('workforce-analytics', $extRef)) {
        echo json_encode(['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        exit;
    }

    // 4. Store
    $integration = new WorkforceIntegration($pdo);
    $result = $integration->receiveTurnoverRisk($input);

    if (!$result['success']) {
        http_response_code(422);
        $log->logCall('inbound', 'workforce-analytics', $endpoint, 'failed', $input, $result['message']);
        echo json_encode(['success' => false, 'error' => $result['message']]);
        exit;
    }

    if ($extRef !== null && !$result['duplicate']) {
        $log->markProcessed('workforce-analytics', $extRef, 'turnover_risk');
    }
    $log->logCall('inbound', 'workforce-analytics', $endpoint, 'success', $input);

    echo json_encode([
        'success'   => true,
        'message'   => $result['message'],
        'id'        => $result['id'],
        'duplicate' => $result['duplicate'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'workforce-analytics', $endpoint, 'failed', $input ?? null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
