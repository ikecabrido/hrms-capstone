<div
    class="modal fade"
    id="complaintViewModal"
    tabindex="-1"
    aria-labelledby="complaintViewModalLabel"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"
        style="
            width: calc(100% - 24px);
            max-width: 1050px;
            margin: 12px auto;
        "
    >


    <div
        class="modal-content"
        style="
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 20px 60px rgba(15,23,42,.20);
        "
    >

        <!-- HEADER -->
        <div
            class="modal-header"
            style="
                padding: 18px 22px;
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
            "
        >

            <div
                style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    min-width: 0;
                "
            >

                <div
                    style="
                        width: 42px;
                        height: 42px;
                        min-width: 42px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 10px;
                        background: #eff6ff;
                        color: #2563eb;
                        font-size: 17px;
                    "
                >
                    <i class="fas fa-file-alt"></i>
                </div>

                <div style="min-width:0;">

                    <div
                        style="
                            margin-bottom: 2px;
                            color: #94a3b8;
                            font-size: 9px;
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: .07em;
                        "
                    >
                        Complaint Record
                    </div>

                    <h5
                        class="modal-title"
                        id="complaintViewModalLabel"
                        style="
                            margin: 0;
                            color: #1e293b;
                            font-size: 17px;
                            font-weight: 700;
                        "
                    >
                        Complaint Details
                    </h5>

                    <div
                        style="
                            margin-top: 2px;
                            color: #64748b;
                            font-size: 11px;
                        "
                    >
                        Complaint #

                        <span
                            id="viewComplaintId"
                            style="
                                color: #2563eb;
                                font-weight: 700;
                            "
                        >
                            -
                        </span>
                    </div>

                </div>

            </div>


            <!-- FIXED CLOSE BUTTON -->

            <button
                type="button"
                data-bs-dismiss="modal"
                aria-label="Close"
                style="
                    width: 34px;
                    height: 34px;
                    min-width: 34px;
                    padding: 0;
                    margin-left: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    background: #f8fafc;
                    color: #64748b;
                    font-size: 16px;
                    cursor: pointer;
                "
            >
                <i class="fas fa-times"></i>
            </button>

        </div>


        <!-- BODY -->

        <div
            class="modal-body"
            style="
                padding: 20px;
                background: #f8fafc;
            "
        >

            <!-- SUMMARY -->

            <div
                style="
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 15px;
                    padding: 18px;
                    margin-bottom: 15px;
                    background: #ffffff;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                "
            >

                <div style="min-width:0; flex:1;">

                    <div
                        style="
                            margin-bottom: 5px;
                            color: #94a3b8;
                            font-size: 9px;
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: .06em;
                        "
                    >
                        Complaint Title
                    </div>

                    <h4
                        id="viewTitle"
                        style="
                            margin: 0;
                            color: #1e293b;
                            font-size: 18px;
                            font-weight: 700;
                            line-height: 1.45;
                            word-break: break-word;
                        "
                    >
                        -
                    </h4>

                </div>


                <div
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                        gap: 7px;
                        flex-wrap: wrap;
                        flex-shrink: 0;
                    "
                >

                    <div id="viewSeverity">
                        -
                    </div>

                    <div id="viewStatus">
                        -
                    </div>

                </div>

            </div>


            <!-- EMPLOYEE INFORMATION -->

            <div
                style="
                    margin-bottom: 15px;
                    padding: 18px;
                    background: #ffffff;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                "
            >

                <div
                    style="
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        padding-bottom: 12px;
                        margin-bottom: 16px;
                        border-bottom: 1px solid #f1f5f9;
                        color: #334155;
                        font-size: 12px;
                        font-weight: 700;
                    "
                >
                    <i
                        class="fas fa-user"
                        style="color:#2563eb;font-size:11px;"
                    ></i>

                    Employee Information
                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-6">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                                letter-spacing:.04em;
                            "
                        >
                            Employee
                        </label>

                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:8px;
                                color:#1e293b;
                                font-size:13px;
                                font-weight:600;
                                word-break:break-word;
                            "
                        >

                            <span
                                style="
                                    width:30px;
                                    height:30px;
                                    min-width:30px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    border-radius:50%;
                                    background:#eff6ff;
                                    color:#2563eb;
                                    font-size:10px;
                                "
                            >
                                <i class="fas fa-user"></i>
                            </span>

                            <span id="viewEmployee">-</span>

                        </div>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Department
                        </label>

                        <div
                            id="viewDepartment"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                                word-break:break-word;
                            "
                        >
                            -
                        </div>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Complaint Type
                        </label>

                        <div
                            id="viewType"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                                word-break:break-word;
                            "
                        >
                            -
                        </div>

                    </div>


                    <div class="col-12 col-md-6">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Assigned To
                        </label>

                        <div
                            id="viewAssigned"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                                word-break:break-word;
                            "
                        >
                            -
                        </div>

                    </div>

                </div>

            </div>


            <!-- INCIDENT INFORMATION -->

            <div
                style="
                    margin-bottom:15px;
                    padding:18px;
                    background:#ffffff;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        padding-bottom:12px;
                        margin-bottom:16px;
                        border-bottom:1px solid #f1f5f9;
                        color:#334155;
                        font-size:12px;
                        font-weight:700;
                    "
                >

                    <i
                        class="fas fa-calendar-alt"
                        style="color:#2563eb;font-size:11px;"
                    ></i>

                    Incident Information

                </div>


                <div class="row g-3">

                    <div class="col-12 col-sm-6 col-md-4">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Incident Date
                        </label>

                        <div
                            id="viewIncidentDate"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                            "
                        >
                            -
                        </div>

                    </div>


                    <div class="col-12 col-sm-6 col-md-4">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Incident Time
                        </label>

                        <div
                            id="viewIncidentTime"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                            "
                        >
                            -
                        </div>

                    </div>


                    <div class="col-12 col-md-4">

                        <label
                            style="
                                display:block;
                                margin-bottom:5px;
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                            "
                        >
                            Location
                        </label>

                        <div
                            id="viewLocation"
                            style="
                                color:#334155;
                                font-size:13px;
                                font-weight:600;
                                word-break:break-word;
                            "
                        >
                            -
                        </div>

                    </div>

                </div>

            </div>


            <!-- DESCRIPTION -->

            <div
                style="
                    margin-bottom:15px;
                    padding:18px;
                    background:#ffffff;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        padding-bottom:12px;
                        margin-bottom:15px;
                        border-bottom:1px solid #f1f5f9;
                        color:#334155;
                        font-size:12px;
                        font-weight:700;
                    "
                >

                    <i
                        class="fas fa-align-left"
                        style="color:#2563eb;font-size:11px;"
                    ></i>

                    Complaint Description

                </div>


                <div
                    id="viewDescription"
                    style="
                        padding:15px;
                        background:#f8fafc;
                        border:1px solid #e5e7eb;
                        border-radius:8px;
                        color:#475569;
                        font-size:13px;
                        line-height:1.7;
                        white-space:pre-line;
                        overflow-wrap:anywhere;
                    "
                >
                    -
                </div>

            </div>


            <!-- EMPLOYEE RESPONSE -->

            <div
                style="
                    margin-bottom:15px;
                    padding:18px;
                    background:#ffffff;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        padding-bottom:12px;
                        margin-bottom:15px;
                        border-bottom:1px solid #f1f5f9;
                        color:#334155;
                        font-size:12px;
                        font-weight:700;
                    "
                >

                    <i
                        class="fas fa-comment-alt"
                        style="color:#2563eb;font-size:11px;"
                    ></i>

                    Employee Response

                </div>


                <div
                    id="viewEmployeeResponse"
                    style="
                        padding:15px;
                        background:#f8fafc;
                        border-left:3px solid #3b82f6;
                        border-radius:6px;
                        color:#475569;
                        font-size:13px;
                        line-height:1.7;
                        white-space:pre-line;
                        overflow-wrap:anywhere;
                    "
                >
                    No response provided.
                </div>


                <div
                    id="viewEmployeeResponseDate"
                    style="
                        margin-top:8px;
                        color:#94a3b8;
                        font-size:10px;
                    "
                ></div>

            </div>


            <!-- RECORD INFORMATION -->

            <div
                style="
                    padding:13px 16px;
                    background:#f1f5f9;
                    border-radius:8px;
                "
            >

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:10px;
                        flex-wrap:wrap;
                    "
                >

                    <div
                        style="
                            color:#64748b;
                            font-size:10px;
                        "
                    >
                        <i class="fas fa-clock me-1"></i>

                        Record created:

                        <strong
                            id="viewCreatedAt"
                            style="
                                color:#475569;
                                font-weight:600;
                            "
                        >
                            -
                        </strong>
                    </div>


                    <div
                        style="
                            color:#94a3b8;
                            font-size:9px;
                        "
                    >
                        Internal HR Record
                    </div>

                </div>

            </div>

        </div>


        <!-- FOOTER -->

        <div
            class="modal-footer"
            style="
                padding:14px 20px;
                background:#ffffff;
                border-top:1px solid #e5e7eb;
            "
        >

            <button
                type="button"
                data-bs-dismiss="modal"
                style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    gap:7px;
                    min-width:90px;
                    padding:9px 16px;
                    border:1px solid #cbd5e1;
                    border-radius:7px;
                    background:#ffffff;
                    color:#475569;
                    font-size:12px;
                    font-weight:600;
                    cursor:pointer;
                "
            >
                <i class="fas fa-times"></i>
                Close
            </button>

        </div>

    </div>

