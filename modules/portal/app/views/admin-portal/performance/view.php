<?php foreach ($allPerformance as $performance): ?>

    <?php

    $rating = (float) (
        $performance['overall_rating']
        ?? $performance['rating']
        ?? 0
    );

    $competencies = [];

    if (!empty($performance['competency_scores'])) {

        $decoded = json_decode(
            $performance['competency_scores'],
            true
        );

        if (is_array($decoded)) {
            $competencies = $decoded;
        }
    }

    ?>

    <div class="modal fade" id="viewPerformanceModal<?= (int) $performance['feedback_id'] ?>" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

            <div class="modal-content border-0 shadow" style="border-radius:16px;">

                <!-- HEADER -->
                <div class="modal-header" style="
                        padding:16px 20px;
                        border-bottom:1px solid #e5e7eb;
                    ">

                    <div>

                        <div style="
                            color:#2563eb;
                            font-size:8px;
                            font-weight:700;
                            letter-spacing:.07em;
                        ">
                            PERFORMANCE EVALUATION
                        </div>

                        <h5 class="modal-title" style="
                                margin-top:4px;
                                color:#111827;
                                font-size:16px;
                                font-weight:700;
                            ">
                            Evaluation #<?= (int) $performance['feedback_id'] ?>
                        </h5>

                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>


                <!-- BODY -->
                <div class="modal-body" style="padding:20px;">

                    <!-- INFORMATION -->
                    <div class="row g-3 mb-4">

                        <div class="col-12 col-md-6">

                            <div class="border rounded-3 p-3 h-100">

                                <div style="
                                    color:#9ca3af;
                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                    Employee
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    Employee #<?= htmlspecialchars(
                                        $performance['employee_id'] ?? '—'
                                    ) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-12 col-md-6">

                            <div class="border rounded-3 p-3 h-100">

                                <div style="
                                    color:#9ca3af;
                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                    Department
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars(
                                        $performance['department'] ?? '—'
                                    ) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-12 col-md-6">

                            <div class="border rounded-3 p-3 h-100">

                                <div style="
                                    color:#9ca3af;
                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                    Reviewer
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars(
                                        $performance['reviewer_name'] ?? '—'
                                    ) ?>
                                </div>

                                <div style="
                                    margin-top:2px;
                                    color:#9ca3af;
                                    font-size:9px;
                                ">
                                    <?= htmlspecialchars(
                                        $performance['reviewer_type'] ?? '—'
                                    ) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-12 col-md-6">

                            <div class="border rounded-3 p-3 h-100">

                                <div style="
                                    color:#9ca3af;
                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                    Review Period
                                </div>

                                <div style="
                                    margin-top:4px;
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:600;
                                ">
                                    <?= htmlspecialchars(
                                        $performance['review_period'] ?? '—'
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- OVERALL RATING -->
                    <div class="rounded-3 mb-4" style="
                            padding:16px;
                            background:#eff6ff;
                        ">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div style="
                                    color:#6b7280;
                                    font-size:8px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                ">
                                    Overall Rating
                                </div>

                                <div style="
                                    margin-top:3px;
                                    color:#2563eb;
                                    font-size:22px;
                                    font-weight:700;
                                ">

                                    <?= number_format($rating, 2) ?>

                                    <span style="
                                        color:#9ca3af;
                                        font-size:10px;
                                    ">
                                        / 5.00
                                    </span>

                                </div>

                            </div>


                            <div style="
                                color:#f59e0b;
                                font-size:15px;
                            ">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <?php if ($i <= round($rating)): ?>

                                        <i class="fas fa-star"></i>

                                    <?php else: ?>

                                        <i class="far fa-star"></i>

                                    <?php endif; ?>

                                <?php endfor; ?>

                            </div>

                        </div>

                    </div>


                    <!-- COMPETENCIES -->
                    <?php if (!empty($competencies)): ?>

                        <div class="mb-4">

                            <div style="
                                margin-bottom:10px;
                                color:#111827;
                                font-size:11px;
                                font-weight:700;
                            ">
                                Competency Scores
                            </div>

                            <div class="row g-2">

                                <?php foreach ($competencies as $name => $score): ?>

                                    <div class="col-12 col-md-6">

                                        <div class="border rounded-3 p-3">

                                            <div class="d-flex justify-content-between">

                                                <span style="
                                                    color:#6b7280;
                                                    font-size:10px;
                                                ">
                                                    <?= htmlspecialchars(
                                                        ucwords(
                                                            str_replace(
                                                                '_',
                                                                ' ',
                                                                $name
                                                            )
                                                        )
                                                    ) ?>
                                                </span>

                                                <strong style="
                                                    color:#111827;
                                                    font-size:10px;
                                                ">
                                                    <?= htmlspecialchars($score) ?>/5
                                                </strong>

                                            </div>

                                            <div class="progress mt-2" style="height:5px;">

                                                <div class="progress-bar bg-primary" style="
                                                        width:<?= min(
                                                            100,
                                                            ((float) $score / 5) * 100
                                                        ) ?>%;
                                                    "></div>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- COMMENTS -->
                    <div class="mb-4">

                        <div style="
                            margin-bottom:8px;
                            color:#111827;
                            font-size:11px;
                            font-weight:700;
                        ">
                            Comments
                        </div>

                        <div class="border rounded-3" style="
                                padding:12px;
                                color:#4b5563;
                                background:#f8fafc;
                                font-size:10px;
                                line-height:1.6;
                            ">
                            <?= nl2br(htmlspecialchars(
                                $performance['comments']
                                ?? 'No comments provided.'
                            )) ?>
                        </div>

                    </div>


                    <!-- STRENGTHS -->
                    <div class="mb-4">

                        <div style="
                            margin-bottom:8px;
                            color:#111827;
                            font-size:11px;
                            font-weight:700;
                        ">
                            Strengths
                        </div>

                        <div style="
                            padding:12px;
                            border-radius:9px;
                            background:#f0fdf4;
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">

                            <i class="fas fa-circle-check text-success me-1"></i>

                            <?= nl2br(htmlspecialchars(
                                $performance['strengths']
                                ?? 'No strengths recorded.'
                            )) ?>

                        </div>

                    </div>


                    <!-- IMPROVEMENT -->
                    <div class="mb-4">

                        <div style="
                            margin-bottom:8px;
                            color:#111827;
                            font-size:11px;
                            font-weight:700;
                        ">
                            Areas for Improvement
                        </div>

                        <div style="
                            padding:12px;
                            border-radius:9px;
                            background:#fff7ed;
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">

                            <i class="fas fa-lightbulb text-warning me-1"></i>

                            <?= nl2br(htmlspecialchars(
                                $performance['areas_for_improvement']
                                ?? 'No improvement areas recorded.'
                            )) ?>

                        </div>

                    </div>


                    <!-- RECOMMENDATION -->
                    <div class="mb-2">

                        <div style="
                            margin-bottom:8px;
                            color:#111827;
                            font-size:11px;
                            font-weight:700;
                        ">
                            Recommendation
                        </div>

                        <div style="
                            padding:12px;
                            border-radius:9px;
                            background:#f5f3ff;
                            color:#374151;
                            font-size:10px;
                            line-height:1.6;
                        ">

                            <i class="fas fa-arrow-right text-primary me-1"></i>

                            <?= nl2br(htmlspecialchars(
                                $performance['recommendation']
                                ?? 'No recommendation provided.'
                            )) ?>

                        </div>

                    </div>


                    <!-- HR REMARKS -->
                    <?php if (!empty($performance['hr_remarks'])): ?>

                        <div class="mt-4">

                            <div style="
                                margin-bottom:8px;
                                color:#111827;
                                font-size:11px;
                                font-weight:700;
                            ">
                                HR Remarks
                            </div>

                            <div class="border rounded-3" style="
                                    padding:12px;
                                    color:#4b5563;
                                    font-size:10px;
                                    line-height:1.6;
                                ">
                                <?= nl2br(htmlspecialchars(
                                    $performance['hr_remarks']
                                )) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- FOOTER -->
                <div class="modal-footer" style="
                        padding:12px 20px;
                        border-top:1px solid #e5e7eb;
                    ">

                    <small class="me-auto text-secondary" style="font-size:8px;">
                        Submitted:
                        <?= !empty($performance['created_at'])
                            ? date(
                                'M d, Y h:i A',
                                strtotime($performance['created_at'])
                            )
                            : '—'
                            ?>
                    </small>

                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="
                            padding:7px 12px;
                            border-radius:7px;
                            font-size:10px;
                            font-weight:600;
                        ">
                        Close
                    </button>

                </div>

            </div>

        </div>

    </div>

<?php endforeach; ?>