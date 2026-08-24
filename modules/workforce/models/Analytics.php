<?php
/**
 * Analytics Model Class
 * Handles analytics and reporting operations
 */

require_once __DIR__ . '/../config/Database.php';

class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics() {
        $metrics = [];

        // Total employees (active)
        $query = "SELECT COUNT(*) as count FROM employees WHERE employment_status = 'Active'";
        $metrics['total_employees'] = $this->db->fetchOne($query)['count'];

        // Total teachers/faculty
        $query = "SELECT COUNT(*) as count FROM employees WHERE (position LIKE '%Teacher%' OR position LIKE '%Faculty%') AND employment_status = 'Active'";
        $metrics['total_teachers'] = $this->db->fetchOne($query)['count'];

        // Total staff
        $query = "SELECT COUNT(*) as count FROM employees WHERE (position NOT LIKE '%Teacher%' AND position NOT LIKE '%Faculty%') AND employment_status = 'Active'";
        $metrics['total_staff'] = $this->db->fetchOne($query)['count'];

        // New hires this year
        $currentYear = date('Y');
        $query = "SELECT COUNT(*) as count FROM employees WHERE YEAR(date_hired) = ? AND employment_status = 'Active'";
        $metrics['new_hires'] = $this->db->fetchOne($query, [$currentYear], 'i')['count'];

        // Average performance
        $query = "SELECT AVG(overall_score) as avg_performance FROM pm_appraisals WHERE overall_score IS NOT NULL";
        $perfData = $this->db->fetchOne($query);
        $metrics['avg_performance'] = round($perfData['avg_performance'] ?? 0, 2);

        // Total appraisals
        $query = "SELECT COUNT(*) as count FROM pm_appraisals";
        $metrics['total_reviews'] = $this->db->fetchOne($query)['count'];

