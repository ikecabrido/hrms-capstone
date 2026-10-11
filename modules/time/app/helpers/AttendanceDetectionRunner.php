<?php
/**
 * Shared attendance detection runner.
 * Extracted from app/cron/detect_attendance_status.php so the same logic can be
 * triggered either by an external scheduler (via the cron script) OR automatically
 * from within the web app (via absence_late_management.php), removing the
 * dependency on any OS-level scheduled task actually being configured correctly.
 */

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../services/AttendanceValidationService.php';
require_once __DIR__ . '/../helpers/Helper.php';
require_once __DIR__ . '/../helpers/OpenAttendanceFinalizer.php';

class AttendanceDetectionRunner
{
    /**
     * Runs the canonical absence/late detection sweep for a given date.
     * Safe to call repeatedly/frequently: markAbsent(), markLate(), and
     * updateStatus() all check existing records before writing, so re-running
     * this for the same date does not create duplicates.
     *
     * @param string|null $targetDate 'Y-m-d', defaults to today
     * @return array results summary (same shape the cron script used to echo)
     */
    public static function run($targetDate = null)
    {
        $targetDate = $targetDate ?: date('Y-m-d');
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
        file_put_contents($debugLogPath, "[AttendanceDetectionRunner] run_date={$targetDate} run_time={$now->format('Y-m-d H:i:s')}\n", FILE_APPEND);

        try {
            $db = TimeDatabase::getInstance();
            $conn = $db->getConnection();
            $attendanceModel = new Attendance();
            $validationService = new \App\Services\AttendanceValidationService();

            $employeeQuery = "SELECT 
                        e.employee_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        COALESCE(d.department_name, '') AS department
                      FROM em_employees e
                      LEFT JOIN em_departments d ON e.department_id = d.department_id
                      WHERE e.employment_status = 'Active'
                      ORDER BY full_name";
            $employeeStmt = $conn->prepare($employeeQuery);
            $employeeStmt->execute();
            $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

            file_put_contents($debugLogPath, "[AttendanceDetectionRunner] employees_retrieved=" . count($employees) . "\n", FILE_APPEND);

            foreach ($employees as $employee) {
                $employeeId = $employee['employee_id'];
                $attendance = $attendanceModel->getTodayAttendance($employeeId, $targetDate);

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
                    $created = $attendanceModel->markAbsent($employeeId, $targetDate, 'Absence detected by canonical cron');
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

        return $results;
    }
}
