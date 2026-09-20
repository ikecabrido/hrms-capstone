<?php
/**
 * Audit Log Helper for Time & Attendance System
 * Logs all attendance-related actions for compliance and security
 */

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/Helper.php';

class AuditLog
{
    private $conn;
    private $table = "audit_logs";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Log an action to audit trail
     * 
     * @param string $action_type - Type of action (TIME_IN, TIME_OUT, QR_SCAN, APPROVAL, etc.)
     * @param int $user_id - ID of user performing action
     * @param int $employee_id - ID of employee affected
     * @param int $attendance_id - ID of attendance record
     * @param array $details - Additional action details
     * @param string $status - SUCCESS or FAILED
     * @param string $error_message - Error message if failed
     */
    public function log($action_type, $user_id = null, $employee_id = null, $attendance_id = null, $details = [], $status = 'SUCCESS', $error_message = null)
    {
        try {
            $query = "INSERT INTO " . $this->table . "
                      (action_type, user_id, employee_id, attendance_id, action_details, ip_address, user_agent, status, error_message)
                      VALUES (:action_type, :user_id, :employee_id, :attendance_id, :details, :ip_address, :user_agent, :status, :error_message)";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':action_type', $action_type);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':attendance_id', $attendance_id);
            $details_json = json_encode($details);
            $stmt->bindParam(':details', $details_json);
            $ip = Helper::getClientIP();
            $stmt->bindParam(':ip_address', $ip);
            $user_agent = Helper::getUserAgent();
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':error_message', $error_message);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AuditLog Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit logs with optional filtering
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";

        if (!empty($filters['action_type'])) {
            $query .= " AND action_type = :action_type";
        }
        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = :user_id";
        }
        if (!empty($filters['employee_id'])) {
            $query .= " AND employee_id = :employee_id";
        }
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= :date_from";
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= :date_to";
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($filters as $key => $value) {
            $stmt->bindParam(':' . $key, $filters[$key]);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
