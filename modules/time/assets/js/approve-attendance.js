
        function openApproveModal(recordId, empName, date, timeIn, timeOut) {
            document.getElementById('approveRecordId').value = recordId;
            const infoBox = `
                <strong>Employee:</strong> ${empName}<br>
                <strong>Date:</strong> ${date}<br>
                <strong>Time In:</strong> ${timeIn} | <strong>Time Out:</strong> ${timeOut}
            `;
            document.getElementById('recordInfo').innerHTML = infoBox;
            document.getElementById('approveModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        const approvalPageSize = 15;
        let approvalCurrentPage = 1;

        function renderApprovalPagination() {
            const table = document.querySelector('.approvals-table');
            const pagination = document.getElementById('approvalPagination');
            const info = document.getElementById('approvalPaginationInfo');
            const buttons = document.getElementById('approvalPageButtons');

            if (!table || !pagination || !info || !buttons) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const totalRecords = rows.length;
            const totalPages = Math.max(1, Math.ceil(totalRecords / approvalPageSize));
            approvalCurrentPage = Math.min(approvalCurrentPage, totalPages);

            rows.forEach((row, index) => {
                const firstRecord = (approvalCurrentPage - 1) * approvalPageSize;
                row.style.display = index >= firstRecord && index < firstRecord + approvalPageSize ? '' : 'none';
            });

            const firstVisible = totalRecords === 0 ? 0 : (approvalCurrentPage - 1) * approvalPageSize + 1;
            const lastVisible = Math.min(approvalCurrentPage * approvalPageSize, totalRecords);
            info.textContent = `Showing ${firstVisible}–${lastVisible} of ${totalRecords} pending records`;
            buttons.innerHTML = '';

            const previous = document.createElement('button');
            previous.type = 'button';
            previous.innerHTML = '<i class="fas fa-chevron-left"></i><span class="sr-only"> Previous</span>';
            previous.disabled = approvalCurrentPage === 1;
            previous.addEventListener('click', () => {
                approvalCurrentPage--;
                renderApprovalPagination();
            });
            buttons.appendChild(previous);

            for (let page = 1; page <= totalPages; page++) {
                const pageButton = document.createElement('button');
                pageButton.type = 'button';
                pageButton.textContent = page;
                pageButton.classList.toggle('active', page === approvalCurrentPage);
                pageButton.setAttribute('aria-label', `Go to page ${page}`);
                pageButton.addEventListener('click', () => {
                    approvalCurrentPage = page;
                    renderApprovalPagination();
                });
                buttons.appendChild(pageButton);
            }

            const next = document.createElement('button');
            next.type = 'button';
            next.innerHTML = '<i class="fas fa-chevron-right"></i><span class="sr-only"> Next</span>';
            next.disabled = approvalCurrentPage === totalPages;
            next.addEventListener('click', () => {
                approvalCurrentPage++;
                renderApprovalPagination();
            });
            buttons.appendChild(next);
        }

        renderApprovalPagination();

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // Load dark mode preference (default to light mode for time_attendance)
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
        });
    