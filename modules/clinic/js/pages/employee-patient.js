function escapePatientHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
}

function formatDate(value) {
    if (!value) return 'Not available';
    const date = new Date(value + 'T00:00:00');
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
}

function showPageMessage(message, type = 'success') {
    const box = document.querySelector('#moduleMessage');
    if (!box) return;
    box.textContent = message;
    box.className = 'module-message ' + (type === 'error' ? 'error' : 'success');
    box.hidden = false;
}

function clearPageMessage() {
    const box = document.querySelector('#moduleMessage');
    if (!box) return;
    box.textContent = '';
    box.hidden = true;
    box.className = 'module-message';
}

function getPatientForm() {
    return document.querySelector('#patientRegistrationForm');
}

function getPatientFormState() {
    const form = getPatientForm();
    return form ? new URLSearchParams(new FormData(form)).toString() : '';
}

function clearPatientFieldErrors() {
    document.querySelectorAll('.patient-field-error').forEach(error => error.remove());
    document.querySelectorAll('#patientRegistrationForm .invalid-field').forEach(field => field.classList.remove('invalid-field'));
}

function showPatientFieldError(fieldName, message) {
    const form = getPatientForm();
    const field = form?.querySelector(`[name="${fieldName}"]`);
    if (!field) return;
    field.classList.add('invalid-field');
    const error = document.createElement('small');
    error.className = 'patient-field-error';
    error.textContent = message;
    field.insertAdjacentElement('afterend', error);
}

function setPatientActionState(isBusy) {
    const saveButton = document.querySelector('#savePatientBtn');
    const deactivateButton = document.querySelector('#deactivatePatientBtn');
    if (saveButton) {
        saveButton.disabled = isBusy || saveButton.dataset.ready !== 'true';
        saveButton.dataset.busy = isBusy ? 'true' : 'false';
    }
    if (deactivateButton) {
        deactivateButton.disabled = isBusy || deactivateButton.dataset.ready !== 'true';
        deactivateButton.dataset.busy = isBusy ? 'true' : 'false';
    }
}

