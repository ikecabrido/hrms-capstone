<?php

include_once __DIR__ . '/../../../database/db.php';

class Note
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

    /**
     * Get a specific note by ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, learner_id, item_type, reference_id, note, created_at, updated_at FROM ld_note WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        return $note ?: null;
    }

    /**
     * Get all notes for a learner
     */
    public function getByLearner(int $learnerId): array
    {
        $sql = 'SELECT id, item_type, reference_id, note, created_at, updated_at FROM ld_note WHERE learner_id = :learner_id ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get notes for a specific item (e.g., course, lesson, quiz)
     */
    public function getByItem(string $itemType, int $referenceId): array
    {
        $sql = 'SELECT id, learner_id, note, created_at, updated_at FROM ld_note WHERE item_type = :item_type AND reference_id = :reference_id ORDER BY updated_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get note for a specific learner on a specific item
     */
    public function getByLearnerAndItem(int $learnerId, string $itemType, int $referenceId): ?array
    {
        $sql = 'SELECT id, note, created_at, updated_at FROM ld_note WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        return $note ?: null;
    }

    /**
     * Create a new note
     */
    public function create(array $input): array
    {
        $learnerId = (int) ($input['learner_id'] ?? 0);
        $itemType = trim((string) ($input['item_type'] ?? ''));
        $referenceId = (int) ($input['reference_id'] ?? 0);
        $noteContent = trim((string) ($input['note'] ?? ''));

        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($itemType === '') {
            return ['success' => false, 'message' => 'Item type is required.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        if ($noteContent === '') {
            return ['success' => false, 'message' => 'Note content is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_note (learner_id, item_type, reference_id, note) VALUES (:learner_id, :item_type, :reference_id, :note)');
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
            ':note' => $noteContent,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Note created successfully.',
        ];
    }

    /**
     * Update a note
     */
    public function update(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $noteContent = trim((string) ($input['note'] ?? ''));

        if ($id <= 0) {
            return ['success' => false, 'message' => 'Note ID is required.'];
        }

        if ($noteContent === '') {
            return ['success' => false, 'message' => 'Note content is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_note SET note = :note, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':note' => $noteContent,
            ':id' => $id,
        ]);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Note updated successfully.',
        ];
    }

    /**
     * Delete a note
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Note ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_note WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Note deleted successfully'];
    }

    /**
     * Count notes for a learner
     */
    public function countByLearner(int $learnerId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_note WHERE learner_id = :learner_id');
        $stmt->execute([':learner_id' => $learnerId]);

        return (int) $stmt->fetchColumn();
    }
}
