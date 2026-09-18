-- =======================================================================
--  LEARNING & DEVELOPMENT (`ld_`) — SCHEMA ONLY  ·  IMPORT-SAFE
-- =======================================================================
--  Generated  : 2026-09-11 22:39:17
--  Source     : 10.4.32-MariaDB @ 127.0.0.1:3307  db `hrms`
--  Contents   : 57 tables, 428 columns, 130 indexes, 40 constraints
--  Data       : none — DDL only
--
--  HOW TO IMPORT
--    mysql -u root hrms < "ld_tables schema.sql"
--    (or phpMyAdmin > Import > choose this file > Go)
--
--  WHAT IT DOES
--    1) CREATE TABLE IF NOT EXISTS  — only tables that are missing
--    2) for tables that already exist, only the differences are applied:
--         ADD COLUMN      (column missing)
--         MODIFY COLUMN   (type / nullability / default / extra changed)
--         ADD KEY/INDEX   (index missing)
--         ADD CONSTRAINT  (foreign key / check missing)
--         ENGINE / CHARSET (table options changed)
--
--  WHAT IT NEVER DOES
--    * never DROP a table, column, index, constraint or row
--    * never INSERT / UPDATE / DELETE data
--    So it is safe to import repeatedly on a database that already has data.
--
--  Helper objects (`_ld_schema_cols`, `_ld_schema_idx`, `_ld_schema_con`,
--  procedure `_ld_schema_sync`) are dropped again at the end of the run.
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------
-- 1) CREATE THE TABLES THAT DO NOT EXIST YET
-- -----------------------------------------------------------------------

-- ld_announcement
CREATE TABLE IF NOT EXISTS `ld_announcement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `audience` enum('all','instructor','learner','admin') NOT NULL DEFAULT 'all',
  `posted_by` int(10) unsigned NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_api_key
CREATE TABLE IF NOT EXISTS `ld_api_key` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_assessment_result
CREATE TABLE IF NOT EXISTS `ld_assessment_result` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `source` varchar(50) NOT NULL,
  `assessment_name` varchar(150) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `result` enum('passed','failed','pending') NOT NULL DEFAULT 'pending',
  `skill_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skill_ids`)),
  `taken_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assessment_learner` (`learner_id`),
  KEY `idx_assessment_source` (`source`,`taken_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_audit_log
CREATE TABLE IF NOT EXISTS `ld_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role` enum('admin','instructor','learner') NOT NULL,
  `action` enum('create','edit','archive','restore','review') NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_item` (`item_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_bookmark
