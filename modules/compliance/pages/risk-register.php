<?php
// =============================================================================
// Risk Assessment – Risk Register
// Migrated from legal_compliance to hrms-capstone
// =============================================================================
$pageTitle = 'Risk Register';

require_once __DIR__ . '/../../../database/db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ------------------------------------------------------------------
// DB helper functions
// ------------------------------------------------------------------
function ra_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function ra_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

// ------------------------------------------------------------------
// Recommendation mapping per risk source (per module spec)
// ------------------------------------------------------------------
$recommendations = [
    'Workforce Risk'       => 'Coaching, Attendance Counseling',
    'Performance Risk'     => 'Performance Improvement Plan (PIP)',
    'Behavioral Risk'      => 'Investigation or Counseling',
    'Payroll Risk'         => 'Payroll Verification',
    'Recruitment & Onboarding Risk' => 'Follow-up on Requirements, Background Check & Onboarding',
    'Employee Management Risk' => 'Update and Verify Employee Records',
    'Compliance Risk'      => 'Complete Required Compliance Action',
    'Employee Portal Risk' => 'Review and Complete Pending Employee Request',
    'Administrative Risk'  => 'Review Administrative Process',
    'Attendance Risk'      => 'Attendance Review and Counseling',
    'Engagement Risk'      => 'Employee Relations Follow-up',
    'Health Risk'          => 'Clinic Review / Medical Evaluation',
    'Training Risk'        => 'Complete Required Training',
    'Exit Risk'            => 'Complete Clearance Process',
];

// Map a risk_type to its risk source category
function ra_source_of(string $riskType): string {
    $r = strtolower(trim($riskType));
    $map = [
        // Attendance
        'frequent late' => 'Attendance Risk', 'frequent absences' => 'Attendance Risk',
        'awol' => 'Attendance Risk', 'undertime' => 'Attendance Risk', 'leave abuse' => 'Attendance Risk',
        'excessive overtime' => 'Attendance Risk',
        // Workforce
        'department understaffing' => 'Workforce Risk',
        'low performance rating' => 'Performance Risk', 'failed kpi' => 'Performance Risk',
        'pip' => 'Performance Risk', 'poor productivity' => 'Performance Risk',
        'overdue performance goal' => 'Performance Risk',
        // Behavioral / Engagement
        'complaints' => 'Behavioral Risk', 'grievances' => 'Behavioral Risk',
        'harassment' => 'Behavioral Risk', 'bullying' => 'Behavioral Risk',
        'unresolved grievance' => 'Engagement Risk', 'low engagement score' => 'Engagement Risk',
        // Payroll
        'salary dispute' => 'Payroll Risk', 'payroll error' => 'Payroll Risk',
        'missing contributions' => 'Payroll Risk', 'tax errors' => 'Payroll Risk',
        'missing payroll record' => 'Payroll Risk', 'unusual payroll adjustment' => 'Payroll Risk',
        // Recruitment / Onboarding
        'missing requirements' => 'Recruitment & Onboarding Risk', 'missing contract' => 'Recruitment & Onboarding Risk',
        'failed background check' => 'Recruitment & Onboarding Risk',
        'incomplete recruitment record' => 'Recruitment & Onboarding Risk',
        'incomplete onboarding' => 'Recruitment & Onboarding Risk', 'incomplete onboarding requirements' => 'Recruitment & Onboarding Risk',
        // Employee Management
        'incomplete employee profile' => 'Employee Management Risk',
        'missing emergency contact' => 'Employee Management Risk',
        'expired employee document' => 'Employee Management Risk',
        // Compliance
        'expired contract' => 'Compliance Risk', 'missing documents' => 'Compliance Risk',
        'expired ids' => 'Compliance Risk',
        'missing policy acknowledgment' => 'Compliance Risk',
        'open compliance incident' => 'Compliance Risk',
        'overdue statutory contribution' => 'Compliance Risk',
        'overdue tax contribution' => 'Compliance Risk',
        // Documentation / Safety
        'workplace accident' => 'Safety Risk', 'property damage' => 'Safety Risk',
        'safety hazard' => 'Safety Risk',
        // Health
        'medical emergency' => 'Health Risk', 'work injury' => 'Health Risk',
        'medical restriction' => 'Health Risk',
        'expired medical clearance' => 'Health Risk',
        // Training
        'incomplete training' => 'Training Risk', 'expired certification' => 'Training Risk',
        'overdue mandatory training' => 'Training Risk',
        // Exit
        'pending clearance' => 'Exit Risk', 'unreturned assets' => 'Exit Risk',
        'pending exit clearance' => 'Exit Risk', 'pending final settlement' => 'Exit Risk',
        // Portal / Admin
        'unread employee notification' => 'Employee Portal Risk',
        'inactive employee account' => 'Administrative Risk',
        // Workforce management
        'department understaffing' => 'Workforce Risk',
    ];
    foreach ($map as $k => $v) {
        if (strpos($r, $k) !== false) return $v;
    }
    return 'Workforce Risk';
}

// Responsible department / officer for a risk source
function ra_responsible(string $source): string {
    $map = [
        'Workforce Risk'          => 'HR Officer',
        'Performance Risk'        => 'Performance Manager',
        'Behavioral Risk'         => 'HR & Employee Relations',
        'Payroll Risk'            => 'Payroll Officer',
        'Recruitment & Onboarding Risk' => 'Recruitment / Onboarding Officer',
        'Employee Management Risk'=> 'HR Officer',
        'Compliance Risk'         => 'Compliance Officer',
        'Employee Portal Risk'    => 'HR Officer',
        'Administrative Risk'     => 'Admin Officer',
        'Attendance Risk'         => 'Time & Attendance Officer',
        'Engagement Risk'         => 'Employee Engagement Officer',
        'Health Risk'             => 'Clinic Officer',
        'Training Risk'           => 'L&D Officer',
        'Exit Risk'               => 'Exit Management Officer',
        'Documentation Risk'      => 'Records Officer',
        'Safety Risk'             => 'Safety Officer',
    ];
    return $map[$source] ?? 'HR Officer';
}

// Source department for display
function ra_source_dept(string $riskType): string {
    $source = ra_source_of($riskType);
    $map = [
        'Workforce Risk'          => 'Workforce Management',
        'Performance Risk'        => 'Performance Management',
        'Behavioral Risk'         => 'Employee Relations',
        'Payroll Risk'            => 'Payroll',
        'Recruitment & Onboarding Risk' => 'Recruitment & Onboarding',
        'Employee Management Risk'=> 'Employee Management',
        'Compliance Risk'         => 'Legal & Compliance',
        'Employee Portal Risk'    => 'Employee Portal',
        'Administrative Risk'     => 'Admin Portal',
        'Attendance Risk'         => 'Time & Attendance',
        'Engagement Risk'         => 'Engagement Management',
        'Health Risk'             => 'Clinic',
        'Training Risk'           => 'Learning & Development',
        'Exit Risk'               => 'Exit Management',
        'Documentation Risk'      => 'Employee Documents',
        'Safety Risk'             => 'Incident Reporting',
    ];
    return $map[$source] ?? '—';
}

