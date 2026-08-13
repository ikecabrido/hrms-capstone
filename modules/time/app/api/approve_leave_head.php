<?php
/**
 * Approve Leave Request by Department Head API
 * POST /api/approve_leave_head.php
 * 
 * Handles department head approval of leave requests
 * First-tier approval before HR admin final approval
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/LeaveController.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../../../../database/db.php';

Session::start();

// Check if user is authenticated
if (!Session::get('user_id')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// Check if user is department head or HR admin
$user_role = Session::get('role');
if ($user_role !== 'DEPARTMENT_HEAD' && $user_role !== 'HR_ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Only department heads or HR admins can approve']);
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

// Get leave request to verify it exists and check authorization
$leaveModel = new Leave();
$leaveRequest = $leaveModel->getById($leave_request_id);

if (!$leaveRequest) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Leave request not found']);
    exit;
}

// Verify department head has authority for this employee
if ($user_role === 'DEPARTMENT_HEAD') {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $query = "SELECT e.department 
              FROM employees e 
              WHERE e.employee_id = :employee_id AND e.status = 'ACTIVE'";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':employee_id', $leaveRequest['employee_id'], PDO::PARAM_INT);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    // Check if department head is assigned to this department
    $query = "SELECT dept_head_id 
              FROM ta_department_heads 
              WHERE user_id = :user_id 
              AND department = :department 
              AND is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', Session::get('user_id'), PDO::PARAM_INT);
    $stmt->bindParam(':department', $employee['department']);
    $stmt->execute();
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized to approve leaves for this department']);
        exit;
    }
}

// Process approval or rejection
$leaveController = new LeaveController();
$user_id = Session::get('user_id');

if ($action === 'APPROVE') {
    $result = $leaveController->approve($leave_request_id, $user_id, false, $remarks);
} else {
    // Rejection
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
