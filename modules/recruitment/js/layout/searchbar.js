document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('applicationSearch');
    const table = document.getElementById('applicationsTable');

    if (!searchInput || !table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr.applicant-row'));

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        rows.forEach((row) => {
            const id = (row.querySelector('.cell-number')?.textContent || '').trim().toLowerCase();
            const name = (row.querySelector('.applicant-name')?.textContent || '').trim().toLowerCase();
            const job = (row.querySelector('.job-badge')?.textContent || '').trim().toLowerCase();

            const matches = !query || id.includes(query) || name.includes(query) || job.includes(query);
            row.style.display = matches ? '' : 'none';
        });
    });
});
