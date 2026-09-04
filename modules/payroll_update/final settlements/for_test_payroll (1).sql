-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2026 at 04:30 AM
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
-- Database: `for_test_payroll`
--

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

--
-- Dumping data for table `exit_employee_settlements`
--

INSERT INTO `exit_employee_settlements` (`settlement_id`, `employee_id`, `exit_case_type`, `exit_case_id`, `last_working_date`, `payroll_settlement_id`, `status`, `requested_at`, `completed_at`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(9001, 1, 'resignation', 101, '2026-08-20', NULL, 'requested', '2026-08-21 09:15:00', NULL, 'Employee submitted resignation. Final settlement requested from Payroll.', NULL, '2026-08-21 01:15:00', '2026-08-21 01:15:00'),
(9002, 2, 'termination', 102, '2026-08-18', 9106, 'processing', '2026-08-21 10:30:00', NULL, 'Employee termination processed by Exit Management. Final settlement requested.', NULL, '2026-08-21 02:30:00', '2026-08-24 07:15:38'),
(9003, 3, 'resignation', 103, '2026-08-15', 9101, 'processing', '2026-08-19 09:00:00', NULL, 'Settlement request accepted by Payroll and currently being processed.', NULL, '2026-08-19 01:00:00', '2026-08-19 01:30:00'),
(9004, 4, 'termination', 104, '2026-08-12', 9102, 'calculated', '2026-08-17 08:30:00', NULL, 'Final settlement calculation completed.', NULL, '2026-08-17 00:30:00', '2026-08-18 07:00:00'),
(9005, 5, 'resignation', 105, '2026-08-10', 9103, 'for_approval', '2026-08-15 09:00:00', NULL, 'Final settlement calculated and submitted for approval.', NULL, '2026-08-15 01:00:00', '2026-08-16 06:00:00'),
(9006, 6, 'termination', 106, '2026-08-05', 9104, 'approved', '2026-08-08 10:00:00', NULL, 'Final settlement approved and awaiting payment release.', NULL, '2026-08-08 02:00:00', '2026-08-12 03:30:00'),
(9007, 29, 'resignation', 107, '2026-07-31', 9105, 'paid', '2026-08-01 09:00:00', '2026-08-07 16:00:00', 'Final settlement released and completed.', NULL, '2026-08-01 01:00:00', '2026-08-07 08:00:00');

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

--
-- Dumping data for table `pr_final_settlements`
--

INSERT INTO `pr_final_settlements` (`settlement_id`, `employee_id`, `exit_settlement_id`, `exit_case_type`, `exit_case_id`, `last_working_date`, `settlement_date`, `total_earnings`, `total_deductions`, `net_settlement`, `status`, `approved_by`, `approved_at`, `paid_at`, `payment_reference`, `payment_method`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(9101, 3, 9003, 'resignation', 103, '2026-08-15', NULL, 0.00, 0.00, 0.00, 'processing', NULL, NULL, NULL, NULL, NULL, 'Settlement is currently being prepared by Payroll.', NULL, '2026-08-19 01:30:00', '2026-08-19 01:30:00'),
(9102, 4, 9004, 'termination', 104, '2026-08-12', '2026-08-18', 18500.00, 2500.00, 16000.00, 'calculated', NULL, NULL, NULL, NULL, NULL, 'Settlement calculation completed.', NULL, '2026-08-17 00:45:00', '2026-08-18 07:00:00'),
(9103, 5, 9005, 'resignation', 105, '2026-08-10', '2026-08-16', 22000.00, 3000.00, 19000.00, 'for_approval', NULL, NULL, NULL, NULL, NULL, 'Settlement submitted for approval.', NULL, '2026-08-15 01:30:00', '2026-08-16 06:00:00'),
(9104, 6, 9006, 'termination', 106, '2026-08-05', '2026-08-12', 25000.00, 3500.00, 21500.00, 'approved', NULL, '2026-08-12 11:30:00', NULL, NULL, NULL, 'Settlement approved and ready for payment release.', NULL, '2026-08-08 02:30:00', '2026-08-12 03:30:00'),
(9105, 29, 9007, 'resignation', 107, '2026-07-31', '2026-08-07', 28000.00, 4000.00, 24000.00, 'paid', NULL, '2026-08-05 10:00:00', '2026-08-07 16:00:00', 'PAY-2026-0807-001', 'Bank Transfer', 'Final settlement successfully released to employee.', NULL, '2026-08-01 01:30:00', '2026-08-07 08:00:00'),
(9106, 2, 9002, 'termination', 102, '2026-08-18', NULL, 0.00, 0.00, 0.00, 'processing', NULL, NULL, NULL, NULL, NULL, NULL, 30, '2026-08-24 07:15:38', '2026-08-24 07:15:38');

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

--
-- Dumping data for table `pr_final_settlement_items`
--

INSERT INTO `pr_final_settlement_items` (`item_id`, `settlement_id`, `item_type`, `item_category`, `item_code`, `description`, `amount`, `source_type`, `source_id`, `sort_order`, `created_at`) VALUES
(9201, 9101, 'earning', 'Final Salary', 'FS-001', 'Remaining salary for final payroll period', 10000.00, 'manual', NULL, 1, '2026-08-19 02:00:00'),
(9202, 9101, 'earning', 'Allowance', 'ALW-001', 'Outstanding employee allowance', 1500.00, 'manual', NULL, 2, '2026-08-19 02:00:00'),
(9203, 9101, 'deduction', 'Absence', 'DED-001', 'Final attendance adjustment', 500.00, 'manual', NULL, 1, '2026-08-19 02:05:00'),
(9204, 9102, 'earning', 'Final Salary', 'FS-002', 'Remaining salary for final payroll period', 12000.00, 'manual', NULL, 1, '2026-08-17 01:00:00'),
(9205, 9102, 'earning', 'Unused Leave', 'LV-001', 'Conversion of eligible unused leave credits', 3500.00, 'manual', NULL, 2, '2026-08-17 01:00:00'),
(9206, 9102, 'earning', 'Allowance', 'ALW-002', 'Outstanding allowance', 3000.00, 'manual', NULL, 3, '2026-08-17 01:00:00'),
(9207, 9102, 'deduction', 'Absence', 'DED-002', 'Unpaid absence adjustment', 1000.00, 'manual', NULL, 1, '2026-08-17 01:05:00'),
(9208, 9102, 'deduction', 'Loan', 'LOAN-001', 'Outstanding employee loan balance', 1500.00, 'manual', NULL, 2, '2026-08-17 01:05:00'),
(9209, 9103, 'earning', 'Final Salary', 'FS-003', 'Final salary payable', 14000.00, 'manual', NULL, 1, '2026-08-15 02:00:00'),
(9210, 9103, 'earning', 'Unused Leave', 'LV-002', 'Eligible unused leave conversion', 5000.00, 'manual', NULL, 2, '2026-08-15 02:00:00'),
(9211, 9103, 'earning', 'Allowance', 'ALW-003', 'Final outstanding allowance', 3000.00, 'manual', NULL, 3, '2026-08-15 02:00:00'),
(9212, 9103, 'deduction', 'Loan', 'LOAN-002', 'Remaining loan balance', 2000.00, 'manual', NULL, 1, '2026-08-15 02:05:00'),
(9213, 9103, 'deduction', 'Absence', 'DED-003', 'Final absence adjustment', 1000.00, 'manual', NULL, 2, '2026-08-15 02:05:00'),
(9214, 9104, 'earning', 'Final Salary', 'FS-004', 'Final salary payable', 15000.00, 'manual', NULL, 1, '2026-08-08 03:00:00'),
(9215, 9104, 'earning', 'Unused Leave', 'LV-003', 'Eligible unused leave conversion', 6000.00, 'manual', NULL, 2, '2026-08-08 03:00:00'),
(9216, 9104, 'earning', 'Allowance', 'ALW-004', 'Outstanding allowance', 4000.00, 'manual', NULL, 3, '2026-08-08 03:00:00'),
(9217, 9104, 'deduction', 'Loan', 'LOAN-003', 'Outstanding loan deduction', 2500.00, 'manual', NULL, 1, '2026-08-08 03:05:00'),
(9218, 9104, 'deduction', 'Absence', 'DED-004', 'Final absence adjustment', 1000.00, 'manual', NULL, 2, '2026-08-08 03:05:00'),
(9219, 9105, 'earning', 'Final Salary', 'FS-005', 'Final salary payable', 17000.00, 'manual', NULL, 1, '2026-08-01 02:00:00'),
(9220, 9105, 'earning', 'Unused Leave', 'LV-004', 'Eligible unused leave conversion', 7000.00, 'manual', NULL, 2, '2026-08-01 02:00:00'),
(9221, 9105, 'earning', 'Allowance', 'ALW-005', 'Outstanding employee allowance', 4000.00, 'manual', NULL, 3, '2026-08-01 02:00:00'),
(9222, 9105, 'deduction', 'Loan', 'LOAN-004', 'Outstanding loan balance', 3000.00, 'manual', NULL, 1, '2026-08-01 02:05:00'),
(9223, 9105, 'deduction', 'Absence', 'DED-005', 'Final absence adjustment', 1000.00, 'manual', NULL, 2, '2026-08-01 02:05:00');

--
-- Indexes for dumped tables
--

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
  ADD KEY `idx_final_settlement_items_settlement` (`settlement_id`),
  ADD KEY `idx_final_settlement_items_type` (`item_type`),
  ADD KEY `idx_final_settlement_items_category` (`item_category`),
  ADD KEY `idx_final_settlement_items_source` (`source_type`,`source_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `exit_employee_settlements`
--
ALTER TABLE `exit_employee_settlements`
  MODIFY `settlement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9008;

--
-- AUTO_INCREMENT for table `pr_final_settlements`
--
ALTER TABLE `pr_final_settlements`
  MODIFY `settlement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9107;

--
-- AUTO_INCREMENT for table `pr_final_settlement_items`
--
ALTER TABLE `pr_final_settlement_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9224;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pr_final_settlements`
--
ALTER TABLE `pr_final_settlements`
  ADD CONSTRAINT `fk_pr_final_settlements_exit` FOREIGN KEY (`exit_settlement_id`) REFERENCES `exit_employee_settlements` (`settlement_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pr_final_settlement_items`
--
ALTER TABLE `pr_final_settlement_items`
  ADD CONSTRAINT `fk_pr_final_settlement_items_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `pr_final_settlements` (`settlement_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
