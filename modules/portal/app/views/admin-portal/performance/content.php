<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>
        <div class="welcome-content">
            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                PERFORMANCE MANAGEMENT
            </span>
            <h1 id="welcomeTitle">
                Employee Performance
            </h1>
            <p id="welcomeDescription">
                View your performance evaluations, feedback, ratings, goals, achievements,
                and development progress throughout your employment.
            </p>
            <div class="welcome-line"></div>
        </div>
        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-chart-line"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <div style="
    width:100%;
    max-width:100%;
    box-sizing:border-box;
    overflow:hidden;
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

            <div style="min-width:0;">

                <span style="
                display:block;
                margin-bottom:5px;
                color:#2563eb;
                font-size:9px;
                font-weight:700;
                letter-spacing:.08em;
            ">
                    PERFORMANCE MANAGEMENT
                </span>

                <h2 style="
                margin:0;
                color:#111827;
                font-size:22px;
                font-weight:700;
            ">
                    Performance Evaluations
                </h2>

                <p style="
                margin:5px 0 0;
                color:#6b7280;
                font-size:11px;
            ">
                    View and monitor employee performance evaluations and feedback.
                </p>

            </div>

            <!-- TOTAL -->
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
            white-space:nowrap;
        ">

                <i class="fas fa-chart-line" style="color:#2563eb;"></i>

                <?= count($allPerformance) ?>

                <?= count($allPerformance) === 1
                    ? 'Evaluation'
                    : 'Evaluations' ?>

            </div>

        </div>

        <!-- TABLE OUTER CARD -->
        <div style="
        width:100%;
        max-width:100%;
        box-sizing:border-box;

        border:1px solid #e5e7eb;
        border-radius:14px;
        background:#fff;

        box-shadow:0 4px 16px rgba(15,23,42,.04);

        overflow:hidden;
    ">

            <!-- IMPORTANT RESPONSIVE WRAPPER -->
            <div style="
            width:100%;
            max-width:100%;
            overflow-x:auto;
            overflow-y:hidden;

            -webkit-overflow-scrolling:touch;

            scrollbar-width:thin;
            scrollbar-color:#cbd5e1 #f8fafc;
        ">

                <table style="
                width:100%;
                min-width:1050px;

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
                                Employee
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
                                Reviewer
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
                                Category
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
                                Review Period
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
                                Rating
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
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($allPerformance)): ?>

                            <?php foreach ($allPerformance as $performance): ?>

                                <?php

                                $rating = (float) (
                                    $performance['overall_rating']
                                    ?? $performance['rating']
                                    ?? 0
                                );

                                $status = strtolower(
                                    $performance['feedback_status']
                                    ?? 'pending'
                                );

                                $statusConfig = match ($status) {

                                    'submitted' => [
                                        '#f0fdf4',
                                        '#15803d'
                                    ],

                                    'approved' => [
                                        '#eff6ff',
                                        '#2563eb'
                                    ],

                                    'reviewed' => [
                                        '#f5f3ff',
                                        '#7c3aed'
                                    ],

                                    'draft' => [
                                        '#f8fafc',
                                        '#64748b'
                                    ],

                                    default => [
                                        '#fff7ed',
                                        '#c2410c'
                                    ]
                                };

                                $statusLabel = ucwords(
                                    str_replace('_', ' ', $status)
                                );

                                ?>

                                <tr style="transition:.2s ease;" onmouseover="this.style.background='#f8fafc';"
                                    onmouseout="this.style.background='#fff';">

                                    <!-- EMPLOYEE -->
                                    <td style="
                                    padding:13px 14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <div style="
                                        color:#111827;
                                        font-weight:600;
                                    ">
                                            Employee #<?= htmlspecialchars(
                                                $performance['employee_id'] ?? '—'
                                            ) ?>
                                        </div>

                                        <div style="
                                        margin-top:3px;
                                        color:#9ca3af;
                                        font-size:9px;
                                    ">
                                            <?= htmlspecialchars(
                                                $performance['department'] ?? 'No Department'
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- REVIEWER -->
                                    <td style="
                                    padding:13px 14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <div style="
                                        color:#374151;
                                        font-weight:600;
                                    ">
                                            <?= htmlspecialchars(
                                                $performance['reviewer_name'] ?? '—'
                                            ) ?>
                                        </div>

                                        <div style="
                                        margin-top:3px;
                                        color:#9ca3af;
                                        font-size:9px;
                                    ">
                                            <?= htmlspecialchars(
                                                $performance['reviewer_type'] ?? '—'
                                            ) ?>
                                        </div>

                                    </td>


                                    <!-- CATEGORY -->
                                    <td style="
                                    padding:13px 14px;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:4px 8px;
                                        border-radius:20px;
                                        background:#eff6ff;
                                        color:#2563eb;
                                        font-size:8px;
                                        font-weight:700;
                                    ">
                                            <?= htmlspecialchars(
                                                $performance['category']
                                                ?? $performance['feedback_category']
                                                ?? '—'
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- REVIEW PERIOD -->
                                    <td style="
                                    padding:13px 14px;
                                    border-bottom:1px solid #f1f5f9;
                                    color:#6b7280;
                                ">
                                        <?= htmlspecialchars(
                                            $performance['review_period'] ?? '—'
                                        ) ?>
                                    </td>


                                    <!-- RATING -->
                                    <td style="
                                    padding:13px 14px;
                                    text-align:center;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <div style="
                                        color:#2563eb;
                                        font-weight:700;
                                        font-size:12px;
                                    ">
                                            <?= number_format($rating, 2) ?>
                                        </div>

                                        <div style="
                                        margin-top:2px;
                                        color:#9ca3af;
                                        font-size:8px;
                                    ">
                                            / 5.00
                                        </div>

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

                                        background:<?= $statusConfig[0] ?>;
                                        color:<?= $statusConfig[1] ?>;

                                        font-size:8px;
                                        font-weight:700;
                                        text-transform:uppercase;
                                    ">

                                            <i class="fas fa-circle" style="font-size:4px;"></i>

                                            <?= htmlspecialchars($statusLabel) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->
                                    <td style="
                                    padding:13px 14px;
                                    text-align:center;
                                    border-bottom:1px solid #f1f5f9;
                                    color:#6b7280;
                                ">

                                        <?= !empty($performance['created_at'])
                                            ? date(
                                                'M d, Y',
                                                strtotime($performance['created_at'])
                                            )
                                            : '—'
                                            ?>

                                    </td>


                                    <!-- ACTION -->
                                    <td style="
                                    padding:13px 14px;
                                    text-align:center;
                                    border-bottom:1px solid #f1f5f9;
                                ">

                                        <button type="button" data-bs-toggle="modal"
                                            data-bs-target="#viewPerformanceModal<?= (int) $performance['feedback_id'] ?>"
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

                                            font-size:10px;
                                            font-weight:600;

                                            cursor:pointer;
                                        ">

                                            <i class="fas fa-eye" style="font-size:9px;"></i>

                                            View

                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8" style="
                                    padding:50px 20px;
                                    text-align:center;
                                    color:#9ca3af;
                                    font-size:11px;
                                ">

                                    <i class="fas fa-chart-line" style="
                                        display:block;
                                        margin-bottom:8px;
                                        font-size:24px;
                                        color:#cbd5e1;
                                    "></i>

                                    No performance evaluations found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>
</div>

<!-- ===================================================== -->
<!-- MODALS -->
<!-- ===================================================== -->
<?php require __DIR__ . '/view.php'; ?>
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