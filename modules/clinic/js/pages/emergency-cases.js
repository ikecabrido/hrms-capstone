function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
}

function resolveEmergencyDataUrl(fileName) {
    var base = document.currentScript && document.currentScript.src
        ? document.currentScript.src.replace(/\/js\/pages\/[^/]+\.js.*$/, '/' + fileName)
        : null;
    if (base) return base;
    var pathname = location.pathname;
    var idx = pathname.lastIndexOf('/modules/clinic/');
    if (idx >= 0) return pathname.slice(0, idx) + '/modules/clinic/' + fileName;
    return fileName;
}

function showEmergencyMessage(message, type = 'success') {
    const box = document.querySelector('#emergencyCaseMessage');
    if (!box) return;
    box.textContent = message;
    box.className = 'module-message ' + (type === 'error' ? 'error' : 'success');
    box.hidden = false;
}

function clearEmergencyMessage() {
    const box = document.querySelector('#emergencyCaseMessage');
    if (!box) return;
    box.textContent = '';
    box.hidden = true;
    box.className = 'module-message';
}

async function emergencyRequest(params, method = 'GET') {
    const queryParams = new URLSearchParams(params);
    const endpoint = resolveEmergencyDataUrl('emergency-cases-data.php');
    const url = method === 'GET' ? `${endpoint}?${queryParams.toString()}` : endpoint;
    const options = {
        method,
        credentials: 'same-origin',
        cache: 'no-store',
    };

    if (method === 'POST') {
        options.headers = { 'Content-Type': 'application/json' };
        options.body = JSON.stringify(params);
    }

    const response = await fetch(url, options);
    const rawText = await response.text();
    let data;
    try {
        data = JSON.parse(rawText);
    } catch (parseErr) {
        const preview = (rawText || '').trim().slice(0, 500);
        console.error('[JSON-GATEKEEPER-V2] Non-JSON response from server. First 500 chars:', preview);
        const looksLikeHtml = (rawText || '').trim().startsWith('<');
        const friendlyMsg = looksLikeHtml
            ? 'Temporary server issue — please refresh the page. If the problem persists, contact your administrator or check the server error log.'
            : (parseErr.message || 'Invalid response from server.');
        throw new Error(friendlyMsg);
    }
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Request could not be completed.');
    }
    return data;
}

function formatDateTime(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
}

function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString([], { dateStyle: 'medium' });
}

function getSeverityBadgeClass(severity) {
    const value = String(severity || '').toLowerCase();
    if (value.includes('critical')) return 'danger';
    if (value.includes('high')) return 'warning';
    if (value.includes('medium')) return 'neutral';
    if (value.includes('low')) return 'success';
    if (value.includes('minor')) return 'info';
    return 'neutral';
}

function getStatusBadgeClass(status) {
    const value = String(status || '').toLowerCase();
    if (value.includes('closed')) return 'neutral';
    if (value.includes('resolved')) return 'success';
    if (value.includes('transferred')) return 'warning';
    if (value.includes('open')) return 'danger';
    if (value.includes('active')) return 'primary';
    return 'neutral';
}

function buildEmergencyCaseRow(item) {
    return `
        <tr data-case-id="${escapeHtml(item.case_id)}">
            <td>${escapeHtml(item.case_id)}</td>
            <td>
                <div class="case-patient-meta">
                    <strong>${escapeHtml(item.patient_name || 'Unknown patient')}</strong>
                    <span>${escapeHtml(item.employee_id ? `Employee ID: ${item.employee_id}` : 'Patient: ' + (item.patient_id || 'N/A'))}</span>
                </div>
            </td>
            <td>${escapeHtml(item.employee_id || 'N/A')}</td>
            <td>${escapeHtml(item.department || 'N/A')}</td>
            <td>${escapeHtml(item.position || 'N/A')}</td>
            <td>${escapeHtml(item.incident_type || 'Other')}</td>
            <td>${escapeHtml(formatDateTime(item.incident_date))}</td>
            <td><span class="state-badge ${getSeverityBadgeClass(item.severity_level)}">${escapeHtml(item.severity_level || 'Medium')}</span></td>
            <td><span class="state-badge ${getStatusBadgeClass(item.case_status)}">${escapeHtml(item.case_status || 'Active')}</span></td>
            <td>${escapeHtml(item.attending_staff || 'N/A')}</td>
            <td>
                <div class="table-actions">
                    <button type="button" class="tiny-btn view-case" data-case-id="${escapeHtml(item.case_id)}">View</button>
                    <button type="button" class="tiny-btn edit-case" data-case-id="${escapeHtml(item.case_id)}">Edit</button>
                    <button type="button" class="tiny-btn danger close-case-btn" data-case-id="${escapeHtml(item.case_id)}">Close</button>
                    <button type="button" class="tiny-btn danger delete-case-btn" data-case-id="${escapeHtml(item.case_id)}">Delete</button>
                </div>
            </td>
        </tr>
    `;
}

