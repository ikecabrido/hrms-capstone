<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$adminId = (int) ($employeeClass->getEmployeeId() ?? 0);

$notifications = [];
$announcements = [];
$unreadCount = 0;

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_notification WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $adminId]);
    $unreadCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, type, title, message, reference_type, reference_id, is_read, created_at FROM ld_notification WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([':uid' => $adminId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, title, message, audience, posted_by, status, created_at, expires_at FROM ld_announcement WHERE status = 'active' AND (audience = 'admin' OR audience = 'all') AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

function adminNotifTimeAgo($datetime) {
    $now = time(); $ts = strtotime($datetime); $diff = $now - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function adminNotifIcon($type) {
    $icons = ['invitation'=>'fa-user-plus','certificate'=>'fa-certificate','announcement'=>'fa-bullhorn','enrollment'=>'fa-graduation-cap','grade'=>'fa-chart-line','reminder'=>'fa-clock','message'=>'fa-envelope','report'=>'fa-flag'];
    return $icons[$type] ?? 'fa-bell';
}

function adminNotifColor($type) {
    $colors = ['invitation'=>'#6f42c1','certificate'=>'#ffc107','announcement'=>'#17a2b8','enrollment'=>'#28a745','grade'=>'#007bff','reminder'=>'#fd7e14','message'=>'#6610f2','report'=>'#dc3545'];
    return $colors[$type] ?? 'var(--primary)';
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="admin-notif-search" placeholder="Search notifications..." />
        </div>
        <div class="toolbar-actions">
            <button type="button" id="admin-mark-all-read" style="padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:500;"><i class="fas fa-check-double"></i> Mark All Read</button>
            
        </div>
    </div>

    <div style="margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;">
        <h2 style="margin:0;">Notifications</h2>
        <?php if ($unreadCount > 0): ?>
            <span style="background:#dc3545;color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:600;"><?= $unreadCount ?> unread</span>
        <?php endif; ?>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-admin-all">All (<?= count($notifications) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-admin-unread">Unread (<?= $unreadCount ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-admin-announcements">Announcements (<?= count($announcements) ?>)</button>
        </div>

        <div class="tab-content active" data-tab="tab-admin-all">
            <?php if (empty($notifications)): ?>
                <div class="mode-card"><div style="padding:3rem;text-align:center;background:#f9f9f9;border-radius:12px;"><i class="fas fa-bell-slash" style="font-size:3rem;color:#ccc;margin-bottom:1rem;display:block;"></i><h3>No notifications</h3></div></div>
            <?php else: ?>
                <div class="mode-card">
                    <div style="display:grid;gap:0.5rem;">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="admin-notif-row" data-nid="<?= $notif['id'] ?>" data-read="<?= $notif['is_read'] ? 'true' : 'false' ?>" style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 1.25rem;background:<?= $notif['is_read'] ? '#fff' : 'rgba(32,0,130,0.03)' ?>;border-radius:10px;border-left:3px solid <?= adminNotifColor($notif['type']) ?>;cursor:pointer;">
                                <div style="width:36px;height:36px;border-radius:50%;background:<?= adminNotifColor($notif['type']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas <?= adminNotifIcon($notif['type']) ?>" style="font-size:0.85rem;"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                                        <strong style="<?= $notif['is_read'] ? '' : 'font-weight:700;' ?>"><?= htmlspecialchars($notif['title']) ?></strong>
                                        <span style="color:#999;font-size:0.8rem;white-space:nowrap;"><?= adminNotifTimeAgo($notif['created_at']) ?></span>
                                    </div>
                                    <p style="margin:0.3rem 0 0 0;color:#555;font-size:0.9rem;"><?= htmlspecialchars($notif['message']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" data-tab="tab-admin-unread">
            <div class="mode-card">
                <?php $unreadItems = array_filter($notifications, fn($n) => !$n['is_read']); ?>
                <?php if (empty($unreadItems)): ?>
                    <div style="padding:3rem;text-align:center;background:#f9f9f9;border-radius:12px;"><i class="fas fa-check-circle" style="font-size:3rem;color:#28a745;margin-bottom:1rem;display:block;"></i><h3>All caught up!</h3></div>
                <?php else: ?>
                    <div style="display:grid;gap:0.5rem;">
                        <?php foreach ($unreadItems as $notif): ?>
                            <div class="admin-notif-row" data-nid="<?= $notif['id'] ?>" style="display:flex;align-items:flex-start;gap:1rem;padding:1rem;background:rgba(32,0,130,0.03);border-radius:10px;border-left:3px solid <?= adminNotifColor($notif['type']) ?>;cursor:pointer;">
                                <div style="width:36px;height:36px;border-radius:50%;background:<?= adminNotifColor($notif['type']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas <?= adminNotifIcon($notif['type']) ?>" style="font-size:0.85rem;"></i></div>
                                <div style="flex:1;"><strong style="font-weight:700;"><?= htmlspecialchars($notif['title']) ?></strong><p style="margin:0.3rem 0 0;color:#555;font-size:0.9rem;"><?= htmlspecialchars($notif['message']) ?></p></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-admin-announcements">
            <?php if (empty($announcements)): ?>
                <div class="mode-card"><div style="padding:3rem;text-align:center;background:#f9f9f9;border-radius:12px;"><i class="fas fa-bullhorn" style="font-size:3rem;color:#ccc;margin-bottom:1rem;display:block;"></i><h3>No announcements</h3></div></div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="mode-card" style="margin-bottom:1rem;border-left:4px solid #17a2b8;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                            <div style="flex:1;"><div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;"><i class="fas fa-bullhorn" style="color:#17a2b8;"></i><strong style="font-size:1.1rem;"><?= htmlspecialchars($ann['title']) ?></strong></div><p style="color:#555;line-height:1.6;margin:0;"><?= nl2br(htmlspecialchars($ann['message'])) ?></p></div>
                            <span style="color:#999;font-size:0.8rem;white-space:nowrap;"><?= adminNotifTimeAgo($ann['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    document.querySelectorAll('.admin-notif-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var nid = this.dataset.nid;
            if (this.dataset.read === 'false') {
                fetch('pages/learner/ajax/mark-notification-read.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'notification_id=' + nid })
                    .then(function(r){return r.json();}).then(function(d){ if(d.success){ row.dataset.read='true'; row.style.background='#fff'; } });
            }
        });
    });
    document.getElementById('admin-mark-all-read').addEventListener('click', function() {
        fetch('pages/learner/ajax/mark-all-notification-read.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'all=1' })
            .then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); });
    });
    document.getElementById('admin-notif-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.admin-notif-row').forEach(function(r) { r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none'; });
    });
})();
</script>
