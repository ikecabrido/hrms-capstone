
        let currentRecordId = null;

        function filterByStatus(status) {
            let url = 'my_absence_appeals.php';
            if (status) {
                url += `?status=${status}`;
            }
            window.location.href = url;
        }

        function editExcuse(recordId) {
            currentRecordId = recordId;
            fetch(`../app/api/absence_late_management.php?action=get_record&record_id=${recordId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('excuseReason').value = res.data.reason || '';
                        openModal('excuseModal');
                    }
                })
                .catch(err => toastr.error('Failed to load record'));
        }

        function viewDetails(recordId) {
            fetch(`../app/api/absence_late_management.php?action=get_record&record_id=${recordId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const record = res.data;
                        let details = `
                            <div style="margin-bottom: 15px;">
                                <strong>Type:</strong> ${record.type}<br>
                                <strong>Date:</strong> ${new Date(record.absence_date).toLocaleDateString()}<br>
                                <strong>Status:</strong> ${record.excuse_status}<br>
                                <strong>Reason:</strong><br>
                                <p style="background: #f8f9fa; padding: 10px; border-radius: 4px;">
                                    ${htmlEscape(record.reason || 'Not provided')}
                                </p>
                                ${record.approval_notes ? `
                                    <strong>HR Review Notes:</strong><br>
                                    <p style="background: #f0f8ff; padding: 10px; border-radius: 4px;">
                                        ${htmlEscape(record.approval_notes)}
                                    </p>
                                ` : ''}
                            </div>
                        `;
                        alert(details);
                    }
                })
                .catch(err => toastr.error('Failed to load details'));
        }

        document.getElementById('excuseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('excuseReason').value;

            fetch('../app/api/absence_late_management.php?action=submit_excuse', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    record_id: currentRecordId,
                    reason: reason
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    toastr.success(res.message);
                    closeModal('excuseModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(res.message);
                }
            })
            .catch(err => toastr.error('Failed to submit excuse'));
        });

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
    