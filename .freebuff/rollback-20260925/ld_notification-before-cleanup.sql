-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hrms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `ld_notification`
--

LOCK TABLES `ld_notification` WRITE;
/*!40000 ALTER TABLE `ld_notification` DISABLE KEYS */;
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (4,35,'conference_reminder','Video conference starts in 30 minutes','Data Privacy Awareness: the session room opens at 2:00 PM.','video_conference',NULL,1,'2026-09-18 04:32:48');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (5,35,'certificate_expiry','Your certificate expires in 14 days','Complete the refresher course to keep your certification valid.','certificate',NULL,1,'2026-09-18 01:57:48');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (6,35,'enrollment','You were enrolled in Data Privacy Awareness','The course is now available in My Learning.','course',NULL,1,'2026-09-16 04:57:48');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (7,35,'grade','Quiz result posted: Data Privacy Basics','You scored 85% on the Data Privacy Basics quiz.','quiz',NULL,1,'2026-09-09 04:57:48');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (35,35,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Records management\" (ADMIN STAFF).',NULL,NULL,1,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (36,99967,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Records management\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (37,29,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Records management\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (38,30,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Records management\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (39,35,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cybersecurity awareness\" (ADMIN STAFF).',NULL,NULL,1,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (40,99967,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cybersecurity awareness\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (41,29,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cybersecurity awareness\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (42,30,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cybersecurity awareness\" (ADMIN STAFF).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (43,35,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — David Aquino\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,1,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (44,99967,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — David Aquino\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (45,29,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — David Aquino\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (46,30,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — David Aquino\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:38:55');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (47,35,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — Felix Del Rosario\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,1,'2026-09-25 08:52:06');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (48,99967,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — Felix Del Rosario\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:06');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (49,29,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — Felix Del Rosario\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:06');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (50,30,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Retention follow-up — Felix Del Rosario\" (PSYCHOLOGY DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:06');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (51,35,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cloud fundamentals workshop\" (IT DEPARTMENT).',NULL,NULL,1,'2026-09-25 08:52:19');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (52,99967,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cloud fundamentals workshop\" (IT DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:19');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (53,29,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cloud fundamentals workshop\" (IT DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:19');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (54,30,'training_request','New Training Request','New training request from a Workforce Analytics record: \"Cloud fundamentals workshop\" (IT DEPARTMENT).',NULL,NULL,0,'2026-09-25 08:52:19');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (55,35,'training_request','New Training Request','David Aquino requested training for \"Teamwork\".',NULL,NULL,1,'2026-09-25 08:54:23');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (56,99967,'training_request','New Training Request','David Aquino requested training for \"Teamwork\".',NULL,NULL,0,'2026-09-25 08:54:23');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (57,29,'training_request','New Training Request','David Aquino requested training for \"Teamwork\".',NULL,NULL,0,'2026-09-25 08:54:23');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (58,30,'training_request','New Training Request','David Aquino requested training for \"Teamwork\".',NULL,NULL,0,'2026-09-25 08:54:23');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (59,35,'workforce_alert','Critical turnover risk','Workforce Analytics flagged David Aquino (PSYCHOLOGY DEPARTMENT) as a critical turnover risk (score 88). Triage it on Workforce Integration.','workforce_turnover_risk',3,1,'2026-09-25 09:51:33');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (60,1018,'workforce_alert','Critical turnover risk','Workforce Analytics flagged David Aquino (PSYCHOLOGY DEPARTMENT) as a critical turnover risk (score 88). Triage it on Workforce Integration.','workforce_turnover_risk',3,0,'2026-09-25 09:51:33');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (61,67999,'workforce_alert','Critical turnover risk','Workforce Analytics flagged David Aquino (PSYCHOLOGY DEPARTMENT) as a critical turnover risk (score 88). Triage it on Workforce Integration.','workforce_turnover_risk',3,0,'2026-09-25 09:51:33');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (62,99967,'workforce_alert','Critical turnover risk','Workforce Analytics flagged David Aquino (PSYCHOLOGY DEPARTMENT) as a critical turnover risk (score 88). Triage it on Workforce Integration.','workforce_turnover_risk',3,0,'2026-09-25 09:51:33');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (63,35,'workforce_alert','Critical skill need','Workforce Analytics sent a critical skill need: \"Advanced Excel for payroll\" for ADMIN STAFF (9 learner(s) need training). Triage it on Workforce Integration.','workforce_competency',4,1,'2026-09-25 09:51:34');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (64,1018,'workforce_alert','Critical skill need','Workforce Analytics sent a critical skill need: \"Advanced Excel for payroll\" for ADMIN STAFF (9 learner(s) need training). Triage it on Workforce Integration.','workforce_competency',4,0,'2026-09-25 09:51:34');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (65,67999,'workforce_alert','Critical skill need','Workforce Analytics sent a critical skill need: \"Advanced Excel for payroll\" for ADMIN STAFF (9 learner(s) need training). Triage it on Workforce Integration.','workforce_competency',4,0,'2026-09-25 09:51:34');
INSERT INTO `ld_notification` (`id`, `user_id`, `type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES (66,99967,'workforce_alert','Critical skill need','Workforce Analytics sent a critical skill need: \"Advanced Excel for payroll\" for ADMIN STAFF (9 learner(s) need training). Triage it on Workforce Integration.','workforce_competency',4,0,'2026-09-25 09:51:34');
/*!40000 ALTER TABLE `ld_notification` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25 23:20:36
