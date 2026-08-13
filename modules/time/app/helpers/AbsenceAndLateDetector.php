<?php
/**
 * Absence & Late Detector
 * Automatically detects employee absences and late arrivals and creates records
 */

namespace App\Helpers;

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../models/AbsenceLateMgmt.php';
require_once __DIR__ . '/HolidayHelper.php';

class AbsenceAndLateDetector
{
    private $conn;
    private $absenceLateMgmt;
    private $attendance_table = "ta_attendance";
    private $employees_table = "employees";
    private $shifts_table = "ta_shifts";
    private $shift_assignments_table = "ta_shift_assignments";
    private $absence_late_table = "ta_absence_late_records";

    public function __construct()
    {
        $db = new \Database();
        $this->conn = $db->getConnection();
        $this->absenceLateMgmt = new \AbsenceLateMgmt();
    }

    /**
     * Detect and create absence records for today
     * Called daily to check which employees didn't show up
     */
    public function detectTodayAbsences()
    {
        $today = date('Y-m-d');
        
        HolidayHelper::init($this->conn);
        if (HolidayHelper::isHoliday($today)) {
            return ['message' => 'Today is a holiday, no absence detection'];
        }

        $dayOfWeek = date('w');
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return ['message' => 'Today is a weekend, no absence detection'];
        }

        $query = "SELECT e.employee_id, e.full_name, e.department
                  FROM {$this->employees_table} e
                  WHERE e.employment_status = 'Active'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        $now = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $validationService = new \App\Services\AttendanceValidationService();

        foreach ($employees as $employee) {
            $evaluation = $validationService->resolveExpectedAttendanceStatus($employee['employee_id'], $today, $now);
            if ($evaluation['status'] !== 'ABSENT') {
                continue;
            }

            $created = $this->createAbsenceRecord(
                $employee['employee_id'],
                $today,
                $evaluation['reason'] ?? 'Auto-detected absence'
            );

            if ($created) {
                $results[] = [
                    'employee_id' => $employee['employee_id'],
                    'name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'status' => 'Absence record created'
                ];
            }
        }

