<div class="employee-dashboard">
    <div class="cursor-tracer"> </div>
    <section class="dashboard-welcome" id="dashboardWelcome">

        <!-- Decorative animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                EMPLOYEE PORTAL
            </span>

            <h1 id="welcomeTitle">
                Welcome back,
                <span>
                    <?= htmlspecialchars($employeeDashboard['first_name'] ?? 'Employee'); ?>
                </span>
            </h1>

            <p id="welcomeDescription">
                Manage your employee services, records, requests, and activities.
            </p>

            <div class="welcome-line"></div>

        </div>

        <!-- Decorative icon -->
        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-user-tie"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../partials/notification.php'; ?>

    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>ANNOUNCEMENTS</span>
                <h2>Latest Announcements</h2>
            </div>

            <a href="index.php?url=announcement" style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 11px;
            border:1px solid #e5e7eb;
            border-radius:8px;
            background:#fff;
            color:#374151;
            text-decoration:none;
            font-size:11px;
            font-weight:600;
        ">
                View All
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>


        <?php if (!empty($announcements)): ?>

            <div style="
            display:flex;
            flex-direction:column;
            gap:10px;
        ">

                <?php foreach (array_slice($announcements, 0, 5) as $announcement): ?>

                    <a href="index.php?url=announcement-view&id=<?= (int) $announcement['eer_announcements_id'] ?>" style="
                        display:flex;
                        align-items:center;
                        gap:13px;
                        width:100%;
                        padding:14px 15px;
                        box-sizing:border-box;
                        border:1px solid #e5e7eb;
                        border-radius:11px;
                        background:#fff;
                        text-decoration:none;
                        transition:.2s ease;
                    " onmouseover="
                        this.style.background='#f8fafc';
                        this.style.borderColor='#bfdbfe';
                    " onmouseout="
                        this.style.background='#fff';
                        this.style.borderColor='#e5e7eb';
                    ">

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
                        font-size:15px;
                    ">
                            <i class="fas fa-bullhorn"></i>
                        </div>


                        <!-- CONTENT -->
                        <div style="
                        flex:1;
                        min-width:0;
                    ">

                            <h3 style="
                            margin:0 0 5px;
                            color:#111827;
                            font-size:13px;
                            font-weight:700;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        ">
                                <?= htmlspecialchars(
                                    $announcement['title'] ?? 'Announcement'
                                ) ?>
                            </h3>


                            <p style="
                            margin:0;
                            color:#6b7280;
                            font-size:11px;
                            line-height:1.5;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        ">
                                <?= htmlspecialchars(
                                    $announcement['content'] ?? ''
                                ) ?>
                            </p>


                            <!-- META -->
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:12px;
                            margin-top:6px;
                            color:#9ca3af;
                            font-size:9px;
                        ">

                                <span>
                                    <i class="far fa-clock"></i>

                                    <?= !empty($announcement['created_at'])
                                        ? date(
                                            'M d, Y h:i A',
                                            strtotime($announcement['created_at'])
                                        )
                                        : '-' ?>
                                </span>


                                <span>
                                    <i class="fas fa-users"></i>

                                    <?= htmlspecialchars(
                                        $announcement['target_audience'] ?? 'All Employees'
                                    ) ?>
                                </span>

                            </div>

                        </div>


                        <!-- ARROW -->
                        <i class="fas fa-chevron-right" style="
                        color:#9ca3af;
                        font-size:10px;
                    "></i>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            padding:35px 20px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:11px;
            background:#fff;
            box-sizing:border-box;
        ">

                <div style="
                width:48px;
                height:48px;
                margin:0 auto 12px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:12px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:20px;
            ">
                    <i class="fas fa-bullhorn"></i>
                </div>

                <h3 style="
                margin:0 0 4px;
                color:#374151;
                font-size:13px;
                font-weight:700;
            ">
                    No Announcements
                </h3>

                <p style="
                margin:0;
                color:#9ca3af;
                font-size:10px;
            ">
                    There are no announcements available at this time.
                </p>

            </div>

        <?php endif; ?>

    </section>

    <section class="dashboard-section">

        <?php

        $employeePerformanceFeedback = is_array($employeePerformanceFeedback ?? null)
            ? $employeePerformanceFeedback
            : [];

        // Sort newest evaluation first
        usort($employeePerformanceFeedback, function ($a, $b) {
            return strtotime($b['created_at'] ?? '1970-01-01')
                <=> strtotime($a['created_at'] ?? '1970-01-01');
        });

        $latestEvaluation = $employeePerformanceFeedback[0] ?? null;

        $latestRating = $latestEvaluation
            ? (float) ($latestEvaluation['overall_rating']
                ?? $latestEvaluation['rating']
                ?? 0)
            : 0;

        $ratingPercentage = min(100, ($latestRating / 5) * 100);

        $status = $latestEvaluation['feedback_status'] ?? 'No Evaluation';

        $statusClass = match (strtolower(str_replace('_', ' ', $status))) {
            'completed', 'approved', 'closed' => 'pe-status-success',
            'pending', 'under review', 'under initial review' => 'pe-status-warning',
            'rejected', 'cancelled' => 'pe-status-danger',
            default => 'pe-status-neutral'
        };

        $competencyScores = [];

        if ($latestEvaluation && !empty($latestEvaluation['competency_scores'])) {
            $decodedScores = json_decode(
                $latestEvaluation['competency_scores'],
                true
            );

            if (is_array($decodedScores)) {
                $competencyScores = $decodedScores;
            }
        }

        $ratingLabel = match (true) {
            $latestRating >= 4.5 => 'Outstanding',
            $latestRating >= 4.0 => 'Excellent',
            $latestRating >= 3.0 => 'Good',
            $latestRating >= 2.0 => 'Needs Improvement',
            $latestRating > 0 => 'Unsatisfactory',
            default => 'Not Rated'
        };
        ?>

        <style>
            .performance-dashboard {
                width: 100%;
                margin-top: 20px;
            }

            .performance-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
            }

            .performance-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 18px 20px;
                border-bottom: 1px solid #eef0f3;
            }

            .performance-title {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .performance-icon {
                width: 42px;
                height: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 11px;
                background: #eff6ff;
                color: #2563eb;
                font-size: 16px;
            }

            .performance-title h3 {
                margin: 0;
                color: #111827;
                font-size: 15px;
                font-weight: 750;
            }

            .performance-title p {
                margin: 3px 0 0;
                color: #94a3b8;
                font-size: 10px;
            }

            .performance-view-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 12px;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                background: #fff;
                color: #2563eb;
                font-size: 10px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                transition: .2s ease;
            }

            .performance-view-btn:hover {
                background: #eff6ff;
                border-color: #bfdbfe;
                color: #1d4ed8;
            }

            .performance-body {
                padding: 20px;
            }

            .performance-main-grid {
                display: grid;
                grid-template-columns: 250px minmax(0, 1fr);
                gap: 18px;
            }

            .performance-rating {
                padding: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 13px;
                background: #f8fafc;
            }

            .performance-label {
                margin-bottom: 7px;
                color: #94a3b8;
                font-size: 9px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .performance-rating-number {
                display: flex;
                align-items: baseline;
                gap: 4px;
            }

            .performance-rating-number strong {
                color: #111827;
                font-size: 32px;
                line-height: 1;
                font-weight: 800;
            }

            .performance-rating-number span {
                color: #94a3b8;
                font-size: 12px;
            }

            .performance-stars {
                display: flex;
                gap: 3px;
                margin: 10px 0;
            }

            .performance-stars i {
                font-size: 12px;
            }

            .performance-rating-label {
                display: inline-flex;
                padding: 5px 8px;
                border-radius: 6px;
                background: #dbeafe;
                color: #1d4ed8;
                font-size: 9px;
                font-weight: 750;
            }

            .performance-progress {
                margin-top: 14px;
            }

            .performance-progress-track {
                width: 100%;
                height: 6px;
                overflow: hidden;
                border-radius: 20px;
                background: #e5e7eb;
            }

            .performance-progress-bar {
                height: 100%;
                border-radius: 20px;
                background: #2563eb;
            }

            .performance-info-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .performance-info {
                padding: 12px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #fff;
            }

            .performance-info-label {
                margin-bottom: 5px;
                color: #94a3b8;
                font-size: 8px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .performance-info-value {
                color: #374151;
                font-size: 10px;
                font-weight: 700;
            }

            .pe-status-success,
            .pe-status-warning,
            .pe-status-danger,
            .pe-status-neutral {
                display: inline-flex;
                align-items: center;
                padding: 4px 7px;
                border-radius: 6px;
                font-size: 8px;
                font-weight: 750;
            }

            .pe-status-success {
                background: #dcfce7;
                color: #166534;
            }

            .pe-status-warning {
                background: #fef3c7;
                color: #92400e;
            }

            .pe-status-danger {
                background: #fee2e2;
                color: #991b1b;
            }

            .pe-status-neutral {
                background: #f1f5f9;
                color: #475569;
            }

            .performance-section {
                margin-top: 20px;
            }

            .performance-section-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .performance-section-header h4 {
                margin: 0;
                color: #111827;
                font-size: 11px;
                font-weight: 750;
            }

            .performance-section-header span {
                color: #94a3b8;
                font-size: 9px;
            }

            .competency-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .competency-item {
                padding: 12px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #fff;
            }

            .competency-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 7px;
            }

            .competency-name {
                color: #475569;
                font-size: 9px;
                font-weight: 650;
            }

            .competency-score {
                color: #111827;
                font-size: 9px;
                font-weight: 750;
            }

            .competency-track {
                height: 5px;
                overflow: hidden;
                border-radius: 20px;
                background: #e5e7eb;
            }

            .competency-bar {
                height: 100%;
                border-radius: 20px;
                background: #2563eb;
            }

            .performance-insights {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
            }

            .performance-insight {
                padding: 13px;
                border-radius: 10px;
                border: 1px solid;
            }

            .performance-insight h5 {
                margin: 0 0 6px;
                font-size: 9px;
                font-weight: 750;
            }

            .performance-insight p {
                margin: 0;
                font-size: 9px;
                line-height: 1.6;
            }

            .insight-strength {
                border-color: #bbf7d0;
                background: #f0fdf4;
                color: #166534;
            }

            .insight-improvement {
                border-color: #fde68a;
                background: #fffbeb;
                color: #92400e;
            }

            .insight-recommendation {
                border-color: #dbeafe;
                background: #eff6ff;
                color: #1e40af;
            }

            .performance-empty {
                padding: 35px 20px;
                text-align: center;
            }

            .performance-empty-icon {
                width: 48px;
                height: 48px;
                margin: 0 auto 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #eff6ff;
                color: #2563eb;
                font-size: 18px;
            }

            .performance-empty h4 {
                margin: 0 0 5px;
                color: #111827;
                font-size: 13px;
                font-weight: 750;
            }

            .performance-empty p {
                margin: 0;
                color: #94a3b8;
                font-size: 10px;
            }

            .recent-evaluations {
                margin-top: 20px;
                border-top: 1px solid #eef0f3;
                padding-top: 18px;
            }

            .evaluation-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                padding: 11px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .evaluation-row:last-child {
                border-bottom: 0;
            }

            .evaluation-period {
                color: #374151;
                font-size: 10px;
                font-weight: 700;
            }

            .evaluation-date {
                margin-top: 3px;
                color: #94a3b8;
                font-size: 8px;
            }

            .evaluation-right {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .evaluation-rating {
                color: #111827;
                font-size: 10px;
                font-weight: 800;
            }

            @media (max-width: 900px) {
                .performance-main-grid {
                    grid-template-columns: 1fr;
                }

                .performance-insights {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 600px) {
                .performance-header {
                    align-items: flex-start;
                }

                .performance-info-grid,
                .competency-grid {
                    grid-template-columns: 1fr;
                }

                .performance-body {
                    padding: 14px;
                }

                .evaluation-row {
                    align-items: flex-start;
                }
            }
        </style>

        <div class="performance-dashboard">

            <div class="performance-card">

                <div class="performance-header">

                    <div class="performance-title">

                        <div class="performance-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>

                        <div>
                            <h3>Performance Evaluation</h3>
                            <p>Your latest performance review</p>
                        </div>

                    </div>

                    <?php if ($latestEvaluation): ?>

                        <a href="index.php?url=performance" type="button" class="performance-view-btn">

                            View Details

                            <i class="fas fa-arrow-right"></i>

                        </a>

                    <?php endif; ?>

                </div>

                <?php if ($latestEvaluation): ?>

                    <div class="performance-body">

                        <div class="performance-main-grid">

                            <div class="performance-rating">

                                <div class="performance-label">
                                    Overall Rating
                                </div>

                                <div class="performance-rating-number">

                                    <strong>
                                        <?= number_format($latestRating, 1) ?>
                                    </strong>

                                    <span>/ 5.0</span>

                                </div>

                                <div class="performance-stars">

                                    <?php for ($i = 1; $i <= 5; $i++): ?>

                                        <i class="fas fa-star" style="color: <?= $i <= round($latestRating)
                                            ? '#f59e0b'
                                            : '#e5e7eb' ?>;">
                                        </i>

                                    <?php endfor; ?>

                                </div>

                                <span class="performance-rating-label">
                                    <?= htmlspecialchars($ratingLabel) ?>
                                </span>

                                <div class="performance-progress">

                                    <div class="performance-progress-track">

                                        <div class="performance-progress-bar" style="width: <?= $ratingPercentage ?>%;">
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="performance-info-grid">

                                <div class="performance-info">

                                    <div class="performance-info-label">
                                        Review Period
                                    </div>

                                    <div class="performance-info-value">
                                        <?= htmlspecialchars(
                                            $latestEvaluation['review_period'] ?? '-'
                                        ) ?>
                                    </div>

                                </div>

                                <div class="performance-info">

                                    <div class="performance-info-label">
                                        Evaluation Date
                                    </div>

                                    <div class="performance-info-value">

                                        <?= !empty($latestEvaluation['created_at'])
                                            ? htmlspecialchars(
                                                date(
                                                    'M d, Y',
                                                    strtotime($latestEvaluation['created_at'])
                                                )
                                            )
                                            : '-' ?>

                                    </div>

                                </div>

                                <div class="performance-info">

                                    <div class="performance-info-label">
                                        Reviewer Type
                                    </div>

                                    <div class="performance-info-value">
                                        <?= htmlspecialchars(
                                            $latestEvaluation['reviewer_type'] ?? '-'
                                        ) ?>
                                    </div>

                                </div>

                                <div class="performance-info">

                                    <div class="performance-info-label">
                                        Evaluation Status
                                    </div>

                                    <div>
                                        <span class="<?= $statusClass ?>">
                                            <?= htmlspecialchars(
                                                ucwords(
                                                    str_replace('_', ' ', $status)
                                                )
                                            ) ?>
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <?php if (!empty($competencyScores)): ?>

                            <div class="performance-section">

                                <div class="performance-section-header">

                                    <h4>
                                        <i class="fas fa-bullseye"></i>
                                        Competency Performance
                                    </h4>

                                    <span>
                                        Latest evaluation
                                    </span>

                                </div>

                                <div class="competency-grid">

                                    <?php foreach ($competencyScores as $competency => $score): ?>

                                        <?php
                                        $score = (float) $score;
                                        $percentage = min(100, ($score / 5) * 100);

                                        $competencyName = ucwords(
                                            str_replace('_', ' ', $competency)
                                        );
                                        ?>

                                        <div class="competency-item">

                                            <div class="competency-top">

                                                <span class="competency-name">
                                                    <?= htmlspecialchars($competencyName) ?>
                                                </span>

                                                <span class="competency-score">
                                                    <?= number_format($score, 1) ?>/5
                                                </span>

                                            </div>

                                            <div class="competency-track">

                                                <div class="competency-bar" style="width: <?= $percentage ?>%;">
                                                </div>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <?php
                        $hasStrength =
                            !empty($latestEvaluation['strengths']);

                        $hasImprovement =
                            !empty($latestEvaluation['areas_for_improvement']);

                        $hasRecommendation =
                            !empty($latestEvaluation['recommendation']);
                        ?>

                        <?php if (
                            $hasStrength ||
                            $hasImprovement ||
                            $hasRecommendation
                        ): ?>

                            <div class="performance-section">

                                <div class="performance-section-header">

                                    <h4>
                                        <i class="fas fa-lightbulb"></i>
                                        Evaluation Insights
                                    </h4>

                                </div>

                                <div class="performance-insights">

                                    <?php if ($hasStrength): ?>

                                        <div class="performance-insight insight-strength">

                                            <h5>
                                                <i class="fas fa-circle-check"></i>
                                                Strengths
                                            </h5>

                                            <p>
                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $latestEvaluation['strengths']
                                                    )
                                                ) ?>
                                            </p>

                                        </div>

                                    <?php endif; ?>

                                    <?php if ($hasImprovement): ?>

                                        <div class="performance-insight insight-improvement">

                                            <h5>
                                                <i class="fas fa-arrow-trend-up"></i>
                                                Areas for Improvement
                                            </h5>

                                            <p>
                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $latestEvaluation['areas_for_improvement']
                                                    )
                                                ) ?>
                                            </p>

                                        </div>

                                    <?php endif; ?>

                                    <?php if ($hasRecommendation): ?>

                                        <div class="performance-insight insight-recommendation">

                                            <h5>
                                                <i class="fas fa-lightbulb"></i>
                                                Recommendation
                                            </h5>

                                            <p>
                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $latestEvaluation['recommendation']
                                                    )
                                                ) ?>
                                            </p>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <?php if (count($employeePerformanceFeedback) > 1): ?>

                            <div class="recent-evaluations">

                                <div class="performance-section-header">

                                    <h4>
                                        <i class="fas fa-clock-rotate-left"></i>
                                        Previous Evaluations
                                    </h4>

                                    <span>
                                        <?= count($employeePerformanceFeedback) ?> total
                                    </span>

                                </div>

                                <?php foreach (
                                    array_slice($employeePerformanceFeedback, 1, 3)
                                    as $evaluation
                                ): ?>

                                    <?php
                                    $previousRating = (float) (
                                        $evaluation['overall_rating']
                                        ?? $evaluation['rating']
                                        ?? 0
                                    );
                                    ?>

                                    <div class="evaluation-row">

                                        <div>

                                            <div class="evaluation-period">
                                                <?= htmlspecialchars(
                                                    $evaluation['review_period']
                                                    ?? 'Evaluation'
                                                ) ?>
                                            </div>

                                            <div class="evaluation-date">

                                                <?= !empty($evaluation['created_at'])
                                                    ? htmlspecialchars(
                                                        date(
                                                            'M d, Y',
                                                            strtotime($evaluation['created_at'])
                                                        )
                                                    )
                                                    : '-' ?>

                                            </div>

                                        </div>

                                        <div class="evaluation-right">

                                            <div class="evaluation-rating">
                                                <?= number_format($previousRating, 1) ?>/5
                                            </div>

                                            <button type="button" class="performance-view-btn" data-bs-toggle="modal"
                                                data-bs-target="#feedbackModal<?= (int) ($evaluation['feedback_id'] ?? 0) ?>">

                                                View

                                            </button>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div class="performance-empty">

                        <div class="performance-empty-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>

                        <h4>
                            No Performance Evaluation Yet
                        </h4>

                        <p>
                            Your performance evaluation will appear here once it has been completed.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>QUICK ACCESS</span>
                <h2>Employee Services</h2>
            </div>
        </div>


        <div class="quick-access-grid">

            <!-- Attendance -->
            <a href="index.php?url=attendance" class="dashboard-card">

                <div class="dashboard-card-icon attendance">
                    <i class="fas fa-clock"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Attendance</h3>
                    <p>View your attendance records</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Leave -->
            <a href="index.php?url=employee-leave-request" class="dashboard-card">

                <div class="dashboard-card-icon leave">
                    <i class="fas fa-calendar-alt"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Leave Request</h3>
                    <p>Submit and track leave requests</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Payroll -->
            <a href="index.php?url=payroll" class="dashboard-card">

                <div class="dashboard-card-icon payroll">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Payroll</h3>
                    <p>View your payslip information</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Benefits -->
            <a href="index.php?url=benefits-and-government-contribution" class="dashboard-card">

                <div class="dashboard-card-icon benefits">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Benefits & Contributions</h3>
                    <p>View your government contributions</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Performance -->
            <a href="index.php?url=performance" class="dashboard-card">

                <div class="dashboard-card-icon performance">
                    <i class="fas fa-chart-line"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Performance</h3>
                    <p>View your performance evaluation</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Training -->
            <a href="index.php?url=training" class="dashboard-card">

                <div class="dashboard-card-icon training">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Training & Development</h3>
                    <p>View your training records</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>

        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>EMPLOYEE RELATIONS</span>
                <h2>Requests & Support</h2>
            </div>
        </div>


        <div class="request-grid">

            <a href="index.php?url=complaint" class="request-card">

                <i class="fas fa-comment-alt"></i>

                <div>
                    <h3>Employee Complaint</h3>
                    <p>Submit or view your complaints</p>
                </div>

            </a>


            <a href="index.php?url=grievance" class="request-card">

                <i class="fas fa-scale-balanced"></i>

                <div>
                    <h3>Grievance</h3>
                    <p>Submit and monitor grievances</p>
                </div>

            </a>


            <a href="index.php?url=resignation" class="request-card">

                <i class="fas fa-user-minus"></i>

                <div>
                    <h3>Resignation Request</h3>
                    <p>Manage your resignation request</p>
                </div>

            </a>

        </div>

    </section>
</div>


<!-- DESIGN INTERACTIVE -->
<link rel="stylesheet" href="/hrms-capstone/modules/portal/public/css/employee-portal-dashboard.css">
<!-- Anime.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.2/anime.min.js"></script>
<script src="/hrms-capstone/modules/portal/public/js/function/employeePortalDashboard.js"></script>