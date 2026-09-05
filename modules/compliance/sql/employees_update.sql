START TRANSACTION;

-- =========================================================
-- ADD HR POSITIONS
-- =========================================================

INSERT INTO `em_positions`
(`position_id`, `position_name`, `slot_count`, `department_id`, `status`, `created_at`, `updated_at`)
VALUES
(NULL, 'Exit Management', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Payroll Management', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Legal and Compliance', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Performance Management', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Employee Engagement and Relations', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Recruitment and Onboarding', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Employee Portal', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Employee Management', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Workforce Analytics and Reporting', 1, 1, 'Active', NOW(), NULL),
(NULL, 'HR Clinic', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Time and Attendance', 1, 1, 'Active', NOW(), NULL),
(NULL, 'Learning and Development', 1, 1, 'Active', NOW(), NULL);


-- =========================================================
-- UPDATE EMPLOYEE POSITIONS
-- =========================================================
-- Assumes the new positions receive IDs 46 through 57.

UPDATE `em_employees`
SET `position_id` = CASE `employee_id`
    WHEN 4  THEN 52  -- Roberto Albert → Employee Portal
    WHEN 29 THEN 51  -- Jhon Carlo Garcia → Recruitment and Onboarding
    WHEN 30 THEN 47  -- Russel Cabrido → Payroll Management
    WHEN 31 THEN 56  -- Jose Mari Rich Malana → Time and Attendance
    WHEN 32 THEN 53  -- Russell Placer → Employee Management
    WHEN 33 THEN 48  -- Cheska Bautista → Legal and Compliance
    WHEN 34 THEN 54  -- Jayson Paigma → Workforce Analytics and Reporting
    WHEN 35 THEN 57  -- Rainiel Quebada → Learning and Development
    WHEN 36 THEN 49  -- Karl Solis → Performance Management
    WHEN 37 THEN 50  -- Geoffrey Balansag → Employee Engagement and Relations
    WHEN 38 THEN 46  -- Johnloyd Reyes → Exit Management
    WHEN 39 THEN 55  -- Alexis Cueto → HR Clinic
END
WHERE `employee_id` IN
(4, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39);

COMMIT;


-- =========================================================
-- VERIFY THE RESULT
-- =========================================================

SELECT
    e.employee_id,
    e.employee_code,
    CONCAT(
        e.first_name,
        ' ',
        COALESCE(CONCAT(e.middle_name, ' '), ''),
        e.last_name
    ) AS employee_name,
    e.position_id,
    p.position_name
FROM `em_employees` e
LEFT JOIN `em_positions` p
    ON e.position_id = p.position_id
WHERE e.employee_id IN
(4, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39)
ORDER BY e.employee_id;