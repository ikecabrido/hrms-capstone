<?php

include_once __DIR__ . '/../../../database/db.php';

class Archive
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
     * Get all archived courses
     */
    public function getArchivedCourses(): array
    {
        $sql = 'SELECT id, title, category, created_at, updated_at FROM ld_course WHERE status = :status ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived modules for a course
     */
    public function getArchivedModules(int $courseId = null): array
    {
        if ($courseId !== null && $courseId > 0) {
            $sql = 'SELECT m.id, m.course_id, m.title, m.created_at FROM ld_module m WHERE m.status = :status AND m.course_id = :course_id ORDER BY m.updated_at DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':status' => 'archived',
                ':course_id' => $courseId,
            ]);
        } else {
            $sql = 'SELECT id, course_id, title, created_at FROM ld_module WHERE status = :status ORDER BY updated_at DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':status' => 'archived']);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived lessons for a module
     */
    public function getArchivedLessons(int $moduleId = null): array
    {
        if ($moduleId !== null && $moduleId > 0) {
            $sql = 'SELECT id, module_id, title, created_at FROM ld_lesson WHERE status = :status AND module_id = :module_id ORDER BY updated_at DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':status' => 'archived',
                ':module_id' => $moduleId,
            ]);
        } else {
            $sql = 'SELECT id, module_id, title, created_at FROM ld_lesson WHERE status = :status ORDER BY updated_at DESC';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':status' => 'archived']);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived quizzes
     */
    public function getArchivedQuizzes(): array
    {
        $sql = 'SELECT id, module_id, title, created_at FROM ld_quiz WHERE status = :status ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived evaluations
     */
    public function getArchivedEvaluations(): array
    {
        $sql = 'SELECT id, course_id, title, created_at FROM ld_evaluation WHERE status = :status ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived comments
     */
    public function getArchivedComments(): array
    {
        $sql = 'SELECT id, learner_id, lesson_id, message, created_at FROM ld_comment WHERE status = :status ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived programs
     */
    public function getArchivedPrograms(): array
    {
        $sql = 'SELECT id, instructor_id, title, created_at FROM ld_program WHERE status = :status ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived learning paths
     */
    public function getArchivedLearningPaths(): array
    {
        $sql = 'SELECT id, instructor_id, title, created_at FROM ld_learning_path WHERE status = :status ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all archived certificates
     */
    public function getArchivedCertificates(): array
    {
        $sql = 'SELECT id, learner_id, course_id, issued_at FROM ld_certificate WHERE status = :status ORDER BY issued_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'archived']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Archive a course
     */
    public function archiveCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_course SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $courseId]);

        return ['success' => true, 'message' => 'Course archived successfully.'];
    }

    /**
     * Restore an archived course
     */
    public function restoreCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_course SET status = 'active', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $courseId]);

        return ['success' => true, 'message' => 'Course restored successfully.'];
    }

    /**
     * Archive a module
     */
    public function archiveModule(int $moduleId): array
    {
        if ($moduleId <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_module SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $moduleId]);

        return ['success' => true, 'message' => 'Module archived successfully.'];
    }

    /**
     * Restore an archived module
     */
    public function restoreModule(int $moduleId): array
    {
        if ($moduleId <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_module SET status = 'active', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $moduleId]);

        return ['success' => true, 'message' => 'Module restored successfully.'];
    }

    /**
     * Get audit log entries
     */
    public function getAuditLog(int $limit = 100): array
    {
        $sql = 'SELECT id, user_id, role, action, item_type, reference_id, details, created_at FROM ld_audit_log ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit log entries for a specific user
     */
    public function getAuditLogByUser(int $userId, int $limit = 100): array
    {
        $sql = 'SELECT id, role, action, item_type, reference_id, created_at FROM ld_audit_log WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add an audit log entry
     */
    public function logAction(array $input): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $role = trim((string) ($input['role'] ?? ''));
        $action = trim((string) ($input['action'] ?? ''));
        $itemType = trim((string) ($input['item_type'] ?? ''));
        $referenceId = (int) ($input['reference_id'] ?? 0);
        $details = isset($input['details']) ? json_encode($input['details']) : null;

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if (!in_array($role, ['admin', 'instructor', 'learner'], true)) {
            return ['success' => false, 'message' => 'Invalid role.'];
        }

        if (!in_array($action, ['create', 'edit', 'archive', 'restore', 'review'], true)) {
            return ['success' => false, 'message' => 'Invalid action.'];
        }

        if ($itemType === '') {
            return ['success' => false, 'message' => 'Item type is required.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_audit_log (user_id, role, action, item_type, reference_id, details) VALUES (:user_id, :role, :action, :item_type, :reference_id, :details)');
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $role,
            ':action' => $action,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
            ':details' => $details,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Audit log entry created successfully.',
        ];
    }

    /**
     * Count archived items by type
     */
    public function countArchived(string $itemType): int
    {
        $map = [
            'course' => 'ld_course',
            'module' => 'ld_module',
            'lesson' => 'ld_lesson',
            'quiz' => 'ld_quiz',
            'evaluation' => 'ld_evaluation',
            'program' => 'ld_program',
        ];

        if (!isset($map[$itemType])) {
            return 0;
        }

        $table = $map[$itemType];
        $sql = "SELECT COUNT(*) FROM $table WHERE status = 'archived'";
        $stmt = $this->conn->query($sql);

        return (int) $stmt->fetchColumn();
    }
}
