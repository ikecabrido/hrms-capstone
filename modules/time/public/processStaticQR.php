<?php
require_once __DIR__ . '/../app/controllers/AttendanceController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../app/services/AttendanceValidationService.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
require_once __DIR__ . '/../app/core/Session.php';

header('Content-Type: application/json');

$employee_id = isset($_GET['id']) ? trim($_GET['id']) : null;

error_log('StaticQR endpoint called with id=' . var_export($employee_id, true));

if ($employee_id === null || $employee_id === '') {
    echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
    exit;
}

$controller = new AttendanceController();
$result = $controller->processStaticQR($employee_id);

echo json_encode($result);