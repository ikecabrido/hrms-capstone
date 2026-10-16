-- =====================================================================
-- Sample e-learning and training data (dummy/demo content)
-- ---------------------------------------------------------------------
-- Populates the Learning module catalog with realistic sample records:
--   * courses + modules + lessons + quizzes + evaluations   (E-Learning)
--   * learning paths (+ ordered items)                      (Training)
--   * programs                                              (Training)
--   * online training / video conferences                   (Training)
--   * course templates                                      (E-Learning)
--   * skill links (course / module / learning path / program)
--   * course-instructor assignment
--
-- Idempotent: every insert is guarded with NOT EXISTS, so re-running this
-- file does not create duplicate rows. Safe on an existing database.
--
-- Usage (from the repository root):
--   C:\xampp\mysql\bin\mysql.exe -u root payr_bcp < modules/learning/sql/seed_elearning_training.sql
--
-- Seeded content is attributed to instructor employee id 1001.
-- =====================================================================

SET @instructor_id := 1001;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. Courses
-- ---------------------------------------------------------------------
INSERT INTO ld_course (instructor_id, title, description, category, status, delivery_mode)
SELECT @instructor_id, s.title, s.description, s.category, 'active', s.delivery_mode
FROM (
    SELECT 'Workplace Safety and Compliance Essentials' AS title,
           'Learn the core safety practices, employee responsibilities, and compliance rules that keep the workplace healthy and incident-free.' AS description,
           'Compliance & Safety' AS category,
           'online' AS delivery_mode
    UNION ALL SELECT 'Effective Business Communication',
           'Build clear written, verbal, and interpersonal communication skills for everyday professional interactions.',
           'Communication', 'online'
    UNION ALL SELECT 'Data Analysis with Microsoft Excel',
           'Turn raw spreadsheets into clean data, useful charts, and decision-ready reports using Microsoft Excel.',
           'Data & Analytics', 'online'
    UNION ALL SELECT 'Leadership Foundations for New Managers',
           'A practical introduction to leading teams: setting expectations, coaching people, and holding performance conversations.',
           'Leadership', 'hybrid'
    UNION ALL SELECT 'Customer Service Excellence',
           'Deliver consistent, empathetic customer experiences and handle difficult situations with confidence.',
           'Customer Experience', 'online'
    UNION ALL SELECT 'Cybersecurity Awareness for Employees',
           'Recognize common cyber threats, protect company data, and follow safe browsing and password practices.',
           'Security', 'online'
) AS s
WHERE NOT EXISTS (SELECT 1 FROM ld_course c WHERE c.title = s.title);

-- ---------------------------------------------------------------------
-- 2. Modules (two per course)
-- ---------------------------------------------------------------------
INSERT INTO ld_module (course_id, title, description, order_index, status)
SELECT c.id, s.title, s.description, s.order_index, 'active'
FROM (
    SELECT 'Workplace Safety and Compliance Essentials' AS course_title, 'Safety Fundamentals and Responsibilities' AS title, 'Why safety matters and what every employee is responsible for.' AS description, 1 AS order_index
    UNION ALL SELECT 'Workplace Safety and Compliance Essentials', 'Hazard Identification and Risk Control', 'Spot hazards early and apply the hierarchy of controls to reduce risk.', 2
    UNION ALL SELECT 'Effective Business Communication', 'Communication Essentials', 'The communication process, listening, and building rapport at work.', 1
    UNION ALL SELECT 'Effective Business Communication', 'Professional Writing and Presentations', 'Write clear emails and reports and deliver confident presentations.', 2
    UNION ALL SELECT 'Data Analysis with Microsoft Excel', 'Excel Foundations and Data Cleaning', 'Navigate Excel and prepare messy data for analysis.', 1
    UNION ALL SELECT 'Data Analysis with Microsoft Excel', 'Data Visualization and Reporting', 'Build charts, pivot tables, and reports that communicate insight.', 2
    UNION ALL SELECT 'Leadership Foundations for New Managers', 'Transitioning into Leadership', 'Move from individual contributor to trusted team leader.', 1
    UNION ALL SELECT 'Leadership Foundations for New Managers', 'Coaching and Performance Conversations', 'Give feedback and run effective one-on-one meetings.', 2
    UNION ALL SELECT 'Customer Service Excellence', 'Customer Service Mindset', 'Understand customer expectations and build empathy and rapport.', 1
    UNION ALL SELECT 'Customer Service Excellence', 'Handling Difficult Customer Situations', 'De-escalate tension and resolve complaints effectively.', 2
    UNION ALL SELECT 'Cybersecurity Awareness for Employees', 'Cybersecurity Essentials', 'Understand common threats and how to protect company data.', 1
    UNION ALL SELECT 'Cybersecurity Awareness for Employees', 'Phishing, Passwords, and Safe Browsing', 'Recognize phishing and apply safe password and browsing habits.', 2
) AS s
JOIN ld_course c ON c.title = s.course_title
WHERE NOT EXISTS (SELECT 1 FROM ld_module m WHERE m.course_id = c.id AND m.title = s.title);

