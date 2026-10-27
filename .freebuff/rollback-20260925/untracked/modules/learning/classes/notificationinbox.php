<?php
/**
 * NotificationInbox — the one notification screen, rendered for every learning role.
 *
 * Three pages used to exist (admin/notification, instructor/notification, learner/notification):
 * ~650 lines between them, each with its own copy of the same query, its own icon map, its own
 * "3m ago" formatter and its own read-state fetch — and none of them agreed with the bell menu
 * the header actually shows. The instructor one did not even read ld_notification: it showed
 * announcements and messages while calling itself "Notification".
 *
 * There is one reader of that table in the application — includes/Notification.php, the class
 * behind the bell — so the page is built on it too: same rows, same icons, same relative times,
 * same destinations (a workforce alert opens its record from here exactly as it does from the
 * bell). The role-specific extras stay with their roles: the caller composes them as extra tabs
 * (the learner's certificate-expiry list) and passes in announcements and messages, which have
 * their own classes and their own audiences.
 *
 * Read state is written through one shared endpoint, pages/shared/ajax/mark-notification.php,
 * because "mark read" is the same one-line UPDATE for every role.
 */
require_once dirname(__DIR__, 3) . '/includes/Notification.php';

final class NotificationInbox
{
    /** Where read-state changes are posted; one endpoint for all three roles. */
    public const ENDPOINT = 'pages/shared/ajax/mark-notification.php';

    /** Label and icon for each tab this view builds itself. */
    private const TABS = [
        'notifications' => ['label' => 'Notifications', 'icon' => 'fa-bell'],
        'unread'        => ['label' => 'Unread',        'icon' => 'fa-circle-dot'],
        'messages'      => ['label' => 'Messages',      'icon' => 'fa-envelope'],
        'announcements' => ['label' => 'Announcements', 'icon' => 'fa-bullhorn'],
    ];

    /**
     * Render the whole page body.
     *
     * $spec:
     *   title, subtitle      heading text
     *   employeeId           whose inbox this is (session employee id)
     *   role                 learning role, so links land on the role's own page
     *   notifications        rows from Notification::getRecent()
     *   unread               Notification::getUnreadCount()
     *   announcements        rows: id, title, message, audience, created_at
     *   messages             rows: id, sender_name, subject, body, is_read, created_at — or []
     *   messagesUnread       int, shown on the stat card and the tab
     *   extraTabs            tab key => ['label' => …, 'icon' => …, 'html' => …], for a role's
     *                        own section (the learner's certificate-expiry list)
     *   emptyNotifications   what to say when there is nothing, per role
     *
     * @param array<string, mixed> $spec
     */
    public static function page(array $spec): string
    {
        $notifications = $spec['notifications'] ?? [];
        $unread = (int) ($spec['unread'] ?? 0);
        $announcements = $spec['announcements'] ?? [];
        $messages = $spec['messages'] ?? [];
        // Whether these tabs exist is the caller's decision — a role that has no messages
        // omits the key entirely and does not get an empty tab in its inbox.
        $hasMessages = array_key_exists('messages', $spec);
        $hasAnnouncements = array_key_exists('announcements', $spec);
        $messagesUnread = (int) ($spec['messagesUnread'] ?? 0);
        $extraTabs = $spec['extraTabs'] ?? [];
        $role = (string) ($spec['role'] ?? '');

        $tabs = ['notifications' => count($notifications), 'unread' => $unread];
        foreach ($extraTabs as $key => $tab) {
            $tabs[$key] = (int) ($tab['count'] ?? 0);
        }
        if ($hasMessages) {
            $tabs['messages'] = count($messages);
        }
        if ($hasAnnouncements) {
            $tabs['announcements'] = count($announcements);
        }

        $first = array_key_first($tabs);

        $html = '<div class="module-content">';
        $html .= self::header($spec, $unread, $messagesUnread, $announcements, $notifications);

        $html .= '<div class="tab-container"><div class="tab-list">';
        foreach ($tabs as $key => $count) {
            $label = $extraTabs[$key]['label'] ?? self::TABS[$key]['label'] ?? ucfirst((string) $key);
            $icon = $extraTabs[$key]['icon'] ?? self::TABS[$key]['icon'] ?? 'fa-table';
            $isCounted = in_array($key, ['notifications', 'unread', 'messages'], true);
            $html .= '<button type="button" class="tab-item' . ($key === $first ? ' active' : '') . '" data-tab="tab-' . self::esc($key) . '">'
                . '<i class="fas ' . self::esc($icon) . '" style="margin-right:0.4rem;"></i>' . self::esc($label)
                . ($isCounted ? ' (' . $count . ')' : '') . '</button>';
        }
        $html .= '</div>';

        $html .= '<div class="tab-content' . ($first === 'notifications' ? ' active' : '') . '" data-tab="tab-notifications">'
            . self::notificationList($notifications, $notifications, $role, $spec)
            . '</div>';

        $unreadRows = array_values(array_filter($notifications, static fn(array $row): bool => (int) ($row['is_read'] ?? 0) === 0));
        $html .= '<div class="tab-content' . ($first === 'unread' ? ' active' : '') . '" data-tab="tab-unread">'
            . self::notificationList($unreadRows, $notifications, $role, $spec)
            . '</div>';

        foreach ($extraTabs as $key => $tab) {
            $html .= '<div class="tab-content' . ($key === $first ? ' active' : '') . '" data-tab="tab-' . self::esc($key) . '">'
                . ($tab['html'] ?? '') . '</div>';
        }

        if ($hasMessages) {
            $html .= '<div class="tab-content" data-tab="tab-messages">' . self::messageList($messages) . '</div>';
        }
        if ($hasAnnouncements) {
            $html .= '<div class="tab-content" data-tab="tab-announcements">' . self::announcementList($announcements) . '</div>';
        }

        $html .= '</div></div>'; // .tab-container, .module-content

        return $html . self::scripts();
    }

