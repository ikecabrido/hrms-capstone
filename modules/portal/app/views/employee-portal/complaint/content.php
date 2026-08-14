<div class="employee-dashboard">

    <section class="dashboard-welcome" id="complaintWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE RELATIONS
            </span>

            <h1 class="welcome-title">
                Employee Complaints
            </h1>

            <p class="welcome-description">
                Submit workplace concerns and monitor the status of your complaints.
                Review your complaint history, case details, and updates from HR.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-comment-alt"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="
    width:100%;
    box-sizing:border-box;
">

        <!-- HEADER -->
        <div style="
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:15px;
        margin-bottom:18px;
        padding-bottom:15px;
        border-bottom:1px solid #e5e7eb;
        flex-wrap:wrap;
    ">

            <div>
                <span style="
                display:block;
                margin-bottom:5px;
                color:#2563eb;
                font-size:9px;
                font-weight:700;
                letter-spacing:.08em;
            ">
                    EMPLOYEE RELATIONS
                </span>

                <h2 style="
                margin:0;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    Complaint History
                </h2>

                <p style="
                margin:5px 0 0;
                color:#6b7280;
                font-size:11px;
            ">
                    View and monitor the complaints you have submitted to HR.
                </p>
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="button" data-bs-toggle="modal" data-bs-target="#complaintModal" style="
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        padding:9px 13px;
        border:1px solid #2563eb;
        border-radius:9px;
        background:#2563eb;
        color:#fff;
        font-size:10px;
        font-weight:600;
        white-space:nowrap;
        cursor:pointer;
    ">
                <i class="fas fa-plus"></i>
                Submit a Complaint
            </button>

        </div>


        <?php if (!empty($complaintHistory)): ?>

            <!-- SUMMARY -->
            <div style="
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:14px;
        ">

                <div style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:7px 11px;
                border:1px solid #e5e7eb;
                border-radius:20px;
                background:#f8fafc;
                color:#374151;
                font-size:10px;
                font-weight:600;
            ">
                    <i class="fas fa-file-alt" style="color:#2563eb;"></i>

                    <?= count($complaintHistory) ?>

                    <?= count($complaintHistory) === 1
                        ? 'Complaint'
                        : 'Complaints' ?>
                </div>

            </div>


            <!-- RESPONSIVE TABLE -->
            <div style="
            width:100%;
            max-width:100%;
            overflow-x:auto;
            overflow-y:hidden;
            -webkit-overflow-scrolling:touch;
            scrollbar-width:thin;
            scrollbar-color:#cbd5e1 #f8fafc;

            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-sizing:border-box;

            box-shadow:0 4px 16px rgba(15,23,42,.04);
        ">

                <table style="
                width:100%;
                min-width:900px;
                border-collapse:separate;
                border-spacing:0;
                table-layout:auto;
                font-size:11px;
                white-space:nowrap;
            ">

                    <thead>
                        <tr style="
                        background:#f8fafc;
                    ">

                            <th style="
                            padding:12px 14px;
                            text-align:left;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Complaint ID
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:left;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Complaint
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:left;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Type
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:left;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Respondent
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:left;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Date
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:center;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Severity
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:center;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Status
                            </th>

                            <th style="
                            padding:12px 14px;
                            text-align:center;
                            color:#6b7280;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                        ">
                                Action
                            </th>

                        </tr>
                    </thead>


                    <tbody>

                        <?php foreach ($complaintHistory as $complaint): ?>

                            <?php
                            $status = strtolower(
                                $complaint['status'] ?? 'submitted'
                            );

                            $statusStyle = match ($status) {
                                'resolved' => [
                                    '#f0fdf4',
                                    '#15803d'
                                ],

                                'rejected' => [
                                    '#fef2f2',
                                    '#dc2626'
                                ],

                                'under_review' => [
                                    '#eff6ff',
                                    '#2563eb'
                                ],

                                'investigation' => [
                                    '#fffbeb',
                                    '#d97706'
                                ],

                                default => [
                                    '#f8fafc',
                                    '#64748b'
                                ]
                            };

                            $severity = strtolower(
                                $complaint['severity'] ?? 'medium'
                            );

                            $severityStyle = match ($severity) {
                                'high', 'critical' => [
                                    '#fef2f2',
                                    '#dc2626'
                                ],

                                'low' => [
                                    '#f0fdf4',
                                    '#15803d'
                                ],

                                default => [
                                    '#fffbeb',
                                    '#d97706'
                                ]
                            };
                            ?>

                            <tr style="
                            transition:.2s ease;
                        " onmouseover="
                            this.style.background='#f8fafc';
                        " onmouseout="
                            this.style.background='#fff';
                        ">

                                <!-- ID -->
                                <td style="
                                padding:13px 14px;
                                border-bottom:1px solid #f1f5f9;
                            ">
                                    <span style="
                                    color:#2563eb;
                                    font-weight:700;
                                ">
                                        <?= htmlspecialchars(
                                            $complaint['incident_id']
                                            ?? '—'
                                        ) ?>
                                    </span>
                                </td>


                                <!-- TITLE -->
                                <td style="
                                padding:13px 14px;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <div style="
                                    color:#111827;
                                    font-weight:600;
                                    max-width:220px;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                    white-space:nowrap;
                                ">
                                        <?= htmlspecialchars(
                                            $complaint['title']
                                            ?? 'Untitled Complaint'
                                        ) ?>
                                    </div>

                                    <div style="
                                    margin-top:3px;
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                        <?= htmlspecialchars(
                                            $complaint['location']
                                            ?? 'No location'
                                        ) ?>
                                    </div>

                                </td>


                                <!-- TYPE -->
                                <td style="
                                padding:13px 14px;
                                border-bottom:1px solid #f1f5f9;
                                color:#4b5563;
                            ">
                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $complaint['incident_type']
                                            ?? '—'
                                        )
                                    ) ?>
                                </td>


                                <!-- RESPONDENT -->
                                <td style="
                                padding:13px 14px;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <div style="
                                    color:#374151;
                                    font-weight:600;
                                ">
                                        <?= htmlspecialchars(
                                            $complaint['respondent_name']
                                            ?? 'Not specified'
                                        ) ?>
                                    </div>

                                    <?php if (
                                        !empty(
                                        $complaint['respondent_employee_id']
                                    )
                                    ): ?>

                                        <div style="
                                        margin-top:3px;
                                        color:#9ca3af;
                                        font-size:9px;
                                    ">
                                            <?= htmlspecialchars(
                                                $complaint[
                                                    'respondent_employee_id'
                                                ]
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- DATE -->
                                <td style="
                                padding:13px 14px;
                                border-bottom:1px solid #f1f5f9;
                                color:#6b7280;
                            ">
                                    <?= !empty(
                                        $complaint['incident_date']
                                    )
                                        ? date(
                                            'M d, Y',
                                            strtotime(
                                                $complaint['incident_date']
                                            )
                                        )
                                        : '—' ?>
                                </td>


                                <!-- SEVERITY -->
                                <td style="
                                padding:13px 14px;
                                text-align:center;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    padding:4px 8px;
                                    border-radius:20px;

                                    background:<?= $severityStyle[0] ?>;
                                    color:<?= $severityStyle[1] ?>;

                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                        <?= htmlspecialchars($severity) ?>
                                    </span>

                                </td>


                                <!-- STATUS -->
                                <td style="
                                padding:13px 14px;
                                text-align:center;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;

                                    padding:4px 8px;
                                    border-radius:20px;

                                    background:<?= $statusStyle[0] ?>;
                                    color:<?= $statusStyle[1] ?>;

                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">

                                        <i class="fas fa-circle" style="font-size:4px;"></i>

                                        <?= htmlspecialchars(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $status
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACTION -->
                                <td style="
                                padding:13px 14px;
                                text-align:center;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <button type="button" data-bs-toggle="modal"
                                        data-bs-target="#viewComplaintModal<?= (int) $complaint['id'] ?>" style="
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        padding:7px 11px;
        border:1px solid #e5e7eb;
        border-radius:7px;
        background:#fff;
        color:#374151;
        font-size:11px;
        font-weight:600;
        cursor:pointer;
    ">
                                        <i class="fa-solid fa-eye" style="font-size:10px;"></i>
                                        View
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
            width:100%;
            padding:60px 20px;
            box-sizing:border-box;

            text-align:center;

            border:1px solid #e5e7eb;
            border-radius:14px;

            background:#fff;

            box-shadow:0 4px 16px rgba(15,23,42,.04);
        ">

                <div style="
                width:64px;
                height:64px;
                margin:0 auto 15px;

                display:flex;
                align-items:center;
                justify-content:center;

                border-radius:16px;

                background:#eff6ff;
                color:#93c5fd;

                font-size:25px;
            ">
                    <i class="fas fa-file-alt"></i>
                </div>

                <h3 style="
                margin:0 0 6px;
                color:#374151;
                font-size:15px;
                font-weight:700;
            ">
                    No Complaints Found
                </h3>

                <p style="
                margin:0 auto 18px;
                max-width:400px;
                color:#9ca3af;
                font-size:11px;
                line-height:1.6;
            ">
                    You have not submitted any employee complaints yet.
                </p>

                <button type="button" data-bs-toggle="modal" data-bs-target="#complaintModal" style="
        display:inline-flex;
        align-items:center;
        gap:7px;
        padding:9px 13px;
        border-radius:9px;
        border:0;
        background:#2563eb;
        color:#fff;
        text-decoration:none;
        font-size:10px;
        font-weight:600;
        cursor:pointer;
    ">
                    <i class="fas fa-plus"></i>
                    Submit a Complaint
                </button>

            </div>

        <?php endif; ?>

    </section>

</div>