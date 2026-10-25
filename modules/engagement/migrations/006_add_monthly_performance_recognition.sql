CREATE TABLE IF NOT EXISTS `eer_recognition_cycles` (
  `month_year` varchar(7) NOT NULL,
  `review_period` varchar(100) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`month_year`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `fk_eer_recognition_cycles_approver`
    FOREIGN KEY (`approved_by`)
    REFERENCES `em_employees` (`employee_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
