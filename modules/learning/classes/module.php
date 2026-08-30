<?php

include_once __DIR__ . '/../../../database/db.php';

class Module
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
        $sql = 'SELECT id, course_id, title, description, status, order_index, created_at, updated_at FROM ld_module WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        return $module ?: null;
    }

    public function getList(int $courseId = 0): array
    {
        $sql = 'SELECT id, course_id, title FROM ld_module WHERE 1 = 1';
        $params = [];

        if ($courseId > 0) {
            $sql .= ' AND course_id = :course_id';
            $params[':course_id'] = $courseId;
        }

        $sql .= ' ORDER BY title ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $moduleId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = $input['description'] ?? null;
        $status = trim((string) ($input['status'] ?? 'active'));
        $orderIndex = $input['order_index'] ?? 0;

        if ($moduleId <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Module title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_module SET title = ?, description = ?, status = ?, order_index = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $title,
            $description,
            $status,
            $orderIndex,
            $moduleId,
        ]);

        return [
            'success' => true,
            'id' => $moduleId,
            'message' => 'Module updated successfully',
        ];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_module SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Module archived successfully',
        ];
    }

    public function create(array $input, int $courseId = 0): array
    {
        $courseId = (int) ($input['course_id'] ?? $courseId);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Module title is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $orderIndex = isset($input['order_index']) && trim((string) $input['order_index']) !== '' ? (int) $input['order_index'] : null;

        if ($orderIndex === null) {
            $maxOrderStmt = $this->conn->prepare('SELECT COALESCE(MAX(order_index), 0) + 1 FROM ld_module WHERE course_id = :course_id');
            $maxOrderStmt->execute([':course_id' => $courseId]);
            $orderIndex = (int) $maxOrderStmt->fetchColumn();
        }

        $sql = 'INSERT INTO ld_module (course_id, title, description, order_index, status)
                VALUES (:course_id, :title, :description, :order_index, :status)';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $title,
            ':description' => $description,
            ':order_index' => $orderIndex,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Module created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
