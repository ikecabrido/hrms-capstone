<?php

$page = 'diversity-inclusion';
include __DIR__ . '/../includes/analytics-setup.php';

$genderTotal = max(1, (int) ($diversityMetrics['active_employees'] ?? 0));
$maxGender = max(array_column($diversity['gender'], 'total') ?: [1]);
$maxDepartment = max(array_column($diversity['departments'], 'total') ?: [1]);
$view = [
    'eyebrow' => 'People equity',
    'title' => 'Diversity & Inclusion',
    'description' => 'Track representation and inclusion signals across your workforce.',
    'cards' => [
        ['Women in workforce', $diversityMetrics['women_percent'] . '%', 'Of active employees'],
        ['Leadership diversity', $diversityMetrics['leadership_percent'] . '%', 'Active leadership roles'],
        ['Departments tracked', count($diversity['departments']), 'With active employees'],
        ['Profile completeness', $diversityMetrics['profile_percent'] . '%', 'Required fields populated'],
    ],
];
include __DIR__ . '/../includes/analytics-header.php';
?>
<div class="wa-dashboard-grid wa-diversity-grid">
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Representation analytics</span><h2>Gender representation</h2><p class="wa-muted">Active employees by declared gender.</p></div><span class="wa-pill"><?= $genderTotal ?> active</span></div>
        <div class="wa-diversity-bars">
            <?php foreach ($diversity['gender'] as $group): ?>
                <div><div><span><?= htmlspecialchars($group['label']) ?></span><b><?= (int) $group['total'] ?> <small><?= round(((int) $group['total'] / $genderTotal) * 100) ?>%</small></b></div><i><em style="width:<?= round(((int) $group['total'] / $maxGender) * 100) ?>%"></em></i></div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Generational analytics</span><h2>Age distribution</h2><p class="wa-muted">Representation across active workforce age groups.</p></div></div>
        <div class="wa-demographics"><?php foreach ($diversity['age'] as $group): ?><div><span><?= htmlspecialchars($group['label']) ?></span><b><?= (int) $group['total'] ?></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Organizational equity</span><h2>Representation by department</h2><p class="wa-muted">Compare workforce size and women representation.</p></div></div>
        <div class="wa-diversity-departments"><?php foreach ($diversity['departments'] as $department): ?><div><span><?= htmlspecialchars($department['department']) ?></span><b style="width:<?= round(((int) $department['total'] / $maxDepartment) * 100) ?>%"><?= (int) $department['total'] ?> <small><?= round(((int) $department['women'] / max(1, (int) $department['total'])) * 100) ?>% women</small></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Employment access</span><h2>Employment type mix</h2><p class="wa-muted">Active workforce by employment arrangement.</p></div></div>
        <div class="wa-reason-list wa-inclusion-list"><?php foreach ($diversity['employment_types'] as $type): ?><div><span><?= htmlspecialchars($type['label']) ?></span><b><?= (int) $type['total'] ?></b></div><?php endforeach; ?></div>
    </article>
</div>
<article class="wa-panel wa-exits-panel">
    <div class="wa-panel-heading"><div><span class="wa-eyebrow">Leadership representation</span><h2>Active leadership profile</h2><p class="wa-muted">Roles identified from current position titles.</p></div></div>
    <div class="wa-table-scroll"><table class="wa-table"><thead><tr><th>Employee</th><th>Position</th><th>Department</th><th>Gender</th></tr></thead><tbody>
        <?php foreach ($diversity['leaders'] as $leader): ?>
            <tr><td><?= htmlspecialchars($leader['employee_name']) ?></td><td><?= htmlspecialchars($leader['position']) ?></td><td><?= htmlspecialchars($leader['department']) ?></td><td><?= htmlspecialchars($leader['gender']) ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</article>
<?php include __DIR__ . '/../includes/analytics-footer.php'; ?>
