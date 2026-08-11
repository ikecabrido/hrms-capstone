<?php

namespace App\Core;

use App\Config\Database;
use PDO;
use PDOException;

class AuditLog
{
    private PDO $conn;
    private string $table = 'audit_logs';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Log an action to the audit trail.
     *
     * @param string      $action_type
     * @param int|null    $user_id
     * @param int|null    $employee_id
     * @param int|null    $attendance_id
     * @param array       $details
     * @param string      $status
     * @param string|null $error_message
     */
    public function log(
        string $action_type,
        ?int $user_id = null,
        ?int $employee_id = null,
        ?int $attendance_id = null,
        array $details = [],
        string $status = 'SUCCESS',
        ?string $error_message = null
    ): bool {
        try {
            $query = "
                INSERT INTO {$this->table}
                (
                    action_type,
                    user_id,
                    employee_id,
                    attendance_id,
                    action_details,
                    ip_address,
                    user_agent,
                    status,
                    error_message
                )
                VALUES
                (
                    :action_type,
                    :user_id,
                    :employee_id,
                    :attendance_id,
                    :details,
                    :ip_address,
                    :user_agent,
                    :status,
                    :error_message
                )
            ";

            $stmt = $this->conn->prepare($query);

            $ip = Helper::getClientIP();
            $userAgent = Helper::getUserAgent();
            $detailsJson = json_encode($details);

            $stmt->bindValue(':action_type', $action_type);
            $stmt->bindValue(':user_id', $user_id, $user_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':employee_id', $employee_id, $employee_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':attendance_id', $attendance_id, $attendance_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':details', $detailsJson);
            $stmt->bindValue(':ip_address', $ip);
            $stmt->bindValue(':user_agent', $userAgent);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':error_message', $error_message);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('AuditLog Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit logs with optional filtering.
     *
     * @param array $filters
     * @param int   $limit
     * @param int   $offset
     *
     * @return array
     */
    public function getLogs(
        array $filters = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $query = "
            SELECT *
            FROM {$this->table}
            WHERE 1 = 1
        ";

        $params = [];

        if (!empty($filters['action_type'])) {
            $query .= " AND action_type = :action_type";
            $params[':action_type'] = $filters['action_type'];
        }

        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['employee_id'])) {
            $query .= " AND employee_id = :employee_id";
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $query .= "
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
