<?php

require_once __DIR__ . '/../../../database/db.php';

$pageTitle   = 'Complaint Management';
$activeGroup = 'Incident Reporting';
$activePage  = 'case-records';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    $db = (new Database())->getConnection();
}
if (!($db instanceof PDO)) {
  throw new RuntimeException('Database connection is unavailable.');
}

$extraCssArray  = [];
$extraJsArray   = [];

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function ch_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function ch_row(PDO $db, string $sql): ?array {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}
function ch_q(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
function ch_priority_class(string $p): string {
    $s = strtolower($p);
    if ($s === 'critical') return 'ir-severity-pill ir-severity-pill--high';
    if ($s === 'high') return 'ir-severity-pill ir-severity-pill--high';
    if ($s === 'medium') return 'ir-severity-pill ir-severity-pill--med';
    return 'ir-severity-pill ir-severity-pill--low';
}
function ch_status_class(string $status): string {
    $s = strtolower($status);
    if (in_array($s, ['closed', 'resolved', 'closed_no_violation', 'closed_warning_issued', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved'], true)) return 'ir-status-stamp ir-status-stamp--compliant';
    if (in_array($s, ['for_hearing', 'decision_pending', 'disciplinary_action', 'for_decision'], true)) return 'ir-status-stamp ir-status-stamp--pending';
    if (in_array($s, ['under_investigation', 'pending_nte', 'awaiting_response', 'under_initial_review', 'pending_employee_response'], true)) return 'ir-status-stamp ir-status-stamp--info';
    return 'ir-status-stamp ir-status-stamp--pending';
}
function ch_label(?string $s): string {
    return htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$s)));
}
function ch_date(?string $d, string $fmt = 'M d, Y'): string {
    return !empty($d) ? date($fmt, strtotime($d)) : '';
}
function ch_employee_name(PDO $db, $employeeId): string {
    if (empty($employeeId)) return '';
    $row = ch_row($db, "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM em_employees WHERE employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row['full_name'] ?? '';
}
function ch_employee_no(PDO $db, $employeeId): string {
    if (empty($employeeId)) return '';
    $row = ch_row($db, "SELECT employee_code FROM em_employees WHERE employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row['employee_code'] ?? '';
}
function ch_type_icon(string $type): string {
    $t = strtolower($type);
    if (str_contains($t, 'conflict of interest')) return 'bi bi-shuffle';
    if (str_contains($t, 'insubordination')) return 'bi bi-person-x';
    if (str_contains($t, 'harassment')) return 'bi bi-emoji-frown';
    if (str_contains($t, 'policy violation')) return 'bi bi-file-earmark-x';
    if (str_contains($t, 'discrimination')) return 'bi bi-people';
    if (str_contains($t, 'misconduct')) return 'bi bi-exclamation-triangle';
    if (str_contains($t, 'theft')) return 'bi bi-bag-x';
    if (str_contains($t, 'attendance')) return 'bi bi-calendar-x';
    if (str_contains($t, 'performance')) return 'bi bi-graph-down';
    return 'bi bi-exclamation-circle';
}

$complaintTable = 'lc_complaints';

$fSearch   = trim($_GET['search'] ?? '');
$fType     = trim($_GET['complaint_type'] ?? '');
$fSeverity = trim($_GET['priority'] ?? '');
$fStatus   = trim($_GET['status'] ?? '');
$fFrom     = trim($_GET['date_from'] ?? '');
$fTo       = trim($_GET['date_to'] ?? '');

$where  = [];
$params = [];
if ($fSearch !== '') {
    $where[] = "(CONCAT('CMP-', LPAD(id, 5, '0', '0')) LIKE ? OR description LIKE ? OR type LIKE ?)";
    $params[] = "%$fSearch%";
    $params[] = "%$fSearch%";
    $params[] = "%$fSearch%";
}
if ($fType !== '')     { $where[] = "type = ?";           $params[] = $fType; }
if ($fSeverity !== '') { $where[] = "severity = ?";       $params[] = $fSeverity; }
if ($fStatus !== '')   {
    if ($fStatus === 'closed') {
        $where[] = "status LIKE ?";
        $params[] = "closed%";
    } else {
        $where[] = "status = ?";
        $params[] = $fStatus;
    }
}
if ($fFrom !== '')     { $where[] = "created_at >= ?";    $params[] = $fFrom; }
if ($fTo !== '')       { $where[] = "created_at <= ?";    $params[] = $fTo; }
$whereSql  = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$whereAnd  = $whereSql ? ($whereSql . ' AND ') : 'WHERE ';

$totalComplaintsStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable`");
$totalComplaintsStmt->execute();
$totalComplaints = (int) $totalComplaintsStmt->fetchColumn();

$newComplaintsStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status = 'under_initial_review'");
$newComplaintsStmt->execute();
$newComplaints = (int) $newComplaintsStmt->fetchColumn();

$underInvestigationStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status = 'under_investigation'");
$underInvestigationStmt->execute();
$underInvestigation = (int) $underInvestigationStmt->fetchColumn();

$forHearingStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status = 'for_decision'");
$forHearingStmt->execute();
$forHearing = (int) $forHearingStmt->fetchColumn();

$pendingNteResponseStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status = 'pending_employee_response'");
$pendingNteResponseStmt->execute();
$pendingNteResponse = (int) $pendingNteResponseStmt->fetchColumn();

$pendingDisciplinary = 0;

$allClosedStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status LIKE 'closed%'");
$allClosedStmt->execute();
$allClosed = (int) $allClosedStmt->fetchColumn();

$closedCasesStmt = $db->prepare("SELECT COUNT(*) FROM `$complaintTable` WHERE status = 'closed'");
$closedCasesStmt->execute();
$closedCases = (int) $closedCasesStmt->fetchColumn();

$typeOptions = ch_q($db, "SELECT DISTINCT type FROM `$complaintTable` WHERE type IS NOT NULL AND TRIM(type) <> '' ORDER BY type");

$records = ch_q($db, "
    SELECT c.*, 
           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
           e.employee_code AS employee_no
    FROM `$complaintTable` c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    $whereSql
    ORDER BY c.created_at DESC LIMIT 100
", $params);

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$activeTab  = $_GET['tab'] ?? ($selectedId ? 'dashboard' : 'dashboard');
$case = null;
if ($selectedId) {
    $case = ch_row($db, "SELECT * FROM `$complaintTable` WHERE id = " . $selectedId . " LIMIT 1");
}

$priorityOptions = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
$statusOptions = [
    'under_initial_review'         => 'Under Initial Review',
    'under_investigation'          => 'Under Investigation',
    'pending_employee_response'    => 'Pending Employee Response',
    'for_decision'                 => 'For Decision',
    'closed_no_violation'          => 'Closed - No Violation',
    'closed_warning_issued'        => 'Closed - Warning Issued',
    'closed_suspension'            => 'Closed - Suspension',
    'closed_termination_recommended' => 'Closed Termination Recommended',
    'closed_resolved'              => 'Closed - Resolved',
    'closed'                       => 'Closed',
];
?>
<section class="ir-module">
   <?php if (!empty($flash)): ?>
     <?php [$fc, $fm] = explode('|', $flash, 2); ?>
     <div class="ir-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

      <div class="ir-summary-bar">
        <a class="ir-summary-item" href="?page=case-records&status=">
         <div class="ir-summary-icon amber"><i class="bi bi-journal-text"></i></div>
         <div>
           <div class="ir-summary-value"><?= number_format($totalComplaints) ?></div>
           <div class="ir-summary-label">Total Complaints</div>
         </div>
       </a>
       <a class="ir-summary-item" href="?page=case-records&status=under_initial_review">
         <div class="ir-summary-icon blue"><i class="bi bi-folder"></i></div>
         <div>
           <div class="ir-summary-value"><?= number_format($newComplaints) ?></div>
           <div class="ir-summary-label">Under Initial Review</div>
         </div>
       </a>
       <a class="ir-summary-item" href="?page=case-records&status=under_investigation">
         <div class="ir-summary-icon purple"><i class="bi bi-binoculars"></i></div>
         <div>
           <div class="ir-summary-value"><?= number_format($underInvestigation) ?></div>
           <div class="ir-summary-label">Under Investigation</div>
         </div>
       </a>
       <a class="ir-summary-item" href="?page=case-records&status=pending_employee_response">
         <div class="ir-summary-icon orange"><i class="bi bi-reply"></i></div>
         <div>
           <div class="ir-summary-value"><?= number_format($pendingNteResponse) ?></div>
           <div class="ir-summary-label">Pending Employee Response</div>
         </div>
       </a>
       <a class="ir-summary-item" href="?page=case-records&status=for_decision">
         <div class="ir-summary-icon red"><i class="bi bi-exclamation-octagon"></i></div>
         <div>
           <div class="ir-summary-value"><?= number_format($forHearing) ?></div>
           <div class="ir-summary-label">For Decision</div>
         </div>
       </a>
        <a class="ir-summary-item" href="?page=case-records&status=closed">
          <div class="ir-summary-icon green"><i class="bi bi-check2-all"></i></div>
          <div>
            <div class="ir-summary-value"><?= number_format($allClosed) ?></div>
            <div class="ir-summary-label">Closed</div>
          </div>
        </a>
      </div>

    <div class="ir-row">
      <div class="ir-col ir-col-main">
        <div class="ir-card">
          <div class="ir-card-head">
            <h3><i class="bi bi-journal-check"></i> Complaint Records</h3>
          </div>
          <div class="ir-card-body">
            <?php if (empty($records)): ?>
             <div class="ir-empty"><i class="bi bi-emoji-smile"></i> No complaints match the current filters.</div>
           <?php else: ?>
           <div class="ir-table-wrap">
             <table class="ir-table">
                 <thead>
                   <tr>
                     <th class="ir-id-cell">Complaint No.</th>
                     <th class="ir-emp-cell">Employee</th>
                     <th>Type</th>
                     <th>Status</th>
                     <th>Priority</th>
                     <th class="ir-action-cell" style="text-align:right;">Actions</th>
                   </tr>
                 </thead>
                <tbody>
                   <?php foreach ($records as $r): ?>
                    <tr data-rid="<?= (int)$r['id'] ?>" style="cursor:pointer;">
                      <td class="ir-id-cell" data-label="Complaint No.">
                        <div class="ir-cnum"><?= htmlspecialchars('CMP-' . str_pad($r['id'] ?? 0, 5, '0', STR_PAD_LEFT)) ?></div>
                        <div class="ir-emp-no"><?= ch_date($r['created_at'] ?? null) ?></div>
                      </td>
                       <td class="ir-emp-cell" data-label="Employee">
                         <div class="ir-emp-name"><?= htmlspecialchars($r['employee_name'] ?: ($r['employee_id'] ?? 'N/A'), ENT_QUOTES) ?></div>
                         <div class="ir-emp-no"><?= htmlspecialchars($r['employee_no'] ?: '—', ENT_QUOTES) ?></div>
                       </td>
                      <td data-label="Type">
                        <span class="ir-type-badge" style="background:rgba(168,121,31,.1);color:#8a6318;border:1px solid rgba(168,121,31,.2);">
                          <i class="<?= ch_type_icon($r['type'] ?? '') ?>"></i> <?= ch_label($r['type'] ?? 'General') ?>
                        </span>
                      </td>
                      <td data-label="Status">
                        <span class="<?= ch_status_class($r['status'] ?? '') ?>"><?= ch_label($r['status'] ?? 'Under Initial Review') ?></span>
                      </td>
                      <td data-label="Priority">
                        <span class="<?= ch_priority_class($r['severity'] ?? '') ?>">
                          <span class="ir-severity-dot ir-severity-dot--<?= strtolower($r['severity'] ?? 'medium') ?>"></span>
                          <?= ch_label($r['severity'] ?? 'Medium') ?>
                        </span>
                      </td>
                      <td class="ir-action-cell" data-label="Actions" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="window.location.href='?page=complaint-workflow&id=<?= (int)$r['id'] ?>'">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>
                   <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

       <div class="ir-col ir-col-side">
         <div class="ir-card">
           <div class="ir-card-head">
             <h3><i class="bi bi-bell"></i> Urgent Actions</h3>
             <span class="ir-stamp ir-stamp-overdue" style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:999px;white-space:nowrap;"><?= number_format($pendingNteResponse + $pendingDisciplinary + $forHearing) ?></span>
           </div>
           <div class="ir-reminder-list ir-reminder-list--compact">
            <?php
            $urgentStatuses = ['under_investigation', 'pending_employee_response', 'for_decision'];
            $urgentCases = array_filter($records, function($r) use ($urgentStatuses) {
                return in_array(strtolower($r['status'] ?? ''), $urgentStatuses, true);
            });
            $urgentCases = array_slice($urgentCases, 0, 5);
            ?>
            <?php if (!empty($urgentCases)): ?>
              <?php foreach ($urgentCases as $r): ?>
                <div class="ir-reminder-row">
                  <div class="ir-reminder-text">
                    <strong><?= ch_label($r['status'] ?? '') ?></strong>
                    <span><?= htmlspecialchars('CMP-' . str_pad($r['id'] ?? 0, 5, '0', STR_PAD_LEFT)) ?></span>
                    <span class="ir-reminder-step"><?= ch_label($r['type'] ?? 'General') ?></span>
                  </div>
                  <div class="ir-reminder-actions">
                    <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="window.location.href='?page=complaint-workflow&id=<?= (int)$r['id'] ?>'">
                      <i class="bi bi-eye"></i> View
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="ir-empty"><i class="bi bi-emoji-smile"></i> No urgent actions required.</div>
            <?php endif; ?>
          </div>
    </div>
   </div>
  </div>
</div>
 </section>

<script>
(function(){
  document.querySelectorAll('tr[data-rid]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, form, label')) return;
      var rid = parseInt(row.getAttribute('data-rid'), 10);
      if (rid) {
        window.location.href = '?page=complaint-workflow&id=' + rid;
      }
    });
  });
  })();
</script>


