<?php
/**
 * QR Attendance Process API
 * Handles QR confirmation submission from employee portal
 * Records attendance for employee after QR token validation
 */

// Log all errors to file
@mkdir(__DIR__ . '/../../logs', 0777, true);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/qr-process-errors.log');
ini_set('display_errors', 1);

// Log that file was accessed
file_put_contents(__DIR__ . '/../../logs/qr-access.log', date('Y-m-d H:i:s') . " - API called\n", FILE_APPEND);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }

    $employee_id = $input['employee_id'] ?? null;
    $token = $input['token'] ?? null;

    if (!$employee_id || !$token) {
        http_response_code(400);
        throw new Exception('Missing employee_id or token');
    }

    // Validate token format
    if (strlen($token) !== 64) {
        http_response_code(400);
        throw new Exception('Invalid token format');
    }

    // Connect to database
    require_once __DIR__ . '/../../../database/db.php';
    $db = Database::getInstance()->getConnection();

    // Validate token exists, is not expired, and hasn't been used
    $stmt = $db->prepare("
        SELECT token_id, generated_for_date, expires_at, used, used_by
        FROM ta_attendance_tokens
        WHERE token = :token
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $token_record = $stmt->fetch();

    if (!$token_record) {
        http_response_code(404);
        throw new Exception('Token not found');
    }

    // Check if token has expired
    if (new DateTime($token_record['expires_at']) < new DateTime()) {
        http_response_code(400);
        throw new Exception('Token has expired');
    }

    // Check if token has already been used
    if ($token_record['used']) {
        http_response_code(400);
        throw new Exception('Token has already been used');
    }

    // Verify employee exists
    $stmt = $db->prepare("
        SELECT employee_id, employee_first_name, employee_last_name
        FROM hr_employees
        WHERE employee_id = :employee_id
        LIMIT 1
    ");
    $stmt->execute(['employee_id' => $employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        throw new Exception('Employee not found');
    }

    // Get current date and time
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $current_date = $now->format('Y-m-d');
    $current_time = $now->format('Y-m-d H:i:s');

    // Determine if this is a time_in or time_out based on existing attendance record
    $stmt = $db->prepare("
        SELECT attendance_id, time_in, time_out
        FROM ta_attendance
        WHERE employee_id = :employee_id
        AND attendance_date = :attendance_date
        ORDER BY (time_in IS NOT NULL AND time_out IS NULL) DESC, created_at DESC, attendance_id DESC
        LIMIT 1
    ");
    $stmt->execute([
        'employee_id' => $employee_id,
        'attendance_date' => $current_date
    ]);
    $attendance_record = $stmt->fetch();

    if ($attendance_record && empty($attendance_record['time_in']) && isset($attendance_record['status']) && $attendance_record['status'] === 'ABSENT') {
        http_response_code(403);
        throw new Exception('Your attendance has already been marked ABSENT for today. Please contact HR.');
    }

    if ($attendance_record && $attendance_record['time_in'] && !$attendance_record['time_out']) {
        $timeInTimestamp = strtotime($attendance_record['time_in']);
        $elapsedSeconds = strtotime($now) - $timeInTimestamp;
        $minimumTimeOutSeconds = 10800;

        if ($elapsedSeconds < $minimumTimeOutSeconds) {
            $remainingSeconds = $minimumTimeOutSeconds - $elapsedSeconds;
            $hours = floor($remainingSeconds / 3600);
            $minutes = floor(($remainingSeconds % 3600) / 60);
            $seconds = $remainingSeconds % 60;
            $timeParts = [];

            if ($hours > 0) {
                $timeParts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
            }
            if ($minutes > 0) {
                $timeParts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            }
            if ($seconds > 0 && count($timeParts) < 2) {
                $timeParts[] = $seconds . ' second' . ($seconds > 1 ? 's' : '');
            }

            return $this->error('You can’t time out right now. Try again after ' . ($timeParts ? implode(' ', $timeParts) : 'a moment') . '.');
        }
        // This is a time_out
        $action = 'TIME_OUT';
        $attendance_id = $attendance_record['attendance_id'];
        
        // Update existing attendance record with time_out
        $stmt = $db->prepare("
            UPDATE ta_attendance
            SET time_out = :time_out,
                recorded_by = 'QR',
                updated_at = NOW()
            WHERE attendance_id = :attendance_id
        ");
        $result = $stmt->execute([
            'time_out' => $current_time,
            'attendance_id' => $attendance_id
        ]);

        if (!$result) {
            throw new Exception('Failed to update attendance record');
        }

        $message = 'Time out recorded successfully';
    } else {
        // This is a time_in (create new record or update existing empty one)
        $action = 'TIME_IN';

        // Get employee's shift for today
        $stmt = $db->prepare("
            SELECT tas.shift_id, s.shift_start_time
            FROM ta_shift_assignments tas
            JOIN hr_shifts s ON tas.shift_id = s.shift_id
            WHERE tas.employee_id = :employee_id
            AND tas.assigned_date = :assigned_date
            AND tas.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            'employee_id' => $employee_id,
            'assigned_date' => $current_date
        ]);
        $shift = $stmt->fetch();

        $shift_id = $shift ? $shift['shift_id'] : null;

        if ($attendance_record) {
            // Update existing record with time_in
            $attendance_id = $attendance_record['attendance_id'];
            $stmt = $db->prepare("
                UPDATE ta_attendance
                SET time_in = :time_in,
                    recorded_by = 'QR',
                    status = 'PRESENT',
                    updated_at = NOW()
                WHERE attendance_id = :attendance_id
            ");
            $result = $stmt->execute([
                'time_in' => $current_time,
                'attendance_id' => $attendance_id
            ]);
        } else {
            // Create new attendance record
            $stmt = $db->prepare("
                INSERT INTO ta_attendance (
                    employee_id,
                    shift_id,
                    attendance_date,
                    time_in,
                    recorded_by,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :employee_id,
                    :shift_id,
                    :attendance_date,
                    :time_in,
                    'QR',
                    'PRESENT',
                    NOW(),
                    NOW()
                )
            ");
            $result = $stmt->execute([
                'employee_id' => $employee_id,
                'shift_id' => $shift_id,
                'attendance_date' => $current_date,
                'time_in' => $current_time
            ]);
            
            $attendance_id = $db->lastInsertId();
        }

        if (!$result) {
            throw new Exception('Failed to record attendance');
        }

        $message = 'Time in recorded successfully';
    }

    // Mark token as used
    $stmt = $db->prepare("
        UPDATE ta_attendance_tokens
        SET used = 1,
            used_by = :used_by,
            used_at = NOW()
        WHERE token = :token
    ");
    $stmt->execute([
        'used_by' => $employee_id,
        'token' => $token
    ]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'action' => $action,
            'employee_name' => trim($employee['employee_first_name'] . ' ' . $employee['employee_last_name']),
            'timestamp' => $current_time,
            'date' => $current_date
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('QR Process Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'QR_PROCESS_ERROR'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('QR Process Fatal Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred',
        'error_code' => 'FATAL_ERROR'
    ]);
}
?>
