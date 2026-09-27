    import { reinitPage } from './page-init.js';

    document.addEventListener('DOMContentLoaded', function () {

    // ─── Page Fetching ───────────────────────────────────────────────────────────

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
            container.innerHTML = result.html;
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
        var a = document.querySelector('[data-page="' + page + '"]');
        if (a) a.className = 'active-menu-link';
    }

    // ─── Sidebar Click Intercept ──────────────────────────────────────────────────

    document.body.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-page]');
        if (!a) return;
        e.preventDefault();
        fetchPage(a.getAttribute('data-page'));
    });

    // ─── Back / Forward ───────────────────────────────────────────────────────────

    window.addEventListener('popstate', function (e) {
        var page = (e.state && e.state.page) || new URL(location).searchParams.get('page') || 'dashboard-overview';
        fetchPage(page, false);
    });

    // ─── Initial Load ─────────────────────────────────────────────────────────────

    var initial = new URL(location).searchParams.get('page') || 'dashboard-overview';
    updateActiveLink(initial);
    reinitPage(initial);

    });