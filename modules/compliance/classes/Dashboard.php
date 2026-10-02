<?php

require_once __DIR__ . '/../../../database/db.php';

class Dashboard
{
    private $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getTotalEmployees()
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM em_employees WHERE employment_status = 'Active'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getComplianceHealthScore()
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            $stmt = $this->conn->query("SELECT AVG(overall_score) FROM lc_compliance_summary WHERE overall_score IS NOT NULL");
            $score = $stmt->fetchColumn();
            return $score !== false ? (int) round($score) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getRiskCounts()
    {
        try {
            if (!$this->conn) {
                return ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
            }
            $sql = "SELECT severity, COUNT(*) as cnt FROM lc_risks WHERE archived = 0 GROUP BY severity";
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return [
                'critical' => (int) ($rows['Critical'] ?? 0),
                'high'     => (int) ($rows['High'] ?? 0),
                'medium'   => (int) ($rows['Medium'] ?? 0),
                'low'      => (int) ($rows['Low'] ?? 0),
            ];
        } catch (Exception $e) {
            return ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        }
    }

    public function getOpenIncidents()
    {
        try {
            if (!$this->conn) {
                return 0;
            }return (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getCriticalOpen()
    {
        try {
            if (!$this->conn) {
                return 0;
            }return (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE severity = 'Critical' AND status NOT IN ('resolved', 'closed')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getDocumentStats()
    {
        try {
            if (!$this->conn) {
                return ['total' => 0, 'valid' => 0, 'rate' => 0, 'expiring30' => 0, 'expiring60' => 0, 'expiring90' => 0, 'expired' => 0];
            }
            $total      = (int) $this->conn->query("SELECT COUNT(*) FROM em_documents")->fetchColumn();
            $expired    = (int) $this->conn->query("
                SELECT COUNT(*) FROM em_documents
                WHERE expiry_date IS NOT NULL
                  AND expiry_date < CURDATE()
            ")->fetchColumn();
            $expiring30 = (int) $this->conn->query("
                SELECT COUNT(*) FROM em_documents
                WHERE expiry_date IS NOT NULL
                  AND expiry_date >= CURDATE()
                  AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ")->fetchColumn();
            $valid = $total - $expired - $expiring30;
            if ($valid < 0) $valid = 0;
            $rate = $total > 0 ? round(($valid / $total) * 100) : 0;

            return [
                'total'      => $total,
                'valid'      => $valid,
                'rate'       => $rate,
                'expiring30' => $expiring30,
                'expiring60' => 0,
                'expiring90' => 0,
                'expired'    => $expired,
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'valid' => 0, 'rate' => 0, 'expiring30' => 0, 'expiring60' => 0, 'expiring90' => 0, 'expired' => 0];
        }
    }

    public function getAuditStats()
    {
        try {
            if (!$this->conn) {
                return ['total' => 0, 'completed' => 0, 'rate' => 0, 'openFindings' => 0, 'totalFindings' => 0, 'resolvedFindings' => 0, 'totalCorrective' => 0, 'completedCorrective' => 0];
            }
            $total            = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audits")->fetchColumn();
            $completed        = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audits WHERE status = 'Completed'")->fetchColumn();
            $openFindings     = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_findings WHERE status IN ('Open', 'In Progress', 'Escalated')")->fetchColumn();
            $totalFindings    = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_findings")->fetchColumn();
            $resolvedFindings = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_findings WHERE status IN ('Resolved', 'Closed')")->fetchColumn();
            $totalCorrective  = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_corrective_actions")->fetchColumn();
            $completedCorrective = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_corrective_actions WHERE status = 'Completed'")->fetchColumn();
            $rate             = $total > 0 ? round(($completed / $total) * 100) : 0;

            return [
                'total'               => $total,
                'completed'           => $completed,
                'rate'                => $rate,
                'openFindings'        => $openFindings,
                'totalFindings'       => $totalFindings,
                'resolvedFindings'    => $resolvedFindings,
                'totalCorrective'     => $totalCorrective,
                'completedCorrective' => $completedCorrective,
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'completed' => 0, 'rate' => 0, 'openFindings' => 0, 'totalFindings' => 0, 'resolvedFindings' => 0, 'totalCorrective' => 0, 'completedCorrective' => 0];
        }
    }

    public function getGovernmentCompliance()
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $agencies = [
                ['name' => 'SSS', 'table' => 'lc_sss_contributions'],
                ['name' => 'PhilHealth', 'table' => 'lc_philhealth_contributions'],
                ['name' => 'Pag-IBIG', 'table' => 'lc_pagibig_contributions'],
                ['name' => 'BIR', 'table' => 'lc_bir_contributions'],
            ];

            $result = [];
            foreach ($agencies as $agency) {
                $table = $agency['table'];

                $total = (int) $this->conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $submitted = (int) $this->conn->query("SELECT COUNT(*) FROM `$table` WHERE status != 'Pending'")->fetchColumn();
                $verified = (int) $this->conn->query("SELECT COUNT(*) FROM `$table` WHERE status IN ('Submitted', 'Paid')")->fetchColumn();
                $outstanding = $total - $submitted;
                if ($outstanding < 0) $outstanding = 0;
                $submissionRate = $total > 0 ? round(($submitted / $total) * 100, 1) : 0;
                $verificationRate = $submitted > 0 ? round(($verified / $submitted) * 100, 1) : 0;

                $result[] = [
                    'name' => $agency['name'],
                    'required' => $total,
                    'submitted' => $submitted,
                    'outstanding' => $outstanding,
                    'verified' => $verified,
                    'submissionRate' => $submissionRate,
                    'verificationRate' => $verificationRate,
                ];
            }

            usort($result, function ($a, $b) {
                return $b['outstanding'] <=> $a['outstanding'];
            });

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getDepartmentCompliance()
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT d.department_name, AVG(s.overall_score) as avg_score, COUNT(s.id) as emp_count
                    FROM em_departments d
                    LEFT JOIN lc_compliance_summary s ON s.department_id = d.department_id
                    WHERE d.status = 'Active'
                    GROUP BY d.department_id, d.department_name
                    ORDER BY avg_score DESC";
            $stmt = $this->conn->query($sql);
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $score = $row['avg_score'] !== null ? (int) round($row['avg_score']) : 0;
                $result[] = [
                    'department' => $row['department_name'],
                    'score'      => $score,
                    'employees'  => (int) $row['emp_count'],
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getIncidentCategories()
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT incident_type, COUNT(*) as cnt FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed') GROUP BY incident_type ORDER BY cnt DESC";
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = 0;
            foreach ($rows as $row) {
                $total += (int) ($row['cnt'] ?? 0);
            }
            foreach ($rows as &$row) {
                $row['total'] = $total;
                $row['share'] = $total > 0 ? round(($row['cnt'] / $total) * 100, 1) : 0;
            }
            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getIncidentOccurrenceData($year = null, $month = null)
    {
        try {
            if (!$this->conn) {
                return ['year' => date('Y'), 'month' => date('m'), 'items' => []];
            }
            $year = $year ?: (int) date('Y');
            $month = $month ?: (int) date('m');

            $sql = "SELECT incident_date, incident_type, severity, COUNT(*) as cnt
                    FROM lc_incident_report
                    WHERE status NOT IN ('resolved', 'closed')
                      AND incident_date IS NOT NULL
                      AND YEAR(incident_date) = :year
                      AND MONTH(incident_date) = :month
                    GROUP BY incident_date, incident_type, severity
                    ORDER BY incident_date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':year' => $year, ':month' => $month]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $byDate = [];
            $total = 0;
            $activeDays = 0;
            $peakDay = null;
            $peakCount = 0;
            $missingCount = 0;

            foreach ($rows as $row) {
                $date = $row['incident_date'];
                $cnt = (int) $row['cnt'];
                $total += $cnt;

                if (!isset($byDate[$date])) {
                    $byDate[$date] = [
                        'date' => $date,
                        'total' => 0,
                        'categories' => [],
                        'severities' => [],
                    ];
                }
                $byDate[$date]['total'] += $cnt;
                $byDate[$date]['categories'][$row['incident_type']] = ($byDate[$date]['categories'][$row['incident_type']] ?? 0) + $cnt;
                if (!empty($row['severity'])) {
                    $byDate[$date]['severities'][$row['severity']] = ($byDate[$date]['severities'][$row['severity']] ?? 0) + $cnt;
                }

                if ($cnt > $peakCount) {
                    $peakCount = $cnt;
                    $peakDay = $date;
                }
            }

            $activeDays = count($byDate);

            $missingSql = "SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed') AND incident_date IS NULL";
            $missingCount = (int) $this->conn->query($missingSql)->fetchColumn();

            if ($total === 0) {
                $latestSql = "SELECT YEAR(incident_date) as yr, MONTH(incident_date) as mo, COUNT(*) as cnt
                              FROM lc_incident_report
                              WHERE status NOT IN ('resolved', 'closed')
                                AND incident_date IS NOT NULL
                              GROUP BY YEAR(incident_date), MONTH(incident_date)
                              ORDER BY yr DESC, mo DESC
                              LIMIT 1";
                $latest = $this->conn->query($latestSql)->fetch(PDO::FETCH_ASSOC);
                if ($latest) {
                    return $this->getIncidentOccurrenceData((int) $latest['yr'], (int) $latest['mo']);
                }
            }

            return [
                'year' => $year,
                'month' => $month,
                'total' => $total,
                'activeDays' => $activeDays,
                'peakDay' => $peakDay,
                'peakCount' => $peakCount,
                'averagePerActiveDay' => $activeDays > 0 ? round($total / $activeDays, 1) : null,
                'days' => $byDate,
                'missingCount' => $missingCount,
            ];
        } catch (Exception $e) {
            return [
                'year' => $year ?: (int) date('Y'),
                'month' => $month ?: (int) date('m'),
                'total' => 0,
                'activeDays' => 0,
                'peakDay' => null,
                'peakCount' => 0,
                'averagePerActiveDay' => null,
                'days' => [],
                'missingCount' => 0,
            ];
        }
    }

    public function getIncidentSeveritySummary()
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT severity, COUNT(*) as cnt FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed') GROUP BY severity ORDER BY cnt DESC";
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[] = [
                    'severity' => $row['severity'],
                    'cnt' => (int) $row['cnt'],
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getIncidentAgingSummary()
    {
        try {
            if (!$this->conn) {
                return [
                    '0-7 days' => 0,
                    '8-30 days' => 0,
                    '31-90 days' => 0,
                    '90+ days' => 0,
                ];
            }
            $sql = "SELECT id, created_at FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed')";
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $buckets = [
                '0-7 days' => 0,
                '8-30 days' => 0,
                '31-90 days' => 0,
                '90+ days' => 0,
            ];

            $now = new DateTime();
            foreach ($rows as $row) {
                if (empty($row['created_at'])) continue;
                $created = new DateTime($row['created_at']);
                $diff = $now->diff($created)->days;
                if ($diff <= 7) {
                    $buckets['0-7 days']++;
                } elseif ($diff <= 30) {
                    $buckets['8-30 days']++;
                } elseif ($diff <= 90) {
                    $buckets['31-90 days']++;
                } else {
                    $buckets['90+ days']++;
                }
            }

            return $buckets;
        } catch (Exception $e) {
            return [
                '0-7 days' => 0,
                '8-30 days' => 0,
                '31-90 days' => 0,
                '90+ days' => 0,
            ];
        }
    }

    public function getEmployeeRiskRanking($limit = 10)
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT r.id, r.risk_type, r.severity, r.status,
                           e.first_name, e.last_name, e.employee_code,
                           d.department_name,
                           (SELECT COUNT(*) FROM lc_risks r2 WHERE r2.employee_id = r.employee_id AND r2.archived = 0) as violations
                     FROM lc_risks r
                     LEFT JOIN em_employees e ON r.employee_id = e.employee_id
                     LEFT JOIN em_departments d ON e.department_id = d.department_id
                     WHERE r.archived = 0
                     ORDER BY 
                         CASE r.severity WHEN 'Critical' THEN 4 WHEN 'High' THEN 3 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 1 END DESC,
                         r.created_at DESC
                     LIMIT " . (int) $limit;
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRecentActivities($limit = 10)
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT 
                        title, 
                        message, 
                        type, 
                        module, 
                        created_at as activity_date, 
                        COALESCE(NULLIF(module, ''), 'System') as actor
                    FROM lc_notifications
                    ORDER BY created_at DESC
                    LIMIT " . (int) $limit;
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTodayTasks()
    {
        try {
            if (!$this->conn) {
                return [];
            }
            $sql = "SELECT task_name as task, status, priority, deadline
                    FROM lc_compliance_tasks
                    WHERE status IN ('Pending', 'In Progress', 'Overdue')
                      AND priority IN ('Urgent', 'High', 'Critical')
                    ORDER BY 
                        CASE priority WHEN 'Critical' THEN 4 WHEN 'Urgent' THEN 3 WHEN 'High' THEN 2 WHEN 'Medium' THEN 1 WHEN 'Low' THEN 0 END DESC,
                        deadline ASC
                    LIMIT 8";
            $stmt = $this->conn->query($sql);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tasks as &$task) {
                $task['statusClass'] = strtolower($task['status']) === 'completed' ? 'success' :
                                       (strtolower($task['priority']) === 'urgent' || strtolower($task['priority']) === 'high' || strtolower($task['priority']) === 'critical' ? 'danger' : 'warning');
            }
            return $tasks;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getMonthlyTrend()
    {
        try {
            if (!$this->conn) {
                return ['months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], 'scores' => [85, 87, 88, 89, 92, 91]];
            }
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '%b %Y') as month,
                        AVG(score) as avg_score
                    FROM lc_compliance_records
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC";
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $months = [];
            $scores = [];
            foreach ($rows as $row) {
                $months[] = $row['month'];
                $scores[] = (int) round($row['avg_score']);
            }

            if (empty($months)) {
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                $scores = [85, 87, 88, 89, 92, 91];
            }

            return ['months' => $months, 'scores' => $scores];
        } catch (Exception $e) {
            return ['months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], 'scores' => [85, 87, 88, 89, 92, 91]];
        }
    }

    public function getActionAnalytics($limit = 8)
    {
        $items = [];

        try {
            if (!$this->conn) {
                return [
                    'total' => 0,
                    'urgency' => [
                        'overdue' => 0,
                        'today' => 0,
                        'soon' => 0,
                        'upcoming' => 0,
                        'unscheduled' => 0,
                    ],
                    'groups' => [],
                ];
            }
            $stmt = $this->conn->prepare("
                SELECT pa.id, pa.due_date, pa.status,
                       CONCAT(e.first_name, ' ', e.last_name) as person_name,
                       p.title as item_title,
                       'Policy Acknowledgement' as item_type,
                       'warning' as severity
                FROM lc_policy_assignments pa
                LEFT JOIN lc_policies p ON p.id = pa.policy_id
                LEFT JOIN em_employees e ON e.employee_id = pa.employee_id
                WHERE pa.status = 'Pending'
                ORDER BY pa.due_date ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $items[] = $row;
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return [
                    'total' => 0,
                    'urgency' => [
                        'overdue' => 0,
                        'today' => 0,
                        'soon' => 0,
                        'upcoming' => 0,
                        'unscheduled' => 0,
                    ],
                    'groups' => [],
                ];
            }
            $stmt = $this->conn->prepare("
                SELECT ci.id, ci.due_date, ci.status, ci.name as item_title, ci.category,
                       CONCAT(e.first_name, ' ', e.last_name) as person_name,
                       'Compliance Item' as item_type,
                       CASE WHEN ci.status = 'Overdue' THEN 'danger' ELSE 'warning' END as severity
                FROM lc_compliance_items ci
                LEFT JOIN em_employees e ON e.employee_id = ci.responsible_person_id
                WHERE ci.status IN ('Pending', 'Overdue', 'Non-Compliant')
                ORDER BY 
                    CASE ci.status WHEN 'Overdue' THEN 1 WHEN 'Non-Compliant' THEN 2 ELSE 3 END,
                    ci.due_date ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $items[] = $row;
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return [
                    'total' => 0,
                    'urgency' => [
                        'overdue' => 0,
                        'today' => 0,
                        'soon' => 0,
                        'upcoming' => 0,
                        'unscheduled' => 0,
                    ],
                    'groups' => [],
                ];
            }
            $stmt = $this->conn->prepare("
                SELECT ed.id, ed.expiry_date as due_date, 'Expired' as status,
                       CONCAT(e.first_name, ' ', e.last_name) as person_name,
                       ed.document_name as item_title, ed.document_type as item_category,
                       'Document Expiry' as item_type,
                       'danger' as severity
                FROM em_documents ed
                LEFT JOIN em_employees e ON e.employee_id = ed.employee_id
                WHERE ed.expiry_date < CURDATE()
                ORDER BY ed.expiry_date ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $items[] = $row;
            }
        } catch (Exception $e) {}

        usort($items, function ($a, $b) {
            $dateA = $a['due_date'] ?? '9999-12-31';
            $dateB = $b['due_date'] ?? '9999-12-31';
            return strcmp($dateA, $dateB);
        });

        $today = new DateTime('today');
        $dueSoonThreshold = new DateInterval('P7D');
        $urgencyCounts = [
            'overdue' => 0,
            'today' => 0,
            'soon' => 0,
            'upcoming' => 0,
            'unscheduled' => 0,
        ];

        $groups = [];
        foreach ($items as $item) {
            $dueDate = $item['due_date'] ?? null;
            $status = strtolower($item['status'] ?? 'pending');
            $title = $item['item_title'] ?? 'Untitled';
            $type = $item['item_type'] ?? 'Action';
            $person = $item['person_name'] ?? 'Unassigned';

            $urgency = 'unscheduled';
            if ($dueDate) {
                $due = new DateTime($dueDate);
                $diff = $today->diff($due)->days;
                if ($due < $today && $status !== 'completed') {
                    $urgency = 'overdue';
                } elseif ($due == $today && $status !== 'completed') {
                    $urgency = 'today';
                } elseif ($due > $today && $diff <= 7 && $status !== 'completed') {
                    $urgency = 'soon';
                } elseif ($status !== 'completed') {
                    $urgency = 'upcoming';
                }
            }

            $urgencyCounts[$urgency]++;

            $groupKey = md5($type . '|' . $title . '|' . $dueDate . '|' . $status);
            if (!isset($groups[$groupKey])) {
                $url = null;
                if ($type === 'Policy Acknowledgement') {
                    $url = '/modules/compliance/index.php?page=policy-management';
                } elseif ($type === 'Compliance Item') {
                    $url = '/modules/compliance/index.php?page=labor-compliance';
                } elseif ($type === 'Document Expiry') {
                    $url = '/modules/compliance/index.php?page=employee-documents';
                }

                $groups[$groupKey] = [
                    'title' => $title,
                    'type' => $type,
                    'due_date' => $dueDate,
                    'status' => $item['status'] ?? 'Pending',
                    'urgency' => $urgency,
                    'severity' => $item['severity'] ?? 'warning',
                    'people_count' => 0,
                    'people' => [],
                    'url' => $url,
                ];
            }
            $groups[$groupKey]['people_count']++;
            if (!in_array($person, $groups[$groupKey]['people'])) {
                $groups[$groupKey]['people'][] = $person;
            }
        }

        $sortedGroups = array_values($groups);
        usort($sortedGroups, function ($a, $b) {
            $order = ['overdue' => 0, 'today' => 1, 'soon' => 2, 'upcoming' => 3, 'unscheduled' => 4];
            return ($order[$a['urgency']] ?? 5) <=> ($order[$b['urgency']] ?? 5);
        });

        $totalPending = array_sum($urgencyCounts);

        return [
            'total' => $totalPending,
            'urgency' => $urgencyCounts,
            'groups' => array_slice($sortedGroups, 0, $limit),
        ];
    }

    public function getAlerts()
    {
        $alerts = [];

        try {
            if (!$this->conn) {
                return $alerts;
            }
            $expiringDocs = (int) $this->conn->query("SELECT COUNT(*) FROM em_documents WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
            if ($expiringDocs > 0) {
                $alerts[] = [
                    'priority' => 'Warning',
                    'icon'     => 'fa-file-circle-exclamation',
                    'message'  => $expiringDocs . ' employee documents are approaching expiration within 30 days.',
                    'count'    => $expiringDocs,
                    'label'    => 'Expiring Documents',
                ];
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return $alerts;
            }
            $pendingAcks = (int) $this->conn->query("SELECT COUNT(*) FROM lc_policy_assignments WHERE status = 'Pending'")->fetchColumn();
            if ($pendingAcks > 0) {
                $alerts[] = [
                    'priority' => 'Information',
                    'icon'     => 'fa-clipboard-check',
                    'message'  => $pendingAcks . ' policy acknowledgements are still pending.',
                    'count'    => $pendingAcks,
                    'label'    => 'Pending Acknowledgements',
                ];
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return $alerts;
            }
            $openFindings = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_findings WHERE status IN ('Open', 'In Progress')")->fetchColumn();
            if ($openFindings > 0) {
                $alerts[] = [
                    'priority' => 'Danger',
                    'icon'     => 'fa-magnifying-glass',
                    'message'  => $openFindings . ' open audit findings require attention.',
                    'count'    => $openFindings,
                    'label'    => 'Open Audit Findings',
                ];
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return $alerts;
            }
            $openIncidents = (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();
            if ($openIncidents > 0) {
                $alerts[] = [
                    'priority' => 'Danger',
                    'icon'     => 'fa-triangle-exclamation',
                    'message'  => $openIncidents . ' unresolved compliance incidents require attention.',
                    'count'    => $openIncidents,
                    'label'    => 'Unresolved Incidents',
                ];
            }
        } catch (Exception $e) {}

        try {
            if (!$this->conn) {
                return $alerts;
            }
            $overdueItems = (int) $this->conn->query("SELECT COUNT(*) FROM lc_compliance_items WHERE status = 'Overdue'")->fetchColumn();
            if ($overdueItems > 0) {
                $alerts[] = [
                    'priority' => 'Warning',
                    'icon'     => 'fa-clock',
                    'message'  => $overdueItems . ' overdue compliance items need review.',
                    'count'    => $overdueItems,
                    'label'    => 'Overdue Items',
                ];
            }
        } catch (Exception $e) {}

        return $alerts;
    }

    // ============================================================
    // Legal Case Dashboard Helpers
    // ============================================================

    public function getOpenLegalCases(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE current_status = 'Open'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getExternalCases(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE external_agency IS NOT NULL AND external_agency <> ''")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getCasesRequiringAction(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE due_date <= NOW() AND current_status NOT IN ('Resolved','Closed','Cancelled')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getOverdueCases(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE due_date < NOW() AND current_status NOT IN ('Resolved','Closed','Cancelled')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getCasesUnderMonitoring(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_cases WHERE current_status = 'Monitoring'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getUpcomingConferences(): int
    {
        try {
            if (!$this->conn) {
                return 0;
            }
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_legal_case_conferences WHERE conference_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getRecentlyResolvedCases(int $limit = 5): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT case_id, case_number, case_title, current_status, date_resolved, date_closed
                FROM lc_legal_cases
                WHERE current_status IN ('Resolved', 'Closed')
                ORDER BY date_resolved DESC, date_closed DESC, updated_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getLegalCaseStats(): array
    {
        return [
            'open'      => $this->getOpenLegalCases(),
            'external'  => $this->getExternalCases(),
            'action'    => $this->getCasesRequiringAction(),
            'overdue'   => $this->getOverdueCases(),
            'monitoring'=> $this->getCasesUnderMonitoring(),
            'conferences' => $this->getUpcomingConferences(),
        ];
    }
}


