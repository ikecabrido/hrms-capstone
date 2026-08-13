function filterPayrollRequests() {

    const input = document.getElementById('payrollRequestSearch');

    if (!input) return;

    const search = input.value.toLowerCase().trim();

    const rows = document.querySelectorAll('.payroll-request-row');

    rows.forEach(function(row) {

        const text = row.textContent.toLowerCase();

        row.style.display = text.includes(search)
            ? ''
            : 'none';

    });
}
const payrollRequests = <?= json_encode(
    $payrollRequests ?? [],
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

console.log('Payroll Requests:', payrollRequests);


document.addEventListener('DOMContentLoaded', function () {

    const buttons = document.querySelectorAll('.view-payroll-request-btn');

    buttons.forEach(function (button) {

        button.addEventListener('click', function () {

            const requestId = String(this.dataset.id);

            console.log('Clicked Request ID:', requestId);

            const request = payrollRequests.find(function (item) {
                return String(item.id) === requestId;
            });

            console.log('Selected Request:', request);

            if (!request) {
                console.error('Payroll request not found:', requestId);
                return;
            }


            /*
             * REQUEST TYPE
             */
            document.getElementById('viewRequestType').textContent =
                request.request_type || '-';


            /*
             * PURPOSE
             */
            document.getElementById('viewRequestPurpose').textContent =
                request.purpose || '-';


            /*
             * REMARKS
             */
            document.getElementById('viewRequestRemarks').textContent =
                request.remarks || '-';


            /*
             * PAYROLL PERIOD
             */
            const periodFrom = request.payroll_period_start
                ? formatDate(request.payroll_period_start)
                : '-';

            const periodTo = request.payroll_period_end
                ? formatDate(request.payroll_period_end)
                : '-';

            document.getElementById('viewPayrollPeriod').textContent =
                periodFrom + ' – ' + periodTo;


            /*
             * REQUESTED DATE
             */
            document.getElementById('viewRequestedDate').textContent =
                request.requested_at
                    ? formatDateTime(request.requested_at)
                    : '-';


            /*
             * STATUS
             */
            setStatus(request.status || 'pending');


            /*
             * PROCESSING INFORMATION
             */
            const processingInformation =
                document.getElementById('processingInformation');

            const processedDate =
                document.getElementById('viewProcessedDate');

            if (request.processed_at || request.status === 'rejected') {

                processingInformation.style.display = 'block';

                processedDate.textContent =
                    request.processed_at
                        ? formatDateTime(request.processed_at)
                        : '-';

            } else {

                processingInformation.style.display = 'none';

                processedDate.textContent = '-';
            }


            /*
             * REJECTION REASON
             */
            const rejectionContainer =
                document.getElementById('rejectionReasonContainer');

            const rejectionReason =
                document.getElementById('viewRejectionReason');

            if (
                String(request.status).toLowerCase() === 'rejected' &&
                request.rejection_reason
            ) {

                rejectionContainer.style.display = 'block';

                rejectionReason.textContent =
                    request.rejection_reason;

            } else {

                rejectionContainer.style.display = 'none';

                rejectionReason.textContent = '-';
            }


            /*
             * DOCUMENT
             */
            const documentContainer =
                document.getElementById('documentContainer');

            const documentLink =
                document.getElementById('viewRequestDocument');

            if (request.document_path) {

                documentContainer.style.display = 'block';

                documentLink.href =
                    request.document_path;

            } else {

                documentContainer.style.display = 'none';

                documentLink.href = '#';
            }

        });

    });


    /*
     * FORMAT DATE
     */
    function formatDate(dateString) {

        if (!dateString) {
            return '-';
        }

        const date = new Date(dateString + 'T00:00:00');

        if (isNaN(date.getTime())) {
            return dateString;
        }

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }


    /*
     * FORMAT DATETIME
     */
    function formatDateTime(dateString) {

        if (!dateString) {
            return '-';
        }

        const date = new Date(
            dateString.replace(' ', 'T')
        );

        if (isNaN(date.getTime())) {
            return dateString;
        }

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }


    /*
     * STATUS
     */
    function setStatus(status) {

        const badge =
            document.getElementById('viewRequestStatus');

        const normalized =
            String(status)
                .toLowerCase()
                .replace(/_/g, ' ');

        let background = '#fef3c7';
        let color = '#92400e';
        let icon = 'fa-clock';


        switch (normalized) {

            case 'approved':
                background = '#dcfce7';
                color = '#166534';
                icon = 'fa-check-circle';
                break;

            case 'rejected':
                background = '#fee2e2';
                color = '#b91c1c';
                icon = 'fa-times-circle';
                break;

            case 'under review':
                background = '#fef3c7';
                color = '#92400e';
                icon = 'fa-clock';
                break;

            case 'processed':
                background = '#dbeafe';
                color = '#1d4ed8';
                icon = 'fa-circle-check';
                break;

            case 'cancelled':
                background = '#f3f4f6';
                color = '#4b5563';
                icon = 'fa-ban';
                break;

        }


        badge.style.background = background;
        badge.style.color = color;

        badge.innerHTML = `
            <i class="fas ${icon}" style="font-size:9px;"></i>
            ${capitalize(normalized)}
        `;
    }


    function capitalize(text) {

        return text.replace(/\b\w/g, function (letter) {
            return letter.toUpperCase();
        });

    }

});