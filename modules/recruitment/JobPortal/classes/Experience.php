<?php

require_once __DIR__ . '/../../../../database/db.php';

class Experience
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create(
        int $applicationId,
        ?string $employer,
        ?string $position,
        ?string $startDate,
        ?string $endDate,
        ?string $description
    ) {
        $stmt = $this->db->prepare('
			INSERT INTO rao_experience
				(application_id, company_name, position, start_date, end_date, description)
			VALUES
				(:application_id, :company_name, :position, :start_date, :end_date, :description)
		');

        $stmt->execute([
            ':application_id' => $applicationId,
            ':company_name' => $employer ?: '',
            ':position' => $position ?: '',
            ':start_date' => $startDate ?: null,
            ':end_date' => $endDate ?: null,
            ':description' => $description ?: ''
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByApplicationId(int $applicationId)
    {
        $stmt = $this->db->prepare('
			SELECT *
			FROM rao_experience
			WHERE application_id = ?
			ORDER BY start_date DESC, id DESC
		');
        $stmt->execute([$applicationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteByApplicationId(int $applicationId)
    {
        $stmt = $this->db->prepare('DELETE FROM rao_experience WHERE application_id = ?');
        return $stmt->execute([$applicationId]);
    }
}
