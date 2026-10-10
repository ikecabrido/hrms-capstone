document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
});

let sidebarInitialized = false;

function initSidebar() {
    if (sidebarInitialized) return;
    sidebarInitialized = true;

    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    document.documentElement.classList.remove('sidebar-precollapsed');
    const footer = document.querySelector('footer');
    const header = document.querySelector('header');
    const hamburger = document.getElementById('hamburgerBtn');

    if (!sidebar || !hamburger) return;

    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    const SIDEBAR_STORAGE_KEY = 'complianceSidebarCollapsed';

    function isMobile() {
        return window.innerWidth <= 768;
    }

    function saveSidebarState(collapsed) {
        if (!isMobile()) {
            localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? 'true' : 'false');
        }
    }

    function openSidebar() {
        if (isMobile()) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            hamburger.classList.add('active');
            hamburger.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('sidebar-mobile-open');
        } else {
            sidebar.classList.remove('hidden');
            mainContent.classList.remove('sidebar-collapsed');
            if (footer) footer.style.marginLeft = '';
            if (header) {
                header.style.left = '';
                header.style.width = '';
            }
            hamburger.setAttribute('aria-expanded', 'true');
            saveSidebarState(false);
        }
    }

    function closeSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            document.body.classList.remove('sidebar-mobile-open');
        } else {
            sidebar.classList.add('hidden');
            mainContent.classList.add('sidebar-collapsed');
            if (footer) footer.style.marginLeft = '0';
            if (header) {
                header.style.left = '0';
                header.style.width = '100%';
            }
            hamburger.setAttribute('aria-expanded', 'false');
            saveSidebarState(true);
        }
    }

    function toggleSidebar() {
        if (isMobile()) {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        } else {
            if (sidebar.classList.contains('hidden')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        }
    }

    hamburger.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });

    // Always clear the page loader once the new document has loaded.
    window.addEventListener('pageshow', function() {
        const pageLoading = document.getElementById('pageLoading');

        if (!pageLoading) return;

        pageLoading.classList.remove('is-loading');
        pageLoading.setAttribute('aria-hidden', 'true');
    });

    // Page loading indicator for internal navigation.
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');

        if (!link) return;
        if (link.target === '_blank') return;
        if (link.hasAttribute('download')) return;

        /*
         * Change Password:
         *
         * 1. If already on Profile Settings, open the modal directly.
         * 2. If on another page, navigate to Profile Settings with
         *    #change-password. profile-settings.php opens the modal.
         *
         * This action intentionally bypasses the full-screen loader.
         */
        if (link.matches('[data-change-password-link]')) {
            e.preventDefault();

            const currentUrl = new URL(window.location.href);
            const currentPage = currentUrl.searchParams.get('page');

            if (
                currentPage === 'profile-settings' &&
                typeof window.pwOpenModal === 'function'
            ) {
                window.pwOpenModal();
                return;
            }

            window.location.href = link.href;
            return;
        }

        if (link.hasAttribute('data-no-loading')) return;

        const url = new URL(link.href, window.location.href);

        // External links are handled normally.
        if (url.origin !== window.location.origin) return;

        const currentPage = window.location.pathname + window.location.search;
        const targetPage = url.pathname + url.search;

        /*
         * Same page navigation with a hash:
         *
         * profile-settings
         *      -> profile-settings#change-password
         *
         * Let the browser handle this normally.
         * Do NOT show the full-screen loader.
         */
        if (currentPage === targetPage && url.hash) {
            return;
        }

        // Same exact URL: nothing to do.
        if (url.href === window.location.href) {
            return;
        }

        const pageLoading = document.getElementById('pageLoading');

        // If there is no loader, allow normal browser navigation.
        if (!pageLoading) return;

        e.preventDefault();

        pageLoading.classList.add('is-loading');
        pageLoading.setAttribute('aria-hidden', 'false');

        setTimeout(function() {
            window.location.href = url.href;
        }, 120);
    });

    overlay.addEventListener('click', function() {
        closeSidebar();
    });

    document.addEventListener('sidebar:close', function() {
        if (isMobile() && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    const menuLinks = document.querySelectorAll('.sidebar .menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) {
                closeSidebar();
            }
        });
    });

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const mobile = isMobile();

            if (mobile) {
                mainContent.classList.remove('sidebar-collapsed');
                if (footer) footer.style.marginLeft = '';
                if (header) {
                    header.style.left = '';
                    header.style.width = '';
                }
                sidebar.classList.remove('hidden');
                closeSidebar();
            } else {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                sidebar.classList.remove('active');

                if (!sidebar.classList.contains('hidden')) {
                    mainContent.classList.remove('sidebar-collapsed');
                    if (footer) footer.style.marginLeft = '';
                    if (header) {
                        header.style.left = '';
                        header.style.width = '';
                    }
                }
            }
        }, 150);
    });

    if (!isMobile()) {
        const savedCollapsed = localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true';

        if (savedCollapsed) {
            sidebar.classList.add('hidden');
            mainContent.classList.add('sidebar-collapsed');

            if (footer) footer.style.marginLeft = '0';

            if (header) {
                header.style.left = '0';
                header.style.width = '100%';
            }

            hamburger.setAttribute('aria-expanded', 'false');
        } else {
            sidebar.classList.remove('hidden');
            mainContent.classList.remove('sidebar-collapsed');

            if (footer) footer.style.marginLeft = '';

            if (header) {
                header.style.left = '';
                header.style.width = '';
            }

            hamburger.setAttribute('aria-expanded', 'true');
        }
    } else {
        sidebar.classList.remove('hidden');
        mainContent.classList.remove('sidebar-collapsed');

        if (footer) footer.style.marginLeft = '';

        if (header) {
            header.style.left = '';
            header.style.width = '';
        }

        hamburger.setAttribute('aria-expanded', 'false');
    }
}

if (document.readyState !== 'loading') {
    initSidebar();
}
