<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/EmployeeDocuments.php';

$employeeClass = new Employee();
$stats = $employeeClass->getDashboardStats(); // reused as-is — same query as before

$docsClass = new EmployeeDocuments();
$reqCounts = $docsClass->getRequirementStatusCounts(); // reused as-is
$reqMap = [];
foreach ($reqCounts as $row) {
    $reqMap[$row['status']] = (int) $row['cnt'];
}

// ── Derived values for the two charts (computed here, not hardcoded — both
//    charts are built entirely from $stats, which already holds real DB data). ──

$deptRows = $stats['by_department'] ?? [];
$maxDeptCount = 0;
foreach ($deptRows as $row) {
    $maxDeptCount = max($maxDeptCount, (int) $row['cnt']);
}
$maxDeptCount = max($maxDeptCount, 1); // avoid div-by-zero when every count is 0

$statusRows = $stats['by_status'] ?? [];
$statusTotal = 0;
foreach ($statusRows as $row) {
    $statusTotal += (int) $row['cnt'];
}

// Fixed, consistent palette for known employment_status enum values
// (Active, Resigned, Terminated, Probationary) plus a neutral fallback for
// anything else the enum might contain — no invented statuses, this only
// colors whatever $statusRows actually returns from the database.
$statusColors = [
    'Active'       => '#16a34a',
    'Probationary' => '#2563eb',
    'Resigned'     => '#94a3b8',
    'Terminated'   => '#dc2626',
];
$fallbackColors = ['#7c3aed', '#ea580c', '#0891b2', '#65a30d'];

$doughnutSegments = [];
$cumulativePct = 0;
$colorIndex = 0;
foreach ($statusRows as $row) {
    $label = $row['employment_status'] ?? 'Unspecified';
    $count = (int) $row['cnt'];
    $pct = $statusTotal > 0 ? ($count / $statusTotal) * 100 : 0;
    $color = $statusColors[$label] ?? $fallbackColors[$colorIndex % count($fallbackColors)];
    if (!isset($statusColors[$label])) {
        $colorIndex++;
    }
    $doughnutSegments[] = [
        'label' => $label,
        'count' => $count,
        'pct'   => $pct,
        'color' => $color,
        'start' => $cumulativePct,
    ];
    $cumulativePct += $pct;
}

// Build the conic-gradient stops string for the doughnut (pure CSS, no chart library)
$gradientStops = [];
foreach ($doughnutSegments as $seg) {
    $from = round($seg['start'] * 3.6, 2);   // percent -> degrees
    $to   = round(($seg['start'] + $seg['pct']) * 3.6, 2);
    $gradientStops[] = "{$seg['color']} {$from}deg {$to}deg";
}
$doughnutStyle = $statusTotal > 0
    ? 'background: conic-gradient(' . implode(', ', $gradientStops) . ');'
    : 'background: var(--color9);';
?>

<div class="module-header">
    <h1>Employee Management Dashboard</h1>
    <p>Overview of employee headcount, departments, and pending requirements.</p>
</div>

<div class="module-content">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="stat-label">Active Employees</div>
                <h2 class="stat-value"><?= (int) $stats['total_active'] ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-gray"><i class="fa-solid fa-box-archive"></i></div>
            <div>
                <div class="stat-label">Archived Employees</div>
                <h2 class="stat-value"><?= (int) $stats['total_archived'] ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="fa-solid fa-file-circle-check"></i></div>
            <div>
                <div class="stat-label">Requirements Submitted</div>
                <h2 class="stat-value"><?= $reqMap['Submitted'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="stat-label">Requirements Missing</div>
                <h2 class="stat-value"><?= $reqMap['Missing'] ?? 0 ?></h2>
            </div>
        </div>
    </div>

    <div class="dashboard-chart-grid">
        <div class="widget-card widget-card-wide">
            <h3>Employees by Department</h3>
            <?php if (!empty($deptRows)): ?>
                <div class="bar-chart">
                    <?php foreach ($deptRows as $row): ?>
                        <?php
                        $count = (int) $row['cnt'];
                        $widthPct = ($count / $maxDeptCount) * 100;
                        ?>
                        <div class="bar-chart-row">
                            <span class="bar-chart-label" title="<?= htmlspecialchars($row['department_name']) ?>">
                                <?= htmlspecialchars($row['department_name']) ?>
                            </span>
                            <div class="bar-chart-track">
                                <div class="bar-chart-fill" style="width: <?= $widthPct ?>%;"></div>
                            </div>
                            <span class="bar-chart-count"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-item">No department data available.</p>
            <?php endif; ?>
        </div>

        <div class="widget-card">
            <h3>Employees by Status</h3>
            <?php if (!empty($doughnutSegments)): ?>
                <div class="doughnut-wrap">
                    <div class="doughnut-chart" style="<?= $doughnutStyle ?>">
                        <div class="doughnut-center">
                            <strong><?= $statusTotal ?></strong>
                            <span>Total</span>
                        </div>
                    </div>
                    <ul class="doughnut-legend">
                        <?php foreach ($doughnutSegments as $seg): ?>
                            <li>
                                <span class="legend-dot" style="background: <?= htmlspecialchars($seg['color']) ?>;"></span>
                                <span class="legend-label"><?= htmlspecialchars($seg['label']) ?></span>
                                <span class="legend-count"><?= $seg['count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="empty-item">No status data available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="widget-card">
        <div class="widget-card-header">
            <h3>Recently Hired</h3>
            <a href="?page=employee-database" data-page="employee-database" class="view-all-link">View All →</a>
        </div>
        <?php if (!empty($stats['recent_hires'])): ?>
            <ul class="simple-list recent-hires-list">
                <?php foreach ($stats['recent_hires'] as $emp): ?>
                    <li>
                        <div class="recent-hire-name">
                            <strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong>
                            <small><?= htmlspecialchars($emp['employee_code']) ?></small>
                        </div>
                        <span class="recent-hire-date">
                            <?= $emp['hire_date'] ? htmlspecialchars(date('M d, Y', strtotime($emp['hire_date']))) : '—' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty-item">No recent hires.</p>
        <?php endif; ?>
    </div>
</div>
