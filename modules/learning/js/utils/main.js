    import { reinitPage } from './page-init.js';

    document.addEventListener('DOMContentLoaded', function () {

    // ─── Page Fetching ───────────────────────────────────────────────────────────

    function executePageScripts(container) {
        const scripts = Array.from(container.querySelectorAll('script'));
        scripts.forEach(function (script) {
            const replacement = document.createElement('script');
            Array.from(script.attributes).forEach(function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });
            replacement.textContent = script.textContent;
            script.parentNode.replaceChild(replacement, script);
        });
    }

    function fetchPage(page, push = true) {
        const container = document.querySelector('.container');
        if (!container) return;

        fetch(`page-loader.php?page=${encodeURIComponent(page)}`, { credentials: 'same-origin' })
            .then(function (response) {
            // Session expired — redirect to login
            if (response.status === 401) {
                return response.json().then(function (data) {
                window.location.href = data.redirect;
                });
            }

            if (!response.ok) throw new Error('Network error');
            const rendered = response.headers.get('X-Rendered-Page') || page;
            return response.text().then(function (html) {
                return { html: html, rendered: rendered };
            });
            })
            .then(function (result) {
            if (!result) return; // was a redirect, bail
            // Fully clear container before inserting new page
            while (container.firstChild) container.removeChild(container.firstChild);
            container.innerHTML = result.html;
            container.setAttribute('data-page', result.rendered);
            executePageScripts(container);
            updateActiveLink(result.rendered);

            if (push) {
                history.pushState({ page: result.rendered }, '', '?page=' + encodeURIComponent(result.rendered));
            }

            reinitPage(result.rendered);
            })
            .catch(function (err) {
            console.error('Page switch failed', err);
            });
        }

    // ─── Active Link ─────────────────────────────────────────────────────────────

    function updateActiveLink(page) {
        document.querySelectorAll('.menu-link, .active-menu-link').forEach(function (el) {
        el.className = 'menu-link';
        });
        var a = document.querySelector('.sidebar a[data-page="' + page + '"]');
        if (a) a.className = 'active-menu-link';
    }

    // ─── Sidebar Click Intercept ──────────────────────────────────────────────────
    // Guard: skip if the inline fallback script in index.php already handled this click.

    document.body.addEventListener('click', function (e) {
        var toolsBackdrop = e.target.closest('.learner-tools-backdrop');
        if (toolsBackdrop) {
            var drawer = document.getElementById('learner-tools-drawer');
            var drawerToggle = drawer && drawer.querySelector('.learner-tools-toggle');
            if (drawer && drawerToggle) {
                drawer.classList.remove('is-open');
                drawerToggle.setAttribute('aria-expanded', 'false');
                drawerToggle.setAttribute('aria-label', 'Show Tools');
                drawerToggle.title = 'Show Tools';
            }
            return;
        }
        var toolsToggle = e.target.closest('.learner-tools-toggle');
        if (toolsToggle) {
            e.preventDefault();
            var toolsDrawer = toolsToggle.closest('.learner-tools-shell');
            if (toolsDrawer) {
                var isOpen = toolsDrawer.classList.toggle('is-open');
                toolsToggle.setAttribute('aria-expanded', String(isOpen));
                toolsToggle.setAttribute('aria-label', isOpen ? 'Hide Tools' : 'Show Tools');
                toolsToggle.title = isOpen ? 'Hide Tools' : 'Show Tools';
            }
            return;
        }
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        if (a.dataset.navHandled) { a.dataset.navHandled = ''; return; }
        e.preventDefault();
        fetchPage(a.getAttribute('data-page'));
    });

    // ─── Keyboard shortcuts ──────────────────────────────────────────────────────

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            var toolsDrawer = document.getElementById('learner-tools-drawer');
            var toolsButton = toolsDrawer && toolsDrawer.querySelector('.learner-tools-toggle');
            if (toolsDrawer && toolsDrawer.classList.contains('is-open') && toolsButton) {
                toolsDrawer.classList.remove('is-open');
                toolsButton.setAttribute('aria-expanded', 'false');
                toolsButton.setAttribute('aria-label', 'Show Tools');
                toolsButton.title = 'Show Tools';
                return;
            }
        }
        if (!event.shiftKey) return;

        const code = event.code;

        // Skip if typing in an input
        const activeTag = document.activeElement && document.activeElement.tagName;
        if (activeTag && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag)) return;

        // Shift+Q → toggle nav sidebar
        if (code === 'KeyQ') {
            event.preventDefault();
            var hamburger = document.querySelector('.hamburger');
            if (hamburger) hamburger.click();
            return;
        }

        // Shift+W → toggle utility panel
        if (code === 'KeyW') {
            event.preventDefault();
            var learnerToolsToggle = document.querySelector('.learner-tools-toggle');
            if (learnerToolsToggle) {
                learnerToolsToggle.click();
                return;
            }
            var userNavigation = document.querySelector('.user-admin-navigation');
            if (userNavigation) {
                var isCollapsed = userNavigation.classList.toggle('user-admin-layout--collapsed');
                var collapseButton = userNavigation.querySelector('.user-admin-sidebar-collapse');
                var expandButton = userNavigation.querySelector('.user-admin-sidebar-expand');
                if (collapseButton) collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
                if (expandButton) expandButton.setAttribute('aria-expanded', String(isCollapsed));
                return;
            }
            if (typeof studyToggleSidebar === 'function') studyToggleSidebar();
            return;
        }

        // Shift+1-9 → switch nav tab
        var digitMatch = code.match(/^Digit([1-9])$/);
        if (digitMatch) {
            var navLink = document.querySelector('a[data-shortcut="' + digitMatch[1] + '"]');
            if (!navLink) return;
            event.preventDefault();
            navLink.click();
        }
    });

    // ─── Back / Forward ───────────────────────────────────────────────────────────

    window.addEventListener('popstate', function (e) {
        var page = (e.state && e.state.page) || new URL(location).searchParams.get('page') || 'dashboard-overview';
        fetchPage(page, false);
    });

    // ─── Initial Load ─────────────────────────────────────────────────────────────

    var initial = new URL(location).searchParams.get('page') || 'dashboard-overview';
    var initContainer = document.querySelector('.container');
    if (initContainer) initContainer.setAttribute('data-page', initial);
    updateActiveLink(initial);
    reinitPage(initial);

    });