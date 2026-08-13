document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    // Create overlay for mobile
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Check if we're on mobile or desktop
    function isMobile() {
        return window.innerWidth <= 768;
    }

    function syncDesktopCollapseState() {
        const collapsed = sidebar.classList.contains('hidden') || document.body.classList.contains('sidebar-collapse');
        document.body.classList.toggle('sidebar-collapse', collapsed);
        sidebar.classList.toggle('hidden', collapsed);
    }

    // Toggle sidebar
    hamburger?.addEventListener('click', function() {
        const mobile = isMobile();

        this.classList.toggle('active');

        if (mobile) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        } else {
            const currentlyCollapsed = sidebar.classList.contains('hidden') || document.body.classList.contains('sidebar-collapse');
            sidebar.classList.toggle('hidden', !currentlyCollapsed);
            document.body.classList.toggle('sidebar-collapse', !currentlyCollapsed);
        }
    });

    // Close sidebar when clicking overlay (mobile only)
    overlay?.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    // Close sidebar when clicking menu links on mobile
    const menuLinks = document.querySelectorAll('.sidebar .menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const mobile = isMobile();

            if (mobile) {
                sidebar.classList.remove('hidden');
                document.body.classList.remove('sidebar-collapse');
                overlay.classList.remove('active');
            } else {
                overlay.classList.remove('active');
                syncDesktopCollapseState();
            }
        }, 250);
    });
});