<?php
/**
 * Update assignment for a single employee
 */

// Ensure PHP warnings/notices do not break JSON output
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Buffer output to prevent accidental HTML from leaking into JSON
ob_start();

require_once(__DIR__ . '/../../../../database/db.php');
require_once(__DIR__ . '/../controllers/ShiftController.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();
    $shiftController = new ShiftController($db);

    $employeeId = $_POST['employee_id'] ?? null;
    $shiftId = $_POST['shift_id'] ?? null;
    $effectiveFrom = $_POST['effective_from'] ?? null;
    $effectiveTo = $_POST['effective_to'] ?? null;

    if (!$employeeId) throw new Exception('employee_id is required');
    if (!$shiftId) throw new Exception('shift_id is required');
    if (!$effectiveFrom) throw new Exception('effective_from is required');

    $result = $shiftController->updateEmployeeAssignment($employeeId, $shiftId, $effectiveFrom, $effectiveTo ?: null);

    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Failed to update assignment');
    }

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Assignment updated',
        'employee_shift_id' => null,
        'saturdays_excluded' => 0
    ]);

} catch (Exception $e) {
    // Clean any buffered output that might contain HTML
    if (ob_get_length() > 0) ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
// Flush remaining buffer safely
if (ob_get_length() > 0) ob_end_flush();
