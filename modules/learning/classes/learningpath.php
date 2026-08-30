<?php

include_once __DIR__ . '/../../../database/db.php';

class LearningPath
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
        $sql = 'SELECT id, instructor_id, title, description, assigned_to, status, created_at, updated_at FROM ld_learning_path WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $path = $stmt->fetch(PDO::FETCH_ASSOC);

        return $path ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, title, description, assigned_to, status FROM ld_learning_path ORDER BY title ASC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $pathId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = $input['description'] ?? null;
        $status = trim((string) ($input['status'] ?? 'active'));
        $assignedTo = isset($input['assigned_to']) && $input['assigned_to'] !== '' ? (int) $input['assigned_to'] : null;

        if ($pathId <= 0) {
            return ['success' => false, 'message' => 'Learning path ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Learning path title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_learning_path SET title = :title, description = :description, status = :status, assigned_to = :assigned_to, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
            ':assigned_to' => $assignedTo,
            ':id' => $pathId,
        ]);

        return ['success' => true, 'id' => $pathId, 'message' => 'Learning path updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Learning path ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_learning_path SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Learning path archived successfully'];
    }

    public function create(array $input, int $instructorId = 0): array
    {
        $instructorId = (int) ($input['instructor_id'] ?? $instructorId);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $assignedTo = isset($input['assigned_to']) && $input['assigned_to'] !== '' ? (int) $input['assigned_to'] : null;
        $status = trim((string) ($input['status'] ?? 'active'));
        $learningRole = strtolower((string) ($input['learning_role'] ?? ''));
        $isAdmin = $learningRole === 'admin' || !empty($input['is_admin']) || !empty($input['admin_access']);

        if ($instructorId <= 0 && !$isAdmin) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Learning path title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'INSERT INTO ld_learning_path (instructor_id, title, description, assigned_to, status) VALUES (:instructor_id, :title, :description, :assigned_to, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':title' => $title,
            ':description' => $description,
            ':assigned_to' => $assignedTo,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Learning path created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
