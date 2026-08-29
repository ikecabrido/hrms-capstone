-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2026 at 01:01 PM
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
-- Database: `hrms-capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `eer_announcements`
--

CREATE TABLE `eer_announcements` (
  `eer_announcements_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `target_audience` varchar(100) DEFAULT 'all',
  `type` enum('announcement','recognition','department_update') DEFAULT 'announcement',
  `department` varchar(100) DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'normal',
  `category` varchar(100) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eer_announcements`
--

INSERT INTO `eer_announcements` (`eer_announcements_id`, `title`, `content`, `created_by`, `created_at`, `target_audience`, `type`, `department`, `priority`, `category`) VALUES
(1, 'Company Town Hall', 'Join tomorrow', 'EMP001', '2026-03-29 04:27:57', 'all', 'announcement', NULL, 'normal', 'general'),
(2, 'Clean-up Day', 'This Friday', 'EMP002', '2026-03-29 04:27:57', 'all', 'announcement', NULL, 'normal', 'general'),
(3, 'Company Town Hall', 'Join tomorrow', 'EMP009', '2026-03-29 12:36:01', 'all', 'announcement', NULL, 'normal', 'general');

-- --------------------------------------------------------

--
-- Table structure for table `eer_grievances`
--

CREATE TABLE `eer_grievances` (
  `eer_grievance_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','Resolved','Closed','Escalated') DEFAULT 'pending',
  `resolution_of_complaint` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `priority` enum('low','medium','high','urgent','critical') DEFAULT 'medium',
  `category` varchar(100) DEFAULT NULL,
  `anonymous` tinyint(1) DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL,
  `confidential` tinyint(1) DEFAULT 0,
  `action_taken` text DEFAULT NULL,
  `satisfaction_rating` int(1) DEFAULT NULL,
  `satisfaction_comment` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `escalation_level` varchar(50) DEFAULT NULL,
  `escalation_reason` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by_employee_id` int(11) DEFAULT NULL,
  `payslip_id` int(11) DEFAULT NULL,
  `gross_pay` decimal(10,2) DEFAULT NULL,
  `total_deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `payslip_information` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eer_grievances`
--

INSERT INTO `eer_grievances` (`eer_grievance_id`, `employee_id`, `subject`, `description`, `status`, `resolution_of_complaint`, `created_at`, `priority`, `category`, `anonymous`, `attachment_path`, `confidential`, `action_taken`, `satisfaction_rating`, `satisfaction_comment`, `resolved_at`, `escalation_level`, `escalation_reason`, `updated_at`, `created_by_employee_id`, `payslip_id`, `gross_pay`, `total_deductions`, `net_pay`, `payslip_information`) VALUES
(10, 1, 'Workplace Conflict', 'conflict', 'pending', NULL, '2026-08-17 09:01:59', 'low', 'Harassment', 1, 'uploads/grievance/grievance_9568e1aa7e005c63a7d17fbe96362e0e.pdf', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-17 01:01:59', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 1, 'Workplace Safety Concern', 'safety', 'pending', NULL, '2026-08-26 01:47:43', 'low', 'Workplace Safety', 1, 'uploads/grievance/grievance_b10ba87be3a245005d4cc33a27449e7f.jpeg', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-25 17:47:43', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `em_departments`
--

CREATE TABLE `em_departments` (
  `department_id` int(11) NOT NULL,
  `department_code` varchar(50) DEFAULT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_departments`
--

