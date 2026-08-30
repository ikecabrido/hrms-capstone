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
<div class="module-header">
    <h1 class="module-header-title"><?= htmlspecialchars($employeeClass->getGreeting()) ?></h1>
</div>

<div class="module-content">
    <!-- Overview + Quick Access side by side -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
        <!-- Overview -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem 0;"><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i> Overview</h3>
            <div class="analytics-cards">
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=admin/analytics'">
                    <h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> Total Users</h2>
                    <p class="analytics-value"><?= $stats['users'] ?></p>
                    <div style="font-size:0.85rem;color:#999;"><?= $stats['instructors'] ?> instructors &middot; <?= $stats['learners'] ?> learners</div>
                </div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=admin/analytics'">
                    <h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Active Courses</h2>
                    <p class="analytics-value"><?= $stats['courses'] ?></p>
                    <div style="font-size:0.85rem;color:#999;"><?= $completionRate ?>% completion rate</div>
                </div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=admin/analytics'">
                    <h2><i class="fas fa-book-open" style="margin-right:0.4rem;opacity:0.6;"></i> Enrollments</h2>
                    <p class="analytics-value"><?= $stats['active_enrollments'] + $stats['completed'] ?></p>
                    <div style="font-size:0.85rem;color:#999;"><?= $stats['active_enrollments'] ?> active &middot; <?= $stats['completed'] ?> completed</div>
                </div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=admin/analytics'">
                    <h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2>
                    <p class="analytics-value"><?= $stats['avg_score'] ?>%</p>
                    <div style="font-size:0.85rem;color:#999;">across all courses</div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem 0;"><i class="fas fa-bolt" style="color:var(--accent);margin-right:0.5rem;"></i> Quick Access</h3>
            <div style="display:grid;gap:0.5rem;">
                <?php $links = [
                    ['icon'=>'fa-users-cog','label'=>'User Management','url'=>'?page=admin/user','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-chart-bar','label'=>'Analytics Dashboard','url'=>'?page=admin/analytics','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-calendar','label'=>'Calendar & Events','url'=>'?page=admin/calendar','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
                    ['icon'=>'fa-shield-alt','label'=>'Moderation','url'=>'?page=admin/moderation','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
                    ['icon'=>'fa-bell','label'=>'Notifications','url'=>'?page=admin/notification','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-user-circle','label'=>'My Profile','url'=>'?page=admin/profile','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
                ]; foreach ($links as $lk): ?>
                <a href="<?= $lk['url'] ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.7rem 1rem;background:var(--bg-subtle);border-radius:8px;text-decoration:none;color:var(--text);font-size:0.9rem;">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?= $lk['bg'] ?>;color:<?= $lk['color'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas <?= $lk['icon'] ?>"></i></div>
                    <span style="flex:1;font-weight:500;"><?= $lk['label'] ?></span>
                    <i class="fas fa-chevron-right" style="font-size:0.7rem;color:#999;"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

        <!-- Top Courses -->
        <div class="mode-card">
            <h3><i class="fas fa-trophy" style="color:var(--accent);margin-right:0.5rem;"></i> Top Courses</h3>
            <?php if (empty($topCourses)): ?>
                <p style="color:#999;text-align:center;padding:1.5rem;">No active courses yet.</p>
            <?php else: ?>
                <div style="display:grid;gap:0.6rem;margin-top:0.75rem;">
                    <?php foreach ($topCourses as $i => $c): ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                            <span style="width:24px;height:24px;border-radius:50%;background:<?= ['var(--primary)','var(--accent)','var(--primary)','var(--accent)','var(--accent)'][$i] ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;flex-shrink:0;"><?= $i + 1 ?></span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.85rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($c['title']) ?></div>
                                <div style="font-size:0.75rem;color:#999;"><?= $c['enrollment_count'] ?> enrolled · <?= $c['avg_score'] ?>% avg</div>
                            </div>
                            <?php $courseCompletion = $c['enrollment_count'] > 0 ? min(100, round(($c['completed_count'] / max(1, $c['enrollment_count'])) * 100)) : 0; ?>
                            <div title="<?= (int) $c['completed_count'] ?> of <?= (int) $c['enrollment_count'] ?> enrollments completed" style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">
                                <div style="width:40px;height:6px;background:#eee;border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:<?= $courseCompletion ?>%;background:var(--primary);border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.75rem;font-weight:700;color:var(--primary);white-space:nowrap;min-width:34px;text-align:right;"><?= $courseCompletion ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Enrollments -->
        <div class="mode-card">
            <h3><i class="fas fa-user-plus" style="color:var(--primary);margin-right:0.5rem;"></i> Recent Enrollments</h3>
            <?php if (empty($recentEnrollments)): ?>
                <p style="color:#999;text-align:center;padding:1.5rem;">No enrollments yet.</p>
            <?php else: ?>
                <div style="display:grid;gap:0.5rem;margin-top:0.75rem;">
                    <?php foreach ($recentEnrollments as $r): ?>
                        <?php
                            $statusColors = ['completed' => 'var(--primary)', 'in_progress' => 'var(--accent)', 'enrolled' => 'var(--primary)', 'invited' => 'var(--accent)', 'withdrawn' => 'var(--accent)'];
                            $color = $statusColors[$r['status']] ?? '#666';
                        ?>
                        <div style="display:flex;align-items:center;gap:0.6rem;padding:0.5rem;background:var(--bg-subtle);border-radius:8px;">
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.85rem;color:var(--text);"><?= htmlspecialchars($r['learner_name'] ?? 'Unknown') ?></div>
                                <div style="font-size:0.75rem;color:#999;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($r['course_title']) ?></div>
                            </div>
                            <span style="font-size:0.7rem;padding:0.15rem 0.5rem;border-radius:12px;background:<?= $color ?>20;color:<?= $color ?>;font-weight:600;white-space:nowrap;"><?= $r['status'] ?></span>
                            <span style="font-size:0.7rem;color:#bbb;white-space:nowrap;"><?= adminTimeAgo($r['enrolled_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Events -->
        <div class="mode-card">
            <h3><i class="fas fa-video" style="color:var(--accent);margin-right:0.5rem;"></i> Upcoming Events</h3>
            <?php if (empty($upcomingEvents)): ?>
                <p style="color:#999;text-align:center;padding:1.5rem;">No upcoming events.</p>
            <?php else: ?>
                <div style="display:grid;gap:0.5rem;margin-top:0.75rem;">
                    <?php foreach ($upcomingEvents as $ev): ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;border-left:3px solid var(--accent);">
                            <div style="width:40px;height:40px;border-radius:8px;background:var(--accent);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;line-height:1;">
                                <span style="font-size:0.65rem;font-weight:600;"><?= date('M', strtotime($ev['scheduled_at'])) ?></span>
                                <span style="font-size:0.9rem;font-weight:800;"><?= date('j', strtotime($ev['scheduled_at'])) ?></span>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.85rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($ev['title']) ?></div>
                                <div style="font-size:0.75rem;color:#999;"><?= date('g:i A', strtotime($ev['scheduled_at'])) ?> · <?= htmlspecialchars($ev['platform']) ?> · <?= $ev['duration_minutes'] ?>min</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Platform Health -->
        <div class="mode-card">
            <h3><i class="fas fa-heartbeat" style="color:var(--primary);margin-right:0.5rem;"></i> Platform Health</h3>
            <div style="display:grid;gap:0.75rem;margin-top:0.75rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                    <span style="font-size:0.85rem;color:#666;"><i class="fas fa-book" style="margin-right:0.4rem;color:var(--primary);"></i> Total Courses</span>
                    <span style="font-weight:700;color:var(--text);"><?= $stats['courses'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                    <span style="font-size:0.85rem;color:#666;"><i class="fas fa-project-diagram" style="margin-right:0.4rem;color:var(--primary);"></i> Programs</span>
                    <span style="font-weight:700;color:var(--text);"><?= $stats['programs'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                    <span style="font-size:0.85rem;color:#666;"><i class="fas fa-certificate" style="margin-right:0.4rem;color:var(--accent);"></i> Certificates Issued</span>
                    <span style="font-weight:700;color:var(--text);"><?= $stats['certificates'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                    <span style="font-size:0.85rem;color:#666;"><i class="fas fa-flag" style="margin-right:0.4rem;color:var(--accent);"></i> Pending Reports</span>
                    <span style="font-weight:700;color:<?= $stats['pending_reports'] > 0 ? 'var(--accent)' : 'var(--text)' ?>;"><?= $stats['pending_reports'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem;background:var(--bg-subtle);border-radius:8px;">
                    <span style="font-size:0.85rem;color:#666;"><i class="fas fa-chart-line" style="margin-right:0.4rem;color:var(--accent);"></i> Completion Rate</span>
                    <span style="font-weight:700;color:var(--text);"><?= $completionRate ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>
