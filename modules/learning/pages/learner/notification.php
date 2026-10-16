<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/certificate-expiry-checker.php';
require_once __DIR__ . '/../../includes/notification-display.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$notifications = [];
$unreadNotifications = [];
$expiryNotifications = [];
$announcements = [];
$certificates = [];
$notificationCount = 0;
$unreadCount = 0;
$announcementCount = 0;
$expiryCount = 0;

try {
    $pdo = (new Database())->getConnection();

    // Certificate expiry notices are generated on every visit, so the tiles and
    // the expiry tab always agree with what the learner can see. A failure here
    // must not take the page down with it.
    try {
        checkCertificateExpiryNotifications($pdo, $learnerId);
    } catch (Throwable $generatorError) {
        error_log('[learning] certificate expiry notification generation failed: ' . $generatorError->getMessage());
    }

    $totals = $pdo->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread
         FROM ld_notification_inbox
         WHERE user_id = :uid"
    );
    $totals->execute([':uid' => $learnerId]);
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
    $listStmt->execute([':uid' => $learnerId]);
    $notifications = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadNotifications = array_values(array_filter($notifications, static function ($row) {
        return (int) ($row['is_read'] ?? 0) === 0;
    }));

    $expiryStmt = $pdo->prepare(
        "SELECT id, type, title, message, reference_type, reference_id, is_read, created_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                DATE_FORMAT(created_at, '%b %e, %Y') AS created_label
         FROM ld_notification_inbox
         WHERE user_id = :uid AND type = 'certificate_expiry'
         ORDER BY created_at DESC
         LIMIT 50"
    );
    $expiryStmt->execute([':uid' => $learnerId]);
    $expiryNotifications = $expiryStmt->fetchAll(PDO::FETCH_ASSOC);
    $expiryCount = count($expiryNotifications);

    // One lookup for every certificate referenced by a notice, instead of a query
    // per row while the list renders.
    $referenceIds = [];
    foreach (array_merge($notifications, $expiryNotifications) as $row) {
        if (($row['type'] ?? '') === 'certificate_expiry' && !empty($row['reference_id'])) {
            $referenceIds[(int) $row['reference_id']] = true;
        }
    }

    if (!empty($referenceIds)) {
        $ids = array_keys($referenceIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $certStmt = $pdo->prepare(
            "SELECT id, verification_code, course_id
             FROM ld_certificate
             WHERE status = 'active' AND id IN ($placeholders)"
        );
        $certStmt->execute($ids);
        foreach ($certStmt->fetchAll(PDO::FETCH_ASSOC) as $certificate) {
            $certificates[(int) $certificate['id']] = $certificate;
        }
    }

    $announcementCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM ld_announcement
         WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= NOW())
           AND (FIND_IN_SET('learner', audience) > 0 OR FIND_IN_SET('all', audience) > 0)"
    )->fetchColumn();

    $announcementStmt = $pdo->prepare(
        "SELECT id, title, message, audience, posted_by, status, created_at, expires_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                DATE_FORMAT(created_at, '%b %e, %Y') AS created_label,
                DATE_FORMAT(expires_at, '%b %e, %Y') AS expires_label
         FROM ld_announcement
         WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= NOW())
           AND (FIND_IN_SET('learner', audience) > 0 OR FIND_IN_SET('all', audience) > 0)
         ORDER BY created_at DESC
         LIMIT 50"
    );
    $announcementStmt->execute();
    $announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'learner/notification');
}

/**
 * Notification feed rows. Certificate notices additionally expose the verify and
 * renew actions; everything else is a plain read-only row.
 */
function renderLearnerNotificationRows(array $items, array $certificates): void
{
    if (empty($items)) {
        ntfEmptyState('fa-bell-slash', 'No notifications', 'Enrollments, grades, certificates and reminders will show up here.');
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $type = (string) ($item['type'] ?? 'update');
        $isRead = (int) ($item['is_read'] ?? 0) === 1;
        $certificate = ($type === 'certificate_expiry' && !empty($item['reference_id']))
            ? ($certificates[(int) $item['reference_id']] ?? null)
            : null;

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

        if ($certificate && !empty($certificate['verification_code'])) {
            $courseId = (int) ($certificate['course_id'] ?? 0);
            echo '<div class="content-card-footer is-actions"><div class="ntf-actions">';
            echo '<a class="ntf-btn ghost" target="_blank" rel="noopener" href="?page=public/verify-certificate&amp;back=learner/notification&amp;code=' . urlencode((string) $certificate['verification_code']) . '">'
                . '<i class="fas fa-external-link-alt" aria-hidden="true"></i> View certificate</a>';
            if ($courseId > 0) {
                echo '<button type="button" class="ntf-btn cert-renew-btn" data-course-id="' . $courseId . '">'
                    . '<i class="fas fa-sync-alt" aria-hidden="true"></i> Renew course</button>';
            }
            echo '</div></div>';
        }

        echo '</div>';
        echo '</article>';
    }
    echo '</div>';

    ntfPaginationRow(count($items));
}

