// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const navbar = document.querySelector('.main-header.navbar');
    const contentWrapper = document.querySelector('.content-wrapper');
    const body = document.body;

    if (!sidebar) {
        console.error('Sidebar not found');
        return;
    }

    // For mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
    } else {
        // Use AdminLTE-compatible sidebar collapse state
        body.classList.toggle('sidebar-collapse');
    }
}

function hidePreloader() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.display = 'none';
        preloader.style.visibility = 'hidden';
    }
}

function showPreloader() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.visibility = 'visible';
        preloader.style.display = 'flex';
    }
}

// Make toggleSidebar globally accessible
window.toggleSidebar = toggleSidebar;

window.addEventListener('load', hidePreloader);

// Close sidebar when clicking on a link (mobile) and show preloader on navigation
document.addEventListener('DOMContentLoaded', function() {
    hidePreloader();

    const navLinks = document.querySelectorAll('a.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            const href = this.getAttribute('href');
            if (href && !href.includes('logout') && !href.startsWith('javascript') && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                showPreloader();
            }

            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('mainSidebar');
                if (sidebar) sidebar.classList.remove('open');
            }
        });
    });

    // Live Clock
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }

    updateClock();
    setInterval(updateClock, 1000);

    // Dark Mode Toggle
    const darkToggle = document.getElementById('darkToggle');
    const themeIcon = document.getElementById('themeIcon');

    if (darkToggle) {
        darkToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);

            if (themeIcon) {
                themeIcon.classList.toggle('fa-moon', !isDarkMode);
                themeIcon.classList.toggle('fa-sun', isDarkMode);
            }
        });
    }

    // Load dark mode preference (default to light mode for time_attendance)
    const darkModeSetting = localStorage.getItem('darkMode');
    const darkMode = darkModeSetting === 'true';

    // Reset to light mode by default on each page load for time_attendance
    if (!darkModeSetting) {
        localStorage.setItem('darkMode', 'false');
    }

    if (darkMode) {
        document.body.classList.add('dark-mode');
        const themeIconEl = document.getElementById('themeIcon');
        if (themeIconEl) {
            themeIconEl.classList.remove('fa-moon');
            themeIconEl.classList.add('fa-sun');
        }
    }

    // Handle responsive on resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar) sidebar.classList.remove('open');
        }
    });

    // Friendly logout confirmation
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            const ok = confirm('Are you sure you want to logout? Have a great day!');
            if (ok) {
                window.location.href = '../../logout.php';
            }
        });
    }
});
