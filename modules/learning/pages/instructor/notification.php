<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/announcement.php';
require_once __DIR__ . '/../../classes/message.php';

$employeeClass = new Employee();
$employeeId = (int) ($employeeClass->getEmployeeId() ?? 0);

$announcements = [];
$messages = [];
$unreadCount = 0;

try {
    $pdo = (new Database())->getConnection();

    $announcements = $pdo->query(
        "SELECT id, title, message, audience, status, created_at, expires_at
         FROM ld_announcement
         WHERE status = 'active'
         ORDER BY created_at DESC
         LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($employeeId > 0) {
        $messages = $pdo->prepare(
            "SELECT id, sender_id, subject, body, is_read, created_at
             FROM ld_message
             WHERE recipient_id = :recipient_id
             ORDER BY created_at DESC
             LIMIT 8"
        );
        $messages->execute([':recipient_id' => $employeeId]);
        $messages = $messages->fetchAll(PDO::FETCH_ASSOC);

        $unreadCount = (new Message($pdo))->getUnreadCount($employeeId);
    }
} catch (Throwable $e) {
    $announcements = [];
    $messages = [];
    $unreadCount = 0;
}

function renderNotificationCards($items, $emptyText, $type = 'announcement') {
    if (empty($items)) {
        echo '<div class="mode-card"><div class="content-card-body"><h3>No notifications yet</h3><p>' . htmlspecialchars($emptyText) . '</p></div></div>';
        return;
    }

    echo '<div class="cards-grid" data-page-size="12">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $title = trim((string) ($item['title'] ?? ($item['subject'] ?? 'Untitled')));
        $message = trim((string) ($item['message'] ?? ($item['body'] ?? 'No description available yet.')));
        $status = trim((string) ($item['status'] ?? ($item['is_read'] ?? '')));
        $meta = $type === 'announcement'
            ? ucfirst((string) ($item['audience'] ?? 'all'))
            : ($item['is_read'] ? 'Read' : 'Unread');
        $date = !empty($item['created_at']) ? date('M d, Y', strtotime((string) $item['created_at'])) : 'Recently';

        echo '<article class="content-card-item" data-entity-id="' . $id . '" data-entity-type="' . htmlspecialchars($type) . '" style="cursor:pointer;">';
        echo '<div class="content-card-thumb">' . htmlspecialchars(strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 2) ?: 'NT')) . '</div>';
        echo '<div class="content-card-body">';
        echo '<div class="content-card-meta"><span class="pill">' . htmlspecialchars($type === 'announcement' ? 'Announcement' : 'Message') . '</span><span>' . htmlspecialchars($meta) . '</span></div>';
        echo '<h3>' . htmlspecialchars($title) . '</h3>';
        echo '<p>' . htmlspecialchars(mb_substr(strip_tags($message), 0, 120)) . '</p>';
        echo '<div class="content-card-footer"><span>' . htmlspecialchars($date) . '</span></div>';
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search notifications..." aria-label="Search notifications" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-filter" aria-label="Filter notifications">
                <option value="all">All</option>
                <option value="unread">Unread</option>
                <option value="announcement">Announcements</option>
                <option value="message">Messages</option>
            </select>
            <a href="?page=instructor/certificate" class="toolbar-add-btn" title="Open certificates">Certificates</a>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-overview" tabindex="0"><i class="fas fa-eye" style="margin-right:0.45rem;"></i>Overview</button>
            <button type="button" class="tab-item" data-tab="tab-announcements" tabindex="0"><i class="fas fa-bullhorn" style="margin-right:0.45rem;"></i>Announcements</button>
            <button type="button" class="tab-item" data-tab="tab-messages" tabindex="0"><i class="fas fa-envelope" style="margin-right:0.45rem;"></i>Messages</button>
        </div>

        <div class="tab-content active" data-tab="tab-overview">
            <div class="analytics-cards">
                <div class="analytics-card">
                    <h2>Total Announcements</h2>
                    <p class="analytics-value"><?= count($announcements) ?></p>
                </div>
                <div class="analytics-card">
                    <h2>Inbox Messages</h2>
                    <p class="analytics-value"><?= count($messages) ?></p>
                </div>
                <div class="analytics-card">
                    <h2>Unread</h2>
                    <p class="analytics-value"><?= $unreadCount ?></p>
                </div>
                <div class="analytics-card">
                    <h2>Audience</h2>
                    <p class="analytics-value"><?= !empty($announcements) ? strtoupper((string) $announcements[0]['audience']) : 'ALL' ?></p>
                </div>
            </div>

            <div class="mode-card" style="margin-top:1.5rem;">
                <h2>Latest updates</h2>
                <p>Keep track of course announcements, learner messages, and system updates in one place.</p>
                <?php renderNotificationCards(array_slice($announcements, 0, 3), 'There are no announcements to display for your audience yet.', 'announcement'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-announcements">
            <div class="mode-card">
                <h2>Announcements</h2>
                <p>Shared updates, policy reminders, and platform notifications sent to your audience.</p>
                <?php renderNotificationCards($announcements, 'There are no active announcements yet.', 'announcement'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-messages">
            <div class="mode-card">
                <h2>Inbox</h2>
                <p>Review the latest messages and follow-ups from learners, admins, and system contacts.</p>
                <?php renderNotificationCards($messages, 'Your inbox is clear. No messages have been received yet.', 'message'); ?>
            </div>
        </div>
    </div>
</div>
<script>\r\n(function() {\r\n    var PAGE = 9;\r\n    var pgState = { announcements: 1, messages: 1 };\r\n\r\n    function paginateCards(tabKey, pgId) {\r\n        var tc = document.querySelector(".tab-content[data-tab=" + tabKey + "]");\r\n        if (!tc) return;\r\n        var grid = tc.querySelector(".cards-grid");\r\n        if (!grid) return;\r\n        var cards = Array.from(grid.querySelectorAll(".content-card-item"));\r\n        var q = (document.querySelector(".toolbar-search input") || {}).value || "";\r\n        q = q.toLowerCase().trim();\r\n        var vis = cards.filter(function(c) { return q === "" || c.textContent.toLowerCase().indexOf(q) > -1; });\r\n        var tot = Math.max(1, Math.ceil(vis.length / PAGE));\r\n        pgState[tabKey] = Math.min(pgState[tabKey], tot);\r\n        var st = (pgState[tabKey] - 1) * PAGE;\r\n        vis.forEach(function(c, i) { c.style.display = (i >= st && i < st + PAGE) ? "" : "none"; });\r\n        var pg = document.getElementById(pgId);\r\n        if (pg) {\r\n            pg.querySelector(".page-indicator").textContent = "Page " + pgState[tabKey] + " of " + tot;\r\n            pg.querySelector("[data-action=prev]").disabled = pgState[tabKey] <= 1;\r\n            pg.querySelector("[data-action=next]").disabled = pgState[tabKey] >= tot;\r\n            pg.style.display = tot <= 1 ? "none" : "";\r\n        }\r\n    }\r\n\r\n    [{k:"tab-announcements",p:"announcements-pagination"},{k:"tab-messages",p:"messages-pagination"}].forEach(function(t) {\r\n        var pg = document.getElementById(t.p);\r\n        if (pg) {\r\n            pg.addEventListener("click", function(e) {\r\n                var btn = e.target.closest("[data-action]");\r\n                if (!btn || btn.disabled) return;\r\n                if (btn.dataset.action === "prev" && pgState[t.k] > 1) pgState[t.k]--;\r\n                if (btn.dataset.action === "next") pgState[t.k]++;\r\n                paginateCards(t.k, t.p);\r\n            });\r\n        }\r\n    });\r\n\r\n    var searchInput = document.querySelector(".toolbar-search input");\r\n    if (searchInput) {\r\n        searchInput.addEventListener("input", function() {\r\n            paginateCards("tab-announcements", "announcements-pagination");\r\n            paginateCards("tab-messages", "messages-pagination");\r\n        });\r\n    }\r\n\r\n    paginateCards("tab-announcements", "announcements-pagination");\r\n})();\r\n</script>