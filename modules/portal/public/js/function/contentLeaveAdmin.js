     document.addEventListener('DOMContentLoaded', function () {

        const table = document.getElementById('leaveRequestTable');
        const filter = document.getElementById('leaveStatusFilter');
        const pagination = document.getElementById('pagination');
        const paginationInfo = document.getElementById('paginationInfo');

        const rowsPerPage = 10;
        let currentPage = 1;

        function getRows() {
            return Array.from(
                table.querySelectorAll('tr[data-status]')
            );
        }

        function getFilteredRows() {
            const status = filter.value;

            return getRows().filter(row => {
                return status === 'all' ||
                    row.dataset.status === status;
            });
        }

        function renderTable() {

            const rows = getRows();
            const filteredRows = getFilteredRows();

            const totalPages = Math.max(
                1,
                Math.ceil(filteredRows.length / rowsPerPage)
            );

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            rows.forEach(row => {
                row.style.display = 'none';
            });

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
            });

            renderPagination(totalPages, filteredRows.length);
        }

        function renderPagination(totalPages, totalRows) {

            pagination.innerHTML = '';

            if (totalRows === 0) {
                paginationInfo.textContent = 'No requests found';
                return;
            }

            const start = (currentPage - 1) * rowsPerPage + 1;
            const end = Math.min(
                currentPage * rowsPerPage,
                totalRows
            );

            paginationInfo.textContent =
                `Showing ${start}-${end} of ${totalRows} requests`;

            // Previous
            const previous = document.createElement('button');

            previous.innerHTML = '<i class="fas fa-chevron-left"></i>';

            previous.disabled = currentPage === 1;

            previous.className = `
                w-8 h-8 rounded-lg border border-slate-200
                text-[10px] flex items-center justify-center
                transition
                ${currentPage === 1
                    ? 'text-slate-300 cursor-not-allowed'
                    : 'text-slate-600 hover:bg-slate-50'}
            `;

            previous.onclick = function () {

                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }

            };

            pagination.appendChild(previous);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {

                const button = document.createElement('button');

                button.textContent = i;

                button.className = `
                    w-8 h-8 rounded-lg border text-[10px] font-semibold
                    transition
                    ${i === currentPage
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'}
                `;

                button.onclick = function () {

                    currentPage = i;
                    renderTable();

                };

                pagination.appendChild(button);
            }

            // Next
            const next = document.createElement('button');

            next.innerHTML = '<i class="fas fa-chevron-right"></i>';

            next.disabled = currentPage === totalPages;

            next.className = `
                w-8 h-8 rounded-lg border border-slate-200
                text-[10px] flex items-center justify-center
                transition
                ${currentPage === totalPages
                    ? 'text-slate-300 cursor-not-allowed'
                    : 'text-slate-600 hover:bg-slate-50'}
            `;

            next.onclick = function () {

                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }

            };

            pagination.appendChild(next);
        }

        filter.addEventListener('change', function () {

            currentPage = 1;
            renderTable();

        });

        renderTable();

    });
 
 function openRejectModal(id, employee, leaveType, start, end) {

            document.getElementById('rejectLeaveId').value = id;
            document.getElementById('rejectEmployee').textContent = employee;
            document.getElementById('rejectLeaveType').textContent = leaveType;
            document.getElementById('rejectDates').textContent =
                start === end ? start : start + ' - ' + end;

            document.getElementById('rejectReason').value = '';
            document.getElementById('quickRejectReason').value = '';

            const modal = document.getElementById('rejectLeaveModal');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            updateRejectButton();
        }

        function closeRejectModal() {

            const modal = document.getElementById('rejectLeaveModal');

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function updateRejectButton() {

            const reason = document
                .getElementById('rejectReason')
                .value
                .trim();

            document.getElementById('confirmRejectButton').disabled =
                reason.length === 0;
        }

        document
            .getElementById('rejectReason')
            .addEventListener('input', updateRejectButton);

        document
            .getElementById('quickRejectReason')
            .addEventListener('change', function () {

                if (this.value) {
                    document.getElementById('rejectReason').value = this.value;
                    updateRejectButton();
                }

            });

        document
            .getElementById('rejectLeaveModal')
            .addEventListener('click', function (e) {

                if (e.target === this) {
                    closeRejectModal();
                }

            });


        // STATUS FILTER
        document
            .getElementById('leaveStatusFilter')
            .addEventListener('change', function () {

                const selected = this.value;

                document
                    .querySelectorAll('#leaveRequestTable tr[data-status]')
                    .forEach(row => {

                        row.style.display =
                            selected === 'all' ||
                                row.dataset.status === selected
                                ? ''
                                : 'none';

                    });

            });
            