CREATE TABLE IF NOT EXISTS `ld_bookmark` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bookmark` (`learner_id`,`item_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_calendar_event
CREATE TABLE IF NOT EXISTS `ld_calendar_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `type` enum('program','training','video-conference') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `duration_minutes` int(10) unsigned DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_certificate
CREATE TABLE IF NOT EXISTS `ld_certificate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `course_version_id` int(10) unsigned DEFAULT NULL,
  `completed_enrollment_id` int(10) unsigned NOT NULL,
  `template_id` int(10) unsigned DEFAULT NULL,
  `verification_code` varchar(64) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` date DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `verification_code` (`verification_code`),
  KEY `course_id` (`course_id`),
  KEY `course_version_id` (`course_version_id`),
  KEY `completed_enrollment_id` (`completed_enrollment_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `ld_certificate_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_certificate_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ld_certificate_ibfk_3` FOREIGN KEY (`completed_enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_certificate_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `ld_certificate_template` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_certificate_template
CREATE TABLE IF NOT EXISTS `ld_certificate_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_certificate_template_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_comment
CREATE TABLE IF NOT EXISTS `ld_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `lesson_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `parent_comment_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `was_ever_reported` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `parent_comment_id` (`parent_comment_id`),
  CONSTRAINT `ld_comment_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_comment_ibfk_2` FOREIGN KEY (`parent_comment_id`) REFERENCES `ld_comment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_conference_attendance
CREATE TABLE IF NOT EXISTS `ld_conference_attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `video_conference_id` int(10) unsigned NOT NULL,
  `session_type` enum('video_conference','in_person','virtual') NOT NULL DEFAULT 'video_conference',
  `event_id` int(10) unsigned DEFAULT NULL,
  `learner_id` int(10) unsigned NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_conference_id` (`video_conference_id`),
  CONSTRAINT `ld_conference_attendance_ibfk_1` FOREIGN KEY (`video_conference_id`) REFERENCES `ld_video_conference` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_course
CREATE TABLE IF NOT EXISTS `ld_course` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `compliance_category` varchar(100) DEFAULT NULL,
  `is_compliance_required` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `delivery_mode` enum('online','face_to_face','hybrid') NOT NULL DEFAULT 'online',
  `start_date` date DEFAULT NULL,
  `enrollment_deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `program_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `ld_course_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_course_instructor
CREATE TABLE IF NOT EXISTS `ld_course_instructor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `instructor_id` int(10) unsigned NOT NULL,
  `role` enum('owner','co-instructor') NOT NULL DEFAULT 'owner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_instructor` (`course_id`,`instructor_id`),
  CONSTRAINT `ld_course_instructor_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_course_skill
CREATE TABLE IF NOT EXISTS `ld_course_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_skill` (`course_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `ld_course_skill_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_course_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_course_template
CREATE TABLE IF NOT EXISTS `ld_course_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `structure_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`structure_json`)),
  `module_count` int(10) unsigned DEFAULT 0,
  `lesson_count` int(10) unsigned DEFAULT 0,
  `quiz_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_course_version
CREATE TABLE IF NOT EXISTS `ld_course_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_course_version_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_display_preference
CREATE TABLE IF NOT EXISTS `ld_display_preference` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `page_size` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_engagement_snapshot
CREATE TABLE IF NOT EXISTS `ld_engagement_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `active_days_30` int(10) unsigned DEFAULT 0,
  `courses_in_progress` int(10) unsigned DEFAULT 0,
  `courses_completed_ytd` int(10) unsigned DEFAULT 0,
  `avg_score` decimal(5,2) DEFAULT NULL,
  `quizzes_started` int(10) unsigned DEFAULT 0,
  `quizzes_abandoned` int(10) unsigned DEFAULT 0,
  `days_since_last_activity` int(10) unsigned DEFAULT NULL,
  `expired_certificates` int(10) unsigned DEFAULT 0,
  `risk_score` decimal(5,2) DEFAULT NULL,
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_factors`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_snapshot` (`learner_id`,`snapshot_date`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_enrollment
CREATE TABLE IF NOT EXISTS `ld_enrollment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `course_version_id` int(10) unsigned DEFAULT NULL,
  `status` enum('invited','enrolled','in_progress','completed','withdrawn') NOT NULL DEFAULT 'enrolled',
  `invited_by` int(10) unsigned DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_course` (`learner_id`,`course_id`),
  KEY `course_id` (`course_id`),
  KEY `course_version_id` (`course_version_id`),
  CONSTRAINT `ld_enrollment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_enrollment_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_evaluation
CREATE TABLE IF NOT EXISTS `ld_evaluation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_seconds` int(10) unsigned DEFAULT NULL,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `max_attempts` int(10) unsigned NOT NULL DEFAULT 2,
  `question_count` int(10) unsigned DEFAULT NULL,
  `show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_evaluation_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_evaluation_feedback
CREATE TABLE IF NOT EXISTS `ld_evaluation_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(10) unsigned NOT NULL,
  `learner_id` int(10) unsigned NOT NULL,
  `instructor_id` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `evaluation_id` (`evaluation_id`),
  CONSTRAINT `ld_evaluation_feedback_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `ld_evaluation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_favorite
CREATE TABLE IF NOT EXISTS `ld_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_favorite` (`learner_id`,`item_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_grade
CREATE TABLE IF NOT EXISTS `ld_grade` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_grade_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_integration_event
CREATE TABLE IF NOT EXISTS `ld_integration_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `external_reference_id` varchar(255) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_external_ref` (`module_name`,`external_reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_integration_log
CREATE TABLE IF NOT EXISTS `ld_integration_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `direction` enum('inbound','outbound') NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_integration_log_module` (`module_name`,`direction`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_learning_path
CREATE TABLE IF NOT EXISTS `ld_learning_path` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `type` enum('standard','knowledge_transfer') NOT NULL DEFAULT 'standard',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `kt_plan_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lp_type_public` (`type`,`is_public`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_learning_path_item
CREATE TABLE IF NOT EXISTS `ld_learning_path_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` int(10) unsigned NOT NULL,
  `item_type` enum('course','module','lesson','quiz','evaluation','program','video-conference') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `order_index` int(10) unsigned DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `learning_path_id` (`learning_path_id`),
  CONSTRAINT `ld_learning_path_item_ibfk_1` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_learning_path_skill
CREATE TABLE IF NOT EXISTS `ld_learning_path_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lp_skill` (`learning_path_id`,`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_lesson
CREATE TABLE IF NOT EXISTS `ld_lesson` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_type` enum('video','text','file','mixed') NOT NULL DEFAULT 'text',
  `content_body` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `order_index` int(10) unsigned DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `ld_lesson_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_lesson_file
CREATE TABLE IF NOT EXISTS `ld_lesson_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` int(10) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  CONSTRAINT `ld_lesson_file_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_message
CREATE TABLE IF NOT EXISTS `ld_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `recipient_id` int(10) unsigned NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_message_recipient` (`recipient_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_module
CREATE TABLE IF NOT EXISTS `ld_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(10) unsigned DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_module_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_module_skill
CREATE TABLE IF NOT EXISTS `ld_module_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_skill` (`module_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `ld_module_skill_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_module_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_note
CREATE TABLE IF NOT EXISTS `ld_note` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` enum('course','module','lesson','quiz') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_note_item` (`item_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_notification
CREATE TABLE IF NOT EXISTS `ld_notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_notification_preference
CREATE TABLE IF NOT EXISTS `ld_notification_preference` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_notif_type` (`user_id`,`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_prerequisite
CREATE TABLE IF NOT EXISTS `ld_prerequisite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `required_course_id` int(10) unsigned DEFAULT NULL,
  `required_skill_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `required_course_id` (`required_course_id`),
  KEY `required_skill_id` (`required_skill_id`),
  CONSTRAINT `ld_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_prerequisite_ibfk_2` FOREIGN KEY (`required_course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_prerequisite_ibfk_3` FOREIGN KEY (`required_skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_program
CREATE TABLE IF NOT EXISTS `ld_program` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_program_skill
CREATE TABLE IF NOT EXISTS `ld_program_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prog_skill` (`program_id`,`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_progress
CREATE TABLE IF NOT EXISTS `ld_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(10) unsigned NOT NULL,
  `item_type` enum('module','lesson','quiz','evaluation') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `idx_progress_item` (`item_type`,`reference_id`),
  CONSTRAINT `ld_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz
CREATE TABLE IF NOT EXISTS `ld_quiz` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_seconds` int(10) unsigned NOT NULL DEFAULT 600,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `max_attempts` int(10) unsigned NOT NULL DEFAULT 2,
  `question_count` int(10) unsigned DEFAULT NULL,
  `show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `ld_quiz_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz_attempt
CREATE TABLE IF NOT EXISTS `ld_quiz_attempt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `quiz_id` int(10) unsigned NOT NULL,
  `quiz_session_id` int(10) unsigned DEFAULT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_items` int(10) unsigned NOT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quiz_session_id` (`quiz_session_id`),
  CONSTRAINT `ld_quiz_attempt_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz_question
CREATE TABLE IF NOT EXISTS `ld_quiz_question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_type` enum('quiz','evaluation') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single_choice','multiple_choice','true_false') NOT NULL DEFAULT 'single_choice',
  `order_index` int(10) unsigned DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_question_ref` (`item_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=530 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz_question_option
CREATE TABLE IF NOT EXISTS `ld_quiz_question_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `ld_quiz_question_option_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1928 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz_session
CREATE TABLE IF NOT EXISTS `ld_quiz_session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` enum('quiz','evaluation') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_seconds` int(10) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','submitted','expired') NOT NULL DEFAULT 'in_progress',
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`)),
  `score` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_session_learner_ref` (`learner_id`,`item_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_quiz_session_answer
CREATE TABLE IF NOT EXISTS `ld_quiz_session_answer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_session_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `selected_option_id` int(10) unsigned DEFAULT NULL,
  `is_marked_for_review` tinyint(1) NOT NULL DEFAULT 0,
  `answered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_session_id` (`quiz_session_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `ld_quiz_session_answer_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_quiz_session_answer_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_rating
CREATE TABLE IF NOT EXISTS `ld_rating` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_course_rating` (`learner_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_rating_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_recognition_course_map
CREATE TABLE IF NOT EXISTS `ld_recognition_course_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recognition_category` varchar(255) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_recognition_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_recognition_unlock
CREATE TABLE IF NOT EXISTS `ld_recognition_unlock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `recognition_category` varchar(100) NOT NULL,
  `external_reference_id` varchar(255) DEFAULT NULL,
  `status` enum('unlocked','redeemed','expired') NOT NULL DEFAULT 'unlocked',
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `redeemed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `idx_recognition_learner` (`learner_id`,`status`),
  CONSTRAINT `ld_recognition_unlock_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_recommendation_course_map
CREATE TABLE IF NOT EXISTS `ld_recommendation_course_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `development_area` varchar(255) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_recommendation_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_report
CREATE TABLE IF NOT EXISTS `ld_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
  `instructor_response` text DEFAULT NULL,
  `instructor_responded_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_request
CREATE TABLE IF NOT EXISTS `ld_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `requested_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_setting
CREATE TABLE IF NOT EXISTS `ld_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_skill
CREATE TABLE IF NOT EXISTS `ld_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `suggested` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_skill_snapshot
CREATE TABLE IF NOT EXISTS `ld_skill_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `position_id` int(10) unsigned DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `total_skills` int(10) unsigned NOT NULL DEFAULT 0,
  `acquired_count` int(10) unsigned NOT NULL DEFAULT 0,
  `gap_count` int(10) unsigned NOT NULL DEFAULT 0,
  `acquired_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`acquired_skills`)),
  `gap_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gap_skills`)),
  `snapshot_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_date` (`learner_id`,`snapshot_date`),
  KEY `idx_learner` (`learner_id`),
  KEY `idx_skill_snapshot_learner_date` (`learner_id`,`snapshot_date`),
  KEY `idx_skill_snapshot_dept` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_training_recommendation
CREATE TABLE IF NOT EXISTS `ld_training_recommendation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `development_area` varchar(150) NOT NULL,
  `priority_level` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `source` varchar(50) NOT NULL,
  `external_reference_id` varchar(255) DEFAULT NULL,
  `status` enum('recommended','invited','enrolled','completed','declined') NOT NULL DEFAULT 'recommended',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `idx_training_reco_employee` (`employee_id`,`status`),
  KEY `idx_training_reco_area` (`development_area`),
  CONSTRAINT `ld_training_recommendation_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_user_event
CREATE TABLE IF NOT EXISTS `ld_user_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` enum('meeting','deadline','reminder','personal','other') DEFAULT 'personal',
  `color` varchar(20) DEFAULT '#320082',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ld_video_conference
CREATE TABLE IF NOT EXISTS `ld_video_conference` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `platform` enum('zoom','google_meet','other') NOT NULL DEFAULT 'google_meet',
  `meeting_link` varchar(500) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(10) unsigned DEFAULT NULL,
  `status` enum('scheduled','completed','archived') NOT NULL DEFAULT 'scheduled',
  `first_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `second_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `ld_video_conference_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ld_video_conference_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------
-- 2) STASH THE DESIRED SCHEMA (helper tables, dropped at the end)
-- -----------------------------------------------------------------------

DROP TABLE IF EXISTS `_ld_schema_con`;
DROP TABLE IF EXISTS `_ld_schema_idx`;
DROP TABLE IF EXISTS `_ld_schema_cols`;
DROP TABLE IF EXISTS `_ld_schema_tables`;

CREATE TABLE `_ld_schema_tables` (
  `table_name` VARCHAR(64) NOT NULL,
  `engine`     VARCHAR(64) NOT NULL,
  `charset`    VARCHAR(64) NOT NULL,
  `collation`  VARCHAR(64) NOT NULL,
  KEY `k_table` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `_ld_schema_cols` (
  `table_name`  VARCHAR(64)  NOT NULL,
  `ordinal`     INT          NOT NULL,
  `column_name` VARCHAR(64)  NOT NULL,
  `col_def`     LONGTEXT     NOT NULL,
  `col_type`    VARCHAR(255) NOT NULL,
  `is_nullable` VARCHAR(3)   NOT NULL,
  `col_default` TEXT         NULL,
  `extra`       VARCHAR(255) NOT NULL DEFAULT '',
  `after_col`   VARCHAR(64)  NULL,
  KEY `k_table` (`table_name`, `ordinal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `_ld_schema_idx` (
  `table_name` VARCHAR(64) NOT NULL,
  `seq`        INT         NOT NULL,
  `index_name` VARCHAR(64) NOT NULL,
  `idx_def`    LONGTEXT    NOT NULL,
  KEY `k_table` (`table_name`, `seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `_ld_schema_con` (
  `table_name`      VARCHAR(64) NOT NULL,
  `seq`             INT         NOT NULL,
  `constraint_name` VARCHAR(64) NOT NULL,
  `constraint_type` VARCHAR(64) NOT NULL,
  `con_def`         LONGTEXT    NOT NULL,
  KEY `k_table` (`table_name`, `seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `_ld_schema_tables` (`table_name`, `engine`, `charset`, `collation`) VALUES
  ('ld_announcement', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_api_key', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_assessment_result', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_audit_log', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_bookmark', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_calendar_event', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_certificate', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_certificate_template', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_comment', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_conference_attendance', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_course', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_course_instructor', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_course_skill', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_course_template', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_course_version', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_display_preference', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_engagement_snapshot', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_enrollment', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_evaluation', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_evaluation_feedback', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_favorite', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_grade', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_integration_event', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_integration_log', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_learning_path', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_learning_path_item', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_learning_path_skill', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_lesson', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_lesson_file', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_message', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_module', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_module_skill', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_note', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_notification', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_notification_preference', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_prerequisite', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_program', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_program_skill', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_progress', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_quiz', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci');
INSERT INTO `_ld_schema_tables` (`table_name`, `engine`, `charset`, `collation`) VALUES
  ('ld_quiz_attempt', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_quiz_question', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_quiz_question_option', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_quiz_session', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_quiz_session_answer', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_rating', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_recognition_course_map', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_recognition_unlock', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_recommendation_course_map', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_report', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_request', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_setting', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_skill', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_skill_snapshot', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_training_recommendation', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_user_event', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci'),
  ('ld_video_conference', 'InnoDB', 'utf8mb4', 'utf8mb4_general_ci');

INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_announcement', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_announcement', 2, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'id'),
  ('ld_announcement', 3, 'message', '`message` text NOT NULL', 'text', 'NO', '<none>', '', 'title'),
  ('ld_announcement', 4, 'audience', '`audience` enum(\'all\',\'instructor\',\'learner\',\'admin\') NOT NULL DEFAULT \'all\'', 'enum(\'all\',\'instructor\',\'learner\',\'admin\')', 'NO', '\'all\'', '', 'message'),
  ('ld_announcement', 5, 'posted_by', '`posted_by` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'audience'),
  ('ld_announcement', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'posted_by'),
  ('ld_announcement', 7, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_announcement', 8, 'expires_at', '`expires_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'created_at'),
  ('ld_api_key', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_api_key', 2, 'module_name', '`module_name` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'id'),
  ('ld_api_key', 3, 'api_key', '`api_key` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'module_name'),
  ('ld_api_key', 4, 'is_active', '`is_active` tinyint(1) NOT NULL DEFAULT 1', 'tinyint(1)', 'NO', '1', '', 'api_key'),
  ('ld_api_key', 5, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'is_active'),
  ('ld_assessment_result', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_assessment_result', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_assessment_result', 3, 'source', '`source` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'learner_id'),
  ('ld_assessment_result', 4, 'assessment_name', '`assessment_name` varchar(150) NOT NULL', 'varchar(150)', 'NO', '<none>', '', 'source'),
  ('ld_assessment_result', 5, 'score', '`score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'assessment_name'),
  ('ld_assessment_result', 6, 'result', '`result` enum(\'passed\',\'failed\',\'pending\') NOT NULL DEFAULT \'pending\'', 'enum(\'passed\',\'failed\',\'pending\')', 'NO', '\'pending\'', '', 'score'),
  ('ld_assessment_result', 7, 'skill_ids', '`skill_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skill_ids`))', 'longtext', 'YES', 'NULL', '', 'result'),
  ('ld_assessment_result', 8, 'taken_at', '`taken_at` datetime NOT NULL', 'datetime', 'NO', '<none>', '', 'skill_ids'),
  ('ld_assessment_result', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'taken_at'),
  ('ld_audit_log', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_audit_log', 2, 'user_id', '`user_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_audit_log', 3, 'role', '`role` enum(\'admin\',\'instructor\',\'learner\') NOT NULL', 'enum(\'admin\',\'instructor\',\'learner\')', 'NO', '<none>', '', 'user_id'),
  ('ld_audit_log', 4, 'action', '`action` enum(\'create\',\'edit\',\'archive\',\'restore\',\'review\') NOT NULL', 'enum(\'create\',\'edit\',\'archive\',\'restore\',\'review\')', 'NO', '<none>', '', 'role'),
  ('ld_audit_log', 5, 'item_type', '`item_type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'action'),
  ('ld_audit_log', 6, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_audit_log', 7, 'details', '`details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`))', 'longtext', 'YES', 'NULL', '', 'reference_id'),
  ('ld_audit_log', 8, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'details'),
  ('ld_bookmark', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_bookmark', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_bookmark', 3, 'item_type', '`item_type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'learner_id'),
  ('ld_bookmark', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_bookmark', 5, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'reference_id'),
  ('ld_calendar_event', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_calendar_event', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_calendar_event', 3, 'type', '`type` enum(\'program\',\'training\',\'video-conference\') NOT NULL', 'enum(\'program\',\'training\',\'video-conference\')', 'NO', '<none>', '', 'instructor_id'),
  ('ld_calendar_event', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'type'),
  ('ld_calendar_event', 5, 'event_date', '`event_date` date NOT NULL', 'date', 'NO', '<none>', '', 'reference_id');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_calendar_event', 6, 'event_time', '`event_time` time DEFAULT NULL', 'time', 'YES', 'NULL', '', 'event_date'),
  ('ld_calendar_event', 7, 'duration_minutes', '`duration_minutes` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'event_time'),
  ('ld_calendar_event', 8, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'duration_minutes'),
  ('ld_calendar_event', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_calendar_event', 10, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_certificate', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_certificate', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_certificate', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_certificate', 4, 'course_version_id', '`course_version_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'course_id'),
  ('ld_certificate', 5, 'completed_enrollment_id', '`completed_enrollment_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'course_version_id'),
  ('ld_certificate', 6, 'template_id', '`template_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'completed_enrollment_id'),
  ('ld_certificate', 7, 'verification_code', '`verification_code` varchar(64) NOT NULL', 'varchar(64)', 'NO', '<none>', '', 'template_id'),
  ('ld_certificate', 8, 'file_path', '`file_path` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'verification_code'),
  ('ld_certificate', 9, 'issued_at', '`issued_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'file_path'),
  ('ld_certificate', 10, 'valid_until', '`valid_until` date DEFAULT NULL', 'date', 'YES', 'NULL', '', 'issued_at'),
  ('ld_certificate', 11, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'valid_until'),
  ('ld_certificate_template', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_certificate_template', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_certificate_template', 3, 'course_id', '`course_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'instructor_id'),
  ('ld_certificate_template', 4, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'course_id'),
  ('ld_certificate_template', 5, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_certificate_template', 6, 'template_file', '`template_file` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'description'),
  ('ld_certificate_template', 7, 'is_active', '`is_active` tinyint(1) NOT NULL DEFAULT 1', 'tinyint(1)', 'NO', '1', '', 'template_file'),
  ('ld_certificate_template', 8, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'is_active'),
  ('ld_certificate_template', 9, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_comment', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_comment', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_comment', 3, 'lesson_id', '`lesson_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_comment', 4, 'message', '`message` text NOT NULL', 'text', 'NO', '<none>', '', 'lesson_id'),
  ('ld_comment', 5, 'parent_comment_id', '`parent_comment_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'message'),
  ('ld_comment', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'parent_comment_id'),
  ('ld_comment', 7, 'was_ever_reported', '`was_ever_reported` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'status'),
  ('ld_comment', 8, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'was_ever_reported'),
  ('ld_conference_attendance', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_conference_attendance', 2, 'video_conference_id', '`video_conference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_conference_attendance', 3, 'session_type', '`session_type` enum(\'video_conference\',\'in_person\',\'virtual\') NOT NULL DEFAULT \'video_conference\'', 'enum(\'video_conference\',\'in_person\',\'virtual\')', 'NO', '\'video_conference\'', '', 'video_conference_id'),
  ('ld_conference_attendance', 4, 'event_id', '`event_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'session_type'),
  ('ld_conference_attendance', 5, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'event_id'),
  ('ld_conference_attendance', 6, 'attended', '`attended` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'learner_id'),
  ('ld_conference_attendance', 7, 'joined_at', '`joined_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'attended');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_course', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_course', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_course', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'instructor_id'),
  ('ld_course', 4, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_course', 5, 'thumbnail_path', '`thumbnail_path` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'description'),
  ('ld_course', 6, 'category', '`category` varchar(100) DEFAULT NULL', 'varchar(100)', 'YES', 'NULL', '', 'thumbnail_path'),
  ('ld_course', 7, 'compliance_category', '`compliance_category` varchar(100) DEFAULT NULL', 'varchar(100)', 'YES', 'NULL', '', 'category'),
  ('ld_course', 8, 'is_compliance_required', '`is_compliance_required` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'compliance_category'),
  ('ld_course', 9, 'status', '`status` enum(\'draft\',\'active\',\'archived\') NOT NULL DEFAULT \'draft\'', 'enum(\'draft\',\'active\',\'archived\')', 'NO', '\'draft\'', '', 'is_compliance_required'),
  ('ld_course', 10, 'delivery_mode', '`delivery_mode` enum(\'online\',\'face_to_face\',\'hybrid\') NOT NULL DEFAULT \'online\'', 'enum(\'online\',\'face_to_face\',\'hybrid\')', 'NO', '\'online\'', '', 'status'),
  ('ld_course', 11, 'start_date', '`start_date` date DEFAULT NULL', 'date', 'YES', 'NULL', '', 'delivery_mode'),
  ('ld_course', 12, 'enrollment_deadline', '`enrollment_deadline` date DEFAULT NULL', 'date', 'YES', 'NULL', '', 'start_date'),
  ('ld_course', 13, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'enrollment_deadline'),
  ('ld_course', 14, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_course', 15, 'program_id', '`program_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'updated_at'),
  ('ld_course_instructor', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_course_instructor', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_course_instructor', 3, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'course_id'),
  ('ld_course_instructor', 4, 'role', '`role` enum(\'owner\',\'co-instructor\') NOT NULL DEFAULT \'owner\'', 'enum(\'owner\',\'co-instructor\')', 'NO', '\'owner\'', '', 'instructor_id'),
  ('ld_course_instructor', 5, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'role'),
  ('ld_course_skill', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_course_skill', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_course_skill', 3, 'skill_id', '`skill_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'course_id'),
  ('ld_course_template', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_course_template', 2, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'id'),
  ('ld_course_template', 3, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_course_template', 4, 'category', '`category` varchar(100) DEFAULT NULL', 'varchar(100)', 'YES', 'NULL', '', 'description'),
  ('ld_course_template', 5, 'created_by', '`created_by` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'category'),
  ('ld_course_template', 6, 'structure_json', '`structure_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`structure_json`))', 'longtext', 'NO', '<none>', '', 'created_by'),
  ('ld_course_template', 7, 'module_count', '`module_count` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'structure_json'),
  ('ld_course_template', 8, 'lesson_count', '`lesson_count` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'module_count'),
  ('ld_course_template', 9, 'quiz_count', '`quiz_count` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'lesson_count'),
  ('ld_course_template', 10, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'quiz_count'),
  ('ld_course_template', 11, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_course_version', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_course_version', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_course_version', 3, 'version_number', '`version_number` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'course_id'),
  ('ld_course_version', 4, 'snapshot', '`snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`))', 'longtext', 'NO', '<none>', '', 'version_number'),
  ('ld_course_version', 5, 'published_at', '`published_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'snapshot'),
  ('ld_display_preference', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL);
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_display_preference', 2, 'user_id', '`user_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_display_preference', 3, 'page_size', '`page_size` tinyint(3) unsigned NOT NULL DEFAULT 10', 'tinyint(3) unsigned', 'NO', '10', '', 'user_id'),
  ('ld_display_preference', 4, 'view_mode', '`view_mode` enum(\'grid\',\'list\') NOT NULL DEFAULT \'grid\'', 'enum(\'grid\',\'list\')', 'NO', '\'grid\'', '', 'page_size'),
  ('ld_display_preference', 5, 'theme', '`theme` enum(\'light\',\'dark\') NOT NULL DEFAULT \'light\'', 'enum(\'light\',\'dark\')', 'NO', '\'light\'', '', 'view_mode'),
  ('ld_engagement_snapshot', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_engagement_snapshot', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_engagement_snapshot', 3, 'snapshot_date', '`snapshot_date` date NOT NULL', 'date', 'NO', '<none>', '', 'learner_id'),
  ('ld_engagement_snapshot', 4, 'active_days_30', '`active_days_30` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'snapshot_date'),
  ('ld_engagement_snapshot', 5, 'courses_in_progress', '`courses_in_progress` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'active_days_30'),
  ('ld_engagement_snapshot', 6, 'courses_completed_ytd', '`courses_completed_ytd` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'courses_in_progress'),
  ('ld_engagement_snapshot', 7, 'avg_score', '`avg_score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'courses_completed_ytd'),
  ('ld_engagement_snapshot', 8, 'quizzes_started', '`quizzes_started` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'avg_score'),
  ('ld_engagement_snapshot', 9, 'quizzes_abandoned', '`quizzes_abandoned` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'quizzes_started'),
  ('ld_engagement_snapshot', 10, 'days_since_last_activity', '`days_since_last_activity` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'quizzes_abandoned'),
  ('ld_engagement_snapshot', 11, 'expired_certificates', '`expired_certificates` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'days_since_last_activity'),
  ('ld_engagement_snapshot', 12, 'risk_score', '`risk_score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'expired_certificates'),
  ('ld_engagement_snapshot', 13, 'risk_factors', '`risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_factors`))', 'longtext', 'YES', 'NULL', '', 'risk_score'),
  ('ld_engagement_snapshot', 14, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'risk_factors'),
  ('ld_enrollment', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_enrollment', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_enrollment', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_enrollment', 4, 'course_version_id', '`course_version_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'course_id'),
  ('ld_enrollment', 5, 'status', '`status` enum(\'invited\',\'enrolled\',\'in_progress\',\'completed\',\'withdrawn\') NOT NULL DEFAULT \'enrolled\'', 'enum(\'invited\',\'enrolled\',\'in_progress\',\'completed\',\'withdrawn\')', 'NO', '\'enrolled\'', '', 'course_version_id'),
  ('ld_enrollment', 6, 'invited_by', '`invited_by` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'status'),
  ('ld_enrollment', 7, 'enrolled_at', '`enrolled_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'invited_by'),
  ('ld_enrollment', 8, 'completed_at', '`completed_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'enrolled_at'),
  ('ld_enrollment', 9, 'last_accessed_at', '`last_accessed_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'completed_at'),
  ('ld_enrollment', 10, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'last_accessed_at'),
  ('ld_evaluation', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_evaluation', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_evaluation', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'course_id'),
  ('ld_evaluation', 4, 'duration_seconds', '`duration_seconds` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'title'),
  ('ld_evaluation', 5, 'passing_score', '`passing_score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'duration_seconds'),
  ('ld_evaluation', 6, 'max_attempts', '`max_attempts` int(10) unsigned NOT NULL DEFAULT 2', 'int(10) unsigned', 'NO', '2', '', 'passing_score'),
  ('ld_evaluation', 7, 'question_count', '`question_count` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'max_attempts'),
  ('ld_evaluation', 8, 'show_answers_after_submit', '`show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'question_count'),
  ('ld_evaluation', 9, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'show_answers_after_submit'),
  ('ld_evaluation', 10, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_evaluation', 11, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_evaluation_feedback', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL);
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_evaluation_feedback', 2, 'evaluation_id', '`evaluation_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_evaluation_feedback', 3, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'evaluation_id'),
  ('ld_evaluation_feedback', 4, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_evaluation_feedback', 5, 'comment', '`comment` text NOT NULL', 'text', 'NO', '<none>', '', 'instructor_id'),
  ('ld_evaluation_feedback', 6, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'comment'),
  ('ld_favorite', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_favorite', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_favorite', 3, 'item_type', '`item_type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'learner_id'),
  ('ld_favorite', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_favorite', 5, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'reference_id'),
  ('ld_grade', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_grade', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_grade', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_grade', 4, 'final_score', '`final_score` decimal(5,2) NOT NULL', 'decimal(5,2)', 'NO', '<none>', '', 'course_id'),
  ('ld_grade', 5, 'status', '`status` enum(\'passed\',\'failed\') NOT NULL', 'enum(\'passed\',\'failed\')', 'NO', '<none>', '', 'final_score'),
  ('ld_grade', 6, 'issued_at', '`issued_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_integration_event', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_integration_event', 2, 'module_name', '`module_name` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'id'),
  ('ld_integration_event', 3, 'external_reference_id', '`external_reference_id` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'module_name'),
  ('ld_integration_event', 4, 'event_type', '`event_type` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'external_reference_id'),
  ('ld_integration_event', 5, 'processed_at', '`processed_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'event_type'),
  ('ld_integration_log', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_integration_log', 2, 'direction', '`direction` enum(\'inbound\',\'outbound\') NOT NULL', 'enum(\'inbound\',\'outbound\')', 'NO', '<none>', '', 'id'),
  ('ld_integration_log', 3, 'module_name', '`module_name` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'direction'),
  ('ld_integration_log', 4, 'endpoint', '`endpoint` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'module_name'),
  ('ld_integration_log', 5, 'status', '`status` enum(\'success\',\'failed\',\'pending\') NOT NULL DEFAULT \'pending\'', 'enum(\'success\',\'failed\',\'pending\')', 'NO', '\'pending\'', '', 'endpoint'),
  ('ld_integration_log', 6, 'payload', '`payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`))', 'longtext', 'YES', 'NULL', '', 'status'),
  ('ld_integration_log', 7, 'error_message', '`error_message` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'payload'),
  ('ld_integration_log', 8, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'error_message'),
  ('ld_learning_path', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_learning_path', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_learning_path', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'instructor_id'),
  ('ld_learning_path', 4, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_learning_path', 5, 'assigned_to', '`assigned_to` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'description'),
  ('ld_learning_path', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'assigned_to'),
  ('ld_learning_path', 7, 'type', '`type` enum(\'standard\',\'knowledge_transfer\') NOT NULL DEFAULT \'standard\'', 'enum(\'standard\',\'knowledge_transfer\')', 'NO', '\'standard\'', '', 'status'),
  ('ld_learning_path', 8, 'is_public', '`is_public` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'type'),
  ('ld_learning_path', 9, 'kt_plan_id', '`kt_plan_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'is_public'),
  ('ld_learning_path', 10, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'kt_plan_id'),
  ('ld_learning_path', 11, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_learning_path_item', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_learning_path_item', 2, 'learning_path_id', '`learning_path_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_learning_path_item', 3, 'item_type', '`item_type` enum(\'course\',\'module\',\'lesson\',\'quiz\',\'evaluation\',\'program\',\'video-conference\') NOT NULL', 'enum(\'course\',\'module\',\'lesson\',\'quiz\',\'evaluation\',\'program\',\'video-conference\')', 'NO', '<none>', '', 'learning_path_id'),
  ('ld_learning_path_item', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_learning_path_item', 5, 'order_index', '`order_index` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'reference_id'),
  ('ld_learning_path_item', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'order_index'),
  ('ld_learning_path_skill', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_learning_path_skill', 2, 'learning_path_id', '`learning_path_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_learning_path_skill', 3, 'skill_id', '`skill_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learning_path_id'),
  ('ld_lesson', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_lesson', 2, 'module_id', '`module_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_lesson', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'module_id'),
  ('ld_lesson', 4, 'content_type', '`content_type` enum(\'video\',\'text\',\'file\',\'mixed\') NOT NULL DEFAULT \'text\'', 'enum(\'video\',\'text\',\'file\',\'mixed\')', 'NO', '\'text\'', '', 'title'),
  ('ld_lesson', 5, 'content_body', '`content_body` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'content_type'),
  ('ld_lesson', 6, 'video_url', '`video_url` varchar(500) DEFAULT NULL', 'varchar(500)', 'YES', 'NULL', '', 'content_body'),
  ('ld_lesson', 7, 'order_index', '`order_index` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'video_url'),
  ('ld_lesson', 8, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'order_index'),
  ('ld_lesson', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_lesson', 10, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_lesson_file', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_lesson_file', 2, 'lesson_id', '`lesson_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_lesson_file', 3, 'file_path', '`file_path` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'lesson_id'),
  ('ld_lesson_file', 4, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'file_path'),
  ('ld_lesson_file', 5, 'uploaded_at', '`uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'title'),
  ('ld_message', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_message', 2, 'sender_id', '`sender_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_message', 3, 'recipient_id', '`recipient_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'sender_id'),
  ('ld_message', 4, 'subject', '`subject` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'recipient_id'),
  ('ld_message', 5, 'body', '`body` text NOT NULL', 'text', 'NO', '<none>', '', 'subject'),
  ('ld_message', 6, 'is_read', '`is_read` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'body'),
  ('ld_message', 7, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'is_read'),
  ('ld_module', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_module', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_module', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'course_id'),
  ('ld_module', 4, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_module', 5, 'order_index', '`order_index` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'description'),
  ('ld_module', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'order_index'),
  ('ld_module', 7, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_module', 8, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_module_skill', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL);
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_module_skill', 2, 'module_id', '`module_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_module_skill', 3, 'skill_id', '`skill_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'module_id'),
  ('ld_note', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_note', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_note', 3, 'item_type', '`item_type` enum(\'course\',\'module\',\'lesson\',\'quiz\') NOT NULL', 'enum(\'course\',\'module\',\'lesson\',\'quiz\')', 'NO', '<none>', '', 'learner_id'),
  ('ld_note', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_note', 5, 'note', '`note` text NOT NULL', 'text', 'NO', '<none>', '', 'reference_id'),
  ('ld_note', 6, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'note'),
  ('ld_note', 7, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_notification', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_notification', 2, 'user_id', '`user_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_notification', 3, 'type', '`type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'user_id'),
  ('ld_notification', 4, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'type'),
  ('ld_notification', 5, 'message', '`message` text NOT NULL', 'text', 'NO', '<none>', '', 'title'),
  ('ld_notification', 6, 'reference_type', '`reference_type` varchar(50) DEFAULT NULL', 'varchar(50)', 'YES', 'NULL', '', 'message'),
  ('ld_notification', 7, 'reference_id', '`reference_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'reference_type'),
  ('ld_notification', 8, 'is_read', '`is_read` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'reference_id'),
  ('ld_notification', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'is_read'),
  ('ld_notification_preference', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_notification_preference', 2, 'user_id', '`user_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_notification_preference', 3, 'notification_type', '`notification_type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'user_id'),
  ('ld_notification_preference', 4, 'enabled', '`enabled` tinyint(1) NOT NULL DEFAULT 1', 'tinyint(1)', 'NO', '1', '', 'notification_type'),
  ('ld_prerequisite', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_prerequisite', 2, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_prerequisite', 3, 'required_course_id', '`required_course_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'course_id'),
  ('ld_prerequisite', 4, 'required_skill_id', '`required_skill_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'required_course_id'),
  ('ld_program', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_program', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_program', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'instructor_id'),
  ('ld_program', 4, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_program', 5, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'description'),
  ('ld_program', 6, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_program', 7, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_program_skill', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_program_skill', 2, 'program_id', '`program_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_program_skill', 3, 'skill_id', '`skill_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'program_id'),
  ('ld_progress', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_progress', 2, 'enrollment_id', '`enrollment_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_progress', 3, 'item_type', '`item_type` enum(\'module\',\'lesson\',\'quiz\',\'evaluation\') NOT NULL', 'enum(\'module\',\'lesson\',\'quiz\',\'evaluation\')', 'NO', '<none>', '', 'enrollment_id'),
  ('ld_progress', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_progress', 5, 'status', '`status` enum(\'not_started\',\'in_progress\',\'completed\') NOT NULL DEFAULT \'not_started\'', 'enum(\'not_started\',\'in_progress\',\'completed\')', 'NO', '\'not_started\'', '', 'reference_id'),
  ('ld_progress', 6, 'completed_at', '`completed_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'status'),
  ('ld_quiz', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz', 2, 'module_id', '`module_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_quiz', 3, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'module_id'),
  ('ld_quiz', 4, 'duration_seconds', '`duration_seconds` int(10) unsigned NOT NULL DEFAULT 600', 'int(10) unsigned', 'NO', '600', '', 'title'),
  ('ld_quiz', 5, 'passing_score', '`passing_score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'duration_seconds'),
  ('ld_quiz', 6, 'max_attempts', '`max_attempts` int(10) unsigned NOT NULL DEFAULT 2', 'int(10) unsigned', 'NO', '2', '', 'passing_score'),
  ('ld_quiz', 7, 'question_count', '`question_count` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'max_attempts'),
  ('ld_quiz', 8, 'show_answers_after_submit', '`show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'question_count'),
  ('ld_quiz', 9, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'show_answers_after_submit'),
  ('ld_quiz', 10, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_quiz', 11, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_quiz_attempt', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz_attempt', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_quiz_attempt', 3, 'quiz_id', '`quiz_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_quiz_attempt', 4, 'quiz_session_id', '`quiz_session_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'quiz_id'),
  ('ld_quiz_attempt', 5, 'score', '`score` decimal(5,2) NOT NULL', 'decimal(5,2)', 'NO', '<none>', '', 'quiz_session_id'),
  ('ld_quiz_attempt', 6, 'total_items', '`total_items` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'score'),
  ('ld_quiz_attempt', 7, 'passed', '`passed` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'total_items'),
  ('ld_quiz_attempt', 8, 'attempted_at', '`attempted_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'passed'),
  ('ld_quiz_question', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz_question', 2, 'item_type', '`item_type` enum(\'quiz\',\'evaluation\') NOT NULL', 'enum(\'quiz\',\'evaluation\')', 'NO', '<none>', '', 'id'),
  ('ld_quiz_question', 3, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_quiz_question', 4, 'question_text', '`question_text` text NOT NULL', 'text', 'NO', '<none>', '', 'reference_id'),
  ('ld_quiz_question', 5, 'question_type', '`question_type` enum(\'single_choice\',\'multiple_choice\',\'true_false\') NOT NULL DEFAULT \'single_choice\'', 'enum(\'single_choice\',\'multiple_choice\',\'true_false\')', 'NO', '\'single_choice\'', '', 'question_text'),
  ('ld_quiz_question', 6, 'order_index', '`order_index` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'question_type'),
  ('ld_quiz_question', 7, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'order_index'),
  ('ld_quiz_question', 8, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_quiz_question', 9, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_quiz_question_option', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz_question_option', 2, 'question_id', '`question_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_quiz_question_option', 3, 'option_text', '`option_text` text NOT NULL', 'text', 'NO', '<none>', '', 'question_id'),
  ('ld_quiz_question_option', 4, 'is_correct', '`is_correct` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'option_text'),
  ('ld_quiz_question_option', 5, 'order_index', '`order_index` int(10) unsigned DEFAULT 0', 'int(10) unsigned', 'YES', '0', '', 'is_correct'),
  ('ld_quiz_session', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz_session', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_quiz_session', 3, 'item_type', '`item_type` enum(\'quiz\',\'evaluation\') NOT NULL', 'enum(\'quiz\',\'evaluation\')', 'NO', '<none>', '', 'learner_id'),
  ('ld_quiz_session', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type'),
  ('ld_quiz_session', 5, 'started_at', '`started_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'reference_id');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_quiz_session', 6, 'duration_seconds', '`duration_seconds` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'started_at'),
  ('ld_quiz_session', 7, 'submitted_at', '`submitted_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'duration_seconds'),
  ('ld_quiz_session', 8, 'status', '`status` enum(\'in_progress\',\'submitted\',\'expired\') NOT NULL DEFAULT \'in_progress\'', 'enum(\'in_progress\',\'submitted\',\'expired\')', 'NO', '\'in_progress\'', '', 'submitted_at'),
  ('ld_quiz_session', 9, 'question_order', '`question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`))', 'longtext', 'YES', 'NULL', '', 'status'),
  ('ld_quiz_session', 10, 'score', '`score` decimal(5,2) DEFAULT NULL', 'decimal(5,2)', 'YES', 'NULL', '', 'question_order'),
  ('ld_quiz_session', 11, 'passed', '`passed` tinyint(1) DEFAULT NULL', 'tinyint(1)', 'YES', 'NULL', '', 'score'),
  ('ld_quiz_session_answer', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_quiz_session_answer', 2, 'quiz_session_id', '`quiz_session_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_quiz_session_answer', 3, 'question_id', '`question_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'quiz_session_id'),
  ('ld_quiz_session_answer', 4, 'selected_option_id', '`selected_option_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'question_id'),
  ('ld_quiz_session_answer', 5, 'is_marked_for_review', '`is_marked_for_review` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'selected_option_id'),
  ('ld_quiz_session_answer', 6, 'answered_at', '`answered_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'is_marked_for_review'),
  ('ld_rating', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_rating', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_rating', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_rating', 4, 'rating', '`rating` tinyint(3) unsigned NOT NULL', 'tinyint(3) unsigned', 'NO', '<none>', '', 'course_id'),
  ('ld_rating', 5, 'comment', '`comment` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'rating'),
  ('ld_rating', 6, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'comment'),
  ('ld_recognition_course_map', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_recognition_course_map', 2, 'recognition_category', '`recognition_category` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'id'),
  ('ld_recognition_course_map', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'recognition_category'),
  ('ld_recognition_course_map', 4, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'course_id'),
  ('ld_recognition_course_map', 5, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_recognition_unlock', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_recognition_unlock', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_recognition_unlock', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'learner_id'),
  ('ld_recognition_unlock', 4, 'recognition_category', '`recognition_category` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'course_id'),
  ('ld_recognition_unlock', 5, 'external_reference_id', '`external_reference_id` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'recognition_category'),
  ('ld_recognition_unlock', 6, 'status', '`status` enum(\'unlocked\',\'redeemed\',\'expired\') NOT NULL DEFAULT \'unlocked\'', 'enum(\'unlocked\',\'redeemed\',\'expired\')', 'NO', '\'unlocked\'', '', 'external_reference_id'),
  ('ld_recognition_unlock', 7, 'unlocked_at', '`unlocked_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_recognition_unlock', 8, 'redeemed_at', '`redeemed_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'unlocked_at'),
  ('ld_recommendation_course_map', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_recommendation_course_map', 2, 'development_area', '`development_area` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'id'),
  ('ld_recommendation_course_map', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'development_area'),
  ('ld_recommendation_course_map', 4, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'course_id'),
  ('ld_recommendation_course_map', 5, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_report', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_report', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_report', 3, 'item_type', '`item_type` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'learner_id'),
  ('ld_report', 4, 'reference_id', '`reference_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'item_type');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_report', 5, 'reason', '`reason` text NOT NULL', 'text', 'NO', '<none>', '', 'reference_id'),
  ('ld_report', 6, 'status', '`status` enum(\'pending\',\'reviewed\',\'archived\') NOT NULL DEFAULT \'pending\'', 'enum(\'pending\',\'reviewed\',\'archived\')', 'NO', '\'pending\'', '', 'reason'),
  ('ld_report', 7, 'instructor_response', '`instructor_response` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'status'),
  ('ld_report', 8, 'instructor_responded_at', '`instructor_responded_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'instructor_response'),
  ('ld_report', 9, 'reviewed_by', '`reviewed_by` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'instructor_responded_at'),
  ('ld_report', 10, 'reviewed_at', '`reviewed_at` timestamp NULL DEFAULT NULL', 'timestamp', 'YES', 'NULL', '', 'reviewed_by'),
  ('ld_report', 11, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'reviewed_at'),
  ('ld_request', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_request', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_request', 3, 'requested_title', '`requested_title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'learner_id'),
  ('ld_request', 4, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'requested_title'),
  ('ld_request', 5, 'status', '`status` enum(\'pending\',\'reviewed\',\'archived\') NOT NULL DEFAULT \'pending\'', 'enum(\'pending\',\'reviewed\',\'archived\')', 'NO', '\'pending\'', '', 'description'),
  ('ld_request', 6, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_setting', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_setting', 2, 'setting_key', '`setting_key` varchar(100) NOT NULL', 'varchar(100)', 'NO', '<none>', '', 'id'),
  ('ld_setting', 3, 'setting_value', '`setting_value` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'setting_key'),
  ('ld_setting', 4, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'setting_value'),
  ('ld_skill', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_skill', 2, 'name', '`name` varchar(150) NOT NULL', 'varchar(150)', 'NO', '<none>', '', 'id'),
  ('ld_skill', 3, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'name'),
  ('ld_skill', 4, 'date_updated', '`date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'description'),
  ('ld_skill', 5, 'suggested', '`suggested` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'date_updated'),
  ('ld_skill', 6, 'status', '`status` enum(\'active\',\'archived\') NOT NULL DEFAULT \'active\'', 'enum(\'active\',\'archived\')', 'NO', '\'active\'', '', 'suggested'),
  ('ld_skill', 7, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_skill_snapshot', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_skill_snapshot', 2, 'learner_id', '`learner_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_skill_snapshot', 3, 'position_id', '`position_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'learner_id'),
  ('ld_skill_snapshot', 4, 'department_id', '`department_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'position_id'),
  ('ld_skill_snapshot', 5, 'total_skills', '`total_skills` int(10) unsigned NOT NULL DEFAULT 0', 'int(10) unsigned', 'NO', '0', '', 'department_id'),
  ('ld_skill_snapshot', 6, 'acquired_count', '`acquired_count` int(10) unsigned NOT NULL DEFAULT 0', 'int(10) unsigned', 'NO', '0', '', 'total_skills'),
  ('ld_skill_snapshot', 7, 'gap_count', '`gap_count` int(10) unsigned NOT NULL DEFAULT 0', 'int(10) unsigned', 'NO', '0', '', 'acquired_count'),
  ('ld_skill_snapshot', 8, 'acquired_skills', '`acquired_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`acquired_skills`))', 'longtext', 'YES', 'NULL', '', 'gap_count'),
  ('ld_skill_snapshot', 9, 'gap_skills', '`gap_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gap_skills`))', 'longtext', 'YES', 'NULL', '', 'acquired_skills'),
  ('ld_skill_snapshot', 10, 'snapshot_date', '`snapshot_date` date NOT NULL', 'date', 'NO', '<none>', '', 'gap_skills'),
  ('ld_skill_snapshot', 11, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'snapshot_date'),
  ('ld_training_recommendation', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_training_recommendation', 2, 'employee_id', '`employee_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_training_recommendation', 3, 'course_id', '`course_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'employee_id'),
  ('ld_training_recommendation', 4, 'development_area', '`development_area` varchar(150) NOT NULL', 'varchar(150)', 'NO', '<none>', '', 'course_id'),
  ('ld_training_recommendation', 5, 'priority_level', '`priority_level` enum(\'low\',\'medium\',\'high\') NOT NULL DEFAULT \'medium\'', 'enum(\'low\',\'medium\',\'high\')', 'NO', '\'medium\'', '', 'development_area');
INSERT INTO `_ld_schema_cols` (`table_name`, `ordinal`, `column_name`, `col_def`, `col_type`, `is_nullable`, `col_default`, `extra`, `after_col`) VALUES
  ('ld_training_recommendation', 6, 'source', '`source` varchar(50) NOT NULL', 'varchar(50)', 'NO', '<none>', '', 'priority_level'),
  ('ld_training_recommendation', 7, 'external_reference_id', '`external_reference_id` varchar(255) DEFAULT NULL', 'varchar(255)', 'YES', 'NULL', '', 'source'),
  ('ld_training_recommendation', 8, 'status', '`status` enum(\'recommended\',\'invited\',\'enrolled\',\'completed\',\'declined\') NOT NULL DEFAULT \'recommended\'', 'enum(\'recommended\',\'invited\',\'enrolled\',\'completed\',\'declined\')', 'NO', '\'recommended\'', '', 'external_reference_id'),
  ('ld_training_recommendation', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'status'),
  ('ld_user_event', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_user_event', 2, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'id'),
  ('ld_user_event', 3, 'description', '`description` text DEFAULT NULL', 'text', 'YES', 'NULL', '', 'title'),
  ('ld_user_event', 4, 'event_date', '`event_date` date NOT NULL', 'date', 'NO', '<none>', '', 'description'),
  ('ld_user_event', 5, 'event_time', '`event_time` time DEFAULT NULL', 'time', 'YES', 'NULL', '', 'event_date'),
  ('ld_user_event', 6, 'event_type', '`event_type` enum(\'meeting\',\'deadline\',\'reminder\',\'personal\',\'other\') DEFAULT \'personal\'', 'enum(\'meeting\',\'deadline\',\'reminder\',\'personal\',\'other\')', 'YES', '\'personal\'', '', 'event_time'),
  ('ld_user_event', 7, 'color', '`color` varchar(20) DEFAULT \'#320082\'', 'varchar(20)', 'YES', '\'#320082\'', '', 'event_type'),
  ('ld_user_event', 8, 'created_by', '`created_by` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'color'),
  ('ld_user_event', 9, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'created_by'),
  ('ld_user_event', 10, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at'),
  ('ld_video_conference', 1, 'id', '`id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned', 'NO', '<none>', 'auto_increment', NULL),
  ('ld_video_conference', 2, 'instructor_id', '`instructor_id` int(10) unsigned NOT NULL', 'int(10) unsigned', 'NO', '<none>', '', 'id'),
  ('ld_video_conference', 3, 'course_id', '`course_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'instructor_id'),
  ('ld_video_conference', 4, 'program_id', '`program_id` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'course_id'),
  ('ld_video_conference', 5, 'title', '`title` varchar(255) NOT NULL', 'varchar(255)', 'NO', '<none>', '', 'program_id'),
  ('ld_video_conference', 6, 'platform', '`platform` enum(\'zoom\',\'google_meet\',\'other\') NOT NULL DEFAULT \'google_meet\'', 'enum(\'zoom\',\'google_meet\',\'other\')', 'NO', '\'google_meet\'', '', 'title'),
  ('ld_video_conference', 7, 'meeting_link', '`meeting_link` varchar(500) NOT NULL', 'varchar(500)', 'NO', '<none>', '', 'platform'),
  ('ld_video_conference', 8, 'scheduled_at', '`scheduled_at` datetime NOT NULL', 'datetime', 'NO', '<none>', '', 'meeting_link'),
  ('ld_video_conference', 9, 'duration_minutes', '`duration_minutes` int(10) unsigned DEFAULT NULL', 'int(10) unsigned', 'YES', 'NULL', '', 'scheduled_at'),
  ('ld_video_conference', 10, 'status', '`status` enum(\'scheduled\',\'completed\',\'archived\') NOT NULL DEFAULT \'scheduled\'', 'enum(\'scheduled\',\'completed\',\'archived\')', 'NO', '\'scheduled\'', '', 'duration_minutes'),
  ('ld_video_conference', 11, 'first_reminder_sent', '`first_reminder_sent` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'status'),
  ('ld_video_conference', 12, 'second_reminder_sent', '`second_reminder_sent` tinyint(1) NOT NULL DEFAULT 0', 'tinyint(1)', 'NO', '0', '', 'first_reminder_sent'),
  ('ld_video_conference', 13, 'created_at', '`created_at` timestamp NOT NULL DEFAULT current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', '', 'second_reminder_sent'),
  ('ld_video_conference', 14, 'updated_at', '`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()', 'timestamp', 'NO', 'current_timestamp()', 'on update current_timestamp()', 'created_at');

INSERT INTO `_ld_schema_idx` (`table_name`, `seq`, `index_name`, `idx_def`) VALUES
  ('ld_announcement', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_api_key', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_assessment_result', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_assessment_result', 2, 'idx_assessment_learner', 'KEY `idx_assessment_learner` (`learner_id`)'),
  ('ld_assessment_result', 3, 'idx_assessment_source', 'KEY `idx_assessment_source` (`source`,`taken_at`)'),
  ('ld_audit_log', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_audit_log', 2, 'idx_audit_user', 'KEY `idx_audit_user` (`user_id`)'),
  ('ld_audit_log', 3, 'idx_audit_item', 'KEY `idx_audit_item` (`item_type`,`reference_id`)'),
  ('ld_bookmark', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_bookmark', 2, 'uniq_bookmark', 'UNIQUE KEY `uniq_bookmark` (`learner_id`,`item_type`,`reference_id`)'),
  ('ld_calendar_event', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_certificate', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_certificate', 2, 'verification_code', 'UNIQUE KEY `verification_code` (`verification_code`)'),
  ('ld_certificate', 3, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_certificate', 4, 'course_version_id', 'KEY `course_version_id` (`course_version_id`)'),
  ('ld_certificate', 5, 'completed_enrollment_id', 'KEY `completed_enrollment_id` (`completed_enrollment_id`)'),
  ('ld_certificate', 6, 'template_id', 'KEY `template_id` (`template_id`)'),
  ('ld_certificate_template', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_certificate_template', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_comment', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_comment', 2, 'lesson_id', 'KEY `lesson_id` (`lesson_id`)'),
  ('ld_comment', 3, 'parent_comment_id', 'KEY `parent_comment_id` (`parent_comment_id`)'),
  ('ld_conference_attendance', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_conference_attendance', 2, 'video_conference_id', 'KEY `video_conference_id` (`video_conference_id`)'),
  ('ld_course', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_course', 2, 'program_id', 'KEY `program_id` (`program_id`)'),
  ('ld_course_instructor', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_course_instructor', 2, 'uniq_course_instructor', 'UNIQUE KEY `uniq_course_instructor` (`course_id`,`instructor_id`)'),
  ('ld_course_skill', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_course_skill', 2, 'uniq_course_skill', 'UNIQUE KEY `uniq_course_skill` (`course_id`,`skill_id`)'),
  ('ld_course_skill', 3, 'skill_id', 'KEY `skill_id` (`skill_id`)'),
  ('ld_course_template', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_course_version', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_course_version', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_display_preference', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_display_preference', 2, 'user_id', 'UNIQUE KEY `user_id` (`user_id`)'),
  ('ld_engagement_snapshot', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_engagement_snapshot', 2, 'uniq_learner_snapshot', 'UNIQUE KEY `uniq_learner_snapshot` (`learner_id`,`snapshot_date`)'),
  ('ld_enrollment', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_enrollment', 2, 'uniq_learner_course', 'UNIQUE KEY `uniq_learner_course` (`learner_id`,`course_id`)');
INSERT INTO `_ld_schema_idx` (`table_name`, `seq`, `index_name`, `idx_def`) VALUES
  ('ld_enrollment', 3, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_enrollment', 4, 'course_version_id', 'KEY `course_version_id` (`course_version_id`)'),
  ('ld_evaluation', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_evaluation', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_evaluation_feedback', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_evaluation_feedback', 2, 'evaluation_id', 'KEY `evaluation_id` (`evaluation_id`)'),
  ('ld_favorite', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_favorite', 2, 'uniq_favorite', 'UNIQUE KEY `uniq_favorite` (`learner_id`,`item_type`,`reference_id`)'),
  ('ld_grade', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_grade', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_integration_event', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_integration_event', 2, 'uniq_module_external_ref', 'UNIQUE KEY `uniq_module_external_ref` (`module_name`,`external_reference_id`)'),
  ('ld_integration_log', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_integration_log', 2, 'idx_integration_log_module', 'KEY `idx_integration_log_module` (`module_name`,`direction`)'),
  ('ld_learning_path', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_learning_path', 2, 'idx_lp_type_public', 'KEY `idx_lp_type_public` (`type`,`is_public`,`status`)'),
  ('ld_learning_path_item', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_learning_path_item', 2, 'learning_path_id', 'KEY `learning_path_id` (`learning_path_id`)'),
  ('ld_learning_path_skill', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_learning_path_skill', 2, 'unique_lp_skill', 'UNIQUE KEY `unique_lp_skill` (`learning_path_id`,`skill_id`)'),
  ('ld_lesson', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_lesson', 2, 'module_id', 'KEY `module_id` (`module_id`)'),
  ('ld_lesson_file', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_lesson_file', 2, 'lesson_id', 'KEY `lesson_id` (`lesson_id`)'),
  ('ld_message', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_message', 2, 'idx_message_recipient', 'KEY `idx_message_recipient` (`recipient_id`,`is_read`)'),
  ('ld_module', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_module', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_module_skill', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_module_skill', 2, 'uniq_module_skill', 'UNIQUE KEY `uniq_module_skill` (`module_id`,`skill_id`)'),
  ('ld_module_skill', 3, 'skill_id', 'KEY `skill_id` (`skill_id`)'),
  ('ld_note', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_note', 2, 'idx_note_item', 'KEY `idx_note_item` (`item_type`,`reference_id`)'),
  ('ld_notification', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_notification', 2, 'idx_notification_user', 'KEY `idx_notification_user` (`user_id`,`is_read`)'),
  ('ld_notification_preference', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_notification_preference', 2, 'uniq_user_notif_type', 'UNIQUE KEY `uniq_user_notif_type` (`user_id`,`notification_type`)'),
  ('ld_prerequisite', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_prerequisite', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_prerequisite', 3, 'required_course_id', 'KEY `required_course_id` (`required_course_id`)');
INSERT INTO `_ld_schema_idx` (`table_name`, `seq`, `index_name`, `idx_def`) VALUES
  ('ld_prerequisite', 4, 'required_skill_id', 'KEY `required_skill_id` (`required_skill_id`)'),
  ('ld_program', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_program_skill', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_program_skill', 2, 'unique_prog_skill', 'UNIQUE KEY `unique_prog_skill` (`program_id`,`skill_id`)'),
  ('ld_progress', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_progress', 2, 'enrollment_id', 'KEY `enrollment_id` (`enrollment_id`)'),
  ('ld_progress', 3, 'idx_progress_item', 'KEY `idx_progress_item` (`item_type`,`reference_id`)'),
  ('ld_quiz', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz', 2, 'module_id', 'KEY `module_id` (`module_id`)'),
  ('ld_quiz_attempt', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz_attempt', 2, 'quiz_session_id', 'KEY `quiz_session_id` (`quiz_session_id`)'),
  ('ld_quiz_question', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz_question', 2, 'idx_quiz_question_ref', 'KEY `idx_quiz_question_ref` (`item_type`,`reference_id`)'),
  ('ld_quiz_question_option', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz_question_option', 2, 'question_id', 'KEY `question_id` (`question_id`)'),
  ('ld_quiz_session', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz_session', 2, 'idx_quiz_session_learner_ref', 'KEY `idx_quiz_session_learner_ref` (`learner_id`,`item_type`,`reference_id`)'),
  ('ld_quiz_session_answer', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_quiz_session_answer', 2, 'quiz_session_id', 'KEY `quiz_session_id` (`quiz_session_id`)'),
  ('ld_quiz_session_answer', 3, 'question_id', 'KEY `question_id` (`question_id`)'),
  ('ld_rating', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_rating', 2, 'uniq_learner_course_rating', 'UNIQUE KEY `uniq_learner_course_rating` (`learner_id`,`course_id`)'),
  ('ld_rating', 3, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_recognition_course_map', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_recognition_course_map', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_recognition_unlock', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_recognition_unlock', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_recognition_unlock', 3, 'idx_recognition_learner', 'KEY `idx_recognition_learner` (`learner_id`,`status`)'),
  ('ld_recommendation_course_map', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_recommendation_course_map', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_report', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_request', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_setting', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_setting', 2, 'setting_key', 'UNIQUE KEY `setting_key` (`setting_key`)'),
  ('ld_skill', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_skill_snapshot', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_skill_snapshot', 2, 'uniq_learner_date', 'UNIQUE KEY `uniq_learner_date` (`learner_id`,`snapshot_date`)'),
  ('ld_skill_snapshot', 3, 'idx_learner', 'KEY `idx_learner` (`learner_id`)'),
  ('ld_skill_snapshot', 4, 'idx_skill_snapshot_learner_date', 'KEY `idx_skill_snapshot_learner_date` (`learner_id`,`snapshot_date`)'),
  ('ld_skill_snapshot', 5, 'idx_skill_snapshot_dept', 'KEY `idx_skill_snapshot_dept` (`department_id`)');
INSERT INTO `_ld_schema_idx` (`table_name`, `seq`, `index_name`, `idx_def`) VALUES
  ('ld_training_recommendation', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_training_recommendation', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_training_recommendation', 3, 'idx_training_reco_employee', 'KEY `idx_training_reco_employee` (`employee_id`,`status`)'),
  ('ld_training_recommendation', 4, 'idx_training_reco_area', 'KEY `idx_training_reco_area` (`development_area`)'),
  ('ld_user_event', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_user_event', 2, 'idx_event_date', 'KEY `idx_event_date` (`event_date`)'),
  ('ld_user_event', 3, 'idx_created_by', 'KEY `idx_created_by` (`created_by`)'),
  ('ld_video_conference', 1, 'PRIMARY', 'PRIMARY KEY (`id`)'),
  ('ld_video_conference', 2, 'course_id', 'KEY `course_id` (`course_id`)'),
  ('ld_video_conference', 3, 'program_id', 'KEY `program_id` (`program_id`)');

INSERT INTO `_ld_schema_con` (`table_name`, `seq`, `constraint_name`, `constraint_type`, `con_def`) VALUES
  ('ld_certificate', 1, 'ld_certificate_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_certificate_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_certificate', 2, 'ld_certificate_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_certificate_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL'),
  ('ld_certificate', 3, 'ld_certificate_ibfk_3', 'FOREIGN KEY', 'CONSTRAINT `ld_certificate_ibfk_3` FOREIGN KEY (`completed_enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE'),
  ('ld_certificate', 4, 'ld_certificate_ibfk_4', 'FOREIGN KEY', 'CONSTRAINT `ld_certificate_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `ld_certificate_template` (`id`) ON DELETE SET NULL'),
  ('ld_certificate_template', 1, 'ld_certificate_template_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_certificate_template_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL'),
  ('ld_comment', 1, 'ld_comment_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_comment_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE'),
  ('ld_comment', 2, 'ld_comment_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_comment_ibfk_2` FOREIGN KEY (`parent_comment_id`) REFERENCES `ld_comment` (`id`) ON DELETE CASCADE'),
  ('ld_conference_attendance', 1, 'ld_conference_attendance_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_conference_attendance_ibfk_1` FOREIGN KEY (`video_conference_id`) REFERENCES `ld_video_conference` (`id`) ON DELETE CASCADE'),
  ('ld_course', 1, 'ld_course_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_course_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL'),
  ('ld_course_instructor', 1, 'ld_course_instructor_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_course_instructor_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_course_skill', 1, 'ld_course_skill_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_course_skill_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_course_skill', 2, 'ld_course_skill_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_course_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE'),
  ('ld_course_version', 1, 'ld_course_version_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_course_version_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_enrollment', 1, 'ld_enrollment_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_enrollment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_enrollment', 2, 'ld_enrollment_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_enrollment_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL'),
  ('ld_evaluation', 1, 'ld_evaluation_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_evaluation_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_evaluation_feedback', 1, 'ld_evaluation_feedback_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_evaluation_feedback_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `ld_evaluation` (`id`) ON DELETE CASCADE'),
  ('ld_grade', 1, 'ld_grade_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_grade_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_learning_path_item', 1, 'ld_learning_path_item_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_learning_path_item_ibfk_1` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE'),
  ('ld_lesson', 1, 'ld_lesson_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_lesson_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE'),
  ('ld_lesson_file', 1, 'ld_lesson_file_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_lesson_file_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE'),
  ('ld_module', 1, 'ld_module_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_module_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_module_skill', 1, 'ld_module_skill_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_module_skill_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE'),
  ('ld_module_skill', 2, 'ld_module_skill_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_module_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE'),
  ('ld_prerequisite', 1, 'ld_prerequisite_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_prerequisite', 2, 'ld_prerequisite_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_prerequisite_ibfk_2` FOREIGN KEY (`required_course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_prerequisite', 3, 'ld_prerequisite_ibfk_3', 'FOREIGN KEY', 'CONSTRAINT `ld_prerequisite_ibfk_3` FOREIGN KEY (`required_skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE'),
  ('ld_progress', 1, 'ld_progress_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE'),
  ('ld_quiz', 1, 'ld_quiz_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_quiz_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE'),
  ('ld_quiz_attempt', 1, 'ld_quiz_attempt_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_quiz_attempt_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE SET NULL'),
  ('ld_quiz_question_option', 1, 'ld_quiz_question_option_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_quiz_question_option_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE'),
  ('ld_quiz_session_answer', 1, 'ld_quiz_session_answer_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_quiz_session_answer_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE CASCADE'),
  ('ld_quiz_session_answer', 2, 'ld_quiz_session_answer_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_quiz_session_answer_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE'),
  ('ld_rating', 1, 'ld_rating_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_rating_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_recognition_course_map', 1, 'ld_recognition_course_map_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_recognition_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_recognition_unlock', 1, 'ld_recognition_unlock_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_recognition_unlock_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_recommendation_course_map', 1, 'ld_recommendation_course_map_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_recommendation_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_training_recommendation', 1, 'ld_training_recommendation_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_training_recommendation_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE'),
  ('ld_video_conference', 1, 'ld_video_conference_ibfk_1', 'FOREIGN KEY', 'CONSTRAINT `ld_video_conference_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL'),
  ('ld_video_conference', 2, 'ld_video_conference_ibfk_2', 'FOREIGN KEY', 'CONSTRAINT `ld_video_conference_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL');

-- -----------------------------------------------------------------------
-- 3) APPLY THE DIFFERENCES TO TABLES THAT ALREADY EXIST
-- -----------------------------------------------------------------------

DROP PROCEDURE IF EXISTS `_ld_schema_sync`;
DELIMITER $$
CREATE PROCEDURE `_ld_schema_sync`()
BEGIN
    DECLARE v_done     INT          DEFAULT 0;
    DECLARE v_ddl      LONGTEXT;

    DECLARE v_table    VARCHAR(64);
    DECLARE v_engine   VARCHAR(64);
    DECLARE v_charset  VARCHAR(64);
    DECLARE v_coll     VARCHAR(64);

    DECLARE v_ord      INT;
    DECLARE v_col      VARCHAR(64);
    DECLARE v_coldef   LONGTEXT;
    DECLARE v_coltype  VARCHAR(255);
    DECLARE v_nullable VARCHAR(3);
    DECLARE v_default  TEXT;
    DECLARE v_extra    VARCHAR(255);
    DECLARE v_after    VARCHAR(64);

    DECLARE v_index    VARCHAR(64);
    DECLARE v_indexdef LONGTEXT;

    DECLARE v_conname  VARCHAR(64);
    DECLARE v_contype  VARCHAR(64);
    DECLARE v_condef   LONGTEXT;

    DECLARE cur_tbl CURSOR FOR
        SELECT table_name, engine, charset, collation
          FROM `_ld_schema_tables` ORDER BY table_name;
    DECLARE cur_col CURSOR FOR
        SELECT table_name, ordinal, column_name, col_def, col_type, is_nullable, col_default, extra, after_col
          FROM `_ld_schema_cols` ORDER BY table_name, ordinal;
    DECLARE cur_idx CURSOR FOR
        SELECT table_name, index_name, idx_def
          FROM `_ld_schema_idx` ORDER BY table_name, seq;
    DECLARE cur_con CURSOR FOR
        SELECT table_name, constraint_name, constraint_type, con_def
          FROM `_ld_schema_con` ORDER BY table_name, seq;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    /* --- table options: engine / charset / collation --- */
    SET v_done = 0;
    OPEN cur_tbl;
    tbl_loop: LOOP
        FETCH cur_tbl INTO v_table, v_engine, v_charset, v_coll;
        IF v_done = 1 THEN LEAVE tbl_loop; END IF;
        IF EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)
           AND NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table
                               AND ENGINE = v_engine AND TABLE_COLLATION = v_coll) THEN
            SET v_ddl = CONCAT('ALTER TABLE `', v_table, '` ENGINE=', v_engine,
                               ' DEFAULT CHARACTER SET ', v_charset, ' COLLATE ', v_coll);
            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;
        END IF;
    END LOOP;
    CLOSE cur_tbl;

    /* --- columns: add missing, fix changed --- */
    SET v_done = 0;
    OPEN cur_col;
    col_loop: LOOP
        FETCH cur_col INTO v_table, v_ord, v_col, v_coldef, v_coltype, v_nullable, v_default, v_extra, v_after;
        IF v_done = 1 THEN LEAVE col_loop; END IF;
        IF EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table) THEN
            IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table
                               AND COLUMN_NAME = v_col) THEN
                SET v_ddl = CONCAT('ALTER TABLE `', v_table, '` ADD COLUMN ', v_coldef,
                                   CASE WHEN v_after IS NULL THEN ' FIRST'
                                        ELSE CONCAT(' AFTER `', v_after, '`') END);
                PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;
            ELSEIF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table
                                   AND COLUMN_NAME = v_col
                                   AND COLUMN_TYPE <=> v_coltype
                                   AND IS_NULLABLE <=> v_nullable
                                   AND EXTRA <=> v_extra
                                   AND IFNULL(COLUMN_DEFAULT, '<none>') = v_default) THEN
                SET v_ddl = CONCAT('ALTER TABLE `', v_table, '` MODIFY COLUMN ', v_coldef);
                PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;
            END IF;
        END IF;
    END LOOP;
    CLOSE cur_col;

    /* --- indexes: add missing --- */
    SET v_done = 0;
    OPEN cur_idx;
    idx_loop: LOOP
        FETCH cur_idx INTO v_table, v_index, v_indexdef;
        IF v_done = 1 THEN LEAVE idx_loop; END IF;
        IF EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)
           AND NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table
                               AND INDEX_NAME = v_index) THEN
            SET v_ddl = CONCAT('ALTER TABLE `', v_table, '` ADD ', v_indexdef);
            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;
        END IF;
    END LOOP;
    CLOSE cur_idx;

    /* --- constraints: add missing foreign keys / checks --- */
    SET v_done = 0;
    OPEN cur_con;
    con_loop: LOOP
        FETCH cur_con INTO v_table, v_conname, v_contype, v_condef;
        IF v_done = 1 THEN LEAVE con_loop; END IF;
        IF EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)
           AND NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = v_table
                               AND CONSTRAINT_NAME = v_conname) THEN
            SET v_ddl = CONCAT('ALTER TABLE `', v_table, '` ADD ', v_condef);
            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;
        END IF;
    END LOOP;
    CLOSE cur_con;
END$$
DELIMITER ;

CALL `_ld_schema_sync`();

-- -----------------------------------------------------------------------
-- 4) CLEAN UP THE HELPER OBJECTS
-- -----------------------------------------------------------------------

DROP PROCEDURE IF EXISTS `_ld_schema_sync`;
DROP TABLE IF EXISTS `_ld_schema_con`;
DROP TABLE IF EXISTS `_ld_schema_idx`;
DROP TABLE IF EXISTS `_ld_schema_cols`;
DROP TABLE IF EXISTS `_ld_schema_tables`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT CONCAT('ld_ schema synced — ', COUNT(*), ' table(s) present.') AS result
  FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE 'ld\_%';
