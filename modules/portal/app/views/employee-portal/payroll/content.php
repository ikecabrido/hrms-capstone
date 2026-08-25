<div class="employee-dashboard">

    <!-- PAYROLL WELCOME -->
    <section class="dashboard-welcome" id="profileWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE PAYROLL
            </span>

            <h1 class="welcome-title">Payroll</h1>

            <p class="welcome-description">
                View your payroll records, payslips, earnings, deductions, and net pay history.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration">
            <i class="fas fa-money-bill-wave"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <!-- Payroll Request -->
    <section class="dashboard-section" style="
    width:100%;
    max-width:100%;
    padding:0;
    margin:0;
    box-sizing:border-box; ">
        <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin:0 0 16px;
        padding-bottom:12px;
        border-bottom:1px solid #e5e7eb;
        flex-wrap:wrap; ">
            <div style="
            display:flex;
            align-items:center;
            gap:11px;
            min-width:0;">

                <!-- ICON -->
                <div style="
                width:40px;
                height:40px;
                min-width:40px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:10px;
                background:#eff6ff;
                color:#2563eb;
                font-size:16px; ">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>

                <!-- TITLE -->
                <div style="
                min-width:0;
                overflow:hidden; ">

                    <h3 style="
                    margin:0;
                    color:#111827;
                    font-size:24px;
                    line-height:1.3;
                    font-weight:700;
                    letter-spacing:-.01em;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis; ">
                        Payroll Request History
                    </h3>

                    <p style="
                    margin:3px 0 0;
                    color:#6b7280;
                    font-size:12px;
                    line-height:1.4;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis; ">
                        View your submitted payroll requests and their status.
                    </p>

                </div>

            </div>


            <!-- HEADER ACTIONS -->
            <div style="
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap; ">

                <!-- RECORD COUNT -->
                <span style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:7px 11px;
                border-radius:20px;
                background:#f8fafc;
                border:1px solid #e5e7eb;
                color:#374151;
                font-size:11px;
                font-weight:600;
                white-space:nowrap; ">
                    <i class="fas fa-file-lines" style="color:#2563eb;"></i>
                    <?= count($payrollRequests ?? []) ?> Records
                </span>


                <!-- CREATE BUTTON -->
                <button type="button" data-bs-toggle="modal" data-bs-target="#createPayrollRequestModal" style="
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
                    box-shadow:0 2px 5px rgba(37,99,235,.20); ">
                    <i class="fas fa-plus"></i>
                    Create Payroll Request
                </button>

            </div>

        </div>


        <?php if (!empty($payrollRequests)): ?>

            <!-- TABLE -->
            <div style="
            width:100%;
            overflow-x:auto;
            overflow-y:hidden;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#ffffff;
            -webkit-overflow-scrolling:touch;
            scrollbar-width:thin;
            scrollbar-color:#cbd5e1 transparent; ">

                <table style="
                width:100%;
                min-width:900px;
                border-collapse:collapse;
                font-size:13px; ">

                    <!-- TABLE HEADER -->
                    <thead>

                        <tr style="
                        background:#f8fafc;
                        border-bottom:1px solid #e5e7eb;
                    ">

                            <th style="
                            padding:13px 16px;
                            text-align:left;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                <i class="fas fa-file-lines me-1"></i>
                                ID
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:left;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                <i class="fas fa-file-lines me-1"></i>
                                Request Type
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:left;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                Purpose
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:left;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                <i class="far fa-calendar me-1"></i>
                                Payroll Period
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:left;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                Requested Date
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:center;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                Status
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:center;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                File
                            </th>

                            <th style="
                            padding:13px 16px;
                            text-align:center;
                            color:#64748b;
                            font-size:11px;
                            font-weight:700;
                            white-space:nowrap; ">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <!-- TABLE BODY -->
                    <tbody id="payrollRequestTableBody">

                        <?php foreach ($payrollRequests as $request): ?>

                            <?php

                            $status = strtolower(trim($request['status'] ?? 'pending'));

                            switch ($status) {

                                case 'approved':
                                    $statusBg = '#dcfce7';
                                    $statusColor = '#166534';
                                    $statusIcon = 'fa-check-circle';
                                    break;

                                case 'rejected':
                                    $statusBg = '#fee2e2';
                                    $statusColor = '#b91c1c';
                                    $statusIcon = 'fa-times-circle';
                                    break;

                                case 'under_review':
                                case 'under review':
                                    $statusBg = '#fef3c7';
                                    $statusColor = '#92400e';
                                    $statusIcon = 'fa-clock';
                                    break;

                                case 'processed':
                                    $statusBg = '#dbeafe';
                                    $statusColor = '#1d4ed8';
                                    $statusIcon = 'fa-circle-check';
                                    break;

                                case 'cancelled':
                                    $statusBg = '#f3f4f6';
                                    $statusColor = '#4b5563';
                                    $statusIcon = 'fa-ban';
                                    break;

                                default:
                                    $statusBg = '#fef3c7';
                                    $statusColor = '#92400e';
                                    $statusIcon = 'fa-clock';
                                    break;
                            }

                            $requestType = $request['request_type'] ?? '-';

                            $purpose = $request['purpose'] ?? '-';

                            $startDate = !empty($request['payroll_period_start'])
                                ? date('M d, Y', strtotime($request['payroll_period_start']))
                                : '-';

                            $endDate = !empty($request['payroll_period_end'])
                                ? date('M d, Y', strtotime($request['payroll_period_end']))
                                : '-';

                            $payrollPeriod = ($startDate !== '-' && $endDate !== '-')
                                ? $startDate . ' – ' . $endDate
                                : '-';

                            $requestedDate = !empty($request['requested_at'])
                                ? date('M d, Y', strtotime($request['requested_at']))
                                : '-';

                            ?>

                            <tr class="payroll-request-row" style="
                                border-bottom:1px solid #f1f5f9;
                                transition:background .2s ease;
                            " onmouseover="this.style.background='#f8fafc'"
                                onmouseout="this.style.background='#ffffff'">

                                <!-- REQUEST TYPE ID -->
                                <td style="
                                padding:14px 16px;
                                color:#111827;
                                font-weight:600;
                                white-space:nowrap; ">

                                    <i class="fa-solid fa-id-card" style="
                                        margin-right:7px;
                                        color:#2563eb;
                                    ">
                                    </i>
                                    #<?= !empty($request['id'])
                                        ? $request['id'] : '-' ?>
                                </td>

                                <!-- REQUEST TYPE -->
                                <td style="
                                padding:14px 16px;
                                color:#111827;
                                font-weight:600;
                                white-space:nowrap; ">

                                    <i class="fas fa-file-invoice" style="
                                        margin-right:7px;
                                        color:#2563eb;
                                    ">
                                    </i>

                                    <?= htmlspecialchars($requestType) ?>

                                </td>


                                <!-- PURPOSE -->
                                <td style="
                                padding:14px 16px;
                                color:#374151;
                                max-width:260px; ">

                                    <div style="
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                    white-space:nowrap;
                                    max-width:260px; ">
                                        <?= htmlspecialchars($purpose) ?>
                                    </div>

                                </td>


                                <!-- PAYROLL PERIOD -->
                                <td style="
                                padding:14px 16px;
                                color:#374151;
                                white-space:nowrap; ">

                                    <i class="fas fa-calendar-days" style="
                                        margin-right:6px;
                                        color:#64748b;
                                    ">
                                    </i>

                                    <?= htmlspecialchars($payrollPeriod) ?>

                                </td>


                                <!-- REQUESTED DATE -->
                                <td style="
                                padding:14px 16px;
                                color:#374151;
                                white-space:nowrap; ">

                                    <?= htmlspecialchars($requestedDate) ?>

                                </td>

                                <!-- STATUS -->
                                <td style="
                                padding:14px 16px;
                                text-align:center; ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;
                                    padding:5px 9px;
                                    border-radius:20px;
                                    background:<?= $statusBg ?>;
                                    color:<?= $statusColor ?>;
                                    font-size:10px;
                                    font-weight:700;
                                    white-space:nowrap; ">

                                        <i class="fas <?= $statusIcon ?>" style="font-size:9px;">
                                        </i>

                                        <?= htmlspecialchars(
                                            ucwords(str_replace('_', ' ', $status))
                                        ) ?>

                                    </span>

                                </td>

                                <!-- Files -->
                                <td style="padding:14px 16px; text-align:center;">
                                    <?php if (!empty($request['document_path'])): ?>
                                        <a href="/hrms-capstone/modules/portal/public/<?= htmlspecialchars(
                                            ltrim(
                                                str_replace(
                                                    'D:\\xampp\\htdocs\\hrms-capstone\\modules\\portal\\public\\',
                                                    '',
                                                    $request['document_path']
                                                ),
                                                '/\\'
                                            )
                                        ) ?>" target="_blank" title="View File" style="color:#2563eb; font-size:14px;">
                                            <i class="fas fa-file"></i>
                                        </a>
                                    <?php else: ?>
                                        <span title="No file attached" style="color:#9ca3af;">
                                            <i class="text-xl fas fa-file-circle-xmark"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- ACTION -->
                                <td style="
    padding:14px 16px;
    text-align:center;
