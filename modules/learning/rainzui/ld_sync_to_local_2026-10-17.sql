-- =======================================================================
-- Learning module (ld_*) data + schema synchronisation
--
-- Source      : ld_database_2026-10-17_100250.sql (the sent dump, 68 ld_* tables)
-- Reference   : payr_bcp (local, 72 ld_* tables)
-- Generated   : 2026-10-17
--
-- What it does
--   1. Creates the 4 ld_* tables the dump is missing:
--      ld_display_preference_store, ld_notification_inbox,
--      ld_notification_preference_store, ld_study_time_log.
--   2. Modifies every column that differs from the reference
--      (ld_announcement.audience  enum -> set  for multi-audience).
--   3. Rewrites every ld_* table so its rows match the reference:
--      the table is cleared and its current reference rows are inserted.
--      Clearing first is required - 24 tables carry secondary UNIQUE
--      keys where a plain upsert could silently update the wrong row.
--
-- Kept deliberately (does NOT match the reference)
--   AUTO_INCREMENT on the `id` column of 26 tables. payr_bcp lacks it,
--   the sent dump has it; it is kept because removing it breaks inserts.
--
-- Notes
--   Two reference rows carry id = 0 (ld_grade, ld_request). Because those
--   ids stay AUTO_INCREMENT on the target, the script sets
--   NO_AUTO_VALUE_ON_ZERO for its own session (as mysqldump does) so the
--   zeros are stored verbatim instead of being replaced by the next id.
--
-- Re-runnable / idempotent. Run with:
--   mysql -u <user> -p <database> < ld_sync_to_local_2026-10-17.sql
--   (or paste it into phpMyAdmin -> SQL)
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Preserve explicit 0 ids in AUTO_INCREMENT columns (mysqldump does the same).
SET @OLD_SQL_MODE = @@SQL_MODE;
SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@sql_mode, ''), 'NO_AUTO_VALUE_ON_ZERO');

