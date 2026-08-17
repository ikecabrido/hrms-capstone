<?php
/**
 * Get all employees for shift assignment modal
 * Returns employee list with shift status
 */

require_once(__DIR__ . '/../../../../../database/db.php');

header('Content-Type: application/json');

try {
    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();

    // Get all active employees with their shift assignment status
    $query = "SELECT 
                e.employee_id,
                e.full_name,
                e.department,
                e.position,
                CASE 
                    WHEN es.employee_shift_id IS NOT NULL THEN 1
                    ELSE 0
                END AS has_shift
              FROM employees e
              LEFT JOIN ta_employee_shifts es ON e.employee_id = es.employee_id 
                  AND es.is_active = 1
                  AND es.effective_from <= CURDATE()
                  AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
              WHERE e.employment_status = 'Active'
              ORDER BY e.full_name ASC";

    $stmt = $db->query($query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'count' => count($employees)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
