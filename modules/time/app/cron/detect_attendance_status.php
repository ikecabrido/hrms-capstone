<?php
/**
 * Canonical Attendance Status Detection Cron Script
 *
 * This script uses the AttendanceValidationService as the single decision engine.
 * It detects attendance for all active employees and persists:
 *   - ABSENT when shift end has passed with no time-in
 *   - LATE when a time-in occurred after shift start
 *
 * It does not create duplicate attendance tables.
 *
 * Usage:
 *   php detect_attendance_status.php
 *   php detect_attendance_status.php 2026-08-03
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../services/AttendanceValidationService.php';
require_once __DIR__ . '/../helpers/Helper.php';
require_once __DIR__ . '/../helpers/OpenAttendanceFinalizer.php';

$scriptDate = null;
if (isset($argv[1]) && trim($argv[1]) !== '') {
    $candidate = DateTime::createFromFormat('Y-m-d', trim($argv[1]));
    if ($candidate && $candidate->format('Y-m-d') === trim($argv[1])) {
        $scriptDate = $candidate->format('Y-m-d');
    }
}

$targetDate = $scriptDate ?: date('Y-m-d');
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));

$results = [
    'success' => false,
    'date' => $targetDate,
    'timestamp' => $now->format('Y-m-d H:i:s'),
    'summary' => [],
    'waiting_for_assignment' => [],
    'waiting_for_time_in' => [],
    'late' => [],
    'absent' => [],
    'present' => [],
    'holiday_or_weekend' => []
];

$debugLogPath = __DIR__ . '/../../logs/absence_detection_debug.log';
file_put_contents($debugLogPath, "[detect_attendance_status] run_date={$targetDate} run_time={$now->format('Y-m-d H:i:s')}\n", FILE_APPEND);

try {
    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();
    $attendanceModel = new Attendance();
    $validationService = new \App\Services\AttendanceValidationService();

    $employeeQuery = "SELECT employee_id, full_name, department FROM employees WHERE employment_status = 'Active' ORDER BY full_name";
    $employeeStmt = $conn->prepare($employeeQuery);
    $employeeStmt->execute();
    $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

    file_put_contents($debugLogPath, "[detect_attendance_status] employees_retrieved=" . count($employees) . "\n", FILE_APPEND);
    foreach ($employees as $employee) {
        $employeeId = $employee['employee_id'];
        $attendance = $attendanceModel->getTodayAttendance($employeeId, $targetDate);
        file_put_contents($debugLogPath, "[detect_attendance_status] employee_id={$employeeId} name=" . $employee['full_name'] . " attendance_exists=" . (!empty($attendance) ? 'true' : 'false') . " time_in=" . ($attendance['time_in'] ?? 'NULL') . " time_out=" . ($attendance['time_out'] ?? 'NULL') . " status=" . ($attendance['status'] ?? 'NULL') . "\n", FILE_APPEND);

        if (!empty($attendance['time_in'])) {
            $status = $validationService->determineStatus($employeeId, $attendance['time_in'], $targetDate);

            if ($status === 'LATE') {
                $minutesLate = $validationService->calculateMinutesLate($employeeId, $attendance['time_in'], $targetDate);
                $attendanceModel->updateStatus($attendance['attendance_id'], 'LATE', $minutesLate);
                $results['late'][] = [
                    'employee_id' => $employeeId,
                    'name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'time_in' => $attendance['time_in'],
                    'minutes_late' => $minutesLate,
                    'date' => $targetDate
                ];
            }

            if ($status === 'HOLIDAY_WORKED' && $attendance['status'] !== 'HOLIDAY_WORKED') {
                $attendanceModel->updateStatus($attendance['attendance_id'], 'HOLIDAY_WORKED', 0);
            }

            if (empty($attendance['time_out'])) {
                OpenAttendanceFinalizer::finalizeOpenAttendanceIfNeeded(
                    $attendanceModel,
                    $validationService,
                    $employeeId,
                    $targetDate,
                    $now
                );
            }

            if ($status === 'LATE') {
                continue;
            }

            $results['present'][] = [
                'employee_id' => $employeeId,
                'name' => $employee['full_name'],
                'department' => $employee['department'],
                'status' => $status,
                'date' => $targetDate
            ];
            continue;
        }

        $evaluation = $validationService->resolveExpectedAttendanceStatus($employeeId, $targetDate, $now);
        $expectedStatus = $evaluation['status'];
        file_put_contents($debugLogPath, "[detect_attendance_status] employee_id={$employeeId} shift_status=" . ($evaluation['shift']['start_time'] ?? 'NONE') . "/" . ($evaluation['shift']['end_time'] ?? 'NONE') . " resolved_status=" . ($expectedStatus ?? 'NULL') . " reason=" . ($evaluation['reason'] ?? 'NULL') . "\n", FILE_APPEND);

        if ($expectedStatus === 'LATE') {
            $minutesLate = $validationService->calculateMinutesLate($employeeId, $now->format('Y-m-d H:i:s'), $targetDate);
            $updated = $attendanceModel->markLate($employeeId, $targetDate, $minutesLate, 'Late detected by canonical cron');
            if ($updated) {
                $results['late'][] = [
                    'employee_id' => $employeeId,
                    'name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'date' => $targetDate,
                    'minutes_late' => $minutesLate,
                    'reason' => $evaluation['reason'] ?? 'After shift start'
                ];
            }
            continue;
        }

        if ($expectedStatus === 'ABSENT') {
            file_put_contents($debugLogPath, "[detect_attendance_status] employee_id={$employeeId} attempting_markAbsent\n", FILE_APPEND);
            $created = $attendanceModel->markAbsent($employeeId, $targetDate, 'Absence detected by canonical cron');
            file_put_contents($debugLogPath, "[detect_attendance_status] employee_id={$employeeId} markAbsent_returned=" . ($created ? 'true' : 'false') . "\n", FILE_APPEND);
            if ($created) {
                $results['absent'][] = [
                    'employee_id' => $employeeId,
                    'name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'date' => $targetDate,
                    'reason' => $evaluation['reason'] ?? 'After shift end'
                ];
            }
            continue;
        }

        if ($expectedStatus === 'WAITING_FOR_ASSIGNMENT') {
            $results['waiting_for_assignment'][] = [
                'employee_id' => $employeeId,
                'name' => $employee['full_name'],
                'department' => $employee['department'],
                'date' => $targetDate,
                'reason' => $evaluation['reason'] ?? 'No shift assigned'
            ];
            continue;
        }

        if ($expectedStatus === 'WAITING_FOR_TIME_IN') {
            $results['waiting_for_time_in'][] = [
                'employee_id' => $employeeId,
                'name' => $employee['full_name'],
                'department' => $employee['department'],
                'date' => $targetDate,
                'reason' => $evaluation['reason'] ?? 'Before shift start'
            ];
            continue;
        }

        $results['holiday_or_weekend'][] = [
            'employee_id' => $employeeId,
            'name' => $employee['full_name'],
            'department' => $employee['department'],
            'date' => $targetDate,
            'reason' => $evaluation['reason'] ?? 'Holiday or weekend'
        ];
    }

    $results['success'] = true;
    $results['summary'] = [
        'employees_checked' => count($employees),
        'waiting_for_assignment' => count($results['waiting_for_assignment']),
        'waiting_for_time_in' => count($results['waiting_for_time_in']),
        'late' => count($results['late']),
        'absent' => count($results['absent']),
        'present' => count($results['present']),
        'holiday_or_weekend' => count($results['holiday_or_weekend'])
    ];
} catch (Exception $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
    $results['message'] = 'Attendance status detection failed.';
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit($results['success'] ? 0 : 1);
