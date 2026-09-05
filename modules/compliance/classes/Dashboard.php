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
            return (int) $this->conn->query("SELECT COUNT(*) FROM em_employees WHERE employment_status = 'Active'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getComplianceHealthScore()
    {
        try {
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
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getCriticalOpen()
    {
        try {
            return (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE severity = 'Critical' AND status NOT IN ('resolved', 'closed')")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getDocumentStats()
    {
        try {
            $total      = (int) $this->conn->query("SELECT COUNT(*) FROM employee_documents")->fetchColumn();
            $valid      = (int) $this->conn->query("SELECT COUNT(*) FROM employee_documents WHERE verification_status = 'Verified'")->fetchColumn();
            $expiring30 = (int) $this->conn->query("
                SELECT COUNT(*) FROM employee_documents
                WHERE verification_status = 'Verified'
                  AND expiry_date IS NOT NULL
                  AND expiry_date >= CURDATE()
                  AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ")->fetchColumn();
            $expired    = (int) $this->conn->query("
                SELECT COUNT(*) FROM employee_documents
                WHERE expiry_date IS NOT NULL
                  AND expiry_date < CURDATE()
            ")->fetchColumn();
            $rate       = $total > 0 ? round(($valid / $total) * 100) : 0;

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
            $agencies = [
                ['name' => 'SSS', 'table' => 'lc_sss_contributions', 'verified_status' => 'Submitted'],
                ['name' => 'PhilHealth', 'table' => 'lc_philhealth_contributions', 'verified_status' => 'Submitted'],
                ['name' => 'Pag-IBIG', 'table' => 'lc_pagibig_contributions', 'verified_status' => 'Submitted'],
                ['name' => 'BIR', 'table' => 'lc_bir_contributions', 'verified_status' => 'Submitted'],
            ];

            $result = [];
            foreach ($agencies as $agency) {
                $table = $agency['table'];
                $verifiedStatus = $agency['verified_status'];

                $total = (int) $this->conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $verified = (int) $this->conn->query("SELECT COUNT(*) FROM `$table` WHERE status = " . $this->conn->quote($verifiedStatus))->fetchColumn();

                $pct = $total > 0 ? round(($verified / $total) * 100) : 0;
                $result[] = [
                    'name'    => $agency['name'],
                    'total'   => $total,
                    'verified' => $verified,
                    'pct'     => $pct,
                ];
            }

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getDepartmentCompliance()
    {
        try {
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
            $sql = "SELECT incident_type, COUNT(*) as cnt FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed') GROUP BY incident_type ORDER BY cnt DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getEmployeeRiskRanking($limit = 10)
    {
        try {
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

    public function getActionRequired($limit = 8)
    {
        $items = [];

        try {
            $stmt = $this->conn->prepare("
                SELECT pa.id, pa.due_date, pa.status,
                       CONCAT(e.first_name, ' ', e.last_name) as person_name,
                       p.title as policy_title,
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
            $stmt = $this->conn->prepare("
                SELECT ci.id, ci.due_date, ci.status, ci.name, ci.category,
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
            $stmt = $this->conn->prepare("
                SELECT ed.id, ed.expiry_date, ed.verification_status as status,
                       CONCAT(e.first_name, ' ', e.last_name) as person_name,
                       ed.document_name, ed.document_type,
                       'Document Verification' as item_type,
                       'warning' as severity
                FROM lc_employee_documents ed
                LEFT JOIN em_employees e ON e.employee_id = ed.employee_id
                WHERE ed.verification_status IN ('Pending', 'Pending Upload', 'Rejected')
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
            $dateA = $a['due_date'] ?? $a['expiry_date'] ?? '9999-12-31';
            $dateB = $b['due_date'] ?? $b['expiry_date'] ?? '9999-12-31';
            return strcmp($dateA, $dateB);
        });

        return array_slice($items, 0, $limit);
    }

    public function getAlerts()
    {
        $alerts = [];

        try {
            $expiringDocs = (int) $this->conn->query("SELECT COUNT(*) FROM lc_employee_documents WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'Expired'")->fetchColumn();
            if ($expiringDocs > 0) {
                $alerts[] = [
                    'priority' => 'Warning',
                    'icon'     => 'fa-file-circle-exclamation',
                    'message'  => $expiringDocs . ' employee documents are approaching expiration within 30 days.',
                ];
            }
        } catch (Exception $e) {}

        try {
            $pendingAcks = (int) $this->conn->query("SELECT COUNT(*) FROM lc_policy_assignments WHERE status = 'Pending'")->fetchColumn();
            if ($pendingAcks > 0) {
                $alerts[] = [
                    'priority' => 'Information',
                    'icon'     => 'fa-clipboard-check',
                    'message'  => $pendingAcks . ' policy acknowledgements are still pending.',
                ];
            }
        } catch (Exception $e) {}

        try {
            $openFindings = (int) $this->conn->query("SELECT COUNT(*) FROM lc_audit_findings WHERE status IN ('Open', 'In Progress')")->fetchColumn();
            if ($openFindings > 0) {
                $alerts[] = [
                    'priority' => 'Danger',
                    'icon'     => 'fa-magnifying-glass',
                    'message'  => $openFindings . ' open audit findings require attention.',
                ];
            }
        } catch (Exception $e) {}

        try {
            $openIncidents = (int) $this->conn->query("SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed')")->fetchColumn();
            if ($openIncidents > 0) {
                $alerts[] = [
                    'priority' => 'Danger',
                    'icon'     => 'fa-triangle-exclamation',
                    'message'  => $openIncidents . ' unresolved compliance incidents require attention.',
                ];
            }
        } catch (Exception $e) {}

        try {
            $overdueItems = (int) $this->conn->query("SELECT COUNT(*) FROM lc_compliance_items WHERE status = 'Overdue'")->fetchColumn();
            if ($overdueItems > 0) {
                $alerts[] = [
                    'priority' => 'Warning',
                    'icon'     => 'fa-clock',
                    'message'  => $overdueItems . ' overdue compliance items need review.',
                ];
            }
        } catch (Exception $e) {}

        return $alerts;
    }
}
