<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/certificate-expiry-checker.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$notifications = [];
$announcements = [];
$unreadCount = 0;

try {
    $pdo = (new Database())->getConnection();

    // Generate certificate expiry notifications
    checkCertificateExpiryNotifications($pdo, $learnerId);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_notification WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $learnerId]);
    $unreadCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, type, title, message, reference_type, reference_id, is_read, created_at FROM ld_notification WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([':uid' => $learnerId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, title, message, audience, posted_by, status, created_at, expires_at FROM ld_announcement WHERE status = 'active' AND (audience = 'learner' OR audience = 'all') AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // defaults
}

function timeAgo($datetime) {
    $now = time();
    $ts = strtotime($datetime);
    $diff = $now - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function notifIcon($type) {
    $icons = [
        'invitation' => 'fa-user-plus',
        'certificate' => 'fa-certificate',
        'certificate_expiry' => 'fa-exclamation-triangle',
        'announcement' => 'fa-bullhorn',
        'enrollment' => 'fa-graduation-cap',
        'grade' => 'fa-chart-line',
        'reminder' => 'fa-clock',
        'message' => 'fa-envelope',
        'update' => 'fa-sync',
    ];
    return $icons[$type] ?? 'fa-bell';
}

function notifColor($type) {
    $colors = [
        'invitation' => '#6f42c1',
        'certificate' => '#ffc107',
        'certificate_expiry' => '#dc3545',
        'announcement' => '#17a2b8',
        'enrollment' => '#28a745',
        'grade' => '#007bff',
        'reminder' => '#fd7e14',
        'message' => '#6610f2',
    ];
    return $colors[$type] ?? 'var(--primary)';
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="notif-search" placeholder="Search notifications..." aria-label="Search notifications" />
        </div>
        <div class="toolbar-actions">
            <button type="button" id="mark-all-read-btn" style="padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; display:inline-flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
            
        </div>
    </div>

    <!-- Unread badge -->
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem;">
        <h2 style="margin:0;">Notifications</h2>
        <?php if ($unreadCount > 0): ?>
            <span style="background:#dc3545; color:#fff; padding:0.25rem 0.75rem; border-radius:20px; font-size:0.85rem; font-weight:600;"><?= $unreadCount ?> unread</span>
        <?php endif; ?>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-all-notifs">All (<?= count($notifications) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-unread">Unread (<?= $unreadCount ?>)</button>
            <?php $expiryCount = count(array_filter($notifications, fn($n) => $n['type'] === 'certificate_expiry')); ?>
            <button type="button" class="tab-item" data-tab="tab-expiry">Certificate Expiry (<?= $expiryCount ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-announcements">Announcements (<?= count($announcements) ?>)</button>
        </div>

        <!-- All Notifications -->
        <div class="tab-content active" data-tab="tab-all-notifs">
            <?php if (empty($notifications)): ?>
                <div class="mode-card">
                    <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <i class="fas fa-bell-slash" style="font-size:3rem; color:#ccc; margin-bottom:1rem; display:block;"></i>
                        <h3>No notifications yet</h3>
                        <p style="color:#999;">You'll see notifications about enrollments, grades, and announcements here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="mode-card">
                    <div id="notification-list" style="display:grid; gap:0.5rem;">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notif-row" data-notif-id="<?= $notif['id'] ?>" data-read="<?= $notif['is_read'] ? 'true' : 'false' ?>" style="display:flex; align-items:flex-start; gap:1rem; padding:1rem 1.25rem; background:<?= $notif['is_read'] ? '#fff' : 'rgba(32,0,130,0.03)' ?>; border-radius:10px; border-left:3px solid <?= notifColor($notif['type']) ?>; cursor:pointer; transition: background 0.2s;">
                                <div style="width:36px; height:36px; border-radius:50%; background:<?= notifColor($notif['type']) ?>; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas <?= notifIcon($notif['type']) ?>" style="font-size:0.85rem;"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                                        <strong style="<?= $notif['is_read'] ? '' : 'font-weight:700;' ?>"><?= htmlspecialchars($notif['title']) ?></strong>
                                        <span style="color:#999; font-size:0.8rem; white-space:nowrap;"><?= timeAgo($notif['created_at']) ?></span>
                                    </div>
                                    <p style="margin:0.3rem 0 0 0; color:#555; font-size:0.9rem; line-height:1.5;"><?= htmlspecialchars($notif['message']) ?></p>
                                    <?php if (!$notif['is_read']): ?>
                                        <span style="display:inline-block; margin-top:0.4rem; width:8px; height:8px; background:#dc3545; border-radius:50;"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pagination-row" id="notif-all-pagination">
                    <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                    <span class="page-indicator">Page 1 of 1</span>
                    <button type="button" class="page-btn" data-action="next">Next</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Unread Only -->
        <div class="tab-content" data-tab="tab-unread">
            <div class="mode-card">
                <div id="unread-list" style="display:grid; gap:0.5rem;">
                    <?php $unreadItems = array_filter($notifications, fn($n) => !$n['is_read']); ?>
                    <?php if (empty($unreadItems)): ?>
                        <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                            <i class="fas fa-check-circle" style="font-size:3rem; color:#28a745; margin-bottom:1rem; display:block;"></i>
                            <h3>All caught up!</h3>
                            <p style="color:#999;">You have no unread notifications.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unreadItems as $notif): ?>
                            <div class="notif-row" data-notif-id="<?= $notif['id'] ?>" style="display:flex; align-items:flex-start; gap:1rem; padding:1rem 1.25rem; background:rgba(32,0,130,0.03); border-radius:10px; border-left:3px solid <?= notifColor($notif['type']) ?>; cursor:pointer;">
                                <div style="width:36px; height:36px; border-radius:50%; background:<?= notifColor($notif['type']) ?>; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas <?= notifIcon($notif['type']) ?>" style="font-size:0.85rem;"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                                        <strong style="font-weight:700;"><?= htmlspecialchars($notif['title']) ?></strong>
                                        <span style="color:#999; font-size:0.8rem; white-space:nowrap;"><?= timeAgo($notif['created_at']) ?></span>
                                    </div>
                                    <p style="margin:0.3rem 0 0 0; color:#555; font-size:0.9rem; line-height:1.5;"><?= htmlspecialchars($notif['message']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pagination-row" id="notif-unread-pagination" style="display:none;">
                <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                <span class="page-indicator">Page 1 of 1</span>
                <button type="button" class="page-btn" data-action="next">Next</button>
            </div>
        </div>

        <!-- Certificate Expiry -->
        <div class="tab-content" data-tab="tab-expiry">
            <?php $expiryNotifs = array_filter($notifications, fn($n) => $n['type'] === 'certificate_expiry'); ?>
            <?php if (empty($expiryNotifs)): ?>
                <div class="mode-card">
                    <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <i class="fas fa-certificate" style="font-size:3rem; color:#28a745; margin-bottom:1rem; display:block;"></i>
                        <h3>No expiring certificates</h3>
                        <p style="color:#999;">Your certificates are all valid and not expiring soon.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="mode-card">
                    <div style="display:grid; gap:0.5rem;">
                        <?php foreach ($expiryNotifs as $notif): ?>
                            <div class="notif-row" data-notif-id="<?= $notif['id'] ?>" data-read="<?= $notif['is_read'] ? 'true' : 'false' ?>" style="display:flex; align-items:flex-start; gap:1rem; padding:1rem 1.25rem; background:<?= $notif['is_read'] ? '#fff' : 'rgba(32,0,130,0.03)' ?>; border-radius:10px; border-left:3px solid #dc3545; cursor:pointer; transition: background 0.2s;">
                                <div style="width:36px; height:36px; border-radius:50%; background:#dc3545; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-exclamation-triangle" style="font-size:0.85rem;"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                                        <strong style="<?= $notif['is_read'] ? '' : 'font-weight:700;' ?>"><?= htmlspecialchars($notif['title']) ?></strong>
                                        <span style="color:#999; font-size:0.8rem; white-space:nowrap;"><?= timeAgo($notif['created_at']) ?></span>
                                    </div>
                                    <p style="margin:0.3rem 0 0 0; color:#555; font-size:0.9rem; line-height:1.5;"><?= htmlspecialchars($notif['message']) ?></p>
                                    <?php if ($notif['reference_id']): ?>
                                        <?php
                                        $certLinkStmt = $pdo->prepare("SELECT verification_code, course_id FROM ld_certificate WHERE id = :cid AND status = 'active' LIMIT 1");
                                        $certLinkStmt->execute([':cid' => $notif['reference_id']]);
                                        $certLinkRow = $certLinkStmt->fetch(PDO::FETCH_ASSOC);
                                        $certLinkCode = $certLinkRow ? $certLinkRow['verification_code'] : null;
                                        $certCourseId = $certLinkRow ? (int)$certLinkRow['course_id'] : 0;
                                        ?>
                                        <?php if ($certLinkCode): ?>
                                        <div style="display:flex; gap:0.4rem; margin-top:0.5rem; flex-wrap:wrap;">
                                            <a href="?page=public/verify-certificate&code=<?= htmlspecialchars($certLinkCode) ?>" target="_blank" style="padding:0.35rem 0.7rem; background:rgba(220,53,69,0.08); color:#dc3545; border-radius:6px; font-size:0.78rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem;"><i class="fas fa-external-link-alt"></i>View Certificate</a>
                                            <?php if ($certCourseId > 0): ?>
                                            <button type="button" class="cert-renew-btn" data-course-id="<?= $certCourseId ?>" onclick="renewCourse(this, <?= $certCourseId ?>)" style="padding:0.35rem 0.7rem; background:var(--primary); color:#fff; border:none; border-radius:6px; font-size:0.78rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.25rem; transition:all 0.2s;"><i class="fas fa-sync-alt"></i>Renew Course</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!$notif['is_read']): ?>
                                        <span style="display:inline-block; margin-top:0.4rem; width:8px; height:8px; background:#dc3545; border-radius:50%;"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pagination-row" id="notif-expiry-pagination" style="display:none;">
                    <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                    <span class="page-indicator">Page 1 of 1</span>
                    <button type="button" class="page-btn" data-action="next">Next</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Announcements -->
        <div class="tab-content" data-tab="tab-announcements">
            <?php if (empty($announcements)): ?>
                <div class="mode-card">
                    <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <i class="fas fa-bullhorn" style="font-size:3rem; color:#ccc; margin-bottom:1rem; display:block;"></i>
                        <h3>No announcements</h3>
                        <p style="color:#999;">There are no active announcements at this time.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="mode-card" style="margin-bottom:1rem; border-left:4px solid #17a2b8;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                            <div style="flex:1;">
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                    <i class="fas fa-bullhorn" style="color:#17a2b8;"></i>
                                    <strong style="font-size:1.1rem;"><?= htmlspecialchars($ann['title']) ?></strong>
                                </div>
                                <p style="color:#555; line-height:1.6; margin:0;"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                            </div>
                            <span style="color:#999; font-size:0.8rem; white-space:nowrap;"><?= timeAgo($ann['created_at']) ?></span>
                        </div>
                        <?php if (!empty($ann['expires_at'])): ?>
                            <div style="margin-top:0.75rem; font-size:0.8rem; color:#999;">
                                <i class="fas fa-clock"></i> Expires <?= date('M j, Y', strtotime($ann['expires_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    // Mark individual notification as read on click
    document.querySelectorAll('.notif-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var notifId = this.dataset.notifId;
            var isRead = this.dataset.read;
            if (isRead === 'false') {
                fetch('pages/learner/ajax/mark-notification-read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'notification_id=' + notifId
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success) {
                        row.dataset.read = 'true';
                        row.style.background = '#fff';
                        var dot = row.querySelector('span[style*="border-radius:50"]');
                        if (dot) dot.remove();
                    }
                });
            }
        });
    });

    // Mark all as read
    document.getElementById('mark-all-read-btn').addEventListener('click', function() {
        fetch('pages/learner/ajax/mark-all-notification-read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'all=1'
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                document.querySelectorAll('.notif-row').forEach(function(row) {
                    row.dataset.read = 'true';
                    row.style.background = '#fff';
                    var dot = row.querySelector('span[style*="border-radius:50"]');
                    if (dot) dot.remove();
                });
                // Update badges
                var badge = document.querySelector('span[style*="#dc3545"]');
                if (badge) badge.style.display = 'none';
            }
        });
    });

    // Search filter
    document.getElementById('notif-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.notif-row').forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
        });
    });

    // Certificate renewal
    window.renewCourse = function(btn, courseId) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
        fetch('pages/learner/ajax/enroll-course.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: courseId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Enrolled';
                btn.style.background = '#10b981';
                if (typeof window.showToast === 'function') {
                    window.showToast('Re-enrolled in course successfully', 'success');
                }
            } else {
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Course';
                btn.disabled = false;
                if (typeof window.showToast === 'function') {
                    window.showToast(data.error || data.message || 'Failed to re-enroll', 'error');
                }
            }
        }).catch(function() {
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Course';
            btn.disabled = false;
            if (typeof window.showToast === 'function') {
                window.showToast('Network error', 'error');
            }
        });
    };
})();
</script>
