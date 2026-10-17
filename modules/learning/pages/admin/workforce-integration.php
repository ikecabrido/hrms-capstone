<?php
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/CSRF.php';

$pdo = (new Database())->getConnection();
$inboxError = '';
$inboxMessage = '';
$reviewTypes = [
    'competency' => [
        'table' => 'ld_competency_requirement',
        'statuses' => ['received', 'planned', 'completed'],
    ],
    'reporting' => [
        'table' => 'ld_reporting_requirement',
        'statuses' => ['received', 'in_progress', 'completed'],
    ],
    'engagement' => [
        'table' => 'ld_training_engagement',
        'statuses' => ['received', 'monitoring', 'completed'],
    ],
    'risk' => [
        'table' => 'ld_turnover_risk',
        'statuses' => ['received', 'monitoring', 'mitigated', 'escalated'],
    ],
    'training_request' => [
        'table' => 'ld_training_requests',
        'statuses' => ['pending', 'accepted', 'rejected', 'completed'],
    ],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $recordType = (string) ($_POST['record_type'] ?? '');
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');

    if (!CSRF::validate(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        $inboxError = 'Your session token expired. Reload this page and try again.';
    } elseif (!isset($reviewTypes[$recordType]) || $recordId === false || $recordId < 1
        || !in_array($status, $reviewTypes[$recordType]['statuses'], true)) {
        http_response_code(400);
        $inboxError = 'The requested review update is invalid.';
    } else {
        $table = $reviewTypes[$recordType]['table'];
        $stmt = $pdo->prepare("UPDATE {$table} SET status = :status WHERE id = :id AND source_module = 'workforce'");
        $stmt->execute([':status' => $status, ':id' => $recordId]);
        $inboxMessage = $stmt->rowCount() > 0 ? 'Review status updated.' : 'No Workforce record needed an update.';
    }
}

$records = [
    'competency' => [],
    'reporting' => [],
    'engagement' => [],
    'risk' => [],
    'training_request' => [],
];

try {
    $records['competency'] = $pdo->query(
        "SELECT id, skill_name, target_department, priority_level, employees_needing_training,
                target_proficiency, expected_learning_output, notes, status, created_at
         FROM ld_competency_requirement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['reporting'] = $pdo->query(
        "SELECT id, target_name, reporting_period_start, reporting_period_end, reporting_frequency,
                completion_target, expected_output, notes, status, created_at
         FROM ld_reporting_requirement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['engagement'] = $pdo->query(
        "SELECT id, external_reference_id, learner_team_type, learner_id, learner_name, department,
                training_activity, engagement_status, engagement_data, recommended_action, notes,
                status, created_at, updated_at
         FROM ld_training_engagement
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['risk'] = $pdo->query(
        "SELECT id, external_reference_id, employee_id, employee_name, department, position_name,
                risk_level, risk_score, risk_factors, recommended_action, assessment_date,
                status, created_at, updated_at
         FROM ld_turnover_risk
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $records['training_request'] = $pdo->query(
        "SELECT id, learner_id, skill_id, skill_name, message, instructor_id, instructor_note,
                target_department, priority_level, status, external_reference_id, created_at, updated_at
         FROM ld_training_requests
         WHERE source_module = 'workforce'
         ORDER BY created_at DESC, id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    DbError::capture($exception, 'admin/workforce-integration');
    $inboxError = 'Workforce records could not be loaded. Check the Learning database connection and table setup.';
}

$counts = array_map('count', $records);
$formatDate = static function ($value): string {
    return $value ? date('M j, Y', strtotime((string) $value)) : '—';
};
$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

/** Tone class for the review select, so the current status reads at a glance. */
$statusTone = static function (string $status): string {
    if (in_array($status, ['completed', 'accepted', 'mitigated'], true)) {
        return 'is-done';
    }
    if (in_array($status, ['in_progress', 'monitoring', 'planned'], true)) {
        return 'is-active';
    }
    if (in_array($status, ['rejected', 'escalated'], true)) {
        return 'is-warn';
    }
    return '';
};

$renderStatusControl = static function (string $type, array $record) use ($reviewTypes, $escape, $statusTone): void {
    $current = (string) ($record['status'] ?? '');
    $id = 'wf-status-' . $type . '-' . (int) $record['id'];
    echo '<form method="post" class="wf-review">';
    echo CSRF::field();
    echo '<input type="hidden" name="action" value="update_status">';
    echo '<input type="hidden" name="record_type" value="' . $escape($type) . '">';
    echo '<input type="hidden" name="record_id" value="' . (int) $record['id'] . '">';
    echo '<label class="visually-hidden" for="' . $escape($id) . '">Review status</label>';
    echo '<select id="' . $escape($id) . '" name="status" class="' . $escape($statusTone($current)) . '">';
    foreach ($reviewTypes[$type]['statuses'] as $status) {
        $selected = $status === $current ? ' selected' : '';
        echo '<option value="' . $escape($status) . '"' . $selected . '>' . $escape(ucwords(str_replace('_', ' ', $status))) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="wf-review-btn" title="Save review status" aria-label="Save review status"><i class="fas fa-check" aria-hidden="true"></i></button>';
    echo '</form>';
};

/** Priority / risk level as a toned badge. */
$levelBadge = static function ($level) use ($escape): string {
    $key = strtolower(trim((string) $level));
    if ($key === '') {
        return '<span class="wf-muted">—</span>';
    }
    $tone = in_array($key, ['critical', 'escalated'], true) ? 'is-critical'
        : (in_array($key, ['high', 'urgent'], true) ? 'is-high'
        : (in_array($key, ['medium', 'moderate'], true) ? 'is-medium' : 'is-low'));
    return '<span class="wf-badge ' . $tone . '">' . $escape(ucfirst($key)) . '</span>';
};
?>
<div class="module-content wf-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Workforce Inbox</h1>
                <p>Review incoming requirements, risk signals, engagement updates, and training actions.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-lightbulb" aria-hidden="true"></i> <b><?= (int) $counts['competency'] ?></b> competency needs</span>
                    <span class="home-hero-chip"><i class="fas fa-chart-column" aria-hidden="true"></i> <b><?= (int) $counts['reporting'] ?></b> reporting</span>
                    <span class="home-hero-chip"><i class="fas fa-users" aria-hidden="true"></i> <b><?= (int) $counts['engagement'] ?></b> engagement</span>
                    <span class="home-hero-chip"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <b><?= (int) $counts['risk'] ?></b> risk</span>
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap" aria-hidden="true"></i> <b><?= (int) $counts['training_request'] ?></b> actions</span>
                </div>
            </div>
        </section>

        <?php if ($inboxMessage !== ''): ?>
            <p class="wf-notice" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><span><?= $escape($inboxMessage) ?></span></p>
        <?php endif; ?>
        <?php if ($inboxError !== ''): ?>
            <p class="wf-notice is-error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span><?= $escape($inboxError) ?></span></p>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tab-list" role="tablist" aria-label="Workforce records">
                <button type="button" class="tab-item active" data-tab="wf-requirements" role="tab" aria-selected="true">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i> Requirements
                    <span class="wf-tab-count"><?= (int) ($counts['competency'] + $counts['reporting']) ?></span>
                </button>
                <button type="button" class="tab-item" data-tab="wf-signals" role="tab" aria-selected="false">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i> Risk &amp; engagement
                    <span class="wf-tab-count"><?= (int) ($counts['risk'] + $counts['engagement']) ?></span>
                </button>
                <button type="button" class="tab-item" data-tab="wf-actions" role="tab" aria-selected="false">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i> Training actions
                    <span class="wf-tab-count"><?= (int) $counts['training_request'] ?></span>
                </button>
            </div>

            <section class="tab-content active" id="wf-requirements" data-tab="wf-requirements">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-violet"><i class="fas fa-lightbulb" aria-hidden="true"></i></span>
                            Competency requirements
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['competency'] ?></span>
                    </div>
                    <?php if (!$records['competency']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce competency requirements have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Need</th><th>Department</th><th>Priority</th><th>Learners</th><th>Expected output</th><th>Received</th><th>Review</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['competency'] as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['skill_name']) ?></span>
                                                <?php if ((string) $record['target_proficiency'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['target_proficiency']) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['target_department']) ?></td>
                                            <td><?= $levelBadge($record['priority_level']) ?></td>
                                            <td class="wf-cell-strong"><?= (int) $record['employees_needing_training'] ?></td>
                                            <td><?= $escape($record['expected_learning_output']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderStatusControl('competency', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-indigo"><i class="fas fa-chart-column" aria-hidden="true"></i></span>
                            Reporting requirements
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['reporting'] ?></span>
                    </div>
                    <?php if (!$records['reporting']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce reporting requirements have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Target</th><th>Period</th><th>Frequency</th><th>Goal</th><th>Expected output</th><th>Received</th><th>Review</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['reporting'] as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['target_name']) ?></span>
                                                <?php if ((string) $record['notes'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['notes']) ?></span><?php endif; ?>
                                            </td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['reporting_period_start'])) ?> &ndash; <?= $escape($formatDate($record['reporting_period_end'])) ?></td>
                                            <td><?= $escape(ucfirst($record['reporting_frequency'])) ?></td>
                                            <td class="wf-cell-strong"><?= $escape($record['completion_target']) ?>%</td>
                                            <td><?= $escape($record['expected_output']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderStatusControl('reporting', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-content" id="wf-signals" data-tab="wf-signals">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-rose"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span>
                            Turnover risk assessments
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['risk'] ?></span>
                    </div>
                    <?php if (!$records['risk']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce risk assessments have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Employee</th><th>Department / role</th><th>Risk</th><th>Factors</th><th>Recommended action</th><th>Assessed</th><th>Review</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['risk'] as $record): ?>
                                        <tr>
                                            <td><span class="wf-cell-title"><?= $escape($record['employee_name'] ?: ($record['employee_id'] ? 'Employee #' . $record['employee_id'] : 'Workforce group')) ?></span></td>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['department']) ?></span>
                                                <?php if ((string) $record['position_name'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['position_name']) ?></span><?php endif; ?>
                                            </td>
                                            <td class="wf-cell-nowrap">
                                                <?= $levelBadge($record['risk_level']) ?>
                                                <?php if ($record['risk_score'] !== null): ?><span class="wf-cell-sub"><?= $escape($record['risk_score']) ?>%</span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['risk_factors']) ?></td>
                                            <td><?= $escape($record['recommended_action']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['assessment_date'] ?: $record['created_at'])) ?></td>
                                            <td><?php $renderStatusControl('risk', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-amber"><i class="fas fa-users" aria-hidden="true"></i></span>
                            Training engagement
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['engagement'] ?></span>
                    </div>
                    <?php if (!$records['engagement']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce engagement records have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Learner / group</th><th>Department</th><th>Activity</th><th>Engagement</th><th>Recommended action</th><th>Received</th><th>Review</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['engagement'] as $record): ?>
                                        <tr>
                                            <td><span class="wf-cell-title"><?= $escape($record['learner_name'] ?: ($record['learner_id'] ? 'Employee #' . $record['learner_id'] : ucwords(str_replace('-', ' ', $record['learner_team_type'])))) ?></span></td>
                                            <td><?= $escape($record['department']) ?></td>
                                            <td><?= $escape(ucwords(str_replace('-', ' ', $record['training_activity']))) ?></td>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape(ucwords(str_replace('-', ' ', $record['engagement_status']))) ?></span>
                                                <?php if ((string) $record['engagement_data'] !== ''): ?><span class="wf-cell-sub"><?= $escape($record['engagement_data']) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['recommended_action']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderStatusControl('engagement', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-content" id="wf-actions" data-tab="wf-actions">
                <div class="wf-card">
                    <div class="wf-card-head">
                        <h2 class="wf-card-title">
                            <span class="wf-card-icon tone-emerald"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                            Training actions
                        </h2>
                        <span class="wf-card-count"><?= (int) $counts['training_request'] ?></span>
                    </div>
                    <p class="wf-card-note">Requests currently associated with Workforce records. New training requests are not created automatically; staff review the source first.</p>
                    <?php if (!$records['training_request']): ?>
                        <div class="wf-empty"><i class="fas fa-inbox" aria-hidden="true"></i><p>No Workforce training actions have arrived.</p></div>
                    <?php else: ?>
                        <div class="wf-table-wrap">
                            <table class="wf-table">
                                <thead>
                                    <tr><th>Request</th><th>Department</th><th>Priority</th><th>Source record</th><th>Request details</th><th>Received</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records['training_request'] as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="wf-cell-title"><?= $escape($record['skill_name']) ?></span>
                                                <?php if ($record['learner_id']): ?><span class="wf-cell-sub">Employee #<?= (int) $record['learner_id'] ?></span><?php endif; ?>
                                            </td>
                                            <td><?= $escape($record['target_department']) ?></td>
                                            <td><?= $levelBadge($record['priority_level']) ?></td>
                                            <td class="wf-cell-muted"><?= $escape($record['external_reference_id'] ?: '—') ?></td>
                                            <td><?= $escape($record['message']) ?></td>
                                            <td class="wf-cell-muted wf-cell-nowrap"><?= $escape($formatDate($record['created_at'])) ?></td>
                                            <td><?php $renderStatusControl('training_request', $record); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
