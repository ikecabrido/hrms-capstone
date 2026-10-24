<?php
require_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/LearningRole.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$viewerId = (int) ($employeeClass->getEmployeeId() ?? 0);
$profileEmployeeId = isset($_GET['id']) && is_scalar($_GET['id']) && ctype_digit((string) $_GET['id'])
    ? (int) $_GET['id']
    : 0;
$viewerRole = $_SESSION['learning_role'] ?? 'learner';
$profile = null;
$profileRole = 'learner';
$bio = '';
$courses = [];
$profileLoadFailed = false;

try {
    $pdo = (new Database())->getConnection();
    if ($viewerId > 0 && $profileEmployeeId > 0) {
        $profileQuery = $pdo->prepare(
            "SELECT e.employee_id, e.first_name, e.last_name, e.position_id, e.department_id,
                    p.position_name, d.department_name, up.bio
             FROM em_employees e
             LEFT JOIN em_positions p ON p.position_id = e.position_id
             LEFT JOIN em_departments d ON d.department_id = e.department_id
             LEFT JOIN ld_user_profile up ON up.employee_id = e.employee_id
             WHERE e.employee_id = :employee_id AND e.employment_status = 'Active'
             LIMIT 1"
        );
        $profileQuery->execute([':employee_id' => $profileEmployeeId]);
        $profile = $profileQuery->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($profile) {
            $profileRole = LearningRole::forEmployee($pdo, $profileEmployeeId);
            if ($profileRole === 'instructor') {
                $courseQuery = $pdo->prepare(
                    "SELECT id, title
                     FROM ld_course
                     WHERE instructor_id = :instructor_id AND status = 'active'
                     ORDER BY title ASC"
                );
                $courseQuery->execute([':instructor_id' => $profileEmployeeId]);
                $courses = $courseQuery->fetchAll(PDO::FETCH_ASSOC);
            }
            $bio = trim((string) ($profile['bio'] ?? ''));
        }
    }
} catch (Throwable $e) {
    error_log('[learning] Public user profile lookup failed: ' . $e->getMessage());
    $profileLoadFailed = true;
}

$displayName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
$initials = strtoupper(substr((string) ($profile['first_name'] ?? ''), 0, 1) . substr((string) ($profile['last_name'] ?? ''), 0, 1));
$initials = $initials !== '' ? $initials : '??';
$roleLabel = ucfirst($profileRole);
$backPage = [
    'admin' => 'admin/admin-home',
    'instructor' => 'instructor/instructor-home',
    'learner' => 'learner/learner-home',
][$viewerRole] ?? 'learner/learner-home';
?>
<div class="module-content">
    <?php if (!$profile): ?>
        <div class="mode-card">
            <h2 class="pfl-section-title"><i class="fas fa-user-slash" aria-hidden="true"></i> Profile unavailable</h2>
            <p class="pfl-empty">
                <?= $profileLoadFailed
                    ? 'This profile could not be loaded. Please try again later.'
                    : 'This profile is unavailable or the account is inactive.' ?>
                <a href="?page=<?= htmlspecialchars($backPage, ENT_QUOTES, 'UTF-8') ?>">Return to your home page</a>
            </p>
        </div>
    <?php else: ?>
        <div style="margin-bottom:1rem;">
            <a class="pfl-admin-back" href="?page=<?= htmlspecialchars($backPage, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Back
            </a>
        </div>

        <section class="mode-card" aria-labelledby="public-profile-name">
            <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <div aria-hidden="true" style="width:4rem; height:4rem; border-radius:50%; display:grid; place-items:center; background:var(--primary); color:#fff; font-size:1.2rem; font-weight:700;">
                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div>
                    <h1 id="public-profile-name" style="margin:0;"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p style="margin:0.25rem 0 0; color:var(--muted);">
                        <span><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        &bull;
                        <?= htmlspecialchars((string) ($profile['position_name'] ?: $roleLabel), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($profile['department_name'])): ?>
                            · <?= htmlspecialchars((string) $profile['department_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div style="margin-top:1.25rem;">
                <h2 class="pfl-section-title">About</h2>
                <p class="pfl-empty"><?= $bio !== '' ? nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')) : 'No profile introduction has been added.' ?></p>
            </div>
        </section>

        <?php if ($profileRole === 'instructor'): ?>
            <section class="mode-card" style="margin-top:1rem;" aria-labelledby="public-profile-courses">
                <h2 id="public-profile-courses" class="pfl-section-title">Active courses</h2>
                <?php if (empty($courses)): ?>
                    <p class="pfl-empty">No active courses are listed.</p>
                <?php else: ?>
                    <ul style="margin:0; padding-left:1.25rem;">
                        <?php foreach ($courses as $course): ?>
                            <li><?= htmlspecialchars((string) $course['title'], ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
