const bellBtn      = document.getElementById('bellBtn');
const bellDropdown = document.getElementById('bellDropdown');
const bellWrapper  = document.getElementById('bellWrapper');
const userBtn      = document.getElementById('userBtn');
const userDropdown = document.getElementById('userDropdown');
const userWrapper  = document.getElementById('userWrapper');
const notifBadge   = document.getElementById('notifBadge');
const markAllRead  = document.getElementById('markAllRead');
const notifList    = document.getElementById('notifList');
const notifDetailDropdown = document.getElementById('notifDetailDropdown');
const notifDetailTitle = document.getElementById('notifDetailTitle');
const notifDetailBody = document.getElementById('notifDetailBody');
const notifDetailClose = document.getElementById('notifDetailClose');

let notificationsLoaded = false;
let allNotifications = [];

function setBadgeText(count) {
    const text = count > 99 ? '99+' : count;
    if (notifBadge) {
        notifBadge.textContent = text;
        notifBadge.classList.toggle('hidden', count === 0);
    }
}

async function refreshBadge() {
    try {
        const response = await fetch('lib/api/notifications.php?action=unread-count', {
            credentials: 'same-origin'
        });

        if (!response.ok) return;

        const result = await response.json();

        if (result.success) {
            setBadgeText(result.unread_count || 0);
        }
    } catch (error) {
        console.error('Failed to refresh notification badge:', error);
    }
}

function closeAll() {
    bellDropdown.classList.remove('open');
    userDropdown.classList.remove('open');
    if (notifDetailDropdown) {
        notifDetailDropdown.classList.remove('open');
        notifDetailDropdown.style.position = '';
        notifDetailDropdown.style.top = '';
        notifDetailDropdown.style.left = '';
        notifDetailDropdown.style.right = '';
    }

    if (window.innerWidth <= 768) {
        [bellDropdown, userDropdown].forEach(function(dd) {
            if (!dd) return;
            dd.style.position = '';
            dd.style.top = '';
            dd.style.left = '';
            dd.style.right = '';
        });
    }
}

function openDropdown(dropdown, triggerEl) {
    const sidebar = document.querySelector('.sidebar');
    const rect = triggerEl.getBoundingClientRect();
    const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : { right: 0 };

    if (window.innerWidth <= 768) {
        dropdown.style.position = '';
        dropdown.style.top = '';
        dropdown.style.left = '';
        dropdown.style.right = '';
    } else {
        dropdown.style.position = 'fixed';
        dropdown.style.top = rect.top + 'px';
        dropdown.style.left = (sidebarRect.right + 8) + 'px';
        dropdown.style.right = '';
    }

    dropdown.classList.add('open');

    if (dropdown === bellDropdown && !notificationsLoaded) {
        loadNotifications();
    }
}

function markNotificationAsRead(id) {
    const idx = allNotifications.findIndex(function(n) { return String(n.id) === String(id); });
    if (idx !== -1) {
        allNotifications[idx].is_read = 1;
        updateBadge(allNotifications);
    }
}