function renderEmergencyCases(items) {
    const tbody = document.querySelector('#emergencyCaseTableBody');
    if (!tbody) return;

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="11" class="empty-state"><p>No emergency cases match the current filters.</p></td></tr>';
        return;
    }

    tbody.innerHTML = items.map(buildEmergencyCaseRow).join('');

    document.querySelectorAll('.view-case').forEach(button => {
        button.addEventListener('click', () => loadCaseForView(button.dataset.caseId));
    });

    document.querySelectorAll('.edit-case').forEach(button => {
        button.addEventListener('click', () => loadCaseForEdit(button.dataset.caseId));
    });

    document.querySelectorAll('.close-case-btn').forEach(button => {
        button.addEventListener('click', () => closeCaseRecord(button.dataset.caseId));
    });

    document.querySelectorAll('.delete-case-btn').forEach(button => {
        button.addEventListener('click', () => deleteCaseRecord(button.dataset.caseId));
    });
}

function renderEmergencySummary(items) {
    const total = document.querySelector('#emergencyTotalCases');
    const active = document.querySelector('#emergencyActiveCases');
    const critical = document.querySelector('#emergencyCriticalCases');
    const closed = document.querySelector('#emergencyClosedCases');

    if (!total || !active || !critical || !closed) return;

    const normalized = Array.isArray(items) ? items : [];
    total.textContent = String(normalized.length);
    active.textContent = String(normalized.filter(item => String(item.case_status || '').toLowerCase() === 'active' || String(item.case_status || '').toLowerCase() === 'open').length);
    critical.textContent = String(normalized.filter(item => String(item.severity_level || '').toLowerCase() === 'critical').length);
    closed.textContent = String(normalized.filter(item => String(item.case_status || '').toLowerCase() === 'closed').length);
}

async function loadEmergencyCases() {
    const search = document.querySelector('#emergencyCaseSearch')?.value.trim() || '';
    const status = document.querySelector('#emergencyStatusFilter')?.value || '';
    const severity = document.querySelector('#emergencySeverityFilter')?.value || '';
    const type = document.querySelector('#emergencyTypeFilter')?.value || '';
    const department = document.querySelector('#emergencyDepartmentFilter')?.value || '';
    const sort = document.querySelector('#emergencySort')?.value || 'date';
    const dateFrom = document.querySelector('#emergencyDateFrom')?.value || '';
    const dateTo = document.querySelector('#emergencyDateTo')?.value || '';

    const response = await emergencyRequest({
        action: 'list',
        search,
        status,
        severity,
        incident_type: type,
        department,
        sort,
        date_from: dateFrom,
        date_to: dateTo,
    }, 'GET');

    const items = response.cases || [];
    renderEmergencyCases(items);
    renderEmergencySummary(items);
}

async function loadEmergencyPatients(search = '') {
    const results = document.querySelector('#patientLookupResults');
    if (!results) return;

    const response = await emergencyRequest({ action: 'patients', search }, 'GET');
    const patients = response.patients || [];

    if (!patients.length) {
        results.innerHTML = '<div class="empty-state compact"><p>No matching patients found.</p></div>';
        return;
    }

    results.innerHTML = patients.map(patient => `
        <button type="button" class="patient-option" data-patient-id="${escapeHtml(patient.patient_id)}">
            <strong>${escapeHtml(patient.patient_name || 'Unknown patient')}</strong>
            <span>Patient ID: ${escapeHtml(patient.patient_id)} • ${escapeHtml(patient.department || 'Department N/A')} • ${escapeHtml(patient.position || 'Position N/A')}</span>
        </button>
    `).join('');

    results.querySelectorAll('.patient-option').forEach(button => {
        button.addEventListener('click', () => selectPatient(button.dataset.patientId));
    });
}

