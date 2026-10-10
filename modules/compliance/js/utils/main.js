    import { reinitPage } from './page-init.js';

    var NAV_COOLDOWN_MS = 800;
    var lastNavigationTime = 0;
    var lastNavigationPage = '';

    var NAV_DEBUG = new URLSearchParams(window.location.search).has('nav-debug');

    function logNav(label, page, current) {
        if (!NAV_DEBUG) return;
        if (typeof console !== 'undefined' && console.log) {
            console.log('[compliance-nav] ' + label + ' page=' + page + ' current=' + current + ' time=' + Date.now());
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

    // ─── Page Fetching ───────────────────────────────────────────────────────────

    function fetchPage(page, push = true) {
        var now = Date.now();
        var current = new URL(location).searchParams.get('page') || 'dashboard-overview';

        if (current === page && !push) {
            logNav('skip-same-page', page, current);
            return;
        }

        if (page === lastNavigationPage && (now - lastNavigationTime) < NAV_COOLDOWN_MS) {
            logNav('cooldown-skip', page, current);
            return;
        }

        lastNavigationTime = now;
        lastNavigationPage = page;

        logNav('fetch', page, current);

        const container = document.querySelector('.container');
        if (!container) return;

        fetch('/modules/compliance/index.php?page=' + encodeURIComponent(page), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (response.status === 401) {
                    return response.json().then(function (data) {
                        window.location.href = data.redirect;
                    });
                }
                if (!response.ok) throw new Error('Network error');
                return response.text().then(function (html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('.container');
                    if (!newContainer) throw new Error('Container not found');
                    return { html: newContainer.innerHTML, rendered: page };
                });
            })
            .then(function (result) {
                if (!result) return;
                container.innerHTML = result.html;

                container.querySelectorAll('script').forEach(function (oldScript) {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(function (attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

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
        var links = document.querySelectorAll('.sidebar a[data-page]');
        var active = document.querySelector('.sidebar a[data-page="' + page + '"]');

        links.forEach(function (el) {
            el.classList.remove('active-menu-link');
            if (!el.classList.contains('menu-link')) {
                el.classList.add('menu-link');
            }
        });

        if (active) {
            active.classList.remove('menu-link');
            active.classList.add('active-menu-link');
        }
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
        var current = new URL(location).searchParams.get('page') || 'dashboard-overview';
        if (current === page) {
            logNav('popstate-skip', page, current);
            return;
        }
        logNav('popstate', page, current);
        fetchPage(page, false);
    });

    // ─── Initial Load ─────────────────────────────────────────────────────────────

    var initial = new URL(location).searchParams.get('page') || 'dashboard-overview';
    logNav('initial', initial, initial);
    updateActiveLink(initial);
    reinitPage(initial);

    });
