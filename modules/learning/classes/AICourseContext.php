<?php
/**
 * AICourseContext — privacy-scoped trainee evidence for course generation.
 *
 * The returned `context` array is safe to send to the model: it contains no
 * employee id, employee code, email, phone, address, salary, birth data, or
 * performance-rating fields. The `meta` array is for the admin UI only.
 *
 * Skill gaps are computed live from the trainee's included enrollments,
 * completed course/module skills, and profile skills. The latest
 * ld_skill_snapshot row is used only when live course-skill evidence is absent.
 */

require_once __DIR__ . '/progress.php';
require_once __DIR__ . '/ProfileLink.php';

class AICourseContext
{
    private PDO $pdo;
    private string $externalSkillsTable;
    private bool $programAssessmentsAvailable;
    private bool $snapshotAvailable;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->externalSkillsTable = $this->tableExists('em_skills')
            ? 'em_skills'
            : ($this->tableExists('employee_skills') ? 'employee_skills' : '');
        $this->programAssessmentsAvailable = $this->tableExists('ld_program_assessment');
        $this->snapshotAvailable = $this->tableExists('ld_skill_snapshot');
    }

    /**
     * Search active trainees for the admin picker. Results deliberately omit
     * contact data and employee codes.
     *
     * @return array{success: bool, trainees: array<int, array<string, mixed>>, message: string}
     */
    public function searchTrainees(string $query, int $limit = 10): array
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        $limit = max(1, min(20, $limit));
        if (mb_strlen($query) < 2) {
            return [
                'success' => true,
                'trainees' => [],
                'message' => 'Type at least two characters to search.',
            ];
        }

        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.hire_date,
                       e.employment_status, d.department_name, p.position_name
                  FROM em_employees e
                  LEFT JOIN em_departments d ON d.department_id = e.department_id
                  LEFT JOIN em_positions p ON p.position_id = e.position_id
                 WHERE e.is_archived = 0
                   AND e.employment_status = 'Active'
                   AND (
                        e.first_name LIKE :query
                        OR e.last_name LIKE :query
                        OR CONCAT(e.first_name, ' ', e.last_name) LIKE :query
                        OR e.employee_code LIKE :query
                        OR d.department_name LIKE :query
                        OR p.position_name LIKE :query
                   )
                 ORDER BY e.first_name ASC, e.last_name ASC
                 LIMIT :row_limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':row_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $trainees = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $employeeId = (int) $row['employee_id'];
            $trainees[] = [
                'id' => $employeeId,
                'name' => trim((string) $row['first_name'] . ' ' . (string) $row['last_name']),
                'department' => (string) ($row['department_name'] ?? ''),
                'position' => (string) ($row['position_name'] ?? ''),
                'hire_date' => $row['hire_date'] !== null ? (string) $row['hire_date'] : null,
                'employment_status' => (string) ($row['employment_status'] ?? ''),
                'profile_url' => LearningProfileLink::url($employeeId, 'learner'),
            ];
        }

        return [
            'success' => true,
            'trainees' => $trainees,
            'message' => $trainees === [] ? 'No active trainees matched.' : '',
        ];
    }

    /**
     * Build the admin preview and the prompt-safe context.
     *
     * @param int[] $selectedSkillIds
     * @param string[] $excludedEvidence
     * @return array<string, mixed>
     */
    public function build(int $employeeId, array $selectedSkillIds = [], array $excludedEvidence = []): array
    {
        if ($employeeId <= 0) {
            return ['success' => false, 'message' => 'Choose a trainee first.'];
        }

        $employee = $this->loadEmployee($employeeId);
        if ($employee === null) {
            return ['success' => false, 'message' => 'Trainee not found.'];
        }
        if ($employee['employment_status'] !== 'Active' || $employee['is_archived'] === 1) {
            return ['success' => false, 'message' => 'Only active trainees can be selected.'];
        }

        $warnings = [];
        $excluded = $this->normalizeExclusions($excludedEvidence, $warnings);
        $included = static fn(string $key): bool => !in_array($key, $excluded, true);

        $activeSkills = $this->loadActiveSkills();
        $activeById = [];
        $activeByName = [];
        foreach ($activeSkills as $skill) {
            $activeById[(int) $skill['id']] = $skill;
            $activeByName[mb_strtolower($skill['name'])] = $skill;
        }
        if ($activeSkills === []) {
            $warnings[] = 'No active skills are defined in the skill catalog.';
        }

        $allEnrollments = $this->loadEnrollments($employeeId);
        $includedEnrollments = [];
        foreach ($allEnrollments as $enrollment) {
            $isCompleted = $enrollment['status'] === 'completed';
            if ($isCompleted ? $included('completed_courses') : $included('current_courses')) {
                $includedEnrollments[] = $enrollment;
            }
        }
        $includedEnrollmentIds = array_map(
            static fn(array $enrollment): int => (int) $enrollment['id'],
            $includedEnrollments
        );

        $allSkills = [];
        $acquiredSkills = [];
        $enrollmentSkills = [];

        $courseSkills = $includedEnrollmentIds !== []
            ? $this->loadCourseSkills($employeeId, $includedEnrollmentIds)
            : [];
        foreach ($courseSkills as $row) {
            $skillId = (int) $row['skill_id'];
            $this->addSkill($allSkills, $skillId, (string) $row['skill_name'], 'course');
            if ($row['enrollment_status'] === 'completed') {
                $this->addSkill($acquiredSkills, $skillId, (string) $row['skill_name'], 'completed_course');
            }
            $this->addEnrollmentSkill($enrollmentSkills, (int) $row['enrollment_id'], $skillId, (string) $row['skill_name']);
        }

        $moduleSkills = $includedEnrollmentIds !== []
            ? $this->loadModuleSkills($employeeId, $includedEnrollmentIds)
            : [];
        foreach ($moduleSkills as $row) {
            $skillId = (int) $row['skill_id'];
            $this->addSkill($allSkills, $skillId, (string) $row['skill_name'], 'module');
            if ((int) $row['module_complete'] === 1) {
                $this->addSkill($acquiredSkills, $skillId, (string) $row['skill_name'], 'completed_module');
            }
            $this->addEnrollmentSkill($enrollmentSkills, (int) $row['enrollment_id'], $skillId, (string) $row['skill_name']);
        }

        $courseSkillCount = count($allSkills);
        $externalSkills = [];
        if ($included('external_skills')) {
            try {
                $externalSkills = $this->loadExternalSkills($employeeId);
            } catch (Throwable $e) {
                $warnings[] = 'Employee profile skills could not be read.';
            }
        }
        foreach ($externalSkills as $external) {
            if ($external['skill_id'] !== null) {
                $this->addSkill($allSkills, (int) $external['skill_id'], (string) $external['name'], 'employee_profile');
                $this->addSkill($acquiredSkills, (int) $external['skill_id'], (string) $external['name'], 'employee_profile');
            }
        }

        $snapshot = null;
        $snapshotUsed = false;
        $source = 'limited';
        $gapSkills = [];

        if ($courseSkillCount > 0) {
            $source = 'live';
            $gapSkills = $this->subtractSkillsByName($allSkills, $acquiredSkills);
        } elseif ($included('snapshot') && $this->snapshotAvailable) {
            $snapshot = $this->loadSnapshot($employeeId);
            if ($snapshot !== null) {
                $snapshotUsed = true;
                $source = 'snapshot';
                $snapshotGaps = $this->mapSnapshotSkills($snapshot['gap_skills'], $activeByName);
                $snapshotAcquired = $this->mapSnapshotSkills($snapshot['acquired_skills'], $activeByName);
                foreach ($snapshotAcquired as $skill) {
                    if ($skill['id'] !== null) {
                        $this->addSkill($acquiredSkills, (int) $skill['id'], (string) $skill['name'], 'snapshot');
                    }
                }
                $gapSkills = $snapshotGaps;
                $warnings[] = 'The skill snapshot from ' . $snapshot['snapshot_date']
                    . ' is used because live course-skill evidence is unavailable.';
                if ($snapshot['stale']) {
                    $warnings[] = 'That skill snapshot is more than 30 days old; review it before generating.';
                }
            }
        }

        if ($source === 'limited') {
            $warnings[] = 'No live course skills or usable skill snapshot were found. The assistant will use limited evidence.';
        }

        $selectedSkills = $this->resolveSelectedSkills(
            $selectedSkillIds,
            $activeById,
            $gapSkills,
            $warnings
        );

        $completedMap = [];
        $currentMap = [];
        foreach ($includedEnrollments as $enrollment) {
            $skills = $this->skillNameList($enrollmentSkills[(int) $enrollment['id']] ?? []);
            $title = (string) $enrollment['course_title'];
            $titleKey = mb_strtolower(trim($title));
            if ($enrollment['status'] === 'completed') {
                if (!isset($completedMap[$titleKey])) {
                    $completedMap[$titleKey] = [
                        'title' => $title,
                        'completed_at' => $enrollment['completed_at'] !== null ? (string) $enrollment['completed_at'] : null,
                        'skills' => $skills,
                    ];
                } else {
                    $completedMap[$titleKey]['skills'] = array_values(array_unique(array_merge(
                        $completedMap[$titleKey]['skills'],
                        $skills
                    )));
                    $completedAt = $enrollment['completed_at'] !== null ? (string) $enrollment['completed_at'] : null;
                    if ($completedAt !== null && ($completedMap[$titleKey]['completed_at'] === null
                        || $completedAt > $completedMap[$titleKey]['completed_at'])) {
                        $completedMap[$titleKey]['completed_at'] = $completedAt;
                    }
                }
            } else {
                if (!isset($currentMap[$titleKey])) {
                    $currentMap[$titleKey] = [
                        'title' => $title,
                        'status' => (string) $enrollment['status'],
                        'enrolled_at' => $enrollment['enrolled_at'] !== null ? (string) $enrollment['enrolled_at'] : null,
                        'skills' => $skills,
                    ];
                } else {
                    $currentMap[$titleKey]['skills'] = array_values(array_unique(array_merge(
                        $currentMap[$titleKey]['skills'],
                        $skills
                    )));
                    $enrolledAt = $enrollment['enrolled_at'] !== null ? (string) $enrollment['enrolled_at'] : null;
                    if ($enrolledAt !== null && ($currentMap[$titleKey]['enrolled_at'] === null
                        || $enrolledAt < $currentMap[$titleKey]['enrolled_at'])) {
                        $currentMap[$titleKey]['enrolled_at'] = $enrolledAt;
                    }
                }
            }
        }
        $completedCourses = array_values($completedMap);
        $currentCourses = array_values($currentMap);

        if ($included('completed_courses') && $completedCourses === []) {
            $warnings[] = 'No completed courses were found for this trainee.';
        }
        if ($included('current_courses') && $currentCourses === []) {
            $warnings[] = 'No current enrollments were found for this trainee.';
        }
        if ($included('completed_courses') && $included('current_courses') && $includedEnrollments === []) {
            $warnings[] = 'No course enrollment evidence was found for this trainee.';
        }

        $quizPerformance = null;
        if ($included('quiz_performance')) {
            $quizPerformance = $this->loadQuizPerformance($employeeId);
            if (($quizPerformance['summary']['attempts'] ?? 0) === 0) {
                $warnings[] = 'No quiz or evaluation attempts were found for this trainee.';
            }
        }

        $programAssessments = null;
        if ($included('program_assessments')) {
            $programAssessments = $this->loadProgramAssessments($employeeId);
            if (!$programAssessments['available']) {
                $warnings[] = 'Program assessment data is unavailable until the delivery/assessment migration is applied.';
            } elseif (($programAssessments['summary']['count'] ?? 0) === 0) {
                $warnings[] = 'No completed program assessments were found for this trainee.';
            }
        }

        $context = [
            'trainee' => [
                'name' => $employee['name'],
                'department' => $employee['department'],
                'position' => $employee['position'],
                'hire_date' => $employee['hire_date'],
                'employment_status' => $employee['employment_status'],
            ],
            'skills' => [
                'source' => $source,
                'acquired' => $this->skillNameList($acquiredSkills),
                'gaps' => $this->skillNameList($gapSkills),
                'selected' => $this->skillNameList($selectedSkills),
                // Only current gaps and explicitly selected skills may be linked
                // to a generated course. The IDs are not sensitive and prevent
                // the model from guessing unrelated catalog ids.
                'catalog' => $this->skillRefList(array_merge($gapSkills, $selectedSkills)),
            ],
            'completed_courses' => $included('completed_courses') ? $completedCourses : [],
            'current_courses' => $included('current_courses') ? $currentCourses : [],
            'quiz_performance' => $quizPerformance,
            'program_assessments' => $programAssessments,
            'warnings' => array_values(array_unique($warnings)),
            'limited_evidence' => $source !== 'live',
        ];

        $evidence = [
            [
                'key' => 'external_skills',
                'label' => 'Employee profile skills',
                'count' => count($externalSkills),
                'included' => $included('external_skills'),
            ],
            [
                'key' => 'completed_courses',
                'label' => 'Completed courses',
                'count' => count($completedCourses),
                'included' => $included('completed_courses'),
            ],
            [
                'key' => 'current_courses',
                'label' => 'Current enrollments',
                'count' => count($currentCourses),
                'included' => $included('current_courses'),
            ],
            [
                'key' => 'quiz_performance',
                'label' => 'Quiz and evaluation performance',
                'count' => (int) ($quizPerformance['summary']['attempts'] ?? 0),
                'included' => $included('quiz_performance'),
            ],
            [
                'key' => 'program_assessments',
                'label' => 'Program assessments',
                'count' => (int) ($programAssessments['summary']['count'] ?? 0),
                'included' => $included('program_assessments'),
            ],
            [
                'key' => 'snapshot',
                'label' => 'Skill snapshot fallback',
                'count' => $snapshotUsed ? 1 : 0,
                'included' => $included('snapshot'),
            ],
        ];

        return [
            'success' => true,
            'message' => 'Context preview ready.',
            'context' => $context,
            'meta' => [
                'trainee' => [
                    'id' => (int) $employee['id'],
                    'name' => $employee['name'],
                    'department' => $employee['department'],
                    'position' => $employee['position'],
                    'hire_date' => $employee['hire_date'],
                    'employment_status' => $employee['employment_status'],
                    'profile_url' => LearningProfileLink::url((int) $employee['id'], 'learner'),
                ],
                'evidence' => $evidence,
                'excluded' => $excluded,
                'skill_options' => array_map(
                    static fn(array $skill): array => [
                        'id' => $skill['id'] !== null ? (int) $skill['id'] : null,
                        'name' => (string) $skill['name'],
                    ],
                    $gapSkills
                ),
                'counts' => [
                    'enrollments' => count($includedEnrollments),
                    'completed_courses' => count($completedCourses),
                    'current_courses' => count($currentCourses),
                    'quiz_sessions' => (int) ($quizPerformance['summary']['attempts'] ?? 0),
                    'program_assessments' => (int) ($programAssessments['summary']['count'] ?? 0),
                    'skills_acquired' => count($this->skillNameList($acquiredSkills)),
                    'skill_gaps' => count($gapSkills),
                ],
                'generated_at' => date('c'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadEmployee(int $employeeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.employee_id, e.first_name, e.last_name, e.hire_date,
                    e.employment_status, e.is_archived,
                    d.department_name, p.position_name
               FROM em_employees e
               LEFT JOIN em_departments d ON d.department_id = e.department_id
               LEFT JOIN em_positions p ON p.position_id = e.position_id
              WHERE e.employee_id = :employee_id
              LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['employee_id'],
            'name' => trim((string) $row['first_name'] . ' ' . (string) $row['last_name']),
            'department' => (string) ($row['department_name'] ?? ''),
            'position' => (string) ($row['position_name'] ?? ''),
            'hire_date' => $row['hire_date'] !== null ? (string) $row['hire_date'] : null,
            'employment_status' => (string) ($row['employment_status'] ?? ''),
            'is_archived' => (int) ($row['is_archived'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadEnrollments(int $employeeId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.course_id, e.status, e.enrolled_at, e.completed_at,
                    e.last_accessed_at, c.title AS course_title
               FROM ld_enrollment e
               JOIN ld_course c ON c.id = e.course_id
              WHERE e.learner_id = :learner_id
                AND e.status <> 'withdrawn'
              ORDER BY CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END,
                       e.completed_at DESC, e.id DESC"
        );
        $stmt->execute([':learner_id' => $employeeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function loadActiveSkills(): array
    {
        $stmt = $this->pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name ASC");
        $skills = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $skills[] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return $skills;
    }

    /**
     * @param int[] $enrollmentIds
     * @return array<int, array<string, mixed>>
     */
    private function loadCourseSkills(int $employeeId, array $enrollmentIds): array
    {
        if ($enrollmentIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [':learner_id' => $employeeId];
        foreach (array_values($enrollmentIds) as $index => $enrollmentId) {
            $placeholder = ':enrollment_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (int) $enrollmentId;
        }

        $sql = "SELECT e.id AS enrollment_id, e.status AS enrollment_status,
                       s.id AS skill_id, s.name AS skill_name
                  FROM ld_enrollment e
                  JOIN ld_course_skill cs ON cs.course_id = e.course_id
                  JOIN ld_skill s ON s.id = cs.skill_id AND s.status = 'active'
                 WHERE e.learner_id = :learner_id
                   AND e.id IN (" . implode(', ', $placeholders) . ")
                 ORDER BY e.id ASC, s.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param int[] $enrollmentIds
     * @return array<int, array<string, mixed>>
     */
    private function loadModuleSkills(int $employeeId, array $enrollmentIds): array
    {
        if ($enrollmentIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [':learner_id' => $employeeId];
        foreach (array_values($enrollmentIds) as $index => $enrollmentId) {
            $placeholder = ':enrollment_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (int) $enrollmentId;
        }

        $moduleCompletedSql = Progress::sqlModuleCompleted('m', 'e.id');
        $sql = "SELECT e.id AS enrollment_id, e.status AS enrollment_status,
                       m.id AS module_id, m.title AS module_title,
                       s.id AS skill_id, s.name AS skill_name,
                       (" . $moduleCompletedSql . ") AS module_complete
                  FROM ld_enrollment e
                  JOIN ld_module m ON m.course_id = e.course_id AND m.status = 'active'
                  JOIN ld_module_skill ms ON ms.module_id = m.id
                  JOIN ld_skill s ON s.id = ms.skill_id AND s.status = 'active'
                 WHERE e.learner_id = :learner_id
                   AND e.id IN (" . implode(', ', $placeholders) . ")
                 ORDER BY e.id ASC, m.order_index ASC, m.id ASC, s.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array{skill_id: ?int, name: string, proficiency: string}>
     */
    private function loadExternalSkills(int $employeeId): array
    {
        if ($this->externalSkillsTable === '') {
            return [];
        }

        $sql = "SELECT es.skill_name, es.proficiency,
                       s.id AS skill_id, s.name AS matched_name
                  FROM " . $this->externalSkillsTable . " es
                  LEFT JOIN ld_skill s ON s.id = (
                       SELECT MIN(s2.id)
                         FROM ld_skill s2
                        WHERE s2.status = 'active'
                          AND LOWER(s2.name) = LOWER(es.skill_name)
                  )
                 WHERE es.employee_id = :employee_id
                 ORDER BY es.skill_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);

        $skills = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $skills[] = [
                'skill_id' => $row['skill_id'] !== null ? (int) $row['skill_id'] : null,
                'name' => (string) $row['skill_name'],
                'proficiency' => (string) ($row['proficiency'] ?? ''),
            ];
        }

        return $skills;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadQuizPerformance(int $employeeId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT qs.id, qs.item_type, qs.status, qs.score, qs.passed, qs.submitted_at,
                    CASE WHEN qs.item_type = 'quiz' THEN q.title ELSE ev.title END AS title,
                    CASE WHEN qs.item_type = 'quiz' THEN c.title ELSE c2.title END AS course_title
               FROM ld_quiz_session qs
               LEFT JOIN ld_quiz q ON qs.item_type = 'quiz' AND q.id = qs.reference_id
               LEFT JOIN ld_module m ON q.module_id = m.id
               LEFT JOIN ld_course c ON m.course_id = c.id
               LEFT JOIN ld_evaluation ev ON qs.item_type = 'evaluation' AND ev.id = qs.reference_id
               LEFT JOIN ld_course c2 ON ev.course_id = c2.id
              WHERE qs.learner_id = :learner_id
                AND qs.status IN ('submitted', 'expired')
              ORDER BY qs.submitted_at DESC, qs.id DESC
              LIMIT 20"
        );
        $stmt->execute([':learner_id' => $employeeId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $submitted = 0;
        $expired = 0;
        $passed = 0;
        $scores = [];
        $recent = [];
        foreach ($sessions as $session) {
            if ($session['status'] === 'submitted') {
                $submitted++;
                if ($session['score'] !== null) {
                    $scores[] = (float) $session['score'];
                }
            } else {
                $expired++;
            }
            if ((int) ($session['passed'] ?? 0) === 1) {
                $passed++;
            }
            if (count($recent) < 5) {
                $recent[] = [
                    'type' => (string) $session['item_type'],
                    'title' => (string) ($session['title'] ?? 'Untitled'),
                    'course_title' => (string) ($session['course_title'] ?? ''),
                    'score' => $session['score'] !== null ? (float) $session['score'] : null,
                    'passed' => $session['passed'] !== null ? (bool) $session['passed'] : null,
                    'status' => (string) $session['status'],
                    'submitted_at' => $session['submitted_at'] !== null ? (string) $session['submitted_at'] : null,
                ];
            }
        }

        $quizAttempts = ['count' => 0, 'average_score' => null, 'passed' => 0];
        try {
            $attemptStmt = $this->pdo->prepare(
                "SELECT COUNT(*) AS attempt_count,
                        AVG(score) AS average_score,
                        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) AS passed_count
                   FROM ld_quiz_attempt
                  WHERE learner_id = :learner_id"
            );
            $attemptStmt->execute([':learner_id' => $employeeId]);
            $attemptRow = $attemptStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $quizAttempts = [
                'count' => (int) ($attemptRow['attempt_count'] ?? 0),
                'average_score' => $attemptRow['average_score'] !== null ? round((float) $attemptRow['average_score'], 1) : null,
                'passed' => (int) ($attemptRow['passed_count'] ?? 0),
            ];
        } catch (Throwable $e) {
            // Quiz attempts are supplementary; sessions remain the primary evidence.
        }

        return [
            'available' => true,
            'summary' => [
                'attempts' => count($sessions),
                'submitted' => $submitted,
                'expired' => $expired,
                'passed' => $passed,
                'average_score' => $scores !== [] ? round(array_sum($scores) / count($scores), 1) : null,
            ],
            'quiz_attempts' => $quizAttempts,
            'recent' => $recent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadProgramAssessments(int $employeeId): array
    {
        if (!$this->programAssessmentsAvailable) {
            return [
                'available' => false,
                'summary' => ['count' => 0, 'passed' => 0, 'failed' => 0, 'average_overall' => null],
                'recent' => [],
            ];
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT pa.program_id, p.title AS program_title, pa.attendance,
                        pa.knowledge_score, pa.skills_score, pa.participation_score,
                        pa.overall_score, pa.result, pa.assessed_at
                   FROM ld_program_assessment pa
                   JOIN ld_program p ON p.id = pa.program_id
                  WHERE pa.employee_id = :employee_id
                    AND pa.status = 'completed'
                  ORDER BY pa.assessed_at DESC, pa.id DESC
                  LIMIT 10"
            );
            $stmt->execute([':employee_id' => $employeeId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'summary' => ['count' => 0, 'passed' => 0, 'failed' => 0, 'average_overall' => null],
                'recent' => [],
            ];
        }

        $passed = 0;
        $failed = 0;
        $scores = [];
        $recent = [];
        foreach ($rows as $row) {
            if ($row['result'] === 'passed') {
                $passed++;
            } elseif ($row['result'] === 'failed') {
                $failed++;
            }
            if ($row['overall_score'] !== null) {
                $scores[] = (float) $row['overall_score'];
            }
            if (count($recent) < 5) {
                $recent[] = [
                    'program_title' => (string) ($row['program_title'] ?? ''),
                    'attendance' => (string) ($row['attendance'] ?? ''),
                    'knowledge_score' => $row['knowledge_score'] !== null ? (int) $row['knowledge_score'] : null,
                    'skills_score' => $row['skills_score'] !== null ? (int) $row['skills_score'] : null,
                    'participation_score' => $row['participation_score'] !== null ? (int) $row['participation_score'] : null,
                    'overall_score' => $row['overall_score'] !== null ? (float) $row['overall_score'] : null,
                    'result' => (string) ($row['result'] ?? ''),
                    'assessed_at' => $row['assessed_at'] !== null ? (string) $row['assessed_at'] : null,
                ];
            }
        }

        return [
            'available' => true,
            'summary' => [
                'count' => count($rows),
                'passed' => $passed,
                'failed' => $failed,
                'average_overall' => $scores !== [] ? round(array_sum($scores) / count($scores), 2) : null,
            ],
            'recent' => $recent,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadSnapshot(int $employeeId): ?array
    {
        if (!$this->snapshotAvailable) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT snapshot_date, total_skills, acquired_count, gap_count,
                    acquired_skills, gap_skills
               FROM ld_skill_snapshot
              WHERE learner_id = :learner_id
              ORDER BY snapshot_date DESC, id DESC
              LIMIT 1"
        );
        $stmt->execute([':learner_id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $snapshotDate = (string) $row['snapshot_date'];
        $stale = strtotime($snapshotDate) < strtotime('-30 days');

        return [
            'snapshot_date' => $snapshotDate,
            'total_skills' => (int) ($row['total_skills'] ?? 0),
            'acquired_count' => (int) ($row['acquired_count'] ?? 0),
            'gap_count' => (int) ($row['gap_count'] ?? 0),
            'acquired_skills' => $this->decodeSkillList($row['acquired_skills'] ?? null),
            'gap_skills' => $this->decodeSkillList($row['gap_skills'] ?? null),
            'stale' => $stale,
        ];
    }

    /**
     * @param string[] $names
     * @param array<string, array{id: int, name: string}> $activeByName
     * @return array<int, array{id: ?int, name: string}>
     */
    private function mapSnapshotSkills(array $names, array $activeByName): array
    {
        $skills = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $matched = $activeByName[mb_strtolower($name)] ?? null;
            $skills[] = [
                'id' => $matched !== null ? (int) $matched['id'] : null,
                'name' => $matched !== null ? (string) $matched['name'] : $name,
            ];
        }

        return $this->uniqueSkills($skills);
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function decodeSkillList(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        $names = [];
        foreach ($decoded as $name) {
            if (is_string($name) && trim($name) !== '') {
                $names[] = trim($name);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param int[] $selectedSkillIds
     * @param array<int, array{id: int, name: string}> $activeById
     * @param array<int, array{id: ?int, name: string}> $gapSkills
     * @param string[] $warnings
     * @return array<int, array{id: int, name: string}>
     */
    private function resolveSelectedSkills(
        array $selectedSkillIds,
        array $activeById,
        array $gapSkills,
        array &$warnings
    ): array {
        $requested = [];
        foreach ($selectedSkillIds as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $requested[(int) $value] = true;
            }
        }

        $gapNames = [];
        foreach ($gapSkills as $gap) {
            $gapNames[mb_strtolower(trim((string) $gap['name']))] = true;
        }

        $selected = [];
        $invalid = [];
        foreach (array_keys($requested) as $skillId) {
            if (!isset($activeById[$skillId])) {
                $invalid[] = (string) $skillId;
                continue;
            }
            $skill = [
                'id' => (int) $activeById[$skillId]['id'],
                'name' => (string) $activeById[$skillId]['name'],
            ];
            $selected[] = $skill;
            if (!isset($gapNames[mb_strtolower(trim($skill['name']))])) {
                $warnings[] = 'Selected skill "' . $skill['name']
                    . '" is not a current gap; it is included as an additional focus area.';
            }
        }

        if ($invalid !== []) {
            $warnings[] = 'Selected skill ids that are not active were ignored: ' . implode(', ', $invalid) . '.';
        }

        return $this->uniqueSkills($selected);
    }

    /**
     * @param array<string, mixed> $input
     * @param string[] $warnings
     * @return string[]
     */
    private function normalizeExclusions(array $input, array &$warnings): array
    {
        $allowed = [
            'external_skills',
            'completed_courses',
            'current_courses',
            'quiz_performance',
            'program_assessments',
            'snapshot',
        ];
        $excluded = [];
        foreach ($input as $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = trim($value);
            if (in_array($value, $allowed, true)) {
                $excluded[$value] = true;
            } elseif ($value !== '') {
                $warnings[] = 'Unknown evidence exclusion "' . $value . '" was ignored.';
            }
        }

        return array_keys($excluded);
    }

    /**
     * Subtract acquired skills by normalized name. Duplicate catalog rows for
     * the same skill must not produce duplicate gaps or inflate gap counts.
     *
     * @param array<string, array{id: ?int, name: string, source: string}> $allSkills
     * @param array<string, array{id: ?int, name: string, source: string}> $acquiredSkills
     * @return array<int, array{id: ?int, name: string}>
     */
    private function subtractSkillsByName(array $allSkills, array $acquiredSkills): array
    {
        $acquiredNames = [];
        foreach ($acquiredSkills as $skill) {
            $acquiredNames[mb_strtolower(trim((string) $skill['name']))] = true;
        }

        $gaps = [];
        foreach ($allSkills as $skill) {
            $name = trim((string) $skill['name']);
            $nameKey = mb_strtolower($name);
            if ($name === '' || isset($acquiredNames[$nameKey]) || isset($gaps[$nameKey])) {
                continue;
            }
            $gaps[$nameKey] = [
                'id' => $skill['id'] !== null ? (int) $skill['id'] : null,
                'name' => $name,
            ];
        }

        return array_values($gaps);
    }

    /**
     * @param array<string, array{id: ?int, name: string, source: string}> $map
     */
    private function addSkill(array &$map, ?int $id, string $name, string $source): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }
        $key = $this->skillKey($id, $name);
        if (!isset($map[$key])) {
            $map[$key] = ['id' => $id, 'name' => $name, 'source' => $source];
        }
    }

    /**
     * @param array<int, array<int, array{id: ?int, name: string}>> $enrollmentSkills
     */
    private function addEnrollmentSkill(array &$enrollmentSkills, int $enrollmentId, ?int $skillId, string $name): void
    {
        $enrollmentSkills[$enrollmentId][$this->skillKey($skillId, $name)] = [
            'id' => $skillId,
            'name' => $name,
        ];
    }

    private function skillKey(?int $id, string $name): string
    {
        return $id !== null ? 'id:' . $id : 'name:' . mb_strtolower(trim($name));
    }

    /**
     * @param array<int, array{id: ?int, name: string}> $skills
     * @return string[]
     */
    private function skillNameList(array $skills): array
    {
        $names = [];
        foreach ($skills as $skill) {
            if (isset($skill['name']) && trim((string) $skill['name']) !== '') {
                $names[] = trim((string) $skill['name']);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param array<int, array{id: ?int, name: string}> $skills
     * @return array<int, array{skill_id: int, name: string}>
     */
    private function skillRefList(array $skills): array
    {
        $refs = [];
        foreach ($skills as $skill) {
            $id = isset($skill['id']) && $skill['id'] !== null ? (int) $skill['id'] : 0;
            $name = trim((string) ($skill['name'] ?? ''));
            if ($id <= 0 || $name === '') {
                continue;
            }
            $refs[$id] = ['skill_id' => $id, 'name' => $name];
        }

        $refs = array_values($refs);
        usort(
            $refs,
            static fn(array $left, array $right): int => strcasecmp($left['name'], $right['name'])
        );

        return array_slice($refs, 0, 40);
    }

    /**
     * @param array<int, array{id: ?int, name: string}> $skills
     * @return array<int, array{id: ?int, name: string}>
     */
    private function uniqueSkills(array $skills): array
    {
        $unique = [];
        foreach ($skills as $skill) {
            $name = trim((string) ($skill['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $id = isset($skill['id']) && $skill['id'] !== null ? (int) $skill['id'] : null;
            $unique[mb_strtolower($name)] = ['id' => $id, 'name' => $name];
        }

        return array_values($unique);
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
               FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = :table_name
              LIMIT 1'
        );
        $stmt->execute([':table_name' => $table]);

        return (bool) $stmt->fetchColumn();
    }
}
