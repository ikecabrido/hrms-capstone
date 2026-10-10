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

$database = new Database();

if ($database->hasConnectionError()) {
    throw new RuntimeException('Database connection unavailable.');
}

$db = $database->getConnection();

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
       <div>
         <div class="ra-summary-value"><?= number_format($summary['total']) ?></div>
         <div class="ra-summary-label">Total Risks</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Critical', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div>
         <div class="ra-summary-value"><?= number_format($summary['critical']) ?></div>
         <div class="ra-summary-label">Critical</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'High', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div>
         <div class="ra-summary-value"><?= number_format($summary['high']) ?></div>
         <div class="ra-summary-label">High</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Medium', $dateFrom ?? '', $dateTo ?? '') ?>">
       <div>
         <div class="ra-summary-value"><?= number_format($summary['medium']) ?></div>
         <div class="ra-summary-label">Medium</div>
       </div>
     </a>
     <a class="ra-summary-item" href="<?= ra_summary_url('All', $searchQuery ?? '', $filterSource ?? 'All', 'Low', $dateFrom ?? '', $dateTo ?? '') ?>">
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
              <h3>Risk Register</h3>
             <div class="ra-card-head__actions">
                <button type="button" class="ra-btn" id="runDetectorBtn" title="Run Risk Detector">
                  <span class="ra-side-panel__toggle-label">Run Detector</span>
                </button>
                <button type="button" class="ra-btn" id="analyticsToggle" title="View Risk Analytics">
                  <span class="ra-side-panel__toggle-label">Analytics</span>
                </button>
                <button type="button" class="ra-btn" id="riskSourcesToggle" title="Toggle Risk Sources">
                  <span class="ra-side-panel__toggle-label">Risk Sources</span>
                </button>
             </div>
           </div>

        <div class="ra-table-wrap" style="margin-top:14px;">
          <?php if (empty($register)): ?>
            <div class="ra-empty">No risks match the current filters.</div>
          <?php else: ?>
          <table class="ra-table" id="riskRegisterTable">
            <thead>
              <tr>
                <th><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['sort' => 'department', 'order' => ($sortField === 'department' && $sortOrder === 'ASC') ? 'desc' : 'asc']))) ?>" class="ra-sort-link <?= $sortField === 'department' ? strtolower($sortOrder) : '' ?>">Source Department</a></th>
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
                    <td data-label="Risk Description"><div class="ra-emp-name" style="max-width:220px; white-space:normal; line-height:1.35;"><?= htmlspecialchars($r['description'] ?? $r['risk_type'], ENT_QUOTES) ?></div></td>
                   <td data-label="Risk Level"><span class="ra-sev <?= ra_severity_class($r['severity']) ?>"><?= htmlspecialchars($r['severity'], ENT_QUOTES) ?></span></td>
                   <td data-label="Assigned To"><span class="ra-emp-no"><?= htmlspecialchars($responsible, ENT_QUOTES) ?></span></td>
                   <td data-label="Status"><span class="ra-status-stamp <?= ra_status_class($r['status']) ?>"><?= htmlspecialchars(ra_status_label($r['status']), ENT_QUOTES) ?></span></td>
                   <td data-label="Last Updated"><span class="ra-emp-no"><?= !empty($r['updated_at']) ? date('M d, Y g:i A', strtotime($r['updated_at'])) : '—' ?></span></td>
                   <td data-label="Action"><a href="<?= htmlspecialchars('/modules/compliance/index.php?page=notification-compose&mode=reply&notification_id=0&to_recipient_dept=' . urlencode($deptName) . '&subject=' . urlencode($riskSubject) . '&body=' . urlencode($riskBody), ENT_QUOTES) ?>" class="ra-btn" title="Send Reminder">Send Reminder</a></td>
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
     <h3>Risk Analytics</h3>
     <button type="button" class="ra-modal__close" id="analyticsModalClose" aria-label="Close"><span style="font-size:0.72rem;font-weight:600;color:#3b4252;">Close</span></button>
   </div>
   <div class="ra-modal__body">
     <div class="ra-analytics-section">
         <div class="ra-analytics-header">
           <h3>Risk Analytics</h3>
           <span class="ra-analytics-meta">Based on current filtered data</span>
         </div>
       <div class="ra-analytics-grid">
         <div class="ra-analytics-card">
           <div class="ra-analytics-card-head">
             <h4>Severity Distribution</h4>
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
                  <span><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></span>
                  <span class="ra-severity-count"><?= number_format($item['count']) ?></span>
                </div>
                <div class="ra-severity-bar-track">
                  <div class="ra-severity-bar-fill" style="width: <?= number_format(($item['count'] / $maxSev) * 100, 1) ?>%;"></div>
                </div>
                <div class="ra-severity-pct"><?= $summary['total'] > 0 ? number_format(($item['count'] / $summary['total']) * 100, 1) : 0 ?>%</div>
              </div>
             <?php endforeach; ?>
           </div>
         </div>

         <div class="ra-analytics-card">
           <div class="ra-analytics-card-head">
             <h4>Monthly Trend</h4>
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
                   <div class="ra-trend-bar" style="height: <?= number_format($pct, 1) ?>%;"></div>
                 </div>
                 <div class="ra-trend-label"><?= $monthLabel ?></div>
                 <div class="ra-trend-value"><?= $m['cnt'] ?></div>
               </div>
               <?php endforeach; ?>
             </div>
             <?php else: ?>
              <div class="ra-empty-state"><div class="es-title">No trend data available</div></div>
             <?php endif; ?>
           </div>
         </div>

         <div class="ra-analytics-card">
           <div class="ra-analytics-card-head">
             <h4>Top Risk Sources</h4>
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
             <h4>Compliance Review</h4>
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
      <h3>Risk Sources</h3>
      <div class="ra-side-panel__actions">
        <?php if ($filterSource !== 'All'): ?>
          <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['source' => 'All']))) ?>" class="ra-btn" title="Clear source filter">Clear</a>
        <?php endif; ?>
         <button type="button" class="rr-btn ra-side-panel__close" id="riskSourcesClose" aria-label="Close filters"><span class="ra-close-text">Close</span></button>
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

 <script src="/modules/compliance/js/risk-register-extracted.js"></script>

   <div class="ra-loading-overlay" id="raLoadingOverlay">
    <div class="ra-loading-card">

      <div class="ra-loading-icon" id="raLoadingIcon" aria-hidden="true"></div>

      <div class="ra-risk-scanner">

        <div class="ra-scanner-grid"></div>

        <div class="ra-scanner-ring ra-ring-outer"></div>
        <div class="ra-scanner-ring ra-ring-middle"></div>
        <div class="ra-scanner-ring ra-ring-inner"></div>

        <div class="ra-scanner-sweep"></div>

        <div class="ra-scanner-core">
          <span class="ra-core-pulse"></span>
          <span class="ra-core-icon">⌁</span>
        </div>

        <span class="ra-scan-dot dot-1"></span>
        <span class="ra-scan-dot dot-2"></span>
        <span class="ra-scan-dot dot-3"></span>
        <span class="ra-scan-dot dot-4"></span>

      </div>

      <div class="ra-loading-title" id="raLoadingTitle">
        Risk Detection in Progress
      </div>

      <div class="ra-loading-dept" id="raLoadingDept">
        Initializing scan...
      </div>

      <div class="ra-loading-depts" id="raLoadingDepts"></div>

      <div class="ra-loading-footer" id="raLoadingFooter">
        Please wait while we scan all HR modules
      </div>

      <button
        type="button"
        class="ra-loading-close"
        id="raLoadingClose"
        style="display:none;">
        Close
      </button>

    </div>
  </div>

  <link rel="stylesheet" href="/modules/compliance/css/risk-register-extracted-01.css">

<link rel="stylesheet" href="/modules/compliance/css/risk-register-extracted-02.css">

<link rel="stylesheet" href="/modules/compliance/css/risk-register-extracted-03.css">

<link rel="stylesheet" href="/modules/compliance/css/risk-register-extracted-04.css">

<link rel="stylesheet" id="ra-modal-centering-fix" href="/modules/compliance/css/risk-register-extracted-05.css">


<link rel="stylesheet" id="ra-modal-viewport-fix" href="/modules/compliance/css/risk-register-extracted-06.css">


<link rel="stylesheet" id="ra-modal-height-adjustment" href="/modules/compliance/css/risk-register-extracted-07.css">

