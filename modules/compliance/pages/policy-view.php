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

$perPage = 10;
$currentPage = isset($_GET['assign_page']) ? max(1, (int) $_GET['assign_page']) : 1;
$totalAssignments = $policy->countAssignments($policyId);
$totalPages = ($perPage > 0 && $totalAssignments > 0) ? (int) ceil($totalAssignments / $perPage) : 1;
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
$assignments = $policy->getAssignments($policyId, [], $perPage, $offset);

$filterStatus = '';
$filterAckStatus = '';

$categories = [];
$allAssignments = [];
$em_employees = [];
$em_departments = [];
$positions = [];
if ($db instanceof PDO && $policy instanceof Policy) {
    try {
        $categories = $policy->getCategories();
        $allAssignments = $policy->getAssignments($policyId, [], null, null);
        $em_employees = $policy->getEmployeesForAssignment();
        $em_departments = $policy->getDepartments();
        $positions = $policy->getPositions();
    } catch (Throwable $e) {
        $allAssignments = [];
        $em_employees = [];
        $em_departments = [];
        $positions = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && $db instanceof PDO && $policy instanceof Policy) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');

    $updateData = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
        'version' => trim((string) ($_POST['version'] ?? '1.0')),
        'effective_date' => !empty($_POST['effective_date']) ? $_POST['effective_date'] : null,
        'acknowledgement_deadline' => !empty($_POST['acknowledgement_deadline']) ? $_POST['acknowledgement_deadline'] : null,
        'status' => trim((string) ($_POST['status'] ?? 'Draft')),
        'requires_acknowledgement' => isset($_POST['requires_acknowledgement']) ? 1 : 0,
        'description' => trim((string) ($_POST['description'] ?? '')),
        'content' => trim((string) ($_POST['content'] ?? '')),
    ];

    if (empty($updateData['title'])) {
        echo json_encode(['success' => false, 'message' => 'Policy Title is required.']);
        exit;
    }

    if ($policy->updatePolicy($policyId, $updateData)) {
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Failed to update policy.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_assignments' && $db instanceof PDO && $policy instanceof Policy) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');

    $removeIds = isset($_POST['remove_ids']) && is_array($_POST['remove_ids']) ? array_map('intval', $_POST['remove_ids']) : [];
    $addIds = isset($_POST['add_ids']) && is_array($_POST['add_ids']) ? array_map('intval', $_POST['add_ids']) : [];
    $dueDate = !empty($_POST['assignment_due_date']) ? $_POST['assignment_due_date'] : null;

    foreach ($removeIds as $empId) {
        $policy->unassignPolicy($policyId, $empId);
    }

    if (!empty($addIds)) {
        $policy->assignPolicy($policyId, $addIds, $dueDate);
    }

    echo json_encode(['success' => true]);
    exit;
}
?>
<link rel="stylesheet" href="/modules/compliance/css/pages/policy-view.css?v=2">

