<?php
/**
 * Get employee details and current assignments
 */

// Improve robustness: don't allow raw PHP errors to produce HTML responses
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/get_employee_details_error.log');

header('Content-Type: application/json');

// Include dependencies, but check for their existence to return JSON errors instead of fatal HTML
$dbPath = __DIR__ . '/../../../../database/db.php';
$shiftCtrlPath = __DIR__ . '/../controllers/ShiftController.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Missing dependency: database.php']);
    exit;
}
if (!file_exists($shiftCtrlPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Missing dependency: ShiftController.php']);
    exit;
}

require_once($dbPath);
require_once($shiftCtrlPath);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    $employeeId = $_POST['employee_id'] ?? null;
    if (!$employeeId) throw new Exception('employee_id is required');

    $database = Database::getInstance();
    $db = $database->getConnection();
    $shiftController = new ShiftController($db);

    // Fetch basic employee info
    $stmt = $db->prepare("SELECT employee_id, full_name, department, position FROM employees WHERE employee_id = :id LIMIT 1");
    $stmt->bindParam(':id', $employeeId);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch assignments (including inactive if needed)
    $assignments = $shiftController->getEmployeeShifts($employeeId);

    echo json_encode([
        'success' => true,
        'employee' => $employee ?: null,
        'assignments' => $assignments
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
