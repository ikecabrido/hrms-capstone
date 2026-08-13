<?php
/**
 * QR Attendance Controller for Time & Attendance Portal
 * Handles smooth QR scanning workflow with auto TIME_IN/TIME_OUT detection
 * This controller is self-contained in time_attendance/portal/
 */

date_default_timezone_set('Asia/Manila');

class QRAttendanceController
{
    private $conn;
    private $debugLog = '';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->debugLog = dirname(dirname(dirname(__DIR__))) . '/qr_debug.log';
    }

    /**
     * Handle QR attendance processing
     * Auto-detects TIME_IN or TIME_OUT based on today's record
     * Returns JSON response
     */
    public function processQRAttendance($employee_id, $qr_token)
    {
        try {
            $this->log("Processing QR attendance for employee: $employee_id, token: " . substr($qr_token, 0, 20) . "...");

            // Validate QR token (if needed)
            if (empty($qr_token)) {
                return $this->error('QR token is empty');
            }

            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            $this->log("Checking today's record for employee: $employee_id, date: $today");

            // Check if there's already a record for today
            $checkQuery = "SELECT attendance_id, time_in, time_out, status FROM ta_attendance 
                           WHERE employee_id = ? AND DATE(attendance_date) = ? 
                           LIMIT 1";
            
            $checkStmt = $this->conn->prepare($checkQuery);
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $checkStmt->execute([$employee_id, $today]);
            $result = $checkStmt->get_result();
            $record = $result->fetch_assoc();

            $this->log("Record check result: " . ($record ? json_encode($record) : 'NO RECORD'));

            // Determine action: TIME_IN or TIME_OUT
            $action = '';
            $message = '';

            if ($record && empty($record['time_in']) && isset($record['status']) && strtoupper($record['status']) === 'ABSENT') {
                $this->log("Attendance already marked absent - refusing QR scan");
                return $this->error('Your attendance has already been marked absent for today. Please contact HR for assistance.');
            }

            if (!$record) {
                // No record yet - this is TIME_IN
                $this->log("No record found - performing TIME_IN");
                $action = 'TIME_IN';
                
                // Get employee's shift
                $shift_query = "SELECT shift_id FROM ta_employee_shifts 
                               WHERE employee_id = ? 
                               AND effective_from <= ? 
                               AND (effective_to IS NULL OR effective_to >= ?)
                               AND is_active = 1
                               ORDER BY effective_from DESC 
                               LIMIT 1";
                
                $shift_stmt = $this->conn->prepare($shift_query);
                $shift_stmt->execute([$employee_id, $today, $today]);
                $shift_result = $shift_stmt->get_result();
                $shift_record = $shift_result->fetch_assoc();
                $shift_id = $shift_record['shift_id'] ?? NULL;

                $this->log("Shift ID: " . ($shift_id ?? 'NULL'));

                // Insert new attendance record
                $insertQuery = "INSERT INTO ta_attendance 
                               (employee_id, attendance_date, time_in, status, recorded_by, shift_id) 
                               VALUES (?, ?, ?, 'PRESENT', 'QR', ?)";
                
                $insertStmt = $this->conn->prepare($insertQuery);
                if (!$insertStmt) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }

                $insertStmt->execute([$employee_id, $today, $now, $shift_id]);
                $this->log("TIME_IN inserted successfully");

                $message = 'Time In recorded successfully at ' . date('H:i:s');
                return $this->success($message, $action, 'TIME_IN');

            } elseif (empty($record['time_in'])) {
                // Record exists but time_in is empty - this is TIME_IN
                $this->log("Record exists but time_in empty - performing TIME_IN");
                $action = 'TIME_IN';

                $updateQuery = "UPDATE ta_attendance 
                               SET time_in = ?, status = 'PRESENT', recorded_by = 'QR'
                               WHERE attendance_id = ?";
                
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->execute([$now, $record['attendance_id']]);
                $this->log("TIME_IN updated successfully");

                $message = 'Time In recorded successfully at ' . date('H:i:s');
                return $this->success($message, $action, 'TIME_IN');

            } elseif (empty($record['time_out'])) {
                // time_in exists but time_out is empty - this is TIME_OUT
                $this->log("Record exists with time_in but no time_out - performing TIME_OUT");
                $action = 'TIME_OUT';

                $updateQuery = "UPDATE ta_attendance 
                               SET time_out = ?, status = 'COMPLETED'
                               WHERE attendance_id = ?";
                
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->execute([$now, $record['attendance_id']]);
                $this->log("TIME_OUT updated successfully");

                // Calculate hours
                $getQuery = "SELECT time_in FROM ta_attendance WHERE attendance_id = ?";
                $getStmt = $this->conn->prepare($getQuery);
                $getStmt->execute([$record['attendance_id']]);
                $timeRecord = $getStmt->get_result()->fetch_assoc();

                $duration = $this->calculateDuration($timeRecord['time_in'], $now);
                $message = 'Time Out recorded successfully at ' . date('H:i:s') . ' | Duration: ' . $duration;
                return $this->success($message, $action, 'TIME_OUT');

            } else {
                // Both time_in and time_out exist - already completed
                $this->log("Attendance already completed for today");
                return $this->error('You have already timed out today at ' . date('H:i', strtotime($record['time_out'])));
            }

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            return $this->error('Error processing QR attendance: ' . $e->getMessage());
        }
    }

    /**
     * Calculate duration between two times
     */
    private function calculateDuration($timeIn, $timeOut)
    {
        $start = strtotime($timeIn);
        $end = strtotime($timeOut);
        $diff = $end - $start;
        $hours = floor($diff / 3600);
        $mins = floor(($diff % 3600) / 60);
        return sprintf("%dh %dm", $hours, $mins);
    }

    /**
     * Success response
     */
    private function success($message, $action, $actionType)
    {
        return [
            'success' => true,
            'message' => $message,
            'action' => $action,
            'action_type' => $actionType
        ];
    }

    /**
     * Error response
     */
    private function error($message)
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }

    /**
     * Log messages to file
     */
    private function log($message)
    {
        $timestamp = "[" . date('Y-m-d H:i:s') . "]";
        file_put_contents($this->debugLog, $timestamp . " " . $message . "\n", FILE_APPEND);
    }
}
?>
