-- =============================================================================
--  ld_seed_skill_map.sql
--  Companion to ld_seed_skills.sql. Maps the seeded skill catalog to every
--  LD table that carries skill tags:
--      ld_course_skill            (course -> skill)
--      ld_module_skill            (module -> skill)
--      ld_learning_path_skill     (learning_path -> skill)
--      ld_program_skill           (program -> skill)
--      ld_competency_requirement  (workforce competency -> skill-name based)
--  Target DB: lear_hrms
--  Safety: only runs when ld_course_skill is empty (no active course->skill
--  mapping exists). Re-run after ld_seed_skills.sql if you already imported
--  ld_skill with rows you want to keep.
-- =============================================================================

USE `lear_hrms`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Guard: skip entirely if active course->skill mappings already exist.
-- This avoids accidentally clobbering a real teaching catalog.
SELECT COUNT(*) INTO @existing_map
FROM ld_course_skill cs
JOIN ld_skill s ON s.id = cs.skill_id
WHERE s.status = 'active';

IF @existing_map = 0 THEN

  -- -------------------------------------------------------------------------
  --  ld_course_skill — one realistic mapping per core workplace skill.
  --  Course IDs are illustrative placeholders (course id 1..16). Replace the
  --  course_id values with the real ids you create later; the shape here is
  --  exactly what ld_course_skill expects (course_id, skill_id) and matches
  --  the FK: course_id -> ld_course(id), skill_id -> ld_skill(id).
  -- -------------------------------------------------------------------------
  INSERT INTO `ld_course_skill` (`course_id`, `skill_id`)
  SELECT
    1, 101 UNION ALL SELECT 2, 102 UNION ALL SELECT 3, 103 UNION ALL SELECT
    4, 104 UNION ALL SELECT 5, 105 UNION ALL SELECT 6, 106 UNION ALL SELECT
    7, 107 UNION ALL SELECT 8, 108 UNION ALL SELECT 9, 109 UNION ALL SELECT
    10, 110 UNION ALL SELECT 11, 111 UNION ALL SELECT 12, 112 UNION ALL SELECT
    13, 113 UNION ALL SELECT 14, 114 UNION ALL SELECT 15, 115 UNION ALL SELECT
    16, 116
  ;

  -- -------------------------------------------------------------------------
  --  ld_module_skill — one illustrative module (module id 1) carries a
  --  broad set of foundational skills. Again, replace module_id with the real
  --  ids you create later. Shape: (module_id, skill_id); FK module_id ->
  --  ld_module(id), skill_id -> ld_skill(id).
  -- -------------------------------------------------------------------------
  INSERT INTO `ld_module_skill` (`module_id`, `skill_id`)
  SELECT 1, 109 UNION ALL SELECT 1, 110 UNION ALL SELECT 1, 111 UNION ALL SELECT
      1, 112 UNION ALL SELECT 1, 103 UNION ALL SELECT 1, 105 UNION ALL SELECT
      1, 113 UNION ALL SELECT 1, 131
  ;

  -- -------------------------------------------------------------------------
  --  ld_learning_path_skill — one standard learning path (learning_path id 1)
  --  tagged with capstone competencies. Shape: (learning_path_id, skill_id);
  --  FK learning_path_id -> ld_learning_path(id), skill_id -> ld_skill(id).
  -- -------------------------------------------------------------------------
  INSERT INTO `ld_learning_path_skill` (`learning_path_id`, `skill_id`)
  SELECT 1, 101 UNION ALL SELECT 1, 102 UNION ALL SELECT 1, 109 UNION ALL SELECT
      1, 112 UNION ALL SELECT 1, 134
  ;

  -- -------------------------------------------------------------------------
  --  ld_program_skill — one program (program id 1) tagged with transferable
  --  skills. Shape: (program_id, skill_id); FK program_id -> ld_program(id),
  --  skill_id -> ld_skill(id).
  -- -------------------------------------------------------------------------
  INSERT INTO `ld_program_skill` (`program_id`, `skill_id`)
  SELECT 1, 103 UNION ALL SELECT 1, 108 UNION ALL SELECT 1, 110 UNION ALL SELECT
      1, 113
  ;

  -- -------------------------------------------------------------------------
  --  ld_competency_requirement — seed a handful of workforce competency
  --  records whose required skill matches a seeded ld_skill row by name.
  --  The application writes these rows via the workforce inbound API
  --  (receive-employee-profile.php / workforce-integration.php); this seed
  --  just pre-populates plausible records so the screens do not start empty.
  --  Columns reviewed against ld_competency_requirement as defined in
  --  workforce_analytics_improvements.sql: id, source_module, skill_name,
  --  target_department, priority_level, employees_needing_training,
  --  target_proficiency, expected_learning_output, notes, status, created_by,
  --  created_at. We leave optional FK / numeric columns at sensible defaults.
  -- -------------------------------------------------------------------------
  INSERT INTO `ld_competency_requirement` (
      `id`, `source_module`, `skill_name`, `target_department`,
      `priority_level`, `employees_needing_training`, `target_proficiency`,
      `expected_learning_output`, `notes`, `status`, `created_by`, `created_at`
  )
  SELECT
      1, 'workforce', 'Project Management', 'All Departments', 'high',  12,
      'Intermediate', 'Learners can plan, coordinate, and deliver a small project using practical methods and tools.',
      'Prerequisite for all coordinator tracks.', 'received', 1, NOW()
  UNION ALL SELECT
      2, 'workforce', 'Leadership', 'All Departments', 'high',  12,
      'Intermediate', 'Learners can guide teams, make decisions, resolve conflict, and support performance.',
      'Required for supervisor candidates.', 'received', 1, NOW()
  UNION ALL SELECT
      3, 'workforce', 'Communication Skills', 'All Departments', 'medium', 18,
      'Intermediate', 'Learners can produce clear written, verbal, presentation, and interpersonal communication.',
      'Cross-cutting skill.', 'received', 1, NOW()
  UNION ALL SELECT
      4, 'workforce', 'Cybersecurity', 'IT / All Departments', 'critical', 12,
      'Intermediate', 'Learners can recognize threats and apply security practices to systems, networks, and data.',
      'Compliance-sensitive track.', 'received', 1, NOW()
  UNION ALL SELECT
      5, 'workforce', 'Data Analysis', 'Analytics / All Departments', 'medium', 15,
      'Intermediate', 'Learners can collect, interpret, and present data for evidence-based decisions.',
      'Used by workforce reporting.', 'received', 1, NOW()
  ;

END IF;

-- -------------------------------------------------------------------------
--  ld_course_skill / ld_module_skill / ld_learning_path_skill / ld_program_skill
--  all reference ld_skill by id, so bump the skill AUTO_INCREMENT away from
--  the seeded range so future manual skills do not collide with our catalog.
-- -------------------------------------------------------------------------
ALTER TABLE `ld_skill` AUTO_INCREMENT = 200;

SET FOREIGN_KEY_CHECKS = 1;
