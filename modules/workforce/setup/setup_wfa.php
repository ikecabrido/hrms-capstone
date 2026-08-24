<?php
/**
 * Setup WFA Tables - Browser Interface
 */

require_once __DIR__ . '/../../auth/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>WFA Setup</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>WFA (Workforce Analytics) Tables Setup</h1>
    
    <?php
    
    try {
        $db = Database::getInstance()->getConnection();
        
        echo '<h2>Creating Tables...</h2>';
        
        // Performance Actions Table
        echo '<p>Creating wfa_performance_actions table...</p>';
        $db->exec("
            CREATE TABLE IF NOT EXISTS `wfa_performance_actions` (
              `action_id` INT AUTO_INCREMENT PRIMARY KEY,
              `employee_id` INT NOT NULL,
              `action_type` VARCHAR(50) NOT NULL COMMENT 'e.g., coaching, training, improvement_plan',
              `title` VARCHAR(255) NOT NULL,
              `description` TEXT,
              `reason` TEXT COMMENT 'Why this action is recommended',
              `priority` VARCHAR(20) DEFAULT 'MEDIUM' COMMENT 'CRITICAL, HIGH, MEDIUM, LOW',
              `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, ongoing, completed, failed, cancelled',
              `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `created_by` INT,
              `start_date` DATE,
              `target_date` DATE,
              `completed_date` DATE,
              `notes` TEXT,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE,
              FOREIGN KEY (`created_by`) REFERENCES `employees`(`employee_id`) ON DELETE SET NULL,
              INDEX `idx_employee_id` (`employee_id`),
              INDEX `idx_status` (`status`),
              INDEX `idx_priority` (`priority`),
              INDEX `idx_target_date` (`target_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo '<span class="success">✓ wfa_performance_actions created</span><br>';
        
        // Performance Improvement Plans Table
        echo '<p>Creating wfa_performance_improvement_plans table...</p>';
        $db->exec("
            CREATE TABLE IF NOT EXISTS `wfa_performance_improvement_plans` (
              `pip_id` INT AUTO_INCREMENT PRIMARY KEY,
              `action_id` INT NOT NULL UNIQUE,
              `employee_id` INT NOT NULL,
              `start_date` DATE NOT NULL,
              `end_date` DATE NOT NULL,
              `duration_days` INT,
              `target_performance_score` DECIMAL(5,2) COMMENT 'Target performance score (0-100)',
              `target_attendance_percentage` INT COMMENT 'Target attendance percentage',
              `target_feedback_score` DECIMAL(5,2) COMMENT 'Target feedback score (0-100)',
              `current_progress_percentage` INT DEFAULT 0,
              `status` VARCHAR(20) DEFAULT 'active' COMMENT 'active, completed, failed, suspended',
              `notes` TEXT,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              FOREIGN KEY (`action_id`) REFERENCES `wfa_performance_actions`(`action_id`) ON DELETE CASCADE,
              FOREIGN KEY (`employee_id`) REFERENCES `employees`(`employee_id`) ON DELETE CASCADE,
              INDEX `idx_employee_id` (`employee_id`),
              INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo '<span class="success">✓ wfa_performance_improvement_plans created</span><br>';
        
        // PIP Reviews Table
        echo '<p>Creating wfa_pip_reviews table...</p>';
        $db->exec("
            CREATE TABLE IF NOT EXISTS `wfa_pip_reviews` (
              `review_id` INT AUTO_INCREMENT PRIMARY KEY,
              `pip_id` INT NOT NULL,
              `review_date` DATE NOT NULL,
              `reviewer_id` INT,
              `performance_score` DECIMAL(5,2),
              `attendance_percentage` INT,
              `feedback_score` DECIMAL(5,2),
              `overall_progress` VARCHAR(20) COMMENT 'on_track, at_risk, off_track',
              `comments` TEXT,
              `recommendations` TEXT,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`pip_id`) REFERENCES `wfa_performance_improvement_plans`(`pip_id`) ON DELETE CASCADE,
              FOREIGN KEY (`reviewer_id`) REFERENCES `employees`(`employee_id`) ON DELETE SET NULL,
              INDEX `idx_pip_id` (`pip_id`),
              INDEX `idx_review_date` (`review_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo '<span class="success">✓ wfa_pip_reviews created</span><br>';
        
        // Insert sample data
        echo '<h2>Inserting Sample Data...</h2>';
        
        $stmt = $db->prepare("
            INSERT INTO `wfa_performance_actions` 
            (`employee_id`, `action_type`, `title`, `description`, `reason`, `priority`, `status`, `created_by`, `start_date`, `target_date`, `notes`)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $actions = [
            [3, 'coaching', '30-Day Attendance Improvement Plan', 'Improve overall attendance and punctuality', 'High absenteeism rate', 'CRITICAL', 'pending', 1, '2026-04-08', '2026-05-08', 'Focus on consistency and reliability'],
            [3, 'training', 'Coaching Session', 'One-on-one coaching to address behavioral concerns', 'Behavioral feedback from assessment', 'CRITICAL', 'pending', 1, '2026-04-08', '2026-04-30', 'Schedule with HR manager'],
            [3, 'training', 'Customer Service Excellence Workshop', 'Improve customer interaction skills', 'Recent customer complaints', 'HIGH', 'pending', 1, '2026-04-08', '2026-05-15', 'Register in online course'],
            [3, 'coaching', 'Communication Skills Development', 'Enhance written and verbal communication', 'Feedback from supervisor', 'HIGH', 'pending', 1, '2026-04-08', '2026-06-08', 'Monthly coaching sessions'],
            [3, 'improvement_plan', 'Productivity Goals', 'Increase task completion rate by 20%', 'Below target productivity metrics', 'HIGH', 'pending', 1, '2026-04-08', '2026-05-08', 'Weekly check-ins'],
            [3, 'training', 'Time Management Course', 'Better prioritization and planning skills', 'Missing deadlines', 'HIGH', 'pending', 1, '2026-04-08', '2026-05-22', 'Online course - 4 weeks'],
            [3, 'coaching', 'Conflict Resolution Workshop', 'Learn to handle workplace conflicts', 'Recent team conflict incident', 'MEDIUM', 'pending', 1, '2026-04-15', '2026-05-30', 'Group workshop'],
            [3, 'training', 'Professional Development Plan', 'Career path discussion and planning', 'Career advancement request', 'MEDIUM', 'pending', 1, '2026-04-08', '2026-06-30', 'Quarterly reviews'],
            [3, 'improvement_plan', 'Performance Metrics Review', 'Monthly review of KPIs', 'Ongoing monitoring required', 'MEDIUM', 'pending', 1, '2026-04-08', '2026-05-08', 'Monthly reports']
        ];
        
        foreach ($actions as $action) {
            $stmt->execute($action);
        }
        
        echo '<span class="success">✓ Inserted 9 sample actions for employee 3</span><br>';
        
        echo '<h2 class="success">✅ All tables created and seeded successfully!</h2>';
        echo '<p><a href="/capstone_hr_management_system/workforce/workforce.php">← Back to Workforce</a></p>';
        
    } catch (Exception $e) {
        echo '<h2 class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    ?>
</body>
</html>
