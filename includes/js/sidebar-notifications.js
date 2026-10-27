/**
 * The shared sidebar's bell menu.
 *
 * Loaded by includes/sidebar.php itself, so every module runs this one copy next to the
 * markup it drives. It reads the endpoint from the bell's data attribute rather than
 * building a URL, which keeps module-relative paths out of the picture.
 *
 * dropdown.js still handles opening the menu and clearing the list locally; this adds the
 * persistence those clicks implied: "Mark all as read" and clicking a notification both
 * write through to the database, and the badge follows the count the server reports.
 */
(function () {
    'use strict';

    var dropdown = document.getElementById('bellDropdown');
    var badge = document.getElementById('notifBadge');
    var list = dropdown ? dropdown.querySelector('.notif-list') : null;
    var endpoint = dropdown ? dropdown.getAttribute('data-notifications-endpoint') : null;

    if (!dropdown || !badge || !list || !endpoint) {
        return;
    }

    function showUnread(count) {
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.classList.toggle('hidden', count <= 0);
    }

    function post(fields) {
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(fields).toString()
        }).then(function (response) {
            return response.json();
        });
    }

    var markAllRead = dropdown.querySelector('.mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function () {
            post({ action: 'mark-all-read' }).then(function (result) {
                if (result && result.success) {
                    showUnread(Number(result.unread) || 0);
                }
            }).catch(function (error) {
                console.warn('Could not mark notifications as read:', error);
            });
        });
    }

    list.addEventListener('click', function (event) {
        var item = event.target instanceof Element
            ? event.target.closest('.notif-item[data-notification-id]')
            : null;

        // Already-read rows and the empty state have nothing to persist.
        if (!item || !item.classList.contains('unread')) {
            return;
        }

        post({ action: 'mark-read', id: item.getAttribute('data-notification-id') }).then(function (result) {
            if (!result || !result.success) {
                return;
            }

            item.classList.remove('unread');
            showUnread(Number(result.unread) || 0);
        }).catch(function (error) {
            console.warn('Could not mark the notification as read:', error);
        });
    });
})();
