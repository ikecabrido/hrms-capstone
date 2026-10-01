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
            <div class="icon-wrapper" id="bellWrapper" data-employee-id="<?= (int)($_SESSION['employee_id'] ?? 0) ?>">
                <i class="fa-regular fa-bell" id="bellBtn"></i>
                <span class="notif-badge hidden" id="notifBadge">0</span>
                <div class="icon-dropdown" id="bellDropdown">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <button class="mark-all-read" id="markAllRead">Mark all as read</button>
                    </div>
                    <ul class="notif-list" id="notifList">
                        <li class="notif-item empty-notif">Loading notifications...</li>
                    </ul>
                </div>
                <div class="icon-dropdown notif-detail-dropdown" id="notifDetailDropdown">
                    <div class="dropdown-header">
                        <span id="notifDetailTitle">Notification</span>
                        <button class="mark-all-read" id="notifDetailClose">Close</button>
                    </div>
                    <div class="notif-detail-body" id="notifDetailBody"></div>
                </div>
            </div>

            <!-- User Icon + Profile Dropdown -->
            <div class="icon-wrapper" id="userWrapper">
                <i class="fa-regular fa-circle-user" id="userBtn"></i>
                <div class="icon-dropdown user-dropdown" id="userDropdown" role="menu">
                    <div class="dropdown-header">
                        <div class="dropdown-user-info">
                            <div class="dropdown-avatar" aria-hidden="true">
                                <?= substr(htmlspecialchars($employeeClass->getEmployeeName()), 0, 1) ?>
                            </div>
                            <div class="dropdown-user-details">
                                <strong><?= htmlspecialchars($employeeClass->getEmployeeName()) ?></strong>
                                <span><?= htmlspecialchars($employeeClass->getEmployeePosition()) ?></span>
                                <div class="account-status">
                                    <span class="status-dot"></span>
                                    Active
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="user-menu">
                        <li>
                            <a href="?page=profile-settings" role="menuitem">
                                <span class="menu-content">
                                    <strong>Profile Settings</strong>
                                    <small>Manage your account</small>
                                </span>
                                <span class="menu-arrow">&rsaquo;</span>
                            </a>
                        </li>
                        <li>
                            <a href="?page=profile-settings#change-password" role="menuitem">
                                <span class="menu-content">
                                    <strong>Change Password</strong>
                                    <small>Update your password</small>
                                </span>
                                <span class="menu-arrow">&rsaquo;</span>
                            </a>
                        </li>
                        <li class="divider" role="separator"></li>
                        <li>
                            <a href="/auth/logout.php" class="signout-link" role="menuitem">
                                <span class="menu-content">
                                    <strong>Sign Out</strong>
                                    <small>End your current session</small>
                                </span>
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
    <h2>Legal & Compliance</h2>
    <?php $pageController->renderNav(); ?>
</aside>