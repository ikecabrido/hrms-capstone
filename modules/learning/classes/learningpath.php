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
        $stmt = $this->conn->prepare('SELECT id, instructor_id, title, description, assigned_to, status, type, is_public, kt_plan_id, created_at, updated_at FROM ld_learning_path WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $path = $stmt->fetch(PDO::FETCH_ASSOC);

        return $path ?: null;
    }

    public function getList(): array
    {
        $stmt = $this->conn->query('SELECT id, title, description, assigned_to, status FROM ld_learning_path ORDER BY title ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $pathId = (int) ($input['id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = $input['description'] ?? null;
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($pathId <= 0) {
            return ['success' => false, 'message' => 'Learning path ID is required.'];
        }
        if ($title === '') {
            return ['success' => false, 'message' => 'Learning path title is required.'];
        }
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $stmt = $this->conn->prepare('UPDATE ld_learning_path SET title = :title, description = :description, status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
            ':id' => $pathId,
        ]);

        if (array_key_exists('assigned_to', $input)) {
            $assignedTo = $input['assigned_to'] !== '' && $input['assigned_to'] !== null
                ? (int) $input['assigned_to']
                : null;
            $assignedStmt = $this->conn->prepare('UPDATE ld_learning_path SET assigned_to = :assigned_to, updated_at = NOW() WHERE id = :id');
            $assignedStmt->execute([':assigned_to' => $assignedTo, ':id' => $pathId]);
        }

        if (isset($input['is_public'])) {
            $visibilityStmt = $this->conn->prepare('UPDATE ld_learning_path SET is_public = :is_public, updated_at = NOW() WHERE id = :id');
            $visibilityStmt->execute([
                ':is_public' => (int) $input['is_public'] === 1 ? 1 : 0,
                ':id' => $pathId,
            ]);
        }

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

    public function inviteLearners(int $pathId, array $employeeIds, int $invitedBy): int
    {
        if ($pathId <= 0) {
            throw new InvalidArgumentException('Learning path ID is required.');
        }

        $employeeCheck = $this->conn->prepare("SELECT 1 FROM em_employees WHERE employee_id = :employee_id AND employment_status = 'Active' LIMIT 1");
        $invite = $this->conn->prepare("INSERT INTO ld_learning_path_invitation
            (learning_path_id, employee_id, status, invited_by)
            VALUES (:path_id, :employee_id, 'invited', :invited_by)
            ON DUPLICATE KEY UPDATE
                status = IF(status IN ('declined', 'withdrawn'), 'invited', status),
                invited_by = VALUES(invited_by)");
        $invitedCount = 0;

        foreach (array_unique(array_filter(array_map('intval', $employeeIds))) as $employeeId) {
            $employeeCheck->execute([':employee_id' => $employeeId]);
            if (!$employeeCheck->fetchColumn()) {
                throw new InvalidArgumentException('One or more invitees are not active employees.');
            }

            $invite->execute([
                ':path_id' => $pathId,
                ':employee_id' => $employeeId,
                ':invited_by' => $invitedBy > 0 ? $invitedBy : null,
            ]);
            $invitedCount++;
        }

        return $invitedCount;
    }

    public function create(array $input, int $instructorId = 0): array
    {
        $instructorId = (int) ($input['instructor_id'] ?? $instructorId);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $assignedTo = isset($input['assigned_to']) && $input['assigned_to'] !== '' ? (int) $input['assigned_to'] : null;
        $isPublic = isset($input['is_public']) ? ((int) $input['is_public'] === 1 ? 1 : 0) : 1;
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

        $stmt = $this->conn->prepare("INSERT INTO ld_learning_path
            (instructor_id, title, description, assigned_to, status, type, is_public)
            VALUES (:instructor_id, :title, :description, :assigned_to, :status, 'standard', :is_public)");
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':title' => $title,
            ':description' => $description,
            ':assigned_to' => $assignedTo,
            ':status' => $status,
            ':is_public' => $isPublic,
        ]);

        return [
            'success' => true,
            'message' => 'Learning path created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }
}