<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Dashboard.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$dashboard = new Dashboard($db);
$employeeClass = new Employee($db);

$pageTitle = 'Dashboard Overview';

$totalEmployees = $dashboard->getTotalEmployees();
$trendValues = [85, 87, 88, 89, 92, 91];
$healthScore = !empty($trendValues)
    ? (int) round(array_sum($trendValues) / count($trendValues))
    : 0;
$riskCounts = $dashboard->getRiskCounts();
$openIncidents = $dashboard->getOpenIncidents();
$criticalOpen = $dashboard->getCriticalOpen();
$closedCases = 0;
if ($db instanceof PDO) {
    $closedCases = (int) $db->query("SELECT COUNT(*) FROM lc_incident_report WHERE status = 'closed' AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')")->fetchColumn();
}
$docStats = $dashboard->getDocumentStats();
$auditStats = $dashboard->getAuditStats();
$govCompliance = $dashboard->getGovernmentCompliance();
$deptCompliance = $dashboard->getDepartmentCompliance();
$incidentCategories = $dashboard->getIncidentCategories();
$incidentOccurrence = $dashboard->getIncidentOccurrenceData();
$incidentSeverity = $dashboard->getIncidentSeveritySummary();
$incidentAging = $dashboard->getIncidentAgingSummary();
$employeeRisk = $dashboard->getEmployeeRiskRanking(10);
$recentActivities = $dashboard->getRecentActivities(8);
$actionAnalytics = $dashboard->getActionAnalytics(8);
$trendData = $dashboard->getMonthlyTrend();
$alerts = $dashboard->getAlerts();

$incidentYear = isset($_GET['incident_year']) ? (int) $_GET['incident_year'] : (int) date('Y');
$incidentMonth = isset($_GET['incident_month']) ? (int) $_GET['incident_month'] : (int) date('m');
if ($incidentOccurrence && ($incidentOccurrence['year'] ?? date('Y')) != $incidentYear || ($incidentOccurrence['month'] ?? date('m')) != $incidentMonth) {
    $incidentOccurrence = $dashboard->getIncidentOccurrenceData($incidentYear, $incidentMonth);
}

$lcTotalCases = 0;
$lcClosedCases = 0;
$lcHighPriority = 0;
$lcStatusBreakdown = [];
$lcAgencyBreakdown = [];
$lcRecentCases = [];
$lcOpenCases = 0;
$lcExternalCases = 0;
$lcRequiringAction = 0;
$lcMaxStatusCount = 1;
$lcResolutionRate = 0;
$lcPending = 0;
$lcOngoing = 0;
$lcResolved = 0;
$lcDismissed = 0;

if (class_exists('LegalCaseManager') && $db instanceof PDO) {
    try {
        $lcManager = new LegalCaseManager($db);
        $lcTotalCases = $lcManager->getTotalCases();
        $lcClosedCases = $lcManager->getClosedCases();
        $lcHighPriority = $lcManager->getHighPriorityCases();
        $lcStatusBreakdown = $lcManager->getStatusBreakdown();
        $lcAgencyBreakdown = $lcManager->getAgencyBreakdown();
        $lcRecentCases = $lcManager->getRecentCases(5);
        $lcOpenCases = $lcTotalCases - $lcClosedCases;
        $lcExternalCases = (int) $db->query("SELECT COUNT(*) FROM lc_legal_cases WHERE external_agency IS NOT NULL AND external_agency <> ''")->fetchColumn();
        $lcRequiringAction = (int) $db->query("SELECT COUNT(*) FROM lc_legal_cases WHERE due_date <= NOW() AND current_status NOT IN ('Resolved','Closed','Cancelled')")->fetchColumn();
        foreach ($lcStatusBreakdown as $s) {
            $cnt = (int)($s['cnt'] ?? 0);
            if ($cnt > $lcMaxStatusCount) $lcMaxStatusCount = $cnt;
        }

        $lcResolutionRate = $lcTotalCases > 0 ? (int) round(($lcClosedCases / $lcTotalCases) * 100) : 0;

        $lcPending = 0;
        $lcOngoing = 0;
        $lcResolved = 0;
        $lcDismissed = 0;

        foreach ($lcStatusBreakdown as $s) {
            $status = strtolower((string)($s['current_status'] ?? ''));
            $cnt = (int)($s['cnt'] ?? 0);
            if (in_array($status, ['resolved', 'closed'])) {
                $lcResolved += $cnt;
            } elseif ($status === 'cancelled') {
                $lcDismissed += $cnt;
            } elseif (in_array($status, ['open', 'draft', 'under assessment', 'under investigation', 'awaiting documents', 'awaiting external action', 'compliance action required'])) {
                $lcPending += $cnt;
            } else {
                $lcOngoing += $cnt;
            }
        }
    } catch (Throwable $e) {
        $lcTotalCases = 0;
        $lcClosedCases = 0;
        $lcHighPriority = 0;
        $lcStatusBreakdown = [];
        $lcAgencyBreakdown = [];
        $lcRecentCases = [];
        $lcOpenCases = 0;
        $lcExternalCases = 0;
        $lcRequiringAction = 0;
        $lcResolutionRate = 0;
        $lcPending = 0;
        $lcOngoing = 0;
        $lcResolved = 0;
        $lcDismissed = 0;
    }
}

?>
<script>
window.TREND_LABELS = <?= json_encode($trendData['months']) ?>;
window.TREND_VALUES = <?= json_encode($trendData['scores']) ?>;
window.RISK_DIST_LABELS = ['Critical', 'High', 'Medium', 'Low'];
window.RISK_DIST_VALUES = [<?= (int)($riskCounts['critical'] ?? 0) ?>, <?= (int)($riskCounts['high'] ?? 0) ?>, <?= (int)($riskCounts['medium'] ?? 0) ?>, <?= (int)($riskCounts['low'] ?? 0) ?>];
window.RISK_DIST_COLORS = ['rgba(30, 64, 175, 0.85)', 'rgba(37, 99, 235, 0.85)', 'rgba(59, 130, 196, 0.85)', 'rgba(147, 197, 253, 0.85)'];
</script>
<?php

$riskExposureScore = ($riskCounts['critical'] * 4) + ($riskCounts['high'] * 3) + ($riskCounts['medium'] * 2) + ($riskCounts['low'] * 1);
$overallRiskLabel = $riskExposureScore <= 5 ? 'Low Risk' : ($riskExposureScore <= 15 ? 'Medium Risk' : 'High Risk');

