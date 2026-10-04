const bellBtn      = document.getElementById('bellBtn');
    const bellDropdown = document.getElementById('bellDropdown');
    const bellWrapper  = document.getElementById('bellWrapper');
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userWrapper  = document.getElementById('userWrapper');
    const notifBadge   = document.getElementById('notifBadge');
    let markAllRead  = document.querySelector('.mark-all-read');

    function closeAll() {
        bellDropdown.classList.remove('open');
        userDropdown.classList.remove('open');
    }

    function openDropdown(dropdown, triggerEl) {
        const sidebar  = document.querySelector('.sidebar');
        const rect     = triggerEl.getBoundingClientRect();
        const sidebarRect = sidebar.getBoundingClientRect();

        dropdown.style.top  = rect.top + 'px';
        dropdown.style.left = (sidebarRect.right + 8) + 'px';
        dropdown.classList.add('open');
    }

    bellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = bellDropdown.classList.contains('open');
        closeAll();
        if (!isOpen) {
            const list = bellDropdown.querySelector('.notif-list');
            if (list && !list.dataset.loaded) {
                // use absolute path if TA_ROOT is provided
                const api = (window.__TA_ROOT ? (window.__TA_ROOT + '/app/api/notifications.php') : 'app/api/notifications.php');
                fetch(api)
                    .then(r => r.text())
                    .then(html => {
                            list.innerHTML = html;
                            list.dataset.loaded = '1';
                            markAllRead = document.querySelector('.mark-all-read');

                            function getReadSet() {
                                try {
                                    const raw = localStorage.getItem('time_notif_read') || '[]';
                                    return new Set(JSON.parse(raw));
                                } catch (e) {
                                    return new Set();
                                }
                            }

                            function saveReadSet(set) {
                                const arr = Array.from(set);
                                localStorage.setItem('time_notif_read', JSON.stringify(arr));
                            }

                            function updateBadge() {
                                const nb = document.getElementById('notifBadge');
                                const anyUnread = !!document.querySelector('.notif-item.unread');
                                if (nb) {
                                    if (anyUnread) nb.classList.remove('hidden'); else nb.classList.add('hidden');
                                }
                            }

                            function markAsRead(id) {
                                if (!id) return;
                                const set = getReadSet();
                                set.add(id);
                                saveReadSet(set);
                                const el = document.querySelector('.notif-item[data-notif-id="' + id + '"]');
                                if (el) el.classList.remove('unread');
                                updateBadge();
                            }

                            // initialize read state and attach click handlers
                            (function initNotifications() {
                                const readSet = getReadSet();
                                document.querySelectorAll('.notif-item[data-notif-id]').forEach(item => {
                                    const id = item.dataset.notifId;
                                    if (readSet.has(id)) {
                                        item.classList.remove('unread');
                                    }
                                    // clicking should mark read (but keep the item in list)
                                    const link = item.querySelector('a');
                                    if (link) {
                                        link.addEventListener('click', function (ev) {
                                            markAsRead(id);
                                            // allow navigation after marking
                                        });
                                    } else {
                                        item.addEventListener('click', function () {
                                            markAsRead(id);
                                        });
                                    }
                                });

                                if (markAllRead) {
                                    markAllRead.addEventListener('click', function () {
                                        const set = getReadSet();
                                        document.querySelectorAll('.notif-item[data-notif-id]').forEach(it => set.add(it.dataset.notifId));
                                        saveReadSet(set);
                                        document.querySelectorAll('.notif-item.unread').forEach(item => item.classList.remove('unread'));
                                        updateBadge();
                                    });
                                }

                                updateBadge();
                            })();
                        }).catch(err => console.error('Failed loading notifications', err));
            }
            openDropdown(bellDropdown, bellBtn);
        }
    });

    userBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = userDropdown.classList.contains('open');
        closeAll();
        if (!isOpen) openDropdown(userDropdown, userBtn);
    });

    document.addEventListener('click', function (e) {
        if (!bellWrapper.contains(e.target) && !userWrapper.contains(e.target)) {
            closeAll();
        }
    });

    if (markAllRead) {
        markAllRead.addEventListener('click', function () {
            document.querySelectorAll('.notif-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            if (notifBadge) notifBadge.classList.add('hidden');
        });
    }