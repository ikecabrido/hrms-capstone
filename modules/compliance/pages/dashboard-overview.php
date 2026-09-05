<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Dashboard.php';
require_once __DIR__ . '/../classes/Employee.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$dashboard = new Dashboard($db);
$employeeClass = new Employee($db);

$pageTitle = 'Compliance Dashboard';

$totalEmployees = $dashboard->getTotalEmployees();
$healthScore = $dashboard->getComplianceHealthScore();
$riskCounts = $dashboard->getRiskCounts();
$openIncidents = $dashboard->getOpenIncidents();
$criticalOpen = $dashboard->getCriticalOpen();
$docStats = $dashboard->getDocumentStats();
$auditStats = $dashboard->getAuditStats();
$govCompliance = $dashboard->getGovernmentCompliance();
$deptCompliance = $dashboard->getDepartmentCompliance();
$incidentCategories = $dashboard->getIncidentCategories();
$employeeRisk = $dashboard->getEmployeeRiskRanking(10);
$recentActivities = $dashboard->getRecentActivities(8);
$actionRequired = $dashboard->getActionRequired(8);
$trendData = $dashboard->getMonthlyTrend();
$alerts = $dashboard->getAlerts();

?>
<script>
window.TREND_LABELS = <?= json_encode($trendData['months']) ?>;
window.TREND_VALUES = <?= json_encode($trendData['scores']) ?>;
window.RISK_DIST_LABELS = ['Critical', 'High', 'Medium', 'Low'];
window.RISK_DIST_VALUES = [<?= (int)($riskCounts['critical'] ?? 0) ?>, <?= (int)($riskCounts['high'] ?? 0) ?>, <?= (int)($riskCounts['medium'] ?? 0) ?>, <?= (int)($riskCounts['low'] ?? 0) ?>];
window.RISK_DIST_COLORS = ['rgba(214, 72, 74, 0.85)', 'rgba(201, 162, 74, 0.85)', 'rgba(217, 154, 43, 0.85)', 'rgba(47, 158, 110, 0.85)'];
</script>
<?php

$riskExposureScore = ($riskCounts['critical'] * 4) + ($riskCounts['high'] * 3) + ($riskCounts['medium'] * 2) + ($riskCounts['low'] * 1);
$overallRiskLabel = $riskExposureScore <= 5 ? 'Low Risk' : ($riskExposureScore <= 15 ? 'Medium Risk' : 'High Risk');

$govRate = 0;
$govVerified = 0;
$govTotal = 0;
foreach ($govCompliance as $g) {
    $govVerified += $g['verified'];
    $govTotal += $g['total'];
}
$govRate = $govTotal > 0 ? round(($govVerified / $govTotal) * 100) : 0;

$pendingExits = 0;
if ($db instanceof PDO) {
    try {
        $pendingExits = (int) $db->query("SELECT COUNT(*) FROM lc_exit_requests WHERE overall_status != 'Completed'")->fetchColumn();
    } catch (Exception $e) {}
}

$overdueItems = 0;
if ($db instanceof PDO) {
    try {
        $overdueItems = (int) $db->query("SELECT COUNT(*) FROM lc_compliance_items WHERE status = 'Overdue'")->fetchColumn();
    } catch (Exception $e) {}
}

$totalTrainings = 0;
$completedTrainings = 0;
if ($db instanceof PDO) {
    try {
        $totalTrainings = (int) $db->query("SELECT COUNT(*) FROM lc_trainings")->fetchColumn();
        $completedTrainings = (int) $db->query("SELECT COUNT(*) FROM lc_trainings WHERE status = 'Completed'")->fetchColumn();
    } catch (Exception $e) {}
}

$today = date('l, F j, Y');

$scoreAngle = $healthScore * 3.6;
$riskBadge = $healthScore >= 90 ? 'success' : ($healthScore >= 75 ? 'warning' : 'danger');
$riskLabel = $healthScore >= 90 ? 'Low Risk' : ($healthScore >= 75 ? 'Medium Risk' : 'High Risk');

