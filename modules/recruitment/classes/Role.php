<?php

class Role {
    private $conn;
    private $roleid;
    private $rolename;

    public function __construct($pdo = null) {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getRoles() {
        $sql = "SELECT role_id, role_name FROM hrms_roles";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRolesByDepartment($departmentId) {
        $sql = "SELECT role_id, role_name FROM hrms_roles WHERE department = :department_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug logging
        error_log("Department ID: " . $departmentId);
        error_log("Found roles: " . print_r($result, true));
        
        return $result;
    }
    
}