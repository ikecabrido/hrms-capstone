-- ============================================================================
-- Learning module — ONE-TIME repair of legacy assessment answers
--
-- Why
--   Some questions that shipped with the demo data had no option flagged as
--   correct, so those quizzes/evaluations could never be passed. Other answers
--   used ad-hoc labels ("True", "Ha", ...) instead of the "tama"/"mali"
--   convention. This script fixes the malformed questions and relabels every
--   active answer so the correct choice is always the "tama" option.
--
-- WARNING — this reloads option text, so only run it once, before authoring
--   real questions. It matches on is_correct, not on the current text, so a
--   second run is a no-op for well-formed data, but it will relabel any new
--   options you add afterwards back to "tama"/"mali".
--
-- Run
--   mysql -u root payr_bcp < modules/learning/sql/fix_legacy_assessment_answers.sql
-- ============================================================================

START TRANSACTION;

-- 1. Questions with no correct option — mark their first option correct.
DROP TEMPORARY TABLE IF EXISTS tmp_q_no_correct;
CREATE TEMPORARY TABLE tmp_q_no_correct AS
SELECT q.id AS question_id, MIN(o.id) AS first_option_id
FROM ld_quiz_question q
JOIN ld_quiz_question_option o ON o.question_id = q.id
WHERE q.status = 'active'
GROUP BY q.id
HAVING SUM(o.is_correct) = 0;

UPDATE ld_quiz_question_option o
JOIN tmp_q_no_correct b ON b.first_option_id = o.id
SET o.is_correct = 1;

DROP TEMPORARY TABLE IF EXISTS tmp_q_no_correct;

-- 2. Questions with more than one correct option — keep only the first.
DROP TEMPORARY TABLE IF EXISTS tmp_q_multi_correct;
CREATE TEMPORARY TABLE tmp_q_multi_correct AS
SELECT q.id AS question_id, MIN(o.id) AS first_correct_id
FROM ld_quiz_question q
JOIN ld_quiz_question_option o ON o.question_id = q.id
WHERE q.status = 'active'
GROUP BY q.id
HAVING SUM(o.is_correct) > 1;

UPDATE ld_quiz_question_option o
JOIN tmp_q_multi_correct m ON m.question_id = o.question_id
SET o.is_correct = (o.id = m.first_correct_id);

DROP TEMPORARY TABLE IF EXISTS tmp_q_multi_correct;

-- 3. The correct option is always the "tama" option.
UPDATE ld_quiz_question_option o
JOIN ld_quiz_question q ON q.id = o.question_id
SET o.option_text = IF(o.is_correct = 1, 'tama', 'mali')
WHERE q.status = 'active';

COMMIT;
