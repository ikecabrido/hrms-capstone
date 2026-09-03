/**
 * employee-app.js
 *
 * All interactive logic for the Employee Management module pages.
 * Uses event delegation on document.body so it keeps working after
 * page-loader.php swaps the .container innerHTML (fragment-injected
 * <script> tags in page HTML never execute, so all logic must live
 * here as a real, imported module instead).
 */

// NOTE: fetch() resolves relative URLs against document.location, not this
// module file's own URL. The page is always served at /modules/employee/index.php
// (history.pushState only changes the query string, never the path), so the
// correct base directory is /modules/employee/ — no leading '../'.
const CONTROLLER = 'controllers/EmployeeController.php';

function qs(sel, ctx = document) {
    return ctx.querySelector(sel);
}
function qsa(sel, ctx = document) {
    return Array.from(ctx.querySelectorAll(sel));
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

function toFormData(obj) {
    const fd = new FormData();
    Object.entries(obj).forEach(([k, v]) => fd.append(k, v ?? ''));
    return fd;
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

// Shared handler for promise rejections from fire-and-forget async calls
// (event handlers can't be awaited), so failures are visible instead of
// silently becoming unhandled promise rejections in the console.
function reportAsyncError(err) {
    console.error('Employee module error:', err);
    alert(err?.message || 'Something went wrong. Please try again.');
}

// ─────────────────────────── Employee Database page ───────────────────────────

async function initEmployeeDatabase(root) {
    const tbody = qs('#employee-table-body', root);
    if (!tbody) return;

    const deptFilter = qs('#filter-department', root);
    const posFilter = qs('#filter-position', root);
    const statusFilter = qs('#filter-status', root);
    const keywordInput = qs('#filter-keyword', root);
    const resetBtn = qs('#filter-reset-btn', root);

    const paginationBar = qs('#employee-pagination', root);
    const paginationSummary = qs('#pagination-summary', root);
    const paginationPages = qs('#pagination-pages', root);
    const prevBtn = qs('#pagination-prev', root);
    const nextBtn = qs('#pagination-next', root);
    const PAGE_SIZE = 10;

    let allResults = [];
    let currentPage = 1;

    function renderPage() {
        const totalItems = allResults.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * PAGE_SIZE;
        const pageItems = allResults.slice(start, start + PAGE_SIZE);

        if (!totalItems) {
            tbody.innerHTML = '<tr><td colspan="7">No employees found.</td></tr>';
            paginationBar.style.display = 'none';
            return;
        }

        tbody.innerHTML = '';
        pageItems.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'employee-row';
            const statusClass = 'status-' + (e.employment_status || '').toLowerCase().replace(/\s+/g, '-');
            tr.innerHTML = `
                <td>${esc(e.employee_code)}</td>
                <td class="employee-name-cell">${esc([e.first_name, e.middle_name, e.last_name].filter(Boolean).join(' '))}</td>
                <td>${esc(e.department_name || '—')}</td>
                <td>${esc(e.position_name || '—')}</td>
                <td><span class="status-badge ${statusClass}">${esc((e.employment_status || 'Unspecified').toUpperCase())}</span></td>
                <td>${fmtDate(e.hire_date)}</td>
                <td>
                    <button type="button" class="btn-view-employee" data-id="${e.employee_id}">View</button>
                    <button type="button" class="btn-edit-employee" data-id="${e.employee_id}">Edit</button>
                    <button type="button" class="btn-archive-employee" data-id="${e.employee_id}">Archive</button>
                </td>`;
            tbody.appendChild(tr);
        });

        paginationBar.style.display = totalItems > PAGE_SIZE ? 'flex' : 'none';
        const rangeStart = start + 1;
        const rangeEnd = Math.min(start + PAGE_SIZE, totalItems);
        paginationSummary.textContent = `Showing ${rangeStart}–${rangeEnd} of ${totalItems} employees`;
        paginationPages.textContent = `Page ${currentPage} of ${totalPages}`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    async function loadTable() {
        tbody.innerHTML = '<tr><td colspan="7">Loading…</td></tr>';
        paginationBar.style.display = 'none';
        const params = {
            keyword: keywordInput?.value || '',
            department_id: deptFilter?.value || '',
            position_id: posFilter?.value || '',
            status: statusFilter?.value || '',
        };
        const res = await api('search_employees', { params });
        if (!res.success) {
            tbody.innerHTML = `<tr><td colspan="7">${esc(res.message || 'Failed to load employees.')}</td></tr>`;
            return;
        }
        allResults = res.data;
        currentPage = 1; // any new filter/search resets to page 1
        renderPage();
    }

    root.dataset.reload = 'reload'; // marker
    root._loadEmployeeTable = loadTable;

    [deptFilter, posFilter, statusFilter].forEach(el => el?.addEventListener('change', loadTable));
    keywordInput?.addEventListener('input', debounce(loadTable, 350));
    resetBtn?.addEventListener('click', () => {
        if (deptFilter) deptFilter.value = '';
        if (posFilter) posFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (keywordInput) keywordInput.value = '';
        loadTable();
    });
    prevBtn?.addEventListener('click', () => { currentPage--; renderPage(); });
    nextBtn?.addEventListener('click', () => { currentPage++; renderPage(); });

    await loadTable();
}

function debounce(fn, delay) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

// ─────────────────────────── Add / Edit Employee modal ───────────────────────────

async function handleDepartmentChange(select) {
    const form = select.closest('form');
    if (!form) return;
    const positionSelect = qs('[name="position_id"]', form);
    if (!positionSelect) return;

    const departmentId = select.value;
    positionSelect.innerHTML = '<option value="">Select Position</option>';
    positionSelect.disabled = true;

    if (!departmentId) return;

    const res = await api('get_positions_by_department', { params: { department_id: departmentId } });
    if (res.success) {
        res.data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.position_id;
            opt.textContent = p.position_name;
            positionSelect.appendChild(opt);
        });
        positionSelect.disabled = false;
    }
}

