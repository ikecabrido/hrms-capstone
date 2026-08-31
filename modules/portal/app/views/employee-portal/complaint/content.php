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

<div style="
    width:100%;
    display:grid;
    grid-template-columns:minmax(0, 1fr) 300px;
    gap:20px;
    align-items:stretch;
    box-sizing:border-box;
">

    <!-- =====================================================
         LEFT COLUMN — COMPLAINT HISTORY
    ====================================================== -->
    <div style="
        min-width:0;
        width:100%;
        display:flex;
        flex-direction:column;
    ">

        <section class="dashboard-section" style="
            width:100%;
            height:100%;
            box-sizing:border-box;
            display:flex;
            flex-direction:column;
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
                <button
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#complaintModal"
                    style="
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
                    "
                >
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


                <!-- TABLE -->
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

                    flex:1;
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
                            <tr style="background:#f8fafc;">

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
                                    $complaint['status'] ?? 'under_initial_review'
                                );

                                $statusStyle = match ($status) {

                                    'under_initial_review' => [
                                        '#eff6ff',
                                        '#2563eb'
                                    ],

                                    'under_investigation' => [
                                        '#fff7ed',
                                        '#ea580c'
                                    ],

                                    'pending_employee_response' => [
                                        '#fefce8',
                                        '#ca8a04'
                                    ],

                                    'for_decision' => [
                                        '#f5f3ff',
                                        '#7c3aed'
                                    ],

                                    'closed_no_violation',
                                    'closed_warning_issued',
                                    'closed_resolved',
                                    'closed' => [
                                        '#f0fdf4',
                                        '#15803d'
                                    ],

                                    'closed_suspension' => [
                                        '#fff7ed',
                                        '#c2410c'
                                    ],

                                    'closed_termination_recommended' => [
                                        '#fef2f2',
                                        '#dc2626'
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

                                    'high' => [
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

                                $statusLabel = ucwords(
                                    str_replace('_', ' ', $status)
                                );
                                ?>

                                <tr
                                    style="transition:.2s ease;"
                                    onmouseover="this.style.background='#f8fafc';"
                                    onmouseout="this.style.background='#fff';"
                                >

                                    <!-- ID -->
                                    <td style="
                                        padding:13px 14px;
                                        border-bottom:1px solid #f1f5f9;
                                    ">
                                        <span style="
                                            color:#2563eb;
                                            font-weight:700;
                                        ">
                                            #<?= htmlspecialchars(
                                                $complaint['id'] ?? '—'
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
                                            $complaint['type'] ?? '—'
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

                                        <?php if (!empty($complaint['employee_id'])): ?>

                                            <div style="
                                                margin-top:3px;
                                                color:#9ca3af;
                                                font-size:9px;
                                            ">
                                                Employee ID:
                                                <?= htmlspecialchars(
                                                    $complaint['employee_id']
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
                                        <?= !empty($complaint['incident_date'])
                                            ? date(
                                                'M d, Y',
                                                strtotime(
                                                    $complaint['incident_date']
                                                )
                                            )
                                            : '—'
                                        ?>
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

                                            <i
                                                class="fas fa-circle"
                                                style="font-size:4px;"
                                            ></i>

                                            <?= htmlspecialchars($statusLabel) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->
                                    <td style="
                                        padding:13px 14px;
                                        text-align:center;
                                        border-bottom:1px solid #f1f5f9;
                                    ">

                                        <button
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewComplaintModal<?= (int) $complaint['id'] ?>"
                                            style="
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
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-eye"
                                                style="font-size:10px;"
                                            ></i>
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
                    flex:1;
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

                    <button
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#complaintModal"
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:7px;
                            padding:9px 13px;
                            border-radius:9px;
                            border:0;
                            background:#2563eb;
                            color:#fff;
                            font-size:10px;
                            font-weight:600;
                            cursor:pointer;
                        "
                    >
                        <i class="fas fa-plus"></i>
                        Submit a Complaint
                    </button>

                </div>

            <?php endif; ?>

        </section>

    </div>


    <!-- =====================================================
         RIGHT COLUMN — COMPLAINT STATUS SIDEBAR
    ====================================================== -->
    <aside style="
        width:100%;
        min-width:0;
        height:100%;
        box-sizing:border-box;
        display:flex;
        flex-direction:column;
    ">

        <?php
        $latestComplaint = !empty($complaintHistory)
            ? $complaintHistory[0]
            : null;

        $latestStatus = strtolower(
            $latestComplaint['status'] ?? 'none'
        );

        $statusConfig = match ($latestStatus) {

            'under_initial_review' => [
                'label' => 'Under Initial Review',
                'icon' => 'fa-magnifying-glass',
                'bg' => '#eff6ff',
                'color' => '#2563eb',
                'description' =>
                    'Your complaint has been received and is being reviewed by HR.'
            ],

            'under_investigation' => [
                'label' => 'Under Investigation',
                'icon' => 'fa-search',
                'bg' => '#fff7ed',
                'color' => '#ea580c',
                'description' =>
                    'HR is currently investigating the complaint.'
            ],

            'pending_employee_response' => [
                'label' => 'Awaiting Response',
                'icon' => 'fa-clock',
                'bg' => '#fefce8',
                'color' => '#ca8a04',
                'description' =>
                    'A response or explanation is currently required.'
            ],

            'for_decision' => [
                'label' => 'For Decision',
                'icon' => 'fa-scale-balanced',
                'bg' => '#f5f3ff',
                'color' => '#7c3aed',
                'description' =>
                    'The complaint has reached the decision stage.'
            ],

            'closed_no_violation',
            'closed_warning_issued',
            'closed_resolved',
            'closed' => [
                'label' => 'Closed',
                'icon' => 'fa-circle-check',
                'bg' => '#f0fdf4',
                'color' => '#15803d',
                'description' =>
                    'This complaint has been closed.'
            ],

            'closed_suspension' => [
                'label' => 'Closed',
                'icon' => 'fa-circle-check',
                'bg' => '#fff7ed',
                'color' => '#c2410c',
                'description' =>
                    'The complaint has been closed following a suspension decision.'
            ],

            'closed_termination_recommended' => [
                'label' => 'Closed',
                'icon' => 'fa-circle-check',
                'bg' => '#fef2f2',
                'color' => '#dc2626',
                'description' =>
                    'The complaint has reached its final decision.'
            ],

            default => [
                'label' => 'No Active Complaint',
                'icon' => 'fa-file-circle-check',
                'bg' => '#f8fafc',
                'color' => '#64748b',
                'description' =>
                    'You currently have no submitted complaints.'
            ]
        };

        $currentStep = match (true) {

            $latestStatus === 'under_initial_review'
                => 1,

            $latestStatus === 'under_investigation'
                => 2,

            $latestStatus === 'pending_employee_response'
                => 3,

            $latestStatus === 'for_decision'
                => 4,

            str_starts_with($latestStatus, 'closed')
                => 5,

            default
                => 1
        };

        $workflowSteps = [
            'Initial Review',
            'Investigation',
            'Employee Response',
            'Decision',
            'Closed'
        ];
        ?>


        <!-- SIDEBAR CARD -->
        <div style="
            width:100%;
            height:100%;
            box-sizing:border-box;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            overflow:hidden;
            box-shadow:0 4px 16px rgba(15,23,42,.04);
            display:flex;
            flex-direction:column;
        ">

            <!-- SIDEBAR HEADER -->
            <div style="
                padding:15px 16px;
                border-bottom:1px solid #f1f5f9;
                flex-shrink:0;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:9px;
                ">

                    <div style="
                        width:34px;
                        height:34px;
                        flex-shrink:0;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:9px;
                        background:#eff6ff;
                        color:#2563eb;
                        font-size:13px;
                    ">
                        <i class="fas fa-file-shield"></i>
                    </div>

                    <div>

                        <div style="
                            color:#111827;
                            font-size:12px;
                            font-weight:700;
                        ">
                            Complaint Status
                        </div>

                        <div style="
                            margin-top:2px;
                            color:#9ca3af;
                            font-size:9px;
                        ">
                            Latest complaint update
                        </div>

                    </div>

                </div>

            </div>


            <?php if ($latestComplaint): ?>

                <!-- SIDEBAR BODY -->
                <div style="
                    padding:16px;
                    flex:1;
                    display:flex;
                    flex-direction:column;
                ">

                    <!-- CURRENT STATUS -->
                    <div style="
                        display:flex;
                        align-items:center;
                        gap:11px;
                        padding:12px;
                        border:1px solid <?= $statusConfig['color'] ?>20;
                        border-radius:10px;
                        background:<?= $statusConfig['bg'] ?>;
                    ">

                        <div style="
                            width:36px;
                            height:36px;
                            flex-shrink:0;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:10px;
                            background:#fff;
                            color:<?= $statusConfig['color'] ?>;
                            box-shadow:0 1px 3px rgba(15,23,42,.06);
                            font-size:13px;
                        ">
                            <i class="fas <?= $statusConfig['icon'] ?>"></i>
                        </div>

                        <div style="min-width:0;">

                            <div style="
                                color:#9ca3af;
                                font-size:8px;
                                font-weight:600;
                                text-transform:uppercase;
                                letter-spacing:.04em;
                            ">
                                Current Status
                            </div>

                            <div style="
                                margin-top:3px;
                                color:<?= $statusConfig['color'] ?>;
                                font-size:11px;
                                font-weight:700;
                                line-height:1.3;
                            ">
                                <?= htmlspecialchars($statusConfig['label']) ?>
                            </div>

                        </div>

                    </div>


                    <!-- COMPLAINT ID -->
                    <div style="
                        margin-top:15px;
                        padding-bottom:13px;
                        border-bottom:1px solid #f1f5f9;
                    ">

                        <div style="
                            color:#9ca3af;
                            font-size:8px;
                            font-weight:600;
                            text-transform:uppercase;
                        ">
                            Complaint ID
                        </div>

                        <div style="
                            margin-top:4px;
                            color:#2563eb;
                            font-size:11px;
                            font-weight:700;
                        ">
                            #<?= htmlspecialchars(
                                $latestComplaint['id'] ?? '—'
                            ) ?>
                        </div>

                    </div>


                    <!-- COMPLAINT -->
                    <div style="
                        margin-top:13px;
                        padding-bottom:13px;
                        border-bottom:1px solid #f1f5f9;
                    ">

                        <div style="
                            color:#9ca3af;
                            font-size:8px;
                            font-weight:600;
                            text-transform:uppercase;
                        ">
                            Complaint
                        </div>

                        <div style="
                            margin-top:4px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                            line-height:1.45;
                        ">
                            <?= htmlspecialchars(
                                $latestComplaint['title']
                                ?? 'Untitled Complaint'
                            ) ?>
                        </div>

                    </div>


                    <!-- PROGRESS -->
                    <div style="
                        margin-top:15px;
                    ">

                        <div style="
                            margin-bottom:10px;
                            color:#111827;
                            font-size:10px;
                            font-weight:700;
                        ">
                            Complaint Progress
                        </div>

                        <?php foreach ($workflowSteps as $index => $step): ?>

                            <?php
                            $stepNumber = $index + 1;

                            $isCompleted =
                                $stepNumber < $currentStep;

                            $isCurrent =
                                $stepNumber === $currentStep;

                            $stepColor =
                                ($isCompleted || $isCurrent)
                                    ? '#2563eb'
                                    : '#d1d5db';
                            ?>

                            <div style="
                                display:flex;
                                align-items:center;
                                gap:9px;
                                margin-bottom:9px;
                            ">

                                <div style="
                                    width:19px;
                                    height:19px;
                                    flex-shrink:0;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    border-radius:50%;
                                    background:<?= $isCompleted
                                        ? '#2563eb'
                                        : ($isCurrent
                                            ? '#eff6ff'
                                            : '#f8fafc') ?>;
                                    border:1px solid <?= $stepColor ?>;
                                    color:<?= $isCompleted
                                        ? '#fff'
                                        : $stepColor ?>;
                                    font-size:7px;
                                    font-weight:700;
                                ">

                                    <?php if ($isCompleted): ?>

                                        <i class="fas fa-check"></i>

                                    <?php else: ?>

                                        <?= $stepNumber ?>

                                    <?php endif; ?>

                                </div>

                                <span style="
                                    color:<?= $isCurrent
                                        ? '#111827'
                                        : ($isCompleted
                                            ? '#374151'
                                            : '#9ca3af') ?>;
                                    font-size:9px;
                                    font-weight:<?= $isCurrent
                                        ? '700'
                                        : '500' ?>;
                                ">
                                    <?= htmlspecialchars($step) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- DESCRIPTION -->
                    <div style="
                        margin-top:6px;
                        padding:11px 12px;
                        border-radius:9px;
                        background:#f8fafc;
                        color:#6b7280;
                        font-size:9px;
                        line-height:1.55;
                    ">

                        <i
                            class="fas fa-circle-info"
                            style="
                                margin-right:4px;
                                color:#94a3b8;
                            "
                        ></i>

                        <?= htmlspecialchars(
                            $statusConfig['description']
                        ) ?>

                    </div>


                    <!-- VIEW BUTTON -->
                    <button
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#viewComplaintModal<?= (int) $latestComplaint['id'] ?>"
                        style="
                            width:100%;
                            margin-top:auto;
                            padding:9px 12px;
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            gap:6px;
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            background:#fff;
                            color:#374151;
                            font-size:10px;
                            font-weight:600;
                            cursor:pointer;
                        "
                    >
                        <i
                            class="fas fa-eye"
                            style="font-size:9px;"
                        ></i>

                        View Latest Complaint
                    </button>

                </div>

            <?php else: ?>

                <!-- EMPTY SIDEBAR -->
                <div style="
                    padding:30px 18px;
                    text-align:center;
                    flex:1;
                    display:flex;
                    flex-direction:column;
                    align-items:center;
                    justify-content:center;
                ">

                    <div style="
                        width:44px;
                        height:44px;
                        margin-bottom:10px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:12px;
                        background:#f8fafc;
                        color:#cbd5e1;
                        font-size:17px;
                    ">
                        <i class="fas fa-file-circle-check"></i>
                    </div>

                    <div style="
                        color:#374151;
                        font-size:11px;
                        font-weight:700;
                    ">
                        No Complaints Yet
                    </div>

                    <div style="
                        margin-top:5px;
                        color:#9ca3af;
                        font-size:9px;
                        line-height:1.5;
                    ">
                        Your submitted complaints will appear here.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </aside>

</div>


<!-- =========================================================
     RESPONSIVE BEHAVIOR
========================================================= -->

<style>
@media (max-width: 900px) {

    .dashboard-section + aside {
        width: 100%;
    }

}

@media (max-width: 900px) {

    /* Stack the two columns on smaller screens */
    div[style*="grid-template-columns:minmax(0, 1fr) 300px"] {
        grid-template-columns: minmax(0, 1fr) !important;
    }

    div[style*="grid-template-columns:minmax(0, 1fr) 300px"] aside {
        height: auto !important;
    }

    div[style*="grid-template-columns:minmax(0, 1fr) 300px"] aside > div {
        height: auto !important;
    }

}
</style>


</div>