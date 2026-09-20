<?php
/**
 * Get employee details and current shift assignments
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/get_employee_details_error.log');

header('Content-Type: application/json');

require_once __DIR__ . '/../../core/TimeDatabase.php';
require_once __DIR__ . '/../controllers/ShiftController.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    $employeeId = $_POST['employee_id'] ?? null;

    if (!$employeeId) {
        throw new Exception('employee_id is required');
    }

    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();

    $shiftController = new ShiftController($db);

    /*
     * Get employee information
     */
    $stmt = $db->prepare("
        SELECT
            e.employee_id,
            CONCAT(
                COALESCE(e.first_name, ''),
                ' ',
                COALESCE(e.last_name, '')
            ) AS full_name,
            COALESCE(d.department_name, '') AS department,
            COALESCE(p.position_name, '') AS position
        FROM em_employees e
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        WHERE e.employee_id = :id
        LIMIT 1
    ");

$stmt->execute([
    ':id' => $employeeId
]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee not found');
    }

    /*
     * Get employee's shift assignments.
     * getEmployeeShifts() intentionally includes inactive
     * assignments because this endpoint is also used for editing.
     */
    $assignments = $shiftController->getEmployeeShifts($employeeId);

    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'assignments' => $assignments
    ]);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}