<section class="policy-module">
  <div class="policy-row">
    <div class="policy-col-main">
      <div class="policy-card">
        <div class="policy-card-head">
          <h3>Policy Details</h3>
          <button type="button" class="policy-card-head-action" id="openEditModal">Edit</button>
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
              <div class="policy-content-box"><?= $policy->formatPolicyContent($policyData['content']) ?></div>
            </div>
          <?php endif; ?>
          </div>
      </div>
      <div class="policy-card">
        <div class="policy-card-head">
          <h3>Assignments (<?= number_format($totalAssignments) ?>)</h3>
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
          <?php if ($totalPages > 1): ?>
          <div class="policy-pagination">
            <span class="policy-pagination-info">
              Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalAssignments)) ?> of <?= number_format($totalAssignments) ?> records
            </span>
            <nav class="policy-pagination-nav" role="navigation" aria-label="Assignments pagination">
              <?php
              $baseUrl = '?page=policy-view';
              $qs = ['id' => (int) $policyId];
              $baseQs = $baseUrl . '&' . http_build_query($qs);
              $prevPage = $currentPage - 1;
              $nextPage = $currentPage + 1;
              ?>
              <a href="<?= $prevPage >= 1 ? $baseQs . '&assign_page=' . $prevPage : '#' ?>"
                 class="policy-page-btn" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>
                <i class="bi bi-chevron-left"></i>
              </a>
              <?php
              $range = 2;
              $start = max(1, $currentPage - $range);
              $end = min($totalPages, $currentPage + $range);
              for ($i = $start; $i <= $end; $i++):
              ?>
              <a href="<?= $baseQs . '&assign_page=' . $i ?>"
                 class="policy-page-btn <?= $i === $currentPage ? 'policy-page-btn--active' : '' ?>"><?= $i ?></a>
              <?php endfor; ?>
              <a href="<?= $nextPage <= $totalPages ? $baseQs . '&assign_page=' . $nextPage : '#' ?>"
                 class="policy-page-btn" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>
                <i class="bi bi-chevron-right"></i>
              </a>
            </nav>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="policy-actions-menu" id="policyActionsMenu" hidden>
      <a href="#" data-menu-link="view">View</a>
      <a href="#" data-menu-link="remind">Remind</a>
    </div>

    <div class="policy-col-side">
      <div class="policy-side-card">
        <h4>Acknowledgement Stats</h4>
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
        <h4>Policy Info</h4>
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
        <h4>Actions</h4>
        <div class="policy-side-actions">
          <a href="?page=acknowledgement-report&id=<?= (int) $policyId ?>" class="policy-side-action policy-side-action-secondary">
            View Report
          </a>
          <a href="?page=policy-management" class="policy-side-action policy-side-action-secondary">
            Back to List
          </a>
        </div>
      </div>
    </div>
   </div>
</section>

