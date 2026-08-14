<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BenefitsAndGovernmentContribution
{
    private $conn;
    private $table = "ep_benefits_and_government_contribution";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY uploaded_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getBenefits(int $employee_id): array
    {
        $query = "
        SELECT *
        FROM {$this->table}
        WHERE employee_id = :employee_id
        ORDER BY uploaded_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(array $data): bool
    {
        $sql = "INSERT INTO ep_benefits_and_government_contribution
            (
                employee_id,
                record_type,
                period,
                description,
                file_name,
                file_path,
                uploaded_by
            )
            VALUES
            (
                :employee_id,
                :record_type,
                :period,
                :description,
                :file_name,
                :file_path,
                :uploaded_by
            )";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':record_type' => $data['record_type'],
            ':period' => $data['period'],
            ':description' => $data['description'],
            ':file_name' => $data['file_name'],
            ':file_path' => $data['file_path'],
            ':uploaded_by' => $data['uploaded_by']
        ]);
    }

}