async function patientRequest(params, method = 'GET') {
    const queryParams = new URLSearchParams(params);
    const url = method === 'GET' ? `employee-patient-data.php?${queryParams.toString()}` : 'employee-patient-data.php';
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

function renderEmployeeList(rows) {
    const target = document.querySelector('#employeeSearchResults');
    if (!target) return;

    if (!rows.length) {
        target.innerHTML = '<div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No employees match your search.</p></div>';
        return;
    }

    target.innerHTML = rows.map(employee => `
        <button type="button" class="entity-item" data-employee-id="${escapePatientHtml(employee.employee_id)}">
            <div>
                <strong>${escapePatientHtml(employee.full_name || 'Unknown employee')}</strong>
                <small>${escapePatientHtml(employee.position || 'No position')} • ${escapePatientHtml(employee.department || 'No department')}</small>
            </div>
            <span class="state-badge ${String(employee.employment_status || 'Active').toLowerCase() === 'inactive' ? 'inactive' : 'active'}">${escapePatientHtml(employee.employment_status || 'Active')}</span>
        </button>
    `).join('');
}

function renderPatientList(rows) {
    const target = document.querySelector('#patientListResults');
    if (!target) return;

    if (!rows.length) {
        target.innerHTML = '<div class="empty-state"><i class="fa-solid fa-user-injured"></i><p>No patient records found.</p></div>';
        return;
    }

    target.innerHTML = rows.map(patient => `
        <button type="button" class="entity-item" data-patient-id="${escapePatientHtml(patient.patient_id)}">
            <div>
                <strong>${escapePatientHtml(patient.full_name || 'Unknown patient')}</strong>
                <small>${escapePatientHtml(patient.patient_type || 'Staff')} • ${escapePatientHtml(patient.status || 'Active')}</small>
            </div>
            <span class="state-badge ${String(patient.status || 'Active').toLowerCase() === 'inactive' ? 'inactive' : 'active'}">${escapePatientHtml(patient.status || 'Active')}</span>
        </button>
    `).join('');
}

function renderSelectedEmployee(employee) {
    const card = document.querySelector('#selectedEmployeeCard');
    const registerButton = document.querySelector('#registerEmployeePatientBtn');
    const badge = document.querySelector('#employeeStatusBadge');
    if (!card || !registerButton || !badge) return;

    if (!employee) {
        card.innerHTML = '<div class="empty-state compact"><i class="fa-solid fa-user-check"></i><p>Select an employee to register as a patient.</p></div>';
        registerButton.disabled = true;
        badge.textContent = 'No employee selected';
        badge.className = 'state-badge neutral';
        return;
    }

    card.innerHTML = `
        <div class="detail-grid">
            <div><label>Employee ID</label><p>${escapePatientHtml(employee.employee_id)}</p></div>
            <div><label>Full Name</label><p>${escapePatientHtml(employee.full_name || 'Not available')}</p></div>
            <div><label>Department</label><p>${escapePatientHtml(employee.department || 'Not available')}</p></div>
            <div><label>Position</label><p>${escapePatientHtml(employee.position || 'Not available')}</p></div>
            <div><label>Email</label><p>${escapePatientHtml(employee.email || 'Not available')}</p></div>
            <div><label>Employment Status</label><p>${escapePatientHtml(employee.employment_status || 'Active')}</p></div>
        </div>
    `;
    registerButton.disabled = false;
    badge.textContent = 'Employee ready';
    badge.className = 'state-badge active';
}

function syncPatientActionButtons() {
    const form = document.querySelector('#patientRegistrationForm');
    const saveButton = document.querySelector('#savePatientBtn');
    const deactivateButton = document.querySelector('#deactivatePatientBtn');
    if (!form || !saveButton || !deactivateButton) return;

    const employeeId = form.querySelector('[name="employee_id"]').value;
    const patientId = form.querySelector('[name="patient_id"]').value;
    const patientStatus = form.querySelector('[name="status"]').value || 'Active';

    saveButton.dataset.ready = (employeeId || patientId) ? 'true' : 'false';
    deactivateButton.dataset.ready = patientId && String(patientStatus).toLowerCase() !== 'inactive' ? 'true' : 'false';
    if (saveButton.dataset.busy !== 'true' && deactivateButton.dataset.busy !== 'true') {
        setPatientActionState(false);
    }
}

function markPatientFormClean() {
    const form = getPatientForm();
    if (form) form.dataset.initialState = getPatientFormState();
}

function populatePatientForm(patient) {
    const form = document.querySelector('#patientRegistrationForm');
    const saveButton = document.querySelector('#savePatientBtn');
    const deactivateButton = document.querySelector('#deactivatePatientBtn');
    if (!form || !saveButton || !deactivateButton) return;

    if (!patient) {
        form.reset();
        form.querySelector('[name="patient_id"]').value = '';
        form.querySelector('[name="employee_id"]').value = '';
        form.querySelector('[name="patient_type"]').value = 'Staff';
        form.querySelector('[name="status"]').value = 'Active';
        syncPatientActionButtons();
        markPatientFormClean();
        return;
    }

    const fields = {
        patient_id: patient.patient_id || '',
        employee_id: patient.employee_id || '',
        first_name: patient.first_name || '',
        last_name: patient.last_name || '',
        middle_name: patient.middle_name || '',
        email: patient.email || '',
        phone: patient.phone || '',
        address: patient.address || '',
        birth_date: patient.birth_date || '',
        gender: patient.gender || 'Other',
        blood_type: patient.blood_type || '',
        allergies: patient.allergies || '',
        medical_conditions: patient.medical_conditions || '',
        current_medications: patient.current_medications || '',
        patient_type: patient.patient_type || 'Staff',
        status: patient.status || 'Active',
    };

    Object.entries(fields).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) field.value = value;
    });

    syncPatientActionButtons();
}

async function loadEmployees(search = '') {
    const data = await patientRequest({ action: 'employees', search }, 'GET');
    renderEmployeeList(data.employees || []);
}

async function loadPatients(search = '') {
    const data = await patientRequest({ action: 'patients', search }, 'GET');
    renderPatientList(data.patients || []);
}

