<?php
/**
 * Shift Model
 * Handles all shift-related database operations
 * 
 * @package Time_and_Attendance
 * @subpackage Models
 */

class Shift {
    private $conn;
    private $table = 'ta_shifts';

    public $shift_id;
    public $shift_name;
    public $start_time;
    public $end_time;
    public $break_duration;
    public $description;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all shifts
     * 
     * @param bool $active_only - Get only active shifts
     * @return array
     */
    public function getAll($active_only = false) {
        $query = "SELECT * FROM " . $this->table;
        
        if ($active_only) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY start_time ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single shift by ID
     * 
     * @param int $shift_id
     * @return array
     */
    public function getById($shift_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE shift_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shift_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new shift
     * 
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (shift_name, start_time, end_time, break_duration, description, is_active)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->shift_name);
        $stmt->bindParam(2, $this->start_time);
        $stmt->bindParam(3, $this->end_time);
        $stmt->bindParam(4, $this->break_duration);
        $stmt->bindParam(5, $this->description);
        $stmt->bindParam(6, $this->is_active);

        return $stmt->execute();
    }

    /**
     * Update shift
     * 
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET shift_name = ?, start_time = ?, end_time = ?, 
                      break_duration = ?, description = ?, is_active = ?
                  WHERE shift_id = ?";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $this->shift_name);
        $stmt->bindParam(2, $this->start_time);
        $stmt->bindParam(3, $this->end_time);
        $stmt->bindParam(4, $this->break_duration);
        $stmt->bindParam(5, $this->description);
        $stmt->bindParam(6, $this->is_active);
        $stmt->bindParam(7, $this->shift_id);

        return $stmt->execute();
    }

    /**
     * Delete shift
     * 
     * @param int $shift_id
     * @return bool
     */
    public function delete($shift_id) {
        $query = "DELETE FROM " . $this->table . " WHERE shift_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $shift_id);

        return $stmt->execute();
    }

    /**
     * Check if shift exists
     * 
     * @param int $shift_id
     * @return bool
     */
    public function exists($shift_id) {
        $result = $this->getById($shift_id);
        return !empty($result);
    }

    /**
     * Get shifts for a specific employee
     * 
     * @param int $employee_id
     * @param string $date - Specific date to check (format: YYYY-MM-DD)
     * @return array
     */
    public function getEmployeeShifts($employee_id, $date = null) {
        $query = "SELECT s.* FROM " . $this->table . " s
                  INNER JOIN employee_shifts es ON s.shift_id = es.shift_id
                  WHERE es.employee_id = ? AND es.is_active = 1";

        if ($date) {
            $query .= " AND es.effective_from <= ? AND (es.effective_to IS NULL OR es.effective_to >= ?)";
        }

        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);

        if ($date) {
            $stmt->execute([$employee_id, $date, $date]);
        } else {
            $stmt->execute([$employee_id]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get current shift for employee
     * 
     * @param int $employee_id
     * @return array
     */
    public function getCurrentShift($employee_id) {
        $today = date('Y-m-d');
        return $this->getEmployeeShifts($employee_id, $today);
    }

    /**
     * Check if time is within shift hours
     * 
     * @param int $employee_id
     * @param string $time - Time to check (format: HH:MM:SS)
     * @param string $date - Date for shift lookup (format: YYYY-MM-DD)
     * @return bool
     */
    public function isWithinShiftHours($employee_id, $time, $date = null) {
        $shift = $this->getEmployeeShifts($employee_id, $date);

        if (empty($shift)) {
            return true; // No shift assigned, allow attendance
        }

        $shift_start = strtotime($shift['start_time']);
        $shift_end = strtotime($shift['end_time']);
        $check_time = strtotime($time);

        // Handle night shifts that cross midnight
        if ($shift_end < $shift_start) {
            return ($check_time >= $shift_start) || ($check_time <= $shift_end);
        }

        return ($check_time >= $shift_start) && ($check_time <= $shift_end);
    }

    /**
     * Get time until shift starts (in minutes)
     * 
     * @param int $employee_id
     * @param string $date - Date for shift lookup (format: YYYY-MM-DD)
     * @return int|null - Minutes until shift starts, or null if no shift
     */
    public function getMinutesUntilShiftStart($employee_id, $date = null) {
        $shift = $this->getEmployeeShifts($employee_id, $date);

        if (empty($shift)) {
            return null;
        }

        $now = new DateTime();
        $shift_start = DateTime::createFromFormat('H:i:s', $shift['start_time']);

        // Handle night shifts
        if ($shift_start < $now) {
            $shift_start->modify('+1 day');
        }

        $interval = $now->diff($shift_start);
        return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    }
}
?>
