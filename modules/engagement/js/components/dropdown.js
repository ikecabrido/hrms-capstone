const bellBtn      = document.getElementById('bellBtn');
    const bellDropdown = document.getElementById('bellDropdown');
    const bellWrapper  = document.getElementById('bellWrapper');
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userWrapper  = document.getElementById('userWrapper');
    const notifBadge   = document.getElementById('notifBadge');
    const markAllRead  = document.querySelector('.mark-all-read');

    function closeAll() {
        if (bellDropdown) bellDropdown.classList.remove('open');
        if (userDropdown) userDropdown.classList.remove('open');
    }

    function openDropdown(dropdown, triggerEl) {
        if (!dropdown || !triggerEl) return;
        const sidebar  = document.querySelector('.sidebar');
        const rect     = triggerEl.getBoundingClientRect();
        const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : { right: rect.right };

        dropdown.style.top  = rect.top + 'px';
        dropdown.style.left = (sidebarRect.right + 8) + 'px';
        dropdown.classList.add('open');
    }

    if (bellBtn && bellDropdown) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = bellDropdown.classList.contains('open');
            closeAll();
            if (!isOpen) openDropdown(bellDropdown, bellBtn);
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
        if (!bellWrapper.contains(target) && !userWrapper.contains(target)) {
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