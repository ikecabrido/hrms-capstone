<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';

$pageTitle = 'Case Reports';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();

$reportType = $_GET['report'] ?? 'summary';
$agencyFilter = $_GET['agency'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = ['1=1'];
$params = [];

if ($agencyFilter !== '') {
    $where[] = 'lc.external_agency = :agency';
    $params[':agency'] = $agencyFilter;
}
if ($statusFilter !== '') {
    $where[] = 'lc.current_status = :status';
    $params[':status'] = $statusFilter;
}
if ($dateFrom !== '') {
    $where[] = 'lc.date_received >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'lc.date_received <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);

$cases = [];
if ($db !== null) {
  try {
    $stmt = $db->prepare("
      SELECT lc.*, e.first_name, e.last_name, e.employee_code,
           CONCAT(ee.first_name, ' ', ee.last_name) AS assigned_name
      FROM lc_legal_cases lc
      LEFT JOIN em_employees e ON lc.employee_id = e.employee_id
      LEFT JOIN em_employees ee ON lc.assigned_to = ee.employee_id
      WHERE $whereSql
      ORDER BY lc.created_at DESC
      LIMIT 500
    ");
    $stmt->execute($params);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $cases = [];
  }
}

$agencies = ['DOLE', 'NLRC', 'SSS', 'PhilHealth', 'Pag-IBIG', 'BIR', 'Other Government Agency'];
$statuses = [
    'Draft', 'Open', 'Under Assessment', 'Under Investigation', 'Awaiting Documents',
    'Awaiting External Action', 'Conference Scheduled', 'In Conference',
    'Settlement Reached', 'Referred', 'Compliance Action Required',
    'Monitoring', 'Resolved', 'Closed', 'Cancelled'
];

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function cr_status_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['closed', 'resolved'], true)) return 'compliant';
    if (in_array($s, ['under assessment', 'under investigation', 'in conference', 'conference scheduled'], true)) return 'info';
    return 'pending';
}
?>

<div class="module-content">
  <?php if ($flash): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="lc-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-funnel"></i> Filter Report</h3></div>
    <form method="get" action="?page=case-reports">
      <input type="hidden" name="page" value="case-reports">
      <div class="lc-filter-bar">
        <div class="lc-field">
          <label>Agency</label>
          <select name="agency">
            <option value="">All Agencies</option>
            <?php foreach ($agencies as $a): ?>
              <option value="<?= htmlspecialchars($a) ?>" <?= $agencyFilter === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Status</label>
          <select name="status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Date From</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="lc-field">
          <label>Date To</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="lc-field" style="display:flex;align-items:flex-end;">
          <button type="submit" class="lc-btn primary"><i class="bi bi-search"></i> Generate Report</button>
        </div>
      </div>
    </form>
  </div>

  <div class="lc-card">
    <div class="lc-card-head">
      <h3><i class="bi bi-journal-text"></i> Case Report</h3>
      <span style="font-size:0.72rem;color:var(--text-400,#8b93a1);"><?= number_format(count($cases)) ?> cases</span>
    </div>
    <?php if (empty($cases)): ?>
      <div class="lc-empty"><i class="bi bi-emoji-smile"></i> No cases match the current filters.</div>
    <?php else: ?>
    <div class="lc-table-wrap">
      <table class="lc-table">
        <thead>
          <tr>
            <th>Case Number</th>
            <th>Case Title</th>
            <th>Type</th>
            <th>Agency</th>
            <th>Source</th>
            <th>Employee</th>
            <th>Status</th>
            <th>Stage</th>
            <th>Priority</th>
            <th>Assigned To</th>
            <th>Date Received</th>
            <th>Date Closed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cases as $c): ?>
            <tr>
              <td data-label="Case Number" class="lc-id-cell"><span class="lc-cnum"><?= htmlspecialchars($c['case_number'], ENT_QUOTES) ?></span></td>
              <td data-label="Case Title" class="lc-emp-cell"><span class="lc-emp-name"><?= htmlspecialchars($c['case_title'], ENT_QUOTES) ?></span></td>
              <td data-label="Type"><span class="lc-type-badge"><?= htmlspecialchars($c['case_type'] ?? 'Other', ENT_QUOTES) ?></span></td>
              <td data-label="Agency"><?= htmlspecialchars($c['external_agency'] ?? '—', ENT_QUOTES) ?></td>
              <td data-label="Source"><?= htmlspecialchars($c['case_source'] ?? '—', ENT_QUOTES) ?></td>
              <td data-label="Employee"><?= htmlspecialchars(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')), ENT_QUOTES) ?></td>
              <td data-label="Status"><span class="lc-status-stamp lc-status-stamp--<?= cr_status_class($c['current_status']) ?>"><?= htmlspecialchars($c['current_status'], ENT_QUOTES) ?></span></td>
              <td data-label="Stage"><?= htmlspecialchars($c['current_stage'] ?? '—', ENT_QUOTES) ?></td>
              <td data-label="Priority"><?= htmlspecialchars(ucfirst($c['priority'] ?? 'Medium'), ENT_QUOTES) ?></td>
              <td data-label="Assigned To"><?= htmlspecialchars($c['assigned_name'] ?? '—', ENT_QUOTES) ?></td>
              <td data-label="Date Received"><?= htmlspecialchars($c['date_received'] ?? '—', ENT_QUOTES) ?></td>
              <td data-label="Date Closed"><?= htmlspecialchars($c['date_closed'] ?? '—', ENT_QUOTES) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
