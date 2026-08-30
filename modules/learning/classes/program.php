<?php

include_once __DIR__ . '/../../../database/db.php';

class Program
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
        $sql = 'SELECT id, instructor_id, title, description, status, created_at, updated_at FROM ld_program WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $program = $stmt->fetch(PDO::FETCH_ASSOC);

        return $program ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, title, description, status FROM ld_program ORDER BY title ASC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $programId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = $input['description'] ?? null;
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($programId <= 0) {
            return ['success' => false, 'message' => 'Program ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Program title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_program SET title = :title, description = :description, status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
            ':id' => $programId,
        ]);

        return ['success' => true, 'id' => $programId, 'message' => 'Program updated successfully'];
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

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'INSERT INTO ld_program (instructor_id, title, description, status) VALUES (:instructor_id, :title, :description, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Program created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
