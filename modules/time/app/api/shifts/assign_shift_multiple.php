<?php
/**
 * Assign shift to multiple employees
 * Handles bulk shift assignment with optional Saturday exclusion
 */

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

    $action = $_POST['action'] ?? null;
    
    if ($action === 'assign_multiple') {
        $employeeIds = json_decode($_POST['employee_ids'] ?? '[]', true);
        $shiftId = $_POST['shift_id'] ?? null;
        $effectiveFrom = $_POST['effective_from'] ?? null;
        $effectiveTo = $_POST['effective_to'] ?? null;
        $excludeSaturday = $_POST['exclude_saturday'] ?? '0';

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
                // If Saturday exclusion is enabled, assign shift but mark Saturdays as excluded
                if ($excludeSaturday === '1' || $excludeSaturday === 1) {
                    // First assign the shift normally
                    $result = $shiftController->assignShiftToEmployee(
                        $employeeId,
                        $shiftId,
                        $effectiveFrom,
                        $effectiveTo ?: null
                    );

                    if ($result['success']) {
                        // Get the employee shift ID that was just created
                        $query = "SELECT employee_shift_id FROM ta_employee_shifts 
                                  WHERE employee_id = :employee_id 
                                  AND shift_id = :shift_id 
                                  ORDER BY effective_from DESC 
                                  LIMIT 1";
                        
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':employee_id', $employeeId);
                        $stmt->bindParam(':shift_id', $shiftId);
                        $stmt->execute();
                        
                        $shiftAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($shiftAssignment) {
                            // Create exclusion records for all Saturdays in the date range
                            $fromDate = new DateTime($effectiveFrom);
                            $toDate = $effectiveTo ? new DateTime($effectiveTo) : new DateTime('+5 years');
                            
                            // If no end date, use a reasonable future date (5 years)
                            if (!$effectiveTo) {
                                $toDate = clone $fromDate;
                                $toDate->add(new DateInterval('P5Y'));
                            }
                            
                            // Find all Saturdays in the range
                            $currentDate = clone $fromDate;
                            $insertQuery = "INSERT INTO ta_shift_exclusions (employee_shift_id, exclusion_date, reason, created_at) 
                                          VALUES (:employee_shift_id, :exclusion_date, 'Saturday exclusion', NOW())
                                          ON DUPLICATE KEY UPDATE updated_at = NOW()";
                            
                            $insertStmt = $db->prepare($insertQuery);
                            
                            while ($currentDate <= $toDate) {
                                // Check if it's Saturday (day 6 in PHP, where Sunday = 0)
                                if ($currentDate->format('w') == 6) {
                                    $empShiftId = $shiftAssignment['employee_shift_id'];
                                    $exclusionDate = $currentDate->format('Y-m-d');
                                    
                                    $insertStmt->bindParam(':employee_shift_id', $empShiftId);
                                    $insertStmt->bindParam(':exclusion_date', $exclusionDate);
                                    $insertStmt->execute();
                                    $saturdaysSkipped++;
                                }
                                $currentDate->add(new DateInterval('P1D'));
                            }
                        }
                        
                        $successCount++;
                    } else {
                        $failureCount++;
                        $errors[] = "Employee $employeeId: " . $result['message'];
                    }
                } else {
                    // Regular assignment without Saturday exclusion
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
                }
            } catch (Exception $e) {
                $failureCount++;
                $errors[] = "Employee $employeeId: " . $e->getMessage();
            }
        }

        $message = "Successfully assigned shift to $successCount employee(s)";
        if ($excludeSaturday === '1' || $excludeSaturday === 1) {
            $message .= " (Saturdays excluded)";
        }

        echo json_encode([
            'success' => $failureCount === 0,
            'message' => $message,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'saturdays_excluded' => $saturdaysSkipped,
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
