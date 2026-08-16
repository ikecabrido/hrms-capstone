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