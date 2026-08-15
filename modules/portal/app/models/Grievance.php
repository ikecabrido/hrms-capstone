<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Grievance
{
    private $conn;

    private string $table = 'eer_grievances';

    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }

    public function all()
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getGrievance(int $employee_id): array
    {
        $query = "
            SELECT * FROM {$this->table} 
            WHERE employee_id = :employee_id
            ORDER BY created_at DESC ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(array $data): bool
    {
        try {

            $sql = "
            INSERT INTO {$this->table} (
                employee_id,
                subject,
                description,
                status,
                priority,
                category,
                anonymous,
                attachment_path,
                confidential,
                created_at,
                updated_at
            )
            VALUES (
                :employee_id,
                :subject,
                :description,
                :status,
                :priority,
                :category,
                :anonymous,
                :attachment_path,
                :confidential,
                NOW(),
                NOW()
            )
        ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([

                ':employee_id' =>
                    $data['employee_id'],

                ':subject' =>
                    $data['subject'],

                ':description' =>
                    $data['description'],

                ':status' =>
                    $data['status'] ?? 'pending',

                ':priority' =>
                    $data['priority'] ?? 'low',

                ':category' =>
                    $data['category'],

                ':anonymous' =>
                    $data['anonymous'] ?? 0,

                ':attachment_path' =>
                    $data['attachment_path'] ?? null,

                ':confidential' =>
                    $data['confidential'] ?? 1
            ]);

        } catch (\Throwable $e) {

            error_log(
                'Grievance model create error: ' .
                $e->getMessage()
            );

            throw $e;
        }
    }
}