async function submitAddEmployeeForm(form) {
    const alertBox = qs('#add-employee-message', form.closest('.modal, body'));
    const res = await api('create_employee', { method: 'POST', body: new FormData(form) });
    showAlert(alertBox, res.message, res.success ? 'success' : 'error');
    if (res.success) {
        form.reset();
        toggleModal(qs('#add-employee-modal'), false);
        document.querySelector('.container')?._loadEmployeeTable?.();
    }
}

async function openViewEmployeeModal(employeeId) {
    const modal = qs('#view-employee-modal');
    const body = qs('#view-employee-body', modal);
    if (!modal || !body) return;

    body.innerHTML = '<p class="empty-item">Loading…</p>';
    toggleModal(modal, true);

    const res = await api('get_employee_details', { params: { employee_id: employeeId } });
    if (!res.success) {
        body.innerHTML = `<p class="empty-item">${esc(res.message || 'Failed to load employee.')}</p>`;
        return;
    }

    const e = res.employee;
    const fullName = [e.first_name, e.middle_name, e.last_name, e.suffix].filter(Boolean).join(' ');
    const rows = [
        ['Employee Code', e.employee_code],
        ['Full Name', fullName],
        ['Email', e.email],
        ['Mobile Number', e.mobile_no || '—'],
        ['Department', e.department_name || '—'],
        ['Position', e.position_name || '—'],
        ['Employment Status', e.employment_status || '—'],
        ['Employment Type', e.employment_type || '—'],
        ['Hire Date', fmtDate(e.hire_date)],
        ['Gender', e.gender || '—'],
        ['Civil Status', e.civil_status || '—'],
    ];

    body.innerHTML = `
        <dl class="view-detail-list">
            ${rows.map(([label, value]) => `
                <dt>${esc(label)}</dt>
                <dd>${esc(value ?? '—')}</dd>
            `).join('')}
        </dl>`;

    const renewBtn = qs('#btn-open-renew-contract', modal);
    if (renewBtn) renewBtn.dataset.id = employeeId;

    await loadContractSection(modal, employeeId);
}

async function loadContractSection(modal, employeeId) {
    const contractBox = qs('#view-employee-contract', modal);
    const historyBody = qs('#view-employee-contract-history', modal);
    if (!contractBox || !historyBody) return;

    contractBox.innerHTML = '<p class="empty-item">Loading…</p>';
    historyBody.innerHTML = '<tr><td colspan="5">Loading…</td></tr>';

    const res = await api('get_contract_info', { params: { employee_id: employeeId } });
    if (!res.success) {
        contractBox.innerHTML = `<p class="empty-item">${esc(res.message || 'Failed to load contract information.')}</p>`;
        historyBody.innerHTML = '<tr><td colspan="5">Failed to load.</td></tr>';
        return;
    }

    const c = res.current;
    if (!c) {
        contractBox.innerHTML = '<p class="empty-item">No contract on record yet. Use "Renew Contract" to add one.</p>';
    } else {
        const rows = [
            ['Current Contract Start', fmtDate(c.contract_start_date)],
            ['Current Contract End', fmtDate(c.contract_end_date)],
            ['Employment Type', c.employment_type || '—'],
            ['Salary', c.salary != null ? Number(c.salary).toLocaleString() : '—'],
            ['Contract Status', c.renewal_status || '—'],
        ];
        contractBox.innerHTML = `
            <dl class="view-detail-list">
                ${rows.map(([label, value]) => `
                    <dt>${esc(label)}</dt>
                    <dd>${esc(value ?? '—')}</dd>
                `).join('')}
            </dl>`;
    }

    if (!res.history || !res.history.length) {
        historyBody.innerHTML = '<tr><td colspan="5">No renewal history yet.</td></tr>';
    } else {
        historyBody.innerHTML = res.history.map(h => `
            <tr>
                <td>${fmtDate(h.contract_start_date)}</td>
                <td>${fmtDate(h.contract_end_date)}</td>
                <td>${esc(h.employment_type || '—')}</td>
                <td>${h.salary != null ? esc(Number(h.salary).toLocaleString()) : '—'}</td>
                <td><span class="status-badge">${esc(h.renewal_status || '—')}</span></td>
            </tr>`).join('');
    }
}