$trendScoreColor = !empty($trendData['scores']) && end($trendData['scores']) >= 90 ? '#2f9e6e' : (!empty($trendData['scores']) && end($trendData['scores']) >= 75 ? '#d99a2b' : '#d6484a');

$quickLinks = [
    ['label' => 'Employee Documents', 'icon' => 'fa-folder-open', 'page' => 'employee-documents', 'color' => '#3b82c4'],
    ['label' => 'Employment Contracts', 'icon' => 'fa-file-contract', 'page' => 'employment-contracts', 'color' => '#8b5cf6'],
    ['label' => 'Labor Law Resources', 'icon' => 'fa-scale-balanced', 'page' => 'labor-compliance', 'color' => '#0d1b2e'],
    ['label' => 'Policy Management', 'icon' => 'fa-book', 'page' => 'policy-management', 'color' => '#2f9e6e'],
    ['label' => 'Incident Records', 'icon' => 'fa-clipboard-list', 'page' => 'incident-reports', 'color' => '#d6484a'],
    ['label' => 'Complaints', 'icon' => 'fa-comments', 'page' => 'case-records', 'color' => '#d99a2b'],
    ['label' => 'Risk Assessment', 'icon' => 'fa-shield-halved', 'page' => 'risk-register', 'color' => '#6366f1'],
    ['label' => 'Government Contributions', 'icon' => 'fa-building-columns', 'page' => 'government-registration', 'color' => '#059669'],
];
?>