async function selectPatient(patientId) {
    const response = await emergencyRequest({ action: 'patient', patient_id: patientId }, 'GET');
    const patient = response.patient;
    if (!patient) {
        throw new Error('Patient could not be loaded.');
    }

    const form = document.querySelector('#emergencyCaseForm');
    if (!form) return;

    const patientIdField = form.querySelector('[name="patient_id"]');
    const patientNameField = form.querySelector('#selectedPatientName');
    const employeeDetails = form.querySelector('#selectedPatientDetails');

    if (patientIdField) patientIdField.value = patient.patient_id || '';
    if (patientNameField) patientNameField.textContent = `${patient.patient_name || 'Patient'} • ${patient.patient_id || ''}`;
    if (employeeDetails) {
        employeeDetails.innerHTML = `
            <p><strong>Employee:</strong> ${escapeHtml(patient.employee_name || 'N/A')}</p>
            <p><strong>Department:</strong> ${escapeHtml(patient.department || 'N/A')}</p>
            <p><strong>Position:</strong> ${escapeHtml(patient.position || 'N/A')}</p>
            <p><strong>Phone:</strong> ${escapeHtml(patient.phone || 'N/A')}</p>
        `;
    }

    const patientLookup = document.querySelector('#patientLookup');
    if (patientLookup) {
        patientLookup.value = patient.patient_name || '';
    }

    const results = document.querySelector('#patientLookupResults');
    if (results) {
        results.innerHTML = '';
    }
}

function showEmergencyError(error, fallback = 'The request could not be completed.') {
    showEmergencyMessage(error instanceof Error ? error.message : fallback, 'error');
}

function openEmergencyModal(mode = 'add', caseData = null) {
    const modal = document.querySelector('#emergencyCaseModal');
    const form = document.querySelector('#emergencyCaseForm');
    const title = document.querySelector('#emergencyModalTitle');
    const submitButton = document.querySelector('#saveEmergencyCaseBtn');
    const cancelButton = document.querySelector('#cancelEmergencyCaseBtn');
    if (!modal || !form || !title || !submitButton) return;

    form.dataset.mode = mode;
    form.reset();
    clearEmergencyMessage();

    const patientIdField = form.querySelector('[name="patient_id"]');
    if (patientIdField) patientIdField.value = '';

    const patientNameField = form.querySelector('#selectedPatientName');
    if (patientNameField) patientNameField.textContent = 'No patient selected';

    const patientDetails = form.querySelector('#selectedPatientDetails');
    if (patientDetails) patientDetails.innerHTML = '<p>Select a patient to fetch employee and department details.</p>';

    if (mode === 'add') {
        const incidentDateField = form.querySelector('[name="incident_date"]');
        if (incidentDateField) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            incidentDateField.value = now.toISOString().slice(0, 16);
        }
    }

    const isEdit = mode === 'edit';
    const isView = mode === 'view';

    title.textContent = isView ? 'Emergency Case Details' : isEdit ? 'Edit Emergency Case' : 'Add New Case';
    submitButton.textContent = isView ? 'Close' : isEdit ? 'Update Case' : 'Save Case';
    submitButton.dataset.mode = mode;

    form.querySelectorAll('input, select, textarea, button').forEach(element => {
        if (element.matches('[name="case_id"]')) return;
        if (element.matches('#saveEmergencyCaseBtn')) return;
        if (element.matches('#cancelEmergencyCaseBtn')) return;
        if (isView) {
            element.disabled = true;
        } else {
            element.disabled = false;
        }
    });

    if (caseData) {
        Object.entries({
            case_id: caseData.case_id || '',
            patient_id: caseData.patient_id || '',
            incident_date: caseData.incident_date ? caseData.incident_date.replace(' ', 'T').slice(0, 16) : '',
            incident_type: caseData.incident_type || 'Other',
            severity_level: caseData.severity_level || 'Medium',
            chief_complaint: caseData.chief_complaint || '',
            initial_assessment: caseData.initial_assessment || '',
            treatment_provided: caseData.treatment_provided || '',
            attending_staff: caseData.attending_staff || '',
            case_status: caseData.case_status || 'Active',
            ambulance_called: caseData.ambulance_called ? 'on' : '',
            ambulance_arrival_time: caseData.ambulance_arrival_time ? caseData.ambulance_arrival_time.replace(' ', 'T').slice(0, 16) : '',
            parents_notified: caseData.parents_notified ? 'on' : '',
            parent_notification_time: caseData.parent_notification_time ? caseData.parent_notification_time.replace(' ', 'T').slice(0, 16) : '',
            witness_names: caseData.witness_names || '',
            transfer_hospital: caseData.transfer_hospital || '',
            follow_up_required: caseData.follow_up_required ? 'on' : '',
            follow_up_date: caseData.follow_up_date || '',
            contact_person: caseData.contact_person || '',
            contact_phone: caseData.contact_phone || '',
            notes: caseData.notes || '',
        }).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (!field) return;
            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
                return;
            }
            field.value = value;
        });

        if (caseData.patient_id) {
            const patientName = caseData.patient_name || caseData.patient_id;
            if (patientNameField) patientNameField.textContent = `${patientName} • ${caseData.patient_id}`;
            if (patientDetails) {
                patientDetails.innerHTML = `
                    <p><strong>Employee:</strong> ${escapeHtml(caseData.employee_name || 'N/A')}</p>
                    <p><strong>Department:</strong> ${escapeHtml(caseData.department || 'N/A')}</p>
                    <p><strong>Position:</strong> ${escapeHtml(caseData.position || 'N/A')}</p>
                    <p><strong>Phone:</strong> ${escapeHtml(caseData.contact_number || 'N/A')}</p>
                `;
            }
        }
    }

    if (isView) {
        submitButton.style.display = 'none';
        cancelButton.textContent = 'Close';
    } else {
        submitButton.style.display = 'inline-flex';
        cancelButton.textContent = 'Cancel';
    }

    modal.hidden = false;
}