    /** Heading, unread badge and the stat cards the three pages each drew their own version of. */
    private static function header(array $spec, int $unread, int $messagesUnread, array $announcements, array $notifications): string
    {
        $html = '<div class="module-header">'
            . '<h1 class="module-header-title">' . self::esc($spec['title'] ?? 'Notifications') . '</h1>'
            . (!empty($spec['subtitle']) ? '<p class="module-header-subtitle">' . self::esc($spec['subtitle']) . '</p>' : '')
            . '</div>';

        $html .= '<div class="toolbar"><div class="toolbar-search">'
            . '<input type="search" id="inbox-search" placeholder="Search notifications, messages, announcements..." aria-label="Search the inbox" />'
            . '</div><div class="toolbar-actions">'
            . '<button type="button" id="inbox-mark-all" style="padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;">'
            . '<i class="fas fa-check-double" style="margin-right:0.35rem;"></i>Mark all read</button>'
            . '</div></div>';

        $cards = [
            ['Unread', $unread, $unread > 0 ? '#dc3545' : '#059669'],
            ['All notifications', count($notifications), 'var(--primary)'],
        ];
        if (array_key_exists('messages', $spec)) {
            $cards[] = ['Unread messages', $messagesUnread, $messagesUnread > 0 ? '#dc3545' : '#059669'];
        }
        $cards[] = ['Announcements', count($announcements), 'var(--primary)'];

        $html .= '<div class="analytics-cards" style="margin-bottom:1.25rem;">';
        foreach ($cards as [$label, $value, $colour]) {
            $html .= '<div class="analytics-card"><h2>' . self::esc($label) . '</h2>'
                . '<p class="analytics-value" style="color:' . $colour . ';">' . (int) $value . '</p></div>';
        }

        return $html . '</div>';
    }

