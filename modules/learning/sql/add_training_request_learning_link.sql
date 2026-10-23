-- Link a Workforce training action to the course or learning path that answers it.
--
-- ld_training_requests.course_id was declared in create_training_requests.sql but is
-- missing from databases created before that column existed; learning_path_id is new.
-- The Workforce Inbox (pages/admin/workforce-integration.php) writes exactly one of
-- the two, and only for rows where source_module = 'workforce'.
ALTER TABLE `ld_training_requests`
    ADD COLUMN `course_id` INT(10) UNSIGNED NULL AFTER `skill_name`,
    ADD COLUMN `learning_path_id` INT(10) UNSIGNED NULL AFTER `course_id`;

ALTER TABLE `ld_training_requests`
    ADD CONSTRAINT `ld_training_requests_course_fk` FOREIGN KEY (`course_id`)
        REFERENCES `ld_course`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `ld_training_requests_path_fk` FOREIGN KEY (`learning_path_id`)
        REFERENCES `ld_learning_path`(`id`) ON DELETE SET NULL;
