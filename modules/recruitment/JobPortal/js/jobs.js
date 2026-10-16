function renderJobs() {
    var grid = document.getElementById('jobsGrid');
    var pill = document.getElementById('jobCountPill');

    pill.textContent = jobs.length +
        (jobs.length === 1 ? " open position" : " open positions");

    if (jobs.length === 0) {
        grid.innerHTML =
            '<div class="empty-state" style="grid-column:1/-1;">' +
                '<h3>No postings yet</h3>' +
                '<p>Open positions will appear here right away.</p>' +
            '</div>';

        return;
    }

    var sorted = jobs.slice().sort(function(a, b) {
        return b.posted - a.posted;
    });

    var html = sorted.map(function(j) {

        var maxApplicants = Number(j.max_applicants || 0);
        var totalApplicants = Number(j.total_applicants || 0);

        // 0 = unlimited
        var isFull =
            maxApplicants > 0 &&
            totalApplicants >= maxApplicants;

        var applicantText = maxApplicants > 0
            ? totalApplicants + ' / ' + maxApplicants + ' applicants'
            : totalApplicants + ' applicants';

        var applyButton;

        if (isFull) {

            applyButton =
                '<div class="application-closed">' +

                    '<button type="button" ' +
                        'class="btn-apply" ' +
                        'disabled>' +
                        'Applications Full' +
                    '</button>' +

                    '<div class="application-closed-text">' +
                        'Maximum number of applicants reached.' +
                    '</div>' +

                '</div>';

        } else {

            applyButton =
                '<button type="button" ' +
                    'class="btn-apply" ' +
                    'onclick="openApply(\'' +
                        escapeHtml(j.id) +
                    '\')">' +
                    'Apply now' +
                '</button>';
        }

        return '' +
            '<div class="job-card">' +

                '<div class="job-top">' +
                    '<div>' +

                        '<div class="job-dept">' +
                            escapeHtml(j.department) +
                        '</div>' +

                        '<h3>' +
                            escapeHtml(j.title) +
                        '</h3>' +

                    '</div>' +
                '</div>' +

                '<div class="job-meta">' +

                    (j.position
                        ? '<span class="tag">' +
                            escapeHtml(j.position) +
                          '</span>'
                        : '') +

                    '<span class="tag">' +
                        escapeHtml(j.type) +
                    '</span>' +

                    '<span class="tag">' +
                        escapeHtml(j.location) +
                    '</span>' +

                    '<span class="tag">' +
                        escapeHtml(applicantText) +
                    '</span>' +

                '</div>' +

                (j.salary
                    ? '<div class="job-salary">' +
                        escapeHtml(j.salary) +
                      '</div>'
                    : '') +

                '<div class="job-desc">' +
                    escapeHtml(j.desc) +
                '</div>' +

                '<div class="job-actions">' +
                    applyButton +
                '</div>' +

            '</div>';

    }).join('');

    grid.innerHTML = html;
}