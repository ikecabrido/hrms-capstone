<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

$pageTitle = 'Exit Management';
$activeGroup = 'Exit Acknowledgement';
$activePage = 'exit-documents';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$exitDb = $db;

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

function er_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function er_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$totalExits      = (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_resignations", 0) + (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_terminations", 0);
$pendingCount    = (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_resignations WHERE (hr_approved_at IS NULL OR legal_approved_at IS NULL) AND archived_from_status IS NULL", 0) + (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_terminations WHERE approved_at IS NULL", 0);
$completedCount  = (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_resignations WHERE hr_approved_at IS NOT NULL AND legal_approved_at IS NOT NULL AND archived_from_status IS NULL", 0) + (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_terminations WHERE approved_at IS NOT NULL", 0);
$archivedCount   = (int) er_value($exitDb, "SELECT COUNT(*) FROM exit_resignations WHERE archived_from_status IS NOT NULL", 0);

$validOverallStatuses = ['All', 'Pending', 'Completed'];
$filterOverallStatus = in_array($_GET['overall_status'] ?? '', $validOverallStatuses, true) ? $_GET['overall_status'] : 'All';

$validLegalStatuses = ['All', 'Pending', 'Confirmed', 'Returned'];
$filterLegalStatus = in_array($_GET['legal_status'] ?? '', $validLegalStatuses, true) ? $_GET['legal_status'] : 'All';

$searchQuery = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$em_departments = er_all($exitDb, "SELECT department_id AS id, department_name AS department FROM em_departments WHERE status = 'Active' ORDER BY department_name ASC");
$filterDept = $_GET['department'] ?? '';
$filterArchived = $_GET['archived'] ?? '';

$where = [];
$params = [];

