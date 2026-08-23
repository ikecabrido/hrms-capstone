    document.addEventListener('DOMContentLoaded', function () {

        const table = document.getElementById('payrollRequestTable');
        const pagination = document.getElementById('payrollPagination');
        const info = document.getElementById('payrollPaginationInfo');
        const filter = document.getElementById('payrollStatusFilter');

        const rows = Array.from(
            table.querySelectorAll('tr[data-status]')
        );

        const rowsPerPage = 10;

        let currentPage = 1;
        let filteredRows = [...rows];

        function renderTable() {

            const total = filteredRows.length;
            const totalPages = Math.max(
                1,
                Math.ceil(total / rowsPerPage)
            );

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            rows.forEach(row => {
                row.style.display = 'none';
            });

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            filteredRows
                .slice(start, end)
                .forEach(row => {
                    row.style.display = '';
                });

            renderInfo(total, start, end);
            renderPagination(totalPages);
        }

        function renderInfo(total, start, end) {

            if (total === 0) {
                info.textContent = 'No requests found';
                return;
            }

            const from = start + 1;
            const to = Math.min(end, total);

            info.textContent =
                `Showing ${from}–${to} of ${total} requests`;
        }

        function renderPagination(totalPages) {

            pagination.innerHTML = '';

            // Previous
            const previous = createButton(
                '<i class="fas fa-chevron-left"></i>',
                currentPage === 1
            );

            previous.onclick = function () {

                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }

            };

            pagination.appendChild(previous);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {

                const button = createButton(
                    i,
                    false,
                    i === currentPage
                );

                button.onclick = function () {

                    currentPage = i;
                    renderTable();

                };

                pagination.appendChild(button);
            }

            // Next
            const next = createButton(
                '<i class="fas fa-chevron-right"></i>',
                currentPage === totalPages
            );

            next.onclick = function () {

                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }

            };

            pagination.appendChild(next);
        }

        function createButton(
            content,
            disabled = false,
            active = false
        ) {

            const button = document.createElement('button');

            button.type = 'button';
            button.innerHTML = content;
            button.disabled = disabled;

            button.className =
                'w-8 h-8 rounded-lg text-[10px] font-bold transition ' +

                (
                    active
                        ? 'bg-blue-600 text-white'
                        : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50'
                ) +

                (
                    disabled
                        ? ' opacity-40 cursor-not-allowed'
                        : ''
                );

            return button;
        }

        filter.addEventListener('change', function () {

            const selectedStatus = this.value;

            filteredRows = rows.filter(row => {

                if (selectedStatus === 'all') {
                    return true;
                }

                return row.dataset.status === selectedStatus;

            });

            currentPage = 1;

            renderTable();
        });

        renderTable();

    });
    function openPayrollUploadModal(id, employeeName, requestType) {

        document.getElementById('uploadRequestId').value = id;

        document.getElementById('uploadEmployeeName').textContent =
            employeeName + ' • ' + requestType;

        const modal = document.getElementById('payrollUploadModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePayrollUploadModal() {

        const modal = document.getElementById('payrollUploadModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openPayrollRejectModal(id, employeeName) {

        document.getElementById('rejectRequestId').value = id;

        document.getElementById('rejectEmployeeName').textContent =
            employeeName;

        const modal = document.getElementById('payrollRejectModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePayrollRejectModal() {

        const modal = document.getElementById('payrollRejectModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }