<?php /* JSON-GATEKEEPER-V2-20260823 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    @require_once __DIR__ . '/../../database/db.php';
    @require_once __DIR__ . '/Controller/ClinicReportController.php';

    if (!isset($_SESSION['employee_id'])) {
        @http_response_code(401);
        throw new RuntimeException('Your session has expired. Please sign in again.');
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $controller = new ClinicReportController($pdo);

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $payload = $method === 'POST' ? $_POST : $_GET;
    $action = trim((string) ($payload['action'] ?? $_GET['action'] ?? ''));

    $response = null;
    $statusCode = 200;

    switch ($action) {
        case 'dashboard':
            $response = $controller->getDashboardStats();
            break;

        case 'search':
            $search = (string) ($payload['search'] ?? '');
            $department = (string) ($payload['department'] ?? '');
            $consultation_type = (string) ($payload['consultation_type'] ?? '');
            $start_date = (string) ($payload['start_date'] ?? '');
            $end_date = (string) ($payload['end_date'] ?? '');
            $page = max(1, (int) ($payload['page'] ?? 1));
            $response = $controller->searchReports($search, $department, $consultation_type, $start_date, $end_date, $page, 20);
            break;

        case 'detail':
            $record_id = trim((string) ($payload['record_id'] ?? ''));
            if ($record_id === '') {
                $statusCode = 422;
                $response = ['success' => false, 'message' => 'Record ID is required.'];
            } else {
                $response = $controller->getReportDetail($record_id);
            }
            break;

        case 'departments':
            $response = $controller->getDepartments();
            break;

        case 'employees':
            $response = $controller->getEmployees();
            break;

        case 'statistics':
            $start_date = (string) ($payload['start_date'] ?? '');
            $end_date = (string) ($payload['end_date'] ?? '');
            $response = $controller->getStatisticsReport($start_date, $end_date);
            break;

        case 'generate_period_report':
            $filters = [
                'report_type' => trim((string) ($payload['report_type'] ?? '')),
                'date_from' => trim((string) ($payload['date_from'] ?? '')),
                'date_to' => trim((string) ($payload['date_to'] ?? '')),
                'month' => trim((string) ($payload['month'] ?? '')),
                'year' => trim((string) ($payload['year'] ?? '')),
                'department' => trim((string) ($payload['department'] ?? '')),
                'employee_id' => trim((string) ($payload['employee_id'] ?? '')),
                'employee_name' => trim((string) ($payload['employee_name'] ?? ''))
            ];
            $response = $controller->generatePeriodReport($filters, $_SESSION['user_id'] ?? null, $_SESSION['employee_name'] ?? 'Clinic Staff');
            break;

        default:
            $statusCode = 400;
            $response = ['success' => false, 'message' => 'Invalid action.'];
    }

    jsonGatekeeperEmit($response, $statusCode);
} catch (InvalidArgumentException $e) {
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], (@http_response_code() ?: 0) < 400 ? 422 : @http_response_code());
} catch (RuntimeException $e) {
    $c = @http_response_code();
    jsonGatekeeperEmit(['success' => false, 'message' => $e->getMessage()], $c < 400 ? 401 : $c);
} catch (Throwable $e) {
    @error_log('Clinic reports request failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    jsonGatekeeperEmit([
        'success' => false,
        'message' => 'Unable to process the clinic report request.'
    ], 500);
}
