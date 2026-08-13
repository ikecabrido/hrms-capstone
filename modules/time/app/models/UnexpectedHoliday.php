<?php
/**
 * Unexpected Holiday Management Model
 * Handles adding crisis/emergency/unexpected holidays
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class UnexpectedHoliday
{
    private $conn;
    private $table = "ta_unexpected_holidays";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Create unexpected holidays table if it doesn't exist
     */
    public static function createTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS ta_unexpected_holidays (
            id INT PRIMARY KEY AUTO_INCREMENT,
            holiday_date DATE NOT NULL UNIQUE,
            holiday_name VARCHAR(255) NOT NULL,
            reason VARCHAR(255),
            description TEXT,
            holiday_type ENUM('CRISIS', 'EMERGENCY', 'STATE', 'MUNICIPAL', 'COMPANY', 'OTHER') DEFAULT 'OTHER',
            status ENUM('ACTIVE', 'CANCELLED') DEFAULT 'ACTIVE',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date (holiday_date),
            INDEX idx_status (status),
            FOREIGN KEY (created_by) REFERENCES hrms_employee(employee_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $conn->exec($sql);
    }

    /**
     * Add unexpected holiday
     */
    public function addHoliday($data)
    {
        // Check if holiday already exists
        $checkQuery = "SELECT id FROM {$this->table}
                      WHERE holiday_date = :date
                      LIMIT 1";
        
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':date', $data['holiday_date']);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Holiday already exists on this date'];
        }

        $query = "INSERT INTO {$this->table}
                  (holiday_date, holiday_name, reason, description, holiday_type, status, created_by)
                  VALUES (:date, :name, :reason, :description, :type, 'ACTIVE', :created_by)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $data['holiday_date']);
        $stmt->bindParam(':name', $data['holiday_name']);
        $stmt->bindParam(':reason', $data['reason'] ?? null);
        $stmt->bindParam(':description', $data['description'] ?? null);
        $stmt->bindParam(':type', $data['holiday_type'] ?? 'OTHER');
        $stmt->bindParam(':created_by', $data['created_by'] ?? null);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Holiday added successfully', 'id' => $this->conn->lastInsertId()];
        }

        return ['success' => false, 'message' => 'Failed to add holiday'];
    }

    /**
     * Get all unexpected holidays
     */
    public function getHolidays($status = 'ACTIVE', $from_date = null, $to_date = null)
    {
        $query = "SELECT * FROM {$this->table}
                  WHERE status = :status";

        if ($from_date && $to_date) {
            $query .= " AND holiday_date BETWEEN :from_date AND :to_date";
        }

        $query .= " ORDER BY holiday_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);

        if ($from_date && $to_date) {
            $stmt->bindParam(':from_date', $from_date);
            $stmt->bindParam(':to_date', $to_date);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get holiday by date
     */
    public function getHolidayByDate($date)
    {
        $query = "SELECT * FROM {$this->table}
                  WHERE holiday_date = :date
                  AND status = 'ACTIVE'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update holiday
     */
    public function updateHoliday($id, $data)
    {
        $query = "UPDATE {$this->table}
                  SET holiday_date = :date,
                      holiday_name = :name,
                      reason = :reason,
                      description = :description,
                      holiday_type = :type
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':date', $data['holiday_date']);
        $stmt->bindParam(':name', $data['holiday_name']);
        $stmt->bindParam(':reason', $data['reason'] ?? null);
        $stmt->bindParam(':description', $data['description'] ?? null);
        $stmt->bindParam(':type', $data['holiday_type'] ?? 'OTHER');

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Holiday updated successfully'];
        }

        return ['success' => false, 'message' => 'Failed to update holiday'];
    }

    /**
     * Cancel/Disable holiday
     */
    public function cancelHoliday($id)
    {
        $query = "UPDATE {$this->table}
                  SET status = 'CANCELLED'
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Holiday cancelled successfully'];
        }

        return ['success' => false, 'message' => 'Failed to cancel holiday'];
    }

    /**
     * Delete holiday
     */
    public function deleteHoliday($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Holiday deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete holiday'];
    }

    /**
     * Check if date is an unexpected holiday
     */
    public function isUnexpectedHoliday($date)
    {
        return $this->getHolidayByDate($date) !== false;
    }
}
?>
