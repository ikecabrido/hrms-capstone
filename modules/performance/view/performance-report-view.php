<?php
if (!isset($reportData) || !is_array($reportData)) {
    $reportData = [];
}
$employees = is_array($reportData['employees'] ?? null) ? $reportData['employees'] : [];
$counts = is_array($reportData['counts'] ?? null) ? $reportData['counts'] : [
    'Good Performance' => 0,
    'Average Performance' => 0,
    'Needs Improvement' => 0,
    'Not Yet Evaluated' => 0,
];
$total = array_sum($counts);
$selected = is_array($reportData['selected'] ?? null) ? $reportData['selected'] : null;
$schoolYears = is_array($reportData['school_years'] ?? null) ? $reportData['school_years'] : [];
$departments = is_array($reportData['departments'] ?? null) ? $reportData['departments'] : [];
$esc = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$score = static fn($value): string => $value === null ? '--' : number_format((float) $value, 0) . '%';
$statusClass = static fn(string $status): string => strtolower(str_replace(' ', '-', $status));
$initials = static fn(array $employee): string => strtoupper(substr(trim((string) ($employee['employee_name'] ?? 'E')), 0, 2));
$query = $_GET;
unset($query['employee_id'], $query['export']);
$baseQuery = http_build_query($query);
?>
<link rel="stylesheet" href="/hrms-capstone/modules/performance/css/pages/performance-report.css">

