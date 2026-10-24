document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('applicationSearch');
    const table = document.getElementById('applicationsTable');

    if (searchInput && table) {
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
    }

    const parsedResumeSearch = document.getElementById('parsedResumeSearch');
    const parsedResumesTable = document.getElementById('parsedResumesTable');

    if (parsedResumeSearch && parsedResumesTable) {
        const rows = Array.from(parsedResumesTable.querySelectorAll('tbody tr'))
            .filter((row) => row.querySelector('.app-id-badge, .candidate-name'));

        parsedResumeSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            rows.forEach((row) => {
                const id = (row.querySelector('.app-id-badge')?.textContent || '').trim().toLowerCase();
                const name = (row.querySelector('.candidate-name')?.textContent || '').trim().toLowerCase();
                row.style.display = !query || id.includes(query) || name.includes(query) ? '' : 'none';
            });
        });
    }

    const interviewResultsSearch = document.getElementById('interviewResultsSearch');
    const interviewResultsTable = document.getElementById('interviewResultsTable');

    if (interviewResultsSearch && interviewResultsTable) {
        const rows = Array.from(interviewResultsTable.querySelectorAll('tbody tr.interview-result-row'));

        interviewResultsSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            rows.forEach((row) => {
                const applicantName = (row.cells[0]?.textContent || '').trim().toLowerCase();
                row.style.display = !query || applicantName.includes(query) ? '' : 'none';
            });
        });
    }

    const onboardingSearch = document.getElementById('onboardingSearch');
    const onboardingTable = document.getElementById('onboardingTable');

    if (onboardingSearch && onboardingTable) {
        const rows = Array.from(onboardingTable.querySelectorAll('tbody tr.onboarding-row'));

        onboardingSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            rows.forEach((row) => {
                const id = (row.cells[0]?.textContent || '').trim().toLowerCase();
                const employeeName = (row.cells[1]?.textContent || '').trim().toLowerCase();
                const positionAndDepartment = (row.cells[2]?.textContent || '').trim().toLowerCase();
                const matches = !query || id.includes(query) || employeeName.includes(query) || positionAndDepartment.includes(query);

                row.style.display = matches ? '' : 'none';
            });
        });
    }

    const hiredApplicantsSearch = document.getElementById('hiredApplicantsSearch');
    const hiredApplicantsTable = document.getElementById('hiredApplicantsTable');

    if (hiredApplicantsSearch && hiredApplicantsTable) {
        const rows = Array.from(hiredApplicantsTable.querySelectorAll('tbody tr.hired-applicant-row'));

        hiredApplicantsSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            rows.forEach((row) => {
                const applicantName = (row.cells[0]?.textContent || '').trim().toLowerCase();
                row.style.display = !query || applicantName.includes(query) ? '' : 'none';
            });
        });
    }
});
