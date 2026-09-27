import { qs, qsa, esc, statusClass, priorityClass, getJSON } from './helpers.js';
import { openCaseDetail } from './case-detail.js';

export function loadCaseList() {
    var params = {
        action: 'list_cases',
        search: EC.filters.search,
        status: EC.filters.status,
        agency: EC.filters.agency,
        page: EC.page,
        page_size: 15
    };
    getJSON(params, function (data) {
        var tbody = qs('#ecCaseTableBody');
        if (!tbody) return;
        var total = (data.total || 0);
        var countEl = qs('#ecListCount');
        if (countEl) countEl.textContent = total + ' case' + (total === 1 ? '' : 's');
        if (!data.data || !data.data.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#8b93a1;">No cases found.</td></tr>';
            qs('#ecPagination').style.display = 'none';
            updateSummaryStats(data.stats && data.stats.total ? data.stats.total : 0, data.stats ? data.stats.open : 0, data.stats ? data.stats.scheduled : 0, data.stats ? data.stats.hearing : 0, data.stats ? data.stats.awaiting : 0, data.stats ? data.stats.closed : 0);
            return;
        }
        var html = '';
        data.data.forEach(function (c) {
            html += '<tr data-case-id="' + (c.case_id) + '">' +
                '<td data-label="Case No."><div class="ec-case-num">' + esc(c.case_number) + '</div><div class="ec-case-meta">' + esc(c.case_type || '') + '</div></td>' +
                '<td data-label="Authority">' + esc(c.external_agency || '—') + '</td>' +
                '<td data-label="Subject">' + esc(c.case_title || c.case_type || '—') + '</td>' +
                '<td data-label="Status"><span class="lc-status-stamp ' + statusClass(c.current_status) + '">' + esc(c.current_status) + '</span></td>' +
                '<td data-label="Priority"><span class="lc-status-stamp ' + priorityClass(c.priority) + '">' + esc(c.priority) + '</span></td>' +
                '<td data-label="Date Received">' + esc(c.date_received || '—') + '</td>' +
                '<td data-label="Assigned To">' + esc(c.assigned_name || '—') + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
        qsa('tr[data-case-id]', tbody).forEach(function (tr) {
            tr.addEventListener('click', function () {
                openCaseDetail(parseInt(tr.getAttribute('data-case-id'), 10));
            });
        });
        renderPagination(data.total, data.page, data.total_pages);
        if (data.stats) {
            updateSummaryStats(data.stats.total, data.stats.open, data.stats.scheduled, data.stats.hearing, data.stats.awaiting, data.stats.closed);
        }
        var countEl2 = qs('#ecListCount');
        if (countEl2) countEl2.textContent = total + ' case' + (total === 1 ? '' : 's');
    });
}

export function updateSummaryFromData(cases) {
    var total = cases.length;
    var open = 0, scheduled = 0, hearing = 0, awaiting = 0, closed = 0;
    cases.forEach(function (c) {
        var s = String(c.current_status || '').toLowerCase();
        if (s.indexOf('closed') !== -1 || s.indexOf('resolved') !== -1 || s.indexOf('withdrawn') !== -1 || s.indexOf('archived') !== -1) closed++;
        else if (s.indexOf('scheduled') !== -1) scheduled++;
        else if (s.indexOf('hearing') !== -1 || s.indexOf('conference') !== -1) hearing++;
        else if (s.indexOf('awaiting') !== -1) awaiting++;
        else if (s.indexOf('open') !== -1 || s.indexOf('draft') !== -1 || s.indexOf('notice received') !== -1) open++;
        else open++;
    });
    updateSummaryStats(total, open, scheduled, hearing, awaiting, closed);
}

export function updateSummaryStats(total, open, scheduled, hearing, awaiting, closed) {
    var el = function (id, val) { var e = qs('#' + id); if (e) e.textContent = val; };
    el('ecStatTotal', total);
    el('ecStatOpen', open);
    el('ecStatScheduled', scheduled);
    el('ecStatHearing', hearing);
    el('ecStatAwaiting', awaiting);
    el('ecStatClosed', closed);
}

export function renderPagination(total, page, totalPages) {
    var wrap = qs('#ecPagination');
    var info = qs('#ecPageInfo');
    var nav = qs('#ecPageNav');
    if (!wrap || !info || !nav) return;
    if (totalPages <= 1) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';
    info.textContent = 'Page ' + page + ' of ' + totalPages + ' (' + total + ' cases)';
    var html = '';
    html += '<button class="ec-page-btn" data-page="' + (page - 1) + '" ' + (page <= 1 ? 'aria-disabled="true"' : '') + '>&laquo;</button>';
    for (var i = 1; i <= totalPages; i++) {
        html += '<button class="ec-page-btn' + (i === page ? ' ec-page-btn--active' : '') + '" data-page="' + i + '">' + i + '</button>';
    }
    html += '<button class="ec-page-btn" data-page="' + (page + 1) + '" ' + (page >= totalPages ? 'aria-disabled="true"' : '') + '>&raquo;</button>';
    nav.innerHTML = html;
    qsa('.ec-page-btn', nav).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var p = parseInt(btn.getAttribute('data-page'), 10);
            if (isNaN(p) || p < 1 || p > totalPages) return;
            EC.page = p;
            loadCaseList();
        });
    });
}