        return $metrics;
    }

    /**
     * Get department-wise distribution
     */
    public function getDepartmentDistribution() {
        $query = "SELECT department, COUNT(*) as count FROM employees WHERE employment_status = 'Active' AND department IS NOT NULL GROUP BY department ORDER BY count DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get gender distribution
     */
    public function getGenderDistribution() {
        // hr_management doesn't have gender field, so return empty or department-based
        $query = "SELECT department as category, COUNT(*) as count FROM employees WHERE employment_status = 'Active' GROUP BY department LIMIT 5";
        return $this->db->fetchAll($query);
    }

    /**
     * Get age group distribution
     */
    public function getAgeGroupDistribution() {
        // hr_management doesn't have age field, so return by hiring year instead
        $query = "SELECT
                    CASE
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) < 1 THEN '< 1 year'
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) BETWEEN 1 AND 3 THEN '1-3 years'
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) BETWEEN 4 AND 7 THEN '4-7 years'
                        ELSE '8+ years'
                    END as tenure_group,
                    COUNT(*) as count
                FROM employees
                WHERE employment_status = 'Active' AND date_hired IS NOT NULL
                GROUP BY tenure_group
                ORDER BY tenure_group ASC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get attendance rate
     */
    public function getAttendanceRate($months = 3) {
        // Calculate attendance rate for last N months
        $query = "SELECT
                    ROUND(
                        (SUM(CASE WHEN status IN ('PRESENT', 'ON_LEAVE') THEN 1 ELSE 0 END) / 
                        COUNT(*)) * 100, 2
                    ) as attendance_rate
                FROM ta_attendance
                WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
        
        $result = $this->db->fetchOne($query, [$months], 'i');
        return $result['attendance_rate'] ?? 0;
    }

    /**
     * Get attendance breakdown by status
     */
    public function getAttendanceBreakdown($days = 30) {
        $query = "SELECT
                    status,
                    COUNT(*) as count,
                    ROUND((COUNT(*) / (SELECT COUNT(*) FROM ta_attendance 
                           WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY))) * 100, 2) as percentage
                FROM ta_attendance
                WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY status
                ORDER BY count DESC";
        
        return $this->db->fetchAll($query, [$days, $days], 'ii');
    }

    /**
     * Get attendance trend over time
     */
    public function getAttendanceTrend($days = 30) {
        $query = "SELECT
                    DATE(attendance_date) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('PRESENT', 'ON_LEAVE') THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late,
                    ROUND(
                        (SUM(CASE WHEN status IN ('PRESENT', 'ON_LEAVE') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2
                    ) as daily_rate
                FROM ta_attendance
                WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(attendance_date)
                ORDER BY date ASC";
        
        return $this->db->fetchAll($query, [$days], 'i');
    }

    /**
     * Get attrition data by month from exit_resignations
     */
    public function getAttritionData($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT
                    MONTH(last_working_date) as month,
                    MONTHNAME(last_working_date) as month_name,
                    resignation_type as resignation_type,
                    COUNT(*) as count
                FROM exit_resignations
                WHERE YEAR(last_working_date) = ? AND status = 'approved'
                GROUP BY MONTH(last_working_date), resignation_type
                ORDER BY MONTH(last_working_date) ASC";

        return $this->db->fetchAll($query, [$year], 'i');
    }

    /**
     * Get total separated/resigned employees for the year
     */
    public function getSeparatedCount($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT COUNT(*) as count
                FROM exit_resignations
                WHERE YEAR(last_working_date) = ? AND status = 'approved'";

        return $this->db->fetchOne($query, [$year], 'i')['count'];
    }

    /**
     * Get resignation reasons breakdown from exit_resignations
     */
    public function getResignationReasons($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT
                    reason,
                    COUNT(*) as count
                FROM exit_resignations
                WHERE YEAR(last_working_date) = ? AND status = 'approved' AND reason IS NOT NULL AND reason != ''
                GROUP BY reason
                ORDER BY count DESC
                LIMIT 10";

        return $this->db->fetchAll($query, [$year], 'i');
    }

    /**
     * Get separated employees list
     */
    public function getSeparatedEmployees($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT
                    er.employee_id,
                    COALESCE(e.full_name, er.employee_id) as employee_name,
                    COALESCE(e.department, 'N/A') as department,
                    COALESCE(e.position, 'N/A') as position,
                    COALESCE(e.email, '') as email,
                    er.resignation_type,
                    er.reason,
                    er.notice_date,
                    er.last_working_date,
                    er.status
                FROM exit_resignations er
                LEFT JOIN employees e ON er.employee_id = e.employee_id
                WHERE YEAR(er.last_working_date) = ?
                ORDER BY er.last_working_date DESC";

        return $this->db->fetchAll($query, [$year], 'i');
    }

    /**
     * Get employees at risk
     */
    public function getEmployeesAtRisk() {
        // hr_management doesn't have absence_days or performance_score in employees table
        // Using pm_appraisals table instead
        $query = "SELECT
                    e.employee_id as id,
                    e.full_name as name,
                    e.department,
                    e.position,
                    COALESCE(pr.overall_score, 0) as performance_score,
                    YEAR(CURDATE()) - YEAR(e.date_hired) as tenure_years,
                    CASE
                        WHEN pr.overall_score < 2.5 THEN 'High'
                        WHEN pr.overall_score < 3.5 THEN 'Medium'
                        ELSE 'Low'
                    END as risk_level
                FROM employees e
                LEFT JOIN pm_appraisals pr ON e.employee_id = pr.employee_id
                WHERE e.employment_status = 'Active'
                AND pr.overall_score IS NOT NULL
                AND pr.overall_score < 3.5
                ORDER BY risk_level DESC";

        return $this->db->fetchAll($query);
    }

    /**
     * Get performance distribution
     */
    public function getPerformanceDistribution() {
        $query = "SELECT
                    CASE
                        WHEN overall_score >= 4.5 THEN 'Excellent (4.5+)'
                        WHEN overall_score >= 4 THEN 'Very Good (4.0-4.5)'
                        WHEN overall_score >= 3 THEN 'Good (3.0-3.9)'
                        WHEN overall_score >= 2 THEN 'Fair (2.0-2.9)'
                        ELSE 'Poor (<2.0)'
                    END as performance_level,
                    COUNT(*) as count,
                    ROUND((COUNT(*) / (SELECT COUNT(*) FROM pm_appraisals WHERE overall_score IS NOT NULL)) * 100, 2) as percentage
                FROM pm_appraisals
                WHERE overall_score IS NOT NULL
                GROUP BY performance_level
                ORDER BY overall_score DESC";

        return $this->db->fetchAll($query);
    }

    /**
     * Generate custom report
     */
    public function generateCustomReport($filters = []) {
        $query = "SELECT * FROM employees WHERE employment_status = 'Active'";
        $params = [];
        $types = '';

        // Apply filters
        if (!empty($filters['department'])) {
            $query .= " AND department = ?";
            $params[] = $filters['department'];
            $types .= 's';
        }

        if (!empty($filters['employment_type'])) {
            $query .= " AND employment_status = ?";
            $params[] = $filters['employment_type'];
            $types .= 's';
        }

        if (!empty($filters['hire_date_from'])) {
            $query .= " AND date_hired >= ?";
            $params[] = $filters['hire_date_from'];
            $types .= 's';
        }

        if (!empty($filters['hire_date_to'])) {
            $query .= " AND date_hired <= ?";
            $params[] = $filters['hire_date_to'];
            $types .= 's';
        }

        $query .= " ORDER BY full_name ASC";

        if (empty($params)) {
            return $this->db->fetchAll($query);
        }

        return $this->db->fetchAll($query, $params, $types);
    }

    /**
     * Get salary statistics
     */
    public function getSalaryStatistics() {
        // hr_management doesn't have salary in employees table
        // Return department statistics instead
        $query = "SELECT
                    department,
                    COUNT(*) as count,
                    COUNT(DISTINCT position) as positions
                FROM employees
                WHERE employment_status = 'Active' AND department IS NOT NULL
                GROUP BY department
                ORDER BY count DESC";

        return $this->db->fetchAll($query);
    }

    /**
     * Get tenure distribution
     */
    public function getTenureDistribution() {
        $query = "SELECT
                    CASE
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) < 1 THEN '< 1 year'
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) BETWEEN 1 AND 3 THEN '1-3 years'
                        WHEN YEAR(CURDATE()) - YEAR(date_hired) BETWEEN 4 AND 7 THEN '4-7 years'
                        ELSE '8+ years'
                    END as tenure_range,
                    COUNT(*) as count
                FROM employees
                WHERE employment_status = 'Active' AND date_hired IS NOT NULL
                GROUP BY tenure_range
                ORDER BY tenure_range ASC";

        return $this->db->fetchAll($query);
    }

    /**
     * Get attrition rate for a given year
     */
    public function getAttritionRate($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $separatedCount = $this->getSeparatedCount($year);

        $query = "SELECT COUNT(*) as count FROM employees WHERE employment_status IN ('Active', 'active')";
        $activeCount = (int)($this->db->fetchOne($query)['count'] ?? 0);

        return $activeCount > 0 ? round(($separatedCount / $activeCount) * 100, 2) : 0.00;
    }

    /**
     * Get root causes of performance issues for an employee
     * Analyzes attendance, appraisal scores, and feedback to identify issues
     */
    public function getPerformanceRootCauses($employee_id) {
        $causes = [];
        
        // 1. Check Attendance Issues (last 30 days)
        $query = "SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absences,
                    SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late_arrivals,
                    ROUND((SUM(CASE WHEN status IN ('PRESENT', 'ON_LEAVE') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
                FROM ta_attendance
                WHERE employee_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $attendanceData = $this->db->fetchOne($query, [$employee_id], 'i');
        
        if ($attendanceData && $attendanceData['total_records'] > 0) {
            $absenceCount = $attendanceData['absences'] ?? 0;
            $attendanceRate = $attendanceData['attendance_rate'] ?? 100;
            
            if ($absenceCount > 5) {
                $causes[] = [
                    'issue' => 'High Absenteeism',
                    'severity' => 'HIGH',
                    'data' => $absenceCount . ' absences in last 30 days',
                    'weight' => 0.4,
                    'metric_value' => $absenceCount
                ];
            } elseif ($absenceCount > 2) {
                $causes[] = [
                    'issue' => 'Moderate Absenteeism',
                    'severity' => 'MEDIUM',
                    'data' => $absenceCount . ' absences in last 30 days',
                    'weight' => 0.25,
                    'metric_value' => $absenceCount
                ];
            }
            
            if ($attendanceRate < 85) {
                $causes[] = [
                    'issue' => 'Low Attendance Rate',
                    'severity' => 'HIGH',
                    'data' => 'Attendance rate: ' . $attendanceRate . '%',
                    'weight' => 0.35,
                    'metric_value' => $attendanceRate
                ];
            }
        }
        
        // 2. Check Appraisal Score (last appraisal)
        $query = "SELECT overall_score, review_date, comments FROM pm_appraisals 
                 WHERE employee_id = ? ORDER BY review_date DESC LIMIT 1";
        
        $appraisalData = $this->db->fetchOne($query, [$employee_id], 'i');
        
        if ($appraisalData && $appraisalData['overall_score']) {
            $score = $appraisalData['overall_score'];
            
            if ($score < 2.5) {
                $causes[] = [
                    'issue' => 'Low Appraisal Score',
                    'severity' => 'HIGH',
                    'data' => 'Latest appraisal: ' . $score . '/5.0',
                    'weight' => 0.35,
                    'metric_value' => $score
                ];
            } elseif ($score < 3.5) {
                $causes[] = [
                    'issue' => 'Below Average Appraisal',
                    'severity' => 'MEDIUM',
                    'data' => 'Latest appraisal: ' . $score . '/5.0',
                    'weight' => 0.25,
                    'metric_value' => $score
                ];
            }
        }
        
        // 3. Check 360 Feedback (latest feedback)
        $query = "SELECT AVG(rating) as avg_feedback FROM pm_360_feedback 
                 WHERE employee_id = ? AND evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        
        $feedbackData = $this->db->fetchOne($query, [$employee_id], 'i');
        
        if ($feedbackData && $feedbackData['avg_feedback']) {
            $feedbackScore = $feedbackData['avg_feedback'];
            
            if ($feedbackScore < 2.5) {
                $causes[] = [
                    'issue' => 'Low Peer/Manager Feedback',
                    'severity' => 'HIGH',
                    'data' => 'Average 360 feedback: ' . round($feedbackScore, 2) . '/5.0',
                    'weight' => 0.30,
                    'metric_value' => $feedbackScore
                ];
            } elseif ($feedbackScore < 3.5) {
                $causes[] = [
                    'issue' => 'Below Average Feedback',
                    'severity' => 'MEDIUM',
                    'data' => 'Average 360 feedback: ' . round($feedbackScore, 2) . '/5.0',
                    'weight' => 0.20,
                    'metric_value' => $feedbackScore
                ];
            }
        }
        
        // Sort by weight (most important first)
        usort($causes, function($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
        
        return $causes;
    }

    /**
     * Get recommended actions based on performance root causes
     * Returns suggested HR actions with priority levels and smart logic
     */
    public function getRecommendedActions($employee_id) {
        $recommendations = [];
        
        // Get root causes first
        $rootCauses = $this->getPerformanceRootCauses($employee_id);
        
        if (empty($rootCauses)) {
            return $recommendations;
        }
        
        // Analyze primary causes and severity
        $highSeverityCount = count(array_filter($rootCauses, function($c) {
            return $c['severity'] === 'HIGH';
        }));
        $mediumSeverityCount = count(array_filter($rootCauses, function($c) {
            return $c['severity'] === 'MEDIUM';
        }));
        
        $issues = array_column($rootCauses, 'issue');
        $hasAbsenteeism = in_array('High Absenteeism', $issues);
        $hasLowAppraisal = in_array('Low Appraisal Score', $issues);
        $hasLowFeedback = in_array('Low Peer/Manager Feedback', $issues);
        $hasLowAttendance = in_array('Low Attendance Rate', $issues);
        
        // PRIORITY 1: Address immediate attendance issues
        if ($hasAbsenteeism || $hasLowAttendance) {
            $recommendations[] = [
                'action' => 'COACHING_SESSION',
                'title' => 'Schedule Coaching Session (Immediate)',
                'description' => 'Meet with employee within 48 hours to understand attendance barriers, health issues, or personal challenges',
                'priority' => 'CRITICAL',
                'action_type' => 'COACHING',
                'timeline' => '48 hours',
                'focus_areas' => ['Root causes', 'Support needs', 'Realistic goals'],
                'confidence' => 0.95
            ];
            
            $recommendations[] = [
                'action' => 'CREATE_PIP',
                'title' => 'Create Attendance Improvement Plan (30 days)',
                'description' => 'Structured plan with specific absences reduction target, weekly check-ins, and clear consequences',
                'duration_days' => 30,
                'priority' => 'CRITICAL',
                'action_type' => 'PIP',
                'target_metric' => 'Maximum 1 absence per 2 weeks',
                'success_criteria' => ['Zero unannounced absences', 'Doctor\'s note for medical absences'],
                'weekly_checkins' => true,
                'confidence' => 0.98
            ];
        }
        
        // PRIORITY 2: Address performance gaps
        if ($hasLowAppraisal) {
            // Intelligent training selection based on performance area
            $trainingModules = [
                'Core Technical Skills',
                'Time Management',
                'Quality Assurance',
                'Work Standards'
            ];
            
            $recommendations[] = [
                'action' => 'ASSIGN_TRAINING',
                'title' => 'Enroll in Performance Development Program',
                'description' => 'Structured training addressing technical and soft skills gaps identified in appraisal',
                'priority' => 'HIGH',
                'action_type' => 'TRAINING',
                'duration_weeks' => 6,
                'training_modules' => $trainingModules,
                'frequency' => '2 sessions per week',
                'with_assessment' => true,
                'confidence' => 0.90
            ];
            
            $recommendations[] = [
                'action' => 'ASSIGN_MENTOR',
                'title' => 'Assign High-Performer Mentor (6 weeks)',
                'description' => 'Pair with top performer in same role for 1-on-1 guidance, shadowing, and skills transfer',
                'priority' => 'HIGH',
                'action_type' => 'MENTORING',
                'duration_weeks' => 6,
                'meeting_frequency' => '2x per week',
                'mentoring_areas' => ['Best practices', 'Problem-solving', 'Professional development'],
                'confidence' => 0.88
            ];
        }
        
        // PRIORITY 3: Address interpersonal/feedback issues
        if ($hasLowFeedback) {
            $recommendations[] = [
                'action' => 'CONDUCT_FEEDBACK_SESSION',
                'title' => 'Formal Feedback Session with Manager',
                'description' => 'Structured meeting to discuss specific 360 feedback, expectations, and improvement areas',
                'priority' => 'HIGH',
                'action_type' => 'FEEDBACK',
                'timeline' => '1 week',
                'include_documentation' => true,
                'focus_areas' => ['Communication', 'Collaboration', 'Teamwork'],
                'confidence' => 0.92
            ];
            
            $recommendations[] = [
                'action' => 'SOFT_SKILLS_TRAINING',
                'title' => 'Soft Skills Development Program',
                'description' => 'Comprehensive training in communication, emotional intelligence, and teamwork',
                'priority' => 'HIGH',
                'action_type' => 'TRAINING',
                'duration_weeks' => 4,
                'training_modules' => ['Communication Skills', 'Emotional Intelligence', 'Conflict Resolution', 'Team Collaboration'],
                'interactive_sessions' => true,
                'confidence' => 0.85
            ];
        }
        
        // PRIORITY 4: Critical escalation if multiple severe issues
        if ($highSeverityCount >= 2) {
            $recommendations[] = [
                'action' => 'FORMAL_WARNING',
                'title' => 'Issue Formal Performance Warning',
                'description' => 'Document performance concerns formally. Employee must acknowledge and sign.',
                'priority' => 'CRITICAL',
                'action_type' => 'WARNING',
                'legally_documented' => true,
                'includes_signature' => true,
                'copies_to_file' => true,
                'confidence' => 0.90
            ];
        }
        
        if ($highSeverityCount >= 3) {
            $recommendations[] = [
                'action' => 'ESCALATE_TO_HR',
                'title' => 'HR Director Review & Intervention',
                'description' => 'Multiple critical issues require HR Director oversight for possible further escalation or separation planning',
                'priority' => 'CRITICAL',
                'action_type' => 'ESCALATION',
                'escalation_level' => 'HR Director',
                'timeline' => 'Immediate (same day)',
                'may_include' => ['Performance improvement contract', 'Disciplinary action plan', 'Termination discussion'],
                'confidence' => 0.92
            ];
        }
        
        // PRIORITY 5: Regular monitoring and follow-up
        $recommendations[] = [
            'action' => 'SETUP_MONITORING',
            'title' => 'Weekly Performance Monitoring',
            'description' => 'Manager to track progress on all improvement initiatives with documented check-ins',
            'priority' => 'HIGH',
            'action_type' => 'MONITORING',
            'frequency' => 'Weekly',
            'duration_weeks' => 30,
            'track_metrics' => ['Attendance', 'Performance quality', 'Feedback progress'],
            'documentation' => 'Required',
            'confidence' => 0.93
        ];
        
        // Sort by priority (CRITICAL → HIGH → MEDIUM → LOW)
        $priorityOrder = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3];
        usort($recommendations, function($a, $b) use ($priorityOrder) {
            $priorityA = $priorityOrder[$a['priority']] ?? 99;
            $priorityB = $priorityOrder[$b['priority']] ?? 99;
            return $priorityA <=> $priorityB;
        });
        
        return $recommendations;
    }
    
    /**
     * Generate intelligent action plan with sequenced steps and milestones
     */
    public function generateActionPlan($employee_id) {
        $rootCauses = $this->getPerformanceRootCauses($employee_id);
        $recommendations = $this->getRecommendedActions($employee_id);
        
        if (empty($recommendations)) {
            return null;
        }
        
        $issues = array_column($rootCauses, 'issue');
        $hasAbsenteeism = in_array('High Absenteeism', $issues);
        $hasLowAppraisal = in_array('Low Appraisal Score', $issues);
        $hasLowFeedback = in_array('Low Peer/Manager Feedback', $issues);
        
        $actionPlan = [
            'employee_id' => $employee_id,
            'plan_type' => $hasAbsenteeism && $hasLowAppraisal ? 'COMPREHENSIVE' : ($hasAbsenteeism ? 'ATTENDANCE' : 'PERFORMANCE'),
            'severity_level' => count(array_filter($rootCauses, fn($c) => $c['severity'] === 'HIGH')) >= 3 ? 'CRITICAL' : 'HIGH',
            'duration_days' => 90,
            'phases' => []
        ];
        
        // PHASE 1: Immediate Assessment (Days 1-7)
        $actionPlan['phases'][] = [
            'phase_name' => 'Phase 1: Immediate Assessment & Communication',
            'duration_days' => '1-7',
            'objectives' => [
                'Meet with employee to discuss concerns',
                'Understand root causes and barriers',
                'Set clear expectations and goals'
            ],
            'actions' => [
                [
                    'step' => 1,
                    'title' => 'Manager Meeting',
                    'description' => 'Initial meeting to address concerns and explain process',
                    'owner' => 'Manager',
                    'deadline' => 'Day 1',
                    'success_criteria' => 'Meeting documented, employee acknowledges expectations'
                ],
                [
                    'step' => 2,
                    'title' => 'One-on-One Coaching',
                    'description' => 'Deep dive into specific issues and challenges',
                    'owner' => 'HR Coordinator / Coach',
                    'deadline' => 'Day 3',
                    'success_criteria' => 'Root causes understood, support plan identified'
                ]
            ],
            'key_deliverables' => ['Written expectations', 'Support plan', 'Timeline agreement']
        ];
        
        // PHASE 2: Implementation (Days 8-45)
        $phase2_actions = [];
        
        if ($hasAbsenteeism) {
            $phase2_actions[] = [
                'step' => 1,
                'title' => 'Attendance Improvement Plan Implementation',
                'description' => 'Execute structured attendance tracking and weekly check-ins',
                'owner' => 'Manager',
                'deadline' => 'Days 8-45 (weekly)',
                'success_criteria' => 'Maximum 1 absence per 2 weeks, improvement trajectory visible'
            ];
        }
        
        if ($hasLowAppraisal) {
            $phase2_actions[] = [
                'step' => 2,
                'title' => 'Performance Development Training',
                'description' => 'Enroll and participate in structured skills training',
                'owner' => 'Training Department',
                'deadline' => 'Days 8-38 (2x per week)',
                'success_criteria' => 'Complete all sessions, pass assessments'
            ];
            
            $phase2_actions[] = [
                'step' => 3,
                'title' => 'Mentoring Sessions',
                'description' => 'Regular 1-on-1 mentoring with high-performer',
                'owner' => 'Assigned Mentor',
                'deadline' => 'Days 8-45 (2x per week)',
                'success_criteria' => 'Mentor reports skill improvements'
            ];
        }
        
        if ($hasLowFeedback) {
            $phase2_actions[] = [
                'step' => count($phase2_actions) + 1,
                'title' => 'Soft Skills Development',
                'description' => 'Interactive training on communication and collaboration',
                'owner' => 'Training Department',
                'deadline' => 'Days 8-38 (weekly sessions)',
                'success_criteria' => 'Complete program, positive peer feedback'
            ];
        }
        
        $actionPlan['phases'][] = [
            'phase_name' => 'Phase 2: Active Improvement (Weeks 2-6)',
            'duration_days' => '8-45',
            'objectives' => [
                'Execute all improvement initiatives',
                'Track progress against targets',
                'Provide regular feedback and support'
            ],
            'actions' => $phase2_actions,
            'key_deliverables' => ['Weekly progress reports', 'Training completion certificates', 'Mentor feedback']
        ];
        
        // PHASE 3: Monitoring & Assessment (Days 46-90)
        $actionPlan['phases'][] = [
            'phase_name' => 'Phase 3: Monitoring & Final Assessment',
            'duration_days' => '46-90',
            'objectives' => [
                'Maintain improvements',
                'Complete advanced training if needed',
                'Final assessment and decision'
            ],
            'actions' => [
                [
                    'step' => 1,
                    'title' => 'Continued Monitoring',
                    'description' => 'Manager continues weekly check-ins and metric tracking',
                    'owner' => 'Manager',
                    'deadline' => 'Weekly through Day 90',
                    'success_criteria' => 'Sustained improvement in all areas'
                ],
                [
                    'step' => 2,
                    'title' => 'Final Assessment Meeting',
                    'description' => 'Comprehensive review of improvement and performance',
                    'owner' => 'Manager + HR',
                    'deadline' => 'Day 85',
                    'success_criteria' => 'Document findings and decision'
                ]
            ],
            'key_deliverables' => ['Final assessment report', 'Performance decision', 'Future development plan']
        ];
        
        // Success Metrics
        $actionPlan['success_metrics'] = [
            'attendance' => [
                'target' => 'Maximum 1 absence per 2 weeks',
                'tracking' => 'Weekly'
            ],
            'performance' => [
                'target' => 'Appraisal score improvement by 0.5+ points',
                'tracking' => 'Monthly'
            ],
            'feedback' => [
                'target' => '360 feedback improvement in next evaluation',
                'tracking' => 'Monthly 1-on-1s'
            ]
        ];
        
        // Timeline Summary
        $actionPlan['timeline_summary'] = [
            [
                'date_range' => 'Week 1',
                'key_events' => ['Initial meeting', 'Coaching session', 'Expectations set']
            ],
            [
                'date_range' => 'Weeks 2-6',
                'key_events' => ['Training starts', 'Mentoring begins', 'Weekly check-ins']
            ],
            [
                'date_range' => 'Weeks 7-12',
                'key_events' => ['Final assessments', 'Continued monitoring', 'Decision point']
            ]
        ];
        
        return $actionPlan;
    }

    /**
     * Get a comprehensive performance assessment for an employee
     */
    public function getPerformanceAssessment($employee_id) {
        $query = "SELECT e.employee_id, e.full_name, e.department, e.position, e.employment_status
                 FROM employees e
                 WHERE e.employee_id = ?";
        
        $employee = $this->db->fetchOne($query, [$employee_id], 'i');
        
        if (!$employee) {
            return null;
        }
        
        // Get latest appraisal score
        $query = "SELECT overall_score, review_date FROM pm_appraisals 
                 WHERE employee_id = ? ORDER BY review_date DESC LIMIT 1";
        $latestAppraisal = $this->db->fetchOne($query, [$employee_id], 'i');
        
        $performanceScore = $latestAppraisal['overall_score'] ?? 0;
        
        return [
            'employee_id' => $employee['employee_id'],
            'full_name' => $employee['full_name'],
            'department' => $employee['department'],
            'position' => $employee['position'],
            'performance_score' => round($performanceScore, 2),
            'performance_status' => $performanceScore >= 4 ? 'Excellent' : ($performanceScore >= 3.5 ? 'Good' : ($performanceScore >= 3 ? 'Satisfactory' : ($performanceScore >= 2 ? 'Below Average' : 'Poor'))),
            'root_causes' => $this->getPerformanceRootCauses($employee_id),
            'recommended_actions' => $this->getRecommendedActions($employee_id)
        ];
    }
}

