<?php

include_once __DIR__ . '/../../../database/db.php';

class Analytics
{
    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get total count of courses
     */
    public function getTotalCourses(): int
    {
        $stmt = $this->conn->query('SELECT COUNT(*) FROM ld_course WHERE status IN (\'draft\', \'active\', \'archived\')');

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get count of active courses
     */
    public function getActiveCourses(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM ld_course WHERE status = 'active'");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get total count of learners enrolled
     */
    public function getTotalLearners(): int
    {
        $stmt = $this->conn->query('SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment');

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get count of active enrollments
     */
    public function getActiveEnrollments(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM ld_enrollment WHERE status IN ('enrolled', 'in_progress')");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get count of completed enrollments
     */
    public function getCompletedEnrollments(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM ld_enrollment WHERE status = 'completed'");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get count of issued certificates
     */
    public function getTotalCertificates(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM ld_certificate WHERE status = 'active'");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get average score across all learners
     */
    public function getAverageScore(): ?float
    {
        $stmt = $this->conn->query('SELECT AVG(final_score) FROM ld_grade');

        $avg = $stmt->fetchColumn();

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Get pass rate (percentage of learners who passed)
     */
    public function getPassRate(): ?float
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM ld_grade WHERE status = 'passed'");
        $passed = (int) $stmt->fetchColumn();

        $stmt = $this->conn->query('SELECT COUNT(*) FROM ld_grade');
        $total = (int) $stmt->fetchColumn();

        if ($total === 0) {
            return null;
        }

        return round(($passed / $total) * 100, 2);
    }

    /**
     * Get courses by instructor
     */
    public function getCoursesByInstructor(int $instructorId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_course WHERE instructor_id = :instructor_id');
        $stmt->execute([':instructor_id' => $instructorId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get learners enrolled in courses by instructor
     */
    public function getLearnersByInstructor(int $instructorId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :instructor_id');
        $stmt->execute([':instructor_id' => $instructorId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get course completion rate for a specific course
     */
    public function getCourseCompletionRate(int $courseId): ?float
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE course_id = :course_id AND status = 'completed'");
        $stmt->execute([':course_id' => $courseId]);
        $completed = (int) $stmt->fetchColumn();

        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_enrollment WHERE course_id = :course_id');
        $stmt->execute([':course_id' => $courseId]);
        $total = (int) $stmt->fetchColumn();

        if ($total === 0) {
            return null;
        }

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get learner progress for a specific course
     */
    public function getLearnerProgress(int $learnerId, int $courseId): array
    {
        $sql = 'SELECT e.id, e.status, e.enrolled_at, e.completed_at, e.last_accessed_at FROM ld_enrollment e WHERE e.learner_id = :learner_id AND e.course_id = :course_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
        ]);

        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Enrollment not found.'];
        }

        // Get modules completed
        $sql = 'SELECT COUNT(DISTINCT p.reference_id) FROM ld_progress p WHERE p.enrollment_id = :enrollment_id AND p.item_type = :item_type AND p.status = :status';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':enrollment_id' => $enrollment['id'],
            ':item_type' => 'module',
            ':status' => 'completed',
        ]);
        $modulesCompleted = (int) $stmt->fetchColumn();

        // Get total modules
        $sql = 'SELECT COUNT(*) FROM ld_module WHERE course_id = :course_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        $totalModules = (int) $stmt->fetchColumn();

        // Get learner's grade
        $sql = 'SELECT final_score, status FROM ld_grade WHERE learner_id = :learner_id AND course_id = :course_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
        ]);
        $grade = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'enrollment' => $enrollment,
            'modules_completed' => $modulesCompleted,
            'total_modules' => $totalModules,
            'grade' => $grade,
        ];
    }

    /**
     * Get top performing learners in a course
     */
    public function getTopLearners(int $courseId, int $limit = 10): array
    {
        $sql = 'SELECT g.learner_id, g.final_score, g.status FROM ld_grade g WHERE g.course_id = :course_id ORDER BY g.final_score DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get quiz performance summary
     */
    public function getQuizPerformance(int $quizId): array
    {
        $sql = 'SELECT AVG(qs.score) as avg_score, MIN(qs.score) as min_score, MAX(qs.score) as max_score, COUNT(DISTINCT qs.learner_id) as attempt_count FROM ld_quiz_session qs WHERE qs.reference_id = :quiz_id AND qs.item_type = :item_type AND qs.status IN (:status_submitted, :status_expired)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':quiz_id' => $quizId,
            ':item_type' => 'quiz',
            ':status_submitted' => 'submitted',
            ':status_expired' => 'expired',
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get enrollment trend (new enrollments per month)
     */
    public function getEnrollmentTrend(int $months = 6): array
    {
        $sql = 'SELECT DATE_TRUNC(\'month\', e.created_at) as month, COUNT(*) as count FROM ld_enrollment e WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH) GROUP BY DATE_TRUNC(\'month\', e.created_at) ORDER BY month ASC';
        
        // Use DATE_FORMAT for MySQL compatibility
        $sql = 'SELECT DATE_FORMAT(e.created_at, \'%Y-%m\') as month, COUNT(*) as count FROM ld_enrollment e WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH) GROUP BY DATE_FORMAT(e.created_at, \'%Y-%m\') ORDER BY month ASC';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get courses by category
     */
    public function getCoursesByCategory(): array
    {
        $sql = 'SELECT category, COUNT(*) as count FROM ld_course WHERE status IN (\'active\', \'archived\') GROUP BY category ORDER BY count DESC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total time spent on courses by learner
     */
    public function getTotalTimeSpent(int $learnerId): int
    {
        // This is an estimate based on enrollment duration
        $sql = 'SELECT SUM(TIMESTAMPDIFF(SECOND, e.enrolled_at, COALESCE(e.completed_at, e.last_accessed_at))) as total_seconds FROM ld_enrollment e WHERE e.learner_id = :learner_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        $result = $stmt->fetchColumn();

        return (int) ($result ?? 0);
    }

    /**
     * Get most popular courses by enrollment
     */
    public function getMostPopularCourses(int $limit = 10): array
    {
        $sql = 'SELECT c.id, c.title, COUNT(e.id) as enrollment_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id = c.id WHERE c.status = :status GROUP BY c.id ORDER BY enrollment_count DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
