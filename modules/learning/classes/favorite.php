<?php

include_once __DIR__ . '/../../../database/db.php';

class Favorite
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
     * Get a specific favorite by ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, learner_id, item_type, reference_id, created_at FROM ld_favorite WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

        return $favorite ?: null;
    }

    /**
     * Get all favorites for a learner
     */
    public function getByLearner(int $learnerId): array
    {
        $sql = 'SELECT id, learner_id, item_type, reference_id, created_at FROM ld_favorite WHERE learner_id = :learner_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':learner_id' => $learnerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get favorites for a specific item type (e.g., 'lesson', 'course')
     */
    public function getByLearnerAndType(int $learnerId, string $itemType): array
    {
        $sql = 'SELECT id, item_type, reference_id, created_at FROM ld_favorite WHERE learner_id = :learner_id AND item_type = :item_type ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if an item is favorited
     */
    public function isFavorited(int $learnerId, string $itemType, int $referenceId): bool
    {
        $sql = 'SELECT 1 FROM ld_favorite WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Add to favorites
     */
    public function add(array $input): array
    {
        $learnerId = (int) ($input['learner_id'] ?? 0);
        $itemType = trim((string) ($input['item_type'] ?? ''));
        $referenceId = (int) ($input['reference_id'] ?? 0);

        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($itemType === '') {
            return ['success' => false, 'message' => 'Item type is required.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        // Check if already favorited
        if ($this->isFavorited($learnerId, $itemType, $referenceId)) {
            return ['success' => false, 'message' => 'Item is already in favorites.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_favorite (learner_id, item_type, reference_id) VALUES (:learner_id, :item_type, :reference_id)');
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Added to favorites successfully.',
        ];
    }

    /**
     * Remove from favorites
     */
    public function remove(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Favorite ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_favorite WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Removed from favorites successfully'];
    }

    /**
     * Remove from favorites by learner, item type, and reference ID
     */
    public function removeByReference(int $learnerId, string $itemType, int $referenceId): array
    {
        if ($learnerId <= 0) {
            return ['success' => false, 'message' => 'Learner ID is required.'];
        }

        if ($itemType === '') {
            return ['success' => false, 'message' => 'Item type is required.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_favorite WHERE learner_id = :learner_id AND item_type = :item_type AND reference_id = :reference_id');
        $stmt->execute([
            ':learner_id' => $learnerId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        return ['success' => true, 'message' => 'Removed from favorites successfully'];
    }
}
