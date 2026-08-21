<?php
/**
 * Provision leave balances only for employees who do not currently have balances for the year.
 * POST /api/provision_missing_balances.php
 * Optional body: { "year": 2026 }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Leave.php';

Session::start();

if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$user_role = AuthController::getCurrentRole();
$allowedRoles = ['HR_ADMIN', 'DEPARTMENT_HEAD', 'time'];
if (!in_array($user_role, $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$year = isset($payload['year']) ? (int)$payload['year'] : null;
if (!$year) {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
}

$leaveModel = new Leave();
try {
    $missing = $leaveModel->getActiveEmployeesMissingBalances($year);
    $processed = [];
    $errors = [];
    foreach ($missing as $emp) {
        $ok = $leaveModel->provisionLeaveBalancesForEmployee($emp['employee_id'], $year);
        if ($ok) {
            $processed[] = $emp['employee_id'];
        } else {
            $errors[] = ['employee_id' => $emp['employee_id'], 'reason' => 'provision_failed'];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Provisioned missing leave balances',
        'year' => $year,
        'employees_found' => count($missing),
        'employees_processed' => count($processed),
        'processed_ids' => $processed,
        'errors' => $errors,
    ]);
} catch (Exception $e) {
    error_log('Provision Missing Balances Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while provisioning missing leave balances',
        'error' => $e->getMessage(),
    ]);
}

?>
