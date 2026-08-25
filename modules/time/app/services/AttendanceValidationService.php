<?php
/**
 * Attendance Validation Service
 * Handles shift validation, holiday/weekend detection, and status determination
 * before employee time-in is recorded
 */

namespace App\Services;

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../helpers/HolidayHelper.php';

class AttendanceValidationService
{
    private $conn;
    private $employees_table = "em_employees";
    private $attendance_table = "ta_attendance";
    private $employee_shifts_table = "ta_employee_shifts";
    private $shifts_table = "ta_shifts";
    private $holidays_table = "ta_holidays";
    private $shift_exclusions_table = "ta_shift_exclusions";
    private $flexible_schedules_table = "ta_flexible_schedules";

    // Configuration: Late detection threshold (minutes after shift start)
    // Business rule: any time_in after shift start is considered LATE, so default to 0
    private $late_threshold_minutes = 30;

    public function __construct()
    {
        $db = \TimeDatabase::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Validate employee can time-in
     * Returns array with validation result and status
     * NOTE: Holidays now allow time-in (for double pay employees)
     */
    public function validateTimeIn($employee_id, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday

        $holiday = $this->getHolidayInfo($date);
        if ($holiday) {
            $isWorkingHoliday = (int)$holiday['is_working_day'] === 1;

            if ($isWorkingHoliday) {
                return [
                    'valid' => true,
                    'can_timein' => true,
                    'status' => 'HOLIDAY_WORKED',
                    'message' => 'Today is a working holiday (' . $holiday['name'] . '). You can time-in for regular/holiday work compliance.',
                    'reason' => 'Working holiday - attendance allowed',
                    'is_holiday' => true,
                    'holiday_name' => $holiday['name'],
                    'holiday_scope' => $holiday['holiday_scope'] ?? 'national',
                    'is_working_day' => true
                ];
            }

            return [
                'valid' => true,
                'can_timein' => false,
                'status' => 'NON_WORKING_HOLIDAY',
                'message' => 'Today is a non-working holiday (' . $holiday['name'] . '). No attendance is required.',
                'reason' => 'Non-working holiday',
                'is_holiday' => true,
                'holiday_name' => $holiday['name'],
                'holiday_scope' => $holiday['holiday_scope'] ?? 'national',
                'is_working_day' => false
            ];
        }

        // Check if today is weekend (Sunday = 0)
        if ($dayOfWeek == 0) {
            return [
                'valid' => true,
                'can_timein' => false,
                'status' => 'WEEKEND',
                'message' => 'Today is Sunday (weekend). No time-in required.',
                'reason' => 'Sunday is not a working day'
            ];
        }

        // Check if employee has shift assigned for this date
        $shift = $this->getEmployeeShiftForDate($employee_id, $date);

        if (!$shift) {
            return [
                'valid' => false,
                'can_timein' => false,
                'status' => 'WAITING_FOR_SHIFT',
                'message' => "You don't have an assigned shift yet. Please contact HR Admin for Shift Assignment.",
                'reason' => 'No shift assigned'
            ];
        }

        // Check if there's a shift exclusion for this employee on this date
        if ($this->hasShiftExclusion($employee_id, $date)) {
            return [
                'valid' => true,
                'can_timein' => false,
                'status' => 'WAITING_FOR_SHIFT',
                'message' => 'You have no scheduled shift for today.',
                'reason' => 'Shift exclusion applied'
            ];
        }

        // Employee can time-in
        return [
            'valid' => true,
            'can_timein' => true,
            'status' => 'PRESENT', // Will be updated to LATE if needed
            'message' => 'Ready to time-in',
            'shift' => $shift
        ];
    }

    /**
     * Determine attendance status based on time-in and shift
     * Returns status: PRESENT, LATE, HOLIDAY_WORKED, or based on validation
     */
    public function determineStatus($employee_id, $time_in, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        $time_in_obj = new \DateTime($time_in);

        $holiday = $this->getHolidayInfo($date);
        if ($holiday && (int)$holiday['is_working_day'] === 1) {
            return 'HOLIDAY_WORKED';
        }

        // Get employee's shift
        $shift = $this->getEmployeeShiftForDate($employee_id, $date);

        if (!$shift) {
            return 'PRESENT'; // No shift, assume present if they timed in
        }

        // Parse shift start time
        $shift_start = new \DateTime($date . ' ' . $shift['start_time']);
        $late_deadline = (clone $shift_start)->modify('+' . $this->late_threshold_minutes . ' minutes');

        // Only mark as late after the 30-minute grace period.
        if ($time_in_obj <= $shift_start) {
            return 'PRESENT'; // On time or early
        }

        if ($time_in_obj <= $late_deadline) {
            return 'PRESENT'; // still within the 30-minute grace period
        }

        return 'LATE';
    }

    /**
     * Calculate minutes late
     */
    public function calculateMinutesLate($employee_id, $time_in, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        $time_in_obj = new \DateTime($time_in);

        // Get employee's shift
        $shift = $this->getEmployeeShiftForDate($employee_id, $date);

        if (!$shift) {
            return 0;
        }

        // Parse shift start time
        $shift_start = new \DateTime($date . ' ' . $shift['start_time']);
        $late_deadline = (clone $shift_start)->modify('+' . $this->late_threshold_minutes . ' minutes');

        // Before or at shift start, not late.
        if ($time_in_obj <= $shift_start) {
            return 0;
        }

        // Within the 30-minute grace period, not late.
        if ($time_in_obj <= $late_deadline) {
            return 0;
        }

        // Calculate difference in minutes after the grace period.
        $interval = $shift_start->diff($time_in_obj);
        $minutes_late = ($interval->h * 60) + $interval->i;

        return max(0, $minutes_late - $this->late_threshold_minutes);
    }

    /**
     * Resolve the expected attendance state for an employee based on current time
     * and the assigned shift.
     */
    public function resolveExpectedAttendanceStatus($employee_id, $date = null, $now = null)
    {
        $date = $date ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($date));
        $holiday = $this->getHolidayInfo($date);

        if ($holiday) {
            $isWorkingHoliday = (int)$holiday['is_working_day'] === 1;
            if (!$isWorkingHoliday) {
                return ['status' => null, 'reason' => 'holiday_or_weekend', 'is_holiday' => true, 'holiday_name' => $holiday['name']];
            }
        }

        if ($dayOfWeek == 0) {
            return ['status' => null, 'reason' => 'weekend'];
        }

        $shift = $this->getEmployeeShiftForDate($employee_id, $date);
        if (!$shift) {
            return ['status' => 'WAITING_FOR_ASSIGNMENT', 'shift' => null, 'reason' => 'No shift assigned'];
        }

        if (empty($shift['start_time']) || empty($shift['end_time'])) {
            return ['status' => 'WAITING_FOR_TIME_IN', 'shift' => $shift, 'reason' => 'Missing shift times'];
        }

        $currentTime = $now instanceof \DateTime ? clone $now : new \DateTime($now ?? 'now');
        $currentTime->setTimezone(new \DateTimeZone('Asia/Manila'));

        $shiftStart = new \DateTime($date . ' ' . $shift['start_time'], new \DateTimeZone('Asia/Manila'));
        $shiftEnd = new \DateTime($date . ' ' . $shift['end_time'], new \DateTimeZone('Asia/Manila'));
        $lateDeadline = (clone $shiftStart)->modify('+' . $this->late_threshold_minutes . ' minutes');

        if ($currentTime < $shiftStart) {
            return ['status' => 'WAITING_FOR_TIME_IN', 'shift' => $shift, 'reason' => 'Before shift start'];
        }

        if ($currentTime <= $lateDeadline) {
            return ['status' => 'WAITING_FOR_TIME_IN', 'shift' => $shift, 'reason' => 'Within grace period'];
        }

        if ($currentTime < $shiftEnd) {
            return ['status' => 'LATE', 'shift' => $shift, 'reason' => 'Late beyond 30-minute threshold'];
        }

        return ['status' => 'ABSENT', 'shift' => $shift, 'reason' => 'After shift end'];
    }

