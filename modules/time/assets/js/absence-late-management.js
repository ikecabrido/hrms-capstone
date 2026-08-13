
        function applyFilters() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const type = document.getElementById('typeFilter').value;

            let url = 'absence_late_management.php?';
            if (startDate) url += `start_date=${startDate}&`;
            if (endDate) url += `end_date=${endDate}&`;
            if (type) url += `type=${type}`;

            window.location.href = url;
        }

        function viewRecord(recordId) {
            fetch(`../app/api/absence_late_management.php?action=get_record&record_id=${recordId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const record = res.data;
                        let html = `
                            <div class="form-group">
                                <label>Employee</label>
                                <p>${htmlEscape(record.full_name)}</p>
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <p>${htmlEscape(record.department || 'N/A')}</p>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <p><span class="badge badge-${record.type.toLowerCase()}">${record.type}</span></p>
                            </div>
                            <div class="form-group">
                                <label>Date</label>
                                <p>${new Date(record.absence_date).toLocaleDateString()}</p>
                            </div>
                            <div class="form-group">
                                <label>Reason</label>
                                <p>${htmlEscape(record.reason || 'Not provided')}</p>
                            </div>
                            <div class="form-group">
                                <label>Late Hours</label>
                                <p>${record.late_minutes !== null ? `${(parseFloat(record.late_minutes) / 60).toFixed(2)}h` : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Time In</label>
                                <p>${record.time_in ? new Date(record.time_in).toLocaleString() : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Time Out</label>
                                <p>${record.time_out ? new Date(record.time_out).toLocaleString() : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Working Hours</label>
                                <p>${record.total_hours_worked !== null ? `${parseFloat(record.total_hours_worked).toFixed(2)}h` : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Regular Hours</label>
                                <p>${record.regular_hours !== null ? `${parseFloat(record.regular_hours).toFixed(2)}h` : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Overtime Hours</label>
                                <p>${record.overtime_hours !== null ? `${parseFloat(record.overtime_hours).toFixed(2)}h` : 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label>Notes</label>
                                <p>${htmlEscape(record.notes || 'No notes')}</p>
                            </div>
                        `;
                        document.getElementById('recordDetails').innerHTML = html;
                        openModal('viewModal');
                    }
                })
                .catch(err => toastr.error('Failed to load record'));
        }

        function generateReport() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const type = document.getElementById('typeFilter').value;

            let url = '../app/api/absence_late_management.php?action=get_report';
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            if (type) url += `&type=${type}`;

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        downloadPdfReport(res.data, startDate, endDate, type);
                    } else {
                        toastr.error(res.message || 'Failed to generate report');
                    }
                })
                .catch(err => toastr.error('Failed to generate report'));
        }

        function downloadPdfReport(data, startDate, endDate, type) {
            const reportWindow = window.open('', '_blank', 'width=1200,height=800');
            if (!reportWindow) {
                toastr.error('Please allow pop-ups to generate the PDF report');
                return;
            }

            const reportRows = data.length ? data.map(record => `
                <tr>
                    <td>${htmlEscape(record.full_name || 'N/A')}</td>
                    <td>${htmlEscape(record.department || 'N/A')}</td>
                    <td><span class="type ${String(record.type).toLowerCase()}">${htmlEscape(record.type || 'N/A')}</span></td>
                    <td>${htmlEscape(record.absence_date || 'N/A')}</td>
                    <td>${htmlEscape(record.reason || 'Not provided')}</td>
                </tr>`).join('') : '<tr><td colspan="5" class="empty">No records found for the selected filters.</td></tr>';

            const title = 'Absence & Late Management Report';
            const generated = new Date().toLocaleString();
            const filters = `${startDate || 'All dates'} to ${endDate || 'All dates'}${type ? ` · Type: ${type}` : ''}`;
            const logoUrl = new URL('../Bestlink College of the Philippines.jpeg', window.location.href).href;

            reportWindow.document.write(`<!doctype html><html><head><title>${title}</title><style>
                @page { size: A4 landscape; margin: 14mm; }
                * { box-sizing: border-box; }
                body { margin: 0; color: #172b4d; font-family: Arial, sans-serif; font-size: 11px; }
                .institution-header { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 0 0 12px; border-bottom: 1px solid #dbe5f0; margin-bottom: 16px; }
                .institution-header img { width: 66px; height: 66px; object-fit: contain; display: block; flex: 0 0 auto; }
                .institution-details { text-align: left; }
                .institution-name { margin: 0; color: #0b4175; font-size: 18px; font-weight: 800; letter-spacing: .2px; }
                .institution-address { margin: 4px 0 0; color: #667085; font-size: 10px; }
                .report-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0b5cab; padding-bottom: 14px; margin-bottom: 18px; }
                h1 { color: #0b4175; font-size: 23px; margin: 0 0 6px; }
                .subtitle, .meta { color: #667085; margin: 3px 0; }
                .summary { display: inline-block; padding: 9px 13px; border-radius: 8px; background: #eef6ff; color: #0b5cab; font-size: 12px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th { background: #0b5cab; color: white; padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
                td { padding: 9px 8px; border-bottom: 1px solid #dbe5f0; vertical-align: top; word-wrap: break-word; }
                tr:nth-child(even) { background: #f6f9fc; }
                .type, .status { display: inline-block; padding: 4px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
                .type.absent { background: #fde8e8; color: #b42318; } .type.late { background: #fff2cc; color: #8a5b00; }
                .status.pending { background: #fff2cc; color: #8a5b00; } .status.approved { background: #dcfce7; color: #166534; } .status.rejected { background: #fde8e8; color: #b42318; }
                .empty { text-align: center; padding: 30px; color: #667085; }
                .footer { margin-top: 18px; color: #667085; font-size: 10px; text-align: right; }
                @media print { .no-print { display: none; } }
            </style></head><body>
                <div class="institution-header">
                    <img src="${logoUrl}" alt="Bestlink College of the Philippines logo">
                    <div class="institution-details">
                        <h2 class="institution-name">Bestlink College of the Philippines</h2>
                        <p class="institution-address">Lot 1 Ipo Road Brgy. Minuyan Proper, City of San Jose Del Monte, Bulacan</p>
                    </div>
                </div>
                <div class="report-header"><div><h1>${title}</h1><p class="subtitle">${filters}</p><p class="meta">Generated: ${generated}</p></div><div class="summary">${data.length} record${data.length === 1 ? '' : 's'}</div></div>
                <table><thead><tr><th>Employee</th><th>Department</th><th>Type</th><th>Date</th><th>Reason</th></tr></thead><tbody>${reportRows}</tbody></table>
                <div class="footer">Human Resource Management System · Official report</div>
                <button class="no-print" onclick="window.print()" style="margin-top:18px;padding:9px 16px;background:#0b5cab;color:#fff;border:0;border-radius:5px;cursor:pointer;"><i class="fas fa-print"></i> Print / Save as PDF</button>
            </body></html>`);
            reportWindow.document.close();
            reportWindow.focus();
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function htmlEscape(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    


        function hidePreloader() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'none';
                preloader.style.visibility = 'hidden';
            }
        }

        function showPreloader() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'flex';
                preloader.style.visibility = 'visible';
            }
        }

        window.addEventListener('load', hidePreloader);

        document.addEventListener('DOMContentLoaded', function() {
            // Delay hiding preloader so animation is visible
            setTimeout(hidePreloader, 6000);
            const navLinks = document.querySelectorAll('a');

            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const href = this.getAttribute('href');
                    if (href && !href.includes('logout') && !href.startsWith('javascript') && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                        showPreloader();
                    }
                });
            });
        });
    