-- ---------------------------------------------------------------------
-- 1. Tables missing from the dump
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ld_display_preference_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `page_size` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_display_preference_user` (`user_id`),
  CONSTRAINT `fk_ld_display_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_notification_inbox` (
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

CREATE TABLE IF NOT EXISTS `ld_notification_preference_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_preference_type` (`user_id`,`notification_type`),
  CONSTRAINT `fk_ld_notification_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_study_time_log` (
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

-- ---------------------------------------------------------------------
-- 2. Column changes
-- ---------------------------------------------------------------------

ALTER TABLE `ld_announcement` MODIFY COLUMN `audience` set('all','instructor','learner','admin') NOT NULL DEFAULT 'all';

-- ---------------------------------------------------------------------
-- 3. Row data (each table is cleared, then filled from the reference)
-- ---------------------------------------------------------------------

-- ld_announcement  (2 rows)
DELETE FROM `ld_announcement`;
INSERT INTO `ld_announcement` (`id`, `title`, `message`, `audience`, `posted_by`, `status`, `created_at`, `expires_at`) VALUES
('1', 'Test', 'sadsadsaw', 'all', '1007', 'active', '2026-10-09 10:56:31', NULL),
('9', 'sda', 'dsadsad', 'all', '1007', 'active', '2026-10-16 19:44:33', NULL);

-- ld_api_key  (8 rows)
DELETE FROM `ld_api_key`;
INSERT INTO `ld_api_key` (`id`, `module_name`, `api_key`, `is_active`, `created_at`) VALUES
('1', 'performance-management', 'pm_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2', '1', '2026-08-30 04:09:17'),
('2', 'recruitment', 'rc_b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3', '1', '2026-08-30 04:09:17'),
('3', 'employee-management', 'em_c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4', '1', '2026-08-30 04:09:17'),
('4', 'employee-engagement', 'ee_d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5', '1', '2026-08-30 04:09:17'),
('5', 'compliance-legal', 'cl_e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6', '1', '2026-08-30 04:09:17'),
('6', 'workforce-analytics', 'wa_f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7', '1', '2026-08-30 04:09:17'),
('7', 'time-attendance', 'ta_a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8', '1', '2026-08-30 04:09:17'),
('8', 'learning-development', 'ld_b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9', '1', '2026-08-30 04:09:17');

-- ld_assessment_result
DELETE FROM `ld_assessment_result`;

-- ld_audit_log
DELETE FROM `ld_audit_log`;

-- ld_bookmark
DELETE FROM `ld_bookmark`;

-- ld_calendar_event
DELETE FROM `ld_calendar_event`;

-- ld_certificate  (2 rows)
DELETE FROM `ld_certificate`;
INSERT INTO `ld_certificate` (`id`, `learner_id`, `course_id`, `course_version_id`, `completed_enrollment_id`, `template_id`, `verification_code`, `file_path`, `issued_at`, `valid_until`, `status`) VALUES
('1', '1006', '5', NULL, '1', NULL, 'CCF1F1EB869F0996', NULL, '2026-10-09 01:15:10', '2027-10-08', 'active'),
('3', '1006', '8', NULL, '5', NULL, 'EDCD2C86306B6251', NULL, '2026-10-15 23:49:25', '2027-10-15', 'active');

-- ld_certificate_template
DELETE FROM `ld_certificate_template`;

-- ld_comment
DELETE FROM `ld_comment`;

-- ld_competency_requirement  (4 rows)
DELETE FROM `ld_competency_requirement`;
INSERT INTO `ld_competency_requirement` (`id`, `source_module`, `skill_name`, `target_department`, `priority_level`, `employees_needing_training`, `target_proficiency`, `expected_learning_output`, `notes`, `status`, `created_by`, `created_at`) VALUES
('1', 'workforce', 'Data analysis', 'IT DEPARTMENT', 'high', '12', 'Advanced', 'Skills course and completion report by department', 'From workforce prediction', 'planned', NULL, '2026-09-25 05:29:34'),
('2', 'workforce', 'Cybersecurity awareness', 'ADMIN STAFF', 'critical', '7', 'Intermediate', 'Skills course and completion report by department', 'Written by the Workforce module directly', 'planned', '4', '2026-09-25 05:35:23'),
('3', 'workforce', 'Records management', 'ADMIN STAFF', 'medium', '3', 'Intermediate', 'Records handling course', NULL, 'planned', NULL, '2026-09-25 05:35:36'),
('4', 'workforce', 'Advanced Excel for payroll', 'ADMIN STAFF', 'critical', '9', 'Advanced', 'Payroll staff can build reconciliation models', 'Raised by the workforce probe', 'received', '1018', '2026-09-25 09:51:34');

-- ld_conference_attendance
DELETE FROM `ld_conference_attendance`;

-- ld_course  (8 rows)
DELETE FROM `ld_course`;
INSERT INTO `ld_course` (`id`, `instructor_id`, `title`, `description`, `thumbnail_path`, `category`, `status`, `delivery_mode`, `start_date`, `enrollment_deadline`, `created_at`, `updated_at`) VALUES
('5', '1007', 'Web Development Fundamentals', 'Learn web development fundamentals including HTML, CSS, and JavaScript.', NULL, 'Programming', 'active', 'online', '2026-10-08', '2027-01-01', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('6', '1007', 'Digital Design & Visual Communication', 'Explore visual design principles for digital media.', NULL, 'Design & Creative', 'active', 'online', '2026-10-08', '2027-01-01', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('7', '1001', 'Workplace Safety and Compliance Essentials', 'Learn the core safety practices, employee responsibilities, and compliance rules that keep the workplace healthy and incident-free.', NULL, 'Compliance & Safety', 'active', 'online', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('8', '1001', 'Effective Business Communication', 'Build clear written, verbal, and interpersonal communication skills for everyday professional interactions.', NULL, 'Communication', 'active', 'online', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('9', '1001', 'Data Analysis with Microsoft Excel', 'Turn raw spreadsheets into clean data, useful charts, and decision-ready reports using Microsoft Excel.', NULL, 'Data & Analytics', 'active', 'online', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('10', '1001', 'Leadership Foundations for New Managers', 'A practical introduction to leading teams: setting expectations, coaching people, and holding performance conversations.', NULL, 'Leadership', 'active', 'hybrid', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('11', '1001', 'Customer Service Excellence', 'Deliver consistent, empathetic customer experiences and handle difficult situations with confidence.', NULL, 'Customer Experience', 'active', 'online', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('12', '1001', 'Cybersecurity Awareness for Employees', 'Recognize common cyber threats, protect company data, and follow safe browsing and password practices.', NULL, 'Security', 'active', 'online', NULL, NULL, '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_course_instructor  (6 rows)
DELETE FROM `ld_course_instructor`;
INSERT INTO `ld_course_instructor` (`id`, `course_id`, `instructor_id`, `role`, `created_at`) VALUES
('1', '7', '1001', 'owner', '2026-10-15 21:24:54'),
('2', '8', '1001', 'owner', '2026-10-15 21:24:54'),
('3', '9', '1001', 'owner', '2026-10-15 21:24:54'),
('4', '10', '1001', 'owner', '2026-10-15 21:24:54'),
('5', '11', '1001', 'owner', '2026-10-15 21:24:54'),
('6', '12', '1001', 'owner', '2026-10-15 21:24:54');

-- ld_course_skill  (10 rows)
DELETE FROM `ld_course_skill`;
INSERT INTO `ld_course_skill` (`id`, `course_id`, `skill_id`) VALUES
('2', '5', '3'),
('4', '6', '4'),
('3', '6', '13'),
('11', '7', '16'),
('6', '8', '3'),
('7', '9', '5'),
('10', '9', '14'),
('5', '10', '2'),
('9', '11', '8'),
('8', '12', '6');

-- ld_course_template  (6 rows)
DELETE FROM `ld_course_template`;
INSERT INTO `ld_course_template` (`id`, `title`, `description`, `category`, `created_by`, `structure_json`, `module_count`, `lesson_count`, `quiz_count`, `created_at`, `updated_at`) VALUES
('1', 'Programming Course Template', 'Auto test', 'General', '99967', '{\"modules\":[{\"id\":2,\"title\":\"Introduction to Programming\",\"description\":\"\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":2,\"title\":\"What is Programming?\",\"content_type\":\"text\",\"content_body\":null,\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":3,\"title\":\"Programming Basics Quiz\",\"duration_seconds\":600,\"passing_score\":\"75.00\",\"max_attempts\":2,\"question_count\":10,\"show_answers_after_submit\":0,\"status\":\"active\",\"questions\":[{\"id\":3,\"question_text\":\"What does CPU stand for?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Central Processing Unit\",\"is_correct\":0,\"order_index\":1},{\"option_text\":\"Computer Processing Unit\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', '1', '1', '1', '2026-08-20 04:42:37', '2026-08-20 04:42:37'),
('2', 'hakdohfga', 'Master modern JavaScript including ES6+, async/await, closures, and functional programming.', 'Programming', '35', '{\"modules\":[{\"id\":6,\"title\":\"Module 1: Fundamentals\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":12,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":13,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":14,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":7,\"title\":\"Quiz: Module 1\",\"duration_seconds\":300,\"passing_score\":\"71.00\",\"max_attempts\":3,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":22,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":23,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":24,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]},{\"id\":25,\"question_text\":\"How does this technique improve performance?\",\"question_type\":\"single_choice\",\"order_index\":4,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":26,\"question_text\":\"What is the correct sequence of steps?\",\"question_type\":\"single_choice\",\"order_index\":5,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]}]}]},{\"id\":7,\"title\":\"Module 2: Core Concepts\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":2,\"status\":\"active\",\"lessons\":[{\"id\":15,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":16,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":17,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":8,\"title\":\"Quiz: Module 2\",\"duration_seconds\":180,\"passing_score\":\"75.00\",\"max_attempts\":3,\"question_count\":3,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":27,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":28,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":29,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', '2', '6', '2', '2026-08-26 16:57:27', '2026-08-26 16:57:27'),
('3', 'haikdog', 'Master modern JavaScript including ES6+, async/await, closures, and functional programming.', 'Programming', '35', '{\"modules\":[{\"id\":6,\"title\":\"Module 1: Fundamentals\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":12,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":13,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":14,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":7,\"title\":\"Quiz: Module 1\",\"duration_seconds\":300,\"passing_score\":\"71.00\",\"max_attempts\":3,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":22,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":23,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":24,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]},{\"id\":25,\"question_text\":\"How does this technique improve performance?\",\"question_type\":\"single_choice\",\"order_index\":4,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":26,\"question_text\":\"What is the correct sequence of steps?\",\"question_type\":\"single_choice\",\"order_index\":5,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]}]}]},{\"id\":7,\"title\":\"Module 2: Core Concepts\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":2,\"status\":\"active\",\"lessons\":[{\"id\":15,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":16,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":17,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":8,\"title\":\"Quiz: Module 2\",\"duration_seconds\":180,\"passing_score\":\"75.00\",\"max_attempts\":3,\"question_count\":3,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":27,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":28,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":29,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', '2', '6', '2', '2026-08-26 19:00:27', '2026-08-26 19:00:27'),
('4', 'Onboarding Essentials Template', 'A ready-made one-module template for new-hire orientation courses.', 'Onboarding', '1001', '{\"modules\":[{\"title\":\"Welcome and Orientation\",\"description\":\"Company overview, culture, and expectations.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"Welcome to the Company\",\"content_type\":\"text\",\"content_body\":\"<p>An introduction to our mission and values.</p>\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[]}]}', '1', '1', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('5', 'Compliance Training Template', 'A reusable template for mandatory compliance and policy training.', 'Compliance & Safety', '1001', '{\"modules\":[{\"title\":\"Policies and Procedures\",\"description\":\"Key policies every employee must follow.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"Understanding Company Policies\",\"content_type\":\"text\",\"content_body\":\"<p>Review the policies that apply to your role.</p>\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"title\":\"Policy Knowledge Check\",\"duration_seconds\":600,\"passing_score\":\"80.00\",\"max_attempts\":2,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[]}]}]}', '1', '1', '1', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', 'Leadership Workshop Template', 'A starter template for leadership and management workshops.', 'Leadership', '1001', '{\"modules\":[{\"title\":\"Leading People\",\"description\":\"Core people-leadership concepts.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"What Makes a Great Leader\",\"content_type\":\"video\",\"video_url\":\"https://www.youtube.com/embed/1Y9d3lR6Qm4\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[]}]}', '1', '1', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_course_version
DELETE FROM `ld_course_version`;

-- ld_cron_run  (2 rows)
DELETE FROM `ld_cron_run`;
INSERT INTO `ld_cron_run` (`id`, `job`, `trigger_type`, `status`, `started_at`, `finished_at`, `duration_ms`, `records_processed`, `message`, `actor_id`) VALUES
('5', 'engagement_snapshot', 'scheduled', 'success', '2026-09-25 21:44:14', '2026-09-25 21:44:14', '98', '11', 'Upserted engagement snapshots for 11 learners on 2026-09-25', '35'),
('6', 'engagement_snapshot', 'manual', 'success', '2026-09-25 21:56:37', '2026-09-25 21:56:38', '79', '11', 'Upserted engagement snapshots for 11 learners on 2026-09-25', '35');

-- ld_display_preference
DELETE FROM `ld_display_preference`;

-- ld_display_preference_store
DELETE FROM `ld_display_preference_store`;

-- ld_engagement_snapshot  (21 rows)
DELETE FROM `ld_engagement_snapshot`;
INSERT INTO `ld_engagement_snapshot` (`id`, `learner_id`, `snapshot_date`, `active_days_30`, `courses_in_progress`, `courses_completed_ytd`, `avg_score`, `quizzes_started`, `quizzes_abandoned`, `days_since_last_activity`, `expired_certificates`, `risk_score`, `risk_factors`, `created_at`) VALUES
('1', '1', '2026-09-05', '1', '0', '0', NULL, '0', '0', '5', '0', '0.00', '[]', '2026-09-05 04:27:26'),
('2', '2', '2026-09-05', '1', '0', '0', NULL, '0', '0', '5', '0', '0.00', '[]', '2026-09-05 04:27:26'),
('3', '33', '2026-09-05', '1', '0', '3', NULL, '0', '0', '0', '0', '0.00', '[]', '2026-09-05 04:27:26'),
('4', '34', '2026-09-05', '1', '5', '3', NULL, '0', '0', '0', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-05 04:27:26'),
('5', '35', '2026-09-05', '2', '1', '0', NULL, '2', '0', '7', '0', '0.00', '[]', '2026-09-05 04:27:26'),
('6', '36', '2026-09-05', '1', '5', '2', NULL, '0', '0', '12', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-05 04:27:26'),
('7', '37', '2026-09-05', '1', '4', '3', NULL, '0', '0', '2', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-05 04:27:26'),
('8', '38', '2026-09-05', '1', '5', '2', NULL, '0', '0', '13', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-05 04:27:26'),
('9', '39', '2026-09-05', '1', '2', '2', NULL, '0', '0', '0', '0', '0.00', '[]', '2026-09-05 04:27:26'),
('10', '67999', '2026-09-05', '7', '15', '3', NULL, '3', '0', '6', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-05 04:27:26'),
('21', '1', '2026-09-25', '1', '0', '0', NULL, '0', '0', '26', '0', '0.00', '[]', '2026-09-25 05:30:13'),
('22', '2', '2026-09-25', '1', '0', '0', NULL, '0', '0', '26', '0', '0.00', '[]', '2026-09-25 05:30:13'),
('23', '13', '2026-09-25', '1', '3', '0', NULL, '0', '0', '18', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-25 05:30:13'),
('24', '33', '2026-09-25', '0', '0', '3', NULL, '0', '0', '19', '0', '0.00', '[]', '2026-09-25 05:30:13'),
('25', '34', '2026-09-25', '0', '5', '3', NULL, '0', '0', '5', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-25 05:30:13'),
('26', '35', '2026-09-25', '1', '1', '0', NULL, '2', '0', '28', '0', '0.00', '[]', '2026-09-25 05:30:13'),
('27', '36', '2026-09-25', '0', '5', '2', NULL, '0', '0', '32', '0', '45.00', '[\"inactive_30_days\",\"many_courses_in_progress\"]', '2026-09-25 05:30:13'),
('28', '37', '2026-09-25', '0', '4', '3', NULL, '0', '0', '22', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-25 05:30:13'),
('29', '38', '2026-09-25', '0', '5', '2', NULL, '0', '0', '33', '0', '45.00', '[\"inactive_30_days\",\"many_courses_in_progress\"]', '2026-09-25 05:30:13'),
('30', '39', '2026-09-25', '0', '2', '2', NULL, '0', '0', '7', '0', '0.00', '[]', '2026-09-25 05:30:13'),
('31', '67999', '2026-09-25', '3', '16', '3', NULL, '6', '0', '18', '0', '15.00', '[\"many_courses_in_progress\"]', '2026-09-25 05:30:13');

-- ld_enrollment  (4 rows)
DELETE FROM `ld_enrollment`;
INSERT INTO `ld_enrollment` (`id`, `learner_id`, `course_id`, `course_version_id`, `status`, `invited_by`, `enrolled_at`, `completed_at`, `last_accessed_at`, `created_at`) VALUES
('1', '1006', '5', NULL, 'completed', NULL, '2026-10-09 00:14:38', '2026-10-09 01:15:10', '2026-10-09 01:15:10', '2026-10-09 00:14:38'),
('2', '1006', '6', NULL, 'completed', NULL, '2026-10-09 10:58:40', '2026-10-09 10:59:05', '2026-10-15 22:58:06', '2026-10-09 10:58:40'),
('4', '1006', '12', NULL, 'enrolled', NULL, '2026-10-15 21:46:15', NULL, '2026-10-15 23:31:37', '2026-10-15 21:46:15'),
('5', '1006', '8', NULL, 'completed', NULL, '2026-10-15 22:23:45', '2026-10-15 23:49:25', '2026-10-15 23:49:25', '2026-10-15 22:23:45');

-- ld_evaluation  (8 rows)
DELETE FROM `ld_evaluation`;
INSERT INTO `ld_evaluation` (`id`, `course_id`, `title`, `duration_seconds`, `passing_score`, `max_attempts`, `question_count`, `show_answers_after_submit`, `status`, `created_at`, `updated_at`) VALUES
('2', '5', 'Final Assessment', '1800', '85.00', '2', '1', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('3', '6', 'Final Assessment', '1800', '85.00', '2', '2', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('4', '7', 'Final Assessment: Workplace Safety and Compliance Essentials', '1800', '85.00', '2', '10', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('5', '8', 'Final Assessment: Effective Business Communication', '1800', '80.00', '2', '10', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', '9', 'Final Assessment: Data Analysis with Microsoft Excel', '1800', '80.00', '2', '12', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('7', '10', 'Final Assessment: Leadership Foundations for New Managers', '1800', '80.00', '2', '10', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('8', '11', 'Final Assessment: Customer Service Excellence', '1800', '80.00', '2', '10', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('9', '12', 'Final Assessment: Cybersecurity Awareness for Employees', '1800', '85.00', '2', '12', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_evaluation_feedback
DELETE FROM `ld_evaluation_feedback`;

-- ld_favorite
DELETE FROM `ld_favorite`;

-- ld_grade  (2 rows)
DELETE FROM `ld_grade`;
INSERT INTO `ld_grade` (`id`, `learner_id`, `course_id`, `final_score`, `status`, `issued_at`) VALUES
('0', '1006', '5', '0.00', 'failed', '2026-10-09 01:15:10'),
('1', '1006', '8', '80.00', 'passed', '2026-10-15 23:49:25');

-- ld_instructor  (1 row)
DELETE FROM `ld_instructor`;
INSERT INTO `ld_instructor` (`employee_id`, `designated_by`, `note`, `designated_at`) VALUES
('99967', NULL, 'Seeded L&D instructor test account', '2026-09-26 05:31:21');

-- ld_integration_event
DELETE FROM `ld_integration_event`;

-- ld_integration_log
DELETE FROM `ld_integration_log`;

-- ld_learning_path  (7 rows)
DELETE FROM `ld_learning_path`;
INSERT INTO `ld_learning_path` (`id`, `instructor_id`, `title`, `description`, `assigned_to`, `status`, `created_at`, `updated_at`, `type`, `is_public`, `kt_plan_id`) VALUES
('3', '1007', 'Knowledge Transfer — Russel Gohetia Cabrido', 'Knowledge transfer path for Russel Gohetia Cabrido. Contains 0 completed or in-progress courses.', NULL, 'active', '2026-10-08 14:44:17', '2026-10-08 14:44:17', 'knowledge_transfer', '0', '18'),
('4', '1007', 'Knowledge Transfer — Jayson Paigma', 'Knowledge transfer path for Jayson Paigma. Contains 1 completed or in-progress courses.', NULL, 'active', '2026-10-09 02:53:21', '2026-10-17 11:48:40', 'knowledge_transfer', '0', '19'),
('5', '1007', 'qwceqecqw', 'qwcecqe', NULL, 'active', '2026-10-09 11:19:56', '2026-10-09 12:04:33', 'standard', '1', NULL),
('6', '1001', 'New Hire Onboarding Journey', 'The essential first-30-days path for every new employee: safety, communication, and security fundamentals.', NULL, 'active', '2026-10-15 21:24:54', '2026-10-16 00:42:20', 'standard', '1', NULL),
('7', '1001', 'Aspiring Team Lead Track', 'A guided path for individual contributors who want to step into team leadership.', NULL, 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54', 'standard', '1', NULL),
('8', '1001', 'Data and Analytics Career Path', 'Build the spreadsheet and communication skills needed to grow into a data-focused role.', NULL, 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54', 'standard', '1', NULL),
('9', '1001', 'Workplace Safety Certification Path', 'Complete the required safety training and assessment to earn the internal safety certification.', NULL, 'active', '2026-10-15 21:24:54', '2026-10-16 00:37:47', 'standard', '1', NULL);

-- ld_learning_path_invitation
DELETE FROM `ld_learning_path_invitation`;

-- ld_learning_path_item  (15 rows)
DELETE FROM `ld_learning_path_item`;
INSERT INTO `ld_learning_path_item` (`id`, `learning_path_id`, `item_type`, `reference_id`, `order_index`, `status`) VALUES
('1', '4', 'course', '5', '1', 'active'),
('4', '5', 'course', '6', '0', 'active'),
('5', '5', 'module', '3', '0', 'active'),
('6', '7', 'program', '4', '3', 'active'),
('7', '9', 'evaluation', '4', '3', 'active'),
('8', '9', 'quiz', '6', '2', 'active'),
('9', '8', 'module', '10', '2', 'active'),
('10', '7', 'module', '12', '2', 'active'),
('11', '6', 'course', '7', '1', 'active'),
('12', '9', 'course', '7', '1', 'active'),
('13', '6', 'course', '8', '2', 'active'),
('14', '8', 'course', '8', '3', 'active'),
('15', '8', 'course', '9', '1', 'active'),
('16', '7', 'course', '10', '1', 'active'),
('17', '6', 'course', '12', '3', 'active');

-- ld_learning_path_skill  (6 rows)
DELETE FROM `ld_learning_path_skill`;
INSERT INTO `ld_learning_path_skill` (`id`, `learning_path_id`, `skill_id`) VALUES
('3', '5', '13'),
('4', '5', '15'),
('7', '6', '16'),
('5', '7', '2'),
('6', '8', '5'),
('8', '9', '16');

-- ld_lesson  (26 rows)
DELETE FROM `ld_lesson`;
INSERT INTO `ld_lesson` (`id`, `module_id`, `title`, `content_type`, `content_body`, `video_url`, `order_index`, `status`, `created_at`, `updated_at`) VALUES
('1', '3', 'Basic Queries', 'text', 'Hello World', 'https://www.youtube.com/watch?v=eFIMruzx_Rs&list=RDeFIMruzx_Rs', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('2', '4', 'ecqweqwc', 'text', '<p>ecqw<strong>eqwcq</strong>w</p><p><br></p><p><em>6trdnydrtn</em></p><p><br></p><blockquote><em>56n756n75</em></blockquote><blockquote><br></blockquote><blockquote><em class=\"ql-font-monospace\">789,</em></blockquote><blockquote><br></blockquote><h1><em>yugomyfyum tfnuf</em></h1>', 'https://www.youtube.com/watch?v=rHbJXpSXVeU&list=RDrHbJXpSXVeU', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:28:31'),
('3', '5', 'Why Workplace Safety Matters', 'text', '<h3>Introduction</h3><p>A safe workplace protects people, productivity, and reputation. This lesson explains the business and human reasons behind workplace safety.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('4', '5', 'Roles, Rights, and Responsibilities', 'text', '<h3>Everyone Has a Role</h3><p>Understand employee rights, employer duties, and how to report concerns through the correct channels.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('5', '6', 'Spotting Common Workplace Hazards', 'text', '<h3>Hazard Awareness</h3><p>Learn how to identify physical, ergonomic, chemical, and psychosocial hazards before they cause harm.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', '6', 'The Hierarchy of Risk Controls', 'text', '<h3>Controlling Risk</h3><p>Elimination, substitution, engineering controls, administrative controls, and PPE - applied in the right order.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('7', '7', 'The Communication Process', 'text', '<h3>Sender, Message, Receiver</h3><p>Explore the communication loop and the barriers that cause misunderstandings at work.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('8', '7', 'Active Listening Techniques', 'video', NULL, 'https://www.youtube.com/embed/1Y9d3lR6Qm4', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('9', '8', 'Writing Clear Emails and Reports', 'text', '<h3>Write to Be Understood</h3><p>Structure business messages with a clear purpose, audience, and call to action.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('10', '8', 'Delivering Confident Presentations', 'video', NULL, 'https://www.youtube.com/embed/AykYRO5d_lI', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('11', '9', 'Navigating Excel and Key Functions', 'text', '<h3>Excel Basics</h3><p>Work with cells, ranges, formulas, and lookup functions that form the backbone of analysis.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('12', '9', 'Cleaning and Organizing Data', 'text', '<h3>From Messy to Tidy</h3><p>Remove duplicates, standardize values, and structure data for reliable reporting.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('13', '10', 'Building Charts and Pivot Tables', 'video', NULL, 'https://www.youtube.com/embed/xbLcfBGU5Qc', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('14', '10', 'Presenting Insights from Data', 'text', '<h3>Telling the Story</h3><p>Choose the right chart and highlight the insight that drives a decision.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('15', '11', 'From Team Member to Team Lead', 'text', '<h3>A New Perspective</h3><p>Shift from doing the work to enabling the team to do the work.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('16', '11', 'Setting Expectations and Goals', 'text', '<h3>Clarity Drives Performance</h3><p>Define outcomes, agree on measures, and align the team around shared goals.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('17', '12', 'Giving Constructive Feedback', 'video', NULL, 'https://www.youtube.com/embed/6z4g7w2KqEo', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('18', '12', 'Running One-on-One Meetings', 'text', '<h3>Make Time Count</h3><p>A repeatable agenda for check-ins that develop people and surface issues early.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('19', '13', 'Understanding Customer Expectations', 'text', '<h3>What Customers Want</h3><p>Responsiveness, reliability, empathy, and a resolution - every time.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('20', '13', 'Building Rapport and Empathy', 'text', '<h3>Connect First</h3><p>Simple techniques to make customers feel heard and valued.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('21', '14', 'De-escalation Techniques', 'video', NULL, 'https://www.youtube.com/embed/1EvUCfXWLoU', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('22', '14', 'Resolving Complaints Effectively', 'text', '<h3>From Complaint to Resolution</h3><p>A structured approach to acknowledge, investigate, and resolve complaints.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('23', '15', 'Common Cyber Threats Explained', 'text', '<h3>Know the Threats</h3><p>Malware, ransomware, social engineering, and insider risk explained in plain language.</p>', NULL, '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('24', '15', 'Protecting Company Data', 'text', '<h3>Data Handling</h3><p>Classify, store, share, and dispose of information safely.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('25', '16', 'Recognizing Phishing Attempts', 'video', NULL, 'https://www.youtube.com/embed/XBkzBrXlle0', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('26', '16', 'Strong Passwords and Multi-Factor Authentication', 'text', '<h3>Lock the Door</h3><p>Create strong passphrases and enable MFA on every account.</p>', NULL, '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_lesson_file
DELETE FROM `ld_lesson_file`;

-- ld_message
DELETE FROM `ld_message`;

-- ld_module  (14 rows)
DELETE FROM `ld_module`;
INSERT INTO `ld_module` (`id`, `course_id`, `title`, `description`, `order_index`, `status`, `created_at`, `updated_at`) VALUES
('3', '5', 'Introduction to SQL', 'Lorem ipiosum', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('4', '6', 'cghffg', 'wecqwecqw', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('5', '7', 'Safety Fundamentals and Responsibilities', 'Why safety matters and what every employee is responsible for.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', '7', 'Hazard Identification and Risk Control', 'Spot hazards early and apply the hierarchy of controls to reduce risk.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('7', '8', 'Communication Essentials', 'The communication process, listening, and building rapport at work.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('8', '8', 'Professional Writing and Presentations', 'Write clear emails and reports and deliver confident presentations.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('9', '9', 'Excel Foundations and Data Cleaning', 'Navigate Excel and prepare messy data for analysis.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('10', '9', 'Data Visualization and Reporting', 'Build charts, pivot tables, and reports that communicate insight.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('11', '10', 'Transitioning into Leadership', 'Move from individual contributor to trusted team leader.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('12', '10', 'Coaching and Performance Conversations', 'Give feedback and run effective one-on-one meetings.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('13', '11', 'Customer Service Mindset', 'Understand customer expectations and build empathy and rapport.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('14', '11', 'Handling Difficult Customer Situations', 'De-escalate tension and resolve complaints effectively.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('15', '12', 'Cybersecurity Essentials', 'Understand common threats and how to protect company data.', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('16', '12', 'Phishing, Passwords, and Safe Browsing', 'Recognize phishing and apply safe password and browsing habits.', '2', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_module_skill  (5 rows)
DELETE FROM `ld_module_skill`;
INSERT INTO `ld_module_skill` (`id`, `module_id`, `skill_id`) VALUES
('1', '6', '16'),
('2', '7', '3'),
('3', '9', '14'),
('4', '10', '5'),
('5', '12', '2');

-- ld_note
DELETE FROM `ld_note`;

-- ld_notification
DELETE FROM `ld_notification`;

-- ld_notification_inbox
DELETE FROM `ld_notification_inbox`;

-- ld_notification_preference
DELETE FROM `ld_notification_preference`;

-- ld_notification_preference_store
DELETE FROM `ld_notification_preference_store`;

-- ld_prerequisite
DELETE FROM `ld_prerequisite`;

-- ld_program  (6 rows)
DELETE FROM `ld_program`;
INSERT INTO `ld_program` (`id`, `instructor_id`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES
('1', '1007', 'xtatcae', 'tacratctevt', 'active', '2026-10-09 02:28:55', '2026-10-09 02:28:55'),
('2', '1007', 'dsadccccsad', 'ascda', 'active', '2026-10-09 02:46:48', '2026-10-09 02:46:48'),
('3', '1007', 'dsadccccsad', 'ascda', 'active', '2026-10-09 02:46:49', '2026-10-09 02:46:49'),
('4', '1001', 'Management Development Program', 'A structured program that prepares high-potential employees for people-leadership roles through courses, workshops, and coaching.', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('5', '1001', 'Graduate Trainee Program', 'An onboarding and development program that rotates new graduates through core business functions.', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', '1001', 'Customer Experience Excellence Program', 'A blended program that builds consistent customer service standards across customer-facing teams.', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_program_skill  (4 rows)
DELETE FROM `ld_program_skill`;
INSERT INTO `ld_program_skill` (`id`, `program_id`, `skill_id`) VALUES
('1', '2', '16'),
('2', '3', '16'),
('3', '4', '2'),
('4', '6', '8');

-- ld_progress  (12 rows)
DELETE FROM `ld_progress`;
INSERT INTO `ld_progress` (`id`, `enrollment_id`, `item_type`, `reference_id`, `status`, `completed_at`) VALUES
('4', '2', 'lesson', '2', 'completed', '2026-10-09 10:58:49'),
('5', '2', 'quiz', '4', 'completed', '2026-10-15 22:23:18'),
('8', '4', 'lesson', '23', 'completed', '2026-10-15 21:46:44'),
('9', '4', 'lesson', '24', 'completed', '2026-10-15 21:46:50'),
('11', '5', 'lesson', '7', 'completed', '2026-10-15 23:04:23'),
('12', '5', 'lesson', '8', 'completed', '2026-10-15 23:04:29'),
('13', '5', 'quiz', '7', 'completed', '2026-10-15 23:04:45'),
('14', '5', 'lesson', '9', 'completed', '2026-10-15 23:04:56'),
('15', '4', 'quiz', '15', 'completed', '2026-10-15 23:05:07'),
('16', '4', 'quiz', '16', 'completed', '2026-10-15 23:11:28'),
('18', '5', 'lesson', '10', 'completed', '2026-10-15 23:49:02'),
('19', '5', 'quiz', '8', 'completed', '2026-10-15 23:49:25');

-- ld_quiz  (14 rows)
DELETE FROM `ld_quiz`;
INSERT INTO `ld_quiz` (`id`, `module_id`, `title`, `duration_seconds`, `passing_score`, `max_attempts`, `question_count`, `show_answers_after_submit`, `status`, `created_at`, `updated_at`) VALUES
('3', '3', 'Quiz 1', '600', '80.00', '2', '2', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('4', '4', 'asdsdsa', '600', '76.00', '2', '3', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('5', '5', 'Quiz: Safety Fundamentals and Responsibilities', '600', '80.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('6', '6', 'Quiz: Hazard Identification and Risk Control', '600', '80.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('7', '7', 'Quiz: Communication Essentials', '600', '75.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('8', '8', 'Quiz: Professional Writing and Presentations', '600', '75.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('9', '9', 'Quiz: Excel Foundations and Data Cleaning', '600', '80.00', '2', '6', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('10', '10', 'Quiz: Data Visualization and Reporting', '600', '80.00', '2', '6', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('11', '11', 'Quiz: Transitioning into Leadership', '600', '75.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('12', '12', 'Quiz: Coaching and Performance Conversations', '600', '75.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('13', '13', 'Quiz: Customer Service Mindset', '600', '80.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('14', '14', 'Quiz: Handling Difficult Customer Situations', '600', '80.00', '2', '5', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('15', '15', 'Quiz: Cybersecurity Essentials', '600', '85.00', '2', '6', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('16', '16', 'Quiz: Phishing, Passwords, and Safe Browsing', '600', '85.00', '2', '6', '1', 'active', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

-- ld_quiz_attempt  (6 rows)
DELETE FROM `ld_quiz_attempt`;
INSERT INTO `ld_quiz_attempt` (`id`, `learner_id`, `quiz_id`, `quiz_session_id`, `score`, `total_items`, `passed`, `attempted_at`) VALUES
('3', '1006', '4', '2', '66.67', '3', '0', '2026-10-09 10:59:05'),
('6', '1006', '4', '3', '0.00', '3', '0', '2026-10-15 22:23:18'),
('8', '1006', '7', '8', '80.00', '5', '1', '2026-10-15 23:04:45'),
('9', '1006', '15', '7', '60.00', '5', '0', '2026-10-15 23:05:07'),
('10', '1006', '16', '9', '80.00', '5', '0', '2026-10-15 23:11:28'),
('11', '1006', '8', '10', '80.00', '5', '1', '2026-10-15 23:49:25');

-- ld_quiz_question  (131 rows)
DELETE FROM `ld_quiz_question`;
INSERT INTO `ld_quiz_question` (`id`, `item_type`, `reference_id`, `question_text`, `question_type`, `order_index`, `status`, `created_at`, `updated_at`) VALUES
('2', 'quiz', '3', 'Hakdog', 'single_choice', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('3', 'evaluation', '2', 'dwa', 'single_choice', '0', 'active', '2026-10-08 17:45:36', '2026-10-08 17:45:36'),
('4', 'quiz', '4', 'sdsad', 'single_choice', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('5', 'quiz', '4', 'sadsadsadw', 'single_choice', '1', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('6', 'quiz', '4', 'afvtav', 'single_choice', '2', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('7', 'evaluation', '3', 'dwasadasd', 'single_choice', '0', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('8', 'evaluation', '3', 'eadsadasd', 'true_false', '1', 'active', '2026-10-09 02:27:42', '2026-10-09 02:27:42'),
('9', 'quiz', '5', 'Sample question 1 for Quiz: Safety Fundamentals and Responsibilities', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('10', 'quiz', '5', 'Sample question 2 for Quiz: Safety Fundamentals and Responsibilities', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('11', 'quiz', '5', 'Sample question 3 for Quiz: Safety Fundamentals and Responsibilities', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('12', 'quiz', '5', 'Sample question 4 for Quiz: Safety Fundamentals and Responsibilities', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('13', 'quiz', '5', 'Sample question 5 for Quiz: Safety Fundamentals and Responsibilities', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('14', 'quiz', '6', 'Sample question 1 for Quiz: Hazard Identification and Risk Control', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('15', 'quiz', '6', 'Sample question 2 for Quiz: Hazard Identification and Risk Control', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('16', 'quiz', '6', 'Sample question 3 for Quiz: Hazard Identification and Risk Control', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('17', 'quiz', '6', 'Sample question 4 for Quiz: Hazard Identification and Risk Control', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('18', 'quiz', '6', 'Sample question 5 for Quiz: Hazard Identification and Risk Control', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('19', 'quiz', '7', 'Sample question 1 for Quiz: Communication Essentials', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('20', 'quiz', '7', 'Sample question 2 for Quiz: Communication Essentials', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('21', 'quiz', '7', 'Sample question 3 for Quiz: Communication Essentials', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('22', 'quiz', '7', 'Sample question 4 for Quiz: Communication Essentials', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('23', 'quiz', '7', 'Sample question 5 for Quiz: Communication Essentials', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('24', 'quiz', '8', 'Sample question 1 for Quiz: Professional Writing and Presentations', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('25', 'quiz', '8', 'Sample question 2 for Quiz: Professional Writing and Presentations', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('26', 'quiz', '8', 'Sample question 3 for Quiz: Professional Writing and Presentations', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('27', 'quiz', '8', 'Sample question 4 for Quiz: Professional Writing and Presentations', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('28', 'quiz', '8', 'Sample question 5 for Quiz: Professional Writing and Presentations', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('29', 'quiz', '9', 'Sample question 1 for Quiz: Excel Foundations and Data Cleaning', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('30', 'quiz', '9', 'Sample question 2 for Quiz: Excel Foundations and Data Cleaning', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('31', 'quiz', '9', 'Sample question 3 for Quiz: Excel Foundations and Data Cleaning', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('32', 'quiz', '9', 'Sample question 4 for Quiz: Excel Foundations and Data Cleaning', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('33', 'quiz', '9', 'Sample question 5 for Quiz: Excel Foundations and Data Cleaning', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('34', 'quiz', '10', 'Sample question 1 for Quiz: Data Visualization and Reporting', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('35', 'quiz', '10', 'Sample question 2 for Quiz: Data Visualization and Reporting', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('36', 'quiz', '10', 'Sample question 3 for Quiz: Data Visualization and Reporting', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('37', 'quiz', '10', 'Sample question 4 for Quiz: Data Visualization and Reporting', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('38', 'quiz', '10', 'Sample question 5 for Quiz: Data Visualization and Reporting', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('39', 'quiz', '11', 'Sample question 1 for Quiz: Transitioning into Leadership', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('40', 'quiz', '11', 'Sample question 2 for Quiz: Transitioning into Leadership', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('41', 'quiz', '11', 'Sample question 3 for Quiz: Transitioning into Leadership', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('42', 'quiz', '11', 'Sample question 4 for Quiz: Transitioning into Leadership', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('43', 'quiz', '11', 'Sample question 5 for Quiz: Transitioning into Leadership', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('44', 'quiz', '12', 'Sample question 1 for Quiz: Coaching and Performance Conversations', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('45', 'quiz', '12', 'Sample question 2 for Quiz: Coaching and Performance Conversations', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('46', 'quiz', '12', 'Sample question 3 for Quiz: Coaching and Performance Conversations', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('47', 'quiz', '12', 'Sample question 4 for Quiz: Coaching and Performance Conversations', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('48', 'quiz', '12', 'Sample question 5 for Quiz: Coaching and Performance Conversations', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('49', 'quiz', '13', 'Sample question 1 for Quiz: Customer Service Mindset', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('50', 'quiz', '13', 'Sample question 2 for Quiz: Customer Service Mindset', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('51', 'quiz', '13', 'Sample question 3 for Quiz: Customer Service Mindset', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('52', 'quiz', '13', 'Sample question 4 for Quiz: Customer Service Mindset', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('53', 'quiz', '13', 'Sample question 5 for Quiz: Customer Service Mindset', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('54', 'quiz', '14', 'Sample question 1 for Quiz: Handling Difficult Customer Situations', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('55', 'quiz', '14', 'Sample question 2 for Quiz: Handling Difficult Customer Situations', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('56', 'quiz', '14', 'Sample question 3 for Quiz: Handling Difficult Customer Situations', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('57', 'quiz', '14', 'Sample question 4 for Quiz: Handling Difficult Customer Situations', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('58', 'quiz', '14', 'Sample question 5 for Quiz: Handling Difficult Customer Situations', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('59', 'quiz', '15', 'Sample question 1 for Quiz: Cybersecurity Essentials', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('60', 'quiz', '15', 'Sample question 2 for Quiz: Cybersecurity Essentials', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('61', 'quiz', '15', 'Sample question 3 for Quiz: Cybersecurity Essentials', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('62', 'quiz', '15', 'Sample question 4 for Quiz: Cybersecurity Essentials', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('63', 'quiz', '15', 'Sample question 5 for Quiz: Cybersecurity Essentials', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('64', 'quiz', '16', 'Sample question 1 for Quiz: Phishing, Passwords, and Safe Browsing', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('65', 'quiz', '16', 'Sample question 2 for Quiz: Phishing, Passwords, and Safe Browsing', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('66', 'quiz', '16', 'Sample question 3 for Quiz: Phishing, Passwords, and Safe Browsing', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('67', 'quiz', '16', 'Sample question 4 for Quiz: Phishing, Passwords, and Safe Browsing', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('68', 'quiz', '16', 'Sample question 5 for Quiz: Phishing, Passwords, and Safe Browsing', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('69', 'evaluation', '4', 'Sample question 1 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('70', 'evaluation', '4', 'Sample question 2 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('71', 'evaluation', '4', 'Sample question 3 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('72', 'evaluation', '4', 'Sample question 4 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('73', 'evaluation', '4', 'Sample question 5 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('74', 'evaluation', '4', 'Sample question 6 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('75', 'evaluation', '4', 'Sample question 7 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('76', 'evaluation', '4', 'Sample question 8 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('77', 'evaluation', '4', 'Sample question 9 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('78', 'evaluation', '4', 'Sample question 10 for Final Assessment: Workplace Safety and Compliance Essentials', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('79', 'evaluation', '5', 'Sample question 1 for Final Assessment: Effective Business Communication', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('80', 'evaluation', '5', 'Sample question 2 for Final Assessment: Effective Business Communication', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('81', 'evaluation', '5', 'Sample question 3 for Final Assessment: Effective Business Communication', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('82', 'evaluation', '5', 'Sample question 4 for Final Assessment: Effective Business Communication', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('83', 'evaluation', '5', 'Sample question 5 for Final Assessment: Effective Business Communication', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('84', 'evaluation', '5', 'Sample question 6 for Final Assessment: Effective Business Communication', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('85', 'evaluation', '5', 'Sample question 7 for Final Assessment: Effective Business Communication', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('86', 'evaluation', '5', 'Sample question 8 for Final Assessment: Effective Business Communication', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('87', 'evaluation', '5', 'Sample question 9 for Final Assessment: Effective Business Communication', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('88', 'evaluation', '5', 'Sample question 10 for Final Assessment: Effective Business Communication', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('89', 'evaluation', '6', 'Sample question 1 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('90', 'evaluation', '6', 'Sample question 2 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('91', 'evaluation', '6', 'Sample question 3 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('92', 'evaluation', '6', 'Sample question 4 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('93', 'evaluation', '6', 'Sample question 5 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('94', 'evaluation', '6', 'Sample question 6 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('95', 'evaluation', '6', 'Sample question 7 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('96', 'evaluation', '6', 'Sample question 8 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('97', 'evaluation', '6', 'Sample question 9 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('98', 'evaluation', '6', 'Sample question 10 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('99', 'evaluation', '6', 'Sample question 11 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '11', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('100', 'evaluation', '6', 'Sample question 12 for Final Assessment: Data Analysis with Microsoft Excel', 'true_false', '12', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('101', 'evaluation', '7', 'Sample question 1 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('102', 'evaluation', '7', 'Sample question 2 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('103', 'evaluation', '7', 'Sample question 3 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('104', 'evaluation', '7', 'Sample question 4 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('105', 'evaluation', '7', 'Sample question 5 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('106', 'evaluation', '7', 'Sample question 6 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('107', 'evaluation', '7', 'Sample question 7 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('108', 'evaluation', '7', 'Sample question 8 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('109', 'evaluation', '7', 'Sample question 9 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('110', 'evaluation', '7', 'Sample question 10 for Final Assessment: Leadership Foundations for New Managers', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('111', 'evaluation', '8', 'Sample question 1 for Final Assessment: Customer Service Excellence', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('112', 'evaluation', '8', 'Sample question 2 for Final Assessment: Customer Service Excellence', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('113', 'evaluation', '8', 'Sample question 3 for Final Assessment: Customer Service Excellence', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('114', 'evaluation', '8', 'Sample question 4 for Final Assessment: Customer Service Excellence', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('115', 'evaluation', '8', 'Sample question 5 for Final Assessment: Customer Service Excellence', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('116', 'evaluation', '8', 'Sample question 6 for Final Assessment: Customer Service Excellence', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('117', 'evaluation', '8', 'Sample question 7 for Final Assessment: Customer Service Excellence', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('118', 'evaluation', '8', 'Sample question 8 for Final Assessment: Customer Service Excellence', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('119', 'evaluation', '8', 'Sample question 9 for Final Assessment: Customer Service Excellence', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('120', 'evaluation', '8', 'Sample question 10 for Final Assessment: Customer Service Excellence', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('121', 'evaluation', '9', 'Sample question 1 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '1', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('122', 'evaluation', '9', 'Sample question 2 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '2', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('123', 'evaluation', '9', 'Sample question 3 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '3', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('124', 'evaluation', '9', 'Sample question 4 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '4', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('125', 'evaluation', '9', 'Sample question 5 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '5', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('126', 'evaluation', '9', 'Sample question 6 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '6', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('127', 'evaluation', '9', 'Sample question 7 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '7', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('128', 'evaluation', '9', 'Sample question 8 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '8', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('129', 'evaluation', '9', 'Sample question 9 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '9', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('130', 'evaluation', '9', 'Sample question 10 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '10', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('131', 'evaluation', '9', 'Sample question 11 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '11', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41'),
('132', 'evaluation', '9', 'Sample question 12 for Final Assessment: Cybersecurity Awareness for Employees', 'true_false', '12', 'active', '2026-10-15 22:50:41', '2026-10-15 22:50:41');

-- ld_quiz_question_option  (266 rows)
DELETE FROM `ld_quiz_question_option`;
INSERT INTO `ld_quiz_question_option` (`id`, `question_id`, `option_text`, `is_correct`, `order_index`) VALUES
('3', '2', 'tama', '1', '0'),
('4', '2', 'mali', '0', '1'),
('5', '2', 'mali', '0', '2'),
('6', '2', 'mali', '0', '3'),
('7', '3', 'tama', '1', '0'),
('8', '3', 'mali', '0', '1'),
('9', '4', 'tama', '1', '0'),
('10', '4', 'mali', '0', '1'),
('11', '4', 'mali', '0', '2'),
('12', '5', 'mali', '0', '0'),
('13', '5', 'tama', '1', '1'),
('14', '5', 'mali', '0', '2'),
('15', '6', 'mali', '0', '0'),
('16', '6', 'tama', '1', '1'),
('17', '7', 'tama', '1', '0'),
('18', '7', 'mali', '0', '1'),
('19', '8', 'tama', '1', '0'),
('20', '8', 'mali', '0', '1'),
('21', '9', 'tama', '1', '1'),
('22', '9', 'mali', '0', '2'),
('23', '10', 'tama', '1', '1'),
('24', '10', 'mali', '0', '2'),
('25', '11', 'tama', '1', '1'),
('26', '11', 'mali', '0', '2'),
('27', '12', 'tama', '1', '1'),
('28', '12', 'mali', '0', '2'),
('29', '13', 'tama', '1', '1'),
('30', '13', 'mali', '0', '2'),
('31', '14', 'tama', '1', '1'),
('32', '14', 'mali', '0', '2'),
('33', '15', 'tama', '1', '1'),
('34', '15', 'mali', '0', '2'),
('35', '16', 'tama', '1', '1'),
('36', '16', 'mali', '0', '2'),
('37', '17', 'tama', '1', '1'),
('38', '17', 'mali', '0', '2'),
('39', '18', 'tama', '1', '1'),
('40', '18', 'mali', '0', '2'),
('41', '19', 'tama', '1', '1'),
('42', '19', 'mali', '0', '2'),
('43', '20', 'tama', '1', '1'),
('44', '20', 'mali', '0', '2'),
('45', '21', 'tama', '1', '1'),
('46', '21', 'mali', '0', '2'),
('47', '22', 'tama', '1', '1'),
('48', '22', 'mali', '0', '2'),
('49', '23', 'tama', '1', '1'),
('50', '23', 'mali', '0', '2'),
('51', '24', 'tama', '1', '1'),
('52', '24', 'mali', '0', '2'),
('53', '25', 'tama', '1', '1'),
('54', '25', 'mali', '0', '2'),
('55', '26', 'tama', '1', '1'),
('56', '26', 'mali', '0', '2'),
('57', '27', 'tama', '1', '1'),
('58', '27', 'mali', '0', '2'),
('59', '28', 'tama', '1', '1'),
('60', '28', 'mali', '0', '2'),
('61', '29', 'tama', '1', '1'),
('62', '29', 'mali', '0', '2'),
('63', '30', 'tama', '1', '1'),
('64', '30', 'mali', '0', '2'),
('65', '31', 'tama', '1', '1'),
('66', '31', 'mali', '0', '2'),
('67', '32', 'tama', '1', '1'),
('68', '32', 'mali', '0', '2'),
('69', '33', 'tama', '1', '1'),
('70', '33', 'mali', '0', '2'),
('71', '34', 'tama', '1', '1'),
('72', '34', 'mali', '0', '2'),
('73', '35', 'tama', '1', '1'),
('74', '35', 'mali', '0', '2'),
('75', '36', 'tama', '1', '1'),
('76', '36', 'mali', '0', '2'),
('77', '37', 'tama', '1', '1'),
('78', '37', 'mali', '0', '2'),
('79', '38', 'tama', '1', '1'),
('80', '38', 'mali', '0', '2'),
('81', '39', 'tama', '1', '1'),
('82', '39', 'mali', '0', '2'),
('83', '40', 'tama', '1', '1'),
('84', '40', 'mali', '0', '2'),
('85', '41', 'tama', '1', '1'),
('86', '41', 'mali', '0', '2'),
('87', '42', 'tama', '1', '1'),
('88', '42', 'mali', '0', '2'),
('89', '43', 'tama', '1', '1'),
('90', '43', 'mali', '0', '2'),
('91', '44', 'tama', '1', '1'),
('92', '44', 'mali', '0', '2'),
('93', '45', 'tama', '1', '1'),
('94', '45', 'mali', '0', '2'),
('95', '46', 'tama', '1', '1'),
('96', '46', 'mali', '0', '2'),
('97', '47', 'tama', '1', '1'),
('98', '47', 'mali', '0', '2'),
('99', '48', 'tama', '1', '1'),
('100', '48', 'mali', '0', '2'),
('101', '49', 'tama', '1', '1'),
('102', '49', 'mali', '0', '2'),
('103', '50', 'tama', '1', '1'),
('104', '50', 'mali', '0', '2'),
('105', '51', 'tama', '1', '1'),
('106', '51', 'mali', '0', '2'),
('107', '52', 'tama', '1', '1'),
('108', '52', 'mali', '0', '2'),
('109', '53', 'tama', '1', '1'),
('110', '53', 'mali', '0', '2'),
('111', '54', 'tama', '1', '1'),
('112', '54', 'mali', '0', '2'),
('113', '55', 'tama', '1', '1'),
('114', '55', 'mali', '0', '2'),
('115', '56', 'tama', '1', '1'),
('116', '56', 'mali', '0', '2'),
('117', '57', 'tama', '1', '1'),
('118', '57', 'mali', '0', '2'),
('119', '58', 'tama', '1', '1'),
('120', '58', 'mali', '0', '2'),
('121', '59', 'tama', '1', '1'),
('122', '59', 'mali', '0', '2'),
('123', '60', 'tama', '1', '1'),
('124', '60', 'mali', '0', '2'),
('125', '61', 'tama', '1', '1'),
('126', '61', 'mali', '0', '2'),
('127', '62', 'tama', '1', '1'),
('128', '62', 'mali', '0', '2'),
('129', '63', 'tama', '1', '1'),
('130', '63', 'mali', '0', '2'),
('131', '64', 'tama', '1', '1'),
('132', '64', 'mali', '0', '2'),
('133', '65', 'tama', '1', '1'),
('134', '65', 'mali', '0', '2'),
('135', '66', 'tama', '1', '1'),
('136', '66', 'mali', '0', '2'),
('137', '67', 'tama', '1', '1'),
('138', '67', 'mali', '0', '2'),
('139', '68', 'tama', '1', '1'),
('140', '68', 'mali', '0', '2'),
('141', '69', 'tama', '1', '1'),
('142', '69', 'mali', '0', '2'),
('143', '70', 'tama', '1', '1'),
('144', '70', 'mali', '0', '2'),
('145', '71', 'tama', '1', '1'),
('146', '71', 'mali', '0', '2'),
('147', '72', 'tama', '1', '1'),
('148', '72', 'mali', '0', '2'),
('149', '73', 'tama', '1', '1'),
('150', '73', 'mali', '0', '2'),
('151', '74', 'tama', '1', '1'),
('152', '74', 'mali', '0', '2'),
('153', '75', 'tama', '1', '1'),
('154', '75', 'mali', '0', '2'),
('155', '76', 'tama', '1', '1'),
('156', '76', 'mali', '0', '2'),
('157', '77', 'tama', '1', '1'),
('158', '77', 'mali', '0', '2'),
('159', '78', 'tama', '1', '1'),
('160', '78', 'mali', '0', '2'),
('161', '79', 'tama', '1', '1'),
('162', '79', 'mali', '0', '2'),
('163', '80', 'tama', '1', '1'),
('164', '80', 'mali', '0', '2'),
('165', '81', 'tama', '1', '1'),
('166', '81', 'mali', '0', '2'),
('167', '82', 'tama', '1', '1'),
('168', '82', 'mali', '0', '2'),
('169', '83', 'tama', '1', '1'),
('170', '83', 'mali', '0', '2'),
('171', '84', 'tama', '1', '1'),
('172', '84', 'mali', '0', '2'),
('173', '85', 'tama', '1', '1'),
('174', '85', 'mali', '0', '2'),
('175', '86', 'tama', '1', '1'),
('176', '86', 'mali', '0', '2'),
('177', '87', 'tama', '1', '1'),
('178', '87', 'mali', '0', '2'),
('179', '88', 'tama', '1', '1'),
('180', '88', 'mali', '0', '2'),
('181', '89', 'tama', '1', '1'),
('182', '89', 'mali', '0', '2'),
('183', '90', 'tama', '1', '1'),
('184', '90', 'mali', '0', '2'),
('185', '91', 'tama', '1', '1'),
('186', '91', 'mali', '0', '2'),
('187', '92', 'tama', '1', '1'),
('188', '92', 'mali', '0', '2'),
('189', '93', 'tama', '1', '1'),
('190', '93', 'mali', '0', '2'),
('191', '94', 'tama', '1', '1'),
('192', '94', 'mali', '0', '2'),
('193', '95', 'tama', '1', '1'),
('194', '95', 'mali', '0', '2'),
('195', '96', 'tama', '1', '1'),
('196', '96', 'mali', '0', '2'),
('197', '97', 'tama', '1', '1'),
('198', '97', 'mali', '0', '2'),
('199', '98', 'tama', '1', '1'),
('200', '98', 'mali', '0', '2'),
('201', '99', 'tama', '1', '1'),
('202', '99', 'mali', '0', '2'),
('203', '100', 'tama', '1', '1'),
('204', '100', 'mali', '0', '2'),
('205', '101', 'tama', '1', '1'),
('206', '101', 'mali', '0', '2'),
('207', '102', 'tama', '1', '1'),
('208', '102', 'mali', '0', '2'),
('209', '103', 'tama', '1', '1'),
('210', '103', 'mali', '0', '2'),
('211', '104', 'tama', '1', '1'),
('212', '104', 'mali', '0', '2'),
('213', '105', 'tama', '1', '1'),
('214', '105', 'mali', '0', '2'),
('215', '106', 'tama', '1', '1'),
('216', '106', 'mali', '0', '2'),
('217', '107', 'tama', '1', '1'),
('218', '107', 'mali', '0', '2'),
('219', '108', 'tama', '1', '1'),
('220', '108', 'mali', '0', '2'),
('221', '109', 'tama', '1', '1'),
('222', '109', 'mali', '0', '2'),
('223', '110', 'tama', '1', '1'),
('224', '110', 'mali', '0', '2'),
('225', '111', 'tama', '1', '1'),
('226', '111', 'mali', '0', '2'),
('227', '112', 'tama', '1', '1'),
('228', '112', 'mali', '0', '2'),
('229', '113', 'tama', '1', '1'),
('230', '113', 'mali', '0', '2'),
('231', '114', 'tama', '1', '1'),
('232', '114', 'mali', '0', '2'),
('233', '115', 'tama', '1', '1'),
('234', '115', 'mali', '0', '2'),
('235', '116', 'tama', '1', '1'),
('236', '116', 'mali', '0', '2'),
('237', '117', 'tama', '1', '1'),
('238', '117', 'mali', '0', '2'),
('239', '118', 'tama', '1', '1'),
('240', '118', 'mali', '0', '2'),
('241', '119', 'tama', '1', '1'),
('242', '119', 'mali', '0', '2'),
('243', '120', 'tama', '1', '1'),
('244', '120', 'mali', '0', '2'),
('245', '121', 'tama', '1', '1'),
('246', '121', 'mali', '0', '2'),
('247', '122', 'tama', '1', '1'),
('248', '122', 'mali', '0', '2'),
('249', '123', 'tama', '1', '1'),
('250', '123', 'mali', '0', '2'),
('251', '124', 'tama', '1', '1'),
('252', '124', 'mali', '0', '2'),
('253', '125', 'tama', '1', '1'),
('254', '125', 'mali', '0', '2'),
('255', '126', 'tama', '1', '1'),
('256', '126', 'mali', '0', '2'),
('257', '127', 'tama', '1', '1'),
('258', '127', 'mali', '0', '2'),
('259', '128', 'tama', '1', '1'),
('260', '128', 'mali', '0', '2'),
('261', '129', 'tama', '1', '1'),
('262', '129', 'mali', '0', '2'),
('263', '130', 'tama', '1', '1'),
('264', '130', 'mali', '0', '2'),
('265', '131', 'tama', '1', '1'),
('266', '131', 'mali', '0', '2'),
('267', '132', 'tama', '1', '1'),
('268', '132', 'mali', '0', '2');

-- ld_quiz_session  (7 rows)
DELETE FROM `ld_quiz_session`;
INSERT INTO `ld_quiz_session` (`id`, `learner_id`, `item_type`, `reference_id`, `started_at`, `duration_seconds`, `submitted_at`, `status`, `question_order`, `score`, `passed`) VALUES
('1', '1006', 'quiz', '3', '2026-10-09 01:04:47', NULL, NULL, 'in_progress', '[{\"question_id\":2,\"option_order\":[3,5,6,4]}]', NULL, NULL),
('2', '1006', 'quiz', '4', '2026-10-09 10:58:59', '600', '2026-10-09 10:59:05', 'submitted', '[{\"question_id\":6,\"option_order\":[16,15]},{\"question_id\":4,\"option_order\":[10,11,9]},{\"question_id\":5,\"option_order\":[12,14,13]}]', '66.67', '0'),
('3', '1006', 'quiz', '4', '2026-10-09 11:10:10', '600', '2026-10-15 22:23:18', 'expired', '[{\"question_id\":6,\"option_order\":[16,15]},{\"question_id\":5,\"option_order\":[14,12,13]},{\"question_id\":4,\"option_order\":[9,11,10]}]', '0.00', '0'),
('7', '1006', 'quiz', '15', '2026-10-15 23:04:03', '600', '2026-10-15 23:05:07', 'submitted', '[{\"question_id\":60,\"option_order\":[123,124]},{\"question_id\":61,\"option_order\":[125,126]},{\"question_id\":59,\"option_order\":[122,121]},{\"question_id\":63,\"option_order\":[130,129]},{\"question_id\":62,\"option_order\":[128,127]}]', '60.00', '0'),
('8', '1006', 'quiz', '7', '2026-10-15 23:04:33', '600', '2026-10-15 23:04:45', 'submitted', '[{\"question_id\":22,\"option_order\":[47,48]},{\"question_id\":23,\"option_order\":[49,50]},{\"question_id\":21,\"option_order\":[46,45]},{\"question_id\":19,\"option_order\":[41,42]},{\"question_id\":20,\"option_order\":[44,43]}]', '80.00', '1'),
('9', '1006', 'quiz', '16', '2026-10-15 23:11:18', '600', '2026-10-15 23:11:28', 'submitted', '[{\"question_id\":66,\"option_order\":[135,136]},{\"question_id\":68,\"option_order\":[140,139]},{\"question_id\":67,\"option_order\":[138,137]},{\"question_id\":65,\"option_order\":[134,133]},{\"question_id\":64,\"option_order\":[131,132]}]', '80.00', '0'),
('10', '1006', 'quiz', '8', '2026-10-15 23:49:05', '600', '2026-10-15 23:49:25', 'submitted', '[{\"question_id\":24,\"option_order\":[51,52]},{\"question_id\":28,\"option_order\":[59,60]},{\"question_id\":25,\"option_order\":[53,54]},{\"question_id\":27,\"option_order\":[57,58]},{\"question_id\":26,\"option_order\":[55,56]}]', '80.00', '1');

-- ld_quiz_session_answer  (26 rows)
DELETE FROM `ld_quiz_session_answer`;
INSERT INTO `ld_quiz_session_answer` (`id`, `quiz_session_id`, `question_id`, `selected_option_id`, `is_marked_for_review`, `answered_at`) VALUES
('2', '2', '6', '16', '0', '2026-10-09 10:59:00'),
('3', '2', '4', '9', '0', '2026-10-09 10:59:01'),
('4', '2', '5', '14', '0', '2026-10-09 10:59:03'),
('5', '3', '6', NULL, '0', NULL),
('6', '3', '5', NULL, '0', NULL),
('7', '3', '4', NULL, '0', NULL),
('17', '7', '60', NULL, '0', NULL),
('18', '7', '61', '125', '0', '2026-10-15 23:05:01'),
('19', '7', '59', '121', '0', '2026-10-15 23:05:02'),
('20', '7', '63', '130', '0', '2026-10-15 23:05:04'),
('21', '7', '62', '127', '0', '2026-10-15 23:05:05'),
('22', '8', '22', '47', '0', '2026-10-15 23:04:34'),
('23', '8', '23', '49', '0', '2026-10-15 23:04:35'),
('24', '8', '21', '46', '0', '2026-10-15 23:04:36'),
('25', '8', '19', '41', '0', '2026-10-15 23:04:39'),
('26', '8', '20', '43', '0', '2026-10-15 23:04:41'),
('27', '9', '66', '135', '0', '2026-10-15 23:11:19'),
('28', '9', '68', '139', '0', '2026-10-15 23:11:21'),
('29', '9', '67', '137', '0', '2026-10-15 23:11:23'),
('30', '9', '65', '133', '0', '2026-10-15 23:11:24'),
('31', '9', '64', '132', '0', '2026-10-15 23:11:25'),
('32', '10', '24', '51', '0', '2026-10-15 23:49:07'),
('33', '10', '28', '59', '0', '2026-10-15 23:49:21'),
('34', '10', '25', '54', '0', '2026-10-15 23:49:09'),
('35', '10', '27', '57', '0', '2026-10-15 23:49:10'),
('36', '10', '26', '55', '0', '2026-10-15 23:49:14');

-- ld_rating
DELETE FROM `ld_rating`;

-- ld_recognition_course_map  (9 rows)
DELETE FROM `ld_recognition_course_map`;
INSERT INTO `ld_recognition_course_map` (`id`, `recognition_category`, `course_id`, `created_at`, `updated_at`) VALUES
('1', 'performance', '26', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('2', 'innovation', '21', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('3', 'teamwork', '11', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('4', 'achievement', '12', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('5', 'general', '27', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('6', 'Employee of the Month', '17', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('7', 'Innovation Award', '18', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('8', 'Customer Excellence', '19', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('9', 'Team Player', '17', '2026-08-30 04:49:29', '2026-08-30 04:49:29');

-- ld_recognition_unlock
DELETE FROM `ld_recognition_unlock`;

-- ld_recommendation_course_map  (15 rows)
DELETE FROM `ld_recommendation_course_map`;
INSERT INTO `ld_recommendation_course_map` (`id`, `development_area`, `course_id`, `created_at`, `updated_at`) VALUES
('1', 'Leadership', '26', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('2', 'Technical', '3', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('3', 'Programming', '3', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('4', 'Communication', '11', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('5', 'Project Management', '12', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('6', 'Design', '15', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('7', 'Marketing', '18', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('8', 'Data Analysis', '20', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('9', 'Cybersecurity', '23', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('10', 'Time Management', '27', '2026-08-30 04:49:21', '2026-08-30 04:49:21'),
('11', 'Leadership', '17', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('12', 'Technical Skills', '18', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('13', 'Communication', '19', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('14', 'Team Management', '17', '2026-08-30 04:49:29', '2026-08-30 04:49:29'),
('15', 'Project Management', '18', '2026-08-30 04:49:29', '2026-08-30 04:49:29');

-- ld_report
DELETE FROM `ld_report`;

-- ld_reporting_requirement  (3 rows)
DELETE FROM `ld_reporting_requirement`;
INSERT INTO `ld_reporting_requirement` (`id`, `source_module`, `target_name`, `reporting_period_start`, `reporting_period_end`, `reporting_frequency`, `completion_target`, `expected_output`, `notes`, `status`, `created_by`, `created_at`) VALUES
('1', 'workforce', 'Q4 compliance training', '2026-10-01', '2026-12-31', 'monthly', '85.00', 'Completion report by department and course', 'Sent from the Workforce custom reports page', 'received', NULL, '2026-09-25 05:29:35'),
('2', 'workforce', 'certification expiry', '2026-09-01', '2026-11-30', 'monthly', '90.00', 'certification expiry report', NULL, 'received', NULL, '2026-09-25 05:29:35'),
('3', 'workforce', 'training participation', '2026-09-01', '2026-12-31', 'monthly', '95.00', 'learner participation list', 'Written by the Workforce module directly', 'received', '4', '2026-09-25 05:35:23');

-- ld_request  (1 row)
DELETE FROM `ld_request`;
INSERT INTO `ld_request` (`id`, `learner_id`, `requested_title`, `description`, `status`, `created_at`) VALUES
('0', '1006', 'hakdog', 'sdsad', 'pending', '2026-10-09 11:16:44');

-- ld_setting  (10 rows)
DELETE FROM `ld_setting`;
INSERT INTO `ld_setting` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
('1', 'report_auto_archive_days', '7', '2026-10-16 13:45:48'),
('2', 'default_quiz_duration_seconds', '600', '2026-10-16 13:45:48'),
('3', 'default_max_quiz_attempts', '2', '2026-10-16 13:45:48'),
('4', 'file_upload_max_mb', '25', '2026-10-16 13:45:48'),
('5', 'certificate_default_validity_days', '0', '2026-10-16 13:45:48'),
('6', 'site_timezone', 'Asia/Manila', '2026-08-15 16:21:00'),
('7', 'enrollment_invitation_expiry_days', '7', '2026-08-15 16:21:00'),
('8', 'video_conference_reminder_first_minutes', '30', '2026-08-15 16:21:00'),
('9', 'video_conference_reminder_second_minutes', '15', '2026-08-15 16:21:00'),
('10', 'default_passing_score', '70', '2026-10-16 13:45:48');

-- ld_skill  (16 rows)
DELETE FROM `ld_skill`;
INSERT INTO `ld_skill` (`id`, `name`, `description`, `date_updated`, `suggested`, `status`, `created_at`) VALUES
('1', 'Project Management', 'Plan, coordinate, and deliver projects using practical methods and tools.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('2', 'Leadership', 'Guide teams, make decisions, resolve conflict, and support performance.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('3', 'Communication Skills', 'Build clear written, verbal, presentation, and interpersonal communication.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('4', 'Technical Writing', 'Create clear technical documentation, guides, procedures, and knowledge articles.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('5', 'Data Analysis', 'Collect, interpret, and present data for evidence-based decisions.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('6', 'Cybersecurity', 'Recognize threats and apply security practices to systems, networks, and data.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('7', 'Web Development', 'Build and maintain responsive web applications and services.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('8', 'Customer Service', 'Support customers through active listening, empathy, and effective resolution.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('9', 'Critical Thinking', 'Evaluate evidence, identify assumptions, and solve problems systematically.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('10', 'Teamwork', 'Collaborate effectively, share responsibility, and contribute to common goals.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('11', 'Time Management', 'Prioritize work, organize schedules, and meet deadlines consistently.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('12', 'Problem Solving', 'Analyze challenges and develop practical, measurable solutions.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('13', 'Digital Literacy', 'Use workplace software, online tools, and digital information effectively.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('14', 'Microsoft Excel', 'Organize data and use formulas, tables, and reports in Microsoft Excel.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('15', 'Human Resources', 'Apply core HR practices in employee relations, staffing, and administration.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43'),
('16', 'Compliance and Safety', 'Follow workplace policies, regulatory requirements, and safety procedures.', '2026-10-08 16:52:43', '0', 'active', '2026-10-08 16:52:43');

-- ld_skill_snapshot  (12 rows)
DELETE FROM `ld_skill_snapshot`;
INSERT INTO `ld_skill_snapshot` (`id`, `learner_id`, `position_id`, `department_id`, `total_skills`, `acquired_count`, `gap_count`, `acquired_skills`, `gap_skills`, `snapshot_date`, `created_at`) VALUES
('1', '67999', NULL, NULL, '36', '20', '16', '[\"Project Management\",\"Leadership\",\"Technical Writing\",\"Data Analysis\",\"Web Development\",\"Customer Service\",\"Time Management\",\"Financial Literacy\",\"Teamwork & Collaboration\",\"Communication Skills\",\"Cybersecurity\",\"Critical Thinking\",\"Adaptability\",\"Quality Assurance\",\"Human Resources Management\",\"Strategic Planning\",\"Inventory Management\",\"Compliance & Ethics\",\"Communication\",\"Critical Thinking\"]', '[\"Adaptability\",\"Cloud Computing\",\"Cybersecurity\",\"Data Analysis\",\"Financial Literacy\",\"JavaScript\",\"Leadership\",\"Machine Learning\",\"Presentation Skills\",\"Problem Solving\",\"Project Management\",\"Python\",\"SQL\",\"Teamwork\",\"Time Management\",\"UX Design\"]', '2026-08-31', '2026-08-31 06:31:22'),
('8', '67999', NULL, NULL, '36', '20', '16', '[\"Project Management\",\"Leadership\",\"Technical Writing\",\"Data Analysis\",\"Web Development\",\"Customer Service\",\"Time Management\",\"Financial Literacy\",\"Teamwork & Collaboration\",\"Communication Skills\",\"Cybersecurity\",\"Critical Thinking\",\"Adaptability\",\"Quality Assurance\",\"Human Resources Management\",\"Strategic Planning\",\"Inventory Management\",\"Compliance & Ethics\",\"Communication\",\"Critical Thinking\"]', '[\"Adaptability\",\"Cloud Computing\",\"Cybersecurity\",\"Data Analysis\",\"Financial Literacy\",\"JavaScript\",\"Leadership\",\"Machine Learning\",\"Presentation Skills\",\"Problem Solving\",\"Project Management\",\"Python\",\"SQL\",\"Teamwork\",\"Time Management\",\"UX Design\"]', '2026-09-02', '2026-09-02 09:50:13'),
('11', '1', '9', '3', '4', '0', '4', '[]', '[\"Teamwork & Collaboration\",\"Critical Thinking\",\"Problem Solving\",\"Cloud Computing\"]', '2026-09-05', '2026-09-05 04:27:25'),
('12', '2', '2', '3', '3', '0', '3', '[]', '[\"Teamwork & Collaboration\",\"Critical Thinking\",\"Problem Solving\"]', '2026-09-05', '2026-09-05 04:27:25'),
('13', '33', '4', '1', '7', '7', '0', '[\"Critical Thinking\",\"Teamwork & Collaboration\",\"Critical Thinking\",\"Problem Solving\",\"Communication Skills\",\"SQL\",\"Communication\"]', '[]', '2026-09-05', '2026-09-05 04:27:25'),
('14', '34', '4', '1', '18', '7', '11', '[\"Technical Writing\",\"Financial Literacy\",\"Leadership\",\"Cybersecurity\",\"Python\",\"Web Development\",\"Quality Assurance\"]', '[\"Time Management\",\"Critical Thinking\",\"Communication Skills\",\"SQL\",\"Communication\",\"Critical Thinking\",\"JavaScript\",\"Presentation Skills\",\"Project Management\",\"Compliance & Ethics\",\"Adaptability\"]', '2026-09-05', '2026-09-05 04:27:26'),
('15', '35', '4', '1', '3', '0', '3', '[]', '[\"Project Management\",\"Compliance & Ethics\",\"Adaptability\"]', '2026-09-05', '2026-09-05 04:27:26'),
('16', '36', '4', '1', '10', '4', '6', '[\"Technical Writing\",\"Financial Literacy\",\"Communication\",\"Critical Thinking\"]', '[\"Teamwork & Collaboration\",\"Inventory Management\",\"Critical Thinking\",\"Cloud Computing\",\"Adaptability\",\"Project Management\"]', '2026-09-05', '2026-09-05 04:27:26'),
('17', '37', '4', '1', '13', '7', '6', '[\"Financial Literacy\",\"Teamwork & Collaboration\",\"Inventory Management\",\"Cybersecurity\",\"Web Development\",\"Quality Assurance\",\"Project Management\"]', '[\"Leadership\",\"UX Design\",\"Technical Writing\",\"Communication Skills\",\"SQL\",\"Communication\"]', '2026-09-05', '2026-09-05 04:27:26'),
('18', '38', '4', '1', '9', '3', '6', '[\"UX Design\",\"Leadership\",\"Teamwork & Collaboration\"]', '[\"Financial Literacy\",\"Inventory Management\",\"Critical Thinking\",\"Technical Writing\",\"Customer Service\",\"Project Management\"]', '2026-09-05', '2026-09-05 04:27:26'),
('19', '39', '4', '1', '10', '6', '4', '[\"Time Management\",\"Communication\",\"Financial Literacy\",\"Leadership\",\"Time Management\",\"Problem Solving\"]', '[\"Inventory Management\",\"Critical Thinking\",\"Adaptability\",\"JavaScript\"]', '2026-09-05', '2026-09-05 04:27:26'),
('20', '67999', '31', '7', '32', '20', '12', '[\"Project Management\",\"Leadership\",\"Technical Writing\",\"Data Analysis\",\"Web Development\",\"Customer Service\",\"Time Management\",\"Financial Literacy\",\"Teamwork & Collaboration\",\"Communication Skills\",\"Cybersecurity\",\"Critical Thinking\",\"Adaptability\",\"Quality Assurance\",\"Human Resources Management\",\"Strategic Planning\",\"Inventory Management\",\"Compliance & Ethics\",\"Communication\",\"Critical Thinking\"]', '[\"Machine Learning\",\"Adaptability\",\"Leadership\",\"Financial Literacy\",\"Problem Solving\",\"Project Management\",\"Data Analysis\",\"JavaScript\",\"Presentation Skills\",\"SQL\",\"Time Management\",\"Cloud Computing\"]', '2026-09-05', '2026-09-05 04:27:26');

-- ld_study_time_log  (588 rows)
DELETE FROM `ld_study_time_log`;
INSERT INTO `ld_study_time_log` (`id`, `learner_id`, `enrollment_id`, `session_token`, `sequence_no`, `active_seconds`, `recorded_at`) VALUES
('1', '1006', '4', 'df256f59-6e83-4e50-9be4-046332ebf4c1', '1', '16', '2026-10-15 21:46:42'),
('2', '1006', '4', '1a50cb76-1490-4060-bb5d-e6d0ed0a6135', '1', '5', '2026-10-15 21:46:48'),
('3', '1006', '4', '00a867b5-84fa-44cf-abf7-edf990fb2104', '1', '14', '2026-10-15 21:47:06'),
('4', '1006', '4', '079b5932-36bb-40e1-8a15-1a7a6a2eb4a3', '1', '2', '2026-10-15 21:47:08'),
('5', '1006', '4', 'df644907-5f4a-4905-a718-b6d15812a6a5', '1', '1', '2026-10-15 21:47:16'),
('6', '1006', '4', '4e1094e0-58fe-4666-afb0-8113bca3c0bf', '1', '6', '2026-10-15 21:49:21'),
('7', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '1', '30', '2026-10-15 21:49:51'),
('8', '1006', '4', '6ead7cf5-5b5b-4724-b02b-21fe641917f4', '1', '23', '2026-10-15 21:49:59'),
('9', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '2', '30', '2026-10-15 21:50:21'),
('10', '1006', '4', '6ead7cf5-5b5b-4724-b02b-21fe641917f4', '2', '30', '2026-10-15 21:50:29'),
('11', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '3', '29', '2026-10-15 21:50:51'),
('12', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '1', '30', '2026-10-15 21:51:00'),
('13', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '4', '30', '2026-10-15 21:51:21'),
('14', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '2', '30', '2026-10-15 21:51:30'),
('15', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '5', '30', '2026-10-15 21:51:51'),
('16', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '3', '29', '2026-10-15 21:52:00'),
('17', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '6', '29', '2026-10-15 21:52:21'),
('18', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '4', '30', '2026-10-15 21:52:30'),
('19', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '7', '30', '2026-10-15 21:52:51'),
('20', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '5', '30', '2026-10-15 21:53:00'),
('21', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '8', '30', '2026-10-15 21:53:21'),
('22', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '6', '29', '2026-10-15 21:53:30'),
('23', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '9', '29', '2026-10-15 21:53:51'),
('24', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '7', '29', '2026-10-15 21:54:00'),
('25', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '10', '30', '2026-10-15 21:54:21'),
('26', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '8', '30', '2026-10-15 21:54:30'),
('27', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '11', '29', '2026-10-15 21:54:51'),
('28', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '9', '29', '2026-10-15 21:55:00'),
('29', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '12', '29', '2026-10-15 21:55:21'),
('30', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '10', '30', '2026-10-15 21:55:30'),
('31', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '13', '30', '2026-10-15 21:55:51'),
('32', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '11', '30', '2026-10-15 21:56:00'),
('33', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '14', '30', '2026-10-15 21:56:21'),
('34', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '12', '29', '2026-10-15 21:56:30'),
('35', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '15', '30', '2026-10-15 21:56:51'),
('36', '1006', '4', 'ed9db08b-aa90-4f75-b51d-40d182c99c75', '1', '30', '2026-10-15 21:56:56'),
('37', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '13', '30', '2026-10-15 21:57:00'),
('38', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '16', '29', '2026-10-15 21:57:21'),
('39', '1006', '4', 'ed9db08b-aa90-4f75-b51d-40d182c99c75', '2', '30', '2026-10-15 21:57:26'),
('40', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '14', '29', '2026-10-15 21:57:30'),
('41', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '17', '29', '2026-10-15 21:57:51'),
('42', '1006', '4', 'ed9db08b-aa90-4f75-b51d-40d182c99c75', '3', '29', '2026-10-15 21:57:56'),
('43', '1006', '4', 'ed9db08b-aa90-4f75-b51d-40d182c99c75', '4', '3', '2026-10-15 21:57:59'),
('44', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '15', '29', '2026-10-15 21:58:00'),
('45', '1006', '4', '0d84b1fb-f81c-412f-84f8-1073f3652891', '1', '5', '2026-10-15 21:58:05'),
('46', '1006', '4', '435b4347-1175-4767-bcd2-790356adf5b3', '1', '12', '2026-10-15 21:58:18'),
('47', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '18', '30', '2026-10-15 21:58:21'),
('48', '1006', '4', 'ce4c53d3-1fdd-46f3-8566-4f239581c2e5', '1', '8', '2026-10-15 21:58:28'),
('49', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '16', '29', '2026-10-15 21:58:30'),
('50', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '19', '30', '2026-10-15 21:58:51'),
('51', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '17', '30', '2026-10-15 21:59:00'),
('52', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '20', '30', '2026-10-15 21:59:21'),
('53', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '18', '29', '2026-10-15 21:59:30'),
('54', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '21', '29', '2026-10-15 21:59:51'),
('55', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '19', '30', '2026-10-15 22:00:00'),
('56', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '22', '30', '2026-10-15 22:00:21'),
('57', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '20', '30', '2026-10-15 22:00:30'),
('58', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '23', '30', '2026-10-15 22:00:51'),
('59', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '21', '29', '2026-10-15 22:01:00'),
('60', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '24', '29', '2026-10-15 22:01:21'),
('61', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '22', '30', '2026-10-15 22:01:30'),
('62', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '25', '30', '2026-10-15 22:01:51'),
('63', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '23', '30', '2026-10-15 22:02:00'),
('64', '1006', '4', '0d79c2d1-8664-421a-8bbf-9fc48f9fdb8c', '1', '6', '2026-10-15 22:02:15'),
('65', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '26', '30', '2026-10-15 22:02:21'),
('66', '1006', '4', 'ec3529fc-e6c2-4c1e-b355-e86f78ec2685', '1', '7', '2026-10-15 22:02:23'),
('67', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '24', '29', '2026-10-15 22:02:30'),
('68', '1006', '4', '4eb6c057-cc29-4ac6-b30e-fa96f43a360c', '1', '7', '2026-10-15 22:02:31'),
('69', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '27', '30', '2026-10-15 22:02:51'),
('70', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '25', '29', '2026-10-15 22:03:00'),
('71', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '28', '29', '2026-10-15 22:03:21'),
('72', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '26', '30', '2026-10-15 22:03:30'),
('73', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '29', '29', '2026-10-15 22:03:51'),
('74', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '27', '30', '2026-10-15 22:04:00'),
('75', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '30', '30', '2026-10-15 22:04:21'),
('76', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '28', '29', '2026-10-15 22:04:30'),
('77', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '31', '30', '2026-10-15 22:04:51'),
('78', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '29', '30', '2026-10-15 22:05:00'),
('79', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '32', '30', '2026-10-15 22:05:21'),
('80', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '30', '29', '2026-10-15 22:05:30'),
('81', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '33', '29', '2026-10-15 22:05:51'),
('82', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '31', '30', '2026-10-15 22:06:00'),
('83', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '34', '30', '2026-10-15 22:06:21'),
('84', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '32', '30', '2026-10-15 22:06:30'),
('85', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '35', '29', '2026-10-15 22:06:51'),
('86', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '33', '29', '2026-10-15 22:07:00'),
('87', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '36', '30', '2026-10-15 22:07:21'),
('88', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '34', '30', '2026-10-15 22:07:30'),
('89', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '37', '29', '2026-10-15 22:07:51'),
('90', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '35', '30', '2026-10-15 22:08:00'),
('91', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '38', '30', '2026-10-15 22:08:21'),
('92', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '36', '29', '2026-10-15 22:08:30'),
('93', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '39', '29', '2026-10-15 22:08:51'),
('94', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '37', '30', '2026-10-15 22:09:00'),
('95', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '40', '30', '2026-10-15 22:09:21'),
('96', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '38', '29', '2026-10-15 22:09:30'),
('97', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '41', '30', '2026-10-15 22:09:51'),
('98', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '39', '30', '2026-10-15 22:10:00'),
('99', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '42', '29', '2026-10-15 22:10:21'),
('100', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '40', '29', '2026-10-15 22:10:30'),
('101', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '43', '30', '2026-10-15 22:10:51'),
('102', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '41', '30', '2026-10-15 22:11:00'),
('103', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '44', '30', '2026-10-15 22:11:22'),
('104', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '42', '30', '2026-10-15 22:11:30'),
('105', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '45', '29', '2026-10-15 22:11:51'),
('106', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '43', '29', '2026-10-15 22:12:00'),
('107', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '46', '30', '2026-10-15 22:12:21'),
('108', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '44', '30', '2026-10-15 22:12:30'),
('109', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '47', '29', '2026-10-15 22:12:51'),
('110', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '45', '29', '2026-10-15 22:13:00'),
('111', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '48', '30', '2026-10-15 22:13:21'),
('112', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '46', '30', '2026-10-15 22:13:30'),
('113', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '49', '30', '2026-10-15 22:13:51'),
('114', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '47', '29', '2026-10-15 22:14:00'),
('115', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '50', '30', '2026-10-15 22:14:21'),
('116', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '48', '29', '2026-10-15 22:14:30'),
('117', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '51', '29', '2026-10-15 22:14:51'),
('118', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '49', '30', '2026-10-15 22:15:00'),
('119', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '52', '29', '2026-10-15 22:15:21'),
('120', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '50', '29', '2026-10-15 22:15:30'),
('121', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '53', '30', '2026-10-15 22:15:51'),
('122', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '51', '29', '2026-10-15 22:16:00'),
('123', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '54', '30', '2026-10-15 22:16:21'),
('124', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '52', '30', '2026-10-15 22:16:30'),
('125', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '55', '30', '2026-10-15 22:16:51'),
('126', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '53', '29', '2026-10-15 22:17:00'),
('127', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '56', '30', '2026-10-15 22:17:21'),
('128', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '54', '29', '2026-10-15 22:17:30'),
('129', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '57', '29', '2026-10-15 22:17:51'),
('130', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '55', '30', '2026-10-15 22:18:00'),
('131', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '58', '30', '2026-10-15 22:18:21'),
('132', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '56', '29', '2026-10-15 22:18:30'),
('133', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '59', '30', '2026-10-15 22:18:51'),
('134', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '57', '30', '2026-10-15 22:19:00'),
('135', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '60', '29', '2026-10-15 22:19:21'),
('136', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '58', '30', '2026-10-15 22:19:30'),
('137', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '61', '30', '2026-10-15 22:19:51'),
('138', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '59', '29', '2026-10-15 22:20:00'),
('139', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '62', '29', '2026-10-15 22:20:21'),
('140', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '60', '30', '2026-10-15 22:20:30'),
('141', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '63', '29', '2026-10-15 22:20:51'),
('142', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '61', '29', '2026-10-15 22:21:00'),
('143', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '64', '30', '2026-10-15 22:21:21'),
('144', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '62', '30', '2026-10-15 22:21:30'),
('145', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '65', '29', '2026-10-15 22:21:51'),
('146', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '63', '29', '2026-10-15 22:22:00'),
('147', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '66', '30', '2026-10-15 22:22:21'),
('148', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '64', '29', '2026-10-15 22:22:30'),
('149', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '67', '29', '2026-10-15 22:22:51'),
('150', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '65', '30', '2026-10-15 22:23:00'),
('151', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '68', '30', '2026-10-15 22:23:21'),
('152', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '66', '30', '2026-10-15 22:23:30'),
('153', '1006', '4', 'fbb9cda6-b07c-479e-a0a5-8ad23346e221', '1', '5', '2026-10-15 22:23:33'),
('154', '1006', '4', 'f7f5de60-3e9a-48fa-a2e0-80e4ffec7824', '1', '2', '2026-10-15 22:23:35'),
('155', '1006', '4', '18b0dfef-c003-45e4-9531-5eced3f1b348', '1', '3', '2026-10-15 22:23:39'),
('156', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '69', '29', '2026-10-15 22:23:51'),
('157', '1006', '5', 'bdb2dc8d-0d7a-46de-9ec0-60f21ff6ef0e', '1', '3', '2026-10-15 22:23:52'),
('158', '1006', '5', 'c0ce6bf8-c783-483c-a1cb-44671474ad17', '1', '1', '2026-10-15 22:23:53'),
('159', '1006', '5', 'f3cd144c-52d4-4c51-86b1-ce52a6b7b85c', '1', '1', '2026-10-15 22:23:55'),
('160', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '67', '30', '2026-10-15 22:24:00'),
('161', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '70', '30', '2026-10-15 22:24:21'),
('162', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '68', '29', '2026-10-15 22:24:30'),
('163', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '71', '29', '2026-10-15 22:24:51'),
('164', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '69', '30', '2026-10-15 22:25:00'),
('165', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '72', '29', '2026-10-15 22:25:21'),
('166', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '70', '29', '2026-10-15 22:25:30'),
('167', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '73', '30', '2026-10-15 22:25:51'),
('168', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '71', '30', '2026-10-15 22:26:00'),
('169', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '74', '30', '2026-10-15 22:26:21'),
('170', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '72', '30', '2026-10-15 22:26:30'),
('171', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '75', '29', '2026-10-15 22:26:51'),
('172', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '73', '30', '2026-10-15 22:27:00'),
('173', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '76', '30', '2026-10-15 22:27:21'),
('174', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '74', '29', '2026-10-15 22:27:30'),
('175', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '77', '30', '2026-10-15 22:27:51'),
('176', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '75', '30', '2026-10-15 22:28:00'),
('177', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '78', '29', '2026-10-15 22:28:21'),
('178', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '76', '29', '2026-10-15 22:28:30'),
('179', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '79', '30', '2026-10-15 22:28:51'),
('180', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '77', '30', '2026-10-15 22:29:00'),
('181', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '80', '29', '2026-10-15 22:29:21'),
('182', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '78', '29', '2026-10-15 22:29:30'),
('183', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '81', '29', '2026-10-15 22:29:51'),
('184', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '79', '30', '2026-10-15 22:30:00'),
('185', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '82', '30', '2026-10-15 22:30:21'),
('186', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '80', '30', '2026-10-15 22:30:30'),
('187', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '83', '30', '2026-10-15 22:30:51'),
('188', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '81', '29', '2026-10-15 22:31:00'),
('189', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '84', '30', '2026-10-15 22:31:21'),
('190', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '82', '29', '2026-10-15 22:31:30'),
('191', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '85', '30', '2026-10-15 22:31:51'),
('192', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '83', '30', '2026-10-15 22:32:00'),
('193', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '86', '29', '2026-10-15 22:32:21'),
('194', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '84', '30', '2026-10-15 22:32:30'),
('195', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '87', '30', '2026-10-15 22:32:51'),
('196', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '85', '30', '2026-10-15 22:33:00'),
('197', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '88', '29', '2026-10-15 22:33:21'),
('198', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '86', '29', '2026-10-15 22:33:30'),
('199', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '89', '30', '2026-10-15 22:33:51'),
('200', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '87', '29', '2026-10-15 22:34:00'),
('201', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '90', '30', '2026-10-15 22:34:21'),
('202', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '88', '30', '2026-10-15 22:34:30'),
('203', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '91', '29', '2026-10-15 22:34:51'),
('204', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '89', '30', '2026-10-15 22:35:00'),
('205', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '92', '29', '2026-10-15 22:35:21'),
('206', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '90', '29', '2026-10-15 22:35:30'),
('207', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '93', '30', '2026-10-15 22:35:51'),
('208', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '91', '30', '2026-10-15 22:36:00'),
('209', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '94', '29', '2026-10-15 22:36:21'),
('210', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '92', '30', '2026-10-15 22:36:30'),
('211', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '95', '30', '2026-10-15 22:36:51'),
('212', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '93', '30', '2026-10-15 22:37:00'),
('213', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '96', '30', '2026-10-15 22:37:21'),
('214', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '94', '29', '2026-10-15 22:37:30'),
('215', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '97', '30', '2026-10-15 22:37:51'),
('216', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '95', '29', '2026-10-15 22:38:00'),
('217', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '98', '29', '2026-10-15 22:38:21'),
('218', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '96', '29', '2026-10-15 22:38:30'),
('219', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '99', '29', '2026-10-15 22:38:51'),
('220', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '97', '29', '2026-10-15 22:39:00'),
('221', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '100', '30', '2026-10-15 22:39:21'),
('222', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '98', '30', '2026-10-15 22:39:30'),
('223', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '101', '29', '2026-10-15 22:39:51'),
('224', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '99', '30', '2026-10-15 22:40:00'),
('225', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '102', '29', '2026-10-15 22:40:21'),
('226', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '100', '29', '2026-10-15 22:40:30'),
('227', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '103', '30', '2026-10-15 22:40:51'),
('228', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '101', '29', '2026-10-15 22:41:00'),
('229', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '104', '29', '2026-10-15 22:41:21'),
('230', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '102', '30', '2026-10-15 22:41:30'),
('231', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '105', '29', '2026-10-15 22:41:51'),
('232', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '103', '30', '2026-10-15 22:42:00'),
('233', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '106', '30', '2026-10-15 22:42:21'),
('234', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '104', '29', '2026-10-15 22:42:30'),
('235', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '107', '29', '2026-10-15 22:42:51'),
('236', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '105', '29', '2026-10-15 22:43:00'),
('237', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '108', '29', '2026-10-15 22:43:21'),
('238', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '106', '29', '2026-10-15 22:43:30'),
('239', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '109', '30', '2026-10-15 22:43:51'),
('240', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '107', '30', '2026-10-15 22:44:00'),
('241', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '110', '29', '2026-10-15 22:44:21'),
('242', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '108', '29', '2026-10-15 22:44:30'),
('243', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '111', '30', '2026-10-15 22:44:51'),
('244', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '109', '30', '2026-10-15 22:45:00'),
('245', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '112', '30', '2026-10-15 22:45:21'),
('246', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '110', '29', '2026-10-15 22:45:30'),
('247', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '113', '29', '2026-10-15 22:45:51'),
('248', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '111', '29', '2026-10-15 22:46:00'),
('249', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '114', '30', '2026-10-15 22:46:21'),
('250', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '112', '30', '2026-10-15 22:46:30'),
('251', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '115', '29', '2026-10-15 22:46:51'),
('252', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '113', '29', '2026-10-15 22:47:00'),
('253', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '116', '29', '2026-10-15 22:47:21'),
('254', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '114', '29', '2026-10-15 22:47:30'),
('255', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '117', '30', '2026-10-15 22:47:51'),
('256', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '115', '30', '2026-10-15 22:48:00'),
('257', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '118', '29', '2026-10-15 22:48:21'),
('258', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '116', '29', '2026-10-15 22:48:30'),
('259', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '119', '30', '2026-10-15 22:48:51'),
('260', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '117', '29', '2026-10-15 22:49:00'),
('261', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '120', '29', '2026-10-15 22:49:21'),
('262', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '118', '29', '2026-10-15 22:49:30'),
('263', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '121', '30', '2026-10-15 22:49:51'),
('264', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '119', '30', '2026-10-15 22:50:00'),
('265', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '122', '29', '2026-10-15 22:50:21'),
('266', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '120', '30', '2026-10-15 22:50:30'),
('267', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '123', '29', '2026-10-15 22:50:51'),
('268', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '121', '30', '2026-10-15 22:51:00'),
('269', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '124', '30', '2026-10-15 22:51:21'),
('270', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '122', '29', '2026-10-15 22:51:30'),
('271', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '125', '30', '2026-10-15 22:51:51'),
('272', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '123', '30', '2026-10-15 22:52:00'),
('273', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '126', '29', '2026-10-15 22:52:21'),
('274', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '124', '30', '2026-10-15 22:52:30'),
('275', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '127', '30', '2026-10-15 22:52:51'),
('276', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '125', '30', '2026-10-15 22:53:00'),
('277', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '128', '29', '2026-10-15 22:53:21'),
('278', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '126', '30', '2026-10-15 22:53:30'),
('279', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '129', '29', '2026-10-15 22:53:51'),
('280', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '127', '29', '2026-10-15 22:54:00'),
('281', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '130', '30', '2026-10-15 22:54:21'),
('282', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '128', '29', '2026-10-15 22:54:30'),
('283', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '131', '30', '2026-10-15 22:54:51'),
('284', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '129', '30', '2026-10-15 22:55:00'),
('285', '1006', '4', '4ec7fdc8-11cb-42b2-9d84-57ab30582948', '132', '8', '2026-10-15 22:55:00'),
('286', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '130', '29', '2026-10-15 22:55:30'),
('287', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '131', '30', '2026-10-15 22:56:00'),
('288', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '132', '29', '2026-10-15 22:56:30'),
('289', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '133', '30', '2026-10-15 22:57:00'),
('290', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '134', '29', '2026-10-15 22:57:30'),
('291', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '135', '30', '2026-10-15 22:58:00'),
('292', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '136', '30', '2026-10-15 22:58:30'),
('293', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '137', '30', '2026-10-15 22:59:00'),
('294', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '138', '29', '2026-10-15 22:59:30'),
('295', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '139', '30', '2026-10-15 23:00:00'),
('296', '1006', '4', 'ba290eb0-65ce-4e9e-b1e3-f4c1f3c02e9a', '1', '13', '2026-10-15 23:00:06'),
('297', '1006', '4', '34f2e61a-e832-4d9e-a193-96eae53cc8e9', '1', '14', '2026-10-15 23:00:21'),
('298', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '140', '29', '2026-10-15 23:00:30'),
('299', '1006', '4', '6bfafcb7-48ce-420c-b7f5-5656bfd17982', '1', '30', '2026-10-15 23:00:51'),
('300', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '141', '30', '2026-10-15 23:01:00'),
('301', '1006', '4', '6bfafcb7-48ce-420c-b7f5-5656bfd17982', '2', '20', '2026-10-15 23:01:12'),
('302', '1006', '4', '02774aab-0f61-4c8f-a830-68ddddc66d64', '1', '9', '2026-10-15 23:01:22'),
('303', '1006', '5', '9c974c0f-111a-469c-b1ad-8026c65a39d4', '1', '4', '2026-10-15 23:01:26'),
('304', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '142', '30', '2026-10-15 23:01:30'),
('305', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '143', '29', '2026-10-15 23:02:00'),
('306', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '144', '29', '2026-10-15 23:02:30'),
('307', '1006', '4', '29c14707-b7b0-44ed-a620-b28f8319a229', '1', '7', '2026-10-15 23:02:57'),
('308', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '145', '29', '2026-10-15 23:03:00'),
('309', '1006', '4', 'ae8e9731-249c-4a5d-9993-8e3460b8b642', '1', '30', '2026-10-15 23:03:28'),
('310', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '146', '30', '2026-10-15 23:03:30'),
('311', '1006', '4', 'ae8e9731-249c-4a5d-9993-8e3460b8b642', '2', '11', '2026-10-15 23:03:39'),
('312', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '147', '30', '2026-10-15 23:04:00'),
('313', '1006', '4', '506abd1f-c422-487e-b277-2b6bdd13eaa5', '1', '22', '2026-10-15 23:04:01'),
('314', '1006', '4', '325e57ab-4ece-45c1-b181-3995828f1c5b', '1', '1', '2026-10-15 23:04:03'),
('315', '1006', '5', '4852120b-8ac7-4c92-bb48-cac63a9e197b', '1', '1', '2026-10-15 23:04:16'),
('316', '1006', '5', '837f3f6c-e878-4eef-bfeb-552cdde3f574', '1', '2', '2026-10-15 23:04:18'),
('317', '1006', '5', 'd8cec6b1-07ec-420d-896c-54b67ee36e47', '1', '3', '2026-10-15 23:04:22'),
('318', '1006', '5', 'fc587222-6498-4a09-9245-43ba5ae9e466', '1', '4', '2026-10-15 23:04:27'),
('319', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '148', '29', '2026-10-15 23:04:30'),
('320', '1006', '5', '886c50f4-6f30-4211-91a1-f6fa8d8f726e', '1', '4', '2026-10-15 23:04:31'),
('321', '1006', '5', 'c5366f63-ce2b-45c9-aea4-ebfdfe5cfb7e', '1', '1', '2026-10-15 23:04:33'),
('322', '1006', '4', '06d7e5af-b3cf-429c-b250-440e2214a6ef', '1', '30', '2026-10-15 23:04:33'),
('323', '1006', '5', 'a9fd9994-556e-42c7-978e-31830cd41ddc', '1', '12', '2026-10-15 23:04:45'),
('324', '1006', '5', '8d303506-b828-4e9f-8099-03af10843eb8', '1', '5', '2026-10-15 23:04:51'),
('325', '1006', '5', '0f62cab3-b22c-408e-9f40-87920a0e2384', '1', '3', '2026-10-15 23:04:55'),
('326', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '149', '29', '2026-10-15 23:05:00'),
('327', '1006', '4', '06d7e5af-b3cf-429c-b250-440e2214a6ef', '2', '29', '2026-10-15 23:05:03'),
('328', '1006', '4', '06d7e5af-b3cf-429c-b250-440e2214a6ef', '3', '3', '2026-10-15 23:05:07'),
('329', '1006', '4', '5790fde2-55e5-4448-b630-93090bec9c71', '1', '3', '2026-10-15 23:05:11'),
('330', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '1', '5', '2026-10-15 23:05:25'),
('331', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '150', '30', '2026-10-15 23:05:30'),
('332', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '1', '30', '2026-10-15 23:05:41'),
('333', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '2', '30', '2026-10-15 23:05:55'),
('334', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '151', '30', '2026-10-15 23:06:00'),
('335', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '2', '30', '2026-10-15 23:06:11'),
('336', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '3', '29', '2026-10-15 23:06:25'),
('337', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '152', '29', '2026-10-15 23:06:30'),
('338', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '3', '30', '2026-10-15 23:06:41'),
('339', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '4', '29', '2026-10-15 23:06:55'),
('340', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '153', '30', '2026-10-15 23:07:00'),
('341', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '4', '29', '2026-10-15 23:07:11'),
('342', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '5', '30', '2026-10-15 23:07:25'),
('343', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '154', '30', '2026-10-15 23:07:30'),
('344', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '5', '30', '2026-10-15 23:07:41'),
('345', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '6', '30', '2026-10-15 23:07:55'),
('346', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '155', '29', '2026-10-15 23:08:00'),
('347', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '6', '29', '2026-10-15 23:08:11'),
('348', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '7', '29', '2026-10-15 23:08:25'),
('349', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '156', '30', '2026-10-15 23:08:30'),
('350', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '7', '30', '2026-10-15 23:08:41'),
('351', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '8', '29', '2026-10-15 23:08:55'),
('352', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '157', '29', '2026-10-15 23:09:00'),
('353', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '8', '29', '2026-10-15 23:09:11'),
('354', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '9', '30', '2026-10-15 23:09:25'),
('355', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '158', '30', '2026-10-15 23:09:30'),
('356', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '9', '30', '2026-10-15 23:09:41'),
('357', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '10', '29', '2026-10-15 23:09:55'),
('358', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '159', '29', '2026-10-15 23:10:00'),
('359', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '10', '29', '2026-10-15 23:10:11'),
('360', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '160', '30', '2026-10-15 23:10:30'),
('361', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '11', '29', '2026-10-15 23:10:41'),
('362', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '161', '29', '2026-10-15 23:11:00'),
('363', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '12', '29', '2026-10-15 23:11:11'),
('364', '1006', '4', '291242b7-6e6a-42f8-aa1a-544e5b809429', '13', '5', '2026-10-15 23:11:16'),
('365', '1006', '4', 'c4a82e6c-454d-45ad-8332-ebf0e85fa975', '1', '1', '2026-10-15 23:11:18'),
('366', '1006', '4', '20f73ef4-bf76-4f4e-9b93-9afc63bd4de2', '1', '10', '2026-10-15 23:11:28'),
('367', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '162', '29', '2026-10-15 23:11:30'),
('368', '1006', '4', 'f9f20e45-52a0-4193-aa39-800b2f54b8c6', '1', '13', '2026-10-15 23:11:42'),
('369', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '163', '30', '2026-10-15 23:12:00'),
('370', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '1', '30', '2026-10-15 23:12:12'),
('371', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '164', '29', '2026-10-15 23:12:30'),
('372', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '2', '30', '2026-10-15 23:12:42'),
('373', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '165', '30', '2026-10-15 23:13:00'),
('374', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '3', '29', '2026-10-15 23:13:12'),
('375', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '166', '29', '2026-10-15 23:13:30'),
('376', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '4', '30', '2026-10-15 23:13:42'),
('377', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '167', '30', '2026-10-15 23:14:00'),
('378', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '5', '29', '2026-10-15 23:14:12'),
('379', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '168', '30', '2026-10-15 23:14:30'),
('380', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '6', '30', '2026-10-15 23:14:42'),
('381', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '169', '30', '2026-10-15 23:15:00'),
('382', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '7', '30', '2026-10-15 23:15:12'),
('383', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '170', '29', '2026-10-15 23:15:30'),
('384', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '8', '29', '2026-10-15 23:15:42'),
('385', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '171', '29', '2026-10-15 23:16:00'),
('386', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '9', '29', '2026-10-15 23:16:12'),
('387', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '172', '29', '2026-10-15 23:16:30'),
('388', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '10', '30', '2026-10-15 23:16:42'),
('389', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '173', '30', '2026-10-15 23:17:00'),
('390', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '11', '29', '2026-10-15 23:17:12'),
('391', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '174', '30', '2026-10-15 23:17:30'),
('392', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '12', '30', '2026-10-15 23:17:42'),
('393', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '175', '29', '2026-10-15 23:18:00'),
('394', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '13', '29', '2026-10-15 23:18:12'),
('395', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '176', '30', '2026-10-15 23:18:30'),
('396', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '14', '30', '2026-10-15 23:18:42'),
('397', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '177', '30', '2026-10-15 23:19:00'),
('398', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '15', '30', '2026-10-15 23:19:12'),
('399', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '178', '29', '2026-10-15 23:19:30'),
('400', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '16', '30', '2026-10-15 23:19:42'),
('401', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '179', '29', '2026-10-15 23:20:00'),
('402', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '17', '29', '2026-10-15 23:20:12'),
('403', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '180', '30', '2026-10-15 23:20:30'),
('404', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '18', '29', '2026-10-15 23:20:42'),
('405', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '181', '30', '2026-10-15 23:21:00'),
('406', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '19', '30', '2026-10-15 23:21:12'),
('407', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '182', '30', '2026-10-15 23:21:30'),
('408', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '20', '29', '2026-10-15 23:21:42'),
('409', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '183', '29', '2026-10-15 23:22:00'),
('410', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '21', '29', '2026-10-15 23:22:12'),
('411', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '184', '30', '2026-10-15 23:22:30'),
('412', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '22', '30', '2026-10-15 23:22:42'),
('413', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '185', '30', '2026-10-15 23:23:00'),
('414', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '23', '29', '2026-10-15 23:23:12'),
('415', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '186', '29', '2026-10-15 23:23:30'),
('416', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '24', '30', '2026-10-15 23:23:42'),
('417', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '187', '29', '2026-10-15 23:24:00'),
('418', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '25', '30', '2026-10-15 23:24:12'),
('419', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '188', '30', '2026-10-15 23:24:30'),
('420', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '26', '29', '2026-10-15 23:24:42'),
('421', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '189', '29', '2026-10-15 23:25:00'),
('422', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '27', '30', '2026-10-15 23:25:12'),
('423', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '190', '30', '2026-10-15 23:25:30'),
('424', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '28', '29', '2026-10-15 23:25:42'),
('425', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '191', '30', '2026-10-15 23:26:00'),
('426', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '29', '30', '2026-10-15 23:26:12'),
('427', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '192', '30', '2026-10-15 23:26:30'),
('428', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '30', '30', '2026-10-15 23:26:42'),
('429', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '193', '30', '2026-10-15 23:27:00'),
('430', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '31', '30', '2026-10-15 23:27:12'),
('431', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '194', '29', '2026-10-15 23:27:30'),
('432', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '32', '29', '2026-10-15 23:27:42'),
('433', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '195', '29', '2026-10-15 23:28:00'),
('434', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '33', '30', '2026-10-15 23:28:12'),
('435', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '196', '30', '2026-10-15 23:28:30'),
('436', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '34', '30', '2026-10-15 23:28:42'),
('437', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '197', '29', '2026-10-15 23:29:00'),
('438', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '35', '29', '2026-10-15 23:29:12'),
('439', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '198', '29', '2026-10-15 23:29:30'),
('440', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '36', '29', '2026-10-15 23:29:42'),
('441', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '199', '30', '2026-10-15 23:30:00'),
('442', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '37', '30', '2026-10-15 23:30:12'),
('443', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '200', '29', '2026-10-15 23:30:30'),
('444', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '38', '29', '2026-10-15 23:30:42'),
('445', '1006', '4', 'a1bc2a07-bcf4-42e8-81e1-9b69936e75d7', '39', '15', '2026-10-15 23:30:57'),
('446', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '201', '30', '2026-10-15 23:31:00'),
('447', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '202', '30', '2026-10-15 23:31:30'),
('448', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '1', '30', '2026-10-15 23:32:00'),
('449', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '203', '29', '2026-10-15 23:32:00'),
('450', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '2', '30', '2026-10-15 23:32:30'),
('451', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '204', '30', '2026-10-15 23:32:30'),
('452', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '3', '29', '2026-10-15 23:33:00'),
('453', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '205', '30', '2026-10-15 23:33:00'),
('454', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '4', '30', '2026-10-15 23:33:30'),
('455', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '206', '30', '2026-10-15 23:33:30'),
('456', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '5', '29', '2026-10-15 23:34:00'),
('457', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '207', '29', '2026-10-15 23:34:00'),
('458', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '6', '29', '2026-10-15 23:34:30'),
('459', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '208', '29', '2026-10-15 23:34:30'),
('460', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '7', '29', '2026-10-15 23:35:00'),
('461', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '209', '29', '2026-10-15 23:35:00'),
('462', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '8', '30', '2026-10-15 23:35:30'),
('463', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '210', '29', '2026-10-15 23:35:30'),
('464', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '9', '29', '2026-10-15 23:36:00'),
('465', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '211', '30', '2026-10-15 23:36:00'),
('466', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '10', '29', '2026-10-15 23:36:30'),
('467', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '212', '29', '2026-10-15 23:36:30'),
('468', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '11', '29', '2026-10-15 23:37:00'),
('469', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '213', '30', '2026-10-15 23:37:00'),
('470', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '12', '30', '2026-10-15 23:37:30'),
('471', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '214', '30', '2026-10-15 23:37:30'),
('472', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '13', '30', '2026-10-15 23:38:00'),
('473', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '215', '30', '2026-10-15 23:38:00'),
('474', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '14', '29', '2026-10-15 23:38:30'),
('475', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '216', '29', '2026-10-15 23:38:30'),
('476', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '15', '29', '2026-10-15 23:39:00'),
('477', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '217', '29', '2026-10-15 23:39:00'),
('478', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '16', '30', '2026-10-15 23:39:30'),
('479', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '218', '30', '2026-10-15 23:39:30'),
('480', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '17', '29', '2026-10-15 23:40:00'),
('481', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '219', '29', '2026-10-15 23:40:00'),
('482', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '18', '30', '2026-10-15 23:40:30'),
('483', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '220', '30', '2026-10-15 23:40:30'),
('484', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '19', '29', '2026-10-15 23:41:00'),
('485', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '221', '29', '2026-10-15 23:41:00'),
('486', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '20', '30', '2026-10-15 23:41:30'),
('487', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '222', '30', '2026-10-15 23:41:30'),
('488', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '21', '29', '2026-10-15 23:42:00'),
('489', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '223', '29', '2026-10-15 23:42:00'),
('490', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '22', '30', '2026-10-15 23:42:30'),
('491', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '224', '30', '2026-10-15 23:42:30'),
('492', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '23', '29', '2026-10-15 23:43:00'),
('493', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '225', '29', '2026-10-15 23:43:00'),
('494', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '24', '29', '2026-10-15 23:43:30'),
('495', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '226', '30', '2026-10-15 23:43:30'),
('496', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '25', '30', '2026-10-15 23:44:00'),
('497', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '227', '29', '2026-10-15 23:44:00'),
('498', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '26', '30', '2026-10-15 23:44:30'),
('499', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '228', '30', '2026-10-15 23:44:30'),
('500', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '27', '29', '2026-10-15 23:45:00'),
('501', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '229', '29', '2026-10-15 23:45:00'),
('502', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '28', '30', '2026-10-15 23:45:30'),
('503', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '230', '30', '2026-10-15 23:45:30'),
('504', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '29', '29', '2026-10-15 23:46:00'),
('505', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '231', '30', '2026-10-15 23:46:00'),
('506', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '30', '29', '2026-10-15 23:46:30'),
('507', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '232', '29', '2026-10-15 23:46:30'),
('508', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '31', '30', '2026-10-15 23:47:00'),
('509', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '233', '30', '2026-10-15 23:47:00'),
('510', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '32', '29', '2026-10-15 23:47:30'),
('511', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '234', '29', '2026-10-15 23:47:30'),
('512', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '33', '30', '2026-10-15 23:48:00'),
('513', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '235', '30', '2026-10-15 23:48:00'),
('514', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '34', '29', '2026-10-15 23:48:30'),
('515', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '236', '29', '2026-10-15 23:48:30'),
('516', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '11', '4', '2026-10-15 23:48:55'),
('517', '1006', '5', '164e97d3-e49a-46f8-b56a-82295eca72fe', '12', '1', '2026-10-15 23:48:56'),
('518', '1006', '5', '0b7344b2-acf1-4f3e-8b5a-10b40ee4fbc0', '1', '3', '2026-10-15 23:49:00'),
('519', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '35', '30', '2026-10-15 23:49:00'),
('520', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '237', '29', '2026-10-15 23:49:00'),
('521', '1006', '5', '39beb442-b674-45e2-b990-f533aeb7ef0d', '1', '4', '2026-10-15 23:49:04'),
('522', '1006', '5', '4a3ba063-c15a-438a-9724-c5f7995268af', '1', '1', '2026-10-15 23:49:05'),
('523', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '36', '29', '2026-10-15 23:49:30'),
('524', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '238', '30', '2026-10-15 23:49:30'),
('525', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '37', '30', '2026-10-15 23:50:00'),
('526', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '239', '30', '2026-10-15 23:50:00'),
('527', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '38', '29', '2026-10-15 23:50:30'),
('528', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '240', '29', '2026-10-15 23:50:30'),
('529', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '39', '30', '2026-10-15 23:51:00'),
('530', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '241', '30', '2026-10-15 23:51:00'),
('531', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '40', '29', '2026-10-15 23:51:30'),
('532', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '242', '29', '2026-10-15 23:51:30'),
('533', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '41', '29', '2026-10-15 23:52:00'),
('534', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '243', '29', '2026-10-15 23:52:00'),
('535', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '42', '30', '2026-10-15 23:52:30'),
('536', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '244', '30', '2026-10-15 23:52:30'),
('537', '1006', '4', '41a1ead9-2b25-4472-8990-4984a8d0ad0b', '43', '20', '2026-10-15 23:52:51'),
('538', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '245', '29', '2026-10-15 23:53:00'),
('539', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '246', '30', '2026-10-15 23:53:30'),
('540', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '247', '30', '2026-10-15 23:54:00'),
('541', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '248', '30', '2026-10-15 23:54:30'),
('542', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '249', '29', '2026-10-15 23:55:00'),
('543', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '250', '29', '2026-10-15 23:55:30'),
('544', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '251', '29', '2026-10-15 23:56:00'),
('545', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '252', '29', '2026-10-15 23:56:30'),
('546', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '253', '30', '2026-10-15 23:57:00'),
('547', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '254', '30', '2026-10-15 23:57:30'),
('548', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '255', '29', '2026-10-15 23:58:00'),
('549', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '256', '30', '2026-10-15 23:58:30'),
('550', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '257', '29', '2026-10-15 23:59:00'),
('551', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '258', '30', '2026-10-15 23:59:30'),
('552', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '259', '29', '2026-10-16 00:00:00'),
('553', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '260', '30', '2026-10-16 00:00:30'),
('554', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '261', '29', '2026-10-16 00:01:00'),
('555', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '262', '29', '2026-10-16 00:01:30'),
('556', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '263', '30', '2026-10-16 00:02:00'),
('557', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '264', '29', '2026-10-16 00:02:30'),
('558', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '265', '30', '2026-10-16 00:03:00'),
('559', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '266', '29', '2026-10-16 00:03:30'),
('560', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '267', '30', '2026-10-16 00:04:00'),
('561', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '268', '29', '2026-10-16 00:04:30'),
('562', '1006', '4', 'fc55c929-feb4-4db4-8173-9b549f380d1d', '1', '9', '2026-10-16 00:04:36'),
('563', '1006', '4', '0b27abdb-efcd-4666-8500-2487d1bc0c58', '1', '8', '2026-10-16 00:04:44'),
('564', '1006', '4', '8922089b-f900-4dd7-b563-5a4e33d91337', '1', '3', '2026-10-16 00:04:49'),
('565', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '269', '30', '2026-10-16 00:05:00'),
('566', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '270', '29', '2026-10-16 00:05:30'),
('567', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '271', '30', '2026-10-16 00:06:00'),
('568', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '272', '30', '2026-10-16 00:06:30'),
('569', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '273', '30', '2026-10-16 00:07:00'),
('570', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '274', '29', '2026-10-16 00:07:30'),
('571', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '275', '30', '2026-10-16 00:08:00'),
('572', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '276', '29', '2026-10-16 00:08:30'),
('573', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '277', '29', '2026-10-16 00:09:00'),
('574', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '278', '30', '2026-10-16 00:09:30'),
('575', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '279', '30', '2026-10-16 00:10:00'),
('576', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '280', '29', '2026-10-16 00:10:30'),
('577', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '281', '29', '2026-10-16 00:11:00'),
('578', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '282', '29', '2026-10-16 00:11:30'),
('579', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '283', '30', '2026-10-16 00:12:00'),
('580', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '284', '30', '2026-10-16 00:12:30'),
('581', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '285', '30', '2026-10-16 00:13:00'),
('582', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '286', '29', '2026-10-16 00:13:30'),
('583', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '287', '30', '2026-10-16 00:14:00'),
('584', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '288', '29', '2026-10-16 00:14:30'),
('585', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '289', '30', '2026-10-16 00:15:00'),
('586', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '290', '30', '2026-10-16 00:15:30'),
('587', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '291', '29', '2026-10-16 00:16:00'),
('588', '1006', '4', 'a4fb5512-7351-4f08-b4fa-e818aa5bd2d2', '292', '30', '2026-10-16 00:16:30');

-- ld_training_engagement  (1 row)
DELETE FROM `ld_training_engagement`;
INSERT INTO `ld_training_engagement` (`id`, `source_module`, `external_reference_id`, `learner_team_type`, `learner_id`, `learner_name`, `department`, `training_activity`, `engagement_status`, `engagement_data`, `recommended_action`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'workforce', 'ENG-1001', 'department-team', '9', 'Elena Ocampo', 'INSTRUCTORS', 'compliance-training', 'low', '4 of 12 learners have not started the module', 'Send a reminder and schedule a coaching session', NULL, 'monitoring', NULL, '2026-09-25 05:29:35', '2026-09-25 08:56:09');

-- ld_training_recommendation
DELETE FROM `ld_training_recommendation`;

-- ld_training_requests  (6 rows)
DELETE FROM `ld_training_requests`;
INSERT INTO `ld_training_requests` (`id`, `learner_id`, `skill_id`, `skill_name`, `message`, `instructor_id`, `instructor_note`, `target_department`, `priority_level`, `status`, `source_module`, `external_reference_id`, `created_by`, `created_at`, `updated_at`) VALUES
('7', NULL, NULL, 'Records management', 'Predicted skill need for 3 learner(s). Target proficiency: Intermediate. Expected learning output: Records handling course.', NULL, NULL, 'ADMIN STAFF', 'medium', 'pending', 'workforce', 'competency:3', '1018', '2026-09-25 08:38:55', '2026-09-25 08:38:55'),
('8', NULL, NULL, 'Cybersecurity awareness', 'Predicted skill need for 7 learner(s). Target proficiency: Intermediate. Expected learning output: Skills course and completion report by department.', NULL, NULL, 'ADMIN STAFF', 'critical', 'pending', 'workforce', 'competency:2', '1018', '2026-09-25 08:38:55', '2026-09-25 08:38:55'),
('9', '13', NULL, 'Retention follow-up — David Aquino', 'Turnover risk: high (score 72.00). low engagement, expired certification Assign refresher course and mentor check-in', NULL, NULL, 'PSYCHOLOGY DEPARTMENT', 'high', 'pending', 'workforce', 'turnover_risk:1', '1018', '2026-09-25 08:38:55', '2026-09-25 08:38:55'),
('10', NULL, NULL, 'Retention follow-up — Felix Del Rosario', 'Turnover risk: medium (score 48.00). no learning activity in 30 days Invite to orientation refresher', NULL, NULL, 'PSYCHOLOGY DEPARTMENT', 'medium', 'pending', 'workforce', 'turnover_risk:2', '1018', '2026-09-25 08:52:06', '2026-10-09 10:58:19'),
('11', NULL, NULL, 'Cloud fundamentals workshop', 'Raised from the IT competency need.', NULL, NULL, 'IT DEPARTMENT', 'high', 'pending', 'workforce', 'competency:1', '1018', '2026-09-25 08:52:19', '2026-09-25 08:52:19'),
('12', '13', '33', 'Teamwork', 'Our team has no teamwork training available.', NULL, NULL, NULL, 'medium', 'pending', 'learning', NULL, NULL, '2026-09-25 08:54:23', '2026-09-25 08:54:23');

-- ld_turnover_risk  (3 rows)
DELETE FROM `ld_turnover_risk`;
INSERT INTO `ld_turnover_risk` (`id`, `source_module`, `external_reference_id`, `employee_id`, `employee_name`, `department`, `position_name`, `risk_level`, `risk_score`, `risk_factors`, `recommended_action`, `assessment_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'workforce', 'TR-2001', '13', 'David Aquino', 'PSYCHOLOGY DEPARTMENT', 'Psychology Instructor', 'high', '72.00', 'low engagement, expired certification', 'Assign refresher course and mentor check-in', '2026-09-25', 'monitoring', NULL, '2026-09-25 05:29:36', '2026-09-25 08:38:55'),
('2', 'workforce', 'TR-2002', NULL, 'Felix Del Rosario', 'PSYCHOLOGY DEPARTMENT', NULL, 'medium', '48.00', 'no learning activity in 30 days', 'Invite to orientation refresher', NULL, 'mitigated', NULL, '2026-09-25 05:29:36', '2026-09-25 08:38:55'),
('3', 'workforce', 'probe-critical-1', '13', 'David Aquino', 'PSYCHOLOGY DEPARTMENT', 'Instructor', 'critical', '88.00', 'flight risk, exit interview pending', 'Retention conversation', '2026-09-25', 'received', '1018', '2026-09-25 09:51:33', '2026-09-25 09:51:33');

