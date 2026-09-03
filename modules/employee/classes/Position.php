<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * Position
 *
 * Wraps read operations against `em_positions`.
 * Columns (verified): position_id, position_name, slot_count, department_id,
 * status, created_at, updated_at
 */
class Position
{
    private $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getAllPositions($activeOnly = true)
    {
        $sql = "SELECT position_id, position_name, slot_count, department_id, status
                FROM em_positions";
        if ($activeOnly) {
            $sql .= " WHERE status = 'Active'";
        }
        $sql .= " ORDER BY position_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositionsByDepartment($departmentId, $activeOnly = true)
    {
        $sql = "SELECT position_id, position_name, slot_count, department_id, status
                FROM em_positions
                WHERE department_id = :department_id";
        if ($activeOnly) {
            $sql .= " AND status = 'Active'";
        }
        $sql .= " ORDER BY position_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_id', $departmentId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositionById($positionId)
    {
        $stmt = $this->conn->prepare(
            "SELECT position_id, position_name, slot_count, department_id, status
             FROM em_positions WHERE position_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $positionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Number of currently-filled slots (active, non-archived employees) for a position.
     */
    public function countFilledSlots($positionId)
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM em_employees
             WHERE position_id = :position_id AND is_archived = 0"
        );
        $stmt->execute([':position_id' => $positionId]);
        return (int) $stmt->fetchColumn();
    }
}
