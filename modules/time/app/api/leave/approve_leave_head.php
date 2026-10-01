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
// Start output buffering to prevent accidental HTML/whitespace from breaking JSON responses
if (function_exists('ob_start')) ob_start();

function respond($code, $payload) {
    http_response_code($code);
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        // discard any prior output that might break JSON
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Attempt to load required files from several candidate relative paths to avoid fatal include errors
$candidatesBase = [
    __DIR__ . '/../../', // app/
    __DIR__ . '/../',     // api/leave/../
    __DIR__ . '/../../../', // app/api/.. up one more
    __DIR__ . '/../../../../',
];

$requiredFiles = [
    'core/Session.php',
    'controllers/AuthController.php',
    'controllers/LeaveController.php',
    'models/Leave.php',
    '../../../../database/db.php',
];

foreach ($requiredFiles as $rel) {
    $found = false;
    foreach ($candidatesBase as $base) {
        $path = realpath($base . $rel) ?: ($base . $rel);
        if (file_exists($path)) {
            require_once $path;
            $found = true;
            break;
        }
    }
    if (!$found) {
        error_log('[TA][approve_leave_head] Missing required file: ' . $rel);
        respond(500, ['success' => false, 'message' => 'Server configuration error: missing ' . $rel]);
    }
}

Session::start();

// Debug logging to help diagnose empty/invalid responses
error_log('[TA][approve_leave_head] invoked. REQUEST_METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? '')); 
error_log('[TA][approve_leave_head] Session: ' . print_r($_SESSION, true));


// Check if user is authenticated
$session_user_id = Session::get('user_id') ?? Session::get('employee_id') ?? ($_SESSION['user']['id'] ?? $_SESSION['employee_id'] ?? null);
if (!$session_user_id) {
    respond(401, ['success' => false, 'message' => 'Unauthorized - Please log in']);
}

// Check if user is department head or HR admin
$sessionRole = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? null;
$isHrAdmin = AuthController::hasRole('HR_ADMIN') || (is_string($sessionRole) && in_array(strtolower($sessionRole), ['hr_admin','admin','hr']));
$isDeptHead = AuthController::hasRole('DEPARTMENT_HEAD') || (is_string($sessionRole) && strtolower($sessionRole) === 'department_head');
// numeric role fallbacks (compat mapping)
$sessionRoleValue = (string) ($sessionRole ?? '');
if (!$isHrAdmin && in_array($sessionRoleValue, ['2','3','7'], true)) $isHrAdmin = true;
if (!$isDeptHead && $sessionRoleValue === '4') $isDeptHead = true;

if (!($isDeptHead || $isHrAdmin)) {
    // include detected session role in debug message to help diagnose role mismatch
    respond(403, ['success' => false, 'message' => 'Forbidden - Only department heads or HR admins can approve', 'detected_role' => $sessionRole]);
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method Not Allowed']);
}

// Get POST data
$rawInput = file_get_contents('php://input');
error_log('[TA][approve_leave_head] Raw input: ' . substr($rawInput, 0, 2000));
$data = json_decode($rawInput ?: '', true);
error_log('[TA][approve_leave_head] Parsed JSON: ' . var_export($data, true));

// Validate required fields
if (!isset($data['leave_request_id']) || !isset($data['action'])) {
    respond(400, ['success' => false, 'message' => 'Missing required fields']);
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
    respond(404, ['success' => false, 'message' => 'Leave request not found']);
}

// Verify department head has authority for this employee
// Use AuthController::hasRole() to avoid undefined variable notices
$isDeptHead = AuthController::hasRole('DEPARTMENT_HEAD');
if ($isDeptHead) {
    $database = TimeDatabase::getInstance();
    $conn = $database->getConnection();
    
    $query = "SELECT COALESCE(d.department_name, e.department) AS department
              FROM em_employees e
              LEFT JOIN em_departments d ON e.department_id = d.department_id
              WHERE e.employee_id = :employee_id AND (e.employment_status = 'Active' OR e.employment_status = 'ACTIVE')";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':employee_id', $leaveRequest['employee_id'], PDO::PARAM_INT);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        respond(404, ['success' => false, 'message' => 'Employee not found']);
    }
    
    // Check if department head is assigned to this department
    $query = "SELECT dept_head_id 
              FROM ta_department_heads 
              WHERE user_id = :user_id 
              AND department = :department 
              AND is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $session_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':department', $employee['department']);
    $stmt->execute();
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        respond(403, ['success' => false, 'message' => 'Not authorized to approve leaves for this department']);
    }
}

// Process approval or rejection
$leaveController = new LeaveController();
$user_id = $session_user_id;

if ($action === 'APPROVE') {
    $result = $leaveController->approve($leave_request_id, $user_id, false, $remarks);
} else {
    // Rejection
    if (empty($remarks)) {
        respond(400, ['success' => false, 'message' => 'Rejection reason is required']);
    }
    $result = $leaveController->reject($leave_request_id, $user_id, $remarks);
}

if ($result['success']) {
    respond(200, [
        'success' => true,
        'message' => $result['message'],
        'data' => [
            'leave_request_id' => $leave_request_id,
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    respond(400, [
        'success' => false,
        'message' => $result['message']
    ]);
}
?>