-- ld_user_event  (2 rows)
DELETE FROM `ld_user_event`;
INSERT INTO `ld_user_event` (`id`, `title`, `description`, `event_date`, `event_time`, `event_type`, `color`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'Study Session', 'Review materials for exam', '2026-08-22', '14:00:00', 'meeting', '#320082', '35', '2026-08-20 16:33:01', '2026-08-20 16:33:01'),
('2', 'eu', 'eb665e', '2026-10-09', '02:25:00', 'personal', '#807500', '1007', '2026-10-09 02:23:12', '2026-10-09 02:23:12');

-- ld_user_profile  (2 rows)
DELETE FROM `ld_user_profile`;
INSERT INTO `ld_user_profile` (`id`, `employee_id`, `bio`, `profile_photo`, `created_at`, `updated_at`) VALUES
('13', '1007', NULL, 'employee_1007_afc6ef5f1e2d105e.jpg', '2026-10-15 20:00:09', '2026-10-17 01:43:41'),
('17', '1006', 'bnvm cbnvmc', 'employee_1006_cfcc2ba7d28b01b0.jpg', '2026-10-15 21:13:57', '2026-10-15 21:14:05');

-- ld_user_role  (1 row)
DELETE FROM `ld_user_role`;
INSERT INTO `ld_user_role` (`employee_id`, `learning_role`, `updated_by`, `created_at`, `updated_at`) VALUES
('1001', 'instructor', '1007', '2026-10-08 12:31:49', '2026-10-08 12:31:49');

