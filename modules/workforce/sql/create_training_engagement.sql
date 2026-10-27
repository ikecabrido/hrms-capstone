CREATE TABLE IF NOT EXISTS wf_training_engagement (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_team ENUM('individual-learner', 'department-team', 'role-group') NOT NULL,
    department VARCHAR(100) NOT NULL,
    training_activity ENUM('course', 'learning-path', 'workshop', 'compliance-training') NOT NULL,
    engagement_status ENUM('active', 'in-progress', 'low', 'inactive') NOT NULL,
    engagement_data TEXT NOT NULL,
    learning_action TEXT NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wf_training_engagement_department (department),
    KEY idx_wf_training_engagement_status (engagement_status),
    KEY idx_wf_training_engagement_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
