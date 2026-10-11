<?php
/**
 * Time Out API Endpoint
 * POST /api/time_out.php
 * 
 * Records employee time out
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Attendance.php';

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

// Get today's attendance record
$attendanceModel = new Attendance();
$todayAttendance = $attendanceModel->getTodayAttendance($employee_id);

if (!$todayAttendance || !$todayAttendance['attendance_id']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No time in record found for today. Please time in first.'
    ]);
    exit;
}

// Record time out
$attendanceController = new AttendanceController();
$result = $attendanceController->timeOut($employee_id);

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'data' => [
            'employee_id' => $employee_id,
            'employee_name' => $employee['full_name'],
            'time_out' => $result['time_out'] ?? null,
            'duration' => $result['duration'] ?? null
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