-- ld_user_session  (38 rows)
DELETE FROM `ld_user_session`;
INSERT INTO `ld_user_session` (`id`, `employee_id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `device_label`, `revoked`, `created_at`, `last_seen_at`) VALUES
('13', '1006', '6', 'dc7720d44e4bc7ff7e12d409e84a0134352058e6b402f8fcae9444c35cec4b9c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-15 20:32:36', '2026-10-16 02:37:55'),
('30', '1001', '5', 'bf7fc6c132d0cd0c5aa5028192dfe06a4959e361af90cade881dff342711ed0b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 20:39:01', '2026-10-15 20:57:22'),
('50', '1006', '6', 'd763dc46dcc88efe670004a47dde6486c8b74c9a606c764595fd1e4974d08ed5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:04:37', '2026-10-15 21:04:37'),
('51', '1006', '6', 'd9853f6ac81ecfd81a8e6baa6bd3faeb7dd1b15bed2b16e8e9513a18f0710195', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:09:28', '2026-10-15 21:09:28'),
('53', '1006', '6', 'fa1eaf24017cc42aca44fbaa45911807def16e445749d2549d4b74d7d7aa29d8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:10:37', '2026-10-15 21:10:37'),
('58', '1006', '6', 'f8a9f1e06708b656cd77957658c273044f5e2441fc414506b15d8fcffc5742f1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:10:44', '2026-10-15 21:10:44'),
('62', '1006', '6', '78a45b29dccff8db9b4a82fc0b6698573770da6a4fd4bd2f5d8e528a7980fdcc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:10:56', '2026-10-15 21:10:56'),
('67', '1006', '6', 'ff26e65e2779c61994930d097f657a741cee315638d02b91381a105a248cbf43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:11:31', '2026-10-15 21:11:31'),
('69', '1006', '6', 'd617a5eab0e27c196d07d9b69c2c63e1bffe0a30580a2aef6784ff64cc2cf99f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:12:00', '2026-10-15 21:12:00'),
('79', '1006', '6', 'f745fdc44910b728d3dbb6677cbf8c2cebf12a84c777073e64ee11ace3a4a94a', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:13:39', '2026-10-15 21:13:39'),
('99', '1006', '6', '30559a5a9cb6514402723cd3951514e515748b4e0ed42ee79c83bfe112d65102', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:14:48', '2026-10-15 21:14:48'),
('105', '1006', '6', '82605dd281d23de3683cfbfd288409ff9ef24267f86cda819562c8bd896dd051', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:14:58', '2026-10-15 21:14:58'),
('132', '1006', '6', '50442ad1905d24d5057dc0e2bba553a00962acd50302ea5aae67c4ac0bed8954', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:16:24', '2026-10-15 21:16:24'),
('133', '1006', '6', 'afef88412f82a88c3402a3c11cdb35fc96863712899127c7203a3214df4651d2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-15 21:16:34', '2026-10-16 01:36:20'),
('418', '1007', '7', '3a66cfd01bb5b7ecd1f25851c0555fc3a4411cf5519a531252404380f1d11f48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 01:41:55', '2026-10-16 01:41:55'),
('429', '1007', '6', 'a8b697a128128f11dff91af5ea7bdb3c3e1279fa4dd561b8fb76163bd8e48663', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-16 01:42:17', '2026-10-16 03:10:04'),
('489', '1007', '7', '8de14ebc0081b9926e2ee6cac028b0c57778864e67d2341e834b1f8aa7dc1610', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '0', '2026-10-16 02:38:40', '2026-10-16 03:12:54'),
('534', '1007', '7', '4bd2e8c000f6dd48f37d561989b0df7186c90fa6677ac5d1fe609b1a802f54cc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 13:34:17', '2026-10-16 18:24:54'),
('541', '1018', NULL, '89a58ca8000bb91282eabbd21955f691689b13186fd060d00123d3b66c0d046f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-16 13:41:57', '2026-10-16 18:34:23'),
('756', '1007', '7', '39702a47e321dad07b3dd4b28d6ac9ae01a218cce61bdbcb5bce0c4680807890', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 18:25:50', '2026-10-16 18:25:50'),
('758', '1006', '6', 'a014b7a7bd94f2b456c5a002d33b19a78d2bc228f110105e60033fc2a1fed1f5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 18:25:57', '2026-10-16 19:44:20'),
('775', '1007', NULL, '7c8c8a44a8c4162142498316c769037be00b2573f4fc94986da9bb37e43e2cf4', '127.0.0.1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-16 19:11:53', '2026-10-16 19:11:53'),
('776', '1009', NULL, '1d8f6fac6ac5e504d0df89c92405a696df502c420925db9b98a47507ebbe98c1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.179 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36', 'Chrome on Windows', '0', '2026-10-16 19:12:18', '2026-10-16 19:23:18'),
('788', '1009', NULL, 'd838bbe733ec6e548212ea367e754808984aef28951a2e247f1869215c7255d7', '127.0.0.1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-16 19:23:48', '2026-10-16 19:23:48'),
('790', '1009', NULL, '219952ba1b891ce286564dd10021e6103e97f0b4097666747954831f28162782', '127.0.0.1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-16 19:44:11', '2026-10-16 19:44:11'),
('791', '1007', NULL, '730a514261e38af0cdc4d0701df72b84fd7703a03993caafe4d8dee06bf2a418', '127.0.0.1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-16 19:44:11', '2026-10-16 19:44:11'),
('794', '1007', '7', '98ae5d2cf8982dc2534f78110e635545f8c5ed340362169683fa810928d717bf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 19:44:26', '2026-10-16 19:44:26'),
('799', '1007', '7', 'ffbb1a0b47e8faa0e7ed47b2294798fbc372532fe26403ce23f4e47baa61f254', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 19:44:50', '2026-10-16 19:44:50'),
('801', '1006', '6', 'b39dba12c4e1465a98092e2dbe7cc911a53c09204ef7dac56e8f007e122877fd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '1', '2026-10-16 19:44:54', '2026-10-16 20:23:59'),
('807', '1007', '7', 'eb6a7574799639733d0ca7c7a40d47e28b5791c713a5bbb61a07233a0190da06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '0', '2026-10-16 20:24:17', '2026-10-16 20:24:17'),
('825', '1007', '7', '284e430fba577ed9c78500497a15233f275bad4e0c90d394321d3eafcd510aae', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '0', '2026-10-17 01:08:24', '2026-10-17 02:45:31'),
('831', '1007', NULL, '4453f1f8ffdd9c33401d0327905af590d8940d34987ba129e859efaf331cc39c', '::1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-17 01:30:22', '2026-10-17 01:32:24'),
('832', '1007', NULL, 'c4378b3eb56cb8b38097b34ae5564f56ed4d5628c41bd569399b42b8c0d87166', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Freebuff/0.0.179 Chrome/130.0.6723.191 Electron/33.4.11 Safari/537.36', 'Chrome on Windows', '0', '2026-10-17 01:30:51', '2026-10-17 02:33:49'),
('837', '1006', NULL, '82540fa462e33dbfd1b3b7acf382bcbad71fb598c9e62c90953508887e363ef5', '::1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-17 01:40:56', '2026-10-17 01:40:56'),
('840', '1007', NULL, '7295054adb56b9736c90c0bf43472455fee0654aa41e83cc47f5aff2337316da', '::1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-17 01:40:58', '2026-10-17 01:43:42'),
('848', '1001', NULL, '9b9eaac78d53960f9f054455a7fb0d6292939853f97550ca5027cb4f59d0a21e', '::1', 'curl/8.21.0', 'Browser on Unknown OS', '0', '2026-10-17 01:45:49', '2026-10-17 01:45:49'),
('860', '1007', '7', '9c83a53d3b42ae38d5b46a51aebc1f6eb6d10e308a614a654e8401c0f1ce0a79', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Chrome on Windows', '0', '2026-10-17 11:01:19', '2026-10-17 16:02:40'),
('862', '1007', NULL, 'f543e92656f9db944cc93cbfee0578fc9d37607e909dfc5d4b3423ddbc80de3b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.0.29 Chrome/152.0.7977.130 Electron/44.4.5 Safari/537.36', 'Chrome on Windows', '0', '2026-10-17 11:01:58', '2026-10-17 16:13:52');

-- ld_video_conference  (15 rows)
DELETE FROM `ld_video_conference`;
INSERT INTO `ld_video_conference` (`id`, `instructor_id`, `course_id`, `program_id`, `title`, `platform`, `meeting_link`, `scheduled_at`, `duration_minutes`, `status`, `first_reminder_sent`, `second_reminder_sent`, `created_at`, `updated_at`) VALUES
('1', '1007', NULL, NULL, 'Toast verification', 'google_meet', 'https://example.com/meeting', '2030-01-01 10:00:00', '60', 'scheduled', '0', '0', '2026-10-09 12:08:36', '2026-10-09 12:12:21'),
('2', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:38', '2026-10-16 16:59:34'),
('3', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:42', '2026-10-16 16:59:34'),
('4', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:42', '2026-10-16 16:59:34'),
('5', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:42', '2026-10-16 16:59:34'),
('6', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:42', '2026-10-16 16:59:34'),
('7', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:42', '2026-10-16 16:59:34'),
('8', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:43', '2026-10-16 16:59:34'),
('9', '1007', '6', NULL, 'sdsada', 'google_meet', 'https://dsadas.com', '2026-10-16 12:08:00', '60', 'completed', '0', '0', '2026-10-09 12:08:43', '2026-10-16 16:59:34'),
('10', '1001', NULL, '4', 'Leadership Essentials Live Workshop', 'zoom', 'https://zoom.us/j/91234567890', '2026-11-05 09:00:00', '120', 'scheduled', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('11', '1001', NULL, '6', 'Customer Service Bootcamp - Session 1', 'google_meet', 'https://meet.google.com/klm-nopq-rst', '2026-10-06 14:00:00', '150', 'completed', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('12', '1001', '8', NULL, 'Effective Communication Skills Clinic', 'zoom', 'https://zoom.us/j/93456789012', '2026-11-18 15:00:00', '75', 'scheduled', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('13', '1001', '9', NULL, 'Excel Data Analysis Live Lab', 'google_meet', 'https://meet.google.com/abc-defg-hij', '2026-11-10 13:30:00', '90', 'scheduled', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('14', '1001', '12', NULL, 'Cybersecurity Awareness Webinar', 'zoom', 'https://zoom.us/j/92345678901', '2026-11-12 10:00:00', '60', 'scheduled', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54'),
('15', '1001', NULL, NULL, 'New Hire Orientation Live Session', 'other', 'https://teams.microsoft.com/l/meetup-join/new-hire-orientation', '2026-11-03 08:30:00', '90', 'scheduled', '0', '0', '2026-10-15 21:24:54', '2026-10-15 21:24:54');

SET SESSION sql_mode = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;
