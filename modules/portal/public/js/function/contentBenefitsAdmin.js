    function openBenefitUploadModal(id, employee, type) {
        document.getElementById('uploadBenefitId').value = id;
        document.getElementById('uploadEmployeeName').value = employee;
        document.getElementById('uploadRecordType').value = type;

        new bootstrap.Modal(
            document.getElementById('benefitUploadModal')
        ).show();
    }
    document.addEventListener('DOMContentLoaded', function () {

        const table = document.getElementById('benefitTable');
        const rows = Array.from(table.querySelectorAll('.benefit-row'));
        const pagination = document.getElementById('benefitPagination');
        const info = document.getElementById('benefitPaginationInfo');

        const rowsPerPage = 5;
        let currentPage = 1;

        function renderPagination() {

            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            if (!totalRows) {
                info.textContent = 'No records found';
                pagination.innerHTML = '';
                return;
            }

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {
                row.style.display =
                    index >= start && index < end ? '' : 'none';
            });

            info.textContent =
                `Showing ${start + 1}-${Math.min(end, totalRows)} of ${totalRows} records`;

            pagination.innerHTML = '';

            // Previous
            const previous = document.createElement('button');

            previous.innerHTML = '<i class="fas fa-chevron-left"></i>';

            previous.className =
                'w-7 h-7 rounded-lg border border-slate-200 text-[10px] text-slate-500 hover:bg-slate-100 disabled:opacity-40';

            previous.disabled = currentPage === 1;

            previous.onclick = function () {
                if (currentPage > 1) {
                    currentPage--;
                    renderPagination();
                }
            };

            pagination.appendChild(previous);


            // Pages
            for (let i = 1; i <= totalPages; i++) {

                const button = document.createElement('button');

                button.textContent = i;

                button.className =
                    'w-7 h-7 rounded-lg border text-[10px] font-semibold transition ' +
                    (i === currentPage
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'border-slate-200 text-slate-500 hover:bg-slate-100');

                button.onclick = function () {
                    currentPage = i;
                    renderPagination();
                };

                pagination.appendChild(button);
            }


            // Next
            const next = document.createElement('button');

            next.innerHTML = '<i class="fas fa-chevron-right"></i>';

            next.className =
                'w-7 h-7 rounded-lg border border-slate-200 text-[10px] text-slate-500 hover:bg-slate-100 disabled:opacity-40';

            next.disabled = currentPage === totalPages;

            next.onclick = function () {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPagination();
                }
            };

            pagination.appendChild(next);
        }

        renderPagination();

    });