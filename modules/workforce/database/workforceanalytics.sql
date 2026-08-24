-- =====================================================
-- Workforce Analytics (WFA) Database Schema
-- Integrated with HR Management System
-- =====================================================

-- =====================================================
-- 1. WFA Employee Metrics - Real-time Employee Count & KPIs
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_employee_metrics` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `total_employees` INT DEFAULT 0,
  `total_teachers` INT DEFAULT 0,
  `total_staff` INT DEFAULT 0,
  `new_hires_this_year` INT DEFAULT 0,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance_score` DECIMAL(5, 2) DEFAULT 0.00,
  `total_departments` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  UNIQUE KEY `unique_metric_date` (`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. WFA Department Analytics - Department-level Statistics
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_department_analytics` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department` VARCHAR(100) NOT NULL,
  `employee_count` INT DEFAULT 0,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance_score` DECIMAL(5, 2) DEFAULT 0.00,
  `headcount_target` INT DEFAULT NULL,
  `vacancy_count` INT DEFAULT 0,
  `average_tenure_years` DECIMAL(5, 2) DEFAULT 0.00,
  `metric_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_department` (`department`),
  INDEX `idx_metric_date` (`metric_date`),
  UNIQUE KEY `unique_dept_date` (`department`, `metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. WFA Attrition Tracking - Turnover & Separation Analysis
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_attrition_tracking` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(50) NOT NULL,
  `separation_date` DATE NOT NULL,
  `separation_type` ENUM('resigned', 'retired', 'terminated', 'other') DEFAULT 'resigned',
  `department` VARCHAR(100),
  `tenure_years` DECIMAL(5, 2),
  `reason_for_leaving` TEXT,
  `exit_interview_completed` BOOLEAN DEFAULT FALSE,
  `rehire_eligible` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_separation_date` (`separation_date`),
  INDEX `idx_separation_type` (`separation_type`),
  INDEX `idx_employee_id` (`employee_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. WFA Monthly Attrition - Attrition Trends by Month
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_monthly_attrition` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `year_month` DATE NOT NULL,
  `total_separations` INT DEFAULT 0,
  `voluntary_separations` INT DEFAULT 0,
  `involuntary_separations` INT DEFAULT 0,
  `attrition_rate_percent` DECIMAL(5, 2) DEFAULT 0.00,
  `average_tenure_departing` DECIMAL(5, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_year_month` (`year_month`),
  INDEX `idx_year_month` (`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. WFA Diversity Metrics - Gender, Age, Department Diversity
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_diversity_metrics` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `department` VARCHAR(100) NOT NULL DEFAULT 'All',
  `diversity_category` VARCHAR(50) NOT NULL COMMENT 'gender, age_group, department',
  `category_value` VARCHAR(100) NOT NULL COMMENT 'Male/Female/Other, 18-25/26-35/etc, Department Name',
  `employee_count` INT DEFAULT 0,
  `percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance` DECIMAL(5, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_department` (`department`),
  INDEX `idx_diversity_category` (`diversity_category`),
  UNIQUE KEY `unique_diversity` (`metric_date`, `department`, `diversity_category`, `category_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 6. WFA Risk Assessment - At-Risk Employee Prediction
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_risk_assessment` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(50) NOT NULL,
  `risk_level` ENUM('high', 'medium', 'low') DEFAULT 'low',
  `risk_score` DECIMAL(5, 2) DEFAULT 0.00 COMMENT '0-100 score',
  `risk_factors` JSON COMMENT 'Array of risk factors',
  `low_performance_flag` BOOLEAN DEFAULT FALSE COMMENT 'Performance < 3.0',
  `high_absence_flag` BOOLEAN DEFAULT FALSE COMMENT 'Absence days > 15',
  `low_engagement_flag` BOOLEAN DEFAULT FALSE,
  `recent_complaints_flag` BOOLEAN DEFAULT FALSE,
  `performance_score` DECIMAL(5, 2),
  `absence_days` INT DEFAULT 0,
  `tenure_months` INT DEFAULT 0,
  `last_assessment_date` DATE,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_risk_level` (`risk_level`),
  INDEX `idx_risk_score` (`risk_score`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 7. WFA Performance Distribution - Performance Level Tracking
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_performance_distribution` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `performance_level` VARCHAR(50) NOT NULL COMMENT 'Excellent, Good, Average, Below Average, Poor',
  `score_range_min` DECIMAL(5, 2),
  `score_range_max` DECIMAL(5, 2),
  `employee_count` INT DEFAULT 0,
  `percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `department_breakdown` JSON COMMENT 'Department distribution',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_performance_level` (`performance_level`),
  UNIQUE KEY `unique_performance_dist` (`metric_date`, `performance_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 8. WFA Salary Statistics - Salary Analysis by Department
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_salary_statistics` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `employee_count` INT DEFAULT 0,
  `min_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `max_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `median_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `total_payroll` DECIMAL(15, 2) DEFAULT 0.00,
  `salary_variance` DECIMAL(12, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_department` (`department`),
  UNIQUE KEY `unique_salary_stats` (`metric_date`, `department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 9. WFA Tenure Analysis - Employee Tenure Distribution
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_tenure_analysis` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `tenure_bracket` VARCHAR(50) NOT NULL COMMENT '0-1yr, 1-3yr, 3-5yr, 5-10yr, 10+ yr',
  `employee_count` INT DEFAULT 0,
  `percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance_score` DECIMAL(5, 2) DEFAULT 0.00,
  `department_breakdown` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_tenure_bracket` (`tenure_bracket`),
  UNIQUE KEY `unique_tenure_analysis` (`metric_date`, `tenure_bracket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 10. WFA Age Distribution - Age Group Demographics
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_age_distribution` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `age_group` VARCHAR(50) NOT NULL COMMENT '18-25, 26-35, 36-45, 46-55, 56+',
  `employee_count` INT DEFAULT 0,
  `percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance_score` DECIMAL(5, 2) DEFAULT 0.00,
  `department_breakdown` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_age_group` (`age_group`),
  UNIQUE KEY `unique_age_distribution` (`metric_date`, `age_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 11. WFA Gender Distribution - Gender Demographics
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_gender_distribution` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `metric_date` DATE NOT NULL,
  `gender` VARCHAR(50) NOT NULL COMMENT 'Male, Female, Other',
  `employee_count` INT DEFAULT 0,
  `percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `average_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `average_performance_score` DECIMAL(5, 2) DEFAULT 0.00,
  `department_breakdown` JSON,
  `position_breakdown` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_metric_date` (`metric_date`),
  INDEX `idx_gender` (`gender`),
  UNIQUE KEY `unique_gender_distribution` (`metric_date`, `gender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 12. WFA Reports - Snapshot of Generated Reports
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_reports` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `report_name` VARCHAR(255) NOT NULL,
  `report_type` VARCHAR(50) NOT NULL COMMENT 'dashboard, attrition, diversity, performance, salary, custom',
  `report_date` DATE NOT NULL,
  `generated_by` INT,
  `filters_applied` JSON COMMENT 'Department, date range, etc.',
  `report_data` LONGTEXT COMMENT 'JSON data snapshot',
  `file_path` VARCHAR(255) COMMENT 'Path to exported file if any',
  `export_format` VARCHAR(20) COMMENT 'CSV, PDF, Excel',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_report_type` (`report_type`),
  INDEX `idx_report_date` (`report_date`),
  INDEX `idx_generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 13. WFA Custom Filters - Saved Filter Configurations
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_custom_filters` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `filter_name` VARCHAR(255) NOT NULL,
  `user_id` INT,
  `filter_config` JSON COMMENT 'Department, employment type, date range',
  `is_public` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_filter_name` (`filter_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 14. WFA Audit Log - Analytics System Audit Trail
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_audit_log` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `action` VARCHAR(100) NOT NULL COMMENT 'view_report, generate_report, export_data, update_filter',
  `resource_type` VARCHAR(50) COMMENT 'report, filter, metric',
  `resource_id` INT,
  `details` TEXT,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 15. WFA Headcount Planning - Headcount Forecasting
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_headcount_planning` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department` VARCHAR(100) NOT NULL,
  `fiscal_year` YEAR NOT NULL,
  `planned_headcount` INT DEFAULT 0,
  `actual_headcount` INT DEFAULT 0,
  `variance` INT DEFAULT 0,
  `planned_salary_budget` DECIMAL(15, 2) DEFAULT 0.00,
  `actual_salary_budget` DECIMAL(15, 2) DEFAULT 0.00,
  `budget_variance` DECIMAL(15, 2) DEFAULT 0.00,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_department` (`department`),
  INDEX `idx_fiscal_year` (`fiscal_year`),
  UNIQUE KEY `unique_headcount_plan` (`department`, `fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 16. WFA Skill Gap Analysis - Competency Gaps by Department
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_skill_gap_analysis` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department` VARCHAR(100) NOT NULL,
  `skill_name` VARCHAR(255) NOT NULL,
  `required_proficiency` VARCHAR(50) COMMENT 'Basic, Intermediate, Advanced, Expert',
  `current_proficiency_avg` VARCHAR(50),
  `employees_with_skill` INT DEFAULT 0,
  `employees_needing_training` INT DEFAULT 0,
  `skill_gap_percentage` DECIMAL(5, 2) DEFAULT 0.00,
  `priority_level` ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
  `training_recommendations` TEXT,
  `last_assessed` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_department` (`department`),
  INDEX `idx_skill_name` (`skill_name`),
  INDEX `idx_priority_level` (`priority_level`),
  UNIQUE KEY `unique_skill_gap` (`department`, `skill_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 17. WFA Compensation Analysis - Salary Competitiveness
-- =====================================================
CREATE TABLE IF NOT EXISTS `wfa_compensation_analysis` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `current_avg_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `market_median_salary` DECIMAL(12, 2) DEFAULT 0.00,
  `salary_competitiveness_ratio` DECIMAL(5, 2) COMMENT 'Current/Market %',
  `employee_count` INT DEFAULT 0,
  `salary_range_min` DECIMAL(12, 2),
  `salary_range_max` DECIMAL(12, 2),
  `recommended_adjustment` DECIMAL(12, 2),
  `last_market_review` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_department` (`department`),
  INDEX `idx_position` (`position`),
  UNIQUE KEY `unique_compensation` (`department`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INDEX OPTIMIZATION FOR PERFORMANCE
-- =====================================================

-- Note: Most indexes are already defined in table creation above.
-- The following are composite indexes for enhanced query performance
-- that complement the existing single-column indexes.

-- =====================================================
-- VIEWS FOR COMMON ANALYTICS QUERIES
-- =====================================================

-- View: Current Employee Count by Department
CREATE OR REPLACE VIEW wfa_current_employees_by_dept AS
SELECT 
  e.department,
  COUNT(DISTINCT e.employee_id) as employee_count,
  ROUND(AVG(CAST(pr.rating AS DECIMAL(5,2))), 2) as avg_performance_score
FROM employees e
LEFT JOIN performance_reviews pr ON e.employee_id = pr.employee_id
WHERE e.employment_status = 'Active'
GROUP BY e.department;

-- View: At-Risk Employees Summary
CREATE OR REPLACE VIEW wfa_at_risk_employees_summary AS
SELECT 
  risk_level,
  COUNT(*) as count,
  ROUND(AVG(risk_score), 2) as avg_risk_score,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wfa_risk_assessment WHERE DATE(updated_at) = CURDATE()), 2) as percentage
FROM wfa_risk_assessment
WHERE DATE(updated_at) = CURDATE()
GROUP BY risk_level;

-- View: Department Diversity Summary (Latest Gender Distribution)
CREATE OR REPLACE VIEW wfa_department_diversity AS
SELECT 
  metric_date,
  diversity_category,
  category_value,
  employee_count,
  percentage,
  average_salary
FROM wfa_diversity_metrics
WHERE diversity_category = 'gender';

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert sample at-risk assessment (Optional - for testing)
INSERT IGNORE INTO `wfa_risk_assessment` 
(`employee_id`, `risk_level`, `risk_score`, `risk_factors`, `low_performance_flag`, `high_absence_flag`, `performance_score`, `absence_days`, `tenure_months`)
VALUES 
('EMP001', 'low', 25.50, '["none"]', 0, 0, 4.5, 2, 36),
('EMP002', 'medium', 55.75, '["high_absence", "low_engagement"]', 0, 1, 3.5, 18, 24),
('EMP003', 'high', 78.25, '["low_performance", "high_absence", "tenure"]', 1, 1, 2.5, 20, 6);

-- =====================================================
-- END OF WORKFORCE ANALYTICS SCHEMA
-- =====================================================
