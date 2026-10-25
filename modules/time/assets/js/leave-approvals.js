console.log('leave approvals script loaded');

(function () {
    const runtime = window.__taLeaveApprovalsRuntime || (window.__taLeaveApprovalsRuntime = {});

    if (typeof toastr !== 'undefined') {
        toastr.options = toastr.options || {};
        toastr.options.positionClass = toastr.options.positionClass || 'toast-top-right';
        toastr.options.timeOut = toastr.options.timeOut || 4000;
    }

    function safeToastr(type, message, title) {
        try {
            if (typeof toastr !== 'undefined' && toastr && typeof toastr[type] === 'function') {
                toastr[type](message, title);
                return;
            }
            if (type === 'error' || type === 'warning') console.error(message);
            else console.log(message);
        } catch (err) {
            console.error('toastr invocation failed', err);
            try { window.alert(message); } catch (e) {}
        }
    }

    function openApproveModal(requestId) {
        console.log('openApproveModal', requestId);
        const approveRequestId = document.getElementById('approveRequestId');
        if (approveRequestId) approveRequestId.value = requestId;
        const modal = document.getElementById('approveModal');
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        const focusable = modal.querySelector('textarea, button, input');
        if (focusable) focusable.focus();
    }

    function openRejectModal(requestId) {
        console.log('openRejectModal', requestId);
        const rejectRequestId = document.getElementById('rejectRequestId');
        if (rejectRequestId) rejectRequestId.value = requestId;
        const modal = document.getElementById('rejectModal');
        if (!modal) return;
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

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseDocumentList(rawDocuments, fallbackDocument) {
        var docs = [];
        if (rawDocuments) {
            try {
                var parsed = JSON.parse(rawDocuments);
                if (Array.isArray(parsed)) {
                    docs = parsed.filter(function(entry) {
                        return typeof entry === 'string' && entry.trim() !== '';
                    });
                }
            } catch (e) {
                docs = [];
            }
        }
        if (!docs.length && fallbackDocument) {
            docs = [fallbackDocument];
        }
        return docs;
    }

    function openBalanceModal(employeeId, fullName) {
        const modal = document.getElementById('balanceModal');
        const body = document.getElementById('balanceModalBody');
        const title = document.getElementById('balanceModalTitle');
        if (!modal || !body || !title) return;

        title.textContent = fullName + ' — Leave Balances';
        body.innerHTML = '<p>Loading leave balances...</p>';
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
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
                    var freq = (balance.accrual_frequency || '').toLowerCase();
                    var freqLabel = '';
                    if (freq === 'monthly') freqLabel = ' <span class="muted">(accrues monthly)</span>';
                    else if (freq === 'yearly') freqLabel = ' <span class="muted">(accrues yearly)</span>';
                    return '<tr>' +
                           '<td>' + (balance.leave_type_name || '') + freqLabel + '</td>' +
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
        if (searchInput.dataset.leaveSearchBound === '1') return;
        searchInput.dataset.leaveSearchBound = '1';

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
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    }

    function bindLeaveApprovalsHandlers() {
        var historyButtons = document.querySelectorAll('.view-history-btn');
        historyButtons.forEach(function(button) {
            button.onclick = function() {
                const modal = document.getElementById('leaveHistoryModal');
                const body = document.getElementById('leaveHistoryModalBody');
                const title = document.getElementById('leaveHistoryModalTitle');
                if (!modal || !body || !title) return;

                const employee = this.dataset.employee || 'Employee';
                const leaveType = this.dataset.leaveType || 'Leave';
                const startDate = this.dataset.startDate || '—';
                const endDate = this.dataset.endDate || '—';
                const status = this.dataset.status || 'Pending';
                const reason = this.dataset.reason || this.dataset.details || 'No details provided.';
                const details = this.dataset.details || 'No details provided.';
                const rejectReason = this.dataset.rejectReason || '';
                const submitted = this.dataset.submitted || '';
                const decided = this.dataset.decided || '';
                const rawDocumentList = this.dataset.documents || '';
                const fallbackDocument = this.dataset.document || '';
                const supportingDocuments = parseDocumentList(rawDocumentList, fallbackDocument);

                title.textContent = employee + ' — ' + leaveType;

                const detailRows = [
                    '<p><strong>Submitted:</strong> ' + escapeHtml(submitted || '—') + '</p>',
                    '<p><strong>Status:</strong> ' + escapeHtml(status) + '</p>',
                    '<p><strong>Start:</strong> ' + escapeHtml(startDate) + '</p>',
                    '<p><strong>End:</strong> ' + escapeHtml(endDate) + '</p>',
                    '<p><strong>Leave Type:</strong> ' + escapeHtml(leaveType) + '</p>',
                    '<p><strong>Decided:</strong> ' + escapeHtml(decided || '—') + '</p>',
                    '<p><strong>Reason:</strong> ' + escapeHtml(reason || details) + '</p>'
                ];

                if (String(status).toLowerCase() === 'rejected') {
                    detailRows.push('<p><strong>Rejection reason:</strong> ' + escapeHtml(rejectReason || '—') + '</p>');
                }

                if (supportingDocuments.length) {
                    const documentLinks = supportingDocuments.map(function(docPath) {
                        const safePath = escapeHtml(docPath);
                        const fileName = escapeHtml(docPath.split('/').pop() || docPath);
                        return '<a href="' + safePath + '" target="_blank" rel="noopener">' + fileName + '</a>';
                    }).join(', ');
                    detailRows.push('<p><strong>Supporting documents:</strong> ' + documentLinks + '</p>');
                } else {
                    detailRows.push('<p><strong>Supporting documents:</strong> No supporting documents</p>');
                }

                body.innerHTML = '<div class="detail-grid">' + detailRows.join('') + '</div>';

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            };
        });

        var balanceButtons = document.querySelectorAll('.balance-button');
        balanceButtons.forEach(function(button) {
            button.onclick = function() {
                openBalanceModal(this.dataset.employeeId, this.dataset.fullName);
            };
        });

        var approveButtons = document.querySelectorAll('.approve-request-btn');
        approveButtons.forEach(function(button) {
            button.onclick = function() {
                openApproveModal(this.dataset.requestId);
            };
        });

        var rejectButtons = document.querySelectorAll('.reject-request-btn');
        rejectButtons.forEach(function(button) {
            button.onclick = function() {
                openRejectModal(this.dataset.requestId);
            };
        });

        var approveForm = document.getElementById('approveForm');
        if (approveForm && !approveForm.dataset.bound) {
            approveForm.dataset.bound = '1';
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
        if (rejectForm && !rejectForm.dataset.bound) {
            rejectForm.dataset.bound = '1';
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
    }

    function bindEscapeKey() {
        if (runtime.escapeKeyBound) return;
        runtime.escapeKeyBound = true;
        runtime.escapeKeyHandler = function (e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                var modals = document.querySelectorAll('.modal.active');
                modals.forEach(function(m) {
                    m.classList.remove('active');
                    m.setAttribute('aria-hidden', 'true');
                });
            }
        };
        document.addEventListener('keydown', runtime.escapeKeyHandler);
    }

    function bindWindowModalClick() {
        if (runtime.windowClickBound) return;
        runtime.windowClickBound = true;
        runtime.windowClickHandler = function (event) {
            if (event.target && event.target.classList && event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        };
        window.addEventListener('click', runtime.windowClickHandler);
    }

    function initLeaveApprovalsPage() {
        console.log('[TA INIT] Leave approvals initialized');
        bindEscapeKey();
        bindWindowModalClick();
        bindLeaveApprovalsHandlers();
        attachLiveSearch();
    }

    window.initLeaveApprovalsPage = initLeaveApprovalsPage;

    if (typeof window.registerPageCleanup === 'function') {
        window.registerPageCleanup(function () {
            if (runtime.escapeKeyHandler) {
                document.removeEventListener('keydown', runtime.escapeKeyHandler);
                runtime.escapeKeyHandler = null;
                runtime.escapeKeyBound = false;
            }
            if (runtime.windowClickHandler) {
                window.removeEventListener('click', runtime.windowClickHandler);
                runtime.windowClickHandler = null;
                runtime.windowClickBound = false;
            }
            const liveSearch = document.querySelector('input[name="employee_search"]');
            if (liveSearch) liveSearch.dataset.leaveSearchBound = '0';
        });
    }

    window.changeEmployeePageSize = function(pageSize) {
        const searchValue = document.querySelector('input[name="employee_search"]') ? document.querySelector('input[name="employee_search"]').value : '';
        const url = new URL(window.location.href);
        url.searchParams.set('employee_page_size', pageSize);
        url.searchParams.set('employee_page', 1);
        url.searchParams.set('leave_page', (window.__TA_CONFIG || {}).leavePage || 1);
        url.searchParams.set('employee_search', searchValue || '');
        window.location.href = url.toString();
    };

    window.jumpToPage = function(form, pageParam) {
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
            input.value = val;
            return true;
        } catch (err) {
            console.error('jumpToPage error', err);
            return false;
        }
    };

    window.provisionLeaveBalances = function() {
        if (!confirm('Provision leave balances for all active employees now?')) {
            return;
        }

        var provisionUrl = (window.__TA_CONFIG || {}).provisionUrl;
        fetch(provisionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
    };

    window.preloaderHold = true;
    window.addEventListener('load', function() {
        const darkModeSetting = localStorage.getItem('darkMode');
        const darkMode = darkModeSetting === 'true';
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
})();
