<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) @session_start(['read_and_close' => false]);
    @require_once __DIR__ . '/Controller/MedicalRecordController.php';
    if (!isset($_SESSION['employee_id'])) { @http_response_code(401); throw new RuntimeException('Your session has expired.'); }
    $controller = new MedicalRecordController();
    $controller->authorize();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $raw = $method === 'POST' ? @file_get_contents('php://input') : false;
    $payload = $method === 'POST' ? ($raw !== false && trim($raw) !== '' ? @json_decode($raw, true) : null) : $_GET;
    if ($method === 'POST' && $payload === null) $payload = $_POST;
    $payload = is_array($payload) ? $payload : [];
    $action = trim((string)($payload['action'] ?? 'employees'));
    if ($method === 'POST') {
        if (!@hash_equals((string)($_SESSION['clinic_medical_record_csrf'] ?? ''), (string)($payload['csrf_token'] ?? ''))) throw new RuntimeException('Security token expired.');
        if ($action === 'save')   { jsonGatekeeperEmit(['message'=>'Medical record added successfully.','record'=>$controller->save($payload, (int)($_SESSION['user_id'] ?? 0))]); }
        if ($action === 'update') { jsonGatekeeperEmit(['message'=>'Medical record updated successfully.','record'=>$controller->update($payload)]); }
        if ($action === 'delete') { $controller->delete($payload); jsonGatekeeperEmit(['message'=>'Medical record deleted successfully.']); }
    }
    if ($action === 'employees') { jsonGatekeeperEmit(['csrf_token' => $controller->getCsrfToken(), 'employees' => $controller->getEmployees($payload['search'] ?? '')]); }
    if ($action === 'history')   { jsonGatekeeperEmit($controller->getHistory($payload)); }
    if ($action === 'record')    { $record = $controller->getRecord($payload); if ($record === null) { @http_response_code(404); throw new RuntimeException('Medical record not found.'); } jsonGatekeeperEmit(['record' => $record]); }
    @http_response_code(400); throw new InvalidArgumentException('Unknown medical record action.');
} catch (InvalidArgumentException $e) { jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], (@http_response_code() ?: 0) < 400 ? 422 : @http_response_code()); }
  catch (RuntimeException $e)     { $c = @http_response_code(); jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], $c < 400 ? 403 : $c); }
  catch (Throwable $e)            { @error_log('Medical record request failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString()); jsonGatekeeperEmit(['success'=>false,'message'=>'Unable to process medical record data.'], 500); }
