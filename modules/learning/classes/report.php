<?php

include_once __DIR__ . '/../../../database/db.php';

class Report
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
        $sql = 'SELECT id, learner_id, item_type, reference_id, reason, status, instructor_response, instructor_responded_at, reviewed_by, reviewed_at, created_at FROM ld_report WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        return $report ?: null;
    }

    public function getList(string $status = 'pending'): array
    {
        $sql = 'SELECT id, learner_id, item_type, reference_id, reason, status, instructor_response, instructor_responded_at, reviewed_by, reviewed_at, created_at FROM ld_report WHERE status = :status ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => $status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPending(): array
    {
        return $this->getList('pending');
    }

    public function getReviewed(): array
    {
        return $this->getList('reviewed');
    }

    public function create(array $input): array
    {
        $learnerId = (int) ($input['learner_id'] ?? 0);
        $itemType = trim((string) ($input['item_type'] ?? ''));
        $referenceId = (int) ($input['reference_id'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? ''));

        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($itemType === '') {
            return ['success' => false, 'message' => 'Item type is required.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        if ($reason === '') {
            return ['success' => false, 'message' => 'Report reason is required.'];
        }

        $sql = 'INSERT INTO ld_report (learner_id, item_type, reference_id, reason, status) VALUES (:learner_id, :item_type, :reference_id, :reason, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
            ':reason' => $reason,
            ':status' => 'pending',
        ]);

        return [
            'success' => true,
            'message' => 'Report created successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function review(int $id, string $response, int $reviewedBy): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Report ID is required.'];
        }

        $response = trim((string) $response);
        $reviewedBy = (int) $reviewedBy;

        if ($reviewedBy <= 0) {
            return ['success' => false, 'message' => 'Reviewed by user ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_report SET status = :status, instructor_response = :response, instructor_responded_at = NOW(), reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'reviewed',
            ':response' => $response ?: null,
            ':reviewed_by' => $reviewedBy,
            ':id' => $id,
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Report reviewed successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Report ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_report SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Report archived successfully'];
    }
}
