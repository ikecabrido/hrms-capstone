
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
    


    const config = window.__TA_CONFIG || {};
    const attendanceData = config.attendanceData || {};

    function showDetails(date) {
        const data = attendanceData[parseInt(date.split('-')[2])];
        if (!data) return;

        const dateObj = new Date(date);
        const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        
        document.getElementById('modalDate').textContent = dateStr;

        // Fetch detailed records for this date
        fetch(`../app/api/get_day_records.php?date=${date}&employee_id=${encodeURIComponent(config.employeeId || '')}`)
            .then(response => response.json())
            .then(records => {
                let html = '';
                if (records.length === 0) {
                    html = '<p style="text-align: center; color: #7f8c8d;">No records for this day.</p>';
                } else {
                    records.forEach(record => {
                        const time_in = new Date(record.time_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                        const time_out = record.time_out ? new Date(record.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : 'N/A';
                        
                        html += `
                            <div class="record-item">
                                <div class="record-time">${time_in} - ${time_out}</div>
                                <div class="record-duration">Duration: ${record.total_hours_worked ? parseFloat(record.total_hours_worked).toFixed(1) : '0'} hours</div>
                                <div class="record-duration">Status: ${record.time_out ? 'Completed' : 'In Progress'}</div>
                            </div>
                        `;
                    });
                }
                document.getElementById('modalBody').innerHTML = html;
                document.getElementById('detailsModal').classList.add('show');
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalBody').innerHTML = '<p style="color: red;">Error loading details.</p>';
                document.getElementById('detailsModal').classList.add('show');
            });
    }

    function closeDetails(event) {
        if (event && event.target.id !== 'detailsModal') return;
        document.getElementById('detailsModal').classList.remove('show');
    }

    function changeEmployee() {
        const employeeId = document.getElementById('employee_select').value;
        window.location.href = `?month=${encodeURIComponent(config.month)}&year=${encodeURIComponent(config.year)}&employee_id=${encodeURIComponent(employeeId)}`;
    }

    // Close modal on Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDetails();
        }
    });
    