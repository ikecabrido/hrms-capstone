<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Acknowledgement Report';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$policy = new Policy($db);

$policyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($policyId <= 0) {
    header('Location: ?page=policy-management');
    exit;
}

$policyData = $policy->getPolicyById($policyId);
if (!$policyData) {
    header('Location: ?page=policy-management');
    exit;
}

$stats = $policy->getAcknowledgementStats($policyId);
$total = (int) ($stats['total_assigned'] ?? 0);
$ack = (int) ($stats['acknowledged'] ?? 0);
$pending = (int) ($stats['pending'] ?? 0);
$overdue = (int) ($stats['overdue'] ?? 0);
$rate = $total > 0 ? round($ack / $total * 100, 1) : 0;

$acknowledgements = $policy->getAcknowledgements($policyId);

?>

<link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/acknowledgement-report.css?v=2">

<section class="policy-module">
  <div class="policy-summary-bar">
    <div class="policy-summary-item">
      <div class="policy-summary-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="policy-summary-value"><?= number_format($total) ?></div>
        <div class="policy-summary-label">Total Assigned</div>
      </div>
    </div>
    <div class="policy-summary-item">
      <div class="policy-summary-icon green"><i class="bi bi-check2-all"></i></div>
      <div>
        <div class="policy-summary-value"><?= number_format($ack) ?></div>
        <div class="policy-summary-label">Acknowledged</div>
        <div class="policy-summary-desc"><?= number_format($rate, 1) ?>% of <?= number_format($total) ?></div>
      </div>
    </div>
    <div class="policy-summary-item">
      <div class="policy-summary-icon amber"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="policy-summary-value"><?= number_format($pending) ?></div>
        <div class="policy-summary-label">Pending</div>
        <div class="policy-summary-desc">Awaiting acknowledgement</div>
      </div>
    </div>
    <div class="policy-summary-item">
      <div class="policy-summary-icon red"><i class="bi bi-exclamation-circle"></i></div>
      <div>
        <div class="policy-summary-value"><?= number_format($overdue) ?></div>
        <div class="policy-summary-label">Overdue</div>
        <div class="policy-summary-desc">Pending action</div>
      </div>
    </div>
  </div>

  <div class="policy-card">
    <div class="policy-card-head">
      <h3><i class="bi bi-bar-chart"></i> Acknowledgement Rate</h3>
    </div>
    <div class="policy-card-body">
      <div class="policy-progress">
        <div class="policy-progress-bar">
          <div class="policy-progress-fill" style="width:<?= $rate ?>%; background:<?= $rate >= 80 ? '#22c55e' : ($rate >= 50 ? '#f59e0b' : '#ef4444') ?>;"></div>
        </div>
        <span class="policy-progress-text"><?= $rate ?>%</span>
      </div>
      <p class="policy-progress-desc"><?= $ack ?> of <?= $total ?> em_employees acknowledged</p>
    </div>
  </div>

  <div class="policy-card">
    <div class="policy-card-head">
      <h3><i class="bi bi-people"></i> Employee List</h3>
    </div>
    <div class="policy-table-wrap">
      <table class="policy-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Position</th>
            <th>Status</th>
            <th>Acknowledged At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($acknowledgements)): ?>
            <tr><td colspan="5" class="policy-empty">No records found.</td></tr>
          <?php else: ?>
            <?php foreach ($acknowledgements as $a): ?>
              <?php
                  $statusLabel = $a['status'];
                  if ($statusLabel === 'Acknowledged') $stampCls = 'published';
                  elseif ($statusLabel === 'Overdue') $stampCls = 'archived';
                  elseif ($statusLabel === 'Pending') $stampCls = 'draft';
                  else $stampCls = 'draft';
              ?>
              <tr>
                <td data-label="Employee"><?= htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?></td>
                <td data-label="Department"><?= htmlspecialchars($a['department_name'] ?? '—') ?></td>
                <td data-label="Position"><?= htmlspecialchars($a['position_name'] ?? '—') ?></td>
                <td data-label="Status"><span class="policy-stamp policy-stamp-<?= $stampCls ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                <td data-label="Acknowledged At"><?= $a['date_acknowledged'] ? date('M d, Y H:i', strtotime($a['date_acknowledged'])) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>