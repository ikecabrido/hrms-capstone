document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('footer');
    const header = document.querySelector('header');

    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function isMobile() {
        return window.innerWidth <= 768;
    }

    hamburger?.addEventListener('click', function() {
        const mobile = isMobile();
        this.classList.toggle('active');

        if (mobile) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('hidden');

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

    overlay?.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

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

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const mobile = isMobile();

            if (mobile) {
                mainContent.style.marginLeft = '';
                footer.style.marginLeft = '';
                header.style.left = '';
                header.style.width = '';
                sidebar.classList.remove('hidden');
            } else {
                overlay.classList.remove('active');

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
