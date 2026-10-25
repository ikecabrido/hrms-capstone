UPDATE eer_badges
SET points_value = CASE name
    WHEN 'Employee of the Month' THEN 50
    WHEN 'Top 2 Monthly Recognition' THEN 40
    WHEN 'Top 3 Monthly Recognition' THEN 30
    WHEN 'Top 4 Monthly Recognition' THEN 20
    WHEN 'Top 5 Monthly Recognition' THEN 10
    ELSE points_value
END
WHERE category = 'monthly_recognition';

UPDATE eer_award_history ah
INNER JOIN eer_recognition_cycle_awards ca
    ON ca.award_history_id = ah.eer_award_history_id
INNER JOIN eer_badges b
    ON b.eer_badge_id = ca.badge_id
SET ah.points = CASE b.name
    WHEN 'Employee of the Month' THEN 50
    WHEN 'Top 2 Monthly Recognition' THEN 40
    WHEN 'Top 3 Monthly Recognition' THEN 30
    WHEN 'Top 4 Monthly Recognition' THEN 20
    WHEN 'Top 5 Monthly Recognition' THEN 10
    ELSE ah.points
END
WHERE ah.award_type = 'employee_of_month'
  AND b.category = 'monthly_recognition';
