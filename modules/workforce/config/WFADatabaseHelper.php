<?php
/**
 * Workforce Analytics (WFA) Database Helper
 * Handles all database operations for workforce analytics tables
 * Uses wfa_ prefix convention
 * 
 * @version 1.0.0
 * @date 2026-03-21
 */

class WFADatabaseHelper {
    private $pdo;
    private $logger;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Insert employee metrics (daily KPIs)
     */
    public function insertEmployeeMetrics($metrics) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_employee_metrics 
                (metric_date, total_employees, total_teachers, total_staff, 
                 new_hires_this_year, average_salary, average_performance_score, 
                 total_departments, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    total_employees = VALUES(total_employees),
                    total_teachers = VALUES(total_teachers),
                    total_staff = VALUES(total_staff),
                    new_hires_this_year = VALUES(new_hires_this_year),
                    average_salary = VALUES(average_salary),
                    average_performance_score = VALUES(average_performance_score),
                    total_departments = VALUES(total_departments),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                date('Y-m-d'),
                $metrics['total_employees'] ?? 0,
                $metrics['total_teachers'] ?? 0,
                $metrics['total_staff'] ?? 0,
                $metrics['new_hires_this_year'] ?? 0,
                $metrics['average_salary'] ?? 0.00,
                $metrics['average_performance_score'] ?? 0.00,
                $metrics['total_departments'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log("WFA Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert department analytics
     */
    public function insertDepartmentAnalytics($department, $analytics) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_department_analytics 
                (department, employee_count, average_salary, average_performance_score,
                 headcount_target, vacancy_count, average_tenure_years, metric_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    employee_count = VALUES(employee_count),
                    average_salary = VALUES(average_salary),
                    average_performance_score = VALUES(average_performance_score),
                    vacancy_count = VALUES(vacancy_count),
                    average_tenure_years = VALUES(average_tenure_years),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                $department,
                $analytics['employee_count'] ?? 0,
                $analytics['average_salary'] ?? 0.00,
                $analytics['average_performance_score'] ?? 0.00,
                $analytics['headcount_target'] ?? null,
                $analytics['vacancy_count'] ?? 0,
                $analytics['average_tenure_years'] ?? 0.00,
                date('Y-m-d')
            ]);
        } catch (Exception $e) {
            error_log("WFA Department Analytics Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record employee attrition/separation
     */
    public function recordAttrition($employee_id, $separation_data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_attrition_tracking 
                (employee_id, separation_date, separation_type, department, 
                 tenure_years, reason_for_leaving, exit_interview_completed, rehire_eligible, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $employee_id,
                $separation_data['separation_date'] ?? date('Y-m-d'),
                $separation_data['separation_type'] ?? 'resigned',
                $separation_data['department'] ?? null,
                $separation_data['tenure_years'] ?? 0,
                $separation_data['reason_for_leaving'] ?? null,
                $separation_data['exit_interview_completed'] ?? false,
                $separation_data['rehire_eligible'] ?? false
            ]);
        } catch (Exception $e) {
            error_log("WFA Attrition Record Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update risk assessment for employee
     */
    public function updateRiskAssessment($employee_id, $risk_data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_risk_assessment 
                (employee_id, risk_level, risk_score, risk_factors,
                 low_performance_flag, high_absence_flag, low_engagement_flag,
                 performance_score, absence_days, tenure_months, last_assessment_date, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    risk_level = VALUES(risk_level),
                    risk_score = VALUES(risk_score),
                    risk_factors = VALUES(risk_factors),
                    low_performance_flag = VALUES(low_performance_flag),
                    high_absence_flag = VALUES(high_absence_flag),
                    low_engagement_flag = VALUES(low_engagement_flag),
                    performance_score = VALUES(performance_score),
                    absence_days = VALUES(absence_days),
                    tenure_months = VALUES(tenure_months),
                    last_assessment_date = VALUES(last_assessment_date),
                    updated_at = NOW()
            ");
            
            $factors = $risk_data['risk_factors'] ?? [];
            $factors_json = json_encode($factors);
            
            return $stmt->execute([
                $employee_id,
                $risk_data['risk_level'] ?? 'low',
                $risk_data['risk_score'] ?? 0,
                $factors_json,
                $risk_data['low_performance_flag'] ?? false,
                $risk_data['high_absence_flag'] ?? false,
                $risk_data['low_engagement_flag'] ?? false,
                $risk_data['performance_score'] ?? 0,
                $risk_data['absence_days'] ?? 0,
                $risk_data['tenure_months'] ?? 0,
                date('Y-m-d'),
                $risk_data['notes'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("WFA Risk Assessment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert diversity metrics
     */
    public function insertDiversityMetrics($category, $category_value, $metrics) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_diversity_metrics 
                (metric_date, diversity_category, category_value, employee_count, 
                 percentage, average_salary, average_performance, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    employee_count = VALUES(employee_count),
                    percentage = VALUES(percentage),
                    average_salary = VALUES(average_salary),
                    average_performance = VALUES(average_performance),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                date('Y-m-d'),
                $category,
                $category_value,
                $metrics['employee_count'] ?? 0,
                $metrics['percentage'] ?? 0.00,
                $metrics['average_salary'] ?? 0.00,
                $metrics['average_performance'] ?? 0.00
            ]);
        } catch (Exception $e) {
            error_log("WFA Diversity Metrics Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert salary statistics
     */
    public function insertSalaryStatistics($department, $salary_data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_salary_statistics 
                (metric_date, department, employee_count, min_salary, max_salary, 
                 average_salary, median_salary, total_payroll, salary_variance, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    employee_count = VALUES(employee_count),
                    min_salary = VALUES(min_salary),
                    max_salary = VALUES(max_salary),
                    average_salary = VALUES(average_salary),
                    median_salary = VALUES(median_salary),
                    total_payroll = VALUES(total_payroll),
                    salary_variance = VALUES(salary_variance),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                date('Y-m-d'),
                $department,
                $salary_data['employee_count'] ?? 0,
                $salary_data['min_salary'] ?? 0,
                $salary_data['max_salary'] ?? 0,
                $salary_data['average_salary'] ?? 0,
                $salary_data['median_salary'] ?? 0,
                $salary_data['total_payroll'] ?? 0,
                $salary_data['salary_variance'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log("WFA Salary Statistics Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert performance distribution
     */
    public function insertPerformanceDistribution($performance_level, $distribution_data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_performance_distribution 
                (metric_date, performance_level, score_range_min, score_range_max, 
                 employee_count, percentage, department_breakdown, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    employee_count = VALUES(employee_count),
                    percentage = VALUES(percentage),
                    department_breakdown = VALUES(department_breakdown),
                    updated_at = NOW()
            ");
            
            $dept_breakdown = json_encode($distribution_data['department_breakdown'] ?? []);
            
            return $stmt->execute([
                date('Y-m-d'),
                $performance_level,
                $distribution_data['score_range_min'] ?? 0,
                $distribution_data['score_range_max'] ?? 5,
                $distribution_data['employee_count'] ?? 0,
                $distribution_data['percentage'] ?? 0,
                $dept_breakdown
            ]);
        } catch (Exception $e) {
            error_log("WFA Performance Distribution Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get metrics for dashboard
     */
    public function getDashboardMetrics($date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            $stmt = $this->pdo->prepare("
                SELECT * FROM wfa_employee_metrics 
                WHERE metric_date = ?
                LIMIT 1
            ");
            $stmt->execute([$date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WFA Get Dashboard Metrics Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get high-risk employees
     */
    public function getHighRiskEmployees($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT employee_id, risk_score, risk_level, risk_factors, 
                       performance_score, absence_days, tenure_months
                FROM wfa_risk_assessment
                WHERE DATE(updated_at) = CURDATE()
                AND risk_level IN ('high', 'medium')
                ORDER BY risk_score DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WFA Get High-Risk Employees Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attrition metrics
     */
    public function getAttritionMetrics($start_date = null, $end_date = null) {
        try {
            $start_date = $start_date ?? date('Y-01-01');
            $end_date = $end_date ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    separation_type,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wfa_attrition_tracking WHERE separation_date BETWEEN ? AND ?), 2) as percentage,
                    ROUND(AVG(tenure_years), 2) as avg_tenure
                FROM wfa_attrition_tracking
                WHERE separation_date BETWEEN ? AND ?
                GROUP BY separation_type
            ");
            $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WFA Get Attrition Metrics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department analytics
     */
    public function getDepartmentAnalytics($department = null, $date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            
            if ($department) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM wfa_department_analytics
                    WHERE department = ? AND metric_date = ?
                    LIMIT 1
                ");
                $stmt->execute([$department, $date]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM wfa_department_analytics
                    WHERE metric_date = ?
                    ORDER BY employee_count DESC
                ");
                $stmt->execute([$date]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("WFA Get Department Analytics Error: " . $e->getMessage());
            return $department ? null : [];
        }
    }

    /**
     * Log audit action
     */
    public function logAuditAction($user_id, $action, $resource_type = null, $resource_id = null, $details = null, $ip_address = null) {
        try {
            $ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? '';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_audit_log 
                (user_id, action, resource_type, resource_id, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $user_id,
                $action,
                $resource_type,
                $resource_id,
                $details,
                $ip_address
            ]);
        } catch (Exception $e) {
            error_log("WFA Audit Log Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save custom filter
     */
    public function saveCustomFilter($user_id, $filter_name, $filter_config, $is_public = false) {
        try {
            $filter_json = json_encode($filter_config);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_custom_filters 
                (user_id, filter_name, filter_config, is_public, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    filter_config = VALUES(filter_config),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                $user_id,
                $filter_name,
                $filter_json,
                $is_public ? 1 : 0
            ]);
        } catch (Exception $e) {
            error_log("WFA Save Custom Filter Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get custom filters for user
     */
    public function getCustomFilters($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, filter_name, filter_config, is_public, created_at
                FROM wfa_custom_filters
                WHERE user_id = ? OR is_public = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON config for each filter
            foreach ($results as &$result) {
                $result['filter_config'] = json_decode($result['filter_config'], true);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("WFA Get Custom Filters Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save report snapshot
     */
    public function saveReportSnapshot($report_name, $report_type, $generated_by, $filters_applied, $report_data) {
        try {
            $filters_json = json_encode($filters_applied);
            $data_json = json_encode($report_data);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO wfa_reports 
                (report_name, report_type, report_date, generated_by, filters_applied, report_data, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            return $stmt->execute([
                $report_name,
                $report_type,
                date('Y-m-d'),
                $generated_by,
                $filters_json,
                $data_json
            ]);
        } catch (Exception $e) {
            error_log("WFA Save Report Snapshot Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent reports
     */
    public function getRecentReports($limit = 10, $report_type = null) {
        try {
            if ($report_type) {
                $stmt = $this->pdo->prepare("
                    SELECT id, report_name, report_type, report_date, generated_by, created_at
                    FROM wfa_reports
                    WHERE report_type = ?
                    ORDER BY created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$report_type, $limit]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT id, report_name, report_type, report_date, generated_by, created_at
                    FROM wfa_reports
                    ORDER BY created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WFA Get Recent Reports Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if tables exist
     */
    public function tablesExist() {
        try {
            $stmt = $this->pdo->query("
                SHOW TABLES LIKE 'wfa_%'
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return count($tables) >= 15; // Should have at least 15 wfa_ tables
        } catch (Exception $e) {
            error_log("WFA Check Tables Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get table statistics
     */
    public function getTableStatistics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    TABLE_NAME,
                    TABLE_ROWS as row_count,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME LIKE 'wfa_%'
                ORDER BY TABLE_NAME
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WFA Get Table Statistics Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
