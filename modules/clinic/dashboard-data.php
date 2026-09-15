<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    @require_once __DIR__ . '/classes/ClinicDashboard.php';

    $dashboard = new ClinicDashboard();
    $range = $_GET['range'] ?? $_GET['date'] ?? null;
    jsonGatekeeperEmit($dashboard->getData($range), 200);
} catch (InvalidArgumentException $e) {
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], 422);
} catch (RuntimeException $e) {
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], (@http_response_code() ?: 0) < 400 ? 400 : @http_response_code());
} catch (Throwable $e) {
    @error_log('Dashboard request failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    jsonGatekeeperEmit(['success' => false, 'message' => 'Unable to load clinic dashboard data.'], 500);
}