-- ---------------------------------------------------------------------
-- 3. Lessons (two per module)
-- ---------------------------------------------------------------------
INSERT INTO ld_lesson (module_id, title, content_type, content_body, video_url, order_index, status)
SELECT m.id, s.title, s.content_type, s.content_body, s.video_url, s.order_index, 'active'
FROM (
    SELECT 'Safety Fundamentals and Responsibilities' AS module_title, 'Why Workplace Safety Matters' AS title, 'text' AS content_type,
           '<h3>Introduction</h3><p>A safe workplace protects people, productivity, and reputation. This lesson explains the business and human reasons behind workplace safety.</p>' AS content_body,
           NULL AS video_url, 1 AS order_index
    UNION ALL SELECT 'Safety Fundamentals and Responsibilities', 'Roles, Rights, and Responsibilities', 'text',
           '<h3>Everyone Has a Role</h3><p>Understand employee rights, employer duties, and how to report concerns through the correct channels.</p>', NULL, 2
    UNION ALL SELECT 'Hazard Identification and Risk Control', 'Spotting Common Workplace Hazards', 'text',
           '<h3>Hazard Awareness</h3><p>Learn how to identify physical, ergonomic, chemical, and psychosocial hazards before they cause harm.</p>', NULL, 1
    UNION ALL SELECT 'Hazard Identification and Risk Control', 'The Hierarchy of Risk Controls', 'text',
           '<h3>Controlling Risk</h3><p>Elimination, substitution, engineering controls, administrative controls, and PPE - applied in the right order.</p>', NULL, 2
    UNION ALL SELECT 'Communication Essentials', 'The Communication Process', 'text',
           '<h3>Sender, Message, Receiver</h3><p>Explore the communication loop and the barriers that cause misunderstandings at work.</p>', NULL, 1
    UNION ALL SELECT 'Communication Essentials', 'Active Listening Techniques', 'video',
           NULL, 'https://www.youtube.com/embed/1Y9d3lR6Qm4', 2
    UNION ALL SELECT 'Professional Writing and Presentations', 'Writing Clear Emails and Reports', 'text',
           '<h3>Write to Be Understood</h3><p>Structure business messages with a clear purpose, audience, and call to action.</p>', NULL, 1
    UNION ALL SELECT 'Professional Writing and Presentations', 'Delivering Confident Presentations', 'video',
           NULL, 'https://www.youtube.com/embed/AykYRO5d_lI', 2
    UNION ALL SELECT 'Excel Foundations and Data Cleaning', 'Navigating Excel and Key Functions', 'text',
           '<h3>Excel Basics</h3><p>Work with cells, ranges, formulas, and lookup functions that form the backbone of analysis.</p>', NULL, 1
    UNION ALL SELECT 'Excel Foundations and Data Cleaning', 'Cleaning and Organizing Data', 'text',
           '<h3>From Messy to Tidy</h3><p>Remove duplicates, standardize values, and structure data for reliable reporting.</p>', NULL, 2
    UNION ALL SELECT 'Data Visualization and Reporting', 'Building Charts and Pivot Tables', 'video',
           NULL, 'https://www.youtube.com/embed/xbLcfBGU5Qc', 1
    UNION ALL SELECT 'Data Visualization and Reporting', 'Presenting Insights from Data', 'text',
           '<h3>Telling the Story</h3><p>Choose the right chart and highlight the insight that drives a decision.</p>', NULL, 2
    UNION ALL SELECT 'Transitioning into Leadership', 'From Team Member to Team Lead', 'text',
           '<h3>A New Perspective</h3><p>Shift from doing the work to enabling the team to do the work.</p>', NULL, 1
    UNION ALL SELECT 'Transitioning into Leadership', 'Setting Expectations and Goals', 'text',
           '<h3>Clarity Drives Performance</h3><p>Define outcomes, agree on measures, and align the team around shared goals.</p>', NULL, 2
    UNION ALL SELECT 'Coaching and Performance Conversations', 'Giving Constructive Feedback', 'video',
           NULL, 'https://www.youtube.com/embed/6z4g7w2KqEo', 1
    UNION ALL SELECT 'Coaching and Performance Conversations', 'Running One-on-One Meetings', 'text',
           '<h3>Make Time Count</h3><p>A repeatable agenda for check-ins that develop people and surface issues early.</p>', NULL, 2
    UNION ALL SELECT 'Customer Service Mindset', 'Understanding Customer Expectations', 'text',
           '<h3>What Customers Want</h3><p>Responsiveness, reliability, empathy, and a resolution - every time.</p>', NULL, 1
    UNION ALL SELECT 'Customer Service Mindset', 'Building Rapport and Empathy', 'text',
           '<h3>Connect First</h3><p>Simple techniques to make customers feel heard and valued.</p>', NULL, 2
    UNION ALL SELECT 'Handling Difficult Customer Situations', 'De-escalation Techniques', 'video',
           NULL, 'https://www.youtube.com/embed/1EvUCfXWLoU', 1
    UNION ALL SELECT 'Handling Difficult Customer Situations', 'Resolving Complaints Effectively', 'text',
           '<h3>From Complaint to Resolution</h3><p>A structured approach to acknowledge, investigate, and resolve complaints.</p>', NULL, 2
    UNION ALL SELECT 'Cybersecurity Essentials', 'Common Cyber Threats Explained', 'text',
           '<h3>Know the Threats</h3><p>Malware, ransomware, social engineering, and insider risk explained in plain language.</p>', NULL, 1
    UNION ALL SELECT 'Cybersecurity Essentials', 'Protecting Company Data', 'text',
           '<h3>Data Handling</h3><p>Classify, store, share, and dispose of information safely.</p>', NULL, 2
    UNION ALL SELECT 'Phishing, Passwords, and Safe Browsing', 'Recognizing Phishing Attempts', 'video',
           NULL, 'https://www.youtube.com/embed/XBkzBrXlle0', 1
    UNION ALL SELECT 'Phishing, Passwords, and Safe Browsing', 'Strong Passwords and Multi-Factor Authentication', 'text',
           '<h3>Lock the Door</h3><p>Create strong passphrases and enable MFA on every account.</p>', NULL, 2
) AS s
JOIN ld_module m ON m.title = s.module_title
WHERE NOT EXISTS (SELECT 1 FROM ld_lesson l WHERE l.module_id = m.id AND l.title = s.title);

