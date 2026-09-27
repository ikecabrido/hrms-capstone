<?php

class Position {
    private $conn;
    private $position_id;
    private $position_name;  
    private $department_id; 

    public function __construct($pdo = null) {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getAllPositions() {
        $stmt = $this->conn->prepare("SELECT position_id, position_name FROM hrms_position");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositionsByDepartment($department_id) {
    $sql = "SELECT position_id, position_name 
            FROM hrms_position 
            WHERE department = :dept_id
            ORDER BY position_name";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':dept_id', $department_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}