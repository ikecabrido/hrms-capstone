<?php
namespace App\Controllers;

require_once __DIR__ . '/../../../database/db.php';

use App\Models\Recognition;
use App\Models\RecognitionCycle;
use Database;

class RecognitionController
{
    private $recognition;
    private $recognitionCycle;

    public function __construct()
    {
        $this->recognition = new Recognition();
        $this->recognitionCycle = new RecognitionCycle();
    }

    public function getMonthlyRecognitionCycle($monthYear, $includePrivate = false)
    {
        return $this->recognitionCycle->preview($monthYear, $includePrivate);
    }

    public function approveMonthlyRecognitionCycle(
        $monthYear,
        array $selectedEmployeeIds,
        $employeeOfMonthId,
        $approvedBy
    )
    {
        return $this->recognitionCycle->approve(
            $monthYear,
            $selectedEmployeeIds,
            $employeeOfMonthId,
            $approvedBy
        );
    }

    public function getRecognitions()
    {
        return $this->recognition->getRecognitions();
    }

    public function sendRecognition($sender_id, $receiver_id, $message, $points)
    {
        return $this->recognition->sendRecognition($sender_id, $receiver_id, $message, $points);
    }

    public function addVotePoints($voter_id, $receiver_id)
    {
        return $this->recognition->addVotePoints($voter_id, $receiver_id);
    }

    public function getLeaderboard()
    {
        return $this->recognition->getTopRecognizedEmployees();
    }

    public function getRecentlyRecognizedEmployees($days = 30)
    {
        return $this->recognition->getRecentlyRecognizedEmployees($days);
    }

    public function getEmployeeFromAwardHistory($awardHistoryId)
    {
        return $this->recognition->getEmployeeFromAwardHistory($awardHistoryId);
    }

    public function getRecognitionHistory($employeeId)
    {
        return $this->recognition->getHistoryByEmployee($employeeId);
    }

    public function manageRewardsCatalog($action, $data)
    {
        return $this->recognition->updateRewardsCatalog($action, $data);
    }

    public function assignAchievementBadge($employeeId, $badgeId, $awardedBy = null, $performanceScore = null)
    {
        return $this->recognition->assignBadge($employeeId, $badgeId, $awardedBy, $performanceScore);
    }

    public function adjustEmployeePoints($employeeId, $points, $reason, $adminEmployeeId)
    {
        return $this->recognition->adjustEmployeePoints($employeeId, $points, $reason, $adminEmployeeId);
    }

    public function getEmployeePerformanceScore($employeeId)
    {
        $database = class_exists(Database::class) && method_exists(Database::class, 'getInstance')
            ? Database::getInstance()
            : new Database();
        $db = $database->getConnection();

        if ($this->recognition->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id', 'overall_rating', 'period_end'])) {
            $sql = "SELECT pr.overall_rating * 20 as performance_score
                FROM pm_performance_reports pr
                    WHERE pr.employee_id = :employee_id
                    ORDER BY pr.period_end DESC, pr.report_id DESC
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(['employee_id' => $employeeId]);
            $row = $stmt->fetch();
            if ($row) {
                return (float)$row['performance_score'];
            }
        }

        if ($this->recognition->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id', 'overall_rating'])) {
            $sql = "SELECT pa.overall_rating as performance_score
                    FROM pm_appraisals pa
                    WHERE pa.employee_id = :employee_id
                    ORDER BY COALESCE(pa.due_date, pa.created_at) DESC, pa.appraisal_id DESC
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(['employee_id' => $employeeId]);
            $row = $stmt->fetch();
            if ($row) {
                return (float)$row['performance_score'];
            }
        }

        return 0.0;
    }

    /**
     * Get recognition recommendations based on performance scores
     * Suggest employees who deserve recognition based on their high performance ratings
     */
    public function getRecognitionRecommendations($limit = 10)
    {
        return $this->recognition->getRecognitionRecommendations(max(1, (int)$limit));
    }

    /**
     * Get performance ratings from the latest finalized review period.
     */
    public function getPerformanceLeaderboard($limit = 20)
    {
        return $this->recognitionCycle->leaderboard($limit);
    }

