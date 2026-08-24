<?php

require_once __DIR__ . '/../model/PerformanceReportModel.php';

class PerformanceReportController
{
    private PerformanceReportModel $model;

    public function __construct($pdo = null)
    {
        $this->model = new PerformanceReportModel($pdo);
    }

    public function normalizeFilters(array $filters = []): array
    {
        $normalized = [];
        $normalized['department'] = trim((string) ($filters['department'] ?? ''));
        $normalized['employee'] = trim((string) ($filters['employee'] ?? ''));
        $normalized['review_period'] = trim((string) ($filters['review_period'] ?? ''));
        $normalized['performance_rating'] = trim((string) ($filters['performance_rating'] ?? ''));
        $normalized['status'] = trim((string) ($filters['status'] ?? ''));

        return $normalized;
    }

    public function getViewData(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        return [
            'stats' => $this->model->getDashboardStats($normalized),
            'rows' => $this->model->getReportRows($normalized),
            'departments' => $this->model->getDepartments(),
            'reviewPeriods' => $this->model->getReviewPeriods(),
            'reportModules' => [
                ['title' => 'Goal Setting Report', 'subtitle' => 'Employee goals and progress', 'metric' => $this->model->getDashboardStats($normalized)['goal_completion_rate'] . '%'],
                ['title' => 'KPI Tracking Report', 'subtitle' => 'KPI achievement and status', 'metric' => $this->model->getDashboardStats($normalized)['average_kpi_achievement'] . '%'],
                ['title' => 'Appraisal & Review Report', 'subtitle' => 'Balanced appraisal scores', 'metric' => $this->model->getDashboardStats($normalized)['average_appraisal_score'] . '/100'],
                ['title' => '360-Degree Feedback Report', 'subtitle' => 'Feedback quality and comments', 'metric' => $this->model->getDashboardStats($normalized)['average_360_feedback'] . '/100'],
                ['title' => 'Training & Development Report', 'subtitle' => 'Training recommendations', 'metric' => $this->model->getDashboardStats($normalized)['training_recommendations'] . ' actions'],
                ['title' => 'Overall Performance Report', 'subtitle' => 'Complete employee picture', 'metric' => $this->model->getDashboardStats($normalized)['average_performance_score'] . '/100'],
            ],
        ];
    }

    public function exportCsv(array $filters = []): string
    {
        $rows = $this->model->getReportRows($this->normalizeFilters($filters));
        $lines = [
            'Employee,Department,Review Period,Goal Completion,KPI Achievement,Appraisal Score,360 Feedback,Overall Score,Status',
        ];

        foreach ($rows as $row) {
            $lines[] = implode(',', [
                $this->csvEscape((string) ($row['employee_name'] ?? '')),
                $this->csvEscape((string) ($row['department'] ?? '')),
                $this->csvEscape((string) ($row['review_period'] ?? '')),
                $this->csvEscape((string) ($row['goal_completion'] ?? '')),
                $this->csvEscape((string) ($row['kpi_achievement'] ?? '')),
                $this->csvEscape((string) ($row['appraisal_score'] ?? '')),
                $this->csvEscape((string) ($row['feedback_score'] ?? '')),
                $this->csvEscape((string) ($row['overall_score'] ?? '')),
                $this->csvEscape((string) ($row['status'] ?? '')),
            ]);
        }

        return implode("\n", $lines);
    }

    private function csvEscape(string $value): string
    {
        $value = str_replace('"', '""', $value);
        if (strpbrk($value, ",\n\r\"") !== false) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }

        $action = strtolower(trim((string) $_POST['action']));

        if ($action === 'export_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="performance-report.csv"');
            echo $this->exportCsv($_POST);
            exit;
        }

        if ($action === 'view_report') {
            $query = [];
            foreach (['department', 'employee', 'review_period', 'performance_rating', 'status'] as $field) {
                if (isset($_POST[$field]) && trim((string) $_POST[$field]) !== '') {
                    $query[$field] = trim((string) $_POST[$field]);
                }
            }

            $location = '?page=performance-report';
            if (!empty($query)) {
                $location .= '&' . http_build_query($query);
            }

            header('Location: ' . $location);
            exit;
        }
    }
}
