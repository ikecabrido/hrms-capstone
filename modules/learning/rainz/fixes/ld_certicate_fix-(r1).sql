-- ============================================================
-- LD_CERTIFICATE RE-IMPORT / MIGRATION SCRIPT
-- Use this ONLY if you already imported the original ld_schema.sql
-- (the version before the certificate table was split into
-- ld_certificate_template + ld_certificate).
--
-- If you have NOT imported ld_schema.sql yet, ignore this file —
-- just import ld_schema.sql directly, it already has the correct
-- split built in.
--
-- WARNING: this drops and recreates ld_certificate and
-- ld_certificate_template. Any existing rows in those two tables
-- will be lost. Since you made a backup already, you're safe —
-- but double check you're running this against the right database.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ld_certificate;
DROP TABLE IF EXISTS ld_certificate_template;

-- What a certificate looks like for a course — instructor-configured, one per course (or reusable).
CREATE TABLE ld_certificate_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NULL,                                -- nullable: a template can be reused across courses
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    template_file VARCHAR(255) NULL,                              -- background/design file for the certificate
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The actual record created per learner when they complete a course.
CREATE TABLE ld_certificate (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    course_version_id INT UNSIGNED NULL,                       -- locks certificate to version completed
    completed_enrollment_id INT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NULL,                               -- which ld_certificate_template was used
    verification_code VARCHAR(64) NOT NULL UNIQUE,              -- for public verify-certificate.php lookup
    file_path VARCHAR(255) NULL,                                  -- the generated/rendered PDF for this learner
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until DATE NULL,                                      -- null = does not expire
    status ENUM('active','archived') NOT NULL DEFAULT 'active', -- "revoked" handled as archived, per no-delete rule
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    FOREIGN KEY (course_version_id) REFERENCES ld_course_version(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_enrollment_id) REFERENCES ld_enrollment(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES ld_certificate_template(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;