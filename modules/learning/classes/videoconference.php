<?php

include_once __DIR__ . '/../../../database/db.php';

class VideoConference
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
        $sql = 'SELECT id, instructor_id, course_id, program_id, title, platform, meeting_link, scheduled_at, duration_minutes, status, created_at, updated_at FROM ld_video_conference WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $conference = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conference ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, title, platform, meeting_link, scheduled_at, duration_minutes, status FROM ld_video_conference ORDER BY scheduled_at DESC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $conferenceId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $platform = trim((string) ($input['platform'] ?? 'google_meet'));
        $meetingLink = trim((string) ($input['meeting_link'] ?? ''));
        $scheduledAt = trim((string) ($input['scheduled_at'] ?? ''));
        $durationMinutes = isset($input['duration_minutes']) && $input['duration_minutes'] !== '' ? (int) $input['duration_minutes'] : null;
        $status = trim((string) ($input['status'] ?? 'scheduled'));
        $courseId = isset($input['course_id']) && $input['course_id'] !== '' ? (int) $input['course_id'] : null;
        $programId = isset($input['program_id']) && $input['program_id'] !== '' ? (int) $input['program_id'] : null;

        if ($conferenceId <= 0) {
            return ['success' => false, 'message' => 'Video conference ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Conference title is required.'];
        }

        if ($meetingLink === '') {
            return ['success' => false, 'message' => 'Meeting link is required.'];
        }

        if ($scheduledAt === '') {
            return ['success' => false, 'message' => 'Scheduled date/time is required.'];
        }

        if (!in_array($platform, ['zoom', 'google_meet', 'other'], true)) {
            $platform = 'google_meet';
        }

        if (!in_array($status, ['scheduled', 'completed', 'archived'], true)) {
            $status = 'scheduled';
        }

        $stmt = $this->conn->prepare('UPDATE ld_video_conference SET title = :title, platform = :platform, meeting_link = :meeting_link, scheduled_at = :scheduled_at, duration_minutes = :duration_minutes, status = :status, course_id = :course_id, program_id = :program_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':platform' => $platform,
            ':meeting_link' => $meetingLink,
            ':scheduled_at' => $scheduledAt,
            ':duration_minutes' => $durationMinutes,
            ':status' => $status,
            ':course_id' => $courseId,
            ':program_id' => $programId,
            ':id' => $conferenceId,
        ]);

        return ['success' => true, 'id' => $conferenceId, 'message' => 'Video conference updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Video conference ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_video_conference SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Video conference archived successfully'];
    }

    public function create(array $input, int $instructorId = 0): array
    {
        $instructorId = (int) ($input['instructor_id'] ?? $instructorId);
        $courseId = isset($input['course_id']) && $input['course_id'] !== '' ? (int) $input['course_id'] : null;
        $programId = isset($input['program_id']) && $input['program_id'] !== '' ? (int) $input['program_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $platform = trim((string) ($input['platform'] ?? 'google_meet'));
        $meetingLink = trim((string) ($input['meeting_link'] ?? ''));
        $scheduledAt = trim((string) ($input['scheduled_at'] ?? ''));
        $durationMinutes = isset($input['duration_minutes']) && $input['duration_minutes'] !== '' ? (int) $input['duration_minutes'] : null;
        $status = trim((string) ($input['status'] ?? 'scheduled'));
        $learningRole = strtolower((string) ($input['learning_role'] ?? ''));
        $isAdmin = $learningRole === 'admin' || !empty($input['is_admin']) || !empty($input['admin_access']);

        if ($instructorId <= 0 && !$isAdmin) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Conference title is required.'];
        }

        if ($meetingLink === '') {
            return ['success' => false, 'message' => 'Meeting link is required.'];
        }

        if ($scheduledAt === '') {
            return ['success' => false, 'message' => 'Scheduled date/time is required.'];
        }

        if (!in_array($platform, ['zoom', 'google_meet', 'other'], true)) {
            $platform = 'google_meet';
        }

        if (!in_array($status, ['scheduled', 'completed', 'archived'], true)) {
            $status = 'scheduled';
        }

        $sql = 'INSERT INTO ld_video_conference (
                    instructor_id,
                    course_id,
                    program_id,
                    title,
                    platform,
                    meeting_link,
                    scheduled_at,
                    duration_minutes,
                    status
                ) VALUES (
                    :instructor_id,
                    :course_id,
                    :program_id,
                    :title,
                    :platform,
                    :meeting_link,
                    :scheduled_at,
                    :duration_minutes,
                    :status
                )';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':course_id' => $courseId,
            ':program_id' => $programId,
            ':title' => $title,
            ':platform' => $platform,
            ':meeting_link' => $meetingLink,
            ':scheduled_at' => $scheduledAt,
            ':duration_minutes' => $durationMinutes,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Video conference created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
