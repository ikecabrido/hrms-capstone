<?php
/**
 * Outbound: get-workforce-analytics.php
 * Returns aggregated learning analytics for Workforce Analytics (wfa_skill_gap_analysis, wfa_reports).
 *
 * GET /api/outbound/get-workforce-analytics.php
 * Header: X-API-Key: <key>
 * Params: department_id (optional), date_from, date_to
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'learning-development');

    // Overall enrollment stats
    $stmt = $pdo->query("
        SELECT
            status,
            COUNT(*) AS cnt
        FROM ld_enrollment
        GROUP BY status
    ");
    $enrollmentStats = [];
    while ($row = $stmt->fetch()) {
        $enrollmentStats[$row['status']] = (int)$row['cnt'];
    }

    // Total enrolled learners
    $totalLearners = array_sum($enrollmentStats);

    // Course popularity (top 10)
    $stmt = $pdo->query("
        SELECT c.id, c.title, c.category, COUNT(e.id) AS enrollment_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completions
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        GROUP BY c.id, c.title, c.category
        ORDER BY enrollment_count DESC
        LIMIT 10
    ");
    $topCourses = $stmt->fetchAll();

    // Skill gap analysis — skills with low completion rates
    $stmt = $pdo->prepare("
        SELECT s.id, s.name,
               COUNT(DISTINCT cs.course_id) AS courses_with_skill,
               COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.learner_id END) AS learners_completed,
               COUNT(DISTINCT e.learner_id) AS total_enrolled
        FROM ld_skill s
        JOIN ld_course_skill cs ON cs.skill_id = s.id
        LEFT JOIN ld_enrollment e ON e.course_id = cs.course_id
        GROUP BY s.id, s.name
        ORDER BY learners_completed ASC
    ");
    $stmt->execute();
    $skillGaps = $stmt->fetchAll();

    $formattedGaps = array_map(function ($row) {
        $total = (int)$row['total_enrolled'];
        $completed = (int)$row['learners_completed'];
        $gapPct = $total > 0 ? round((($total - $completed) / $total) * 100, 2) : 0;
        return [
            'skill_name'             => $row['name'],
            'courses_with_skill'     => (int)$row['courses_with_skill'],
            'employees_with_skill'   => $completed,
            'employees_needing_training' => $total - $completed,
            'skill_gap_percentage'   => $gapPct,
            'priority_level'         => $gapPct > 70 ? 'critical' : ($gapPct > 50 ? 'high' : ($gapPct > 30 ? 'medium' : 'low')),
        ];
    }, $skillGaps);

    // Completion rate trend
    $stmt = $pdo->query("
        SELECT
            DATE_FORMAT(enrolled_at, '%Y-%m') AS month,
            COUNT(*) AS enrollments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completions
        FROM ld_enrollment
        WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $trend = $stmt->fetchAll();

    // Certificate stats
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM ld_certificate");
    $totalCerts = (int)$stmt->fetch()['total'];

    $payload = [
        'total_learners'      => $totalLearners,
        'enrollment_stats'    => $enrollmentStats,
        'total_certificates'  => $totalCerts,
        'top_courses'         => $topCourses,
        'skill_gap_analysis'  => $formattedGaps,
        'completion_trend'    => $trend,
    ];

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-workforce-analytics', 'success', [
        'filters' => $_GET,
    ]);

    echo json_encode(['success' => true, 'data' => $payload]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-workforce-analytics', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
