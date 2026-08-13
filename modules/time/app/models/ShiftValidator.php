<?php
/**
 * Shift Assignment Validator
 * Validates if employee has shift assigned before time in/out
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class ShiftValidator
{
    private $conn;
    private $shift_assignments_table = "ta_shift_assignments";
    private $shifts_table = "ta_shifts";
    private $employees_table = "em_employee";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
        $this->createTableIfNotExists();
    }

    /**
     * Create ta_shift_assignments table if it doesn't exist
     */
    private function createTableIfNotExists()
    {
        try {
            $createTableQuery = "CREATE TABLE IF NOT EXISTS {$this->shift_assignments_table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                shift_id INT NOT NULL,
                effective_from DATE NOT NULL,
                effective_to DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT,
                FOREIGN KEY (employee_id) REFERENCES {$this->employees_table}(employee_id) ON DELETE CASCADE,
                FOREIGN KEY (shift_id) REFERENCES {$this->shifts_table}(shift_id) ON DELETE RESTRICT,
                UNIQUE KEY unique_assignment (employee_id, shift_id, effective_from),
                INDEX idx_employee (employee_id),
                INDEX idx_dates (effective_from, effective_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conn->exec($createTableQuery);
        } catch (\Exception $e) {
            // Table already exists or other error - continue anyway
            error_log("ShiftValidator table creation warning: " . $e->getMessage());
        }
    }

    /**
     * Check if employee has shift assigned for today
     */
    public function hasShiftAssignedToday($employee_id, $date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            $query = "SELECT sa.shift_id, s.shift_id, s.shift_name, s.start_time, s.end_time
                      FROM {$this->shift_assignments_table} sa
                      JOIN {$this->shifts_table} s ON sa.shift_id = s.shift_id
                      WHERE sa.employee_id = :employee_id
                      AND sa.effective_from <= :date
                      AND (sa.effective_to IS NULL OR sa.effective_to >= :date)
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ShiftValidator::hasShiftAssignedToday error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all employees without shift assignment
     */
    public function getEmployeesWithoutShift()
    {
        try {
            $today = date('Y-m-d');
            
            $query = "SELECT
                        e.employee_id,
                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                        e.department,
                        e.position,
                        e.status
                      FROM {$this->employees_table} e
                      WHERE e.status = 'active'
                      AND NOT EXISTS (
                          SELECT 1 FROM ta_employee_shifts es
                          WHERE es.employee_id = e.employee_id
                            AND es.is_active = 1
                            AND es.effective_from <= :today
                            AND (es.effective_to IS NULL OR es.effective_to >= :today)
                      )
                      ORDER BY full_name";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ShiftValidator::getEmployeesWithoutShift error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of employees without shift
     */
    public function getUnassignedShiftCount()
    {
        try {
            $today = date('Y-m-d');
            
            $query = "SELECT COUNT(DISTINCT e.employee_id) as count
                      FROM {$this->employees_table} e
                      LEFT JOIN ta_employee_shifts es ON e.employee_id = es.employee_id
                        AND es.effective_from <= :today
                        AND (es.effective_to IS NULL OR es.effective_to >= :today)
                        AND es.is_active = 1
                      WHERE e.status = 'active'
                      AND es.employee_shift_id IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            error_log("ShiftValidator::getUnassignedShiftCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all available shifts
     */
    public function getAvailableShifts()
    {
        try {
            $query = "SELECT * FROM {$this->shifts_table}
                      WHERE is_active = 1
                      ORDER BY start_time";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ShiftValidator::getAvailableShifts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign shift to employee
     */
    public function assignShift($employee_id, $shift_id, $effective_from, $effective_to = null)
    {
        try {
            // Check if assignment already exists and overlaps
            $checkQuery = "SELECT id FROM {$this->shift_assignments_table}
                          WHERE employee_id = :employee_id
                          AND shift_id = :shift_id
                          AND effective_from = :effective_from
                          LIMIT 1";

            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':shift_id', $shift_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':effective_from', $effective_from);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Shift assignment already exists'];
            }

            $insertQuery = "INSERT INTO {$this->shift_assignments_table}
                            (employee_id, shift_id, effective_from, effective_to)
                            VALUES (:employee_id, :shift_id, :effective_from, :effective_to)";

            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':shift_id', $shift_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':effective_from', $effective_from);
            $insertStmt->bindParam(':effective_to', $effective_to);

            if ($insertStmt->execute()) {
                return ['success' => true, 'message' => 'Shift assigned successfully'];
            }

            return ['success' => false, 'message' => 'Failed to assign shift'];
        } catch (\Exception $e) {
            error_log("ShiftValidator::assignShift error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>
