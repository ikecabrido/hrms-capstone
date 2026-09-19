function initHamburgerLayout() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('footer');
    const header = document.querySelector('header');
    
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
    
    // Toggle sidebar
    hamburger?.addEventListener('click', function() {
        const mobile = isMobile();
        
        // Toggle hamburger animation
        this.classList.toggle('active');
        
        if (mobile) {
            // Mobile behavior: show overlay
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        } else {
            // Desktop behavior: hide sidebar and adjust layout
            sidebar.classList.toggle('hidden');
            
            // Update collapsed state using the shared body class rather than inline styles.
            // This keeps layout behavior consistent across pages and allows stylesheets
            // (e.g. `body.sidebar-collapse main.main-content`) to control the layout.
            if (sidebar.classList.contains('hidden')) {
                document.body.classList.add('sidebar-collapse');
            } else {
                document.body.classList.remove('sidebar-collapse');
            }
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
    
    // Apply the correct layout for the current viewport. This must run once
    // immediately on page load (not just react to click/resize events) -
    // otherwise whatever inline style happens to already be present (stale
    // from a previous view, or set by a resize event during a DevTools
    // device-emulation transition) permanently overrides the CSS underneath
    // it, since inline styles always beat stylesheet rules regardless of
    // any @media query or CSS variable.
    function applyLayoutForCurrentViewport() {
        const mobile = isMobile();

        if (mobile) {
            // Mobile: ensure the shared collapsed class is not applied and
            // clear any desktop-only states so CSS can take over.
            document.body.classList.remove('sidebar-collapse');
            sidebar.classList.remove('hidden');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburger?.classList.remove('active');
        } else {
            // Desktop: use the sidebar's `hidden` flag to drive the shared
            // `sidebar-collapse` class. This avoids writing inline styles here.
            overlay.classList.remove('active');
            if (sidebar.classList.contains('hidden')) {
                document.body.classList.add('sidebar-collapse');
            } else {
                document.body.classList.remove('sidebar-collapse');
            }
        }
    }
    
    // Apply correct layout immediately on load, before any user interaction.
    applyLayoutForCurrentViewport();
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyLayoutForCurrentViewport, 250);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHamburgerLayout);
} else {
    initHamburgerLayout();
}