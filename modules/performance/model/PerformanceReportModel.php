<?php

require_once __DIR__ . '/../../../database/db.php';

class PerformanceReportModel
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

    private function getEmployeeRows(): array
    {
        $table = $this->employeeTable();
        if (!$this->tableExists($table)) {
            return [];
        }

        // Resolve possible column names; fall back to null when missing
        $firstNameCol = $this->columnExists($table, 'first_name') ? 'first_name' : ($this->columnExists($table, 'employee_first_name') ? 'employee_first_name' : null);
        $lastNameCol = $this->columnExists($table, 'last_name') ? 'last_name' : ($this->columnExists($table, 'employee_last_name') ? 'employee_last_name' : null);
        $employeeIdCol = $this->columnExists($table, 'employee_id') ? 'employee_id' : ($this->columnExists($table, 'id') ? 'id' : null);

        if ($this->columnExists($table, 'department')) {
            $departmentCol = 'department';
        } elseif ($this->columnExists($table, 'department_name')) {
            $departmentCol = 'department_name';
        } else {
            $departmentCol = null;
        }

        // Build safe SQL fragments
        $firstExpr = $firstNameCol ? "COALESCE({$firstNameCol}, '')" : "''";
        $lastExpr = $lastNameCol ? "COALESCE({$lastNameCol}, '')" : "''";
        $employeeIdExpr = $employeeIdCol ? "{$employeeIdCol} AS employee_id" : "NULL AS employee_id";
        $departmentExpr = $departmentCol ? "COALESCE({$departmentCol}, 'General') AS department" : "'General' AS department";

        $whereClause = "1=1";
        if ($firstNameCol || $lastNameCol) {
            $nameConditions = [];
            if ($firstNameCol) {
                $nameConditions[] = "{$firstExpr} <> ''";
            }
            if ($lastNameCol) {
                $nameConditions[] = "{$lastExpr} <> ''";
            }
            $whereClause = !empty($nameConditions) ? implode(' OR ', $nameConditions) : '1=1';
        }

        $sql = "SELECT {$employeeIdExpr}, CONCAT({$firstExpr}, ' ', {$lastExpr}) AS employee_name, {$departmentExpr}
                FROM {$table}
                WHERE {$whereClause}
                ORDER BY employee_name ASC";

        return $this->fetchAll($sql);
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name
                  AND column_name = :column_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
        $stmt->bindValue(':column_name', $column, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function getTableRows(string $table): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }

        $stmt = $this->conn->query('SELECT * FROM ' . $table);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function normalizeRating(string $value): string
    {
        $score = $this->toFloat($value);

        if ($score >= 90) {
            return 'Excellent';
        }

        if ($score >= 80) {
            return 'Good';
        }

        if ($score >= 70) {
            return 'Average';
        }

        return 'Fair';
    }

    private function buildFallbackStats(): array
    {
        return [
            'total_employees' => 128,
            'average_performance_score' => 82.6,
            'goal_completion_rate' => 78.4,
            'average_kpi_achievement' => 82.6,
            'average_appraisal_score' => 4.1,
            'average_360_feedback' => 36,
            'employees_needing_development' => 22,
            'training_recommendations' => 9,
        ];
    }

    private function buildFallbackRows(array $filters = []): array
    {
        $rows = [
            ['employee_name' => 'Alicia Gomez', 'department' => 'Engineering', 'review_period' => 'Q2 2026', 'goal_completion' => 92, 'kpi_achievement' => 91, 'appraisal_score' => 94, 'feedback_score' => 88, 'status' => 'Excellent'],
            ['employee_name' => 'Marcus Lee', 'department' => 'Sales', 'review_period' => 'Q2 2026', 'goal_completion' => 84, 'kpi_achievement' => 86, 'appraisal_score' => 88, 'feedback_score' => 82, 'status' => 'Good'],
            ['employee_name' => 'Priya Shah', 'department' => 'Finance', 'review_period' => 'Q2 2026', 'goal_completion' => 78, 'kpi_achievement' => 80, 'appraisal_score' => 82, 'feedback_score' => 76, 'status' => 'Average'],
            ['employee_name' => 'Daniel Ross', 'department' => 'Operations', 'review_period' => 'Q2 2026', 'goal_completion' => 70, 'kpi_achievement' => 72, 'appraisal_score' => 74, 'feedback_score' => 69, 'status' => 'Average'],
            ['employee_name' => 'Sara Ali', 'department' => 'HR', 'review_period' => 'Q2 2026', 'goal_completion' => 65, 'kpi_achievement' => 68, 'appraisal_score' => 71, 'feedback_score' => 66, 'status' => 'Fair'],
            ['employee_name' => 'Jason Wu', 'department' => 'IT', 'review_period' => 'Q2 2026', 'goal_completion' => 88, 'kpi_achievement' => 90, 'appraisal_score' => 92, 'feedback_score' => 87, 'status' => 'Excellent'],
        ];

        if (!empty($filters['department']) && strtolower((string) $filters['department']) !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                return strtolower((string) ($row['department'] ?? '')) === strtolower((string) $filters['department']);
            }));
        }

        if (!empty($filters['employee']) && strtolower((string) $filters['employee']) !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                return strtolower((string) ($row['employee_name'] ?? '')) === strtolower((string) $filters['employee']);
            }));
        }

        if (!empty($filters['review_period']) && strtolower((string) $filters['review_period']) !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                return strtolower((string) ($row['review_period'] ?? '')) === strtolower((string) $filters['review_period']);
            }));
        }

        if (!empty($filters['performance_rating']) && strtolower((string) $filters['performance_rating']) !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                return strtolower((string) ($row['status'] ?? '')) === strtolower((string) $filters['performance_rating']);
            }));
        }

        if (!empty($filters['status']) && strtolower((string) $filters['status']) !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                return strtolower((string) ($row['status'] ?? '')) === strtolower((string) $filters['status']);
            }));
        }

        foreach ($rows as &$row) {
            $row['overall_score'] = round(($this->toFloat($row['goal_completion']) + $this->toFloat($row['kpi_achievement']) + $this->toFloat($row['appraisal_score']) + $this->toFloat($row['feedback_score'])) / 4, 1);
            $row['status'] = $this->normalizeRating((string) $row['overall_score']);
        }
        unset($row);

        return $rows;
    }

    public function getDepartments(): array
    {
        $departments = [];

        foreach ($this->getEmployeeRows() as $employee) {
            $department = trim((string) ($employee['department'] ?? ''));
            if ($department !== '') {
                $departments[$department] = $department;
            }
        }

        if (!empty($departments)) {
            ksort($departments);
            return array_values($departments);
        }

        return ['Engineering', 'Finance', 'HR', 'IT', 'Operations', 'Sales'];
    }

    public function getReviewPeriods(): array
    {
        $periods = [];

        foreach (['pm_appraisals', 'pm_goals', 'pm_feedback_360_entries', 'pm_review_cycles'] as $table) {
            foreach ($this->getTableRows($table) as $row) {
                $period = trim((string) ($row['review_period'] ?? $row['cycle_period'] ?? $row['period'] ?? ''));
                if ($period !== '') {
                    $periods[$period] = $period;
                }
            }
        }

        if (!empty($periods)) {
            ksort($periods);
            return array_values($periods);
        }

        return ['Q2 2026', 'Q1 2026', 'Q4 2025'];
    }

    public function getReportRows(array $filters = []): array
    {
        $employeeRows = $this->getEmployeeRows();
        $reportRows = [];

        if (empty($employeeRows) && empty($this->getTableRows('pm_appraisals')) && empty($this->getTableRows('pm_goals')) && empty($this->getTableRows('pm_feedback_360_entries')) && empty($this->getTableRows('pm_training_recommendations'))) {
            return $this->buildFallbackRows($filters);
        }

        $employeeIndex = [];
        foreach ($employeeRows as $employee) {
            $key = (string) ($employee['employee_id'] ?? $employee['employee_name'] ?? uniqid('emp_', true));
            $employeeIndex[$key] = [
                'employee_id' => $employee['employee_id'] ?? $key,
                'employee_name' => $employee['employee_name'] ?? 'Unknown Employee',
                'department' => $employee['department'] ?? 'General',
                'review_period' => 'Q2 2026',
                'goal_completion' => 0,
                'kpi_achievement' => 0,
                'appraisal_score' => 0,
                'feedback_score' => 0,
                'training_count' => 0,
                'status' => 'Fair',
            ];
        }

        foreach ($this->getTableRows('pm_goals') as $row) {
            $employeeId = $row['employee_id'] ?? $row['user_id'] ?? null;
            $employeeKey = $employeeId !== null ? (string) $employeeId : trim((string) ($row['employee_name'] ?? ''));
            if (!isset($employeeIndex[$employeeKey])) {
                $employeeName = trim((string) ($row['employee_name'] ?? $employeeKey));
                $employeeIndex[$employeeKey] = [
                    'employee_id' => $employeeId ?? $employeeKey,
                    'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    'department' => trim((string) ($row['department'] ?? 'General')),
                    'review_period' => trim((string) ($row['review_period'] ?? 'Q2 2026')),
                    'goal_completion' => 0,
                    'kpi_achievement' => 0,
                    'appraisal_score' => 0,
                    'feedback_score' => 0,
                    'training_count' => 0,
                    'status' => 'Fair',
                ];
            }

            $completionValue = $row['completion_percentage'] ?? $row['progress_percentage'] ?? $row['progress'] ?? null;
            if ($completionValue !== null) {
                $employeeIndex[$employeeKey]['goal_completion'] = $this->toFloat($completionValue);
            }

            $period = trim((string) ($row['review_period'] ?? $row['period'] ?? ''));
            if ($period !== '') {
                $employeeIndex[$employeeKey]['review_period'] = $period;
            }
        }

        foreach ($this->getTableRows('pm_appraisals') as $row) {
            $employeeId = $row['employee_id'] ?? $row['user_id'] ?? null;
            $employeeKey = $employeeId !== null ? (string) $employeeId : trim((string) ($row['employee_name'] ?? ''));
            if (!isset($employeeIndex[$employeeKey])) {
                $employeeName = trim((string) ($row['employee_name'] ?? $employeeKey));
                $employeeIndex[$employeeKey] = [
                    'employee_id' => $employeeId ?? $employeeKey,
                    'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    'department' => trim((string) ($row['department'] ?? 'General')),
                    'review_period' => trim((string) ($row['review_period'] ?? 'Q2 2026')),
                    'goal_completion' => 0,
                    'kpi_achievement' => 0,
                    'appraisal_score' => 0,
                    'feedback_score' => 0,
                    'training_count' => 0,
                    'status' => 'Fair',
                ];
            }

            $score = $row['overall_rating'] ?? $row['final_score'] ?? $row['rating'] ?? null;
            if ($score !== null) {
                $employeeIndex[$employeeKey]['appraisal_score'] = $this->toFloat($score);
            }

            $period = trim((string) ($row['review_period'] ?? $row['period'] ?? ''));
            if ($period !== '') {
                $employeeIndex[$employeeKey]['review_period'] = $period;
            }

            $department = trim((string) ($row['department'] ?? ''));
            if ($department !== '') {
                $employeeIndex[$employeeKey]['department'] = $department;
            }
        }

        foreach ($this->getTableRows('pm_feedback_360_entries') as $row) {
            $employeeId = $row['employee_id'] ?? $row['user_id'] ?? null;
            $employeeKey = $employeeId !== null ? (string) $employeeId : trim((string) ($row['employee_name'] ?? ''));
            if (!isset($employeeIndex[$employeeKey])) {
                $employeeName = trim((string) ($row['employee_name'] ?? $employeeKey));
                $employeeIndex[$employeeKey] = [
                    'employee_id' => $employeeId ?? $employeeKey,
                    'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    'department' => trim((string) ($row['department'] ?? 'General')),
                    'review_period' => trim((string) ($row['review_period'] ?? 'Q2 2026')),
                    'goal_completion' => 0,
                    'kpi_achievement' => 0,
                    'appraisal_score' => 0,
                    'feedback_score' => 0,
                    'training_count' => 0,
                    'status' => 'Fair',
                ];
            }

            $score = $row['overall_rating'] ?? $row['average_score'] ?? $row['rating'] ?? $row['feedback_score'] ?? null;
            if ($score !== null) {
                $employeeIndex[$employeeKey]['feedback_score'] = $this->toFloat($score);
            }

            $period = trim((string) ($row['review_period'] ?? $row['period'] ?? ''));
            if ($period !== '') {
                $employeeIndex[$employeeKey]['review_period'] = $period;
            }
        }

        foreach (['pm_kpis', 'kpi_assignments', 'pm_kpi_assignments'] as $table) {
            foreach ($this->getTableRows($table) as $row) {
                $employeeId = $row['employee_id'] ?? $row['user_id'] ?? null;
                $employeeKey = $employeeId !== null ? (string) $employeeId : trim((string) ($row['employee_name'] ?? ''));
                if (!isset($employeeIndex[$employeeKey])) {
                    $employeeName = trim((string) ($row['employee_name'] ?? $employeeKey));
                    $employeeIndex[$employeeKey] = [
                        'employee_id' => $employeeId ?? $employeeKey,
                        'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                        'department' => trim((string) ($row['department'] ?? 'General')),
                        'review_period' => trim((string) ($row['review_period'] ?? 'Q2 2026')),
                        'goal_completion' => 0,
                        'kpi_achievement' => 0,
                        'appraisal_score' => 0,
                        'feedback_score' => 0,
                        'training_count' => 0,
                        'status' => 'Fair',
                    ];
                }

                $score = $row['achievement_percentage'] ?? $row['kpi_achievement'] ?? $row['score'] ?? null;
                if ($score !== null) {
                    $employeeIndex[$employeeKey]['kpi_achievement'] = $this->toFloat($score);
                }
            }
        }

        foreach ($this->getTableRows('pm_training_recommendations') as $row) {
            $employeeId = $row['employee_id'] ?? $row['user_id'] ?? null;
            $employeeKey = $employeeId !== null ? (string) $employeeId : trim((string) ($row['employee_name'] ?? ''));
            if (!isset($employeeIndex[$employeeKey])) {
                $employeeName = trim((string) ($row['employee_name'] ?? $employeeKey));
                $employeeIndex[$employeeKey] = [
                    'employee_id' => $employeeId ?? $employeeKey,
                    'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    'department' => trim((string) ($row['department'] ?? 'General')),
                    'review_period' => trim((string) ($row['review_period'] ?? 'Q2 2026')),
                    'goal_completion' => 0,
                    'kpi_achievement' => 0,
                    'appraisal_score' => 0,
                    'feedback_score' => 0,
                    'training_count' => 0,
                    'status' => 'Fair',
                ];
            }

            $employeeIndex[$employeeKey]['training_count'] = (int) ($employeeIndex[$employeeKey]['training_count'] ?? 0) + 1;
        }

        foreach ($employeeIndex as $employee) {
            $overallScore = 0.0;
            $totalComponents = 0;
            $goalCompletion = $this->toFloat($employee['goal_completion']);
            $kpiAchievement = $this->toFloat($employee['kpi_achievement']);
            $appraisalScore = $this->toFloat($employee['appraisal_score']);
            $feedbackScore = $this->toFloat($employee['feedback_score']);

            if ($goalCompletion > 0) {
                $overallScore += $goalCompletion;
                $totalComponents++;
            }
            if ($kpiAchievement > 0) {
                $overallScore += $kpiAchievement;
                $totalComponents++;
            }
            if ($appraisalScore > 0) {
                $overallScore += $appraisalScore;
                $totalComponents++;
            }
            if ($feedbackScore > 0) {
                $overallScore += $feedbackScore;
                $totalComponents++;
            }

            $averageScore = $totalComponents > 0 ? round($overallScore / $totalComponents, 1) : 0.0;
            $status = $this->normalizeRating((string) $averageScore);

            $reportRows[] = [
                'employee_id' => $employee['employee_id'],
                'employee_name' => $employee['employee_name'],
                'department' => $employee['department'],
                'review_period' => $employee['review_period'],
                'goal_completion' => round($goalCompletion, 1),
                'kpi_achievement' => round($kpiAchievement, 1),
                'appraisal_score' => round($appraisalScore, 1),
                'feedback_score' => round($feedbackScore, 1),
                'training_count' => (int) ($employee['training_count'] ?? 0),
                'overall_score' => $averageScore,
                'status' => $status,
                // how many components contributed to the average (used to filter out unevaluated employees)
                'component_count' => $totalComponents,
            ];
        }

        // Remove employees that have no evaluation components (no goal/kpi/appraisal/feedback recorded)
        $reportRows = array_values(array_filter($reportRows, function ($r) {
            return isset($r['component_count']) && (int) $r['component_count'] > 0;
        }));

        if (empty($reportRows)) {
            return $this->buildFallbackRows($filters);
        }

        if (!empty($filters['department']) && strtolower((string) $filters['department']) !== 'all') {
            $department = strtolower((string) $filters['department']);
            $reportRows = array_values(array_filter($reportRows, function ($row) use ($department) {
                return strtolower((string) ($row['department'] ?? '')) === $department;
            }));
        }

        if (!empty($filters['employee']) && strtolower((string) $filters['employee']) !== 'all') {
            $employeeName = strtolower((string) $filters['employee']);
            $reportRows = array_values(array_filter($reportRows, function ($row) use ($employeeName) {
                return strtolower((string) ($row['employee_name'] ?? '')) === $employeeName;
            }));
        }

        if (!empty($filters['review_period']) && strtolower((string) $filters['review_period']) !== 'all') {
            $period = strtolower((string) $filters['review_period']);
            $reportRows = array_values(array_filter($reportRows, function ($row) use ($period) {
                return strtolower((string) ($row['review_period'] ?? '')) === $period;
            }));
        }

        if (!empty($filters['performance_rating']) && strtolower((string) $filters['performance_rating']) !== 'all') {
            $rating = strtolower((string) $filters['performance_rating']);
            $reportRows = array_values(array_filter($reportRows, function ($row) use ($rating) {
                return strtolower((string) ($row['status'] ?? '')) === $rating;
            }));
        }

        if (!empty($filters['status']) && strtolower((string) $filters['status']) !== 'all') {
            $status = strtolower((string) $filters['status']);
            $reportRows = array_values(array_filter($reportRows, function ($row) use ($status) {
                return strtolower((string) ($row['status'] ?? '')) === $status;
            }));
        }

        usort($reportRows, function ($left, $right) {
            return $right['overall_score'] <=> $left['overall_score'];
        });

        return $reportRows;
    }

    public function getDashboardStats(array $filters = []): array
    {
        $reportRows = $this->getReportRows($filters);

        if (empty($reportRows)) {
            return $this->buildFallbackStats();
        }

        $scores = [];
        $goalScores = [];
        $kpiScores = [];
        $appraisalScores = [];
        $feedbackScores = [];
        $developmentCount = 0;

        foreach ($reportRows as $row) {
            $scores[] = $this->toFloat($row['overall_score']);
            $goalScores[] = $this->toFloat($row['goal_completion']);
            $kpiScores[] = $this->toFloat($row['kpi_achievement']);
            $appraisalScores[] = $this->toFloat($row['appraisal_score']);
            $feedbackScores[] = $this->toFloat($row['feedback_score']);

            if (strtolower((string) ($row['status'] ?? '')) !== 'excellent' && $this->toFloat($row['overall_score']) < 75) {
                $developmentCount++;
            }
        }

        return [
            'total_employees' => count($reportRows),
            'average_performance_score' => round(array_sum($scores) / count($scores), 1),
            'goal_completion_rate' => round(array_sum($goalScores) / count($goalScores), 1),
            'average_kpi_achievement' => round(array_sum($kpiScores) / count($kpiScores), 1),
            'average_appraisal_score' => round(array_sum($appraisalScores) / count($appraisalScores), 1),
            'average_360_feedback' => round(array_sum($feedbackScores) / count($feedbackScores), 1),
            'employees_needing_development' => $developmentCount,
            'training_recommendations' => array_sum(array_map(static fn ($row) => (int) ($row['training_count'] ?? 0), $reportRows)),
        ];
    }
}
