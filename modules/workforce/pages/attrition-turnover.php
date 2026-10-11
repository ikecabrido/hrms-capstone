<?php

$page = 'attrition-turnover';
include __DIR__ . '/../includes/analytics-setup.php';

$summary = $attrition['summary'];
$latestRate = $attrition['monthly'][0]['attrition_rate_percent'] ?? 0;
$highestDepartment = $attrition['departments'][0]['department'] ?? 'No data';
$view = [
    'eyebrow' => 'Retention intelligence',
    'title' => 'Attrition & Turnover',
    'description' => 'Understand retention patterns and identify teams that need attention.',
    'cards' => [],
];
include __DIR__ . '/../includes/analytics-header.php';
?>
<div class="wa-cards wa-attrition-kpis">
    <?php foreach ([
        ['Total resignations', (int) ($summary['resignations'] ?? 0), 'Voluntary exits'],
        ['Total terminations', (int) ($summary['terminations'] ?? 0), 'Involuntary exits'],
        ['Voluntary turnover', (int) ($summary['voluntary'] ?? 0), 'Resigned, retired, or other'],
        ['Involuntary turnover', (int) ($summary['involuntary'] ?? 0), 'Terminated employees'],
        ['Monthly turnover rate', $latestRate . '%', 'Latest recorded month'],
        ['Average tenure before leaving', ($summary['average_tenure'] ?? 0) . ' yrs', 'Across tracked exits'],
        ['Top reason for leaving', $attrition['reasons'][0]['reason'] ?? 'Not specified', 'Most reported reason'],
        ['Highest-turnover department', $highestDepartment, 'Most recorded exits'],
    ] as $card): ?>
        <article class="wa-card"><span><?= htmlspecialchars($card[0]) ?></span><strong><?= htmlspecialchars((string) $card[1]) ?></strong><small><?= htmlspecialchars($card[2]) ?></small></article>
    <?php endforeach; ?>
</div>
<div class="wa-dashboard-grid wa-attrition-grid">
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Trend analytics</span><h2>Turnover by month</h2><p class="wa-muted">Monthly separations and attrition rate.</p></div></div>
        <div class="wa-mini-chart"><?php foreach (array_reverse($attrition['monthly']) as $month): ?><div><i style="height:<?= min(100, ((int) $month['total_separations']) * 16 + 8) ?>%"></i><span><?= htmlspecialchars(substr($month['period'], 0, 3)) ?></span></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Reason analytics</span><h2>Resignation reasons</h2><p class="wa-muted">Top reported reasons for leaving.</p></div></div>
        <div class="wa-reason-list"><?php foreach ($attrition['reasons'] as $reason): ?><div><span><?= htmlspecialchars($reason['reason']) ?></span><b><?= (int) $reason['total'] ?></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Department analytics</span><h2>Turnover by department</h2></div></div>
        <div class="wa-departments"><?php $max = max(array_column($attrition['departments'], 'total') ?: [1]); foreach ($attrition['departments'] as $department): ?><div><span><?= htmlspecialchars($department['department']) ?></span><b style="width:<?= round(((int) $department['total'] / $max) * 100) ?>%"><?= (int) $department['total'] ?></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Service analytics</span><h2>Turnover by length of service</h2></div></div>
        <div class="wa-tenure-list"><?php foreach (['0-1 year' => 18, '1-3 years' => 34, '3-5 years' => 28, '5+ years' => 20] as $label => $percent): ?><div><span><?= $label ?></span><b><?= $percent ?>%</b></div><?php endforeach; ?></div>
    </article>
</div>
<article class="wa-panel wa-exits-panel">
    <div class="wa-panel-heading"><div><span class="wa-eyebrow">Exit records</span><h2>Employees who left</h2><p class="wa-muted">Date hired, exit date, and recorded reason.</p></div></div>
    <div class="wa-table-scroll"><table class="wa-table"><thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Date hired</th><th>Exit date</th><th>Reason</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($attrition['employees'] as $employee): ?>
            <tr><td><?= htmlspecialchars($employee['employee_name']) ?></td><td><?= htmlspecialchars($employee['department']) ?></td><td><?= htmlspecialchars($employee['position']) ?></td><td><?= htmlspecialchars($employee['hire_date'] ?? '-') ?></td><td><?= htmlspecialchars($employee['separation_date']) ?></td><td><?= htmlspecialchars($employee['reason_for_leaving'] ?: ucfirst($employee['separation_type'])) ?></td><td><button class="wa-button wa-button-small" type="button" onclick="openActionModal(this)">Action</button></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</article>
<?php include __DIR__ . '/../includes/analytics-footer.php'; ?>
