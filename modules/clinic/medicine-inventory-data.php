<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) { @session_start(['read_and_close' => false]); }
    @require_once __DIR__ . '/Controller/MedicineInventoryController.php';
    if (!isset($_SESSION['employee_id'])) { @http_response_code(401); throw new RuntimeException('Your session has expired. Please sign in again.'); }
    $controller = new MedicineInventoryController();
    $controller->authorize();
    $action = trim((string)($_REQUEST['action'] ?? ''));
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $payload = [];
    if ($method === 'POST') {
        $raw = @file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) { $payload = $decoded; }
        }
        if (!$payload) { $payload = $_POST; }
    } else {
        $payload = $_GET;
    }
    if ($action === 'list')       { jsonGatekeeperEmit(['items' => $controller->listMedicines($payload)]); }
    if ($action === 'suppliers')  { jsonGatekeeperEmit(['suppliers' => $controller->getSuppliers()]); }
    if ($action === 'get')        { $med = $controller->getMedicine($payload); if ($med === null) { @http_response_code(404); throw new RuntimeException('Medicine record not found.'); } jsonGatekeeperEmit(['medicine' => $med]); }
    if ($action === 'save')       { $med = $controller->saveMedicine($payload); jsonGatekeeperEmit(['message' => 'Medicine saved successfully.', 'medicine' => $med]); }
    if ($action === 'deactivate') { $med = $controller->deactivateMedicine($payload); jsonGatekeeperEmit(['message' => 'Medicine deactivated successfully.', 'medicine' => $med]); }
    if ($action === 'stock')      { $med = $controller->updateStock($payload); jsonGatekeeperEmit(['message' => 'Medicine stock updated successfully.', 'medicine' => $med]); }
    @http_response_code(400); throw new InvalidArgumentException('Unknown medicine inventory action.');
} catch (InvalidArgumentException $e) { $c=@http_response_code(); jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], $c<400?422:$c); }
  catch (RuntimeException $e)          { $c=@http_response_code(); jsonGatekeeperEmit(['success'=>false,'message'=>$e->getMessage()], $c<400?403:$c); }
  catch (Throwable $e)                 { @error_log('Medicine inventory request failed: '.$e->getMessage()."\n".$e->getTraceAsString()); jsonGatekeeperEmit(['success'=>false,'message'=>'Unable to process the medicine inventory request.'],500); }
