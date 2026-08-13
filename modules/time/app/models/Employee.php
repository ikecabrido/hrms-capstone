<?php
/**
 * Employee Model for Time & Attendance System
 * Manages employee data, credentials, and information
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class Employee
{
    private $conn;
    private $table = "em_employees";

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Get employee by user_id
     */
    public function getByUserId($user_id)
    {
        $query = "SELECT e.*, u.username, u.role 
                  FROM " . $this->table . " e
                  JOIN users u ON e.user_id = u.id
                  WHERE e.user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee by employee_id
     */
    public function getById($employee_id)
    {
        $query = "SELECT e.*, u.username, u.role 
                  FROM " . $this->table . " e
                  LEFT JOIN users u ON e.user_id = u.id
                  WHERE e.employee_id = :employee_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active employees, with optional search.
     */
    public function getAll($status = 'active', $limit = 100, $offset = 0, $search = '')
    {
        $query = "SELECT e.*,
                         CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name
                  FROM " . $this->table . " e
                  WHERE e.status = :status";

        if ($search !== '') {
            $query .= " AND (CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search OR e.employee_id LIKE :search)";
        }

        $query .= " ORDER BY full_name LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        if ($search !== '') {
            $searchValue = '%' . $search . '%';
            $stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee full name
     */
    public function getFullName($employee_id)
    {
        $query = "SELECT CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name
                  FROM " . $this->table . " 
                  WHERE employee_id = :employee_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['full_name'] ?? 'Unknown';
    }

    /**
     * Update employee information
     */
    public function update($employee_id, $data)
    {
        $query = "UPDATE " . $this->table . " SET ";
        $fields = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        
        $query .= implode(", ", $fields) . " WHERE employee_id = :employee_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        
        foreach ($data as $key => $value) {
            $stmt->bindParam(':' . $key, $data[$key]);
        }

        return $stmt->execute();
    }

    /**
     * Get employee count
     */
    public function getTotalCount($status = 'active', $search = '')
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE status = :status";

        if ($search !== '') {
            $query .= " AND (CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE :search OR employee_id LIKE :search)";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        if ($search !== '') {
            $searchValue = '%' . $search . '%';
            $stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
