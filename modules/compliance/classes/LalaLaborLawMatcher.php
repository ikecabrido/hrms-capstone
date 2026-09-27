<?php

require_once __DIR__ . '/LalaNormalizer.php';
require_once __DIR__ . '/LaborLawReference.php';

class LalaLaborLawMatcher
{
    private $conn;
    private $model;
    private $normalizer;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
        $this->model = new LaborLawReference($this->conn);
        $this->normalizer = new LalaNormalizer();
    }

    public function search(string $query, int $limit = 5): array
    {
        $normalized = $this->normalizer->normalize($query);
        if ($normalized === '') {
            return [];
        }

        $references = $this->model->searchReferencesForAssistant($normalized, $limit);
        $results = [];
        foreach ($references as $r) {
            $results[] = ['record' => $r, 'score' => (int) ($r['relevance'] ?? 0), 'match_type' => 'labor_law'];
        }
        return $results;
    }

    public function getReferenceById(int $id): ?array
    {
        $reference = $this->model->getReferenceById($id);
        return $reference ?: null;
    }
}
