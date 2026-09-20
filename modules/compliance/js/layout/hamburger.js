document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
});

let sidebarInitialized = false;

function initSidebar() {
    if (sidebarInitialized) return;
    sidebarInitialized = true;

    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
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

    function isMobile() {
        return window.innerWidth <= 768;
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
        sidebar.classList.remove('hidden');
        mainContent.classList.remove('sidebar-collapsed');
        if (footer) footer.style.marginLeft = '';
        if (header) {
            header.style.left = '';
            header.style.width = '';
        }
        hamburger.setAttribute('aria-expanded', 'true');
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
