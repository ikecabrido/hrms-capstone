-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2026 at 10:56 AM
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
  `target_audience` varchar(100) DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eer_announcements`
--

INSERT INTO `eer_announcements` (`eer_announcements_id`, `title`, `content`, `created_by`, `created_at`, `target_audience`) VALUES
(1, 'Company Town Hall', 'Join tomorrow', 'EMP001', '2026-03-29 04:27:57', 'all'),
(2, 'Clean-up Day', 'This Friday', 'EMP002', '2026-03-29 04:27:57', 'all'),
(3, 'Company Town Hall', 'Join tomorrow', 'EMP009', '2026-03-29 12:36:01', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `em_employees`
--

CREATE TABLE `em_employees` (
  `id` int(11) NOT NULL,
  `employee_num` varchar(20) DEFAULT NULL,
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
  `mobile_no` varchar(20) DEFAULT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
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
-- Dumping data for table `em_employees`
--

INSERT INTO `em_employees` (`id`, `employee_num`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `mobile_no`, `phone_no`, `current_address`, `profile_image`, `permanent_address`, `department`, `position`, `position_id`, `hire_date`, `regular_date`, `employment_status`, `employment_type`, `unit_load`, `graduate_level`, `ranking`, `credentials`, `faculty_notes`, `negotiated_salary`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_date`) VALUES
(1, 'EMP-000001', 1, 'Ronaldo', 'G.', 'Raymundo', '', 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, '09318952822', '0287654321', 'San Jose Del Monte, Bulacan', 'profile_1_1786621064.png', NULL, 'IT DEPARTMENT', 'IT Staff', 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-06 05:47:35', '2026-08-13 11:37:44', 0, NULL, NULL),
(2, 'EMP-000002', 3, 'Juan', 'Dela', 'Cruz', NULL, 'Male', '1990-05-15', NULL, NULL, NULL, NULL, '09123456789', '021234567', '123 Main St, Manila', NULL, NULL, 'Executive Administration', 'College President', NULL, '2023-01-15', NULL, 'Active', NULL, NULL, 'None', NULL, NULL, NULL, NULL, '2026-08-06 06:54:48', '2026-08-11 16:50:50', 0, NULL, NULL),
(3, 'EMP-000003', NULL, 'Maria', 'Santos', 'Reyes', NULL, 'Female', '1992-03-18', 'Malolos, Bulacan', 'Married', 'Filipino', NULL, '09171234567', '0441234567', 'Malolos, Bulacan', NULL, NULL, 'Employee Management', 'HR Officer', 44, '2024-06-10', '2025-06-10', 'Active', 'Full-time', NULL, 'Masteral', 'Senior HR Staff', 'BS Psychology, MA Human Resource Management', NULL, 45000.00, '2026-08-08 01:00:00', NULL, 0, NULL, NULL),
(4, 'EMP-000004', NULL, 'Michael', 'Tan', 'Santos', 'Jr.', 'Male', '1988-11-25', 'Meycauayan, Bulacan', 'Married', 'Filipino', NULL, '09181234567', '0442345678', 'Meycauayan, Bulacan', NULL, NULL, 'Payroll', 'Payroll Officer', 43, '2023-08-01', '2024-08-01', 'Active', 'Full-time', NULL, 'Masteral', 'Payroll Specialist', 'BS Accountancy, CPA', NULL, 48000.00, '2026-08-08 01:15:00', NULL, 0, NULL, NULL),
(5, 'EMP-000005', NULL, 'Angela', 'Marie', 'Garcia', NULL, 'Female', '1996-07-09', 'Baliwag, Bulacan', 'Single', 'Filipino', NULL, '09201234567', '0443456789', 'Baliwag, Bulacan', NULL, NULL, 'Learning', 'Training Coordinator', 47, '2025-01-15', NULL, 'Probationary', 'Full-time', NULL, 'None', 'Training Coordinator', 'BS Education', 'Handles employee training and seminar coordination.', 32000.00, '2026-08-08 01:30:00', NULL, 0, NULL, NULL),
(6, 'EMP-000006', NULL, 'Daniel', 'Lopez', 'Mendoza', NULL, 'Male', '1993-09-14', 'San Fernando, Pampanga', 'Single', 'Filipino', NULL, '09301234567', '0451234567', 'San Fernando, Pampanga', NULL, NULL, 'Performance', 'Performance Management Officer', 48, '2024-03-20', '2025-03-20', 'Active', 'Full-time', NULL, 'Masteral', 'Performance Specialist', 'BS Business Administration, MBA', NULL, 42000.00, '2026-08-08 01:45:00', NULL, 0, NULL, NULL),
(7, 'EMP-000007', NULL, 'Sofia', 'Anne', 'Villanueva', NULL, 'Female', '1997-12-03', 'Quezon City', 'Single', 'Filipino', NULL, '09401234567', '0281234567', 'Quezon City', NULL, NULL, 'Employee Engagement', 'Employee Relations Officer', 51, '2025-05-05', NULL, 'Probationary', 'Full-time', NULL, 'None', 'Employee Relations Staff', 'BS Psychology', 'Handles employee engagement activities and concerns.', 35000.00, '2026-08-08 02:00:00', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `em_positions`
--

CREATE TABLE `em_positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_positions`
--

INSERT INTO `em_positions` (`position_id`, `position_name`, `department_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'School Directress', 1, 'Active', '2026-08-06 05:47:35', NULL),
(2, 'College Coordinator', 1, 'Active', '2026-08-06 05:47:35', NULL),
(3, 'HR Officer', 1, 'Active', '2026-08-06 05:47:35', NULL),
(4, 'HR Staff', 1, 'Active', '2026-08-06 05:47:35', NULL),
(5, 'Librarian', 1, 'Active', '2026-08-06 05:47:35', NULL),
(6, 'General Education Instructor', 2, 'Active', '2026-08-06 05:47:35', NULL),
(7, 'English Instructor', 2, 'Active', '2026-08-06 05:47:35', NULL),
(8, 'Mathematics Instructor', 2, 'Active', '2026-08-06 05:47:35', NULL),
(9, 'IT Staff', 3, 'Active', '2026-08-06 05:47:35', NULL),
(10, 'IT Instructor', 3, 'Active', '2026-08-06 05:47:35', NULL),
(11, 'Psychology Instructor', 4, 'Active', '2026-08-06 05:47:35', NULL),
(12, 'Criminology Instructor', 5, 'Active', '2026-08-06 05:47:35', NULL),
(13, 'Tourism Instructor', 6, 'Active', '2026-08-06 05:47:35', NULL);

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
(6, 1, 'BIR Form 2316', '2026-07', NULL, 'TECHNOPRENEURSHIP.docx', 'public/assets/uploads/benefits/1786689743_90f376f3c6.docx', 1, '2026-08-14 06:42:23', '2026-08-14 06:42:23'),
(7, 1, 'SSS', '2026-02', NULL, 'BSIS-BULACAN-BSIS-RESEARCH-FESTIVAL-2025-2026-EMPLOYEE-PORTAL-CAPSTONE.docx', 'assets/uploads/benefits/benefits1786691045_cd50b6f620.docx', 1, '2026-08-14 07:04:05', '2026-08-14 07:04:05'),
(8, 1, 'Pag-IBIG', '2026-02', 'sample', 'BPA-EMPLOYEE-PORTAL.pdf', 'assets/uploads/benefits/benefits1786692021_7130abb94c.pdf', 1, '2026-08-14 07:20:21', '2026-08-14 07:20:21');

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
(2, 'Academic Forum', 'https://meet.jit.si/hr_meeting_69d0b6b27deb8', 3, 6, '2026-04-06 14:58:00', 'scheduled'),
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
(4, 1, 'Payroll Correction', 'Regular Payroll Processing', NULL, '2026-08-13', '2026-08-15', 'Pending', '2026-08-13 17:55:44', NULL, NULL, NULL, NULL, '2026-08-13 17:55:44', '2026-08-13 17:55:44');

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
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `theme` enum('light','dark') DEFAULT 'light',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ep_users`
--

INSERT INTO `ep_users` (`id`, `username`, `password`, `email`, `is_admin`, `is_active`, `theme`, `created_at`, `password_reset_token`, `password_reset_expires`) VALUES
(1, 'Employee 1', '$2y$10$O6XSlGEzC5GCae7BrLAhneWoLgqV3P1Pi3a0czwSdmZ.6.kR8F9va', 'crobertjanssen@gmail.com', 0, 1, 'light', '2026-01-28 07:21:13', NULL, NULL),
(2, 'Employee 2', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', 'crobertjanssen@gmail.com', 0, 1, 'light', '2026-03-24 18:06:12', NULL, NULL),
(3, 'Admin Employee Portal', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', NULL, 1, 1, 'light', '2026-01-28 07:21:13', NULL, NULL),
(4, 'Employee 3', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', NULL, 0, 1, 'light', '2026-01-28 07:21:13', NULL, NULL);

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
(1023, 'Robert', NULL, 'Campos', 12, 'active', '2026-08-09', 32, 26, 53);

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
(53, 'Portal', 26);

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

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `approved_by`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sick Leave', '2026-08-04', '2026-08-05', 'Feeling unwell and need to rest.', 'Approved', 2, 'Approved by HR.', '2026-08-01 01:15:00', '2026-08-02 02:30:00'),
(2, 3, 'Vacation Leave', '2026-08-10', '2026-08-12', 'Personal vacation with family.', 'Approved', 2, 'Leave approved.', '2026-08-03 00:45:00', '2026-08-04 03:20:00'),
(3, 4, 'Emergency Leave', '2026-08-14', '2026-08-14', 'Family emergency that requires immediate attention.', 'Pending', NULL, NULL, '2026-08-13 01:10:00', '2026-08-13 01:10:00'),
(4, 5, 'Sick Leave', '2026-08-18', '2026-08-19', 'Medical consultation and recovery.', 'Pending', NULL, NULL, '2026-08-13 05:25:00', '2026-08-13 05:25:00'),
(5, 6, 'Vacation Leave', '2026-08-24', '2026-08-26', 'Planned personal vacation.', 'Rejected', 2, 'Leave period conflicts with scheduled activities.', '2026-08-08 06:00:00', '2026-08-09 01:45:00'),
(6, 1, 'Vacation Leave', '2026-08-13', '2026-08-15', 'vacation', 'Pending', NULL, NULL, '2026-08-13 15:49:17', '2026-08-13 15:49:17'),
(7, 2, 'Sick Leave', '2026-08-13', '2026-08-13', 'sample', 'Pending', NULL, NULL, '2026-08-13 15:50:14', '2026-08-13 15:55:05'),
(8, 3, 'Bereavement Leave', '2026-08-13', '2026-08-13', 'sample', 'Pending', NULL, NULL, '2026-08-13 15:53:27', '2026-08-13 15:55:09'),
(9, 4, 'Bereavement Leave', '2026-08-13', '2026-08-13', 'sadad', 'Pending', NULL, NULL, '2026-08-13 15:54:08', '2026-08-13 15:55:12'),
(10, 5, 'Paternity Leave', '2026-08-13', '2026-08-13', 'dasda', 'Pending', NULL, NULL, '2026-08-13 15:54:40', '2026-08-13 15:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `pr_payslips`
--

CREATE TABLE `pr_payslips` (
  `payslip_id` int(11) NOT NULL,
  `payroll_run_id` int(11) DEFAULT NULL,
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

INSERT INTO `pr_payslips` (`payslip_id`, `payroll_run_id`, `employee_id`, `gross_pay`, `total_deductions`, `net_pay`, `generated_at`, `is_exit_settlement`, `settlement_id`, `resignation_id`) VALUES
(1, 1001, 1, 25000.00, 2500.00, 22500.00, '2026-07-15 01:00:00', 0, NULL, NULL),
(2, 1002, 1, 25000.00, 2750.00, 22250.00, '2026-07-30 01:00:00', 0, NULL, NULL),
(3, 1003, 2, 30000.00, 3500.00, 26500.00, '2026-07-30 01:30:00', 0, NULL, NULL),
(4, 1004, 3, 28000.00, 3200.00, 24800.00, '2026-08-01 02:00:00', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ta_attendance`
--

CREATE TABLE `ta_attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `recorded_by` enum('MANUAL','QR','SYSTEM') NOT NULL DEFAULT 'MANUAL',
  `status` enum('PRESENT','ABSENT','LATE','EARLY_OUT','PENDING_APPROVAL') NOT NULL DEFAULT 'PENDING_APPROVAL',
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
  `is_working_day` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_attendance`
--

INSERT INTO `ta_attendance` (`attendance_id`, `employee_id`, `shift_id`, `attendance_date`, `time_in`, `time_out`, `recorded_by`, `status`, `is_approved`, `approved_by`, `approval_remarks`, `approved_at`, `created_at`, `updated_at`, `total_hours_worked`, `regular_hours`, `overtime_hours`, `is_within_time_window`, `is_within_timeout_window`, `is_within_shift_hours`, `is_working_day`) VALUES
(40, 1, NULL, '2026-07-30', '2026-07-30 16:40:11', '2026-07-30 16:40:14', 'MANUAL', 'PENDING_APPROVAL', 0, NULL, NULL, NULL, '2026-07-30 08:40:11', '2026-08-13 11:29:08', 0.00, 0.00, 0.00, 1, 1, 1, 1),
(41, 1, NULL, '2026-08-02', '2026-08-02 13:04:32', '2026-08-02 14:36:07', 'MANUAL', 'PENDING_APPROVAL', 0, NULL, NULL, NULL, '2026-08-02 05:04:32', '2026-08-13 11:29:11', 1.53, 1.53, 0.00, 1, 1, 1, 1),
(42, 1, NULL, '2026-08-03', '2026-08-03 01:04:25', '2026-08-03 20:43:11', 'MANUAL', 'PENDING_APPROVAL', 0, NULL, NULL, NULL, '2026-08-02 17:04:25', '2026-08-13 11:29:13', 19.65, 8.00, 11.65, 1, 1, 1, 1);

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
(32, '$2y$10$LscHpCGVKkgXBbqYCp4wuuLq3nWpsnV./3mR6n7LcHnLnBBaFZCGm', 1023, NULL, '2026-08-09 05:31:06', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  ADD PRIMARY KEY (`eer_announcements_id`);

--
-- Indexes for table `em_employees`
--
ALTER TABLE `em_employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ep_benefits_and_government_contribution`
--
ALTER TABLE `ep_benefits_and_government_contribution`
  ADD PRIMARY KEY (`benefit_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

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
  ADD PRIMARY KEY (`leave_id`);

--
-- Indexes for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  ADD PRIMARY KEY (`payslip_id`);

--
-- Indexes for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_attendance_employee` (`employee_id`);

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
-- AUTO_INCREMENT for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  MODIFY `eer_announcements_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `em_employees`
--
ALTER TABLE `em_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ep_benefits_and_government_contribution`
--
ALTER TABLE `ep_benefits_and_government_contribution`
  MODIFY `benefit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ep_notifications`
--
ALTER TABLE `ep_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `ep_notification_recipients`
--
ALTER TABLE `ep_notification_recipients`
  MODIFY `recipient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `ep_online_meetings`
--
ALTER TABLE `ep_online_meetings`
  MODIFY `meetings_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ep_payroll_request`
--
ALTER TABLE `ep_payroll_request`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ep_resignation_requests`
--
ALTER TABLE `ep_resignation_requests`
  MODIFY `resignation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ep_users`
--
ALTER TABLE `ep_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `hrms_department`
--
ALTER TABLE `hrms_department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `hrms_employee`
--
ALTER TABLE `hrms_employee`
  MODIFY `employee_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1024;

--
-- AUTO_INCREMENT for table `hrms_position`
--
ALTER TABLE `hrms_position`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `hrms_roles`
--
ALTER TABLE `hrms_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  MODIFY `payslip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
