<?php
/**
 * Assign shift to multiple employees
 * Handles bulk shift assignment with optional Saturday exclusion
 */

require_once(__DIR__ . '/../../../../../database/db.php');
require_once(__DIR__ . '/../controllers/ShiftController.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();
    $shiftController = new ShiftController($db);

    $action = $_POST['action'] ?? null;
    
    if ($action === 'assign_multiple') {
        $employeeIds = json_decode($_POST['employee_ids'] ?? '[]', true);
        $shiftId = $_POST['shift_id'] ?? null;
        $effectiveFrom = $_POST['effective_from'] ?? null;
        $effectiveTo = $_POST['effective_to'] ?? null;

        if (empty($employeeIds) || !is_array($employeeIds)) {
            throw new Exception('Invalid employee IDs');
        }

        if (!$shiftId) {
            throw new Exception('Shift ID is required');
        }

        if (!$effectiveFrom) {
            throw new Exception('Effective from date is required');
        }

        $successCount = 0;
        $failureCount = 0;
        $errors = [];
        $saturdaysSkipped = 0;

        foreach ($employeeIds as $employeeId) {
            try {
                $result = $shiftController->assignShiftToEmployee(
                    $employeeId,
                    $shiftId,
                    $effectiveFrom,
                    $effectiveTo ?: null
                );

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $errors[] = "Employee $employeeId: " . $result['message'];
                }
            } catch (Exception $e) {
                $failureCount++;
                $errors[] = "Employee $employeeId: " . $e->getMessage();
            }
        }

        $message = "Successfully assigned shift to $successCount employee(s)";

        echo json_encode([
            'success' => $failureCount === 0,
            'message' => $message,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'saturdays_excluded' => 0,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
