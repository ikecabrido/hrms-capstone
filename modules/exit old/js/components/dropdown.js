const bellBtn      = document.getElementById('bellBtn');
    const bellDropdown = document.getElementById('bellDropdown');
    const bellWrapper  = document.getElementById('bellWrapper');
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userWrapper  = document.getElementById('userWrapper');
    const notifBadge   = document.getElementById('notifBadge');
    const markAllRead  = document.querySelector('.mark-all-read');

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
        if (!isOpen) openDropdown(bellDropdown, bellBtn);
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

    markAllRead.addEventListener('click', function () {
        document.querySelectorAll('.notif-item.unread').forEach(item => {
            item.classList.remove('unread');
        });
        notifBadge.classList.add('hidden');
    });