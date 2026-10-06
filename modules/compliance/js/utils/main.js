    import { reinitPage } from './page-init.js';

    var PREV_PAGE_KEY = 'compliance_prev_page';
    var NAV_COOLDOWN_MS = 800;
    var lastNavigationTime = 0;
    var lastNavigationPage = '';

    function markPreviousPage(currentPage) {
        try {
            var previous = sessionStorage.getItem(PREV_PAGE_KEY);
            if (previous && previous !== currentPage) {
                var prevLink = document.querySelector('[data-page="' + previous + '"]');
                if (prevLink) {
                    prevLink.classList.add('prev-page-link');
                    prevLink.setAttribute('title', 'Previously visited: ' + prevLink.textContent.trim());
                }
            }
            sessionStorage.setItem(PREV_PAGE_KEY, currentPage);
        } catch (e) {}
    }

    function clearPreviousPageMark() {
        try {
            document.querySelectorAll('.prev-page-link').forEach(function (el) {
                el.classList.remove('prev-page-link');
                el.removeAttribute('title');
            });
        } catch (e) {}
    }

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

        if (current !== page) {
            markPreviousPage(current);
        }

        logNav('fetch', page, current);

        const container = document.querySelector('.container');
        if (!container) return;

        fetch('index.php?page=' + encodeURIComponent(page), {
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
        clearPreviousPageMark();
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
        var current = new URL(location).searchParams.get('page') || 'dashboard-overview';
        if (current === page) {
            logNav('popstate-skip', page, current);
            return;
        }
        markPreviousPage(current);
        logNav('popstate', page, current);
        fetchPage(page, false);
    });

    // ─── Initial Load ─────────────────────────────────────────────────────────────

    var initial = new URL(location).searchParams.get('page') || 'dashboard-overview';
    logNav('initial', initial, initial);
    updateActiveLink(initial);
    reinitPage(initial);

    });
