<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/TimeDatabase.php';

try {
    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT shift_id, shift_name, start_time, end_time, is_active FROM ta_shifts ORDER BY shift_name ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $weekdayStmt = $conn->prepare("SELECT shift_id, weekday FROM ta_shift_weekday_times WHERE is_active = 1 ORDER BY weekday ASC");
    $weekdayStmt->execute();
    $weekdaysByShift = [];
    foreach ($weekdayStmt->fetchAll(PDO::FETCH_ASSOC) as $weekdayRow) {
        $weekdaysByShift[(int)$weekdayRow['shift_id']][] = (int)$weekdayRow['weekday'];
    }

    $assignmentWeekdayStmt = $conn->prepare(
        "SELECT shift_id, DAYOFWEEK(effective_from) - 1 AS weekday
         FROM ta_employee_shifts
         WHERE is_active = 1
         GROUP BY shift_id, DAYOFWEEK(effective_from) - 1"
    );
    $assignmentWeekdayStmt->execute();
    foreach ($assignmentWeekdayStmt->fetchAll(PDO::FETCH_ASSOC) as $assignmentWeekday) {
        $shiftId = (int)$assignmentWeekday['shift_id'];
        $weekdaysByShift[$shiftId][] = (int)$assignmentWeekday['weekday'];
    }

    foreach ($weekdaysByShift as $shiftId => $weekdays) {
        $weekdaysByShift[$shiftId] = array_values(array_unique(array_map('intval', $weekdays)));
        sort($weekdaysByShift[$shiftId]);
    }

    $templates = array_map(function($r) use ($weekdaysByShift) {
        return [
            'shift_id' => (int)$r['shift_id'],
            'shift_name' => $r['shift_name'],
            'start_time' => $r['start_time'],
            'end_time' => $r['end_time'],
            'is_active' => (int)$r['is_active'],
            'weekdays' => $weekdaysByShift[(int)$r['shift_id']] ?? []
        ];
    }, $rows ?: []);

    echo json_encode(['success' => true, 'templates' => $templates]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

