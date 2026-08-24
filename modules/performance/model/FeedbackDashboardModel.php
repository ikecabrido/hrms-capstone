<?php
require_once __DIR__ . '/../../../database/db.php';

class FeedbackDashboardModel
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

    private function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type'] ?? PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function employeeTable(): string
    {
        if ($this->tableExists('em_employees')) {
            return 'em_employees';
        }

        return $this->tableExists('hrms_employee') ? 'hrms_employee' : 'em_employees';
    }

    private function employeeNameById(?int $employeeId): ?string
    {
        if (!$employeeId || !$this->tableExists($this->employeeTable())) {
            return null;
        }

        $table = $this->employeeTable();
        $sql = "SELECT CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS employee_name
                FROM {$table}
                WHERE employee_id = :employee_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':employee_id', (int) $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['employee_name'] ?? null;
    }

    private function normalizeRaterType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        $map = [
            'self' => 'Self',
            'employee' => 'Self',
            'manager' => 'Manager',
            'supervisor' => 'Manager',
            'leader' => 'Manager',
            'peer' => 'Peer',
            'colleague' => 'Peer',
            'subordinate' => 'Subordinate',
            'direct report' => 'Subordinate',
            'direct_report' => 'Subordinate',
            'other' => 'Other',
        ];

        return $map[$normalized] ?? ucfirst($normalized ?: 'Other');
    }

    private function parseCompetencyScores($competencyScores): array
    {
        if (is_array($competencyScores)) {
            return $competencyScores;
        }

        if (empty($competencyScores)) {
            return [];
        }

        $decoded = json_decode((string) $competencyScores, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $pairs = [];
        foreach (preg_split('/\r\n|\n|,/', (string) $competencyScores) as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $part, 2));
            if ($key !== '' && $value !== '') {
                $pairs[$key] = (float) $value;
            }
        }

        return $pairs;
    }

    private function competencyLabels(): array
    {
        return [
            'Communication',
            'Teamwork',
            'Leadership',
            'Problem Solving',
            'Adaptability',
            'Accountability',
        ];
    }

    private function cycleFilterExpression(): string
    {
        return "(review_period = :cycle OR review_period LIKE :cycle_like OR feedback_category = :cycle OR category = :cycle)";
    }

    public function getReviewCycles(): array
    {
        if (!$this->tableExists('pm_review_cycles')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT cycle_id, title, cycle_period, status, start_date, end_date
             FROM pm_review_cycles
             ORDER BY start_date DESC, cycle_id DESC'
        );
    }

    public function getFeedbackEntries(array $filters = []): array
    {
        if (!$this->tableExists('pm_feedback_360_entries')) {
            return [];
        }

        $sql = 'SELECT * FROM pm_feedback_360_entries WHERE 1 = 1';
        $params = [];

        if (!empty($filters['cycle'])) {
            $cycle = trim((string) $filters['cycle']);
            $sql .= ' AND ' . $this->cycleFilterExpression();
            $params[':cycle'] = ['value' => $cycle, 'type' => PDO::PARAM_STR];
            $params[':cycle_like'] = ['value' => '%' . $cycle . '%', 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['rater_type']) && strtolower((string) $filters['rater_type']) !== 'all') {
            $raterType = $this->normalizeRaterType((string) $filters['rater_type']);
            $sql .= ' AND reviewer_type = :reviewer_type';
            $params[':reviewer_type'] = ['value' => $raterType, 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY updated_at DESC, feedback_id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function getEmployeeCount(): int
    {
        $employeeTable = $this->employeeTable();
        if (!$this->tableExists($employeeTable)) {
            return 0;
        }

        $statusColumn = $employeeTable === 'em_employees' ? 'employment_status' : 'status';
        $sql = "SELECT COUNT(*) FROM {$employeeTable} WHERE {$statusColumn} IN ('active', 'Active', 'ACTIVE') OR {$statusColumn} IS NULL";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getDashboardStats(array $filters = []): array
    {
        $cycles = $this->getReviewCycles();
        $activeCycles = 0;
        foreach ($cycles as $cycle) {
            $status = strtolower(trim((string) ($cycle['status'] ?? '')));
            if ($status === 'active' || $status === 'in progress' || $status === 'in_progress') {
                $activeCycles++;
            }
        }

        $entries = $this->getFeedbackEntries($filters);
        $completedFeedback = 0;
        $scores = [];

        foreach ($entries as $entry) {
            $status = strtolower(trim((string) ($entry['feedback_status'] ?? '')));
            if ($status === 'completed') {
                $completedFeedback++;
            }

            $score = (float) ($entry['overall_rating'] ?? $entry['rating'] ?? 0);
            if ($score > 0) {
                $scores[] = $score;
            }
        }

        $avgScore = 0.0;
        if (!empty($scores)) {
            $avgScore = round(array_sum($scores) / count($scores), 2);
        }

        return [
            'active_cycles' => $activeCycles,
            'total_employees' => $this->getEmployeeCount(),
            'completed_feedback' => $completedFeedback,
            'overall_avg_score' => $avgScore,
        ];
    }

    public function getRadarChartData(array $filters = []): array
    {
        $entries = $this->getFeedbackEntries($filters);
        $labels = $this->competencyLabels();
        $datasetMap = [];

        foreach (['Self', 'Manager', 'Peer', 'Subordinate', 'Other'] as $raterType) {
            $datasetMap[$raterType] = array_fill(0, count($labels), 0.0);
            $counts = array_fill(0, count($labels), 0);

            foreach ($entries as $entry) {
                if ($this->normalizeRaterType((string) ($entry['reviewer_type'] ?? '')) !== $raterType) {
                    continue;
                }

                $scores = $this->parseCompetencyScores($entry['competency_scores'] ?? null);
                foreach ($labels as $index => $label) {
                    $score = null;
                    if (isset($scores[$label])) {
                        $score = (float) $scores[$label];
                    } elseif (isset($scores[strtolower($label)]) || isset($scores[str_replace(' ', '_', strtolower($label))])) {
                        $matchKey = strtolower($label);
                        $altKey = str_replace(' ', '_', $matchKey);
                        $score = (float) ($scores[$matchKey] ?? $scores[$altKey] ?? 0);
                    }

                    if ($score === null || $score <= 0) {
                        $score = (float) ($entry['rating'] ?? 0);
                    }

                    if ($score > 0) {
                        $datasetMap[$raterType][$index] += $score;
                        $counts[$index]++;
                    }
                }
            }

            foreach ($labels as $index => $label) {
                if ($counts[$index] > 0) {
                    $datasetMap[$raterType][$index] = round($datasetMap[$raterType][$index] / $counts[$index], 2);
                } else {
                    $datasetMap[$raterType][$index] = 0.0;
                }
            }
        }

        $datasets = [];
        $palette = [
            'Self' => '#2dd4bf',
            'Manager' => '#3b82f6',
            'Peer' => '#8b5cf6',
            'Subordinate' => '#f59e0b',
            'Other' => '#10b981',
        ];

        foreach (['Self', 'Manager', 'Peer', 'Subordinate', 'Other'] as $raterType) {
            $datasets[] = [
                'label' => $raterType,
                'data' => $datasetMap[$raterType],
                'borderColor' => $palette[$raterType],
                'backgroundColor' => $this->hexToRgba($palette[$raterType], 0.18),
                'fill' => true,
                'pointBackgroundColor' => $palette[$raterType],
                'pointRadius' => 4,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function getRaterBreakdown(array $filters = []): array
    {
        $entries = $this->getFeedbackEntries($filters);
        $grouped = [];

        foreach (['Self', 'Manager', 'Peer', 'Subordinate', 'Other'] as $type) {
            $grouped[$type] = 0;
        }

        foreach ($entries as $entry) {
            $raterType = $this->normalizeRaterType((string) ($entry['reviewer_type'] ?? ''));
            if (!isset($grouped[$raterType])) {
                $grouped['Other'] = ($grouped['Other'] ?? 0) + 1;
                continue;
            }
            $grouped[$raterType]++;
        }

        $total = array_sum($grouped);
        $rows = [];
        foreach (['Self', 'Manager', 'Peer', 'Subordinate', 'Other'] as $type) {
            $count = (int) $grouped[$type];
            $rows[] = [
                'label' => $type,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        return [
            'total' => $total,
            'rows' => $rows,
            'labels' => array_map(fn($row) => $row['label'], $rows),
            'data' => array_map(fn($row) => $row['count'], $rows),
        ];
    }

    public function getProgressSummary(array $filters = []): array
    {
        $entries = $this->getFeedbackEntries($filters);
        $counts = [
            'Invited' => 0,
            'In Progress' => 0,
            'Completed' => 0,
            'Overdue' => 0,
        ];

        foreach ($entries as $entry) {
            $status = strtolower(trim((string) ($entry['feedback_status'] ?? '')));
            if (in_array($status, ['pending', 'invited', 'not started', 'not_started'], true)) {
                $counts['Invited']++;
            } elseif (in_array($status, ['in progress', 'in_progress'], true)) {
                $counts['In Progress']++;
            } elseif ($status === 'completed') {
                $counts['Completed']++;
            } elseif (in_array($status, ['overdue', 'late'], true)) {
                $counts['Overdue']++;
            }
        }

        $total = array_sum($counts);
        $progress = [];
        foreach ($counts as $label => $count) {
            $progress[] = [
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        return [
            'total' => $total,
            'items' => $progress,
        ];
    }

    public function getOverallScore(array $filters = []): array
    {
        $entries = $this->getFeedbackEntries($filters);
        $scores = [];

        foreach ($entries as $entry) {
            $score = (float) ($entry['overall_rating'] ?? $entry['rating'] ?? 0);
            if ($score > 0) {
                $scores[] = $score;
            }
        }

        $avg = 0.0;
        if (!empty($scores)) {
            $avg = round(array_sum($scores) / count($scores), 2);
        }

        return [
            'average' => $avg,
            'percentage' => $avg > 0 ? round(($avg / 5) * 100, 1) : 0.0,
        ];
    }

    public function getCompetencySummary(array $filters = []): array
    {
        $entries = $this->getFeedbackEntries($filters);
        $scores = [];
        foreach ($this->competencyLabels() as $label) {
            $scores[$label] = [];
        }

        foreach ($entries as $entry) {
            $parsed = $this->parseCompetencyScores($entry['competency_scores'] ?? null);
            foreach ($this->competencyLabels() as $label) {
                $rawScore = $parsed[$label] ?? null;
                if ($rawScore === null) {
                    continue;
                }
                $scores[$label][] = (float) $rawScore;
            }
        }

        $summary = [];
        foreach ($this->competencyLabels() as $label) {
            $values = $scores[$label];
            if (empty($values)) {
                $summary[] = ['label' => $label, 'score' => 0.0];
                continue;
            }
            $summary[] = ['label' => $label, 'score' => round(array_sum($values) / count($values), 2)];
        }

        usort($summary, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'strengths' => array_slice($summary, 0, 3),
            'improvements' => array_slice($summary, -3),
        ];
    }

    public function getRecentSubmissions(array $filters = [], int $limit = 8): array
    {
        if (!$this->tableExists('pm_feedback_360_entries')) {
            return [];
        }

        $sql = 'SELECT * FROM pm_feedback_360_entries WHERE 1 = 1';
        $params = [];

        if (!empty($filters['cycle'])) {
            $cycle = trim((string) $filters['cycle']);
            $sql .= ' AND ' . $this->cycleFilterExpression();
            $params[':cycle'] = ['value' => $cycle, 'type' => PDO::PARAM_STR];
            $params[':cycle_like'] = ['value' => '%' . $cycle . '%', 'type' => PDO::PARAM_STR];
        }

        if (!empty($filters['rater_type']) && strtolower((string) $filters['rater_type']) !== 'all') {
            $sql .= ' AND reviewer_type = :reviewer_type';
            $params[':reviewer_type'] = ['value' => $this->normalizeRaterType((string) $filters['rater_type']), 'type' => PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY updated_at DESC, feedback_id DESC LIMIT :limit';
        $params[':limit'] = ['value' => (int) $limit, 'type' => PDO::PARAM_INT];

        $rows = $this->fetchAll($sql, $params);

        foreach ($rows as &$row) {
            $row['employee_name'] = $this->employeeNameById((int) ($row['employee_id'] ?? 0)) ?: 'Employee ' . ($row['employee_id'] ?? 'N/A');
            $row['rater_name'] = !empty($row['reviewer_name']) ? $row['reviewer_name'] : 'Rater';
            $row['rater_type'] = $this->normalizeRaterType((string) ($row['reviewer_type'] ?? 'Other'));
            $row['score'] = (float) ($row['overall_rating'] ?? $row['rating'] ?? 0);
            $row['submitted_on'] = $row['updated_at'] ?: $row['created_at'];
            $row['status'] = ucfirst(strtolower(trim((string) ($row['feedback_status'] ?? 'Pending'))));
            if ($row['status'] === '') {
                $row['status'] = 'Pending';
            }
        }

        return $rows;
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return sprintf('rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha);
    }
}
