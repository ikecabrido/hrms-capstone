-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 04:33 PM
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
-- Table structure for table `bir_contributions`
--

CREATE TABLE `bir_contributions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contribution_number` varchar(100) DEFAULT NULL,
  `status` enum('Submitted','Paid','Pending','Overdue','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bir_contributions`
--

INSERT INTO `bir_contributions` (`id`, `employee_id`, `contribution_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'BIR-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(2, 4, 'BIR-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(3, 6, 'BIR-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(4, 9, 'BIR-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(5, 10, 'BIR-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(6, 11, 'BIR-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(7, 12, 'BIR-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(8, 13, 'BIR-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(9, 14, 'BIR-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(10, 15, 'BIR-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(11, 16, 'BIR-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(12, 17, 'BIR-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(13, 18, 'BIR-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(14, 19, 'BIR-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(15, 20, 'BIR-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00'),
(16, 1, 'BIR-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(17, 4, 'BIR-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(18, 6, 'BIR-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(19, 9, 'BIR-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(20, 10, 'BIR-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(21, 11, 'BIR-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(22, 12, 'BIR-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(23, 13, 'BIR-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(24, 14, 'BIR-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(25, 15, 'BIR-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(26, 16, 'BIR-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(27, 17, 'BIR-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(28, 18, 'BIR-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(29, 19, 'BIR-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(30, 20, 'BIR-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `cm_clinic_reports`
--

CREATE TABLE `cm_clinic_reports` (
  `report_id` int(10) NOT NULL,
  `report_type` enum('Daily','Weekly','Monthly','Custom','Annual') DEFAULT NULL,
  `report_date` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(10) DEFAULT NULL,
  `status` enum('Generated','Processing','Error') DEFAULT 'Generated',
  `file_path` varchar(500) DEFAULT NULL,
  `file_format` enum('PDF','Excel','HTML','JSON') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_document_attachments`
--

CREATE TABLE `cm_document_attachments` (
  `attachment_id` int(10) NOT NULL,
  `record_id` int(10) DEFAULT NULL,
  `document_type` enum('Lab Result','X-Ray','Prescription','Medical Certificate','Other') DEFAULT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_emergency_cases`
--

CREATE TABLE `cm_emergency_cases` (
  `case_id` int(10) NOT NULL,
  `patient_id` int(10) NOT NULL,
  `incident_date` datetime NOT NULL,
  `incident_type` enum('Accident','Medical Emergency','Injury','Other','Illness','Fainting','Allergic Reaction') DEFAULT 'Other',
  `severity_level` enum('Low','Medium','High','Critical','Minor') DEFAULT 'Medium',
  `chief_complaint` text DEFAULT NULL,
  `initial_assessment` text DEFAULT NULL,
  `treatment_provided` text DEFAULT NULL,
  `attending_staff` varchar(255) DEFAULT NULL,
  `case_status` enum('Active','Resolved','Transferred','Closed','Open') DEFAULT 'Active',
  `ambulance_called` tinyint(1) DEFAULT 0,
  `ambulance_arrival_time` datetime DEFAULT NULL,
  `parents_notified` tinyint(1) DEFAULT 0,
  `parent_notification_time` datetime DEFAULT NULL,
  `witness_names` text DEFAULT NULL,
  `transfer_hospital` varchar(200) DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `cm_emergency_cases`
--
DELIMITER $$
CREATE TRIGGER `tr_emergency_case_close` BEFORE UPDATE ON `cm_emergency_cases` FOR EACH ROW BEGIN
    IF NEW.case_status = 'Closed' AND OLD.case_status != 'Closed' THEN
        SET NEW.updated_at = NOW();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_medical_records`
--

CREATE TABLE `cm_medical_records` (
  `record_id` int(10) NOT NULL,
  `patient_id` int(10) NOT NULL,
  `visit_date` datetime NOT NULL,
  `chief_complaint` text NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `consultation_type` enum('Walk-in','Appointment','Emergency','Follow-up') DEFAULT NULL,
  `status` enum('Completed','Pending','Follow-up') DEFAULT 'Pending',
  `attending_physician` varchar(150) DEFAULT NULL,
  `vital_signs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vital_signs`)),
  `medications_prescribed` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_medicine_inventory`
--

CREATE TABLE `cm_medicine_inventory` (
  `medicine_id` int(10) NOT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `dosage_form` enum('Tablet','Capsule','Liquid','Injection','Ointment','Other') DEFAULT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(8,2) DEFAULT NULL,
  `selling_price` decimal(8,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_id` int(10) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `storage_requirements` text DEFAULT NULL,
  `status` enum('Available','Low Stock','Out of Stock','Expired') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cm_medicine_inventory`
--

INSERT INTO `cm_medicine_inventory` (`medicine_id`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `current_stock`, `reorder_level`, `unit_cost`, `selling_price`, `expiry_date`, `supplier_id`, `manufacturer`, `storage_requirements`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'Paracetamol', 'Acetaminophen', 'Analgesic', 'Tablet', '500mg', 500, 50, 2.50, NULL, '2026-12-31', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(2, 'Ibuprofen', 'Ibuprofen', 'Analgesic', 'Tablet', '400mg', 300, 30, 3.75, NULL, '2026-11-30', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(3, 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', 200, 25, 8.50, NULL, '2026-10-31', 2, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(4, 'Omeprazole', 'Omeprazole', 'Antacid', 'Capsule', '20mg', 150, 20, 6.25, NULL, '2027-01-31', 2, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL),
(5, 'Loratadine', 'Loratadine', 'Antihistamine', 'Tablet', '10mg', 400, 40, 4.00, NULL, '2026-09-30', 1, NULL, NULL, 'Available', '2026-08-15 14:36:16', '2026-08-15 14:36:16', NULL);

--
-- Triggers `cm_medicine_inventory`
--
DELIMITER $$
CREATE TRIGGER `tr_medicine_stock_update` BEFORE UPDATE ON `cm_medicine_inventory` FOR EACH ROW BEGIN
    IF NEW.expiry_date < CURDATE() THEN
        SET NEW.status = 'Expired';
    ELSEIF NEW.current_stock <= 0 THEN
        SET NEW.status = 'Out of Stock';
    ELSEIF NEW.current_stock <= NEW.reorder_level THEN
        SET NEW.status = 'Low Stock';
    ELSE
        SET NEW.status = 'Available';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_medicine_usage_logs`
--

CREATE TABLE `cm_medicine_usage_logs` (
  `log_id` int(10) NOT NULL,
  `medicine_id` int(10) NOT NULL,
  `record_id` int(10) DEFAULT NULL,
  `usage_date` datetime NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `remaining_stock` int(11) NOT NULL,
  `purpose` varchar(200) DEFAULT NULL,
  `used_by` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_patients`
--

CREATE TABLE `cm_patients` (
  `patient_id` int(10) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `patient_type` enum('Staff','Faculty') DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cm_suppliers`
--

CREATE TABLE `cm_suppliers` (
  `supplier_id` int(10) NOT NULL,
  `supplier_code` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cm_suppliers`
--

INSERT INTO `cm_suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `status`, `created_at`) VALUES
(1, 'MEDSUP001', 'MediCare Pharmaceuticals', 'John Smith', '123-456-7890', 'john@medicare.com', NULL, NULL, 'Active', '2026-08-15 14:36:16'),
(2, 'MEDSUP002', 'HealthPlus Supplies', 'Maria Santos', '098-765-4321', 'maria@healthplus.com', NULL, NULL, 'Active', '2026-08-15 14:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `cm_vital_signs`
--

CREATE TABLE `cm_vital_signs` (
  `vital_sign_id` int(10) NOT NULL,
  `record_id` int(10) NOT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `oxygen_saturation` decimal(3,1) DEFAULT NULL,
  `blood_sugar` decimal(5,1) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_announcements`
--

CREATE TABLE `eer_announcements` (
  `eer_announcements_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `target_audience` varchar(100) DEFAULT 'all',
  `type` enum('announcement','recognition','department_update') DEFAULT 'announcement',
  `department` varchar(100) DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'normal',
  `category` varchar(100) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_award_history`
--

CREATE TABLE `eer_award_history` (
  `eer_award_history_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `award_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  `nominated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `award_type` enum('employee_of_month','performance_award','achievement','special_recognition') DEFAULT 'special_recognition',
  `points` int(11) DEFAULT 0,
  `status` enum('nominated','shortlisted','winner','archived') DEFAULT 'nominated',
  `vote_count` int(11) DEFAULT 0,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `month_year` varchar(7) DEFAULT NULL,
  `award_icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_award_votes`
--

CREATE TABLE `eer_award_votes` (
  `eer_award_vote_id` int(11) NOT NULL,
  `award_history_id` int(11) NOT NULL,
  `voter_user_id` int(11) NOT NULL,
  `nominee_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_badges`
--

CREATE TABLE `eer_badges` (
  `eer_badge_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `points_value` int(11) DEFAULT 10,
  `category` varchar(100) DEFAULT 'achievement',
  `requirement_type` varchar(100) DEFAULT 'manual',
  `requirement_value` int(11) DEFAULT NULL,
  `status` enum('active','inactive','retired') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_comments`
--

CREATE TABLE `eer_comments` (
  `eer_comment_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('employee','user') DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_employee_badges`
--

CREATE TABLE `eer_employee_badges` (
  `eer_employee_badge_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `badge_id` int(11) DEFAULT NULL,
  `awarded_at` datetime DEFAULT current_timestamp(),
  `awarded_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `performance_linked` tinyint(1) DEFAULT 0,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_forums`
--

CREATE TABLE `eer_forums` (
  `eer_forum_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `eer_grievance_attendance_links`
--

CREATE TABLE `eer_grievance_attendance_links` (
  `id` int(11) NOT NULL,
  `grievance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `attendance_status` varchar(50) DEFAULT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `early_out_minutes` int(11) DEFAULT 0,
  `linked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_grievance_payroll`
--

CREATE TABLE `eer_grievance_payroll` (
  `eer_grievance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_module` enum('Salary Calculation','Payslip Generation','Benefits Management','Tax & Deductions','Compliance & Statutory Reporting') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `complaint_title` varchar(150) NOT NULL,
  `complaint_details` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Under Review','Resolved','Rejected','Closed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_grievance_updates`
--

CREATE TABLE `eer_grievance_updates` (
  `id` int(11) NOT NULL,
  `grievance_id` int(11) DEFAULT NULL,
  `update_text` text DEFAULT NULL,
  `updated_by_employee_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_groups`
--

CREATE TABLE `eer_groups` (
  `eer_group_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_group_members`
--

CREATE TABLE `eer_group_members` (
  `eer_group_member_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_messages`
--

CREATE TABLE `eer_messages` (
  `eer_message_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_notifications`
--

CREATE TABLE `eer_notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_policies`
--

CREATE TABLE `eer_policies` (
  `eer_policy_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `target_audience` varchar(100) DEFAULT 'all',
  `category` varchar(100) DEFAULT 'general',
  `effective_date` date DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_projects`
--

CREATE TABLE `eer_projects` (
  `eer_project_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'planning',
  `created_by_employee_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_reactions`
--

CREATE TABLE `eer_reactions` (
  `eer_reaction_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('employee','user') NOT NULL DEFAULT 'employee',
  `type` enum('like','heart','wow') DEFAULT 'like',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_recognitions`
--

CREATE TABLE `eer_recognitions` (
  `eer_recognition_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT 'general',
  `source` enum('manual','performance','achievement') DEFAULT 'manual',
  `performance_report_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `leaderboard_position` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_replies`
--

CREATE TABLE `eer_replies` (
  `eer_reply_id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `parent_reply_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('employee','user') DEFAULT 'employee',
  `content` text DEFAULT NULL,
  `mentioned_user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_rewards`
--

CREATE TABLE `eer_rewards` (
  `eer_reward_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `points_required` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'general',
  `icon` varchar(255) DEFAULT NULL,
  `tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `performance_requirement` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_reward_redemptions`
--

CREATE TABLE `eer_reward_redemptions` (
  `eer_reward_redemption_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `reward_id` int(11) DEFAULT NULL,
  `points_used` int(11) DEFAULT NULL,
  `redeemed_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_social_posts`
--

CREATE TABLE `eer_social_posts` (
  `eer_social_post_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `author_type` enum('employee','user') NOT NULL,
  `item_type` enum('post','file') DEFAULT 'post',
  `content` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_surveys`
--

CREATE TABLE `eer_surveys` (
  `eer_survey_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `survey_type` varchar(100) DEFAULT 'engagement',
  `created_by_employee_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `feedback_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_answers`
--

CREATE TABLE `eer_survey_answers` (
  `eer_survey_answer_id` int(11) NOT NULL,
  `response_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_feedback`
--

CREATE TABLE `eer_survey_feedback` (
  `eer_survey_feedback_id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_feedback_id`
--

CREATE TABLE `eer_survey_feedback_id` (
  `eer_survey_feedback_id_id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `evaluator_type` varchar(50) DEFAULT 'Self',
  `category` varchar(100) DEFAULT 'general',
  `is_anonymous` tinyint(1) DEFAULT 0,
  `evaluation_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_questions`
--

CREATE TABLE `eer_survey_questions` (
  `eer_survey_question_id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `question_text` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_responses`
--

CREATE TABLE `eer_survey_responses` (
  `eer_survey_response_id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `target_employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eer_survey_targets`
--

CREATE TABLE `eer_survey_targets` (
  `eer_survey_target_id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `employee_certifications`
--

INSERT INTO `employee_certifications` (`cert_id`, `employee_id`, `cert_name`, `issuing_organization`, `date_issued`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Certificate of Training in Information Technology', 'TESDA', '2025-06-15', NULL, '2026-08-13 19:13:07', NULL),
(2, 2, 'Certificate of Completion in Computer Training', 'TESDA', '2024-08-20', NULL, '2026-08-13 19:13:07', NULL),
(3, 3, 'Information Technology Training Certificate', 'TESDA', '2025-02-10', NULL, '2026-08-13 19:13:07', NULL),
(4, 4, 'Basic IT Support Certification', 'Technical Education Institute', '2025-05-12', '2028-05-12', '2026-08-13 19:13:07', NULL),
(5, 9, 'Teaching Methodology Certificate', 'TESDA', '2024-06-18', NULL, '2026-08-13 19:13:07', NULL),
(6, 10, 'English Language Teaching Certificate', 'Professional Education Institute', '2025-03-10', NULL, '2026-08-13 19:13:07', NULL),
(7, 11, 'Mathematics Teaching Certificate', 'Professional Education Institute', '2024-11-22', NULL, '2026-08-13 19:13:07', NULL),
(8, 13, 'Psychology Seminar Certificate', 'Psychological Association of the Philippines', '2025-01-15', NULL, '2026-08-13 19:13:07', NULL),
(9, 17, 'Criminology Training Certificate', 'Philippine Criminology Association', '2025-04-08', NULL, '2026-08-13 19:13:07', NULL),
(10, 21, 'Tourism and Hospitality Training Certificate', 'TESDA', '2025-02-20', NULL, '2026-08-13 19:13:07', NULL),
(11, 25, 'Technical-Vocational Education Certificate', 'TESDA', '2025-06-01', NULL, '2026-08-13 19:13:07', NULL);

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
(1, 1, 'Document Uploaded', NULL, '', 'N/A', 'training cert.', '2026-08-07', 'Uploaded document: training cert. (Other)', 'System', NULL, NULL, '2026-08-07 14:44:05'),
(2, 3, 'Personal Info Update', NULL, '', 'Previous data', 'Updated personal information', '2026-08-12', 'Updated personal details', 'Employee', NULL, NULL, '2026-08-11 20:04:04'),
(3, 2, 'Document Uploaded', NULL, '', 'N/A', 'Resume', '2026-08-12', 'Uploaded document: Resume (Other)', 'System', NULL, NULL, '2026-08-12 02:48:05'),
(4, 4, 'Document Uploaded', NULL, '', 'N/A', 'Resume', '2026-08-13', 'Uploaded document: Resume (Contract)', 'System', NULL, NULL, '2026-08-13 11:41:05');

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
(1, 1, 'training cert.', 'Other', '../../assets/documents/2026/08/6a75ef357ca3a_724368498_1322572223322433_1893840578082960664_n.png', '724368498_1322572223322433_1893840578082960664_n.png', '1844150', NULL, '2026-08-07 14:44:05', NULL, NULL, 'Other', NULL),
(2, 2, 'Resume', 'Other', '../../assets/documents/2026/08/6a7bdee5b0bf0_Billingandclaims.docx', 'Billing and claims.docx', '168703', NULL, '2026-08-12 02:48:05', NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Resume/CV', '2026-08-12'),
(3, 4, 'Resume', 'Contract', '../../assets/documents/2026/08/6a7dad51e5bfb_721402886_3168808403311507_8912135982123999394_n.png', '721402886_3168808403311507_8912135982123999394_n.png', '1751854', NULL, '2026-08-13 11:41:05', NULL, 'image/png', 'Resume/CV', '2027-08-12');

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
-- Table structure for table `employee_requirements`
--

CREATE TABLE `employee_requirements` (
  `requirement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `requirement_name` varchar(100) NOT NULL,
  `status` enum('Submitted','Missing','For Follow-up') NOT NULL DEFAULT 'Missing',
  `remarks` text DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_requirements`
--

INSERT INTO `employee_requirements` (`requirement_id`, `employee_id`, `document_id`, `requirement_name`, `status`, `remarks`, `submitted_date`, `follow_up_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Training Certificate', 'Submitted', 'Document has been submitted and recorded.', '2026-08-07', NULL, '2026-08-13 19:24:44', NULL),
(2, 1, NULL, 'Resume/CV', 'Missing', 'Employee has not yet submitted a resume.', NULL, '2026-08-20', '2026-08-13 19:24:44', NULL),
(3, 1, NULL, 'PSA Birth Certificate', 'For Follow-up', 'Follow up with employee regarding submission.', NULL, '2026-08-20', '2026-08-13 19:24:44', NULL),
(4, 2, 2, 'Resume/CV', 'Submitted', 'Resume has been uploaded.', '2026-08-12', NULL, '2026-08-13 19:24:44', NULL),
(5, 2, NULL, 'Transcript of Records', 'Missing', 'Transcript of Records is not yet submitted.', NULL, '2026-08-20', '2026-08-13 19:24:44', NULL),
(6, 2, NULL, 'Diploma', 'For Follow-up', 'Employee needs to submit a copy of diploma.', NULL, '2026-08-20', '2026-08-13 19:24:44', NULL),
(7, 4, 3, 'Resume/CV', 'Submitted', 'Resume document has been uploaded.', '2026-08-13', NULL, '2026-08-13 19:24:44', NULL),
(8, 4, NULL, 'NBI Clearance', 'Missing', 'NBI Clearance is not yet submitted.', NULL, '2026-08-22', '2026-08-13 19:24:44', NULL),
(9, 9, NULL, 'Resume/CV', 'For Follow-up', 'Follow up with faculty member for resume submission.', NULL, '2026-08-20', '2026-08-13 19:24:44', NULL),
(10, 13, NULL, 'Transcript of Records', 'Missing', 'Faculty member has not yet submitted TOR.', NULL, '2026-08-22', '2026-08-13 19:24:44', NULL),
(11, 17, NULL, 'NBI Clearance', 'For Follow-up', 'Follow up regarding NBI Clearance.', NULL, '2026-08-22', '2026-08-13 19:24:44', NULL),
(12, 21, NULL, 'Medical Certificate', 'Missing', 'Medical certificate has not yet been submitted.', NULL, '2026-08-25', '2026-08-13 19:24:44', NULL),
(13, 25, NULL, 'Diploma', 'For Follow-up', 'Follow up with faculty regarding diploma submission.', NULL, '2026-08-25', '2026-08-13 19:24:44', NULL);

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
(1, 'EMP-000001', 4, 'Ronaldo', 'G.', 'Raymundo', NULL, 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, 'ronaldocruz22@gmail.com', '09123456789', '0287654321', 'San Jose Del Monte, Bulacan', NULL, 3, 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', 20000.00, '2026-08-06 13:47:35', '2026-08-13 15:17:40', 0, NULL, NULL),
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
(20, 'EMP-000020', NULL, 'Ian', '', 'Perez', NULL, NULL, '1990-01-11', NULL, NULL, NULL, NULL, 'admin@gmail.com', '', '', '', NULL, 5, NULL, '2026-08-13', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-13 11:18:56', '2026-08-13 15:17:40', 0, NULL, NULL),
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
-- Table structure for table `em_roles`
--

CREATE TABLE `em_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `em_roles`
--

INSERT INTO `em_roles` (`role_id`, `role_name`, `description`, `status`, `created_at`) VALUES
(1, 'System Administrator', 'Full system administration access', 'Active', '2026-08-14 07:50:12'),
(2, 'Recruitment Staff', 'Handles recruitment and hiring processes', 'Active', '2026-08-14 07:50:12'),
(3, 'Employee Management Staff', 'Handles employee records and employee information', 'Active', '2026-08-14 07:50:12'),
(4, 'Payroll Staff', 'Handles payroll and compensation processing', 'Active', '2026-08-14 07:50:12'),
(5, 'Time and Attendance Staff', 'Handles attendance, schedules, and timekeeping', 'Active', '2026-08-14 07:50:12'),
(6, 'Performance Management Staff', 'Handles employee performance and appraisal', 'Active', '2026-08-14 07:50:12'),
(7, 'Learning and Development Staff', 'Handles training and employee development', 'Active', '2026-08-14 07:50:12'),
(8, 'Compliance Staff', 'Handles HR compliance and regulatory records', 'Active', '2026-08-14 07:50:12'),
(9, 'Workforce Management Staff', 'Handles workforce planning and analytics', 'Active', '2026-08-14 07:50:12'),
(10, 'Exit Management Staff', 'Handles resignation, termination, and employee exit processes', 'Active', '2026-08-14 07:50:12'),
(11, 'Clinic Staff', 'Handles employee clinic and health-related records', 'Active', '2026-08-14 07:50:12'),
(12, 'Engagement Staff', 'Handles employee engagement activities', 'Active', '2026-08-14 07:50:12'),
(13, 'Employee', 'Regular employee access', 'Active', '2026-08-14 07:50:12');

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
  `role` varchar(50) DEFAULT NULL,
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

INSERT INTO `ep_users` (`id`, `username`, `password`, `email`, `role`, `is_admin`, `is_active`, `theme`, `created_at`, `password_reset_token`, `password_reset_expires`) VALUES
(1, 'Employee 1', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', 'sample@gmail.com', 'employee_portal', 0, 1, 'light', '2026-01-28 07:21:13', NULL, NULL),
(2, 'Employee 2', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', 'crobertjanssen@gmail.com', 'employee_portal', 0, 1, 'light', '2026-03-24 18:06:12', NULL, NULL),
(3, 'Admin Employee Portal', '$2y$10$GF34eDR6uEqpxNIovwKmRu2A6u3ALXgmMkn8zBdoREYLb1Em0euAK', NULL, 'employee_portal', 1, 1, 'light', '2026-01-28 07:21:13', NULL, NULL),
(4, 'Employee 3', '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.', NULL, 'employee_portal', 0, 1, 'light', '2026-01-28 07:21:13', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exit_archive`
--

CREATE TABLE `exit_archive` (
  `id` int(11) NOT NULL,
  `archive_type` enum('resignation','settlement','interview','document','survey','transfer_plan','transfer_item','termination') NOT NULL,
  `original_id` int(11) NOT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `original_created_by` int(11) DEFAULT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archive_reason` varchar(255) DEFAULT NULL,
  `archive_data` longtext DEFAULT NULL COMMENT 'JSON data of the original record',
  `restored` tinyint(1) DEFAULT 0,
  `restored_by` int(11) DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_documents`
--

CREATE TABLE `exit_documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `document_type` enum('resignation_letter','clearance_form','handover_document','certificate','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_employee_settlements`
--

CREATE TABLE `exit_employee_settlements` (
  `settlement_id` int(11) NOT NULL,
  `employee_id` int(10) NOT NULL,
  `exit_case_type` enum('resignation','termination') NOT NULL,
  `exit_case_id` int(11) NOT NULL,
  `last_working_date` date NOT NULL,
  `payroll_settlement_id` int(11) DEFAULT NULL,
  `status` enum('pending','requested','processing','calculated','for_approval','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
  `requested_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interviews`
--

CREATE TABLE `exit_interviews` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) NOT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `location` varchar(255) DEFAULT 'Virtual',
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interview_hr_assessments`
--

CREATE TABLE `exit_interview_hr_assessments` (
  `id` int(11) NOT NULL,
  `interview_id` int(11) NOT NULL,
  `summary` text DEFAULT NULL,
  `key_findings` text DEFAULT NULL,
  `hr_recommendations` text DEFAULT NULL,
  `follow_up_actions` text DEFAULT NULL,
  `rehire_eligibility` enum('yes','no','conditional') DEFAULT NULL,
  `knowledge_transfer_required` tinyint(1) DEFAULT 0,
  `clearance_recommendation` enum('clear','not_clear','pending') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_knowledge_transfer_plans`
--

CREATE TABLE `exit_knowledge_transfer_plans` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) NOT NULL,
  `successor_id` int(10) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_resignations`
--

CREATE TABLE `exit_resignations` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) NOT NULL,
  `reason` text NOT NULL,
  `notice_date` date NOT NULL,
  `last_working_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `preclearance_desk_person` int(11) DEFAULT NULL,
  `status` enum('pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn') NOT NULL DEFAULT 'pending_review',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_from_status` enum('pending','approved','rejected','withdrawn') DEFAULT NULL,
  `hr_approved_by` int(11) DEFAULT NULL,
  `hr_approved_at` datetime DEFAULT NULL,
  `hr_approval_comments` text DEFAULT NULL,
  `legal_approved_by` int(11) DEFAULT NULL,
  `legal_approved_at` datetime DEFAULT NULL,
  `legal_approval_comments` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `resignation_letter_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id` int(10) DEFAULT NULL,
  `exit_case_type` enum('resignation','termination') DEFAULT NULL,
  `exit_case_id` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `approval_status` enum('draft','scheduled','approved','archived') NOT NULL DEFAULT 'scheduled',
  `employee_status_updated` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exit_surveys`
--

INSERT INTO `exit_surveys` (`id`, `title`, `description`, `target_audience`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`, `employee_id`, `exit_case_type`, `exit_case_id`, `scheduled_date`, `scheduled_time`, `approval_status`, `employee_status_updated`) VALUES
(1, 'Post-Exit Survey for John Carlo Garcia', 'test', 'all', '2026-08-14', '2026-08-14', 'active', 10, '2026-08-14 13:06:27', '2026-08-14 13:06:27', 14, 'termination', 9, '2026-08-14', '00:00:00', 'scheduled', 0);

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
  `employee_id` int(10) DEFAULT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responses`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_terminations`
--

CREATE TABLE `exit_terminations` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) NOT NULL,
  `termination_reason` text NOT NULL,
  `effective_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `status` enum('pending_review','pending_legal_review','approved','rejected','rejected_by_legal','withdrawn') NOT NULL DEFAULT 'pending_review',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `legal_approved_by` int(11) DEFAULT NULL,
  `legal_approved_at` datetime DEFAULT NULL,
  `legal_approval_comments` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
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
-- Table structure for table `kpi_assignments`
--

CREATE TABLE `kpi_assignments` (
  `assignment_id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `assignee_id` int(10) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `target_value` decimal(14,4) NOT NULL,
  `stretch_target_value` decimal(14,4) DEFAULT NULL,
  `min_acceptable_value` decimal(14,4) DEFAULT NULL,
  `period_type` enum('Daily','Weekly','Bi-weekly','Monthly','Quarterly','Semi-annually','Annually') NOT NULL DEFAULT 'Monthly',
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT 100.00,
  `assignment_status` enum('Draft','Active','Paused','Completed','Cancelled') DEFAULT 'Active',
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_attachments`
--

CREATE TABLE `kpi_attachments` (
  `attachment_id` int(11) NOT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_by_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_categories`
--

CREATE TABLE `kpi_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `category_description` text DEFAULT NULL,
  `category_color` varchar(20) DEFAULT '#3498db',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_comments`
--

CREATE TABLE `kpi_comments` (
  `comment_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `comment_type` enum('General','Question','Action Item','Feedback','Resolution') DEFAULT 'General',
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` varchar(100) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_definitions`
--

CREATE TABLE `kpi_definitions` (
  `kpi_id` int(11) NOT NULL,
  `kpi_code` varchar(50) NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `kpi_description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `kpi_type` enum('Financial','Operational','HR','Customer','Quality','Safety','Other') NOT NULL DEFAULT 'Operational',
  `measurement_unit` varchar(50) DEFAULT NULL,
  `calculation_formula` text DEFAULT NULL,
  `data_source` varchar(150) DEFAULT NULL,
  `benchmark_value` decimal(14,4) DEFAULT NULL,
  `target_direction` enum('Higher is Better','Lower is Better','Target Range') NOT NULL DEFAULT 'Higher is Better',
  `min_target_value` decimal(14,4) DEFAULT NULL,
  `max_target_value` decimal(14,4) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT 100.00,
  `is_active` tinyint(1) DEFAULT 1,
  `default_frequency` enum('Daily','Weekly','Bi-weekly','Monthly','Quarterly','Semi-annually','Annually') DEFAULT 'Monthly',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_entries`
--

CREATE TABLE `kpi_entries` (
  `entry_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `reporting_period` varchar(50) DEFAULT NULL,
  `actual_value` decimal(14,4) NOT NULL,
  `target_value` decimal(14,4) DEFAULT NULL,
  `variance_value` decimal(14,4) DEFAULT NULL,
  `variance_percentage` decimal(8,4) DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `performance_status` enum('Not Started','At Risk','Behind','On Track','Exceeds Target','Completed') DEFAULT 'Not Started',
  `entry_notes` text DEFAULT NULL,
  `evidence_file_path` varchar(500) DEFAULT NULL,
  `captured_by` varchar(100) DEFAULT NULL,
  `captured_by_name` varchar(255) DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_by_name` varchar(255) DEFAULT NULL,
  `review_status` enum('Pending Review','Reviewed','Verified','Disputed') DEFAULT 'Pending Review',
  `review_comments` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_history`
--

CREATE TABLE `kpi_history` (
  `history_id` int(11) NOT NULL,
  `kpi_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `action_type` enum('Create','Update','Delete','Status Change','Review','Approval','Comment','Attachment') NOT NULL,
  `field_changed` varchar(150) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `action_details` text DEFAULT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `performed_by_name` varchar(255) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_targets`
--

CREATE TABLE `kpi_targets` (
  `target_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `target_period` varchar(50) NOT NULL,
  `target_period_start` date NOT NULL,
  `target_period_end` date NOT NULL,
  `baseline_value` decimal(14,4) DEFAULT NULL,
  `target_value` decimal(14,4) NOT NULL,
  `stretch_target_value` decimal(14,4) DEFAULT NULL,
  `actual_value` decimal(14,4) DEFAULT NULL,
  `achievement_percentage` decimal(8,4) DEFAULT NULL,
  `target_status` enum('Pending','In Progress','Met','Exceeded','Missed') DEFAULT 'Pending',
  `set_by` varchar(100) DEFAULT NULL,
  `set_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_acknowledgment_log`
--

CREATE TABLE `lc_acknowledgment_log` (
  `acknowledgment_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `policy_id` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `status` enum('Acknowledged','Pending','Declined') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_acknowledgment_log`
--

INSERT INTO `lc_acknowledgment_log` (`acknowledgment_id`, `employee_id`, `policy_id`, `acknowledged_at`, `status`) VALUES
(1, 6, 1, '2026-07-01 09:00:00', 'Acknowledged'),
(2, 9, 2, '2026-07-02 10:30:00', 'Acknowledged'),
(3, 10, 3, NULL, 'Pending'),
(4, 11, 1, '2026-07-04 08:15:00', 'Acknowledged'),
(5, 12, 4, '2026-07-05 14:20:00', 'Acknowledged'),
(6, 13, 2, NULL, 'Pending'),
(7, 14, 5, '2026-07-07 11:45:00', 'Acknowledged'),
(8, 15, 3, NULL, 'Declined'),
(9, 16, 1, '2026-07-09 16:00:00', 'Acknowledged'),
(10, 17, 4, NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `lc_activity_log`
--

CREATE TABLE `lc_activity_log` (
  `activity_id` int(11) NOT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `activity_date` datetime DEFAULT NULL,
  `actor` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_audits`
--

CREATE TABLE `lc_audits` (
  `audit_id` int(11) NOT NULL,
  `audit_no` varchar(100) NOT NULL,
  `audit_type` enum('Employee Compliance Audit','Government Compliance Audit','Employment Contract Audit','Policy Compliance Audit','Leave Compliance Audit','Document Audit','Recruitment Compliance Audit','Exit Clearance Audit','Incident & Disciplinary Audit','Faculty Qualification Audit','Employee Document Audit','Data Privacy Compliance Audit','Training & Certification Audit','Policy Acknowledgement Audit') DEFAULT 'Employee Compliance Audit',
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `auditor_name` varchar(255) DEFAULT NULL,
  `audit_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','Overdue') DEFAULT 'Scheduled',
  `scope` text DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `methodology` text DEFAULT NULL,
  `findings_count` int(11) DEFAULT 0,
  `critical_count` int(11) DEFAULT 0,
  `major_count` int(11) DEFAULT 0,
  `minor_count` int(11) DEFAULT 0,
  `compliance_score` int(11) DEFAULT NULL,
  `overall_rating` enum('Excellent','Good','Satisfactory','Needs Improvement','Poor') DEFAULT NULL,
  `report_file` varchar(500) DEFAULT NULL,
  `report_status` enum('Not Generated','Draft','Submitted','Approved','Returned') DEFAULT 'Not Generated',
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_audits`
--

INSERT INTO `lc_audits` (`audit_id`, `audit_no`, `audit_type`, `department_id`, `department_name`, `auditor_id`, `auditor_name`, `audit_date`, `start_date`, `end_date`, `status`, `scope`, `objective`, `methodology`, `findings_count`, `critical_count`, `major_count`, `minor_count`, `compliance_score`, `overall_rating`, `report_file`, `report_status`, `created_by`, `created_by_name`, `created_at`, `updated_at`) VALUES
(1, 'AUD-2026-0001', 'Employee Compliance Audit', NULL, 'Human Resources', NULL, 'Maria Santos', '2026-08-01', '2026-08-01', '2026-08-05', 'Completed', 'Full employee records review', 'Verify all employee documentation is complete and compliant', NULL, 3, 0, 1, 2, 85, 'Good', NULL, 'Not Generated', NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(2, 'AUD-2026-0002', 'Government Compliance Audit', NULL, 'Human Resources', NULL, 'Juan Dela Cruz', '2026-08-10', '2026-08-10', '2026-08-14', 'In Progress', 'SSS, PhilHealth, Pag-IBIG, BIR contributions', 'Verify government remittances are accurate and timely', NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'Not Generated', NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 02:27:32'),
(3, 'AUD-2026-0003', 'Employment Contract Audit', NULL, 'Human Resources', NULL, 'Ana Reyes', '2026-08-15', '2026-08-15', '2026-08-20', 'Scheduled', 'All active employment contracts', 'Ensure contracts are current and legally compliant', NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'Not Generated', NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(4, 'AUD-2026-0004', 'Policy Compliance Audit', NULL, 'All Departments', NULL, 'Carlos Mendoza', '2026-09-01', '2026-09-01', '2026-09-05', 'In Progress', 'Policy acknowledgement and adherence', 'Verify all employees have acknowledged current policies', NULL, 0, 0, 0, 0, NULL, NULL, NULL, 'Not Generated', NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 02:27:48'),
(5, 'AUD-2026-0005', 'Exit Clearance Audit', NULL, 'Human Resources', NULL, 'Lisa Garcia', '2026-09-10', '2026-09-10', '2026-09-15', 'In Progress', 'Exit clearance procedures', 'Verify exit clearance process completeness', NULL, 1, 0, 0, 1, NULL, NULL, NULL, 'Draft', NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 03:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `lc_audit_checklists`
--

CREATE TABLE `lc_audit_checklists` (
  `checklist_id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `category` varchar(255) DEFAULT 'General',
  `item_name` varchar(500) NOT NULL,
  `result` enum('Compliant','Non-Compliant','N/A','Pending') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lc_audit_checklists`
--

INSERT INTO `lc_audit_checklists` (`checklist_id`, `audit_id`, `category`, `item_name`, `result`, `remarks`, `checked_by`, `checked_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Employee Records', 'Complete Personal Information', 'Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(2, 1, 'Employee Records', 'Employment Contract', 'Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(3, 1, 'Employee Records', 'Government IDs', 'Non-Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(4, 1, 'Government Compliance', 'SSS Contribution', 'Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(5, 1, 'Government Compliance', 'PhilHealth Contribution', 'Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(6, 1, 'Policy Compliance', 'Handbook Signed', 'Compliant', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(7, 1, 'Policy Compliance', 'NDA Signed', 'N/A', NULL, 1, '2026-08-06 15:49:09', '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(8, 2, 'Government Compliance', 'SSS Records', 'Pending', NULL, NULL, NULL, '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(9, 2, 'Government Compliance', 'PhilHealth Records', 'Pending', NULL, NULL, NULL, '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(10, 3, 'Employment Contracts', 'Contract Current', 'Pending', NULL, NULL, NULL, '2026-08-06 07:49:09', '2026-08-06 07:49:09'),
(11, 3, 'Employment Contracts', ' legally Compliant', 'Pending', NULL, NULL, NULL, '2026-08-06 07:49:09', '2026-08-06 07:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `lc_audit_corrective_actions`
--

CREATE TABLE `lc_audit_corrective_actions` (
  `action_id` int(11) NOT NULL,
  `action_no` varchar(100) NOT NULL,
  `finding_id` int(11) NOT NULL,
  `finding_no` varchar(100) DEFAULT NULL,
  `audit_id` int(11) DEFAULT NULL,
  `audit_no` varchar(100) DEFAULT NULL,
  `action_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_to_name` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Completed','Overdue','Cancelled') DEFAULT 'Pending',
  `progress_percent` int(11) DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `verification_status` enum('Not Verified','Verified','Rejected') DEFAULT 'Not Verified',
  `verification_notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_by_name` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_audit_corrective_actions`
--

INSERT INTO `lc_audit_corrective_actions` (`action_id`, `action_no`, `finding_id`, `finding_no`, `audit_id`, `audit_no`, `action_type`, `description`, `assigned_to`, `assigned_to_name`, `department_id`, `department_name`, `priority`, `status`, `progress_percent`, `due_date`, `started_at`, `completed_at`, `verification_status`, `verification_notes`, `verified_by`, `verified_by_name`, `verified_at`, `remarks`, `created_by`, `created_by_name`, `created_at`, `updated_at`) VALUES
(1, 'CA-2026-0001', 1, 'FND-2026-0001', 1, 'AUD-2026-0001', 'Document Collection', 'Collect updated proof of billing from 5 employees', NULL, 'Maria Santos', NULL, 'Human Resources', 'High', 'In Progress', 60, '2026-08-15', NULL, NULL, 'Not Verified', NULL, NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(2, 'CA-2026-0002', 2, 'FND-2026-0002', 1, 'AUD-2026-0001', 'Process Improvement', 'Implement automated policy acknowledgement reminders', NULL, 'Juan Dela Cruz', NULL, 'Human Resources', 'Medium', 'Pending', 0, '2026-08-20', NULL, NULL, 'Not Verified', NULL, NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(3, 'CA-2026-0003', 3, 'FND-2026-0003', 1, 'AUD-2026-0001', 'Document Collection', 'Follow up on missing training certificates', NULL, 'Ana Reyes', NULL, 'Human Resources', 'Medium', 'Completed', 100, '2026-08-12', NULL, NULL, 'Not Verified', NULL, NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(4, 'CA-2026-0004', 4, 'FND-2026-0004', 2, 'AUD-2026-0002', 'Financial Reconciliation', 'Reconcile SSS contribution discrepancy and submit adjustment', NULL, 'Juan Dela Cruz', NULL, 'Human Resources', 'Critical', 'In Progress', 30, '2026-08-20', NULL, NULL, 'Not Verified', NULL, NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(5, 'CA-2026-0005', 5, 'FND-2026-0005', 2, 'AUD-2026-0002', 'Process Improvement', 'File late submission explanation for PhilHealth', NULL, 'Ana Reyes', NULL, 'Human Resources', 'High', 'Pending', 0, '2026-08-25', NULL, NULL, 'Not Verified', NULL, NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `lc_audit_findings`
--

CREATE TABLE `lc_audit_findings` (
  `finding_id` int(11) NOT NULL,
  `finding_no` varchar(100) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `audit_no` varchar(100) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `severity` enum('Critical','Major','Minor','Observation') DEFAULT 'Minor',
  `status` enum('Open','In Progress','Resolved','Closed','Escalated') DEFAULT 'Open',
  `finding_title` varchar(255) DEFAULT NULL,
  `finding_description` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_to_name` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_by_name` varchar(255) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_audit_findings`
--

INSERT INTO `lc_audit_findings` (`finding_id`, `finding_no`, `audit_id`, `audit_no`, `category`, `department_id`, `department_name`, `severity`, `status`, `finding_title`, `finding_description`, `evidence`, `recommendation`, `root_cause`, `assigned_to`, `assigned_to_name`, `due_date`, `resolved_at`, `resolved_by`, `resolved_by_name`, `resolution_notes`, `created_by`, `created_by_name`, `created_at`, `updated_at`) VALUES
(1, 'FND-2026-0001', 1, 'AUD-2026-0001', 'Documentation', NULL, 'Human Resources', 'Major', 'In Progress', 'Missing employee address proof', '5 employees do not have updated proof of billing on file', NULL, 'Request updated proof of billing from affected employees within 5 days', NULL, NULL, 'Maria Santos', '2026-08-15', NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(2, 'FND-2026-0002', 1, 'AUD-2026-0001', 'Compliance', NULL, 'Human Resources', 'Minor', 'Open', 'Late policy acknowledgement', '2 employees acknowledged policies 3 days after deadline', NULL, 'Enforce policy acknowledgement deadlines with automated reminders', NULL, NULL, 'Maria Santos', '2026-08-20', NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(3, 'FND-2026-0003', 1, 'AUD-2026-0001', 'Documentation', NULL, 'Human Resources', 'Minor', 'Resolved', 'Incomplete training records', '3 employees have missing mandatory training completion certificates', NULL, 'Follow up with employees to submit training certificates', NULL, NULL, 'Juan Dela Cruz', '2026-08-12', NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(4, 'FND-2026-0004', 2, 'AUD-2026-0002', 'Government Compliance', NULL, 'Human Resources', 'Critical', 'Open', 'SSS contribution mismatch', 'SSS monthly remittance for June 2026 has a discrepancy of PHP 2,500', NULL, 'Reconcile SSS contribution records and submit adjustment', NULL, NULL, 'Juan Dela Cruz', '2026-08-20', NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(5, 'FND-2026-0005', 2, 'AUD-2026-0002', 'Government Compliance', NULL, 'Human Resources', 'Major', 'Open', 'PhilHealth late submission', 'PhilHealth remittance for Q2 2026 was submitted 5 days late', NULL, 'File late submission explanation and ensure timely future submissions', NULL, NULL, 'Ana Reyes', '2026-08-25', NULL, NULL, NULL, NULL, NULL, 'System', '2026-08-04 01:15:10', '2026-08-04 01:15:10'),
(6, 'FND-2026-0006', 5, 'AUD-2026-0005', 'Exit Clearance', NULL, 'Human Resources', 'Minor', 'Open', 'Incomplete exit clearance', 'Exit clearance checklist is incomplete or pending required approvals from departments.', NULL, 'Follow up with pending signatories and complete all clearance requirements.', NULL, NULL, 'Erwin Cruz — HR Manager', '2026-08-06', NULL, NULL, NULL, NULL, 0, 'compliance', '2026-08-04 03:04:30', '2026-08-04 03:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `lc_audit_logs`
--

CREATE TABLE `lc_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` enum('Create','Update','Delete','Approval','Rejection','Upload','Acknowledgement','Export','Print','Login','Other') NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_audit_logs`
--

INSERT INTO `lc_audit_logs` (`id`, `user_id`, `action`, `module`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 66, 'Create', 'Compliance Violation', 'Violation LV-20260731-001 created: Unauthorized overtime without compensation', '127.0.0.1', 'Mozilla/5.0', '2026-07-15 01:00:00'),
(2, 66, 'Update', 'Compliance Violation', 'Case LV-20260731-002 updated to Under Investigation', '127.0.0.1', 'Mozilla/5.0', '2026-07-25 06:00:00'),
(3, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 01:33:33'),
(4, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 01:53:45'),
(5, 0, 'Create', 'Audit Report', '{\"audit_id\":5,\"report_status\":\"Draft\",\"action\":\"generate_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:01:34'),
(6, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:01:41'),
(7, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:06:15'),
(8, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:20:37'),
(9, 0, 'Update', 'Audit Report', '{\"audit_id\":4,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:20:42'),
(10, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:24:59'),
(11, 0, 'Update', 'Audit Report', '{\"audit_id\":2,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:27:32'),
(12, 0, 'Update', 'Audit Report', '{\"audit_id\":4,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:27:48'),
(13, 0, '', 'Audit Report', '{\"action\":\"schedule_report_directress\",\"report_key\":\"sss_compliance_report\",\"report_name\":\"Sss Compliance Report\",\"frequency\":\"Monthly\",\"schedule_id\":1,\"email_sent\":false}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:30:29'),
(14, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:35:29'),
(15, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 02:37:56'),
(16, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:02:31'),
(17, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:02:40'),
(18, 0, 'Create', 'Audit Report', '{\"finding_id\":6,\"finding_no\":\"FND-2026-0006\",\"severity\":\"Minor\",\"action\":\"add_finding\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:04:30'),
(19, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:08:37'),
(20, 0, 'Create', 'Audit Report', '{\"audit_id\":5,\"report_status\":\"Draft\",\"action\":\"generate_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:08:55'),
(21, 0, 'Update', 'Audit Report', '{\"audit_id\":5,\"status\":\"In Progress\",\"action\":\"conduct_audit\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 03:09:20'),
(22, 0, '', 'Audit Report', '{\"report_id\":2,\"report_key\":\"employee_master_list\",\"report_label\":\"Employee Master List\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 04:11:28'),
(23, 0, '', 'Audit Report', '{\"report_id\":3,\"report_key\":\"employee_master_list\",\"report_label\":\"Employee Master List\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 04:11:56'),
(24, 0, '', 'Audit Report', '{\"report_id\":4,\"report_key\":\"recruitment_summary\",\"report_label\":\"Recruitment Summary\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 04:55:49'),
(25, 0, '', 'Audit Report', '{\"report_id\":5,\"report_key\":\"employee_master_list\",\"report_label\":\"Employee Master List\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:34:17'),
(26, 0, '', 'Audit Report', '{\"report_id\":6,\"report_key\":\"policy_acknowledgement\",\"report_label\":\"Policy Acknowledgement\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:34:54'),
(27, 0, '', 'Audit Report', '{\"report_id\":7,\"report_key\":\"policy_acknowledgement\",\"report_label\":\"Policy Acknowledgement\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:35:13'),
(28, 0, '', 'Audit Report', '{\"report_id\":8,\"report_key\":\"sss_compliance_report\",\"report_label\":\"sss_compliance_report\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:40:37'),
(29, 0, '', 'Audit Report', '{\"schedule_id\":2,\"report_key\":\"missing_registrations\",\"frequency\":\"Monthly\",\"action\":\"schedule_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:40:44'),
(30, 0, '', 'Audit Report', '{\"schedule_id\":3,\"report_key\":\"document_expiration\",\"frequency\":\"Monthly\",\"action\":\"schedule_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:40:53'),
(31, 0, '', 'Audit Report', '{\"report_id\":11,\"report_key\":\"vacancy_reports\",\"report_label\":\"Vacancy Reports\",\"action\":\"send_report\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-04 05:41:53'),
(32, 0, '', 'Audit Report', '{\"report_id\":12,\"report_key\":\"employee_master_list\",\"report_label\":\"Employee Master List\",\"action\":\"send_report\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-06 12:21:58'),
(33, 0, '', 'Audit Report', '{\"report_id\":13,\"report_key\":\"employee_master_list\",\"report_label\":\"Employee Master List\",\"action\":\"send_report\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-06 12:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_audit_trail`
--

CREATE TABLE `lc_audit_trail` (
  `audit_id` int(11) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `action` enum('INSERT','UPDATE','DELETE','VIEW') DEFAULT 'VIEW',
  `user_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_bir_tax_table`
--

CREATE TABLE `lc_bir_tax_table` (
  `id` int(11) NOT NULL,
  `min_annual_income` decimal(12,2) NOT NULL,
  `max_annual_income` decimal(12,2) NOT NULL,
  `fixed_tax` decimal(12,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `excess_over` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `revenue_regulation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_bir_tax_table`
--

INSERT INTO `lc_bir_tax_table` (`id`, `min_annual_income`, `max_annual_income`, `fixed_tax`, `tax_rate`, `excess_over`, `effective_date`, `status`, `revenue_regulation`) VALUES
(1, 20833.00, 33332.00, 0.00, 15.00, 20833.00, '2025-01-01', 'Active', 'CREATE MORE Act / RR No. 11-2024'),
(2, 33333.00, 66666.00, 2500.00, 20.00, 33333.00, '2025-01-01', 'Active', 'CREATE MORE Act / RR No. 11-2024'),
(3, 66667.00, 166666.00, 10833.33, 25.00, 66667.00, '2025-01-01', 'Active', 'CREATE MORE Act / RR No. 11-2024'),
(4, 166667.00, 666666.00, 40833.33, 30.00, 166667.00, '2025-01-01', 'Active', 'CREATE MORE Act / RR No. 11-2024'),
(5, 666667.00, 99999999.99, 200833.33, 35.00, 666667.00, '2025-01-01', 'Active', 'CREATE MORE Act / RR No. 11-2024');

-- --------------------------------------------------------

--
-- Table structure for table `lc_calendar`
--

CREATE TABLE `lc_calendar` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('Compliance','Training','Audit','Legal Case','Government Contribution','Document Expiration','Meeting','Policy','Recruitment','Exit','Holiday','Other') NOT NULL DEFAULT 'Other',
  `date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') DEFAULT 'Upcoming',
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `color` varchar(20) DEFAULT '#0d6efd',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_calendar`
--

INSERT INTO `lc_calendar` (`id`, `title`, `description`, `event_type`, `date`, `location`, `status`, `priority`, `color`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SSS Contribution Deadline', 'Monthly SSS contribution submission.', 'Government Contribution', '2026-07-09', 'HR Office', 'Upcoming', 'High', '#dc3545', 1, '2026-07-16 23:39:20', '2026-07-17 01:24:57'),
(2, 'Workplace Safety Training', 'Mandatory annual safety training.', 'Training', '2026-08-19', 'Training Room', 'Upcoming', 'Medium', '#198754', 1, '2026-07-16 23:39:20', '2026-07-24 06:03:53'),
(3, 'Internal Compliance Audit', 'Quarterly HR compliance audit.', 'Audit', '2026-08-12', 'Conference Room', 'Upcoming', 'Critical', '#fd7e14', 1, '2026-07-16 23:39:20', '2026-07-17 00:32:38'),
(4, 'Employment Contract Renewal', 'Renew employment contract.', 'Compliance', '2026-08-15', 'HR Office', 'Upcoming', 'High', '#0d6efd', 1, '2026-07-16 23:39:20', '2026-07-16 23:39:20'),
(5, 'Faculty Meeting', 'Monthly faculty compliance meeting.', 'Meeting', '2026-08-18', 'Conference Hall', 'Upcoming', 'Medium', '#6f42c1', 1, '2026-07-16 23:39:20', '2026-07-17 00:16:27'),
(6, 'Professional License Expiration', 'Renew PRC License before expiration.', 'Document Expiration', '2026-09-01', 'Online', 'Upcoming', 'Critical', '#dc3545', 1, '2026-07-16 23:39:20', '2026-07-16 23:39:20'),
(7, 'Policy Orientation', 'Orientation on the revised Employee Handbook.', 'Policy', '2026-07-09', 'Auditorium', 'Upcoming', 'Medium', '#20c997', 1, '2026-07-16 23:39:20', '2026-07-17 01:25:03'),
(8, 'Employee Exit Interview', 'Conduct exit interview for resigned employee.', 'Exit', '2026-08-25', 'HR Office', 'Upcoming', 'High', '#6c757d', 1, '2026-07-16 23:39:20', '2026-07-16 23:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `lc_complaint_evidence`
--

CREATE TABLE `lc_complaint_evidence` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_complaint_evidence`
--

INSERT INTO `lc_complaint_evidence` (`id`, `complaint_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `description`, `created_at`) VALUES
(1, 8, 'document_15_20260803.pdf', 'uploads/complaint_evidence/1785736353_0_0af657.pdf', 'application/pdf', 54474, NULL, NULL, '2026-08-03 05:52:33');

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_checks`
--

CREATE TABLE `lc_compliance_checks` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `law_type` varchar(100) DEFAULT NULL,
  `status` enum('Compliant','Non-Compliant','Pending','Under Review') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `date_checked` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_items`
--

CREATE TABLE `lc_compliance_items` (
  `id` int(11) NOT NULL,
  `compliance_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `responsible_person_id` int(11) DEFAULT NULL,
  `frequency` enum('Daily','Weekly','Monthly','Quarterly','Annual','One-Time') DEFAULT 'Monthly',
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Compliant','Non-Compliant','Overdue') NOT NULL DEFAULT 'Pending',
  `risk_level` enum('Low','Medium','High') DEFAULT 'Low',
  `remarks` text DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_compliance_items`
--

INSERT INTO `lc_compliance_items` (`id`, `compliance_id`, `name`, `category`, `subcategory`, `description`, `department`, `responsible_person_id`, `frequency`, `due_date`, `status`, `risk_level`, `remarks`, `is_recurring`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'LAB-001', 'Occupational Safety Monthly Inspection', 'Safety', 'RA 11058', 'Monthly safety inspection of all work areas.', 'Facilities Management', 111, 'Monthly', '2026-08-15', 'Compliant', 'High', 'Completed on schedule.', 1, 66, '2026-07-01 00:00:00', '2026-07-15 00:00:00'),
(2, 'LAB-002', 'DOLE Semi-Annual Report', 'DOLE Standard', 'Reporting', 'Submit semi-annual report to DOLE regional office.', 'Human Resources', 66, '', '2026-08-30', 'Pending', 'Medium', NULL, 0, 66, '2026-07-01 00:00:00', '2026-07-01 00:00:00'),
(3, 'LAB-003', 'Employee Contract Renewal Review', 'Contract', 'Renewal', 'Review and process contract renewals for probationary employees.', 'Human Resources', 66, 'Monthly', '2026-08-05', 'In Progress', 'Medium', '3 contracts pending review.', 1, 66, '2026-07-01 00:00:00', '2026-07-10 00:00:00'),
(4, 'LAB-004', 'Government Contribution Remittance SSS', 'Benefits', 'SSS', 'Ensure timely SSS remittance.', 'Human Resources', 66, 'Monthly', '2026-08-10', 'Compliant', 'High', 'Remitted on time.', 1, 66, '2026-07-01 00:00:00', '2026-07-05 00:00:00'),
(5, 'LAB-005', 'Fire Drill Conduct', 'Safety', 'Emergency', 'Conduct quarterly fire drill.', 'Facilities Management', 111, 'Quarterly', '2026-09-30', 'Pending', 'Low', NULL, 1, 66, '2026-07-01 00:00:00', '2026-07-01 00:00:00'),
(6, 'LAB-006', 'Working Hours Policy Compliance', 'Working Hours', 'Policy', 'Verify adherence to 8-hour workday and overtime rules.', 'Human Resources', 66, 'Monthly', '2026-08-20', 'Compliant', 'Medium', 'No anomalies found.', 1, 66, '2026-07-01 00:00:00', '2026-07-20 00:00:00'),
(7, 'LAB-007', 'Data Privacy Compliance Audit', 'Other', 'Data Privacy', 'Annual audit of data privacy measures.', 'Information Technology', 71, 'Annual', '2026-12-15', 'Pending', 'High', NULL, 0, 66, '2026-07-01 00:00:00', '2026-07-01 00:00:00'),
(8, 'LAB-008', 'Pag-IBIG Remittance Compliance', 'Benefits', 'Pag-IBIG', 'Ensure timely Pag-IBIG remittance.', 'Human Resources', 66, 'Monthly', '2026-08-12', 'Compliant', 'High', 'Remitted.', 1, 66, '2026-07-01 00:00:00', '2026-07-08 00:00:00'),
(9, 'LAB-009', 'Exit Interview Compliance', 'Contract', 'Separation', 'Ensure exit interviews are conducted for all resignations.', 'Human Resources', 66, 'Monthly', '2026-08-25', 'Non-Compliant', 'Medium', '2 pending exit interviews.', 1, 66, '2026-07-01 00:00:00', '2026-07-01 00:00:00'),
(10, 'LAB-010', 'Annual Physical Examination', 'Safety', 'Health', 'Conduct annual physical exam for all employees.', 'Clinic', 87, 'Annual', '2026-10-15', 'Overdue', 'Low', 'Schedule pending.', 0, 66, '2026-07-01 00:00:00', '2026-07-01 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_records`
--

CREATE TABLE `lc_compliance_records` (
  `record_id` int(11) NOT NULL,
  `compliance_type` varchar(255) DEFAULT NULL,
  `status` enum('Compliant','Non-Compliant','Pending','Under Review') DEFAULT 'Pending',
  `score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_compliance_records`
--

INSERT INTO `lc_compliance_records` (`record_id`, `compliance_type`, `status`, `score`, `created_at`) VALUES
(1, 'Employment Contract', 'Compliant', 95, '2026-06-30 16:00:00'),
(2, 'Government Contribution', 'Compliant', 88, '2026-07-01 16:00:00'),
(3, 'Employee Document', 'Pending', 72, '2026-07-02 16:00:00'),
(4, 'Leave Compliance', 'Compliant', 91, '2026-07-03 16:00:00'),
(5, 'Policy Acknowledgement', 'Non-Compliant', 65, '2026-07-04 16:00:00'),
(6, 'Training & Certification', 'Under Review', 78, '2026-07-05 16:00:00'),
(7, 'Employment Contract', 'Compliant', 97, '2026-07-06 16:00:00'),
(8, 'Government Contribution', 'Pending', 82, '2026-07-07 16:00:00'),
(9, 'Employee Document', 'Compliant', 94, '2026-07-08 16:00:00'),
(10, 'Leave Compliance', 'Non-Compliant', 60, '2026-07-09 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_rules`
--

CREATE TABLE `lc_compliance_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `law_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_summary`
--

CREATE TABLE `lc_compliance_summary` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `employment_score` decimal(5,2) DEFAULT 0.00,
  `leave_score` decimal(5,2) DEFAULT 0.00,
  `benefits_score` decimal(5,2) DEFAULT 0.00,
  `working_conditions_score` decimal(5,2) DEFAULT 0.00,
  `workplace_protection_score` decimal(5,2) DEFAULT 0.00,
  `data_privacy_score` decimal(5,2) DEFAULT 0.00,
  `overall_score` decimal(5,2) DEFAULT 0.00,
  `status` enum('compliant','at_risk','non_compliant') DEFAULT 'compliant',
  `critical_issues` int(11) DEFAULT 0,
  `high_risks` int(11) DEFAULT 0,
  `medium_risks` int(11) DEFAULT 0,
  `low_risks` int(11) DEFAULT 0,
  `last_checked` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_tasks`
--

CREATE TABLE `lc_compliance_tasks` (
  `id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Completed','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
  `deadline` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_compliance_violations`
--

CREATE TABLE `lc_compliance_violations` (
  `violation_id` int(11) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `violation_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `detected_date` date NOT NULL,
  `status` enum('Logged','Under Investigation','Resolved','Closed','Dismissed') NOT NULL DEFAULT 'Logged',
  `corrective_action` text DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `assigned_investigator` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_compliance_violations`
--

INSERT INTO `lc_compliance_violations` (`violation_id`, `reference_no`, `employee_id`, `violation_type`, `title`, `description`, `severity`, `detected_date`, `status`, `corrective_action`, `resolution_notes`, `resolved_date`, `assigned_investigator`, `created_at`, `updated_at`) VALUES
(1, 'LV-20260731-001', 9, 'Labor Code', 'Unauthorized overtime without compensation', 'Employee was required to work beyond 8 hours without proper overtime pay.', 'High', '2026-07-15', 'Logged', 'Review timekeeping records and process back pay.', NULL, NULL, 66, '2026-07-15 01:00:00', '2026-07-15 01:00:00'),
(2, 'LV-20260731-002', 12, 'Benefits', 'Delayed SSS remittance', 'SSS contribution for June 2026 was remitted 15 days late.', 'Medium', '2026-07-20', 'Under Investigation', 'Coordinate with payroll to expedite remittance and update cut-off schedule.', NULL, NULL, 66, '2026-07-20 02:30:00', '2026-07-25 06:00:00'),
(3, 'LV-20260731-003', 29, 'Working Hours', 'Failure to grant rest day premium', 'Employee worked on scheduled rest day without premium pay.', 'Critical', '2026-07-10', 'Logged', 'Adjust payroll and issue retroactive pay.', NULL, NULL, NULL, '2026-07-10 00:15:00', '2026-07-31 15:03:59'),
(4, 'LV-20260731-004', 61, 'DOLE Standard', 'Missing wilful disobedience report', 'No incident report filed for wilful disobedience case.', 'Low', '2026-07-25', 'Resolved', 'File required report with DOLE regional office.', 'Report submitted and acknowledged.', '2026-07-28', 66, '2026-07-25 03:20:00', '2026-07-28 01:10:00'),
(5, 'LV-20260731-005', 78, 'Safety', 'Expired fire extinguisher inspection', 'Fire extinguishers in Building C have not been inspected since 2025.', 'Medium', '2026-07-22', 'Closed', 'Schedule inspection with accredited provider.', 'Inspection completed.', '2026-07-29', 66, '2026-07-21 23:45:00', '2026-07-29 08:00:00'),
(6, 'LV-20260731-006', 66, 'Contract', 'Expired contract not renewed', 'Employment contract for probationary employee expired without renewal.', 'High', '2026-07-18', 'Under Investigation', 'Initiate renewal process or separate employee.', NULL, NULL, 66, '2026-07-18 05:00:00', '2026-07-18 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_contracts`
--

CREATE TABLE `lc_contracts` (
  `contract_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `contract_type` enum('Regular','Probationary','Fixed-Term','Project','Seasonal','Casual','Part-Time') DEFAULT 'Regular',
  `sub_type` varchar(100) DEFAULT NULL,
  `governing_law` varchar(255) DEFAULT 'Philippine Labor Code (PD 442)',
  `jurisdiction` varchar(100) DEFAULT 'Philippines',
  `requires_dual_sig` tinyint(1) NOT NULL DEFAULT 1,
  `digital_signature_status` enum('none','partial','complete') NOT NULL DEFAULT 'none',
  `renewal_trigger_date` date DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `status` enum('Draft','Active','Expired','Terminated') NOT NULL DEFAULT 'Draft',
  `monthly_salary` decimal(12,2) DEFAULT 0.00,
  `working_hours_per_week` int(11) DEFAULT 40,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_role` varchar(50) DEFAULT 'hr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `flagged` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Manual compliance-gap flag',
  `flagged_at` timestamp NULL DEFAULT NULL,
  `flagged_by` int(11) DEFAULT NULL,
  `flag_reason` varchar(255) DEFAULT NULL,
  `gap_response` text DEFAULT NULL COMMENT 'Remediation documenting how the gap was addressed',
  `gap_resolved_at` timestamp NULL DEFAULT NULL,
  `gap_resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_contracts`
--

INSERT INTO `lc_contracts` (`contract_id`, `category_id`, `employee_id`, `contract_number`, `contract_type`, `sub_type`, `governing_law`, `jurisdiction`, `requires_dual_sig`, `digital_signature_status`, `renewal_trigger_date`, `start_date`, `end_date`, `renewal_date`, `status`, `monthly_salary`, `working_hours_per_week`, `file_path`, `file_name`, `notes`, `created_by`, `created_by_role`, `created_at`, `updated_at`, `flagged`, `flagged_at`, `flagged_by`, `flag_reason`, `gap_response`, `gap_resolved_at`, `gap_resolved_by`) VALUES
(1, NULL, 6, 'CTR-2026-0001', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2023-08-01', '2026-02-11', NULL, 'Expired', 12000.00, 40, 'uploads/contracts/contract_1.pdf', 'contract_1.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 18:04:11', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, 9, 'CTR-2026-0002', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2026-08-01', NULL, 'Active', 80000.00, 40, 'uploads/contracts/contract_2.pdf', 'contract_2.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, 10, 'CTR-2026-0003', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 75000.00, 40, 'uploads/contracts/contract_3.pdf', 'contract_3.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 11, 'CTR-2026-0004', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 12000.00, 40, 'uploads/contracts/contract_4.pdf', 'contract_4.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 18:04:16', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, 12, 'CTR-2026-0005', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2024-04-08', '2026-07-15', NULL, 'Expired', 65000.00, 40, 'uploads/contracts/contract_5.pdf', 'contract_5.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 16:55:09', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, 13, 'CTR-2026-0006', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 65000.00, 40, 'uploads/contracts/contract_6.pdf', 'contract_6.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, 14, 'CTR-2026-0007', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2024-05-06', '2026-08-02', NULL, 'Expired', 60000.00, 40, 'uploads/contracts/contract_7.pdf', 'contract_7.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 16:54:58', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(8, NULL, 15, 'CTR-2026-0008', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 55000.00, 40, 'uploads/contracts/contract_8.pdf', 'contract_8.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(9, NULL, 16, 'CTR-2026-0009', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 50000.00, 40, 'uploads/contracts/contract_9.pdf', 'contract_9.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(10, NULL, 17, 'CTR-2026-0010', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 45000.00, 40, 'uploads/contracts/contract_10.pdf', 'contract_10.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(11, NULL, 18, 'CTR-2026-0011', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 40000.00, 40, 'uploads/contracts/contract_11.pdf', 'contract_11.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(12, NULL, 19, 'CTR-2026-0012', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 35000.00, 40, 'uploads/contracts/contract_12.pdf', 'contract_12.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(13, NULL, 20, 'CTR-2026-0013', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 30000.00, 40, 'uploads/contracts/contract_13.pdf', 'contract_13.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 21, 'CTR-2026-0014', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_14.pdf', 'contract_14.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(15, NULL, 22, 'CTR-2026-0015', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_15.pdf', 'contract_15.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(16, NULL, 23, 'CTR-2026-0016', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_16.pdf', 'contract_16.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(17, NULL, 24, 'CTR-2026-0017', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_17.pdf', 'contract_17.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(18, NULL, 25, 'CTR-2026-0018', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_18.pdf', 'contract_18.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(19, NULL, 26, 'CTR-2026-0019', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_19.pdf', 'contract_19.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(20, NULL, 27, 'CTR-2026-0020', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_20.pdf', 'contract_20.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(21, NULL, 28, 'CTR-2026-0021', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_21.pdf', 'contract_21.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(22, NULL, 29, 'CTR-2026-0022', '', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_22.pdf', 'contract_22.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(23, NULL, 30, 'CTR-2026-0023', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_23.pdf', 'contract_23.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(24, NULL, 31, 'CTR-2026-0024', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_24.pdf', 'contract_24.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(25, NULL, 32, 'CTR-2026-0025', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_25.pdf', 'contract_25.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(26, NULL, 33, 'CTR-2026-0026', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_26.pdf', 'contract_26.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(27, NULL, 34, 'CTR-2026-0027', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-02-03', '2026-08-01', NULL, 'Expired', 15000.00, 40, 'uploads/contracts/contract_27.pdf', 'contract_27.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 16:54:27', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(28, NULL, 35, 'CTR-2026-0028', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_28.pdf', 'contract_28.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(29, NULL, 36, 'CTR-2026-0029', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_29.pdf', 'contract_29.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(30, NULL, 37, 'CTR-2026-0030', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_30.pdf', 'contract_30.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(31, NULL, 38, 'CTR-2026-0031', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_31.pdf', 'contract_31.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(32, NULL, 39, 'CTR-2026-0032', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_32.pdf', 'contract_32.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(33, NULL, 40, 'CTR-2026-0033', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_33.pdf', 'contract_33.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(34, NULL, 41, 'CTR-2026-0034', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_34.pdf', 'contract_34.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(35, NULL, 42, 'CTR-2026-0035', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_35.pdf', 'contract_35.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(36, NULL, 43, 'CTR-2026-0036', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_36.pdf', 'contract_36.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(37, NULL, 44, 'CTR-2026-0037', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_37.pdf', 'contract_37.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(38, NULL, 45, 'CTR-2026-0038', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_38.pdf', 'contract_38.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(39, NULL, 46, 'CTR-2026-0039', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_39.pdf', 'contract_39.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(40, NULL, 47, 'CTR-2026-0040', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_40.pdf', 'contract_40.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(41, NULL, 48, 'CTR-2026-0041', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_41.pdf', 'contract_41.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(42, NULL, 49, 'CTR-2026-0042', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_42.pdf', 'contract_42.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(43, NULL, 50, 'CTR-2026-0043', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_43.pdf', 'contract_43.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(44, NULL, 51, 'CTR-2026-0044', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_44.pdf', 'contract_44.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(45, NULL, 52, 'CTR-2026-0045', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_45.pdf', 'contract_45.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(46, NULL, 53, 'CTR-2026-0046', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_46.pdf', 'contract_46.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(47, NULL, 54, 'CTR-2026-0047', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_47.pdf', 'contract_47.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(48, NULL, 55, 'CTR-2026-0048', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_48.pdf', 'contract_48.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(49, NULL, 56, 'CTR-2026-0049', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_49.pdf', 'contract_49.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(50, NULL, 57, 'CTR-2026-0050', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_50.pdf', 'contract_50.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(51, NULL, 58, 'CTR-2026-0051', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_51.pdf', 'contract_51.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(52, NULL, 59, 'CTR-2026-0052', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_52.pdf', 'contract_52.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(53, NULL, 60, 'CTR-2026-0053', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_53.pdf', 'contract_53.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(54, NULL, 61, 'CTR-2026-0054', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_54.pdf', 'contract_54.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(55, NULL, 62, 'CTR-2026-0055', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_55.pdf', 'contract_55.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(56, NULL, 63, 'CTR-2026-0056', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_56.pdf', 'contract_56.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(57, NULL, 64, 'CTR-2026-0057', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_57.pdf', 'contract_57.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(58, NULL, 65, 'CTR-2026-0058', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_58.pdf', 'contract_58.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(59, NULL, 66, 'CTR-2026-0059', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_59.pdf', 'contract_59.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(60, NULL, 67, 'CTR-2026-0060', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_60.pdf', 'contract_60.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(61, NULL, 68, 'CTR-2026-0061', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_61.pdf', 'contract_61.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(62, NULL, 69, 'CTR-2026-0062', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_62.pdf', 'contract_62.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(63, NULL, 70, 'CTR-2026-0063', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_63.pdf', 'contract_63.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(64, NULL, 71, 'CTR-2026-0064', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_64.pdf', 'contract_64.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(65, NULL, 72, 'CTR-2026-0065', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_65.pdf', 'contract_65.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(66, NULL, 73, 'CTR-2026-0066', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_66.pdf', 'contract_66.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(67, NULL, 74, 'CTR-2026-0067', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_67.pdf', 'contract_67.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(68, NULL, 75, 'CTR-2026-0068', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_68.pdf', 'contract_68.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(69, NULL, 76, 'CTR-2026-0069', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_69.pdf', 'contract_69.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(70, NULL, 77, 'CTR-2026-0070', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_70.pdf', 'contract_70.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(71, NULL, 78, 'CTR-2026-0071', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_71.pdf', 'contract_71.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(72, NULL, 79, 'CTR-2026-0072', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_72.pdf', 'contract_72.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(73, NULL, 80, 'CTR-2026-0073', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_73.pdf', 'contract_73.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(75, NULL, 82, 'CTR-2026-0075', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_75.pdf', 'contract_75.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(76, NULL, 83, 'CTR-2026-0076', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_76.pdf', 'contract_76.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(77, NULL, 84, 'CTR-2026-0077', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_77.pdf', 'contract_77.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(78, NULL, 85, 'CTR-2026-0078', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_78.pdf', 'contract_78.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(79, NULL, 86, 'CTR-2026-0079', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_79.pdf', 'contract_79.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(80, NULL, 87, 'CTR-2026-0080', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_80.pdf', 'contract_80.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(81, NULL, 88, 'CTR-2026-0081', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_81.pdf', 'contract_81.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(82, NULL, 89, 'CTR-2026-0082', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_82.pdf', 'contract_82.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(83, NULL, 90, 'CTR-2026-0083', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_83.pdf', 'contract_83.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(84, NULL, 91, 'CTR-2026-0084', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_84.pdf', 'contract_84.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(85, NULL, 92, 'CTR-2026-0085', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_85.pdf', 'contract_85.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(86, NULL, 93, 'CTR-2026-0086', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_86.pdf', 'contract_86.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(87, NULL, 94, 'CTR-2026-0087', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_87.pdf', 'contract_87.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(88, NULL, 95, 'CTR-2026-0088', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_88.pdf', 'contract_88.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(89, NULL, 96, 'CTR-2026-0089', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_89.pdf', 'contract_89.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(90, NULL, 97, 'CTR-2026-0090', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_90.pdf', 'contract_90.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(91, NULL, 98, 'CTR-2026-0091', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_91.pdf', 'contract_91.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(92, NULL, 99, 'CTR-2026-0092', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_92.pdf', 'contract_92.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(93, NULL, 100, 'CTR-2026-0093', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_93.pdf', 'contract_93.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(94, NULL, 101, 'CTR-2026-0094', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_94.pdf', 'contract_94.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(95, NULL, 102, 'CTR-2026-0095', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_95.pdf', 'contract_95.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(96, NULL, 103, 'CTR-2026-0096', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_96.pdf', 'contract_96.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(97, NULL, 104, 'CTR-2026-0097', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_97.pdf', 'contract_97.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(98, NULL, 105, 'CTR-2026-0098', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_98.pdf', 'contract_98.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(99, NULL, 106, 'CTR-2026-0099', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_99.pdf', 'contract_99.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(100, NULL, 107, 'CTR-2026-0100', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_100.pdf', 'contract_100.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(101, NULL, 108, 'CTR-2026-0101', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_101.pdf', 'contract_101.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(102, NULL, 109, 'CTR-2026-0102', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_102.pdf', 'contract_102.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(103, NULL, 110, 'CTR-2026-0103', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_103.pdf', 'contract_103.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(104, NULL, 111, 'CTR-2026-0104', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_104.pdf', 'contract_104.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(105, NULL, 112, 'CTR-2026-0105', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_105.pdf', 'contract_105.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(106, NULL, 113, 'CTR-2026-0106', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_106.pdf', 'contract_106.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(107, NULL, 114, 'CTR-2026-0107', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_107.pdf', 'contract_107.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(108, NULL, 115, 'CTR-2026-0108', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_108.pdf', 'contract_108.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(109, NULL, 116, 'CTR-2026-0109', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_109.pdf', 'contract_109.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(110, NULL, 117, 'CTR-2026-0110', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_110.pdf', 'contract_110.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(111, NULL, 118, 'CTR-2026-0111', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_111.pdf', 'contract_111.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(112, NULL, 119, 'CTR-2026-0112', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_112.pdf', 'contract_112.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(113, NULL, 120, 'CTR-2026-0113', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_113.pdf', 'contract_113.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(114, NULL, 121, 'CTR-2026-0114', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_114.pdf', 'contract_114.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(115, NULL, 122, 'CTR-2026-0115', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_115.pdf', 'contract_115.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(116, NULL, 123, 'CTR-2026-0116', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_116.pdf', 'contract_116.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(117, NULL, 124, 'CTR-2026-0117', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_117.pdf', 'contract_117.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(118, NULL, 125, 'CTR-2026-0118', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_118.pdf', 'contract_118.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(119, NULL, 126, 'CTR-2026-0119', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_119.pdf', 'contract_119.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(120, NULL, 127, 'CTR-2026-0120', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_120.pdf', 'contract_120.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(121, NULL, 128, 'CTR-2026-0121', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_121.pdf', 'contract_121.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(122, NULL, 129, 'CTR-2026-0122', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_122.pdf', 'contract_122.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(123, NULL, 130, 'CTR-2026-0123', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_123.pdf', 'contract_123.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(124, NULL, 131, 'CTR-2026-0124', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_124.pdf', 'contract_124.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(125, NULL, 148, 'CTR-2026-0125', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_125.pdf', 'contract_125.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(126, NULL, 149, 'CTR-2026-0126', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_126.pdf', 'contract_126.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(127, NULL, 150, 'CTR-2026-0127', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_127.pdf', 'contract_127.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(128, NULL, 151, 'CTR-2026-0128', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_128.pdf', 'contract_128.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(129, NULL, 152, 'CTR-2026-0129', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_129.pdf', 'contract_129.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(130, NULL, 153, 'CTR-2026-0130', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_130.pdf', 'contract_130.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(131, NULL, 154, 'CTR-2026-0131', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_131.pdf', 'contract_131.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(132, NULL, 155, 'CTR-2026-0132', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_132.pdf', 'contract_132.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(133, NULL, 156, 'CTR-2026-0133', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_133.pdf', 'contract_133.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:02', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(134, NULL, 157, 'CTR-2026-0134', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2029-08-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_134.pdf', 'contract_134.pdf', NULL, 0, 'hr', '2026-08-01 13:59:59', '2026-08-02 10:01:03', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(135, 1, 6, 'CTR-2026-0135', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-01', '2027-02-01', NULL, 'Active', 15000.00, 40, 'uploads/contracts/contract_135.pdf', 'contract_135.pdf', NULL, 0, 'hr', '2026-08-01 14:12:26', '2026-08-02 10:01:03', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(136, NULL, 158, 'CTR-2026-0136', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2019-12-02', '2020-12-02', NULL, 'Expired', 30000.00, 40, 'uploads/contracts/contract_136.pdf', 'contract_136.pdf', NULL, 3, 'hr', '2026-08-02 04:53:29', '2026-08-02 16:35:07', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(137, NULL, 317, 'CTR-2026-0137', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2019-12-02', '2020-12-02', NULL, 'Active', 30000.00, 40, 'uploads/contracts/contract_137.pdf', 'contract_137.pdf', NULL, NULL, 'hr', '2026-08-02 05:22:40', '2026-08-02 10:01:03', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(140, NULL, 318, 'CTR-2026-0139', 'Probationary', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2023-02-01', '2024-02-01', NULL, 'Active', 30000.00, 40, 'uploads/contracts/contract_140.pdf', 'contract_140.pdf', NULL, 3, 'hr', '2026-08-02 08:04:32', '2026-08-02 16:31:48', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(141, NULL, 319, 'CTR-2026-0141', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2021-08-15', '2022-08-15', NULL, 'Terminated', 30000.00, 40, 'uploads/contracts/contract_141.pdf', 'contract_141.pdf', NULL, 3, 'hr', '2026-08-02 08:04:56', '2026-08-02 16:35:14', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(142, NULL, 320, 'CTR-2026-0142', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2020-07-20', '2021-07-20', NULL, 'Terminated', 11000.00, 40, 'uploads/contracts/contract_142.pdf', 'contract_142.pdf', NULL, 3, 'hr', '2026-08-02 08:05:57', '2026-08-02 16:35:22', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(143, NULL, 158, 'CTR-2026-0143', 'Regular', NULL, 'Philippine Labor Code (PD 442)', 'Philippines', 1, 'none', NULL, '2026-08-04', '2027-08-04', NULL, 'Draft', 0.00, 40, 'http://127.0.0.1/capstone_hr_management_system/legal_compliance/pages/labor_law_compliance/uploads/contracts/contract_CTR20260143.pdf', 'contract_CTR20260143.pdf', NULL, NULL, 'hr', '2026-08-04 07:32:14', '2026-08-04 07:32:14', 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lc_corrective_actions`
--

CREATE TABLE `lc_corrective_actions` (
  `id` int(11) NOT NULL,
  `case_reference` varchar(50) NOT NULL,
  `violation_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` enum('Sent to Payroll','Under Review','Corrected','Closed','Dismissed') NOT NULL DEFAULT 'Sent to Payroll',
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `due_date` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `action_summary` text DEFAULT NULL,
  `issue_details` text DEFAULT NULL,
  `legal_basis` text DEFAULT NULL,
  `payroll_status` enum('Confirmed','Denied','Partial') DEFAULT NULL,
  `payroll_response` text DEFAULT NULL,
  `payroll_updated_at` datetime DEFAULT NULL,
  `payroll_updated_by` int(11) DEFAULT NULL,
  `compliance_verification_status` enum('Verified','Rejected') DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_corrective_actions`
--

INSERT INTO `lc_corrective_actions` (`id`, `case_reference`, `violation_id`, `employee_id`, `status`, `priority`, `due_date`, `department_id`, `assigned_to`, `action_summary`, `issue_details`, `legal_basis`, `payroll_status`, `payroll_response`, `payroll_updated_at`, `payroll_updated_by`, `compliance_verification_status`, `verification_notes`, `closed_at`, `closed_by`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CA-202607310003-0001', 3, 29, 'Corrected', 'Critical', '2026-07-24', 3, 66, 'Process retroactive overtime pay', 'Failure to grant rest day premium.', 'Labor Code Art. 93', 'Confirmed', 'Payroll processed retroactive pay.', '2026-07-23 10:00:00', 66, 'Verified', 'Verified and closed.', '2026-07-30 16:45:00', 66, 66, '2026-07-12 00:30:00', '2026-07-30 08:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_disciplinary_actions`
--

CREATE TABLE `lc_disciplinary_actions` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `issued_by` int(11) DEFAULT NULL,
  `status` enum('issued','active','served','completed','cancelled') DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_disciplinary_actions`
--

INSERT INTO `lc_disciplinary_actions` (`id`, `incident_id`, `employee_id`, `action_type`, `reason`, `start_date`, `end_date`, `duration_days`, `is_active`, `issued_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 23, 9, 'Written Warning', 'Sleeping during duty hours', '2026-07-18', '2026-07-18', 1, 0, 6, 'completed', '2026-07-17 16:00:00', '2026-08-03 05:40:47'),
(2, 24, 28, 'Suspension', 'Theft of company property', '2026-07-20', '2026-08-03', 14, 0, 5, 'completed', '2026-07-18 16:00:00', '2026-08-03 06:03:28'),
(3, 25, 31, 'Final Written Warning', 'Insubordination', '2026-07-30', '2026-07-30', 1, 1, 5, 'active', '2026-07-29 16:00:00', '2026-07-31 16:15:00'),
(4, 26, 15, 'Termination', 'Conflict of interest', '2026-08-01', '2026-08-01', 1, 0, 6, 'completed', '2026-07-31 16:00:00', '2026-08-03 05:42:25'),
(5, 27, 35, 'Written Warning', 'Attendance violation', '2026-07-10', '2026-07-10', 1, 0, 5, 'completed', '2026-07-09 16:00:00', '2026-07-15 01:00:00'),
(6, 20, 12, 'Suspension', 'Harassment', '2026-07-25', '2026-08-03', 10, 0, 158, 'completed', '2026-07-24 16:00:00', '2026-08-03 06:03:28'),
(7, 28, 41, 'Performance Improvement Plan', 'Performance issue', '2026-07-05', '2026-10-05', 90, 1, 5, 'active', '2026-07-04 16:00:00', '2026-07-19 23:45:00'),
(8, 21, 18, 'Investigation', 'Discrimination', '2026-07-20', NULL, NULL, 1, 5, 'issued', '2026-07-19 16:00:00', '2026-07-30 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_document_requests`
--

CREATE TABLE `lc_document_requests` (
  `request_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `request_status` varchar(100) DEFAULT 'draft',
  `archived` tinyint(1) DEFAULT 0,
  `signature_status` varchar(50) DEFAULT 'none',
  `requires_signature` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `required_by` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'Medium',
  `notes` varchar(50) DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_document_requests`
--

INSERT INTO `lc_document_requests` (`request_id`, `employee_id`, `document_type`, `request_status`, `archived`, `signature_status`, `requires_signature`, `created_at`, `required_by`, `assigned_to`, `priority`, `notes`) VALUES
(2, 102, 'Certificate of Employment (COE)', 'pending', 0, 'unsigned', 1, '2026-08-02 02:00:00', '2026-08-05', 3, 'Medium', 'COE for visa and bank loan application purposes'),
(3, 103, 'Quitclaim and Release', 'pending', 0, 'pending_signature', 1, '2026-08-02 03:00:00', '2026-08-06', 4, 'Medium', 'Final settlement release for resigned employee'),
(4, 104, 'Exit Acknowledgement', 'pending', 0, 'pending_signature', 1, '2026-08-02 04:00:00', '2026-08-06', 4, 'Medium', 'Acknowledgement of final pay, benefits, and proper'),
(5, 105, 'Non-Disclosure Agreement (NDA)', 'pending', 0, 'signed', 1, '2026-08-02 05:00:00', '2026-08-09', 5, 'Medium', 'Confidentiality agreement for access to sensitive '),
(6, 106, 'Notice to Explain (NTE)', 'pending', 0, 'unsigned', 1, '2026-08-02 06:00:00', '2026-08-05', 2, 'Medium', 'Formal notice for unexplained absence on 2026-07-2'),
(7, 107, 'Written Warning', 'pending', 0, 'unsigned', 1, '2026-08-02 07:00:00', '2026-08-06', 3, 'Medium', 'Warning for repeated violation of company attendan'),
(8, 108, 'Exit Clearance', 'pending', 0, 'pending_signature', 1, '2026-08-02 08:00:00', '2026-08-07', 4, 'Medium', 'Clearance checklist and sign-offs for employee tra'),
(9, 109, 'Return-to-Work Agreement', 'pending', 0, 'unsigned', 1, '2026-08-02 09:00:00', '2026-08-09', 3, 'Medium', 'Agreement for employee returning from medical leav'),
(10, 110, 'Leave Agreement', 'pending', 0, 'unsigned', 1, '2026-08-02 10:00:00', '2026-08-09', 3, 'Medium', 'Approved maternity leave agreement with return-to-'),
(22, 111, 'Training Bond', 'pending', 0, 'none', 1, '2026-08-02 11:00:00', '2026-08-03', 6, 'Medium', 'Training bond for new technical staff - CRITICAL'),
(23, 112, 'Study Leave Agreement', 'pending', 0, 'none', 1, '2026-08-02 12:00:00', '2026-08-20', 7, 'Medium', 'Study leave agreement for part-time Masters progra'),
(24, 9, 'Certificate of Employment (COE)', 'completed', 0, 'none', 1, '2026-07-31 16:00:00', '2026-08-15', NULL, 'Medium', 'COE generated for Maria Santos.'),
(25, 17, 'Certificate of Employment (COE)', 'completed', 0, 'none', 1, '2026-07-31 16:00:00', '2026-08-15', NULL, 'Medium', 'COE generated for Daniel Cruz.'),
(26, 20, 'Certificate of Employment (COE)', 'pending', 0, 'none', 1, '2026-07-31 16:00:00', '2026-08-15', NULL, 'High', 'COE request for Jennifer Bautista pending verifica'),
(27, 11, 'Quitclaim and Release', 'Pending', 0, 'pending_signature', 1, '2026-07-31 16:00:00', '2026-08-12', NULL, 'High', 'Quitclaim for Ana Garcia pending employee signatur'),
(28, 12, 'Quitclaim and Release', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-05', NULL, 'Medium', 'Quitclaim executed by Carlos Mendoza upon separati'),
(29, 15, 'Quitclaim and Release', 'Pending', 0, 'unsigned', 1, '2026-08-01 16:00:00', '2026-08-10', NULL, 'High', 'Quitclaim for Pedro Lim awaiting signing.'),
(30, 21, 'Quitclaim and Release', 'Completed', 0, 'signed', 1, '2026-07-24 16:00:00', '2026-08-01', NULL, 'Low', 'Quitclaim and final release for Richard Ong.'),
(31, 28, 'Quitclaim and Release', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-11', NULL, 'Medium', 'Quitclaim for Diana Uy pending notarization.'),
(32, 35, 'Quitclaim and Release', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'Quitclaim executed by Michael Tiu upon resignation'),
(33, 11, 'Exit Acknowledgement', 'Pending', 0, 'pending_signature', 1, '2026-07-31 16:00:00', '2026-08-10', NULL, 'High', 'Exit acknowledgement for Ana Garcia pending final '),
(34, 12, 'Exit Acknowledgement', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-05', NULL, 'Medium', 'Exit acknowledgement executed by Carlos Mendoza up'),
(35, 15, 'Exit Acknowledgement', 'Pending', 0, 'unsigned', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Exit acknowledgement for Pedro Lim awaiting employ'),
(36, 21, 'Exit Acknowledgement', 'Completed', 0, 'signed', 1, '2026-07-24 16:00:00', '2026-08-01', NULL, 'Low', 'Exit acknowledgement and property return signed by'),
(37, 28, 'Exit Acknowledgement', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-11', NULL, 'High', 'Exit acknowledgement for Diana Uy pending clearanc'),
(38, 35, 'Exit Acknowledgement', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'Exit acknowledgement executed by Michael Tiu upon '),
(39, 11, 'Leave Agreement', 'Pending', 0, 'pending_signature', 1, '2026-07-31 16:00:00', '2026-08-10', NULL, 'High', 'Leave agreement for Ana Garcia pending department '),
(40, 12, 'Leave Agreement', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-05', NULL, 'Medium', 'Leave agreement executed by Carlos Mendoza for vac'),
(41, 15, 'Leave Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Leave agreement for Pedro Lim awaiting employee si'),
(42, 21, 'Leave Agreement', 'Completed', 0, 'signed', 1, '2026-07-24 16:00:00', '2026-08-01', NULL, 'Low', 'Leave agreement executed by Richard Ong for sick l'),
(43, 28, 'Leave Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-11', NULL, 'High', 'Leave agreement for Diana Uy pending medical certi'),
(44, 35, 'Leave Agreement', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'Leave agreement executed by Michael Tiu for patern'),
(45, 27, 'Return-to-Work Agreement', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Return-to-work agreement for Brian Co pending medi'),
(46, 29, 'Return-to-Work Agreement', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Return-to-work agreement for Eric Tiu awaiting emp'),
(47, 34, 'Return-to-Work Agreement', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Return-to-work agreement executed by Lorraine Chan'),
(48, 46, 'Return-to-Work Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Return-to-work agreement for Xavier Dy pending doc'),
(49, 69, 'Return-to-Work Agreement', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Return-to-work agreement executed by Victor Dy aft'),
(50, 78, 'Return-to-Work Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Return-to-work agreement for Federico Co pending H'),
(51, 27, 'Non-Disclosure Agreement (NDA)', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'NDA for Brian Co pending HR review before signing.'),
(52, 29, 'Non-Disclosure Agreement (NDA)', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'NDA for Eric Tiu awaiting employee signature.'),
(53, 34, 'Non-Disclosure Agreement (NDA)', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'NDA executed by Lorraine Chan upon onboarding.'),
(54, 46, 'Non-Disclosure Agreement (NDA)', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'NDA for Xavier Dy pending legal compliance verific'),
(55, 69, 'Non-Disclosure Agreement (NDA)', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'NDA executed by Victor Dy for project confidential'),
(56, 78, 'Non-Disclosure Agreement (NDA)', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'NDA for Federico Co pending department head approv'),
(57, 27, 'Training Bond', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Training bond for Brian Co pending HR review.'),
(58, 29, 'Training Bond', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Training bond for Eric Tiu awaiting employee signa'),
(59, 34, 'Training Bond', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Training bond executed by Lorraine Chan upon accep'),
(60, 46, 'Training Bond', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Training bond for Xavier Dy pending department hea'),
(61, 69, 'Training Bond', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Training bond executed by Victor Dy for leadership'),
(62, 78, 'Training Bond', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Training bond for Federico Co pending notarization'),
(63, 27, 'Non-Compete Agreement', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Non-compete agreement for Brian Co pending legal r'),
(64, 29, 'Non-Compete Agreement', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Non-compete agreement for Eric Tiu awaiting employ'),
(65, 34, 'Non-Compete Agreement', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Non-compete agreement executed by Lorraine Chan up'),
(66, 46, 'Non-Compete Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Non-compete agreement for Xavier Dy pending depart'),
(67, 69, 'Non-Compete Agreement', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Non-compete agreement executed by Victor Dy for ke'),
(68, 78, 'Non-Compete Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Non-compete agreement for Federico Co pending nota'),
(69, 27, 'Study Leave Agreement', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Study leave agreement for Brian Co pending departm'),
(70, 29, 'Study Leave Agreement', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Study leave agreement for Eric Tiu awaiting employ'),
(71, 34, 'Study Leave Agreement', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Study leave agreement executed by Lorraine Chan fo'),
(72, 46, 'Study Leave Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Study leave agreement for Xavier Dy pending HR ver'),
(73, 69, 'Study Leave Agreement', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Study leave agreement executed by Victor Dy for pa'),
(74, 78, 'Study Leave Agreement', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Study leave agreement for Federico Co pending nota'),
(75, 27, 'Notice to Explain', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'NTE for Brian Co for unexplained absence on 2026-0'),
(76, 29, 'Notice to Explain', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'NTE for Eric Tiu for policy violation pending empl'),
(77, 34, 'Notice to Explain', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'NTE executed by Lorraine Chan for tardiness incide'),
(78, 46, 'Notice to Explain', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'NTE for Xavier Dy for insubordination pending HR r'),
(79, 69, 'Notice to Explain', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'NTE executed by Victor Dy for breach of protocol.'),
(80, 78, 'Notice to Explain', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'NTE for Federico Co pending supervisor signature.'),
(81, 27, 'Written Warning', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Written warning for Brian Co for repeated attendan'),
(82, 29, 'Written Warning', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Written warning for Eric Tiu for performance issue'),
(83, 34, 'Written Warning', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Written warning executed by Lorraine Chan for dres'),
(84, 46, 'Written Warning', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Written warning for Xavier Dy for misuse of compan'),
(85, 69, 'Written Warning', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Written warning executed by Victor Dy for minor po'),
(86, 78, 'Written Warning', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Written warning for Federico Co pending HR review.'),
(87, 27, 'Suspension Notice', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Suspension notice for Brian Co pending investigati'),
(88, 29, 'Suspension Notice', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Suspension notice for Eric Tiu awaiting employee s'),
(89, 34, 'Suspension Notice', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Suspension notice executed by Lorraine Chan for 3-'),
(90, 46, 'Suspension Notice', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Suspension notice for Xavier Dy pending HR approva'),
(91, 69, 'Suspension Notice', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Suspension notice executed by Victor Dy for 1-day '),
(92, 78, 'Suspension Notice', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Suspension notice for Federico Co pending legal re'),
(93, 27, 'Notice of Decision', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Notice of decision for Brian Co pending management'),
(94, 29, 'Notice of Decision', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Notice of decision for Eric Tiu awaiting final app'),
(95, 34, 'Notice of Decision', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Notice of decision executed by Lorraine Chan for g'),
(96, 46, 'Notice of Decision', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Notice of decision for Xavier Dy pending HR direct'),
(97, 69, 'Notice of Decision', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Notice of decision executed by Victor Dy for admin'),
(98, 78, 'Notice of Decision', 'Pending', 0, 'unsigned', 1, '2026-08-03 16:00:00', '2026-08-11', NULL, 'Medium', 'Notice of decision for Federico Co pending notariz'),
(99, 6, 'Notice to Explain', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'NTE for Erwin Cruz for violation of attendance pol'),
(100, 9, 'Notice to Explain', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'NTE executed by Maria Santos for performance issue'),
(101, 13, 'Notice to Explain', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'NTE for Lisa Aquino pending employee acknowledgmen'),
(102, 16, 'Notice to Explain', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'NTE executed by Sofia Ramos for minor policy infra'),
(103, 22, 'Notice to Explain', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'NTE for Patricia Tan pending investigation complet'),
(104, 25, 'Notice to Explain', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'NTE executed by Christopher Lee for breach of prot'),
(105, 6, 'Notice to Explain (NTE)', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'NTE for Erwin Cruz for violation of attendance pol'),
(106, 9, 'Notice to Explain (NTE)', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'NTE executed by Maria Santos for performance issue'),
(107, 13, 'Notice to Explain (NTE)', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'NTE for Lisa Aquino pending employee acknowledgmen'),
(108, 16, 'Notice to Explain (NTE)', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'NTE executed by Sofia Ramos for minor policy infra'),
(109, 22, 'Notice to Explain (NTE)', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'NTE for Patricia Tan pending investigation complet'),
(110, 25, 'Notice to Explain (NTE)', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'NTE executed by Christopher Lee for breach of prot'),
(111, 6, 'Exit Clearance', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Exit clearance for Erwin Cruz pending all departme'),
(112, 9, 'Exit Clearance', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Exit clearance executed by Maria Santos upon resig'),
(113, 13, 'Exit Clearance', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Exit clearance for Lisa Aquino awaiting finance ap'),
(114, 16, 'Exit Clearance', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Exit clearance executed by Sofia Ramos for contrac'),
(115, 22, 'Exit Clearance', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Exit clearance for Patricia Tan pending IT asset r'),
(116, 25, 'Exit Clearance', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'Exit clearance executed by Christopher Lee upon re'),
(117, 6, 'Clearance Survey', 'Pending', 0, 'unsigned', 1, '2026-07-31 16:00:00', '2026-08-08', NULL, 'High', 'Clearance survey for Erwin Cruz pending all depart'),
(118, 9, 'Clearance Survey', 'Completed', 0, 'signed', 1, '2026-07-27 16:00:00', '2026-08-04', NULL, 'Medium', 'Clearance survey executed by Maria Santos upon res'),
(119, 13, 'Clearance Survey', 'Pending', 0, 'pending_signature', 1, '2026-08-01 16:00:00', '2026-08-09', NULL, 'Medium', 'Clearance survey for Lisa Aquino awaiting finance '),
(120, 16, 'Clearance Survey', 'Completed', 0, 'signed', 1, '2026-07-25 16:00:00', '2026-08-02', NULL, 'Low', 'Clearance survey executed by Sofia Ramos for contr'),
(121, 22, 'Clearance Survey', 'Pending', 0, 'unsigned', 1, '2026-08-02 16:00:00', '2026-08-10', NULL, 'High', 'Clearance survey for Patricia Tan pending IT asset'),
(122, 25, 'Clearance Survey', 'Completed', 0, 'signed', 1, '2026-07-29 16:00:00', '2026-08-06', NULL, 'Medium', 'Clearance survey executed by Christopher Lee upon '),
(123, 15, 'Notice to Explain (NTE)', 'completed', 0, 'none', 1, '2026-08-03 05:40:32', NULL, NULL, 'Medium', NULL),
(124, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 06:34:56', NULL, NULL, 'Medium', NULL),
(125, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 06:42:36', NULL, NULL, 'Medium', NULL),
(126, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 06:58:13', NULL, NULL, 'Medium', NULL),
(127, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:03:23', NULL, NULL, 'Medium', NULL),
(128, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:05:37', NULL, NULL, 'Medium', NULL),
(129, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:06:17', NULL, NULL, 'Medium', NULL),
(130, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:08:42', NULL, NULL, 'Medium', NULL),
(131, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:11:34', NULL, NULL, 'Medium', NULL),
(132, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:13:11', NULL, NULL, 'Medium', NULL),
(133, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:15:26', NULL, NULL, 'Medium', NULL),
(134, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:17:54', NULL, NULL, 'Medium', NULL),
(135, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:19:15', NULL, NULL, 'Medium', NULL),
(136, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:21:50', NULL, NULL, 'Medium', NULL),
(137, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 07:39:45', NULL, NULL, 'Medium', NULL),
(138, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:15:50', NULL, NULL, 'Medium', NULL),
(139, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:24:24', NULL, NULL, 'Medium', NULL),
(140, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:25:43', NULL, NULL, 'Medium', NULL),
(141, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:25:52', NULL, NULL, 'Medium', NULL),
(142, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:29:42', NULL, NULL, 'Medium', NULL),
(143, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:32:49', NULL, NULL, 'Medium', NULL),
(144, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:33:32', NULL, NULL, 'Medium', NULL),
(145, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:33:39', NULL, NULL, 'Medium', NULL),
(146, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:34:35', NULL, NULL, 'Medium', NULL),
(147, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:34:54', NULL, NULL, 'Medium', NULL),
(148, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 08:49:09', NULL, NULL, 'Medium', NULL),
(149, 9, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 09:02:04', NULL, NULL, 'Medium', NULL),
(150, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:04:47', NULL, NULL, 'Medium', NULL),
(151, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:06:34', NULL, NULL, 'Medium', NULL),
(152, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:07:42', NULL, NULL, 'Medium', NULL),
(153, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:09:25', NULL, NULL, 'Medium', NULL),
(154, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:10:19', NULL, NULL, 'Medium', NULL),
(155, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:10:25', NULL, NULL, 'Medium', NULL),
(156, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:11:30', NULL, NULL, 'Medium', NULL),
(157, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:11:36', NULL, NULL, 'Medium', NULL),
(158, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:11:52', NULL, NULL, 'Medium', NULL),
(159, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:17:31', NULL, NULL, 'Medium', NULL),
(160, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:17:53', NULL, NULL, 'Medium', NULL),
(161, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:18:48', NULL, NULL, 'Medium', NULL),
(162, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:19:13', NULL, NULL, 'Medium', NULL),
(163, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 09:23:55', NULL, NULL, 'Medium', NULL),
(164, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 11:29:36', NULL, NULL, 'Medium', NULL),
(165, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 11:57:09', NULL, NULL, 'Medium', NULL),
(166, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 12:59:10', NULL, NULL, 'Medium', NULL),
(167, 15, 'Notice to Explain (NTE)', 'completed', 0, 'none', 1, '2026-08-03 13:01:20', NULL, NULL, 'Medium', NULL),
(168, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 13:04:13', NULL, NULL, 'Medium', NULL),
(169, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 13:06:13', NULL, NULL, 'Medium', NULL),
(170, 15, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 13:09:52', NULL, NULL, 'Medium', NULL),
(171, 9, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 13:18:05', NULL, NULL, 'Medium', NULL),
(172, 9, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 13:21:18', NULL, NULL, 'Medium', NULL),
(173, 9, 'Written Warning', 'completed', 0, 'none', 1, '2026-08-03 13:27:23', NULL, NULL, 'Medium', NULL),
(174, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 13:41:30', NULL, NULL, 'Medium', NULL),
(175, 15, 'Termination Decision', 'completed', 0, 'none', 1, '2026-08-03 13:42:10', NULL, NULL, 'Medium', NULL),
(176, 15, 'Suspension Notice', 'completed', 0, 'none', 1, '2026-08-03 13:51:22', NULL, NULL, 'Medium', NULL),
(177, 12, 'Suspension Notice', 'completed', 0, 'none', 1, '2026-08-03 14:03:18', NULL, NULL, 'Medium', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lc_email_templates`
--

CREATE TABLE `lc_email_templates` (
  `id` int(11) NOT NULL,
  `template_code` varchar(100) NOT NULL,
  `scenario` varchar(100) NOT NULL,
  `component` varchar(50) NOT NULL DEFAULT 'body',
  `component_order` int(11) NOT NULL DEFAULT 1,
  `template_text` text NOT NULL,
  `is_html` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_email_templates`
--

INSERT INTO `lc_email_templates` (`id`, `template_code`, `scenario`, `component`, `component_order`, `template_text`, `is_html`, `status`, `created_at`, `updated_at`) VALUES
(1, 'document_reminder', 'general', 'subject', 1, 'Reminder: Please Submit Your Required Document', 1, 'Active', '2026-08-04 08:29:59', '2026-08-04 08:29:59'),
(2, 'document_reminder', 'general', 'body', 1, 'Dear {{employee_name}},<br><br>\n\nThis is a friendly reminder that we have not yet received your required document: <strong>{{document_name}}</strong>.<br><br>\n\nPlease upload or submit the document as soon as possible to remain compliant with company policies and regulatory requirements.<br><br>\n\nIf you have already submitted the document, please disregard this reminder. If you are experiencing any issues or have questions, do not hesitate to reach out to the Human Resources Department.<br><br>\n\nYou may submit your document through the HR Management System or visit the HR office for assistance.<br><br>\n\nThank you for your prompt attention to this matter.<br><br>\n\nBest regards,<br>\n<strong>Human Resources Department</strong><br>\n{{company_name}}', 1, 'Active', '2026-08-04 08:29:59', '2026-08-04 08:29:59'),
(3, 'RSK001', 'regulatory_update', 'subject', 1, 'Risk Assessment Notification – Action Required', 1, 'Active', '2026-08-05 03:17:46', '2026-08-05 03:17:46'),
(4, 'RSK001', 'regulatory_update', 'body', 1, 'Dear {{employee_name}},<br><br>\n\nThis is to inform you that a <strong>Risk Assessment</strong> has been logged under your record and requires your attention.<br><br>\n\n<table style=\"border-collapse: collapse; width: 100%; margin: 12px 0;\">\n  <tr style=\"background-color: #f2f2f2;\">\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Employee No.</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{employee_id}}</td>\n  </tr>\n  <tr>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Risk Category</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{risk_category}}</td>\n  </tr>\n  <tr style=\"background-color: #f2f2f2;\">\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Risk Level</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{risk_severity}}</td>\n  </tr>\n  <tr>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Description</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{risk_description}}</td>\n  </tr>\n  <tr style=\"background-color: #f2f2f2;\">\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Mitigation Plan</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{mitigation_plan}}</td>\n  </tr>\n  <tr>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd; font-weight: bold;\">Reference ID</td>\n    <td style=\"padding: 8px 12px; border: 1px solid #ddd;\">{{reference_id}}</td>\n  </tr>\n</table>\n\nPlease review the details above and coordinate with the <strong>HR Legal &amp; Compliance Office</strong> if you have any questions or need further clarification.<br><br>\n\nYour prompt response and cooperation are highly appreciated.<br><br>\n\nBest regards,<br>\n<strong>HR Legal &amp; Compliance Office</strong><br>\n{{company_name}}', 1, 'Active', '2026-08-05 03:17:46', '2026-08-05 03:17:46');

-- --------------------------------------------------------

--
-- Table structure for table `lc_employee_documents`
--

CREATE TABLE `lc_employee_documents` (
  `id` int(11) NOT NULL,
  `document_uuid` varchar(64) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `verification_status` varchar(50) DEFAULT 'Pending Upload',
  `compliance_status` varchar(50) DEFAULT 'Missing',
  `status` varchar(50) DEFAULT 'Valid',
  `file_path` varchar(500) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `flag_reason` varchar(255) DEFAULT NULL,
  `flag_notes` text DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_employee_documents`
--

INSERT INTO `lc_employee_documents` (`id`, `document_uuid`, `employee_id`, `document_name`, `document_type`, `document_number`, `category`, `verification_status`, `compliance_status`, `status`, `file_path`, `mime_type`, `issued_date`, `expiry_date`, `flag_reason`, `flag_notes`, `flagged_at`, `reminder_sent_at`, `verified_by`, `verified_at`, `rejection_reason`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(9, 'DOC-2026-0009', 6, 'PRC Professional License', 'License', 'PRC-2026-009', 'Professional', 'Verified', 'Expiring Soon', 'Valid', '/uploads/licenses/prc_6.pdf', 'application/pdf', '2026-01-10', '2026-08-20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-08 16:00:00', '2026-08-04 06:19:17'),
(10, 'DOC-2026-0010', 9, 'PRC License ID', 'License', 'PRC-ID-2026-010', 'Identification', 'Verified', 'Expiring Soon', 'Valid', '/uploads/licenses/prc_9.pdf', 'application/pdf', '2026-02-05', '2026-08-25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-09 16:00:00', '2026-08-04 06:19:17'),
(11, 'DOC-2026-0011', 10, 'PRC CPD Compliance Certificate', 'Certificate', 'CPD-2026-011', 'Compliance', 'Verified', 'Expiring Soon', 'Valid', '/uploads/licenses/prc_10.pdf', 'application/pdf', '2026-03-01', '2026-09-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-10 16:00:00', '2026-08-04 06:19:17'),
(12, 'DOC-2026-0012', 11, 'Annual Medical Certificate', 'Certificate', 'MED-2026-012', 'Medical', 'Verified', 'Expiring Soon', 'Valid', '/uploads/documents/medical_11.pdf', 'application/pdf', '2026-04-10', '2026-08-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-11 16:00:00', '2026-08-04 06:19:17'),
(13, 'DOC-2026-0013', 12, 'NBI Clearance', 'Clearance', 'NBI-2026-013', 'Background', 'Verified', 'Expiring Soon', 'Valid', '/uploads/documents/medical_12.pdf', 'application/pdf', '2026-05-05', '2026-08-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-12 16:00:00', '2026-08-04 06:19:18'),
(14, 'DOC-2026-0014', 13, 'Drug Test Certificate', 'Certificate', 'DT-2026-014', 'Institutional', 'Verified', 'Expiring Soon', 'Valid', '/uploads/documents/medical_13.pdf', 'application/pdf', '2026-06-01', '2026-08-28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-13 16:00:00', '2026-08-04 06:19:18'),
(15, 'DOC-2026-0015', 14, 'Child Protection Training Certificate', 'Certificate', 'CPT-2026-015', 'Compliance', 'Verified', 'Expiring Soon', 'Valid', '/uploads/documents/medical_14.pdf', 'application/pdf', '2026-06-10', '2026-09-03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-14 16:00:00', '2026-08-04 06:19:18'),
(16, 'DOC-2026-0016', 15, 'Data Privacy Training Certificate', 'Certificate', 'DPT-2026-016', 'Compliance', 'Verified', 'Complete', 'Valid', '/uploads/documents/medical_15.pdf', 'application/pdf', '2026-07-01', '2027-07-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-15 16:00:00', '2026-08-04 06:19:18'),
(17, 'DOC-2026-0017', 6, 'Fire Safety & Emergency Training', 'Certificate', 'FST-2026-017', 'Safety', 'Verified', 'Expiring Soon', 'Valid', '/uploads/documents/medical_6.pdf', 'application/pdf', '2026-01-20', '2026-08-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-16 16:00:00', '2026-08-04 06:19:18'),
(18, 'DOC-2026-0018', 9, 'First Aid Certificate', 'Certificate', 'FA-2026-018', 'Emergency', 'Verified', 'Expired', 'Valid', '/uploads/documents/medical_9.pdf', 'application/pdf', '2026-02-15', '2026-07-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-17 16:00:00', '2026-08-04 06:19:18'),
(19, 'DOC-2026-0019', 10, 'Employment Contract', 'Contract', 'EMP-2026-019', 'Employment', 'Verified', 'Expiring Soon', 'Valid', '/uploads/contracts/contract_10.pdf', 'application/pdf', '2026-03-01', '2026-08-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-07-18 16:00:00', '2026-08-04 06:19:18');

-- --------------------------------------------------------

--
-- Table structure for table `lc_exit_approvals`
--

CREATE TABLE `lc_exit_approvals` (
  `id` int(11) NOT NULL,
  `exit_request_id` int(11) NOT NULL,
  `approver_role` varchar(100) NOT NULL,
  `action` enum('Pending','Approved','Rejected','Returned') NOT NULL DEFAULT 'Pending',
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_exit_approvals`
--

INSERT INTO `lc_exit_approvals` (`id`, `exit_request_id`, `approver_role`, `action`, `comments`, `acted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Immediate Supervisor', 'Approved', 'Approved. No pending issues.', '2026-07-16 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(2, 1, 'Department Head', 'Approved', 'Approved. Good standing employee.', '2026-07-16 02:30:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(3, 1, 'HR Officer', 'Approved', 'Exit clearance initiated.', '2026-07-17 00:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(4, 1, 'Legal Officer', 'Approved', 'All legal requirements verified.', '2026-07-18 06:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(5, 1, 'Finance', 'Approved', 'Final pay processed.', '2026-07-19 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(6, 1, 'IT', 'Approved', 'Access terminated.', '2026-07-19 03:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(7, 1, 'Property Custodian', 'Approved', 'Equipment returned.', '2026-07-19 06:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(8, 1, 'HR Director', 'Approved', 'Exit approved.', '2026-07-20 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(9, 2, 'Immediate Supervisor', 'Approved', 'Approved resignation.', '2026-08-01 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(10, 2, 'Department Head', 'Approved', 'Approved.', '2026-08-01 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(11, 2, 'HR Officer', 'Approved', 'Clearance initiated.', '2026-08-01 03:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(12, 2, 'Legal Officer', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(13, 2, 'Finance', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(14, 2, 'IT', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(15, 2, 'Property Custodian', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(16, 2, 'HR Director', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(17, 3, 'Immediate Supervisor', 'Approved', 'Approved contract end.', '2026-07-11 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(18, 3, 'Department Head', 'Approved', 'Approved.', '2026-07-12 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(19, 3, 'HR Officer', 'Approved', 'Clearance in progress.', '2026-07-14 00:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(20, 3, 'Legal Officer', 'Approved', 'Compliance verified.', '2026-07-22 06:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(21, 3, 'Finance', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(22, 3, 'IT', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(23, 3, 'Property Custodian', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(24, 3, 'HR Director', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(25, 4, 'Immediate Supervisor', 'Returned', 'Missing clearance docs.', '2026-08-02 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(26, 4, 'Department Head', 'Returned', 'Cannot approve without documents.', '2026-08-02 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(27, 4, 'HR Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(28, 4, 'Legal Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(29, 4, 'Finance', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(30, 4, 'IT', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(31, 4, 'Property Custodian', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(32, 4, 'HR Director', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(33, 5, 'Immediate Supervisor', 'Approved', 'Approved retirement.', '2026-05-25 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(34, 5, 'Department Head', 'Approved', 'Approved.', '2026-05-26 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(35, 5, 'HR Officer', 'Approved', 'Retirement benefits processed.', '2026-06-01 00:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(36, 5, 'Legal Officer', 'Approved', 'All requirements met.', '2026-06-03 06:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(37, 5, 'Finance', 'Approved', 'Final pay and benefits released.', '2026-06-05 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(38, 5, 'IT', 'Approved', 'Access terminated.', '2026-06-05 03:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(39, 5, 'Property Custodian', 'Approved', 'All equipment returned.', '2026-06-05 06:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(40, 5, 'HR Director', 'Approved', 'Retirement approved.', '2026-06-06 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(41, 6, 'Immediate Supervisor', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(42, 6, 'Department Head', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(43, 6, 'HR Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(44, 6, 'Legal Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(45, 6, 'Finance', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(46, 6, 'IT', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(47, 6, 'Property Custodian', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(48, 6, 'HR Director', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(49, 7, 'Immediate Supervisor', 'Rejected', 'Policy violation pending investigation.', '2026-07-10 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(50, 7, 'Department Head', 'Rejected', 'Cannot approve.', '2026-07-10 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(51, 7, 'HR Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(52, 7, 'Legal Officer', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(53, 7, 'Finance', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(54, 7, 'IT', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(55, 7, 'Property Custodian', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(56, 7, 'HR Director', 'Pending', NULL, NULL, '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(57, 8, 'Immediate Supervisor', 'Approved', 'Approved.', '2026-07-29 01:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(58, 8, 'Department Head', 'Approved', 'Approved.', '2026-07-29 02:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(59, 8, 'HR Officer', 'Approved', 'Clearance in progress.', '2026-07-30 00:00:00', '2026-08-03 14:35:09', '2026-08-03 14:35:09'),
(60, 8, 'Legal Officer', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(61, 8, 'Finance', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(62, 8, 'IT', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(63, 8, 'Property Custodian', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26'),
(64, 8, 'HR Director', 'Approved', 'Verified.', '2026-08-03 23:30:26', '2026-08-03 14:35:09', '2026-08-03 23:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `lc_exit_clearance`
--

CREATE TABLE `lc_exit_clearance` (
  `id` int(11) NOT NULL,
  `exit_request_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` varchar(100) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_exit_clearance`
--

INSERT INTO `lc_exit_clearance` (`id`, `exit_request_id`, `category`, `item_name`, `is_completed`, `completed_by`, `completed_at`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-07-17 02:00:00', 'All documents verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(2, 1, 'HR', 'Government Records Updated', 1, 'HR Officer Cruz', '2026-07-17 02:30:00', 'SSS, PhilHealth, Pag-IBIG updated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(3, 1, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-07-17 03:00:00', 'No unused leave credits.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(4, 1, 'HR', 'Employment Certificate Ready', 1, 'HR Officer Cruz', '2026-07-17 06:00:00', 'Certificate prepared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(5, 1, 'Finance', 'Salary Clearance', 1, 'Finance Officer Dizon', '2026-07-18 01:00:00', 'No outstanding salary adjustments.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(6, 1, 'Finance', 'Cash Advance Cleared', 1, 'Finance Officer Dizon', '2026-07-18 01:30:00', 'All cash advances settled.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(7, 1, 'Finance', 'Payroll Clearance', 1, 'Finance Officer Dizon', '2026-07-18 02:00:00', 'Final payroll processed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(8, 1, 'Finance', 'Final Pay Processed', 1, 'Finance Officer Dizon', '2026-07-19 00:00:00', 'Final pay released.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(9, 1, 'IT', 'Email Disabled', 1, 'IT Support Gomez', '2026-07-19 01:00:00', 'Account deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(10, 1, 'IT', 'System Access Removed', 1, 'IT Support Gomez', '2026-07-19 01:30:00', 'All systems access revoked.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(11, 1, 'IT', 'Laptop Returned', 1, 'IT Support Gomez', '2026-07-19 03:00:00', 'Asset ID 1042 returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(12, 1, 'IT', 'ID Returned', 1, 'IT Support Gomez', '2026-07-19 06:00:00', 'ID card deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(13, 1, 'Property', 'Office Keys Returned', 1, 'Property Custodian Ramos', '2026-07-19 02:00:00', 'All keys returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(14, 1, 'Property', 'Uniform Returned', 1, 'Property Custodian Ramos', '2026-07-19 02:30:00', 'N/A - no uniform issued.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(15, 1, 'Property', 'Equipment Returned', 1, 'Property Custodian Ramos', '2026-07-19 03:00:00', 'All equipment accounted for.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(16, 1, 'Property', 'Library Books Returned', 1, 'Property Custodian Ramos', '2026-07-19 06:00:00', 'No borrowed books.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(17, 1, 'Legal', 'NDA Signed', 1, 'Legal Officer Reyes', '2026-07-20 01:00:00', 'NDA acknowledged.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(18, 1, 'Legal', 'Exit Agreement Signed', 1, 'Legal Officer Reyes', '2026-07-20 01:30:00', 'Exit agreement executed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(19, 1, 'Legal', 'Pending Case Check', 1, 'Legal Officer Reyes', '2026-07-20 02:00:00', 'No pending cases.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(20, 1, 'Legal', 'Company Policy Compliance Verified', 1, 'Legal Officer Reyes', '2026-07-20 02:30:00', 'All policies acknowledged.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(21, 2, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-08-01 01:00:00', 'Documents collected.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(22, 2, 'HR', 'Government Records Updated', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(23, 2, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-08-01 01:30:00', '5 days unused leave.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(24, 2, 'HR', 'Employment Certificate Ready', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(25, 2, 'Finance', 'Salary Clearance', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(26, 2, 'Finance', 'Cash Advance Cleared', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(27, 2, 'Finance', 'Payroll Clearance', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(28, 2, 'Finance', 'Final Pay Processed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(29, 2, 'IT', 'Email Disabled', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(30, 2, 'IT', 'System Access Removed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(31, 2, 'IT', 'Laptop Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(32, 2, 'IT', 'ID Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(33, 2, 'Property', 'Office Keys Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(34, 2, 'Property', 'Uniform Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(35, 2, 'Property', 'Equipment Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(36, 2, 'Property', 'Library Books Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(37, 2, 'Legal', 'NDA Signed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(38, 2, 'Legal', 'Exit Agreement Signed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(39, 2, 'Legal', 'Pending Case Check', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(40, 2, 'Legal', 'Company Policy Compliance Verified', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(41, 3, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-07-22 01:00:00', 'Files verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(42, 3, 'HR', 'Government Records Updated', 1, 'HR Officer Cruz', '2026-07-22 01:30:00', 'Records updated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(43, 3, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-07-22 02:00:00', 'Leave balance cleared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(44, 3, 'HR', 'Employment Certificate Ready', 1, 'HR Officer Cruz', '2026-07-22 06:00:00', 'Certificate prepared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(45, 3, 'Finance', 'Salary Clearance', 1, 'Finance Officer Dizon', '2026-07-23 01:00:00', 'No outstanding balance.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(46, 3, 'Finance', 'Cash Advance Cleared', 1, 'Finance Officer Dizon', '2026-07-23 01:30:00', 'Cleared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(47, 3, 'Finance', 'Payroll Clearance', 1, 'Finance Officer Dizon', '2026-07-23 02:00:00', 'Final pay processed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(48, 3, 'Finance', 'Final Pay Processed', 1, 'Finance Officer Dizon', '2026-07-24 00:00:00', 'Released.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(49, 3, 'IT', 'Email Disabled', 1, 'IT Support Gomez', '2026-07-24 01:00:00', 'Deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(50, 3, 'IT', 'System Access Removed', 1, 'IT Support Gomez', '2026-07-24 01:30:00', 'Revoked.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(51, 3, 'IT', 'Laptop Returned', 1, 'IT Support Gomez', '2026-07-24 03:00:00', 'Asset ID 3312 returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(52, 3, 'IT', 'ID Returned', 1, 'IT Support Gomez', '2026-07-24 06:00:00', 'Deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(53, 3, 'Property', 'Office Keys Returned', 0, NULL, NULL, 'Pending return.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(54, 3, 'Property', 'Uniform Returned', 0, NULL, NULL, 'Pending return.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(55, 3, 'Property', 'Equipment Returned', 0, NULL, NULL, 'Pending return.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(56, 3, 'Property', 'Library Books Returned', 0, NULL, NULL, 'N/A.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(57, 3, 'Legal', 'NDA Signed', 1, 'Legal Officer Reyes', '2026-07-25 01:00:00', 'Signed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(58, 3, 'Legal', 'Exit Agreement Signed', 1, 'Legal Officer Reyes', '2026-07-25 01:30:00', 'Executed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(59, 3, 'Legal', 'Pending Case Check', 1, 'Legal Officer Reyes', '2026-07-25 02:00:00', 'No pending cases.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(60, 3, 'Legal', 'Company Policy Compliance Verified', 1, 'Legal Officer Reyes', '2026-07-25 02:30:00', 'Verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(61, 4, 'HR', 'Personnel File Complete', 0, NULL, NULL, 'Missing documents.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(62, 4, 'HR', 'Government Records Updated', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(63, 4, 'HR', 'Leave Balance Verified', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(64, 4, 'HR', 'Employment Certificate Ready', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(65, 4, 'Finance', 'Salary Clearance', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(66, 4, 'Finance', 'Cash Advance Cleared', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(67, 4, 'Finance', 'Payroll Clearance', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(68, 4, 'Finance', 'Final Pay Processed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(69, 4, 'IT', 'Email Disabled', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(70, 4, 'IT', 'System Access Removed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(71, 4, 'IT', 'Laptop Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(72, 4, 'IT', 'ID Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(73, 4, 'Property', 'Office Keys Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(74, 4, 'Property', 'Uniform Returned', 0, NULL, NULL, 'N/A.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(75, 4, 'Property', 'Equipment Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(76, 4, 'Property', 'Library Books Returned', 0, NULL, NULL, 'N/A.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(77, 4, 'Legal', 'NDA Signed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(78, 4, 'Legal', 'Exit Agreement Signed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(79, 4, 'Legal', 'Pending Case Check', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(80, 4, 'Legal', 'Company Policy Compliance Verified', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(81, 5, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-06-01 01:00:00', 'Verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(82, 5, 'HR', 'Government Records Updated', 1, 'HR Officer Cruz', '2026-06-01 01:30:00', 'Updated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(83, 5, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-06-01 02:00:00', 'Converted to cash.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(84, 5, 'HR', 'Employment Certificate Ready', 1, 'HR Officer Cruz', '2026-06-02 01:00:00', 'Prepared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(85, 5, 'Finance', 'Salary Clearance', 1, 'Finance Officer Dizon', '2026-06-02 02:00:00', 'Cleared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(86, 5, 'Finance', 'Cash Advance Cleared', 1, 'Finance Officer Dizon', '2026-06-02 02:30:00', 'Cleared.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(87, 5, 'Finance', 'Payroll Clearance', 1, 'Finance Officer Dizon', '2026-06-03 00:00:00', 'Final payroll done.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(88, 5, 'Finance', 'Final Pay Processed', 1, 'Finance Officer Dizon', '2026-06-04 00:00:00', 'Released with retirement benefits.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(89, 5, 'IT', 'Email Disabled', 1, 'IT Support Gomez', '2026-06-04 01:00:00', 'Deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(90, 5, 'IT', 'System Access Removed', 1, 'IT Support Gomez', '2026-06-04 01:30:00', 'Revoked.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(91, 5, 'IT', 'Laptop Returned', 1, 'IT Support Gomez', '2026-06-04 03:00:00', 'Asset ID 1150 returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(92, 5, 'IT', 'ID Returned', 1, 'IT Support Gomez', '2026-06-04 06:00:00', 'Deactivated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(93, 5, 'Property', 'Office Keys Returned', 1, 'Property Custodian Ramos', '2026-06-04 02:00:00', 'All keys returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(94, 5, 'Property', 'Uniform Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(95, 5, 'Property', 'Equipment Returned', 1, 'Property Custodian Ramos', '2026-06-04 03:00:00', 'All items returned.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(96, 5, 'Property', 'Library Books Returned', 1, 'Property Custodian Ramos', '2026-06-04 06:00:00', 'No borrowed books.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(97, 5, 'Legal', 'NDA Signed', 1, 'Legal Officer Reyes', '2026-06-05 01:00:00', 'Signed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(98, 5, 'Legal', 'Exit Agreement Signed', 1, 'Legal Officer Reyes', '2026-06-05 01:30:00', 'Executed.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(99, 5, 'Legal', 'Pending Case Check', 1, 'Legal Officer Reyes', '2026-06-05 02:00:00', 'No cases.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(100, 5, 'Legal', 'Company Policy Compliance Verified', 1, 'Legal Officer Reyes', '2026-06-05 02:30:00', 'Verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(101, 6, 'HR', 'Personnel File Complete', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(102, 6, 'HR', 'Government Records Updated', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(103, 6, 'HR', 'Leave Balance Verified', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(104, 6, 'HR', 'Employment Certificate Ready', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(105, 6, 'Finance', 'Salary Clearance', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(106, 6, 'Finance', 'Cash Advance Cleared', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(107, 6, 'Finance', 'Payroll Clearance', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(108, 6, 'Finance', 'Final Pay Processed', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(109, 6, 'IT', 'Email Disabled', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(110, 6, 'IT', 'System Access Removed', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(111, 6, 'IT', 'Laptop Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(112, 6, 'IT', 'ID Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(113, 6, 'Property', 'Office Keys Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(114, 6, 'Property', 'Uniform Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(115, 6, 'Property', 'Equipment Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(116, 6, 'Property', 'Library Books Returned', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(117, 6, 'Legal', 'NDA Signed', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(118, 6, 'Legal', 'Exit Agreement Signed', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(119, 6, 'Legal', 'Pending Case Check', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(120, 6, 'Legal', 'Company Policy Compliance Verified', 0, NULL, NULL, NULL, '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(121, 7, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-07-06 01:00:00', 'Files retrieved.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(122, 7, 'HR', 'Government Records Updated', 1, 'HR Officer Cruz', '2026-07-06 01:30:00', 'Updated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(123, 7, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-07-06 02:00:00', 'Verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(124, 7, 'HR', 'Employment Certificate Ready', 0, NULL, NULL, 'Pending - exit rejected.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(125, 7, 'Finance', 'Salary Clearance', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(126, 7, 'Finance', 'Cash Advance Cleared', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(127, 7, 'Finance', 'Payroll Clearance', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(128, 7, 'Finance', 'Final Pay Processed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(129, 7, 'IT', 'Email Disabled', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(130, 7, 'IT', 'System Access Removed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(131, 7, 'IT', 'Laptop Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(132, 7, 'IT', 'ID Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(133, 7, 'Property', 'Office Keys Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(134, 7, 'Property', 'Uniform Returned', 0, NULL, NULL, 'N/A.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(135, 7, 'Property', 'Equipment Returned', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(136, 7, 'Property', 'Library Books Returned', 0, NULL, NULL, 'N/A.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(137, 7, 'Legal', 'NDA Signed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(138, 7, 'Legal', 'Exit Agreement Signed', 0, NULL, NULL, 'Pending.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(139, 7, 'Legal', 'Pending Case Check', 0, NULL, NULL, 'Investigation ongoing.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(140, 7, 'Legal', 'Company Policy Compliance Verified', 0, NULL, NULL, 'Pending investigation.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(141, 8, 'HR', 'Personnel File Complete', 1, 'HR Officer Cruz', '2026-07-29 01:00:00', 'Verified.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(142, 8, 'HR', 'Government Records Updated', 1, 'HR Officer Cruz', '2026-07-29 01:30:00', 'Updated.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(143, 8, 'HR', 'Leave Balance Verified', 1, 'HR Officer Cruz', '2026-07-29 02:00:00', '3 days unused leave.', '2026-08-03 14:35:25', '2026-08-03 14:35:25'),
(144, 8, 'HR', 'Employment Certificate Ready', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(145, 8, 'Finance', 'Salary Clearance', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(146, 8, 'Finance', 'Cash Advance Cleared', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(147, 8, 'Finance', 'Payroll Clearance', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(148, 8, 'Finance', 'Final Pay Processed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(149, 8, 'IT', 'Email Disabled', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(150, 8, 'IT', 'System Access Removed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(151, 8, 'IT', 'Laptop Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(152, 8, 'IT', 'ID Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(153, 8, 'Property', 'Office Keys Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(154, 8, 'Property', 'Uniform Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(155, 8, 'Property', 'Equipment Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(156, 8, 'Property', 'Library Books Returned', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(157, 8, 'Legal', 'NDA Signed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(158, 8, 'Legal', 'Exit Agreement Signed', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(159, 8, 'Legal', 'Pending Case Check', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26'),
(160, 8, 'Legal', 'Company Policy Compliance Verified', 1, 'System', '2026-08-03 23:30:26', 'Completed.', '2026-08-03 14:35:25', '2026-08-03 23:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `lc_exit_requests`
--

CREATE TABLE `lc_exit_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `employee_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `date_filed` date DEFAULT NULL,
  `last_working_day` date DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `type_of_separation` enum('Voluntary','Involuntary','Retirement','End of Contract','Deceased','Others') NOT NULL DEFAULT 'Voluntary',
  `immediate_supervisor` varchar(100) DEFAULT NULL,
  `separation_notes` text DEFAULT NULL,
  `overall_status` enum('Pending','Completed') NOT NULL DEFAULT 'Pending',
  `legal_status` enum('Pending','Confirmed','Returned') NOT NULL DEFAULT 'Pending',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` varchar(100) DEFAULT NULL,
  `legal_remarks` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `recruitment_status` enum('Draft','Notified','Updated') NOT NULL DEFAULT 'Draft',
  `recruitment_notified_at` timestamp NULL DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_exit_requests`
--

INSERT INTO `lc_exit_requests` (`id`, `request_number`, `employee_id`, `employee_name`, `department`, `position`, `date_filed`, `last_working_day`, `reason`, `type_of_separation`, `immediate_supervisor`, `separation_notes`, `overall_status`, `legal_status`, `confirmed_at`, `confirmed_by`, `legal_remarks`, `approved_at`, `recruitment_status`, `recruitment_notified_at`, `archived`, `archived_at`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'EXIT-20260801-1042', 9, 'Maria Santos', 'Executive Administration', 'College President', '2026-07-15', '2026-08-15', 'Resignation', 'Voluntary', 'Juan Dela Cruz', 'Employee resigned for better opportunity abroad.', 'Completed', 'Confirmed', '2026-08-03 09:19:22', 'Legal Officer', '', '2026-07-20 06:00:00', 'Notified', '2026-08-03 09:19:22', 1, '2026-08-02 01:00:00', '2026-07-15 00:00:00', '2026-08-03 16:27:13', 1),
(2, 'EXIT-20260802-2051', 10, 'Jose Reyes', 'Executive Administration', 'Executive Vice President', '2026-08-01', '2026-09-01', 'Resignation', 'Voluntary', 'Ana Reyes', 'Pursuing career change to private sector.', 'Pending', 'Returned', NULL, 'Legal Officer', '', '2026-08-01 08:45:00', 'Draft', NULL, 0, NULL, '2026-08-01 00:00:00', '2026-08-04 00:00:08', 1),
(3, 'EXIT-20260728-3890', 11, 'Ana Garcia', 'Academic Affairs', 'Vice President for Academic Affairs', '2026-07-10', '2026-08-10', 'End of Contract', 'End of Contract', 'Pedro Lim', 'Contract ended as per agreement.', 'Pending', 'Confirmed', '2026-07-25 03:00:00', 'Legal Officer Reyes', 'Compliance verified. Contract requirements met.', '2026-07-22 01:30:00', 'Notified', '2026-07-25 03:01:00', 0, NULL, '2026-07-10 00:00:00', '2026-08-03 16:27:13', 1),
(4, 'EXIT-20260803-7723', 12, 'Carlos Mendoza', 'Administration', 'Vice President for Administration', '2026-08-02', '2026-09-02', 'Resignation', 'Voluntary', 'Carmen Villanueva', 'Missing clearance documents.', 'Pending', 'Returned', NULL, 'Legal Officer', '', '2026-08-02 02:00:00', 'Notified', '2026-08-03 09:19:48', 0, NULL, '2026-08-02 00:00:00', '2026-08-03 23:59:41', 1),
(5, 'EXIT-20260615-9901', 15, 'Pedro Lim', 'Executive Administration', 'Campus Director', '2026-05-20', '2026-06-30', 'Retirement', 'Retirement', 'Sandra Pascual', 'Retired after 30 years of service.', 'Completed', 'Confirmed', '2026-06-10 06:00:00', 'Legal Officer Reyes', 'All retirement benefits processed.', '2026-06-05 02:00:00', 'Updated', '2026-06-10 06:01:00', 1, '2026-07-01 00:00:00', '2026-05-20 00:00:00', '2026-08-03 16:27:13', 1),
(6, 'EXIT-20260803-1155', 19, 'Michael Torres', 'Academic Affairs', 'Assistant Dean', '2026-08-03', '2026-09-15', 'Resignation', 'Voluntary', 'Michael Ong', 'Relocating to another city.', 'Completed', 'Confirmed', '2026-08-03 18:06:02', 'Legal Officer', '', NULL, 'Notified', '2026-08-03 18:06:02', 0, NULL, '2026-08-03 00:00:00', '2026-08-04 00:06:02', 1),
(7, 'EXIT-20260720-4408', 14, 'Mark Villanueva', 'Student Affairs', 'Vice President for Student Affairs', '2026-07-05', '2026-08-05', 'Involuntary', 'Involuntary', 'Patricia Go', 'Termination due to policy violation.', 'Pending', 'Confirmed', NULL, NULL, 'Pending investigation findings.', '2026-07-10 01:00:00', 'Draft', NULL, 0, NULL, '2026-07-05 00:00:00', '2026-08-03 16:37:51', 1),
(8, 'EXIT-20260801-6629', 22, 'Patricia Tan', 'Faculty', 'Course Coordinator', '2026-07-28', '2026-09-01', 'Resignation', 'Voluntary', 'Antonio Co', 'Further studies.', 'Completed', 'Confirmed', NULL, NULL, NULL, '2026-07-28 07:00:00', 'Draft', NULL, 0, NULL, '2026-07-28 00:00:00', '2026-08-03 16:37:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lc_generated_reports`
--

CREATE TABLE `lc_generated_reports` (
  `id` int(11) NOT NULL,
  `report_code` varchar(100) NOT NULL,
  `template_id` int(11) DEFAULT 1,
  `generated_by` int(11) DEFAULT 1,
  `report_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_format` varchar(50) DEFAULT 'PDF',
  `status` enum('Generated','Submitted','Pending','Processing','Approved','Returned','Archived','Error') DEFAULT 'Generated',
  `report_key` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submission_id` int(11) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `return_remarks` text DEFAULT NULL,
  `period_label` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_generated_reports`
--

INSERT INTO `lc_generated_reports` (`id`, `report_code`, `template_id`, `generated_by`, `report_date`, `start_date`, `end_date`, `file_path`, `file_format`, `status`, `report_key`, `created_at`, `submission_id`, `submitted_by`, `submitted_at`, `approved_by`, `approved_at`, `archived_by`, `archived_at`, `returned_by`, `returned_at`, `return_remarks`, `period_label`) VALUES
(1, 'RPT-2026-0001', 1, 157, '2026-08-04', '2026-08-04', '2026-08-04', 'reports/generated/RPT-2026-0001.pdf', 'PDF', 'Submitted', 'active_employees', '2026-08-03 16:51:36', 1, 157, '2026-08-04 00:51:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'RPT--20260804061128', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'employee_master_list', '2026-08-04 04:11:28', NULL, 0, '2026-08-04 12:11:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'RPT--20260804061156', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'employee_master_list', '2026-08-04 04:11:56', NULL, 0, '2026-08-04 12:11:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'RPT--20260804065549', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'recruitment_summary', '2026-08-04 04:55:49', NULL, 0, '2026-08-04 12:55:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'RPT--20260804073417', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'employee_master_list', '2026-08-04 05:34:17', NULL, 0, '2026-08-04 13:34:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'RPT--20260804073454', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'policy_acknowledgement', '2026-08-04 05:34:54', NULL, 0, '2026-08-04 13:34:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'RPT--20260804073513', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'policy_acknowledgement', '2026-08-04 05:35:13', NULL, 0, '2026-08-04 13:35:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'RPT--20260804074037', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'sss_compliance_report', '2026-08-04 05:40:37', NULL, 0, '2026-08-04 13:40:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'RPT--20260804074044', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'missing_registrations', '2026-08-04 05:40:44', NULL, 0, '2026-08-04 13:40:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'RPT--20260804074053', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'document_expiration', '2026-08-04 05:40:53', NULL, 0, '2026-08-04 13:40:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'RPT--20260804074153', 1, 0, '2026-08-04', NULL, NULL, NULL, 'PDF', 'Submitted', 'vacancy_reports', '2026-08-04 05:41:53', NULL, 0, '2026-08-04 13:41:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'RPT--20260806142158', 1, 0, '2026-08-06', NULL, NULL, NULL, 'PDF', 'Submitted', 'employee_master_list', '2026-08-06 12:21:58', NULL, 0, '2026-08-06 20:21:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'RPT--20260806142200', 1, 0, '2026-08-06', NULL, NULL, NULL, 'PDF', 'Submitted', 'employee_master_list', '2026-08-06 12:22:00', NULL, 0, '2026-08-06 20:22:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lc_government_requirements`
--

CREATE TABLE `lc_government_requirements` (
  `id` int(11) NOT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `agency` varchar(100) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','overdue','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_government_validations`
--

CREATE TABLE `lc_government_validations` (
  `id` int(11) NOT NULL,
  `agency` varchar(100) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `government_number` varchar(100) DEFAULT NULL,
  `status` enum('Verified','Pending','Submitted','Invalid Format','Expired') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(5, 'INC-2024-005', NULL, 'Data Breach', 'Critical', 'Unauthorized Access to Confidential Files', 'Employee accessed confidential client data outside of authorized job function.', '2024-07-25', 'IT Department - Server Room', 11, 45, 'Carlos Mendoza', 'decision_made', 'Decision', NULL, 1, NULL, NULL, 1, '2026-08-04 01:23:12', '2026-08-04 01:23:12');

-- --------------------------------------------------------

--
-- Table structure for table `lc_job_posting_requests`
--

CREATE TABLE `lc_job_posting_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `exit_request_id` int(11) DEFAULT NULL,
  `vacant_position_id` int(11) DEFAULT NULL,
  `previous_position` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'Regular',
  `salary_grade` varchar(20) DEFAULT NULL,
  `reason` enum('Replacement','New Position','Promotion','Others') NOT NULL DEFAULT 'Replacement',
  `vacancy_date` date DEFAULT NULL,
  `immediate_supervisor` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Pending Approval','Approved','Rejected','Published','Filled','Archived') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_job_posting_requests`
--

INSERT INTO `lc_job_posting_requests` (`id`, `request_number`, `exit_request_id`, `vacant_position_id`, `previous_position`, `department`, `employment_type`, `salary_grade`, `reason`, `vacancy_date`, `immediate_supervisor`, `status`, `created_at`, `updated_at`) VALUES
(1, 'JOB-20260801-1001', 1, 1, 'HR Officer', 'Human Resources', 'Regular', 'HR-12', 'Replacement', '2026-08-15', 'Juan Dela Cruz', 'Pending Approval', '2026-08-03 14:35:47', '2026-08-03 14:35:47'),
(2, 'JOB-20260728-2002', 3, 2, 'Systems Analyst', 'Information Technology', 'Fixed-Term', 'IT-15', 'Replacement', '2026-08-10', 'Pedro Lim', 'Draft', '2026-08-03 14:35:47', '2026-08-03 14:35:47'),
(3, 'JOB-20260615-3003', 5, 3, 'Department Head', 'Academics', 'Regular', 'AC-18', 'Replacement', '2026-06-30', 'Sandra Pascual', 'Published', '2026-08-03 14:35:47', '2026-08-03 14:35:47'),
(4, 'JOB-20260802-4004', 2, 4, 'Accounting Head', 'Finance', 'Regular', 'FIN-16', 'Replacement', '2026-09-01', 'Ana Reyes', 'Draft', '2026-08-03 14:35:47', '2026-08-03 14:35:47');

-- --------------------------------------------------------

--
-- Table structure for table `lc_leave_requests`
--

CREATE TABLE `lc_leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_minimum_wage`
--

CREATE TABLE `lc_minimum_wage` (
  `id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `is_global` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `minimum_wage` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `effective_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lc_minimum_wage`
--

INSERT INTO `lc_minimum_wage` (`id`, `position_id`, `is_global`, `minimum_wage`, `status`, `effective_date`, `created_at`) VALUES
(7, NULL, 'Yes', 15000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(8, 315, 'No', 80000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(9, 316, 'No', 75000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(10, 317, 'No', 70000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(11, 318, 'No', 65000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(12, 319, 'No', 65000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(13, 320, 'No', 60000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(14, 321, 'No', 55000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(15, 322, 'No', 50000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(16, 325, 'No', 45000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(17, 326, 'No', 40000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(18, 327, 'No', 35000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(19, 328, 'No', 30000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10'),
(20, 399, 'No', 45000.00, 'Active', '2024-01-01', '2026-07-31 08:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `lc_notice_to_explain`
--

CREATE TABLE `lc_notice_to_explain` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) DEFAULT NULL,
  `nte_number` varchar(50) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `deadline_date` datetime DEFAULT NULL,
  `status` enum('Issued','Received','Responded','Escalated','Closed') DEFAULT 'Issued',
  `explanation` text DEFAULT NULL,
  `response_received` tinyint(1) NOT NULL DEFAULT 0,
  `response_date` datetime DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_notifications`
--

CREATE TABLE `lc_notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `notification_type` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `module` varchar(100) DEFAULT NULL,
  `sender_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_notifications`
--

INSERT INTO `lc_notifications` (`id`, `employee_id`, `user_id`, `recipient_id`, `email`, `title`, `message`, `type`, `notification_type`, `status`, `is_read`, `module`, `sender_email`, `created_at`, `updated_at`) VALUES
(1, 29, NULL, NULL, NULL, 'Corrective Action Required', 'Corrective Action Required – Failure to grant rest day premium.\n\nReference: CA-202607310003-0001\nEmployee: Eric T. Tiu\nIssue: Failure to grant rest day premium.\n\nRequired Action:\n• Review payroll records.\n• Adjust the employee\'s wage.\n• Compute any wage deficiency or back pay.\n• Update the payroll system.\n• Confirm completion through the HRMS.\n\nDue Date: July 24, 2026', 'warning', 'in_app', 'Unread', 0, 'corrective_actions', NULL, '2026-07-12 00:30:00', '2026-07-12 00:30:00'),
(2, 66, NULL, NULL, NULL, 'Corrective Action Verified', 'Corrective action CA-202607310003-0001 verified and closed. Notes: Verified and closed.', 'info', 'in_app', 'Read', 1, 'corrective_actions', NULL, '2026-07-30 08:45:00', '2026-07-30 08:45:00'),
(3, 29, NULL, NULL, NULL, 'Compliance Violation: Late PhilHealth Remittance', 'Employee Eric T. Tiu has an unremitted PhilHealth contribution for July 2026.\n\nAction Required:\n• Verify payroll deductions.\n• Remit missing contribution to PhilHealth.\n• Update compliance tracker.\n\nDue: August 5, 2026', 'warning', 'in_app', 'Viewed', 0, 'PhilHealth Monitoring', NULL, '2026-08-01 00:00:00', '2026-08-01 08:02:08'),
(4, 6, NULL, NULL, NULL, ' SSS Contribution Shortfall Detected', 'Employee Juan Dela Cruz (HR Manager) shows a discrepancy in SSS contributions for the month of June 2026.\n\nPlease review the remittance report and file the corrective action immediately.', 'danger', 'in_app', 'Unread', 0, 'SSS Monitoring', NULL, '2026-07-31 01:15:00', '2026-07-31 01:15:00'),
(5, 9, NULL, NULL, NULL, 'BIR Form 1601-C Due Soon', 'Reminder: BIR Form 1601-C (monthly tax return) is due on August 10, 2026.\n\nResponsible: Maria Santos (College President)\nEnsure the form is filed and copies are archived.', 'warning', 'in_app', 'Unread', 0, 'BIR Monitoring', NULL, '2026-07-31 23:30:00', '2026-07-31 23:30:00'),
(6, 10, NULL, NULL, NULL, 'Pag-IBIG Contribution Verified', 'Pag-IBIG contributions for Q2 2026 have been verified and are fully compliant.\n\nEmployee: Jose Reyes\nStatus: Resolved\nNo further action required.', 'success', 'in_app', 'Read', 1, 'Pag-IBIG Monitoring', NULL, '2026-07-30 06:20:00', '2026-07-30 06:20:00'),
(7, 29, NULL, NULL, NULL, 'Employment Contract Expiring', 'The employment contract for Eric T. Tiu (Lecturer) will expire on September 1, 2026.\n\nPlease initiate renewal or separation documentation at least 30 days before expiration.', 'info', 'in_app', 'Unread', 0, 'Employment Contracts', NULL, '2026-07-31 22:00:00', '2026-07-31 22:00:00'),
(8, 6, NULL, NULL, NULL, 'Corrective Action Update', 'Corrective action CA-202608010001 has been updated.\n\nEmployee: Juan Dela Cruz\nModule: SSS Monitoring\nStatus: In Progress\nReview the updated timeline and confirm completion.', 'info', 'in_app', 'Viewed', 0, 'Corrective Actions', NULL, '2026-08-01 02:45:00', '2026-08-01 08:01:53'),
(9, 9, NULL, NULL, NULL, 'System Notification: New Compliance Policy', 'A new labor law compliance policy has been published. All managers are required to review the updated guidelines.\n\nDocument: Labor Code Art. 93 Update\nEffective: August 15, 2026', 'primary', 'in_app', 'Viewed', 0, 'Compliance', NULL, '2026-08-01 03:00:00', '2026-08-01 06:49:36'),
(10, 10, NULL, NULL, NULL, 'Employee Document Pending Review', 'The exit acknowledgement document for Jose Reyes is awaiting HR review.\n\nPlease log in to the HRMS and approve or request revisions.', 'info', 'in_app', 'Read', 1, 'Employee Documents', NULL, '2026-07-29 08:30:00', '2026-07-29 08:30:00'),
(11, 29, NULL, NULL, NULL, 'Payroll Compliance Alert', 'A potential minimum wage violation was detected in the July 2026 payroll run for Region III.\n\nPlease review the payroll register and confirm adjustments before the next pay cycle.', 'danger', 'in_app', 'Unread', 0, 'Payroll', NULL, '2026-07-31 21:15:00', '2026-07-31 21:15:00'),
(12, 6, NULL, NULL, NULL, 'Audit Log: Compliance Review Completed', 'The scheduled compliance review for the Human Resources department has been completed.\n\nReviewer: Juan Dela Cruz\nOutcome: All items passed\nNext review: September 1, 2026', 'success', 'in_app', 'Read', 1, 'Compliance', NULL, '2026-07-31 09:00:00', '2026-07-31 09:00:00'),
(13, 29, NULL, NULL, NULL, 'Compliance Violation: Late PhilHealth Remittance', 'Employee Eric T. Tiu has an unremitted PhilHealth contribution for July 2026.\n\nAction Required:\n• Verify payroll deductions.\n• Remit missing contribution to PhilHealth.\n• Update compliance tracker.\n\nDue: August 5, 2026', 'warning', 'in_app', 'Unread', 0, 'PhilHealth Monitoring', NULL, '2026-08-01 00:00:00', '2026-08-01 00:00:00'),
(14, 6, NULL, NULL, NULL, ' SSS Contribution Shortfall Detected', 'Employee Juan Dela Cruz (HR Manager) shows a discrepancy in SSS contributions for the month of June 2026.\n\nPlease review the remittance report and file the corrective action immediately.', 'danger', 'in_app', 'Unread', 0, 'SSS Monitoring', NULL, '2026-07-31 01:15:00', '2026-07-31 01:15:00'),
(15, 9, NULL, NULL, NULL, 'BIR Form 1601-C Due Soon', 'Reminder: BIR Form 1601-C (monthly tax return) is due on August 10, 2026.\n\nResponsible: Maria Santos (College President)\nEnsure the form is filed and copies are archived.', 'warning', 'in_app', 'Unread', 0, 'BIR Monitoring', NULL, '2026-07-31 23:30:00', '2026-07-31 23:30:00'),
(16, 10, NULL, NULL, NULL, 'Pag-IBIG Contribution Verified', 'Pag-IBIG contributions for Q2 2026 have been verified and are fully compliant.\n\nEmployee: Jose Reyes\nStatus: Resolved\nNo further action required.', 'success', 'in_app', 'Read', 1, 'Pag-IBIG Monitoring', NULL, '2026-07-30 06:20:00', '2026-07-30 06:20:00'),
(17, 29, NULL, NULL, NULL, 'Employment Contract Expiring', 'The employment contract for Eric T. Tiu (Lecturer) will expire on September 1, 2026.\n\nPlease initiate renewal or separation documentation at least 30 days before expiration.', 'info', 'in_app', 'Unread', 0, 'Employment Contracts', NULL, '2026-07-31 22:00:00', '2026-07-31 22:00:00'),
(18, 6, NULL, NULL, NULL, 'Corrective Action Update', 'Corrective action CA-202608010001 has been updated.\n\nEmployee: Juan Dela Cruz\nModule: SSS Monitoring\nStatus: In Progress\nReview the updated timeline and confirm completion.', 'info', 'in_app', 'Unread', 0, 'Corrective Actions', NULL, '2026-08-01 02:45:00', '2026-08-01 02:45:00'),
(19, 9, NULL, NULL, NULL, 'System Notification: New Compliance Policy', 'A new labor law compliance policy has been published. All managers are required to review the updated guidelines.\n\nDocument: Labor Code Art. 93 Update\nEffective: August 15, 2026', 'primary', 'in_app', 'Viewed', 0, 'Compliance', NULL, '2026-08-01 03:00:00', '2026-08-01 06:51:44'),
(20, 10, NULL, NULL, NULL, 'Employee Document Pending Review', 'The exit acknowledgement document for Jose Reyes is awaiting HR review.\n\nPlease log in to the HRMS and approve or request revisions.', 'info', 'in_app', 'Read', 1, 'Employee Documents', NULL, '2026-07-29 08:30:00', '2026-07-29 08:30:00'),
(21, 29, NULL, NULL, NULL, 'Payroll Compliance Alert', 'A potential minimum wage violation was detected in the July 2026 payroll run for Region III.\n\nPlease review the payroll register and confirm adjustments before the next pay cycle.', 'danger', 'in_app', 'Viewed', 0, 'Payroll', NULL, '2026-07-31 21:15:00', '2026-08-01 08:02:00'),
(22, 6, NULL, NULL, NULL, 'Audit Log: Compliance Review Completed', 'The scheduled compliance review for the Human Resources department has been completed.\n\nReviewer: Juan Dela Cruz\nOutcome: All items passed\nNext review: September 1, 2026', 'success', 'in_app', 'Read', 1, 'Compliance', NULL, '2026-07-31 09:00:00', '2026-07-31 09:00:00'),
(23, 15, NULL, NULL, 'system', 'Report Submitted for Review: RPT-2026-0001', 'A new compliance report has been submitted by Legal & Compliance for your review.\n\nReport Code: RPT-2026-0001\nSubmitted By: compliance\nSubmitted At: Aug 03, 2026 18:51\nPlease review the report and approve or return it with remarks.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-03 16:51:36', '2026-08-03 16:51:36'),
(24, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 01:33:33', '2026-08-04 01:42:57'),
(25, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 01:53:45', '2026-08-04 01:53:45'),
(26, NULL, NULL, 0, NULL, 'Audit Report Draft', 'Audit #5 report is now Draft.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:01:34', '2026-08-04 02:01:34'),
(27, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:01:41', '2026-08-04 02:01:41'),
(28, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 02:06:15', '2026-08-04 02:08:11'),
(29, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:20:37', '2026-08-04 02:20:37'),
(30, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #4 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:20:42', '2026-08-04 02:20:42'),
(31, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:24:59', '2026-08-04 02:24:59'),
(32, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #2 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:27:32', '2026-08-04 02:27:32'),
(33, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #4 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:27:48', '2026-08-04 02:27:48'),
(34, NULL, NULL, 0, NULL, 'Report Scheduled for Directress', 'Sss Compliance Report has been scheduled for Monthly delivery to the School Directress.', 'success', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 02:30:29', '2026-08-04 02:31:22'),
(35, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:35:29', '2026-08-04 02:35:29'),
(36, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 02:37:56', '2026-08-04 02:37:56'),
(37, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:02:31', '2026-08-04 03:02:31'),
(38, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:02:40', '2026-08-04 03:02:40'),
(39, NULL, NULL, 0, NULL, 'Audit Finding Recorded', 'FND-2026-0006: Incomplete exit clearance', 'warning', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:04:30', '2026-08-04 03:04:30'),
(40, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:08:37', '2026-08-04 03:08:37'),
(41, NULL, NULL, 0, NULL, 'Audit Report Draft', 'Audit #5 report is now Draft.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:08:55', '2026-08-04 03:08:55'),
(42, NULL, NULL, 0, NULL, 'Audit Status Updated', 'Audit #5 is now In Progress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 03:09:20', '2026-08-04 03:09:20'),
(43, NULL, NULL, 0, NULL, 'Report Submitted', 'Employee Master List has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 04:11:28', '2026-08-04 04:11:28'),
(44, NULL, NULL, 0, NULL, 'Report Submitted', 'Employee Master List has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 04:11:56', '2026-08-04 04:11:56'),
(45, NULL, NULL, 0, NULL, 'Report Submitted', 'Recruitment Summary has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 04:55:49', '2026-08-04 04:55:49'),
(46, NULL, NULL, 0, NULL, 'Report Submitted', 'Employee Master List has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 05:34:17', '2026-08-04 05:34:17'),
(47, NULL, NULL, 0, NULL, 'Report Submitted', 'Policy Acknowledgement has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 05:34:54', '2026-08-04 05:34:54'),
(48, NULL, NULL, 0, NULL, 'Report Submitted', 'Policy Acknowledgement has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-04 05:35:13', '2026-08-04 05:35:13'),
(49, NULL, NULL, 0, NULL, 'Report Submitted', 'sss_compliance_report has been submitted to the Directress.', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 05:40:37', '2026-08-04 05:41:10'),
(50, NULL, NULL, 0, NULL, 'Report Scheduled', 'missing_registrations has been scheduled (Monthly).', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 05:40:44', '2026-08-04 05:41:04'),
(51, NULL, NULL, 0, NULL, 'Report Scheduled', 'document_expiration has been scheduled (Monthly).', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 05:40:53', '2026-08-04 05:41:00'),
(52, NULL, NULL, 0, NULL, 'Report Submitted', 'Vacancy Reports has been submitted to the Directress.', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-04 05:41:53', '2026-08-04 05:42:04'),
(53, NULL, NULL, 0, NULL, 'Report Submitted', 'Employee Master List has been submitted to the Directress.', 'info', NULL, NULL, 0, NULL, NULL, '2026-08-06 12:21:58', '2026-08-06 12:21:58'),
(54, NULL, NULL, 0, NULL, 'Report Submitted', 'Employee Master List has been submitted to the Directress.', 'info', NULL, 'Viewed', 0, NULL, NULL, '2026-08-06 12:22:00', '2026-08-06 12:28:06');

-- --------------------------------------------------------

--
-- Table structure for table `lc_pagibig_table`
--

CREATE TABLE `lc_pagibig_table` (
  `id` int(11) NOT NULL,
  `min_compensation` decimal(10,2) NOT NULL,
  `max_compensation` decimal(10,2) NOT NULL,
  `employee_rate` decimal(10,2) NOT NULL,
  `employer_rate` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `circular_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_pagibig_table`
--

INSERT INTO `lc_pagibig_table` (`id`, `min_compensation`, `max_compensation`, `employee_rate`, `employer_rate`, `effective_date`, `status`, `circular_number`) VALUES
(1, 1500.00, 1500.00, 15.00, 30.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(2, 1500.01, 2000.00, 40.00, 40.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(3, 2000.01, 2500.00, 50.00, 50.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(4, 2500.01, 3000.00, 60.00, 60.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(5, 3000.01, 3500.00, 70.00, 70.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(6, 3500.01, 4000.00, 80.00, 80.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(7, 4000.01, 4500.00, 90.00, 90.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(8, 4500.01, 5000.00, 100.00, 100.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(9, 5000.01, 5500.00, 110.00, 110.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(10, 5500.01, 6000.00, 120.00, 120.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(11, 6000.01, 6500.00, 130.00, 130.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(12, 6500.01, 7000.00, 140.00, 140.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(13, 7000.01, 7500.00, 150.00, 150.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(14, 7500.01, 8000.00, 160.00, 160.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(15, 8000.01, 8500.00, 170.00, 170.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(16, 8500.01, 9000.00, 180.00, 180.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(17, 9000.01, 9500.00, 190.00, 190.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(18, 9500.01, 10000.00, 200.00, 200.00, '2026-01-01', 'Active', 'HDMF Circular No. 460'),
(19, 10000.01, 99999999.99, 200.00, 200.00, '2026-01-01', 'Active', 'HDMF Circular No. 460');

-- --------------------------------------------------------

--
-- Table structure for table `lc_philhealth_table`
--

CREATE TABLE `lc_philhealth_table` (
  `id` int(11) NOT NULL,
  `min_compensation` decimal(12,2) NOT NULL,
  `max_compensation` decimal(12,2) DEFAULT NULL,
  `employee_rate` decimal(10,2) NOT NULL,
  `employer_rate` decimal(10,2) NOT NULL,
  `effective_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `circular_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_philhealth_table`
--

INSERT INTO `lc_philhealth_table` (`id`, `min_compensation`, `max_compensation`, `employee_rate`, `employer_rate`, `effective_date`, `status`, `circular_number`) VALUES
(1, 10000.00, 20000.00, 250.00, 500.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(2, 20000.01, 30000.00, 500.00, 750.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(3, 30000.01, 40000.00, 750.00, 1000.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(4, 40000.01, 50000.00, 1000.00, 1250.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(5, 50000.01, 60000.00, 1250.00, 1500.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(6, 60000.01, 70000.00, 1500.00, 1750.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(7, 70000.01, 80000.00, 1750.00, 2000.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(8, 80000.01, 90000.00, 2000.00, 2250.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(9, 90000.01, 99999.99, 2250.00, 2500.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002'),
(10, 100000.00, 99999999.99, 2500.00, 2500.00, '2025-01-01', 'Active', 'PhilHealth Advisory No. 2025-0002');

-- --------------------------------------------------------

--
-- Table structure for table `lc_philippine_laws`
--

CREATE TABLE `lc_philippine_laws` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` enum('Active','Amended','Repealed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_philippine_laws`
--

INSERT INTO `lc_philippine_laws` (`id`, `code`, `title`, `description`, `category`, `effective_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PD 442', 'Labor Code of the Philippines', 'Promulgated on May 1, 1974, P.D. 442 (The Labor Code of the Philippines) is the primary law governing employment practices, labor relations, worker protection, and labor standards across the country. It establishes the rights of workers, the duties of employers, and the framework for state intervention in labor disputes.\n\nCore Books and Pillars of the Labor Code\nThe Labor Code is divided into seven major divisions (Books) covering key aspects of employment:\n\nBook I – Pre-Employment (Articles 12–42):\n\nRegulates recruitment and placement of local and overseas workers (OFWs).\n\nGoverns the employment of non-resident aliens and foreign nationals.\n\nBook II – Human Resources Development Program (Articles 43–81):\n\nOutlines national policies on manpower development, technical education, and apprenticeship programs.\n\nBook III – Conditions of Employment (Articles 82–155):\n\nWorking Hours & Rest Periods: Standard 8-hour workday, weekly rest days, and mandatory meal periods.\n\nOvertime & Night Shift Differential: Premium rates for hours worked beyond 8 hours and night shifts (10:00 PM to 6:00 AM).\n\nHolidays & Premium Pay: Pay rules for regular holidays, special non-working days, and rest-day work.\n\nService Incentive Leave (SIL): Entitlement to 5 days of paid annual leave after one year of service.\n\nWages & Minimum Wage Compliance: Rules on wage protection, prohibition of unlawful deductions, and regional minimum wage orders.\n\nBook IV – Health, Safety, and Social Welfare Benefits (Articles 156–210):\n\nMandatory medical and dental services in workplaces based on company size and risk level.\n\nEstablishes occupational health and safety standards and the Employees\' Compensation Program (ECP).\n\nBook V – Labor Relations (Articles 211–277):\n\nGuarantees the right to self-organization, joining labor unions, and collective bargaining agreements (CBAs).\n\nRegulates strikes, lockouts, and unfair labor practices (ULP).\n\nEstablishes the role and jurisdiction of the National Labor Relations Commission (NLRC).\n\nBook VI – Post-Employment (Articles 278–287):\n\nSecurity of Tenure: Employees cannot be dismissed without just cause or authorized cause under due process.\n\nTypes of Employment: Rules governing regular, probationary, casual, project-based, seasonal, and fixed-term employment.\n\nTermination & Separation Pay: Distinguishes between just causes (e.g., serious misconduct, neglect) and authorized causes (e.g., redundancy, retrenchment, disease), setting compulsory separation pay formulas where required.\n\nRetirement Pay: Establishes statutory minimum retirement benefits upon reaching optional (60) or mandatory (65) retirement age.\n\nBook VII – Transitory and Final Provisions (Articles 288–302):\n\nCovers penalties for violations, prescription periods for filing labor claims, and administrative rules.', 'Labor Standards', '1974-05-01', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:35:19'),
(2, 'RA 11058', 'Occupational Safety and Health (OSH) Law', 'Signed into law on August 17, 2018, RA 11058 (An Act Strengthening Compliance with Occupational Safety and Health Standards and Providing Penalties for Violations Thereof) mandates that employers provide a safe and healthy workplace for all employees. It applies to all establishments, projects, sites, and places where work is performed in the Philippines across both private and public sectors.\n\nCore Duties and Obligations of Employers\nProvision of Free Personal Protective Equipment (PPE): Employers must provide appropriate PPE (e.g., hard hats, safety shoes, gloves, masks) free of charge to all workers facing hazards. Employers are strictly prohibited from deducting PPE costs from employee wages.\n\nDesignation of OSH Personnel: Establishments must deploy certified OSH personnel (Safety Officers and First Aiders) based on company size and risk classification (low, medium, or high risk).\n\nMandatory OSH Orientation and Training: All employees must undergo mandatory safety orientations and OSH training to understand workplace hazards and emergency procedures.\n\nEstablishment of OSH Programs: Companies must formulate, implement, and maintain a written Occupational Safety and Health Program aligned with Department of Labor and Employment (DOLE) regulations.\n\nReporting Accidents: Work-related accidents, injuries, or fatalities must be reported to DOLE within mandatory statutory timelines.\n\nKey Rights of Workers Under the Law\nRight to Know: Employees have the right to be fully informed about all physical, chemical, or biological hazards in their work environment.\n\nRight to Refuse Unsafe Work: A worker has the legal right to refuse work without fear of retaliation or penalty if an imminent danger to life or safety exists in the workplace.\n\nRight to Report Accidents: Workers have the right to report unsafe conditions, health risks, or accidents to DOLE or relevant authorities without facing disciplinary action or discrimination from employers.\n\nAdministrative Fines and Penalties\nUnlike previous labor standards where compliance was primarily advisory, RA 11058 introduces strict financial penalties for non-compliance:\n\nAdministrative Fines: DOLE can impose daily fines of up to ₱100,000 per day for willful failure or refusal to comply with OSH standards until the violation is fully rectified.\n\nWork Stoppage Orders (WSO): DOLE may order the immediate stoppage of work or operations if an imminent danger poses a severe threat to worker safety.', 'OSH', '2018-05-30', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:46:36'),
(3, 'RA 10173', 'Data Privacy Act of 2012', 'Enacted on August 15, 2012, Republic Act No. 10173 is a comprehensive privacy law in the Philippines designed to protect the fundamental human right of privacy while ensuring the free flow of information to promote innovation and growth. It regulates the collection, processing, storage, and sharing of personal data by both private entities and government agencies. The law created the National Privacy Commission (NPC) to administer and enforce its provisions.\n\nCore Data Classifications\nPersonal Information\nRefers to any information, whether recorded in a material form or not, from which the identity of an individual is apparent or can be reasonably and directly ascertained by the entity holding the information, or when put together with other information would directly and certainly identify an individual (such as name, contact number, home address, or email address).\n\nSensitive Personal Information\nRefers to personal information that receives a higher level of legal protection, including:\n\nInformation about an individual\'s race, ethnic origin, marital status, age, color, and religious, philosophical, or political affiliations.\n\nInformation about an individual\'s health, education, genetic or sexual life, or to any proceeding for any offense committed or alleged to have been committed by such person.\n\nInformation issued by government agencies peculiar to an individual, including social security numbers, health records, licenses, tax returns, and government ID numbers.\n\nPrivileged Information\nRefers to any and all forms of data which under the Rules of Court and other pertinent laws constitute privileged communication, such as attorney-client or doctor-patient communications.\n\nCore Principles of Data Processing\nOrganizations must adhere to three central principles when handling personal data:\n\nTransparency\nData subjects must be aware of the nature, purpose, and extent of the processing of their personal data, including their rights as data subjects.\n\nLegitimate Purpose\nThe processing of information must be compatible with a declared and specified purpose which is not contrary to law, morals, or public policy.\n\nProportionality\nThe processing of information must be adequate, relevant, suitable, necessary, and not excessive in relation to a declared and specified purpose.\n\nRights of Data Subjects\nUnder the law, individuals whose personal data is processed are granted specific rights:\n\nRight to be informed whether their personal data is being or has been processed.\n\nRight to access their personal data held by an organization.\n\nRight to object to the processing of their personal data, including processing for direct marketing or automated profiling.\n\nRight to erasure or blocking of inaccurate, incomplete, or unlawfully obtained personal data.\n\nRight to damages sustained due to inaccurate, incomplete, outdated, false, unlawfully obtained, or unauthorized use of personal data.\n\nRight to file a complaint with the National Privacy Commission.\n\nRight to data portability to obtain a copy of data in an electronic or structured format.\n\nHR and Workplace Compliance Standards\nFor Human Resources departments and employers, compliance with RA 10173 involves several key responsibilities:\n\nEmployee Consent and Privacy Notices\nEmployers must provide clear privacy notices informing job applicants and employees about how their personal data will be collected, used, stored, and shared. Explicit consent should be obtained where necessary.\n\nProtection of 201 Files and Payroll Data\nHR records containing sensitive information (such as SSS numbers, PhilHealth records, Pag-IBIG IDs, TINs, salary details, and medical history) must be secured with physical and technical access controls. Access must be restricted to authorized personnel only.\n\nBackground Checks and Performance Reviews\nConducting background checks or sharing employee data with third-party background screeners, HMO providers, or payroll processors requires valid legal grounds, employee notification, and data sharing agreements.\n\nData Breach Management\nOrganizations must implement security measures to prevent data breaches. In the event of a security breach involving sensitive personal information, the National Privacy Commission and affected data subjects must be notified within 72 hours of knowledge of the breach.', 'Data Privacy', '2012-08-10', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:43:20'),
(4, 'RA 7877', 'Anti-Sexual Harassment Act', 'Enacted on March 2, 1995, RA 7877 is the foundational Philippine law that declares all forms of sexual harassment in employment, education, and training environments unlawful. It focuses specifically on situations where authority, influence, or moral ascendancy is present, protecting employees, applicants, students, and trainees from sexual coercion or intimidation.\n\nKey Definition and Conditions for Sexual Harassment\nUnder RA 7877, work-related or education-related sexual harassment is committed by an employer, employee, manager, supervisor, agent, teacher, instructor, professor, coach, or any person who holds authority, influence, or moral ascendancy over another, when:\n\nIn a Work-Related Environment:\n\nThe sexual favor is made a condition in hiring, employment, re-employment, or continued employment, or in granting favorable compensation, terms, conditions, promotions, or privileges.\n\nRefusal to grant the sexual favor results in limiting, segregating, or classifying the employee which in any way discriminates against or diminishes their employment opportunities.\n\nThe acts impair the employee’s rights or privileges under labor laws or create an intimidating, hostile, or offensive environment for the employee.\n\nIn an Education or Training Environment:\n\nThe sexual favor is made a condition to the giving of a passing grade, grant of honors, scholarship, stipend, or allowance.\n\nThe sexual favor is a condition for admission, re-admission, or continued enrollment in an institution.\n\nThe sexual advances result in an intimidating, hostile, or offensive environment for the student or trainee.\n\nEmployer and Management Obligations\nDuty to Prevent and Deter: Employers and heads of offices or institutions have a affirmative legal duty to prevent or deter the commission of acts of sexual harassment.\n\nEstablishment of CODI: Employers are required to create a Committee on Decorum and Investigation (CODI) composed of representatives from management, employees, and unions (or student representatives in academic settings) to conduct hearings and investigate complaints.\n\nPromulgation of Rules: Organizations must promulgate rules and regulations outlining proper workplace conduct and clear administrative procedure for investigating cases.\n\nEmployer Liability: An employer or head of office can be held solidarily liable for damages if they are informed of acts of sexual harassment and fail to take immediate and proper action.\n\nPenalties for Violations\nAny person who violates the provisions of RA 7877 faces:\n\nFines: Ranging from ₱10,000 to ₱20,000.\n\nImprisonment: Ranging from one (1) month to six (6) months, or both at the discretion of the court.\n\nAdministrative Sanctions: Independent of criminal prosecution, administrative proceedings may lead to suspension or termination of employment.', 'Workplace Conduct', '1995-03-02', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:52:31'),
(5, 'RA 11313', 'Safe Spaces Act', 'Signed into law on April 17, 2019, and taking effect on July 14, 2019, RA 11313 expands the legal definitions of gender-based sexual harassment in the Philippines. Unlike the older Anti-Sexual Harassment Act of 1995 (RA 7877)—which focused primarily on authority-based harassment in workplace and academic settings—the Safe Spaces Act covers all forms of gender-based sexual harassment committed in public spaces, educational institutions, workplaces, and online platforms, regardless of whether a supervisor-subordinate relationship exists.\n\nKey Forms of Gender-Based Sexual Harassment Covered\nIn Public Spaces:\n\nCatcalling, wolf-whistling, persistent uninvited comments, unwanted sexual remarks, or slurs based on sex, gender, or sexual orientation.\n\nStalking, leering, intrusive gazing, unwanted touching, gropery, or public exposure.\n\nIn the Workplace:\n\nUnwanted sexual advances, requests for sexual favors, or conduct of a sexual nature that creates an intimidating, hostile, or humiliating work environment.\n\nPeer-to-peer or subordinate-to-superior harassment (removing the requirement that the offender must hold authority or influence over the victim).\n\nIn Online Spaces (Cyber Sexual Harassment):\n\nSending unsolicited sexually explicit photos, videos, or messages.\n\nCyberstalking, impersonation, or publishing private sexual details or media without consent.\n\nEmployer and HR Compliance Mandates\nTo ensure a safe environment, employers are required to take proactive compliance steps under RA 11313:\n\nPolicy Formulation: Promulgate and disseminate clear company policies prohibiting gender-based sexual harassment in the workplace and during work-related events.\n\nCommittee on Decorum and Investigation (CODI): Establish an internal CODI (or designated handling body) to investigate complaints, conduct hearings, and recommend administrative actions.\n\nMandatory Training: Conduct regular awareness programs, orientations, and anti-harassment training for all staff and management.\n\nReporting Mechanisms: Create accessible, secure, and confidential grievance channels for employees to lodge complaints without fear of retaliation or penalty.\n\nPrompt Administrative Action: Take immediate investigative action upon receiving a complaint and inform appropriate government agencies (such as DOLE) as required.\n\nPenalties for Non-Compliance\nEmployers who fail to fulfill their duties under RA 11313 (such as failing to create a CODI, failing to investigate complaints, or failing to act on reports) face administrative fines imposed by the Department of Labor and Employment (DOLE) or relevant regulatory authorities. Offending individuals face criminal liability, fines, and imprisonment depending on the severity of the act.', 'Workplace Conduct', '2019-07-14', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:50:29'),
(6, 'RA 11210', 'Expanded Maternity Leave Law', 'Signed into law on February 20, 2019, RA 11210 significantly expanded maternity leave benefits for female workers in the Philippines across both public and private sectors, as well as the informal economy. The law increased the paid maternity leave duration from the previous 60–78 days to 105 days, providing working mothers with adequate time to recover from childbirth, care for their newborns, and support early childhood development.\n\nDuration of Maternity Leave\nStandard Maternity Leave: 105 days with full pay for live childbirth, regardless of whether the delivery was via normal spontaneous delivery (NSD) or caesarean section.\n\nAdditional Leave for Solo Parents: Qualified solo mothers under the Solo Parents\' Welfare Act receive an additional 15 days of paid leave, bringing their total maternity leave to 120 days with full pay.\n\nMiscarriage or Emergency Termination of Pregnancy (ETP): 60 days with full pay.\n\nOptional Unpaid Extension: Female workers have the option to extend their leave for an additional 30 days without pay, provided they notify their employer in writing at least 45 days prior to the end of their paid maternity leave.\n\nAllocation to Father or Alternate Caregiver\nFemale employees entitled to 105 days of maternity leave may elect to allocate up to seven (7) days of their leave to:\n\nThe child\'s father (whether married to the mother or not).\n\nAn alternate caregiver, such as a relative within the fourth degree of consanguinity or a current partner living in the same household.\n\nNote: This allocated leave is independent of and in addition to the father\'s statutory 7-day leave under the Paternity Leave Act (RA 8187).\n\nQualification and Coverage\nUniversal Coverage: Applies to all covered female workers in the private sector, government sector, informal economy, voluntary SSS members, and female national athletes.\n\nNo Cap on Pregnancy Frequency: Unlike the previous law (which capped maternity benefits at the first four deliveries), RA 11210 applies to every instance of pregnancy, miscarriage, or ETP, regardless of frequency.\n\nSSS Qualifying Condition: For private sector employees and voluntary members, the employee must have paid at least three (3) monthly SSS contributions within the 12-month period immediately preceding the semester of childbirth or miscarriage.\n\nSalary Differential and Payment\nFull Pay Guarantee: The benefit consists of full pay, which includes basic salary and regular allowances.\n\nEmployer Salary Differential: In the private sector, SSS pays the statutory SSS maternity benefit, while the employer is required to pay the salary differential (the difference between full salary and SSS maternity benefits), unless the employer qualifies for specific exemptions under DOLE rules (e.g., micro-enterprises employing not more than 10 workers).\n\nJob Security and Anti-Discrimination\nSecurity of Tenure: Maternity leave cannot be used as a ground for dismissal, demotion, or discrimination in employment.\n\nReturn to Work: Upon completion of maternity leave, the employee is guaranteed restoration to her former position or an equivalent position with equal pay, benefits, and seniority rights.', 'Leave Policy', '2019-02-21', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:48:38'),
(7, 'RA 8187', 'Paternity Leave Act', 'Republic Act No. 8187 is a Philippine labor law enacted to grant every married male employee in both the private and public sectors a period of paid paternity leave. The law ensures that working fathers can support their lawful wives during recovery and assist in caring for the newborn child or miscarried pregnancy without losing income.\n\nKey Provisions & Entitlements\nDuration: 7 calendar days of leave with full pay.\n\nFull Compensation: Refers to basic salary plus regular allowances.\n\nFrequency Limit: Valid for the first four (4) deliveries or miscarriages of the employee’s legitimate spouse.\n\nEffective Date: March 23, 1996.\n\nEligibility Requirements\nTo qualify for paternity leave under RA 8187, the male employee must meet the following criteria:\n\nEmployment Status: Active employee at the time of delivery or miscarriage.\n\nMarital Status: Lawfully married to the mother of the child.\n\nCohabitation: Living under the same roof with his spouse (unless temporary physical separation is required due to work or occupation).\n\nDelivery Limit: The birth or miscarriage is among the first 4 deliveries/miscarriages with his current legal spouse.\n\nNotice: Has notified his employer of the expected birth within a reasonable time frame.\n\nImportant HR & Operational Rules\nNon-Cumulative & Non-Convertible: Unused paternity leave days cannot be carried over to subsequent births and cannot be converted to cash.\n\nUsage Window: Leave must be taken during or immediately after the delivery/miscarriage, usually required within 60 days from the birth or miscarriage date.\n\nRequired Documentation for Filing:\n\nMarriage Certificate\n\nBirth Certificate or Medical/Death Certificate (for miscarriage/stillbirth)\n\nInteraction with the Expanded Maternity Leave Law (RA 11210)\nUnder RA 11210 (Expanded Maternity Leave Law):\n\nA female employee entitled to maternity leave can opt to allocate up to 7 days of her 105-day paid maternity leave to the child’s father.\n\nThis allocated leave is separate from and in addition to the 7-day paternity leave under RA 8187.\n\nAs a result, an eligible married father can receive a total of up to 14 days of paid leave if his spouse allocates 7 days of her maternity leave to him.', 'Leave Policy', '1996-03-23', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:56:13'),
(8, 'RA 11861', 'Solo Parents\' Welfare Act', 'Enacted in June 2022 (and lapsing into law to amend the older Solo Parents\' Welfare Act of 2000 / RA 8972), RA 11861 expands the benefits, financial privileges, workplace protections, and social services provided to solo parents and their children in the Philippines. It recognizes the unique financial, emotional, and logistical challenges faced by single heads of households.\n\nExpanded Definition of a Solo Parent\nThe law broadened who qualifies as a solo parent, including:\n\nA parent who gives birth as a result of rape or sexual offenses.\n\nA parent left alone due to the death, incarceration, physical/mental incapacity, legal separation, annulment, or abandonment by a spouse for at least 6 months (reduced from 1 year under the old law).\n\nUnmarried mothers or fathers who keep and rear their child or children.\n\nAny legal guardian, adoptive parent, or foster parent who assumes sole responsibility for the child.\n\nA spouse or family member of an Overseas Filipino Worker (OFW) belonging to the low-income/semi-skilled category who has been away continuously for at least 12 months.\n\nKey Workplace Benefits & HR Compliance Mandates\nExpanded Solo Parent Leave:\n\nQualified solo parent employees are entitled to seven (7) days of paid leave per year.\n\nReduced Tenure Requirement: The length of service required to avail of the leave was reduced from 1 year to six (6) months of continuous service with the employer.\n\nFlexible Work Schedules:\n\nEmployers are required to provide flexible working arrangements (flexitime) for solo parent employees, provided it does not disrupt company operations or productivity.\n\nProtection Against Workplace Discrimination:\n\nEmployers are strictly prohibited from discriminating against any employee with respect to terms, conditions, hiring, promotion, or compensation solely on account of their status as a solo parent.\n\nPriority in Telecommuting/Work-from-Home:\n\nSolo parents are given statutory priority when employers implement flexible or telecommuting work arrangements under the Telecommuting Act (RA 11165).\n\nFinancial Subsidies and Tax Discounts\nMonthly Cash Allowance: Solo parents earning minimum wage or below are entitled to a monthly cash subsidy of ₱1,000 per month from their local government unit (LGU).\n\n10% Discount and VAT Exemption: Solo parents earning an annual income of ₱250,000 or less receive a 10% discount and VAT exemption on essential goods for their child (such as infant milk, baby food, diapers, and prescribed medicines) up until the child reaches 6 years of age.\n\nEducational Scholarships: Priority allocation in government scholarship programs, technical education (TESDA), and financial assistance for qualified solo parents and their dependent children.\n\nAutomatic PhilHealth Coverage: Automatic inclusion in the National Health Insurance Program, with premiums paid by the national government.', 'Employee Benefits', '2022-12-22', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:51:29'),
(9, 'RA 9710', 'Magna Carta of Women', 'Republic Act No. 9710 (enacted August 14, 2009) is a comprehensive human rights law that seeks to eliminate discrimination against women by recognizing, protecting, fulfilling, and promoting the rights of Filipino women, especially those in marginalized sectors.\n\nIn the corporate and workplace setting, it serves as the foundational law for gender equality, non-discrimination in employment, and specialized gender-related healthcare leaves.\n\nKey Workplace Rights & Non-Discrimination Mandates\nEqual Opportunities in Employment:\n\nProhibits discrimination in hiring, promotion, training, and pay based on gender.\n\nProhibits firing, suspending, or penalizing a female employee on account of pregnancy or marital status.\n\nSafe & Gender-Fair Work Environment:\n\nMandates employers to ensure workplaces are free from sexual harassment, gender-based violence, and hostile work conditions (reinforcing RA 7877 and RA 11313 / Safe Spaces Act).\n\nGender Mainstreaming & Facilities:\n\nEncourages gender-responsive policies, equal access to leadership roles, and gender-fair language in official documentation and company communication.\n\nSpecial Leave Benefit for Women (MCW Leave)\nOne of the most critical HR compliance requirements under RA 9710 (Section 18) is the Special Leave Benefit for Women:\n\nEntitlement: Up to two (2) months (60 calendar days) of fully paid leave following surgery caused by gynecological disorders.\n\nQualifying Conditions:\n\nThe female employee has rendered at least 6 months of continuous aggregate service with the employer within the last 12 months prior to surgery.\n\nShe has undergone surgery due to gynecological disorders (e.g., myomectomy, hysterectomy, ovarian cystectomy, or surgeries for endo-cervical polyps, pelvic inflammatory diseases, etc.) certified by a competent physician.\n\nUsage & Pay: Fully paid based on basic salary plus standard allowances. It is non-cumulative and non-convertible to cash if unutilized.\n\nRelation to Other Leaves: This benefit is in addition to standard sick leaves, vacation leaves, and maternity leave benefits.\n\nRequired Documentation for MCW Special Leave\nWhen an employee applies for the MCW Special Leave, HR should require:\n\nMedical certificate issued by an attending gynecologist detailing the diagnosis, procedure performed, and recommended recovery period.\n\nClinical summary or operative technique report from the hospital/clinic.\n\nApproved Leave Application Form specifying MCW Leave.\n\nHR & Operational Compliance Checklist\nPolicy Alignment: Ensure company leave policies (e.g., POL-013) clearly define and integrate the 60-day MCW Special Leave Benefit alongside statutory maternity and sick leaves.\n\nNon-Discrimination in Evaluations: Audit performance appraisal systems to ensure female employees taking MCW leave or maternity leave are not penalized in performance ratings or promotion tracks.\n\nSupportive Facilities: Provide adequate workplace facilities (such as lactation stations under RA 10028) and ensure non-discriminatory health coverage.', 'Workplace Conduct', '2009-08-14', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:57:42'),
(10, 'RA 10911', 'Anti-Age Discrimination in Employment Act', 'Enacted on July 21, 2016, Republic Act No. 10911, or the Anti-Age Discrimination in Employment Act, is a landmark Philippine labor law designed to promote employment of individuals based on their abilities, skills, and qualifications rather than their age. It prohibits age-based discrimination in recruitment, hiring, compensation, promotion, training, and termination across both the private and public sectors.\n\nKey Prohibitions Under the Law\nIt is unlawful for an employer, recruitment agency, or labor organization to engage in any of the following acts:\n\nIn Recruitment and Hiring\n\nPrinting, publishing, or posting job advertisements or notices that state age requirements or preferences.\n\nRequiring applicants to declare their age or date of birth during the initial application process.\n\nRejecting an employment application, refusing to candidate-screen, or declining to hire solely because of an individual\'s age.\n\nIn Terms and Conditions of Employment\n\nPaying lower compensation or providing fewer benefits to an employee on account of age.\n\nDenying promotions, training opportunities, or career advancements due to age.\n\nImposing age limits in labor management agreements or company policies without legal justification.\n\nIn Retirement and Termination\n\nForcing an employee to retire early or laying off workers based on age prior to reaching mandatory retirement ages established by law or company policy.\n\nDismissing or terminating employment solely due to an employee\'s age.\n\nExceptions (Bona Fide Occupational Qualifications)\nAge-based distinctions or limitations are permitted only under specific, justifiable conditions:\n\nAge is a genuine occupational qualification (BFOQ) necessary for the normal operation of a particular business or performance of a specific job (e.g., specific public safety or physical performance requirements).\n\nThe intent is to observe the terms of a bona fide seniority system or employee benefit plan (e.g., retirement plans).\n\nThe action aims to fulfill state-sponsored job creation or employment programs for specific demographic groups.\n\nHR and Compliance Mandates\nCompany Policy Review: HR departments must remove age limits from job postings, hiring forms, and internal promotion frameworks.\n\nPenalties for Non-Compliance: Violators face fines ranging from PHP 50,000 to PHP 500,000, imprisonment ranging from 3 months to 2 years, or both.', 'Workplace Conduct', '2016-07-24', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:44:40'),
(11, 'RA 11199', 'Social Security Act', 'Signed into law on February 7, 2019 (with an effective date retroactively applied to late 2018/early 2019 implementation), RA 11199 repealed the older Social Security Law (RA 8282) to strengthen and modernize the Social Security System (SSS). The law expanded SSS coverage, introduced mandatory social security for Overseas Filipino Workers (OFWs), updated contribution rates and salary caps, and created new benefit programs such as Unemployment Insurance.\n\nCoverage and Compulsory Membership\nPrivate Sector Workers: Compulsory coverage for all employees in the private sector, regardless of status (regular, probationary, casual, temporary), including domestic workers (Kasambahays).\n\nSelf-Employed and Voluntary Members: Covers self-employed individuals, freelancers, gig economy workers, and voluntary members.\n\nOverseas Filipino Workers (OFWs): Mandates compulsory coverage for all sea-based and land-based OFWs.\n\nCore Benefits Provided Under SSS\nSickness Benefit: A daily cash allowance granted to a member who is unable to work due to sickness or injury.\n\nMaternity Benefit: A daily cash allowance for female members who have given birth or suffered a miscarriage (integrated with the Expanded Maternity Leave Law / RA 11210).\n\nDisability Benefit: Monthly pension or lump-sum payment granted to a member who becomes partially or totally disabled.\n\nRetirement Benefit: Monthly pension (or lump-sum amount) paid to a member who has reached retirement age (optional at 60, mandatory at 65) and met minimum contribution requirements (at least 120 monthly contributions for a lifetime pension).\n\nDeath and Funeral Benefits: Monthly pension or cash payout granted to primary beneficiaries upon the death of a member, alongside a funeral benefit to assist with burial costs.\n\nUnemployment Insurance / Involuntary Separation Benefit: Introduced under RA 11199, this provides a cash allowance (50% of the average monthly salary credit for up to two months) to covered employees involuntarily separated from work due to authorized causes (e.g., redundancy, retrenchment, closure, or disease).\n\nEmployer Obligations and Compliance\nMandatory Registration: Employers must register their business and all covered employees with SSS within 30 days from the start of employment.\n\nRemittance of Contributions: Employers are required to deduct the employee’s share from their salary and remit it, together with the employer\'s share, to SSS on or before the monthly deadline.\n\nPenalties for Non-Remittance: Failure or refusal to register employees or remit collected SSS contributions carries criminal liability, interest penalties (2% per month), and potential imprisonment for non-compliant company officers.', 'Government Contributions', '2018-12-05', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:47:36'),
(12, 'RA 11223', 'Universal Health Care Act / PhilHealth', 'Signed into law on February 20, 2019, RA 11223 guarantees all Filipino citizens automatic inclusion in the National Health Insurance Program (NHIP) administered by the Philippine Health Insurance Corporation (PhilHealth). The law ensures equitable access to quality and affordable health care services, protecting individuals and families from financial hardship due to medical expenses.\n\nCore Membership Categories\nDirect Contributors:\n\nIndividuals who have the capacity to pay health insurance premiums.\n\nIncludes employed workers (in both private and public sectors), self-employed individuals, professional practitioners, migrant workers (OFWs), and voluntary members.\n\nIndirect Contributors:\n\nIndividuals whose premiums are subsidized by the national government from tax revenues.\n\nIncludes indigent families, senior citizens, persons with disabilities (PWDs), and beneficiaries under the Conditional Cash Transfer (4Ps) program.\n\nImpact on Employers and HR Compliance\nMandatory Enrollment and Remittance:\nEmployers are legally required to register all employees with PhilHealth and regularly remit monthly premium contributions, split equally between the employer and employee.\n\nPhased Increase in Contribution Rates:\nThe law scheduled a gradual increase in the PhilHealth contribution rate (scaling up to 5.0%) along with adjustments to the monthly salary ceiling to ensure the long-term sustainability of the health fund.\n\nCertificate of PhilHealth Clearance:\nCompliance with PhilHealth reporting and remittances is required for corporate reporting, business permit renewals, and labor audits conducted by the Department of Labor and Employment (DOLE).\n\nKey Health Benefits and Coverage\nPrimary Care Benefit (PhilHealth KonSulta):\nGrants all citizens access to comprehensive outpatient services, health screenings, initial consultations, laboratory tests, and essential medicines.\n\nInpatient and Outpatient Hospitalization:\nProvides coverage for hospital room charges, medical procedures, surgical operations, and doctor fees through PhilHealth case rates.\n\nZ Benefit Packages:\nOffers specialized financial protection for catastrophic and severe illnesses (such as cancer treatments, kidney transplants, and major heart surgeries).', 'Government Contributions', '2019-02-20', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:49:35'),
(13, 'RA 9679', 'Pag-IBIG Fund Law', 'Republic Act No. 9679 (enacted July 21, 2009, effective November 13, 2009) repealed Presidential Decree No. 1754 and reconstituted the Home Development Mutual Fund (HDMF), popularly known as the Pag-IBIG Fund.It established a national mandatory savings system for Filipino workers to provide affordable housing financing, tax-exempt mutual savings programs, and short-term loans.Mandatory CoverageUnder RA 9679, Pag-IBIG coverage is mandatory for:Private Sector Employees: All employees covered by the Social Security System (SSS), regardless of employment status (permanent, temporary, contractual, or casual).Government Sector Employees: All employees covered by the Government Service Insurance System (GSIS), including members of the Armed Forces of the Philippines (AFP), PNP, BFP, and BJMP.Overseas Filipino Workers (OFWs): All land-based and sea-based Filipino workers abroad.Self-Employed Individuals: Self-employed persons with an income of at least ₱1,000 per month.Kasambahays: Domestic workers as mandated by the Kasambahay Law (RA 10361).Contribution Rates & RulesContributions are shared between the employee and the employer based on the employee\'s monthly basic salary:Monthly Compensation RangeEmployee ShareEmployer Share₱1,500 and below1%2%Over ₱1,5002%2%Maximum Monthly Compensation Cap: Under the standard law, the statutory salary cap was set at ₱5,000 (yielding a maximum standard contribution of ₱100 employee + ₱100 employer). However, mandatory maximum contribution rates updated effective February 2024 set the maximum monthly fund salary (MFS) cap to ₱10,000 (resulting in a ₱200 employee share and ₱200 employer share, totaling ₱400 monthly).Voluntary Upward Contributions: Employees can choose to contribute higher than the mandatory monthly limit to grow their savings and dividend earnings faster.Key Benefits & ProgramsSavings & Dividends (Regular Savings):Member contributions earn annual tax-free dividends.Accumulated total accumulated value (TAV) can be withdrawn upon maturity (20 years of membership or 240 monthly contributions), retirement (age 60 optional, age 65 mandatory), total disability, permanent departure from the Philippines, or death.MP2 (Modified Pag-IBIG 2) Savings:A voluntary 5-year savings program for active Pag-IBIG members offering higher dividend rates than the regular savings program, fully tax-free and government-guaranteed.Housing Loans:Grants eligible members long-term financing (up to 30 years) for house lot purchase, residential construction, home improvement, or refinancing at competitive interest rates.Short-Term & Calamity Loans:Multi-Purpose Loan (MPL): Allows members to borrow up to 80% of their accumulated Pag-IBIG savings for educational, medical, or livelihood needs.Calamity Loan: Accessible to members residing in areas declared under a state of calamity.HR & Employer Compliance ResponsibilitiesEmployer Registration: All employers operating in the Philippines are required to register with the Pag-IBIG Fund and obtain an Employer ID Number.Employee Registration: Employers must ensure all newly hired eligible employees are registered and their Pag-IBIG MID (Membership ID) numbers are updated in company records.Deductions & Remittance:Deduct the employee’s share from their monthly compensation.Pay the corresponding employer share.Remit total contributions to Pag-IBIG on or before the designated monthly deadline (typically based on the last digit of the employer\'s Pag-IBIG number or standard calendar schedules).Reporting: Submit monthly remittance forms/files (PDRF / Billing schedules) through Pag-IBIG’s online employer portal.Penalties for Non-Compliance: Failure or refusal to register employees, deduct contributions, or remit collected funds subjects employers to penalty surcharges (typically 3% per month of delay) and legal liabilities under the law.', 'Government Contributions', '2009-11-13', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:57:02'),
(14, 'NIRC', 'National Internal Revenue Code (BIR Tax Laws)', 'The National Internal Revenue Code of 1997 (Republic Act No. 8424), commonly referred to as the Tax Code, is the foundational legal framework governing national internal revenue taxes in the Philippines. It is administered and enforced by the Bureau of Internal Revenue (BIR).\n\nKey Areas Relevant to HR and Payroll Compliance\nWithholding Tax on Compensation:\nEmployers are legally required to act as withholding agents for the government. This means calculating, deducting, and remitting the correct income tax from employees\' wages or salaries each payroll period based on the progressive tax tables (as updated by laws like TRAIN / RA 10963).\n\n13th Month Pay & Tax Exemptions:\nUnder the NIRC (as amended), mandatory benefits such as the 13th-month pay and other bonuses/benefits are tax-exempt up to a statutory ceiling (currently ₱90,000). Any amount exceeding this threshold is subject to tax.\n\nDe Minimis Benefits:\nThe Tax Code outlines rules for \"de minimis\" benefits—small-value facilities or privileges offered by employers to promote health, goodwill, or efficiency (e.g., rice subsidy, uniform allowance, medical benefits). These remain exempt from compensation and fringe benefit taxes up to specified limits.\n\nFringe Benefits Tax (FBT):\nApplies to non-cash benefits or perks granted to managerial and supervisory employees (such as housing, vehicles, or membership fees), which are taxed separately from regular compensation.\n\nAnnualization and BIR Form 2316:\nAt the end of each calendar year, HR must conduct an annual tax consolidation (annualization) to adjust for any over-withheld or under-withheld tax, issuing BIR Form 2316 (Certificate of Compensation Payment/Tax Withheld) to employees for tax filing and compliance.', 'Tax Compliance', '1997-12-31', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:29:52'),
(15, 'PD 851', '13th Month Pay Law', 'Decreed on December 16, 1975, by President Ferdinand Marcos, P.D. 851 mandates all covered employers in the Philippines to pay their rank-and-file employees a 13th-month pay no later than December 24 of every year. Its primary purpose is to provide additional monetary compensation to assist workers during the Christmas season and offset rising costs of living.\n Coverage and Eligibility\n\nEligible Employees: All rank-and-file employees in the private sector are entitled to receive 13th-month pay, provided they have worked for at least one (1) month during the calendar year.\nEmployment Status: It applies regardless of the employee\'s status (e.g., regular, probationary, casual, project, or seasonal workers).\nSeparated/Resigned Employees: An employee who resigned or whose services were terminated at any time before the payment of the 13th-month pay is still entitled to a pro-rated share corresponding to the time worked during the year.\n\n Computation Formula\n\nThe minimum 13th-month pay is calculated as 1/12 of the total basic salary earned by an employee within a calendar year:\n\n13th Month Pay = Total Basic Salary Earned During the Year / 12\n\nBasic Salary includes: All remunerations or earnings paid by an employer for services rendered (e.g., basic rate, paid leaves, cost of living allowance merged into basic salary).\nBasic Salary excludes: Fringe benefits, overtime pay, premium pay, night shift differential, holiday pay, unused leave encashments, and commissions (unless explicitly agreed or established by company practice to form part of basic pay).\n\n Key Legal and Tax Considerations\n\nDeadline for Payment: Must be paid on or before December 24 of each year. Employers are also permitted to give half of the amount before the start of the regular school year (May or June) and the remainder before December 24.\nTax Exemption Cap: Under the National Internal Revenue Code (NIRC) as amended by the TRAIN Law, 13th-month pay and other benefits/bonuses are exempt from income tax up to PHP 90,000. Any amount exceeding PHP 90,000 is subject to standard income tax.\nNo Deductions/Off-setting: 13th-month pay cannot be substituted with cash equivalents (such as grocery vouchers) or offset against employee loans/advances unless explicitly consented to in writing.\n- Ability to mimic typewriter sound when typing.\n- Optional break reminders after long writing sessions.\n- Keyboard shortcuts for common actions.\n- Focus mode to leave you with a barebones and pristine editor.\n- Full-screen mode for a distraction-free writing experience.\n- Floating window (in supported browsers) to effectively take notes across other apps.\n- Download notes as plain text, PDF, HTML, and DOCX file.\n- Ability to play ambient noise to help you focus.\n- It\'s proudly open-source!\n\n', 'Compensation', '1975-12-16', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:41:47'),
(16, 'ECC', 'Employees\' Compensation Program', 'The Employees\' Compensation Program (ECP) is a tax-exempt government program established under Title II, Book IV of the Labor Code of the Philippines (PD 442). It is designed to provide a comprehensive package of public safety benefits and financial assistance to workers (and their dependents) in the event of work-connected sickness, injury, disability, or death.\n\nIt is administered by the Employees\' Compensation Commission (ECC) in cooperation with:\n\nSocial Security System (SSS): For private sector employees.\n\nGovernment Service Insurance System (GSIS): For public/government sector employees.\n\nCoverage and Employer Obligations\nMandatory Coverage: Applies to all compulsory members of the SSS (private sector) and GSIS (public sector), including formal workers, casuals, seasonals, and household helpers (Kasambahays).\n\n100% Employer-Funded: Premium contributions to the EC Special Fund are strictly paid by the employer. Employers are prohibited from deducting EC contributions from their employees\' monthly paychecks or salaries.\n\nKey Benefits under the ECP\nMedical Benefits:\n\nCoverage for hospital room rates, medicines, medical supplies, doctor\'s fees, and surgical procedures required due to a work-related illness or accident.\n\nDisability Cash Benefits:\n\nTemporary Total Disability (TTD): Daily income allowance for employees unable to work for a temporary period due to injury or illness.\n\nPermanent Partial Disability (PPD): Monthly pension or lump-sum payout for permanent partial loss of body functions (e.g., loss of a finger, hearing impairment).\n\nPermanent Total Disability (PTD): Lifetime monthly pension for severe, permanent conditions that prevent future gainful employment (e.g., loss of vision in both eyes, paralysis).\n\nDeath and Funeral Benefits:\n\nDeath Pension: Monthly pension granted to primary beneficiaries (spouse and minor children) upon the employee\'s work-related death.\n\nFuneral Benefit: Cash benefit provided to assist with funeral and burial expenses.\n\nRehabilitation Services (Katulong at Gabay sa Manggagawang May Kapansanan / KaGaBaY Program):\n\nFree physical or occupational therapy.\n\nAssistive devices (e.g., wheelchairs, prosthetics, hearing aids).\n\nSkills training and livelihood assistance to help injured workers re-enter the workforce or start a small business.\n\nBasic Conditions for Compensability\nTo be eligible for EC benefits, the illness, injury, or death must meet basic criteria:\n\nInjury: Must be sustained while performing official duties, during workspace travel (going to or coming from work via a direct route), or while executing a lawful order from the employer.\n\nSickness: The disease must be explicitly listed by the ECC as an occupational disease, or direct proof must be shown that the risk of contracting the illness was increased by working conditions.', 'Employee Benefits', '1973-01-01', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:32:34');
INSERT INTO `lc_philippine_laws` (`id`, `code`, `title`, `description`, `category`, `effective_date`, `status`, `created_at`, `updated_at`) VALUES
(17, 'LA 06-2020', 'Labor Advisory No. 06, Series of 2020 (Final Pay)', 'Issued on February 3, 2020, LA 06-2020 sets clear, mandatory guidelines and timelines for employers regarding the processing and release of final pay, company clearance, and Certificates of Employment (COE) to separated employees—regardless of whether the separation was due to resignation, retrenchment, end of contract, or termination for cause.\n\nKey Mandates and Timelines\n30-Day Mandatory Release Period for Final Pay:\n\nUnless a more favorable company policy, Collective Bargaining Agreement (CBA), or employment contract exists, final pay must be released within thirty (30) calendar days from the exact date of the employee’s separation or termination.\n\n3-Day Requirement for Certificate of Employment (COE):\n\nUpon request by a former employee, the employer must issue a Certificate of Employment within three (3) calendar days from the time of the request.\n\nComponents of \"Final Pay\"\nUnder LA 06-2020, \"Final Pay\" (also referred to as last pay or clearance pay) refers to the sum total of all earned or accrued monetary amounts due to an employee upon separation, which includes:\n\nUnpaid salary or wages earned up to the employee\'s last working day.\n\nPro-rated 13th-month pay.\n\nCash conversion of unused accrued leaves (such as Service Incentive Leave / SIL), if applicable under company policy or law.\n\nSeparation pay, if applicable (e.g., due to authorized causes under the Labor Code such as redundancy, retrenchment, or disease).\n\nRetirement pay, if eligible.\n\nIncome tax refund for over-withheld compensation taxes (tax annualization), if applicable.\n\nReturn of cash bonds or other employee deposits, if applicable.\n\nEnforcement & Dispute Resolution\nSingle-Entry Approach (SEnA): Any dispute or claim arising from unpaid, delayed, or improperly withheld final pay, or the failure to issue a COE, falls under the SEnA program of DOLE.\n\nFiling Requests: Separated employees can file a Request for Assistance (RFA) at the nearest DOLE regional or field office for conciliation-mediation services to compel compliance.', 'Separation Policy', '2020-01-01', 'Active', '2026-07-31 15:17:43', '2026-07-31 15:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `lc_policy_acknowledgments`
--

CREATE TABLE `lc_policy_acknowledgments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `policy_id` int(11) DEFAULT NULL,
  `date_acknowledged` datetime DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_recruitment`
--

CREATE TABLE `lc_recruitment` (
  `id` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `status` enum('Open','Closed','Filled','Cancelled') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_report_history`
--

CREATE TABLE `lc_report_history` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_by_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_report_history`
--

INSERT INTO `lc_report_history` (`id`, `report_id`, `action`, `performed_by`, `performed_by_name`, `notes`, `created_at`) VALUES
(1, 1, 'Generated', 157, 'compliance', 'Generated report: active_employees', '2026-08-03 16:51:36'),
(2, 1, 'Submitted', 157, 'compliance', 'Submitted to HR Directress.', '2026-08-03 16:51:36'),
(3, 2, 'submit', 0, 'compliance', 'Report Employee Master List submitted to Directress', '2026-08-04 04:11:28'),
(4, 3, 'submit', 0, 'compliance', 'Report Employee Master List submitted to Directress', '2026-08-04 04:11:56'),
(5, 4, 'submit', 0, 'compliance', 'Report Recruitment Summary submitted to Directress', '2026-08-04 04:55:49'),
(6, 5, 'submit', 0, 'compliance', 'Report Employee Master List submitted to Directress', '2026-08-04 05:34:17'),
(7, 6, 'submit', 0, 'compliance', 'Report Policy Acknowledgement submitted to Directress', '2026-08-04 05:34:54'),
(8, 7, 'submit', 0, 'compliance', 'Report Policy Acknowledgement submitted to Directress', '2026-08-04 05:35:13'),
(9, 8, 'submit', 0, 'compliance', 'Report sss_compliance_report submitted to Directress', '2026-08-04 05:40:37'),
(10, 11, 'submit', 0, 'compliance', 'Report Vacancy Reports submitted to Directress', '2026-08-04 05:41:53'),
(11, 12, 'submit', 0, 'compliance', 'Report Employee Master List submitted to Directress', '2026-08-06 12:21:58'),
(12, 13, 'submit', 0, 'compliance', 'Report Employee Master List submitted to Directress', '2026-08-06 12:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_report_schedule`
--

CREATE TABLE `lc_report_schedule` (
  `schedule_id` int(11) NOT NULL,
  `report_key` varchar(100) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `module` varchar(100) DEFAULT 'General',
  `frequency` enum('Daily','Weekly','Monthly','Quarterly','Annual','Anytime') DEFAULT 'Monthly',
  `day_of_month` int(11) DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_report_schedule`
--

INSERT INTO `lc_report_schedule` (`schedule_id`, `report_key`, `report_name`, `module`, `frequency`, `day_of_month`, `next_run`, `last_run`, `active`, `created_at`, `updated_at`) VALUES
(1, 'sss_compliance_report', 'Sss Compliance Report', 'Audit Report', 'Monthly', 1, '2026-09-04 04:30:28', NULL, 1, '2026-08-04 02:30:29', '2026-08-04 02:30:29'),
(2, 'missing_registrations', 'missing_registrations', 'Audit & Reporting', 'Monthly', 1, '2026-08-05 07:40:44', NULL, 1, '2026-08-04 05:40:44', '2026-08-04 05:40:44'),
(3, 'document_expiration', 'document_expiration', 'Audit & Reporting', 'Monthly', 1, '2026-08-05 07:40:53', NULL, 1, '2026-08-04 05:40:53', '2026-08-04 05:40:53');

-- --------------------------------------------------------

--
-- Table structure for table `lc_report_submission`
--

CREATE TABLE `lc_report_submission` (
  `submission_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `report_code` varchar(100) NOT NULL,
  `submitted_to` int(11) DEFAULT NULL,
  `status` enum('submitted','pending','approved','returned','archived') DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_report_submission`
--

INSERT INTO `lc_report_submission` (`submission_id`, `report_id`, `report_code`, `submitted_to`, `status`, `created_at`, `submitted_at`, `reviewed_at`, `reviewed_by`, `remarks`) VALUES
(1, 1, 'RPT-2026-0001', 8, 'submitted', '2026-08-03 16:51:36', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lc_report_templates`
--

CREATE TABLE `lc_report_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `module` varchar(100) DEFAULT 'General',
  `frequency` varchar(50) DEFAULT 'Anytime',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_report_templates`
--

INSERT INTO `lc_report_templates` (`id`, `template_name`, `module`, `frequency`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Employee Master List', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(2, 'Active Employees', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(3, 'Inactive Employees', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(4, 'Employee Directory', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(5, 'Contract Status Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(6, 'Contract Expiration Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(7, 'Policy Acknowledgement Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(8, 'Mandatory Training Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(9, 'Employee Violations Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(10, 'Exit Summary Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(11, 'Employee Turnover Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(12, 'Exit Confirmation Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(13, 'Vacant Positions Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(14, 'Incident Summary', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(15, 'Workplace Accident Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(16, 'Health Incident Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(17, 'Employee Misconduct Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(18, 'Environmental Hazard Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(19, 'Investigation Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(20, 'Corrective Action Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(21, 'Risk Assessment Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(22, 'Risk Register', 'Risk Assessment', 'Quarterly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(23, 'High Risk Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(24, 'Risk Mitigation Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(25, 'SSS Compliance Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(26, 'PhilHealth Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(27, 'Pag-IBIG Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(28, 'BIR Tax Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(29, 'Compliance Summary', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(30, 'Audit Trail', 'Legal & Compliance', 'Anytime', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(31, 'Compliance Activities', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(32, 'Monthly Compliance Report', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(33, 'Annual Compliance Report', 'Legal & Compliance', 'Annual', 'Active', '2026-08-03 16:43:22', '2026-08-03 16:43:22'),
(34, 'Employee Master List', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(35, 'Active Employees', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(36, 'Inactive Employees', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(37, 'Employee Directory', 'Employee Management', 'Anytime', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(38, 'Contract Status Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(39, 'Contract Expiration Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(40, 'Policy Acknowledgement Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(41, 'Mandatory Training Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(42, 'Employee Violations Report', 'Employee Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(43, 'Exit Summary Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(44, 'Employee Turnover Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(45, 'Exit Confirmation Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(46, 'Vacant Positions Report', 'Exit Management', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(47, 'Incident Summary', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(48, 'Workplace Accident Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(49, 'Health Incident Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(50, 'Employee Misconduct Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(51, 'Environmental Hazard Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(52, 'Investigation Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(53, 'Corrective Action Report', 'Incident Reporting', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(54, 'Risk Assessment Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(55, 'Risk Register', 'Risk Assessment', 'Quarterly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(56, 'High Risk Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(57, 'Risk Mitigation Report', 'Risk Assessment', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(58, 'SSS Compliance Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(59, 'PhilHealth Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(60, 'Pag-IBIG Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(61, 'BIR Tax Report', 'Government Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(62, 'Compliance Summary', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(63, 'Audit Trail', 'Legal & Compliance', 'Anytime', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(64, 'Compliance Activities', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(65, 'Monthly Compliance Report', 'Legal & Compliance', 'Monthly', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31'),
(66, 'Annual Compliance Report', 'Legal & Compliance', 'Annual', 'Active', '2026-08-03 16:50:31', '2026-08-03 16:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `lc_risks`
--

CREATE TABLE `lc_risks` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `risk_type` varchar(100) NOT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `description` text DEFAULT NULL,
  `mitigation_plan` text DEFAULT NULL,
  `status` enum('new_report','under_review','mitigated','resolved','closed') DEFAULT 'new_report',
  `monitoring_status` enum('pending_review','monitoring','verified','resolved','closed') DEFAULT 'pending_review',
  `compliance_review` enum('pending_verification','verified','requires_followup') DEFAULT 'pending_verification',
  `department_progress` text DEFAULT NULL,
  `review_findings` text DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `reviewed_by` varchar(255) DEFAULT NULL,
  `last_reviewed` datetime DEFAULT NULL,
  `supporting_documents` text DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_risks`
--

INSERT INTO `lc_risks` (`id`, `employee_id`, `risk_type`, `severity`, `description`, `mitigation_plan`, `status`, `monitoring_status`, `compliance_review`, `department_progress`, `review_findings`, `review_remarks`, `review_date`, `reviewed_by`, `last_reviewed`, `supporting_documents`, `archived`, `created_at`, `updated_at`) VALUES
(50, 6, 'Habitual Late', 'High', 'Employee frequently reporting late for the past 3 months, affecting operational hours.', 'Attendance counseling and coaching on disciplinary policy.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-02 23:06:00', '2026-08-04 23:06:00'),
(51, 12, 'Frequent Absences', 'Medium', 'Multiple unapproved absences recorded within the current quarter.', 'Issue notice to explain and require medical certificates.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-30 23:06:00', '2026-08-04 23:06:00'),
(52, 44, 'AWOL', 'High', 'Employee has been absent without official leave for seven consecutive days.', 'Escalate to HR for disciplinary action and contact tracing.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-03 23:06:00', '2026-08-04 23:06:00'),
(53, 29, 'Undertime', 'Low', 'Repeated undertime incidents without approved requests.', 'Counsel employee and monitor attendance records.', 'mitigated', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-27 23:06:00', '2026-08-04 23:06:00'),
(54, 37, 'Leave Abuse', 'Medium', 'Suspected misuse of leave credits and inconsistent leave filings.', 'Review leave history and validate supporting documents.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-01 23:06:00', '2026-08-04 23:06:00'),
(55, 21, 'Low Performance Rating', 'Medium', 'Employee received a below-expectation rating in the last performance review.', 'Place under Performance Improvement Plan (PIP).', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-29 23:06:00', '2026-08-04 23:06:00'),
(56, 25, 'Failed KPI', 'High', 'Employee failed to meet key performance indicators for two consecutive quarters.', 'Implement PIP and set measurable improvement targets.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-31 23:06:00', '2026-08-04 23:06:00'),
(57, 28, 'Poor Productivity', 'Low', 'Declining productivity output observed over recent weeks.', 'Provide coaching and workload reassessment.', 'mitigated', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-25 23:06:00', '2026-08-04 23:06:00'),
(58, 20, 'Complaints', 'High', 'Formal complaint filed regarding workplace conduct and interpersonal conflict.', 'Investigate the complaint and schedule mediation.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-02 23:06:00', '2026-08-04 23:06:00'),
(59, 33, 'Grievances', 'Medium', 'Employee raised a grievance regarding unfair assignment of tasks.', 'Review grievance and facilitate resolution meeting.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-28 23:06:00', '2026-08-04 23:06:00'),
(60, 13, 'Payroll Error', 'Medium', 'Discrepancy identified in employee compensation for the last payroll cycle.', 'Verify payroll records and issue correction.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-01 23:06:00', '2026-08-04 23:06:00'),
(61, 39, 'Missing Contributions', 'High', 'Government contribution remittances not reflected for the month.', 'Verify contributions with government agencies and reconcile.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-03 23:06:00', '2026-08-04 23:06:00'),
(62, 45, 'Missing Requirements', 'Low', 'New hire onboarding requirements are incomplete.', 'Follow up with new hire for missing documents.', 'mitigated', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-26 23:06:00', '2026-08-04 23:06:00'),
(63, 46, 'Failed Background Check', 'Medium', 'Background check revealed discrepancies in submitted credentials.', 'Review findings and escalate to recruitment head.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-30 23:06:00', '2026-08-04 23:06:00'),
(64, 40, 'Expired Contract', 'High', 'Employment contract is expiring soon and renewal is not yet initiated.', 'Initiate contract renewal process with the involved departments.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-02 23:06:00', '2026-08-04 23:06:00'),
(65, 41, 'Missing Documents', 'Medium', 'Required employee documents are missing from the repository.', 'Request submission and complete the document file.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-31 23:06:00', '2026-08-04 23:06:00'),
(66, 24, 'Workplace Accident', 'Critical', 'Workplace accident reported requiring immediate safety review.', 'Conduct workplace inspection and implement safety measures.', 'under_review', 'pending_review', 'pending_verification', NULL, 'nnmjjjj', 'jjjjj', '2026-08-05 04:46:24', NULL, '2026-08-05 04:46:24', NULL, 0, '2026-08-03 23:06:00', '2026-08-05 02:46:24'),
(67, 31, 'Safety Hazard', 'High', 'A potential safety hazard was identified in the laboratory area.', 'Schedule immediate inspection and corrective maintenance.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-29 23:06:00', '2026-08-04 23:06:00'),
(68, 30, 'Medical Restriction', 'Medium', 'Employee has a medical restriction limiting certain duties.', 'Coordinate with clinic for medical evaluation and accommodation.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-30 23:06:00', '2026-08-04 23:06:00'),
(69, 34, 'Work Injury', 'High', 'Employee sustained a work-related injury requiring evaluation.', 'Refer to clinic for medical evaluation and document the injury.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-01 23:06:00', '2026-08-04 23:06:00'),
(70, 22, 'Incomplete Training', 'Low', 'Employee has not completed required training modules.', 'Enroll employee in the remaining required training.', 'mitigated', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-24 23:06:00', '2026-08-04 23:06:00'),
(71, 27, 'Expired Certification', 'Medium', 'A professional certification has expired and needs renewal.', 'Coordinate renewal with the issuing body.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-28 23:06:00', '2026-08-04 23:06:00'),
(72, 38, 'Pending Clearance', 'Medium', 'Employee exit process has pending clearance items.', 'Complete the clearance process before final separation.', 'under_review', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-31 23:06:00', '2026-08-04 23:06:00'),
(73, 42, 'Unreturned Assets', 'High', 'Company property has not yet been returned by the exiting employee.', 'Follow up on asset return and complete exit clearance.', 'new_report', 'pending_review', 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-08-02 23:06:00', '2026-08-04 23:06:00'),
(74, 6, 'Habitual Late', 'Low', 'Employee frequently reporting late for the past 3 months, affecting operational hours.', 'Attendance counseling and coaching on disciplinary policy.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-05 23:06:00', '2026-08-04 23:06:00'),
(75, 13, 'Payroll Error', 'Low', 'Discrepancy identified in employee compensation for the last payroll cycle.', 'Verify payroll records and issue correction.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-10 23:06:00', '2026-08-04 23:06:00'),
(76, 40, 'Expired Contract', 'Low', 'Employment contract is expiring soon and renewal is not yet initiated.', 'Initiate contract renewal process with the involved departments.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-15 23:06:00', '2026-08-04 23:06:00'),
(77, 20, 'Complaints', 'Low', 'Formal complaint filed regarding workplace conduct and interpersonal conflict.', 'Investigate the complaint and schedule mediation.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-07 23:06:00', '2026-08-04 23:06:00'),
(78, 24, 'Workplace Accident', 'Low', 'Workplace accident reported requiring immediate safety review.', 'Conduct workplace inspection and implement safety measures.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-13 23:06:00', '2026-08-04 23:06:00'),
(79, 30, 'Medical Restriction', 'Low', 'Employee has a medical restriction limiting certain duties.', 'Coordinate with clinic for medical evaluation and accommodation.', 'closed', 'closed', 'verified', NULL, 'Documents verified. Department completed all required actions.', 'Compliance verified and risk closed.', '2026-07-26 07:06:00', 'Legal & Compliance', '2026-07-31 07:06:00', 'Attendance Report, Payroll Correction Report, Employment Contract Renewal, Investigation Report, Medical Clearance', 0, '2026-07-17 23:06:00', '2026-08-04 23:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_risk_flags`
--

CREATE TABLE `lc_risk_flags` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `description` text DEFAULT NULL,
  `status` enum('open','investigating','resolved') NOT NULL DEFAULT 'open',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lc_sss_table`
--

CREATE TABLE `lc_sss_table` (
  `id` int(11) NOT NULL,
  `min_compensation` decimal(10,2) NOT NULL,
  `max_compensation` decimal(10,2) DEFAULT NULL,
  `employee_share` decimal(10,2) NOT NULL,
  `employer_share` decimal(10,2) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_sss_table`
--

INSERT INTO `lc_sss_table` (`id`, `min_compensation`, `max_compensation`, `employee_share`, `employer_share`, `status`, `created_at`, `updated_at`) VALUES
(1, 5000.00, 5499.99, 250.00, 500.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(2, 5500.00, 5999.99, 275.00, 550.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(3, 6000.00, 6499.99, 300.00, 600.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(4, 6500.00, 6999.99, 325.00, 650.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(5, 7000.00, 7499.99, 350.00, 700.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(6, 7500.00, 7999.99, 375.00, 750.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(7, 8000.00, 8499.99, 400.00, 800.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(8, 8500.00, 8999.99, 425.00, 850.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(9, 9000.00, 9499.99, 450.00, 900.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(10, 9500.00, 9999.99, 475.00, 950.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(11, 10000.00, 10499.99, 500.00, 1000.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(12, 10500.00, 10999.99, 525.00, 1050.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(13, 11000.00, 11499.99, 550.00, 1100.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(14, 11500.00, 11999.99, 575.00, 1150.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(15, 12000.00, 12499.99, 600.00, 1200.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(16, 12500.00, 12999.99, 625.00, 1250.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(17, 13000.00, 13499.99, 650.00, 1300.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(18, 13500.00, 13999.99, 675.00, 1350.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(19, 14000.00, 14499.99, 700.00, 1400.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(20, 14500.00, 14999.99, 725.00, 1450.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(21, 15000.00, 15499.99, 750.00, 1500.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(22, 15500.00, 15999.99, 775.00, 1550.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(23, 16000.00, 16499.99, 800.00, 1600.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(24, 16500.00, 16999.99, 825.00, 1650.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(25, 17000.00, 17499.99, 850.00, 1700.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(26, 17500.00, 17999.99, 875.00, 1750.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(27, 18000.00, 18499.99, 900.00, 1800.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(28, 18500.00, 18999.99, 925.00, 1850.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(29, 19000.00, 19499.99, 950.00, 1900.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(30, 19500.00, 19999.99, 975.00, 1950.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(31, 20000.00, 20499.99, 1000.00, 2000.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(32, 20500.00, 20999.99, 1025.00, 2050.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(33, 21000.00, 21499.99, 1050.00, 2100.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(34, 21500.00, 21999.99, 1075.00, 2150.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(35, 22000.00, 22499.99, 1100.00, 2200.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(36, 22500.00, 22999.99, 1125.00, 2250.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(37, 23000.00, 23499.99, 1150.00, 2300.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(38, 23500.00, 23999.99, 1175.00, 2350.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(39, 24000.00, 24499.99, 1200.00, 2400.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(40, 24500.00, 24999.99, 1225.00, 2450.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(41, 25000.00, 25499.99, 1250.00, 2500.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(42, 25500.00, 25999.99, 1275.00, 2550.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(43, 26000.00, 26499.99, 1300.00, 2600.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(44, 26500.00, 26999.99, 1325.00, 2650.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(45, 27000.00, 27499.99, 1350.00, 2700.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(46, 27500.00, 27999.99, 1375.00, 2750.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(47, 28000.00, 28499.99, 1400.00, 2800.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(48, 28500.00, 28999.99, 1425.00, 2850.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(49, 29000.00, 29499.99, 1450.00, 2900.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(50, 29500.00, 29999.99, 1475.00, 2950.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(51, 30000.00, 30499.99, 1500.00, 3000.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(52, 30500.00, 30999.99, 1525.00, 3050.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(53, 31000.00, 31499.99, 1550.00, 3100.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(54, 31500.00, 31999.99, 1575.00, 3150.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(55, 32000.00, 32499.99, 1600.00, 3200.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(56, 32500.00, 32999.99, 1625.00, 3250.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(57, 33000.00, 33499.99, 1650.00, 3300.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(58, 33500.00, 33999.99, 1675.00, 3350.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(59, 34000.00, 34499.99, 1700.00, 3400.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(60, 34500.00, 34999.99, 1725.00, 3450.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06'),
(61, 35000.00, 99999999.99, 1750.00, 3500.00, 'Active', '2026-08-01 05:50:06', '2026-08-01 05:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `lc_trainings`
--

CREATE TABLE `lc_trainings` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_name` varchar(255) NOT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Completed','In Progress','Expired','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_trainings`
--

INSERT INTO `lc_trainings` (`id`, `employee_id`, `training_name`, `training_type`, `date_completed`, `expiry_date`, `status`, `created_at`) VALUES
(1, 6, 'Fire Safety Training', 'Safety', '2026-01-15', '2027-01-15', 'Completed', '2026-01-14 16:00:00'),
(2, 9, 'Leadership Development', 'Management', '2026-02-20', '2027-02-20', 'Completed', '2026-02-19 16:00:00'),
(3, 10, 'Data Privacy Awareness', 'Compliance', '2026-03-10', '2027-03-10', 'Completed', '2026-03-09 16:00:00'),
(4, 11, 'First Aid Training', 'Safety', '2026-04-05', '2026-10-05', 'Expired', '2026-04-04 16:00:00'),
(5, 12, 'Anti-Harassment Policy', 'Compliance', '2026-05-12', '2027-05-12', 'Completed', '2026-05-11 16:00:00'),
(6, 13, 'IT Security Basics', 'Technical', '2026-06-18', '2027-06-18', 'In Progress', '2026-06-17 16:00:00'),
(7, 14, 'Customer Service Excellence', 'Soft Skills', '2026-07-01', '2027-07-01', 'Pending', '2026-06-30 16:00:00'),
(8, 15, 'OSHA Standards', 'Safety', '2026-07-10', '2027-07-10', 'Pending', '2026-07-09 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lc_vacant_positions`
--

CREATE TABLE `lc_vacant_positions` (
  `id` int(11) NOT NULL,
  `exit_request_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `employment_type` varchar(50) DEFAULT 'Regular',
  `salary_grade` varchar(20) DEFAULT NULL,
  `vacancy_date` date DEFAULT NULL,
  `immediate_supervisor` varchar(100) DEFAULT NULL,
  `status` enum('Open','Filled','On-Hold','Archived') NOT NULL DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_vacant_positions`
--

INSERT INTO `lc_vacant_positions` (`id`, `exit_request_id`, `full_name`, `department`, `position`, `employment_type`, `salary_grade`, `vacancy_date`, `immediate_supervisor`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Maria Santos', 'Human Resources', 'HR Officer', 'Regular', 'HR-12', '2026-08-15', 'Juan Dela Cruz', 'Open', '2026-08-03 14:35:36', '2026-08-03 14:35:36'),
(2, 3, 'Ana Cruz', 'Information Technology', 'Systems Analyst', 'Fixed-Term', 'IT-15', '2026-08-10', 'Pedro Lim', 'Open', '2026-08-03 14:35:36', '2026-08-03 14:35:36'),
(3, 5, 'Lourdes Fernandez', 'Academics', 'Department Head', 'Regular', 'AC-18', '2026-06-30', 'Sandra Pascual', 'Filled', '2026-08-03 14:35:36', '2026-08-03 14:35:36'),
(4, 2, 'Jose Garcia', 'Finance', 'Accounting Head', 'Regular', 'FIN-16', '2026-09-01', 'Ana Reyes', 'Open', '2026-08-03 14:35:36', '2026-08-03 14:35:36');

-- --------------------------------------------------------

--
-- Table structure for table `ld_announcement`
--

CREATE TABLE `ld_announcement` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `audience` enum('all','instructor','learner','admin') NOT NULL DEFAULT 'all',
  `posted_by` int(10) UNSIGNED NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_api_key`
--

CREATE TABLE `ld_api_key` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_audit_log`
--

CREATE TABLE `ld_audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','instructor','learner') NOT NULL,
  `action` enum('create','edit','archive','restore','review') NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_bookmark`
--

CREATE TABLE `ld_bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_calendar_event`
--

CREATE TABLE `ld_calendar_event` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `type` enum('program','training','video-conference') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_certificate`
--

CREATE TABLE `ld_certificate` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `course_version_id` int(10) UNSIGNED DEFAULT NULL,
  `completed_enrollment_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED DEFAULT NULL,
  `verification_code` varchar(64) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` date DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_certificate_template`
--

CREATE TABLE `ld_certificate_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_comment`
--

CREATE TABLE `ld_comment` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `parent_comment_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `was_ever_reported` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_conference_attendance`
--

CREATE TABLE `ld_conference_attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `video_conference_id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `ld_course_skill`
--

CREATE TABLE `ld_course_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `ld_display_preference`
--

CREATE TABLE `ld_display_preference` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `page_size` tinyint(3) UNSIGNED NOT NULL DEFAULT 10,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_enrollment`
--

CREATE TABLE `ld_enrollment` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `course_version_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('invited','enrolled','in_progress','completed','withdrawn') NOT NULL DEFAULT 'enrolled',
  `invited_by` int(10) UNSIGNED DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_evaluation`
--

CREATE TABLE `ld_evaluation` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `max_attempts` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `question_count` int(10) UNSIGNED DEFAULT NULL,
  `show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_evaluation_feedback`
--

CREATE TABLE `ld_evaluation_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_favorite`
--

CREATE TABLE `ld_favorite` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_grade`
--

CREATE TABLE `ld_grade` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_integration_event`
--

CREATE TABLE `ld_integration_event` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `external_reference_id` varchar(255) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_integration_log`
--

CREATE TABLE `ld_integration_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_learning_path`
--

CREATE TABLE `ld_learning_path` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_learning_path_item`
--

CREATE TABLE `ld_learning_path_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `learning_path_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('course','module','lesson','quiz','evaluation','program','video-conference') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `order_index` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_lesson`
--

CREATE TABLE `ld_lesson` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_type` enum('video','text','file','mixed') NOT NULL DEFAULT 'text',
  `content_body` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `order_index` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_lesson_file`
--

CREATE TABLE `ld_lesson_file` (
  `id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_message`
--

CREATE TABLE `ld_message` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `recipient_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_module`
--

CREATE TABLE `ld_module` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_module_skill`
--

CREATE TABLE `ld_module_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_note`
--

CREATE TABLE `ld_note` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('course','module','lesson','quiz') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_notification`
--

CREATE TABLE `ld_notification` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_notification_preference`
--

CREATE TABLE `ld_notification_preference` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_prerequisite`
--

CREATE TABLE `ld_prerequisite` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `required_course_id` int(10) UNSIGNED DEFAULT NULL,
  `required_skill_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_program`
--

CREATE TABLE `ld_program` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_progress`
--

CREATE TABLE `ld_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('module','lesson','quiz','evaluation') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz`
--

CREATE TABLE `ld_quiz` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 600,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `max_attempts` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `question_count` int(10) UNSIGNED DEFAULT NULL,
  `show_answers_after_submit` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz_attempt`
--

CREATE TABLE `ld_quiz_attempt` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `quiz_session_id` int(10) UNSIGNED DEFAULT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_items` int(10) UNSIGNED NOT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz_question`
--

CREATE TABLE `ld_quiz_question` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('quiz','evaluation') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single_choice','multiple_choice','true_false') NOT NULL DEFAULT 'single_choice',
  `order_index` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz_question_option`
--

CREATE TABLE `ld_quiz_question_option` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz_session`
--

CREATE TABLE `ld_quiz_session` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('quiz','evaluation') NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','submitted','expired') NOT NULL DEFAULT 'in_progress',
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`)),
  `score` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_quiz_session_answer`
--

CREATE TABLE `ld_quiz_session_answer` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_session_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_option_id` int(10) UNSIGNED DEFAULT NULL,
  `is_marked_for_review` tinyint(1) NOT NULL DEFAULT 0,
  `answered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_rating`
--

CREATE TABLE `ld_rating` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_report`
--

CREATE TABLE `ld_report` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `reference_id` int(10) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
  `instructor_response` text DEFAULT NULL,
  `instructor_responded_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_request`
--

CREATE TABLE `ld_request` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `requested_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_setting`
--

CREATE TABLE `ld_setting` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_setting`
--

INSERT INTO `ld_setting` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'report_auto_archive_days', '7', '2026-08-15 08:21:00'),
(2, 'default_quiz_duration_seconds', '600', '2026-08-15 08:21:00'),
(3, 'default_max_quiz_attempts', '2', '2026-08-15 08:21:00'),
(4, 'file_upload_max_mb', '25', '2026-08-15 08:21:00'),
(5, 'certificate_default_validity_days', '', '2026-08-15 08:21:00'),
(6, 'site_timezone', 'Asia/Manila', '2026-08-15 08:21:00'),
(7, 'enrollment_invitation_expiry_days', '7', '2026-08-15 08:21:00'),
(8, 'video_conference_reminder_first_minutes', '30', '2026-08-15 08:21:00'),
(9, 'video_conference_reminder_second_minutes', '15', '2026-08-15 08:21:00');

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

-- --------------------------------------------------------

--
-- Table structure for table `ld_video_conference`
--

CREATE TABLE `ld_video_conference` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `program_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `platform` enum('zoom','google_meet','other') NOT NULL DEFAULT 'google_meet',
  `meeting_link` varchar(500) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('scheduled','completed','archived') NOT NULL DEFAULT 'scheduled',
  `first_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `second_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `pagibig_contributions`
--

CREATE TABLE `pagibig_contributions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contribution_number` varchar(100) DEFAULT NULL,
  `status` enum('Submitted','Paid','Pending','Overdue','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagibig_contributions`
--

INSERT INTO `pagibig_contributions` (`id`, `employee_id`, `contribution_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PI-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(2, 4, 'PI-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(3, 6, 'PI-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(4, 9, 'PI-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(5, 10, 'PI-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(6, 11, 'PI-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(7, 12, 'PI-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(8, 13, 'PI-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(9, 14, 'PI-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(10, 15, 'PI-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(11, 16, 'PI-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(12, 17, 'PI-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(13, 18, 'PI-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(14, 19, 'PI-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(15, 20, 'PI-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00'),
(16, 1, 'PI-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(17, 4, 'PI-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(18, 6, 'PI-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(19, 9, 'PI-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(20, 10, 'PI-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(21, 11, 'PI-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(22, 12, 'PI-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(23, 13, 'PI-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(24, 14, 'PI-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(25, 15, 'PI-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(26, 16, 'PI-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(27, 17, 'PI-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(28, 18, 'PI-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(29, 19, 'PI-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(30, 20, 'PI-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_part_time_rates`
--

CREATE TABLE `payroll_part_time_rates` (
  `part_time_rate_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_part_time_rates`
--

INSERT INTO `payroll_part_time_rates` (`part_time_rate_id`, `employee_id`, `hourly_rate`, `effective_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 16, 250.00, '2026-01-01', NULL, 'active', '2026-08-15 17:11:24', '2026-08-15 17:11:24'),
(2, 17, 275.00, '2026-01-01', NULL, 'active', '2026-08-15 17:11:24', '2026-08-15 17:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `personal_information`
--

CREATE TABLE `personal_information` (
  `personal_info_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `disability_info` varchar(255) DEFAULT NULL,
  `current_address` varchar(255) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_information`
--

INSERT INTO `personal_information` (`personal_info_id`, `employee_id`, `birth_date`, `gender`, `birth_place`, `civil_status`, `citizenship`, `religion`, `blood_type`, `height`, `weight`, `spouse_name`, `spouse_occupation`, `father_name`, `father_occupation`, `mother_name`, `mother_occupation`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_number`, `created_at`, `updated_at`, `disability_info`, `current_address`, `permanent_address`) VALUES
(1, 1, '1995-01-02', NULL, NULL, 'Single', 'Filipino', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-06 13:47:35', NULL, NULL, NULL, NULL),
(2, 3, '1995-09-18', 'Male', NULL, 'Single', 'Filipino', '', 'A+', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-11 19:09:33', '2026-08-11 20:04:04', '', 'GAYA-GAYA 6D', 'GAYA-GAYA 6D'),
(3, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:01:36', NULL, NULL, NULL, NULL),
(4, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:04:21', NULL, NULL, NULL, NULL),
(5, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:05:33', NULL, NULL, NULL, NULL),
(6, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:06:40', NULL, NULL, NULL, NULL),
(7, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:07:40', NULL, NULL, NULL, NULL),
(8, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:08:37', NULL, NULL, NULL, NULL),
(9, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:09:29', NULL, NULL, NULL, NULL),
(10, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:10:26', NULL, NULL, NULL, NULL),
(11, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:11:23', NULL, NULL, NULL, NULL),
(12, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:12:30', NULL, NULL, NULL, NULL),
(13, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:13:14', NULL, NULL, NULL, NULL),
(14, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:14:12', NULL, NULL, NULL, NULL),
(15, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:15:10', NULL, NULL, NULL, NULL),
(16, 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:16:41', NULL, NULL, NULL, NULL),
(17, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:17:19', NULL, NULL, NULL, NULL),
(18, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:18:06', NULL, NULL, NULL, NULL),
(19, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:18:56', NULL, NULL, NULL, NULL),
(20, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:19:35', NULL, NULL, NULL, NULL),
(21, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:20:21', NULL, NULL, NULL, NULL),
(22, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:21:00', NULL, NULL, NULL, NULL),
(23, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:21:36', NULL, NULL, NULL, NULL),
(24, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:22:23', NULL, NULL, NULL, NULL),
(25, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:22:59', NULL, NULL, NULL, NULL),
(26, 27, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:23:32', NULL, NULL, NULL, NULL),
(27, 28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 11:24:04', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `philhealth_contributions`
--

CREATE TABLE `philhealth_contributions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contribution_number` varchar(100) DEFAULT NULL,
  `status` enum('Submitted','Paid','Pending','Overdue','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `philhealth_contributions`
--

INSERT INTO `philhealth_contributions` (`id`, `employee_id`, `contribution_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PH-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(2, 4, 'PH-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(3, 6, 'PH-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(4, 9, 'PH-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(5, 10, 'PH-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(6, 11, 'PH-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(7, 12, 'PH-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(8, 13, 'PH-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(9, 14, 'PH-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(10, 15, 'PH-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(11, 16, 'PH-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(12, 17, 'PH-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(13, 18, 'PH-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(14, 19, 'PH-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(15, 20, 'PH-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00'),
(16, 1, 'PH-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(17, 4, 'PH-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(18, 6, 'PH-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(19, 9, 'PH-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(20, 10, 'PH-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(21, 11, 'PH-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(22, 12, 'PH-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(23, 13, 'PH-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(24, 14, 'PH-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(25, 15, 'PH-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(26, 16, 'PH-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(27, 17, 'PH-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(28, 18, 'PH-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(29, 19, 'PH-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(30, 20, 'PH-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00'),
(31, 1, 'PH-2024-0001', 'Submitted', '2024-07-01 00:00:00', '2024-07-01 00:00:00'),
(32, 4, 'PH-2024-0002', 'Submitted', '2024-07-01 00:05:00', '2024-07-05 01:00:00'),
(33, 6, 'PH-2024-0003', 'Pending', '2024-07-02 00:10:00', '2024-07-02 00:10:00'),
(34, 9, 'PH-2024-0004', 'Submitted', '2024-07-02 00:15:00', '2024-07-02 00:15:00'),
(35, 10, 'PH-2024-0005', 'Paid', '2024-07-03 00:20:00', '2024-07-10 02:00:00'),
(36, 11, 'PH-2024-0006', 'Submitted', '2024-07-03 00:25:00', '2024-07-03 00:25:00'),
(37, 12, 'PH-2024-0007', 'Overdue', '2024-07-04 00:30:00', '2024-07-04 00:30:00'),
(38, 13, 'PH-2024-0008', 'Pending', '2024-07-04 00:35:00', '2024-07-04 00:35:00'),
(39, 14, 'PH-2024-0009', 'Submitted', '2024-07-05 00:40:00', '2024-07-05 00:40:00'),
(40, 15, 'PH-2024-0010', 'Rejected', '2024-07-05 00:45:00', '2024-07-08 03:00:00'),
(41, 16, 'PH-2024-0011', 'Submitted', '2024-07-08 00:50:00', '2024-07-08 00:50:00'),
(42, 17, 'PH-2024-0012', 'Paid', '2024-07-08 00:55:00', '2024-07-15 01:30:00'),
(43, 18, 'PH-2024-0013', 'Pending', '2024-07-09 01:00:00', '2024-07-09 01:00:00'),
(44, 19, 'PH-2024-0014', 'Submitted', '2024-07-09 01:05:00', '2024-07-09 01:05:00'),
(45, 20, 'PH-2024-0015', 'Overdue', '2024-07-10 01:10:00', '2024-07-10 01:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `pm_appraisals`
--

CREATE TABLE `pm_appraisals` (
  `appraisal_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Not Started',
  `overall_rating` decimal(3,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `review_cycle_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_appraisal_history`
--

CREATE TABLE `pm_appraisal_history` (
  `history_id` int(11) NOT NULL,
  `appraisal_id` int(11) NOT NULL,
  `action` varchar(120) NOT NULL,
  `details` text DEFAULT NULL,
  `created_by` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_appraisal_items`
--

CREATE TABLE `pm_appraisal_items` (
  `item_id` int(11) NOT NULL,
  `appraisal_id` int(11) NOT NULL,
  `criterion` varchar(255) NOT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_employee_training`
--

CREATE TABLE `pm_employee_training` (
  `employee_training_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `recommendation_id` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `attendance_status` enum('Not Started','Attended','Absent') DEFAULT 'Not Started',
  `completion_status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `final_score` decimal(6,2) DEFAULT NULL,
  `certificate_status` enum('Not Issued','Issued') DEFAULT 'Not Issued',
  `certificate_reference` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `pm_goals`
--

CREATE TABLE `pm_goals` (
  `goal_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `goal_title` varchar(255) NOT NULL,
  `goal_description` text NOT NULL,
  `goal_category` varchar(100) DEFAULT NULL,
  `priority_level` varchar(20) DEFAULT 'Medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `target_completion_percentage` int(11) DEFAULT 0,
  `kpi_name` varchar(255) DEFAULT NULL,
  `kpi_target` varchar(255) DEFAULT NULL,
  `expected_outcome` text DEFAULT NULL,
  `smart_notes` text DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `progress_notes` text DEFAULT NULL,
  `latest_update_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Draft',
  `approval_comment` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_goal_approvals`
--

CREATE TABLE `pm_goal_approvals` (
  `approval_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `decision` varchar(20) NOT NULL,
  `comments` text DEFAULT NULL,
  `approved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_goal_attachments`
--

CREATE TABLE `pm_goal_attachments` (
  `attachment_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_goal_comments`
--

CREATE TABLE `pm_goal_comments` (
  `comment_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `user_id` int(10) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_goal_history`
--

CREATE TABLE `pm_goal_history` (
  `history_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_goal_progress`
--

CREATE TABLE `pm_goal_progress` (
  `progress_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `progress_percentage` int(11) NOT NULL,
  `progress_notes` text DEFAULT NULL,
  `update_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_performance_reports`
--

CREATE TABLE `pm_performance_reports` (
  `report_id` int(11) NOT NULL,
  `report_code` varchar(40) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `review_period` varchar(100) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `overall_rating` decimal(4,2) DEFAULT NULL,
  `kpi_health_score` decimal(5,2) DEFAULT NULL,
  `feedback_average` decimal(4,2) DEFAULT NULL,
  `goal_completion_rate` decimal(5,2) DEFAULT NULL,
  `performance_status` varchar(50) DEFAULT NULL,
  `risk_level` varchar(50) DEFAULT NULL,
  `training_recommendation` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `status` enum('Draft','Finalized','Archived') DEFAULT 'Draft',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_performance_report_actions`
--

CREATE TABLE `pm_performance_report_actions` (
  `action_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `owner` varchar(150) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Open','Planned','Completed','Deferred') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_performance_report_evaluations`
--

CREATE TABLE `pm_performance_report_evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `competency_name` varchar(255) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_performance_report_kpis`
--

CREATE TABLE `pm_performance_report_kpis` (
  `kpi_item_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `kpi_name` varchar(255) DEFAULT NULL,
  `goal_title` varchar(255) DEFAULT NULL,
  `kpi_target` varchar(255) DEFAULT NULL,
  `actual_value` varchar(255) DEFAULT NULL,
  `achievement_percentage` decimal(6,2) DEFAULT NULL,
  `performance_status` varchar(100) DEFAULT NULL,
  `manager_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_report_disciplinary_actions`
--

CREATE TABLE `pm_report_disciplinary_actions` (
  `disciplinary_action_id` int(10) UNSIGNED NOT NULL,
  `action_code` varchar(20) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `violation` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `incident_date` date NOT NULL,
  `reported_by` varchar(150) NOT NULL,
  `disciplinary_action` varchar(100) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('Active','Completed','Under Review','Cancelled') NOT NULL DEFAULT 'Active',
  `corrective_action` text DEFAULT NULL,
  `follow_up_review_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_review_cycles`
--

CREATE TABLE `pm_review_cycles` (
  `cycle_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `cycle_period` varchar(100) DEFAULT NULL,
  `appraisal_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_training_evaluations`
--

CREATE TABLE `pm_training_evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `employee_training_id` int(11) NOT NULL,
  `evaluation_date` date DEFAULT NULL,
  `knowledge_rating` decimal(4,2) DEFAULT NULL,
  `skill_rating` decimal(4,2) DEFAULT NULL,
  `application_rating` decimal(4,2) DEFAULT NULL,
  `overall_rating` decimal(4,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `evaluated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_training_programs`
--

CREATE TABLE `pm_training_programs` (
  `training_id` int(11) NOT NULL,
  `training_code` varchar(100) NOT NULL,
  `training_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `training_category` varchar(100) DEFAULT NULL,
  `skill_focus` varchar(255) DEFAULT NULL,
  `training_provider` varchar(255) DEFAULT NULL,
  `training_type` enum('Internal','External','Online') DEFAULT 'Internal',
  `duration_hours` decimal(6,2) DEFAULT NULL,
  `delivery_mode` enum('Face-to-Face','Online','Hybrid') DEFAULT 'Online',
  `cost` decimal(12,2) DEFAULT 0.00,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_training_recommendations`
--

CREATE TABLE `pm_training_recommendations` (
  `recommendation_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `recommendation_date` date DEFAULT NULL,
  `source_type` varchar(100) DEFAULT NULL,
  `source_id` varchar(100) DEFAULT NULL,
  `development_area` varchar(255) DEFAULT NULL,
  `performance_gap` text DEFAULT NULL,
  `recommendation_reason` text DEFAULT NULL,
  `priority_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `recommended_by` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','In Progress','Completed') DEFAULT 'Pending',
  `target_completion_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pr_contribution_rates`
--

CREATE TABLE `pr_contribution_rates` (
  `id` int(11) NOT NULL,
  `contribution_type` varchar(50) NOT NULL,
  `employee_rate` decimal(5,2) NOT NULL,
  `min_salary` decimal(10,2) DEFAULT 0.00,
  `max_salary` decimal(10,2) DEFAULT 9999999.00,
  `is_percentage` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_contribution_rates`
--

INSERT INTO `pr_contribution_rates` (`id`, `contribution_type`, `employee_rate`, `min_salary`, `max_salary`, `is_percentage`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'sss', 5.00, 0.00, 9999999.00, 1, 1, '2026-07-25 20:59:11', '2026-07-25 20:59:11'),
(2, 'philhealth', 2.50, 0.00, 9999999.00, 1, 1, '2026-07-25 20:59:11', '2026-07-25 20:59:11'),
(3, 'pagibig', 1.00, 0.00, 1500.00, 1, 1, '2026-07-25 20:59:11', '2026-07-25 20:59:11'),
(4, 'pagibig', 2.00, 1501.00, 9999999.00, 1, 1, '2026-07-25 20:59:11', '2026-07-25 20:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `pr_deductions`
--

CREATE TABLE `pr_deductions` (
  `deduction_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type` enum('fixed','percentage') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `is_statutory` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pr_employee_adjustments`
--

CREATE TABLE `pr_employee_adjustments` (
  `adjustment_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `period_id` int(11) NOT NULL,
  `type` enum('deduction') DEFAULT 'deduction',
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded loan proof document',
  `deduction_subtype` enum('loans','other') DEFAULT 'other' COMMENT 'Subtype for deductions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_employee_adjustments`
--

INSERT INTO `pr_employee_adjustments` (`adjustment_id`, `employee_id`, `period_id`, `type`, `description`, `amount`, `created_at`, `file_path`, `deduction_subtype`) VALUES
(2, 2, 1, 'deduction', 'Employee Loan', 500.00, '2026-08-15 17:41:12', NULL, 'loans'),
(3, 4, 1, 'deduction', 'Uniform Replacement', 300.00, '2026-08-15 17:41:12', NULL, 'other'),
(4, 11, 1, 'deduction', 'Employee Loan', 750.00, '2026-08-15 17:41:12', NULL, 'loans'),
(5, 16, 1, 'deduction', 'Equipment Charge', 250.00, '2026-08-15 17:41:12', NULL, 'other'),
(6, 30, 1, 'deduction', 'nakasira ng monitor', 1569.00, '2026-08-16 23:02:39', 'uploads/deductions/deduction_d646cccbe0abc6b7b3bdc5545c031762.pdf', 'other'),
(7, 17, 1, 'deduction', 'broken system unit due to forceful impact', 1000.00, '2026-08-16 23:06:29', NULL, 'other');

-- --------------------------------------------------------

--
-- Table structure for table `pr_employee_benefits`
--

CREATE TABLE `pr_employee_benefits` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `has_sss` tinyint(1) DEFAULT 1 COMMENT 'SSS Contribution enrollment',
  `has_philhealth` tinyint(1) DEFAULT 1 COMMENT 'PhilHealth enrollment',
  `has_pagibig` tinyint(1) DEFAULT 1 COMMENT 'Pag-IBIG enrollment',
  `sss_amount_override` decimal(10,2) DEFAULT NULL COMMENT 'Manual override if needed',
  `philhealth_amount_override` decimal(10,2) DEFAULT NULL,
  `pagibig_amount_override` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_employee_benefits`
--

INSERT INTO `pr_employee_benefits` (`id`, `employee_id`, `has_sss`, `has_philhealth`, `has_pagibig`, `sss_amount_override`, `philhealth_amount_override`, `pagibig_amount_override`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(2, 2, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(3, 3, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(4, 4, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(5, 9, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(6, 10, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(7, 11, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(8, 12, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(9, 13, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(10, 16, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(11, 17, 1, 1, 1, NULL, NULL, NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `pr_employee_deductions`
--

CREATE TABLE `pr_employee_deductions` (
  `employee_deduction_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `deduction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pr_final_settlements`
--

CREATE TABLE `pr_final_settlements` (
  `settlement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_settlement_id` int(11) DEFAULT NULL,
  `exit_case_type` enum('resignation','termination') NOT NULL,
  `exit_case_id` int(11) NOT NULL,
  `last_working_date` date NOT NULL,
  `settlement_date` date DEFAULT NULL,
  `total_earnings` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_settlement` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','processing','calculated','for_approval','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pr_final_settlement_items`
--

CREATE TABLE `pr_final_settlement_items` (
  `item_id` int(11) NOT NULL,
  `settlement_id` int(11) NOT NULL,
  `item_type` enum('earning','deduction') NOT NULL,
  `item_category` varchar(50) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pr_pagibig_rates`
--

CREATE TABLE `pr_pagibig_rates` (
  `id` int(11) NOT NULL,
  `min_salary` decimal(10,2) NOT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `employee_rate` decimal(5,4) NOT NULL,
  `employer_rate` decimal(5,4) NOT NULL,
  `employee_max_contribution` decimal(10,2) DEFAULT NULL,
  `employer_max_contribution` decimal(10,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_pagibig_rates`
--

INSERT INTO `pr_pagibig_rates` (`id`, `min_salary`, `max_salary`, `employee_rate`, `employer_rate`, `employee_max_contribution`, `employer_max_contribution`, `effective_from`, `effective_to`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 0.00, 1500.00, 0.0100, 0.0200, NULL, NULL, '2024-01-01', NULL, 1, '2026-08-14 13:21:45', '2026-08-14 13:21:45'),
(2, 1500.01, NULL, 0.0200, 0.0200, 200.00, 200.00, '2024-01-01', NULL, 1, '2026-08-14 13:21:45', '2026-08-14 13:21:45'),
(3, 0.00, 1500.00, 0.0100, 0.0200, NULL, NULL, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(4, 1500.01, NULL, 0.0200, 0.0200, 200.00, 200.00, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

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
(19, 3, 4, 17500.00, 320.00, 17180.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(20, 3, 37, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(21, 3, 33, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(22, 3, 30, 16000.00, 1569.00, 14431.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(23, 3, 2, 12500.00, 1619.33, 10880.67, '2026-08-16 23:15:53', 0, NULL, NULL),
(24, 3, 39, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(25, 3, 3, 15000.00, 1000.00, 14000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(26, 3, 29, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(27, 3, 16, 6457.50, 856.44, 5601.06, '2026-08-16 23:15:53', 0, NULL, NULL),
(28, 3, 31, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(29, 3, 34, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(30, 3, 32, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(31, 3, 35, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(32, 3, 1, 10000.00, 1874.00, 8126.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(33, 3, 38, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(34, 3, 5, 10000.00, 0.00, 10000.00, '2026-08-16 23:15:53', 0, NULL, NULL),
(35, 3, 36, 15000.00, 0.00, 15000.00, '2026-08-16 23:15:54', 0, NULL, NULL),
(36, 3, 17, 8181.25, 1030.00, 7151.25, '2026-08-16 23:15:54', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pr_payslip_items`
--

CREATE TABLE `pr_payslip_items` (
  `payslip_item_id` int(11) NOT NULL,
  `payslip_id` int(11) DEFAULT NULL,
  `item_type` enum('earning','deduction') DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_payslip_items`
--

INSERT INTO `pr_payslip_items` (`payslip_item_id`, `payslip_id`, `item_type`, `description`, `amount`) VALUES
(1, 19, 'earning', 'Semi-Monthly Salary (35,000.00 ÷ 2)', 17500.00),
(2, 19, 'deduction', 'Uniform Replacement', 300.00),
(3, 19, 'deduction', 'Late (10 minutes × ₱2.00)', 20.00),
(4, 20, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(5, 21, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(6, 22, 'earning', 'Semi-Monthly Salary (32,000.00 ÷ 2)', 16000.00),
(7, 22, 'deduction', 'nakasira ng monitor', 1569.00),
(8, 23, 'earning', 'Semi-Monthly Salary (25,000.00 ÷ 2)', 12500.00),
(9, 23, 'deduction', 'Employee Loan', 500.00),
(10, 23, 'deduction', 'SSS', 625.00),
(11, 23, 'deduction', 'PhilHealth', 312.50),
(12, 23, 'deduction', 'Pag-IBIG', 100.00),
(13, 23, 'deduction', 'Withholding Tax', 81.83),
(14, 24, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(15, 25, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(16, 25, 'deduction', 'Unexcused Absence - Aug 05, 2026 (₱1,000.00)', 1000.00),
(17, 26, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(18, 27, 'earning', 'Part-time Hours (25.83 hrs × ₱250.00)', 6457.50),
(19, 27, 'deduction', 'Equipment Charge', 250.00),
(20, 27, 'deduction', 'Late (10 minutes × ₱2.00)', 20.00),
(21, 27, 'deduction', 'SSS', 325.00),
(22, 27, 'deduction', 'PhilHealth', 161.44),
(23, 27, 'deduction', 'Pag-IBIG', 100.00),
(24, 28, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(25, 29, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(26, 30, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(27, 31, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(28, 32, 'earning', 'Semi-Monthly Salary (20,000.00 ÷ 2)', 10000.00),
(29, 32, 'deduction', 'Late (12 minutes × ₱2.00)', 24.00),
(30, 32, 'deduction', 'Unexcused Absence - Aug 05, 2026 (₱1,000.00)', 1000.00),
(31, 32, 'deduction', 'SSS', 500.00),
(32, 32, 'deduction', 'PhilHealth', 250.00),
(33, 32, 'deduction', 'Pag-IBIG', 100.00),
(34, 33, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(35, 34, 'earning', 'Semi-Monthly Salary (20,000.00 ÷ 2)', 10000.00),
(36, 35, 'earning', 'Semi-Monthly Salary (30,000.00 ÷ 2)', 15000.00),
(37, 36, 'earning', 'Part-time Hours (29.75 hrs × ₱275.00)', 8181.25),
(38, 36, 'deduction', 'broken system unit due to forceful impact', 1000.00),
(39, 36, 'deduction', 'Late (15 minutes × ₱2.00)', 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `pr_periods`
--

CREATE TABLE `pr_periods` (
  `period_id` int(11) NOT NULL,
  `period_name` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `status` enum('open','processing','closed') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_periods`
--

INSERT INTO `pr_periods` (`period_id`, `period_name`, `start_date`, `end_date`, `pay_date`, `status`) VALUES
(1, 'Aug 1-15, 2026', '2026-08-01', '2026-08-15', '2026-08-20', 'closed');

-- --------------------------------------------------------

--
-- Table structure for table `pr_philhealth_rates`
--

CREATE TABLE `pr_philhealth_rates` (
  `id` int(11) NOT NULL,
  `min_salary` decimal(10,2) NOT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `premium_rate` decimal(5,4) NOT NULL,
  `employee_share` decimal(5,4) NOT NULL,
  `employer_share` decimal(5,4) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_philhealth_rates`
--

INSERT INTO `pr_philhealth_rates` (`id`, `min_salary`, `max_salary`, `premium_rate`, `employee_share`, `employer_share`, `effective_from`, `effective_to`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 10000.00, 100000.00, 0.0500, 0.0250, 0.0250, '2025-01-01', NULL, 1, '2026-08-14 13:21:08', '2026-08-14 13:21:08'),
(2, 10000.00, 100000.00, 0.0500, 0.0250, 0.0250, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `pr_position_deduction_rates`
--

CREATE TABLE `pr_position_deduction_rates` (
  `id` int(11) NOT NULL,
  `position_type` enum('Admin','Teacher','Other') NOT NULL,
  `absence_deduction_amount` decimal(10,2) NOT NULL COMMENT 'Per absence deduction',
  `late_per_minute_rate` decimal(5,2) DEFAULT 2.00 COMMENT 'Deduction per minute late',
  `late_per_hour_rate` decimal(5,2) DEFAULT 120.00 COMMENT 'Deduction per hour late',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_position_deduction_rates`
--

INSERT INTO `pr_position_deduction_rates` (`id`, `position_type`, `absence_deduction_amount`, `late_per_minute_rate`, `late_per_hour_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 1000.00, 2.00, 120.00, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(2, 'Teacher', 1000.00, 2.00, 120.00, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(3, 'Other', 1000.00, 2.00, 120.00, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `pr_runs`
--

CREATE TABLE `pr_runs` (
  `run_id` int(11) NOT NULL,
  `period_id` int(11) DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','finalized') DEFAULT 'draft',
  `finalized_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_runs`
--

INSERT INTO `pr_runs` (`run_id`, `period_id`, `processed_at`, `status`, `finalized_by`) VALUES
(3, 1, '2026-08-16 23:15:54', 'finalized', 30);

-- --------------------------------------------------------

--
-- Table structure for table `pr_sss_contribution_rates`
--

CREATE TABLE `pr_sss_contribution_rates` (
  `id` int(11) NOT NULL,
  `min_compensation` decimal(10,2) NOT NULL,
  `max_compensation` decimal(10,2) DEFAULT NULL,
  `monthly_salary_credit` decimal(10,2) NOT NULL,
  `employee_rate` decimal(5,4) NOT NULL DEFAULT 0.0500,
  `employer_rate` decimal(5,4) NOT NULL DEFAULT 0.1000,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_sss_contribution_rates`
--

INSERT INTO `pr_sss_contribution_rates` (`id`, `min_compensation`, `max_compensation`, `monthly_salary_credit`, `employee_rate`, `employer_rate`, `effective_from`, `effective_to`, `is_active`, `created_at`, `updated_at`) VALUES
(54, 0.00, 4250.00, 4000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(55, 4250.01, 4750.00, 4500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(56, 4750.01, 5250.00, 5000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(57, 5250.01, 5750.00, 5500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(58, 5750.01, 6250.00, 6000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(59, 6250.01, 6750.00, 6500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(60, 6750.01, 7250.00, 7000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(61, 7250.01, 7750.00, 7500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(62, 7750.01, 8250.00, 8000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(63, 8250.01, 8750.00, 8500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(64, 8750.01, 9250.00, 9000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(65, 9250.01, 9750.00, 9500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(66, 9750.01, 10250.00, 10000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(67, 10250.01, 10750.00, 10500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(68, 10750.01, 11250.00, 11000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(69, 11250.01, 11750.00, 11500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(70, 11750.01, 12250.00, 12000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(71, 12250.01, 12750.00, 12500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(72, 12750.01, 13250.00, 13000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(73, 13250.01, 13750.00, 13500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(74, 13750.01, 14250.00, 14000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(75, 14250.01, 14750.00, 14500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(76, 14750.01, 15250.00, 15000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(77, 15250.01, 15750.00, 15500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(78, 15750.01, 16250.00, 16000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(79, 16250.01, 16750.00, 16500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(80, 16750.01, 17250.00, 17000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(81, 17250.01, 17750.00, 17500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(82, 17750.01, 18250.00, 18000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(83, 18250.01, 18750.00, 18500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(84, 18750.01, 19250.00, 19000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(85, 19250.01, 19750.00, 19500.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(86, 19750.01, 20250.00, 20000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(87, 20250.01, 21250.00, 21000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(88, 21250.01, 22250.00, 22000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(89, 22250.01, 23250.00, 23000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(90, 23250.01, 24250.00, 24000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(91, 24250.01, 25250.00, 25000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(92, 25250.01, 26250.00, 26000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(93, 26250.01, 27250.00, 27000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(94, 27250.01, 28250.00, 28000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(95, 28250.01, 29250.00, 29000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(96, 29250.01, 30250.00, 30000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(97, 30250.01, 31250.00, 31000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(98, 31250.01, 32250.00, 32000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(99, 32250.01, 33250.00, 33000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(100, 33250.01, 34250.00, 34000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(101, 34250.01, 35250.00, 35000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(102, 35250.01, 36250.00, 36000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(103, 36250.01, 37250.00, 37000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(104, 37250.01, 38250.00, 38000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(105, 38250.01, 39250.00, 39000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(106, 39250.01, 40250.00, 40000.00, 0.0500, 0.1000, '2026-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `pr_tax_tables`
--

CREATE TABLE `pr_tax_tables` (
  `tax_id` int(11) NOT NULL,
  `pay_frequency` enum('daily','weekly','semi_monthly','monthly') NOT NULL,
  `min_income` decimal(12,2) NOT NULL,
  `max_income` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `fixed_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_tax_tables`
--

INSERT INTO `pr_tax_tables` (`tax_id`, `pay_frequency`, `min_income`, `max_income`, `tax_rate`, `fixed_tax`, `effective_from`, `effective_to`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 'semi_monthly', 0.00, 10416.00, 0.0000, 0.00, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(8, 'semi_monthly', 10417.00, 16666.00, 0.1500, 0.00, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(9, 'semi_monthly', 16667.00, 33332.00, 0.2000, 937.50, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(10, 'semi_monthly', 33333.00, 83332.00, 0.2500, 4270.70, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(11, 'semi_monthly', 83333.00, 333332.00, 0.3000, 16770.70, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12'),
(12, 'semi_monthly', 333333.00, NULL, 0.3500, 91770.70, '2023-01-01', NULL, 1, '2026-08-15 17:41:12', '2026-08-15 17:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `pr_teacher_qualification_rates`
--

CREATE TABLE `pr_teacher_qualification_rates` (
  `id` int(11) NOT NULL,
  `qualification` enum('ProfEd','LPT','Masteral','Doctoral') NOT NULL,
  `pay_per_unit` decimal(10,2) NOT NULL COMMENT 'PHP per unit',
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pr_teacher_qualification_rates`
--

INSERT INTO `pr_teacher_qualification_rates` (`id`, `qualification`, `pay_per_unit`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ProfEd', 128.00, 'ProfEd/Normal Teacher - Default', 1, '2026-03-29 08:58:43', '2026-03-29 08:58:43'),
(2, 'LPT', 130.00, 'Licensed Professional Teacher', 1, '2026-03-29 08:58:43', '2026-03-29 08:58:43'),
(3, 'Masteral', 250.00, 'Teachers with Masteral Degree', 1, '2026-03-29 08:58:43', '2026-03-29 08:58:43'),
(4, 'Doctoral', 500.00, 'Teachers with Doctorate qualification', 1, '2026-08-13 00:14:11', '2026-08-13 00:14:11');

-- --------------------------------------------------------

--
-- Table structure for table `sss_contributions`
--

CREATE TABLE `sss_contributions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contribution_number` varchar(100) DEFAULT NULL,
  `status` enum('Submitted','Paid','Pending','Overdue','Rejected') DEFAULT 'Pending',
  `payroll_notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sss_contributions`
--

INSERT INTO `sss_contributions` (`id`, `employee_id`, `contribution_number`, `status`, `payroll_notified`, `created_at`, `updated_at`) VALUES
(1, 1, 'SSS-2026-0001-001', 'Paid', 1, '2026-07-16 22:45:12', '2026-07-11 22:45:12'),
(2, 1, 'SSS-2026-0001-002', 'Overdue', 0, '2026-07-06 22:45:12', '2026-07-03 22:45:12'),
(3, 4, 'SSS-2026-0004-001', 'Rejected', 0, '2026-07-24 22:45:12', '2026-07-09 22:45:12'),
(4, 4, 'SSS-2026-0004-002', 'Paid', 1, '2026-07-23 22:45:12', '2026-07-09 22:45:12'),
(5, 4, 'SSS-2026-0004-003', 'Paid', 1, '2026-05-08 22:45:12', '2026-07-05 22:45:12'),
(6, 6, 'SSS-2026-0006-001', 'Pending', 0, '2026-05-14 22:45:12', '2026-07-27 22:45:12'),
(7, 6, 'SSS-2026-0006-002', 'Overdue', 0, '2026-06-20 22:45:12', '2026-07-26 22:45:12'),
(8, 6, 'SSS-2026-0006-003', 'Pending', 0, '2026-05-07 22:45:12', '2026-07-06 22:45:12'),
(9, 9, 'SSS-2026-0009-001', 'Submitted', 1, '2026-05-07 22:45:12', '2026-07-04 22:45:12'),
(10, 9, 'SSS-2026-0009-002', 'Paid', 1, '2026-07-10 22:45:12', '2026-07-13 22:45:12'),
(11, 10, 'SSS-2026-0010-001', 'Submitted', 1, '2026-05-12 22:45:12', '2026-07-12 22:45:12'),
(12, 10, 'SSS-2026-0010-002', 'Submitted', 1, '2026-05-09 22:45:12', '2026-07-13 22:45:12'),
(13, 11, 'SSS-2026-0011-001', 'Rejected', 0, '2026-06-11 22:45:12', '2026-07-03 22:45:12'),
(14, 11, 'SSS-2026-0011-002', 'Pending', 0, '2026-07-29 22:45:12', '2026-07-18 22:45:12'),
(15, 12, 'SSS-2026-0012-001', 'Pending', 0, '2026-06-01 22:45:12', '2026-07-24 22:45:12'),
(16, 12, 'SSS-2026-0012-002', 'Overdue', 0, '2026-07-20 22:45:12', '2026-07-12 22:45:12'),
(17, 13, 'SSS-2026-0013-001', 'Pending', 0, '2026-05-30 22:45:12', '2026-07-19 22:45:12'),
(18, 13, 'SSS-2026-0013-002', 'Submitted', 1, '2026-05-11 22:45:12', '2026-07-16 22:45:12'),
(19, 14, 'SSS-2026-0014-001', 'Paid', 1, '2026-05-17 22:45:12', '2026-07-12 22:45:12'),
(20, 14, 'SSS-2026-0014-002', 'Pending', 0, '2026-06-09 22:45:12', '2026-07-27 22:45:12'),
(21, 14, 'SSS-2026-0014-003', 'Overdue', 0, '2026-06-07 22:45:12', '2026-07-03 22:45:12'),
(22, 15, 'SSS-2026-0015-001', 'Pending', 0, '2026-05-20 22:45:12', '2026-07-24 22:45:12'),
(23, 15, 'SSS-2026-0015-002', 'Rejected', 0, '2026-06-30 22:45:12', '2026-07-03 22:45:12'),
(24, 15, 'SSS-2026-0015-003', 'Paid', 1, '2026-07-25 22:45:12', '2026-07-13 22:45:12');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, '2026-04-05', 36, 'PH', '2026-04-04 20:30:10'),
(3, '2026-08-15', 36, 'PH', '2026-08-15 12:22:37'),
(4, '2026-08-15', 36, 'PH', '2026-08-15 12:25:06'),
(5, '2026-08-15', 36, 'PH', '2026-08-15 12:27:01'),
(6, '2026-08-15', 36, 'PH', '2026-08-15 12:29:24'),
(7, '2026-08-15', 36, 'PH', '2026-08-15 12:44:01'),
(8, '2026-08-15', 36, 'PH', '2026-08-15 14:54:42'),
(9, '2026-08-15', 36, 'PH', '2026-08-15 14:54:59');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `balance_deducted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_employee_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_employee_summary` (
`employee_id` int(11)
,`full_name` varchar(101)
,`department` varchar(100)
,`position` varchar(100)
,`employment_type` enum('Full-time','Part-time','Laboratory','OJT/Training')
,`employment_status` enum('Active','Resigned','Terminated','Probationary')
,`total_visits` bigint(21)
,`last_visit_date` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_medicine_status`
-- (See below for the actual view)
--
CREATE TABLE `v_medicine_status` (
`medicine_id` int(10)
,`medicine_name` varchar(200)
,`category` varchar(100)
,`current_stock` int(11)
,`reorder_level` int(11)
,`unit_cost` decimal(8,2)
,`expiry_date` date
,`stock_status` varchar(12)
,`expiry_status` varchar(13)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_patient_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_patient_summary` (
`patient_id` int(10)
,`employee_id` int(11)
,`full_name` varchar(101)
,`patient_type` enum('Staff','Faculty')
,`status` enum('Active','Inactive')
,`total_visits` bigint(21)
,`visits_last_30_days` bigint(21)
,`last_visit_date` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `wfa_age_distribution`
--

CREATE TABLE `wfa_age_distribution` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `age_group` varchar(50) NOT NULL COMMENT '18-25, 26-35, 36-45, 46-55, 56+',
  `employee_count` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance_score` decimal(5,2) DEFAULT 0.00,
  `department_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`department_breakdown`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_attrition_tracking`
--

CREATE TABLE `wfa_attrition_tracking` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `separation_date` date NOT NULL,
  `separation_type` enum('resigned','retired','terminated','other') DEFAULT 'resigned',
  `department_id` int(10) DEFAULT NULL,
  `tenure_years` decimal(5,2) DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `exit_interview_completed` tinyint(1) DEFAULT 0,
  `rehire_eligible` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `wfa_at_risk_employees_summary`
-- (See below for the actual view)
--
CREATE TABLE `wfa_at_risk_employees_summary` (
`risk_level` enum('high','medium','low')
,`count` bigint(21)
,`avg_risk_score` decimal(6,2)
,`percentage` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `wfa_audit_log`
--

CREATE TABLE `wfa_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'view_report, generate_report, export_data, update_filter',
  `resource_type` varchar(50) DEFAULT NULL COMMENT 'report, filter, metric',
  `resource_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_compensation_analysis`
--

CREATE TABLE `wfa_compensation_analysis` (
  `id` int(11) NOT NULL,
  `department_id` int(10) NOT NULL,
  `position_id` int(10) NOT NULL,
  `current_avg_salary` decimal(12,2) DEFAULT 0.00,
  `market_median_salary` decimal(12,2) DEFAULT 0.00,
  `salary_competitiveness_ratio` decimal(5,2) DEFAULT NULL COMMENT 'Current/Market %',
  `employee_count` int(11) DEFAULT 0,
  `salary_range_min` decimal(12,2) DEFAULT NULL,
  `salary_range_max` decimal(12,2) DEFAULT NULL,
  `recommended_adjustment` decimal(12,2) DEFAULT NULL,
  `last_market_review` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `wfa_current_employees_by_dept`
-- (See below for the actual view)
--
CREATE TABLE `wfa_current_employees_by_dept` (
`department_id` int(11)
,`department_name` varchar(100)
,`employee_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `wfa_custom_filters`
--

CREATE TABLE `wfa_custom_filters` (
  `id` int(11) NOT NULL,
  `filter_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `filter_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Department, employment type, date range' CHECK (json_valid(`filter_config`)),
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_department_analytics`
--

CREATE TABLE `wfa_department_analytics` (
  `id` int(11) NOT NULL,
  `department_id` int(10) NOT NULL,
  `employee_count` int(11) DEFAULT 0,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance_score` decimal(5,2) DEFAULT 0.00,
  `headcount_target` int(11) DEFAULT NULL,
  `vacancy_count` int(11) DEFAULT 0,
  `average_tenure_years` decimal(5,2) DEFAULT 0.00,
  `metric_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `wfa_department_diversity`
-- (See below for the actual view)
--
CREATE TABLE `wfa_department_diversity` (
`metric_date` date
,`diversity_category` varchar(50)
,`category_value` varchar(100)
,`employee_count` int(11)
,`percentage` decimal(5,2)
,`average_salary` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `wfa_diversity_metrics`
--

CREATE TABLE `wfa_diversity_metrics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `department_id` int(10) DEFAULT NULL,
  `diversity_category` varchar(50) NOT NULL COMMENT 'gender, age_group, department',
  `category_value` varchar(100) NOT NULL COMMENT 'Male/Female/Other, 18-25/26-35/etc, Department Name',
  `employee_count` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_employee_metrics`
--

CREATE TABLE `wfa_employee_metrics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_employees` int(11) DEFAULT 0,
  `total_teachers` int(11) DEFAULT 0,
  `total_staff` int(11) DEFAULT 0,
  `new_hires_this_year` int(11) DEFAULT 0,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance_score` decimal(5,2) DEFAULT 0.00,
  `total_departments` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_gender_distribution`
--

CREATE TABLE `wfa_gender_distribution` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `gender` varchar(50) NOT NULL COMMENT 'Male, Female, Other',
  `employee_count` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance_score` decimal(5,2) DEFAULT 0.00,
  `department_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`department_breakdown`)),
  `position_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`position_breakdown`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_headcount_planning`
--

CREATE TABLE `wfa_headcount_planning` (
  `id` int(11) NOT NULL,
  `department_id` int(10) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `planned_headcount` int(11) DEFAULT 0,
  `actual_headcount` int(11) DEFAULT 0,
  `variance` int(11) DEFAULT 0,
  `planned_salary_budget` decimal(15,2) DEFAULT 0.00,
  `actual_salary_budget` decimal(15,2) DEFAULT 0.00,
  `budget_variance` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_monthly_attrition`
--

CREATE TABLE `wfa_monthly_attrition` (
  `id` int(11) NOT NULL,
  `year_month` date NOT NULL,
  `total_separations` int(11) DEFAULT 0,
  `voluntary_separations` int(11) DEFAULT 0,
  `involuntary_separations` int(11) DEFAULT 0,
  `attrition_rate_percent` decimal(5,2) DEFAULT 0.00,
  `average_tenure_departing` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_performance_distribution`
--

CREATE TABLE `wfa_performance_distribution` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `performance_level` varchar(50) NOT NULL COMMENT 'Excellent, Good, Average, Below Average, Poor',
  `score_range_min` decimal(5,2) DEFAULT NULL,
  `score_range_max` decimal(5,2) DEFAULT NULL,
  `employee_count` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `department_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Department distribution' CHECK (json_valid(`department_breakdown`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_reports`
--

CREATE TABLE `wfa_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL COMMENT 'dashboard, attrition, diversity, performance, salary, custom',
  `report_date` date NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `filters_applied` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Department, date range, etc.' CHECK (json_valid(`filters_applied`)),
  `report_data` longtext DEFAULT NULL COMMENT 'JSON data snapshot',
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Path to exported file if any',
  `export_format` varchar(20) DEFAULT NULL COMMENT 'CSV, PDF, Excel',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_risk_assessment`
--

CREATE TABLE `wfa_risk_assessment` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) DEFAULT NULL,
  `risk_level` enum('high','medium','low') DEFAULT 'low',
  `risk_score` decimal(5,2) DEFAULT 0.00 COMMENT '0-100 score',
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of risk factors' CHECK (json_valid(`risk_factors`)),
  `low_performance_flag` tinyint(1) DEFAULT 0 COMMENT 'Performance < 3.0',
  `high_absence_flag` tinyint(1) DEFAULT 0 COMMENT 'Absence days > 15',
  `low_engagement_flag` tinyint(1) DEFAULT 0,
  `recent_complaints_flag` tinyint(1) DEFAULT 0,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `absence_days` int(11) DEFAULT 0,
  `tenure_months` int(11) DEFAULT 0,
  `last_assessment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_salary_statistics`
--

CREATE TABLE `wfa_salary_statistics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `department_id` int(10) NOT NULL,
  `employee_count` int(11) DEFAULT 0,
  `min_salary` decimal(12,2) DEFAULT 0.00,
  `max_salary` decimal(12,2) DEFAULT 0.00,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `median_salary` decimal(12,2) DEFAULT 0.00,
  `total_payroll` decimal(15,2) DEFAULT 0.00,
  `salary_variance` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_skill_gap_analysis`
--

CREATE TABLE `wfa_skill_gap_analysis` (
  `id` int(11) NOT NULL,
  `department_id` int(10) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `required_proficiency` varchar(50) DEFAULT NULL COMMENT 'Basic, Intermediate, Advanced, Expert',
  `current_proficiency_avg` varchar(50) DEFAULT NULL,
  `employees_with_skill` int(11) DEFAULT 0,
  `employees_needing_training` int(11) DEFAULT 0,
  `skill_gap_percentage` decimal(5,2) DEFAULT 0.00,
  `priority_level` enum('critical','high','medium','low') DEFAULT 'medium',
  `training_recommendations` text DEFAULT NULL,
  `last_assessed` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wfa_tenure_analysis`
--

CREATE TABLE `wfa_tenure_analysis` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `tenure_bracket` varchar(50) NOT NULL COMMENT '0-1yr, 1-3yr, 3-5yr, 5-10yr, 10+ yr',
  `employee_count` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `average_salary` decimal(12,2) DEFAULT 0.00,
  `average_performance_score` decimal(5,2) DEFAULT 0.00,
  `department_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`department_breakdown`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_employee_summary`
--
DROP TABLE IF EXISTS `v_employee_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_employee_summary`  AS SELECT `e`.`employee_id` AS `employee_id`, concat(`e`.`first_name`,' ',`e`.`last_name`) AS `full_name`, `d`.`department_name` AS `department`, `p`.`position_name` AS `position`, `e`.`employment_type` AS `employment_type`, `e`.`employment_status` AS `employment_status`, count(`mr`.`record_id`) AS `total_visits`, max(`mr`.`visit_date`) AS `last_visit_date` FROM ((((`em_employees` `e` left join `em_departments` `d` on(`e`.`department_id` = `d`.`department_id`)) left join `em_positions` `p` on(`e`.`position_id` = `p`.`position_id`)) left join `cm_patients` `cp` on(`e`.`employee_id` = `cp`.`employee_id`)) left join `cm_medical_records` `mr` on(`cp`.`patient_id` = `mr`.`patient_id`)) GROUP BY `e`.`employee_id`, `e`.`first_name`, `e`.`last_name`, `d`.`department_name`, `p`.`position_name`, `e`.`employment_type`, `e`.`employment_status` ;

-- --------------------------------------------------------

--
-- Structure for view `v_medicine_status`
--
DROP TABLE IF EXISTS `v_medicine_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_medicine_status`  AS SELECT `cm_medicine_inventory`.`medicine_id` AS `medicine_id`, `cm_medicine_inventory`.`medicine_name` AS `medicine_name`, `cm_medicine_inventory`.`category` AS `category`, `cm_medicine_inventory`.`current_stock` AS `current_stock`, `cm_medicine_inventory`.`reorder_level` AS `reorder_level`, `cm_medicine_inventory`.`unit_cost` AS `unit_cost`, `cm_medicine_inventory`.`expiry_date` AS `expiry_date`, CASE WHEN `cm_medicine_inventory`.`expiry_date` < curdate() THEN 'Expired' WHEN `cm_medicine_inventory`.`current_stock` = 0 THEN 'Out of Stock' WHEN `cm_medicine_inventory`.`current_stock` <= `cm_medicine_inventory`.`reorder_level` THEN 'Low Stock' ELSE 'Available' END AS `stock_status`, CASE WHEN `cm_medicine_inventory`.`expiry_date` < curdate() THEN 'Expired' WHEN `cm_medicine_inventory`.`expiry_date` <= curdate() + interval 30 day THEN 'Expiring Soon' ELSE 'OK' END AS `expiry_status` FROM `cm_medicine_inventory` ;

-- --------------------------------------------------------

--
-- Structure for view `v_patient_summary`
--
DROP TABLE IF EXISTS `v_patient_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_patient_summary`  AS SELECT `p`.`patient_id` AS `patient_id`, `p`.`employee_id` AS `employee_id`, CASE WHEN `e`.`employee_id` is not null THEN concat(`e`.`first_name`,' ',`e`.`last_name`) ELSE concat('Patient #',`p`.`patient_id`) END AS `full_name`, `p`.`patient_type` AS `patient_type`, `p`.`status` AS `status`, count(`mr`.`record_id`) AS `total_visits`, count(case when `mr`.`visit_date` >= curdate() - interval 30 day then 1 end) AS `visits_last_30_days`, max(`mr`.`visit_date`) AS `last_visit_date` FROM ((`cm_patients` `p` left join `em_employees` `e` on(`p`.`employee_id` = `e`.`employee_id`)) left join `cm_medical_records` `mr` on(`p`.`patient_id` = `mr`.`patient_id`)) GROUP BY `p`.`patient_id`, `p`.`employee_id`, `e`.`employee_id`, `e`.`first_name`, `e`.`last_name`, `p`.`patient_type`, `p`.`status` ;

-- --------------------------------------------------------

--
-- Structure for view `wfa_at_risk_employees_summary`
--
DROP TABLE IF EXISTS `wfa_at_risk_employees_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wfa_at_risk_employees_summary`  AS SELECT `wfa_risk_assessment`.`risk_level` AS `risk_level`, count(0) AS `count`, round(avg(`wfa_risk_assessment`.`risk_score`),2) AS `avg_risk_score`, round(count(0) * 100.0 / (select count(0) from `wfa_risk_assessment` where cast(`wfa_risk_assessment`.`updated_at` as date) = curdate()),2) AS `percentage` FROM `wfa_risk_assessment` WHERE cast(`wfa_risk_assessment`.`updated_at` as date) = curdate() GROUP BY `wfa_risk_assessment`.`risk_level` ;

-- --------------------------------------------------------

--
-- Structure for view `wfa_current_employees_by_dept`
--
DROP TABLE IF EXISTS `wfa_current_employees_by_dept`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wfa_current_employees_by_dept`  AS SELECT `d`.`department_id` AS `department_id`, `d`.`department_name` AS `department_name`, count(distinct `e`.`employee_id`) AS `employee_count` FROM (`em_employees` `e` left join `em_departments` `d` on(`e`.`department_id` = `d`.`department_id`)) WHERE `e`.`employment_status` = 'Active' GROUP BY `d`.`department_id`, `d`.`department_name` ;

-- --------------------------------------------------------

--
-- Structure for view `wfa_department_diversity`
--
DROP TABLE IF EXISTS `wfa_department_diversity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wfa_department_diversity`  AS SELECT `wfa_diversity_metrics`.`metric_date` AS `metric_date`, `wfa_diversity_metrics`.`diversity_category` AS `diversity_category`, `wfa_diversity_metrics`.`category_value` AS `category_value`, `wfa_diversity_metrics`.`employee_count` AS `employee_count`, `wfa_diversity_metrics`.`percentage` AS `percentage`, `wfa_diversity_metrics`.`average_salary` AS `average_salary` FROM `wfa_diversity_metrics` WHERE `wfa_diversity_metrics`.`diversity_category` = 'gender' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cm_clinic_reports`
--
ALTER TABLE `cm_clinic_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_report_date` (`report_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cm_document_attachments`
--
ALTER TABLE `cm_document_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_document_type` (`document_type`);

--
-- Indexes for table `cm_emergency_cases`
--
ALTER TABLE `cm_emergency_cases`
  ADD PRIMARY KEY (`case_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_case_status` (`case_status`),
  ADD KEY `idx_severity_level` (`severity_level`);

--
-- Indexes for table `cm_medical_records`
--
ALTER TABLE `cm_medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_consultation_type` (`consultation_type`);

--
-- Indexes for table `cm_medicine_inventory`
--
ALTER TABLE `cm_medicine_inventory`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_medicine_name` (`medicine_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `fk_cm_medicine_supplier` (`supplier_id`);

--
-- Indexes for table `cm_medicine_usage_logs`
--
ALTER TABLE `cm_medicine_usage_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `used_by` (`used_by`),
  ADD KEY `fk_cm_usage_record` (`record_id`),
  ADD KEY `idx_medicine_id` (`medicine_id`),
  ADD KEY `idx_usage_date` (`usage_date`);

--
-- Indexes for table `cm_patients`
--
ALTER TABLE `cm_patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `idx_patient_type` (`patient_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `cm_suppliers`
--
ALTER TABLE `cm_suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`),
  ADD KEY `idx_supplier_name` (`supplier_name`);

--
-- Indexes for table `cm_vital_signs`
--
ALTER TABLE `cm_vital_signs`
  ADD PRIMARY KEY (`vital_sign_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Indexes for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  ADD PRIMARY KEY (`eer_announcements_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `eer_award_history`
--
ALTER TABLE `eer_award_history`
  ADD PRIMARY KEY (`eer_award_history_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_eer_award_history_nominated_by` (`nominated_by`);

--
-- Indexes for table `eer_award_votes`
--
ALTER TABLE `eer_award_votes`
  ADD UNIQUE KEY `uniq_award_vote` (`award_history_id`,`voter_user_id`),
  ADD KEY `award_history_id` (`award_history_id`),
  ADD KEY `voter_user_id` (`voter_user_id`),
  ADD KEY `fk_eer_award_votes_nominee` (`nominee_employee_id`);

--
-- Indexes for table `eer_badges`
--
ALTER TABLE `eer_badges`
  ADD PRIMARY KEY (`eer_badge_id`);

--
-- Indexes for table `eer_comments`
--
ALTER TABLE `eer_comments`
  ADD PRIMARY KEY (`eer_comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_eer_comments_user` (`user_id`);

--
-- Indexes for table `eer_employee_badges`
--
ALTER TABLE `eer_employee_badges`
  ADD PRIMARY KEY (`eer_employee_badge_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `badge_id` (`badge_id`),
  ADD KEY `fk_eer_employee_badges_awarded_by` (`awarded_by`);

--
-- Indexes for table `eer_forums`
--
ALTER TABLE `eer_forums`
  ADD PRIMARY KEY (`eer_forum_id`),
  ADD KEY `fk_eer_forums_employee` (`created_by_employee_id`);

--
-- Indexes for table `eer_grievances`
--
ALTER TABLE `eer_grievances`
  ADD PRIMARY KEY (`eer_grievance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `eer_grievance_attendance_links`
--
ALTER TABLE `eer_grievance_attendance_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grievance_id` (`grievance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `attendance_id` (`attendance_id`);

--
-- Indexes for table `eer_grievance_payroll`
--
ALTER TABLE `eer_grievance_payroll`
  ADD PRIMARY KEY (`eer_grievance_id`),
  ADD KEY `fk_payroll_employee` (`employee_id`);

--
-- Indexes for table `eer_grievance_updates`
--
ALTER TABLE `eer_grievance_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_update_grievance` (`grievance_id`),
  ADD KEY `fk_update_user` (`updated_by_employee_id`);

--
-- Indexes for table `eer_groups`
--
ALTER TABLE `eer_groups`
  ADD PRIMARY KEY (`eer_group_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `eer_group_members`
--
ALTER TABLE `eer_group_members`
  ADD PRIMARY KEY (`eer_group_member_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `eer_messages`
--
ALTER TABLE `eer_messages`
  ADD PRIMARY KEY (`eer_message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `eer_notifications`
--
ALTER TABLE `eer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_employee` (`employee_id`);

--
-- Indexes for table `eer_policies`
--
ALTER TABLE `eer_policies`
  ADD PRIMARY KEY (`eer_policy_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `eer_projects`
--
ALTER TABLE `eer_projects`
  ADD PRIMARY KEY (`eer_project_id`),
  ADD KEY `fk_eer_projects_employee` (`created_by_employee_id`);

--
-- Indexes for table `eer_reactions`
--
ALTER TABLE `eer_reactions`
  ADD PRIMARY KEY (`eer_reaction_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_eer_reactions_user` (`user_id`);

--
-- Indexes for table `eer_recognitions`
--
ALTER TABLE `eer_recognitions`
  ADD PRIMARY KEY (`eer_recognition_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `eer_replies`
--
ALTER TABLE `eer_replies`
  ADD PRIMARY KEY (`eer_reply_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_eer_replies_parent` (`parent_reply_id`),
  ADD KEY `fk_eer_replies_mentioned_user` (`mentioned_user_id`);

--
-- Indexes for table `eer_rewards`
--
ALTER TABLE `eer_rewards`
  ADD PRIMARY KEY (`eer_reward_id`);

--
-- Indexes for table `eer_reward_redemptions`
--
ALTER TABLE `eer_reward_redemptions`
  ADD PRIMARY KEY (`eer_reward_redemption_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reward_id` (`reward_id`),
  ADD KEY `fk_eer_reward_redemptions_approved_by` (`approved_by`);

--
-- Indexes for table `eer_social_posts`
--
ALTER TABLE `eer_social_posts`
  ADD PRIMARY KEY (`eer_social_post_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `eer_surveys`
--
ALTER TABLE `eer_surveys`
  ADD PRIMARY KEY (`eer_survey_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`),
  ADD KEY `fk_eer_surveys_pm360` (`feedback_id`);

--
-- Indexes for table `eer_survey_answers`
--
ALTER TABLE `eer_survey_answers`
  ADD PRIMARY KEY (`eer_survey_answer_id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `eer_survey_feedback`
--
ALTER TABLE `eer_survey_feedback`
  ADD PRIMARY KEY (`eer_survey_feedback_id`),
  ADD KEY `fk_eer_survey_feedback_survey` (`survey_id`);

--
-- Indexes for table `eer_survey_feedback_id`
--
ALTER TABLE `eer_survey_feedback_id`
  ADD PRIMARY KEY (`eer_survey_feedback_id_id`),
  ADD KEY `fk_eer_survey_feedback_id_survey` (`survey_id`),
  ADD KEY `fk_eer_survey_feedback_id_employee` (`employee_id`);

--
-- Indexes for table `eer_survey_questions`
--
ALTER TABLE `eer_survey_questions`
  ADD PRIMARY KEY (`eer_survey_question_id`),
  ADD KEY `survey_id` (`survey_id`);

--
-- Indexes for table `eer_survey_responses`
--
ALTER TABLE `eer_survey_responses`
  ADD PRIMARY KEY (`eer_survey_response_id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_response_target_employee` (`target_employee_id`);

--
-- Indexes for table `eer_survey_targets`
--
ALTER TABLE `eer_survey_targets`
  ADD PRIMARY KEY (`eer_survey_target_id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `document_id` (`document_id`);

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
  ADD KEY `position_id` (`position_id`),
  ADD KEY `fk_employees_department` (`department_id`);

--
-- Indexes for table `em_positions`
--
ALTER TABLE `em_positions`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `em_roles`
--
ALTER TABLE `em_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uq_role_name` (`role_name`);

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
-- Indexes for table `exit_archive`
--
ALTER TABLE `exit_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_archive_employee` (`employee_id`);

--
-- Indexes for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_exit_case` (`exit_case_type`,`exit_case_id`),
  ADD KEY `fk_exit_documents_employee` (`employee_id`);

--
-- Indexes for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  ADD PRIMARY KEY (`settlement_id`),
  ADD KEY `idx_exit_settlement_employee` (`employee_id`),
  ADD KEY `idx_exit_settlement_case` (`exit_case_type`,`exit_case_id`),
  ADD KEY `idx_exit_settlement_payroll` (`payroll_settlement_id`),
  ADD KEY `idx_exit_settlement_status` (`status`),
  ADD KEY `idx_exit_settlement_last_working_date` (`last_working_date`);

--
-- Indexes for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_interviews_employee` (`employee_id`);

--
-- Indexes for table `exit_interview_hr_assessments`
--
ALTER TABLE `exit_interview_hr_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interview_id` (`interview_id`);

--
-- Indexes for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_transfer_items_plan` (`plan_id`);

--
-- Indexes for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_transfer_plans_employee` (`employee_id`),
  ADD KEY `fk_exit_transfer_plans_successor` (`successor_id`);

--
-- Indexes for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_resignations_employee` (`employee_id`);

--
-- Indexes for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_surveys_employee` (`employee_id`);

--
-- Indexes for table `exit_survey_answers`
--
ALTER TABLE `exit_survey_answers`
  ADD KEY `fk_exit_survey_answers_response_id_exit_survey_responses` (`response_id`),
  ADD KEY `fk_exit_survey_answers_question_id_exit_survey_questions` (`question_id`);

--
-- Indexes for table `exit_survey_questions`
--
ALTER TABLE `exit_survey_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_survey_questions_survey_id_exit_surveys` (`survey_id`);

--
-- Indexes for table `exit_survey_responses`
--
ALTER TABLE `exit_survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exit_survey_responses_employee` (`employee_id`),
  ADD KEY `fk_exit_survey_responses_survey_id_exit_surveys` (`survey_id`);

--
-- Indexes for table `exit_terminations`
--
ALTER TABLE `exit_terminations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_termination_employee` (`employee_id`),
  ADD KEY `fk_termination_submitted_by` (`submitted_by`),
  ADD KEY `fk_termination_approved_by` (`approved_by`);

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
-- Indexes for table `kpi_assignments`
--
ALTER TABLE `kpi_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_kpi_assignments_kpi` (`kpi_id`),
  ADD KEY `idx_kpi_assignments_assignee` (`assignee_id`),
  ADD KEY `idx_kpi_assignments_period` (`period_start_date`,`period_end_date`),
  ADD KEY `idx_kpi_assignments_status` (`assignment_status`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_kpi_attachments_entry` (`entry_id`),
  ADD KEY `idx_kpi_attachments_assignment` (`assignment_id`);

--
-- Indexes for table `kpi_categories`
--
ALTER TABLE `kpi_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `kpi_comments`
--
ALTER TABLE `kpi_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_kpi_comments_assignment` (`assignment_id`),
  ADD KEY `idx_kpi_comments_entry` (`entry_id`),
  ADD KEY `idx_kpi_comments_user` (`user_id`),
  ADD KEY `idx_kpi_comments_resolved` (`is_resolved`);

--
-- Indexes for table `kpi_definitions`
--
ALTER TABLE `kpi_definitions`
  ADD PRIMARY KEY (`kpi_id`),
  ADD UNIQUE KEY `kpi_code` (`kpi_code`),
  ADD KEY `idx_kpi_definitions_category` (`category_id`),
  ADD KEY `idx_kpi_definitions_type` (`kpi_type`),
  ADD KEY `idx_kpi_definitions_active` (`is_active`);

--
-- Indexes for table `kpi_entries`
--
ALTER TABLE `kpi_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `idx_kpi_entries_assignment` (`assignment_id`),
  ADD KEY `idx_kpi_entries_date` (`entry_date`),
  ADD KEY `idx_kpi_entries_period` (`reporting_period`),
  ADD KEY `idx_kpi_entries_status` (`performance_status`),
  ADD KEY `idx_kpi_entries_review` (`review_status`);

--
-- Indexes for table `kpi_history`
--
ALTER TABLE `kpi_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_kpi_history_kpi` (`kpi_id`),
  ADD KEY `idx_kpi_history_assignment` (`assignment_id`),
  ADD KEY `idx_kpi_history_entry` (`entry_id`),
  ADD KEY `idx_kpi_history_action` (`action_type`),
  ADD KEY `idx_kpi_history_performed_by` (`performed_by`);

--
-- Indexes for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  ADD PRIMARY KEY (`target_id`),
  ADD KEY `idx_kpi_targets_assignment` (`assignment_id`),
  ADD KEY `idx_kpi_targets_period` (`target_period_start`,`target_period_end`),
  ADD KEY `idx_kpi_targets_status` (`target_status`);

--
-- Indexes for table `ld_announcement`
--
ALTER TABLE `ld_announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_api_key`
--
ALTER TABLE `ld_api_key`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_audit_log`
--
ALTER TABLE `ld_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_item` (`item_type`,`reference_id`);

--
-- Indexes for table `ld_bookmark`
--
ALTER TABLE `ld_bookmark`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bookmark` (`learner_id`,`item_type`,`reference_id`);

--
-- Indexes for table `ld_calendar_event`
--
ALTER TABLE `ld_calendar_event`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_certificate`
--
ALTER TABLE `ld_certificate`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `verification_code` (`verification_code`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `course_version_id` (`course_version_id`),
  ADD KEY `completed_enrollment_id` (`completed_enrollment_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `ld_certificate_template`
--
ALTER TABLE `ld_certificate_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_comment`
--
ALTER TABLE `ld_comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`);

--
-- Indexes for table `ld_conference_attendance`
--
ALTER TABLE `ld_conference_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `video_conference_id` (`video_conference_id`);

--
-- Indexes for table `ld_course`
--
ALTER TABLE `ld_course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course_instructor`
--
ALTER TABLE `ld_course_instructor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_course_instructor` (`course_id`,`instructor_id`);

--
-- Indexes for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_course_skill` (`course_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_display_preference`
--
ALTER TABLE `ld_display_preference`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `ld_enrollment`
--
ALTER TABLE `ld_enrollment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_learner_course` (`learner_id`,`course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `course_version_id` (`course_version_id`);

--
-- Indexes for table `ld_evaluation`
--
ALTER TABLE `ld_evaluation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_evaluation_feedback`
--
ALTER TABLE `ld_evaluation_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluation_id` (`evaluation_id`);

--
-- Indexes for table `ld_favorite`
--
ALTER TABLE `ld_favorite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_favorite` (`learner_id`,`item_type`,`reference_id`);

--
-- Indexes for table `ld_grade`
--
ALTER TABLE `ld_grade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_integration_event`
--
ALTER TABLE `ld_integration_event`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_module_external_ref` (`module_name`,`external_reference_id`);

--
-- Indexes for table `ld_integration_log`
--
ALTER TABLE `ld_integration_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integration_log_module` (`module_name`,`direction`);

--
-- Indexes for table `ld_learning_path`
--
ALTER TABLE `ld_learning_path`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_learning_path_item`
--
ALTER TABLE `ld_learning_path_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learning_path_id` (`learning_path_id`);

--
-- Indexes for table `ld_lesson`
--
ALTER TABLE `ld_lesson`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `ld_lesson_file`
--
ALTER TABLE `ld_lesson_file`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `ld_message`
--
ALTER TABLE `ld_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_recipient` (`recipient_id`,`is_read`);

--
-- Indexes for table `ld_module`
--
ALTER TABLE `ld_module`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_module_skill`
--
ALTER TABLE `ld_module_skill`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_module_skill` (`module_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `ld_note`
--
ALTER TABLE `ld_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_note_item` (`item_type`,`reference_id`);

--
-- Indexes for table `ld_notification`
--
ALTER TABLE `ld_notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_user` (`user_id`,`is_read`);

--
-- Indexes for table `ld_notification_preference`
--
ALTER TABLE `ld_notification_preference`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_notif_type` (`user_id`,`notification_type`);

--
-- Indexes for table `ld_prerequisite`
--
ALTER TABLE `ld_prerequisite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `required_course_id` (`required_course_id`),
  ADD KEY `required_skill_id` (`required_skill_id`);

--
-- Indexes for table `ld_program`
--
ALTER TABLE `ld_program`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_progress`
--
ALTER TABLE `ld_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `idx_progress_item` (`item_type`,`reference_id`);

--
-- Indexes for table `ld_quiz`
--
ALTER TABLE `ld_quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `ld_quiz_attempt`
--
ALTER TABLE `ld_quiz_attempt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_session_id` (`quiz_session_id`);

--
-- Indexes for table `ld_quiz_question`
--
ALTER TABLE `ld_quiz_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_question_ref` (`item_type`,`reference_id`);

--
-- Indexes for table `ld_quiz_question_option`
--
ALTER TABLE `ld_quiz_question_option`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `ld_quiz_session`
--
ALTER TABLE `ld_quiz_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_session_learner_ref` (`learner_id`,`item_type`,`reference_id`);

--
-- Indexes for table `ld_quiz_session_answer`
--
ALTER TABLE `ld_quiz_session_answer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_session_id` (`quiz_session_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `ld_rating`
--
ALTER TABLE `ld_rating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_learner_course_rating` (`learner_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_report`
--
ALTER TABLE `ld_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_request`
--
ALTER TABLE `ld_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_setting`
--
ALTER TABLE `ld_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `ld_skill`
--
ALTER TABLE `ld_skill`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_video_conference`
--
ALTER TABLE `ld_video_conference`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `payroll_part_time_rates`
--
ALTER TABLE `payroll_part_time_rates`
  ADD PRIMARY KEY (`part_time_rate_id`),
  ADD KEY `idx_ptr_employee` (`employee_id`),
  ADD KEY `idx_ptr_effective_date` (`effective_date`),
  ADD KEY `idx_ptr_status` (`status`);

--
-- Indexes for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD PRIMARY KEY (`personal_info_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `pm_appraisals`
--
ALTER TABLE `pm_appraisals`
  ADD PRIMARY KEY (`appraisal_id`),
  ADD KEY `fk_pm_appraisals_employee` (`employee_id`),
  ADD KEY `fk_pm_appraisals_reviewer` (`reviewer_id`),
  ADD KEY `fk_pm_appraisals_cycle` (`review_cycle_id`);

--
-- Indexes for table `pm_appraisal_history`
--
ALTER TABLE `pm_appraisal_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `fk_pm_appraisal_history_appraisal` (`appraisal_id`);

--
-- Indexes for table `pm_appraisal_items`
--
ALTER TABLE `pm_appraisal_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_pm_appraisal_items_appraisal` (`appraisal_id`);

--
-- Indexes for table `pm_employee_training`
--
ALTER TABLE `pm_employee_training`
  ADD PRIMARY KEY (`employee_training_id`),
  ADD KEY `idx_employee_training_employee` (`employee_id`),
  ADD KEY `idx_employee_training_training` (`training_id`),
  ADD KEY `idx_employee_training_recommendation` (`recommendation_id`);

--
-- Indexes for table `pm_feedback_360_entries`
--
ALTER TABLE `pm_feedback_360_entries`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `pm_feedback_360_entries_idx_employee` (`employee_id`),
  ADD KEY `pm_feedback_360_entries_idx_review_period` (`review_period`),
  ADD KEY `fk_pm_feedback_reviewer` (`reviewer_id`);

--
-- Indexes for table `pm_goals`
--
ALTER TABLE `pm_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `fk_pm_goals_employee` (`employee_id`),
  ADD KEY `fk_pm_goals_supervisor` (`supervisor_id`);

--
-- Indexes for table `pm_goal_approvals`
--
ALTER TABLE `pm_goal_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `idx_goal_approvals_goal_id` (`goal_id`),
  ADD KEY `fk_pm_goal_approvals_supervisor` (`supervisor_id`);

--
-- Indexes for table `pm_goal_attachments`
--
ALTER TABLE `pm_goal_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_goal_attachments_goal_id` (`goal_id`);

--
-- Indexes for table `pm_goal_comments`
--
ALTER TABLE `pm_goal_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_goal_comments_goal_id` (`goal_id`),
  ADD KEY `fk_pm_goal_comments_user` (`user_id`);

--
-- Indexes for table `pm_goal_history`
--
ALTER TABLE `pm_goal_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_goal_history_goal_id` (`goal_id`);

--
-- Indexes for table `pm_goal_progress`
--
ALTER TABLE `pm_goal_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `idx_goal_progress_goal_id` (`goal_id`);

--
-- Indexes for table `pm_performance_reports`
--
ALTER TABLE `pm_performance_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `fk_pm_performance_reports_employee` (`employee_id`),
  ADD KEY `fk_pm_performance_reports_supervisor` (`supervisor_id`);

--
-- Indexes for table `pm_performance_report_actions`
--
ALTER TABLE `pm_performance_report_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `idx_report_actions_report` (`report_id`);

--
-- Indexes for table `pm_performance_report_evaluations`
--
ALTER TABLE `pm_performance_report_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `idx_report_eval_report` (`report_id`);

--
-- Indexes for table `pm_performance_report_kpis`
--
ALTER TABLE `pm_performance_report_kpis`
  ADD PRIMARY KEY (`kpi_item_id`),
  ADD KEY `idx_report_kpis_report` (`report_id`);

--
-- Indexes for table `pm_report_disciplinary_actions`
--
ALTER TABLE `pm_report_disciplinary_actions`
  ADD PRIMARY KEY (`disciplinary_action_id`),
  ADD UNIQUE KEY `action_code` (`action_code`),
  ADD KEY `fk_pm_disciplinary_employee` (`employee_id`);

--
-- Indexes for table `pm_review_cycles`
--
ALTER TABLE `pm_review_cycles`
  ADD PRIMARY KEY (`cycle_id`);

--
-- Indexes for table `pm_training_evaluations`
--
ALTER TABLE `pm_training_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `idx_training_evaluations_employee_training` (`employee_training_id`);

--
-- Indexes for table `pm_training_programs`
--
ALTER TABLE `pm_training_programs`
  ADD PRIMARY KEY (`training_id`),
  ADD UNIQUE KEY `training_code` (`training_code`),
  ADD KEY `idx_training_programs_category` (`training_category`),
  ADD KEY `idx_training_programs_provider` (`training_provider`);

--
-- Indexes for table `pm_training_recommendations`
--
ALTER TABLE `pm_training_recommendations`
  ADD PRIMARY KEY (`recommendation_id`),
  ADD KEY `idx_recommendations_employee_id` (`employee_id`),
  ADD KEY `idx_recommendations_source_id` (`source_id`);

--
-- Indexes for table `pr_contribution_rates`
--
ALTER TABLE `pr_contribution_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pr_deductions`
--
ALTER TABLE `pr_deductions`
  ADD PRIMARY KEY (`deduction_id`);

--
-- Indexes for table `pr_employee_adjustments`
--
ALTER TABLE `pr_employee_adjustments`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD KEY `fk_employee_adjustments_employee` (`employee_id`),
  ADD KEY `fk_employee_adjustments_payroll_period` (`period_id`);

--
-- Indexes for table `pr_employee_benefits`
--
ALTER TABLE `pr_employee_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_employee_benefits` (`employee_id`);

--
-- Indexes for table `pr_employee_deductions`
--
ALTER TABLE `pr_employee_deductions`
  ADD PRIMARY KEY (`employee_deduction_id`),
  ADD KEY `fk_employee_deductions` (`employee_id`),
  ADD KEY `fk_employee_deductions_deduction` (`deduction_id`);

--
-- Indexes for table `pr_final_settlements`
--
ALTER TABLE `pr_final_settlements`
  ADD PRIMARY KEY (`settlement_id`),
  ADD KEY `idx_final_settlement_employee` (`employee_id`),
  ADD KEY `idx_final_settlement_exit` (`exit_settlement_id`),
  ADD KEY `idx_final_settlement_case` (`exit_case_type`,`exit_case_id`),
  ADD KEY `idx_final_settlement_status` (`status`),
  ADD KEY `idx_final_settlement_date` (`settlement_date`);

--
-- Indexes for table `pr_final_settlement_items`
--
ALTER TABLE `pr_final_settlement_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_final_item_settlement` (`settlement_id`),
  ADD KEY `idx_final_item_type` (`item_type`),
  ADD KEY `idx_final_item_category` (`item_category`);

--
-- Indexes for table `pr_pagibig_rates`
--
ALTER TABLE `pr_pagibig_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagibig_salary` (`min_salary`,`max_salary`,`effective_from`);

--
-- Indexes for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  ADD PRIMARY KEY (`payslip_id`),
  ADD KEY `fk_employee_payslips` (`employee_id`),
  ADD KEY `fk_employee_runs` (`run_id`);

--
-- Indexes for table `pr_payslip_items`
--
ALTER TABLE `pr_payslip_items`
  ADD PRIMARY KEY (`payslip_item_id`),
  ADD KEY `fk_payslip_items` (`payslip_id`);

--
-- Indexes for table `pr_periods`
--
ALTER TABLE `pr_periods`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `pr_philhealth_rates`
--
ALTER TABLE `pr_philhealth_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_philhealth_salary` (`min_salary`,`max_salary`,`effective_from`);

--
-- Indexes for table `pr_position_deduction_rates`
--
ALTER TABLE `pr_position_deduction_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pr_runs`
--
ALTER TABLE `pr_runs`
  ADD PRIMARY KEY (`run_id`),
  ADD KEY `fk_runs` (`period_id`);

--
-- Indexes for table `pr_sss_contribution_rates`
--
ALTER TABLE `pr_sss_contribution_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sss_compensation` (`min_compensation`,`max_compensation`,`effective_from`);

--
-- Indexes for table `pr_tax_tables`
--
ALTER TABLE `pr_tax_tables`
  ADD PRIMARY KEY (`tax_id`),
  ADD KEY `idx_tax_lookup` (`pay_frequency`,`min_income`,`max_income`,`effective_from`,`is_active`);

--
-- Indexes for table `pr_teacher_qualification_rates`
--
ALTER TABLE `pr_teacher_qualification_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qualification` (`qualification`),
  ADD KEY `idx_qualification` (`qualification`);

--
-- Indexes for table `sss_contributions`
--
ALTER TABLE `sss_contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sss_contribution_employee` (`employee_id`);

--
-- Indexes for table `ta_absence_late_policies`
--
ALTER TABLE `ta_absence_late_policies`
  ADD PRIMARY KEY (`policy_id`);

--
-- Indexes for table `ta_absence_late_records`
--
ALTER TABLE `ta_absence_late_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_date` (`absence_date`),
  ADD KEY `idx_status` (`excuse_status`);

--
-- Indexes for table `ta_absence_late_thresholds`
--
ALTER TABLE `ta_absence_late_thresholds`
  ADD PRIMARY KEY (`threshold_id`),
  ADD KEY `fk_ta_absence_late_thresholds_employee` (`employee_id`);

--
-- Indexes for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_ta_attendance_employee` (`employee_id`),
  ADD KEY `fk_ta_attendance_shift` (`shift_id`),
  ADD KEY `fk_ta_attendance_leave_request` (`leave_request_id`),
  ADD KEY `fk_ta_attendance_approved_by` (`approved_by`);

--
-- Indexes for table `ta_employee_shifts`
--
ALTER TABLE `ta_employee_shifts`
  ADD PRIMARY KEY (`employee_shift_id`),
  ADD KEY `fk_ta_employee_shifts_employee` (`employee_id`),
  ADD KEY `fk_ta_employee_shifts_shift` (`shift_id`);

--
-- Indexes for table `ta_flexible_schedules`
--
ALTER TABLE `ta_flexible_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ta_flexible_schedules_employee` (`employee_id`),
  ADD KEY `fk_ta_flexible_schedules_created_by` (`created_by`);

--
-- Indexes for table `ta_holidays`
--
ALTER TABLE `ta_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ta_holidays_created_by` (`created_by`);

--
-- Indexes for table `ta_holiday_sync_log`
--
ALTER TABLE `ta_holiday_sync_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ta_leave_balances`
--
ALTER TABLE `ta_leave_balances`
  ADD PRIMARY KEY (`leave_balance_id`),
  ADD KEY `fk_ta_leave_balances_employee` (`employee_id`),
  ADD KEY `fk_ta_leave_balances_leave_type` (`leave_type_id`);

--
-- Indexes for table `ta_leave_requests`
--
ALTER TABLE `ta_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ta_leave_requests_employee` (`employee_id`),
  ADD KEY `fk_ta_leave_requests_leave_type` (`leave_type_id`);

--
-- Indexes for table `ta_leave_types`
--
ALTER TABLE `ta_leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `ta_shifts`
--
ALTER TABLE `ta_shifts`
  ADD PRIMARY KEY (`shift_id`);

--
-- Indexes for table `ta_shift_assignments`
--
ALTER TABLE `ta_shift_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ta_shift_assignments_employee` (`employee_id`),
  ADD KEY `fk_ta_shift_assignments_shift` (`shift_id`),
  ADD KEY `fk_ta_shift_assignments_created_by` (`created_by`);

--
-- Indexes for table `ta_shift_exclusions`
--
ALTER TABLE `ta_shift_exclusions`
  ADD PRIMARY KEY (`exclusion_id`),
  ADD KEY `fk_ta_shift_exclusions_employee_shift` (`employee_shift_id`);

--
-- Indexes for table `ta_shift_weekday_times`
--
ALTER TABLE `ta_shift_weekday_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `weekday` (`weekday`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_user_employee` (`employee_id`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- Indexes for table `wfa_age_distribution`
--
ALTER TABLE `wfa_age_distribution`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_age_distribution` (`metric_date`,`age_group`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_age_group` (`age_group`);

--
-- Indexes for table `wfa_attrition_tracking`
--
ALTER TABLE `wfa_attrition_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_separation_date` (`separation_date`),
  ADD KEY `idx_separation_type` (`separation_type`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `fk_wfa_attrition_department` (`department_id`);

--
-- Indexes for table `wfa_audit_log`
--
ALTER TABLE `wfa_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `wfa_compensation_analysis`
--
ALTER TABLE `wfa_compensation_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_compensation` (`department_id`,`position_id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_position` (`position_id`);

--
-- Indexes for table `wfa_custom_filters`
--
ALTER TABLE `wfa_custom_filters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_filter_name` (`filter_name`);

--
-- Indexes for table `wfa_department_analytics`
--
ALTER TABLE `wfa_department_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_date` (`department_id`,`metric_date`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `wfa_diversity_metrics`
--
ALTER TABLE `wfa_diversity_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_diversity` (`metric_date`,`department_id`,`diversity_category`,`category_value`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_diversity_category` (`diversity_category`);

--
-- Indexes for table `wfa_employee_metrics`
--
ALTER TABLE `wfa_employee_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_metric_date` (`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `wfa_gender_distribution`
--
ALTER TABLE `wfa_gender_distribution`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gender_distribution` (`metric_date`,`gender`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_gender` (`gender`);

--
-- Indexes for table `wfa_headcount_planning`
--
ALTER TABLE `wfa_headcount_planning`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_headcount_plan` (`department_id`,`fiscal_year`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`);

--
-- Indexes for table `wfa_monthly_attrition`
--
ALTER TABLE `wfa_monthly_attrition`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_year_month` (`year_month`),
  ADD KEY `idx_year_month` (`year_month`);

--
-- Indexes for table `wfa_performance_distribution`
--
ALTER TABLE `wfa_performance_distribution`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_performance_dist` (`metric_date`,`performance_level`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_performance_level` (`performance_level`);

--
-- Indexes for table `wfa_reports`
--
ALTER TABLE `wfa_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_report_date` (`report_date`),
  ADD KEY `idx_generated_by` (`generated_by`);

--
-- Indexes for table `wfa_risk_assessment`
--
ALTER TABLE `wfa_risk_assessment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_risk_level` (`risk_level`),
  ADD KEY `idx_risk_score` (`risk_score`);

--
-- Indexes for table `wfa_salary_statistics`
--
ALTER TABLE `wfa_salary_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_salary_stats` (`metric_date`,`department_id`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_department` (`department_id`);

--
-- Indexes for table `wfa_skill_gap_analysis`
--
ALTER TABLE `wfa_skill_gap_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_skill_gap` (`department_id`,`skill_name`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_skill_name` (`skill_name`),
  ADD KEY `idx_priority_level` (`priority_level`);

--
-- Indexes for table `wfa_tenure_analysis`
--
ALTER TABLE `wfa_tenure_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tenure_analysis` (`metric_date`,`tenure_bracket`),
  ADD KEY `idx_metric_date` (`metric_date`),
  ADD KEY `idx_tenure_bracket` (`tenure_bracket`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cm_clinic_reports`
--
ALTER TABLE `cm_clinic_reports`
  MODIFY `report_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_document_attachments`
--
ALTER TABLE `cm_document_attachments`
  MODIFY `attachment_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_emergency_cases`
--
ALTER TABLE `cm_emergency_cases`
  MODIFY `case_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_medical_records`
--
ALTER TABLE `cm_medical_records`
  MODIFY `record_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_medicine_inventory`
--
ALTER TABLE `cm_medicine_inventory`
  MODIFY `medicine_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cm_medicine_usage_logs`
--
ALTER TABLE `cm_medicine_usage_logs`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_patients`
--
ALTER TABLE `cm_patients`
  MODIFY `patient_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cm_suppliers`
--
ALTER TABLE `cm_suppliers`
  MODIFY `supplier_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cm_vital_signs`
--
ALTER TABLE `cm_vital_signs`
  MODIFY `vital_sign_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  MODIFY `eer_announcements_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `eer_award_history`
--
ALTER TABLE `eer_award_history`
  MODIFY `eer_award_history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `eer_badges`
--
ALTER TABLE `eer_badges`
  MODIFY `eer_badge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `eer_comments`
--
ALTER TABLE `eer_comments`
  MODIFY `eer_comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `eer_employee_badges`
--
ALTER TABLE `eer_employee_badges`
  MODIFY `eer_employee_badge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `eer_forums`
--
ALTER TABLE `eer_forums`
  MODIFY `eer_forum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eer_grievances`
--
ALTER TABLE `eer_grievances`
  MODIFY `eer_grievance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eer_grievance_attendance_links`
--
ALTER TABLE `eer_grievance_attendance_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eer_grievance_updates`
--
ALTER TABLE `eer_grievance_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `eer_groups`
--
ALTER TABLE `eer_groups`
  MODIFY `eer_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `eer_group_members`
--
ALTER TABLE `eer_group_members`
  MODIFY `eer_group_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `eer_messages`
--
ALTER TABLE `eer_messages`
  MODIFY `eer_message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eer_notifications`
--
ALTER TABLE `eer_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `eer_policies`
--
ALTER TABLE `eer_policies`
  MODIFY `eer_policy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eer_projects`
--
ALTER TABLE `eer_projects`
  MODIFY `eer_project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `eer_reactions`
--
ALTER TABLE `eer_reactions`
  MODIFY `eer_reaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `eer_recognitions`
--
ALTER TABLE `eer_recognitions`
  MODIFY `eer_recognition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `eer_replies`
--
ALTER TABLE `eer_replies`
  MODIFY `eer_reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `eer_rewards`
--
ALTER TABLE `eer_rewards`
  MODIFY `eer_reward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `eer_reward_redemptions`
--
ALTER TABLE `eer_reward_redemptions`
  MODIFY `eer_reward_redemption_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `eer_social_posts`
--
ALTER TABLE `eer_social_posts`
  MODIFY `eer_social_post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `eer_surveys`
--
ALTER TABLE `eer_surveys`
  MODIFY `eer_survey_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `eer_survey_answers`
--
ALTER TABLE `eer_survey_answers`
  MODIFY `eer_survey_answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `eer_survey_feedback`
--
ALTER TABLE `eer_survey_feedback`
  MODIFY `eer_survey_feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `eer_survey_feedback_id`
--
ALTER TABLE `eer_survey_feedback_id`
  MODIFY `eer_survey_feedback_id_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eer_survey_questions`
--
ALTER TABLE `eer_survey_questions`
  MODIFY `eer_survey_question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `eer_survey_responses`
--
ALTER TABLE `eer_survey_responses`
  MODIFY `eer_survey_response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `eer_survey_targets`
--
ALTER TABLE `eer_survey_targets`
  MODIFY `eer_survey_target_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  MODIFY `cert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_change_history`
--
ALTER TABLE `employee_change_history`
  MODIFY `change_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  MODIFY `dependent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- AUTO_INCREMENT for table `em_departments`
--
ALTER TABLE `em_departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `em_education`
--
ALTER TABLE `em_education`
  MODIFY `education_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `em_roles`
--
ALTER TABLE `em_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ep_online_meetings`
--
ALTER TABLE `ep_online_meetings`
  MODIFY `meetings_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ep_payroll_request`
--
ALTER TABLE `ep_payroll_request`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `exit_archive`
--
ALTER TABLE `exit_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `exit_documents`
--
ALTER TABLE `exit_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  MODIFY `settlement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `exit_interview_hr_assessments`
--
ALTER TABLE `exit_interview_hr_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- AUTO_INCREMENT for table `kpi_assignments`
--
ALTER TABLE `kpi_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_categories`
--
ALTER TABLE `kpi_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_comments`
--
ALTER TABLE `kpi_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_definitions`
--
ALTER TABLE `kpi_definitions`
  MODIFY `kpi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_entries`
--
ALTER TABLE `kpi_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_history`
--
ALTER TABLE `kpi_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  MODIFY `target_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_announcement`
--
ALTER TABLE `ld_announcement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_api_key`
--
ALTER TABLE `ld_api_key`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_audit_log`
--
ALTER TABLE `ld_audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_bookmark`
--
ALTER TABLE `ld_bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_calendar_event`
--
ALTER TABLE `ld_calendar_event`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_certificate`
--
ALTER TABLE `ld_certificate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_certificate_template`
--
ALTER TABLE `ld_certificate_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_comment`
--
ALTER TABLE `ld_comment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_conference_attendance`
--
ALTER TABLE `ld_conference_attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_course`
--
ALTER TABLE `ld_course`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_course_instructor`
--
ALTER TABLE `ld_course_instructor`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_display_preference`
--
ALTER TABLE `ld_display_preference`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_enrollment`
--
ALTER TABLE `ld_enrollment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_evaluation`
--
ALTER TABLE `ld_evaluation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_evaluation_feedback`
--
ALTER TABLE `ld_evaluation_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_favorite`
--
ALTER TABLE `ld_favorite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_grade`
--
ALTER TABLE `ld_grade`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_integration_event`
--
ALTER TABLE `ld_integration_event`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_integration_log`
--
ALTER TABLE `ld_integration_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_learning_path`
--
ALTER TABLE `ld_learning_path`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_learning_path_item`
--
ALTER TABLE `ld_learning_path_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_lesson`
--
ALTER TABLE `ld_lesson`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_lesson_file`
--
ALTER TABLE `ld_lesson_file`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_message`
--
ALTER TABLE `ld_message`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_module`
--
ALTER TABLE `ld_module`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_module_skill`
--
ALTER TABLE `ld_module_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_note`
--
ALTER TABLE `ld_note`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_notification`
--
ALTER TABLE `ld_notification`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_notification_preference`
--
ALTER TABLE `ld_notification_preference`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_prerequisite`
--
ALTER TABLE `ld_prerequisite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_program`
--
ALTER TABLE `ld_program`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_progress`
--
ALTER TABLE `ld_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz`
--
ALTER TABLE `ld_quiz`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz_attempt`
--
ALTER TABLE `ld_quiz_attempt`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz_question`
--
ALTER TABLE `ld_quiz_question`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz_question_option`
--
ALTER TABLE `ld_quiz_question_option`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz_session`
--
ALTER TABLE `ld_quiz_session`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_quiz_session_answer`
--
ALTER TABLE `ld_quiz_session_answer`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_rating`
--
ALTER TABLE `ld_rating`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_report`
--
ALTER TABLE `ld_report`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_request`
--
ALTER TABLE `ld_request`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_setting`
--
ALTER TABLE `ld_setting`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ld_skill`
--
ALTER TABLE `ld_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ld_video_conference`
--
ALTER TABLE `ld_video_conference`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_part_time_rates`
--
ALTER TABLE `payroll_part_time_rates`
  MODIFY `part_time_rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `personal_information`
--
ALTER TABLE `personal_information`
  MODIFY `personal_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pm_appraisals`
--
ALTER TABLE `pm_appraisals`
  MODIFY `appraisal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_appraisal_history`
--
ALTER TABLE `pm_appraisal_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_appraisal_items`
--
ALTER TABLE `pm_appraisal_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_employee_training`
--
ALTER TABLE `pm_employee_training`
  MODIFY `employee_training_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_feedback_360_entries`
--
ALTER TABLE `pm_feedback_360_entries`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goals`
--
ALTER TABLE `pm_goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goal_approvals`
--
ALTER TABLE `pm_goal_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goal_attachments`
--
ALTER TABLE `pm_goal_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goal_comments`
--
ALTER TABLE `pm_goal_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goal_history`
--
ALTER TABLE `pm_goal_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_goal_progress`
--
ALTER TABLE `pm_goal_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_performance_reports`
--
ALTER TABLE `pm_performance_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_performance_report_actions`
--
ALTER TABLE `pm_performance_report_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_performance_report_evaluations`
--
ALTER TABLE `pm_performance_report_evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_performance_report_kpis`
--
ALTER TABLE `pm_performance_report_kpis`
  MODIFY `kpi_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_report_disciplinary_actions`
--
ALTER TABLE `pm_report_disciplinary_actions`
  MODIFY `disciplinary_action_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_review_cycles`
--
ALTER TABLE `pm_review_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_training_evaluations`
--
ALTER TABLE `pm_training_evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_training_programs`
--
ALTER TABLE `pm_training_programs`
  MODIFY `training_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_training_recommendations`
--
ALTER TABLE `pm_training_recommendations`
  MODIFY `recommendation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pr_contribution_rates`
--
ALTER TABLE `pr_contribution_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pr_employee_adjustments`
--
ALTER TABLE `pr_employee_adjustments`
  MODIFY `adjustment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pr_employee_benefits`
--
ALTER TABLE `pr_employee_benefits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pr_employee_deductions`
--
ALTER TABLE `pr_employee_deductions`
  MODIFY `employee_deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pr_final_settlements`
--
ALTER TABLE `pr_final_settlements`
  MODIFY `settlement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pr_final_settlement_items`
--
ALTER TABLE `pr_final_settlement_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pr_pagibig_rates`
--
ALTER TABLE `pr_pagibig_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  MODIFY `payslip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `pr_payslip_items`
--
ALTER TABLE `pr_payslip_items`
  MODIFY `payslip_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `pr_periods`
--
ALTER TABLE `pr_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pr_philhealth_rates`
--
ALTER TABLE `pr_philhealth_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pr_position_deduction_rates`
--
ALTER TABLE `pr_position_deduction_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pr_runs`
--
ALTER TABLE `pr_runs`
  MODIFY `run_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pr_sss_contribution_rates`
--
ALTER TABLE `pr_sss_contribution_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `pr_tax_tables`
--
ALTER TABLE `pr_tax_tables`
  MODIFY `tax_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pr_teacher_qualification_rates`
--
ALTER TABLE `pr_teacher_qualification_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sss_contributions`
--
ALTER TABLE `sss_contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `ta_absence_late_policies`
--
ALTER TABLE `ta_absence_late_policies`
  MODIFY `policy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ta_absence_late_records`
--
ALTER TABLE `ta_absence_late_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ta_absence_late_thresholds`
--
ALTER TABLE `ta_absence_late_thresholds`
  MODIFY `threshold_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `ta_holiday_sync_log`
--
ALTER TABLE `ta_holiday_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ta_leave_balances`
--
ALTER TABLE `ta_leave_balances`
  MODIFY `leave_balance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ta_leave_requests`
--
ALTER TABLE `ta_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ta_shifts`
--
ALTER TABLE `ta_shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ta_shift_assignments`
--
ALTER TABLE `ta_shift_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wfa_age_distribution`
--
ALTER TABLE `wfa_age_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_attrition_tracking`
--
ALTER TABLE `wfa_attrition_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_audit_log`
--
ALTER TABLE `wfa_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_compensation_analysis`
--
ALTER TABLE `wfa_compensation_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_custom_filters`
--
ALTER TABLE `wfa_custom_filters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_department_analytics`
--
ALTER TABLE `wfa_department_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_diversity_metrics`
--
ALTER TABLE `wfa_diversity_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_employee_metrics`
--
ALTER TABLE `wfa_employee_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_gender_distribution`
--
ALTER TABLE `wfa_gender_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_headcount_planning`
--
ALTER TABLE `wfa_headcount_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_monthly_attrition`
--
ALTER TABLE `wfa_monthly_attrition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_performance_distribution`
--
ALTER TABLE `wfa_performance_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_reports`
--
ALTER TABLE `wfa_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_risk_assessment`
--
ALTER TABLE `wfa_risk_assessment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_salary_statistics`
--
ALTER TABLE `wfa_salary_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_skill_gap_analysis`
--
ALTER TABLE `wfa_skill_gap_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wfa_tenure_analysis`
--
ALTER TABLE `wfa_tenure_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cm_clinic_reports`
--
ALTER TABLE `cm_clinic_reports`
  ADD CONSTRAINT `cm_clinic_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cm_document_attachments`
--
ALTER TABLE `cm_document_attachments`
  ADD CONSTRAINT `cm_document_attachments_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `cm_medical_records` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cm_document_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cm_emergency_cases`
--
ALTER TABLE `cm_emergency_cases`
  ADD CONSTRAINT `cm_emergency_cases_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `cm_patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cm_emergency_cases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cm_medical_records`
--
ALTER TABLE `cm_medical_records`
  ADD CONSTRAINT `cm_medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `cm_patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cm_medical_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cm_medicine_inventory`
--
ALTER TABLE `cm_medicine_inventory`
  ADD CONSTRAINT `cm_medicine_inventory_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cm_medicine_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `cm_suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cm_medicine_usage_logs`
--
ALTER TABLE `cm_medicine_usage_logs`
  ADD CONSTRAINT `cm_medicine_usage_logs_ibfk_1` FOREIGN KEY (`used_by`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cm_usage_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `cm_medicine_inventory` (`medicine_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_usage_record` FOREIGN KEY (`record_id`) REFERENCES `cm_medical_records` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `cm_patients`
--
ALTER TABLE `cm_patients`
  ADD CONSTRAINT `fk_cm_patients_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `cm_vital_signs`
--
ALTER TABLE `cm_vital_signs`
  ADD CONSTRAINT `cm_vital_signs_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `cm_medical_records` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cm_vital_signs_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `eer_announcements`
--
ALTER TABLE `eer_announcements`
  ADD CONSTRAINT `fk_eer_announcements_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_award_history`
--
ALTER TABLE `eer_award_history`
  ADD CONSTRAINT `fk_eer_award_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_award_history_nominated_by` FOREIGN KEY (`nominated_by`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_award_votes`
--
ALTER TABLE `eer_award_votes`
  ADD CONSTRAINT `fk_eer_award_votes_award` FOREIGN KEY (`award_history_id`) REFERENCES `eer_award_history` (`eer_award_history_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_award_votes_nominee` FOREIGN KEY (`nominee_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_award_votes_voter` FOREIGN KEY (`voter_user_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_comments`
--
ALTER TABLE `eer_comments`
  ADD CONSTRAINT `fk_eer_comments_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_comments_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_employee_badges`
--
ALTER TABLE `eer_employee_badges`
  ADD CONSTRAINT `fk_eer_employee_badges_awarded_by` FOREIGN KEY (`awarded_by`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_employee_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `eer_badges` (`eer_badge_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_employee_badges_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_forums`
--
ALTER TABLE `eer_forums`
  ADD CONSTRAINT `fk_eer_forums_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_grievances`
--
ALTER TABLE `eer_grievances`
  ADD CONSTRAINT `fk_eer_grievances_created_by` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_grievances_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_grievance_attendance_links`
--
ALTER TABLE `eer_grievance_attendance_links`
  ADD CONSTRAINT `fk_eer_grievance_attendance_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `ta_attendance` (`attendance_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_grievance_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_grievance_attendance_grievance` FOREIGN KEY (`grievance_id`) REFERENCES `eer_grievances` (`eer_grievance_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_grievance_payroll`
--
ALTER TABLE `eer_grievance_payroll`
  ADD CONSTRAINT `fk_eer_grievance_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `eer_grievance_updates`
--
ALTER TABLE `eer_grievance_updates`
  ADD CONSTRAINT `fk_eer_grievance_updates_employee` FOREIGN KEY (`updated_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_grievance_updates_grievance` FOREIGN KEY (`grievance_id`) REFERENCES `eer_grievances` (`eer_grievance_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_groups`
--
ALTER TABLE `eer_groups`
  ADD CONSTRAINT `fk_eer_groups_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_group_members`
--
ALTER TABLE `eer_group_members`
  ADD CONSTRAINT `fk_eer_group_members_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_group_members_group` FOREIGN KEY (`group_id`) REFERENCES `eer_groups` (`eer_group_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_messages`
--
ALTER TABLE `eer_messages`
  ADD CONSTRAINT `fk_eer_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_notifications`
--
ALTER TABLE `eer_notifications`
  ADD CONSTRAINT `fk_eer_notifications_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_policies`
--
ALTER TABLE `eer_policies`
  ADD CONSTRAINT `fk_eer_policies_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_projects`
--
ALTER TABLE `eer_projects`
  ADD CONSTRAINT `fk_eer_projects_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_reactions`
--
ALTER TABLE `eer_reactions`
  ADD CONSTRAINT `fk_eer_reactions_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_recognitions`
--
ALTER TABLE `eer_recognitions`
  ADD CONSTRAINT `fk_eer_recognitions_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_recognitions_sender` FOREIGN KEY (`sender_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_replies`
--
ALTER TABLE `eer_replies`
  ADD CONSTRAINT `fk_eer_replies_comment` FOREIGN KEY (`comment_id`) REFERENCES `eer_comments` (`eer_comment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_replies_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_replies_mentioned_user` FOREIGN KEY (`mentioned_user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_replies_parent` FOREIGN KEY (`parent_reply_id`) REFERENCES `eer_replies` (`eer_reply_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_replies_post` FOREIGN KEY (`post_id`) REFERENCES `eer_social_posts` (`eer_social_post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_replies_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_reward_redemptions`
--
ALTER TABLE `eer_reward_redemptions`
  ADD CONSTRAINT `fk_eer_reward_redemptions_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_reward_redemptions_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_reward_redemptions_reward` FOREIGN KEY (`reward_id`) REFERENCES `eer_rewards` (`eer_reward_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_social_posts`
--
ALTER TABLE `eer_social_posts`
  ADD CONSTRAINT `fk_eer_social_posts_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_social_posts_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_surveys`
--
ALTER TABLE `eer_surveys`
  ADD CONSTRAINT `fk_eer_surveys_created_by` FOREIGN KEY (`created_by_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_answers`
--
ALTER TABLE `eer_survey_answers`
  ADD CONSTRAINT `fk_eer_survey_answers_question` FOREIGN KEY (`question_id`) REFERENCES `eer_survey_questions` (`eer_survey_question_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_survey_answers_response` FOREIGN KEY (`response_id`) REFERENCES `eer_survey_responses` (`eer_survey_response_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_feedback`
--
ALTER TABLE `eer_survey_feedback`
  ADD CONSTRAINT `fk_eer_survey_feedback_survey` FOREIGN KEY (`survey_id`) REFERENCES `eer_surveys` (`eer_survey_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_feedback_id`
--
ALTER TABLE `eer_survey_feedback_id`
  ADD CONSTRAINT `fk_eer_survey_feedback_id_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_survey_feedback_id_survey` FOREIGN KEY (`survey_id`) REFERENCES `eer_surveys` (`eer_survey_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_questions`
--
ALTER TABLE `eer_survey_questions`
  ADD CONSTRAINT `fk_eer_survey_questions_survey` FOREIGN KEY (`survey_id`) REFERENCES `eer_surveys` (`eer_survey_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_responses`
--
ALTER TABLE `eer_survey_responses`
  ADD CONSTRAINT `fk_eer_survey_responses_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_survey_responses_survey` FOREIGN KEY (`survey_id`) REFERENCES `eer_surveys` (`eer_survey_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_survey_responses_target_employee` FOREIGN KEY (`target_employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eer_survey_targets`
--
ALTER TABLE `eer_survey_targets`
  ADD CONSTRAINT `fk_eer_survey_targets_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eer_survey_targets_survey` FOREIGN KEY (`survey_id`) REFERENCES `eer_surveys` (`eer_survey_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_certifications`
--
ALTER TABLE `employee_certifications`
  ADD CONSTRAINT `employee_certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_change_history`
--
ALTER TABLE `employee_change_history`
  ADD CONSTRAINT `employee_change_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_dependents`
--
ALTER TABLE `employee_dependents`
  ADD CONSTRAINT `employee_dependents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_emergency_contacts`
--
ALTER TABLE `employee_emergency_contacts`
  ADD CONSTRAINT `employee_emergency_contacts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_languages`
--
ALTER TABLE `employee_languages`
  ADD CONSTRAINT `employee_languages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  ADD CONSTRAINT `fk_requirements_document` FOREIGN KEY (`document_id`) REFERENCES `employee_documents` (`document_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requirements_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_work_experience`
--
ALTER TABLE `employee_work_experience`
  ADD CONSTRAINT `employee_work_experience_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD CONSTRAINT `employment_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `em_education`
--
ALTER TABLE `em_education`
  ADD CONSTRAINT `em_education_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `em_employees`
--
ALTER TABLE `em_employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`);

--
-- Constraints for table `em_positions`
--
ALTER TABLE `em_positions`
  ADD CONSTRAINT `em_positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `exit_archive`
--
ALTER TABLE `exit_archive`
  ADD CONSTRAINT `fk_exit_archive_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD CONSTRAINT `fk_exit_documents_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  ADD CONSTRAINT `fk_exit_settlements_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD CONSTRAINT `fk_exit_interviews_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exit_interview_hr_assessments`
--
ALTER TABLE `exit_interview_hr_assessments`
  ADD CONSTRAINT `fk_exit_hr_assessments_interview` FOREIGN KEY (`interview_id`) REFERENCES `exit_interviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exit_knowledge_transfer_items`
--
ALTER TABLE `exit_knowledge_transfer_items`
  ADD CONSTRAINT `fk_exit_transfer_items_plan` FOREIGN KEY (`plan_id`) REFERENCES `exit_knowledge_transfer_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exit_knowledge_transfer_plans`
--
ALTER TABLE `exit_knowledge_transfer_plans`
  ADD CONSTRAINT `fk_exit_transfer_plans_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exit_transfer_plans_successor` FOREIGN KEY (`successor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `exit_resignations`
--
ALTER TABLE `exit_resignations`
  ADD CONSTRAINT `fk_exit_resignations_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exit_surveys`
--
ALTER TABLE `exit_surveys`
  ADD CONSTRAINT `fk_exit_surveys_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `exit_survey_answers`
--
ALTER TABLE `exit_survey_answers`
  ADD CONSTRAINT `fk_exit_survey_answers_question_id_exit_survey_questions` FOREIGN KEY (`question_id`) REFERENCES `exit_survey_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exit_survey_answers_response_id_exit_survey_responses` FOREIGN KEY (`response_id`) REFERENCES `exit_survey_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exit_survey_questions`
--
ALTER TABLE `exit_survey_questions`
  ADD CONSTRAINT `fk_exit_survey_questions_survey_id_exit_surveys` FOREIGN KEY (`survey_id`) REFERENCES `exit_surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exit_survey_responses`
--
ALTER TABLE `exit_survey_responses`
  ADD CONSTRAINT `fk_exit_survey_responses_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exit_survey_responses_survey_id_exit_surveys` FOREIGN KEY (`survey_id`) REFERENCES `exit_surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exit_terminations`
--
ALTER TABLE `exit_terminations`
  ADD CONSTRAINT `fk_exit_terminations_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `family_background`
--
ALTER TABLE `family_background`
  ADD CONSTRAINT `family_background_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `government_ids`
--
ALTER TABLE `government_ids`
  ADD CONSTRAINT `government_ids_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_assignments`
--
ALTER TABLE `kpi_assignments`
  ADD CONSTRAINT `kpi_assignments_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_definitions` (`kpi_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_assignments_ibfk_2` FOREIGN KEY (`assignee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `kpi_assignments_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_attachments`
--
ALTER TABLE `kpi_attachments`
  ADD CONSTRAINT `kpi_attachments_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `kpi_entries` (`entry_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_attachments_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `kpi_assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_comments`
--
ALTER TABLE `kpi_comments`
  ADD CONSTRAINT `kpi_comments_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `kpi_entries` (`entry_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_comments_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `kpi_assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_comments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_definitions`
--
ALTER TABLE `kpi_definitions`
  ADD CONSTRAINT `fk_kpi_definition_category` FOREIGN KEY (`category_id`) REFERENCES `kpi_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_entries`
--
ALTER TABLE `kpi_entries`
  ADD CONSTRAINT `fk_kpi_entries_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `kpi_assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_history`
--
ALTER TABLE `kpi_history`
  ADD CONSTRAINT `kpi_history_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `kpi_entries` (`entry_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_history_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `kpi_assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpi_history_ibfk_3` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_definitions` (`kpi_id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_targets`
--
ALTER TABLE `kpi_targets`
  ADD CONSTRAINT `fk_kpi_targets_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `kpi_assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_certificate`
--
ALTER TABLE `ld_certificate`
  ADD CONSTRAINT `ld_certificate_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_certificate_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ld_certificate_ibfk_3` FOREIGN KEY (`completed_enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_certificate_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `ld_certificate_template` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ld_certificate_template`
--
ALTER TABLE `ld_certificate_template`
  ADD CONSTRAINT `ld_certificate_template_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ld_comment`
--
ALTER TABLE `ld_comment`
  ADD CONSTRAINT `ld_comment_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_comment_ibfk_2` FOREIGN KEY (`parent_comment_id`) REFERENCES `ld_comment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_conference_attendance`
--
ALTER TABLE `ld_conference_attendance`
  ADD CONSTRAINT `ld_conference_attendance_ibfk_1` FOREIGN KEY (`video_conference_id`) REFERENCES `ld_video_conference` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_course_instructor`
--
ALTER TABLE `ld_course_instructor`
  ADD CONSTRAINT `ld_course_instructor_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  ADD CONSTRAINT `ld_course_skill_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_course_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  ADD CONSTRAINT `ld_course_version_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_enrollment`
--
ALTER TABLE `ld_enrollment`
  ADD CONSTRAINT `ld_enrollment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_enrollment_ibfk_2` FOREIGN KEY (`course_version_id`) REFERENCES `ld_course_version` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ld_evaluation`
--
ALTER TABLE `ld_evaluation`
  ADD CONSTRAINT `ld_evaluation_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_evaluation_feedback`
--
ALTER TABLE `ld_evaluation_feedback`
  ADD CONSTRAINT `ld_evaluation_feedback_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `ld_evaluation` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_grade`
--
ALTER TABLE `ld_grade`
  ADD CONSTRAINT `ld_grade_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_learning_path_item`
--
ALTER TABLE `ld_learning_path_item`
  ADD CONSTRAINT `ld_learning_path_item_ibfk_1` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_lesson`
--
ALTER TABLE `ld_lesson`
  ADD CONSTRAINT `ld_lesson_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_lesson_file`
--
ALTER TABLE `ld_lesson_file`
  ADD CONSTRAINT `ld_lesson_file_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `ld_lesson` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_module`
--
ALTER TABLE `ld_module`
  ADD CONSTRAINT `ld_module_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_module_skill`
--
ALTER TABLE `ld_module_skill`
  ADD CONSTRAINT `ld_module_skill_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_module_skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_prerequisite`
--
ALTER TABLE `ld_prerequisite`
  ADD CONSTRAINT `ld_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_prerequisite_ibfk_2` FOREIGN KEY (`required_course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_prerequisite_ibfk_3` FOREIGN KEY (`required_skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_progress`
--
ALTER TABLE `ld_progress`
  ADD CONSTRAINT `ld_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `ld_enrollment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_quiz`
--
ALTER TABLE `ld_quiz`
  ADD CONSTRAINT `ld_quiz_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `ld_module` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_quiz_attempt`
--
ALTER TABLE `ld_quiz_attempt`
  ADD CONSTRAINT `ld_quiz_attempt_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ld_quiz_question_option`
--
ALTER TABLE `ld_quiz_question_option`
  ADD CONSTRAINT `ld_quiz_question_option_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_quiz_session_answer`
--
ALTER TABLE `ld_quiz_session_answer`
  ADD CONSTRAINT `ld_quiz_session_answer_ibfk_1` FOREIGN KEY (`quiz_session_id`) REFERENCES `ld_quiz_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_quiz_session_answer_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `ld_quiz_question` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_rating`
--
ALTER TABLE `ld_rating`
  ADD CONSTRAINT `ld_rating_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_video_conference`
--
ALTER TABLE `ld_video_conference`
  ADD CONSTRAINT `ld_video_conference_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ld_video_conference_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `ld_program` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_part_time_rates`
--
ALTER TABLE `payroll_part_time_rates`
  ADD CONSTRAINT `fk_ptr_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD CONSTRAINT `personal_information_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_appraisals`
--
ALTER TABLE `pm_appraisals`
  ADD CONSTRAINT `fk_pm_appraisals_cycle` FOREIGN KEY (`review_cycle_id`) REFERENCES `pm_review_cycles` (`cycle_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pm_appraisals_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_appraisals_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_appraisal_history`
--
ALTER TABLE `pm_appraisal_history`
  ADD CONSTRAINT `fk_pm_appraisal_history_appraisal` FOREIGN KEY (`appraisal_id`) REFERENCES `pm_appraisals` (`appraisal_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_appraisal_items`
--
ALTER TABLE `pm_appraisal_items`
  ADD CONSTRAINT `fk_pm_appraisal_items_appraisal` FOREIGN KEY (`appraisal_id`) REFERENCES `pm_appraisals` (`appraisal_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_employee_training`
--
ALTER TABLE `pm_employee_training`
  ADD CONSTRAINT `fk_pm_employee_training_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pm_employee_training_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `pm_training_programs` (`training_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pm_employee_training_ibfk_2` FOREIGN KEY (`recommendation_id`) REFERENCES `pm_training_recommendations` (`recommendation_id`) ON DELETE SET NULL;

--
-- Constraints for table `pm_feedback_360_entries`
--
ALTER TABLE `pm_feedback_360_entries`
  ADD CONSTRAINT `fk_pm_feedback_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_feedback_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_goals`
--
ALTER TABLE `pm_goals`
  ADD CONSTRAINT `fk_pm_goals_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_goals_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_goal_approvals`
--
ALTER TABLE `pm_goal_approvals`
  ADD CONSTRAINT `fk_pm_goal_approvals_goal` FOREIGN KEY (`goal_id`) REFERENCES `pm_goals` (`goal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pm_goal_approvals_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_goal_attachments`
--
ALTER TABLE `pm_goal_attachments`
  ADD CONSTRAINT `fk_pm_goal_attachments_goal` FOREIGN KEY (`goal_id`) REFERENCES `pm_goals` (`goal_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_goal_comments`
--
ALTER TABLE `pm_goal_comments`
  ADD CONSTRAINT `fk_pm_goal_comments_goal` FOREIGN KEY (`goal_id`) REFERENCES `pm_goals` (`goal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pm_goal_comments_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_goal_history`
--
ALTER TABLE `pm_goal_history`
  ADD CONSTRAINT `fk_pm_goal_history_goal` FOREIGN KEY (`goal_id`) REFERENCES `pm_goals` (`goal_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_goal_progress`
--
ALTER TABLE `pm_goal_progress`
  ADD CONSTRAINT `fk_pm_goal_progress_goal` FOREIGN KEY (`goal_id`) REFERENCES `pm_goals` (`goal_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_performance_reports`
--
ALTER TABLE `pm_performance_reports`
  ADD CONSTRAINT `fk_pm_performance_reports_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_performance_reports_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pm_performance_report_actions`
--
ALTER TABLE `pm_performance_report_actions`
  ADD CONSTRAINT `pm_performance_report_actions_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `pm_performance_reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_performance_report_evaluations`
--
ALTER TABLE `pm_performance_report_evaluations`
  ADD CONSTRAINT `pm_performance_report_evaluations_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `pm_performance_reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_performance_report_kpis`
--
ALTER TABLE `pm_performance_report_kpis`
  ADD CONSTRAINT `pm_performance_report_kpis_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `pm_performance_reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_report_disciplinary_actions`
--
ALTER TABLE `pm_report_disciplinary_actions`
  ADD CONSTRAINT `fk_pm_disciplinary_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pm_training_evaluations`
--
ALTER TABLE `pm_training_evaluations`
  ADD CONSTRAINT `pm_training_evaluations_ibfk_1` FOREIGN KEY (`employee_training_id`) REFERENCES `pm_employee_training` (`employee_training_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_training_recommendations`
--
ALTER TABLE `pm_training_recommendations`
  ADD CONSTRAINT `fk_pm_training_recommendations_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_employee_adjustments`
--
ALTER TABLE `pr_employee_adjustments`
  ADD CONSTRAINT `fk_employee_adjustments_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employee_adjustments_payroll_period` FOREIGN KEY (`period_id`) REFERENCES `pr_periods` (`period_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_employee_benefits`
--
ALTER TABLE `pr_employee_benefits`
  ADD CONSTRAINT `fk_employee_benefits` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_employee_deductions`
--
ALTER TABLE `pr_employee_deductions`
  ADD CONSTRAINT `fk_employee_deductions` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employee_deductions_deduction` FOREIGN KEY (`deduction_id`) REFERENCES `pr_deductions` (`deduction_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_final_settlement_items`
--
ALTER TABLE `pr_final_settlement_items`
  ADD CONSTRAINT `fk_final_item_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `pr_final_settlements` (`settlement_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pr_payslips`
--
ALTER TABLE `pr_payslips`
  ADD CONSTRAINT `fk_employee_payslips` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employee_runs` FOREIGN KEY (`run_id`) REFERENCES `pr_runs` (`run_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_payslip_items`
--
ALTER TABLE `pr_payslip_items`
  ADD CONSTRAINT `fk_payslip_items` FOREIGN KEY (`payslip_id`) REFERENCES `pr_payslips` (`payslip_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pr_runs`
--
ALTER TABLE `pr_runs`
  ADD CONSTRAINT `fk_runs` FOREIGN KEY (`period_id`) REFERENCES `pr_periods` (`period_id`) ON UPDATE CASCADE;

--
-- Constraints for table `sss_contributions`
--
ALTER TABLE `sss_contributions`
  ADD CONSTRAINT `fk_sss_contribution_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ta_absence_late_thresholds`
--
ALTER TABLE `ta_absence_late_thresholds`
  ADD CONSTRAINT `fk_ta_absence_late_thresholds_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_attendance`
--
ALTER TABLE `ta_attendance`
  ADD CONSTRAINT `fk_ta_attendance_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_attendance_leave_request` FOREIGN KEY (`leave_request_id`) REFERENCES `ta_leave_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_attendance_shift` FOREIGN KEY (`shift_id`) REFERENCES `ta_shifts` (`shift_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ta_employee_shifts`
--
ALTER TABLE `ta_employee_shifts`
  ADD CONSTRAINT `fk_ta_employee_shifts_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_employee_shifts_shift` FOREIGN KEY (`shift_id`) REFERENCES `ta_shifts` (`shift_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_flexible_schedules`
--
ALTER TABLE `ta_flexible_schedules`
  ADD CONSTRAINT `fk_ta_flexible_schedules_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_flexible_schedules_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_holidays`
--
ALTER TABLE `ta_holidays`
  ADD CONSTRAINT `fk_ta_holidays_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ta_leave_balances`
--
ALTER TABLE `ta_leave_balances`
  ADD CONSTRAINT `fk_ta_leave_balances_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_leave_balances_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `ta_leave_types` (`leave_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_leave_requests`
--
ALTER TABLE `ta_leave_requests`
  ADD CONSTRAINT `fk_ta_leave_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_leave_requests_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `ta_leave_types` (`leave_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_shift_assignments`
--
ALTER TABLE `ta_shift_assignments`
  ADD CONSTRAINT `fk_ta_shift_assignments_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_shift_assignments_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_shift_assignments_shift` FOREIGN KEY (`shift_id`) REFERENCES `ta_shifts` (`shift_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ta_shift_exclusions`
--
ALTER TABLE `ta_shift_exclusions`
  ADD CONSTRAINT `fk_ta_shift_exclusions_employee_shift` FOREIGN KEY (`employee_shift_id`) REFERENCES `ta_employee_shifts` (`employee_shift_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_account`
--
ALTER TABLE `user_account`
  ADD CONSTRAINT `fk_user_employee` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `em_roles` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wfa_attrition_tracking`
--
ALTER TABLE `wfa_attrition_tracking`
  ADD CONSTRAINT `fk_wfa_attrition_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `wfa_attrition_tracking_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `wfa_audit_log`
--
ALTER TABLE `wfa_audit_log`
  ADD CONSTRAINT `fk_wfa_audit_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `wfa_compensation_analysis`
--
ALTER TABLE `wfa_compensation_analysis`
  ADD CONSTRAINT `fk_wfa_comp_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wfa_comp_position` FOREIGN KEY (`position_id`) REFERENCES `em_positions` (`position_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wfa_custom_filters`
--
ALTER TABLE `wfa_custom_filters`
  ADD CONSTRAINT `fk_wfa_filter_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `wfa_department_analytics`
--
ALTER TABLE `wfa_department_analytics`
  ADD CONSTRAINT `fk_wfa_dept_analytics_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wfa_diversity_metrics`
--
ALTER TABLE `wfa_diversity_metrics`
  ADD CONSTRAINT `fk_wfa_diversity_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `wfa_headcount_planning`
--
ALTER TABLE `wfa_headcount_planning`
  ADD CONSTRAINT `fk_wfa_headcount_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wfa_reports`
--
ALTER TABLE `wfa_reports`
  ADD CONSTRAINT `fk_wfa_reports_user` FOREIGN KEY (`generated_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `wfa_risk_assessment`
--
ALTER TABLE `wfa_risk_assessment`
  ADD CONSTRAINT `wfa_risk_assessment_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `wfa_salary_statistics`
--
ALTER TABLE `wfa_salary_statistics`
  ADD CONSTRAINT `fk_wfa_salary_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wfa_skill_gap_analysis`
--
ALTER TABLE `wfa_skill_gap_analysis`
  ADD CONSTRAINT `fk_wfa_skill_gap_department` FOREIGN KEY (`department_id`) REFERENCES `em_departments` (`department_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
