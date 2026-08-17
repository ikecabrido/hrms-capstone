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

    <section style="width:100%;margin-top:20px;">

        <?php
        $latestLeave = null;

        foreach ($leaveHistory as $leave) {
            if (strtoupper(trim($leave['status'] ?? '')) !== 'CANCELLED') {
                $latestLeave = $leave;
                break;
            }
        }

        if ($latestLeave) {
            $status = strtoupper(trim($latestLeave['status'] ?? 'PENDING'));

            $statusConfig = match ($status) {
                'APPROVED' => [
                    'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;',
                    'fa-check-circle',
                    'Approved'
                ],
                'REJECTED' => [
                    'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;',
                    'fa-times-circle',
                    'Rejected'
                ],
                'CANCELLED' => [
                    'background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;',
                    'fa-ban',
                    'Cancelled'
                ],
                default => [
                    'background:#fffbeb;color:#b45309;border:1px solid #fde68a;',
                    'fa-clock',
                    'Pending'
                ]
            };

            $startDate = !empty($latestLeave['start_date'])
                ? strtotime($latestLeave['start_date'])
                : false;

            $endDate = !empty($latestLeave['end_date'])
                ? strtotime($latestLeave['end_date'])
                : false;

            $businessDays = 0;

            if ($startDate && $endDate) {
                for (
                    $date = $startDate;
                    $date <= $endDate;
                    $date = strtotime('+1 day', $date)
                ) {
                    if (date('N', $date) < 6) {
                        $businessDays++;
                    }
                }
            }

            $requestId = $latestLeave['leave_request_id']
                ?? $latestLeave['id']
                ?? $latestLeave['lr_id']
                ?? null;

            $managerDone = in_array($status, ['APPROVED', 'REJECTED']);
            $hrDone = $status === 'APPROVED';
        }
        ?>

        <!-- SECTION HEADER -->
        <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:12px;
    ">
            <div>
                <h3 style="
                margin:0;
                color:#111827;
                font-size:16px;
                font-weight:700;
            ">
                    Leave Request Status
                </h3>

                <p style="
                margin:3px 0 0;
                color:#94a3b8;
                font-size:11px;
            ">
                    Track your latest leave request and approval progress.
                </p>
            </div>
        </div>


        <?php if ($latestLeave): ?>

            <!-- MAIN CARD -->
            <div style="
            width:100%;
            border:1px solid #e5e7eb;
            border-radius:16px;
            background:#fff;
            box-shadow:0 4px 16px rgba(15,23,42,.06);
            overflow:hidden;
        ">

                <!-- CARD TOP -->
                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                padding:17px 20px;
                background:linear-gradient(135deg,#f8fafc,#f1f5f9);
                border-bottom:1px solid #e5e7eb;
            ">

                    <div style="
                    display:flex;
                    align-items:center;
                    gap:11px;
                ">

                        <div style="
                        width:40px;
                        height:40px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:11px;
                        background:#dbeafe;
                        color:#2563eb;
                        font-size:15px;
                    ">
                            <i class="fas fa-calendar-check"></i>
                        </div>

                        <div>
                            <div style="
                            color:#111827;
                            font-size:13px;
                            font-weight:700;
                        ">
                                Latest Leave Request
                            </div>

                            <div style="
                            margin-top:3px;
                            color:#94a3b8;
                            font-size:10px;
                        ">
                                <?= $requestId
                                    ? 'Request #' . htmlspecialchars($requestId)
                                    : 'Leave request'
                                    ?>
                            </div>
                        </div>

                    </div>

                    <!-- STATUS -->
                    <span style="
                    display:inline-flex;
                    align-items:center;
                    gap:6px;
                    padding:6px 11px;
                    border-radius:20px;
                    font-size:10px;
                    font-weight:700;
                    <?= $statusConfig[0] ?>
                ">
                        <i class="fas <?= $statusConfig[1] ?>"></i>
                        <?= htmlspecialchars($statusConfig[2]) ?>
                    </span>

                </div>


                <!-- CARD BODY -->
                <div style="padding:20px;">

                    <!-- REQUEST INFORMATION -->
                    <div style="
                    display:grid;
                    grid-template-columns:repeat(4,minmax(0,1fr));
                    gap:10px;
                ">

                        <!-- TYPE -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:5px;
                            margin-bottom:6px;
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="fas fa-tag"></i>
                                Leave Type
                            </div>

                            <div style="
                            color:#1f2937;
                            font-size:12px;
                            font-weight:700;
                        ">
                                <?= htmlspecialchars(
                                    $latestLeave['leave_type_name'] ?? '-'
                                ) ?>
                            </div>
                        </div>


                        <!-- DATE -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:5px;
                            margin-bottom:6px;
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="far fa-calendar"></i>
                                Date Range
                            </div>

                            <div style="
                            color:#1f2937;
                            font-size:12px;
                            font-weight:700;
                        ">
                                <?php if ($startDate && $endDate): ?>

                                    <?= date('M d, Y', $startDate) ?>

                                    <?php if (
                                        date('Y-m-d', $startDate) !==
                                        date('Y-m-d', $endDate)
                                    ): ?>
                                        <span style="color:#94a3b8;">–</span>
                                        <?= date('M d, Y', $endDate) ?>
                                    <?php endif; ?>

                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- DAYS -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:5px;
                            margin-bottom:6px;
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="fas fa-business-time"></i>
                                Duration
                            </div>

                            <div style="
                            color:#1f2937;
                            font-size:12px;
                            font-weight:700;
                        ">
                                <?= $businessDays ?>
                                <?= $businessDays == 1 ? 'Business Day' : 'Business Days' ?>
                            </div>
                        </div>


                        <!-- SUBMITTED -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:5px;
                            margin-bottom:6px;
                            color:#94a3b8;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="far fa-clock"></i>
                                Submitted
                            </div>

                            <div style="
                            color:#1f2937;
                            font-size:11px;
                            font-weight:700;
                        ">
                                <?= !empty($latestLeave['date_submitted'])
                                    ? date(
                                        'M d, Y',
                                        strtotime($latestLeave['date_submitted'])
                                    )
                                    : '-' ?>
                            </div>
                        </div>

                    </div>


                    <!-- PROGRESS -->
                    <div style="
                    margin-top:24px;
                    padding:17px;
                    border:1px solid #eef2f7;
                    border-radius:12px;
                    background:#fafbfc;
                ">

                        <div style="
                        margin-bottom:18px;
                        color:#374151;
                        font-size:11px;
                        font-weight:700;
                    ">
                            Approval Progress
                        </div>

                        <div style="
                        display:flex;
                        width:100%;
                    ">

                            <!-- SUBMITTED -->
                            <div style="
                            flex:1;
                            position:relative;
                            text-align:center;
                        ">

                                <div style="
                                width:30px;
                                height:30px;
                                margin:auto;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:50%;
                                background:#2563eb;
                                color:#fff;
                                font-size:10px;
                                position:relative;
                                z-index:2;
                                box-shadow:0 0 0 4px #dbeafe;
                            ">
                                    <i class="fas fa-check"></i>
                                </div>

                                <div style="
                                margin-top:9px;
                                color:#111827;
                                font-size:10px;
                                font-weight:700;
                            ">
                                    Submitted
                                </div>

                                <div style="
                                margin-top:3px;
                                color:#94a3b8;
                                font-size:9px;
                            ">
                                    <?= !empty($latestLeave['date_submitted'])
                                        ? date(
                                            'M d, Y',
                                            strtotime($latestLeave['date_submitted'])
                                        )
                                        : '-' ?>
                                </div>

                                <div style="
                                position:absolute;
                                top:14px;
                                left:50%;
                                width:100%;
                                height:2px;
                                background:<?= $managerDone
                                    ? '#2563eb'
                                    : '#e2e8f0' ?>;
                                z-index:1;
                            "></div>

                            </div>


                            <!-- MANAGER -->
                            <div style="
                            flex:1;
                            position:relative;
                            text-align:center;
                        ">

                                <div style="
                                width:30px;
                                height:30px;
                                margin:auto;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:50%;
                                background:<?= $managerDone
                                    ? ($status === 'REJECTED'
                                        ? '#fee2e2'
                                        : '#dcfce7')
                                    : '#fef3c7' ?>;
                                color:<?= $managerDone
                                    ? ($status === 'REJECTED'
                                        ? '#dc2626'
                                        : '#16a34a')
                                    : '#b45309' ?>;
                                border:2px solid <?= $managerDone
                                    ? ($status === 'REJECTED'
                                        ? '#ef4444'
                                        : '#22c55e')
                                    : '#f59e0b' ?>;
                                font-size:10px;
                                position:relative;
                                z-index:2;
                            ">
                                    <i class="fas <?= $managerDone
                                        ? ($status === 'REJECTED'
                                            ? 'fa-times'
                                            : 'fa-check')
                                        : 'fa-clock' ?>"></i>
                                </div>

                                <div style="
                                margin-top:9px;
                                color:#111827;
                                font-size:10px;
                                font-weight:700;
                            ">
                                    Manager Review
                                </div>

                                <div style="
                                margin-top:3px;
                                color:#94a3b8;
                                font-size:9px;
                            ">
                                    <?= $status === 'PENDING'
                                        ? 'In progress'
                                        : ($status === 'REJECTED'
                                            ? 'Rejected'
                                            : 'Completed') ?>
                                </div>

                                <div style="
                                position:absolute;
                                top:14px;
                                left:50%;
                                width:100%;
                                height:2px;
                                background:<?= $hrDone
                                    ? '#2563eb'
                                    : '#e2e8f0' ?>;
                                z-index:1;
                            "></div>

                            </div>


                            <!-- HR -->
                            <div style="
                            flex:1;
                            text-align:center;
                        ">

                                <div style="
                                width:30px;
                                height:30px;
                                margin:auto;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:50%;
                                background:<?= $hrDone
                                    ? '#dcfce7'
                                    : '#f1f5f9' ?>;
                                color:<?= $hrDone
                                    ? '#16a34a'
                                    : '#94a3b8' ?>;
                                font-size:10px;
                                position:relative;
                                z-index:2;
                            ">
                                    <i class="fas <?= $hrDone
                                        ? 'fa-check'
                                        : 'fa-lock' ?>"></i>
                                </div>

                                <div style="
                                margin-top:9px;
                                color:#64748b;
                                font-size:10px;
                                font-weight:700;
                            ">
                                    HR Finalized
                                </div>

                                <div style="
                                margin-top:3px;
                                color:#94a3b8;
                                font-size:9px;
                            ">
                                    <?= $hrDone
                                        ? 'Completed'
                                        : ($status === 'REJECTED'
                                            ? 'Not required'
                                            : 'Waiting') ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- DESCRIPTION / REASON -->
                    <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:10px;
                    margin-top:12px;
                ">

                        <!-- LEAVE DESCRIPTION -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">

                            <div style="
                            margin-bottom:6px;
                            color:#64748b;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="fas fa-info-circle" style="margin-right:4px;color:#2563eb;"></i>
                                About This Leave
                            </div>

                            <div style="
                            color:#475569;
                            font-size:11px;
                            line-height:1.5;
                        ">
                                <?= htmlspecialchars(
                                    $latestLeave['leave_type_description']
                                    ?? 'No description available.'
                                ) ?>
                            </div>

                        </div>


                        <!-- EMPLOYEE REASON -->
                        <div style="
                        padding:13px;
                        border:1px solid #eef2f7;
                        border-radius:11px;
                        background:#fafbfc;
                    ">

                            <div style="
                            margin-bottom:6px;
                            color:#64748b;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="fas fa-comment-alt" style="margin-right:4px;color:#2563eb;"></i>
                                Your Reason
                            </div>

                            <div style="
                            color:#475569;
                            font-size:11px;
                            line-height:1.5;
                        ">
                                <?= htmlspecialchars(
                                    $latestLeave['details']
                                    ?? 'No reason provided.'
                                ) ?>
                            </div>

                        </div>

                    </div>


                    <!-- REJECTION -->
                    <?php if (
                        $status === 'REJECTED' &&
                        !empty($latestLeave['reject_reason'])
                    ): ?>

                        <div style="
                        margin-top:10px;
                        padding:12px 13px;
                        border-radius:11px;
                        background:#fef2f2;
                        border:1px solid #fecaca;
                    ">

                            <div style="
                            margin-bottom:4px;
                            color:#b91c1c;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                        ">
                                <i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>
                                Rejection Reason
                            </div>

                            <div style="
                            color:#991b1b;
                            font-size:11px;
                            line-height:1.5;
                        ">
                                <?= htmlspecialchars(
                                    $latestLeave['reject_reason']
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- ACTIONS -->
                    <?php if ($status === 'PENDING' || $status === 'APPROVED'): ?>

                        <div style="
                        display:flex;
                        justify-content:flex-end;
                        gap:8px;
                        margin-top:16px;
                    ">

                            <?php if ($status === 'PENDING'): ?>

                                <form action="index.php?url=leave-cancel" method="POST" style="display:inline;">
                                    <input type="hidden" name="leave_request_id" value="<?= htmlspecialchars($requestId) ?>">

                                    <button type="submit" style="
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:8px 13px;
        border:1px solid #fecaca;
        border-radius:8px;
        background:#fff;
        color:#dc2626;
        font-size:11px;
        font-weight:600;
        cursor:pointer;
    ">
                                        <i class="fas fa-times"></i>
                                        Cancel Request
                                    </button>
                                </form>

                            <?php elseif ($status === 'APPROVED'): ?>

                                <button type="button" style="
                                display:inline-flex;
                                align-items:center;
                                gap:6px;
                                padding:8px 13px;
                                border:0;
                                border-radius:8px;
                                background:#2563eb;
                                color:#fff;
                                font-size:11px;
                                font-weight:600;
                                cursor:pointer;
                            ">
                                    <i class="fas fa-download"></i>
                                    Download Receipt
                                </button>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            padding:40px 20px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:16px;
            background:#fff;
            box-shadow:0 4px 16px rgba(15,23,42,.05);
        ">

                <div style="
                width:48px;
                height:48px;
                margin:0 auto 12px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:13px;
                background:#eff6ff;
                color:#2563eb;
                font-size:17px;
            ">
                    <i class="fas fa-calendar-plus"></i>
                </div>

                <div style="
                color:#1f2937;
                font-size:13px;
                font-weight:700;
            ">
                    No Leave Requests Yet
                </div>

                <div style="
                margin-top:5px;
                color:#94a3b8;
                font-size:10px;
            ">
                    Your latest leave request will appear here after submission.
                </div>

            </div>

        <?php endif; ?>

    </section>

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
                                Leave ID
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
                                Reject Reason
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
                                Supporting Documents
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
                                        #<?= htmlspecialchars(
                                            $leave['id'] ?? '-'
                                        ) ?>

                                    </div>

                                </td>


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
                                            $leave['leave_type_name'] ?? '-'
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
                                            $leave['reject_reason'] ?? '-'
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
                                            $leave['details'] ?? '—'
                                        ) ?>
                                    </div>

                                </td>
                                <!-- SUPPORTING DOCUMENT -->
                                <td style="
    padding:12px 13px;
    text-align:center;
    border-bottom:1px solid #f1f5f9;
    white-space:nowrap;
">

                                    <?php if (!empty($leave['supporting_document'])): ?>

                                        <a href="/hrms-capstone/modules/portal/public/assets/uploads/leave/<?= rawurlencode($leave['supporting_document']) ?>"
                                            target="_blank" rel="noopener noreferrer" style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:5px;
                padding:6px 10px;
                border:1px solid #bfdbfe;
                border-radius:7px;
                background:#eff6ff;
                color:#2563eb;
                font-size:10px;
                font-weight:600;
                text-decoration:none;
            ">
                                            <i class="fas fa-file-alt"></i>
                                            View
                                        </a>

                                    <?php else: ?>

                                        <span style="
            color:#94a3b8;
            font-size:10px;
        ">
                                            No document
                                        </span>

                                    <?php endif; ?>

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

                                    <?= !empty($leave['date_submitted'])
                                        ? date('M d, Y h:i A', strtotime($leave['date_submitted']))
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