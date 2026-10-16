<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$stats = ['users' => 0, 'courses' => 0, 'active_enrollments' => 0, 'passed_courses' => 0, 'instructors' => 0, 'learners' => 0, 'completed' => 0, 'avg_score' => 0, 'pending_reports' => 0];
$topCourses = [];
$monthlyEnrollments = [];
$recentGrades = [];

try {
    $pdo = (new Database())->getConnection();
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM em_employees')->fetchColumn();
    $stats['courses'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE status IN ('active','draft')")->fetchColumn();
    $stats['active_enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status IN ('enrolled','in_progress')")->fetchColumn();
    $stats['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status = 'completed'")->fetchColumn();
    $stats['instructors'] = (int) $pdo->query("SELECT COUNT(DISTINCT instructor_id) FROM ld_course WHERE instructor_id IS NOT NULL")->fetchColumn();
    $stats['learners'] = (int) $pdo->query("SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment")->fetchColumn();
    $stats['avg_score'] = (float) ($pdo->query("SELECT ROUND(COALESCE(AVG(final_score),0),1) FROM ld_grade")->fetchColumn() ?? 0);
    $stats['pending_reports'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_report WHERE status='pending'")->fetchColumn();

    $topCourses = $pdo->query("SELECT c.title, COUNT(e.id) AS enrollment_count, ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score, COUNT(DISTINCT g.id) AS grade_count, SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id=c.id LEFT JOIN ld_grade g ON g.course_id=c.id WHERE c.status='active' GROUP BY c.id,c.title ORDER BY enrollment_count DESC, c.title ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    $monthlyEnrollments = $pdo->query("SELECT DATE_FORMAT(enrolled_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM ld_enrollment WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC")->fetchAll(PDO::FETCH_ASSOC);

    $recentGrades = $pdo->query("SELECT g.final_score, g.status, c.title FROM ld_grade g JOIN ld_course c ON c.id=g.course_id ORDER BY g.issued_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

$completionRate = ($stats['active_enrollments'] + $stats['completed']) > 0 ? round(($stats['completed'] / max(1, $stats['active_enrollments'] + $stats['completed'])) * 100) : 0;

// --- Values derived for the markup (same numbers the page always showed) ---
$totalEnrollments = $stats['active_enrollments'] + $stats['completed'];
$adminCount = max(0, $stats['users'] - $stats['instructors'] - $stats['learners']);

/** Share of all employees, clamped to a usable bar width. */
$userBars = [
    ['label' => 'Instructors', 'key' => 'instructors', 'count' => (int) $stats['instructors']],
    ['label' => 'Learners', 'key' => 'learners', 'count' => (int) $stats['learners']],
    ['label' => 'Admins', 'key' => 'admins', 'count' => $adminCount],
];

$distA = 0; $distB = 0; $distC = 0; $distD = 0; $distF = 0;
foreach ($recentGrades as $g) {
    $s = (float) $g['final_score'];
    if ($s >= 90) $distA++; elseif ($s >= 80) $distB++; elseif ($s >= 70) $distC++; elseif ($s >= 60) $distD++; else $distF++;
}
$totalG = count($recentGrades);
$maxG = max(max($distA, $distB, $distC, $distD, $distF), 1);
$gradeBands = [
    ['key' => 'a', 'label' => 'A', 'range' => '90-100', 'count' => $distA],
    ['key' => 'b', 'label' => 'B', 'range' => '80-89', 'count' => $distB],
    ['key' => 'c', 'label' => 'C', 'range' => '70-79', 'count' => $distC],
    ['key' => 'd', 'label' => 'D', 'range' => '60-69', 'count' => $distD],
    ['key' => 'f', 'label' => 'F', 'range' => 'Below 60', 'count' => $distF],
];
?>

<div class="module-content an-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="an-title">
            <div class="home-hero-main">
                <h1 id="an-title">Analytics</h1>
                <p>How courses, learners and grades are performing across the platform.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-users"></i> <?= (int) $stats['users'] ?> users</span>
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap"></i> <?= (int) $stats['courses'] ?> courses</span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $stats['avg_score'] ?>% avg score</span>
                </div>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon tone-violet"><i class="fas fa-users"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Total Users</span>
                    <span class="home-stat-value"><?= (int) $stats['users'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['learners'] ?> learner<?= (int) $stats['learners'] === 1 ? '' : 's' ?> &middot; <?= (int) $stats['instructors'] ?> instructor<?= (int) $stats['instructors'] === 1 ? '' : 's' ?></span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-graduation-cap"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Active Courses</span>
                    <span class="home-stat-value"><?= (int) $stats['courses'] ?></span>
                    <span class="home-stat-sub"><?= $completionRate ?>% completion rate</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-book-open"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Enrollments</span>
                    <span class="home-stat-value"><?= $totalEnrollments ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['active_enrollments'] ?> active &middot; <?= (int) $stats['completed'] ?> completed</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-amber"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub">across all courses</span>
                </span>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-overview"><i class="fas fa-gauge-high"></i> Overview</button>
                <button type="button" class="tab-item" data-tab="tab-courses"><i class="fas fa-trophy"></i> Courses</button>
                <button type="button" class="tab-item" data-tab="tab-trends"><i class="fas fa-chart-line"></i> Trends</button>
            </div>

            <!-- Overview -->
            <div class="tab-content active" data-tab="tab-overview" id="tab-overview">
                <div class="an-cols">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-pie"></i> Platform Summary</h3>
                        <div class="an-summary">
                            <div class="an-ring" style="--an-pct: <?= (int) $completionRate ?>;">
                                <div class="an-ring-inner">
                                    <span class="an-ring-value"><?= $completionRate ?>%</span>
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
                                    <span class="an-kv-value"><?= $totalEnrollments ?></span>
                                </div>
                                <div class="an-kv">
                                    <span class="an-kv-label">Pending reports</span>
                                    <span class="an-kv-value"><?= (int) $stats['pending_reports'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-users"></i> User Breakdown</h3>
                        <div class="an-breakdown">
                            <?php foreach ($userBars as $bar):
                                $pct = $stats['users'] > 0 ? (int) round(($bar['count'] / $stats['users']) * 100) : 0;
                                $width = max(0, min(100, $pct));
                            ?>
                            <div class="an-bar-row">
                                <span class="an-bar-label"><?= htmlspecialchars($bar['label']) ?></span>
                                <span class="an-bar-track"><span class="an-bar-fill is-<?= $bar['key'] ?>" style="width:<?= $width ?>%;"></span></span>
                                <span class="an-bar-value"><?= $bar['count'] ?><span class="an-bar-pct"><?= $pct ?>%</span></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Courses -->
            <div class="tab-content" data-tab="tab-courses" id="tab-courses">
                <div class="an-card">
                    <h3 class="an-card-title"><i class="fas fa-trophy"></i> Top Courses by Enrollment</h3>
                    <?php if (empty($topCourses)): ?>
                    <div class="an-empty">
                        <i class="fas fa-book-open"></i>
                        <strong>No course data yet</strong>
                        <span>Courses will appear here once learners enroll.</span>
                    </div>
                    <?php else: ?>
                    <div class="an-table-wrap">
                        <table class="an-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th class="an-th-num">Enrolled</th>
                                    <th class="an-th-num">Completed</th>
                                    <th class="an-th-num">Completion</th>
                                    <th class="an-th-num">Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCourses as $i => $tc):
                                    $tcComp = $tc['enrollment_count'] > 0 ? (int) round(($tc['completed_count'] / $tc['enrollment_count']) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="an-course">
                                            <span class="an-rank"><?= $i + 1 ?></span>
                                            <span class="an-course-name" title="<?= htmlspecialchars($tc['title']) ?>"><?= htmlspecialchars($tc['title']) ?></span>
                                        </div>
                                    </td>
                                    <td class="an-td-num"><?= (int) $tc['enrollment_count'] ?></td>
                                    <td class="an-td-num"><?= (int) $tc['completed_count'] ?></td>
                                    <td class="an-td-num">
                                        <div class="an-completion">
                                            <span class="an-mini-track"><span class="an-mini-fill" style="width:<?= max(0, min(100, $tcComp)) ?>%;"></span></span>
                                            <span class="an-completion-val"><?= $tcComp ?>%</span>
                                        </div>
                                    </td>
                                    <td class="an-td-num">
                                        <?php if ((int) $tc['grade_count'] > 0): ?>
                                        <span class="an-score"><?= $tc['avg_score'] ?>%</span>
                                        <?php else: ?>
                                        <span class="an-muted" title="No grades issued yet">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Trends -->
            <div class="tab-content" data-tab="tab-trends" id="tab-trends">
                <div class="an-card">
                    <h3 class="an-card-title"><i class="fas fa-chart-line"></i> Enrollment Trends (Last 6 Months)</h3>
                    <?php if (empty($monthlyEnrollments)): ?>
                    <div class="an-empty">
                        <i class="fas fa-chart-line"></i>
                        <strong>Not enough data yet</strong>
                        <span>Enrollment activity will be charted here once it starts.</span>
                    </div>
                    <?php else:
                        $maxCnt = max(array_column($monthlyEnrollments, 'cnt'));
                        $maxCnt = max($maxCnt, 1);
                    ?>
                    <div class="an-chart">
                        <?php foreach ($monthlyEnrollments as $me):
                            $h = round(($me['cnt'] / $maxCnt) * 100);
                        ?>
                        <div class="an-chart-col">
                            <span class="an-chart-value"><?= (int) $me['cnt'] ?></span>
                            <span class="an-chart-bar" style="height:<?= max(2, (int) $h) ?>%;" title="<?= (int) $me['cnt'] ?> enrollments"></span>
                            <span class="an-chart-label"><?= date('M', strtotime($me['month'] . '-01')) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="an-card">
                    <h3 class="an-card-title"><i class="fas fa-chart-bar"></i> Grade Distribution</h3>
                    <?php if ($totalG === 0): ?>
                    <div class="an-empty">
                        <i class="fas fa-graduation-cap"></i>
                        <strong>No grades to display</strong>
                        <span>Grades will be charted here once quizzes are graded.</span>
                    </div>
                    <?php else: ?>
                    <div class="an-dist">
                        <?php foreach ($gradeBands as $band):
                            $barH = round(($band['count'] / $maxG) * 100);
                            $pct = round(($band['count'] / $totalG) * 100);
                        ?>
                        <div class="an-dist-col">
                            <span class="an-dist-count"><?= $band['count'] ?></span>
                            <span class="an-dist-pct"><?= $pct ?>%</span>
                            <span class="an-dist-bar an-grade-<?= $band['key'] ?>" style="height:<?= max(2, (int) $barH) ?>%;"></span>
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
                    <p class="an-note"><i class="fas fa-info-circle"></i> Based on the <?= $totalG ?> most recently issued grade<?= $totalG === 1 ? '' : 's' ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
