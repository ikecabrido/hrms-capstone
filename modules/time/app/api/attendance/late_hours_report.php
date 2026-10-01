<?php
/**
 * API: Late Hours Report
 * Returns total late minutes/hours per employee for a date range.
 * Used by the payroll module to calculate late-based deductions.
 * Late time only - does not cover absences or leave.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/Attendance.php';
require_once __DIR__ . '/../../core/Session.php';

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$employeeId = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int)$_GET['employee_id'] : null;

if (!$startDate || !$endDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'start_date and end_date are required (format: Y-m-d)']);
    exit;
}

$startObj = DateTime::createFromFormat('Y-m-d', $startDate);
$endObj = DateTime::createFromFormat('Y-m-d', $endDate);
if (!$startObj || $startObj->format('Y-m-d') !== $startDate || !$endObj || $endObj->format('Y-m-d') !== $endDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'start_date and end_date must be valid dates in Y-m-d format']);
    exit;
}

try {
    $attendanceModel = new Attendance();
    $records = $attendanceModel->getLateHoursSummary($startDate, $endDate, $employeeId);

    echo json_encode([
        'success' => true,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'employee_id' => $employeeId,
        'records' => $records
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate late hours report', 'error' => $e->getMessage()]);
}
