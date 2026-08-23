    const rowsPerPage = 10;
    let currentPage = 1;

    const searchInput = document.getElementById('attendanceSearch');
    const statusSelect = document.getElementById('attendanceStatus');

    const rows = Array.from(
        document.querySelectorAll('.attendance-row')
    );

    function getFilteredRows() {

        const search = searchInput?.value.toLowerCase().trim() || '';
        const status = statusSelect?.value || '';

        return rows.filter(row => {

            const name = row.dataset.name || '';
            const id = row.dataset.id || '';
            const rowStatus = row.dataset.status || '';

            const matchesSearch =
                !search ||
                name.includes(search) ||
                id.includes(search);

            const matchesStatus =
                !status ||
                rowStatus === status;

            return matchesSearch && matchesStatus;
        });
    }

    function renderPagination() {

        const filteredRows = getFilteredRows();

        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(
            startIndex + rowsPerPage,
            total
        );

        /* Hide all rows first */
        rows.forEach(row => {
            row.style.display = 'none';
        });

        /* Show current page */
        filteredRows
            .slice(startIndex, endIndex)
            .forEach(row => {
                row.style.display = '';
            });

        /* Counter */
        document.getElementById('paginationStart').textContent =
            total ? startIndex + 1 : 0;

        document.getElementById('paginationEnd').textContent =
            endIndex;

        document.getElementById('paginationTotal').textContent =
            total;

        /* Buttons */
        document.getElementById('prevPage').disabled =
            currentPage <= 1;

        document.getElementById('nextPage').disabled =
            currentPage >= totalPages;

        renderPageNumbers(totalPages);
    }

    function renderPageNumbers(totalPages) {

        const container =
            document.getElementById('paginationNumbers');

        container.innerHTML = '';

        /*
         * Don't show page numbers if there is
         * only one page.
         */
        if (totalPages <= 1) {
            return;
        }

        for (let i = 1; i <= totalPages; i++) {

            const button = document.createElement('button');

            button.type = 'button';

            button.textContent = i;

            button.className = `
            w-8 h-8
            rounded-lg
            text-xs
            font-medium
            border
            transition
            ${i === currentPage
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                }
        `;

            button.addEventListener('click', () => {

                currentPage = i;

                renderPagination();

                document
                    .getElementById('attendanceTable')
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

            });

            container.appendChild(button);
        }
    }

    /* Previous */
    document.getElementById('prevPage')
        ?.addEventListener('click', () => {

            if (currentPage > 1) {

                currentPage--;

                renderPagination();
            }
        });

    /* Next */
    document.getElementById('nextPage')
        ?.addEventListener('click', () => {

            const totalPages = Math.ceil(
                getFilteredRows().length / rowsPerPage
            );

            if (currentPage < totalPages) {

                currentPage++;

                renderPagination();
            }
        });

    /* Search */
    searchInput?.addEventListener('input', () => {

        currentPage = 1;

        renderPagination();
    });

    /* Status */
    statusSelect?.addEventListener('change', () => {

        currentPage = 1;

        renderPagination();
    });

    /* Initial */
    renderPagination();


    function viewAttendance(attendanceId) {

        console.log('View attendance:', attendanceId);

    }
    const attendanceDate = document.getElementById('attendanceDate');
    const attendanceSearch = document.getElementById('attendanceSearch');
    const attendanceStatus = document.getElementById('attendanceStatus');

    attendanceDate?.addEventListener('change', filterAttendance);
    attendanceSearch?.addEventListener('input', filterAttendance);
    attendanceStatus?.addEventListener('change', filterAttendance);

    function filterAttendance() {
        const date = attendanceDate?.value || '';
        const search = attendanceSearch?.value.toLowerCase().trim() || '';
        const status = attendanceStatus?.value || '';

        document.querySelectorAll('.attendance-row').forEach(row => {

            const rowDate = row.dataset.date || '';
            const name = row.dataset.name || '';
            const id = row.dataset.id || '';
            const rowStatus = row.dataset.status || '';

            const matchesDate = !date || rowDate === date;

            const matchesSearch =
                !search ||
                name.includes(search) ||
                id.includes(search);

            const matchesStatus =
                !status ||
                rowStatus === status;

            row.style.display =
                matchesDate && matchesSearch && matchesStatus
                    ? ''
                    : 'none';
        });
    }

    function viewAttendance(attendanceId) {
        console.log('View attendance:', attendanceId);
    }

    filterAttendance();
    