<?php
/**
 * Submit Leave Request API
 * POST /api/submit_leave.php
 * 
 * Submits a new leave request with balance validation
 * Requires employee to be logged in and have sufficient balance
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/LeaveController.php';
require_once __DIR__ . '/../helpers/Helper.php';

Session::start();

// Check if user is authenticated
if (!Session::get('user_id')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get POST data or multipart/form-data
$data = [];
if (!empty($_FILES) || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false)) {
    // Use form fields
    $data = $_POST;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
}

// Validate required fields
$required_fields = ['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// Validate dates
$dateValidation = Helper::validateLeaveRequestDates($data['start_date'], $data['end_date']);
if (!$dateValidation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $dateValidation['message']]);
    exit;
}

// Check if user is requesting leave for themselves or is HR admin
$user_role = Session::get('role');
$request_employee_id = (int)$data['employee_id'];
$session_user_id = Session::get('user_id');

// Get employee's user_id if different employee is being requested
if ($request_employee_id != $session_user_id && $user_role !== 'HR_ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Cannot submit leave for other employees']);
    exit;
}

// Calculate total days using working-day rules
$total_days = $dateValidation['total_days'];

// Prepare data
$request_data = [
    'employee_id' => $request_employee_id,
    'leave_type_id' => (int)$data['leave_type_id'],
    'start_date' => $data['start_date'],
    'end_date' => $data['end_date'],
    'reason' => trim($data['reason']),
    'total_days' => $total_days
];

// Handle file upload if present (supporting_document)
if (!empty($_FILES['supporting_document']['name'])) {
    $uploadDir = __DIR__ . '/../../../uploads/leave_documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $ext = strtolower(pathinfo($_FILES['supporting_document']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExtensions, true)) {
        $safeFilename = uniqid('leave_doc_') . '.' . $ext;
        $destination = $uploadDir . $safeFilename;
        if (move_uploaded_file($_FILES['supporting_document']['tmp_name'], $destination)) {
            $request_data['supporting_document'] = 'uploads/leave_documents/' . $safeFilename;
            $request_data['document_uploaded_at'] = date('Y-m-d H:i:s');
        }
    }
}

// Submit request through controller
$leaveController = new LeaveController();
$result = $leaveController->submitRequest($request_data);

if ($result['success']) {
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'data' => [
            'total_days' => $total_days,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date']
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
