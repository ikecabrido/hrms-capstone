<?php

include_once __DIR__ . '/../../../database/db.php';

class User {
    private $conn;
    private $infoid;
    private $firstname;
    private $lastname;
    private $middlename;
    private $role;
    private $status; 

    public function __construct($pdo = null) {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function userSession() {
        if (isset($_SESSION['employee_id'])) {
            $sql = "SELECT 
                        e.employee_id,
                        e.first_name,
                        e.last_name,
                        e.middle_name,
                        e.department AS department_id,
                        r.role_name AS role,
                        e.status
                    FROM `hrms_employee` e
                    LEFT JOIN `hrms_roles` r ON e.role = r.role_id
                    LEFT JOIN `hrms_department` d ON e.department = d.department_id
                    WHERE e.employee_id = :employee_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':employee_id' => $_SESSION['employee_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null; // No user session found
    }

    public function registerEmployee($employee_id, $department_id, $position_id, $password) {
    $checkStmt = $this->conn->prepare("SELECT user_id FROM user_account WHERE employee_id = :employee_id");
    $checkStmt->execute([':employee_id' => $employee_id]);
    if ($checkStmt->fetchColumn()) {
        return ['success' => false, 'message' => 'Employee already has a user account.'];
    }

    // Get the role tied to this department
    $roleStmt = $this->conn->prepare("SELECT role_id FROM hrms_roles WHERE department = :dept_id LIMIT 1");
    $roleStmt->execute([':dept_id' => $department_id]);
    $role_id = $roleStmt->fetchColumn();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $this->conn->beginTransaction();

        // 1. INSERT into user_account
        $insertStmt = $this->conn->prepare("
            INSERT INTO user_account (password, employee_id, created_at)
            VALUES (:password, :employee_id, NOW())
        ");
        $insertStmt->execute([
            ':password'    => $hashed_password,
            ':employee_id' => $employee_id,
        ]);

        $user_id = $this->conn->lastInsertId();

        // 2. UPDATE sms_employee
            $updateStmt = $this->conn->prepare("
                UPDATE hrms_employee
                SET department = :department_id,
                    position   = :position_id,
                    role       = :role_id,
                    user_id    = :user_id,
                    status     = 'active'
                WHERE employee_id = :employee_id
            ");
            $updateStmt->execute([
                ':department_id' => $department_id,
                ':position_id'   => $position_id,
                ':role_id'       => $role_id ?: null,
                ':user_id'       => $user_id,
                ':employee_id'   => $employee_id,
            ]);

        $this->conn->commit();
        return ['success' => true, 'message' => 'Employee registered successfully.'];

    } catch (Exception $e) {
        $this->conn->rollBack();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
}