function ra_likelihood(string $level): string {
    $map = [
        'Critical' => 'Almost Certain',
        'High' => 'Likely',
        'Medium' => 'Possible',
        'Low' => 'Unlikely',
    ];
    return $map[$level] ?? 'Possible';
}
function ra_impact(string $level): string {
    $map = [
        'Critical' => 'Severe',
        'High' => 'Major',
        'Medium' => 'Moderate',
        'Low' => 'Minor',
    ];
    return $map[$level] ?? 'Minor';
}

function ra_status_label(string $s): string {
    $map = [
        'new_report'   => 'Open',
        'under_review' => 'Under Review',
        'mitigated'    => 'Mitigated',
        'resolved'     => 'Resolved',
        'closed'       => 'Closed',
    ];
    return $map[$s] ?? ucfirst(str_replace('_', ' ', $s));
}
function ra_status_class(string $s): string {
    $m = [
        'new_report'   => 'ra-status-stamp--info',
        'under_review' => 'ra-status-stamp--pending',
        'mitigated'    => 'ra-status-stamp--info',
        'resolved'     => 'ra-status-stamp--compliant',
        'closed'       => 'ra-status-stamp--compliant',
    ];
    return $m[$s] ?? 'ra-status-stamp--pending';
}
function ra_severity_class(string $s): string {
    $m = [
        'Critical' => 'ra-sev--critical',
        'High'     => 'ra-sev--high',
        'Medium'   => 'ra-sev--medium',
        'Low'      => 'ra-sev--low',
    ];
    return $m[$s] ?? 'ra-sev--low';
}

function ra_compliance_review_label(string $s): string {
    $map = [
        'pending_verification' => 'Pending Verification',
        'verified' => 'Verified',
        'requires_followup' => 'Requires Follow-up',
    ];
    return $map[$s] ?? ucfirst(str_replace('_', ' ', $s));
}

function ra_monitoring_status_label(string $s): string {
    $map = [
        'pending_review' => 'Pending Review',
        'monitoring' => 'Monitoring',
        'verified' => 'Verified',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];
    return $map[$s] ?? ucfirst(str_replace('_', ' ', $s));
}

// ------------------------------------------------------------------
// Shared summary data
// ------------------------------------------------------------------
$summary = [
    'total'        => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0", 0),
    'critical'     => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND severity = 'Critical'", 0),
    'high'         => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND severity = 'High'", 0),
    'medium'       => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND severity = 'Medium'", 0),
    'low'          => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND severity = 'Low'", 0),
    'open'         => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND status = 'new_report'", 0),
    'under_review' => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND status = 'under_review'", 0),
    'monitoring'   => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND status = 'mitigated'", 0),
    'resolved'     => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND status = 'resolved'", 0),
];

// ------------------------------------------------------------------
// Analytics data
// ------------------------------------------------------------------
$analytics = [
    'by_source' => ra_all($db, "SELECT r.risk_type, COUNT(*) as cnt, r.severity FROM lc_risks r WHERE r.archived = 0 GROUP BY r.risk_type, r.severity ORDER BY cnt DESC"),
    'by_month'  => ra_all($db, "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM lc_risks WHERE archived = 0 GROUP BY month ORDER BY month ASC LIMIT 12"),
    'by_status' => ra_all($db, "SELECT status, COUNT(*) as cnt FROM lc_risks WHERE archived = 0 GROUP BY status"),
    'by_monitoring' => ra_all($db, "SELECT monitoring_status, COUNT(*) as cnt FROM lc_risks WHERE archived = 0 GROUP BY monitoring_status"),
    'by_compliance' => ra_all($db, "SELECT compliance_review, COUNT(*) as cnt FROM lc_risks WHERE archived = 0 GROUP BY compliance_review"),
    'avg_mitigation_days' => (int) ra_value($db, "SELECT AVG(DATEDIFF(updated_at, created_at)) FROM lc_risks WHERE archived = 0 AND status IN ('mitigated', 'resolved', 'closed') AND updated_at > created_at", 0),
    'overdue_monitoring' => (int) ra_value($db, "SELECT COUNT(*) FROM lc_risks WHERE archived = 0 AND monitoring_status IN ('pending_review', 'monitoring') AND last_reviewed < DATE_SUB(NOW(), INTERVAL 30 DAY)", 0),
];

// Risk register with filters, search, sorting, and date range
$validStatuses    = ['All', 'Open', 'Under Review', 'Monitoring', 'Resolved', 'Closed'];
$filterStatus     = in_array($_GET['status'] ?? '', $validStatuses, true) ? $_GET['status'] : 'All';
$validSeverities  = ['All', 'Critical', 'High', 'Medium', 'Low'];
$filterSeverity   = in_array($_GET['severity'] ?? '', $validSeverities, true) ? $_GET['severity'] : 'All';
$validSources     = ['All', 'Workforce Management', 'Performance Management', 'Employee Relations', 'Payroll', 'Recruitment & Onboarding', 'Employee Management', 'Legal & Compliance', 'Employee Portal', 'Admin Portal', 'Time & Attendance', 'Engagement Management', 'Clinic', 'Exit Management', 'Learning & Development', 'Employee Documents', 'Incident Reporting'];
$filterSource     = in_array($_GET['source'] ?? '', $validSources, true) ? $_GET['source'] : 'All';
$searchQuery      = trim($_GET['search'] ?? '');
$sortField        = in_array($_GET['sort'] ?? '', ['severity', 'department', 'status', 'updated'], true) ? $_GET['sort'] : 'updated';
$sortOrder        = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc'], true) ? strtoupper($_GET['order']) : 'DESC';
$dateFrom         = trim($_GET['date_from'] ?? '');
$dateTo           = trim($_GET['date_to'] ?? '');

$where = ['r.archived = 0'];
$params = [];

