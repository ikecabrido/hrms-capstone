<?php
/**
 * Inbound: receive-training-engagement.php
 * Receives training-engagement records from Workforce Analytics and stores them in
 * ld_training_engagement, where the Learning admin page reviews and follows them up.
 *
 * Source page: modules/workforce/pages/turnover-risk-indicator.php
 *              ("Record training engagement")
 *
 * POST /api/inbound/receive-training-engagement.php
 * Header: X-API-Key: <workforce-analytics key>
 * Body (JSON): {
 *   external_reference_id, learner_team_type, learner_id, learner_name, department,
 *   training_activity, engagement_status, engagement_data, recommended_action, notes, created_by
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

$endpoint = 'receive-training-engagement';

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
    $extRef = $reference !== '' ? 'wa-engagement-' . $reference : null;
    if ($extRef !== null && $log->isDuplicate('workforce-analytics', $extRef)) {
        echo json_encode(['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        exit;
    }

    // 4. Store
    $integration = new WorkforceIntegration($pdo);
    $result = $integration->receiveTrainingEngagement($input);

    if (!$result['success']) {
        http_response_code(422);
        $log->logCall('inbound', 'workforce-analytics', $endpoint, 'failed', $input, $result['message']);
        echo json_encode(['success' => false, 'error' => $result['message']]);
        exit;
    }

    if ($extRef !== null && !$result['duplicate']) {
        $log->markProcessed('workforce-analytics', $extRef, 'training_engagement');
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