/** Announcement cards for the announcements tab. */
function renderLearnerAnnouncements(array $items): void
{
    if (empty($items)) {
        ntfEmptyState('fa-bullhorn', 'No announcements', 'When instructors or admins publish an update for learners it will appear here.');
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
<div class="module-content ntf-page ntf-page--four">
    <div class="home-dashboard">
        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Notifications</h1>
                <p>Enrollment, grading and certificate updates, plus every announcement published to learners.</p>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon tone-violet"><i class="fas fa-bell"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Notifications</span>
                    <span class="home-stat-value" data-count="total"><?= $notificationCount ?></span>
                    <span class="home-stat-sub">all activity</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-rose"><i class="fas fa-envelope-open-text"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Unread</span>
                    <span class="home-stat-value" data-count="unread"><?= $unreadCount ?></span>
                    <span class="home-stat-sub">waiting for you</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-amber"><i class="fas fa-certificate"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Certificate expiry</span>
                    <span class="home-stat-value"><?= $expiryCount ?></span>
                    <span class="home-stat-sub">need renewing</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-bullhorn"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Announcements</span>
                    <span class="home-stat-value"><?= $announcementCount ?></span>
                    <span class="home-stat-sub">from your team</span>
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
            </div>
        </div>

        <?php ntfPreferencesPanel(); ?>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-notif-all">
                    <i class="fas fa-inbox" aria-hidden="true"></i> All (<?= count($notifications) ?>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-unread">
                    <i class="fas fa-envelope-open-text" aria-hidden="true"></i> Unread (<span data-count="unread"><?= $unreadCount ?></span>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-expiry">
                    <i class="fas fa-certificate" aria-hidden="true"></i> Certificate expiry (<?= $expiryCount ?>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-announcements">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i> Announcements (<?= count($announcements) ?>)
                </button>
            </div>

            <div class="tab-content active" data-tab="tab-notif-all" id="tab-notif-all">
                <?php renderLearnerNotificationRows($notifications, $certificates); ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-unread" id="tab-notif-unread">
                <?php if (empty($unreadNotifications)) {
                    ntfEmptyState('fa-circle-check', 'All caught up', 'You have read every notification you received.');
                } else {
                    renderLearnerNotificationRows($unreadNotifications, $certificates);
                } ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-expiry" id="tab-notif-expiry">
                <?php if (empty($expiryNotifications)) {
                    ntfEmptyState('fa-certificate', 'No expiring certificates', 'Your certificates are valid and none of them are nearing their expiry date.');
                } else {
                    renderLearnerNotificationRows($expiryNotifications, $certificates);
                } ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-announcements" id="tab-notif-announcements">
                <?php renderLearnerAnnouncements($announcements); ?>
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

    // Certificate notices offer a one-click re-enrol. Registered first so the
    // row handler below still records the notice as read afterwards.
    page.addEventListener('click', function (event) {
        var button = event.target.closest('.cert-renew-btn');
        if (!button) return;

        var courseId = parseInt(button.dataset.courseId || '0', 10);
        if (!courseId) return;

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Enrolling...';

        fetch('pages/learner/ajax/enroll-course.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ course_id: courseId })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || data.success !== true) {
                    throw new Error((data && (data.error || data.message)) || 'Could not re-enrol in this course.');
                }
                button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Enrolled';
                button.classList.add('is-done');
                if (typeof window.showToast === 'function') {
                    window.showToast('Re-enrolled in the course successfully.', 'success');
                }
            })
            .catch(function (error) {
                button.innerHTML = '<i class="fas fa-sync-alt" aria-hidden="true"></i> Renew course';
                button.disabled = false;
                if (typeof window.showToast === 'function') {
                    window.showToast(error.message || 'Could not re-enrol in this course.', 'error');
                }
            });
    });

    page.addEventListener('click', function (event) {
        var row = event.target.closest('.ntf-item');
        if (!row || !page.contains(row) || row.dataset.read !== 'false') return;

        var notificationId = row.dataset.notifId;
        if (!notificationId) return;

        row.dataset.read = 'pending';
        fetch('pages/learner/ajax/mark-notification-read.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: 'notification_id=' + encodeURIComponent(notificationId)
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
            fetch('pages/learner/ajax/mark-all-notification-read.php', {
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
