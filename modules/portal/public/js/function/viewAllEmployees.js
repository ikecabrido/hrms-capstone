    document.addEventListener('DOMContentLoaded', function () {

        const modal = document.getElementById('employeeViewModal');

        if (modal) {

            modal.addEventListener('show.bs.modal', function (event) {

                const button = event.relatedTarget;

                if (!button) return;

                const data = button.dataset;

                /* =========================
                   EMPLOYEE NAME
                ========================= */

                const fullName = [
                    data.firstName,
                    data.middleName,
                    data.lastName,
                    data.suffix
                ].filter(Boolean).join(' ');

                const initials =
                    (data.firstName?.charAt(0) || '') +
                    (data.lastName?.charAt(0) || '');


/* =========================
   PROFILE IMAGE
========================= */

const image = document.getElementById('modalProfileImage');
const initialsElement = document.getElementById('modalInitials');

const profileImage = data.profileImage?.trim() || '';
const employeeName = fullName || '';

if (image && initialsElement) {

    if (profileImage) {

        image.src =
            '/hrms-capstone/modules/portal/public/assets/uploads/profile/' +
            encodeURIComponent(profileImage);

        image.style.display = 'block';
        initialsElement.style.display = 'none';

    } else {

        image.src = '';
        image.style.display = 'none';

        initialsElement.textContent =
            employeeName.charAt(0).toUpperCase() || '--';

        initialsElement.style.display = 'flex';
    }
}


                /* =========================
                   EMPLOYEE INFORMATION
                ========================= */

                document.getElementById('modalFullName').textContent =
                    fullName || '--';

                document.getElementById('modalEmployeeNumber').textContent =
                    data.employeeNum || '--';

                document.getElementById('modalFirstName').textContent =
                    data.firstName || '--';

                document.getElementById('modalMiddleName').textContent =
                    data.middleName || '--';

                document.getElementById('modalLastName').textContent =
                    data.lastName || '--';

                document.getElementById('modalSuffix').textContent =
                    data.suffix || '--';

                document.getElementById('modalDepartment').textContent =
                    data.department || '--';

                document.getElementById('modalPosition').textContent =
                    data.position || '--';

                document.getElementById('modalEmploymentType').textContent =
                    data.employmentType || '--';

                document.getElementById('modalDateHired').textContent =
                    data.dateHired || '--';

                document.getElementById('modalGender').textContent =
                    data.gender || '--';

                document.getElementById('modalBirthDate').textContent =
                    data.birthDate || '--';

                document.getElementById('modalPhone').textContent =
                    data.phone || '--';

                document.getElementById('modalAddress').textContent =
                    data.address || '--';


                /* =========================
                   EMPLOYMENT STATUS
                ========================= */

                const status = document.getElementById('modalStatus');

                if (status) {

                    status.textContent =
                        data.employmentStatus || '--';

                    status.className =
                        'badge rounded-pill ' +
                        (
                            data.employmentStatus?.toLowerCase() === 'active'
                                ? 'text-bg-success'
                                : 'text-bg-warning'
                        );
                }

            });

        }


        /* =========================
           TABLE PAGINATION + SEARCH
        ========================= */

        const table = document.getElementById('employees-table');
        const search = document.getElementById('employeeSearch');
        const prev = document.getElementById('prevPage');
        const next = document.getElementById('nextPage');

        const pageStart = document.getElementById('pageStart');
        const pageEnd = document.getElementById('pageEnd');
        const totalEntries = document.getElementById('totalEntries');
        const pageInfo = document.getElementById('pageInfo');

        if (!table || !prev || !next) return;

        const rows = [
            ...table.querySelectorAll('tbody tr[data-name]')
        ];

        const pageSize = 10;

        let currentPage = 1;
        let filteredRows = [...rows];


        function render() {

            const total = filteredRows.length;

            const totalPages =
                Math.max(1, Math.ceil(total / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start =
                (currentPage - 1) * pageSize;

            const end =
                Math.min(start + pageSize, total);


            rows.forEach(row => {
                row.style.display = 'none';
            });


            filteredRows
                .slice(start, end)
                .forEach(row => {
                    row.style.display = '';
                });


            pageStart.textContent =
                total ? start + 1 : 0;

            pageEnd.textContent =
                end;

            totalEntries.textContent =
                total;

            pageInfo.textContent =
                `${currentPage} / ${totalPages}`;


            prev.disabled =
                currentPage === 1;

            next.disabled =
                currentPage === totalPages ||
                total === 0;


            prev.classList.toggle(
                'cursor-not-allowed',
                prev.disabled
            );

            prev.classList.toggle(
                'text-slate-300',
                prev.disabled
            );

            prev.classList.toggle(
                'text-slate-600',
                !prev.disabled
            );

            prev.classList.toggle(
                'hover:bg-slate-50',
                !prev.disabled
            );


            next.classList.toggle(
                'cursor-not-allowed',
                next.disabled
            );

            next.classList.toggle(
                'opacity-50',
                next.disabled
            );

            next.classList.toggle(
                'hover:bg-indigo-700',
                !next.disabled
            );
        }


        function filterRows() {

            const query = search
                ? search.value.trim().toLowerCase()
                : '';


            filteredRows = rows.filter(row => {

                const name =
                    row.dataset.name || '';

                const department =
                    row.dataset.department || '';

                const text =
                    row.textContent.toLowerCase();


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

            const totalPages =
                Math.ceil(
                    filteredRows.length / pageSize
                );


            if (currentPage < totalPages) {

                currentPage++;

                render();
            }

        });


        if (search) {
            search.addEventListener(
                'input',
                filterRows
            );
        }


        render();

    });