function openRenewContractModal(employeeId) {
    const modal = qs('#renew-contract-modal');
    if (!modal || !employeeId) return;

    const form = qs('#renew-contract-form', modal);
    form.reset();
    qs('[name="employee_id"]', form).value = employeeId;

    const currentBox = qs('#renew-contract-current', modal);
    currentBox.innerHTML = '<p class="empty-item">Loading current contract…</p>';

    api('get_contract_info', { params: { employee_id: employeeId } }).then(res => {
        if (!res.success) {
            currentBox.innerHTML = `<p class="empty-item">${esc(res.message || 'Failed to load current contract.')}</p>`;
            return;
        }
        const c = res.current;
        currentBox.innerHTML = c
            ? `<dl class="view-detail-list">
                   <dt>Current Contract End</dt><dd>${esc(fmtDate(c.contract_end_date))}</dd>
                   <dt>Current Status</dt><dd>${esc(c.renewal_status || '—')}</dd>
               </dl>`
            : '<p class="empty-item">No existing contract on record — this will be the first one.</p>';
    }).catch(reportAsyncError);

    toggleModal(modal, true);
}

async function submitRenewContractForm(form) {
    const alertBox = qs('#renew-contract-message', form.closest('.modal, body'));
    const employeeId = qs('[name="employee_id"]', form)?.value;
    const res = await api('renew_contract', { method: 'POST', body: new FormData(form) });
    showAlert(alertBox, res.message, res.success ? 'success' : 'error');
    if (res.success) {
        toggleModal(qs('#renew-contract-modal'), false);
        const viewModal = qs('#view-employee-modal');
        if (viewModal && employeeId) await loadContractSection(viewModal, employeeId);
        document.querySelector('.container')?._loadEmployeeTable?.();
    }
}

async function openEditEmployeeModal(employeeId) {
    const res = await api('get_employee_details', { params: { employee_id: employeeId } });
    if (!res.success) {
        alert(res.message || 'Failed to load employee.');
        return;
    }
    const modal = qs('#edit-employee-modal');
    if (!modal) return;

    const form = qs('#edit-employee-form', modal);
    const e = res.employee;

    // Populate every field (inputs, textareas, and selects) except department_id
    // and position_id, which need cascading handling below since the position
    // options are populated dynamically based on the chosen department.
    Object.keys(e).forEach(key => {
        if (key === 'department_id' || key === 'position_id') return;
        const field = qs(`[name="${key}"]`, form);
        if (field) field.value = e[key] ?? '';
    });

    const deptSelect = qs('[name="department_id"]', form);
    if (deptSelect) {
        deptSelect.value = e.department_id ?? '';
        await handleDepartmentChange(deptSelect);
        const posSelect = qs('[name="position_id"]', form);
        if (posSelect) posSelect.value = e.position_id ?? '';
    }

    toggleModal(modal, true);
}

async function submitEditEmployeeForm(form) {
    const alertBox = qs('#edit-employee-message', form.closest('.modal, body'));
    const res = await api('update_employee', { method: 'POST', body: new FormData(form) });
    showAlert(alertBox, res.message, res.success ? 'success' : 'error');
    if (res.success) {
        toggleModal(qs('#edit-employee-modal'), false);
        document.querySelector('.container')?._loadEmployeeTable?.();
    }
}

async function archiveEmployee(employeeId) {
    if (!confirm('Archive this employee? Their record will be hidden from the active list but not deleted.')) return;
    const res = await api('archive_employee', { method: 'POST', body: toFormData({ employee_id: employeeId }) });
    if (!res.success) alert(res.message || 'Failed to archive employee.');
    document.querySelector('.container')?._loadEmployeeTable?.();
}

