/**
 * Points the shared sidebar's bell at this module's pages.
 *
 * The bell menu is shared markup — includes/sidebar.php prints it for every module, and
 * its rows are plain text: the notification id is on the row, but nothing says where it
 * leads. The pages a row leads to are this module's, so this script applies them.
 *
 * It reads the map the module's entry point prints (window.LD_BELL_LINKS, built by
 * classes/notificationlinks.php) and turns each row into a real link, so the shared
 * sidebar, its endpoint and every other module are untouched. A row opens the page that
 * owns its subject; "View all notifications" opens the reader's own list.
 *
 * Wrapping the row's children in an anchor with display:contents keeps the link
 * semantics (middle-click, copy address, hover target) without disturbing the list's
 * layout: the anchor generates no box of its own, so the shared CSS still sees the same
 * children it styled.
 */
(function () {
    'use strict';

    var config = window.LD_BELL_LINKS;
    var dropdown = document.getElementById('bellDropdown');

    if (!config || !dropdown) {
        return;
    }

    var list = dropdown.querySelector('.notif-list');
    var links = config.links || {};
    var endpoint = dropdown.getAttribute('data-notifications-endpoint');

    // The footer's "View all notifications" is a placeholder (href="#") in the shared
    // markup; this module knows which list belongs to the reader.
    var footerLink = dropdown.querySelector('.dropdown-footer a');
    if (footerLink && config.inbox) {
        footerLink.setAttribute('href', config.inbox);
    }

    if (!list) {
        return;
    }

    /**
     * Mark the row read before navigating, because navigation cancels the request the
     * shared bell script starts on the same click. Removing the class first also stops
     * that script from sending a second one. The navigation never waits longer than
     * NAVIGATE_AFTER_MS, so a slow or failing request cannot trap the click.
     */
    var NAVIGATE_AFTER_MS = 600;

    function markReadThenGo(item, id, href) {
        var go = function () {
            window.location.href = href;
        };

        if (!endpoint || !item.classList.contains('unread')) {
            go();
            return;
        }

        item.classList.remove('unread');

        var settled = false;
        var finish = function () {
            if (settled) {
                return;
            }
            settled = true;
            go();
        };

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({ action: 'mark-read', id: id }).toString()
        }).then(finish).catch(finish);

        window.setTimeout(finish, NAVIGATE_AFTER_MS);
    }

    Array.prototype.forEach.call(
        list.querySelectorAll('.notif-item[data-notification-id]'),
        function (item) {
            var id = item.getAttribute('data-notification-id');
            var href = links[id];

            // A row without a destination — an id older than the map, or a list rendered
            // by another module — keeps its current behaviour.
            if (!href || item.querySelector('a.notif-link')) {
                return;
            }

            var link = document.createElement('a');
            link.className = 'notif-link';
            link.href = href;
            link.style.display = 'contents';
            link.style.cursor = 'pointer';

            while (item.firstChild) {
                link.appendChild(item.firstChild);
            }
            item.appendChild(link);

            link.addEventListener('click', function (event) {
                // Modified clicks belong to the browser: new tab, new window, download.
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                markReadThenGo(item, id, href);
            });
        }
    );
})();
