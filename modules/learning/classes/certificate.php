<?php

include_once __DIR__ . '/../../../database/db.php';

class Certificate
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

    // ------------------------------------------------------------------
    // TEMPLATE — what a certificate looks like (instructor-configured)
    // ------------------------------------------------------------------

    public function createTemplate(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $instructorId = (int) ($data['instructor_id'] ?? 0);

        if ($title === '' || $instructorId <= 0) {
            return ['success' => false, 'message' => 'Title and instructor are required.'];
        }

        $sql = 'INSERT INTO ld_certificate_template (instructor_id, course_id, title, description, template_file, is_active)
                VALUES (:instructor_id, :course_id, :title, :description, :template_file, :is_active)';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':course_id' => !empty($data['course_id']) ? (int) $data['course_id'] : null,
            ':title' => $title,
            ':description' => $data['description'] ?? null,
            ':template_file' => $data['template_file'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 1, // default active on create
        ]);

        return [
            'success' => true,
            'message' => 'Certificate template created.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function updateTemplate(int $id, array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($id <= 0 || $title === '') {
            return ['success' => false, 'message' => 'Certificate title is required.'];
        }

        $sql = 'UPDATE ld_certificate_template
                SET title = :title, description = :description, template_file = :template_file,
                    is_active = :is_active, updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $data['description'] ?? null,
            ':template_file' => $data['template_file'] ?? null,
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':id' => $id,
        ]);

        return ['success' => true, 'message' => 'Certificate template updated.', 'id' => $id];
    }

    public function archiveTemplate(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Certificate template ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_certificate_template SET is_active = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'message' => 'Certificate template archived.'];
    }

    public function getTemplateList(?int $courseId = null): array
    {
        $sql = 'SELECT * FROM ld_certificate_template WHERE is_active = 1';
        $params = [];

        if ($courseId !== null) {
            $sql .= ' AND (course_id = :course_id OR course_id IS NULL)';
            $params[':course_id'] = $courseId;
        }

        $sql .= ' ORDER BY title ASC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // ISSUED CERTIFICATE — the actual per-learner record
    // ------------------------------------------------------------------

    /**
     * Issue a certificate to a learner. Only valid once the enrollment
     * is actually completed — this is enforced here, not left to the caller.
     */
    public function issue(int $learnerId, int $courseId, int $completedEnrollmentId, ?int $templateId = null, ?string $validUntil = null): array
    {
        if ($learnerId <= 0 || $courseId <= 0 || $completedEnrollmentId <= 0) {
            return ['success' => false, 'message' => 'Learner, Course, and Enrollment are required.'];
        }

        $enrollmentCheck = $this->conn->prepare(
            "SELECT status, course_version_id FROM ld_enrollment WHERE id = :id AND learner_id = :learner_id LIMIT 1"
        );
        $enrollmentCheck->execute([':id' => $completedEnrollmentId, ':learner_id' => $learnerId]);
        $enrollment = $enrollmentCheck->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Enrollment record not found.'];
        }

        if ($enrollment['status'] !== 'completed') {
            return ['success' => false, 'message' => 'Certificate can only be issued once the course is completed.'];
        }

        $existing = $this->getByEnrollment($completedEnrollmentId);
        if ($existing) {
            return ['success' => false, 'message' => 'A certificate has already been issued for this enrollment.'];
        }

        $verificationCode = $this->generateVerificationCode();

        $sql = 'INSERT INTO ld_certificate
                    (learner_id, course_id, course_version_id, completed_enrollment_id, template_id, verification_code, valid_until, status)
                VALUES
                    (:learner_id, :course_id, :course_version_id, :completed_enrollment_id, :template_id, :verification_code, :valid_until, :status)';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':course_id' => $courseId,
            ':course_version_id' => $enrollment['course_version_id'],
            ':completed_enrollment_id' => $completedEnrollmentId,
            ':template_id' => $templateId,
            ':verification_code' => $verificationCode,
            ':valid_until' => $validUntil,
            ':status' => 'active',
        ]);

        $certificateId = (int) $this->conn->lastInsertId();

        return [
            'success' => true,
            'message' => 'Certificate issued.',
            'id' => $certificateId,
            'verification_code' => $verificationCode,
        ];
    }

    /**
     * Revoke a certificate — per the no-delete rule, this archives it, it does not remove the row.
     */
    public function revoke(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Certificate ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_certificate SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'message' => 'Certificate revoked.'];
    }

    public function getByEnrollment(int $enrollmentId): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM ld_certificate WHERE completed_enrollment_id = :id LIMIT 1');
        $stmt->execute([':id' => $enrollmentId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Learner's own certificates — powers result-subpage/certificate.php.
     */
    public function getByLearner(int $learnerId): array
    {
        $sql = 'SELECT c.*, co.title AS course_title
                FROM ld_certificate c
                JOIN ld_course co ON co.id = c.course_id
                WHERE c.learner_id = :learner_id AND c.status = "active"
                ORDER BY c.issued_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Public verification lookup — verify-certificate.php. No auth, code-based only.
     */
    public function verifyByCode(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $sql = "SELECT c.verification_code, c.issued_at, c.valid_until, c.status,
                       co.title AS course_title, emp.first_name, emp.last_name
                FROM ld_certificate c
                JOIN ld_course co ON co.id = c.course_id
                JOIN employee emp ON emp.id = c.learner_id
                WHERE c.verification_code = :code
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':code' => $code]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['is_expired'] = $row['valid_until'] !== null && strtotime($row['valid_until']) < time();
        $row['is_valid'] = $row['status'] === 'active' && !$row['is_expired'];

        return $row;
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(8))); // 16-char code
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_certificate WHERE verification_code = :code');
            $stmt->execute([':code' => $code]);
            $exists = (int) $stmt->fetchColumn() > 0;
        } while ($exists);

        return $code;
    }
}