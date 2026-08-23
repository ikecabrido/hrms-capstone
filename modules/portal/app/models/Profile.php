<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Profile
{
    private $conn;
    private $table = "ep_users";
    private $employeesTable = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function findByEmail($email)
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function findById($id)
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "
        UPDATE {$this->table}
        SET password = :password
        WHERE id = :id
        LIMIT 1
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }
    public function updateProfile(int $userId, array $data): bool
    {
        $sql = "UPDATE {$this->employeesTable} SET
        first_name = :first_name,
        middle_name = :middle_name,
        last_name = :last_name,
        suffix = :suffix,
        gender = :gender,
        birth_date = :birth_date,
        civil_status = :civil_status,
        mobile_no = :mobile_no,
        current_address = :current_address
        WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':first_name' => $data['first_name'] ?? '',
            ':middle_name' => $data['middle_name'] ?? '',
            ':last_name' => $data['last_name'] ?? '',
            ':suffix' => $data['suffix'] ?? '',
            ':gender' => $data['gender'] ?? '',
            ':birth_date' => !empty($data['birth_date'])
                ? $data['birth_date']
                : null,
            ':civil_status' => $data['civil_status'] ?? '',
            ':mobile_no' => $data['mobile_no'] ?? '',
            ':current_address' => $data['current_address'] ?? '',
            ':user_id' => $userId
        ]);
    }
    public function updateEmail(int $userId, string $email): bool
    {
        $sql = "UPDATE {$this->table}
            SET email = :email
            WHERE id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
    }
    public function updateProfileImage(int $userId, string $profileImage): bool
    {
        $sql = "UPDATE {$this->employeesTable}
            SET profile_image = :profile_image
            WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':profile_image' => $profileImage,
            ':user_id' => $userId
        ]);
    }
}