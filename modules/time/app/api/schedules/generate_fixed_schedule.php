<?php
/**
 * Generate Fixed Schedule API
 * Saves per-date fixed shifts using the same ta_shifts and ta_employee_shifts
 * tables used by the create and assign APIs.
 */
header('Content-Type: application/json');

// This endpoint is consumed as JSON. Prevent PHP notices/warnings from being
// prepended to the response and turning it into invalid JSON in the browser.
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../../../../database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Invalid JSON data');

    $employee_id = $data['employee_id'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    $days = $data['days'] ?? []; // expected format: [0=>['start'=>'08:00','end'=>'17:00'], 1=>...]

    if (!$employee_id || !$start_date || !$end_date) {
        throw new Exception('employee_id, start_date and end_date are required');
    }

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    // Validate employee exists
    $emp_check = $conn->prepare("SELECT employee_id FROM employees WHERE employee_id = ? OR employee_id LIKE ? LIMIT 1");
    $emp_check->execute([$employee_id, $employee_id]);
    if ($emp_check->rowCount() === 0) {
        throw new Exception('Employee not found');
    }

    // Basic validation of dates
    $sd = new DateTime($start_date);
    $ed = new DateTime($end_date);
    if ($ed < $sd) throw new Exception('end_date must be >= start_date');

    $conn->beginTransaction();

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($sd, $interval, $ed->modify('+1 day'));

    $insertCount = 0;

    foreach ($period as $date) {
        $dstr = $date->format('Y-m-d');
        $w = (int)$date->format('w'); // 0=Sunday

        if (!isset($days[$w])) continue; // day not selected

        $cfg = $days[$w];
        $start_time = $cfg['start'] ?? null;
        $end_time = $cfg['end'] ?? null;

        if (!$start_time || !$end_time) {
            $conn->rollBack();
            throw new Exception('Start and end time required for selected weekdays');
        }

        // validate time formats and logical order
        $start_ts = strtotime($start_time);
        $end_ts = strtotime($end_time);
        if ($start_ts === false || $end_ts === false) {
            $conn->rollBack();
            throw new Exception('Invalid start or end time format');
        }
        if ($start_ts >= $end_ts) {
            $conn->rollBack();
            throw new Exception('Start time must be before end time');
        }

        $break_start = isset($cfg['break_start']) ? $cfg['break_start'] : null;
        $break_end = isset($cfg['break_end']) ? $cfg['break_end'] : null;

        if ($break_start || $break_end) {
            if (!$break_start || !$break_end) {
                $conn->rollBack();
                throw new Exception('Both break_start and break_end must be provided if specifying a break');
            }
            $bstart_ts = strtotime($break_start);
            $bend_ts = strtotime($break_end);
            if ($bstart_ts === false || $bend_ts === false) {
                $conn->rollBack();
                throw new Exception('Invalid break time format');
            }
            // ensure break inside work window
            if ($bstart_ts < $start_ts || $bend_ts > $end_ts || $bstart_ts >= $bend_ts) {
                $conn->rollBack();
                throw new Exception('Break times must be within the work period and break start < break end');
            }
            // normalize
            $break_start = date('H:i:s', $bstart_ts);
            $break_end = date('H:i:s', $bend_ts);
        }

        // Map the time window to a ta_shifts record, as the normal create flow does.
        $shift_start_only = date('H:i:s', $start_ts);
        $shift_end_only = date('H:i:s', $end_ts);

        // Try to find an existing shift with the same start and end times
        $shiftStmt = $conn->prepare("SELECT shift_id FROM ta_shifts WHERE start_time = ? AND end_time = ? LIMIT 1");
        $shiftStmt->execute([$shift_start_only, $shift_end_only]);
        $shiftRow = $shiftStmt->fetch(PDO::FETCH_ASSOC);
        if ($shiftRow && isset($shiftRow['shift_id'])) {
            $shift_id = $shiftRow['shift_id'];
        } else {
            // Create a simple custom shift record
            $shiftName = 'Custom ' . substr($shift_start_only,0,5) . '-' . substr($shift_end_only,0,5);
            $insShift = $conn->prepare("INSERT INTO ta_shifts (shift_name, start_time, end_time, created_at, updated_at, is_active) VALUES (?, ?, ?, NOW(), NOW(), 1)");
            $insShift->execute([$shiftName, $shift_start_only, $shift_end_only]);
            $shift_id = $conn->lastInsertId();
        }

        // Deactivate any currently active assignments overlapping this date for the employee
        $deact = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 0 WHERE employee_id = ? AND is_active = 1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)");
        $deact->execute([$employee_id, $dstr, $dstr]);

        // Insert into ta_employee_shifts if not already present for this employee/date/shift
        $checkEmpShift = $conn->prepare("SELECT employee_shift_id FROM ta_employee_shifts WHERE employee_id = ? AND shift_id = ? AND effective_from = ? LIMIT 1");
        $checkEmpShift->execute([$employee_id, $shift_id, $dstr]);
        $existsEmpShift = $checkEmpShift->fetch(PDO::FETCH_ASSOC);
        if (!$existsEmpShift) {
            $insEmpShift = $conn->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
            // For single-date custom schedules, set effective_to equal to the date
            $insEmpShift->execute([$employee_id, $shift_id, $dstr, $dstr]);
        }

        $insertCount++;
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'inserted' => $insertCount,
        'message' => 'Fixed schedule generated'
    ]);

} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    if (ob_get_length()) ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
