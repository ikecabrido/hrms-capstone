-- Keep badge attribution compatible with the ON DELETE SET NULL foreign key.
ALTER TABLE `eer_employee_badges`
  MODIFY COLUMN `awarded_by` int(11) DEFAULT NULL;
