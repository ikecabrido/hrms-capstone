<?php
$pageTitle = 'Notification History';
$skipModuleHeader = false;

require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../../../database/db.php';

$databaseClass = 'Database';
$database = new $databaseClass();
$db = $database->getConnection();

$siteTimezone = 'Asia/Manila';
try {
    $stmt = $db->query("SELECT setting_value FROM ld_setting WHERE setting_key = 'site_timezone' LIMIT 1");
    $tz = $stmt->fetchColumn();
    if ($tz && is_string($tz)) {
        $siteTimezone = $tz;
    }
} catch (Throwable $e) {
    error_log('Sent history timezone lookup error: ' . $e->getMessage());
}
date_default_timezone_set($siteTimezone);

$search    = trim((string)($_GET['search'] ?? ''));
$type      = trim((string)($_GET['type'] ?? ''));
$date      = trim((string)($_GET['date'] ?? ''));
$fromDate  = trim((string)($_GET['from_date'] ?? ''));
$toDate    = trim((string)($_GET['to_date'] ?? ''));
$sentPage  = max(1, (int)($_GET['sent_page'] ?? 1));

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$limit = max(1, min($limit, 100));
$offset = ($sentPage - 1) * $limit;

$where = [];
$params = [];
$searchParams = [];

if ($type !== '') {
    $where[] = 'n.type = :type';
    $params[':type'] = $type;
}

if ($date === 'today') {
    $where[] = 'DATE(n.created_at) = CURDATE()';
} elseif ($date === 'last_7_days') {
    $where[] = 'n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($date === 'this_month') {
    $where[] = 'MONTH(n.created_at) = MONTH(NOW()) AND YEAR(n.created_at) = YEAR(NOW())';
}

if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $where[] = 'DATE(n.created_at) >= :from_date';
    $params[':from_date'] = $fromDate;
}
if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $where[] = 'DATE(n.created_at) <= :to_date';
    $params[':to_date'] = $toDate;
}

$whereSql = implode(' AND ', $where);

if ($search !== '') {
    $searchWhere = ' AND (
        n.email LIKE :search
        OR n.title LIKE :search
        OR n.message LIKE :search
        OR CONCAT(e.first_name, " ", e.last_name) LIKE :search
        OR e.first_name LIKE :search
        OR e.last_name LIKE :search
    )';
    $searchParams[':search'] = '%' . $search . '%';
} else {
    $searchWhere = '';
}

$sentNotifications = [];
$fetchError = null;
$totalCount = 0;

