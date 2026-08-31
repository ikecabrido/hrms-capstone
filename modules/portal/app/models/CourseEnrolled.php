<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class CourseEnrolled
{
    private $conn;

    private $enrollmentTable = "ld_enrollment";
    private $courseTable = "ld_course";
    private $moduleTable = "ld_module";
    private $lessonTable = "ld_lesson";
    private $lessonFileTable = "ld_lesson_file";
    private $progressTable = "ld_progress";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function hasEnrollment(int $employeeId): bool
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 1
                FROM {$this->enrollmentTable}
                WHERE learner_id = :employee_id
                AND status IN (
                    'invited',
                    'enrolled',
                    'in_progress'
                )
                LIMIT 1
            ");

            $stmt->execute([
                ':employee_id' => $employeeId
            ]);

            return $stmt->fetchColumn() !== false;

        } catch (Exception $e) {
            throw new Exception(
                "Failed to check employee enrollment: " .
                $e->getMessage()
            );
        }
    }
    public function enroll(
        int $employeeId,
        int $courseId
    ): bool {
        try {

            if (
                $this->hasEnrollmentForCourse(
                    $employeeId,
                    $courseId
                )
            ) {
                return false;
            }

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->enrollmentTable}
                (
                    learner_id,
                    course_id,
                    status,
                    enrolled_at
                )
                VALUES
                (
                    :learner_id,
                    :course_id,
                    'enrolled',
                    NOW()
                )
            ");

            return $stmt->execute([
                ':learner_id' => $employeeId,
                ':course_id' => $courseId
            ]);

        } catch (Exception $e) {
            throw new Exception(
                "Failed to enroll employee: " .
                $e->getMessage()
            );
        }
    }
    public function hasEnrollmentForCourse(
        int $employeeId,
        int $courseId
    ): bool {
        try {

            $stmt = $this->conn->prepare("
                SELECT 1
                FROM {$this->enrollmentTable}
                WHERE learner_id = :learner_id
                AND course_id = :course_id
                AND status IN (
                    'invited',
                    'enrolled',
                    'in_progress'
                )
                LIMIT 1
            ");

            $stmt->execute([
                ':learner_id' => $employeeId,
                ':course_id' => $courseId
            ]);

            return $stmt->fetchColumn() !== false;

        } catch (Exception $e) {
            throw new Exception(
                "Failed to check course enrollment: " .
                $e->getMessage()
            );
        }
    }

    public function getAllEnrolledCoursesWithContent(
        int $employeeId
    ): array {
        try {

            $courses = $this->getEnrolledCourses(
                $employeeId
            );

            foreach ($courses as &$course) {

                $course['modules'] =
                    $this->getCourseModules(
                        (int) $course['course_id'],
                        (int) $course['enrollment_id']
                    );

                $totalLessons = 0;
                $completedLessons = 0;

                foreach ($course['modules'] as $module) {

                    foreach (
                        $module['lessons']
                        as $lesson
                    ) {

                        $totalLessons++;

                        if (
                            !empty(
                            $lesson['completed']
                        )
                        ) {
                            $completedLessons++;
                        }
                    }
                }

                $course['total_modules'] =
                    count($course['modules']);

                $course['total_lessons'] =
                    $totalLessons;

                $course['completed_lessons'] =
                    $completedLessons;

                $course['remaining_lessons'] =
                    max(
                        0,
                        $totalLessons -
                        $completedLessons
                    );

                $course['progress'] =
                    $totalLessons > 0
                    ? (int) round(
                        (
                            $completedLessons /
                            $totalLessons
                        ) * 100
                    )
                    : 0;
            }

            unset($course);

            return $courses;

        } catch (Exception $e) {
            throw new Exception(
                "Failed to fetch enrolled courses " .
                "with content: " .
                $e->getMessage()
            );
        }
    }
    public function updateLastAccessed(
        int $enrollmentId
    ): bool {
        try {

            $stmt = $this->conn->prepare("
                UPDATE {$this->enrollmentTable}

                SET last_accessed_at = NOW()

                WHERE id = :enrollment_id
            ");

            return $stmt->execute([
                ':enrollment_id' => $enrollmentId
            ]);

        } catch (Exception $e) {
            throw new Exception(
                "Failed to update enrollment access time: " .
                $e->getMessage()
            );
        }
    }
    public function getEnrolledCourses(int $employeeId): array
    {
        try {
            $stmt = $this->conn->prepare("
            SELECT
                e.id AS enrollment_id,
                e.learner_id,
                e.course_id,
                e.course_version_id,
                e.status AS enrollment_status,
                e.enrolled_at,
                e.completed_at,
                e.last_accessed_at,

                c.id AS course_id,
                c.instructor_id,
                c.title,
                c.description,
                c.thumbnail_path,
                c.category,
                c.status AS course_status,
                c.start_date,
                c.enrollment_deadline,
                c.created_at AS course_created_at,
                c.updated_at AS course_updated_at

            FROM {$this->enrollmentTable} e

            INNER JOIN {$this->courseTable} c
                ON c.id = e.course_id

            WHERE e.learner_id = :employee_id

            ORDER BY e.id DESC
        ");

            $stmt->execute([
                ':employee_id' => $employeeId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            throw new Exception(
                "Failed to fetch enrolled courses: " . $e->getMessage()
            );
        }
    }
    public function getModuleLessons(
        int $moduleId,
        int $enrollmentId
    ): array {
        try {

            $stmt = $this->conn->prepare("
            SELECT
                l.id,
                l.module_id,
                l.title,
                l.content_type,
                l.content_body,
                l.video_url,
                l.order_index,
                l.status,

                p.status AS progress_status,
                p.completed_at

            FROM {$this->lessonTable} l

            LEFT JOIN {$this->progressTable} p
                ON p.enrollment_id = :enrollment_id
                AND p.item_type = 'lesson'
                AND p.reference_id = l.id

            WHERE l.module_id = :module_id
            AND l.status = 'active'

            ORDER BY
                l.order_index ASC,
                l.id ASC
        ");

            $stmt->execute([
                ':module_id' => $moduleId,
                ':enrollment_id' => $enrollmentId
            ]);

            $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lessons as &$lesson) {

                $lesson['files'] = $this->getLessonFiles(
                    (int) $lesson['id']
                );

                $lesson['completed'] =
                    ($lesson['progress_status'] ?? '') === 'completed';
            }

            unset($lesson);

            return $lessons;

        } catch (Exception $e) {

            throw new Exception(
                "Failed to fetch module lessons: " . $e->getMessage()
            );
        }
    }
    public function getLessonFiles(int $lessonId): array
    {
        try {

            $stmt = $this->conn->prepare("
            SELECT
                id,
                lesson_id,
                file_path,
                title,
                uploaded_at

            FROM ld_lesson_file

            WHERE lesson_id = :lesson_id

            ORDER BY id ASC
        ");

            $stmt->execute([
                ':lesson_id' => $lessonId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {

            throw new Exception(
                "Failed to fetch lesson files: " . $e->getMessage()
            );
        }
    }
    public function completeModule(
        int $enrollmentId,
        int $moduleId
    ): bool {
        try {

            $checkStmt = $this->conn->prepare("
            SELECT id
            FROM {$this->progressTable}
            WHERE enrollment_id = :enrollment_id
              AND item_type = 'module'
              AND reference_id = :module_id
            LIMIT 1
        ");

            $checkStmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':module_id' => $moduleId
            ]);

            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {

                $stmt = $this->conn->prepare("
                UPDATE {$this->progressTable}
                SET
                    status = 'completed',
                    completed_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

                return $stmt->execute([
                    ':id' => $existing['id']
                ]);
            }

            $stmt = $this->conn->prepare("
            INSERT INTO {$this->progressTable}
            (
                enrollment_id,
                item_type,
                reference_id,
                status,
                completed_at
            )
            VALUES
            (
                :enrollment_id,
                'module',
                :module_id,
                'completed',
                CURRENT_TIMESTAMP
            )
        ");

            return $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':module_id' => $moduleId
            ]);

        } catch (Exception $e) {

            throw new Exception(
                "Failed to complete module: " . $e->getMessage()
            );
        }
    }
    public function getEnrollmentById(
        int $enrollmentId,
        int $employeeId
    ): ?array {
        try {

            $stmt = $this->conn->prepare("
            SELECT *
            FROM {$this->enrollmentTable}
            WHERE id = :enrollment_id
              AND learner_id = :employee_id
              AND status IN (
                  'invited',
                  'enrolled',
                  'in_progress'
              )
            LIMIT 1
        ");

            $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':employee_id' => $employeeId
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (Exception $e) {

            throw new Exception(
                "Failed to verify enrollment: " . $e->getMessage()
            );
        }
    }
    public function getCourseProgressStats(
        int $courseId,
        int $enrollmentId
    ): array {
        try {
            $moduleStmt = $this->conn->prepare("
            SELECT
                COUNT(DISTINCT m.id) AS total_modules,

                COUNT(
                    DISTINCT CASE
                        WHEN p.status = 'completed'
                        THEN m.id
                    END
                ) AS completed_modules

            FROM {$this->moduleTable} m

            LEFT JOIN {$this->progressTable} p
                ON p.enrollment_id = :enrollment_id
                AND p.item_type = 'module'
                AND p.reference_id = m.id

            WHERE m.course_id = :course_id
              AND m.status = 'active'
        ");

            $moduleStmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':course_id' => $courseId
            ]);

            $stats = $moduleStmt->fetch(PDO::FETCH_ASSOC);

            $totalModules = (int) ($stats['total_modules'] ?? 0);

            $completedModules = (int) (
                $stats['completed_modules'] ?? 0
            );

            $remainingModules = max(
                0,
                $totalModules - $completedModules
            );

            $courseProgress = $totalModules > 0
                ? (int) round(
                    ($completedModules / $totalModules) * 100
                )
                : 0;

            return [
                'courseProgress' => min(
                    100,
                    max(0, $courseProgress)
                ),

                'totalModules' => $totalModules,

                'completedModules' => $completedModules,

                'remainingModules' => $remainingModules,

                'totalLessons' => 0,

                'completedLessons' => 0,

                'remainingLessons' => 0
            ];

        } catch (Exception $e) {
            throw new Exception(
                "Failed to get course progress statistics: " .
                $e->getMessage()
            );
        }
    }
    public function getActiveEnrollment(int $employeeId): ?array
    {
        try {

            $stmt = $this->conn->prepare("
            SELECT
                e.id AS enrollment_id,
                e.learner_id,
                e.course_id,
                e.course_version_id,
                e.status AS enrollment_status,
                e.invited_by,
                e.enrolled_at,
                e.completed_at,
                e.last_accessed_at,
                e.created_at,

                c.id AS course_id,
                c.instructor_id,
                c.title,
                c.description,
                c.thumbnail_path,
                c.category,
                c.status AS course_status,
                c.start_date,
                c.enrollment_deadline,
                c.created_at AS course_created_at,
                c.updated_at AS course_updated_at

            FROM {$this->enrollmentTable} e

            INNER JOIN {$this->courseTable} c
                ON c.id = e.course_id

            WHERE e.learner_id = :employee_id
              AND e.status IN (
                  'invited',
                  'enrolled',
                  'in_progress'
              )

            ORDER BY e.id DESC

            LIMIT 1
        ");

            $stmt->execute([
                ':employee_id' => $employeeId
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (Exception $e) {

            throw new Exception(
                "Failed to fetch active enrollment: " .
                $e->getMessage()
            );
        }
    }
    public function getCourseModules(
        int $courseId,
        int $enrollmentId
    ): array {
        try {

            $stmt = $this->conn->prepare("
            SELECT
                m.id,
                m.course_id,
                m.title,
                m.description,
                m.order_index,
                m.status,

                p.status AS progress_status,
                p.completed_at AS module_completed_at

            FROM {$this->moduleTable} m

            LEFT JOIN {$this->progressTable} p
                ON p.enrollment_id = :enrollment_id
                AND p.item_type = 'module'
                AND p.reference_id = m.id

            WHERE m.course_id = :course_id
              AND m.status = 'active'

            ORDER BY
                m.order_index ASC,
                m.id ASC
        ");

            $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':course_id' => $courseId
            ]);

            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($modules as &$module) {

                $module['lessons'] = $this->getModuleLessons(
                    (int) $module['id'],
                    $enrollmentId
                );

            }

            unset($module);

            return $modules;

        } catch (Exception $e) {

            throw new Exception(
                "Failed to fetch course modules: " .
                $e->getMessage()
            );
        }
    }
    public function completeEnrollment(
        int $enrollmentId,
        int $employeeId
    ): bool {
        try {
            $stmt = $this->conn->prepare("
        UPDATE {$this->enrollmentTable}
        SET
            status = 'completed',
            completed_at = CURRENT_TIMESTAMP
        WHERE id = :enrollment_id
          AND learner_id = :employee_id
          AND status IN ('enrolled', 'in_progress')
    ");

            return $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':employee_id' => $employeeId
            ]);

        } catch (Exception $e) {
            throw new Exception(
                "Failed to complete enrollment: " . $e->getMessage()
            );
        }
    }
    public function getCompletedEnrollmentById(
        int $enrollmentId,
        int $employeeId
    ): ?array {

        try {

            $stmt = $this->conn->prepare("
            SELECT *
            FROM {$this->enrollmentTable}
            WHERE id = :enrollment_id
              AND learner_id = :employee_id
              AND status = 'completed'
            LIMIT 1
        ");

            $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':employee_id' => $employeeId
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (Exception $e) {

            throw new Exception(
                "Failed to verify completed enrollment: "
                . $e->getMessage()
            );
        }
    }
}