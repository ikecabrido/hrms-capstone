<?php
/**
 * API: Get today's employees with status including shift exclusions
 * Returns attendance data with consideration for shift exclusions
 */

require_once(__DIR__ . '/../../../../database/db.php');
require_once(__DIR__ . '/../models/Attendance.php');

header('Content-Type: application/json');

try {
    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();
    
    $attendanceModel = new Attendance();

    $today = date('Y-m-d');

    // Check if today is a holiday
    $queryHoliday = "SELECT holiday_id FROM ta_holidays 
                     WHERE holiday_date = :today 
                     AND is_active = 1 
                     LIMIT 1";
    
    $stmtHoliday = $db->prepare($queryHoliday);
    $stmtHoliday->bindParam(':today', $today);
    $stmtHoliday->execute();
    
    $isHolidayToday = $stmtHoliday->rowCount() > 0;

    // Get all employees' attendance for today
    $query = "SELECT 
                e.employee_id,
                e.full_name,
                e.department,
                e.position,
                ta.attendance_id,
                ta.time_in,
                ta.time_out,
                ta.attendance_date
            FROM employees e
            LEFT JOIN ta_attendance ta ON e.employee_id = ta.employee_id 
                AND ta.attendance_date = :today
            WHERE e.status = 'ACTIVE'
            ORDER BY e.full_name ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':today', $today);
    $stmt->execute();

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendanceData = [];
    foreach ($records as $record) {
        $status = 'ABSENT';

        if ($isHolidayToday) {
            $status = 'HOLIDAY';
        } elseif ($attendanceModel->hasShiftExclusionForDate($record['employee_id'], $today)) {
            // Employee has a shift exclusion (e.g., Saturday) - they shouldn't work
            $status = 'NO_SHIFT_TODAY';
        } elseif ($record['time_in']) {
            // Check if they're late (more than 9 AM)
            $timeInObj = new DateTime($record['time_in']);
            $status = $timeInObj->format('H') > 9 ? 'LATE' : 'PRESENT';
        }

        $record['status'] = $status;
        $attendanceData[] = $record;
    }

    echo json_encode([
        'success' => true,
        'today' => $today,
        'is_holiday' => $isHolidayToday,
        'records' => $attendanceData,
        'count' => count($attendanceData)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>