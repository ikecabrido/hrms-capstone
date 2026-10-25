-- Back up the database first. Run this migration manually in phpMyAdmin.
-- AUTO_INCREMENT requires request_id to already have an index/key.
-- The existing request_id data type is preserved from INFORMATION_SCHEMA.

SET @request_id_type = (
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'em_lc_document_requests'
      AND COLUMN_NAME = 'request_id'
);

SET @request_id_ddl = CONCAT(
    'ALTER TABLE em_lc_document_requests MODIFY COLUMN request_id ',
    @request_id_type,
    ' NOT NULL AUTO_INCREMENT'
);

PREPARE request_id_stmt FROM @request_id_ddl;
EXECUTE request_id_stmt;
DEALLOCATE PREPARE request_id_stmt;

ALTER TABLE em_lc_document_requests
    MODIFY COLUMN notes TEXT NULL,
    ALTER COLUMN request_status SET DEFAULT 'Pending';

UPDATE em_lc_document_requests
SET request_status = 'Pending'
WHERE request_status = 'draft';
