-- ============================================================================
-- L&D ↔ Workforce Analytics integration tables
-- Implements: modules/learning/rainz/integration mds/INTEGRATION_WORKFORCE_ANALYTICS.md
-- Run once:  mysql -u root hrms < workforce_integration.sql
--
-- Everything here is CREATE TABLE IF NOT EXISTS, so the file is safe to re-run
-- on a database where the tables already exist (including the workforce module's
-- own copy of ld_competency_requirement / ld_reporting_requirement).
--
-- Two of these tables are already written to directly by the Workforce Analytics
-- module, so their column list must stay byte-compatible with the copy shipped in
-- "hrmss workforce.sql":
--   * ld_competency_requirement  <- predicted skill / competency needs
--   * ld_reporting_requirement   <- reporting requirements / targets
-- The other two are the reception tables for Workforce-owned signals that had no
-- destination before:
--   * ld_training_engagement     <- training engagement records
--   * ld_turnover_risk           <- turnover-risk assessments
--
-- One table serves the opposite direction — follow-up raised in Learning:
--   * ld_training_requests       <- learner skill-gap requests + admin follow-up
--                                   raised from a Workforce-sent record
--
-- And one table records the scheduled jobs that keep this integration fresh:
--   * ld_cron_run                <- one row per engagement-snapshot run, so the
--                                   admin page can show the last run / next due
--                                   (classes/cron.php)
-- ============================================================================