$pendingExits = 0;
if ($db instanceof PDO) {
    try {
        $pendingExits = (int) $db->query("SELECT COUNT(*) FROM exit_resignations WHERE status NOT IN ('Completed', 'Cancelled')")->fetchColumn();
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
$riskBadge = $healthScore >= 90 ? 'info' : ($healthScore >= 75 ? 'info' : 'danger');
$riskLabel = $healthScore >= 90 ? 'Low Risk' : ($healthScore >= 75 ? 'Medium Risk' : 'High Risk');

$trendScoreColor = !empty($trendData['scores']) && end($trendData['scores']) >= 90 ? '#2563eb' : (!empty($trendData['scores']) && end($trendData['scores']) >= 75 ? '#60a5fa' : '#1e40af');

$quickLinks = [
    ['label' => 'Employee Documents', 'icon' => 'fa-folder-open', 'page' => 'employee-documents', 'color' => '#3b82c6'],
    ['label' => 'Employment Contracts', 'icon' => 'fa-file-contract', 'page' => 'employment-contracts', 'color' => '#2563eb'],
    ['label' => 'Labor Law Resources', 'icon' => 'fa-scale-balanced', 'page' => 'labor-compliance', 'color' => '#1e40af'],
    ['label' => 'Policy Management', 'icon' => 'fa-book', 'page' => 'policy-management', 'color' => '#60a5fa'],
    ['label' => 'Incident Records', 'icon' => 'fa-clipboard-list', 'page' => 'incident-reports', 'color' => '#1e3a8a'],
    ['label' => 'Complaints', 'icon' => 'fa-comments', 'page' => 'case-records', 'color' => '#93c5fd'],
    ['label' => 'Risk Assessment', 'icon' => 'fa-shield-halved', 'page' => 'risk-register', 'color' => '#1d4ed8'],
    ['label' => 'Government Contributions', 'icon' => 'fa-building-columns', 'page' => 'government-registration', 'color' => '#2563eb'],
];

function lc_report_status_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['closed', 'resolved'], true)) return 'compliant';
    if (in_array($s, ['under assessment', 'under investigation', 'in conference', 'conference scheduled'], true)) return 'info';
    return 'pending';
}

?>

