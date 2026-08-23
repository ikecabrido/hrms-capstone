<div class="employee-dashboard">

    <section class="dashboard-welcome" id="resignationWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE RESIGNATION
            </span>

            <h1 class="welcome-title">Resignation</h1>

            <p class="welcome-description">
                Submit and monitor your resignation request, view your resignation details,
                and track its status and approval progress.
            </p>

            <div class="welcome-line"></div>
        </div>

        <div class="welcome-decoration">
            <i class="fas fa-file-signature"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="width:100%;box-sizing:border-box;padding:20px;">

        <?php if (!empty($resignations)): ?>

            <?php $resignation = $resignations[0]; ?>

            <?php
            $status = strtolower($resignation['status'] ?? 'pending');

            $statusStyle = match ($status) {
                'pending' => [
                    'background' => '#fff7ed',
                    'color' => '#ea580c',
                    'icon' => 'fa-clock'
                ],
                'approved' => [
                    'background' => '#f0fdf4',
                    'color' => '#16a34a',
                    'icon' => 'fa-check-circle'
                ],
                'rejected' => [
                    'background' => '#fef2f2',
                    'color' => '#dc2626',
                    'icon' => 'fa-times-circle'
                ],
                'cancelled' => [
                    'background' => '#f3f4f6',
                    'color' => '#6b7280',
                    'icon' => 'fa-ban'
                ],
                default => [
                    'background' => '#eff6ff',
                    'color' => '#2563eb',
                    'icon' => 'fa-info-circle'
                ]
            };
            ?>
            <!-- RESIGNATION CARD -->
            <div style="
            max-width:950px;
            margin:0 auto;
            border:1px solid #e5e7eb;
            border-radius:16px;
            background:#fff;
            overflow:hidden;
            box-shadow:0 4px 15px rgba(0,0,0,.04);
        ">

                <!-- HEADER -->
                <div style="
                padding:22px 26px;
                border-bottom:1px solid #eef2f7;
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
                flex-wrap:wrap;
            ">

                    <div>
                        <div style="
                        color:#9ca3af;
                        font-size:9px;
                        font-weight:700;
                        letter-spacing:.7px;
                        text-transform:uppercase;
                    ">
                            Employee Separation
                        </div>

                        <h2 style="
                        margin:5px 0 0;
                        color:#111827;
                        font-size:18px;
                        font-weight:700;
                    ">
                            Resignation Letter
                        </h2>

                        <div style="
                        margin-top:4px;
                        color:#9ca3af;
                        font-size:10px;
                    ">
                            Submitted
                            <?= !empty($resignation['date_submitted'])
                                ? date('M d, Y', strtotime($resignation['date_submitted']))
                                : '—'
                                ?>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div style="
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 12px;
    border-radius:20px;
    background:<?= $statusStyle['background'] ?>;
    color:<?= $statusStyle['color'] ?>;
    font-size:10px;
    font-weight:700;
    text-transform:capitalize;
