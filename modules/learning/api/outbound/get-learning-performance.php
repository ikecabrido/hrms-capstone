<?php
/**
 * Outbound: get-learning-performance.php
 * Returns learner grades, quiz scores, and skill proficiency for Performance Management.
 *
 * GET /api/outbound/get-learning-performance.php
 * Header: X-API-Key: <key>
 * Params: learner_id (optional), course_id (optional)
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'learning-development');

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($_GET['learner_id'])) {
        $where .= " AND qa.learner_id = :lid";
        $params[':lid'] = (int)$_GET['learner_id'];
    }
    if (!empty($_GET['course_id'])) {
        $where .= " AND c.id = :cid";
        $params[':cid'] = (int)$_GET['course_id'];
    }

    // Quiz performance
    $stmt = $pdo->prepare("
        SELECT
            qa.learner_id AS employee_id,
            c.id AS course_id,
            c.title AS course_title,
            q.title AS quiz_title,
            qa.score,
            qa.passed,
            qa.submitted_at,
            m.title AS module_title
        FROM ld_quiz_attempt qa
        JOIN ld_quiz q ON q.id = qa.quiz_id
        JOIN ld_module m ON m.id = q.module_id
        JOIN ld_course c ON c.id = m.course_id
        $where
        ORDER BY qa.submitted_at DESC
    ");
    $stmt->execute($params);
    $quizScores = $stmt->fetchAll();

    // Aggregate by employee
    $aggregated = [];
    foreach ($quizScores as $row) {
        $eid = (int)$row['employee_id'];
        if (!isset($aggregated[$eid])) {
            $aggregated[$eid] = [
                'employee_id' => $eid,
                'total_quizzes' => 0,
                'passed_quizzes' => 0,
                'avg_score' => 0,
                'scores' => [],
                'courses' => [],
            ];
        }
        $aggregated[$eid]['total_quizzes']++;
        if ($row['passed']) $aggregated[$eid]['passed_quizzes']++;
        $aggregated[$eid]['scores'][] = (float)$row['score'];
        if (!in_array($row['course_title'], $aggregated[$eid]['courses'])) {
            $aggregated[$eid]['courses'][] = $row['course_title'];
        }
    }

    foreach ($aggregated as &$a) {
        $a['avg_score'] = count($a['scores']) > 0
            ? round(array_sum($a['scores']) / count($a['scores']), 2)
            : 0;
        unset($a['scores']);
    }
    unset($a);

    // Skill proficiency per learner
    $skillWhere = str_replace('qa.learner_id', 'e.learner_id', $where);
    $stmt2 = $pdo->prepare("
        SELECT
            e.learner_id AS employee_id,
            s.name AS skill_name,
            COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.course_id END) AS courses_completed,
            COUNT(DISTINCT cs.course_id) AS total_courses_with_skill
        FROM ld_enrollment e
        JOIN ld_course_skill cs ON cs.course_id = e.course_id
        JOIN ld_skill s ON s.id = cs.skill_id
        $skillWhere
        GROUP BY e.learner_id, s.name
    ");
    $stmt2->execute($params);
    $skillData = $stmt2->fetchAll();

    $skillProficiency = [];
    foreach ($skillData as $row) {
        $eid = (int)$row['employee_id'];
        if (!isset($skillProficiency[$eid])) {
            $skillProficiency[$eid] = ['employee_id' => $eid, 'skills' => []];
        }
        $proficiency = $row['total_courses_with_skill'] > 0
            ? round(($row['courses_completed'] / $row['total_courses_with_skill']) * 100, 1)
            : 0;
        $skillProficiency[$eid]['skills'][] = [
            'skill_name' => $row['skill_name'],
            'proficiency' => $proficiency,
            'courses_completed' => (int)$row['courses_completed'],
            'total_courses' => (int)$row['total_courses_with_skill'],
        ];
    }

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-learning-performance', 'success', [
        'count' => count($aggregated),
        'filters' => $_GET,
    ]);

    echo json_encode([
        'success' => true,
        'quiz_performance' => array_values($aggregated),
        'skill_proficiency' => array_values($skillProficiency),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-learning-performance', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
