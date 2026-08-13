console.log('leave approvals script loaded');
        function openApproveModal(requestId) {
            console.log('openApproveModal', requestId);
            document.getElementById('approveRequestId').value = requestId;
            document.getElementById('approveModal').classList.add('active');
        }

        function openRejectModal(requestId) {
            console.log('openRejectModal', requestId);
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectModal').classList.add('active');
        }

        function formatLeaveDays(value) {
            var num = parseFloat(value);
            if (!Number.isFinite(num)) {
                return '';
            }
            return num % 1 === 0 ? num.toString() : num.toFixed(2).replace(/\.0+$/, '');
        }

        function openBalanceModal(employeeId, fullName) {
            const modal = document.getElementById('balanceModal');
            const body = document.getElementById('balanceModalBody');
            const title = document.getElementById('balanceModalTitle');

            title.textContent = fullName + ' — Leave Balances';
            body.innerHTML = '<p>Loading leave balances...</p>';
            modal.classList.add('active');

            var balanceApiUrl = (window.__TA_CONFIG || {}).balanceApiUrl;
            fetch(balanceApiUrl + '?employee_id=' + encodeURIComponent(employeeId))
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            body.innerHTML = '<p class="text-danger">Unable to load leave balances. Server returned ' + response.status + '.</p>';
                            throw new Error('HTTP ' + response.status + ': ' + text);
                        });
                    }

                    return response.json().then(function(data) {
                        return { status: response.status, body: data };
                    });
                })
                .then(function(result) {
                    var data = result.body;
                    if (!data.success) {
                        body.innerHTML = '<p class="text-danger">' + (data.message || 'Unable to load leave balances.') + '</p>';
                        return;
                    }

                    var balances = data.data;
                    if (!balances || balances.length === 0) {
                        body.innerHTML = '<p>No leave balances available for this employee.</p>';
                        return;
                    }

                    var rows = balances.map(function(balance) {
                        return '<tr>' +
                               '<td>' + (balance.leave_type_name || '') + '</td>' +
                               '<td>' + formatLeaveDays(balance.total_days) + '</td>' +
                               '<td>' + formatLeaveDays(balance.used_days) + '</td>' +
                               '<td>' + formatLeaveDays(balance.remaining_days) + '</td>' +
                               '</tr>';
                    }).join('');

                    body.innerHTML = '<div class="table-responsive">' +
                                     '<table class="approvals-table">' +
                                     '<thead><tr>' +
                                     '<th>Leave Type</th>' +
                                     '<th>Total Days</th>' +
                                     '<th>Used</th>' +
                                     '<th>Remaining</th>' +
                                     '</tr></thead>' +
                                     '<tbody>' + rows + '</tbody>' +
                                     '</table></div>';
                })
                .catch(function() {
                    body.innerHTML = '<p class="text-danger">Unable to load leave balances at this time.</p>';
                });
        }

        function debounce(fn, delay) {
            var timer;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function() {
                    fn.apply(context, args);
                }, delay);
            };
        }

        function attachLiveSearch() {
            var searchInput = document.querySelector('input[name="employee_search"]');
            if (!searchInput) return;

            searchInput.addEventListener('input', debounce(function() {
                var form = this.closest('form');
                if (!form) return;
                var url = new URL(window.location.href);
                url.searchParams.set('employee_search', this.value);
                url.searchParams.set('employee_page', 1);
                url.searchParams.set('leave_page', (window.__TA_CONFIG || {}).leavePage || 1);
                url.searchParams.set('employee_page_size', (window.__TA_CONFIG || {}).recordsPerPage || 25);
                window.location.href = url.toString();
            }, 300));
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            var balanceButtons = document.querySelectorAll('.balance-button');
            balanceButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openBalanceModal(this.dataset.employeeId, this.dataset.fullName);
                });
            });
            var approveButtons = document.querySelectorAll('.approve-request-btn');
            approveButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openApproveModal(this.dataset.requestId);
                });
            });
            var rejectButtons = document.querySelectorAll('.reject-request-btn');
            rejectButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openRejectModal(this.dataset.requestId);
                });
            });
            var approveForm = document.getElementById('approveForm');
            if (approveForm) {
                approveForm.addEventListener('submit', function() {
                    console.log('approveForm submitted');
                });
            }
            var rejectForm = document.getElementById('rejectForm');
            if (rejectForm) {
                rejectForm.addEventListener('submit', function() {
                    console.log('rejectForm submitted');
                });
            }
            attachLiveSearch();
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        function changeEmployeePageSize(pageSize) {
            const searchValue = document.querySelector('input[name="employee_search"]').value;
            const url = new URL(window.location.href);
            url.searchParams.set('employee_page_size', pageSize);
            url.searchParams.set('employee_page', 1);
            url.searchParams.set('leave_page', (window.__TA_CONFIG || {}).leavePage || 1);
            url.searchParams.set('employee_search', searchValue || '');
            window.location.href = url.toString();
        }

        function provisionLeaveBalances() {
            if (!confirm('Provision leave balances for all active employees now?')) {
                return;
            }

            var provisionUrl = (window.__TA_CONFIG || {}).provisionUrl;
            fetch(provisionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ year: new Date().getFullYear() })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    if (!response.ok) {
                        throw new Error(data.message || 'Request failed');
                    }
                    return data;
                });
            })
            .then(function(data) {
                if (data.success) {
                    alert('Leave balances provisioned for ' + data.employees_processed + ' active employees for ' + data.year + '.');
                    window.location.reload();
                } else {
                    alert('Provisioning failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(function(error) {
                alert('Unable to provision leave balances. ' + error.message);
            });
        }

        // Load dark mode preference (default to light mode for time_attendance)
        window.preloaderHold = true;

        window.addEventListener('load', function() {
            const darkModeSetting = localStorage.getItem('darkMode');
            const darkMode = darkModeSetting === 'true'; // Only true if explicitly set
            
            // Reset to light mode by default on each page load for time_attendance
            if (!darkModeSetting) {
                localStorage.setItem('darkMode', 'false');
            }
            
            if (darkMode) {
                document.body.classList.add('dark-mode');
            }

            if (window.releasePreloader) {
                window.releasePreloader(1200);
            }
        });
    