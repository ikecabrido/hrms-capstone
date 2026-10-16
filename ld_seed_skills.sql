-- =============================================================================
--  ld_seed_skills.sql
--  Idempotent seed: inserts the standard skill catalog only if ld_skill is empty.
--  Target DB: lear_hrms   Table: ld_skill
--  Columns used: id, name, description, suggested, status, created_at, updated_at
-- =============================================================================

USE `lear_hrms`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Only seed if there is no active skill row yet — safe to re-run after restore.
INSERT INTO `ld_skill` (`id`, `name`, `description`, `suggested`, `status`, `created_at`, `updated_at`)
SELECT
  101, 'Project Management',    'Plan, coordinate, and deliver projects using practical methods and tools.',     1, 'active', NOW(), NOW()
UNION ALL SELECT
  102, 'Leadership',            'Guide teams, make decisions, resolve conflict, and support performance.',       1, 'active', NOW(), NOW()
UNION ALL SELECT
  103, 'Communication Skills',  'Build clear written, verbal, presentation, and interpersonal communication.',   1, 'active', NOW(), NOW()
UNION ALL SELECT
  104, 'Technical Writing',     'Create clear technical documentation, guides, procedures, and knowledge articles.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  105, 'Data Analysis',         'Collect, interpret, and present data for evidence-based decisions.',             1, 'active', NOW(), NOW()
UNION ALL SELECT
  106, 'Cybersecurity',         'Recognize threats and apply security practices to systems, networks, and data.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  107, 'Web Development',       'Build and maintain responsive web applications and services.',                   1, 'active', NOW(), NOW()
UNION ALL SELECT
  108, 'Customer Service',      'Support customers through active listening, empathy, and effective resolution.',  1, 'active', NOW(), NOW()
UNION ALL SELECT
  109, 'Critical Thinking',     'Evaluate evidence, identify assumptions, and solve problems systematically.',     1, 'active', NOW(), NOW()
UNION ALL SELECT
  110, 'Teamwork',              'Collaborate effectively, share responsibility, and contribute to common goals.',  1, 'active', NOW(), NOW()
UNION ALL SELECT
  111, 'Time Management',       'Prioritize work, organize schedules, and meet deadlines consistently.',          1, 'active', NOW(), NOW()
UNION ALL SELECT
  112, 'Problem Solving',       'Analyze challenges and develop practical, measurable solutions.',                1, 'active', NOW(), NOW()
UNION ALL SELECT
  113, 'Digital Literacy',      'Use workplace software, online tools, and digital information effectively.',     1, 'active', NOW(), NOW()
UNION ALL SELECT
  114, 'Microsoft Excel',       'Organize data and use formulas, tables, and reports in Microsoft Excel.',        1, 'active', NOW(), NOW()
UNION ALL SELECT
  115, 'Human Resources',       'Apply core HR practices in employee relations, staffing, and administration.',   1, 'active', NOW(), NOW()
UNION ALL SELECT
  116, 'Compliance and Safety', 'Follow workplace policies, regulatory requirements, and safety procedures.',     1, 'active', NOW(), NOW()
UNION ALL SELECT
  117, 'Mathematics',           'Translating information across quantities, pattern recognition, and proportional reasoning.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  118, 'English Language Arts', 'Read, interpret, and write clear text across literature and informational genres.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  119, 'Science',               'Observe, question, measure, and reason from evidence to explain natural phenomena.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  120, 'Social Studies',        'Examine human societies, geography, economics, government, and history.',        1, 'active', NOW(), NOW()
UNION ALL SELECT
  121, 'Second Language Acquisition', 'Build listening, speaking, reading, and writing in an additional language.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  122, 'Visual and Performing Arts', 'Create, interpret, and critique art, music, theater, and dance.',           1, 'active', NOW(), NOW()
UNION ALL SELECT
  123, 'Physical Education',    'Develop movement skills, fitness, teamwork, and a healthy, active lifestyle.',    1, 'active', NOW(), NOW()
UNION ALL SELECT
  124, 'Career and Technical Education', 'Learn applied trades, industry certifications, and real-world employability skills.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  125, 'Computer Science',      'Algorithmic thinking, programming, data structures, and responsible technology use.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  126, 'Financial Literacy',    'Budgeting, saving, spending wisely, credit, investing, and money management.',  1, 'active', NOW(), NOW()
UNION ALL SELECT
  127, 'Health and Wellness',   'Understand nutrition, mental health, relationships, and lifelong healthy habits.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  128, 'Civics',                'Roles of citizens, institutions, law, and community participation in a democratic society.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  129, 'Media and Information Literacy', 'Evaluate sources, recognize bias, and navigate digital information responsibly.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  130, 'Entrepreneurship',      'Identify opportunities, build value, fund ideas, and manage small ventures.',    1, 'active', NOW(), NOW()
UNION ALL SELECT
  131, 'Study Skills',          'Effective note-taking, summarization, memory strategies, and exam preparation habits.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  132, 'Public Speaking',       'Organize and deliver confident, audience-aware oral presentations.',             1, 'active', NOW(), NOW()
UNION ALL SELECT
  133, 'Research Methods',      'Frame questions, gather evidence, cite sources, and synthesize findings.',       1, 'active', NOW(), NOW()
UNION ALL SELECT
  134, 'Mentoring and Coaching', 'Support others through feedback, goal-setting, and developmental conversations.', 1, 'active', NOW(), NOW()
UNION ALL SELECT
  135, 'Conflict Resolution',   'De-escalate tension, find shared ground, and negotiate durable solutions.',      1, 'active', NOW(), NOW()
UNION ALL SELECT
  136, 'Data Privacy',          'Handle personal and organizational data ethically, legally, and securely.',      1, 'active', NOW(), NOW()
UNION ALL SELECT
  137, 'Presentation Design',   'Design clear slide decks and visuals that support, rather than duplicate, spoken content.', 1, 'active', NOW(), NOW()
;

-- Keep the auto_increment at a safe value so future manual adds do not reuse old ids.
ALTER TABLE `ld_skill` AUTO_INCREMENT = 200;

SET FOREIGN_KEY_CHECKS = 1;
