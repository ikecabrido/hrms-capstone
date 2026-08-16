<div class="employee-dashboard">

    <section class="dashboard-welcome" id="profileWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                PERFORMANCE EVALUATION
            </span>

            <h1 class="welcome-title">
                360° Feedback
            </h1>

            <p class="welcome-description">
                Review your 360° feedback evaluations, including ratings, categories,
                comments, and evaluation results from different evaluators.
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
">

        <?php
        $feedbackRecords = $employeePerformanceFeedback ?? [];

        $totalFeedback = count($feedbackRecords);

        $totalRating = 0;

        foreach ($feedbackRecords as $feedback) {
            $totalRating += (int) ($feedback['rating'] ?? 0);
        }

        $averageRating = $totalFeedback > 0
            ? round($totalRating / $totalFeedback, 1)
            : 0;

        $ratingPercentage = $averageRating > 0
            ? min(100, ($averageRating / 5) * 100)
            : 0;
        ?>


        <!-- SUMMARY CARDS -->
        <div style="
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        margin-bottom:22px;
    ">

            <!-- TOTAL EVALUATIONS -->
            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-sizing:border-box;
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:10px;
            ">

                    <div>

                        <span style="
                        display:block;
                        margin-bottom:5px;
                        color:#9ca3af;
                        font-size:9px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Evaluation Records
                        </span>

                        <strong style="
                        display:block;
                        color:#111827;
                        font-size:24px;
                        line-height:1;
                    ">
                            <?= $totalFeedback ?>
                        </strong>

                        <span style="
                        display:block;
                        margin-top:6px;
                        color:#6b7280;
                        font-size:9px;
                    ">
                            Total feedback received
                        </span>

                    </div>


                    <div style="
                    width:42px;
                    height:42px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:11px;
                    background:#eff6ff;
                    color:#2563eb;
                    font-size:16px;
                ">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                </div>

            </div>


            <!-- AVERAGE RATING -->
            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-sizing:border-box;
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:10px;
            ">

                    <div>

                        <span style="
                        display:block;
                        margin-bottom:5px;
                        color:#9ca3af;
                        font-size:9px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Average Rating
                        </span>

                        <strong style="
                        display:block;
                        color:#111827;
                        font-size:24px;
                        line-height:1;
                    ">
                            <?= number_format($averageRating, 1) ?>
                            <span style="
                            color:#9ca3af;
                            font-size:12px;
                            font-weight:500;
                        ">
                                / 5
                            </span>
                        </strong>

                        <div style="
                        display:flex;
                        align-items:center;
                        gap:3px;
                        margin-top:7px;
                    ">

                            <?php for ($i = 1; $i <= 5; $i++): ?>

                                <i class="fas fa-star" style="
                                color:<?= $i <= round($averageRating)
                                    ? '#f59e0b'
                                    : '#e5e7eb' ?>;
                                font-size:9px;
                            "></i>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <div style="
                    width:42px;
                    height:42px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:11px;
                    background:#fffbeb;
                    color:#d97706;
                    font-size:16px;
                ">
                        <i class="fas fa-star"></i>
                    </div>

                </div>

            </div>


            <!-- PERFORMANCE STATUS -->
            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-sizing:border-box;
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:10px;
            ">

                    <div>

                        <span style="
                        display:block;
                        margin-bottom:5px;
                        color:#9ca3af;
                        font-size:9px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Performance Status
                        </span>

                        <?php
                        if ($averageRating >= 4.5) {
                            $performanceLabel = 'Excellent';
                            $performanceColor = '#166534';
                            $performanceBg = '#dcfce7';
                        } elseif ($averageRating >= 3.5) {
                            $performanceLabel = 'Very Good';
                            $performanceColor = '#2563eb';
                            $performanceBg = '#eff6ff';
                        } elseif ($averageRating >= 2.5) {
                            $performanceLabel = 'Satisfactory';
                            $performanceColor = '#b45309';
                            $performanceBg = '#fef3c7';
                        } elseif ($averageRating > 0) {
                            $performanceLabel = 'Needs Improvement';
                            $performanceColor = '#dc2626';
                            $performanceBg = '#fef2f2';
                        } else {
                            $performanceLabel = 'No Rating';
                            $performanceColor = '#6b7280';
                            $performanceBg = '#f3f4f6';
                        }
                        ?>

                        <span style="
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                        padding:6px 9px;
                        border-radius:20px;
                        background:<?= $performanceBg ?>;
                        color:<?= $performanceColor ?>;
                        font-size:10px;
                        font-weight:700;
                    ">
                            <i class="fas fa-circle" style="font-size:5px;"></i>
                            <?= $performanceLabel ?>
                        </span>

                        <span style="
                        display:block;
                        margin-top:7px;
                        color:#6b7280;
                        font-size:9px;
                    ">
                            Based on available feedback
                        </span>

                    </div>


                    <div style="
                    width:42px;
                    height:42px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:11px;
                    background:#f0fdf4;
                    color:#16a34a;
                    font-size:16px;
                ">
                        <i class="fas fa-chart-pie"></i>
                    </div>

                </div>

            </div>

        </div>


        <!-- FEEDBACK SECTION -->
        <div style="
        margin-bottom:12px;
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:15px;
        flex-wrap:wrap;
    ">

            <div>

                <span style="
                display:block;
                margin-bottom:4px;
                color:#2563eb;
                font-size:9px;
                font-weight:700;
                letter-spacing:.07em;
            ">
                    EVALUATION HISTORY
                </span>

                <h2 style="
                margin:0;
                color:#111827;
                font-size:17px;
                font-weight:700;
            ">
                    360° Feedback Records
                </h2>

                <p style="
                margin:4px 0 0;
                color:#9ca3af;
                font-size:10px;
            ">
                    Detailed feedback submitted as part of your performance evaluation.
                </p>

            </div>

        </div>


        <?php if (!empty($feedbackRecords)): ?>

            <!-- TABLE -->
            <div style="
    width:100%;
    max-width:100%;
    box-sizing:border-box;
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 #f8fafc;

    border:1px solid #e5e7eb;
    border-radius:14px;
    background:#fff;

    box-shadow:0 4px 16px rgba(15,23,42,.04);
