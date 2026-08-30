-- Course Templates — stores reusable course structures as JSON snapshots
CREATE TABLE IF NOT EXISTS ld_course_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    created_by INT UNSIGNED NOT NULL,
    structure_json JSON NOT NULL,           -- full course structure snapshot
    module_count INT UNSIGNED DEFAULT 0,
    lesson_count INT UNSIGNED DEFAULT 0,
    quiz_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
