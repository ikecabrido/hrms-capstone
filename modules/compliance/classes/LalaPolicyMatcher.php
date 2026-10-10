<?php

require_once __DIR__ . '/LalaNormalizer.php';

class LalaPolicyMatcher
{
    private $conn;
    private $normalizer;

    private $synonymMap = [
        'remote_work' => [
            'remote work', 'work from home', 'wfh', 'working remotely',
            'remote employee', 'remote arrangement', 'home based work',
            'work from home policy', 'telecommute', 'telework',
            'flexible work arrangement', 'remote work policy',
            'work remotely', 'teleworking', 'distributed team',
            'home based', 'remote working',
        ],
        'code_of_conduct' => [
            'code of conduct', 'conduct policy', 'employee conduct',
            'professional conduct', 'workplace behavior', 'ethical guidelines',
            'employee behavior', 'standards of behavior', 'ethical standards',
            'workplace conduct', 'professional ethics', 'employee ethics',
            'conduct rules', 'behavior policy',
        ],
        'data_privacy' => [
            'data privacy', 'privacy policy', 'personal information',
            'personal data', 'data protection', 'privacy rules',
            'handling personal information', 'data privacy policy',
            'information privacy', 'data security', 'confidentiality',
            'data breach', 'privacy compliance', 'personal data protection',
            'data governance', 'data handling', 'sensitive personal information',
        ],
        'it_security' => [
            'it security', 'information security', 'computer security',
            'system security', 'data security', 'access control',
            'security requirements', 'it security policy',
            'cybersecurity', 'network security', 'password policy',
            'security protocols', 'data protection', 'information protection',
            'system access', 'digital security',
        ],
        'employee_handbook' => [
            'employee handbook', 'handbook', 'hr handbook', 'employee guide',
            'company handbook', 'employee policies', 'benefits and procedures',
            'company policies', 'employee manual', 'hr manual',
            'staff handbook', 'company guide', 'policy handbook',
            'employee resource', 'new hire guide',
        ],
        'anti_harassment' => [
            'harassment', 'anti harassment', 'workplace harassment',
            'discrimination', 'harassment policy', 'workplace discrimination',
            'sexual harassment', 'gender harassment', 'zero tolerance',
            'harassment prevention', 'conduct policy', 'respectful workplace',
            'anti harassment policy', 'discrimination policy',
        ],
        'social_media' => [
            'social media', 'facebook', 'instagram', 'tiktok', 'linkedin',
            'online posts', 'social media rules', 'employee social media',
            'social media policy', 'social networking', 'digital conduct',
            'social platforms', 'company social media', 'online behavior',
        ],
        'business_continuity' => [
            'business continuity', 'emergency procedures', 'emergency operations',
            'disaster recovery', 'disruptions', 'continuity plan',
            'emergency response', 'business continuity plan',
            'crisis management', 'disaster recovery plan', 'contingency plan',
            'emergency plan', 'resilience plan', 'operational continuity',
        ],
        'workplace_safety' => [
            'workplace safety', 'safety policy', 'health and safety',
            'safety procedures', 'workplace hazards', 'employee safety',
            'safe workplace', 'occupational safety', 'safety standards',
            'ppe', 'personal protective equipment', 'hazard identification',
            'safety compliance', 'workplace health', 'incident prevention',
            'safety training',
        ],
        'expense_reimbursement' => [
            'expense reimbursement', 'reimbursement', 'expense report',
            'expense claim', 'employee expenses', 'reimbursable expenses',
            'reimbursement process', 'expense policy',
            'travel expense', 'business expense', 'claim reimbursement',
            'expense submission', 'reimbursable', 'expense allowance',
        ],
    ];

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
        $this->normalizer = new LalaNormalizer();
    }

    public function search(string $query, int $limit = 5): array
    {
        $normalized = $this->normalizer->normalize($query);
        if ($normalized === '') {
            return [];
        }

        $matchedPolicyId = $this->matchPolicyCode($normalized);
        if ($matchedPolicyId) {
            $record = $this->getPolicyById($matchedPolicyId);
            if ($record) {
                return [['record' => $record, 'score' => 100, 'match_type' => 'policy_code']];
            }
        }

        try {
            $synonymResults = $this->searchBySynonyms($normalized, $limit);
            if (!empty($synonymResults)) {
                return $synonymResults;
            }
            return $this->searchByFullText($normalized, $limit);
        } catch (Throwable $e) {
            error_log('LalaPolicyMatcher search fallback: ' . $e->getMessage());
            return $this->searchBySimple($normalized, $limit);
        }
    }

    public function getPolicyById(int $id): ?array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, c.name AS category_name
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record && !isset($record['category_name'])) {
                $record['category_name'] = '';
            }
            return $record ?: null;
        } catch (Throwable $e) {
            error_log('LalaPolicyMatcher getPolicyById fallback: ' . $e->getMessage());
            $stmt = $this->conn->prepare("
                SELECT p.*
                FROM lc_policies p
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record) {
                $record['category_name'] = '';
            }
            return $record ?: null;
        }
    }

    public function getPolicyByCode(string $code): ?array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, c.name AS category_name
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE p.policy_code = :code
                ORDER BY p.version DESC
                LIMIT 1
            ");
            $stmt->execute([':code' => $code]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record && !isset($record['category_name'])) {
                $record['category_name'] = '';
            }
            return $record ?: null;
        } catch (Throwable $e) {
            error_log('LalaPolicyMatcher getPolicyByCode fallback: ' . $e->getMessage());
            $stmt = $this->conn->prepare("
                SELECT p.*
                FROM lc_policies p
                WHERE p.policy_code = :code
                ORDER BY p.version DESC
                LIMIT 1
            ");
            $stmt->execute([':code' => $code]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record) {
                $record['category_name'] = '';
            }
            return $record ?: null;
        }
    }

    private function matchPolicyCode(string $normalized): ?int
    {
        if (preg_match('/\b(pol[- ]?data[- ]?\d{3})\b/', $normalized, $m)) {
            $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $m[1]));
            $stmt = $this->conn->prepare("SELECT id FROM lc_policies WHERE policy_code = :code LIMIT 1");
            $stmt->execute([':code' => $code]);
            $id = $stmt->fetchColumn();
            return $id ? (int) $id : null;
        }
        return null;
    }

    private function searchBySynonyms(string $normalized, int $limit): array
    {
        $results = [];
        $foundIds = [];

        foreach ($this->synonymMap as $group => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (str_contains($normalized, $synonym)) {
                    try {
                        $sql = "
                            SELECT p.*, c.name AS category_name,
                            (CASE WHEN p.status = 'Published' THEN 10 WHEN p.status = 'For Review' THEN 5 WHEN p.status = 'Draft' THEN 2 WHEN p.status = 'Archived' THEN 1 ELSE 0 END) AS status_score
                            FROM lc_policies p
                            LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                            WHERE p.title LIKE :search
                            ORDER BY status_score DESC, p.effective_date DESC
                            LIMIT " . (int) $limit;
                        $stmt = $this->conn->prepare($sql);
                        $searchParam = '%' . $synonym . '%';
                        $stmt->execute([':search' => $searchParam]);
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                            if (isset($foundIds[$r['id']])) continue;
                            $foundIds[$r['id']] = true;
                            $results[] = ['record' => $r, 'score' => 50, 'match_type' => 'synonym'];
                        }
                        if (!empty($results)) return $results;
                    } catch (Throwable $e) {
                        error_log('LalaPolicyMatcher synonym search fallback: ' . $e->getMessage());
                        $fallback = $this->searchBySimple('"' . $synonym . '"', $limit, $foundIds);
                        foreach ($fallback as $r) {
                            $results[] = $r;
                        }
                        if (!empty($results)) return $results;
                    }
                }
            }
        }

        return $results;
    }

    private function searchByFullText(string $normalized, int $limit): array
    {
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return [];
        }

        $scoreParts = [];
        $params = [];
        $whereParts = [];
        $idx = 0;

        foreach ($words as $word) {
            if (strlen($word) < 3) continue;
            $idx++;
            $p = ":w{$idx}";
            $params[$p] = "%{$word}%";
            $scoreParts[] = "(CASE WHEN p.title LIKE {$p} THEN 5 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN p.description LIKE {$p} THEN 3 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN p.content LIKE {$p} THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN p.policy_code LIKE {$p} THEN 4 ELSE 0 END)";
            $whereParts[] = "(p.title LIKE {$p} OR p.description LIKE {$p} OR p.content LIKE {$p} OR p.policy_code LIKE {$p})";
        }

        if (empty($whereParts)) {
            return [];
        }

        try {
            $sql = "
                SELECT p.*, c.name AS category_name,
                (" . implode(" + ", $scoreParts) . ") AS relevance
                FROM lc_policies p
                LEFT JOIN lc_policy_categories c ON p.category_id = c.id
                WHERE " . implode(" AND ", $whereParts) . "
                HAVING relevance > 0
                ORDER BY
                    CASE
                        WHEN p.status = 'Published' THEN 1
                        WHEN p.status = 'For Review' THEN 2
                        WHEN p.status = 'Draft' THEN 3
                        WHEN p.status = 'Archived' THEN 4
                        ELSE 5
                    END,
                    p.effective_date DESC
                LIMIT " . (int) $limit;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($rows as $r) {
                $results[] = ['record' => $r, 'score' => (int) ($r['relevance'] ?? 0), 'match_type' => 'fulltext'];
            }
            return $results;
        } catch (Throwable $e) {
            error_log('LalaPolicyMatcher fulltext search fallback: ' . $e->getMessage());
            return $this->searchBySimple($normalized, $limit);
        }
    }

    private function searchBySimple(string $normalized, int $limit, array &$foundIds = []): array
    {
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return [];
        }

        $whereParts = [];
        $params = [];
        $idx = 0;
        foreach ($words as $word) {
            if (strlen($word) < 3) continue;
            $idx++;
            $p = ":w{$idx}";
            $params[$p] = "%{$word}%";
            $whereParts[] = "(p.title LIKE {$p} OR p.description LIKE {$p} OR p.content LIKE {$p} OR p.policy_code LIKE {$p})";
        }

        if (empty($whereParts)) {
            return [];
        }

        $sql = "
            SELECT p.*,
            (CASE WHEN p.status = 'Published' THEN 10 WHEN p.status = 'For Review' THEN 5 WHEN p.status = 'Draft' THEN 2 WHEN p.status = 'Archived' THEN 1 ELSE 0 END) AS relevance
            FROM lc_policies p
            WHERE " . implode(" AND ", $whereParts) . "
            ORDER BY relevance DESC, p.effective_date DESC
            LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            if (isset($foundIds[$r['id']])) continue;
            $foundIds[$r['id']] = true;
            $r['category_name'] = $r['category_name'] ?? '';
            $results[] = ['record' => $r, 'score' => (int) ($r['relevance'] ?? 0), 'match_type' => 'simple'];
        }
        return $results;
    }
}
