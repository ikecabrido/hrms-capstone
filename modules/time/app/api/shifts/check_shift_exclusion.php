<?php
/**
 * Check if an employee has a shift exclusion for a given date
 * Returns whether the employee should not work on that date
 */

require_once(__DIR__ . '/../../../../database/db.php');

header('Content-Type: application/json');

try {
    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();

    $employeeId = $_GET['employee_id'] ?? null;
    $date = $_GET['date'] ?? date('Y-m-d');

    if (!$employeeId) {
        throw new Exception('Employee ID is required');
    }

    // Check if employee has a shift exclusion for this date
    $query = "SELECT COUNT(*) as exclusion_count 
              FROM ta_shift_exclusions se
              WHERE se.exclusion_date = :date
              AND se.employee_shift_id IN (
                  SELECT employee_shift_id FROM ta_employee_shifts 
                  WHERE employee_id = :employee_id 
                  AND is_active = 1
                  AND effective_from <= :date
                  AND (effective_to IS NULL OR effective_to >= :date)
              )";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employeeId);
    $stmt->bindParam(':date', $date);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasExclusion = $result['exclusion_count'] > 0;

    echo json_encode([
        'success' => true,
        'employee_id' => $employeeId,
        'date' => $date,
        'has_exclusion' => $hasExclusion,
        'message' => $hasExclusion ? 'Employee has a shift exclusion for this date' : 'No shift exclusion found'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>