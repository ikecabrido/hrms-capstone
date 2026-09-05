<?php

$pageTitle = 'Pag-IBIG Monitoring';
$moduleHeaderImage = '/hrms-capstone/modules/compliance/assets/pagibig.webp';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}
if (!$db instanceof PDO) {
  throw new RuntimeException('Database connection is unavailable.');
}

function pagibig_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function pagibig_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$totalEmployees = (int) pagibig_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')");
$submitted      = (int) pagibig_value($db, "SELECT COUNT(*) FROM lc_pagibig_contributions WHERE status = 'Submitted'");
$pending        = (int) pagibig_value($db, "SELECT COUNT(*) FROM lc_pagibig_contributions WHERE status = 'Pending'");
$overdue        = (int) pagibig_value($db, "SELECT COUNT(*) FROM lc_pagibig_contributions WHERE status = 'Overdue'");
$rejected       = (int) pagibig_value($db, "SELECT COUNT(*) FROM lc_pagibig_contributions WHERE status = 'Rejected'");

$nextRemittanceDate = new DateTime('now');
$nextRemittanceDate->modify('last day of next month');
$daysUntilRemittance = (int) $nextRemittanceDate->diff(new DateTime('now'))->days;
$remittanceLabel = 'Next Remittance';
$remittanceDateStr = $nextRemittanceDate->format('F j');
$remittanceDaysStr = $daysUntilRemittance . ' day' . ($daysUntilRemittance !== 1 ? 's' : '') . ' left';

$filter = trim((string)($_GET['filter'] ?? 'all'));

$recentQuery = "
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) AS full_name, 
           e.employee_code AS employee_no, 
           e.email AS employee_email
    FROM lc_pagibig_contributions c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
";

$recentParams = [];
if ($filter === 'submitted') {
    $recentQuery .= " WHERE c.status = 'Submitted'";
} elseif ($filter === 'pending') {
    $recentQuery .= " WHERE c.status = 'Pending'";
} elseif ($filter === 'overdue') {
    $recentQuery .= " WHERE c.status = 'Overdue'";
} elseif ($filter === 'rejected') {
    $recentQuery .= " WHERE c.status = 'Rejected'";
}

$recentQuery .= " ORDER BY c.created_at DESC LIMIT 100";

