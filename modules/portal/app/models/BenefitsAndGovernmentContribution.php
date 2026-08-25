<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BenefitsAndGovernmentContribution
{
    private $conn;
    private $table = "ep_benefits_and_government_contribution";
    private $employeeTable = "em_employees";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function all(): array
    {
        $sql = "
        SELECT
            b.*,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM {$this->table} b
        INNER JOIN {$this->employeeTable} e
            ON b.employee_id = e.employee_id
        ORDER BY b.uploaded_at DESC
    ";

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
    public function updateFile(
        int $benefitId,
        string $fileName,
        string $filePath,
        int $uploadedBy
    ): bool {
        $sql = "
        UPDATE {$this->table}
        SET
            file_name = :file_name,
            file_path = :file_path,
            uploaded_by = :uploaded_by,
            updated_at = NOW()
        WHERE benefit_id = :benefit_id
    ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':file_name' => $fileName,
            ':file_path' => $filePath,
            ':uploaded_by' => $uploadedBy,
            ':benefit_id' => $benefitId
        ]);
    }


}