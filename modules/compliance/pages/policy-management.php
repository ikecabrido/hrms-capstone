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
if ($action === 'delete' && $policyId > 0) {
    $policy->deletePolicy($policyId);
    header('Location: ?page=policy-management');
    exit;
}
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
    font-family: Arial, sans-serif;
}
.policy-module {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 4px 2px 24px;
}
.cw-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-700, #3b4252);
    font-size: 10px;
    line-height: 1.4;
    font-family: Arial, sans-serif;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    transition: border-color .12s ease, color .12s ease, background .12s ease;
}
.cw-btn:hover {
    border-color: var(--text-primary, #2563eb);
    color: var(--text-primary, #2563eb);
    background: #fff;
}
.cw-btn.primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.cw-btn.primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}
.cw-btn.danger {
    background: #fff;
    border-color: #e4e8ee;
    color: #991b1b;
}
.cw-btn.danger:hover {
    border-color: #b91c1c;
    color: #b91c1c;
    background: #fff;
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
    border-radius: 6px;
    padding: 12px 15px;
    box-shadow: none;
    margin-bottom: 14px;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}
.policy-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
    min-width: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border, #e4e8ee);
}
.policy-card-head h3 {
    margin: 0;
    font-size: 11.5px;
    line-height: 1.3;
    color: var(--text-900, #1b2430);
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    overflow-wrap: anywhere;
    font-family: Arial, sans-serif;
}
.policy-empty {
    padding: 24px;
    text-align: center;
    color: var(--text-400, #8b93a1);
    font-size: 11.5px;
    font-family: Arial, sans-serif;
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
    font-size: 11.5px;
    min-width: 0;
    font-family: Arial, sans-serif;
}
.policy-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 10px;
    line-height: 1.3;
    font-family: Arial, sans-serif;
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
    font-size: 11.5px;
    color: var(--text-900, #1b2430);
    font-family: Arial, sans-serif;
}
.policy-table td[data-label="Category"] {
    font-size: 11.5px;
    padding: 6px 10px;
    font-family: Arial, sans-serif;
}
.policy-table tr:last-child td { border-bottom: none; }
.policy-stamp {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    line-height: 1.3;
    padding: 3px 10px;
    border-radius: 4px;
    white-space: nowrap;
    font-family: Arial, sans-serif;
}
.policy-stamp-draft { background: #fdf6f6; color: var(--text-danger, #b34b4b); border: 1px solid #f5c6c6; }
.policy-stamp-review { background: #fffbf0; color: #8a6d1a; border: 1px solid #f0e4a8; }
.policy-stamp-approved { background: #fffbf0; color: #8a6d1a; border: 1px solid #f0e4a8; }
.policy-stamp-published { background: #f6fbf7; color: var(--text-success, #3f8053); border: 1px solid #c8e6d0; }
.policy-stamp-archived { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
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
    font-size: 11.5px;
    white-space: nowrap;
    font-family: Arial, sans-serif;
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
    background: var(--card-bg, #fff);
    color: var(--text-900, #1b2430);
    font-size: 1rem;
    font-family: Arial, sans-serif;
    cursor: pointer;
    transition: all 0.15s ease;
}
.policy-actions-toggle:hover { background: var(--paper, #eef1f5); }
.policy-actions-menu {
    display: none;
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e4e8ee;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(13,27,46,.08);
    min-width: 150px;
    padding: 4px;
    max-width: calc(100vw - 16px);
}
.policy-actions-menu.show { display: block; }
.policy-actions-menu a,
.policy-actions-menu button {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 6px 10px;
    border-radius: 4px;
    border: none;
    background: transparent;
    color: var(--text-900, #1b2430);
    font-size: 11.5px;
    cursor: pointer;
    text-decoration: none;
    transition: background .12s ease;
    white-space: nowrap;
}
.policy-actions-menu a:hover,
.policy-actions-menu button:hover { background: #f4f5f7; }
.policy-actions-menu .policy-action-danger { color: var(--text-danger, #b34b4b); }
.policy-actions-menu .policy-action-danger:hover { background: rgba(179, 75, 75, .06); }
.policy-side-card,
.cw-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 6px;
    padding: 12px 15px;
    box-shadow: none;
    margin-bottom: 14px;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}
.policy-side-card h4,
.cw-card h4 {
    margin: 0 0 10px;
    font-size: 15px;
    line-height: 1.3;
    overflow-wrap: anywhere;
    font-family: Arial, sans-serif;
}
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
    font-size: 12.5px;
    color: var(--text-700, #3b4252);
    min-width: 0;
    overflow-wrap: anywhere;
}
.policy-quick-value {
    font-size: 12.5px;
    color: var(--text-900, #1b2430);
    white-space: nowrap;
}
.policy-filter-form {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.policy-filter-form select,
.policy-filter-form input {
    padding: 8px 10px;
    border-radius: 4px;
    border: 1px solid var(--border, #e4e8ee);
    font-size: 12.5px;
    min-width: 0;
    max-width: 100%;
    height: 38px;
    background: var(--card-bg, #fff);
    color: var(--text-900, #1b2430);
}
.policy-filter-form button {
    padding: 8px 14px;
    border-radius: 4px;
    border: 1px solid var(--border, #e4e8ee);
    background: var(--card-bg, #fff);
    cursor: pointer;
    font-size: 12.5px;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
    height: 38px;
}
.policy-filter-form button:hover {
    border-color: var(--text-primary, #2563eb);
    color: var(--text-primary, #2563eb);
}
.philhealth-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; flex-wrap:wrap; font-size:10px; color:var(--text-700, #3b4252); font-family: Arial, sans-serif; }
.philhealth-pagination-info { font-size:10px; color:var(--text-700, #3b4252); white-space:nowrap; font-family: Arial, sans-serif; }
.philhealth-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:transparent; border:1px solid var(--border,#e4e8ee); border-radius:6px; overflow:hidden; }
.philhealth-pagination-nav .philhealth-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border:0; background:transparent; font-size:10px; color:var(--text-900, #1b2430);     cursor: pointer;
    text-decoration: none;
    transition: background-color .1s ease;
    font-family: Arial, sans-serif;
}
.philhealth-pagination-nav .philhealth-page-btn:hover:not(.philhealth-page-btn--active) { background:var(--slate-100,#f1f5f9); }
.philhealth-pagination-nav .philhealth-page-btn[disabled] { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.philhealth-pagination-nav .philhealth-page-btn--active { background:var(--text-primary, #2563eb);     color:#fff; }
.philhealth-pagination-nav .philhealth-page-ellipsis { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; color:var(--text-700, #3b4252); font-family: Arial, sans-serif; }
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
    .policy-card {
        padding: 14px;
    }
    .policy-side-card,
    .cw-card {
        padding: 14px;
    }
    .policy-table {
        font-size: 12.5px;
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
    font-size: 11.5px;
    color: #8b93a1;
        letter-spacing: 0.02em;
    }
.policy-table td[data-label="Policy"] {
    font-size: 15px;
}
    .policy-table td[data-label="Title"] {
    font-size: 15px;
    color: var(--text-900, #1b2430);
    }
    .policy-table td[data-label="Title"]::before {
        display: none;
    }
    .policy-table td[data-label="Category"] {
        font-size: 12.5px;
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
    .policy-card {
        padding: 14px;
        border-radius: 12px;
    }
    .policy-side-card,
    .cw-card {
        padding: 14px;
        border-radius: 12px;
    }
    .policy-card-head h3 {
        font-size: 15px;
    }
    .philhealth-pagination {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px 0 0;
        font-size: 12.5px;
    }
    .philhealth-pagination-nav {
        align-self: center;
    }
}
@media (max-width: 380px) {
    .policy-card {
        padding: 14px;
        border-radius: 12px;
    }
    .policy-side-card {
        padding: 14px;
        border-radius: 12px;
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
        font-size: 12.5px;
    }
}
.policy-filter-form select,
.policy-filter-form input {
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    font-size: 11.5px;
    min-width: 0;
    max-width: 100%;
    font-family: Arial, sans-serif;
}
.policy-filter-form button {
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    cursor: pointer;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}
.policy-filter-form button:hover { background: var(--paper, #eef1f5); }
.philhealth-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; flex-wrap:wrap; font-size:10px; color:var(--text-500,#64748b); font-family: Arial, sans-serif; }
.philhealth-pagination-info { font-size:10px; color:var(--text-500,#64748b); white-space:nowrap; font-family: Arial, sans-serif; }
.philhealth-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:transparent; border:1px solid var(--border,#e4e8ee); border-radius:6px; overflow:hidden; font-family: Arial, sans-serif; }
.philhealth-pagination-nav .philhealth-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border:0; background:transparent; font-size:10px; color:var(--text-700,#334155);     cursor: pointer;
    text-decoration: none;
    transition: background-color .1s ease;
    font-family: Arial, sans-serif; }
.philhealth-pagination-nav .philhealth-page-btn:hover:not(.philhealth-page-btn--active) { background:var(--slate-100,#f1f5f9); }
.philhealth-pagination-nav .philhealth-page-btn[disabled] { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.philhealth-pagination-nav .philhealth-page-btn--active {     background:#2563eb; color:#fff; }
.philhealth-pagination-nav .philhealth-page-ellipsis { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; color:var(--text-500,#64748b); font-family: Arial, sans-serif; }
.policy-summary-bar { display:flex; gap:8px; margin-bottom:14px; flex-wrap:nowrap; }
.policy-summary-item { display:flex; align-items:center; gap:6px; padding:10px 14px; border-radius:2px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:auto; text-decoration:none; color:inherit; transition:border-color .1s ease; cursor:pointer; }
.policy-summary-item:hover { border-color:#cbd5e1; }
.policy-summary-active { border-color:var(--info-blue,#3b82c4); background:#fafbfc; }
.policy-summary-value {     font-size:1.1rem; color:var(--text-900,#1b2430); line-height:1.5; font-family: Arial, sans-serif; }
.policy-summary-label {     font-size:0.6rem; color:var(--text-700,#3b4252); margin-top:2px; font-family: Arial, sans-serif; }
.policy-summary-desc { font-size:0.55rem; color:var(--text-400,#8b93a1); margin-top:1px;  font-family: Arial, sans-serif; }
@media (max-width: 1100px) {
    .policy-row {
        grid-template-columns: minmax(0, 1fr);
    }
    .policy-col-side {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }
    .policy-summary-item {
        min-width: 0;
        flex: 1 1 calc(50% - 8px);
        max-width: calc(50% - 8px);
    }
}
@media (max-width: 768px) {
    .policy-card {
        padding: 14px;
        border-radius: 12px;
    }
    .policy-side-card,
    .cw-card {
        padding: 14px;
        border-radius: 12px;
    }
.policy-card-head h3 {
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}
.policy-table {
    font-size: 11.5px;
    font-family: Arial, sans-serif;
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
    font-size: 10px;
    font-family: Arial, sans-serif;
    text-transform: uppercase;
    color: var(--text-400, #8b93a1);
    letter-spacing: 0.02em;
}
.policy-table td[data-label="Policy Code"] {
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}
.policy-table td[data-label="Title"] {
    font-size: 11.5px;
    color: var(--text-900, #1b2430);
    font-family: Arial, sans-serif;
}
    .policy-table td[data-label="Title"]::before {
        display: none;
    }
.policy-table td[data-label="Category"] {
    font-size: 11.5px;
    font-family: Arial, sans-serif;
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
        gap: 12px;
        margin-bottom: 14px;
    }
    .policy-summary-item {
        padding: 2px 0;
    }
    .policy-summary-value {
        font-size: 11.5px;
        font-family: Arial, sans-serif;
    }
    .policy-summary-label {
        font-size: 10px;
        font-family: Arial, sans-serif;
    }
    .policy-summary-desc {
        font-size: 10px;
        font-family: Arial, sans-serif;
    }
    .policy-card {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .policy-side-card,
    .cw-card {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 12px;
    }
.philhealth-pagination {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    padding: 12px 0 0;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}
    .philhealth-pagination-nav {
        align-self: center;
    }
}
@media (max-width: 380px) {
    .policy-summary-bar {
        grid-template-columns: 1fr;
    }
    .policy-summary-item {
        padding: 10px 12px;
    }
    .policy-summary-value {
        font-size: 1.05rem;
    }
    .policy-card {
        padding: 12px;
        border-radius: 10px;
    }
    .policy-side-card,
    .cw-card {
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
        font-size: 11.5px;
        font-family: Arial, sans-serif;
    }
}
</style>
<section class="policy-module">
    <div class="policy-summary-bar">
      <a class="policy-summary-item policy-summary-active" href="?page=policy-management">
        <div>
          <div class="policy-summary-value"><?= number_format($totalPolicies) ?></div>
          <div class="policy-summary-label">Total Policies</div>
        </div>
      </a>
      <a class="policy-summary-item" href="?page=policy-management&status=Published">
        <div>
          <div class="policy-summary-value"><?= number_format($published) ?></div>
          <div class="policy-summary-label">Published</div>
        </div>
      </a>
      <a class="policy-summary-item" href="?page=policy-management&ack_status=Acknowledged">
        <div>
          <div class="policy-summary-value"><?= number_format($ackCount) ?></div>
          <div class="policy-summary-label">Acknowledged</div>
          <div class="policy-summary-desc"><?= number_format($ackRate, 1) ?>% of <?= number_format($totalAssigned) ?></div>
        </div>
      </a>
      <a class="policy-summary-item" href="?page=policy-management&ack_status=Pending">
        <div>
          <div class="policy-summary-value"><?= number_format($pendingCount) ?></div>
          <div class="policy-summary-label">Pending</div>
          <div class="policy-summary-desc">Awaiting acknowledgement</div>
        </div>
      </a>
      <a class="policy-summary-item" href="?page=policy-management&ack_status=Overdue">
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
              <div class="policy-card-head-content">
                <h3>Policies</h3>
              </div>
              <a href="?page=policy-create" class="cw-btn primary">Create Policy</a>
            </div>
            <div class="policy-card-body">
          <?php if (empty($policies)): ?>
            <div class="policy-empty">No policies found.</div>
          <?php else: ?>
          <div class="policy-table-wrap">
            <table class="policy-table">
              <thead>
                <tr>
                  <th>Policy</th>
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
                  <td data-label="Policy">    <?= htmlspecialchars($p['policy_code']) ?><br><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                  <td data-label="Category"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                  <td data-label="Version">v<?= htmlspecialchars($p['version']) ?></td>
                  <td data-label="Effective Date"><?= $p['effective_date'] ? date('M d, Y', strtotime($p['effective_date'])) : '—' ?></td>
                  <td data-label="Status"><span class="policy-stamp policy-stamp-<?= $stampCls ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                  <td data-label="Acknowledgement">
                    <?php if ($total > 0): ?>
                      <div class="policy-progress">
                        <div class="policy-progress-bar">
                           <div class="policy-progress-fill" style="width:<?= $rate ?>%; background:<?= $rate >= 80 ? '#2563eb' : ($rate >= 50 ? '#3b82c6' : '#60a5fa') ?>;"></div>
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
                        ...
                      </button>
                      <div class="policy-actions-menu">
                        <a href="?page=policy-view&id=<?= (int) $p['id'] ?>">View</a>
                        <a href="?page=acknowledgement-report&id=<?= (int) $p['id'] ?>">Report</a>
                        <a href="?page=policy-management&action=delete&id=<?= (int) $p['id'] ?>" class="policy-action-danger" onclick="return confirm('Delete this policy? This cannot be undone.');">Delete</a>
                      </div>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
          <div class="philhealth-pagination">
            <span class="philhealth-pagination-info">Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalPoliciesCount)) ?> of <?= number_format($totalPoliciesCount) ?></span>
            <nav class="philhealth-pagination-nav" role="navigation" aria-label="Pagination">
              <?php if ($page > 1): ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $page - 1])) ?>" class="philhealth-page-btn" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
              <?php else: ?>
                <button type="button" class="philhealth-page-btn" disabled aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
              <?php endif; ?>
              <?php
                $maxVisible = 3;
                $startPage = max(1, $page - (int) floor($maxVisible / 2));
                $endPage = min($totalPages, $startPage + $maxVisible - 1);
                if ($endPage - $startPage < $maxVisible - 1) {
                  $startPage = max(1, $endPage - $maxVisible + 1);
                }
                if ($startPage > 1) {
                  echo '<a href="?page=policy-management&' . http_build_query(array_merge($filters, ['policy_page' => 1])) . '" class="philhealth-page-btn">1</a>';
                  if ($startPage > 2) echo '<span class="philhealth-page-ellipsis">&hellip;</span>';
                }
                for ($i = $startPage; $i <= $endPage; $i++):
                  $active = $i === $page;
              ?>
                <?php if ($active): ?>
                  <span class="philhealth-page-btn philhealth-page-btn--active" aria-current="page"><?= number_format($i) ?></span>
                <?php else: ?>
                  <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $i])) ?>" class="philhealth-page-btn"><?= number_format($i) ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1) echo '<span class="philhealth-page-ellipsis">&hellip;</span>'; ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $totalPages])) ?>" class="philhealth-page-btn"><?= number_format($totalPages) ?></a>
              <?php endif; ?>
              <?php if ($page < $totalPages): ?>
                <a href="?page=policy-management&<?= http_build_query(array_merge($filters, ['policy_page' => $page + 1])) ?>" class="philhealth-page-btn" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
              <?php else: ?>
                <button type="button" class="philhealth-page-btn" disabled aria-label="Next"><i class="bi bi-chevron-right"></i></button>
              <?php endif; ?>
            </nav>
          </div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="policy-col-side">
      <div class="cw-card">
        <div class="policy-card-head">
          <div class="policy-card-head-content">
            <h4>Policy Summary</h4>
          </div>
        </div>
        <div class="policy-card-body">
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
        <div style="border-top: 1px solid var(--border, #e4e8ee); margin: 4px 0;"></div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Acknowledgement Rate</span>
          <span class="policy-quick-value"><?= number_format($ackRate, 1) ?>%</span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Overdue</span>
          <span class="policy-quick-value" style="color:#a3272a;"><?= number_format($overdueCount) ?></span>
        </div>
      </div>
      </div>
      <div class="cw-card">
        <div class="policy-card-head">
          <div class="policy-card-head-content">
            <h4>Policy Lifecycle</h4>
          </div>
        </div>
        <div class="policy-card-body">
        <div style="display:flex; flex-direction:column; gap:8px; font-size:11.5px; font-family: Arial, sans-serif;">
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