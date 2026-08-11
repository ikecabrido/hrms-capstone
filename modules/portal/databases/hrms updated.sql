-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2026 at 07:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hrms`
--

-- --------------------------------------------------------

--
-- Table structure for table `hrms_department`
--

CREATE TABLE `hrms_department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `department_head` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hrms_department`
--

INSERT INTO `hrms_department` (`department_id`, `department_name`, `department_head`) VALUES
(15, 'Recruitment', NULL),
(16, 'Employee Management', NULL),
(17, 'Payroll', NULL),
(18, 'Time and Attendance', NULL),
(19, 'Performance', NULL),
(20, 'Learning', NULL),
(21, 'Compliance', NULL),
(22, 'Exit', NULL),
(23, 'Clinic', NULL),
(24, 'Workforce', NULL),
(25, 'Employee Engagement', NULL),
(26, 'Portal', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hrms_employee`
--

CREATE TABLE `hrms_employee` (
  `employee_id` bigint(20) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hrms_employee`
--

INSERT INTO `hrms_employee` (`employee_id`, `first_name`, `middle_name`, `last_name`, `role`, `status`, `date_hired`, `user_id`, `department`, `position`) VALUES
(1012, 'Jhon Carlo', NULL, 'Garcia', 1, 'active', '2026-08-08', 21, 15, 42),
(1013, 'Russel', 'Gohetia', 'Cabrido', 3, 'active', '2026-08-08', 22, 17, 43),
(1014, 'Jose Mari Rich', NULL, 'Malana', 4, 'active', '2026-08-09', 23, 18, 50),
(1015, 'Russell', NULL, 'Placer', 2, 'active', '2026-08-09', 24, 16, 44),
(1016, 'Cheska', NULL, 'Baustita', 7, 'active', '2026-08-09', 25, 21, 45),
(1017, 'Jayson', NULL, 'Paigma', 8, 'active', '2026-08-09', 26, 24, 46),
(1018, 'Rainiel', NULL, 'Quebada', 6, 'active', '2026-08-09', 27, 20, 47),
(1019, 'Karl', NULL, 'Solis', 5, 'active', '2026-08-09', 28, 19, 48),
(1020, 'Geoffrey', NULL, 'Balansag', 11, 'active', '2026-08-09', 29, 25, 51),
(1021, 'Johnloyd', NULL, 'Reyes', 9, 'active', '2026-08-09', 30, 22, 49),
(1022, 'Alexis', NULL, 'Cueto', 10, 'active', '2026-08-09', 31, 23, 52),
(1023, 'Robert',NULL,'Campos',12,'active','2026-08-09', 32,26,53);

-- --------------------------------------------------------

--
-- Table structure for table `hrms_position`
--

CREATE TABLE `hrms_position` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) DEFAULT NULL,
  `department` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hrms_position`
--

INSERT INTO `hrms_position` (`position_id`, `position_name`, `department`) VALUES
(42, 'Recruiter', 15),
(43, 'Payroll', 17),
(44, 'Employee Management', 16),
(45, 'Legal Compliance', 21),
(46, 'Workforce and Analytics', 24),
(47, 'Learning and Development', 20),
(48, 'Performance Management', 19),
(49, 'Exit Management', 22),
(50, 'Time and Attendance', 18),
(51, 'Employee Engagement', 25),
(52, 'Clinic', 23),
(53,'Portal',26);

-- --------------------------------------------------------

--
-- Table structure for table `hrms_roles`
--

CREATE TABLE `hrms_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `department` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hrms_roles`
--

INSERT INTO `hrms_roles` (`role_id`, `role_name`, `department`) VALUES
(1, 'Recruitment', 15),
(2, 'Employee', 16),
(3, 'Payroll', 17),
(4, 'Time', 18),
(5, 'Performance', 19),
(6, 'Learning', 20),
(7, 'Compliance', 21),
(8, 'Workforce', 24),
(9, 'Exit', 22),
(10, 'Clinic', 23),
(11, 'Employee Engagement', 25),
(12, 'Portal', 26);

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

CREATE TABLE `user_account` (
  `user_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` bigint(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `remember_token` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`user_id`, `password`, `employee_id`, `email`, `created_at`, `remember_token`) VALUES
(21, '$2y$10$q5/aXH6szEDXVM1rrAgd..BpDjNQCNfjv/bJc/xRR/iWRiLIDypfq', 1012, NULL, '2026-08-09 02:50:23', NULL),
(22, '$2y$10$dkxxQqySBw.Xj7ynHFVUTugipnIcOsSw9xW9Bkmu1mD6BON74xEJ2', 1013, NULL, '2026-08-08 12:48:40', NULL),
(23, '$2y$10$E6IfBsx8oYjxiESpMwsiJeIX83m5mRZXW0REze1.YWGohDK5j2Wm.', 1014, NULL, '2026-08-09 05:11:32', NULL),
(24, '$2y$10$xO/C3.Yk4zkdzaN/iEyxSu2ewtzq6.gJj25pAV3URbF4hD8NY7Fke', 1015, NULL, '2026-08-09 05:24:56', NULL),
(25, '$2y$10$8yp86m7jO2S8NPM3xTMuyuobAAb4NGsoLxtmv4jpJly2bWcjaYSJi', 1016, NULL, '2026-08-09 05:25:51', NULL),
(26, '$2y$10$ridpEYIFsgjdYqFI3VTKF.MjSf8GX1H.XrzE/4UxmwWQXkJWHbkh6', 1017, NULL, '2026-08-09 05:26:25', NULL),
(27, '$2y$10$pZWeY6ODmnwb4VJuxQiCzOMBL4E42/iqg1Xc1tfCQ3IGFdhJ4O112', 1018, NULL, '2026-08-09 05:28:23', NULL),
(28, '$2y$10$aQ9sEVmIz.cbA5j5eYEIT.r2lCUkfrd6I8X.LY/BLkuo/TUK1Mce6', 1019, NULL, '2026-08-09 05:29:00', NULL),
(29, '$2y$10$88ez1flvZDHzMKv/jQdxE.HbveLfEt6x5fEDGjfX/T0GEqPT8iN0G', 1020, NULL, '2026-08-09 05:29:45', NULL),
(30, '$2y$10$DQB1tiH4sQ2RE7yWxOcz3OLUwoKvO6SghNeaDmT0.1nfJpJv/zJWG', 1021, NULL, '2026-08-09 05:30:45', NULL),
(31, '$2y$10$Htc.AaV0g3yW1hrOtux6fu1oOXiGgxz5WctWPfE/CjccN3EPwtYlG', 1022, NULL, '2026-08-09 05:31:06', NULL),
(32, '$2y$10$LscHpCGVKkgXBbqYCp4wuuLq3nWpsnV./3mR6n7LcHnLnBBaFZCGm',1023,NULL,'2026-08-09 05:31:06',NULL);
--
-- Indexes for dumped tables
--

--
-- Indexes for table `hrms_department`
--
ALTER TABLE `hrms_department`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `idx_hrms_department_head` (`department_head`);

--
-- Indexes for table `hrms_employee`
--
ALTER TABLE `hrms_employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `idx_hrms_employee_user` (`user_id`),
  ADD KEY `idx_hrms_employee_role` (`role`),
  ADD KEY `idx_hrms_employee_department` (`department`),
  ADD KEY `idx_hrms_employee_position` (`position`);

--
-- Indexes for table `hrms_position`
--
ALTER TABLE `hrms_position`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `idx_hrms_position_department` (`department`);

--
-- Indexes for table `hrms_roles`
--
ALTER TABLE `hrms_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD KEY `idx_hrms_roles_department` (`department`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_user_account_employee` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hrms_department`
--
ALTER TABLE `hrms_department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `hrms_employee`
--
ALTER TABLE `hrms_employee`
  MODIFY `employee_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1023;

--
-- AUTO_INCREMENT for table `hrms_position`
--
ALTER TABLE `hrms_position`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `hrms_roles`
--
ALTER TABLE `hrms_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hrms_department`
--
ALTER TABLE `hrms_department`
  ADD CONSTRAINT `fk_hrms_department_head` FOREIGN KEY (`department_head`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `hrms_employee`
--
ALTER TABLE `hrms_employee`
  ADD CONSTRAINT `fk_hrms_employee_department` FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_position` FOREIGN KEY (`position`) REFERENCES `hrms_position` (`position_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_role` FOREIGN KEY (`role`) REFERENCES `hrms_roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrms_employee_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `hrms_position`
--
ALTER TABLE `hrms_position`
  ADD CONSTRAINT `fk_hrms_position_department` FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `hrms_roles`
--
ALTER TABLE `hrms_roles`
  ADD CONSTRAINT `fk_hrms_roles_department` FOREIGN KEY (`department`) REFERENCES `hrms_department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_account`
--
ALTER TABLE `user_account`
  ADD CONSTRAINT `fk_user_account_employee` FOREIGN KEY (`employee_id`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
