-- ============================================================
-- WORKFORCE ANALYTICS IMPROVEMENTS — ld_ table gaps
-- Implements: modules/learning/rainz/integration mds/ld-tables-gaps-improvements.md
-- Run once:  mysql -u root hrms < workforce_analytics_improvements.sql
-- (This is a one-time migration — ALTERs are not IF NOT EXISTS guarded.)
-- ============================================================

-- ── Gap 3: compliance tagging on courses ─────────────────────
ALTER TABLE `ld_course`
    ADD COLUMN `compliance_category` VARCHAR(100) NULL AFTER `category`,
    ADD COLUMN `is_compliance_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `compliance_category`;

-- ── Gap 2: per-employee training recommendation record ───────
CREATE TABLE IF NOT EXISTS `ld_training_recommendation` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,          -- = learner_id / em_employees.employee_id
    `course_id` INT UNSIGNED NOT NULL,
    `development_area` VARCHAR(150) NOT NULL,
    `priority_level` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    `source` VARCHAR(50) NOT NULL,                -- 'appraisal' | '360_feedback' | 'skill_gap' | 'job_test'
    `external_reference_id` VARCHAR(255) NULL,    -- ties to pm_training_recommendations for idempotency
    `status` ENUM('recommended','invited','enrolled','completed','declined') NOT NULL DEFAULT 'recommended',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `ld_course`(`id`) ON DELETE CASCADE,
    INDEX `idx_training_reco_employee` (`employee_id`, `status`),
    INDEX `idx_training_reco_area` (`development_area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Gap 4: job test / assessment results (Recruitment) ───────
CREATE TABLE IF NOT EXISTS `ld_assessment_result` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `learner_id` INT UNSIGNED NOT NULL,           -- = employee_id (candidate id at onboarding time)
    `source` VARCHAR(50) NOT NULL,                -- 'recruitment_job_test' | 'onboarding_assessment'
    `assessment_name` VARCHAR(150) NOT NULL,
    `score` DECIMAL(5,2) NULL,
    `result` ENUM('passed','failed','pending') NOT NULL DEFAULT 'pending',
    `skill_ids` JSON NULL,                        -- skills probed by the assessment
    `taken_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_assessment_learner` (`learner_id`),
    INDEX `idx_assessment_source` (`source`, `taken_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Gap 5: per-employee recognition unlock record ────────────
CREATE TABLE IF NOT EXISTS `ld_recognition_unlock` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `learner_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `recognition_category` VARCHAR(100) NOT NULL,
    `external_reference_id` VARCHAR(255) NULL,    -- eer_recognitions.id for idempotency
    `status` ENUM('unlocked','redeemed','expired') NOT NULL DEFAULT 'unlocked',
    `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `redeemed_at` TIMESTAMP NULL,
    FOREIGN KEY (`course_id`) REFERENCES `ld_course`(`id`) ON DELETE CASCADE,
    INDEX `idx_recognition_learner` (`learner_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Gap 6: position/department context on skill snapshots ────
ALTER TABLE `ld_skill_snapshot`
    ADD COLUMN `position_id` INT UNSIGNED NULL AFTER `learner_id`,
    ADD COLUMN `department_id` INT UNSIGNED NULL AFTER `position_id`;

ALTER TABLE `ld_skill_snapshot`
    ADD INDEX `idx_skill_snapshot_learner_date` (`learner_id`, `snapshot_date`),
    ADD INDEX `idx_skill_snapshot_dept` (`department_id`);

-- ── Gap 1: turnover risk / engagement snapshot ───────────────
CREATE TABLE IF NOT EXISTS `ld_engagement_snapshot` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `learner_id` INT UNSIGNED NOT NULL,           -- = em_employees.employee_id
    `snapshot_date` DATE NOT NULL,
    `active_days_30` INT UNSIGNED DEFAULT 0,
    `courses_in_progress` INT UNSIGNED DEFAULT 0,
    `courses_completed_ytd` INT UNSIGNED DEFAULT 0,
    `avg_score` DECIMAL(5,2) NULL,
    `quizzes_started` INT UNSIGNED DEFAULT 0,
    `quizzes_abandoned` INT UNSIGNED DEFAULT 0,
    `days_since_last_activity` INT UNSIGNED NULL,
    `expired_certificates` INT UNSIGNED DEFAULT 0,
    `risk_score` DECIMAL(5,2) NULL,               -- computed composite 0-100
    `risk_factors` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_learner_snapshot` (`learner_id`, `snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Gap 8: attendance beyond video conferences ───────────────
ALTER TABLE `ld_conference_attendance`
    ADD COLUMN `session_type` ENUM('video_conference','in_person','virtual') NOT NULL DEFAULT 'video_conference' AFTER `video_conference_id`,
    ADD COLUMN `event_id` INT UNSIGNED NULL AFTER `session_type`;   -- ld_calendar_event / ld_user_event id for non-video sessions