try {
    $countSql = "SELECT COUNT(*) FROM lc_sent_history n LEFT JOIN em_employees e ON n.employee_id = e.employee_id" . ($whereSql !== '' ? " WHERE $whereSql" : '') . $searchWhere;
    $countStmt = $db->prepare($countSql);
    foreach (array_merge($params, $searchParams) as $key => $val) {
        $countStmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    error_log('Sent history count error: ' . $e->getMessage());
}

$totalSent = 0;
$sentToday = 0;
$sentThisMonth = 0;
$unreadCount = 0;

try {
    $totalSent = (int)$db->query("SELECT COUNT(*) FROM lc_sent_history")->fetchColumn();
    $sentToday = (int)$db->query("SELECT COUNT(*) FROM lc_sent_history WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $sentThisMonth = (int)$db->query("SELECT COUNT(*) FROM lc_sent_history WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();
    $unreadCount = (int)$db->query("SELECT COUNT(*) FROM lc_sent_history WHERE is_read = 0")->fetchColumn();
} catch (Throwable $e) {
    error_log('Sent history summary error: ' . $e->getMessage());
}

try {
    $sql = "
        SELECT n.id, n.title, n.message, n.type, n.is_read, n.created_at, n.email, n.sender_email, n.employee_id,
               CONCAT(e.first_name, IFNULL(CONCAT(' ', e.middle_name, ' '), ' '), e.last_name) AS employee_name
        FROM lc_sent_history n
        LEFT JOIN em_employees e ON n.employee_id = e.employee_id" . ($whereSql !== '' ? " WHERE $whereSql" : '') . $searchWhere . "
        ORDER BY n.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $db->prepare($sql);

    foreach ($searchParams as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $sentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Sent history fetch error: ' . $e->getMessage());
    $fetchError = 'Unable to load notification history. Please try again or contact the administrator.';
    $sentNotifications = [];
}

$buildPageUrl = function(int $p) {
    $params = $_GET;
    $params['sent_page'] = $p;
    unset($params['page']);
    return '?page=sent-history&' . http_build_query($params);
};

$totalPages = (int)ceil($totalCount / $limit);
if ($totalPages < 1) $totalPages = 1;
$startRecord = $totalCount > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $limit, $totalCount);

$maxVisible = 5;
$startPageNum = max(1, $sentPage - (int)floor($maxVisible / 2));
$endPageNum = min($totalPages, $startPageNum + $maxVisible - 1);
if ($endPageNum - $startPageNum + 1 < $maxVisible) {
    $startPageNum = max(1, $endPageNum - $maxVisible + 1);
}
$pages = [];
for ($i = $startPageNum; $i <= $endPageNum; $i++) {
    $pages[] = $i;
}

function escapeHtml($text) {
    if ($text === null) return '';
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function cleanMessagePreview($message) {
    return preg_replace('/(On\s+\d{2}\/\d{2}\/\d{4},\s+\d{2}:\d{2}:\d{2}),\s*.*?wrote:/s', '$1', $message);
}

function typeLabel($type) {
    $labels = [
        'danger'  => 'Alert',
        'warning' => 'Warning',
        'info'    => 'Info',
        'success' => 'Success',
        'primary' => 'Notice',
    ];
    return $labels[$type] ?? ucfirst($type ?: 'Notification');
}

function typeBadgeClass($type) {
    $map = [
        'danger'  => 'bg-danger-subtle text-danger border border-danger-subtle',
        'warning' => 'bg-warning-subtle text-warning border border-warning-subtle',
        'info'    => 'bg-info-subtle text-info border border-info-subtle',
        'success' => 'bg-success-subtle text-success border border-success-subtle',
        'primary' => 'bg-primary-subtle text-primary border border-primary-subtle',
    ];
    return $map[$type] ?? 'bg-secondary-subtle text-secondary border border-secondary-subtle';
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
?>
<div class="module-content">
<section class="cc-module">

  <?php if ($fetchError): ?>
    <div class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle"></i>
      <?php echo $fetchError; ?>
    </div>
  <?php endif; ?>

  <div class="nc-sh-cards">
    <div class="nc-sh-card">
      <div class="nc-sh-card-label">Total Sent</div>
      <div class="nc-sh-card-value"><?php echo number_format($totalSent); ?></div>
    </div>
    <div class="nc-sh-card">
      <div class="nc-sh-card-label">Sent Today</div>
      <div class="nc-sh-card-value"><?php echo number_format($sentToday); ?></div>
    </div>
    <div class="nc-sh-card">
      <div class="nc-sh-card-label">This Month</div>
      <div class="nc-sh-card-value"><?php echo number_format($sentThisMonth); ?></div>
    </div>
    <div class="nc-sh-card">
      <div class="nc-sh-card-label">Unread Notifications</div>
      <div class="nc-sh-card-value"><?php echo number_format($unreadCount); ?></div>
    </div>
  </div>

  <?php if (empty($sentNotifications) && !$fetchError): ?>
    <div class="nc-empty-state">
      <div class="nc-empty-icon"><i class="bi bi-clock-history"></i></div>
      <h3>
        <?php if ($search !== '' || $type !== '' || $date !== '' || $fromDate !== '' || $toDate !== ''): ?>
          No notifications match your current filters.
        <?php else: ?>
          No Sent Notifications
        <?php endif; ?>
      </h3>
      <p>
        <?php if ($search !== '' || $type !== '' || $date !== '' || $fromDate !== '' || $toDate !== ''): ?>
          Try adjusting your search or filter criteria.
        <?php else: ?>
          You have not sent any notifications via email yet.
        <?php endif; ?>
      </p>
      <?php if ($search !== '' || $type !== '' || $date !== '' || $fromDate !== '' || $toDate !== ''): ?>
        <a href="?page=sent-history" class="nc-toolbar-btn">Clear Filters</a>
      <?php else: ?>
        <a href="?page=notification-compose&mode=new" class="nc-toolbar-btn">
          <i class="bi bi-plus-lg"></i> Compose Notification
        </a>
      <?php endif; ?>
    </div>
  <?php elseif (!$fetchError): ?>
    <div class="nc-grid-2 nc-history-grid">
      <div class="nc-history-wrap">
        <div class="nc-history-header">
        <a href="?page=notification-compose&mode=new" class="nc-toolbar-btn">
          <i class="bi bi-plus-lg"></i> Compose Notification
        </a>
      </div>
      <div class="nc-history-list" id="ncHistoryList">
          <?php foreach ($sentNotifications as $index => $notif): ?>
            <?php
              $email = trim((string)($notif['email'] ?? ''));
              $employeeName = trim((string)($notif['employee_name'] ?? ''));
              $displayName = $employeeName !== '' ? $employeeName : ($email !== '' ? $email : 'system');
              $createdAt = $notif['created_at'] ?? '';
              $isRead = (int)($notif['is_read'] ?? 0) === 1;
              $notifType = $notif['type'] ?? '';
              $message = (string)($notif['message'] ?? '');
              $cleanMessage = cleanMessagePreview($message);
              $msgPreview = strlen($cleanMessage) > 120 ? substr($cleanMessage, 0, 120) . '...' : $cleanMessage;
              $dates = formatNotificationDate($createdAt, $siteTimezone);
              $typeLabelStr = typeLabel($notifType);
              $typeBadgeStr = typeBadgeClass($notifType);
            ?>
            <div class="nc-history-card"
                 data-id="<?php echo (int)$notif['id']; ?>"
                 data-index="<?php echo $index; ?>"
                 data-email="<?php echo escapeHtml($email); ?>"
                 data-name="<?php echo escapeHtml($employeeName); ?>"
                 data-title="<?php echo escapeHtml($notif['title'] ?? ''); ?>"
                 data-type="<?php echo escapeHtml($typeLabelStr); ?>"
                 data-type-badge="<?php echo escapeHtml($typeBadgeStr); ?>"
                 data-date="<?php echo escapeHtml($dates['date']); ?>"
                 data-time="<?php echo escapeHtml($dates['time']); ?>"
                 data-status="<?php echo $isRead ? 'Read' : 'Unread'; ?>"
                  data-message="<?php echo escapeHtml($cleanMessage); ?>"
                 tabindex="0"
                 role="button"
                 aria-label="View notification: <?php echo escapeHtml($notif['title'] ?? 'No subject'); ?>">
              <div class="nc-history-card-top">
                <div class="nc-history-card-title"><?php echo escapeHtml($notif['title'] ?? ''); ?></div>
                <div class="nc-history-card-meta">
                  <span class="badge <?php echo $typeBadgeStr; ?>"><?php echo escapeHtml($typeLabelStr); ?></span>
                  <span class="nc-history-card-date"><?php echo escapeHtml($dates['date']); ?></span>
                </div>
              </div>
              <div class="nc-history-card-mid">
                <div class="nc-history-card-recipient">
                  <i class="bi bi-person"></i>
                  <span class="nc-history-card-name"><?php echo escapeHtml($displayName); ?></span>
                  <?php if ($email !== ''): ?>
                    <span class="nc-history-card-email">&lt;<?php echo escapeHtml($email); ?>&gt;</span>
                  <?php endif; ?>
                </div>
                <div class="nc-history-card-preview" title="<?php echo escapeHtml($cleanMessage); ?>"><?php echo escapeHtml($msgPreview); ?></div>
              </div>
              <div class="nc-history-card-bottom">
                <span class="badge <?php echo $isRead ? 'bg-secondary-subtle text-secondary' : 'bg-primary'; ?>">
                  <?php echo $isRead ? 'Read' : 'Unread'; ?>
                </span>
                <span class="nc-history-card-time"><?php echo escapeHtml($dates['time']); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <div class="nc-sh-pagination">
            <div class="nc-sh-count">Showing <?php echo $startRecord; ?>–<?php echo $endRecord; ?> of <?php echo number_format($totalCount); ?> notifications</div>
            <div class="nc-sh-pages">
              <?php if ($sentPage > 1): ?>
                <a href="<?php echo $buildPageUrl($sentPage - 1); ?>" class="nc-sh-page-link">&laquo; Previous</a>
              <?php endif; ?>
              <?php foreach ($pages as $p): ?>
                <?php if ($p === $sentPage): ?>
                  <span class="nc-sh-page-current"><?php echo $p; ?></span>
                <?php else: ?>
                  <a href="<?php echo $buildPageUrl($p); ?>" class="nc-sh-page-link"><?php echo $p; ?></a>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if ($sentPage < $totalPages): ?>
                <a href="<?php echo $buildPageUrl($sentPage + 1); ?>" class="nc-sh-page-link">Next &raquo;</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <aside class="nc-preview-sidebar" id="ncPreviewSidebar">
        <div class="nc-preview-header">
          <i class="bi bi-eye"></i> Email Preview
          <span class="nc-preview-hint">Live preview</span>
        </div>
        <div class="nc-preview-body" id="ncPreviewBody">
        </div>
      </aside>
    </div>
  <?php endif; ?>
</section>

<script>
(function() {
    var dateFilter = document.getElementById('nc-sh-date-filter');
    var customDates = document.getElementById('nc-sh-custom-dates');
    if (dateFilter && customDates) {
        dateFilter.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDates.style.display = 'flex';
            } else {
                customDates.style.display = 'none';
            }
        });
    }

    var cards = document.querySelectorAll('.nc-history-card');
    var previewBody = document.getElementById('ncPreviewBody');
    if (!previewBody) return;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showPreview(card) {
        var name = card.getAttribute('data-name') || '';
        var email = card.getAttribute('data-email') || '';
        var title = card.getAttribute('data-title') || '';
        var type = card.getAttribute('data-type') || '';
        var typeBadge = card.getAttribute('data-type-badge') || '';
        var date = card.getAttribute('data-date') || '';
        var time = card.getAttribute('data-time') || '';
        var status = card.getAttribute('data-status') || '';
        var message = card.getAttribute('data-message') || '';

        var recipientHtml = '<div>' + escapeHtml(name || 'system') + '</div>';
        if (email) {
            recipientHtml += '<div class="nc-sh-detail-email">' + escapeHtml(email) + '</div>';
        }
        var statusBadge = status === 'Read'
            ? 'bg-secondary-subtle text-secondary'
            : 'bg-primary';

        var html = '<div class="nc-preview-email">';
        html += '<div class="nc-preview-header-block">';
        html += '<div class="nc-preview-institution">';
        html += '<div class="nc-preview-institution-name">Bestlink College of the Philippines</div>';
        html += '<div class="nc-preview-campus">Human Resources Department</div>';
        html += '</div></div>';
        html += '<div class="nc-preview-info-block">';
        html += '<div class="nc-preview-info-row"><span class="nc-preview-info-label">To</span><span class="nc-preview-info-value">' + recipientHtml + '</span></div>';
        html += '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Subject</span><span class="nc-preview-info-value">' + escapeHtml(title) + '</span></div>';
        html += '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Type</span><span class="nc-preview-info-value"><span class="badge ' + escapeHtml(typeBadge) + '">' + escapeHtml(type) + '</span></span></div>';
        html += '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Sent</span><span class="nc-preview-info-value">' + escapeHtml(date) + '<br><span class="nc-sh-detail-time">' + escapeHtml(time) + '</span></span></div>';
        html += '<div class="nc-preview-info-row"><span class="nc-preview-info-label">Status</span><span class="nc-preview-info-value"><span class="badge ' + statusBadge + '">' + escapeHtml(status) + '</span></span></div>';
        html += '</div>';
        html += '<div class="nc-preview-section-label"><i class="bi bi-chat-left-text"></i> Message</div>';
        html += '<div class="nc-preview-body-content">';
        html += '<p>' + escapeHtml(message).replace(/(On\s+\d{2}\/\d{2}\/\d{4},\s+\d{2}:\d{2}:\d{2}),\s*[\s\S]*?wrote:/g, '$1').replace(/\n/g, '<br>') + '</p>';
        html += '</div>';
        html += '</div>';

        previewBody.innerHTML = html;

        cards.forEach(function(c) { c.classList.remove('selected'); });
        card.classList.add('selected');
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            showPreview(card);
        });
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                showPreview(card);
            }
        });
    });
})();
</script>
</div>
