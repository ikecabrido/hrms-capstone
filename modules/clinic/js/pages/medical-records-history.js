function escapeHistory(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
}

function formatHistoryDate(value, withTime = false) {
    if (!value) return 'Not available';
    const date = new Date(value.replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? escapeHistory(value) : date.toLocaleString([], withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' });
}

function historyStatusClass(status) {
    return String(status || '').toLowerCase().replace(/\s+/g, '-');
}

function formatVitalSigns(value) {
    if (!value) return 'None recorded';
    try {
        return JSON.stringify(typeof value === 'string' ? JSON.parse(value) : value, null, 2);
    } catch (error) {
        return String(value);
    }
}

function showHistoryError(message) {
    const target = document.querySelector('#historyError');
    if (target) { target.textContent = message; target.hidden = false; }
}

let medicalRecordCsrf = '';
let selectedEmployee = null;

function clearHistoryError() {
    const target = document.querySelector('#historyError');
    if (target) { target.textContent = ''; target.hidden = true; }
}

async function requestHistory(params) {
    const query = new URLSearchParams(params);
    const response = await fetch(`medical-record-data.php?${query.toString()}`, { credentials: 'same-origin', cache: 'no-store' });
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
        showHistoryError(friendlyMsg);
        throw new Error(friendlyMsg);
    }
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load medical record data.');
    return data;
}

function renderEmployees(employees, selectedId) {
    employees = Array.isArray(employees) ? employees : [];
    const target = document.querySelector('#employeeResults');
    if (!employees.length) {
        target.innerHTML = '<div class="history-empty"><i class="fa-solid fa-user-slash"></i>No employees match your search.</div>';
        return;
    }
    document.querySelector('#employeeResultStatus').textContent = `Total: ${employees.length}`;
    target.innerHTML = employees.map(employee => `<button type="button" class="employee-result ${String(employee.employee_id) === String(selectedId) ? 'selected' : ''}" data-employee-id="${escapeHistory(employee.employee_id)}"><b class="employee-initials">${escapeHistory((employee.full_name || 'E').split(' ').map(part => part[0]).join('').slice(0, 2).toUpperCase())}</b><span><strong>${escapeHistory(employee.full_name)}</strong><small>ID ${escapeHistory(employee.employee_id)} • ${escapeHistory((employee.department || employee.position || 'Employee').toUpperCase())}</small></span></button>`).join('');
}

function renderSummary(employee) {
    const target = document.querySelector('#employeeSummary');
    if (!employee) { target.innerHTML = '<div class="history-empty">Select an employee to view medical history.</div>'; return; }
    selectedEmployee = employee; const initials=(employee.full_name||'E').split(' ').map(part=>part[0]).join('').slice(0,2).toUpperCase(); target.innerHTML=`<div class="profile-card"><b class="profile-avatar">${escapeHistory(initials)}</b><div><h3>${escapeHistory(employee.full_name)}</h3><span>ID ${escapeHistory(employee.employee_id)} • ${escapeHistory(employee.department||'Department not available')}</span><small>${escapeHistory(employee.email||'Email not available')} • ${escapeHistory(employee.contact_number||'Contact not available')}</small></div></div><div class="summary-cards"><div class="summary-item"><small>Medical Records</small><strong>${Number(employee.record_count||0).toLocaleString()}</strong></div><div class="summary-item"><small>Last Visit</small><strong>${formatHistoryDate(employee.last_visit_date)}</strong></div><div class="summary-item"><small>Status</small><strong>${escapeHistory(employee.patient_status||employee.employment_status||'Not available')}</strong></div></div>`;
}

function renderRecords(records) {
    records = Array.isArray(records) ? records : [];
    const target = document.querySelector('#historyRecords');
    if (!records.length) { target.innerHTML='<tr><td colspan="5"><div class="history-empty"><strong>No medical records found</strong><span>This employee does not have records for the selected date range.</span></div></td></tr>';return; } target.innerHTML=records.map(record=>`<tr><td>${formatHistoryDate(record.visit_date)}</td><td>${escapeHistory(record.examination||record.consultation_type||'Not specified')}</td><td>${escapeHistory(record.chief_complaint||'None')}</td><td><span class="history-badge ${historyStatusClass(record.status)}">${escapeHistory(record.status||'Unknown')}</span></td><td><button class="icon-action" data-record-id="${escapeHistory(record.record_id)}" data-record-action="view"><i class="fa-regular fa-eye"></i></button><button class="icon-action edit-action" data-record-id="${escapeHistory(record.record_id)}" data-record-action="edit"><i class="fa-regular fa-pen-to-square"></i></button><button class="icon-action delete-action" data-record-id="${escapeHistory(record.record_id)}" data-record-action="delete"><i class="fa-regular fa-trash-can"></i></button></td></tr>`).join('');
}

function renderRecordDetails(record) {
    const target = document.querySelector('#recordDetails');
    target.innerHTML = `<div class="history-modal-header"><div><h2>Medical record details</h2><span class="dashboard-status">Record ${escapeHistory(record.record_id)}</span></div><button type="button" class="history-modal-close" data-close-history-modal aria-label="Close details"><i class="fa-solid fa-xmark"></i></button></div><div class="history-detail-grid"><div class="history-detail"><small>Visit date</small><p>${formatHistoryDate(record.visit_date, true)}</p></div><div class="history-detail"><small>Status</small><p><span class="history-badge ${historyStatusClass(record.status)}">${escapeHistory(record.status || 'Unknown')}</span></p></div><div class="history-detail"><small>Examination type</small><p>${escapeHistory(record.consultation_type || 'Not available')}</p></div><div class="history-detail"><small>Attending physician</small><p>${escapeHistory(record.attending_physician || 'Not assigned')}</p></div><div class="history-detail full"><small>Chief complaint</small><p>${escapeHistory(record.chief_complaint || 'Not available')}</p></div><div class="history-detail full"><small>Diagnosis / findings</small><p>${escapeHistory(record.diagnosis || 'Not available')}</p></div><div class="history-detail full"><small>Vital signs</small><p>${escapeHistory(formatVitalSigns(record.vital_signs))}</p></div><div class="history-detail full"><small>Treatment</small><p>${escapeHistory(record.treatment || 'Not available')}</p></div><div class="history-detail full"><small>Medications prescribed</small><p>${escapeHistory(record.medications_prescribed || 'None recorded')}</p></div><div class="history-detail full"><small>Notes</small><p>${escapeHistory(record.notes || 'None recorded')}</p></div></div>`;
    document.querySelector('#historyModal').classList.add('open');
    document.querySelector('#historyModal').setAttribute('aria-hidden', 'false');
}

async function loadEmployees(search = '') {
    const data = await requestHistory({ action: 'employees', search });
    medicalRecordCsrf = data.csrf_token || medicalRecordCsrf;
    renderEmployees(data.employees, document.querySelector('#medicalEmployeeId').value);
}

async function loadHistory(employeeId, page = 1) {
    clearHistoryError();
    document.querySelector('#medicalEmployeeId').value = employeeId;
    const data = await requestHistory({ action: 'history', employee_id: employeeId, date_from: document.querySelector('#historyDateFrom').value, date_to: document.querySelector('#historyDateTo').value, page });
    renderSummary(data.employee || null);
    const history = data.history && typeof data.history === 'object' ? data.history : { items: Array.isArray(data.records) ? data.records : [], pages: 1, page: 1 };
    renderRecords(history.items);
    document.querySelector('#historyTableFooter').innerHTML=Array.from({length:Math.max(1, Number(history.pages) || 1)},(_,index)=>`<button class="page-button ${index+1===Number(history.page||1)?'active':''}" data-page="${index+1}">${index+1}</button>`).join('');
    document.querySelectorAll('.employee-result').forEach(button => button.classList.toggle('selected', button.dataset.employeeId === String(employeeId)));
}

function closeRecordForm(){const modal=document.querySelector('#recordFormModal');modal.classList.remove('open');modal.setAttribute('aria-hidden','true');}
function openRecordForm(record=null){if(!selectedEmployee){showHistoryError('Please select an employee before adding a medical record.');return;}const form=document.querySelector('#medicalRecordForm');form.reset();form.elements.employee_name.value=selectedEmployee.full_name;form.elements.employee_id.value=selectedEmployee.employee_id;form.elements.record_id.value=record?.record_id||'';form.elements.record_date.value=record?String(record.visit_date).slice(0,10):new Date().toISOString().slice(0,10);if(record)['examination','chief_complaint','diagnosis','treatment','notes','status'].forEach(field=>{form.elements[field].value=record[field]||'';});document.querySelector('#recordFormModal').classList.add('open');document.querySelector('#recordFormModal').setAttribute('aria-hidden','false');}
async function submitRecord(event){event.preventDefault();const form=event.currentTarget,payload=Object.fromEntries(new FormData(form));payload.action=payload.record_id?'update':'save';payload.csrf_token=medicalRecordCsrf;const button=document.querySelector('#saveMedicalRecord');button.disabled=true;try{const response=await fetch('medical-record-data.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const rawText = await response.text();let result;try {result = JSON.parse(rawText);} catch (parseErr) {const preview = (rawText || '').trim().slice(0, 500);console.error('[JSON-GATEKEEPER-V2] Non-JSON response from server. First 500 chars:', preview);const looksLikeHtml = (rawText || '').trim().startsWith('<');const friendlyMsg = looksLikeHtml? 'Temporary server issue — please refresh the page. If the problem persists, contact your administrator or check the server error log.': (parseErr.message || 'Invalid response from server.');const target=document.querySelector('#recordFormError');target.textContent=friendlyMsg;target.hidden=false;throw new Error(friendlyMsg);}if(!response.ok||!result.success)throw new Error(result.message||'Unable to save medical record.');closeRecordForm();await loadHistory(payload.employee_id);showHistoryError(result.message);}catch(error){const target=document.querySelector('#recordFormError');target.textContent=error.message;target.hidden=false;}finally{button.disabled=false;}}
async function handleRecordAction(button){const employeeId=document.querySelector('#medicalEmployeeId').value,data=await requestHistory({action:'record',employee_id:employeeId,record_id:button.dataset.recordId});if(button.dataset.recordAction==='view')return renderRecordDetails(data.record);if(button.dataset.recordAction==='edit')return openRecordForm(data.record);if(button.dataset.recordAction==='delete'&&confirm('Are you sure you want to delete this medical record?')){const response=await fetch('medical-record-data.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',employee_id:employeeId,record_id:button.dataset.recordId,csrf_token:medicalRecordCsrf})});const rawText = await response.text();let result;try {result = JSON.parse(rawText);} catch (parseErr) {const preview = (rawText || '').trim().slice(0, 500);console.error('[JSON-GATEKEEPER-V2] Non-JSON response from server. First 500 chars:', preview);const looksLikeHtml = (rawText || '').trim().startsWith('<');const friendlyMsg = looksLikeHtml? 'Temporary server issue — please refresh the page. If the problem persists, contact your administrator or check the server error log.': (parseErr.message || 'Invalid response from server.');showHistoryError(friendlyMsg);throw new Error(friendlyMsg);}if(!response.ok||!result.success)throw new Error(result.message||'Unable to delete medical record.');await loadHistory(employeeId);showHistoryError(result.message);}}

export function initMedicalHistory() {
    const root = document.querySelector('.medical-history-page');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';
    const search = document.querySelector('#employeeSearch');
    const panelSearch = document.querySelector('#employeePanelSearch');
    let searchTimer;
    search.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadEmployees(search.value.trim()).catch(error => showHistoryError(error.message)), 250); });
    panelSearch.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>loadEmployees(panelSearch.value.trim()).catch(error=>showHistoryError(error.message)),250);});
    document.querySelector('#employeeResults').addEventListener('click', event => { const employee = event.target.closest('[data-employee-id]'); if (employee) loadHistory(employee.dataset.employeeId).catch(error => showHistoryError(error.message)); });
    document.querySelector('#historyFilters').addEventListener('submit', event => { event.preventDefault(); const employeeId = document.querySelector('#medicalEmployeeId').value; if (employeeId) loadHistory(employeeId).catch(error => showHistoryError(error.message)); });
    document.querySelector('#historyRecords').addEventListener('click',event=>{const button=event.target.closest('[data-record-id]');if(button)handleRecordAction(button).catch(error=>showHistoryError(error.message));}); document.querySelector('#historyTableFooter').addEventListener('click',event=>{const button=event.target.closest('[data-page]'),id=document.querySelector('#medicalEmployeeId').value;if(button&&id)loadHistory(id,button.dataset.page).catch(error=>showHistoryError(error.message));}); document.querySelector('#addMedicalRecord').addEventListener('click',()=>openRecordForm());document.querySelector('#medicalRecordForm').addEventListener('submit',submitRecord);document.querySelectorAll('[data-close-record-form]').forEach(button=>button.addEventListener('click',closeRecordForm));document.querySelector('#clearHistoryFilters').addEventListener('click',()=>{document.querySelector('#historyDateFrom').value='';document.querySelector('#historyDateTo').value='';const id=document.querySelector('#medicalEmployeeId').value;if(id)loadHistory(id).catch(error=>showHistoryError(error.message));});
    document.querySelector('#historyModal').addEventListener('click', event => { if (event.target.id === 'historyModal' || event.target.closest('[data-close-history-modal]')) { document.querySelector('#historyModal').classList.remove('open'); document.querySelector('#historyModal').setAttribute('aria-hidden', 'true'); } });
    loadEmployees().then(() => { const selected = document.querySelector('#medicalEmployeeId').value; if (selected) return loadHistory(selected); }).catch(error => showHistoryError(error.message));
}

window.addEventListener('page:loaded', initMedicalHistory);
document.addEventListener('DOMContentLoaded', initMedicalHistory);
