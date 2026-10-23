<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/progress.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$employeeId = (int) ($employeeClass->getEmployeeId() ?? 0);

$stats = [
    'my_enrollments' => 0, 'completed' => 0, 'in_progress' => 0,
    'avg_score' => 0, 'quizzes_taken' => 0, 'pass_rate' => 0,
    'total_modules' => 0, 'modules_completed' => 0,
    'certificates' => 0, 'time_spent' => 0,
];
$courseGrades = [];
$recentActivity = [];
$monthlyScores = [];
$weeklyActivity = [];
$skillBreakdown = [];
$streak = 0;

try {
    $pdo = (new Database())->getConnection();

    // Core stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid");
    $stmt->execute([':lid' => $employeeId]);
    $stats['my_enrollments'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status IN ('enrolled', 'in_progress')");
    $stmt->execute([':lid' => $employeeId]);
    $stats['in_progress'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(final_score), 0), 1) FROM ld_grade WHERE learner_id = :lid");
    $stmt->execute([':lid' => $employeeId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_session WHERE learner_id = :lid AND status IN ('submitted','expired')");
    $stmt->execute([':lid' => $employeeId]);
    $stats['quizzes_taken'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COUNT(CASE WHEN passed = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 1) FROM ld_quiz_session WHERE learner_id = :lid AND status IN ('submitted','expired')");
    $stmt->execute([':lid' => $employeeId]);
    $stats['pass_rate'] = (float) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :lid AND status = 'active'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['certificates'] = (int) $stmt->fetchColumn();

    // Module progress
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_progress p JOIN ld_enrollment e ON e.id = p.enrollment_id WHERE e.learner_id = :lid AND p.item_type = 'lesson' AND p.status = 'completed'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['modules_completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id JOIN ld_course c ON c.id = m.course_id JOIN ld_enrollment e ON e.course_id = c.id WHERE e.learner_id = :lid AND l.status = 'active' AND m.status = 'active'");
    $stmt->execute([':lid' => $employeeId]);
    $stats['total_modules'] = (int) $stmt->fetchColumn();

    // Time spent
    $stmt = $pdo->prepare("SELECT SUM(TIMESTAMPDIFF(SECOND, e.enrolled_at, COALESCE(e.completed_at, e.last_accessed_at))) FROM ld_enrollment e WHERE e.learner_id = :lid");
    $stmt->execute([':lid' => $employeeId]);
    $stats['time_spent'] = (int) ($stmt->fetchColumn() ?? 0);

    // Course grades for chart
    $stmt = $pdo->prepare("SELECT g.final_score, g.status, c.title FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE g.learner_id = :lid ORDER BY g.issued_at DESC LIMIT 10");
    $stmt->execute([':lid' => $employeeId]);
    $courseGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly scores for chart (last 6 months)
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(g.issued_at, '%Y-%m') AS month, ROUND(AVG(g.final_score), 1) AS avg_score, COUNT(*) AS courses FROM ld_grade g WHERE g.learner_id = :lid AND g.issued_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
    $stmt->execute([':lid' => $employeeId]);
    $monthlyScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $streak = (new Progress($pdo))->getStreakData($employeeId)['streak'];

    // Weekly activity heatmap (last 4 weeks)
    $stmt = $pdo->prepare("SELECT DATE(last_accessed_at) AS day, COUNT(DISTINCT course_id) AS courses_accessed FROM ld_enrollment WHERE learner_id = :lid AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) GROUP BY day ORDER BY day ASC");
    $stmt->execute([':lid' => $employeeId]);
    $weeklyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Skill breakdown
    $stmt = $pdo->prepare("SELECT s.name, COUNT(DISTINCT e.course_id) AS course_count, SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed FROM ld_skill s JOIN ld_course_skill cs ON cs.skill_id = s.id JOIN ld_enrollment e ON e.course_id = cs.course_id WHERE e.learner_id = :lid GROUP BY s.id, s.name ORDER BY completed DESC LIMIT 8");
    $stmt->execute([':lid' => $employeeId]);
    $skillBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // defaults stay at 0
}

$hours = floor($stats['time_spent'] / 3600);
$minutes = floor(($stats['time_spent'] % 3600) / 60);
$timeDisplay = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
$completionRate = $stats['my_enrollments'] > 0 ? round(($stats['completed'] / $stats['my_enrollments']) * 100) : 0;
$lessonCompletionRate = $stats['total_modules'] > 0 ? round(($stats['modules_completed'] / $stats['total_modules']) * 100) : 0;
?>
<div class="module-content an-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="la-title">
            <div class="home-hero-main">
                <h1 id="la-title">Analytics</h1>
                <p><?= $streak > 0 ? 'You have been learning for ' . $streak . ' consecutive day' . ($streak !== 1 ? 's' : '') . '. Keep it up!' : 'Your learning activity, scores and skills at a glance.' ?></p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-fire"></i> <?= (int) $streak ?> day<?= $streak === 1 ? '' : 's' ?> streak</span>
                    <span class="home-hero-chip"><i class="fas fa-book-open"></i> <?= (int) $stats['my_enrollments'] ?> enrolled</span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $stats['avg_score'] ?>% avg score</span>
                </div>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat is-link" title="Show the overview tab" onclick="document.querySelector('.tab-item[data-tab=tab-overview]').click()">
                <span class="home-stat-icon tone-violet"><i class="fas fa-graduation-cap"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Enrolled</span>
                    <span class="home-stat-value"><?= (int) $stats['my_enrollments'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['in_progress'] ?> active</span>
                </span>
            </div>
            <div class="home-stat is-link" title="Show the overview tab" onclick="document.querySelector('.tab-item[data-tab=tab-overview]').click()">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-circle-check"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Completed</span>
                    <span class="home-stat-value"><?= (int) $stats['completed'] ?></span>
                    <span class="home-stat-sub"><?= $completionRate ?>% completion</span>
                </span>
            </div>
            <div class="home-stat is-link" title="Show the performance tab" onclick="document.querySelector('.tab-item[data-tab=tab-performance]').click()">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub"><?= $stats['pass_rate'] ?>% pass rate</span>
                </span>
            </div>
            <div class="home-stat is-link" title="Show the performance tab" onclick="document.querySelector('.tab-item[data-tab=tab-performance]').click()">
                <span class="home-stat-icon tone-amber"><i class="fas fa-trophy"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Certificates</span>
                    <span class="home-stat-value"><?= (int) $stats['certificates'] ?></span>
                    <span class="home-stat-sub">earned</span>
                </span>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-overview" aria-label="Overview" title="Overview"><i class="fas fa-gauge-high" aria-hidden="true"></i><span class="tab-label">Overview</span></button>
                <button type="button" class="tab-item" data-tab="tab-performance" aria-label="Performance" title="Performance"><i class="fas fa-chart-line" aria-hidden="true"></i><span class="tab-label">Performance</span></button>
                <button type="button" class="tab-item" data-tab="tab-activity" aria-label="Activity" title="Activity"><i class="fas fa-calendar-check" aria-hidden="true"></i><span class="tab-label">Activity</span></button>
                <button type="button" class="tab-item" data-tab="tab-skills" aria-label="Skills" title="Skills"><i class="fas fa-star" aria-hidden="true"></i><span class="tab-label">Skills</span></button>
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
                                    <span class="an-kv-label">Courses enrolled</span>
                                    <span class="an-kv-value"><?= (int) $stats['my_enrollments'] ?></span>
                                </div>
                                <div class="an-kv">
                                    <span class="an-kv-label">In progress</span>
                                    <span class="an-kv-value"><?= (int) $stats['in_progress'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-list-check"></i> Lesson Progress</h3>
                        <div class="an-breakdown">
                            <div class="an-bar-row">
                                <span class="an-bar-label">Lessons</span>
                                <span class="an-bar-track"><span class="an-bar-fill" style="width:<?= max(0, min(100, (int) $lessonCompletionRate)) ?>%;"></span></span>
                                <span class="an-bar-value"><?= (int) $lessonCompletionRate ?>%</span>
                            </div>
                        </div>
                        <div class="an-kv-list" style="margin-top:0.9rem;">
                            <div class="an-kv">
                                <span class="an-kv-label">Lessons completed</span>
                                <span class="an-kv-value"><?= (int) $stats['modules_completed'] ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Total lessons</span>
                                <span class="an-kv-value"><?= (int) $stats['total_modules'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-clock"></i> Time Investment</h3>
                        <div class="an-kv-list">
                            <div class="an-kv">
                                <span class="an-kv-label">Total learning time</span>
                                <span class="an-kv-value"><?= $timeDisplay ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Quizzes taken</span>
                                <span class="an-kv-value"><?= (int) $stats['quizzes_taken'] ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Pass rate</span>
                                <span class="an-kv-value"><?= $stats['pass_rate'] ?>%</span>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-bar"></i> Course Scores</h3>
                        <?php if (empty($courseGrades)): ?>
                        <div class="an-empty">
                            <i class="fas fa-chart-bar"></i>
                            <strong>No grades recorded yet</strong>
                            <span>Your course scores will be listed here.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-table-wrap">
                            <table class="an-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="an-th-num">Score</th>
                                        <th class="an-th-num">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($courseGrades, 0, 5) as $grade):
                                        $gradePct = (int) round($grade['final_score']);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="an-course">
                                                <span class="an-course-name" title="<?= htmlspecialchars($grade['title']) ?>"><?= htmlspecialchars($grade['title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="an-td-num">
                                            <div class="an-completion">
                                                <span class="an-mini-track"><span class="an-mini-fill" style="width:<?= max(0, min(100, $gradePct)) ?>%;"></span></span>
                                                <span class="an-completion-val"><?= round($grade['final_score'], 1) ?>%</span>
                                            </div>
                                        </td>
                                        <td class="an-td-num"><span class="an-pill <?= $grade['status'] === 'passed' ? 'is-good' : 'is-bad' ?>"><?= ucfirst($grade['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== PERFORMANCE ==================== -->
            <div class="tab-content" data-tab="tab-performance">
                <div class="an-cols">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-line"></i> Score Trend</h3>
                        <?php if (empty($monthlyScores)): ?>
                        <div class="an-empty">
                            <i class="fas fa-chart-line"></i>
                            <strong>Not enough data yet</strong>
                            <span>Complete courses to see your score trend.</span>
                        </div>
                        <?php else:
                            $maxScore = max(100, max(array_column($monthlyScores, 'avg_score')));
                        ?>
                        <div class="an-chart">
                            <?php foreach ($monthlyScores as $ms):
                                $msH = round(($ms['avg_score'] / $maxScore) * 100);
                            ?>
                            <div class="an-chart-col">
                                <span class="an-chart-value"><?= $ms['avg_score'] ?>%</span>
                                <span class="an-chart-bar" style="height:<?= max(2, (int) $msH) ?>%;" title="<?= $ms['avg_score'] ?>% average"></span>
                                <span class="an-chart-label"><?= date('M', strtotime($ms['month'] . '-01')) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-chart-bar"></i> Grade Distribution</h3>
                        <?php
                        $dist = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'f' => 0];
                        foreach ($courseGrades as $cg) {
                            $cgScore = (float) $cg['final_score'];
                            if ($cgScore >= 90) { $dist['a']++; }
                            elseif ($cgScore >= 80) { $dist['b']++; }
                            elseif ($cgScore >= 70) { $dist['c']++; }
                            elseif ($cgScore >= 60) { $dist['d']++; }
                            else { $dist['f']++; }
                        }
                        $totalGrades = count($courseGrades);
                        $gradeBands = [
                            ['key' => 'a', 'label' => 'A', 'range' => '90-100'],
                            ['key' => 'b', 'label' => 'B', 'range' => '80-89'],
                            ['key' => 'c', 'label' => 'C', 'range' => '70-79'],
                            ['key' => 'd', 'label' => 'D', 'range' => '60-69'],
                            ['key' => 'f', 'label' => 'F', 'range' => 'Below 60'],
                        ];
                        $maxBand = max(1, max(array_values($dist)));
                        ?>
                        <?php if ($totalGrades === 0): ?>
                        <div class="an-empty">
                            <i class="fas fa-graduation-cap"></i>
                            <strong>No grades to display</strong>
                            <span>Your graded courses will be charted here.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-dist">
                            <?php foreach ($gradeBands as $band):
                                $bandCount = (int) $dist[$band['key']];
                                $bandH = round(($bandCount / $maxBand) * 100);
                                $bandPct = (int) round(($bandCount / $totalGrades) * 100);
                            ?>
                            <div class="an-dist-col">
                                <span class="an-dist-count"><?= $bandCount ?></span>
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
                        <p class="an-note"><i class="fas fa-info-circle"></i> Based on your <?= $totalGrades ?> most recent grade<?= $totalGrades === 1 ? '' : 's' ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-clipboard-check"></i> Quiz Performance</h3>
                        <div class="an-kv-list">
                            <div class="an-kv">
                                <span class="an-kv-label">Total attempts</span>
                                <span class="an-kv-value"><?= (int) $stats['quizzes_taken'] ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Pass rate</span>
                                <span class="an-kv-value"><?= $stats['pass_rate'] ?>%</span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Average quiz score</span>
                                <span class="an-kv-value"><?= $stats['avg_score'] ?>%</span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Certificates earned</span>
                                <span class="an-kv-value"><?= (int) $stats['certificates'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== ACTIVITY ==================== -->
            <div class="tab-content" data-tab="tab-activity">
                <div class="an-stack">
                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-calendar-check"></i> Learning Activity (Last 4 Weeks)</h3>
                        <?php
                        $heatMap = [];
                        foreach ($weeklyActivity as $wa) {
                            $heatMap[$wa['day']] = (int) $wa['courses_accessed'];
                        }
                        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        $startDate = new DateTime('-27 days');
                        $startDate->modify('-' . $startDate->format('w') . ' days');
                        $today = new DateTime('today');
                        ?>
                        <div class="an-heatmap">
                            <div></div>
                            <?php for ($w = 0; $w < 4; $w++): ?>
                            <div class="an-heatmap-head">W<?= $w + 1 ?></div>
                            <?php endfor; ?>
                            <?php for ($d = 0; $d < 7; $d++): ?>
                            <div class="an-heatmap-day"><?= $dayNames[$d] ?></div>
                                <?php for ($w = 0; $w < 4; $w++):
                                    $cellDate = clone $startDate;
                                    $cellDate->modify('+' . ($w * 7 + $d) . ' days');
                                    $dateStr = $cellDate->format('Y-m-d');
                                    $count = $heatMap[$dateStr] ?? 0;
                                    $isFuture = $cellDate > $today;
                                    $level = $count >= 3 ? ' is-3' : ($count >= 2 ? ' is-2' : ($count >= 1 ? ' is-1' : ''));
                                ?>
                                <div class="an-heat-cell<?= $isFuture ? ' is-future' : $level ?>" title="<?= $dateStr ?>: <?= $count ?> course<?= $count === 1 ? '' : 's' ?>"><?= $isFuture ? '' : $cellDate->format('j') ?></div>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="an-heatmap-legend">
                            <span>Less</span>
                            <span class="an-heat-swatch an-heat-cell"></span>
                            <span class="an-heat-swatch an-heat-cell is-1"></span>
                            <span class="an-heat-swatch an-heat-cell is-2"></span>
                            <span class="an-heat-swatch an-heat-cell is-3"></span>
                            <span>More</span>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-stopwatch"></i> Time Breakdown</h3>
                        <div class="an-kv-list">
                            <div class="an-kv">
                                <span class="an-kv-label">Total learning time</span>
                                <span class="an-kv-value"><?= $timeDisplay ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Courses completed</span>
                                <span class="an-kv-value"><?= (int) $stats['completed'] ?></span>
                            </div>
                            <div class="an-kv">
                                <span class="an-kv-label">Quizzes attempted</span>
                                <span class="an-kv-value"><?= (int) $stats['quizzes_taken'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="an-card">
                        <h3 class="an-card-title"><i class="fas fa-trophy"></i> Achievements</h3>
                        <?php
                        $achievements = [];
                        if ($streak >= 3) { $achievements[] = ['icon' => 'fa-fire', 'label' => '3-Day Streak']; }
                        if ($streak >= 7) { $achievements[] = ['icon' => 'fa-fire', 'label' => '7-Day Streak']; }
                        if ($stats['completed'] >= 1) { $achievements[] = ['icon' => 'fa-graduation-cap', 'label' => 'First Course Completed']; }
                        if ($stats['completed'] >= 5) { $achievements[] = ['icon' => 'fa-trophy', 'label' => '5 Courses Completed']; }
                        if ($stats['certificates'] >= 1) { $achievements[] = ['icon' => 'fa-certificate', 'label' => 'First Certificate']; }
                        if ($stats['avg_score'] >= 90) { $achievements[] = ['icon' => 'fa-star', 'label' => 'Score Master (90%+)']; }
                        if ($stats['quizzes_taken'] >= 10) { $achievements[] = ['icon' => 'fa-clipboard-check', 'label' => 'Quiz Champion (10+)']; }
                        ?>
                        <?php if (empty($achievements)): ?>
                        <div class="an-empty">
                            <i class="fas fa-medal"></i>
                            <strong>No achievements yet</strong>
                            <span>Keep learning to unlock your first badge.</span>
                        </div>
                        <?php else: ?>
                        <div class="an-badges">
                            <?php foreach ($achievements as $ach): ?>
                            <span class="an-badge"><i class="fas <?= $ach['icon'] ?>"></i> <?= $ach['label'] ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== SKILLS ==================== -->
            <div class="tab-content" data-tab="tab-skills">
                <div class="an-card">
                    <h3 class="an-card-title"><i class="fas fa-star"></i> Skill Breakdown</h3>
                    <?php if (empty($skillBreakdown)): ?>
                    <div class="an-empty">
                        <i class="fas fa-award"></i>
                        <strong>No skills data yet</strong>
                        <span>Complete courses to build your skill profile.</span>
                    </div>
                    <?php else: ?>
                    <div class="an-breakdown">
                        <?php foreach ($skillBreakdown as $skill):
                            $skillPct = $skill['course_count'] > 0 ? (int) round(($skill['completed'] / $skill['course_count']) * 100) : 0;
                        ?>
                        <div class="an-bar-row">
                            <span class="an-bar-label" title="<?= htmlspecialchars($skill['name']) ?>"><?= htmlspecialchars($skill['name']) ?></span>
                            <span class="an-bar-track"><span class="an-bar-fill" style="width:<?= max(0, min(100, $skillPct)) ?>%;"></span></span>
                            <span class="an-bar-value"><?= (int) $skill['completed'] ?>/<?= (int) $skill['course_count'] ?><span class="an-bar-pct"><?= $skillPct ?>%</span></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
