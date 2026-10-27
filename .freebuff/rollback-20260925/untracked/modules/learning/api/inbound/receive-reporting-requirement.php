<?php
/**
 * Inbound: receive-reporting-requirement.php
 * Receives reporting requirements / targets from Workforce Analytics and stores
 * them in ld_reporting_requirement.
 *
 * The Workforce module also writes this table directly (via
 * Employee::saveLearningReportingRequirement()); this endpoint is the API path for
 * the same handoff, so both are supported.
 *
 * Source pages: modules/workforce/pages/implementation-guide.php
 *               modules/workforce/pages/custom-reports.php
 *
 * POST /api/inbound/receive-reporting-requirement.php
 * Header: X-API-Key: <workforce-analytics key>
 * Body (JSON): {
 *   target_name, reporting_period_start, reporting_period_end, reporting_frequency,
 *   completion_target, expected_output, notes, created_by
 * }
 * reporting_period_start / end also accept the sender's aliases period_start / period_end.
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

$endpoint = 'receive-reporting-requirement';

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

    // 3. Store (validated in WorkforceIntegration::receiveReportingRequirement)
    $integration = new WorkforceIntegration($pdo);
    $result = $integration->receiveReportingRequirement($input);
    $log = new IntegrationLog($pdo);

    if (!$result['success']) {
        http_response_code(422);
        $log->logCall('inbound', 'workforce-analytics', $endpoint, 'failed', $input, $result['message']);
        echo json_encode(['success' => false, 'error' => $result['message']]);
        exit;
    }

    $log->logCall('inbound', 'workforce-analytics', $endpoint, 'success', $input);

    echo json_encode([
        'success'   => true,
        'message'   => $result['message'],
        'id'        => $result['id'],
        'duplicate' => false,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'workforce-analytics', $endpoint, 'failed', $input ?? null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
