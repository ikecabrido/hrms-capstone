-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 02:59 PM
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
-- Table structure for table `lc_complaints`
--

CREATE TABLE `lc_complaints` (
  `id` int(11) NOT NULL,
  `type` varchar(150) DEFAULT NULL,
  `severity` varchar(20) DEFAULT 'medium',
  `status` enum('under_initial_review','under_investigation','pending_employee_response','for_decision','closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved','closed') DEFAULT 'under_initial_review',
  `employee_id` int(11) DEFAULT NULL,
  `reporter_name` varchar(150) DEFAULT NULL,
  `reporter_department` varchar(150) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_response` text DEFAULT NULL,
  `employee_response_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lc_complaints`
--

INSERT INTO `lc_complaints` (`id`, `type`, `severity`, `status`, `employee_id`, `reporter_name`, `reporter_department`, `incident_date`, `incident_time`, `location`, `title`, `description`, `assigned_to`, `assigned_name`, `created_at`, `updated_at`, `employee_response`, `employee_response_date`) VALUES
(1, 'Voluntary Exit', 'Medium', 'under_initial_review', 3, 'Erwin M. De Guzman', 'Department 3', '2026-08-01', '09:15:00', 'Human Resources Office', 'Accepted Teaching Position Abroad', 'Employee voluntarily resigned after accepting a teaching position in Singapore and plans to relocate abroad.', NULL, NULL, '2026-08-01 01:30:00', '2026-08-27 12:30:51', 'I accepted a teaching position in Singapore and will be relocating abroad.', '2026-08-01 00:00:00'),
(2, 'Voluntary Exit', 'Low', 'under_initial_review', 4, 'Roberto J Albert', 'Department 3', '2026-08-02', '10:20:00', 'Human Resources Office', 'Career Change to Software Development', 'Employee intends to leave the institution to pursue a career in software development and transition into the technology industry.', NULL, NULL, '2026-08-02 02:35:00', '2026-08-27 12:30:51', 'I would like to transition into a software development career.', '2026-08-02 00:00:00'),
(3, 'Terminated', 'Medium', 'under_initial_review', 5, 'Althea M. Santos', 'Department 1', '2026-08-03', '14:00:00', 'Faculty Room', 'Repeated Absenteeism', 'Employee was terminated after repeated and excessive unauthorized absences despite prior attendance reminders and corrective action.', NULL, NULL, '2026-08-03 06:20:00', '2026-08-27 12:34:08', 'I was accepted into a graduate program in Australia and will be studying full-time.', '2026-08-03 00:00:00'),
(4, 'Voluntary Exit', 'Low', 'under_initial_review', 6, 'Bianca G. Reyes', 'Department 1', '2026-08-04', '11:10:00', 'Human Resources Office', 'Higher-Paying Employment', 'Employee voluntarily resigned after receiving an employment offer from another organization with a higher salary and additional benefits.', NULL, NULL, '2026-08-04 03:25:00', '2026-08-27 12:30:51', 'I accepted another position that offers better compensation and benefits.', '2026-08-04 00:00:00'),
(5, 'Voluntary Exit', 'Low', 'under_initial_review', 7, 'Chloe M. Cruz', 'Department 1', '2026-08-05', '13:30:00', 'Human Resources Office', 'Family Migration to Canada', 'Employee intends to resign because her immediate family will be migrating to Canada and she plans to relocate with them.', NULL, NULL, '2026-08-05 05:45:00', '2026-08-27 12:30:51', 'My immediate family will be relocating to Canada, and I have decided to move with them.', '2026-08-05 00:00:00'),
(6, 'Terminated', 'Medium', 'under_initial_review', 8, 'Diana G. Bautista', 'Department 1', '2026-08-06', '08:45:00', 'Human Resources Office', 'Workplace Misconduct', 'Employee was terminated due to repeated inappropriate workplace behavior and violation of institutional conduct policies.', NULL, NULL, '2026-08-06 01:00:00', '2026-08-27 12:34:08', 'I have decided to pursue a different career path outside the education sector.', '2026-08-06 00:00:00'),
(7, 'Voluntary Exit', 'Low', 'for_decision', 9, 'Elena G. Ocampo', 'Department 2', '2026-08-07', '15:10:00', 'Human Resources Office', 'Starting Own Business', 'Employee voluntarily resigned to establish and manage a small business as a full-time entrepreneur.', 32, 'Russell  Placer', '2026-08-07 07:25:00', '2026-08-27 12:45:00', 'I am leaving employment to focus on establishing my own business.', '2026-08-07 00:00:00'),
(8, 'Voluntary Exit', 'Low', 'under_initial_review', 10, 'Fiona G. Ramos', 'Department 2', '2026-08-08', '09:40:00', 'Human Resources Office', 'Relocation to Cebu', 'Employee intends to resign because her family is relocating to Cebu, making continued employment at the institution impractical.', NULL, NULL, '2026-08-08 01:55:00', '2026-08-27 12:30:51', 'My family is relocating to Cebu, so I need to leave my current position.', '2026-08-08 00:00:00'),
(9, 'Voluntary Exit', 'Medium', 'under_initial_review', 11, 'Aaron Mendoza', 'Department 2', '2026-08-09', '13:15:00', 'Human Resources Office', 'Government Career Opportunity', 'Employee decided to resign after receiving an offer for a government position aligned with his professional goals.', NULL, NULL, '2026-08-09 05:30:00', '2026-08-27 12:30:51', 'I accepted a government position that aligns with my long-term career goals.', '2026-08-09 00:00:00'),
(10, 'Terminated', 'Low', 'under_initial_review', 12, 'Caleb Santos', 'Department 2', '2026-08-10', '10:00:00', 'Human Resources Office', 'Insubordination', 'Employee was terminated following repeated refusal to comply with lawful and reasonable work instructions from management.', NULL, NULL, '2026-08-10 02:15:00', '2026-08-27 12:34:08', 'I have decided to retire from active employment and focus on my personal plans.', '2026-08-10 00:00:00'),
(11, 'Voluntary Exit', 'Low', 'under_initial_review', 13, 'David Aquino', 'Department 4', '2026-08-11', '14:30:00', 'Human Resources Office', 'Transition to Teaching Career', 'Employee intends to leave his current position to pursue a full-time teaching career.', NULL, NULL, '2026-08-11 06:45:00', '2026-08-27 12:30:51', 'I want to pursue a full-time teaching career because it better matches my professional goals.', '2026-08-11 00:00:00'),
(12, 'Voluntary Exit', 'Medium', 'under_initial_review', 14, 'Ethan Garcia', 'Department 4', '2026-08-12', '11:20:00', 'Human Resources Office', 'Overseas Employment in Dubai', 'Employee received an overseas employment opportunity in Dubai and intends to relocate for the new position.', NULL, NULL, '2026-08-12 03:35:00', '2026-08-27 12:30:51', 'I accepted an overseas employment opportunity in Dubai and will be relocating.', '2026-08-12 00:00:00'),
(13, 'Voluntary Exit', 'Low', 'under_initial_review', 17, 'Hugo Villanueva', 'Department 5', '2026-08-13', '09:50:00', 'Human Resources Office', 'Professional Certification', 'Employee plans to resign to focus on obtaining an international professional certification and preparing for a career transition.', NULL, NULL, '2026-08-13 02:05:00', '2026-08-27 12:30:51', 'I need to dedicate time to completing my professional certification and preparing for a career transition.', '2026-08-13 00:00:00'),
(14, 'Terminated', 'Low', 'under_initial_review', 18, 'Ian Fernandez', 'Department 5', '2026-08-14', '16:00:00', 'Human Resources Office', 'Serious Policy Violation', 'Employee was terminated after an investigation found a serious violation of institutional policies and workplace rules.', NULL, NULL, '2026-08-14 08:15:00', '2026-08-27 12:34:08', 'My spouse accepted employment in New Zealand, and our family has decided to relocate.', '2026-08-14 00:00:00'),
(15, 'Voluntary Exit', 'Medium', 'under_investigation', 19, 'Jacob Lopez', 'Department 5', '2026-08-15', '13:40:00', 'Human Resources Office', 'Overseas Healthcare Opportunity', 'Employee voluntarily resigned after receiving an opportunity to work in the healthcare sector abroad.', 14, 'Ethan  Garcia', '2026-08-15 05:55:00', '2026-08-27 12:46:32', 'I received an overseas employment opportunity and have decided to pursue it.', '2026-08-15 00:00:00'),
(16, 'Voluntary Exit', 'Low', 'under_initial_review', 20, 'Ian Perez', 'Department 5', '2026-08-18', '10:30:00', 'Human Resources Office', 'Private Sector Career Opportunity', 'Employee intends to leave the institution to pursue an accounting position in a private corporation with broader career opportunities.', NULL, NULL, '2026-08-18 02:45:00', '2026-08-27 12:30:51', 'I accepted a private-sector position that provides broader career opportunities.', '2026-08-18 00:00:00'),
(17, 'Voluntary Exit', 'Low', 'closed_termination_recommended', 21, 'Gia Valdez', 'Department 6', '2026-08-19', '14:10:00', 'Human Resources Office', 'Full-Time Further Education', 'Employee plans to resign to pursue full-time studies and complete a professional degree.', 33, 'Cheska Morales Bautista', '2026-08-19 06:25:00', '2026-08-27 12:45:42', 'I will be returning to school full-time to complete my professional degree.', '2026-08-19 00:00:00'),
(18, 'Terminated', 'Medium', 'pending_employee_response', 22, 'Aaron Valdez', 'Department 6', '2026-08-20', '09:20:00', 'Human Resources Office', 'Repeated Tardiness and Absenteeism', 'Employee was terminated following a documented pattern of repeated tardiness and unauthorized absences that affected work operations.', 31, 'Jose Mari Rich  Malana', '2026-08-20 01:35:00', '2026-08-27 12:44:23', 'I accepted a permanent remote position with an international company.', '2026-08-20 00:00:00'),
(19, 'Voluntary Exit', 'Low', 'for_decision', 23, 'Aaron Pascual', 'Department 6', '2026-08-21', '15:30:00', 'Human Resources Office', 'Transfer to Another School', 'Employee voluntarily resigned after accepting a teaching position at another educational institution closer to his residence.', 19, 'Jacob  Lopez', '2026-08-21 07:45:00', '2026-08-27 12:47:06', 'I accepted a position at another school that is closer to my residence.', '2026-08-21 00:00:00'),
(20, 'Voluntary Exit', 'Medium', 'under_investigation', 24, 'Iris Soriano', 'Department 6', '2026-08-22', '11:45:00', 'Human Resources Office', 'Career Change to Healthcare', 'Employee decided to voluntarily resign to pursue a career in healthcare and begin formal training in the field.', 33, 'Cheska Morales Bautista', '2026-08-22 04:00:00', '2026-08-27 12:43:28', 'I have decided to change careers and pursue formal training for a career in healthcare.', '2026-08-22 00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lc_complaints`
--
ALTER TABLE `lc_complaints`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