        return $results;
    }

    /**
     * Detect and create late records for a specific date
     * Call this after shift hours to check for late arrivals
     */
    public function detectLateArrivals($date = null)
    {
        $date = $date ?? date('Y-m-d');
        
        // Check if date is a holiday
        HolidayHelper::init($this->conn);
        if (HolidayHelper::isHoliday($date)) {
            return ['message' => 'Date is a holiday, no late detection'];
        }

        // Get all employees who checked in late today
        $query = "SELECT 
                    a.attendance_id,
                    a.employee_id,
                    a.time_in,
                    e.full_name,
                    e.department,
                    s.shift_id,
                    s.start_time
                  FROM {$this->attendance_table} a
                  JOIN {$this->employees_table} e ON a.employee_id = e.employee_id
                  JOIN {$this->shift_assignments_table} sa ON a.employee_id = sa.employee_id
                  JOIN {$this->shifts_table} s ON sa.shift_id = s.shift_id
                  WHERE a.attendance_date = :date
                  AND a.time_in IS NOT NULL
                  AND a.status != 'LATE'
                  AND TIME(a.time_in) > TIME(s.start_time)
                  AND sa.effective_from <= :date
                  AND (sa.effective_to IS NULL OR sa.effective_to >= :date)
                  GROUP BY a.attendance_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $lateEmployees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($lateEmployees as $employee) {
            $minutesLate = $this->calculateMinutesLate(
                $employee['time_in'],
                $employee['start_time']
            );

            $created = $this->createLateRecord(
                $employee['employee_id'],
                $employee['attendance_id'],
                $date,
                $minutesLate,
                "Auto-detected late arrival ({$minutesLate} minutes)"
            );

            if ($created) {
                $results[] = [
                    'employee_id' => $employee['employee_id'],
                    'name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'minutes_late' => $minutesLate,
                    'status' => 'Late record created'
                ];
            }
        }

        return $results;
    }

    /**
     * Create absence record
     */
    private function createAbsenceRecord($employee_id, $absence_date, $notes = '')
    {
        // Check if absence record already exists
        $checkQuery = "SELECT id FROM {$this->absence_late_table}
                      WHERE employee_id = :employee_id
                      AND absence_date = :absence_date
                      AND type = 'ABSENCE'
                      LIMIT 1";

        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $checkStmt->bindParam(':absence_date', $absence_date);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return false; // Record already exists
        }

        // Create new absence record
        $insertQuery = "INSERT INTO {$this->absence_late_table}
                        (employee_id, attendance_id, type, absence_date, excuse_status, is_excused, notes, created_at)
                        VALUES (:employee_id, NULL, 'ABSENCE', :absence_date, 'PENDING', 0, :notes, NOW())";

        $insertStmt = $this->conn->prepare($insertQuery);
        $insertStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $insertStmt->bindParam(':absence_date', $absence_date);
        $insertStmt->bindParam(':notes', $notes);

        return $insertStmt->execute();
    }

    /**
     * Create late record
     */
    private function createLateRecord($employee_id, $attendance_id, $absence_date, $minutes_late, $notes = '')
    {
        // Check if late record already exists
        $checkQuery = "SELECT id FROM {$this->absence_late_table}
                      WHERE employee_id = :employee_id
                      AND absence_date = :absence_date
                      AND type = 'LATE'
                      LIMIT 1";

        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $checkStmt->bindParam(':absence_date', $absence_date);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return false; // Record already exists
        }

        // Create new late record
        $insertQuery = "INSERT INTO {$this->absence_late_table}
                        (employee_id, attendance_id, type, absence_date, minutes_late, excuse_status, is_excused, notes, created_at)
                        VALUES (:employee_id, :attendance_id, 'LATE', :absence_date, :minutes_late, 'PENDING', 0, :notes, NOW())";

        $insertStmt = $this->conn->prepare($insertQuery);
        $insertStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $insertStmt->bindParam(':attendance_id', $attendance_id, \PDO::PARAM_INT);
        $insertStmt->bindParam(':absence_date', $absence_date);
        $insertStmt->bindParam(':minutes_late', $minutes_late, \PDO::PARAM_INT);
        $insertStmt->bindParam(':notes', $notes);

        return $insertStmt->execute();
    }

    /**
     * Calculate minutes late
     */
    private function calculateMinutesLate($timeIn, $startTime)
    {
        $inTime = new \DateTime($timeIn);
        $startDateTime = new \DateTime(date('Y-m-d') . ' ' . $startTime);

        $interval = $inTime->diff($startDateTime);
        return $interval->h * 60 + $interval->i;
    }

    /**
     * Create announcement/notification for absence
     */
    public function announceAbsence($employee_id, $absence_date)
    {
        // Get employee details
        $empQuery = "SELECT full_name, department FROM {$this->employees_table}
                    WHERE employee_id = :employee_id LIMIT 1";
        $empStmt = $this->conn->prepare($empQuery);
        $empStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $empStmt->execute();
        $employee = $empStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$employee) {
            return false;
        }

        // Create notification (you can customize this based on your notification system)
        $announcement = [
            'type' => 'ABSENCE',
            'title' => 'Employee Absence - ' . $employee['full_name'],
            'message' => "{$employee['full_name']} ({$employee['department']}) is marked ABSENT on " . date('F d, Y', strtotime($absence_date)),
            'employee_id' => $employee_id,
            'date' => $absence_date,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->createNotification($announcement);
    }

    /**
     * Create announcement/notification for late arrival
     */
    public function announceLate($employee_id, $absence_date, $minutes_late)
    {
        // Get employee details
        $empQuery = "SELECT full_name, department FROM {$this->employees_table}
                    WHERE employee_id = :employee_id LIMIT 1";
        $empStmt = $this->conn->prepare($empQuery);
        $empStmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $empStmt->execute();
        $employee = $empStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$employee) {
            return false;
        }

        // Create notification (you can customize this based on your notification system)
        $announcement = [
            'type' => 'LATE',
            'title' => 'Employee Late Arrival - ' . $employee['full_name'],
            'message' => "{$employee['full_name']} ({$employee['department']}) was {$minutes_late} minutes late on " . date('F d, Y', strtotime($absence_date)),
            'employee_id' => $employee_id,
            'date' => $absence_date,
            'minutes_late' => $minutes_late,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->createNotification($announcement);
    }

    /**
     * Create notification record
     * This can be integrated with your notification/announcement system
     */
    private function createNotification($announcement)
    {
        // Log to file for now (you can enhance this to store in database)
        $logFile = __DIR__ . '/../../logs/absence_late_announcements.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = json_encode($announcement) . "\n";
        return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
    }
}
?>