function openNotifDetail(notification, triggerEl) {
    const sidebar  = document.querySelector('.sidebar');
    const rect     = triggerEl.getBoundingClientRect();
    const sidebarRect = sidebar.getBoundingClientRect();

    notifDetailTitle.textContent = notification.title || 'Notification';

    const config = typeConfig(notification.type);
    const email = (notification.sender_email || notification.email || '').trim();
    const hasEmail = email !== '';

    const replyHref = hasEmail ? '?page=notification-compose&mode=reply&notification_id=' + encodeURIComponent(notification.id) + '&notification_key=' + encodeURIComponent(notification.type) + '&to_recipient_email=' + encodeURIComponent(email) : '#';
    const forwardHref = hasEmail ? '?page=notification-compose&mode=forward&notification_id=' + encodeURIComponent(notification.id) + '&notification_key=' + encodeURIComponent(notification.type) + '&subject=' + encodeURIComponent('Fwd: ' + notification.title) + '&body=' + encodeURIComponent(notification.message || '') : '#';

    const replyClass = 'notif-action-btn notif-reply-btn' + (hasEmail ? '' : ' disabled');
    const forwardClass = 'notif-action-btn notif-forward-btn' + (hasEmail ? '' : ' disabled');

    const actionsHtml = '<div class="notif-detail-actions">' +
        '<a class="' + replyClass + '" href="' + replyHref + '" ' + (hasEmail ? '' : 'onclick="return false;"') + '>' +
            '<i class="fa-solid fa-reply"></i> Reply' +
        '</a>' +
        '<a class="' + forwardClass + '" href="' + forwardHref + '" ' + (hasEmail ? '' : 'onclick="return false;"') + '>' +
            '<i class="fa-solid fa-share"></i> Forward' +
        '</a>' +
    '</div>';

    notifDetailBody.innerHTML = '<div class="notif-detail-meta">' +
        '<span class="notif-detail-module"><i class="fa-solid fa-cube"></i> ' + escapeHtml(notification.module || 'Compliance') + '</span>' +
        '<span class="notif-detail-time"><i class="fa-regular fa-clock"></i> ' + relativeTime(notification.created_at) + '</span>' +
    '</div>' +
    '<div class="notif-detail-icon-row">' +
        '<div class="notif-icon notif-icon-lg" style="background: ' + config.bg + '; color: ' + config.color + ';">' +
            '<i class="fa-solid ' + config.icon + '"></i>' +
        '</div>' +
    '</div>' +
    '<div class="notif-detail-message">' + escapeHtml(notification.message || '') + '</div>' +
    actionsHtml;

    notifDetailDropdown.classList.add('open');

    markNotificationAsRead(notification.id);
    persistReadState(notification.id);
}

function relativeTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function typeConfig(type) {
    const config = {
        danger: { bg: '#fef2f2', color: '#dc2626', icon: 'fa-circle-exclamation' },
        warning: { bg: '#fffbeb', color: '#d97706', icon: 'fa-triangle-exclamation' },
        info: { bg: '#ecfeff', color: '#0891b2', icon: 'fa-circle-info' },
        success: { bg: '#ecfdf5', color: '#059669', icon: 'fa-circle-check' },
        primary: { bg: '#eef2ff', color: '#4f46e5', icon: 'fa-star' }
    };

    return config[type] || config.info;
}

