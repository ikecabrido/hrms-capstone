SET @enrollment_has_zero = (SELECT COUNT(*) FROM ld_enrollment WHERE id = 0);
SET @enrollment_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_enrollment);
SET @progress_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_progress);
SET @session_has_zero = (SELECT COUNT(*) FROM ld_quiz_session WHERE id = 0);
SET @session_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_quiz_session);
SET @session_answer_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_quiz_session_answer);
SET @quiz_attempt_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_quiz_attempt);
SET @certificate_next_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM ld_certificate);

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

UPDATE ld_progress
SET enrollment_id = @enrollment_next_id
WHERE enrollment_id = 0 AND @enrollment_has_zero > 0;

UPDATE ld_certificate
SET completed_enrollment_id = @enrollment_next_id
WHERE completed_enrollment_id = 0 AND @enrollment_has_zero > 0;

UPDATE ld_enrollment
SET id = @enrollment_next_id
WHERE id = 0;

UPDATE ld_quiz_session_answer
SET quiz_session_id = @session_next_id
WHERE quiz_session_id = 0 AND @session_has_zero > 0;

UPDATE ld_quiz_attempt
SET quiz_session_id = @session_next_id
WHERE quiz_session_id = 0 AND @session_has_zero > 0;

UPDATE ld_quiz_session
SET id = @session_next_id
WHERE id = 0;

UPDATE ld_progress SET id = @progress_next_id WHERE id = 0;
UPDATE ld_quiz_session_answer SET id = @session_answer_next_id WHERE id = 0;
UPDATE ld_quiz_attempt SET id = @quiz_attempt_next_id WHERE id = 0;
UPDATE ld_certificate SET id = @certificate_next_id WHERE id = 0;
UPDATE ld_quiz_session SET duration_seconds = NULL WHERE duration_seconds = 0;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE ld_enrollment MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE ld_progress MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE ld_quiz_session MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE ld_quiz_session_answer MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE ld_quiz_attempt MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE ld_certificate MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;