$recent = pagibig_all($db, $recentQuery, $recentParams);
$recentActivity = array_slice($recent, 0, 6);
?>
<style>
.pagibig-module { padding: 4px 2px 24px; }
.pagibig-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.pagibig-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.pagibig-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.pagibig-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
.pagibig-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.pagibig-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
.pagibig-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.pagibig-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.pagibig-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.pagibig-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.pagibig-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.pagibig-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }
.pagibig-summary-desc { font-size:0.62rem; color:var(--text-400,#8b93a1); margin-top:2px; font-weight:600; }

.pagibig-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.pagibig-col-main { min-width:0; }
.pagibig-col-side { width:380px; flex-shrink:0; }

.pagibig-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.pagibig-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.pagibig-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.pagibig-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.pagibig-card-body { 
  display:flex;
  flex-direction:column;
  max-height: 540px;
  overflow: hidden;
}
.pagibig-table-wrap { 
  overflow: auto;
  flex: 1 1 auto;
}
.pagibig-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.pagibig-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
.pagibig-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); }
.pagibig-table tr:last-child td { border-bottom:none; }
.pagibig-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.pagibig-stamp-submitted { background:rgba(47,158,110,.12); color:#1f7a52; }
.pagibig-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.pagibig-stamp-overdue { background:rgba(214,72,74,.12); color:#a3272a; }
.pagibig-stamp-rejected { background:rgba(214,72,74,.12); color:#a3272a; }

/* Pag-IBIG Pagination */
.pagibig-pagination { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:14px; flex-wrap:wrap; font-size:0.8rem; color:var(--text-600,#4a505a); }
.pagibig-pagination-info { font-size:0.8rem; color:var(--text-600,#4a505a); white-space:nowrap; }
.pagibig-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:8px; overflow:hidden; }
.pagibig-pagination-nav .pagibig-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:none; background:transparent; font-size:0.8rem; color:var(--text-700,#3b4252); cursor:pointer; transition:all .15s ease; }
.pagibig-pagination-nav .pagibig-page-btn:hover { background:rgba(59,130,196,.08); color:var(--info-blue,#3b82c4); }
.pagibig-pagination-nav .pagibig-page-btn:disabled { opacity:0.4; cursor:not-allowed; }
.pagibig-pagination-nav .pagibig-page-btn--active { background:var(--info-blue,#3b82c4); color:#fff; font-weight:600; }
.pagibig-pagination-nav .pagibig-page-ellipsis { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem; color:var(--text-400,#8b93a1); user-select:none; }

.pagibig-bracket-placeholder { display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:28px 16px; }
.pagibig-bracket-placeholder i { font-size:1.6rem; color:var(--text-400,#8b93a1); }
.pagibig-bracket-title { font-size:0.9rem; font-weight:700; color:var(--text-900,#1b2430); }
.pagibig-bracket-desc { font-size:0.78rem; color:var(--text-500,#6b7280); line-height:1.5; }
.pagibig-bracket-link { margin-top:6px; font-size:0.78rem; font-weight:600; color:var(--info-blue,#3b82c4); text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.pagibig-bracket-link:hover { text-decoration:underline; }

.pagibig-activity-list { display:flex; flex-direction:column; }
.pagibig-activity-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid var(--border,#e4e8ee); }
.pagibig-activity-item:last-child { border-bottom:none; }
.pagibig-activity-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:6px; }
.pagibig-activity-dot-submitted { background:#1f7a52; }
.pagibig-activity-dot-pending { background:#d99a2b; }
.pagibig-activity-dot-overdue { background:#d6484a; }
.pagibig-activity-dot-rejected { background:#d6484a; }
.pagibig-activity-body { flex:1; min-width:0; }
.pagibig-activity-text { font-size:0.82rem; font-weight:600; color:var(--text-900,#1b2430); line-height:1.4; }
.pagibig-activity-meta { display:flex; gap:10px; margin-top:2px; font-size:0.72rem; color:var(--text-400,#8b93a1); font-family:var(--font-mono,'IBM Plex Mono',monospace); }
.pagibig-activity-name { font-weight:600; color:var(--text-600,#5a6779); }

.pagibig-finder-card { border-color:var(--seal-gold-light,#f4e6c9); }
.pagibig-finder-form { margin-bottom:14px; }
.pagibig-finder-label { display:block; font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:6px; }
.pagibig-finder-input-wrap { display:flex; align-items:center; gap:8px; border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:10px 12px; background:#fff; transition:border-color .15s ease, box-shadow .15s ease; }
.pagibig-finder-input-wrap:focus-within { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }
.pagibig-finder-prefix { font-weight:700; color:var(--text-900,#1b2430); font-size:0.95rem; }
.pagibig-finder-input { flex:1; border:0; outline:none; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); background:transparent; min-width:0; }
.pagibig-finder-input::placeholder { color:var(--text-400,#8b93a1); font-weight:500; }

.pagibig-finder-result { background:var(--paper,#eef1f5); border:1px solid var(--border,#e4e8ee); border-radius:12px; padding:14px; }
.pagibig-finder-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.pagibig-finder-key { font-size:0.76rem; font-weight:700; color:var(--text-600,#5a6779); text-transform:uppercase; letter-spacing:.4px; }
.pagibig-finder-val { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.pagibig-finder-divider { height:1px; background:var(--border,#e4e8ee); margin:10px 0; }
.pagibig-finder-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.pagibig-finder-cell { display:flex; flex-direction:column; gap:2px; padding:10px; background:#fff; border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.pagibig-finder-cell--total { background:rgba(168,121,31,.08); border-color:rgba(168,121,31,.25); }
.pagibig-finder-cell-label { font-size:0.68rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.3px; }
.pagibig-finder-cell-value { font-size:0.95rem; font-weight:800; color:var(--text-900,#1b2430); }
.pagibig-ee { color:#1c5a8a; }
.pagibig-er { color:#1f7a52; }
.pagibig-total { color:#8a6318; }
.pagibig-finder-empty { display:flex; align-items:center; gap:8px; padding:16px; color:var(--text-400,#8b93a1); font-size:0.82rem; text-align:center; justify-content:center; }
.pagibig-finder-empty i { font-size:1rem; }

.pagibig-email-payroll-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border, #e4e8ee);
  background: #fff;
  color: var(--text-700, #3b4252);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}
.pagibig-email-payroll-btn:hover:not(:disabled) {
  border-color: var(--seal-gold, #a8791f);
  color: var(--seal-gold, #a8791f);
  box-shadow: 0 0 0 3px rgba(168, 121, 31, 0.08);
}
.pagibig-email-payroll-btn:disabled {
  background: var(--paper, #eef1f5);
  border-color: var(--hairline, #dde3ea);
  color: var(--text-400, #8b95a4);
  cursor: not-allowed;
  box-shadow: none;
}

@media (max-width: 980px) {
  .pagibig-row { grid-template-columns:1fr; }
  .pagibig-col-side { width:100%; min-width:0; }
}

/* ============================================
   Pag-IBIG RESPONSIVE OVERRIDES
   ============================================ */

/* Prevent horizontal overflow */
.pagibig-module {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.pagibig-card {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
}

/* Summary bar: tablet/mobile responsive */
@media (max-width: 980px) {
    .pagibig-summary-bar {
        gap: 10px;
    }
    .pagibig-summary-item {
        min-width: 0;
        flex: 1 1 calc(50% - 10px);
        max-width: calc(50% - 10px);
        padding: 14px 16px;
    }
    .pagibig-summary-item > div {
        min-width: 0;
    }
    .pagibig-summary-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    .pagibig-summary-value {
        font-size: 1.1rem;
    }
    .pagibig-summary-label {
        font-size: 0.7rem;
    }
    .pagibig-summary-desc {
        font-size: 0.6rem;
    }
}

@media (max-width: 400px) {
    .pagibig-summary-bar {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .pagibig-summary-item {
        max-width: 100%;
        flex: 1 1 auto;
    }
}

/* Card head wrapping */
.pagibig-card-head {
    flex-wrap: wrap;
    gap: 8px;
}

/* ============================================
   MOBILE CARD TABLE (max-width: 768px)
   ============================================ */
@media (max-width: 768px) {
    .pagibig-card-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .pagibig-table-wrap {
        overflow: visible !important;
        flex: none !important;
    }

    .pagibig-table,
    .pagibig-table thead,
    .pagibig-table tbody,
    .pagibig-table th,
    .pagibig-table td,
    .pagibig-table tr {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .pagibig-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .pagibig-table thead {
        display: none;
    }

    .pagibig-table tr {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .pagibig-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid var(--hairline, #dde3ea);
        text-align: right;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .pagibig-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .pagibig-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        text-align: left;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .pagibig-table td:last-child {
        justify-content: flex-end;
    }

    .pagibig-stamp {
        font-size: 0.72rem;
        padding: 3px 10px;
    }

    .pagibig-email-payroll-btn {
        height: 40px;
        min-width: 40px;
        width: 40px;
        font-size: 1rem;
    }
}

/* ============================================
   PAGINATION RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .pagibig-pagination {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .pagibig-pagination-info {
        text-align: center;
        order: 1;
    }
    .pagibig-pagination-nav {
        order: 0;
    }
}

/* ============================================
   FINDER GRID RESPONSIVE
   ============================================ */
@media (max-width: 400px) {
    .pagibig-finder-grid {
        grid-template-columns: 1fr;
    }
    .pagibig-finder-cell--total {
        order: -1;
    }
}

.pagibig-finder-input {
    min-width: 0;
}

/* Finder result value wrapping */
.pagibig-finder-val {
    overflow-wrap: anywhere;
    word-break: break-word;
}

/* Finder row wrapping */
.pagibig-finder-row {
    flex-wrap: wrap;
    gap: 6px;
}
.pagibig-finder-key {
    flex-shrink: 0;
}
</style>

<div class="pagibig-module">
  <div class="pagibig-summary-bar">
    <a class="pagibig-summary-item <?= $filter === 'all' ? 'pagibig-summary-active' : '' ?>" href="?page=pagibig_monitoring&filter=all">
      <div class="pagibig-summary-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="pagibig-summary-value"><?= number_format($totalEmployees) ?></div>
        <div class="pagibig-summary-label">Total Employees</div>
      </div>
    </a>
    <a class="pagibig-summary-item <?= $filter === 'submitted' ? 'pagibig-summary-active' : '' ?>" href="?page=pagibig_monitoring&filter=submitted">
      <div class="pagibig-summary-icon green"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="pagibig-summary-value"><?= number_format($submitted) ?></div>
        <div class="pagibig-summary-label">Submitted</div>
      </div>
    </a>
    <a class="pagibig-summary-item <?= $filter === 'pending' ? 'pagibig-summary-active' : '' ?>" href="?page=pagibig_monitoring&filter=pending">
      <div class="pagibig-summary-icon amber"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="pagibig-summary-value"><?= number_format($pending) ?></div>
        <div class="pagibig-summary-label">Pending</div>
      </div>
    </a>
     <a class="pagibig-summary-item <?= $filter === 'overdue' ? 'pagibig-summary-active' : '' ?>" href="?page=pagibig_monitoring&filter=overdue">
       <div class="pagibig-summary-icon red"><i class="bi bi-exclamation-triangle"></i></div>
       <div>
         <div class="pagibig-summary-value"><?= number_format($overdue) ?></div>
         <div class="pagibig-summary-label">Overdue</div>
       </div>
     </a>
     <div class="pagibig-summary-item">
      <div class="pagibig-summary-icon seal"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div class="pagibig-summary-value"><?= htmlspecialchars($remittanceDateStr) ?></div>
        <div class="pagibig-summary-label"><?= htmlspecialchars($remittanceLabel) ?></div>
        <div class="pagibig-summary-desc"><?= htmlspecialchars($remittanceDaysStr) ?></div>
      </div>
    </div>
  </div>

  <div class="pagibig-row">
    <div class="pagibig-col-main">
      <div class="pagibig-card">
           <div class="pagibig-card-head">
             <h3><i class="bi bi-list-ul"></i> Recent Pag-IBIG Submissions</h3>
           </div>
        <div class="pagibig-card-body">
          <div class="pagibig-table-wrap">
              <table class="pagibig-table" id="pagibigTable">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Employee No.</th>
                    <th>Contribution No.</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Updated</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recent)): ?>
                    <tr><td colspan="7"><div class="pagibig-empty">No contribution records found.</div></td></tr>
                  <?php else: ?>
                    <?php foreach ($recent as $r):
                      $status = strtolower($r['status'] ?? 'pending');
                      if (in_array($status, ['submitted', 'paid'])) $stampCls = 'submitted';
                      elseif ($status === 'overdue') $stampCls = 'overdue';
                      elseif ($status === 'rejected') $stampCls = 'rejected';
                      else $stampCls = 'pending';
                    ?>
                    <tr>
                      <td data-label="Employee"><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></td>
                      <td data-label="Employee No."><?= htmlspecialchars($r['employee_no'] ?? '—') ?></td>
                      <td data-label="Contribution No."><?= !empty($r['contribution_number']) ? htmlspecialchars($r['contribution_number']) : '—' ?></td>
                      <td data-label="Status">
                        <?php if ($status === 'pending'): ?>
                          <a href="https://www.pagibigfundservices.com/Views/HomePage.aspx" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                            <span class="pagibig-stamp pagibig-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span>
                          </a>
                        <?php else: ?>
                          <span class="pagibig-stamp pagibig-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span>
                        <?php endif; ?>
                      </td>
                      <td data-label="Date Submitted"><?= !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : '—' ?></td>
                      <td data-label="Updated"><?= !empty($r['updated_at']) ? date('M d, Y', strtotime($r['updated_at'])) : '—' ?></td>
                      <td data-label="Action">
                        <?php if (in_array($status, ['pending', 'rejected'])): ?>
                          <a href="https://www.pagibigfundservices.com/Views/HomePage.aspx" target="_blank" rel="noopener noreferrer" class="pagibig-email-payroll-btn" title="Go to Pag-IBIG Employer Portal">
                            <i class="bi bi-box-arrow-up-right"></i>
                          </a>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="pagibig-pagination" id="pagibigRecentPagination" style="display:none;">
            <span class="pagibig-pagination-info" id="pagibigPaginationInfo"></span>
            <nav class="pagibig-pagination-nav" id="pagibigPaginationNav" role="navigation" aria-label="Pag-IBIG table pagination"></nav>
          </div>
        </div>
      </div>
    </div>

    <div class="pagibig-col-side">
      <div class="pagibig-card pagibig-finder-card">
        <div class="pagibig-card-head">
          <h3><i class="bi bi-cash-coin"></i> Pag-IBIG Contribution Reference</h3>
        </div>
        <div class="pagibig-card-body">
          <div class="pagibig-finder-form">
            <label class="pagibig-finder-label" for="pagibigSalaryInput">Monthly Salary</label>
            <div class="pagibig-finder-input-wrap">
              <span class="pagibig-finder-prefix">₱</span>
              <input type="number" id="pagibigSalaryInput" class="pagibig-finder-input" placeholder="25,000" min="0" step="1" inputmode="numeric">
            </div>
          </div>
          <div class="pagibig-finder-result" id="pagibigFinderResult" style="display:none;">
            <div class="pagibig-finder-row">
              <span class="pagibig-finder-key">Employee Rate</span>
              <span class="pagibig-finder-val" id="pagibigFinderERate">—</span>
            </div>
            <div class="pagibig-finder-row">
              <span class="pagibig-finder-key">Employer Rate</span>
              <span class="pagibig-finder-val" id="pagibigFinderRRate">—</span>
            </div>
            <div class="pagibig-finder-divider"></div>
            <div class="pagibig-finder-grid">
              <div class="pagibig-finder-cell">
                <span class="pagibig-finder-cell-label">Employee Share</span>
                <span class="pagibig-finder-cell-value pagibig-ee" id="pagibigFinderEE">₱0.00</span>
              </div>
              <div class="pagibig-finder-cell">
                <span class="pagibig-finder-cell-label">Employer Share</span>
                <span class="pagibig-finder-cell-value pagibig-er" id="pagibigFinderER">₱0.00</span>
              </div>
              <div class="pagibig-finder-cell pagibig-finder-cell--total">
                <span class="pagibig-finder-cell-label">Total</span>
                <span class="pagibig-finder-cell-value pagibig-total" id="pagibigFinderTotal">₱0.00</span>
              </div>
            </div>
          </div>
          <div class="pagibig-finder-empty" id="pagibigFinderEmpty">
            <i class="bi bi-info-circle"></i>
            <span>Enter a monthly salary to see the applicable contribution.</span>
          </div>
        </div>
      </div>

      <div class="pagibig-card pagibig-bracket-card">
        <div class="pagibig-card-head">
          <h3><i class="bi bi-percent"></i> Pag-IBIG Brackets</h3>
        </div>
        <div class="pagibig-card-body">
          <div class="pagibig-bracket-placeholder">
            <i class="bi bi-cash-stack"></i>
            <div class="pagibig-bracket-title">Pag-IBIG Contribution Table</div>
            <div class="pagibig-bracket-desc">View the complete Pag-IBIG contribution schedule by monthly salary credit.</div>
            <a href="?page=government-contribution-brackets&type=pagibig" class="pagibig-bracket-link">Open Pag-IBIG Table <i class="bi bi-arrow-right-short"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var salaryInput = document.getElementById('pagibigSalaryInput');
  var resultBox = document.getElementById('pagibigFinderResult');
  var emptyBox = document.getElementById('pagibigFinderEmpty');
  var eRateLabel = document.getElementById('pagibigFinderERate');
  var rRateLabel = document.getElementById('pagibigFinderRRate');
  var eeLabel = document.getElementById('pagibigFinderEE');
  var erLabel = document.getElementById('pagibigFinderER');
  var totalLabel = document.getElementById('pagibigFinderTotal');

  function formatMoney(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateFinder() {
    var raw = salaryInput.value.replace(/[^0-9]/g, '');
    if (raw === '') {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var salary = parseFloat(raw);
    if (isNaN(salary) || salary <= 0) {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var capped = Math.min(salary, 5000);
    var ee = capped * 0.01;
    var er = capped * 0.02;
    var total = capped * 0.03;
    var note = salary > 5000 ? ' (capped at ₱5,000 base)' : '';

    eRateLabel.textContent = '1%';
    rRateLabel.textContent = '2%';
    eeLabel.textContent = formatMoney(ee);
    erLabel.textContent = formatMoney(er);
    totalLabel.textContent = formatMoney(total) + note;

    resultBox.style.display = 'block';
    emptyBox.style.display = 'none';
  }

  if (salaryInput) {
    salaryInput.addEventListener('input', updateFinder);
    salaryInput.addEventListener('change', updateFinder);
  }

  /* Pag-IBIG Recent Table Pagination */
  (function() {
    var table = document.getElementById('pagibigTable');
    if (!table) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    if (rows.length === 0) return;

    var pageSize = 15;
    var totalItems = rows.length;
    var totalPages = Math.ceil(totalItems / pageSize);
    var currentPage = 1;

    var infoEl = document.getElementById('pagibigPaginationInfo');
    var navEl = document.getElementById('pagibigPaginationNav');
    var paginationEl = document.getElementById('pagibigRecentPagination');

    paginationEl.style.display = 'flex';

    function startIdx() { return (currentPage - 1) * pageSize; }
    function endIdx() { return Math.min(startIdx() + pageSize, totalItems); }

    function renderPage() {
      rows.forEach(function(row, i) {
        row.style.display = (i >= startIdx() && i < endIdx()) ? '' : 'none';
      });
      infoEl.textContent = 'Showing ' + (startIdx() + 1) + '–' + endIdx() + ' of ' + totalItems;
      navEl.innerHTML = '';
      renderNav();
    }

    function renderNav() {
      var prevDisabled = currentPage === 1;
      var nextDisabled = currentPage === totalPages;

      var prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'pagibig-page-btn';
      prevBtn.disabled = prevDisabled;
      prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
      prevBtn.addEventListener('click', function() {
        if (!prevDisabled) { currentPage--; renderPage(); }
      });
      navEl.appendChild(prevBtn);

      var maxVisible = 3;
      var startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
      var endPage = Math.min(totalPages, startPage + maxVisible - 1);
      if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
      }

      if (startPage > 1) {
        appendPageBtn(1);
        if (startPage > 2) appendEllipsis();
      }

      for (var p = startPage; p <= endPage; p++) {
        appendPageBtn(p);
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) appendEllipsis();
        appendPageBtn(totalPages);
      }

      var nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'pagibig-page-btn';
      nextBtn.disabled = nextDisabled;
      nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
      nextBtn.addEventListener('click', function() {
        if (!nextDisabled) { currentPage++; renderPage(); }
      });
      navEl.appendChild(nextBtn);
    }

    function appendPageBtn(pageNum) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pagibig-page-btn' + (pageNum === currentPage ? ' pagibig-page-btn--active' : '');
      btn.textContent = pageNum;
      btn.addEventListener('click', function() {
        currentPage = pageNum;
        renderPage();
      });
      navEl.appendChild(btn);
    }

    function appendEllipsis() {
      var span = document.createElement('span');
      span.className = 'pagibig-page-ellipsis';
      span.textContent = '…';
      navEl.appendChild(span);
    }

    renderPage();
  })();
})();
</script>