if ($filterStatus !== 'All') {
    $map = ['Open' => 'new_report', 'Under Review' => 'under_review', 'Monitoring' => 'mitigated', 'Resolved' => 'resolved', 'Closed' => 'closed'];
    $where[] = 'r.status = :status';
    $params[':status'] = $map[$filterStatus] ?? $filterStatus;
}
if ($filterSeverity !== 'All') {
    $where[] = 'r.severity = :severity';
    $params[':severity'] = $filterSeverity;
}
if ($filterSource !== 'All') {
    $sourceMap = [
        'Workforce Management' => 'Workforce Risk', 'Performance Management' => 'Performance Risk',
        'Employee Relations' => 'Behavioral Risk', 'Payroll' => 'Payroll Risk',
        'Recruitment & Onboarding' => 'Recruitment & Onboarding Risk',
        'Employee Management' => 'Employee Management Risk', 'Legal & Compliance' => 'Compliance Risk',
        'Employee Portal' => 'Employee Portal Risk', 'Admin Portal' => 'Administrative Risk',
        'Time & Attendance' => 'Attendance Risk', 'Engagement Management' => 'Engagement Risk',
        'Clinic' => 'Health Risk', 'Exit Management' => 'Exit Risk',
        'Learning & Development' => 'Training Risk', 'Employee Documents' => 'Documentation Risk',
        'Incident Reporting' => 'Safety Risk',
    ];
    $filterSourceCategory = $sourceMap[$filterSource] ?? $filterSource;
}
if ($searchQuery !== '') {
    $where[] = "(CONCAT(e.first_name, ' ', e.last_name) LIKE :search OR r.risk_type LIKE :search OR COALESCE(d.department_name, '') LIKE :search OR r.description LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}
if ($dateFrom !== '') {
    $where[] = 'DATE(r.created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(r.created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$orderByMap = [
    'employee'  => "CONCAT(e.first_name, ' ', e.last_name)",
    'severity'  => "FIELD(r.severity, 'Critical', 'High', 'Medium', 'Low')",
    'department' => "COALESCE(d.department_name, '')",
    'status'    => "r.status",
    'updated'   => "r.updated_at",
];
$orderBy = $orderByMap[$sortField] ?? "r.updated_at";
$whereSql = 'WHERE ' . implode(' AND ', $where);

$register = ra_all($db, "
    SELECT r.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name, COALESCE(d.department_name, 'N/A') AS department, COALESCE(p.position_name, 'N/A') AS position, e.email, e.employee_code AS employee_no
    FROM lc_risks r
    LEFT JOIN em_employees e ON r.employee_id = e.employee_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    $whereSql
    ORDER BY $orderBy $sortOrder, r.id DESC
", $params);

if (!empty($filterSourceCategory)) {
    $register = array_values(array_filter($register, function($r) use ($filterSourceCategory) {
        return ra_source_of($r['risk_type']) === $filterSourceCategory;
    }));
}

// Risk source list (for sidebar display)
$riskSources = [
    'Workforce Management' => 'Workforce Risk',
    'Performance Management' => 'Performance Risk',
    'Employee Relations' => 'Behavioral Risk',
    'Payroll' => 'Payroll Risk',
    'Recruitment & Onboarding' => 'Recruitment & Onboarding Risk',
    'Employee Management' => 'Employee Management Risk',
    'Legal & Compliance' => 'Compliance Risk',
    'Employee Portal' => 'Employee Portal Risk',
    'Admin Portal' => 'Administrative Risk',
    'Time & Attendance' => 'Attendance Risk',
    'Engagement Management' => 'Engagement Risk',
    'Clinic' => 'Health Risk',
    'Exit Management' => 'Exit Risk',
    'Learning & Development' => 'Training Risk',
    'Employee Documents' => 'Documentation Risk',
    'Incident Reporting' => 'Safety Risk',
];
?>

<section class="ra-module">
  <?php
  function ra_summary_url(string $status, string $search, string $source, string $severity, string $dateFrom, string $dateTo): string {
      $params = ['page' => 'risk-register'];
      if ($search !== '') $params['search'] = $search;
      if ($source !== '' && $source !== 'All') $params['source'] = $source;
      if ($severity !== '' && $severity !== 'All') $params['severity'] = $severity;
      if ($status !== '' && $status !== 'All') $params['status'] = $status;
      if ($dateFrom !== '') $params['date_from'] = $dateFrom;
      if ($dateTo !== '') $params['date_to'] = $dateTo;
      return '?' . http_build_query($params);
  }
  ?>
   <div class="ra-summary-bar">
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'All', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div class="ra-summary-icon blue"><i class="bi bi-shield-exclamation"></i></div>
       <div>
         <div class="ra-summary-value"><?= number_format($summary['total']) ?></div>
         <div class="ra-summary-label">Total Risks</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Critical', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div class="ra-summary-icon red"><i class="bi bi-exclamation-octagon"></i></div>
       <div>
         <div class="ra-summary-value"><?= number_format($summary['critical']) ?></div>
         <div class="ra-summary-label">Critical</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'High', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div class="ra-summary-icon amber"><i class="bi bi-exclamation-triangle"></i></div>
       <div>
         <div class="ra-summary-value"><?= number_format($summary['high']) ?></div>
         <div class="ra-summary-label">High</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Medium', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div class="ra-summary-icon blue"><i class="bi bi-exclamation-circle"></i></div>
       <div>
         <div class="ra-summary-value"><?= number_format($summary['medium']) ?></div>
         <div class="ra-summary-label">Medium</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Low', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div class="ra-summary-icon green"><i class="bi bi-shield-check"></i></div>
       <div>
         <div class="ra-summary-value"><?= number_format($summary['low']) ?></div>
         <div class="ra-summary-label">Low</div>
       </div>
     </a>
    </div>

   <div class="ra-row ra-row--full">
     <div class="ra-col-main">
       <div class="ra-card">
           <div class="ra-card-head">
             <h3><i class="bi bi-journal-text"></i> Risk Register</h3>
             <div class="ra-card-head__actions">
               <button type="button" class="ra-btn" id="runDetectorBtn" title="Run Risk Detector">
                 <i class="bi bi-radar"></i> <span class="ra-side-panel__toggle-label">Run Detector</span>
               </button>
               <button type="button" class="ra-btn" id="analyticsToggle" title="View Risk Analytics">
                 <i class="bi bi-bar-chart-line"></i> <span class="ra-side-panel__toggle-label">Analytics</span>
               </button>
               <button type="button" class="ra-btn" id="riskSourcesToggle" title="Toggle Risk Sources">
                 <i class="bi bi-funnel"></i> <span class="ra-side-panel__toggle-label">Risk Sources</span>
               </button>
             </div>
           </div>

        <div class="ra-table-wrap" style="margin-top:14px;">
          <?php if (empty($register)): ?>
            <div class="ra-empty"><i class="bi bi-emoji-smile"></i> No risks match the current filters.</div>
          <?php else: ?>
          <table class="ra-table" id="riskRegisterTable">
            <thead>
              <tr>
                <th><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => 'department', 'order' => ($sortField === 'department' && $sortOrder === 'ASC') ? 'desc' : 'asc']))) ?>" class="ra-sort-link <?= $sortField === 'department' ? strtolower($sortOrder) : '' ?>">Source Department</a></th>
                <th>Risk Category</th>
                <th>Risk Description</th>
                <th><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => 'severity', 'order' => ($sortField === 'severity' && $sortOrder === 'ASC') ? 'desc' : 'asc']))) ?>" class="ra-sort-link <?= $sortField === 'severity' ? strtolower($sortOrder) : '' ?>">Risk Level</a></th>
                <th>Assigned To</th>
                <th><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => ($sortField === 'status' && $sortOrder === 'ASC') ? 'desc' : 'asc']))) ?>" class="ra-sort-link <?= $sortField === 'status' ? strtolower($sortOrder) : '' ?>">Status</a></th>
                <th><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => 'updated', 'order' => ($sortField === 'updated' && $sortOrder === 'ASC') ? 'desc' : 'asc']))) ?>" class="ra-sort-link <?= $sortField === 'updated' ? strtolower($sortOrder) : '' ?>">Last Updated</a></th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
               <?php foreach ($register as $r):
                 $src = ra_source_of($r['risk_type']);
                 $rec = $recommendations[$src] ?? '—';
                 $responsible = ra_responsible($src);
                 $deptName = ra_source_dept($r['risk_type']);
                 $riskSubject = 'Urgent: ' . ($r['risk_type'] ?? 'Documentation Risk') . ' - ' . ($r['severity'] ?? 'High') . ' Severity - Immediate Action Required';
                 $riskBody = "Dear {$deptName} Team,\n\n";
                 $riskBody .= "This is an urgent reminder regarding a critical compliance risk that requires your immediate attention.\n\n";
                 $riskBody .= "Risk Details:\n";
                 $riskBody .= "- Category: " . ($r['risk_type'] ?? 'Documentation Risk') . "\n";
                 $riskBody .= "- Severity: " . ($r['severity'] ?? 'High') . "\n";
                 $riskBody .= "- Description: " . ($r['description'] ?? 'Medical Certificate expiring within 30 days (expiry: 2026-09-25)') . "\n";
                 $riskBody .= "- Mitigation Plan: " . ($r['mitigation_plan'] ?? 'Please complete the missing documents immediately') . "\n\n";
                 $riskBody .= "Please treat this matter with urgency and take the necessary steps to resolve this risk promptly. Your immediate action is required to ensure compliance and avoid any potential issues.\n\n";
                 $riskBody .= "Best regards,\nHR Department";
               ?>
                  <tr>
                    <td data-label="Source Department"><span class="ra-type-badge"><?= htmlspecialchars(ra_source_dept($r['risk_type']), ENT_QUOTES) ?></span></td>
                   <td data-label="Risk Category"><div class="ra-emp-name"><?= htmlspecialchars($src, ENT_QUOTES) ?></div></td>
                   <td data-label="Risk Description"><div class="ra-emp-name" style="max-width:220px; white-space:normal; line-height:1.35;"><?= htmlspecialchars($r['description'] ?? $r['risk_type'], ENT_QUOTES) ?></div></td>
                   <td data-label="Risk Level"><span class="ra-sev <?= ra_severity_class($r['severity']) ?>"><?= htmlspecialchars($r['severity'], ENT_QUOTES) ?></span></td>
                   <td data-label="Assigned To"><span class="ra-emp-no"><?= htmlspecialchars($responsible, ENT_QUOTES) ?></span></td>
                   <td data-label="Status"><span class="ra-status-stamp <?= ra_status_class($r['status']) ?>"><?= htmlspecialchars(ra_status_label($r['status']), ENT_QUOTES) ?></span></td>
                   <td data-label="Last Updated"><span class="ra-emp-no"><?= !empty($r['updated_at']) ? date('M d, Y g:i A', strtotime($r['updated_at'])) : '—' ?></span></td>
                   <td data-label="Action"><a href="<?= htmlspecialchars('http://127.0.0.1/hrms-capstone/modules/compliance/index.php?page=notification-compose&mode=reply&notification_id=0&to_recipient_dept=' . urlencode($deptName) . '&subject=' . urlencode($riskSubject) . '&body=' . urlencode($riskBody), ENT_QUOTES) ?>" class="ra-btn" title="Send Reminder"><i class="bi bi-envelope"></i> Send Reminder</a></td>
                 </tr>
               <?php endforeach; ?>
             </tbody>
           </table>
           <?php endif; ?>
         </div>
       </div>
     </div>
   </div>
