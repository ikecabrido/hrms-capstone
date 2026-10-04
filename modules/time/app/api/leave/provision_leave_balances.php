<?php
/**
 * Provision leave balances for active employees
 * POST /api/provision_leave_balances.php
 *
 * Optional request body or query: { "year": 2026 }
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
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
}

$leaveModel = new Leave();
try {
    $provCount = $leaveModel->provisionLeaveBalancesForActiveEmployees($year);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Leave balances provisioned successfully',
        'year' => $year ?? date('Y'),
        'employees_processed' => $provCount,
    ]);
} catch (Exception $e) {
    error_log('Provision Leave Balances Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while provisioning leave balances',
        'error' => $e->getMessage()
    ]);
}
