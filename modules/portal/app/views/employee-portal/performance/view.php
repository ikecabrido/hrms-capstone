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


    /*
    | Parse competency JSON
    */

    $competencyScores = [];

    if (!empty($row['competency_scores'])) {

        $decodedScores = json_decode(
            $row['competency_scores'],
            true
        );

        if (is_array($decodedScores)) {
            $competencyScores = $decodedScores;
        }
    }

    ?>

    <div class="modal fade" id="feedbackModal<?= $feedbackId ?>" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content" style="
                            border:0;
                            border-radius:16px;
                            overflow:hidden;
                            box-shadow:0 20px 50px rgba(15,23,42,.18);
                        ">


                <!-- HEADER -->

                <div style="
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            gap:15px;
                            padding:18px 20px;
                            border-bottom:1px solid #e5e7eb;
                            background:#fff;
                        ">

                    <div style="
                                display:flex;
                                align-items:center;
                                gap:12px;
                            ">

                        <div style="
                                    width:42px;
                                    height:42px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    border-radius:11px;
                                    background:#eff6ff;
                                    color:#2563eb;
                                ">
                            <i class="fas fa-chart-line"></i>
                        </div>

                        <div>

                            <div style="
                                        color:#111827;
                                        font-size:15px;
                                        font-weight:750;
                                    ">
                                Performance Evaluation
                            </div>

                            <div style="
                                        margin-top:3px;
                                        color:#94a3b8;
                                        font-size:9px;
                                    ">
                                <?= htmlspecialchars($evaluator) ?>
                            </div>

                        </div>

                    </div>


                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>


                <!-- BODY -->

                <div style="
                            max-height:70vh;
                            overflow-y:auto;
                            padding:20px;
                            background:#fff;
                        ">


                    <!-- RATING -->

                    <div style="
                                display:flex;
                                align-items:center;
                                justify-content:space-between;
                                gap:15px;
                                padding:15px;
                                margin-bottom:18px;
                                border:1px solid #e5e7eb;
                                border-radius:12px;
                                background:#f8fafc;
                            ">

                        <div>

                            <div style="
                                        margin-bottom:5px;
                                        color:#64748b;
                                        font-size:9px;
                                        font-weight:800;
                                        text-transform:uppercase;
                                    ">
                                Overall Rating
                            </div>

                            <div style="
                                        color:#111827;
                                        font-size:25px;
                                        font-weight:750;
                                    ">

                                <?= number_format($rating, 1) ?>

                                <span style="
                                            color:#94a3b8;
                                            font-size:11px;
                                        ">
                                    / 5
                                </span>

                            </div>

                        </div>


                        <div style="
                                    display:flex;
                                    gap:3px;
                                ">

                            <?php for ($i = 1; $i <= 5; $i++): ?>

                                <i class="fas fa-star" style="
                                                color:<?= $i <= round($rating)
                                                    ? '#f59e0b'
                                                    : '#e5e7eb' ?>;
                                                font-size:13px;
                                            "></i>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <!-- INFORMATION GRID -->

                    <div style="
                                display:grid;
                                grid-template-columns:repeat(2,minmax(0,1fr));
                                gap:10px;
                                margin-bottom:18px;
                            ">

                        <?php

                        $details = [
                            'Reviewer Type' => $row['reviewer_type'] ?? '-',
                            'Department' => $row['department'] ?? '-',
                            'Review Period' => $row['review_period'] ?? '-',
                            'Category' => $row['feedback_category'] ?? $row['category'] ?? '-',
                            'Status' => $row['feedback_status'] ?? '-',
                            'Evaluation Date' => !empty($row['created_at'])
                                ? date('M d, Y', strtotime($row['created_at']))
                                : '-'
                        ];

                        foreach ($details as $label => $value):
                            ?>

                            <div style="
                                        padding:11px 12px;
                                        border:1px solid #e5e7eb;
                                        border-radius:10px;
                                        background:#fff;
                                    ">

                                <div style="
                                            margin-bottom:4px;
                                            color:#94a3b8;
                                            font-size:8px;
                                            font-weight:800;
                                            text-transform:uppercase;
                                        ">
                                    <?= htmlspecialchars($label) ?>
                                </div>

                                <div style="
                                            color:#374151;
                                            font-size:10px;
                                            font-weight:650;
                                        ">
                                    <?= htmlspecialchars($value) ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- COMMENTS -->

                    <div style="margin-bottom:18px;">

                        <div style="
                                    margin-bottom:7px;
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:750;
                                ">
                            Feedback
                        </div>

                        <div style="
                                    padding:13px;
                                    border:1px solid #e5e7eb;
                                    border-radius:10px;
                                    background:#f8fafc;
                                    color:#475569;
                                    font-size:10px;
                                    line-height:1.7;
                                ">
                            <?= nl2br(
                                htmlspecialchars(
                                    $row['comments'] ?? 'No comments provided.'
                                )
                            ) ?>
                        </div>

                    </div>


                    <!-- STRENGTHS -->

                    <?php if (!empty($row['strengths'])): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                        margin-bottom:7px;
                                        color:#166534;
                                        font-size:11px;
                                        font-weight:750;
                                    ">
                                <i class="fas fa-circle-check"></i>
                                Strengths
                            </div>

                            <div style="
                                        padding:13px;
                                        border:1px solid #bbf7d0;
                                        border-radius:10px;
                                        background:#f0fdf4;
                                        color:#166534;
                                        font-size:10px;
                                        line-height:1.7;
                                    ">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $row['strengths']
                                    )
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- AREAS FOR IMPROVEMENT -->

                    <?php if (!empty($row['areas_for_improvement'])): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                        margin-bottom:7px;
                                        color:#b45309;
                                        font-size:11px;
                                        font-weight:750;
                                    ">
                                <i class="fas fa-arrow-trend-up"></i>
                                Areas for Improvement
                            </div>

                            <div style="
                                        padding:13px;
                                        border:1px solid #fde68a;
                                        border-radius:10px;
                                        background:#fffbeb;
                                        color:#92400e;
                                        font-size:10px;
                                        line-height:1.7;
                                    ">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $row['areas_for_improvement']
                                    )
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- RECOMMENDATION -->

                    <?php if (!empty($row['recommendation'])): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                        margin-bottom:7px;
                                        color:#2563eb;
                                        font-size:11px;
                                        font-weight:750;
                                    ">
                                <i class="fas fa-lightbulb"></i>
                                Recommendation
                            </div>

                            <div style="
                                        padding:13px;
                                        border:1px solid #dbeafe;
                                        border-radius:10px;
                                        background:#eff6ff;
                                        color:#1e40af;
                                        font-size:10px;
                                        line-height:1.7;
                                    ">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $row['recommendation']
                                    )
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- COMPETENCY SCORES -->

                    <?php if (!empty($competencyScores)): ?>

                        <div style="margin-bottom:18px;">

                            <div style="
                                        margin-bottom:9px;
                                        color:#111827;
                                        font-size:11px;
                                        font-weight:750;
                                    ">
                                Competency Scores
                            </div>

                            <div style="
                                        display:grid;
                                        grid-template-columns:repeat(2,minmax(0,1fr));
                                        gap:9px;
                                    ">

                                <?php foreach ($competencyScores as $competency => $score): ?>

                                    <?php
                                    $score = (float) $score;
                                    $percentage = min(100, ($score / 5) * 100);
                                    ?>

                                    <div style="
                                                padding:11px;
                                                border:1px solid #e5e7eb;
                                                border-radius:10px;
                                                background:#fff;
                                            ">

                                        <div style="
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:space-between;
                                                    gap:10px;
                                                    margin-bottom:7px;
                                                ">

                                            <span style="
                                                        color:#475569;
                                                        font-size:9px;
                                                        font-weight:650;
                                                    ">
                                                <?= htmlspecialchars(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $competency
                                                        )
                                                    )
                                                ) ?>
                                            </span>

                                            <strong style="
                                                        color:#111827;
                                                        font-size:10px;
                                                    ">
                                                <?= number_format($score, 1) ?>/5
                                            </strong>

                                        </div>

                                        <div style="
                                                    width:100%;
                                                    height:5px;
                                                    overflow:hidden;
                                                    border-radius:20px;
                                                    background:#e5e7eb;
                                                ">

                                            <div style="
                                                        width:<?= $percentage ?>%;
                                                        height:100%;
                                                        border-radius:20px;
                                                        background:#2563eb;
                                                    "></div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- HR REMARKS -->

                    <?php if (!empty($row['hr_remarks'])): ?>

                        <div>

                            <div style="
                                        margin-bottom:7px;
                                        color:#111827;
                                        font-size:11px;
                                        font-weight:750;
                                    ">
                                HR Remarks
                            </div>

                            <div style="
                                        padding:13px;
                                        border:1px solid #e5e7eb;
                                        border-radius:10px;
                                        background:#f8fafc;
                                        color:#475569;
                                        font-size:10px;
                                        line-height:1.7;
                                    ">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $row['hr_remarks']
                                    )
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- FOOTER -->

                <div style="
                            display:flex;
                            justify-content:flex-end;
                            padding:12px 20px;
                            border-top:1px solid #e5e7eb;
                            background:#f8fafc;
                        ">

                    <button type="button" data-bs-dismiss="modal" style="
                                    padding:8px 14px;
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    background:#fff;
                                    color:#374151;
                                    font-size:10px;
                                    font-weight:650;
                                    cursor:pointer;
                                ">
                        Close
                    </button>

                </div>

            </div>

        </div>

    </div>

<?php endforeach; ?>