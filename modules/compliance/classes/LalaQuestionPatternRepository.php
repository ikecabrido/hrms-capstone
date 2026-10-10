<?php

class LalaQuestionPatternRepository
{
    private $conn;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function findOrCreate(string $normalizedPattern, string $intent, string $source): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM lc_lala_question_patterns
                WHERE normalized_pattern = :normalized_pattern
                LIMIT 1
            ");
            $stmt->execute([':normalized_pattern' => $normalizedPattern]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $this->conn->prepare("
                    UPDATE lc_lala_question_patterns
                    SET matched_count = matched_count + 1, intent = :intent, source = :source
                    WHERE id = :id
                ")->execute([
                    ':intent' => $intent,
                    ':source' => $source,
                    ':id' => $existing['id'],
                ]);
                return $existing;
            }

            $this->conn->prepare("
                INSERT INTO lc_lala_question_patterns (pattern, normalized_pattern, intent, source, matched_count, approved)
                VALUES (:pattern, :normalized_pattern, :intent, :source, 1, 0)
            ")->execute([
                ':pattern' => $normalizedPattern,
                ':normalized_pattern' => $normalizedPattern,
                ':intent' => $intent,
                ':source' => $source,
            ]);

            return $this->findApprovedPatterns(['intent' => $intent])[0] ?? [];
        } catch (Throwable $e) {
            error_log('LalaQuestionPatternRepository error: ' . $e->getMessage());
            return [];
        }
    }

    public function findApprovedPatterns(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM lc_lala_question_patterns WHERE 1=1";
            $params = [];

            if (!empty($filters['intent'])) {
                $sql .= " AND intent = :intent";
                $params[':intent'] = $filters['intent'];
            }
            if (!empty($filters['approved'])) {
                $sql .= " AND approved = :approved";
                $params[':approved'] = (int) $filters['approved'];
            }

            $sql .= " ORDER BY matched_count DESC, id DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('LalaQuestionPatternRepository findApprovedPatterns error: ' . $e->getMessage());
            return [];
        }
    }

    public function approvePattern(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE lc_lala_question_patterns SET approved = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function rejectPattern(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM lc_lala_question_patterns WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
