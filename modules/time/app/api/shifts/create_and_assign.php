<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Invalid JSON payload');

    $shift_name = trim($data['shift_name'] ?? '');
    $weekdays = $data['weekdays'] ?? []; // expected: [1=>['assigned'=>1,'start'=>'08:00','end'=>'17:00','break_start'=>...], ...]
    $employees = $data['employees'] ?? []; // array of employee_id
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;

    if (!$shift_name) throw new Exception('shift_name is required');
    if (empty($weekdays)) throw new Exception('weekdays template is required');
    if (!$start_date || !$end_date) throw new Exception('start_date and end_date are required');

    $sd = new DateTime($start_date);
    $ed = new DateTime($end_date);
    if ($ed < $sd) throw new Exception('end_date must be >= start_date');

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    $includeSaturday = 0;
    foreach ($weekdays as $w => $cfg) {
        if ((int)$w === 6 && !empty($cfg['assigned'])) {
            $includeSaturday = 1;
            break;
        }
    }

    // Determine a representative start/end time for the shift record (first assigned weekday)
    $rep_start = null; $rep_end = null;
    foreach ($weekdays as $w => $cfg) {
        if (!empty($cfg['assigned'])) {
            if (isset($cfg['start']) && isset($cfg['end'])) {
                $rep_start = date('H:i:s', strtotime($cfg['start']));
                $rep_end = date('H:i:s', strtotime($cfg['end']));
                break;
            }
        }
    }

    // Insert into ta_shifts
    $insShift = $conn->prepare("INSERT INTO ta_shifts (shift_name, start_time, end_time, include_saturday, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, NOW(), NOW(), 1)");
    $insShift->execute([$shift_name, $rep_start, $rep_end, $includeSaturday]);
    $shift_id = $conn->lastInsertId();

    // Insert weekday times into ta_shift_weekday_times (skip Sunday - weekday 0)
    $insertWeekday = $conn->prepare("INSERT INTO ta_shift_weekday_times (shift_id, weekday, start_time, end_time, break_start, break_end, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
    foreach ($weekdays as $w => $cfg) {
        // ensure numeric weekday
        $wInt = (int)$w;
        if ($wInt === 0) continue; // explicitly exclude Sundays
        $assigned = !empty($cfg['assigned']);
        if (!$assigned) continue;
        $s = isset($cfg['start']) ? date('H:i:s', strtotime($cfg['start'])) : null;
        $e = isset($cfg['end']) ? date('H:i:s', strtotime($cfg['end'])) : null;
        $bs = isset($cfg['break_start']) && $cfg['break_start'] !== '' ? date('H:i:s', strtotime($cfg['break_start'])) : null;
        $be = isset($cfg['break_end']) && $cfg['break_end'] !== '' ? date('H:i:s', strtotime($cfg['break_end'])) : null;
        if (!$s || !$e) {
            $conn->rollBack();
            throw new Exception("Weekday {$wInt} requires start and end time");
        }
        $insertWeekday->execute([$shift_id, $wInt, $s, $e, $bs, $be]);
    }

    // Assign employees: for each employee, for each date in range, if its weekday assigned then deactivate overlaps and create ta_employee_shifts
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($sd, $interval, $ed->modify('+1 day'));

    $deactStmt = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 0 WHERE employee_id = ? AND is_active = 1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)");
    $checkEmpShift = $conn->prepare("SELECT employee_shift_id, is_active FROM ta_employee_shifts WHERE employee_id = ? AND shift_id = ? AND effective_from = ? LIMIT 1");
    $reactivateStmt = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 1, updated_at = NOW() WHERE employee_shift_id = ? LIMIT 1");
    $insEmpShift = $conn->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");

    $skippedDays = [
        'sunday' => 0,
        'saturday_excluded' => 0,
        'saturday_no_template' => 0,
        'no_weekday_template' => 0,
        'duplicate' => 0,
    ];
    $inserted = 0;
    foreach ($employees as $emp) {
        $empId = (int)$emp;
        foreach ($period as $date) {
            $dstr = $date->format('Y-m-d');
            $w = (int)$date->format('w'); // 0=Sun

            if ($w === 0) {
                $skippedDays['sunday']++;
                continue;
            }

            if ($w === 6) {
                if (!$includeSaturday) {
                    $skippedDays['saturday_excluded']++;
                    continue;
                }
                if (empty($weekdays[$w]) || empty($weekdays[$w]['assigned'])) {
                    $skippedDays['saturday_no_template']++;
                    continue;
                }
            }

            if (empty($weekdays[$w]) || empty($weekdays[$w]['assigned'])) {
                $skippedDays['no_weekday_template']++;
                continue;
            }

            $checkEmpShift->execute([$empId, $shift_id, $dstr]);
            $existingRow = $checkEmpShift->fetch(PDO::FETCH_ASSOC);
            if ($existingRow) {
                if ((int)($existingRow['is_active'] ?? 0) === 0) {
                    $reactivateStmt->execute([(int)$existingRow['employee_shift_id']]);
                }
                $skippedDays['duplicate']++;
                continue;
            }

            $deactStmt->execute([$empId, $dstr, $dstr]);
            $insEmpShift->execute([$empId, $shift_id, $dstr, $dstr]);
            $inserted++;
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'shift_id' => $shift_id, 'assigned_rows' => $inserted, 'skipped_days' => $skippedDays, 'saturday_excluded' => $skippedDays['saturday_excluded']]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
