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
    $employeeShiftModel = new EmployeeShift($conn);
    $employeeShiftModel->ensureSchema();

    $shiftRowStmt = $conn->prepare("SELECT include_saturday FROM ta_shifts WHERE shift_id = ? LIMIT 1");
    $shiftRowStmt->execute([$shift_id]);
    $shiftMeta = $shiftRowStmt->fetch(PDO::FETCH_ASSOC);
    $includeSaturday = (int)($shiftMeta['include_saturday'] ?? 0);

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

    $conn->beginTransaction();

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($sd, $interval, $ed->modify('+1 day'));

    $deactStmt = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 0 WHERE employee_id = ? AND is_active = 1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)");
    $checkEmpShift = $conn->prepare("SELECT employee_shift_id, is_active FROM ta_employee_shifts WHERE employee_id = ? AND shift_id = ? AND effective_from = ? LIMIT 1");
    $reactivateStmt = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 1, updated_at = NOW() WHERE employee_shift_id = ? LIMIT 1");
    $insEmpShift = $conn->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, assigned_days, start_time, end_time, break_start, break_end, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");

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
            $w = (int)$date->format('w');

            if ($w === 0) {
                $skippedDays['sunday']++;
                continue;
            }

            if ($w === 6) {
                if (!$includeSaturday) {
                    $skippedDays['saturday_excluded']++;
                    continue;
                }
                if (empty($weekdays[$w])) {
                    $skippedDays['saturday_no_template']++;
                    continue;
                }
            }

            if (empty($weekdays[$w])) {
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
            $dayConfig = $weekdays[$w] ?? [];
            $dayStart = $dayConfig['start'] ?? null;
            $dayEnd = $dayConfig['end'] ?? null;
            $dayBreakStart = $dayConfig['break_start'] ?? null;
            $dayBreakEnd = $dayConfig['break_end'] ?? null;
            $insEmpShift->execute([$empId, $shift_id, $dstr, $dstr, (string)$w, $dayStart, $dayEnd, $dayBreakStart, $dayBreakEnd]);
            $inserted++;
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'assigned_rows' => $inserted,
        'skipped_days' => $skippedDays,
        'saturday_excluded' => $skippedDays['saturday_excluded'],
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
