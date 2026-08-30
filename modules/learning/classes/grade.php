<?php

include_once __DIR__ . '/../../../database/db.php';

class Grade
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
     * Computes and stores the final grade for a completed course.
     *
     * Rule: if the course has a final Evaluation, that score IS the final grade
     * (it's the course's culminating assessment). If there's no Evaluation,
     * the final grade is the average of all Quiz scores in the course.
     * If neither exists, no grade record is created (nothing to score).
     */
    public function calculateAndStore(int $learnerId, int $courseId): array
    {
        if ($learnerId <= 0 || $courseId <= 0) {
            return ['success' => false, 'message' => 'Learner ID and Course ID are required.'];
        }

        $existing = $this->getByLearnerAndCourse($learnerId, $courseId);
        if ($existing) {
            return ['success' => true, 'message' => 'Grade already recorded.', 'id' => (int) $existing['id']];
        }

        $evaluationScore = $this->getEvaluationScore($learnerId, $courseId);

        if ($evaluationScore !== null) {
            $finalScore = $evaluationScore;
        } else {
            $quizAverage = $this->getQuizAverage($learnerId, $courseId);

            if ($quizAverage === null) {
                return ['success' => false, 'message' => 'No scored assessments found for this course.'];
            }

            $finalScore = $quizAverage;
        }

        $passingScore = $this->getCoursePassingThreshold($courseId);
        $status = $passingScore !== null ? ($finalScore >= $passingScore ? 'passed' : 'failed') : 'passed';

        $stmt = $this->conn->prepare(
            'INSERT INTO ld_grade (learner_id, course_id, final_score, status)
             VALUES (:learner_id, :course_id, :final_score, :status)'
        );
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
            ':final_score' => $finalScore,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Grade recorded.',
            'id' => (int) $this->conn->lastInsertId(),
            'final_score' => $finalScore,
            'status' => $status,
        ];
    }

    public function getByLearnerAndCourse(int $learnerId, int $courseId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM ld_grade WHERE learner_id = :learner_id AND course_id = :course_id LIMIT 1'
        );
        $stmt->execute([':learner_id' => $learnerId, ':course_id' => $courseId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * All of a learner's grades — powers result-subpage/grade.php.
     */
    public function getByLearner(int $learnerId): array
    {
        $sql = 'SELECT g.*, c.title AS course_title
                FROM ld_grade g
                JOIN ld_course c ON c.id = g.course_id
                WHERE g.learner_id = :learner_id
                ORDER BY g.issued_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aggregate score across all of a learner's graded courses — powers get-average-score.php.
     */
    public function getAverageScore(int $learnerId): ?float
    {
        $stmt = $this->conn->prepare('SELECT AVG(final_score) FROM ld_grade WHERE learner_id = :learner_id');
        $stmt->execute([':learner_id' => $learnerId]);

        $avg = $stmt->fetchColumn();

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Manual override — Admin/Instructor correcting a grade (edit-grade.php).
     */
    public function update(int $id, float $finalScore, ?string $status = null): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Grade ID is required.'];
        }

        if ($status === null) {
            $status = $finalScore >= 60 ? 'passed' : 'failed'; // fallback threshold if none supplied
        }

        if (!in_array($status, ['passed', 'failed'], true)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_grade SET final_score = :final_score, status = :status WHERE id = :id');
        $stmt->execute([
            ':final_score' => $finalScore,
            ':status' => $status,
            ':id' => $id,
        ]);

        return ['success' => true, 'message' => 'Grade updated.', 'id' => $id];
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function getEvaluationScore(int $learnerId, int $courseId): ?float
    {
        $stmt = $this->conn->prepare(
            "SELECT qs.score
             FROM ld_quiz_session qs
             JOIN ld_evaluation e ON e.id = qs.reference_id
             WHERE qs.learner_id = :learner_id AND e.course_id = :course_id
               AND qs.item_type = 'evaluation' AND qs.status IN ('submitted','expired')
             ORDER BY qs.submitted_at DESC LIMIT 1"
        );
        $stmt->execute([':learner_id' => $learnerId, ':course_id' => $courseId]);

        $score = $stmt->fetchColumn();

        return $score !== false ? (float) $score : null;
    }

    private function getQuizAverage(int $learnerId, int $courseId): ?float
    {
        $stmt = $this->conn->prepare(
            "SELECT AVG(latest.score) FROM (
                SELECT qs.reference_id, MAX(qs.submitted_at) AS latest_at
                FROM ld_quiz_session qs
                JOIN ld_quiz q ON q.id = qs.reference_id
                WHERE qs.learner_id = :learner_id AND q.module_id IN (
                    SELECT id FROM ld_module WHERE course_id = :course_id
                ) AND qs.item_type = 'quiz' AND qs.status IN ('submitted','expired')
                GROUP BY qs.reference_id
             ) AS grouped
             JOIN ld_quiz_session latest
               ON latest.reference_id = grouped.reference_id AND latest.submitted_at = grouped.latest_at"
        );
        $stmt->execute([':learner_id' => $learnerId, ':course_id' => $courseId]);

        $avg = $stmt->fetchColumn();

        return $avg !== false && $avg !== null ? round((float) $avg, 2) : null;
    }

    private function getCoursePassingThreshold(int $courseId): ?float
    {
        // Uses the Evaluation's passing_score if the course has one, since that's
        // the primary grading source. Falls back to null (always "passed") otherwise.
        $stmt = $this->conn->prepare('SELECT passing_score FROM ld_evaluation WHERE course_id = :course_id LIMIT 1');
        $stmt->execute([':course_id' => $courseId]);

        $score = $stmt->fetchColumn();

        return $score !== false && $score !== null ? (float) $score : null;
    }
}