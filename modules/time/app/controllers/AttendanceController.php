<?php
/**
 * Attendance Controller for Time & Attendance System
 * Handles attendance recording, time in/out, and QR processing
 */

// Set PHP timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__. '/../models/Attendance.php';
require_once __DIR__. '/../models/EmployeeShift.php';
require_once __DIR__. '/../services/AttendanceValidationService.php';
require_once __DIR__. '/../helpers/QRHelper.php';
require_once __DIR__. '/../helpers/Helper.php';
require_once __DIR__. '/../helpers/AuditLog.php';
require_once __DIR__. '/../helpers/OpenAttendanceFinalizer.php';
require_once __DIR__. '/../core/Session.php';

class AttendanceController
{
    private $attendanceModel;
    private $employeeModel;
    private $employeeShiftModel;
    private $validationService;
    private $qrHelper;
    private $auditLog;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->employeeModel = new Employee();
        $this->employeeShiftModel = new EmployeeShift(TimeDatabase::getInstance());
        $this->validationService = new \App\Services\AttendanceValidationService();
        $this->qrHelper = new QRHelper();
        $this->auditLog = new AuditLog();
    }

    /**
     * Enforce a short cooldown between repeated QR scan requests.
     */
    private function enforceQrCooldown($employee_id, $cooldownSeconds = 5)
    {
        Session::start();
        $now = time();
        $cooldownUntil = Session::get('qr_scan_cooldown_until');

        if (!empty($cooldownUntil) && $now < (int)$cooldownUntil) {
            $timeLeftSeconds = max(1, (int)$cooldownUntil - $now);
            return [
                'allowed' => false,
                'time_left_seconds' => $timeLeftSeconds,
                'message' => 'Please wait ' . $timeLeftSeconds . ' second' . ($timeLeftSeconds === 1 ? '' : 's') . ' before scanning again.'
            ];
        }

        Session::set('qr_scan_cooldown_until', $now + $cooldownSeconds);
        return [
            'allowed' => true,
            'time_left_seconds' => 0
        ];
    }

    /**
     * Enforce a 3-hour minimum before time-out is allowed after time-in.
     */
    private function enforceTimeoutWindow($timeInTimestamp)
    {
        $elapsedSeconds = time() - (int) $timeInTimestamp;
        if ($elapsedSeconds >= 10800) {
            return [
                'allowed' => true,
                'time_left_seconds' => 0,
                'time_left_text' => null
            ];
        }

        $remainingSeconds = 10800 - $elapsedSeconds;
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

        return [
            'allowed' => false,
            'time_left_seconds' => max(1, $remainingSeconds),
            'time_left_text' => implode(' ', $timeParts) ?: 'a moment',
            'message' => 'You can time out after ' . (implode(' ', $timeParts) ?: 'a moment') . '.'
        ];
    }

    /**
     * Auto-finalize an open attendance record at shift end when the employee forgot to time out.
     */
    private function autoFinalizeOpenAttendanceIfNeeded($employee_id, $attendanceDate = null, $now = null)
    {
        return OpenAttendanceFinalizer::finalizeOpenAttendanceIfNeeded(
            $this->attendanceModel,
            $this->validationService,
            $employee_id,
            $attendanceDate,
            $now
        );
    }

    /**
     * Record Time In for an employee
     * 
     * @param int $employee_id - Employee ID
     * @param string $method - Recording method (MANUAL or QR)
     * @return array - Response array with success status and message
     */
    public function timeIn($employee_id, $method = 'MANUAL')
    {
        Session::start();
        $user_id = Session::get('user_id');
        $todayDate = date('Y-m-d');

        try {
            // STEP 1: Validate if employee can time-in (shift check, holiday check, weekend check)
            $validation = $this->validationService->validateTimeIn($employee_id, $todayDate);

            // If not valid or cannot time-in, return error
            if (!$validation['can_timein']) {
                $this->auditLog->log('TIME_IN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => $validation['reason'], 'status' => $validation['status']], 'FAILED', $validation['message']);
                
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'status' => $validation['status'],
                    'reason' => $validation['reason'] ?? 'No shift assigned',
                    'can_timein' => false
                ];
            }

            // STEP 2: Check if employee has already timed in today
            $existingRecord = $this->attendanceModel->getTodayAttendance($employee_id, $todayDate);

            if ($existingRecord && empty($existingRecord['time_in']) && isset($existingRecord['status']) && $existingRecord['status'] === 'ABSENT') {
                $this->auditLog->log('TIME_IN_FAILED', $user_id, $employee_id, $existingRecord['attendance_id'], 
                    ['reason' => 'Marked absent', 'status' => 'ABSENT'], 'FAILED', 'Cannot time in because attendance is already marked absent for today');
                return [
                    'success' => false,
                    'message' => 'Your attendance for today has already been marked ABSENT. Please contact HR to appeal or correct this status.',
                    'status' => 'ABSENT'
                ];
            }

            if ($existingRecord && !empty($existingRecord['time_in'])) {
                $this->auditLog->log('TIME_IN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Already timed in'], 'FAILED', 'Employee already has time in record for today');
                return [
                    'success' => false,
                    'message' => 'You have already timed in today at '. Helper::formatTime($existingRecord['time_in'])
                ];
            }

            // STEP 3: Determine status based on shift time
            $status = $this->validationService->determineStatus($employee_id, date('Y-m-d H:i:s'), $todayDate);

            // STEP 4: Insert time in record with status
            if ($this->attendanceModel->timeIn($employee_id, $method, $status)) {
                // Get the record to get attendance_id
                $record = $this->attendanceModel->getTodayAttendance($employee_id, $todayDate);

                // Calculate minutes late if applicable
                $minutesLate = 0;
                if ($status === 'LATE') {
                    $minutesLate = $this->validationService->calculateMinutesLate($employee_id, $record['time_in'], $todayDate);
                }

                // Get full employee name
                $employee = $this->employeeModel->getById($employee_id);

                // Log success
                $this->auditLog->log('TIME_IN_SUCCESS', $user_id, $employee_id, $record['attendance_id'], 
                    ['method' => $method, 'status' => $status, 'minutes_late' => $minutesLate], 'SUCCESS');

                // Build response message
                $message = 'Time In recorded successfully at '. Helper::formatTime($record['time_in']);
                if ($status === 'LATE') {
                    $message.= " (LATE by {$minutesLate} minutes)";
                }

                return [
                    'success' => true,
                    'message' => $message,
                    'employee_name' => $employee['full_name'],
                    'time_in' => $record['time_in'],
                    'status' => $status,
                    'minutes_late' => $minutesLate
                ];
            } else {
                $this->auditLog->log('TIME_IN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Database error'], 'FAILED', 'Failed to insert time in record');
                return [
                    'success' => false,
                    'message' => 'Failed to record time in. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("TimeIn CATCH: employee_id={$employee_id} existingRecord=" . json_encode($existingRecord ?? null) . " error=" . $e->getMessage() . " file=" . $e->getFile() . " line=" . $e->getLine());
            error_log("TimeIn Error: ". $e->getMessage());
            $this->auditLog->log('TIME_IN_ERROR', $user_id, $employee_id, null, 
                ['error' => $e->getMessage()], 'FAILED', $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please contact HR.'
            ];
        }
    }

    /**
     * Record Time Out for an employee
     * 
     * @param int $employee_id - Employee ID
     * @param string $method - Recording method (MANUAL or QR)
     * @return array - Response array with success status and message
     */
    public function timeOut($employee_id, $method = 'MANUAL')
    {
        Session::start();
        $user_id = Session::get('user_id');

        try {
            $this->autoFinalizeOpenAttendanceIfNeeded($employee_id, date('Y-m-d'));

            // Get today's attendance record
            $record = $this->attendanceModel->getTodayAttendance($employee_id);

            if (!$record) {
                $this->auditLog->log('TIME_OUT_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'No time in record'], 'FAILED', 'No attendance record found for today');
                return [
                    'success' => false,
                    'message' => 'Please record Time In first.'
                ];
            }

            if (!empty($record['time_out'])) {
                $this->auditLog->log('TIME_OUT_FAILED', $user_id, $employee_id, $record['attendance_id'], 
                    ['reason' => 'Already timed out'], 'FAILED', 'Employee already has time out record');
                return [
                    'success' => false,
                    'message' => 'You have already timed out today at '. Helper::formatTime($record['time_out'])
                ];
            }

            if (!empty($record['time_in'])) {
                $timeoutWindow = $this->enforceTimeoutWindow(strtotime($record['time_in']));
                if (!$timeoutWindow['allowed']) {
                    $this->auditLog->log('TIME_OUT_FAILED', $user_id, $employee_id, $record['attendance_id'], 
                        ['reason' => 'Timeout window not met', 'time_left_seconds' => $timeoutWindow['time_left_seconds']], 'FAILED', $timeoutWindow['message']);
                    return [
                        'success' => false,
                        'message' => $timeoutWindow['message'],
                        'time_left_seconds' => $timeoutWindow['time_left_seconds'],
                        'time_left_text' => $timeoutWindow['time_left_text'],
                        'action' => 'TIME_OUT'
                    ];
                }
            }

            // Update time out
            if ($this->attendanceModel->timeOut($record['attendance_id'])) {
                // Get updated record
                $updatedRecord = $this->attendanceModel->getTodayAttendance($employee_id);

                // Calculate duration and hours
                $duration = Helper::calculateDuration($updatedRecord['time_in'], $updatedRecord['time_out']);
                $hoursData = Helper::calculateHours($updatedRecord['time_in'], $updatedRecord['time_out'], 8);

                // Update attendance record with hours data
                $this->attendanceModel->updateHours($record['attendance_id'], $hoursData);

                // Get employee name
                $employee = $this->employeeModel->getById($employee_id);

                // Log success
                $this->auditLog->log('TIME_OUT_SUCCESS', $user_id, $employee_id, $record['attendance_id'], 
                    ['duration' => $duration, 'hours' => $hoursData], 'SUCCESS');

                return [
                    'success' => true,
                    'message' => 'Time Out recorded successfully at '. Helper::formatTime($updatedRecord['time_out']),
                    'employee_name' => $employee['full_name'],
                    'time_out' => $updatedRecord['time_out'],
                    'duration' => $duration,
                    'total_hours' => $hoursData['total_hours'],
                    'regular_hours' => $hoursData['regular_hours'],
                    'overtime_hours' => $hoursData['overtime_hours']
                ];
            } else {
                $updatedRecord = $this->attendanceModel->getTodayAttendance($employee_id);
                if ($updatedRecord && !empty($updatedRecord['time_out'])) {
                    $this->auditLog->log('TIME_OUT_FAILED', $user_id, $employee_id, $record['attendance_id'], 
                        ['reason' => 'Already timed out'], 'FAILED', 'Employee already has time out record');
                    return [
                        'success' => false,
                        'message' => 'You have already timed out today at '. Helper::formatTime($updatedRecord['time_out'])
                    ];
                }

                $this->auditLog->log('TIME_OUT_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Database error'], 'FAILED', 'Failed to update time out');
                return [
                    'success' => false,
                    'message' => 'Failed to record time out. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("TimeOut CATCH: employee_id={$employee_id} attendance_id=" . ($record['attendance_id'] ?? 'NULL') . " record=" . json_encode($record ?? null) . " error=" . $e->getMessage() . " file=" . $e->getFile() . " line=" . $e->getLine());
            error_log("TimeOut Error: ". $e->getMessage());
            $this->auditLog->log('TIME_OUT_ERROR', $user_id, $employee_id, null, 
                ['error' => $e->getMessage()], 'FAILED', $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please contact HR.'
            ];
        }
    }

    /**
     * Process QR attendance (Smart - handles both Time In and Time Out)
     * Uses a token-based approach
     * 
     * @param int $employee_id - Employee ID
     * @param string $token - QR token
     * @return array - Response array
     */
    public function processQRAttendance($employee_id, $token)
    {
        Session::start();
        $user_id = Session::get('user_id');

        try {
            // Validate QR token
            $tokenData = $this->qrHelper->validateToken($token);

            if (!$tokenData) {
                $this->auditLog->log('QR_SCAN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Invalid or expired token'], 'FAILED', 'Token validation failed');
                return [
                    'success' => false,
                    'message' => 'QR code has expired or is invalid. Please ask HR to generate a new one.'
                ];
            }

            // Check if token is for today
            if ($tokenData['generated_for_date']!== Helper::getCurrentDate()) {
                $this->auditLog->log('QR_SCAN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Token not for today'], 'FAILED', 'Token date mismatch');
                return [
                    'success' => false,
                    'message' => 'QR code is not valid for today.'
                ];
            }

            $cooldown = $this->enforceQrCooldown($employee_id);
            if (!$cooldown['allowed']) {
                $employee = $this->employeeModel->getById($employee_id);
                return [
                    'success' => false,
                    'message' => $cooldown['message'],
                    'time_left_seconds' => $cooldown['time_left_seconds'],
                    'employee_info' => $employee ? [
                        'employee_id' => $employee['employee_id'],
                        'full_name' => $employee['full_name'],
                        'department' => $employee['department'] ?? 'N/A',
                        'position' => $employee['position'] ?? 'N/A',
                        'avatar' => $employee['profile_photo'] ?? 'default-user.png'
                    ] : null,
                    'action' => 'TIME_IN'
                ];
            }

            $employee = $this->employeeModel->getById($employee_id);
            $employee_info = $employee ? [
                'employee_id' => $employee['employee_id'],
                'full_name' => $employee['full_name'],
                'department' => $employee['department'] ?? 'N/A',
                'position' => $employee['position'] ?? 'N/A',
                'avatar' => $employee['profile_photo'] ?? 'default-user.png'
            ] : null;

            $this->autoFinalizeOpenAttendanceIfNeeded($employee_id, date('Y-m-d'));

            // Check if employee has timed in today
            $todayRecord = $this->attendanceModel->getTodayAttendance($employee_id);

            if ($todayRecord && empty($todayRecord['time_in']) && isset($todayRecord['status']) && $todayRecord['status'] === 'ABSENT') {
                return [
                    'success' => false,
                    'message' => 'Your attendance has already been marked absent for today. Please contact HR for assistance.',
                    'employee_info' => $employee_info,
                    'action' => 'ABSENT'
                ];
            }

            // Smart decision: if already timed in, do time out; otherwise do time in
            if ($todayRecord && !empty($todayRecord['time_in']) && empty($todayRecord['time_out'])) {
                // Employee already timed in, so do TIME OUT
                $result = $this->timeOut($employee_id, 'QR');
            } else if (!$todayRecord || empty($todayRecord['time_in'])) {
                // Employee hasn't timed in yet, so do TIME IN
                $result = $this->timeIn($employee_id, 'QR');
            } else {
                $result = [
                    'success' => false,
                    'message' => 'You have already timed out today at '. Helper::formatTime($todayRecord['time_out'])
                ];
            }

            if ($result['success']) {
                $this->qrHelper->markUsed($token, $employee_id);
                $this->auditLog->log('QR_SCAN_SUCCESS', $user_id, $employee_id, null, 
                    ['token_id' => $tokenData['token_id'], 'action' => isset($result['time_in'])? 'TIME_IN' : 'TIME_OUT'], 'SUCCESS');
            }

            return $result;
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log("QR Processing Error: ". $error_msg. " | Trace: ". $e->getTraceAsString());
            $this->auditLog->log('QR_SCAN_ERROR', $user_id, $employee_id, null, 
                ['error' => $error_msg], 'FAILED', $error_msg);
            return [
                'success' => false,
                'message' => 'Error processing QR code: '. $error_msg
            ];
        }
    }

    /**
     * Process Static QR attendance (Smart - handles both Time In and Time Out)
     * Uses a permanent employee ID from the QR code.
     * 
     * @param int $employee_id - Employee ID
     * @return array - Response array
     */
    public function processStaticQR($employee_id)
    {
        $debugLogPath = dirname(__DIR__, 2) . '/public/qr_debug.log';
        $entry = '[processStaticQR] ' . date('Y-m-d H:i:s') . ' ' . json_encode([
            'employee_id' => $employee_id,
            'get' => $_GET,
            'post' => $_POST,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($debugLogPath, $entry, FILE_APPEND);

        error_log('processStaticQR ENTRY: ' . json_encode([
            'employee_id' => $employee_id,
            'get' => $_GET,
            'post' => $_POST,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null
        ], JSON_UNESCAPED_SLASHES));

        Session::start();
        $user_id = Session::get('user_id');

        try {
            // Check if employee exists
            $employee = $this->employeeModel->getById($employee_id);
            if (!$employee) {
                $this->auditLog->log('QR_SCAN_FAILED', $user_id, $employee_id, null, 
                    ['reason' => 'Employee not found'], 'FAILED', 'Employee ID not found in database');
                return [
                    'success' => false,
                    'message' => 'Invalid Employee ID.'
                ];
            }

            // Prepare employee info for UI display
            $employee_info = [
                'employee_id' => $employee['employee_id'],
                'full_name' => $employee['full_name'],
                'department' => $employee['department'] ?? 'N/A',
                'position' => $employee['position'] ?? 'N/A',
                'avatar' => $employee['profile_photo'] ?? 'default-user.png'
            ];

            $cooldown = $this->enforceQrCooldown($employee_id);
            if (!$cooldown['allowed']) {
                return [
                    'success' => false,
                    'message' => $cooldown['message'],
                    'time_left_seconds' => $cooldown['time_left_seconds'],
                    'employee_info' => $employee_info,
                    'action' => 'TIME_IN'
                ];
            }

            $this->autoFinalizeOpenAttendanceIfNeeded($employee_id, date('Y-m-d'));

            // Check if employee has timed in today
            error_log("StaticQR: processStaticQR entered for employee_id={$employee_id}");
            $todayRecord = $this->attendanceModel->getTodayAttendance($employee_id);
            error_log("StaticQR: todayRecord=" . json_encode($todayRecord));
            error_log("StaticQR: branchCheck employee_id={$employee_id} todayRecordExists=" . ($todayRecord ? 'true' : 'false') . " time_in=" . ($todayRecord['time_in'] ?? 'NULL') . " time_out=" . ($todayRecord['time_out'] ?? 'NULL'));
            $branchEntry = '[processStaticQR] ' . date('Y-m-d H:i:s') . ' ' . json_encode([
                'employee_id' => $employee_id,
                'todayRecord' => $todayRecord,
                'selected_branch' => ($todayRecord && !empty($todayRecord['time_in']) && empty($todayRecord['time_out'])) ? 'TIME_OUT' : ((!$todayRecord || empty($todayRecord['time_in'])) ? 'TIME_IN' : 'ALREADY_TIMED_OUT')
            ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($debugLogPath, $branchEntry, FILE_APPEND);

            error_log('processStaticQR BRANCH: ' . json_encode([
                'employee_id' => $employee_id,
                'todayRecord' => $todayRecord,
                'selected_branch' => ($todayRecord && !empty($todayRecord['time_in']) && empty($todayRecord['time_out'])) ? 'TIME_OUT' : ((!$todayRecord || empty($todayRecord['time_in'])) ? 'TIME_IN' : 'ALREADY_TIMED_OUT')
            ], JSON_UNESCAPED_SLASHES));

            if ($todayRecord && empty($todayRecord['time_in']) && isset($todayRecord['status']) && $todayRecord['status'] === 'ABSENT') {
                return [
                    'success' => false,
                    'message' => 'Your attendance has already been marked absent for today. Please contact HR for assistance.',
                    'employee_info' => $employee_info,
                    'action' => 'ABSENT'
                ];
            }

            // Smart decision: if already timed in, do time out after at least 3 hours; otherwise do time in
            if ($todayRecord && !empty($todayRecord['time_in']) && empty($todayRecord['time_out'])) {
                error_log("StaticQR: entering TIME_OUT branch for employee_id={$employee_id} todayRecord.attendance_id=" . ($todayRecord['attendance_id'] ?? 'NULL') . " todayRecord=" . json_encode($todayRecord));
                $result = $this->timeOut($employee_id, 'QR');
                error_log("StaticQR: after timeOut() returned for employee_id={$employee_id} attendance_id=" . ($todayRecord['attendance_id'] ?? 'NULL') . " result=" . json_encode($result));
                if ($result['success']) {
                    $result['employee_info'] = $employee_info;
                    $result['action'] = 'TIME_OUT';
                    $result['timed_at'] = $result['time_out'] ?? $todayRecord['time_out'] ?? null;
                } else {
                    $result['employee_info'] = $employee_info;
                    $result['action'] = 'TIME_OUT';
                }
            } else if (!$todayRecord || empty($todayRecord['time_in'])) {
                // Employee hasn't timed in yet, so do TIME IN
                $result = $this->timeIn($employee_id, 'QR');
                if ($result['success']) {
                    $result['employee_info'] = $employee_info;
                    $result['action'] = 'TIME_IN';
                    $result['timed_at'] = $result['time_in'] ?? null;
                }
            } else {
                $result = [
                    'success' => false,
                    'message' => 'You have already timed out today at '. Helper::formatTime($todayRecord['time_out'])
                ];
            }

            if (!isset($result['employee_info'])) {
                $result['employee_info'] = $employee_info;
            }

            if (isset($result['status'])) {
                $result['employee_info']['status'] = $result['status'];
            }

            if ($result['success']) {
                $this->auditLog->log('QR_SCAN_SUCCESS', $user_id, $employee_id, null, 
                    ['action' => isset($result['time_in'])? 'TIME_IN' : 'TIME_OUT'], 'SUCCESS');
            }

            return $result;
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log("StaticQR CATCH: employee_id={$employee_id} error=" . $error_msg . " file=" . $e->getFile() . " line=" . $e->getLine());
            error_log("Static QR Processing Error: ". $error_msg. " | Trace: ". $e->getTraceAsString());
            $this->auditLog->log('QR_SCAN_ERROR', $user_id, $employee_id, null, 
                ['error' => $error_msg], 'FAILED', $error_msg);
            return [
                'success' => false,
                'error' => $error_msg,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    }

    /**
     * Get today's attendance record for employee
     * 
     * @param int $employee_id - Employee ID
     * @return array|null - Attendance record or null
     */
    public function getTodayRecord($employee_id)
    {
        return $this->attendanceModel->getTodayAttendance($employee_id);
    }

    /**
     * Get attendance status for display
     * 
     * @param int $employee_id - Employee ID
     * @return array - Status information
     */
    public function getStatus($employee_id)
    {
        $record = $this->getTodayRecord($employee_id);

        if (!$record) {
            return [
                'status' => 'NOT_STARTED',
                'time_in' => null,
                'time_out' => null,
                'duration' => null
            ];
        }

        $status = 'TIME_IN_ONLY';
        if (!empty($record['time_out'])) {
            $status = 'COMPLETED';
        } elseif (empty($record['time_in'])) {
            $status = 'NOT_STARTED';
        }

        return [
            'status' => $status,
            'time_in' => $record['time_in'],
            'time_out' => $record['time_out'],
            'duration' => $status === 'COMPLETED'? Helper::calculateDuration($record['time_in'], $record['time_out']) : null,
            'method' => $record['recorded_by']?? 'MANUAL'
        ];
    }
}