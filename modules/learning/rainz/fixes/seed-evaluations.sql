-- Seed final evaluations for courses that have none.
-- Idempotent: courses that already have an active evaluation are skipped.
-- Usage: mysql -u root hrms < seed-evaluations.sql

INSERT INTO ld_evaluation (course_id, title, passing_score, max_attempts, question_count, status)
SELECT c.id,
       CONCAT('Final Evaluation: ', c.title),
       70.00,
       2,
       3,
       'active'
FROM ld_course c
WHERE c.status IN ('active', 'draft')
  AND NOT EXISTS (
      SELECT 1 FROM ld_evaluation e
      WHERE e.course_id = c.id AND e.status = 'active'
  );

-- Three standard questions per newly created evaluation.
INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status)
SELECT 'evaluation', e.id, q.question_text, q.question_type, q.ord, 'active'
FROM ld_evaluation e
JOIN (
    SELECT 'Which of the following best describes the main goal of this course?' AS question_text, 'single_choice' AS question_type, 1 AS ord
    UNION ALL SELECT 'Applying what you learned, which scenario fits the course material?', 'multiple_choice', 2
    UNION ALL SELECT 'This course covered practical skills that can be applied on the job.', 'true_false', 3
) q
WHERE e.status = 'active'
  AND e.title LIKE 'Final Evaluation: %'
  AND NOT EXISTS (SELECT 1 FROM ld_quiz_question qq WHERE qq.item_type = 'evaluation' AND qq.reference_id = e.id);

-- Options per question: first option correct for Q1 and Q3, second for Q2.
INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index)
SELECT qq.id, o.option_text, o.is_correct, o.ord
FROM ld_quiz_question qq
JOIN (
    SELECT 1 AS qnum, 'It provides the foundational knowledge and skills covered in the course.' AS option_text, 1 AS is_correct, 1 AS ord
    UNION ALL SELECT 1, 'None of the above.', 0, 2
    UNION ALL SELECT 1, 'It is unrelated to workplace practice.', 0, 3
    UNION ALL SELECT 2, 'A real-world task that uses the techniques taught.', 1, 1
    UNION ALL SELECT 2, 'A task that requires none of the course concepts.', 0, 2
    UNION ALL SELECT 2, 'A task outside the scope of the course.', 0, 3
    UNION ALL SELECT 3, 'True', 1, 1
    UNION ALL SELECT 3, 'False', 0, 2
) o ON o.qnum = qq.order_index
WHERE qq.item_type = 'evaluation'
  AND qq.status = 'active'
  AND qq.question_text IN (
      'Which of the following best describes the main goal of this course?',
      'Applying what you learned, which scenario fits the course material?',
      'This course covered practical skills that can be applied on the job.'
  )
  AND NOT EXISTS (SELECT 1 FROM ld_quiz_question_option qo WHERE qo.question_id = qq.id);

-- Keep question_count accurate.
UPDATE ld_evaluation e
SET e.question_count = (
    SELECT COUNT(*) FROM ld_quiz_question qq
    WHERE qq.item_type = 'evaluation' AND qq.reference_id = e.id AND qq.status = 'active'
)
WHERE e.status = 'active'
  AND e.title LIKE 'Final Evaluation: %';
