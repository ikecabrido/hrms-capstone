<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$stats = ['my_courses' => 0, 'my_modules' => 0, 'my_learners' => 0, 'my_programs' => 0, 'completed' => 0, 'avg_score' => 0];
$recentEnrollments = [];
$topCourses = [];
$atRiskCount = 0;

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_course WHERE instructor_id = :iid AND status IN ('active','draft')");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_courses'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_module m JOIN ld_course c ON c.id = m.course_id WHERE c.instructor_id = :iid AND m.status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_modules'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_learners'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_program WHERE instructor_id = :iid AND status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['my_programs'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status = 'completed'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['completed'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score),0),1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();
    $recentEnrollments = $pdo->prepare("SELECT e.status, e.enrolled_at, c.title AS course_title, CONCAT(emp.first_name,' ',emp.last_name) AS learner_name FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id WHERE c.instructor_id = :iid ORDER BY e.enrolled_at DESC LIMIT 5");
    $recentEnrollments->execute([':iid' => $instructorId]);
    $recentEnrollments = $recentEnrollments->fetchAll(PDO::FETCH_ASSOC);
    $topCourses = $pdo->prepare("SELECT c.title, COUNT(e.id) AS enrollment_count, ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score, SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id = c.id LEFT JOIN ld_grade g ON g.course_id = c.id AND g.learner_id = e.learner_id WHERE c.instructor_id = :iid AND c.status = 'active' GROUP BY c.id,c.title ORDER BY enrollment_count DESC LIMIT 5");
    $topCourses->execute([':iid' => $instructorId]);
    $topCourses = $topCourses->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status IN ('enrolled','in_progress') AND DATEDIFF(NOW(), e.last_accessed_at) > 14");
    $stmt->execute([':iid' => $instructorId]);
    $atRiskCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) {}

