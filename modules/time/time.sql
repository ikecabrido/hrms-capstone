-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 16, 2026 at 07:20 PM
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
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN STAFF', 'Administrative and support staff', 'Active', '2026-08-06 13:47:35', NULL),
(2, 'INSTRUCTORS', 'Teaching faculty members', 'Active', '2026-08-06 13:47:35', NULL),
(3, 'IT DEPARTMENT', 'Information Technology department', 'Active', '2026-08-06 13:47:35', NULL),
(4, 'PSYCHOLOGY DEPARTMENT', 'Psychology department', 'Active', '2026-08-06 13:47:35', NULL),
(5, 'CRIMINOLOGY DEPARTMENT', 'Criminology department', 'Active', '2026-08-06 13:47:35', NULL),
(6, 'TOURISM DEPARTMENT', 'Tourism department', 'Active', '2026-08-06 13:47:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
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
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `regular_date` date DEFAULT NULL,
  `employment_status` enum('Active','Resigned','Terminated','Probationary') DEFAULT 'Active',
  `employment_type` enum('Full-time','Part-time','Laboratory','OJT/Training') DEFAULT NULL,
  `unit_load` int(11) DEFAULT NULL,
  `graduate_level` enum('None','Masteral','Doctoral') DEFAULT 'None',
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
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_code`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `email`, `mobile_no`, `phone_no`, `current_address`, `permanent_address`, `department`, `position`, `position_id`, `hire_date`, `regular_date`, `employment_status`, `employment_type`, `unit_load`, `graduate_level`, `ranking`, `credentials`, `faculty_notes`, `negotiated_salary`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_date`) VALUES
(1, 'EMP-000001', NULL, 'Ronaldo', 'G.', 'Raymundo', NULL, 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, 'ronaldocruz22@gmail.com', '09123456789', '0287654321', 'San Jose Del Monte, Bulacan', NULL, 'IT DEPARTMENT', 'IT Staff', 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-06 13:47:35', '2026-08-07 08:33:05', 0, NULL, NULL),
(2, 'EMP-000002', NULL, 'Juan', 'Dela', 'Cruz', NULL, 'Male', '1990-05-15', NULL, NULL, NULL, NULL, 'juan.delacruz@bcp.edu.ph', '09123456789', '021234567', '123 Main St, Manila', NULL, 'Executive Administration', 'College President', NULL, '2023-01-15', NULL, 'Active', NULL, NULL, 'None', NULL, NULL, NULL, NULL, '2026-08-06 14:54:48', '2026-08-06 16:46:37', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_certifications`
--

CREATE TABLE `employee_certifications` (
  `cert_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `cert_name` varchar(100) NOT NULL,
  `issuing_organization` varchar(100) NOT NULL,
  `date_issued` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_change_history`
--

CREATE TABLE `employee_change_history` (
  `change_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `change_type` varchar(50) NOT NULL DEFAULT '',
  `user_id` int(11) DEFAULT NULL,
  `field_name` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `effective_date` date NOT NULL DEFAULT curdate(),
  `remarks` text DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_change_history`
--

INSERT INTO `employee_change_history` (`change_id`, `employee_id`, `change_type`, `user_id`, `field_name`, `old_value`, `new_value`, `effective_date`, `remarks`, `updated_by`, `ip_address`, `change_reason`, `created_at`) VALUES
(1, 1, 'Document Uploaded', NULL, '', 'N/A', 'training cert.', '2026-08-07', 'Uploaded document: training cert. (Other)', 'System', NULL, NULL, '2026-08-07 14:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `employee_dependents`
--

CREATE TABLE `employee_dependents` (
  `dependent_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `mime_type` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Other',
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_documents`
--

INSERT INTO `employee_documents` (`document_id`, `employee_id`, `document_name`, `document_type`, `file_path`, `file_name`, `file_size`, `uploaded_by`, `created_at`, `updated_at`, `mime_type`, `category`, `expiry_date`) VALUES
(1, 1, 'training cert.', 'Other', '../../assets/documents/2026/08/6a75ef357ca3a_724368498_1322572223322433_1893840578082960664_n.png', '724368498_1322572223322433_1893840578082960664_n.png', '1844150', NULL, '2026-08-07 14:44:05', NULL, NULL, 'Other', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_education`
--

CREATE TABLE `employee_education` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `field_of_study` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_emergency_contacts`
--

CREATE TABLE `employee_emergency_contacts` (
  `contact_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_languages`
--

CREATE TABLE `employee_languages` (
  `language_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `language_name` varchar(50) NOT NULL,
  `proficiency` enum('Beginner','Intermediate','Advanced','Fluent','Native') DEFAULT 'Intermediate',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `skill_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `proficiency` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Intermediate',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_work_experience`
--

CREATE TABLE `employee_work_experience` (
  `work_exp_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_history`
--

CREATE TABLE `employment_history` (
  `history_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `em_documents`
--

CREATE TABLE `em_documents` (
  `doc_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `em_education`
--

CREATE TABLE `em_education` (
  `education_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `level` enum('Elementary','High School','Senior High School','College','Masteral','Doctoral') NOT NULL,
  `school_name` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_graduated` varchar(20) DEFAULT NULL,
  `honors` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
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

INSERT INTO `em_employees` (`employee_id`, `employee_code`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `email`, `mobile_no`, `phone_no`, `current_address`, `permanent_address`, `department`, `position`, `position_id`, `hire_date`, `regular_date`, `employment_status`, `employment_type`, `unit_load`, `graduate_level`, `ranking`, `credentials`, `faculty_notes`, `negotiated_salary`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_date`) VALUES
(1, 'EMP-000001', NULL, 'Ronaldo', 'G.', 'Raymundo', NULL, 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, 'ronaldocruz22@gmail.com', '09123456789', '0287654321', 'San Jose Del Monte, Bulacan', NULL, 'IT DEPARTMENT', 'IT Staff', 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-06 13:47:35', '2026-08-13 10:53:46', 0, NULL, NULL),
(2, 'EMP-000002', NULL, 'Juan', 'Dela', 'Cruz', NULL, 'Male', '1990-05-15', NULL, NULL, NULL, NULL, 'juan.delacruz@bcp.edu.ph', '09123456789', '021234567', '123 Main St, Manila', NULL, 'IT DEPARTMENT', 'IT Staff', 2, '2023-01-15', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-06 14:54:48', '2026-08-13 10:53:59', 0, NULL, NULL),
(3, 'EMP-000003', NULL, 'Erwin', 'M.', 'De Guzman', NULL, NULL, '1995-09-18', NULL, NULL, NULL, NULL, 'erwindeguzman@gmail.com', '09123456789', '0987654321', '', NULL, 'IT DEPARTMENT', 'IT Staff', 6, '2026-08-11', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', 20000.00, '2026-08-11 19:09:33', '2026-08-13 10:54:17', 0, NULL, NULL),
(4, 'EMP-000004', NULL, 'Roberto', 'J', 'Albert', NULL, NULL, '1998-02-12', NULL, NULL, NULL, NULL, 'robert@gmail.com', '09123456789', '987654321', '', NULL, 'IT DEPARTMENT', 'IT Staff', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-13 11:01:36', '2026-08-13 11:01:36', 0, NULL, NULL),
(5, 'EMP-000005', NULL, 'Althea', 'M.', 'Santos', NULL, NULL, '1999-09-19', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '09123456789', '987654321', '', NULL, 'ADMIN STAFF', 'School Directress', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', 20000.00, '2026-08-13 11:04:21', '2026-08-13 11:04:21', 0, NULL, NULL),
(6, 'EMP-000006', NULL, 'Bianca', 'G.', 'Reyes', NULL, NULL, '1995-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '987654321', '', NULL, 'ADMIN STAFF', 'College Coordinator', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'Masteral', '', '', '', NULL, '2026-08-13 11:05:33', '2026-08-13 11:05:33', 0, NULL, NULL),
(7, 'EMP-000007', NULL, 'Chloe', 'M.', 'Cruz', NULL, NULL, '1995-02-12', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'ADMIN STAFF', 'Monitoring Staff', NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:06:40', '2026-08-13 11:06:40', 0, NULL, NULL),
(8, 'EMP-000008', NULL, 'Diana', 'G.', 'Bautista', NULL, NULL, '1995-11-11', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '', '', '', NULL, 'ADMIN STAFF', 'Laboratory Technician', NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:07:39', '2026-08-13 11:07:40', 0, NULL, NULL),
(9, 'EMP-000009', NULL, 'Elena', 'G.', 'Ocampo', NULL, NULL, '1995-06-16', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'INSTRUCTORS', 'General Education Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:08:37', '2026-08-13 11:08:37', 0, NULL, NULL),
(10, 'EMP-000010', NULL, 'Fiona', 'G.', 'Ramos', NULL, NULL, '1996-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'INSTRUCTORS', 'English Instructor', NULL, '2026-08-13', NULL, 'Active', '', NULL, 'None', '', '', '', NULL, '2026-08-13 11:09:29', '2026-08-13 11:09:29', 0, NULL, NULL),
(11, 'EMP-000011', NULL, 'Aaron', '', 'Mendoza', NULL, NULL, '1999-07-17', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'INSTRUCTORS', 'Mathematics Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:10:26', '2026-08-13 11:10:26', 0, NULL, NULL),
(12, 'EMP-000012', NULL, 'Caleb', '', 'Santos', NULL, NULL, '1990-04-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'INSTRUCTORS', 'Entrepreneurship Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:11:23', '2026-08-13 11:11:23', 0, NULL, NULL),
(13, 'EMP-000013', NULL, 'David', '', 'Aquino', NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, 'admin@hrsystem.com', '', '', '', NULL, 'PSYCHOLOGY DEPARTMENT', 'Psychology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:12:30', '2026-08-13 11:12:30', 0, NULL, NULL),
(14, 'EMP-000014', NULL, 'Ethan', '', 'Garcia', NULL, NULL, '1999-12-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'PSYCHOLOGY DEPARTMENT', 'Psychology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:13:14', '2026-08-13 11:13:14', 0, NULL, NULL),
(15, 'EMP-000015', NULL, 'Felix', '', 'Del Rosario', NULL, NULL, '2001-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'PSYCHOLOGY DEPARTMENT', 'Psychology Instructor', NULL, '2026-08-13', NULL, 'Active', 'OJT/Training', NULL, 'None', '', '', '', NULL, '2026-08-13 11:14:12', '2026-08-13 11:14:12', 0, NULL, NULL),
(16, 'EMP-000016', NULL, 'Gabriel', '', 'Gonzales', NULL, NULL, '1990-02-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'PSYCHOLOGY DEPARTMENT', 'Psychology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Part-time', 1, 'None', '', '', '', NULL, '2026-08-13 11:15:10', '2026-08-13 11:15:10', 0, NULL, NULL),
(17, 'EMP-000017', NULL, 'Hugo', '', 'Villanueva', NULL, NULL, '1999-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'CRIMINOLOGY DEPARTMENT', 'Criminology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Part-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:16:41', '2026-08-13 11:16:41', 0, NULL, NULL),
(18, 'EMP-000018', NULL, 'Ian', '', 'Fernandez', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'CRIMINOLOGY DEPARTMENT', 'Criminology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:17:19', '2026-08-13 11:17:19', 0, NULL, NULL),
(19, 'EMP-000019', NULL, 'Jacob', '', 'Lopez', NULL, NULL, '1999-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'CRIMINOLOGY DEPARTMENT', 'Criminology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:18:06', '2026-08-13 11:18:06', 0, NULL, NULL),
(20, 'EMP-000020', NULL, 'Ian', '', 'Perez', NULL, NULL, '1990-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'CRIMINOLOGY DEPARTMENT', 'Criminology Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:18:56', '2026-08-13 11:18:56', 0, NULL, NULL),
(21, 'EMP-000021', NULL, 'Gia', '', 'Valdez', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'TOURISM DEPARTMENT', 'Tourism Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:19:35', '2026-08-13 11:19:35', 0, NULL, NULL),
(22, 'EMP-000022', NULL, 'Aaron', '', 'Valdez', NULL, NULL, '1996-12-15', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'TOURISM DEPARTMENT', 'Tourism Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:20:21', '2026-08-13 11:20:21', 0, NULL, NULL),
(23, 'EMP-000023', NULL, 'Aaron', '', 'Pascual', NULL, NULL, '1999-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'TOURISM DEPARTMENT', 'Tourism Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:21:00', '2026-08-13 11:21:00', 0, NULL, NULL),
(24, 'EMP-000024', NULL, 'Iris', '', 'Soriano', NULL, NULL, '1998-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'TOURISM DEPARTMENT', 'Tourism Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:21:36', '2026-08-13 11:21:36', 0, NULL, NULL),
(25, 'EMP-000025', NULL, 'Zenith', '', 'Tolentino', NULL, NULL, '1998-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'BTVTED', 'College Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:22:23', '2026-08-13 11:22:23', 0, NULL, NULL),
(26, 'EMP-000026', NULL, 'Lumina', '', 'Tolentino', NULL, NULL, '1999-10-20', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'BTVTED', 'Assistant Professor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:22:59', '2026-08-13 11:22:59', 0, NULL, NULL),
(27, 'EMP-000027', NULL, 'Vibe', '', 'Mercado', NULL, NULL, '1999-08-08', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'BTVTED', 'Associate Professor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:23:32', '2026-08-13 11:23:32', 0, NULL, NULL),
(28, 'EMP-000028', NULL, 'Diana', '', 'Mercado', NULL, NULL, '1990-09-19', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 'BTVTED', 'Technical Instructor', NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:24:04', '2026-08-13 11:24:04', 0, NULL, NULL);

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
(2, 'College Coordinator', 2, 1, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
(3, 'HR Officer', 1, 1, 'Active', '2026-08-06 13:47:35', NULL),
(4, 'HR Staff', 2, 1, 'Active', '2026-08-06 13:47:35', '2026-08-11 18:20:50'),
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
-- Table structure for table `em_users`
--

CREATE TABLE `em_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','hr','employee') DEFAULT 'employee',
  `theme` enum('light','dark') DEFAULT 'light',
  `employee_id` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `account_status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_users`
--

INSERT INTO `em_users` (`user_id`, `username`, `password`, `role`, `theme`, `employee_id`, `profile_pic`, `account_status`, `last_login`, `password_changed_at`, `failed_login_attempts`, `created_at`) VALUES
(1, 'admin', '$2y$10$NamPkg2msMgDuc6CLVq/Y.1ezlD1yYvhrVke4tBLHKe2e1nVvihBa', 'admin', 'light', '1', NULL, 'Active', NULL, '2026-08-07 06:20:30', 0, '2026-08-06 13:47:35'),
(2, 'hr_employee', '$2y$10$eJUKXrh8r9ay2zZIK6OYYu.QDZqJ5IX4SXZlYt0g.qUH9cN9O7Hd6', 'hr', 'light', NULL, NULL, 'Active', NULL, NULL, 0, '2026-08-07 07:41:26');

-- --------------------------------------------------------

--
-- Table structure for table `exit_documents`
--

CREATE TABLE `exit_documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL,
  `document_type` enum('resignation_letter','clearance_form','handover_document','certificate','settlement_receipt','exit_interview','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` bigint(20) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_employee_settlements`
--

CREATE TABLE `exit_employee_settlements` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `resignation_id` int(11) DEFAULT NULL,
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `remaining_salary` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `holiday_pay` decimal(10,2) DEFAULT 0.00,
  `bonuses` decimal(10,2) DEFAULT 0.00,
  `commission` decimal(10,2) DEFAULT 0.00,
  `hra` decimal(10,2) DEFAULT 0.00,
  `conveyance` decimal(10,2) DEFAULT 0.00,
  `lta` decimal(10,2) DEFAULT 0.00,
  `medical_allowance` decimal(10,2) DEFAULT 0.00,
  `other_allowances` decimal(10,2) DEFAULT 0.00,
  `separation_pay` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `sss` decimal(10,2) DEFAULT 0.00,
  `philhealth` decimal(10,2) DEFAULT 0.00,
  `pagibig` decimal(10,2) DEFAULT 0.00,
  `cash_advance` decimal(10,2) DEFAULT 0.00,
  `company_loan` decimal(10,2) DEFAULT 0.00,
  `equipment_damage` decimal(10,2) DEFAULT 0.00,
  `missing_assets` decimal(10,2) DEFAULT 0.00,
  `late_deductions` decimal(10,2) DEFAULT 0.00,
  `absence_deductions` decimal(10,2) DEFAULT 0.00,
  `provident_fund` decimal(10,2) DEFAULT 0.00,
  `gratuity` decimal(10,2) DEFAULT 0.00,
  `notice_pay` decimal(10,2) DEFAULT 0.00,
  `outstanding_loans` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `net_payable` decimal(10,2) NOT NULL,
  `settlement_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','paid','rejected') DEFAULT 'draft',
  `approved_by` bigint(20) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interviews`
--

CREATE TABLE `exit_interviews` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_case_type` enum('resignation','termination') NOT NULL,
  `exit_case_id` int(11) NOT NULL,
  `interviewer_id` bigint(20) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `location` varchar(255) DEFAULT 'Virtual',
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `completed_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interview_feedback`
--

CREATE TABLE `exit_interview_feedback` (
  `id` int(11) NOT NULL,
  `interview_id` int(11) NOT NULL,
  `overall_satisfaction` tinyint(1) NOT NULL,
  `work_environment_rating` tinyint(1) NOT NULL,
  `management_rating` tinyint(1) NOT NULL,
  `compensation_rating` tinyint(1) NOT NULL,
  `work_life_balance_rating` tinyint(1) NOT NULL,
  `reason_for_leaving` text NOT NULL,
  `suggestions` text DEFAULT NULL,
  `would_recommend` enum('yes','no') NOT NULL,
  `additional_comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_knowledge_transfer_items`
--

CREATE TABLE `exit_knowledge_transfer_items` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `item_type` enum('document','process','contact','system','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_knowledge_transfer_plans`
--

CREATE TABLE `exit_knowledge_transfer_plans` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `successor_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_resignations`
--

CREATE TABLE `exit_resignations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `resignation_type` enum('voluntary','involuntary') NOT NULL,
  `reason` text NOT NULL,
  `notice_date` date NOT NULL,
  `last_working_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `resignation_letter_path` varchar(500) DEFAULT NULL,
  `submitted_by` bigint(20) DEFAULT NULL,
  `preclearance_desk_person` bigint(20) DEFAULT NULL,
  `status` enum('pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn') DEFAULT 'pending_review',
  `approved_by` bigint(20) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `hr_approved_by` bigint(20) DEFAULT NULL,
  `hr_approved_at` datetime DEFAULT NULL,
  `hr_approval_comments` text DEFAULT NULL,
  `reviewed_by` bigint(20) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `legal_approved_by` bigint(20) DEFAULT NULL,
  `legal_approved_at` datetime DEFAULT NULL,
  `legal_approval_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_surveys`
--

CREATE TABLE `exit_surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_audience` enum('all','voluntary','involuntary') DEFAULT 'all',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_survey_answers`
--

CREATE TABLE `exit_survey_answers` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_survey_questions`
--

CREATE TABLE `exit_survey_questions` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','textarea','radio','checkbox','select','rating') NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `required` tinyint(1) DEFAULT 0,
  `order_num` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_survey_responses`
--

CREATE TABLE `exit_survey_responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL,
  `survey_type` varchar(100) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_terminations`
--

CREATE TABLE `exit_terminations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `termination_reason` text NOT NULL,
  `effective_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `submitted_by` bigint(20) DEFAULT NULL,
  `status` enum('pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn') DEFAULT 'pending_review',
  `reviewed_by` bigint(20) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `legal_approved_by` bigint(20) DEFAULT NULL,
  `legal_approved_at` datetime DEFAULT NULL,
  `legal_approval_comments` text DEFAULT NULL,
  `approved_by` bigint(20) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_background`
--

CREATE TABLE `family_background` (
  `family_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `number_of_children` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `government_ids`
--

CREATE TABLE `government_ids` (
  `gov_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sss_no` varchar(50) DEFAULT NULL,
  `philhealth_no` varchar(50) DEFAULT NULL,
  `pagibig_no` varchar(50) DEFAULT NULL,
  `tin_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(25, 'Employee Engagement', NULL);

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
(1014, 'Jose Mari Rich', 'Alicante', 'Malana', 4, 'active', '2026-08-09', 23, 18, 50),
(1015, 'Russell', NULL, 'Placer', 2, 'active', '2026-08-09', 24, 16, 44),
(1016, 'Cheska', 'Bautista', 'Jalotjot', 7, 'active', '2026-08-09', 25, 21, 45),
(1017, 'Jayson', NULL, 'Paigma', 8, 'active', '2026-08-09', 26, 24, 46),
(1018, 'Rainiel', NULL, 'Quebada', 6, 'active', '2026-08-09', 27, 20, 47),
(1019, 'Karl', NULL, 'Solis', 5, 'active', '2026-08-09', 28, 19, 48),
(1020, 'Geoffrey', 'Cabanag', 'Balansag', 11, 'active', '2026-08-09', 29, 25, 51),
(1021, 'Johnloyd', 'Tinio', 'Reyes', 9, 'active', '2026-08-09', 30, 22, 49),
(1022, 'Alexis', NULL, 'Cueto', 10, 'active', '2026-08-09', 31, 23, 52);

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
(52, 'Clinic', 23);

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
(5, 'Peformance', 19),
(6, 'Learning', 20),
(7, 'Compliance', 21),
(8, 'Workforce', 24),
(9, 'Exit', 22),
(10, 'Clinic', 23),
(11, 'Employee Engagement', 25);

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Sick Leave','Vacation Leave','Emergency Leave','Maternity Leave','Paternity Leave','Bereavement Leave','Other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `overtime_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_clearances`
--

CREATE TABLE `payroll_clearances` (
  `id` int(11) NOT NULL,
  `settlement_id` int(11) NOT NULL,
  `requested_by` bigint(20) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payroll clearance requests linked to exit settlements';

-- --------------------------------------------------------

--
-- Table structure for table `personal_information`
--

CREATE TABLE `personal_information` (
  `personal_info_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed','Separated') DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_information`
--

INSERT INTO `personal_information` (`personal_info_id`, `employee_id`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `blood_type`, `height`, `weight`, `spouse_name`, `spouse_occupation`, `father_name`, `father_occupation`, `mother_name`, `mother_occupation`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_number`, `created_at`, `updated_at`) VALUES
(1, 1, '1995-01-02', NULL, 'Single', 'Filipino', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:47:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_name`, `department_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'School Directress', 1, 'Active', '2026-08-06 13:47:35', NULL),
(2, 'College Coordinator', 1, 'Active', '2026-08-06 13:47:35', NULL),
(3, 'HR Officer', 1, 'Active', '2026-08-06 13:47:35', NULL),
(4, 'HR Staff', 1, 'Active', '2026-08-06 13:47:35', NULL),
(5, 'Librarian', 1, 'Active', '2026-08-06 13:47:35', NULL),
(6, 'General Education Instructor', 2, 'Active', '2026-08-06 13:47:35', NULL),
(7, 'English Instructor', 2, 'Active', '2026-08-06 13:47:35', NULL),
(8, 'Mathematics Instructor', 2, 'Active', '2026-08-06 13:47:35', NULL),
(9, 'IT Staff', 3, 'Active', '2026-08-06 13:47:35', NULL),
(10, 'IT Instructor', 3, 'Active', '2026-08-06 13:47:35', NULL),
(11, 'Psychology Instructor', 4, 'Active', '2026-08-06 13:47:35', NULL),
(12, 'Criminology Instructor', 5, 'Active', '2026-08-06 13:47:35', NULL),
(13, 'Tourism Instructor', 6, 'Active', '2026-08-06 13:47:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ta_absence_late_policies`
--

CREATE TABLE `ta_absence_late_policies` (
  `policy_id` int(11) NOT NULL,
  `policy_name` varchar(100) NOT NULL,
  `max_late_per_month` int(11) DEFAULT 3,
  `max_absent_per_month` int(11) DEFAULT 2,
  `max_excused_absent_per_month` int(11) DEFAULT 2,
  `max_excused_late_per_month` int(11) DEFAULT 5,
  `warning_after_late_count` int(11) DEFAULT 5,
  `warning_after_absent_count` int(11) DEFAULT 3,
  `late_threshold_minutes` int(11) DEFAULT 15,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_absence_late_policies`
--

INSERT INTO `ta_absence_late_policies` (`policy_id`, `policy_name`, `max_late_per_month`, `max_absent_per_month`, `max_excused_absent_per_month`, `max_excused_late_per_month`, `warning_after_late_count`, `warning_after_absent_count`, `late_threshold_minutes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Company Policy', 3, 2, 2, 5, 5, 3, 15, 1, '2026-03-19 18:42:28', '2026-03-19 18:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `ta_absence_late_records`
--

CREATE TABLE `ta_absence_late_records` (
  `record_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `absence_date` date NOT NULL,
  `type` enum('ABSENT','LATE') NOT NULL DEFAULT 'ABSENT',
  `late_minutes` int(11) DEFAULT NULL,
  `excuse_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reason` text DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ta_absence_late_records`
--

INSERT INTO `ta_absence_late_records` (`record_id`, `employee_id`, `absence_date`, `type`, `late_minutes`, `excuse_status`, `reason`, `approval_notes`, `approved_by`, `approval_date`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-04-04', 'ABSENT', NULL, 'APPROVED', 'Sick leave', NULL, NULL, '2026-04-06 10:13:59', '2026-04-06 02:08:24', '2026-04-06 02:13:59'),
(2, 1, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(3, 1, '2026-04-06', 'ABSENT', NULL, 'APPROVED', 'Doctor appointment', '', NULL, '2026-04-06 10:39:53', '2026-04-06 02:08:24', '2026-04-06 02:39:53'),
(4, 2, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(5, 2, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(6, 2, '2026-04-06', 'ABSENT', NULL, 'APPROVED', 'Doctor appointment', '', NULL, '2026-04-06 10:39:59', '2026-04-06 02:08:24', '2026-04-06 02:39:59'),
(7, 3, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(8, 3, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(9, 3, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(10, 4, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(11, 4, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(12, 4, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(13, 5, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(14, 5, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(15, 5, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:24', '2026-04-06 02:08:24'),
(16, 1, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(17, 1, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(18, 1, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(19, 2, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(20, 2, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(21, 2, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(22, 3, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(23, 3, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(24, 3, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(25, 4, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(26, 4, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(27, 4, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(28, 5, '2026-04-04', 'ABSENT', NULL, 'PENDING', 'Sick leave', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(29, 5, '2026-04-05', 'LATE', 25, 'PENDING', 'Traffic delay', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28'),
(30, 5, '2026-04-06', 'ABSENT', NULL, 'PENDING', 'Doctor appointment', NULL, NULL, NULL, '2026-04-06 02:08:28', '2026-04-06 02:08:28');

-- --------------------------------------------------------

--
-- Table structure for table `ta_absence_late_thresholds`
--

CREATE TABLE `ta_absence_late_thresholds` (
  `threshold_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `absent_count` int(11) DEFAULT 0,
  `late_count` int(11) DEFAULT 0,
  `excused_absent_count` int(11) DEFAULT 0,
  `excused_late_count` int(11) DEFAULT 0,
  `warning_level` enum('NONE','LEVEL_1','LEVEL_2','LEVEL_3') DEFAULT 'NONE',
  `warning_date` datetime DEFAULT NULL,
  `last_action_taken` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id_new` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_absence_late_thresholds`
--

INSERT INTO `ta_absence_late_thresholds` (`threshold_id`, `employee_id`, `month_year`, `absent_count`, `late_count`, `excused_absent_count`, `excused_late_count`, `warning_level`, `warning_date`, `last_action_taken`, `created_at`, `updated_at`, `employee_id_new`) VALUES
(0, 1, '2026-04', 2, 2, 2, 0, 'NONE', NULL, NULL, '2026-04-06 02:39:53', '2026-04-06 02:39:53', NULL),
(0, 2, '2026-04', 3, 2, 1, 0, 'NONE', NULL, NULL, '2026-04-06 02:39:59', '2026-04-06 02:39:59', NULL),
(0, 11, '2026-08', NULL, NULL, NULL, NULL, 'NONE', NULL, NULL, '2026-08-01 04:50:23', '2026-08-01 04:50:23', NULL),
(0, 14, '2026-08', NULL, NULL, NULL, NULL, 'NONE', NULL, NULL, '2026-08-01 04:50:28', '2026-08-01 04:50:28', NULL);

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
  `shift_minutes` int(11) DEFAULT 0 COMMENT 'Expected shift duration in minutes',
  `employee_id_new` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_attendance`
--

INSERT INTO `ta_attendance` (`attendance_id`, `employee_id`, `shift_id`, `attendance_date`, `time_in`, `time_out`, `recorded_by`, `status`, `leave_request_id`, `is_approved`, `approved_by`, `approval_remarks`, `approved_at`, `created_at`, `updated_at`, `total_hours_worked`, `regular_hours`, `overtime_hours`, `is_within_time_window`, `is_within_timeout_window`, `is_within_shift_hours`, `late_minutes`, `early_out_minutes`, `shift_minutes`, `employee_id_new`) VALUES
(1, 3, NULL, '2026-03-27', '0000-00-00 00:00:00', NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-03-27 15:17:19', '2026-04-05 20:25:17', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(2, 3, NULL, '2026-03-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'MANUAL', 'EARLY_OUT', NULL, 0, NULL, NULL, NULL, '2026-03-28 00:46:35', '2026-04-05 20:25:23', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(3, 1, NULL, '2026-03-28', '2026-03-28 09:03:23', '2026-03-28 09:03:31', 'MANUAL', 'EARLY_OUT', NULL, 0, NULL, NULL, NULL, '2026-03-28 01:01:53', '2026-04-05 20:25:26', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(4, 2, NULL, '2026-03-28', '2026-03-28 09:04:55', '2026-03-28 09:16:05', 'MANUAL', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-03-28 01:04:55', '2026-04-05 20:25:31', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(5, 6, NULL, '2026-03-28', '2026-03-28 10:59:33', NULL, 'MANUAL', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-03-28 02:59:33', '2026-04-05 20:25:35', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(6, 7, NULL, '2026-03-28', '2026-03-28 11:56:06', NULL, 'MANUAL', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-03-28 03:56:06', '2026-03-28 03:56:06', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(7, 8, NULL, '2026-03-28', '2026-03-28 17:36:20', '2026-03-28 17:37:39', 'MANUAL', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-03-28 09:36:20', '2026-04-05 20:25:40', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(8, 4, NULL, '2026-03-28', '2026-03-28 15:44:30', NULL, 'QR', 'PENDING_APPROVAL', NULL, 1, 3, '', '2026-04-06 01:57:03', '2026-03-28 14:44:30', '2026-04-05 17:57:03', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(9, 5, NULL, '2026-03-28', '2026-03-28 23:13:41', NULL, 'MANUAL', 'ON_LEAVE', NULL, 1, 3, '', '2026-04-06 00:59:43', '2026-03-28 15:13:41', '2026-04-05 20:25:44', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(10, 3, NULL, '2026-03-29', '2026-03-29 16:06:47', '2026-03-29 16:06:53', 'MANUAL', 'EARLY_OUT', NULL, 1, 3, '', '2026-04-02 19:29:21', '2026-03-29 08:06:47', '2026-04-05 20:25:50', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(11, 4, NULL, '2026-03-29', '2026-03-29 16:57:44', '2026-03-29 16:58:24', 'QR', 'PENDING_APPROVAL', NULL, 1, 3, '', '2026-04-02 19:29:18', '2026-03-29 08:57:44', '2026-04-02 11:29:18', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(12, 6, NULL, '2026-03-29', '2026-03-29 17:15:18', NULL, 'QR', 'PRESENT', NULL, 1, 3, 'awdaw', '2026-04-02 19:29:02', '2026-03-29 09:15:18', '2026-04-05 20:25:54', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(13, 5, NULL, '2026-03-30', '2026-03-30 09:49:20', NULL, 'QR', 'PENDING_APPROVAL', NULL, 1, 3, '', '2026-04-02 14:33:42', '2026-03-30 01:49:20', '2026-04-02 06:33:42', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(14, 1, 2, '2026-04-05', '2026-04-05 19:24:55', '2026-04-05 19:26:31', 'QR', 'PRESENT', NULL, 1, 3, '', '2026-04-05 19:28:24', '2026-04-05 11:24:55', '2026-04-05 11:28:24', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(15, 1, NULL, '2026-04-06', '2026-04-06 02:56:46', NULL, 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-04-05 18:56:46', '2026-04-05 18:56:46', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(16, 8, NULL, '2026-04-06', '2026-04-06 03:37:20', NULL, 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-04-05 19:37:20', '2026-04-05 19:37:20', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(300, 1, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(301, 2, 1, '2026-01-05', '2026-01-05 08:15:00', '2026-01-05 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.75, 8.00, 0.75, 1, 1, 1, 15, 0, 480, NULL),
(302, 3, 1, '2026-01-05', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 480, NULL),
(303, 4, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 16:30:00', 'QR', 'EARLY_OUT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.50, 8.00, 0.50, 1, 1, 1, 0, 30, 480, NULL),
(304, 5, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:30:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.50, 8.00, 1.50, 1, 1, 1, 0, 0, 480, NULL),
(305, 13, 2, '2026-01-05', '2026-01-05 09:30:00', '2026-01-05 16:30:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 7.00, 6.50, 0.50, 1, 1, 1, 30, 0, 360, NULL),
(306, 6, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(307, 7, 1, '2026-01-05', NULL, NULL, 'SYSTEM', 'ON_LEAVE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 480, NULL),
(308, 8, 1, '2026-01-05', '2026-01-05 08:05:00', '2026-01-05 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.92, 8.00, 0.92, 1, 1, 1, 5, 0, 480, NULL),
(309, 9, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(310, 10, 1, '2026-01-05', '2026-01-05 08:25:00', '2026-01-05 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.58, 8.00, 0.58, 1, 1, 1, 25, 0, 480, NULL),
(311, 11, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:30:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.50, 8.00, 1.50, 1, 1, 1, 0, 0, 480, NULL),
(312, 14, 1, '2026-01-05', '2026-01-05 08:00:00', '2026-01-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(313, 1, 1, '2026-01-20', '2026-01-20 08:10:00', '2026-01-20 17:30:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.33, 8.00, 1.33, 1, 1, 1, 10, 0, 480, NULL),
(314, 2, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(315, 3, 1, '2026-01-20', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 480, NULL),
(316, 13, 2, '2026-01-20', '2026-01-20 10:00:00', '2026-01-20 16:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 6.00, 6.00, 0.00, 1, 1, 1, 0, 0, 360, NULL),
(317, 6, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(318, 7, 1, '2026-01-20', '2026-01-20 08:20:00', '2026-01-20 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.67, 8.00, 0.67, 1, 1, 1, 20, 0, 480, NULL),
(319, 8, 1, '2026-01-20', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 480, NULL),
(320, 9, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(321, 10, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 16:30:00', 'QR', 'EARLY_OUT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.50, 8.00, 0.50, 1, 1, 1, 0, 30, 480, NULL),
(322, 11, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(323, 14, 1, '2026-01-20', '2026-01-20 08:00:00', '2026-01-20 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(324, 1, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(325, 2, 1, '2026-02-05', '2026-02-05 08:40:00', '2026-02-05 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.33, 8.00, 0.33, 1, 1, 1, 40, 0, 480, NULL),
(326, 3, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:30:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.50, 8.00, 1.50, 1, 1, 1, 0, 0, 480, NULL),
(327, 13, 2, '2026-02-05', NULL, NULL, 'SYSTEM', 'ON_LEAVE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 360, NULL),
(328, 6, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(329, 7, 1, '2026-02-05', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 480, NULL),
(330, 8, 1, '2026-02-05', '2026-02-05 08:10:00', '2026-02-05 17:00:00', 'QR', 'LATE', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 8.83, 8.00, 0.83, 1, 1, 1, 10, 0, 480, NULL),
(331, 9, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(332, 10, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(333, 11, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:30:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.50, 8.00, 1.50, 1, 1, 1, 0, 0, 480, NULL),
(334, 14, 1, '2026-02-05', '2026-02-05 08:00:00', '2026-02-05 17:00:00', 'QR', 'PRESENT', NULL, 1, 11, NULL, '2026-04-06 04:11:11', '2026-04-05 20:11:11', '2026-04-05 20:11:11', 9.00, 8.00, 1.00, 1, 1, 1, 0, 0, 480, NULL),
(335, 4, NULL, '2026-04-06', '2026-04-06 05:14:40', NULL, 'QR', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-04-05 21:14:40', '2026-04-05 21:14:40', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(336, 5, NULL, '2026-04-06', '2026-04-06 05:16:31', NULL, 'QR', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-04-05 21:16:31', '2026-04-05 21:16:31', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(337, 11, NULL, '2026-04-06', '2026-04-06 05:19:14', NULL, 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-04-05 21:19:14', '2026-04-05 21:19:14', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(338, 3, 7, '2026-04-06', '2026-04-06 12:38:54', NULL, 'QR', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-04-06 04:38:54', '2026-04-06 04:38:54', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(339, 1, NULL, '2026-03-13', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(340, 1, NULL, '2026-03-11', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(341, 1, NULL, '2026-03-24', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(342, 1, NULL, '2026-03-10', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(343, 1, NULL, '2026-04-04', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(344, 1, NULL, '2026-03-23', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(345, 2, NULL, '2026-03-17', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(346, 2, NULL, '2026-04-04', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(347, 2, NULL, '2026-03-13', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(348, 2, NULL, '2026-03-27', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(349, 2, NULL, '2026-03-31', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(350, 2, NULL, '2026-03-14', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(351, 3, NULL, '2026-03-19', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(352, 3, NULL, '2026-03-18', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(353, 3, NULL, '2026-03-31', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(354, 3, NULL, '2026-04-02', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(355, 3, NULL, '2026-03-12', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(356, 3, NULL, '2026-03-11', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(357, 4, NULL, '2026-03-24', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(358, 4, NULL, '2026-03-15', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(359, 4, NULL, '2026-04-05', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(360, 4, NULL, '2026-03-12', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(361, 4, NULL, '2026-04-03', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(362, 4, NULL, '2026-03-08', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(363, 4, NULL, '2026-04-04', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(364, 5, NULL, '2026-03-29', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(365, 5, NULL, '2026-03-16', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(366, 5, NULL, '2026-03-08', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(367, 5, NULL, '2026-04-05', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(368, 5, NULL, '2026-03-15', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(369, 5, NULL, '2026-04-04', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(370, 6, NULL, '2026-03-11', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(371, 6, NULL, '2026-03-25', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(372, 6, NULL, '2026-03-22', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(373, 6, NULL, '2026-03-09', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(374, 6, NULL, '2026-03-14', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(375, 6, NULL, '2026-04-02', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(376, 7, NULL, '2026-03-26', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(377, 7, NULL, '2026-03-31', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(378, 7, NULL, '2026-03-10', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(379, 7, NULL, '2026-03-21', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(380, 7, NULL, '2026-03-14', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(381, 7, NULL, '2026-03-13', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(382, 8, NULL, '2026-03-24', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(383, 8, NULL, '2026-03-27', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(384, 8, NULL, '2026-04-03', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(385, 8, NULL, '2026-04-04', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(386, 8, NULL, '2026-03-16', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(387, 8, NULL, '2026-03-22', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(388, 9, NULL, '2026-03-22', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(389, 9, NULL, '2026-03-26', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(390, 9, NULL, '2026-03-08', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(391, 9, NULL, '2026-03-14', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(392, 9, NULL, '2026-03-09', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(393, 9, NULL, '2026-03-12', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(394, 9, NULL, '2026-03-24', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(395, 10, NULL, '2026-03-30', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(396, 10, NULL, '2026-03-10', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(397, 10, NULL, '2026-03-24', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(398, 10, NULL, '2026-03-12', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(399, 10, NULL, '2026-03-17', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(400, 10, NULL, '2026-03-22', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(401, 10, NULL, '2026-03-27', NULL, NULL, 'MANUAL', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-04-07 15:32:47', '2026-04-07 15:32:47', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(402, 1, NULL, '2026-07-21', '2026-07-21 15:07:35', NULL, 'MANUAL', 'PENDING_APPROVAL', NULL, 0, NULL, NULL, NULL, '2026-07-21 07:07:35', '2026-07-21 07:07:35', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(403, 1, NULL, '2026-07-31', '2026-07-31 16:50:07', NULL, 'QR', 'PRESENT', NULL, 0, NULL, NULL, NULL, '2026-07-31 08:50:07', '2026-07-31 08:50:07', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(417, 42, NULL, '2026-07-31', '2026-07-31 18:46:44', NULL, 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-07-31 10:46:44', '2026-07-31 10:46:44', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(418, 11, NULL, '2026-07-31', '2026-07-31 18:51:08', NULL, 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-07-31 10:51:08', '2026-07-31 10:51:08', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(419, 11, NULL, '2026-08-01', '2026-08-01 10:33:46', NULL, 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-08-01 02:33:46', '2026-08-01 02:33:46', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(420, 14, NULL, '2026-08-01', '2026-08-01 10:41:38', NULL, 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-08-01 02:41:38', '2026-08-01 02:41:38', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(421, 39, NULL, '2026-08-01', '2026-08-01 11:41:52', NULL, 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-08-01 03:41:52', '2026-08-01 03:41:52', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(422, 39, NULL, '2025-01-01', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-08-03 08:52:28', '2026-08-03 08:52:28', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(423, 5, NULL, '2025-01-01', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-08-03 08:52:28', '2026-08-03 08:52:28', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(424, 40, NULL, '2026-08-03', '2026-08-03 19:12:56', '2026-08-03 17:00:00', 'QR', 'LATE', NULL, 0, NULL, NULL, NULL, '2026-08-03 11:12:56', '2026-08-03 13:43:53', -2.22, -2.22, 0.00, 1, 1, 1, 792, 0, 0, NULL),
(425, 39, NULL, '2026-08-03', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-08-03 12:07:01', '2026-08-03 13:43:53', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(426, 11, NULL, '2026-08-03', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-08-03 12:07:01', '2026-08-03 13:43:54', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL),
(427, 42, NULL, '2026-08-03', NULL, NULL, 'SYSTEM', 'ABSENT', NULL, 0, NULL, NULL, NULL, '2026-08-03 12:07:01', '2026-08-03 13:43:54', NULL, NULL, NULL, 1, 1, 1, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ta_employee_shifts`
--

CREATE TABLE `ta_employee_shifts` (
  `employee_shift_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id_new` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_employee_shifts`
--

INSERT INTO `ta_employee_shifts` (`employee_shift_id`, `employee_id`, `shift_id`, `effective_from`, `effective_to`, `is_active`, `created_at`, `updated_at`, `employee_id_new`) VALUES
(3, 5, 2, '2026-03-28', '2026-05-28', 0, '2026-03-28 15:01:20', '2026-04-02 12:30:58', NULL),
(5, 6, 2, '2026-03-02', '2026-06-02', 1, '2026-04-02 12:31:21', '2026-04-02 12:31:21', NULL),
(7, 2, 2, '2026-03-02', '2026-06-02', 1, '2026-04-02 15:03:15', '2026-04-02 15:03:15', NULL),
(8, 8, 2, '2026-03-02', '2026-06-02', 1, '2026-04-02 15:03:50', '2026-04-02 15:03:50', NULL),
(9, 5, 2, '2026-03-02', '2026-06-02', 0, '2026-04-02 15:05:22', '2026-04-04 15:37:05', NULL),
(10, 1, 2, '2026-03-02', '2026-06-02', 1, '2026-04-02 15:25:02', '2026-04-02 15:25:02', NULL),
(14, 5, 7, '2026-03-02', '2026-06-02', 0, '2026-04-05 03:55:42', '2026-04-05 04:23:24', NULL),
(15, 3, 7, '2026-03-02', '2026-06-02', 1, '2026-04-05 03:55:42', '2026-04-05 03:55:42', NULL),
(16, 7, 7, '2026-03-02', '2026-06-02', 1, '2026-04-05 03:57:23', '2026-04-05 03:57:23', NULL),
(17, 5, 2, '2026-07-09', '2026-07-30', 0, '2026-07-09 15:43:57', '2026-08-09 07:46:58', NULL),
(18, 42, 2, '2026-05-31', '2026-12-31', 1, '2026-07-31 08:31:30', '2026-07-31 08:31:30', NULL),
(19, 11, 2, '2026-07-31', '2026-12-31', 1, '2026-07-31 10:50:43', '2026-07-31 10:50:43', NULL),
(20, 40, 2, '2026-07-01', '2026-12-31', 1, '2026-08-03 10:49:37', '2026-08-03 10:49:37', NULL),
(21, 5, 2, '2026-07-09', '2026-07-30', 1, '2026-08-09 07:46:58', '2026-08-09 07:46:58', NULL),
(22, 1, 0, '2026-08-10', '2026-08-10', 1, '2026-08-10 11:23:19', '2026-08-10 11:23:19', NULL),
(23, 5, 7, '2026-08-10', '2026-08-10', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(24, 5, 7, '2026-08-17', '2026-08-17', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(25, 5, 7, '2026-08-24', '2026-08-24', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(26, 5, 7, '2026-08-31', '2026-08-31', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(27, 5, 7, '2026-09-07', '2026-09-07', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(28, 5, 7, '2026-09-14', '2026-09-14', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(29, 5, 7, '2026-09-21', '2026-09-21', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(30, 5, 7, '2026-09-28', '2026-09-28', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(31, 5, 7, '2026-10-05', '2026-10-05', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(32, 5, 7, '2026-10-12', '2026-10-12', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(33, 5, 7, '2026-10-19', '2026-10-19', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(34, 5, 7, '2026-10-26', '2026-10-26', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(35, 5, 7, '2026-11-02', '2026-11-02', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(36, 5, 7, '2026-11-09', '2026-11-09', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(37, 5, 7, '2026-11-16', '2026-11-16', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(38, 5, 7, '2026-11-23', '2026-11-23', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(39, 5, 7, '2026-11-30', '2026-11-30', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(40, 5, 7, '2026-12-07', '2026-12-07', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(41, 5, 7, '2026-12-14', '2026-12-14', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(42, 5, 7, '2026-12-21', '2026-12-21', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL),
(43, 5, 7, '2026-12-28', '2026-12-28', 1, '2026-08-10 11:28:12', '2026-08-10 11:28:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ta_flexible_schedules`
--

CREATE TABLE `ta_flexible_schedules` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `repeat_until` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_flexible_schedules`
--

INSERT INTO `ta_flexible_schedules` (`id`, `employee_id`, `schedule_date`, `start_time`, `end_time`, `break_start`, `break_end`, `day_of_week`, `repeat_until`, `contract_end_date`, `notes`, `created_by`, `created_at`) VALUES
(31, 5, '2026-04-06', '06:00:00', '18:00:00', NULL, NULL, 1, '2026-06-02', '2026-06-02', '', 3, '2026-04-05 04:24:45'),
(32, 5, '2026-04-07', '06:00:00', '18:00:00', NULL, NULL, 2, '2026-06-02', '2026-06-02', '', 3, '2026-04-05 04:24:45'),
(33, 5, '2026-04-08', '06:00:00', '18:00:00', NULL, NULL, 3, '2026-06-02', '2026-06-02', '', 3, '2026-04-05 04:24:45'),
(34, 5, '2026-04-09', '06:00:00', '18:00:00', NULL, NULL, 4, '2026-06-02', '2026-06-02', '', 3, '2026-04-05 04:24:45'),
(35, 5, '2026-08-09', '06:00:00', '18:00:00', NULL, NULL, 1, '2026-12-31', '2026-12-31', '', 3, '2026-04-05 04:24:45'),
(36, 39, '2026-08-03', '07:00:00', '19:00:00', NULL, NULL, 1, '2026-12-31', NULL, '', 3, '2026-08-01 03:40:19'),
(37, 39, '2026-08-04', '07:00:00', '19:00:00', NULL, NULL, 2, '2026-12-31', NULL, '', 3, '2026-08-01 03:40:19'),
(38, 39, '2026-08-05', '07:00:00', '19:00:00', NULL, NULL, 3, '2026-12-31', NULL, '', 3, '2026-08-01 03:40:19'),
(39, 39, '2026-08-08', '08:00:00', '20:00:00', NULL, NULL, 6, '2026-12-31', NULL, '', 3, '2026-08-01 03:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `ta_holidays`
--

CREATE TABLE `ta_holidays` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `holiday_date` date NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `country_code` varchar(10) DEFAULT 'PH',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'national',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ta_holidays`
--

INSERT INTO `ta_holidays` (`id`, `name`, `holiday_date`, `is_recurring`, `country_code`, `description`, `category`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(145, 'New Year\'s Day', '2026-01-01', 0, 'PH', 'Bagong Taon', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(146, 'Chinese New Year', '2026-02-17', 0, 'PH', 'Chinese New Year', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(147, 'Maundy Thursday', '2026-04-02', 0, 'PH', 'Huwebes Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(148, 'Good Friday', '2026-04-03', 0, 'PH', 'Biyernes Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(149, 'Holy Saturday', '2026-04-04', 0, 'PH', 'Sabado de Gloria', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(150, 'Day of Valor', '2026-04-09', 0, 'PH', 'Araw ng Kagitingan', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(151, 'Labour Day', '2026-05-01', 0, 'PH', 'Araw ng Paggawa', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(152, 'Independence Day', '2026-06-12', 0, 'PH', 'Araw ng Kalayaan', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(153, 'Ninoy Aquino Day', '2026-08-21', 0, 'PH', 'Araw ng Kamatayan ni Senador Benigno Simeon \"Ninoy\" Aquino Jr.', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(154, 'National Heroes Day', '2026-08-31', 0, 'PH', 'Araw ng mga Bayani', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(155, 'All Saints\' Day Eve', '2026-10-31', 0, 'PH', 'All Saints\' Day Eve', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(156, 'All Saints\' Day', '2026-11-01', 0, 'PH', 'Araw ng mga Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(157, 'Bonifacio Day', '2026-11-30', 0, 'PH', 'Araw ni Gat Andres Bonifacio', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(158, 'Feast of the Immaculate Conception of Mary', '2026-12-08', 0, 'PH', 'Kapistahan ng Immaculada Concepcion', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(159, 'Christmas Eve', '2026-12-24', 0, 'PH', 'Christmas Eve', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(160, 'Christmas Day', '2026-12-25', 0, 'PH', 'Araw ng Pasko', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(161, 'Rizal Day', '2026-12-30', 0, 'PH', 'Araw ng Kamatayan ni Dr. Jose Rizal', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(162, 'Last Day of The Year', '2026-12-31', 0, 'PH', 'Huling Araw ng Taon', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(163, 'New Year\'s Day', '2027-01-01', 0, 'PH', 'Bagong Taon', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(164, 'Chinese New Year', '2027-02-06', 0, 'PH', 'Chinese New Year', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(165, 'Maundy Thursday', '2027-03-25', 0, 'PH', 'Huwebes Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(166, 'Good Friday', '2027-03-26', 0, 'PH', 'Biyernes Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(167, 'Holy Saturday', '2027-03-27', 0, 'PH', 'Sabado de Gloria', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(168, 'Day of Valor', '2027-04-09', 0, 'PH', 'Araw ng Kagitingan', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(169, 'Labour Day', '2027-05-01', 0, 'PH', 'Araw ng Paggawa', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(170, 'Independence Day', '2027-06-12', 0, 'PH', 'Araw ng Kalayaan', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(171, 'Ninoy Aquino Day', '2027-08-21', 0, 'PH', 'Araw ng Kamatayan ni Senador Benigno Simeon \"Ninoy\" Aquino Jr.', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(172, 'National Heroes Day', '2027-08-30', 0, 'PH', 'Araw ng mga Bayani', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(173, 'All Saints\' Day Eve', '2027-10-31', 0, 'PH', 'All Saints\' Day Eve', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(174, 'All Saints\' Day', '2027-11-01', 0, 'PH', 'Araw ng mga Santo', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(175, 'Bonifacio Day', '2027-11-30', 0, 'PH', 'Araw ni Gat Andres Bonifacio', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(176, 'Feast of the Immaculate Conception of Mary', '2027-12-08', 0, 'PH', 'Kapistahan ng Immaculada Concepcion', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(177, 'Christmas Eve', '2027-12-24', 0, 'PH', 'Christmas Eve', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(178, 'Christmas Day', '2027-12-25', 0, 'PH', 'Araw ng Pasko', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(179, 'Rizal Day', '2027-12-30', 0, 'PH', 'Araw ng Kamatayan ni Dr. Jose Rizal', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59'),
(180, 'Last Day of The Year', '2027-12-31', 0, 'PH', 'Huling Araw ng Taon', 'national', 1, NULL, '2026-08-15 14:54:59', '2026-08-15 14:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `ta_holiday_sync_log`
--

CREATE TABLE `ta_holiday_sync_log` (
  `id` int(11) NOT NULL,
  `sync_date` date DEFAULT NULL,
  `total_holidays` int(11) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `last_synced` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ta_holiday_sync_log`
--

INSERT INTO `ta_holiday_sync_log` (`id`, `sync_date`, `total_holidays`, `country_code`, `last_synced`) VALUES
(1, '2026-03-20', 36, 'PH', '2026-03-19 23:41:28'),
(3, '2026-04-05', 36, 'PH', '2026-04-04 20:30:10'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 12:22:37'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 12:25:06'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 12:27:01'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 12:29:24'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 12:44:01'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 14:54:42'),
(0, '2026-08-15', 36, 'PH', '2026-08-15 14:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `ta_leave_balances`
--

CREATE TABLE `ta_leave_balances` (
  `leave_balance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `opening_balance` decimal(5,2) DEFAULT 0.00,
  `used_balance` decimal(5,2) DEFAULT 0.00,
  `remaining_balance` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id_new` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_leave_balances`
--

INSERT INTO `ta_leave_balances` (`leave_balance_id`, `employee_id`, `leave_type_id`, `year`, `opening_balance`, `used_balance`, `remaining_balance`, `notes`, `created_at`, `updated_at`, `employee_id_new`) VALUES
(0, 1, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 2, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 3, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 4, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 5, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 6, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 7, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 8, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 9, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 10, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 11, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 12, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 13, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 14, 1, 2026, 15.00, 0.00, 15.00, 'Vacation Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 1, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 2, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 3, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 4, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 5, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 6, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 7, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 8, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 9, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 10, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 11, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 12, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 13, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 14, 2, 2026, 10.00, 0.00, 10.00, 'Sick Leave - Annual', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 1, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 2, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 3, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 4, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 5, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 6, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 7, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 8, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 9, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 10, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 11, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 12, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 13, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 14, 4, 2026, 5.00, 0.00, 5.00, 'Emergency Leave - Company Policy', '2026-04-05 20:53:54', '2026-04-05 20:53:54', NULL),
(0, 12, 5, 2026, 7.00, 0.00, 7.00, 'Paternity Leave - RA 8187', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 13, 5, 2026, 7.00, 0.00, 7.00, 'Paternity Leave - RA 8187', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 1, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 2, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 3, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 4, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 5, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 6, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 7, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 8, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 9, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 10, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 11, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 12, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 13, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 14, 6, 2026, 1.00, 0.00, 1.00, 'Birthday Leave - RA 11976', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 1, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 2, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 3, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 4, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 5, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 6, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 7, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 8, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 9, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 10, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 11, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 12, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 13, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL),
(0, 14, 7, 2026, 5.00, 0.00, 5.00, 'Bereavement Leave', '2026-04-05 20:59:11', '2026-04-05 20:59:11', NULL);

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
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `details` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL COMMENT 'Detailed reason for leave request',
  `supporting_document` varchar(255) DEFAULT NULL,
  `documents` longtext DEFAULT NULL COMMENT 'JSON array of uploaded document file paths',
  `document_uploaded_at` timestamp NULL DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `employee_id_new` int(11) DEFAULT NULL,
  `balance_deducted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_leave_requests`
--

INSERT INTO `ta_leave_requests` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `date_submitted`, `updated_at`, `status`, `details`, `reason`, `supporting_document`, `documents`, `document_uploaded_at`, `reject_reason`, `employee_id_new`, `balance_deducted`) VALUES
(3, 1, 4, '2026-03-29', '2026-04-01', '2026-03-29 08:42:09', '2026-03-29 08:55:46', '', 'wdawdaw', NULL, NULL, NULL, NULL, 'ok', NULL, 0),
(0, 9, 7, '2026-04-10', '2026-04-13', '2026-04-05 21:52:42', '2026-04-05 22:25:15', 'Approved', 'jaswhdawdjaw', 'sjnfasweda', NULL, '[\"uploads\\/leave_documents\\/1775425962_1395d830a5063abb0240981ffdda4ddd.docx\"]', NULL, 'Testing approval', NULL, 0),
(0, 4, 7, '2026-04-13', '2026-04-15', '2026-04-05 22:22:14', '2026-04-05 22:25:15', 'Approved', 'mamamo', 'hakdog', NULL, '[\"uploads\\/leave_documents\\/1775427734_6013a4c5dc9b8150083f13a898a787bf.png\"]', NULL, 'Testing approval', NULL, 0),
(0, 2, 3, '2026-04-06', '2026-04-07', '2026-04-05 22:29:15', NULL, 'Pending', 'manyakis', 'buntis', NULL, '[\"uploads\\/leave_documents\\/1775428155_2084d04d2c6a56f24807fc5128ac46b5.png\"]', NULL, NULL, NULL, 0),
(0, 2, 5, '2026-04-06', '2026-04-11', '2026-04-05 22:39:03', NULL, 'Pending', 'try docu', 'test drove vehicle', NULL, '[\"uploads\\/leave_documents\\/1775428743_0b96168510f18f0e4fdeecdedd9d5d92.png\"]', NULL, NULL, NULL, 0),
(0, 3, 4, '2026-04-07', '2026-04-09', '2026-04-06 05:00:48', NULL, 'Pending', 'Urgent', 'For emergency', NULL, '[\"uploads\\/leave_documents\\/1775451648_f54262b7163d4c5f1d392ccde6f78be7.jpg\"]', NULL, NULL, NULL, 0),
(0, 3, 2, '2026-04-06', '2026-04-10', '2026-04-06 05:02:54', NULL, 'Pending', 'Test', 'Testing', NULL, '[\"uploads\\/leave_documents\\/1775451774_83c43a0c18c2626b7e272806cef2ae62.jpg\"]', NULL, NULL, NULL, 0);

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
(1, 'Vacation Leave', NULL, 15, 1, 1, '2026-04-05 20:55:08'),
(2, 'Sick Leave', NULL, 10, 1, 1, '2026-04-05 20:55:08'),
(3, 'Maternity Leave', NULL, 5, 1, 1, '2026-04-05 20:55:08'),
(4, 'Emergency Leave', NULL, 3, 0, 0, '2026-04-05 20:55:08'),
(5, 'Paternity Leave', NULL, 7, 1, 1, '2026-04-05 20:55:08'),
(6, 'Birthday Leave', NULL, 1, 1, 1, '2026-04-05 20:55:08'),
(7, 'Bereavement Leave', NULL, 5, 1, 1, '2026-04-05 20:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `ta_shifts`
--

CREATE TABLE `ta_shifts` (
  `shift_id` int(11) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_duration` int(11) DEFAULT 60 COMMENT 'Break duration in minutes',
  `description` text DEFAULT NULL,
  `include_saturday` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_shifts`
--

INSERT INTO `ta_shifts` (`shift_id`, `shift_name`, `start_time`, `end_time`, `break_duration`, `description`, `include_saturday`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Morning Shift', '06:00:00', '17:00:00', 60, '\r\n\r\n', 0, 1, '2026-03-20 04:03:28', '2026-03-20 04:03:28'),
(7, 'No Saturday', '06:00:00', '18:00:00', 60, '12-1pm lunch break', 0, 1, '2026-04-05 03:55:12', '2026-04-05 03:55:12'),
(0, 'Custom 08:00-17:00', '08:00:00', '17:00:00', 60, NULL, 0, 1, '2026-08-10 11:23:19', '2026-08-10 11:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `ta_shift_assignments`
--

CREATE TABLE `ta_shift_assignments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ta_shift_exclusions`
--

CREATE TABLE `ta_shift_exclusions` (
  `exclusion_id` int(11) NOT NULL,
  `employee_shift_id` int(11) NOT NULL,
  `exclusion_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ta_shift_exclusions`
--

INSERT INTO `ta_shift_exclusions` (`exclusion_id`, `employee_shift_id`, `exclusion_date`, `reason`, `created_at`, `updated_at`) VALUES
(27, 14, '2026-03-07', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(28, 14, '2026-03-14', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(29, 14, '2026-03-21', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(30, 14, '2026-03-28', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(31, 14, '2026-04-04', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(32, 14, '2026-04-11', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(33, 14, '2026-04-18', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(34, 14, '2026-04-25', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(35, 14, '2026-05-02', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(36, 14, '2026-05-09', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(37, 14, '2026-05-16', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(38, 14, '2026-05-23', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(39, 14, '2026-05-30', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(40, 15, '2026-03-07', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(41, 15, '2026-03-14', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(42, 15, '2026-03-21', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(43, 15, '2026-03-28', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(44, 15, '2026-04-04', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(45, 15, '2026-04-11', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(46, 15, '2026-04-18', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(47, 15, '2026-04-25', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(48, 15, '2026-05-02', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(49, 15, '2026-05-09', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(50, 15, '2026-05-16', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(51, 15, '2026-05-23', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(52, 15, '2026-05-30', 'Saturday exclusion', '2026-04-05 03:55:42', '2026-04-05 03:55:42'),
(53, 16, '2026-03-07', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(54, 16, '2026-03-14', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(55, 16, '2026-03-21', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(56, 16, '2026-03-28', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(57, 16, '2026-04-04', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(58, 16, '2026-04-11', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(59, 16, '2026-04-18', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(60, 16, '2026-04-25', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(61, 16, '2026-05-02', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(62, 16, '2026-05-09', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(63, 16, '2026-05-16', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(64, 16, '2026-05-23', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(65, 16, '2026-05-30', 'Saturday exclusion', '2026-04-05 03:57:23', '2026-04-05 03:57:23'),
(66, 0, '2026-07-04', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(67, 0, '2026-07-11', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(68, 0, '2026-07-18', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(69, 0, '2026-07-25', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(70, 0, '2026-08-01', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(71, 0, '2026-08-08', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(72, 0, '2026-08-15', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(73, 0, '2026-08-22', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(74, 0, '2026-08-29', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(75, 0, '2026-09-05', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(76, 0, '2026-09-12', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(77, 0, '2026-09-19', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(78, 0, '2026-09-26', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(79, 0, '2026-10-03', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(80, 0, '2026-10-10', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(81, 0, '2026-10-17', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(82, 0, '2026-10-24', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(83, 0, '2026-10-31', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(84, 0, '2026-11-07', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(85, 0, '2026-11-14', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(86, 0, '2026-11-21', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(87, 0, '2026-11-28', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(88, 0, '2026-12-05', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(89, 0, '2026-12-12', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(90, 0, '2026-12-19', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37'),
(91, 0, '2026-12-26', 'Saturday exclusion', '2026-08-03 10:49:37', '2026-08-03 10:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `ta_shift_weekday_times`
--

CREATE TABLE `ta_shift_weekday_times` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `weekday` tinyint(4) NOT NULL COMMENT '0=Sunday,1=Monday...6=Saturday',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','hr','employee') DEFAULT 'employee',
  `theme` enum('light','dark') DEFAULT 'light',
  `employee_id` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `account_status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `theme`, `employee_id`, `profile_pic`, `account_status`, `last_login`, `password_changed_at`, `failed_login_attempts`, `created_at`) VALUES
(1, 'admin', '$2y$10$NamPkg2msMgDuc6CLVq/Y.1ezlD1yYvhrVke4tBLHKe2e1nVvihBa', 'admin', 'light', '1', NULL, 'Active', NULL, '2026-08-07 06:20:30', 0, '2026-08-06 13:47:35'),
(2, 'hr_employee', '$2y$10$eJUKXrh8r9ay2zZIK6OYYu.QDZqJ5IX4SXZlYt0g.qUH9cN9O7Hd6', 'hr', 'light', NULL, NULL, 'Active', NULL, NULL, 0, '2026-08-07 07:41:26');

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
(31, '$2y$10$Htc.AaV0g3yW1hrOtux6fu1oOXiGgxz5WctWPfE/CjccN3EPwtYlG', 1022, NULL, '2026-08-09 05:31:06', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  ADD PRIMARY KEY (`cert_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_change_history`
--
ALTER TABLE `employee_change_history`
  ADD PRIMARY KEY (`change_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  ADD PRIMARY KEY (`dependent_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_education`
--
ALTER TABLE `employee_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_education_employee_id` (`employee_id`);

--
-- Indexes for table `employee_emergency_contacts`
--
ALTER TABLE `employee_emergency_contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_languages`
--
ALTER TABLE `employee_languages`
  ADD PRIMARY KEY (`language_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_work_experience`
--
ALTER TABLE `employee_work_experience`
  ADD PRIMARY KEY (`work_exp_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `em_departments`
--
ALTER TABLE `em_departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `em_documents`
--
ALTER TABLE `em_documents`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `em_education`
--
ALTER TABLE `em_education`
  ADD PRIMARY KEY (`education_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `em_employees`
--
ALTER TABLE `em_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `em_positions`
--
ALTER TABLE `em_positions`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `em_users`
--
ALTER TABLE `em_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_document_employee` (`employee_id`),
  ADD KEY `idx_document_exit_case` (`exit_case_type`,`exit_case_id`),
  ADD KEY `fk_document_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_settlement_employee` (`employee_id`),
  ADD KEY `fk_settlement_resignation` (`resignation_id`),
  ADD KEY `idx_settlement_exit_case` (`exit_case_type`,`exit_case_id`),
  ADD KEY `fk_settlement_approved_by` (`approved_by`),
  ADD KEY `fk_settlement_created_by` (`created_by`);

--
-- Indexes for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_interview_employee` (`employee_id`),
  ADD KEY `fk_interview_exit_case` (`exit_case_id`),
  ADD KEY `fk_interview_interviewer` (`interviewer_id`);

--
-- Indexes for table `exit_interview_feedback`
--
ALTER TABLE `exit_interview_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_interview` (`interview_id`);

--
-- Indexes for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_plan` (`plan_id`);

--
-- Indexes for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transfer_employee` (`employee_id`),
  ADD KEY `fk_transfer_successor` (`successor_id`),
  ADD KEY `fk_transfer_created_by` (`created_by`);

--
-- Indexes for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resignation_employee` (`employee_id`),
  ADD KEY `fk_resignation_submitted_by` (`submitted_by`),
  ADD KEY `fk_resignation_preclearance_desk` (`preclearance_desk_person`),
  ADD KEY `fk_resignation_approved_by` (`approved_by`),
  ADD KEY `fk_resignation_hr_approved_by` (`hr_approved_by`),
  ADD KEY `fk_resignation_legal_approved_by` (`legal_approved_by`);

--
-- Indexes for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_survey_created_by` (`created_by`);

--
-- Indexes for table `exit_survey_answers`
--
ALTER TABLE `exit_survey_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_answer_response` (`response_id`),
  ADD KEY `fk_answer_question` (`question_id`);

--
-- Indexes for table `exit_survey_questions`
--
ALTER TABLE `exit_survey_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_question_survey` (`survey_id`);

--
-- Indexes for table `exit_survey_responses`
--
ALTER TABLE `exit_survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_response_survey` (`survey_id`),
  ADD KEY `fk_response_employee` (`employee_id`),
  ADD KEY `idx_response_exit_case` (`exit_case_type`,`exit_case_id`);

--
-- Indexes for table `exit_terminations`
--
ALTER TABLE `exit_terminations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_termination_employee` (`employee_id`),
  ADD KEY `fk_termination_submitted_by` (`submitted_by`),
  ADD KEY `fk_termination_approved_by` (`approved_by`),
  ADD KEY `fk_termination_reviewed_by` (`reviewed_by`),
  ADD KEY `fk_termination_legal_approved_by` (`legal_approved_by`);

--
-- Indexes for table `family_background`
--
ALTER TABLE `family_background`
  ADD PRIMARY KEY (`family_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `government_ids`
--
ALTER TABLE `government_ids`
  ADD PRIMARY KEY (`gov_id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`overtime_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `payroll_clearances`
--
ALTER TABLE `payroll_clearances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_settlement_id` (`settlement_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD PRIMARY KEY (`personal_info_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `ta_absence_late_records`
--
ALTER TABLE `ta_absence_late_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_date` (`absence_date`),
  ADD KEY `idx_status` (`excuse_status`);

--
-- Indexes for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `ta_employee_shifts`
--
ALTER TABLE `ta_employee_shifts`
  ADD PRIMARY KEY (`employee_shift_id`);

--
-- Indexes for table `ta_flexible_schedules`
--
ALTER TABLE `ta_flexible_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ta_holidays`
--
ALTER TABLE `ta_holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ta_leave_types`
--
ALTER TABLE `ta_leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `ta_shift_exclusions`
--
ALTER TABLE `ta_shift_exclusions`
  ADD PRIMARY KEY (`exclusion_id`);

--
-- Indexes for table `ta_shift_weekday_times`
--
ALTER TABLE `ta_shift_weekday_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `weekday` (`weekday`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`);

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
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  MODIFY `cert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_change_history`
--
ALTER TABLE `employee_change_history`
  MODIFY `change_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  MODIFY `dependent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_education`
--
ALTER TABLE `employee_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_emergency_contacts`
--
ALTER TABLE `employee_emergency_contacts`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_languages`
--
ALTER TABLE `employee_languages`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_work_experience`
--
ALTER TABLE `employee_work_experience`
  MODIFY `work_exp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_history`
--
ALTER TABLE `employment_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `em_employees`
--
ALTER TABLE `em_employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `exit_documents`
--
ALTER TABLE `exit_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_interview_feedback`
--
ALTER TABLE `exit_interview_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_survey_answers`
--
ALTER TABLE `exit_survey_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_survey_questions`
--
ALTER TABLE `exit_survey_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_survey_responses`
--
ALTER TABLE `exit_survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_terminations`
--
ALTER TABLE `exit_terminations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_background`
--
ALTER TABLE `family_background`
  MODIFY `family_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `government_ids`
--
ALTER TABLE `government_ids`
  MODIFY `gov_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `overtime_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_clearances`
--
ALTER TABLE `payroll_clearances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_information`
--
ALTER TABLE `personal_information`
  MODIFY `personal_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ta_absence_late_records`
--
ALTER TABLE `ta_absence_late_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=428;

--
-- AUTO_INCREMENT for table `ta_employee_shifts`
--
ALTER TABLE `ta_employee_shifts`
  MODIFY `employee_shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `ta_flexible_schedules`
--
ALTER TABLE `ta_flexible_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `ta_holidays`
--
ALTER TABLE `ta_holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `ta_leave_types`
--
ALTER TABLE `ta_leave_types`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ta_shift_exclusions`
--
ALTER TABLE `ta_shift_exclusions`
  MODIFY `exclusion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `ta_shift_weekday_times`
--
ALTER TABLE `ta_shift_weekday_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  ADD CONSTRAINT `employee_certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_change_history`
--
ALTER TABLE `employee_change_history`
  ADD CONSTRAINT `employee_change_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  ADD CONSTRAINT `employee_dependents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_emergency_contacts`
--
ALTER TABLE `employee_emergency_contacts`
  ADD CONSTRAINT `employee_emergency_contacts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_languages`
--
ALTER TABLE `employee_languages`
  ADD CONSTRAINT `employee_languages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_work_experience`
--
ALTER TABLE `employee_work_experience`
  ADD CONSTRAINT `employee_work_experience_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD CONSTRAINT `employment_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD CONSTRAINT `fk_document_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_document_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  ADD CONSTRAINT `fk_settlement_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_settlement_created_by` FOREIGN KEY (`created_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_settlement_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_settlement_resignation` FOREIGN KEY (`resignation_id`) REFERENCES `exit_resignations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD CONSTRAINT `fk_interview_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_interview_interviewer` FOREIGN KEY (`interviewer_id`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_interview_feedback`
--
ALTER TABLE `exit_interview_feedback`
  ADD CONSTRAINT `fk_feedback_interview` FOREIGN KEY (`interview_id`) REFERENCES `exit_interviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  ADD CONSTRAINT `fk_item_plan` FOREIGN KEY (`plan_id`) REFERENCES `exit_knowledge_transfer_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  ADD CONSTRAINT `fk_transfer_created_by` FOREIGN KEY (`created_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transfer_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transfer_successor` FOREIGN KEY (`successor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  ADD CONSTRAINT `fk_resignation_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_resignation_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resignation_hr_approved_by` FOREIGN KEY (`hr_approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_resignation_legal_approved_by` FOREIGN KEY (`legal_approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_resignation_preclearance_desk` FOREIGN KEY (`preclearance_desk_person`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_resignation_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  ADD CONSTRAINT `fk_survey_created_by` FOREIGN KEY (`created_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_survey_answers`
--
ALTER TABLE `exit_survey_answers`
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `exit_survey_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_response` FOREIGN KEY (`response_id`) REFERENCES `exit_survey_responses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_survey_questions`
--
ALTER TABLE `exit_survey_questions`
  ADD CONSTRAINT `fk_question_survey` FOREIGN KEY (`survey_id`) REFERENCES `exit_surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_survey_responses`
--
ALTER TABLE `exit_survey_responses`
  ADD CONSTRAINT `fk_response_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_response_survey` FOREIGN KEY (`survey_id`) REFERENCES `exit_surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_terminations`
--
ALTER TABLE `exit_terminations`
  ADD CONSTRAINT `fk_termination_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_termination_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_termination_legal_approved_by` FOREIGN KEY (`legal_approved_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_termination_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_termination_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `family_background`
--
ALTER TABLE `family_background`
  ADD CONSTRAINT `family_background_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `government_ids`
--
ALTER TABLE `government_ids`
  ADD CONSTRAINT `government_ids_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

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
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_clearances`
--
ALTER TABLE `payroll_clearances`
  ADD CONSTRAINT `fk_clearance_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `exit_employee_settlements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD CONSTRAINT `personal_information_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_account`
--
ALTER TABLE `user_account`
  ADD CONSTRAINT `fk_user_account_employee` FOREIGN KEY (`employee_id`) REFERENCES `hrms_employee` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
