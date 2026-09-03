<?php
namespace App\Models;

class Recognition extends BaseModel
{
    private function hasPerformanceTables()
    {
        return $this->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id', 'overall_rating', 'period_end'])
            || $this->tableHasColumns('pm_reports', ['report_id', 'employee_id', 'final_rating_percent', 'final_grade', 'period_end'])
            || $this->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id', 'overall_rating']);
    }

    protected function getSenderNameSql($senderIdExpr, $alias = 'sender_name')
    {
        return "COALESCE(CONCAT_WS(' ', he.first_name, he.middle_name, he.last_name), $senderIdExpr) AS $alias";
    }
    public function getRecognitions()
    {
        // Get both manual recognitions and performance report recognitions
        $sql = "
        SELECT 
            er.eer_recognition_id as id,
            er.sender_id,
            er.receiver_id,
            COALESCE(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name), CONCAT('Employee #', er.receiver_id)) as receiver_name,
            COALESCE(CONCAT_WS(' ', us.first_name, us.middle_name, us.last_name), 'engage') as sender_name,
            er.message,
            er.points,
            er.created_at,
            'manual' as source,
            er.category
        FROM eer_recognitions er
        LEFT JOIN em_employees us ON er.sender_id = us.employee_id
        LEFT JOIN em_employees e ON er.receiver_id = e.employee_id
        WHERE er.source IS NULL OR er.source != 'performance'
        ORDER BY er.created_at DESC";

