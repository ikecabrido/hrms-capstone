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
    private string $courseSkillTable = 'ld_course_skill';
    private string $skillTable = 'ld_skill';
    private string $versionTable = 'ld_course_version';
    private string $employeeTable = 'em_employees';

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

            $skills = $data['skills'] ?? [];

            if (is_array($skills)) {

                foreach ($skills as $skillId) {

                    $skillId = (int) $skillId;

                    if ($skillId <= 0) {
                        continue;
                    }


                    /* ---------------------------------------------
                       Make sure the skill actually exists
                    --------------------------------------------- */

                    $sql = "
                    SELECT id
                    FROM {$this->skillTable}
                    WHERE id = :skill_id
                    LIMIT 1
                ";

                    $stmt = $this->conn->prepare($sql);

                    $stmt->execute([
                        ':skill_id' => $skillId
                    ]);

                    if (!$stmt->fetchColumn()) {
                        throw new Exception(
                            "Skill ID {$skillId} does not exist."
                        );
                    }


                    /* ---------------------------------------------
                       Insert course-skill relationship
                    --------------------------------------------- */

                    $sql = "
                    INSERT INTO {$this->courseSkillTable}
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


            /* =====================================================
               INSERT INITIAL VERSION
            ===================================================== */

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
    public function update(
        int $courseId,
        array $data,
        ?string $newThumbnailPath = null
    ): bool {

        try {

            $this->conn->beginTransaction();

            /* =====================================================
               GET EXISTING COURSE
            ===================================================== */

            $stmt = $this->conn->prepare(
                "SELECT *
             FROM {$this->courseTable}
             WHERE id = :course_id
             LIMIT 1"
            );

            $stmt->execute([
                ':course_id' => $courseId
            ]);

            $existingCourse = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingCourse) {
                throw new Exception('Course not found.');
            }


            /* =====================================================
               BASIC DATA
            ===================================================== */

            $title = trim($data['title'] ?? '');
            $description = trim($data['description'] ?? '');
            $category = trim($data['category'] ?? '');

            $instructorId = (int) ($data['instructor_id'] ?? 0);

            $status = $data['status'] ?? 'draft';

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

            if (
                !in_array(
                    $status,
                    ['draft', 'active', 'archived'],
                    true
                )
            ) {
                throw new Exception('Invalid course status.');
            }


            /* =====================================================
               THUMBNAIL
            ===================================================== */

            $thumbnailPath = $existingCourse['thumbnail_path'] ?? null;

            if ($newThumbnailPath !== null) {
                $thumbnailPath = $newThumbnailPath;
            }


            /* =====================================================
               UPDATE MAIN COURSE
            ===================================================== */

            $stmt = $this->conn->prepare(
                "UPDATE {$this->courseTable}
             SET
                instructor_id = :instructor_id,
                title = :title,
                description = :description,
                thumbnail_path = :thumbnail_path,
                category = :category,
                status = :status,
                start_date = :start_date,
                enrollment_deadline = :enrollment_deadline
             WHERE id = :course_id"
            );

            $stmt->execute([
                ':instructor_id' => $instructorId,
                ':title' => $title,
                ':description' => $description,
                ':thumbnail_path' => $thumbnailPath,
                ':category' => $category,
                ':status' => $status,
                ':start_date' => $startDate,
                ':enrollment_deadline' => $enrollmentDeadline,
                ':course_id' => $courseId
            ]);


/* =====================================================
   UPDATE INSTRUCTORS
===================================================== */

$stmt = $this->conn->prepare(
    "DELETE FROM {$this->instructorTable}
     WHERE course_id = :course_id"
);

$stmt->execute([
    ':course_id' => $courseId
]);


/* =====================================================
   INSERT COURSE OWNER
===================================================== */

$stmt = $this->conn->prepare(
    "INSERT INTO {$this->instructorTable}
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
    )"
);

$stmt->execute([
    ':course_id' => $courseId,
    ':instructor_id' => $instructorId
]);


/* =====================================================
   INSERT CO-INSTRUCTORS
===================================================== */

$coInstructors =
    $data['co_instructors'] ?? [];

if (!is_array($coInstructors)) {
    $coInstructors = [];
}


/*
 * Remove duplicates and owner
 */
$coInstructors = array_unique(
    array_map(
        'intval',
        $coInstructors
    )
);


foreach ($coInstructors as $coInstructorId) {

    if ($coInstructorId <= 0) {
        continue;
    }

    /*
     * Owner cannot also be co-instructor
     */
    if ($coInstructorId === $instructorId) {
        continue;
    }

    $stmt = $this->conn->prepare(
        "INSERT INTO {$this->instructorTable}
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
        )"
    );

    $stmt->execute([
        ':course_id' =>
            $courseId,

        ':instructor_id' =>
            $coInstructorId
    ]);
}

            /* =====================================================
               UPDATE COURSE SKILLS
            ===================================================== */

            $stmt = $this->conn->prepare(
                "DELETE FROM {$this->courseSkillTable}
             WHERE course_id = :course_id"
            );

            $stmt->execute([
                ':course_id' => $courseId
            ]);


            /* =====================================================
               INSERT NEW COURSE SKILLS
            ===================================================== */

            $skills = $data['skills'] ?? [];

            if (is_array($skills)) {

                foreach ($skills as $skillId) {

                    $skillId = (int) $skillId;

                    if ($skillId <= 0) {
                        continue;
                    }

                    $stmt = $this->conn->prepare(
                        "INSERT INTO {$this->courseSkillTable}
                     (
                        course_id,
                        skill_id
                     )
                     VALUES
                     (
                        :course_id,
                        :skill_id
                     )"
                    );

                    $stmt->execute([
                        ':course_id' => $courseId,
                        ':skill_id' => $skillId
                    ]);
                }
            }


            /* =====================================================
               CREATE VERSION SNAPSHOT
            ===================================================== */

            $snapshot = [
                'title' => $title,
                'description' => $description,
                'thumbnail_path' => $thumbnailPath,
                'category' => $category,
                'status' => $status,
                'start_date' => $startDate,
                'enrollment_deadline' => $enrollmentDeadline,

                'instructor_id' => $instructorId,

                'co_instructors' => $coInstructors,

                'skills' => $skills,

                'lessons' => $data['lessons'] ?? []
            ];

            $snapshotJson = json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            if ($snapshotJson === false) {
                throw new Exception(
                    'Failed to create course version snapshot.'
                );
            }


            /* =====================================================
               GET NEXT VERSION NUMBER
            ===================================================== */

            $stmt = $this->conn->prepare(
                "SELECT COALESCE(
                MAX(version_number),
                0
             ) + 1
             FROM {$this->versionTable}
             WHERE course_id = :course_id"
            );

            $stmt->execute([
                ':course_id' => $courseId
            ]);

            $versionNumber = (int) $stmt->fetchColumn();


            /* =====================================================
               INSERT VERSION
            ===================================================== */

            $stmt = $this->conn->prepare(
                "INSERT INTO {$this->versionTable}
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
             )"
            );

            $stmt->execute([
                ':course_id' => $courseId,
                ':version_number' => $versionNumber,
                ':snapshot' => $snapshotJson
            ]);


            /* =====================================================
               COMMIT
            ===================================================== */

            $this->conn->commit();


            /* =====================================================
               DELETE OLD THUMBNAIL
            ===================================================== */

            if (
                $newThumbnailPath !== null &&
                !empty($existingCourse['thumbnail_path']) &&
                $existingCourse['thumbnail_path'] !== $newThumbnailPath
            ) {

                $oldThumbnail =
                    dirname(__DIR__, 2) .
                    '/public/' .
                    ltrim(
                        $existingCourse['thumbnail_path'],
                        '/'
                    );

                if (is_file($oldThumbnail)) {
                    @unlink($oldThumbnail);
                }
            }


            return true;


        } catch (Exception $e) {

            /* =====================================================
               ROLLBACK
            ===================================================== */

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }


            /* =====================================================
               REMOVE NEW THUMBNAIL IF UPDATE FAILED
            ===================================================== */

            if ($newThumbnailPath !== null) {

                $newThumbnail =
                    dirname(__DIR__, 2) .
                    '/public/' .
                    ltrim(
                        $newThumbnailPath,
                        '/'
                    );

                if (is_file($newThumbnail)) {
                    @unlink($newThumbnail);
                }
            }


            throw new Exception(
                "Failed to update course: " . $e->getMessage()
            );
        }
    }
    public function toggleStatus(int $courseId): string
    {
        try {

            $this->conn->beginTransaction();

            $sql = "
            SELECT status
            FROM {$this->courseTable}
            WHERE id = :course_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);

            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                throw new Exception('Course not found.');
            }

            $currentStatus = $course['status'];

            if ($currentStatus === 'draft') {

                $newStatus = 'active';

            } elseif ($currentStatus === 'active') {

                $newStatus = 'draft';

            } else {

                throw new Exception(
                    'Archived courses cannot be toggled.'
                );
            }

            $sql = "
            UPDATE {$this->courseTable}
            SET status = :status
            WHERE id = :course_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':status' => $newStatus,
                ':course_id' => $courseId
            ]);

            $this->conn->commit();

            return $newStatus;

        } catch (Exception $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }
    public function deleteCourse(int $courseId): bool
    {
        try {

            $this->conn->beginTransaction();


            /* =====================================================
               GET COURSE
            ===================================================== */

            $sql = "
            SELECT thumbnail_path
            FROM {$this->courseTable}
            WHERE id = :course_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);

            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                throw new Exception('Course not found.');
            }

            $thumbnailPath = $course['thumbnail_path'] ?? null;


            /* =====================================================
               DELETE COURSE INSTRUCTORS
            ===================================================== */

            $sql = "
            DELETE FROM {$this->instructorTable}
            WHERE course_id = :course_id
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);


            /* =====================================================
               DELETE COURSE SKILLS

               IMPORTANT:
               Delete from ld_course_skill,
               NOT ld_skill.
            ===================================================== */

            $sql = "
            DELETE FROM {$this->courseSkillTable}
            WHERE course_id = :course_id
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);


            /* =====================================================
               DELETE COURSE VERSIONS
            ===================================================== */

            $sql = "
            DELETE FROM {$this->versionTable}
            WHERE course_id = :course_id
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);


            /* =====================================================
               DELETE COURSE
            ===================================================== */

            $sql = "
            DELETE FROM {$this->courseTable}
            WHERE id = :course_id
            LIMIT 1
        ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':course_id' => $courseId
            ]);


            /* =====================================================
               VERIFY DELETE
            ===================================================== */

            if ($stmt->rowCount() === 0) {
                throw new Exception(
                    'Failed to delete course.'
                );
            }


            /* =====================================================
               COMMIT
            ===================================================== */

            $this->conn->commit();


            /* =====================================================
               DELETE THUMBNAIL FILE

               Only delete the file AFTER the database
               transaction has successfully committed.
            ===================================================== */

            if (!empty($thumbnailPath)) {

                $thumbnailFile =
                    dirname(__DIR__, 2) .
                    '/public/' .
                    ltrim($thumbnailPath, '/');

                if (is_file($thumbnailFile)) {
                    @unlink($thumbnailFile);
                }
            }


            return true;


        } catch (Exception $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw new Exception(
                "Failed to delete course: " .
                $e->getMessage()
            );
        }
    }
    public function find(int $id): ?array
    {
        $sql = "SELECT *
            FROM {$this->courseTable}
            WHERE id = :id
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        return $course ?: null;
    }
    public function getInstructors(): array
    {
        $sql = "
        SELECT
            e.employee_id,
            CONCAT(e.first_name, ' ', e.last_name) AS instructor_name
        FROM {$this->employeeTable} AS e
        ORDER BY e.last_name ASC, e.first_name ASC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    public function getSkills(): array
{
    $stmt = $this->conn->prepare("
        SELECT
            id,
            name,
            description,
            suggested,
            status
        FROM {$this->skillTable}
        WHERE status = 'active'
        ORDER BY name ASC
    ");

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}