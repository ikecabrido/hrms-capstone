ALTER TABLE `eer_employee_badges`
  MODIFY COLUMN `eer_employee_badge_id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `eer_recognition_cycle_awards` (
  `recognition_cycle_award_id` int(11) NOT NULL AUTO_INCREMENT,
  `month_year` varchar(7) NOT NULL,
  `award_history_id` int(11) NOT NULL,
  `recognition_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `performance_report_id` int(11) DEFAULT NULL,
  `badge_id` int(11) DEFAULT NULL,
  `reward_id` int(11) DEFAULT NULL,
  `award_level` enum('employee_of_month','outstanding_employee') NOT NULL,
  `reward_status` enum('not_assigned','pending','fulfilled') NOT NULL DEFAULT 'not_assigned',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `fulfilled_by` int(11) DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `employee_badge_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`recognition_cycle_award_id`),
  UNIQUE KEY `uq_recognition_cycle_award_history` (`award_history_id`),
  UNIQUE KEY `uq_recognition_cycle_employee` (`month_year`,`employee_id`),
  KEY `idx_recognition_cycle_reward_status` (`month_year`,`reward_status`),
  KEY `idx_recognition_cycle_employee_badge` (`employee_badge_id`),
  CONSTRAINT `fk_recognition_cycle_awards_cycle`
    FOREIGN KEY (`month_year`) REFERENCES `eer_recognition_cycles` (`month_year`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_history`
    FOREIGN KEY (`award_history_id`) REFERENCES `eer_award_history` (`eer_award_history_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_recognition`
    FOREIGN KEY (`recognition_id`) REFERENCES `eer_recognitions` (`eer_recognition_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_badge`
    FOREIGN KEY (`badge_id`) REFERENCES `eer_badges` (`eer_badge_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_employee_badge`
    FOREIGN KEY (`employee_badge_id`) REFERENCES `eer_employee_badges` (`eer_employee_badge_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_reward`
    FOREIGN KEY (`reward_id`) REFERENCES `eer_rewards` (`eer_reward_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recognition_cycle_awards_fulfilled_by`
    FOREIGN KEY (`fulfilled_by`) REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `eer_recognition_cycle_awards`
  MODIFY COLUMN `reward_status` enum('not_assigned','pending','fulfilled')
    NOT NULL DEFAULT 'not_assigned';

SET @employee_badge_link_ddl = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `eer_recognition_cycle_awards`
       ADD COLUMN `employee_badge_id` int(11) DEFAULT NULL AFTER `fulfilled_at`,
       ADD KEY `idx_recognition_cycle_employee_badge` (`employee_badge_id`),
       ADD CONSTRAINT `fk_recognition_cycle_awards_employee_badge`
         FOREIGN KEY (`employee_badge_id`) REFERENCES `eer_employee_badges` (`eer_employee_badge_id`)
         ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'eer_recognition_cycle_awards'
    AND COLUMN_NAME = 'employee_badge_id'
);
PREPARE employee_badge_link_stmt FROM @employee_badge_link_ddl;
EXECUTE employee_badge_link_stmt;
DEALLOCATE PREPARE employee_badge_link_stmt;
