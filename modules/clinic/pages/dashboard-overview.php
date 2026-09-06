<?php
require_once __DIR__ . '/../classes/ClinicDashboard.php';

$clinicDashboard = new ClinicDashboard();
$clinicData = $clinicDashboard->getData($_GET['range'] ?? null);
$range = $clinicData['range'];
$inv = $clinicData['inventory_status'];
$invTotal = max(1, (int)($inv['total'] ?? 0));
$invInStock = (int)($inv['in_stock'] ?? 0);
$invLowStock = (int)($inv['low_stock'] ?? 0);
$invExpired = (int)($inv['expired'] ?? 0);
$invInStockPct = $invTotal > 0 ? round(($invInStock / $invTotal) * 100) : 0;
$invLowStockPct = $invTotal > 0 ? round(($invLowStock / $invTotal) * 100) : 0;
$invExpiredPct = $invTotal > 0 ? round(($invExpired / $invTotal) * 100) : 0;
$clinicJsFile = __DIR__ . '/../js/pages/dashboard-overview.js';
$clinicJsVersion = is_file($clinicJsFile) ? (string) filemtime($clinicJsFile) : '1';
?>

<section class="clinic-dashboard">
    <div class="dashboard-heading">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back, Clinic Staff!</p>
        </div>
        <div class="dashboard-actions">
            <div class="dashboard-date">
                <i class="fa-regular fa-calendar"></i>
                <span><?= date('F j, Y | l') ?></span>
            </div>
        </div>
    </div>

    <div class="stat-grid" id="statGrid">
        <article class="stat-card stat--blue">
            <div class="stat-icon-wrap">
                <i class="fa-solid fa-users stat-icon"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-metric="total_patients"><?= number_format((int)($clinicData['metrics']['total_patients'] ?? 0)) ?></div>
                <div class="stat-label">Total Patients<br><span class="stat-sub">This Month</span></div>
                <div class="stat-change stat-change--up" data-change="total_patients_change">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span><?= (int)($clinicData['metrics']['total_patients_change'] ?? 0) ?>%</span>
                </div>
            </div>
        </article>

        <article class="stat-card stat--green">
            <div class="stat-icon-wrap">
                <i class="fa-solid fa-clipboard-list stat-icon"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-metric="todays_appointments"><?= number_format((int)($clinicData['metrics']['todays_appointments'] ?? 0)) ?></div>
                <div class="stat-label">Today's<br><span class="stat-sub">Appointments</span></div>
                <div class="stat-change stat-change--up" data-change="todays_appointments_change">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span><?= (int)($clinicData['metrics']['todays_appointments_change'] ?? 0) ?>%</span>
                </div>
            </div>
        </article>

        <article class="stat-card stat--purple">
            <div class="stat-icon-wrap">
                <i class="fa-solid fa-briefcase-medical stat-icon"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-metric="emergency_cases"><?= number_format((int)($clinicData['metrics']['emergency_cases'] ?? 0)) ?></div>
                <div class="stat-label">Emergency<br><span class="stat-sub">Cases</span></div>
                <div class="stat-change stat-change--up" data-change="emergency_cases_change">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span><?= (int)($clinicData['metrics']['emergency_cases_change'] ?? 0) ?>%</span>
                </div>
            </div>
        </article>

        <article class="stat-card stat--amber">
            <div class="stat-icon-wrap">
                <i class="fa-solid fa-pills stat-icon"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-metric="low_stock"><?= number_format((int)($clinicData['metrics']['low_stock'] ?? 0)) ?></div>
                <div class="stat-label">Low Stock<br><span class="stat-sub">Medicines</span></div>
                <div class="stat-change stat-change--down" data-change="low_stock_change">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                    <span><?= abs((int)($clinicData['metrics']['low_stock_change'] ?? 0)) ?>%</span>
                </div>
            </div>
        </article>

        <article class="stat-card stat--red">
            <div class="stat-icon-wrap">
                <i class="fa-solid fa-bell stat-icon"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value" data-metric="expired_meds"><?= number_format((int)($clinicData['metrics']['expired_meds'] ?? 0)) ?></div>
                <div class="stat-label">Expired<br><span class="stat-sub">Medicines</span></div>
                <div class="stat-change stat-change--up" data-change="expired_meds_change">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span><?= (int)($clinicData['metrics']['expired_meds_change'] ?? 0) ?>%</span>
                </div>
            </div>
        </article>
    </div>

    <div class="dash-grid dash-grid--2col">
        <section class="dash-panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-chart-line"></i> Patient Visits Overview</h2>
                <select class="range-select" id="rangeSelect" aria-label="Date range for patient visits">
                    <option value="this_month" <?= $range === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="last_7_days" <?= $range === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30_days" <?= $range === 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                </select>
            </div>
            <canvas class="visit-chart" id="visitChart" aria-label="Patient visits line chart"></canvas>
            <div class="visit-stats">
                <div class="visit-stat">
                    <div class="visit-stat-label">Total Visits</div>
                    <div class="visit-stat-row">
                        <strong class="visit-stat-value" data-visit-stat="total"><?= number_format((int)($clinicData['visit_stats']['total'] ?? 0)) ?></strong>
                        <span class="stat-change stat-change--up" data-change="total_change">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                            <span><?= (int)($clinicData['visit_stats']['total_change'] ?? 0) ?>%</span>
                        </span>
                    </div>
                </div>
                <div class="visit-stat-divider"></div>
                <div class="visit-stat">
                    <div class="visit-stat-label">Average / Day</div>
                    <div class="visit-stat-row">
                        <strong class="visit-stat-value" data-visit-stat="average_day"><?= htmlspecialchars($clinicData['visit_stats']['average_day'] ?? 0) ?></strong>
                        <span class="stat-change stat-change--up" data-change="average_change">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                            <span><?= (int)($clinicData['visit_stats']['average_change'] ?? 0) ?>%</span>
                        </span>
                    </div>
                </div>
                <div class="visit-stat-divider"></div>
                <div class="visit-stat">
                    <div class="visit-stat-label">This Month</div>
                    <div class="visit-stat-row">
                        <span class="visit-stat-value visit-stat-value--small" data-visit-stat="label"><?= htmlspecialchars($clinicData['visit_stats']['label'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="dash-panel">
            <div class="panel-header panel-header--link">
                <h2><i class="fa-regular fa-calendar-check"></i> Upcoming Appointments</h2>
                <a href="?page=medical-records-history" class="view-all">View All</a>
            </div>
            <ul class="appointment-list" id="appointmentList">
                <?php foreach ($clinicData['upcoming_appointments'] as $a): ?>
                <li class="appointment-item">
                    <span class="appointment-time"><?= date('h:i A', strtotime($a['appointment_time'])) ?></span>
                    <span class="appointment-name"><?= htmlspecialchars($a['patient_name'] ?: 'Unknown patient') ?></span>
                    <span class="appointment-purpose"><?= htmlspecialchars($a['purpose']) ?></span>
                    <span class="appointment-badge"><?= htmlspecialchars($a['status']) ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($clinicData['upcoming_appointments'])): ?>
                <li class="empty-dash"><i class="fa-regular fa-calendar-xmark"></i> No upcoming appointments.</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <div class="dash-grid dash-grid--3col">
        <section class="dash-panel">
            <div class="panel-header panel-header--link">
                <h2><i class="fa-solid fa-pills"></i> Medicine Inventory Status</h2>
                <a href="?page=medicines-inventory" class="view-all">View All</a>
            </div>
            <div class="inventory-wrap">
                <div class="donut-container">
                    <canvas id="inventoryDonut" aria-label="Medicine inventory status donut chart"></canvas>
                    <div class="donut-total">
                        <span>Total</span>
                        <strong id="inventoryTotal"><?= number_format($invTotal) ?></strong>
                    </div>
                </div>
                <ul class="inventory-legend">
                    <li>
                        <span class="legend-dot legend-dot--green"></span>
                        <span class="legend-label">In Stock</span>
                        <span class="legend-value" id="inv-instock"><?= $invInStock ?> <small>(<?= $invInStockPct ?>%)</small></span>
                    </li>
                    <li>
                        <span class="legend-dot legend-dot--amber"></span>
                        <span class="legend-label">Low Stock</span>
                        <span class="legend-value" id="inv-lowstock"><?= $invLowStock ?> <small>(<?= $invLowStockPct ?>%)</small></span>
                    </li>
                    <li>
                        <span class="legend-dot legend-dot--red"></span>
                        <span class="legend-label">Expired</span>
                        <span class="legend-value" id="inv-expired"><?= $invExpired ?> <small>(<?= $invExpiredPct ?>%)</small></span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="dash-panel">
            <div class="panel-header panel-header--link">
                <h2><i class="fa-solid fa-truck-medical"></i> Recent Emergency Cases</h2>
                <a href="?page=emergency-cases" class="view-all">View All</a>
            </div>
            <ul class="emergency-list" id="emergencyList">
                <?php foreach ($clinicData['recent_emergency'] as $e): ?>
                <li class="emergency-item">
                    <?php
                    $sev = strtolower($e['severity_level'] ?? 'medium');
                    $sevClass = 'urgent-tag';
                    if ($sev === 'high' || $sev === 'critical') $sevClass .= ' urgent-tag--critical';
                    elseif ($sev === 'low' || $sev === 'minor') $sevClass .= ' urgent-tag--low';
                    ?>
                    <span class="<?= $sevClass ?>"><?= htmlspecialchars($e['severity_level'] ?? 'Medium') ?></span>
                    <div class="emergency-info">
                        <strong><?= htmlspecialchars($e['patient_name'] ?: 'Unknown patient') ?></strong>
                        <small><?= htmlspecialchars($e['chief_complaint']) ?></small>
                    </div>
                    <span class="emergency-time"><?= date('g:i A', strtotime($e['incident_date'])) ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($clinicData['recent_emergency'])): ?>
                <li class="empty-dash"><i class="fa-solid fa-shield-heart"></i> No recent emergency cases.</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="panel-header panel-header--link">
                <h2><i class="fa-regular fa-file-lines"></i> Recent Clinic Reports</h2>
                <a href="?page=clinic-reports" class="view-all">View All</a>
            </div>
            <ul class="reports-list" id="reportsList">
                <?php foreach ($clinicData['recent_reports'] as $r): ?>
                <li class="reports-item">
                    <span class="reports-icon"><i class="fa-regular fa-file-lines"></i></span>
                    <div class="reports-info">
                        <strong><?= htmlspecialchars($r['report_type']) ?> Clinic Report - <?= date('M j, Y', strtotime($r['report_date'])) ?></strong>
                    </div>
                    <span class="reports-date"><?= date('M j, Y', strtotime($r['report_date'])) ?></span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($clinicData['recent_reports'])): ?>
                <li class="empty-dash"><i class="fa-regular fa-folder-open"></i> No reports available.</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <section class="dash-panel employee-directory-table">
        <div class="panel-header panel-header--link">
            <h2><i class="fa-regular fa-clock-rotate-left"></i> Recent Medical Records</h2>
            <a href="?page=medical-records-history" class="view-all">View All</a>
        </div>
        <div class="clinic-table-wrap">
            <table class="clinic-table" aria-label="Recent medical records table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date / Time</th>
                        <th>Chief Complaint</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recentMedicalTableBody">
                    <?php foreach ($clinicData['recent_medical_records'] as $r): ?>
                    <?php
                        $typeClass = strtolower(trim(preg_replace('/[^a-z0-9-]+/i', '-', (string)($r['consultation_type'] ?? 'General')), '-'));
                        $statusClass = strtolower(trim(preg_replace('/[^a-z0-9-]+/i', '-', (string)($r['status'] ?? 'Completed')), '-'));
                        $empCodeHtml = !empty($r['employee_code'])
                            ? '<span class="emp-code"><i class="fa-regular fa-id-badge"></i> ' . htmlspecialchars($r['employee_code']) . '</span>'
                            : '';
                        $empId = (int)($r['employee_id'] ?? 0);
                    ?>
                    <tr class="employee-row" data-employee-id="<?= $empId ?>">
                        <td>
                            <div class="emp-name-wrap">
                                <strong class="emp-name"><?= htmlspecialchars($r['employee_name'] ?: 'Unknown Patient') ?></strong>
                                <?= $empCodeHtml ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['department_name'] ?? 'N/A') ?></td>
                        <td><?= !empty($r['visit_date']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($r['visit_date']))) : '&mdash;' ?></td>
                        <td><?= htmlspecialchars($r['chief_complaint'] ?? 'N/A') ?></td>
                        <td><span class="clinic-badge <?= htmlspecialchars($typeClass) ?>"><?= htmlspecialchars($r['consultation_type'] ?? 'General') ?></span></td>
                        <td><span class="clinic-badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($r['status'] ?? 'Completed') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clinicData['recent_medical_records'])): ?>
                    <tr>
                        <td colspan="6" class="empty-clinic">No recent medical records yet &mdash; entries will appear here once consultations are logged.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="dash-footer">
        <div class="dash-footer-left">
            <div class="dash-footer-logo">
                <i class="fa-solid fa-heart-pulse"></i>
                <strong>Bestlink HR Clinic</strong>
            </div>
            <span class="dash-footer-copyright">
                <i class="fa-regular fa-copyright"></i>
                <span><?= date('Y') ?> Bestlink College Clinic. All rights reserved.</span>
            </span>
        </div>
        <div class="dash-footer-right">
            <div class="dash-footer-meta">
                <i class="fa-solid fa-circle"></i>
                <span>System Online</span>
            </div>
        </div>
    </div>
</section>

<script>
window.__DASH_INIT_DATA__ = <?= json_encode($clinicData) ?>;
</script>
<script src="js/pages/dashboard-overview.js?v=<?= $clinicJsVersion ?>" defer></script>
