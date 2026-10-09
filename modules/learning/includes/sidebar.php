<?php
$employeeName = (string) ($_SESSION['employee_name'] ?? 'User');
$employeePosition = (string) ($_SESSION['position_name'] ?? '');
$employeeInitial = strtoupper(substr($employeeName, 0, 1));
?>

<aside class="sidebar">
    <div class="school-logo">
        <img src="assets/bcp-logo.png" alt="School logo">
    </div>
    <div class="sidebar-header">
        <div class="user_avatar"><?= htmlspecialchars($employeeInitial, ENT_QUOTES, 'UTF-8') ?></div>
        <p class="employee_name"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="employee_position"><?= htmlspecialchars($employeePosition, ENT_QUOTES, 'UTF-8') ?></p>
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
