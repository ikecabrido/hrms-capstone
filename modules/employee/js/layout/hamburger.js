document.addEventListener('DOMContentLoaded', function() {
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
            
            // Adjust main content, footer, and header
            if (sidebar.classList.contains('hidden')) {
                mainContent.style.marginLeft = '0';
                footer.style.marginLeft = '0';
                header.style.left = '0';
                header.style.width = '100%';
            } else {
                mainContent.style.marginLeft = '252px';
                footer.style.marginLeft = '252px';
                header.style.left = '252px';
                header.style.width = 'calc(100% - 252px)';
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
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const mobile = isMobile();
            
            if (mobile) {
                // Reset desktop styles
                mainContent.style.marginLeft = '';
                footer.style.marginLeft = '';
                header.style.left = '';
                header.style.width = '';
                sidebar.classList.remove('hidden');
            } else {
                // Remove mobile overlay
                overlay.classList.remove('active');
                
                // Restore desktop layout if sidebar is not hidden
                if (!sidebar.classList.contains('hidden')) {
                    mainContent.style.marginLeft = '252px';
                    footer.style.marginLeft = '252px';
                    header.style.left = '252px';
                    header.style.width = 'calc(100% - 252px)';
                }
            }
        }, 250);
    });
});