<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * Training programs.
 *
 * A program owns four sets of links:
 *   - courses     (ld_program_course)
 *   - skills      (ld_program_skill, existing table)
 *   - trainers    (ld_program_trainer)
 *   - participants(ld_program_participant)
 *
 * The existing ld_program.instructor_id is kept as the program owner. The
 * extra delivery/schedule/expiry columns are installed by
 * rainzui/ld_program_delivery_assessment_2026-10-26.sql.
 */
class Program
{
    private PDO $conn;
    private ?bool $schemaReadyCache = null;

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
     * True when the program delivery/assessment migration has been applied.
     * The read-only program list still works without it; the builder shows a
     * clear instruction instead of failing with a SQL error.
     */
    public function schemaReady(): bool
    {
        if ($this->schemaReadyCache !== null) {
            return $this->schemaReadyCache;
        }

        try {
            $columns = $this->conn->query(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ld_program'"
            )->fetchAll(PDO::FETCH_COLUMN);
            $columns = array_map('strtolower', $columns);

            $requiredColumns = ['delivery_mode', 'starts_at', 'ends_at', 'expires_at', 'location', 'parent_program_id', 'run_number'];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns, true)) {
                    return $this->schemaReadyCache = false;
                }
            }

            foreach (['ld_program_skill', 'ld_program_course', 'ld_program_trainer', 'ld_program_participant', 'ld_program_assessment'] as $table) {
                if (!$this->tableExists($table)) {
                    return $this->schemaReadyCache = false;
                }
            }

            return $this->schemaReadyCache = true;
        } catch (Throwable $e) {
            error_log('Program::schemaReady failed: ' . $e->getMessage());
            return $this->schemaReadyCache = false;
        }
    }

    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            if ($this->schemaReady()) {
                $sql = "SELECT p.*,
                               CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) AS owner_name,
                               (SELECT COUNT(*) FROM ld_program_course pc WHERE pc.program_id = p.id) AS course_count,
                               (SELECT COUNT(*) FROM ld_program_trainer pt WHERE pt.program_id = p.id) AS trainer_count,
                               (SELECT COUNT(*) FROM ld_program_participant pp WHERE pp.program_id = p.id AND pp.status <> 'withdrawn') AS participant_count,
                               (SELECT COUNT(*) FROM ld_program_assessment pa WHERE pa.program_id = p.id AND pa.status = 'completed') AS assessed_count
                        FROM ld_program p
                        LEFT JOIN em_employees e ON e.employee_id = p.instructor_id
                        WHERE p.id = :id
                        LIMIT 1";
            } else {
                $sql = 'SELECT id, instructor_id, title, description, status, created_at, updated_at FROM ld_program WHERE id = :id LIMIT 1';
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $program = $stmt->fetch(PDO::FETCH_ASSOC);

            return $program ?: null;
        } catch (Throwable $e) {
            error_log('Program::getById failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getList(): array
    {
        try {
            if ($this->schemaReady()) {
                $sql = "SELECT p.*,
                               (SELECT COUNT(*) FROM ld_program_trainer pt WHERE pt.program_id = p.id) AS trainer_count,
                               (SELECT COUNT(*) FROM ld_program_participant pp WHERE pp.program_id = p.id AND pp.status <> 'withdrawn') AS participant_count
                        FROM ld_program p
                        ORDER BY p.title ASC";
            } else {
                $sql = 'SELECT id, title, description, status FROM ld_program ORDER BY title ASC';
            }

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getList failed: ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $input, int $instructorId = 0): array
    {
        $instructorId = (int) ($input['instructor_id'] ?? $instructorId);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'active'));
        $learningRole = strtolower((string) ($input['learning_role'] ?? ''));
        $isAdmin = $learningRole === 'admin' || !empty($input['is_admin']) || !empty($input['admin_access']);

        if ($instructorId <= 0 && !$isAdmin) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Program title is required.'];
        }
        if (mb_strlen($title) > 255) {
            return ['success' => false, 'message' => 'Program title must be 255 characters or fewer.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $deliveryMode = $this->normaliseDeliveryMode($input['delivery_mode'] ?? 'face_to_face');
        $startsAt = $this->normaliseDateTime($input['starts_at'] ?? null);
        $endsAt = $this->normaliseDateTime($input['ends_at'] ?? null);
        $expiresAt = $this->normaliseDateTime($input['expires_at'] ?? null);
        $location = trim((string) ($input['location'] ?? ''));

        $dateError = $this->validateDates($startsAt, $endsAt, $expiresAt);
        if ($dateError !== '') {
            return ['success' => false, 'message' => $dateError];
        }

        try {
            $this->conn->beginTransaction();

            if ($this->schemaReady()) {
                $sql = 'INSERT INTO ld_program
                            (instructor_id, title, description, status, delivery_mode, starts_at, ends_at, expires_at, location, run_number)
                        VALUES
                            (:instructor_id, :title, :description, :status, :delivery_mode, :starts_at, :ends_at, :expires_at, :location, 1)';
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':instructor_id' => $instructorId > 0 ? $instructorId : null,
                    ':title' => $title,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status,
                    ':delivery_mode' => $deliveryMode,
                    ':starts_at' => $startsAt,
                    ':ends_at' => $endsAt,
                    ':expires_at' => $expiresAt,
                    ':location' => $location !== '' ? $location : null,
                ]);
            } else {
                $stmt = $this->conn->prepare('INSERT INTO ld_program (instructor_id, title, description, status) VALUES (:instructor_id, :title, :description, :status)');
                $stmt->execute([
                    ':instructor_id' => $instructorId > 0 ? $instructorId : null,
                    ':title' => $title,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status,
                ]);
            }

            $programId = (int) $this->conn->lastInsertId();
            $warnings = [];

            if ($this->schemaReady()) {
                $warnings = $this->syncAssociations($programId, $input, $instructorId, $instructorId);
                $warnings = array_merge($warnings, $this->syncOnlineTraining($programId, $input, $deliveryMode, $startsAt));
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Program created successfully.',
                'id' => $programId,
                'warnings' => $warnings,
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Program::create failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create program.', 'error' => $e->getMessage()];
        }
    }

    public function update(array $input): array
    {
        $programId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($programId <= 0) {
            return ['success' => false, 'message' => 'Program ID is required.'];
        }
        if ($title === '') {
            return ['success' => false, 'message' => 'Program title is required.'];
        }
        if (mb_strlen($title) > 255) {
            return ['success' => false, 'message' => 'Program title must be 255 characters or fewer.'];
        }
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $existing = $this->getById($programId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Program not found.'];
        }

        $deliveryMode = $this->normaliseDeliveryMode($input['delivery_mode'] ?? ($existing['delivery_mode'] ?? 'face_to_face'));
        $startsAt = $this->normaliseDateTime($input['starts_at'] ?? null);
        $endsAt = $this->normaliseDateTime($input['ends_at'] ?? null);
        $expiresAt = $this->normaliseDateTime($input['expires_at'] ?? null);
        $location = trim((string) ($input['location'] ?? ''));

        $dateError = $this->validateDates($startsAt, $endsAt, $expiresAt);
        if ($dateError !== '') {
            return ['success' => false, 'message' => $dateError];
        }

        $actorId = (int) ($input['actor_id'] ?? $input['instructor_id'] ?? $_SESSION['employee_id'] ?? 0);
        $ownerId = (int) ($existing['instructor_id'] ?? 0);

        try {
            $this->conn->beginTransaction();

            if ($this->schemaReady()) {
                $stmt = $this->conn->prepare('UPDATE ld_program
                    SET title = :title,
                        description = :description,
                        status = :status,
                        delivery_mode = :delivery_mode,
                        starts_at = :starts_at,
                        ends_at = :ends_at,
                        expires_at = :expires_at,
                        location = :location,
                        updated_at = NOW()
                    WHERE id = :id');
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status,
                    ':delivery_mode' => $deliveryMode,
                    ':starts_at' => $startsAt,
                    ':ends_at' => $endsAt,
                    ':expires_at' => $expiresAt,
                    ':location' => $location !== '' ? $location : null,
                    ':id' => $programId,
                ]);
            } else {
                $stmt = $this->conn->prepare('UPDATE ld_program SET title = :title, description = :description, status = :status, updated_at = NOW() WHERE id = :id');
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status,
                    ':id' => $programId,
                ]);
            }

            $warnings = [];
            if ($this->schemaReady()) {
                $warnings = $this->syncAssociations($programId, $input, $actorId, $ownerId);
                $warnings = array_merge($warnings, $this->syncOnlineTraining($programId, $input, $deliveryMode, $startsAt));
            }

            $this->conn->commit();

            return [
                'success' => true,
                'id' => $programId,
                'message' => 'Program updated successfully.',
                'warnings' => $warnings,
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Program::update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update program.', 'error' => $e->getMessage()];
        }
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Program ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_program SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Program archived successfully'];
    }

    /**
     * Clone a program with new dates and copy its courses, skills, trainers
     * and active participants. Assessments and online sessions are not copied
     * so the new run starts with a clean evaluation sheet and meeting details.
     */
    public function rerun(int $programId, array $input = []): array
    {
        $source = $this->getById($programId);
        if (!$source) {
            return ['success' => false, 'message' => 'Program not found.'];
        }
        if (!$this->schemaReady()) {
            return ['success' => false, 'message' => 'Apply the program database update before re-running a program.'];
        }

        $actorId = (int) ($input['actor_id'] ?? $_SESSION['employee_id'] ?? 0);
        $newOwnerId = (int) ($source['instructor_id'] ?? 0);
        if ($newOwnerId <= 0) {
            $newOwnerId = $actorId;
        }
        $rootId = (int) ($source['parent_program_id'] ?? 0);
        if ($rootId <= 0) {
            $rootId = $programId;
        }

        $title = trim((string) ($input['title'] ?? ''));
        $nextRun = $this->nextRunNumber($rootId);
        if ($title === '') {
            $title = trim((string) $source['title']) . ' (Re-run ' . $nextRun . ')';
        }
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        $startsAt = $this->normaliseDateTime($input['starts_at'] ?? null);
        $endsAt = $this->normaliseDateTime($input['ends_at'] ?? null);
        $expiresAt = $this->normaliseDateTime($input['expires_at'] ?? null);

        if ($startsAt === null) {
            $startsAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        }
        if ($endsAt === null) {
            $duration = 0;
            if (!empty($source['starts_at']) && !empty($source['ends_at'])) {
                $duration = max(0, strtotime((string) $source['ends_at']) - strtotime((string) $source['starts_at']));
            }
            $endsAt = date('Y-m-d H:i:s', strtotime($startsAt) + ($duration > 0 ? $duration : 86400));
        }
        if ($expiresAt === null) {
            $duration = 0;
            if (!empty($source['starts_at']) && !empty($source['expires_at'])) {
                $duration = max(0, strtotime((string) $source['expires_at']) - strtotime((string) $source['starts_at']));
            }
            $expiresAt = date('Y-m-d H:i:s', strtotime($startsAt) + ($duration > 0 ? $duration : 30 * 86400));
        }

        $dateError = $this->validateDates($startsAt, $endsAt, $expiresAt);
        if ($dateError !== '') {
            return ['success' => false, 'message' => $dateError];
        }

        $courseIds = $this->normaliseIdList($this->getCourses($programId), 'id');
        $skillIds = $this->normaliseIdList($this->getSkills($programId), 'id');
        $trainerIds = $this->normaliseIdList($this->getTrainers($programId), 'employee_id');
        $participantIds = $this->normaliseIdList($this->getParticipants($programId), 'employee_id');
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare('INSERT INTO ld_program
                (instructor_id, title, description, status, delivery_mode, starts_at, ends_at, expires_at, location, parent_program_id, run_number)
                VALUES
                (:instructor_id, :title, :description, :status, :delivery_mode, :starts_at, :ends_at, :expires_at, :location, :parent_program_id, :run_number)');
            $stmt->execute([
                ':instructor_id' => $newOwnerId > 0 ? $newOwnerId : null,
                ':title' => $title,
                ':description' => $source['description'] ?: null,
                ':status' => 'active',
                ':delivery_mode' => $this->normaliseDeliveryMode($source['delivery_mode'] ?? 'face_to_face'),
                ':starts_at' => $startsAt,
                ':ends_at' => $endsAt,
                ':expires_at' => $expiresAt,
                ':location' => $source['location'] ?: null,
                ':parent_program_id' => $rootId,
                ':run_number' => $nextRun,
            ]);

            $newId = (int) $this->conn->lastInsertId();

            $this->syncAssociations($newId, [
                'course_ids' => $courseIds,
                'skill_ids' => $skillIds,
                'trainer_ids' => $trainerIds,
                'participant_ids' => $participantIds,
            ], $actorId, $newOwnerId);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Program re-run created successfully.',
                'id' => $newId,
                'redirect' => '?page=instructor/training-subpage/program&id=' . $newId,
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Program::rerun failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to re-run program.', 'error' => $e->getMessage()];
        }
    }

    public function getCourses(int $programId): array
    {
        if ($programId <= 0 || !$this->tableExists('ld_program_course')) {
            return [];
        }

        try {
            $stmt = $this->conn->prepare('SELECT c.id, c.title, c.status, c.delivery_mode
                FROM ld_program_course pc
                JOIN ld_course c ON c.id = pc.course_id
                WHERE pc.program_id = :program_id
                ORDER BY c.title ASC');
            $stmt->execute([':program_id' => $programId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getCourses failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSkills(int $programId): array
    {
        if ($programId <= 0) {
            return [];
        }

        try {
            $stmt = $this->conn->prepare('SELECT s.id, s.name
                FROM ld_program_skill ps
                JOIN ld_skill s ON s.id = ps.skill_id
                WHERE ps.program_id = :program_id
                ORDER BY s.name ASC');
            $stmt->execute([':program_id' => $programId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getSkills failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getTrainers(int $programId): array
    {
        if ($programId <= 0 || !$this->tableExists('ld_program_trainer')) {
            return [];
        }

        try {
            $stmt = $this->conn->prepare("SELECT e.employee_id, e.email,
                    CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) AS full_name,
                    d.department_name, p.position_name
                FROM ld_program_trainer pt
                JOIN em_employees e ON e.employee_id = pt.trainer_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE pt.program_id = :program_id
                ORDER BY e.last_name ASC, e.first_name ASC");
            $stmt->execute([':program_id' => $programId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getTrainers failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getParticipants(int $programId): array
    {
        if ($programId <= 0 || !$this->tableExists('ld_program_participant')) {
            return [];
        }

        try {
            $stmt = $this->conn->prepare("SELECT pp.id AS participant_id, pp.employee_id, pp.status AS participant_status,
                    pp.enrolled_at, pp.completed_at,
                    e.email,
                    CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) AS full_name,
                    d.department_name, p.position_name,
                    pa.id AS assessment_id, pa.attendance, pa.knowledge_score, pa.skills_score,
                    pa.participation_score, pa.overall_score, pa.result, pa.feedback,
                    pa.recommendations, pa.status AS assessment_status, pa.assessed_at,
                    pa.trainer_id,
                    CONCAT_WS(' ', t.first_name, NULLIF(t.middle_name, ''), t.last_name) AS trainer_name
                FROM ld_program_participant pp
                JOIN em_employees e ON e.employee_id = pp.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                LEFT JOIN ld_program_assessment pa
                    ON pa.program_id = pp.program_id AND pa.employee_id = pp.employee_id
                LEFT JOIN em_employees t ON t.employee_id = pa.trainer_id
                WHERE pp.program_id = :program_id AND pp.status <> 'withdrawn'
                ORDER BY e.last_name ASC, e.first_name ASC");
            $stmt->execute([':program_id' => $programId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getParticipants failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getVideoConference(int $programId): ?array
    {
        if ($programId <= 0 || !$this->tableExists('ld_video_conference')) {
            return null;
        }

        try {
            $stmt = $this->conn->prepare('SELECT * FROM ld_video_conference WHERE program_id = :program_id ORDER BY id DESC LIMIT 1');
            $stmt->execute([':program_id' => $programId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Program::getVideoConference failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getAssessmentSummary(int $programId): array
    {
        $summary = [
            'total' => 0,
            'assessed' => 0,
            'passed' => 0,
            'failed' => 0,
            'pending' => 0,
            'average_score' => null,
        ];

        if ($programId <= 0 || !$this->tableExists('ld_program_participant') || !$this->tableExists('ld_program_assessment')) {
            return $summary;
        }

        try {
            $stmt = $this->conn->prepare("SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN pa.status = 'completed' THEN 1 ELSE 0 END) AS assessed,
                    SUM(CASE WHEN pa.result = 'passed' THEN 1 ELSE 0 END) AS passed,
                    SUM(CASE WHEN pa.result = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN pa.result = 'pending' OR pa.id IS NULL THEN 1 ELSE 0 END) AS pending,
                    AVG(pa.overall_score) AS average_score
                FROM ld_program_participant pp
                LEFT JOIN ld_program_assessment pa
                    ON pa.program_id = pp.program_id AND pa.employee_id = pp.employee_id
                WHERE pp.program_id = :program_id AND pp.status <> 'withdrawn'");
            $stmt->execute([':program_id' => $programId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $summary['total'] = (int) ($row['total'] ?? 0);
                $summary['assessed'] = (int) ($row['assessed'] ?? 0);
                $summary['passed'] = (int) ($row['passed'] ?? 0);
                $summary['failed'] = (int) ($row['failed'] ?? 0);
                $summary['pending'] = (int) ($row['pending'] ?? 0);
                $summary['average_score'] = $row['average_score'] !== null ? (float) $row['average_score'] : null;
            }
        } catch (Throwable $e) {
            error_log('Program::getAssessmentSummary failed: ' . $e->getMessage());
        }

        return $summary;
    }

    public function canEvaluate(int $programId, int $employeeId, bool $isAdmin = false): bool
    {
        if ($programId <= 0 || $employeeId <= 0) {
            return false;
        }
        if ($isAdmin) {
            return true;
        }

        try {
            $stmt = $this->conn->prepare('SELECT 1 FROM ld_program WHERE id = :program_id AND instructor_id = :employee_id LIMIT 1');
            $stmt->execute([':program_id' => $programId, ':employee_id' => $employeeId]);
            if ($stmt->fetchColumn()) {
                return true;
            }

            if ($this->tableExists('ld_program_trainer')) {
                $stmt = $this->conn->prepare('SELECT 1 FROM ld_program_trainer WHERE program_id = :program_id AND trainer_id = :employee_id LIMIT 1');
                $stmt->execute([':program_id' => $programId, ':employee_id' => $employeeId]);
                return (bool) $stmt->fetchColumn();
            }
        } catch (Throwable $e) {
            error_log('Program::canEvaluate failed: ' . $e->getMessage());
        }

        return false;
    }

    public function getAccessiblePrograms(int $employeeId, bool $isAdmin = false): array
    {
        try {
            if ($this->schemaReady()) {
                $sql = "SELECT p.*,
                               (SELECT COUNT(*) FROM ld_program_participant pp WHERE pp.program_id = p.id AND pp.status <> 'withdrawn') AS participant_count,
                               (SELECT COUNT(*) FROM ld_program_assessment pa WHERE pa.program_id = p.id AND pa.status = 'completed') AS assessed_count,
                               (SELECT COUNT(*) FROM ld_program_trainer pt WHERE pt.program_id = p.id) AS trainer_count
                        FROM ld_program p
                        WHERE 1 = 1";
                $params = [];
                if (!$isAdmin) {
                    $sql .= ' AND (p.instructor_id = :owner_id OR EXISTS (
                                SELECT 1 FROM ld_program_trainer pt2
                                WHERE pt2.program_id = p.id AND pt2.trainer_id = :trainer_id
                            ))';
                    $params[':owner_id'] = $employeeId;
                    $params[':trainer_id'] = $employeeId;
                }
                $sql .= " ORDER BY CASE WHEN p.status = 'active' THEN 0 ELSE 1 END, p.starts_at IS NULL, p.starts_at ASC, p.title ASC";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt = $this->conn->prepare('SELECT id, instructor_id, title, description, status, created_at, updated_at FROM ld_program ORDER BY title ASC');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getAccessiblePrograms failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * All active employees, used by the trainee picker.
     */
    public function getEmployeeDirectory(): array
    {
        try {
            $stmt = $this->conn->query("SELECT e.employee_id, e.email,
                    CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) AS full_name,
                    d.department_name, p.position_name
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.employment_status = 'Active' AND COALESCE(e.is_archived, 0) = 0
                ORDER BY e.last_name ASC, e.first_name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Program::getEmployeeDirectory failed: ' . $e->getMessage());
            return [];
        }
    }

    private function syncAssociations(int $programId, array $input, int $actorId, int $ownerId): array
    {
        $warnings = [];

        $courseIds = $this->existingIds('ld_course', 'id', $this->normaliseIdList($input['course_ids'] ?? []));
        $skillIds = $this->existingIds('ld_skill', 'id', $this->normaliseIdList($input['skill_ids'] ?? []));
        $trainerIds = $this->existingIds('em_employees', 'employee_id', $this->normaliseIdList($input['trainer_ids'] ?? []));
        $participantIds = $this->existingIds('em_employees', 'employee_id', $this->normaliseIdList($input['participant_ids'] ?? []));

        if ($ownerId > 0 && !in_array($ownerId, $trainerIds, true)) {
            $trainerIds[] = $ownerId;
        }

        if ($this->tableExists('ld_program_course')) {
            $this->conn->prepare('DELETE FROM ld_program_course WHERE program_id = :program_id')->execute([':program_id' => $programId]);
            $insert = $this->conn->prepare('INSERT IGNORE INTO ld_program_course (program_id, course_id) VALUES (:program_id, :course_id)');
            foreach ($courseIds as $courseId) {
                $insert->execute([':program_id' => $programId, ':course_id' => $courseId]);
            }
        } else {
            $warnings[] = 'Program courses could not be saved because ld_program_course is missing.';
        }

        try {
            $this->conn->prepare('DELETE FROM ld_program_skill WHERE program_id = :program_id')->execute([':program_id' => $programId]);
            $insert = $this->conn->prepare('INSERT IGNORE INTO ld_program_skill (program_id, skill_id) VALUES (:program_id, :skill_id)');
            foreach ($skillIds as $skillId) {
                $insert->execute([':program_id' => $programId, ':skill_id' => $skillId]);
            }
        } catch (Throwable $e) {
            error_log('Program skill sync failed: ' . $e->getMessage());
            $warnings[] = 'Program skills could not be saved.';
        }

        if ($this->tableExists('ld_program_trainer')) {
            $this->conn->prepare('DELETE FROM ld_program_trainer WHERE program_id = :program_id')->execute([':program_id' => $programId]);
            $insert = $this->conn->prepare('INSERT IGNORE INTO ld_program_trainer (program_id, trainer_id, assigned_by) VALUES (:program_id, :trainer_id, :assigned_by)');
            foreach ($trainerIds as $trainerId) {
                $insert->execute([
                    ':program_id' => $programId,
                    ':trainer_id' => $trainerId,
                    ':assigned_by' => $actorId > 0 ? $actorId : null,
                ]);
            }
        } else {
            $warnings[] = 'Program trainers could not be saved because ld_program_trainer is missing.';
        }

        if ($this->tableExists('ld_program_participant')) {
            $existingStmt = $this->conn->prepare("SELECT employee_id FROM ld_program_participant WHERE program_id = :program_id AND status <> 'withdrawn'");
            $existingStmt->execute([':program_id' => $programId]);
            $existingIds = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN));
            $removedIds = array_values(array_diff($existingIds, $participantIds));

            if ($removedIds) {
                $placeholders = implode(',', array_fill(0, count($removedIds), '?'));
                $params = array_merge([$programId], $removedIds);
                $this->conn->prepare("UPDATE ld_program_participant SET status = 'withdrawn' WHERE program_id = ? AND employee_id IN ($placeholders)")->execute($params);
            }

            $insert = $this->conn->prepare("INSERT INTO ld_program_participant (program_id, employee_id, status, added_by)
                VALUES (:program_id, :employee_id, 'enrolled', :added_by)
                ON DUPLICATE KEY UPDATE
                    completed_at = IF(status = 'withdrawn', NULL, completed_at),
                    status = IF(status = 'withdrawn', 'enrolled', status)");
            foreach ($participantIds as $participantId) {
                $insert->execute([
                    ':program_id' => $programId,
                    ':employee_id' => $participantId,
                    ':added_by' => $actorId > 0 ? $actorId : null,
                ]);
            }

            if (!empty($input['enroll_participants']) && $courseIds) {
                $enrol = $this->conn->prepare("INSERT IGNORE INTO ld_enrollment
                    (learner_id, course_id, status, invited_by, enrolled_at)
                    VALUES (:learner_id, :course_id, 'enrolled', :invited_by, NOW())");
                foreach ($courseIds as $courseId) {
                    foreach ($participantIds as $participantId) {
                        $enrol->execute([
                            ':learner_id' => $participantId,
                            ':course_id' => $courseId,
                            ':invited_by' => $actorId > 0 ? $actorId : null,
                        ]);
                    }
                }
            }
        } else {
            $warnings[] = 'Trainees could not be saved because ld_program_participant is missing.';
        }

        return $warnings;
    }

    private function syncOnlineTraining(int $programId, array $input, string $deliveryMode, ?string $startsAt): array
    {
        if (!$this->tableExists('ld_video_conference')) {
            return $deliveryMode === 'online'
                ? ['Online training was not saved because ld_video_conference is missing.']
                : [];
        }

        $existing = $this->getVideoConference($programId);
        if ($deliveryMode !== 'online') {
            if ($existing) {
                try {
                    $this->conn->prepare("UPDATE ld_video_conference SET status = 'archived', updated_at = NOW() WHERE program_id = :program_id")
                        ->execute([':program_id' => $programId]);
                } catch (Throwable $e) {
                    error_log('Program online training archival failed: ' . $e->getMessage());
                    return ['The linked online training session could not be archived.'];
                }
            }
            return [];
        }

        $title = trim((string) ($input['online_title'] ?? ''));
        $meetingLink = trim((string) ($input['online_meeting_link'] ?? ''));
        $scheduledAt = $this->normaliseDateTime($input['online_scheduled_at'] ?? null);
        $duration = (int) ($input['online_duration_minutes'] ?? 0);
        $platform = strtolower(trim((string) ($input['online_platform'] ?? 'google_meet')));
        $status = strtolower(trim((string) ($input['online_status'] ?? 'scheduled')));

        if ($title === '' && $existing) {
            $title = (string) $existing['title'];
        }
        if ($title === '') {
            $title = trim((string) ($input['title'] ?? ''));
        }
        if ($meetingLink === '' && $existing) {
            $meetingLink = (string) $existing['meeting_link'];
        }
        if ($scheduledAt === null && $existing) {
            $scheduledAt = (string) $existing['scheduled_at'];
        }
        if ($scheduledAt === null) {
            $scheduledAt = $startsAt;
        }
        if ($duration <= 0 && $existing) {
            $duration = (int) ($existing['duration_minutes'] ?? 0);
        }
        if (!in_array($platform, ['zoom', 'google_meet', 'other'], true)) {
            $platform = 'google_meet';
        }
        if (!in_array($status, ['scheduled', 'completed', 'archived'], true)) {
            $status = 'scheduled';
        }

        $hasAttempt = $existing || $title !== '' || $meetingLink !== '' || $scheduledAt !== null;
        if (!$hasAttempt) {
            return [];
        }
        if ($title === '' || $meetingLink === '' || $scheduledAt === null) {
            return ['Online training was not saved: enter a title, meeting link and schedule.'];
        }

        try {
            if ($existing) {
                $stmt = $this->conn->prepare('UPDATE ld_video_conference
                    SET title = :title, platform = :platform, meeting_link = :meeting_link,
                        scheduled_at = :scheduled_at, duration_minutes = :duration_minutes,
                        status = :status, updated_at = NOW()
                    WHERE id = :id');
                $stmt->execute([
                    ':title' => $title,
                    ':platform' => $platform,
                    ':meeting_link' => $meetingLink,
                    ':scheduled_at' => $scheduledAt,
                    ':duration_minutes' => $duration > 0 ? $duration : null,
                    ':status' => $status,
                    ':id' => (int) $existing['id'],
                ]);
            } else {
                $stmt = $this->conn->prepare('INSERT INTO ld_video_conference
                    (instructor_id, course_id, program_id, title, platform, meeting_link, scheduled_at, duration_minutes, status)
                    VALUES (:instructor_id, :course_id, :program_id, :title, :platform, :meeting_link, :scheduled_at, :duration_minutes, :status)');
                $stmt->execute([
                    ':instructor_id' => (int) ($_SESSION['employee_id'] ?? 0) ?: (int) ($input['instructor_id'] ?? 0),
                    ':course_id' => null,
                    ':program_id' => $programId,
                    ':title' => $title,
                    ':platform' => $platform,
                    ':meeting_link' => $meetingLink,
                    ':scheduled_at' => $scheduledAt,
                    ':duration_minutes' => $duration > 0 ? $duration : null,
                    ':status' => $status,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Program online training sync failed: ' . $e->getMessage());
            return ['The online training details could not be saved.'];
        }

        return [];
    }

    private function nextRunNumber(int $rootId): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COALESCE(MAX(run_number), 1) FROM ld_program WHERE id = :root_id OR parent_program_id = :root_id');
            $stmt->execute([':root_id' => $rootId]);
            return max(1, (int) $stmt->fetchColumn()) + 1;
        } catch (Throwable $e) {
            return 2;
        }
    }

    private function validateDates(?string $startsAt, ?string $endsAt, ?string $expiresAt): string
    {
        $start = $startsAt ? strtotime($startsAt) : false;
        $end = $endsAt ? strtotime($endsAt) : false;
        $expiry = $expiresAt ? strtotime($expiresAt) : false;

        if ($startsAt !== null && $start === false) {
            return 'The start date and time is invalid.';
        }
        if ($endsAt !== null && $end === false) {
            return 'The end date and time is invalid.';
        }
        if ($expiresAt !== null && $expiry === false) {
            return 'The expiry date and time is invalid.';
        }
        if ($start !== false && $end !== false && $end < $start) {
            return 'The program end must be after its start.';
        }
        if ($start !== false && $expiry !== false && $expiry < $start) {
            return 'The expiry date must be after the program start.';
        }

        return '';
    }

    private function normaliseDateTime($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function normaliseDeliveryMode($value): string
    {
        $mode = strtolower(trim((string) $value));
        return in_array($mode, ['face_to_face', 'online'], true) ? $mode : 'face_to_face';
    }

    private function normaliseIdList($value, ?string $column = null): array
    {
        if (is_array($value) && $column !== null) {
            $value = array_column($value, $column);
        }
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : explode(',', (string) $value);
        }

        $ids = [];
        foreach ($value as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function existingIds(string $table, string $column, array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $allowed = [
            'ld_course' => ['id'],
            'ld_skill' => ['id'],
            'em_employees' => ['employee_id'],
        ];
        if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->conn->prepare("SELECT `$column` FROM `$table` WHERE `$column` IN ($placeholders)");
            $stmt->execute($ids);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            error_log('Program::existingIds failed: ' . $e->getMessage());
            return [];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}
