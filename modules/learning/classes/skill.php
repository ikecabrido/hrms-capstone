<?php

include_once __DIR__ . '/../../../database/db.php';

class Skill
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
        $sql = 'SELECT id, name, description, suggested, status, created_at, updated_at FROM ld_skill WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $skill = $stmt->fetch(PDO::FETCH_ASSOC);

        return $skill ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, name, description, suggested, status FROM ld_skill ORDER BY name ASC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $skillId = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $description = $input['description'] ?? null;
        $suggested = !empty($input['suggested']);
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($skillId <= 0) {
            return ['success' => false, 'message' => 'Skill ID is required.'];
        }

        if ($name === '') {
            return ['success' => false, 'message' => 'Skill name is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_skill SET name = :name, description = :description, suggested = :suggested, status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':suggested' => $suggested ? 1 : 0,
            ':status' => $status,
            ':id' => $skillId,
        ]);

        return ['success' => true, 'id' => $skillId, 'message' => 'Skill updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Skill ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_skill SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Skill archived successfully'];
    }

    public function create(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $suggested = isset($input['suggested']) ? (int) $input['suggested'] : 0;
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($name === '') {
            return ['success' => false, 'message' => 'Skill name is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $sql = 'INSERT INTO ld_skill (name, description, suggested, status) VALUES (:name, :description, :suggested, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':suggested' => $suggested ? 1 : 0,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Skill created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}
