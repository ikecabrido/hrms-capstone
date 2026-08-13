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

INSERT INTO `em_employees` (`id`, `employee_num`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birth_date`, `birth_place`, `civil_status`, `citizenship`, `religion`, `email`, `mobile_no`, `phone_no`, `current_address`, `permanent_address`, `department`, `position`, `position_id`, `hire_date`, `regular_date`, `employment_status`, `employment_type`, `unit_load`, `graduate_level`, `ranking`, `credentials`, `faculty_notes`, `negotiated_salary`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_date`) VALUES
(1, 'EMP-000001', 1, 'Ronaldo', 'G.', 'Raymundo', NULL, 'Male', '1995-01-02', NULL, 'Single', 'Filipino', NULL, 'ronaldocruz22@gmail.com', '09123456789', '0287654321', 'San Jose Del Monte, Bulacan', NULL, 'IT DEPARTMENT', 'IT Staff', 9, '2026-08-06', NULL, 'Active', 'Full-time', NULL, 'None', '', '', '', NULL, '2026-08-06 05:47:35', '2026-08-11 04:48:25', 0, NULL, NULL),
(2, 'EMP-000002', NULL, 'Juan', 'Dela', 'Cruz', NULL, 'Male', '1990-05-15', NULL, NULL, NULL, NULL, 'juan.delacruz@bcp.edu.ph', '09123456789', '021234567', '123 Main St, Manila', NULL, 'Executive Administration', 'College President', NULL, '2023-01-15', NULL, 'Active', NULL, NULL, 'None', NULL, NULL, NULL, NULL, '2026-08-06 06:54:48', '2026-08-06 08:46:37', 0, NULL, NULL),
(3, 'EMP-000003', NULL, 'Maria', 'Santos', 'Reyes', NULL, 'Female', '1992-03-18', 'Malolos, Bulacan', 'Married', 'Filipino', NULL, 'maria.reyes@bcp.edu.ph', '09171234567', '0441234567', 'Malolos, Bulacan', NULL, 'Employee Management', 'HR Officer', 44, '2024-06-10', '2025-06-10', 'Active', 'Full-time', NULL, 'Masteral', 'Senior HR Staff', 'BS Psychology, MA Human Resource Management', NULL, 45000.00, '2026-08-08 01:00:00', NULL, 0, NULL, NULL),
(4, 'EMP-000004', NULL, 'Michael', 'Tan', 'Santos', 'Jr.', 'Male', '1988-11-25', 'Meycauayan, Bulacan', 'Married', 'Filipino', NULL, 'michael.santos@bcp.edu.ph', '09181234567', '0442345678', 'Meycauayan, Bulacan', NULL, 'Payroll', 'Payroll Officer', 43, '2023-08-01', '2024-08-01', 'Active', 'Full-time', NULL, 'Masteral', 'Payroll Specialist', 'BS Accountancy, CPA', NULL, 48000.00, '2026-08-08 01:15:00', NULL, 0, NULL, NULL),
(5, 'EMP-000005', NULL, 'Angela', 'Marie', 'Garcia', NULL, 'Female', '1996-07-09', 'Baliwag, Bulacan', 'Single', 'Filipino', NULL, 'angela.garcia@bcp.edu.ph', '09201234567', '0443456789', 'Baliwag, Bulacan', NULL, 'Learning', 'Training Coordinator', 47, '2025-01-15', NULL, 'Probationary', 'Full-time', NULL, 'None', 'Training Coordinator', 'BS Education', 'Handles employee training and seminar coordination.', 32000.00, '2026-08-08 01:30:00', NULL, 0, NULL, NULL),
(6, 'EMP-000006', NULL, 'Daniel', 'Lopez', 'Mendoza', NULL, 'Male', '1993-09-14', 'San Fernando, Pampanga', 'Single', 'Filipino', NULL, 'daniel.mendoza@bcp.edu.ph', '09301234567', '0451234567', 'San Fernando, Pampanga', NULL, 'Performance', 'Performance Management Officer', 48, '2024-03-20', '2025-03-20', 'Active', 'Full-time', NULL, 'Masteral', 'Performance Specialist', 'BS Business Administration, MBA', NULL, 42000.00, '2026-08-08 01:45:00', NULL, 0, NULL, NULL),
(7, 'EMP-000007', NULL, 'Sofia', 'Anne', 'Villanueva', NULL, 'Female', '1997-12-03', 'Quezon City', 'Single', 'Filipino', NULL, 'sofia.villanueva@bcp.edu.ph', '09401234567', '0281234567', 'Quezon City', NULL, 'Employee Engagement', 'Employee Relations Officer', 51, '2025-05-05', NULL, 'Probationary', 'Full-time', NULL, 'None', 'Employee Relations Staff', 'BS Psychology', 'Handles employee engagement activities and concerns.', 35000.00, '2026-08-08 02:00:00', NULL, 0, NULL, NULL);

ALTER TABLE `em_employees`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `em_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

CREATE TABLE `em_positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `em_positions` (`position_id`, `position_name`, `department_id`, `status`, `created_at`, `updated_at`) VALUES
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

ALTER TABLE `em_employees`
ADD COLUMN `profile_image` VARCHAR(255) NULL AFTER `current_address`;