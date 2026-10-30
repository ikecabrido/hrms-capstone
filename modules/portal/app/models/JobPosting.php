<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class JobPosting
{
    private $conn;
    private string $table = 'ep_job_posting';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function all()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|false
    {
        $query = "INSERT INTO {$this->table}
            (
                comp_name,
                comp_logo,
                comp_email,
                comp_number,
                comp_address,
                open_positions,
                qualifications,
                benefits
            )
            VALUES
            (
                :comp_name,
                :comp_logo,
                :comp_email,
                :comp_number,
                :comp_address,
                :open_positions,
                :qualifications,
                :benefits
            )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':comp_name', $data['comp_name']);
        $stmt->bindValue(':comp_logo', $data['comp_logo']);
        $stmt->bindValue(':comp_email', $data['comp_email']);
        $stmt->bindValue(':comp_number', $data['comp_number']);
        $stmt->bindValue(':comp_address', $data['comp_address']);
        $stmt->bindValue(':open_positions', $data['open_positions']);
        $stmt->bindValue(':qualifications', $data['qualifications']);
        $stmt->bindValue(':benefits', $data['benefits']);

        if (!$stmt->execute()) {
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }
}
