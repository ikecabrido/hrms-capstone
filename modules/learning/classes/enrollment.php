<?php

include_once __DIR__ . '/../../../database/db.php';

class Enrollment
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
     * Learner self-enrolls into a course (Catalog "Enroll" action).
     * Goes straight to 'enrolled' status — no confirmation step needed
     * since the learner is the one initiating it.
     */
    public function enroll(int $learnerId, int $courseId): array
    {
        if ($learnerId <= 0 || $courseId <= 0) {
            return ['success' => false, 'message' => 'Learner ID and Course ID are required.'];
        }

        $existing = $this->getByLearnerAndCourse($learnerId, $courseId);
        if ($existing) {
            return ['success' => false, 'message' => 'You are already enrolled in this course.'];
        }

        $versionId = $this->getCurrentCourseVersionId($courseId);

        $sql = 'INSERT INTO ld_enrollment (learner_id, course_id, course_version_id, status, enrolled_at)
                VALUES (:learner_id, :course_id, :course_version_id, :status, NOW())';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
            ':course_version_id' => $versionId,
            ':status' => 'enrolled',
        ]);

        return [
            'success' => true,
            'message' => 'Enrolled successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    /**
     * Instructor-initiated enrollment (assign-learner.php).
     * Creates an 'invited' row — learner must accept before it becomes 'enrolled'.
     */
    public function invite(int $learnerId, int $courseId, int $invitedBy = 0): array
    {
        if ($learnerId <= 0 || $courseId <= 0) {
            return ['success' => false, 'message' => 'Learner ID and Course ID are required.'];
        }

        $existing = $this->getByLearnerAndCourse($learnerId, $courseId);
        if ($existing) {
            return ['success' => false, 'message' => 'This learner already has an enrollment record for this course.'];
        }

        $versionId = $this->getCurrentCourseVersionId($courseId);

        $sql = 'INSERT INTO ld_enrollment (learner_id, course_id, course_version_id, status, invited_by)
                VALUES (:learner_id, :course_id, :course_version_id, :status, :invited_by)';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
            ':course_version_id' => $versionId,
            ':status' => 'invited',
            ':invited_by' => $invitedBy,
        ]);

        $enrollmentId = (int) $this->conn->lastInsertId();

        // Notification dispatch belongs to Notification.php — left as a hook here.
        // e.g. (new Notification($this->conn))->send($learnerId, 'invitation', ...);

        return [
            'success' => true,
            'message' => 'Invitation sent.',
            'id' => $enrollmentId,
        ];
    }

    /**
     * Learner accepts a pending course invitation.
     */
    public function acceptInvitation(int $enrollmentId, int $learnerId): array
    {
        $enrollment = $this->getById($enrollmentId);

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Invitation not found.'];
        }

        if ((int) $enrollment['learner_id'] !== $learnerId) {
            return ['success' => false, 'message' => 'This invitation does not belong to you.'];
        }

        if ($enrollment['status'] !== 'invited') {
            return ['success' => false, 'message' => 'This invitation is no longer pending.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE ld_enrollment SET status = 'enrolled', enrolled_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $enrollmentId]);

        return ['success' => true, 'message' => 'Invitation accepted. You are now enrolled.'];
    }

    /**
     * Learner declines a pending course invitation.
     * Per the no-delete rule, this archives the row instead of removing it.
     */
    public function declineInvitation(int $enrollmentId, int $learnerId): array
    {
        $enrollment = $this->getById($enrollmentId);

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Invitation not found.'];
        }

        if ((int) $enrollment['learner_id'] !== $learnerId) {
            return ['success' => false, 'message' => 'This invitation does not belong to you.'];
        }

        if ($enrollment['status'] !== 'invited') {
            return ['success' => false, 'message' => 'This invitation is no longer pending.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE ld_enrollment SET status = 'withdrawn' WHERE id = :id"
        );
        $stmt->execute([':id' => $enrollmentId]);

        return ['success' => true, 'message' => 'Invitation declined.'];
    }

    /**
     * Learner drops a course they're currently enrolled in.
     */
    public function unenroll(int $learnerId, int $courseId): array
    {
        $enrollment = $this->getByLearnerAndCourse($learnerId, $courseId);

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Enrollment not found.'];
        }

        if (!in_array($enrollment['status'], ['enrolled', 'in_progress'], true)) {
            return ['success' => false, 'message' => 'This course cannot be unenrolled from its current state.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE ld_enrollment SET status = 'withdrawn' WHERE id = :id"
        );
        $stmt->execute([':id' => $enrollment['id']]);

        return ['success' => true, 'message' => 'Unenrolled successfully.'];
    }

    /**
     * Marks an enrollment complete — typically called once Evaluation is passed
     * or all course items are done. Also triggers final grade calculation.
     */
    public function markCompleted(int $enrollmentId): array
    {
        if ($enrollmentId <= 0) {
            return ['success' => false, 'message' => 'Enrollment ID is required.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE ld_enrollment SET status = 'completed', completed_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $enrollmentId]);

        $enrollment = $this->getById($enrollmentId);
        if ($enrollment) {
            require_once __DIR__ . '/grade.php';
            $grade = new Grade($this->conn);
            $grade->calculateAndStore((int) $enrollment['learner_id'], (int) $enrollment['course_id']);
        }

        return ['success' => true, 'message' => 'Enrollment marked as completed.'];
    }

    public function touchLastAccessed(int $enrollmentId): void
    {
        if ($enrollmentId <= 0) {
            return;
        }

        $stmt = $this->conn->prepare(
            'UPDATE ld_enrollment SET last_accessed_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':id' => $enrollmentId]);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM ld_enrollment WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByLearnerAndCourse(int $learnerId, int $courseId): ?array
    {
        $sql = 'SELECT * FROM ld_enrollment WHERE learner_id = :learner_id AND course_id = :course_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * A learner's own enrolled courses — powers Study/Catalog "already enrolled" checks.
     */
    public function getByLearner(int $learnerId, ?string $status = null): array
    {
        $sql = 'SELECT e.*, c.title AS course_title, c.thumbnail_path
                FROM ld_enrollment e
                JOIN ld_course c ON c.id = e.course_id
                WHERE e.learner_id = :learner_id';
        $params = [':learner_id' => $learnerId];

        if ($status !== null && $status !== '') {
            $sql .= ' AND e.status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY e.enrolled_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Instructor's roster for one course — get-course-roster.php.
     */
    public function getByCourse(int $courseId, ?string $status = null): array
    {
        $sql = 'SELECT e.*, emp.first_name, emp.last_name, emp.email
                FROM ld_enrollment e
                JOIN employee emp ON emp.id = e.learner_id
                WHERE e.course_id = :course_id';
        $params = [':course_id' => $courseId];

        if ($status !== null && $status !== '') {
            $sql .= ' AND e.status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY e.enrolled_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pending invitations for a learner — powers the "Pending Invitations" widget.
     */
    public function getPendingInvitations(int $learnerId): array
    {
        $sql = "SELECT e.*, c.title AS course_title, emp.first_name AS inviter_first_name, emp.last_name AS inviter_last_name
                FROM ld_enrollment e
                JOIN ld_course c ON c.id = e.course_id
                LEFT JOIN employee emp ON emp.id = e.invited_by
                WHERE e.learner_id = :learner_id AND e.status = 'invited'
                ORDER BY e.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByCourse(int $courseId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM ld_enrollment WHERE course_id = :course_id';
        $params = [':course_id' => $courseId];

        if ($status !== null && $status !== '') {
            $sql .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getCurrentCourseVersionId(int $courseId): ?int
    {
        $stmt = $this->conn->prepare(
            'SELECT id FROM ld_course_version WHERE course_id = :course_id ORDER BY version_number DESC LIMIT 1'
        );
        $stmt->execute([':course_id' => $courseId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}