</section>

<!-- Analytics Modal -->
<div class="ra-modal-backdrop" id="analyticsModalBackdrop"></div>
<div class="ra-modal ra-modal--wide" id="analyticsModal" aria-hidden="true">
  <div class="ra-modal__head">
    <h3><i class="bi bi-bar-chart-line"></i> Risk Analytics</h3>
    <button type="button" class="ra-modal__close" id="analyticsModalClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="ra-modal__body">
    <div class="ra-analytics-section">
      <div class="ra-analytics-header">
        <h3><i class="bi bi-bar-chart-line"></i> Risk Analytics</h3>
        <span class="ra-analytics-meta">Based on current filtered data</span>
      </div>
      <div class="ra-analytics-grid">
        <div class="ra-analytics-card">
          <div class="ra-analytics-card-head">
            <h4><i class="bi bi-pie-chart"></i> Severity Distribution</h4>
          </div>
          <div class="ra-analytics-body">
            <?php
            $maxSev = max($summary['critical'], $summary['high'], $summary['medium'], $summary['low'], 1);
            $sevItems = [
                ['label' => 'Critical', 'count' => $summary['critical'], 'class' => 'ra-sev--critical'],
                ['label' => 'High', 'count' => $summary['high'], 'class' => 'ra-sev--high'],
                ['label' => 'Medium', 'count' => $summary['medium'], 'class' => 'ra-sev--medium'],
                ['label' => 'Low', 'count' => $summary['low'], 'class' => 'ra-sev--low'],
            ];
            foreach ($sevItems as $item):
            ?>
            <div class="ra-severity-row">
              <div class="ra-severity-label">
                <span class="ra-sev <?= $item['class'] ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
                <span class="ra-severity-count"><?= number_format($item['count']) ?></span>
              </div>
              <div class="ra-severity-bar-track">
                <div class="ra-severity-bar-fill <?= $item['class'] ?>" style="width: <?= number_format(($item['count'] / $maxSev) * 100, 1) ?>%;"></div>
              </div>
              <div class="ra-severity-pct"><?= $summary['total'] > 0 ? number_format(($item['count'] / $summary['total']) * 100, 1) : 0 ?>%</div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="ra-analytics-card">
          <div class="ra-analytics-card-head">
            <h4><i class="bi bi-calendar3"></i> Monthly Trend</h4>
            <span class="ra-card-meta">Last 12 months</span>
          </div>
          <div class="ra-analytics-body">
            <?php if (!empty($analytics['by_month'])):
              $maxMonth = max(array_column($analytics['by_month'], 'cnt'));
              $maxMonth = max($maxMonth, 1);
            ?>
            <div class="ra-trend-chart">
              <?php foreach ($analytics['by_month'] as $m):
                $pct = ($m['cnt'] / $maxMonth) * 100;
                $monthLabel = date('M Y', strtotime($m['month'] . '-01'));
              ?>
              <div class="ra-trend-col">
                <div class="ra-trend-bar-wrap">
                  <div class="ra-trend-bar" style="height: <?= number_format($pct, 1) ?>%;" title="<?= number_format($m['cnt']) ?> risks"></div>
                </div>
                <div class="ra-trend-label"><?= $monthLabel ?></div>
                <div class="ra-trend-value"><?= $m['cnt'] ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="ra-empty-state"><i class="bi bi-calendar-x"></i><div class="es-title">No trend data available</div></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ra-analytics-card">
          <div class="ra-analytics-card-head">
            <h4><i class="bi bi-diagram-3"></i> Top Risk Sources</h4>
          </div>
          <div class="ra-analytics-body">
            <?php
            $sourceCounts = [];
            foreach ($register as $r) {
                $src = ra_source_of($r['risk_type']);
                $sourceCounts[$src] = ($sourceCounts[$src] ?? 0) + 1;
            }
            arsort($sourceCounts);
            $topSources = array_slice($sourceCounts, 0, 8, true);
            $maxSource = !empty($topSources) ? max($topSources) : 1;
            foreach ($topSources as $src => $cnt):
            ?>
            <div class="ra-source-analytics-row">
              <div class="ra-source-analytics-name"><?= htmlspecialchars($src, ENT_QUOTES) ?></div>
              <div class="ra-source-analytics-bar-track">
                <div class="ra-source-analytics-bar" style="width: <?= number_format(($cnt / $maxSource) * 100, 1) ?>%;"></div>
              </div>
              <div class="ra-source-analytics-count"><?= number_format($cnt) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="ra-analytics-card">
          <div class="ra-analytics-card-head">
            <h4><i class="bi bi-check2-square"></i> Compliance Review</h4>
            <span class="ra-card-meta">Current review status</span>
          </div>
          <div class="ra-analytics-body">
            <?php foreach ($analytics['by_compliance'] as $c): ?>
            <div class="ra-compliance-row">
              <span class="ra-compliance-label"><?= htmlspecialchars(ra_compliance_review_label($c['compliance_review']), ENT_QUOTES) ?></span>
              <div class="ra-compliance-bar-track">
                <div class="ra-compliance-bar-fill" style="width: <?= $summary['total'] > 0 ? number_format(($c['cnt'] / $summary['total']) * 100, 1) : 0 ?>%;"></div>
              </div>
              <span class="ra-compliance-value"><?= number_format($c['cnt']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="ra-side-panel-backdrop" id="riskSourcesBackdrop"></div>

<aside class="ra-side-panel" id="riskSourcesPanel" aria-hidden="true">
  <div class="ra-side-panel__head">
    <h3><i class="bi bi-diagram-3"></i> Risk Sources</h3>
    <div class="ra-side-panel__actions">
      <?php if ($filterSource !== 'All'): ?>
        <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['source' => 'All']))) ?>" class="ra-btn" title="Clear source filter">Clear</a>
      <?php endif; ?>
      <button type="button" class="ra-btn ra-side-panel__close" id="riskSourcesClose" aria-label="Close filters">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>
  <div class="ra-side-panel__body">
    <div class="ra-sources-grid">
      <?php
        $grouped = [];
        foreach ($register as $r) {
            $src = ra_source_of($r['risk_type']);
            $dept = ra_source_dept($r['risk_type']);
            $key = $dept . '|||' . $src;
            if (!isset($grouped[$key])) $grouped[$key] = ['dept' => $dept, 'source' => $src, 'total' => 0, 'items' => []];
            $grouped[$key]['total']++;
            $item = $r['risk_type'];
            if (!in_array($item, $grouped[$key]['items'], true)) $grouped[$key]['items'][] = $item;
        }
        $orderedDepts = ['Recruitment & Onboarding','Employee Management','Payroll','Legal & Compliance','Employee Portal','Admin Portal','Workforce Management','Time & Attendance','Performance Management','Engagement Management','Clinic','Exit Management','Learning & Development','Employee Documents','Incident Reporting','Employee Relations'];
        usort($grouped, function($a, $b) use ($orderedDepts) {
            $ia = array_search($a['dept'], $orderedDepts);
            $ib = array_search($b['dept'], $orderedDepts);
            return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
        });
      ?>
      <?php foreach ($grouped as $g): ?>
      <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['source' => $g['dept']]))) ?>" class="ra-source-card<?= $filterSource === $g['dept'] ? ' active' : '' ?>">
        <div class="ra-source-header">
          <i class="bi bi-building"></i>
          <div class="ra-source-meta">
            <div class="ra-source-title"><?= htmlspecialchars($g['dept']) ?></div>
            <div class="ra-source-sub"><?= htmlspecialchars($g['source']) ?></div>
          </div>
          <span class="ra-source-count"><?= number_format($g['total']) ?></span>
        </div>
        <div class="ra-source-tags">
          <?php foreach ($g['items'] as $item): ?>
            <span class="ra-source-tag"><?= htmlspecialchars($item) ?></span>
          <?php endforeach; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</aside>

