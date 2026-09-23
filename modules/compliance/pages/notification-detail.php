<?php
$pageTitle = 'Notifications';
$skipModuleHeader = false;

require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../../../database/db.php';

$siteTimezone = 'Asia/Manila';
$db = null;
try {
    $database = new Database();
    $db = $database->getConnection();

    if (!($db instanceof PDO)) {
        http_response_code(500);
        echo '<div class="module-content"><p>Database connection unavailable.</p></div>';
        exit;
    }

    $stmt = $db->query("SELECT setting_value FROM ld_setting WHERE setting_key = 'site_timezone' LIMIT 1");
    $tz = $stmt->fetchColumn();
    if ($tz && is_string($tz)) {
        $siteTimezone = $tz;
    }
} catch (Throwable $e) {
    error_log('Notification detail timezone lookup error: ' . $e->getMessage());
}
date_default_timezone_set($siteTimezone);

$notificationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($notificationId <= 0) {
    http_response_code(400);
    echo '<div class="module-content"><p>Invalid notification ID.</p></div>';
    exit;
}

if (!($db instanceof PDO)) {
  http_response_code(500);
  echo '<div class="module-content"><p>Database connection unavailable.</p></div>';
  exit;
}

try {
    $stmt = $db->prepare('SELECT id, title, message, type, module, is_read, created_at, email, sender_email, employee_id FROM lc_notifications WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $notificationId]);
    $currentNotification = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Notification detail fetch error: ' . $e->getMessage());
    $currentNotification = false;
}

if (!$currentNotification) {
    http_response_code(404);
    echo '<div class="module-content"><p>Notification not found.</p></div>';
    exit;
}

$currentNotification['id'] = (int) $currentNotification['id'];
$currentNotification['is_read'] = (int) ($currentNotification['is_read'] ?? 0);
$currentNotification['type'] = strtolower((string) ($currentNotification['type'] ?? 'info'));
$currentNotification['module'] = (string) ($currentNotification['module'] ?? 'Compliance');
$currentNotification['title'] = (string) ($currentNotification['title'] ?? 'Notification');
$currentNotification['message'] = trim((string) ($currentNotification['message'] ?? ''));
$currentNotification['email'] = (string) ($currentNotification['email'] ?? '');
$currentNotification['sender_email'] = (string) ($currentNotification['sender_email'] ?? '');
$currentNotification['employee_id'] = (string) ($currentNotification['employee_id'] ?? '');
$currentNotification['created_at'] = (string) ($currentNotification['created_at'] ?? '');

$pageTitle = 'Notifications';

function escapeHtml($text) {
    if ($text === null) return '';
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function formatNotificationDate($createdAt, $tz) {
    if (!$createdAt) return ['date' => '—', 'time' => '—'];
    try {
        $dt = new DateTime($createdAt, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($tz));
        return [
            'date' => $dt->format('M j, Y'),
            'time' => $dt->format('g:i A'),
        ];
    } catch (Exception $e) {
        $ts = strtotime($createdAt);
        if ($ts) {
            return [
                'date' => date('M j, Y', $ts),
                'time' => date('g:i A', $ts),
            ];
        }
        return ['date' => '—', 'time' => '—'];
    }
}

function typeLabel(string $type): string {
    $labels = [
        'danger'  => 'Alert',
        'warning' => 'Warning',
        'info'    => 'Info',
        'success' => 'Success',
        'primary' => 'Notice',
    ];
    return $labels[$type] ?? ucfirst($type ?: 'Notification');
}

function typeBadgeClass(string $type): string {
    $map = [
        'danger'  => 'bg-danger-subtle text-danger border border-danger-subtle',
        'warning' => 'bg-warning-subtle text-warning border border-warning-subtle',
        'info'    => 'bg-info-subtle text-info border border-info-subtle',
        'success' => 'bg-success-subtle text-success border border-success-subtle',
        'primary' => 'bg-primary-subtle text-primary border border-primary-subtle',
    ];
    return $map[$type] ?? 'bg-secondary-subtle text-secondary border border-secondary-subtle';
}

$currentFormatted = formatNotificationDate($currentNotification['created_at'], $siteTimezone);

$allNotifications = [];
try {
    $sql = 'SELECT id, title, message, type, module, is_read, created_at, email, sender_email, employee_id FROM lc_notifications ORDER BY created_at DESC LIMIT 100';
    $stmt = $db->query($sql);
    $allNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Notification list fetch error: ' . $e->getMessage());
}

$hasEmail = !empty($currentNotification['sender_email']) ? $currentNotification['sender_email'] : $currentNotification['email'];
$replyDisabled = empty($hasEmail) ? ' disabled' : '';
$forwardDisabled = empty($hasEmail) ? ' disabled' : '';
$replyHref = !empty($hasEmail) ? '?page=notification-compose&mode=reply&notification_id=' . $currentNotification['id'] . '&notification_key=' . $currentNotification['type'] . '&to_recipient_email=' . urlencode($hasEmail) : '#';
$forwardHref = !empty($hasEmail) ? '?page=notification-compose&mode=forward&notification_id=' . $currentNotification['id'] . '&notification_key=' . $currentNotification['type'] . '&subject=' . urlencode('Fwd: ' . $currentNotification['title']) . '&body=' . urlencode($currentNotification['message']) : '#';
$replyOnclick = empty($hasEmail) ? ' onclick="return false;"' : '';
$forwardOnclick = empty($hasEmail) ? ' onclick="return false;"' : '';
?>

<div class="module-content">
<section class="cc-module nc-notif-detail">

  <div class="nc-history-grid">
    <div class="nc-history-wrap">
      <div class="nc-history-list" id="ncHistoryList">
        <?php if (empty($allNotifications)): ?>
          <div class="nc-empty-state" style="padding: 2.5rem 1.5rem; border-radius: 12px;">
            <div class="nc-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <h3>No Notifications</h3>
            <p>You have no notifications at this time.</p>
          </div>
        <?php else: ?>
          <?php foreach ($allNotifications as $index => $notif): ?>
            <?php
              $notifId = (int)($notif['id'] ?? 0);
              $notifType = strtolower((string)($notif['type'] ?? 'info'));
              $notifTitle = (string)($notif['title'] ?? '');
              $notifMessage = (string)($notif['message'] ?? '');
              $notifModule = (string)($notif['module'] ?? 'Compliance');
              $notifIsRead = (int)($notif['is_read'] ?? 0) === 1;
              $notifCreatedAt = (string)($notif['created_at'] ?? '');
              $notifEmail = (string)($notif['email'] ?? '');
              $notifSenderEmail = (string)($notif['sender_email'] ?? '');
              $notifEmployeeId = (string)($notif['employee_id'] ?? '');

              $dates = formatNotificationDate($notifCreatedAt, $siteTimezone);
              $typeLabelStr = typeLabel($notifType);
              $typeBadgeStr = typeBadgeClass($notifType);
              $isSelected = $notifId === $notificationId;
              $selectedClass = $isSelected ? ' selected' : '';
            ?>
            <a href="?page=notification-detail&id=<?= $notifId ?>" class="nc-history-card<?= $selectedClass ?>" data-id="<?= $notifId ?>">
              <div class="nc-history-card-top">
                <div class="nc-history-card-title"><?= escapeHtml($notifTitle) ?></div>
                <div class="nc-history-card-meta">
                  <span class="badge <?= escapeHtml($typeBadgeStr) ?>"><?= escapeHtml($typeLabelStr) ?></span>
                  <span class="nc-history-card-date"><?= escapeHtml($dates['date']) ?></span>
                </div>
              </div>

              <div class="nc-history-card-mid">
                <div class="nc-history-card-recipient">
                  <i class="fa-solid fa-cube"></i>
                  <span class="nc-history-card-name"><?= escapeHtml($notifModule) ?></span>
                </div>
                <div class="nc-history-card-preview" title="<?= escapeHtml($notifMessage) ?>">
                  <?= escapeHtml(strlen($notifMessage) > 120 ? substr($notifMessage, 0, 120) . '...' : $notifMessage) ?>
                </div>
              </div>

              <div class="nc-history-card-bottom">
                <span class="badge <?= $notifIsRead ? 'bg-secondary-subtle text-secondary' : 'bg-primary' ?>">
                  <?= $notifIsRead ? 'Read' : 'Unread' ?>
                </span>
                <span class="nc-history-card-time"><?= escapeHtml($dates['time']) ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <aside class="nc-preview-sidebar">
      <div class="nc-preview-card">
        <div class="nc-preview-header">
          <h3>Details</h3>
        </div>
        <div class="nc-preview-body">
          <div class="nc-sh-detail-grid">
            <div class="nc-sh-detail-label">Notification ID</div>
            <div class="nc-sh-detail-value">#<?= escapeHtml($currentNotification['id']) ?></div>

            <div class="nc-sh-detail-label">Type</div>
            <div class="nc-sh-detail-value">
              <span class="badge <?= escapeHtml(typeBadgeClass($currentNotification['type'])) ?>">
                <?= escapeHtml(typeLabel($currentNotification['type'])) ?>
              </span>
            </div>

            <div class="nc-sh-detail-label">Module</div>
            <div class="nc-sh-detail-value"><?= escapeHtml($currentNotification['module']) ?></div>

            <div class="nc-sh-detail-label">Status</div>
            <div class="nc-sh-detail-value">
              <?php if ($currentNotification['is_read'] === 0): ?>
                <span class="badge bg-primary">Unread</span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle">Read</span>
              <?php endif; ?>
            </div>

            <div class="nc-sh-detail-label">Sent Date</div>
            <div class="nc-sh-detail-value">
              <div><?= escapeHtml($currentFormatted['date']) ?></div>
              <div class="nc-sh-detail-time"><?= escapeHtml($currentFormatted['time']) ?></div>
            </div>

            <?php if (!empty($currentNotification['sender_email'])): ?>
              <div class="nc-sh-detail-label">Sender Email</div>
              <div class="nc-sh-detail-value">
                <?= escapeHtml($currentNotification['sender_email']) ?>
                <?php if (!empty($currentNotification['email']) && $currentNotification['email'] !== $currentNotification['sender_email']): ?>
                  <div class="nc-sh-detail-email"><?= escapeHtml($currentNotification['email']) ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($currentNotification['employee_id'])): ?>
              <div class="nc-sh-detail-label">Employee ID</div>
              <div class="nc-sh-detail-value"><?= escapeHtml($currentNotification['employee_id']) ?></div>
            <?php endif; ?>
          </div>

          <div class="nc-sh-detail-message">
            <div class="nc-sh-detail-label">Message</div>
            <div class="nc-sh-detail-text"><?= escapeHtml($currentNotification['message']) ?></div>
          </div>

          <div class="nc-nd-actions" style="margin-top: 18px;">
            <a class="nc-action-btn nc-reply-btn<?= $replyDisabled ?>" href="<?= escapeHtml($replyHref) ?>"<?= $replyOnclick ?>>
              <i class="fa-solid fa-reply"></i>
              Reply
            </a>
            <a class="nc-action-btn nc-forward-btn<?= $forwardDisabled ?>" href="<?= escapeHtml($forwardHref) ?>"<?= $forwardOnclick ?>>
              <i class="fa-solid fa-share"></i>
              Forward
            </a>
          </div>
        </div>
      </div>
    </aside>
  </div>

</section>
</div>
