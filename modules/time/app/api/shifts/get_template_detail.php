<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../database/db.php';

try {
    if (!isset($_GET['shift_id'])) throw new Exception('shift_id required');
    $shift_id = (int)$_GET['shift_id'];

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT shift_id, shift_name, start_time, end_time, break_duration, description, include_saturday, is_active FROM ta_shifts WHERE shift_id = ? LIMIT 1");
    $stmt->execute([$shift_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$shift) throw new Exception('Shift not found');

    $wstmt = $conn->prepare("SELECT weekday, start_time, end_time, break_start, break_end, is_active FROM ta_shift_weekday_times WHERE shift_id = ? ORDER BY weekday ASC");
    $wstmt->execute([$shift_id]);
    $weekdays = [];
    while ($r = $wstmt->fetch(PDO::FETCH_ASSOC)) {
        $weekdays[(int)$r['weekday']] = [
            'start' => $r['start_time'],
            'end' => $r['end_time'],
            'break_start' => $r['break_start'],
            'break_end' => $r['break_end'],
            'is_active' => (int)$r['is_active']
        ];
    }

    $template = [
        'shift_id' => (int)$shift['shift_id'],
        'shift_name' => $shift['shift_name'],
        'start_time' => $shift['start_time'],
        'end_time' => $shift['end_time'],
        'break_duration' => isset($shift['break_duration']) ? (int)$shift['break_duration'] : 0,
        'description' => $shift['description'],
        'include_saturday' => isset($shift['include_saturday']) ? (int)$shift['include_saturday'] : 0,
        'is_active' => (int)$shift['is_active'],
        'weekdays' => $weekdays
    ];

    echo json_encode(['success' => true, 'template' => $template]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
