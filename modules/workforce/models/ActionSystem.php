<?php
/**
 * ActionSystem.php
 * Core logic for WFA Action & Intervention System
 * Detects performance issues, recommends actions, and manages interventions
 * 
 * CONSTRAINTS: Does NOT modify pm_ tables. Reads from employees, performance_reviews, attendance.
 */

namespace WFA\System;

class ActionSystem
{
    private $db;
    private $employee_id;
    
    // Thresholds for issue detection
    private const ABSENCE_THRESHOLD = 4;
    private const TARDINESS_THRESHOLD = 10;
    private const LOW_RATING_THRESHOLD = 2.5;
    private const CRITICAL_RATING_THRESHOLD = 2.0;
    
    public function __construct($database)
    {
        // Support both PDO and mysqli connections
        $this->db = $database;
    }

    /**
     * Helper: Execute INSERT query and return last insert ID
     * @return int|false
     */
    private function executeInsert($query, $params = [])
    {
        try {
            if ($this->db instanceof \mysqli) {
                // Replace named parameters with ? for mysqli
                $query_params = [];
                $query = preg_replace_callback('/:(\w+)/', function($matches) use (&$query_params, $params) {
                    $query_params[] = $params[$matches[1]] ?? null;
                    return '?';
                }, $query);
                
                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    throw new \Exception("Prepare failed: " . $this->db->error);
                }
                
                if (!empty($query_params)) {
                    $types = str_repeat('s', count($query_params));
                    $stmt->bind_param($types, ...$query_params);
                }
                
                $stmt->execute();
                return $stmt->insert_id;
            } else {
                // PDO
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
                return intval($this->db->lastInsertId());
            }
        } catch (\Exception $e) {
            error_log("Insert execution error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Execute query that doesn't return data (INSERT/UPDATE/DELETE)
     * @return bool
     */
    private function executeWrite($query, $params = [])
    {
        try {
            if ($this->db instanceof \mysqli) {
                // Replace named parameters with ? for mysqli
                $query_params = [];
                $query = preg_replace_callback('/:(\w+)/', function($matches) use (&$query_params, $params) {
                    $query_params[] = $params[$matches[1]] ?? null;
                    return '?';
                }, $query);
                
                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    throw new \Exception("Prepare failed: " . $this->db->error);
                }
                
                if (!empty($query_params)) {
                    $types = str_repeat('s', count($query_params));
                    $stmt->bind_param($types, ...$query_params);
                }
                
                return $stmt->execute();
            } else {
                // PDO
                $stmt = $this->db->prepare($query);
                return $stmt->execute($params);
            }
        } catch (\Exception $e) {
            error_log("Write execution error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Execute SELECT query that works with both PDO and mysqli
     * @return array|null
     */
    private function executeQuery($query, $params = [])
    {
        try {
            // Check if it's mysqli
            if ($this->db instanceof \mysqli) {
                // Replace named parameters with ? for mysqli
                $query_params = [];
                $query = preg_replace_callback('/:(\w+)/', function($matches) use (&$query_params, $params) {
                    $query_params[] = $params[$matches[1]] ?? null;
                    return '?';
                }, $query);
                
                $stmt = $this->db->prepare($query);
                if (!$stmt) {
                    throw new \Exception("Prepare failed: " . $this->db->error);
                }
                
                if (!empty($query_params)) {
                    $types = str_repeat('s', count($query_params));
                    $stmt->bind_param($types, ...$query_params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                return !empty($data) ? $data : null;
            } else {
                // PDO
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {
            error_log("Query execution error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * CORE FUNCTION: Detect Performance Issues
     * Analyzes attendance and performance data WITHOUT modifying pm_ tables
     * 
     * @param int $employee_id
     * @return array ['issues' => [], 'severity' => 'string', 'metrics' => []]
     */
    public function detectPerformanceIssues($employee_id)
    {
        $this->employee_id = $employee_id;
        $issues = [];
        $metrics = [];
        
        // 1. Get Latest Performance Rating
        $rating = $this->getLatestPerformanceRating();
        $metrics['rating'] = $rating;
        
        // 2. Get Attendance Statistics
        $attendance_stats = $this->getAttendanceStatistics();
        $metrics['attendance'] = $attendance_stats;
        
        // 3. Analyze and Detect Issues
        if ($attendance_stats['absences'] > self::ABSENCE_THRESHOLD) {
            $issues[] = [
                'type' => 'Absenteeism',
                'message' => 'High Absenteeism',
                'details' => "{$attendance_stats['absences']} absences detected (threshold: " . self::ABSENCE_THRESHOLD . ")",
                'severity' => 'High'
            ];
        }
        
        if ($attendance_stats['tardiness'] > self::TARDINESS_THRESHOLD) {
            $issues[] = [
                'type' => 'Tardiness',
                'message' => 'Frequent Tardiness',
                'details' => "{$attendance_stats['tardiness']} late arrivals (threshold: " . self::TARDINESS_THRESHOLD . ")",
                'severity' => 'Medium'
            ];
        }
        
        if ($rating !== null && $rating < self::CRITICAL_RATING_THRESHOLD) {
            $issues[] = [
                'type' => 'Low Performance',
                'message' => 'Critical Low Performance Rating',
                'details' => "Rating {$rating}/5 (threshold: " . self::CRITICAL_RATING_THRESHOLD . ")",
                'severity' => 'Critical'
            ];
        } elseif ($rating !== null && $rating < self::LOW_RATING_THRESHOLD) {
            $issues[] = [
                'type' => 'Low Performance',
                'message' => 'Low Performance Rating',
                'details' => "Rating {$rating}/5 (threshold: " . self::LOW_RATING_THRESHOLD . ")",
                'severity' => 'High'
            ];
        }
        
        // 4. Check for Training Gaps
        $training_gaps = $this->checkTrainingGaps();
        if (!empty($training_gaps)) {
            $issues[] = [
                'type' => 'Training Gap',
                'message' => 'Skill Development Needed',
                'details' => implode(', ', $training_gaps),
                'severity' => 'Medium'
            ];
        }
        
        // 5. Calculate Overall Severity
        $severity = $this->calculateSeverity($issues);
        
        return [
            'employee_id' => $employee_id,
            'issues' => $issues,
            'severity' => $severity,
            'metrics' => $metrics,
            'issue_count' => count($issues),
            'has_critical_issues' => $severity === 'Critical'
        ];
    }

    /**
     * Get Latest Performance Rating
     * Reads from pm_appraisals table WITHOUT modification
     * 
     * @return float|null
     */
    private function getLatestPerformanceRating()
    {
        try {
            $query = "
                SELECT overall_score 
                FROM pm_appraisals 
                WHERE employee_id = :employee_id 
                ORDER BY review_date DESC 
                LIMIT 1
            ";
            
            $results = $this->executeQuery($query, ['employee_id' => $this->employee_id]);
            return $results && count($results) > 0 ? floatval($results[0]['overall_score']) : null;
        } catch (\Exception $e) {
            error_log("Error getting performance rating: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Attendance Statistics
     * Reads from ta_attendance table WITHOUT modification
     * 
     * @return array ['absences' => int, 'tardiness' => int, 'attendance_rate' => float]
     */
    private function getAttendanceStatistics()
    {
        try {
            // Get last 6 months of attendance
            $query = "
                SELECT 
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absences,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as tardiness,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status IN ('Present', 'Half-Day') THEN 1 ELSE 0 END) as present_days
                FROM ta_attendance 
                WHERE employee_id = :employee_id 
                AND attendance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ";
            
            $results = $this->executeQuery($query, ['employee_id' => $this->employee_id]);
            $result = $results && count($results) > 0 ? $results[0] : [];
            
            $absences = intval($result['absences'] ?? 0);
            $tardiness = intval($result['tardiness'] ?? 0);
            $total = intval($result['total_days'] ?? 1);
            $present = intval($result['present_days'] ?? 0);
            
            return [
                'absences' => $absences,
                'tardiness' => $tardiness,
                'total_days' => $total,
                'present_days' => $present,
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            error_log("Error getting attendance statistics: " . $e->getMessage());
            return [
                'absences' => 0,
                'tardiness' => 0,
                'total_days' => 0,
                'present_days' => 0,
                'attendance_rate' => 0
            ];
        }
    }

    /**
     * Check for Training Gaps
     * Identifies areas where training is needed based on performance data
     * 
     * @return array
     */
    private function checkTrainingGaps()
    {
        // This would connect to pm_training_recommendations if available
        // For now, return empty - can be extended based on your training module
        try {
            $query = "
                SELECT DISTINCT training_program 
                FROM pm_training_recommendations 
                WHERE employee_id = :employee_id 
                AND status IN ('Proposed', 'Pending')
                LIMIT 3
            ";
            
            $results = $this->executeQuery($query, ['employee_id' => $this->employee_id]);
            return $results ? array_column($results, 'training_program') : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate Overall Severity Level
     * 
     * @param array $issues
     * @return string 'Low', 'Medium', 'High', 'Critical'
     */
    private function calculateSeverity($issues)
    {
        $has_critical = array_reduce($issues, fn($carry, $issue) => 
            $carry || $issue['severity'] === 'Critical', false
        );
        
        if ($has_critical) return 'Critical';
        
        $high_count = count(array_filter($issues, fn($i) => $i['severity'] === 'High'));
        if ($high_count >= 2) return 'High';
        if ($high_count === 1) return 'High';
        
        $medium_count = count(array_filter($issues, fn($i) => $i['severity'] === 'Medium'));
        if ($medium_count >= 2) return 'Medium';
        if ($medium_count === 1) return 'Medium';
        
        return 'Low';
    }

    /**
     * BONUS: Recommend Actions Based on Detected Issues
     * 
     * @param array $issue_detection Result from detectPerformanceIssues()
     * @return array ['issues' => [], 'recommended_action' => 'string', 'confidence_score' => float]
     */
    public function recommendAction($issue_detection)
    {
        $issues = $issue_detection['issues'];
        $issue_types = array_column($issues, 'type');
        $severity = $issue_detection['severity'];
        
        $recommendation = 'No Action Required';
        $confidence = 0.0;
        
        if ($severity === 'Critical') {
            $recommendation = 'Create PIP';
            $confidence = 0.95;
        } elseif ($severity === 'High') {
            if (count($issues) >= 2) {
                // Multiple high-severity issues
                if (in_array('Low Performance', $issue_types) && 
                    in_array('Absenteeism', $issue_types)) {
                    $recommendation = 'Create PIP';
                    $confidence = 0.90;
                } else {
                    $recommendation = 'Issue Warning';
                    $confidence = 0.80;
                }
            } else if (in_array('Low Performance', $issue_types)) {
                $recommendation = 'Assign Training';
                $confidence = 0.85;
            } else if (in_array('Absenteeism', $issue_types)) {
                $recommendation = 'Issue Warning';
                $confidence = 0.75;
            }
        } elseif ($severity === 'Medium') {
            if (in_array('Training Gap', $issue_types)) {
                $recommendation = 'Assign Training';
                $confidence = 0.70;
            } else if (in_array('Tardiness', $issue_types)) {
                $recommendation = 'Issue Warning';
                $confidence = 0.65;
            } else {
                $recommendation = 'Assign Mentor';
                $confidence = 0.60;
            }
        }
        
        return [
            'issues' => array_map(fn($i) => $i['message'], $issues),
            'recommended_action' => $recommendation,
            'confidence_score' => $confidence,
            'severity' => $severity,
            'action_rationale' => $this->getActionRationale($recommendation, $issues)
        ];
    }

    /**
     * Get Rationale for Recommended Action
     * 
     * @param string $action
     * @param array $issues
     * @return string
     */
    private function getActionRationale($action, $issues)
    {
        $rationales = [
            'Create PIP' => 'Multiple critical issues require formal structured intervention',
            'Issue Warning' => 'Employee needs formal notification of performance concerns',
            'Assign Training' => 'Skills development and training can address identified gaps',
            'Assign Mentor' => 'Mentoring and guidance can improve performance',
            'No Action Required' => 'Employee performance is within acceptable standards'
        ];
        
        return $rationales[$action] ?? 'Action recommended based on performance analysis';
    }

    /**
     * Create a Performance Improvement Plan
     * Writes to WFA tables ONLY (does not modify pm_ tables)
     * 
     * @param array $data
     * @return array ['success' => bool, 'pip_id' => int, 'message' => string]
     */
    public function createPerformanceImprovementPlan($data)
    {
        try {
            $query = "
                INSERT INTO wfa_performance_improvement_plans 
                (employee_id, start_date, end_date, reason, action_plan, status, performance_target, created_by) 
                VALUES (:employee_id, :start_date, :end_date, :reason, :action_plan, :status, :performance_target, :created_by)
            ";
            
            $pip_id = $this->executeInsert($query, [
                'employee_id' => $data['employee_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'action_plan' => $data['action_plan'],
                'status' => 'ONGOING',
                'performance_target' => $data['performance_target'] ?? 3.0,
                'created_by' => $data['created_by'] ?? 1
            ]);
            
            if ($pip_id) {
                return [
                    'success' => true,
                    'pip_id' => $pip_id,
                    'message' => 'Performance Improvement Plan created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'pip_id' => 0,
                    'message' => 'Failed to create Performance Improvement Plan'
                ];
            }
        } catch (\Exception $e) {
            error_log("Error creating PIP: " . $e->getMessage());
            return [
                'success' => false,
                'pip_id' => 0,
                'message' => 'Error creating PIP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create an Action/Intervention
     * Writes to WFA tables ONLY
     * 
     * @param array $data
     * @return array ['success' => bool, 'action_id' => int, 'message' => string]
     */
    public function createAction($data)
    {
        try {
            $query = "
                INSERT INTO wfa_actions 
                (employee_id, pip_id, action_type, description, status, assigned_to, due_date, created_at) 
                VALUES (:employee_id, :pip_id, :action_type, :description, :status, :assigned_to, :due_date, NOW())
            ";
            
            $action_id = $this->executeInsert($query, [
                'employee_id' => $data['employee_id'],
                'pip_id' => $data['pip_id'] ?? null,
                'action_type' => $data['action_type'],
                'description' => $data['description'],
                'status' => 'Pending',
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_date' => $data['due_date'] ?? date('Y-m-d', strtotime('+30 days'))
            ]);
            
            if ($action_id) {
                return [
                    'success' => true,
                    'action_id' => $action_id,
                    'message' => 'Action created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'action_id' => 0,
                    'message' => 'Failed to create action'
                ];
            }
        } catch (\Exception $e) {
            error_log("Error creating action: " . $e->getMessage());
            return [
                'success' => false,
                'action_id' => 0,
                'message' => 'Error creating action: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store Recommendation for Audit Trail
     * 
     * @param int $employee_id
     * @param array $recommendation
     * @return bool
     */
    public function storeRecommendation($employee_id, $recommendation)
    {
        try {
            $query = "
                INSERT INTO wfa_action_recommendations 
                (employee_id, detected_issues, recommended_action, confidence_score) 
                VALUES (:employee_id, :detected_issues, :recommended_action, :confidence_score)
            ";
            
            return $this->executeWrite($query, [
                'employee_id' => $employee_id,
                'detected_issues' => json_encode($recommendation['issues']),
                'recommended_action' => $recommendation['recommended_action'],
                'confidence_score' => $recommendation['confidence_score']
            ]);
        } catch (\Exception $e) {
            error_log("Error storing recommendation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Low Performance Employees
     * Returns employees who need intervention
     * 
     * @return array
     */
    public function getLowPerformanceEmployees()
    {
        try {
            $query = "
                SELECT DISTINCT
                    e.employee_id,
                    e.full_name,
                    e.position,
                    e.department,
                    pa.overall_score as rating,
                    COUNT(DISTINCT CASE WHEN ta.status = 'Absent' THEN ta.id END) as absences,
                    COUNT(DISTINCT CASE WHEN ta.status = 'Late' THEN ta.id END) as tardiness
                FROM employees e
                LEFT JOIN pm_appraisals pa ON e.employee_id = pa.employee_id
                LEFT JOIN ta_attendance ta ON e.employee_id = ta.employee_id 
                    AND ta.attendance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                WHERE e.status = 'Active'
                GROUP BY e.employee_id, e.full_name, e.position, e.department, pa.overall_score
                HAVING pa.overall_score < " . self::LOW_RATING_THRESHOLD . "
                    OR COUNT(DISTINCT CASE WHEN ta.status = 'Absent' THEN ta.id END) > " . self::ABSENCE_THRESHOLD . "
                ORDER BY pa.overall_score ASC, absences DESC
            ";
            
            $employees = $this->executeQuery($query);
            
            if (!$employees) {
                return [];
            }
            
            // Add issue analysis for each employee
            foreach ($employees as &$employee) {
                $issue_detection = $this->detectPerformanceIssues($employee['employee_id']);
                $employee['issues'] = $issue_detection['issues'];
                $employee['severity'] = $issue_detection['severity'];
                $employee['recommendation'] = $this->recommendAction($issue_detection);
            }
            
            return $employees;
        } catch (\Exception $e) {
            error_log("Error getting low performance employees: " . $e->getMessage());
            return [];
        }
    }
}
