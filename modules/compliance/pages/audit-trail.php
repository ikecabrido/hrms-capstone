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
    if (in_array($s, ['completed', 'closed', 'resolved', 'compliant', 'approved', 'verified', 'paid', 'archived'], true)) return 'ar-status-stamp ar-status-stamp--compliant';
    if (in_array($s, ['scheduled', 'pending', 'logged', 'under review', 'submitted', 'draft', 'open', 'not generated', 'ready'], true)) return 'ar-status-stamp ar-status-stamp--info';
    if (in_array($s, ['in progress', 'under investigation', 'processing', 'sent to payroll'], true)) return 'ar-status-stamp ar-status-stamp--pending';
    if (in_array($s, ['overdue', 'critical', 'rejected', 'cancelled', 'dismissed', 'expired', 'returned'], true)) return 'ar-status-stamp ar-status-stamp--overdue';
    return 'ar-status-stamp ar-status-stamp--pending';
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
            ['key' => 'employee_compliance', 'label' => 'Employee Compliance Status', 'table' => 'lc_compliance_records', 'table_label' => 'Compliance Records', 'export' => 'export_report'],
            ['key' => 'employee_documents', 'label' => 'Employee Documents', 'table' => 'lc_employee_documents', 'table_label' => 'Employee Documents', 'export' => 'export_report'],
            ['key' => 'employment_contracts', 'label' => 'Employment Contracts', 'table' => 'lc_contracts', 'table_label' => 'Contracts', 'export' => 'export_contract_compliance'],
            ['key' => 'document_expiration', 'label' => 'Document Expiration', 'table' => 'lc_employee_documents', 'table_label' => 'Employee Documents', 'export' => 'export_report'],
            ['key' => 'training_certifications', 'label' => 'Training & Certifications', 'table' => 'lc_trainings', 'table_label' => 'Trainings', 'export' => 'export_report'],
            ['key' => 'policy_acknowledgement', 'label' => 'Policy Acknowledgement', 'table' => 'lc_acknowledgment_log', 'table_label' => 'Acknowledgement Log', 'export' => 'export_report'],
            ['key' => 'leave_summary', 'label' => 'Leave Summary', 'table' => 'leave_requests', 'table_label' => 'Leave Requests', 'export' => 'export_report'],
        ]
    ],
    'Government Reports' => [
        'icon' => 'bi-bank2',
        'reports' => [
            ['key' => 'sss_compliance', 'label' => 'SSS Compliance', 'table' => 'sss_contributions', 'table_label' => 'SSS Contributions', 'export' => 'export_sss_report'],
            ['key' => 'philhealth_compliance', 'label' => 'PhilHealth Compliance', 'table' => 'philhealth_contributions', 'table_label' => 'PhilHealth Contributions', 'export' => 'export_philhealth_report'],
            ['key' => 'pagibig_compliance', 'label' => 'Pag-IBIG Compliance', 'table' => 'pagibig_contributions', 'table_label' => 'Pag-IBIG Contributions', 'export' => 'export_pagibig_report'],
            ['key' => 'bir_compliance', 'label' => 'BIR Compliance', 'table' => 'pr_bir_contribution', 'table_label' => 'BIR Contributions', 'export' => 'export_government_report'],
            ['key' => 'government_submission', 'label' => 'Government Submission Status', 'table' => 'lc_government_validations', 'table_label' => 'Government Validations', 'export' => 'export_government_report'],
            ['key' => 'missing_registrations', 'label' => 'Missing Government Registrations', 'table' => 'lc_government_requirements', 'table_label' => 'Government Requirements', 'export' => 'export_government_report'],
            ['key' => 'government_summary', 'label' => 'Government Compliance Summary', 'table' => 'lc_compliance_records', 'table_label' => 'Compliance Records', 'export' => 'export_government_report'],
        ]
    ],
    'Legal Reports' => [
        'icon' => 'bi-shield-exclamation',
        'reports' => [
            ['key' => 'incident_reports', 'label' => 'Incident Reports', 'table' => 'incident_report', 'table_label' => 'Incident Reports', 'export' => 'export_incident'],
            ['key' => 'disciplinary_actions', 'label' => 'Disciplinary Actions', 'table' => 'lc_disciplinary_actions', 'table_label' => 'Disciplinary Actions', 'export' => 'export_report'],
            ['key' => 'anonymous_reports', 'label' => 'Anonymous Reports', 'table' => 'lc_complaints', 'table_label' => 'Complaints', 'export' => 'export_report'],
            ['key' => 'legal_cases', 'label' => 'Legal Cases', 'table' => 'lc_compliance_violations', 'table_label' => 'Compliance Violations', 'export' => 'export_report'],
            ['key' => 'risk_assessment', 'label' => 'Risk Assessment', 'table' => 'lc_risks', 'table_label' => 'Risks', 'export' => 'export_risk'],
            ['key' => 'audit_findings', 'label' => 'Audit Findings', 'table' => 'lc_audit_findings', 'table_label' => 'Audit Findings', 'export' => 'export_report'],
        ]
    ],
    'Recruitment & Exit Reports' => [
        'icon' => 'bi-person-plus',
        'reports' => [
            ['key' => 'recruitment_summary', 'label' => 'Recruitment Summary', 'table' => 'lc_recruitment', 'table_label' => 'Recruitment', 'export' => 'export_report'],
            ['key' => 'new_employees', 'label' => 'New Employees', 'table' => 'em_employees', 'table_label' => 'Employees', 'export' => 'export_report'],
            ['key' => 'contract_renewals', 'label' => 'Contract Renewals', 'table' => 'lc_contracts', 'table_label' => 'Contracts', 'export' => 'export_contract_compliance'],
            ['key' => 'exit_clearance', 'label' => 'Exit Clearance', 'table' => 'lc_exit_clearance', 'table_label' => 'Exit Clearance', 'export' => 'export_report'],
            ['key' => 'exit_summary', 'label' => 'Exit Summary', 'table' => 'exit_resignations', 'table_label' => 'Exit Requests', 'export' => 'export_report'],
            ['key' => 'job_posting_approval', 'label' => 'Job Posting Approval', 'table' => 'lc_job_posting_requests', 'table_label' => 'Job Posting Requests', 'export' => 'export_report'],
            ['key' => 'vacancy_reports', 'label' => 'Vacancy Reports', 'table' => 'lc_vacant_positions', 'table_label' => 'Vacant Positions', 'export' => 'export_report'],
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
?>

<!-- ============ SUMMARY CARDS ============ -->
<div class="ar-summary-bar">
  <div class="ar-summary-item" onclick="arSwitchTab('ready_reports')">
    <div class="ar-summary-icon blue"><i class="bi bi-file-earmark-bar-graph"></i></div>
    <div>
      <div class="ar-summary-value"><?= number_format(array_sum($reportCounts)) ?></div>
      <div class="ar-summary-label">Ready Reports</div>
    </div>
  </div>
  <div class="ar-summary-item" onclick="arSwitchTab('scheduled')">
    <div class="ar-summary-icon amber"><i class="bi bi-calendar-event"></i></div>
    <div>
      <div class="ar-summary-value"><?= number_format(count($scheduledReports)) ?></div>
      <div class="ar-summary-label">Scheduled Reports</div>
    </div>
  </div>
  <div class="ar-summary-item" onclick="arSwitchTab('submitted')">
    <div class="ar-summary-icon purple"><i class="bi bi-send"></i></div>
    <div>
      <div class="ar-summary-value"><?= number_format(count($submittedReports)) ?></div>
      <div class="ar-summary-label">Submitted Reports</div>
    </div>
  </div>
  <div class="ar-summary-item" onclick="arSwitchTab('audit_trail')">
    <div class="ar-summary-icon green"><i class="bi bi-clock-history"></i></div>
    <div>
      <div class="ar-summary-value"><?= number_format(count($auditTrail)) ?></div>
      <div class="ar-summary-label">Audit Trail</div>
    </div>
  </div>
</div>

<div class="ar-row">
  <div class="ar-col-main">

    <!-- ============ READY REPORTS ============ -->
    <div id="arPanel-ready_reports" class="ar-panel" style="display:<?= $activeTab === 'ready_reports' ? 'block' : 'none' ?>;">
      <?php if ($filterCategory !== '' && isset($reportCategories[$filterCategory])): ?>
        <?php $cat = $reportCategories[$filterCategory]; ?>
        <div class="ar-card">
          <div class="ar-card-head">
            <h3><i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i> <?= htmlspecialchars($filterCategory) ?></h3>
            <button type="button" class="ar-btn" onclick="arFilterByType('')"><i class="bi bi-x"></i> Clear Filter</button>
          </div>
          <div class="ar-table-wrap">
            <table class="ar-table">
              <thead>
                <tr>
                  <th>Report</th>
                  <th>Source</th>
                  <th>Records</th>
                  <th class="ar-action-cell" style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cat['reports'] as $rpt): ?>
                <tr>
                  <td>
                     <div class="ar-cnum"><?= htmlspecialchars($rpt['label']) ?></div>
                     <div class="ar-emp-no"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rpt['key']))) ?></div>
                  </td>
                  <td><span class="ar-type-badge"><?= htmlspecialchars($rpt['table_label'] ?? $rpt['table']) ?></span></td>
                  <td>
                    <span class="ar-status-stamp ar-status-stamp--info"><?= number_format($reportCounts[$rpt['key']] ?? 0) ?> records</span>
                  </td>
                  <td class="ar-action-cell" style="text-align:right;">
                    <button type="button" class="ar-btn-icon" onclick="arPreview('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Preview"><i class="bi bi-eye"></i></button>
                    <button type="button" class="ar-btn-icon" onclick="arGeneratePDF('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Generate PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                    <button type="button" class="ar-btn-icon" onclick="arSendToDirectress('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['label']) ?>')" title="Send to Directress"><i class="bi bi-send"></i></button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php elseif ($filterCategory !== '' && !isset($reportCategories[$filterCategory])): ?>
        <div class="ar-card"><div class="ar-empty"><i class="bi bi-emoji-smile"></i> Category not found.</div></div>
      <?php else: ?>
        <?php foreach ($reportCategories as $catName => $cat): ?>
        <div class="ar-card">
          <div class="ar-card-head">
            <h3><i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i> <?= htmlspecialchars($catName) ?></h3>
          </div>
          <div class="ar-table-wrap">
            <table class="ar-table">
              <thead>
                <tr>
                  <th>Report</th>
                  <th>Source</th>
                  <th>Records</th>
                  <th class="ar-action-cell" style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cat['reports'] as $rpt): ?>
                <tr>
                  <td>
                     <div class="ar-cnum"><?= htmlspecialchars($rpt['label']) ?></div>
                     <div class="ar-emp-no"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rpt['key']))) ?></div>
                  </td>
                  <td><span class="ar-type-badge"><?= htmlspecialchars($rpt['table_label'] ?? $rpt['table']) ?></span></td>
                  <td>
                    <span class="ar-status-stamp ar-status-stamp--info"><?= number_format($reportCounts[$rpt['key']] ?? 0) ?> records</span>
                  </td>
                  <td class="ar-action-cell" style="text-align:right;">
                    <button type="button" class="ar-btn-icon" onclick="arPreview('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Preview"><i class="bi bi-eye"></i></button>
                    <button type="button" class="ar-btn-icon" onclick="arGeneratePDF('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['export']) ?>')" title="Generate PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                    <button type="button" class="ar-btn-icon" onclick="arSendToDirectress('<?= htmlspecialchars($rpt['key']) ?>', '<?= htmlspecialchars($rpt['label']) ?>')" title="Send to Directress"><i class="bi bi-send"></i></button>
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
    <div id="arPanel-scheduled" class="ar-panel" style="display:<?= $activeTab === 'scheduled' ? 'block' : 'none' ?>;">
      <div class="ar-card">
        <div class="ar-card-head">
          <h3><i class="bi bi-calendar-event"></i> Scheduled Reports</h3>
          <button type="button" class="ar-btn primary" onclick="openScheduleModal()"><i class="bi bi-plus-lg"></i> Schedule Report</button>
        </div>
        <div class="ar-table-wrap">
          <table class="ar-table">
            <thead>
              <tr>
                <th>Report</th>
                <th>Frequency</th>
                <th>Next Due</th>
                <th>Recipient</th>
                <th>Status</th>
                <th class="ar-action-cell" style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($scheduledReports)) : ?>
                <tr><td colspan="6"><div class="ar-empty"><i class="bi bi-emoji-smile"></i> No scheduled reports.</div></td></tr>
              <?php else : ?>
                <?php foreach ($scheduledReports as $sr): ?>
                <tr>
                  <td>
                    <div class="ar-cnum"><?= htmlspecialchars(ar_report_label($sr['report_key'] ?? '', $reportCategories) ?: ($sr['report_name'] ?? $sr['report_key'] ?? '—')) ?></div>
                    <div class="ar-emp-no"><?= htmlspecialchars($sr['module'] ?? '') ?></div>
                  </td>
                  <td><span class="ar-type-badge"><?= htmlspecialchars($sr['frequency'] ?? 'Monthly') ?></span></td>
                  <td><span class="ar-emp-no"><?= !empty($sr['next_run']) ? date('M d, Y', strtotime($sr['next_run'])) : '—' ?></span></td>
                  <td><div class="ar-emp-name"><?= htmlspecialchars($sr['recipient_email'] ?? 'Directress') ?></div></td>
                  <td>
                    <?php if (!empty($sr['active'])) : ?>
                      <span class="ar-status-stamp ar-status-stamp--info">Active</span>
                    <?php else : ?>
                      <span class="ar-status-stamp ar-status-stamp--overdue">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="ar-action-cell" style="text-align:right;">
                    <button type="button" class="ar-btn-icon" onclick="arSendNow('<?= htmlspecialchars($sr['report_key'] ?? '') ?>')" title="Send Now"><i class="bi bi-send"></i></button>
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
    <div id="arPanel-submitted" class="ar-panel" style="display:<?= $activeTab === 'submitted' ? 'block' : 'none' ?>;">
      <div class="ar-card">
        <div class="ar-card-head">
          <h3><i class="bi bi-file-earmark-bar-graph"></i> Submitted Reports</h3>
        </div>
        <div class="ar-table-wrap">
          <table class="ar-table">
            <thead>
              <tr>
                <th>Report</th>
                <th>Generated</th>
                <th>Submitted</th>
                <th>Status</th>
                <th class="ar-action-cell" style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($submittedReports)) : ?>
                <tr><td colspan="5"><div class="ar-empty"><i class="bi bi-emoji-smile"></i> No submitted reports yet. (Count: <?= count($submittedReports) ?>)</div></td></tr>
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
                    <div class="ar-cnum"><?= htmlspecialchars($label) ?></div>
                    <div class="ar-emp-no"><?= htmlspecialchars($code) ?></div>
                  </td>
                  <td><span class="ar-emp-no"><?= $created ?></span></td>
                  <td><span class="ar-emp-no"><?= $submitted ?></span></td>
                  <td><span class="ar-status-stamp ar-status-stamp--<?= $sc ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                  <td class="ar-action-cell" style="text-align:right;">
                    <button type="button" class="ar-btn-icon" onclick="arPreview('<?= htmlspecialchars($r['report_key'] ?? '') ?>', 'export_report')" title="Preview"><i class="bi bi-eye"></i></button>
                    <button type="button" class="ar-btn-icon" onclick="arGeneratePDF('<?= htmlspecialchars($r['report_key'] ?? '') ?>', 'export_report')" title="PDF"><i class="bi bi-file-earmark-pdf"></i></button>
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
    <div id="arPanel-audit_trail" class="ar-panel" style="display:<?= $activeTab === 'audit_trail' ? 'block' : 'none' ?>;">
      <div class="ar-card">
        <div class="ar-card-head">
          <h3><i class="bi bi-clock-history"></i> Audit Trail</h3>
        </div>
        <div class="ar-table-wrap">
          <table class="ar-table">
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
                <tr><td colspan="5"><div class="ar-empty"><i class="bi bi-emoji-smile"></i> No audit trail records.</div></td></tr>
              <?php else : ?>
                <?php foreach ($auditTrail as $t): ?>
                <tr>
                  <td><span class="ar-emp-no"><?= !empty($t['created_at']) ? date('M d, Y H:i', strtotime($t['created_at'])) : '—' ?></span></td>
                  <td><div class="ar-emp-name"><?= htmlspecialchars($t['user_name'] ?? ('User #' . $t['user_id'])) ?></div></td>
                  <td>
                    <?php
                      $act = strtolower($t['action'] ?? '');
                      if (str_contains($act, 'generate') || str_contains($act, 'export')) $ac = 'info';
                      elseif (str_contains($act, 'send') || str_contains($act, 'submit')) $ac = 'pending';
                      elseif (str_contains($act, 'approve')) $ac = 'compliant';
                      elseif (str_contains($act, 'reject') || str_contains($act, 'return')) $ac = 'overdue';
                      else $ac = 'info';
                    ?>
                    <span class="ar-status-stamp ar-status-stamp--<?= $ac ?>"><?= htmlspecialchars(ar_label($t['action'] ?? 'Action')) ?></span>
                  </td>
                  <td><div class="ar-emp-name"><?= htmlspecialchars(ar_report_label($t['report_key'] ?? '', $reportCategories) ?: ($t['report_key'] ?? '—')) ?></div></td>
                  <td><span class="ar-emp-no"><?= htmlspecialchars(mb_strimwidth($t['details'] ?? '', 0, 60, '…')) ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /.ar-col-main -->

  <!-- ============ SIDEBAR REPORT TYPES ============ -->
  <div class="ar-col-side">
    <div class="ar-card">
      <div class="ar-card-head"><h3><i class="bi bi-diagram-3"></i> Report Type</h3></div>
      <div style="display:flex; flex-direction:column; gap:6px;">
        <button type="button" class="ar-type-btn" onclick="arFilterByType('')">
          <i class="bi bi-folder2-open"></i>
          <div style="flex:1; min-width:0;">
            <div style="font-weight:700; font-size:0.78rem;">All Reports</div>
            <div style="font-size:0.68rem; opacity:0.85;"><?= number_format(array_sum($reportCounts)) ?> total records</div>
          </div>
          <span class="ar-type-count"><?= number_format(array_sum($reportCounts)) ?></span>
        </button>
        <?php foreach ($reportCategories as $catName => $cat): 
          $catCount = 0;
          foreach ($cat['reports'] as $rpt) { $catCount += $reportCounts[$rpt['key']] ?? 0; }
        ?>
        <button type="button" class="ar-type-btn" onclick="arFilterByType('<?= htmlspecialchars($catName) ?>')">
          <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
          <div style="flex:1; min-width:0;">
            <div style="font-weight:700; font-size:0.78rem;"><?= htmlspecialchars($catName) ?></div>
            <div style="font-size:0.68rem; opacity:0.85;"><?= number_format(count($cat['reports'])) ?> reports</div>
          </div>
          <span class="ar-type-count"><?= number_format($catCount) ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ar-card">
      <div class="ar-card-head"><h3><i class="bi bi-calendar-plus"></i> Schedule Report</h3></div>
      <button type="button" class="ar-quick-btn" onclick="openScheduleModal()" style="width:100%;">
        <i class="bi bi-calendar-plus"></i>
        <div><div style="font-weight:700; font-size:0.82rem;">Schedule New Report</div><div style="font-size:0.72rem; opacity:0.85;">Set up recurring reports</div></div>
      </button>
    </div>
  </div>
