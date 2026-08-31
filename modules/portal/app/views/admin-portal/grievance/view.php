<div class="modal fade" id="grievanceViewModal" tabindex="-1" aria-labelledby="grievanceViewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"
        style="max-width:1100px; width:calc(100% - 24px);">


        <div class="modal-content" style="
            border:0;
            border-radius:14px;
            overflow:hidden;
            box-shadow:0 20px 60px rgba(15,23,42,.20);
            background:#ffffff;
        ">

            <!-- HEADER -->
            <div class="modal-header" style="
                padding:18px 22px;
                border-bottom:1px solid #e2e8f0;
                background:#ffffff;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:13px;
                    min-width:0;
                ">

                    <div style="
                        width:42px;
                        height:42px;
                        min-width:42px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:10px;
                        background:#eff6ff;
                        color:#2563eb;
                        font-size:17px;
                    ">
                        <i class="fas fa-file-alt"></i>
                    </div>

                    <div style="min-width:0;">

                        <h5 class="modal-title" id="grievanceViewModalLabel" style="
                            margin:0;
                            color:#0f172a;
                            font-size:16px;
                            font-weight:700;
                        ">
                            Grievance Details
                        </h5>

                        <div style="
                            margin-top:3px;
                            color:#94a3b8;
                            font-size:10px;
                        ">
                            Case #
                            <span id="viewGrievanceId" style="
                                color:#475569;
                                font-weight:700;
                            ">-</span>
                        </div>

                    </div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="
                    margin:0;
                    padding:10px;
                    opacity:.7;
                "></button>

            </div>


            <!-- BODY -->
            <div class="modal-body" style="
                padding:22px;
                background:#f8fafc;
            ">

                <!-- CASE SUMMARY -->

                <div style="
                    background:#ffffff;
                    border:1px solid #e2e8f0;
                    border-radius:11px;
                    padding:18px;
                    margin-bottom:16px;
                ">

                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:flex-start;
                        gap:15px;
                        flex-wrap:wrap;
                    ">

                        <div style="min-width:0; flex:1;">

                            <div style="
                                color:#94a3b8;
                                font-size:9px;
                                font-weight:700;
                                text-transform:uppercase;
                                letter-spacing:.05em;
                                margin-bottom:5px;
                            ">
                                Grievance Subject
                            </div>

                            <h4 id="viewSubject" style="
                                margin:0;
                                color:#0f172a;
                                font-size:19px;
                                font-weight:700;
                                line-height:1.4;
                                word-break:break-word;
                            ">
                                -
                            </h4>

                        </div>


                        <!-- STATUS / PRIORITY -->

                        <div style="
                            display:flex;
                            align-items:center;
                            gap:7px;
                            flex-wrap:wrap;
                        ">

                            <span id="viewPriority" style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                min-width:70px;
                                padding:6px 10px;
                                border-radius:20px;
                                background:#f0fdf4;
                                color:#15803d;
                                border:1px solid #bbf7d0;
                                font-size:9px;
                                font-weight:700;
                            ">
                                Low
                            </span>

                            <span id="viewStatus" style="
                                display:inline-flex;
                                align-items:center;
                                gap:5px;
                                padding:6px 10px;
                                border-radius:20px;
                                background:#fff7ed;
                                color:#c2410c;
                                border:1px solid #fed7aa;
                                font-size:9px;
                                font-weight:700;
                            ">
                                <span style="
                                    width:5px;
                                    height:5px;
                                    border-radius:50%;
                                    background:currentColor;
                                "></span>
                                Pending
                            </span>

                        </div>

                    </div>

                </div>


                <!-- INFORMATION GRID -->

                <div style="
                    display:grid;
                    grid-template-columns:repeat(3,minmax(0,1fr));
                    gap:12px;
                    margin-bottom:16px;
                ">

                    <!-- CATEGORY -->

                    <div style="
                        padding:14px;
                        background:#ffffff;
                        border:1px solid #e2e8f0;
                        border-radius:10px;
                    ">

                        <div style="
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            margin-bottom:7px;
                        ">
                            Category
                        </div>

                        <div id="viewCategory" style="
                            color:#334155;
                            font-size:12px;
                            font-weight:600;
                            word-break:break-word;
                        ">
                            -
                        </div>

                    </div>


                    <!-- EMPLOYEE -->

                    <div style="
                        padding:14px;
                        background:#ffffff;
                        border:1px solid #e2e8f0;
                        border-radius:10px;
                    ">

                        <div style="
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            margin-bottom:7px;
                        ">
                            Employee ID
                        </div>

                        <div id="viewEmployeeId" style="
                            color:#334155;
                            font-size:12px;
                            font-weight:600;
                        ">
                            -
                        </div>

                    </div>


                    <!-- CREATED -->

                    <div style="
                        padding:14px;
                        background:#ffffff;
                        border:1px solid #e2e8f0;
                        border-radius:10px;
                    ">

                        <div style="
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            margin-bottom:7px;
                        ">
                            Submitted
                        </div>

                        <div id="viewCreatedAt" style="
                            color:#334155;
                            font-size:12px;
                            font-weight:600;
                        ">
                            -
                        </div>

                    </div>

                </div>


                <!-- PRIVACY -->

                <div style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    flex-wrap:wrap;
                    margin-bottom:16px;
                ">

                    <span id="viewAnonymous" style="
                        display:inline-flex;
                        align-items:center;
                        gap:5px;
                        padding:6px 9px;
                        border-radius:6px;
                        background:#f1f5f9;
                        color:#475569;
                        font-size:9px;
                        font-weight:700;
                    ">
                        <i class="fas fa-user-secret"></i>
                        Anonymous
                    </span>

                    <span id="viewConfidential" style="
                        display:inline-flex;
                        align-items:center;
                        gap:5px;
                        padding:6px 9px;
                        border-radius:6px;
                        background:#fef2f2;
                        color:#b91c1c;
                        font-size:9px;
                        font-weight:700;
                    ">
                        <i class="fas fa-lock"></i>
                        Confidential
                    </span>

                </div>


                <!-- DESCRIPTION -->

                <div style="
                    background:#ffffff;
                    border:1px solid #e2e8f0;
                    border-radius:11px;
                    margin-bottom:16px;
                    overflow:hidden;
                ">

                    <div style="
                        padding:13px 16px;
                        border-bottom:1px solid #e2e8f0;
                        color:#334155;
                        font-size:11px;
                        font-weight:700;
                    ">
                        <i class="fas fa-align-left" style="
                            margin-right:6px;
                            color:#2563eb;
                        "></i>
                        Grievance Description
                    </div>

                    <div id="viewDescription" style="
                        padding:17px;
                        color:#475569;
                        font-size:12px;
                        line-height:1.7;
                        white-space:pre-wrap;
                        word-break:break-word;
                        min-height:80px;
                    ">
                        -
                    </div>

                </div>


                <!-- ATTACHMENT -->

                <div id="viewAttachmentSection" style="
                    background:#ffffff;
                    border:1px solid #e2e8f0;
                    border-radius:11px;
                    padding:15px 16px;
                    margin-bottom:16px;
                ">

                    <div style="
                        color:#334155;
                        font-size:11px;
                        font-weight:700;
                        margin-bottom:10px;
                    ">
                        <i class="fas fa-paperclip" style="
                            margin-right:6px;
                            color:#2563eb;
                        "></i>
                        Supporting Attachment
                    </div>

                    <a href="#" id="viewAttachment" target="_blank" style="
                        display:inline-flex;
                        align-items:center;
                        gap:7px;
                        padding:8px 11px;
                        border:1px solid #dbe3ef;
                        border-radius:7px;
                        color:#2563eb;
                        background:#f8fafc;
                        font-size:10px;
                        font-weight:600;
                        text-decoration:none;
                    ">
                        <i class="fas fa-file"></i>
                        View Attachment
                    </a>

                </div>


                <!-- ADMIN ACTION / RESOLUTION -->

                <div style="
                    display:grid;
                    grid-template-columns:repeat(2,minmax(0,1fr));
                    gap:16px;
                ">

                    <!-- ACTION TAKEN -->

                    <div style="
                        background:#ffffff;
                        border:1px solid #e2e8f0;
                        border-radius:11px;
                        overflow:hidden;
                    ">

                        <div style="
                            padding:13px 16px;
                            border-bottom:1px solid #e2e8f0;
                            color:#334155;
                            font-size:11px;
                            font-weight:700;
                        ">
                            <i class="fas fa-tasks" style="
                                margin-right:6px;
                                color:#2563eb;
                            "></i>
                            Action Taken
                        </div>

                        <div id="viewActionTaken" style="
                            padding:16px;
                            color:#64748b;
                            font-size:11px;
                            line-height:1.6;
                            min-height:80px;
                            white-space:pre-wrap;
                        ">
                            No action recorded.
                        </div>

                    </div>


                    <!-- RESOLUTION -->

                    <div style="
                        background:#ffffff;
                        border:1px solid #e2e8f0;
                        border-radius:11px;
                        overflow:hidden;
                    ">

                        <div style="
                            padding:13px 16px;
                            border-bottom:1px solid #e2e8f0;
                            color:#334155;
                            font-size:11px;
                            font-weight:700;
                        ">
                            <i class="fas fa-check-circle" style="
                                margin-right:6px;
                                color:#16a34a;
                            "></i>
                            Resolution
                        </div>

                        <div id="viewResolution" style="
                            padding:16px;
                            color:#64748b;
                            font-size:11px;
                            line-height:1.6;
                            min-height:80px;
                            white-space:pre-wrap;
                        ">
                            No resolution recorded.
                        </div>

                    </div>

                </div>


                <!-- ESCALATION -->

                <div id="viewEscalationSection" style="
                    margin-top:16px;
                    background:#fff7ed;
                    border:1px solid #fed7aa;
                    border-radius:11px;
                    padding:15px 16px;
                ">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        color:#c2410c;
                        font-size:11px;
                        font-weight:700;
                        margin-bottom:7px;
                    ">
                        <i class="fas fa-exclamation-triangle"></i>
                        Escalation Information
                    </div>

                    <div style="
                        display:grid;
                        grid-template-columns:140px 1fr;
                        gap:8px;
                        font-size:10px;
                    ">

                        <span style="color:#9a3412;font-weight:600;">
                            Escalation Level
                        </span>

                        <span id="viewEscalationLevel" style="color:#7c2d12;">
                            -
                        </span>

                        <span style="color:#9a3412;font-weight:600;">
                            Reason
                        </span>

                        <span id="viewEscalationReason" style="color:#7c2d12;">
                            -
                        </span>

                    </div>

                </div>


                <!-- SATISFACTION -->

                <div style="
                    margin-top:16px;
                    background:#ffffff;
                    border:1px solid #e2e8f0;
                    border-radius:11px;
                    padding:16px;
                ">

                    <div style="
                        color:#334155;
                        font-size:11px;
                        font-weight:700;
                        margin-bottom:10px;
                    ">
                        <i class="fas fa-star" style="
                            color:#eab308;
                            margin-right:6px;
                        "></i>
                        Employee Satisfaction
                    </div>

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:15px;
                        flex-wrap:wrap;
                    ">

                        <div id="viewSatisfactionRating" style="
                            color:#eab308;
                            font-size:16px;
                            font-weight:700;
                        ">
                            -
                        </div>

                        <div id="viewSatisfactionComment" style="
                            color:#64748b;
                            font-size:11px;
                            flex:1;
                            min-width:200px;
                        ">
                            No satisfaction feedback provided.
                        </div>

                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="modal-footer" style="
                padding:13px 20px;
                border-top:1px solid #e2e8f0;
                background:#ffffff;
                display:flex;
                align-items:center;
                justify-content:flex-end;
                gap:8px;
                flex-wrap:wrap;
            ">

                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="
                    min-width:80px;
                    height:35px;
                    border:1px solid #dbe3ef;
                    border-radius:7px;
                    color:#475569;
                    background:#ffffff;
                    font-size:11px;
                    font-weight:600;
                ">
                    <i class="fas fa-times me-1"></i>
                    Close
                </button>

            </div>

        </div>

    </div>