    /**
     * One notification list. Rows are the bell's rows: same icon map, same relative time, and
     * the same destination — a workforce alert opens its record here too.
     *
     * @param array<int, array<string, mixed>> $rows     what to draw
     * @param array<int, array<string, mixed>> $allRows  the full set, for the "nothing here" copy
     */
    private static function notificationList(array $rows, array $allRows, string $role, array $spec): string
    {
        if ($rows === []) {
            $message = $allRows === []
                ? (string) ($spec['emptyNotifications'] ?? 'Nothing has been sent to you yet.')
                : 'Nothing unread — you are up to date.';

            return '<div class="mode-card"><div style="padding:3rem;text-align:center;background:#f9f9f9;border-radius:12px;">'
                . '<i class="fas fa-bell-slash" style="font-size:3rem;color:#ccc;margin-bottom:1rem;display:block;"></i>'
                . '<h3 style="margin:0 0 0.4rem;">Nothing here</h3>'
                . '<p style="margin:0;color:#999;">' . self::esc($message) . '</p></div></div>';
        }

        $html = '<div class="mode-card"><div style="display:grid;gap:0.5rem;">';
        foreach ($rows as $row) {
            $html .= self::notificationRow($row, $role);
        }

        return $html . '</div></div>';
    }

    private static function notificationRow(array $row, string $role): string
    {
        $id = (int) ($row['id'] ?? 0);
        $type = (string) ($row['type'] ?? '');
        $isRead = (int) ($row['is_read'] ?? 0) === 1;
        // The same resolution the bell uses: a row that names a record gets a link to it.
        $href = Notification::linkFor($row, $role);

        $body = '<div style="width:36px;height:36px;border-radius:50%;background:' . ($isRead ? 'rgba(32,0,130,0.08)' : 'var(--primary)')
            . ';color:' . ($isRead ? 'var(--muted)' : '#fff') . ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
            . '<i class="fas ' . self::esc(Notification::iconFor($type)) . '" style="font-size:0.85rem;"></i></div>'
            . '<div style="flex:1;min-width:0;">'
            . '<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">'
            . '<strong' . ($isRead ? '' : ' style="font-weight:800;"') . '>' . self::esc((string) ($row['title'] ?? '')) . '</strong>'
            . '<span style="color:#999;font-size:0.8rem;white-space:nowrap;">' . self::esc(Notification::timeAgo((string) ($row['created_at'] ?? ''))) . '</span>'
            . '</div>'
            . '<p style="margin:0.3rem 0 0;color:#555;font-size:0.9rem;line-height:1.5;">' . self::esc((string) ($row['message'] ?? '')) . '</p>';

        if ($href !== null) {
            $body .= '<a href="' . self::esc($href) . '" style="display:inline-flex;align-items:center;gap:0.3rem;margin-top:0.5rem;'
                . 'padding:0.35rem 0.7rem;background:rgba(32,0,130,0.08);color:var(--primary);border-radius:6px;'
                . 'font-size:0.78rem;font-weight:700;text-decoration:none;">Open <i class="fas fa-arrow-right-long" style="font-size:0.7rem;"></i></a>';
        }

        $body .= '</div>';

        return '<div class="inbox-row" data-notification-id="' . $id . '" data-read="' . ($isRead ? 'true' : 'false') . '"'
            . ' style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 1.25rem;border-radius:10px;'
            . 'background:' . ($isRead ? '#fff' : 'rgba(32,0,130,0.03)') . ';border-left:3px solid '
            . ($isRead ? 'rgba(32,0,130,0.12)' : 'var(--primary)') . ';cursor:pointer;">' . $body . '</div>';
    }

