<?php
/**
 * Inbound: receive-competency-requirement.php
 * Receives predicted skill / competency needs from Workforce Analytics and stores
 * them in ld_competency_requirement.
 *
 * The Workforce module also writes this table directly (via
 * Employee::saveCompetencyRequirement()); this endpoint is the API path for the
 * same handoff, so both are supported.
 *
 * Source page: modules/workforce/pages/predicted-skill-competency-needs.php
 *
 * POST /api/inbound/receive-competency-requirement.php
 * Header: X-API-Key: <workforce-analytics key>
 * Body (JSON): {
 *   skill_name, target_department, priority_level, employees_needing_training,
 *   target_proficiency, expected_learning_output, notes, created_by
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

$endpoint = 'receive-competency-requirement';

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

    // 3. Store (validated in WorkforceIntegration::receiveCompetencyRequirement)
    $integration = new WorkforceIntegration($pdo);
    $result = $integration->receiveCompetencyRequirement($input);
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
