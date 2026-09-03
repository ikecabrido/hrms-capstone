/**
 * document-requests.js (Employee module / HR-Admin management)
 *
 * Event delegation on document.body, same pattern as employee-app.js, so it
 * keeps working after page-loader.php swaps the .container innerHTML.
 */

const CONTROLLER = 'controllers/EmployeeController.php';

function qs(sel, ctx = document) {
    return ctx.querySelector(sel);
}

async function api(action, { method = 'GET', params = {}, body = null } = {}) {
    let url = `${CONTROLLER}?action=${encodeURIComponent(action)}`;
    if (method === 'GET' && params) {
        const usp = new URLSearchParams(params);
        if ([...usp].length) url += '&' + usp.toString();
    }

    const opts = { method, credentials: 'same-origin' };
    if (body) opts.body = body;

    const res = await fetch(url, opts);
    if (res.status === 401 || res.status === 403) {
        const data = await res.json().catch(() => ({}));
        if (data.redirect) window.location.href = data.redirect;
        throw new Error(data.message || 'Unauthorized');
    }
    return res.json();
}

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function fmtDate(d) {
    if (!d || d === '0000-00-00') return '—';
    const dt = new Date(d);
    if (isNaN(dt.getTime())) return '—';
    return dt.toLocaleDateString();
}

function showAlert(container, message, type = 'success') {
    if (!container) return;
    container.textContent = message;
    container.className = `alert alert-${type}`;
    container.style.display = 'block';
    setTimeout(() => { container.style.display = 'none'; }, 4000);
}

function toggleModal(modalEl, show) {
    if (!modalEl) return;
    modalEl.classList.toggle('open', show);
}

function reportAsyncError(err) {
    console.error('Document Requests error:', err);
    alert(err?.message || 'Something went wrong. Please try again.');
}

let currentRequests = [];

async function loadDocumentRequests(root) {
    const tbody = qs('#document-requests-table-body', root);
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="9">Loading…</td></tr>';

    const statusFilter = qs('#dr-filter-status', root)?.value || '';
    const archivedFilter = qs('#dr-filter-archived', root)?.value ?? '0';

    const res = await api('get_document_requests', {
        params: { request_status: statusFilter, archived: archivedFilter },
    });

    if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="9">${esc(res.message || 'Failed to load document requests.')}</td></tr>`;
        return;
    }

    currentRequests = res.data;

    if (!currentRequests.length) {
        tbody.innerHTML = '<tr><td colspan="9">No document requests found.</td></tr>';
        return;
    }

    tbody.innerHTML = currentRequests.map((r, i) => `
        <tr>
            <td>${esc(r.employee_code || '')} — ${esc(((r.first_name || '') + ' ' + (r.last_name || '')).trim() || '—')}</td>
            <td>${esc(r.document_type)}</td>
            <td>${fmtDate(r.created_at)}</td>
            <td>${fmtDate(r.required_by)}</td>
            <td>${esc(r.priority || '—')}</td>
            <td><span class="status-badge">${esc(r.request_status || '—')}</span></td>
            <td>${esc(r.assigned_to || '—')}</td>
            <td>${esc(r.verified == 1 ? 'Verified' : '—')}</td>
            <td><button type="button" class="btn-secondary btn-process-request" data-index="${i}">Process</button></td>
        </tr>
    `).join('');
}

async function initDocumentRequests(root) {
    const tbody = qs('#document-requests-table-body', root);
    if (!tbody) return; // not this page

    await loadDocumentRequests(root);

    qs('#dr-filter-status', root)?.addEventListener('change', () => loadDocumentRequests(root).catch(reportAsyncError));
    qs('#dr-filter-archived', root)?.addEventListener('change', () => loadDocumentRequests(root).catch(reportAsyncError));
}

function openProcessRequestModal(request) {
    const modal = qs('#process-request-modal');
    if (!modal || !request) return;

    const summary = qs('#process-request-summary', modal);
    summary.innerHTML = `
        <dl class="view-detail-list">
            <dt>Employee</dt><dd>${esc(((request.first_name || '') + ' ' + (request.last_name || '')).trim() || '—')}</dd>
            <dt>Document Type</dt><dd>${esc(request.document_type)}</dd>
            <dt>Date Requested</dt><dd>${esc(fmtDate(request.created_at))}</dd>
            <dt>Notes from Employee</dt><dd>${esc(request.notes || '—')}</dd>
        </dl>`;

    const form = qs('#process-request-form', modal);
    form.reset();
    qs('[name="request_id"]', form).value = request.request_id;

    toggleModal(modal, true);
}

async function submitProcessRequestForm(form) {
    const alertBox = qs('#process-request-message');

    const fd = new FormData(form);
    // Checkboxes only appear in FormData when checked — normalize so the
    // server can tell "explicitly checked" apart from "leave unchanged".
    if (!form.querySelector('[name="verified"]').checked) fd.delete('verified');
    if (!form.querySelector('[name="archived"]').checked) fd.delete('archived');

    const res = await api('update_document_request', { method: 'POST', body: fd });
    showAlert(alertBox, res.message, res.success ? 'success' : 'error');
    if (res.success) {
        toggleModal(qs('#process-request-modal'), false);
        const root = document.querySelector('.container');
        if (root) await loadDocumentRequests(root);
    }
}

// ─────────────────────────── Wiring ───────────────────────────

window.addEventListener('page:loaded', function (e) {
    if (e.detail?.page !== 'document-requests') return;
    const root = document.querySelector('.container');
    if (root) initDocumentRequests(root).catch(reportAsyncError);
});

document.body.addEventListener('click', function (e) {
    const processBtn = e.target.closest('.btn-process-request');
    if (processBtn) {
        const request = currentRequests[Number(processBtn.dataset.index)];
        if (request) openProcessRequestModal(request);
        return;
    }

    if (e.target.closest('[data-modal-close]')) {
        const modal = e.target.closest('.modal');
        if (modal) toggleModal(modal, false);
        return;
    }
});

document.body.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.id !== 'process-request-form') return;

    e.preventDefault();
    submitProcessRequestForm(form).catch(reportAsyncError);
});