function closeEmergencyModal() {
    const modal = document.querySelector('#emergencyCaseModal');
    const form = document.querySelector('#emergencyCaseForm');
    if (modal) modal.hidden = true;
    if (form) {
        form.reset();
        form.dataset.mode = 'add';
    }
    clearEmergencyMessage();
}

async function loadCaseForView(caseId) {
    const response = await emergencyRequest({ action: 'get', case_id: caseId }, 'GET');
    openEmergencyModal('view', response.case);
}

async function loadCaseForEdit(caseId) {
    const response = await emergencyRequest({ action: 'get', case_id: caseId }, 'GET');
    openEmergencyModal('edit', response.case);
}

async function closeCaseRecord(caseId) {
    const confirmed = window.confirm('Close this emergency case?');
    if (!confirmed) return;
    const response = await emergencyRequest({ action: 'close', case_id: caseId }, 'POST');
    showEmergencyMessage(response.message || 'Emergency case closed successfully.');
    await loadEmergencyCases();
}

async function deleteCaseRecord(caseId) {
    const confirmed = window.confirm('Delete this emergency case permanently?');
    if (!confirmed) return;
    const response = await emergencyRequest({ action: 'delete', case_id: caseId }, 'POST');
    showEmergencyMessage(response.message || 'Emergency case deleted successfully.');
    await loadEmergencyCases();
}

async function submitEmergencyCaseForm() {
    const form = document.querySelector('#emergencyCaseForm');
    if (!form) return;

    const mode = form.dataset.mode || 'add';
    const saveButton = document.querySelector('#saveEmergencyCaseBtn');
    if (saveButton?.disabled) return;
    const payload = Object.fromEntries(new FormData(form).entries());

    if (!payload.patient_id) {
        showEmergencyMessage('Please select a patient before saving the emergency case.', 'error');
        return;
    }

    if (!payload.incident_date) {
        showEmergencyMessage('Incident date and time are required.', 'error');
        return;
    }

    if (!payload.chief_complaint) {
        showEmergencyMessage('Chief complaint is required.', 'error');
        return;
    }

    if (!payload.attending_staff) {
        showEmergencyMessage('Attending medical staff is required.', 'error');
        return;
    }

    payload.ambulance_called = !!form.querySelector('[name="ambulance_called"]').checked;
    payload.parents_notified = !!form.querySelector('[name="parents_notified"]').checked;
    payload.follow_up_required = !!form.querySelector('[name="follow_up_required"]').checked;

    const action = mode === 'edit' ? 'update' : 'save';
    if (saveButton) saveButton.disabled = true;
    try {
        const response = await emergencyRequest({ action, ...payload }, 'POST');
        closeEmergencyModal();
        showEmergencyMessage(response.message || 'Emergency case saved successfully.');
        await loadEmergencyCases();
    } catch (error) {
        showEmergencyError(error, 'Emergency case could not be saved.');
    } finally {
        if (saveButton) saveButton.disabled = false;
    }
}

