<?php
include_once __DIR__ . '/../../../auth/session.php';
include __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../autoload.php';

use App\Models\Notification;

$employeeClass = new Employee();
$sidebarEmployeeId = (int)($employeeClass->getEmployeeId() ?? 0);
$sidebarNotifications = $sidebarEmployeeId > 0
    ? (new Notification())->getForEmployee($sidebarEmployeeId)
    : [];
$sidebarLcmNotifications = $sidebarEmployeeId > 0
    ? (new \App\Models\LcmIntegration())->getNotificationsForEmployee($sidebarEmployeeId)
    : [];
$sidebarNotifications = array_merge(
    array_map(static function ($notification) {
        $notification['source'] = 'hr';
        return $notification;
    }, $sidebarNotifications),
    array_map(static function ($notification) {
        $notification['source'] = 'lcm';
        return $notification;
    }, $sidebarLcmNotifications)
);
usort($sidebarNotifications, static function ($first, $second) {
    return strcmp((string)($second['created_at'] ?? ''), (string)($first['created_at'] ?? ''));
});
$sidebarUnreadCount = count(array_filter($sidebarNotifications, static function ($notification) {
    return empty($notification['is_read']);
}));

?>

<aside class="sidebar">
    <div class="school-logo">
        <img src="../../assets/bcp-logo.png" alt="School Logo">
        <div class="sidebar-icons">

            <!-- Bell Icon + Notification Dropdown -->
            <div class="icon-wrapper" id="bellWrapper">
                <i class="fa-regular fa-bell" id="bellBtn" role="button" tabindex="0" aria-label="Open notifications"></i>
                <span id="notifBadge" class="notification-badge<?= $sidebarUnreadCount ? '' : ' hidden' ?>"><?= $sidebarUnreadCount ?></span>
                <div class="icon-dropdown" id="bellDropdown">
                    <div class="dropdown-header">
                        <span>HR Notifications</span>
                        <button type="button" class="mark-all-read"<?= $sidebarUnreadCount ? '' : ' disabled' ?>>Mark all as read</button>
                    </div>
                    <ul class="notif-list" id="sidebar-notification-list" data-unread-count="<?= $sidebarUnreadCount ?>">
                        <?php if ($sidebarNotifications): ?>
                            <?php foreach (array_slice($sidebarNotifications, 0, 10) as $notification): ?>
                                <li class="notif-item<?= empty($notification['is_read']) ? ' unread' : '' ?>" data-notification-id="<?= (int)$notification['id'] ?>" data-notification-source="<?= htmlspecialchars($notification['source'], ENT_QUOTES, 'UTF-8') ?>">
                                    <strong><?= htmlspecialchars(ucfirst($notification['type'] ?? $notification['notification_type'] ?? 'HR Update'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($notification['title'])): ?>
                                        <span><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($notification['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <small><?= htmlspecialchars($notification['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="notif-item notification-empty" role="status">No notifications yet.</li>
                        <?php endif; ?>
                    </ul>
                    <div class="dropdown-footer">
                        <span>Showing latest 10 notifications</span>
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
                            <a href="<?= htmlspecialchars(preg_replace('#/modules/.*$#', '', $_SERVER['SCRIPT_NAME'] ?? '') . '/auth/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="signout-link">
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
    <h2>Engagement Dashboard</h2>
    <ul>
        <?php $pageController->renderNav(); ?>
    </ul>
</aside>