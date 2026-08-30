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
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        if (a.dataset.navHandled) { a.dataset.navHandled = ''; return; }
        e.preventDefault();
        fetchPage(a.getAttribute('data-page'));
    });

    // ─── Keyboard shortcuts ──────────────────────────────────────────────────────

    document.addEventListener('keydown', function (event) {
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

        // Shift+W → toggle course panel
        if (code === 'KeyW') {
            event.preventDefault();
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