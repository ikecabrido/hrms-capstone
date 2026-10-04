            
            // Shared helpers available early in the page.
            if (typeof window.safeLower !== 'function') {
                window.safeLower = function(value) {
                    return String(value || '').toLowerCase();
                };
            }
            if (typeof window.escapeHtml !== 'function') {
                window.escapeHtml = function(value) {
                    return String(value || '').replace(/[&<>"']/g, function(match) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        }[match] || match;
                    });
                };
            }

            // Unified modal lifecycle for the shift management page.
            function openModal(modalId) {
                try {
                    const modal = document.getElementById(modalId);
                    if (!modal) return;
                    if (modal.parentElement !== document.body) {
                        try { document.body.appendChild(modal); } catch (e) { /* ignore */ }
                    }
                    modal.style.display = 'flex';
                    modal.style.position = 'fixed';
                    modal.style.inset = '0';
                    modal.style.zIndex = '99999';
                    document.body.classList.add('modal-open');
                } catch (e) {
                    console.error('openModal error:', e);
                }
            }

            function closeModal(modalId) {
                try {
                    const modal = document.getElementById(modalId);
                    if (!modal) return;
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    if (modalId === 'assignmentModal') {
                        assignmentMode = 'create';
                        selectedEmployeeForEdit = null;
                        const actionBtn = document.getElementById('assignmentModalActionButton');
                        const headerTitle = document.querySelector('#assignmentModal .modal-header h2');
                        const container = document.getElementById('employeeListContainer');
                        if (headerTitle) headerTitle.innerHTML = '<i class="fas fa-user-check"></i> Assign Shift to Employees';
                        if (actionBtn) { actionBtn.innerHTML = '<i class="fas fa-check"></i> Assign to Selected'; actionBtn.dataset.action = 'assign'; }
                        if (container) container.style.display = 'none';
                        const searchFilterContainer = document.getElementById('employeeSearchFilterContainer');
                        const selectedDisplay = document.getElementById('selectedEmployeeDisplay');
                        if (searchFilterContainer) searchFilterContainer.style.display = '';
                        if (selectedDisplay) selectedDisplay.style.display = 'none';
                    }
                } catch (e) {
                    console.error('closeModal error:', e);
                }
            }

            window.openModal = openModal;
            window.closeModal = closeModal;

            if (!window.__shiftModalHandlersBound) {
                window.__shiftModalHandlersBound = true;
                document.addEventListener('click', function (event) {
                    const closeTrigger = event.target.closest('.modal-close, [data-close-modal], [onclick*="closeModal("]');
                    if (closeTrigger) {
                        event.preventDefault();
                        let modalId = closeTrigger.getAttribute('data-close-modal');
                        if (!modalId) {
                            const onclick = closeTrigger.getAttribute('onclick') || '';
                            const match = onclick.match(/closeModal\(['"]([^'"]+)['"]\)/);
                            modalId = match ? match[1] : closeTrigger.closest('.modal')?.id;
                        }
                        if (modalId) closeModal(modalId);
                        return;
                    }

                    if (event.target.classList.contains('modal')) {
                        closeModal(event.target.id);
                    }
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        const visibleModals = Array.from(document.querySelectorAll('.modal')).filter(function (modal) {
                            return modal.style.display !== 'none' && getComputedStyle(modal).display !== 'none';
                        });
                        const modal = visibleModals[visibleModals.length - 1];
                        if (modal && modal.id) {
                            closeModal(modal.id);
                        }
                    }
                });
            }

            let fixedScheduleEmployees = [];
            let filteredFixedScheduleEmployees = [];
            const TIME_API_ROOT = window.__TA_API_ROOT || '/hrms/hrms-capstone/modules/time/app/api';

            function showToast(message, type) {
                const toast = document.createElement('div');
                toast.className = 'ta-toast ta-toast-' + (type === 'error' ? 'error' : 'success');
                toast.textContent = message;
                toast.style.cssText = 'position:fixed; right:24px; bottom:24px; z-index:10000; min-width:280px; max-width:420px; padding:14px 18px; border-radius:8px; color:#fff; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,.2); background:' + (type === 'error' ? '#c0392b' : '#218739') + ';';
                document.body.appendChild(toast);
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity .25s ease';
                    setTimeout(function() { toast.remove(); }, 250);
                }, 3500);
            }

            function cleanupShiftPageState() {
                const searchInput = document.getElementById('gf_employee_search');
                if (searchInput) {
                    searchInput.oninput = null;
                    searchInput.onfocus = null;
                    searchInput.dataset.bound = '';
                }
                const dropdown = document.getElementById('gf_employee_dropdown');
                if (dropdown) {
                    dropdown.innerHTML = '';
                    dropdown.style.display = 'none';
                }
                const display = document.getElementById('gf_selected_employee_display');
                if (display) {
                    display.textContent = 'Select an employee';
                }
                const hiddenInput = document.getElementById('gf_employee_id');
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }

            if (typeof window.registerPageCleanup === 'function') {
                window.registerPageCleanup(cleanupShiftPageState);
            }

            function initShiftManagement() {
                            console.log('[TA INIT] initShiftManagement');

                setupEmployeeSearch();
                loadFixedScheduleEmployees();

                if (typeof loadEmployees === 'function') {
                    loadEmployees();
                }

                if (typeof loadShiftTemplates === 'function') {
                    loadShiftTemplates();
                }

                if (typeof loadAssignments === 'function') {
                    loadAssignments();
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                initShiftManagement();
            });

