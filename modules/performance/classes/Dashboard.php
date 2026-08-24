<?php

require_once __DIR__ . '/../../../database/db.php';

class PerformanceDashboard
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

    public function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function fetchScalar(string $sql, array $params = []): ?string
    {
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value['value'], $value['type'] ?? PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetchColumn();

        return $result === false ? null : (string) $result;
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

    public function getOverviewStats(): array
    {
        $stats = [
            'total_employees' => 0,
            'active_evaluations' => 0,
            'pending_appraisals' => 0,
            'completed_appraisals' => 0,
            'pending_feedback' => 0,
            'training_recommendations' => 0,
            'employees_needing_attention' => 0,
            'performance_goals' => 0,
            'recent_activity_count' => 0,
        ];

        $employeeTable = $this->employeeTable();
        if ($this->tableExists($employeeTable)) {
            $statusColumn = $employeeTable === 'em_employees' ? 'employment_status' : 'status';
            $stats['total_employees'] = (int) $this->fetchScalar(
                "SELECT COUNT(*) FROM {$employeeTable} WHERE {$statusColumn} IN ('active','Active','ACTIVE') OR {$statusColumn} IS NULL"
            );
        }

        if ($this->tableExists('pm_appraisals')) {
            $stats['active_evaluations'] = (int) $this->fetchScalar(
                "SELECT COUNT(*) FROM pm_appraisals WHERE status NOT IN ('Completed', 'Completed ', 'Cancelled', 'Archived', 'Closed')"
            );
            $stats['pending_appraisals'] = (int) $this->fetchScalar(
                "SELECT COUNT(*) FROM pm_appraisals WHERE status IN ('Pending', 'Not Started', 'In Progress', 'In Progress ')"
            );
            $stats['completed_appraisals'] = (int) $this->fetchScalar(
                "SELECT COUNT(*) FROM pm_appraisals WHERE status = 'Completed'"
            );
        }

        if ($this->tableExists('pm_feedback_360_entries')) {
            $stats['pending_feedback'] = (int) $this->fetchScalar(
                "SELECT COUNT(*) FROM pm_feedback_360_entries WHERE feedback_status = 'Pending'"
            );
        }

        if ($this->tableExists('pm_training_recommendations')) {
            $stats['training_recommendations'] = (int) $this->fetchScalar(
                'SELECT COUNT(*) FROM pm_training_recommendations'
            );
        }

        if ($this->tableExists('pm_goals')) {
            $stats['performance_goals'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_goals');
        }

        if ($this->tableExists('kpi_assignments')) {
            $stats['performance_goals'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM kpi_assignments') + $stats['performance_goals'];
        }

        $stats['employees_needing_attention'] = count($this->getAttentionEmployees());
        $stats['recent_activity_count'] = count($this->getRecentActivities());

        return $stats;
    }

    public function getPerformanceSummary(): array
    {
        $summary = [
            'average_rating' => null,
            'top_employee' => 'No data available',
            'lowest_employee' => 'No data available',
            'overall_status' => 'No data available',
        ];

        if ($this->tableExists('pm_appraisals')) {
            $ratingRows = $this->fetchAll(
                'SELECT employee_name, overall_rating FROM pm_appraisals WHERE overall_rating IS NOT NULL AND overall_rating > 0 ORDER BY overall_rating DESC'
            );

            if (!empty($ratingRows)) {
                $scores = array_map(fn($row) => (float) $row['overall_rating'], $ratingRows);
                $summary['average_rating'] = round(array_sum($scores) / count($scores), 1);
                $summary['top_employee'] = $ratingRows[0]['employee_name'] ?? 'No data available';
                $summary['lowest_employee'] = end($ratingRows)['employee_name'] ?? 'No data available';

                if ($summary['average_rating'] >= 90) {
                    $summary['overall_status'] = 'Outstanding';
                } elseif ($summary['average_rating'] >= 80) {
                    $summary['overall_status'] = 'Exceeds Expectations';
                } elseif ($summary['average_rating'] >= 70) {
                    $summary['overall_status'] = 'Meets Expectations';
                } elseif ($summary['average_rating'] >= 60) {
                    $summary['overall_status'] = 'Needs Improvement';
                } else {
                    $summary['overall_status'] = 'Unsatisfactory';
                }
            }
        }

        return $summary;
    }

    public function getPerformanceDistribution(): array
    {
        $distribution = [
            'Outstanding' => 0,
            'Exceeds Expectations' => 0,
            'Meets Expectations' => 0,
            'Needs Improvement' => 0,
            'Unsatisfactory' => 0,
        ];

        $ratingRows = [];

        if ($this->tableExists('pm_appraisals')) {
            $ratingRows = $this->fetchAll(
                'SELECT overall_rating FROM pm_appraisals WHERE overall_rating IS NOT NULL AND overall_rating > 0'
            );
        }

        if (empty($ratingRows) && $this->tableExists('pm_performance_reports')) {
            $ratingRows = $this->fetchAll(
                'SELECT overall_rating FROM pm_performance_reports WHERE overall_rating IS NOT NULL AND overall_rating > 0'
            );
        }

        foreach ($ratingRows as $row) {
            $score = (float) $row['overall_rating'];

            if ($score >= 90) {
                $distribution['Outstanding']++;
            } elseif ($score >= 80) {
                $distribution['Exceeds Expectations']++;
            } elseif ($score >= 70) {
                $distribution['Meets Expectations']++;
            } elseif ($score >= 60) {
                $distribution['Needs Improvement']++;
            } else {
                $distribution['Unsatisfactory']++;
            }
        }

        return $distribution;
    }

    public function getTopPerformers(int $limit = 5): array
    {
        if (!$this->tableExists('pm_appraisals')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT employee_name, overall_rating, department, status
             FROM pm_appraisals
             WHERE overall_rating IS NOT NULL AND overall_rating > 0
             ORDER BY overall_rating DESC, updated_at DESC
             LIMIT :limit',
            [':limit' => ['value' => $limit, 'type' => PDO::PARAM_INT]]
        );
    }

    public function getNeedsImprovement(int $limit = 5): array
    {
        if (!$this->tableExists('pm_appraisals')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT employee_name, overall_rating, department, status
             FROM pm_appraisals
             WHERE overall_rating IS NOT NULL AND overall_rating < 70
             ORDER BY overall_rating ASC, updated_at DESC
             LIMIT :limit',
            [':limit' => ['value' => $limit, 'type' => PDO::PARAM_INT]]
        );
    }

    public function getKpiSummary(): array
    {
        $summary = [
            'total_kpis' => 0,
            'assigned_kpis' => 0,
            'completed_kpis' => 0,
            'in_progress_kpis' => 0,
            'at_risk_kpis' => 0,
        ];

        if ($this->tableExists('kpi_definitions')) {
            $summary['total_kpis'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM kpi_definitions WHERE is_active = 1');
        }

        if ($this->tableExists('kpi_assignments')) {
            $summary['assigned_kpis'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM kpi_assignments');
            $summary['completed_kpis'] = (int) $this->fetchScalar(
                "SELECT COUNT(DISTINCT assignment_id) FROM kpi_entries WHERE performance_status = 'Completed'"
            );
            $summary['in_progress_kpis'] = (int) $this->fetchScalar(
                "SELECT COUNT(DISTINCT assignment_id) FROM kpi_entries WHERE performance_status IN ('On Track', 'Behind', 'At Risk', 'Not Started')"
            );
            $summary['at_risk_kpis'] = (int) $this->fetchScalar(
                "SELECT COUNT(DISTINCT assignment_id) FROM kpi_entries WHERE performance_status IN ('At Risk', 'Behind')"
            );
        }

        if ($summary['assigned_kpis'] === 0 && $summary['total_kpis'] > 0) {
            $summary['assigned_kpis'] = $summary['total_kpis'];
        }

        return $summary;
    }

    public function getKpiTrendData(): array
    {
        if (!$this->tableExists('kpi_entries')) {
            return ['labels' => [], 'values' => []];
        }

        $rows = $this->fetchAll(
            "SELECT DATE_FORMAT(entry_date, '%b %Y') AS label, AVG(performance_score) AS avg_score
             FROM kpi_entries
             WHERE performance_score IS NOT NULL
             GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
             ORDER BY MIN(entry_date) DESC
             LIMIT 6"
        );

        $rows = array_reverse($rows);

        return [
            'labels' => array_map(fn($row) => $row['label'], $rows),
            'values' => array_map(fn($row) => round((float) $row['avg_score'], 1), $rows),
        ];
    }

    public function getAppraisalRows(int $limit = 5): array
    {
        if (!$this->tableExists('pm_appraisals')) {
            return [];
        }

        return $this->fetchAll(
            "SELECT a.employee_name AS employee,
                    COALESCE(rc.cycle_period, 'N/A') AS appraisal_period,
                    a.status,
                    a.overall_rating AS performance_rating,
                    a.due_date AS appraisal_date
             FROM pm_appraisals a
             LEFT JOIN pm_review_cycles rc ON rc.cycle_id = a.review_cycle_id
             ORDER BY a.updated_at DESC
             LIMIT :limit",
            [':limit' => ['value' => $limit, 'type' => PDO::PARAM_INT]]
        );
    }

    public function getFeedbackRows(int $limit = 5): array
    {
        if (!$this->tableExists('pm_feedback_360_entries')) {
            return [];
        }

        return $this->fetchAll(
            "SELECT employee_id,
                    review_period,
                    feedback_status,
                    overall_rating,
                    created_at
             FROM pm_feedback_360_entries
             ORDER BY created_at DESC
             LIMIT :limit",
            [':limit' => ['value' => $limit, 'type' => PDO::PARAM_INT]]
        );
    }

    public function getTrainingRows(int $limit = 5): array
    {
        if (!$this->tableExists('pm_training_recommendations')) {
            return [];
        }

        $sql = "SELECT tr.employee_id,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee,
                       tr.development_area,
                       tr.performance_gap,
                       tr.priority_level,
                       tr.status,
                       tr.recommendation_date
                FROM pm_training_recommendations tr
                LEFT JOIN {$this->employeeTable()} e ON e.employee_id = tr.employee_id
                ORDER BY tr.created_at DESC
                LIMIT :limit";

        return $this->fetchAll($sql, [':limit' => ['value' => $limit, 'type' => PDO::PARAM_INT]]);
    }

    public function getTrainingSummary(): array
    {
        $summary = [
            'total_recommendations' => 0,
            'pending_recommendations' => 0,
            'approved_recommendations' => 0,
            'completed_training' => 0,
            'high_priority_training' => 0,
        ];

        if ($this->tableExists('pm_training_recommendations')) {
            $summary['total_recommendations'] = (int) $this->fetchScalar('SELECT COUNT(*) FROM pm_training_recommendations');
            $summary['pending_recommendations'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status = 'Pending'");
            $summary['approved_recommendations'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status = 'Approved'");
            $summary['completed_training'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE status = 'Completed'");
            $summary['high_priority_training'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_training_recommendations WHERE priority_level IN ('High', 'Critical')");
        }

        return $summary;
    }

    public function getTrendData(): array
    {
        $labels = [];
        $performance = [];
        $kpis = [];
        $appraisals = [];
        $training = [];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-{$i} months");
            $months[] = $date->format('Y-m');
        }

        $labels = array_map(fn($month) => DateTime::createFromFormat('Y-m', $month)->format('M Y'), $months);

        if ($this->tableExists('pm_appraisals')) {
            $rows = $this->fetchAll(
                "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS month, AVG(overall_rating) AS avg_rating
                 FROM pm_appraisals
                 WHERE overall_rating IS NOT NULL
                 GROUP BY DATE_FORMAT(updated_at, '%Y-%m')"
            );

            foreach ($months as $month) {
                $value = 0;
                foreach ($rows as $row) {
                    if ($row['month'] === $month) {
                        $value = (float) $row['avg_rating'];
                        break;
                    }
                }
                $appraisals[] = $value;
            }
        }

        if ($this->tableExists('kpi_entries')) {
            $rows = $this->fetchAll(
                "SELECT DATE_FORMAT(entry_date, '%Y-%m') AS month, AVG(performance_score) AS avg_score
                 FROM kpi_entries
                 WHERE performance_score IS NOT NULL
                 GROUP BY DATE_FORMAT(entry_date, '%Y-%m')"
            );

            foreach ($months as $month) {
                $value = 0;
                foreach ($rows as $row) {
                    if ($row['month'] === $month) {
                        $value = (float) $row['avg_score'];
                        break;
                    }
                }
                $kpis[] = $value;
            }
        }

        if ($this->tableExists('pm_training_recommendations')) {
            $rows = $this->fetchAll(
                "SELECT DATE_FORMAT(recommendation_date, '%Y-%m') AS month, COUNT(*) AS total
                 FROM pm_training_recommendations
                 WHERE recommendation_date IS NOT NULL
                 GROUP BY DATE_FORMAT(recommendation_date, '%Y-%m')"
            );

            foreach ($months as $month) {
                $value = 0;
                foreach ($rows as $row) {
                    if ($row['month'] === $month) {
                        $value = (int) $row['total'];
                        break;
                    }
                }
                $training[] = $value;
            }
        }

        if ($this->tableExists('pm_performance_reports')) {
            $rows = $this->fetchAll(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, AVG(kpi_health_score) AS avg_score
                 FROM pm_performance_reports
                 WHERE kpi_health_score IS NOT NULL
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
            );

            foreach ($months as $month) {
                $value = 0;
                foreach ($rows as $row) {
                    if ($row['month'] === $month) {
                        $value = (float) $row['avg_score'];
                        break;
                    }
                }
                $performance[] = $value;
            }
        }

        return [
            'labels' => $labels,
            'performance' => $performance,
            'kpis' => $kpis,
            'appraisals' => $appraisals,
            'training' => $training,
        ];
    }

    public function getAttentionEmployees(): array
    {
        $result = [];

        if ($this->tableExists('pm_appraisals')) {
            $rows = $this->fetchAll(
                'SELECT employee_name, overall_rating, status
                 FROM pm_appraisals
                 WHERE overall_rating IS NOT NULL AND overall_rating < 70
                 ORDER BY overall_rating ASC, updated_at DESC'
            );

            foreach ($rows as $row) {
                $result[] = [
                    'employee' => $row['employee_name'],
                    'reason' => 'Low appraisal rating',
                    'rating' => $row['overall_rating'],
                    'status' => $row['status'],
                ];
            }
        }

        if ($this->tableExists('kpi_entries')) {
            $rows = $this->fetchAll(
                "SELECT DISTINCT e.captured_by_name AS employee, e.performance_status
                 FROM kpi_entries e
                 WHERE e.performance_status IN ('At Risk', 'Behind', 'Not Started')
                 ORDER BY e.entry_date DESC"
            );

            foreach ($rows as $row) {
                $result[] = [
                    'employee' => $row['employee'] ?: 'Unknown employee',
                    'reason' => 'KPI performance at risk',
                    'rating' => null,
                    'status' => $row['performance_status'],
                ];
            }
        }

        if ($this->tableExists('pm_training_recommendations')) {
            $rows = $this->fetchAll(
                "SELECT employee_id, priority_level, status
                 FROM pm_training_recommendations
                 WHERE priority_level IN ('High', 'Critical') AND status IN ('Pending', 'Approved')"
            );

            foreach ($rows as $row) {
                $employeeName = $this->resolveEmployeeName($row['employee_id']);
                $result[] = [
                    'employee' => $employeeName,
                    'reason' => 'Recommended training need',
                    'rating' => null,
                    'status' => $row['priority_level'],
                ];
            }
        }

        $unique = [];
        foreach ($result as $item) {
            $key = $item['employee'] . '|' . $item['reason'];
            if (!isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }

        return array_values($unique);
    }

    public function getRecentActivities(): array
    {
        $activities = [];

        if ($this->tableExists('pm_goal_history')) {
            $activities = array_merge($activities, $this->fetchAll(
                "SELECT action AS activity,
                        COALESCE(created_by, 'System') AS employee_user,
                        created_at AS activity_date,
                        'Updated' AS status
                 FROM pm_goal_history
                 ORDER BY created_at DESC
                 LIMIT 8"
            ));
        }

        if ($this->tableExists('pm_appraisal_history')) {
            $activities = array_merge($activities, $this->fetchAll(
                "SELECT action AS activity,
                        COALESCE(created_by, 'System') AS employee_user,
                        created_at AS activity_date,
                        'Updated' AS status
                 FROM pm_appraisal_history
                 ORDER BY created_at DESC
                 LIMIT 8"
            ));
        }

        if ($this->tableExists('kpi_history')) {
            $activities = array_merge($activities, $this->fetchAll(
                "SELECT action_type AS activity,
                        COALESCE(performed_by_name, performed_by, 'System') AS employee_user,
                        performed_at AS activity_date,
                        'Updated' AS status
                 FROM kpi_history
                 ORDER BY performed_at DESC
                 LIMIT 8"
            ));
        }

        if ($this->tableExists('pm_training_recommendations')) {
            $activities = array_merge($activities, $this->fetchAll(
                "SELECT 'Training recommended' AS activity,
                        COALESCE(recommended_by, 'HR') AS employee_user,
                        recommendation_date AS activity_date,
                        priority_level AS status
                 FROM pm_training_recommendations
                 ORDER BY recommendation_date DESC
                 LIMIT 8"
            ));
        }

        usort($activities, fn($a, $b) => strtotime($b['activity_date']) <=> strtotime($a['activity_date']));

        return array_slice($activities, 0, 8);
    }

    public function getFeedbackSummary(): array
    {
        $summary = [
            'pending' => 0,
            'completed' => 0,
            'active' => 0,
        ];

        if ($this->tableExists('pm_feedback_360_entries')) {
            $summary['pending'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_feedback_360_entries WHERE feedback_status = 'Pending'");
            $summary['completed'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_feedback_360_entries WHERE feedback_status = 'Completed'");
            $summary['active'] = (int) $this->fetchScalar("SELECT COUNT(*) FROM pm_feedback_360_entries WHERE feedback_status NOT IN ('Completed', 'Cancelled', 'Rejected')");
        }

        return $summary;
    }

    private function resolveEmployeeName($employeeId): string
    {
        $employeeTable = $this->employeeTable();
        if (!$this->tableExists($employeeTable) || $employeeId === null || $employeeId === '') {
            return 'Unknown employee';
        }

        $row = $this->fetchAll(
            'SELECT CONCAT(first_name, " ", last_name) AS employee_name FROM ' . $employeeTable . ' WHERE employee_id = :employee_id LIMIT 1',
            [':employee_id' => ['value' => (int) $employeeId, 'type' => PDO::PARAM_INT]]
        );

        return $row[0]['employee_name'] ?? 'Unknown employee';
    }
}
