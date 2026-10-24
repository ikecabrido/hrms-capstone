-- Knowledge Transfer / L&D workflow schema for Exit module
-- This is intentionally read-only against ld_* tables; Exit keeps workflow state in its own tables.

CREATE TABLE IF NOT EXISTS exit_kt_ld_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    plan_id INT(11) NOT NULL,
    requested_by INT(11) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'requested',
    progress_percent INT(11) NOT NULL DEFAULT 0,
    requested_at DATETIME DEFAULT NULL,
    last_synced_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    external_path_id INT(11) DEFAULT NULL,
    external_path_title VARCHAR(255) DEFAULT NULL,
    external_path_ref VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_exit_kt_ld_requests_plan (plan_id),
    KEY idx_exit_kt_ld_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exit_kt_ld_status_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    plan_id INT(11) NOT NULL,
    request_id INT(11) DEFAULT NULL,
    old_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) DEFAULT NULL,
    changed_by INT(11) DEFAULT NULL,
    reason TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_exit_kt_ld_status_log_plan (plan_id),
    KEY idx_exit_kt_ld_status_log_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exit-owned workflow state on exit_knowledge_transfer_plans
-- Note: the model adds these columns idempotently at runtime if they are missing.
-- Columns expected by the workflow: ld_status, ld_requested_at, ld_requested_by,
-- ld_last_synced_at, ld_progress_percent, ld_approved_by, ld_approved_at,
-- ld_last_sync_notes, ld_path_id, ld_path_title, ld_path_external_ref.
