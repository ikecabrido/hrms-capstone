<?php

$page = 'custom-reports';
include __DIR__ . '/../includes/analytics-setup.php';

$reportingMessage = '';
$reportingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_learning_requirement') {
    $targetName = trim((string) ($_POST['target_name'] ?? ''));
    $periodStart = trim((string) ($_POST['period_start'] ?? ''));
    $periodEnd = trim((string) ($_POST['period_end'] ?? ''));
    $completionTarget = filter_var($_POST['completion_target'] ?? null, FILTER_VALIDATE_FLOAT);
    $expectedOutput = trim((string) ($_POST['expected_output'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    $validDates = DateTime::createFromFormat('Y-m-d', $periodStart)
        && DateTime::createFromFormat('Y-m-d', $periodEnd);
    if ($targetName === '' || !$validDates || $periodEnd < $periodStart || $completionTarget === false
        || $completionTarget < 0 || $completionTarget > 100 || $expectedOutput === '') {
        $reportingError = 'Complete the target, valid reporting period, completion target (0-100), and expected output.';
    } else {
        try {
            $requirementId = $employeeClass->saveLearningReportingRequirement([
                'target_name' => $targetName,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'completion_target' => $completionTarget,
                'expected_output' => $expectedOutput,
                'notes' => $notes,
                'created_by' => (int) ($_SESSION['employee_id'] ?? 0),
            ]);
            $reportingMessage = "Reporting requirement #{$requirementId} was sent to the Learning system.";
        } catch (Throwable $exception) {
            error_log('[workforce] Reporting requirement failed: ' . $exception->getMessage());
            $reportingError = 'The reporting requirement could not be sent. Please try again.';
        }
    }
}
$reportingRequirements = $employeeClass->getLearningReportingRequirements();
$trainingReport = $employeeClass->getTrainingReport();
$view = [
    'eyebrow' => 'Workforce intelligence',
    'title' => 'Custom Reports',
    'description' => 'Build focused reports from your workforce data and share decisions faster.',
    'cards' => [
        ['Saved reports', '12', 'Available to your team'],
        ['Scheduled reports', '4', 'Running automatically'],
        ['Data coverage', '96%', 'Profiles with complete data'],
        ['Last refresh', 'Today', date('h:i A')],
        ['Training participation', $trainingReport['participating_employees'], 'Employees with an enrollment'],
        ['Training completion rate', $trainingReport['completion_rate'] . '%', 'Completed enrollments'],
    ],
];
$showReportActions = true;
include __DIR__ . '/../includes/analytics-header.php';
?>
<form class="wa-filters" method="get">
    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
    <label>Employee
        <select name="employee_id">
            <option value="">All employees</option>
            <?php foreach ($allEmployees as $employee): ?>
                <?php $employeeName = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?>
                <option value="<?= htmlspecialchars((string) $employee['employee_id']) ?>" <?= $employeeFilter === (string) $employee['employee_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($employeeName !== '' ? $employeeName : 'Employee #' . $employee['employee_id']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Status
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($employeeStatuses as $statusValue => $statusLabel): ?>
                <option value="<?= htmlspecialchars($statusValue) ?>" <?= $statusFilter === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(strtolower($statusLabel))) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="wa-button" type="submit">Apply filters</button>
    <?php if ($employeeFilter !== '' || $statusFilter !== ''): ?><a class="wa-clear" href="?page=<?= urlencode($page) ?>">Clear</a><?php endif; ?>
</form>
<section class="wa-panel" style="margin-bottom:1rem;">
    <div class="wa-panel-heading">
        <div><span class="wa-eyebrow">Workforce to Learning</span><h2>Reporting requirement target</h2><p class="wa-muted">Define the target input and expected output that the Learning system should track.</p></div>
        <span class="wa-pill">Learning input</span>
    </div>
    <?php if ($reportingMessage): ?><p class="wa-empty" style="color:var(--wa-teal);"><?= htmlspecialchars($reportingMessage) ?></p><?php endif; ?>
    <?php if ($reportingError): ?><p class="wa-empty" style="color:#b42318;"><?= htmlspecialchars($reportingError) ?></p><?php endif; ?>
    <form method="post" class="wa-filters" style="margin:0;">
        <input type="hidden" name="action" value="send_learning_requirement">
        <label>Reporting target
            <input type="text" name="target_name" maxlength="150" required placeholder="e.g. Q4 compliance training">
        </label>
        <label>Completion target (%)
            <input type="number" name="completion_target" min="0" max="100" step="0.01" required placeholder="90">
        </label>
        <label>Period start
            <input type="date" name="period_start" required>
        </label>
        <label>Period end
            <input type="date" name="period_end" required>
        </label>
        <label style="grid-column:1/-1;">Expected output
            <input type="text" name="expected_output" maxlength="255" required placeholder="e.g. Completion report by department and course">
        </label>
        <label style="grid-column:1/-1;">Notes
            <textarea name="notes" rows="3" placeholder="Optional requirements for the Learning team."></textarea>
        </label>
        <button class="wa-button" type="submit">Send to Learning system</button>
    </form>
</section>
<?php if ($reportingRequirements): ?>
<section class="wa-panel" style="margin-bottom:1rem;">
    <div class="wa-panel-heading"><div><span class="wa-eyebrow">Learning handoff</span><h2>Submitted reporting requirements</h2><p class="wa-muted">Recent targets sent from Workforce.</p></div></div>
    <div class="wa-table-scroll"><table class="wa-table"><thead><tr><th>Target</th><th>Period</th><th>Completion target</th><th>Expected output</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($reportingRequirements as $requirement): ?>
            <tr>
                <td><?= htmlspecialchars($requirement['target_name']) ?></td>
                <td><?= htmlspecialchars($requirement['reporting_period_start']) ?> to <?= htmlspecialchars($requirement['reporting_period_end']) ?></td>
                <td><?= htmlspecialchars((string) $requirement['completion_target']) ?>%</td>
                <td><?= htmlspecialchars($requirement['expected_output']) ?></td>
                <td><span class="wa-pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $requirement['status']))) ?></span></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<div class="wa-grid">
    <article class="wa-panel">
        <span class="wa-eyebrow">Workforce overview</span>
        <h2><?= htmlspecialchars($view['title']) ?> insights</h2>
        <p class="wa-muted">Review the latest workforce indicators and use them to guide your next decision.</p>
        <div class="wa-bars">
            <div><span>Current period</span><i style="width:82%"></i><b>82%</b></div>
            <div><span>Previous period</span><i style="width:68%"></i><b>68%</b></div>
            <div><span>Target</span><i style="width:90%"></i><b>90%</b></div>
        </div>
    </article>
    <article class="wa-panel">
        <span class="wa-eyebrow">Employee snapshot</span>
        <h2>Workforce coverage</h2>
        <?php if ($employees): ?>
            <table class="wa-table"><thead><tr><th>Employee</th><th>Department</th><th>Status</th></tr></thead><tbody>
                <?php foreach (array_slice($employees, 0, 5) as $employee): ?>
                    <tr><td><?= htmlspecialchars(trim($employee['first_name'] . ' ' . $employee['last_name'])) ?></td><td><?= htmlspecialchars($employee['department_name'] ?? 'Unassigned') ?></td><td><?= htmlspecialchars($employee['status'] ?? 'Active') ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php else: ?>
            <p class="wa-muted"><?= ($employeeFilter !== '' || $statusFilter !== '') ? 'No employees match the selected filters.' : 'No employee data is available yet.' ?></p>
        <?php endif; ?>
    </article>
</div>
<section class="wa-panel" style="margin-top:1rem;">
    <div class="wa-panel-heading">
        <div><span class="wa-eyebrow">Learning analytics</span><h2>Training participation &amp; completion</h2><p class="wa-muted">Participation and completion data from the Learning module.</p></div>
        <span class="wa-pill"><?= $trainingReport['total_enrollments'] ?> enrollments</span>
    </div>
    <div class="wa-coverage">
        <strong><?= htmlspecialchars((string) $trainingReport['completion_rate']) ?>%</strong>
        <span>Overall completion rate<small><?= $trainingReport['completed_enrollments'] ?> completed of <?= $trainingReport['total_enrollments'] ?> enrollments</small></span>
    </div>
    <ul class="wa-source-list">
        <li>Participating employees <b><?= $trainingReport['participating_employees'] ?></b></li>
        <li>Active enrollments <b><?= $trainingReport['active_enrollments'] ?></b></li>
        <li>Completed enrollments <b><?= $trainingReport['completed_enrollments'] ?></b></li>
    </ul>
</section>
<div class="wa-grid" style="margin-top:1rem;">
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Course performance</span><h2>Participation by course</h2><p class="wa-muted">Courses ranked by learner participation.</p></div></div>
        <?php if ($trainingReport['course_breakdown']): ?>
            <div class="wa-table-scroll"><table class="wa-table"><thead><tr><th>Course</th><th>Participants</th><th>Completed</th><th>Rate</th></tr></thead><tbody>
                <?php foreach ($trainingReport['course_breakdown'] as $course): ?>
                    <tr><td><?= htmlspecialchars($course['title']) ?></td><td><?= (int) $course['participants'] ?></td><td><?= (int) $course['completed'] ?></td><td><?= htmlspecialchars((string) ($course['completion_rate'] ?? 0)) ?>%</td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?>
            <p class="wa-empty">No training participation data is available yet.</p>
        <?php endif; ?>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Department insight</span><h2>Participation by department</h2><p class="wa-muted">Training activity across departments.</p></div></div>
        <?php if ($trainingReport['department_breakdown']): ?>
            <ul class="wa-source-list">
                <?php foreach ($trainingReport['department_breakdown'] as $department): ?>
                    <li><?= htmlspecialchars($department['department_name']) ?><b><?= (int) $department['participants'] ?> participants / <?= (int) $department['completed'] ?> completed</b></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="wa-empty">No department training data is available yet.</p>
        <?php endif; ?>
    </article>
</div>
<?php include __DIR__ . '/../includes/analytics-footer.php'; ?>