    /**
     * Get employees who do not yet have a performance report
     */
    public function getEmployeesWithoutPerformanceReports()
    {
        $database = class_exists(Database::class) && method_exists(Database::class, 'getInstance')
            ? Database::getInstance()
            : new Database();
        $db = $database->getConnection();

        if (!$this->recognition->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id'])
            && !$this->recognition->tableHasColumns('pm_reports', ['report_id', 'employee_id'])
            && !$this->recognition->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id'])) {
            return [];
        }

        $reportSources = [
            ['pm_performance_reports', ['report_id', 'employee_id']],
            ['pm_reports', ['report_id', 'employee_id']],
            ['pm_appraisals', ['appraisal_id', 'employee_id']],
        ];
        $hasPerformanceData = false;
        foreach ($reportSources as [$table, $columns]) {
            if ($this->recognition->tableHasColumns($table, $columns)) {
                $stmt = $db->query("SELECT 1 FROM {$table} LIMIT 1");
                if ($stmt->fetchColumn()) {
                    $hasPerformanceData = true;
                    break;
                }
            }
        }
        if (!$hasPerformanceData) {
            return [];
        }

        $joins = [];
        if ($this->recognition->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id'])) {
            $joins[] = 'LEFT JOIN pm_performance_reports ppr ON e.employee_id = ppr.employee_id';
        }
        if ($this->recognition->tableHasColumns('pm_reports', ['report_id', 'employee_id'])) {
            $joins[] = 'LEFT JOIN pm_reports pr ON e.employee_id = pr.employee_id';
        }
        if ($this->recognition->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id'])) {
            $joins[] = 'LEFT JOIN pm_appraisals pa ON e.employee_id = pa.employee_id';
        }

        $whereClauses = [];
        if ($this->recognition->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id'])) {
            $whereClauses[] = 'ppr.employee_id IS NULL';
        }
        if ($this->recognition->tableHasColumns('pm_reports', ['report_id', 'employee_id'])) {
            $whereClauses[] = 'pr.employee_id IS NULL';
        }
        if ($this->recognition->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id'])) {
            $whereClauses[] = 'pa.employee_id IS NULL';
        }

        $sql = "SELECT e.employee_id, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name, d.department_name as department
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            " . implode("\n", $joins) . "
            WHERE " . implode(' AND ', $whereClauses) . "
                ORDER BY CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name)";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get suggested awards based on performance
     */
    public function getSuggestedAwardsForPerformer($employeeId)
    {
        $database = class_exists(Database::class) && method_exists(Database::class, 'getInstance')
            ? Database::getInstance()
            : new Database();
        $db = $database->getConnection();
        
           $sql = "SELECT
                       CASE WHEN pr.overall_rating >= 4.5 THEN 'Excellent'
                           WHEN pr.overall_rating >= 4.0 THEN 'Outstanding'
                           WHEN pr.overall_rating >= 3.5 THEN 'Very Satisfactory'
                           WHEN pr.overall_rating >= 3.0 THEN 'Satisfactory'
                           ELSE 'Fair' END as final_grade,
                       CASE WHEN pr.overall_rating >= 4.5 THEN 5
                           WHEN pr.overall_rating >= 4.0 THEN 4
                           WHEN pr.overall_rating >= 3.5 THEN 3
                           ELSE 2 END as overall_rating_5,
                       pr.kpi_health_score * 20 as kpi_score,
                       NULL as attendance_score, d.department_name as department
                 FROM pm_performance_reports pr
                INNER JOIN em_employees e ON pr.employee_id = e.employee_id
                LEFT JOIN em_departments d ON e.department_id = d.department_id
                WHERE pr.employee_id = :employee_id
                ORDER BY pr.period_end DESC
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            return [];
        }

        $suggestions = [];
        if ($report['final_grade'] === 'Outstanding') {
            $suggestions[] = [
                'award_name' => 'Top Performer Award',
                'reason' => 'Outstanding performance rating',
                'points_value' => 100
            ];
        }
        
        if ($report['overall_rating_5'] == 5 && $report['kpi_score'] >= 95) {
            $suggestions[] = [
                'award_name' => 'Excellent Achievement Badge',
                'reason' => 'Perfect rating with exceptional KPI scores',
                'points_value' => 75
            ];
        }
        
        if ($report['attendance_score'] >= 95) {
            $suggestions[] = [
                'award_name' => 'Perfect Attendance Award',
                'reason' => 'Exceptional attendance and punctuality',
                'points_value' => 50
            ];
        }

        return $suggestions;
    }

    /**
     * Get comprehensive leaderboard with all sources
     */
    public function getComprehensiveLeaderboard($limit = 20)
    {
        return $this->recognition->getComprehensiveLeaderboard($limit);
    }

    /**
     * Get employee of the month candidates
     */
    public function getEmployeeOfTheMonthCandidates($month = null, $year = null, $currentUserId = null)
    {
        return $this->recognition->getEmployeeOfTheMonthCandidates($month, $year, $currentUserId);
    }

    public function hasVotedForAwardHistory($awardHistoryId, $voterUserId)
    {
        return $this->recognition->hasVotedForAwardHistory($awardHistoryId, $voterUserId);
    }

    public function hasVotedForEmployeeMonth($voterUserId, $awardHistoryId)
    {
        return $this->recognition->hasVotedForEmployeeMonth($voterUserId, $awardHistoryId);
    }

    public function recordEmployeeMonthVote($awardHistoryId, $voterUserId, $nomineeEmployeeId)
    {
        return $this->recognition->recordEmployeeMonthVote($awardHistoryId, $voterUserId, $nomineeEmployeeId);
    }

    public function deleteEmployeeMonthNomination($awardHistoryId)
    {
        return $this->recognition->deleteEmployeeMonthNomination($awardHistoryId);
    }

    /**
     * Sync performance reports into recognitions table
     */
    public function syncPerformanceRecognitions()
    {
        return $this->recognition->syncPerformanceRecognitions();
    }

    /**
     * Get total points for an employee
     */
    public function getEmployeeTotalPoints($employeeId)
    {
        return $this->recognition->getEmployeeTotalPoints($employeeId);
    }

    /**
     * Get badge recommendations for an employee
     */
    public function getBadgeRecommendations($employeeId)
    {
        return $this->recognition->getBadgeRecommendations($employeeId);
    }

    /**
     * Get department-based leaderboard
     */
    public function getDepartmentLeaderboard($department = null, $limit = 10)
    {
        return $this->recognition->getDepartmentLeaderboard($department, $limit);
    }
}
