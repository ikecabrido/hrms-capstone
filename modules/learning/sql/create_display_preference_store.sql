-- ============================================================================
-- Employee-scoped display preferences
--
-- ld_display_preference shipped with the same two defects as the notification
-- tables before it:
--   * user_id FOREIGN KEY -> user_account(user_id), while every caller addresses
--     the employee id (em_employees.employee_id). The two id ranges do not
--     overlap in this database, so every save died on errno 1452;
--   * id had no AUTO_INCREMENT and the INSERT omits it, so a saved row could
--     only ever land on id = 0 and the second save hit a duplicate primary key.
--
-- Neither is fixable without altering a legacy table, so the legacy one is left
-- untouched. ld_display_preference_store carries the same meaning (one row per
-- employee, page_size + view_mode) with an auto-increment id and a foreign key
-- to the employee record. Theme is deliberately not carried over: light/dark is
-- still the browser's own setting (localStorage), and one setting with two
-- stores is how a preference stops being honest.
--
-- Safe to run more than once.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ld_display_preference_store (
    id         INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id    INT(11)           NOT NULL,
    page_size  TINYINT(3) UNSIGNED NOT NULL DEFAULT 12,
    view_mode  ENUM('grid','list') NOT NULL DEFAULT 'grid',
    PRIMARY KEY (id),
    UNIQUE KEY uniq_display_preference_user (user_id),
    CONSTRAINT fk_ld_display_preference_employee
        FOREIGN KEY (user_id) REFERENCES em_employees (employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Carry over anything the legacy table holds. The legacy user_id pointed at
-- user_account, so rows that are not employee ids cannot satisfy the new foreign
-- key and are skipped, as is any employee already present here.
INSERT INTO ld_display_preference_store (id, user_id, page_size, view_mode)
SELECT p.id, p.user_id, p.page_size, p.view_mode
FROM ld_display_preference p
JOIN em_employees e ON e.employee_id = p.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM ld_display_preference_store t
    WHERE t.id = p.id
       OR t.user_id = p.user_id
);

SELECT COUNT(*) AS display_preference_rows FROM ld_display_preference_store;
SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ld_display_preference_store';