">
                                    <button type="button" class="view-payroll-request-btn" data-bs-toggle="modal"
                                        data-bs-target="#viewPayrollRequestModal<?= (int) $request['id'] ?>" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            padding:7px 11px;
            border:1px solid #dbeafe;
            border-radius:8px;
            background:#eff6ff;
            color:#2563eb;
            font-size:11px;
            font-weight:600;
            cursor:pointer;
            white-space:nowrap;
        ">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>

                </table>
<?php require __DIR__ . '/view-payroll-request.php'; ?>
            </div>


        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            box-sizing:border-box;
            text-align:center;
            padding:50px 20px;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#ffffff; ">

                <div style="
                width:55px;
                height:55px;
                margin:0 auto 14px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:14px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:23px; ">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>

                <h5 style="
                margin:0 0 5px;
                color:#374151;
                font-size:15px;
                font-weight:700; ">
                    No Payroll Requests
                </h5>

                <p style="
                margin:0 0 16px;
                color:#9ca3af;
                font-size:12px; ">
                    Your payroll requests will appear here once you submit a request.
                </p>

                <button type="button" data-bs-toggle="modal" data-bs-target="#createPayrollRequestModal" style="
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
                    cursor:pointer; ">
                    <i class="fas fa-plus"></i>
                    Create Payroll Request
                </button>

            </div>

        <?php endif; ?>

    </section>

    <!-- Payroll History -->
    <section class="dashboard-section mt-4" style="
    width:100%;
    max-width:100%;
    padding:0;
    margin:0;
    box-sizing:border-box; ">

        <div style="
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin:0 0 16px;
    padding-bottom:12px;
    border-bottom:1px solid #e5e7eb;
    flex-wrap:wrap; ">

            <div style="
        display:flex;
        align-items:center;
        gap:11px;
        min-width:0; ">

                <!-- Icon -->
                <div style="
            width:40px;
            height:40px;
            min-width:40px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
            background:#eff6ff;
            color:#2563eb;
            font-size:16px; ">
                    <i class="fas fa-money-check-dollar"></i>
                </div>

                <!-- Title -->
                <div style="
            min-width:0;
            overflow:hidden; ">

                    <h3 style="
                margin:0;
                color:#111827;
                font-size:24px;
                line-height:1.3;
                font-weight:700;
                letter-spacing:-.01em;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis; ">
                        Payroll History
                    </h3>

                    <p style="
                margin:3px 0 0;
                color:#6b7280;
                font-size:12px;
                line-height:1.4;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis; ">
                        Your recorded payroll and payslip history.
                    </p>

                </div>

            </div>

            <div>
                <span style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:7px 11px;
                border-radius:20px;
                background:#f8fafc;
                border:1px solid #e5e7eb;
                color:#374151;
                font-size:11px;
                font-weight:600;
                white-space:nowrap; ">
                    <i class="fas fa-file-invoice" style="color:#2563eb;"></i>
                    <?= count($payrollHistory ?? []) ?> Records
                </span>
            </div>
        </div>


        <?php if (!empty($payrollHistory)): ?>

            <div style="
                width:100%;
                overflow-x:auto;
                overflow-y:hidden;
                border:1px solid #e5e7eb;
                border-radius:12px;
                background:#ffffff;
                -webkit-overflow-scrolling:touch;
                scrollbar-width:thin;
                scrollbar-color:#cbd5e1 transparent; ">

                <table style="
                    width:100%;
                    min-width:850px;
                    border-collapse:collapse;
                    font-size:13px; ">

                    <thead>

                        <tr style="
                            background:#f8fafc;
                            border-bottom:1px solid #e5e7eb; ">

                            <th style="
                                padding:13px 16px;
                                text-align:left;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                <i class="far fa-calendar me-1"></i>
                                ID
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:left;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                <i class="far fa-calendar me-1"></i>
                                Payroll Date
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:right;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                Gross Pay
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:right;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                Deductions
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:right;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                Net Pay
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:center;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                Status
                            </th>

                            <th style="
                                padding:13px 16px;
                                text-align:center;
                                color:#64748b;
                                font-size:11px;
                                font-weight:700;
                                white-space:nowrap; ">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($payrollHistory as $payroll): ?>

                            <tr style="
                                border-bottom:1px solid #f1f5f9;
                                transition:background .2s ease;
                            " onmouseover="this.style.background='#f8fafc'"
                                onmouseout="this.style.background='#ffffff'">

                                <!-- ID -->
                                <td style="
                                    padding:14px 16px;
                                    color:#111827;
                                    font-weight:600;
                                    white-space:nowrap; ">
                                    <i class="fa-solid fa-id-card" style="
                                        margin-right:7px;
                                        color:#2563eb;
                                    "></i>
                                    #<?= !empty($payroll['payslip_id'])
                                        ? $payroll['payslip_id'] : '-' ?>
                                </td>

                                <!-- DATE -->
                                <td style="
                                    padding:14px 16px;
                                    color:#111827;
                                    font-weight:600;
                                    white-space:nowrap; ">
                                    <i class="fas fa-calendar-days" style="
                                        margin-right:7px;
                                        color:#2563eb;
                                    "></i>

                                    <?= !empty($payroll['generated_at'])
                                        ? date('M d, Y', strtotime($payroll['generated_at']))
                                        : '-' ?>
                                </td>


                                <!-- GROSS -->
                                <td style="
                                    padding:14px 16px;
                                    text-align:right;
                                    color:#374151;
                                    white-space:nowrap; ">
                                    ₱<?= number_format(
                                        (float) ($payroll['gross_pay'] ?? 0),
                                        2
                                    ) ?>
                                </td>


                                <!-- DEDUCTIONS -->
                                <td style="
                                    padding:14px 16px;
                                    text-align:right;
                                    color:#dc2626;
                                    white-space:nowrap; ">
                                    ₱<?= number_format(
                                        (float) ($payroll['total_deductions'] ?? 0),
                                        2
                                    ) ?>
                                </td>


                                <!-- NET PAY -->
                                <td style="
                                    padding:14px 16px;
                                    text-align:right;
                                    color:#166534;
                                    font-weight:700;
                                    white-space:nowrap; ">
                                    ₱<?= number_format(
                                        (float) ($payroll['net_pay'] ?? 0),
                                        2
                                    ) ?>
                                </td>


                                <!-- STATUS -->
                                <td style="
                                    padding:14px 16px;
                                    text-align:center; ">

                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:5px;
                                        padding:5px 9px;
                                        border-radius:20px;
                                        background:#dcfce7;
                                        color:#166534;
                                        font-size:10px;
                                        font-weight:700;
                                        white-space:nowrap;
                                    ">
                                        <i class="fas fa-circle" style="
                                            font-size:5px;
                                        "></i>

                                        Released
                                    </span>

                                </td>


                                <!-- ACTION -->
                                <td style="
                                    padding:14px 16px;
                                    text-align:center; ">

                                    <button type="button" class="btn btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewPayrollModal<?= (int) $payroll['payslip_id'] ?>" style="
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:7px 11px;
        border:1px solid #dbeafe;
        border-radius:8px;
        background:#eff6ff;
        color:#2563eb;
        font-size:11px;
        font-weight:600;
    ">
                                        <i class="fas fa-eye"></i>
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
                box-sizing:border-box;
                text-align:center;
                padding:50px 20px;
                border:1px solid #e5e7eb;
                border-radius:12px;
                background:#ffffff; ">

                <div style="
                    width:55px;
                    height:55px;
                    margin:0 auto 14px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:14px;
                    background:#eff6ff;
                    color:#93c5fd;
                    font-size:23px; ">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>

                <h5 style="
                    margin:0 0 5px;
                    color:#374151;
                    font-size:15px;
                    font-weight:700; ">
                    No Payroll Records
                </h5>

                <p style="
                    margin:0;
                    color:#9ca3af;
                    font-size:12px; ">
                    Your payroll history will appear here once a payslip has been generated.
                </p>

            </div>

        <?php endif; ?>

    </section>
</div>
<script>
    document.querySelectorAll('.modal').forEach(modal => {
        document.body.appendChild(modal);
    });
</script>
<style>
    .modal {
        z-index: 1055 !important;
    }

    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>