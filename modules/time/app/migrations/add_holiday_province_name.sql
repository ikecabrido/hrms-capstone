ALTER TABLE `ta_holidays`
  ADD COLUMN IF NOT EXISTS `province_name` VARCHAR(100) NULL AFTER `holiday_scope`;
