<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/controllers/AttendanceController.php';

Session::start();

$employee_id = $_GET['id'] ?? null;

if ($employee_id === null || $employee_id === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing employee ID in QR request.'
    ]);
    exit;
}

$employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
if ($employee_id === false) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid employee ID in QR request.'
    ]);
    exit;
}

try {
    $controller = new AttendanceController();
    $result = $controller->processStaticQR((int) $employee_id);
    echo json_encode($result);
} catch (Throwable $e) {
    error_log('processStaticQR.php fatal error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'QR processing failed on the server.'
    ]);
}
