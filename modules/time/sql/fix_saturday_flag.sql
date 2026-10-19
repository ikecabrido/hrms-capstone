UPDATE ta_shifts t
SET t.include_saturday = 1
WHERE EXISTS (
    SELECT 1
    FROM ta_shift_weekday_times w
    WHERE w.shift_id = t.shift_id
      AND w.weekday = 6
      AND w.is_active = 1
);
