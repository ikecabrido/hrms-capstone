<?php
namespace App\Models;

use DomainException;

class RecognitionCycle extends BaseModel
{
    public function __construct(\PDO $connection = null)
    {
        parent::__construct($connection);
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $badgeIdColumn = $this->execute(
            "SHOW COLUMNS FROM eer_badges LIKE 'eer_badge_id'"
        )->fetch(\PDO::FETCH_ASSOC);
        if ($badgeIdColumn && stripos((string)($badgeIdColumn['Extra'] ?? ''), 'auto_increment') === false) {
            $this->execute(
                'ALTER TABLE eer_badges
                 MODIFY eer_badge_id int(11) NOT NULL AUTO_INCREMENT'
            );
        }
        $this->execute(
            "CREATE TABLE IF NOT EXISTS eer_recognition_cycles (
                month_year varchar(7) NOT NULL,
                review_period varchar(100) DEFAULT NULL,
                period_start date DEFAULT NULL,
                period_end date DEFAULT NULL,
                status enum('pending','approved') NOT NULL DEFAULT 'pending',
                approved_by int(11) DEFAULT NULL,
                approved_at datetime DEFAULT NULL,
                PRIMARY KEY (month_year),
                KEY approved_by (approved_by),
                CONSTRAINT fk_eer_recognition_cycles_approver
                    FOREIGN KEY (approved_by)
                    REFERENCES em_employees (employee_id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        $badgeIdColumn = $this->execute(
            "SHOW COLUMNS FROM eer_employee_badges LIKE 'eer_employee_badge_id'"
        )->fetch(\PDO::FETCH_ASSOC);
        if ($badgeIdColumn && stripos((string)($badgeIdColumn['Extra'] ?? ''), 'auto_increment') === false) {
            $this->execute(
                'ALTER TABLE eer_employee_badges
                 MODIFY eer_employee_badge_id int(11) NOT NULL AUTO_INCREMENT'
            );
        }
        $this->execute(
            "CREATE TABLE IF NOT EXISTS eer_recognition_cycle_awards (
                recognition_cycle_award_id int(11) NOT NULL AUTO_INCREMENT,
                month_year varchar(7) NOT NULL,
                award_history_id int(11) NOT NULL,
                recognition_id int(11) DEFAULT NULL,
                employee_id int(11) DEFAULT NULL,
                performance_report_id int(11) DEFAULT NULL,
                badge_id int(11) DEFAULT NULL,
                reward_id int(11) DEFAULT NULL,
                award_level enum('employee_of_month','outstanding_employee') NOT NULL,
                reward_status enum('not_assigned','pending','fulfilled') NOT NULL DEFAULT 'not_assigned',
                assigned_at datetime NOT NULL DEFAULT current_timestamp(),
                fulfilled_by int(11) DEFAULT NULL,
                fulfilled_at datetime DEFAULT NULL,
                employee_badge_id int(11) DEFAULT NULL,
                PRIMARY KEY (recognition_cycle_award_id),
                UNIQUE KEY uq_recognition_cycle_award_history (award_history_id),
                UNIQUE KEY uq_recognition_cycle_employee (month_year, employee_id),
                KEY idx_recognition_cycle_reward_status (month_year, reward_status),
                CONSTRAINT fk_recognition_cycle_awards_cycle
                    FOREIGN KEY (month_year) REFERENCES eer_recognition_cycles (month_year)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_history
                    FOREIGN KEY (award_history_id) REFERENCES eer_award_history (eer_award_history_id)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_recognition
                    FOREIGN KEY (recognition_id) REFERENCES eer_recognitions (eer_recognition_id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_employee
                    FOREIGN KEY (employee_id) REFERENCES em_employees (employee_id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_badge
                    FOREIGN KEY (badge_id) REFERENCES eer_badges (eer_badge_id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_employee_badge
                    FOREIGN KEY (employee_badge_id) REFERENCES eer_employee_badges (eer_employee_badge_id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_reward
                    FOREIGN KEY (reward_id) REFERENCES eer_rewards (eer_reward_id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_recognition_cycle_awards_fulfilled_by
                    FOREIGN KEY (fulfilled_by) REFERENCES em_employees (employee_id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        if (!$this->tableHasColumns('eer_recognition_cycle_awards', ['employee_badge_id'])) {
            $this->execute(
                'ALTER TABLE eer_recognition_cycle_awards
                 ADD COLUMN employee_badge_id int(11) DEFAULT NULL AFTER fulfilled_at,
                 ADD KEY idx_recognition_cycle_employee_badge (employee_badge_id),
                 ADD CONSTRAINT fk_recognition_cycle_awards_employee_badge
                    FOREIGN KEY (employee_badge_id)
                    REFERENCES eer_employee_badges (eer_employee_badge_id)
                    ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }
        $rewardStatusColumn = $this->execute(
            "SHOW COLUMNS FROM eer_recognition_cycle_awards LIKE 'reward_status'"
        )->fetch(\PDO::FETCH_ASSOC);
        if ($rewardStatusColumn && strpos((string)$rewardStatusColumn['Type'], 'not_assigned') === false) {
            $this->execute(
                "ALTER TABLE eer_recognition_cycle_awards
                 MODIFY reward_status enum('not_assigned','pending','fulfilled')
                 NOT NULL DEFAULT 'not_assigned'"
            );
        }
    }

    private function getOrCreateMonthlyRankBadge($rank)
    {
        $pointsByRank = [50, 40, 30, 20, 10];
        $badgeNames = [
            1 => 'Employee of the Month',
            2 => 'Top 2 Monthly Recognition',
            3 => 'Top 3 Monthly Recognition',
            4 => 'Top 4 Monthly Recognition',
            5 => 'Top 5 Monthly Recognition'
        ];
        $tiersByRank = [1 => 'platinum', 2 => 'gold', 3 => 'silver', 4 => 'bronze', 5 => 'bronze'];
        $rank = max(1, min(5, (int)$rank));
        $badgeName = $badgeNames[$rank];
        $badge = $this->execute(
            'SELECT eer_badge_id FROM eer_badges
             WHERE category = :category AND name = :name
             LIMIT 1 FOR UPDATE',
            ['category' => 'monthly_recognition', 'name' => $badgeName]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($badge) {
            $badgeId = (int)$badge['eer_badge_id'];
            $this->execute(
                'UPDATE eer_badges
                 SET points_value = :points, status = \'active\'
                 WHERE eer_badge_id = :badge_id',
                ['points' => $pointsByRank[$rank - 1], 'badge_id' => $badgeId]
            );
            return $badgeId;
        }

        $this->execute(
            'INSERT INTO eer_badges
                (name, description, icon, tier, points_value, category, requirement_type, status, created_at)
             VALUES
                (:name, :description, :icon, :tier, :points, \'monthly_recognition\', \'manual\', \'active\', NOW())',
            [
                'name' => $badgeName,
                'description' => 'Automatically awarded to the Top ' . $rank . ' employee in an approved monthly recognition cycle.',
                'icon' => $rank === 1 ? 'fas fa-trophy' : 'fas fa-medal',
                'tier' => $tiersByRank[$rank],
                'points' => $pointsByRank[$rank - 1]
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    private function getRankingForLatestAvailablePeriod(array $requestedPeriod = null)
    {
        $feedRecipients = $this->execute(
            "SELECT er.receiver_id AS employee_id,
                    COALESCE(NULLIF(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name), ''),
                             CONCAT('Employee #', er.receiver_id)) AS employee_name,
                    d.department_name AS department,
                    SUM(COALESCE(er.points, 0)) AS recognition_points,
                    COUNT(*) AS recognition_count
             FROM eer_recognitions er
             INNER JOIN em_employees e ON e.employee_id = er.receiver_id
             LEFT JOIN em_departments d ON d.department_id = e.department_id
             WHERE er.status = 'approved'
               AND (er.source IS NULL OR er.source != 'performance')
             GROUP BY er.receiver_id, e.first_name, e.middle_name, e.last_name, d.department_name
             ORDER BY recognition_points DESC, er.receiver_id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
        if (!$feedRecipients) {
            return [
                'status' => 'no_recognitions',
                'message' => 'No approved Recognition Feed recipients are available to rank.',
                'candidates' => []
            ];
        }

        $requiredColumns = [
            'report_id',
            'employee_id',
            'review_period',
            'period_start',
            'period_end',
            'overall_rating',
            'kpi_health_score',
            'status'
        ];
        $rows = [];
        if ($this->tableHasColumns('pm_performance_reports', $requiredColumns)) {
            $rows = $this->execute(
                "SELECT pr.report_id, pr.employee_id, pr.review_period, pr.period_start,
                        pr.period_end, pr.overall_rating, pr.kpi_health_score, pr.status
                 FROM pm_performance_reports pr
                 WHERE pr.status = 'Finalized'
                   AND pr.employee_id IS NOT NULL
                   AND pr.overall_rating IS NOT NULL
                 ORDER BY pr.period_end DESC, pr.period_start DESC,
                          pr.report_id DESC"
            )->fetchAll(\PDO::FETCH_ASSOC);
        }

        $feedRecipientIds = array_fill_keys(
            array_map('intval', array_column($feedRecipients, 'employee_id')),
            true
        );
        $period = null;
        if ($requestedPeriod !== null) {
            foreach ($rows as $row) {
                if (isset($feedRecipientIds[(int)$row['employee_id']])
                    && $row['review_period'] === ($requestedPeriod['review_period'] ?? null)
                    && $row['period_start'] === ($requestedPeriod['period_start'] ?? null)
                    && $row['period_end'] === ($requestedPeriod['period_end'] ?? null)) {
                    $period = $row;
                    break;
                }
            }
        } else {
            foreach ($rows as $row) {
                if (isset($feedRecipientIds[(int)$row['employee_id']])) {
                    $period = $row;
                    break;
                }
            }
        }
        $reportSets = [];
        if ($period) {
            foreach ($rows as $row) {
                $employeeId = (int)$row['employee_id'];
                if (!isset($feedRecipientIds[$employeeId])
                    || ($period['period_end'] !== null
                        && $row['period_end'] !== null
                        && $row['period_end'] > $period['period_end'])) {
                    continue;
                }
                $row['employee_id'] = $employeeId;
                $row['overall_rating'] = (float)$row['overall_rating'];
                $row['kpi_health_score'] = $row['kpi_health_score'] !== null
                    && is_numeric($row['kpi_health_score'])
                    && (float)$row['kpi_health_score'] >= 0
                    && (float)$row['kpi_health_score'] <= 100
                    ? (float)$row['kpi_health_score']
                    : null;
                if (!isset($reportSets[$employeeId])) {
                    $reportSets[$employeeId] = [$row];
                    continue;
                }
                $latestReport = $reportSets[$employeeId][0];
                if ($row['review_period'] === $latestReport['review_period']
                    && $row['period_start'] === $latestReport['period_start']
                    && $row['period_end'] === $latestReport['period_end']
                    && $row['status'] === $latestReport['status']) {
                    $reportSets[$employeeId][] = $row;
                }
            }
        }

        $reportsByEmployee = [];
        $reportConflicts = [];
        foreach ($reportSets as $employeeId => $reports) {
            $ratings = array_unique(array_column($reports, 'overall_rating'));
            $kpiScores = array_unique(array_map(function ($report) {
                return $report['kpi_health_score'] === null ? 'missing' : (string)$report['kpi_health_score'];
            }, $reports));
            if (count($ratings) > 1 || count($kpiScores) > 1) {
                $reportConflicts[] = $employeeId;
                continue;
            }
            $reportsByEmployee[$employeeId] = $reports[0];
        }
        if ($reportConflicts) {
            return [
                'status' => 'report_conflict',
                'message' => 'Multiple performance reports for a recognized employee have conflicting ratings. Resolve those reports before finalizing recognition.',
                'review_period' => $period['review_period'] ?? null,
                'period_start' => $period['period_start'] ?? null,
                'period_end' => $period['period_end'] ?? null,
                'conflicting_employee_ids' => array_values($reportConflicts),
                'eligible_count' => count($feedRecipients),
                'candidates' => []
            ];
        }

        $candidates = [];
        foreach ($feedRecipients as $recipient) {
            $employeeId = (int)$recipient['employee_id'];
            $report = $reportsByEmployee[$employeeId] ?? null;
            $candidates[] = [
                'report_id' => $report['report_id'] ?? null,
                'employee_id' => $employeeId,
                'review_period' => $report['review_period'] ?? ($period['review_period'] ?? null),
                'period_start' => $report['period_start'] ?? ($period['period_start'] ?? null),
                'period_end' => $report['period_end'] ?? ($period['period_end'] ?? null),
                'overall_rating' => $report ? (float)$report['overall_rating'] : null,
                'kpi_health_score' => $report['kpi_health_score'] ?? null,
                'performance_report_status' => $report['status'] ?? null,
                'employee_name' => $recipient['employee_name'],
                'department' => $recipient['department'],
                'recognition_points' => (int)$recipient['recognition_points'],
                'recognition_count' => (int)$recipient['recognition_count']
            ];
        }
        usort($candidates, function ($first, $second) {
            if ($first['overall_rating'] !== null && $second['overall_rating'] === null) return -1;
            if ($first['overall_rating'] === null && $second['overall_rating'] !== null) return 1;
            if ($first['overall_rating'] !== null && $second['overall_rating'] !== null) {
                $ratingOrder = $second['overall_rating'] <=> $first['overall_rating'];
                if ($ratingOrder !== 0) return $ratingOrder;
            }
            return strcasecmp($first['employee_name'], $second['employee_name'])
                ?: ($first['employee_id'] <=> $second['employee_id']);
        });

        $ratingGroups = [];
        foreach ($candidates as $candidate) {
            $groupKey = $candidate['overall_rating'] === null
                ? 'unrated:' . $candidate['employee_id']
                : 'rating:' . (string)$candidate['overall_rating'];
            $ratingGroups[$groupKey][] = $candidate;
        }
        $tieGroups = [];
        foreach ($ratingGroups as $groupKey => $group) {
            $isUnrated = strpos($groupKey, 'unrated:') === 0;
            $allKpiValid = !$isUnrated && count(array_filter($group, function ($candidate) {
                return $candidate['kpi_health_score'] !== null;
            })) === count($group);
            if ($allKpiValid) {
                usort($group, function ($first, $second) {
                    return ($second['kpi_health_score'] <=> $first['kpi_health_score'])
                        ?: ($first['employee_id'] <=> $second['employee_id']);
                });
            } else {
                usort($group, function ($first, $second) {
                    return $first['employee_id'] <=> $second['employee_id'];
                });
            }
            $ties = [];
            foreach ($group as $candidate) {
                $tieKey = $isUnrated
                    ? $groupKey
                    : ($allKpiValid
                        ? 'kpi:' . (string)$candidate['kpi_health_score']
                        : 'rating:' . (string)$candidate['overall_rating']);
                $ties[$tieKey][] = $candidate['employee_id'];
            }
            foreach ($ties as $employeesInTie) {
                if (count($employeesInTie) > 1) {
                    $tieGroups[] = $employeesInTie;
                }
            }
            $ratingGroups[$groupKey] = $group;
        }
        $candidates = [];
        foreach ($ratingGroups as $group) {
            foreach ($group as $candidate) {
                $candidates[] = $candidate;
            }
        }

        $requiredCount = min(5, count($candidates));
        $position = 0;
        $candidateCount = count($candidates);
        foreach ($candidates as &$candidate) {
            $position++;
            $candidate['rank'] = $position;
            $candidate['selected_by_default'] = $position <= $requiredCount;
            $candidate['tie_group'] = null;
            foreach ($tieGroups as $index => $employeesInTie) {
                if (in_array($candidate['employee_id'], $employeesInTie, true)) {
                    $candidate['tie_group'] = $index + 1;
                    break;
                }
            }
        }
        unset($candidate);
        $allCandidates = $candidates;
        $cutoffCandidate = $requiredCount > 0 ? $candidates[$requiredCount - 1] : null;
        $cutoffTieGroup = $cutoffCandidate && $cutoffCandidate['tie_group'] !== null
            ? $cutoffCandidate['tie_group']
            : null;
        foreach ($candidates as &$candidate) {
            $candidate['cutoff_tie'] = $cutoffTieGroup !== null
                && $candidate['tie_group'] === $cutoffTieGroup;
        }
        unset($candidate);
        $topTie = isset($candidates[0]) && $candidates[0]['tie_group'] !== null
            ? $candidates[0]['tie_group']
            : null;
        foreach ($candidates as &$candidate) {
            $candidate['winner_eligible'] = $topTie !== null
                ? $candidate['tie_group'] === $topTie
                : $candidate['rank'] === 1;
            if ($candidate['cutoff_tie']) {
                $candidate['selected_by_default'] = false;
            }
        }
        unset($candidate);
        $allCandidates = $candidates;
        $eligible = array_values(array_filter($candidates, function ($candidate) use ($requiredCount, $cutoffTieGroup) {
            return $candidate['rank'] <= $requiredCount
                || ($cutoffTieGroup !== null && $candidate['tie_group'] === $cutoffTieGroup);
        }));
        return [
            'status' => 'pending',
            'review_period' => $period['review_period'] ?? null,
            'period_start' => $period['period_start'] ?? null,
            'period_end' => $period['period_end'] ?? null,
            'required_count' => $requiredCount,
            'eligible_count' => $candidateCount,
            'cutoff_tie' => $cutoffTieGroup !== null,
            'top_tie' => $topTie !== null,
            'cutoff_rating' => $cutoffCandidate['overall_rating'] ?? null,
            'ranking_source' => 'recognition_feed_recipients',
            'candidates' => $eligible,
            'all_candidates' => $allCandidates,
            'ranked_employees' => $allCandidates
        ];
    }

    public function leaderboard($limit)
    {
        $ranking = $this->getRankingForLatestAvailablePeriod();
        return array_slice($ranking['all_candidates'] ?? [], 0, max(1, (int)$limit));
    }

    public function preview($monthYear, $includePrivate = false)
    {
        $cycle = $this->execute(
            'SELECT month_year, review_period, period_start, period_end, status, approved_at,
                    approved_by
             FROM eer_recognition_cycles WHERE month_year = :month_year',
            ['month_year' => $monthYear]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($cycle && $cycle['status'] === 'approved') {
            $cycle['status'] = 'approved';
            $cycle['required_count'] = 0;
            $cycle['cutoff_tie'] = false;
            $cycle['candidates'] = $this->execute(
                "SELECT ah.eer_award_history_id, ah.employee_id, ah.award_name, ah.points AS award_points,
                        ah.reason, ah.status AS award_status, ah.performance_score / 20 AS overall_rating,
                        e.department_id, d.department_name AS department,
                           COALESCE(NULLIF(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name), ''),
                                    CONCAT('Former employee #', ah.employee_id)) AS employee_name,
                        ca.performance_report_id, ca.badge_id, ca.employee_badge_id, b.name AS badge_name,
                        b.icon AS badge_icon, b.points_value AS badge_points,
                        ca.reward_id, r.name AS reward_name, ca.reward_status,
                        ca.assigned_at, ca.fulfilled_at
                    FROM eer_award_history ah
                    INNER JOIN eer_recognition_cycle_awards ca
                        ON ca.award_history_id = ah.eer_award_history_id
                    LEFT JOIN em_employees e ON e.employee_id = ah.employee_id
                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                 LEFT JOIN eer_badges b ON b.eer_badge_id = ca.badge_id
                 LEFT JOIN eer_rewards r ON r.eer_reward_id = ca.reward_id
                 WHERE ah.month_year = :month_year
                   AND ah.award_type = 'employee_of_month'
                 ORDER BY CASE WHEN ah.status = 'winner' THEN 0 ELSE 1 END,
                          ah.performance_score DESC, ah.employee_id ASC",
                ['month_year' => $monthYear]
            )->fetchAll(\PDO::FETCH_ASSOC);
            $approvedByEmployee = [];
            foreach ($cycle['candidates'] as $approvedCandidate) {
                $approvedByEmployee[(int)$approvedCandidate['employee_id']] = $approvedCandidate;
            }
            $feedRanking = $this->getRankingForLatestAvailablePeriod([
                'review_period' => $cycle['review_period'],
                'period_start' => $cycle['period_start'],
                'period_end' => $cycle['period_end']
            ]);
            if ($feedRanking['status'] === 'pending') {
                $cycle['candidates'] = $feedRanking['ranked_employees'];
                $cycle['ranked_employees'] = $feedRanking['ranked_employees'];
                $cycle['required_count'] = $feedRanking['required_count'];
                foreach ($cycle['candidates'] as &$candidate) {
                    $approvedCandidate = $approvedByEmployee[(int)$candidate['employee_id']] ?? null;
                    if ($approvedCandidate) {
                        $candidate['award_name'] = $approvedCandidate['award_name'];
                        $candidate['award_status'] = $approvedCandidate['award_status'];
                        $candidate['award_points'] = (int)$approvedCandidate['award_points'];
                        $candidate['badge_id'] = (int)$approvedCandidate['badge_id'];
                        $candidate['employee_badge_id'] = (int)$approvedCandidate['employee_badge_id'];
                        $candidate['badge_name'] = $approvedCandidate['badge_name'];
                        $candidate['badge_icon'] = $approvedCandidate['badge_icon'];
                        $candidate['badge_points'] = (int)($approvedCandidate['badge_points'] ?? 0);
                        $candidate['reward_name'] = $approvedCandidate['reward_name'];
                        $candidate['reward_status'] = $approvedCandidate['reward_status'];
                    } else {
                        $candidate['award_status'] = null;
                    }
                }
                unset($candidate);
            }
            if (!$includePrivate) {
                unset($cycle['approved_by']);
                foreach (['candidates', 'ranked_employees'] as $candidateKey) {
                    if (!isset($cycle[$candidateKey])) {
                        continue;
                    }
                    foreach ($cycle[$candidateKey] as &$candidate) {
                        unset(
                            $candidate['overall_rating'],
                            $candidate['kpi_health_score'],
                            $candidate['performance_report_id'],
                            $candidate['report_id'],
                            $candidate['performance_report_status'],
                            $candidate['reason']
                        );
                    }
                    unset($candidate);
                }
                usort($cycle['candidates'], function ($first, $second) {
                    $winnerOrder = ($first['award_status'] === 'winner' ? 0 : 1)
                        <=> ($second['award_status'] === 'winner' ? 0 : 1);
                    return $winnerOrder ?: strcasecmp($first['employee_name'], $second['employee_name']);
                });
            }
            return $cycle;
        }

        $ranking = $this->getRankingForLatestAvailablePeriod();
        unset($ranking['all_candidates']);
        $ranking['month_year'] = $monthYear;
        if (!$includePrivate) {
            unset($ranking['conflicting_employee_ids']);
            if ($ranking['status'] === 'report_conflict') {
                $ranking['status'] = 'pending_review';
                $ranking['message'] = 'The recognition cycle is awaiting HR review.';
            }
            foreach (['candidates', 'ranked_employees'] as $candidateKey) {
                if (!isset($ranking[$candidateKey])) {
                    continue;
                }
                foreach ($ranking[$candidateKey] as &$candidate) {
                    unset(
                        $candidate['overall_rating'],
                        $candidate['kpi_health_score'],
                        $candidate['performance_report_id'],
                        $candidate['report_id'],
                        $candidate['performance_report_status']
                    );
                }
                unset($candidate);
            }
        }
        if (!$cycle) {
            return $ranking;
        }

        $ranking['status'] = 'pending';
        return $ranking;
    }

    public function approve(
        $monthYear,
        array $selectedEmployeeIds,
        $employeeOfMonthId,
        $approvedBy
    )
    {
        $this->db->beginTransaction();
        try {
            $ranking = $this->getRankingForLatestAvailablePeriod();
            if ($ranking['status'] !== 'pending' || empty($ranking['candidates'])) {
                throw new DomainException($ranking['message'] ?? 'No performance report period is available.', 422);
            }
            $requiredCount = (int)$ranking['required_count'];
            $submittedCount = count($selectedEmployeeIds);
            $normalizedIds = [];
            foreach ($selectedEmployeeIds as $employeeId) {
                $validatedId = filter_var($employeeId, FILTER_VALIDATE_INT);
                if ($validatedId === false || $validatedId <= 0) {
                    throw new DomainException('The selection contains an invalid employee ID.', 422);
                }
                $normalizedIds[] = (int)$validatedId;
            }
            $selectedEmployeeIds = array_values(array_unique($normalizedIds));
            if (count($selectedEmployeeIds) !== $requiredCount || count($selectedEmployeeIds) !== $submittedCount) {
                throw new DomainException(
                    'Select exactly ' . $requiredCount . ' eligible employees before approving.',
                    422
                );
            }

            $eligibleById = [];
            foreach ($ranking['candidates'] as $candidate) {
                $eligibleById[(int)$candidate['employee_id']] = $candidate;
            }
            foreach ($selectedEmployeeIds as $employeeId) {
                if (!isset($eligibleById[$employeeId])) {
                    throw new DomainException('The selection contains an employee outside the eligible Top 5 pool.', 422);
                }
            }
            foreach ($ranking['candidates'] as $candidate) {
                if ($candidate['overall_rating'] > $ranking['cutoff_rating']
                    && !in_array((int)$candidate['employee_id'], $selectedEmployeeIds, true)) {
                    throw new DomainException('All employees ranked above the tie at the cutoff must be included.', 422);
                }
            }
            foreach ($ranking['candidates'] as $candidate) {
                if ($candidate['rank'] < $requiredCount && !in_array((int)$candidate['employee_id'], $selectedEmployeeIds, true)) {
                    throw new DomainException('All employees ranked above the Top 5 cutoff must be included.', 422);
                }
            }

            if (!in_array((int)$employeeOfMonthId, $selectedEmployeeIds, true)) {
                throw new DomainException('The Employee of the Month must be one of the selected employees.', 422);
            }
            $highestSelectedCandidate = $eligibleById[(int)$employeeOfMonthId];
            $highestRank = min(array_map(function ($employeeId) use ($eligibleById) {
                return $eligibleById[$employeeId]['rank'];
            }, $selectedEmployeeIds));
            $topCandidate = $ranking['all_candidates'][0] ?? null;
            $isResolvedTopTie = $topCandidate
                && $topCandidate['tie_group'] !== null
                && $highestSelectedCandidate['tie_group'] === $topCandidate['tie_group'];
            if (!$isResolvedTopTie && $eligibleById[(int)$employeeOfMonthId]['rank'] !== $highestRank) {
                throw new DomainException(
                    'The Employee of the Month must have the highest selected Overall Rating.',
                    422
                );
            }

            $this->execute(
                "INSERT INTO eer_recognition_cycles
                    (month_year, review_period, period_start, period_end, status)
                 VALUES (:month_year, :review_period, :period_start, :period_end, 'pending')
                 ON DUPLICATE KEY UPDATE month_year = VALUES(month_year)",
                [
                    'month_year' => $monthYear,
                    'review_period' => $ranking['review_period'],
                    'period_start' => $ranking['period_start'],
                    'period_end' => $ranking['period_end']
                ]
            );
            $cycle = $this->execute(
                'SELECT status, review_period, period_start, period_end
                 FROM eer_recognition_cycles WHERE month_year = :month_year FOR UPDATE',
                ['month_year' => $monthYear]
            )->fetch(\PDO::FETCH_ASSOC);
            if (!$cycle || $cycle['status'] === 'approved') {
                throw new DomainException('This monthly recognition cycle has already been approved.', 409);
            }
            if ($cycle['review_period'] !== $ranking['review_period']
                || $cycle['period_start'] !== $ranking['period_start']
                || $cycle['period_end'] !== $ranking['period_end']) {
                throw new DomainException('The available review period changed. Reload the candidates and try again.', 409);
            }

            usort($selectedEmployeeIds, function ($firstId, $secondId) use ($eligibleById) {
                return $eligibleById[$firstId]['rank'] <=> $eligibleById[$secondId]['rank'];
            });
            $selectedEmployeeIds = array_values(array_filter(
                $selectedEmployeeIds,
                function ($employeeId) use ($employeeOfMonthId) {
                    return $employeeId !== (int)$employeeOfMonthId;
                }
            ));
            array_unshift($selectedEmployeeIds, (int)$employeeOfMonthId);
            $awardPointsByRank = [50, 40, 30, 20, 10];

            foreach ($selectedEmployeeIds as $selectionIndex => $employeeId) {
                $candidate = $eligibleById[$employeeId];
                $isEmployeeOfMonth = $employeeId === (int)$employeeOfMonthId;
                $rank = $selectionIndex + 1;
                $awardPoints = $awardPointsByRank[min($selectionIndex, count($awardPointsByRank) - 1)];
                $badgeId = $this->getOrCreateMonthlyRankBadge($rank);
                $awardName = $isEmployeeOfMonth ? 'Employee of the Month' : 'Outstanding Employee';
                $reason = $candidate['overall_rating'] !== null
                    ? sprintf(
                        'Recognition Feed recipient ranked by %s Overall Rating for %s (%s to %s).',
                        strtolower($candidate['performance_report_status'] ?? 'performance'),
                        $candidate['review_period'] ?: 'Unspecified period',
                        $candidate['period_start'] ?: 'N/A',
                        $candidate['period_end'] ?: 'N/A'
                    )
                    : 'Recognition Feed recipient ranked after employees with finalized Overall Ratings; recognition points did not determine ranking.';
                $this->execute(
                    'INSERT INTO eer_award_history
                        (employee_id, award_name, reason, nominated_by, award_type, points,
                         status, performance_score, month_year, award_icon, created_at)
                     VALUES
                        (:employee_id, :award_name, :reason, :approved_by, :award_type, :points,
                         :status, :performance_score, :month_year, :award_icon, NOW())',
                    [
                        'employee_id' => $employeeId,
                        'award_name' => $awardName,
                        'reason' => $reason,
                        'approved_by' => $approvedBy,
                        'award_type' => 'employee_of_month',
                        'points' => $awardPoints,
                        'status' => $isEmployeeOfMonth ? 'winner' : 'shortlisted',
                        'performance_score' => $candidate['overall_rating'] === null
                            ? null
                            : $candidate['overall_rating'] * 20,
                        'month_year' => $monthYear,
                        'award_icon' => $isEmployeeOfMonth ? 'fas fa-trophy' : 'fas fa-medal'
                    ]
                );
                $awardHistoryId = (int)$this->db->lastInsertId();
                $recognitionMessage = sprintf(
                    '%s for %s, selected from approved Recognition Feed recipients.',
                    $awardName,
                    $monthYear
                );
                $this->execute(
                    "INSERT INTO eer_recognitions
                        (sender_id, receiver_id, message, points, category, source,
                         performance_report_id, status, created_at)
                     VALUES
                        (:sender_id, :receiver_id, :message, 0, :category, 'manual',
                         :report_id, 'approved', NOW())",
                    [
                        'sender_id' => (int)$approvedBy,
                        'receiver_id' => $employeeId,
                        'message' => $recognitionMessage,
                        'category' => $isEmployeeOfMonth ? 'employee_of_month' : 'outstanding_employee',
                        'report_id' => $candidate['report_id'] === null
                            ? null
                            : (int)$candidate['report_id']
                    ]
                );
                $recognitionId = (int)$this->db->lastInsertId();
                $this->execute(
                    'INSERT INTO eer_employee_badges
                        (employee_id, badge_id, awarded_by, reason, performance_linked,
                         performance_score, awarded_at)
                     VALUES
                        (:employee_id, :badge_id, :awarded_by, :reason, 0, NULL, NOW())',
                    [
                        'employee_id' => $employeeId,
                        'badge_id' => $badgeId,
                        'awarded_by' => (int)$approvedBy,
                        'reason' => sprintf(
                            'Top %d in the approved monthly recognition cycle for %s.',
                            $rank,
                            $monthYear
                        )
                    ]
                );
                $employeeBadgeId = (int)$this->db->lastInsertId();
                $this->execute(
                    'INSERT INTO eer_recognition_cycle_awards
                        (month_year, award_history_id, recognition_id, employee_id, performance_report_id,
                         badge_id, reward_id, employee_badge_id, award_level, reward_status, assigned_at)
                     VALUES
                        (:month_year, :award_history_id, :recognition_id, :employee_id, :performance_report_id,
                         :badge_id, NULL, :employee_badge_id, :award_level, \'not_assigned\', NOW())',
                    [
                        'month_year' => $monthYear,
                        'award_history_id' => $awardHistoryId,
                        'recognition_id' => $recognitionId,
                        'employee_id' => $employeeId,
                        'performance_report_id' => $candidate['report_id'] === null
                            ? null
                            : (int)$candidate['report_id'],
                        'badge_id' => $badgeId,
                        'employee_badge_id' => $employeeBadgeId,
                        'award_level' => $isEmployeeOfMonth ? 'employee_of_month' : 'outstanding_employee'
                    ]
                );
            }

            $this->execute(
                "UPDATE eer_recognition_cycles
                 SET status = 'approved', approved_by = :approved_by, approved_at = NOW()
                 WHERE month_year = :month_year",
                [
                    'approved_by' => $approvedBy,
                    'month_year' => $monthYear
                ]
            );
            $this->db->commit();
            return $this->preview($monthYear, true);
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

}