// ─────────────────────────── Employee Profile (personal-information page) ───────────────────────────

async function initPersonalInformation(root) {
    const picker = qs('#profile-employee-picker', root);
    if (!picker) return;

    picker.addEventListener('change', () => loadProfile(root, picker.value));

    if (picker.value) await loadProfile(root, picker.value);
}

async function loadProfile(root, employeeId) {
    const panel = qs('#profile-panel', root);
    if (!panel) return; // not on the Personal Information page — nothing to update
    if (!employeeId) {
        panel.style.display = 'none';
        return;
    }

    const res = await api('get_employee_details', { params: { employee_id: employeeId } });
    if (!res.success) return;

    panel.style.display = '';
    panel.dataset.employeeId = employeeId;
    // Populate every employee_id hidden field within this panel first — covers
    // the inline "add" forms (dependents, education, skills, etc.) that aren't
    // touched by the fillForm() calls below, which only target the 3 main forms.
    qsa('[name="employee_id"]', panel).forEach(el => { el.value = employeeId; });

    const pi = res.profile.personal_information || {};
    const fb = res.profile.family_background || {};
    const gi = res.profile.government_ids || {};

    fillForm(qs('#personal-info-form', panel), { ...pi, employee_id: employeeId });
    fillForm(qs('#family-background-form', panel), { ...fb, employee_id: employeeId });
    fillForm(qs('#government-ids-form', panel), { ...gi, employee_id: employeeId });

    renderList(qs('#dependents-list', panel), res.profile.dependents, d =>
        `${esc(d.name)} — ${esc(d.relationship)} ${d.birth_date ? '(' + fmtDate(d.birth_date) + ')' : ''}`,
        'dependent_id', 'delete_dependent', employeeId);

    renderList(qs('#emergency-contacts-list', panel), res.profile.emergency_contacts, c =>
        `${esc(c.name)} — ${esc(c.relationship)} — ${esc(c.contact_number)}`,
        'contact_id', 'delete_emergency_contact', employeeId);

    renderList(qs('#education-list', panel), res.records.education, ed =>
        `${esc(ed.level)}: ${esc(ed.school_name)} ${ed.course ? '(' + esc(ed.course) + ')' : ''} ${ed.year_graduated ? '– ' + esc(ed.year_graduated) : ''}`,
        'education_id', 'delete_education', employeeId);

    renderList(qs('#certifications-list', panel), res.records.certifications, c =>
        `${esc(c.cert_name)} — ${esc(c.issuing_organization)} ${c.date_issued ? '(' + fmtDate(c.date_issued) + ')' : ''}`,
        'cert_id', 'delete_certification', employeeId);

    renderList(qs('#skills-list', panel), res.records.skills, s =>
        `${esc(s.skill_name)} — ${esc(s.proficiency)}`,
        'skill_id', 'delete_skill', employeeId);

    renderList(qs('#languages-list', panel), res.records.languages, l =>
        `${esc(l.language_name)} — ${esc(l.proficiency)}`,
        'language_id', 'delete_language', employeeId);

    renderList(qs('#work-experience-list', panel), res.records.work_experience, w =>
        `${esc(w.company_name)} — ${esc(w.position)} (${fmtDate(w.start_date)} – ${w.end_date ? fmtDate(w.end_date) : 'Present'})`,
        'work_exp_id', 'delete_work_experience', employeeId);
}

function fillForm(form, data) {
    if (!form) return;
    Object.keys(data).forEach(key => {
        const field = qs(`[name="${key}"]`, form);
        if (field) field.value = data[key] ?? '';
    });
}

function renderList(container, items, labelFn, idKey, deleteAction, employeeId) {
    if (!container) return;
    if (!items || !items.length) {
        container.innerHTML = '<li class="empty-item">None on record.</li>';
        return;
    }
    container.innerHTML = '';
    items.forEach(item => {
        const li = document.createElement('li');
        li.innerHTML = `<span>${labelFn(item)}</span>
            <button type="button" class="btn-delete-item" data-action="${deleteAction}" data-id-key="${idKey}"
                data-id="${item[idKey]}" data-employee-id="${employeeId}">&times;</button>`;
        container.appendChild(li);
    });
}

