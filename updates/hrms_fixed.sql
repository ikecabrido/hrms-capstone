CREATE DATABASE IF NOT EXISTS `hrms`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `hrms`;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `user_account`;
DROP TABLE IF EXISTS `hrms_employee`;
DROP TABLE IF EXISTS `hrms_roles`;
DROP TABLE IF EXISTS `hrms_position`;
DROP TABLE IF EXISTS `hrms_department`;

CREATE TABLE `hrms_department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) DEFAULT NULL,
  `department_head` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  KEY `idx_hrms_department_head` (`department_head`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `hrms_department`
(`department_id`,`department_name`,`department_head`) VALUES
(15,'Recruitment',NULL),
(16,'Employee Management',NULL),
(17,'Payroll',NULL),
(18,'Time and Attendance',NULL),
(19,'Performance',NULL),
(20,'Learning',NULL),
(21,'Compliance',NULL),
(22,'Exit',NULL),
(23,'Clinic',NULL),
(24,'Workforce',NULL);

CREATE TABLE `hrms_position` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `position_name` varchar(100) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  PRIMARY KEY (`position_id`),
  KEY `idx_hrms_position_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `hrms_position`
(`position_id`,`position_name`,`department`) VALUES
(42,'Recruiter',15);

CREATE TABLE `hrms_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  KEY `idx_hrms_roles_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `hrms_roles`
(`role_id`,`role_name`,`department`) VALUES
(1,'Recruitment',15),
(2,'Employee',16),
(3,'Payroll',17),
(4,'Time',18),
(5,'Peformance',19),
(6,'Learning',20),
(7,'Compliance',21),
(8,'Workforce',24),
(9,'Exit',22);

CREATE TABLE `hrms_employee` (
  `employee_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  KEY `idx_hrms_employee_user` (`user_id`),
  KEY `idx_hrms_employee_role` (`role`),
  KEY `idx_hrms_employee_department` (`department`),
  KEY `idx_hrms_employee_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `hrms_employee`
(`employee_id`,`first_name`,`middle_name`,`last_name`,`role`,`status`,`date_hired`,`user_id`,`department`,`position`) VALUES
(1012,'Jhon Carlo',NULL,'Garcia',1,'active','2026-08-08',NULL,15,42);

CREATE TABLE `user_account` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(255) NOT NULL,
  `employee_id` bigint(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `remember_token` int(1) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_account_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_account`
(`user_id`,`password`,`employee_id`,`email`,`created_at`,`remember_token`) VALUES
(20,'admin123',1012,NULL,'2026-08-08 20:48:40',NULL);

-- Add the foreign keys only after all referenced tables exist.
ALTER TABLE `hrms_position`
  ADD CONSTRAINT `fk_hrms_position_department`
  FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `hrms_roles`
  ADD CONSTRAINT `fk_hrms_roles_department`
  FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `hrms_employee`
  ADD CONSTRAINT `fk_hrms_employee_department`
    FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_position`
    FOREIGN KEY (`position`) REFERENCES `hrms_position` (`position_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_role`
    FOREIGN KEY (`role`) REFERENCES `hrms_roles` (`role_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_user`
    FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_account`
  ADD CONSTRAINT `fk_user_account_employee`
  FOREIGN KEY (`employee_id`) REFERENCES `hrms_employee` (`employee_id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `hrms_department`
  ADD CONSTRAINT `fk_hrms_department_head`
  FOREIGN KEY (`department_head`) REFERENCES `hrms_employee` (`employee_id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
