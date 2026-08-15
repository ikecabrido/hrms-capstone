<div class="employee-dashboard">

    <section class="dashboard-welcome" id="grievanceWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE GRIEVANCE
            </span>

            <h1 class="welcome-title">Grievance</h1>

            <p class="welcome-description">
                Submit and monitor your workplace grievances, view complaint details,
                and track the status and resolution of your concerns.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration">
            <i class="fas fa-comments"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="
    width:100%;
    box-sizing:border-box;
    padding:20px;
">

        <?php
        $totalGrievances = count($employeeGrievances);

        $pendingGrievances = 0;
        $resolvedGrievances = 0;
        $escalatedGrievances = 0;

        foreach ($employeeGrievances as $grievance) {
            $status = strtolower($grievance['status'] ?? '');

            if ($status === 'pending') {
                $pendingGrievances++;
            }

            if ($status === 'resolved') {
                $resolvedGrievances++;
            }

            if (
                !empty($grievance['escalation_level']) ||
                $status === 'escalated'
            ) {
                $escalatedGrievances++;
            }
        }
        ?>

        <!-- PAGE HEADER -->
        <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:22px;
        gap:15px;
        flex-wrap:wrap;
    ">

            <div>
                <h2 style="
                margin:0;
                color:#111827;
                font-size:20px;
                font-weight:700;
            ">
                    My Grievances
                </h2>

                <p style="
                margin:5px 0 0;
                color:#6b7280;
                font-size:12px;
            ">
                    View and monitor your submitted workplace grievances.
                </p>
            </div>

            <button type="button" data-bs-toggle="modal" data-bs-target="#submitGrievanceModal" style="
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:10px 16px;
        border:0;
        border-radius:9px;
        background:#2563eb;
        color:#fff;
        font-size:12px;
        font-weight:600;
        cursor:pointer;
    ">
                <i class="fas fa-plus"></i>
                Submit Grievance
            </button>

        </div>


        <!-- STATISTICS -->
        <div style="
        display:grid;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        gap:14px;
        margin-bottom:22px;
    ">

            <!-- TOTAL -->
            <div style="
            padding:16px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            background:#fff;
        ">
                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
            ">
                    <span style="
                    color:#6b7280;
                    font-size:11px;
                    font-weight:500;
                ">
                        Total Grievances
                    </span>

                    <i class="fas fa-file-alt" style="
                    color:#2563eb;
                    font-size:14px;
                "></i>
                </div>

                <div style="
                margin-top:8px;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    <?= $totalGrievances ?>
                </div>
            </div>


            <!-- PENDING -->
            <div style="
            padding:16px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            background:#fff;
        ">
                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
            ">
                    <span style="
                    color:#6b7280;
                    font-size:11px;
                    font-weight:500;
                ">
                        Pending
                    </span>

                    <i class="fas fa-clock" style="
                    color:#f59e0b;
                    font-size:14px;
                "></i>
                </div>

                <div style="
                margin-top:8px;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    <?= $pendingGrievances ?>
                </div>
            </div>


            <!-- RESOLVED -->
            <div style="
            padding:16px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            background:#fff;
        ">
                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
            ">
                    <span style="
                    color:#6b7280;
                    font-size:11px;
                    font-weight:500;
                ">
                        Resolved
                    </span>

                    <i class="fas fa-check-circle" style="
                    color:#16a34a;
                    font-size:14px;
                "></i>
                </div>

                <div style="
                margin-top:8px;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    <?= $resolvedGrievances ?>
                </div>
            </div>


            <!-- ESCALATED -->
            <div style="
            padding:16px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            background:#fff;
        ">
                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
            ">
                    <span style="
                    color:#6b7280;
                    font-size:11px;
                    font-weight:500;
                ">
                        Escalated
                    </span>

                    <i class="fas fa-level-up-alt" style="
                    color:#dc2626;
                    font-size:14px;
                "></i>
                </div>

                <div style="
                margin-top:8px;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    <?= $escalatedGrievances ?>
                </div>
            </div>

        </div>


        <!-- GRIEVANCE HISTORY -->
        <div style="
        border:1px solid #e5e7eb;
        border-radius:12px;
        background:#fff;
        overflow:hidden;
    ">

            <!-- TABLE HEADER -->
            <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:17px 18px;
            border-bottom:1px solid #f1f5f9;
        ">

                <div>
                    <h3 style="
                    margin:0;
                    color:#111827;
                    font-size:13px;
                    font-weight:700;
                ">
                        Grievance History
                    </h3>

                    <p style="
                    margin:4px 0 0;
                    color:#9ca3af;
                    font-size:10px;
                ">
                        Your submitted grievances and their current status.
                    </p>
                </div>

            </div>


            <?php if (!empty($employeeGrievances)): ?>

                <div style="
                width:100%;
                overflow-x:auto;
            ">

                    <table style="
                    width:100%;
                    min-width:850px;
                    border-collapse:collapse;
                ">

                        <thead>
                            <tr style="
                            background:#f8fafc;
                        ">

                                <th style="
                                padding:11px 14px;
                                text-align:left;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    SUBJECT
                                </th>

                                <th style="
                                padding:11px 14px;
                                text-align:left;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    CATEGORY
                                </th>

                                <th style="
                                padding:11px 14px;
                                text-align:left;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    DATE
                                </th>

                                <th style="
                                padding:11px 14px;
                                text-align:left;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    PRIORITY
                                </th>

                                <th style="
                                padding:11px 14px;
                                text-align:left;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    STATUS
                                </th>

                                <th style="
                                padding:11px 14px;
                                text-align:center;
                                color:#6b7280;
                                font-size:9px;
                                font-weight:600;
                            ">
                                    ACTION
                                </th>

                            </tr>
                        </thead>


                        <tbody>

                            <?php foreach ($employeeGrievances as $grievance): ?>

                                <?php
                                $status = strtolower(
                                    $grievance['status'] ?? 'pending'
                                );

                                $priority = strtolower(
                                    $grievance['priority'] ?? 'low'
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
                                    'escalated' => [
                                        'background:#fef2f2;',
                                        'color:#dc2626;'
                                    ],
                                    default => [
                                        'background:#f3f4f6;',
                                        'color:#6b7280;'
                                    ]
                                };

                                $priorityStyle = match ($priority) {
                                    'high' => [
                                        'background:#fef2f2;',
                                        'color:#dc2626;'
                                    ],
                                    'medium' => [
                                        'background:#fff7ed;',
                                        'color:#ea580c;'
                                    ],
                                    'low' => [
                                        'background:#f0fdf4;',
                                        'color:#16a34a;'
                                    ],
                                    default => [
                                        'background:#f3f4f6;',
                                        'color:#6b7280;'
                                    ]
                                };
                                ?>

                                <tr>

                                    <!-- SUBJECT -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <div style="
                                        color:#111827;
                                        font-size:11px;
                                        font-weight:600;
                                    ">
                                            <?= htmlspecialchars(
                                                $grievance['subject'] ?? '—'
                                            ) ?>
                                        </div>

                                        <?php if (!empty($grievance['anonymous'])): ?>

                                            <div style="
                                            margin-top:4px;
                                            color:#9ca3af;
                                            font-size:9px;
                                        ">
                                                <i class="fas fa-user-secret"></i>
                                                Anonymous
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CATEGORY -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                    color:#4b5563;
                                    font-size:10px;
                                ">
                                        <?= htmlspecialchars(
                                            $grievance['category'] ?? '—'
                                        ) ?>
                                    </td>


                                    <!-- DATE -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                    color:#4b5563;
                                    font-size:10px;
                                ">
                                        <?= !empty($grievance['created_at'])
                                            ? htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $grievance['created_at']
                                                    )
                                                )
                                            )
                                            : '—'
                                            ?>
                                    </td>


                                    <!-- PRIORITY -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <span style="
                                        display:inline-block;
                                        padding:5px 9px;
                                        border-radius:20px;
                                        font-size:9px;
                                        font-weight:700;
                                        text-transform:capitalize;
                                        <?= $priorityStyle[0] ?>
                                        <?= $priorityStyle[1] ?>
                                    ">
                                            <?= htmlspecialchars($priority) ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <span style="
                                        display:inline-block;
                                        padding:5px 9px;
                                        border-radius:20px;
                                        font-size:9px;
                                        font-weight:700;
                                        text-transform:capitalize;
                                        <?= $statusStyle[0] ?>
                                        <?= $statusStyle[1] ?>
                                    ">
                                            <?= htmlspecialchars(
                                                str_replace('_', ' ', $status)
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- ACTION -->
                                    <td style="
                                    padding:14px;
                                    border-bottom:1px solid #f1f5f9;
                                    text-align:center;
                                ">

                                        <button type="button" class="btn btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#viewGrievanceModal<?= $grievanceId ?>" title="View Grievance"
                                            style="
                width:34px;
                height:34px;
                padding:0;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                border:1px solid #dbeafe;
                border-radius:8px;
                background:#eff6ff;
                color:#2563eb;
            ">
                                            <i class="fas fa-eye" style="font-size:12px;"></i>
                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- EMPTY STATE -->
                <div style="
                padding:55px 20px;
                text-align:center;
            ">

                    <div style="
                    width:48px;
                    height:48px;
                    margin:0 auto 12px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:50%;
                    background:#f3f4f6;
                    color:#9ca3af;
                ">
                        <i class="fas fa-inbox"></i>
                    </div>

                    <div style="
                    color:#374151;
                    font-size:12px;
                    font-weight:600;
                ">
                        No grievances found
                    </div>

                    <div style="
                    margin-top:4px;
                    color:#9ca3af;
                    font-size:10px;
                ">
                        You have not submitted any grievances yet.
                    </div>

                </div>

            <?php endif; ?>

        </div>


        <!-- VIEW GRIEVANCE MODALS -->
        <?php foreach ($employeeGrievances as $grievance): ?>

            <div class="modal fade" id="viewGrievanceModal<?= (int) $grievance['eer_grievance_id'] ?>" tabindex="-1"
                aria-hidden="true">

                <div class="modal-dialog modal-lg modal-dialog-centered">

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
                    ">

                            <div>

                                <h5 style="
                                margin:0;
                                color:#111827;
                                font-size:16px;
                                font-weight:700;
                            ">
                                    Grievance Details
                                </h5>

                                <div style="
                                margin-top:4px;
                                color:#9ca3af;
                                font-size:10px;
                            ">
                                    Grievance #<?= (int) $grievance['eer_grievance_id'] ?>
                                </div>

                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                        </div>


                        <!-- BODY -->
                        <div class="modal-body" style="
                        padding:22px;
                    ">

                            <div style="margin-bottom:18px;">

                                <label for="subject" style="
        display:block;
        margin-bottom:7px;
        color:#374151;
        font-size:12px;
        font-weight:600;
    ">
                                    Subject
                                </label>

                                <input type="text" id="subject" name="subject" readonly
                                    placeholder="Subject will be generated from your description" style="
            width:100%;
            height:44px;
            padding:0 13px;
            border:1px solid #d1d5db;
            border-radius:9px;
            background:#f9fafb;
            color:#374151;
            font-size:12px;
            outline:none;
            box-sizing:border-box;
        ">

                                <div style="
        margin-top:5px;
        color:#9ca3af;
        font-size:10px;
    ">
                                    The subject is automatically generated from your description.
                                </div>

                            </div>


                            <!-- CATEGORY + PRIORITY -->
                            <div style="
                            display:grid;
                            grid-template-columns:1fr 1fr;
                            gap:12px;
                            margin-bottom:18px;
                        ">

                                <div style="
                                padding:12px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">

                                    <div style="
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                        Category
                                    </div>

                                    <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                        <?= htmlspecialchars(
                                            $grievance['category'] ?? '—'
                                        ) ?>
                                    </div>

                                </div>


                                <div style="
                                padding:12px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">

                                    <div style="
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                        Priority
                                    </div>

                                    <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                    text-transform:capitalize;
                                ">
                                        <?= htmlspecialchars($priority) ?>
                                    </div>

                                </div>

                            </div>


                            <!-- STATUS + DATE -->
                            <div style="
                            display:grid;
                            grid-template-columns:1fr 1fr;
                            gap:12px;
                            margin-bottom:18px;
                        ">

                                <div style="
                                padding:12px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">

                                    <div style="
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                        Status
                                    </div>

                                    <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                    text-transform:capitalize;
                                ">
                                        <?= htmlspecialchars(
                                            str_replace('_', ' ', $status)
                                        ) ?>
                                    </div>

                                </div>


                                <div style="
                                padding:12px;
                                border:1px solid #f1f5f9;
                                border-radius:9px;
                                background:#fafafa;
                            ">

                                    <div style="
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                        Date Submitted
                                    </div>

                                    <div style="
                                    margin-top:4px;
                                    color:#374151;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                        <?= !empty($grievance['created_at'])
                                            ? htmlspecialchars(
                                                date(
                                                    'F d, Y h:i A',
                                                    strtotime(
                                                        $grievance['created_at']
                                                    )
                                                )
                                            )
                                            : '—'
                                            ?>
                                    </div>

                                </div>

                            </div>


                            <!-- DESCRIPTION -->
                            <?php if (!empty($grievance['description'])): ?>

                                <div style="margin-bottom:18px;">

                                    <label for="description" style="
        display:block;
        margin-bottom:7px;
        color:#374151;
        font-size:12px;
        font-weight:600;
    ">
                                        Description <span style="color:#dc2626;">*</span>
                                    </label>

                                    <textarea id="description" name="description" required rows="6"
                                        placeholder="Please describe what happened, including relevant details..." style="
            width:100%;
            padding:12px 13px;
            border:1px solid #d1d5db;
            border-radius:9px;
            background:#fff;
            color:#374151;
            font-size:12px;
            line-height:1.6;
            resize:vertical;
            box-sizing:border-box;
            outline:none;
        "></textarea>

                                </div>

                            <?php endif; ?>


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
                                    padding:12px 13px;
                                    border:1px solid #e5e7eb;
                                    border-radius:9px;
                                    background:#f8fafc;
                                    color:#374151;
                                    font-size:11px;
                                    line-height:1.6;
                                ">
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $grievance['resolution_of_complaint']
                                            )
                                        ) ?>
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
                                    padding:12px 13px;
                                    border:1px solid #e5e7eb;
                                    border-radius:9px;
                                    background:#f8fafc;
                                    color:#374151;
                                    font-size:11px;
                                    line-height:1.6;
                                ">
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $grievance['action_taken']
                                            )
                                        ) ?>
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
                                            <?= htmlspecialchars(
                                                $grievance['escalation_level']
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                    <?php if (!empty($grievance['escalation_reason'])): ?>

                                        <div style="
                                        color:#374151;
                                        font-size:10px;
                                    ">
                                            <strong>Reason:</strong>
                                            <?= htmlspecialchars(
                                                $grievance['escalation_reason']
                                            ) ?>
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

                                    <a href="<?= htmlspecialchars($grievance['attachment_path']) ?>" target="_blank" style="
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


                        <!-- FOOTER -->
                        <div class="modal-footer" style="
                        padding:14px 22px;
                        border-top:1px solid #f1f5f9;
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

    </section>

</div>