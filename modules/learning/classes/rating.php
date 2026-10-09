<?php

include_once __DIR__ . '/../../../database/db.php';

class Rating
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
     * Get rating for a specific course by a learner
     */
    public function getByLearnerAndCourse(int $learnerId, int $courseId): ?array
    {
        $sql = 'SELECT id, learner_id, course_id, rating, comment, created_at FROM ld_rating WHERE learner_id = :learner_id AND course_id = :course_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
        ]);

        $rating = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rating ?: null;
    }

    /**
     * Get all ratings for a course
     */
    public function getByCourse(int $courseId): array
    {
        $sql = 'SELECT id, learner_id, rating, comment, created_at FROM ld_rating WHERE course_id = :course_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get average rating for a course
     */
    public function getAverageRating(int $courseId): ?float
    {
        $sql = 'SELECT AVG(rating) FROM ld_rating WHERE course_id = :course_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);

        $avg = $stmt->fetchColumn();

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Get all ratings by a learner
     */
    public function getByLearner(int $learnerId): array
    {
        $sql = 'SELECT r.id, r.course_id, r.rating, r.comment, r.created_at, c.title AS course_title
                FROM ld_rating r
                JOIN ld_course c ON c.id = r.course_id
                WHERE r.learner_id = :learner_id
                ORDER BY r.created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create or update a rating
     */
    public function createOrUpdate(array $input): array
    {
        $learnerId = (int) ($input['learner_id'] ?? 0);
        $courseId = (int) ($input['course_id'] ?? 0);
        $rating = (int) ($input['rating'] ?? 0);
        $comment = isset($input['comment']) ? trim((string) $input['comment']) : null;

        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5.'];
        }

        // Check if rating exists
        $existing = $this->getByLearnerAndCourse($learnerId, $courseId);

        if ($existing) {
            // Update existing rating
            $stmt = $this->conn->prepare('UPDATE ld_rating SET rating = :rating, comment = :comment WHERE learner_id = :learner_id AND course_id = :course_id');
            $stmt->execute([
                ':rating' => $rating,
                ':comment' => $comment,
                ':learner_id' => $learnerId,
                ':course_id' => $courseId,
            ]);

            return [
                'success' => true,
                'id' => (int) $existing['id'],
                'message' => 'Rating updated successfully.',
            ];
        }

        // Create new rating
        $stmt = $this->conn->prepare('INSERT INTO ld_rating (learner_id, course_id, rating, comment) VALUES (:learner_id, :course_id, :rating, :comment)');
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Rating created successfully.',
        ];
    }

    /**
     * Delete a rating
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Rating ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_rating WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Rating deleted successfully'];
    }
}