// Reinitialize on each visit to the shifts page because the DOM is replaced by AJAX navigation.
function initShiftsPage() {
    console.log('[TA INIT] Shifts initialized');
    try { initShiftManagement(); } catch (e) { console.error('initShiftsPage error', e); }
}

            function submitGenerateFixed() {
                const employeeId = document.getElementById('gf_employee_id').value;
                if (!employeeId) { showToast('No employee shift was selected for editing.', 'error'); return; }
                const startDate = document.getElementById('gf_start_date').value;
                const endDate = document.getElementById('gf_end_date').value;

                if (!startDate || !endDate) { showToast('Please provide the schedule start and end dates.', 'error'); return; }

                const days = {};
                let validationError = '';
                ['1','2','3','4','5','6'].forEach(function(d) {
                    if (validationError) return;
                    const enabledEl = document.getElementById('gf_day_' + d + '_enabled');
                    const enabled = enabledEl ? enabledEl.checked : false;
                    if (!enabled) return;
                    const sEl = document.getElementById('gf_day_' + d + '_start');
                    const eEl = document.getElementById('gf_day_' + d + '_end');
                    if (!sEl || !eEl) return;
                    const s = sEl.value;
                    const e = eEl.value;
                    const bsEl = document.getElementById('gf_day_' + d + '_break_start');
                    const beEl = document.getElementById('gf_day_' + d + '_break_end');
                    const bs = bsEl ? bsEl.value : null;
                    const be = beEl ? beEl.value : null;
                    if (!s || !e) { validationError = 'Please enter start and end times for every selected day.'; return; }
                    days[d] = { start: s, end: e };
                    if (bs || be) { days[d].break_start = bs; days[d].break_end = be; }
                });

                if (validationError) { showToast(validationError, 'error'); return; }
                if (Object.keys(days).length === 0) { showToast('Select at least one scheduled day.', 'error'); return; }

                const payload = { employee_id: employeeId, start_date: startDate, end_date: endDate, days: days };

                fetch(TIME_API_ROOT + '/schedules/generate_fixed_schedule.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }).then(async r => {
                    const responseText = await r.text();
                    let res;
                    try {
                        res = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Save shift returned invalid JSON:', responseText);
                        throw new Error('The server returned an invalid response. Check the PHP error log.');
                    }
                    if (!r.ok && (!res || res.success !== false)) {
                        throw new Error('The server rejected the shift update.');
                    }
                    return res;
                }).then(res => {
                    if (res.success) {
                        showToast('Employee shift changes saved successfully.', 'success');
                        closeModal('generateFixedModal');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        showToast('Unable to save shift changes: ' + (res.error || 'Unknown error'), 'error');
                    }
                }).catch(err => {
                    showToast('Unable to save shift changes: ' + (err.message || 'Network or server error'), 'error');
                    console.error(err);
                });
            }

            // copySelectedFlexToGenerate removed — flexible modal deprecated in this view

            function loadFixedScheduleEmployees() {
                return fetch(TIME_API_ROOT + '/shifts/get_employees.php')
                    .then(response => response.text())
                    .then(text => {
                        let data;
                        try { data = JSON.parse(text); } catch (err) { console.error('get_employees returned non-JSON:', text); throw err; }
                        if (data.success) {
                            fixedScheduleEmployees = Array.isArray(data.employees) ? data.employees : [];
                            filteredFixedScheduleEmployees = [...fixedScheduleEmployees];
                            window.fixedScheduleEmployees = fixedScheduleEmployees;
                            if (document.getElementById('gf_employee_search') && document.getElementById('gf_employee_search').value.trim()) {
                                updateFixedEmployeeSearch();
                            }
                            return fixedScheduleEmployees;
                        }
                        console.error('Error loading employees:', data.message);
                        return [];
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        return [];
                    });
            }

            function renderFixedEmployeeSuggestions(employees) {
                const suggestions = document.getElementById('gf_employee_suggestions');
                const searchInput = document.getElementById('gf_employee_search');
                if (!suggestions || !searchInput) return;

                if (!employees || employees.length === 0) {
                    suggestions.innerHTML = '<div style="padding: 12px; color: #666;">No employees found</div>';
                    suggestions.style.display = 'block';
                    return;
                }

                suggestions.innerHTML = employees.map(emp => {
                    const label = emp.full_name || emp.employee || emp.name || emp.employee_id;
                    const id = emp.employee_id ?? emp.id ?? emp.employeeId;
                    return `<div class="employee-suggestion-item" data-id="${escapeHtml(id)}" data-name="${escapeHtml(label)}" style="padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #eee;">${escapeHtml(label)}<span style="float:right; color:#999;">${escapeHtml(id)}</span></div>`;
                }).join('');
                suggestions.style.display = 'block';
            }

            function filterFixedEmployeeSuggestions(searchTerm) {
                const term = safeLower(searchTerm || '').trim();
                const suggestions = document.getElementById('gf_employee_suggestions');
                if (!suggestions) return;

                if (term.length === 0) {
                    suggestions.style.display = 'none';
                    return;
                }

                filteredFixedScheduleEmployees = fixedScheduleEmployees.filter(emp => {
                    const fullName = safeLower(emp.full_name || emp.employee || emp.name || '');
                    const employeeId = safeLower(String(emp.employee_id || emp.id || ''));
                    return fullName.includes(term) || employeeId.includes(term);
                });

                renderFixedEmployeeSuggestions(filteredFixedScheduleEmployees);
            }

            function searchFixedEmployees() {
                const searchTerm = document.getElementById('gf_employee_search').value;
                if (!searchTerm || String(searchTerm).trim().length === 0) {
                    showToast('An employee shift must be selected before editing.', 'error');
                    return;
                }
                filterFixedEmployeeSuggestions(searchTerm);
            }

            function selectFixedEmployee(employeeId, employeeName) {
                const display = document.getElementById('gf_selected_employee_display');
                const hid = document.getElementById('gf_employee_id');
                const searchInput = document.getElementById('gf_employee_search');
                const suggestions = document.getElementById('gf_employee_suggestions');
                if (hid) hid.value = employeeId;
                if (display) display.textContent = employeeName || employeeId;
                if (searchInput) searchInput.value = employeeName || employeeId;
                if (suggestions) suggestions.style.display = 'none';
            }

            function toggleGfDayRow(dayIndex) {
                const controls = document.getElementById('gf_day_' + dayIndex + '_controls');
                const checkbox = document.getElementById('gf_day_' + dayIndex + '_enabled');
                if (!controls || !checkbox) return;
                controls.style.display = checkbox.checked ? 'grid' : 'none';
            }

            // Open the edit employee shift modal and prefill with the employee's existing schedule
            function openGenerateFixedModalForEmployee(employeeId) {
                const id = employeeId || (typeof selectedEmployeeIdForEdit !== 'undefined' ? selectedEmployeeIdForEdit : (window.selectedEmployeeIdForEdit || window.selectedEmployeeForEdit));
                if (!id) {
                    showToast('No employee shift was selected for editing.', 'error');
                    return;
                }

                selectedEmployeeIdForEdit = id;
                try { window.selectedEmployeeIdForEdit = id; } catch (e) { /* ignore */ }

                const searchInput = document.getElementById('gf_employee_search');
                const suggestions = document.getElementById('gf_employee_suggestions');
                const disp = document.getElementById('gf_selected_employee_display');
                const hid = document.getElementById('gf_employee_id');
                const startDate = document.getElementById('gf_start_date');
                const endDate = document.getElementById('gf_end_date');

                closeModal('employeeShiftModal');

                if (searchInput) {
                    searchInput.value = '';
                }
                if (suggestions) {
                    suggestions.style.display = 'none';
                }
                if (hid) {
                    hid.value = id;
                }
                if (disp) {
                    disp.innerText = 'Loading employee schedule...';
                }
                if (!fixedScheduleEmployees.length) {
                    loadFixedScheduleEmployees();
                }
                if (startDate) {
                    startDate.value = '';
                }
                if (endDate) {
                    endDate.value = '';
                }

                for (let i = 1; i <= 6; i++) {
                    const cb = document.getElementById('gf_day_' + i + '_enabled');
                    const startEl = document.getElementById('gf_day_' + i + '_start');
                    const endEl = document.getElementById('gf_day_' + i + '_end');
                    const breakStartEl = document.getElementById('gf_day_' + i + '_break_start');
                    const breakEndEl = document.getElementById('gf_day_' + i + '_break_end');
                    if (cb) {
                        cb.checked = false;
                        toggleGfDayRow(i);
                    }
                    if (startEl) startEl.value = '';
                    if (endEl) endEl.value = '';
                    if (breakStartEl) breakStartEl.value = '';
                    if (breakEndEl) breakEndEl.value = '';
                }

                const weekRange = getCurrentWeekRange();
                const scheduleApi = (window.__TA_API_ROOT || '/hrms/hrms-capstone/modules/time/app/api') + '/schedules/get_employee_schedule.php';
                fetch(`${scheduleApi}?employee_id=${encodeURIComponent(id)}&start_date=${encodeURIComponent(weekRange.start)}&end_date=${encodeURIComponent(weekRange.end)}`)
                    .then(r => r.text())
                    .then(text => {
                        let data;
                        try { data = JSON.parse(text); } catch (e) { throw new Error('Invalid JSON'); }
                        if (!data || !data.success) throw new Error(data && data.error ? data.error : 'Failed to fetch employee schedule');

                        const emp = data.employee || {};
                        const schedule = Array.isArray(data.schedule) ? data.schedule : [];

                        if (disp) {
                            disp.innerText = (emp.full_name || emp.name || emp.employee_name || ('Employee ' + id));
                        }
                        if (searchInput) {
                            searchInput.value = emp.full_name || emp.name || emp.employee_name || id;
                        }
                        if (startDate) startDate.value = weekRange.start;
                        if (endDate) endDate.value = weekRange.end;

                        schedule.forEach(day => {
                            const dayDate = day.date ? new Date(day.date) : null;
                            if (!dayDate) return;
                            const dow = dayDate.getDay();
                            if (dow === 0) return;
                            const idx = dow;
                            const cb = document.getElementById('gf_day_' + idx + '_enabled');
                            if (!cb) return;

                            const source = day.custom ? day.custom : (day.flexible ? day.flexible : day.shift);
                            const startValue = source ? parseTimeValue(source.start_time || source.start || '') : '';
                            const endValue = source ? parseTimeValue(source.end_time || source.end || '') : '';
                            const breakStartValue = source ? parseTimeValue(source.break_start || source.breakStart || '') : '';
                            const breakEndValue = source ? parseTimeValue(source.break_end || source.breakEnd || '') : '';

                            if (!startValue || !endValue) return;
                            cb.checked = true;
                            const startEl = document.getElementById('gf_day_' + idx + '_start');
                            const endEl = document.getElementById('gf_day_' + idx + '_end');
                            const breakStartEl = document.getElementById('gf_day_' + idx + '_break_start');
                            const breakEndEl = document.getElementById('gf_day_' + idx + '_break_end');
                            if (startEl) startEl.value = startValue;
                            if (endEl) endEl.value = endValue;
                            if (breakStartEl) breakStartEl.value = breakStartValue;
                            if (breakEndEl) breakEndEl.value = breakEndValue;
                            toggleGfDayRow(idx);
                        });

                        openModal('generateFixedModal');
                    })
                    .catch(err => {
                        console.error('Error loading employee schedule for generate modal', err);
                        if (disp) {
                            disp.innerText = (typeof id === 'string' ? id : 'Employee ID: ' + id);
                        }
                        showToast('Unable to load the employee schedule for editing.', 'error');
                        openModal('generateFixedModal');
                    });
            }

            if (typeof closeModal !== 'function') {
                window.closeModal = function(modalId) {
                    try {
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.style.display = 'none';
                            document.body.classList.remove('modal-open');
                        }
                    } catch (e) { console.error('closeModal (defensive) error', e); }
                };
            }

            // Make sure the Create Shift button always opens a modal
            if (typeof openCreateShiftModal !== 'function') {
                window.openCreateShiftModal = function() { openModal('createShiftModal'); };
            }
        


        function openModal(modalId) {
            try {
                const modal = document.getElementById(modalId);
                    if (!modal) return;

                    if (modal.parentElement !== document.body) {
                        try { document.body.appendChild(modal); } catch (e) { /* ignore */ }
                    }

                    modal.style.display = 'flex';
                    modal.style.position = 'fixed';
                    modal.style.inset = '0';
                    modal.style.zIndex = '99999';
                    document.body.classList.add('modal-open');
                    if (modal.__overlayHandler) {
                        window.removeEventListener('resize', modal.__overlayHandler);
                        window.removeEventListener('scroll', modal.__overlayHandler, true);
                    }
                    modal.__overlayHandler = null;
                } catch (err) {
                    console.error('openModal error:', err);
                }
            }

            function closeModal(modalId) {
                try {
                    const modal = document.getElementById(modalId);
                    if (!modal) return;
                document.body.classList.remove('modal-open');

                if (modal.__overlayHandler) {
                    window.removeEventListener('resize', modal.__overlayHandler);
                    window.removeEventListener('scroll', modal.__overlayHandler, true);
                    delete modal.__overlayHandler;
                }

                const forms = modal.querySelectorAll('form');
                forms.forEach(form => form.reset());

                modal.style.position = 'fixed';
                modal.style.inset = '0';
                modal.style.zIndex = '99999';
                if (modalId === 'assignmentModal') {
                    assignmentMode = 'create';
                    selectedEmployeeForEdit = null;
                    const actionBtn = document.getElementById('assignmentModalActionButton');
                    const headerTitle = document.querySelector('#assignmentModal .modal-header h2');
                    const container = document.getElementById('employeeListContainer');
                    if (headerTitle) headerTitle.innerHTML = '<i class="fas fa-user-check"></i> Assign Shift to Employees';
                    if (actionBtn) { actionBtn.innerHTML = '<i class="fas fa-check"></i> Assign to Selected'; actionBtn.dataset.action = 'assign'; }
                    if (container) container.style.display = 'none';
                    const searchFilterContainer = document.getElementById('employeeSearchFilterContainer');
                    const selectedDisplay = document.getElementById('selectedEmployeeDisplay');
                    if (searchFilterContainer) searchFilterContainer.style.display = '';
                    if (selectedDisplay) selectedDisplay.style.display = 'none';
                }

                modal.style.display = 'none';
            } catch (err) {
                console.error('closeModal error:', err);
            }
        }

        // ============ MULTI-SELECT EMPLOYEE FUNCTIONS ============
        let allEmployees = [];
        let filteredEmployees = [];
        let selectedEmployees = new Set();
        // assignmentMode: 'create' = multi-assign flow, 'edit' = editing a single employee
        let assignmentMode = 'create';
        let selectedEmployeeForEdit = null;

        // Load employees when modal opens
        function loadEmployeeList() {
            const empApiUrl = (window.__TA_API_ROOT || (window.__TA_ROOT ? window.__TA_ROOT + '/app/api' : '/hrms/hrms-capstone/modules/time/app/api')) + '/shifts/get_employees_for_shift.php';
            fetch(empApiUrl)
                .then(response => response.text())
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); } catch (err) { console.error('get_employees_for_shift returned non-JSON:', text); throw err; }
                    if (data.success) {
                        allEmployees = data.employees;
                        filteredEmployees = [...allEmployees];
                        renderEmployeeCheckboxes(filteredEmployees);
                    }
                })
                .catch(error => console.error('Error loading employees:', error));
        }

        // Render employee checkboxes
        function renderEmployeeCheckboxes(employees) {
            const container = document.getElementById('employeeCheckboxList');
            container.innerHTML = '';

            employees.forEach(emp => {
                const isChecked = selectedEmployees.has(emp.employee_id);
                const checkboxHTML = `
                    <div style="padding: 12px 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px;">
                        <input 
                            type="checkbox" 
                            id="emp_${emp.employee_id}" 
                            value="${emp.employee_id}" 
                            class="employee-checkbox"
                            ${isChecked ? 'checked' : ''}
                            onchange="updateSelectedEmployees()"
                            style="width: 18px; height: 18px; cursor: pointer;"
                        >
                        <label for="emp_${emp.employee_id}" style="flex: 1; cursor: pointer; margin: 0; display: flex; align-items: center; gap: 10px;">
                            <span style="font-weight: 600; color: #333;">${escapeHtml(emp.full_name)}</span>
                            <span style="color: #999; font-size: 12px;">${escapeHtml(emp.department || 'N/A')}</span>
                            ${emp.has_shift ? '<span style="background: #4CAF50; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">ASSIGNED</span>' : '<span style="background: #FF9800; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">UNASSIGNED</span>'}
                        </label>
                    </div>
                `;
                container.innerHTML += checkboxHTML;
            });

            if (employees.length === 0) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">No employees found</div>';
            }
        }

        // Update selected employees and count
        function updateSelectedEmployees() {
            selectedEmployees.clear();
            document.querySelectorAll('.employee-checkbox:checked').forEach(checkbox => {
                selectedEmployees.add(checkbox.value);
            });
            const selectedCountEl = document.getElementById('selectedCount');
            if (selectedCountEl) selectedCountEl.textContent = selectedEmployees.size;
        }

        // Filter employees by search term
        function filterEmployeeList(searchTerm) {
            const term = safeLower(searchTerm || '').trim();
            const container = document.getElementById('employeeListContainer');
            if (!container) return;

            filteredEmployees = allEmployees.filter(emp => 
                safeLower(emp.full_name).includes(term) || 
                safeLower(emp.department).includes(term)
            );

            // Show container only if there's search text
            if (term.length > 0) {
                container.style.display = 'block';
                renderEmployeeCheckboxes(filteredEmployees);
            } else {
                container.style.display = 'none';
            }
            
            // Re-check previously selected employees
            selectedEmployees.forEach(empId => {
                const checkbox = document.getElementById('emp_' + empId);
                if (checkbox) checkbox.checked = true;
            });
        }

        // Filter employees by assignment status
        function filterEmployeeByStatus(status) {
            const container = document.getElementById('employeeListContainer');
            
            if (status === '') {
                container.style.display = 'none';
                filteredEmployees = [...allEmployees];
            } else if (status === 'assigned') {
                container.style.display = 'block';
                filteredEmployees = allEmployees.filter(emp => emp.has_shift);
            } else if (status === 'unassigned') {
                container.style.display = 'block';
                filteredEmployees = allEmployees.filter(emp => !emp.has_shift);
            }
            renderEmployeeCheckboxes(filteredEmployees);
            // Re-check previously selected employees
            selectedEmployees.forEach(empId => {
                const checkbox = document.getElementById('emp_' + empId);
                if (checkbox) checkbox.checked = true;
            });
        }

        // Search employees
        function searchEmployees() {
            const searchTerm = document.getElementById('employeeSearchInput').value;
            const container = document.getElementById('employeeListContainer');
            
            if (searchTerm.length > 0) {
                container.style.display = 'block';
                filterEmployeeList(searchTerm);
            } else {
                container.style.display = 'none';
            }
        }

        // Assign shift to multiple employees
        function assignMultipleEmployees() {
            if (selectedEmployees.size === 0) {
                alert('Please select at least one employee');
                return;
            }

            const shiftId = document.getElementById('shift_id').value;
            const effectiveFrom = document.getElementById('effective_from').value;
            const effectiveTo = document.getElementById('effective_to').value;
            const excludeSaturday = document.getElementById('exclude_saturday').checked;

            if (!shiftId) {
                alert('Please select a shift');
                return;
            }

            if (!effectiveFrom) {
                alert('Please select an effective from date');
                return;
            }

            // Send request to assign shift to multiple employees using template assign API
            const payload = {
                shift_id: parseInt(shiftId, 10),
                employees: Array.from(selectedEmployees).map(x => parseInt(x,10)),
                start_date: effectiveFrom,
                end_date: effectiveTo || effectiveFrom,
                exclude_saturday: excludeSaturday ? 1 : 0
            };

            fetch(TIME_API_ROOT + '/shifts/assign_to_template.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try { data = JSON.parse(text); } catch (err) { console.error('assign_shift_multiple returned non-JSON:', text); throw err; }
                if (data.success) {
                    alert('Shifts assigned successfully to ' + selectedEmployees.size + ' employee(s)');
                    closeModal('assignmentModal');
                    location.reload(); // Reload to see updates
                } else {
                    alert('Error: ' + (data.message || 'Failed to assign shifts'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error assigning shifts: ' + error.message);
            });
        }

        // Generic handler for assignment modal primary action
        function handleAssignmentModalAction() {
            const actionBtn = document.getElementById('assignmentModalActionButton');
            const mode = actionBtn && actionBtn.dataset && actionBtn.dataset.action ? actionBtn.dataset.action : (assignmentMode === 'edit' ? 'update' : 'assign');
            if (mode === 'assign') {
                assignMultipleEmployees();
            } else {
                updateEmployeeAssignment();
            }
        }

        // Update assignment for a single employee (frontend-only; backend endpoint may need to support this action)
        function updateEmployeeAssignment() {
            if (!selectedEmployeeForEdit) {
                alert('No employee selected for update');
                return;
            }

            const shiftId = document.getElementById('shift_id').value;
            const effectiveFrom = document.getElementById('effective_from').value;
            const effectiveTo = document.getElementById('effective_to').value;
            const excludeSaturday = document.getElementById('exclude_saturday').checked;

            if (!shiftId) {
                alert('Please select a shift');
                return;
            }

            if (!effectiveFrom) {
                alert('Please select an effective from date');
                return;
            }

            const formData = new FormData();
            formData.append('employee_id', selectedEmployeeForEdit);
            formData.append('shift_id', shiftId);
            formData.append('effective_from', effectiveFrom);
            formData.append('effective_to', effectiveTo || null);
            formData.append('exclude_saturday', excludeSaturday ? '1' : '0');

            fetch(TIME_API_ROOT + '/update_employee_assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try { data = JSON.parse(text); } catch (err) { console.error('assign_shift_multiple(update) returned non-JSON:', text); throw err; }
                if (data.success) {
                    alert('Assignment updated successfully');
                    closeModal('assignmentModal');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update assignment'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating assignment: ' + error.message);
            });
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text || '').replace(/[&<>"']/g, m => map[m] || m);
        }

        // Initialize employee list when modal opens
        const originalOpenModal = openModal;
        openModal = function(modalId) {
            if (modalId === 'assignmentModal') {
                loadEmployeeList();
                const actionBtn = document.getElementById('assignmentModalActionButton');
                const headerTitle = document.querySelector('#assignmentModal .modal-header h2');
                const container = document.getElementById('employeeListContainer');
                const searchFilterContainer = document.getElementById('employeeSearchFilterContainer');

                const selectedDisplay = document.getElementById('selectedEmployeeDisplay');
                const searchInput = document.getElementById('employeeSearchInput');
                const multiLabel = document.getElementById('employeeMultiLabel');
                const selectedSummaryEl = document.getElementById('selectedSummary');
                if (assignmentMode === 'create') {
                    selectedEmployees.clear();
                    const selectedCountEl = document.getElementById('selectedCount');
                    if (selectedCountEl) selectedCountEl.textContent = '0';
                    if (container) container.style.display = 'none';
                    if (searchFilterContainer) searchFilterContainer.style.display = '';
                    if (searchInput) searchInput.style.display = '';
                    if (selectedDisplay) selectedDisplay.style.display = 'none';
                    if (multiLabel) multiLabel.style.display = '';
                    if (selectedSummaryEl) selectedSummaryEl.innerHTML = '<strong>Selected:</strong> <span id="selectedCount">0</span> employee(s)';
                    if (headerTitle) headerTitle.innerHTML = '<i class="fas fa-user-check"></i> Assign Shift to Employees';
                    if (actionBtn) { actionBtn.innerHTML = '<i class="fas fa-check"></i> Assign to Selected'; actionBtn.dataset.action = 'assign'; }
                } else if (assignmentMode === 'edit') {
                    // preselect single employee and hide the multi-select list
                    selectedEmployees = new Set([selectedEmployeeForEdit]);
                    const selectedCountEl = document.getElementById('selectedCount');
                    if (selectedCountEl) selectedCountEl.textContent = '1';
                    if (container) container.style.display = 'none';
                    if (searchFilterContainer) searchFilterContainer.style.display = 'none';
                    if (searchInput) searchInput.style.display = 'none';
                    if (selectedDisplay) selectedDisplay.style.display = 'block';
                    if (multiLabel) multiLabel.style.display = 'none';
                    if (headerTitle) headerTitle.innerHTML = '<i class="fas fa-user-edit"></i> Edit Employee Assignment';
                    if (actionBtn) { actionBtn.innerHTML = '<i class="fas fa-save"></i> Update Assignment'; actionBtn.dataset.action = 'update'; }
                    const selField = document.getElementById('selected_employees');
                    if (selField) selField.value = JSON.stringify([selectedEmployeeForEdit]);
                    // Prefill form fields from normalized assignment data if available and show selected employee
                    try {
                        const emp = (employeeAssignmentData || []).find(e => String(e.employee_id) === String(selectedEmployeeForEdit));
                        const selectedDisplay = document.getElementById('selectedEmployeeDisplay');
                        if (selectedDisplay) {
                            selectedDisplay.textContent = emp ? (emp.employee || emp.full_name || emp.employee_id) : ('Employee ID: ' + selectedEmployeeForEdit);
                        }
                        if (emp && emp.assignments && emp.assignments.length > 0) {
                            const activeAssign = emp.assignments.find(a => a.isActive) || emp.assignments[0];
                            if (activeAssign) {
                                const shiftSel = document.getElementById('shift_id');
                                if (shiftSel && activeAssign.shift_id) shiftSel.value = activeAssign.shift_id;
                                const effFrom = document.getElementById('effective_from');
                                if (effFrom && activeAssign.effective_from) effFrom.value = (activeAssign.effective_from || '').split(' ')[0];
                                const effTo = document.getElementById('effective_to');
                                if (effTo) effTo.value = activeAssign.effective_to ? (activeAssign.effective_to.split ? activeAssign.effective_to.split(' ')[0] : activeAssign.effective_to) : '';
                                const excl = document.getElementById('exclude_saturday');
                                if (excl) {
                                    excl.checked = !!activeAssign.exclude_saturday || false;
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error pre-filling assignment edit form:', err);
                    }
                }
            } else if (modalId === 'generateFixedModal') {
                const suggestions = document.getElementById('gf_employee_suggestions');
                if (suggestions) suggestions.style.display = 'none';
            }
            originalOpenModal(modalId);
        };

        function openCreateShiftModal() {
            // Reset the form completely
            const form = document.querySelector('#createShiftModal .shift-form');
            if (form) {
                document.getElementById('shift_name').value = '';
                document.getElementById('create_start_date').value = '';
                document.getElementById('create_end_date').value = '';
            }
            openModal('createShiftModal');
            try {
                const today = new Date().toISOString().split('T')[0];
                const startInput = document.getElementById('create_start_date');
                const endInput = document.getElementById('create_end_date');
                if (startInput) { startInput.value = today; startInput.setAttribute('min', today); }
                if (endInput) { endInput.value = today; endInput.setAttribute('min', today); }
            } catch (e) { /* ignore */ }
        }

        // Create shift: weekday toggles for create modal
        function attachDayToggles(prefix) {
            for (let d = 1; d <= 6; d++) {
                const cb = document.getElementById(prefix + '_day_' + d + '_enabled');
                const controls = document.getElementById(prefix + '_day_' + d + '_controls');
                if (!cb || !controls) continue;
                cb.addEventListener('change', function() {
                    controls.style.display = cb.checked ? 'grid' : 'none';
                });
            }
        }

        (function initializeDayToggles() {
            attachDayToggles('create');
            attachDayToggles('edit');
        })();

        // Handle Create Shift form submit
        const createShiftForm = document.getElementById('createShiftForm');
        if (createShiftForm) {
            createShiftForm.addEventListener('submit', function(ev) {
                ev.preventDefault();
                createAndAssignShift();
            });
        }

        function createAndAssignShift() {
            const shiftName = document.getElementById('shift_name').value.trim();
            const startDate = document.getElementById('create_start_date').value;
            const endDate = document.getElementById('create_end_date').value;
            if (!shiftName) { alert('Shift name is required'); return; }
            if (!startDate || !endDate) { alert('Please set effective start and end dates'); return; }

            const weekdays = {};
            for (let d = 1; d <= 6; d++) {
                const enabled = document.getElementById('create_day_' + d + '_enabled').checked;
                if (!enabled) continue;
                const s = document.getElementById('create_day_' + d + '_start').value;
                const e = document.getElementById('create_day_' + d + '_end').value;
                const bs = document.getElementById('create_day_' + d + '_break_start').value || null;
                const be = document.getElementById('create_day_' + d + '_break_end').value || null;
                if (!s || !e) { alert('Start and end time required for selected weekdays'); return; }
                weekdays[d] = { assigned: 1, start: s, end: e };
                if (bs || be) { weekdays[d].break_start = bs; weekdays[d].break_end = be; }
            }

            const payload = { shift_name: shiftName, weekdays: weekdays, start_date: startDate, end_date: endDate };

            fetch(TIME_API_ROOT + '/shifts/create_and_assign.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    alert('Shift created and assigned: ' + (res.assigned_rows || 0) + ' rows');
                    closeModal('createShiftModal');
                    location.reload();
                } else {
                    alert('Error: ' + (res.error || res.message || 'Unknown'));
                }
            }).catch(err => { console.error(err); alert('Network or server error'); });
        }

        function openEditShiftModalSafe(shiftId, shiftName, startTime, endTime, breakDuration, description, isActive, excludeSaturday) {
            try {
                // Ensure values are the correct type
                const params = {
                    shiftId: parseInt(shiftId),
                    shiftName: String(shiftName),
                    startTime: String(startTime),
                    endTime: String(endTime),
                    breakDuration: parseInt(breakDuration) || 0,
                    description: String(description),
                    isActive: Boolean(isActive && isActive !== 'false'),
                    excludeSaturday: Boolean(excludeSaturday && excludeSaturday !== 'false')
                };
                
                // Populate the edit modal with current values
                const editForm = document.getElementById('editShiftModal');
                if (!editForm) {
                    console.error('Edit shift modal not found');
                    return;
                }
                
                document.getElementById('edit_shift_id').value = params.shiftId;
                document.getElementById('edit_shift_name').value = params.shiftName;
                document.getElementById('edit_start_time').value = params.startTime;
                document.getElementById('edit_end_time').value = params.endTime;
                document.getElementById('edit_break_duration').value = params.breakDuration;
                document.getElementById('edit_description').value = params.description;
                
                console.log('Edit modal populated:', params);
                
                // Open the modal
                openModal('editShiftModal');
            } catch (error) {
                console.error('Error opening edit modal:', error);
            }
        }

        function openEditShiftModalFromButton(button) {
            try {
                const editData = JSON.parse(button.dataset.shiftEdit);
                openEditShiftModalSafe(...editData);
            } catch (error) {
                console.error('Error reading shift data:', error);
            }
        }

        // Flexible schedule edit functions removed from this view.

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.shift-tab');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }

            // Add active class to clicked button (only if event exists and has a target)
            if (event && event.target && event.target.classList) {
                event.target.classList.add('active');
            }
        }

        // Flexible Schedule: Toggle repeat end date field
        document.addEventListener('DOMContentLoaded', function() {
            const repeatUntilCheckbox = document.getElementById('flex_repeat_until');
            const repeatUntilContainer = document.getElementById('flex_repeat_until_container');
            const editRepeatUntilCheckbox = document.getElementById('edit_flex_repeat_until');
            const editRepeatUntilContainer = document.getElementById('edit_flex_repeat_until_container');
            
            const contractEndCheckbox = document.getElementById('flex_contract_end');
            const contractEndContainer = document.getElementById('flex_contract_end_container');
            const editContractEndCheckbox = document.getElementById('edit_flex_contract_end');
            const editContractEndContainer = document.getElementById('edit_flex_contract_end_container');

            if (repeatUntilCheckbox) {
                repeatUntilCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        repeatUntilContainer.style.display = 'block';
                        document.getElementById('flex_repeat_end_date').focus();
                    } else {
                        repeatUntilContainer.style.display = 'none';
                        document.getElementById('flex_repeat_end_date').value = '';
                    }
                });
            }

            if (editRepeatUntilCheckbox) {
                editRepeatUntilCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        editRepeatUntilContainer.style.display = 'block';
                        document.getElementById('edit_flex_repeat_end_date').focus();
                    } else {
                        editRepeatUntilContainer.style.display = 'none';
                        document.getElementById('edit_flex_repeat_end_date').value = '';
                    }
                });
            }
            
            if (contractEndCheckbox) {
                contractEndCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        contractEndContainer.style.display = 'block';
                        document.getElementById('flex_contract_end_date').focus();
                    } else {
                        contractEndContainer.style.display = 'none';
                        document.getElementById('flex_contract_end_date').value = '';
                    }
                });
            }

            if (editContractEndCheckbox) {
                editContractEndCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        editContractEndContainer.style.display = 'block';
                        document.getElementById('edit_flex_contract_end_date').focus();
                    } else {
                        editContractEndContainer.style.display = 'none';
                        document.getElementById('edit_flex_contract_end_date').value = '';
                    }
                });
            }

            // Set minimum date to today
            const dateInput = document.getElementById('flex_date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
                dateInput.value = today;
            }

            const editDateInput = document.getElementById('edit_flex_date');
            if (editDateInput) {
                const today = new Date().toISOString().split('T')[0];
                editDateInput.setAttribute('min', today);
            }

            // Auto-switch to flexible tab removed — flexible UI deprecated in this view
        });

        // Flexible schedule client-side features removed from this view.
    


        // Assignment Table Data
        let assignmentTableData = (window.__TA_CONFIG || {}).assignments || [];

        // Shifts data exported from server to client to resolve shift_id -> name/time
        const shiftsArray = (window.__TA_CONFIG || {}).shifts || [];
        const shiftsMap = {};
        (shiftsArray || []).forEach(s => {
            shiftsMap[String(s.shift_id ?? s.shiftId ?? s.id)] = s;
        });

        function formatTime(hms) {
            if (!hms) return '';
            // Accept 'HH:MM:SS' or 'HH:MM'
            const parts = String(hms).split(':');
            if (parts.length < 2) return hms;
            let hour = parseInt(parts[0], 10);
            const minute = parts[1].padStart(2, '0');
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12;
            if (hour === 0) hour = 12;
            return `${hour}:${minute} ${ampm}`;
        }

        function formatTimeRange(start, end) {
            if (!start && !end) return '';
            if (!start) return formatTime(end);
            if (!end) return formatTime(start);
            return `${formatTime(start)} - ${formatTime(end)}`;
        }

        function padDateSegment(value) {
            return String(value).padStart(2, '0');
        }

        function formatDateISO(date) {
            return `${date.getFullYear()}-${padDateSegment(date.getMonth() + 1)}-${padDateSegment(date.getDate())}`;
        }

        function getCurrentWeekRange(referenceDate = new Date()) {
            const date = new Date(referenceDate);
            const day = date.getDay();
            const monday = new Date(date);
            monday.setDate(date.getDate() - ((day + 6) % 7));
            const saturday = new Date(monday);
            saturday.setDate(monday.getDate() + 5);
            return { start: formatDateISO(monday), end: formatDateISO(saturday) };
        }

        function parseTimeValue(value) {
            if (!value) return '';
            if (value.length >= 5 && value.indexOf(':') === 2) {
                return value.slice(0, 5);
            }
            const parsed = value.split(' '); // handle datetime strings
            return parsed.length > 0 ? parsed[0].slice(0, 5) : value.slice(0, 5);
        }

        let employeeAssignmentData = [];
        let assignmentCurrentPage = 1;
        let assignmentPageSize = 10;
        let assignmentSortField = 'employee';
        let assignmentSortAsc = true;
        let assignmentFilterText = '';
        let selectedEmployeeIdForEdit = null;

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (match) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match] || match));
        }

        function escapeJs(value) {
            return String(value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, ' ');
        }

        function showScheduleDetail(detailText) {
            if (typeof detailText !== 'string' || detailText.trim() === '') {
                detailText = 'No schedule details available.';
            }
            const body = document.getElementById('scheduleDetailModalBody');
            if (body) {
                body.innerHTML = '<div style="display:grid; gap:12px;">' +
                    '<div style="padding:14px 16px; background:#f4f7fb; border-radius:8px; color:#263238; line-height:1.6;">' +
                    escapeHtml(detailText) +
                    '</div>' +
                    '</div>';
            }
            openModal('scheduleDetailModal');
        }

        function safeLower(value) {
            return String(value || '').toLowerCase();
        }

        function normalizeAssignmentData() {
            const employees = {};

            assignmentTableData.forEach(row => {
                const empId = String(row.employee_id ?? row.employee ?? row.employeeId ?? '');
                const employeeName = row.employee || row.full_name || row.name || 'Unknown';
                const departmentName = row.department || row.dept || '';
                const isActive = row.isActive || row.is_active === 1 || row.is_active === '1' || row.is_active === true;
                const rowStatus = row.status || (isActive ? 'Active' : 'Scheduled');

                if (!employees[empId]) {
                    employees[empId] = {
                        employee_id: empId,
                        employee: employeeName,
                        department: departmentName,
                        shift_count: 0,
                        active_count: 0,
                        status: rowStatus,
                        assignments: []
                    };
                }

                // Recurring schedules can produce one assignment row per date.
                // The employee-level view represents that recurring assignment as one shift.
                employees[empId].shift_count = 1;
                if (isActive) {
                    employees[empId].active_count = 1;
                    employees[empId].status = 'Active';
                }

                // Normalize assignment row and attach shift details when available
                const assign = Object.assign({}, row);
                const sid = String((row.shift_id ?? row.shiftId ?? row.shift) || '');
                const shiftInfo = shiftsMap[sid] || {};
                const assignedShiftName = shiftInfo.shift_name || row.shift || '';
                assign.shift = /^custom\b/i.test(assignedShiftName)
                    ? `${employeeName}'s Shift`
                    : assignedShiftName;
                assign.shift_id = sid || (row.shift_id || row.shiftId || '');
                assign.time = formatTimeRange(shiftInfo.start_time || shiftInfo.start_time, shiftInfo.end_time || shiftInfo.end_time) || row.time || '';
                assign.from = shiftInfo.start_time || row.from || row.start || '';
                assign.to = shiftInfo.end_time || row.to || row.end || '';
                assign.isActive = isActive;
                assign.status = row.status || (isActive ? 'Active' : 'Scheduled');

                employees[empId].assignments.push(assign);
            });

            employeeAssignmentData = Object.values(employees);
        }

        function renderAssignmentTable() {
            const filtered = employeeAssignmentData.filter(row => {
                const searchTerm = safeLower(assignmentFilterText);
                const employeeName = String(row.employee || row.full_name || '');
                const departmentName = String(row.department || '');
                const matchesEmployee = safeLower(employeeName).includes(searchTerm);
                const matchesDepartment = safeLower(departmentName).includes(searchTerm);
                const matchesShift = row.assignments.some(assign => {
                    const shiftText = String(assign.shift || assign.shift_name || assign.shiftName || '');
                    return safeLower(shiftText).includes(searchTerm);
                });
                return matchesEmployee || matchesDepartment || matchesShift;
            });

            const sorted = [...filtered];
            sorted.sort((a, b) => {
                let aVal = a[assignmentSortField];
                let bVal = b[assignmentSortField];

                if (assignmentSortField === 'shift_count' || assignmentSortField === 'active_count') {
                    aVal = parseInt(aVal, 10);
                    bVal = parseInt(bVal, 10);
                }

                if (aVal < bVal) return assignmentSortAsc ? -1 : 1;
                if (aVal > bVal) return assignmentSortAsc ? 1 : -1;
                return 0;
            });

            const totalRecords = sorted.length;
            const totalPages = Math.ceil(totalRecords / assignmentPageSize);
            if (assignmentCurrentPage > totalPages && totalPages > 0) assignmentCurrentPage = totalPages;

            const start = (assignmentCurrentPage - 1) * assignmentPageSize;
            const end = start + assignmentPageSize;
            const pageData = sorted.slice(start, end);

            let html = '';
            if (pageData.length === 0) {
                html = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 12px;"></i>No employee assignments found.</td></tr>';
            } else {
                pageData.forEach(row => {
                    const employeeIdSafe = String(row.employee_id || '');
                    const employeeIdJson = JSON.stringify(employeeIdSafe);
                    const employeeIdJsonEscaped = escapeHtml(employeeIdJson);
                    html += `<tr>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-circle" style="font-size: 20px; color: #3498db;"></i>
                                    <span>${escapeHtml(row.employee)}</span>
                                </div>
                                <span style="font-size: 12px; color: #777;">${escapeHtml(row.department || 'No department')}</span>
                            </div>
                        </td>
                        <td>${row.shift_count}</td>
                        <td>${row.active_count}</td>
                        <td><span class="shift-status ${row.status === 'Active' ? '' : 'inactive'}">${row.status}</span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick='openEmployeeShiftModal(${employeeIdJsonEscaped})' style="padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>`;
                });
            }

            document.getElementById('assignmentTableBody').innerHTML = html;
            updateAssignmentPagination(totalRecords, totalPages);
        }

        function updateAssignmentPagination(total, pages) {
            document.getElementById('assignmentInfo').textContent = total === 0 ? 'Showing 0 of 0 records' : `Showing ${Math.min((assignmentCurrentPage - 1) * assignmentPageSize + 1, total)} to ${Math.min(assignmentCurrentPage * assignmentPageSize, total)} of ${total} records`;
            const pageNumbers = document.getElementById('assignmentPageNumbers');
            pageNumbers.innerHTML = '';
            for (let i = Math.max(1, assignmentCurrentPage - 2); i <= Math.min(pages, assignmentCurrentPage + 2); i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = i;
                btn.style.cssText = `padding: 6px 12px; border: 2px solid ${i === assignmentCurrentPage ? '#003d82' : '#ddd'}; background: ${i === assignmentCurrentPage ? '#003d82' : 'white'}; color: ${i === assignmentCurrentPage ? 'white' : '#333'}; border-radius: 6px; cursor: pointer; font-weight: ${i === assignmentCurrentPage ? '600' : '400'};`;
                btn.onclick = () => { assignmentCurrentPage = i; renderAssignmentTable(); };
                pageNumbers.appendChild(btn);
            }
            document.getElementById('prevAssignBtn').disabled = assignmentCurrentPage === 1;
            document.getElementById('nextAssignBtn').disabled = assignmentCurrentPage === pages || pages === 0;
        }

        function nextAssignmentPage() {
            const pages = Math.ceil(employeeAssignmentData.length / assignmentPageSize);
            if (assignmentCurrentPage < pages) assignmentCurrentPage++;
            renderAssignmentTable();
        }

        function previousAssignmentPage() {
            if (assignmentCurrentPage > 1) assignmentCurrentPage--;
            renderAssignmentTable();
        }

        function changeAssignmentPageSize() {
            assignmentPageSize = parseInt(document.getElementById('assignmentPerPage').value, 10);
            assignmentCurrentPage = 1;
            renderAssignmentTable();
        }

        function sortAssignments(field) {
            if (assignmentSortField === field) {
                assignmentSortAsc = !assignmentSortAsc;
            } else {
                assignmentSortField = field;
                assignmentSortAsc = true;
            }
            assignmentCurrentPage = 1;
            renderAssignmentTable();
        }

        function resetAssignmentFilters() {
            assignmentFilterText = '';
            document.getElementById('assignmentSearch').value = '';
            assignmentCurrentPage = 1;
            assignmentSortField = 'employee';
            assignmentSortAsc = true;
            renderAssignmentTable();
        }

        function openEmployeeShiftModal(employeeId) {
            const employee = employeeAssignmentData.find(row => row.employee_id === employeeId);
            if (!employee) return;

            console.log('openEmployeeShiftModal - employee:', employee);

            const detailsHtmlParts = [];

            detailsHtmlParts.push(
                '<div style="padding-bottom: 20px;">' +
                '<p style="margin: 0 0 10px; color: #555;">Employee: <strong>' + escapeHtml(employee.employee) + '</strong></p>' +
                '<p style="margin: 0; color: #555;">Department: <strong>' + escapeHtml(employee.department || 'N/A') + '</strong></p>' +
                '</div>'
            );

            detailsHtmlParts.push('<div style="margin-bottom: 20px;">');
            detailsHtmlParts.push('<div style="margin-bottom: 12px; font-weight: 700; color: #333;">Assigned Days</div>');

            const dayAbbr = ['Su', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
            const scheduleRows = (employee.assignments || []).map(a => {
                const explicitDay = a.day_of_week !== undefined && a.day_of_week !== null && !isNaN(Number(a.day_of_week)) ? Number(a.day_of_week) : null;
                const dateValue = a.effective_from || a.schedule_date || a.date || a.shift_date || a.day_date || a.date_assigned || '';
                let dayIndex = explicitDay;
                if (dayIndex === null && dateValue) {
                    const dateParts = String(dateValue).split(/[ T-]/).slice(0, 3);
                    if (dateParts.length === 3 && /^\d{4}$/.test(dateParts[0])) {
                        const dateObject = new Date(Number(dateParts[0]), Number(dateParts[1]) - 1, Number(dateParts[2]));
                        if (!isNaN(dateObject.getTime())) dayIndex = dateObject.getDay();
                    }
                }
                const label = dayIndex !== null && dayIndex >= 0 && dayIndex <= 6 ? dayAbbr[dayIndex] : '—';
                const dateNoteText = dateValue ? ` ${String(dateValue).split(' ')[0]}` : '';
                const scheduleStart = a.start_time || a.start || a.from || a.shift_start || a.schedule_start || '';
                const scheduleEnd = a.end_time || a.end || a.to || a.shift_end || a.schedule_end || '';
                const startText = scheduleStart ? formatTime(scheduleStart) : '—';
                const endText = scheduleEnd ? formatTime(scheduleEnd) : '—';
                const isActiveFlag = a.isActive || a.is_active === 1 || a.is_active === '1' || false;
                const statusText = a.status || (isActiveFlag ? 'Active' : 'Scheduled');
                const sourceType = a.custom ? 'Custom Shift' : a.flexible ? 'Flexible Schedule' : 'Standard Shift';
                return {
                    labelHtml: '<div style="display:flex; flex-direction:column; gap:3px;"><span style="font-weight:600; color:#222;">' + escapeHtml(label) + '</span>' +
                        (dateNoteText ? '<span style="font-size:11px; color:#777;">' + escapeHtml(dateNoteText) + '</span>' : '') +
                        '</div>',
                    detailTitle: `${employee.employee} | Assigned ${label}${dateNoteText ? ' on ' + dateNoteText.trim() : ''} | ${a.shift || a.shift_name || sourceType} | ${startText} - ${endText}`,
                    startText,
                    endText,
                    statusText,
                    sourceType
                };
            });

            let scheduleHtml = '<div style="margin-bottom:14px;">';
            if (scheduleRows.length === 0) {
                scheduleHtml += '<div style="color:#777;">No scheduled shifts available for this employee.</div>';
            } else {
                scheduleHtml += '<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">';
                scheduleHtml += '<thead><tr style="background:#f4f6f8;">';
                scheduleHtml += '<th style="padding:10px; text-align:left; font-size:13px; color:#333;">Day</th>';
                scheduleHtml += '<th style="padding:10px; text-align:left; font-size:13px; color:#333;">Schedule Start</th>';
                scheduleHtml += '<th style="padding:10px; text-align:left; font-size:13px; color:#333;">Schedule End</th>';
                scheduleHtml += '<th style="padding:10px; text-align:left; font-size:13px; color:#333;">Status</th>';
                scheduleHtml += '<th style="padding:10px; text-align:left; font-size:13px; color:#333;">Details</th>';
                scheduleHtml += '</tr></thead><tbody>';

                scheduleRows.forEach(r => {
                    scheduleHtml += '<tr>' +
                        '<td style="padding:10px; border-bottom:1px solid #eaeaea; font-weight:600; color:#222;">' + r.labelHtml + '</td>' +
                        '<td style="padding:10px; border-bottom:1px solid #eaeaea;">' + escapeHtml(r.startText) + '</td>' +
                        '<td style="padding:10px; border-bottom:1px solid #eaeaea;">' + escapeHtml(r.endText) + '</td>' +
                        '<td style="padding:10px; border-bottom:1px solid #eaeaea;">' + escapeHtml(r.statusText) + '</td>' +
                        '<td style="padding:10px; border-bottom:1px solid #eaeaea; text-align:center;">' +
                            '<button type="button" onclick="showScheduleDetail(\'' + escapeJs(r.detailTitle) + '\')" style="background:none;border:none;padding:0;color:#1565c0;cursor:pointer;">' +
                                '<i class="fas fa-eye"></i>' +
                            '</button>' +
                        '</td>' +
                    '</tr>';
                });

                scheduleHtml += '</tbody></table>';
            }

            scheduleHtml += '</div>';
            detailsHtmlParts.push(scheduleHtml);
            detailsHtmlParts.push('</div>');

            const body = document.getElementById('employeeShiftModalBody');
            if (body) {
                body.innerHTML = detailsHtmlParts.join('');
            }

            const editButton = document.getElementById('employeeShiftModalEditButton');
            if (editButton) {
                editButton.style.display = 'inline-flex';
            }
            selectedEmployeeIdForEdit = employeeId;
            try { window.selectedEmployeeIdForEdit = employeeId; } catch (e) { /* ignore */ }
            openModal('employeeShiftModal');
        }

        function openAssignmentModalForEmployee() {
            if (!selectedEmployeeIdForEdit) {
                closeModal('employeeShiftModal');
                return;
            }
            // Enter single-employee edit mode
            assignmentMode = 'edit';
            selectedEmployeeForEdit = selectedEmployeeIdForEdit;
            selectedEmployees = new Set([selectedEmployeeForEdit]);
            const selectedCountEl = document.getElementById('selectedCount');
            if (selectedCountEl) selectedCountEl.textContent = '1';

            // Fetch employee details from server to ensure name/assignment are authoritative
            fetch(TIME_API_ROOT + '/get_employee_details.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams({ employee_id: selectedEmployeeForEdit })
            })
            .then(r => r.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('get_employee_details returned non-JSON:', text);
                    throw new Error('Server error when fetching employee details');
                }
                if (!data || !data.success) {
                    throw new Error(data && data.message ? data.message : 'Failed to fetch employee details');
                }

                // Fill display
                const emp = data.employee || null;
                const assignments = data.assignments || [];
                const sd = document.getElementById('selectedEmployeeDisplay');
                const summary = document.getElementById('selectedSummary');
                if (sd) {
                    const name = emp ? (emp.full_name || emp.full_name || emp.employee_id) : ('Employee ID: ' + selectedEmployeeForEdit);
                    const dept = emp ? (emp.department || '') : '';
                    sd.innerHTML = '<div style="display:flex; gap:10px; align-items:center;"><i class="fas fa-user-circle" style="font-size:18px;color:#1565c0"></i><div><div style="font-weight:700;color:#233e8b">' + escapeHtml(name) + '</div>' + (dept ? '<div style="font-size:12px;color:#777">' + escapeHtml(dept) + '</div>' : '') + '</div></div>';
                    sd.style.display = 'block';
                }
                if (summary && emp) {
                    summary.innerHTML = '<strong>Selected:</strong> ' + escapeHtml(emp.full_name || emp.employee_id);
                }

                // Prefill assignment fields if we have assignment data
                if (assignments && assignments.length > 0) {
                    const activeAssign = assignments.find(a => a.is_active == 1 || a.isActive) || assignments[0];
                    if (activeAssign) {
                        const shiftSel = document.getElementById('shift_id');
                        if (shiftSel && (activeAssign.shift_id || activeAssign.shiftId || activeAssign.shift)) {
                            shiftSel.value = activeAssign.shift_id || activeAssign.shiftId || activeAssign.shift;
                        }
                        const effFrom = document.getElementById('effective_from');
                        if (effFrom && activeAssign.effective_from) effFrom.value = (activeAssign.effective_from || '').split(' ')[0];
                        const effTo = document.getElementById('effective_to');
                        if (effTo && activeAssign.effective_to) effTo.value = (activeAssign.effective_to || '').split ? activeAssign.effective_to.split(' ')[0] : activeAssign.effective_to;
                        const excl = document.getElementById('exclude_saturday');
                        if (excl) excl.checked = !!(activeAssign.exclude_saturday || activeAssign.excludeSaturday || false);
                    }
                }

                // Open the assignment modal prefilled for edit
                openModal('assignmentModal');
                closeModal('employeeShiftModal');
            })
            .catch(err => {
                console.error('Error fetching employee details:', err);
                // Fallback: open modal without prefill
                loadEmployeeList();
                openModal('assignmentModal');
                closeModal('employeeShiftModal');
            });
        }

        const assignmentSearchInput = document.getElementById('assignmentSearch');
        if (assignmentSearchInput) {
            assignmentSearchInput.addEventListener('keyup', function() {
                assignmentFilterText = this.value || '';
                assignmentCurrentPage = 1;
                showAssignmentSuggestions();
                renderAssignmentTable();
            });
        }

        function showAssignmentSuggestions() {
            const searchBox = document.getElementById('assignmentSearch');
            const suggestionsBox = document.getElementById('assignmentSuggestions');
            if (!searchBox || !suggestionsBox) {
                return;
            }
            const query = safeLower(searchBox.value).trim();

            if (query.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }

            const suggestions = new Set();
            employeeAssignmentData.forEach(row => {
                const employeeName = String(row.employee || row.full_name || '');
                const departmentName = String(row.department || '');
                if (safeLower(employeeName).includes(query)) {
                    suggestions.add(employeeName);
                }
                if (safeLower(departmentName).includes(query)) {
                    suggestions.add(departmentName);
                }
                row.assignments.forEach(assign => {
                    const shiftText = String(assign.shift || assign.shift_name || assign.shiftName || '');
                    if (safeLower(shiftText).includes(query)) {
                        suggestions.add(assign.shift || assign.shift_name || assign.shiftName || '');
                    }
                });
            });

            if (suggestions.size === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }

            suggestionsBox.innerHTML = '';
            Array.from(suggestions).slice(0, 8).forEach(suggestion => {
                const item = document.createElement('div');
                item.style.cssText = 'padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;';
                item.innerHTML = `<i class="fas fa-search" style="color: #999; margin-right: 8px;"></i>${escapeHtml(suggestion)}`;
                item.onmouseover = () => item.style.background = '#f8f9fa';
                item.onmouseout = () => item.style.background = 'white';
                item.onclick = () => {
                    searchBox.value = suggestion;
                    assignmentFilterText = suggestion;
                    assignmentCurrentPage = 1;
                    renderAssignmentTable();
                    suggestionsBox.style.display = 'none';
                };
                suggestionsBox.appendChild(item);
            });

            suggestionsBox.style.display = 'block';
        }

        document.addEventListener('click', function(e) {
            const assignmentSuggestions = document.getElementById('assignmentSuggestions');
            if (!e.target.closest('#assignmentSearch') && !e.target.closest('#assignmentSuggestions') && assignmentSuggestions) {
                assignmentSuggestions.style.display = 'none';
            }
        });

        normalizeAssignmentData();
        renderAssignmentTable();

        // Load existing shift templates into templates table
        function loadShiftTemplates() {
            fetch('/hrms/hrms-capstone/modules/time/app/api/shifts/get_templates.php')
                .then(r => r.text())
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); } catch (err) { console.error('get_templates non-json', text); return; }
                    if (!data || !data.success) return;
                    renderTemplatesTable(data.templates || []);
                })
                .catch(err => console.error('Error loading templates:', err));
        }

        function renderTemplatesTable(templates) {
            const body = document.getElementById('templatesTableBody');
            if (!body) return;
            if (!templates || templates.length === 0) {
                body.innerHTML = '<tr><td colspan="4" style="padding:12px; color:#666;">No templates</td></tr>';
                return;
            }
            body.innerHTML = templates.map(t => {
                const id = t.shift_id || t.id || '';
                const name = t.shift_name || 'Template';
                const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const assignedDays = Array.isArray(t.weekdays)
                    ? t.weekdays.map(day => dayNames[Number(day)]).filter(Boolean)
                    : [];
                const days = assignedDays.length ? assignedDays.join(', ') : 'No weekdays assigned';
                const active = (t.is_active == 1 || t.is_active === '1') ? '<span style="color:green;">Active</span>' : '<span style="color:#999;">Inactive</span>';
                return `<tr><td>${escapeHtml(name)}</td><td>${escapeHtml(days)}</td><td>${active}</td><td><button class="btn btn-secondary" onclick="viewTemplate(${escapeHtml(id)})">View</button> <button class="btn btn-primary" onclick="openMultipleAssignForTemplate(${escapeHtml(id)})">Assign</button></td></tr>`;
            }).join('');
        }

        function viewTemplate(shiftId) {
            fetch('/hrms/hrms-capstone/modules/time/app/api/shifts/get_template_detail.php?shift_id=' + encodeURIComponent(shiftId))
                .then(r => r.text())
                .then(text => {
                    let data; try { data = JSON.parse(text); } catch (err) { console.error('get_template_detail non-json', text); return; }
                    if (!data || !data.success) { alert('Unable to load template'); return; }
                    openTemplateViewModal(data.template);
                }).catch(err => console.error(err));
        }

        let currentTemplateForEdit = null;

        function getCurrentWeekDates() {
            const today = new Date();
            const monday = new Date(today);
            const dayIndex = monday.getDay();
            const diff = dayIndex === 0 ? -6 : 1 - dayIndex;
            monday.setDate(monday.getDate() + diff);
            monday.setHours(0, 0, 0, 0);

            return Array.from({ length: 7 }, (_, index) => {
                const current = new Date(monday);
                current.setDate(monday.getDate() + index);
                return current;
            });
        }

        function formatTimeLabel(value) {
            if (!value) return '—';
            const text = String(value).trim();
            if (text.length <= 5) return text;
            return text.substring(0, 5);
        }

        function getTemplateScheduleRows(template) {
            const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const weekDays = getCurrentWeekDates();
            const weekdayConfig = template && template.weekdays ? template.weekdays : {};
            const rows = [];

            Object.keys(weekdayConfig).forEach(key => {
                const rawDay = Number(key);
                if (Number.isNaN(rawDay)) return;
                const normalizedDay = rawDay === 7 ? 0 : rawDay;
                const weekIndex = (normalizedDay + 6) % 7;
                const cfg = weekdayConfig[key] || {};
                const assigned = cfg.assigned === 1 || cfg.is_active === 1 || cfg.start || cfg.end;
                if (!assigned) return;

                const date = weekDays[weekIndex];
                const dateText = date ? date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';
                const startTime = formatTimeLabel(cfg.start || template.start_time || '');
                const endTime = formatTimeLabel(cfg.end || template.end_time || '');
                const breakStart = formatTimeLabel(cfg.break_start || cfg.breakStart || '');
                const breakEnd = formatTimeLabel(cfg.break_end || cfg.breakEnd || '');
                const breakText = breakStart !== '—' && breakEnd !== '—' ? `${breakStart} - ${breakEnd}` : '—';
                const dayName = dayNames[weekIndex] || `Day ${rawDay}`;

                rows.push({
                    dayName,
                    dateText,
                    startTime,
                    endTime,
                    breakText,
                    status: 'Scheduled'
                });
            });

            return rows.sort((a, b) => {
                const order = { Monday: 0, Tuesday: 1, Wednesday: 2, Thursday: 3, Friday: 4, Saturday: 5, Sunday: 6 };
                return (order[a.dayName] ?? 99) - (order[b.dayName] ?? 99);
            });
        }

        function openTemplateViewModal(template) {
            currentTemplateForEdit = template;
            const body = document.getElementById('templateViewBody');
            if (!body) return;

            const name = template.shift_name || template.shiftName || 'Template';
            const start = formatTimeLabel(template.start_time || '');
            const end = formatTimeLabel(template.end_time || '');
            const scheduleRows = getTemplateScheduleRows(template);
            const description = template.description || 'No description available.';

            const summaryHtml = `
                <div style="padding-bottom: 20px;">
                    <p style="margin: 0 0 8px; color: #555;">Shift Template: <strong>${escapeHtml(name)}</strong></p>
                    <p style="margin: 0 0 8px; color: #555;">Time: <strong>${escapeHtml(start)} - ${escapeHtml(end)}</strong></p>
                    <p style="margin: 0; color: #555;">Description: <strong>${escapeHtml(description)}</strong></p>
                </div>
            `;

            let rowsHtml = '';
            if (scheduleRows.length === 0) {
                rowsHtml = '<div style="padding: 18px 0; color: #777;">No scheduled days for this week.</div>';
            } else {
                rowsHtml = `
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                        <thead>
                            <tr style="background: #f4f6f8;">
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #333;">Day</th>
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #333;">Date</th>
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #333;">Shift Time</th>
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #333;">Break</th>
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #333;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${scheduleRows.map(row => `
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #eaeaea; font-weight: 600; color: #222;">${escapeHtml(row.dayName)}</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eaeaea;">${escapeHtml(row.dateText)}</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eaeaea;">${escapeHtml(row.startTime)} - ${escapeHtml(row.endTime)}</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eaeaea;">${escapeHtml(row.breakText)}</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eaeaea;">${escapeHtml(row.status)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            body.innerHTML = `
                <div>
                    ${summaryHtml}
                    <div style="margin-bottom: 20px;">
                        <div style="margin-bottom: 12px; font-weight: 700; color: #333;">This Week Schedule</div>
                        ${rowsHtml}
                    </div>
                </div>
            `;
            openModal('templateViewModal');
        }

        function openEditTemplateModal() {
            if (!currentTemplateForEdit) {
                alert('Template data not loaded yet. Please open the template view again.');
                return;
            }
            loadTemplateIntoEditModal(currentTemplateForEdit);
            closeModal('templateViewModal');
            openModal('editShiftModal');
        }

        function loadTemplateIntoEditModal(template) {
            document.getElementById('edit_shift_id').value = template.shift_id || '';
            document.getElementById('edit_shift_name').value = template.shift_name || '';
            document.getElementById('edit_start_time').value = template.start_time || '';
            document.getElementById('edit_end_time').value = template.end_time || '';
            document.getElementById('edit_break_duration').value = template.break_duration || '';
            document.getElementById('edit_description').value = template.description || '';

            // Prefill weekday template values when available
            const weekdayConfig = template.weekdays || {};
            for (let d = 1; d <= 6; d++) {
                const cfg = weekdayConfig[d] || weekdayConfig[String(d)] || {};
                const enabled = !!cfg.assigned || cfg.assigned === 1 || cfg.start || cfg.end;
                const checkbox = document.getElementById('edit_day_' + d + '_enabled');
                const controls = document.getElementById('edit_day_' + d + '_controls');
                if (checkbox) checkbox.checked = enabled;
                if (controls) controls.style.display = enabled ? 'grid' : 'none';
                const start = document.getElementById('edit_day_' + d + '_start');
                const end = document.getElementById('edit_day_' + d + '_end');
                const breakStart = document.getElementById('edit_day_' + d + '_break_start');
                const breakEnd = document.getElementById('edit_day_' + d + '_break_end');
                if (start) start.value = cfg.start || '';
                if (end) end.value = cfg.end || '';
                if (breakStart) breakStart.value = cfg.break_start || cfg.breakStart || '';
                if (breakEnd) breakEnd.value = cfg.break_end || cfg.breakEnd || '';
            }
        }

        function buildEditTemplateWeekdays() {
            const weekdays = {};
            for (let d = 1; d <= 6; d++) {
                const enabled = !!document.getElementById('edit_day_' + d + '_enabled')?.checked;
                const start = document.getElementById('edit_day_' + d + '_start')?.value || '';
                const end = document.getElementById('edit_day_' + d + '_end')?.value || '';
                const breakStart = document.getElementById('edit_day_' + d + '_break_start')?.value || null;
                const breakEnd = document.getElementById('edit_day_' + d + '_break_end')?.value || null;

                if (enabled) {
                    if (!start || !end) {
                        alert('Please set both start and end times for all enabled weekdays.');
                        return null;
                    }
                    weekdays[d] = {
                        assigned: 1,
                        start: start,
                        end: end,
                        break_start: breakStart,
                        break_end: breakEnd
                    };
                } else {
                    weekdays[d] = {
                        assigned: 0,
                        start: null,
                        end: null,
                        break_start: breakStart,
                        break_end: breakEnd
                    };
                }
            }
            return weekdays;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const editShiftForm = document.getElementById('editShiftForm');
            if (editShiftForm) {
                editShiftForm.addEventListener('submit', function(ev) {
                    ev.preventDefault();
                    saveTemplateUpdate();
                });
            }
        });

        function saveTemplateUpdate() {
            const shiftId = parseInt(document.getElementById('edit_shift_id').value, 10);
            const shiftName = document.getElementById('edit_shift_name').value.trim();
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;
            const breakDuration = parseInt(document.getElementById('edit_break_duration').value, 10) || null;
            const description = document.getElementById('edit_description').value.trim();

            if (!shiftId || !shiftName || !startTime || !endTime) {
                alert('Please complete the shift name, start time, and end time.');
                return;
            }

            const weekdays = buildEditTemplateWeekdays();
            if (weekdays === null) return;
            const payload = {
                shift_id: shiftId,
                shift_name: shiftName,
                start_time: startTime,
                end_time: endTime,
                break_duration: breakDuration,
                description: description,
                weekdays: weekdays
            };

            fetch(TIME_API_ROOT + '/shifts/update_template.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.text())
            .then(text => {
                let data;
                try { data = JSON.parse(text); } catch (err) { console.error('update_template returned non-JSON:', text); throw err; }
                if (data.success) {
                    alert('Template updated successfully');
                    closeModal('editShiftModal');
                } else {
                    alert('Error updating template: ' + (data.error || data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error updating template: ' + err.message);
            });
        }

        // Defensive binding: ensure update handler is attached even if DOMContentLoaded was missed
        (function bindEditShiftHandlers() {
            try {
                const form = document.getElementById('editShiftForm');
                if (form && !form._saveBound) {
                    form._saveBound = true;
                    form.addEventListener('submit', function(ev) {
                        ev.preventDefault();
                        console.log('[DEBUG] editShiftForm submit intercepted');
                        saveTemplateUpdate();
                    });
                }

                const btn = document.querySelector('#editShiftModal button[name="update_shift"]');
                if (btn && !btn._clickBound) {
                    btn._clickBound = true;
                    btn.addEventListener('click', function(ev) {
                        ev.preventDefault();
                        console.log('[DEBUG] update shift button clicked');
                        // Trigger the form submit flow
                        if (form) {
                            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                            form.dispatchEvent(submitEvent);
                        } else {
                            saveTemplateUpdate();
                        }
                    });
                }
            } catch (e) {
                console.error('bindEditShiftHandlers error', e);
            }
        })();

        function openMultipleAssign() {
            // open assignment modal in create mode
            assignmentMode = 'create';
            openModal('assignmentModal');
        }

        function renderFixedEmployeeDropdown(matches) {
            const dropdown = document.getElementById('gf_employee_dropdown');
            const input = document.getElementById('gf_employee_search');
            if (!dropdown || !input) return;

            dropdown.innerHTML = '';
            if (!matches || !matches.length) {
                dropdown.innerHTML = '<div style="padding: 12px; color: #777;">No employees found</div>';
                dropdown.style.display = input.value.trim() ? 'block' : 'none';
                dropdown.style.position = 'absolute';
                dropdown.style.zIndex = '2000';
                return;
            }

            matches.forEach(employee => {
                const item = document.createElement('div');
                const fullName = String(employee.full_name || `${employee.first_name || ''} ${employee.middle_name || ''} ${employee.last_name || ''}`)
                    .replace(/\s+/g, ' ')
                    .trim();
                const employeeCode = employee.employee_code || employee.employee_no || employee.employee_id || '';

                item.style.padding = '10px 12px';
                item.style.cursor = 'pointer';
                item.style.borderBottom = '1px solid #eee';
                item.innerHTML = `
                    <strong>${escapeHtml(fullName || 'Unknown employee')}</strong>
                    <br>
                    <small style="color:#777;">${escapeHtml(employeeCode)}</small>
                `;
                item.addEventListener('click', () => selectFixedScheduleEmployee(employee));
                item.addEventListener('mouseenter', () => item.style.background = '#f5f7fa');
                item.addEventListener('mouseleave', () => item.style.background = 'white');
                dropdown.appendChild(item);
            });

            dropdown.style.position = 'absolute';
            dropdown.style.zIndex = '2000';
            dropdown.style.display = 'block';
        }

        function updateFixedEmployeeSearch() {
            const input = document.getElementById('gf_employee_search');
            const search = (input ? input.value : '').trim().toLowerCase();
            const employees = Array.isArray(fixedScheduleEmployees) ? fixedScheduleEmployees : [];
            const dropdown = document.getElementById('gf_employee_dropdown');

            if (!search) {
                if (dropdown) {
                    dropdown.innerHTML = '';
                    dropdown.style.display = 'none';
                }
                return;
            }

            const matches = employees.filter(employee => {
                const fullName = String(employee.full_name || `${employee.first_name || ''} ${employee.middle_name || ''} ${employee.last_name || ''}`)
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
                const employeeCode = String(employee.employee_code || employee.employee_no || employee.employee_id || '').toLowerCase();
                return fullName.includes(search) || employeeCode.includes(search);
            });

            renderFixedEmployeeDropdown(matches);
        }

        function setupEmployeeSearch() {
            const input = document.getElementById('gf_employee_search');
            if (!input) return;
            if (input.dataset.bound === 'fixed-employee-search') return;
            input.dataset.bound = 'fixed-employee-search';
            input.oninput = updateFixedEmployeeSearch;
            input.onfocus = function () {
                if (!fixedScheduleEmployees.length) {
                    loadFixedScheduleEmployees();
                }
                updateFixedEmployeeSearch();
            };

            document.addEventListener('click', function (event) {
                const dropdown = document.getElementById('gf_employee_dropdown');
                if (!dropdown) return;
                const clickedInsideInput = input.contains(event.target);
                const clickedInsideDropdown = dropdown.contains(event.target);
                if (!clickedInsideInput && !clickedInsideDropdown) {
                    dropdown.style.display = 'none';
                }
            });
        }

            function selectFixedScheduleEmployee(employee) {
                const input = document.getElementById('gf_employee_search');
                const employeeId = document.getElementById('gf_employee_id');
                const display = document.getElementById('gf_selected_employee_display');
                const dropdown = document.getElementById('gf_employee_dropdown');

                const fullName = String(employee.full_name || `${employee.first_name || ''} ${employee.middle_name || ''} ${employee.last_name || ''}`)
                    .replace(/\s+/g, ' ')
                    .trim();

                input.value = fullName;

                employeeId.value = employee.employee_id;

                display.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    Selected: ${escapeHtml(fullName)}
                `;

                dropdown.style.display = 'none';
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            document.addEventListener('DOMContentLoaded', function () {
                setupEmployeeSearch();
            });

        function openMultipleAssignForTemplate(shiftId) {
            // preselect the shift in assignment modal and show only unassigned employees
            document.getElementById('shift_id').value = shiftId;
            document.getElementById('employeeFilterStatus').value = 'unassigned';
            openModal('assignmentModal');
            filterEmployeeByStatus('unassigned');
        }

        // Debug helper: ensure edit button clicks are observed
        (function attachEditButtonDebug() {
            const btn = document.getElementById('employeeShiftModalEditButton');
            if (!btn) return;
            // prevent attaching multiple times
            if (btn._debugAttached) return;
            btn._debugAttached = true;
            btn.addEventListener('click', function (e) {
                try {
                    console.log('employeeShiftModalEditButton clicked', {
                        selectedEmployeeIdForEdit: (typeof selectedEmployeeIdForEdit !== 'undefined' ? selectedEmployeeIdForEdit : undefined),
                        windowSelected: window.selectedEmployeeIdForEdit
                    });
                } catch (err) { console.log('edit click log error', err); }
            });
        })();
    