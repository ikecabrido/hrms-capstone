-- Optional reference from EER grievances to the existing Legal & Compliance records.
-- Run this once against the existing HRMS database after confirming lc_compliance_records exists.

ALTER TABLE eer_grievances
  ADD COLUMN compliance_record_id INT NULL,
  ADD INDEX idx_eer_grievances_compliance_record_id (compliance_record_id),
  ADD CONSTRAINT fk_eer_grievances_compliance_record
    FOREIGN KEY (compliance_record_id)
    REFERENCES lc_compliance_records (record_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
