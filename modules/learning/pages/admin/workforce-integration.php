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
$renderStatusControl = static function (string $type, array $record) use ($reviewTypes, $escape): void {
    echo '<form method="post" class="wf-review-status">';
    echo CSRF::field();
    echo '<input type="hidden" name="action" value="update_status">';
    echo '<input type="hidden" name="record_type" value="' . $escape($type) . '">';
    echo '<input type="hidden" name="record_id" value="' . (int) $record['id'] . '">';
    echo '<label class="visually-hidden" for="wf-status-' . $escape($type) . '-' . (int) $record['id'] . '">Review status</label>';
    echo '<select id="wf-status-' . $escape($type) . '-' . (int) $record['id'] . '" name="status">';
    foreach ($reviewTypes[$type]['statuses'] as $status) {
        $selected = $status === ($record['status'] ?? '') ? ' selected' : '';
        echo '<option value="' . $escape($status) . '"' . $selected . '>' . $escape(ucwords(str_replace('_', ' ', $status))) . '</option>';
    }
    echo '</select><button type="submit" class="wf-review-save" title="Save review status" aria-label="Save review status"><i class="fas fa-check" aria-hidden="true"></i></button></form>';
};
?>
<style>
.wf-inbox-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap}
.wf-inbox-heading h1{margin:0;font-size:1.45rem;color:var(--text)}
.wf-inbox-heading p{margin:.35rem 0 0;color:var(--muted)}
.wf-inbox-counts{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:.65rem;margin-bottom:1rem}
.wf-inbox-count{padding:.8rem .9rem;border:1px solid var(--border);border-radius:8px;background:var(--surface)}
.wf-inbox-count span{display:block;color:var(--muted);font-size:.74rem}
.wf-inbox-count strong{display:block;margin-top:.2rem;font-size:1.35rem;color:var(--text)}
.wf-inbox-table-wrap{overflow-x:auto}
.wf-inbox-table{width:100%;border-collapse:collapse;font-size:.84rem;min-width:760px}
.wf-inbox-table th,.wf-inbox-table td{padding:.72rem .65rem;border-bottom:1px solid var(--border);text-align:left;vertical-align:top}
.wf-inbox-table th{color:var(--muted);font-size:.72rem;text-transform:uppercase}
.wf-inbox-table td small{display:block;margin-top:.2rem;color:var(--muted);white-space:normal}
.wf-review-status{display:flex;gap:.35rem;align-items:center;min-width:150px}
.wf-review-status select{min-width:0;max-width:145px;padding:.42rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text)}
.wf-review-save{width:34px;height:34px;flex:none;border:0;border-radius:6px;background:var(--primary);color:#fff;cursor:pointer}
.wf-review-save:focus-visible,.wf-review-status select:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.wf-inbox-notice{margin:0 0 1rem;padding:.7rem .9rem;border-left:3px solid var(--primary);background:var(--bg-subtle);color:var(--text)}
.wf-inbox-notice.error{border-color:#b42318;color:#b42318}
.wf-inbox-empty{padding:1.5rem;text-align:center;color:var(--muted)}
@media(max-width:800px){.wf-inbox-counts{grid-template-columns:repeat(2,minmax(0,1fr))}.wf-inbox-count:last-child{grid-column:1/-1}}
</style>

<div class="module-content">
    <div class="wf-inbox-heading">
        <div><h1>Workforce Inbox</h1><p>Review incoming requirements, risk signals, engagement updates, and training actions.</p></div>
    </div>
    <?php if ($inboxMessage !== ''): ?><p class="wf-inbox-notice" role="status"><?= $escape($inboxMessage) ?></p><?php endif; ?>
    <?php if ($inboxError !== ''): ?><p class="wf-inbox-notice error" role="alert"><?= $escape($inboxError) ?></p><?php endif; ?>

    <div class="wf-inbox-counts" aria-label="Workforce record counts">
        <div class="wf-inbox-count"><span>Competency needs</span><strong><?= $counts['competency'] ?></strong></div>
        <div class="wf-inbox-count"><span>Reporting requirements</span><strong><?= $counts['reporting'] ?></strong></div>
        <div class="wf-inbox-count"><span>Engagement records</span><strong><?= $counts['engagement'] ?></strong></div>
        <div class="wf-inbox-count"><span>Risk assessments</span><strong><?= $counts['risk'] ?></strong></div>
        <div class="wf-inbox-count"><span>Training actions</span><strong><?= $counts['training_request'] ?></strong></div>
    </div>

    <div class="tab-container">
        <div class="tab-list" role="tablist" aria-label="Workforce records">
            <button type="button" class="tab-item active" data-tab="wf-requirements">Requirements</button>
            <button type="button" class="tab-item" data-tab="wf-signals">Risk &amp; engagement</button>
            <button type="button" class="tab-item" data-tab="wf-actions">Training actions</button>
        </div>

        <section class="tab-content active" id="wf-requirements" data-tab="wf-requirements">
            <div class="mode-card">
                <h2>Competency requirements</h2>
                <?php if (!$records['competency']): ?><p class="wf-inbox-empty">No Workforce competency requirements have arrived.</p><?php else: ?>
                <div class="wf-inbox-table-wrap"><table class="wf-inbox-table"><thead><tr><th>Need</th><th>Department</th><th>Priority</th><th>Learners</th><th>Expected output</th><th>Received</th><th>Review</th></tr></thead><tbody>
                    <?php foreach ($records['competency'] as $record): ?><tr>
                        <td><strong><?= $escape($record['skill_name']) ?></strong><small><?= $escape($record['target_proficiency']) ?></small></td>
                        <td><?= $escape($record['target_department']) ?></td><td><?= $escape(ucfirst($record['priority_level'])) ?></td>
                        <td><?= (int) $record['employees_needing_training'] ?></td><td><?= $escape($record['expected_learning_output']) ?></td>
                        <td><?= $escape($formatDate($record['created_at'])) ?></td><td><?php $renderStatusControl('competency', $record); ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
            <div class="mode-card" style="margin-top:1rem;">
                <h2>Reporting requirements</h2>
                <?php if (!$records['reporting']): ?><p class="wf-inbox-empty">No Workforce reporting requirements have arrived.</p><?php else: ?>
                <div class="wf-inbox-table-wrap"><table class="wf-inbox-table"><thead><tr><th>Target</th><th>Period</th><th>Frequency</th><th>Goal</th><th>Expected output</th><th>Received</th><th>Review</th></tr></thead><tbody>
                    <?php foreach ($records['reporting'] as $record): ?><tr>
                        <td><strong><?= $escape($record['target_name']) ?></strong><small><?= $escape($record['notes']) ?></small></td>
                        <td><?= $escape($formatDate($record['reporting_period_start'])) ?> to <?= $escape($formatDate($record['reporting_period_end'])) ?></td>
                        <td><?= $escape(ucfirst($record['reporting_frequency'])) ?></td><td><?= $escape($record['completion_target']) ?>%</td>
                        <td><?= $escape($record['expected_output']) ?></td><td><?= $escape($formatDate($record['created_at'])) ?></td>
                        <td><?php $renderStatusControl('reporting', $record); ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
        </section>

        <section class="tab-content" id="wf-signals" data-tab="wf-signals">
            <div class="mode-card">
                <h2>Turnover risk assessments</h2>
                <?php if (!$records['risk']): ?><p class="wf-inbox-empty">No Workforce risk assessments have arrived.</p><?php else: ?>
                <div class="wf-inbox-table-wrap"><table class="wf-inbox-table"><thead><tr><th>Employee</th><th>Department / role</th><th>Risk</th><th>Factors</th><th>Recommended action</th><th>Assessed</th><th>Review</th></tr></thead><tbody>
                    <?php foreach ($records['risk'] as $record): ?><tr>
                        <td><strong><?= $escape($record['employee_name'] ?: ($record['employee_id'] ? 'Employee #' . $record['employee_id'] : 'Workforce group')) ?></strong></td>
                        <td><?= $escape($record['department']) ?><small><?= $escape($record['position_name']) ?></small></td>
                        <td><?= $escape(ucfirst($record['risk_level'])) ?><?= $record['risk_score'] !== null ? ' · ' . $escape($record['risk_score']) . '%' : '' ?></td>
                        <td><?= $escape($record['risk_factors']) ?></td><td><?= $escape($record['recommended_action']) ?></td>
                        <td><?= $escape($formatDate($record['assessment_date'] ?: $record['created_at'])) ?></td><td><?php $renderStatusControl('risk', $record); ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
            <div class="mode-card" style="margin-top:1rem;">
                <h2>Training engagement</h2>
                <?php if (!$records['engagement']): ?><p class="wf-inbox-empty">No Workforce engagement records have arrived.</p><?php else: ?>
                <div class="wf-inbox-table-wrap"><table class="wf-inbox-table"><thead><tr><th>Learner / group</th><th>Department</th><th>Activity</th><th>Engagement</th><th>Recommended action</th><th>Received</th><th>Review</th></tr></thead><tbody>
                    <?php foreach ($records['engagement'] as $record): ?><tr>
                        <td><strong><?= $escape($record['learner_name'] ?: ($record['learner_id'] ? 'Employee #' . $record['learner_id'] : ucwords(str_replace('-', ' ', $record['learner_team_type'])))) ?></strong></td>
                        <td><?= $escape($record['department']) ?></td><td><?= $escape(ucwords(str_replace('-', ' ', $record['training_activity']))) ?></td>
                        <td><?= $escape(ucwords(str_replace('-', ' ', $record['engagement_status']))) ?><small><?= $escape($record['engagement_data']) ?></small></td>
                        <td><?= $escape($record['recommended_action']) ?></td><td><?= $escape($formatDate($record['created_at'])) ?></td>
                        <td><?php $renderStatusControl('engagement', $record); ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
        </section>

        <section class="tab-content" id="wf-actions" data-tab="wf-actions">
            <div class="mode-card">
                <h2>Training actions</h2>
                <p>Requests currently associated with Workforce records. New training requests are not created automatically; staff review the source first.</p>
                <?php if (!$records['training_request']): ?><p class="wf-inbox-empty">No Workforce training actions have arrived.</p><?php else: ?>
                <div class="wf-inbox-table-wrap"><table class="wf-inbox-table"><thead><tr><th>Request</th><th>Department</th><th>Priority</th><th>Source record</th><th>Request details</th><th>Received</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($records['training_request'] as $record): ?><tr>
                        <td><strong><?= $escape($record['skill_name']) ?></strong><?php if ($record['learner_id']): ?><small>Employee #<?= (int) $record['learner_id'] ?></small><?php endif; ?></td>
                        <td><?= $escape($record['target_department']) ?></td><td><?= $escape(ucfirst($record['priority_level'])) ?></td>
                        <td><?= $escape($record['external_reference_id']) ?></td><td><?= $escape($record['message']) ?></td>
                        <td><?= $escape($formatDate($record['created_at'])) ?></td><td><?php $renderStatusControl('training_request', $record); ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
        </section>
    </div>
</div>