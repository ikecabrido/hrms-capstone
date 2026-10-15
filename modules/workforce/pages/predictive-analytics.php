<?php

$page = 'predictive-analytics';
include __DIR__ . '/../includes/analytics-setup.php';

$view = [
    'eyebrow' => 'Forward-looking insights',
    'title' => 'Predictive Analytics',
    'description' => 'Use workforce trends to plan ahead and reduce operational risk.',
    'cards' => [
        ['Forecast accuracy', '91%', 'Validated against last quarter'],
        ['Hiring demand', '+14', 'Expected next quarter'],
        ['High-risk roles', '4', 'Require succession planning'],
        ['Recommended actions', '9', 'Ready for review'],
    ],
];
include __DIR__ . '/../includes/analytics-header.php';
?>
<form class="wa-filters" method="get">
    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
    <label>Employee
        <select name="employee_id" onchange="this.form.submit()">
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
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach ($employeeStatuses as $statusValue => $statusLabel): ?>
                <option value="<?= htmlspecialchars($statusValue) ?>" <?= $statusFilter === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(strtolower($statusLabel))) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="wa-button" type="submit">Apply filters</button>
    <?php if ($employeeFilter !== '' || $statusFilter !== ''): ?><a class="wa-clear" href="?page=<?= urlencode($page) ?>">Clear</a><?php endif; ?>
</form>
<?php if ($employeeFilter !== '' || $statusFilter !== ''): ?>
    <p class="wa-filter-summary" role="status">
        Showing <?= count($employees) ?> of <?= $employeeCount ?> employees
        <?php if ($employeeFilter !== ''): ?> for the selected employee<?php endif; ?>
        <?php if ($statusFilter !== ''): ?> with status <?= htmlspecialchars(ucwords(strtolower($statusFilter))) ?><?php endif; ?>.
    </p>
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
            <?php if (count($employees) > 5): ?><p class="wa-muted">Showing the first 5 matching employees.</p><?php endif; ?>
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
<?php include __DIR__ . '/../includes/analytics-footer.php'; ?>
