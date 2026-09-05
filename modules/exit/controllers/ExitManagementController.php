<?php

require_once __DIR__ . '/../models/ExitManagementModel.php';

class ExitManagementController
{
    protected ExitManagementModel $model;

    public function __construct()
    {
        $this->model = new ExitManagementModel();
    }

    /**
     * Return counts for pipeline stages to render a simple exit process pipeline
     */
    public function getExitPipeline(): array
    {
        try {
            $db = $this->model->getConnection();

            // Pending approvals (resignations + terminations)
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE status IN ('pending','pending_review','pending_legal_review')");
            $pendingResignations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            $pendingTerminations = 0;
            if ($this->model->tableExists('exit_terminations')) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_terminations WHERE status IN ('pending_review','pending_legal_review')");
                $pendingTerminations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            $pending = $pendingResignations + $pendingTerminations;

            // Approved (resignations + terminations)
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE status = 'approved'");
            $approvedResignations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            $approvedTerminations = 0;
            if ($this->model->tableExists('exit_terminations')) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_terminations WHERE status = 'approved'");
                $approvedTerminations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            }
            $approved = $approvedResignations + $approvedTerminations;

            // Interviews (unique cases that have scheduled or completed interviews)
            $stmt = $db->query("SELECT COUNT(DISTINCT CONCAT(IFNULL(exit_case_type,''), '-', IFNULL(exit_case_id,''))) AS count FROM exit_interviews WHERE status IN ('scheduled','completed','pending')");
            $interview = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

            // Knowledge transfer needed (use model helper)
            try {
                $kt = $this->model->getEmployeesNeedingKnowledgeTransfer();
                $knowledge_transfer = is_array($kt) ? count($kt) : 0;
            } catch (Exception $e) {
                $knowledge_transfer = 0;
            }

            // Documentation incomplete
            if ($this->model->columnExists('exit_resignations', 'documentation_complete')) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE documentation_complete = 0");
                $documentation = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            } else {
                $documentation = 0;
            }


            // Post-exit feedback pending
            try {
                $eligible = $this->model->getEligiblePostExitFeedbackCases();
                $post_exit_feedback = is_array($eligible) ? count($eligible) : 0;
            } catch (Exception $e) {
                $post_exit_feedback = 0;
            }

