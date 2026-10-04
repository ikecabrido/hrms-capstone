<?php
/**
 * Approve Leave Request by HR Admin API
 * POST /api/approve_leave_hr.php
 * 
 * Handles HR admin final approval of leave requests
 * Second-tier approval that deducts from leave balance
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/LeaveController.php';
require_once __DIR__ . '/../models/Leave.php';

Session::start();

// Check if user is authenticated
if (!Session::get('user_id')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// Check if user is HR admin
$user_role = Session::get('role');
if ($user_role !== 'HR_ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Only HR admins can approve']);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['leave_request_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$leave_request_id = (int)$data['leave_request_id'];
$action = strtoupper($data['action']); // APPROVE or REJECT
$remarks = isset($data['remarks']) ? trim($data['remarks']) : '';

if ($action !== 'APPROVE' && $action !== 'REJECT') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action. Must be APPROVE or REJECT']);
    exit;
}

// Get leave request
$leaveModel = new Leave();
$leaveRequest = $leaveModel->getById($leave_request_id);

if (!$leaveRequest) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Leave request not found']);
    exit;
}

// Verify leave is in correct status for HR approval
if ($action === 'APPROVE' && !in_array(strtoupper($leaveRequest['status']), ['APPROVED_BY_HEAD', 'PENDING'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Leave request must be pending or approved by department head first']);
    exit;
}

// Process approval or rejection
$leaveController = new LeaveController();
$user_id = Session::get('user_id');

if ($action === 'APPROVE') {
    // HR approval deducts balance
    $result = $leaveController->approve($leave_request_id, $user_id, true, $remarks);
} else {
    // HR rejection
    if (empty($remarks)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }
    $result = $leaveController->reject($leave_request_id, $user_id, $remarks);
}

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'data' => [
            'leave_request_id' => $leave_request_id,
            'action' => $action,
            'balance_deducted' => ($action === 'APPROVE' ? $leaveRequest['total_days'] : 0),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
?>
