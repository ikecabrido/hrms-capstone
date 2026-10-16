<?php
$viewData = get_defined_vars();
$interviews = is_array($viewData['interviews'] ?? null) ? $viewData['interviews'] : [];

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? 'N/A'), ENT_QUOTES, 'UTF-8');
};

$fullName = trim(
    ($application['first_name'] ?? '') . ' ' .
        ($application['middle_name'] ?? '') . ' ' .
        ($application['last_name'] ?? '')
);

$stageResults = [
    1 => ['label' => 'Initial Interview', 'result' => 'pending', 'interviewer' => 'N/A', 'date' => null, 'time' => null],
    2 => ['label' => 'Technical Interview', 'result' => 'pending', 'interviewer' => 'N/A', 'date' => null, 'time' => null],
    3 => ['label' => 'Final Interview', 'result' => 'pending', 'interviewer' => 'N/A', 'date' => null, 'time' => null],
];

foreach ($interviews as $interview) {
    $stageOrder = (int)($interview['stage_order'] ?? 0);
    if (isset($stageResults[$stageOrder])) {
        $stageResults[$stageOrder] = array_merge($stageResults[$stageOrder], [
            'result' => strtolower($interview['result'] ?? 'pending'),
            'interviewer' => $interview['interviewer'] ?? 'N/A',
            'date' => $interview['interview_date'] ?? null,
            'time' => $interview['interview_time'] ?? null,
        ]);
    }
}

$allStagesPassed = count($stageResults) === count(array_filter($stageResults, static function ($stage) {
    return $stage['result'] === 'passed';
}));
$isHired = strtolower((string)($application['status'] ?? '')) === 'hired' || !empty($application['hired']);
?>

<div class="application-detail">
    <div class="module-header">
        <a href="index.php?page=interview-results" class="application-back-link">
            <i class="fas fa-arrow-left"></i> Back to Interview Results
        </a>
        <div class="detail-heading">
            <div>
                <p class="detail-eyebrow">Applicant Interview Profile</p>
                <h2 class="module-title"><?= $escape($fullName ?: 'Applicant') ?></h2>
                <p class="module-description">Interview Status</p>
            </div>
        </div>
    </div>

    <section class="detail-section">
        <h3><i class="fas fa-user"></i> Basic Information</h3>
        <div class="detail-grid">
            <div>
                <span class="detail-label">Name</span>
                <strong><?= $escape($fullName ?: 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Email</span>
                <strong><?= $escape($application['email'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Cellphone Number</span>
                <strong><?= $escape($application['phone'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Position</span>
                <strong><?= $escape($application['position'] ?? 'N/A') ?></strong>
            </div>
            <div>
                <span class="detail-label">Department</span>
                <strong><?= $escape($application['department'] ?? 'N/A') ?></strong>
            </div>
        </div>
    </section>

    <section class="detail-section">
        <h3><i class="fas fa-clipboard-check"></i> Interview Results</h3>
        <div class="detail-grid interview-results-grid">
            <?php foreach ($stageResults as $stage): ?>
                <div class="interview-stage-card">
                    <span class="detail-label"><?= $escape($stage['label']) ?></span>
                    <span class="detail-label">Interviewer</span>
                    <strong><?= $escape($stage['interviewer']) ?></strong>
                    <span class="detail-label">Date and Time</span>
                    <strong>
                        <?php if (!empty($stage['date'])): ?>
                            <?= $escape(date('F j, Y', strtotime($stage['date']))) ?>
                            at
                            <?= !empty($stage['time']) ? $escape(date('g:i A', strtotime($stage['time']))) : 'N/A' ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </strong>
                    <span class="detail-label">Result</span>
                    <?php if ($stage['result'] === 'passed'): ?>
                        <strong style="color:#16a34a;"><i class="fas fa-check-circle"></i> Passed</strong>
                    <?php elseif ($stage['result'] === 'failed'): ?>
                        <strong style="color:#dc2626;"><i class="fas fa-times-circle"></i> Failed</strong>
                    <?php else: ?>
                        <strong style="color:#64748b;"><i class="fas fa-clock"></i> Pending</strong>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php if ($allStagesPassed && !$isHired && empty($application['ready_for_offer'])): ?>
    <div style="margin-top: 25px; display: flex; justify-content: flex-end;">
        <form method="POST" action="index.php?page=mark-ready-for-offer">
            <input
                type="hidden"
                name="application_id"
                value="<?= $escape($application['id'] ?? '') ?>"
            >

            <button
                type="submit"
                class="ready-offer-btn"
                onclick="return confirm('Are you sure you want to mark this applicant as Ready to Offer?');"
            >
                <i class="fas fa-file-signature"></i>
                Ready to Offer
            </button>
        </form>
    </div>
<?php elseif (!empty($application['ready_for_offer'])): ?>
    <div style="margin-top: 25px; display: flex; justify-content: flex-end;">
        <button
            type="button"
            class="ready-offer-btn"
            disabled
        >
            <i class="fas fa-check-circle"></i>
            Ready for Offer
        </button>
    </div>
<?php endif; ?>
</div>