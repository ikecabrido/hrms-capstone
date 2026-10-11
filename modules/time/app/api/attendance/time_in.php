<?php
/**
 * Time In API Endpoint
 * POST /api/time_in.php
 * 
 * Records employee time in with manual or QR method
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../models/Employee.php';

Session::start();

// Verify user is authenticated
if (!Session::get('user_id')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get employee details
$employeeModel = new Employee();
$employee = $employeeModel->getByUserId(Session::get('user_id'));

if (!$employee || !isset($employee['employee_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

$employee_id = $employee['employee_id'];
$method = $_GET['method'] ?? $_POST['method'] ?? 'MANUAL';

// Create attendance controller
$attendanceController = new AttendanceController();

// Record time in
$result = $attendanceController->timeIn($employee_id, $method);

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'data' => [
            'employee_id' => $employee_id,
            'employee_name' => $employee['full_name'],
            'time_in' => $result['time_in'] ?? null,
            'status' => $result['status'] ?? 'PRESENT'
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
