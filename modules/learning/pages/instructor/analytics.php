<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

// Admin analytics reuses this view with platform-wide data. Instructors keep
// their existing course-scoped analytics.
$analyticsScope = $analyticsScope ?? 'instructor';
$isAdminScope = $analyticsScope === 'admin';
$learnerProfileUrl = static function (int $learnerId): string {
    return LearningProfileLink::url($learnerId, 'learner');
};
$scopeSql = $isAdminScope ? '' : ' AND c.instructor_id = :iid';
$scopeParams = $isAdminScope ? [] : [':iid' => $instructorId];
$programScopeSql = $isAdminScope ? '' : ' AND p.instructor_id = :iid';

$stats = ['my_courses' => 0, 'my_modules' => 0, 'my_learners' => 0, 'my_programs' => 0, 'completed' => 0, 'in_progress' => 0, 'avg_score' => 0, 'pass_rate' => 0, 'total_quizzes' => 0];
$coursePerformance = [];
$studentLeaderboard = [];
$atRiskStudents = [];
$recentGrades = [];
$monthlyEnrollments = [];
$gradeDistribution = [];
$courseEngagement = [];

try {
    $pdo = (new Database())->getConnection();

    // Core stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_course c WHERE 1=1 $scopeSql AND c.status IN ('active','draft')");
    $stmt->execute($scopeParams);
    $stats['my_courses'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_module m JOIN ld_course c ON c.id = m.course_id WHERE 1=1 $scopeSql AND m.status = 'active'");
    $stmt->execute($scopeParams);
    $stats['my_modules'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE 1=1 $scopeSql");
    $stmt->execute($scopeParams);
    $stats['my_learners'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_program p WHERE 1=1 $programScopeSql AND p.status = 'active'");
    $stmt->execute($scopeParams);
    $stats['my_programs'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE 1=1 $scopeSql AND e.status = 'completed'");
    $stmt->execute($scopeParams);
    $stats['completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE 1=1 $scopeSql AND e.status IN ('enrolled','in_progress')");
    $stmt->execute($scopeParams);
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score),0),1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE 1=1 $scopeSql");
    $stmt->execute($scopeParams);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    $totalEnrollments = $stats['completed'] + $stats['in_progress'];
    $stats['pass_rate'] = $totalEnrollments > 0 ? round(($stats['completed'] / $totalEnrollments) * 100) : 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id JOIN ld_course c ON c.id = m.course_id WHERE 1=1 $scopeSql AND q.status = 'active'");
    $stmt->execute($scopeParams);
    $stats['total_quizzes'] = (int) $stmt->fetchColumn();

    // Course performance
    $coursePerformance = $pdo->prepare("
        SELECT c.id, c.title, c.status,
            COUNT(DISTINCT e.id) AS enrollment_count,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN e.status IN ('enrolled','in_progress') THEN 1 ELSE 0 END) AS active_count,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count,
            (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m2 ON m2.id = l.module_id WHERE m2.course_id = c.id AND l.status = 'active') AS lesson_count
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN ld_grade g ON g.course_id = c.id AND g.learner_id = e.learner_id
        WHERE 1=1 $scopeSql AND c.status IN ('active','draft')
        GROUP BY c.id, c.title, c.status
        ORDER BY enrollment_count DESC
    ");
    $coursePerformance->execute($scopeParams);
    $coursePerformance = $coursePerformance->fetchAll(PDO::FETCH_ASSOC);

    // Student leaderboard
    $studentLeaderboard = $pdo->prepare("
        SELECT e.learner_id,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
            pos.position_name AS learner_position,
            COUNT(DISTINCT e.course_id) AS courses_enrolled,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS courses_completed,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            MAX(e.last_accessed_at) AS last_active
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        LEFT JOIN em_positions pos ON pos.position_id = emp.position_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE 1=1 $scopeSql AND e.status IN ('enrolled','in_progress','completed')
        GROUP BY e.learner_id, emp.first_name, emp.last_name, pos.position_name
        ORDER BY avg_score DESC, courses_completed DESC
        LIMIT 20
    ");
    $studentLeaderboard->execute($scopeParams);
    $studentLeaderboard = $studentLeaderboard->fetchAll(PDO::FETCH_ASSOC);

    // At-risk students (enrolled but inactive for 14+ days, or failing)
    $atRiskStudents = $pdo->prepare("
        SELECT e.learner_id,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
            c.title AS course_title,
            e.status AS enrollment_status,
            e.last_accessed_at,
            DATEDIFF(NOW(), e.last_accessed_at) AS days_inactive,
            ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
            CASE
                WHEN DATEDIFF(NOW(), e.last_accessed_at) > 14 THEN 'inactive'
                WHEN AVG(g.final_score) < 60 AND AVG(g.final_score) IS NOT NULL THEN 'failing'
                ELSE 'slow_progress'
            END AS risk_reason
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        WHERE 1=1 $scopeSql AND e.status IN ('enrolled','in_progress')
        GROUP BY e.learner_id, emp.first_name, emp.last_name, c.title, e.status, e.last_accessed_at
        HAVING days_inactive > 14 OR avg_score < 60
        ORDER BY days_inactive DESC
        LIMIT 15
    ");
    $atRiskStudents->execute($scopeParams);
    $atRiskStudents = $atRiskStudents->fetchAll(PDO::FETCH_ASSOC);

    // Recent grades
    $recentGrades = $pdo->prepare("
        SELECT g.learner_id, g.final_score, g.status, g.issued_at, c.title AS course_title,
            CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
        FROM ld_grade g
        JOIN ld_course c ON c.id = g.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = g.learner_id
        WHERE 1=1 $scopeSql
        ORDER BY g.issued_at DESC LIMIT 15
    ");
    $recentGrades->execute($scopeParams);
    $recentGrades = $recentGrades->fetchAll(PDO::FETCH_ASSOC);

    // Monthly enrollments trend
    $monthlyEnrollments = $pdo->prepare("
        SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m') AS month, COUNT(*) AS cnt
        FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id
        WHERE 1=1 $scopeSql AND e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC
    ");
    $monthlyEnrollments->execute($scopeParams);
    $monthlyEnrollments = $monthlyEnrollments->fetchAll(PDO::FETCH_ASSOC);

    // Grade distribution
    $gradeDistribution = $pdo->prepare("
        SELECT
            SUM(CASE WHEN g.final_score >= 90 THEN 1 ELSE 0 END) AS grade_a,
            SUM(CASE WHEN g.final_score >= 80 AND g.final_score < 90 THEN 1 ELSE 0 END) AS grade_b,
            SUM(CASE WHEN g.final_score >= 70 AND g.final_score < 80 THEN 1 ELSE 0 END) AS grade_c,
            SUM(CASE WHEN g.final_score >= 60 AND g.final_score < 70 THEN 1 ELSE 0 END) AS grade_d,
            SUM(CASE WHEN g.final_score < 60 THEN 1 ELSE 0 END) AS grade_f,
            COUNT(*) AS total
        FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE 1=1 $scopeSql
    ");
    $gradeDistribution->execute($scopeParams);
    $gradeDistribution = $gradeDistribution->fetch(PDO::FETCH_ASSOC);

    // Course engagement (per-course breakdown)
    $courseEngagement = $pdo->prepare("
        SELECT c.title,
            (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = c.id AND l.status = 'active') AS total_lessons,
            (SELECT COUNT(DISTINCT p.reference_id) FROM ld_progress p JOIN ld_enrollment e2 ON e2.id = p.enrollment_id WHERE e2.course_id = c.id AND p.item_type = 'lesson' AND p.status = 'completed') AS completed_lessons,
            (SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m2 ON m2.id = q.module_id WHERE m2.course_id = c.id AND q.status = 'active') AS total_quizzes,
            (SELECT COUNT(*) FROM ld_quiz_session qs JOIN ld_enrollment e3 ON e3.course_id = c.id WHERE qs.learner_id = e3.learner_id AND qs.item_type = 'quiz' AND qs.status IN ('submitted','expired')) AS quizzes_taken,
            (SELECT ROUND(COALESCE(AVG(qs2.score),0),1) FROM ld_quiz_session qs2 JOIN ld_enrollment e4 ON e4.course_id = c.id WHERE qs2.learner_id = e4.learner_id AND qs2.item_type = 'quiz' AND qs2.status = 'submitted') AS avg_quiz_score
        FROM ld_course c
        WHERE 1=1 $scopeSql AND c.status = 'active'
        ORDER BY c.title ASC
    ");
    $courseEngagement->execute($scopeParams);
    $courseEngagement = $courseEngagement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

$totalEnroll = $stats['completed'] + $stats['in_progress'];
$completionRate = $totalEnroll > 0 ? round(($stats['completed'] / $totalEnroll) * 100) : 0;
?>
<div class="module-content an-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="ia-title">
            <div class="home-hero-main">
                <h1 id="ia-title">Analytics</h1>
                <p><?= $isAdminScope ? 'How courses, trainees and grades are performing across the platform.' : 'How your courses, trainees and grades are performing.' ?></p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap"></i> <?= (int) $stats['my_courses'] ?> course<?= (int) $stats['my_courses'] === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-users"></i> <?= (int) $stats['my_learners'] ?> trainee<?= (int) $stats['my_learners'] === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $stats['avg_score'] ?>% avg score</span>
                </div>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat is-link" title="Show the overview tab" onclick="document.querySelector('.tab-item[data-tab=tab-overview]').click()">
                <span class="home-stat-icon tone-violet"><i class="fas fa-graduation-cap"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Courses</span>
                    <span class="home-stat-value"><?= (int) $stats['my_courses'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['my_modules'] ?> modules &middot; <?= (int) $stats['total_quizzes'] ?> quizzes</span>
                </span>
            </div>
            <div class="home-stat is-link" title="Show the students tab" onclick="document.querySelector('.tab-item[data-tab=tab-students]').click()">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-users"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Trainees</span>
                    <span class="home-stat-value"><?= (int) $stats['my_learners'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['completed'] ?> completed &middot; <?= (int) $stats['in_progress'] ?> active</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub"><?= $stats['pass_rate'] ?>% pass rate</span>
                </span>
            </div>
            <div class="home-stat is-link" title="Show the students tab" onclick="document.querySelector('.tab-item[data-tab=tab-students]').click()">
                <span class="home-stat-icon tone-rose"><i class="fas fa-triangle-exclamation"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">At-Risk</span>
                    <span class="home-stat-value"><?= count($atRiskStudents) ?></span>
                    <span class="home-stat-sub"><?= count($atRiskStudents) === 1 ? 'student needs attention' : 'students need attention' ?></span>
                </span>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-overview" aria-label="Overview" title="Overview"><i class="fas fa-gauge-high" aria-hidden="true"></i><span class="tab-label">Overview</span></button>
                <button type="button" class="tab-item" data-tab="tab-students" aria-label="Students" title="Students"><i class="fas fa-users" aria-hidden="true"></i><span class="tab-label">Students</span></button>
                <button type="button" class="tab-item" data-tab="tab-courses" aria-label="Courses" title="Courses"><i class="fas fa-book-open" aria-hidden="true"></i><span class="tab-label">Courses</span></button>
                <button type="button" class="tab-item" data-tab="tab-grades" aria-label="Grades" title="Grades"><i class="fas fa-chart-bar" aria-hidden="true"></i><span class="tab-label">Grades</span></button>
                <button type="button" class="tab-item" data-tab="tab-roster" aria-label="Roster" title="Roster"><i class="fas fa-clipboard-list" aria-hidden="true"></i><span class="tab-label">Roster</span></button>
                <button type="button" class="tab-item" data-tab="tab-progress" aria-label="Progress" title="Progress"><i class="fas fa-chart-simple" aria-hidden="true"></i><span class="tab-label">Progress</span></button>
            </div>

            <!-- ==================== OVERVIEW ==================== -->
            <div class="tab-content active" data-tab="tab-overview">
                <div class="an-cols">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-pie"></i> Course Completion</h3>
                        <div class="an-summary">
                            <div class="an-ring" style="--an-pct: <?= (int) $completionRate ?>;">
                                <div class="an-ring-inner">
                                    <span class="an-ring-value"><?= (int) $completionRate ?>%</span>
                                    <span class="an-ring-label">Completed</span>
                                </div>
                            </div>
                            <div class="an-kv-list">
                                <div class="an-kv">
                                    <span class="an-kv-label">Courses completed</span>
                                    <span class="an-kv-value"><?= (int) $stats['completed'] ?></span>
                                </div>
                                <div class="an-kv">
                                    <span class="an-kv-label">Total enrollments</span>
                                    <span class="an-kv-value"><?= $totalEnroll ?></span>
                                </div>
                                <div class="an-kv">
                                    <span class="an-kv-label">In progress</span>
                                    <span class="an-kv-value"><?= (int) $stats['in_progress'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-line"></i> Enrollment Trend</h3>
                        <?php if (empty($monthlyEnrollments)): ?>
                        <div class="an-empty">
                            <i class="fas fa-chart-line"></i>
                            <strong>No enrollment data yet</strong>
                            <span>Monthly enrollments will be charted here.</span>
                        </div>
                        <?php else:
                            $maxCnt = max(array_column($monthlyEnrollments, 'cnt'));
                            $maxCnt = max($maxCnt, 1);
                        ?>
                        <div class="an-chart">
                            <?php foreach ($monthlyEnrollments as $me):
                                $meH = round(($me['cnt'] / $maxCnt) * 100);
                            ?>
                            <div class="an-chart-col">
                                <span class="an-chart-value"><?= (int) $me['cnt'] ?></span>
                                <span class="an-chart-bar" style="height:<?= max(2, (int) $meH) ?>%;" title="<?= (int) $me['cnt'] ?> enrollments"></span>
                                <span class="an-chart-label"><?= date('M', strtotime($me['month'] . '-01')) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-trophy"></i> Top Courses</h3>
                        <?php if (empty($coursePerformance)): ?>
                        <div class="an-empty">
                            <i class="fas fa-book-open"></i>
                            <strong>No courses yet</strong>
                            <span><?= $isAdminScope ? 'Courses will be ranked here once trainees enroll.' : 'Courses you author will be ranked here.' ?></span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="an-th-num">Enrolled</th>
                                        <th class="an-th-num">Avg Score</th>
                                        <th class="an-th-num">Completion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($coursePerformance, 0, 5) as $idx => $cp):
                                        $cpCompRate = $cp['enrollment_count'] > 0 ? (int) round(($cp['completed_count'] / $cp['enrollment_count']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="an-course">
                                                <span class="an-rank"><?= $idx + 1 ?></span>
                                                <span class="an-course-name" title="<?= htmlspecialchars($cp['title']) ?>"><?= htmlspecialchars($cp['title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><?= (int) $cp['enrollment_count'] ?></td>
                                        <td class="an-td-num"><span class="an-score"><?= $cp['avg_score'] ?>%</span></td>
                                        <td class="an-td-num">
                                            <div class="an-completion">
                                                <span class="an-mini-track"><span class="an-mini-fill" style="width:<?= max(0, min(100, $cpCompRate)) ?>%;"></span></span>
                                                <span class="an-completion-val"><?= $cpCompRate ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-triangle-exclamation"></i> At-Risk Students</h3>
                        <?php if (empty($atRiskStudents)): ?>
                        <div class="an-empty">
                            <i class="fas fa-circle-check"></i>
                            <strong>All students are on track</strong>
                            <span>Nobody is flagged as at risk right now.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th class="an-th-num">Signal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($atRiskStudents, 0, 5) as $ar):
                                        $arPill = $ar['risk_reason'] === 'inactive' ? 'is-bad' : ($ar['risk_reason'] === 'failing' ? 'is-warn' : 'is-neutral');
                                        $arLabel = $ar['risk_reason'] === 'inactive' ? $ar['days_inactive'] . 'd inactive' : ($ar['risk_reason'] === 'failing' ? $ar['avg_score'] . '% avg' : 'Slow progress');
                                    ?>
                                    <tr>
                                        <td><strong><a href="<?= htmlspecialchars($learnerProfileUrl((int) $ar['learner_id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ar['learner_name']) ?></a></strong></td>
                                        <td class="an-muted"><?= htmlspecialchars($ar['course_title']) ?></td>
                                        <td class="an-td-num"><span class="an-pill <?= $arPill ?>"><?= htmlspecialchars($arLabel) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($atRiskStudents) > 5): ?>
                        <p class="an-note"><i class="fas fa-info-circle"></i> <?= count($atRiskStudents) - 5 ?> more at-risk student<?= count($atRiskStudents) - 5 === 1 ? '' : 's' ?> on the Students tab.</p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== STUDENTS ==================== -->
            <div class="tab-content" data-tab="tab-students">
                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-trophy"></i> Student Leaderboard</h3>
                        <?php if (empty($studentLeaderboard)): ?>
                        <div class="an-empty">
                            <i class="fas fa-users"></i>
                            <strong>No student data yet</strong>
                            <span><?= $isAdminScope ? 'Trainees appear here once they enrol in a course.' : 'Trainees appear here once they enrol in your courses.' ?></span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th class="an-th-num">Enrolled</th>
                                        <th class="an-th-num">Completed</th>
                                        <th class="an-th-num">Avg Score</th>
                                        <th class="an-th-num">Last Active</th>
                                        <th class="an-th-num">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentLeaderboard as $idx => $sl):
                                        $daysSinceActive = $sl['last_active'] ? (int) floor((time() - strtotime($sl['last_active'])) / 86400) : 999;
                                        $slPill = $daysSinceActive <= 3 ? 'is-good' : ($daysSinceActive <= 7 ? 'is-warn' : 'is-bad');
                                        $slStatus = $daysSinceActive <= 3 ? 'Active' : ($daysSinceActive <= 7 ? 'Recent' : 'Inactive');
                                        $slScore = $sl['avg_score'] >= 80 ? '' : ($sl['avg_score'] >= 60 ? 'is-warn' : 'is-low');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="an-course">
                                                <span class="an-rank"><?= $idx + 1 ?></span>
                                                <span>
                                                    <a class="an-course-name" href="<?= htmlspecialchars($learnerProfileUrl((int) $sl['learner_id']), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($sl['learner_name']) ?>"><?= htmlspecialchars($sl['learner_name']) ?></a>
                                                    <?php if (!empty($sl['learner_position'])): ?>
                                                    <span class="an-sub"><?= htmlspecialchars($sl['learner_position']) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><?= (int) $sl['courses_enrolled'] ?></td>
                                        <td class="an-td-num"><?= (int) $sl['courses_completed'] ?></td>
                                        <td class="an-td-num"><span class="an-score <?= $slScore ?>"><?= $sl['avg_score'] ?>%</span></td>
                                        <td class="an-td-num an-muted"><?= $sl['last_active'] ? date('M j', strtotime($sl['last_active'])) : 'Never' ?></td>
                                        <td class="an-td-num"><span class="an-pill <?= $slPill ?>"><?= $slStatus ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-triangle-exclamation"></i> Students Needing Attention</h3>
                        <?php if (empty($atRiskStudents)): ?>
                        <div class="an-empty">
                            <i class="fas fa-circle-check"></i>
                            <strong>No students flagged as at-risk</strong>
                            <span>Inactive or failing trainees will be listed here.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th class="an-th-num">Status</th>
                                        <th class="an-th-num">Avg Score</th>
                                        <th class="an-th-num">Days Inactive</th>
                                        <th class="an-th-num">Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($atRiskStudents as $ar):
                                        $arPill = $ar['risk_reason'] === 'inactive' ? 'is-bad' : ($ar['risk_reason'] === 'failing' ? 'is-warn' : 'is-neutral');
                                        $arLabel = $ar['risk_reason'] === 'inactive' ? 'Inactive' : ($ar['risk_reason'] === 'failing' ? 'Failing' : 'Slow');
                                        $arScore = $ar['avg_score'] >= 70 ? '' : 'is-low';
                                    ?>
                                    <tr>
                                        <td><strong><a href="<?= htmlspecialchars($learnerProfileUrl((int) $ar['learner_id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ar['learner_name']) ?></a></strong></td>
                                        <td class="an-muted"><?= htmlspecialchars($ar['course_title']) ?></td>
                                        <td class="an-td-num"><span class="an-pill is-neutral"><?= ucfirst(str_replace('_', ' ', $ar['enrollment_status'])) ?></span></td>
                                        <td class="an-td-num"><span class="an-score <?= $arScore ?>"><?= $ar['avg_score'] ?>%</span></td>
                                        <td class="an-td-num"><?= (int) $ar['days_inactive'] ?>d</td>
                                        <td class="an-td-num"><span class="an-pill <?= $arPill ?>"><?= $arLabel ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== COURSES ==================== -->
            <div class="tab-content" data-tab="tab-courses">
                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-book-open"></i> Course Engagement</h3>
                        <?php if (empty($courseEngagement)): ?>
                        <div class="an-empty">
                            <i class="fas fa-book-open"></i>
                            <strong>No active courses</strong>
                            <span>Engagement appears once a published course has trainees.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="an-th-num">Lessons</th>
                                        <th class="an-th-num">Quizzes Taken</th>
                                        <th class="an-th-num">Avg Quiz Score</th>
                                        <th class="an-th-num">Lesson Completion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseEngagement as $ce):
                                        $lessonComp = $ce['total_lessons'] > 0 ? (int) round(($ce['completed_lessons'] / $ce['total_lessons']) * 100) : 0;
                                        $ceScore = $ce['avg_quiz_score'] >= 70 ? '' : 'is-low';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="an-course">
                                                <span class="an-course-name" title="<?= htmlspecialchars($ce['title']) ?>"><?= htmlspecialchars($ce['title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><?= (int) $ce['completed_lessons'] ?> / <?= (int) $ce['total_lessons'] ?></td>
                                        <td class="an-td-num"><?= (int) $ce['quizzes_taken'] ?> / <?= (int) $ce['total_quizzes'] ?></td>
                                        <td class="an-td-num">
                                            <?php if ((int) $ce['total_quizzes'] > 0): ?>
                                            <span class="an-score <?= $ceScore ?>"><?= $ce['avg_quiz_score'] ?>%</span>
                                            <?php else: ?>
                                            <span class="an-muted">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="an-td-num">
                                            <div class="an-completion">
                                                <span class="an-mini-track"><span class="an-mini-fill" style="width:<?= max(0, min(100, $lessonComp)) ?>%;"></span></span>
                                                <span class="an-completion-val"><?= $lessonComp ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-table"></i> Course Overview</h3>
                        <?php if (empty($coursePerformance)): ?>
                        <div class="an-empty">
                            <i class="fas fa-book-open"></i>
                            <strong>No courses yet</strong>
                            <span><?= $isAdminScope ? 'All courses and their enrolment figures appear here.' : 'Your courses and their enrolment figures appear here.' ?></span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="an-th-num">Status</th>
                                        <th class="an-th-num">Enrolled</th>
                                        <th class="an-th-num">Completed</th>
                                        <th class="an-th-num">Active</th>
                                        <th class="an-th-num">Completion</th>
                                        <th class="an-th-num">Avg Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coursePerformance as $cp):
                                        $cpRate = $cp['enrollment_count'] > 0 ? (int) round(($cp['completed_count'] / $cp['enrollment_count']) * 100) : 0;
                                        $cpScore = $cp['avg_score'] >= 70 ? '' : ($cp['avg_score'] >= 50 ? 'is-warn' : 'is-low');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="an-course">
                                                <span class="an-course-name" title="<?= htmlspecialchars($cp['title']) ?>"><?= htmlspecialchars($cp['title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><span class="an-pill <?= $cp['status'] === 'active' ? 'is-good' : 'is-neutral' ?>"><?= ucfirst($cp['status']) ?></span></td>
                                        <td class="an-td-num"><?= (int) $cp['enrollment_count'] ?></td>
                                        <td class="an-td-num"><?= (int) $cp['completed_count'] ?></td>
                                        <td class="an-td-num"><?= (int) $cp['active_count'] ?></td>
                                        <td class="an-td-num">
                                            <div class="an-completion">
                                                <span class="an-mini-track"><span class="an-mini-fill" style="width:<?= max(0, min(100, $cpRate)) ?>%;"></span></span>
                                                <span class="an-completion-val"><?= $cpRate ?>%</span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><span class="an-score <?= $cpScore ?>"><?= $cp['avg_score'] ?>%</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== GRADES ==================== -->
            <div class="tab-content" data-tab="tab-grades">
                <?php
                $gdTotal = (int) ($gradeDistribution['total'] ?? 0);
                $gradeBands = [
                    ['key' => 'a', 'label' => 'A', 'range' => '90-100', 'count' => (int) ($gradeDistribution['grade_a'] ?? 0)],
                    ['key' => 'b', 'label' => 'B', 'range' => '80-89', 'count' => (int) ($gradeDistribution['grade_b'] ?? 0)],
                    ['key' => 'c', 'label' => 'C', 'range' => '70-79', 'count' => (int) ($gradeDistribution['grade_c'] ?? 0)],
                    ['key' => 'd', 'label' => 'D', 'range' => '60-69', 'count' => (int) ($gradeDistribution['grade_d'] ?? 0)],
                    ['key' => 'f', 'label' => 'F', 'range' => 'Below 60', 'count' => (int) ($gradeDistribution['grade_f'] ?? 0)],
                ];
                $maxG = max(1, max(array_column($gradeBands, 'count')));
                ?>
                <div class="an-cols">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-bar"></i> Grade Distribution</h3>
                        <?php if ($gdTotal === 0): ?>
                        <div class="an-empty">
                            <i class="fas fa-graduation-cap"></i>
                            <strong>No grades to display</strong>
                            <span>Grades will be charted here once quizzes are graded.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-dist">
                            <?php foreach ($gradeBands as $band):
                                $bandH = round(($band['count'] / $maxG) * 100);
                                $bandPct = (int) round(($band['count'] / $gdTotal) * 100);
                            ?>
                            <div class="an-dist-col">
                                <span class="an-dist-count"><?= $band['count'] ?></span>
                                <span class="an-dist-pct"><?= $bandPct ?>%</span>
                                <span class="an-dist-bar an-grade-<?= $band['key'] ?>" style="height:<?= max(2, (int) $bandH) ?>%;"></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="an-legend">
                            <?php foreach ($gradeBands as $band): ?>
                            <div class="an-legend-item">
                                <div class="an-legend-swatch an-grade-<?= $band['key'] ?>"></div>
                                <div class="an-legend-label"><?= $band['label'] ?></div>
                                <div class="an-legend-sub"><?= $band['range'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="an-note"><i class="fas fa-info-circle"></i> Based on the <?= $gdTotal ?> grade<?= $gdTotal === 1 ? '' : 's' ?> issued <?= $isAdminScope ? 'across all courses' : 'in your courses' ?>.</p>
                        <?php endif; ?>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-line"></i> Score Summary</h3>
                        <div class="an-kv-list">
                            <div class="an-kv">
                                <span class="an-kv-label">Average score</span>
                                <span class="an-kv-value"><?= $stats['avg_score'] ?>%</span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Pass rate</span>
                                <span class="an-kv-value"><?= $stats['pass_rate'] ?>%</span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Total grades</span>
                                <span class="an-kv-value"><?= $gdTotal ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Quizzes created</span>
                                <span class="an-kv-value"><?= (int) $stats['total_quizzes'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-clock"></i> Recent Grades</h3>
                        <?php if (empty($recentGrades)): ?>
                        <div class="an-empty">
                            <i class="fas fa-clock"></i>
                            <strong>No grades recorded yet</strong>
                            <span>Recently issued grades will be listed here.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th class="an-th-num">Score</th>
                                        <th class="an-th-num">Status</th>
                                        <th class="an-th-num">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentGrades as $rg):
                                        $rgScore = $rg['final_score'] >= 70 ? '' : ($rg['final_score'] >= 50 ? 'is-warn' : 'is-low');
                                    ?>
                                    <tr>
                                        <td><strong><a href="<?= htmlspecialchars($learnerProfileUrl((int) $rg['learner_id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rg['learner_name']) ?></a></strong></td>
                                        <td class="an-muted"><?= htmlspecialchars($rg['course_title']) ?></td>
                                        <td class="an-td-num"><span class="an-score <?= $rgScore ?>"><?= round($rg['final_score'], 1) ?>%</span></td>
                                        <td class="an-td-num"><span class="an-pill <?= $rg['status'] === 'passed' ? 'is-good' : 'is-bad' ?>"><?= ucfirst($rg['status']) ?></span></td>
                                        <td class="an-td-num an-muted"><?= $rg['issued_at'] ? date('M j, Y', strtotime($rg['issued_at'])) : '&mdash;' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== ROSTER ==================== -->
            <div class="tab-content" data-tab="tab-roster">
                <?php $hideLearnerImport = true; ?>
                <?php include __DIR__ . '/manage-learners.php'; ?>
            </div>

            <!-- ==================== PROGRESS ==================== -->
            <div class="tab-content" data-tab="tab-progress">
                <?php include __DIR__ . '/progress-dashboard.php'; ?>
            </div>
        </div>
    </div>
</div>