        return $this->execute($sql)->fetchAll();
    }

    public function getRecognitionRecommendations($limit = 10)
    {
        if (!$this->hasPerformanceTables()) {
            return [];
        }

        $parts = [];

        if ($this->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id', 'overall_rating', 'period_end'])) {
            $parts[] = "SELECT
                        pr.report_id,
                        pr.employee_id,
                        pr.review_period as evaluation_period,
                        pr.period_start,
                        pr.period_end,
                        pr.kpi_health_score as kpi_score,
                        NULL as attendance_score,
                        pr.overall_rating * 20 as overall_rating_percent,
                        CASE
                            WHEN pr.overall_rating >= 4.5 THEN 5
                            WHEN pr.overall_rating >= 4.0 THEN 4
                            WHEN pr.overall_rating >= 3.5 THEN 3
                            ELSE 2
                        END as overall_rating_5,
                        pr.overall_rating * 20 as final_rating_percent,
                        CASE
                            WHEN pr.overall_rating >= 4.5 THEN 'Excellent'
                            WHEN pr.overall_rating >= 4.0 THEN 'Outstanding'
                            WHEN pr.overall_rating >= 3.5 THEN 'Very Satisfactory'
                            WHEN pr.overall_rating >= 3.0 THEN 'Satisfactory'
                            ELSE 'Fair'
                        END as final_grade,
                        pr.summary as remarks
                    FROM pm_performance_reports pr";
        }

        if ($this->tableHasColumns('pm_reports', ['report_id', 'employee_id', 'final_rating_percent', 'final_grade', 'period_end'])) {
            $parts[] = "SELECT 
                        pr.report_id,
                        pr.employee_id,
                        pr.evaluation_period,
                        pr.period_start,
                        pr.period_end,
                        pr.kpi_score,
                        pr.attendance_score,
                        pr.overall_rating_percent,
                        pr.overall_rating_5,
                        pr.final_rating_percent,
                        pr.final_grade,
                        pr.remarks
                    FROM pm_reports pr";
        }

        if ($this->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id', 'overall_rating'])) {
            $parts[] = "SELECT
                        pa.appraisal_id as report_id,
                        pa.employee_id,
                        NULL as evaluation_period,
                        pa.due_date as period_start,
                        pa.due_date as period_end,
                        NULL as kpi_score,
                        NULL as attendance_score,
                        pa.overall_rating as overall_rating_percent,
                        CASE
                            WHEN pa.overall_rating >= 4.5 THEN 5
                            WHEN pa.overall_rating >= 4.0 THEN 4
                            WHEN pa.overall_rating >= 3.5 THEN 3
                            ELSE 2
                        END as overall_rating_5,
                        pa.overall_rating as final_rating_percent,
                        CASE
                            WHEN pa.overall_rating >= 4.5 THEN 'Excellent'
                            WHEN pa.overall_rating >= 4.0 THEN 'Outstanding'
                            WHEN pa.overall_rating >= 3.5 THEN 'Very Satisfactory'
                            WHEN pa.overall_rating >= 3.0 THEN 'Satisfactory'
                            ELSE 'Fair'
                        END as final_grade,
                        CONCAT('Appraisal review for ', COALESCE(pa.due_date, pa.created_at)) as remarks
                    FROM pm_appraisals pa";
        }

        if (empty($parts)) {
            return [];
        }

        $unionSql = implode(' UNION ALL ', $parts);

        $sql = "SELECT 
                    pr.report_id,
                    pr.employee_id,
                    COALESCE(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name), CONCAT('Employee #', pr.employee_id)) as employee_name,
                    pr.evaluation_period,
                    pr.period_start,
                    pr.period_end,
                    pr.kpi_score,
                    pr.attendance_score,
                    pr.overall_rating_percent,
                    pr.overall_rating_5,
                    pr.final_rating_percent,
                    pr.final_grade,
                    pr.remarks,
                    CONCAT('Performance Review: ', pr.final_grade, ' (', pr.final_rating_percent, '%)') as message
                FROM ($unionSql) pr
                LEFT JOIN em_employees e ON pr.employee_id = e.employee_id
                WHERE pr.period_end >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    AND pr.final_rating_percent >= 80
                    AND pr.final_grade IN ('Outstanding', 'Very Satisfactory', 'Excellent')
                ORDER BY pr.final_rating_percent DESC, pr.period_end DESC
                LIMIT " . (int)$limit;

        return $this->execute($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function sendRecognition($sender_id, $receiver_id, $message, $points)
    {
        // Insert recognition into eer_recognitions table
        $sql = "INSERT INTO eer_recognitions (sender_id, receiver_id, message, points, category, created_at) 
                VALUES (:sender_id, :receiver_id, :message, :points, 'general', NOW())";
        
        $this->execute($sql, [
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message,
            'points' => $points
        ]);
        
        $recognitionId = (int)$this->db->lastInsertId();

        $notification = new Notification();
        $notification->notifyEmployees([(int)$receiver_id], 'You received a recognition: ' . $message, 'recognition');
        $notification->notifyHr('A recognition was sent to employee #' . (int)$receiver_id . '.', 'recognition', [(int)$sender_id, (int)$receiver_id]);

        return $recognitionId;
    }

    public function autoNominateForEmployeeOfTheMonth($employeeId, $reason = null)
    {
        $monthYear = date('Y-m');
        $existing = $this->execute(
            'SELECT * FROM eer_award_history WHERE employee_id = :employee_id AND month_year = :month_year AND award_type = :award_type',
            ['employee_id' => $employeeId, 'month_year' => $monthYear, 'award_type' => 'employee_of_month']
        )->fetch();

        if (!$existing) {
            $this->execute(
                'INSERT INTO eer_award_history (employee_id, award_name, reason, nominated_by, award_type, month_year, status, created_at) 
                VALUES (:employee_id, :award_name, :reason, :nominated_by, :award_type, :month_year, :status, NOW())',
                [
                    'employee_id' => $employeeId,
                    'award_name' => 'Employee of the Month Nomination',
                    'reason' => $reason ?: 'Auto-nominated after recognition entry',
                    'nominated_by' => null,
                    'award_type' => 'employee_of_month',
                    'month_year' => $monthYear,
                    'status' => 'nominated'
                ]
            );
        }
    }

    public function addVotePoints($voter_id, $receiver_id)
    {
        // Add vote recognition (5 points for voting)
        $sql = "INSERT INTO eer_recognitions (sender_id, receiver_id, message, points, category, created_at) 
                VALUES (:sender_id, :receiver_id, :message, 5, 'vote', NOW())";
        
        $result = $this->execute($sql, [
            'sender_id' => $voter_id,
            'receiver_id' => $receiver_id,
            'message' => 'Vote recognition'
        ]);
        
        // Get the inserted ID
        return $this->db->lastInsertId();
    }

    public function getHistoryByEmployee($employeeId)
    {
                if (!$this->hasTable('pm_performance_reports') && !$this->hasTable('pm_appraisals')) {
                    return [];
                }

                // Return performance report history for the employee
                $sql = "SELECT pr.*, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name,
                                             CONCAT('Performance Review: ',
                                                CASE WHEN pr.overall_rating >= 4.5 THEN 'Excellent'
                                                     WHEN pr.overall_rating >= 4.0 THEN 'Outstanding'
                                                     WHEN pr.overall_rating >= 3.5 THEN 'Very Satisfactory'
                                                     WHEN pr.overall_rating >= 3.0 THEN 'Satisfactory'
                                                     ELSE 'Fair' END,
                                                ' (', pr.overall_rating * 20, '%)') as message,
                                             CASE
                                                 WHEN pr.overall_rating >= 4.5 THEN 100
                                                 WHEN pr.overall_rating >= 4.0 THEN 50
                                                 WHEN pr.overall_rating >= 3.5 THEN 25
                                                 ELSE 0
                                             END as points
                                FROM pm_performance_reports pr
                                JOIN em_employees e ON pr.employee_id = e.employee_id
                                WHERE pr.employee_id = :employeeId
                                ORDER BY pr.period_end DESC";
                if (!$this->hasTable('pm_performance_reports')) {
                    return [];
                }
                return $this->execute($sql, ['employeeId' => $employeeId])->fetchAll();
    }

    public function updateRewardsCatalog($action, $data)
    {
        if ($action === 'add') {
            $sql = "INSERT INTO eer_rewards (name, description, points_required) VALUES (:name, :description, :points_required)";
            return $this->execute($sql, $data);
        } elseif ($action === 'delete') {
            $sql = "DELETE FROM eer_rewards WHERE eer_reward_id = :id";
            return $this->execute($sql, ['id' => $data['id']]);
        }
    }

    public function assignBadge($employeeId, $badgeId, $awardedBy = null, $performanceScore = null)
    {
        $sql = "INSERT INTO eer_employee_badges (employee_id, badge_id, awarded_by, performance_score, awarded_at)
                VALUES (:employeeId, :badgeId, :awardedBy, :performanceScore, NOW())";
        return $this->execute($sql, [
            'employeeId' => $employeeId,
            'badgeId' => $badgeId,
            'awardedBy' => $awardedBy,
            'performanceScore' => $performanceScore !== null ? (float)$performanceScore : 0.00
        ]);
    }

    public function getTopRecognizedEmployees($limit = 10)
    {
        // Show only employees who actually received recognition entries.
        $sql = "
        SELECT 
            e.employee_id as receiver_id,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as receiver_name,
            COUNT(er.eer_recognition_id) as recognition_count,
            SUM(er.points) as total_points
        FROM eer_recognitions er
        JOIN em_employees e ON er.receiver_id = e.employee_id
        WHERE er.points IS NOT NULL
          AND (er.source IS NULL OR er.source IN ('manual', 'achievement'))
        GROUP BY e.employee_id, e.first_name, e.middle_name, e.last_name
        ORDER BY total_points DESC, recognition_count DESC
        LIMIT " . (int)$limit;
        
        return $this->execute($sql)->fetchAll();
    }

    public function getRecentlyRecognizedEmployees($days = 30)
    {
        $sql = "SELECT DISTINCT e.employee_id, CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name, COUNT(er.eer_recognition_id) as recognition_count
            FROM em_employees e
            JOIN eer_recognitions er ON e.employee_id = er.receiver_id
            WHERE (er.source IS NULL OR er.source != 'performance')
              AND er.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY e.employee_id
            ORDER BY recognition_count DESC";
        return $this->execute($sql, ['days' => $days])->fetchAll();
    }

    public function getEmployeeFromAwardHistory($awardHistoryId)
    {
        $sql = "SELECT employee_id FROM eer_award_history WHERE eer_award_history_id = :id";
        $result = $this->execute($sql, ['id' => $awardHistoryId])->fetch();
        return $result ? $result['employee_id'] : null;
    }

    public function hasVotedForAwardHistory($awardHistoryId, $voterUserId)
    {
        $sql = "SELECT 1 FROM eer_award_votes WHERE award_history_id = :award_history_id AND voter_user_id = :voter_user_id LIMIT 1";
        $result = $this->execute($sql, [
            'award_history_id' => $awardHistoryId,
            'voter_user_id' => $voterUserId
        ])->fetchColumn();
        return (bool)$result;
    }

        public function hasVotedForEmployeeMonth($voterUserId, $awardHistoryId)
    {
        $sql = "SELECT 1
                FROM eer_award_votes ev
                INNER JOIN eer_award_history ah ON ah.eer_award_history_id = ev.award_history_id
                                INNER JOIN eer_award_history target_ah ON target_ah.month_year = ah.month_year
                WHERE ev.voter_user_id = :voter_user_id
                                    AND target_ah.eer_award_history_id = :award_history_id
                                    AND target_ah.award_type = 'employee_of_month'
                  AND ah.award_type = 'employee_of_month'
                LIMIT 1";
        return (bool)$this->execute($sql, [
            'voter_user_id' => $voterUserId,
                        'award_history_id' => $awardHistoryId
        ])->fetchColumn();
    }

    public function recordEmployeeMonthVote($awardHistoryId, $voterUserId, $nomineeEmployeeId)
    {
        $sql = "INSERT INTO eer_award_votes (award_history_id, voter_user_id, nominee_employee_id, created_at)
                VALUES (:award_history_id, :voter_user_id, :nominee_employee_id, NOW())";
        $this->execute($sql, [
            'award_history_id' => $awardHistoryId,
            'voter_user_id' => $voterUserId,
            'nominee_employee_id' => $nomineeEmployeeId
        ]);

        $this->execute(
            "UPDATE eer_award_history SET vote_count = vote_count + 1 WHERE eer_award_history_id = :id",
            ['id' => $awardHistoryId]
        );
    }

    public function deleteEmployeeMonthNomination($awardHistoryId)
    {
        $this->execute('DELETE FROM eer_award_votes WHERE award_history_id = :award_history_id', [
            'award_history_id' => $awardHistoryId
        ]);
        $deleted = $this->execute('DELETE FROM eer_award_history WHERE eer_award_history_id = :id AND award_type = :award_type', [
            'id' => $awardHistoryId,
            'award_type' => 'employee_of_the_month'
        ]);
        return $deleted->rowCount() > 0;
    }

    /**
     * Get comprehensive leaderboard with all recognition sources
     */
    public function getComprehensiveLeaderboard($limit = 20)
    {
        $sql = "
        SELECT
            e.employee_id,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name,
            d.department_name as department,
            p.position_name as position,
            COALESCE(SUM(CASE WHEN er.source IS NULL OR er.source IN ('manual', 'achievement') THEN er.points ELSE 0 END), 0) as recognition_points,
            COALESCE(SUM(CASE WHEN er.source = 'performance' THEN er.points ELSE 0 END), 0) as performance_points,
            COALESCE((
                SELECT SUM(b.points_value)
                FROM eer_employee_badges eb
                JOIN eer_badges b ON b.eer_badge_id = eb.badge_id
                WHERE eb.employee_id = e.employee_id
            ), 0) as badge_points,
            COALESCE((
                SELECT SUM(CASE WHEN ah.points > 0 THEN ah.points ELSE COALESCE(ah.vote_count * 5, 0) END)
                FROM eer_award_history ah
                WHERE ah.employee_id = e.employee_id
            ), 0) as award_points,
            COUNT(er.eer_recognition_id) as recognition_count,
            ROW_NUMBER() OVER (ORDER BY (
                COALESCE(SUM(CASE WHEN er.source IS NULL OR er.source IN ('manual', 'achievement') THEN er.points ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN er.source = 'performance' THEN er.points ELSE 0 END), 0)
                + COALESCE((SELECT SUM(b.points_value) FROM eer_employee_badges eb JOIN eer_badges b ON b.eer_badge_id = eb.badge_id WHERE eb.employee_id = e.employee_id), 0)
                + COALESCE((SELECT SUM(CASE WHEN ah.points > 0 THEN ah.points ELSE COALESCE(ah.vote_count * 5, 0) END) FROM eer_award_history ah WHERE ah.employee_id = e.employee_id), 0)
            ) DESC) as rank_position,
            (
                COALESCE(SUM(CASE WHEN er.source IS NULL OR er.source IN ('manual', 'achievement') THEN er.points ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN er.source = 'performance' THEN er.points ELSE 0 END), 0)
                + COALESCE((SELECT SUM(b.points_value) FROM eer_employee_badges eb JOIN eer_badges b ON b.eer_badge_id = eb.badge_id WHERE eb.employee_id = e.employee_id), 0)
                + COALESCE((SELECT SUM(CASE WHEN ah.points > 0 THEN ah.points ELSE COALESCE(ah.vote_count * 5, 0) END) FROM eer_award_history ah WHERE ah.employee_id = e.employee_id), 0)
            ) as total_points
        FROM eer_recognitions er
        JOIN em_employees e ON e.employee_id = er.receiver_id
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        WHERE er.points IS NOT NULL
          AND (er.source IS NULL OR er.source IN ('manual', 'achievement'))
        GROUP BY e.employee_id, e.first_name, e.middle_name, e.last_name, d.department_name, p.position_name
        ORDER BY total_points DESC, recognition_count DESC
        LIMIT " . (int)$limit;

        return $this->execute($sql)->fetchAll();
    }

    /**
     * Sync performance reports into eer_recognitions table.
     * Inserts a recognition row for each performance report that has points
     * and is not already recorded in eer_recognitions (matched by performance_report_id).
     * Returns the number of rows inserted.
     */
    public function syncPerformanceRecognitions()
    {
        $sql = "INSERT INTO eer_recognitions (sender_id, receiver_id, message, points, category, source, performance_report_id, created_at)
                SELECT
                    pr.created_by as sender_id,
                    COALESCE(u.id, NULL) as receiver_id,
                    CONCAT('Performance Review: ',
                        CASE WHEN pr.overall_rating >= 4.5 THEN 'Excellent'
                             WHEN pr.overall_rating >= 4.0 THEN 'Outstanding'
                             WHEN pr.overall_rating >= 3.5 THEN 'Very Satisfactory'
                             WHEN pr.overall_rating >= 3.0 THEN 'Satisfactory'
                             ELSE 'Fair' END,
                        ' (', pr.overall_rating * 20, '%)') as message,
                    CASE
                        WHEN pr.overall_rating >= 4.5 THEN 100
                        WHEN pr.overall_rating >= 4.0 THEN 50
                        WHEN pr.overall_rating >= 3.5 THEN 25
                        ELSE 0
                    END as points,
                    'performance' as category,
                    'performance' as source,
                    pr.report_id as performance_report_id,
                    pr.created_at as created_at
                FROM pm_performance_reports pr
                LEFT JOIN em_employees u ON u.employee_id = pr.employee_id
                WHERE (CASE
                    WHEN pr.overall_rating >= 4.5 THEN 100
                    WHEN pr.overall_rating >= 4.0 THEN 50
                    WHEN pr.overall_rating >= 3.5 THEN 25
                        ELSE 0
                    END) > 0
                AND NOT EXISTS (
                    SELECT 1 FROM eer_recognitions er WHERE er.performance_report_id = pr.report_id
                )";

        $stmt = $this->execute($sql);
        $rowsInserted = $stmt->rowCount();

        if ($rowsInserted > 0) {
            $this->execute(
                "UPDATE eer_recognitions SET sender_id = eer_recognition_id WHERE source = 'performance' AND sender_id != eer_recognition_id"
            );
        }

        return $rowsInserted;
    }

    /**
     * Get employee of the month candidates based on performance and recognitions
     */
    public function getEmployeeOfTheMonthCandidates($month = null, $year = null, $currentUserId = null)
    {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        $monthYear = sprintf('%04d-%02d', $year, $month);
        
        $userVoteJoin = '';
        $userVoteSelect = '0 as has_voted';
        $params = [
            'month' => (int)$month,
            'year' => (int)$year,
            'month_year' => $monthYear,
            'award_type' => 'employee_of_month'
        ];

        if ($currentUserId) {
            $userVoteJoin = "
            LEFT JOIN eer_award_votes ev ON ev.voter_user_id = :current_user_id
                AND EXISTS (
                    SELECT 1 FROM eer_award_history voted_ah
                    WHERE voted_ah.eer_award_history_id = ev.award_history_id
                      AND voted_ah.month_year = ah.month_year
                      AND voted_ah.award_type = 'employee_of_month'
                )";
            $userVoteSelect = "CASE WHEN ev.eer_award_vote_id IS NOT NULL THEN 1 ELSE 0 END as has_voted";
            $params['current_user_id'] = $currentUserId;
        }

        $sql = "
        SELECT 
            e.employee_id,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name,
            d.department_name as department,
            p.position_name as position,
            ah.month_year,
            ah.status,
            COALESCE(ah.vote_count, 0) as votes,
            $userVoteSelect,
            COALESCE(
                ah.performance_score,
                (
                    SELECT pr2.overall_rating * 20
                    FROM pm_performance_reports pr2
                    WHERE pr2.employee_id = e.employee_id
                    ORDER BY pr2.period_end DESC
                    LIMIT 1
                ),
                (
                    SELECT pa2.overall_rating * 20
                    FROM pm_appraisals pa2
                    WHERE pa2.employee_id = e.employee_id
                    ORDER BY COALESCE(pa2.due_date, pa2.created_at) DESC
                    LIMIT 1
                ),
                0
            ) as performance_score,
            COALESCE(SUM(CASE WHEN er.receiver_id = e.employee_id THEN er.points ELSE 0 END), 0) as recognition_total,
            COALESCE(COUNT(DISTINCT er.eer_recognition_id), 0) as recognition_count,
            COALESCE(ah.points, 0) as award_points,
            COALESCE(ah.reason, '') as nomination_reason,
            ah.eer_award_history_id
        FROM eer_award_history ah
        INNER JOIN em_employees e ON e.employee_id = ah.employee_id
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        LEFT JOIN eer_recognitions er ON e.employee_id = er.receiver_id 
            AND (er.source IS NULL OR er.source != 'performance')
            AND MONTH(er.created_at) = :month
            AND YEAR(er.created_at) = :year
        $userVoteJoin
        WHERE ah.month_year = :month_year
            AND ah.award_type = :award_type
        GROUP BY e.employee_id, d.department_name, p.position_name, ah.eer_award_history_id
        ORDER BY votes DESC, recognition_total DESC, performance_score DESC";
        
        return $this->execute($sql, $params)->fetchAll();
    }

    /**
     * Get total points for an employee from all sources
     */
    public function getEmployeeTotalPoints($employeeId)
    {
        $sql = "
        SELECT
            " . (int)$employeeId . " as employee_id,
            COALESCE(SUM(CASE WHEN source = 'recognition' THEN points ELSE 0 END), 0) as recognition_points,
            COALESCE(SUM(CASE WHEN source = 'performance' THEN points ELSE 0 END), 0) as performance_points,
            COALESCE(SUM(CASE WHEN source = 'badge' THEN points ELSE 0 END), 0) as badge_points,
            COALESCE(SUM(CASE WHEN source = 'award' THEN points ELSE 0 END), 0) as award_points,
            COALESCE(SUM(points), 0) as total_points
        FROM (
            SELECT 'recognition' as source, er.points
            FROM eer_recognitions er
            WHERE er.receiver_id = :employeeId
            
            UNION ALL
            
            SELECT 'performance' as source,
                CASE
                    WHEN pr.overall_rating_5 = 5 AND pr.final_rating_percent >= 95 THEN 100
                    WHEN pr.overall_rating_5 >= 4 AND pr.final_rating_percent >= 80 THEN 50
                    WHEN pr.overall_rating_5 >= 3 AND pr.final_rating_percent >= 70 THEN 25
                    ELSE 0
                END as points
            FROM pm_performance_reports pr
            WHERE pr.employee_id = :employeeId
            
            UNION ALL
            
            SELECT 'performance' as source,
                CASE
                    WHEN pa.overall_rating >= 4.5 THEN 100
                    WHEN pa.overall_rating >= 4.0 THEN 50
                    WHEN pa.overall_rating >= 3.5 THEN 25
                    ELSE 0
                END as points
            FROM pm_appraisals pa
            WHERE pa.employee_id = :employeeId
            
            UNION ALL
            
            SELECT 'badge' as source, b.points_value
            FROM eer_employee_badges eb
            JOIN eer_badges b ON eb.badge_id = b.eer_badge_id
            WHERE eb.employee_id = :employeeId
            
            UNION ALL
            
            SELECT 'award' as source,
                CASE
                    WHEN ah.points > 0 THEN ah.points
                    ELSE COALESCE(ah.vote_count * 5, 0)
                END as points
            FROM eer_award_history ah
            WHERE ah.employee_id = :employeeId
        ) all_points";
        
        return $this->execute($sql, ['employeeId' => $employeeId])->fetch();
    }

    /**
     * Get badge recommendations based on employee performance and achievements
     */
    public function getBadgeRecommendations($employeeId)
    {
        $sql = "
        SELECT DISTINCT
            b.eer_badge_id,
            b.name,
            b.description,
            b.icon,
            b.tier,
            b.points_value,
            b.category,
            b.requirement_type,
            b.requirement_value,
            CASE 
                WHEN eb.eer_employee_badge_id IS NOT NULL THEN 'owned'
                WHEN (
                    SELECT COUNT(*) FROM eer_recognitions er 
                    WHERE er.receiver_id = :employeeId
                ) >= COALESCE(b.requirement_value, 5) THEN 'eligible'
                ELSE 'not_eligible'
            END as status,
            eb.awarded_at as owned_since
        FROM eer_badges b
        LEFT JOIN eer_employee_badges eb ON b.eer_badge_id = eb.badge_id 
            AND eb.employee_id = :employeeId
        WHERE b.status = 'active'
        ORDER BY b.tier, b.points_value DESC";
        
        return $this->execute($sql, ['employeeId' => $employeeId])->fetchAll();
    }

    /**
     * Get department-based leaderboard
     */
    public function getDepartmentLeaderboard($department = null, $limit = 10)
    {
        $whereClauses = [
            'er.points IS NOT NULL',
            "(er.source IS NULL OR er.source IN ('manual', 'achievement'))"
        ];
        $params = [];

        if ($department) {
            $whereClauses[] = 'd.department_name = :department';
            $params['department'] = $department;
        }

        $sql = "
        SELECT 
            e.employee_id,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) as employee_name,
            d.department_name as department,
            p.position_name as position,
            SUM(er.points) as total_points,
            RANK() OVER (PARTITION BY d.department_name ORDER BY SUM(er.points) DESC) as dept_rank
        FROM eer_recognitions er
        JOIN em_employees e ON e.employee_id = er.receiver_id
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        WHERE " . implode(' AND ', $whereClauses) . "
        GROUP BY e.employee_id, e.first_name, e.middle_name, e.last_name, d.department_name, p.position_name
        ORDER BY total_points DESC
        LIMIT " . (int)$limit;

        return $this->execute($sql, $params)->fetchAll();
    }
}

