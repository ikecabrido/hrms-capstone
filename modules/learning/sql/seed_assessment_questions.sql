-- ============================================================================
-- Learning module — sample questions for quizzes & evaluations
--
-- Purpose
--   Gives every active quiz / evaluation that has no questions yet a set of
--   true/false sample questions so learners can actually start the assessment.
--   Each question gets two options: "tama" (the correct one) and "mali".
--
--   Quizzes get 5 questions, evaluations get `question_count` questions
--   (falling back to 10 when unset, capped at 12 by this script).
--
-- Idempotent & additive
--   Questions are only added to items that currently have zero active
--   questions, and options only to questions that have none. Re-running the
--   script never modifies or removes existing questions/options, so it is safe
--   to run again after real content has been authored.
--
--   Repairing the legacy questions that shipped without a correct answer, and
--   relabelling answers to use "tama"/"mali", lives in the separate one-time
--   script `fix_legacy_assessment_answers.sql`.
--
-- Run
--   mysql -u root payr_bcp < modules/learning/sql/seed_assessment_questions.sql
-- ============================================================================

START TRANSACTION;

-- 1. Questions ---------------------------------------------------------------
INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status)
SELECT s.item_type,
       s.reference_id,
       CONCAT('Sample question ', n.n, ' for ', s.title),
       'true_false',
       n.n,
       'active'
FROM (
    SELECT 'quiz' AS item_type, q.id AS reference_id, q.title AS title, 5 AS qcount
    FROM ld_quiz q
    WHERE q.status = 'active'
    UNION ALL
    SELECT 'evaluation',
           e.id,
           e.title,
           LEAST(COALESCE(NULLIF(e.question_count, 0), 10), 12)
    FROM ld_evaluation e
    WHERE e.status = 'active'
) s
JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) n ON n.n <= s.qcount
WHERE NOT EXISTS (
    SELECT 1
    FROM ld_quiz_question qq
    WHERE qq.item_type = s.item_type
      AND qq.reference_id = s.reference_id
      AND qq.status = 'active'
);

-- 2. Options — the "tama" answer is always the correct one -------------------
INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index)
SELECT qq.id, v.option_text, v.is_correct, v.order_index
FROM ld_quiz_question qq
CROSS JOIN (
    SELECT 'tama' AS option_text, 1 AS is_correct, 1 AS order_index
    UNION ALL
    SELECT 'mali', 0, 2
) v
WHERE qq.status = 'active'
  AND NOT EXISTS (
      SELECT 1 FROM ld_quiz_question_option o WHERE o.question_id = qq.id
  );

COMMIT;
