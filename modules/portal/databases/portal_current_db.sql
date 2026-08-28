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
