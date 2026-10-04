<?php
/**
 * EmployeeShift Model
 * Handles employee-to-shift assignment operations
 * 
 * @package Time_and_Attendance
 * @subpackage Models
 */

class EmployeeShift {
    private $conn;
    private $table = 'ta_employee_shifts';

    public $employee_shift_id;
    public $employee_id;
    public $shift_id;
    public $effective_from;
    public $effective_to;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Assign shift to employee
     * 
     * @return bool
     */
    public function assign() {
        // First, deactivate any active shifts for this employee
        $this->deactivateOtherShifts($this->employee_id);

        $query = "INSERT INTO " . $this->table . "
                  (employee_id, shift_id, effective_from, effective_to, is_active)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->employee_id);
        $stmt->bindParam(2, $this->shift_id);
        $stmt->bindParam(3, $this->effective_from);
        $stmt->bindParam(4, $this->effective_to);
        $stmt->bindParam(5, $this->is_active);

        return $stmt->execute();
    }

    /**
     * Update employee shift assignment
     * 
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET shift_id = ?, effective_from = ?, effective_to = ?, is_active = ?
                  WHERE employee_shift_id = ?";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->shift_id);
        $stmt->bindParam(2, $this->effective_from);
        $stmt->bindParam(3, $this->effective_to);
        $stmt->bindParam(4, $this->is_active);
        $stmt->bindParam(5, $this->employee_shift_id);

        return $stmt->execute();
    }

    /**
     * Get shift assignment for employee
     * 
     * @param int $employee_id
     * @param bool $active_only
     * @return array
     */
    public function getEmployeeAssignments($employee_id, $active_only = true) {
        $query = "SELECT es.*, s.shift_name, s.start_time, s.end_time, 
                         CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name
                  FROM " . $this->table . " es
                  INNER JOIN ta_shifts s ON es.shift_id = s.shift_id
                  INNER JOIN em_employees e ON es.employee_id = e.employee_id
                  WHERE es.employee_id = ?";

        if ($active_only) {
            $query .= " AND es.is_active = 1";
        }

        $query .= " ORDER BY es.effective_from DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$employee_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get current shift for employee
     * 
     * @param int $employee_id
     * @return array
     */
    public function getCurrentShift($employee_id) {
        $query = "SELECT es.*, s.shift_name, s.start_time, s.end_time, s.break_duration
                  FROM " . $this->table . " es
                  INNER JOIN ta_shifts s ON es.shift_id = s.shift_id
                  WHERE es.employee_id = ? AND es.is_active = 1 
                  AND es.effective_from <= CURDATE()
                  AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$employee_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get active flexible schedule for employee
     *
     * @param int $employee_id
     * @return array|null
     */
    public function getActiveFlexibleSchedule($employee_id, $date = null) {
        $date = $date ?? date('Y-m-d');
        $dayOfWeek = date('w', strtotime($date));

        $query = "SELECT *
                  FROM ta_flexible_schedules fs
                  WHERE fs.employee_id = ?
                  AND (
                      fs.schedule_date = ?
                      OR (
                          fs.day_of_week IS NOT NULL
                          AND fs.day_of_week = ?
                          AND (fs.repeat_until IS NULL OR fs.repeat_until >= ?)
                          AND (fs.contract_end_date IS NULL OR fs.contract_end_date >= ?)
                      )
                  )
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$employee_id, $date, $dayOfWeek, $date, $date]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all employees with their current shifts
     * 
     * @param int $shift_id - Filter by specific shift (optional)
     * @return array
     */
    public function getAllAssignments($shift_id = null) {
        $query = "SELECT es.*, s.shift_name, s.start_time, s.end_time,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                    COALESCE(d.department_name, '') AS department,
                    COALESCE(p.position_name, '') AS position
                FROM " . $this->table . " es
                INNER JOIN ta_shifts s ON es.shift_id = s.shift_id
                INNER JOIN em_employees e ON es.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                LEFT JOIN em_positions p ON e.position_id = p.position_id
                WHERE es.is_active = 1";

        if ($shift_id) {
            $query .= " AND es.shift_id = ?";
        }

        $query .= " ORDER BY s.shift_name, full_name";

        $stmt = $this->conn->prepare($query);

        if ($shift_id) {
            $stmt->execute([$shift_id]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of employees on a specific shift
     * 
     * @param int $shift_id
     * @return int
     */
    public function getShiftEmployeeCount($shift_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . "
                  WHERE shift_id = ? AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shift_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Deactivate other shift assignments for employee
     * 
     * @param int $employee_id
     * @return bool
     */
    private function deactivateOtherShifts($employee_id) {
        $query = "UPDATE " . $this->table . "
                  SET is_active = 0
                  WHERE employee_id = ? AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$employee_id]);
    }

    /**
     * Remove shift assignment
     * 
     * @param int $employee_shift_id
     * @return bool
     */
    public function remove($employee_shift_id) {
        $query = "DELETE FROM " . $this->table . " WHERE employee_shift_id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$employee_shift_id]);
    }

    /**
     * Check if employee has shift assigned
     * 
     * @param int $employee_id
     * @return bool
     */
    public function hasActiveShift($employee_id) {
        if ($this->getCurrentShift($employee_id)) {
            return true;
        }

        return !empty($this->getActiveFlexibleSchedule($employee_id));
    }

    /**
     * Get employees without active shift assignments
     * 
     * @return array
     */
    public function getEmployeesWithoutShift() {
        $today = date('Y-m-d');
        $dayOfWeek = date('w', strtotime($today));

                $query = "SELECT DISTINCT e.employee_id,
                                                 CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                                                 COALESCE(d.department_name, '') AS department,
                                                 COALESCE(p.position_name, '') AS position
                                    FROM em_employees e
                                    LEFT JOIN em_departments d ON e.department_id = d.department_id
                                    LEFT JOIN em_positions p ON e.position_id = p.position_id
                  LEFT JOIN " . $this->table . " es ON e.employee_id = es.employee_id
                    AND es.is_active = 1
                    AND es.effective_from <= :today
                    AND (es.effective_to IS NULL OR es.effective_to >= :today)
                  LEFT JOIN ta_flexible_schedules fs ON e.employee_id = fs.employee_id
                    AND (
                        fs.schedule_date = :today
                        OR (
                            fs.day_of_week IS NOT NULL
                            AND fs.day_of_week = :day_of_week
                            AND (fs.repeat_until IS NULL OR fs.repeat_until >= :today)
                            AND (fs.contract_end_date IS NULL OR fs.contract_end_date >= :today)
                        )
                    )
                  WHERE LOWER(e.employment_status) = 'active'
                    AND es.employee_shift_id IS NULL
                    AND fs.id IS NULL
                  ORDER BY full_name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':day_of_week', $dayOfWeek, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of employees without shift assignment
     * 
     * @return int
     */
    public function getEmployeesWithoutShiftCount() {
        $employees = $this->getEmployeesWithoutShift();
        return count($employees);
    }

    /**
     * Get employees flagged as near-termination risk because they missed assigned shifts
     * recently. This is a lightweight warning list for HR review.
     *
     * @param int $daysBack
     * @param int $limit
     * @return array
     */
    public function getEmployeesNearTermination($daysBack = 7, $limit = 10) {
        $query = "SELECT
                    e.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                                        COALESCE(d.department_name, '') AS department,
                                        COALESCE(p.position_name, '') AS position,
                    COUNT(a.attendance_id) AS missed_shift_days,
                    MAX(a.attendance_date) AS last_missed_shift
                  FROM em_employees e
                                    INNER JOIN " . $this->table . " es ON e.employee_id = es.employee_id
                    AND es.is_active = 1
                    AND es.effective_from <= CURDATE()
                    AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
                                    LEFT JOIN em_departments d ON e.department_id = d.department_id
                                    LEFT JOIN em_positions p ON e.position_id = p.position_id
                  LEFT JOIN ta_attendance a ON a.employee_id = e.employee_id
                    AND a.status = 'ABSENT'
                    AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days_back DAY)
                  WHERE LOWER(e.employment_status) = 'active'
                                    GROUP BY e.employee_id, e.first_name, e.last_name, d.department_name, p.position_name
                  HAVING COUNT(a.attendance_id) >= 1
                  ORDER BY missed_shift_days DESC, last_missed_shift DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days_back', $daysBack, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
