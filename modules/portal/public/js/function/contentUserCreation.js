    document.addEventListener('DOMContentLoaded', () => {

        const table = document.getElementById('pending-provision-table');
        const search = document.getElementById('employeeSearch');
        const prev = document.getElementById('prevPage');
        const next = document.getElementById('nextPage');

        const pageStart = document.getElementById('pageStart');
        const pageEnd = document.getElementById('pageEnd');
        const totalEntries = document.getElementById('totalEntries');
        const pageInfo = document.getElementById('pageInfo');

        if (!table || !prev || !next) return;

        const rows = [...table.querySelectorAll('tbody tr[data-name]')];

        const pageSize = 10;

        let currentPage = 1;
        let filteredRows = [...rows];


        function render() {

            const total = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, total);


            rows.forEach(row => {
                row.style.display = 'none';
            });


            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
            });


            pageStart.textContent = total ? start + 1 : 0;
            pageEnd.textContent = end;
            totalEntries.textContent = total;

            pageInfo.textContent = `${currentPage} / ${totalPages}`;


            prev.disabled = currentPage === 1;

            next.disabled = currentPage === totalPages || total === 0;


            prev.classList.toggle('cursor-not-allowed', prev.disabled);
            prev.classList.toggle('text-slate-300', prev.disabled);
            prev.classList.toggle('text-slate-600', !prev.disabled);
            prev.classList.toggle('hover:bg-slate-50', !prev.disabled);


            next.classList.toggle('cursor-not-allowed', next.disabled);
            next.classList.toggle('opacity-50', next.disabled);
            next.classList.toggle('hover:bg-indigo-700', !next.disabled);

        }


        function filterRows() {

            const query = search
                ? search.value.trim().toLowerCase()
                : '';

            filteredRows = rows.filter(row => {

                const name = row.dataset.name || '';
                const department = row.dataset.department || '';
                const text = row.textContent.toLowerCase();

                return !query ||
                    name.includes(query) ||
                    department.includes(query) ||
                    text.includes(query);

            });

            currentPage = 1;

            render();

        }


        prev.addEventListener('click', () => {

            if (currentPage > 1) {

                currentPage--;

                render();

            }

        });


        next.addEventListener('click', () => {

            const totalPages = Math.ceil(filteredRows.length / pageSize);

            if (currentPage < totalPages) {

                currentPage++;

                render();

            }

        });


        if (search) {
            search.addEventListener('input', filterRows);
        }


        render();

    });
    document.addEventListener('click', function (e) {

        const button = e.target.closest('.password-toggle-btn');

        if (!button) {
            return;
        }

        const inputId = button.getAttribute('data-password-target');
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (!input || !icon) {
            console.error('Password input not found:', inputId);
            return;
        }

        if (input.type === 'password') {

            input.type = 'text';

            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');

            button.setAttribute('aria-label', 'Hide password');
            button.setAttribute('title', 'Hide password');

        } else {

            input.type = 'password';

            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');

            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('title', 'Show password');
        }

    });
    
function setUserActive(userId, employeeName) {

    if (!userId) {
        alert('Invalid user account.');
        return;
    }

    const confirmed = confirm(
        'Set this user account as active?\n\n' +
        'Employee: ' + employeeName
    );

    if (!confirmed) {
        return;
    }

    const form = document.createElement('form');

    form.method = 'POST';
    form.action = 'index.php?url=user-set-active';

    const userInput = document.createElement('input');
    userInput.type = 'hidden';
    userInput.name = 'user_id';
    userInput.value = userId;

    form.appendChild(userInput);

    document.body.appendChild(form);

    form.submit();
}
        let currentPage = 1;
        let rowsPerPage = 10;

        function getInactiveRows() {
            return Array.from(
                document.querySelectorAll('.inactive-user-row')
            );
        }


        function renderPagination() {

            const rows = getInactiveRows();
            const totalRows = rows.length;

            if (!totalRows) {
                return;
            }

            const totalPages = Math.ceil(totalRows / rowsPerPage);

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {

                row.style.display =
                    index >= start && index < end
                        ? 'table-row'
                        : 'none';

            });


            /* PAGINATION INFO */

            const info = document.getElementById('paginationInfo');

            if (info) {

                const showingStart = start + 1;
                const showingEnd = Math.min(end, totalRows);

                info.textContent =
                    `${showingStart}-${showingEnd} of ${totalRows}`;

            }


            /* PAGINATION BUTTONS */

            const container =
                document.getElementById('paginationButtons');

            if (!container) {
                return;
            }

            container.innerHTML = '';


            /* PREVIOUS */

            const previous = document.createElement('button');

            previous.type = 'button';
            previous.innerHTML =
                '<i class="fas fa-chevron-left"></i>';

            previous.disabled = currentPage === 1;

            previous.style.cssText = `
        width:30px;
        height:30px;
        display:flex;
        align-items:center;
        justify-content:center;
        border:1px solid #e2e8f0;
        border-radius:7px;
        background:${currentPage === 1 ? '#f8fafc' : '#fff'};
        color:${currentPage === 1 ? '#cbd5e1' : '#475569'};
        font-size:9px;
        cursor:${currentPage === 1 ? 'not-allowed' : 'pointer'};
    `;

            previous.onclick = function () {

                if (currentPage > 1) {

                    currentPage--;

                    renderPagination();

                }

            };

            container.appendChild(previous);


            /* PAGE NUMBERS */

            for (let i = 1; i <= totalPages; i++) {

                const button = document.createElement('button');

                button.type = 'button';
                button.textContent = i;

                const active = i === currentPage;

                button.style.cssText = `
            min-width:30px;
            height:30px;
            padding:0 8px;
            border:1px solid ${active ? '#2563eb' : '#e2e8f0'};
            border-radius:7px;
            background:${active ? '#2563eb' : '#fff'};
            color:${active ? '#fff' : '#475569'};
            font-size:9px;
            font-weight:700;
            cursor:pointer;
        `;

                button.onclick = function () {

                    currentPage = i;

                    renderPagination();

                };

                container.appendChild(button);

            }


            /* NEXT */

            const next = document.createElement('button');

            next.type = 'button';
            next.innerHTML =
                '<i class="fas fa-chevron-right"></i>';

            next.disabled = currentPage === totalPages;

            next.style.cssText = `
        width:30px;
        height:30px;
        display:flex;
        align-items:center;
        justify-content:center;
        border:1px solid #e2e8f0;
        border-radius:7px;
        background:${currentPage === totalPages ? '#f8fafc' : '#fff'};
        color:${currentPage === totalPages ? '#cbd5e1' : '#475569'};
        font-size:9px;
        cursor:${currentPage === totalPages ? 'not-allowed' : 'pointer'};
    `;

            next.onclick = function () {

                if (currentPage < totalPages) {

                    currentPage++;

                    renderPagination();

                }

            };

            container.appendChild(next);

        }


        function changeRowsPerPage() {

            rowsPerPage = parseInt(
                document.getElementById('rowsPerPage').value
            );

            currentPage = 1;

            renderPagination();

        }


        /* INITIALIZE */

        document.addEventListener(
            'DOMContentLoaded',
            function () {
                renderPagination();
            }
        );
