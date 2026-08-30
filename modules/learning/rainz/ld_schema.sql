-- ============================================================
-- LEARNING & DEVELOPMENT MODULE — DATABASE SCHEMA
-- Compiled from learning-project-structure.md
-- Convention: all tables prefixed `ld_`, singular naming
-- Charset: utf8mb4 (supports Tagalog content + emoji-safe)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hrms`
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `hrms`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. CORE CONTENT ENTITIES
-- ============================================================

CREATE TABLE ld_course (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,          -- primary owner; co-ownership via ld_course_instructor
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail_path VARCHAR(255),
    category VARCHAR(100),
    status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
    start_date DATE NULL,
    enrollment_deadline DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_course_instructor (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    role ENUM('owner','co-instructor') NOT NULL DEFAULT 'owner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_course_instructor (course_id, instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_course_version (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    snapshot JSON NOT NULL,                        -- full course structure at time of publish
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_module (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT UNSIGNED DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_lesson (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('video','text','file','mixed') NOT NULL DEFAULT 'text',
    content_body TEXT NULL,                         -- rich text/HTML, when type includes 'text'
    video_url VARCHAR(500) NULL,                     -- embed URL, when type includes 'video'
    order_index INT UNSIGNED DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES ld_module(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_lesson_file (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES ld_lesson(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. QUIZ & EVALUATION ENGINE (shared structure)
-- ============================================================

CREATE TABLE ld_quiz (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 600,   -- default 10 minutes
    passing_score DECIMAL(5,2) NULL,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 2,
    question_count INT UNSIGNED NULL,                       -- null = show all questions in pool
    show_answers_after_submit BOOLEAN NOT NULL DEFAULT FALSE, -- blind by default
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES ld_module(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_evaluation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,                        -- attaches to COURSE, not module
    title VARCHAR(255) NOT NULL,
    duration_seconds INT UNSIGNED NULL,                      -- nullable: untimed is valid
    passing_score DECIMAL(5,2) NULL,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 2,
    question_count INT UNSIGNED NULL,
    show_answers_after_submit BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_evaluation_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT UNSIGNED NOT NULL,
    learner_id INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES ld_evaluation(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shared question bank — used by BOTH Quiz and Evaluation via item_type
CREATE TABLE ld_quiz_question (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('quiz','evaluation') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,                       -- ld_quiz.id OR ld_evaluation.id
    question_text TEXT NOT NULL,
    question_type ENUM('single_choice','multiple_choice','true_false') NOT NULL DEFAULT 'single_choice',
    order_index INT UNSIGNED DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quiz_question_ref (item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_quiz_question_option (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    order_index INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES ld_quiz_question(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_quiz_session (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    item_type ENUM('quiz','evaluation') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,                       -- ld_quiz.id OR ld_evaluation.id
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT UNSIGNED NULL,
    submitted_at TIMESTAMP NULL,
    status ENUM('in_progress','submitted','expired') NOT NULL DEFAULT 'in_progress',
    question_order JSON NULL,                                  -- shuffled question order + shuffled option order, locked at start
    score DECIMAL(5,2) NULL,
    passed BOOLEAN NULL,
    INDEX idx_quiz_session_learner_ref (learner_id, item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_quiz_session_answer (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_session_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_option_id INT UNSIGNED NULL,                      -- nullable if unanswered
    is_marked_for_review BOOLEAN NOT NULL DEFAULT FALSE,
    answered_at TIMESTAMP NULL,
    FOREIGN KEY (quiz_session_id) REFERENCES ld_quiz_session(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES ld_quiz_question(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Legacy/simple attempt record (used by analytics & results summaries)
CREATE TABLE ld_quiz_attempt (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    quiz_id INT UNSIGNED NOT NULL,
    quiz_session_id INT UNSIGNED NULL,
    score DECIMAL(5,2) NOT NULL,
    total_items INT UNSIGNED NOT NULL,
    passed BOOLEAN NOT NULL DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_session_id) REFERENCES ld_quiz_session(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. TRAINING: PROGRAMS, SKILLS, VIDEO CONFERENCE, CALENDAR
-- ============================================================

CREATE TABLE ld_program (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_skill (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    suggested BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_module_skill (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (module_id) REFERENCES ld_module(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES ld_skill(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_module_skill (module_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_course_skill (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES ld_skill(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_course_skill (course_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_video_conference (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NULL,
    program_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    platform ENUM('zoom','google_meet','other') NOT NULL DEFAULT 'google_meet',
    meeting_link VARCHAR(500) NOT NULL,
    scheduled_at DATETIME NOT NULL,
    duration_minutes INT UNSIGNED NULL,
    status ENUM('scheduled','completed','archived') NOT NULL DEFAULT 'scheduled',
    first_reminder_sent BOOLEAN NOT NULL DEFAULT FALSE,
    second_reminder_sent BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE SET NULL,
    FOREIGN KEY (program_id) REFERENCES ld_program(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_conference_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_conference_id INT UNSIGNED NOT NULL,
    learner_id INT UNSIGNED NOT NULL,
    attended BOOLEAN NOT NULL DEFAULT FALSE,
    joined_at TIMESTAMP NULL,
    FOREIGN KEY (video_conference_id) REFERENCES ld_video_conference(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_calendar_event (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,
    type ENUM('program','training','video-conference') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,                       -- points to ld_program.id / ld_video_conference.id / etc.
    event_date DATE NOT NULL,
    event_time TIME NULL,
    duration_minutes INT UNSIGNED NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. LEARNING PATH (mixed-content sequence)
-- ============================================================

CREATE TABLE ld_learning_path (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT UNSIGNED NULL,                             -- learner_id, if assigned to a specific learner
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_learning_path_item (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_path_id INT UNSIGNED NOT NULL,
    item_type ENUM('course','module','lesson','quiz','evaluation','program','video-conference') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    order_index INT UNSIGNED DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    FOREIGN KEY (learning_path_id) REFERENCES ld_learning_path(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. ENROLLMENT, PROGRESS, GRADES
-- ============================================================

CREATE TABLE ld_enrollment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    course_version_id INT UNSIGNED NULL,                       -- locks enrollment to the version started
    status ENUM('invited','enrolled','in_progress','completed','withdrawn') NOT NULL DEFAULT 'enrolled',
    invited_by INT UNSIGNED NULL,                               -- instructor_id, if assign-learner.php created this
    enrolled_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    FOREIGN KEY (course_version_id) REFERENCES ld_course_version(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_learner_course (learner_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    item_type ENUM('module','lesson','quiz','evaluation') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    status ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (enrollment_id) REFERENCES ld_enrollment(id) ON DELETE CASCADE,
    INDEX idx_progress_item (item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_grade (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    final_score DECIMAL(5,2) NOT NULL,
    status ENUM('passed','failed') NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. CERTIFICATE (split: template design vs. per-learner issued record)
-- ============================================================

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

-- ============================================================
-- 7. CATALOG ENGAGEMENT: RATING, COMMENT, NOTE, BOOKMARK, FAVORITE, REQUEST, PREREQUISITE
-- ============================================================

CREATE TABLE ld_rating (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,                          -- 1-5
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_learner_course_rating (learner_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_comment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    parent_comment_id INT UNSIGNED NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active', -- 'archived' = hidden but kept forever
    was_ever_reported BOOLEAN NOT NULL DEFAULT FALSE,             -- permanent audit marker, never resets
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES ld_lesson(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES ld_comment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_note (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    item_type ENUM('course','module','lesson','quiz') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_note_item (item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_bookmark (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_bookmark (learner_id, item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_favorite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_favorite (learner_id, item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_request (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    requested_title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_prerequisite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    required_course_id INT UNSIGNED NULL,
    required_skill_id INT UNSIGNED NULL,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    FOREIGN KEY (required_course_id) REFERENCES ld_course(id) ON DELETE CASCADE,
    FOREIGN KEY (required_skill_id) REFERENCES ld_skill(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. MODERATION (flagged/reported content)
-- ============================================================

CREATE TABLE ld_report (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
    instructor_response TEXT NULL,
    instructor_responded_at TIMESTAMP NULL,
    reviewed_by INT UNSIGNED NULL,                              -- admin_id
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. NOTIFICATIONS, ANNOUNCEMENTS, MESSAGING
-- ============================================================

CREATE TABLE ld_notification (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,                                  -- 'invitation','certificate','report','announcement', etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT UNSIGNED NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_notification_preference (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uniq_user_notif_type (user_id, notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_announcement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    audience ENUM('all','instructor','learner','admin') NOT NULL DEFAULT 'all',
    posted_by INT UNSIGNED NOT NULL,                             -- admin_id
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_message (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NULL,
    body TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_recipient (recipient_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. SETTINGS, AUDIT, DISPLAY PREFERENCES
-- ============================================================

CREATE TABLE ld_setting (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('admin','instructor','learner') NOT NULL,
    action ENUM('create','edit','archive','restore','review') NOT NULL,
    item_type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_item (item_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_display_preference (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    page_size TINYINT UNSIGNED NOT NULL DEFAULT 10,             -- 10 / 20 / 30
    view_mode ENUM('grid','list') NOT NULL DEFAULT 'grid',
    theme ENUM('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. CROSS-MODULE INTEGRATION
-- ============================================================

CREATE TABLE ld_api_key (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,                          -- 'performance-management','employee-management', etc.
    api_key VARCHAR(255) NOT NULL,                                -- store hashed, not plaintext
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_integration_event (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    external_reference_id VARCHAR(255) NOT NULL,                 -- for idempotency checks
    event_type VARCHAR(100) NOT NULL,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_module_external_ref (module_name, external_reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ld_integration_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    direction ENUM('inbound','outbound') NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    status ENUM('success','failed','pending') NOT NULL DEFAULT 'pending',
    payload JSON NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_integration_log_module (module_name, direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. COURSE TEMPLATES
-- ============================================================

CREATE TABLE IF NOT EXISTS ld_course_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    created_by INT UNSIGNED NOT NULL,
    structure_json JSON NOT NULL,
    module_count INT UNSIGNED DEFAULT 0,
    lesson_count INT UNSIGNED DEFAULT 0,
    quiz_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED: default system settings (System Settings section)
-- ============================================================
INSERT INTO ld_setting (setting_key, setting_value) VALUES
    ('report_auto_archive_days', '7'),
    ('default_quiz_duration_seconds', '600'),
    ('default_max_quiz_attempts', '2'),
    ('file_upload_max_mb', '25'),
    ('certificate_default_validity_days', ''),           -- empty = does not expire
    ('site_timezone', 'Asia/Manila'),
    ('enrollment_invitation_expiry_days', '7'),
    ('video_conference_reminder_first_minutes', '30'),
    ('video_conference_reminder_second_minutes', '15');