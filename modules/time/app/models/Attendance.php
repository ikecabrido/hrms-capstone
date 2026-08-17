<?php
/**
 * Attendance Model for Time & Attendance System
 * Handles all attendance-related database operations
 */

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../../classes/Employee.php';

class Attendance
{
    private $conn;
    private $table = "ta_attendance";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Get today's attendance record for an employee
     */
    public function getTodayAllEmployees($limit = 100, $offset = 0)
{
    $query = "SELECT
                e.employee_id,
                e.first_name,
                e.middle_name,
                e.last_name,
                CONCAT(
                    COALESCE(e.first_name, ''),
                    ' ',
                    COALESCE(e.last_name, '')
                ) AS full_name,
                e.department,
                e.position,
                e.employment_status,

                a.attendance_id,
                a.time_in,
                a.time_out,
                a.attendance_date,
                a.status,
                a.late_minutes,
                a.recorded_by,
                a.total_hours_worked,
                a.regular_hours,
                a.overtime_hours,
                a.created_at,
                a.updated_at

              FROM em_employees e

              LEFT JOIN ta_attendance a
                ON a.employee_id = e.employee_id
                AND a.attendance_date = CURDATE()

              WHERE LOWER(e.employment_status) = 'active'

              ORDER BY full_name ASC

              LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($query);

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Record Time In with status and optional late minutes
     */
    public function timeIn($employee_id, $method, $status = 'PRESENT', $late_minutes = 0)
    {
        $attendance_date = date('Y-m-d');
        $existingRecord = $this->getTodayAttendance($employee_id, $attendance_date);

        if ($existingRecord && !empty($existingRecord['time_in'])) {
            return false;
        }

        if ($existingRecord && empty($existingRecord['time_in'])) {
            $query = "UPDATE $this->table
                      SET time_in = NOW(), recorded_by = :method, status = :status, late_minutes = :late_minutes, updated_at = CURRENT_TIMESTAMP
                      WHERE attendance_id = :attendance_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':late_minutes', $late_minutes, PDO::PARAM_INT);
            $stmt->bindParam(':attendance_id', $existingRecord['attendance_id']);

            return $stmt->execute();
        }

        $query = "INSERT INTO $this->table 
                  (employee_id, time_in, attendance_date, recorded_by, status, late_minutes)
                  VALUES (:employee_id, NOW(), :attendance_date, :method, :status, :late_minutes)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':attendance_date', $attendance_date);
        $stmt->bindParam(':method', $method);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':late_minutes', $late_minutes, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Record Time Out
     */
    public function timeOut($attendance_id)
    {
        error_log("Attendance::timeOut entered with attendance_id=" . var_export($attendance_id, true));

        if (empty($attendance_id) || !is_numeric($attendance_id) || intval($attendance_id) <= 0) {
            error_log("Attendance::timeOut called with invalid attendance_id: " . var_export($attendance_id, true));
            return false;
        }

        $query = "UPDATE $this->table 
                  SET time_out = NOW(), updated_at = CURRENT_TIMESTAMP
                  WHERE attendance_id = :attendance_id
                  AND (time_out IS NULL OR time_out = '0000-00-00 00:00:00')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);

        try {
            $result = $stmt->execute();
        } catch (Exception $e) {
            error_log("Attendance::timeOut CATCH attendance_id={$attendance_id} error=" . $e->getMessage() . " file=" . $e->getFile() . " line=" . $e->getLine());
            throw $e;
        }

        $affectedRows = $stmt->rowCount();
        error_log("Attendance::timeOut attendance_id={$attendance_id} result=" . ($result ? 'true' : 'false') . " affected_rows={$affectedRows}");

