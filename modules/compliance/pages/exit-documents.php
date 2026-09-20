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

$exitDb = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$exitDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$exitDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
    if ($s === 'confirmed') return 'ch-status-stamp--compliant';
    if ($s === 'returned') return 'ch-status-stamp--pending';
    return 'ch-status-stamp--pending';
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
    if ($s === 'completed') return 'ch-status-stamp--compliant';
    return 'ch-status-stamp--pending';
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
?>
<section class="ch-module">
   <?php if (!empty($flash)): ?>
     <?php [$fc, $fm] = explode('|', $flash, 2); ?>
     <div class="ch-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

    <div class="ch-summary-bar">
      <?php
        $buildUrl = function(array $overrides) {
          $params = $_GET;
          foreach ($overrides as $k => $v) {
            if ($v === null || $v === '') {
              unset($params[$k]);
            } else {
              $params[$k] = $v;
            }
          }
          $params['page'] = 'exit-documents';
          return '?' . http_build_query($params);
        };
        $isTotal = $filterOverallStatus === 'All' && $filterArchived !== '1';
        $isPending = $filterOverallStatus === 'Pending' && $filterArchived !== '1';
        $isCompleted = $filterOverallStatus === 'Completed' && $filterArchived !== '1';
      ?>
      <a class="ch-summary-item<?= $isTotal ? ' ch-summary-item--active' : '' ?>" href="<?= htmlspecialchars($buildUrl(['overall_status' => 'All', 'archived' => null])) ?>">
        <div class="ch-summary-icon amber"><i class="bi bi-folder2-open"></i></div>
        <div>
          <div class="ch-summary-value"><?= number_format($totalExits) ?></div>
          <div class="ch-summary-label">Total Exit Requests</div>
        </div>
      </a>
      <a class="ch-summary-item<?= $isPending ? ' ch-summary-item--active' : '' ?>" href="<?= htmlspecialchars($buildUrl(['overall_status' => 'Pending', 'archived' => null])) ?>">
        <div class="ch-summary-icon blue"><i class="bi bi-clock-history"></i></div>
        <div>
          <div class="ch-summary-value"><?= number_format($pendingCount) ?></div>
          <div class="ch-summary-label">Pending</div>
        </div>
      </a>
      <a class="ch-summary-item<?= $isCompleted ? ' ch-summary-item--active' : '' ?>" href="<?= htmlspecialchars($buildUrl(['overall_status' => 'Completed', 'archived' => null])) ?>">
        <div class="ch-summary-icon green"><i class="bi bi-check-circle"></i></div>
        <div>
          <div class="ch-summary-value"><?= number_format($completedCount) ?></div>
          <div class="ch-summary-label">Completed</div>
        </div>
      </a>
    </div>

   <div class="ch-row">
      <div class="ch-col ch-col-main">
         <div class="ch-card">
           <div class="ch-card-head">
             <h3><i class="bi bi-journal-check"></i> Exit Acknowledgement Records</h3>
           </div>
           <div class="ch-card-body">
            <?php if (empty($exits)): ?>
              <div class="ch-empty"><i class="bi bi-emoji-smile"></i> No exit records match the current filters.</div>
            <?php else: ?>
            <div class="ch-table-wrap">
              <table class="ch-table">
                 <thead>
                   <tr>
                     <th class="ch-id-cell">Request #</th>
                     <th class="ch-emp-cell">Employee</th>
                     <th>Department</th>
                     <th>Exit Type</th>
                     <th>Last Working Day</th>
                     <th>Overall Status</th>
                     <th class="ch-action-cell" style="text-align:right;">Actions</th>
                   </tr>
                 </thead>
                 <tbody>
                   <?php foreach ($exits as $e):
                     $overallClass = er_overall_class($e['overall_status']);
                   ?>
                    <tr data-rid="<?= (int)$e['id'] ?>" style="cursor:pointer;">
                      <td class="ch-id-cell" data-label="Request #">
                        <div class="ch-cnum"><?= htmlspecialchars($e['request_number'] ?? 'N/A', ENT_QUOTES) ?></div>
                        <div class="ch-emp-no"><?= !empty($e['last_working_day']) ? date('M d, Y', strtotime($e['last_working_day'])) : '—' ?></div>
                      </td>
                      <td class="ch-emp-cell" data-label="Employee">
                        <div class="ch-emp-name"><?= htmlspecialchars($e['employee_name'] ?? 'N/A', ENT_QUOTES) ?></div>
                        <div class="ch-emp-no"><?= htmlspecialchars($e['position'] ?? '—', ENT_QUOTES) ?></div>
                      </td>
                      <td data-label="Department">
                        <span class="ch-type-badge" style="background:rgba(59,130,196,.08);color:#1c5a8a;border:1px solid rgba(59,130,196,.16);">
                          <?= htmlspecialchars($e['department'] ?? '—', ENT_QUOTES) ?>
                        </span>
                      </td>
                        <td data-label="Exit Type">
                          <span class="ch-type-badge" style="background:rgba(124,58,237,.08);color:#5b21b6;border:1px solid rgba(124,58,237,.16);">
                            <?= htmlspecialchars(er_short_reason($e['reason'] ?? 'N/A'), ENT_QUOTES) ?>
                          </span>
                        </td>
                      <td data-label="Last Working Day">
                        <div class="ch-emp-no"><?= !empty($e['last_working_day']) ? date('M d, Y', strtotime($e['last_working_day'])) : '—' ?></div>
                      </td>
                     <td data-label="Overall Status">
                       <span class="ch-status-stamp <?= $overallClass ?>"><?= htmlspecialchars(er_overall_label($e['overall_status']), ENT_QUOTES) ?></span>
                     </td>
                     <td class="ch-action-cell" data-label="Actions" style="text-align:right;">
                        <button type="button" class="ch-btn-icon" onclick="event.stopPropagation(); window.location.href='?page=exit-acknowledgement&id=<?= (int)$e['id'] ?>'">
                          <i class="bi bi-eye"></i> View
                        </button>
                     </td>
                   </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php if ($totalPages > 1): ?>
              <div class="ch-pagination-wrap">
                <div class="ch-pagination-info">
                  Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $pageSize, $totalCases)) ?> of <?= number_format($totalCases) ?> records
                </div>
                <nav class="ch-pagination" aria-label="Table pagination">
                  <?php if ($currentPage > 1): ?>
                    <a class="ch-page-link" href="<?= htmlspecialchars($buildUrl(['p' => $currentPage - 1])) ?>">&laquo; Prev</a>
                  <?php endif; ?>

                  <?php
                    $range = 2;
                    $startPage = max(1, $currentPage - $range);
                    $endPage = min($totalPages, $currentPage + $range);
                    if ($startPage > 1) {
                      echo '<a class="ch-page-link" href="' . htmlspecialchars($buildUrl(['p' => 1])) . '">1</a>';
                      if ($startPage > 2) echo '<span class="ch-page-dots">&hellip;</span>';
                    }
                    for ($i = $startPage; $i <= $endPage; $i++):
                  ?>
                    <a class="ch-page-link<?= $i === $currentPage ? ' ch-page-link--active' : '' ?>" href="<?= htmlspecialchars($buildUrl(['p' => $i])) ?>"><?= $i ?></a>
                  <?php endfor; ?>

                  <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1) echo '<span class="ch-page-dots">&hellip;</span>'; ?>
                    <a class="ch-page-link" href="<?= htmlspecialchars($buildUrl(['p' => $totalPages])) ?>"><?= $totalPages ?></a>
                  <?php endif; ?>

                  <?php if ($currentPage < $totalPages): ?>
                    <a class="ch-page-link" href="<?= htmlspecialchars($buildUrl(['p' => $currentPage + 1])) ?>">Next &raquo;</a>
                  <?php endif; ?>
                </nav>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
           </div>
         </div>
      </div>
      <div class="ch-col ch-col-side">
         <div class="ch-card" style="flex: 0 0 auto; min-height: auto;">
            <form class="ch-filter-form" method="get" id="erFilterForm" data-skip>
             <input type="hidden" name="page" value="exit-documents">
             <input type="hidden" name="archived" id="erArchived" value="<?= htmlspecialchars($filterArchived, ENT_QUOTES) ?>">
             <div class="ch-filter-row ch-filter-row--primary">
               <div class="ch-field ch-field--grow ch-field--full">
                 <label for="erSearch">Search</label>
                 <input type="text" id="erSearch" name="search" placeholder="Employee, request #..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES) ?>">
               </div>
               <div class="ch-field--ddl-actions">
                 <div class="ch-field">
                   <label for="erOverallStatus">Status</label>
                   <select id="erOverallStatus" name="overall_status" onchange="this.form.requestSubmit()">
                     <?php foreach ($validOverallStatuses as $st): ?>
                       <option value="<?= htmlspecialchars($st, ENT_QUOTES) ?>" <?= $filterOverallStatus === $st ? 'selected' : '' ?>><?= htmlspecialchars($st === 'All' ? 'All Statuses' : ucfirst(str_replace('_', ' ', $st))) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="ch-field">
                   <label for="erLegalStatus">Legal Status</label>
                   <select id="erLegalStatus" name="legal_status" onchange="this.form.requestSubmit()">
                     <?php foreach ($validLegalStatuses as $ls): ?>
                       <option value="<?= htmlspecialchars($ls, ENT_QUOTES) ?>" <?= $filterLegalStatus === $ls ? 'selected' : '' ?>><?= htmlspecialchars($ls === 'All' ? 'All Legal Statuses' : ucfirst($ls)) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="ch-field ch-field--ddl-actions">
                   <button type="submit" class="ch-btn primary">
                     <i class="bi bi-search"></i> Search
                   </button>
                   <a class="ch-btn" href="?page=exit-documents">
                     <i class="bi bi-arrow-counterclockwise"></i> Reset
                   </a>
                 </div>
               </div>
             </div>
           </form>
        </div>

         <div class="ch-card">
           <div class="ch-card-head">
             <h3><i class="bi bi-lightning"></i> Quick Filters</h3>
           </div>
           <div style="display:flex; flex-direction:column; gap:8px;">
             <a href="<?= htmlspecialchars($buildUrl(['overall_status' => 'Pending', 'archived' => null])) ?>" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:rgba(59,130,196,.06); border:1px solid rgba(59,130,196,.18); text-decoration:none; color:#1c5a8a; transition:all .15s ease;">
               <i class="bi bi-clock-history" style="font-size:1.1rem;"></i>
               <div>
                 <div style="font-weight:700; font-size:0.82rem;">Pending Verifications</div>
                 <div style="font-size:0.72rem; opacity:0.85;"><?= number_format($pendingCount) ?> exit requests awaiting review</div>
               </div>
             </a>
             <a href="<?= htmlspecialchars($buildUrl(['overall_status' => 'Completed', 'archived' => null])) ?>" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:rgba(47,158,110,.06); border:1px solid rgba(47,158,110,.18); text-decoration:none; color:#1f7a52; transition:all .15s ease;">
               <i class="bi bi-check-circle" style="font-size:1.1rem;"></i>
               <div>
                 <div style="font-weight:700; font-size:0.82rem;">Completed Exits</div>
                 <div style="font-size:0.72rem; opacity:0.85;"><?= number_format($completedCount) ?> successfully processed exits</div>
               </div>
             </a>
             <a href="<?= htmlspecialchars($buildUrl(['archived' => '1'])) ?>" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:rgba(124,58,237,.06); border:1px solid rgba(124,58,237,.18); text-decoration:none; color:#5b21b6; transition:all .15s ease;">
               <i class="bi bi-archive" style="font-size:1.1rem;"></i>
               <div>
                 <div style="font-weight:700; font-size:0.82rem;">Archived Records</div>
                 <div style="font-size:0.72rem; opacity:0.85;"><?= number_format($archivedCount) ?> records in archive</div>
               </div>
             </a>
           </div>
         </div>
      </div>
    </div>
