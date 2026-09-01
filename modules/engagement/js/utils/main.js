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
        var grievanceView = e.target.closest('[data-grievance-view], a[href*="grievance_detail.php"]');
        if (grievanceView) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var grievanceId = Number(grievanceView.getAttribute('data-grievance-view'));
            if (!grievanceId && grievanceView.href) {
                grievanceId = Number(new URL(grievanceView.href, window.location.href).searchParams.get('id'));
            }
            if (grievanceId <= 0) return;

            if (typeof window.viewGrievanceDetails === 'function') {
                try {
                    window.viewGrievanceDetails(grievanceId);
                    return;
                } catch (error) {
                    console.warn('Grievance modal handler failed; using loader fallback.', error);
                }
            }

            import('../../pages/js/grievance.js')
                .then(function () {
                    if (typeof window.viewGrievanceDetails === 'function') {
                        window.viewGrievanceDetails(grievanceId);
                    } else {
                        openGrievanceDetailsFallback(grievanceId);
                    }
                })
                .catch(function (error) {
                    console.error('Unable to load grievance details module', error);
                    openGrievanceDetailsFallback(grievanceId);
                });
            return;
        }

        var a = e.target.closest('a[data-page]');
        if (!a) return;
        e.preventDefault();
        fetchPage(a.getAttribute('data-page'));
    });

    function openGrievanceDetailsFallback(grievanceId) {
        var existing = document.getElementById('global-grievance-modal');
        if (existing) existing.remove();

        var root = window.location.pathname.split('/modules/engagement/')[0] + '/modules/engagement/';
        var detailUrl = new URL('pages/grievance_detail.php', window.location.origin + root);
        detailUrl.searchParams.set('id', String(grievanceId));

        var modal = document.createElement('div');
        modal.id = 'global-grievance-modal';
        modal.className = 'global-grievance-modal grievance-area';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = '<div class="global-grievance-modal-dialog">' +
            '<div class="global-grievance-modal-header"><h2>Grievance Details</h2><button type="button" class="global-grievance-modal-close" aria-label="Close">&times;</button></div>' +
            '<div class="global-grievance-modal-body"><div class="text-muted text-center p-4">Loading details...</div></div></div>';
        var styleLink = document.createElement('link');
        styleLink.rel = 'stylesheet';
        styleLink.href = new URL('css/style/grievance.css', detailUrl).href;
        modal.appendChild(styleLink);
        document.body.appendChild(modal);
        modal.querySelector('.global-grievance-modal-close').addEventListener('click', function () { modal.remove(); });
        modal.addEventListener('click', function (event) { if (event.target === modal) modal.remove(); });

        fetch(detailUrl.href, { credentials: 'same-origin' })
            .then(function (response) { if (!response.ok) throw new Error('Unable to load grievance details.'); return response.text(); })
            .then(function (html) {
                var content = new DOMParser().parseFromString(html, 'text/html').querySelector('.grievance-detail-content');
                modal.querySelector('div > div:last-child').innerHTML = content ? content.outerHTML : '<div class="alert alert-danger">Grievance details not found.</div>';
            })
            .catch(function (error) {
                modal.querySelector('div > div:last-child').innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
            });
    }

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