</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const grievanceModal = document.getElementById('grievanceViewModal');

        if (!grievanceModal) {
            return;
        }

        /*
         * PHP grievance data
         */
        const grievances = <?= json_encode(
            $allGrievance ?? [],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        grievanceModal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const grievanceId = button.getAttribute('data-bs-id');

            /*
             * Find the selected grievance
             */
            const grievance = grievances.find(function (item) {
                return String(item.eer_grievance_id) === String(grievanceId);
            });

            if (!grievance) {
                console.error('Grievance not found:', grievanceId);
                return;
            }

            /*
             * Basic helper
             */
            function value(value, fallback = '-') {
                return value !== null &&
                    value !== undefined &&
                    String(value).trim() !== ''
                    ? value
                    : fallback;
            }

            /*
             * Format date
             */
            function formatDate(dateString) {

                if (!dateString) {
                    return '-';
                }

                const date = new Date(dateString.replace(' ', 'T'));

                if (isNaN(date.getTime())) {
                    return dateString;
                }

                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            /*
             * Status label
             */
            function formatStatus(status) {

                if (!status) {
                    return 'Unknown';
                }

                return status
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, function (letter) {
                        return letter.toUpperCase();
                    });
            }

            /*
             * Priority label
             */
            function formatPriority(priority) {

                if (!priority) {
                    return 'Unknown';
                }

                return priority.charAt(0).toUpperCase() +
                    priority.slice(1).toLowerCase();
            }

            /*
             * Populate modal
             */

            document.getElementById('viewGrievanceId').textContent =
                value(grievance.eer_grievance_id);

            document.getElementById('viewSubject').textContent =
                value(grievance.subject);

            document.getElementById('viewEmployeeId').textContent =
                value(grievance.employee_id);

            document.getElementById('viewCategory').textContent =
                value(grievance.category);

            document.getElementById('viewDescription').textContent =
                value(grievance.description);

            document.getElementById('viewCreatedAt').textContent =
                formatDate(grievance.created_at);

            /*
             * Priority
             */
            const priorityElement =
                document.getElementById('viewPriority');

            priorityElement.textContent =
                formatPriority(grievance.priority);

            /*
             * Reset priority styling
             */
            priorityElement.style.background = '#f1f5f9';
            priorityElement.style.color = '#475569';
            priorityElement.style.borderColor = '#cbd5e1';

            if (grievance.priority === 'low') {

                priorityElement.style.background = '#f0fdf4';
                priorityElement.style.color = '#15803d';
                priorityElement.style.borderColor = '#bbf7d0';

            } else if (grievance.priority === 'medium') {

                priorityElement.style.background = '#fffbeb';
                priorityElement.style.color = '#b45309';
                priorityElement.style.borderColor = '#fde68a';

            } else if (grievance.priority === 'high') {

                priorityElement.style.background = '#fff7ed';
                priorityElement.style.color = '#c2410c';
                priorityElement.style.borderColor = '#fed7aa';

            } else if (grievance.priority === 'urgent') {

                priorityElement.style.background = '#fef2f2';
                priorityElement.style.color = '#b91c1c';
                priorityElement.style.borderColor = '#fecaca';
            }

            /*
             * Status
             */
            const statusElement =
                document.getElementById('viewStatus');

            statusElement.textContent =
                formatStatus(grievance.status);

            /*
             * Restore status dot
             */
            const statusDot = document.createElement('span');

            statusDot.style.width = '5px';
            statusDot.style.height = '5px';
            statusDot.style.borderRadius = '50%';
            statusDot.style.background = 'currentColor';

            statusElement.prepend(statusDot);

            /*
             * Status colors
             */
            statusElement.style.background = '#f1f5f9';
            statusElement.style.color = '#475569';
            statusElement.style.borderColor = '#cbd5e1';

            if (grievance.status === 'pending') {

                statusElement.style.background = '#fff7ed';
                statusElement.style.color = '#c2410c';
                statusElement.style.borderColor = '#fed7aa';

            } else if (grievance.status === 'in_progress') {

                statusElement.style.background = '#eff6ff';
                statusElement.style.color = '#1d4ed8';
                statusElement.style.borderColor = '#bfdbfe';

            } else if (grievance.status === 'resolved') {

                statusElement.style.background = '#f0fdf4';
                statusElement.style.color = '#15803d';
                statusElement.style.borderColor = '#bbf7d0';

            } else if (grievance.status === 'closed') {

                statusElement.style.background = '#f1f5f9';
                statusElement.style.color = '#475569';
                statusElement.style.borderColor = '#cbd5e1';
            }

            /*
             * Anonymous
             */
            const anonymousElement =
                document.getElementById('viewAnonymous');

            if (Number(grievance.anonymous) === 1) {

                anonymousElement.innerHTML =
                    '<i class="fas fa-user-secret"></i> Anonymous';

                anonymousElement.style.display = 'inline-flex';

            } else {

                anonymousElement.innerHTML =
                    '<i class="fas fa-user"></i> Identified';

                anonymousElement.style.background = '#eff6ff';
                anonymousElement.style.color = '#1d4ed8';
            }

            /*
             * Confidential
             */
            const confidentialElement =
                document.getElementById('viewConfidential');

            if (Number(grievance.confidential) === 1) {

                confidentialElement.innerHTML =
                    '<i class="fas fa-lock"></i> Confidential';

                confidentialElement.style.display = 'inline-flex';

            } else {

                confidentialElement.innerHTML =
                    '<i class="fas fa-unlock"></i> Not Confidential';

                confidentialElement.style.background = '#f1f5f9';
                confidentialElement.style.color = '#64748b';
            }

            /*
             * Attachment
             */
            const attachmentSection =
                document.getElementById('viewAttachmentSection');

            const attachment =
                document.getElementById('viewAttachment');

            if (grievance.attachment_path) {

                attachmentSection.style.display = 'block';

                attachment.href =
                    grievance.attachment_path;

            } else {

                attachmentSection.style.display = 'none';
                attachment.removeAttribute('href');
            }

            /*
             * Action Taken
             */
            document.getElementById('viewActionTaken').textContent =
                value(
                    grievance.action_taken,
                    'No action recorded.'
                );

            /*
             * Resolution
             */
            document.getElementById('viewResolution').textContent =
                value(
                    grievance.resolution_of_complaint,
                    'No resolution recorded.'
                );

            /*
             * Escalation
             */
            const escalationSection =
                document.getElementById('viewEscalationSection');

            if (
                grievance.escalation_level ||
                grievance.escalation_reason
            ) {

                escalationSection.style.display = 'block';

                document.getElementById('viewEscalationLevel').textContent =
                    value(grievance.escalation_level);

                document.getElementById('viewEscalationReason').textContent =
                    value(grievance.escalation_reason);

            } else {

                escalationSection.style.display = 'none';
            }

            /*
             * Satisfaction
             */
            const rating =
                grievance.satisfaction_rating;

            document.getElementById(
                'viewSatisfactionRating'
            ).textContent =
                rating
                    ? `${rating}/5`
                    : '-';

            document.getElementById(
                'viewSatisfactionComment'
            ).textContent =
                value(
                    grievance.satisfaction_comment,
                    'No satisfaction feedback provided.'
                );

        });

    });
</script>