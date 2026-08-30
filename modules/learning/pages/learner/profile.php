<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$profile = null;
$stats = ['courses_completed' => 0, 'certificates' => 0, 'avg_score' => 0, 'member_since' => ''];
$recentActivity = [];

try {
    $pdo = (new Database())->getConnection();

    // Get employee info with position and department names
    $stmt = $pdo->prepare("SELECT e.employee_id, e.first_name, e.last_name, e.email, e.position_id, e.department_id, p.position_name, d.department_name FROM em_employees e LEFT JOIN em_positions p ON p.position_id = e.position_id LEFT JOIN em_departments d ON d.department_id = e.department_id WHERE e.employee_id = :id LIMIT 1");
    $stmt->execute([':id' => $learnerId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $stmt->execute([':lid' => $learnerId]);
    $stats['courses_completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :lid AND status = 'active'");
    $stmt->execute([':lid' => $learnerId]);
    $stats['certificates'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(final_score), 0), 1) FROM ld_grade WHERE learner_id = :lid");
    $stmt->execute([':lid' => $learnerId]);
    $stats['avg_score'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT MIN(enrolled_at) FROM ld_enrollment WHERE learner_id = :lid");
    $stmt->execute([':lid' => $learnerId]);
    $stats['member_since'] = $stmt->fetchColumn() ?: date('Y-m-d');

    // Recent enrollments
    $stmt = $pdo->prepare("SELECT e.status, e.enrolled_at, c.title FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE e.learner_id = :lid ORDER BY e.last_accessed_at DESC LIMIT 5");
    $stmt->execute([':lid' => $learnerId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // defaults
}

$initials = $profile ? strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)) : '??';
$fullName = $profile ? trim($profile['first_name'] . ' ' . $profile['last_name']) : 'Unknown';
?>
<div class="module-content">
    <div class="toolbar">
        
    </div>

    <!-- Profile Header -->
    <div class="mode-card" style="background:linear-gradient(135deg, var(--primary), var(--text)); color:#fff; padding:2.5rem; border-radius:16px; margin-bottom:1.5rem;">
        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
            <div style="width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-size:2.2rem; font-weight:700; border:3px solid rgba(255,255,255,0.4); flex-shrink:0;">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div>
                <h1 style="margin:0; font-size:1.8rem;"><?= htmlspecialchars($fullName) ?></h1>
                <p style="margin:0.25rem 0 0 0; opacity:0.8;"><?= htmlspecialchars($profile['position_name'] ?? 'Learner') ?></p>
                <?php if (!empty($profile['department_name'])): ?>
                    <p style="margin:0.25rem 0 0 0; opacity:0.6; font-size:0.9rem;"><?= htmlspecialchars($profile['department_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="background:rgba(32,0,130,0.05);">
            <h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Completed</h2>
            <p class="analytics-value"><?= $stats['courses_completed'] ?></p>
            <div style="font-size:0.85rem; color:#999;">courses</div>
        </div>
        <div class="analytics-card" style="background:rgba(40,167,69,0.05);">
            <h2><i class="fas fa-trophy" style="margin-right:0.4rem;opacity:0.6;"></i> Certificates</h2>
            <p class="analytics-value"><?= $stats['certificates'] ?></p>
            <div style="font-size:0.85rem; color:#999;">earned</div>
        </div>
        <div class="analytics-card" style="background:rgba(255,193,7,0.05);">
            <h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2>
            <p class="analytics-value"><?= $stats['avg_score'] ?>%</p>
            <div style="font-size:0.85rem; color:#999;">overall</div>
        </div>
        <div class="analytics-card" style="background:rgba(23,162,184,0.05);">
            <h2><i class="fas fa-calendar" style="margin-right:0.4rem;opacity:0.6;"></i> Member Since</h2>
            <p class="analytics-value" style="font-size:1.2rem;"><?= date('M Y', strtotime($stats['member_since'])) ?></p>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
        <!-- Personal Information -->
        <div class="mode-card">
            <h3><i class="fas fa-user" style="color:var(--primary); margin-right:0.5rem;"></i> Personal Information</h3>
            <div style="padding:1rem 0;">
                <div style="display:flex; justify-content:space-between; padding:0.75rem 0; border-bottom:1px solid #f0f0f0;">
                    <span style="color:#666;">Full Name</span>
                    <strong><?= htmlspecialchars($fullName) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.75rem 0; border-bottom:1px solid #f0f0f0;">
                    <span style="color:#666;">Email</span>
                    <strong><?= htmlspecialchars($profile['email'] ?? '—') ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.75rem 0; border-bottom:1px solid #f0f0f0;">
                    <span style="color:#666;">Position</span>
                    <strong><?= htmlspecialchars($profile['position_name'] ?? '—') ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.75rem 0;">
                    <span style="color:#666;">Department</span>
                    <strong><?= htmlspecialchars($profile['department_name'] ?? '—') ?></strong>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mode-card">
            <h3><i class="fas fa-history" style="color:var(--primary); margin-right:0.5rem;"></i> Recent Activity</h3>
            <div style="padding:0.75rem 0;">
                <?php if (empty($recentActivity)): ?>
                    <p style="color:#999; text-align:center; padding:1rem;">No activity yet.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $act):
                        $statusColor = $act['status'] === 'completed' ? '#28a745' : ($act['status'] === 'in_progress' ? '#ffc107' : '#17a2b8');
                    ?>
                        <div style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0; border-bottom:1px solid #f0f0f0;">
                            <span style="width:8px; height:8px; border-radius:50%; background:<?= $statusColor ?>; flex-shrink:0;"></span>
                            <div style="flex:1; min-width:0;">
                                <strong style="font-size:0.9rem; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($act['title']) ?></strong>
                                <span style="font-size:0.8rem; color:#999;"><?= date('M j', strtotime($act['enrolled_at'])) ?></span>
                            </div>
                            <span style="font-size:0.75rem; padding:0.2rem 0.5rem; background:<?= $statusColor ?>15; color:<?= $statusColor ?>; border-radius:4px; font-weight:500;"><?= ucfirst($act['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mode-card" style="margin-top:1.5rem;">
        <h3><i class="fas fa-link" style="color:var(--primary); margin-right:0.5rem;"></i> Quick Links</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:0.75rem; padding:1rem 0;">
            <a href="?page=learner/study" style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:#f9f9f9; border-radius:10px; text-decoration:none; color:#333; transition:background 0.2s;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(32,0,130,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center;"><i class="fas fa-book-open"></i></div>
                <div><strong style="display:block;">My Study</strong><span style="color:#999; font-size:0.85rem;">Continue learning</span></div>
            </a>
            <a href="?page=learner/result" style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:#f9f9f9; border-radius:10px; text-decoration:none; color:#333; transition:background 0.2s;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(40,167,69,0.1); color:#28a745; display:flex; align-items:center; justify-content:center;"><i class="fas fa-certificate"></i></div>
                <div><strong style="display:block;">My Certificates</strong><span style="color:#999; font-size:0.85rem;">View earned certificates</span></div>
            </a>
            <a href="?page=learner/notes" style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:#f9f9f9; border-radius:10px; text-decoration:none; color:#333; transition:background 0.2s;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(255,193,7,0.1); color:#ffc107; display:flex; align-items:center; justify-content:center;"><i class="fas fa-sticky-note"></i></div>
                <div><strong style="display:block;">My Notes</strong><span style="color:#999; font-size:0.85rem;">Review your notes</span></div>
            </a>

        </div>
    </div>
</div>