</div>

<!-- ============ SCHEDULE MODAL ============ -->
<div class="ar-modal-backdrop" id="arScheduleModal">
  <div class="ar-modal">
    <div class="ar-modal-head">
      <h3><i class="bi bi-calendar-plus"></i> Schedule Report</h3>
      <button type="button" class="ar-modal-close" onclick="closeModal('arScheduleModal')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="ar-modal-body">
      <form id="arScheduleForm">
        <div class="ar-field">
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
        <div class="ar-field">
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
        <div class="ar-check">
          <input type="checkbox" name="send_now" id="arSendNow" value="1" checked>
          <label for="arSendNow">Send immediately</label>
        </div>
      </form>
    </div>
    <div class="ar-modal-foot">
      <button type="button" class="ar-btn" onclick="closeModal('arScheduleModal')">Cancel</button>
      <button type="button" class="ar-btn primary" onclick="submitScheduleForm()"><i class="bi bi-check-lg"></i> Schedule</button>
    </div>
  </div>
</div>

<!-- ============ TOAST ============ -->
<div class="ar-toast" id="arToast"></div>

<style>
.ch-module { padding: 4px 2px 24px; }
.ch-breadcrumb { margin-bottom:10px; }
.ch-breadcrumb .breadcrumb { background:transparent; padding:0; margin:0; font-size:0.76rem; }
.ch-breadcrumb .breadcrumb-item a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.ch-breadcrumb .breadcrumb-item a:hover { text-decoration:underline; }
.ch-breadcrumb .breadcrumb-item.active { color:var(--text-500,#6b7280); }
.ch-breadcrumb .breadcrumb-item + .breadcrumb-item::before { color:var(--text-400,#8b93a1); }

.ar-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.ar-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.ar-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.ar-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.ar-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.ar-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ar-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.ar-summary-icon.purple { background:rgba(124,58,237,.10); color:#5b21b6; }
.ar-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.ar-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

.ar-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.ar-col-main { min-width:0; }
.ar-col-side { width:380px; flex-shrink:0; }

.ar-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.ar-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.ar-card-head h3 { margin:0; font-size:0.88rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ar-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.76rem; }
.ar-table-wrap { overflow:auto; max-height: 420px; }
.ar-table { width:100%; border-collapse:collapse; font-size:0.72rem; color:#1b2430; }
.ar-table th { text-align:left; padding:10px 12px; font-size:0.66rem; font-weight:700; text-transform:uppercase; color:#8b93a1; border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; vertical-align:middle; }
.ar-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:middle; color:#1b2430; min-width:0; }
.ar-table tr:last-child td { border-bottom:none; }
.ar-table .ar-action-cell { width:140px; }

.ar-cnum { font-weight:600; color:#2b3340; font-size:0.68rem; }
.ar-emp-name { font-weight:600; color:#2b3340; font-size:0.68rem; }
.ar-emp-no { font-size:0.64rem; color:#8b93a1; }
.ar-type-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.62rem; font-weight:700; white-space:nowrap; background:rgba(59,130,196,.08); color:#1c5a8a; border:1px solid rgba(59,130,196,.16); }

.ar-status-stamp { display:inline-block; font-size:0.62rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.ar-status-stamp--compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.ar-status-stamp--info { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ar-status-stamp--pending { background:rgba(217,154,43,.14); color:#a86b13; }
.ar-status-stamp--overdue { background:rgba(214,72,74,.12); color:#a3272a; }

.ar-btn { font-size:0.62rem; font-weight:600; padding:8px 12px; border-radius:6px; border:1px solid var(--border,#e4e8ee); background:var(--card-bg,#fff); color:var(--text-600,#5b6472); cursor:pointer; text-decoration:none; white-space:nowrap; transition:all 150ms ease; display:inline-flex; align-items:center; gap:4px; }
.ar-btn:hover { background:var(--bg-page,#f3f5f9); border-color:var(--border-strong,#d3d9e2); }
.ar-btn.primary { background:var(--info-blue,#3b82c4); color:#fff; border-color:var(--info-blue,#3b82c4); }
.ar-btn.primary:hover { background:#1c5a8a; border-color:#1c5a8a; color:#fff; }
.ar-btn-icon { display:inline-flex; align-items:center; justify-content:center; gap:3px; padding:2px 4px; border-radius:5px; border:1px solid var(--border,#e4e8ee); background:#fff; color:#5b6472; cursor:pointer; text-decoration:none; white-space:nowrap; font-size:0.62rem; font-weight:600; transition:background 150ms ease, border-color 150ms ease, color 150ms ease, transform 80ms ease; }
.ar-btn-icon:hover { background:#f3f5f9; border-color:#d3d9e2; color:#2b3340; }
.ar-btn-icon:active { transform:translateY(1px); }
.ar-btn-icon .bi { font-size:0.82rem; line-height:1; color:inherit; }
.ar-action-cell { text-align:right; }

.ar-field { display:flex; flex-direction:column; gap:4px; min-width:0; }
.ar-field label { font-size:0.64rem; font-weight:600; color:var(--text-700,#3b4252); margin-bottom:3px; display:block; text-transform:none; letter-spacing:normal; }
.ar-field input, .ar-field select { width:100%; padding:7px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.72rem; color:var(--text-900,#1b2430); background:var(--card-bg,#fff); box-sizing:border-box; transition:border-color 150ms ease, box-shadow 150ms ease; }
.ar-field input:focus, .ar-field select:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,0.12); }
.ar-field--actions { display:flex; align-items:flex-end; gap:6px; }
.ar-quick-btn { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:rgba(59,130,196,.06); border:1px solid rgba(59,130,196,.18); text-decoration:none; color:#1c5a8a; cursor:pointer; width:100%; text-align:left; font-family:inherit; font-size:inherit; transition:all .15s ease; }
.ar-quick-btn:hover { background:rgba(59,130,196,.12); }

.ar-type-btn { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; background:#fff; border:1px solid var(--border,#e4e8ee); text-decoration:none; color:#1c5a8a; cursor:pointer; width:100%; text-align:left; font-family:inherit; font-size:inherit; transition:all .15s ease; }
.ar-type-btn:hover { background:rgba(59,130,196,.06); border-color:var(--info-blue,#3b82c4); }
.ar-type-btn i { font-size:1rem; color:var(--info-blue,#3b82c4); width:20px; text-align:center; }
.ar-type-count { font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(59,130,196,.08); color:#1c5a8a; white-space:nowrap; }

@media (max-width:1100px){ .ar-row { grid-template-columns:1fr; } .ar-col-side { width:auto; } }
@media (max-width:600px){ .ar-tabs { flex-direction:column; } }

.ar-modal-backdrop { position:fixed; inset:0; background:rgba(13,27,46,.55); z-index:1050; display:none; align-items:center; justify-content:center; backdrop-filter:blur(2px); }
.ar-modal-backdrop.open { display:flex; }
.ar-modal { background:#fff; border-radius:16px; border:1px solid var(--hairline,#e4e8ee); box-shadow:0 4px 8px rgba(13,27,46,.06), 0 16px 32px -14px rgba(13,27,46,.18); width:640px; max-width:calc(100vw - 32px); max-height:92vh; display:flex; flex-direction:column; }
.ar-modal-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--hairline,#e4e8ee); flex-shrink:0; }
.ar-modal-head h3 { font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); margin:0; display:flex; align-items:center; gap:8px; }
.ar-modal-close { width:32px; height:32px; border-radius:50%; border:1px solid var(--hairline,#e4e8ee); background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-600,#5b6472); }
.ar-modal-close:hover { background:var(--paper,#f3f5f9); }
.ar-modal-body { padding:16px 18px; flex:1 1 auto; min-height:0; }
.ar-modal-foot { display:flex; justify-content:flex-end; gap:8px; padding:12px 18px; border-top:1px solid var(--hairline,#e4e8ee); flex-shrink:0; }
.ar-field { display:flex; flex-direction:column; gap:2px; margin-bottom:8px; }
.ar-field label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); }
.ar-field input, .ar-field select, .ar-field textarea { width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.8rem; color:var(--text-900,#1b2430); box-sizing:border-box; font-family:inherit; }
.ar-field input:focus, .ar-field select:focus, .ar-field textarea:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,0.12); }
.ar-check { display:flex; align-items:center; gap:8px; margin-top:4px; }
.ar-check input { width:auto; }
.ar-check label { font-size:0.78rem; color:var(--text-700,#3b4252); }

.ar-toast { position:fixed; left:50%; bottom:28px; transform:translate(-50%,16px); background:var(--ink-900,#0d1b2e); color:#fff; font-size:13px; font-weight:600; padding:11px 18px; border-radius:999px; box-shadow:0 4px 12px rgba(0,0,0,.2); opacity:0; transition:opacity .25s ease, transform .25s ease; z-index:400; pointer-events:none; }
.ar-toast.show { opacity:1; transform:translate(-50%,0); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var forms = document.querySelectorAll('.ar-modal form');
  forms.forEach(function (f) {
    f.addEventListener('submit', function (e) { e.preventDefault(); });
  });
});

function arSwitchTab(tab, btn) {
  document.querySelectorAll('.ar-tab').forEach(function (t) { t.classList.remove('active'); });
  if (btn) {
    btn.classList.add('active');
  } else {
    var tabs = document.querySelectorAll('.ar-tab');
    for (var i = 0; i < tabs.length; i++) {
      if (tabs[i].textContent.trim().toLowerCase().indexOf(tab.replace('_', ' ')) !== -1 || tabs[i].getAttribute('onclick').indexOf("'" + tab + "'") !== -1) {
        tabs[i].classList.add('active');
        break;
      }
    }
  }
  document.querySelectorAll('.ar-panel').forEach(function (p) { p.style.display = 'none'; });
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

