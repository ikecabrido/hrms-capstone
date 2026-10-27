<?php
/**
 * AIAnalytics - read-only usage summaries for the admin AI Assistant.
 *
 * The queries are intentionally bounded to a small date range and a small
 * number of rows so the admin page stays responsive as job history grows.
 * No provider key, ciphertext, or prompt/context body is selected here.
 */
class AIAnalytics
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $days): array
    {
        $days = $this->normalizeDays($days);
        $where = 'created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';

        $summaryStmt = $this->pdo->query(
            "SELECT COUNT(*) AS jobs,
                    COALESCE(SUM(estimated_cost_usd), 0) AS cost,
                    COALESCE(SUM(status = 'failed'), 0) AS failures,
                    COALESCE(SUM(status = 'applied'), 0) AS applied,
                    COALESCE(SUM(prompt_tokens + completion_tokens), 0) AS tokens
               FROM ld_ai_course_job
              WHERE " . $where
        );
        $summary = $summaryStmt ? ($summaryStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $dailyStmt = $this->pdo->query(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS jobs,
                    COALESCE(SUM(estimated_cost_usd), 0) AS cost,
                    COALESCE(SUM(status = 'failed'), 0) AS failures
               FROM ld_ai_course_job
              WHERE " . $where . "
              GROUP BY DATE(created_at)
              ORDER BY day ASC"
        );
        $dailyRows = $dailyStmt ? ($dailyStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $modelStmt = $this->pdo->query(
            "SELECT model,
                    COUNT(*) AS jobs,
                    COALESCE(SUM(estimated_cost_usd), 0) AS cost,
                    COALESCE(SUM(prompt_tokens + completion_tokens), 0) AS tokens
               FROM ld_ai_course_job
              WHERE " . $where . "
              GROUP BY model
              ORDER BY jobs DESC, model ASC
              LIMIT 8"
        );
        $models = $modelStmt ? ($modelStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $statusStmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS jobs
               FROM ld_ai_course_job
              WHERE " . $where . "
              GROUP BY status"
        );
        $statusRows = $statusStmt ? ($statusStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $failureStmt = $this->pdo->query(
            "SELECT id, model, created_at, error_message
               FROM ld_ai_course_job
              WHERE status = 'failed' AND " . $where . "
              ORDER BY id DESC
              LIMIT 5"
        );
        $failures = $failureStmt ? ($failureStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $statusCounts = [];
        foreach (['draft', 'generating', 'ready', 'applied', 'failed', 'cancelled'] as $status) {
            $statusCounts[$status] = 0;
        }
        foreach ($statusRows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = (int) ($row['jobs'] ?? 0);
            }
        }

        $daily = $this->fillDaily($dailyRows, $days);
        $maxJobs = 1;
        $maxCost = 0.01;
        foreach ($daily as $day) {
            $maxJobs = max($maxJobs, (int) $day['jobs']);
            $maxCost = max($maxCost, (float) $day['cost']);
        }

        return [
            'days' => $days,
            'range_label' => 'Last ' . $days . ' days',
            'jobs' => (int) ($summary['jobs'] ?? 0),
            'cost' => (float) ($summary['cost'] ?? 0),
            'failures' => (int) ($summary['failures'] ?? 0),
            'applied' => (int) ($summary['applied'] ?? 0),
            'tokens' => (int) ($summary['tokens'] ?? 0),
            'daily' => $daily,
            'max_jobs' => $maxJobs,
            'max_cost' => $maxCost,
            'models' => $models,
            'statuses' => $statusCounts,
            'recent_failures' => $failures,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(int $days): array
    {
        $days = $this->normalizeDays($days);
        $stmt = $this->pdo->query(
            "SELECT j.id, j.status, j.model, j.prompt_tokens, j.completion_tokens,
                    j.estimated_cost_usd, j.created_at, j.applied_at, j.error_message,
                    CONCAT(l.first_name, ' ', l.last_name) AS learner_name,
                    CONCAT(o.first_name, ' ', o.last_name) AS owner_name
               FROM ld_ai_course_job j
               LEFT JOIN em_employees l ON l.employee_id = j.learner_id
               LEFT JOIN em_employees o ON o.employee_id = j.course_owner_id
              WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL " . $days . " DAY)
              ORDER BY j.id DESC
              LIMIT 5000"
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function fillDaily(array $rows, int $days): array
    {
        $byDay = [];
        foreach ($rows as $row) {
            $day = (string) ($row['day'] ?? '');
            if ($day !== '') {
                $byDay[$day] = [
                    'jobs' => (int) ($row['jobs'] ?? 0),
                    'cost' => (float) ($row['cost'] ?? 0),
                    'failures' => (int) ($row['failures'] ?? 0),
                ];
            }
        }

        $daily = [];
        $today = new DateTimeImmutable('today');
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->modify('-' . $i . ' days');
            $key = $date->format('Y-m-d');
            $daily[] = [
                'day' => $key,
                'label' => $date->format('M j'),
                'jobs' => $byDay[$key]['jobs'] ?? 0,
                'cost' => $byDay[$key]['cost'] ?? 0.0,
                'failures' => $byDay[$key]['failures'] ?? 0,
            ];
        }

        return $daily;
    }

    private function normalizeDays(int $days): int
    {
        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }
}
