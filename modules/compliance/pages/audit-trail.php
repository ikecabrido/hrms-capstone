<?php
// =============================================================================
// Audit & Reporting – Report Center
// =============================================================================
$pageTitle   = 'Audit & Reporting';
$activeGroup = 'Reporting';
$activePage  = 'audit-trail';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function ar_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function ar_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
function ar_status_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['completed', 'closed', 'resolved', 'compliant', 'approved', 'verified', 'paid', 'archived'], true)) return 'ir-status-stamp--compliant';
    if (in_array($s, ['scheduled', 'pending', 'logged', 'under review', 'submitted', 'draft', 'open', 'not generated', 'ready'], true)) return 'ir-status-stamp--info';
    if (in_array($s, ['in progress', 'under investigation', 'processing', 'sent to payroll'], true)) return 'ir-status-stamp--pending';
    if (in_array($s, ['overdue', 'critical', 'rejected', 'cancelled', 'dismissed', 'expired', 'returned'], true)) return 'ir-status-stamp--overdue';
    return 'ir-status-stamp--pending';
}
function ar_label(?string $s): string {
    return htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$s)));
}
function ar_report_label(?string $key, array $reportCategories): string {
    foreach ($reportCategories as $cat) {
        foreach ($cat['reports'] as $rpt) {
            if ($rpt['key'] === $key) return $rpt['label'];
        }
    }
    return '';
}

$reportCategories = [];
$reportCategories = [
    'Employee Reports' => [
        'icon' => 'bi-people',
        'reports' => [
            ['key' => 'employee_master_list', 'label' => 'Employee Master List', 'table' => 'em_employees', 'table_label' => 'Employees', 'export' => 'export_report'],
            ['key' => 'employee_compliance', 'label' => 'Employee Compliance Status', 'table' => 'lc_compliance_summary', 'table_label' => 'Compliance Records', 'export' => 'export_report'],
            ['key' => 'employee_documents', 'label' => 'Employee Documents', 'table' => 'em_documents', 'table_label' => 'Employee Documents', 'export' => 'export_report'],
            ['key' => 'training_certifications', 'label' => 'Training & Certifications', 'table' => 'pm_employee_training', 'table_label' => 'Trainings', 'export' => 'export_report'],
            ['key' => 'policy_acknowledgement', 'label' => 'Policy Acknowledgement', 'table' => 'lc_acknowledgment_log', 'table_label' => 'Acknowledgement Log', 'export' => 'export_report'],
            ['key' => 'leave_summary', 'label' => 'Leave Summary', 'table' => 'ta_leave_requests', 'table_label' => 'Leave Requests', 'export' => 'export_report'],
        ]
    ],
    'Government Reports' => [
        'icon' => 'bi-bank2',
        'reports' => [
            ['key' => 'sss_compliance', 'label' => 'SSS Compliance', 'table' => 'lc_sss_contributions', 'table_label' => 'SSS Contributions', 'export' => 'export_sss_report'],
            ['key' => 'philhealth_compliance', 'label' => 'PhilHealth Compliance', 'table' => 'lc_philhealth_contributions', 'table_label' => 'PhilHealth Contributions', 'export' => 'export_philhealth_report'],
            ['key' => 'pagibig_compliance', 'label' => 'Pag-IBIG Compliance', 'table' => 'lc_pagibig_contributions', 'table_label' => 'Pag-IBIG Contributions', 'export' => 'export_pagibig_report'],
            ['key' => 'bir_compliance', 'label' => 'BIR Compliance', 'table' => 'lc_bir_contributions', 'table_label' => 'BIR Contributions', 'export' => 'export_government_report'],
            ['key' => 'government_submission', 'label' => 'Government Submission Status', 'table' => 'em_government_ids', 'table_label' => 'Government Validations', 'export' => 'export_government_report'],
        ]
    ],
    'Legal & External Cases' => [
        'icon' => 'bi bi-shield-exclamation',
        'reports' => [
            ['key' => 'legal_case_summary', 'label' => 'External Case Summary', 'table' => 'lc_legal_cases', 'table_label' => 'Legal Cases', 'export' => 'export_legal_case'],
            ['key' => 'legal_case_status', 'label' => 'Case Status Report', 'table' => 'lc_legal_cases', 'table_label' => 'Legal Cases', 'export' => 'export_legal_case'],
            ['key' => 'legal_case_agency', 'label' => 'Agency Report', 'table' => 'lc_legal_cases', 'table_label' => 'Legal Cases', 'export' => 'export_legal_case'],
        ]
    ],
    'Legal Reports' => [
        'icon' => 'bi-shield-exclamation',
        'reports' => [
            ['key' => 'incident_reports', 'label' => 'Incident Reports', 'table' => 'lc_incident_report', 'table_label' => 'Incident Reports', 'export' => 'export_incident'],
            ['key' => 'disciplinary_actions', 'label' => 'Disciplinary Actions', 'table' => 'lc_complaint_decision_history', 'table_label' => 'Disciplinary Actions', 'export' => 'export_report'],
            ['key' => 'complaints', 'label' => 'Complaints', 'table' => 'lc_complaints', 'table_label' => 'Complaints', 'export' => 'export_report'],
            ['key' => 'risk_assessment', 'label' => 'Risk Assessment', 'table' => 'lc_risks', 'table_label' => 'Risks', 'export' => 'export_risk'],
        ]
    ],
    'Recruitment & Exit Reports' => [
        'icon' => 'bi-person-plus',
        'reports' => [
            ['key' => 'recruitment_summary', 'label' => 'Recruitment Summary', 'table' => 'rao_applications', 'table_label' => 'Recruitment', 'export' => 'export_report'],
            ['key' => 'new_employees', 'label' => 'New Employees', 'table' => 'rao_onboarding', 'table_label' => 'Employees', 'export' => 'export_report'],
            ['key' => 'contract_renewals', 'label' => 'Contract Renewals', 'table' => 'em_contract_renewals', 'table_label' => 'Contracts', 'export' => 'export_contract_compliance'],
            ['key' => 'exit_clearance', 'label' => 'Exit Clearance', 'table' => 'exit_resignations', 'table_label' => 'Exit Clearance', 'export' => 'export_report'],
            ['key' => 'job_posting_approval', 'label' => 'Job Posting Approval', 'table' => 'rao_jobs', 'table_label' => 'Job Posting Requests', 'export' => 'export_report'],
        ]
    ],
];

