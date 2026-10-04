<?php
/**
 * API - Get Day Attendance Records
 * Returns attendance records for a specific date
 */

require_once __DIR__ . '/../../../../../database/db.php';
require_once '../core/Session.php';

// Verify session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$employee_id = $_GET['employee_id'] ?? $_SESSION['employee_id'];
$date = $_GET['date'] ?? date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Permissions check
if ($employee_id != $_SESSION['employee_id'] && $_SESSION['role'] !== 'HR_ADMIN' && $_SESSION['role'] !== 'DEPARTMENT_HEAD') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    $db = TimeDatabase::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT 
            id,
            time_in,
            time_out,
            total_hours_worked,
            CASE 
                WHEN time_in IS NOT NULL AND TIME(time_in) > '08:00:00' THEN 'LATE'
                WHEN time_in IS NOT NULL THEN 'PRESENT'
                ELSE 'ABSENT'
            END as status
        FROM ta_attendance
        WHERE employee_id = :employee_id
        AND DATE(time_in) = :date
        ORDER BY time_in DESC
    ");

    $stmt->execute([
        ':employee_id' => (int)$employee_id,
        ':date' => $date
    ]);

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($records);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
