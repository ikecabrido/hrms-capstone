<?php if (!empty($complaintHistory)): ?>
    <?php foreach ($complaintHistory as $complaint): ?>
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
                                    <?= htmlspecialchars($complaint['incident_id'] ?? '—') ?>
                                </span>
                            </div>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>


                    <!-- BODY -->
                    <div class="modal-body" style="
                    padding:22px;
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
                                font-weight:500;
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
                                    <?= htmlspecialchars(
                                        str_replace('_', ' ', $complaint['status'] ?? '—')
                                    ) ?>
                                </div>
                            </div>

                            <?php
                            $status = $complaint['status'] ?? 'submitted';

                            $statusStyle = match ($status) {
                                'submitted' => [
                                    'background:#eff6ff;',
                                    'color:#2563eb;'
                                ],
                                'under_review' => [
                                    'background:#fff7ed;',
                                    'color:#ea580c;'
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

                            <span style="
                            padding:5px 10px;
                            border-radius:20px;
                            font-size:9px;
                            font-weight:700;
                            <?= $statusStyle[0] ?>
                            <?= $statusStyle[1] ?>
                        ">
                                <?= htmlspecialchars(
                                    str_replace('_', ' ', $status)
                                ) ?>
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
                            grid-template-columns:repeat(2, 1fr);
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
                                        Incident Type
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
                                                $complaint['incident_type'] ?? '—'
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

                                    <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                    text-transform:capitalize;
                                ">
                                        <?= htmlspecialchars(
                                            $complaint['severity'] ?? '—'
                                        ) ?>
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
                                        <?= htmlspecialchars(
                                            $complaint['incident_date'] ?? '—'
                                        ) ?>
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
                                        <?= htmlspecialchars(
                                            $complaint['incident_time'] ?? '—'
                                        ) ?>
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


                                <!-- RELATIONSHIP -->
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
                                        Relationship to Respondent
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
                                                $complaint['respondent_relationship'] ?? '—'
                                            )
                                        ) ?>
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
                                Respondent
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
                                        $complaint['respondent_name'] ?? '—'
                                    ) ?>
                                </div>

                                <div style="
                                margin-top:4px;
                                color:#6b7280;
                                font-size:10px;
                            ">
                                    <?= htmlspecialchars(
                                        $complaint['respondent_department'] ?? '—'
                                    ) ?>

                                    <?php if (!empty($complaint['respondent_position'])): ?>
                                        ·
                                        <?= htmlspecialchars(
                                            $complaint['respondent_position']
                                        ) ?>
                                    <?php endif; ?>
                                </div>

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
                                Title
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
                        <div>

                            <div style="
                            margin-bottom:7px;
                            color:#6b7280;
                            font-size:10px;
                            font-weight:600;
                        ">
                                Description
                            </div>

                            <?php if (!empty($complaint['description'])): ?>

                                <textarea readonly style="
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
    "><?= htmlspecialchars($complaint['description']) ?></textarea>

                            <?php endif; ?>

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