<?php
require_once __DIR__ . '/../classes/Dashboard.php';

$dashboard = new PerformanceDashboard();
$overviewStats = $dashboard->getOverviewStats();
$performanceSummary = $dashboard->getPerformanceSummary();
$performanceDistribution = $dashboard->getPerformanceDistribution();
$kpiSummary = $dashboard->getKpiSummary();
$appraisalRows = $dashboard->getAppraisalRows();
$feedbackSummary = $dashboard->getFeedbackSummary();
$feedbackRows = $dashboard->getFeedbackRows();
$trainingSummary = $dashboard->getTrainingSummary();
$trainingRows = $dashboard->getTrainingRows();
$trendData = $dashboard->getTrendData();
$attentionEmployees = $dashboard->getAttentionEmployees();
$recentActivities = $dashboard->getRecentActivities();
$topPerformers = $dashboard->getTopPerformers();
$needsImprovement = $dashboard->getNeedsImprovement();

$distributionColors = [
    'Outstanding' => '#16a34a',
    'Exceeds Expectations' => '#22c55e',
    'Meets Expectations' => '#3b82f6',
    'Needs Improvement' => '#f59e0b',
    'Unsatisfactory' => '#ef4444',
];

$ratingBreakdown = $performanceDistribution;
$maxDistributionValue = max(array_values($ratingBreakdown));
$maxDistributionValue = $maxDistributionValue > 0 ? $maxDistributionValue : 1;

$emptyState = '<div class="empty-state">No data available</div>';
?>

<link rel="stylesheet" href="css/pages/dashboard-overview.css">

