CREATE TABLE IF NOT EXISTS ld_user_role (
    employee_id INT NOT NULL PRIMARY KEY,
    learning_role ENUM('admin', 'instructor', 'learner') NOT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ld_user_role_role (learning_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;