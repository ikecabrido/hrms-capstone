<?php
$employeeClass = $employeeClass ?? new Employee();
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($_POST['action'] ?? '', ['send_reporting_requirement', 'update_reporting_requirement'], true)) {
    $isReportingUpdate = ($_POST['action'] ?? '') === 'update_reporting_requirement';
    $reportingId = (int) ($_POST['id'] ?? 0);
    $target = trim((string) ($_POST['target_name'] ?? ''));
    $periodStart = trim((string) ($_POST['period_start'] ?? ''));
    $periodEnd = trim((string) ($_POST['period_end'] ?? ''));
    $frequency = strtolower(trim((string) ($_POST['reporting_frequency'] ?? '')));
    $completionTarget = filter_var($_POST['completion_target'] ?? null, FILTER_VALIDATE_FLOAT);
    $output = trim((string) ($_POST['expected_output'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $validFrequencies = ['weekly', 'monthly', 'quarterly', 'annual'];
    $validTargets = ['training participation', 'training completion', 'certification expiry', 'skill gap progress'];
    $validOutputs = ['completion report by department', 'learner participation list', 'certification expiry report', 'skill gap action plan'];
    $validDates = DateTime::createFromFormat('Y-m-d', $periodStart) && DateTime::createFromFormat('Y-m-d', $periodEnd);

    if (
        !in_array($target, $validTargets, true)
        || !$validDates
        || $periodEnd < $periodStart
        || !in_array($frequency, $validFrequencies, true)
        || $completionTarget === false
        || $completionTarget < 0
        || $completionTarget > 100
        || !in_array($output, $validOutputs, true)
    ) {
        $error = 'Choose valid target and output options, dates, frequency, and a completion target from 0 to 100.';
    } else {
        try {
            $reportingData = [
                'target_name' => $target,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'reporting_frequency' => $frequency,
                'completion_target' => $completionTarget,
                'expected_output' => $output,
                'notes' => $notes,
                'created_by' => (int) ($_SESSION['employee_id'] ?? 0),
            ];
            if ($isReportingUpdate && $reportingId > 0) {
                $employeeClass->updateLearningReportingRequirement($reportingId, $reportingData);
                $message = "Reporting requirement #{$reportingId} was updated.";
            } else {
                $id = $employeeClass->saveLearningReportingRequirement($reportingData);
                $message = "Reporting requirement #{$id} was sent to the Learning system.";
            }
        } catch (Throwable $exception) {
            error_log('[workforce] Implementation reporting requirement failed: ' . $exception->getMessage());
            $error = 'The reporting requirement could not be sent. Please try again.';
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete_reporting_requirement') {
    $reportingId = (int) ($_POST['id'] ?? 0);
    if ($reportingId > 0) {
        try {
            $employeeClass->deleteLearningReportingRequirement($reportingId);
            $message = "Reporting requirement #{$reportingId} was deleted.";
        } catch (Throwable $exception) {
            error_log('[workforce] Reporting requirement deletion failed: ' . $exception->getMessage());
            $error = 'The reporting requirement could not be deleted.';
        }
    }
}

$requirements = $employeeClass->getLearningReportingRequirements();
?>
<section class="wf-resource-page">
    <div class="wf-resource-hero">
        <span class="wa-eyebrow">Operating model</span>
        <h1>Implementation Guide</h1>
        <p>Set clear reporting targets, define the required input, and send an expected output to the Learning system.</p>
    </div>

    <section class="wf-resource-send" style="margin-top:1.25rem;">
        <span class="wa-eyebrow">Workforce to Learning</span>
        <h2>Reporting requirement / target</h2>
        <p class="wf-resource-send-intro">Choose the reporting input and output that Learning should prepare.</p>
        <div class="wf-io-flow" aria-label="Workforce to Learning data flow">
            <div class="wf-io-flow-step wf-io-flow-input">
                <span class="wf-io-flow-number">1</span>
                <div><strong>Workforce input</strong><small>Define the target, period, and success goal.</small></div>
            </div>
            <span class="wf-io-flow-arrow" aria-hidden="true">→</span>
            <div class="wf-io-flow-step wf-io-flow-output">
                <span class="wf-io-flow-number">2</span>
                <div><strong>Learning output</strong><small>Choose the report or action plan to receive.</small></div>
            </div>
        </div>
        <?php if ($message): ?>
            <p class="wf-resource-send-status" role="status"><?= htmlspecialchars($message) ?></p>
            <script>
                window.alert(<?= json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                window.location.replace(window.location.pathname + '?page=implementation-guide');
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="wf-resource-send-status" style="color:#b42318;" role="alert"><?= htmlspecialchars($error) ?></p>
            <script>
                window.alert(<?= json_encode($error, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            </script>
        <?php endif; ?>
        <form method="post" class="wf-resource-send-grid">
            <input type="hidden" name="action" value="send_reporting_requirement">
            <input type="hidden" name="id" value="">
            <label class="wf-io-input-field">Reporting target / input
                <select name="target_name" required>
                    <option value="">Choose a target</option>
                    <option value="training participation">Training participation</option>
                    <option value="training completion">Training completion</option>
                    <option value="certification expiry">Certification expiry</option>
                    <option value="skill gap progress">Skill gap progress</option>
                </select>
            </label>
            <label class="wf-io-input-field">Reporting frequency
                <select name="reporting_frequency" required>
                    <option value="">Choose frequency</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annual">Annual</option>
                </select>
            </label>
            <label class="wf-io-input-field">Period start
                <input type="date" name="period_start" required>
            </label>
            <label class="wf-io-input-field">Period end
                <input type="date" name="period_end" required>
            </label>
            <label class="wf-io-input-field">Completion target (%)
                <input type="number" name="completion_target" min="0" max="100" step="0.01" required placeholder="90">
            </label>
            <label class="wf-io-output-field">Expected output
                <select name="expected_output" required>
                    <option value="">Choose output</option>
                    <option value="completion report by department">Completion report by department</option>
                    <option value="learner participation list">Learner participation list</option>
                    <option value="certification expiry report">Certification expiry report</option>
                    <option value="skill gap action plan">Skill gap action plan</option>
                </select>
            </label>
            <label class="wf-resource-send-wide wf-io-output-field">Notes
                <textarea name="notes" rows="3" placeholder="Optional instructions for the Learning team."></textarea>
            </label>
            <button type="submit" class="wf-resource-send-button"><span aria-hidden="true">↗</span> <span class="wf-submit-label">Send target to Learning system</span></button>
        </form>
    </section>

    <?php if ($requirements): ?>
        <section class="wf-engagement-review wf-io-results" style="margin-top:1.25rem;">
            <div class="wf-engagement-review-heading">
                <div><span class="wa-eyebrow">Output received</span><h2>Recent reporting targets</h2><p>Requirements previously sent from Workforce and ready for Learning.</p></div>
                <span class="wf-io-results-count"><?= count($requirements) ?> saved</span>
            </div>
            <div class="wa-table-scroll"><table class="wa-table"><thead><tr><th>Target</th><th>Frequency</th><th>Period</th><th>Goal</th><th>Output</th><th>Notes</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($requirements as $requirement): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords($requirement['target_name'])) ?></td>
                        <td><?= htmlspecialchars(ucfirst($requirement['reporting_frequency'])) ?></td>
                        <td><?= htmlspecialchars($requirement['reporting_period_start']) ?> to <?= htmlspecialchars($requirement['reporting_period_end']) ?></td>
                        <td><?= htmlspecialchars((string) $requirement['completion_target']) ?>%</td>
                        <td><?= htmlspecialchars(ucwords($requirement['expected_output'])) ?></td>
                        <td><?= $requirement['notes'] !== null && $requirement['notes'] !== '' ? nl2br(htmlspecialchars($requirement['notes'])) : '—' ?></td>
                        <td><span class="wa-pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $requirement['status']))) ?></span></td>
                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($requirement['created_at']))) ?></td>
                        <td>
                            <button type="button" class="wa-button wa-button-small" data-edit-reporting="<?= htmlspecialchars(json_encode($requirement), ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this reporting requirement?');">
                                <input type="hidden" name="action" value="delete_reporting_requirement">
                                <input type="hidden" name="id" value="<?= (int) $requirement['id'] ?>">
                                <button type="submit" class="wa-button wa-button-small">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        </section>
        <script>
        document.querySelectorAll('[data-edit-reporting]').forEach(function (button) {
            button.addEventListener('click', function () {
                var record = JSON.parse(button.dataset.editReporting);
                var form = document.querySelector('form.wf-resource-send-grid');
                form.querySelector('[name="action"]').value = 'update_reporting_requirement';
                form.querySelector('[name="id"]').value = record.id;
                form.querySelector('[name="target_name"]').value = record.target_name || '';
                form.querySelector('[name="reporting_frequency"]').value = record.reporting_frequency || '';
                form.querySelector('[name="period_start"]').value = record.reporting_period_start || '';
                form.querySelector('[name="period_end"]').value = record.reporting_period_end || '';
                form.querySelector('[name="completion_target"]').value = record.completion_target || '';
                form.querySelector('[name="expected_output"]').value = record.expected_output || '';
                form.querySelector('[name="notes"]').value = record.notes || '';
                form.querySelector('.wf-submit-label').textContent = 'Update reporting requirement';
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
        </script>
    <?php endif; ?>

</section>