-- ---------------------------------------------------------------------
-- 4. Quizzes (one per module)
-- ---------------------------------------------------------------------
INSERT INTO ld_quiz (module_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status)
SELECT m.id, s.title, 600, s.passing_score, 2, s.question_count, 1, 'active'
FROM (
    SELECT 'Safety Fundamentals and Responsibilities' AS module_title, 'Quiz: Safety Fundamentals and Responsibilities' AS title, 80.00 AS passing_score, 5 AS question_count
    UNION ALL SELECT 'Hazard Identification and Risk Control', 'Quiz: Hazard Identification and Risk Control', 80.00, 5
    UNION ALL SELECT 'Communication Essentials', 'Quiz: Communication Essentials', 75.00, 5
    UNION ALL SELECT 'Professional Writing and Presentations', 'Quiz: Professional Writing and Presentations', 75.00, 5
    UNION ALL SELECT 'Excel Foundations and Data Cleaning', 'Quiz: Excel Foundations and Data Cleaning', 80.00, 6
    UNION ALL SELECT 'Data Visualization and Reporting', 'Quiz: Data Visualization and Reporting', 80.00, 6
    UNION ALL SELECT 'Transitioning into Leadership', 'Quiz: Transitioning into Leadership', 75.00, 5
    UNION ALL SELECT 'Coaching and Performance Conversations', 'Quiz: Coaching and Performance Conversations', 75.00, 5
    UNION ALL SELECT 'Customer Service Mindset', 'Quiz: Customer Service Mindset', 80.00, 5
    UNION ALL SELECT 'Handling Difficult Customer Situations', 'Quiz: Handling Difficult Customer Situations', 80.00, 5
    UNION ALL SELECT 'Cybersecurity Essentials', 'Quiz: Cybersecurity Essentials', 85.00, 6
    UNION ALL SELECT 'Phishing, Passwords, and Safe Browsing', 'Quiz: Phishing, Passwords, and Safe Browsing', 85.00, 6
) AS s
JOIN ld_module m ON m.title = s.module_title
WHERE NOT EXISTS (SELECT 1 FROM ld_quiz q WHERE q.module_id = m.id AND q.title = s.title);

