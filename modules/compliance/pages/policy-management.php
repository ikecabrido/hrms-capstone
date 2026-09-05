<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Policy Management';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$policy = new Policy($db);
$currentUserId = $policy->getCurrentUserEmployeeId();

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$policyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stats = $policy->getDashboardStats();
$filters = [
    'status' => isset($_GET['status']) ? trim((string) $_GET['status']) : '',
    'category_id' => isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0,
    'search' => isset($_GET['search']) ? trim((string) $_GET['search']) : '',
    'ack_status' => isset($_GET['ack_status']) ? trim((string) $_GET['ack_status']) : '',
];
$perPage = 5;
$page = isset($_GET['policy_page']) ? max(1, (int) $_GET['policy_page']) : 1;
$offset = ($page - 1) * $perPage;
$totalPoliciesCount = $policy->getPoliciesCount($filters);
$totalPages = (int) ceil($totalPoliciesCount / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$policies = $policy->getPolicies($filters, $perPage, $offset);
$categories = $policy->getCategories();

$totalPolicies = (int) ($stats['policies']['total_policies'] ?? 0);
$published = (int) ($stats['policies']['published'] ?? 0);
$draft = (int) ($stats['policies']['draft'] ?? 0);
$ackCount = (int) ($stats['assignments']['acknowledged'] ?? 0);
$pendingCount = (int) ($stats['assignments']['pending'] ?? 0);
$overdueCount = (int) ($stats['assignments']['overdue'] ?? 0);
$totalAssigned = (int) ($stats['assignments']['total_assignments'] ?? 0);
$ackRate = $totalAssigned > 0 ? round($ackCount / $totalAssigned * 100, 1) : 0;

$filterStatus = $filters['status'];
$filterAckStatus = $filters['ack_status'];
?>
<style>
.policy-module,
.policy-module *,
.policy-module *::before,
.policy-module *::after {
    box-sizing: border-box;
}

.policy-module {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 4px 2px 24px;
}

.policy-summary-bar {
    display: flex;
    gap: 14px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.policy-summary-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 14px;
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    flex: 1 1 0;
    min-width: 0;
    text-decoration: none;
    color: inherit;
    transition: all .15s ease;
    cursor: pointer;
}

.policy-summary-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft, 0 4px 12px rgba(13, 27, 46, .08));
    border-color: var(--info-blue, #3b82c4);
}

.policy-summary-active {
    outline: 2px solid var(--info-blue, #3b82c4);
    outline-offset: -2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 196, .15) !important;
}

.policy-summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.policy-summary-icon.blue { background: rgba(59,130,196,.12); color: #1c5a8a; }
.policy-summary-icon.green { background: rgba(47,158,110,.12); color: #1f7a52; }
.policy-summary-icon.amber { background: rgba(217,154,43,.14); color: #a86b13; }
.policy-summary-icon.red { background: rgba(214,72,74,.12); color: #a3272a; }

.policy-summary-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-900, #1b2430);
    line-height: 1;
    overflow-wrap: anywhere;
}

.policy-summary-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-700, #3b4252);
    margin-top: 4px;
    overflow-wrap: anywhere;
}

.policy-summary-desc {
    font-size: 0.72rem;
    color: var(--text-400, #8b93a1);
    margin-top: 2px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.policy-row {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
    align-items: start;
    min-width: 0;
}

.policy-col-main {
    min-width: 0;
    max-width: 100%;
}

.policy-col-side {
    width: 320px;
    flex-shrink: 0;
    min-width: 0;
    max-width: 100%;
}

.policy-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 14px;
    padding: 18px;
    box-shadow: var(--shadow-soft, 0 1px 2px rgba(13, 27, 46, .04));
    margin-bottom: 16px;
    min-width: 0;
    max-width: 100%;
}

.policy-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
    flex-wrap: wrap;
    min-width: 0;
}

.policy-card-head h3 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    overflow-wrap: anywhere;
}

.policy-empty {
    padding: 24px;
    text-align: center;
    color: var(--text-400, #8b93a1);
    font-size: 0.84rem;
}

.policy-card-body {
    display: flex;
    flex-direction: column;
    max-height: 620px;
    overflow-y: auto;
    min-width: 0;
    max-width: 100%;
}

.policy-table-wrap {
    overflow: auto;
    flex: 1 1 auto;
    max-height: 420px;
    min-width: 0;
    max-width: 100%;
}

.policy-table-wrap::-webkit-scrollbar { width: 8px; height: 8px; }
.policy-table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
.policy-table-wrap::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
.policy-table-wrap::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

.policy-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    min-width: 0;
}

.policy-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-400, #8b93a1);
    border-bottom: 1px solid var(--border, #e4e8ee);
    background: #fafbfc;
    white-space: nowrap;
}

.policy-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border, #e4e8ee);
    vertical-align: middle;
    min-width: 0;
    overflow-wrap: anywhere;
}

