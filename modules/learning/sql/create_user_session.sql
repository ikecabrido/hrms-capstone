-- Signed-in session registry for the Learning module.
-- Powers the "Security" block on the profile pages: last login, active
-- devices, and "sign out other sessions".
CREATE TABLE IF NOT EXISTS ld_user_session (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    user_id INT NULL,
    session_token CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    device_label VARCHAR(120) NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ld_user_session_token (session_token),
    KEY idx_ld_user_session_employee (employee_id, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
