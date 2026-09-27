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
    if (in_array($s, ['for_hearing', 'decision_pending', 'disciplinary_action', 'for_decision', 'termination_employee_reply', 'termination_reviewed'], true)) return 'ir-status-stamp ir-status-stamp--pending';
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
$complaintTable = 'lc_complaints';

$fSearch   = trim($_GET['search'] ?? '');
$fType     = trim($_GET['complaint_type'] ?? '');
$fSeverity = trim($_GET['priority'] ?? '');
$fStatus   = trim($_GET['status'] ?? '');
$fFrom     = trim($_GET['date_from'] ?? '');
$fTo       = trim($_GET['date_to'] ?? '');
$fPage     = isset($_GET['cr_page']) ? max(1, (int) $_GET['cr_page']) : 1;

$baseUrl = '?page=case-records';

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
    ORDER BY c.created_at DESC
", $params);

$countQuery = "SELECT COUNT(*) FROM `$complaintTable` c $whereSql";
$totalRows = (int) ch_value($db, $countQuery, 0);

$perPage = 13;
$totalPages = ($perPage > 0 && $totalRows > 0) ? (int) ceil($totalRows / $perPage) : 1;
if ($fPage > $totalPages) {
    $fPage = $totalPages;
}
$offset = ($fPage - 1) * $perPage;

$records = ch_q($db, "
    SELECT c.*, 
           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
           e.employee_code AS employee_no
    FROM `$complaintTable` c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    $whereSql
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$activeTab  = $_GET['tab'] ?? ($selectedId ? 'dashboard' : 'dashboard');
$case = null;
if ($selectedId) {
    $case = ch_row($db, "SELECT * FROM `$complaintTable` WHERE id = " . $selectedId . " LIMIT 1");
}

$priorityOptions = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
$statusOptions = [
    'under_initial_review'          => 'Under Initial Review',
    'under_investigation'           => 'Under Investigation',
    'pending_employee_response'     => 'Pending Employee Response',
    'for_decision'                  => 'For Decision',
    'closed_no_violation'           => 'Closed - No Violation',
    'closed_warning_issued'         => 'Closed - Warning Issued',
    'closed_second_written_warning' => 'Closed - Second Written Warning',
    'closed_final_written_warning'  => 'Closed - Final Written Warning',
    'closed_suspension'             => 'Closed - Suspension',
    'closed_termination_recommended'=> 'Closed - Termination Recommended',
    'termination_employee_reply'    => 'Termination - Employee Reply',
    'termination_reviewed'          => 'Termination - Reviewed',
    'closed_resolved'               => 'Closed - Resolved',
    'closed'                        => 'Closed',
];
?>

    <div class="ir-summary-bar">
       <a class="ir-summary-item <?= $fStatus === '' && $fType === '' && $fSeverity === '' && $fSearch === '' && $fFrom === '' && $fTo === '' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>">
         <div>
           <div class="ir-summary-value"><?= number_format($totalComplaints) ?></div>
           <div class="ir-summary-label">Total Complaints</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $fStatus === 'under_investigation' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=under_investigation&complaint_type=&priority=&search=&date_from=&date_to=&cr_page=1">
         <div>
           <div class="ir-summary-value"><?= number_format($underInvestigation) ?></div>
           <div class="ir-summary-label">Under Investigation</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $fStatus === 'pending_employee_response' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=pending_employee_response&complaint_type=&priority=&search=&date_from=&date_to=&cr_page=1">
         <div>
           <div class="ir-summary-value"><?= number_format($pendingNteResponse) ?></div>
           <div class="ir-summary-label">Pending Response</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $fStatus === 'for_decision' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=for_decision&complaint_type=&priority=&search=&date_from=&date_to=&cr_page=1">
         <div>
           <div class="ir-summary-value"><?= number_format($forHearing) ?></div>
           <div class="ir-summary-label">For Decision</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $fStatus === 'closed' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=closed&complaint_type=&priority=&search=&date_from=&date_to=&cr_page=1">
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
             <h3>Complaint Records</h3>
          </div>
          <div class="ir-card-body">
             <?php if (empty($records)): ?>
              <div class="ir-empty">No complaints match the current filters.</div>
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
                        <th>Action</th>
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
                          <span class="ir-type-badge"><?= ch_label($r['type'] ?? 'General') ?></span>
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
                         <td data-label="Action">
                           <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="window.location.href='?page=complaint-workflow&id=<?= (int)$r['id'] ?>'">View</button>
                         </td>
                       </tr>
                    <?php endforeach; ?>
                 </tbody>
               </table>
             </div>
             <?php if ($totalPages > 1): ?>
             <div class="ir-pagination">
               <span class="ir-pagination-info">
                 Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalRows)) ?> of <?= number_format($totalRows) ?> records
               </span>
               <nav class="ir-pagination-nav" role="navigation" aria-label="Complaint table pagination">
                 <?php
                 $qs = [];
                 if ($fStatus !== '') $qs[] = 'status=' . urlencode($fStatus);
                 if ($fType !== '') $qs[] = 'complaint_type=' . urlencode($fType);
                 if ($fSeverity !== '') $qs[] = 'priority=' . urlencode($fSeverity);
                 if ($fSearch !== '') $qs[] = 'search=' . urlencode($fSearch);
                 if ($fFrom !== '') $qs[] = 'date_from=' . urlencode($fFrom);
                 if ($fTo !== '') $qs[] = 'date_to=' . urlencode($fTo);
                 $baseQs = $baseUrl . ($qs ? '&' . implode('&', $qs) : '');
                 $prevPage = $fPage - 1;
                 $nextPage = $fPage + 1;
                 ?>
                  <a href="<?= htmlspecialchars($baseQs) ?>&cr_page=<?= $prevPage ?>"
                     class="ir-page-btn" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>
                    &lt;
                  </a>
                 <?php
                 $range = 2;
                 $start = max(1, $fPage - $range);
                 $end = min($totalPages, $fPage + $range);
                 for ($i = $start; $i <= $end; $i++):
                 ?>
                 <a href="<?= htmlspecialchars($baseQs) ?>&cr_page=<?= $i ?>"
                    class="ir-page-btn <?= $i === $fPage ? 'ir-page-btn--active' : '' ?>"><?= $i ?></a>
                 <?php endfor; ?>
                  <a href="<?= htmlspecialchars($baseQs) ?>&cr_page=<?= $nextPage ?>"
                     class="ir-page-btn" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>
                    &gt;
                  </a>
               </nav>
             </div>
             <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

       <div class="ir-col ir-col-side">
         <div class="ir-card">
           <div class="ir-card-head">
              <h3>Urgent Actions</h3>
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
                    <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="window.location.href='?page=complaint-workflow&id=<?= (int)$r['id'] ?>'">View</button>
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
</div>
  </section>

<script>
(function(){
  function attachRowClick() {
    document.removeEventListener('click', document._caseRecordsClickHandler);
    document._caseRecordsClickHandler = function(e) {
      var row = e.target.closest('tr[data-rid]');
      if (!row) return;
      if (e.target.closest('button, a, input, select, textarea, form, label')) return;
      var rid = parseInt(row.getAttribute('data-rid'), 10);
      if (rid) {
        window.location.href = '?page=complaint-workflow&id=' + rid;
      }
    };
    document.addEventListener('click', document._caseRecordsClickHandler);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachRowClick);
  } else {
    attachRowClick();
  }
  window.addEventListener('page:loaded', attachRowClick);
})();
</script>


