<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    @require_once __DIR__ . '/Controller/PatientController.php';

    if (!isset($_SESSION['employee_id'])) {
        @http_response_code(401);
        throw new RuntimeException('Your session has expired. Please sign in again.');
    }

    $controller = new PatientController();
    $controller->authorize();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    $payload = [];
    if ($method === 'POST') {
        $raw = @file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        if (!$payload) {
            $payload = $_POST;
        }
    } else {
        $payload = $_GET;
    }

    $action = trim((string) ($payload['action'] ?? $_REQUEST['action'] ?? ''));

    if ($action === 'employees') {
        jsonGatekeeperEmit(['employees' => $controller->searchEmployees((string) ($payload['search'] ?? ''))], 200);
    }

    if ($action === 'employee') {
        $employee = $controller->getEmployee($payload);
        jsonGatekeeperEmit(['employee' => $employee], 200);
    }

    if ($action === 'patients') {
        jsonGatekeeperEmit(['patients' => $controller->listPatients((string) ($payload['search'] ?? ''))], 200);
    }

    if ($action === 'patient') {
        $patient = $controller->getPatient($payload);
        if ($patient === null) {
            @http_response_code(404);
            throw new RuntimeException('Patient record not found.');
        }
        jsonGatekeeperEmit(['patient' => $patient], 200);
    }

    if ($action === 'register') {
        $patient = $controller->registerEmployeeAsPatient($payload);
        jsonGatekeeperEmit(['message' => 'Employee registered as patient successfully.', 'patient' => $patient], 200);
    }

    if ($action === 'save') {
        $patient = $controller->savePatient($payload);
        jsonGatekeeperEmit(['message' => 'Changes saved successfully.', 'patient' => $patient], 200);
    }

    if ($action === 'update') {
        $patient = $controller->updatePatient($payload);
        jsonGatekeeperEmit(['message' => 'Patient information updated successfully.', 'patient' => $patient], 200);
    }

    if ($action === 'deactivate') {
        $patient = $controller->deactivatePatient($payload);
        jsonGatekeeperEmit(['message' => 'Record deactivated successfully.', 'patient' => $patient], 200);
    }

    @http_response_code(400);
    throw new InvalidArgumentException('Unknown employee patient action.');
} catch (InvalidArgumentException $e) {
    if ((@http_response_code() ?: 0) < 400) {
        @http_response_code(422);
    }
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], (@http_response_code() ?: 0) < 400 ? 422 : @http_response_code());
} catch (RuntimeException $e) {
    if ((@http_response_code() ?: 0) < 400) {
        @http_response_code(403);
    }
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], (@http_response_code() ?: 0) < 400 ? 403 : @http_response_code());
} catch (Throwable $e) {
    @error_log('Employee patient request failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    jsonGatekeeperEmit(['success' => false, 'message' => 'Unable to process the employee patient request.'], 500);
}
