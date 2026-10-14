-- Goal Setting and Calendar Events database setup
-- Select the HRMS database before importing this file.

CREATE TABLE IF NOT EXISTS pm_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    department INT DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    supervisor_id BIGINT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    goal_title VARCHAR(255) NOT NULL,
    goal_description TEXT NOT NULL,
    goal_category VARCHAR(100) DEFAULT 'Performance',
    goal_type VARCHAR(100) DEFAULT 'Individual Goal',
    priority_level VARCHAR(20) DEFAULT 'Medium',
    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    target_completion_percentage TINYINT UNSIGNED DEFAULT 0,
    kpi_name VARCHAR(255) DEFAULT NULL,
    kpi_target VARCHAR(255) DEFAULT NULL,
    expected_outcome TEXT DEFAULT NULL,
    smart_notes TEXT DEFAULT NULL,
    progress_percentage TINYINT UNSIGNED DEFAULT 0,
    progress_notes TEXT DEFAULT NULL,
    latest_update_date DATE DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'Draft',
    approval_comment TEXT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_goals_employee (employee_id),
    INDEX idx_goals_status (status),
    INDEX idx_goals_priority (priority_level),
    INDEX idx_goals_category (goal_category),
    INDEX idx_goals_due_date (due_date),
    INDEX idx_goals_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    progress_percentage TINYINT UNSIGNED NOT NULL,
    progress_notes TEXT DEFAULT NULL,
    update_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_progress_goal (goal_id),
    CONSTRAINT fk_goal_progress_goal FOREIGN KEY (goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    supervisor_id BIGINT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    decision VARCHAR(20) NOT NULL,
    comments TEXT DEFAULT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_approvals_goal (goal_id),
    CONSTRAINT fk_goal_approvals_goal FOREIGN KEY (goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    uploaded_by VARCHAR(50) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_attachments_goal (goal_id),
    CONSTRAINT fk_goal_attachments_goal FOREIGN KEY (goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    user_id VARCHAR(50) DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_comments_goal (goal_id),
    CONSTRAINT fk_goal_comments_goal FOREIGN KEY (goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    created_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_history_goal (goal_id),
    CONSTRAINT fk_goal_history_goal FOREIGN KEY (goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT DEFAULT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL DEFAULT 'Other HR Event',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    employee_name VARCHAR(255) DEFAULT NULL,
    department INT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    related_goal_id INT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
    reminder VARCHAR(50) NOT NULL DEFAULT 'None',
    created_by VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_goal_events_dates (start_date, end_date),
    INDEX idx_goal_events_type (event_type),
    INDEX idx_goal_events_employee (employee_id),
    INDEX idx_goal_events_goal (related_goal_id),
    CONSTRAINT fk_goal_events_goal FOREIGN KEY (related_goal_id)
        REFERENCES pm_goals(goal_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add employee_id to an existing event table without deleting existing events.
SET @goal_events_employee_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pm_goal_events'
      AND COLUMN_NAME = 'employee_id'
);
SET @goal_events_employee_id_sql = IF(
    @goal_events_employee_id_exists = 0,
    'ALTER TABLE pm_goal_events ADD COLUMN employee_id BIGINT DEFAULT NULL AFTER event_id',
    'SELECT 1'
);
PREPARE goal_events_employee_id_migration FROM @goal_events_employee_id_sql;
EXECUTE goal_events_employee_id_migration;
DEALLOCATE PREPARE goal_events_employee_id_migration;

SET @goal_events_employee_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pm_goal_events'
      AND INDEX_NAME = 'idx_goal_events_employee'
);
SET @goal_events_employee_index_sql = IF(
    @goal_events_employee_index_exists = 0,
    'ALTER TABLE pm_goal_events ADD INDEX idx_goal_events_employee (employee_id)',
    'SELECT 1'
);
PREPARE goal_events_employee_index_migration FROM @goal_events_employee_index_sql;
EXECUTE goal_events_employee_index_migration;
DEALLOCATE PREPARE goal_events_employee_index_migration;
