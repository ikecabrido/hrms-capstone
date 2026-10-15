<?php

$page = 'dashboard-overview';
include __DIR__ . '/../includes/analytics-setup.php';

$learningAnalytics = $employeeClass->getLearningAnalytics();
$certificationStatus = $employeeClass->getCertificationStatus();
$jobVacancies = $employeeClass->getJobVacancies();
$departmentCounts = [];
foreach ($employees as $employee) {
    $department = $employee['department_name'] ?? 'Unassigned';
    $departmentCounts[$department] = ($departmentCounts[$department] ?? 0) + 1;
}
arsort($departmentCounts);
$departmentCounts = array_slice($departmentCounts, 0, 6, true);
$maxDepartmentCount = max($departmentCounts ?: [1]);
$view = [
    'eyebrow' => 'Workforce intelligence',
    'title' => 'Dashboard & Metrics',
    'description' => 'A live view of the people signals shaping your workforce decisions.',
    'cards' => [
        ['Total employees', $dashboardMetrics['total_employees'] ?? 0, 'All active and separated records'],
        ['Active employees', $dashboardMetrics['active_employees'] ?? 0, 'Current workforce'],
        ['New hires', $dashboardMetrics['new_hires'] ?? 0, 'Since January ' . date('Y')],
        ['Employees on leave', $dashboardMetrics['employees_on_leave'] ?? 0, 'Approved leave today'],
        ['Resigned / terminated', $dashboardMetrics['separated_employees'] ?? 0, 'Current workforce movement'],
        ['Attendance rate', ($dashboardMetrics['estimated_30_day_rate'] ?? 0) . '%', 'Estimated from last 30 recorded days'],
        ['Today attendance', ($dashboardMetrics['today_attendance_rate'] ?? 0) . '%', 'Present or late vs active workforce'],
        ['Turnover rate', ($dashboardMetrics['turnover_rate'] ?? 0) . '%', 'Based on total employees'],
    ],
];
include __DIR__ . '/../includes/analytics-header.php';
?>
<section class="wa-panel wa-vacancies-panel">
    <div class="wa-panel-heading">
        <div><span class="wa-eyebrow">Recruitment planning</span><h2>Job vacancies</h2><p class="wa-muted">Open roles shared with the Recruitment module.</p></div>
    </div>
    <?php if ($jobVacancies): ?>
        <table class="wa-table">
            <thead><tr><th>Position</th><th>Location</th><th>Applicants</th></tr></thead>
            <tbody>
                <?php foreach ($jobVacancies as $vacancy): ?>
                    <?php $limit = (int) ($vacancy['max_applicants'] ?? 0); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($vacancy['title']) ?></strong><br><small><?= htmlspecialchars($vacancy['category'] ?: 'General') ?></small></td>
                        <td><?= htmlspecialchars($vacancy['location'] ?: 'Not specified') ?></td>
                        <td><?= (int) $vacancy['total_applicants'] ?> / <?= $limit > 0 ? $limit : 'Unlimited' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="wa-empty">No vacancies have been created yet.</p>
    <?php endif; ?>
