-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 03:24 PM
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
-- Database: `payr_bcp`
--

-- --------------------------------------------------------

--
-- Table structure for table `ld_announcement`
--

CREATE TABLE `ld_announcement` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `audience` set('all','instructor','learner','admin') NOT NULL DEFAULT 'all',
  `posted_by` int(10) UNSIGNED NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_announcement`
--

INSERT INTO `ld_announcement` (`id`, `title`, `message`, `audience`, `posted_by`, `status`, `created_at`, `expires_at`) VALUES
(1, 'Test', 'sadsadsaw', 'all', 1007, 'active', '2026-10-09 02:56:31', NULL),
(4, 'ZZ spa publish probe', 'temporary', 'all', 1007, 'active', '2026-10-16 10:33:19', NULL),
(9, 'sda', 'dsadsad', 'all', 1007, 'active', '2026-10-16 11:44:33', NULL);

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

--
-- Dumping data for table `ld_api_key`
--

INSERT INTO `ld_api_key` (`id`, `module_name`, `api_key`, `is_active`, `created_at`) VALUES
(1, 'performance-management', 'pm_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2', 1, '2026-08-29 20:09:17'),
(2, 'recruitment', 'rc_b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3', 1, '2026-08-29 20:09:17'),
(3, 'employee-management', 'em_c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4', 1, '2026-08-29 20:09:17'),
(4, 'employee-engagement', 'ee_d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5', 1, '2026-08-29 20:09:17'),
(5, 'compliance-legal', 'cl_e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6', 1, '2026-08-29 20:09:17'),
(6, 'workforce-analytics', 'wa_f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7', 1, '2026-08-29 20:09:17'),
(7, 'time-attendance', 'ta_a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8', 1, '2026-08-29 20:09:17'),
(8, 'learning-development', 'ld_b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9', 1, '2026-08-29 20:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `ld_assessment_result`
--

CREATE TABLE `ld_assessment_result` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(50) NOT NULL,
  `assessment_name` varchar(150) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `result` enum('passed','failed','pending') NOT NULL DEFAULT 'pending',
  `skill_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skill_ids`)),
  `taken_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_audit_log`
--

CREATE TABLE `ld_audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL,
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

--
-- Dumping data for table `ld_certificate`
--

INSERT INTO `ld_certificate` (`id`, `learner_id`, `course_id`, `course_version_id`, `completed_enrollment_id`, `template_id`, `verification_code`, `file_path`, `issued_at`, `valid_until`, `status`) VALUES
(1, 1006, 5, NULL, 1, NULL, 'CCF1F1EB869F0996', NULL, '2026-10-08 17:15:10', '2027-10-08', 'active'),
(3, 1006, 8, NULL, 5, NULL, 'EDCD2C86306B6251', NULL, '2026-10-15 15:49:25', '2027-10-15', 'active');

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
-- Table structure for table `ld_competency_requirement`
--

CREATE TABLE `ld_competency_requirement` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_module` varchar(80) NOT NULL DEFAULT 'workforce',
  `skill_name` varchar(150) NOT NULL,
  `target_department` varchar(150) NOT NULL,
  `priority_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `employees_needing_training` int(10) UNSIGNED NOT NULL,
  `target_proficiency` varchar(50) NOT NULL,
  `expected_learning_output` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('received','planned','completed') NOT NULL DEFAULT 'received',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_competency_requirement`
--

INSERT INTO `ld_competency_requirement` (`id`, `source_module`, `skill_name`, `target_department`, `priority_level`, `employees_needing_training`, `target_proficiency`, `expected_learning_output`, `notes`, `status`, `created_by`, `created_at`) VALUES
(1, 'workforce', 'Data analysis', 'IT DEPARTMENT', 'high', 12, 'Advanced', 'Skills course and completion report by department', 'From workforce prediction', 'planned', NULL, '2026-09-24 21:29:34'),
(2, 'workforce', 'Cybersecurity awareness', 'ADMIN STAFF', 'critical', 7, 'Intermediate', 'Skills course and completion report by department', 'Written by the Workforce module directly', 'planned', 4, '2026-09-24 21:35:23'),
(3, 'workforce', 'Records management', 'ADMIN STAFF', 'medium', 3, 'Intermediate', 'Records handling course', NULL, 'planned', NULL, '2026-09-24 21:35:36'),
(4, 'workforce', 'Advanced Excel for payroll', 'ADMIN STAFF', 'critical', 9, 'Advanced', 'Payroll staff can build reconciliation models', 'Raised by the workforce probe', 'received', 1018, '2026-09-25 01:51:34');

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
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `delivery_mode` enum('online','face_to_face','hybrid') NOT NULL DEFAULT 'online',
  `start_date` date DEFAULT NULL,
  `enrollment_deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course`
--

INSERT INTO `ld_course` (`id`, `instructor_id`, `title`, `description`, `thumbnail_path`, `category`, `status`, `delivery_mode`, `start_date`, `enrollment_deadline`, `created_at`, `updated_at`) VALUES
(5, 1007, 'Web Development Fundamentals', 'Learn web development fundamentals including HTML, CSS, and JavaScript.', NULL, 'Programming', 'active', 'online', '2026-10-08', '2027-01-01', '2026-10-08 09:45:36', '2026-10-08 09:45:36'),
(6, 1007, 'Digital Design & Visual Communication', 'Explore visual design principles for digital media.', NULL, 'Design & Creative', 'active', 'online', '2026-10-08', '2027-01-01', '2026-10-08 18:27:42', '2026-10-08 18:27:42'),
(7, 1001, 'Workplace Safety and Compliance Essentials', 'Learn the core safety practices, employee responsibilities, and compliance rules that keep the workplace healthy and incident-free.', NULL, 'Compliance & Safety', 'active', 'online', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(8, 1001, 'Effective Business Communication', 'Build clear written, verbal, and interpersonal communication skills for everyday professional interactions.', NULL, 'Communication', 'active', 'online', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(9, 1001, 'Data Analysis with Microsoft Excel', 'Turn raw spreadsheets into clean data, useful charts, and decision-ready reports using Microsoft Excel.', NULL, 'Data & Analytics', 'active', 'online', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(10, 1001, 'Leadership Foundations for New Managers', 'A practical introduction to leading teams: setting expectations, coaching people, and holding performance conversations.', NULL, 'Leadership', 'active', 'hybrid', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(11, 1001, 'Customer Service Excellence', 'Deliver consistent, empathetic customer experiences and handle difficult situations with confidence.', NULL, 'Customer Experience', 'active', 'online', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(12, 1001, 'Cybersecurity Awareness for Employees', 'Recognize common cyber threats, protect company data, and follow safe browsing and password practices.', NULL, 'Security', 'active', 'online', NULL, NULL, '2026-10-15 13:24:54', '2026-10-15 13:24:54');

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
(1, 7, 1001, 'owner', '2026-10-15 13:24:54'),
(2, 8, 1001, 'owner', '2026-10-15 13:24:54'),
(3, 9, 1001, 'owner', '2026-10-15 13:24:54'),
(4, 10, 1001, 'owner', '2026-10-15 13:24:54'),
(5, 11, 1001, 'owner', '2026-10-15 13:24:54'),
(6, 12, 1001, 'owner', '2026-10-15 13:24:54');

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
(2, 5, 3),
(4, 6, 4),
(3, 6, 13),
(11, 7, 16),
(6, 8, 3),
(7, 9, 5),
(10, 9, 14),
(5, 10, 2),
(9, 11, 8),
(8, 12, 6);

-- --------------------------------------------------------

--
-- Table structure for table `ld_course_template`
--

CREATE TABLE `ld_course_template` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `structure_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`structure_json`)),
  `module_count` int(10) UNSIGNED DEFAULT 0,
  `lesson_count` int(10) UNSIGNED DEFAULT 0,
  `quiz_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_course_template`
