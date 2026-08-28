<?php

namespace App\Models;

use Exception;
use App\Config\Database;
use PDO;

class Course
{
    private $conn;

    private string $courseTable = 'ld_course';
    private string $instructorTable = 'ld_course_instructor';
    private string $skillTable = 'ld_course_skill';
    private string $versionTable = 'ld_course_version';

    public function __construct()
    {
        $database = new Database;
        $this->conn = $database->getConnection();
    }

    public function store(array $data): int
    {
        try {

            $this->conn->beginTransaction();


            /* =====================================================
               BASIC COURSE DATA
            ===================================================== */

            $title = trim($data['title'] ?? '');
            $description = trim($data['description'] ?? '');
            $category = trim($data['category'] ?? '');

            $instructorId = (int) ($data['instructor_id'] ?? 0);

            $thumbnailPath = !empty($data['thumbnail_path'])
                ? trim($data['thumbnail_path'])
                : null;

            $startDate = !empty($data['start_date'])
                ? $data['start_date']
                : null;

            $enrollmentDeadline = !empty($data['enrollment_deadline'])
                ? $data['enrollment_deadline']
                : null;


            /* =====================================================
               VALIDATION
            ===================================================== */

            if ($title === '') {
                throw new Exception('Course title is required.');
            }

            if ($category === '') {
                throw new Exception('Course category is required.');
            }

            if ($description === '') {
                throw new Exception('Course description is required.');
            }

            if ($instructorId <= 0) {
                throw new Exception('Course owner is required.');
            }


            /* =====================================================
               INSERT COURSE
            ===================================================== */

            $sql = "
            INSERT INTO {$this->courseTable}
            (
                instructor_id,
                title,
                description,
                thumbnail_path,
                category,
                status,
                start_date,
                enrollment_deadline
            )
            VALUES
            (
                :instructor_id,
                :title,
                :description,
                :thumbnail_path,
                :category,
                :status,
                :start_date,
                :enrollment_deadline
            )
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':instructor_id' => $instructorId,
                ':title' => $title,
                ':description' => $description,

                // IMPORTANT
                ':thumbnail_path' => $thumbnailPath,

                ':category' => $category,
                ':status' => 'draft',
                ':start_date' => $startDate,
                ':enrollment_deadline' => $enrollmentDeadline
            ]);


            /* =====================================================
               GET COURSE ID
            ===================================================== */

            $courseId = (int) $this->conn->lastInsertId();

            if ($courseId <= 0) {
                throw new Exception('Failed to create course.');
            }


            /* =====================================================
               COURSE OWNER
            ===================================================== */

            $sql = "
            INSERT INTO {$this->instructorTable}
            (
                course_id,
                instructor_id,
                role
            )
            VALUES
            (
                :course_id,
                :instructor_id,
                'owner'
            )
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId,
                ':instructor_id' => $instructorId
            ]);


            /* =====================================================
               CO-INSTRUCTORS
            ===================================================== */

            $coInstructors = $data['co_instructors'] ?? [];

            if (is_array($coInstructors)) {

                foreach ($coInstructors as $coInstructorId) {

                    $coInstructorId = (int) $coInstructorId;

                    if ($coInstructorId <= 0) {
                        continue;
                    }

                    if ($coInstructorId === $instructorId) {
                        continue;
                    }

                    $sql = "
                    INSERT INTO {$this->instructorTable}
                    (
                        course_id,
                        instructor_id,
                        role
                    )
                    VALUES
                    (
                        :course_id,
                        :instructor_id,
                        'co-instructor'
                    )
                ";

                    $stmt = $this->conn->prepare($sql);

                    $stmt->execute([
                        ':course_id' => $courseId,
                        ':instructor_id' => $coInstructorId
                    ]);
                }
            }


            /* =====================================================
               COURSE SKILLS
            ===================================================== */

            $skills = $data['skills'] ?? [];

            if (is_array($skills)) {

                foreach ($skills as $skillId) {

                    $skillId = (int) $skillId;

                    if ($skillId <= 0) {
                        continue;
                    }

                    $sql = "
                    INSERT INTO {$this->skillTable}
                    (
                        course_id,
                        skill_id
                    )
                    VALUES
                    (
                        :course_id,
                        :skill_id
                    )
                ";

                    $stmt = $this->conn->prepare($sql);

                    $stmt->execute([
                        ':course_id' => $courseId,
                        ':skill_id' => $skillId
                    ]);
                }
            }


            /* =====================================================
               COURSE VERSION
            ===================================================== */

            $snapshot = [
                'title' => $title,
                'description' => $description,
                'thumbnail_path' => $thumbnailPath,
                'category' => $category,
                'start_date' => $startDate,
                'enrollment_deadline' => $enrollmentDeadline,

                'instructor_id' => $instructorId,

                'co_instructors' => $coInstructors,

                'skills' => $skills,

                'lessons' => $data['lessons'] ?? []
            ];

            $snapshotJson = json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($snapshotJson === false) {
                throw new Exception(
                    'Failed to create course version snapshot.'
                );
            }


            $sql = "
            INSERT INTO {$this->versionTable}
            (
                course_id,
                version_number,
                snapshot
            )
            VALUES
            (
                :course_id,
                :version_number,
                :snapshot
            )
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId,
                ':version_number' => 1,
                ':snapshot' => $snapshotJson
            ]);


            /* =====================================================
               COMMIT
            ===================================================== */

            $this->conn->commit();

            return $courseId;


        } catch (Exception $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }
}