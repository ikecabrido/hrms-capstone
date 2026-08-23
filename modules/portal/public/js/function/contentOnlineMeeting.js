    document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('updateMeetingStatusModal');

    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {

        const button = event.relatedTarget;

        if (!button) return;

        const id = button.dataset.meetingId || '';
        const title = button.dataset.meetingTitle || '--';
        const status = button.dataset.meetingStatus || 'scheduled';

        document.getElementById('updateMeetingId').value = id;

        document.getElementById('updateMeetingTitle').textContent = title;

        const statusSelect = document.getElementById('updateMeetingStatus');

        statusSelect.value = status;

    });

});
    document.addEventListener('DOMContentLoaded', function () {

        const search = document.getElementById('meetingSearch');
        const prev = document.getElementById('meetingPrev');
        const next = document.getElementById('meetingNext');

        const pageStart = document.getElementById('meetingPageStart');
        const pageEnd = document.getElementById('meetingPageEnd');
        const total = document.getElementById('meetingTotal');
        const pageInfo = document.getElementById('meetingPageInfo');

        const rows = [
            ...document.querySelectorAll('.meeting-row')
        ];

        const pageSize = 10;

        let currentPage = 1;
        let filteredRows = [...rows];

        function renderMeetings() {

            const totalRows = filteredRows.length;

            const totalPages = Math.max(
                1,
                Math.ceil(totalRows / pageSize)
            );

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = (currentPage - 1) * pageSize;
            const end = Math.min(
                start + pageSize,
                totalRows
            );

            rows.forEach(row => {
                row.style.display = 'none';
            });

            filteredRows
                .slice(start, end)
                .forEach(row => {
                    row.style.display = '';
                });

            pageStart.textContent =
                totalRows ? start + 1 : 0;

            pageEnd.textContent = end;

            total.textContent = totalRows;

            pageInfo.textContent =
                `${currentPage} / ${totalPages}`;

            prev.disabled =
                currentPage === 1;

            next.disabled =
                currentPage === totalPages ||
                totalRows === 0;
        }

        function filterMeetings() {

            const query = search
                ? search.value.trim().toLowerCase()
                : '';

            filteredRows = rows.filter(row => {

                const text =
                    row.dataset.search ||
                    row.textContent.toLowerCase();

                return !query ||
                    text.includes(query);
            });

            currentPage = 1;

            renderMeetings();
        }

        prev?.addEventListener('click', function () {

            if (currentPage > 1) {

                currentPage--;

                renderMeetings();
            }
        });

        next?.addEventListener('click', function () {

            const totalPages =
                Math.ceil(
                    filteredRows.length / pageSize
                );

            if (currentPage < totalPages) {

                currentPage++;

                renderMeetings();
            }
        });

        search?.addEventListener(
            'input',
            filterMeetings
        );

        renderMeetings();

    });


    function copyMeetingLink(link) {

        navigator.clipboard.writeText(link)
            .then(() => {

                // Optional simple feedback
                alert('Meeting link copied.');

            })
            .catch(() => {

                alert('Unable to copy meeting link.');

            });
    }
    document.addEventListener('DOMContentLoaded', function () {

        const modal = document.getElementById('updateMeetingStatusModal');

        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) return;

            document.getElementById('updateMeetingId').value =
                button.dataset.id || '';

            document.getElementById('updateMeetingTitle').textContent =
                button.dataset.title || '--';

            document.getElementById('updateMeetingStatus').value =
                button.dataset.status || 'scheduled';
        });

    });
    
    document.addEventListener('DOMContentLoaded', function () {

        const rows = [...document.querySelectorAll('.meeting-row')];
        const search = document.getElementById('meetingSearch');
        const date = document.getElementById('meetingDate');
        const filters = document.querySelectorAll('.meeting-filter');
        const count = document.getElementById('meetingCount');

        let currentFilter = 'all';


        function filterMeetings() {

            const query = search?.value.toLowerCase().trim() || '';
            const selectedDate = date?.value || '';

            let visible = 0;

            rows.forEach(row => {

                const title = row.dataset.title || '';
                const host = row.dataset.host || '';
                const status = row.dataset.status || '';
                const rowDate = row.dataset.date || '';

                const matchesSearch =
                    !query ||
                    title.includes(query) ||
                    host.includes(query);

                const matchesDate =
                    !selectedDate ||
                    rowDate === selectedDate;

                let matchesFilter = true;

                switch (currentFilter) {

                    case 'live':
                        matchesFilter = status === 'live';
                        break;

                    case 'upcoming':
                        matchesFilter = status === 'upcoming';
                        break;

                    case 'past':
                        matchesFilter = status === 'past';
                        break;

                    case 'my':
                        /*
                         * Replace this with your actual logged-in
                         * employee/host ID comparison.
                         */
                        matchesFilter = true;
                        break;

                    default:
                        matchesFilter = true;
                }

                const show =
                    matchesSearch &&
                    matchesDate &&
                    matchesFilter;

                row.style.display = show ? '' : 'none';

                if (show) {
                    visible++;
                }

            });

            if (count) {
                count.textContent = visible;
            }
        }


        filters.forEach(button => {

            button.addEventListener('click', function () {

                currentFilter = this.dataset.filter;

                filters.forEach(btn => {

                    btn.classList.remove(
                        'bg-slate-800',
                        'text-white'
                    );

                    btn.classList.add(
                        'text-slate-600'
                    );

                });

                this.classList.remove('text-slate-600');

                this.classList.add(
                    'bg-slate-800',
                    'text-white'
                );

                filterMeetings();

            });

        });


        search?.addEventListener(
            'input',
            filterMeetings
        );

        date?.addEventListener(
            'change',
            filterMeetings
        );


        filterMeetings();

    });


    function copyMeetingLink(link) {

        if (!link) {
            return;
        }

        navigator.clipboard.writeText(link)
            .then(() => {

                /*
                 * Replace with your Bootstrap toast
                 * if you already have one.
                 */
                alert('Meeting link copied.');

            })
            .catch(() => {

                alert('Unable to copy meeting link.');

            });

    }
    
    document.addEventListener('DOMContentLoaded', function () {

        const modal = document.getElementById('updateMeetingStatusModal');

        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) return;

            const id = button.dataset.meetingId;
            const title = button.dataset.meetingTitle;
            const status = button.dataset.meetingStatus;

            document.getElementById('updateMeetingId').value = id;

            document.getElementById('updateMeetingTitle').textContent =
                title || '--';

            document.getElementById('updateMeetingStatus').value =
                status || 'scheduled';
        });

    });