    import { reinitPage } from './page-init.js';

    document.addEventListener('DOMContentLoaded', function () {

    // Helper: execute any scripts that were injected via innerHTML.
    // Also temporarily intercept registrations for 'DOMContentLoaded' so
    // scripts that attach that listener after being injected still run.
    // Execute scripts injected via innerHTML and wait for external scripts to load.
    function executeInjectedScripts(container) {
        return new Promise((resolve) => {
            try {
                const scripts = Array.from(container.querySelectorAll('script'));
                if (!scripts.length) return resolve();

                const originalAddEventListener = document.addEventListener;

                // Intercept DOMContentLoaded listener attachments and invoke them immediately.
                document.addEventListener = function (type, listener, options) {
                    if (type === 'DOMContentLoaded') {
                        try { listener.call(document, new Event('DOMContentLoaded')); } catch (e) { /* ignore */ }
                    }
                    return originalAddEventListener.call(this, type, listener, options);
                };

                const loadPromises = scripts.map(s => {
                    return new Promise((res) => {
                        const srcAttr = s.getAttribute && s.getAttribute('src');
                        if (srcAttr) {
                            // Resolve to absolute URL for dedupe checks
                            const abs = new URL(srcAttr, location).href;
                            const existing = Array.from(document.querySelectorAll('script'))
                                .find(el => el.src && el.src === abs && el.getAttribute('data-injected'));
                            if (existing) {
                                // Remove placeholder in container and treat as already loaded
                                s.parentNode && s.parentNode.removeChild(s);
                                return res();
                            }

                            const newScript = document.createElement('script');
                            if (s.type) newScript.type = s.type;
                            newScript.src = abs;
                            newScript.async = false;
                            newScript.setAttribute('data-injected', '1');
                            newScript.addEventListener('load', function () { res(); });
                            newScript.addEventListener('error', function () { console.error('Failed to load', abs); res(); });
                            document.body.appendChild(newScript);
                            s.parentNode && s.parentNode.removeChild(s);
                        } else {
                            // Inline script: execute immediately
                            const newScript = document.createElement('script');
                            if (s.type) newScript.type = s.type;
                            newScript.textContent = s.textContent || s.innerText || '';
                            document.body.appendChild(newScript);
                            s.parentNode && s.parentNode.removeChild(s);
                            // Inline executed synchronously
                            return res();
                        }
                    });
                });

                Promise.all(loadPromises).then(() => {
                    // restore original addEventListener
                    document.addEventListener = originalAddEventListener;
                    resolve();
                }).catch((err) => {
                    document.addEventListener = originalAddEventListener;
                    console.error('executeInjectedScripts promise error', err);
                    resolve();
                });
            } catch (err) {
                console.error('executeInjectedScripts error', err);
                try { document.addEventListener = originalAddEventListener; } catch (e) {}
                resolve();
            }
        });
    }

    // ─── Page Fetching ───────────────────────────────────────────────────────────

    async function fetchPage(page, push = true) {
        const container = document.querySelector('.container');
        if (!container) return;

        console.log('[TA PAGE] Loading page:', page);

        try {
            if (typeof window.clearPageCleanup === 'function') {
                window.clearPageCleanup();
            }

            const response = await fetch(`page-loader.php?page=${encodeURIComponent(page)}`, { credentials: 'same-origin' });
            // Session expired — redirect to login
            if (response.status === 401) {
                return response.json().then(function (data) {
                    window.location.href = data.redirect;
                });
            }

            if (!response.ok) throw new Error('Network error');
            const rendered = response.headers.get('X-Rendered-Page') || page;
            const html = await response.text();
            const result = { html: html, rendered: rendered };

            if (!result) return; // was a redirect, bail
            container.innerHTML = result.html;
            console.log('[TA PAGE] HTML injected:', result.rendered);
            console.log('[TA PAGE] Loading page scripts:', result.rendered);
            await executeInjectedScripts(container);
            console.log('[TA PAGE] Page scripts ready:', result.rendered);

            updateActiveLink(result.rendered);

            if (push) {
                history.pushState({ page: result.rendered }, '', '?page=' + encodeURIComponent(result.rendered));
            }

            reinitPage(result.rendered);
        } catch (err) {
            console.error('Page switch failed', err);
        }
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