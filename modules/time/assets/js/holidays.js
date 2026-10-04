
        // Get color based on holiday category
        function getCategoryColor(category) {
            const colors = {
                'national': '#e74c3c',   // Red
                'regional': '#f39c12',   // Orange
                'optional': '#3498db',   // Blue
                'special': '#9b59b6'     // Purple
            };
            return colors[category] || '#95a5a6'; // Gray default
        }

        window.preloaderHold = true;
        function releaseHolidayPreloader(delay = 0) {
            if (window.releasePreloader) {
                window.releasePreloader(delay);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load holiday data via AJAX once the page is ready
            loadHolidayData();
        });

function initHolidaysPage() {
    if (initHolidaysPage._inited) return;
    initHolidaysPage._inited = true;
    console.log('[TA INIT] Holidays initialized');
    bindHolidayManagement();
    loadHolidayData();
    try { if (typeof initHolidays === 'function') initHolidays(); } catch (e) { console.error('initHolidaysPage error', e); }
}

        // Load holiday data via AJAX
        function loadHolidayData() {
            fetch('/hrms/hrms-capstone/modules/time/app/api/holidays/holiday_api.php?action=get_page_data')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('API returned ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success || data.holidays !== undefined) {
                            renderHolidayContent(data);
                        } else {
                            showSetupNeeded();
                        }
                    } catch (e) {
                        console.error('JSON Parse Error:', e, 'Response:', text.substring(0, 200));
                        showSetupNeeded();
                    }
                })
                .catch(error => {
                    console.error('Error loading holidays:', error);
                    showSetupNeeded();
                });
        }

        // Render holiday content
        function renderHolidayContent(data) {
            const container = document.getElementById('holidayContent');
            
            // Check if we have valid data
            if (!data.holidays || !Array.isArray(data.holidays)) {
                data.holidays = [];
            }
            if (!data.upcoming || !Array.isArray(data.upcoming)) {
                data.upcoming = [];
            }

            // Build upcoming list HTML
            let upcomingHtml = '';
            if (data.upcoming && data.upcoming.length > 0) {
                data.upcoming.forEach(holiday => {
                    const daysLeft = Math.ceil((new Date(holiday.holiday_date) - new Date()) / (1000 * 60 * 60 * 24));
                    upcomingHtml += `
                        <div class="holiday-item" style="background: rgba(255, 255, 255, 0.1); padding: 10px; margin-bottom: 8px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-left: 3px solid rgba(255, 255, 255, 0.3);">
                            <div class="name" style="flex: 1;">
                                <strong>${holiday.name}</strong><br>
                                <span style="font-size: 11px; opacity: 0.8;">${new Date(holiday.holiday_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</span>
                            </div>
                            <div class="days" style="background: rgba(255, 255, 255, 0.2); padding: 3px 10px; border-radius: 4px; font-weight: 600; font-size: 12px;">${daysLeft} days</div>
                        </div>
                    `;
                });
            }

            const nextHoliday = data.upcoming && data.upcoming.length > 0 ? data.upcoming[0] : null;
            const daysUntilNext = nextHoliday ? Math.ceil((new Date(nextHoliday.holiday_date) - new Date()) / (1000 * 60 * 60 * 24)) : 'N/A';

            container.innerHTML = `
                <div class="holiday-container" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 30px;">
                    <!-- Left: Holiday Widget -->
                    <div class="holiday-widget" style="background: linear-gradient(135deg, #003d82 0%, #005ba8 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                        <h3 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-bell"></i> Upcoming Holidays
                        </h3>
                        <div class="holiday-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                            <div class="stat-box" style="background: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div class="number" style="font-size: 20px; font-weight: 700; margin-bottom: 5px;">${data.holidays.length}</div>
                                <div class="label" style="font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Total Holidays</div>
                            </div>
                            <div class="stat-box" style="background: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div class="number" style="font-size: 20px; font-weight: 700; margin-bottom: 5px;">${data.upcoming ? data.upcoming.length : 0}</div>
                                <div class="label" style="font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Coming Up</div>
                            </div>
                            <div class="stat-box" style="background: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div class="number" style="font-size: 20px; font-weight: 700; margin-bottom: 5px;">${daysUntilNext !== 'N/A' ? daysUntilNext : '-'}</div>
                                <div class="label" style="font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Days Left</div>
                            </div>
                        </div>
                        ${nextHoliday ? `
                            <div class="next-holiday-block" style="background: rgba(255, 255, 255, 0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid rgba(255, 255, 255, 0.5);">
                                <div class="label" style="font-size: 12px; opacity: 0.9; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">Next Holiday</div>
                                <div class="holiday-name" style="font-size: 20px; font-weight: 600; margin-bottom: 10px;">${nextHoliday.name}</div>
                                <div class="countdown" style="font-size: 36px; font-weight: 700; margin-bottom: 5px; color: #fff;">${daysUntilNext}</div>
                                <div class="countdown-label" style="font-size: 12px; opacity: 0.85;">${daysUntilNext == 0 ? 'Today!' : daysUntilNext == 1 ? 'Tomorrow' : 'days remaining'}</div>
                            </div>
                        ` : ''}
                        <div class="upcoming-holidays">
                            <h4 style="font-size: 14px; margin-bottom: 12px; opacity: 0.95; text-transform: uppercase; letter-spacing: 0.5px;">Upcoming Holidays</h4>
                            ${upcomingHtml || '<p style="font-size: 12px; opacity: 0.8;">No upcoming holidays</p>'}
                        </div>
                        <div class="sync-info" style="font-size: 11px; opacity: 0.8; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                            <div style="margin-bottom: 10px;">
                                <strong>Last Updated:</strong><br>
                                <span id="lastSyncTime">${new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})}</span>
                            </div>
                            <button class="sync-button" style="display: inline-block; background: rgba(255, 255, 255, 0.25); color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; margin-top: 10px; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;" onclick="syncHolidays()">
                                <i class="fas fa-sync-alt"></i> Refresh Holidays
                            </button>
                        </div>
                    </div>
                    <div class="calendar-container glass-panel" style="padding: 20px; border-radius: 18px;">
                        <h3 style="margin: 0 0 20px 0; color: #333; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-calendar-days"></i> Holiday Calendar
                        </h3>
                        <div id="holidayCalendar" style="height: 550px;"></div>
                    </div>
                </div>
            `;

            // Initialize calendar after content is rendered
            initializeCalendar(data.holidays);
        }

        // Initialize FullCalendar
        function initializeCalendar(holidays) {
            const calendarEl = document.getElementById('holidayCalendar');
            if (!calendarEl) {
                releaseHolidayPreloader(1200);
                return;
            }

            try {
                const events = holidays.map(h => ({
                    title: h.name,
                    start: h.holiday_date,
                    backgroundColor: '#005ba8',
                    borderColor: '#005ba8',
                    extendedProps: {
                        category: h.category || 'Holiday'
                    }
                }));

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listMonth'
                    },
                    events: events,
                    eventClick: function(info) {
                        const event = info.event;
                        alert(
                            event.title + '\n' +
                            'Date: ' + event.start.toLocaleDateString() + '\n' +
                            'Category: ' + (event.extendedProps.category || 'Holiday')
                        );
                    },
                    editable: false,
                    selectable: false
                });

                calendar.render();
            } catch (err) {
                console.error('Calendar initialization error:', err);
            } finally {
                releaseHolidayPreloader(1200);
            }
        }

        // Show error message
        function showError(message) {
            const container = document.getElementById('holidayContent');
            container.innerHTML = `
                <div class="glass-panel" style="background: rgba(248, 215, 218, 0.9); border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 18px; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <h3>${message}</h3>
                    <p style="margin-bottom: 0;">Please try reloading the page or contact support if the problem persists.</p>
                </div>
            `;
            releaseHolidayPreloader(1200);
        }

        // Show setup needed
        function showSetupNeeded() {
            const container = document.getElementById('holidayContent');
            container.innerHTML = `
                <div class="glass-panel" style="padding: 40px; border-radius: 18px; text-align: center;">
                    <i class="fas fa-wrench" style="font-size: 48px; color: #ffc107; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">Holiday System Setup Required</h3>
                    <p style="color: #999; margin-bottom: 20px;">The holiday system needs to be initialized. Please visit the setup page to sync holidays from the API.</p>
                    <p style="font-size: 12px; color: #999; margin-bottom: 20px;">
                        <strong>Setup Steps:</strong><br>
                        1. Visit <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">/app/setup/holiday_setup.php</code><br>
                        2. Click "Sync Holidays from API"<br>
                        3. Return to this page
                    </p>
                    <a href="/hrms/hrms-capstone/modules/time/app/setup/holiday_setup.php"
                    style="display: inline-block; background: #005ba8; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; text-decoration: none;">
                        <i class="fas fa-cog"></i> Go to Setup
                    </a>
                </div>
            `;
            releaseHolidayPreloader(1200);
        }

        // Configure toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };

        // Sync holidays from API
        function syncHolidays() {
            const btn = event.target.closest('.sync-button');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            btn.disabled = true;

            fetch('/hrms/hrms-capstone/modules/time/app/api/holidays/holiday_api.php?action=sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Holidays synced successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(data.message || 'Failed to sync holidays');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error syncing holidays');
            })
            .finally(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        }

        /* HR holiday management additions. These use the existing API routes above. */
        let holidayRecords = [];
        let holidayCalendarInstance = null;
        let holidayFormBound = false;
        let holidayListPage = 1;
        const holidayPageSize = 10;

        function escapeHolidayHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
            }[character]));
        }

        function holidayScopeLabel(scope) {
            return ({ national: 'National', provincial: 'Provincial', company: 'Company' })[scope] || 'National';
        }

        function holidayWorkLabel(workingDay) {
            return Number(workingDay) === 1 ? 'Working' : 'Non-Working';
        }

        function holidaySourceLabel(source) {
            return source === 'api_nager' ? 'Nager API' : 'Manual';
        }

        function holidayBadge(label, type) {
            const colors = {
                national: '#e8f1fb', provincial: '#fff3cd', company: '#e8f5e9',
                working: '#d1ecf1', nonworking: '#f8d7da', manual: '#e2e3e5', api: '#cfe2ff'
            };
            const textColors = {
                national: '#174a7c', provincial: '#856404', company: '#216e39',
                working: '#0c5460', nonworking: '#721c24', manual: '#383d41', api: '#084298'
            };
            return `<span style="display:inline-block; padding:3px 8px; border-radius:12px; background:${colors[type] || '#e2e3e5'}; color:${textColors[type] || '#383d41'}; font-size:11px; font-weight:600; white-space:nowrap;">${escapeHolidayHtml(label)}</span>`;
        }

        function setHolidayFormMessage(message, isError = true) {
            const box = document.getElementById('holidayFormMessage');
            if (!box) return;
            box.textContent = message || '';
            box.style.display = message ? 'block' : 'none';
            box.style.background = isError ? '#f8d7da' : '#d1e7dd';
            box.style.color = isError ? '#721c24' : '#0f5132';
        }

        function updateProvinceField() {
            const scope = document.getElementById('holidayScope');
            const provinceField = document.getElementById('provinceField');
            const province = document.getElementById('provinceName');
            const provincial = scope && scope.value === 'provincial';
            if (!provinceField || !province) return;
            provinceField.hidden = !provincial;
            province.disabled = !provincial;
            province.required = provincial;
            if (!provincial) province.value = '';
        }

        function openHolidayModal(holiday = null) {
            const modal = document.getElementById('holidayModal');
            const form = document.getElementById('holidayForm');
            if (!modal || !form) return;
            form.reset();
            setHolidayFormMessage('');
            document.getElementById('holidayModalTitle').textContent = holiday ? 'Edit Holiday' : 'Add Holiday';
            document.getElementById('saveHolidayButton').textContent = holiday ? 'Save Changes' : 'Save Holiday';
            document.getElementById('holidayId').value = holiday ? holiday.id : '';
            document.getElementById('holidayName').value = holiday ? holiday.name : '';
            document.getElementById('holidayDate').value = holiday ? holiday.holiday_date : '';
            document.getElementById('holidayScope').value = holiday ? (holiday.holiday_scope || 'national') : 'national';
            document.getElementById('provinceName').value = holiday ? (holiday.province_name || '') : '';
            document.getElementById('holidayWorkingDay').value = holiday ? String(Number(holiday.is_working_day) === 1 ? 1 : 0) : '0';
            document.getElementById('holidayRecurring').checked = holiday ? Number(holiday.is_recurring) === 1 : false;
            document.getElementById('holidayDescription').value = holiday ? (holiday.description || '') : '';
            updateProvinceField();
            modal.hidden = false;
            document.getElementById('holidayName').focus();
        }

        function closeHolidayModal() {
            const modal = document.getElementById('holidayModal');
            if (modal) modal.hidden = true;
        }

        function getHolidayPayload() {
            const scope = document.getElementById('holidayScope').value;
            const province = scope === 'provincial' ? document.getElementById('provinceName').value.trim() : null;
            return {
                name: document.getElementById('holidayName').value.trim(),
                holiday_date: document.getElementById('holidayDate').value,
                holiday_scope: scope,
                province_name: province,
                is_working_day: Number(document.getElementById('holidayWorkingDay').value),
                is_recurring: document.getElementById('holidayRecurring').checked ? 1 : 0,
                description: document.getElementById('holidayDescription').value.trim()
            };
        }

        function validateHolidayPayload(payload) {
            if (!payload.name) return 'Holiday name is required.';
            if (!payload.holiday_date) return 'Holiday date is required.';
            if (!['national', 'provincial', 'company'].includes(payload.holiday_scope)) return 'Select a valid holiday scope.';
            if (payload.holiday_scope === 'provincial' && !payload.province_name) return 'Province is required for provincial holidays.';
            if (![0, 1].includes(payload.is_working_day)) return 'Select a valid work status.';
            return '';
        }

        async function submitHolidayForm(event) {
            event.preventDefault();
            const button = document.getElementById('saveHolidayButton');
            const payload = getHolidayPayload();
            const validationMessage = validateHolidayPayload(payload);
            if (validationMessage) {
                setHolidayFormMessage(validationMessage);
                return;
            }
            if (button.disabled) return;
            button.disabled = true;
            button.textContent = 'Saving...';
            const id = document.getElementById('holidayId').value;
            const action = id ? 'update' : 'create';
            if (id) payload.id = Number(id);
            try {
                const response = await fetch(`/hrms/hrms-capstone/modules/time/app/api/holidays/holiday_api.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save holiday.');
                closeHolidayModal();
                if (window.toastr) toastr.success(result.message || 'Holiday saved successfully.');
                await loadHolidayData();
            } catch (error) {
                setHolidayFormMessage(error.message || 'Unable to save holiday.');
            } finally {
                button.disabled = false;
                button.textContent = id ? 'Save Changes' : 'Save Holiday';
            }
        }

        async function deleteHolidayRecord(holiday) {
            const summary = `${holiday.name} (${formatHolidayDate(holiday.holiday_date)}) - ${holidayScopeLabel(holiday.holiday_scope)} - ${holidayWorkLabel(holiday.is_working_day)}`;
            if (!window.confirm(`Delete this holiday?\n\n${summary}`)) return;
            try {
                const response = await fetch(`/hrms/hrms-capstone/modules/time/app/api/holidays/holiday_api.php?action=delete&id=${encodeURIComponent(holiday.id)}`, { method: 'DELETE' });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to delete holiday.');
                if (window.toastr) toastr.success(result.message || 'Holiday deleted successfully.');
                await loadHolidayData();
            } catch (error) {
                if (window.toastr) toastr.error(error.message || 'Unable to delete holiday.');
            }
        }

        function formatHolidayDate(date) {
            if (!date) return '';
            return new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }

        function renderHolidayRows() {
            const scopeFilter = document.getElementById('holidayScopeFilter')?.value || 'all';
            const workFilter = document.getElementById('holidayWorkFilter')?.value || 'all';
            const monthFilter = document.getElementById('holidayMonthFilter')?.value || 'all';
            const scopeOrder = { national: 0, provincial: 1, company: 2 };
            const filtered = holidayRecords.filter(holiday => {
                const scopeMatches = scopeFilter === 'all' || scopeFilter === 'monthly' || holiday.holiday_scope === scopeFilter;
                const workMatches = workFilter === 'all' || Number(holiday.is_working_day) === Number(workFilter);
                const monthMatches = scopeFilter !== 'monthly' || monthFilter === 'all' || new Date(`${holiday.holiday_date}T00:00:00`).getMonth() + 1 === Number(monthFilter);
                return scopeMatches && workMatches && monthMatches;
            }).sort((first, second) => {
                const firstDate = new Date(`${first.holiday_date}T00:00:00`);
                const secondDate = new Date(`${second.holiday_date}T00:00:00`);
                const monthDifference = firstDate.getMonth() - secondDate.getMonth();
                if (monthDifference !== 0) return monthDifference;
                const scopeDifference = (scopeOrder[first.holiday_scope] ?? 99) - (scopeOrder[second.holiday_scope] ?? 99);
                if (scopeDifference !== 0) return scopeDifference;
                return firstDate - secondDate;
            });
            const body = document.getElementById('holidayTableBody');
            const empty = document.getElementById('holidayTableEmpty');
            const pagination = document.getElementById('holidayPagination');
            if (!body || !empty) return;
            const pageCount = Math.max(1, Math.ceil(filtered.length / holidayPageSize));
            holidayListPage = Math.min(Math.max(holidayListPage, 1), pageCount);
            const pageStart = (holidayListPage - 1) * holidayPageSize;
            const pageRows = filtered.slice(pageStart, pageStart + holidayPageSize);
            body.innerHTML = pageRows.map(holiday => {
                const scope = holidayScopeLabel(holiday.holiday_scope);
                const scopeText = holiday.holiday_scope === 'provincial' && holiday.province_name ? `${scope} — ${holiday.province_name}` : scope;
                const actions = holiday.holiday_scope === 'national'
                    ? '<span class="holiday-protected-label"><i class="fas fa-lock"></i> Protected</span>'
                    : `<button type="button" class="holiday-edit-action" data-id="${escapeHolidayHtml(holiday.id)}"><i class="fas fa-pen"></i> Edit</button><button type="button" class="holiday-delete-action" data-id="${escapeHolidayHtml(holiday.id)}"><i class="fas fa-trash"></i> Delete</button>`;
                return `<tr>
                    <td class="holiday-name-cell"><strong>${escapeHolidayHtml(holiday.name)}</strong><div class="holiday-description">${escapeHolidayHtml(holiday.description || '')}</div></td>
                    <td class="holiday-date-cell"><strong>${escapeHolidayHtml(formatHolidayDate(holiday.holiday_date))}</strong></td>
                    <td>${holidayBadge(scopeText, holiday.holiday_scope || 'national')}</td>
                    <td>${holidayBadge(holidayWorkLabel(holiday.is_working_day), Number(holiday.is_working_day) === 1 ? 'working' : 'nonworking')}</td>
                    <td class="holiday-actions">${actions}</td>
                </tr>`;
            }).join('');
            empty.style.display = filtered.length ? 'none' : 'block';
            if (pagination) {
                pagination.innerHTML = filtered.length > holidayPageSize ? `
                    <button type="button" data-page="${holidayListPage - 1}" ${holidayListPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i> Previous</button>
                    <span>Page ${holidayListPage} of ${pageCount}<small>${filtered.length} holidays</small></span>
                    <button type="button" data-page="${holidayListPage + 1}" ${holidayListPage === pageCount ? 'disabled' : ''}>Next <i class="fas fa-chevron-right"></i></button>` : '';
                pagination.querySelectorAll('button[data-page]').forEach(button => button.addEventListener('click', () => {
                    holidayListPage = Number(button.dataset.page);
                    renderHolidayRows();
                }));
            }
        }

        function updateHolidayMonthFilter() {
            const scopeFilter = document.getElementById('holidayScopeFilter');
            const monthFilter = document.getElementById('holidayMonthFilter');
            if (!scopeFilter || !monthFilter) return;
            monthFilter.hidden = scopeFilter.value !== 'monthly';
            if (scopeFilter.value !== 'monthly') monthFilter.value = 'all';
        }

        function bindHolidayManagement() {
            if (holidayFormBound) return;
            holidayFormBound = true;
            document.addEventListener('click', event => {
                if (event.target.closest('#addHolidayButton')) openHolidayModal();
            });
            document.getElementById('closeHolidayModal')?.addEventListener('click', closeHolidayModal);
            document.getElementById('cancelHolidayModal')?.addEventListener('click', closeHolidayModal);
            document.getElementById('holidayScope')?.addEventListener('change', updateProvinceField);
            document.getElementById('holidayForm')?.addEventListener('submit', submitHolidayForm);
            document.getElementById('holidayModal')?.addEventListener('click', event => {
                if (event.target.id === 'holidayModal') closeHolidayModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeHolidayModal();
            });
            document.getElementById('holidayScopeFilter')?.addEventListener('change', () => { updateHolidayMonthFilter(); holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayWorkFilter')?.addEventListener('change', () => { holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayMonthFilter')?.addEventListener('change', () => { holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayMonthFilter')?.addEventListener('change', () => { holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayTableBody')?.addEventListener('click', event => {
                const id = Number(event.target.dataset.id);
                const holiday = holidayRecords.find(item => Number(item.id) === id);
                if (!holiday) return;
                if (event.target.classList.contains('holiday-edit-action')) openHolidayModal(holiday);
                if (event.target.classList.contains('holiday-delete-action')) deleteHolidayRecord(holiday);
            });
        }

        function renderHolidayContent(data) {
            holidayRecords = Array.isArray(data.holidays) ? data.holidays : [];
            const upcoming = Array.isArray(data.upcoming) ? data.upcoming : [];
            const nextHoliday = upcoming[0] || null;
            const daysUntilNext = nextHoliday ? Math.ceil((new Date(`${nextHoliday.holiday_date}T00:00:00`) - new Date()) / 86400000) : 'N/A';
            const container = document.getElementById('holidayContent');
            if (!container) return;
            const upcomingHtml = upcoming.map(holiday => `<div class="holiday-item"><div class="name"><strong>${escapeHolidayHtml(holiday.name)}</strong><br><span style="font-size:11px;opacity:.8;">${escapeHolidayHtml(formatHolidayDate(holiday.holiday_date))}</span></div><div class="days">${Math.max(0, Math.ceil((new Date(`${holiday.holiday_date}T00:00:00`) - new Date()) / 86400000))} days</div></div>`).join('');
            container.innerHTML = `<div class="holiday-container" style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:24px;">
                <div class="holiday-widget" style="background:linear-gradient(135deg,#003d82 0%,#005ba8 100%);color:white;padding:25px;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,.2);">
                    <h3 style="margin:0 0 15px;font-size:18px;"><i class="fas fa-bell"></i> Upcoming Holidays</h3>
                    <div class="holiday-stats"><div class="stat-box"><div class="number">${holidayRecords.length}</div><div class="label">Total Holidays</div></div><div class="stat-box"><div class="number">${upcoming.length}</div><div class="label">Coming Up</div></div><div class="stat-box"><div class="number">${daysUntilNext === 'N/A' ? '-' : daysUntilNext}</div><div class="label">Days Left</div></div></div>
                    ${nextHoliday ? `<div class="next-holiday-block"><div class="label">Next Holiday</div><div class="holiday-name">${escapeHolidayHtml(nextHoliday.name)}</div><div class="countdown">${daysUntilNext}</div><div class="countdown-label">${daysUntilNext === 0 ? 'Today!' : daysUntilNext === 1 ? 'Tomorrow' : 'days remaining'}</div></div>` : ''}
                    <div class="upcoming-holidays"><h4>Upcoming Holidays</h4>${upcomingHtml || '<p style="font-size:12px;opacity:.8;">No upcoming holidays</p>'}</div>
                    <div class="sync-info"><strong>Last Updated:</strong><br>${new Date().toLocaleString()}<br><button type="button" id="addHolidayButton" style="display:inline-block;background:#fff;color:#005ba8;padding:9px 14px;border:0;border-radius:5px;cursor:pointer;font-size:12px;margin-top:10px;font-weight:600;"><i class="fas fa-plus"></i> Add Holiday</button><button class="sync-button" type="button" id="syncHolidayButton"><i class="fas fa-sync-alt"></i> Refresh Holidays</button></div>
                </div>
                <div class="calendar-container glass-panel" style="padding:20px;border-radius:18px;"><h3><i class="fas fa-calendar-days"></i> Holiday Calendar</h3><div id="holidayCalendar" style="height:550px;"></div></div>
            </div>
            <div class="glass-panel holiday-list-panel">
                <div class="holiday-list-header"><div><h3><i class="fas fa-list"></i> Holiday List</h3><p>Manage national, provincial, and company holidays</p></div><div class="holiday-list-filters"><label>Scope<select id="holidayScopeFilter" aria-label="Filter by scope"><option value="all">All Scopes</option><option value="national">National</option><option value="provincial">Provincial</option><option value="company">Company</option><option value="monthly">Monthly</option></select></label><select id="holidayMonthFilter" aria-label="Filter by month" hidden><option value="all">All Months</option><option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option><option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select><label>Work status<select id="holidayWorkFilter" aria-label="Filter by work status"><option value="all">All Statuses</option><option value="1">Working</option><option value="0">Non-Working</option></select></div></div>
                <div class="holiday-table-wrap"><table class="holiday-list-table"><thead><tr><th>Holiday</th><th>Date</th><th>Scope</th><th>Work Status</th><th>Actions</th></tr></thead><tbody id="holidayTableBody"></tbody></table></div><div id="holidayTableEmpty" class="no-holidays">No holidays match the selected filters.</div><div id="holidayPagination" class="holiday-pagination"></div>
            </div>
            `;
            document.getElementById('holidayScopeFilter')?.addEventListener('change', () => { updateHolidayMonthFilter(); holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayWorkFilter')?.addEventListener('change', () => { holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayMonthFilter')?.addEventListener('change', () => { holidayListPage = 1; renderHolidayRows(); });
            document.getElementById('holidayTableBody')?.addEventListener('click', event => {
                const id = Number(event.target.dataset.id);
                const holiday = holidayRecords.find(item => Number(item.id) === id);
                if (!holiday) return;
                if (event.target.classList.contains('holiday-edit-action')) openHolidayModal(holiday);
                if (event.target.classList.contains('holiday-delete-action')) deleteHolidayRecord(holiday);
            });
            bindHolidayManagement();
            document.getElementById('syncHolidayButton')?.addEventListener('click', syncHolidays);
            updateHolidayMonthFilter();
            renderHolidayRows();
            initializeCalendar(holidayRecords);
            releaseHolidayPreloader(1200);
        }

        function initializeCalendar(holidays) {
            const calendarEl = document.getElementById('holidayCalendar');
            if (!calendarEl || typeof FullCalendar === 'undefined') return;
            if (holidayCalendarInstance) holidayCalendarInstance.destroy();
            const events = holidays.map(holiday => ({
                title: holiday.name,
                start: holiday.holiday_date,
                backgroundColor: Number(holiday.is_working_day) === 1 ? '#0c8599' : '#005ba8',
                borderColor: Number(holiday.is_working_day) === 1 ? '#0c8599' : '#005ba8',
                extendedProps: { holiday }
            }));
            holidayCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                events,
                editable: false,
                selectable: false,
                eventClick: info => {
                    const holiday = info.event.extendedProps.holiday;
                    const scope = holidayScopeLabel(holiday.holiday_scope) + (holiday.holiday_scope === 'provincial' && holiday.province_name ? ` — ${holiday.province_name}` : '');
                    alert(`${holiday.name}\nDate: ${formatHolidayDate(holiday.holiday_date)}\nScope: ${scope}\nWork Status: ${holidayWorkLabel(holiday.is_working_day)}\nSource: ${holidaySourceLabel(holiday.source)}${holiday.description ? `\nDescription: ${holiday.description}` : ''}`);
                }
            });
            holidayCalendarInstance.render();
        }

        function syncHolidays() {
            const button = document.getElementById('syncHolidayButton');
            if (button) { button.disabled = true; button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...'; }
            fetch('/hrms/hrms-capstone/modules/time/app/api/holidays/holiday_api.php?action=sync', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(response => response.json().then(data => ({ response, data })))
                .then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.message || 'Failed to sync holidays.'); if (window.toastr) toastr.success(data.message || 'Holidays synced successfully.'); return loadHolidayData(); })
                .catch(error => { if (window.toastr) toastr.error(error.message || 'Error syncing holidays.'); })
                .finally(() => { if (button) { button.disabled = false; button.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Holidays'; } });
        }

        document.addEventListener('DOMContentLoaded', bindHolidayManagement);
    