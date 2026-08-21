// Data from PHP
(function(){
    const dashboardConfig = window.__TA_CONFIG || {};
        const isHolidayToday = Boolean(dashboardConfig.isHolidayToday);
        const holidayInfo = dashboardConfig.holidayInfo || null;
        const attendanceData = Array.isArray(dashboardConfig.attendanceData) ? dashboardConfig.attendanceData : [];
        // Ensure each record has a `duration` string. Prefer `total_hours_worked` then compute from time_in/time_out.
        function formatHoursToHm(hours) {
            if (hours === null || hours === undefined || hours === '') return null;
            const hFloat = parseFloat(hours);
            if (isNaN(hFloat)) return null;
            const totalMinutes = Math.round(hFloat * 60);
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            return `${h}h ${m}m`;
        }

        function formatMsToHm(ms) {
            if (ms === null || isNaN(ms)) return null;
            const totalMinutes = Math.round(ms / 60000);
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            return `${h}h ${m}m`;
        }

        // Normalize attendanceData to include `duration`
        for (let i = 0; i < attendanceData.length; i++) {
            const rec = attendanceData[i];
            // prefer existing duration
            if (rec.duration && String(rec.duration).trim() !== '') continue;

            // try total_hours_worked (decimal hours)
            if (rec.total_hours_worked !== undefined && rec.total_hours_worked !== null && rec.total_hours_worked !== '') {
                const formatted = formatHoursToHm(rec.total_hours_worked);
                if (formatted) { rec.duration = formatted; continue; }
            }

            // fallback: compute from time_in/time_out
            if (rec.time_in && rec.time_out) {
                const tin = Date.parse(rec.time_in);
                const tout = Date.parse(rec.time_out);
                if (!isNaN(tin) && !isNaN(tout) && tout > tin) {
                    rec.duration = formatMsToHm(tout - tin);
                    continue;
                }
            }

            // still missing
            rec.duration = 'N/A';
        }
        const employees = Array.isArray(dashboardConfig.employees) ? dashboardConfig.employees : [];
        const jq = window.jQuery || window.$;

        if (!jq) {
            console.warn('Dashboard jQuery dependency is missing; dashboard interactions are disabled.');
        }

        function renderQRDirectory() {
            // Populate modal select for employee QR selection.
            const select = document.getElementById('qrEmployeeSelect');
            if (!select) return;
            select.innerHTML = '';
            employees.forEach(emp => {
                const opt = document.createElement('option');
                opt.value = emp.employee_id;
                opt.text = `${emp.employee_no || emp.employee_id} - ${emp.full_name}`;
                select.appendChild(opt);
            });

            // When selection changes, auto-generate QR
                select.addEventListener('change', function() {
                    const id = this.value;
                    if (!id) return; // ignore placeholder/no-selection
                    const emp = employees.find(e => e.employee_id == id);
                    if (emp) {
                        viewQR(emp.employee_id, emp.full_name, emp.department || 'N/A', emp.position || 'N/A');
                    }
                });

                // add placeholder option; do not auto-open modal on page load
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.text = '-- Select Employee --';
                select.insertBefore(placeholder, select.firstChild);
        }

        let currentQRInstance = null;
        function viewQR(id, name, dept, pos) {
            if (!jq) {
                return;
            }

            $('#qrModal').modal('show');
            $('#modalEmpName').text(name);
            $('#modalEmpDetails').text(dept + ' - ' + pos);
            $('#qrModal').data('employeeId', id);

            const container = document.getElementById('qrcode');
            container.innerHTML = '';
            currentQRInstance = new QRCode(container, {
                text: id.toString(),
                width: 200,
                height: 200,
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        function printQR(id) {
            let emp = null;
            if (id) {
                emp = employees.find(e => e.employee_id == id);
            } else {
                const sel = document.getElementById('qrEmployeeSelect');
                const selectedId = sel ? sel.value : null;
                emp = employees.find(e => e.employee_id == selectedId);
            }

            if (emp) {
                viewQR(emp.employee_id, emp.full_name, emp.department || 'N/A', emp.position || 'N/A');
                setTimeout(performPrint, 500);
            }
        }

        function performPrint() {
            if (!jq) {
                return;
            }

            const content = document.getElementById('qrcode').innerHTML;
            const win = window.open('', '', 'width=400,height=400');
            win.document.write(`
                <html>
                <head><title>Print QR</title></head>
                <body style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh;">
                    <div style="padding:20px; border:1px solid #000; text-align:center;">
                        ${content}
                        <h3 style="margin-top:10px;">${$('#modalEmpName').text()}</h3>
                        <p>${$('#modalEmpDetails').text()}</p>
                    </div>
                </body>
                </html>
            `);
            win.document.close();
            win.print();
        }

        // --- Attendance Display Logic ---
        let attendanceCurrentPage = 1;
        const attendancePageSize = 20;

        function getStatus(record) {
            if (isHolidayToday) {
                return 'HOLIDAY';
            }

            if (record.time_in) {
                const time = new Date(record.time_in);
                return (record.status && String(record.status).toUpperCase() === 'LATE') || time.getHours() > 9 ? 'LATE' : 'PRESENT';
            }

            if (record.has_shift_today || record.employee_id) {
                return 'WAITING FOR TIME IN';
            }

            if (record.status) {
                return record.status;
            }
            return 'ABSENT';
        }

        function getStatusOrder(status) {
            switch (status) {
                case 'PRESENT': return 1;
                case 'LATE': return 2;
                case 'WAITING FOR TIME IN': return 3;
                case 'ABSENT': return 4;
                default: return 5;
            }
        }

        function hideAttendanceInfoModal() {
            const modal = document.getElementById('attendanceInfoModal');
            if (!modal) return;

            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modal.style.display = 'none';

            if (window.jQuery) {
                window.jQuery(modal).modal('hide');
            }
        }

        function openAttendanceInfoModal(record) {
            const modalBody = document.getElementById('attendanceInfoModalBody');
            if (!modalBody) return;

            const status = getStatus(record);
            const timeIn = record.time_in ? new Date(record.time_in).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : 'Not recorded';
            const timeOut = record.time_out ? new Date(record.time_out).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : 'Pending';
            const duration = record.duration || 'N/A';

            const shiftStart = record.shift_start ? formatTimeValue(record.shift_start) : 'N/A';
            const shiftEnd = record.shift_end ? formatTimeValue(record.shift_end) : 'N/A';

            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-12 mb-2"><strong>Employee:</strong> ${escapeHtml(record.full_name || 'N/A')}</div>
                    <div class="col-12 mb-2"><strong>ID:</strong> ${escapeHtml(record.employee_id || record.employee_no || 'N/A')}</div>
                    <div class="col-12 mb-2"><strong>Shift Start:</strong> ${shiftStart}</div>
                    <div class="col-12 mb-2"><strong>Shift End:</strong> ${shiftEnd}</div>
                    <div class="col-12 mb-2"><strong>Time In:</strong> ${escapeHtml(timeIn)}</div>
                    <div class="col-12 mb-2"><strong>Time Out:</strong> ${escapeHtml(timeOut)}</div>
                    <div class="col-12 mb-2"><strong>Duration:</strong> ${escapeHtml(duration)}</div>
                    <div class="col-12 mb-2"><strong>Status:</strong> <span class="badge ${status === 'PRESENT' ? 'badge-success' : status === 'LATE' ? 'badge-warning' : status === 'WAITING FOR TIME IN' ? 'badge-secondary' : status === 'HOLIDAY' ? 'badge-info' : 'badge-absent'}">${escapeHtml(status)}</span></div>
                </div>
            `;

            const modal = document.getElementById('attendanceInfoModal');
            if (window.jQuery && modal) {
                window.jQuery(modal).modal('show');
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
            } else if (modal) {
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function formatTimeValue(value) {
            if (!value) return '<span style="color: #999;">N/A</span>';

            const date = new Date(`2000-01-01T${value}`);
            if (Number.isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        }

        function renderAttendancePage(records) {
            const tbody = document.getElementById('attendanceBody');
            const pageInfo = document.getElementById('attendancePageInfo');
            const prevBtn = document.getElementById('attendancePrev');
            const nextBtn = document.getElementById('attendanceNext');

            if (!tbody) return;
            tbody.innerHTML = '';

            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #999;">No records found</td></tr>';
                if (pageInfo) pageInfo.textContent = 'Showing 0 of 0 records';
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
                return;
            }

            const totalPages = Math.max(1, Math.ceil(records.length / attendancePageSize));
            attendanceCurrentPage = Math.min(attendanceCurrentPage, totalPages);
            const start = (attendanceCurrentPage - 1) * attendancePageSize;
            const pageRecords = records.slice(start, start + attendancePageSize);

            pageRecords.forEach(record => {
                const row = document.createElement('tr');
                const status = getStatus(record);
                const statusClass = status === 'PRESENT'? 'badge-success' : (
                    status === 'LATE'? 'badge-warning' : (
                        status === 'WAITING FOR TIME IN'? 'badge-secondary' : (
                            status === 'HOLIDAY'? 'badge-info' : 'badge-absent'
                        )
                    )
                );

                row.innerHTML = `
                    <td><strong>${escapeHtml(record.employee_no || record.employee_id || 'N/A')}</strong></td>
                    <td>${escapeHtml(record.full_name)}</td>
                    <td>${formatTimeValue(record.shift_start)}</td>
                    <td>${formatTimeValue(record.shift_end)}</td>
                    <td>${record.time_in? new Date(record.time_in).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', hour12: true}) : '<span style="color: #e74c3c;">Not recorded</span>'}</td>
                    <td>${record.time_out? new Date(record.time_out).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', hour12: true}) : '<span style="color: #f39c12;">Pending</span>'}</td>
                    <td>${record.duration || 'N/A'}</td>
                    <td><span class="badge ${statusClass}">${status}</span></td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-info attendance-view-btn"
                            data-employee-id="${escapeHtml(record.employee_id || '')}"
                            data-full-name="${escapeHtml(record.full_name || '')}"
                            data-department="${escapeHtml(record.department || '')}"
                            data-position="${escapeHtml(record.position || '')}"
                            data-time-in="${escapeHtml(record.time_in || '')}"
                            data-time-out="${escapeHtml(record.time_out || '')}"
                            data-duration="${escapeHtml(record.duration || '')}"
                            data-status="${escapeHtml(status)}"
                            data-shift-start="${escapeHtml(record.shift_start || '')}"
                            data-shift-end="${escapeHtml(record.shift_end || '')}"
                            style="padding: 5px 10px;"
                        >View Info</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.querySelectorAll('.attendance-view-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const record = {
                        employee_id: this.dataset.employeeId || '',
                        full_name: this.dataset.fullName || '',
                        department: this.dataset.department || '',
                        position: this.dataset.position || '',
                        shift_start: this.dataset.shiftStart || '',
                        shift_end: this.dataset.shiftEnd || '',
                        time_in: this.dataset.timeIn || '',
                        time_out: this.dataset.timeOut || '',
                        duration: this.dataset.duration || '',
                        status: this.dataset.status || ''
                    };
                    openAttendanceInfoModal(record);
                });
            });

            if (pageInfo) pageInfo.textContent = `Showing ${start + 1} to ${Math.min(start + attendancePageSize, records.length)} of ${records.length} records`;
            if (prevBtn) prevBtn.disabled = attendanceCurrentPage === 1;
            if (nextBtn) nextBtn.disabled = attendanceCurrentPage === totalPages;
        }

        function filterAndSort() {
            attendanceCurrentPage = 1;
            const searchField = document.getElementById('attendanceSearch');
            const sortField = document.getElementById('attendanceSort');
            const searchTerm = searchField ? searchField.value.toLowerCase() : '';
            const sortOption = sortField ? sortField.value : '';

            let filtered = attendanceData.filter(record => {
                const name = record.full_name.toLowerCase();
                const dept = (record.department || '').toLowerCase();
                return name.includes(searchTerm) || dept.includes(searchTerm);
            });

            filtered.sort((a, b) => {
                const statusA = getStatus(a);
                const statusB = getStatus(b);
                const statusDiff = getStatusOrder(statusA) - getStatusOrder(statusB);
                if (statusDiff !== 0) {
                    return statusDiff;
                }

                switch (sortOption) {
                    case 'name': return a.full_name.localeCompare(b.full_name);
                    case 'name-desc': return b.full_name.localeCompare(a.full_name);
                    case 'time': return new Date(b.time_in || 0) - new Date(a.time_in || 0);
                    case 'time-asc': return new Date(a.time_in || 0) - new Date(b.time_in || 0);
                    case 'department': return (a.department || '').localeCompare(b.department || '');
                    case 'status': return 0;
                    default: return 0;
                }
            });

            renderAttendancePage(filtered);
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        const prevButton = document.getElementById('attendancePrev');
        const nextButton = document.getElementById('attendanceNext');
        const attendanceSearchInput = document.getElementById('attendanceSearch');
        const attendanceSortSelect = document.getElementById('attendanceSort');

        if (prevButton) {
            prevButton.addEventListener('click', function() {
                if (attendanceCurrentPage > 1) {
                    attendanceCurrentPage -= 1;
                    filterAndSort();
                }
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                attendanceCurrentPage += 1;
                filterAndSort();
            });
        }

        if (jq) {
            jq(document).ready(function() {
                hideAttendanceInfoModal();
                renderQRDirectory();
                filterAndSort();
                if (attendanceSearchInput) jq(attendanceSearchInput).on('keyup', filterAndSort);
                if (attendanceSortSelect) jq(attendanceSortSelect).on('change', filterAndSort);
            });
        } else {
            hideAttendanceInfoModal();
            renderQRDirectory();
            filterAndSort();
        }

        // Camera and employee-QR functionality moved to separate pages (`qr_scanner.php`, `employee_qr_list.php`).

        function updateClock() {
            const clockElement = document.getElementById('liveClock');
            if (clockElement) {
                const now = new Date();
                clockElement.textContent = now.toLocaleTimeString();
            }
        }
        setInterval(updateClock, 1000);

        // Export commonly used functions to the global scope so other scripts and inline handlers continue to work
        try {
            window.renderQRDirectory = renderQRDirectory;
            window.viewQR = viewQR;
            window.printQR = printQR;
            window.hideAttendanceInfoModal = hideAttendanceInfoModal;
            window.openAttendanceInfoModal = openAttendanceInfoModal;
            window.filterAndSort = filterAndSort;
            window.updateClock = updateClock;
        } catch (e) {
            // noop
        }
})();
    