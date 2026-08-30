<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$adminId = (int) ($employeeClass->getEmployeeId() ?? 0);

$profile = null;
$platformStats = ['users' => 0, 'courses' => 0, 'enrollments' => 0, 'reports' => 0];

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email, position, department FROM em_employees WHERE employee_id = :id LIMIT 1");
    $stmt->execute([':id' => $adminId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $platformStats['users'] = (int) $pdo->query("SELECT COUNT(*) FROM hrms_employee")->fetchColumn();
    $platformStats['courses'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE status='active'")->fetchColumn();
    $platformStats['enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment")->fetchColumn();
    $platformStats['reports'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_report WHERE status='pending'")->fetchColumn();
} catch (Throwable $e) {}

$initials = $profile ? strtoupper(substr($profile['first_name'],0,1).substr($profile['last_name'],0,1)) : '??';
$fullName = $profile ? trim($profile['first_name'].' '.$profile['last_name']) : 'Unknown';
?>
<div class="module-content">
    <div class="mode-card" style="background:linear-gradient(135deg, var(--primary), var(--text));color:#fff;padding:2.5rem;border-radius:16px;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700;border:3px solid rgba(255,255,255,0.4);"><?= htmlspecialchars($initials) ?></div>
            <div>
                <h1 style="margin:0;font-size:1.8rem;"><?= htmlspecialchars($fullName) ?></h1>
                <p style="margin:0.25rem 0 0;opacity:0.8;"><?= htmlspecialchars($profile['position'] ?? 'Admin') ?></p>
                <?php if (!empty($profile['department'])): ?><p style="margin:0.25rem 0 0;opacity:0.6;font-size:0.9rem;"><?= htmlspecialchars($profile['department']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="background:rgba(32,0,130,0.05);"><h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> Users</h2><p class="analytics-value"><?= $platformStats['users'] ?></p></div>
        <div class="analytics-card" style="background:rgba(40,167,69,0.05);"><h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Courses</h2><p class="analytics-value"><?= $platformStats['courses'] ?></p></div>
        <div class="analytics-card" style="background:rgba(255,193,7,0.05);"><h2><i class="fas fa-book-open" style="margin-right:0.4rem;opacity:0.6;"></i> Enrollments</h2><p class="analytics-value"><?= $platformStats['enrollments'] ?></p></div>
        <div class="analytics-card" style="background:rgba(220,53,69,0.05);"><h2><i class="fas fa-flag" style="margin-right:0.4rem;opacity:0.6;"></i> Pending</h2><p class="analytics-value"><?= $platformStats['reports'] ?></p></div>
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
                <a href="?page=admin/user" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(32,0,130,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center;"><i class="fas fa-users"></i></div>
                    <div><strong style="display:block;">User Management</strong><span style="color:#999;font-size:0.85rem;">Manage instructors & learners</span></div>
                </a>
                <a href="?page=admin/moderation" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(220,53,69,0.1);color:#dc3545;display:flex;align-items:center;justify-content:center;"><i class="fas fa-scale-balanced"></i></div>
                    <div><strong style="display:block;">Moderation</strong><span style="color:#999;font-size:0.85rem;">Review reports & content</span></div>
                </a>
                <a href="?page=admin/analytics" style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#f9f9f9;border-radius:10px;text-decoration:none;color:#333;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(23,162,184,0.1);color:#17a2b8;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-line"></i></div>
                    <div><strong style="display:block;">Analytics</strong><span style="color:#999;font-size:0.85rem;">Platform insights & reports</span></div>
                </a>
            </div>
        </div>
    </div>
</div>