</div>


</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        /*
         * Convert PHP complaint data into JavaScript.
         * This uses the $allComplaints array already loaded
         * by adminIndex().
         */
        const complaints = <?= json_encode(
            $allComplaints ?? [],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>;


        /*
         * Bootstrap 5 modal
         */
        const complaintModal =
            document.getElementById('complaintViewModal');


        if (!complaintModal) {
            return;
        }


        /*
         * Fired when Bootstrap is about to open the modal.
         */
        complaintModal.addEventListener(
            'show.bs.modal',
            function (event) {

                const button = event.relatedTarget;

                if (!button) {
                    return;
                }


                /*
                 * Get the complaint ID from:
                 *
                 * data-bs-id="15"
                 *
                 * JavaScript reads this as:
                 * button.dataset.bsId
                 */
                const complaintId = button.dataset.bsId;


                /*
                 * Find the complaint from $allComplaints
                 */
                const complaint = complaints.find(function (item) {

                    return String(item.id) === String(complaintId);

                });


                /*
                 * If complaint doesn't exist
                 */
                if (!complaint) {

                    console.error(
                        'Complaint not found:',
                        complaintId
                    );

                    return;
                }


                /*
                 * ==========================================
                 * BASIC INFORMATION
                 * ==========================================
                 */

                document.getElementById('viewComplaintId')
                    .textContent = complaint.id ?? '-';

                document.getElementById('viewEmployee')
                    .textContent = complaint.reporter_name ?? '-';

                document.getElementById('viewDepartment')
                    .textContent =
                    complaint.reporter_department ?? '-';

                document.getElementById('viewType')
                    .textContent = complaint.type ?? '-';

                document.getElementById('viewTitle')
                    .textContent = complaint.title ?? '-';


                /*
                 * ==========================================
                 * SEVERITY
                 * ==========================================
                 */

                const severityElement =
                    document.getElementById('viewSeverity');

                severityElement.innerHTML = '';

                const severityBadge =
                    document.createElement('span');

                severityBadge.className =
                    'severity severity-' +
                    String(
                        complaint.severity ?? 'low'
                    ).toLowerCase();

                severityBadge.textContent =
                    complaint.severity ?? 'Unknown';

                severityElement.appendChild(
                    severityBadge
                );


                /*
                 * ==========================================
                 * STATUS
                 * ==========================================
                 */

                const statusElement =
                    document.getElementById('viewStatus');

                const status =
                    String(
                        complaint.status ?? 'unknown'
                    ).toLowerCase();

                const statusLabel =
                    status
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, function (letter) {
                            return letter.toUpperCase();
                        });


                statusElement.innerHTML =
                    `
                    <span class="complaint-status status-${status}">
                        <span></span>
                        ${escapeHtml(statusLabel)}
                    </span>
                `;


                /*
                 * ==========================================
                 * DESCRIPTION
                 * ==========================================
                 */

                document.getElementById('viewDescription')
                    .textContent =
                    complaint.description ??
                    'No description provided.';


                /*
                 * ==========================================
                 * INCIDENT INFORMATION
                 * ==========================================
                 */

                document.getElementById('viewIncidentDate')
                    .textContent =
                    formatDate(
                        complaint.incident_date
                    );

                document.getElementById('viewIncidentTime')
                    .textContent =
                    formatTime(
                        complaint.incident_time
                    );

                document.getElementById('viewLocation')
                    .textContent =
                    complaint.location ?? '-';


                /*
                 * ==========================================
                 * ASSIGNED ADMIN
                 * ==========================================
                 */

                document.getElementById('viewAssigned')
                    .textContent =
                    complaint.assigned_name ||
                    'Unassigned';


                /*
                 * ==========================================
                 * CREATED
                 * ==========================================
                 */

                document.getElementById('viewCreatedAt')
                    .textContent =
                    formatDateTime(
                        complaint.created_at
                    );


                /*
                 * ==========================================
                 * EMPLOYEE RESPONSE
                 * ==========================================
                 */

                document.getElementById(
                    'viewEmployeeResponse'
                ).textContent =
                    complaint.employee_response ||
                    'No response provided.';


                /*
                 * ==========================================
                 * RESPONSE DATE
                 * ==========================================
                 */

                document.getElementById(
                    'viewEmployeeResponseDate'
                ).textContent =
                    complaint.employee_response_date
                        ? 'Responded on ' +
                        formatDateTime(
                            complaint.employee_response_date
                        )
                        : '';

            }
        );


        /*
         * ==============================================
         * FORMAT DATE
         * ==============================================
         */

        function formatDate(value) {

            if (!value) {
                return '-';
            }

            const date =
                new Date(value + 'T00:00:00');

            if (isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleDateString(
                'en-US',
                {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }
            );
        }


        /*
         * ==============================================
         * FORMAT TIME
         * ==============================================
         */

        function formatTime(value) {

            if (!value) {
                return '-';
            }

            const parts = value.split(':');

            if (parts.length < 2) {
                return value;
            }

            let hour =
                parseInt(parts[0], 10);

            const minute = parts[1];

            const period =
                hour >= 12 ? 'PM' : 'AM';

            hour =
                hour % 12 || 12;

            return `${hour}:${minute} ${period}`;
        }


        /*
         * ==============================================
         * FORMAT DATETIME
         * ==============================================
         */

        function formatDateTime(value) {

            if (!value) {
                return '-';
            }

            const date =
                new Date(
                    value.replace(' ', 'T')
                );

            if (isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString(
                'en-US',
                {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                }
            );
        }


        /*
         * ==============================================
         * HTML ESCAPE
         * ==============================================
         */

        function escapeHtml(value) {

            const div =
                document.createElement('div');

            div.textContent = value;

            return div.innerHTML;
        }

    });
</script>