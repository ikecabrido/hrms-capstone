<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Training
{
    private $conn;

    private string $courseTable = 'ld_course';
    private string $instructorTable = 'ld_course_instructor';
    private string $courseSkillTable = 'ld_course_skill';
    private string $skillTable = 'ld_skill';
    private string $versionTable = 'ld_course_version';

    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }
    public function allCourse(): array
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT * FROM {$this->courseTable}
             ORDER BY start_date ASC, created_at DESC"
            );

            $stmt->execute();

            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($courses as &$course) {

                $id = $course['id'];

                $stmt = $this->conn->prepare(
                    "SELECT * FROM {$this->instructorTable}
                 WHERE course_id = :id
                 ORDER BY role = 'owner' DESC, id ASC"
                );

                $stmt->execute([':id' => $id]);

                $course['instructors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $this->conn->prepare(
                    "SELECT
                    cs.*,
                    s.name AS skill_name,
                    s.description AS skill_description,
                    s.suggested AS skill_suggested,
                    s.status AS skill_status
                 FROM {$this->courseSkillTable} cs
                 LEFT JOIN {$this->skillTable} s
                    ON s.id = cs.skill_id
                 WHERE cs.course_id = :id
                 ORDER BY cs.id ASC"
                );

                $stmt->execute([':id' => $id]);

                $course['skills'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $this->conn->prepare(
                    "SELECT * FROM {$this->versionTable}
                 WHERE course_id = :id
                 ORDER BY version_number ASC"
                );

                $stmt->execute([':id' => $id]);

                $course['versions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $courses;

        } catch (Exception $e) {
            throw new Exception(
                "Failed to fetch courses: " . $e->getMessage()
            );
        }
    }
}