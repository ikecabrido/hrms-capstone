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

    <section class="dashboard-section" style="width:100%;box-sizing:border-box;">

        <?php
        $feedbackRecords = $employeePerformanceFeedback ?? [];

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalFeedback = count($feedbackRecords);

        $totalRating = 0;
        $ratedCount = 0;

        foreach ($feedbackRecords as $feedback) {
            $rating = isset($feedback['overall_rating'])
                ? (float) $feedback['overall_rating']
                : (float) ($feedback['rating'] ?? 0);

            if ($rating > 0) {
                $totalRating += $rating;
                $ratedCount++;
            }
        }

        $averageRating = $ratedCount > 0
            ? round($totalRating / $ratedCount, 2)
            : 0;

        $ratingPercentage = $averageRating > 0
            ? min(100, ($averageRating / 5) * 100)
            : 0;


        /*
        |--------------------------------------------------------------------------
        | PERFORMANCE STATUS
        |--------------------------------------------------------------------------
        */

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
            $performanceColor = '#64748b';
            $performanceBg = '#f1f5f9';
        }
        ?>


        <!-- =========================================================
         HEADER
    ========================================================== -->

        <div style="
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:20px;
        margin-bottom:20px;
        flex-wrap:wrap;
    ">

            <div>

                <div style="
                display:flex;
                align-items:center;
                gap:7px;
                margin-bottom:6px;
                color:#2563eb;
                font-size:9px;
                font-weight:800;
                text-transform:uppercase;
                letter-spacing:.08em;
            ">

                    <i class="fas fa-chart-line"></i>

                    Performance Management

                </div>

                <h2 style="
                margin:0;
                color:#111827;
                font-size:20px;
                font-weight:750;
                letter-spacing:-.02em;
            ">
                    360° Performance Feedback
                </h2>

                <p style="
                margin:5px 0 0;
                color:#64748b;
                font-size:11px;
            ">
                    Review your performance evaluations, ratings, and development feedback.
                </p>

            </div>

            <div style="
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:7px 11px;
            border:1px solid #e5e7eb;
            border-radius:9px;
            background:#fff;
            color:#64748b;
            font-size:10px;
            font-weight:600;
        ">

                <i class="fas fa-clock" style="color:#94a3b8;"></i>

                <?= $totalFeedback ?>

                <?= $totalFeedback === 1 ? 'Evaluation' : 'Evaluations' ?>

            </div>

        </div>


        <!-- =========================================================
         SUMMARY CARDS
    ========================================================== -->

        <div class="performance-summary-grid" style="
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        margin-bottom:24px;
    ">


            <!-- TOTAL -->

            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-shadow:0 2px 8px rgba(15,23,42,.03);
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
            ">

                    <div>

                        <div style="
                        margin-bottom:6px;
                        color:#94a3b8;
                        font-size:9px;
                        font-weight:800;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Evaluation Records
                        </div>

                        <div style="
                        color:#111827;
                        font-size:25px;
                        line-height:1;
                        font-weight:750;
                    ">
                            <?= $totalFeedback ?>
                        </div>

                        <div style="
                        margin-top:7px;
                        color:#64748b;
                        font-size:9px;
                    ">
                            Total feedback received
                        </div>

                    </div>

                    <div style="
                    width:44px;
                    height:44px;
                    min-width:44px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:12px;
                    background:#eff6ff;
                    color:#2563eb;
                    font-size:16px;
                ">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                </div>

            </div>


            <!-- AVERAGE -->

            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-shadow:0 2px 8px rgba(15,23,42,.03);
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
            ">

                    <div>

                        <div style="
                        margin-bottom:6px;
                        color:#94a3b8;
                        font-size:9px;
                        font-weight:800;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Average Rating
                        </div>

                        <div style="
                        color:#111827;
                        font-size:25px;
                        line-height:1;
                        font-weight:750;
                    ">

                            <?= number_format($averageRating, 2) ?>

                            <span style="
                            color:#94a3b8;
                            font-size:11px;
                            font-weight:500;
                        ">
                                / 5
                            </span>

                        </div>


                        <div style="
                        display:flex;
                        align-items:center;
                        gap:3px;
                        margin-top:8px;
                    ">

                            <?php for ($i = 1; $i <= 5; $i++): ?>

                                <i class="fas fa-star" style="
                                    color:<?= $i <= round($averageRating)
                                        ? '#f59e0b'
                                        : '#e5e7eb' ?>;
                                    font-size:10px;
                                "></i>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <div style="
                    width:44px;
                    height:44px;
                    min-width:44px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:12px;
                    background:#fffbeb;
                    color:#d97706;
                    font-size:16px;
                ">
                        <i class="fas fa-star"></i>
                    </div>

                </div>

            </div>


            <!-- PERFORMANCE -->

            <div style="
            padding:18px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
            box-shadow:0 2px 8px rgba(15,23,42,.03);
        ">

                <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
            ">

                    <div>

                        <div style="
                        margin-bottom:7px;
                        color:#94a3b8;
                        font-size:9px;
                        font-weight:800;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    ">
                            Performance Status
                        </div>

                        <span style="
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                        padding:6px 10px;
                        border-radius:20px;
                        background:<?= $performanceBg ?>;
                        color:<?= $performanceColor ?>;
                        font-size:10px;
                        font-weight:750;
                    ">

                            <i class="fas fa-circle" style="font-size:5px;"></i>

                            <?= htmlspecialchars($performanceLabel) ?>

                        </span>

                        <div style="
                        margin-top:7px;
                        color:#64748b;
                        font-size:9px;
                    ">
                            Based on <?= $ratedCount ?> rated evaluation<?= $ratedCount === 1 ? '' : 's' ?>
                        </div>

                    </div>


                    <div style="
                    width:44px;
                    height:44px;
                    min-width:44px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:12px;
                    background:#f0fdf4;
                    color:#16a34a;
                    font-size:16px;
                ">
                        <i class="fas fa-chart-pie"></i>
                    </div>

                </div>

            </div>

        </div>



        <!-- =========================================================
         TABLE HEADER
    ========================================================== -->

        <div style="
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:15px;
        margin-bottom:12px;
        flex-wrap:wrap;
    ">

            <div>

                <div style="
                margin-bottom:4px;
                color:#2563eb;
                font-size:9px;
                font-weight:800;
                letter-spacing:.07em;
                text-transform:uppercase;
            ">
                    Evaluation History
                </div>

                <h3 style="
                margin:0;
                color:#111827;
                font-size:16px;
                font-weight:750;
            ">
                    Feedback Records
                </h3>

            </div>

        </div>



        <?php if (!empty($feedbackRecords)): ?>


            <!-- =====================================================
             TABLE CONTAINER
        ====================================================== -->

            <div style="
    width:100%;
    max-width:100%;
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;

    border:1px solid #e5e7eb;
    border-radius:14px;
    background:#fff;

    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 #f8fafc;