async function submitSubForm(form) {
    const action = form.dataset.action;
    if (!action) return;
    const alertBox = qs('.alert', form.parentElement) || qs('.alert', form);
    const res = await api(action, { method: 'POST', body: new FormData(form) });
    if (alertBox) showAlert(alertBox, res.message || (res.success ? 'Saved.' : 'Failed.'), res.success ? 'success' : 'error');
    if (res.success) {
        const employeeId = form.querySelector('[name="employee_id"]')?.value;
        const root = document.querySelector('.container');
        if (employeeId && root) {
            // add_requirement lives on the Documents page (#docs-panel); every
            // other sub-form here lives on Personal Information (#profile-panel).
            if (action === 'add_requirement') {
                await loadDocuments(root, employeeId);
            } else {
                await loadProfile(root, employeeId);
            }
        }
        if (form.dataset.resetOnSuccess !== 'false') {
            const preserveEmployee = form.querySelector('[name="employee_id"]')?.value;
            form.reset();
            const empField = form.querySelector('[name="employee_id"]');
            if (empField) empField.value = preserveEmployee;
        }
    }
}

async function deleteSubItem(btn) {
    if (!confirm('Remove this record?')) return;
    const action = btn.dataset.action;
    const idKey = btn.dataset.idKey;
    const id = btn.dataset.id;
    const employeeId = btn.dataset.employeeId;

    const res = await api(action, {
        method: 'POST',
        body: toFormData({ [idKey]: id, employee_id: employeeId }),
    });
    if (!res.success) {
        alert(res.message || 'Failed to remove record.');
        return;
    }
    const root = document.querySelector('.container');
    if (!root) return;

    // Route to the loader matching where this delete actually happened —
    // document/requirement deletes live on the Documents page (#docs-panel),
    // everything else lives on the Personal Information page (#profile-panel).
    if (action === 'delete_document' || action === 'delete_requirement') {
        await loadDocuments(root, employeeId);
    } else {
        await loadProfile(root, employeeId);
    }
}

// ─────────────────────────── Documents Management page ───────────────────────────

async function initDocumentsManagement(root) {
    const picker = qs('#docs-employee-picker', root);
    if (!picker) return;

    picker.addEventListener('change', () => loadDocuments(root, picker.value));

    // Cosmetic: show the chosen filename in the upload drop area
    const fileInput = qs('#upload-document-file', root);
    const fileLabel = qs('#upload-file-filename', root);
    fileInput?.addEventListener('change', () => {
        if (fileLabel) {
            fileLabel.textContent = fileInput.files?.[0]?.name || 'Choose a file to upload (PDF, DOC, DOCX, JPG, PNG)';
        }
    });

    if (picker.value) await loadDocuments(root, picker.value);
}