// ------------------------------------------------------------------
// Load counts for each report
// ------------------------------------------------------------------
$reportCounts = [];
foreach ($reportCategories as $catName => $cat) {
    foreach ($cat['reports'] as $rpt) {
        $table = $rpt['table'];
        try {
            $count = (int) ar_value($db, "SELECT COUNT(*) FROM `$table`", 0);
        } catch (Throwable $e) {
            $count = 0;
        }
        $reportCounts[$rpt['key']] = $count;
    }
}

// ------------------------------------------------------------------
// Scheduled Reports
// ------------------------------------------------------------------
$scheduledReports = ar_all($db, "SELECT * FROM lc_report_schedule WHERE active = 1 ORDER BY next_run ASC");

// ------------------------------------------------------------------
// Submitted Reports
// ------------------------------------------------------------------
$submittedReports = ar_all($db, "SELECT r.report_code, r.report_date, r.status, r.file_format, r.created_at, r.period_label, r.report_key, r.report_key AS report_title, r.generated_by, r.submitted_by FROM lc_generated_reports r WHERE r.status = 'Submitted' ORDER BY r.created_at DESC LIMIT 50");

// ------------------------------------------------------------------
// Audit Trail (report history)
// ------------------------------------------------------------------
$auditTrail = ar_all($db, "SELECT h.*, u.full_name AS user_name FROM lc_report_history h LEFT JOIN em_employees u ON u.employee_id = h.user_id ORDER BY h.created_at DESC LIMIT 50");

// ------------------------------------------------------------------
// Active tab / filter
// ------------------------------------------------------------------
$activeTab = $_GET['tab'] ?? 'ready_reports';
$filterCategory = $_GET['category'] ?? '';

$totalReports = array_sum($reportCounts);
$totalScheduled = count($scheduledReports);
$totalSubmitted = count($submittedReports);
$totalAudit = count($auditTrail);
?>