function setUpEmergencyCaseEvents() {
    const root = document.querySelector('.emergency-cases-module');
    if (root && root.dataset.eventsHooked === '1') return;
    if (root) root.dataset.eventsHooked = '1';

    const searchInput = document.querySelector('#emergencyCaseSearch');
    if (searchInput && searchInput.dataset.hooked !== '1') {
        searchInput.dataset.hooked = '1';
        searchInput.addEventListener('input', () => loadEmergencyCases());
    }

    ['emergencyStatusFilter', 'emergencySeverityFilter', 'emergencyTypeFilter', 'emergencyDepartmentFilter', 'emergencySort', 'emergencyDateFrom', 'emergencyDateTo'].forEach(id => {
        const field = document.querySelector(`#${id}`);
        if (!field || field.dataset.hooked === '1') return;
        field.dataset.hooked = '1';
        field.addEventListener('change', loadEmergencyCases);
        field.addEventListener('input', loadEmergencyCases);
    });

    const patientLookup = document.querySelector('#patientLookup');
    if (patientLookup && patientLookup.dataset.hooked !== '1') {
        patientLookup.dataset.hooked = '1';
        patientLookup.addEventListener('input', () => loadEmergencyPatients(patientLookup.value.trim()).catch(error => showEmergencyError(error, 'Patient search failed.')));
    }

    const patientSearchButton = document.querySelector('#searchPatientBtn');
    if (patientSearchButton && patientLookup && patientSearchButton.dataset.hooked !== '1') {
        patientSearchButton.dataset.hooked = '1';
        patientSearchButton.addEventListener('click', () => loadEmergencyPatients(patientLookup.value.trim()).catch(error => showEmergencyError(error, 'Patient search failed.')));
    }

    const addButton = document.querySelector('#addEmergencyCaseBtn');
    if (addButton && addButton.dataset.hooked !== '1') {
        addButton.dataset.hooked = '1';
        addButton.addEventListener('click', () => openEmergencyModal('add'));
    }

    const saveButton = document.querySelector('#saveEmergencyCaseBtn');
    if (saveButton && saveButton.dataset.hooked !== '1') {
        saveButton.dataset.hooked = '1';
        saveButton.addEventListener('click', () => {
            if (saveButton.dataset.mode === 'view') {
                closeEmergencyModal();
                return;
            }
            submitEmergencyCaseForm().catch(error => showEmergencyError(error, 'Emergency case could not be saved.'));
        });
    }

    const cancelButton = document.querySelector('#cancelEmergencyCaseBtn');
    if (cancelButton && cancelButton.dataset.hooked !== '1') {
        cancelButton.dataset.hooked = '1';
        cancelButton.addEventListener('click', closeEmergencyModal);
    }

    const closeButton = document.querySelector('#closeEmergencyCaseModal');
    if (closeButton && closeButton.dataset.hooked !== '1') {
        closeButton.dataset.hooked = '1';
        closeButton.addEventListener('click', closeEmergencyModal);
    }

    const refreshButton = document.querySelector('#refreshEmergencyCasesBtn');
    if (refreshButton && refreshButton.dataset.hooked !== '1') {
        refreshButton.dataset.hooked = '1';
        refreshButton.addEventListener('click', loadEmergencyCases);
    }
}

async function initializeEmergencyCasesModule() {
    const root = document.querySelector('.emergency-cases-module');
    if (!root || root.dataset.initialized === '1') {
        if (!root) {
            if (initializeEmergencyCasesModule._retry === undefined) initializeEmergencyCasesModule._retry = 0;
            if (initializeEmergencyCasesModule._retry < 8) {
                initializeEmergencyCasesModule._retry += 1;
                setTimeout(initializeEmergencyCasesModule, 120 * initializeEmergencyCasesModule._retry);
            }
            return;
        }
        return;
    }
    root.dataset.initialized = '1';
    initializeEmergencyCasesModule._retry = 0;

    setUpEmergencyCaseEvents();
    try {
        await loadEmergencyCases();
        await loadEmergencyPatients();
    } catch (error) {
        showEmergencyError(error, 'Emergency Cases could not be loaded.');
    }
}

document.addEventListener('DOMContentLoaded', initializeEmergencyCasesModule);
window.addEventListener('page:loaded', function () {
    initializeEmergencyCasesModule._retry = 0;
    setTimeout(initializeEmergencyCasesModule, 0);
});