async function loadEmployeeDetails(employeeId) {
    const data = await patientRequest({ action: 'employee', employee_id: employeeId }, 'GET');
    renderSelectedEmployee(data.employee || null);
    if (data.employee) {
        const form = document.querySelector('#patientRegistrationForm');
        if (form) {
            form.querySelector('[name="patient_id"]').value = '';
            form.querySelector('[name="employee_id"]').value = data.employee.employee_id || '';
            form.querySelector('[name="first_name"]').value = data.employee.first_name || '';
            form.querySelector('[name="last_name"]').value = data.employee.last_name || '';
            form.querySelector('[name="email"]').value = data.employee.email || '';
            form.querySelector('[name="patient_type"]').value = 'Staff';
            form.querySelector('[name="status"]').value = 'Active';
            form.querySelector('[name="middle_name"]').value = '';
            form.querySelector('[name="phone"]').value = '';
            form.querySelector('[name="address"]').value = '';
            form.querySelector('[name="birth_date"]').value = '';
            form.querySelector('[name="gender"]').value = 'Other';
            form.querySelector('[name="blood_type"]').value = '';
            form.querySelector('[name="allergies"]').value = '';
            form.querySelector('[name="medical_conditions"]').value = '';
            form.querySelector('[name="current_medications"]').value = '';
            syncPatientActionButtons();
            markPatientFormClean();
        }
    }
}

async function loadPatientDetails(patientId) {
    const data = await patientRequest({ action: 'patient', patient_id: patientId }, 'GET');
    populatePatientForm(data.patient || null);
    syncPatientActionButtons();
    markPatientFormClean();
}

async function registerSelectedEmployee() {
    const employeeId = document.querySelector('.entity-item.selected')?.dataset?.employeeId;
    if (!employeeId) {
        showPageMessage('Please select an employee first.', 'error');
        return;
    }
    const created = await patientRequest({ action: 'register', employee_id: employeeId }, 'POST');
    showPageMessage(created.message || 'Employee registered successfully.');
    await loadPatients();
    await loadEmployeeDetails(employeeId);
    await loadPatientDetails(created.patient.patient_id);
}

async function savePatientForm() {
    const form = document.querySelector('#patientRegistrationForm');
    if (!form) return;

    const saveButton = document.querySelector('#savePatientBtn');
    if (saveButton?.dataset.busy === 'true') return;
    clearPatientFieldErrors();

    const formData = Object.fromEntries(new FormData(form).entries());
    const employeeId = formData.employee_id;
    const patientId = formData.patient_id;

    if (!employeeId && !patientId) {
        showPatientFieldError('employee_id', 'Select an employee or patient first.');
        showPageMessage('Please select an employee or patient before saving.', 'error');
        return;
    }

    if (!formData.first_name || !formData.last_name) {
        if (!formData.first_name) showPatientFieldError('first_name', 'First name is required.');
        if (!formData.last_name) showPatientFieldError('last_name', 'Last name is required.');
        showPageMessage('First name and last name are required.', 'error');
        return;
    }

    setPatientActionState(true);
    try {
        const saved = await patientRequest({ action: 'save', ...formData }, 'POST');
        showPageMessage(saved.message || 'Changes saved successfully.');
        await loadPatients();
        if (saved.patient && saved.patient.patient_id) {
            form.querySelector('[name="patient_id"]').value = saved.patient.patient_id || '';
            form.querySelector('[name="employee_id"]').value = saved.patient.employee_id || formData.employee_id || '';
            form.querySelector('[name="status"]').value = saved.patient.status || 'Active';
            await loadPatientDetails(saved.patient.patient_id);
            markPatientFormClean();
        }
    } finally {
        setPatientActionState(false);
    }
}

async function deactivateSelectedPatient() {
    const form = document.querySelector('#patientRegistrationForm');
    if (!form) return;
    const patientId = form.querySelector('[name="patient_id"]').value;
    if (!patientId) {
        showPageMessage('Please select a patient first.', 'error');
        return;
    }

    if (String(form.querySelector('[name="status"]').value).toLowerCase() === 'inactive') {
        showPageMessage('This patient record is already inactive.', 'error');
        syncPatientActionButtons();
        return;
    }

    const confirmed = window.confirm('Are you sure you want to deactivate this record?');
    if (!confirmed) {
        return;
    }

    setPatientActionState(true);
    try {
        const result = await patientRequest({ action: 'deactivate', patient_id: patientId }, 'POST');
        showPageMessage(result.message || 'Record deactivated successfully.');
        await loadPatients();
        await loadPatientDetails(patientId);
        markPatientFormClean();
    } finally {
        setPatientActionState(false);
    }
}