<div class="module-content">
    <div class="dash">

        <!-- Row 1: Health Score + KPI Strip -->
        <div class="dash-row dash-row--horizontal dash-row--compact">
            <div class="dash-health-card">
                <div class="health-ring">
                    <svg viewBox="0 0 120 120" class="health-svg">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="<?= $healthScore >= 90 ? '#2f9e6e' : ($healthScore >= 75 ? '#d99a2b' : '#d6484a') ?>" stroke-width="8"
                            stroke-dasharray="<?= number_format($scoreAngle, 2) ?> <?= number_format(360 - $scoreAngle, 2) ?>"
                            stroke-dashoffset="0" stroke-linecap="round" transform="rotate(-90 60 60)"/>
                        <text x="60" y="56" text-anchor="middle" font-size="22" font-weight="700" fill="#1b2430" font-family="var(--font-display, 'Playfair Display', Georgia, serif)"><?= $healthScore ?>%</text>
                        <text x="60" y="72" text-anchor="middle" font-size="9" fill="#64748b" font-weight="600" letter-spacing="1">HEALTH</text>
                    </svg>
                </div>
                <div class="health-meta">
                    <div class="health-title">Overall Compliance Health</div>
                    <div class="health-badges">
                        <span class="badge badge-<?= $riskBadge ?>"><?= $riskLabel ?></span>
                        <span class="badge badge-info">Risk Score: <?= $riskExposureScore ?></span>
                    </div>
                    <div class="health-stats">
                        <div class="health-stat">
                            <span class="hs-label">Active Employees</span>
                            <span class="hs-value"><?= number_format($totalEmployees) ?></span>
                        </div>
                        <div class="health-stat">
                            <span class="hs-label">Pending Acknowledgements</span>
                            <?php
                                $pendingAcks = 0;
                                if ($db instanceof PDO) {
                                    try {
                                        $pendingStatement = $db->query("SELECT COUNT(*) FROM lc_policy_assignments WHERE status = 'Pending'");
                                        if ($pendingStatement !== false) {
                                            $pendingAcks = (int) $pendingStatement->fetchColumn();
                                        }
                                    } catch (Exception $e) {}
                                }
                            ?>
                            <span class="hs-value"><?= number_format($pendingAcks) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kpi-strip">
                <div class="kpi-box">
                    <div class="kpi-box-label">Total Employees</div>
                    <div class="kpi-box-value"><?= number_format($totalEmployees) ?></div>
                </div>
                <div class="kpi-box kpi-box--success">
                    <div class="kpi-box-label">Compliance Rate</div>
                    <div class="kpi-box-value"><?= $healthScore ?>%</div>
                </div>
                <div class="kpi-box kpi-box--danger">
                    <div class="kpi-box-label">Open Issues</div>
                    <div class="kpi-box-value"><?= number_format($openIncidents + $criticalOpen) ?></div>
                    <div class="kpi-box-sub"><?= number_format($criticalOpen) ?> critical</div>
                </div>
                <div class="kpi-box kpi-box--warning">
                    <div class="kpi-box-label">Expiring Documents</div>
                    <div class="kpi-box-value"><?= number_format($docStats['expiring30']) ?></div>
                    <div class="kpi-box-sub">Next 30 days</div>
                </div>
                <div class="kpi-box kpi-box--danger">
                    <div class="kpi-box-label">Expired Documents</div>
                    <div class="kpi-box-value"><?= number_format($docStats['expired']) ?></div>
                </div>
            </div>
        </div>

        <!-- Mobile Quick Links -->
        <div class="quick-links-mobile" aria-label="Quick navigation">
            <?php foreach (array_slice($quickLinks, 0, 8) as $link): ?>
            <a href="index.php?page=<?= urlencode($link['page']) ?>" class="quick-link-mobile-item" style="--ql-color: <?= htmlspecialchars($link['color']) ?>">
                <i class="fa-solid <?= htmlspecialchars($link['icon']) ?>"></i>
                <span><?= htmlspecialchars($link['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Row 2: Trend + Risk Distribution -->
        <div class="dash-section-header">
            <h3>Performance Trends</h3>
        </div>
        <div class="dash-row dash-row--analytics">
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Compliance Score Trend</h4>
                    <div class="chart-panel-meta">6-month moving average</div>
                </div>
                <div class="chart-panel-body">
                    <div class="sparkline-wrap"><canvas id="dashTrendChart"></canvas></div>
                    <div class="metric-row">
                        <div class="metric-item">
                            <div class="m-label">Peak</div>
                            <div class="m-value"><?= !empty($trendData['scores']) ? max($trendData['scores']) : '—' ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="m-label">Low</div>
                            <div class="m-value"><?= !empty($trendData['scores']) ? min($trendData['scores']) : '—' ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="m-label">Average</div>
                            <div class="m-value"><?= !empty($trendData['scores']) ? number_format(array_sum($trendData['scores']) / count($trendData['scores']), 1) : '—' ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="m-label">Latest</div>
                            <div class="m-value"><?= !empty($trendData['scores']) ? end($trendData['scores']) : '—' ?></div>
                            <div class="m-sub">Most recent</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Risk Distribution</h4>
                    <div class="chart-panel-meta"><?= array_sum($riskCounts) ?> active flags</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (array_sum($riskCounts) > 0): ?>
                    <div class="risk-pie-wrap">
                        <canvas id="riskPieChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-shield-halved"></i><div class="es-title">No active risk flags</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 3: Document Lifecycle + Department Performance -->
        <div class="dash-section-header">
            <h3>Document & Department Analytics</h3>
        </div>
        <div class="dash-row dash-row--analytics">
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Document Compliance Lifecycle</h4>
                    <div class="chart-panel-meta">Current status breakdown</div>
                </div>
                <div class="chart-panel-body">
                    <div class="lifecycle-grid">
                        <div class="lifecycle-item">
                            <div class="lifecycle-count"><?= number_format($docStats['total']) ?></div>
                            <div class="lifecycle-label">Total Documents</div>
                        </div>
                        <div class="lifecycle-item lifecycle-item--valid">
                            <div class="lifecycle-count"><?= number_format($docStats['valid']) ?></div>
                            <div class="lifecycle-label">Verified & Valid</div>
                        </div>
                        <div class="lifecycle-item lifecycle-item--expiring">
                            <div class="lifecycle-count"><?= number_format($docStats['expiring30']) ?></div>
                            <div class="lifecycle-label">Expiring (30d)</div>
                        </div>
                        <div class="lifecycle-item lifecycle-item--expired">
                            <div class="lifecycle-count"><?= number_format($docStats['expired']) ?></div>
                            <div class="lifecycle-label">Expired</div>
                        </div>
                        <div class="lifecycle-item">
                            <div class="lifecycle-count"><?= $docStats['rate'] ?>%</div>
                            <div class="lifecycle-label">Compliance Rate</div>
                        </div>
                    </div>
                    <div class="lifecycle-bars">
                        <?php if ($docStats['total'] > 0): 
                            $validPct = ($docStats['valid'] / $docStats['total']) * 100;
                            $expiringPct = ($docStats['expiring30'] / $docStats['total']) * 100;
                            $expiredPct = ($docStats['expired'] / $docStats['total']) * 100;
                            $otherPct = 100 - $validPct - $expiringPct - $expiredPct;
                        ?>
                        <div class="lifecycle-bar">
                            <div class="lifecycle-bar-fill lifecycle-bar-fill--valid" style="width:<?= number_format($validPct, 1) ?>%;"></div>
                            <div class="lifecycle-bar-fill lifecycle-bar-fill--expiring" style="width:<?= number_format($expiringPct, 1) ?>%;"></div>
                            <div class="lifecycle-bar-fill lifecycle-bar-fill--expired" style="width:<?= number_format($expiredPct, 1) ?>%;"></div>
                            <div class="lifecycle-bar-fill lifecycle-bar-fill--other" style="width:<?= number_format(max(0, $otherPct), 1) ?>%;"></div>
                        </div>
                        <div class="lifecycle-legend">
                            <span class="lifecycle-legend-item"><span class="lifecycle-dot" style="background:#2f9e6e;"></span> Verified</span>
                            <span class="lifecycle-legend-item"><span class="lifecycle-dot" style="background:#d99a2b;"></span> Expiring</span>
                            <span class="lifecycle-legend-item"><span class="lifecycle-dot" style="background:#d6484a;"></span> Expired</span>
                            <span class="lifecycle-legend-item"><span class="lifecycle-dot" style="background:#94a3b8;"></span> Other</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Department Compliance Ranking</h4>
                    <div class="chart-panel-meta">By average score</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($deptCompliance)): 
                        $maxDeptScore = 0;
                        foreach ($deptCompliance as $d) { if ((int)$d['score'] > $maxDeptScore) $maxDeptScore = (int)$d['score']; }
                        if ($maxDeptScore <= 0) $maxDeptScore = 1;
                        $colors_dept = ['#2f9e6e','#3b82c4','#6366f1','#d99a2b','#d6484a'];
                        $ci = 0;
                    ?>
                    <div class="dept-ranks">
                        <?php foreach ($deptCompliance as $d): 
                            $pct = ($d['score'] / $maxDeptScore) * 100;
                            $color = $colors_dept[$ci % count($colors_dept)];
                        ?>
                        <div class="dept-rank-row">
                            <div class="dept-rank-name"><?= htmlspecialchars($d['department']) ?></div>
                            <div class="dept-rank-track"><div class="dept-rank-fill" style="width:<?= number_format($pct, 1) ?>%; background:<?= $color ?>;"></div></div>
                            <div class="dept-rank-score"><?= $d['score'] ?>%</div>
                        </div>
                        <?php $ci++; endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-building"></i><div class="es-title">No department compliance data</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 4: Government + Incidents -->
        <div class="dash-section-header">
            <h3>Regulatory & Incident Overview</h3>
        </div>
        <div class="dash-row dash-row--analytics">
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Government Contribution Compliance</h4>
                    <div class="chart-panel-meta">Agency submission status</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($govCompliance)): 
                        $maxGovPct = 0;
                        foreach ($govCompliance as $g) { if ((int)$g['pct'] > $maxGovPct) $maxGovPct = (int)$g['pct']; }
                        if ($maxGovPct <= 0) $maxGovPct = 100;
                    ?>
                    <div class="gov-grid">
                        <?php foreach ($govCompliance as $gov): 
                            $compliancePct = ($gov['pct'] / $maxGovPct) * 100;
                            $gapPct = 100 - $compliancePct;
                        ?>
                        <div class="gov-item">
                            <div class="gov-item-header">
                                <span class="gov-item-name"><?= htmlspecialchars($gov['name']) ?></span>
                                <span class="gov-item-pct"><?= $gov['pct'] ?>%</span>
                            </div>
                            <div class="gov-item-bar">
                                <div class="gov-item-fill gov-item-fill--verified" style="width:<?= number_format($compliancePct, 1) ?>%;"></div>
                                <div class="gov-item-fill gov-item-fill--gap" style="width:<?= number_format($gapPct, 1) ?>%;"></div>
                            </div>
                            <div class="gov-item-meta"><?= number_format($gov['verified']) ?> / <?= number_format($gov['total']) ?> submitted</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-building"></i><div class="es-title">No government compliance records yet</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Incident Categories</h4>
                    <div class="chart-panel-meta">Open incidents</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($incidentCategories)): 
                        $maxCatCnt = 0;
                        foreach ($incidentCategories as $c) { if ((int)$c['cnt'] > $maxCatCnt) $maxCatCnt = (int)$c['cnt']; }
                        if ($maxCatCnt <= 0) $maxCatCnt = 1;
                    ?>
                    <div class="incident-categories">
                        <?php foreach ($incidentCategories as $cat): 
                            $catPct = ($cat['cnt'] / $maxCatCnt) * 100;
                        ?>
                        <div class="incident-cat-row">
                            <div class="incident-cat-label"><?= htmlspecialchars($cat['incident_type']) ?></div>
                            <div class="incident-cat-track">
                                <div class="incident-cat-fill" style="width:<?= number_format($catPct, 1) ?>%;"></div>
                            </div>
                            <div class="incident-cat-value"><?= $cat['cnt'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-clipboard-list"></i><div class="es-title">No open incidents</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Row 5: Action Center + Alerts -->
        <div class="dash-section-header">
            <h3>Action Center</h3>
        </div>
        <div class="dash-row dash-row--analytics">
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Priority Actions</h4>
                    <div class="chart-panel-meta"><?= count($actionRequired) ?> items requiring attention</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($actionRequired)): ?>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Responsible</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actionRequired as $item): 
                                    $label = $item['item_type'] ?? 'Action';
                                    $name = $item['policy_title'] ?? $item['name'] ?? $item['document_name'] ?? '—';
                                    $person = $item['person_name'] ?? 'Unassigned';
                                    $due = $item['due_date'] ?? $item['expiry_date'] ?? null;
                                    $status = $item['status'] ?? 'Pending';
                                    $severity = strtolower($item['severity'] ?? 'warning');
                                    $severityClass = $severity === 'danger' ? 'table-row--danger' : ($severity === 'warning' ? 'table-row--warning' : '');
                                ?>
                                <tr class="<?= $severityClass ?>">
                                    <td class="td-primary" data-label="Item"><?= htmlspecialchars($name) ?></td>
                                    <td data-label="Type"><?= htmlspecialchars($label) ?></td>
                                    <td data-label="Responsible"><?= htmlspecialchars($person) ?></td>
                                    <td class="td-mono" data-label="Due Date"><?= $due ? htmlspecialchars(date('M d, Y', strtotime($due))) : '—' ?></td>
                                    <td data-label="Status"><span class="badge badge-<?= htmlspecialchars($severity) ?>"><?= htmlspecialchars($status) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-check-circle"></i><div class="es-title">No pending actions</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>System Alerts</h4>
                    <div class="chart-panel-meta"><?= count($alerts) ?> active</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($alerts)): ?>
                        <div class="alert-list">
                            <?php foreach ($alerts as $alert): ?>
                                <div class="alert-item alert-item--<?= strtolower($alert['priority']) ?>">
                                    <div class="alert-icon"><i class="fa-solid <?= htmlspecialchars($alert['icon']) ?>"></i></div>
                                    <div class="alert-text"><?= htmlspecialchars($alert['message']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-check-circle"></i><div class="es-title">No active alerts</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
