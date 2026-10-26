<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/announcement.php';
require_once __DIR__ . '/../../classes/message.php';
require_once __DIR__ . '/../../includes/notification-display.php';

$employeeClass = new Employee();
$employeeId = (int) ($employeeClass->getEmployeeId() ?? 0);

$announcements = [];
$messages = [];
$announcementCount = 0;
$messageCount = 0;
$unreadCount = 0;

try {
    $pdo = (new Database())->getConnection();

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM ld_announcement
         WHERE status = 'active'
           AND (expires_at IS NULL OR expires_at >= NOW())
           AND (FIND_IN_SET('all', audience) > 0 OR FIND_IN_SET('instructor', audience) > 0 OR posted_by = :employee_id)"
    );
    $countStmt->execute([':employee_id' => $employeeId]);
    $announcementCount = (int) $countStmt->fetchColumn();

    $announcementStmt = $pdo->prepare(
        "SELECT id, title, message, audience, status, created_at, expires_at,
                TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds,
                DATE_FORMAT(created_at, '%b %e, %Y') AS created_label,
                DATE_FORMAT(expires_at, '%b %e, %Y') AS expires_label
         FROM ld_announcement
         WHERE status = 'active'
           AND (expires_at IS NULL OR expires_at >= NOW())
           AND (FIND_IN_SET('all', audience) > 0 OR FIND_IN_SET('instructor', audience) > 0 OR posted_by = :employee_id)
         ORDER BY created_at DESC
         LIMIT 50"
    );
    $announcementStmt->execute([':employee_id' => $employeeId]);
    $announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($employeeId > 0) {
        $messageStmt = $pdo->prepare(
            "SELECT m.id, m.sender_id, m.subject, m.body, m.is_read, m.created_at,
                    TIMESTAMPDIFF(SECOND, m.created_at, NOW()) AS age_seconds,
                    DATE_FORMAT(m.created_at, '%b %e, %Y') AS created_label,
                    CONCAT(e.first_name, ' ', e.last_name) AS sender_name
             FROM ld_message m
             LEFT JOIN em_employees e ON e.employee_id = m.sender_id
             WHERE m.recipient_id = :recipient_id
             ORDER BY m.created_at DESC
             LIMIT 50"
        );
        $messageStmt->execute([':recipient_id' => $employeeId]);
        $messages = $messageStmt->fetchAll(PDO::FETCH_ASSOC);

        $unreadCount = (new Message($pdo))->getUnreadCount($employeeId);
    }

    $messageCount = count($messages);
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/notification');
    $announcements = [];
    $messages = [];
    $announcementCount = 0;
    $messageCount = 0;
    $unreadCount = 0;
}

/** Announcement cards for the announcements tab. */
function renderInstructorAnnouncements(array $items): void
{
    if (empty($items)) {
        ntfEmptyState('fa-bullhorn', 'No announcements', 'Policy updates and platform notices addressed to trainers will be listed here.');
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
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
        echo '<h3>' . htmlspecialchars((string) ($item['title'] ?? 'Untitled announcement')) . '</h3>';
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

/** Inbox rows. The unread state is read-only here: messages are opened from the inbox. */
function renderInstructorMessages(array $items, ?PDO $pdo = null): void
{
    if (empty($items)) {
        ntfEmptyState('fa-envelope', 'Inbox is clear', 'Messages from trainees, admins and system contacts will arrive here.');
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $isRead = (int) ($item['is_read'] ?? 0) === 1;
        $sender = trim((string) ($item['sender_name'] ?? '')) ?: 'Unknown sender';
        $senderId = (int) ($item['sender_id'] ?? 0);
        $senderRole = $pdo instanceof PDO && $senderId > 0
            ? LearningRole::forEmployee($pdo, $senderId)
            : 'learner';
        $senderUrl = $senderId > 0 ? LearningProfileLink::url($senderId, $senderRole) : '';
        $senderHtml = $senderUrl !== ''
            ? '<a href="' . htmlspecialchars($senderUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($sender) . '</a>'
            : htmlspecialchars($sender);
        $subject = trim((string) ($item['subject'] ?? '')) ?: 'No subject';

        echo '<article class="content-card-item ntf-item ' . ($isRead ? 'is-read' : 'is-unread') . '"'
            . ' data-status="' . ($isRead ? 'read' : 'unread') . '">';
        echo '<span class="ntf-icon is-' . ($isRead ? 'slate' : 'violet') . '" aria-hidden="true"><i class="fas fa-envelope' . ($isRead ? '-open' : '') . '"></i></span>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta">'
            . '<span class="pill">Message</span>'
            . '<span class="ntf-time"><i class="far fa-clock" aria-hidden="true"></i> ' . htmlspecialchars(ldNotifAge($item)) . '</span>'
            . '</div>';
        echo '<h3>' . htmlspecialchars($subject) . '</h3>';
        echo '<p>' . htmlspecialchars((string) ($item['body'] ?? '')) . '</p>';
        echo '<div class="content-card-footer">'
            . '<span><i class="fas fa-user" aria-hidden="true"></i> ' . $senderHtml . '</span>'
            . '<span class="ntf-state ' . ($isRead ? 'is-read' : 'is-unread') . '">' . ($isRead ? 'Read' : 'Unread') . '</span>'
            . '</div>';
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
                <h1>Notification</h1>
                <p>Announcements addressed to trainers and every message waiting in your inbox, in one place.</p>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-bullhorn"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Announcements</span>
                    <span class="home-stat-value"><?= $announcementCount ?></span>
                    <span class="home-stat-sub">for your audience</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-violet"><i class="fas fa-envelope"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Inbox messages</span>
                    <span class="home-stat-value"><?= $messageCount ?></span>
                    <span class="home-stat-sub">in your inbox</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-rose"><i class="fas fa-envelope-open-text"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Unread</span>
                    <span class="home-stat-value"><?= $unreadCount ?></span>
                    <span class="home-stat-sub">waiting for a reply</span>
                </span>
            </div>
        </div>

        <div class="ntf-toolbar">
            <div class="toolbar-search">
                <input type="search" placeholder="Search announcements and messages..." aria-label="Search announcements and messages" />
            </div>
            <div class="ntf-actions-bar">
                <?php ntfPreferencesButton(); ?>
                <?php include dirname(__DIR__, 2) . '/includes/announcement-composer-trigger.php'; ?>
            </div>
        </div>
        <?php ntfPreferencesPanel(); ?>
        <?php $announcementComposerRole = 'instructor'; include dirname(__DIR__, 2) . '/includes/announcement-composer.php'; ?>

        <div class="tab-container">
            <div class="tab-list">
                <button type="button" class="tab-item active" data-tab="tab-notif-announcements">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i> Announcements (<?= count($announcements) ?>)
                </button>
                <button type="button" class="tab-item" data-tab="tab-notif-messages">
                    <i class="fas fa-envelope" aria-hidden="true"></i> Messages (<?= count($messages) ?>)
                </button>
            </div>

            <div class="tab-content active" data-tab="tab-notif-announcements" id="tab-notif-announcements">
                <?php renderInstructorAnnouncements($announcements); ?>
            </div>

            <div class="tab-content" data-tab="tab-notif-messages" id="tab-notif-messages">
                <?php renderInstructorMessages($messages, $pdo ?? null); ?>
            </div>
        </div>
    </div>
</div>
