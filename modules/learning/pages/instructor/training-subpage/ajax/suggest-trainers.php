<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/LearningRole.php';
require_once dirname(__FILE__, 5) . '/classes/ProfileLink.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    $employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    if ($employeeId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $role = LearningRole::forEmployee($pdo, $employeeId);
    if (!in_array($role, ['admin', 'instructor'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only trainers and administrators can search trainers.']);
        exit;
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }

    $skillInput = $_GET['skill_ids'] ?? [];
    if (!is_array($skillInput)) {
        $skillInput = explode(',', (string) $skillInput);
    }
    $skillIds = [];
    foreach ($skillInput as $skillId) {
        $skillId = (int) $skillId;
        if ($skillId > 0) {
            $skillIds[$skillId] = $skillId;
        }
    }
    $skillIds = array_values($skillIds);

    $tableExists = static function (string $table) use ($pdo): bool {
        try {
            $check = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
            $check->execute([':table' => $table]);
            return (bool) $check->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    };

    $skillEvidence = [
        "SELECT c.instructor_id AS employee_id, cs.skill_id, 'teaches' AS evidence_type, 0 AS proficiency_score
         FROM ld_course c
         JOIN ld_course_skill cs ON cs.course_id = c.id
         WHERE c.instructor_id IS NOT NULL",
        "SELECT ci.instructor_id AS employee_id, cs.skill_id, 'co_teaches' AS evidence_type, 0 AS proficiency_score
         FROM ld_course_instructor ci
         JOIN ld_course_skill cs ON cs.course_id = ci.course_id",
        "SELECT en.learner_id AS employee_id, cs.skill_id, 'completed' AS evidence_type, 0 AS proficiency_score
         FROM ld_enrollment en
         JOIN ld_course_skill cs ON cs.course_id = en.course_id
         WHERE en.status = 'completed'",
    ];

    if ($tableExists('ld_program_trainer')) {
        $skillEvidence[] = "SELECT pt.trainer_id AS employee_id, ps.skill_id, 'program' AS evidence_type, 0 AS proficiency_score
            FROM ld_program_trainer pt
            JOIN ld_program_skill ps ON ps.program_id = pt.program_id";
    }
    if ($tableExists('employee_skills')) {
        $skillEvidence[] = "SELECT es.employee_id, s.id AS skill_id, 'external' AS evidence_type,
                MAX(CASE LOWER(COALESCE(es.proficiency, 'intermediate'))
                    WHEN 'expert' THEN 5
                    WHEN 'advanced' THEN 4
                    WHEN 'intermediate' THEN 3
                    WHEN 'beginner' THEN 1
                    ELSE 2
                END) AS proficiency_score
            FROM employee_skills es
            JOIN ld_skill s ON LOWER(TRIM(s.name)) = LOWER(TRIM(es.skill_name))
            GROUP BY es.employee_id, s.id";
    }

    $where = ["e.employment_status = 'Active'", 'COALESCE(e.is_archived, 0) = 0'];
    $params = [];

    if ($skillIds) {
        $where[] = 'ev.skill_id IN (' . implode(',', array_map('intval', $skillIds)) . ')';
    }

    if ($q !== '') {
        $where[] = "(e.first_name LIKE :q1 OR e.last_name LIKE :q2
            OR CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) LIKE :q3
            OR e.email LIKE :q4 OR d.department_name LIKE :q5 OR p.position_name LIKE :q6)";
        $like = '%' . $q . '%';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
        $params[':q5'] = $like;
        $params[':q6'] = $like;
    }

    $sql = "SELECT e.employee_id, e.email,
                   CONCAT_WS(' ', e.first_name, NULLIF(e.middle_name, ''), e.last_name) AS full_name,
                   d.department_name, p.position_name,
                   COALESCE(SUM(CASE ev.evidence_type
                       WHEN 'external' THEN ev.proficiency_score
                       WHEN 'teaches' THEN 3
                       WHEN 'co_teaches' THEN 2
                       WHEN 'program' THEN 2
                       WHEN 'completed' THEN 1
                       ELSE 0 END), 0) AS score,
                   COUNT(DISTINCT ev.skill_id) AS matched_skill_count,
                   GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS matched_skills,
                   CASE WHEN i.employee_id IS NOT NULL OR ur.learning_role = 'instructor' THEN 1 ELSE 0 END AS is_trainer
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            LEFT JOIN ld_instructor i ON i.employee_id = e.employee_id
            LEFT JOIN ld_user_role ur ON ur.employee_id = e.employee_id
            LEFT JOIN (
                " . implode("\n                UNION ALL\n                ", $skillEvidence) . "
            ) ev ON ev.employee_id = e.employee_id
            LEFT JOIN ld_skill s ON s.id = ev.skill_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY e.employee_id, e.email, e.first_name, e.middle_name, e.last_name,
                     d.department_name, p.position_name, i.employee_id, ur.learning_role
            ORDER BY score DESC, matched_skill_count DESC, is_trainer DESC,
                     e.last_name ASC, e.first_name ASC
            LIMIT 30";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trainers as &$trainer) {
        $trainer['employee_id'] = (int) $trainer['employee_id'];
        $trainer['score'] = (int) $trainer['score'];
        $trainer['matched_skill_count'] = (int) $trainer['matched_skill_count'];
        $trainer['is_trainer'] = (int) $trainer['is_trainer'] === 1;
        $trainer['matched_skills'] = trim((string) ($trainer['matched_skills'] ?? ''));
        $trainer['profile_url'] = LearningProfileLink::url($trainer['employee_id'], 'instructor');
    }
    unset($trainer);

    echo json_encode([
        'success' => true,
        'trainers' => $trainers,
        'selected_skill_ids' => $skillIds,
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to search trainers.',
        'error' => $e->getMessage(),
    ]);
    exit;
}