function renderNotifications(notifications) {
    if (!notifList) return;

    if (!notifications.length) {
        notifList.innerHTML = '<li class="notif-item empty-notif">No notifications found</li>';
        return;
    }

    notifList.innerHTML = notifications.map(function(notification) {
        const config = typeConfig(notification.type);
        const isRead = parseInt(notification.is_read, 10) === 1;
        const unreadClass = isRead ? '' : 'unread';

        const email = notification.email || '';
        const empId = notification.employee_id || '';
        const module = notification.module || '';
        const subtitle = email
            ? email
            : (empId ? 'Employee #' + empId : (module || 'Notification'));

        let previewMessage = escapeHtml(notification.message || '');
        if (previewMessage.length > 180) {
            previewMessage = previewMessage.slice(0, 180).trimEnd() + '...';
        }

        return '<li class="notif-item ' + unreadClass + '" data-id="' + notification.id + '" data-email="' + escapeHtml(email) + '">' +
            '<div class="notif-icon" style="background: ' + config.bg + '; color: ' + config.color + ';">' +
                '<i class="fa-solid ' + config.icon + '"></i>' +
            '</div>' +
            '<div class="notif-content">' +
                '<p>' + escapeHtml(notification.title) + '</p>' +
                '<span>' + previewMessage + ' &middot; ' + relativeTime(notification.created_at) + '</span>' +
            '</div>' +
        '</li>';
    }).join('');

    document.querySelectorAll('.notif-item[data-id]').forEach(function(item) {
        item.addEventListener('click', function(e) {
            const id = this.getAttribute('data-id');
            const notification = allNotifications.find(function(n) { return String(n.id) === String(id); });
            if (notification) {
                markNotificationAsRead(id);
                persistReadState(id);
                openNotifDetail(notification, this);
            }
        });
    });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function loadNotifications() {
    try {
        const response = await fetch('lib/api/notifications.php?action=list&limit=100', {
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('Failed to load notifications');

        const result = await response.json();

        if (result.success) {
            allNotifications = (result.data || []).map(function(n) {
                n.is_read = parseInt(n.is_read, 10) === 1;
                return n;
            });
            renderNotifications(allNotifications);
            updateBadge(allNotifications);
        } else {
            if (notifList) {
                notifList.innerHTML = '<li class="notif-item empty-notif">' + escapeHtml(result.message || 'Failed to load notifications') + '</li>';
            }
        }

        notificationsLoaded = true;
    } catch (error) {
        console.error('Notifications load failed:', error);
        if (notifList) {
            notifList.innerHTML = '<li class="notif-item empty-notif">Failed to load notifications</li>';
        }
    }
}

async function updateBadge(notifications) {
    const unreadCount = notifications.filter(function(n) {
        return parseInt(n.is_read, 10) !== 1;
    }).length;

    setBadgeText(unreadCount);
}

async function persistReadState(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark-read');
        formData.append('id', id);

        await fetch('lib/api/notifications.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
    } catch (error) {
        console.error('Persist read state failed:', error);
    }
}

async function markAsRead(id) {
    markNotificationAsRead(id);
    await persistReadState(id);
}

async function markAllAsRead() {
    try {
        const formData = new FormData();
        formData.append('action', 'mark-all-read');

        const response = await fetch('lib/api/notifications.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            allNotifications.forEach(function(n) { n.is_read = true; });
            document.querySelectorAll('.notif-item.unread').forEach(function(item) {
                item.classList.remove('unread');
            });
            if (notifBadge) notifBadge.classList.add('hidden');
        }
    } catch (error) {
        console.error('Mark all as read failed:', error);
    }
}

bellBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = bellDropdown.classList.contains('open');
    closeAll();
    if (!isOpen) {
        openDropdown(bellDropdown, bellBtn);
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const hamburger = document.getElementById('hamburgerBtn');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                if (hamburger) {
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
                document.body.classList.remove('sidebar-mobile-open');
            }
        }
    }
});

userBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = userDropdown.classList.contains('open');
    closeAll();
    if (!isOpen) {
        openDropdown(userDropdown, userBtn);
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const hamburger = document.getElementById('hamburgerBtn');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                if (hamburger) {
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
                document.body.classList.remove('sidebar-mobile-open');
            }
        }
    }
});

document.addEventListener('click', function (e) {
    const inBell = bellWrapper && bellWrapper.contains(e.target);
    const inUser = userWrapper && userWrapper.contains(e.target);
    const inBellDropdown = bellDropdown && bellDropdown.contains(e.target);
    const inUserDropdown = userDropdown && userDropdown.contains(e.target);
    if (!inBell && !inUser && !inBellDropdown && !inUserDropdown) {
        closeAll();
    }
});

markAllRead.addEventListener('click', function (e) {
    e.stopPropagation();
    markAllAsRead();
});

notifDetailClose.addEventListener('click', function (e) {
    e.stopPropagation();
    notifDetailDropdown.classList.remove('open');
});

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshBadge);
    } else {
        refreshBadge();
    }

    window.addEventListener('resize', function() {
        var isMobile = window.innerWidth <= 768;
        [bellDropdown, userDropdown].forEach(function(dd) {
            if (!dd) return;
            if (isMobile) {
                dd.style.position = '';
                dd.style.top = '';
                dd.style.left = '';
                dd.style.right = '';
            }
            if (!dd.classList.contains('open')) return;
            if (!isMobile) {
                var triggerId = dd.id === 'userDropdown' ? 'userBtn' : (dd.id === 'bellDropdown' ? 'bellBtn' : null);
                var triggerEl = triggerId ? document.getElementById(triggerId) : null;
                if (triggerEl) {
                    var sidebar = document.querySelector('.sidebar');
                    var rect = triggerEl.getBoundingClientRect();
                    var sidebarRect = sidebar ? sidebar.getBoundingClientRect() : { right: 0 };
                    dd.style.position = 'fixed';
                    dd.style.top = rect.top + 'px';
                    dd.style.left = (sidebarRect.right + 8) + 'px';
                    dd.style.right = '';
                }
            }
        });
    });