</section>

<style>
.ch-module { padding: 4px 2px 24px; }
.ch-summary-bar { display:flex; gap:14px; margin-bottom:32px; flex-wrap:wrap; }
.ch-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.ch-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.ch-summary-item--active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; border-color:var(--info-blue,#3b82c4); }
.ch-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.ch-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.ch-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ch-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.ch-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.ch-summary-icon.purple { background:rgba(124,58,237,.10); color:#5b21b6; }
.ch-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.ch-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

.ch-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.ch-col-main { min-width:0; }
.ch-col-side { width:380px; flex-shrink:0; }

.ch-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.ch-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.ch-card-head h3 { margin:0; font-size:0.88rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ch-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.76rem; }

.ch-card-body { display:flex; flex-direction:column; max-height: 540px; overflow: hidden; }
.ch-table-wrap { overflow: auto; flex: 1 1 auto; }
.ch-table { width:100%; border-collapse:collapse; font-size:0.72rem; color: #1b2430; }
.ch-table th { text-align:left; padding:10px 12px; font-size:0.66rem; font-weight:700; text-transform:uppercase; color:#8b93a1; border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; vertical-align: middle; }
.ch-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align: middle; color: #1b2430; min-width: 0; }
.ch-table tr:last-child td { border-bottom:none; }
.ch-table .ch-id-cell { width: 120px; }
.ch-table .ch-emp-cell { width: 160px; }
.ch-table .ch-action-cell { width: 50px; }

.ch-flash { padding: 10px 14px; border-radius: 10px; font-size: 0.76rem; font-weight: 600; margin-bottom: 14px; }
.ch-flash.success { background: rgba(47, 158, 110, .10); color: #1f7a52; border: 1px solid rgba(47, 158, 110, .22); }
.ch-flash.error { background: rgba(214, 72, 74, .10); color: #a3272a; border: 1px solid rgba(214, 72, 74, .22); }

.ch-cnum { font-weight: 600; color: #2b3340; font-size: 0.68rem; }
.ch-emp-name { font-weight: 600; color: #2b3340; font-size: 0.68rem; }
.ch-emp-no { font-size: 0.64rem; color: #8b93a1; }
.ch-type-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.62rem; font-weight: 700; white-space: nowrap; }
.ch-type-badge--long { white-space: normal; max-width: 180px; line-height: 1.35; }

.ch-status-stamp { display: inline-block; font-size: 0.62rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
.ch-status-stamp--compliant { background: rgba(47, 158, 110, .12); color: #1f7a52; }
.ch-status-stamp--info { background: rgba(59, 130, 196, .12); color: #1c5a8a; }
.ch-status-stamp--pending { background: rgba(217, 154, 43, .14); color: #a86b13; }

.ch-btn-icon {
  display: inline-flex; align-items: center; justify-content: center; gap: 3px;
  padding: 2px 4px; border-radius: 5px; border: 1px solid var(--border, #e4e8ee);
  background: #fff; color: #5b6472;
  cursor: pointer; text-decoration: none; white-space: nowrap;
  font-size: 0.62rem; font-weight: 600;
  transition: background 150ms ease, border-color 150ms ease, color 150ms ease, transform 80ms ease;
}
.ch-btn-icon:hover { background: #f3f5f9; border-color: #d3d9e2; color: #2b3340; }
.ch-btn-icon:active { transform: translateY(1px); }
.ch-btn-icon .bi { font-size: 0.82rem; line-height: 1; color: inherit; }
.ch-action-cell { text-align: right; }

.ch-filter-form { display: flex; flex-direction: column; gap: 10px; }
.ch-filter-row { display: grid; gap: 10px; }
.ch-filter-row--primary { grid-template-columns: 1fr; }
.ch-filter-row--primary > .ch-field--ddl-actions { grid-template-columns: 1fr 1fr auto; }
.ch-filter-row--advanced { grid-template-columns: 1fr 1fr 1fr; }
@media (max-width: 1100px) {
  .ch-filter-row--primary { grid-template-columns: 1fr; }
  .ch-filter-row--primary > .ch-field--ddl-actions { grid-template-columns: 1fr 1fr; }
  .ch-filter-row--advanced { grid-template-columns: 1fr 1fr; }
  .ch-row { grid-template-columns: 1fr; }
  .ch-col-side { position: static; width: auto; }
}
@media (max-width: 600px) {
  .ch-filter-row--primary { grid-template-columns: 1fr; }
  .ch-filter-row--primary > .ch-field--ddl-actions { grid-template-columns: 1fr; }
  .ch-filter-row--advanced { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .ch-summary-bar { gap: 10px; }
  .ch-summary-item { min-width: 140px; padding: 14px 16px; }
  .ch-summary-value { font-size: 1rem; }
  .ch-summary-label { font-size: 0.68rem; }
  .ch-table thead { display: none; }
  .ch-table tbody { display: block; }
  .ch-table tr { display: block; margin-bottom: 12px; border: 1px solid var(--border,#e4e8ee); border-radius: 10px; background: #fff; padding: 4px 0; box-shadow: 0 1px 2px rgba(13,27,46,.04); }
  .ch-table td { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; padding: 8px 12px; border-bottom: 1px solid var(--border,#e4e8ee); text-align: left; }
  .ch-table td:last-child { border-bottom: none; }
  .ch-table td::before { content: attr(data-label); font-size: 0.64rem; font-weight: 700; text-transform: uppercase; color: #8b93a1; letter-spacing: 0.02em; }
  .ch-table .ch-id-cell,
  .ch-table .ch-emp-cell,
  .ch-table .ch-action-cell { width: auto; }
  .ch-btn-icon { padding: 8px 14px; font-size: 0.72rem; border-radius: 8px; }
  .ch-btn-icon .bi { font-size: 1.1rem; }
  .ch-pagination-wrap { flex-direction: column; align-items: stretch; gap: 8px; }
  .ch-pagination { justify-content: center; }
}
@media (max-width: 480px) {
  .ch-summary-bar { flex-direction: column; gap: 8px; }
  .ch-summary-item { min-width: auto; width: 100%; }
  .ch-filter-row--primary > .ch-field--ddl-actions { grid-template-columns: 1fr; }
  .ch-field--ddl-actions { grid-template-columns: 1fr; }
  .ch-table td { padding: 10px 12px; }
  .ch-table td::before { font-size: 0.66rem; }
}
.ch-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.ch-field--grow { flex: 1; min-width: 0; }
.ch-field--full { grid-column: 1 / -1; }
.ch-field--ddl-actions {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 10px;
  grid-column: 1 / -1;
  align-items: end;
  min-width: 0;
}
.ch-field label {
  font-size: 0.68rem; font-weight: 600; color: var(--text-700,#3b4252); margin-bottom: 3px;
  display: block; text-transform: none; letter-spacing: normal;
}
.ch-field input, .ch-field select {
  width: 100%; padding: 7px 10px; border: 1px solid var(--border,#e4e8ee); border-radius: 8px;
  font-size: 0.78rem; color: var(--text-900,#1b2430); background: var(--card-bg,#fff);
  transition: border-color 150ms ease, box-shadow 150ms ease;
  box-sizing: border-box;
}
.ch-field input:focus, .ch-field select:focus {
  outline: none; border-color: var(--info-blue,#3b82c4);
  box-shadow: 0 0 0 3px rgba(59,130,196,0.12);
}
.ch-btn {
  font-size: 0.62rem; font-weight: 600;
  padding: 3px 9px; border-radius: 6px; border: 1px solid var(--border, #e4e8ee);
  background: var(--card-bg, #fff); color: var(--text-600, #5b6472);
  cursor: pointer; text-decoration: none; white-space: nowrap;
  transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
  display: inline-flex; align-items: center; gap: 4px;
}
.ch-btn:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); }
.ch-btn.primary { background: var(--info-blue, #3b82c4); color: #fff; border-color: var(--info-blue, #3b82c4); }
.ch-btn.primary:hover { background: #1c5a8a; border-color: #1c5a8a; color: #fff; }
.ch-btn.ghost { background: transparent; border-color: transparent; color: var(--text-600, #5b6472); }
.ch-btn.ghost:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); }

.ch-reminder-list { display: flex; flex-direction: column; gap: 10px; }
.ch-reminder-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px; border: 1px solid var(--border, #e4e8ee); border-radius: 8px; background: #fff; transition: background-color 160ms ease, border-color 160ms ease; }
.ch-reminder-row:hover { background: var(--bg-soft, #f8f9fb); border-color: var(--border-strong, #d3d9e2); }
.ch-reminder-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.ch-reminder-text strong { font-size: 0.68rem; color: var(--text-900, #1b2430); font-weight: 600; }
.ch-reminder-text span { font-size: 0.64rem; color: var(--text-500, #8b93a1); }
.ch-reminder-step { font-size: 0.62rem; color: var(--text-600, #5b6472); font-weight: 600; }
.ch-reminder-actions { display: flex; gap: 6px; flex-shrink: 0; }
.ch-btn-xs { padding: 2px 8px; font-size: 0.6rem; border-radius: 6px; }
.ch-stamp-overdue { background: rgba(214,72,74,0.10); color: #a3272a; }

.ch-pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; padding-top:10px; border-top:1px solid rgba(59,130,196,.1); flex-wrap:wrap; }
.ch-pagination-info { font-size:0.78rem; color:var(--text-500,#6b7280); font-weight:500; }
.ch-pagination { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.ch-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 9px; border-radius:10px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); color:var(--text-700,#3b4252); font-size:0.8rem; font-weight:600; text-decoration:none; cursor:pointer; transition: background 120ms ease, border-color 120ms ease, color 120ms ease; }
.ch-page-link:hover { background:var(--bg-soft,#f3f5f9); border-color:var(--border-strong,#d3d9e2); color:var(--text-900,#1b2430); }
.ch-page-link--active { background:var(--info-blue,#3b82c4); border-color:var(--info-blue,#3b82c4); color:#fff; }
.ch-page-dots { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; color:var(--text-400,#8b93a1); font-size:0.8rem; }
</style>

<script>
(function(){
  document.querySelectorAll('tr[data-rid]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, form, label, .ch-btn-icon')) return;
      var rid = parseInt(row.getAttribute('data-rid'), 10);
      if (rid) {
        window.location.href = '?page=exit-acknowledgement&id=' + rid;
      }
    });
  });
})();
</script>

