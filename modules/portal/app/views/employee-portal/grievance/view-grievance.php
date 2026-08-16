<?php foreach ($employeeGrievances as $grievance): ?>

    <?php
    $grievanceId = (int)($grievance['eer_grievance_id'] ?? 0);
    $status = strtolower($grievance['status'] ?? 'pending');

    $statusStyle = match ($status) {
        'pending' => ['background:#fff7ed;', 'color:#ea580c;'],
        'under_review' => ['background:#eff6ff;', 'color:#2563eb;'],
        'investigation' => ['background:#fefce8;', 'color:#ca8a04;'],
        'resolved' => ['background:#f0fdf4;', 'color:#16a34a;'],
        'rejected', 'escalated' => ['background:#fef2f2;', 'color:#dc2626;'],
        default => ['background:#f3f4f6;', 'color:#6b7280;']
    };
    ?>

    <div class="modal fade"
        id="viewEmployeeGrievanceModal<?= $grievanceId ?>"
        tabindex="-1"
        aria-labelledby="viewEmployeeGrievanceModalLabel<?= $grievanceId ?>"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"
            style="max-width:760px;">

            <div class="modal-content"
                style="
                    border:0;
                    border-radius:16px;
                    overflow:hidden;
                    box-shadow:0 20px 50px rgba(0,0,0,.15);
                ">

                <div class="modal-header"
                    style="
                        padding:20px 24px;
                        border-bottom:1px solid #eef2f7;
                        background:#fff;
                    ">

                    <div>
                        <h5
                            id="viewEmployeeGrievanceModalLabel<?= $grievanceId ?>"
                            style="
                                margin:0;
                                color:#111827;
                                font-size:16px;
                                font-weight:700;
                            ">
                            Grievance Details
                        </h5>

                        <div style="
                            margin-top:5px;
                            color:#9ca3af;
                            font-size:10px;
                        ">
                            Grievance ID:
                            <span style="
                                color:#2563eb;
                                font-weight:700;
                            ">
                                <?= $grievanceId ?>
                            </span>
                        </div>
                    </div>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body"
                    style="
                        padding:24px;
                        background:#fff;
                    ">

                    <!-- STATUS -->
                    <div style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        margin-bottom:20px;
                        padding:13px 15px;
                        border:1px solid #e5e7eb;
                        border-radius:10px;
                        background:#f8fafc;
                    ">

                        <div>
                            <div style="
                                color:#6b7280;
                                font-size:10px;
                            ">
                                Current Status
                            </div>

                            <div style="
                                margin-top:3px;
                                color:#111827;
                                font-size:12px;
                                font-weight:700;
                                text-transform:capitalize;
                            ">
                                <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                            </div>
                        </div>

                        <span style="
                            padding:5px 10px;
                            border-radius:20px;
                            font-size:9px;
                            font-weight:700;
                            text-transform:capitalize;
                            <?= $statusStyle[0] ?>
                            <?= $statusStyle[1] ?>
                        ">
                            <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                        </span>

                    </div>

                    <!-- INFORMATION -->
                    <div style="margin-bottom:20px;">

                        <div style="
                            margin-bottom:12px;
                            color:#111827;
                            font-size:12px;
                            font-weight:700;
                        ">
                            Grievance Information
                        </div>

                        <div style="
                            display:grid;
                            grid-template-columns:1fr 1fr;
                            gap:12px;
                        ">

                            <div style="
                                padding:12px 14px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">
                                <div style="color:#9ca3af;font-size:9px;">
                                    Category
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars($grievance['category'] ?? '—') ?>
                                </div>
                            </div>

                            <div style="
                                padding:12px 14px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">
                                <div style="color:#9ca3af;font-size:9px;">
                                    Incident Date
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars($grievance['incident_date'] ?? '—') ?>
                                </div>
                            </div>

                            <div style="
                                padding:12px 14px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">
                                <div style="color:#9ca3af;font-size:9px;">
                                    Location
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars($grievance['location'] ?? '—') ?>
                                </div>
                            </div>

                            <div style="
                                padding:12px 14px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">
                                <div style="color:#9ca3af;font-size:9px;">
                                    Priority
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                    text-transform:capitalize;
                                ">
                                    <?= htmlspecialchars($grievance['priority'] ?? '—') ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- SUBJECT -->
                    <div style="margin-bottom:18px;">

                        <div style="
                            margin-bottom:7px;
                            color:#6b7280;
                            font-size:10px;
                            font-weight:600;
                        ">
                            Subject
                        </div>

                        <div style="
                            padding:12px 14px;
                            border:1px solid #e5e7eb;
                            border-radius:9px;
                            color:#374151;
                            background:#fff;
                            font-size:11px;
                            font-weight:600;
                            overflow-wrap:anywhere;
                        ">
                            <?= htmlspecialchars($grievance['subject'] ?? '—') ?>
                        </div>

                    </div>

                    <!-- DESCRIPTION -->
                    <div style="margin-bottom:18px;">

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
                            min-height:120px;
                            padding:13px 14px;
                            border:1px solid #e5e7eb;
                            border-radius:9px;
                            color:#374151;
                            background:#fafafa;
                            font-size:11px;
                            line-height:1.6;
                            white-space:pre-wrap;
                            overflow-wrap:anywhere;
                            box-sizing:border-box;
                        ">
                            <?= !empty($grievance['description'])
                                ? htmlspecialchars($grievance['description'])
                                : 'No description provided.'
                            ?>
                        </div>

                    </div>

                    <!-- RESOLUTION -->
                    <?php if (!empty($grievance['resolution_of_complaint'])): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                margin-bottom:7px;
                                color:#6b7280;
                                font-size:10px;
                                font-weight:600;
                            ">
                                Resolution
                            </div>

                            <div style="
                                padding:12px 14px;
                                border:1px solid #e5e7eb;
                                border-radius:9px;
                                background:#f8fafc;
                                color:#374151;
                                font-size:11px;
                                line-height:1.6;
                                white-space:pre-wrap;
                            ">
                                <?= htmlspecialchars($grievance['resolution_of_complaint']) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- ACTION TAKEN -->
                    <?php if (!empty($grievance['action_taken'])): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                margin-bottom:7px;
                                color:#6b7280;
                                font-size:10px;
                                font-weight:600;
                            ">
                                Action Taken
                            </div>

                            <div style="
                                padding:12px 14px;
                                border:1px solid #e5e7eb;
                                border-radius:9px;
                                background:#f8fafc;
                                color:#374151;
                                font-size:11px;
                                line-height:1.6;
                                white-space:pre-wrap;
                            ">
                                <?= htmlspecialchars($grievance['action_taken']) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- ESCALATION -->
                    <?php if (
                        !empty($grievance['escalation_level']) ||
                        !empty($grievance['escalation_reason'])
                    ): ?>

                        <div style="
                            padding:13px;
                            border:1px solid #fecaca;
                            border-radius:9px;
                            background:#fef2f2;
                        ">

                            <div style="
                                margin-bottom:6px;
                                color:#dc2626;
                                font-size:10px;
                                font-weight:700;
                            ">
                                Escalation Information
                            </div>

                            <?php if (!empty($grievance['escalation_level'])): ?>
                                <div style="
                                    color:#374151;
                                    font-size:10px;
                                    margin-bottom:4px;
                                ">
                                    <strong>Level:</strong>
                                    <?= htmlspecialchars($grievance['escalation_level']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($grievance['escalation_reason'])): ?>
                                <div style="
                                    color:#374151;
                                    font-size:10px;
                                ">
                                    <strong>Reason:</strong>
                                    <?= htmlspecialchars($grievance['escalation_reason']) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                    <!-- ATTACHMENT -->
                    <?php if (!empty($grievance['attachment_path'])): ?>

                        <div style="
                            margin-top:18px;
                            padding:12px;
                            border:1px solid #e5e7eb;
                            border-radius:9px;
                            background:#fafafa;
                        ">

                            <div style="
                                color:#6b7280;
                                font-size:10px;
                                font-weight:600;
                                margin-bottom:6px;
                            ">
                                Attachment
                            </div>

                            <a href="<?= htmlspecialchars($grievance['attachment_path']) ?>"
                                target="_blank"
                                style="
                                    color:#2563eb;
                                    font-size:10px;
                                    font-weight:600;
                                    text-decoration:none;
                                ">
                                <i class="fas fa-paperclip"></i>
                                View Attachment
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="modal-footer"
                    style="
                        padding:14px 24px;
                        border-top:1px solid #eef2f7;
                        background:#fff;
                    ">

                    <button type="button"
                        data-bs-dismiss="modal"
                        style="
                            padding:9px 16px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            background:#fff;
                            color:#374151;
                            font-size:10px;
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