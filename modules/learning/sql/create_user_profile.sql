-- Per-employee profile extras for the Learning module:
-- a learner-settable bio and an uploaded profile picture.
-- Note: modules/learning/classes/UserProfile.php also creates this table
-- lazily with CREATE TABLE IF NOT EXISTS, so importing this file is
-- optional but recommended for production parity.
CREATE TABLE IF NOT EXISTS ld_user_profile (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    bio TEXT NULL,
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ld_user_profile_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
