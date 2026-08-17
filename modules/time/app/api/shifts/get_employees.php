<?php
/**
 * Get all employees for flexible schedule modal
 * Returns employee list with assignment status (shift or flexible schedule)
 */

require_once(__DIR__ . '/../../../../../database/db.php');

header('Content-Type: application/json');

try {
    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();

    // Get all active employees with their shift and flexible schedule status
    $query = "SELECT 
                e.employee_id,
                e.full_name,
                e.department,
                e.position,
                CASE 
                    WHEN fs.id IS NOT NULL THEN 1
                    ELSE 0
                END AS has_flexible_schedule_direct,
                CASE 
                    WHEN es.employee_shift_id IS NOT NULL 
                        AND es.is_active = 1
                        AND es.effective_from <= CURDATE()
                        AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
                    THEN 1
                    ELSE 0
                END AS has_shift,
                CASE 
                    WHEN fs.id IS NOT NULL OR (
                        es.employee_shift_id IS NOT NULL 
                        AND es.is_active = 1
                        AND es.effective_from <= CURDATE()
                        AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
                    ) THEN 1
                    ELSE 0
                END AS has_flexible_schedule
              FROM employees e
              LEFT JOIN ta_flexible_schedules fs ON e.employee_id = fs.employee_id
                  AND (
                      fs.schedule_date = CURDATE()
                      OR (
                          fs.day_of_week IS NOT NULL
                          AND fs.day_of_week = DAYOFWEEK(CURDATE()) - 1
                          AND (fs.repeat_until IS NULL OR fs.repeat_until >= CURDATE())
                          AND (fs.contract_end_date IS NULL OR fs.contract_end_date >= CURDATE())
                      )
                  )
              LEFT JOIN ta_employee_shifts es ON e.employee_id = es.employee_id
                  AND es.is_active = 1
                  AND es.effective_from <= CURDATE()
                  AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
              WHERE e.employment_status = 'Active'
              GROUP BY e.employee_id, e.full_name, e.department, e.position
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
        'message' => 'Error fetching employees: ' . $e->getMessage()
    ]);
}
?>
