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
    $excludeSaturday = $_POST['exclude_saturday'] ?? '0';

    if (!$employeeId) throw new Exception('employee_id is required');
    if (!$shiftId) throw new Exception('shift_id is required');
    if (!$effectiveFrom) throw new Exception('effective_from is required');

    $result = $shiftController->updateEmployeeAssignment($employeeId, $shiftId, $effectiveFrom, $effectiveTo ?: null);

    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Failed to update assignment');
    }

    // Determine employee_shift_id for possible exclusions
    $stmt = $db->prepare("SELECT employee_shift_id FROM ta_employee_shifts WHERE employee_id = :employee_id AND shift_id = :shift_id ORDER BY effective_from DESC LIMIT 1");
    $stmt->bindParam(':employee_id', $employeeId);
    $stmt->bindParam(':shift_id', $shiftId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeShiftId = $row['employee_shift_id'] ?? null;

    $saturdaysSkipped = 0;
    if (($excludeSaturday === '1' || $excludeSaturday === 1) && $employeeShiftId) {
        // Create exclusion records for Saturdays between effectiveFrom and effectiveTo (or 5 years ahead if none)
        $fromDate = new DateTime($effectiveFrom);
        $toDate = $effectiveTo ? new DateTime($effectiveTo) : new DateTime('+5 years');

        if (!$effectiveTo) {
            $toDate = clone $fromDate;
            $toDate->add(new DateInterval('P5Y'));
        }

        $currentDate = clone $fromDate;
        $insertQuery = "INSERT INTO ta_shift_exclusions (employee_shift_id, exclusion_date, reason, created_at) 
                        VALUES (:employee_shift_id, :exclusion_date, 'Saturday exclusion', NOW())
                        ON DUPLICATE KEY UPDATE updated_at = NOW()";
        $insertStmt = $db->prepare($insertQuery);

        while ($currentDate <= $toDate) {
            if ($currentDate->format('w') == 6) {
                $exclusionDate = $currentDate->format('Y-m-d');
                $insertStmt->bindParam(':employee_shift_id', $employeeShiftId);
                $insertStmt->bindParam(':exclusion_date', $exclusionDate);
                $insertStmt->execute();
                $saturdaysSkipped++;
            }
            $currentDate->add(new DateInterval('P1D'));
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Assignment updated',
        'employee_shift_id' => $employeeShiftId,
        'saturdays_excluded' => $saturdaysSkipped
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
