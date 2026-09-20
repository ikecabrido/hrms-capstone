(function () {
    'use strict';

    var POLL_INTERVAL_SECONDS = 30;
    var DISMISS_TTL_MS = 10 * 1000;
    var STORAGE_KEY = 'clinic_stock_alert_dismissed_until';
    var LAST_HASH_KEY = 'clinic_stock_alert_last_hash';
    var state = {
        lastData: null,
        countdownTimer: null,
        pollTimer: null,
        hash: '',
        overlayEl: null,
        alertEl: null,
        initialized: false
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[c];
        });
    }

    function resolveEndpoint(fileName) {
        var base = document.currentScript && document.currentScript.src
            ? document.currentScript.src.replace(/\/js\/components\/[^/]+\.js.*$/, '/' + fileName)
            : null;
        if (base) return base;
        var pathname = location.pathname;
        var idx = pathname.lastIndexOf('/modules/clinic/');
        if (idx >= 0) {
            var pre = pathname.slice(0, idx);
            var root = (pre || '') + '/modules/clinic/' + fileName;
            return root;
        }
        var q = location.search.indexOf('page=') >= 0;
        if (q) {
            return './' + fileName;
        }
        return fileName;
    }

    function buildInventoryUrl(filter) {
        var url = location.pathname + '?page=medicines-inventory';
        if (filter) url += '&filter=' + encodeURIComponent(filter);
        return url;
    }

    function setDismissed() {
        try {
            var until = String(Date.now() + DISMISS_TTL_MS);
            window.sessionStorage.setItem(STORAGE_KEY, until);
        } catch (e) {}
    }

    function isDismissed() {
        try {
            var until = window.sessionStorage.getItem(STORAGE_KEY);
            if (!until) return false;
            var num = parseInt(until, 10);
            if (!num || Number.isNaN(num)) return false;
            if (Date.now() >= num) {
                window.sessionStorage.removeItem(STORAGE_KEY);
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    function getLastHash() {
        try { return window.sessionStorage.getItem(LAST_HASH_KEY) || ''; } catch (e) { return ''; }
    }
    function setLastHash(h) {
        try { window.sessionStorage.setItem(LAST_HASH_KEY, String(h || '')); } catch (e) {}
    }

    function hashData(data) {
        var key = [
            'E' + (data.expired_count || 0),
            'L' + (data.low_stock_count || 0),
            (data.expired || []).map(function (m) { return m.medicine_id + '|' + m.days_expired; }).join('~'),
            (data.low_stock || []).map(function (m) { return m.medicine_id + '|' + m.current_stock; }).join('~')
        ].join('::');
        var h = 0;
        for (var i = 0; i < key.length; i++) {
            h = ((h << 5) - h) + key.charCodeAt(i);
            h |= 0;
        }
        return String(h);
    }

    async function fetchAlerts() {
        var endpoint = resolveEndpoint('clinic-medicine-alerts.php');
        var response = await fetch(endpoint + '?_=' + Date.now(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        });
        var rawText = await response.text();
        var data;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            var preview = (rawText || '').trim().slice(0, 300);
            console.error('[clinic-stock-alert] Non-JSON response:', preview);
            throw new Error('Invalid response from stock alert endpoint.');
        }
        if (!response.ok || !data || data.success === false) {
            var msg = (data && data.message) ? data.message : 'Stock alert request failed.';
            throw new Error(msg);
        }
        return data;
    }

    function ensureDom() {
        if (!document.body) return false;
        if (document.getElementById('clinicStockAlertOverlay') && document.getElementById('clinicStockAlert')) {
            state.overlayEl = document.getElementById('clinicStockAlertOverlay');
            state.alertEl = document.getElementById('clinicStockAlert');
            return true;
        }
        if (!state.overlayEl) {
            var overlay = document.createElement('div');
            overlay.id = 'clinicStockAlertOverlay';
            overlay.className = 'clinic-stock-alert-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
            state.overlayEl = overlay;
        }
        if (!state.alertEl) {
            var alertEl = document.createElement('div');
            alertEl.id = 'clinicStockAlert';
            alertEl.className = 'clinic-stock-alert';
            alertEl.setAttribute('role', 'dialog');
            alertEl.setAttribute('aria-modal', 'true');
            alertEl.setAttribute('aria-labelledby', 'csaTitle');
            document.body.appendChild(alertEl);
            state.alertEl = alertEl;
        }
        return true;
    }

    function renderAlert(data) {
        if (!ensureDom()) return;
        var alertEl = state.alertEl;
        var overlay = state.overlayEl;
        if (!alertEl || !overlay) return;

        var total = Math.max(0, Number(data.total_alerts || 0));
        var expiredCount = Math.max(0, Number(data.expired_count || 0));
        var lowStockCount = Math.max(0, Number(data.low_stock_count || 0));
        var severity = expiredCount > 0 ? 'high' : (lowStockCount > 0 ? 'medium' : 'none');
        var severityLabel = severity === 'high' ? 'SEVERITY • HIGH • PATIENT SAFETY RISK'
                           : severity === 'medium' ? 'SEVERITY • MEDIUM • INVENTORY ALERT'
                           : 'SEVERITY • INFO';

        var summaryParts = [];
        if (expiredCount > 0) {
            summaryParts.push(
                '<strong>' + escapeHtml(expiredCount) + ' expired</strong>' +
                ' <span class="csa-summary-text">medicine(s) in stock. Do not dispense.</span>'
            );
        }
        if (lowStockCount > 0) {
            summaryParts.push(
                '<strong>' + escapeHtml(lowStockCount) + ' low stock</strong>' +
                ' <span class="csa-summary-text">medicine(s) below reorder level.</span>'
            );
        }

        var items = [];
        var maxItems = 3;
        var expired = data.expired || [];
        var lowStock = data.low_stock || [];
        for (var i = 0; i < expired.length && items.length < maxItems; i++) {
            var ex = expired[i];
            var daysLabel = Number(ex.days_expired || 0) === 1 ? 'day' : 'days';
            items.push(
                '<div class="csa-item csa-item-expired">' +
                    '<p class="csa-item-title"><i class="fa-solid fa-skull-crossbones"></i> ' + escapeHtml(ex.medicine_name) + '</p>' +
                    '<p class="csa-item-meta">Expired ' + escapeHtml(Number(ex.days_expired || 0)) + ' ' + daysLabel + ' ago (' + escapeHtml(ex.expiry_display || 'N/A') + ') • ' + escapeHtml(ex.category || 'N/A') + '</p>' +
                '</div>'
            );
        }
        for (var j = 0; j < lowStock.length && items.length < maxItems; j++) {
            var ls = lowStock[j];
            items.push(
                '<div class="csa-item csa-item-low">' +
                    '<p class="csa-item-title"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(ls.medicine_name) + '</p>' +
                    '<p class="csa-item-meta">Remaining: ' + escapeHtml(Number(ls.current_stock || 0)) + ' ' + escapeHtml(ls.unit || 'pcs') + ' • Reorder at: ' + escapeHtml(Number(ls.reorder_level || 0)) + ' • ' + escapeHtml(ls.category || 'N/A') + '</p>' +
                '</div>'
            );
        }
        var remainingTotal = (expired.length + lowStock.length) - items.length;
        var moreHtml = remainingTotal > 0
            ? '<div class="csa-more">+ ' + escapeHtml(remainingTotal) + ' more stock alerts &mdash; click Details.</div>'
            : '';

        var severityClass = severity === 'medium' ? 'severity-medium' : '';
        var iconClass = severity === 'high' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-exclamation';

        alertEl.innerHTML =
            '<div class="csa-top-row">' +
                '<div class="csa-icon-wrap">' +
                    '<div class="csa-count-badge">' + escapeHtml(total) + '</div>' +
                    '<i class="' + iconClass + '"></i>' +
                '</div>' +
                '<div class="csa-head-title-wrap">' +
                    '<h2 id="csaTitle" class="csa-title">THREAT DETECTED</h2>' +
                '</div>' +
                '<button type="button" class="csa-close" id="csaCloseBtn" aria-label="Dismiss alert">&times;</button>' +
            '</div>' +
            '<div class="csa-severity-pill ' + severityClass + '">' + escapeHtml(severityLabel) + '</div>' +
            '<div class="csa-countdown-pill ' + severityClass + '">' +
                '<i class="fa-solid fa-stopwatch"></i> <span id="csaCountdown">Next scan in ' + POLL_INTERVAL_SECONDS + 's</span>' +
            '</div>' +
            '<p class="csa-summary">' +
                '<strong>' + escapeHtml(total) + ' violations</strong>' +
                ' <span class="csa-summary-text">found in clinic stock &mdash; ' +
                    (expiredCount > 0 ? '<i class="fa-solid fa-triangle-exclamation" style="color:#dc2626"></i> ' + escapeHtml(expiredCount) : '0') +
                    ' expired / ' +
                    (lowStockCount > 0 ? '<i class="fa-solid fa-bell" style="color:#d97706"></i> ' + escapeHtml(lowStockCount) : '0') +
                    ' low' +
                '</span>' +
            '</p>' +
            (summaryParts.length ? '<p class="csa-summary">' + summaryParts.join('<br>') + '</p>' : '') +
            '<div class="csa-items">' + items.join('') + '</div>' +
            moreHtml +
            '<div class="csa-actions">' +
                '<button type="button" class="csa-btn csa-btn-resolve" id="csaResolveBtn">' +
                    '<i class="fa-solid fa-wand-magic-sparkles" style="margin-right:4px"></i> Resolve Now' +
                '</button>' +
                '<button type="button" class="csa-btn csa-btn-dismiss" id="csaDismissBtn">' +
                    '<i class="fa-solid fa-check" style="margin-right:4px"></i> Got it' +
                '</button>' +
                '<button type="button" class="csa-btn csa-btn-details" id="csaDetailsBtn">' +
                    '<i class="fa-solid fa-circle-info" style="margin-right:4px"></i> Details' +
                '</button>' +
            '</div>' +
            '<div class="csa-footer">Bestlink HR Clinic &bull; Auto-scans every ' + POLL_INTERVAL_SECONDS + 's</div>';

        attachHandlers();
        state.lastData = data;
    }

    function showAlert() {
        if (!ensureDom()) return;
        var el = state.alertEl;
        var overlay = state.overlayEl;
        if (!el || !overlay) return;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        el.classList.add('is-open');
    }

    function hideAlert() {
        if (!state.alertEl || !state.overlayEl) return;
        state.alertEl.classList.remove('is-open');
        state.overlayEl.classList.remove('is-open');
        state.overlayEl.setAttribute('aria-hidden', 'true');
    }

    function attachHandlers() {
        var el = state.alertEl;
        if (!el) return;
        var closeBtn = el.querySelector('#csaCloseBtn');
        if (closeBtn && closeBtn.dataset.hooked !== '1') {
            closeBtn.dataset.hooked = '1';
            closeBtn.addEventListener('click', function () { dismiss('Got it'); });
        }
        var dismissBtn = el.querySelector('#csaDismissBtn');
        if (dismissBtn && dismissBtn.dataset.hooked !== '1') {
            dismissBtn.dataset.hooked = '1';
            dismissBtn.addEventListener('click', function () { dismiss('Got it'); });
        }
        var resolveBtn = el.querySelector('#csaResolveBtn');
        if (resolveBtn && resolveBtn.dataset.hooked !== '1') {
            resolveBtn.dataset.hooked = '1';
            resolveBtn.addEventListener('click', function () {
                var data = state.lastData || {};
                var filter = (data.expired_count || 0) > 0 ? 'expired' : 'low_stock';
                hideAlert();
                setDismissed();
                window.location.href = buildInventoryUrl(filter);
            });
        }
        var detailsBtn = el.querySelector('#csaDetailsBtn');
        if (detailsBtn && detailsBtn.dataset.hooked !== '1') {
            detailsBtn.dataset.hooked = '1';
            detailsBtn.addEventListener('click', function () {
                openDetails();
            });
        }
        if (state.overlayEl && state.overlayEl.dataset.hooked !== '1') {
            state.overlayEl.dataset.hooked = '1';
            state.overlayEl.addEventListener('click', function () {
                dismiss('Got it');
            });
        }
    }

    function openDetails() {
        var data = state.lastData || {};
        var modal = document.getElementById('clinicStockAlertDetails');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'clinicStockAlertDetails';
            modal.className = 'clinic-stock-alert-details';
            document.body.appendChild(modal);
        }
        var items = (data.expired || []).map(function (item) {
            return '<tr><td>' + escapeHtml(item.medicine_name) + '</td><td><span class="csa-detail-tag expired">Expired</span></td><td>' + escapeHtml(item.expiry_display || 'N/A') + '</td><td>' + escapeHtml(item.current_stock) + '</td><td><a href="' + escapeHtml(buildInventoryUrl('expired')) + '">View</a></td></tr>';
        }).concat((data.low_stock || []).map(function (item) {
            return '<tr><td>' + escapeHtml(item.medicine_name) + '</td><td><span class="csa-detail-tag low">Low Stock</span></td><td>Reorder at ' + escapeHtml(item.reorder_level) + '</td><td>' + escapeHtml(item.current_stock) + '</td><td><a href="' + escapeHtml(buildInventoryUrl('low_stock')) + '">View</a></td></tr>';
        })).join('');
        modal.innerHTML = '<div class="csa-details-card" role="dialog" aria-modal="true" aria-labelledby="csaDetailsTitle"><div class="csa-details-head"><h2 id="csaDetailsTitle">Violation Details</h2><button type="button" class="csa-details-close" aria-label="Close details">&times;</button></div><p class="csa-details-summary">' + escapeHtml(data.expired_count || 0) + ' expired &bull; ' + escapeHtml(data.low_stock_count || 0) + ' low stock</p><div class="csa-details-table-wrap"><table><thead><tr><th>Medicine</th><th>Type</th><th>Details</th><th>Stock</th><th>Action</th></tr></thead><tbody>' + (items || '<tr><td colspan="5">No current violations.</td></tr>') + '</tbody></table></div></div>';
        modal.classList.add('is-open');
        modal.querySelector('.csa-details-close').addEventListener('click', function () { modal.classList.remove('is-open'); });
        modal.addEventListener('click', function (event) { if (event.target === modal) modal.classList.remove('is-open'); }, { once: true });
    }

    function dismiss(reason) {
        hideAlert();
        setDismissed();
    }

    function startCountdown() {
        if (state.countdownTimer) { clearInterval(state.countdownTimer); state.countdownTimer = null; }
        var remaining = POLL_INTERVAL_SECONDS;
        var updateLabel = function () {
            var el = document.getElementById('csaCountdown');
            if (!el) return;
            var label;
            if (remaining <= 1) {
                label = 'Scanning now…';
            } else {
                label = 'Next scan in ' + remaining + 's';
            }
            el.textContent = label;
        };
        updateLabel();
        state.countdownTimer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                remaining = POLL_INTERVAL_SECONDS;
            }
            updateLabel();
        }, 1000);
    }

    async function runOnce(forceShow) {
        try {
            var data = await fetchAlerts();
            if (!data) return;
            var total = Number(data.total_alerts || 0);
            var newHash = hashData(data);
            if (total <= 0) {
                hideAlert();
                setLastHash(newHash);
                state.hash = newHash;
                state.lastData = data;
                return;
            }
            renderAlert(data);
            var dismissed = !forceShow && isDismissed();
            var changed = newHash !== getLastHash();
            if (changed) {
                setDismissed._skip = true;
                try { window.sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
                dismissed = false;
            }
            if (!dismissed) {
                showAlert();
                startCountdown();
            }
            state.hash = newHash;
            setLastHash(newHash);
        } catch (err) {
            console.warn('[clinic-stock-alert] Poll error:', err.message || err);
        }
    }

    function schedulePolling() {
        if (state.pollTimer) { clearInterval(state.pollTimer); state.pollTimer = null; }
        state.pollTimer = setInterval(function () {
            runOnce(false);
        }, POLL_INTERVAL_SECONDS * 1000);
    }

    function init() {
        if (state.initialized) return;
        state.initialized = true;

        var domReady = function () {
            if (!document.body) {
                setTimeout(domReady, 40);
                return;
            }
            ensureDom();
            runOnce(true);
            schedulePolling();
        };
        if (document.body) {
            domReady();
        } else {
            document.addEventListener('DOMContentLoaded', domReady, { once: true });
        }
        window.addEventListener('page:loaded', function () {
            ensureDom();
            setTimeout(function () { runOnce(false); }, 50);
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                dismiss('Got it');
            }
        }, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
