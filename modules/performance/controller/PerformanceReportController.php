<?php

require_once __DIR__ . '/../model/PerformanceReportModel.php';

class PerformanceReportController
{
    private PerformanceReportModel $model;

    public function __construct(?PDO $pdo = null)
    {
        $this->model = new PerformanceReportModel($pdo);
    }

    public function getData(array $filters = []): array
    {
        $all = $this->model->getEmployees();
        $employees = array_values(array_filter($all, static function (array $employee) use ($filters): bool {
            $search = strtolower(trim((string) ($filters['search'] ?? '')));
            return (!$search || str_contains(strtolower($employee['employee_name'] . ' ' . $employee['position']), $search))
                && (!$filters['department'] || (string) $employee['department'] === (string) $filters['department'])
                && (!$filters['status'] || $employee['status'] === $filters['status'])
                && (!$filters['school_year'] || (string) ($employee['school_year'] ?? '') === (string) $filters['school_year']);
        }));
        $counts = array_fill_keys(['Good Performance', 'Average Performance', 'Needs Improvement', 'Not Yet Evaluated'], 0);
        foreach ($all as $employee) $counts[$employee['status']]++;
        $selected = null;
        foreach ($employees as $employee) if ((int) $employee['employee_id'] === (int) ($filters['employee_id'] ?? 0)) $selected = $this->model->details($employee);
        return ['employees' => $employees, 'counts' => $counts, 'selected' => $selected, 'departments' => array_values(array_unique(array_filter(array_column($all, 'department')))), 'school_years' => array_values(array_unique(array_filter(array_column($all, 'school_year'))))];
    }

    public function export(array $filters): void
    {
        $data = $this->getData($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="performance-report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Employee Name', 'Position', 'Department', 'KPI Score', 'Attendance', 'Appraisal', '360 Feedback', 'Goal Achievement', 'Overall', 'Status']);
        foreach ($data['employees'] as $employee) fputcsv($output, [$employee['employee_name'], $employee['position'], $employee['department'], $employee['kpi'], $employee['attendance'], $employee['appraisal'], $employee['feedback'], $employee['goals'], $employee['overall'], $employee['status']]);
        fclose($output);
        exit;
    }
}