-- --------------------------------------------------------
-- Predicted skill / competency needs (Workforce -> Learning)
-- Written by: modules/workforce/pages/predicted-skill-competency-needs.php
--             via Employee::saveCompetencyRequirement()
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_competency_requirement` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `skill_name` varchar(150) NOT NULL,
  `target_department` varchar(150) NOT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `employees_needing_training` int(10) UNSIGNED NOT NULL,
  `target_proficiency` varchar(50) NOT NULL,
  `expected_learning_output` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','planned','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_competency_status` (`status`),
  KEY `idx_competency_priority` (`priority_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Reporting requirements / targets (Workforce -> Learning)
-- Written by: modules/workforce/pages/implementation-guide.php and
--             modules/workforce/pages/custom-reports.php
--             via Employee::saveLearningReportingRequirement()
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_reporting_requirement` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `target_name` varchar(150) NOT NULL,
  `reporting_period_start` date NOT NULL,
  `reporting_period_end` date NOT NULL,
  `reporting_frequency` enum('weekly','monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
  `completion_target` decimal(5,2) NOT NULL,
  `expected_output` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','in_progress','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reporting_status` (`status`),
  KEY `idx_reporting_period` (`reporting_period_start`,`reporting_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Training engagement records (Workforce -> Learning)
-- Reception table for the "Record training engagement" input on the Workforce
-- turnover-risk indicator page, which previously had nowhere to save.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_training_engagement` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `learner_team_type` enum('individual-learner','department-team','role-group') NOT NULL DEFAULT 'individual-learner',
  `learner_id` int(10) UNSIGNED DEFAULT NULL,
  `learner_name` varchar(150) DEFAULT NULL,
  `department` varchar(150) NOT NULL,
  `training_activity` enum('course','learning-path','workshop','compliance-training','other') NOT NULL DEFAULT 'course',
  `engagement_status` enum('active','in-progress','low','inactive') NOT NULL DEFAULT 'active',
  `engagement_data` text DEFAULT NULL,
  `recommended_action` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','monitoring','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_engagement_ref` (`source_module`,`external_reference_id`),
  KEY `idx_engagement_status` (`status`),
  KEY `idx_engagement_signal` (`engagement_status`),
  KEY `idx_engagement_department` (`department`),
  KEY `idx_engagement_learner` (`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Turnover-risk assessments (Workforce -> Learning)
-- Workforce-assessed turnover risk per employee. Complements the Learning-computed
-- engagement/turnover-risk snapshot in ld_engagement_snapshot (cron-populated).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_turnover_risk` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_name` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `position_name` varchar(150) DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `risk_score` decimal(5,2) DEFAULT NULL,
  `risk_factors` text DEFAULT NULL,
  `recommended_action` text DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `status` enum('received','monitoring','mitigated','escalated') NOT NULL DEFAULT 'received',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_turnover_risk_ref` (`source_module`,`external_reference_id`),
  KEY `idx_turnover_risk_status` (`status`),
  KEY `idx_turnover_risk_level` (`risk_level`),
  KEY `idx_turnover_risk_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Training engagement / turnover-risk snapshot (Learning-computed)
-- Already present on an up-to-date install; created here so a fresh database can
-- render the integration pages. Populated by:
--   cron/materialize-engagement-snapshots.php
--   (and by api/inbound/receive-training-engagement.php for the "learning activity" band)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_engagement_snapshot` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `active_days_30` int(10) UNSIGNED DEFAULT 0,
  `courses_in_progress` int(10) UNSIGNED DEFAULT 0,
  `courses_completed_ytd` int(10) UNSIGNED DEFAULT 0,
  `avg_score` decimal(5,2) DEFAULT NULL,
  `quizzes_started` int(10) UNSIGNED DEFAULT 0,
  `quizzes_abandoned` int(10) UNSIGNED DEFAULT 0,
  `days_since_last_activity` int(10) UNSIGNED DEFAULT NULL,
  `expired_certificates` int(10) UNSIGNED DEFAULT 0,
  `risk_score` decimal(5,2) DEFAULT NULL,
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_factors`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_learner_snapshot` (`learner_id`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Training requests — the follow-up Learning raises against a received record.
--
-- Learner-raised rows were already written by three code paths that had no table
-- to write to (learner skill-gap request, instructor training-requests review,
-- instructor approve/reject), so the column list covers those:
--   pages/learner/ajax/request-training.php           INSERT (learner_id, skill_id, skill_name, message)
--   pages/instructor/training-requests.php            SELECT tr.*
--   pages/instructor/ajax/manage-training-request.php UPDATE instructor_id, instructor_note
-- Requests raised by an admin from the Workforce Integration page leave
-- learner_id NULL and carry target_department plus a "<dataset>:<id>" pointer in
-- external_reference_id back to the record they came from.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_training_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `learner_id` int(10) UNSIGNED DEFAULT NULL,
  `skill_id` int(10) UNSIGNED DEFAULT NULL,
  `skill_name` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `instructor_id` int(10) UNSIGNED DEFAULT NULL,
  `instructor_note` text DEFAULT NULL,
  `target_department` varchar(150) DEFAULT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
  `source_module` varchar(80) NOT NULL DEFAULT 'learning',
  `external_reference_id` varchar(120) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_training_request_status` (`status`),
  KEY `idx_training_request_learner` (`learner_id`),
  KEY `idx_training_request_skill` (`skill_id`),
  KEY `idx_training_request_department` (`target_department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Scheduler bookkeeping: one row per cron run.
-- Written by: modules/learning/classes/cron.php (LearningCron::run) — the CLI
--   wrappers in cron/ and the admin "run now" endpoint both go through it.
-- Read by:    WorkforceIntegrationView (the schedule / last-run panel),
--             cron/run-due.php (is a job due?) and pages/admin/workforce-integration.php.
-- Status 'running' with an old started_at means the process died: the next
-- LearningCron::run() marks it failed instead of blocking the job forever.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ld_cron_run` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job` varchar(80) NOT NULL,
  `trigger_type` enum('scheduled','manual','cli') NOT NULL DEFAULT 'scheduled',
  `status` enum('running','success','failed') NOT NULL DEFAULT 'running',
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_ms` int(10) UNSIGNED DEFAULT NULL,
  `records_processed` int(10) UNSIGNED DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cron_run_job` (`job`, `started_at`),
  KEY `idx_cron_run_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- API key for the Workforce Analytics module (inbound + outbound auth).
-- Matches the key seeded by build 2.23.sql; the INSERT is a no-op when present.
-- --------------------------------------------------------
INSERT INTO `ld_api_key` (`module_name`, `api_key`, `is_active`)
SELECT 'workforce-analytics',
       'wa_f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7',
       1
WHERE NOT EXISTS (
    SELECT 1 FROM `ld_api_key` WHERE `module_name` = 'workforce-analytics'
);
