-- ============================================================================
-- ld_announcement: give the primary key an AUTO_INCREMENT
--
-- The table shipped with `id int(10) unsigned NOT NULL` and no AUTO_INCREMENT,
-- while every writer omits the column:
--
--   classes/announcement.php          INSERT INTO ld_announcement (title, ...)
--   pages/shared/ajax/add-announcement.php  same, without id
--
-- MySQL therefore used the implicit default 0 for the first row (the stale
-- "Test" announcement) and rejected every later one with
-- "Duplicate entry '0' for key 'PRIMARY'", so publishing an announcement has
-- never worked. Existing row values are left untouched: the row on id 0 stays
-- exactly as it is, and new announcements start at 1.
--
-- The same missing-AUTO_INCREMENT pattern exists on many other ld_* tables
-- (ld_audit_log, ld_notification, ld_grade, ld_skill, ...); that is deliberately
-- not touched here.
--
-- Safe to run more than once.
-- ============================================================================

SET @ld_announcement_needs_auto_increment := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ld_announcement'
      AND COLUMN_NAME = 'id'
      AND EXTRA NOT LIKE '%auto_increment%'
);

SET @ld_announcement_sql := IF(
    @ld_announcement_needs_auto_increment > 0,
    'ALTER TABLE ld_announcement MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
    'SELECT ''ld_announcement.id is already AUTO_INCREMENT'' AS note'
);

PREPARE ld_announcement_stmt FROM @ld_announcement_sql;
EXECUTE ld_announcement_stmt;
DEALLOCATE PREPARE ld_announcement_stmt;

SELECT COLUMN_NAME, COLUMN_TYPE, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ld_announcement' AND COLUMN_NAME = 'id';
