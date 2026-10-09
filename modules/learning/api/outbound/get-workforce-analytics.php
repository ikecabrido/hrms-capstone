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

    ApiAuth::requireAuth($pdo, 'workforce-analytics');

    $departmentId = null;
    if (isset($_GET['department_id']) && $_GET['department_id'] !== '') {
        $departmentId = filter_var($_GET['department_id'], FILTER_VALIDATE_INT);
        if ($departmentId === false || $departmentId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'department_id must be a positive integer']);
            exit;
        }
    }

    $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
    $dateTo = trim((string) ($_GET['date_to'] ?? ''));
    foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $name => $value) {
        if ($value !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $name . ' must use YYYY-MM-DD']);
                exit;
            }
        }
    }
    if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'date_from must be on or before date_to']);
        exit;
    }

    $buildFilters = static function (string $dateColumn, string $employeeAlias, string $prefix) use ($departmentId, $dateFrom, $dateTo): array {
        $conditions = ['1 = 1'];
        $params = [];
        if ($departmentId !== null) {
            $conditions[] = $employeeAlias . '.department_id = :' . $prefix . '_department_id';
            $params[':' . $prefix . '_department_id'] = $departmentId;
        }
        if ($dateFrom !== '') {
            $conditions[] = $dateColumn . ' >= :' . $prefix . '_date_from';
            $params[':' . $prefix . '_date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $conditions[] = $dateColumn . ' < DATE_ADD(:' . $prefix . '_date_to, INTERVAL 1 DAY)';
            $params[':' . $prefix . '_date_to'] = $dateTo;
        }
        return ['sql' => implode(' AND ', $conditions), 'params' => $params];
    };

    // Overall enrollment stats
    $enrollmentFilters = $buildFilters('e.enrolled_at', 'emp', 'enrollment');
    $stmt = $pdo->prepare("
        SELECT
            status,
            COUNT(*) AS cnt
        FROM ld_enrollment e
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE {$enrollmentFilters['sql']}
        GROUP BY status
    ");
    $stmt->execute($enrollmentFilters['params']);
    $enrollmentStats = [];
    while ($row = $stmt->fetch()) {
        $enrollmentStats[$row['status']] = (int)$row['cnt'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id)
        FROM ld_enrollment e
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE {$enrollmentFilters['sql']}");
    $stmt->execute($enrollmentFilters['params']);
    $totalLearners = (int) $stmt->fetchColumn();

    // Course popularity (top 10)
    $courseFilters = $buildFilters('e.enrolled_at', 'emp', 'course');
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.category, COUNT(DISTINCT e.id) AS enrollment_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completions
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE {$courseFilters['sql']}
        GROUP BY c.id, c.title, c.category
        ORDER BY enrollment_count DESC
        LIMIT 10
    ");
    $stmt->execute($courseFilters['params']);
    $topCourses = $stmt->fetchAll();

    // Skill gap analysis — skills with low completion rates
    $skillFilters = $buildFilters('e.enrolled_at', 'emp', 'skill');
    $trendRollingWindow = ($dateFrom === '' && $dateTo === '')
        ? 'AND e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)'
        : '';
    $stmt = $pdo->prepare("
        SELECT s.id, s.name,
               COUNT(DISTINCT cs.course_id) AS courses_with_skill,
               COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.learner_id END) AS learners_completed,
               COUNT(DISTINCT e.learner_id) AS total_enrolled
        FROM ld_skill s
        JOIN ld_course_skill cs ON cs.skill_id = s.id
        LEFT JOIN ld_enrollment e ON e.course_id = cs.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE {$skillFilters['sql']}
        GROUP BY s.id, s.name
        ORDER BY learners_completed ASC
    ");
    $stmt->execute($skillFilters['params']);
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
    $trendFilters = $buildFilters('e.enrolled_at', 'emp', 'trend');
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(e.enrolled_at, '%Y-%m') AS month,
            COUNT(*) AS enrollments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completions
        FROM ld_enrollment e
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE {$trendFilters['sql']}
                    {$trendRollingWindow}
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute($trendFilters['params']);
    $trend = $stmt->fetchAll();

    // Certificate stats
    $certificateFilters = $buildFilters('c.issued_at', 'emp', 'certificate');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total
        FROM ld_certificate c
        LEFT JOIN em_employees emp ON emp.employee_id = c.learner_id
        WHERE {$certificateFilters['sql']}");
    $stmt->execute($certificateFilters['params']);
    $totalCerts = (int)$stmt->fetch()['total'];

    // Certification expiry (next 90 days) — feeds wfa_reports
        $stmt = $pdo->prepare("
        SELECT c.learner_id, c.issued_at, c.valid_until, co.title AS course_title
        FROM ld_certificate c
        JOIN ld_course co ON co.id = c.course_id
                LEFT JOIN em_employees emp ON emp.employee_id = c.learner_id
        WHERE c.status = 'active' AND c.valid_until IS NOT NULL
          AND c.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                    AND {$certificateFilters['sql']}
        ORDER BY c.valid_until ASC
    ");
        $stmt->execute($certificateFilters['params']);
    $expiringCertificates = $stmt->fetchAll();

    // Latest engagement/risk snapshot — feeds wfa_risk_assessment / wfa_at_risk_employees_summary
    $riskSummary = ['snapshot_date' => null, 'bands' => [], 'at_risk_learners' => []];
        $riskFilters = $buildFilters('es.snapshot_date', 'emp', 'risk');
        $stmt = $pdo->prepare("SELECT MAX(es.snapshot_date) AS d
                FROM ld_engagement_snapshot es
                LEFT JOIN em_employees emp ON emp.employee_id = es.learner_id
                WHERE {$riskFilters['sql']}");
        $stmt->execute($riskFilters['params']);
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
            LEFT JOIN em_employees emp ON emp.employee_id = es.learner_id
            WHERE es.snapshot_date = :d AND {$riskFilters['sql']}
            GROUP BY band
            ORDER BY band
        ");
        $stmt->execute(array_merge([':d' => $riskDate], $riskFilters['params']));
        $bands = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT es.learner_id, es.risk_score, es.risk_factors,
                   es.active_days_30, es.days_since_last_activity, es.expired_certificates
            FROM ld_engagement_snapshot es
            LEFT JOIN em_employees emp ON emp.employee_id = es.learner_id
            WHERE es.snapshot_date = :d AND es.risk_score >= 40 AND {$riskFilters['sql']}
            ORDER BY es.risk_score DESC
        ");
        $stmt->execute(array_merge([':d' => $riskDate], $riskFilters['params']));
        $atRisk = $stmt->fetchAll();

        $riskSummary = [
            'snapshot_date' => $riskDate,
            'bands' => $bands,
            'at_risk_learners' => $atRisk,
        ];
    }

    // Latest skill snapshots (per-learner, with position/department) — feeds wfa_skill_gap_analysis
    $skillSnapshots = [];
    $snapshotFilters = $buildFilters('ss.snapshot_date', 'emp', 'snapshot');
    $stmt = $pdo->prepare("SELECT MAX(ss.snapshot_date) AS d
        FROM ld_skill_snapshot ss
        LEFT JOIN em_employees emp ON emp.employee_id = ss.learner_id
        WHERE {$snapshotFilters['sql']}");
    $stmt->execute($snapshotFilters['params']);
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
            WHERE ss.snapshot_date = :d AND {$snapshotFilters['sql']}
            ORDER BY ss.gap_count DESC
        ");
        $stmt->execute(array_merge([':d' => $skillDate], $snapshotFilters['params']));
        $skillSnapshots = $stmt->fetchAll();
    }

    // Knowledge-transfer learning paths (retention-relevant metric)
    $pathFilters = $buildFilters('lp.created_at', 'emp', 'path');
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_paths,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_paths
                FROM ld_learning_path lp
                LEFT JOIN em_employees emp ON emp.employee_id = lp.assigned_to
                WHERE (lp.type = 'knowledge_transfer' OR lp.kt_plan_id IS NOT NULL)
                    AND {$pathFilters['sql']}
    ");
    $stmt->execute($pathFilters['params']);
    $ktPaths = $stmt->fetch();

    $payload = [
        'total_learners'      => $totalLearners,
        'snapshot_date'       => $riskDate ?: $skillDate,
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
        'filters'             => [
            'department_id' => $departmentId,
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
        ],
    ];

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-workforce-analytics', 'success', [
        'filters' => $_GET,
    ]);

    echo json_encode(['success' => true, 'data' => $payload]);
} catch (Throwable $e) {
    http_response_code(500);
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-workforce-analytics', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
