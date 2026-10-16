<?php

require_once __DIR__ . '/../database/db.php';

class Education
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create(int $applicationId, string $school, string $degree, int $yearGraduated)
    {
        $stmt = $this->db->prepare('
			INSERT INTO rao_education
				(application_id, school_name, degree, year_graduated)
			VALUES
				(:application_id, :school_name, :degree, :year_graduated)
		');

        $stmt->execute([
            ':application_id' => $applicationId,
            ':school_name' => $school,
            ':degree' => $degree,
            ':year_graduated' => $yearGraduated
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByApplicationId(int $applicationId)
    {
        $stmt = $this->db->prepare('
			SELECT *
			FROM rao_education
			WHERE application_id = ?
			ORDER BY year_graduated DESC, id DESC
		');
        $stmt->execute([$applicationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteByApplicationId(int $applicationId)
    {
        $stmt = $this->db->prepare('DELETE FROM rao_education WHERE application_id = ?');
        return $stmt->execute([$applicationId]);
    }
}
