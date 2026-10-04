<?php

/**
 * Attendance Holiday Integration
 * Handles holiday logic in attendance system
 */

namespace App\Integrations;

use App\Models\Holiday;
use App\Helpers\HolidayHelper;

class AttendanceHolidayIntegration
{
    private $holiday;
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
        $this->holiday = new Holiday($database);
        HolidayHelper::init($database);
    }

    /**
     * Check if employee should be marked on a date
     * Returns status: 'HOLIDAY', 'ABSENT', 'PRESENT' or 'LATE'
     */
    public function getAttendanceStatus($employeeId, $date, $hasCheckedIn = false, $checkInTime = null)
    {
        // First check if it's a holiday
        if (HolidayHelper::isHoliday($date)) {
            return [
                'status' => 'HOLIDAY',
                'reason' => 'No time-in required',
                'holiday' => HolidayHelper::getHolidayByDate($date)
            ];
        }

        // Then check attendance
        if ($hasCheckedIn) {
            return [
                'status' => 'PRESENT',
                'reason' => 'Employee checked in',
                'checkedIn' => true
            ];
        }

        return [
            'status' => 'ABSENT',
            'reason' => 'Employee did not check in',
            'checkedIn' => false
        ];
    }

    /**
     * Process attendance record with holiday consideration
     */
    public function processAttendance($employeeId, $date, $checkInTime = null, $checkOutTime = null)
    {
        try {
            // Check if holiday
            if (HolidayHelper::isHoliday($date)) {
                return $this->recordHolidayAttendance($employeeId, $date);
            }

            // Regular attendance processing
            return [
                'success' => true,
                'isHoliday' => false,
                'message' => 'Attendance processed normally'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Record attendance as holiday
     */
    private function recordHolidayAttendance($employeeId, $date)
    {
        try {
            $holiday = HolidayHelper::getHolidayByDate($date);

            $query = "INSERT INTO ta_attendance 
                     (employee_id, attendance_date, attendance_status, notes, created_at)
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE
                     attendance_status = 'HOLIDAY',
                     notes = ?,
                     updated_at = NOW()";

            $stmt = $this->db->prepare($query);
            
            $note = "Holiday - " . ($holiday['name'] ?? 'Public Holiday');
            
            $stmt->execute([
                $employeeId,
                $date,
                'HOLIDAY',
                $note,
                $note
            ]);

            return [
                'success' => true,
                'isHoliday' => true,
                'status' => 'HOLIDAY',
                'message' => 'Recorded as holiday',
                'holiday' => $holiday
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if employee needs to mark attendance for a date
     */
    public function needsAttendanceMarking($employeeId, $date)
    {
        // Don't need to mark on holidays
        if (HolidayHelper::isHoliday($date)) {
            return false;
        }

        // Check if it's a working day
        $dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
        if (in_array($dayOfWeek, [0, 6])) {
            // Need to check if there's a shift assigned
            return $this->hasShiftAssigned($employeeId, $date);
        }

        return true;
    }

    /**
     * Check if employee has shift assigned for a date
     */
    private function hasShiftAssigned($employeeId, $date)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM ta_employee_schedules
                     WHERE employee_id = ? 
                     AND DATE(schedule_date) = ?
                     AND is_active = 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $date]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Exclude holidays from absence calculation
     */
    public function calculateAbsenceDays($employeeId, $startDate, $endDate)
    {
        try {
            // Get all dates in range
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->modify('+1 day'); // Include end date

            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);

            $absenceDays = 0;
            $holidays = [];

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Skip if holiday
                if (HolidayHelper::isHoliday($dateStr)) {
                    $holiday = HolidayHelper::getHolidayByDate($dateStr);
                    $holidays[] = $holiday;
                    continue;
                }

                // Skip if weekend (unless shift assigned)
                if (!$this->needsAttendanceMarking($employeeId, $dateStr)) {
                    continue;
                }

                // Check actual attendance
                $query = "SELECT attendance_status FROM ta_attendance
                         WHERE employee_id = ? 
                         AND DATE(attendance_date) = ?
                         LIMIT 1";

                $stmt = $this->db->prepare($query);
                $stmt->execute([$employeeId, $dateStr]);

                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$result || $result['attendance_status'] === 'ABSENT') {
                    $absenceDays++;
                }
            }

            return [
                'absence_days' => $absenceDays,
                'holidays_excluded' => count($holidays),
                'holidays' => $holidays
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get attendance summary excluding holidays
     */
    public function getAttendanceSummary($employeeId, $month = null, $year = null)
    {
        try {
            $month = $month ?? date('m');
            $year = $year ?? date('Y');

            $query = "SELECT 
                        COALESCE(SUM(CASE WHEN attendance_status = 'PRESENT' THEN 1 ELSE 0 END), 0) as present_days,
                        COALESCE(SUM(CASE WHEN attendance_status = 'ABSENT' THEN 1 ELSE 0 END), 0) as absent_days,
                        COALESCE(SUM(CASE WHEN attendance_status = 'LATE' THEN 1 ELSE 0 END), 0) as late_days,
                        COALESCE(SUM(CASE WHEN attendance_status = 'HOLIDAY' THEN 1 ELSE 0 END), 0) as holiday_days,
                        COUNT(*) as total_records
                     FROM ta_attendance
                     WHERE employee_id = ?
                     AND MONTH(attendance_date) = ?
                     AND YEAR(attendance_date) = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $month, $year]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Calculate working days (excluding weekends and holidays)
            $workingDays = $this->calculateWorkingDaysInMonth($month, $year);
            $holidaysInMonth = $this->getHolidaysInMonth($month, $year);

            return [
                'present_days' => $result['present_days'],
                'absent_days' => $result['absent_days'],
                'late_days' => $result['late_days'],
                'holiday_days' => $result['holiday_days'],
                'total_records' => $result['total_records'],
                'working_days' => $workingDays,
                'holidays_in_month' => count($holidaysInMonth),
                'attendance_rate' => $workingDays > 0 ? round(($result['present_days'] / $workingDays) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate working days in a month (excluding weekends)
     */
    private function calculateWorkingDaysInMonth($month, $year)
    {
        $start = new \DateTime("{$year}-{$month}-01");
        $end = new \DateTime($start->format('Y-m-t'));
        $end->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        $workingDays = 0;

        foreach ($period as $date) {
            $dayOfWeek = $date->format('w');
            
            // Skip weekends
            if (in_array($dayOfWeek, [0, 6])) {
                continue;
            }

            // Skip holidays
            if (HolidayHelper::isHoliday($date->format('Y-m-d'))) {
                continue;
            }

            $workingDays++;
        }

        return $workingDays;
    }

    /**
     * Get holidays in a month
     */
    private function getHolidaysInMonth($month, $year)
    {
        return $this->holiday->getAllHolidays([
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Generate attendance report with holiday awareness
     */
    public function generateAttendanceReport($employeeId, $startDate, $endDate)
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->modify('+1 day');

            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);

            $report = [];

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                $record = [
                    'date' => $dateStr,
                    'day' => $date->format('l'),
                    'status' => 'NO_RECORD',
                    'is_holiday' => false,
                    'holiday_name' => null,
                    'check_in_time' => null,
                    'check_out_time' => null
                ];

                // Check if holiday
                if (HolidayHelper::isHoliday($dateStr)) {
                    $holiday = HolidayHelper::getHolidayByDate($dateStr);
                    $record['is_holiday'] = true;
                    $record['holiday_name'] = $holiday['name'];
                    $record['status'] = 'HOLIDAY';
                    $report[] = $record;
                    continue;
                }

                // Get attendance record
                $query = "SELECT * FROM ta_attendance
                         WHERE employee_id = ?
                         AND DATE(attendance_date) = ?
                         LIMIT 1";

                $stmt = $this->db->prepare($query);
                $stmt->execute([$employeeId, $dateStr]);

                $attendance = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($attendance) {
                    $record['status'] = $attendance['attendance_status'];
                    $record['check_in_time'] = $attendance['check_in_time'] ?? null;
                    $record['check_out_time'] = $attendance['check_out_time'] ?? null;
                }

                $report[] = $record;
            }

            return [
                'success' => true,
                'report' => $report,
                'total_days' => count($report),
                'employee_id' => $employeeId,
                'period' => "{$startDate} to {$endDate}"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
