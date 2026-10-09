<?php

include_once __DIR__ . '/../../../database/db.php';

class Lesson
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
        $sql = 'SELECT id, module_id, title, content_type, content_body, video_url, status, order_index, created_at, updated_at FROM ld_lesson WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

        return $lesson ?: null;
    }

    public function getList(int $moduleId = 0): array
    {
        $sql = 'SELECT id, module_id, title, status, order_index FROM ld_lesson WHERE 1 = 1';
        $params = [];

        if ($moduleId > 0) {
            $sql .= ' AND module_id = :module_id';
            $params[':module_id'] = $moduleId;
        }

        $sql .= ' ORDER BY order_index ASC, created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $lessonId = (int) ($input['id'] ?? 0);
        $moduleId = isset($input['module_id']) ? (int) $input['module_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $contentType = trim((string) ($input['content_type'] ?? 'text'));
        $contentBody = $input['content_body'] ?? null;
        $videoUrl = $input['video_url'] ?? null;
        $status = trim((string) ($input['status'] ?? 'active'));
        $orderIndex = isset($input['order_index']) ? (int) $input['order_index'] : 0;

        if ($lessonId <= 0) {
            return ['success' => false, 'message' => 'Lesson ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Lesson title is required.'];
        }

        if (!in_array($contentType, ['video', 'text', 'file', 'mixed'], true)) {
            $contentType = 'text';
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $data = [
            ':title' => $title,
            ':content_type' => $contentType,
            ':content_body' => $contentBody !== null && trim((string) $contentBody) !== '' ? $contentBody : null,
            ':video_url' => $videoUrl !== null && trim((string) $videoUrl) !== '' ? $videoUrl : null,
            ':status' => $status,
            ':order_index' => $orderIndex,
            ':id' => $lessonId,
        ];

        $sql = 'UPDATE ld_lesson SET title = :title, content_type = :content_type, content_body = :content_body, video_url = :video_url, status = :status, order_index = :order_index, updated_at = NOW() WHERE id = :id';

        if ($moduleId !== null && $moduleId > 0) {
            $sql = 'UPDATE ld_lesson SET module_id = :module_id, title = :title, content_type = :content_type, content_body = :content_body, video_url = :video_url, status = :status, order_index = :order_index, updated_at = NOW() WHERE id = :id';
            $data[':module_id'] = $moduleId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($data);

        return [
            'success' => true,
            'id' => $lessonId,
            'message' => 'Lesson updated successfully',
        ];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Lesson ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_lesson SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Lesson archived successfully',
        ];
    }

    public function create(array $input, int $moduleId = 0): array
    {
        $moduleId = (int) ($input['module_id'] ?? $moduleId);
        $title = trim((string) ($input['title'] ?? ''));
        $contentType = trim((string) ($input['content_type'] ?? 'text'));
        $contentBody = trim((string) ($input['content_body'] ?? ''));
        $videoUrl = trim((string) ($input['video_url'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($moduleId <= 0) {
            return ['success' => false, 'message' => 'Module ID is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Lesson title is required.'];
        }

        if (!in_array($contentType, ['video', 'text', 'file', 'mixed'], true)) {
            $contentType = 'text';
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $orderIndex = isset($input['order_index']) && trim((string) $input['order_index']) !== '' ? (int) $input['order_index'] : null;

        if ($orderIndex === null) {
            $maxOrderStmt = $this->conn->prepare('SELECT COALESCE(MAX(order_index), 0) + 1 FROM ld_lesson WHERE module_id = :module_id');
            $maxOrderStmt->execute([':module_id' => $moduleId]);
            $orderIndex = (int) $maxOrderStmt->fetchColumn();
        }

        $sql = 'INSERT INTO ld_lesson (
                    module_id,
                    title,
                    content_type,
                    content_body,
                    video_url,
                    order_index,
                    status
                ) VALUES (
                    :module_id,
                    :title,
                    :content_type,
                    :content_body,
                    :video_url,
                    :order_index,
                    :status
                )';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':module_id' => $moduleId,
            ':title' => $title,
            ':content_type' => $contentType,
            ':content_body' => $contentBody !== '' ? $contentBody : null,
            ':video_url' => $videoUrl !== '' ? $videoUrl : null,
            ':order_index' => $orderIndex,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Lesson created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
