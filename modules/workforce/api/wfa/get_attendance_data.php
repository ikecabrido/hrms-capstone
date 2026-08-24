<?php
/**
 * Attendance Data API
 * Returns recent attendance records for dashboard
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get limit from query parameter (default 50)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    
    // Get recent attendance records
    $query = "
        SELECT 
            a.attendance_id,
            a.employee_id,
            e.full_name,
            e.department,
            a.attendance_date as date,
            a.time_in,
            a.time_out,
            a.status,
            a.approval_remarks as remarks
        FROM ta_attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY a.attendance_date DESC, a.time_in DESC
        LIMIT ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $days, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get attendance summary statistics
    $summary_query = "
        SELECT 
            COUNT(DISTINCT DATE(attendance_date)) as working_days,
            SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as total_late,
            SUM(CASE WHEN status = 'ON_LEAVE' THEN 1 ELSE 0 END) as total_on_leave,
            ROUND(SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as attendance_rate
        FROM ta_attendance
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ";
    
    $stmt = $db->prepare($summary_query);
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc() ?: [];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'records' => $attendance_records,
            'total_records' => count($attendance_records),
            'summary' => $summary
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