">
                        <i class="fas <?= $statusStyle['icon'] ?>"></i>
                        <?= htmlspecialchars($resignation['status'] ?? 'Pending') ?>
                    </div>

                </div>


                <!-- LETTER -->
                <div style="
                padding:30px;
                background:#f8fafc;
            ">

                    <div style="
                    max-width:760px;
                    margin:0 auto;
                    padding:42px 48px;
                    background:#fff;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                    box-shadow:0 3px 12px rgba(0,0,0,.04);
                ">

                        <!-- LETTER DATE -->
                        <div style="
                        text-align:right;
                        color:#4b5563;
                        font-size:11px;
                        margin-bottom:30px;
                    ">
                            <?= !empty($resignation['date_submitted'])
                                ? date('F d, Y', strtotime($resignation['date_submitted']))
                                : '—'
                                ?>
                        </div>


                        <!-- LETTER TITLE -->
                        <div style="
                        margin-bottom:25px;
                        color:#111827;
                        font-size:14px;
                        font-weight:700;
                    ">
                            Formal Resignation
                        </div>


                        <!-- LETTER BODY -->
                        <div style="
                        color:#374151;
                        font-size:11px;
                        line-height:1.8;
                    ">

                            <p style="margin:0 0 18px;">
                                Dear Human Resources,
                            </p>

                            <p style="margin:0 0 18px;">
                                Please accept this letter as my formal resignation from my
                                position with the organization.
                            </p>

                            <p style="margin:0 0 18px;">
                                I am submitting my resignation under the
                                <strong>
                                    <?= htmlspecialchars($resignation['resignation_type'] ?? '—') ?>
                                </strong>
                                arrangement. My intended last working day is
                                <strong>
                                    <?= !empty($resignation['intended_last_working_day'])
                                        ? date(
                                            'F d, Y',
                                            strtotime($resignation['intended_last_working_day'])
                                        )
                                        : '—'
                                        ?>
                                </strong>.
                            </p>

                            <p style="margin:0 0 18px;">
                                <strong>Reason for resignation:</strong>
                            </p>

                            <p style="
                            margin:0 0 20px;
                            white-space:pre-wrap;
                        ">
                                <?= htmlspecialchars(
                                    $resignation['resignation_reason'] ?? 'No reason provided.'
                                ) ?>
                            </p>

                            <?php if (!empty($resignation['employee_remarks'])): ?>

                                <p style="margin:0 0 18px;">
                                    <strong>Employee Remarks:</strong>
                                </p>

                                <p style="
                                margin:0 0 25px;
                                white-space:pre-wrap;
                            ">
                                    <?= htmlspecialchars($resignation['employee_remarks']) ?>
                                </p>

                            <?php endif; ?>

                            <p style="margin:0 0 18px;">
                                I appreciate the opportunities and experiences provided
                                during my employment. I will do my best to ensure a
                                smooth transition and proper turnover of my responsibilities.
                            </p>

                            <p style="margin:30px 0 0;">
                                Respectfully,
                            </p>

                        </div>

                    </div>

                </div>


                <!-- INFORMATION -->
                <div style="
                padding:24px 26px;
                border-top:1px solid #eef2f7;
            ">

                    <div style="
                    margin-bottom:14px;
                    color:#111827;
                    font-size:12px;
                    font-weight:700;
                ">
                        Resignation Details
                    </div>

                    <div style="
                    display:grid;
                    grid-template-columns:repeat(3,minmax(0,1fr));
                    gap:12px;
                ">

                        <!-- TYPE -->
                        <div style="
                        padding:13px;
                        border:1px solid #f1f5f9;
                        border-radius:9px;
                        background:#fafafa;
                    ">
                            <div style="
                            color:#9ca3af;
                            font-size:9px;
                        ">
                                Resignation Type
                            </div>

                            <div style="
                            margin-top:5px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                                <?= htmlspecialchars(
                                    $resignation['resignation_type'] ?? '—'
                                ) ?>
                            </div>
                        </div>


                        <!-- SUBMITTED -->
                        <div style="
                        padding:13px;
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
                            margin-top:5px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                                <?= !empty($resignation['date_submitted'])
                                    ? date(
                                        'M d, Y',
                                        strtotime($resignation['date_submitted'])
                                    )
                                    : '—'
                                    ?>
                            </div>
                        </div>


                        <!-- LAST DAY -->
                        <div style="
                        padding:13px;
                        border:1px solid #f1f5f9;
                        border-radius:9px;
                        background:#fafafa;
                    ">
                            <div style="
                            color:#9ca3af;
                            font-size:9px;
                        ">
                                Last Working Day
                            </div>

                            <div style="
                            margin-top:5px;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                                <?= !empty($resignation['intended_last_working_day'])
                                    ? date(
                                        'M d, Y',
                                        strtotime(
                                            $resignation['intended_last_working_day']
                                        )
                                    )
                                    : '—'
                                    ?>
                            </div>
                        </div>

                    </div>


                    <!-- HR REMARKS -->
                    <?php if (!empty($resignation['hr_remarks'])): ?>

                        <div style="
                        margin-top:14px;
                        padding:14px;
                        border:1px solid #dbeafe;
                        border-radius:9px;
                        background:#eff6ff;
                    ">

                            <div style="
                            margin-bottom:5px;
                            color:#2563eb;
                            font-size:9px;
                            font-weight:700;
                        ">
                                HR REMARKS
                            </div>

                            <div style="
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">
                                <?= htmlspecialchars($resignation['hr_remarks']) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- ATTACHMENT -->
                    <?php if (!empty($resignation['attachment'])): ?>

                        <a href="/hrms-capstone/modules/portal/public/assets/<?= htmlspecialchars($resignation['attachment']) ?>" target="_blank" style="
            margin-top:14px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:13px 14px;
            border:1px solid #e5e7eb;
            border-radius:9px;
            background:#fff;
            text-decoration:none;
            cursor:pointer;
            transition:.2s ease;
        " onmouseover="
            this.style.borderColor='#bfdbfe';
            this.style.background='#f8fbff';
        " onmouseout="
            this.style.borderColor='#e5e7eb';
            this.style.background='#fff';
        ">

                            <div style="
            display:flex;
            align-items:center;
            gap:10px;
        ">

                                <div style="
                width:34px;
                height:34px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:8px;
                background:#fef2f2;
                color:#dc2626;
            ">
                                    <i class="fas fa-file-pdf"></i>
                                </div>

                                <div>

                                    <div style="
                    color:#374151;
                    font-size:10px;
                    font-weight:600;
                ">
                                        Resignation Letter Attachment
                                    </div>

                                    <div style="
                    margin-top:2px;
                    color:#9ca3af;
                    font-size:9px;
                ">
                                        PDF Document • Click to view
                                    </div>

                                </div>

                            </div>

                            <div style="
            width:30px;
            height:30px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:1px solid #dbeafe;
            border-radius:7px;
            background:#eff6ff;
            color:#2563eb;
            flex-shrink:0;
        ">
                                <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                            </div>

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>

            <!-- NO RESIGNATION -->
            <div style="
            max-width:700px;
            margin:30px auto;
            padding:50px 30px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:16px;
            background:#fff;
        ">

                <div style="
                width:60px;
                height:60px;
                margin:0 auto 16px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:50%;
                background:#eff6ff;
                color:#2563eb;
                font-size:20px;
            ">
                    <i class="fas fa-file-signature"></i>
                </div>

                <h3 style="
                margin:0;
                color:#111827;
                font-size:16px;
                font-weight:700;
            ">
                    No Resignation Submitted
                </h3>

                <p style="
                margin:7px 0 20px;
                color:#9ca3af;
                font-size:10px;
            ">
                    You currently have no active resignation request.
                </p>

                <button type="button" data-bs-toggle="modal" data-bs-target="#submitResignationModal" style="
                    padding:10px 16px;
                    border:0;
                    border-radius:8px;
                    background:#2563eb;
                    color:#fff;
                    font-size:10px;
                    font-weight:600;
                    cursor:pointer;
                ">
                    <i class="fas fa-plus"></i>
                    Submit Resignation
                </button>

            </div>

        <?php endif; ?>

    </section>

</div>
<?php require __DIR__ . '/resignation.php'; ?>
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