--

INSERT INTO `ld_course_template` (`id`, `title`, `description`, `category`, `created_by`, `structure_json`, `module_count`, `lesson_count`, `quiz_count`, `created_at`, `updated_at`) VALUES
(1, 'Programming Course Template', 'Auto test', 'General', 99967, '{\"modules\":[{\"id\":2,\"title\":\"Introduction to Programming\",\"description\":\"\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":2,\"title\":\"What is Programming?\",\"content_type\":\"text\",\"content_body\":null,\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":3,\"title\":\"Programming Basics Quiz\",\"duration_seconds\":600,\"passing_score\":\"75.00\",\"max_attempts\":2,\"question_count\":10,\"show_answers_after_submit\":0,\"status\":\"active\",\"questions\":[{\"id\":3,\"question_text\":\"What does CPU stand for?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Central Processing Unit\",\"is_correct\":0,\"order_index\":1},{\"option_text\":\"Computer Processing Unit\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', 1, 1, 1, '2026-08-19 20:42:37', '2026-08-19 20:42:37'),
(2, 'hakdohfga', 'Master modern JavaScript including ES6+, async/await, closures, and functional programming.', 'Programming', 35, '{\"modules\":[{\"id\":6,\"title\":\"Module 1: Fundamentals\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":12,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":13,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":14,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":7,\"title\":\"Quiz: Module 1\",\"duration_seconds\":300,\"passing_score\":\"71.00\",\"max_attempts\":3,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":22,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":23,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":24,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]},{\"id\":25,\"question_text\":\"How does this technique improve performance?\",\"question_type\":\"single_choice\",\"order_index\":4,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":26,\"question_text\":\"What is the correct sequence of steps?\",\"question_type\":\"single_choice\",\"order_index\":5,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]}]}]},{\"id\":7,\"title\":\"Module 2: Core Concepts\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":2,\"status\":\"active\",\"lessons\":[{\"id\":15,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":16,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":17,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":8,\"title\":\"Quiz: Module 2\",\"duration_seconds\":180,\"passing_score\":\"75.00\",\"max_attempts\":3,\"question_count\":3,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":27,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":28,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":29,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', 2, 6, 2, '2026-08-26 08:57:27', '2026-08-26 08:57:27'),
(3, 'haikdog', 'Master modern JavaScript including ES6+, async/await, closures, and functional programming.', 'Programming', 35, '{\"modules\":[{\"id\":6,\"title\":\"Module 1: Fundamentals\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"id\":12,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":13,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":14,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":7,\"title\":\"Quiz: Module 1\",\"duration_seconds\":300,\"passing_score\":\"71.00\",\"max_attempts\":3,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":22,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":23,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":24,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]},{\"id\":25,\"question_text\":\"How does this technique improve performance?\",\"question_type\":\"single_choice\",\"order_index\":4,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":26,\"question_text\":\"What is the correct sequence of steps?\",\"question_type\":\"single_choice\",\"order_index\":5,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]}]}]},{\"id\":7,\"title\":\"Module 2: Core Concepts\",\"description\":\"This module covers essential concepts and practical exercises for programming training.\",\"order_index\":2,\"status\":\"active\",\"lessons\":[{\"id\":15,\"title\":\"Lesson 1: Introduction\",\"content_type\":\"text\",\"content_body\":\"<h3>Introduction<\\/h3><p>Getting started with the basics of this topic. Understand the core concepts and terminology.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":null,\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":16,\"title\":\"Lesson 2: Core Concepts\",\"content_type\":\"video\",\"content_body\":\"<h3>Core Concepts<\\/h3><p>Deep dive into the fundamental principles. Learn how each component works and interacts.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":2,\"status\":\"active\",\"quizzes\":[],\"files\":[]},{\"id\":17,\"title\":\"Lesson 3: Practical Application\",\"content_type\":\"mixed\",\"content_body\":\"<h3>Practical Application<\\/h3><p>Apply your knowledge through hands-on exercises and real-world scenarios.<\\/p><p>In this lesson, we will explore the topic in detail with examples and exercises. By the end, you should be able to apply these concepts in practical scenarios.<\\/p>\",\"video_url\":\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\",\"order_index\":3,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"id\":8,\"title\":\"Quiz: Module 2\",\"duration_seconds\":180,\"passing_score\":\"75.00\",\"max_attempts\":3,\"question_count\":3,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[{\"id\":27,\"question_text\":\"What is the primary purpose of this concept?\",\"question_type\":\"single_choice\",\"order_index\":1,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":28,\"question_text\":\"Which approach is most appropriate for this scenario?\",\"question_type\":\"single_choice\",\"order_index\":2,\"options\":[{\"option_text\":\"Strongly Agree\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"Agree\",\"is_correct\":0,\"order_index\":2},{\"option_text\":\"Neutral\",\"is_correct\":0,\"order_index\":3},{\"option_text\":\"Disagree\",\"is_correct\":0,\"order_index\":4}]},{\"id\":29,\"question_text\":\"What are the key components of this system?\",\"question_type\":\"true_false\",\"order_index\":3,\"options\":[{\"option_text\":\"True\",\"is_correct\":1,\"order_index\":1},{\"option_text\":\"False\",\"is_correct\":0,\"order_index\":2}]}]}]}]}', 2, 6, 2, '2026-08-26 11:00:27', '2026-08-26 11:00:27'),
(4, 'Onboarding Essentials Template', 'A ready-made one-module template for new-hire orientation courses.', 'Onboarding', 1001, '{\"modules\":[{\"title\":\"Welcome and Orientation\",\"description\":\"Company overview, culture, and expectations.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"Welcome to the Company\",\"content_type\":\"text\",\"content_body\":\"<p>An introduction to our mission and values.</p>\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[]}]}', 1, 1, 0, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(5, 'Compliance Training Template', 'A reusable template for mandatory compliance and policy training.', 'Compliance & Safety', 1001, '{\"modules\":[{\"title\":\"Policies and Procedures\",\"description\":\"Key policies every employee must follow.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"Understanding Company Policies\",\"content_type\":\"text\",\"content_body\":\"<p>Review the policies that apply to your role.</p>\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[{\"title\":\"Policy Knowledge Check\",\"duration_seconds\":600,\"passing_score\":\"80.00\",\"max_attempts\":2,\"question_count\":5,\"show_answers_after_submit\":1,\"status\":\"active\",\"questions\":[]}]}]}', 1, 1, 1, '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(6, 'Leadership Workshop Template', 'A starter template for leadership and management workshops.', 'Leadership', 1001, '{\"modules\":[{\"title\":\"Leading People\",\"description\":\"Core people-leadership concepts.\",\"order_index\":1,\"status\":\"active\",\"lessons\":[{\"title\":\"What Makes a Great Leader\",\"content_type\":\"video\",\"video_url\":\"https://www.youtube.com/embed/1Y9d3lR6Qm4\",\"order_index\":1,\"status\":\"active\",\"quizzes\":[],\"files\":[]}],\"quizzes\":[]}]}', 1, 1, 0, '2026-10-15 13:24:54', '2026-10-15 13:24:54');

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
-- Table structure for table `ld_cron_run`
--

CREATE TABLE `ld_cron_run` (
  `id` int(10) UNSIGNED NOT NULL,
  `job` varchar(80) NOT NULL,
  `trigger_type` enum('scheduled','manual','cli') NOT NULL DEFAULT 'scheduled',
  `status` enum('running','success','failed') NOT NULL DEFAULT 'running',
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_ms` int(10) UNSIGNED DEFAULT NULL,
  `records_processed` int(10) UNSIGNED DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_cron_run`
--

INSERT INTO `ld_cron_run` (`id`, `job`, `trigger_type`, `status`, `started_at`, `finished_at`, `duration_ms`, `records_processed`, `message`, `actor_id`) VALUES
(5, 'engagement_snapshot', 'scheduled', 'success', '2026-09-25 21:44:14', '2026-09-25 21:44:14', 98, 11, 'Upserted engagement snapshots for 11 learners on 2026-09-25', 35),
(6, 'engagement_snapshot', 'manual', 'success', '2026-09-25 21:56:37', '2026-09-25 21:56:38', 79, 11, 'Upserted engagement snapshots for 11 learners on 2026-09-25', 35);

-- --------------------------------------------------------

--
-- Table structure for table `ld_display_preference`
--

CREATE TABLE `ld_display_preference` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) NOT NULL,
  `page_size` tinyint(3) UNSIGNED NOT NULL DEFAULT 10,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_display_preference_store`
--

CREATE TABLE `ld_display_preference_store` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `page_size` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `view_mode` enum('grid','list') NOT NULL DEFAULT 'grid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_engagement_snapshot`
--

CREATE TABLE `ld_engagement_snapshot` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(10) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `active_days_30` int(10) UNSIGNED DEFAULT 0,
  `courses_in_progress` int(10) UNSIGNED DEFAULT 0,
  `courses_completed_ytd` int(10) UNSIGNED DEFAULT 0,
  `avg_score` decimal(5,2) DEFAULT NULL,
  `quizzes_started` int(10) UNSIGNED DEFAULT 0,
  `quizzes_abandoned` int(10) UNSIGNED DEFAULT 0,
  `days_since_last_activity` int(10) UNSIGNED DEFAULT NULL,
  `expired_certificates` int(10) UNSIGNED DEFAULT 0,
  `risk_score` decimal(5,2) DEFAULT NULL,
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_factors`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_engagement_snapshot`
--

INSERT INTO `ld_engagement_snapshot` (`id`, `learner_id`, `snapshot_date`, `active_days_30`, `courses_in_progress`, `courses_completed_ytd`, `avg_score`, `quizzes_started`, `quizzes_abandoned`, `days_since_last_activity`, `expired_certificates`, `risk_score`, `risk_factors`, `created_at`) VALUES
(1, 1, '2026-09-05', 1, 0, 0, NULL, 0, 0, 5, 0, 0.00, '[]', '2026-09-04 20:27:26'),
(2, 2, '2026-09-05', 1, 0, 0, NULL, 0, 0, 5, 0, 0.00, '[]', '2026-09-04 20:27:26'),
(3, 33, '2026-09-05', 1, 0, 3, NULL, 0, 0, 0, 0, 0.00, '[]', '2026-09-04 20:27:26'),
(4, 34, '2026-09-05', 1, 5, 3, NULL, 0, 0, 0, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-04 20:27:26'),
(5, 35, '2026-09-05', 2, 1, 0, NULL, 2, 0, 7, 0, 0.00, '[]', '2026-09-04 20:27:26'),
(6, 36, '2026-09-05', 1, 5, 2, NULL, 0, 0, 12, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-04 20:27:26'),
(7, 37, '2026-09-05', 1, 4, 3, NULL, 0, 0, 2, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-04 20:27:26'),
(8, 38, '2026-09-05', 1, 5, 2, NULL, 0, 0, 13, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-04 20:27:26'),
(9, 39, '2026-09-05', 1, 2, 2, NULL, 0, 0, 0, 0, 0.00, '[]', '2026-09-04 20:27:26'),
(10, 67999, '2026-09-05', 7, 15, 3, NULL, 3, 0, 6, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-04 20:27:26'),
(21, 1, '2026-09-25', 1, 0, 0, NULL, 0, 0, 26, 0, 0.00, '[]', '2026-09-24 21:30:13'),
(22, 2, '2026-09-25', 1, 0, 0, NULL, 0, 0, 26, 0, 0.00, '[]', '2026-09-24 21:30:13'),
(23, 13, '2026-09-25', 1, 3, 0, NULL, 0, 0, 18, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-24 21:30:13'),
(24, 33, '2026-09-25', 0, 0, 3, NULL, 0, 0, 19, 0, 0.00, '[]', '2026-09-24 21:30:13'),
(25, 34, '2026-09-25', 0, 5, 3, NULL, 0, 0, 5, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-24 21:30:13'),
(26, 35, '2026-09-25', 1, 1, 0, NULL, 2, 0, 28, 0, 0.00, '[]', '2026-09-24 21:30:13'),
(27, 36, '2026-09-25', 0, 5, 2, NULL, 0, 0, 32, 0, 45.00, '[\"inactive_30_days\",\"many_courses_in_progress\"]', '2026-09-24 21:30:13'),
(28, 37, '2026-09-25', 0, 4, 3, NULL, 0, 0, 22, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-24 21:30:13'),
(29, 38, '2026-09-25', 0, 5, 2, NULL, 0, 0, 33, 0, 45.00, '[\"inactive_30_days\",\"many_courses_in_progress\"]', '2026-09-24 21:30:13'),
(30, 39, '2026-09-25', 0, 2, 2, NULL, 0, 0, 7, 0, 0.00, '[]', '2026-09-24 21:30:13'),
(31, 67999, '2026-09-25', 3, 16, 3, NULL, 6, 0, 18, 0, 15.00, '[\"many_courses_in_progress\"]', '2026-09-24 21:30:13');

-- --------------------------------------------------------

--
-- Table structure for table `ld_enrollment`
--

CREATE TABLE `ld_enrollment` (
  `id` int(10) UNSIGNED NOT NULL,
  `learner_id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `course_version_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('invited','enrolled','in_progress','completed','withdrawn') NOT NULL DEFAULT 'enrolled',
  `invited_by` int(10) UNSIGNED DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_enrollment`
--

INSERT INTO `ld_enrollment` (`id`, `learner_id`, `course_id`, `course_version_id`, `status`, `invited_by`, `enrolled_at`, `completed_at`, `last_accessed_at`, `created_at`) VALUES
(1, 1006, 5, NULL, 'completed', NULL, '2026-10-08 16:14:38', '2026-10-08 17:15:10', '2026-10-08 17:15:10', '2026-10-08 16:14:38'),
(2, 1006, 6, NULL, 'completed', NULL, '2026-10-09 02:58:40', '2026-10-09 02:59:05', '2026-10-15 14:58:06', '2026-10-09 02:58:40'),
(4, 1006, 12, NULL, 'enrolled', NULL, '2026-10-15 13:46:15', NULL, '2026-10-15 15:31:37', '2026-10-15 13:46:15'),
(5, 1006, 8, NULL, 'completed', NULL, '2026-10-15 14:23:45', '2026-10-15 15:49:25', '2026-10-15 15:49:25', '2026-10-15 14:23:45');

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

--
-- Dumping data for table `ld_evaluation`
--

INSERT INTO `ld_evaluation` (`id`, `course_id`, `title`, `duration_seconds`, `passing_score`, `max_attempts`, `question_count`, `show_answers_after_submit`, `status`, `created_at`, `updated_at`) VALUES
(2, 5, 'Final Assessment', 1800, 85.00, 2, 1, 0, 'active', '2026-10-08 09:45:36', '2026-10-08 09:45:36'),
(3, 6, 'Final Assessment', 1800, 85.00, 2, 2, 0, 'active', '2026-10-08 18:27:42', '2026-10-08 18:27:42'),
(4, 7, 'Final Assessment: Workplace Safety and Compliance Essentials', 1800, 85.00, 2, 10, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(5, 8, 'Final Assessment: Effective Business Communication', 1800, 80.00, 2, 10, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(6, 9, 'Final Assessment: Data Analysis with Microsoft Excel', 1800, 80.00, 2, 12, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(7, 10, 'Final Assessment: Leadership Foundations for New Managers', 1800, 80.00, 2, 10, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(8, 11, 'Final Assessment: Customer Service Excellence', 1800, 80.00, 2, 10, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(9, 12, 'Final Assessment: Cybersecurity Awareness for Employees', 1800, 85.00, 2, 12, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54');

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
  `learner_id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `status` enum('passed','failed') NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_grade`
--

INSERT INTO `ld_grade` (`id`, `learner_id`, `course_id`, `final_score`, `status`, `issued_at`) VALUES
(0, 1006, 5, 0.00, 'failed', '2026-10-08 17:15:10'),
(1, 1006, 8, 80.00, 'passed', '2026-10-15 15:49:25');

-- --------------------------------------------------------

--
-- Table structure for table `ld_instructor`
--

CREATE TABLE `ld_instructor` (
  `employee_id` int(10) UNSIGNED NOT NULL,
  `designated_by` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `designated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_instructor`
--

INSERT INTO `ld_instructor` (`employee_id`, `designated_by`, `note`, `designated_at`) VALUES
(99967, NULL, 'Seeded L&D instructor test account', '2026-09-25 21:31:21');

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
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` enum('standard','knowledge_transfer') NOT NULL DEFAULT 'standard',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `kt_plan_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_learning_path`
--

INSERT INTO `ld_learning_path` (`id`, `instructor_id`, `title`, `description`, `assigned_to`, `status`, `created_at`, `updated_at`, `type`, `is_public`, `kt_plan_id`) VALUES
(3, 1007, 'Knowledge Transfer — Russel Gohetia Cabrido', 'Knowledge transfer path for Russel Gohetia Cabrido. Contains 0 completed or in-progress courses.', NULL, 'active', '2026-10-08 06:44:17', '2026-10-08 06:44:17', 'knowledge_transfer', 0, 18),
(4, 1007, 'Knowledge Transfer — Jayson Paigma', 'Knowledge transfer path for Jayson Paigma. Contains 1 completed or in-progress courses.', NULL, 'active', '2026-10-08 18:53:21', '2026-10-08 18:53:21', 'knowledge_transfer', 0, 19),
(5, 1007, 'qwceqecqw', 'qwcecqe', NULL, 'active', '2026-10-09 03:19:56', '2026-10-09 04:04:33', 'standard', 1, NULL),
(6, 1001, 'New Hire Onboarding Journey', 'The essential first-30-days path for every new employee: safety, communication, and security fundamentals.', NULL, 'active', '2026-10-15 13:24:54', '2026-10-15 16:42:20', 'standard', 1, NULL),
(7, 1001, 'Aspiring Team Lead Track', 'A guided path for individual contributors who want to step into team leadership.', NULL, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54', 'standard', 1, NULL),
(8, 1001, 'Data and Analytics Career Path', 'Build the spreadsheet and communication skills needed to grow into a data-focused role.', NULL, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54', 'standard', 1, NULL),
(9, 1001, 'Workplace Safety Certification Path', 'Complete the required safety training and assessment to earn the internal safety certification.', NULL, 'active', '2026-10-15 13:24:54', '2026-10-15 16:37:47', 'standard', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ld_learning_path_invitation`
--

CREATE TABLE `ld_learning_path_invitation` (
  `id` int(10) UNSIGNED NOT NULL,
  `learning_path_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `status` enum('invited','enrolled','declined','withdrawn') NOT NULL DEFAULT 'invited',
  `invited_by` int(10) UNSIGNED DEFAULT NULL,
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
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

--
-- Dumping data for table `ld_learning_path_item`
--

INSERT INTO `ld_learning_path_item` (`id`, `learning_path_id`, `item_type`, `reference_id`, `order_index`, `status`) VALUES
(1, 4, 'course', 5, 1, 'active'),
(4, 5, 'course', 6, 0, 'active'),
(5, 5, 'module', 3, 0, 'active'),
(6, 7, 'program', 4, 3, 'active'),
(7, 9, 'evaluation', 4, 3, 'active'),
(8, 9, 'quiz', 6, 2, 'active'),
(9, 8, 'module', 10, 2, 'active'),
(10, 7, 'module', 12, 2, 'active'),
(11, 6, 'course', 7, 1, 'active'),
(12, 9, 'course', 7, 1, 'active'),
(13, 6, 'course', 8, 2, 'active'),
(14, 8, 'course', 8, 3, 'active'),
(15, 8, 'course', 9, 1, 'active'),
(16, 7, 'course', 10, 1, 'active'),
(17, 6, 'course', 12, 3, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `ld_learning_path_skill`
--

CREATE TABLE `ld_learning_path_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `learning_path_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_learning_path_skill`
--

INSERT INTO `ld_learning_path_skill` (`id`, `learning_path_id`, `skill_id`) VALUES
(3, 5, 13),
(4, 5, 15),
(7, 6, 16),
(5, 7, 2),
(6, 8, 5),
(8, 9, 16);

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

--
-- Dumping data for table `ld_lesson`
--

INSERT INTO `ld_lesson` (`id`, `module_id`, `title`, `content_type`, `content_body`, `video_url`, `order_index`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'Basic Queries', 'text', 'Hello World', 'https://www.youtube.com/watch?v=eFIMruzx_Rs&list=RDeFIMruzx_Rs', 0, 'active', '2026-10-08 09:45:36', '2026-10-08 09:45:36'),
(2, 4, 'ecqweqwc', 'text', '<p>ecqw<strong>eqwcq</strong>w</p><p><br></p><p><em>6trdnydrtn</em></p><p><br></p><blockquote><em>56n756n75</em></blockquote><blockquote><br></blockquote><blockquote><em class=\"ql-font-monospace\">789,</em></blockquote><blockquote><br></blockquote><h1><em>yugomyfyum tfnuf</em></h1>', 'https://www.youtube.com/watch?v=rHbJXpSXVeU&list=RDrHbJXpSXVeU', 0, 'active', '2026-10-08 18:27:42', '2026-10-08 18:28:31'),
(3, 5, 'Why Workplace Safety Matters', 'text', '<h3>Introduction</h3><p>A safe workplace protects people, productivity, and reputation. This lesson explains the business and human reasons behind workplace safety.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(4, 5, 'Roles, Rights, and Responsibilities', 'text', '<h3>Everyone Has a Role</h3><p>Understand employee rights, employer duties, and how to report concerns through the correct channels.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(5, 6, 'Spotting Common Workplace Hazards', 'text', '<h3>Hazard Awareness</h3><p>Learn how to identify physical, ergonomic, chemical, and psychosocial hazards before they cause harm.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(6, 6, 'The Hierarchy of Risk Controls', 'text', '<h3>Controlling Risk</h3><p>Elimination, substitution, engineering controls, administrative controls, and PPE - applied in the right order.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(7, 7, 'The Communication Process', 'text', '<h3>Sender, Message, Receiver</h3><p>Explore the communication loop and the barriers that cause misunderstandings at work.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(8, 7, 'Active Listening Techniques', 'video', NULL, 'https://www.youtube.com/embed/1Y9d3lR6Qm4', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(9, 8, 'Writing Clear Emails and Reports', 'text', '<h3>Write to Be Understood</h3><p>Structure business messages with a clear purpose, audience, and call to action.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(10, 8, 'Delivering Confident Presentations', 'video', NULL, 'https://www.youtube.com/embed/AykYRO5d_lI', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(11, 9, 'Navigating Excel and Key Functions', 'text', '<h3>Excel Basics</h3><p>Work with cells, ranges, formulas, and lookup functions that form the backbone of analysis.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(12, 9, 'Cleaning and Organizing Data', 'text', '<h3>From Messy to Tidy</h3><p>Remove duplicates, standardize values, and structure data for reliable reporting.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(13, 10, 'Building Charts and Pivot Tables', 'video', NULL, 'https://www.youtube.com/embed/xbLcfBGU5Qc', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(14, 10, 'Presenting Insights from Data', 'text', '<h3>Telling the Story</h3><p>Choose the right chart and highlight the insight that drives a decision.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(15, 11, 'From Team Member to Team Lead', 'text', '<h3>A New Perspective</h3><p>Shift from doing the work to enabling the team to do the work.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(16, 11, 'Setting Expectations and Goals', 'text', '<h3>Clarity Drives Performance</h3><p>Define outcomes, agree on measures, and align the team around shared goals.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(17, 12, 'Giving Constructive Feedback', 'video', NULL, 'https://www.youtube.com/embed/6z4g7w2KqEo', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(18, 12, 'Running One-on-One Meetings', 'text', '<h3>Make Time Count</h3><p>A repeatable agenda for check-ins that develop people and surface issues early.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(19, 13, 'Understanding Customer Expectations', 'text', '<h3>What Customers Want</h3><p>Responsiveness, reliability, empathy, and a resolution - every time.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(20, 13, 'Building Rapport and Empathy', 'text', '<h3>Connect First</h3><p>Simple techniques to make customers feel heard and valued.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(21, 14, 'De-escalation Techniques', 'video', NULL, 'https://www.youtube.com/embed/1EvUCfXWLoU', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(22, 14, 'Resolving Complaints Effectively', 'text', '<h3>From Complaint to Resolution</h3><p>A structured approach to acknowledge, investigate, and resolve complaints.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(23, 15, 'Common Cyber Threats Explained', 'text', '<h3>Know the Threats</h3><p>Malware, ransomware, social engineering, and insider risk explained in plain language.</p>', NULL, 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(24, 15, 'Protecting Company Data', 'text', '<h3>Data Handling</h3><p>Classify, store, share, and dispose of information safely.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(25, 16, 'Recognizing Phishing Attempts', 'video', NULL, 'https://www.youtube.com/embed/XBkzBrXlle0', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(26, 16, 'Strong Passwords and Multi-Factor Authentication', 'text', '<h3>Lock the Door</h3><p>Create strong passphrases and enable MFA on every account.</p>', NULL, 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54');

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
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
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

--
-- Dumping data for table `ld_module`
--

INSERT INTO `ld_module` (`id`, `course_id`, `title`, `description`, `order_index`, `status`, `created_at`, `updated_at`) VALUES
(3, 5, 'Introduction to SQL', 'Lorem ipiosum', 0, 'active', '2026-10-08 09:45:36', '2026-10-08 09:45:36'),
(4, 6, 'cghffg', 'wecqwecqw', 0, 'active', '2026-10-08 18:27:42', '2026-10-08 18:27:42'),
(5, 7, 'Safety Fundamentals and Responsibilities', 'Why safety matters and what every employee is responsible for.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(6, 7, 'Hazard Identification and Risk Control', 'Spot hazards early and apply the hierarchy of controls to reduce risk.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(7, 8, 'Communication Essentials', 'The communication process, listening, and building rapport at work.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(8, 8, 'Professional Writing and Presentations', 'Write clear emails and reports and deliver confident presentations.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(9, 9, 'Excel Foundations and Data Cleaning', 'Navigate Excel and prepare messy data for analysis.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(10, 9, 'Data Visualization and Reporting', 'Build charts, pivot tables, and reports that communicate insight.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(11, 10, 'Transitioning into Leadership', 'Move from individual contributor to trusted team leader.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(12, 10, 'Coaching and Performance Conversations', 'Give feedback and run effective one-on-one meetings.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(13, 11, 'Customer Service Mindset', 'Understand customer expectations and build empathy and rapport.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(14, 11, 'Handling Difficult Customer Situations', 'De-escalate tension and resolve complaints effectively.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(15, 12, 'Cybersecurity Essentials', 'Understand common threats and how to protect company data.', 1, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(16, 12, 'Phishing, Passwords, and Safe Browsing', 'Recognize phishing and apply safe password and browsing habits.', 2, 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `ld_module_skill`
--

CREATE TABLE `ld_module_skill` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_module_skill`
--

INSERT INTO `ld_module_skill` (`id`, `module_id`, `skill_id`) VALUES
(1, 6, 16),
(2, 7, 3),
(3, 9, 14),
(4, 10, 5),
(5, 12, 2);

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
  `user_id` int(11) NOT NULL,
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
-- Table structure for table `ld_notification_inbox`
--

CREATE TABLE `ld_notification_inbox` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `user_id` int(10) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ld_notification_preference_store`
--

CREATE TABLE `ld_notification_preference_store` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `instructor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ld_program`
--

INSERT INTO `ld_program` (`id`, `instructor_id`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1007, 'xtatcae', 'tacratctevt', 'active', '2026-10-08 18:28:55', '2026-10-08 18:28:55'),
(2, 1007, 'dsadccccsad', 'ascda', 'active', '2026-10-08 18:46:48', '2026-10-08 18:46:48'),
(3, 1007, 'dsadccccsad', 'ascda', 'active', '2026-10-08 18:46:49', '2026-10-08 18:46:49'),
(4, 1001, 'Management Development Program', 'A structured program that prepares high-potential employees for people-leadership roles through courses, workshops, and coaching.', 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(5, 1001, 'Graduate Trainee Program', 'An onboarding and development program that rotates new graduates through core business functions.', 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54'),
(6, 1001, 'Customer Experience Excellence Program', 'A blended program that builds consistent customer service standards across customer-facing teams.', 'active', '2026-10-15 13:24:54', '2026-10-15 13:24:54');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `ld_assessment_result`
--
ALTER TABLE `ld_assessment_result`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assessment_learner` (`learner_id`),
  ADD KEY `idx_assessment_source` (`source`,`taken_at`);

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
-- Indexes for table `ld_competency_requirement`
--
ALTER TABLE `ld_competency_requirement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_competency_status` (`status`),
  ADD KEY `idx_competency_priority` (`priority_level`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ld_course_instructor_employee` (`instructor_id`);

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
  ADD KEY `ld_course_skill_ibfk_2` (`skill_id`);

--
-- Indexes for table `ld_course_template`
--
ALTER TABLE `ld_course_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ld_course_version`
--
ALTER TABLE `ld_course_version`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `ld_cron_run`
--
ALTER TABLE `ld_cron_run`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cron_run_job` (`job`,`started_at`),
  ADD KEY `idx_cron_run_status` (`status`);

--
-- Indexes for table `ld_display_preference`
--
ALTER TABLE `ld_display_preference`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `ld_display_preference_store`
--
ALTER TABLE `ld_display_preference_store`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_display_preference_user` (`user_id`);

--
-- Indexes for table `ld_engagement_snapshot`
--
ALTER TABLE `ld_engagement_snapshot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_learner_snapshot` (`learner_id`,`snapshot_date`);

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
  ADD KEY `course_id` (`course_id`),
  ADD KEY `fk_ld_grade_learner_employee` (`learner_id`);

--
-- Indexes for table `ld_instructor`
--
ALTER TABLE `ld_instructor`
  ADD PRIMARY KEY (`employee_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ld_learning_path_instructor_employee` (`instructor_id`),
  ADD KEY `idx_ld_learning_path_visibility` (`status`,`is_public`),
  ADD KEY `idx_ld_learning_path_kt_plan` (`kt_plan_id`);

--
-- Indexes for table `ld_learning_path_invitation`
--
ALTER TABLE `ld_learning_path_invitation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ld_path_invitation_recipient` (`learning_path_id`,`employee_id`),
  ADD KEY `idx_ld_path_invitation_employee` (`employee_id`,`status`);

--
-- Indexes for table `ld_learning_path_item`
--
ALTER TABLE `ld_learning_path_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learning_path_id` (`learning_path_id`);

--
-- Indexes for table `ld_learning_path_skill`
--
ALTER TABLE `ld_learning_path_skill`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lp_skill` (`learning_path_id`,`skill_id`);

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
  ADD KEY `idx_message_recipient` (`recipient_id`,`is_read`),
  ADD KEY `fk_ld_message_sender_employee` (`sender_id`);

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
-- Indexes for table `ld_notification_inbox`
--
ALTER TABLE `ld_notification_inbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_inbox_user` (`user_id`,`is_read`);

--
-- Indexes for table `ld_notification_preference`
--
ALTER TABLE `ld_notification_preference`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_notif_type` (`user_id`,`notification_type`);

--
-- Indexes for table `ld_notification_preference_store`
--
ALTER TABLE `ld_notification_preference_store`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_preference_type` (`user_id`,`notification_type`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ld_program_instructor_employee` (`instructor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ld_announcement`
--
ALTER TABLE `ld_announcement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ld_assessment_result`
--
ALTER TABLE `ld_assessment_result`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ld_certificate`
--
ALTER TABLE `ld_certificate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ld_competency_requirement`
--
ALTER TABLE `ld_competency_requirement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ld_course`
--
ALTER TABLE `ld_course`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ld_course_skill`
--
ALTER TABLE `ld_course_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ld_course_template`
--
ALTER TABLE `ld_course_template`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ld_cron_run`
--
ALTER TABLE `ld_cron_run`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ld_display_preference_store`
--
ALTER TABLE `ld_display_preference_store`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ld_engagement_snapshot`
--
ALTER TABLE `ld_engagement_snapshot`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `ld_enrollment`
--
ALTER TABLE `ld_enrollment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ld_evaluation`
--
ALTER TABLE `ld_evaluation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ld_learning_path`
--
ALTER TABLE `ld_learning_path`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ld_learning_path_invitation`
--
ALTER TABLE `ld_learning_path_invitation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ld_learning_path_item`
--
ALTER TABLE `ld_learning_path_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ld_learning_path_skill`
--
ALTER TABLE `ld_learning_path_skill`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ld_lesson`
--
ALTER TABLE `ld_lesson`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `ld_module`
--
ALTER TABLE `ld_module`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `ld_notification_inbox`
--
ALTER TABLE `ld_notification_inbox`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ld_notification_preference_store`
--
ALTER TABLE `ld_notification_preference_store`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ld_program`
--
ALTER TABLE `ld_program`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ld_audit_log`
--
ALTER TABLE `ld_audit_log`
  ADD CONSTRAINT `fk_ld_audit_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE;

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
-- Constraints for table `ld_course`
--
ALTER TABLE `ld_course`
  ADD CONSTRAINT `fk_ld_course_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `ld_display_preference`
--
ALTER TABLE `ld_display_preference`
  ADD CONSTRAINT `fk_ld_display_preference_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ld_display_preference_store`
--
ALTER TABLE `ld_display_preference_store`
  ADD CONSTRAINT `fk_ld_display_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ld_enrollment`
--
ALTER TABLE `ld_enrollment`
  ADD CONSTRAINT `fk_ld_enrollment_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
  ADD CONSTRAINT `fk_ld_grade_learner_employee` FOREIGN KEY (`learner_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ld_grade_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_learning_path`
--
ALTER TABLE `ld_learning_path`
  ADD CONSTRAINT `fk_ld_learning_path_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ld_learning_path_invitation`
--
ALTER TABLE `ld_learning_path_invitation`
  ADD CONSTRAINT `fk_ld_path_invitation_path` FOREIGN KEY (`learning_path_id`) REFERENCES `ld_learning_path` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `ld_message`
--
ALTER TABLE `ld_message`
  ADD CONSTRAINT `fk_ld_message_recipient_employee` FOREIGN KEY (`recipient_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ld_message_sender_employee` FOREIGN KEY (`sender_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `ld_notification`
--
ALTER TABLE `ld_notification`
  ADD CONSTRAINT `fk_ld_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ld_notification_inbox`
--
ALTER TABLE `ld_notification_inbox`
  ADD CONSTRAINT `fk_ld_notification_inbox_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ld_notification_preference`
--
ALTER TABLE `ld_notification_preference`
  ADD CONSTRAINT `fk_ld_notification_preference_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ld_notification_preference_store`
--
ALTER TABLE `ld_notification_preference_store`
  ADD CONSTRAINT `fk_ld_notification_preference_employee` FOREIGN KEY (`user_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ld_prerequisite`
--
ALTER TABLE `ld_prerequisite`
  ADD CONSTRAINT `ld_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_prerequisite_ibfk_2` FOREIGN KEY (`required_course_id`) REFERENCES `ld_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ld_prerequisite_ibfk_3` FOREIGN KEY (`required_skill_id`) REFERENCES `ld_skill` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ld_program`
--
ALTER TABLE `ld_program`
  ADD CONSTRAINT `fk_ld_program_instructor_employee` FOREIGN KEY (`instructor_id`) REFERENCES `em_employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