            return [
                'labels' => ['Pending Approval','Approved','Interview','Knowledge Transfer','Documentation','Post-Exit Feedback'],
                'data' => [$pending, $approved, $interview, $knowledge_transfer, $documentation, $post_exit_feedback]
            ];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Return upcoming exits within the specified number of days.
     */
    public function getUpcomingExits(int $days = 14, int $limit = 6): array
    {
        try {
            $db = $this->model->getConnection();
            $days = max(1, (int)$days);
            $limit = max(1, (int)$limit);

                        // Include both resignations and terminations (alias termination.effective_date as last_working_date)
                        $query = "SELECT r.id AS resignation_id, r.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS full_name, COALESCE(d.department_name, '') AS department, e.email, r.notice_date, r.last_working_date, DATEDIFF(r.last_working_date, CURDATE()) AS days_left, r.status, 'resignation' AS exit_type
                                            FROM exit_resignations r
                                            LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                                            LEFT JOIN em_departments d ON e.department_id = d.department_id
                                            WHERE r.last_working_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                                                AND r.status NOT IN ('archived','withdrawn','rejected','rejected_by_legal')
                                            
                                            UNION ALL

                                            SELECT t.id AS resignation_id, t.employee_id, CONCAT(e2.first_name, ' ', e2.last_name) AS full_name, COALESCE(d2.department_name, '') AS department, e2.email, NULL AS notice_date, t.effective_date AS last_working_date, DATEDIFF(t.effective_date, CURDATE()) AS days_left, t.status, 'termination' AS exit_type
                                            FROM exit_terminations t
                                            LEFT JOIN em_employees e2 ON t.employee_id = e2.employee_id
                                            LEFT JOIN em_departments d2 ON e2.department_id = d2.department_id
                                            WHERE t.effective_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                                                AND t.status NOT IN ('archived','withdrawn','rejected','rejected_by_legal')
                                            ORDER BY last_working_date ASC
                                            LIMIT {$limit}";

            $stmt = $db->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'data' => $rows,
                'total' => count($rows),
                'days' => $days
            ];
        } catch (Exception $e) {
            return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Aggregate upcoming and overdue exit activities from the active exit workflow tables.
     * This is a centralized HR reminder feed; module-specific logic remains in each model.
     */
    public function getUpcomingExitActivities(int $days = 30, int $limit = 20): array
    {
        try {
            $db = $this->model->getConnection();
            $days = max(7, (int)$days);
            $limit = max(1, (int)$limit);
            $activities = [];
            $seen = [];

            $addActivity = function (array $activity) use (&$activities, &$seen) {
                if (empty($activity['scheduled_date'])) {
                    return;
                }

                $employeeId = $activity['employee_id'] ?? null;
                $exitCaseType = $activity['exit_case_type'] ?? null;
                $exitCaseId = $activity['exit_case_id'] ?? null;
                $activityType = $activity['activity_type'] ?? 'alert';
                $dateValue = $activity['scheduled_date'];
                $key = md5((string)$employeeId . '|' . (string)$exitCaseType . '|' . (string)$exitCaseId . '|' . (string)$activityType . '|' . (string)$dateValue);
                if (isset($seen[$key])) {
                    return;
                }
                $seen[$key] = true;

                $status = trim((string)($activity['status'] ?? 'scheduled'));
                $daysRemaining = isset($activity['days_remaining']) ? (int)$activity['days_remaining'] : null;
                $priority = $activity['priority'] ?? $this->resolveActivityPriority($status, $daysRemaining, (bool)($activity['is_action_required'] ?? false));

                $activities[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $activity['employee_name'] ?? 'Unknown Employee',
                    'exit_case_type' => $exitCaseType,
                    'exit_case_id' => $exitCaseId,
                    'activity_type' => $activityType,
                    'activity_label' => $activity['activity_label'] ?? 'Exit activity',
                    'scheduled_date' => $dateValue,
                    'status' => $status ?: 'scheduled',
                    'priority' => $priority,
                    'days_remaining' => $daysRemaining,
                    'action' => $activity['action'] ?? null,
                    'is_action_required' => (bool)($activity['is_action_required'] ?? false),
                ];
            };

            $windowStart = date('Y-m-d', strtotime('-30 days'));
            $windowEnd = date('Y-m-d', strtotime('+' . $days . ' days'));

            $resignationSql = "SELECT
                r.id AS exit_case_id,
                r.employee_id,
                CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                'resignation' AS exit_case_type,
                r.last_working_date AS scheduled_date,
                r.status,
                DATEDIFF(r.last_working_date, CURDATE()) AS days_remaining,
                'resignation' AS activity_type,
                'Last Working Date' AS activity_label,
                'resignation_detail' AS action,
                0 AS is_action_required
            FROM exit_resignations r
            LEFT JOIN em_employees e ON e.employee_id = r.employee_id
            WHERE r.last_working_date IS NOT NULL
              AND r.status NOT IN ('archived', 'withdrawn', 'rejected', 'rejected_by_legal')
              AND r.last_working_date BETWEEN ? AND ?";
            $resignationStmt = $db->prepare($resignationSql);
            $resignationStmt->execute([$windowStart, $windowEnd]);
            foreach ($resignationStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $addActivity($row);
            }

            if ($this->model->tableExists('exit_terminations')) {
                $terminationSql = "SELECT
                    t.id AS exit_case_id,
                    t.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    'termination' AS exit_case_type,
                    t.effective_date AS scheduled_date,
                    t.status,
                    DATEDIFF(t.effective_date, CURDATE()) AS days_remaining,
                    'termination' AS activity_type,
                    'Termination Effective Date' AS activity_label,
                    'termination_detail' AS action,
                    0 AS is_action_required
                FROM exit_terminations t
                LEFT JOIN em_employees e ON e.employee_id = t.employee_id
                WHERE t.effective_date IS NOT NULL
                  AND t.status NOT IN ('archived', 'withdrawn', 'rejected', 'rejected_by_legal')
                  AND t.effective_date BETWEEN ? AND ?";
                $terminationStmt = $db->prepare($terminationSql);
                $terminationStmt->execute([$windowStart, $windowEnd]);
                foreach ($terminationStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_interviews') && $this->model->columnExists('exit_interviews', 'scheduled_date')) {
                $interviewSql = "SELECT
                    ei.id AS exit_case_id,
                    ei.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    COALESCE(ei.exit_case_type, 'resignation') AS exit_case_type,
                    ei.scheduled_date AS scheduled_date,
                    ei.status,
                    DATEDIFF(ei.scheduled_date, CURDATE()) AS days_remaining,
                    'interview' AS activity_type,
                    'Exit Interview' AS activity_label,
                    'interview_detail' AS action,
                    0 AS is_action_required
                FROM exit_interviews ei
                LEFT JOIN em_employees e ON e.employee_id = ei.employee_id
                WHERE ei.status IN ('scheduled', 'pending', 'in_progress')
                  AND ei.scheduled_date IS NOT NULL
                  AND ei.scheduled_date BETWEEN ? AND ?";
                $interviewStmt = $db->prepare($interviewSql);
                $interviewStmt->execute([$windowStart, $windowEnd]);
                foreach ($interviewStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_knowledge_transfer_plans') && $this->model->columnExists('exit_knowledge_transfer_plans', 'end_date')) {
                $ktSql = "SELECT
                    ktp.id AS exit_case_id,
                    ktp.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    'knowledge_transfer' AS exit_case_type,
                    ktp.end_date AS scheduled_date,
                    ktp.status,
                    DATEDIFF(ktp.end_date, CURDATE()) AS days_remaining,
                    'knowledge_transfer' AS activity_type,
                    'Knowledge Transfer' AS activity_label,
                    'knowledge_transfer_detail' AS action,
                    0 AS is_action_required
                FROM exit_knowledge_transfer_plans ktp
                LEFT JOIN em_employees e ON e.employee_id = ktp.employee_id
                WHERE ktp.status IN ('active', 'in_progress')
                  AND ktp.end_date IS NOT NULL
                  AND ktp.end_date BETWEEN ? AND ?";
                $ktStmt = $db->prepare($ktSql);
                $ktStmt->execute([$windowStart, $windowEnd]);
                foreach ($ktStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_employee_settlements') && $this->model->columnExists('exit_employee_settlements', 'settlement_date')) {
                                $settlementSql = "SELECT
                                        s.id AS exit_case_id,
                                        s.employee_id,
                                        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                                        COALESCE(s.exit_case_type, 'resignation') AS exit_case_type,
                                        COALESCE(s.settlement_date, s.requested_at, s.completed_at, r.last_working_date) AS scheduled_date,
                                        s.status,
                                        DATEDIFF(COALESCE(s.settlement_date, s.requested_at, s.completed_at, r.last_working_date), CURDATE()) AS days_remaining,
                                        'settlement' AS activity_type,
                                        'Settlement' AS activity_label,
                                        'settlement_detail' AS action,
                                        0 AS is_action_required
                                FROM exit_employee_settlements s
                                LEFT JOIN em_employees e ON e.employee_id = s.employee_id
                                LEFT JOIN exit_resignations r ON s.resignation_id = r.id OR s.exit_case_id = r.id
                                WHERE s.status IN ('pending', 'pending_approval', 'approved')
                                    AND COALESCE(s.settlement_date, s.requested_at, s.completed_at, r.last_working_date) IS NOT NULL
                                    AND COALESCE(s.settlement_date, s.requested_at, s.completed_at, r.last_working_date) BETWEEN ? AND ?";
                $settlementStmt = $db->prepare($settlementSql);
                $settlementStmt->execute([$windowStart, $windowEnd]);
                foreach ($settlementStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_surveys') && $this->model->columnExists('exit_surveys', 'scheduled_date')) {
                $surveySql = "SELECT
                    s.id AS exit_case_id,
                    s.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    COALESCE(s.exit_case_type, 'resignation') AS exit_case_type,
                    s.scheduled_date AS scheduled_date,
                    s.approval_status AS status,
                    DATEDIFF(s.scheduled_date, CURDATE()) AS days_remaining,
                    'survey' AS activity_type,
                    'Post-Exit Survey' AS activity_label,
                    'survey_detail' AS action,
                    0 AS is_action_required
                FROM exit_surveys s
                LEFT JOIN em_employees e ON e.employee_id = s.employee_id
                LEFT JOIN exit_resignations r ON s.exit_case_type = 'resignation' AND s.exit_case_id = r.id
                LEFT JOIN exit_terminations t ON s.exit_case_type = 'termination' AND s.exit_case_id = t.id
                WHERE s.approval_status IN ('draft', 'scheduled')
                  AND s.scheduled_date IS NOT NULL
                  AND s.scheduled_date BETWEEN ? AND ?
                  AND (
                    (s.exit_case_type = 'resignation' AND r.id IS NOT NULL AND r.status = 'approved')
                    OR (s.exit_case_type = 'termination' AND t.id IS NOT NULL AND t.status = 'approved')
                    OR (s.exit_case_type IS NULL)
                  )";
                $surveyStmt = $db->prepare($surveySql);
                $surveyStmt->execute([$windowStart, $windowEnd]);
                foreach ($surveyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_resignations') && $this->model->columnExists('exit_resignations', 'documentation_complete')) {
                $documentationSql = "SELECT
                    r.id AS exit_case_id,
                    r.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    'resignation' AS exit_case_type,
                    r.last_working_date AS scheduled_date,
                    r.status,
                    DATEDIFF(r.last_working_date, CURDATE()) AS days_remaining,
                    'documentation' AS activity_type,
                    'Documentation Incomplete' AS activity_label,
                    'documentation_detail' AS action,
                    1 AS is_action_required
                FROM exit_resignations r
                LEFT JOIN em_employees e ON e.employee_id = r.employee_id
                WHERE r.documentation_complete = 0
                  AND r.status NOT IN ('archived', 'withdrawn', 'rejected', 'rejected_by_legal')
                  AND r.last_working_date IS NOT NULL
                  AND r.last_working_date BETWEEN ? AND ?";
                $documentationStmt = $db->prepare($documentationSql);
                $documentationStmt->execute([$windowStart, $windowEnd]);
                foreach ($documentationStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            if ($this->model->tableExists('exit_terminations') && $this->model->columnExists('exit_terminations', 'documentation_complete')) {
                $documentationTerminationSql = "SELECT
                    t.id AS exit_case_id,
                    t.employee_id,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                    'termination' AS exit_case_type,
                    t.effective_date AS scheduled_date,
                    t.status,
                    DATEDIFF(t.effective_date, CURDATE()) AS days_remaining,
                    'documentation' AS activity_type,
                    'Documentation Incomplete' AS activity_label,
                    'documentation_detail' AS action,
                    1 AS is_action_required
                FROM exit_terminations t
                LEFT JOIN em_employees e ON e.employee_id = t.employee_id
                WHERE t.documentation_complete = 0
                  AND t.status NOT IN ('archived', 'withdrawn', 'rejected', 'rejected_by_legal')
                  AND t.effective_date IS NOT NULL
                  AND t.effective_date BETWEEN ? AND ?";
                $documentationTerminationStmt = $db->prepare($documentationTerminationSql);
                $documentationTerminationStmt->execute([$windowStart, $windowEnd]);
                foreach ($documentationTerminationStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $addActivity($row);
                }
            }

            usort($activities, function (array $a, array $b) {
                $priorityRank = ['URGENT' => 0, 'DUE SOON' => 1, 'UPCOMING' => 2, 'SCHEDULED' => 3, 'ACTION REQUIRED' => 4];
                $aPriority = strtoupper((string)($a['priority'] ?? 'SCHEDULED'));
                $bPriority = strtoupper((string)($b['priority'] ?? 'SCHEDULED'));

                $aRank = $priorityRank[$aPriority] ?? 99;
                $bRank = $priorityRank[$bPriority] ?? 99;
                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }

                $aDate = $a['scheduled_date'] ?? '9999-12-31';
                $bDate = $b['scheduled_date'] ?? '9999-12-31';
                return strcmp($aDate, $bDate);
            });

            $summary = [
                'total' => count($activities),
                'urgent' => 0,
                'due_soon' => 0,
                'upcoming' => 0,
                'action_required' => 0,
            ];

            foreach ($activities as $activity) {
                $priority = strtoupper((string)($activity['priority'] ?? 'SCHEDULED'));
                if ($priority === 'URGENT') {
                    $summary['urgent']++;
                } elseif ($priority === 'DUE SOON') {
                    $summary['due_soon']++;
                } elseif ($priority === 'UPCOMING') {
                    $summary['upcoming']++;
                } elseif ($priority === 'ACTION REQUIRED') {
                    $summary['action_required']++;
                }
            }

            return [
                'data' => array_slice($activities, 0, $limit),
                'total' => count($activities),
                'summary' => $summary,
                'days' => $days,
            ];
        } catch (Exception $e) {
            return ['data' => [], 'total' => 0, 'summary' => ['total' => 0, 'urgent' => 0, 'due_soon' => 0, 'upcoming' => 0, 'action_required' => 0], 'days' => $days, 'error' => $e->getMessage()];
        }
    }

    private function resolveActivityPriority(string $status, ?int $daysRemaining, bool $actionRequired = false): string
    {
        if ($actionRequired) {
            return 'ACTION REQUIRED';
        }

        $normalizedStatus = strtolower(trim($status));
        if ($normalizedStatus === 'overdue' || $daysRemaining === null) {
            return 'SCHEDULED';
        }

        if ($daysRemaining <= 0) {
            return 'URGENT';
        }

        if ($daysRemaining <= 3) {
            return 'DUE SOON';
        }

        if ($daysRemaining <= 7) {
            return 'UPCOMING';
        }

        return 'SCHEDULED';
    }

    /**
     * Aggregate actionable items for the Action Required list
     */
    public function getActionItems(): array
    {
        try {
            $db = $this->model->getConnection();
            $items = [];

            // Pending resignation approvals
            try {
                $stmt = $db->query("SELECT id, employee_id, DATEDIFF(CURDATE(), notice_date) AS days_since_notice, last_working_date, reason FROM exit_resignations WHERE status IN ('pending_review','pending_legal_review') ORDER BY created_at DESC LIMIT 20");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $items[] = [
                        'type' => 'resignation_approval',
                        'id' => $r['id'],
                        'employee_id' => $r['employee_id'],
                        'label' => 'Resignation approval',
                        'meta' => $r,
                        'priority' => 1
                    ];
                }
            } catch (Exception $e) {
                error_log('ExitManagementController::getActionItems pending resignation approvals query failed: ' . $e->getMessage());
            }

            // Pending termination reviews (mirrors pending resignation approvals)
            try {
                $stmt = $db->query("SELECT id, employee_id, termination_reason, effective_date, status, DATEDIFF(CURDATE(), effective_date) AS days_overdue FROM exit_terminations WHERE status IN ('pending_review','pending_legal_review') ORDER BY created_at DESC LIMIT 20");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
                    $items[] = [
                        'type' => 'termination_pending',
                        'id' => $t['id'],
                        'employee_id' => $t['employee_id'],
                        'label' => 'Termination review',
                        'meta' => $t,
                        'priority' => 1
                    ];
                }
            } catch (Exception $e) {
                error_log('getActionItems: pending termination reviews query failed: ' . $e->getMessage());
            }

            // Approved terminations whose effective date has already passed (overdue, needs finalizing)
            try {
                $stmt = $db->query("SELECT id, employee_id, termination_reason, effective_date, status, DATEDIFF(CURDATE(), effective_date) AS days_overdue FROM exit_terminations WHERE status = 'approved' AND effective_date <= CURDATE() ORDER BY effective_date ASC LIMIT 20");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
                    $items[] = [
                        'type' => 'termination_overdue',
                        'id' => $t['id'],
                        'employee_id' => $t['employee_id'],
                        'label' => 'Termination overdue',
                        'meta' => $t,
                        'priority' => 1
                    ];
                }
            } catch (Exception $e) {
                error_log('getActionItems: overdue terminations query failed: ' . $e->getMessage());
            }

            // Interviews scheduled but not completed (pending action)
            // Use verified column `scheduled_date` only
            $stmt = $db->query("SELECT ei.id AS interview_id, ei.employee_id, ei.scheduled_date AS scheduled_at, ei.status, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM exit_interviews ei LEFT JOIN em_employees e ON ei.employee_id = e.employee_id WHERE ei.status = 'scheduled' ORDER BY ei.scheduled_date ASC LIMIT 20");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $i) {
                $items[] = [
                    'type' => 'interview_scheduled',
                    'id' => $i['interview_id'],
                    'employee_id' => $i['employee_id'],
                    'label' => 'Interview scheduled',
                    'meta' => $i,
                    'priority' => 2
                ];
            }

            // Knowledge transfer required but no active plan
            try {
                $kt = $this->model->getEmployeesNeedingKnowledgeTransfer();
                foreach ($kt as $k) {
                    $items[] = [
                        'type' => 'knowledge_transfer_required',
                        'id' => $k['id'] ?? null,
                        'employee_id' => $k['id'] ?? null,
                        'label' => 'Knowledge transfer required',
                        'meta' => $k,
                        'priority' => 3
                    ];
                }
            } catch (Exception $e) {}

            // Settlements pending approval — use verified settlement columns
            if ($this->model->tableExists('exit_employee_settlements')) {
                $stmt = $db->query("SELECT settlement_id AS id, employee_id, exit_case_type, exit_case_id, status, requested_at, completed_at, created_at FROM exit_employee_settlements WHERE status IN ('pending_approval','pending') ORDER BY created_at ASC LIMIT 20");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                    $items[] = [
                        'type' => 'settlement_pending',
                        'id' => $s['id'],
                        'employee_id' => $s['employee_id'],
                        'label' => 'Settlement pending',
                        'meta' => $s,
                        'priority' => 2
                    ];
                }
            }

            // Documentation incomplete
            if ($this->model->columnExists('exit_resignations', 'documentation_complete')) {
                $stmt = $db->query("SELECT id, employee_id, last_working_date FROM exit_resignations WHERE documentation_complete = 0 ORDER BY last_working_date ASC LIMIT 20");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
                    $items[] = [
                        'type' => 'documentation_incomplete',
                        'id' => $d['id'],
                        'employee_id' => $d['employee_id'],
                        'label' => 'Documentation incomplete',
                        'meta' => $d,
                        'priority' => 4
                    ];
                }
            }

            // Post-exit feedback to schedule
            try {
                $eligible = $this->model->getEligiblePostExitFeedbackCases();
                foreach ($eligible as $eCase) {
                    $items[] = [
                        'type' => 'post_exit_schedule',
                        'id' => $eCase['exit_case_id'] ?? null,
                        'employee_id' => $eCase['employee_id'] ?? null,
                        'label' => 'Schedule post-exit survey',
                        'meta' => $eCase,
                        'priority' => 5
                    ];
                }
            } catch (Exception $e) {}

            // Return sorted by priority then recent
            usort($items, function($a, $b) {
                if (($a['priority'] ?? 0) !== ($b['priority'] ?? 0)) {
                    return ($a['priority'] ?? 0) - ($b['priority'] ?? 0);
                }
                return 0;
            });

            return ['data' => array_slice($items, 0, 30), 'total' => count($items)];
        } catch (Exception $e) {
            return ['data' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        try {
            // Query actual data from database
            $db = $this->model->getConnection();

            // Count pending resignations
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE status = 'pending'");
            $pendingResignations = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Count scheduled interviews
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_interviews WHERE status = 'scheduled'");
            $scheduledInterviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Count active transfers
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_knowledge_transfer_plans WHERE status = 'active'");
            $activeTransfers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Count pending settlements
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_employee_settlements WHERE status = 'pending_approval'");
            $pendingSettlements = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Count total active employees (the managed workforce, not system login accounts)
            $stmt = $db->query("SELECT COUNT(*) as count FROM em_employees WHERE employment_status = 'Active'");
            $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Active exit cases (not archived/withdrawn/rejected)
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE status NOT IN ('archived','withdrawn','rejected','rejected_by_legal')");
                $activeExitCases = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $activeExitCases = 0;
            }

            // Upcoming exits in next 14 days
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE last_working_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)");
                $upcomingExits = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $upcomingExits = 0;
            }

            // Documentation incomplete: check column if available
            try {
                if ($this->model->columnExists('exit_resignations', 'documentation_complete')) {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE documentation_complete = 0");
                    $documentationIncomplete = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                } else {
                    $documentationIncomplete = 0;
                }
            } catch (Exception $e) {
                $documentationIncomplete = 0;
            }

            // Post-exit feedback pending (use model eligibility check)
            try {
                $eligible = $this->model->getEligiblePostExitFeedbackCases();
                $postExitFeedbackPending = is_array($eligible) ? count($eligible) : 0;
            } catch (Exception $e) {
                $postExitFeedbackPending = 0;
            }

            // Count payroll pre-clearance approvals (settlements approved)
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM exit_employee_settlements WHERE status = 'approved'");
                $approvedPreclearances = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $approvedPreclearances = 0;
            }

            // Calculate interviews completed percentage
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM exit_interviews");
                $totalInterviews = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                $stmt = $db->query("SELECT COUNT(*) as completed FROM exit_interviews WHERE status = 'completed'");
                $completedInterviews = (int)($stmt->fetch(PDO::FETCH_ASSOC)['completed'] ?? 0);
                $interviewsCompletedPercent = $totalInterviews > 0 ? round(($completedInterviews / $totalInterviews) * 100) : 0;
            } catch (Exception $e) {
                $interviewsCompletedPercent = 0;
            }