">

                <table style="
        width:100%;
        min-width:850px;
        max-width:none;

        border-collapse:separate;
        border-spacing:0;

        table-layout:auto;

        font-size:11px;
        color:#374151;

        white-space:nowrap;
    

                    <thead>

                        <tr style=" background:#f8fafc; border-bottom:1px solid #e5e7eb; ">

                                <th style=" padding:14px 16px; text-align:left; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Evaluator
                                </th>

                                <th style=" padding:14px 16px; text-align:left; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Competency
                                </th>

                                <th style=" padding:14px 16px; text-align:center; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Rating
                                </th>

                                <th style=" padding:14px 16px; text-align:left; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Feedback
                                </th>

                                <th style=" padding:14px 16px; text-align:center; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Privacy
                                </th>

                                <th style=" padding:14px 16px; text-align:left; color:#64748b; font-size:9px;
                font-weight:700; text-transform:uppercase; letter-spacing:.05em; ">
                                    Evaluation Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($feedbackRecords as $row): ?>

                                    <?php
                                    $rating = (int) ($row['rating'] ?? 0);

                                    $anonymous =
                                        (int) ($row['is_anonymous'] ?? 0) === 1;

                                    $ratingColor = '#6b7280';
                                    $ratingBg = '#f3f4f6';
                                    $ratingLabel = 'Not Rated';

                                    if ($rating >= 5) {
                                        $ratingColor = '#166534';
                                        $ratingBg = '#dcfce7';
                                        $ratingLabel = 'Excellent';
                                    } elseif ($rating >= 4) {
                                        $ratingColor = '#2563eb';
                                        $ratingBg = '#eff6ff';
                                        $ratingLabel = 'Very Good';
                                    } elseif ($rating >= 3) {
                                        $ratingColor = '#b45309';
                                        $ratingBg = '#fef3c7';
                                        $ratingLabel = 'Satisfactory';
                                    } elseif ($rating > 0) {
                                        $ratingColor = '#dc2626';
                                        $ratingBg = '#fef2f2';
                                        $ratingLabel = 'Needs Improvement';
                                    }
                                    ?>

                                    <tr style=" border-bottom:1px solid #f1f5f9; transition:.18s ease; " onmouseover="
                this.style.background='#f8fafc' ; " onmouseout=" this.style.background='#fff' ; ">

                                        <!-- EVALUATOR -->
                                        <td style=" padding:16px; vertical-align:middle; ">

                                            <div style=" display:flex; align-items:center; gap:10px; ">

                                                <div style=" width:38px; height:38px; min-width:38px; display:flex;
                align-items:center; justify-content:center; border-radius:10px; background:#eff6ff; color:#2563eb;
                font-size:13px; ">
                                                    <i class=" fas fa-user"></i>
                </div>

                <div>

                    <div style="
                                            color:#111827;
                                            font-size:11px;
                                            font-weight:700;
                                        ">
                        <?= $anonymous
                            ? 'Anonymous Evaluator'
                            : htmlspecialchars(
                                $row['evaluator_type']
                                ?? 'Evaluator'
                            ) ?>
                    </div>

                    <div style="
                                            margin-top:3px;
                                            color:#94a3b8;
                                            font-size:8px;
                                        ">
                        Performance Feedback
                    </div>

                </div>

        </div>

        </td>


        <!-- CATEGORY -->
        <td style="
                                padding:16px;
                                vertical-align:middle;
                            ">

            <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:6px;
                                    padding:6px 9px;
                                    border-radius:8px;
                                    background:#f8fafc;
                                    border:1px solid #e5e7eb;
                                    color:#374151;
                                    font-size:10px;
                                    font-weight:600;
                                ">
                <i class="fas fa-layer-group" style="color:#64748b;font-size:9px;">
                </i>

                <?= htmlspecialchars(
                    $row['category'] ?? '-'
                ) ?>
            </span>

        </td>


        <!-- RATING -->
        <td style="
                                padding:16px;
                                text-align:center;
                                vertical-align:middle;
                            ">

            <div style="
                                    display:flex;
                                    flex-direction:column;
                                    align-items:center;
                                    gap:4px;
                                ">

                <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:5px;
                                        min-width:48px;
                                        justify-content:center;
                                        padding:6px 9px;
                                        border-radius:8px;
                                        background:<?= $ratingBg ?>;
                                        color:<?= $ratingColor ?>;
                                        font-size:11px;
                                        font-weight:700;
                                    ">
                    <i class="fas fa-star" style="font-size:8px;">
                    </i>

                    <?= $rating ?>/5
                </span>

                <span style="
                                        color:#94a3b8;
                                        font-size:8px;
                                    ">
                    <?= $ratingLabel ?>
                </span>

            </div>

        </td>


        <!-- FEEDBACK -->
        <td style="
                                padding:16px;
                                max-width:350px;
                                vertical-align:middle;
                            ">

            <div style="
                                    color:#475569;
                                    font-size:10px;
                                    line-height:1.55;
                                    max-width:330px;
                                ">
                <?= htmlspecialchars(
                    $row['comments'] ?? 'No comments provided.'
                ) ?>
            </div>

        </td>


        <!-- PRIVACY -->
        <td style="
                                padding:16px;
                                text-align:center;
                                vertical-align:middle;
                            ">

            <?php if ($anonymous): ?>

                <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:5px;
                                        padding:6px 9px;
                                        border-radius:20px;
                                        background:#f1f5f9;
                                        color:#64748b;
                                        font-size:9px;
                                        font-weight:600;
                                    ">
                    <i class="fas fa-user-secret"></i>
                    Anonymous
                </span>

            <?php else: ?>

                <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:5px;
                                        padding:6px 9px;
                                        border-radius:20px;
                                        background:#eff6ff;
                                        color:#2563eb;
                                        font-size:9px;
                                        font-weight:600;
                                    ">
                    <i class="fas fa-user"></i>
                    Identified
                </span>

            <?php endif; ?>

        </td>


        <!-- DATE -->
        <td style="
                                padding:16px;
                                vertical-align:middle;
                            ">

            <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:7px;
                                    color:#475569;
                                    font-size:10px;
                                    font-weight:600;
                                ">

                <i class="far fa-calendar-alt" style="color:#94a3b8;">
                </i>

                <?= !empty($row['evaluation_date'])
                    ? date(
                        'M d, Y',
                        strtotime(
                            $row['evaluation_date']
                        )
                    )
                    : '-' ?>

            </div>

            <?php if (!empty($row['created_at'])): ?>

                <div style="
                                        margin-top:4px;
                                        color:#94a3b8;
                                        font-size:8px;
                                    ">
                    Recorded
                    <?= date(
                        'M d, Y',
                        strtotime(
                            $row['created_at']
                        )
                    ) ?>
                </div>

            <?php endif; ?>

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
            padding:70px 25px;
            box-sizing:border-box;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
        ">

        <div style="
                width:70px;
                height:70px;
                margin:0 auto 16px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:18px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:27px;
            ">
            <i class="fas fa-chart-line"></i>
        </div>

        <h3 style="
                margin:0 0 6px;
                color:#1e293b;
                font-size:16px;
                font-weight:700;
            ">
            No Performance Evaluations Yet
        </h3>

        <p style="
                max-width:430px;
                margin:0 auto;
                color:#94a3b8;
                font-size:10px;
                line-height:1.6;
            ">
            Your 360° performance feedback and evaluation records
            will appear here once an assessment has been completed.
        </p>

    </div>

<?php endif; ?>

</section>


</div>