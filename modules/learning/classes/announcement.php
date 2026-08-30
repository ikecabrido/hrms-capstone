<?php

include_once __DIR__ . '/../../../database/db.php';

class Announcement
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
        $sql = 'SELECT id, title, message, audience, posted_by, status, created_at, expires_at FROM ld_announcement WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

        return $announcement ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, title, message, audience, posted_by, status, created_at, expires_at FROM ld_announcement WHERE status = :status ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => 'active']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAudience(string $audience): array
    {
        $sql = 'SELECT id, title, message, audience, posted_by, status, created_at, expires_at FROM ld_announcement WHERE status = :status AND (audience = :audience OR audience = :all) ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':status' => 'active',
            ':audience' => $audience,
            ':all' => 'all',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $input, int $postedBy = 0): array
    {
        $postedBy = (int) ($input['posted_by'] ?? $postedBy);
        $title = trim((string) ($input['title'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));
        $audience = trim((string) ($input['audience'] ?? 'all'));
        $expiresAt = isset($input['expires_at']) && $input['expires_at'] !== '' ? $input['expires_at'] : null;

        if ($postedBy <= 0) {
            return ['success' => false, 'message' => 'Posted by user ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Announcement title is required.'];
        }

        if ($message === '') {
            return ['success' => false, 'message' => 'Announcement message is required.'];
        }

        if (!in_array($audience, ['all', 'instructor', 'learner', 'admin'], true)) {
            $audience = 'all';
        }

        $sql = 'INSERT INTO ld_announcement (title, message, audience, posted_by, expires_at, status) VALUES (:title, :message, :audience, :posted_by, :expires_at, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':audience' => $audience,
            ':posted_by' => $postedBy,
            ':expires_at' => $expiresAt,
            ':status' => 'active',
        ]);

        return [
            'success' => true,
            'message' => 'Announcement created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function update(array $input): array
    {
        $announcementId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));
        $audience = trim((string) ($input['audience'] ?? 'all'));
        $expiresAt = isset($input['expires_at']) && $input['expires_at'] !== '' ? $input['expires_at'] : null;
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($announcementId <= 0) {
            return ['success' => false, 'message' => 'Announcement ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Announcement title is required.'];
        }

        if ($message === '') {
            return ['success' => false, 'message' => 'Announcement message is required.'];
        }

        if (!in_array($audience, ['all', 'instructor', 'learner', 'admin'], true)) {
            $audience = 'all';
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_announcement SET title = :title, message = :message, audience = :audience, expires_at = :expires_at, status = :status WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':audience' => $audience,
            ':expires_at' => $expiresAt,
            ':status' => $status,
            ':id' => $announcementId,
        ]);

        return ['success' => true, 'id' => $announcementId, 'message' => 'Announcement updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Announcement ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_announcement SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Announcement archived successfully'];
    }
}
