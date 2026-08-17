<?php
/**
 * Save Employee Schedule API
 * Updates shift assignments and attendance records
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../../database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $employee_id = $data['employee_id'] ?? null;
    $date = $data['date'] ?? null;
    $shifts = $data['shifts'] ?? [];

    if (!$employee_id || !$date) {
        throw new Exception('Employee ID and date are required');
    }

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    // Verify employee exists
    $verify_query = "SELECT employee_id FROM employees WHERE employee_id = ? AND employment_status = 'Active'";
    $stmt = $conn->prepare($verify_query);
    $stmt->execute([$employee_id]);
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Employee not found');
    }

    // Start transaction
    $conn->beginTransaction();

    // Store schedule changes through the same tables used by the create,
    // assign, edit, and viewing flows.
    if (!empty($shifts)) {
        foreach ($shifts as $shift) {
            $start_time_raw = $shift['start_time'] ?? null;
            $end_time_raw = $shift['end_time'] ?? null;
            $break_start_raw = isset($shift['break_start']) ? $shift['break_start'] : null;
            $break_end_raw = isset($shift['break_end']) ? $shift['break_end'] : null;

            if (!$start_time_raw || !$end_time_raw) {
                $conn->rollBack();
                throw new Exception('Start and end time are required for each shift entry');
            }

            $start_ts = strtotime($start_time_raw);
            $end_ts = strtotime($end_time_raw);
            if ($start_ts === false || $end_ts === false) {
                $conn->rollBack();
                throw new Exception('Invalid time format for start or end time');
            }
            if ($start_ts >= $end_ts) {
                $conn->rollBack();
                throw new Exception('Start time must be before end time');
            }

            $start_only = date('H:i:s', $start_ts);
            $end_only = date('H:i:s', $end_ts);
            $find_shift = $conn->prepare("SELECT shift_id FROM ta_shifts WHERE start_time = ? AND end_time = ? LIMIT 1");
            $find_shift->execute([$start_only, $end_only]);
            $shift_row = $find_shift->fetch(PDO::FETCH_ASSOC);
            if ($shift_row) {
                $shift_id = $shift_row['shift_id'];
            } else {
                $shift_name = 'Custom ' . substr($start_only, 0, 5) . '-' . substr($end_only, 0, 5);
                $create_shift = $conn->prepare("INSERT INTO ta_shifts (shift_name, start_time, end_time, created_at, updated_at, is_active) VALUES (?, ?, ?, NOW(), NOW(), 1)");
                $create_shift->execute([$shift_name, $start_only, $end_only]);
                $shift_id = $conn->lastInsertId();
            }

            $deactivate = $conn->prepare("UPDATE ta_employee_shifts SET is_active = 0 WHERE employee_id = ? AND is_active = 1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)");
            $deactivate->execute([$employee_id, $date, $date]);
            $insert_assignment = $conn->prepare("INSERT INTO ta_employee_shifts (employee_id, shift_id, effective_from, effective_to, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
            $insert_assignment->execute([$employee_id, $shift_id, $date, $date]);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Schedule saved successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
