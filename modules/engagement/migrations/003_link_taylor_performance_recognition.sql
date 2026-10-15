-- Taylor Garcia's performance report has no employee_id. The HR user confirmed
-- that Taylor's recommendation should route to the existing Alexis Cueto record.
UPDATE pm_performance_reports AS pr
JOIN em_employees AS e
  ON LOWER(TRIM(CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name))) = 'alexis cueto'
SET pr.employee_id = e.employee_id
WHERE LOWER(TRIM(pr.employee_name)) = 'taylor garcia'
  AND pr.employee_id IS NULL;
