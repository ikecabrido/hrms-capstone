
window.openBenefitUploadModal = function (id, employee, type) {
    document.getElementById('uploadBenefitId').value = id;
    document.getElementById('uploadEmployeeName').value = employee;
    document.getElementById('uploadRecordType').value = type;

    const modalElement = document.getElementById('benefitUploadModal');

    if (!modalElement) {
        console.error('Benefit upload modal was not found.');
        return;
    }

    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.error('Bootstrap Modal is not loaded.');
        return;
    }

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
};

document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('benefitTable');
    const pagination = document.getElementById('benefitPagination');
    const info = document.getElementById('benefitPaginationInfo');

    if (!table || !pagination || !info) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('.benefit-row'));
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

        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        rows.forEach((row, index) => {
            row.style.display = index >= start && index < end ? '' : 'none';
        });

        info.textContent =
            `Showing ${start + 1}-${Math.min(end, totalRows)} of ${totalRows} records`;

        pagination.innerHTML = '';

        function createButton(label, className, disabled, onClick) {
            const button = document.createElement('button');
            button.type = 'button';
            button.innerHTML = label;
            button.className = className;
            button.disabled = disabled;
            button.addEventListener('click', onClick);
            pagination.appendChild(button);
        }

        const baseClass =
            'w-7 h-7 rounded-lg border border-slate-200 text-[10px] text-slate-500 hover:bg-slate-100 disabled:opacity-40';

        createButton(
            '<i class="fas fa-chevron-left"></i>',
            baseClass,
            currentPage === 1,
            () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderPagination();
                }
            }
        );

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = i;
            button.className =
                'w-7 h-7 rounded-lg border text-[10px] font-semibold transition ' +
                (i === currentPage
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'border-slate-200 text-slate-500 hover:bg-slate-100');

            button.addEventListener('click', () => {
                currentPage = i;
                renderPagination();
            });

            pagination.appendChild(button);
        }

        createButton(
            '<i class="fas fa-chevron-right"></i>',
            baseClass,
            currentPage === totalPages,
            () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPagination();
                }
            }
        );
    }

    renderPagination();
});
