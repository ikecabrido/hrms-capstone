<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$stats = ['users' => 0, 'courses' => 0, 'active_enrollments' => 0, 'completed' => 0, 'instructors' => 0, 'learners' => 0, 'avg_score' => 0, 'programs' => 0, 'certificates' => 0, 'pending_reports' => 0];
$recentEnrollments = [];
$topCourses = [];
$recentActivity = [];
$upcomingEvents = [];

try {
    $pdo = (new Database())->getConnection();

    // Core stats
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM em_employees')->fetchColumn();
    $stats['courses'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE status IN ('active','draft')")->fetchColumn();
    $stats['active_enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status IN ('enrolled','in_progress')")->fetchColumn();
    $stats['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status = 'completed'")->fetchColumn();
    $stats['instructors'] = (int) $pdo->query("SELECT COUNT(DISTINCT instructor_id) FROM ld_course WHERE instructor_id IS NOT NULL")->fetchColumn();
    $stats['learners'] = (int) $pdo->query("SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment")->fetchColumn();
    $stats['avg_score'] = (float) ($pdo->query("SELECT ROUND(COALESCE(AVG(final_score),0),1) FROM ld_grade")->fetchColumn() ?? 0);
    $stats['programs'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_program WHERE status = 'active'")->fetchColumn();
    $stats['certificates'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_certificate WHERE status = 'active'")->fetchColumn();
    $stats['pending_reports'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_report WHERE status = 'pending'")->fetchColumn();

    // Recent enrollments
    $recentEnrollments = $pdo->query("
        SELECT e.status, e.enrolled_at, e.completed_at, c.title AS course_title,
               CONCAT(emp.first_name,' ',emp.last_name) AS learner_name
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        ORDER BY COALESCE(e.completed_at, e.enrolled_at) DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Top courses by enrollment
    $topCourses = $pdo->query("
        SELECT c.title, c.status, COUNT(e.id) AS enrollment_count,
               ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score,
               SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count
        FROM ld_course c
        LEFT JOIN ld_enrollment e ON e.course_id = c.id
        LEFT JOIN ld_grade g ON g.course_id = c.id
        WHERE c.status = 'active'
        GROUP BY c.id, c.title, c.status
        ORDER BY enrollment_count DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming video conferences
    $upcomingEvents = $pdo->query("
        SELECT vc.title, vc.scheduled_at, vc.platform, vc.duration_minutes,
               CONCAT(emp.first_name,' ',emp.last_name) AS host_name
        FROM ld_video_conference vc
        LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        WHERE vc.status = 'scheduled' AND vc.scheduled_at >= NOW()
        ORDER BY vc.scheduled_at ASC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

function adminTimeAgo($dt) {
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}

$completionRate = ($stats['active_enrollments'] + $stats['completed']) > 0
    ? round(($stats['completed'] / max(1, $stats['active_enrollments'] + $stats['completed'])) * 100) : 0;
?>
<div class="module-content">
    <div class="home-dashboard">

        <!-- Hero -->
        <section class="home-hero">
            <div class="home-hero-main">
                <h1><?= htmlspecialchars($employeeClass->getGreeting()) ?></h1>
                <p>Here is how the learning platform is performing today.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-users"></i> <?= (int) $stats['users'] ?> users</span>
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap"></i> <?= (int) $stats['courses'] ?> courses</span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $stats['avg_score'] ?>% avg score</span>
                </div>
            </div>
        </section>

        <!-- Key stats -->
        <div class="home-stats">
            <a class="home-stat" href="?page=admin/user">
                <span class="home-stat-icon tone-violet"><i class="fas fa-users"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Total Users</span>
                    <span class="home-stat-value"><?= (int) $stats['users'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['instructors'] ?> instructors &middot; <?= (int) $stats['learners'] ?> learners</span>
                </span>
            </a>
            <a class="home-stat" href="?page=admin/analytics">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-graduation-cap"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Active Courses</span>
                    <span class="home-stat-value"><?= (int) $stats['courses'] ?></span>
                    <span class="home-stat-sub"><?= $completionRate ?>% completion rate</span>
                </span>
            </a>
            <a class="home-stat" href="?page=admin/analytics">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-book-open"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Enrollments</span>
                    <span class="home-stat-value"><?= (int) ($stats['active_enrollments'] + $stats['completed']) ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['active_enrollments'] ?> active &middot; <?= (int) $stats['completed'] ?> completed</span>
                </span>
            </a>
            <div class="home-stat">
                <span class="home-stat-icon tone-amber"><i class="fas fa-star"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Avg Score</span>
                    <span class="home-stat-value"><?= $stats['avg_score'] ?>%</span>
                    <span class="home-stat-sub">across all courses</span>
                </span>
            </div>
        </div>

        <!-- Main column + sidebar -->
        <div class="home-columns">
            <div class="home-col">
                <!-- Top Courses -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-trophy"></i></span> Top Courses</h3>
                        <a class="home-card-link" href="?page=admin/analytics">View analytics <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (empty($topCourses)): ?>
                        <div class="home-empty"><i class="fas fa-trophy"></i><p>No active courses yet.</p></div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($topCourses as $i => $c): $courseCompletion = $c['enrollment_count'] > 0 ? min(100, round(($c['completed_count'] / max(1, $c['enrollment_count'])) * 100)) : 0; ?>
                            <div class="home-row">
                                <span class="home-row-icon tone-violet"><?= $i + 1 ?></span>
                                <span class="home-row-body">
                                    <span class="home-row-title"><?= htmlspecialchars($c['title']) ?></span>
                                    <span class="home-row-meta"><?= (int) $c['enrollment_count'] ?> enrolled &bull; <?= $c['avg_score'] ?>% avg</span>
                                </span>
                                <span class="home-progress" title="<?= (int) $c['completed_count'] ?> of <?= (int) $c['enrollment_count'] ?> enrollments completed">
                                    <span class="home-progress-track"><span class="home-progress-fill" style="width:<?= $courseCompletion ?>%"></span></span>
                                    <span class="home-progress-val"><?= $courseCompletion ?>%</span>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Enrollments -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-user-plus"></i></span> Recent Enrollments</h3>
                    </div>
                    <?php if (empty($recentEnrollments)): ?>
                        <div class="home-empty"><i class="fas fa-user-plus"></i><p>No enrollments yet.</p></div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($recentEnrollments as $r): ?>
                            <div class="home-row">
                                <span class="home-row-icon tone-<?= $r['status'] === 'completed' ? 'emerald' : 'amber' ?>"><i class="fas fa-user"></i></span>
                                <span class="home-row-body">
                                    <span class="home-row-title"><?= htmlspecialchars($r['learner_name'] ?? 'Unknown') ?></span>
                                    <span class="home-row-meta"><?= htmlspecialchars($r['course_title']) ?></span>
                                </span>
                                <span class="home-badge <?= $r['status'] === 'completed' ? 'is-success' : 'is-warn' ?>"><?= htmlspecialchars(str_replace('_', ' ', $r['status'])) ?></span>
                                <span class="home-row-meta" style="margin:0;"><?= adminTimeAgo($r['enrolled_at']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="home-col">
                <!-- Quick Access -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bolt"></i></span> Quick Access</h3>
                    </div>
                    <div class="home-quick">
                        <?php $links = [
                            ['icon'=>'fa-users-cog','label'=>'User Management','url'=>'?page=admin/user','tone'=>'indigo'],
                            ['icon'=>'fa-chart-bar','label'=>'Analytics Dashboard','url'=>'?page=admin/analytics','tone'=>'indigo'],
                            ['icon'=>'fa-calendar','label'=>'Calendar &amp; Events','url'=>'?page=admin/calendar','tone'=>'amber'],
                            ['icon'=>'fa-shield-alt','label'=>'Moderation','url'=>'?page=admin/moderation','tone'=>'amber'],
                            ['icon'=>'fa-bell','label'=>'Notifications','url'=>'?page=admin/notification','tone'=>'indigo'],
                            ['icon'=>'fa-user-circle','label'=>'My Profile','url'=>'?page=admin/profile','tone'=>'violet'],
                        ]; foreach ($links as $lk): ?>
                        <a class="home-quick-link" href="<?= $lk['url'] ?>">
                            <span class="home-quick-icon tone-<?= $lk['tone'] ?>"><i class="fas <?= $lk['icon'] ?>"></i></span>
                            <span class="home-quick-label"><?= $lk['label'] ?></span>
                            <i class="fas fa-chevron-right home-quick-chev"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-video"></i></span> Upcoming Events</h3>
                    </div>
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="home-empty"><i class="fas fa-video"></i><p>No upcoming events.</p></div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="home-row">
                                <span class="home-row-icon tone-indigo" style="flex-direction:column;line-height:1.05;">
                                    <span style="font-size:0.6rem;font-weight:700;text-transform:uppercase;"><?= date('M', strtotime($ev['scheduled_at'])) ?></span>
                                    <span style="font-size:0.95rem;font-weight:800;"><?= date('j', strtotime($ev['scheduled_at'])) ?></span>
                                </span>
                                <span class="home-row-body">
                                    <span class="home-row-title"><?= htmlspecialchars($ev['title']) ?></span>
                                    <span class="home-row-meta"><?= date('g:i A', strtotime($ev['scheduled_at'])) ?> &bull; <?= htmlspecialchars($ev['platform']) ?> &bull; <?= (int) $ev['duration_minutes'] ?>min</span>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Platform Health -->
                <div class="home-card">
                    <div class="home-card-head">
                        <h3 class="home-card-title"><span class="home-card-icon"><i class="fas fa-heartbeat"></i></span> Platform Health</h3>
                    </div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-book"></i> Total Courses</span><span class="home-kv-value"><?= (int) $stats['courses'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-project-diagram"></i> Programs</span><span class="home-kv-value"><?= (int) $stats['programs'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-certificate"></i> Certificates Issued</span><span class="home-kv-value"><?= (int) $stats['certificates'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-flag"></i> Pending Reports</span><span class="home-kv-value" <?= $stats['pending_reports'] > 0 ? 'style="color:var(--accent);"' : '' ?>><?= (int) $stats['pending_reports'] ?></span></div>
                    <div class="home-kv"><span class="home-kv-label"><i class="fas fa-chart-line"></i> Completion Rate</span><span class="home-kv-value"><?= $completionRate ?>%</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
