<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Policy Details';

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

$assignments = $policy->getAssignments($policyId);

$filterStatus = '';
$filterAckStatus = '';
?>
<link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/policy-view.css?v=2">

<section class="policy-module">
  <div class="policy-row">
    <div class="policy-col-main">
      <div class="policy-card">
        <div class="policy-card-head">
          <h3><i class="bi bi-info-circle"></i> Policy Details</h3>
        </div>
        <div class="policy-card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Policy Code</label>
              <div class="form-control-plain"><?= htmlspecialchars($policyData['policy_code']) ?></div>
            </div>
            <div class="form-group">
              <label>Version</label>
              <div class="form-control-plain">v<?= htmlspecialchars($policyData['version']) ?></div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Effective Date</label>
              <div class="form-control-plain"><?= $policyData['effective_date'] ? date('F d, Y', strtotime($policyData['effective_date'])) : '—' ?></div>
            </div>
            <div class="form-group">
              <label>Acknowledgement Deadline</label>
              <div class="form-control-plain"><?= $policyData['acknowledgement_deadline'] ? date('F d, Y', strtotime($policyData['acknowledgement_deadline'])) : '—' ?></div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <div class="form-control-plain">
                <?php
                  $statusLower = strtolower($policyData['status'] ?? 'draft');
                  if ($statusLower === 'draft') $stampCls = 'draft';
                  elseif ($statusLower === 'for review') $stampCls = 'review';
                  elseif ($statusLower === 'approved') $stampCls = 'approved';
                  elseif ($statusLower === 'published') $stampCls = 'published';
                  elseif ($statusLower === 'archived') $stampCls = 'archived';
                  else $stampCls = 'draft';
                ?>
                <span class="policy-stamp policy-stamp-<?= $stampCls ?>"><?= htmlspecialchars($policyData['status']) ?></span>
              </div>
            </div>
            <div class="form-group">
              <label>Requires Acknowledgement</label>
              <div class="form-control-plain"><?= (int) $policyData['requires_acknowledgement'] ? 'Yes' : 'No' ?></div>
            </div>
          </div>
          <?php if (!empty($policyData['description'])): ?>
            <div class="form-group">
              <label>Description</label>
              <div class="form-control-plain"><?= nl2br(htmlspecialchars($policyData['description'])) ?></div>
            </div>
          <?php endif; ?>
          <?php if (!empty($policyData['content'])): ?>
            <div class="form-group">
              <label>Policy Content</label>
              <div class="policy-content-box"><?= htmlspecialchars(strip_tags($policyData['content'])) ?></div>
            </div>
           <?php endif; ?>
        </div>
      </div>

      <div class="policy-card">
        <div class="policy-card-head">
          <h3><i class="bi bi-people"></i> Assignments (<?= count($assignments) ?>)</h3>
        </div>
        <div class="policy-card-body">
          <?php if (empty($assignments)): ?>
            <div class="policy-empty">No assignments yet.</div>
          <?php else: ?>
          <div class="policy-table-wrap">
            <table class="policy-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Assigned At</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assignments as $a): ?>
                  <tr>
                    <td data-label="Employee"><?= htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?></td>
                    <td data-label="Department"><?= htmlspecialchars($a['department_name'] ?? '—') ?></td>
                    <td data-label="Position"><?= htmlspecialchars($a['position_name'] ?? '—') ?></td>
                    <td data-label="Assigned At"><?= $a['assigned_at'] ? date('M d, Y', strtotime($a['assigned_at'])) : '—' ?></td>
                    <td data-label="Due Date"><?= $a['due_date'] ? date('M d, Y', strtotime($a['due_date'])) : '—' ?></td>
                    <td data-label="Status">
                      <?php
                        $assignmentStatus = strtolower($a['status'] ?? 'pending');
                        if ($assignmentStatus === 'acknowledged') $assignStampCls = 'published';
                        elseif ($assignmentStatus === 'overdue') $assignStampCls = 'violation';
                        else $assignStampCls = 'pending';
                      ?>
                      <span class="policy-stamp policy-stamp-<?= $assignStampCls ?>"><?= htmlspecialchars($a['status']) ?></span>
                    </td>
                    <td data-label="Actions">
                       <div class="policy-actions-mobile">
                         <a href="?page=acknowledgement-report&id=<?= (int) $policyId ?>&employee_id=<?= (int) $a['employee_id'] ?>" class="policy-mobile-action" aria-label="View" title="View">
                           <i class="bi bi-eye"></i>
                         </a>
                         <a href="?page=notification-compose&mode=new&notification_id=0&to_recipient_no=<?= urlencode($a['employee_no'] ?? '') ?>&to_recipient_name=<?= urlencode(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?>&notification_key=policy_reminder&policy_id=<?= (int) $policyId ?>" class="policy-mobile-action" aria-label="Send reminder" title="Send reminder">
                           <i class="bi bi-envelope"></i>
                         </a>
                       </div>
                       <button type="button"
                               class="policy-actions-toggle"
                               data-policy-menu-toggle
                               data-view-url="?page=acknowledgement-report&id=<?= (int) $policyId ?>&employee_id=<?= (int) $a['employee_id'] ?>"
                               data-remind-url="?page=notification-compose&mode=new&notification_id=0&to_recipient_no=<?= urlencode($a['employee_no'] ?? '') ?>&to_recipient_name=<?= urlencode(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?>&notification_key=policy_reminder&policy_id=<?= (int) $policyId ?>"
                               aria-label="Actions">
                         <i class="bi bi-three-dots-vertical"></i>
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

    <div class="policy-actions-menu" id="policyActionsMenu" hidden>
      <a href="#" data-menu-link="view"><i class="bi bi-eye"></i> View</a>
      <a href="#" data-menu-link="remind"><i class="bi bi-bell"></i> Remind</a>
    </div>

    <div class="policy-col-side">
      <div class="policy-side-card">
        <h4><i class="bi bi-bar-chart"></i> Acknowledgement Stats</h4>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Total Assigned</span>
          <span class="policy-quick-value"><?= number_format($total) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Acknowledged</span>
          <span class="policy-quick-value success"><?= number_format($ack) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Pending</span>
          <span class="policy-quick-value warning"><?= number_format($pending) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Overdue</span>
          <span class="policy-quick-value danger"><?= number_format($overdue) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Rate</span>
          <span class="policy-quick-value"><?= number_format($rate, 1) ?>%</span>
        </div>
      </div>

      <div class="policy-side-card">
        <h4><i class="bi bi-info-circle"></i> Policy Info</h4>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Code</span>
          <span class="policy-quick-value"><?= htmlspecialchars($policyData['policy_code']) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Version</span>
          <span class="policy-quick-value">v<?= htmlspecialchars($policyData['version']) ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Category</span>
          <span class="policy-quick-value"><?= htmlspecialchars($policyData['category_name'] ?? '—') ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Effective</span>
          <span class="policy-quick-value"><?= $policyData['effective_date'] ? date('M d, Y', strtotime($policyData['effective_date'])) : '—' ?></span>
        </div>
        <div class="policy-quick-stat">
          <span class="policy-quick-label">Deadline</span>
          <span class="policy-quick-value"><?= $policyData['acknowledgement_deadline'] ? date('M d, Y', strtotime($policyData['acknowledgement_deadline'])) : '—' ?></span>
        </div>
      </div>

      <div class="policy-side-card">
        <h4><i class="bi bi-lightbulb"></i> Actions</h4>
        <div class="policy-side-actions">
          <a href="?page=acknowledgement-report&id=<?= (int) $policyId ?>" class="policy-side-action policy-side-action-secondary">
            <i class="bi bi-bar-chart"></i> View Report
          </a>
          <a href="?page=policy-management" class="policy-side-action policy-side-action-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.policy-content-box {
  max-height: 400px;
  overflow-y: auto;
  padding: 12px;
  background: #f8f9fa;
  border: 1px solid var(--border,#e4e8ee);
  border-radius: 8px;
  white-space: pre-wrap;
  font-family: inherit;
  font-size: 0.9rem;
  line-height: 1.6;
}
</style>

