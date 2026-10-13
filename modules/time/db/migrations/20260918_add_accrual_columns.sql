ALTER TABLE ta_leave_types
  ADD COLUMN accrual_frequency ENUM('yearly','monthly') NOT NULL DEFAULT 'yearly',
  ADD COLUMN monthly_accrual_days DECIMAL(4,1) DEFAULT NULL;

ALTER TABLE ta_leave_balances
  ADD COLUMN last_monthly_accrual_month VARCHAR(7) DEFAULT NULL; -- format YYYY-MM

-- Set Vacation and Sick to monthly accrual of 3 days each
UPDATE ta_leave_types
SET accrual_frequency = 'monthly',
    monthly_accrual_days = 3
WHERE leave_type_name IN ('Vacation Leave','Sick Leave');

ALTER TABLE `ta_leave_balances`
  ADD COLUMN `last_yearly_allocation_year` VARCHAR(4) DEFAULT NULL;