">

                <table style="
        width:100%;
        min-width:1050px;
        border-collapse:separate;
        border-spacing:0;
        table-layout:auto;
        color:#374151;
        font-size:11px;
        white-space:nowrap;
    ">

                    <!-- =================================================
                     STICKY HEADER
                ================================================== -->

                    <thead>

                        <tr style="
                        background:#f8fafc;
                    ">

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:left;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Evaluator
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:left;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Category
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:center;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Rating
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:left;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Feedback
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:center;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Status
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:left;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Period
                            </th>

                            <th style="
                            position:sticky;
                            top:0;
                            z-index:5;
                            padding:13px 16px;
                            text-align:center;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-size:9px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:#f8fafc;
                        ">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($feedbackRecords as $row): ?>

                            <?php

                            $feedbackId = (int) ($row['feedback_id'] ?? 0);

                            $rating = isset($row['overall_rating'])
                                ? (float) $row['overall_rating']
                                : (float) ($row['rating'] ?? 0);

                            $anonymous =
                                (int) ($row['is_anonymous'] ?? 0) === 1;

                            $evaluator = $anonymous
                                ? 'Anonymous Evaluator'
                                : ($row['reviewer_name']
                                    ?? $row['reviewer_type']
                                    ?? 'Evaluator');

                            $category =
                                $row['feedback_category']
                                ?? $row['category']
                                ?? '-';

                            $comments =
                                $row['comments']
                                ?? 'No comments provided.';

                            $status =
                                $row['feedback_status']
                                ?? 'Submitted';

                            $period =
                                $row['review_period']
                                ?? '-';


                            /*
                            | Rating styling
                            */

                            if ($rating >= 4.5) {

                                $ratingColor = '#166534';
                                $ratingBg = '#dcfce7';
                                $ratingLabel = 'Excellent';

                            } elseif ($rating >= 3.5) {

                                $ratingColor = '#2563eb';
                                $ratingBg = '#eff6ff';
                                $ratingLabel = 'Very Good';

                            } elseif ($rating >= 2.5) {

                                $ratingColor = '#b45309';
                                $ratingBg = '#fef3c7';
                                $ratingLabel = 'Satisfactory';

                            } elseif ($rating > 0) {

                                $ratingColor = '#dc2626';
                                $ratingBg = '#fef2f2';
                                $ratingLabel = 'Needs Improvement';

                            } else {

                                $ratingColor = '#64748b';
                                $ratingBg = '#f1f5f9';
                                $ratingLabel = 'Not Rated';
                            }


                            /*
                            | Status styling
                            */

                            $statusLower = strtolower(trim($status));

                            if ($statusLower === 'approved' || $statusLower === 'completed') {

                                $statusColor = '#166534';
                                $statusBg = '#dcfce7';

                            } elseif ($statusLower === 'submitted') {

                                $statusColor = '#2563eb';
                                $statusBg = '#eff6ff';

                            } elseif ($statusLower === 'draft') {

                                $statusColor = '#64748b';
                                $statusBg = '#f1f5f9';

                            } elseif ($statusLower === 'rejected') {

                                $statusColor = '#dc2626';
                                $statusBg = '#fef2f2';

                            } else {

                                $statusColor = '#b45309';
                                $statusBg = '#fef3c7';
                            }

                            ?>

                            <!-- =================================================
                             ROW
                        ================================================== -->

                            <tr style="
                                transition:background .15s ease;
                            " onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">


                                <!-- EVALUATOR -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                vertical-align:middle;
                            ">

                                    <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:10px;
                                ">

                                        <div style="
                                        width:36px;
                                        height:36px;
                                        min-width:36px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        border-radius:10px;
                                        background:<?= $anonymous ? '#f1f5f9' : '#eff6ff' ?>;
                                        color:<?= $anonymous ? '#64748b' : '#2563eb' ?>;
                                    ">

                                            <i class="fas <?= $anonymous ? 'fa-user-secret' : 'fa-user-check' ?>"></i>

                                        </div>

                                        <div>

                                            <div style="
                                            color:#111827;
                                            font-size:11px;
                                            font-weight:700;
                                        ">
                                                <?= htmlspecialchars($evaluator) ?>
                                            </div>

                                            <div style="
                                            margin-top:3px;
                                            color:#94a3b8;
                                            font-size:8px;
                                        ">
                                                <?= htmlspecialchars($row['department'] ?? 'Performance Evaluation') ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- CATEGORY -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                vertical-align:middle;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:6px;
                                    padding:6px 9px;
                                    border:1px solid #e5e7eb;
                                    border-radius:8px;
                                    background:#f8fafc;
                                    color:#475569;
                                    font-size:9px;
                                    font-weight:650;
                                ">

                                        <i class="fas fa-layer-group" style="
                                            color:#94a3b8;
                                            font-size:8px;
                                        "></i>

                                        <?= htmlspecialchars($category) ?>

                                    </span>

                                </td>


                                <!-- RATING -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
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
                                        padding:6px 9px;
                                        border-radius:8px;
                                        background:<?= $ratingBg ?>;
                                        color:<?= $ratingColor ?>;
                                        font-size:10px;
                                        font-weight:750;
                                    ">

                                            <i class="fas fa-star" style="font-size:8px;"></i>

                                            <?= number_format($rating, 1) ?>/5

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
                                width:320px;
                                max-width:320px;
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                vertical-align:middle;
                            ">

                                    <div style="
                                    max-width:300px;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                    white-space:nowrap;
                                    color:#475569;
                                    font-size:10px;
                                ">
                                        <?= htmlspecialchars($comments) ?>
                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                text-align:center;
                                vertical-align:middle;
                            ">

                                    <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;
                                    padding:6px 9px;
                                    border-radius:20px;
                                    background:<?= $statusBg ?>;
                                    color:<?= $statusColor ?>;
                                    font-size:9px;
                                    font-weight:700;
                                ">

                                        <i class="fas fa-circle" style="font-size:5px;"></i>

                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>

                                    </span>

                                </td>


                                <!-- PERIOD -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                vertical-align:middle;
                            ">

                                    <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:6px;
                                    color:#475569;
                                    font-size:10px;
                                    font-weight:600;
                                ">

                                        <i class="far fa-calendar-alt" style="color:#94a3b8;"></i>

                                        <?= htmlspecialchars($period) ?>

                                    </div>

                                    <?php if (!empty($row['created_at'])): ?>

                                        <div style="
                                        margin-top:4px;
                                        color:#94a3b8;
                                        font-size:8px;
                                    ">

                                            <?= date(
                                                'M d, Y',
                                                strtotime($row['created_at'])
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTION -->

                                <td style="
                                padding:15px 16px;
                                border-bottom:1px solid #f1f5f9;
                                text-align:center;
                                vertical-align:middle;
                            ">

                                    <button type="button" data-bs-toggle="modal"
                                        data-bs-target="#feedbackModal<?= $feedbackId ?>" style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:6px;
                                        padding:7px 11px;
                                        border:1px solid #dbeafe;
                                        border-radius:8px;
                                        background:#eff6ff;
                                        color:#2563eb;
                                        font-size:9px;
                                        font-weight:700;
                                        cursor:pointer;
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


            <!-- =========================================================
             DETAIL MODALS
        ========================================================== -->

            <?php require __DIR__ . '/view.php'; ?>


        <?php else: ?>


            <!-- =====================================================
             EMPTY STATE
        ====================================================== -->

            <div style="
            padding:65px 25px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fff;
        ">

                <div style="
                width:64px;
                height:64px;
                margin:0 auto 15px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:17px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:24px;
            ">
                    <i class="fas fa-chart-line"></i>
                </div>

                <h3 style="
                margin:0 0 6px;
                color:#1e293b;
                font-size:15px;
                font-weight:750;
            ">
                    No Performance Evaluations Yet
                </h3>

                <p style="
                max-width:430px;
                margin:0 auto;
                color:#94a3b8;
                font-size:10px;
                line-height:1.7;
            ">
                    Your 360° performance feedback and evaluation records
                    will appear here once an assessment has been completed.
                </p>

            </div>

        <?php endif; ?>

    </section>


    <!-- =============================================================
     RESPONSIVE
============================================================= -->

    <style>
        .performance-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        @media (max-width: 900px) {

            .performance-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

        }

        @media (max-width: 600px) {

            .performance-summary-grid {
                grid-template-columns: 1fr !important;
            }

        }

        .performance-table-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;

            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;

            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
        }

        /* Chrome, Edge, Safari */
        .performance-table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .performance-table-wrapper::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .performance-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .performance-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .performance-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
            color: #374151;
            font-size: 11px;
            white-space: nowrap;
        }
    </style>
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