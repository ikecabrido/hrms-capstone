<?php
$employeeName = (string) ($_SESSION['employee_name'] ?? 'User');
$employeePosition = (string) ($_SESSION['position_name'] ?? '');
$employeeInitial = strtoupper(substr($employeeName, 0, 1));

// Profile entry point: the avatar/name block links to the role's profile page.
$learningRole = 'learner';
$profilePhoto = null;
try {
    if (isset($pageController) && method_exists($pageController, 'getLearningRole')) {
        $learningRole = $pageController->getLearningRole();
    }
    require_once __DIR__ . '/../classes/UserProfile.php';
    $userProfile = new UserProfile();
    $profileRow = $userProfile->getForEmployee((int) ($_SESSION['employee_id'] ?? 0));
    if (!empty($profileRow['profile_photo'])) {
        $profilePhoto = basename($profileRow['profile_photo']);
    }
} catch (Throwable $e) {
    $profilePhoto = null;
}
$profilePage = $learningRole . '/profile';
?>

<aside class="sidebar">
    <div class="school-logo">
        <img src="assets/bcp-logo.png" alt="School logo">
        <button type="button" class="sidebar-close" aria-label="Close navigation">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="sidebar-header">
        <a class="sidebar-profile-link" href="?page=<?= htmlspecialchars($profilePage, ENT_QUOTES, 'UTF-8') ?>" data-page="<?= htmlspecialchars($profilePage, ENT_QUOTES, 'UTF-8') ?>">
            <div class="user_avatar" id="sidebar-avatar" data-initial="<?= htmlspecialchars($employeeInitial, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($profilePhoto !== null): ?>
                    <img src="uploads/profile-photos/<?= htmlspecialchars($profilePhoto, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else: ?>
                    <?= htmlspecialchars($employeeInitial, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <p class="employee_name"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="employee_position"><?= htmlspecialchars($employeePosition, ENT_QUOTES, 'UTF-8') ?></p>
        </a>
    </div>
    <ul>
        <?php $pageController->renderNav(); ?>
    </ul>
    <div class="sidebar-footer">
        <a class="menu-link logout-link" href="logout.php">
            <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