function attachEmployeeSelection() {
    document.body.addEventListener('click', event => {
        const employeeButton = event.target.closest('[data-employee-id]');
        if (employeeButton) {
            document.querySelectorAll('[data-employee-id]').forEach(button => button.classList.toggle('selected', button === employeeButton));
            const employeeId = employeeButton.dataset.employeeId;
            loadEmployeeDetails(employeeId).catch(error => showPageMessage(error.message, 'error'));
        }

        const patientButton = event.target.closest('[data-patient-id]');
        if (patientButton) {
            document.querySelectorAll('[data-patient-id]').forEach(button => button.classList.toggle('selected', button === patientButton));
            loadPatientDetails(patientButton.dataset.patientId).catch(error => showPageMessage(error.message, 'error'));
        }
    });
}

export function initEmployeePatient() {
    const root = document.querySelector('.patient-module');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';

    clearPageMessage();
    markPatientFormClean();
    populatePatientForm(null);
    renderSelectedEmployee(null);

    const employeeSearch = document.querySelector('#employeePatientSearch');
    const patientSearch = document.querySelector('#patientListSearch');
    const registerButton = document.querySelector('#registerEmployeePatientBtn');
    const saveButton = document.querySelector('#savePatientBtn');
    const deactivateButton = document.querySelector('#deactivatePatientBtn');
    const clearButton = document.querySelector('#clearSelectionBtn');
    const formResetButton = document.querySelector('[data-reset-form]');
    const refreshEmployees = document.querySelector('#refreshEmployeeSearch');
    const refreshPatients = document.querySelector('#refreshPatientList');

    if (employeeSearch) {
        let timer;
        employeeSearch.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                loadEmployees(employeeSearch.value.trim()).catch(error => showPageMessage(error.message, 'error'));
            }, 250);
        });
    }

    if (patientSearch) {
        let timer;
        patientSearch.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                loadPatients(patientSearch.value.trim()).catch(error => showPageMessage(error.message, 'error'));
            }, 250);
        });
    }

    if (registerButton) {
        registerButton.addEventListener('click', () => registerSelectedEmployee().catch(error => showPageMessage(error.message, 'error')));
    }

    if (saveButton) {
        saveButton.addEventListener('click', () => savePatientForm().catch(error => showPageMessage(error.message, 'error')));
    }

    document.querySelector('#patientRegistrationForm')?.addEventListener('input', syncPatientActionButtons);
    document.querySelector('#patientRegistrationForm')?.addEventListener('change', syncPatientActionButtons);

    if (deactivateButton) {
        deactivateButton.addEventListener('click', () => deactivateSelectedPatient().catch(error => showPageMessage(error.message, 'error')));
    }

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            renderSelectedEmployee(null);
            populatePatientForm(null);
            document.querySelectorAll('[data-employee-id], [data-patient-id]').forEach(item => item.classList.remove('selected'));
            clearPageMessage();
        });
    }

    if (formResetButton) {
        formResetButton.addEventListener('click', () => {
            const form = document.querySelector('#patientRegistrationForm');
            if (form && form.dataset.initialState !== getPatientFormState()) {
                const confirmed = window.confirm('You have unsaved changes. Are you sure you want to cancel?');
                if (!confirmed) return;
            }
            if (form) {
                form.reset();
            }
            populatePatientForm(null);
            renderSelectedEmployee(null);
            document.querySelectorAll('[data-employee-id], [data-patient-id]').forEach(item => item.classList.remove('selected'));
            clearPageMessage();
        });
    }

    if (refreshEmployees) {
        refreshEmployees.addEventListener('click', () => loadEmployees(employeeSearch?.value.trim() || '').catch(error => showPageMessage(error.message, 'error')));
    }

    if (refreshPatients) {
        refreshPatients.addEventListener('click', () => loadPatients(patientSearch?.value.trim() || '').catch(error => showPageMessage(error.message, 'error')));
    }

    attachEmployeeSelection();
    loadEmployees().catch(error => showPageMessage(error.message, 'error'));
    loadPatients().catch(error => showPageMessage(error.message, 'error'));
}

window.addEventListener('page:loaded', () => initEmployeePatient());
document.addEventListener('DOMContentLoaded', () => initEmployeePatient());