        return ($result && $affectedRows > 0);
    }

    /**
     * Record Time Out at a specific timestamp
     */
    public function timeOutAt($attendance_id, $time_out)
    {
        if (empty($attendance_id) || !is_numeric($attendance_id) || intval($attendance_id) <= 0) {
            return false;
        }

        $query = "UPDATE $this->table 
                  SET time_out = :time_out, updated_at = CURRENT_TIMESTAMP
                  WHERE attendance_id = :attendance_id
                  AND (time_out IS NULL OR time_out = '0000-00-00 00:00:00')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);
        $stmt->bindParam(':time_out', $time_out);

        $result = $stmt->execute();
        return ($result && $stmt->rowCount() > 0);
    }

    /**
     * Get attendance records for date range
     */
    public function getByDateRange($start_date, $end_date, $employee_id = null, $limit = 500, $offset = 0)
    {
        $query = "SELECT a.*, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, e.department, e.position
                  FROM $this->table a
                  JOIN em_employees e ON a.employee_id = e.employee_id
                  WHERE a.attendance_date BETWEEN :start_date AND :end_date";

        if (!is_null($employee_id)) {
            $query .= " AND a.employee_id = :employee_id";
        }

        $query .= " ORDER BY a.attendance_date DESC, a.created_at DESC
                   LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);

        if (!is_null($employee_id)) {
            $stmt->bindParam(':employee_id', $employee_id);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's attendance summary (for dashboard)
     */
    public function getTodaySummary()
    {
        $query = "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN time_in IS NULL THEN 1 ELSE 0 END) as absent_count
                  FROM $this->table
                  WHERE attendance_date = CURDATE()";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTodayAttendance($employee_id, $attendance_date = null)
    {
        $attendance_date = $attendance_date ?? date('Y-m-d');

        $query = "SELECT *
                FROM {$this->table}
                WHERE employee_id = :employee_id
                AND attendance_date = :attendance_date
                LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':attendance_date', $attendance_date);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Get attendance history for a specific employee
     */
    public function getEmployeeHistory($employee_id, $limit = 30, $offset = 0)
    {
        $query = "SELECT * FROM $this->table
                  WHERE employee_id = :employee_id
                  ORDER BY attendance_date DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pending approvals
     */
    public function getPendingApprovals($limit = 50, $offset = 0)
    {
        $query = "SELECT a.*, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, e.department
                  FROM $this->table a
                  JOIN em_employees e ON a.employee_id = e.employee_id
                  WHERE a.is_approved = 0
                  ORDER BY a.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve attendance record
     */
    public function approve($attendance_id, $approved_by, $remarks = "")
    {
        $query = "UPDATE $this->table 
                  SET is_approved = 1, 
                      approved_by = :approved_by,
                      approval_remarks = :remarks,
                      approved_at = NOW()
                  WHERE attendance_id = :attendance_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attendance_id', $attendance_id);
        $stmt->bindParam(':approved_by', $approved_by);
        $stmt->bindParam(':remarks', $remarks);

        return $stmt->execute();
    }

    /**
     * Update attendance status and optionally late minutes
     */
    public function updateStatus($attendance_id, $status, $late_minutes = null)
    {
        $query = "UPDATE $this->table 
                  SET status = :status";

        if ($late_minutes !== null) {
            $query .= ", late_minutes = :late_minutes";
        }

        $query .= " WHERE attendance_id = :attendance_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attendance_id', $attendance_id);
        $stmt->bindParam(':status', $status);

        if ($late_minutes !== null) {
            $stmt->bindParam(':late_minutes', $late_minutes, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Record a system-generated absence in ta_attendance
     */
    public function markAbsent($employee_id, $attendance_date = null, $notes = null)
    {
        $attendance_date = $attendance_date ?? date('Y-m-d');
        $debugLogPath = __DIR__ . '/../../logs/absence_detection_debug.log';

        $existingRecord = $this->getTodayAttendance($employee_id, $attendance_date);
        file_put_contents($debugLogPath, "[Attendance::markAbsent] employee_id={$employee_id} attendance_date={$attendance_date} existingRecord=" . ($existingRecord ? 'true' : 'false') . " time_in=" . ($existingRecord['time_in'] ?? 'NULL') . " time_out=" . ($existingRecord['time_out'] ?? 'NULL') . " status=" . ($existingRecord['status'] ?? 'NULL') . "\n", FILE_APPEND);
        // If a record exists with time_in, do not mark absent
        if ($existingRecord && !empty($existingRecord['time_in'])) {
            file_put_contents($debugLogPath, "[Attendance::markAbsent] skipped because existing time_in present\n", FILE_APPEND);
            return false;
        }

        // If a record exists but has no time_in, update it to ABSENT (idempotent)
        if ($existingRecord && empty($existingRecord['time_in'])) {
            $query = "UPDATE {$this->table} SET status = 'ABSENT', recorded_by = 'SYSTEM', updated_at = CURRENT_TIMESTAMP";
            $columnCheck = $this->conn->prepare("SHOW COLUMNS FROM {$this->table} LIKE 'notes'");
            $columnCheck->execute();
            if ($columnCheck->rowCount() > 0 && $notes !== null) {
                $query .= ", notes = CONCAT(IFNULL(notes, ''), '\n', :notes)";
            }
            $query .= " WHERE attendance_id = :attendance_id";

            $stmt = $this->conn->prepare($query);
            if ($columnCheck->rowCount() > 0 && $notes !== null) {
                $stmt->bindParam(':notes', $notes);
            }
            $stmt->bindParam(':attendance_id', $existingRecord['attendance_id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            file_put_contents($debugLogPath, "[Attendance::markAbsent] update_existing result=" . ($result ? 'true' : 'false') . " rowcount=" . $stmt->rowCount() . " query=" . $query . "\n", FILE_APPEND);
            return $result;
        }

        $columnCheck = $this->conn->prepare("SHOW COLUMNS FROM {$this->table} LIKE 'notes'");
        $columnCheck->execute();
        $hasNotesColumn = $columnCheck->rowCount() > 0;

        $query = "INSERT INTO {$this->table}
                  (employee_id, attendance_date, status, recorded_by, late_minutes";
        $values = ") VALUES (:employee_id, :attendance_date, 'ABSENT', 'SYSTEM', 0";

        if ($hasNotesColumn) {
            $query .= ", notes";
            $values .= ", :notes";
        }

        $query .= $values . ")";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':attendance_date', $attendance_date);

        if ($hasNotesColumn) {
            $stmt->bindParam(':notes', $notes);
        }

        $result = $stmt->execute();
        file_put_contents($debugLogPath, "[Attendance::markAbsent] insert_new result=" . ($result ? 'true' : 'false') . " rowcount=" . $stmt->rowCount() . " query=" . $query . "\n", FILE_APPEND);
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            file_put_contents($debugLogPath, "[Attendance::markAbsent] insert_error=" . json_encode($errorInfo) . "\n", FILE_APPEND);
        }

        return $result;
    }

    /**
     * Record a system-generated late attendance status for no time-in yet after shift start
     */
    public function markLate($employee_id, $attendance_date = null, $late_minutes = 0, $notes = null)
    {
        $attendance_date = $attendance_date ?? date('Y-m-d');

        $existingRecord = $this->getTodayAttendance($employee_id, $attendance_date);
        if ($existingRecord && !empty($existingRecord['time_in'])) {
            return false;
        }

        if ($existingRecord && empty($existingRecord['time_in'])) {
            $query = "UPDATE {$this->table} SET status = 'LATE', late_minutes = :late_minutes, recorded_by = 'SYSTEM', updated_at = CURRENT_TIMESTAMP";
            $columnCheck = $this->conn->prepare("SHOW COLUMNS FROM {$this->table} LIKE 'notes'");
            $columnCheck->execute();
            if ($columnCheck->rowCount() > 0 && $notes !== null) {
                $query .= ", notes = CONCAT(IFNULL(notes, ''), '\n', :notes)";
            }
            $query .= " WHERE attendance_id = :attendance_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':late_minutes', $late_minutes, PDO::PARAM_INT);
            if ($columnCheck->rowCount() > 0 && $notes !== null) {
                $stmt->bindParam(':notes', $notes);
            }
            $stmt->bindParam(':attendance_id', $existingRecord['attendance_id'], PDO::PARAM_INT);
            return $stmt->execute();
        }

        $columnCheck = $this->conn->prepare("SHOW COLUMNS FROM {$this->table} LIKE 'notes'");
        $columnCheck->execute();
        $hasNotesColumn = $columnCheck->rowCount() > 0;

        $query = "INSERT INTO {$this->table}
                  (employee_id, attendance_date, status, recorded_by, late_minutes";
        $values = ") VALUES (:employee_id, :attendance_date, 'LATE', 'SYSTEM', :late_minutes";

        if ($hasNotesColumn) {
            $query .= ", notes";
            $values .= ", :notes";
        }

        $query .= $values . ")";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':attendance_date', $attendance_date);
        $stmt->bindParam(':late_minutes', $late_minutes, PDO::PARAM_INT);
        if ($hasNotesColumn) {
            $stmt->bindParam(':notes', $notes);
        }

        return $stmt->execute();
    }

    /**
     * Update attendance record with hours data
     */
    public function updateHours($attendance_id, $hoursData)
    {
        $query = "UPDATE $this->table 
                  SET total_hours_worked = :total_hours,
                      regular_hours = :regular_hours,
                      overtime_hours = :overtime_hours
                  WHERE attendance_id = :attendance_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attendance_id', $attendance_id);
        $stmt->bindParam(':total_hours', $hoursData['total_hours']);
        $stmt->bindParam(':regular_hours', $hoursData['regular_hours']);
        $stmt->bindParam(':overtime_hours', $hoursData['overtime_hours']);

        return $stmt->execute();
    }

    /**
     * Check if a date is a holiday
     */
    public function isHoliday($date)
    {
        $query = "SELECT is_working_day FROM ta_holidays 
                  WHERE holiday_date = :date 
                  AND year = YEAR(:date) 
                  AND is_working_day = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    /**
     * Get holiday information for a date
     */
        public function getHolidayInfo($date)
    {
        $query = "SELECT id, name, description
                FROM ta_holidays
                WHERE holiday_date = :date
                AND is_active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all holidays for a year
     */
    public function getHolidaysByYear($year = null)
    {
        $year = $year ?: date('Y');
        
        $query = "SELECT holiday_date, holiday_name, description, is_working_day 
                  FROM ta_holidays 
                  WHERE year = :year 
                  ORDER BY holiday_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if employee has a shift exclusion for a given date (e.g., Saturday exclusion)
     */
    public function hasShiftExclusionForDate($employee_id, $date)
    {
        $query = "SELECT COUNT(*) as exclusion_count 
                  FROM ta_shift_exclusions se
                  WHERE se.exclusion_date = :date
                  AND se.employee_shift_id IN (
                      SELECT employee_shift_id FROM ta_employee_shifts 
                      WHERE employee_id = :employee_id 
                      AND is_active = 1
                      AND effective_from <= :date
                      AND (effective_to IS NULL OR effective_to >= :date)
                  )";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':date', $date);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['exclusion_count'] > 0;
        } catch (Exception $e) {
            // If table doesn't exist yet, return false
            return false;
        }
    }
}
