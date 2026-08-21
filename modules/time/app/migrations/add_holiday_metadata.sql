ALTER TABLE `ta_holidays`
  ADD COLUMN `holiday_scope` VARCHAR(30) NOT NULL DEFAULT 'national' AFTER `category`,
  ADD COLUMN `is_working_day` TINYINT(1) NOT NULL DEFAULT 0 AFTER `holiday_scope`,
  ADD COLUMN `source` VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER `is_working_day`,
  ADD INDEX `idx_holiday_scope` (`holiday_scope`),
  ADD INDEX `idx_is_working_day` (`is_working_day`),
  ADD INDEX `idx_holiday_source` (`source`),
  ADD INDEX `idx_holiday_scope_date` (`holiday_scope`, `holiday_date`);

UPDATE `ta_holidays`
SET `holiday_scope` = COALESCE(NULLIF(TRIM(`holiday_scope`), ''), `category`, 'national'),
    `is_working_day` = CASE WHEN `is_working_day` IS NULL THEN 0 ELSE `is_working_day` END,
    `source` = CASE WHEN `source` IS NULL OR TRIM(`source`) = '' THEN 'manual' ELSE `source` END
WHERE `holiday_scope` IS NULL
   OR TRIM(`holiday_scope`) = ''
   OR `is_working_day` IS NULL
   OR `source` IS NULL
   OR TRIM(`source`) = '';
