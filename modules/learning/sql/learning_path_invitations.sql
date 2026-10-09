USE `payr_bcp`;

ALTER TABLE `ld_learning_path`
    ADD COLUMN IF NOT EXISTS `type` enum('standard','knowledge_transfer') NOT NULL DEFAULT 'standard',
    ADD COLUMN IF NOT EXISTS `is_public` tinyint(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `kt_plan_id` int(10) unsigned DEFAULT NULL,
    ADD KEY IF NOT EXISTS `idx_ld_learning_path_visibility` (`status`,`is_public`),
    ADD KEY IF NOT EXISTS `idx_ld_learning_path_kt_plan` (`kt_plan_id`);

ALTER TABLE `ld_learning_path`
    MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `ld_learning_path_invitation` (
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
    CONSTRAINT `fk_ld_path_invitation_path`
        FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;