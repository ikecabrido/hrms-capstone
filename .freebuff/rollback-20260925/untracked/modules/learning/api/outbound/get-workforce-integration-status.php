<?php
/**
 * Outbound: get-workforce-integration-status.php
 * Returns Learning's triage state for everything Workforce Analytics sent, so the
 * sending module can see what Learning decided instead of assuming.
 *
 * GET /api/outbound/get-workforce-integration-status.php
 * Header: X-API-Key: <key for module "workforce-analytics">
 * Params: since (optional, YYYY-MM-DD — filters the decision log), limit (optional)
 *
 * Payload: current status of every received record per dataset, the follow-up
 * training requests raised from them, and the audit trail of the decisions
 * (ld_audit_log rows written by WorkforceIntegration — the reception tables keep
 * no history of their own, and two of them have no updated_at column).
 *
 * The key is the same ld_api_key row the inbound endpoints use, so one credential
 * covers both directions of the handoff.
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 3) . '/classes/workforceintegration.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

$pdo = null;
$log = null;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'workforce-analytics');

    $since = isset($_GET['since']) ? trim((string) $_GET['since']) : null;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;

    $integration = new WorkforceIntegration($pdo);
    $feed = $integration->getTriageFeed($since, $limit);

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'workforce-analytics', 'get-workforce-integration-status', 'success', [
        'filters' => $_GET,
    ]);

    echo json_encode(['success' => true, 'data' => $feed]);
} catch (Throwable $e) {
    http_response_code(500);

    if ($pdo instanceof PDO) {
        try {
            $log = $log ?? new IntegrationLog($pdo);
            $log->logCall('outbound', 'workforce-analytics', 'get-workforce-integration-status', 'failed', null, $e->getMessage());
        } catch (Throwable $logError) {
            error_log('get-workforce-integration-status log failed: ' . $logError->getMessage());
        }
    }

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