.policy-table tr:last-child td { border-bottom: none; }

.policy-stamp {
    display: inline-block;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
}

.policy-stamp-draft { background: rgba(217,154,43,.14); color: #a86b13; }
.policy-stamp-review { background: rgba(59,130,196,.12); color: #1c5a8a; }
.policy-stamp-approved { background: rgba(59,130,196,.12); color: #1c5a8a; }
.policy-stamp-published { background: rgba(47,158,110,.12); color: #1f7a52; }
.policy-stamp-archived { background: rgba(107,114,128,.12); color: #4b5563; }

.policy-progress {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}

.policy-progress-bar {
    flex: 1 1 auto;
    background: #e5e7eb;
    border-radius: 4px;
    height: 8px;
    min-width: 0;
    max-width: 100%;
}

.policy-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .3s;
    min-width: 0;
}

.policy-progress-text {
    font-size: 0.78rem;
    white-space: nowrap;
}

.policy-actions {
    position: relative;
    display: inline-block;
}

.policy-actions-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-700, #3b4252);
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}

.policy-actions-toggle:hover { background: var(--paper, #eef1f5); }

.policy-actions-menu {
    display: none;
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(13, 27, 46, .12);
    min-width: 170px;
    padding: 6px;
    max-width: calc(100vw - 16px);
}

.policy-actions-menu.show { display: block; }

.policy-actions-menu a,
.policy-actions-menu button {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: var(--text-900, #1b2430);
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .12s ease;
    white-space: nowrap;
}

.policy-actions-menu a:hover,
.policy-actions-menu button:hover { background: var(--paper, #eef1f5); }

.policy-actions-menu .policy-action-danger { color: #ef4444; }
.policy-actions-menu .policy-action-danger:hover { background: rgba(239,68,68,.08); }

.policy-side-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 14px;
    padding: 18px;
    box-shadow: var(--shadow-soft, 0 1px 2px rgba(13, 27, 46, .04));
    margin-bottom: 16px;
    min-width: 0;
    max-width: 100%;
}

.policy-side-card h4 {
    margin: 0 0 12px;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    overflow-wrap: anywhere;
}

.policy-quick-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border, #e4e8ee);
    gap: 10px;
}

.policy-quick-stat:last-child { border-bottom: none; }

.policy-quick-label {
    font-size: 0.78rem;
    color: var(--text-600, #5a6779);
    min-width: 0;
    overflow-wrap: anywhere;
}

.policy-quick-value {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    white-space: nowrap;
}

.policy-filter-form {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.policy-filter-form select,
.policy-filter-form input {
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    font-size: 0.85rem;
    min-width: 0;
    max-width: 100%;
}

.policy-filter-form button {
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
}

.policy-filter-form button:hover { background: var(--paper, #eef1f5); }

.policy-pagination {
    min-width: 0;
    max-width: 100%;
}

.policy-pagination-links {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    min-width: 0;
}

.policy-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-900, #1b2430);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.82rem;
    transition: all .12s ease;
}

.policy-page-btn:hover {
    background: var(--paper, #eef1f5);
    border-color: var(--info-blue, #3b82c4);
}

.policy-page-btn-active {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid var(--info-blue, #3b82c4);
    background: var(--info-blue, #3b82c4);
    color: #fff;
    font-weight: 700;
    font-size: 0.82rem;
}

@media (max-width: 1100px) {
    .policy-row {
        grid-template-columns: minmax(0, 1fr);
    }

    .policy-col-side {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .policy-summary-bar {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .policy-summary-item {
        padding: 14px;
        gap: 10px;
        border-radius: 12px;
        flex: 1 1 0;
    }

    .policy-summary-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .policy-summary-value {
        font-size: 1.25rem;
    }

    .policy-summary-label {
        font-size: 0.76rem;
    }

    .policy-summary-desc {
        font-size: 0.68rem;
    }

    .policy-card {
        padding: 14px;
        border-radius: 12px;
    }

    .policy-side-card {
        padding: 14px;
        border-radius: 12px;
    }

    .policy-card-head h3 {
        font-size: 0.9rem;
    }

    .policy-table {
        font-size: 0.78rem;
    }

    .policy-table th,
    .policy-table td {
        padding: 8px 10px;
    }

    .policy-table-wrap {
        max-height: 500px;
    }

    .policy-card-body {
        max-height: 720px;
    }
}

@media (max-width: 767px) {
    .policy-table,
    .policy-table tbody,
    .policy-table tr,
    .policy-table td {
        display: block;
        width: 100%;
    }

    .policy-table thead {
        display: none;
    }

    .policy-table tbody tr {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 14px;
        margin-bottom: 12px;
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        background: var(--card-bg, #fff);
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13, 27, 46, .04));
    }

    .policy-table tbody tr:last-child {
        margin-bottom: 0;
    }

    .policy-table td {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 2px 0;
        border-bottom: none;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .policy-table td::before {
        content: attr(data-label);
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        letter-spacing: 0.02em;
    }

    .policy-table td[data-label="Policy Code"] strong {
        font-size: 0.92rem;
    }

    .policy-table td[data-label="Title"] {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-900, #1b2430);
    }

    .policy-table td[data-label="Title"]::before {
        display: none;
    }

    .policy-table td[data-label="Policy Code"]::before {
        display: none;
    }

    .policy-table td[data-label="Actions"] {
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        padding-top: 6px;
    }

    .policy-table td[data-label="Actions"]::before {
        display: none;
    }

    .policy-table-wrap {
        overflow: visible;
        max-height: none;
    }

    .policy-card-body {
        max-height: none;
        overflow-y: visible;
    }

    .policy-actions-menu {
        min-width: 170px;
        max-width: calc(100vw - 32px);
    }
}

@media (max-width: 576px) {
    .policy-module {
        padding: 2px 0 20px;
    }

    .policy-summary-bar {
        gap: 10px;
        margin-bottom: 12px;
    }

    .policy-summary-item {
        padding: 12px 14px;
    }

    .policy-summary-icon {
        width: 36px;
        height: 36px;
    }

    .policy-summary-value {
        font-size: 1.15rem;
    }

    .policy-summary-label {
        font-size: 0.72rem;
    }

    .policy-summary-desc {
        font-size: 0.66rem;
    }

    .policy-card {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 12px;
    }

    .policy-side-card {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 12px;
    }

    .policy-pagination {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px 0 0;
        font-size: 0.78rem;
    }

    .policy-pagination-links {
        justify-content: center;
    }

    .policy-page-btn,
    .policy-page-btn-active {
        min-height: 40px;
        min-width: 40px;
        padding: 6px 10px;
        font-size: 0.78rem;
    }
}

@media (max-width: 380px) {
    .policy-summary-bar {
        grid-template-columns: 1fr;
    }

    .policy-summary-item {
        flex-direction: row;
        padding: 12px;
    }

    .policy-summary-icon {
        width: 34px;
        height: 34px;
    }

    .policy-summary-value {
        font-size: 1.1rem;
    }

    .policy-card {
        padding: 12px;
        border-radius: 10px;
    }

    .policy-side-card {
        padding: 12px;
        border-radius: 10px;
    }

    .policy-table tbody tr {
        padding: 12px;
        gap: 8px;
    }

    .policy-actions-toggle {
        min-width: 44px;
        min-height: 44px;
        width: 44px;
        height: 44px;
    }

    .policy-actions-menu a,
    .policy-actions-menu button {
        padding: 12px;
        font-size: 0.82rem;
    }
}
</style>

<section class="policy-module">
   <div class="policy-summary-bar">
     <a class="policy-summary-item <?= $filterStatus === '' && $filterAckStatus === '' ? 'policy-summary-active' : '' ?>" href="?page=policy-management">
       <div class="policy-summary-icon blue"><i class="bi bi-folder"></i></div>
       <div>
         <div class="policy-summary-value"><?= number_format($totalPolicies) ?></div>
         <div class="policy-summary-label">Total Policies</div>
       </div>
     </a>
     <a class="policy-summary-item <?= $filterStatus === 'Published' ? 'policy-summary-active' : '' ?>" href="?page=policy-management&status=Published">
       <div class="policy-summary-icon green"><i class="bi bi-check-circle"></i></div>
       <div>
         <div class="policy-summary-value"><?= number_format($published) ?></div>
         <div class="policy-summary-label">Published</div>
       </div>
     </a>
     <a class="policy-summary-item <?= $filterAckStatus === 'Acknowledged' ? 'policy-summary-active' : '' ?>" href="?page=policy-management&ack_status=Acknowledged">
       <div class="policy-summary-icon green"><i class="bi bi-check2-all"></i></div>
       <div>
         <div class="policy-summary-value"><?= number_format($ackCount) ?></div>
         <div class="policy-summary-label">Acknowledged</div>
         <div class="policy-summary-desc"><?= number_format($ackRate, 1) ?>% of <?= number_format($totalAssigned) ?></div>
       </div>
     </a>
     <a class="policy-summary-item <?= $filterAckStatus === 'Pending' ? 'policy-summary-active' : '' ?>" href="?page=policy-management&ack_status=Pending">
       <div class="policy-summary-icon amber"><i class="bi bi-clock-history"></i></div>
       <div>
         <div class="policy-summary-value"><?= number_format($pendingCount) ?></div>
         <div class="policy-summary-label">Pending</div>
         <div class="policy-summary-desc">Awaiting acknowledgement</div>
       </div>
     </a>
     <a class="policy-summary-item <?= $filterAckStatus === 'Overdue' ? 'policy-summary-active' : '' ?>" href="?page=policy-management&ack_status=Overdue">
       <div class="policy-summary-icon red"><i class="bi bi-exclamation-circle"></i></div>
       <div>
         <div class="policy-summary-value"><?= number_format($overdueCount) ?></div>
         <div class="policy-summary-label">Overdue</div>
         <div class="policy-summary-desc">Pending action</div>
       </div>
     </a>
   </div>

  <div class="policy-row">
    <div class="policy-col-main">
      <div class="policy-card">
        <div class="policy-card-head">
          <h3><i class="bi bi-folder2-open"></i> Policies</h3>
        </div>
        <div class="policy-card-body">
          <?php if (empty($policies)): ?>
            <div class="policy-empty">No policies found.</div>
          <?php else: ?>
          <div class="policy-table-wrap">
            <table class="policy-table">
              <thead>
                <tr>
                  <th>Policy Code</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Version</th>
                  <th>Effective Date</th>
                  <th>Status</th>
                  <th>Acknowledgement</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($policies as $p):
                  $statsRow = $policy->getAcknowledgementStats((int) $p['id']);
                  $total = (int) ($statsRow['total_assigned'] ?? 0);
                  $ack = (int) ($statsRow['acknowledged'] ?? 0);
                  $pending = (int) ($statsRow['pending'] ?? 0);
                  $overdue = (int) ($statsRow['overdue'] ?? 0);
                  $rate = $total > 0 ? round($ack / $total * 100, 1) : 0;
                  $statusLower = strtolower($p['status'] ?? 'draft');
                  if ($statusLower === 'draft') $stampCls = 'draft';
                  elseif ($statusLower === 'for review') $stampCls = 'review';
                  elseif ($statusLower === 'approved') $stampCls = 'approved';
                  elseif ($statusLower === 'published') $stampCls = 'published';
                  elseif ($statusLower === 'archived') $stampCls = 'archived';
                  else $stampCls = 'draft';
                ?>
                <tr>
                  <td data-label="Policy Code"><strong><?= htmlspecialchars($p['policy_code']) ?></strong></td>
                  <td data-label="Title"><?= htmlspecialchars($p['title']) ?></td>
                  <td data-label="Category"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                  <td data-label="Version">v<?= htmlspecialchars($p['version']) ?></td>
                  <td data-label="Effective Date"><?= $p['effective_date'] ? date('M d, Y', strtotime($p['effective_date'])) : '—' ?></td>
                  <td data-label="Status"><span class="policy-stamp policy-stamp-<?= $stampCls ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                  <td data-label="Acknowledgement">
                    <?php if ($total > 0): ?>
                      <div class="policy-progress">
                        <div class="policy-progress-bar">
                          <div class="policy-progress-fill" style="width:<?= $rate ?>%; background:<?= $rate >= 80 ? '#22c55e' : ($rate >= 50 ? '#f59e0b' : '#ef4444') ?>;"></div>
                        </div>
                        <span class="policy-progress-text"><?= $rate ?>%</span>
                      </div>
                      <small style="color:var(--text-400,#8b93a1);"><?= $ack ?> ack / <?= $pending ?> pending / <?= $overdue ?> overdue</small>
                    <?php else: ?>
                      <span style="color:var(--text-400,#8b93a1);">No assignments</span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Actions">
                    <div class="policy-actions">
                      <button type="button" class="policy-actions-toggle" onclick="togglePolicyMenu(this)" aria-label="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <div class="policy-actions-menu">
                        <a href="?page=policy-view&id=<?= (int) $p['id'] ?>"><i class="bi bi-eye"></i> View</a>
                        <a href="?page=acknowledgement-report&id=<?= (int) $p['id'] ?>"><i class="bi bi-bar-chart"></i> Report</a>
                      </div>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
          <div class="policy-pagination" style="display:flex; justify-content:space-between; align-items:center; padding:14px 0 0; font-size:0.82rem; color:var(--text-600,#5a6779); flex-wrap:wrap; gap:10px;">
            <span>Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $perPage, $totalPoliciesCount)) ?> of <?= number_format($totalPoliciesCount) ?> policies</span>
            <div class="policy-pagination-links" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
              <?php if ($page > 1): ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $page - 1])) ?>" class="policy-page-btn" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); text-decoration:none; font-weight:600; font-size:0.82rem;">&laquo; Prev</a>
              <?php endif; ?>
              <?php
                $range = 2;
                $start = max(1, $page - $range);
                $end = min($totalPages, $page + $range);
                if ($start > 1) {
                  echo '<a href="?page=policy-management&' . http_build_query(array_merge($filters, ['policy_page' => 1])) . '" class="policy-page-btn" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); text-decoration:none; font-weight:600; font-size:0.82rem;">1</a>';
                  if ($start > 2) echo '<span style="color:var(--text-400,#8b93a1);">...</span>';
                }
                for ($i = $start; $i <= $end; $i++):
                  $active = $i === $page;
              ?>
                <?php if ($active): ?>
                  <span class="policy-page-btn-active" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--info-blue,#3b82c4); background:var(--info-blue,#3b82c4); color:#fff; font-weight:700; font-size:0.82rem;"><?= number_format($i) ?></span>
                <?php else: ?>
                  <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $i])) ?>" class="policy-page-btn" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); text-decoration:none; font-weight:600; font-size:0.82rem;"><?= number_format($i) ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1) echo '<span style="color:var(--text-400,#8b93a1);">...</span>'; ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $totalPages])) ?>" class="policy-page-btn" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); text-decoration:none; font-weight:600; font-size:0.82rem;"><?= number_format($totalPages) ?></a>
              <?php endif; ?>
              <?php if ($page < $totalPages): ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $page + 1])) ?>" class="policy-page-btn" style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); text-decoration:none; font-weight:600; font-size:0.82rem;">Next &raquo;</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="policy-col-side">
      <div class="policy-side-card">
        <h4><i class="bi bi-info-circle"></i> Policy Summary</h4>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Total Policies</span>
          <span class="policy-quick-value"><?= number_format($totalPolicies) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Published</span>
          <span class="policy-quick-value" style="color:#1f7a52;"><?= number_format($published) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Draft</span>
          <span class="policy-quick-value" style="color:#a86b13;"><?= number_format($draft) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Acknowledgement Rate</span>
          <span class="policy-quick-value"><?= number_format($ackRate, 1) ?>%</span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Overdue</span>
          <span class="policy-quick-value" style="color:#a3272a;"><?= number_format($overdueCount) ?></span>
        </div>
      </div>

      <div class="policy-side-card">
        <h4><i class="bi bi-lightbulb"></i> Policy Lifecycle</h4>
        <div style="display:flex; flex-direction:column; gap:8px; font-size:0.78rem;">
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="policy-stamp policy-stamp-draft">Draft</span>
            <span style="color:var(--text-600,#5a6779); overflow-wrap:anywhere;">Being prepared</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="policy-stamp policy-stamp-review">For Review</span>
            <span style="color:var(--text-600,#5a6779); overflow-wrap:anywhere;">Awaiting approval</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="policy-stamp policy-stamp-approved">Approved</span>
            <span style="color:var(--text-600,#5a6779); overflow-wrap:anywhere;">Ready to publish</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="policy-stamp policy-stamp-published">Published</span>
            <span style="color:var(--text-600,#5a6779); overflow-wrap:anywhere;">Active for em_employees</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="policy-stamp policy-stamp-archived">Archived</span>
            <span style="color:var(--text-600,#5a6779); overflow-wrap:anywhere;">Historical record</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function togglePolicyMenu(btn) {
  const menu = btn.nextElementSibling;
  const isOpen = menu.classList.contains('show');
  document.querySelectorAll('.policy-actions-menu.show').forEach(m => m.classList.remove('show'));
  if (!isOpen) {
    const rect = btn.getBoundingClientRect();
    let top = rect.bottom + 6;
    let right = window.innerWidth - rect.right;
    const menuWidth = 170;
    const padding = 8;
    if (right + menuWidth > window.innerWidth - padding) {
      right = window.innerWidth - menuWidth - padding;
    }
    if (right < padding) {
      right = padding;
    }
    if (top + 150 > window.innerHeight) {
      top = rect.top - 150;
    }
    if (top < padding) {
      top = padding;
    }
    menu.style.top = top + 'px';
    menu.style.right = right + 'px';
    menu.classList.add('show');
  }
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.policy-actions')) {
    document.querySelectorAll('.policy-actions-menu.show').forEach(m => m.classList.remove('show'));
  }
});
</script>