<div class="performance-dashboard">
    <div class="dashboard-topbar">
        <div class="dashboard-title-wrap">
            <h1>Performance Dashboard</h1>
            <p>Employee performance, KPI health, appraisals, feedback and development planning.</p>
        </div>
    </div>

    <div class="dashboard-stat-grid">
        <?php
        $statCards = [
            ['label' => 'Total Employees', 'value' => $overviewStats['total_employees'], 'icon' => 'fa-users', 'meta' => 'Current workforce'],
            ['label' => 'Active Performance Evaluations', 'value' => $overviewStats['active_evaluations'], 'icon' => 'fa-file-circle-check', 'meta' => 'In progress'],
            ['label' => 'Pending Appraisals', 'value' => $overviewStats['pending_appraisals'], 'icon' => 'fa-clipboard-list', 'meta' => 'Awaiting review'],
            ['label' => 'Completed Appraisals', 'value' => $overviewStats['completed_appraisals'], 'icon' => 'fa-circle-check', 'meta' => 'Finalized'],
            ['label' => 'Pending 360 Feedback', 'value' => $overviewStats['pending_feedback'], 'icon' => 'fa-people-arrows', 'meta' => 'Feedback to collect'],
            ['label' => 'Training Recommendations', 'value' => $overviewStats['training_recommendations'], 'icon' => 'fa-graduation-cap', 'meta' => 'Development plans'],
            ['label' => 'Employees Needing Attention', 'value' => $overviewStats['employees_needing_attention'], 'icon' => 'fa-triangle-exclamation', 'meta' => 'Intervention list'],
            ['label' => 'Performance Goals / KPIs', 'value' => $overviewStats['performance_goals'], 'icon' => 'fa-bullseye', 'meta' => 'Live tracking'],
        ];
        foreach ($statCards as $stat):
        ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                    <div class="stat-icon"><i class="fa-solid <?= htmlspecialchars($stat['icon']) ?>"></i></div>
                </div>
                <div>
                    <p class="stat-value"><?= (int)$stat['value'] > 0 ? number_format((int)$stat['value']) : '0' ?></p>
                    <p class="stat-subtext"><?= htmlspecialchars($stat['meta']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="overview-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>Performance Overview</h2>
                <a href="?page=performance-report">View report</a>
            </div>

            <div class="overview-summary">
                <div class="info-card">
                    <span class="info-label">Overall Summary</span>
                    <span class="info-value"><?= htmlspecialchars($performanceSummary['overall_status'] ?: 'No data available') ?></span>
                    <span class="info-caption">Current workforce status</span>
                </div>
                <div class="info-card">
                    <span class="info-label">Average Rating</span>
                    <span class="info-value"><?= $performanceSummary['average_rating'] !== null ? number_format((float)$performanceSummary['average_rating'], 1) . '/100' : 'No data available' ?></span>
                    <span class="info-caption">Across active appraisals</span>
                </div>
                <div class="info-card">
                    <span class="info-label">Top Performer</span>
                    <span class="info-value"><?= htmlspecialchars($performanceSummary['top_employee'] ?: 'No data available') ?></span>
                    <span class="info-caption">Highest current rating</span>
                </div>
            </div>

            <div class="rating-breakdown" style="margin-top: 1.2rem;">
                <?php foreach ($ratingBreakdown as $label => $count): ?>
                    <?php $pct = ($count / $maxDistributionValue) * 100; ?>
                    <div class="rating-row">
                        <div class="label"><?= htmlspecialchars($label) ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $pct ?>%; background: <?= htmlspecialchars($distributionColors[$label] ?? '#64748b') ?>;"></div>
                        </div>
                        <div class="count"><?= $count ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Performance Rating Distribution</h2>
            </div>
            <div class="chart-wrap">
                <?php if (!empty(array_filter($performanceDistribution, fn($v) => $v > 0))): ?>
                    <canvas id="performanceDistributionChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">No data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>KPI Performance</h2>
        </div>
        <div class="kpi-grid">
            <div class="kpi-card">
                <p class="kpi-name">Total KPIs</p>
                <p class="kpi-value"><?= number_format((int)$kpiSummary['total_kpis']) ?></p>
                <div class="kpi-meta">Active KPI definitions</div>
            </div>
            <div class="kpi-card">
                <p class="kpi-name">Assigned KPIs</p>
                <p class="kpi-value"><?= number_format((int)$kpiSummary['assigned_kpis']) ?></p>
                <div class="kpi-meta">Current assignments</div>
            </div>
            <div class="kpi-card">
                <p class="kpi-name">Completed KPIs</p>
                <p class="kpi-value"><?= number_format((int)$kpiSummary['completed_kpis']) ?></p>
                <div class="kpi-meta">Achieved targets</div>
            </div>
            <div class="kpi-card">
                <p class="kpi-name">In Progress</p>
                <p class="kpi-value"><?= number_format((int)$kpiSummary['in_progress_kpis']) ?></p>
                <div class="kpi-meta">Ongoing evaluation</div>
            </div>
            <div class="kpi-card">
                <p class="kpi-name">At Risk</p>
                <p class="kpi-value"><?= number_format((int)$kpiSummary['at_risk_kpis']) ?></p>
                <div class="kpi-meta">Needs monitoring</div>
            </div>
        </div>
    </div>

    <div class="section-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>Appraisals & Reviews</h2>
                <a href="?page=appraisals-review">View All</a>
            </div>
            <?php if (!empty($appraisalRows)): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Appraisal Period</th>
                                <th>Status</th>
                                <th>Performance Rating</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appraisalRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['employee'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['appraisal_period'] ?? 'N/A') ?></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ', '-', $row['status'] ?? 'pending')) ?>"><?= htmlspecialchars($row['status'] ?? 'Pending') ?></span></td>
                                    <td><?= $row['performance_rating'] !== null ? number_format((float)$row['performance_rating'], 1) : 'N/A' ?></td>
                                    <td><?= !empty($row['appraisal_date']) ? htmlspecialchars(date('M d, Y', strtotime($row['appraisal_date']))) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= $emptyState ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Top Performers</h2>
            </div>
            <?php if (!empty($topPerformers)): ?>
                <div class="mini-list">
                    <?php foreach ($topPerformers as $employee): ?>
                        <div class="mini-item">
                            <div>
                                <div class="name"><?= htmlspecialchars($employee['employee_name'] ?? 'Unknown employee') ?></div>
                                <div class="meta"><?= htmlspecialchars($employee['department'] ?? 'N/A') ?></div>
                            </div>
                            <div class="score"><?= $employee['overall_rating'] !== null ? number_format((float)$employee['overall_rating'], 1) : 'N/A' ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?= $emptyState ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>360-Degree Feedback</h2>
                <a href="?page=360-degree-feedback">View All</a>
            </div>
            <div class="kpi-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 1rem;">
                <div class="kpi-card">
                    <p class="kpi-name">Pending</p>
                    <p class="kpi-value"><?= number_format((int)$feedbackSummary['pending']) ?></p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-name">Completed</p>
                    <p class="kpi-value"><?= number_format((int)$feedbackSummary['completed']) ?></p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-name">Active</p>
                    <p class="kpi-value"><?= number_format((int)$feedbackSummary['active']) ?></p>
                </div>
            </div>
            <?php if (!empty($feedbackRows)): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Review Period</th>
                                <th>Status</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbackRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['employee_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['review_period'] ?? 'N/A') ?></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ', '-', ($row['feedback_status'] ?? 'Pending'))) ?>"><?= htmlspecialchars($row['feedback_status'] ?? 'Pending') ?></span></td>
                                    <td><?= $row['overall_rating'] !== null ? number_format((float)$row['overall_rating'], 1) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= $emptyState ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Employees Requiring Attention</h2>
            </div>
            <?php if (!empty($attentionEmployees)): ?>
                <div class="mini-list">
                    <?php foreach (array_slice($attentionEmployees, 0, 5) as $employee): ?>
                        <div class="mini-item">
                            <div>
                                <div class="name"><?= htmlspecialchars($employee['employee'] ?? 'Unknown employee') ?></div>
                                <div class="meta"><?= htmlspecialchars($employee['reason'] ?? 'Needs attention') ?></div>
                            </div>
                            <div class="score"><?= $employee['rating'] !== null ? number_format((float)$employee['rating'], 1) : htmlspecialchars(strtoupper(substr($employee['status'] ?? 'N/A', 0, 3))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No employees requiring attention</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="trend-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>Performance Trends</h2>
            </div>
            <div class="chart-wrap">
                <?php if (!empty(array_filter($trendData['performance'], fn($v) => $v > 0)) || !empty(array_filter($trendData['kpis'], fn($v) => $v > 0)) || !empty(array_filter($trendData['appraisals'], fn($v) => $v > 0)) || !empty(array_filter($trendData['training'], fn($v) => $v > 0))): ?>
                    <canvas id="performanceTrendChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">No trend data available</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Training & Development</h2>
                <a href="?page=training-development">View All</a>
            </div>
            <div class="kpi-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 1rem;">
                <div class="kpi-card">
                    <p class="kpi-name">Total</p>
                    <p class="kpi-value"><?= number_format((int)$trainingSummary['total_recommendations']) ?></p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-name">Pending</p>
                    <p class="kpi-value"><?= number_format((int)$trainingSummary['pending_recommendations']) ?></p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-name">Approved</p>
                    <p class="kpi-value"><?= number_format((int)$trainingSummary['approved_recommendations']) ?></p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-name">High Priority</p>
                    <p class="kpi-value"><?= number_format((int)$trainingSummary['high_priority_training']) ?></p>
                </div>
            </div>
            <?php if (!empty($trainingRows)): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Gap</th>
                                <th>Training</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainingRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['employee'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['development_area'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['performance_gap'] ?? 'N/A') ?></td>
                                    <td><span class="badge <?= strtolower($row['priority_level'] ?? 'medium') ?>"><?= htmlspecialchars($row['priority_level'] ?? 'Medium') ?></span></td>
                                    <td><span class="badge <?= strtolower(str_replace(' ', '-', ($row['status'] ?? 'Pending'))) ?>"><?= htmlspecialchars($row['status'] ?? 'Pending') ?></span></td>
                                    <td><?= !empty($row['recommendation_date']) ? htmlspecialchars(date('M d, Y', strtotime($row['recommendation_date']))) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= $emptyState ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>Recent Performance Activities</h2>
            </div>
            <?php if (!empty($recentActivities)): ?>
                <div class="timeline">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <strong><?= htmlspecialchars($activity['activity'] ?? 'Activity') ?></strong>
                                <span><?= htmlspecialchars($activity['employee_user'] ?? 'System') ?> • <?= !empty($activity['activity_date']) ? htmlspecialchars(date('M d, Y h:i A', strtotime($activity['activity_date']))) : 'N/A' ?></span>
                                <span class="badge <?= strtolower(str_replace(' ', '-', ($activity['status'] ?? 'Updated'))) ?>"><?= htmlspecialchars($activity['status'] ?? 'Updated') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?= $emptyState ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const distributionCanvas = document.getElementById('performanceDistributionChart');
        if (distributionCanvas) {
            new Chart(distributionCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Outstanding', 'Exceeds Expectations', 'Meets Expectations', 'Needs Improvement', 'Unsatisfactory'],
                    datasets: [{
                        data: [
                            <?= (int) $performanceDistribution['Outstanding'] ?>,
                            <?= (int) $performanceDistribution['Exceeds Expectations'] ?>,
                            <?= (int) $performanceDistribution['Meets Expectations'] ?>,
                            <?= (int) $performanceDistribution['Needs Improvement'] ?>,
                            <?= (int) $performanceDistribution['Unsatisfactory'] ?>
                        ],
                        backgroundColor: ['#16a34a', '#22c55e', '#3b82f6', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const trendCanvas = document.getElementById('performanceTrendChart');
        if (trendCanvas) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trendData['labels']) ?>,
                    datasets: [
                        {
                            label: 'Performance',
                            data: <?= json_encode($trendData['performance']) ?>,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.12)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'KPI',
                            data: <?= json_encode($trendData['kpis']) ?>,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.12)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Appraisal',
                            data: <?= json_encode($trendData['appraisals']) ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Training',
                            data: <?= json_encode($trendData['training']) ?>,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.12)',
                            tension: 0.35,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: false }
                    }
                }
            });
        }
    });
</script>
