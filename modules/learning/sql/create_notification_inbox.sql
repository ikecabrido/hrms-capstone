-- ============================================================================
-- Employee-scoped notification store
--
-- ld_notification shipped with two defects that make it unusable from the
-- Learning module:
--   * user_id FOREIGN KEY -> user_account(user_id), while every reader and
--     writer in the module addresses employees (em_employees.employee_id), so
--     inserts fail with errno 1452;
--   * id had no AUTO_INCREMENT, and every INSERT omits it, so rows landed on
--     id = 0 and the second insert hit a duplicate primary key.
--
-- The legacy table is left untouched. This new table carries the same columns
-- with an auto-increment id and a foreign key to the employee record, and the
-- module reads and writes ld_notification_inbox from now on.
--
-- Safe to run more than once.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ld_notification_inbox (
    id             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT(11)          NOT NULL,
    type           VARCHAR(50)      NOT NULL,
    title          VARCHAR(255)     NOT NULL,
    message        TEXT             NOT NULL,
    reference_type VARCHAR(50)          DEFAULT NULL,
    reference_id   INT(10) UNSIGNED     DEFAULT NULL,
    is_read        TINYINT(1)       NOT NULL DEFAULT 0,
    created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notification_inbox_user (user_id, is_read),
    CONSTRAINT fk_ld_notification_inbox_employee
        FOREIGN KEY (user_id) REFERENCES em_employees (employee_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Carry over anything the legacy table holds. Rows whose user_id is not an
-- employee (the old table pointed at user_account) cannot satisfy the new
-- foreign key and are intentionally skipped.
INSERT INTO ld_notification_inbox
    (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
SELECT n.id, n.user_id, n.type, n.title, n.message, n.reference_type, n.reference_id, n.is_read, n.created_at
FROM ld_notification n
JOIN em_employees e ON e.employee_id = n.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM ld_notification_inbox i WHERE i.id = n.id
);

SELECT COUNT(*) AS notification_inbox_rows FROM ld_notification_inbox;
SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ld_notification_inbox';
