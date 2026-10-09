<?php

include_once __DIR__ . '/../../../database/db.php';

class Comment
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

    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, learner_id, lesson_id, message, parent_comment_id, status, was_ever_reported, created_at FROM ld_comment WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $comment ?: null;
    }

    public function getByLesson(int $lessonId): array
    {
        $sql = 'SELECT id, learner_id, lesson_id, message, parent_comment_id, status, created_at FROM ld_comment WHERE lesson_id = :lesson_id AND status = :status ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':lesson_id' => $lessonId,
            ':status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReplies(int $parentCommentId): array
    {
        $sql = 'SELECT id, learner_id, message, status, created_at FROM ld_comment WHERE parent_comment_id = :parent_id AND status = :status ORDER BY created_at ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':parent_id' => $parentCommentId,
            ':status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $input): array
    {
        $learnerId = (int) ($input['learner_id'] ?? 0);
        $lessonId = (int) ($input['lesson_id'] ?? 0);
        $message = trim((string) ($input['message'] ?? ''));
        $parentCommentId = isset($input['parent_comment_id']) && $input['parent_comment_id'] !== '' ? (int) $input['parent_comment_id'] : null;

        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($lessonId <= 0) {
            return ['success' => false, 'message' => 'Lesson ID is required.'];
        }

        if ($message === '') {
            return ['success' => false, 'message' => 'Comment message is required.'];
        }

        $sql = 'INSERT INTO ld_comment (learner_id, lesson_id, message, parent_comment_id, status) VALUES (:learner_id, :lesson_id, :message, :parent_id, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':lesson_id' => $lessonId,
            ':message' => $message,
            ':parent_id' => $parentCommentId,
            ':status' => 'active',
        ]);

        return [
            'success' => true,
            'message' => 'Comment created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function update(array $input): array
    {
        $commentId = (int) ($input['id'] ?? 0);
        $message = trim((string) ($input['message'] ?? ''));

        if ($commentId <= 0) {
            return ['success' => false, 'message' => 'Comment ID is required.'];
        }

        if ($message === '') {
            return ['success' => false, 'message' => 'Comment message is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_comment SET message = :message WHERE id = :id');
        $stmt->execute([
            ':message' => $message,
            ':id' => $commentId,
        ]);

        return ['success' => true, 'id' => $commentId, 'message' => 'Comment updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Comment ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_comment SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Comment archived successfully'];
    }

    public function flagAsReported(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Comment ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_comment SET was_ever_reported = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Comment flagged as reported'];
    }
}