INSERT INTO `em_departments` (`department_id`, `department_code`, `department_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'DEPT-001', 'ADMIN STAFF', 'Administrative and support staff', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(2, 'DEPT-002', 'INSTRUCTORS', 'Teaching faculty members', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(3, 'DEPT-003', 'IT DEPARTMENT', 'Information Technology department', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(4, 'DEPT-004', 'PSYCHOLOGY DEPARTMENT', 'Psychology department', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(5, 'DEPT-005', 'CRIMINOLOGY DEPARTMENT', 'Criminology department', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(6, 'DEPT-006', 'TOURISM DEPARTMENT', 'Tourism department', 'Active', '2026-08-06 13:47:35', '2026-08-11 09:17:00'),
(7, 'DEPT-007', 'BTVTED', 'Bachelor of Technical-Vocational Teacher Education department', 'Active', '2026-08-13 10:41:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `em_employees`
--

CREATE TABLE `em_employees` (
  `employee_id` int(11) NOT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed','Separated') DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `regular_date` date DEFAULT NULL,
  `employment_status` enum('Active','Resigned','Terminated','Probationary') DEFAULT 'Active',
  `employment_type` enum('Full-time','Part-time','Laboratory','OJT/Training') DEFAULT NULL,
  `unit_load` int(11) DEFAULT NULL,
  `graduate_level` enum('None','LPT','Masteral','Doctoral') DEFAULT 'None',
  `ranking` varchar(100) DEFAULT NULL,
  `credentials` varchar(255) DEFAULT NULL,
  `faculty_notes` text DEFAULT NULL,
  `negotiated_salary` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_employees`
--

INSERT INTO `em_employees` (`employee_id`, `employee_code`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `email`, `mobile_no`, `phone_no`, `current_address`, `permanent_address`, `department_id`, `position_id`, `hire_date`, `regular_date`, `employment_status`, `employment_type`, `unit_load`, `graduate_level`, `ranking`, `credentials`, `faculty_notes`, `negotiated_salary`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_date`) VALUES
(1, 'EMP-000001', 4, 'Ronaldo', 'G.', 'Raymundo', '', 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, 'ronaldocruz22@gmail.com', '09123456789', '0287654321', 'San Jose Del Monte, Bulacan', NULL, 3, 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-06 13:47:35', '2026-08-25 15:33:06', 0, NULL, NULL),
(2, 'EMP-000002', NULL, 'Juan', 'Dela', 'Cruz', NULL, 'Male', '1990-05-15', NULL, NULL, NULL, NULL, 'juan.delacruz@bcp.edu.ph', '09123456789', '021234567', '123 Main St, Manila', NULL, 3, 2, '2023-01-15', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-06 14:54:48', '2026-08-13 15:17:40', 0, NULL, NULL),
(3, 'EMP-000003', NULL, 'Erwin', 'M.', 'De Guzman', NULL, NULL, '1995-09-18', NULL, NULL, NULL, NULL, 'erwindeguzman@gmail.com', '09123456789', '0987654321', '', NULL, 3, 6, '2026-08-11', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', 20000.00, '2026-08-11 19:09:33', '2026-08-13 15:17:40', 0, NULL, NULL),
(4, 'EMP-000004', NULL, 'Roberto', 'J', 'Albert', NULL, NULL, '1998-02-12', NULL, NULL, NULL, NULL, 'robert@gmail.com', '09123456789', '987654321', '', NULL, 3, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-13 11:01:36', '2026-08-13 15:17:40', 0, NULL, NULL),
(5, 'EMP-000005', NULL, 'Althea', 'M.', 'Santos', NULL, NULL, '1999-09-19', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '09123456789', '987654321', '', NULL, 1, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', 20000.00, '2026-08-13 11:04:21', '2026-08-13 15:17:40', 0, NULL, NULL),
(6, 'EMP-000006', NULL, 'Bianca', 'G.', 'Reyes', NULL, NULL, '1995-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '987654321', '', NULL, 1, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', NULL, '2026-08-13 11:05:33', '2026-08-13 15:17:40', 0, NULL, NULL),
(7, 'EMP-000007', NULL, 'Chloe', 'M.', 'Cruz', NULL, NULL, '1995-02-12', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 1, NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:06:40', '2026-08-13 15:17:40', 0, NULL, NULL),
(8, 'EMP-000008', NULL, 'Diana', 'G.', 'Bautista', NULL, NULL, '1995-11-11', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '', '', '', NULL, 1, NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:07:39', '2026-08-13 15:17:40', 0, NULL, NULL),
(9, 'EMP-000009', NULL, 'Elena', 'G.', 'Ocampo', NULL, NULL, '1995-06-16', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 2, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:08:37', '2026-08-13 15:17:40', 0, NULL, NULL),
(10, 'EMP-000010', NULL, 'Fiona', 'G.', 'Ramos', NULL, NULL, '1996-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 2, NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:09:29', '2026-08-13 15:17:40', 0, NULL, NULL),
(11, 'EMP-000011', NULL, 'Aaron', '', 'Mendoza', NULL, NULL, '1999-07-17', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 2, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:10:26', '2026-08-13 15:17:40', 0, NULL, NULL),
(12, 'EMP-000012', NULL, 'Caleb', '', 'Santos', NULL, NULL, '1990-04-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 2, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:11:23', '2026-08-13 15:17:40', 0, NULL, NULL),
(13, 'EMP-000013', NULL, 'David', '', 'Aquino', NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '', '', '', NULL, 4, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:12:30', '2026-08-13 15:17:40', 0, NULL, NULL),
(14, 'EMP-000014', NULL, 'Ethan', '', 'Garcia', NULL, NULL, '1999-12-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 4, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:13:14', '2026-08-13 15:17:40', 0, NULL, NULL),
(15, 'EMP-000015', NULL, 'Felix', '', 'Del Rosario', NULL, NULL, '2001-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 4, NULL, '2026-08-13', NULL, 'Active', 'OJT/Training', NULL, 'None', '', '', '', NULL, '2026-08-13 11:14:12', '2026-08-13 15:17:40', 0, NULL, NULL),
(16, 'EMP-000016', NULL, 'Gabriel', '', 'Gonzales', NULL, NULL, '1990-02-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 4, NULL, '2026-08-13', NULL, 'Active', 'Part-time', 1, 'None', '', '', '', NULL, '2026-08-13 11:15:10', '2026-08-13 15:17:40', 0, NULL, NULL),
(17, 'EMP-000017', NULL, 'Hugo', '', 'Villanueva', NULL, NULL, '1999-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 5, NULL, '2026-08-13', NULL, 'Active', 'Part-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:16:41', '2026-08-13 15:17:40', 0, NULL, NULL),
(18, 'EMP-000018', NULL, 'Ian', '', 'Fernandez', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 5, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:17:19', '2026-08-13 15:17:40', 0, NULL, NULL),
(19, 'EMP-000019', NULL, 'Jacob', '', 'Lopez', NULL, NULL, '1999-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 5, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:18:06', '2026-08-13 15:17:40', 0, NULL, NULL),
(20, 'EMP-000020', NULL, 'Ian', '', 'Perez', NULL, NULL, '1990-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 5, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:18:56', '2026-08-25 18:22:36', 0, NULL, NULL),
(21, 'EMP-000021', NULL, 'Gia', '', 'Valdez', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 6, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:19:35', '2026-08-13 15:17:40', 0, NULL, NULL),
(22, 'EMP-000022', NULL, 'Aaron', '', 'Valdez', NULL, NULL, '1996-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 6, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:20:21', '2026-08-13 15:17:40', 0, NULL, NULL),
(23, 'EMP-000023', NULL, 'Aaron', '', 'Pascual', NULL, NULL, '1999-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 6, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:21:00', '2026-08-13 15:17:40', 0, NULL, NULL),
(24, 'EMP-000024', NULL, 'Iris', '', 'Soriano', NULL, NULL, '1998-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 6, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:21:36', '2026-08-13 15:17:40', 0, NULL, NULL),
(25, 'EMP-000025', NULL, 'Zenith', '', 'Tolentino', NULL, NULL, '1998-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 7, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:22:23', '2026-08-13 15:17:40', 0, NULL, NULL),
(26, 'EMP-000026', NULL, 'Lumina', '', 'Tolentino', NULL, NULL, '1999-10-20', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 7, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:22:59', '2026-08-13 15:17:40', 0, NULL, NULL),
(27, 'EMP-000027', NULL, 'Vibe', '', 'Mercado', NULL, NULL, '1999-08-08', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 7, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:23:32', '2026-08-13 15:17:40', 0, NULL, NULL),
(28, 'EMP-000028', NULL, 'Diana', '', 'Mercado', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 7, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:24:04', '2026-08-13 15:17:40', 0, NULL, NULL),
(29, 'EMP-000029', NULL, 'Jhon Carlo', NULL, 'Garcia', NULL, 'Male', '1995-01-15', NULL, 'Single', 'Filipino', NULL, 'jhon.garcia@bcp.edu.ph', '09170000001', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(30, 'EMP-000030', NULL, 'Russel', 'Gohetia', 'Cabrido', NULL, 'Male', '1995-02-20', NULL, 'Single', 'Filipino', NULL, 'russel.cabrido@bcp.edu.ph', '09170000002', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 32000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(31, 'EMP-000031', NULL, 'Jose Mari Rich', NULL, 'Malana', NULL, 'Male', '1995-03-10', NULL, 'Single', 'Filipino', NULL, 'jose.malana@bcp.edu.ph', '09170000003', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(32, 'EMP-000032', NULL, 'Russell', NULL, 'Placer', NULL, 'Male', '1995-04-15', NULL, 'Single', 'Filipino', NULL, 'russell.placer@bcp.edu.ph', '09170000004', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(33, 'EMP-000033', NULL, 'Cheska', NULL, 'Baustita', NULL, 'Female', '1996-05-20', NULL, 'Single', 'Filipino', NULL, 'cheska.baustita@bcp.edu.ph', '09170000005', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(34, 'EMP-000034', NULL, 'Jayson', NULL, 'Paigma', NULL, 'Male', '1995-06-15', NULL, 'Single', 'Filipino', NULL, 'jayson.paigma@bcp.edu.ph', '09170000006', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(35, 'EMP-000035', NULL, 'Rainiel', NULL, 'Quebada', NULL, 'Male', '1996-07-10', NULL, 'Single', 'Filipino', NULL, 'rainiel.quebada@bcp.edu.ph', '09170000007', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(36, 'EMP-000036', NULL, 'Karl', NULL, 'Solis', NULL, 'Male', '1995-08-20', NULL, 'Single', 'Filipino', NULL, 'karl.solis@bcp.edu.ph', '09170000008', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(37, 'EMP-000037', NULL, 'Geoffrey', NULL, 'Balansag', NULL, 'Male', '1995-09-10', NULL, 'Single', 'Filipino', NULL, 'geoffrey.balansag@bcp.edu.ph', '09170000009', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(38, 'EMP-000038', NULL, 'Johnloyd', NULL, 'Reyes', NULL, 'Male', '1996-10-15', NULL, 'Single', 'Filipino', NULL, 'johnloyd.reyes@bcp.edu.ph', '09170000010', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL),
(39, 'EMP-000039', NULL, 'Alexis', NULL, 'Cueto', NULL, 'Female', '1996-11-20', NULL, 'Single', 'Filipino', NULL, 'alexis.cueto@bcp.edu.ph', '09170000011', NULL, 'Bulacan', NULL, 1, 4, '2026-08-14', NULL, 'Active', 'Full-time', NULL, 'Masteral', NULL, NULL, NULL, 30000.00, '2026-08-14 08:33:41', '2026-08-14 08:33:41', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `em_positions`
--

CREATE TABLE `em_positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `slot_count` int(11) NOT NULL DEFAULT 1,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_positions`
--

INSERT INTO `em_positions` (`position_id`, `position_name`, `slot_count`, `department_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'School Directress', 1, 1, 'Active', '2026-08-06 13:47:35', NULL),
(2, 'College Coordinator', 1, 1, 'Active', '2026-08-06 13:47:35', '2026-08-14 08:30:44'),
(3, 'HR Officer', 1, 1, 'Active', '2026-08-06 13:47:35', NULL),
(4, 'HR Staff', 20, 1, 'Active', '2026-08-06 13:47:35', '2026-08-14 08:30:25'),
(5, 'Librarian', 2, 1, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(6, 'General Education Instructor', 5, 2, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(7, 'English Instructor', 3, 2, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(8, 'Mathematics Instructor', 3, 2, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(9, 'IT Staff', 3, 3, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(10, 'IT Instructor', 4, 3, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(11, 'Psychology Instructor', 2, 4, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(12, 'Criminology Instructor', 4, 5, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(13, 'Tourism Instructor', 3, 6, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(14, 'Monitoring Staff', 2, 1, 'Active', '2026-08-11 18:20:50', NULL),
(15, 'Laboratory Technician', 2, 1, 'Active', '2026-08-11 18:20:50', NULL),
(16, 'Records Officer', 1, 1, 'Active', '2026-08-11 18:20:50', NULL),
(17, 'Records Keeper', 2, 1, 'Active', '2026-08-11 18:20:50', NULL),
(18, 'Guidance Assistant', 1, 1, 'Active', '2026-08-11 18:20:50', NULL),
(19, 'Guidance Counselor', 1, 1, 'Active', '2026-08-11 18:20:50', NULL),
(20, 'Accounting Clerk', 2, 1, 'Active', '2026-08-11 18:20:50', NULL),
(21, 'School Nurse', 1, 1, 'Active', '2026-08-11 18:20:50', NULL),
(22, 'Professional Education Instructor', 3, 2, 'Active', '2026-08-11 18:20:50', NULL),
(23, 'Accounting Instructor', 2, 2, 'Active', '2026-08-11 18:20:50', NULL),
(24, 'Entrepreneurship Instructor', 2, 2, 'Active', '2026-08-11 18:20:50', NULL),
(25, 'Criminology Laboratory Staff', 2, 5, 'Active', '2026-08-11 18:20:50', NULL),
(26, 'Law Instructor', 3, 5, 'Active', '2026-08-11 18:20:50', NULL),
(27, 'Filipino Instructor', 3, 2, 'Active', '2026-08-11 20:29:07', '2026-08-11 20:36:24'),
(28, 'Science Instructor', 3, 2, 'Active', '2026-08-11 20:29:07', '2026-08-11 20:36:24'),
(29, 'Physical Education Instructor', 3, 2, 'Active', '2026-08-11 20:29:07', '2026-08-11 20:36:24'),
(30, 'Social Science Instructor', 3, 2, 'Active', '2026-08-11 20:29:07', '2026-08-11 20:36:24'),
(31, 'College Instructor', 3, 7, 'Active', '2026-08-13 10:41:59', NULL),
(32, 'Assistant Professor', 3, 7, 'Active', '2026-08-13 10:41:59', NULL),
(33, 'Associate Professor', 2, 7, 'Active', '2026-08-13 10:41:59', NULL),
(34, 'Technical Instructor', 3, 7, 'Active', '2026-08-13 10:41:59', NULL),
(35, 'Department Head', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(36, 'Program Chair', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(37, 'College Dean', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(38, 'Curriculum Specialist', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(39, 'Shop Superintendent', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(40, 'Laboratory Manager', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(41, 'Toolkeeper', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(42, 'Laboratory Technician', 2, 7, 'Active', '2026-08-13 10:41:59', NULL),
(43, 'OJT Coordinator', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(44, 'Internship Coordinator', 1, 7, 'Active', '2026-08-13 10:41:59', NULL),
(45, 'Extension Services Coordinator', 1, 7, 'Active', '2026-08-13 10:41:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ep_benefits_and_government_contribution`
--

CREATE TABLE `ep_benefits_and_government_contribution` (
  `benefit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `record_type` enum('SSS','PhilHealth','Pag-IBIG','Withholding Tax','BIR Form 2316') NOT NULL,
  `period` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_benefits_and_government_contribution`
--

INSERT INTO `ep_benefits_and_government_contribution` (`benefit_id`, `employee_id`, `record_type`, `period`, `description`, `file_name`, `file_path`, `uploaded_by`, `uploaded_at`, `updated_at`) VALUES
(2, 1, 'BIR Form 2316', '2026-07', 'Sample 1', '', '', 3, '2026-07-19 08:14:46', '2026-08-14 05:48:42'),
(4, 1, 'SSS', '2026-08', 'sample', '', '', 3, '2026-07-30 23:34:54', '2026-08-14 05:48:49'),
(5, 1, 'SSS', '2026-08', 'sample', '', '', 1, '2026-08-02 01:06:38', '2026-08-14 05:48:54'),
(6, 1, 'BIR Form 2316', '2026-07', NULL, 'BPA-EMPLOYEE-PORTAL.pdf', 'assets/uploads/benefits/benefits_1786954895_ab4dee60.pdf', 3, '2026-08-14 06:42:23', '2026-08-17 08:21:35'),
(7, 1, 'SSS', '2026-02', NULL, 'BSIS-BULACAN-BSIS-RESEARCH-FESTIVAL-2025-2026-EMPLOYEE-PORTAL-CAPSTONE.docx', 'assets/uploads/benefits/benefits1786691045_cd50b6f620.docx', 1, '2026-08-14 07:04:05', '2026-08-14 07:04:05'),
(8, 1, 'Pag-IBIG', '2026-02', 'sample', 'BPA-EMPLOYEE-PORTAL.pdf', 'assets/uploads/benefits/benefits1786692021_7130abb94c.pdf', 1, '2026-08-14 07:20:21', '2026-08-14 07:20:21'),
(9, 2, 'Pag-IBIG', '2026-06', 'sample', 'BPA-EMPLOYEE-PORTAL.pdf', 'assets/uploads/benefits/benefits_1786954963_92bdd2e8.pdf', 3, '2026-08-17 08:03:22', '2026-08-17 08:22:43'),
(10, 3, 'BIR Form 2316', '2026-07', 'sample', 'BPA-EMPLOYEE-PORTAL.pdf', 'assets/uploads/benefits/benefits1786953890_74e86d6cf9.pdf', 3, '2026-08-17 08:04:50', '2026-08-17 08:04:50'),
(11, 1, 'Pag-IBIG', '2026-07', 'sample', 'warehousing.jpg', 'assets/uploads/benefits/benefits1787675086_f47a4dd5a2.jpg', 4, '2026-08-25 16:24:46', '2026-08-25 16:24:46');

-- --------------------------------------------------------

--
-- Table structure for table `ep_notifications`
--

CREATE TABLE `ep_notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('announcement','payroll','leave','training','performance','document','meeting','compliance','general') DEFAULT 'general',
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `target_url` varchar(255) DEFAULT NULL,
  `created_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_notifications`
--

INSERT INTO `ep_notifications` (`notification_id`, `title`, `message`, `type`, `priority`, `target_url`, `created_by_user_id`, `created_at`) VALUES
(1, 'Company Orientation', 'Welcome to the company orientation scheduled on July 20, 2026.', 'announcement', 'normal', 'employee-announcements', 1, '2026-07-15 06:13:02'),
(2, 'Payslip Available', 'Your payslip for June 2026 is now available.', 'payroll', 'important', 'employee-payslip', 1, '2026-07-15 06:13:02'),
(3, 'Leave Request Approved', 'Your leave request has been approved.', 'leave', 'important', 'employee-leave-request', 2, '2026-07-15 06:13:02'),
(4, 'Mandatory Cybersecurity Training', 'You have been enrolled in the Cybersecurity Awareness Training.', 'training', 'urgent', 'employee-training-programs', 2, '2026-07-15 06:13:02'),
(5, 'Performance Evaluation', 'Your quarterly performance evaluation is now available.', 'performance', 'important', 'performance-feedback', 1, '2026-07-15 06:13:02'),
(6, 'Document Submission', 'Please submit your updated government IDs.', 'document', 'urgent', 'employee-documents', 3, '2026-07-15 06:13:02'),
(7, 'Team Meeting', 'A department meeting is scheduled tomorrow at 10:00 AM.', 'meeting', 'normal', 'employee-meetings', 2, '2026-07-15 06:13:02'),
(8, 'Compliance Reminder', 'Complete the annual compliance training before the deadline.', 'compliance', 'urgent', 'employee-compliance', 1, '2026-07-15 06:13:02'),
(9, 'Holiday Announcement', 'The office will be closed on National Heroes Day.', 'announcement', 'normal', 'employee-announcements', 1, '2026-07-15 06:13:02'),
(10, 'Payroll Reminder', 'Payroll processing will begin tomorrow.', 'payroll', 'normal', 'employee-payslip', 2, '2026-07-15 06:13:02'),
(11, 'Training Certificate', 'Your training certificate is now available.', 'training', 'normal', 'employee-training-programs', 3, '2026-07-15 06:13:02'),
(12, 'Performance Feedback', 'Your manager has submitted new performance feedback.', 'performance', 'important', 'employee-performance', 2, '2026-07-15 06:13:02'),
(13, 'Document Verified', 'Your submitted employment documents have been verified.', 'document', 'normal', 'employee-documents', 1, '2026-07-15 06:13:02'),
(14, 'Meeting Rescheduled', 'The monthly staff meeting has been moved to Friday.', 'meeting', 'important', 'employee-meetings', 2, '2026-07-15 06:13:02'),
(15, 'Leave Request Pending', 'Your leave request is currently under review.', 'leave', 'normal', 'employee-leave-request', 1, '2026-07-15 06:13:02'),
(16, 'Company Picnic', 'Join us for the annual company outing this Saturday.', 'announcement', 'normal', 'employee-announcements', 3, '2026-07-15 06:13:02'),
(17, 'Salary Adjustment Notice', 'Your salary adjustment has been processed.', 'payroll', 'important', 'employee-payslip', 1, '2026-07-15 06:13:02'),
(18, 'Training Reminder', 'Your enrolled training starts tomorrow.', 'training', 'important', 'employee-training-programs', 2, '2026-07-15 06:13:02'),
(19, 'Compliance Deadline', 'Your compliance documents must be submitted this week.', 'compliance', 'urgent', 'employee-compliance', 3, '2026-07-15 06:13:02'),
(20, 'Employee Survey', 'Please complete the annual employee satisfaction survey.', 'general', 'normal', 'employee-dashboard', 1, '2026-07-15 06:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `ep_notification_recipients`
--

CREATE TABLE `ep_notification_recipients` (
  `recipient_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_notification_recipients`
--

INSERT INTO `ep_notification_recipients` (`recipient_id`, `notification_id`, `employee_id`, `is_read`, `read_at`) VALUES
(1, 1, 1, 1, '2026-08-14 08:55:34'),
(2, 2, 1, 0, '2026-08-14 08:54:41'),
(3, 3, 3, 0, NULL),
(4, 4, 4, 0, NULL),
(5, 5, 5, 1, '2026-07-18 17:49:21'),
(6, 6, 6, 0, NULL),
(7, 7, 7, 0, '2026-07-18 17:34:47'),
(8, 8, 8, 1, '2026-07-15 06:13:02'),
(9, 9, 9, 0, NULL),
(10, 10, 10, 0, NULL),
(11, 11, 11, 1, '2026-07-15 06:13:02'),
(12, 12, 12, 0, NULL),
(13, 13, 13, 0, NULL),
(14, 14, 14, 1, '2026-07-15 06:13:02'),
(15, 15, 15, 0, NULL),
(16, 16, 16, 0, NULL),
(17, 17, 17, 1, '2026-07-15 06:13:02'),
(18, 18, 18, 0, NULL),
(19, 19, 19, 0, NULL),
(20, 27, 15, 0, NULL),
(21, 27, 16, 0, NULL),
(22, 27, 14, 0, NULL),
(23, 20, 15, 0, NULL),
(24, 20, 16, 0, NULL),
(25, 20, 14, 1, '2026-08-02 01:42:27'),
(26, 54, 15, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ep_online_meetings`
--

CREATE TABLE `ep_online_meetings` (
  `meetings_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `meeting_link` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_online_meetings`
--

INSERT INTO `ep_online_meetings` (`meetings_id`, `title`, `meeting_link`, `created_by`, `employee_id`, `scheduled_at`, `status`) VALUES
(2, 'Academic Forum', 'https://meet.jit.si/hr_meeting_69d0b6b27deb8', 3, 6, '2026-04-06 14:58:00', 'cancelled'),
(3, 'Midterm Planning', 'https://meet.jit.si/hr_meeting_69d0b6d45aa13', 3, 6, '2026-04-08 14:59:00', 'scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `ep_payroll_request`
--

CREATE TABLE `ep_payroll_request` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `payroll_period_start` date DEFAULT NULL,
  `payroll_period_end` date DEFAULT NULL,
  `status` enum('Pending','Processing','Approved','Rejected','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_payroll_request`
--

INSERT INTO `ep_payroll_request` (`id`, `employee_id`, `request_type`, `purpose`, `remarks`, `payroll_period_start`, `payroll_period_end`, `status`, `requested_at`, `processed_at`, `processed_by`, `rejection_reason`, `document_path`, `created_at`, `updated_at`) VALUES
(4, 1, 'Payroll Correction', 'Regular Payroll Processing', NULL, '2026-08-13', '2026-08-15', 'Approved', '2026-08-13 17:55:44', '2026-08-17 05:49:33', 3, NULL, 'assets/uploads/payroll/payroll_4_1786945773.pdf', '2026-08-13 17:55:44', '2026-08-17 05:49:33'),
(5, 1, 'Incorrect Deduction', 'New Hire Payroll Onboarding', NULL, '2026-08-01', '2026-08-03', 'Rejected', '2026-08-17 06:04:20', '2026-08-17 07:12:44', 3, 'sample', NULL, '2026-08-17 06:04:20', '2026-08-17 07:12:44'),
(6, 1, 'Payroll Correction', 'Salary Advance Request', NULL, '2026-08-18', '2026-08-27', 'Approved', '2026-08-25 15:41:51', '2026-08-25 18:11:37', 3, NULL, 'assets/uploads/payroll/payroll_6_1787681497.pdf', '2026-08-25 15:41:51', '2026-08-25 18:11:37'),
(7, 1, 'Missing Salary', 'Other / General Inquiry', NULL, '2026-08-26', '2026-08-27', 'Approved', '2026-08-25 15:44:43', '2026-08-25 16:10:53', 3, NULL, 'assets/uploads/payroll/payroll_7_1787674253.docx', '2026-08-25 15:44:43', '2026-08-25 16:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `ep_resignation_requests`
--

CREATE TABLE `ep_resignation_requests` (
  `resignation_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `resignation_type` enum('Immediate','With Notice') DEFAULT 'With Notice',
  `resignation_reason` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `date_submitted` datetime DEFAULT current_timestamp(),
  `intended_last_working_day` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `employee_remarks` text DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_resignation_requests`
--

INSERT INTO `ep_resignation_requests` (`resignation_id`, `employee_id`, `resignation_type`, `resignation_reason`, `attachment`, `date_submitted`, `intended_last_working_day`, `status`, `employee_remarks`, `hr_remarks`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'With Notice', 'I have accepted a new career opportunity that aligns with my professional goals.', 'uploads/resignation/resignation_1.pdf', '2026-08-01 09:15:00', '2026-08-31', 'Rejected', 'I will ensure a proper turnover of my responsibilities.', 'sample', 3, '2026-08-17 19:40:34', '2026-08-01 07:01:43', '2026-08-17 11:40:34'),
(8, 1, 'With Notice', 'sample', 'uploads/resignation/resignation_1_1786844898.pdf', '2026-08-16 03:48:18', '2026-08-24', 'Approved', 'sample', 'sample', 3, '2026-08-17 19:35:16', '2026-08-15 19:48:18', '2026-08-17 11:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `ep_users`
--

CREATE TABLE `ep_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `role` varchar(50) NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `theme` enum('light','dark') DEFAULT 'light',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_users`
--

INSERT INTO `ep_users` (`id`, `username`, `password`, `email`, `is_admin`, `role`, `is_active`, `theme`, `profile_image`, `created_at`, `password_reset_token`, `password_reset_expires`) VALUES
(1, 'Employee 1', '$2y$10$O6XSlGEzC5GCae7BrLAhneWoLgqV3P1Pi3a0czwSdmZ.6.kR8F9va', 'monstreborvinsmoke025@gmail.com', 0, 'employee', 1, 'light', NULL, '2026-01-28 07:21:13', NULL, NULL),
(2, 'Employee 2', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', NULL, 0, 'employee', 1, 'light', NULL, '2026-03-24 18:06:12', NULL, NULL),
(3, 'Admin Employee Portal', '$2y$10$h2fzXPO1/co0hCUn/wwOnuLB4I/26hMz3hGJIqbRmujA2R1UxHIvy', 'crobertjanssen@gmail.com', 1, 'super_admin', 1, 'light', NULL, '2026-01-28 07:21:13', NULL, NULL),
(4, 'Employee 3', '$2y$10$g17RDcYiD9hI9K6Nj5KyMehcqlAaoh0qDeGpYUn9TJI1cpfkIfv32', 'sample@gmail.com', 0, 'employee', 1, 'light', 'profile_4_1787671041.png', '2026-01-28 07:21:13', NULL, NULL),
(5, 'sample', '$2y$10$dHzUOq6RTrujLH/eo9w4dudWV6qZD41cAhVM22kde2bl8I5KlxZuK', 'camposrobertjanssent.pdm@gmail.com', 0, 'employee', 1, 'light', NULL, '2026-08-16 13:35:55', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lc_incidents`
--

CREATE TABLE `lc_incidents` (
  `id` int(11) NOT NULL,
  `incident_id` varchar(50) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `respondent_id` int(11) DEFAULT NULL,
  `reporter_id` int(11) DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `status` enum('submitted','under_review','investigation','nte_issued','explanation_received','hr_evaluation','decision_made','final_action','resolved','closed') NOT NULL DEFAULT 'submitted',
  `current_workflow_step` varchar(50) DEFAULT NULL,
  `status_changed_at` datetime DEFAULT NULL,
  `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
  `nte_deadline` datetime DEFAULT NULL,
  `explanation_deadline` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_incidents`
--

INSERT INTO `lc_incidents` (`id`, `incident_id`, `type`, `incident_type`, `severity`, `title`, `description`, `incident_date`, `location`, `respondent_id`, `reporter_id`, `reporter_name`, `status`, `current_workflow_step`, `status_changed_at`, `is_confidential`, `nte_deadline`, `explanation_deadline`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INC-2024-001', NULL, 'Harassment', 'High', 'Workplace Harassment Complaint', 'Employee reported repeated inappropriate comments from supervisor during team meetings.', '2024-07-15', 'Main Office - 3rd Floor', 5, 12, 'Juan Dela Cruz', 'investigation', 'Investigation', NULL, 0, '2024-07-22 17:00:00', '2024-07-18 17:00:00', 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12'),
(2, 'INC-2024-002', NULL, 'Policy Violation', 'Medium', 'Unauthorized Use of Company Resources', 'Employee used company vehicle for personal errands without approval.', '2024-07-20', 'Branch Office - Parking Area', 8, 15, 'Maria Santos', 'under_review', 'HR Review', NULL, 0, NULL, '2024-07-25 17:00:00', 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12'),
(3, 'INC-2024-003', NULL, 'Safety Incident', 'Critical', 'Equipment Malfunction Near Miss', 'Heavy equipment malfunctioned during operation; no injuries reported but safety protocols breached.', '2024-07-22', 'Warehouse - Zone B', 3, 20, 'Pedro Reyes', 'submitted', 'Initial Review', NULL, 1, NULL, NULL, 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12'),
(4, 'INC-2024-004', NULL, 'Attendance Violation', 'Low', 'Excessive Unexcused Absences', 'Employee accumulated 15 unexcused absences in one month without prior notification.', '2024-07-18', 'Remote / Work From Home', 22, 30, 'Ana Gonzales', 'hr_evaluation', 'HR Evaluation', NULL, 0, NULL, NULL, 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12'),
(5, 'INC-2024-005', NULL, 'Data Breach', 'Critical', 'Unauthorized Access to Confidential Files', 'Employee accessed confidential client data outside of authorized job function.', '2024-07-25', 'IT Department - Server Room', 11, 45, 'Carlos Mendoza', 'decision_made', 'Decision', NULL, 1, NULL, NULL, 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12'),
(6, 'INC-2026-006', NULL, 'absenteeism', 'Medium', 'Sample', 'sample', '2026-08-25', 'HR Office', 8, 1, 'Ronaldo Raymundo', 'submitted', 'Initial Review', '2026-08-26 01:28:14', 0, NULL, NULL, 4, '2026-08-25 17:28:14', '2026-08-25 17:40:52'),
(7, 'INC-2026-007', NULL, 'workplace_conflict', 'Medium', 'Workplace conflict', 'sample', '2026-08-25', 'Campus Grounds', 9, 1, 'Ronaldo Raymundo', 'submitted', 'Initial Review', '2026-08-26 01:41:25', 0, NULL, NULL, 4, '2026-08-25 17:41:25', '2026-08-25 17:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `ld_course`
--

CREATE TABLE `ld_course` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `enrollment_deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course`
--

INSERT INTO `ld_course` (`id`, `instructor_id`, `title`, `description`, `thumbnail_path`, `category`, `status`, `start_date`, `enrollment_deadline`, `created_at`, `updated_at`) VALUES
(1, 1, 'Effective Workplace Communication', 'Develop professional communication skills for communicating clearly and effectively with colleagues, supervisors, and clients.', 'assets/uploads/learning/course_20260829_113305_42333b0e9a.jpg', 'Communication', 'draft', '2026-09-07', '2026-09-04', '2026-08-28 14:00:22', '2026-08-29 10:42:17'),
(2, 2, 'Time Management and Productivity', 'Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.', 'assets/uploads/learning/course_20260829_115529_1ed358fae5.png', 'Productivity', 'draft', '2026-09-09', '2026-09-06', '2026-08-28 14:00:22', '2026-08-29 10:03:32'),
(3, 3, 'Leadership Essentials', 'Learn the fundamentals of effective leadership including decision-making, delegation, motivation, accountability, and team management.', 'assets/uploads/learning/course_20260829_120838_6b5ceb7e3e.png', 'Leadership', 'active', '2026-09-14', '2026-09-11', '2026-08-28 14:00:22', '2026-08-29 10:08:38'),
(4, 4, 'Workplace Ethics and Professional Conduct', 'Understand professional ethics, workplace behavior, accountability, confidentiality, and responsible decision-making.', 'uploads/courses/workplace-ethics.jpg', 'Compliance', 'active', '2026-09-16', '2026-09-13', '2026-08-28 14:00:22', '2026-08-28 14:00:22'),
(5, 5, 'Microsoft Excel for Office Productivity', 'Develop practical Microsoft Excel skills including formulas, functions, data organization, charts, and basic data analysis.', 'uploads/courses/excel-productivity.jpg', 'Technical Skills', 'active', '2026-09-21', '2026-09-18', '2026-08-28 14:00:22', '2026-08-28 14:00:22'),
(6, 6, 'Customer Service Excellence', 'Improve customer service skills through effective listening, problem solving, professional communication, and handling difficult situations.', 'uploads/courses/customer-service.jpg', 'Customer Service', 'active', '2026-09-23', '2026-09-20', '2026-08-28 14:00:22', '2026-08-28 14:00:22'),
(7, 7, 'Teamwork and Collaboration', 'Build stronger teamwork skills through collaboration strategies, conflict management, trust building, and effective team participation.', 'uploads/courses/teamwork-collaboration.jpg', 'Team Development', 'active', '2026-09-28', '2026-09-25', '2026-08-28 14:00:22', '2026-08-28 14:00:22'),
(9, 9, 'Workplace Well-Being and Stress Management', 'Learn practical strategies for managing workplace stress, maintaining work-life balance, and developing healthy professional habits.', 'uploads/courses/stress-management.jpg', 'Well-Being', 'active', '2026-10-05', '2026-10-02', '2026-08-28 14:00:22', '2026-08-28 14:00:22'),
(14, 5, 'Data Analysis Fundamentals', 'sample', 'assets/uploads/learning/course_20260828_184837_0049f703db.jpg', 'Compliance', 'active', '2026-11-28', '2026-10-28', '2026-08-28 16:48:37', '2026-08-29 10:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `ld_course_instructor`
--

CREATE TABLE `ld_course_instructor` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `role` enum('owner','co-instructor') NOT NULL DEFAULT 'owner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course_instructor`
--

INSERT INTO `ld_course_instructor` (`id`, `course_id`, `instructor_id`, `role`, `created_at`) VALUES
(7, 4, 4, 'owner', '2026-08-28 14:00:31'),
(8, 4, 5, 'co-instructor', '2026-08-28 14:00:31'),
(9, 5, 5, 'owner', '2026-08-28 14:00:31'),
(10, 5, 6, 'co-instructor', '2026-08-28 14:00:31'),
(11, 6, 6, 'owner', '2026-08-28 14:00:31'),
(12, 6, 7, 'co-instructor', '2026-08-28 14:00:31'),
(13, 7, 7, 'owner', '2026-08-28 14:00:31'),
(14, 7, 8, 'co-instructor', '2026-08-28 14:00:31'),
(17, 9, 9, 'owner', '2026-08-28 14:00:31'),
(18, 9, 10, 'co-instructor', '2026-08-28 14:00:31'),
(21, 11, 6, 'owner', '2026-08-28 16:33:13'),
(22, 11, 5, 'co-instructor', '2026-08-28 16:33:13'),
(23, 11, 7, 'co-instructor', '2026-08-28 16:33:13'),
(24, 12, 6, 'owner', '2026-08-28 16:35:19'),
(25, 13, 4, 'owner', '2026-08-28 16:43:09'),
(26, 13, 5, 'co-instructor', '2026-08-28 16:43:09'),
(27, 14, 5, 'owner', '2026-08-28 16:48:37'),
(30, 1, 1, 'owner', '2026-08-29 09:33:05'),
(31, 1, 2, 'co-instructor', '2026-08-29 09:33:05'),
(32, 1, 8, 'co-instructor', '2026-08-29 09:33:05'),
(41, 2, 2, 'owner', '2026-08-29 10:03:32'),
(42, 2, 3, 'co-instructor', '2026-08-29 10:03:32'),
(43, 3, 3, 'owner', '2026-08-29 10:08:38'),
(44, 3, 4, 'co-instructor', '2026-08-29 10:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `ld_course_skill`
--

CREATE TABLE `ld_course_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course_skill`
--

INSERT INTO `ld_course_skill` (`id`, `course_id`, `skill_id`) VALUES
(10, 4, 10),
(11, 4, 11),
(12, 4, 12),
(13, 5, 13),
(14, 5, 14),
(15, 5, 15),
(16, 6, 1),
(17, 6, 16),
(18, 6, 17),
(19, 7, 2),
(20, 7, 8),
(21, 7, 18),
(25, 9, 5),
(26, 9, 16),
(27, 9, 18),
(31, 11, 2),
(32, 12, 2),
(33, 13, 1),
(34, 14, 8),
(38, 1, 1),
(39, 1, 2),
(40, 1, 3),
(53, 2, 4),
(54, 2, 5),
(55, 2, 6),
(56, 3, 7),
(57, 3, 8),
(58, 3, 9);

-- --------------------------------------------------------

--
-- Table structure for table `ld_course_version`
--

CREATE TABLE `ld_course_version` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `published_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course_version`
--

INSERT INTO `ld_course_version` (`id`, `course_id`, `version_number`, `snapshot`, `published_at`) VALUES
(1, 1, 1, '{\n    \"title\": \"Effective Workplace Communication\",\n    \"category\": \"Communication\",\n    \"description\": \"Develop professional communication skills for communicating clearly and effectively with colleagues, supervisors, and clients.\",\n    \"lessons\": [\n        {\n            \"title\": \"Communication Fundamentals\",\n            \"duration_minutes\": 30\n        },\n        {\n            \"title\": \"Active Listening\",\n            \"duration_minutes\": 40\n        },\n        {\n            \"title\": \"Professional Workplace Communication\",\n            \"duration_minutes\": 45\n        }\n    ]\n}', '2026-08-28 14:01:07'),
(2, 2, 1, '{\r\n    \"title\": \"Time Management and Productivity\",\r\n    \"category\": \"Productivity\",\r\n    \"description\": \"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Understanding Time Management\",\r\n            \"duration_minutes\": 30\r\n        },\r\n        {\r\n            \"title\": \"Prioritizing Tasks\",\r\n            \"duration_minutes\": 40\r\n        },\r\n        {\r\n            \"title\": \"Productivity Techniques\",\r\n            \"duration_minutes\": 45\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(3, 3, 1, '{\n    \"title\": \"Leadership Essentials\",\n    \"category\": \"Leadership\",\n    \"description\": \"Learn the fundamentals of effective leadership including decision-making, delegation, motivation, accountability, and team management.\",\n    \"lessons\": [\n        {\n            \"title\": \"Introduction to Leadership\",\n            \"duration_minutes\": 35\n        },\n        {\n            \"title\": \"Decision Making\",\n            \"duration_minutes\": 45\n        },\n        {\n            \"title\": \"Motivating Teams\",\n            \"duration_minutes\": 50\n        }\n    ]\n}', '2026-08-28 14:01:07'),
(4, 4, 1, '{\r\n    \"title\": \"Workplace Ethics and Professional Conduct\",\r\n    \"category\": \"Compliance\",\r\n    \"description\": \"Understand professional ethics, workplace behavior, accountability, confidentiality, and responsible decision-making.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Workplace Ethics\",\r\n            \"duration_minutes\": 30\r\n        },\r\n        {\r\n            \"title\": \"Professional Conduct\",\r\n            \"duration_minutes\": 35\r\n        },\r\n        {\r\n            \"title\": \"Ethical Decision Making\",\r\n            \"duration_minutes\": 40\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(5, 5, 1, '{\r\n    \"title\": \"Microsoft Excel for Office Productivity\",\r\n    \"category\": \"Technical Skills\",\r\n    \"description\": \"Develop practical Microsoft Excel skills including formulas, functions, data organization, charts, and basic data analysis.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Excel Fundamentals\",\r\n            \"duration_minutes\": 40\r\n        },\r\n        {\r\n            \"title\": \"Formulas and Functions\",\r\n            \"duration_minutes\": 50\r\n        },\r\n        {\r\n            \"title\": \"Charts and Data Analysis\",\r\n            \"duration_minutes\": 60\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(6, 6, 1, '{\r\n    \"title\": \"Customer Service Excellence\",\r\n    \"category\": \"Customer Service\",\r\n    \"description\": \"Improve customer service skills through effective listening, problem solving, professional communication, and handling difficult situations.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Customer Service Fundamentals\",\r\n            \"duration_minutes\": 30\r\n        },\r\n        {\r\n            \"title\": \"Handling Difficult Customers\",\r\n            \"duration_minutes\": 45\r\n        },\r\n        {\r\n            \"title\": \"Customer Problem Solving\",\r\n            \"duration_minutes\": 45\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(7, 7, 1, '{\r\n    \"title\": \"Teamwork and Collaboration\",\r\n    \"category\": \"Team Development\",\r\n    \"description\": \"Build stronger teamwork skills through collaboration strategies, conflict management, trust building, and effective team participation.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Building Effective Teams\",\r\n            \"duration_minutes\": 35\r\n        },\r\n        {\r\n            \"title\": \"Collaboration Strategies\",\r\n            \"duration_minutes\": 40\r\n        },\r\n        {\r\n            \"title\": \"Managing Team Conflict\",\r\n            \"duration_minutes\": 45\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(9, 9, 1, '{\r\n    \"title\": \"Workplace Well-Being and Stress Management\",\r\n    \"category\": \"Well-Being\",\r\n    \"description\": \"Learn practical strategies for managing workplace stress, maintaining work-life balance, and developing healthy professional habits.\",\r\n    \"lessons\": [\r\n        {\r\n            \"title\": \"Understanding Workplace Stress\",\r\n            \"duration_minutes\": 30\r\n        },\r\n        {\r\n            \"title\": \"Stress Management Techniques\",\r\n            \"duration_minutes\": 45\r\n        },\r\n        {\r\n            \"title\": \"Maintaining Work-Life Balance\",\r\n            \"duration_minutes\": 40\r\n        }\r\n    ]\r\n}', '2026-08-28 14:01:07'),
(11, 11, 1, '{\"title\":\"Effective Workplace Communication\",\"description\":\"sample\",\"category\":\"Technical Skills\",\"start_date\":\"2026-11-28\",\"enrollment_deadline\":\"2026-10-28\",\"instructor_id\":6,\"co_instructors\":[\"5\",\"7\"],\"skills\":[\"2\"],\"lessons\":{\"0\":{\"title\":\"sample\"},\"${lessonIndex}\":{\"duration_minutes\":\"150\"}}}', '2026-08-28 16:33:13'),
(12, 12, 1, '{\"title\":\"Digital Literacy in the Workplace\",\"description\":\"sample\",\"category\":\"Customer Service\",\"start_date\":\"2026-11-28\",\"enrollment_deadline\":\"2026-10-28\",\"instructor_id\":6,\"co_instructors\":[\"6\"],\"skills\":[\"2\"],\"lessons\":{\"0\":{\"title\":\"sample\"},\"${lessonIndex}\":{\"duration_minutes\":\"300\"}}}', '2026-08-28 16:35:19'),
(13, 13, 1, '{\"title\":\"Effective Workplace Communication\",\"description\":\"sample\",\"category\":\"Compliance\",\"start_date\":\"2026-11-28\",\"enrollment_deadline\":\"2026-10-28\",\"instructor_id\":4,\"co_instructors\":[\"5\"],\"skills\":[\"1\"],\"lessons\":{\"0\":{\"title\":\"sample\"},\"${lessonIndex}\":{\"duration_minutes\":\"120\"}}}', '2026-08-28 16:43:09'),
(14, 14, 1, '{\"title\":\"Data Analysis Fundamentals\",\"description\":\"sample\",\"thumbnail_path\":\"assets/uploads/learning/course_20260828_184837_0049f703db.jpg\",\"category\":\"Compliance\",\"start_date\":\"2026-11-28\",\"enrollment_deadline\":\"2026-10-28\",\"instructor_id\":5,\"co_instructors\":[\"5\"],\"skills\":[\"8\"],\"lessons\":{\"0\":{\"title\":\"sample\"},\"${lessonIndex}\":{\"duration_minutes\":\"90\"}}}', '2026-08-28 16:48:37'),
(15, 1, 2, '{\"title\":\"Effective Workplace Communication\",\"description\":\"Develop professional communication skills for communicating clearly and effectively with colleagues, supervisors, and clients.\",\"category\":\"Communication\",\"status\":\"active\",\"start_date\":\"2026-09-07\",\"enrollment_deadline\":\"2026-09-04\",\"instructor_id\":1,\"co_instructors\":[\"2\"],\"skills\":[\"1\",\"2\",\"3\"],\"lessons\":[{\"title\":\"Communication Fundamentals\",\"duration_minutes\":\"30\"}],\"thumbnail_path\":\"uploads/courses/workplace-communication.jpg\"}', '2026-08-29 09:32:31'),
(16, 1, 3, '{\"title\":\"Effective Workplace Communication\",\"description\":\"Develop professional communication skills for communicating clearly and effectively with colleagues, supervisors, and clients.\",\"category\":\"Communication\",\"status\":\"active\",\"start_date\":\"2026-09-07\",\"enrollment_deadline\":\"2026-09-04\",\"instructor_id\":1,\"co_instructors\":[\"2\",\"8\"],\"skills\":[\"1\",\"2\",\"3\"],\"lessons\":[{\"title\":\"Communication Fundamentals\",\"duration_minutes\":\"30\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_113305_42333b0e9a.jpg\"}', '2026-08-29 09:33:05'),
(17, 2, 2, '{\"title\":\"Time Management and Productivity\",\"description\":\"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\"category\":\"Productivity\",\"status\":\"active\",\"start_date\":\"2026-09-09\",\"enrollment_deadline\":\"2026-09-06\",\"instructor_id\":2,\"co_instructors\":[\"3\"],\"skills\":[\"4\",\"5\",\"6\"],\"lessons\":[{\"title\":\"Understanding Time Management\",\"duration_minutes\":\"30\"},{\"title\":\"Prioritizing Tasks\",\"duration_minutes\":\"180\"},{\"title\":\"Productivity Techniques\",\"duration_minutes\":\"45\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_114638_a306add0dd.jpg\"}', '2026-08-29 09:46:38'),
(18, 2, 3, '{\"title\":\"Time Management and Productivity\",\"description\":\"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\"category\":\"Productivity\",\"status\":\"active\",\"start_date\":\"2026-09-09\",\"enrollment_deadline\":\"2026-09-06\",\"instructor_id\":2,\"co_instructors\":[\"3\"],\"skills\":[\"4\",\"5\",\"6\"],\"lessons\":[{\"title\":\"Understanding Time Management\",\"duration_minutes\":\"30\"},{\"title\":\"Prioritizing Tasks\",\"duration_minutes\":\"60\"},{\"title\":\"Productivity Techniques\",\"duration_minutes\":\"45\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_115529_1ed358fae5.png\"}', '2026-08-29 09:55:29'),
(19, 2, 4, '{\"title\":\"Time Management and Productivity\",\"description\":\"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\"category\":\"Productivity\",\"status\":\"active\",\"start_date\":\"2026-09-09\",\"enrollment_deadline\":\"2026-09-06\",\"instructor_id\":2,\"co_instructors\":[\"3\"],\"skills\":[\"4\",\"5\",\"6\"],\"lessons\":[{\"title\":\"Understanding Time Management\",\"duration_minutes\":\"180\"},{\"title\":\"Prioritizing Tasks\",\"duration_minutes\":\"150\"},{\"title\":\"Productivity Techniques\",\"duration_minutes\":\"45\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_115529_1ed358fae5.png\"}', '2026-08-29 09:55:47'),
(20, 2, 5, '{\"title\":\"Time Management and Productivity\",\"description\":\"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\"category\":\"Productivity\",\"status\":\"archived\",\"start_date\":\"2026-09-09\",\"enrollment_deadline\":\"2026-09-06\",\"instructor_id\":2,\"co_instructors\":[\"3\"],\"skills\":[\"4\",\"5\",\"6\"],\"lessons\":[{\"title\":\"Understanding Time Management\",\"duration_minutes\":\"30\"},{\"title\":\"Prioritizing Tasks\",\"duration_minutes\":\"180\"},{\"title\":\"Productivity Techniques\",\"duration_minutes\":\"45\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_115529_1ed358fae5.png\"}', '2026-08-29 10:03:12'),
(21, 2, 6, '{\"title\":\"Time Management and Productivity\",\"description\":\"Learn practical techniques for prioritizing tasks, managing deadlines, reducing procrastination, and improving workplace productivity.\",\"category\":\"Productivity\",\"status\":\"draft\",\"start_date\":\"2026-09-09\",\"enrollment_deadline\":\"2026-09-06\",\"instructor_id\":2,\"co_instructors\":[\"3\"],\"skills\":[\"4\",\"5\",\"6\"],\"lessons\":[{\"title\":\"Understanding Time Management\",\"duration_minutes\":\"30\"},{\"title\":\"Prioritizing Tasks\",\"duration_minutes\":\"180\"},{\"title\":\"Productivity Techniques\",\"duration_minutes\":\"45\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_115529_1ed358fae5.png\"}', '2026-08-29 10:03:32'),
(22, 3, 2, '{\"title\":\"Leadership Essentials\",\"description\":\"Learn the fundamentals of effective leadership including decision-making, delegation, motivation, accountability, and team management.\",\"category\":\"Leadership\",\"status\":\"active\",\"start_date\":\"2026-09-14\",\"enrollment_deadline\":\"2026-09-11\",\"instructor_id\":3,\"co_instructors\":[\"4\"],\"skills\":[\"7\",\"8\",\"9\"],\"lessons\":[{\"title\":\"Introduction to Leadership\",\"duration_minutes\":\"360\"},{\"title\":\"Decision Making\",\"duration_minutes\":\"45\"},{\"title\":\"Motivating Teams\",\"duration_minutes\":\"240\"}],\"thumbnail_path\":\"assets/uploads/learning/course_20260829_120838_6b5ceb7e3e.png\"}', '2026-08-29 10:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `ld_skill`
--

CREATE TABLE `ld_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `suggested` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_skill`
--

INSERT INTO `ld_skill` (`id`, `name`, `description`, `date_updated`, `suggested`, `status`, `created_at`) VALUES
(1, 'Communication', 'Ability to communicate clearly and effectively with others.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(2, 'Leadership', 'Ability to guide, motivate, and manage individuals or teams.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(3, 'Time Management', 'Ability to organize tasks and manage time efficiently.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(4, 'Problem Solving', 'Ability to identify problems and develop effective solutions.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(5, 'Critical Thinking', 'Ability to analyze information and make logical decisions.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(6, 'Teamwork', 'Ability to collaborate effectively with team members.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(7, 'Adaptability', 'Ability to adjust effectively to changing situations and requirements.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(8, 'Project Management', 'Ability to plan, organize, and execute projects successfully.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(9, 'Conflict Resolution', 'Ability to handle and resolve workplace conflicts professionally.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(10, 'Decision Making', 'Ability to evaluate options and make effective decisions.', '2026-08-29 10:23:09', 1, 'active', '2026-08-29 10:23:09'),
(11, 'Customer Service', 'Ability to provide professional and effective customer support.', '2026-08-29 10:23:09', 0, 'active', '2026-08-29 10:23:09'),
(12, 'Technical Writing', 'Ability to create clear and structured technical documentation.', '2026-08-29 10:23:09', 0, 'active', '2026-08-29 10:23:09'),
(13, 'Presentation Skills', 'Ability to confidently present ideas and information to an audience.', '2026-08-29 10:23:09', 0, 'active', '2026-08-29 10:23:09'),
(14, 'Emotional Intelligence', 'Ability to understand and manage emotions in professional interactions.', '2026-08-29 10:23:09', 0, 'active', '2026-08-29 10:23:09'),
(15, 'Creativity', 'Ability to generate innovative ideas and approaches to tasks.', '2026-08-29 10:23:09', 0, 'active', '2026-08-29 10:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `pm_feedback_360_entries`
--

CREATE TABLE `pm_feedback_360_entries` (
  `feedback_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reviewer_type` varchar(30) NOT NULL,
  `reviewer_name` varchar(120) NOT NULL,
  `reviewer_id` int(10) DEFAULT NULL,
  `category` varchar(80) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comments` text DEFAULT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `feedback_status` varchar(30) DEFAULT 'Pending',
  `feedback_category` varchar(80) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `recommendation` varchar(80) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `supporting_documents` varchar(255) DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `overall_rating` decimal(3,2) DEFAULT NULL,
  `competency_scores` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pm_feedback_360_entries`
--

INSERT INTO `pm_feedback_360_entries` (`feedback_id`, `employee_id`, `reviewer_type`, `reviewer_name`, `reviewer_id`, `category`, `rating`, `comments`, `review_period`, `department`, `feedback_status`, `feedback_category`, `strengths`, `areas_for_improvement`, `recommendation`, `is_anonymous`, `supporting_documents`, `hr_remarks`, `overall_rating`, `competency_scores`, `created_at`, `updated_at`) VALUES
(1, 1, 'Supervisor', 'Juan Dela Cruz', 2, 'Performance', 4, 'Demonstrates strong performance and consistently completes assigned tasks.', '2026-Q3', 'IT DEPARTMENT', 'Submitted', 'Performance', 'Strong technical skills and good teamwork.', 'Improve documentation and time management.', 'Continue professional development and technical training.', 0, NULL, NULL, 4.00, '{\"communication\":4,\"teamwork\":5,\"technical_skills\":4,\"problem_solving\":4}', '2026-08-25 16:28:55', '2026-08-25 16:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `pr_payslips`
--

CREATE TABLE `pr_payslips` (
  `payslip_id` int(11) NOT NULL,
  `run_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `gross_pay` decimal(10,2) DEFAULT NULL,
  `total_deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_exit_settlement` tinyint(1) DEFAULT 0 COMMENT 'Flag: 1 if this is an exit/final payslip',
  `settlement_id` int(11) DEFAULT NULL COMMENT 'Links to exit_employee_settlements.id',
  `resignation_id` int(11) DEFAULT NULL COMMENT 'Links to exit_resignations.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_payslips`
--

INSERT INTO `pr_payslips` (`payslip_id`, `run_id`, `employee_id`, `gross_pay`, `total_deductions`, `net_pay`, `generated_at`, `is_exit_settlement`, `settlement_id`, `resignation_id`) VALUES
(1, 3, 1, 17500.00, 320.00, 17180.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(2, 3, 1, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(3, 3, 33, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(4, 3, 30, 16000.00, 1569.00, 14431.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(5, 3, 2, 12500.00, 1619.33, 10880.67, '2026-08-16 23:15:53', 0, NULL, NULL),
(6, 3, 39, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(7, 3, 3, 15000.00, 1000.00, 14000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(8, 3, 29, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ta_attendance`
--

CREATE TABLE `ta_attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `recorded_by` enum('MANUAL','QR','SYSTEM') NOT NULL DEFAULT 'MANUAL',
  `status` enum('PRESENT','ABSENT','LATE','EARLY_OUT','ON_LEAVE','PENDING_APPROVAL') DEFAULT 'PENDING_APPROVAL',
  `leave_request_id` int(11) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approval_remarks` varchar(255) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_hours_worked` decimal(5,2) DEFAULT NULL,
  `regular_hours` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `is_within_time_window` tinyint(1) DEFAULT 1,
  `is_within_timeout_window` tinyint(1) DEFAULT 1,
  `is_within_shift_hours` tinyint(1) DEFAULT 1,
  `late_minutes` int(11) DEFAULT 0 COMMENT 'Number of minutes employee was late (0 if on time)',
  `early_out_minutes` int(11) DEFAULT 0 COMMENT 'Number of minutes employee left early (0 if on time)',
  `shift_minutes` int(11) DEFAULT 0 COMMENT 'Expected shift duration in minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_attendance`
--

INSERT INTO `ta_attendance` (`attendance_id`, `employee_id`, `shift_id`, `attendance_date`, `time_in`, `time_out`, `recorded_by`, `status`, `leave_request_id`, `is_approved`, `approved_by`, `approval_remarks`, `approved_at`, `created_at`, `updated_at`, `total_hours_worked`, `regular_hours`, `overtime_hours`, `is_within_time_window`, `is_within_timeout_window`, `is_within_shift_hours`, `late_minutes`, `early_out_minutes`, `shift_minutes`) VALUES
(1, 1, NULL, '2026-07-30', '2026-07-30 16:40:11', '2026-07-30 16:40:14', 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-07-30 08:40:11', '2026-08-13 11:29:08', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 0),
(2, 1, NULL, '2026-08-02', '2026-08-02 13:04:32', '2026-08-02 14:36:07', 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-08-02 05:04:32', '2026-08-13 11:29:11', 1.53, 1.53, 0.00, 1, 1, 1, 0, 0, 0),
(3, 1, NULL, '2026-08-03', '2026-08-03 01:04:25', '2026-08-03 20:43:11', 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-08-02 17:04:25', '2026-08-13 11:29:13', 19.65, 8.00, 11.65, 1, 1, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ta_leave_requests`
--

CREATE TABLE `ta_leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `details` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL COMMENT 'Detailed reason for leave request',
  `supporting_document` varchar(255) DEFAULT NULL,
  `documents` longtext DEFAULT NULL COMMENT 'JSON array of uploaded document file paths',
  `document_uploaded_at` timestamp NULL DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `balance_deducted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_leave_requests`
--

INSERT INTO `ta_leave_requests` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `date_submitted`, `updated_at`, `status`, `details`, `reason`, `supporting_document`, `documents`, `document_uploaded_at`, `reject_reason`, `balance_deducted`) VALUES
(1, 7, 4, '2026-04-02', '2026-04-03', '2026-04-01 19:59:51', NULL, 'Approved', 'sample', NULL, NULL, NULL, NULL, NULL, 0),
(2, 7, 1, '2026-04-04', '2026-04-05', '2026-04-03 21:16:06', NULL, 'Pending', 'sample', NULL, NULL, NULL, NULL, NULL, 0),
(3, 7, 4, '2026-04-04', '2026-04-05', '2026-04-04 00:50:24', NULL, 'Pending', 'Sample', NULL, NULL, NULL, NULL, NULL, 0),
(4, 7, 7, '2026-04-05', '2026-04-06', '2026-04-04 22:17:18', NULL, 'Pending', 'sample', NULL, NULL, NULL, NULL, NULL, 0),
(5, 6, 5, '2026-04-05', '2026-04-06', '2026-04-04 22:36:25', NULL, 'Approved', 'tetsting', NULL, NULL, NULL, NULL, NULL, 0),
(6, 7, 6, '2026-04-05', '2026-04-06', '2026-04-05 05:30:48', NULL, 'Pending', 'Sample', NULL, NULL, NULL, NULL, NULL, 0),
(7, 7, 5, '2026-04-12', '2026-04-13', '2026-04-11 23:30:26', '2026-07-30 21:22:13', 'Approved', 'I need to accompany my wife during her labor', NULL, NULL, NULL, NULL, NULL, 0),
(8, 7, 3, '2026-07-13', '2026-07-14', '2026-07-13 05:03:25', '2026-07-30 21:22:11', 'Rejected', 'emergency', NULL, NULL, NULL, NULL, 'sample', 0),
(9, 16, 1, '2026-07-31', '2026-08-01', '2026-07-30 20:33:27', '2026-07-30 21:20:31', 'Rejected', 'sample', NULL, NULL, NULL, NULL, 'sample', 0),
(10, 14, 1, '2026-08-02', '2026-08-03', '2026-08-02 01:02:13', NULL, 'Pending', 'sample', NULL, NULL, NULL, NULL, NULL, 0),
(11, 1, 2, '2026-08-18', '2026-08-20', '2026-08-17 02:26:31', '2026-08-25 18:10:30', 'Rejected', 'sample', NULL, NULL, NULL, NULL, 'Conflicting Work Schedule', 0),
(12, 1, 5, '2026-08-18', '2026-08-19', '2026-08-17 03:48:30', '2026-08-17 04:45:14', 'Rejected', 'sample', NULL, 'leave_1_20260817054830_4dde1bdbdf.pdf', NULL, NULL, 'Incomplete Leave Documentation Sample', 0),
(13, 1, 1, '2026-08-19', '2026-08-20', '2026-08-17 03:53:55', NULL, 'Cancelled', 'sample', NULL, 'leave_1_20260817055355_b8558fb35f.pdf', NULL, NULL, NULL, 0),
(14, 0, 6, '2026-08-19', '2026-08-20', '2026-08-18 15:50:19', NULL, 'Pending', 'sample', NULL, 'leave_0_20260818175019_8370593f5a.pdf', NULL, NULL, NULL, 0),
(15, 1, 6, '2026-08-25', '2026-08-27', '2026-08-25 15:37:39', NULL, 'Approved', 'sample', NULL, 'leave_1_20260825173739_3b74ac2f75.jpeg', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ta_leave_types`
--

CREATE TABLE `ta_leave_types` (
  `leave_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `days_per_year` int(11) NOT NULL DEFAULT 10,
  `is_deductible` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_leave_types`
--

INSERT INTO `ta_leave_types` (`leave_type_id`, `leave_type_name`, `description`, `days_per_year`, `is_deductible`, `requires_approval`, `created_at`) VALUES
(1, 'Sick Leave', 'Leave taken when an employee is unable to work due to illness or medical reasons.', 3, 1, 1, '2026-02-09 08:00:00'),
(2, 'Vacation Leave', 'Leave taken for vacation, rest, travel, or personal time away from work.', 4, 1, 1, '2026-02-14 08:00:00'),
(3, 'Emergency Leave', 'Leave granted when an employee needs to attend to an unexpected or urgent personal matter.', 1, 1, 1, '2026-02-02 08:00:00'),
(4, 'Maternity Leave', 'Leave granted to an employee for childbirth, recovery, and related maternity needs.', 105, 0, 1, '2026-02-19 08:00:00'),
(5, 'Paternity Leave', 'Leave granted to a father following the birth of a child to provide care and support.', 7, 0, 1, '2026-02-20 08:00:00'),
(6, 'Bereavement Leave', 'Leave granted when an employee experiences the death of an immediate family member.', 5, 0, 1, '2026-02-21 08:00:00'),
(7, 'Study Leave', 'Leave granted to an employee for educational, academic, or study-related purposes.', 10, 1, 1, '2026-02-22 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

CREATE TABLE `user_account` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  `profile_pic` varchar(255) DEFAULT NULL,
  `account_status` enum('Active','Inactive','Locked') NOT NULL DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`user_id`, `employee_id`, `role_id`, `password`, `theme`, `profile_pic`, `account_status`, `last_login`, `password_changed_at`, `failed_login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 29, 2, '$2y$10$q5/aXH6szEDXVM1rrAgd..BpDjNQCNfjv/bJc/xRR/iWRiLIDypfq', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:36:10'),
(2, 30, 4, '$2y$10$dkxxQqySBw.Xj7ynHFVUTugipnIcOsSw9xW9Bkmu1mD6BON74xEJ2', 'light', NULL, 'Active', '2026-08-15 17:03:03', NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-15 17:03:03'),
(3, 31, 5, '$2y$10$E6IfBsx8oYjxiESpMwsiJeIX83m5mRZXW0REze1.YWGohDK5j2Wm.', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:37:10'),
(4, 32, 3, '$2y$10$xO/C3.Yk4zkdzaN/iEyxSu2ewtzq6.gJj25pAV3URbF4hD8NY7Fke', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:37:35'),
(5, 33, 8, '$2y$10$8yp86m7jO2S8NPM3xTMuyuobAAb4NGsoLxtmv4jpJly2bWcjaYSJi', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:38:09'),
(6, 34, 9, '$2y$10$ridpEYIFsgjdYqFI3VTKF.MjSf8GX1H.XrzE/4UxmwWQXkJWHbkh6', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:38:34'),
(7, 35, 7, '$2y$10$pZWeY6ODmnwb4VJuxQiCzOMBL4E42/iqg1Xc1tfCQ3IGFdhJ4O112', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:38:58'),
(8, 36, 6, '$2y$10$aQ9sEVmIz.cbA5j5eYEIT.r2lCUkfrd6I8X.LY/BLkuo/TUK1Mce6', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:39:33'),
(9, 37, 12, '$2y$10$88ez1flvZDHzMKv/jQdxE.HbveLfEt6x5fEDGjfX/T0GEqPT8iN0G', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:40:11'),
(10, 38, 10, '$2y$10$DQB1tiH4sQ2RE7yWxOcz3OLUwoKvO6SghNeaDmT0.1nfJpJv/zJWG', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:40:40'),
(11, 39, 11, '$2y$10$Htc.AaV0g3yW1hrOtux6fu1oOXiGgxz5WctWPfE/CjccN3EPwtYlG', 'light', NULL, 'Active', NULL, NULL, 0, NULL, '2026-08-14 08:35:08', '2026-08-14 08:41:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  ADD PRIMARY KEY (`eer_announcements_id`);

--
-- Indexes for table `eer_grievances`
--
ALTER TABLE `eer_grievances`
  ADD PRIMARY KEY (`eer_grievance_id`);

--
-- Indexes for table `em_departments`
--
ALTER TABLE `em_departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `em_employees`
--
ALTER TABLE `em_employees`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `em_positions`
--
ALTER TABLE `em_positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `ep_benefits_and_government_contribution`
--
ALTER TABLE `ep_benefits_and_government_contribution`
  ADD PRIMARY KEY (`benefit_id`);

--
-- Indexes for table `ep_notifications`
--
ALTER TABLE `ep_notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `ep_notification_recipients`
--
ALTER TABLE `ep_notification_recipients`
  ADD PRIMARY KEY (`recipient_id`);

--
-- Indexes for table `ep_online_meetings`
--
ALTER TABLE `ep_online_meetings`
  ADD PRIMARY KEY (`meetings_id`);

--
-- Indexes for table `ep_payroll_request`
--
ALTER TABLE `ep_payroll_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ep_resignation_requests`
--
ALTER TABLE `ep_resignation_requests`
  ADD PRIMARY KEY (`resignation_id`);

--
-- Indexes for table `ep_users`
--
ALTER TABLE `ep_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lc_incidents`
--
ALTER TABLE `lc_incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course`
--
ALTER TABLE `ld_course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course_instructor`
--
ALTER TABLE `ld_course_instructor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_skill`
--
ALTER TABLE `ld_skill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pm_feedback_360_entries`
--
ALTER TABLE `pm_feedback_360_entries`
  ADD PRIMARY KEY (`feedback_id`);

--
-- Indexes for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  ADD PRIMARY KEY (`payslip_id`);

--
-- Indexes for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `ta_leave_requests`
--
ALTER TABLE `ta_leave_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ta_leave_types`
--
ALTER TABLE `ta_leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  MODIFY `eer_announcements_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `eer_grievances`
--
ALTER TABLE `eer_grievances`
  MODIFY `eer_grievance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `em_departments`
--
ALTER TABLE `em_departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `em_employees`
--
ALTER TABLE `em_employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `em_positions`
--
ALTER TABLE `em_positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `ep_benefits_and_government_contribution`
--
ALTER TABLE `ep_benefits_and_government_contribution`
  MODIFY `benefit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ep_notifications`
--
ALTER TABLE `ep_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `ep_notification_recipients`
--
ALTER TABLE `ep_notification_recipients`
  MODIFY `recipient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `ep_online_meetings`
--
ALTER TABLE `ep_online_meetings`
  MODIFY `meetings_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ep_payroll_request`
--
ALTER TABLE `ep_payroll_request`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ep_resignation_requests`
--
ALTER TABLE `ep_resignation_requests`
  MODIFY `resignation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ep_users`
--
ALTER TABLE `ep_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lc_incidents`
--
ALTER TABLE `lc_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ld_course`
--
ALTER TABLE `ld_course`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ld_course_instructor`
--
ALTER TABLE `ld_course_instructor`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `ld_skill`
--
ALTER TABLE `ld_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pm_feedback_360_entries`
--
ALTER TABLE `pm_feedback_360_entries`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  MODIFY `payslip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ta_leave_requests`
--
ALTER TABLE `ta_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ta_leave_types`
--
ALTER TABLE `ta_leave_types`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
