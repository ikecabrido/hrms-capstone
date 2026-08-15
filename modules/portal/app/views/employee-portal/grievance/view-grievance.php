<?php if (!empty($employeeGrievances)): ?>

    <?php foreach ($employeeGrievances as $grievance): ?>

        <?php
        $grievanceId = (int) ($grievance['eer_grievance_id'] ?? 0);
        ?>

        <!-- View Modal -->
<div 
    class="modal fade" 
    id="viewGrievanceModal<?= $grievanceId ?>" 
    tabindex="-1" 
    aria-labelledby="viewGrievanceModalLabel<?= $grievanceId ?>" 
    aria-hidden="true"
    data-bs-backdrop="false"
>

            <div class="modal-dialog modal-lg modal-dialog-centered"
                style="max-width:760px;">

                <div
                    class="modal-content"
                    style="
                        border:0;
                        border-radius:14px;
                        overflow:hidden;
                        box-shadow:0 20px 45px rgba(0,0,0,.12);
                    "
                >

                    <!-- HEADER -->
                    <div
                        class="modal-header"
                        style="
                            padding:18px 22px;
                            border-bottom:1px solid #f1f5f9;
                            background:#fff;
                        "
                    >

                        <div>

                            <h5
                                id="viewGrievanceModalLabel<?= $grievanceId ?>"
                                style="
                                    margin:0;
                                    color:#111827;
                                    font-size:16px;
                                    font-weight:700;
                                "
                            >
                                Grievance Details
                            </h5>

                            <div
                                style="
                                    margin-top:4px;
                                    color:#9ca3af;
                                    font-size:10px;
                                "
                            >
                                Grievance ID:

                                <span
                                    style="
                                        color:#2563eb;
                                        font-weight:700;
                                    "
                                >
                                    <?= htmlspecialchars($grievanceId) ?>
                                </span>
                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>

                    </div>


                    <!-- BODY -->
                    <div
                        class="modal-body"
                        style="
                            padding:22px;
                            background:#fff;
                        "
                    >

                        <?php
                        $status = strtolower(
                            $grievance['status'] ?? 'pending'
                        );

                        $statusStyle = match ($status) {

                            'pending' => [
                                'background:#fff7ed;',
                                'color:#ea580c;'
                            ],

                            'under_review' => [
                                'background:#eff6ff;',
                                'color:#2563eb;'
                            ],

                            'investigation' => [
                                'background:#fefce8;',
                                'color:#ca8a04;'
                            ],

                            'resolved' => [
                                'background:#f0fdf4;',
                                'color:#16a34a;'
                            ],

                            'rejected' => [
                                'background:#fef2f2;',
                                'color:#dc2626;'
                            ],

                            default => [
                                'background:#f3f4f6;',
                                'color:#6b7280;'
                            ]
                        };
                        ?>


                        <!-- STATUS -->
                        <div
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:space-between;
                                margin-bottom:20px;
                                padding:13px 15px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#f8fafc;
                            "
                        >

                            <div>

                                <div
                                    style="
                                        color:#6b7280;
                                        font-size:10px;
                                    "
                                >
                                    Current Status
                                </div>

                                <div
                                    style="
                                        margin-top:3px;
                                        color:#111827;
                                        font-size:12px;
                                        font-weight:700;
                                        text-transform:capitalize;
                                    "
                                >
                                    <?= htmlspecialchars(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $status
                                        )
                                    ) ?>
                                </div>

                            </div>


                            <span
                                style="
                                    padding:5px 10px;
                                    border-radius:20px;
                                    font-size:9px;
                                    font-weight:700;
                                    text-transform:capitalize;
                                    <?= $statusStyle[0] ?>
                                    <?= $statusStyle[1] ?>
                                "
                            >
                                <?= htmlspecialchars(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $status
                                    )
                                ) ?>
                            </span>

                        </div>


                        <!-- GRIEVANCE INFORMATION -->
                        <div style="margin-bottom:20px;">

                            <div
                                style="
                                    margin-bottom:12px;
                                    color:#111827;
                                    font-size:12px;
                                    font-weight:700;
                                "
                            >
                                Grievance Information
                            </div>


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:repeat(2, 1fr);
                                    gap:12px;
                                "
                            >

                                <!-- CATEGORY -->
                                <div
                                    style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    "
                                >

                                    <div
                                        style="
                                            color:#9ca3af;
                                            font-size:9px;
                                        "
                                    >
                                        Category
                                    </div>

                                    <div
                                        style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        "
                                    >
                                        <?= htmlspecialchars(
                                            $grievance['category'] ?? '—'
                                        ) ?>
                                    </div>

                                </div>


                                <!-- DATE -->
                                <div
                                    style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    "
                                >

                                    <div
                                        style="
                                            color:#9ca3af;
                                            font-size:9px;
                                        "
                                    >
                                        Incident Date
                                    </div>

                                    <div
                                        style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        "
                                    >
                                        <?= htmlspecialchars(
                                            $grievance['incident_date'] ?? '—'
                                        ) ?>
                                    </div>

                                </div>


                                <!-- LOCATION -->
                                <div
                                    style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    "
                                >

                                    <div
                                        style="
                                            color:#9ca3af;
                                            font-size:9px;
                                        "
                                    >
                                        Location
                                    </div>

                                    <div
                                        style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                        "
                                    >
                                        <?= htmlspecialchars(
                                            $grievance['location'] ?? '—'
                                        ) ?>
                                    </div>

                                </div>


                                <!-- PRIORITY -->
                                <div
                                    style="
                                        padding:12px 14px;
                                        border:1px solid #f1f5f9;
                                        border-radius:9px;
                                        background:#fafafa;
                                    "
                                >

                                    <div
                                        style="
                                            color:#9ca3af;
                                            font-size:9px;
                                        "
                                    >
                                        Priority
                                    </div>

                                    <div
                                        style="
                                            margin-top:4px;
                                            color:#374151;
                                            font-size:11px;
                                            font-weight:600;
                                            text-transform:capitalize;
                                        "
                                    >
                                        <?= htmlspecialchars(
                                            $grievance['priority'] ?? '—'
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- SUBJECT -->
                        <div style="margin-bottom:16px;">

                            <div
                                style="
                                    margin-bottom:7px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                "
                            >
                                Subject
                            </div>

                            <div
                                style="
                                    padding:12px 14px;
                                    border:1px solid #e5e7eb;
                                    border-radius:9px;
                                    color:#374151;
                                    background:#fff;
                                    font-size:11px;
                                    font-weight:600;
                                "
                            >
                                <?= htmlspecialchars(
                                    $grievance['subject'] ?? '—'
                                ) ?>
                            </div>

                        </div>


                        <!-- DESCRIPTION -->
                        <div>

                            <div
                                style="
                                    margin-bottom:7px;
                                    color:#6b7280;
                                    font-size:10px;
                                    font-weight:600;
                                "
                            >
                                Description
                            </div>

                            <?php if (!empty($grievance['description'])): ?>

                                <textarea
                                    readonly
                                    style="
                                        width:100%;
                                        min-height:100px;
                                        padding:13px 14px;
                                        border:1px solid #e5e7eb;
                                        border-radius:9px;
                                        color:#374151;
                                        background:#fafafa;
                                        font-size:11px;
                                        line-height:1.6;
                                        resize:none;
                                        outline:none;
                                        font-family:inherit;
                                        box-sizing:border-box;
                                    "
                                ><?= htmlspecialchars($grievance['description']) ?></textarea>

                            <?php else: ?>

                                <div
                                    style="
                                        padding:13px 14px;
                                        border:1px solid #e5e7eb;
                                        border-radius:9px;
                                        color:#9ca3af;
                                        background:#fafafa;
                                        font-size:11px;
                                    "
                                >
                                    No description provided.
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- FOOTER -->
                    <div
                        class="modal-footer"
                        style="
                            padding:14px 22px;
                            border-top:1px solid #f1f5f9;
                            background:#fff;
                        "
                    >

                        <button
                            type="button"
                            class="btn btn-light btn-sm"
                            data-bs-dismiss="modal"
                            style="
                                padding:8px 16px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                color:#374151;
                                font-size:11px;
                                font-weight:600;
                            "
                        >
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>