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
            er.source,
            er.category
        FROM eer_recognitions er
        LEFT JOIN em_employees us ON er.sender_id = us.employee_id
        LEFT JOIN em_employees e ON er.receiver_id = e.employee_id
        WHERE er.status = 'approved'
          AND (er.source IS NULL OR er.source != 'performance')
        ORDER BY er.created_at DESC";

        return $this->execute($sql)->fetchAll();
    }

    public function getRecognitionRecommendations($limit = 10)
    {
        if (!$this->hasPerformanceTables()) {
            return [];
        }

        $parts = [];

        if ($this->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id', 'review_period', 'period_start', 'period_end', 'overall_rating', 'kpi_health_score', 'summary', 'employee_name'])) {
            $parts[] = "SELECT
                        pr.report_id,
                        pr.employee_id,
                        pr.employee_name as source_employee_name,
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

        if ($this->tableHasColumns('pm_reports', ['report_id', 'employee_id', 'evaluation_period', 'period_start', 'final_rating_percent', 'final_grade', 'period_end'])) {
            $sourceEmployeeName = $this->hasColumn('pm_reports', 'employee_name') ? 'pr.employee_name' : 'NULL';
            $parts[] = "SELECT 
                        pr.report_id,
                        pr.employee_id,
                        $sourceEmployeeName as source_employee_name,
                        pr.evaluation_period,
                        pr.period_start,
                        pr.period_end,
                        NULL as kpi_score,
                        NULL as attendance_score,
                        pr.final_rating_percent as overall_rating_percent,
                        CASE
                            WHEN pr.final_rating_percent >= 95 THEN 5
                            WHEN pr.final_rating_percent >= 80 THEN 4
                            WHEN pr.final_rating_percent >= 70 THEN 3
                            ELSE 2
                        END as overall_rating_5,
                        pr.final_rating_percent,
                        pr.final_grade,
                        NULL as remarks
                    FROM pm_reports pr";
        }

        if ($this->tableHasColumns('pm_appraisals', ['appraisal_id', 'employee_id', 'overall_rating', 'due_date', 'created_at'])) {
            $sourceEmployeeName = $this->hasColumn('pm_appraisals', 'employee_name') ? 'pa.employee_name' : 'NULL';
            $parts[] = "SELECT
                        pa.appraisal_id as report_id,
                        pa.employee_id,
                        $sourceEmployeeName as source_employee_name,
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
                    COALESCE(e.employee_id, (
                        SELECT matched.employee_id
                        FROM em_employees matched
                        WHERE LOWER(TRIM(CONCAT_WS(' ', matched.first_name, matched.middle_name, matched.last_name))) = LOWER(TRIM(pr.source_employee_name))
                        ORDER BY matched.employee_id ASC
                        LIMIT 1
                    ), pr.employee_id) as employee_id,
                    COALESCE(NULLIF(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name), ''), NULLIF(pr.source_employee_name, ''), CONCAT('Employee #', pr.employee_id)) as employee_name,
                    pr.source_employee_name,
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
        $senderId = filter_var($sender_id, FILTER_VALIDATE_INT);
        $receiverId = filter_var($receiver_id, FILTER_VALIDATE_INT);
        $message = is_string($message) ? trim($message) : '';
        $points = filter_var($points, FILTER_VALIDATE_INT);

        if ($senderId === false || $senderId <= 0 || $receiverId === false || $receiverId <= 0
            || $message === '' || $points === false || $points <= 0) {
            throw new \InvalidArgumentException('A valid sender, recipient, message, and positive points value are required.');
        }

        $receiverExists = $this->execute(
            'SELECT employee_id FROM em_employees WHERE employee_id = :employee_id LIMIT 1',
            ['employee_id' => $receiverId]
        )->fetchColumn();
        if (!$receiverExists) {
            throw new \RuntimeException('The selected employee is no longer available. Refresh the page and select an employee from the current list.');
        }

        $existingRecognition = $this->execute(
            "SELECT eer_recognition_id
             FROM eer_recognitions
             WHERE receiver_id = :receiver_id
               AND category = 'general'
               AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
               AND created_at < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
             LIMIT 1",
            ['receiver_id' => $receiverId]
        )->fetchColumn();

        if ($existingRecognition) {
            throw new \RuntimeException('This employee has already been recognized this month.');
        }

        // Store the receiver's current department with the recognition.
        $columns = ['sender_id', 'receiver_id', 'message', 'points', 'category', 'created_at'];
        $values = [':sender_id', 'e.employee_id', ':message', ':points', "'general'", 'NOW()'];
        $joins = '';

        if ($this->tableHasColumns('eer_recognitions', ['performance_report_id'])
            && $this->tableHasColumns('pm_performance_reports', ['report_id', 'employee_id', 'period_end'])) {
            $columns[] = 'performance_report_id';
            $values[] = '(SELECT pr.report_id FROM pm_performance_reports pr
                         WHERE pr.employee_id = e.employee_id
                         ORDER BY pr.period_end DESC LIMIT 1)';
        }

        if ($this->tableHasColumns('eer_recognitions', ['department'])
            && $this->tableHasColumns('em_employees', ['employee_id', 'department_id'])
            && $this->tableHasColumns('em_departments', ['department_id', 'department_name'])) {
            $columns[] = 'department';
            $values[] = 'd.department_name';
            $joins = ' LEFT JOIN em_departments d ON e.department_id = d.department_id';
        }

        $sql = 'INSERT INTO eer_recognitions (' . implode(', ', $columns) . ')
                SELECT ' . implode(', ', $values) . '
                FROM em_employees e' . $joins . '
                WHERE e.employee_id = :receiver_id';
        
        $insert = $this->execute($sql, [
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
            'points' => $points
        ]);
        if ($insert->rowCount() !== 1) {
            throw new \RuntimeException('Recognition was not saved. Refresh the page and select an employee from the current list.');
        }
        
        $recognitionId = (int)$this->db->lastInsertId();

        $notification = new Notification();
        try {
            $this->execute(
                'UPDATE eer_recognitions er
                 INNER JOIN em_employees e ON e.employee_id = er.receiver_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 SET er.department = COALESCE(er.department, d.department_name),
                     er.performance_report_id = COALESCE(
                         er.performance_report_id,
                         (SELECT pr.report_id FROM pm_performance_reports pr
                          WHERE pr.employee_id = e.employee_id
                          ORDER BY pr.period_end DESC LIMIT 1)
                     )
                 WHERE er.department IS NULL OR er.performance_report_id IS NULL'
            );
        } catch (\PDOException $e) {
            error_log('Recognition saved, but metadata refresh failed: ' . $e->getMessage());
        }

        try {
            $leaderboard = $this->getComprehensiveLeaderboard(10000);
            foreach ($leaderboard as $entry) {
                if (empty($entry['rank_position'])) continue;
                $this->execute(
                    'UPDATE eer_recognitions
                     SET leaderboard_position = :leaderboard_position
                     WHERE receiver_id = :receiver_id',
                    [
                        'leaderboard_position' => (int)($entry['rank_position'] ?? 0),
                        'receiver_id' => (int)($entry['employee_id'] ?? 0)
                    ]
                );
            }
        } catch (\PDOException $e) {
            error_log('Recognition saved, but leaderboard refresh failed: ' . $e->getMessage());
        }

        try {
            $notification->notifyEmployees([$receiverId], 'You received a recognition: ' . $message, 'recognition');
        } catch (\PDOException $e) {
            error_log('Recognition saved, but recipient notification failed: ' . $e->getMessage());
        }

        try {
            $notification->notifyHr('A recognition was sent to employee #' . $receiverId . '.', 'recognition', [(int)$sender_id, $receiverId]);
        } catch (\PDOException $e) {
            error_log('Recognition saved, but HR notification failed: ' . $e->getMessage());
        }

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
        $nextEmployeeBadgeId = (int)$this->execute(
            'SELECT COALESCE(MAX(eer_employee_badge_id), 0) + 1 FROM eer_employee_badges'
        )->fetchColumn();

        $sql = "INSERT INTO eer_employee_badges (eer_employee_badge_id, employee_id, badge_id, awarded_by, reason, performance_linked, performance_score, awarded_at)
            VALUES (:employeeBadgeId, :employeeId, :badgeId, :awardedBy, :reason, :performanceLinked, :performanceScore, NOW())";
        return $this->execute($sql, [
            'employeeBadgeId' => $nextEmployeeBadgeId,
            'employeeId' => $employeeId,
            'badgeId' => $badgeId,
            'awardedBy' => $awardedBy,
            'reason' => 'Assigned by HR/Admin.',
            'performanceLinked' => $performanceScore !== null && (float)$performanceScore > 0 ? 1 : 0,
            'performanceScore' => $performanceScore !== null ? (float)$performanceScore : 0.00
        ]);
    }

    public function adjustEmployeePoints($employeeId, $points, $reason, $adminEmployeeId)
    {
        $nextRecognitionId = (int)$this->execute(
            'SELECT COALESCE(MAX(eer_recognition_id), 0) + 1 FROM eer_recognitions'
        )->fetchColumn();
        $this->execute(
            "INSERT INTO eer_recognitions
             (eer_recognition_id, sender_id, receiver_id, message, points, category, source, status, created_at)
             VALUES (:id, :sender_id, :receiver_id, :message, :points, 'admin_adjustment', 'manual', 'approved', NOW())",
            [
                'id' => $nextRecognitionId,
                'sender_id' => (int)$adminEmployeeId,
                'receiver_id' => (int)$employeeId,
                'message' => trim($reason),
                'points' => (int)$points
            ]
        );
        return $nextRecognitionId;
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
          AND er.status = 'approved'
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
              AND er.status = 'approved'
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
        $nextVoteId = (int)$this->execute(
            'SELECT COALESCE(MAX(eer_award_vote_id), 0) + 1 FROM eer_award_votes'
        )->fetchColumn();
        $sql = "INSERT INTO eer_award_votes (eer_award_vote_id, award_history_id, voter_user_id, nominee_employee_id, created_at)
                VALUES (:vote_id, :award_history_id, :voter_user_id, :nominee_employee_id, NOW())";
        $this->execute($sql, [
            'vote_id' => $nextVoteId,
            'award_history_id' => $awardHistoryId,
            'voter_user_id' => $voterUserId,
            'nominee_employee_id' => $nomineeEmployeeId
        ]);

        $this->execute(
            "UPDATE eer_award_history
             SET vote_count = vote_count + 1,
                 points = GREATEST(COALESCE(points, 0), (vote_count + 1) * 5)
             WHERE eer_award_history_id = :id",
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
        SELECT monthly_points.*,
               RANK() OVER (ORDER BY monthly_points.total_points DESC) AS rank_position
        FROM (
            SELECT e.employee_id,
                   CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
                   d.department_name AS department,
                   p.position_name AS position,
                   COALESCE(r.recognition_points, 0) AS recognition_points,
                   COALESCE(b.badge_points, 0) AS badge_points,
                   COALESCE(a.award_points, 0) AS award_points,
                   COALESCE(r.recognition_points, 0)
                     + COALESCE(b.badge_points, 0)
                     + COALESCE(a.award_points, 0)
                     - LEAST(COALESCE(a.award_points, 0), COALESCE(mrb.monthly_badge_points, 0)) AS total_points,
                   COALESCE(r.recognition_count, 0) AS recognition_count
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            LEFT JOIN (
                SELECT receiver_id,
                       SUM(points) AS recognition_points,
                       COUNT(*) AS recognition_count
                FROM eer_recognitions
                WHERE points IS NOT NULL
                  AND status = 'approved'
                  AND (source IS NULL OR source IN ('manual', 'achievement'))
                  AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND created_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                GROUP BY receiver_id
            ) r ON r.receiver_id = e.employee_id
            LEFT JOIN (
                SELECT eb.employee_id, SUM(badge.points_value) AS badge_points
                FROM eer_employee_badges eb
                INNER JOIN eer_badges badge ON badge.eer_badge_id = eb.badge_id
                WHERE eb.awarded_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND eb.awarded_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                GROUP BY eb.employee_id
            ) b ON b.employee_id = e.employee_id
            LEFT JOIN (
                SELECT ca.employee_id, SUM(badge.points_value) AS monthly_badge_points
                FROM eer_recognition_cycle_awards ca
                INNER JOIN eer_recognition_cycles cycle ON cycle.month_year = ca.month_year
                INNER JOIN eer_award_history ah
                    ON ah.eer_award_history_id = ca.award_history_id
                   AND ah.award_type = 'employee_of_month'
                INNER JOIN eer_employee_badges eb
                    ON eb.eer_employee_badge_id = ca.employee_badge_id
                INNER JOIN eer_badges badge
                    ON badge.eer_badge_id = eb.badge_id
                   AND badge.category = 'monthly_recognition'
                WHERE ca.month_year = DATE_FORMAT(CURDATE(), '%Y-%m')
                  AND cycle.status = 'approved'
                GROUP BY ca.employee_id
            ) mrb ON mrb.employee_id = e.employee_id
            LEFT JOIN (
                SELECT employee_id, SUM(points) AS award_points
                FROM eer_award_history
                WHERE points > 0 AND month_year = DATE_FORMAT(CURDATE(), '%Y-%m')
                GROUP BY employee_id
            ) a ON a.employee_id = e.employee_id
        ) monthly_points
        WHERE monthly_points.total_points > 0
        ORDER BY monthly_points.total_points DESC,
                 monthly_points.recognition_count DESC,
                 monthly_points.employee_name ASC
        LIMIT " . (int)$limit;

        return $this->execute($sql)->fetchAll();
    }

    private function autoAwardAchievementBadges()
    {
        $lockAcquired = (int)$this->execute(
            "SELECT GET_LOCK('hrms_auto_award_achievement_badges', 5)"
        )->fetchColumn() === 1;
        if (!$lockAcquired) return;

        try {
            // Remove duplicates created by earlier overlapping auto-refresh requests.
            $this->execute(
                'DELETE duplicate_badge FROM eer_employee_badges duplicate_badge
                 INNER JOIN eer_employee_badges original_badge
                   ON original_badge.employee_id = duplicate_badge.employee_id
                  AND original_badge.badge_id = duplicate_badge.badge_id
                  AND original_badge.eer_employee_badge_id < duplicate_badge.eer_employee_badge_id'
            );

            $badges = $this->execute(
                "SELECT eer_badge_id, name, requirement_value
                 FROM eer_badges
                 WHERE status = 'active'
                   AND requirement_type = 'achievement'
                   AND requirement_value IS NOT NULL
                   AND requirement_value > 0"
            )->fetchAll();

            foreach ($badges as $badge) {
                $qualifiedEmployees = $this->execute(
                    "SELECT receiver_id as employee_id, COUNT(*) as achievement_count
                     FROM eer_recognitions
                     WHERE source = 'achievement' OR category = 'achievement'
                     GROUP BY receiver_id
                     HAVING achievement_count >= :requirement_value",
                    ['requirement_value' => (int)$badge['requirement_value']]
                )->fetchAll();

                foreach ($qualifiedEmployees as $employee) {
                    $alreadyAwarded = $this->execute(
                        'SELECT 1 FROM eer_employee_badges WHERE employee_id = :employee_id AND badge_id = :badge_id LIMIT 1',
                        [
                            'employee_id' => (int)$employee['employee_id'],
                            'badge_id' => (int)$badge['eer_badge_id']
                        ]
                    )->fetchColumn();
                    if ($alreadyAwarded) continue;

                    $nextEmployeeBadgeId = (int)$this->execute(
                        'SELECT COALESCE(MAX(eer_employee_badge_id), 0) + 1 FROM eer_employee_badges'
                    )->fetchColumn();
                    $this->execute(
                        "INSERT INTO eer_employee_badges
                         (eer_employee_badge_id, employee_id, badge_id, awarded_at, reason, performance_linked, performance_score)
                         VALUES (:id, :employee_id, :badge_id, NOW(), :reason, 0, 0)",
                        [
                            'id' => $nextEmployeeBadgeId,
                            'employee_id' => (int)$employee['employee_id'],
                            'badge_id' => (int)$badge['eer_badge_id'],
                            'reason' => 'Automatically awarded after meeting the achievement requirement.'
                        ]
                    );
                }
            }
        } finally {
            $this->execute("SELECT RELEASE_LOCK('hrms_auto_award_achievement_badges')");
        }
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

        $this->execute(
            'UPDATE eer_award_history ah
             SET ah.performance_score = COALESCE(
                     ah.performance_score,
                     (SELECT pr.overall_rating * 20 FROM pm_performance_reports pr
                      WHERE pr.employee_id = ah.employee_id
                      ORDER BY pr.period_end DESC, pr.report_id DESC LIMIT 1),
                     (SELECT pa.overall_rating * 20 FROM pm_appraisals pa
                      WHERE pa.employee_id = ah.employee_id
                      ORDER BY COALESCE(pa.due_date, pa.created_at) DESC, pa.appraisal_id DESC LIMIT 1),
                     0
                 ),
                 ah.points = GREATEST(COALESCE(ah.points, 0), COALESCE(ah.vote_count, 0) * 5),
                 ah.award_icon = COALESCE(ah.award_icon, :award_icon)
             WHERE ah.month_year = :month_year AND ah.award_type = :award_type',
            ['month_year' => $monthYear, 'award_type' => 'employee_of_month', 'award_icon' => 'fas fa-trophy']
        );

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
            COALESCE(ah.award_icon, 'fas fa-trophy') as award_icon,
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
            0 as performance_points,
            COALESCE(SUM(CASE WHEN source = 'badge' THEN points ELSE 0 END), 0) as badge_points,
            COALESCE(SUM(CASE WHEN source = 'award' THEN points ELSE 0 END), 0) as award_points,
            COALESCE(SUM(points), 0) as total_points
        FROM (
            SELECT 'recognition' as source, er.points
            FROM eer_recognitions er
            WHERE er.receiver_id = :employeeId
              AND er.status = 'approved'
              AND (er.source IS NULL OR er.source IN ('manual', 'achievement'))
            
            UNION ALL
            
            SELECT 'badge' as source, b.points_value
            FROM eer_employee_badges eb
            JOIN eer_badges b ON eb.badge_id = b.eer_badge_id
            WHERE eb.employee_id = :employeeId
              AND NOT EXISTS (
                  SELECT 1
                  FROM eer_recognition_cycle_awards ca
                  INNER JOIN eer_recognition_cycles cycle ON cycle.month_year = ca.month_year
                  INNER JOIN eer_award_history ah
                      ON ah.eer_award_history_id = ca.award_history_id
                     AND ah.award_type = 'employee_of_month'
                  WHERE ca.employee_badge_id = eb.eer_employee_badge_id
                    AND cycle.status = 'approved'
              )
            
            UNION ALL
            
            SELECT 'award' as source,
                COALESCE(ah.points, 0) as points
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
        $sql = "
        SELECT monthly_points.*,
               RANK() OVER (PARTITION BY monthly_points.department ORDER BY monthly_points.total_points DESC) AS dept_rank
        FROM (
            SELECT e.employee_id,
                   CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
                   d.department_name AS department,
                   p.position_name AS position,
                   COALESCE(r.points, 0) + COALESCE(b.points, 0) + COALESCE(a.points, 0)
                     - LEAST(COALESCE(a.points, 0), COALESCE(mrb.monthly_badge_points, 0)) AS total_points
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            LEFT JOIN (
                SELECT receiver_id, SUM(points) AS points
                FROM eer_recognitions
                WHERE points IS NOT NULL AND status = 'approved'
                  AND (source IS NULL OR source IN ('manual', 'achievement'))
                  AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND created_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                GROUP BY receiver_id
            ) r ON r.receiver_id = e.employee_id
            LEFT JOIN (
                SELECT eb.employee_id, SUM(badge.points_value) AS points
                FROM eer_employee_badges eb
                INNER JOIN eer_badges badge ON badge.eer_badge_id = eb.badge_id
                WHERE eb.awarded_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                  AND eb.awarded_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                GROUP BY eb.employee_id
            ) b ON b.employee_id = e.employee_id
            LEFT JOIN (
                SELECT ca.employee_id, SUM(badge.points_value) AS monthly_badge_points
                FROM eer_recognition_cycle_awards ca
                INNER JOIN eer_recognition_cycles cycle ON cycle.month_year = ca.month_year
                INNER JOIN eer_award_history ah
                    ON ah.eer_award_history_id = ca.award_history_id
                   AND ah.award_type = 'employee_of_month'
                INNER JOIN eer_employee_badges eb
                    ON eb.eer_employee_badge_id = ca.employee_badge_id
                INNER JOIN eer_badges badge
                    ON badge.eer_badge_id = eb.badge_id
                   AND badge.category = 'monthly_recognition'
                WHERE ca.month_year = DATE_FORMAT(CURDATE(), '%Y-%m')
                  AND cycle.status = 'approved'
                GROUP BY ca.employee_id
            ) mrb ON mrb.employee_id = e.employee_id
            LEFT JOIN (
                SELECT employee_id, SUM(points) AS points
                FROM eer_award_history
                WHERE points > 0 AND month_year = DATE_FORMAT(CURDATE(), '%Y-%m')
                GROUP BY employee_id
            ) a ON a.employee_id = e.employee_id
        ) monthly_points
        WHERE monthly_points.total_points > 0"
        . ($department ? ' AND monthly_points.department = :department' : '') . "
        ORDER BY monthly_points.total_points DESC, monthly_points.employee_name ASC
        LIMIT " . (int)$limit;

        return $this->execute($sql, $department ? ['department' => $department] : [])->fetchAll();
    }
}