async function loadDocuments(root, employeeId) {
    const panel = qs('#docs-panel', root);
    if (!panel) return; // not on the Documents Management page — nothing to update
    if (!employeeId) {
        panel.style.display = 'none';
        return;
    }
    panel.style.display = '';
    panel.dataset.employeeId = employeeId;
    // Populate every employee_id hidden field within this panel (upload form,
    // requirements form) — these are what FormData actually reads on submit.
    qsa('[name="employee_id"]', panel).forEach(el => { el.value = employeeId; });

    const [empRes, docsRes, reqRes] = await Promise.all([
        api('get_employee_details', { params: { employee_id: employeeId } }),
        api('get_documents', { params: { employee_id: employeeId } }),
        api('get_requirements', { params: { employee_id: employeeId } }),
    ]);

    const documents = docsRes.success ? docsRes.data : [];
    const requirements = reqRes.success ? reqRes.data : [];
    const completedCount = requirements.filter(r => r.status === 'Submitted').length;
    const totalReq = requirements.length;
    const completionPct = totalReq > 0 ? Math.round((completedCount / totalReq) * 100) : 0;

    // ── Employee summary card ──
    const summaryCard = qs('#employee-summary-card', panel);
    if (summaryCard) {
        if (empRes.success) {
            const e = empRes.employee;
            const fullName = [e.first_name, e.middle_name, e.last_name].filter(Boolean).join(' ');
            summaryCard.innerHTML = `
                <div class="employee-summary-header">
                    <div class="employee-summary-avatar">${esc((e.first_name || '?').charAt(0))}</div>
                    <div>
                        <h3 class="employee-summary-name">${esc(fullName)}</h3>
                        <span class="employee-summary-code">${esc(e.employee_code)}</span>
                    </div>
                </div>
                <div class="employee-summary-meta">
                    <span>${esc(e.department_name || '—')} • ${esc(e.position_name || '—')}</span>
                </div>
                <div class="employee-summary-stats">
                    <div><strong>${documents.length}</strong><span>Documents</span></div>
                    <div><strong>${completedCount} / ${totalReq}</strong><span>Requirements</span></div>
                    <div><strong>${completionPct}%</strong><span>Completion</span></div>
                </div>`;
        } else {
            summaryCard.innerHTML = '';
        }
    }

    // ── Uploaded documents (document cards) ──
    const docsList = qs('#documents-list', panel);
    if (docsList) {
        if (!documents.length) {
            docsList.innerHTML = '<li class="empty-item">No documents uploaded.</li>';
        } else {
            docsList.innerHTML = '';
            documents.forEach(d => {
                const li = document.createElement('li');
                li.className = 'document-card';
                li.innerHTML = `
                    <div class="document-card-main">
                        <div class="document-card-name">${esc(d.document_name)}</div>
                        <div class="document-card-meta">
                            <span class="badge badge-muted">${esc(d.category || 'Other')}</span>
                            <span>Uploaded: ${fmtDate(d.created_at)}</span>
                        </div>
                    </div>
                    <div class="document-card-actions">
                        <a href="${esc(d.file_path)}" target="_blank" rel="noopener" class="btn-secondary btn-small">View</a>
                        <a href="${esc(d.file_path)}" download="${esc(d.file_name)}" class="btn-secondary btn-small">Download</a>
                        <button type="button" class="btn-delete-item" data-action="delete_document" data-id-key="document_id"
                            data-id="${d.document_id}" data-employee-id="${employeeId}" title="Delete document">&times;</button>
                    </div>`;
                docsList.appendChild(li);
            });
        }
    }

    // ── Requirements checklist + progress bar ──
    const progressLabel = qs('#requirements-progress-label', panel);
    const progressFill = qs('#requirements-progress-fill', panel);
    if (progressLabel) progressLabel.textContent = `${completedCount} / ${totalReq} Complete`;
    if (progressFill) progressFill.style.width = `${completionPct}%`;

    const reqList = qs('#requirements-list', panel);
    if (reqList) {
        if (!requirements.length) {
            reqList.innerHTML = '<li class="empty-item">No requirements tracked.</li>';
        } else {
            reqList.innerHTML = '';
            requirements.forEach(r => {
                const li = document.createElement('li');
                const isSubmitted = r.status === 'Submitted';
                li.innerHTML = `
                    <span>
                        <i class="fa-solid ${isSubmitted ? 'fa-circle-check' : 'fa-circle-xmark'} requirement-icon requirement-icon-${isSubmitted ? 'ok' : 'missing'}"></i>
                        ${esc(r.requirement_name)} —
                        <span class="status-badge status-${esc((r.status || '').toLowerCase().replace(/\s+/g, '-'))}">${esc(r.status)}</span>
                    </span>
                    <button type="button" class="btn-delete-item" data-action="delete_requirement" data-id-key="requirement_id"
                        data-id="${r.requirement_id}" data-employee-id="${employeeId}">&times;</button>`;
                reqList.appendChild(li);
            });
        }
    }
}

async function submitUploadDocumentForm(form) {
    const alertBox = qs('.alert', form.parentElement) || qs('.alert', form);
    const res = await api('upload_document', { method: 'POST', body: new FormData(form) });
    if (alertBox) showAlert(alertBox, res.message, res.success ? 'success' : 'error');
    if (res.success) {
        const employeeId = form.querySelector('[name="employee_id"]')?.value;
        form.reset();
        const empField = form.querySelector('[name="employee_id"]');
        if (empField) empField.value = employeeId;
        // form.reset() clears the file input but doesn't fire 'change', so the
        // drop-area label would otherwise keep showing the old filename.
        const fileLabel = qs('#upload-file-filename', form);
        if (fileLabel) fileLabel.textContent = 'Choose a file to upload (PDF, DOC, DOCX, JPG, PNG)';
        const root = document.querySelector('.container');
        if (root && employeeId) await loadDocuments(root, employeeId);
    }
}

// ─────────────────────────── Employee History page ───────────────────────────

function changeTypeBadgeClass(type) {
    const map = {
        'Document Uploaded': 'history-badge-green',
        'Employee Restored': 'history-badge-green',
        'Personal Info Update': 'history-badge-blue',
        'Employee Created': 'history-badge-teal',
        'Field Update': 'history-badge-neutral',
        'Employee Archived': 'history-badge-red',
    };
    return map[type] || 'history-badge-neutral';
}

