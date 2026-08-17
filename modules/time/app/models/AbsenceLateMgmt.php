<?php
/**
 * Absence & Late Management Model
 * Handles all absence and late arrival tracking, excuse management, and reporting
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class AbsenceLateMgmt
{
    private $conn;
    private $records_table = "ta_absence_late_records";
    private $thresholds_table = "ta_absence_late_thresholds";
    private $policies_table = "ta_absence_late_policies";
    private $attendance_table = "ta_attendance";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Get all absence & late records with filters
     */
    public function getRecords($filters = [])
    {
        $query = "SELECT * FROM (
                    SELECT 
                        r.record_id,
                        r.employee_id,
                        r.absence_date,
                        CAST(r.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                        CAST(r.excuse_status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                        CAST(r.reason AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        r.created_at,
                        NULL AS time_in,
                        NULL AS time_out,
                        NULL AS late_minutes,
                        NULL AS total_hours_worked,
                        NULL AS regular_hours,
                        NULL AS overtime_hours,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST('legacy' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->records_table} r
                    JOIN em_employees e ON r.employee_id = e.employee_id
                    WHERE 1=1

                    UNION ALL

                    SELECT 
                        a.attendance_id AS record_id,
                        a.employee_id,
                        a.attendance_date AS absence_date,
                        CASE 
                            WHEN a.status = 'ABSENT' THEN 'ABSENT'
                            WHEN a.status = 'LATE' THEN 'LATE'
                            ELSE 'ABSENT'
                        END AS type,
                        CAST('PENDING' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                        CAST(CASE 
                            WHEN TRIM(COALESCE(a.approval_remarks, '')) <> '' THEN a.approval_remarks
                            WHEN a.status = 'ABSENT' THEN 'Marked absent by system'
                            WHEN a.status = 'LATE' THEN 'Marked late by system'
                            ELSE 'Attendance record'
                        END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        a.created_at,
                        a.time_in,
                        a.time_out,
                        a.late_minutes,
                        a.total_hours_worked,
                        a.regular_hours,
                        a.overtime_hours,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST('attendance' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->attendance_table} a
                    JOIN em_employees e ON a.employee_id = e.employee_id
                    WHERE a.status IN ('ABSENT', 'LATE')
                ) AS combined
                WHERE 1=1";

        // Filter by employee
        if (!empty($filters['employee_id'])) {
            $query .= " AND combined.employee_id = :employee_id";
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query .= " AND combined.type = :type";
        }

        // Filter by excuse status
        if (!empty($filters['excuse_status'])) {
            $query .= " AND combined.excuse_status = :excuse_status";
        }

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query .= " AND combined.absence_date BETWEEN :start_date AND :end_date";
        }

        $query .= " ORDER BY combined.absence_date DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if (!empty($filters['employee_id'])) {
            $stmt->bindParam(':employee_id', $filters['employee_id'], PDO::PARAM_INT);
        }
        if (!empty($filters['type'])) {
            $stmt->bindParam(':type', $filters['type']);
        }
        if (!empty($filters['excuse_status'])) {
            $stmt->bindParam(':excuse_status', $filters['excuse_status']);
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $stmt->bindParam(':start_date', $filters['start_date']);
            $stmt->bindParam(':end_date', $filters['end_date']);
        }

        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single absence/late record
     */
    public function getRecord($record_id)
    {
        $query = "SELECT 
                    r.record_id,
                    r.employee_id,
                    r.absence_date,
                    r.type,
                    r.excuse_status,
                    r.reason,
                    r.created_at,
                    NULL AS time_in,
                    NULL AS time_out,
                    NULL AS late_minutes,
                    NULL AS total_hours_worked,
                    NULL AS regular_hours,
                    NULL AS overtime_hours,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    e.department,
                    'legacy' AS source
                  FROM {$this->records_table} r
                  JOIN em_employees e ON r.employee_id = e.employee_id
                  WHERE r.record_id = :record_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            return $record;
        }

        $query = "SELECT 
                    a.attendance_id AS record_id,
                    a.employee_id,
                    a.attendance_date AS absence_date,
                    CASE 
                        WHEN a.status = 'ABSENT' THEN 'ABSENT'
                        WHEN a.status = 'LATE' THEN 'LATE'
                        ELSE 'ABSENT'
                    END AS type,
                    'PENDING' AS excuse_status,
                    CASE 
                        WHEN TRIM(COALESCE(a.approval_remarks, '')) <> '' THEN a.approval_remarks
                        WHEN a.status = 'ABSENT' THEN 'Marked absent by system'
                        WHEN a.status = 'LATE' THEN 'Marked late by system'
                        ELSE 'Attendance record'
                    END AS reason,
                    a.created_at,
                    a.time_in,
                    a.time_out,
                    a.late_minutes,
                    a.total_hours_worked,
                    a.regular_hours,
                    a.overtime_hours,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    e.department,
                    'attendance' AS source
                  FROM {$this->attendance_table} a
                  JOIN em_employees e ON a.employee_id = e.employee_id
                  WHERE a.attendance_id = :record_id
                  AND a.status IN ('ABSENT', 'LATE')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create absence/late record from attendance
     */
    public function createRecord($attendance_id, $employee_id, $absence_date, $type, $submitted_by = null)
    {
        $query = "INSERT INTO {$this->records_table} 
                  (employee_id, absence_date, type, excuse_status)
                  VALUES (:employee_id, :absence_date, :type, 'PENDING')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':absence_date', $absence_date);
        $stmt->bindParam(':type', $type);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Submit excuse request (by employee)
     */
    public function submitExcuse($record_id, $reason, $supporting_document = null, $employee_id = null)
    {
        $legacyQuery = "UPDATE {$this->records_table} 
                  SET reason = :reason,
                      excuse_status = 'PENDING'
                  WHERE record_id = :record_id";

        if (!is_null($employee_id)) {
            $legacyQuery .= " AND employee_id = :employee_id";
        }

        $stmt = $this->conn->prepare($legacyQuery);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason);

        if (!is_null($employee_id)) {
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            return true;
        }

        $attendanceQuery = "UPDATE {$this->attendance_table}
                            SET approval_remarks = :reason
                            WHERE attendance_id = :record_id";

        if (!is_null($employee_id)) {
            $attendanceQuery .= " AND employee_id = :employee_id";
        }

        $stmt = $this->conn->prepare($attendanceQuery);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason);

        if (!is_null($employee_id)) {
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Approve/Reject excuse (by HR)
     */
    public function reviewExcuse($record_id, $status, $approval_notes, $reviewed_by)
    {
        $legacyQuery = "UPDATE {$this->records_table} 
                  SET excuse_status = :status,
                      approval_notes = :notes,
                      approval_date = NOW()
                  WHERE record_id = :record_id";

        $stmt = $this->conn->prepare($legacyQuery);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $approval_notes);

        if ($stmt->execute()) {
            $record = $this->getRecord($record_id);
            if ($record) {
                $this->updateThresholds($record['employee_id'], $record['absence_date']);
            }
            return true;
        }

        $attendanceQuery = "UPDATE {$this->attendance_table}
                            SET approval_remarks = :notes
                            WHERE attendance_id = :record_id";

        $stmt = $this->conn->prepare($attendanceQuery);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $approval_notes);

        if ($stmt->execute()) {
            $record = $this->getRecord($record_id);
            if ($record) {
                $this->updateThresholds($record['employee_id'], $record['absence_date']);
            }
            return true;
        }

        return false;
    }

    /**
     * Add notes to record
     */
    public function addNotes($record_id, $notes)
    {
        $query = "UPDATE {$this->records_table} 
                  SET notes = :notes
                  WHERE record_id = :record_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes);

        return $stmt->execute();
    }

    /**
     * Get employee absence/late summary
     */
    public function getEmployeeSummary($employee_id, $month_year = null)
    {
        if (is_null($month_year)) {
            $month_year = date('Y-m');
        }

        $query = "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN type = 'ABSENT' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN type = 'LATE' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN excuse_status = 'APPROVED' THEN 1 ELSE 0 END) as excused_count,
                    SUM(CASE WHEN excuse_status != 'APPROVED' THEN 1 ELSE 0 END) as unexcused_count,
                    SUM(CASE WHEN excuse_status = 'PENDING' THEN 1 ELSE 0 END) as pending_count
                  FROM (
                    SELECT record_id, employee_id, absence_date,
                           CAST(type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                           CAST(excuse_status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status
                    FROM {$this->records_table}
                    WHERE employee_id = :employee_id
                    AND DATE_FORMAT(absence_date, '%Y-%m') = :month_year

                    UNION ALL

                    SELECT attendance_id AS record_id, employee_id, attendance_date AS absence_date,
                           CAST(CASE WHEN status = 'ABSENT' THEN 'ABSENT' WHEN status = 'LATE' THEN 'LATE' ELSE 'ABSENT' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                           CAST('PENDING' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status
                    FROM {$this->attendance_table}
                    WHERE employee_id = :employee_id
                    AND status IN ('ABSENT', 'LATE')
                    AND DATE_FORMAT(attendance_date, '%Y-%m') = :month_year
                  ) AS combined";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':month_year', $month_year);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update monthly thresholds
     */
    public function updateThresholds($employee_id, $date)
    {
        $month_year = date('Y-m', strtotime($date));

        $query = "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN type = 'ABSENT' AND excuse_status != 'APPROVED' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN type = 'LATE' AND excuse_status != 'APPROVED' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN type = 'ABSENT' AND excuse_status = 'APPROVED' THEN 1 ELSE 0 END) as excused_absent_count,
                    SUM(CASE WHEN type = 'LATE' AND excuse_status = 'APPROVED' THEN 1 ELSE 0 END) as excused_late_count
                  FROM {$this->records_table}
                  WHERE employee_id = :employee_id
                  AND DATE_FORMAT(absence_date, '%Y-%m') = :month_year";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':month_year', $month_year);
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if record exists
        $check_query = "SELECT threshold_id FROM {$this->thresholds_table} 
                        WHERE employee_id = :employee_id AND month_year = :month_year";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':month_year', $month_year);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update existing
            $update_query = "UPDATE {$this->thresholds_table} 
                            SET absent_count = :absent_count,
                                late_count = :late_count,
                                excused_absent_count = :excused_absent_count,
                                excused_late_count = :excused_late_count
                            WHERE employee_id = :employee_id AND month_year = :month_year";

            $stmt = $this->conn->prepare($update_query);
        } else {
            // Insert new
            $update_query = "INSERT INTO {$this->thresholds_table} 
                            (employee_id, month_year, absent_count, late_count, excused_absent_count, excused_late_count)
                            VALUES (:employee_id, :month_year, :absent_count, :late_count, :excused_absent_count, :excused_late_count)";

            $stmt = $this->conn->prepare($update_query);
        }

        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':month_year', $month_year);
        $stmt->bindParam(':absent_count', $summary['absent_count'], PDO::PARAM_INT);
        $stmt->bindParam(':late_count', $summary['late_count'], PDO::PARAM_INT);
        $stmt->bindParam(':excused_absent_count', $summary['excused_absent_count'], PDO::PARAM_INT);
        $stmt->bindParam(':excused_late_count', $summary['excused_late_count'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Generate report
     */
    public function getReport($filters = [])
    {
        $query = "SELECT * FROM (
                    SELECT 
                        r.record_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        e.employee_id,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST(r.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                        r.absence_date,
                        CAST(r.excuse_status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                        CAST(r.reason AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        r.created_at,
                        CAST('legacy' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->records_table} r
                    JOIN em_employees e ON r.employee_id = e.employee_id
                    WHERE 1=1

                    UNION ALL

                    SELECT 
                        a.attendance_id AS record_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        e.employee_id,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST(CASE WHEN a.status = 'ABSENT' THEN 'ABSENT' WHEN a.status = 'LATE' THEN 'LATE' ELSE 'ABSENT' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                        a.attendance_date AS absence_date,
                        CAST('PENDING' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                        CAST(CASE 
                            WHEN TRIM(COALESCE(a.approval_remarks, '')) <> '' THEN a.approval_remarks
                            WHEN a.status = 'ABSENT' THEN 'Marked absent by system'
                            WHEN a.status = 'LATE' THEN 'Marked late by system'
                            ELSE 'Attendance record'
                        END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        a.created_at,
                        CAST('attendance' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->attendance_table} a
                    JOIN em_employees e ON a.employee_id = e.employee_id
                    WHERE a.status IN ('ABSENT', 'LATE')
                ) AS combined
                WHERE 1=1";

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query .= " AND combined.absence_date BETWEEN :start_date AND :end_date";
        }

        if (!empty($filters['department'])) {
            $query .= " AND combined.department = :department";
        }

        if (!empty($filters['type'])) {
            $query .= " AND combined.type = :type";
        }

        $query .= " ORDER BY combined.absence_date DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $stmt->bindParam(':start_date', $filters['start_date']);
            $stmt->bindParam(':end_date', $filters['end_date']);
        }
        if (!empty($filters['department'])) {
            $stmt->bindParam(':department', $filters['department']);
        }
        if (!empty($filters['type'])) {
            $stmt->bindParam(':type', $filters['type']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats($filters = [])
    {
        $query = "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN type = 'ABSENT' THEN 1 ELSE 0 END) as total_absents,
                    SUM(CASE WHEN type = 'LATE' THEN 1 ELSE 0 END) as total_lates,
                    SUM(CASE WHEN excuse_status = 'PENDING' THEN 1 ELSE 0 END) as pending_reviews,
                    SUM(CASE WHEN excuse_status = 'APPROVED' THEN 1 ELSE 0 END) as approved_excuses,
                    SUM(CASE WHEN excuse_status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_excuses
                  FROM (
                    SELECT record_id,
                           CAST(type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                           CAST(excuse_status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                           absence_date
                    FROM {$this->records_table}
                    WHERE 1=1

                    UNION ALL

                    SELECT attendance_id AS record_id,
                           CAST(CASE WHEN status = 'ABSENT' THEN 'ABSENT' WHEN status = 'LATE' THEN 'LATE' ELSE 'ABSENT' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                           CAST('PENDING' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS excuse_status,
                           attendance_date AS absence_date
                    FROM {$this->attendance_table}
                    WHERE status IN ('ABSENT', 'LATE')
                  ) AS combined
                  WHERE 1=1";

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query .= " AND combined.absence_date BETWEEN :start_date AND :end_date";
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $stmt->bindParam(':start_date', $filters['start_date']);
            $stmt->bindParam(':end_date', $filters['end_date']);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get pending approvals
     */
    public function getPendingApprovals($limit = 20)
    {
        $query = "SELECT * FROM (
                    SELECT 
                        r.record_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        e.employee_id,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST(r.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                        r.absence_date,
                        CAST(r.reason AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        r.created_at,
                        CAST('legacy' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->records_table} r
                    JOIN em_employees e ON r.employee_id = e.employee_id
                    WHERE r.excuse_status = 'PENDING'

                    UNION ALL

                    SELECT 
                        a.attendance_id AS record_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        e.employee_id,
                        CAST(e.department AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS department,
                        CAST(CASE WHEN a.status = 'ABSENT' THEN 'ABSENT' WHEN a.status = 'LATE' THEN 'LATE' ELSE 'ABSENT' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS type,
                        a.attendance_date AS absence_date,
                        CAST(CASE 
                            WHEN TRIM(COALESCE(a.approval_remarks, '')) <> '' THEN a.approval_remarks
                            WHEN a.status = 'ABSENT' THEN 'Marked absent by system'
                            WHEN a.status = 'LATE' THEN 'Marked late by system'
                            ELSE 'Attendance record'
                        END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS reason,
                        a.created_at,
                        CAST('attendance' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS source
                    FROM {$this->attendance_table} a
                    JOIN em_employees e ON a.employee_id = e.employee_id
                    WHERE a.status IN ('ABSENT', 'LATE')
                ) AS combined
                ORDER BY combined.created_at ASC
                LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark absences as excused when leave is approved
     * Called when a leave request is approved
     */
    public function markAbsencesExcusedByLeave($employee_id, $leave_request_id, $start_date, $end_date)
    {
        try {
            $current_date = new DateTime($start_date);
            $end_datetime = new DateTime($end_date);

            // Iterate through each day in the leave period
            while ($current_date <= $end_datetime) {
                $date_str = $current_date->format('Y-m-d');

                // Check if there's an absence or late record for this date
                $check_query = "SELECT record_id FROM {$this->records_table}
                               WHERE employee_id = :employee_id
                               AND absence_date = :absence_date";

                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                $check_stmt->bindParam(':absence_date', $date_str);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    // Record exists, update it
                    $update_query = "UPDATE {$this->records_table}
                                    SET excuse_status = 'APPROVED',
                                        reason = 'Approved Leave',
                                        approval_notes = 'Automatically marked as excused due to approved leave request',
                                        approval_date = NOW()
                                    WHERE employee_id = :employee_id
                                    AND absence_date = :absence_date";

                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                    $update_stmt->bindParam(':absence_date', $date_str);
                    $update_stmt->execute();
                } else {
                    // No record exists, create one for this absent day
                    $insert_query = "INSERT INTO {$this->records_table}
                                    (employee_id, absence_date, type, excuse_status, reason, approval_notes, approval_date)
                                    VALUES (:employee_id, :absence_date, 'ABSENT', 'APPROVED',
                                            'Approved Leave', 'Automatically created from approved leave request', NOW())";

                    $insert_stmt = $this->conn->prepare($insert_query);
                    $insert_stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':absence_date', $date_str);
                    $insert_stmt->execute();
                }

                // Move to next day
                $current_date->modify('+1 day');
            }

            // Update thresholds
            $this->updateThresholds($employee_id, $start_date);

            return true;
        } catch (Exception $e) {
            error_log("Error marking absences as excused: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse excused status when leave is rejected
     * Called when a leave request is rejected
     */
    public function reverseLeaveExcuse($employee_id, $leave_request_id)
    {
        $query = "UPDATE {$this->records_table}
                 SET excuse_status = 'PENDING',
                     approval_notes = 'Leave request was rejected'
                 WHERE employee_id = :employee_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get label/status for display
     */
    public function getExcuseLabel($record)
    {
        if ($record['excuse_status'] !== 'APPROVED') {
            return 'Unexcused';
        }

        return 'Excused - ' . $record['excuse_status'];
    }
}
?>