<script>
(function() {
  var panel = document.getElementById('riskSourcesPanel');
  var toggleBtn = document.getElementById('riskSourcesToggle');
  var closeBtn = document.getElementById('riskSourcesClose');
  var backdrop = document.getElementById('riskSourcesBackdrop');

  function getFilterState() {
    var params = new URLSearchParams(window.location.search);
    return {
      status: params.get('status') || 'All',
      source: params.get('source') || 'All',
      severity: params.get('severity') || 'All',
      search: params.get('search') || '',
      date_from: params.get('date_from') || '',
      date_to: params.get('date_to') || ''
    };
  }

  function resetToInitial() {
    var url = new URL(window.location.href);
    var params = url.searchParams;
    var initial = new URLSearchParams();
    if (params.has('page')) initial.set('page', params.get('page'));
    url.search = initial.toString();
    window.location.href = url.toString();
  }

  if (panel && toggleBtn && closeBtn) {
    function openPanel() { panel.classList.add('is-open'); panel.setAttribute('aria-hidden', 'false'); if (backdrop) backdrop.classList.add('is-open'); }
    function closePanel() { panel.classList.remove('is-open'); panel.setAttribute('aria-hidden', 'true'); if (backdrop) backdrop.classList.remove('is-open'); }
    toggleBtn.addEventListener('click', function() { panel.classList.contains('is-open') ? closePanel() : openPanel(); });
    closeBtn.addEventListener('click', closePanel);
    if (backdrop) {
      backdrop.addEventListener('click', function() {
        closePanel();
        resetToInitial();
      });
    }
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) closePanel(); });
  }

  var summaryItems = document.querySelectorAll('.ra-summary-item');
  summaryItems.forEach(function(item) {
    item.addEventListener('click', function(e) {
      var href = item.getAttribute('href');
      if (!href) return;
      var url = new URL(href, window.location.origin);
      var params = new URLSearchParams(url.search);
      var clickedSeverity = params.get('severity') || 'All';
      var current = getFilterState();
      if (clickedSeverity !== 'All' && clickedSeverity === current.severity) {
        e.preventDefault();
        params.set('severity', 'All');
        url.search = params.toString();
        window.location.href = url.toString();
      }
    });
  });

  var sourceCards = document.querySelectorAll('.ra-source-card');
  sourceCards.forEach(function(card) {
    card.addEventListener('click', function(e) {
      var href = card.getAttribute('href');
      if (!href) return;
      var url = new URL(href, window.location.origin);
      var params = new URLSearchParams(url.search);
      var clickedSource = params.get('source') || 'All';
      var current = getFilterState();
      if (clickedSource !== 'All' && clickedSource === current.source) {
        e.preventDefault();
        params.set('source', 'All');
        url.search = params.toString();
        window.location.href = url.toString();
      }
    });
  });
})();

(function() {
  var runBtn = document.getElementById('runDetectorBtn');
  if (!runBtn) return;

  runBtn.addEventListener('click', function() {
    var originalHtml = runBtn.innerHTML;
    runBtn.disabled = true;
    runBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Detecting...';

    fetch('./lib/ajax/run_risk_detector.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ scope: 'all_departments' })
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
      if (result && result.success) {
        var message = result.message || 'Risk detection completed.';
        if (result.total_new === 0) {
          message = 'No new risks detected. All clear!';
        }
        alert('Success\n\n' + message);
        if (typeof refreshRiskRegister === 'function') {
          refreshRiskRegister();
        }
      } else {
        alert('Risk detection failed: ' + (result && result.message ? result.message : 'Unknown error'));
      }
    })
    .catch(function(error) {
      alert('Risk detection failed: ' + error.message);
    })
    .finally(function() {
      runBtn.disabled = false;
      runBtn.innerHTML = originalHtml;
    });
  });
})();

