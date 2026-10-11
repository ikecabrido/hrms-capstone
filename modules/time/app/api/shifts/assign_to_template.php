<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Invalid JSON');

    $shift_id = isset($data['shift_id']) ? (int)$data['shift_id'] : 0;
    $employees = $data['employees'] ?? [];
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;

    if (!$shift_id) throw new Exception('shift_id required');
    if (empty($employees)) throw new Exception('employees required');
    if (!$start_date || !$end_date) throw new Exception('start_date and end_date required');

    $sd = new DateTime($start_date);
    $ed = new DateTime($end_date);
    if ($ed < $sd) throw new Exception('end_date must be >= start_date');

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    // fetch weekday template for this shift
    $wstmt = $conn->prepare("SELECT weekday, start_time, end_time, break_start, break_end, is_active FROM ta_shift_weekday_times WHERE shift_id = ? AND is_active = 1");
    $wstmt->execute([$shift_id]);
    $weekdays = [];
    while ($r = $wstmt->fetch(PDO::FETCH_ASSOC)) {
        $weekdays[(int)$r['weekday']] = [
            'start' => $r['start_time'],
            'end' => $r['end_time'],
            'break_start' => $r['break_start'],
            'break_end' => $r['break_end']
        ];
    }
    if (empty($weekdays)) throw new Exception('No active weekday configuration for this shift');

    $excludeSaturday = !empty($data['exclude_saturday']);

    $conn->beginTransaction();

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($sd, $interval, $ed->modify('+1 day'));

    $deactStmt = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 0 WHERE employee_id = ? AND is_active = 1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)");
    $checkEmpShift = $conn->prepare("SELECT employee_shift_id FROM ta_employee_shifts WHERE employee_id = ? AND shift_id = ? AND effective_from = ? LIMIT 1");
    $insEmpShift = $conn->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");

    $inserted = 0;
    foreach ($employees as $emp) {
        $empId = (int)$emp;
        foreach ($period as $date) {
            $dstr = $date->format('Y-m-d');
            $w = (int)$date->format('w');
            if ($w === 0) continue; // skip Sunday
            if ($excludeSaturday && $w === 6) continue; // skip Saturday when requested
            if (empty($weekdays[$w])) continue;

            // deactivate overlaps
            $deactStmt->execute([$empId, $dstr, $dstr]);

            // avoid duplicates
            $checkEmpShift->execute([$empId, $shift_id, $dstr]);
            if ($checkEmpShift->fetch(PDO::FETCH_ASSOC)) continue;

            $insEmpShift->execute([$empId, $shift_id, $dstr, $dstr]);
            $inserted++;
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'assigned_rows' => $inserted]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