function instrTimeAgo($dt) {
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}
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
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=instructor/elearning'"><h2><i class="fas fa-book" style="margin-right:0.4rem;opacity:0.6;"></i> My Courses</h2><p class="analytics-value"><?= $stats['my_courses'] ?></p><div style="font-size:0.85rem;color:#999;"><?= $stats['my_modules'] ?> modules</div></div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=instructor/analytics'"><h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> My Learners</h2><p class="analytics-value"><?= $stats['my_learners'] ?></p><div style="font-size:0.85rem;color:#999;"><?= $stats['completed'] ?> completed</div></div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=instructor/analytics'"><h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2><p class="analytics-value"><?= $stats['avg_score'] ?>%</p><div style="font-size:0.85rem;color:#999;">across all courses</div></div>
                <div class="analytics-card" style="cursor:pointer;" onclick="location.href='?page=instructor/analytics'"><h2><i class="fas fa-exclamation-triangle" style="margin-right:0.4rem;opacity:0.6;"></i> At-Risk</h2><p class="analytics-value" style="color:<?= $atRiskCount > 0 ? 'var(--accent)' : 'inherit' ?>;"><?= $atRiskCount ?></p><div style="font-size:0.85rem;color:#999;">inactive 14+ days</div></div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="mode-card">
            <h3 style="margin:0 0 1rem 0;"><i class="fas fa-bolt" style="color:var(--accent);margin-right:0.5rem;"></i> Quick Access</h3>
            <div style="display:grid;gap:0.5rem;">
                <?php $links = [
                    ['icon'=>'fa-book','label'=>'E-Learning','url'=>'?page=instructor/elearning','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-person-hiking','label'=>'Trainings','url'=>'?page=instructor/training','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
                    ['icon'=>'fa-chart-line','label'=>'Analytics','url'=>'?page=instructor/analytics','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-award','label'=>'Certificates','url'=>'?page=instructor/certificate','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
                    ['icon'=>'fa-bell','label'=>'Notifications','url'=>'?page=instructor/notification','bg'=>'rgba(99,102,241,0.1)','color'=>'#6366f1'],
                    ['icon'=>'fa-user-circle','label'=>'My Profile','url'=>'?page=instructor/profile','bg'=>'rgba(245,158,11,0.1)','color'=>'#f59e0b'],
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

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
        <div>
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;"><h3 style="margin:0;"><i class="fas fa-trophy" style="color:var(--primary);margin-right:0.5rem;"></i> Top Courses</h3><a href="?page=instructor/analytics" style="color:var(--primary);font-size:0.9rem;text-decoration:none;">View Analytics &rarr;</a></div>
                <?php if (empty($topCourses)): ?><p style="color:#999;text-align:center;padding:1rem;">No courses yet</p><?php else: ?><?php foreach ($topCourses as $idx => $tc): $compRate = $tc['enrollment_count'] > 0 ? round(($tc['completed_count'] / $tc['enrollment_count']) * 100) : 0; ?>
                <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem;background:var(--bg-subtle);border-radius:8px;margin-bottom:0.5rem;"><div style="width:32px;height:32px;border-radius:8px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;"><?= $idx + 1 ?></div><div style="flex:1;min-width:0;"><strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:0.95rem;"><?= htmlspecialchars($tc['title']) ?></strong><span style="color:#999;font-size:0.8rem;"><?= $tc['enrollment_count'] ?> enrolled &bull; <?= $tc['avg_score'] ?>% avg &bull; <?= $compRate ?>% done</span></div><div title="<?= (int) $tc['completed_count'] ?> of <?= (int) $tc['enrollment_count'] ?> enrollments completed" style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;"><div style="width:50px;background:var(--border);height:6px;border-radius:3px;overflow:hidden;"><div style="background:var(--primary);height:100%;width:<?= $compRate ?>%;"></div></div><span style="font-size:0.8rem;font-weight:700;color:var(--primary);white-space:nowrap;min-width:38px;text-align:right;"><?= $compRate ?>%</span></div></div>
                <?php endforeach; ?><?php endif; ?>
            </div>
            <div class="mode-card">
                <h3 style="margin:0 0 1rem 0;"><i class="fas fa-clock" style="color:var(--primary);margin-right:0.5rem;"></i> Recent Enrollments</h3>
                <?php if (empty($recentEnrollments)): ?><p style="color:#999;text-align:center;padding:1rem;">No enrollments yet</p><?php else: ?><?php foreach ($recentEnrollments as $re): $sColor = $re['status'] === 'completed' ? 'var(--primary)' : ($re['status'] === 'in_progress' ? 'var(--accent)' : 'var(--accent)'); ?>
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid var(--bg-subtle);"><div style="width:8px;height:8px;border-radius:50%;background:<?= $sColor ?>;flex-shrink:0;"></div><div style="flex:1;min-width:0;"><strong style="font-size:0.9rem;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($re['learner_name']) ?></strong><span style="font-size:0.8rem;color:#999;"><?= htmlspecialchars(mb_substr($re['course_title'], 0, 35)) ?></span></div><span style="font-size:0.75rem;color:#999;white-space:nowrap;"><?= instrTimeAgo($re['enrolled_at']) ?></span></div>
                <?php endforeach; ?><?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($atRiskCount > 0): ?>
            <div class="mode-card" style="margin-bottom:1.5rem;border-left:4px solid var(--accent);">
                <h3 style="margin:0 0 0.5rem;font-size:1rem;"><i class="fas fa-exclamation-triangle" style="color:var(--accent);margin-right:0.4rem;"></i> At-Risk Students</h3>
                <p style="margin:0;color:#666;font-size:0.9rem;"><?= $atRiskCount ?> student<?= $atRiskCount !== 1 ? 's' : '' ?> haven't accessed courses in 14+ days.</p>
                <a href="?page=instructor/analytics" style="display:inline-block;margin-top:0.75rem;padding:0.4rem 0.8rem;background:var(--accent);color:#fff;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:500;">View Details</a>
            </div>
            <?php endif; ?>
            <div class="mode-card">
                <h3 style="margin:0 0 1rem;font-size:1rem;"><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:0.4rem;"></i> Summary</h3>
                <div style="display:grid;gap:0.5rem;">
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--bg-subtle);font-size:0.9rem;"><span style="color:var(--muted,#666);">Active Courses</span><strong><?= $stats['my_courses'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--bg-subtle);font-size:0.9rem;"><span style="color:var(--muted,#666);">Total Learners</span><strong><?= $stats['my_learners'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--bg-subtle);font-size:0.9rem;"><span style="color:var(--muted,#666);">Completed</span><strong><?= $stats['completed'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--bg-subtle);font-size:0.9rem;"><span style="color:var(--muted,#666);">Avg Score</span><strong><?= $stats['avg_score'] ?>%</strong></div>
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;font-size:0.9rem;"><span style="color:var(--muted,#666);">Programs</span><strong><?= $stats['my_programs'] ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>