<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../includes/notification-display.php';

$employeeClass = new Employee();
$adminId = (int) ($employeeClass->getEmployeeId() ?? 0);

$notifications = [];
$unreadNotifications = [];
$announcements = [];
$notificationCount = 0;
$unreadCount = 0;
$announcementCount = 0;

try {
    $pdo = (new Database())->getConnection();

    // Tile numbers come from the table: the lists below stop at the 50 newest rows.
    $totals = $pdo->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread
         FROM ld_notification_inbox
         WHERE user_id = :uid"
    );
    $totals->execute([':uid' => $adminId]);
    $totalsRow = $totals->fetch(PDO::FETCH_ASSOC) ?: [];
    $notificationCount = (int) ($totalsRow['total'] ?? 0);
    $unreadCount = (int) ($totalsRow['unread'] ?? 0);

    $listStmt = $pdo->prepare(
        "SELECT id, type, title, message, reference_type, reference_id, is_read, created_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                DATE_FORMAT(created_at, '%b %e, %Y') AS created_label
         FROM ld_notification_inbox
         WHERE user_id = :uid
         ORDER BY created_at DESC
         LIMIT 50"
    );
    $listStmt->execute([':uid' => $adminId]);
    $notifications = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadNotifications = array_values(array_filter($notifications, static function ($row) {
        return (int) ($row['is_read'] ?? 0) === 0;
    }));

    $announcementCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM ld_announcement
         WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= NOW())"
    )->fetchColumn();

    $announcementStmt = $pdo->prepare(
        "SELECT id, title, message, audience, posted_by, status, created_at, expires_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                DATE_FORMAT(created_at, '%b %e, %Y') AS created_label,
                DATE_FORMAT(expires_at, '%b %e, %Y') AS expires_label
         FROM ld_announcement
         WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= NOW())
         ORDER BY created_at DESC
         LIMIT 50"
    );
    $announcementStmt->execute();
    $announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'admin/notification');
}

/**
 * Notification feed rows. The shared .cards-grid / .content-card-item hooks are
 * kept so search and pagination keep working; rows carry the notification id so
 * the page script can mark them read.
 */