<section class="ir-module">
   <?php if (!empty($flash)): ?>
      <?php [$fc, $fm] = explode('|', $flash, 2); ?>
      <div class="ir-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

    <div class="ir-summary-bar">
       <a class="ir-summary-item <?= $activeTab === 'ready_reports' && $filterCategory === '' ? 'ir-summary-active' : '' ?>" href="?page=audit-trail&tab=ready_reports&category=">
         <div>
           <div class="ir-summary-value"><?= number_format($totalReports) ?></div>
           <div class="ir-summary-label">Ready Reports</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $activeTab === 'scheduled' ? 'ir-summary-active' : '' ?>" href="?page=audit-trail&tab=scheduled&category=">
         <div>
           <div class="ir-summary-value"><?= number_format($totalScheduled) ?></div>
           <div class="ir-summary-label">Scheduled Reports</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $activeTab === 'submitted' ? 'ir-summary-active' : '' ?>" href="?page=audit-trail&tab=submitted&category=">
         <div>
           <div class="ir-summary-value"><?= number_format($totalSubmitted) ?></div>
           <div class="ir-summary-label">Submitted Reports</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $activeTab === 'audit_trail' ? 'ir-summary-active' : '' ?>" href="?page=audit-trail&tab=audit_trail&category=">
         <div>
           <div class="ir-summary-value"><?= number_format($totalAudit) ?></div>
           <div class="ir-summary-label">Audit Trail</div>
         </div>
       </a>
    </div>

   <div class="ir-row">
      <div class="ir-col ir-col-main">

        <!-- ============ READY REPORTS ============ -->
        <div id="arPanel-ready_reports" class="ir-panel" style="display:<?= $activeTab === 'ready_reports' ? 'block' : 'none' ?>;">
          <?php if ($filterCategory !== '' && isset($reportCategories[$filterCategory])): ?>
            <?php $cat = $reportCategories[$filterCategory]; ?>
            <div class="ir-card">
              <div class="ir-card-head">
                <h3><?= htmlspecialchars($filterCategory) ?></h3>
                <button type="button" class="ir-btn" onclick="arFilterByType('')"><i class="bi bi-x"></i> Clear Filter</button>
              </div>
              <div class="ir-table-wrap">
                <table class="ir-table">
                  <thead>
                    <tr>
                      <th>Report</th>
                      <th>Source</th>
                      <th>Records</th>
                      <th class="ir-action-cell" style="text-align:right;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cat['reports'] as $rpt): ?>
                    <tr>
                      <td>
                         <div class="ir-cnum"><?= htmlspecialchars($rpt['label']) ?></div>
                         <div class="ir-emp-no"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rpt['key']))) ?></div>
                      </td>
                      <td><span class="ir-type-badge"><?= htmlspecialchars($rpt['table_label'] ?? $rpt['table']) ?></span></td>
                      <td>
                        <span class="ir-status-stamp ir-status-stamp--info"><?= number_format($reportCounts[$rpt['key']] ?? 0) ?> records</span>
                      </td>
                      <td class="ir-action-cell" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arPreview('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Preview"><i class="bi bi-eye"></i></button>
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arGeneratePDF('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Generate PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arSendToDirectress('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['label']) ?>')" title="Send to Directress"><i class="bi bi-send"></i></button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php elseif ($filterCategory !== '' && !isset($reportCategories[$filterCategory])): ?>
            <div class="ir-card"><div class="ir-empty"><i class="bi bi-emoji-smile"></i> Category not found.</div></div>
          <?php else: ?>
            <?php foreach ($reportCategories as $catName => $cat): ?>
            <div class="ir-card">
              <div class="ir-card-head">
                <h3><?= htmlspecialchars($catName) ?></h3>
              </div>
              <div class="ir-table-wrap">
                <table class="ir-table">
                  <thead>
                    <tr>
                      <th>Report</th>
                      <th>Source</th>
                      <th>Records</th>
                      <th class="ir-action-cell" style="text-align:right;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cat['reports'] as $rpt): ?>
                    <tr>
                      <td>
                         <div class="ir-cnum"><?= htmlspecialchars($rpt['label']) ?></div>
                         <div class="ir-emp-no"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rpt['key']))) ?></div>
                      </td>
                      <td><span class="ir-type-badge"><?= htmlspecialchars($rpt['table_label'] ?? $rpt['table']) ?></span></td>
                      <td>
                        <span class="ir-status-stamp ir-status-stamp--info"><?= number_format($reportCounts[$rpt['key']] ?? 0) ?> records</span>
                      </td>
                      <td class="ir-action-cell" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arPreview('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Preview"><i class="bi bi-eye"></i></button>
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arGeneratePDF('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Generate PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arSendToDirectress('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['label']) ?>')" title="Send to Directress"><i class="bi bi-send"></i></button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- ============ SCHEDULED REPORTS ============ -->
        <div id="arPanel-scheduled" class="ir-panel" style="display:<?= $activeTab === 'scheduled' ? 'block' : 'none' ?>;">
          <div class="ir-card">
            <div class="ir-card-head">
              <h3><i class="bi bi-calendar-event"></i> Scheduled Reports</h3>
              <button type="button" class="ir-btn primary" onclick="openScheduleModal()"><i class="bi bi-plus-lg"></i> Schedule Report</button>
            </div>
            <div class="ir-table-wrap">
              <table class="ir-table">
                <thead>
                  <tr>
                    <th>Report</th>
                    <th>Frequency</th>
                    <th>Next Due</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th class="ir-action-cell" style="text-align:right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($scheduledReports)) : ?>
                    <tr><td colspan="6"><div class="ir-empty"><i class="bi bi-emoji-smile"></i> No scheduled reports.</div></td></tr>
                  <?php else : ?>
                    <?php foreach ($scheduledReports as $sr): ?>
                    <tr>
                      <td>
                        <div class="ir-cnum"><?= htmlspecialchars(ar_report_label($sr['report_key'] ?? '', $reportCategories) ?: ($sr['report_name'] ?? $sr['report_key'] ?? '—')) ?></div>
                        <div class="ir-emp-no"><?= htmlspecialchars($sr['module'] ?? '') ?></div>
                      </td>
                      <td><span class="ir-type-badge"><?= htmlspecialchars($sr['frequency'] ?? 'Monthly') ?></span></td>
                      <td><span class="ir-emp-no"><?= !empty($sr['next_run']) ? date('M d, Y', strtotime($sr['next_run'])) : '—' ?></span></td>
                      <td><div class="ir-emp-name"><?= htmlspecialchars($sr['recipient_email'] ?? 'Directress') ?></div></td>
                      <td>
                        <?php if (!empty($sr['active'])) : ?>
                          <span class="ir-status-stamp ir-status-stamp--info">Active</span>
                        <?php else : ?>
                          <span class="ir-status-stamp ir-status-stamp--overdue">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td class="ir-action-cell" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arSendNow('<?= htmlspecialchars($sr['report_key'] ?? '') ?>')" title="Send Now"><i class="bi bi-send"></i> Send</button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============ SUBMITTED REPORTS ============ -->
        <div id="arPanel-submitted" class="ir-panel" style="display:<?= $activeTab === 'submitted' ? 'block' : 'none' ?>;">
          <div class="ir-card">
            <div class="ir-card-head">
              <h3><i class="bi bi-file-earmark-bar-graph"></i> Submitted Reports</h3>
            </div>
            <div class="ir-table-wrap">
              <table class="ir-table">
                <thead>
                  <tr>
                    <th>Report</th>
                    <th>Generated</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th class="ir-action-cell" style="text-align:right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($submittedReports)) : ?>
                    <tr><td colspan="5"><div class="ir-empty"><i class="bi bi-emoji-smile"></i> No submitted reports yet.</div></td></tr>
                  <?php else : ?>
                    <?php foreach ($submittedReports as $r): ?>
                    <?php
                      try {
                        $label = ar_report_label($r['report_key'] ?? '', $reportCategories) ?: ($r['report_title'] ?? '—');
                        $code = $r['report_code'] ?? '';
                        $created = !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : '—';
                        $submitted = !empty($r['report_date']) ? date('M d, Y', strtotime($r['report_date'])) : '—';
                        $st = strtolower($r['status'] ?? '');
                        if (in_array($st, ['approved', 'archived'], true)) $sc = 'compliant';
                        elseif (in_array($st, ['submitted', 'pending approval'], true)) $sc = 'pending';
                        elseif (in_array($st, ['returned'], true)) $sc = 'overdue';
                        else $sc = 'info';
                        $statusLabel = ar_label($r['status'] ?? 'Draft');
                      } catch (Throwable $e) {
                        $label = 'ERROR: ' . $e->getMessage();
                        $code = $r['report_code'] ?? '';
                        $created = '—';
                        $submitted = '—';
                        $sc = 'info';
                        $statusLabel = 'Error';
                      }
                    ?>
                    <tr>
                      <td>
                        <div class="ir-cnum"><?= htmlspecialchars($label) ?></div>
                        <div class="ir-emp-no"><?= htmlspecialchars($code) ?></div>
                      </td>
                      <td><span class="ir-emp-no"><?= $created ?></span></td>
                      <td><span class="ir-emp-no"><?= $submitted ?></span></td>
                      <td><span class="ir-status-stamp ir-status-stamp--<?= $sc ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                      <td class="ir-action-cell" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arPreview('<?= htmlspecialchars($r['report_key'] ?? '') ?>', 'export_report')" title="Preview"><i class="bi bi-eye"></i></button>
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arGeneratePDF('<?= htmlspecialchars($r['report_key'] ?? '') ?>', 'export_report')" title="PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============ AUDIT TRAIL ============ -->
        <div id="arPanel-audit_trail" class="ir-panel" style="display:<?= $activeTab === 'audit_trail' ? 'block' : 'none' ?>;">
          <div class="ir-card">
            <div class="ir-card-head">
              <h3><i class="bi bi-clock-history"></i> Audit Trail</h3>
            </div>
            <div class="ir-table-wrap">
              <table class="ir-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Report</th>
                    <th>Details</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($auditTrail)) : ?>
                    <tr><td colspan="5"><div class="ir-empty"><i class="bi bi-emoji-smile"></i> No audit trail records.</div></td></tr>
                  <?php else : ?>
                    <?php foreach ($auditTrail as $t): ?>
                    <tr>
                      <td><span class="ir-emp-no"><?= !empty($t['created_at']) ? date('M d, Y H:i', strtotime($t['created_at'])) : '—' ?></span></td>
                      <td><div class="ir-emp-name"><?= htmlspecialchars($t['user_name'] ?? ('User #' . $t['user_id'])) ?></div></td>
                      <td>
                        <?php
                          $act = strtolower($t['action'] ?? '');
                          if (str_contains($act, 'generate') || str_contains($act, 'export')) $ac = 'info';
                          elseif (str_contains($act, 'send') || str_contains($act, 'submit')) $ac = 'pending';
                          elseif (str_contains($act, 'approve')) $ac = 'compliant';
                          elseif (str_contains($act, 'reject') || str_contains($act, 'return')) $ac = 'overdue';
                          else $ac = 'info';
                        ?>
                        <span class="ir-status-stamp ir-status-stamp--<?= $ac ?>"><?= htmlspecialchars(ar_label($t['action'] ?? 'Action')) ?></span>
                      </td>
                      <td><div class="ir-emp-name"><?= htmlspecialchars(ar_report_label($t['report_key'] ?? '', $reportCategories) ?: ($t['report_key'] ?? '—')) ?></div></td>
                      <td><span class="ir-emp-no"><?= htmlspecialchars(mb_strimwidth($t['details'] ?? '', 0, 60, '…')) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

     </div><!-- /.ir-col-main -->

     <div class="ir-col ir-col-side">

       <div class="ir-card">
         <div class="ir-card-head">
           <h3>Urgent Actions</h3>
           <span class="ir-stamp ir-stamp-overdue" style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:999px;white-space:nowrap;"><?= number_format($totalSubmitted) ?></span>
         </div>
         <div class="ir-reminder-list ir-reminder-list--compact">
           <?php if ($totalSubmitted > 0): ?>
             <?php
               $urgentSubmitted = array_slice($submittedReports, 0, 5);
             ?>
             <?php foreach ($urgentSubmitted as $r): ?>
               <?php
                 $label = ar_report_label($r['report_key'] ?? '', $reportCategories) ?: ($r['report_title'] ?? '—');
                 $code = $r['report_code'] ?? '';
                 $statusLabel = ar_label($r['status'] ?? 'Draft');
               ?>
               <div class="ir-reminder-row">
                 <div class="ir-reminder-text">
                   <strong><?= htmlspecialchars($statusLabel, ENT_QUOTES) ?></strong>
                   <span><?= htmlspecialchars($label, ENT_QUOTES) ?> — <?= htmlspecialchars($code, ENT_QUOTES) ?></span>
                   <span class="ir-reminder-step"><?= htmlspecialchars(ar_label($r['status'] ?? ''), ENT_QUOTES) ?></span>
                 </div>
                  <div class="ir-reminder-actions">
                     <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="arPreview('<?= htmlspecialchars($r['report_key'] ?? '') ?>', 'export_report')">View</button>
                  </div>
               </div>
             <?php endforeach; ?>
           <?php else: ?>
              <div class="ir-empty">No urgent actions required.</div>
            <?php endif; ?>
         </div>
       </div>

     </div>
   </div>
 </section>


 <!-- ============ SCHEDULE MODAL ============ -->
 <div class="ir-modal-backdrop" id="arScheduleModal">
   <div class="ir-modal">
     <div class="ir-modal-head">
       <h3><i class="bi bi-calendar-plus"></i> Schedule Report</h3>
       <button type="button" class="ir-modal-close" onclick="closeModal('arScheduleModal')"><i class="bi bi-x-lg"></i></button>
     </div>
     <div class="ir-modal-body">
       <form id="arScheduleForm">
         <div class="ir-field">
           <label>Report</label>
           <select name="report_key" required>
             <option value="">Select report</option>
             <?php foreach ($reportCategories as $catName => $cat): ?>
               <optgroup label="<?= htmlspecialchars($catName) ?>">
                 <?php foreach ($cat['reports'] as $rpt): ?>
                   <option value="<?= htmlspecialchars($rpt['key']) ?>"><?= htmlspecialchars($rpt['label']) ?></option>
                 <?php endforeach; ?>
               </optgroup>
             <?php endforeach; ?>
           </select>
         </div>
         <div class="ir-field">
           <label>Frequency</label>
           <select name="frequency">
             <option value="Anytime">Anytime (send now)</option>
             <option value="Daily">Daily</option>
             <option value="Weekly">Weekly</option>
             <option value="Monthly" selected>Monthly</option>
             <option value="Quarterly">Quarterly</option>
             <option value="Annual">Annual</option>
           </select>
         </div>
         <div class="ir-check">
           <input type="checkbox" name="send_now" id="arSendNow" value="1" checked>
           <label for="arSendNow">Send immediately</label>
         </div>
       </form>
     </div>
     <div class="ir-modal-foot">
       <button type="button" class="ir-btn" onclick="closeModal('arScheduleModal')">Cancel</button>
       <button type="button" class="ir-btn primary" onclick="submitScheduleForm()"><i class="bi bi-check-lg"></i> Schedule</button>
     </div>
   </div>
 </div>

 <!-- ============ TOAST ============ -->
 <div class="ir-toast" id="arToast"></div>

 <style>
 .ir-module { padding: 4px 2px 24px; }

 .ir-summary-bar { display:flex; gap:6px; margin-bottom:8px; flex-wrap:wrap; }
 .ir-summary-item { display:flex; align-items:center; gap:10px; padding:16px 18px; border-radius:8px; background:#fff; border:1px solid #dde3ea; flex:1; min-width:140px; text-decoration:none; color:inherit; transition:all .12s ease; cursor:pointer; }
 .ir-summary-item:hover { border-color:#3b82c4; box-shadow:0 1px 4px rgba(13,27,46,.05); }
 .ir-summary-active { outline:2px solid #3b82c4; outline-offset:-2px; box-shadow:none !important; }
 .ir-summary-value { font-size:1.05rem; font-weight:400; color:#1b2430; line-height:1; }
 .ir-summary-label { font-size:.72rem; font-weight:400; color:#3b4252; margin-top:2px; }

 .ir-row { display:grid; grid-template-columns:1fr 340px; gap:12px; align-items:start; font-family: Arial, sans-serif; }
 .ir-col-main { min-width:0; }
 .ir-col-side { width:340px; flex-shrink:0; }

 .ir-card { background:#fff; border:1px solid #dde3ea; border-radius:10px; padding:14px; margin-bottom:12px; font-family: Arial, sans-serif; }
 .ir-card-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
 .ir-card-head h3 { margin:0; font-size:.82rem; font-weight:700; color:#1b2430; }
 .ir-empty { padding:20px; text-align:center; color:#8b93a1; font-size:.72rem; }

 .ir-table-wrap { overflow:auto; flex:1 1 auto; }
 .ir-table { width:100%; border-collapse:collapse; font-size:.68rem; font-family: Arial, sans-serif; }
 .ir-table th { text-align:left; padding:8px 10px; font-size:.6rem; font-weight:700; text-transform:uppercase; color:#8b93a1; border-bottom:1px solid #dde3ea; background:#fafbfc; font-family: Arial, sans-serif; }
 .ir-table td { padding:8px 10px; border-bottom:1px solid #dde3ea; vertical-align:middle; font-family: Arial, sans-serif; }
 .ir-table tr:last-child td { border-bottom:none; }
 .ir-table .ir-emp-cell { width:130px; }
 .ir-table .ir-id-cell { width:110px; }

 .ir-flash { padding:8px 12px; border-radius:8px; font-size:.72rem; font-weight:600; margin-bottom:10px; }
 .ir-flash.success { background:rgba(59,130,196,.10); color:#1c5a8a; border:1px solid rgba(59,130,196,.20); }
 .ir-flash.error { background:rgba(59,130,196,.10); color:#1c5a8a; border:1px solid rgba(59,130,196,.20); }

 .ir-cnum { font-weight:600; color:#2b3340; font-size:.66rem; }
 .ir-emp-name { font-weight:600; color:#2b3340; font-size:.66rem; }
 .ir-emp-no { font-size:.6rem; color:#8b93a1; }
 .ir-type-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:.6rem; font-weight:700; background:rgba(59,130,196,.08); color:#1c5a8a; border:1px solid rgba(59,130,196,.15); white-space:nowrap; }

 .ir-status-stamp { display:inline-block; font-size:.6rem; font-weight:700; padding:2px 8px; border-radius:999px; white-space:nowrap; }
 .ir-status-stamp--compliant { background:rgba(59,130,196,.10); color:#1c5a8a; }
 .ir-status-stamp--info { background:rgba(59,130,196,.10); color:#1c5a8a; }
 .ir-status-stamp--pending { background:rgba(59,130,196,.08); color:#1c5a8a; }
 .ir-status-stamp--overdue { background:rgba(214,72,74,.12); color:#a3272a; }

 .ir-btn { font-size:.66rem; font-weight:600; padding:2px 8px; border-radius:6px; border:1px solid #dde3ea; background:#fff; color:#5b6472; cursor:pointer; text-decoration:none; white-space:nowrap; transition:background .12s ease, border-color .12s ease; display:inline-flex; align-items:center; gap:4px; }
 .ir-btn:hover { background:#f3f5f9; border-color:#c5cdd8; }
 .ir-btn.primary { background:#3b82c4; color:#fff; border-color:#3b82c4; }
 .ir-btn.primary:hover { background:#1c5a8a; border-color:#1c5a8a; }
 .ir-btn.ghost { background:transparent; border-color:transparent; color:#5b6472; }
 .ir-btn.ghost:hover { background:#f3f5f9; border-color:#c5cdd8; }

 .ir-reminder-list { display:flex; flex-direction:column; gap:8px; }
 .ir-reminder-row { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px; border:1px solid #dde3ea; border-radius:6px; background:#fff; }
 .ir-reminder-row:hover { background:#f8f9fb; }
 .ir-reminder-text { display:flex; flex-direction:column; gap:2px; min-width:0; }
 .ir-reminder-text strong { font-size:.66rem; color:#1b2430; font-weight:600; }
 .ir-reminder-text span { font-size:.6rem; color:#8b93a1; }
 .ir-reminder-step { font-size:.58rem; color:#5b6472; font-weight:600; }
 .ir-reminder-actions { display:flex; gap:6px; flex-shrink:0; }
 .ir-btn-xs { padding:2px 7px; font-size:.58rem; border-radius:5px; }
 .ir-stamp-overdue { background:rgba(59,130,196,.10); color:#1c5a8a; font-size:.58rem; padding:2px 8px; border-radius:999px; font-weight:700; }

 @media (max-width:1100px) {
   .ir-row { grid-template-columns:1fr; }
   .ir-col-side { position:static; width:auto; }
 }
 @media (max-width:768px) {
   .ir-row { grid-template-columns:1fr; }
   .ir-col-side { width:auto; }
   .ir-table thead { display:none; }
   .ir-table, .ir-table tbody, .ir-table tr, .ir-table td { display:block; width:100%; }
   .ir-table tr { background:#fff; border:1px solid #dde3ea; border-radius:10px; padding:10px; margin-bottom:10px; }
   .ir-table td { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px solid #dde3ea; text-align:right; }
   .ir-table td:last-child { border-bottom:none; padding-bottom:0; }
   .ir-table td::before { content:attr(data-label); font-size:.6rem; font-weight:700; text-transform:uppercase; color:#8b93a1; text-align:left; flex-shrink:0; }
   .ir-table .ir-id-cell, .ir-table .ir-emp-cell { width:auto; }
   .ir-btn { padding:4px 8px; font-size:.66rem; }
 }

 .ir-modal-backdrop { position:fixed; inset:0; background:rgba(13,27,46,.55); z-index:1050; display:none; align-items:center; justify-content:center; backdrop-filter:blur(2px); }
 .ir-modal-backdrop.open { display:flex; }
 .ir-modal { background:#fff; border-radius:16px; border:1px solid #e4e8ee; box-shadow:0 4px 8px rgba(13,27,46,.06), 0 16px 32px -14px rgba(13,27,46,.18); width:640px; max-width:calc(100vw - 32px); max-height:92vh; display:flex; flex-direction:column; }
 .ir-modal-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #e4e8ee; flex-shrink:0; }
 .ir-modal-head h3 { font-size:1rem; font-weight:700; color:#1b2430; margin:0; display:flex; align-items:center; gap:8px; }
 .ir-modal-close { width:32px; height:32px; border-radius:50%; border:1px solid #e4e8ee; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#5b6472; }
 .ir-modal-close:hover { background:#f3f5f9; }
 .ir-modal-body { padding:16px 18px; flex:1 1 auto; min-height:0; }
 .ir-modal-foot { display:flex; justify-content:flex-end; gap:8px; padding:12px 18px; border-top:1px solid #e4e8ee; flex-shrink:0; }
 .ir-field { display:flex; flex-direction:column; gap:2px; margin-bottom:8px; }
 .ir-field label { font-size:.72rem; font-weight:700; color:#3b4252; }
 .ir-field input, .ir-field select, .ir-field textarea { width:100%; padding:8px 10px; border:1px solid #e4e8ee; border-radius:8px; font-size:.8rem; color:#1b2430; box-sizing:border-box; font-family:inherit; }
 .ir-field input:focus, .ir-field select:focus, .ir-field textarea:focus { outline:none; border-color:#3b82c4; box-shadow:0 0 0 3px rgba(59,130,196,0.12); }
 .ir-check { display:flex; align-items:center; gap:8px; margin-top:4px; }
 .ir-check input { width:auto; }
 .ir-check label { font-size:.78rem; color:#3b4252; }

 .ir-toast { position:fixed; left:50%; bottom:28px; transform:translate(-50%,16px); background:#0d1b2e; color:#fff; font-size:13px; font-weight:600; padding:11px 18px; border-radius:999px; box-shadow:0 4px 12px rgba(0,0,0,.2); opacity:0; transition:opacity .25s ease, transform .25s ease; z-index:400; pointer-events:none; }
 .ir-toast.show { opacity:1; transform:translate(-50%,0); }
 </style>

 <script>
 document.addEventListener('DOMContentLoaded', function () {
   var forms = document.querySelectorAll('.ir-modal form');
   forms.forEach(function (f) {
     f.addEventListener('submit', function (e) { e.preventDefault(); });
   });
 });

 function arSwitchTab(tab, btn) {
   document.querySelectorAll('.ir-tab').forEach(function (t) { t.classList.remove('active'); });
   if (btn) {
     btn.classList.add('active');
   } else {
     var tabs = document.querySelectorAll('.ir-tab');
     for (var i = 0; i < tabs.length; i++) {
       if (tabs[i].textContent.trim().toLowerCase().indexOf(tab.replace('_', ' ')) !== -1 || tabs[i].getAttribute('onclick').indexOf("'" + tab + "'") !== -1) {
         tabs[i].classList.add('active');
         break;
       }
     }
   }
   document.querySelectorAll('.ir-panel').forEach(function (p) { p.style.display = 'none'; });
   var panel = document.getElementById('arPanel-' + tab);
   if (panel) panel.style.display = 'block';
   var url = new URL(window.location);
   url.searchParams.set('tab', tab);
   window.history.replaceState({}, '', url);
 }

 function arFilterByType(category) {
   arSwitchTab('ready_reports');
   var url = new URL(window.location);
   if (category) {
     url.searchParams.set('category', category);
   } else {
     url.searchParams.delete('category');
   }
   window.history.replaceState({}, '', url);
   window.location.reload();
 }

 function openModal(id) {
   var m = document.getElementById(id);
   if (m) m.classList.add('open');
 }
 function closeModal(id) {
   var m = document.getElementById(id);
   if (m) m.classList.remove('open');
 }
 function openScheduleModal() {
   document.getElementById('arScheduleForm').reset();
   document.getElementById('arSendNow').checked = true;
   openModal('arScheduleModal');
 }

 function showToast(msg, isError) {
   var t = document.getElementById('arToast');
   if (!t) return;
   t.textContent = msg;
   t.style.background = isError ? '#a3272a' : '#0d1b2e';
   t.classList.add('show');
   clearTimeout(t._timer);
   t._timer = setTimeout(function () { t.classList.remove('show'); }, 3500);
 }

 function getApiBase() {
   var path = window.location.pathname;
   var parts = path.split('/').filter(Boolean);
   var lcIndex = parts.indexOf('hrms-capstone');
   if (lcIndex !== -1) {
     return window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/';
   }
   var dirs = parts.slice(0, -2);
   return window.location.origin + '/' + dirs.join('/') + '/lib/api/';
 }

 function postForm(url, form, successMsg) {
   var fd = form instanceof FormData ? form : new FormData(form);
   fetch(url, { method: 'POST', body: fd })
     .then(function (r) { return r.json(); })
     .then(function (res) {
       console.log('API response:', res);
       if (res && res.success) {
         showToast(res.message || successMsg || 'Success');
         setTimeout(function () { window.location.reload(); }, 900);
       } else {
         var msg = (res && res.message) || 'Action failed.';
         if (res && res.debug) {
           msg += ' | user_id=' + res.debug.session_user_id + ' action=' + res.debug.action;
           console.error('API debug:', res.debug);
         }
         showToast(msg, true);
       }
     })
     .catch(function (err) { console.error('Network error:', err); showToast('Network error. Please try again.', true); });
 }

 function arPreview(key, exportType) {
   window.open(getApiBase() + 'preview_report.php?key=' + encodeURIComponent(key) + '&export=' + encodeURIComponent(exportType), '_blank');
 }
 function arGeneratePDF(key, exportType) {
   window.open(getApiBase() + exportType + '.php?key=' + encodeURIComponent(key) + '&format=pdf', '_blank');
 }
 function arSendToDirectress(key, label) {
   var fd = new FormData();
   fd.append('action', 'send_report');
   fd.append('report_key', key);
   fd.append('report_label', label);
   postForm(getApiBase() + 'audit_report_action.php', fd, 'Report sent to Directress.');
 }
 function arSendNow(key) {
   var fd = new FormData();
   fd.append('action', 'send_report');
   fd.append('report_key', key);
   fd.append('report_label', key);
   postForm(getApiBase() + 'audit_report_action.php', fd, 'Report sent to Directress.');
 }
 function submitScheduleForm() {
   var form = document.getElementById('arScheduleForm');
   if (!form.querySelector('[name="report_key"]').value) { showToast('Please select a report.', true); return; }
   var fd = new FormData(form);
   fd.append('action', 'schedule_report');
   postForm(getApiBase() + 'audit_report_action.php', fd, 'Report scheduled.');
 }
 </script>

 <?php
 /* migrated from capstone_hr_management_system/legal_compliance/pages/audits_reporting/audit_report.php */
 ?>