<div class="policy-modal-overlay" id="editPolicyModal" hidden>
  <div class="policy-modal">
    <div class="policy-modal-head">
      <h3>Edit Policy</h3>
      <button type="button" class="policy-modal-close" id="closeEditModal">&times;</button>
    </div>
    <div class="policy-modal-body">
      <form id="editPolicyForm" data-skip>
        <input type="hidden" name="action" value="update">
        <div class="policy-form-row">
          <div class="policy-form-group">
            <label for="edit_title">Policy Title <span style="color:var(--pv-danger);">*</span></label>
            <input type="text" id="edit_title" name="title" class="policy-form-control" required value="<?= htmlspecialchars($policyData['title']) ?>">
          </div>
          <div class="policy-form-group">
            <label for="edit_version">Version</label>
            <input type="text" id="edit_version" name="version" class="policy-form-control" value="<?= htmlspecialchars($policyData['version']) ?>">
          </div>
        </div>
        <div class="policy-form-row">
          <div class="policy-form-group">
            <label for="edit_category_id">Category</label>
            <select id="edit_category_id" name="category_id" class="policy-form-control">
              <option value="">— Select Category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= ((int) $policyData['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="policy-form-group">
            <label for="edit_status">Status</label>
            <select id="edit_status" name="status" class="policy-form-control">
              <?php
                $statuses = ['Draft','For Review','Approved','Published','Archived'];
                foreach ($statuses as $st):
                  $sel = ($policyData['status'] ?? 'Draft') === $st ? 'selected' : '';
              ?>
                <option value="<?= htmlspecialchars($st) ?>" <?= $sel ?>><?= htmlspecialchars($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="policy-form-row">
          <div class="policy-form-group">
            <label for="edit_effective_date">Effective Date</label>
            <input type="date" id="edit_effective_date" name="effective_date" class="policy-form-control" value="<?= htmlspecialchars($policyData['effective_date'] ?? '') ?>">
          </div>
          <div class="policy-form-group">
            <label for="edit_ack_deadline">Acknowledgement Deadline</label>
            <input type="date" id="edit_ack_deadline" name="acknowledgement_deadline" class="policy-form-control" value="<?= htmlspecialchars($policyData['acknowledgement_deadline'] ?? '') ?>">
          </div>
        </div>
        <div class="policy-form-group">
          <label for="edit_description">Description</label>
          <textarea id="edit_description" name="description" rows="3" class="policy-form-control"><?= htmlspecialchars($policyData['description'] ?? '') ?></textarea>
        </div>
        <div class="policy-form-group">
          <label for="edit_content">Policy Content</label>
          <textarea id="edit_content" name="content" rows="6" class="policy-form-control"><?= htmlspecialchars($policyData['content'] ?? '') ?></textarea>
        </div>
        <div class="policy-form-group">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12.5px; color:var(--pv-text);">
            <input type="checkbox" name="requires_acknowledgement" value="1" <?= (int) $policyData['requires_acknowledgement'] ? 'checked' : '' ?> style="width:14px; height:14px; accent-color:var(--pv-primary);">
            <span>Requires Acknowledgement</span>
          </label>
        </div>
      </form>

      <div class="policy-assignment-section" style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--pv-border-light);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <label style="font-size: 13px; font-weight: 600; color: var(--pv-text);">Assignments</label>
          <span class="policy-assignment-count" style="font-size: 11px; color: var(--pv-muted);"><?= number_format(count($allAssignments)) ?> assigned</span>
        </div>

        <div class="policy-assignment-list" style="max-height: 220px; overflow-y: auto; border: 1px solid var(--pv-border-light); border-radius: var(--pv-radius); margin-bottom: 12px;">
          <?php if (empty($allAssignments)): ?>
            <div class="policy-empty" style="padding: 14px; font-size: 12px; color: var(--pv-muted);">No assignments yet.</div>
          <?php else: ?>
            <table class="policy-table" style="font-size: 11.5px;">
              <thead>
                <tr>
                  <th style="padding: 8px 12px; font-size: 10px; text-transform: none; color: var(--pv-muted); border-bottom: 1px solid var(--pv-border-light);">Employee</th>
                  <th style="padding: 8px 12px; font-size: 10px; text-transform: none; color: var(--pv-muted); border-bottom: 1px solid var(--pv-border-light);">Department</th>
                  <th style="padding: 8px 12px; font-size: 10px; text-transform: none; color: var(--pv-muted); border-bottom: 1px solid var(--pv-border-light);">Due Date</th>
                  <th style="padding: 8px 12px; font-size: 10px; text-transform: none; color: var(--pv-muted); border-bottom: 1px solid var(--pv-border-light); width: 60px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allAssignments as $a): ?>
                  <tr class="policy-assignment-item" data-employee-id="<?= (int) $a['employee_id'] ?>">
                    <td data-label="Employee" style="padding: 8px 12px; border-bottom: 1px solid var(--pv-border-light);">
                      <?= htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?>
                    </td>
                    <td data-label="Department" style="padding: 8px 12px; border-bottom: 1px solid var(--pv-border-light); color: var(--pv-muted); font-size: 10px;">
                      <?= htmlspecialchars($a['department_name'] ?? '—') ?>
                    </td>
                    <td data-label="Due Date" style="padding: 8px 12px; border-bottom: 1px solid var(--pv-border-light); color: var(--pv-muted); font-size: 10px;">
                      <?= $a['due_date'] ? date('M d, Y', strtotime($a['due_date'])) : '—' ?>
                    </td>
                    <td style="padding: 8px 12px; border-bottom: 1px solid var(--pv-border-light); text-align: right;">
                      <button type="button" class="policy-remove-assignment" data-employee-id="<?= (int) $a['employee_id'] ?>" style="background: transparent; border: none; color: var(--pv-danger); cursor: pointer; font-size: 16px; line-height: 1;" title="Remove assignment">&times;</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <div class="policy-form-row" style="gap: 12px; align-items: flex-end;">
          <div class="policy-form-group" style="flex: 1;">
            <label for="assignment_due_date">Due Date (optional)</label>
            <input type="date" id="assignment_due_date" class="policy-form-control" value="<?= htmlspecialchars($policyData['acknowledgement_deadline'] ?? '') ?>">
          </div>
          <div class="policy-form-group" style="flex: 2;">
            <label for="assignment_employee_select">Add Employees</label>
            <select id="assignment_employee_select" class="policy-form-control" size="4" multiple style="height: auto;">
              <?php foreach ($em_employees as $emp): ?>
                <option value="<?= (int) $emp['employee_id'] ?>">
                  <?= htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '') . ' (' . ($emp['employee_code'] ?? '') . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="button" class="policy-btn-primary" id="addAssignmentsBtn" style="margin-top: 10px;">Add Selected Employees</button>
      </div>
    </div>
    <div class="policy-modal-foot">
      <button type="button" class="policy-btn-secondary" id="cancelEditModal">Cancel</button>
      <button type="submit" form="editPolicyForm" class="policy-btn-primary">Save Changes</button>
    </div>
  </div>
</div>

<style>
.policy-content-box {
  max-height: 400px;
  overflow-y: auto;
  padding: 12px;
  background: #f8f9fa;
  border: 1px solid var(--border,#e4e8ee);
  border-radius: 8px;
  white-space: pre-wrap;
  font-family: Arial, sans-serif;
  font-size: 12px;
  font-weight: normal;
  line-height: 1.5;
}
.policy-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; flex-wrap:wrap; font-size:0.75rem; color:var(--text-500,#64748b); }
.policy-pagination-info { font-size:0.75rem; color:var(--text-500,#64748b); white-space:nowrap; }
.policy-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:transparent; border:1px solid var(--border,#e4e8ee); border-radius:6px; overflow:hidden; }
.policy-pagination-nav .policy-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border:0; background:transparent; font-size:0.75rem; color:var(--text-700,#334155); cursor:pointer; text-decoration:none; transition:background-color .1s ease; }
.policy-pagination-nav .policy-page-btn:hover:not(.policy-page-btn--active) { background:var(--slate-100,#f1f5f9); }
.policy-pagination-nav .policy-page-btn[aria-disabled="true"] { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.policy-pagination-nav .policy-page-btn--active { background:#2563eb; color:#fff; }

.policy-card-head-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  padding: 0 12px;
  border-radius: 4px;
  border: 1px solid var(--pv-border);
  background: var(--pv-card);
  color: var(--pv-primary);
  font-size: 12px;
  cursor: pointer;
  text-decoration: none;
  transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.policy-card-head-action:hover {
  background: var(--pv-primary);
  color: #fff;
  border-color: var(--pv-primary);
}

.policy-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 10500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.policy-modal-overlay[hidden] { display: none; }
.policy-modal {
  background: #fff;
  border: 1px solid var(--pv-border);
  border-radius: 8px;
  width: 100%;
  max-width: 720px;
  max-height: calc(100vh - 40px);
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}
.policy-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--pv-border-light);
}
.policy-modal-head h3 {
  margin: 0;
  font-size: 14px;
  color: var(--pv-text);
  font-family: Arial, sans-serif;
}
.policy-modal-close {
  background: transparent;
  border: none;
  font-size: 20px;
  line-height: 1;
  color: var(--pv-muted);
  cursor: pointer;
  padding: 0 4px;
}
.policy-modal-close:hover { color: var(--pv-text); }
.policy-modal-body {
  padding: 16px;
  overflow-y: auto;
}
.policy-modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid var(--pv-border-light);
}
.policy-btn-primary, .policy-btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 34px;
  padding: 0 14px;
  border-radius: var(--pv-radius);
  font-size: 13px;
  cursor: pointer;
  border: 1px solid transparent;
}
.policy-btn-primary {
  background: var(--pv-primary);
  border-color: var(--pv-primary);
  color: #fff;
}
.policy-btn-primary:hover { background: var(--pv-primary-hover); border-color: var(--pv-primary-hover); }
.policy-btn-secondary {
  background: var(--pv-card);
  border-color: var(--pv-border);
  color: var(--pv-text);
}
.policy-btn-secondary:hover { background: var(--pv-bg); }

.policy-form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.policy-form-row + .policy-form-row { margin-top: 12px; }
.policy-form-group { display: flex; flex-direction: column; gap: 4px; }
.policy-form-group label { font-size: 11px; color: var(--pv-muted); }
.policy-form-control {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pv-border);
  border-radius: var(--pv-radius);
  font-size: 13px;
  color: var(--pv-text);
  background: #fff;
}
.policy-form-control:focus {
  outline: none;
  border-color: var(--pv-primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.12);
}
textarea.policy-form-control { resize: vertical; min-height: 80px; }
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

(function() {
  var modal = document.getElementById('editPolicyModal');
  var openBtn = document.getElementById('openEditModal');
  var closeBtn = document.getElementById('closeEditModal');
  var cancelBtn = document.getElementById('cancelEditModal');
  var form = document.getElementById('editPolicyForm');

  function openModal() { if (modal) { modal.hidden = false; } }
  function closeModal() { if (modal) { modal.hidden = true; } }

  if (openBtn) openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeModal();
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
  });

  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      var url = window.location.href.split('?')[0] + '?page=policy-view&id=<?= (int) $policyId ?>';
      fetch(url, { method: 'POST', body: formData, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text().then(function(text) { return { ok: r.ok, status: r.status, text: text }; }); })
        .then(function(resp) {
          if (!resp.ok) {
            alert('Server error (' + resp.status + '):\n' + resp.text.substring(0, 500));
            return;
          }
          try {
            var data = JSON.parse(resp.text);
            if (data.success) {
              closeModal();
              location.reload();
            } else {
              alert(data.message || 'Update failed.');
            }
          } catch (err) {
            alert('Invalid JSON response from server:\n\n' + (resp.text ? resp.text.substring(0, 1000) : 'empty response'));
          }
        })
        .catch(function(err) { alert('Network error: ' + (err.message || err)); });
    });
  }

  function assignmentUrl() {
    return window.location.href.split('?')[0] + '?page=policy-view&id=<?= (int) $policyId ?>&action=update_assignments';
  }

  function updateAssignmentCount() {
    var countEl = document.querySelector('.policy-assignment-count');
    var rows = document.querySelectorAll('.policy-assignment-item');
    if (countEl) countEl.textContent = numberFormat(rows.length) + ' assigned';
  }

  function numberFormat(num) {
    return (num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function removeAssignment(employeeId, row) {
    if (!confirm('Remove this assignment?')) return;
    var url = assignmentUrl();
    var params = new URLSearchParams();
    params.set('action', 'update_assignments');
    params.set('remove_ids[]', String(employeeId));
      fetch(url, { method: 'POST', body: params, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text().then(function(text) { return { ok: r.ok, status: r.status, text: text }; }); })
        .then(function(resp) {
          if (!resp.ok) {
            alert('Server error (' + resp.status + '):\n' + resp.text.substring(0, 500));
            return;
          }
          try {
            var data = JSON.parse(resp.text);
            if (data.success) {
              if (row && row.parentNode) row.parentNode.removeChild(row);
              updateAssignmentCount();
            } else {
              alert(data.message || 'Failed to remove assignment.');
            }
          } catch (err) {
            alert('Invalid JSON response from server:\n\n' + (resp.text ? resp.text.substring(0, 1000) : 'empty response'));
          }
        })
        .catch(function(err) { alert('Network error: ' + (err.message || err)); });
  }

  document.addEventListener('click', function(e) {
    var removeBtn = e.target.closest('.policy-remove-assignment');
    if (!removeBtn) return;
    var empId = removeBtn.getAttribute('data-employee-id');
    var row = removeBtn.closest('.policy-assignment-item');
    if (empId) removeAssignment(empId, row);
  });

  var addAssignmentsBtn = document.getElementById('addAssignmentsBtn');
  if (addAssignmentsBtn) {
    addAssignmentsBtn.addEventListener('click', function() {
      var select = document.getElementById('assignment_employee_select');
      var dueDateInput = document.getElementById('assignment_due_date');
      if (!select) return;
      var selected = Array.from(select.selectedOptions).map(function(opt) { return parseInt(opt.value, 10); });
      if (!selected.length) {
        alert('Please select at least one employee.');
        return;
      }
      var url = assignmentUrl();
      var params = new URLSearchParams();
      params.set('action', 'update_assignments');
      selected.forEach(function(id) { params.append('add_ids[]', String(id)); });
      if (dueDateInput && dueDateInput.value) params.set('assignment_due_date', dueDateInput.value);
      fetch(url, { method: 'POST', body: params, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text().then(function(text) { return { ok: r.ok, status: r.status, text: text }; }); })
        .then(function(resp) {
          if (!resp.ok) {
            alert('Server error (' + resp.status + '):\n' + resp.text.substring(0, 500));
            return;
          }
          try {
            var data = JSON.parse(resp.text);
            if (data.success) {
              location.reload();
            } else {
              alert(data.message || 'Failed to add assignments.');
            }
          } catch (err) {
            alert('Invalid JSON response from server:\n\n' + (resp.text ? resp.text.substring(0, 1000) : 'empty response'));
          }
        })
        .catch(function(err) { alert('Network error: ' + (err.message || err)); });
    });
  }
})();
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
