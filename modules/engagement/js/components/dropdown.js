const bellBtn      = document.getElementById('bellBtn');
    const bellDropdown = document.getElementById('bellDropdown');
    const bellWrapper  = document.getElementById('bellWrapper');
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userWrapper  = document.getElementById('userWrapper');
    const notifBadge   = document.getElementById('notifBadge');
    const markAllRead  = document.querySelector('.mark-all-read');

    if (bellDropdown) document.body.appendChild(bellDropdown);
    if (userDropdown) document.body.appendChild(userDropdown);

    function closeAll() {
        if (bellDropdown) bellDropdown.classList.remove('open');
        if (userDropdown) userDropdown.classList.remove('open');
    }

    function openDropdown(dropdown, triggerEl) {
        if (!dropdown || !triggerEl) return;
        const sidebar  = document.querySelector('.sidebar');
        const rect     = triggerEl.getBoundingClientRect();
        const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : { right: rect.right };

        if (window.innerWidth <= 768) {
            const width = Math.min(300, window.innerWidth - 24);
            dropdown.style.width = width + 'px';
            const centeredLeft = rect.left + (rect.width / 2) - (width / 2);
            dropdown.style.left = Math.max(12, Math.min(centeredLeft, window.innerWidth - width - 12)) + 'px';
            dropdown.style.top = Math.max(12, rect.bottom + 8) + 'px';
        } else {
            dropdown.style.width = '';
            dropdown.style.top  = rect.top + 'px';
            dropdown.style.left = (sidebarRect.right + 8) + 'px';
        }
        dropdown.classList.add('open');

        if (window.innerWidth <= 768) {
            const maxTop = window.innerHeight - dropdown.getBoundingClientRect().height - 12;
            dropdown.style.top = Math.max(12, Math.min(parseFloat(dropdown.style.top), maxTop)) + 'px';
        }
    }

    if (bellBtn && bellDropdown) {
        const toggleBellDropdown = function (e) {
            e.stopPropagation();
            const isOpen = bellDropdown.classList.contains('open');
            closeAll();
            if (!isOpen) openDropdown(bellDropdown, bellBtn);
        };
        bellBtn.addEventListener('click', toggleBellDropdown);
        bellBtn.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleBellDropdown(event);
            }
        });
    }

    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = userDropdown.classList.contains('open');
            closeAll();
            if (!isOpen) openDropdown(userDropdown, userBtn);
        });
    }

    document.addEventListener('click', function (e) {
        const target = e.target;
        if (!bellWrapper || !userWrapper) return;
        if (!bellWrapper.contains(target) && !bellDropdown?.contains(target)
            && !userWrapper.contains(target) && !userDropdown?.contains(target)) {
            closeAll();
        }
    });

    if (markAllRead) {
        markAllRead.addEventListener('click', function () {
            markAllRead.disabled = true;
            fetch('api/communication.php?action=mark_all_notifications_read', {
                method: 'POST',
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) throw new Error('Unable to mark notifications as read.');
                    return response.text().then(text => {
                        if (!text || !text.trim()) {
                            throw new Error('Empty JSON response from server.');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            throw new Error('Malformed JSON response from server.');
                        }
                    });
                })
                .then(() => {
                    document.querySelectorAll('.notif-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    const notificationList = document.getElementById('sidebar-notification-list');
                    if (notificationList) notificationList.dataset.unreadCount = '0';
                    if (notifBadge) notifBadge.classList.add('hidden');
                    markAllRead.disabled = true;
                    window.dispatchEvent(new CustomEvent('notifications:all-read'));
                })
                .catch(error => {
                    markAllRead.disabled = false;
                    console.error(error);
                });
        });
    }

    const notificationList = document.getElementById('sidebar-notification-list');
    if (notificationList) {
        notificationList.addEventListener('click', function (event) {
            const item = event.target.closest('.notif-item.unread[data-notification-id]');
            if (!item || item.classList.contains('is-marking-read')) return;

            item.classList.add('is-marking-read');
            fetch('api/communication.php?action=mark_notification_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify({
                    notification_id: item.dataset.notificationId,
                    source: item.dataset.notificationSource || 'hr'
                })
            })
                .then(response => {
                    if (!response.ok) throw new Error('Unable to mark notification as read.');
                    return response.text().then(text => {
                        if (!text || !text.trim()) {
                            throw new Error('Empty JSON response from server.');
                        }
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            throw new Error('Malformed JSON response from server.');
                        }
                    });
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    item.classList.remove('unread', 'is-marking-read');
                    const unreadCount = Math.max(0, Number(notificationList.dataset.unreadCount || 0) - 1);
                    notificationList.dataset.unreadCount = String(unreadCount);
                    if (notifBadge) {
                        notifBadge.textContent = String(unreadCount);
                        notifBadge.classList.toggle('hidden', unreadCount === 0);
                    }
                    if (markAllRead) markAllRead.disabled = unreadCount === 0;
                })
                .catch(error => {
                    item.classList.remove('is-marking-read');
                    console.error(error);
                });
        });
    }