<?php
/**
 * Enhanced Absence & Late Detector
 * Automatically detects employee absences and late arrivals
 * Marks attendance status in real-time
 */

namespace App\Helpers;

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/HolidayHelper.php';
require_once __DIR__ . '/../models/UnexpectedHoliday.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../services/AttendanceValidationService.php';
class EnhancedAbsenceDetector
{
    private $conn;
    private $attendance_table = "ta_attendance";
    private $employees_table = "em_employees";
    private $late_threshold_minutes = 0; // will read from validation service
    private $unexpectedHolidayModel;
    private $validationService;
    private $attendanceModel;

    public function __construct()
    {
        $db = \TimeDatabase::getInstance();
        $this->conn = $db->getConnection();
        $this->unexpectedHolidayModel = new \App\Models\UnexpectedHoliday();
        $this->attendanceModel = new \Attendance();
        $this->validationService = new \App\Services\AttendanceValidationService();
        $this->late_threshold_minutes = (int)$this->validationService->getLateThreshold();
    }

    /**
     * Detect and mark late arrivals for today
     */
    public function detectAndMarkLateToday()
    {
        $today = date('Y-m-d');

        // Find attendance records with time_in and status null or PRESENT
        $query = "SELECT a.attendance_id, a.employee_id, a.time_in, e.full_name, e.department
                  FROM {$this->attendance_table} a
                  JOIN {$this->employees_table} e ON a.employee_id = e.employee_id
                  WHERE a.attendance_date = :date
                  AND a.time_in IS NOT NULL
                  AND (a.status IS NULL OR a.status = 'PRESENT')
                  AND e.employment_status = 'Active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $today);
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];

        foreach ($records as $record) {
            $minutesLate = $this->validationService->calculateMinutesLate($record['employee_id'], $record['time_in'], $today);
            if ($minutesLate > 0) {
                // Persist LATE status and minutes
                $this->attendanceModel->updateStatus($record['attendance_id'], 'LATE', $minutesLate);
                $results[] = [
                    'attendance_id' => $record['attendance_id'],
                    'employee_id' => $record['employee_id'],
                    'name' => $record['full_name'],
                    'department' => $record['department'],
                    'time_in' => $record['time_in'],
                    'minutes_late' => $minutesLate
                ];
            }
        }

        return ['status' => 'completed', 'date' => $today, 'late_count' => count($results), 'results' => $results];
    }

    /**
     * Detect and mark absences for a date range (inclusive)
     */
    public function detectAndMarkAbsencesRange($start_date, $end_date)
    {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $results = ['status' => 'completed', 'start_date' => $start_date, 'end_date' => $end_date, 'days' => []];

        for ($dt = clone $start; $dt <= $end; $dt->modify('+1 day')) {
            $date = $dt->format('Y-m-d');

            if (!$this->isWorkingDay($date)) {
                $results['days'][$date] = ['status' => 'skipped', 'reason' => 'Holiday or weekend'];
                continue;
            }

            // Get active employees
            $employeesQuery = "SELECT 
                                employee_id,
                                CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name,
                                department
                              FROM {$this->employees_table}
                              WHERE employment_status = 'Active'";
            $stmt = $this->conn->prepare($employeesQuery);
            $stmt->execute();
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $dayResults = [];
            $now = new \DateTime($date . ' 23:59:59', new \DateTimeZone('Asia/Manila'));

            foreach ($employees as $employee) {
                $evaluation = $this->validationService->resolveExpectedAttendanceStatus($employee['employee_id'], $date, $now);
                if ($evaluation['status'] !== 'ABSENT') {
                    continue;
                }

                // If attendance row exists with time_in, skip
                $attendance = $this->attendanceModel->getTodayAttendance($employee['employee_id'], $date);
                if ($attendance && !empty($attendance['time_in'])) {
                    continue;
                }

                $created = $this->attendanceModel->markAbsent($employee['employee_id'], $date, 'Auto-detected absence - replayed detection for ' . $date);
                $dayResults[] = ['employee_id' => $employee['employee_id'], 'name' => $employee['full_name'], 'department' => $employee['department'], 'action' => $created ? 'Marked as ABSENT' : 'Already exists/updated'];
            }

            $results['days'][$date] = ['marked_absent' => count($dayResults), 'details' => $dayResults];
        }

        return $results;
    }

    /**
     * Detect and mark absences for today
     */
    public function detectAndMarkAbsenceToday()
    {
        $today = date('Y-m-d');
        return $this->detectAndMarkAbsencesRange($today, $today);
    }

    /**
     * Create or update absence record - kept for backward compat but uses Attendance model
     */
    private function createAbsenceRecord($employee_id, $absence_date, $notes = '')
    {
        return $this->attendanceModel->markAbsent($employee_id, $absence_date, $notes);
    }

    /**
     * Check if date is a working day (not weekend or holiday)
     */
    private function isWorkingDay($date)
    {
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek == 0) { // Sunday
            return false;
        }

        try {
            HolidayHelper::init($this->conn);
            if (HolidayHelper::isHoliday($date)) {
                return false;
            }
        } catch (\Exception $e) {
            // assume working day on error
        }

        try {
            if ($this->unexpectedHolidayModel && method_exists($this->unexpectedHolidayModel, 'isUnexpectedHoliday') && $this->unexpectedHolidayModel->isUnexpectedHoliday($date)) {
                return false;
            }
        } catch (\Exception $e) {
            // assume working day on error
        }

        return true;
    }

    /**
     * Get today's attendance summary (for dashboard)
     */
    public function getTodayAttendanceSummary()
    {
        $today = date('Y-m-d');
        $query = "SELECT 
                    COUNT(DISTINCT e.employee_id) as total_employees,
                    SUM(CASE WHEN a.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN a.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
                  FROM {$this->employees_table} e
                  LEFT JOIN {$this->attendance_table} a ON e.employee_id = a.employee_id AND a.attendance_date = :today
                  WHERE e.employment_status = 'Active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getTodayAllEmployeesWithStatus($limit = 100, $offset = 0)
    {
        $today = date('Y-m-d');
        $query = "SELECT 
                    COALESCE(a.attendance_id, 0) as attendance_id,
                    e.employee_id,
                    e.full_name,
                    e.department,
                    COALESCE(a.time_in, '-') as time_in,
                    COALESCE(a.time_out, '-') as time_out,
                    COALESCE(a.status, 'ABSENT') as status,
                    a.attendance_date
                  FROM {$this->employees_table} e
                  LEFT JOIN {$this->attendance_table} a ON e.employee_id = a.employee_id AND a.attendance_date = :today
                  WHERE e.employment_status = 'Active'
                  ORDER BY e.full_name
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>
