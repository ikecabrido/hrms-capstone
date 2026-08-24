<?php
include_once __DIR__ . '/../../../auth/session.php';
include __DIR__ . '/../classes/Employee.php';
$employeeClass = new Employee();

?>

<aside class="sidebar">
    <div class="school-logo">
        <img src="assets/bcp-logo.png" alt="School Logo">
        <div class="sidebar-icons">

            <!-- Bell Icon + Notification Dropdown -->
            <div class="icon-wrapper" id="bellWrapper">
                <i class="fa-regular fa-bell" id="bellBtn"></i>
                <div class="icon-dropdown" id="bellDropdown">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <button class="mark-all-read">Mark all as read</button>
                    </div>
                    <ul class="notif-list">
                        <li class="notif-item">
                        </li>
                        <div class="dropdown-footer">
                            <a href="#">View all notifications</a>
                        </div>
                </div>
            </div>

            <!-- User Icon + Profile Dropdown -->
            <div class="icon-wrapper" id="userWrapper">
                <i class="fa-regular fa-circle-user" id="userBtn"></i>
                <div class="icon-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-info">
                            <div class="dropdown-avatar">
                                <?= substr(htmlspecialchars($employeeClass->getEmployeeName()), 0, 1) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($employeeClass->getEmployeeName()) ?></strong>
                                <span><?= htmlspecialchars($employeeClass->getEmployeePosition()) ?></span>
                            </div>
                        </div>
                    </div>
                    <ul class="user-menu">
                        <li>
                            <a href="#"><i class="fa-regular fa-user"></i> Profile Settings</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-lock"></i> Change Password</a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="/hrms-capstone/auth/logout.php" class="signout-link">
                                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
    <div class="sidebar-header">
        <div class="user_avatar"><?= substr(htmlspecialchars($employeeClass->getEmployeeName()), 0, 1) ?></div>
        <h1 class="employee_name"><?= htmlspecialchars($employeeClass->getEmployeeName()) ?></h1>
        <p class="employee_position"><?= htmlspecialchars($employeeClass->getEmployeePosition()) ?></p>
    </div>
    <h2>HR Dashboard &amp; Metrics</h2>
    <ul>
        <?php $pageController->renderNav(); ?>
    </ul>
</aside>