    private static function messageList(array $messages): string
    {
        if ($messages === []) {
            return '<div class="mode-card"><h2 style="margin-top:0;">Your inbox</h2>'
                . '<p style="color:var(--muted);">No messages have been sent to you yet. Messages are not notifications — '
                . 'the bell and the Notifications tab are where the system talks to you.</p></div>';
        }

        $html = '<div class="mode-card"><h2 style="margin-top:0;">Your inbox</h2>'
            . '<p style="color:var(--muted);font-size:0.85rem;">Messages sent to you by learners, admins and system contacts. '
            . 'This is the Inbox, not the notification list — announcements live under their own tab.</p>'
            . '<div style="display:grid;gap:0.5rem;margin-top:0.75rem;">';

        foreach ($messages as $message) {
            $html .= '<div style="padding:1rem 1.25rem;border-radius:10px;background:'
                . (!empty($message['is_read']) ? '#fff' : 'rgba(32,0,130,0.03)')
                . ';border-left:3px solid var(--primary);">'
                . '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">'
                . '<strong>' . self::esc((string) ($message['subject'] ?? 'Untitled')) . '</strong>'
                . '<span style="color:#999;font-size:0.8rem;white-space:nowrap;">'
                . self::esc(Notification::timeAgo((string) ($message['created_at'] ?? ''))) . '</span></div>'
                . '<div style="font-size:0.78rem;color:var(--muted);margin-top:0.2rem;">'
                . self::esc((string) ($message['sender_name'] ?? 'Unknown sender')) . '</div>'
                . '<p style="margin:0.4rem 0 0;color:#555;font-size:0.9rem;line-height:1.5;">'
                . self::esc((string) ($message['body'] ?? '')) . '</p></div>';
        }

        return $html . '</div></div>';
    }

    private static function announcementList(array $announcements): string
    {
        if ($announcements === []) {
            return '<div class="mode-card"><h2 style="margin-top:0;">Announcements</h2>'
                . '<p style="color:var(--muted);">Nothing has been posted for your audience yet.</p></div>';
        }

        $html = '<div class="mode-card"><h2 style="margin-top:0;">Announcements</h2>'
            . '<p style="color:var(--muted);font-size:0.85rem;">Notices posted for your audience. They stay here until they expire.</p>'
            . '<div style="display:grid;gap:0.75rem;margin-top:0.75rem;">';

        foreach ($announcements as $announcement) {
            $html .= '<div style="padding:1rem 1.25rem;background:rgba(23,162,184,0.05);border-left:4px solid #17a2b8;border-radius:10px;">'
                . '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">'
                . '<strong style="font-size:1rem;">' . self::esc((string) ($announcement['title'] ?? 'Untitled')) . '</strong>'
                . '<span style="color:#999;font-size:0.8rem;white-space:nowrap;">'
                . self::esc(Notification::timeAgo((string) ($announcement['created_at'] ?? ''))) . '</span></div>'
                . '<p style="color:#555;line-height:1.6;margin:0.4rem 0 0;">'
                . nl2br(self::esc((string) ($announcement['message'] ?? ''))) . '</p></div>';
        }

        return $html . '</div></div>';
    }

    /**
     * Row clicking marks read through the shared endpoint, "mark all" clears the badge, and the
     * search box filters whatever tab is open — the three behaviours each page used to implement.
     */
    private static function scripts(): string
    {
        $endpoint = self::ENDPOINT;

        return <<<HTML
<script>
(function () {
    var ENDPOINT = '{$endpoint}';

    function markRead(row) {
        if (!row || row.dataset.read === 'true') { return; }
        fetch(ENDPOINT, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'id=' + encodeURIComponent(row.dataset.notificationId)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.success) { return; }
            row.dataset.read = 'true';
            row.style.background = '#fff';
            row.style.borderLeftColor = 'rgba(32,0,130,0.12)';
        }).catch(function () { /* the notification stays unread; nothing to undo */ });
    }

    document.querySelectorAll('.inbox-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a')) { return; } // the "Open" link goes to its record
            markRead(row);
        });
    });

    var markAll = document.getElementById('inbox-mark-all');
    if (markAll) {
        markAll.addEventListener('click', function () {
            fetch(ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'all=1'
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data && data.success) { window.location.reload(); }
            }).catch(function () {});
        });
    }

    var search = document.getElementById('inbox-search');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var active = document.querySelector('.tab-content.active');
            if (!active) { return; }
            active.querySelectorAll('.inbox-row, .mode-card > div').forEach(function (item) {
                if (!item.classList.contains('inbox-row')) { return; } // only the lists filter
                var match = q === '' || item.textContent.toLowerCase().indexOf(q) !== -1;
                item.style.display = match ? '' : 'none';
            });
        });
    }
})();
</script>
HTML;
    }

    private static function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }
}
