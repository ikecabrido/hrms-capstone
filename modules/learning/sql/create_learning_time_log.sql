CREATE TABLE IF NOT EXISTS ld_study_time_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    session_token CHAR(36) NOT NULL,
    sequence_no INT UNSIGNED NOT NULL,
    active_seconds SMALLINT UNSIGNED NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ld_study_time_session_sequence (session_token, sequence_no),
    KEY idx_ld_study_time_learner (learner_id, recorded_at),
    KEY idx_ld_study_time_enrollment (enrollment_id),
    CONSTRAINT fk_ld_study_time_enrollment
        FOREIGN KEY (enrollment_id) REFERENCES ld_enrollment (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;