-- Add knowledge transfer support to ld_learning_path
-- Run this migration to add the new columns

ALTER TABLE `ld_learning_path`
    ADD COLUMN `type` ENUM('standard','knowledge_transfer') NOT NULL DEFAULT 'standard' AFTER `status`,
    ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`,
    ADD COLUMN `kt_plan_id` INT UNSIGNED NULL AFTER `is_public`;

-- Optional: add index for catalog queries
ALTER TABLE `ld_learning_path`
    ADD INDEX `idx_lp_type_public` (`type`, `is_public`, `status`);