async function initEmployeeHistory(root) {
    const picker = qs('#history-employee-picker', root);
    const changeTypeFilter = qs('#history-changetype-filter', root);
    const dateFrom = qs('#history-date-from', root);
    const dateTo = qs('#history-date-to', root);
    const resetBtn = qs('#history-reset-btn', root);
    const tbody = qs('#history-table-body', root);
    if (!tbody) return;

    const paginationBar = qs('#history-pagination', root);
    const paginationSummary = qs('#history-pagination-summary', root);
    const paginationPages = qs('#history-pagination-pages', root);
    const prevBtn = qs('#history-pagination-prev', root);
    const nextBtn = qs('#history-pagination-next', root);
    const PAGE_SIZE = 10;

    let allRecords = [];
    let filteredRecords = [];
    let currentPage = 1;

    function updateSummaryCards(records) {
        const recentChanges = records.length;
        const documentsUploaded = records.filter(h => h.change_type === 'Document Uploaded').length;
        const employeesAffected = new Set(records.map(h => h.employee_id).filter(Boolean)).size;
        const setText = (id, val) => { const el = qs('#' + id, root); if (el) el.textContent = val; };
        setText('stat-recent-changes', recentChanges);
        setText('stat-documents-uploaded', documentsUploaded);
        setText('stat-employees-affected', employeesAffected);
    }

    function applyFilters() {
        const typeVal = changeTypeFilter?.value || '';
        const fromVal = dateFrom?.value || '';
        const toVal = dateTo?.value || '';

        filteredRecords = allRecords.filter(h => {
            if (typeVal && h.change_type !== typeVal) return false;
            if (fromVal || toVal) {
                const recordDate = (h.created_at || '').slice(0, 10); // YYYY-MM-DD
                if (fromVal && recordDate < fromVal) return false;
                if (toVal && recordDate > toVal) return false;
            }
            return true;
        });
        currentPage = 1;
        renderPage();
    }

    function renderPage() {
        const totalItems = filteredRecords.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        if (!totalItems) {
            tbody.innerHTML = '<tr><td colspan="6">No history records found.</td></tr>';
            paginationBar.style.display = 'none';
            return;
        }

        const start = (currentPage - 1) * PAGE_SIZE;
        const pageItems = filteredRecords.slice(start, start + PAGE_SIZE);

        tbody.innerHTML = '';
        pageItems.forEach((h, idx) => {
            const globalIndex = start + idx;
            const tr = document.createElement('tr');
            const who = h.first_name ? `${esc(h.first_name)} ${esc(h.last_name)}` : (h.employee_id ?? '—');
            const oldNew = `${esc(h.old_value ?? '—')} → ${esc(h.new_value ?? '—')}`;
            tr.innerHTML = `
                <td>${fmtDate(h.created_at)}</td>
                <td>${esc(who)}</td>
                <td>
                    <span class="history-badge ${changeTypeBadgeClass(h.change_type)}">${esc(h.change_type)}</span>
                    ${h.field_name ? `<div class="history-field-label">${esc(h.field_name)}</div>` : ''}
                </td>
                <td class="history-value-cell" title="${esc(h.old_value ?? '')} → ${esc(h.new_value ?? '')}">${oldNew}</td>
                <td>${esc(h.updated_by || '—')}</td>
                <td><button type="button" class="btn-secondary btn-small btn-view-history" data-index="${globalIndex}">View</button></td>`;
            tbody.appendChild(tr);
        });

        paginationBar.style.display = totalItems > PAGE_SIZE ? 'flex' : 'none';
        const rangeStart = start + 1;
        const rangeEnd = Math.min(start + PAGE_SIZE, totalItems);
        paginationSummary.textContent = `Showing ${rangeStart}–${rangeEnd} of ${totalItems} changes`;
        paginationPages.textContent = `Page ${currentPage} of ${totalPages}`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    async function loadHistory() {
        tbody.innerHTML = '<tr><td colspan="6">Loading…</td></tr>';
        paginationBar.style.display = 'none';
        const params = picker?.value ? { employee_id: picker.value } : { limit: 50 };
        const res = await api('get_history', { params });
        allRecords = res.success ? res.data : [];
        updateSummaryCards(allRecords);
        applyFilters();
    }

    // Expose the filtered record list to the delegated click handler so the
    // View button can open the detail modal without a second network call.
    root._historyRecords = () => filteredRecords;

    picker?.addEventListener('change', loadHistory);
    changeTypeFilter?.addEventListener('change', applyFilters);
    dateFrom?.addEventListener('change', applyFilters);
    dateTo?.addEventListener('change', applyFilters);
    resetBtn?.addEventListener('click', () => {
        if (changeTypeFilter) changeTypeFilter.value = '';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        applyFilters();
    });
    prevBtn?.addEventListener('click', () => { currentPage--; renderPage(); });
    nextBtn?.addEventListener('click', () => { currentPage++; renderPage(); });

    await loadHistory();
}

function openHistoryDetailModal(record) {
    const modal = qs('#history-detail-modal');
    const body = qs('#history-detail-body', modal);
    if (!modal || !body) return;

    const who = record.first_name ? `${record.first_name} ${record.last_name} (${record.employee_code || ''})` : (record.employee_id ?? '—');
    const rows = [
        ['Employee', who],
        ['Change Type', record.change_type],
        ['Field', record.field_name || '—'],
        ['Old Value', record.old_value ?? '—'],
        ['New Value', record.new_value ?? '—'],
        ['Updated By', record.updated_by || '—'],
        ['Date/Time', record.created_at || '—'],
    ];
    body.innerHTML = `
        <dl class="view-detail-list">
            ${rows.map(([label, value]) => `
                <dt>${esc(label)}</dt>
                <dd>${esc(value)}</dd>
            `).join('')}
        </dl>`;
    toggleModal(modal, true);
}


// ─────────────────────────── Dashboard page ───────────────────────────
// Dashboard is fully server-rendered in PHP (no AJAX needed on load).

// ─────────────────────────── Wiring ───────────────────────────

function initPageByName(page) {
    const root = document.querySelector('.container');
    if (!root) return;

    const handlers = {
        'employee-database': initEmployeeDatabase,
        'personal-information': initPersonalInformation,
        'documents-management': initDocumentsManagement,
        'employee-history': initEmployeeHistory,
    };

    const handler = handlers[page];
    if (!handler) return;

    // Surface any rejection (e.g. an auth/authorization error without a
    // 'redirect', or a network failure) instead of letting it disappear as an
    // unhandled promise rejection with no visible feedback to the user.
    Promise.resolve(handler(root)).catch(reportAsyncError);
}

// Delegated click handling (works across AJAX-swapped fragments)
document.body.addEventListener('click', function (e) {
    const viewHistoryBtn = e.target.closest('.btn-view-history');
    if (viewHistoryBtn) {
        const root = document.querySelector('.container');
        const records = root?._historyRecords?.() || [];
        const record = records[Number(viewHistoryBtn.dataset.index)];
        if (record) openHistoryDetailModal(record);
        return;
    }

    const viewBtn = e.target.closest('.btn-view-employee');
    if (viewBtn) {
        openViewEmployeeModal(viewBtn.dataset.id).catch(reportAsyncError);
        return;
    }

    const editBtn = e.target.closest('.btn-edit-employee');
    if (editBtn) {
        openEditEmployeeModal(editBtn.dataset.id).catch(reportAsyncError);
        return;
    }

    const archiveBtn = e.target.closest('.btn-archive-employee');
    if (archiveBtn) {
        archiveEmployee(archiveBtn.dataset.id).catch(reportAsyncError);
        return;
    }

    const renewBtn = e.target.closest('#btn-open-renew-contract');
    if (renewBtn) {
        openRenewContractModal(renewBtn.dataset.id);
        return;
    }

    const deleteItemBtn = e.target.closest('.btn-delete-item');
    if (deleteItemBtn) {
        deleteSubItem(deleteItemBtn).catch(reportAsyncError);
        return;
    }

    const modalOpen = e.target.closest('[data-modal-open]');
    if (modalOpen) {
        toggleModal(document.getElementById(modalOpen.dataset.modalOpen), true);
        return;
    }

    const modalClose = e.target.closest('[data-modal-close]');
    if (modalClose) {
        toggleModal(modalClose.closest('.modal'), false);
        return;
    }
});

// Delegated change handling for department -> position cascading selects
document.body.addEventListener('change', function (e) {
    if (e.target.matches('[name="department_id"]') && e.target.closest('form')?.dataset.skip !== undefined) {
        handleDepartmentChange(e.target);
    }
});

// Delegated submit handling for module forms explicitly marked data-skip
// (these are handled here instead of the generic initForms() in page-init.js)
document.body.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.skip === undefined) return; // let generic initForms handle non-module forms

    e.preventDefault();

    if (form.id === 'add-employee-form') return submitAddEmployeeForm(form).catch(reportAsyncError);
    if (form.id === 'edit-employee-form') return submitEditEmployeeForm(form).catch(reportAsyncError);
    if (form.id === 'upload-document-form') return submitUploadDocumentForm(form).catch(reportAsyncError);
    if (form.id === 'renew-contract-form') return submitRenewContractForm(form).catch(reportAsyncError);
    if (form.dataset.action) return submitSubForm(form).catch(reportAsyncError);
});

// Re-init whenever a page fragment is loaded (initial load + AJAX nav)
window.addEventListener('page:loaded', function (e) {
    initPageByName(e.detail?.page);
});