            return [
                'total_employees' => $totalEmployees,
                'pending_resignations' => $pendingResignations,
                'scheduled_interviews' => $scheduledInterviews,
                'active_transfers' => $activeTransfers,
                'pending_settlements' => $pendingSettlements,
                'incomplete_documentation' => 0,
                'approved_preclearances' => $approvedPreclearances,
                'interviews_completed_percent' => $interviewsCompletedPercent
                ,'active_exit_cases' => $activeExitCases
                ,'upcoming_exits' => $upcomingExits
                ,'documentation_incomplete' => $documentationIncomplete
                ,'post_exit_feedback_pending' => $postExitFeedbackPending
            ];
        } catch (Exception $e) {
            // Return default stats if query fails
            return [
                'total_employees' => 0,
                'pending_resignations' => 0,
                'scheduled_interviews' => 0,
                'active_transfers' => 0,
                'pending_settlements' => 0,
                'incomplete_documentation' => 0
            ];
        }
    }

    /**
     * Get employee exit summary
     */
    public function getEmployeeExitSummary(int $employeeId): array
    {
        $employee = $this->model->getEmployeeById($employeeId);

        if (!$employee) {
            return ['error' => 'Employee not found'];
        }

        return [
            'employee' => $employee,
            'resignations' => [], // Would be populated from ResignationModel
            'interviews' => [], // Would be populated from ExitInterviewModel
            'transfers' => [], // Would be populated from KnowledgeTransferModel
            'settlements' => [], // Would be populated from SettlementModel
            'documents' => [], // Would be populated from DocumentationModel
            'surveys' => [] // Would be populated from SurveyModel
        ];
    }

    /**
     * Get eligible employees for exit management
     */
    public function getEligibleEmployees(): array
    {
        return $this->model->getEligibleEmployees();
    }

    /**
     * Get recent resignations
     */
    public function getRecentResignations(int $limit = 10): array
    {
        try {
            $db = $this->model->getConnection();
                 $query = "SELECT r.*, 
                         CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                         COALESCE(d.department_name, '') AS department,
                         e.email AS employee_email,
                         CONCAT(p.first_name, ' ', p.last_name) AS preclearance_desk_person_name,
                         DATEDIFF(r.last_working_date, CURDATE()) AS days_left
                     FROM exit_resignations r
                     LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                     LEFT JOIN em_departments d ON e.department_id = d.department_id
                     LEFT JOIN em_employees p ON r.preclearance_desk_person = p.employee_id
                     ORDER BY r.created_at DESC -- Default sorting: newest first
                     LIMIT ?";

            $stmt = $db->prepare($query);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            // Fallback for databases that do not have preclearance_desk_person
            try {
                  $query = "SELECT r.*, 
                             CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                             COALESCE(d.department_name, '') AS department,
                             e.email,
                             DATEDIFF(r.last_working_date, CURDATE()) AS days_left
                         FROM exit_resignations r
                         LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                         LEFT JOIN em_departments d ON e.department_id = d.department_id
                         ORDER BY r.created_at DESC -- Default sorting: newest first
                         LIMIT ?";

                // ensure we have a DB connection in fallback
                $db = $this->model->getConnection();

                $stmt = $db->prepare($query);
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                // add placeholder for preclearance
                foreach ($results as &$row) {
                    if (!isset($row['preclearance_desk_person_name'])) {
                        $row['preclearance_desk_person_name'] = null;
                    }

                    if (!isset($row['full_name']) && isset($row['employee_name'])) {
                        $row['full_name'] = $row['employee_name'];
                    }

                    if (!isset($row['department']) && isset($row['department_id'])) {
                        $row['department'] = $row['department_id'];
                    }
                }

                return $results;
            } catch (Exception $e2) {
                error_log('ExitManagementController::getRecentResignations error: ' . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Aggregate recent and active cases + feedback summaries for dashboard
     */
    public function getRecentActiveCases(int $limit = 8): array
    {
        try {
            $db = $this->model->getConnection();

            $recent = [];

            // Recent resignations
            $recentResignations = $this->getRecentResignations($limit);
            $recent['recent_resignations'] = is_array($recentResignations) ? $recentResignations : [];

            // Recent terminations
            $terminations = [];
            try {
                $stmt = $db->query("SELECT t.id, t.employee_id, t.status, t.effective_date, t.created_at, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM exit_terminations t LEFT JOIN em_employees e ON t.employee_id = e.employee_id ORDER BY t.updated_at DESC LIMIT {$limit}");
                $terminations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $terminations = [];
            }
            $recent['recent_terminations'] = $terminations;

            // Recent interviews (scheduled or in_progress)
            $interviews = [];
            try {
                $stmt = $db->query("SELECT ei.id AS interview_id, ei.employee_id, COALESCE(ei.scheduled_date, ei.scheduled_at) AS scheduled_at, ei.status, CONCAT(e.first_name, ' ', e.last_name) AS full_name FROM exit_interviews ei LEFT JOIN em_employees e ON ei.employee_id = e.employee_id WHERE ei.status IN ('scheduled','in_progress') ORDER BY COALESCE(ei.scheduled_date, ei.scheduled_at) DESC LIMIT {$limit}");
                $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $interviews = [];
            }
            $recent['recent_interviews'] = $interviews;

            // Recent feedback / surveys (attempt common table names)
            $feedback = [];
            try {
                if ($this->model->tableExists('post_exit_surveys')) {
                    $stmt = $db->query("SELECT id, exit_case_id, employee_id, status, created_at FROM post_exit_surveys ORDER BY created_at DESC LIMIT {$limit}");
                    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } elseif ($this->model->tableExists('survey_responses')) {
                    $stmt = $db->query("SELECT id, survey_id, responder_id AS employee_id, created_at, score FROM survey_responses ORDER BY created_at DESC LIMIT {$limit}");
                    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } else {
                    $feedback = [];
                }
            } catch (Exception $e) {
                $feedback = [];
            }
            $recent['recent_feedback'] = $feedback;

            return $recent;
        } catch (Exception $e) {
            return ['recent_resignations' => [], 'recent_interviews' => [], 'recent_feedback' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get resignation trend data (last 6 months)
     */
    public function getResignationTrend(): array
    {
        try {
            $db = $this->model->getConnection();
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as count
                      FROM exit_resignations
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month";
            
            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $months = [];
            $counts = [];
            foreach ($results as $row) {
                $months[] = $row['month'];
                $counts[] = (int)$row['count'];
            }
            
            return [
                'labels' => $months,
                'data' => $counts
            ];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get resignation reasons distribution
     */
    public function getResignationReasons(): array
    {
        try {
            $db = $this->model->getConnection();
            $query = "SELECT reason, COUNT(*) as count
                      FROM exit_resignations
                      WHERE reason IS NOT NULL AND reason != ''
                      GROUP BY reason
                      ORDER BY count DESC";
            
            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reasons = [];
            $counts = [];
            foreach ($results as $row) {
                $reasons[] = $row['reason'];
                $counts[] = (int)$row['count'];
            }
            
            return [
                'labels' => $reasons,
                'data' => $counts
            ];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get exit status distribution
     */
    public function getExitStatusDistribution(): array
    {
        try {
            $db = $this->model->getConnection();
            // Group exits by the employee's department to show which departments
            // have the most exit cases. Use LEFT JOIN in case employee record
            // is missing; fallback to 'Unknown'.
            $query = "SELECT COALESCE(d.department_name, 'Unknown') AS department, COUNT(*) AS count
                      FROM exit_resignations r
                      LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                      LEFT JOIN em_departments d ON e.department_id = d.department_id
                      GROUP BY department
                      ORDER BY count DESC";

            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $departments = [];
            $counts = [];
            foreach ($results as $row) {
                $departments[] = $row['department'] ?? 'Unknown';
                $counts[] = (int)$row['count'];
            }

            return [
                'labels' => $departments,
                'data' => $counts
            ];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get approved payroll clearance notifications
     */
    public function getPayrollClearanceNotifications(): array
    {
        try {
            $db = $this->model->getConnection();

            $countStmt = $db->query("SELECT COUNT(*) AS count FROM payroll_clearances WHERE status = 'approved' AND approved_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $notificationCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            $detailsStmt = $db->prepare(
                "SELECT pc.id,
                        pc.settlement_id,
                        pc.approved_at,
                        pc.comments,
                        CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                        COALESCE(s.settlement_date, s.requested_at, s.completed_at) AS settlement_date,
                        s.net_payable
                 FROM payroll_clearances pc
                 LEFT JOIN exit_employee_settlements s ON pc.settlement_id = s.id
                 LEFT JOIN em_employees e ON s.employee_id = e.employee_id
                 WHERE pc.status = 'approved'
                   AND pc.approved_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY pc.approved_at DESC
                 LIMIT 5"
            );
            $detailsStmt->execute();
            $notifications = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'count' => $notificationCount,
                'notifications' => $notifications
            ];
        } catch (Exception $e) {
            return ['count' => 0, 'notifications' => []];
        }
    }

    /**
     * Get resignation type distribution
     */
    public function getResignationTypeDistribution(): array
    {
        try {
            $db = $this->model->getConnection();
            // If the schema does not include resignation_type, return empty distribution
            if (!$this->model->columnExists('exit_resignations', 'resignation_type')) {
                return ['labels' => [], 'data' => []];
            }

            $query = "SELECT resignation_type, COUNT(*) as count
                      FROM exit_resignations
                      WHERE resignation_type IS NOT NULL AND resignation_type != ''
                      GROUP BY resignation_type
                      ORDER BY count DESC";

            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $types = [];
            $counts = [];
            foreach ($results as $row) {
                $types[] = ucfirst($row['resignation_type']);
                $counts[] = (int)$row['count'];
            }
            
            return [
                'labels' => $types,
                'data' => $counts
            ];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Return both exit status distribution and department distribution
     */
    public function getExitStatusAndDepartment(): array
    {
        try {
            $db = $this->model->getConnection();

            // Status distribution
            $statusStmt = $db->query("SELECT status, COUNT(*) as count FROM exit_resignations GROUP BY status");
            $statusResults = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            $statuses = [];
            $statusCounts = [];
            foreach ($statusResults as $row) {
                $statuses[] = isset($row['status']) ? ucwords(str_replace('_', ' ', $row['status'])) : '';
                $statusCounts[] = (int)$row['count'];
            }

            // Department distribution (reuse department grouping)
            $deptStmt = $db->query("SELECT COALESCE(d.department_name, 'Unknown') AS department, COUNT(*) AS count
                                      FROM exit_resignations r
                                      LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                                      LEFT JOIN em_departments d ON e.department_id = d.department_id
                                      GROUP BY department
                                      ORDER BY count DESC");
            $deptResults = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
            $departments = [];
            $deptCounts = [];
            foreach ($deptResults as $row) {
                $departments[] = $row['department'] ?? 'Unknown';
                $deptCounts[] = (int)$row['count'];
            }

            return [
                'status' => ['labels' => $statuses, 'data' => $statusCounts],
                'department' => ['labels' => $departments, 'data' => $deptCounts]
            ];
        } catch (Exception $e) {
            return ['status' => ['labels' => [], 'data' => []], 'department' => ['labels' => [], 'data' => []]];
        }
    }

    /**
     * Debug: return first 20 exit_resignations joined with employee info
     */
    public function getExitJoinedSample(): array
    {
        try {
            $db = $this->model->getConnection();
            $stmt = $db->prepare(
                "SELECT r.*, COALESCE(d.department_name, 'Unknown') AS department, CONCAT(e.first_name, ' ', e.last_name) AS full_name
                 FROM exit_resignations r
                 LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                 LEFT JOIN em_departments d ON e.department_id = d.department_id
                 ORDER BY r.id DESC
                 LIMIT 20"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['rows' => $rows];
        } catch (Exception $e) {
            return ['rows' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get termination trend (last 6 months)
     */
    public function getTerminationTrend(): array
    {
        try {
            $db = $this->model->getConnection();
            $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                      FROM exit_terminations
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month";

            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $months = [];
            $counts = [];
            foreach ($results as $row) {
                $months[] = $row['month'];
                $counts[] = (int)$row['count'];
            }

            return ['labels' => $months, 'data' => $counts];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get termination status distribution
     */
    public function getTerminationStatusDistribution(): array
    {
        try {
            $db = $this->model->getConnection();
            $query = "SELECT status, COUNT(*) as count FROM exit_terminations GROUP BY status";
            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $statuses = [];
            $counts = [];
            foreach ($results as $row) {
                $statuses[] = isset($row['status']) ? ucwords(str_replace('_', ' ', $row['status'])) : '';
                $counts[] = (int)$row['count'];
            }

            return ['labels' => $statuses, 'data' => $counts];
        } catch (Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        try {
            $db = $this->model->getConnection();
            
            // Total exited this year
            $stmt = $db->query("SELECT COUNT(*) as count FROM exit_resignations WHERE YEAR(last_working_date) = YEAR(NOW())");
            $totalExited = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Average notice period
            $stmt = $db->query("SELECT AVG(DATEDIFF(last_working_date, notice_date)) as avg_days FROM exit_resignations WHERE last_working_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $avgNotice = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_days'] ?? 0);
            
            // Top resignation reason
            $stmt = $db->query("SELECT reason FROM exit_resignations WHERE reason IS NOT NULL AND reason != '' GROUP BY reason ORDER BY COUNT(*) DESC LIMIT 1");
            $topReason = $stmt->fetch(PDO::FETCH_ASSOC)['reason'] ?? 'N/A';
            
            // Interviews completion rate
            $stmt = $db->query("SELECT COUNT(DISTINCT r.id) as total FROM exit_resignations r WHERE r.status IN ('approved', 'completed')");
            $totalResignations = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(DISTINCT ei.employee_id) as count FROM exit_interviews ei WHERE ei.status IN ('completed', 'scheduled')");
            $completedInterviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $interviewRate = $totalResignations > 0 ? round(($completedInterviews / $totalResignations) * 100) : 0;
            
            return [
                'total_exited' => $totalExited,
                'avg_notice' => $avgNotice,
                'top_reason' => $topReason,
                'interview_rate' => $interviewRate
            ];
        } catch (Exception $e) {
            return [
                'total_exited' => 0,
                'avg_notice' => 0,
                'top_reason' => 'N/A',
                'interview_rate' => 0
            ];
        }
    }

    /**
     * Handle AJAX requests
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        try {
            switch ($action) {
                case 'get_exit_case_documentation_list':
                    $status = $data['status'] ?? 'all';
                    $page = (int)($data['page'] ?? 1);
                    $limit = (int)($data['limit'] ?? 10);
                    $search = $data['search'] ?? '';

                    $listResult = $this->model->getExitCaseDocumentationList($status, $page, $limit, $search);

                    // Try to augment each case with documents_check (progress) when possible
                    try {
                        require_once __DIR__ . '/../models/DocumentationModel.php';
                        $docModel = new DocumentationModel();
                        if (!empty($listResult['data']) && is_array($listResult['data'])) {
                            foreach ($listResult['data'] as &$caseRow) {
                                try {
                                    $empId = (int)($caseRow['employee_id'] ?? 0);
                                    $caseType = $caseRow['exit_case_type'] ?? '';
                                    $caseId = (int)($caseRow['exit_case_id'] ?? 0);
                                    if (!empty($caseType) && $caseId > 0) {
                                        // compute exit-case progress across canonical 5 steps
                                        $caseRow['documents_check'] = $docModel->computeExitCaseProgress($caseType, $caseId, $empId);
                                    } elseif ($empId > 0) {
                                        // fallback to legacy per-employee doc check
                                        $caseRow['documents_check'] = $docModel->checkRequiredDocuments($empId);
                                    } else {
                                        $caseRow['documents_check'] = null;
                                    }
                                } catch (Exception $e) {
                                    $caseRow['documents_check'] = null;
                                }
                            }
                            unset($caseRow);
                        }
                    } catch (Exception $e) {
                        // Non-fatal: if DocumentationModel cannot be loaded, leave listResult untouched
                        error_log('Failed to augment documentation list with documents_check: ' . $e->getMessage());
                    }

                    return $listResult;

                case 'get_exit_case_documentation':
                    $exitCaseType = $data['exit_case_type'] ?? '';
                    $exitCaseId = (int)($data['exit_case_id'] ?? 0);

                    if (empty($exitCaseType) || empty($exitCaseId)) {
                        error_log('get_exit_case_documentation missing params: ' . json_encode($data));
                        return ['success' => false, 'message' => 'exit_case_type and exit_case_id are required'];
                    }

                    error_log("get_exit_case_documentation called with type={$exitCaseType}, id={$exitCaseId}");
                    // Load core case details
                    $case = $this->model->getExitCaseDetails($exitCaseType, $exitCaseId);
                    if (!$case) {
                        error_log("Exit case not found: type={$exitCaseType}, id={$exitCaseId}");
                        return ['success' => false, 'message' => 'Exit case not found'];
                    }

                    // Load related records: documents, interview, transfer plans, settlement
                    require_once __DIR__ . '/../models/DocumentationModel.php';
                    require_once __DIR__ . '/../models/ExitInterviewModel.php';
                    require_once __DIR__ . '/../models/KnowledgeTransferModel.php';
                    require_once __DIR__ . '/../models/SettlementModel.php';

                    $docModel = new DocumentationModel();
                    $interviewModel = new ExitInterviewModel();
                    $transferModel = new KnowledgeTransferModel();

                    $documents = $docModel->getDocumentsByExitCase($exitCaseType, $exitCaseId);

                    // Compute required documents progress for this employee (if available)
                    $documentsCheck = null;
                    try {
                        if (!empty($case['employee_id'])) {
                            $documentsCheck = $docModel->checkRequiredDocuments((int)$case['employee_id']);
                        }
                    } catch (Exception $e) {
                        error_log('checkRequiredDocuments failed: ' . $e->getMessage());
                        $documentsCheck = null;
                    }

                    // Detect whether this installation supports per-case document linking
                    $documentsSupported = $docModel->columnExists('exit_documents', 'exit_case_type') && $docModel->columnExists('exit_documents', 'exit_case_id');

                    error_log('Documents supported in schema: ' . ($documentsSupported ? 'yes' : 'no'));
                    error_log('Documents found: ' . count($documents));

                    // Try to get the interview linked to the case (return latest)
                    $hasInterviewSchema = $this->model->tableExists('exit_interviews')
                        && $this->model->columnExists('exit_interviews', 'exit_case_type')
                        && $this->model->columnExists('exit_interviews', 'exit_case_id');

                    if ($hasInterviewSchema) {
                        $stmt = $this->model->getConnection()->prepare("SELECT * FROM exit_interviews WHERE exit_case_type = ? AND exit_case_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$exitCaseType, $exitCaseId]);
                        $exitInterview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    } else {
                        error_log('Exit interview query skipped: missing exit_interviews schema or case linkage columns');
                        $exitInterview = null;
                    }
                    error_log('Exit interview found: ' . ($exitInterview ? 'yes' : 'no'));

                    // Knowledge transfer: pick most recent plan for the employee
                    $transferPlans = $transferModel->getTransferPlansByEmployee($case['employee_id']);
                    $knowledgeTransfer = count($transferPlans) ? $transferPlans[0] : null;

                    // Settlement: attempt to find settlement by exit_case_type/exit_case_id
                    $settlement = null;
                    $hasSettlementSchema = $this->model->tableExists('exit_employee_settlements')
                        && $this->model->columnExists('exit_employee_settlements', 'exit_case_type')
                        && $this->model->columnExists('exit_employee_settlements', 'exit_case_id');

                    if ($hasSettlementSchema) {
                        $stmt2 = $this->model->getConnection()->prepare("SELECT * FROM exit_employee_settlements WHERE exit_case_type = ? AND exit_case_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt2->execute([$exitCaseType, $exitCaseId]);
                        $settlement = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
                    }

                    if (!$settlement && $this->model->tableExists('exit_employee_settlements') && $exitCaseType === 'resignation' && $this->model->columnExists('exit_employee_settlements', 'resignation_id')) {
                        $stmt2 = $this->model->getConnection()->prepare("SELECT * FROM exit_employee_settlements WHERE resignation_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt2->execute([$exitCaseId]);
                        $settlement = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
                        error_log('Settlement found by resignation_id fallback: ' . ($settlement ? 'yes' : 'no'));
                    }

                    if (!$settlement && !empty($case['employee_id']) && $this->model->tableExists('exit_employee_settlements') && $this->model->columnExists('exit_employee_settlements', 'employee_id')) {
                        $stmt2 = $this->model->getConnection()->prepare("SELECT * FROM exit_employee_settlements WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt2->execute([$case['employee_id']]);
                        $settlement = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
                        error_log('Settlement found by employee_id fallback: ' . ($settlement ? 'yes' : 'no'));
                    }

                    if (!$settlement && !$hasSettlementSchema) {
                        error_log('Settlement query skipped: missing exit_employee_settlements schema or case linkage columns');
                    }
                    error_log('Settlement found: ' . ($settlement ? 'yes' : 'no'));

                    return [
                        'success' => true,
                        'data' => $case,
                        'documents_supported' => $documentsSupported,
                        'documents' => $documents,
                        'documents_check' => $documentsCheck,
                        'exit_interview' => $exitInterview,
                        'knowledge_transfer' => $knowledgeTransfer,
                        'settlement' => $settlement
                    ];
                case 'get_employee_details':
                    return $this->getEmployeeExitSummary($data['employee_id'] ?? 0);

                case 'get_dashboard_stats':
                    return $this->getDashboardStats();

                case 'get_eligible_employees':
                    return $this->getEligibleEmployees();

                case 'get_employees_with_resignations':
                    return $this->model->getEmployeesWithResignations();

                case 'get_employees_needing_knowledge_transfer':
                    return $this->model->getEmployeesNeedingKnowledgeTransfer();

                case 'get_approved_exit_cases':
                    return $this->model->getApprovedExitCases();
                case 'get_waiting_interview_cases':
                    return $this->model->getApprovedCasesAwaitingInterview();
                case 'get_eligible_post_exit_cases':
                    return $this->model->getEligiblePostExitFeedbackCases();
                case 'get_active_exit_cases':
                    return $this->model->getActiveExitCases();

                case 'get_employee_salary_components':
                    return $this->model->getEmployeeSalaryComponents($data['employee_id'] ?? '');

                case 'get_eligible_interviewers':
                    return $this->model->getEligibleInterviewers();

                case 'get_payroll_clearance_notifications':
                    return $this->getPayrollClearanceNotifications();

                case 'get_recent_resignations':
                    return $this->getRecentResignations($data['limit'] ?? 10);

                case 'get_resignation_trend':
                    return $this->getResignationTrend();

                case 'get_resignation_reasons':
                    return $this->getResignationReasons();

                case 'get_exit_status':
                    return $this->getExitStatusDistribution();

                case 'get_exit_status_and_department':
                    return $this->getExitStatusAndDepartment();

                case 'get_exit_pipeline':
                    return $this->getExitPipeline();

                case 'get_upcoming_exits':
                    $days = isset($data['days']) ? (int)$data['days'] : 14;
                    $limit = isset($data['limit']) ? (int)$data['limit'] : 6;
                    return $this->getUpcomingExits($days, $limit);

                case 'get_upcoming_exit_activities':
                    $days = isset($data['days']) ? (int)$data['days'] : 14;
                    $limit = isset($data['limit']) ? (int)$data['limit'] : 8;
                    return $this->getUpcomingExitActivities($days, $limit);

                case 'get_action_items':
                    return $this->getActionItems();

                case 'debug_get_exit_joined':
                    return $this->getExitJoinedSample();

                case 'get_resignation_types':
                    return $this->getResignationTypeDistribution();

                case 'get_termination_trend':
                    return $this->getTerminationTrend();

                case 'get_recent_active_cases':
                    $limit = isset($data['limit']) ? (int)$data['limit'] : 8;
                    return $this->getRecentActiveCases($limit);

                case 'get_termination_status':
                    return $this->getTerminationStatusDistribution();

                case 'get_dashboard_metrics':
                    return $this->getDashboardMetrics();

                default:
                    return ['error' => 'Unknown action'];
            }
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}