-- ---------------------------------------------------------------------
-- 5. Evaluations (one per course)
-- ---------------------------------------------------------------------
INSERT INTO ld_evaluation (course_id, title, duration_seconds, passing_score, max_attempts, question_count, show_answers_after_submit, status)
SELECT c.id, s.title, 1800, s.passing_score, 2, s.question_count, 1, 'active'
FROM (
    SELECT 'Workplace Safety and Compliance Essentials' AS course_title, 'Final Assessment: Workplace Safety and Compliance Essentials' AS title, 85.00 AS passing_score, 10 AS question_count
    UNION ALL SELECT 'Effective Business Communication', 'Final Assessment: Effective Business Communication', 80.00, 10
    UNION ALL SELECT 'Data Analysis with Microsoft Excel', 'Final Assessment: Data Analysis with Microsoft Excel', 80.00, 12
    UNION ALL SELECT 'Leadership Foundations for New Managers', 'Final Assessment: Leadership Foundations for New Managers', 80.00, 10
    UNION ALL SELECT 'Customer Service Excellence', 'Final Assessment: Customer Service Excellence', 80.00, 10
    UNION ALL SELECT 'Cybersecurity Awareness for Employees', 'Final Assessment: Cybersecurity Awareness for Employees', 85.00, 12
) AS s
JOIN ld_course c ON c.title = s.course_title
WHERE NOT EXISTS (SELECT 1 FROM ld_evaluation e WHERE e.course_id = c.id AND e.title = s.title);

-- ---------------------------------------------------------------------
-- 6. Programs
-- ---------------------------------------------------------------------
INSERT INTO ld_program (instructor_id, title, description, status)
SELECT @instructor_id, s.title, s.description, 'active'
FROM (
    SELECT 'Management Development Program' AS title, 'A structured program that prepares high-potential employees for people-leadership roles through courses, workshops, and coaching.' AS description
    UNION ALL SELECT 'Graduate Trainee Program', 'An onboarding and development program that rotates new graduates through core business functions.'
    UNION ALL SELECT 'Customer Experience Excellence Program', 'A blended program that builds consistent customer service standards across customer-facing teams.'
) AS s
WHERE NOT EXISTS (SELECT 1 FROM ld_program p WHERE p.title = s.title);

-- ---------------------------------------------------------------------
-- 7. Online training (video conferences / live sessions)
-- ---------------------------------------------------------------------
INSERT INTO ld_video_conference (instructor_id, course_id, program_id, title, platform, meeting_link, scheduled_at, duration_minutes, status)
SELECT @instructor_id, c.id, p.id, s.title, s.platform, s.meeting_link, s.scheduled_at, s.duration_minutes, s.status
FROM (
    SELECT 'Management Development Program' AS program_title, NULL AS course_title,
           'Leadership Essentials Live Workshop' AS title, 'zoom' AS platform,
           'https://zoom.us/j/91234567890' AS meeting_link, '2026-11-05 09:00:00' AS scheduled_at,
           120 AS duration_minutes, 'scheduled' AS status
    UNION ALL SELECT NULL, 'Data Analysis with Microsoft Excel',
           'Excel Data Analysis Live Lab', 'google_meet',
           'https://meet.google.com/abc-defg-hij', '2026-11-10 13:30:00', 90, 'scheduled'
    UNION ALL SELECT NULL, 'Cybersecurity Awareness for Employees',
           'Cybersecurity Awareness Webinar', 'zoom',
           'https://zoom.us/j/92345678901', '2026-11-12 10:00:00', 60, 'scheduled'
    UNION ALL SELECT 'Customer Experience Excellence Program', NULL,
           'Customer Service Bootcamp - Session 1', 'google_meet',
           'https://meet.google.com/klm-nopq-rst', '2026-10-06 14:00:00', 150, 'completed'
    UNION ALL SELECT NULL, NULL,
           'New Hire Orientation Live Session', 'other',
           'https://teams.microsoft.com/l/meetup-join/new-hire-orientation', '2026-11-03 08:30:00', 90, 'scheduled'
    UNION ALL SELECT NULL, 'Effective Business Communication',
           'Effective Communication Skills Clinic', 'zoom',
           'https://zoom.us/j/93456789012', '2026-11-18 15:00:00', 75, 'scheduled'
) AS s
LEFT JOIN ld_course c ON c.title = s.course_title
LEFT JOIN ld_program p ON p.title = s.program_title
WHERE NOT EXISTS (SELECT 1 FROM ld_video_conference v WHERE v.title = s.title);

