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

    // Certification expiry (next 90 days) — feeds wfa_reports
    $stmt = $pdo->query("
        SELECT c.learner_id, c.issued_at, c.valid_until, co.title AS course_title
        FROM ld_certificate c
        JOIN ld_course co ON co.id = c.course_id
        WHERE c.status = 'active' AND c.valid_until IS NOT NULL
          AND c.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY c.valid_until ASC
    ");
    $expiringCertificates = $stmt->fetchAll();

    // Latest engagement/risk snapshot — feeds wfa_risk_assessment / wfa_at_risk_employees_summary
    $riskSummary = ['snapshot_date' => null, 'bands' => [], 'at_risk_learners' => []];
    $stmt = $pdo->query("SELECT MAX(snapshot_date) AS d FROM ld_engagement_snapshot");
    $riskDate = $stmt->fetch()['d'];
    if ($riskDate) {
        $stmt = $pdo->prepare("
            SELECT CASE
                        WHEN es.risk_score >= 70 THEN 'high'
                        WHEN es.risk_score >= 40 THEN 'medium'
                        ELSE 'low'
                    END AS band,
                    COUNT(*) AS employee_count,
                    ROUND(AVG(es.risk_score), 2) AS avg_risk_score
            FROM ld_engagement_snapshot es
            WHERE es.snapshot_date = :d
            GROUP BY band
            ORDER BY band
        ");
        $stmt->execute([':d' => $riskDate]);
        $bands = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT es.learner_id, es.risk_score, es.risk_factors,
                   es.active_days_30, es.days_since_last_activity, es.expired_certificates
            FROM ld_engagement_snapshot es
            WHERE es.snapshot_date = :d AND es.risk_score >= 40
            ORDER BY es.risk_score DESC
        ");
        $stmt->execute([':d' => $riskDate]);
        $atRisk = $stmt->fetchAll();

        $riskSummary = [
            'snapshot_date' => $riskDate,
            'bands' => $bands,
            'at_risk_learners' => $atRisk,
        ];
    }

    // Latest skill snapshots (per-learner, with position/department) — feeds wfa_skill_gap_analysis
    $skillSnapshots = [];
    $stmt = $pdo->query("SELECT MAX(snapshot_date) AS d FROM ld_skill_snapshot");
    $skillDate = $stmt->fetch()['d'];
    if ($skillDate) {
        $stmt = $pdo->prepare("
            SELECT ss.learner_id, ss.position_id, ss.department_id,
                   d.department_name, p.position_name,
                   ss.total_skills, ss.acquired_count, ss.gap_count,
                   ss.acquired_skills, ss.gap_skills, ss.snapshot_date
            FROM ld_skill_snapshot ss
            LEFT JOIN em_departments d ON d.department_id = ss.department_id
            LEFT JOIN em_positions p ON p.position_id = ss.position_id
            WHERE ss.snapshot_date = :d
            ORDER BY ss.gap_count DESC
        ");
        $stmt->execute([':d' => $skillDate]);
        $skillSnapshots = $stmt->fetchAll();
    }

    // Knowledge-transfer learning paths (retention-relevant metric)
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_paths,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_paths
        FROM ld_learning_path
        WHERE type = 'knowledge_transfer' OR kt_plan_id IS NOT NULL
    ");
    $ktPaths = $stmt->fetch();

    $payload = [
        'total_learners'      => $totalLearners,
        'enrollment_stats'    => $enrollmentStats,
        'total_certificates'  => $totalCerts,
        'top_courses'         => $topCourses,
        'skill_gap_analysis'  => $formattedGaps,
        'skill_snapshots'     => $skillSnapshots,
        'completion_trend'    => $trend,
        'expiring_certificates' => $expiringCertificates,
        'risk_summary'        => $riskSummary,
        'knowledge_transfer'  => [
            'total_paths'  => (int)($ktPaths['total_paths'] ?? 0),
            'active_paths' => (int)($ktPaths['active_paths'] ?? 0),
        ],
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
