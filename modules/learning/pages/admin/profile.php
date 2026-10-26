<?php
/**
 * Admin profile. Layout comes from the shared profile partials; this file only
 * adds the admin-specific platform overview and quick links.
 */
$profileRoleLabel = 'Administrator';
require_once __DIR__ . '/../shared/includes/profile-data.php';

$adminStats = ['courses' => 0, 'learners' => 0, 'instructors' => 0, 'skills' => 0];
if ($pdo) {
    try {
        $adminStats['courses'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE status IN ('active','draft')")->fetchColumn();
        $adminStats['learners'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_user_role WHERE learning_role = 'learner'")->fetchColumn();
        $adminStats['instructors'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_user_role WHERE learning_role = 'instructor'")->fetchColumn();
        $adminStats['skills'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_skill WHERE status = 'active'")->fetchColumn();
    } catch (Throwable $e) {
        // keep defaults
    }
}

$gradebookLink = null;
?>
<?php include __DIR__ . '/../shared/includes/profile-assets.php'; ?>
<div class="module-content">
    <?php include __DIR__ . '/../shared/includes/profile-header.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-completion.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-bio.php'; ?>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <?php include __DIR__ . '/../shared/includes/profile-info.php'; ?>
        <?php include __DIR__ . '/../shared/includes/profile-contact.php'; ?>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h3 class="pfl-section-title"><i class="fas fa-chart-simple" aria-hidden="true"></i> Platform Overview</h3>
        <div class="pfl-stats">
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-book" aria-hidden="true"></i> Active Courses</div>
                <div class="pfl-stat-value"><?= (int) $adminStats['courses'] ?></div>
                <div class="pfl-stat-sub">published or in draft</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-graduation-cap" aria-hidden="true"></i> Trainees</div>
                <div class="pfl-stat-value is-teal"><?= (int) $adminStats['learners'] ?></div>
                <div class="pfl-stat-sub">with the trainee role</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-chalkboard-user" aria-hidden="true"></i> Trainers</div>
                <div class="pfl-stat-value is-green"><?= (int) $adminStats['instructors'] ?></div>
                <div class="pfl-stat-sub">with the trainer role</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-lightbulb" aria-hidden="true"></i> Skills Tracked</div>
                <div class="pfl-stat-value is-amber"><?= (int) $adminStats['skills'] ?></div>
                <div class="pfl-stat-sub">active skills in the catalog</div>
            </div>
        </div>
    </div>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <div class="mode-card">
            <h3 class="pfl-section-title"><i class="fas fa-link" aria-hidden="true"></i> Quick Links</h3>
            <div class="pfl-quick-links">
                <a class="pfl-quick-link" href="?page=admin/user" data-page="admin/user">
                    <div class="pfl-quick-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                    <div><strong>User Management</strong><span>Add and manage trainees &amp; trainers</span></div>
                </a>
                <a class="pfl-quick-link" href="?page=admin/gradebook" data-page="admin/gradebook">
                    <div class="pfl-quick-icon"><i class="fas fa-book-bookmark" aria-hidden="true"></i></div>
                    <div><strong>Grade Book</strong><span>Review scores across courses</span></div>
                </a>
                <a class="pfl-quick-link" href="?page=admin/analytics" data-page="admin/analytics">
                    <div class="pfl-quick-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                    <div><strong>Analytics</strong><span>Track platform-wide performance</span></div>
                </a>
                <a class="pfl-quick-link" href="?page=admin/settings" data-page="admin/settings">
                    <div class="pfl-quick-icon"><i class="fas fa-gear" aria-hidden="true"></i></div>
                    <div><strong>Settings</strong><span>Configure the Learning module</span></div>
                </a>
            </div>
        </div>

        <?php include __DIR__ . '/../shared/includes/profile-security.php'; ?>
    </div>

    <?php include __DIR__ . '/../shared/includes/profile-gradebook.php'; ?>

    <?php include __DIR__ . '/../shared/includes/profile-preferences.php'; ?>
</div>
<?php include __DIR__ . '/../shared/includes/profile-scripts.php'; ?>