if ($filterOverallStatus !== 'All') {
    if ($filterOverallStatus === 'Completed') {
        $where[] = "er.hr_approved_at IS NOT NULL AND er.legal_approved_at IS NOT NULL AND er.archived_from_status IS NULL";
    } elseif ($filterOverallStatus === 'Pending') {
        $where[] = "(er.hr_approved_at IS NULL OR er.legal_approved_at IS NULL) AND er.archived_from_status IS NULL";
    }
}
if ($filterLegalStatus !== 'All') {
    if ($filterLegalStatus === 'Confirmed') {
        $where[] = 'er.legal_approved_at IS NOT NULL';
    } elseif ($filterLegalStatus === 'Returned') {
        $where[] = "er.status = 'rejected_by_legal'";
    } elseif ($filterLegalStatus === 'Pending') {
        $where[] = 'er.legal_approved_at IS NULL AND er.status != \'rejected_by_legal\'';
    }
}
if ($filterDept !== '') {
    $where[] = 'd.department_name = :department';
    $params[':department'] = $filterDept;
}
if ($dateFrom !== '') {
    $where[] = 'DATE(er.last_working_date) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(er.last_working_date) <= :date_to';
    $params[':date_to'] = $dateTo;
}
if ($filterArchived === '1') {
    $where[] = 'er.archived_from_status IS NOT NULL';
}
if ($searchQuery !== '') {
    $where[] = "(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search OR CONCAT('RES-', LPAD(er.id, 6, '0')) LIKE :search OR p.position_name LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$exits = er_all($exitDb, "
    SELECT er.id, 
           CONCAT('RES-', LPAD(er.id, 6, '0')) AS request_number,
           er.employee_id, er.created_at AS date_filed, er.last_working_date AS last_working_day,
           er.reason, 
           CASE er.status 
             WHEN 'pending_review' THEN 'Pending Review'
             WHEN 'pending_legal_review' THEN 'Pending Legal Review'
             WHEN 'approved' THEN 'Approved'
             WHEN 'rejected' THEN 'Rejected'
             WHEN 'rejected_by_legal' THEN 'Rejected by Legal'
             WHEN 'withdrawn' THEN 'Withdrawn'
           END AS type_of_separation,
           '' AS immediate_supervisor,
           COALESCE(er.comments, er.review_remarks, er.hr_approval_comments, er.legal_approval_comments) AS separation_notes,
           CASE 
             WHEN er.archived_from_status IS NOT NULL THEN 'Archived'
             WHEN er.status = 'approved' AND er.hr_approved_at IS NOT NULL AND er.legal_approved_at IS NOT NULL THEN 'Completed'
             ELSE 'Pending'
           END AS overall_status,
           CASE 
             WHEN er.legal_approved_at IS NOT NULL THEN 'Confirmed'
             WHEN er.status = 'rejected_by_legal' THEN 'Returned'
             ELSE 'Pending'
           END AS legal_status,
           er.legal_approved_at AS confirmed_at,
           er.legal_approved_by AS confirmed_by,
           COALESCE(er.review_remarks, er.legal_approval_comments) AS legal_remarks,
           er.approved_at, 
           NULL AS recruitment_status, 
           NULL AS recruitment_notified_at,
           CASE WHEN er.archived_from_status IS NOT NULL THEN 1 ELSE 0 END AS archived,
           NULL AS archived_at,
           er.created_at, er.updated_at,
           er.submitted_by AS created_by,
           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
           e.employee_code AS employee_no,
           d.department_name AS department,
           p.position_name AS position
    FROM exit_resignations er
    LEFT JOIN em_employees e ON e.employee_id = er.employee_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    $whereSql
    ORDER BY er.created_at DESC
", $params);

$terminationWhere = [];
$terminationParams = [];
if ($filterDept !== '') {
    $terminationWhere[] = 'd.department_name = :department';
    $terminationParams[':department'] = $filterDept;
}
if ($dateFrom !== '') {
    $terminationWhere[] = 'DATE(t.effective_date) >= :date_from';
    $terminationParams[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $terminationWhere[] = 'DATE(t.effective_date) <= :date_to';
    $terminationParams[':date_to'] = $dateTo;
}
if ($searchQuery !== '') {
    $terminationWhere[] = "(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :search OR CONCAT('TER-', LPAD(t.id, 6, '0')) LIKE :search OR p.position_name LIKE :search)";
    $terminationParams[':search'] = '%' . $searchQuery . '%';
}
$terminationWhereSql = $terminationWhere ? 'WHERE ' . implode(' AND ', $terminationWhere) : '';

$terminations = er_all($exitDb, "
    SELECT 
        t.id,
        CONCAT('TER-', LPAD(t.id, 6, '0')) AS request_number,
        t.employee_id,
        t.created_at AS date_filed,
        t.effective_date AS last_working_day,
        t.termination_reason AS reason,
        '' AS immediate_supervisor,
        t.comments AS separation_notes,
        CASE 
            WHEN t.approved_at IS NOT NULL THEN 'Completed'
            ELSE 'Pending'
        END AS overall_status,
        'Pending' AS legal_status,
        NULL AS confirmed_at,
        NULL AS confirmed_by,
        NULL AS legal_remarks,
        t.approved_at, 
        NULL AS recruitment_status, 
        NULL AS recruitment_notified_at,
        0 AS archived,
        NULL AS archived_at,
        t.created_at, t.updated_at,
        t.submitted_by AS created_by,
        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
        e.employee_code AS employee_no,
        d.department_name AS department,
        p.position_name AS position
    FROM exit_terminations t
    LEFT JOIN em_employees e ON e.employee_id = t.employee_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    $terminationWhereSql
    ORDER BY t.created_at DESC
", $terminationParams);

$exits = array_merge($exits, $terminations);

usort($exits, function($a, $b) {
    return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
});

if ($filterOverallStatus !== 'All') {
    if ($filterOverallStatus === 'Completed') {
        $exits = array_filter($exits, function($e) {
            return $e['overall_status'] === 'Completed';
        });
    } elseif ($filterOverallStatus === 'Pending') {
        $exits = array_filter($exits, function($e) {
            return $e['overall_status'] !== 'Completed';
        });
    }
}
if ($filterLegalStatus !== 'All') {
    if ($filterLegalStatus === 'Confirmed') {
        $exits = array_filter($exits, function($e) {
            return $e['legal_status'] === 'Confirmed';
        });
    } elseif ($filterLegalStatus === 'Returned') {
        $exits = array_filter($exits, function($e) {
            return $e['legal_status'] === 'Returned';
        });
    } elseif ($filterLegalStatus === 'Pending') {
        $exits = array_filter($exits, function($e) {
            return $e['legal_status'] === 'Pending';
        });
    }
}
if ($filterArchived === '1') {
    $exits = array_filter($exits, function($e) {
        return $e['archived'] == 1;
    });
} elseif ($filterArchived === '0') {
    $exits = array_filter($exits, function($e) {
        return $e['archived'] != 1;
    });
}

$totalCases = count($exits);

$pageSize = 10;
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$totalPages = (int) ceil($totalCases / $pageSize);
if ($totalPages < 1) $totalPages = 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;
$offset = ($currentPage - 1) * $pageSize;
$exits = array_slice($exits, $offset, $pageSize);

function er_legal_class(string $s): string {
    $s = strtolower($s);
    if ($s === 'confirmed') return 'ir-status-stamp--compliant';
    if ($s === 'returned') return 'ir-status-stamp--pending';
    return 'ir-status-stamp--pending';
}
function er_legal_label(string $s): string {
    $map = [
        'pending'    => 'Pending Verification',
        'confirmed'  => 'Verified',
        'returned'   => 'Returned',
    ];
    return $map[strtolower($s)] ?? ucfirst($s);
}
function er_overall_class(string $s): string {
    $s = strtolower($s);
    if ($s === 'completed') return 'ir-status-stamp--compliant';
    return 'ir-status-stamp--pending';
}
function er_overall_label(string $s): string {
    $map = [
        'pending'      => 'Pending',
        'completed'    => 'Completed',
    ];
    return $map[strtolower($s)] ?? ucfirst(str_replace('_', ' ', $s));
}
function er_short_reason(string $text): string {
    $words = preg_split('/\s+/', trim($text));
    if (count($words) <= 2) {
        return $text;
    }
    return $words[0] . ' ' . $words[1];
}

$urgentExits = array_slice(array_filter($exits, function($e) {
    return $e['legal_status'] === 'Pending';
}), 0, 5);

$baseUrl = '?page=exit-documents';
?>
<section class="ir-module">
   <?php if (!empty($flash)): ?>
      <?php [$fc, $fm] = explode('|', $flash, 2); ?>
      <div class="ir-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

    <div class="ir-summary-bar">
       <a class="ir-summary-item <?= $filterOverallStatus === 'All' && $filterArchived !== '1' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>">
         <div>
           <div class="ir-summary-value"><?= number_format($totalExits) ?></div>
           <div class="ir-summary-label">Total Exit Requests</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $filterOverallStatus === 'Pending' && $filterArchived !== '1' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&overall_status=Pending&legal_status=All&search=&date_from=&date_to=&department=&archived=">
         <div>
           <div class="ir-summary-value"><?= number_format($pendingCount) ?></div>
           <div class="ir-summary-label">Pending</div>
         </div>
       </a>
       <a class="ir-summary-item <?= $filterOverallStatus === 'Completed' && $filterArchived !== '1' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&overall_status=Completed&legal_status=All&search=&date_from=&date_to=&department=&archived=">
         <div>
           <div class="ir-summary-value"><?= number_format($completedCount) ?></div>
           <div class="ir-summary-label">Completed</div>
         </div>
       </a>
    </div>

   <div class="ir-row">
      <div class="ir-col ir-col-main">
         <div class="ir-card">
           <div class="ir-card-head">
               <h3>Exit Acknowledgement Records</h3>
           </div>
           <div class="ir-card-body">
            <?php if (empty($exits)): ?>
               <div class="ir-empty">No exit records match the current filters.</div>
            <?php else: ?>
            <div class="ir-table-wrap">
              <table class="ir-table">
                  <thead>
                    <tr>
                      <th class="ir-id-cell">Request #</th>
                      <th class="ir-emp-cell">Employee</th>
                      <th>Department</th>
                      <th>Exit Type</th>
                      <th>Last Working Day</th>
                       <th>Overall Status</th>
                     </tr>
                  </thead>
                 <tbody>
                    <?php foreach ($exits as $e):
                      $overallClass = er_overall_class($e['overall_status']);
                    ?>
                    <tr data-rid="<?= (int)$e['id'] ?>" style="cursor:pointer;">
                      <td class="ir-id-cell" data-label="Request #">
                        <div class="ir-cnum"><?= htmlspecialchars($e['request_number'] ?? 'N/A', ENT_QUOTES) ?></div>
                        <div class="ir-emp-no"><?= !empty($e['last_working_day']) ? date('M d, Y', strtotime($e['last_working_day'])) : '—' ?></div>
                      </td>
                      <td class="ir-emp-cell" data-label="Employee">
                        <div class="ir-emp-name"><?= htmlspecialchars($e['employee_name'] ?? 'N/A', ENT_QUOTES) ?></div>
                        <div class="ir-emp-no"><?= htmlspecialchars($e['position'] ?? '—', ENT_QUOTES) ?></div>
                      </td>
                       <td data-label="Department">
                         <span class="ir-type-badge" style="background:rgba(59,130,196,.08);color:#1c5a8a;border:1px solid rgba(59,130,196,.15);">
                           <?= htmlspecialchars($e['department'] ?? '—', ENT_QUOTES) ?>
                         </span>
                       </td>
                         <td data-label="Exit Type">
                           <span class="ir-type-badge" style="background:rgba(124,58,237,.08);color:#5b21b6;border:1px solid rgba(124,58,237,.15);">
                             <?= htmlspecialchars(er_short_reason($e['reason'] ?? 'N/A'), ENT_QUOTES) ?>
                           </span>
                         </td>
                      <td data-label="Last Working Day">
                        <div class="ir-emp-no"><?= !empty($e['last_working_day']) ? date('M d, Y', strtotime($e['last_working_day'])) : '—' ?></div>
                      </td>
                      <td data-label="Overall Status">
                        <span class="ir-status-stamp ir-status-stamp--<?= $overallClass ?>"><?= htmlspecialchars(er_overall_label($e['overall_status']), ENT_QUOTES) ?></span>
                      </td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
          <div class="ir-pagination-wrap">
            <div class="ir-pagination-info">
              Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $pageSize, $totalCases)) ?> of <?= number_format($totalCases) ?> records
            </div>
            <nav class="ir-pagination" aria-label="Table pagination">
              <?php if ($currentPage > 1): ?>
                <a class="ir-page-link" href="<?= htmlspecialchars($baseUrl . '&p=' . ($currentPage - 1) . '&overall_status=' . urlencode($filterOverallStatus) . '&legal_status=' . urlencode($filterLegalStatus) . '&search=' . urlencode($searchQuery) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&department=' . urlencode($filterDept) . '&archived=' . urlencode($filterArchived)) ?>">&laquo; Prev</a>
              <?php endif; ?>

              <?php
                $range = 2;
                $startPage = max(1, $currentPage - $range);
                $endPage = min($totalPages, $currentPage + $range);
                if ($startPage > 1) {
                  echo '<a class="ir-page-link" href="' . htmlspecialchars($baseUrl . '&p=1&overall_status=' . urlencode($filterOverallStatus) . '&legal_status=' . urlencode($filterLegalStatus) . '&search=' . urlencode($searchQuery) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&department=' . urlencode($filterDept) . '&archived=' . urlencode($filterArchived)) . '">1</a>';
                  if ($startPage > 2) echo '<span class="ir-page-dots">&hellip;</span>';
                }
                for ($i = $startPage; $i <= $endPage; $i++):
              ?>
                <a class="ir-page-link<?= $i === $currentPage ? ' ir-page-link--active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '&p=' . $i . '&overall_status=' . urlencode($filterOverallStatus) . '&legal_status=' . urlencode($filterLegalStatus) . '&search=' . urlencode($searchQuery) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&department=' . urlencode($filterDept) . '&archived=' . urlencode($filterArchived)) ?>"><?= $i ?></a>
              <?php endfor; ?>

              <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1) echo '<span class="ir-page-dots">&hellip;</span>'; ?>
                <a class="ir-page-link" href="<?= htmlspecialchars($baseUrl . '&p=' . $totalPages . '&overall_status=' . urlencode($filterOverallStatus) . '&legal_status=' . urlencode($filterLegalStatus) . '&search=' . urlencode($searchQuery) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&department=' . urlencode($filterDept) . '&archived=' . urlencode($filterArchived)) ?>"><?= $totalPages ?></a>
              <?php endif; ?>

              <?php if ($currentPage < $totalPages): ?>
                <a class="ir-page-link" href="<?= htmlspecialchars($baseUrl . '&p=' . ($currentPage + 1) . '&overall_status=' . urlencode($filterOverallStatus) . '&legal_status=' . urlencode($filterLegalStatus) . '&search=' . urlencode($searchQuery) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&department=' . urlencode($filterDept) . '&archived=' . urlencode($filterArchived)) ?>">Next &raquo;</a>
              <?php endif; ?>
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
             <span class="ir-stamp ir-stamp-overdue" style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:999px;white-space:nowrap;"><?= number_format($pendingCount) ?></span>
           </div>
           <div class="ir-reminder-list ir-reminder-list--compact">
            <?php if ($pendingCount > 0): ?>
              <?php
                $pendingExits = array_slice(array_filter($exits, function($e) {
                  return $e['legal_status'] === 'Pending';
                }), 0, 5);
              ?>
              <?php foreach ($pendingExits as $e): ?>
                <div class="ir-reminder-row">
                  <div class="ir-reminder-text">
                    <strong><?= htmlspecialchars(er_overall_label($e['overall_status']), ENT_QUOTES) ?></strong>
                    <span><?= htmlspecialchars($e['employee_name'], ENT_QUOTES) ?> — <?= htmlspecialchars($e['request_number'], ENT_QUOTES) ?></span>
                    <span class="ir-reminder-step"><?= htmlspecialchars(er_short_reason($e['reason'] ?? 'N/A'), ENT_QUOTES) ?></span>
                  </div>
                   <div class="ir-reminder-actions">
                      <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="window.location.href='?page=exit-acknowledgement&id=<?= (int)$e['id'] ?>'">View</button>
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

  .ir-card-body { display:flex; flex-direction:column; max-height:520px; overflow:hidden; }
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
    .ir-card-body { max-height:none; }
    .ir-table thead { display:none; }
    .ir-table, .ir-table tbody, .ir-table tr, .ir-table td { display:block; width:100%; }
    .ir-table tr { background:#fff; border:1px solid #dde3ea; border-radius:10px; padding:10px; margin-bottom:10px; }
    .ir-table td { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:6px 0; border-bottom:1px solid #dde3ea; text-align:right; }
    .ir-table td:last-child { border-bottom:none; padding-bottom:0; }
    .ir-table td::before { content:attr(data-label); font-size:.6rem; font-weight:700; text-transform:uppercase; color:#8b93a1; text-align:left; flex-shrink:0; }
    .ir-table .ir-id-cell, .ir-table .ir-emp-cell { width:auto; }
    .ir-btn { padding:4px 8px; font-size:.66rem; }
  }
  .ir-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; flex-wrap:wrap; font-size:11.5px; color:#64748b; line-height:1.2; }
  .ir-pagination-info { font-size:11.5px; color:#64748b; white-space:nowrap; line-height:1.2; }
  .ir-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:transparent; border:1px solid #e4e8ee; border-radius:6px; overflow:hidden; flex-wrap:wrap; padding:2px; }
  .ir-pagination-nav .ir-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border:0; background:transparent; font-size:11.5px; font-weight:600; color:#334155; cursor:pointer; text-decoration:none; transition:background-color .1s ease; line-height:1.2; }
  .ir-pagination-nav .ir-page-btn:hover:not(.ir-page-btn--active) { background:#f1f5f9; }
  .ir-pagination-nav .ir-page-btn[aria-disabled="true"] { opacity:0.35; cursor:not-allowed; pointer-events:none; }
  .ir-pagination-nav .ir-page-btn--active { background:#2563eb; color:#fff; font-weight:600; }
  .ir-pagination-nav .ir-page-ellipsis { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; font-size:11.5px; color:#64748b; line-height:1.2; }
  </style>

<script>
(function(){
  window.irOpenDetail = function(id) {
    window.location.href = '?page=exit-acknowledgement&id=' + encodeURIComponent(id);
  };

  document.querySelectorAll('tr[data-rid]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, form, label')) return;
      irOpenDetail(parseInt(row.getAttribute('data-rid'), 10));
    });
  });
})();
</script>