-- ---------------------------------------------------------------------
-- 8. Learning paths
-- ---------------------------------------------------------------------
INSERT INTO ld_learning_path (instructor_id, title, description, status, type, is_public)
SELECT @instructor_id, s.title, s.description, 'active', 'standard', 1
FROM (
    SELECT 'New Hire Onboarding Journey' AS title, 'The essential first-30-days path for every new employee: safety, communication, and security fundamentals.' AS description
    UNION ALL SELECT 'Aspiring Team Lead Track', 'A guided path for individual contributors who want to step into team leadership.'
    UNION ALL SELECT 'Data and Analytics Career Path', 'Build the spreadsheet and communication skills needed to grow into a data-focused role.'
    UNION ALL SELECT 'Workplace Safety Certification Path', 'Complete the required safety training and assessment to earn the internal safety certification.'
) AS s
WHERE NOT EXISTS (SELECT 1 FROM ld_learning_path lp WHERE lp.title = s.title);

-- ---------------------------------------------------------------------
-- 9. Learning path items (ordered)
-- ---------------------------------------------------------------------
INSERT INTO ld_learning_path_item (learning_path_id, item_type, reference_id, order_index, status)
SELECT lp.id, s.item_type, COALESCE(c.id, m.id, q.id, e.id, p.id, v.id), s.order_index, 'active'
FROM (
    SELECT 'New Hire Onboarding Journey' AS path_title, 'course' AS item_type, 'Workplace Safety and Compliance Essentials' AS ref_title, 1 AS order_index
    UNION ALL SELECT 'New Hire Onboarding Journey', 'course', 'Effective Business Communication', 2
    UNION ALL SELECT 'New Hire Onboarding Journey', 'course', 'Cybersecurity Awareness for Employees', 3
    UNION ALL SELECT 'Aspiring Team Lead Track', 'course', 'Leadership Foundations for New Managers', 1
    UNION ALL SELECT 'Aspiring Team Lead Track', 'module', 'Coaching and Performance Conversations', 2
    UNION ALL SELECT 'Aspiring Team Lead Track', 'program', 'Management Development Program', 3
    UNION ALL SELECT 'Data and Analytics Career Path', 'course', 'Data Analysis with Microsoft Excel', 1
    UNION ALL SELECT 'Data and Analytics Career Path', 'module', 'Data Visualization and Reporting', 2
    UNION ALL SELECT 'Data and Analytics Career Path', 'course', 'Effective Business Communication', 3
    UNION ALL SELECT 'Workplace Safety Certification Path', 'course', 'Workplace Safety and Compliance Essentials', 1
    UNION ALL SELECT 'Workplace Safety Certification Path', 'quiz', 'Quiz: Hazard Identification and Risk Control', 2
    UNION ALL SELECT 'Workplace Safety Certification Path', 'evaluation', 'Final Assessment: Workplace Safety and Compliance Essentials', 3
) AS s
JOIN ld_learning_path lp ON lp.title = s.path_title
LEFT JOIN ld_course c ON s.item_type = 'course' AND c.title = s.ref_title
LEFT JOIN ld_module m ON s.item_type = 'module' AND m.title = s.ref_title
LEFT JOIN ld_quiz q ON s.item_type = 'quiz' AND q.title = s.ref_title
LEFT JOIN ld_evaluation e ON s.item_type = 'evaluation' AND e.title = s.ref_title
LEFT JOIN ld_program p ON s.item_type = 'program' AND p.title = s.ref_title
LEFT JOIN ld_video_conference v ON s.item_type = 'video-conference' AND v.title = s.ref_title
WHERE COALESCE(c.id, m.id, q.id, e.id, p.id, v.id) IS NOT NULL
  AND NOT EXISTS (
        SELECT 1 FROM ld_learning_path_item li
        WHERE li.learning_path_id = lp.id
          AND li.item_type = s.item_type
          AND li.reference_id = COALESCE(c.id, m.id, q.id, e.id, p.id, v.id)
  );

