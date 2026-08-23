    function openRejectModal(resignationId, employeeName) {
        document.getElementById('rejectResignationId').value =
            resignationId;

        document.getElementById('rejectEmployeeName').textContent =
            employeeName || 'Unknown Employee';

        document.getElementById('rejectionReason').value = '';

        document.getElementById('rejectModal').style.display = 'flex';

        setTimeout(function () {
            document.getElementById('rejectionReason').focus();
        }, 100);
    }


    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }


    function submitRejection(form) {
        const reason =
            document.getElementById('rejectionReason').value.trim();

        if (!reason) {

            document.getElementById('rejectionReason').focus();

            return false;
        }

        return confirm(
            'Are you sure you want to reject this resignation request?\n\n' +
            'This action will record the rejection reason.'
        );
    }

    let selectedResignationId = null;

    function openApproveModal(resignationId, employeeName, lastWorkingDay) {

        selectedResignationId = resignationId;

        document.getElementById('approveResignationId').value = resignationId;

        const idDisplay = document.getElementById('approveResignationIdDisplay');

        if (idDisplay) {
            idDisplay.textContent = '#' + resignationId;
        }

        document.getElementById('approveEmployeeName').textContent =
            employeeName || 'Unknown Employee';

        document.getElementById('approveLastWorkingDay').textContent =
            'Last Working Day: ' + (lastWorkingDay || '—');

        document.getElementById('approvalRemarks').value = '';

        document.getElementById('approveModal').style.display = 'flex';
    }


    function closeApproveModal() {

        document.getElementById('approveModal').style.display = 'none';

    }


    function submitApproval(form) {

        if (!selectedResignationId) {
            alert('Invalid resignation request.');
            return false;
        }

        return confirm(
            'Are you sure you want to approve this resignation request?\n\n' +
            'The employee account will be deactivated and the employee will ' +
            'be moved into the offboarding process.'
        );
    }

    function viewResignation(
        id,
        employeeName,
        resignationType,
        reason,
        dateSubmitted,
        lastWorkingDay,
        status
    ) {
        document.getElementById('viewResignationId').textContent = '#' + id;
        document.getElementById('viewEmployeeName').textContent = employeeName;
        document.getElementById('viewResignationType').textContent = resignationType;
        document.getElementById('viewResignationReason').textContent = reason;
        document.getElementById('viewDateSubmitted').textContent = dateSubmitted;
        document.getElementById('viewLastWorkingDay').textContent = lastWorkingDay;
        document.getElementById('viewStatusText').textContent = status;

        const avatar = document.getElementById('viewEmployeeAvatar');
        avatar.textContent = employeeName.trim().charAt(0).toUpperCase();

        const statusBadge = document.getElementById('viewStatus');
        const statusText = status.toLowerCase();

        statusBadge.textContent = status;

        if (statusText === 'approved') {
            statusBadge.style.background = '#dcfce7';
            statusBadge.style.color = '#15803d';
        } else if (statusText === 'rejected') {
            statusBadge.style.background = '#fee2e2';
            statusBadge.style.color = '#dc2626';
        } else if (statusText === 'completed') {
            statusBadge.style.background = '#e0e7ff';
            statusBadge.style.color = '#4338ca';
        } else {
            statusBadge.style.background = '#fef3c7';
            statusBadge.style.color = '#b45309';
        }

        const statusTextElement = document.getElementById('viewStatusText');
        statusTextElement.style.color =
            statusText === 'approved' ? '#15803d' :
                statusText === 'rejected' ? '#dc2626' :
                    statusText === 'completed' ? '#4338ca' :
                        '#b45309';

        const modal = new bootstrap.Modal(
            document.getElementById('viewResignationModal')
        );

        modal.show();
    }

let currentPage = 1;
let rowsPerPage = 10;

function getResignationRows() {

    return Array.from(
        document.querySelectorAll(
            '#resignationTableBody .resignation-row'
        )
    );

}


function renderPagination() {

    const rows = getResignationRows();
    const totalRows = rows.length;

    const totalPages = Math.max(
        1,
        Math.ceil(totalRows / rowsPerPage)
    );

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    rows.forEach((row, index) => {

        row.style.display =
            index >= start && index < end
                ? ''
                : 'none';

    });


    /* INFO */

    const info = document.getElementById(
        'paginationInfo'
    );

    if (totalRows === 0) {

        info.textContent = 'Showing 0–0 of 0';

    } else {

        info.textContent =
            `Showing ${start + 1}–${Math.min(end, totalRows)} of ${totalRows}`;

    }


    /* BUTTONS */

    const container = document.getElementById(
        'paginationButtons'
    );

    container.innerHTML = '';


    /* PREVIOUS */

    const previous = document.createElement('button');

    previous.type = 'button';

    previous.innerHTML =
        '<i class="fas fa-chevron-left"></i>';

    previous.style.cssText = `
        width:30px;
        height:30px;
        border:1px solid #e2e8f0;
        border-radius:7px;
        background:#fff;
        color:#64748b;
        font-size:9px;
        cursor:pointer;
    `;

    previous.disabled = currentPage === 1;

    if (previous.disabled) {

        previous.style.opacity = '.4';
        previous.style.cursor = 'not-allowed';

    }

    previous.onclick = function () {

        if (currentPage > 1) {

            currentPage--;

            renderPagination();

        }

    };

    container.appendChild(previous);


    /* PAGE NUMBERS */

    for (let page = 1; page <= totalPages; page++) {

        const button = document.createElement('button');

        button.type = 'button';

        button.textContent = page;

        button.style.cssText = `
            min-width:30px;
            height:30px;
            padding:0 8px;
            border-radius:7px;
            font-size:10px;
            font-weight:700;
            cursor:pointer;
        `;

        if (page === currentPage) {

            button.style.background = '#2563eb';
            button.style.color = '#fff';
            button.style.border = '1px solid #2563eb';

        } else {

            button.style.background = '#fff';
            button.style.color = '#64748b';
            button.style.border = '1px solid #e2e8f0';

            button.onmouseover = function () {

                this.style.background = '#f8fafc';
                this.style.color = '#2563eb';

            };

            button.onmouseout = function () {

                this.style.background = '#fff';
                this.style.color = '#64748b';

            };

        }

        button.onclick = function () {

            currentPage = page;

            renderPagination();

        };

        container.appendChild(button);

    }


    /* NEXT */

    const next = document.createElement('button');

    next.type = 'button';

    next.innerHTML =
        '<i class="fas fa-chevron-right"></i>';

    next.style.cssText = `
        width:30px;
        height:30px;
        border:1px solid #e2e8f0;
        border-radius:7px;
        background:#fff;
        color:#64748b;
        font-size:9px;
        cursor:pointer;
    `;

    next.disabled = currentPage === totalPages;

    if (next.disabled) {

        next.style.opacity = '.4';
        next.style.cursor = 'not-allowed';

    }

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