<section class="performance-report" aria-labelledby="performance-report-title">
    <div class="report-heading">
        <div>
            <h1 id="performance-report-title">Performance Report</h1>
            <p>View and analyze employee performance across evaluation areas.</p>
        </div>
        <div class="report-heading-actions">
            <label class="school-year-select"><i class="fa-regular fa-calendar"></i><span>School Year</span><select aria-label="School year" onchange="const url = new URL(window.location); this.value ? url.searchParams.set('school_year', this.value) : url.searchParams.delete('school_year'); window.location = url;"><option value="">All years</option><?php foreach ($schoolYears as $schoolYear): ?><option value="<?= $esc($schoolYear) ?>" <?= ($_GET['school_year'] ?? '') === $schoolYear ? 'selected' : '' ?>><?= $esc($schoolYear) ?></option><?php endforeach; ?></select></label>
            <button class="button button-light" type="button" onclick="window.print()"><i class="fa-regular fa-file-pdf"></i> Export PDF</button><a class="button button-primary" href="?page=performance-report&amp;<?= $esc($baseQuery) ?>&amp;export=excel"><i class="fa-solid fa-download"></i> Export Excel</a>
        </div>
    </div>

    <div class="summary-grid">
        <?php foreach ([['Good Performance', 'good', 'fa-trophy'], ['Average Performance', 'average', 'fa-arrow-trend-up'], ['Needs Improvement', 'needs', 'fa-triangle-exclamation'], ['Not Yet Evaluated', 'unevaluated', 'fa-user-clock']] as [$label, $tone, $icon]): ?>
            <?php $count = $counts[$label] ?? 0; ?>
            <article class="summary-card <?= $tone ?>"><div class="summary-icon"><i class="fa-solid <?= $icon ?>"></i></div><div><span><?= $esc($label) ?></span><strong><?= $count ?></strong><small><?= $total ? number_format($count / $total * 100, 1) : '0.0' ?>%</small></div></article>
        <?php endforeach; ?>
    </div>

    <div class="report-layout">
        <section class="employee-panel">
            <div class="panel-heading"><div><h2>Employee Performance</h2><p><?= count($employees) ?> employee<?= count($employees) === 1 ? '' : 's' ?> shown</p></div><a class="button button-light" href="?page=performance-report"><i class="fa-solid fa-rotate-left"></i> Reset</a></div>
            <form class="report-filters" method="get"><input type="hidden" name="page" value="performance-report"><label class="search-input"><i class="fa-solid fa-magnifying-glass"></i><input name="search" value="<?= $esc($_GET['search'] ?? '') ?>" placeholder="Search employee name..." aria-label="Search employee name"></label><select name="department" aria-label="Filter by department"><option value="">All Departments</option><?php foreach ($departments as $department): ?><option value="<?= $esc($department) ?>" <?= (string) ($_GET['department'] ?? '') === (string) $department ? 'selected' : '' ?>><?= $esc($department) ?></option><?php endforeach; ?></select><select name="status" aria-label="Filter by status"><option value="">All Status</option><?php foreach (array_keys($counts) as $status): ?><option value="<?= $esc($status) ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= $esc($status) ?></option><?php endforeach; ?></select><button class="button button-filter" type="submit"><i class="fa-solid fa-filter"></i> Filter</button></form>
            <?php if (!$employees): ?><div class="empty-state"><i class="fa-regular fa-folder-open"></i><h3>No Performance Data Available</h3><p>No employees have been evaluated yet, or the selected filters returned no results.</p></div><?php else: ?><div class="table-wrap"><table class="performance-table"><thead><tr><th>#</th><th>Employee Name</th><th>Position</th><th>Department</th><th>KPI</th><th>Attendance</th><th>Appraisal</th><th>360 Feedback</th><th>Goals</th><th>Overall</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach ($employees as $index => $employee): ?><tr><td><?= $index + 1 ?></td><td><div class="employee-cell"><span class="avatar"><?= $initials($employee) ?></span><span><strong><?= $esc($employee['employee_name']) ?></strong><small><?= $esc($employee['employee_code'] ?: 'Employee') ?></small></span></div></td><td><?= $esc($employee['position'] ?: 'Not specified') ?></td><td><?= $esc($employee['department'] ?: 'Not specified') ?></td><td><?= $score($employee['kpi']) ?></td><td><?= $score($employee['attendance']) ?></td><td><?= $score($employee['appraisal']) ?></td><td><?= $score($employee['feedback']) ?></td><td><?= $score($employee['goals']) ?></td><td><strong class="overall-value <?= $statusClass($employee['status']) ?>"><?= $score($employee['overall']) ?></strong></td><td><span class="status-badge <?= $statusClass($employee['status']) ?>"><?= $esc($employee['status']) ?></span></td><td><a class="view-button" href="?page=performance-report&amp;<?= $esc($baseQuery) ?>&amp;employee_id=<?= (int) $employee['employee_id'] ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div><div class="table-footer"><span>Showing <?= count($employees) ?> of <?= count($employees) ?> employees</span><div class="pagination" data-pagination="performance-table"><button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="active">1</button><button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button></div></div><?php endif; ?>
        </section>

        <aside class="detail-panel" aria-live="polite">
            <?php if (!$selected): ?><div class="detail-empty"><i class="fa-solid fa-arrow-pointer"></i><h2>Performance Details</h2><p>Select an employee from the list to view their report.</p></div><?php else: ?><div class="detail-heading"><h2><i class="fa-regular fa-circle-check"></i> Performance Details</h2><a href="?page=performance-report&amp;<?= $esc($baseQuery) ?>">Back to List</a></div><div class="detail-person"><span class="detail-avatar"><?= $initials($selected) ?></span><div><h3><?= $esc($selected['employee_name']) ?></h3><p><?= $esc($selected['position'] ?: 'Not specified') ?> <span>|</span> <?= $esc($selected['department'] ?: 'Not specified') ?></p><span class="status-badge <?= $statusClass($selected['status']) ?>"><?= $esc($selected['status']) ?></span></div><div class="overall-callout"><small>Overall Performance</small><strong><?= $score($selected['overall']) ?></strong></div></div><div class="score-grid"><?php foreach ([['KPI', 'kpi', 'fa-bullseye'], ['Attendance', 'attendance', 'fa-calendar-check'], ['Appraisal', 'appraisal', 'fa-star'], ['360 Feedback', 'feedback', 'fa-users'], ['Goal Achievement', 'goals', 'fa-flag-checkered']] as [$label, $key, $icon]): ?><div><i class="fa-solid <?= $icon ?>"></i><small><?= $label ?></small><strong><?= $score($selected[$key]) ?></strong></div><?php endforeach; ?></div><?php if ($selected['strengths']): ?><div class="detail-section strengths"><h3><i class="fa-solid fa-circle-check"></i> Strengths / Good Performance</h3><ul><?php foreach ($selected['strengths'] as $item): ?><li><?= $esc($item) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php if ($selected['areas']): ?><div class="detail-section improvements"><h3><i class="fa-solid fa-triangle-exclamation"></i> Areas for Improvement</h3><ul><?php foreach ($selected['areas'] as $item): ?><li><?= $esc($item) ?></li><?php endforeach; ?></ul></div><?php endif; ?><div class="detail-section recommendation"><h3><i class="fa-solid fa-lightbulb"></i> Recommendation</h3><p><?= $esc($selected['recommendation']) ?></p></div><div class="detail-actions"><a class="button button-light" href="?page=performance-report&amp;<?= $esc($baseQuery) ?>&amp;employee_id=<?= (int) $selected['employee_id'] ?>&amp;export=excel"><i class="fa-regular fa-file-excel"></i> Export Excel</a><button class="button button-primary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Report</button></div><?php endif; ?>
        </aside>
    </div>
</section>
<script>
(() => {
    const table = document.querySelector('.performance-table');
    const pager = document.querySelector('.pagination');
    if (!table || !pager) return;
    const rows = [...table.querySelectorAll('tbody tr')];
    const pageSize = 10;
    const pageCount = Math.max(1, Math.ceil(rows.length / pageSize));
    let page = 1;
    const footer = document.querySelector('.table-footer span');
    const render = () => {
        rows.forEach((row, index) => row.style.display = index >= (page - 1) * pageSize && index < page * pageSize ? '' : 'none');
        pager.innerHTML = `<button type="button" data-page="prev" ${page === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
        for (let number = 1; number <= pageCount; number += 1) pager.innerHTML += `<button type="button" data-page="${number}" class="${number === page ? 'active' : ''}">${number}</button>`;
        pager.innerHTML += `<button type="button" data-page="next" ${page === pageCount ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
        if (footer) footer.textContent = `Showing ${Math.min(rows.length, (page - 1) * pageSize + 1)}-${Math.min(rows.length, page * pageSize)} of ${rows.length} employees`;
        pager.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            const target = button.dataset.page;
            page = target === 'prev' ? Math.max(1, page - 1) : target === 'next' ? Math.min(pageCount, page + 1) : Number(target);
            render();
        }));
    };
    render();
})();
</script>