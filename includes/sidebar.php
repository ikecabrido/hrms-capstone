<?php
/**
 * The sidebar every module shares.
 *
 * Twelve modules used to carry their own copy of this markup, identical down to the
 * whitespace — the only thing that actually varied was the data each module put into
 * it. That data now comes in through $sidebar, set by the module's own
 * includes/sidebar.php before it requires this file:
 *
 *   $sidebar['dir']    the module's directory. Its classes/Employee.php is loaded from
 *                      there (module pages rely on that class already existing), and
 *                      the module's own employee data is what the header shows.
 *   $sidebar['title']  heading printed above the nav; null omits the heading.
 *
 * The module's Page instance is expected in $pageController — its renderNav() prints
 * the module's own links and section headings, so the nav itself stays per module.
 *
 * Note: the logo path is browser-relative on purpose, because every module serves its
 * own assets/ directory next to its index.php. Do not rewrite it as an app-root path.
 */

require_once dirname(__DIR__) . '/auth/session.php';
require_once __DIR__ . '/app-base.php';

$sidebarDir = isset($sidebar['dir']) ? rtrim($sidebar['dir'], "/\\") : '';
if ($sidebarDir === '' || !is_file($sidebarDir . '/classes/Employee.php')) {
    trigger_error(
        "Sidebar: set \$sidebar['dir'] to the module directory (holding classes/Employee.php) before requiring includes/sidebar.php.",
        E_USER_WARNING
    );
    return;
}

if (!isset($pageController) || !method_exists($pageController, 'renderNav')) {
    trigger_error(
        'Sidebar: expecting $pageController (the module Page instance) with renderNav().',
        E_USER_WARNING
    );
    return;
}

require_once $sidebarDir . '/classes/Employee.php';

$employeeClass = new Employee();

// Employee::getEmployeeName()/getEmployeePosition() return HTML-escaped text already —
// all twelve module classes escape at the source — so these values are printed raw on
// purpose. Escaping them again would show "Tom &amp; Jerry" for a name spelled "Tom & Jerry".
$employeeName = $employeeClass->getEmployeeName();
$employeePosition = $employeeClass->getEmployeePosition();
$employeeInitial = substr($employeeName, 0, 1);

// What the bell menu shows. Read through the shared Notification class so all twelve
// modules get the same behaviour; an employee with nothing waiting — or a database
// without the table — renders the empty state instead of failing the whole page.
$notifications      = [];
$notificationUnread = 0;
$notificationUserId = (int) ($current_employee_id ?? $_SESSION['employee_id'] ?? 0);

if ($notificationUserId > 0) {
    require_once __DIR__ . '/Notification.php';

    try {
        $notificationModel  = new Notification();
        $notifications      = $notificationModel->getRecent($notificationUserId, 6);
        $notificationUnread = $notificationModel->getUnreadCount($notificationUserId);
    } catch (Throwable $e) {
        error_log('Sidebar notifications unavailable: ' . $e->getMessage());
    }
}

// null means this module's sidebar shows no heading above the nav.
$sidebarTitle = $sidebar['title'] ?? null;

?>

<aside class="sidebar">
    <div class="school-logo">
        <img src="assets/bcp-logo.png" alt="School Logo">
        <div class="sidebar-icons">

            <!-- Bell Icon + Notification Dropdown -->
            <div class="icon-wrapper" id="bellWrapper">
                <i class="fa-regular fa-bell" id="bellBtn"></i>
                <!-- Live unread count. Kept in the markup because dropdown.js hides this
                     exact element when everything is read; it stays hidden at zero. The CSS
                     is a 16px circle, so two digits would not fit. -->
                <span class="notif-badge<?= $notificationUnread > 0 ? '' : ' hidden' ?>" id="notifBadge"><?= $notificationUnread > 9 ? '9+' : $notificationUnread ?></span>
                <div class="icon-dropdown" id="bellDropdown" data-notifications-endpoint="<?= htmlspecialchars(AppBase::pathFor('includes/ajax/notifications.php')) ?>">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <button class="mark-all-read">Mark all as read</button>
                    </div>
                    <ul class="notif-list">
<?php if ($notifications === []): ?>
                        <li class="notif-item">
                            <div class="notif-content">
                                <p>No notifications yet</p>
                                <span>Updates about your work will show up here.</span>
                            </div>
                        </li>
<?php else: ?>
<?php foreach ($notifications as $notification): ?>
                        <li class="notif-item<?= $notification['is_read'] === 0 ? ' unread' : '' ?>"
                            data-notification-id="<?= $notification['id'] ?>"
                            title="<?= htmlspecialchars($notification['message'], ENT_QUOTES) ?>">
                            <span class="notif-icon"><i class="fa-solid <?= htmlspecialchars(Notification::iconFor($notification['type'])) ?>"></i></span>
                            <div class="notif-content">
                                <p><?= htmlspecialchars($notification['title']) ?></p>
                                <span><?= htmlspecialchars(Notification::timeAgo($notification['created_at'])) ?></span>
                            </div>
                        </li>
<?php endforeach; ?>
<?php endif; ?>
                    </ul>
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
                                <?= $employeeInitial ?>
                            </div>
                            <div>
                                <strong><?= $employeeName ?></strong>
                                <span><?= $employeePosition ?></span>
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
                            <a href="<?= htmlspecialchars(AppBase::pathFor('auth/logout.php')) ?>" class="signout-link">
                                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
    <div class="sidebar-header">
        <div class="user_avatar"><?= $employeeInitial ?></div>
        <h1 class="employee_name"><?= $employeeName ?></h1>
        <p class="employee_position"><?= $employeePosition ?></p>
    </div>
<?php if ($sidebarTitle !== null && $sidebarTitle !== ''): ?>
    <h2><?= htmlspecialchars($sidebarTitle) ?></h2>
<?php endif; ?>
    <ul>
        <?php $pageController->renderNav(); ?>
    </ul>
</aside>
<?php // The bell's behaviour travels with the markup it drives, so all twelve modules run one copy. ?>
<script defer src="<?= htmlspecialchars(AppBase::pathFor('includes/js/sidebar-notifications.js')) ?>"></script>
