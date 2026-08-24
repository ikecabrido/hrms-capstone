<?php
require_once __DIR__ . '/../controller/PerformanceReportController.php';

$controller = new PerformanceReportController();
$filters = $controller->normalizeFilters($_GET);
$data = $controller->getViewData($filters);
$stats = $data['stats'];
$reportRows = $data['rows'];
$departments = $data['departments'];
$reviewPeriods = $data['reviewPeriods'];
$reportModules = $data['reportModules'];

function reportBadgeClass(string $status): string
{
    $status = strtolower(trim($status));

    if ($status === 'excellent') {
        return 'excellent';
    }

    if ($status === 'good') {
        return 'good';
    }

    if ($status === 'average') {
        return 'average';
    }

    return 'fair';
}

function scoreText($value): string
{
    return $value === null || $value === '' ? '0' : (string) $value;
}
?>

<link rel="stylesheet" href="css/pages/performance-report.css">
   <h2 style="margin: 0; font-size: 20px;">Performance Reports</h2>
                    <span class="muted">Generate and compare performance metrics across all performance management modules.</span>
                </div>
                <div class="topbar-right"></div>
            </div>
<div class="performance-report-page">
    <div class="report-shell">
        <div class="report-main-panel">
            <div class="topbar">
                <div class="topbar-left">
              

            <div class="report-summary-grid">
                <div class="summary-card">
                    <div class="summary-label"><span>Total Employees Evaluated</span><i class="fa-solid fa-users"></i></div>
                    <div class="summary-value"><?= number_format((int) ($stats['total_employees'] ?? 0)) ?></div>
                    <div class="summary-meta">Employees in current report</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Average Performance Score</span><i class="fa-solid fa-star"></i></div>
                    <div class="summary-value"><?= number_format((float) ($stats['average_performance_score'] ?? 0), 1) ?></div>
                    <div class="summary-meta">Overall weighted score</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Goal Completion Rate</span><i class="fa-solid fa-bullseye"></i></div>
                    <div class="summary-value"><?= number_format((float) ($stats['goal_completion_rate'] ?? 0), 1) ?>%</div>
                    <div class="summary-meta">Goal execution rate</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Average KPI Achievement</span><i class="fa-solid fa-chart-column"></i></div>
                    <div class="summary-value"><?= number_format((float) ($stats['average_kpi_achievement'] ?? 0), 1) ?>%</div>
                    <div class="summary-meta">KPI target attainment</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Average Appraisal Score</span><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="summary-value"><?= number_format((float) ($stats['average_appraisal_score'] ?? 0), 1) ?></div>
                    <div class="summary-meta">Review performance rating</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>360° Feedback Average</span><i class="fa-solid fa-comments"></i></div>
                    <div class="summary-value"><?= number_format((float) ($stats['average_360_feedback'] ?? 0), 1) ?></div>
                    <div class="summary-meta">Peer and manager feedback</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Employees Needing Development</span><i class="fa-solid fa-user-clock"></i></div>
                    <div class="summary-value"><?= number_format((int) ($stats['employees_needing_development'] ?? 0)) ?></div>
                    <div class="summary-meta">At-risk team members</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label"><span>Training Recommendations</span><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="summary-value"><?= number_format((int) ($stats['training_recommendations'] ?? 0)) ?></div>
                    <div class="summary-meta">Development actions</div>
                </div>
            </div>

            <div class="performance-table-panel">
                <form id="performanceReportForm" method="get" class="table-toolbar">
                    <input type="hidden" name="page" value="performance-report">

                    <div class="left-controls">
                        <div>
                            <label class="muted" for="departmentFilter" style="display:block; margin-bottom:4px; font-size:11px;">Department</label>
                            <select id="departmentFilter" name="department" class="filter-select">
                                <option value="all">All Departments</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= htmlspecialchars($department) ?>" <?= (isset($_GET['department']) && strtolower((string) $_GET['department']) === strtolower((string) $department)) ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="muted" for="employeeFilter" style="display:block; margin-bottom:4px; font-size:11px;">Employee</label>
                            <select id="employeeFilter" name="employee" class="filter-select">
                                <option value="all">All Employees</option>
                                <?php foreach ($reportRows as $row): ?>
                                    <?php $employeeName = $row['employee_name'] ?? ''; ?>
                                    <?php if ($employeeName !== ''): ?>
                                        <option value="<?= htmlspecialchars($employeeName) ?>" <?= (isset($_GET['employee']) && strtolower((string) $_GET['employee']) === strtolower((string) $employeeName)) ? 'selected' : '' ?>><?= htmlspecialchars($employeeName) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="muted" for="periodFilter" style="display:block; margin-bottom:4px; font-size:11px;">Review Period</label>
                            <select id="periodFilter" name="review_period" class="filter-select">
                                <option value="all">All Periods</option>
                                <?php foreach ($reviewPeriods as $period): ?>
                                    <option value="<?= htmlspecialchars($period) ?>" <?= (isset($_GET['review_period']) && strtolower((string) $_GET['review_period']) === strtolower((string) $period)) ? 'selected' : '' ?>><?= htmlspecialchars($period) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="muted" for="ratingFilter" style="display:block; margin-bottom:4px; font-size:11px;">Performance Rating</label>
                            <select id="ratingFilter" name="performance_rating" class="filter-select">
                                <option value="all">All Ratings</option>
                                <option value="excellent" <?= (isset($_GET['performance_rating']) && strtolower((string) $_GET['performance_rating']) === 'excellent') ? 'selected' : '' ?>>Excellent</option>
                                <option value="good" <?= (isset($_GET['performance_rating']) && strtolower((string) $_GET['performance_rating']) === 'good') ? 'selected' : '' ?>>Good</option>
                                <option value="average" <?= (isset($_GET['performance_rating']) && strtolower((string) $_GET['performance_rating']) === 'average') ? 'selected' : '' ?>>Average</option>
                                <option value="fair" <?= (isset($_GET['performance_rating']) && strtolower((string) $_GET['performance_rating']) === 'fair') ? 'selected' : '' ?>>Fair</option>
                            </select>
                        </div>

                        <div>
                            <label class="muted" for="statusFilter" style="display:block; margin-bottom:4px; font-size:11px;">Status</label>
                            <select id="statusFilter" name="status" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="excellent" <?= (isset($_GET['status']) && strtolower((string) $_GET['status']) === 'excellent') ? 'selected' : '' ?>>Excellent</option>
                                <option value="good" <?= (isset($_GET['status']) && strtolower((string) $_GET['status']) === 'good') ? 'selected' : '' ?>>Good</option>
                                <option value="average" <?= (isset($_GET['status']) && strtolower((string) $_GET['status']) === 'average') ? 'selected' : '' ?>>Average</option>
                                <option value="fair" <?= (isset($_GET['status']) && strtolower((string) $_GET['status']) === 'fair') ? 'selected' : '' ?>>Fair</option>
                            </select>
                        </div>
                    </div>

                    <div class="right-controls">
                        <button type="submit" class="button-pill primary">View Report</button>
                        <button type="button" id="clearFiltersBtn" class="button-pill">Clear Filters</button>
                    </div>
                </form>

                <div style="margin-top: 18px; margin-bottom: 12px;">
                    <div style="display:flex; justify-content: space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                        <h3 style="margin:0; font-size:18px;">Report by Module</h3>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-top:12px;">
                        <?php foreach ($reportModules as $module): ?>
                            <div style="background:#fff; border:1px solid #e7ebf2; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:12px; box-shadow: 0 8px 18px rgba(15,23,42,0.04);">
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                                    <div style="width:38px; height:38px; border-radius:50%; background:#edf4ff; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                        <i class="fa-solid fa-chart-simple"></i>
                                    </div>
                                    <span class="status-chip excellent" style="font-size:10px;">Active</span>
                                </div>
                                <h4 style="margin:0; font-size:15px; color:#1f2937;"><?= htmlspecialchars($module['title']) ?></h4>
                                <p style="margin:0; font-size:12px; color:#64748b; min-height:36px;"><?= htmlspecialchars($module['subtitle']) ?></p>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="font-size:18px; color:#0f172a;"><?= htmlspecialchars($module['metric']) ?></strong>
                                    <a href="#" style="color:#1d4ed8; text-decoration:none; font-size:12px;">View Report</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <table class="performance-table" id="performanceReportTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Review Period</th>
                            <th>Goal Completion</th>
                            <th>KPI Achievement</th>
                            <th>Appraisal</th>
                            <th>360 Feedback</th>
                            <th>Overall Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reportRows)): ?>
                            <?php foreach ($reportRows as $row): ?>
                                <?php $status = strtolower((string) ($row['status'] ?? 'Fair')); ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($row['employee_name'] ?? 'Unknown Employee')) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['department'] ?? 'General')) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['review_period'] ?? 'N/A')) ?></td>
                                    <td><?= scoreText($row['goal_completion'] ?? 0) ?>%</td>
                                    <td><?= scoreText($row['kpi_achievement'] ?? 0) ?>%</td>
                                    <td><?= scoreText($row['appraisal_score'] ?? 0) ?></td>
                                    <td><?= scoreText($row['feedback_score'] ?? 0) ?></td>
                                    <td><?= scoreText($row['overall_score'] ?? 0) ?></td>
                                    <td><span class="status-chip <?= reportBadgeClass($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; padding:28px; color:#64748b;">No report data matched the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var clearButton = document.getElementById('clearFiltersBtn');
        if (clearButton) {
            clearButton.addEventListener('click', function () {
                window.location.href = '?page=performance-report';
            });
        }
    });
</script>
