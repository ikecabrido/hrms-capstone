-- ============================================================================
-- Employee-scoped notification preferences
--
-- ld_notification_preference shipped with the same two defects as ld_notification:
--   * user_id FOREIGN KEY -> user_account(user_id), while Setting.php and the
--     preference endpoints address the employee id (em_employees.employee_id),
--     so inserts fail with errno 1452;
--   * id had no AUTO_INCREMENT and the INSERT omits it, so every saved
--     preference landed on id = 0 and the second distinct type hit a duplicate
--     primary key.
--
-- The legacy table is left untouched. ld_notification_preference_store carries
-- the same columns with an auto-increment id and a foreign key to the employee
-- record, and Setting.php reads and writes it from now on.
--
-- Safe to run more than once.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ld_notification_preference_store (
    id                INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT(11)          NOT NULL,
    notification_type VARCHAR(50)      NOT NULL,
    enabled           TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_notification_preference_type (user_id, notification_type),
    CONSTRAINT fk_ld_notification_preference_employee
        FOREIGN KEY (user_id) REFERENCES em_employees (employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Carry over anything the legacy table holds. Rows whose user_id is not an
-- employee (the old table pointed at user_account) cannot satisfy the new
-- foreign key and are skipped, as is any pair already present here.
INSERT INTO ld_notification_preference_store (id, user_id, notification_type, enabled)
SELECT p.id, p.user_id, p.notification_type, p.enabled
FROM ld_notification_preference p
JOIN em_employees e ON e.employee_id = p.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM ld_notification_preference_store t
    WHERE t.id = p.id
       OR (t.user_id = p.user_id AND t.notification_type = p.notification_type)
);

SELECT COUNT(*) AS notification_preference_rows FROM ld_notification_preference_store;
SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ld_notification_preference_store';
