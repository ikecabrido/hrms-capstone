-- =======================================================================
-- Learning module (ld_*) schema for the host server
--
-- Upload this with  Admin > Moderation > Export LD Database > Apply schema file
--   * tick "confirmation" (the file drops and recreates tables)
--   * then use ld_host_data_2026-10-17.sql with "Import row data"
--
-- Drops and recreates all 72 ld_* tables from the reference database
-- (payr_bcp), keeping AUTO_INCREMENT on the ids the sent dump already had it.
-- 72 DROP + 72 CREATE statements.
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `ld_announcement`;
CREATE TABLE `ld_announcement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `audience` set('all','instructor','learner','admin') NOT NULL DEFAULT 'all',
  `posted_by` int(10) unsigned NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_api_key`;
CREATE TABLE `ld_api_key` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_assessment_result`;
CREATE TABLE `ld_assessment_result` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_audit_log`;
CREATE TABLE `ld_audit_log` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `role` enum('admin','instructor','learner') NOT NULL,
  `action` enum('create','edit','archive','restore','review') NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_item` (`item_type`,`reference_id`),
  CONSTRAINT `fk_ld_audit_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_bookmark`;
CREATE TABLE `ld_bookmark` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bookmark` (`learner_id`,`item_type`,`reference_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_calendar_event`;
CREATE TABLE `ld_calendar_event` (

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

DROP TABLE IF EXISTS `ld_certificate`;
CREATE TABLE `ld_certificate` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_certificate_template`;
CREATE TABLE `ld_certificate_template` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_comment`;
CREATE TABLE `ld_comment` (

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

DROP TABLE IF EXISTS `ld_competency_requirement`;
CREATE TABLE `ld_competency_requirement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `skill_name` varchar(150) NOT NULL,
  `target_department` varchar(150) NOT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `employees_needing_training` int(10) unsigned NOT NULL,
  `target_proficiency` varchar(50) NOT NULL,
  `expected_learning_output` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','planned','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_competency_status` (`status`),
  KEY `idx_competency_priority` (`priority_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_conference_attendance`;
CREATE TABLE `ld_conference_attendance` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `video_conference_id` int(10) unsigned NOT NULL,
  `learner_id` int(10) unsigned NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_conference_id` (`video_conference_id`),
  CONSTRAINT `ld_conference_attendance_ibfk_1` FOREIGN KEY (`video_conference_id`) REFERENCES `ld_video_conference` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_course`;
CREATE TABLE `ld_course` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `delivery_mode` enum('online','face_to_face','hybrid') NOT NULL DEFAULT 'online',
  `start_date` date DEFAULT NULL,
  `enrollment_deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ld_course_instructor_employee` (`instructor_id`),
  CONSTRAINT `fk_ld_course_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_course_instructor`;
CREATE TABLE `ld_course_instructor` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `instructor_id` int(10) unsigned NOT NULL,
  `role` enum('owner','co-instructor') NOT NULL DEFAULT 'owner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_instructor` (`course_id`,`instructor_id`),
  CONSTRAINT `ld_course_instructor_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_course_skill`;
CREATE TABLE `ld_course_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_skill` (`course_id`,`skill_id`),
  KEY `ld_course_skill_ibfk_2` (`skill_id`),
  CONSTRAINT `ld_course_skill_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_course_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_course_template`;
CREATE TABLE `ld_course_template` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_course_version`;
CREATE TABLE `ld_course_version` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_course_version_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_cron_run`;
CREATE TABLE `ld_cron_run` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job` varchar(80) NOT NULL,
  `trigger_type` enum('scheduled','manual','cli') NOT NULL DEFAULT 'scheduled',
  `status` enum('running','success','failed') NOT NULL DEFAULT 'running',
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `records_processed` int(10) unsigned DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `actor_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cron_run_job` (`job`,`started_at`),
  KEY `idx_cron_run_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_display_preference`;
CREATE TABLE `ld_display_preference` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `page_size` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_ld_display_preference_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_display_preference_store`;
CREATE TABLE `ld_display_preference_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `page_size` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_display_preference_user` (`user_id`),
  CONSTRAINT `fk_ld_display_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_engagement_snapshot`;
CREATE TABLE `ld_engagement_snapshot` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_enrollment`;
CREATE TABLE `ld_enrollment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(11) NOT NULL,
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
  CONSTRAINT `fk_ld_enrollment_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ld_enrollment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_enrollment_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_evaluation`;
CREATE TABLE `ld_evaluation` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_evaluation_feedback`;
CREATE TABLE `ld_evaluation_feedback` (

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

DROP TABLE IF EXISTS `ld_favorite`;
CREATE TABLE `ld_favorite` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_favorite` (`learner_id`,`item_type`,`reference_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_grade`;
CREATE TABLE `ld_grade` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(11) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `fk_ld_grade_learner_employee` (`learner_id`),
  CONSTRAINT `fk_ld_grade_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ld_grade_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_instructor`;
CREATE TABLE `ld_instructor` (
  `employee_id` int(10) unsigned NOT NULL,
  `designated_by` int(10) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `designated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_integration_event`;
CREATE TABLE `ld_integration_event` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `external_reference_id` varchar(255) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_external_ref` (`module_name`,`external_reference_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_integration_log`;
CREATE TABLE `ld_integration_log` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_learning_path`;
CREATE TABLE `ld_learning_path` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` enum('standard','knowledge_transfer') NOT NULL DEFAULT 'standard',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `kt_plan_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ld_learning_path_instructor_employee` (`instructor_id`),
  KEY `idx_ld_learning_path_visibility` (`status`,`is_public`),
  KEY `idx_ld_learning_path_kt_plan` (`kt_plan_id`),
  CONSTRAINT `fk_ld_learning_path_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_learning_path_invitation`;
CREATE TABLE `ld_learning_path_invitation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `status` enum('invited','enrolled','declined','withdrawn') NOT NULL DEFAULT 'invited',
  `invited_by` int(10) unsigned DEFAULT NULL,
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ld_path_invitation_recipient` (`learning_path_id`,`employee_id`),
  KEY `idx_ld_path_invitation_employee` (`employee_id`,`status`),
  CONSTRAINT `fk_ld_path_invitation_path` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_learning_path_item`;
CREATE TABLE `ld_learning_path_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` int(10) unsigned NOT NULL,
  `item_type` enum('course','module','lesson','quiz','evaluation','program','video-conference') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `order_index` int(10) unsigned DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `learning_path_id` (`learning_path_id`),
  CONSTRAINT `ld_learning_path_item_ibfk_1` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_learning_path_skill`;
CREATE TABLE `ld_learning_path_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lp_skill` (`learning_path_id`,`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_lesson`;
CREATE TABLE `ld_lesson` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_lesson_file`;
CREATE TABLE `ld_lesson_file` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` int(10) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  CONSTRAINT `ld_lesson_file_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_message`;
CREATE TABLE `ld_message` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_message_recipient` (`recipient_id`,`is_read`),
  KEY `fk_ld_message_sender_employee` (`sender_id`),
  CONSTRAINT `fk_ld_message_recipient_employee` FOREIGN KEY (`recipient_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ld_message_sender_employee` FOREIGN KEY (`sender_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_module`;
CREATE TABLE `ld_module` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_module_skill`;
CREATE TABLE `ld_module_skill` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_skill` (`module_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `ld_module_skill_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ld_module_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_note`;
CREATE TABLE `ld_note` (

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

DROP TABLE IF EXISTS `ld_notification`;
CREATE TABLE `ld_notification` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_ld_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_notification_inbox`;
CREATE TABLE `ld_notification_inbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification_inbox_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_ld_notification_inbox_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_notification_preference`;
CREATE TABLE `ld_notification_preference` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_notif_type` (`user_id`,`notification_type`),
  CONSTRAINT `fk_ld_notification_preference_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_notification_preference_store`;
CREATE TABLE `ld_notification_preference_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_preference_type` (`user_id`,`notification_type`),
  CONSTRAINT `fk_ld_notification_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_prerequisite`;
CREATE TABLE `ld_prerequisite` (

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

DROP TABLE IF EXISTS `ld_program`;
CREATE TABLE `ld_program` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ld_program_instructor_employee` (`instructor_id`),
  CONSTRAINT `fk_ld_program_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_program_skill`;
CREATE TABLE `ld_program_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prog_skill` (`program_id`,`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_progress`;
CREATE TABLE `ld_progress` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz`;
CREATE TABLE `ld_quiz` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz_attempt`;
CREATE TABLE `ld_quiz_attempt` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz_question`;
CREATE TABLE `ld_quiz_question` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz_question_option`;
CREATE TABLE `ld_quiz_question_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `ld_quiz_question_option_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz_session`;
CREATE TABLE `ld_quiz_session` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_quiz_session_answer`;
CREATE TABLE `ld_quiz_session_answer` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_rating`;
CREATE TABLE `ld_rating` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(11) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_course_rating` (`learner_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `fk_ld_rating_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ld_rating_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_recognition_course_map`;
CREATE TABLE `ld_recognition_course_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recognition_category` varchar(255) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_recognition_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_recognition_unlock`;
CREATE TABLE `ld_recognition_unlock` (
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

DROP TABLE IF EXISTS `ld_recommendation_course_map`;
CREATE TABLE `ld_recommendation_course_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `development_area` varchar(255) NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `ld_recommendation_course_map_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_report`;
CREATE TABLE `ld_report` (

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

DROP TABLE IF EXISTS `ld_reporting_requirement`;
CREATE TABLE `ld_reporting_requirement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `target_name` varchar(150) NOT NULL,
  `reporting_period_start` date NOT NULL,
  `reporting_period_end` date NOT NULL,
  `reporting_frequency` enum('weekly','monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
  `completion_target` decimal(5,2) NOT NULL,
  `expected_output` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','in_progress','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reporting_status` (`status`),
  KEY `idx_reporting_period` (`reporting_period_start`,`reporting_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_request`;
CREATE TABLE `ld_request` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(11) NOT NULL,
  `requested_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ld_request_learner_employee` (`learner_id`),
  CONSTRAINT `fk_ld_request_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_setting`;
CREATE TABLE `ld_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_skill`;
CREATE TABLE `ld_skill` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `suggested` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_skill_snapshot`;
CREATE TABLE `ld_skill_snapshot` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_study_time_log`;
CREATE TABLE `ld_study_time_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned NOT NULL,
  `enrollment_id` int(10) unsigned NOT NULL,
  `session_token` char(36) NOT NULL,
  `sequence_no` int(10) unsigned NOT NULL,
  `active_seconds` smallint(5) unsigned NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ld_study_time_session_sequence` (`session_token`,`sequence_no`),
  KEY `idx_ld_study_time_learner` (`learner_id`,`recorded_at`),
  KEY `idx_ld_study_time_enrollment` (`enrollment_id`),
  CONSTRAINT `fk_ld_study_time_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_training_engagement`;
CREATE TABLE `ld_training_engagement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `learner_team_type` enum('individual-learner','department-team','role-group') NOT NULL DEFAULT 'individual-learner',
  `learner_id` int(10) unsigned DEFAULT NULL,
  `learner_name` varchar(150) DEFAULT NULL,
  `department` varchar(150) NOT NULL,
  `training_activity` enum('course','learning-path','workshop','compliance-training','other') NOT NULL DEFAULT 'course',
  `engagement_status` enum('active','in-progress','low','inactive') NOT NULL DEFAULT 'active',
  `engagement_data` text DEFAULT NULL,
  `recommended_action` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','monitoring','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_engagement_ref` (`source_module`,`external_reference_id`),
  KEY `idx_engagement_status` (`status`),
  KEY `idx_engagement_signal` (`engagement_status`),
  KEY `idx_engagement_department` (`department`),
  KEY `idx_engagement_learner` (`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_training_recommendation`;
CREATE TABLE `ld_training_recommendation` (
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

DROP TABLE IF EXISTS `ld_training_requests`;
CREATE TABLE `ld_training_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) unsigned DEFAULT NULL,
  `skill_id` int(10) unsigned DEFAULT NULL,
  `skill_name` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `instructor_id` int(10) unsigned DEFAULT NULL,
  `instructor_note` text DEFAULT NULL,
  `target_department` varchar(150) DEFAULT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
  `source_module` varchar(80) NOT NULL DEFAULT 'learning',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_training_request_status` (`status`),
  KEY `idx_training_request_learner` (`learner_id`),
  KEY `idx_training_request_skill` (`skill_id`),
  KEY `idx_training_request_department` (`target_department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_turnover_risk`;
CREATE TABLE `ld_turnover_risk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `employee_id` int(10) unsigned DEFAULT NULL,
  `employee_name` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `position_name` varchar(150) DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `risk_score` decimal(5,2) DEFAULT NULL,
  `risk_factors` text DEFAULT NULL,
  `recommended_action` text DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `status` enum('received','monitoring','mitigated','escalated') NOT NULL DEFAULT 'received',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_turnover_risk_ref` (`source_module`,`external_reference_id`),
  KEY `idx_turnover_risk_status` (`status`),
  KEY `idx_turnover_risk_level` (`risk_level`),
  KEY `idx_turnover_risk_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_user_event`;
CREATE TABLE `ld_user_event` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_user_profile`;
CREATE TABLE `ld_user_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ld_user_profile_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_user_role`;
CREATE TABLE `ld_user_role` (
  `employee_id` int(11) NOT NULL,
  `learning_role` enum('admin','instructor','learner') NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_id`),
  KEY `idx_ld_user_role_role` (`learning_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ld_user_session`;
CREATE TABLE `ld_user_session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_token` char(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_label` varchar(120) DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ld_user_session_token` (`session_token`),
  KEY `idx_ld_user_session_employee` (`employee_id`,`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ld_video_conference`;
CREATE TABLE `ld_video_conference` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
