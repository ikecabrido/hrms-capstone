<?php

/**
 * Leave & Absence Holiday Integration
 * Handles holiday logic in leave/absence management
 */

namespace App\Integrations;

use App\Models\Holiday;
use App\Helpers\HolidayHelper;

class LeaveHolidayIntegration
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
     * Check if leave request overlaps with holidays
     */
    public function checkHolidayOverlap($startDate, $endDate)
    {
        try {
            $holidays = HolidayHelper::getHolidaysBetween($startDate, $endDate);

            return [
                'has_overlap' => count($holidays) > 0,
                'holidays' => $holidays,
                'holiday_count' => count($holidays)
            ];
        } catch (\Exception $e) {
            return [
                'has_overlap' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate leave days excluding holidays
     */
    public function calculateLeaveDaysExcludingHolidays($startDate, $endDate, $includeWeekends = false)
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->modify('+1 day'); // Include end date

            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);

            $leaveDays = 0;
            $holidaysExcluded = [];
            $weekendDays = 0;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayOfWeek = $date->format('w'); // 0 = Sunday, 6 = Saturday

                // Check if holiday
                if (HolidayHelper::isHoliday($dateStr)) {
                    $holiday = HolidayHelper::getHolidayByDate($dateStr);
                    $holidaysExcluded[] = $holiday;
                    continue;
                }

                // Check if weekend
                if (!$includeWeekends && in_array($dayOfWeek, [0, 6])) {
                    $weekendDays++;
                    continue;
                }

                $leaveDays++;
            }

            return [
                'success' => true,
                'leave_days' => $leaveDays,
                'total_days' => count($period) - 1, // Exclude the extra day from +1
                'holidays_excluded' => count($holidaysExcluded),
                'weekend_days' => $weekendDays,
                'holidays' => $holidaysExcluded,
                'include_weekends' => $includeWeekends
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate leave request against holidays
     */
    public function validateLeaveRequest($employeeId, $startDate, $endDate, $leaveType = 'VACATION')
    {
        try {
            $errors = [];
            $warnings = [];

            // Check date order
            if (strtotime($startDate) > strtotime($endDate)) {
                $errors[] = 'Start date must be before end date';
            }

            // Check if dates are in the past
            if (strtotime($startDate) < strtotime('today')) {
                $errors[] = 'Cannot apply leave for past dates';
            }

            // Check holiday overlap
            $overlap = $this->checkHolidayOverlap($startDate, $endDate);
            if ($overlap['has_overlap']) {
                $warnings[] = [
                    'message' => $overlap['holiday_count'] . ' holiday(s) within leave period',
                    'holidays' => $overlap['holidays']
                ];
            }

            // Check existing leave/absence
            $existingLeave = $this->checkExistingLeave($employeeId, $startDate, $endDate);
            if ($existingLeave['count'] > 0) {
                $errors[] = 'Leave already exists for some dates in this period';
            }

            // Calculate leave days
            $leaveDaysInfo = $this->calculateLeaveDaysExcludingHolidays($startDate, $endDate);

            return [
                'valid' => count($errors) === 0,
                'errors' => $errors,
                'warnings' => $warnings,
                'leave_days_calculation' => $leaveDaysInfo
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Check existing leave/absence records
     */
    private function checkExistingLeave($employeeId, $startDate, $endDate)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM ta_leave_requests
                     WHERE employee_id = ?
                     AND leave_status NOT IN ('REJECTED', 'CANCELLED')
                     AND (
                        (start_date <= ? AND end_date >= ?)
                        OR (start_date >= ? AND start_date <= ?)
                        OR (end_date >= ? AND end_date <= ?)
                     )";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $endDate, $startDate, $startDate, $endDate, $startDate, $endDate]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return ['count' => 0];
        }
    }

    /**
     * Create leave request with holiday awareness
     */
    public function createLeaveRequest($data)
    {
        try {
            // Validate
            $validation = $this->validateLeaveRequest(
                $data['employee_id'],
                $data['start_date'],
                $data['end_date'],
                $data['leave_type'] ?? 'VACATION'
            );

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ];
            }

            // Calculate days excluding holidays and weekends
            $leaveDays = $validation['leave_days_calculation']['leave_days'];

            // Check leave balance
            $balance = $this->checkLeaveBalance($data['employee_id'], $data['leave_type'], $leaveDays);

            if (!$balance['has_balance']) {
                return [
                    'success' => false,
                    'message' => 'Insufficient leave balance',
                    'available' => $balance['available'],
                    'required' => $leaveDays
                ];
            }

            // Create leave request
            $query = "INSERT INTO ta_leave_requests
                     (employee_id, leave_type, start_date, end_date, reason, leave_days, leave_status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['employee_id'],
                $data['leave_type'] ?? 'VACATION',
                $data['start_date'],
                $data['end_date'],
                $data['reason'] ?? '',
                $leaveDays
            ]);

            return [
                'success' => true,
                'message' => 'Leave request created',
                'leave_id' => $this->db->lastInsertId(),
                'leave_days' => $leaveDays,
                'warnings' => $validation['warnings']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check employee leave balance
     */
    private function checkLeaveBalance($employeeId, $leaveType, $requestedDays)
    {
        try {
            $query = "SELECT balance FROM ta_leave_balances
                     WHERE employee_id = ?
                     AND leave_type = ?
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $leaveType]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'has_balance' => false,
                    'available' => 0,
                    'message' => 'No leave balance found'
                ];
            }

            $available = $result['balance'];
            $hasBalance = $available >= $requestedDays;

            return [
                'has_balance' => $hasBalance,
                'available' => $available,
                'required' => $requestedDays
            ];
        } catch (\Exception $e) {
            return [
                'has_balance' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get leave request preview
     */
    public function getLeaveRequestPreview($startDate, $endDate, $employeeId = null)
    {
        try {
            $calculation = $this->calculateLeaveDaysExcludingHolidays($startDate, $endDate);
            $overlap = $this->checkHolidayOverlap($startDate, $endDate);

            return [
                'success' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_calendar_days' => $calculation['total_days'],
                'weekend_days' => $calculation['weekend_days'],
                'holiday_days' => $calculation['holidays_excluded'],
                'actual_leave_days' => $calculation['leave_days'],
                'holidays_in_period' => $overlap['holidays'],
                'notes' => [
                    'holidays' => $overlap['has_overlap'] ? $overlap['holiday_count'] . ' holiday(s) excluded' : 'No holidays in period'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get absence status with holiday check
     */
    public function getAbsenceStatus($employeeId, $date)
    {
        try {
            // Check if holiday
            if (HolidayHelper::isHoliday($date)) {
                $holiday = HolidayHelper::getHolidayByDate($date);
                return [
                    'status' => 'HOLIDAY',
                    'isHoliday' => true,
                    'holiday' => $holiday,
                    'message' => 'No absence recorded (holiday)'
                ];
            }

            // Check existing absence/leave
            $query = "SELECT * FROM ta_leave_requests
                     WHERE employee_id = ?
                     AND DATE(start_date) <= ?
                     AND DATE(end_date) >= ?
                     AND leave_status = 'APPROVED'
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $date, $date]);

            $leave = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($leave) {
                return [
                    'status' => 'ON_LEAVE',
                    'isHoliday' => false,
                    'leave_type' => $leave['leave_type'],
                    'message' => 'Employee on approved leave'
                ];
            }

            // Check attendance
            $query = "SELECT attendance_status FROM ta_attendance
                     WHERE employee_id = ?
                     AND DATE(attendance_date) = ?
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $date]);

            $attendance = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($attendance && $attendance['attendance_status'] === 'PRESENT') {
                return [
                    'status' => 'PRESENT',
                    'isHoliday' => false,
                    'message' => 'Employee marked present'
                ];
            }

            return [
                'status' => 'ABSENT',
                'isHoliday' => false,
                'message' => 'Employee marked absent'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate leave report with holiday information
     */
    public function generateLeaveReport($employeeId, $startDate, $endDate)
    {
        try {
            $query = "SELECT * FROM ta_leave_requests
                     WHERE employee_id = ?
                     AND start_date >= ?
                     AND end_date <= ?
                     ORDER BY start_date";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $startDate, $endDate]);

            $leaves = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $report = [];
            $totalLeaveDays = 0;
            $holidaysOverlap = 0;

            foreach ($leaves as $leave) {
                $calculation = $this->calculateLeaveDaysExcludingHolidays(
                    $leave['start_date'],
                    $leave['end_date']
                );

                $overlap = $this->checkHolidayOverlap(
                    $leave['start_date'],
                    $leave['end_date']
                );

                $leaveDays = $calculation['leave_days'];
                $totalLeaveDays += $leaveDays;
                $holidaysOverlap += $overlap['holiday_count'];

                $report[] = [
                    'leave_id' => $leave['id'],
                    'leave_type' => $leave['leave_type'],
                    'start_date' => $leave['start_date'],
                    'end_date' => $leave['end_date'],
                    'status' => $leave['leave_status'],
                    'leave_days_actual' => $leaveDays,
                    'holidays_in_period' => $overlap['holiday_count'],
                    'approved_date' => $leave['approved_at'] ?? null
                ];
            }

            return [
                'success' => true,
                'report' => $report,
                'summary' => [
                    'total_leave_requests' => count($leaves),
                    'total_leave_days' => $totalLeaveDays,
                    'total_holidays_overlap' => $holidaysOverlap,
                    'period' => "{$startDate} to {$endDate}"
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark dates as absent (excluding holidays)
     */
    public function markAbsentDates($employeeId, $startDate, $endDate, $reason = 'Unauthorized Absence')
    {
        try {
            $this->db->beginTransaction();

            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->modify('+1 day');

            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);

            $markedDays = 0;
            $skippedDays = 0;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Skip holidays
                if (HolidayHelper::isHoliday($dateStr)) {
                    $skippedDays++;
                    continue;
                }

                // Mark as absent
                $query = "INSERT INTO ta_attendance
                         (employee_id, attendance_date, attendance_status, notes, created_at)
                         VALUES (?, ?, 'ABSENT', ?, NOW())
                         ON DUPLICATE KEY UPDATE
                         attendance_status = 'ABSENT',
                         notes = ?,
                         updated_at = NOW()";

                $stmt = $this->db->prepare($query);
                $stmt->execute([$employeeId, $dateStr, $reason, $reason]);

                $markedDays++;
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Marked {$markedDays} days as absent",
                'marked_days' => $markedDays,
                'holidays_skipped' => $skippedDays
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
