<?php

require_once __DIR__ . '/../../../database/db.php';

class PerformanceReportModel
{
    private PDO $conn;
    private array $tables = [];
    private array $columns = [];

    public function __construct(?PDO $pdo = null)
    {
        $this->conn = $pdo instanceof PDO ? $pdo : (new Database())->getConnection();
    }

    private function tableExists(string $table): bool
    {
        if (!array_key_exists($table, $this->tables)) {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
            $stmt->execute([':table' => $table]);
            $this->tables[$table] = (int) $stmt->fetchColumn() > 0;
        }
        return $this->tables[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) return false;
        if (!isset($this->columns[$table])) {
            $stmt = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
            $this->columns[$table] = array_fill_keys(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field'), true);
        }
        return isset($this->columns[$table][$column]);
    }

    private function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getEmployees(): array
    {
        if (!$this->tableExists('em_employees')) return [];
        $department = $this->tableExists('em_departments') ? 'COALESCE(d.department_name, CAST(e.department_id AS CHAR))' : 'CAST(e.department_id AS CHAR)';
        $position = $this->tableExists('em_positions') ? 'p.position_name' : "''";
        $archived = $this->hasColumn('em_employees', 'is_archived') ? 'AND (e.is_archived = 0 OR e.is_archived IS NULL)' : '';
        $rows = $this->query("SELECT e.employee_id, e.employee_code, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name, e.suffix) AS employee_name,
                {$department} AS department, {$position} AS position
                FROM em_employees e
                " . ($this->tableExists('em_departments') ? 'LEFT JOIN em_departments d ON d.department_id = e.department_id' : '') . "
                " . ($this->tableExists('em_positions') ? 'LEFT JOIN em_positions p ON p.position_id = e.position_id' : '') . "
                WHERE 1=1 {$archived} ORDER BY employee_name");
        $scores = $this->scoreRows();
        foreach ($rows as &$employee) {
            $employee = array_merge($employee, $scores[(int) $employee['employee_id']] ?? $this->emptyScores());
            $employee['overall'] = $this->overall($employee);
            $employee['status'] = $this->classification($employee['overall']);
        }
        return $rows;
    }

    private function emptyScores(): array
    {
        return ['kpi' => null, 'attendance' => null, 'appraisal' => null, 'feedback' => null, 'goals' => null, 'school_year' => null, 'evaluated' => false];
    }

    private function scoreRows(): array
    {
        $scores = [];
        $sources = [];
        if ($this->tableExists('pm_performance_reports') && $this->hasColumn('pm_performance_reports', 'employee_id')) {
            $fields = [];
            foreach (['kpi_health_score' => 'kpi', 'feedback_average' => 'feedback', 'goal_completion_rate' => 'goals', 'overall_rating' => 'appraisal'] as $column => $key) {
                if ($this->hasColumn('pm_performance_reports', $column)) $fields[] = "AVG(`{$column}`) AS `{$key}`";
            }
            $period = $this->hasColumn('pm_performance_reports', 'review_period') ? ', MAX(review_period) AS school_year' : '';
            if ($fields) $sources[] = $this->query('SELECT employee_id, ' . implode(',', $fields) . "{$period} FROM pm_performance_reports WHERE employee_id IS NOT NULL GROUP BY employee_id");
        }
        if ($this->tableExists('pm_appraisals') && $this->hasColumn('pm_appraisals', 'employee_id')) {
            $rating = $this->hasColumn('pm_appraisals', 'overall_rating') ? 'overall_rating' : ($this->hasColumn('pm_appraisals', 'overall_score') ? 'overall_score' : null);
            if ($rating) $sources[] = $this->query("SELECT employee_id, AVG(`{$rating}`) AS appraisal FROM pm_appraisals WHERE employee_id IS NOT NULL GROUP BY employee_id");
        }
        if ($this->tableExists('pm_kpi_entries') && $this->tableExists('pm_kpi_assignments')) $sources[] = $this->query('SELECT a.assignee_id AS employee_id, AVG(e.performance_score) AS kpi FROM pm_kpi_entries e JOIN pm_kpi_assignments a ON a.assignment_id = e.assignment_id WHERE e.performance_score IS NOT NULL GROUP BY a.assignee_id');
        if ($this->tableExists('pm_feedback_summary')) $sources[] = $this->query('SELECT employee_id, AVG(overall_score) AS feedback FROM pm_feedback_summary WHERE overall_score IS NOT NULL GROUP BY employee_id');
        elseif ($this->tableExists('pm_360_feedback')) $sources[] = $this->query('SELECT employee_id, AVG(rating) * 20 AS feedback FROM pm_360_feedback WHERE rating IS NOT NULL GROUP BY employee_id');
        elseif ($this->tableExists('pm_feedback_360_entries')) $sources[] = $this->query('SELECT employee_id, AVG(COALESCE(overall_rating, rating * 20)) AS feedback FROM pm_feedback_360_entries GROUP BY employee_id');
        if ($this->tableExists('pm_goals')) $sources[] = $this->query('SELECT employee_id, AVG(progress_percentage) AS goals FROM pm_goals WHERE progress_percentage IS NOT NULL GROUP BY employee_id');
        if ($this->tableExists('ta_attendance')) $sources[] = $this->query("SELECT employee_id, AVG(CASE WHEN status IN ('PRESENT','LATE','EARLY_OUT') THEN 100 ELSE 0 END) AS attendance FROM ta_attendance GROUP BY employee_id");
        if ($this->tableExists('pm_reports')) $sources[] = $this->query('SELECT employee_id, AVG(kpi_score) AS kpi, AVG(attendance_score) AS attendance, AVG(final_rating_percent) AS appraisal FROM pm_reports GROUP BY employee_id');
        foreach ($sources as $rows) foreach ($rows as $row) {
            $id = (int) $row['employee_id'];
            $scores[$id] ??= $this->emptyScores();
            foreach (['kpi', 'attendance', 'appraisal', 'feedback', 'goals'] as $key) if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') $scores[$id][$key] = round((float) $row[$key], 1);
            if (!empty($row['school_year'])) $scores[$id]['school_year'] = $row['school_year'];
            $scores[$id]['evaluated'] = true;
        }
        return $scores;
    }

    private function overall(array $employee): ?float
    {
        $values = array_filter([$employee['kpi'], $employee['attendance'], $employee['appraisal'], $employee['feedback'], $employee['goals']], static fn($value) => $value !== null);
        return $values ? round(array_sum($values) / count($values), 1) : null;
    }

    public function classification(?float $score): string
    {
        if ($score === null) return 'Not Yet Evaluated';
        if ($score >= 80) return 'Good Performance';
        if ($score >= 70) return 'Average Performance';
        return 'Needs Improvement';
    }

    public function details(array $employee): array
    {
        $areas = [];
        foreach (['kpi' => 'KPI achievement', 'attendance' => 'Attendance', 'appraisal' => 'Appraisal results', 'feedback' => 'Teamwork and communication', 'goals' => 'Goal completion'] as $key => $label) if ($employee[$key] !== null && $employee[$key] < 70) $areas[] = $label;
        $strengths = [];
        foreach (['kpi' => 'Consistently achieved assigned KPIs', 'attendance' => 'Good attendance record', 'feedback' => 'Strong teamwork feedback', 'goals' => 'Completed goals on schedule', 'appraisal' => 'Strong appraisal results'] as $key => $label) if ($employee[$key] !== null && $employee[$key] >= 80) $strengths[] = $label;
        $employee['areas'] = $areas;
        $employee['strengths'] = $strengths;
        $employee['recommendation'] = $employee['overall'] !== null && $employee['overall'] < 80 ? 'Skills development, coaching, and a follow-up evaluation are recommended.' : 'Consider advanced training, leadership development, or a career development plan.';
        return $employee;
    }
}