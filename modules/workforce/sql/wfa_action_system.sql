-- WFA Action & Intervention System Tables
-- Schema: hr-management
-- Purpose: Track performance improvement plans and interventions without modifying pm_ tables

-- Table 1: Performance Improvement Plans
CREATE TABLE IF NOT EXISTS `wfa_performance_improvement_plans` (
  `pip_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `action_plan` TEXT NOT NULL,
  `status` ENUM('ONGOING', 'COMPLETED', 'FAILED') DEFAULT 'ONGOING',
  `performance_target` DECIMAL(3, 2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT,
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: Actions & Interventions
CREATE TABLE IF NOT EXISTS `wfa_actions` (
  `action_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `pip_id` INT DEFAULT NULL,
  `action_type` ENUM('Training', 'Warning', 'PIP', 'Mentoring', 'Counseling', 'Suspension') DEFAULT 'Training',
  `description` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `assigned_to` INT,
  `due_date` DATE,
  `completion_date` DATE,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_pip_id` (`pip_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`pip_id`) REFERENCES `wfa_performance_improvement_plans`(`pip_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: Action Recommendations (for audit trail)
CREATE TABLE IF NOT EXISTS `wfa_action_recommendations` (
  `recommendation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `detected_issues` JSON NOT NULL,
  `recommended_action` VARCHAR(100) NOT NULL,
  `confidence_score` DECIMAL(3, 2),
  `acknowledged` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_recommended_action` (`recommended_action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: Issue Tracker (for detailed analysis)
CREATE TABLE IF NOT EXISTS `wfa_performance_issues` (
  `issue_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `issue_type` ENUM('Absenteeism', 'Tardiness', 'Low Performance', 'Skill Gap', 'Behavior', 'Other') NOT NULL,
  `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
  `description` TEXT NOT NULL,
  `detected_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved` BOOLEAN DEFAULT FALSE,
  `resolution_date` DATE,
  `resolution_notes` TEXT,
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_issue_type` (`issue_type`),
  INDEX `idx_severity` (`severity`),
  INDEX `idx_resolved` (`resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
