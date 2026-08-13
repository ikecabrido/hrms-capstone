<div class="employee-dashboard">
    <section class="dashboard-welcome" id="leaveRequestWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE LEAVE REQUEST
            </span>

            <h1 class="welcome-title">Leave Request</h1>

            <p class="welcome-description">
                Submit and manage your leave requests, view request history,
                and check the approval status of your submitted leaves.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration">
            <i class="fas fa-calendar-minus"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="
    width:100%;
    max-width:100%;
    padding:0;
    margin:0;
    box-sizing:border-box;
">

        <!-- HEADER -->
        <div style="
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin:0 0 14px;
    flex-wrap:wrap;
">

            <!-- LEFT: TITLE -->
            <div style="
        display:flex;
        align-items:center;
        gap:10px;
        min-width:0;
    ">

                <div style="
            width:38px;
            height:38px;
            min-width:38px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
            background:#eff6ff;
            color:#2563eb;
            font-size:16px;
        ">
                    <i class="fas fa-calendar-alt"></i>
                </div>

                <div style="min-width:0;">

                    <h3 style="
                margin:0;
                font-size:18px;
                line-height:1.3;
                font-weight:700;
                color:#111827;
            ">
                        Leave History
                    </h3>

                    <p style="
                margin:2px 0 0;
                color:#6b7280;
                font-size:12px;
                line-height:1.4;
            ">
                        View your submitted leave requests.
                    </p>

                </div>

            </div>


            <!-- RIGHT: ACTIONS -->
            <div style="
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    ">
                <!-- RECORD COUNT -->
                <span style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            padding:7px 10px;
            border-radius:18px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            color:#374151;
            font-size:11px;
            font-weight:600;
            white-space:nowrap;
        ">
                    <i class="fas fa-list-ul" style="color:#2563eb;"></i>

                    <?= count($leaveHistory ?? []) ?> Records
                </span>
                <!-- CREATE LEAVE REQUEST -->
                <button type="button" data-bs-toggle="modal" data-bs-target="#submitLeaveModal" style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:7px;
                padding:8px 13px;
                border:0;
                border-radius:9px;
                background:#2563eb;
                color:#fff;
                font-size:11px;
                font-weight:600;
                line-height:1;
                white-space:nowrap;
                cursor:pointer;
                box-shadow:0 2px 5px rgba(37,99,235,.20);
                transition:all .2s ease;
            ">
                    <span class="text-[14px]">
                        <i class="fas fa-plus"></i>
                        Create Leave Request
                    </span>
                </button>
            </div>

        </div>


        <?php if (!empty($leaveHistory)): ?>

            <!-- TABLE CONTAINER -->
            <div style="
            width:100%;
            max-width:100%;
            overflow-x:auto;
            overflow-y:hidden;
            -webkit-overflow-scrolling:touch;
            overscroll-behavior-x:contain;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#fff;
            box-shadow:0 3px 12px rgba(15,23,42,.04);
            scrollbar-width:thin;
            scrollbar-color:#cbd5e1 #f8fafc;
        ">

                <table style="
                width:100%;
                min-width:900px;
                border-collapse:separate;
                border-spacing:0;
                font-size:12px;
            ">

                    <!-- TABLE HEADER -->
                    <thead>

                        <tr style="
                        background:#f8fafc;
                    ">

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="fas fa-tag" style="margin-right:4px;color:#2563eb;"></i>
                                Leave Type
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="fas fa-calendar-day" style="margin-right:4px;color:#2563eb;"></i>
                                Start Date
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="fas fa-calendar-check" style="margin-right:4px;color:#2563eb;"></i>
                                End Date
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="fas fa-comment-alt" style="margin-right:4px;color:#2563eb;"></i>
                                Reason
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:center;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="fas fa-info-circle" style="margin-right:4px;color:#2563eb;"></i>
                                Status
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                Remarks
                            </th>

                            <th style="
                            padding:11px 13px;
                            text-align:left;
                            color:#64748b;
                            font-size:10px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.04em;
                            border-bottom:1px solid #e5e7eb;
                            white-space:nowrap;
                        ">
                                <i class="far fa-clock" style="margin-right:4px;color:#2563eb;"></i>
                                Submitted
                            </th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->
                    <tbody>

                        <?php foreach ($leaveHistory as $leave): ?>

                            <?php

                            $status = strtoupper(
                                trim($leave['status'] ?? 'PENDING')
                            );

                            $statusConfig = match ($status) {

                                'APPROVED' => [
                                    'background:#dcfce7;color:#166534;',
                                    'fa-check-circle',
                                    'Approved'
                                ],

                                'REJECTED' => [
                                    'background:#fee2e2;color:#991b1b;',
                                    'fa-times-circle',
                                    'Rejected'
                                ],

                                'PENDING' => [
                                    'background:#fef3c7;color:#92400e;',
                                    'fa-clock',
                                    'Pending'
                                ],

                                'CANCELLED' => [
                                    'background:#f3f4f6;color:#4b5563;',
                                    'fa-ban',
                                    'Cancelled'
                                ],

                                default => [
                                    'background:#eff6ff;color:#1d4ed8;',
                                    'fa-info-circle',
                                    ucfirst(strtolower($status))
                                ]

                            };

                            ?>

                            <tr style="
                            transition:background .15s ease;
                        ">

                                <!-- LEAVE TYPE -->
                                <td style="
                                padding:12px 13px;
                                color:#111827;
                                font-weight:600;
                                border-bottom:1px solid #f1f5f9;
                                white-space:nowrap;
                            ">

                                    <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:8px;
                                ">

                                        <span style="
                                        width:28px;
                                        height:28px;
                                        min-width:28px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        border-radius:8px;
                                        background:#eff6ff;
                                        color:#2563eb;
                                        font-size:11px;
                                    ">
                                            <i class="fas fa-file-alt"></i>
                                        </span>

                                        <?= htmlspecialchars(
                                            $leave['leave_type'] ?? '-'
                                        ) ?>

                                    </div>

                                </td>


                                <!-- START DATE -->
                                <td style="
                                padding:12px 13px;
                                color:#374151;
                                border-bottom:1px solid #f1f5f9;
                                white-space:nowrap;
                            ">

                                    <i class="far fa-calendar" style="color:#94a3b8;margin-right:5px;">
                                    </i>

                                    <?= !empty($leave['start_date'])
                                        ? date('M d, Y', strtotime($leave['start_date']))
                                        : '-' ?>

                                </td>


                                <!-- END DATE -->
                                <td style="
                                padding:12px 13px;
                                color:#374151;
                                border-bottom:1px solid #f1f5f9;
                                white-space:nowrap;
                            ">

                                    <i class="far fa-calendar-check" style="color:#94a3b8;margin-right:5px;">
                                    </i>

                                    <?= !empty($leave['end_date'])
                                        ? date('M d, Y', strtotime($leave['end_date']))
                                        : '-' ?>

                                </td>


                                <!-- REASON -->
                                <td style="
                                padding:12px 13px;
                                color:#4b5563;
                                border-bottom:1px solid #f1f5f9;
                                min-width:200px;
                                max-width:260px;
                            ">

                                    <div style="
                                    line-height:1.45;
                                    white-space:normal;
                                    overflow-wrap:anywhere;
                                ">
                                        <?= htmlspecialchars(
                                            $leave['reason'] ?? '-'
                                        ) ?>
                                    </div>

                                </td>


                                <!-- STATUS -->
                                <td style="
                                padding:12px 13px;
                                text-align:center;
                                border-bottom:1px solid #f1f5f9;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    justify-content:center;
                                    gap:5px;
                                    padding:5px 9px;
                                    border-radius:18px;
                                    font-size:10px;
                                    font-weight:700;
                                    white-space:nowrap;
                                    <?= $statusConfig[0] ?>
                                ">

                                        <i class="fas <?= $statusConfig[1] ?>"></i>

                                        <?= htmlspecialchars(
                                            $statusConfig[2]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- REMARKS -->
                                <td style="
                                padding:12px 13px;
                                color:#6b7280;
                                border-bottom:1px solid #f1f5f9;
                                min-width:170px;
                                max-width:230px;
                            ">

                                    <div style="
                                    line-height:1.45;
                                    white-space:normal;
                                    overflow-wrap:anywhere;
                                ">
                                        <?= htmlspecialchars(
                                            $leave['remarks'] ?? '—'
                                        ) ?>
                                    </div>

                                </td>


                                <!-- SUBMITTED -->
                                <td style="
                                padding:12px 13px;
                                color:#6b7280;
                                border-bottom:1px solid #f1f5f9;
                                white-space:nowrap;
                            ">

                                    <i class="far fa-clock" style="color:#94a3b8;margin-right:5px;">
                                    </i>

                                    <?= !empty($leave['created_at'])
                                        ? date('M d, Y h:i A', strtotime($leave['created_at']))
                                        : '-' ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- SCROLL INDICATOR -->
            <div style="
            display:flex;
            align-items:center;
            justify-content:center;
            gap:5px;
            margin-top:7px;
            color:#94a3b8;
            font-size:10px;
        ">
                <i class="fas fa-arrows-alt-h"></i>
                <span>Swipe or scroll horizontally to view more</span>
            </div>


        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            padding:40px 20px;
            box-sizing:border-box;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#fff;
            text-align:center;
            box-shadow:0 3px 12px rgba(15,23,42,.04);
        ">

                <div style="
                width:56px;
                height:56px;
                margin:0 auto 12px;
                border-radius:15px;
                background:#eff6ff;
                color:#2563eb;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:22px;
            ">
                    <i class="fas fa-calendar-times"></i>
                </div>

                <h5 style="
                margin:0 0 4px;
                color:#374151;
                font-size:15px;
                font-weight:700;
            ">
                    No Leave Records
                </h5>

                <p style="
                margin:0;
                color:#9ca3af;
                font-size:12px;
            ">
                    You don't have any leave requests yet.
                </p>

            </div>

        <?php endif; ?>

    </section>
</div>