<script>
(function() {
  var menu = document.getElementById('policyActionsMenu');
  if (!menu) return;

  function positionMenu(btn) {
    var r = btn.getBoundingClientRect();
    menu.style.left = '0px';
    menu.style.top = '0px';
    var mw = menu.offsetWidth, mh = menu.offsetHeight;
    var left = r.right - mw;
    if (left < 8) left = 8;
    if (left + mw > window.innerWidth - 8) {
      left = window.innerWidth - mw - 8;
    }
    var top = r.bottom + 6;
    if (top + mh > window.innerHeight - 8 && r.top - mh - 6 > 8) {
      top = r.top - mh - 6;
    }
    if (top < 8) top = 8;
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
  }

  function hideMenu() {
    menu.hidden = true;
    menu.classList.remove('show');
  }

  document.addEventListener('click', function(e) {
    var toggle = e.target.closest('[data-policy-menu-toggle]');
    if (toggle) {
      e.preventDefault();
      e.stopPropagation();

      var viewUrl = toggle.getAttribute('data-view-url') || '#';
      var remindUrl = toggle.getAttribute('data-remind-url') || '#';

      var viewLink = menu.querySelector('[data-menu-link="view"]');
      var remindLink = menu.querySelector('[data-menu-link="remind"]');

      if (viewLink) viewLink.href = viewUrl;
      if (remindLink) remindLink.href = remindUrl;

      positionMenu(toggle);
      menu.hidden = false;
      menu.classList.add('show');
      return;
    }

    if (!menu.contains(e.target)) {
      hideMenu();
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') hideMenu();
  });
})();

function sendReminder(policyId, employeeId) {
  if (!confirm('Send reminder to this employee?')) return;
  fetch('?page=policy-view&id=' + policyId + '&action=remind', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'policy_id=' + policyId + '&employee_id=' + employeeId
  }).then(r => r.json()).then(data => {
    if (data.success) {
      alert('Reminder sent successfully.');
    } else {
      alert(data.message || 'Failed to send reminder.');
    }
  });
}
</script>

<?php
if (isset($_GET['action']) && $_GET['action'] === 'remind' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $remindPolicyId = isset($_POST['policy_id']) ? (int) $_POST['policy_id'] : 0;
    $remindEmpId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
    if ($remindPolicyId > 0 && $remindEmpId > 0) {
        $policy->sendReminder($remindPolicyId, $remindEmpId, $policy->getCurrentUserEmployeeId());
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>