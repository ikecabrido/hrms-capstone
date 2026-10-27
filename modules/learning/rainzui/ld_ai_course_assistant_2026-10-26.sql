-- =======================================================================
-- AI Course Assistant: jobs, provider configuration, and audit tables
-- =======================================================================
-- Adds only new ld_ai_* tables. Existing course/content tables are not
-- altered. Apply this file once. The CREATE TABLE statements use
-- IF NOT EXISTS, but the migration should not be re-run blindly after the
-- code starts writing provider keys or generation jobs.
--
-- Tables:
--   ld_ai_course_job         one admin generation request
--   ld_ai_course_job_step    outline/module/evaluation/quality steps
--   ld_ai_api_key            encrypted provider key metadata
--   ld_ai_model              enabled/default/fallback model catalog
--   ld_ai_setting            non-secret admin settings
--   ld_ai_prompt_template    versioned prompt templates
--   ld_ai_audit_log          security and configuration audit trail
--
-- Provider keys are stored as ciphertext plus an HMAC fingerprint and the
-- last four characters. Never add a plaintext key column.
-- =======================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `ld_ai_course_job` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int(11) DEFAULT NULL,
  `course_owner_id` int(11) DEFAULT NULL,
  `learner_id` int(11) DEFAULT NULL,
  `course_id` int(10) unsigned DEFAULT NULL,
  `provider` varchar(40) NOT NULL DEFAULT 'openrouter',
  `model` varchar(160) NOT NULL,
  `prompt_version` varchar(30) NOT NULL DEFAULT 'v1',
  `status` enum('draft','generating','ready','applied','failed','cancelled') NOT NULL DEFAULT 'draft',
  `current_step` varchar(80) DEFAULT NULL,
  `total_steps` smallint(5) unsigned NOT NULL DEFAULT 0,
  `completed_steps` smallint(5) unsigned NOT NULL DEFAULT 0,
  `brief_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`brief_json`)),
  `context_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`context_json`)),
  `blueprint_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`blueprint_json`)),
  `prompt_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `completion_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `estimated_cost_usd` decimal(12,6) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `applied_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_job_creator` (`created_by`,`created_at`),
  KEY `idx_ai_job_owner` (`course_owner_id`),
  KEY `idx_ai_job_learner` (`learner_id`),
  KEY `idx_ai_job_status` (`status`),
  KEY `idx_ai_job_course` (`course_id`),
  KEY `idx_ai_job_created` (`created_at`),
  CONSTRAINT `fk_ai_job_creator`
    FOREIGN KEY (`created_by`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ai_job_owner`
    FOREIGN KEY (`course_owner_id`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ai_job_learner`
    FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ai_job_course`
    FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_course_job_step` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint(20) unsigned NOT NULL,
  `step_key` varchar(80) NOT NULL,
  `step_type` enum('outline','module','evaluation','quality') NOT NULL,
  `status` enum('pending','running','done','failed','skipped') NOT NULL DEFAULT 'pending',
  `attempt_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `request_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`request_json`)),
  `response_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`response_json`)),
  `prompt_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `completion_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `estimated_cost_usd` decimal(12,6) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_job_step` (`job_id`,`step_key`),
  KEY `idx_ai_job_step_status` (`job_id`,`status`),
  KEY `idx_ai_job_step_type` (`job_id`,`step_type`),
  CONSTRAINT `fk_ai_job_step_job`
    FOREIGN KEY (`job_id`) REFERENCES `ld_ai_course_job` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_api_key` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(40) NOT NULL DEFAULT 'openrouter',
  `label` varchar(100) NOT NULL,
  `key_ciphertext` varbinary(2048) NOT NULL,
  `key_nonce` varbinary(64) NOT NULL,
  `key_algorithm` varchar(30) NOT NULL DEFAULT 'aes-256-gcm',
  `key_fingerprint` char(64) NOT NULL,
  `key_last4` char(4) NOT NULL,
  `status` enum('active','disabled','revoked') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `last_tested_at` datetime DEFAULT NULL,
  `last_test_status` enum('never','success','failed') NOT NULL DEFAULT 'never',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_key_fingerprint` (`provider`,`key_fingerprint`),
  KEY `idx_ai_key_status` (`provider`,`status`),
  CONSTRAINT `fk_ai_key_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(40) NOT NULL DEFAULT 'openrouter',
  `model_slug` varchar(160) NOT NULL,
  `display_name` varchar(160) NOT NULL,
  `context_length` int(10) unsigned DEFAULT NULL,
  `prompt_price` decimal(18,12) NOT NULL DEFAULT 0,
  `completion_price` decimal(18,12) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_fallback` tinyint(1) NOT NULL DEFAULT 0,
  `last_synced_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_model` (`provider`,`model_slug`),
  KEY `idx_ai_model_enabled` (`provider`,`enabled`),
  KEY `idx_ai_model_default` (`provider`,`is_default`),
  KEY `idx_ai_model_fallback` (`provider`,`is_fallback`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_setting` (`setting_key`),
  CONSTRAINT `fk_ai_setting_updated_by`
    FOREIGN KEY (`updated_by`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_prompt_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prompt_key` varchar(80) NOT NULL,
  `version` varchar(30) NOT NULL,
  `name` varchar(160) NOT NULL,
  `content` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_prompt_version` (`prompt_key`,`version`),
  KEY `idx_ai_prompt_active` (`prompt_key`,`is_active`),
  CONSTRAINT `fk_ai_prompt_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ld_ai_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`metadata_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ai_audit_employee` (`employee_id`,`created_at`),
  KEY `idx_ai_audit_action` (`action`,`created_at`),
  CONSTRAINT `fk_ai_audit_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
