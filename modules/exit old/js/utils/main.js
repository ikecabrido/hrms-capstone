    import { reinitPage } from './page-init.js';

    document.addEventListener('DOMContentLoaded', function () {

    // ─── Script Re-execution (innerHTML does NOT run injected <script> tags) ──────
    // Scripts are re-created and run sequentially, in document order, so that
    // library scripts finish loading before any page script that depends on
    // them executes — matching normal browser document-parsing behavior.

    function runInjectedScripts(container, onDone) {
        const originalScripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        let i = 0;

        function runNext() {
            if (i >= originalScripts.length) {
                if (onDone) onDone();
                return;
            }
            const oldScript = originalScripts[i++];
            const newScript = document.createElement('script');

            Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });

            if (oldScript.src) {
                newScript.onload = runNext;
                newScript.onerror = runNext;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            } else {
                newScript.text = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
                runNext();
            }
        }

        runNext();
    }

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
            runInjectedScripts(container, function () {
                document.dispatchEvent(new Event('app:pageready'));
            });
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
    document.dispatchEvent(new Event('app:pageready'));

    });