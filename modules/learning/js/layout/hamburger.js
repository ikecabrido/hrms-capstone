document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('footer');
    const header = document.querySelector('header');
    const desktopSidebarWidth = 252;
    
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

    function applyDesktopLayout(isHidden) {
        const width = isHidden ? 0 : desktopSidebarWidth;

        if (mainContent) mainContent.style.marginLeft = width + 'px';
        if (footer) footer.style.marginLeft = width + 'px';
        if (header) {
            header.style.left = width + 'px';
            header.style.width = 'calc(100% - ' + width + 'px)';
        }
    }

    function syncSidebarState() {
        if (!sidebar) return;

        if (isMobile()) {
            sidebar.classList.remove('hidden');
            if (overlay) overlay.classList.toggle('active', sidebar.classList.contains('active'));
            return;
        }

        applyDesktopLayout(sidebar.classList.contains('hidden'));
    }
    
    function toggleSidebar() {
        const mobile = isMobile();

        if (hamburger) {
            hamburger.classList.toggle('active');
        }

        if (mobile) {
            sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
            return;
        }

        sidebar.classList.toggle('hidden');
        syncSidebarState();
    }

    // Toggle sidebar
    hamburger?.addEventListener('click', toggleSidebar);


    
    // Close sidebar when clicking overlay (mobile only)
    overlay?.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    syncSidebarState();
    
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
                // Reset desktop styles
                if (mainContent) mainContent.style.marginLeft = '';
                if (footer) footer.style.marginLeft = '';
                if (header) {
                    header.style.left = '';
                    header.style.width = '';
                }
                sidebar.classList.remove('hidden');
            } else {
                // Remove mobile overlay
                if (overlay) overlay.classList.remove('active');
                sidebar.classList.remove('active');
                
                // Restore desktop layout if sidebar is not hidden
                syncSidebarState();
            }
        }, 250);
    });
});