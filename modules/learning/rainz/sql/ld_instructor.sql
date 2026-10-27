-- =======================================================================
--  ld_instructor — Learning & Development instructor roster
-- =======================================================================
--  Import-safe: CREATE TABLE IF NOT EXISTS, no data touched.
--
--      mysql -u root hrms < "ld_instructor.sql"
--
--  WHY THIS TABLE EXISTS
--    Until now an employee was an instructor only if a course, learning
--    path, program or video conference named them as its instructor. A
--    brand-new instructor therefore had no access until they were given
--    something to teach. This table is the explicit designation: put an
--    employee id here and Page::isInstructor() treats them as an instructor
--    straight away, before any class exists.
--
--    The rule Page::roleForAccount() applies is: teaching assignment OR a
--    row here => instructor. Holding the L&D staff role is neither.
--
--  HOW TO USE
--    Designate:   php modules/learning/cron/designate-instructor.php <employee_id>
--    Remove:      php modules/learning/cron/designate-instructor.php <employee_id> --remove
--    List:        php modules/learning/cron/designate-instructor.php --list
--    (The tool creates this table if it is missing, so importing this file
--     by hand is optional.)
-- =======================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ld_instructor` (
  `employee_id` int(10) unsigned NOT NULL,
  `designated_by` int(10) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `designated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