-- ---------------------------------------------------------------------
-- 10. Course templates
-- ---------------------------------------------------------------------
INSERT INTO ld_course_template (title, description, category, created_by, structure_json, module_count, lesson_count, quiz_count)
SELECT s.title, s.description, s.category, @instructor_id, s.structure_json, s.module_count, s.lesson_count, s.quiz_count
FROM (
    SELECT 'Onboarding Essentials Template' AS title,
           'A ready-made one-module template for new-hire orientation courses.' AS description,
           'Onboarding' AS category,
           '{"modules":[{"title":"Welcome and Orientation","description":"Company overview, culture, and expectations.","order_index":1,"status":"active","lessons":[{"title":"Welcome to the Company","content_type":"text","content_body":"<p>An introduction to our mission and values.</p>","order_index":1,"status":"active","quizzes":[],"files":[]}],"quizzes":[]}]}' AS structure_json,
           1 AS module_count, 1 AS lesson_count, 0 AS quiz_count
    UNION ALL SELECT 'Compliance Training Template',
           'A reusable template for mandatory compliance and policy training.',
           'Compliance & Safety',
           '{"modules":[{"title":"Policies and Procedures","description":"Key policies every employee must follow.","order_index":1,"status":"active","lessons":[{"title":"Understanding Company Policies","content_type":"text","content_body":"<p>Review the policies that apply to your role.</p>","order_index":1,"status":"active","quizzes":[],"files":[]}],"quizzes":[{"title":"Policy Knowledge Check","duration_seconds":600,"passing_score":"80.00","max_attempts":2,"question_count":5,"show_answers_after_submit":1,"status":"active","questions":[]}]}]}',
           1, 1, 1
    UNION ALL SELECT 'Leadership Workshop Template',
           'A starter template for leadership and management workshops.',
           'Leadership',
           '{"modules":[{"title":"Leading People","description":"Core people-leadership concepts.","order_index":1,"status":"active","lessons":[{"title":"What Makes a Great Leader","content_type":"video","video_url":"https://www.youtube.com/embed/1Y9d3lR6Qm4","order_index":1,"status":"active","quizzes":[],"files":[]}],"quizzes":[]}]}',
           1, 1, 0
) AS s
WHERE NOT EXISTS (SELECT 1 FROM ld_course_template t WHERE t.title = s.title);

-- ---------------------------------------------------------------------
-- 11. Course <-> instructor assignment
-- ---------------------------------------------------------------------
SET @course_instructor_base := (SELECT COALESCE(MAX(id), 0) FROM ld_course_instructor);
INSERT INTO ld_course_instructor (id, course_id, instructor_id, role)
SELECT @course_instructor_base + ROW_NUMBER() OVER (ORDER BY c.id), c.id, @instructor_id, 'owner'
FROM ld_course c
WHERE c.title IN (
        'Workplace Safety and Compliance Essentials',
        'Effective Business Communication',
        'Data Analysis with Microsoft Excel',
        'Leadership Foundations for New Managers',
        'Customer Service Excellence',
        'Cybersecurity Awareness for Employees'
      )
  AND NOT EXISTS (
        SELECT 1 FROM ld_course_instructor ci
        WHERE ci.course_id = c.id AND ci.instructor_id = @instructor_id
  );

