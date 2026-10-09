INSERT INTO ld_skill (id, name, description, status)
SELECT current_ids.max_id + seed.seed_id, seed.name, seed.description, 'active'
FROM (
    SELECT 1 AS seed_id, 'Project Management' AS name, 'Plan, coordinate, and deliver projects using practical methods and tools.' AS description
    UNION ALL SELECT 2, 'Leadership', 'Guide teams, make decisions, resolve conflict, and support performance.'
    UNION ALL SELECT 3, 'Communication Skills', 'Build clear written, verbal, presentation, and interpersonal communication.'
    UNION ALL SELECT 4, 'Technical Writing', 'Create clear technical documentation, guides, procedures, and knowledge articles.'
    UNION ALL SELECT 5, 'Data Analysis', 'Collect, interpret, and present data for evidence-based decisions.'
    UNION ALL SELECT 6, 'Cybersecurity', 'Recognize threats and apply security practices to systems, networks, and data.'
    UNION ALL SELECT 7, 'Web Development', 'Build and maintain responsive web applications and services.'
    UNION ALL SELECT 8, 'Customer Service', 'Support customers through active listening, empathy, and effective resolution.'
    UNION ALL SELECT 9, 'Critical Thinking', 'Evaluate evidence, identify assumptions, and solve problems systematically.'
    UNION ALL SELECT 10, 'Teamwork', 'Collaborate effectively, share responsibility, and contribute to common goals.'
    UNION ALL SELECT 11, 'Time Management', 'Prioritize work, organize schedules, and meet deadlines consistently.'
    UNION ALL SELECT 12, 'Problem Solving', 'Analyze challenges and develop practical, measurable solutions.'
    UNION ALL SELECT 13, 'Digital Literacy', 'Use workplace software, online tools, and digital information effectively.'
    UNION ALL SELECT 14, 'Microsoft Excel', 'Organize data and use formulas, tables, and reports in Microsoft Excel.'
    UNION ALL SELECT 15, 'Human Resources', 'Apply core HR practices in employee relations, staffing, and administration.'
    UNION ALL SELECT 16, 'Compliance and Safety', 'Follow workplace policies, regulatory requirements, and safety procedures.'
) AS seed
JOIN (SELECT COALESCE(MAX(id), 0) AS max_id FROM ld_skill) AS current_ids
    ON 1 = 1
WHERE NOT EXISTS (SELECT 1 FROM ld_skill WHERE status = 'active');