<?php if (!empty($complaintHistory)): ?>

    <?php foreach ($complaintHistory as $complaint): ?>

        <?php
        $status = strtolower($complaint['status'] ?? 'under_initial_review');

        $statusStyles = match ($status) {
            'under_initial_review' => [
                'background:#eff6ff;',
                'color:#2563eb;'
            ],

            'under_investigation' => [
                'background:#fff7ed;',
                'color:#ea580c;'
            ],

            'pending_employee_response' => [
                'background:#fefce8;',
                'color:#ca8a04;'
            ],

            'for_decision' => [
                'background:#f5f3ff;',
                'color:#7c3aed;'
            ],

            'closed_no_violation',
            'closed_resolved' => [
                'background:#f0fdf4;',
                'color:#16a34a;'
            ],

            'closed_warning_issued' => [
                'background:#fffbeb;',
                'color:#d97706;'
            ],

            'closed_suspension',
            'closed_termination_recommended',
            'closed' => [
                'background:#fef2f2;',
                'color:#dc2626;'
            ],

            default => [
                'background:#f3f4f6;',
                'color:#6b7280;'
            ]
        };

        $statusLabel = ucwords(
            str_replace('_', ' ', $status)
        );

        $severity = strtolower(
            $complaint['severity'] ?? 'medium'
        );

        $severityStyles = match ($severity) {
            'low' => [
                'background:#f0fdf4;',
                'color:#15803d;'
            ],

            'medium' => [
                'background:#fffbeb;',
                'color:#d97706;'
            ],

            'high' => [
                'background:#fef2f2;',
                'color:#dc2626;'
            ],

            default => [
                'background:#f3f4f6;',
                'color:#6b7280;'
            ]
        };

        $severityLabel = ucfirst($severity);
        ?>

        <div class="modal fade" id="viewComplaintModal<?= (int) $complaint['id'] ?>" tabindex="-1"
            aria-labelledby="viewComplaintModalLabel<?= (int) $complaint['id'] ?>" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:760px;">

                <div class="modal-content" style="
                        border:0;
                        border-radius:14px;
                        overflow:hidden;
                        box-shadow:0 20px 45px rgba(0,0,0,.12);
                    ">

                    <!-- HEADER -->
                    <div class="modal-header" style="
                            padding:18px 22px;
                            border-bottom:1px solid #f1f5f9;
                            background:#fff;
                        ">

                        <div>

                            <h5 id="viewComplaintModalLabel<?= (int) $complaint['id'] ?>" style="
                                    margin:0;
                                    color:#111827;
                                    font-size:16px;
                                    font-weight:700;
                                ">
                                Complaint Details
                            </h5>

                            <div style="
                                    margin-top:4px;
                                    color:#9ca3af;
                                    font-size:10px;
                                ">
                                Complaint ID:

                                <span style="
                                        color:#2563eb;
                                        font-weight:700;
                                    ">
                                    #<?= (int) $complaint['id'] ?>
                                </span>
                            </div>

                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>

                    <!-- SCROLLABLE BODY -->
                    <div class="modal-body" style="
                            padding:22px;
                            background:#fff;
                            max-height:70vh;
                            overflow-y:auto;
                            overflow-x:hidden;
                            scrollbar-width:thin;
                        ">

                        <!-- STATUS -->
                        <div style="
                                display:flex;
                                align-items:center;
                                justify-content:space-between;
                                gap:15px;
                                margin-bottom:20px;
                                padding:14px 15px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#f8fafc;
                            ">

                            <div>

                                <div style="
                                        color:#6b7280;
                                        font-size:10px;
                                        font-weight:500;
                                    ">
                                    Current Status
                                </div>

                                <div style="
                                        margin-top:3px;
                                        color:#111827;
                                        font-size:12px;
                                        font-weight:700;
                                    ">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </div>

                            </div>

                            <span style="
                                    padding:5px 10px;
                                    border-radius:20px;
                                    font-size:9px;
                                    font-weight:700;
                                    <?= $statusStyles[0] ?>
                                    <?= $statusStyles[1] ?>
                                ">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>

                        </div>

                        <!-- INCIDENT INFORMATION -->
                        <div style="margin-bottom:20px;">

                            <div style="
                                    margin-bottom:12px;
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:700;
                                ">
                                Incident Information
                            </div>

                            <div style="
                                    display:grid;
                                    grid-template-columns:repeat(2,1fr);
                                    gap:12px;
                                ">

                                <!-- TYPE -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Complaint Type
                                    </div>

                                    <div style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                            text-transform:capitalize;
                                        ">
                                        <?= htmlspecialchars(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $complaint['type'] ?? '—'
                                            )
                                        ) ?>
                                    </div>
                                </div>

                                <!-- SEVERITY -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Severity
                                    </div>

                                    <div style="margin-top:4px;">
                                        <span style="
                                                display:inline-flex;
                                                align-items:center;
                                                padding:4px 8px;
                                                border-radius:20px;
                                                font-size:8px;
                                                font-weight:700;
                                                text-transform:uppercase;
                                                <?= $severityStyles[0] ?>
                                                <?= $severityStyles[1] ?>
                                            ">
                                            <?= htmlspecialchars($severityLabel) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- DATE -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Incident Date
                                    </div>

                                    <div style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        ">
                                        <?= !empty($complaint['incident_date'])
                                            ? htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime($complaint['incident_date'])
                                                )
                                            )
                                            : '—'
                                            ?>
                                    </div>
                                </div>

                                <!-- TIME -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Incident Time
                                    </div>

                                    <div style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        ">
                                        <?= !empty($complaint['incident_time'])
                                            ? htmlspecialchars(
                                                date(
                                                    'h:i A',
                                                    strtotime($complaint['incident_time'])
                                                )
                                            )
                                            : '—'
                                            ?>
                                    </div>
                                </div>

                                <!-- LOCATION -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Location
                                    </div>

                                    <div style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        ">
                                        <?= htmlspecialchars(
                                            $complaint['location'] ?? '—'
                                        ) ?>
                                    </div>
                                </div>

                                <!-- SUBMITTED -->
                                <div style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    ">
                                    <div style="
                                            color:#9ca3af;
                                            font-size:9px;
                                            font-weight:500;
                                        ">
                                        Submitted
                                    </div>

                                    <div style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        ">
                                        <?= !empty($complaint['created_at'])
                                            ? htmlspecialchars(
                                                date(
                                                    'M d, Y h:i A',
                                                    strtotime($complaint['created_at'])
                                                )
                                            )
                                            : '—'
                                            ?>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <!-- RESPONDENT -->
                        <div style="margin-bottom:20px;">

                            <div style="
                                    margin-bottom:12px;
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:700;
                                ">
                                Person Being Reported
                            </div>

                            <div style="
                                    padding:14px;
                                    border:1px solid #e5e7eb;
                                    border-radius:10px;
                                    background:#f8fafc;
                                ">

                                <div style="
                                        color:#374151;
                                        font-size:12px;
                                        font-weight:700;
                                    ">
                                    <?= htmlspecialchars(
                                        $complaint['respondent_name']
                                        ?? 'Unknown Employee'
                                    ) ?>
                                </div>

                                <?php if (!empty($complaint['employee_id'])): ?>

                                    <div style="
                                            margin-top:4px;
                                            color:#6b7280;
                                            font-size:10px;
                                        ">
                                        Employee ID:
                                        <?= htmlspecialchars(
                                            $complaint['employee_id']
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- REPORTER -->
                        <div style="margin-bottom:20px;">

                            <div style="
                                    margin-bottom:12px;
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:700;
                                ">
                                Submitted By
                            </div>

                            <div style="
                                    padding:14px;
                                    border:1px solid #e5e7eb;
                                    border-radius:10px;
                                    background:#f8fafc;
                                ">

                                <div style="
                                        color:#374151;
                                        font-size:11px;
                                        font-weight:600;
                                    ">
                                    <?= htmlspecialchars(
                                        $complaint['reporter_name'] ?? '—'
                                    ) ?>
                                </div>

                                <?php if (!empty($complaint['reporter_department'])): ?>

                                    <div style="
                                            margin-top:4px;
                                            color:#6b7280;
                                            font-size:10px;
                                        ">
                                        <?= htmlspecialchars(
                                            $complaint['reporter_department']
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- TITLE -->
                        <div style="margin-bottom:16px;">

                            <div style="
                                    margin-bottom:7px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                Complaint Title
                            </div>

                            <div style="
                                    padding:12px 14px;
                                    border:1px solid #e5e7eb;
                                    border-radius:9px;
                                    color:#374151;
                                    background:#fff;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                <?= htmlspecialchars(
                                    $complaint['title'] ?? '—'
                                ) ?>
                            </div>

                        </div>

                        <!-- DESCRIPTION -->
                        <div style="margin-bottom:20px;">

                            <div style="
                                    margin-bottom:7px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                                Description
                            </div>

                            <div style="
                                    width:100%;
                                    min-height:110px;
                                    padding:13px 14px;
                                    border:1px solid #e5e7eb;
                                    border-radius:9px;
                                    color:#374151;
                                    background:#fafafa;
                                    font-size:11px;
                                    line-height:1.6;
                                    white-space:pre-wrap;
                                    box-sizing:border-box;
                                ">
                                <?= htmlspecialchars(
                                    $complaint['description'] ?? '—'
                                ) ?>
                            </div>

                        </div>

                        <!-- ASSIGNMENT -->
                        <?php if (
                            !empty($complaint['assigned_to']) ||
                            !empty($complaint['assigned_name'])
                        ): ?>

                            <div style="margin-bottom:20px;">

                                <div style="
                                        margin-bottom:12px;
                                        color:#111827;
                                        font-size:12px;
                                        font-weight:700;
                                    ">
                                    Assigned HR Personnel
                                </div>

                                <div style="
                                        padding:14px;
                                        border:1px solid #e5e7eb;
                                        border-radius:10px;
                                        background:#f8fafc;
                                    ">

                                    <div style="
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        ">
                                        <?= htmlspecialchars(
                                            $complaint['assigned_name']
                                            ?? 'Assigned HR Personnel'
                                        ) ?>
                                    </div>

                                    <?php if (!empty($complaint['assigned_to'])): ?>

                                        <div style="
                                                margin-top:4px;
                                                color:#6b7280;
                                                font-size:10px;
                                            ">
                                            Employee ID:
                                            <?= htmlspecialchars(
                                                $complaint['assigned_to']
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <!-- EMPLOYEE RESPONSE -->
                        <?php if (!empty($complaint['employee_response'])): ?>

                            <div style="margin-bottom:20px;">

                                <div style="
                                        margin-bottom:7px;
                                        color:#111827;
                                        font-size:12px;
                                        font-weight:700;
                                    ">
                                    Employee Response
                                </div>

                                <div style="
                                        padding:13px 14px;
                                        border:1px solid #e5e7eb;
                                        border-radius:9px;
                                        color:#374151;
                                        background:#fafafa;
                                        font-size:11px;
                                        line-height:1.6;
                                        white-space:pre-wrap;
                                    ">
                                    <?= htmlspecialchars(
                                        $complaint['employee_response']
                                    ) ?>
                                </div>

                                <?php if (!empty($complaint['employee_response_date'])): ?>

                                    <div style="
                                            margin-top:5px;
                                            color:#9ca3af;
                                            font-size:9px;
                                        ">
                                        Response submitted:
                                        <?= htmlspecialchars(
                                            date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $complaint['employee_response_date']
                                                )
                                            )
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <!-- UPDATED -->
                        <div style="
                                padding-top:12px;
                                border-top:1px solid #f1f5f9;
                                color:#9ca3af;
                                font-size:9px;
                            ">
                            Last updated:
                            <?= !empty($complaint['updated_at'])
                                ? htmlspecialchars(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($complaint['updated_at'])
                                    )
                                )
                                : '—'
                                ?>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer" style="
                            padding:14px 22px;
                            border-top:1px solid #f1f5f9;
                            background:#fff;
                        ">

                        <button type="button" data-bs-dismiss="modal" style="
                                padding:8px 16px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                background:#fff;
                                color:#374151;
                                font-size:11px;
                                font-weight:600;
                                cursor:pointer;
                            ">
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>