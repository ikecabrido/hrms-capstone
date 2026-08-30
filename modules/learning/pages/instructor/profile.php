<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$profile = null;
$stats = ['courses' => 0, 'learners' => 0, 'completed' => 0, 'avg_score' => 0, 'programs' => 0];

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email, position, department FROM em_employees WHERE employee_id = :id LIMIT 1");
    $stmt->execute([':id' => $instructorId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_course WHERE instructor_id = :iid AND status IN ('active','draft')");
    $stmt->execute([':iid' => $instructorId]);
    $stats['courses'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['learners'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status = 'completed'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score),0),1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid");
    $stmt->execute([':iid' => $instructorId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_program WHERE instructor_id = :iid AND status = 'active'");
    $stmt->execute([':iid' => $instructorId]);
    $stats['programs'] = (int) $stmt->fetchColumn();

} catch (Throwable $e) {}

$initials = $profile ? strtoupper(substr($profile['first_name'],0,1).substr($profile['last_name'],0,1)) : '??';
$fullName = $profile ? trim($profile['first_name'].' '.$profile['last_name']) : 'Unknown';
?>
<div class="module-content">
    <div class="toolbar">
        
    </div>

    <div class="mode-card" style="background:linear-gradient(135deg, var(--primary), var(--text));color:#fff;padding:2.5rem;border-radius:16px;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700;border:3px solid rgba(255,255,255,0.4);"><?= htmlspecialchars($initials) ?></div>
            <div>
                <h1 style="margin:0;font-size:1.8rem;"><?= htmlspecialchars($fullName) ?></h1>
                <p style="margin:0.25rem 0 0;opacity:0.8;"><?= htmlspecialchars($profile['position'] ?? 'Instructor') ?></p>
                <?php if (!empty($profile['department'])): ?><p style="margin:0.25rem 0 0;opacity:0.6;font-size:0.9rem;"><?= htmlspecialchars($profile['department']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="background:rgba(32,0,130,0.05);"><h2><i class="fas fa-book" style="margin-right:0.4rem;opacity:0.6;"></i> Courses</h2><p class="analytics-value"><?= $stats['courses'] ?></p><div style="font-size:0.85rem;color:#999;">created</div></div>
        <div class="analytics-card" style="background:rgba(40,167,69,0.05);"><h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> Learners</h2><p class="analytics-value"><?= $stats['learners'] ?></p><div style="font-size:0.85rem;color:#999;">total enrolled</div></div>
        <div class="analytics-card" style="background:rgba(255,193,7,0.05);"><h2><i class="fas fa-check-circle" style="margin-right:0.4rem;opacity:0.6;"></i> Completed</h2><p class="analytics-value"><?= $stats['completed'] ?></p><div style="font-size:0.85rem;color:#999;">courses finished</div></div>
        <div class="analytics-card" style="background:rgba(23,162,184,0.05);"><h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2><p class="analytics-value"><?= $stats['avg_score'] ?>%</p><div style="font-size:0.85rem;color:#999;">student average</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <div class="mode-card">
            <h3><i class="fas fa-user" style="color:var(--primary);margin-right:0.5rem;"></i> Personal Information</h3>
            <div style="padding:1rem 0;">
                <div style="display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #f0f0f0;"><span style="color:#666;">Full Name</span><strong><?= htmlspecialchars($fullName) ?></strong></div>
                <div style="display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #f0f0f0;"><span style="color:#666;">Email</span><strong><?= htmlspecialchars($profile['email'] ?? '—') ?></strong></div>
                <div style="display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #f0f0f0;"><span style="color:#666;">Position</span><strong><?= htmlspecialchars($profile['position'] ?? '—') ?></strong></div>
                <div style="display:flex;justify-content:space-between;padding:0.75rem 0;"><span style="color:#666;">Department</span><strong><?= htmlspecialchars($profile['department'] ?? '—') ?></strong></div>
            </div>
        </div>

        <div class="mode-card">
            <h3><i class="fas fa-link" style="color:var(--primary);margin-right:0.5rem;"></i> Quick Links</h3>
            <div style="display:grid;gap:0.75rem;padding:1rem 0;">
                <a href="?page=instructor/elearning" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(32,0,130,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center;"><i class="fas fa-book"></i></div>
                    <div><strong style="display:block;">E-Learning</strong><span style="color:#999;font-size:0.85rem;">Manage your courses</span></div>
                </a>
                <a href="?page=instructor/analytics" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(23,162,184,0.1);color:#17a2b8;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-line"></i></div>
                    <div><strong style="display:block;">Analytics</strong><span style="color:#999;font-size:0.85rem;">Track student performance</span></div>
                </a>
                <a href="?page=instructor/calendar" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(40,167,69,0.1);color:#28a745;display:flex;align-items:center;justify-content:center;"><i class="fas fa-calendar"></i></div>
                    <div><strong style="display:block;">Calendar</strong><span style="color:#999;font-size:0.85rem;">View your schedule</span></div>
                </a>
            </div>
        </div>
    </div>
</div>
