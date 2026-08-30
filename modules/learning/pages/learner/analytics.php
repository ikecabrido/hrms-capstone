<?php
include_once __DIR__ . '/../../classes/Employee.php';
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

    // Learning streak — consecutive days with activity
    $stmt = $pdo->prepare("SELECT DISTINCT DATE(last_accessed_at) AS active_day FROM ld_enrollment WHERE learner_id = :lid AND last_accessed_at IS NOT NULL ORDER BY active_day DESC LIMIT 30");
    $stmt->execute([':lid' => $employeeId]);
    $activeDays = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $streak = 0;
    $today = (new DateTime())->format('Y-m-d');
    $checkDate = new DateTime();
    if (!empty($activeDays) && $activeDays[0] === $today) {
        $streak = 1;
        for ($i = 1; $i < count($activeDays); $i++) {
            $checkDate->modify('-1 day');
            if ($activeDays[$i] === $checkDate->format('Y-m-d')) {
                $streak++;
            } else {
                break;
            }
        }
    } elseif (!empty($activeDays)) {
        $yesterday = (new DateTime('-1 day'))->format('Y-m-d');
        if ($activeDays[0] === $yesterday) {
            $streak = 1;
            $checkDate->modify('-1 day');
            for ($i = 1; $i < count($activeDays); $i++) {
                $checkDate->modify('-1 day');
                if ($activeDays[$i] === $checkDate->format('Y-m-d')) {
                    $streak++;
                } else {
                    break;
                }
            }
        }
    }

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
<div class="module-content analytics-dashboard">
    <div class="toolbar">
        
    </div>

    <!-- Learning Streak Banner -->
    <div class="mode-card" style="background:linear-gradient(135deg, rgba(255,152,0,0.1), rgba(255,193,7,0.05)); border-left:4px solid var(--accent); margin-bottom:1.5rem; display:flex; align-items:center; gap:1.5rem; padding:1.5rem 2rem;">
        <div style="font-size:3rem; color:var(--accent);">
            <i class="fas fa-fire"></i>
        </div>
        <div>
            <h2 style="margin:0; color:var(--accent); font-size:1.8rem;"><?= $streak ?> Day<?= $streak !== 1 ? 's' : '' ?> Streak!</h2>
            <p style="margin:0.25rem 0 0 0; color:#666;">
                <?= $streak > 0 ? "You've been learning for $streak consecutive day" . ($streak !== 1 ? 's' : '') . ". Keep it up!" : "Start learning today to build your streak!" ?>
            </p>
        </div>
    </div>

    <!-- Primary Stats -->
    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="border-top:3px solid var(--primary);">
            <h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Enrolled</h2>
            <p class="analytics-value"><?= $stats['my_enrollments'] ?></p>
            <div style="font-size:0.85rem; color:#999;"><?= $stats['in_progress'] ?> active</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--primary);">
            <h2><i class="fas fa-check-circle" style="margin-right:0.4rem;opacity:0.6;"></i> Completed</h2>
            <p class="analytics-value"><?= $stats['completed'] ?></p>
            <div style="font-size:0.85rem; color:#999;"><?= $completionRate ?>% completion</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--accent);">
            <h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2>
            <p class="analytics-value"><?= $stats['avg_score'] ?>%</p>
            <div style="font-size:0.85rem; color:#999;"><?= $stats['pass_rate'] ?>% pass rate</div>
        </div>
        <div class="analytics-card" style="border-top:3px solid var(--accent);">
            <h2><i class="fas fa-trophy" style="margin-right:0.4rem;opacity:0.6;"></i> Certificates</h2>
            <p class="analytics-value"><?= $stats['certificates'] ?></p>
            <div style="font-size:0.85rem; color:#999;">earned</div>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-overview">Overview</button>
            <button type="button" class="tab-item" data-tab="tab-performance">Performance</button>
            <button type="button" class="tab-item" data-tab="tab-activity">Activity</button>
            <button type="button" class="tab-item" data-tab="tab-skills">Skills</button>
        </div>

        <!-- Overview Tab -->
        <div class="tab-content active" data-tab="tab-overview">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                <!-- Course Completion Gauge -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-pie" style="color:var(--primary); margin-right:0.5rem;"></i> Course Completion</h3>
                    <div style="display:flex; align-items:center; gap:2rem; padding:1.5rem 0;">
                        <div style="position:relative; width:120px; height:120px;">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="var(--border)" stroke-width="12"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary)" stroke-width="12"
                                    stroke-dasharray="<?= $completionRate * 3.14 ?> <?= 314 - ($completionRate * 3.14) ?>"
                                    stroke-dashoffset="78.5" stroke-linecap="round" style="transform:rotate(-90deg);transform-origin:center;transition:stroke-dasharray 1s;"/>
                            </svg>
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:1.5rem; font-weight:700; color:var(--primary);"><?= $completionRate ?>%</div>
                        </div>
                        <div>
                            <p style="margin:0; font-size:1.1rem;"><strong><?= $stats['completed'] ?></strong> courses completed</p>
                            <p style="margin:0.25rem 0; color:#666;">out of <?= $stats['my_enrollments'] ?> enrolled</p>
                            <p style="margin:0.25rem 0; color:#999; font-size:0.9rem;"><?= $stats['in_progress'] ?> currently in progress</p>
                        </div>
                    </div>
                </div>

                <!-- Lesson Progress -->
                <div class="mode-card">
                    <h3><i class="fas fa-tasks" style="color:var(--primary); margin-right:0.5rem;"></i> Lesson Progress</h3>
                    <div style="padding:1.5rem 0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                            <span>Lessons Completed</span>
                            <strong style="color:var(--primary);"><?= $lessonCompletionRate ?>%</strong>
                        </div>
                        <div style="background:var(--border); height:12px; border-radius:6px; overflow:hidden; margin-bottom:1rem;">
                            <div style="background:linear-gradient(90deg, var(--primary), var(--text)); height:100%; width:<?= $lessonCompletionRate ?>%; border-radius:6px;"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; color:#666; font-size:0.9rem;">
                            <span><?= $stats['modules_completed'] ?> completed</span>
                            <span><?= $stats['total_modules'] ?> total lessons</span>
                        </div>
                    </div>
                </div>

                <!-- Time Investment -->
                <div class="mode-card">
                    <h3><i class="fas fa-clock" style="color:var(--primary); margin-right:0.5rem;"></i> Time Investment</h3>
                    <div style="padding:1.5rem 0; text-align:center;">
                        <div style="font-size:2.5rem; font-weight:700; color:var(--primary);"><?= $timeDisplay ?></div>
                        <p style="color:#666; margin:0.5rem 0;">total learning time</p>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem; text-align:center;">
                            <div style="padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                                <div style="font-size:1.2rem; font-weight:600;"><?= $stats['quizzes_taken'] ?></div>
                                <div style="font-size:0.85rem; color:#999;">Quizzes Taken</div>
                            </div>
                            <div style="padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                                <div style="font-size:1.2rem; font-weight:600;"><?= $stats['pass_rate'] ?>%</div>
                                <div style="font-size:0.85rem; color:#999;">Pass Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Course Grades -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-bar" style="color:var(--primary); margin-right:0.5rem;"></i> Course Scores</h3>
                    <?php if (empty($courseGrades)): ?>
                        <p style="color:#999; text-align:center; padding:2rem 0;">No grades recorded yet.</p>
                    <?php else: ?>
                        <div style="padding:1rem 0;">
                            <?php foreach (array_slice($courseGrades, 0, 5) as $grade): ?>
                                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                                    <span style="flex:1; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(mb_substr($grade['title'], 0, 30)) ?></span>
                                    <div style="width:120px; background:var(--border); height:8px; border-radius:4px; overflow:hidden; flex-shrink:0;">
                                        <div style="background:<?= $grade['status'] === 'passed' ? 'var(--primary)' : 'var(--danger)' ?>; height:100%; width:<?= min(100, round($grade['final_score'])) ?>%;"></div>
                                    </div>
                                    <span style="font-weight:600; color:var(--primary); min-width:45px; text-align:right; font-size:0.9rem;"><?= round($grade['final_score'], 1) ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div class="tab-content" data-tab="tab-performance">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                <!-- Monthly Score Trend -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-line" style="color:var(--primary); margin-right:0.5rem;"></i> Score Trend</h3>
                    <?php if (empty($monthlyScores)): ?>
                        <p style="color:#999; text-align:center; padding:2rem 0;">Not enough data yet. Complete courses to see trends.</p>
                    <?php else: ?>
                        <div style="padding:1.5rem 0;">
                            <div style="display:flex; align-items:flex-end; gap:0.5rem; height:160px; padding:0 0.5rem;">
                                <?php
                                $maxScore = max(array_column($monthlyScores, 'avg_score'));
                                $maxScore = max($maxScore, 100);
                                foreach ($monthlyScores as $ms):
                                    $height = round(($ms['avg_score'] / $maxScore) * 140);
                                ?>
                                    <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.25rem;">
                                        <span style="font-size:0.75rem; font-weight:600; color:var(--primary);"><?= $ms['avg_score'] ?>%</span>
                                        <div style="width:100%; max-width:50px; height:<?= $height ?>px; background:linear-gradient(180deg, var(--primary), var(--text)); border-radius:6px 6px 0 0; transition:height 0.5s;"></div>
                                        <span style="font-size:0.7rem; color:#999;"><?= date('M', strtotime($ms['month'] . '-01')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Grade Distribution -->
                <div class="mode-card">
                    <h3><i class="fas fa-chart-bar" style="color:var(--primary); margin-right:0.5rem;"></i> Grade Distribution</h3>
                    <?php
                    $distA = 0; $distB = 0; $distC = 0; $distF = 0;
                    foreach ($courseGrades as $g) {
                        $s = (float) $g['final_score'];
                        if ($s >= 90) $distA++;
                        elseif ($s >= 80) $distB++;
                        elseif ($s >= 70) $distC++;
                        else $distF++;
                    }
                    $totalGrades = count($courseGrades);
                    ?>
                    <div style="padding:1.5rem 0;">
                        <?php
                        $distributions = [
                            ['label' => 'A (90-100%)', 'count' => $distA, 'color' => 'var(--primary)'],
                            ['label' => 'B (80-89%)', 'count' => $distB, 'color' => 'var(--accent)'],
                            ['label' => 'C (70-79%)', 'count' => $distC, 'color' => 'var(--accent)'],
                            ['label' => 'F (<70%)', 'count' => $distF, 'color' => 'var(--danger)'],
                        ];
                        foreach ($distributions as $d):
                            $pct = $totalGrades > 0 ? round(($d['count'] / $totalGrades) * 100) : 0;
                        ?>
                            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                                <span style="width:80px; font-size:0.85rem; color:#666;"><?= $d['label'] ?></span>
                                <div style="flex:1; background:var(--border); height:12px; border-radius:6px; overflow:hidden;">
                                    <div style="background:<?= $d['color'] ?>; height:100%; width:<?= $pct ?>%; border-radius:6px;"></div>
                                </div>
                                <span style="width:40px; font-weight:600; font-size:0.9rem; text-align:right;"><?= $d['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($totalGrades === 0): ?>
                            <p style="color:#999; text-align:center;">No grades to display.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quiz Performance Summary -->
                <div class="mode-card" style="grid-column: span 2;">
                    <h3><i class="fas fa-clipboard-check" style="color:var(--primary); margin-right:0.5rem;"></i> Quiz Performance Summary</h3>
                    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; padding:1.5rem 0;">
                        <div style="text-align:center; padding:1rem; background:var(--bg-subtle); border-radius:10px;">
                            <div style="font-size:1.8rem; font-weight:700; color:var(--primary);"><?= $stats['quizzes_taken'] ?></div>
                            <div style="font-size:0.85rem; color:#999;">Total Attempts</div>
                        </div>
                        <div style="text-align:center; padding:1rem; background:var(--bg-subtle); border-radius:10px;">
                            <div style="font-size:1.8rem; font-weight:700; color:var(--primary);"><?= $stats['pass_rate'] ?>%</div>
                            <div style="font-size:0.85rem; color:#999;">Pass Rate</div>
                        </div>
                        <div style="text-align:center; padding:1rem; background:var(--bg-subtle); border-radius:10px;">
                            <div style="font-size:1.8rem; font-weight:700; color:var(--accent);"><?= $stats['avg_score'] ?>%</div>
                            <div style="font-size:0.85rem; color:#999;">Avg Quiz Score</div>
                        </div>
                        <div style="text-align:center; padding:1rem; background:var(--bg-subtle); border-radius:10px;">
                            <div style="font-size:1.8rem; font-weight:700; color:var(--accent);"><?= $stats['certificates'] ?></div>
                            <div style="font-size:0.85rem; color:#999;">Certificates</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div class="tab-content" data-tab="tab-activity">
            <!-- Activity Heatmap -->
            <div class="mode-card">
                <h3><i class="fas fa-calendar-check" style="color:var(--primary); margin-right:0.5rem;"></i> Learning Activity (Last 4 Weeks)</h3>
                <div style="padding:1.5rem 0;">
                    <?php
                    $heatMap = [];
                    foreach ($weeklyActivity as $wa) {
                        $heatMap[$wa['day']] = (int) $wa['courses_accessed'];
                    }
                    $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    // Build a 7x4 grid
                    $startDate = new DateTime('-27 days');
                    $startDate->modify('-' . $startDate->format('w') . ' days'); // Start on Sunday
                    ?>
                    <div style="display:grid; grid-template-columns:40px repeat(4, 1fr); gap:4px; max-width:500px;">
                        <div></div>
                        <?php for ($w = 0; $w < 4; $w++): ?>
                            <div style="text-align:center; font-size:0.75rem; color:#999; font-weight:600;">W<?= $w + 1 ?></div>
                        <?php endfor; ?>
                        <?php for ($d = 0; $d < 7; $d++): ?>
                            <div style="font-size:0.75rem; color:#999; display:flex; align-items:center; justify-content:flex-end; padding-right:4px;"><?= $dayNames[$d] ?></div>
                            <?php for ($w = 0; $w < 4; $w++):
                                $cellDate = clone $startDate;
                                $cellDate->modify('+' . ($w * 7 + $d) . ' days');
                                $dateStr = $cellDate->format('Y-m-d');
                                $count = $heatMap[$dateStr] ?? 0;
                                $isFuture = $cellDate > new DateTime();
                                $bg = $isFuture ? 'var(--bg-subtle)' : ($count === 0 ? '#eee' : ($count >= 3 ? 'var(--primary)' : ($count >= 2 ? 'var(--primary)' : 'var(--primary)')));
                            ?>
                                <div style="aspect-ratio:1; background:<?= $bg ?>; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:<?= $count > 0 ? '#fff' : '#ccc' ?>; font-weight:600;"
                                     title="<?= $dateStr ?>: <?= $count ?> course<?= $count !== 1 ? 's' : '' ?>">
                                    <?= $cellDate->format('j') ?>
                                </div>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-top:1rem; font-size:0.8rem; color:#999;">
                        <span>Less</span>
                        <span style="width:14px; height:14px; background:#eee; border-radius:2px;"></span>
                        <span style="width:14px; height:14px; background:var(--primary); border-radius:2px;"></span>
                        <span style="width:14px; height:14px; background:var(--primary); border-radius:2px;"></span>
                        <span style="width:14px; height:14px; background:var(--primary); border-radius:2px;"></span>
                        <span>More</span>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-top:1.5rem;">
                <div class="mode-card">
                    <h3><i class="fas fa-stopwatch" style="color:var(--primary); margin-right:0.5rem;"></i> Time Breakdown</h3>
                    <div style="padding:1rem 0;">
                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:rgba(32,0,130,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center;"><i class="fas fa-hourglass-half"></i></div>
                            <div><strong><?= $timeDisplay ?></strong><br><span style="color:#999; font-size:0.85rem;">Total Learning Time</span></div>
                        </div>
                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:rgba(40,167,69,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center;"><i class="fas fa-graduation-cap"></i></div>
                            <div><strong><?= $stats['completed'] ?> courses</strong><br><span style="color:#999; font-size:0.85rem;">Completed</span></div>
                        </div>
                        <div style="display:flex; align-items:center; gap:1rem; padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:rgba(255,193,7,0.1); color:var(--accent); display:flex; align-items:center; justify-content:center;"><i class="fas fa-redo"></i></div>
                            <div><strong><?= $stats['quizzes_taken'] ?> quizzes</strong><br><span style="color:#999; font-size:0.85rem;">Attempted</span></div>
                        </div>
                    </div>
                </div>

                <div class="mode-card">
                    <h3><i class="fas fa-trophy" style="color:var(--primary); margin-right:0.5rem;"></i> Achievements</h3>
                    <div style="padding:1rem 0; display:grid; gap:0.75rem;">
                        <?php
                        $achievements = [];
                        if ($streak >= 3) $achievements[] = ['icon' => 'fa-fire', 'label' => '3-Day Streak', 'color' => 'var(--accent)'];
                        if ($streak >= 7) $achievements[] = ['icon' => 'fa-fire', 'label' => '7-Day Streak', 'color' => 'var(--danger)'];
                        if ($stats['completed'] >= 1) $achievements[] = ['icon' => 'fa-graduation-cap', 'label' => 'First Course Completed', 'color' => 'var(--primary)'];
                        if ($stats['completed'] >= 5) $achievements[] = ['icon' => 'fa-trophy', 'label' => '5 Courses Completed', 'color' => 'var(--accent)'];
                        if ($stats['certificates'] >= 1) $achievements[] = ['icon' => 'fa-certificate', 'label' => 'First Certificate', 'color' => 'var(--accent)'];
                        if ($stats['avg_score'] >= 90) $achievements[] = ['icon' => 'fa-star', 'label' => 'Score Master (90%+)', 'color' => 'var(--accent)'];
                        if ($stats['quizzes_taken'] >= 10) $achievements[] = ['icon' => 'fa-clipboard-check', 'label' => 'Quiz Champion (10+)', 'color' => 'var(--accent)'];
                        ?>
                        <?php if (empty($achievements)): ?>
                            <div style="text-align:center; padding:1rem; color:#999;">
                                <i class="fas fa-medal" style="font-size:2rem; display:block; margin-bottom:0.5rem; color:#ddd;"></i>
                                <p>Keep learning to unlock achievements!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($achievements as $ach): ?>
                                <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:var(--bg-subtle); border-radius:8px;">
                                    <div style="width:36px; height:36px; border-radius:50%; background:<?= $ach['color'] ?>; color:#fff; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas <?= $ach['icon'] ?>"></i>
                                    </div>
                                    <span style="font-weight:500;"><?= $ach['label'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skills Tab -->
        <div class="tab-content" data-tab="tab-skills">
            <div class="mode-card">
                <h3><i class="fas fa-star" style="color:var(--primary); margin-right:0.5rem;"></i> Skill Breakdown</h3>
                <?php if (empty($skillBreakdown)): ?>
                    <div style="padding:3rem; text-align:center; background:var(--bg-subtle); border-radius:12px;">
                        <i class="fas fa-award" style="font-size:3rem; color:#ccc; margin-bottom:1rem; display:block;"></i>
                        <h3>No skills data yet</h3>
                        <p style="color:#999;">Complete courses to build your skill profile.</p>
                    </div>
                <?php else: ?>
                    <div style="padding:1rem 0;">
                        <?php foreach ($skillBreakdown as $skill):
                            $pct = $skill['course_count'] > 0 ? round(($skill['completed'] / $skill['course_count']) * 100) : 0;
                        ?>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                                <div style="width:140px; font-weight:500; font-size:0.95rem;"><?= htmlspecialchars($skill['name']) ?></div>
                                <div style="flex:1; background:var(--border); height:10px; border-radius:5px; overflow:hidden;">
                                    <div style="background:linear-gradient(90deg, var(--primary), var(--text)); height:100%; width:<?= $pct ?>%;"></div>
                                </div>
                                <div style="min-width:80px; text-align:right; font-size:0.85rem;">
                                    <strong style="color:var(--primary);"><?= $skill['completed'] ?>/<?= $skill['course_count'] ?></strong>
                                    <span style="color:#999;"> courses</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
