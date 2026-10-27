-- Store the Font Awesome icon selected for each skill.
-- Apply once to every Learning database before deploying the updated module.
ALTER TABLE `ld_skill`
    ADD COLUMN `icon` varchar(64) NOT NULL DEFAULT 'fas fa-star' AFTER `description`;