-- ---------------------------------------------------------------------
-- 12. Course <-> skill links
-- ---------------------------------------------------------------------
INSERT INTO ld_course_skill (course_id, skill_id)
SELECT c.id, sk.id
FROM (
    SELECT 'Workplace Safety and Compliance Essentials' AS course_title, 'Compliance and Safety' AS skill_name
    UNION ALL SELECT 'Effective Business Communication', 'Communication Skills'
    UNION ALL SELECT 'Data Analysis with Microsoft Excel', 'Data Analysis'
    UNION ALL SELECT 'Data Analysis with Microsoft Excel', 'Microsoft Excel'
    UNION ALL SELECT 'Leadership Foundations for New Managers', 'Leadership'
    UNION ALL SELECT 'Customer Service Excellence', 'Customer Service'
    UNION ALL SELECT 'Cybersecurity Awareness for Employees', 'Cybersecurity'
) AS map
JOIN ld_course c ON c.title = map.course_title
JOIN ld_skill sk ON sk.name = map.skill_name
WHERE NOT EXISTS (SELECT 1 FROM ld_course_skill cs WHERE cs.course_id = c.id AND cs.skill_id = sk.id);

-- ---------------------------------------------------------------------
-- 13. Module <-> skill links (ld_module_skill.id is not auto-increment)
-- ---------------------------------------------------------------------
SET @module_skill_base := (SELECT COALESCE(MAX(id), 0) FROM ld_module_skill);
INSERT INTO ld_module_skill (id, module_id, skill_id)
SELECT @module_skill_base + ROW_NUMBER() OVER (ORDER BY m.id, sk.id), m.id, sk.id
FROM (
    SELECT 'Excel Foundations and Data Cleaning' AS module_title, 'Microsoft Excel' AS skill_name
    UNION ALL SELECT 'Data Visualization and Reporting', 'Data Analysis'
    UNION ALL SELECT 'Hazard Identification and Risk Control', 'Compliance and Safety'
    UNION ALL SELECT 'Coaching and Performance Conversations', 'Leadership'
    UNION ALL SELECT 'Communication Essentials', 'Communication Skills'
) AS map
JOIN ld_module m ON m.title = map.module_title
JOIN ld_skill sk ON sk.name = map.skill_name
WHERE NOT EXISTS (SELECT 1 FROM ld_module_skill ms WHERE ms.module_id = m.id AND ms.skill_id = sk.id);

-- ---------------------------------------------------------------------
-- 14. Learning path <-> skill links
-- ---------------------------------------------------------------------
INSERT INTO ld_learning_path_skill (learning_path_id, skill_id)
SELECT lp.id, sk.id
FROM (
    SELECT 'New Hire Onboarding Journey' AS path_title, 'Compliance and Safety' AS skill_name
    UNION ALL SELECT 'Aspiring Team Lead Track', 'Leadership'
    UNION ALL SELECT 'Data and Analytics Career Path', 'Data Analysis'
    UNION ALL SELECT 'Workplace Safety Certification Path', 'Compliance and Safety'
) AS map
JOIN ld_learning_path lp ON lp.title = map.path_title
JOIN ld_skill sk ON sk.name = map.skill_name
WHERE NOT EXISTS (SELECT 1 FROM ld_learning_path_skill ls WHERE ls.learning_path_id = lp.id AND ls.skill_id = sk.id);

-- ---------------------------------------------------------------------
-- 15. Program <-> skill links
-- ---------------------------------------------------------------------
INSERT INTO ld_program_skill (program_id, skill_id)
SELECT p.id, sk.id
FROM (
    SELECT 'Management Development Program' AS program_title, 'Leadership' AS skill_name
    UNION ALL SELECT 'Customer Experience Excellence Program', 'Customer Service'
) AS map
JOIN ld_program p ON p.title = map.program_title
JOIN ld_skill sk ON sk.name = map.skill_name
WHERE NOT EXISTS (SELECT 1 FROM ld_program_skill ps WHERE ps.program_id = p.id AND ps.skill_id = sk.id);

COMMIT;

-- ---------------------------------------------------------------------
-- Summary
-- ---------------------------------------------------------------------
SELECT 'Courses' AS entity, COUNT(*) AS created FROM ld_course WHERE instructor_id = @instructor_id AND status = 'active'
UNION ALL SELECT 'Learning paths', COUNT(*) FROM ld_learning_path WHERE instructor_id = @instructor_id
UNION ALL SELECT 'Programs', COUNT(*) FROM ld_program WHERE instructor_id = @instructor_id
UNION ALL SELECT 'Online training', COUNT(*) FROM ld_video_conference WHERE instructor_id = @instructor_id
UNION ALL SELECT 'Course templates', COUNT(*) FROM ld_course_template WHERE created_by = @instructor_id;
