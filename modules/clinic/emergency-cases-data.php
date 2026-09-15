<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) @session_start(['read_and_close' => false]);
    @require_once __DIR__ . '/Controller/EmergencyCaseController.php';
    $controller = new EmergencyCaseController();
    $controller->authorize();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $input = $_GET;
    if ($method === 'POST') {
        $raw = @file_get_contents('php://input');
        $decoded = $raw !== false && trim($raw) !== '' ? @json_decode($raw, true) : null;
        if ($decoded === null && !empty($_POST)) { $input = $_POST; }
        elseif (is_array($decoded)) { $input = $decoded; }
        else { jsonGatekeeperEmit(['success' => false, 'message' => 'Invalid request body.'], 400); }
    }
    $action = trim((string)($input['action'] ?? 'list'));
    switch ($action) {
        case 'list':    jsonGatekeeperEmit(['cases' => $controller->listCases($input)]);
        case 'get':     jsonGatekeeperEmit(['case' => $controller->getCase($input['case_id'] ?? null)]);
        case 'patients':jsonGatekeeperEmit(['patients' => $controller->patients($input['search'] ?? '')]);
        case 'patient': jsonGatekeeperEmit(['patient' => $controller->patient($input['patient_id'] ?? null)]);
        case 'save':
            if ($method !== 'POST') { jsonGatekeeperEmit(['success'=>false,'message'=>'POST is required for this action.'], 405); }
            $caseId = $controller->create($input);
            jsonGatekeeperEmit(['case_id' => $caseId, 'message' => 'Emergency case saved successfully.'], 201);
        case 'update':
            if ($method !== 'POST') { jsonGatekeeperEmit(['success'=>false,'message'=>'POST is required for this action.'], 405); }
            $controller->update($input);
            jsonGatekeeperEmit(['message' => 'Emergency case updated successfully.']);
        case 'delete':
            if ($method !== 'POST') { jsonGatekeeperEmit(['success'=>false,'message'=>'POST is required for this action.'], 405); }
            $controller->delete($input['case_id'] ?? null);
            jsonGatekeeperEmit(['message' => 'Emergency case deleted successfully.']);
        case 'close':
            if ($method !== 'POST') { jsonGatekeeperEmit(['success'=>false,'message'=>'POST is required for this action.'], 405); }
            $controller->close($input['case_id'] ?? null);
            jsonGatekeeperEmit(['message' => 'Emergency case closed successfully.']);
        default:
            jsonGatekeeperEmit(['success' => false, 'message' => 'Unknown emergency case action.'], 400);
    }
} catch (InvalidArgumentException $e) { jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], 422); }
  catch (RuntimeException $e)     { $c=@http_response_code(); jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], $c===403?403:($c>=400?$c:404)); }
  catch (Throwable $e)            { @error_log('Emergency case request failed: '.$e->getMessage()."\n".$e->getTraceAsString()); jsonGatekeeperEmit(['success'=>false,'message'=>'The emergency case request could not be completed.'], 500); }