    /**
     * Check for absence detection
     * Called at end of shift or next day
     * Returns true if employee hasn't timed in by end of shift
     */
    public function shouldMarkAsAbsent($employee_id, $date = null, $now = null)
    {
        $date = $date ?? date('Y-m-d');
        $evaluation = $this->resolveExpectedAttendanceStatus($employee_id, $date, $now);

        if ($evaluation['status'] !== 'ABSENT') {
            return false;
        }

        $query = "SELECT attendance_id, time_in FROM {$this->attendance_table}
                  WHERE employee_id = :employee_id
                  AND attendance_date = :date
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        $attendance = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$attendance) {
            return true;
        }

        return empty($attendance['time_in']);
    }

    /**
     * Get employee shift for a specific date
     */
        private function getEmployeeShiftForDate($employee_id, $date = null) {
        $date = $date ?? date('Y-m-d');

        // 1. First check for a regular assigned shift
        $query = "SELECT 
                        es.*,
                        s.shift_name,
                        s.start_time,
                        s.end_time,
                        s.break_duration,
                        s.shift_id
                FROM {$this->employee_shifts_table} es
                INNER JOIN {$this->shifts_table} s 
                    ON es.shift_id = s.shift_id
                WHERE es.employee_id = :employee_id
                    AND es.is_active = 1
                    AND es.effective_from <= :date
                    AND (es.effective_to IS NULL OR es.effective_to >= :date)
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        $shift = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Regular shift found
        if ($shift) {
            return $shift;
        }

        // 2. No regular shift, so check flexible schedule
        return $this->getFlexibleScheduleShift($employee_id, $date);
    }

    /**
     * Fallback to a flexible schedule when no regular shift assignment exists.
     */
    private function getFlexibleScheduleShift($employee_id, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($date));

        $query = "SELECT id, start_time, end_time, schedule_date, day_of_week
                  FROM {$this->flexible_schedules_table}
                  WHERE employee_id = :employee_id
                  AND (
                      schedule_date = :date
                      OR (
                          day_of_week IS NOT NULL
                          AND day_of_week = :day_of_week
                          AND (repeat_until IS NULL OR repeat_until >= :date)
                          AND (contract_end_date IS NULL OR contract_end_date >= :date)
                      )
                  )
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':day_of_week', $dayOfWeek, \PDO::PARAM_INT);
        $stmt->execute();

        $flexibleSchedule = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$flexibleSchedule) {
            return null;
        }

        return [
            'shift_name' => 'Flexible Schedule',
            'start_time' => $flexibleSchedule['start_time'],
            'end_time' => $flexibleSchedule['end_time'],
            'break_duration' => null,
            'shift_id' => null,
            'is_flexible' => true,
            'flexible_schedule_id' => $flexibleSchedule['id'],
            'flexible_schedule_date' => $flexibleSchedule['schedule_date'],
            'flexible_day_of_week' => $flexibleSchedule['day_of_week']
        ];
    }

    /**
     * Check if date is a holiday
     */
        public function isHoliday($date)
    {
        return $this->getHolidayInfo($date) !== null;
    }

    /**
     * Get holiday information for a specific date
     */
    private function getHolidayInfo($date)
    {
        $query = "SELECT id, name, holiday_date, description, is_recurring, category, holiday_scope, is_working_day, source
                  FROM {$this->holidays_table}
                  WHERE holiday_date = :date
                  AND is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Check if there's a shift exclusion for employee on this date
     */
    private function hasShiftExclusion($employee_id, $date)
    {
        $query = "SELECT tse.exclusion_id
                  FROM {$this->shift_exclusions_table} tse
                  JOIN {$this->employee_shifts_table} es ON tse.employee_shift_id = es.employee_shift_id
                  WHERE es.employee_id = :employee_id
                  AND tse.exclusion_date = :date
                  AND es.is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Detect late arrivals for a specific date
     * Returns array of employees who are late
     */
    public function detectLateArrivals($date = null)
    {
        $date = $date ?? date('Y-m-d');

                        $query = "SELECT 
                        a.attendance_id,
                        a.employee_id,
                        a.time_in,
                        CONCAT(
                                COALESCE(e.first_name, ''),
                                ' ',
                                COALESCE(e.last_name, '')
                        ) AS full_name,
                        COALESCE(d.department_name, '') AS department
                    FROM ta_attendance a
                    JOIN {$this->employees_table} e 
                        ON a.employee_id = e.employee_id
                    LEFT JOIN em_departments d ON e.department_id = d.department_id
                    WHERE a.attendance_date = :date
                    AND a.time_in IS NOT NULL
                    AND (a.status IS NULL OR a.status = 'PRESENT')
                    AND e.employment_status = 'Active'
                    ORDER BY full_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $late_arrivals = [];

        foreach ($records as $record) {
            $shift = $this->getEmployeeShiftForDate($record['employee_id'], $date);
            if (!$shift || empty($shift['start_time'])) {
                continue;
            }

            $minutes_late = $this->calculateMinutesLate(
                $record['employee_id'],
                $record['time_in'],
                $date
            );

            // If more than threshold minutes late, include in results
            if ($minutes_late > 0) {
                $late_arrivals[] = [
                    'attendance_id' => $record['attendance_id'],
                    'employee_id' => $record['employee_id'],
                    'name' => $record['full_name'],
                    'department' => $record['department'],
                    'time_in' => $record['time_in'],
                    'shift_start' => $shift['start_time'],
                    'minutes_late' => $minutes_late
                ];
            }
        }

        return $late_arrivals;
    }

    /**
     * Detect absences for a specific date (including holiday absences)
     * Returns array of employees who didn't time-in by end of shift
     */
    public function detectAbsences($date = null)
    {
        $date = $date ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday

        // Don't check on weekends - but DO check on holidays (for holiday absences)
        if ($dayOfWeek == 0) {
            return [];
        }

                $employeeQuery = "SELECT 
                                        e.employee_id,
                                        CONCAT(
                                                COALESCE(e.first_name, ''),
                                                ' ',
                                                COALESCE(e.last_name, '')
                                        ) AS full_name,
                                        COALESCE(d.department_name, '') AS department
                                    FROM {$this->employees_table} e
                                    LEFT JOIN em_departments d ON e.department_id = d.department_id
                                    WHERE e.employment_status = 'Active'
                                    ORDER BY full_name";

        $stmt = $this->conn->prepare($employeeQuery);
        $stmt->execute();
        $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $absences = [];
        $now = new \DateTime('now', new \DateTimeZone('Asia/Manila'));

        foreach ($employees as $employee) {
            $evaluation = $this->resolveExpectedAttendanceStatus($employee['employee_id'], $date, $now);
            if ($evaluation['status'] !== 'ABSENT') {
                continue;
            }

            $attendanceQuery = "SELECT attendance_id, time_in
                                FROM {$this->attendance_table}
                                WHERE employee_id = :employee_id
                                AND attendance_date = :date
                                LIMIT 1";
            $attendanceStmt = $this->conn->prepare($attendanceQuery);
            $attendanceStmt->bindParam(':employee_id', $employee['employee_id'], \PDO::PARAM_INT);
            $attendanceStmt->bindParam(':date', $date);
            $attendanceStmt->execute();

            $attendance = $attendanceStmt->fetch(\PDO::FETCH_ASSOC);
            if ($attendance && !empty($attendance['time_in'])) {
                continue;
            }

            $shift = $this->getEmployeeShiftForDate($employee['employee_id'], $date);
            $absences[] = [
                'employee_id' => $employee['employee_id'],
                'full_name' => $employee['full_name'],
                'department' => $employee['department'],
                'shift_id' => $shift['shift_id'] ?? null,
                'start_time' => $shift['start_time'] ?? null,
                'end_time' => $shift['end_time'] ?? null,
                'expected_status' => 'ABSENT'
            ];
        }

        return $absences;
    }

    /**
     * Set late threshold minutes (default 15)
     */
    public function setLateThreshold($minutes)
    {
        $this->late_threshold_minutes = (int)$minutes;
    }

    /**
     * Get late threshold minutes
     */
    public function getLateThreshold()
    {
        return $this->late_threshold_minutes;
    }
}
?>