(function() {
  var analyticsToggle = document.getElementById('analyticsToggle');
  var analyticsModal = document.getElementById('analyticsModal');
  var analyticsBackdrop = document.getElementById('analyticsModalBackdrop');
  var analyticsClose = document.getElementById('analyticsModalClose');

  function openAnalyticsModal() {
    analyticsModal.classList.add('is-open');
    analyticsModal.setAttribute('aria-hidden', 'false');
    if (analyticsBackdrop) analyticsBackdrop.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeAnalyticsModal() {
    analyticsModal.classList.remove('is-open');
    analyticsModal.setAttribute('aria-hidden', 'true');
    if (analyticsBackdrop) analyticsBackdrop.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  if (analyticsToggle) {
    analyticsToggle.addEventListener('click', function() {
      if (analyticsModal.classList.contains('is-open')) {
        closeAnalyticsModal();
      } else {
        openAnalyticsModal();
      }
    });
  }

  if (analyticsClose) {
    analyticsClose.addEventListener('click', closeAnalyticsModal);
  }

  if (analyticsBackdrop) {
    analyticsBackdrop.addEventListener('click', closeAnalyticsModal);
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && analyticsModal && analyticsModal.classList.contains('is-open')) {
      closeAnalyticsModal();
    }
  });
})();
</script>

