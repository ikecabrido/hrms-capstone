<?php
/**
 * Shared helper for finalizing open attendance records at shift end.
 *
 * This is used by both the QR/controller flow and the automatic attendance
 * detection cron so forgotten time-outs are closed even when no QR scan occurs.
 */

require_once __DIR__ . '/Helper.php';

class OpenAttendanceFinalizer
{
    /**
     * Finalize an open attendance record at shift end when the employee forgot to time out.
     *
     * @param Attendance $attendanceModel
     * @param mixed $validationService
     * @param int $employee_id
     * @param string|null $attendanceDate
     * @param DateTime|null $now
     * @return bool
     */
    public static function finalizeOpenAttendanceIfNeeded($attendanceModel, $validationService, $employee_id, $attendanceDate = null, $now = null)
    {
        $attendanceDate = $attendanceDate ?? date('Y-m-d');
        $now = $now instanceof DateTime ? $now : new DateTime('now', new DateTimeZone('Asia/Manila'));

        $record = $attendanceModel->getTodayAttendance($employee_id, $attendanceDate);
        if (!$record || empty($record['time_in']) || !empty($record['time_out'])) {
            return false;
        }

        $evaluation = $validationService->resolveExpectedAttendanceStatus($employee_id, $attendanceDate, $now);
        if (empty($evaluation['shift']['end_time'])) {
            return false;
        }

        $shiftEnd = new DateTime($attendanceDate . ' ' . $evaluation['shift']['end_time'], new DateTimeZone('Asia/Manila'));
        if ($now < $shiftEnd) {
            return false;
        }

        $timeoutAt = $shiftEnd->format('Y-m-d H:i:s');
        if ($attendanceModel->timeOutAt($record['attendance_id'], $timeoutAt)) {
            $updatedRecord = $attendanceModel->getTodayAttendance($employee_id, $attendanceDate);
            $hoursData = Helper::calculateHours($updatedRecord['time_in'], $updatedRecord['time_out'], 8);
            $attendanceModel->updateHours($record['attendance_id'], $hoursData);
            return true;
        }

        return false;
    }
}