function renderAdminNotificationRows(array $items): void
{
    if (empty($items)) {
        ntfEmptyState('fa-bell-slash', 'No notifications', 'Enrollments, grades, certificates and reminders addressed to you will appear here.');
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $type = (string) ($item['type'] ?? 'update');
        $isRead = (int) ($item['is_read'] ?? 0) === 1;

        echo '<article class="content-card-item ntf-item ' . ($isRead ? 'is-read' : 'is-unread') . '"'
            . ' data-notif-id="' . $id . '"'
            . ' data-read="' . ($isRead ? 'true' : 'false') . '"'
            . ' data-status="' . ($isRead ? 'read' : 'unread') . '"'
            . ' data-type="' . htmlspecialchars($type) . '">';
        echo '<span class="ntf-icon is-' . ldNotifTone($type) . '" aria-hidden="true"><i class="fas ' . ldNotifIcon($type) . '"></i></span>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta">'
            . '<span class="pill">' . htmlspecialchars(ldNotifLabel($type)) . '</span>'
            . '<span class="ntf-time"><i class="far fa-clock" aria-hidden="true"></i> ' . htmlspecialchars(ldNotifAge($item)) . '</span>'
            . '</div>';
        echo '<h3>' . htmlspecialchars((string) ($item['title'] ?? 'Untitled notification')) . '</h3>';
        echo '<p>' . htmlspecialchars((string) ($item['message'] ?? '')) . '</p>';
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';

    ntfPaginationRow(count($items));
}

/** Announcement cards for the announcements tab. */
function renderAdminAnnouncements(array $items): void
{
    if (empty($items)) {
        ntfEmptyState('fa-bullhorn', 'No announcements', 'Announcements you publish to the Learning module audiences will be listed here.');
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $title = (string) ($item['title'] ?? 'Untitled announcement');
        $audience = ldNotifAudienceLabel($item['audience'] ?? null);
        $expires = trim((string) ($item['expires_label'] ?? ''));
        $expires = ($expires === '' || empty($item['expires_at'])) ? null : $expires;

        echo '<article class="content-card-item ntf-item ntf-announce" data-status="announcement">';
        echo '<span class="ntf-icon is-indigo" aria-hidden="true"><i class="fas fa-bullhorn"></i></span>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta">'
            . '<span class="pill">' . htmlspecialchars($audience) . '</span>'
            . '<span class="ntf-time"><i class="far fa-clock" aria-hidden="true"></i> ' . htmlspecialchars(ldNotifAge($item)) . '</span>'
            . '</div>';
        echo '<h3>' . htmlspecialchars($title) . '</h3>';
        echo '<p>' . htmlspecialchars((string) ($item['message'] ?? '')) . '</p>';
        if ($expires !== null) {
            echo '<div class="content-card-footer"><span><i class="fas fa-hourglass-half" aria-hidden="true"></i> Expires ' . htmlspecialchars($expires) . '</span></div>';
        }
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';

    ntfPaginationRow(count($items));
}
?>
<div class="module-content ntf-page">
    <div class="home-dashboard">
        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Notifications</h1>
                <p>System alerts, enrollment and grading activity, plus the announcements you publish to learners and instructors.</p>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon tone-violet"><i class="fas fa-bell"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Notifications</span>
                    <span class="home-stat-value" data-count="total"><?= $notificationCount ?></span>
                    <span class="home-stat-sub">addressed to you</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-rose"><i class="fas fa-envelope-open-text"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Unread</span>
                    <span class="home-stat-value" data-count="unread"><?= $unreadCount ?></span>
                    <span class="home-stat-sub">still need a look</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-bullhorn"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Announcements</span>
                    <span class="home-stat-value"><?= $announcementCount ?></span>
                    <span class="home-stat-sub">active right now</span>
                </span>
            </div>
        </div>

        <div class="ntf-toolbar">
            <div class="toolbar-search">
                <input type="search" placeholder="Search notifications..." aria-label="Search notifications" />
            </div>
            <div class="ntf-actions-bar">
                <?php ntfPreferencesButton(); ?>
                <button type="button" id="ntf-mark-all-read" class="ntf-btn">
                    <i class="fas fa-check-double" aria-hidden="true"></i> Mark all read
                </button>
                <?php include dirname(__DIR__, 2) . '/includes/announcement-composer-trigger.php'; ?>
            </div>
        </div>
        <?php ntfPreferencesPanel(); ?>
        <?php $announcementComposerRole = 'admin'; include dirname(__DIR__, 2) . '/includes/announcement-composer.php'; ?>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-notif-all">
                    <i class="fas fa-inbox" aria-hidden="true"></i> All (<?= count($notifications) ?>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-unread">
                    <i class="fas fa-envelope-open-text" aria-hidden="true"></i> Unread (<span data-count="unread"><?= $unreadCount ?></span>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-announcements">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i> Announcements (<?= count($announcements) ?>)
                </button>
            </div>

            <div class="tab-content active" data-tab="tab-notif-all" id="tab-notif-all">
                <?php renderAdminNotificationRows($notifications); ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-unread" id="tab-notif-unread">
                <?php renderAdminNotificationRows($unreadNotifications); ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-announcements" id="tab-notif-announcements">
                <?php renderAdminAnnouncements($announcements); ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var page = document.querySelector('.ntf-page');
    if (!page) return;

    var csrfToken = window.CSRF_TOKEN
        || (document.querySelector('meta[name="csrf-token"]') || {}).content
        || '';

    function setUnreadCounts(value) {
        document.querySelectorAll('.ntf-page [data-count="unread"]').forEach(function (node) {
            node.textContent = String(value);
        });
    }

    function currentUnreadCount() {
        var node = document.querySelector('.ntf-page [data-count="unread"]');
        return Math.max(0, parseInt(node ? node.textContent : '0', 10) || 0);
    }

    function markRowRead(row) {
        row.dataset.read = 'true';
        row.dataset.status = 'read';
        row.classList.remove('is-unread');
        row.classList.add('is-read');
    }

    page.addEventListener('click', function (event) {
        var row = event.target.closest('.ntf-item');
        if (!row || !page.contains(row) || row.dataset.read !== 'false') return;

        var notificationId = row.dataset.notifId;
        if (!notificationId) return;

        row.dataset.read = 'pending';
        fetch('pages/admin/ajax/mark-notification-read.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: 'id=' + encodeURIComponent(notificationId)
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || data.success !== true) throw new Error('failed');
                markRowRead(row);
                setUnreadCounts(currentUnreadCount() - 1);
            })
            .catch(function () {
                row.dataset.read = 'false';
            });
    });

    var markAllButton = document.getElementById('ntf-mark-all-read');
    if (markAllButton) {
        markAllButton.addEventListener('click', function () {
            markAllButton.disabled = true;
            fetch('pages/admin/ajax/mark-all-notification-read.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: 'all=1'
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data || data.success !== true) throw new Error('failed');
                    page.querySelectorAll('.ntf-item[data-read="false"]').forEach(markRowRead);
                    setUnreadCounts(0);
                    if (typeof window.showToast === 'function') {
                        window.showToast('All notifications marked as read.', 'success');
                    }
                })
                .catch(function () {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Could not update your notifications.', 'error');
                    }
                })
                .finally(function () {
                    markAllButton.disabled = false;
                });
        });
    }
})();
</script>
