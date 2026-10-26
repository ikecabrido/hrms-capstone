-- =======================================================================
-- Learning programs: delivery, schedule, trainers, trainees, assessments
-- =======================================================================
-- Adds the columns and ld_* tables used by the program builder and the
-- trainer evaluation page. Apply this file once with:
--
--   Admin > Moderation > Export LD Database > Apply schema file
--
-- The columns are added to the existing ld_program table; the new tables are
-- safe to create only once. Existing program rows keep their current title,
-- description, status and owner.
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `ld_program`
  ADD COLUMN `delivery_mode` enum('face_to_face','online') NOT NULL DEFAULT 'face_to_face' AFTER `description`,
  ADD COLUMN `starts_at` datetime DEFAULT NULL AFTER `delivery_mode`,
  ADD COLUMN `ends_at` datetime DEFAULT NULL AFTER `starts_at`,
  ADD COLUMN `expires_at` datetime DEFAULT NULL AFTER `ends_at`,
  ADD COLUMN `location` varchar(255) DEFAULT NULL AFTER `expires_at`,
  ADD COLUMN `parent_program_id` int(10) unsigned DEFAULT NULL AFTER `location`,
  ADD COLUMN `run_number` int(10) unsigned NOT NULL DEFAULT 1 AFTER `parent_program_id`,
  ADD KEY `idx_ld_program_parent` (`parent_program_id`),
  ADD KEY `idx_ld_program_schedule` (`starts_at`, `expires_at`),
  ADD CONSTRAINT `fk_ld_program_parent` FOREIGN KEY (`parent_program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `ld_program_skill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prog_skill` (`program_id`,`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_program_course` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_program_course` (`program_id`,`course_id`),
  KEY `idx_program_course_course` (`course_id`),
  CONSTRAINT `fk_program_course_program` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_program_course_course` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_program_trainer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_program_trainer` (`program_id`,`trainer_id`),
  KEY `idx_program_trainer_trainer` (`trainer_id`),
  CONSTRAINT `fk_program_trainer_program` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_program_trainer_employee` FOREIGN KEY (`trainer_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_program_participant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `employee_id` int(11) NOT NULL,
  `status` enum('invited','enrolled','completed','withdrawn') NOT NULL DEFAULT 'enrolled',
  `added_by` int(11) DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_program_participant` (`program_id`,`employee_id`),
  KEY `idx_program_participant_employee` (`employee_id`),
  CONSTRAINT `fk_program_participant_program` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_program_participant_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_program_assessment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `employee_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `attendance` enum('present','late','absent','excused') NOT NULL DEFAULT 'present',
  `knowledge_score` tinyint(3) unsigned DEFAULT NULL,
  `skills_score` tinyint(3) unsigned DEFAULT NULL,
  `participation_score` tinyint(3) unsigned DEFAULT NULL,
  `overall_score` decimal(3,2) DEFAULT NULL,
  `result` enum('pending','passed','failed') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','completed') NOT NULL DEFAULT 'draft',
  `assessed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_program_assessment` (`program_id`,`employee_id`),
  KEY `idx_program_assessment_employee` (`employee_id`),
  KEY `idx_program_assessment_trainer` (`trainer_id`),
  KEY `idx_program_assessment_result` (`result`, `status`),
  CONSTRAINT `fk_program_assessment_program` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_program_assessment_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_program_assessment_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
