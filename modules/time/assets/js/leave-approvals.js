console.log('leave approvals script loaded');
        // Ensure toastr is configured if available
        if (typeof toastr !== 'undefined') {
            toastr.options = toastr.options || {};
            toastr.options.positionClass = toastr.options.positionClass || 'toast-top-right';
            toastr.options.timeOut = toastr.options.timeOut || 4000;
        }
        // Safe toastr wrapper to avoid crashes if toastr is in a bad state
        function safeToastr(type, message, title) {
            try {
                if (typeof toastr !== 'undefined' && toastr && typeof toastr[type] === 'function') {
                    toastr[type](message, title);
                    return;
                }
                // fallback logging
                if (type === 'error' || type === 'warning') console.error(message);
                else console.log(message);
            } catch (err) {
                console.error('toastr invocation failed', err);
                try { window.alert(message); } catch (e) {}
            }
        }
        function openApproveModal(requestId) {
            console.log('openApproveModal', requestId);
            document.getElementById('approveRequestId').value = requestId;
            const modal = document.getElementById('approveModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            // focus first textarea or button inside modal
            const focusable = modal.querySelector('textarea, button, input');
            if (focusable) focusable.focus();
        }

        function openRejectModal(requestId) {
            console.log('openRejectModal', requestId);
            document.getElementById('rejectRequestId').value = requestId;
            const modal = document.getElementById('rejectModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            const focusable = modal.querySelector('textarea, button, input');
            if (focusable) focusable.focus();
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
            modal.setAttribute('aria-hidden', 'false');
            // focus the modal close button if present
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) closeBtn.focus();

            var balanceApiUrl = (window.__TA_CONFIG || {}).balanceApiUrl;
            fetch(balanceApiUrl + '?employee_id=' + encodeURIComponent(employeeId), { credentials: 'same-origin' })
                .then(function(response) {
                    if (response.status === 401) {
                        body.innerHTML = '<p class="text-danger">Unauthorized. Please log in to view leave balances.</p>';
                        safeToastr('error', 'Unauthorized — please log in');
                        throw new Error('Unauthorized');
                    }

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

        // Close modals on Escape key for accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                var modals = document.querySelectorAll('.modal.active');
                modals.forEach(function(m) {
                    m.classList.remove('active');
                    m.setAttribute('aria-hidden', 'true');
                });
            }
        });

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

        function initLeaveApprovals() {
            console.log('initLeaveApprovals');
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
                approveForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const requestId = document.getElementById('approveRequestId').value;
                    const remarks = approveForm.querySelector('textarea[name="remarks"]').value || '';
                    const apiUrl = (window.__TA_CONFIG || {}).approveLeaveUrl;
                    if (!apiUrl) {
                        safeToastr('error', 'Approve API not configured');
                        console.error('Approve API not configured');
                        return;
                    }

                    fetch(apiUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ leave_request_id: parseInt(requestId, 10), action: 'APPROVE', remarks: remarks })
                    })
                    .then(function(res) { return res.json().catch(function(){ return { success: false, message: 'Invalid JSON response' }; }); })
                    .then(function(data) {
                        if (data && data.success) {
                            // remove the row for this request
                            const btn = document.querySelector('.approve-request-btn[data-request-id="' + requestId + '"]');
                            if (btn) {
                                const row = btn.closest('tr');
                                if (row) row.remove();
                            }
                            closeModal('approveModal');
                            safeToastr('success', data.message || 'Leave approved');
                        } else {
                            safeToastr('error', data.message || 'Failed to approve leave');
                        }
                    })
                    .catch(function(err) {
                        console.error('Approve error', err);
                        safeToastr('error', 'Unable to approve leave at this time');
                    });
                });
            }
            var rejectForm = document.getElementById('rejectForm');
            if (rejectForm) {
                rejectForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const requestId = document.getElementById('rejectRequestId').value;
                    const remarks = rejectForm.querySelector('textarea[name="remarks"]').value || '';
                    if (!remarks || remarks.trim() === '') {
                        safeToastr('warning', 'Rejection reason is required');
                        return;
                    }
                    const apiUrl = (window.__TA_CONFIG || {}).approveLeaveUrl;
                    if (!apiUrl) {
                        safeToastr('error', 'Approve API not configured');
                        console.error('Approve API not configured');
                        return;
                    }

                    fetch(apiUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ leave_request_id: parseInt(requestId, 10), action: 'REJECT', remarks: remarks })
                    })
                    .then(function(res) { return res.json().catch(function(){ return { success: false, message: 'Invalid JSON response' }; }); })
                    .then(function(data) {
                        if (data && data.success) {
                            const btn = document.querySelector('.reject-request-btn[data-request-id="' + requestId + '"]');
                            if (btn) {
                                const row = btn.closest('tr');
                                if (row) row.remove();
                            }
                            closeModal('rejectModal');
                            safeToastr('success', data.message || 'Leave rejected');
                        } else {
                            safeToastr('error', data.message || 'Failed to reject leave');
                        }
                    })
                    .catch(function(err) {
                        console.error('Reject error', err);
                        safeToastr('error', 'Unable to reject leave at this time');
                    });
                });
            }
            attachLiveSearch();
        }

        // Attach handlers now if DOM already loaded, otherwise wait for DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLeaveApprovals);
        } else {
            initLeaveApprovals();
        }

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

        /**
         * Validate and submit jump-to-page forms produced by render_pagination().
         * Returns true to allow normal form submission, or false to block it.
         */
        function jumpToPage(form, pageParam) {
            try {
                if (!form || !pageParam) return false;
                var input = form.querySelector('input[name="' + pageParam + '"]');
                if (!input) return false;
                var val = parseInt(input.value, 10);
                var max = parseInt(input.getAttribute('max') || input.max || 0, 10) || 0;
                if (!val || val < 1) {
                    safeToastr('warning', 'Please enter a valid page number (>= 1)');
                    input.focus();
                    return false;
                }
                if (max && val > max) {
                    safeToastr('warning', 'Page number exceeds maximum (' + max + ')');
                    input.focus();
                    return false;
                }
                // ensure integer value is set
                input.value = val;
                return true;
            } catch (err) {
                console.error('jumpToPage error', err);
                return false;
            }
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
                    if (typeof toastr !== 'undefined') toastr.success('Leave balances provisioned for ' + data.employees_processed + ' active employees for ' + data.year + '.');
                    else alert('Leave balances provisioned for ' + data.employees_processed + ' active employees for ' + data.year + '.');
                    window.location.reload();
                } else {
                    if (typeof toastr !== 'undefined') toastr.error('Provisioning failed: ' + (data.message || 'Unknown error'));
                    else alert('Provisioning failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(function(error) {
                if (typeof toastr !== 'undefined') toastr.error('Unable to provision leave balances. ' + error.message);
                else alert('Unable to provision leave balances. ' + error.message);
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
    