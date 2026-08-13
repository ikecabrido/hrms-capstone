// Data from PHP
        const config = window.__TA_CONFIG || {};
        const isHolidayToday = Boolean(config.isHolidayToday);
        const holidayInfo = config.holidayInfo || null;
        const attendanceData = Array.isArray(config.attendanceData) ? config.attendanceData : [];
        const employees = Array.isArray(config.employees) ? config.employees : [];

        // --- QR Directory Logic ---
        function toggleQRDirectory() {
            // legacy stub - directory removed in new UI
        }

        function filterQRDirectory() {
            // legacy stub - directory removed in new UI
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

            // If employee has a recorded time_in, derive status from that
            if (record.time_in) {
                const time = new Date(record.time_in);
                // Simple heuristic: present if before or at shift start (handled server-side when inserted)
                // Fallback: consider hours > 9 as late for legacy records
                return time.getHours() > 9 ? 'LATE' : 'PRESENT';
            }

            // No time_in recorded yet: fall back to stored status or legacy ABSENT
            if (record.status) {
                return record.status;
            }
            return 'ABSENT';
        }

        function getStatusOrder(status) {
            switch (status) {
                case 'PRESENT': return 1;
                case 'LATE': return 2;
                case 'ABSENT': return 3;
                default: return 4;
            }
        }

        function renderAttendancePage(records) {
            const tbody = document.getElementById('attendanceBody');
            const pageInfo = document.getElementById('attendancePageInfo');
            const prevBtn = document.getElementById('attendancePrev');
            const nextBtn = document.getElementById('attendanceNext');

            tbody.innerHTML = '';

            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No records found</td></tr>';
                pageInfo.textContent = 'Showing 0 of 0 records';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
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
                        status === 'HOLIDAY'? 'badge-info' : 'badge-absent'
                    )
                );

                row.innerHTML = `
                    <td><strong>${escapeHtml(record.employee_no || record.employee_id)}</strong></td>
                    <td>${escapeHtml(record.full_name)}</td>
                    <td>${escapeHtml(record.department || 'N/A')}</td>
                    <td>${escapeHtml(record.position || 'N/A')}</td>
                    <td>${record.time_in? new Date(record.time_in).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', hour12: true}) : '<span style="color: #e74c3c;">Not recorded</span>'}</td>
                    <td>${record.time_out? new Date(record.time_out).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', hour12: true}) : '<span style="color: #f39c12;">Pending</span>'}</td>
                    <td>${record.duration || 'N/A'}</td>
                    <td><span class="badge ${statusClass}">${status}</span></td>
                `;
                tbody.appendChild(row);
            });

            pageInfo.textContent = `Showing ${start + 1} to ${Math.min(start + attendancePageSize, records.length)} of ${records.length} records`;
            prevBtn.disabled = attendanceCurrentPage === 1;
            nextBtn.disabled = attendanceCurrentPage === totalPages;
        }

        function filterAndSort() {
            attendanceCurrentPage = 1;
            const searchTerm = document.getElementById('attendanceSearch').value.toLowerCase();
            const sortOption = document.getElementById('attendanceSort').value;

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

        document.getElementById('attendancePrev').addEventListener('click', function() {
            if (attendanceCurrentPage > 1) {
                attendanceCurrentPage -= 1;
                filterAndSort();
            }
        });

        document.getElementById('attendanceNext').addEventListener('click', function() {
            attendanceCurrentPage += 1;
            filterAndSort();
        });

        $(document).ready(function() {
            renderQRDirectory();
            filterAndSort();
            $('#attendanceSearch').on('keyup', filterAndSort);
            $('#attendanceSort').on('change', filterAndSort);
        });

        // Camera and employee-QR functionality moved to separate pages (`qr_scanner.php`, `employee_qr_list.php`).

        function updateClock() {
            const clockElement = document.getElementById('liveClock');
            if (clockElement) {
                const now = new Date();
                clockElement.textContent = now.toLocaleTimeString();
            }
        }
        setInterval(updateClock, 1000);
    