<div class="module-content">
    <div class="dash">

        <!-- Row 1: Health Score + KPI Strip -->
        <div class="dash-row dash-row--horizontal dash-row--compact">
            <div class="dash-health-card">
                <div class="health-ring">
                    <svg viewBox="0 0 120 120" class="health-svg">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="<?= $healthScore >= 90 ? '#2563eb' : ($healthScore >= 75 ? '#60a5fa' : '#1e40af') ?>" stroke-width="8"
                            stroke-dasharray="<?= number_format($scoreAngle, 2) ?> <?= number_format(360 - $scoreAngle, 2) ?>"
                            stroke-dashoffset="0" stroke-linecap="round" transform="rotate(-90 60 60)"/>
                        <text x="60" y="56" text-anchor="middle" font-size="22" font-weight="700" fill="#1b2430"><?= $healthScore ?>%</text>
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
                <a href="/modules/compliance/lib/api/preview_report.php?key=employee_master_list&export=export_report" class="kpi-box-link">
                    <div class="kpi-box">
                        <div class="kpi-box-label">Total Employees</div>
                        <div class="kpi-box-value"><?= number_format($totalEmployees) ?></div>
                    </div>
                </a>
                <div class="kpi-box kpi-box--success">
                    <div class="kpi-box-label">Compliance Rate</div>
                    <div class="kpi-box-value"><?= $healthScore ?>%</div>
                </div>
                <a href="?page=incident-reports" class="kpi-box-link">
                    <div class="kpi-box kpi-box--danger">
                        <div class="kpi-box-label">Open Issues</div>
                        <div class="kpi-box-value"><?= number_format($openIncidents + $criticalOpen) ?></div>
                        <div class="kpi-box-sub"><?= number_format($criticalOpen) ?> critical</div>
                    </div>
                </a>
                <a href="?page=employee-documents&amp;status=Expiring+Soon" class="kpi-box-link">
                    <div class="kpi-box kpi-box--warning">
                        <div class="kpi-box-label">Expiring Documents</div>
                        <div class="kpi-box-value"><?= number_format($docStats['expiring30']) ?></div>
                        <div class="kpi-box-sub">Next 30 days</div>
                    </div>
                </a>
                <a href="?page=employee-documents&amp;status=Expired" class="kpi-box-link">
                    <div class="kpi-box kpi-box--danger">
                        <div class="kpi-box-label">Expired Documents</div>
                        <div class="kpi-box-value"><?= number_format($docStats['expired']) ?></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Legal Case Quick Stats -->
        <?php
        $lcStats = [];
        if (class_exists('LegalCaseManager')) {
            try {
                $lcStats = (new LegalCaseManager($db))->getLegalCaseStats();
            } catch (Throwable $e) {
                $lcStats = ['open'=>0,'external'=>0,'action'=>0,'overdue'=>0,'monitoring'=>0,'conferences'=>0];
            }
        }
        ?>
      
         

        <!-- Mobile Quick Links -->
        

     
      
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
            <a href="?page=risk-register" class="chart-panel-link">
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
            </a>
        </div>

        
        <div class="dash-row dash-row--agency-directory">
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Legal Cases Summary</h4>
                    <div class="chart-panel-meta">Status distribution</div>
                </div>
                <div class="chart-panel-body">
                    <div class="legal-chart-wrap">
                        <canvas id="legalCasesChart"></canvas>
                    </div>
                    <script>
                        window.LEGAL_CASES_LABELS = <?= json_encode(array_column($lcStatusBreakdown, 'current_status')) ?>;
                        window.LEGAL_CASES_VALUES = <?= json_encode(array_map(function($s){ return (int)($s['cnt'] ?? 0); }, $lcStatusBreakdown)) ?>;
                    </script>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Government Agency Directory</h4>
                    <div class="chart-panel-meta">Philippine government agencies</div>
                </div>
                <div class="chart-panel-body">
                    <?php
                    $agencyDirectory = [
                        [
                            'title' => 'Education Regulators & School Systems',
                            'dot' => 'lc-status-dot--education',
                            'color' => '#2563eb',
                            'agencies' => [
                                [
                                    'name' => 'DepEd',
                                    'full_name' => 'Department of Education',
                                    'description' => 'Primary government agency responsible for governance and development of Philippine basic education, curriculum, and teacher quality.',
                                    'phone' => '(02) 8633-7200',
                                    'email' => 'query@deped.gov.ph',
                                    'website' => 'www.deped.gov.ph'
                                ],
                                [
                                    'name' => 'CHED',
                                    'full_name' => 'Commission on Higher Education',
                                    'description' => 'Governs higher education institutions and ensures quality standards for tertiary and graduate programs.',
                                    'phone' => '(02) 8234-4389',
                                    'email' => 'information@ched.gov.ph',
                                    'website' => 'www.ched.gov.ph'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Labor & Industrial Relations',
                            'dot' => 'lc-status-dot--labor',
                            'color' => '#d97706',
                            'agencies' => [
                                [
                                    'name' => 'DOLE',
                                    'full_name' => 'Department of Labor and Employment',
                                    'description' => 'Oversees labor markets, workers\' welfare, employment promotion, and enforcement of labor standards.',
                                    'phone' => '(02) 8527-8000',
                                    'email' => 'dole@dole.gov.ph',
                                    'website' => 'www.dole.gov.ph'
                                ],
                                [
                                    'name' => 'NLRC',
                                    'full_name' => 'National Labor Relations Commission',
                                    'description' => 'Adjudicates labor disputes, unfair labor practice cases, and implements labor arbitration.',
                                    'phone' => '(02) 8561-2210',
                                    'email' => 'nlrc@nlrc.dole.gov.ph',
                                    'website' => 'www.nlrc.dole.gov.ph'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Mandatory Employee Premium Benefits',
                            'dot' => 'lc-status-dot--benefits',
                            'color' => '#10b981',
                            'agencies' => [
                                [
                                    'name' => 'SSS',
                                    'full_name' => 'Social Security System',
                                    'description' => 'Administers social security benefits, including retirement, sickness, and death benefits for private sector employees.',
                                    'phone' => '(02) 8920-6446',
                                    'email' => 'member_relations@sss.gov.ph',
                                    'website' => 'www.sss.gov.ph'
                                ],
                                [
                                    'name' => 'PhilHealth',
                                    'full_name' => 'Philippine Health Insurance Corporation',
                                    'description' => 'Provides universal health coverage through premium-based insurance for all Filipinos.',
                                    'phone' => '(02) 8633-7438',
                                    'email' => 'action@philhealth.gov.ph',
                                    'website' => 'www.philhealth.gov.ph'
                                ],
                                [
                                    'name' => 'Pag-IBIG',
                                    'full_name' => 'Home Development Mutual Fund',
                                    'description' => 'Manages housing finance and provident savings, offering affordable financing and membership benefits.',
                                    'phone' => '(02) 8811-8080',
                                    'email' => 'wecare@pagibigfund.gov.ph',
                                    'website' => 'www.pagibigfund.gov.ph'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Professional Licenses & Taxation',
                            'dot' => 'lc-status-dot--licenses',
                            'color' => '#7c3aed',
                            'agencies' => [
                                [
                                    'name' => 'PRC',
                                    'full_name' => 'Professional Regulation Commission',
                                    'description' => 'Regulates and licenses professionals, oversees board examinations, and enforces professional standards.',
                                    'phone' => '(02) 8523-8486',
                                    'email' => 'info@prc.gov.ph',
                                    'website' => 'www.prc.gov.ph'
                                ],
                                [
                                    'name' => 'BIR',
                                    'full_name' => 'Bureau of Internal Revenue',
                                    'description' => 'Collects taxes, enforces tax laws, and administers tax policy and compliance nationwide.',
                                    'phone' => '(02) 8701-1000',
                                    'email' => 'bir_inquiry@bir.gov.ph',
                                    'website' => 'www.bir.gov.ph'
                                ]
                            ]
                        ]
                    ];
                    ?>
                    <div class="agency-directory">
                        <?php foreach ($agencyDirectory as $category): ?>
                            <div class="agency-category">
                                <div class="agency-list">
                                    <?php foreach ($category['agencies'] as $agency): ?>
                                        <div class="agency-row">
                                            <div class="agency-row-primary">
                                                <span class="agency-name"><?= htmlspecialchars($agency['name']) ?></span>
                                                <span class="agency-full-name"><?= htmlspecialchars($agency['full_name']) ?></span>
                                            </div>
                                            <div class="agency-row-body">
                                                <div class="agency-description"><?= htmlspecialchars($agency['description']) ?></div>
                                                <div class="agency-contacts">
                                                    <span class="agency-contact"><i class="bi bi-telephone"></i> <?= htmlspecialchars($agency['phone']) ?></span>
                                                    <a href="index.php?page=notification-compose&amp;mode=reply&amp;notification_id=0&amp;email=<?= rawurlencode($agency['email']) ?>" class="agency-contact agency-email" style="color:inherit;text-decoration:none;cursor:pointer"><i class="bi bi-envelope"></i> <?= htmlspecialchars($agency['email']) ?></a>
                                                    <span class="agency-contact"><i class="bi bi-globe"></i> <?= htmlspecialchars($agency['website']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="dash-row dash-row--analytics">
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Document Compliance Lifecycle</h4>
                    <div class="chart-panel-meta">Current status breakdown</div>
                </div>
                <div class="chart-panel-body">
                    <?php
                        $docTotal = (int)($docStats['total'] ?? 0);
                        $docValid = (int)($docStats['valid'] ?? 0);
                        $docExpiring = (int)($docStats['expiring30'] ?? 0);
                        $docExpired = (int)($docStats['expired'] ?? 0);
                        $docRate = isset($docStats['rate']) ? (float) $docStats['rate'] : 0;
                        $attentionRequired = $docExpiring + $docExpired;

                        $validPct = $docTotal > 0 ? round(($docValid / $docTotal) * 100, 1) : 0;
                        $expiringPct = $docTotal > 0 ? round(($docExpiring / $docTotal) * 100, 1) : 0;
                        $expiredPct = $docTotal > 0 ? round(($docExpired / $docTotal) * 100, 1) : 0;
                    ?>
                    <div class="document-health">
                        <div class="document-health-kpis">
                            <a href="?page=employee-documents" class="dh-kpi-link">
                                <div class="dh-kpi">
                                    <div class="dh-kpi-value"><?= number_format($docTotal) ?></div>
                                    <div class="dh-kpi-label">Total Documents</div>
                                </div>
                            </a>
                            <a href="?page=employee-documents&status=Verified" class="dh-kpi-link">
                                <div class="dh-kpi dh-kpi--valid">
                                    <div class="dh-kpi-value"><?= number_format($docValid) ?></div>
                                    <div class="dh-kpi-label">Valid</div>
                                    <div class="dh-kpi-sub"><?= $validPct ?>% of portfolio</div>
                                </div>
                            </a>
                            <a href="?page=employee-documents&status=Expiring+Soon" class="dh-kpi-link">
                                <div class="dh-kpi dh-kpi--expiring">
                                    <div class="dh-kpi-value"><?= number_format($docExpiring) ?></div>
                                    <div class="dh-kpi-label">Expiring Soon</div>
                                    <div class="dh-kpi-sub"><?= $expiringPct ?>% — within 30 days</div>
                                </div>
                            </a>
                            <a href="?page=employee-documents&status=Expired" class="dh-kpi-link">
                                <div class="dh-kpi dh-kpi--expired">
                                    <div class="dh-kpi-value"><?= number_format($docExpired) ?></div>
                                    <div class="dh-kpi-label">Expired</div>
                                    <div class="dh-kpi-sub"><?= $expiredPct ?>% — immediate attention</div>
                                </div>
                            </a>
                            <a href="?page=employee-documents" class="dh-kpi-link">
                                <div class="dh-kpi dh-kpi--attention">
                                    <div class="dh-kpi-value"><?= number_format($attentionRequired) ?></div>
                                    <div class="dh-kpi-label">Attention Required</div>
                                    <div class="dh-kpi-sub"><?= number_format($docExpiring) ?> expiring + <?= number_format($docExpired) ?> expired</div>
                                </div>
                            </a>
                        </div>

                        <div class="document-health-distribution">
                            <div class="dh-distribution-bar" data-total="<?= $docTotal ?>">
                                <?php if ($docTotal > 0): ?>
                                <div class="dh-distribution-fill dh-distribution-fill--valid" style="width:<?= number_format(($docValid / $docTotal) * 100, 2) ?>%;" data-status="Valid" data-count="<?= $docValid ?>" data-pct="<?= $validPct ?>"></div>
                                <div class="dh-distribution-fill dh-distribution-fill--expiring" style="width:<?= number_format(($docExpiring / $docTotal) * 100, 2) ?>%;" data-status="Expiring Soon" data-count="<?= $docExpiring ?>" data-pct="<?= $expiringPct ?>"></div>
                                <div class="dh-distribution-fill dh-distribution-fill--expired" style="width:<?= number_format(($docExpired / $docTotal) * 100, 2) ?>%;" data-status="Expired" data-count="<?= $docExpired ?>" data-pct="<?= $expiredPct ?>"></div>
                                <?php endif; ?>
                            </div>
                            <div class="dh-distribution-legend">
                                <span class="dh-legend-item"><span class="dh-legend-dot" style="background:#2563eb;"></span> Valid</span>
                                <span class="dh-legend-item"><span class="dh-legend-dot" style="background:#60a5fa;"></span> Expiring Soon</span>
                                <span class="dh-legend-item"><span class="dh-legend-dot" style="background:#1e40af;"></span> Expired</span>
                            </div>
                        </div>

                        <div class="document-health-insight">
                            <?php if ($docTotal === 0): ?>
                                No documents have been added yet.
                            <?php elseif ($attentionRequired === 0): ?>
                                Most documents are currently valid and no immediate action is required.
                            <?php elseif ($docExpired > 0 && $docExpiring > 0): ?>
                                Most documents are valid, but <?= number_format($attentionRequired) ?> documents require attention.
                            <?php elseif ($docExpired > 0): ?>
                                Expired documents represent <?= $expiredPct ?>% of the portfolio and require immediate review.
                            <?php elseif ($docExpiring > 0): ?>
                                Compliance is stable, but <?= number_format($docExpiring) ?> documents will require renewal within the next 30 days.
                            <?php else: ?>
                                Most documents are currently valid. <?= number_format($attentionRequired) ?> documents require attention.
                            <?php endif; ?>
                        </div>

                        <?php
                            $recentRequests = [];
                            $pendingRequestCount = 0;
                            if ($db instanceof PDO) {
                                $tableName = 'lc_document_requests';
                                $checkTable = $db->query("SHOW TABLES LIKE 'em_lc_document_requests'");
                                if ($checkTable && $checkTable->rowCount() > 0) {
                                    $tableName = 'em_lc_document_requests';
                                }
                                
                                try {
                                    $pendingRequestCount = (int) $db->query("SELECT COUNT(*) FROM {$tableName} WHERE LOWER(request_status) = 'pending'")->fetchColumn();
                                } catch (Exception $e) {
                                    error_log('Dashboard doc request count error: ' . $e->getMessage());
                                }
                                try {
                                    $stmt = $db->prepare("
                                        SELECT 
                                            dr.request_id,
                                            dr.document_type,
                                            dr.request_status,
                                            dr.created_at,
                                            dr.priority,
                                            e.employee_id,
                                            e.employee_code,
                                            CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                                            d.department_name,
                                            p.position_name
                                          FROM {$tableName} dr
                                          LEFT JOIN em_employees e ON dr.employee_id = e.employee_id
                                          LEFT JOIN em_departments d ON e.department_id = d.department_id
                                          LEFT JOIN em_positions p ON e.position_id = p.position_id
                                          WHERE LOWER(dr.request_status) = 'pending'
                                          ORDER BY dr.created_at DESC
                                          LIMIT 2
                                    ");
                                    $stmt->execute();
                                    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (Exception $e) {
                                    error_log('Dashboard doc request list error: ' . $e->getMessage());
                                }
                            }

                            $statusBadgeMap = [
                                'pending' => 'dr-badge--pending',
                                'processing' => 'dr-badge--processing',
                                'generated' => 'dr-badge--generated',
                                'released' => 'dr-badge--released',
                                'completed' => 'dr-badge--completed',
                                'rejected' => 'dr-badge--rejected',
                                'approved' => 'dr-badge--completed',
                            ];
                        ?>
                        <div class="document-requests-section">
                            <div class="dr-inner">
                                <div class="dr-header">
                                    <div>
                                        <div class="dr-title">Document Requests</div>
                                        <div class="dr-subtitle">Recent document requests submitted by employees</div>
                                    </div>
                                </div>
                                <?php if (!empty($recentRequests)): ?>
                                <div class="dr-list">
                                <?php foreach ($recentRequests as $req): 
                                    $status = strtolower((string)($req['request_status'] ?? 'pending'));
                                    $badgeClass = $statusBadgeMap[$status] ?? 'dr-badge--neutral';
                                    $createdAt = $req['created_at'] ?? '';
                                    $requestedDate = $createdAt ? date('M d, Y', strtotime($createdAt)) : '—';
                                    $docType = $req['document_type'] ?? 'Document';
                                    if (strlen($docType) > 40) {
                                        $docType = substr($docType, 0, 38) . '…';
                                    }
                                ?>
                                <div class="dr-item">
                                    <div class="dr-employee">
                                        <div class="dr-employee-name"><?= htmlspecialchars($req['full_name'] ?? 'Unknown') ?></div>
                                        <div class="dr-employee-meta"><?= htmlspecialchars($req['employee_code'] ?? '') ?><?php if (!empty($req['department_name'])): ?> · <?= htmlspecialchars($req['department_name']) ?><?php endif; ?></div>
                                    </div>
                                    <div class="dr-document"><?= htmlspecialchars($docType) ?></div>
                                    <div class="dr-date"><?= $requestedDate ?></div>
                                    <div class="dr-status"><span class="dr-badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($req['request_status'] ?? 'Pending')) ?></span></div>
                                    <div class="dr-action">
                                        <?php
                                            $employeeId = (int)($req['employee_id'] ?? 0);
                                            $docTypeValue = $req['document_type'] ?? 'Document';
                                            $docTypeLabelToCode = [
                                                'Certificate of Employment (COE)' => 'coe',
                                                'Exit Acknowledgement' => 'exit_acknowledgement',
                                                'Leave Agreement' => 'leave_agreement',
                                                'Return-to-Work Agreement' => 'return_service',
                                                'Non-Disclosure Agreement (NDA)' => 'nda',
                                                'Training Bond' => 'training_bond',
                                                'Study Leave Agreement' => 'study_leave',
                                                'Non-Compete Agreement' => 'non_compete',
                                                'Notice to Explain (NTE)' => 'nte',
                                                'Written Warning' => 'written_warning',
                                                'Suspension Notice' => 'suspension_notice',
                                                'Employee Handbook' => 'employee_handbook',
                                                'Notice of Decision' => 'notice_of_decision',
                                                'Termination Decision' => 'termination_decision',
                                                'Exit Clearance' => 'exit_clearance',
                                                'Clearance Survey' => 'clearance_survey',
                                            ];
                                            $templateCode = $docTypeLabelToCode[$docTypeValue] ?? strtolower(str_replace([' ', '-', '(', ')', '/'], '_', $docTypeValue));
                                            $templateFile = $templateCode . '.php';
                                        ?>
                                        <a href="?page=preview-document&request_id=<?= urlencode((string)($req['request_id'] ?? '')) ?>&employee_id=<?= $employeeId ?>&document_type=<?= urlencode($docTypeValue) ?>&template=<?= urlencode($templateFile) ?>&template_code=<?= urlencode($templateCode) ?>" class="dr-action-link">View</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($pendingRequestCount > 0): ?>
                            <a href="?page=document-requests&status=pending" class="dr-footer"><?= number_format($pendingRequestCount) ?> pending request<?= $pendingRequestCount != 1 ? 's' : '' ?></a>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="dr-empty">
                                <div class="dr-empty-title">No document requests yet.</div>
                                <div class="dr-empty-subtitle">Employee document requests will appear here.</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        </div>
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
                        $deptLabels = array_map(function ($d) { return $d['department']; }, $deptCompliance);
                        $deptScores = array_map(function ($d) { return (int)($d['score'] ?? 0); }, $deptCompliance);
                        $deptLabelsJson = json_encode($deptLabels);
                        $deptScoresJson = json_encode($deptScores);
                    ?>
                    <div class="dept-chart-wrap">
                        <canvas id="deptComplianceChart"></canvas>
                    </div>
                    <script>
                        window.DEPT_COMPLIANCE_LABELS = <?= $deptLabelsJson ?>;
                        window.DEPT_COMPLIANCE_SCORES = <?= $deptScoresJson ?>;
                    </script>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-building"></i><div class="es-title">No department compliance data</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dash-row dash-row--analytics">
            <?php
                $govTotalRequired = 0;
                $govTotalSubmitted = 0;
                $govTotalOutstanding = 0;
                $govTotalVerified = 0;
                $govAgencies = [];

                if (!empty($govCompliance)) {
                    foreach ($govCompliance as $g) {
                        $required = (int)($g['required'] ?? 0);
                        $submitted = (int)($g['submitted'] ?? 0);
                        $outstanding = (int)($g['outstanding'] ?? 0);
                        $verified = (int)($g['verified'] ?? 0);
                        $submissionRate = isset($g['submissionRate']) ? (float) $g['submissionRate'] : 0;
                        $verificationRate = isset($g['verificationRate']) ? (float) $g['verificationRate'] : 0;

                        $agencyUrl = '';
                        switch (strtolower($g['name'])) {
                            case 'philhealth': $agencyUrl = '?page=philhealth-contributions'; break;
                            case 'sss': $agencyUrl = '?page=sss-contribution'; break;
                            case 'pag-ibig':
                            case 'pagibig': $agencyUrl = '?page=pagibig_monitoring'; break;
                            case 'bir': $agencyUrl = '?page=bir-monitoring'; break;
                        }

                        $govAgencies[] = compact('required', 'submitted', 'outstanding', 'verified', 'submissionRate', 'verificationRate', 'agencyUrl') + ['name' => $g['name'], 'url' => $agencyUrl];
                        $govTotalRequired += $required;
                        $govTotalSubmitted += $submitted;
                        $govTotalOutstanding += $outstanding;
                        $govTotalVerified += $verified;
                    }
                    usort($govAgencies, function ($a, $b) { return $b['outstanding'] <=> $a['outstanding']; });
                }

                $govInsightText = '';
                if (!empty($govAgencies)) {
                    if ($govTotalRequired === 0) {
                        $govInsightText = 'No government contribution submissions are currently required.';
                    } else {
                        $govInsightText = number_format($govTotalOutstanding) . ' submissions remain outstanding across ' . count($govAgencies) . ' agencies.';
                        $maxOutstanding = 0;
                        $maxAgency = '';
                        foreach ($govAgencies as $a) {
                            if ($a['outstanding'] > $maxOutstanding) { $maxOutstanding = $a['outstanding']; $maxAgency = $a['name']; }
                        }
                        if ($maxOutstanding > 0) $govInsightText .= ' ' . htmlspecialchars($maxAgency) . ' has the largest submission gap with ' . number_format($maxOutstanding) . ' outstanding submissions.';
                        if ($govTotalVerified > 0) $govInsightText .= ' ' . number_format($govTotalVerified) . ' of ' . number_format($govTotalSubmitted) . ' submitted documents have been verified, leaving ' . number_format($govTotalSubmitted - $govTotalVerified) . ' submissions pending verification.';
                    }
                }
            ?>
            <div class="chart-panel chart-panel--wide">
                <div class="chart-panel-head">
                    <h4>Government Contribution Compliance</h4>
                    <div class="chart-panel-meta">Agency submission and verification status</div>
                </div>
                <div class="chart-panel-body">
                    <?php
                        /* Government compliance totals and agencies are prepared above. */
                        /* The rendering below uses the prepared values. */
                        /*
                            foreach ($govCompliance as $g) {
                                $required = (int)($g['required'] ?? 0);
                                $submitted = (int)($g['submitted'] ?? 0);
                                $outstanding = (int)($g['outstanding'] ?? 0);
                                $verified = (int)($g['verified'] ?? 0);
                                $submissionRate = isset($g['submissionRate']) ? (float) $g['submissionRate'] : 0;
                                $verificationRate = isset($g['verificationRate']) ? (float) $g['verificationRate'] : 0;

                                $agencyUrl = '';
                                switch (strtolower($g['name'])) {
                                    case 'philhealth':
                                        $agencyUrl = '?page=philhealth-contributions';
                                        break;
                                    case 'sss':
                                        $agencyUrl = '?page=sss-contribution';
                                        break;
                                    case 'pag-ibig':
                                    case 'pagibig':
                                        $agencyUrl = '?page=pagibig_monitoring';
                                        break;
                                    case 'bir':
                                        $agencyUrl = '?page=bir-monitoring';
                                        break;
                                }

                                $govAgencies[] = [
                                    'name' => $g['name'],
                                    'required' => $required,
                                    'submitted' => $submitted,
                                    'outstanding' => $outstanding,
                                    'verified' => $verified,
                                    'submissionRate' => $submissionRate,
                                    'verificationRate' => $verificationRate,
                                    'url' => $agencyUrl,
                                ];

                                $govTotalRequired += $required;
                                $govTotalSubmitted += $submitted;
                                $govTotalOutstanding += $outstanding;
                                $govTotalVerified += $verified;
                            }

                            }
                        */

                        $overallSubmissionRate = $govTotalRequired > 0 ? round(($govTotalSubmitted / $govTotalRequired) * 100, 1) : 0;
                        $overallVerificationRate = $govTotalSubmitted > 0 ? round(($govTotalVerified / $govTotalSubmitted) * 100, 1) : 0;
                    ?>
                    <?php if (!empty($govAgencies)): ?>
                    <div class="gov-summary">
                        <div class="gov-summary-item">
                            <div class="gov-summary-value"><?= number_format($govTotalRequired) ?></div>
                            <div class="gov-summary-label">Total Required</div>
                        </div>
                        <div class="gov-summary-item">
                            <div class="gov-summary-value"><?= number_format($govTotalSubmitted) ?></div>
                            <div class="gov-summary-label">Submitted</div>
                            <div class="gov-summary-sub"><?= $overallSubmissionRate ?>%</div>
                        </div>
                        <div class="gov-summary-item gov-summary-item--outstanding">
                            <div class="gov-summary-value"><?= number_format($govTotalOutstanding) ?></div>
                            <div class="gov-summary-label">Outstanding</div>
                        </div>
                        <div class="gov-summary-item">
                            <div class="gov-summary-value"><?= number_format($govTotalVerified) ?></div>
                            <div class="gov-summary-label">Verified</div>
                            <div class="gov-summary-sub"><?= $overallVerificationRate ?>% of submitted</div>
                        </div>
                    </div>

                    <div class="gov-agencies">
                        <?php foreach ($govAgencies as $gov): 
                            $submissionWidth = $gov['required'] > 0 ? number_format(($gov['submitted'] / $gov['required']) * 100, 1) : 0;
                            $verificationWidth = $gov['submitted'] > 0 ? number_format(($gov['verified'] / $gov['submitted']) * 100, 1) : 0;
                        ?>
                        <?php if (!empty($gov['url'])): ?>
                        <a href="<?= htmlspecialchars($gov['url']) ?>" class="gov-agency-link">
                        <?php endif; ?>
                        <div class="gov-agency">
                            <div class="gov-agency-header">
                                <span class="gov-agency-name"><?= htmlspecialchars($gov['name']) ?></span>
                                <span class="gov-agency-pct"><?= $submissionWidth ?>%</span>
                            </div>
                            <div class="gov-agency-bars">
                                <div class="gov-agency-bar-label">Submission</div>
                                <div class="gov-agency-bar">
                                    <div class="gov-agency-bar-fill gov-agency-bar-fill--submission" style="width:<?= $submissionWidth ?>%;"></div>
                                </div>
                                <?php if ($gov['submitted'] > 0): ?>
                                <div class="gov-agency-bar-label">Verification</div>
                                <div class="gov-agency-bar">
                                    <div class="gov-agency-bar-fill gov-agency-bar-fill--verification" style="width:<?= $verificationWidth ?>%;"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="gov-agency-meta">
                                <span><?= number_format($gov['submitted']) ?> of <?= number_format($gov['required']) ?> submitted</span>
                                <span class="gov-agency-outstanding"><?= number_format($gov['outstanding']) ?> outstanding</span>
                            </div>
                            <?php if ($gov['submitted'] > 0): ?>
                            <div class="gov-agency-verification">
                                Verified: <?= number_format($gov['verified']) ?> / <?= number_format($gov['submitted']) ?> · <?= $verificationWidth ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($gov['url'])): ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-building"></i><div class="es-title">No government compliance records yet</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-panel-head">
                    <h4>Incident Risk &amp; Workload</h4>
                    <div class="chart-panel-meta">Incident activity by category</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($incidentCategories)): 
                        $incidentTotal = 0;
                        foreach ($incidentCategories as $c) { $incidentTotal += (int)($c['cnt'] ?? 0); }
                    ?>
                    <div class="incident-summary">
                        <a href="?page=incident-reports" class="incident-summary-link">
                            <div class="incident-summary-item">
                                <div class="incident-summary-value">17</div>
                                <div class="incident-summary-label">Total Incidents</div>
                            </div>
                        </a>
                        <a href="?page=incident-reports&amp;status=under_review&amp;category=All&amp;severity=All&amp;type=All&amp;department=&amp;officer=&amp;date_from=&amp;date_to=&amp;search=" class="incident-summary-link">
                            <div class="incident-summary-item">
                                <div class="incident-summary-value">7</div>
                                <div class="incident-summary-label">Open Cases</div>
                            </div>
                        </a>
                        <a href="?page=incident-reports&amp;status=escalated&amp;category=All&amp;severity=All&amp;type=All&amp;department=&amp;officer=&amp;date_from=&amp;date_to=&amp;search=" class="incident-summary-link">
                            <div class="incident-summary-item">
                                <div class="incident-summary-value">9</div>
                                <div class="incident-summary-label">Under Investigation</div>
                            </div>
                        </a>
                        <a href="?page=incident-reports" class="incident-summary-link">
                            <div class="incident-summary-item">
                                <div class="incident-summary-value">8</div>
                                <div class="incident-summary-label">Pending CAPA</div>
                            </div>
                        </a>
                        <a href="?page=incident-reports" class="incident-summary-link">
                            <div class="incident-summary-item">
                                <div class="incident-summary-value"><?= number_format($closedCases) ?></div>
                                <div class="incident-summary-label">Closed Cases</div>
                            </div>
                        </a>
                    </div>
                    <div class="incident-categories">
                        <?php foreach ($incidentCategories as $cat): 
                            $catShare = $incidentTotal > 0 ? number_format(($cat['cnt'] / $incidentTotal) * 100, 1) : 0;
                        ?>
                        <div class="incident-cat-row" data-category="<?= htmlspecialchars($cat['incident_type']) ?>">
                            <div class="incident-cat-label"><?= htmlspecialchars($cat['incident_type']) ?></div>
                            <div class="incident-cat-track">
                                <div class="incident-cat-fill" style="width:<?= $catShare ?>%;"></div>
                            </div>
                            <div class="incident-cat-meta">
                                <span class="incident-cat-value"><?= $cat['cnt'] ?></span>
                                <span class="incident-cat-pct"><?= $catShare ?>%</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-clipboard-list"></i><div class="es-title">No open incidents</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dash-row dash-row--analytics">
<div class="chart-panel chart-panel--wide incident-occurrence-panel">

    <div class="chart-panel-head">
        <div>
            <h4>Incident Occurrence</h4>
            <div class="chart-panel-meta">
                Monthly incident activity and severity
            </div>
        </div>

        <div class="incident-calendar-controls">
            <button
                type="button"
                class="incident-calendar-btn"
                data-direction="prev"
                aria-label="Previous month">
                &#8249;
            </button>

            <span class="incident-calendar-month">August 2026</span>

            <button
                type="button"
                class="incident-calendar-btn"
                data-direction="next"
                aria-label="Next month">
                &#8250;
            </button>
        </div>
    </div>

    <div class="chart-panel-body">

        <div class="incident-calendar-summary">

            <div class="incident-summary-item">
                <div class="incident-summary-value">18</div>
                <div class="incident-summary-label">Total Incidents</div>
            </div>

            <div class="incident-summary-item">
                <div class="incident-summary-value">8</div>
                <div class="incident-summary-label">Active Days</div>
            </div>

            <div class="incident-summary-item">
                <div class="incident-summary-value">Aug 11</div>
                <div class="incident-summary-label">Peak Day</div>
            </div>

            <div class="incident-summary-item">
                <div class="incident-summary-value">3</div>
                <div class="incident-summary-label">Peak Count</div>
            </div>

        </div>

        <div
            class="incident-calendar"
            data-year="2026"
            data-month="9"
            data-days="{&quot;2026-08-09&quot;:{&quot;date&quot;:&quot;2026-08-09&quot;,&quot;total&quot;:1,&quot;categories&quot;:{&quot;Workplace Accident&quot;:1},&quot;severities&quot;:{&quot;high&quot;:1}},&quot;2026-08-10&quot;:{&quot;date&quot;:&quot;2026-08-10&quot;,&quot;total&quot;:1,&quot;categories&quot;:{&quot;Occupational Injury&quot;:1},&quot;severities&quot;:{&quot;medium&quot;:1}},&quot;2026-08-11&quot;:{&quot;date&quot;:&quot;2026-08-11&quot;,&quot;total&quot;:3,&quot;categories&quot;:{&quot;Exposure Incident&quot;:2,&quot;Workplace Accident&quot;:1},&quot;severities&quot;:{&quot;high&quot;:2,&quot;medium&quot;:1}},&quot;2026-08-12&quot;:{&quot;date&quot;:&quot;2026-08-12&quot;,&quot;total&quot;:2,&quot;categories&quot;:{&quot;Medical Emergency&quot;:2},&quot;severities&quot;:{&quot;high&quot;:2}},&quot;2026-08-13&quot;:{&quot;date&quot;:&quot;2026-08-13&quot;,&quot;total&quot;:2,&quot;categories&quot;:{&quot;Environmental &amp; Safety Hazard&quot;:1,&quot;Health Incident&quot;:1},&quot;severities&quot;:{&quot;high&quot;:1,&quot;medium&quot;:1}},&quot;2026-08-14&quot;:{&quot;date&quot;:&quot;2026-08-14&quot;,&quot;total&quot;:2,&quot;categories&quot;:{&quot;Health Incident&quot;:1,&quot;Workplace Accident&quot;:1},&quot;severities&quot;:{&quot;medium&quot;:2}},&quot;2026-08-15&quot;:{&quot;date&quot;:&quot;2026-08-15&quot;,&quot;total&quot;:2,&quot;categories&quot;:{&quot;Health Incident&quot;:1,&quot;Workplace Accident&quot;:1},&quot;severities&quot;:{&quot;medium&quot;:1,&quot;high&quot;:1}},&quot;2026-08-16&quot;:{&quot;date&quot;:&quot;2026-08-16&quot;,&quot;total&quot;:5,&quot;categories&quot;:{&quot;Environmental &amp; Safety Hazard&quot;:1,&quot;Return-to-Work Monitoring&quot;:3,&quot;Workplace Accident&quot;:1},&quot;severities&quot;:{&quot;critical&quot;:1,&quot;low&quot;:2,&quot;medium&quot;:2}}}">

            <div class="incident-calendar-weekdays">
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
                <div>Sun</div>
            </div>

            <div class="incident-calendar-grid">
                <div class="incident-calendar-day incident-calendar-day--empty"></div>
                <div class="incident-calendar-day incident-calendar-day--empty"></div>
                <div class="incident-calendar-day incident-calendar-day--empty"></div>
                <div class="incident-calendar-day incident-calendar-day--empty"></div>
                <div class="incident-calendar-day incident-calendar-day--empty"></div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-01, 0 incidents">
                    <div class="incident-calendar-day-number">1</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-02, 0 incidents">
                    <div class="incident-calendar-day-number">2</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-03, 0 incidents">
                    <div class="incident-calendar-day-number">3</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-04, 0 incidents">
                    <div class="incident-calendar-day-number">4</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-05, 0 incidents">
                    <div class="incident-calendar-day-number">5</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-06, 0 incidents">
                    <div class="incident-calendar-day-number">6</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-07, 0 incidents">
                    <div class="incident-calendar-day-number">7</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-08, 0 incidents">
                    <div class="incident-calendar-day-number">8</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--low" role="button" tabindex="0" aria-label="2026-08-09, 1 incident">
                    <div class="incident-calendar-day-number">9</div>
                    <div class="incident-calendar-day-count">1</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--low" role="button" tabindex="0" aria-label="2026-08-10, 1 incident">
                    <div class="incident-calendar-day-number">10</div>
                    <div class="incident-calendar-day-count">1</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--high incident-calendar-day--selected" role="button" tabindex="0" aria-label="2026-08-11, 3 incidents">
                    <div class="incident-calendar-day-number">11</div>
                    <div class="incident-calendar-day-count">3</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--medium" role="button" tabindex="0" aria-label="2026-08-12, 2 incidents">
                    <div class="incident-calendar-day-number">12</div>
                    <div class="incident-calendar-day-count">2</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--medium" role="button" tabindex="0" aria-label="2026-08-13, 2 incidents">
                    <div class="incident-calendar-day-number">13</div>
                    <div class="incident-calendar-day-count">2</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--medium" role="button" tabindex="0" aria-label="2026-08-14, 2 incidents">
                    <div class="incident-calendar-day-number">14</div>
                    <div class="incident-calendar-day-count">2</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--medium" role="button" tabindex="0" aria-label="2026-08-15, 2 incidents">
                    <div class="incident-calendar-day-number">15</div>
                    <div class="incident-calendar-day-count">2</div>
                </div>

                <div class="incident-calendar-day incident-calendar-day--critical" role="button" tabindex="0" aria-label="2026-08-16, 5 incidents">
                    <div class="incident-calendar-day-number">16</div>
                    <div class="incident-calendar-day-count">5</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-17, 0 incidents">
                    <div class="incident-calendar-day-number">17</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-18, 0 incidents">
                    <div class="incident-calendar-day-number">18</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-19, 0 incidents">
                    <div class="incident-calendar-day-number">19</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-20, 0 incidents">
                    <div class="incident-calendar-day-number">20</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-21, 0 incidents">
                    <div class="incident-calendar-day-number">21</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-22, 0 incidents">
                    <div class="incident-calendar-day-number">22</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-23, 0 incidents">
                    <div class="incident-calendar-day-number">23</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-24, 0 incidents">
                    <div class="incident-calendar-day-number">24</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-25, 0 incidents">
                    <div class="incident-calendar-day-number">25</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-26, 0 incidents">
                    <div class="incident-calendar-day-number">26</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-27, 0 incidents">
                    <div class="incident-calendar-day-number">27</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-28, 0 incidents">
                    <div class="incident-calendar-day-number">28</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-29, 0 incidents">
                    <div class="incident-calendar-day-number">29</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-30, 0 incidents">
                    <div class="incident-calendar-day-number">30</div>
                </div>

                <div class="incident-calendar-day" role="button" tabindex="0" aria-label="2026-08-31, 0 incidents">
                    <div class="incident-calendar-day-number">31</div>
                </div>
            </div>
        </div>

        <div class="incident-calendar-legend">
            <span>Incident Count</span>
            <span class="legend-level legend-level--0"></span>
            <span class="legend-level legend-level--1"></span>
            <span class="legend-level legend-level--2"></span>
            <span class="legend-level legend-level--3"></span>
            <span class="legend-level legend-level--4"></span>
            <span>High Activity</span>
        </div>

        <div class="incident-selected-day" aria-live="polite" style="display: none !important;">

            <div class="incident-selected-day-head">
                <div>
                    <div class="incident-insight-label">Selected Date</div>
                    <div class="incident-selected-date">August 11, 2026</div>
                </div>

                <div class="incident-selected-total">
                    <strong>3</strong>
                    <span>Incidents</span>
                </div>
            </div>

            <div class="incident-selected-details">

                <div class="incident-detail-group">
                    <div class="incident-detail-title">Incident Categories</div>

                    <div class="incident-detail-row">
                        <span>Exposure Incident</span>
                        <strong>2</strong>
                    </div>

                    <div class="incident-detail-row">
                        <span>Workplace Accident</span>
                        <strong>1</strong>
                    </div>
                </div>

                <div class="incident-detail-group">
                    <div class="incident-detail-title">Severity</div>

                    <div class="incident-detail-row">
                        <span>High</span>
                        <strong>2</strong>
                    </div>

                    <div class="incident-detail-row">
                        <span>Medium</span>
                        <strong>1</strong>
                    </div>
                </div>

            </div>
        </div>

        <div class="incident-calendar-insight">

            <div class="incident-insight-label">
                Analytical Insight
            </div>

            <div class="incident-insight-text">
                18 incidents were recorded across 8 active days.
                Incident activity peaked on August 16 with 5 incidents.
            </div>

        </div>

    </div>
</div>


        <div class="dash-row dash-row--analytics dash-row--stacked">
            <div class="chart-panel chart-panel--wide priority-actions-panel">
                <div class="chart-panel-head">
                    <h4>Priority Actions</h4>
                    <div class="chart-panel-meta"><?= number_format($actionAnalytics['total']) ?> items requiring attention</div>
                </div>
                <div class="chart-panel-body">
                    <?php if ($actionAnalytics['total'] > 0): ?>
                    <div class="priority-actions-summary">
                        <div class="action-summary-item">
                            <div class="action-summary-value"><?= number_format($actionAnalytics['urgency']['overdue']) ?></div>
                            <div class="action-summary-label">Overdue</div>
                        </div>
                        <div class="action-summary-item">
                            <div class="action-summary-value"><?= number_format($actionAnalytics['urgency']['today']) ?></div>
                            <div class="action-summary-label">Due Today</div>
                        </div>
                        <div class="action-summary-item">
                            <div class="action-summary-value"><?= number_format($actionAnalytics['urgency']['soon']) ?></div>
                            <div class="action-summary-label">Due Soon</div>
                        </div>
                        <div class="action-summary-item">
                            <div class="action-summary-value"><?= number_format(count($actionAnalytics['groups'])) ?></div>
                            <div class="action-summary-label">Action Groups</div>
                        </div>
                    </div>

                    <?php
                        $urgencyMax = max(array_values($actionAnalytics['urgency']));
                        if ($urgencyMax <= 0) $urgencyMax = 1;
                        $urgencyLabels = [
                            'overdue' => 'Overdue',
                            'today' => 'Due Today',
                            'soon' => 'Due Soon',
                            'upcoming' => 'Upcoming',
                            'unscheduled' => 'Unscheduled',
                        ];
                    ?>
                    <div class="action-status-chart">
                        <?php foreach ($actionAnalytics['urgency'] as $key => $count): if ($count <= 0) continue; ?>
                        <div class="action-status-row">
                            <div class="action-status-label"><?= $urgencyLabels[$key] ?? ucfirst($key) ?></div>
                            <div class="action-status-track">
                                <div class="action-status-fill action-status-fill--<?= $key ?>" style="width:<?= number_format(($count / $urgencyMax) * 100, 1) ?>%;"></div>
                            </div>
                            <div class="action-status-value"><?= number_format($count) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="action-group-list">
                        <?php foreach (array_slice($actionAnalytics['groups'], 0, 5) as $group): 
                            $dueDate = $group['due_date'] ?? null;
                            $relativeDate = '';
                            if ($dueDate) {
                                $due = new DateTime($dueDate);
                                $todayObj = new DateTime('today');
                                $diff = $todayObj->diff($due)->days;
                                if ($due < $todayObj) {
                                    $relativeDate = 'Overdue by ' . $diff . ' day' . ($diff != 1 ? 's' : '');
                                } elseif ($due == $todayObj) {
                                    $relativeDate = 'Due today';
                                } else {
                                    $relativeDate = 'Due in ' . $diff . ' day' . ($diff != 1 ? 's' : '');
                                }
                            }
                        ?>
                        <div class="action-group">
                            <div class="action-group-main">
                                <div class="action-group-title">
                                    <span class="action-urgency action-urgency--<?= $group['urgency'] ?>"><?= $urgencyLabels[$group['urgency']] ?? ucfirst($group['urgency']) ?></span>
                                    <?= htmlspecialchars($group['title']) ?>
                                </div>
                                <div class="action-group-type"><?= htmlspecialchars($group['type']) ?></div>
                                <div class="action-group-meta">
                                    <span><?= number_format($group['people_count']) ?> people</span>
                                    <span><?= number_format($group['people_count']) ?> pending</span>
                                </div>
                                <div class="action-group-due">
                                    <?php if ($dueDate): ?>
                                        <?= htmlspecialchars(date('M d, Y', strtotime($dueDate))) ?>
                                        <span class="action-group-relative"><?= htmlspecialchars($relativeDate) ?></span>
                                    <?php else: ?>
                                        No due date
                                    <?php endif; ?>
                            </div>
                            <?php if (!empty($group['url'])): ?>
                                <button type="button" class="action-group-action" onclick="window.open('<?= htmlspecialchars($group['url']) ?>', '_blank')">View</button>
                            <?php else: ?>
                                <button type="button" class="action-group-action">View</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="action-compliance-insight">
                        <div class="action-insight-label">Attention Summary</div>
                        <div class="action-insight-text">
                            <?php
                                $topGroup = $actionAnalytics['groups'][0] ?? null;
                                if ($topGroup):
                            ?>
                                <?= htmlspecialchars($topGroup['type']) ?> requires the most attention with <?= number_format($topGroup['people_count']) ?> affected records.
                            <?php else: ?>
                                No pending actions require attention.
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-check-circle"></i><div class="es-title">All actions are up to date</div></div>
                    <div class="action-compliance-insight">
                        <div class="action-insight-label">Attention Summary</div>
                        <div class="action-insight-text">No pending actions require attention.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chart-panel compliance-signals-panel">
                <div class="chart-panel-head">
                    <h4>Compliance Signals</h4>
                    <div class="chart-panel-meta"><?= count($alerts) ?> active</div>
                </div>
                <div class="chart-panel-body">
                    <?php if (!empty($alerts)): 
                        $signalMax = 0;
                        foreach ($alerts as $alert) { if (isset($alert['count']) && $alert['count'] > $signalMax) $signalMax = $alert['count']; }
                        if ($signalMax <= 0) $signalMax = 1;
                    ?>
                    <div class="compliance-signal-list">
                        <?php foreach ($alerts as $alert): if (!isset($alert['count'])) continue; ?>
                        <div class="compliance-signal">
                            <div class="compliance-signal-value"><?= number_format($alert['count']) ?></div>
                            <div class="compliance-signal-label"><?= htmlspecialchars($alert['label'] ?? $alert['message']) ?></div>
                            <div class="compliance-signal-track">
                                <div class="compliance-signal-fill" style="width:<?= number_format(($alert['count'] / $signalMax) * 100, 1) ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-check-circle"></i><div class="es-title">No active compliance signals</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>


<script src="js/pages/legal-case-chat.js"></script>