<style>
/* ============ Risk Assessment (ra-) layout : matches ch- style from exit_records ============ */
.ra-module { padding: 4px 2px 24px; }
/* Summary bar */
.ra-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.ra-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:160px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.ra-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.ra-summary-item.active { border-color:var(--info-blue,#3b82c4); background:rgba(59,130,196,.04); box-shadow:0 0 0 2px rgba(59,130,196,.15); }
.ra-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.ra-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.ra-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ra-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.ra-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.ra-summary-icon.purple { background:rgba(124,58,237,.10); color:#5b21b6; }
.ra-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.ra-summary-label { font-size:0.8rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

.ra-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.ra-col-main { min-width:0; }
.ra-col-side { width:380px; flex-shrink:0; }
.ra-row--full { display:block; }
.ra-row--full .ra-col-main { width:100%; }

.ra-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.ra-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.ra-card-head__actions { display:inline-flex; align-items:center; gap:8px; }
.ra-card-head h3 { margin:0; font-size:0.94rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ra-side-panel { box-sizing:border-box; position:fixed; top:0; right:0; width:min(320px,85vw); height:100vh; height:100dvh; background:var(--card-bg,#fff); overflow-y:auto; overflow-x:hidden; overscroll-behavior:contain; transform:translateX(100%); transition:transform .35s cubic-bezier(0.22,0.61,0.36,1),visibility 0s linear .35s; z-index:1000; visibility:hidden; pointer-events:none; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; box-shadow:-4px 0 12px rgba(13,27,46,.08); }
.ra-side-panel.is-open { transform:translateX(0); visibility:visible; pointer-events:auto; transition:transform .35s cubic-bezier(0.22,0.61,0.36,1),visibility 0s; }
.ra-side-panel__head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 18px; border-bottom:1px solid var(--border,#e4e8ee); position:sticky; top:0; background:var(--card-bg,#fff); z-index:1; }
.ra-side-panel__head h3 { margin:0; font-size:0.94rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ra-side-panel__actions { display:flex; align-items:center; gap:8px; }
.ra-side-panel__body { padding:14px 18px 18px; }
.ra-side-panel__close { padding:4px 8px; }
.ra-side-panel__toggle-label { display:inline; }
.ra-side-panel-backdrop { position:fixed; inset:0; background:rgba(14,28,51,.45); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); z-index:999; opacity:0; visibility:hidden; transition:opacity .35s ease, visibility 0s linear .35s; }
.ra-side-panel-backdrop.is-open { opacity:1; visibility:visible; transition:opacity .35s ease, visibility 0s; }
@media (max-width:768px){ .ra-side-panel { width:min(360px,90vw); } }
@media (max-width:480px){ .ra-side-panel { width:100vw; } }
.ra-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.82rem; }
.ra-table-wrap { overflow:auto; max-height:520px; }
.ra-table { width:100%; border-collapse:collapse; font-size:0.8rem; color:#1b2430; margin-left:0; margin-right:0; }
.ra-table th { text-align:left; padding:10px 12px; font-size:0.74rem; font-weight:700; text-transform:uppercase; color:#8b93a1; border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; vertical-align:middle; }
.ra-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:middle; color:#1b2430; min-width:0; }
.ra-table tr:last-child td { border-bottom:none; }

.ra-page-head { margin-bottom:10px; }
.ra-sort-link { color:inherit; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
.ra-sort-link:hover { color:var(--info-blue,#3b82c4); text-decoration:none; }
.ra-sort-link::after { content:' ↕'; opacity:0.4; font-size:0.6rem; }
.ra-sort-link.asc::after { content:' ↑'; opacity:1; }
.ra-sort-link.desc::after { content:' ↓'; opacity:1; }

.ra-sources-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px; }
.ra-source-card { display:flex; flex-direction:column; gap:6px; padding:10px 12px; border-radius:10px; background:#fff; border:1px solid var(--border,#e4e8ee); text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.ra-source-card:hover { border-color:var(--info-blue,#3b82c4); box-shadow:0 2px 8px rgba(59,130,196,.08); transform:translateY(-1px); }
.ra-source-card.active { border-color:var(--info-blue,#3b82c4); background:rgba(59,130,196,.04); box-shadow:0 0 0 2px rgba(59,130,196,.15); }
.ra-source-header { display:flex; align-items:center; gap:8px; }
.ra-source-header i { color:var(--info-blue,#3b82c4); }
.ra-source-meta { flex:1; min-width:0; }
.ra-source-title { font-weight:700; font-size:0.86rem; color:#2b3340; }
.ra-source-sub { font-size:0.76rem; opacity:0.85; color:#8b93a1; }
.ra-source-count { font-size:0.76rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(59,130,196,.08); color:#1c5a8a; white-space:nowrap; }
.ra-source-tags { display:flex; flex-wrap:wrap; gap:4px; }
.ra-source-tag { font-size:0.7rem; font-weight:600; padding:2px 8px; border-radius:999px; background:rgba(59,130,196,.06); color:#1c5a8a; border:1px solid rgba(59,130,196,.12); }

.modal-body .ra-card { box-shadow:none; }
.ra-emp-name { font-weight:600; color:#2b3340; font-size:0.76rem; }
.ra-emp-no { font-size:0.72rem; color:#8b93a1; }
.ra-type-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; white-space:nowrap; background:rgba(59,130,196,.08); color:#1c5a8a; border:1px solid rgba(59,130,196,.16); }
.ra-type-count { font-size:0.76rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(59,130,196,.08); color:#1c5a8a; white-space:nowrap; }

.ra-status-stamp { display:inline-block; font-size:0.7rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.ra-status-stamp--compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.ra-status-stamp--info { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ra-status-stamp--pending { background:rgba(217,154,43,.14); color:#a86b13; }
.ra-status-stamp--overdue { background:rgba(214,72,74,.12); color:#a3272a; }

/* Severity badges */
.ra-sev { display:inline-block; font-size:0.7rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.ra-sev--critical { background:rgba(214,72,74,.14); color:#a3272a; }
.ra-sev--high { background:rgba(217,154,43,.16); color:#a86b13; }
.ra-sev--medium { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ra-sev--low { background:rgba(47,158,110,.12); color:#1f7a52; }

/* Analytics Section */
.ra-analytics-section {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border, #e4e8ee);
  border-radius: 14px;
  padding: 14px;
  box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
  margin-bottom: 14px;
}
.ra-analytics-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  flex-wrap: wrap;
  gap: 6px;
}
.ra-analytics-header h3 {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--text-900, #1b2430);
  display: flex;
  align-items: center;
  gap: 6px;
}
.ra-analytics-meta {
  font-size: 0.68rem;
  color: var(--text-400, #8b93a1);
  font-weight: 600;
}
.ra-analytics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.ra-analytics-card {
  background: var(--bg-page, #f3f5f9);
  border: 1px solid var(--border, #e4e8ee);
  border-radius: 12px;
  padding: 10px;
}
.ra-analytics-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  gap: 6px;
}
.ra-analytics-card-head h4 {
  margin: 0;
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--text-900, #1b2430);
  display: flex;
  align-items: center;
  gap: 5px;
}
.ra-analytics-body {
  padding: 2px 0;
}

/* Severity analytics */
.ra-severity-row {
  display: grid;
  grid-template-columns: 100px 1fr 45px;
  gap: 8px;
  align-items: center;
  margin-bottom: 7px;
}
.ra-severity-row:last-child { margin-bottom: 0; }
.ra-severity-label {
  display: flex;
  align-items: center;
  gap: 5px;
}
.ra-severity-count {
  font-size: 0.68rem;
  color: var(--text-700, #3b4252);
  font-weight: 600;
}
.ra-severity-bar-track {
  height: 14px;
  background: var(--card-bg, #fff);
  border-radius: 4px;
  overflow: hidden;
}
.ra-severity-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.5s ease;
}
.ra-severity-bar-fill.ra-sev--critical { background: rgba(214,72,74,.8); }
.ra-severity-bar-fill.ra-sev--high { background: rgba(217,154,43,.8); }
.ra-severity-bar-fill.ra-sev--medium { background: rgba(59,130,196,.8); }
.ra-severity-bar-fill.ra-sev--low { background: rgba(47,158,110,.8); }
.ra-severity-pct {
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--text-700, #3b4252);
  text-align: right;
}

/* Trend chart */
.ra-trend-chart {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 120px;
  padding: 8px 0;
}
.ra-trend-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 0;
}
.ra-trend-bar-wrap {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.ra-trend-bar {
  width: 100%;
  max-width: 28px;
  background: linear-gradient(to top, var(--info-blue, #3b82c4), rgba(59,130,196,.6));
  border-radius: 3px 3px 0 0;
  transition: height 0.5s ease;
  min-height: 2px;
}
.ra-trend-label {
  font-size: 0.55rem;
  color: var(--text-400, #8b93a1);
  font-weight: 600;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
.ra-trend-value {
  font-size: 0.62rem;
  font-weight: 700;
  color: var(--text-900, #1b2430);
}

/* Source analytics */
.ra-source-analytics-row {
  display: grid;
  grid-template-columns: 120px 1fr 35px;
  gap: 8px;
  align-items: center;
  margin-bottom: 6px;
}
.ra-source-analytics-row:last-child { margin-bottom: 0; }
.ra-source-analytics-name {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-900, #1b2430);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ra-source-analytics-bar-track {
  height: 14px;
  background: var(--card-bg, #fff);
  border-radius: 3px;
  overflow: hidden;
}
.ra-source-analytics-bar {
  height: 100%;
  background: var(--info-blue, #3b82c4);
  border-radius: 3px;
  transition: width 0.5s ease;
}
.ra-source-analytics-count {
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--text-700, #3b4252);
  text-align: right;
}

/* Monitoring overview */
.ra-monitoring-overview {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}
.ra-monitoring-stat {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: var(--card-bg, #fff);
  border-radius: 8px;
}
.ra-monitoring-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  background: rgba(59,130,196,.1);
  color: var(--info-blue, #3b82c4);
  flex-shrink: 0;
}
.ra-monitoring-info { flex: 1; }
.ra-monitoring-value {
  font-size: 0.9rem;
  font-weight: 800;
  color: var(--text-900, #1b2430);
  line-height: 1;
}
.ra-monitoring-label {
  font-size: 0.62rem;
  color: var(--text-400, #8b93a1);
  font-weight: 600;
  margin-top: 2px;
}
.ra-monitoring-alert {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  background: rgba(214,72,74,.08);
  border: 1px solid rgba(214,72,74,.2);
  border-radius: 8px;
  color: #a3272a;
  font-size: 0.72rem;
  font-weight: 600;
}
.ra-monitoring-alert--active {
  animation: raPulse 2s infinite;
}
@keyframes raPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Compliance review */
.ra-compliance-row {
  display: grid;
  grid-template-columns: 120px 1fr 40px;
  gap: 8px;
  align-items: center;
  margin-bottom: 7px;
}
.ra-compliance-row:last-child { margin-bottom: 0; }
.ra-compliance-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-700, #3b4252);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ra-compliance-bar-track {
  height: 14px;
  background: var(--card-bg, #fff);
  border-radius: 3px;
  overflow: hidden;
}
.ra-compliance-bar-fill {
  height: 100%;
  background: var(--info-blue, #3b82c4);
  border-radius: 3px;
  transition: width 0.5s ease;
}
.ra-compliance-value {
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--text-700, #3b4252);
  text-align: right;
}

@media (max-width: 1100px) {
  .ra-analytics-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .ra-trend-label { display: none; }
  .ra-severity-row { grid-template-columns: 1fr; gap: 3px; }
  .ra-severity-bar-track { height: 12px; }
  .ra-source-analytics-row { grid-template-columns: 1fr; gap: 3px; }
}

/* Filter form */
.ra-filter-form { display:flex; flex-direction:column; gap:10px; }
.ra-filter-row { display:grid; gap:10px; }
.ra-filter-row--primary { grid-template-columns:1fr; }
.ra-filter-row--primary > .ra-field--ddl-actions { grid-template-columns:1fr 1fr auto; }
.ra-field { display:flex; flex-direction:column; gap:4px; min-width:0; }
.ra-field--grow { flex:1; min-width:0; }
.ra-field--full { grid-column:1 / -1; }
.ra-field--ddl-actions { display:grid; grid-template-columns:1fr 1fr auto; gap:10px; grid-column:1 / -1; align-items:end; min-width:0; }
.ra-field label { font-size:0.72rem; font-weight:600; color:var(--text-700,#3b4252); margin-bottom:3px; display:block; text-transform:none; letter-spacing:normal; }
.ra-field input, .ra-field select { width:100%; padding:7px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.82rem; color:var(--text-900,#1b2430); background:var(--card-bg,#fff); transition:border-color 150ms ease, box-shadow 150ms ease; box-sizing:border-box; }
.ra-field input:focus, .ra-field select:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,0.12); }
.ra-btn { font-size:0.68rem; font-weight:600; padding:8px 12px; border-radius:6px; border:1px solid var(--border,#e4e8ee); background:var(--card-bg,#fff); color:var(--text-600,#5b6472); cursor:pointer; text-decoration:none; white-space:nowrap; transition:all 150ms ease; display:inline-flex; align-items:center; gap:4px; }
.ra-btn:hover { background:var(--bg-page,#f3f5f9); border-color:var(--border-strong,#d3d9e2); }
.ra-btn.primary { background:var(--info-blue,#3b82c4); color:#fff; border-color:var(--info-blue,#3b82c4); }
.ra-btn.primary:hover { background:#1c5a8a; border-color:#1c5a8a; color:#fff; }

/* Analytics Modal */
.ra-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(14,28,51,.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 2000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0s linear 0.3s;
}
.ra-modal-backdrop.is-open {
  opacity: 1;
  visibility: visible;
  transition: opacity 0.3s ease, visibility 0s;
}
.ra-modal {
  position: fixed;
  top: 50%;
  left: calc(50% + var(--sidebar-width) / 2);
  transform: translate(-50%, -50%) scale(0.95);
  width: min(1100px, 92vw);
  max-height: 92vh;
  background: var(--card-bg, #fff);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(13,27,46,.2);
  z-index: 2001;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s cubic-bezier(0.22, 0.61, 0.36, 1);
}
.ra-modal.is-open {
  opacity: 1;
  visibility: visible;
  transform: translate(-50%, -50%) scale(1);
}
.ra-modal--wide {
  width: min(1100px, 92vw);
}
.ra-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid var(--border, #e4e8ee);
  position: sticky;
  top: 0;
  background: var(--card-bg, #fff);
  z-index: 1;
  border-radius: 16px 16px 0 0;
}
.ra-modal__head h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-900, #1b2430);
  display: flex;
  align-items: center;
  gap: 8px;
}
.ra-modal__close {
  padding: 6px 10px;
  border: none;
  background: transparent;
  color: var(--text-400, #8b93a1);
  font-size: 1.1rem;
  cursor: pointer;
  border-radius: 6px;
  transition: all 150ms ease;
}
.ra-modal__close:hover {
  background: var(--bg-page, #f3f5f9);
  color: var(--text-900, #1b2430);
}
.ra-modal__body {
  padding: 14px;
}

@media (max-width: 768px) {
  .ra-modal {
    width: 96vw;
    max-height: 92vh;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
  }
  .ra-modal.is-open {
    transform: translate(-50%, -50%) scale(1);
  }

  .ra-summary-bar {
    gap: 8px;
  }
  .ra-summary-item {
    flex: 1 1 calc(50% - 8px);
    min-width: 140px;
    padding: 14px 16px;
    gap: 10px;
  }
  .ra-summary-icon {
    width: 38px;
    height: 38px;
    font-size: 1rem;
    border-radius: 10px;
  }
  .ra-summary-value {
    font-size: 1.05rem;
  }
  .ra-summary-label {
    font-size: 0.72rem;
  }

  .ra-card-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  .ra-card-head__actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  /* Table → card conversion */
  .ra-table-wrap {
    max-height: none;
    overflow-y: visible;
  }

  .ra-table,
  .ra-table tbody,
  .ra-table tr {
    display: block;
  }

  .ra-table thead {
    display: none;
  }

  .ra-table tbody tr {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(13, 27, 46, .06);
    transition: box-shadow 120ms ease, border-color 120ms ease;
    overflow: hidden;
  }

  .ra-table tbody tr:active {
    box-shadow: 0 2px 6px rgba(13, 27, 46, .1);
    border-color: var(--info-blue, #3b82c4);
  }

  .ra-table tbody tr td {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2px;
    padding: 7px 0;
    border-bottom: 1px solid var(--border, #e4e8ee);
    font-size: 0.82rem;
    min-width: 0;
    word-break: break-word;
    overflow-wrap: anywhere;
    max-width: 100%;
  }

  .ra-table tbody tr td:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .ra-table tbody tr td[data-label] {
    position: relative;
  }

  .ra-table tbody tr td[data-label]::before {
    content: attr(data-label);
    font-weight: 600;
    color: var(--text-700, #3b4252);
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: block;
    margin-bottom: 2px;
    word-break: break-word;
  }

  /* First cell is the primary identifier */
  .ra-table tbody tr td:first-child[data-label]::before {
    display: none;
  }
  .ra-table tbody tr td:first-child {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--text-900, #1b2430);
    padding-bottom: 10px;
    margin-bottom: 6px;
    border-bottom: 1px solid var(--border, #e4e8ee);
    text-align: left;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .ra-sev,
  .ra-status-stamp {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    display: inline-block;
  }

  .ra-btn {
    font-size: 0.76rem;
    padding: 8px 14px;
  }

  .ra-side-panel {
    width: min(360px, 90vw);
  }

  .ra-analytics-grid {
    grid-template-columns: 1fr;
  }

  .ra-severity-row {
    grid-template-columns: 1fr;
    gap: 3px;
  }
  .ra-severity-bar-track {
    height: 12px;
  }
  .ra-source-analytics-row {
    grid-template-columns: 1fr;
    gap: 3px;
  }
  .ra-compliance-row {
    grid-template-columns: 1fr;
    gap: 3px;
  }
  .ra-trend-label {
    display: none;
  }
}

@media (max-width: 480px) {
  .ra-summary-bar {
    gap: 6px;
  }
  .ra-summary-item {
    flex: 1 1 100%;
    min-width: 100%;
    padding: 12px 14px;
    gap: 8px;
    border-radius: 12px;
  }
  .ra-summary-icon {
    width: 34px;
    height: 34px;
    font-size: 0.88rem;
    border-radius: 8px;
  }
  .ra-summary-value {
    font-size: 1rem;
  }
  .ra-summary-label {
    font-size: 0.68rem;
  }

  .ra-card {
    padding: 14px;
    border-radius: 12px;
  }
  .ra-card-head h3 {
    font-size: 0.88rem;
  }

  .ra-table tbody tr {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 10px;
  }
  .ra-table tbody tr td {
    font-size: 0.78rem;
    padding: 6px 0;
  }
  .ra-table tbody tr td:first-child {
    font-size: 0.88rem;
    padding-bottom: 8px;
    margin-bottom: 4px;
  }
  .ra-table tbody tr td[data-label]::before {
    font-size: 0.63rem;
  }
  .ra-sev,
  .ra-status-stamp {
    font-size: 0.68rem;
    padding: 3px 8px;
  }
  .ra-btn {
    font-size: 0.72rem;
    padding: 7px 12px;
  }
  .ra-emp-name {
    font-size: 0.72rem;
  }
  .ra-emp-no {
    font-size: 0.68rem;
  }
  .ra-type-badge {
    font-size: 0.68rem;
    padding: 2px 8px;
  }

  .ra-side-panel {
    width: 100vw;
  }
}

@media (max-width: 360px) {
  .ra-module {
    padding: 2px 0 16px;
  }
  .ra-summary-item {
    padding: 10px 12px;
  }
  .ra-summary-icon {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }
  .ra-summary-value {
    font-size: 0.92rem;
  }
  .ra-card {
    padding: 10px;
    border-radius: 10px;
  }
  .ra-table tbody tr {
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 8px;
  }
  .ra-table tbody tr td {
    font-size: 0.72rem;
    padding: 5px 0;
  }
  .ra-table tbody tr td:first-child {
    font-size: 0.82rem;
  }
}
</style>
