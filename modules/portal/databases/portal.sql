-- USERS
CREATE TABLE
  `ep_users` (
    `id` int (11) NOT NULL,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `role` varchar(50) DEFAULT NULL,
    `is_admin` tinyint (1) NOT NULL DEFAULT 0,
    `is_active` tinyint (1) NOT NULL DEFAULT 1,
    `theme` enum ('light', 'dark') DEFAULT 'light',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `password_reset_token` varchar(255) DEFAULT NULL,
    `password_reset_expires` datetime DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
  `ep_users` (
    `id`,
    `username`,
    `password`,
    `email`,
    `role`,
    `is_admin`,
    `is_active`,
    `theme`,
    `created_at`,
    `password_reset_token`,
    `password_reset_expires`
  )
VALUES
  (
    1,
    'Employee 1',
    '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.',
    'sample@gmail.com',
    'employee_portal',
    0,
    1,
    'light',
    '2026-01-28 15:21:13',
    NULL,
    NULL
  ),
  (
    2,
    'Employee 2',
    '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.',
    'crobertjanssen@gmail.com',
    'employee_portal',
    0,
    1,
    'light',
    '2026-03-25 02:06:12',
    NULL,
    NULL
  ),
  (
    3,
    'Admin Employee Portal',
    '$2y$10$GF34eDR6uEqpxNIovwKmRu2A6u3ALXgmMkn8zBdoREYLb1Em0euAK',
    NULL,
    'employee_portal',
    1,
    1,
    'light',
    '2026-01-28 15:21:13',
    NULL,
    NULL
  ),
  (
    4,
    'Employee 3',
    '$2y$10$b2mhtPvVKZKi7yhVPL3S7uc4QU9V25ltIWQ9Qjp538la9gg7qIRn.',
    NULL,
    'employee_portal',
    0,
    1,
    'light',
    '2026-01-28 15:21:13',
    NULL,
    NULL
  );

ALTER TABLE `ep_users` ADD PRIMARY KEY (`id`);

ALTER TABLE `ep_users` MODIFY `id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 21;

COMMIT;

-- ONLINE MEETING
CREATE TABLE
  `ep_online_meetings` (
    `meetings_id` int (11) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `meeting_link` text DEFAULT NULL,
    `created_by` int (11) DEFAULT NULL,
    `employee_id` int (11) DEFAULT NULL,
    `scheduled_at` datetime DEFAULT NULL,
    `status` enum ('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled'
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
  `ep_online_meetings` (
    `meetings_id`,
    `title`,
    `meeting_link`,
    `created_by`,
    `employee_id`,
    `scheduled_at`,
    `status`
  )
VALUES
  (
    2,
    'Academic Forum',
    'https://meet.jit.si/hr_meeting_69d0b6b27deb8',
    3,
    6,
    '2026-04-06 14:58:00',
    'scheduled'
  ),
  (
    3,
    'Midterm Planning',
    'https://meet.jit.si/hr_meeting_69d0b6d45aa13',
    3,
    6,
    '2026-04-08 14:59:00',
    'scheduled'
  );

ALTER TABLE `ep_online_meetings` ADD PRIMARY KEY (`meetings_id`);

ALTER TABLE `ep_online_meetings` MODIFY `meetings_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 5;

COMMIT;

-- RESIGNATION REQUEST
CREATE TABLE
  `ep_resignation_requests` (
    `resignation_id` int (11) NOT NULL,
    `employee_id` int (11) NOT NULL,
    `resignation_type` enum ('Immediate', 'With Notice') DEFAULT 'With Notice',
    `resignation_reason` text NOT NULL,
    `attachment` varchar(255) DEFAULT NULL,
    `date_submitted` datetime DEFAULT current_timestamp(),
    `intended_last_working_day` date NOT NULL,
    `status` enum ('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    `employee_remarks` text DEFAULT NULL,
    `hr_remarks` text DEFAULT NULL,
    `reviewed_by` int (11) DEFAULT NULL,
    `reviewed_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `ep_resignation_requests` ADD PRIMARY KEY (`resignation_id`);

ALTER TABLE `ep_resignation_requests` MODIFY `resignation_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

COMMIT;

-- PAYROLL REQUEST
CREATE TABLE
  `ep_payroll_request` (
    `id` int (10) UNSIGNED NOT NULL,
    `employee_id` int (11) NOT NULL,
    `request_type` varchar(100) NOT NULL,
    `purpose` varchar(255) DEFAULT NULL,
    `remarks` text DEFAULT NULL,
    `payroll_period_start` date DEFAULT NULL,
    `payroll_period_end` date DEFAULT NULL,
    `status` enum (
      'Pending',
      'Processing',
      'Approved',
      'Rejected',
      'Completed',
      'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `processed_at` timestamp NULL DEFAULT NULL,
    `processed_by` int (11) DEFAULT NULL,
    `rejection_reason` text DEFAULT NULL,
    `document_path` varchar(500) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `ep_payroll_request` ADD PRIMARY KEY (`id`);

ALTER TABLE `ep_payroll_request` MODIFY `id` int (10) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

COMMIT;