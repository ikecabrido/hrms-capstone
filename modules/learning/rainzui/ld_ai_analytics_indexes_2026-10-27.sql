-- AI Assistant analytics index (2026-10-27)
--
-- The base AI schema already indexes created_at and status. This optional
-- follow-up index supports the model-usage breakdown added in P10 without
-- changing the reviewed one-time schema migration.
--
-- Run once on the Learning database. Do not re-run blindly if the index exists.

ALTER TABLE `ld_ai_course_job`
  ADD INDEX `idx_ai_job_model_created` (`model`, `created_at`);