</section>
<div class="wa-dashboard-grid">
    <article class="wa-panel wa-hiring-panel">
        <div class="wa-panel-heading">
            <div><span class="wa-eyebrow">Recruitment analytics</span><h2>Employee hire trend</h2><p class="wa-muted">Monthly hiring activity and recruitment progression.</p></div>
            <span class="wa-pill">Jan - Jun</span>
        </div>
        <div class="wa-hiring-content">
            <div>
                <h3>Hiring breakdown</h3>
                <table class="wa-table"><thead><tr><th>Month</th><th>New hires</th></tr></thead><tbody>
                    <?php foreach ($analytics['hire_months'] as $month): ?>
                        <tr><td><?= htmlspecialchars($month['month_name']) ?></td><td><strong><?= (int) $month['total'] ?></strong></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
            <div class="wa-trend">
                <h3>Hire trend</h3>
                <div class="wa-trend-chart">
                    <svg viewBox="0 0 360 150" role="img" aria-label="Monthly hires trend">
                        <path class="wa-chart-area" d="M10 124 C45 104, 62 91, 82 96 S122 132, 145 112 S184 49, 213 55 S260 22, 292 24 S330 24, 350 24 L350 140 L10 140 Z"></path>
                        <path class="wa-chart-line" d="M10 124 C45 104, 62 91, 82 96 S122 132, 145 112 S184 49, 213 55 S260 22, 292 24 S330 24, 350 24"></path>
                        <?php foreach ([[10, 124], [82, 96], [145, 112], [213, 55], [292, 24], [350, 24]] as $point): ?>
                            <circle cx="<?= $point[0] ?>" cy="<?= $point[1] ?>" r="4"></circle>
                        <?php endforeach; ?>
                    </svg>
                </div>
                <div class="wa-chart-labels"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span></div>
            </div>
        </div>
    </article>
    <article class="wa-panel wa-attendance-panel">
        <div class="wa-panel-heading">
            <div><span class="wa-eyebrow">Attendance analytics</span><h2>Attendance trends</h2><p class="wa-muted">Realtime attendance status across the workforce.</p></div>
            <span class="wa-pill">Realtime</span>
        </div>
        <div class="wa-donut" style="background: conic-gradient(var(--wa-teal) 0 <?= htmlspecialchars((string) $attendanceDonutPresent) ?>%, #e77b5d <?= htmlspecialchars((string) $attendanceDonutPresent) ?>% <?= htmlspecialchars((string) min(100, $attendanceDonutPresent + $attendanceDonutAbsent)) ?>%, #e7bd4d <?= htmlspecialchars((string) min(100, $attendanceDonutPresent + $attendanceDonutAbsent)) ?>% <?= htmlspecialchars((string) min(100, $attendanceDonutPresent + $attendanceDonutAbsent + $attendanceDonutLeave)) ?>%);">
            <span><strong><?= htmlspecialchars((string) number_format($attendanceRate, 1)) ?>%</strong><small>Present</small></span>
        </div>
        <div class="wa-attendance-monitor">
            <div><span>Late today</span><strong><?= htmlspecialchars((string) ($dashboardMetrics['late_today'] ?? 0)) ?></strong></div>
            <div><span>Absent today</span><strong><?= htmlspecialchars((string) ($dashboardMetrics['absent_today'] ?? 0)) ?></strong></div>
            <div><span>On leave today</span><strong><?= htmlspecialchars((string) ($dashboardMetrics['leave_today'] ?? 0)) ?></strong></div>
        </div>
        <div class="wa-legend"><span><i class="present"></i>Present</span><span><i class="absent"></i>Absent</span><span><i class="leave"></i>On leave</span></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Attendance monitoring</span><h2>Workforce attendance signal</h2><p class="wa-muted">Attendance data shared from the Time &amp; Attendance module.</p></div><span class="wa-pill"><?= (int) ($dashboardMetrics['attendance_days'] ?? 0) ?> days tracked</span></div>
        <div class="wa-coverage"><strong><?= htmlspecialchars((string) ($dashboardMetrics['estimated_30_day_rate'] ?? 0)) ?>%</strong><span>Estimated 30-day attendance<small><?= (int) ($dashboardMetrics['attended_today'] ?? 0) ?> employees present or late today</small></span></div>
        <ul class="wa-source-list"><li>Present today <b><?= (int) ($dashboardMetrics['present_today'] ?? 0) ?></b></li><li>Late today <b><?= (int) ($dashboardMetrics['late_today'] ?? 0) ?></b></li><li>Absent today <b><?= (int) ($dashboardMetrics['absent_today'] ?? 0) ?></b></li></ul>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Descriptive analytics</span><h2>Department headcount</h2><p class="wa-muted">Employee distribution by department.</p></div><span class="wa-pill"><?= $employeeCount ?> employees</span></div>
        <div class="wa-departments"><?php foreach ($departmentCounts as $department => $count): ?><div><span><?= htmlspecialchars($department) ?></span><b style="width:<?= round(($count / $maxDepartmentCount) * 100) ?>%"><?= $count ?></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Demographic analytics</span><h2>Gender distribution</h2><p class="wa-muted">Employee profile distribution.</p></div></div>
        <div class="wa-demographics"><?php foreach ($analytics['gender'] as $group): ?><div><span><?= htmlspecialchars($group['gender'] ?? 'Not specified') ?></span><b><?= (int) $group['total'] ?></b></div><?php endforeach; ?></div>
    </article>
    <article class="wa-panel">
        <div class="wa-panel-heading"><div><span class="wa-eyebrow">Learning analytics</span><h2>Training activity</h2><p class="wa-muted">Live learning activity from the Learning account.</p></div><span class="wa-pill">Connected</span></div>
        <div class="wa-coverage"><strong><?= $learningAnalytics['completed_enrollments'] ?></strong><span>Completed enrollments<small><?= $learningAnalytics['total_certificates'] ?> certificates issued</small></span></div>
        <ul class="wa-source-list"><li>Total enrollments <b><?= $learningAnalytics['total_enrollments'] ?></b></li><li>In progress <b><?= $learningAnalytics['in_progress_enrollments'] ?></b></li><li>Pending <b><?= $learningAnalytics['pending_enrollments'] ?></b></li></ul>
    </article>
</div>
<article class="wa-panel wa-certification-panel">
    <div class="wa-panel-heading">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <img src="assets/bcp-logo.png" alt="Bestlink College of the Philippines logo" style="width:42px;height:42px;object-fit:contain;">
            <div><span class="wa-eyebrow">Learning analytics</span><h2>Certification status</h2><p class="wa-muted">Issued certificates and their expiry dates.</p></div>
        </div>
        <span class="wa-pill"><?= $certificationStatus['total_certificates'] ?> total</span>
    </div>
    <?php if ($certificationStatus['certificates']): ?>
        <div class="wa-table-scroll">
            <table class="wa-table">
                <thead><tr><th>Certificate</th><th>Employee</th><th>Issued</th><th>Expiry date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($certificationStatus['certificates'] as $certificate): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($certificate['course_title']) ?></strong><br><small><?= htmlspecialchars($certificate['verification_code']) ?></small></td>
                            <td><?= htmlspecialchars($certificate['learner_name']) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($certificate['issued_at']))) ?></td>
                            <td><?= $certificate['valid_until'] ? htmlspecialchars(date('M d, Y', strtotime($certificate['valid_until']))) : 'No expiry' ?></td>
                            <td>
                                <span class="wa-pill"><?= htmlspecialchars($certificate['display_status']) ?></span>
                                <a class="wa-text-link" href="?page=certificate-print&amp;certificate_id=<?= (int) $certificate['id'] ?>" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-print" aria-hidden="true"></i> Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="wa-empty">No certificates have been issued yet.</p>
    <?php endif; ?>
</article>
<article class="wa-panel wa-operating"><div><span class="wa-eyebrow">Analytics operating model</span><h2>From data to action</h2></div><ol><?php foreach (['Collect', 'Clean', 'Describe', 'Diagnose', 'Predict', 'Prescribe', 'Operationalize'] as $index => $step): ?><li class="<?= $index === 0 ? 'current' : '' ?>"><b><?= sprintf('%02d', $index + 1) ?></b><span><?= $step ?></span></li><?php endforeach; ?></ol></article>
<?php include __DIR__ . '/../includes/analytics-footer.php'; ?>