<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';
require_once __DIR__ . '/../classes/LaborLawReference.php';

$pageTitle = 'External Case Detail';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$caseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($caseId <= 0) {
    header('Location: ?page=dashboard-overview&msg=error|Invalid case ID');
    exit;
}

$db = (new Database())->getConnection();
$manager = new LegalCaseManager($db);
$case = $manager->getCase($caseId);

if (!$case) {
    header('Location: ?page=dashboard-overview&msg=error|Case not found');
    exit;
}

if (empty($case['external_agency'])) {
    header('Location: ?page=dashboard-overview&msg=error|This is not an external case');
    exit;
}

$workflow = $manager->getWorkflow($caseId);
$documents = $manager->getDocuments($caseId);
$history = $manager->getCaseHistory($caseId);

$assignedName = '';
if (!empty($case['assigned_to'])) {
    $assignedName = $manager->getEmployeeName((int) $case['assigned_to']);
}

$officers = [];
try {
    $stmt = $db->query("SELECT employee_id, first_name, last_name FROM em_employees WHERE employment_status = 'Active' ORDER BY first_name, last_name");
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$caseTypes = [
    'SEnA Request for Assistance','DOLE Complaint','DOLE Compliance Matter','DOLE Inspection',
    'DOLE Notice/Order','Labor Standards Concern','Labor Relations Concern','Government Referral','Other External Legal Matter'
];
$caseSources = [
    'Internal HR Complaint','Employee Directly Reported to DOLE','DOLE Notice',
    'SEnA Request for Assistance','DOLE Inspection','Government Referral',
    'Management Referral','Other External Source'
];
$docTypes = ['Agency Notice','Agency Email','Agency Letter','Request for Assistance','Conference Notice','HR Response','Employee Statement','Supporting Document','Compliance Document','Settlement Document','Other'];
$resolutionValues = ['Resolved','Settlement Reached','Compliance Completed','Referred','Withdrawn','No Further Action','Other'];

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

$relatedComplaint = null;
if (!empty($case['complaint_id'])) {
    try {
        $stmt = $db->prepare("SELECT id, type, description, status FROM lc_complaints WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int) $case['complaint_id']]);
        $relatedComplaint = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

$laborLawRefs = [];
try {
    $llm = new LaborLawReference($db);
    $laborLawRefs = $llm->getReferences(['search' => $case['case_title'] . ' ' . ($case['description'] ?? '')]);
} catch (Throwable $e) {}

$isOverdue = false;
if ($case['due_date'] && !in_array($case['current_status'], ['Closed','Cancelled','Resolved'], true)) {
    $isOverdue = new DateTime($case['due_date']) < new DateTime();
}

$pendingActions = array_filter($workflow, function($w) {
    return empty($w['completed_at']);
});
$overdueActions = array_filter($pendingActions, function($w) {
    return $w['due_date'] && new DateTime($w['due_date']) < new DateTime();
});
?>


<div class="module-content">
  <?php if ($flash): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="lc-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div id="ecDetailHeader"></div>

  <!-- Case Overview -->
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-info-circle"></i> Case Information</h3></div>
    <div class="ec-info-grid">
      <div class="ec-info-item"><div class="ec-info-label">Case Number</div><div class="ec-info-value"><?= htmlspecialchars($case['case_number']) ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">External Agency</div><div class="ec-info-value"><?= htmlspecialchars($case['external_agency'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Case Type</div><div class="ec-info-value"><?= htmlspecialchars($case['case_type'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Source</div><div class="ec-info-value"><?= htmlspecialchars($case['case_source'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Reference Number</div><div class="ec-info-value"><?= htmlspecialchars($case['external_reference_no'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Date Received</div><div class="ec-info-value"><?= htmlspecialchars($case['date_received'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Employee</div><div class="ec-info-value"><?= htmlspecialchars(trim(($case['first_name'] ?? '') . ' ' . ($case['last_name'] ?? '')) ?: '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Department</div><div class="ec-info-value"><?= htmlspecialchars($case['department_name'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Assigned To</div><div class="ec-info-value"><?= htmlspecialchars($assignedName ?: 'Unassigned') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Status</div><div class="ec-info-value"><span class="lc-status-stamp <?= ec_formatStatus($case['current_status']) ?>"><?= htmlspecialchars($case['current_status']) ?></span></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Due Date</div><div class="ec-info-value"><?= htmlspecialchars($case['due_date'] ?? '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Date Resolved</div><div class="ec-info-value"><?= htmlspecialchars($case['date_resolved'] ?? '—') ?></div></div>
      <div class="ec-info-item" style="grid-column:1/-1;"><div class="ec-info-label">Concern / Description</div><div class="ec-info-value"><?= nl2br(htmlspecialchars($case['description'] ?? '')) ?></div></div>
    </div>

    <?php if ($relatedComplaint): ?>
    <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border,#e4e8ee);">
      <div style="font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Related Internal Complaint</div>
      <a href="?page=complaint-workflow&id=<?= (int) $relatedComplaint['id'] ?>" class="lc-btn" style="font-size:0.72rem;">
        <i class="bi bi-link-45deg"></i> Complaint #<?= htmlspecialchars('CMP-' . str_pad($relatedComplaint['id'], 5, '0', STR_PAD_LEFT)) ?> — <?= htmlspecialchars($relatedComplaint['type'] ?? 'Complaint') ?>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Actions / Monitoring -->
  <div class="lc-card" id="ecActionsSection">
    <div class="lc-card-head">
      <h3><i class="bi bi-list-check"></i> Actions &amp; Monitoring</h3>
      <button type="button" class="lc-btn primary" onclick="document.getElementById('ecAddActionForm').scrollIntoView({behavior:'smooth'})" style="font-size:0.72rem;">
        <i class="bi bi-plus-lg"></i> Add Action
      </button>
    </div>
    <?php if (empty($workflow)): ?>
      <div class="lc-empty">No actions recorded yet. Use the form below to add required actions.</div>
    <?php else: ?>
    <div class="ec-actions-table-wrap">
      <table class="lc-table">
        <thead>
          <tr>
            <th>Action</th>
            <th>Assigned To</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Completed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($workflow as $w): 
            $isActionOverdue = $w['due_date'] && empty($w['completed_at']) && new DateTime($w['due_date']) < new DateTime();
            $actionStatus = $w['completed_at'] ? 'Completed' : ($isActionOverdue ? 'Overdue' : ($w['status'] ?: 'Pending'));
          ?>
          <tr>
            <td data-label="Action"><?= htmlspecialchars($w['action'] ?: $w['stage']) ?></td>
            <td data-label="Assigned To"><?= htmlspecialchars($w['assigned_name'] ?: '—') ?></td>
            <td data-label="Due Date"><?= htmlspecialchars($w['due_date'] ?: '—') ?></td>
            <td data-label="Status">
              <?php if ($actionStatus === 'Completed'): ?>
                <span class="lc-status-stamp lc-status-stamp--closed">Completed</span>
              <?php elseif ($actionStatus === 'Overdue'): ?>
                <span class="lc-status-stamp lc-status-stamp--overdue">Overdue</span>
              <?php else: ?>
                <span class="lc-status-stamp lc-status-stamp--investigating">Pending</span>
              <?php endif; ?>
            </td>
            <td data-label="Completed"><?= htmlspecialchars($w['completed_at'] ?: '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Add Action Form -->
    <form id="ecAddActionForm" style="margin-top:16px;" data-ec-action="add_workflow">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="ec-form-grid">
        <div class="lc-field">
          <label>Action *</label>
          <input type="text" name="action" placeholder="e.g. Submit payroll records" required>
        </div>
        <div class="lc-field">
          <label>Assigned To</label>
          <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach ($officers as $o): ?>
              <option value="<?= (int) $o['employee_id'] ?>"><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Due Date</label>
          <input type="date" name="due_date">
        </div>
        <div class="lc-field">
          <label>Status</label>
          <select name="status">
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Remarks</label>
          <textarea name="remarks" placeholder="Optional remarks..."></textarea>
        </div>
      </div>
      <button type="submit" class="lc-btn primary" style="margin-top:10px;"><i class="bi bi-plus-lg"></i> Add Action</button>
    </form>
  </div>

  <!-- Documents -->
  <div class="lc-card" id="ecDocumentsSection">
    <div class="lc-card-head"><h3><i class="bi bi-folder2-open"></i> Documents</h3></div>
    <div id="ecDocumentsList">
      <?php if (empty($documents)): ?>
        <div class="lc-empty">No documents uploaded yet.</div>
      <?php else: ?>
        <div class="lc-file-list">
          <?php foreach ($documents as $d): ?>
            <div class="lc-file-row">
              <div class="lc-file-text">
                <strong><?= htmlspecialchars($d['document_name']) ?></strong>
                <span><?= htmlspecialchars($d['document_type'] ?? '') ?> | <?= htmlspecialchars($d['created_at'] ?? '') ?> | Uploaded by <?= htmlspecialchars($d['uploaded_by_name'] ?? '—') ?></span>
              </div>
              <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="lc-btn-icon" title="Download"><i class="bi bi-download"></i></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <form id="ecUploadForm" style="margin-top:12px;" enctype="multipart/form-data" data-ec-action="upload_document">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="ec-form-grid">
        <div class="lc-field">
          <label>Document Type</label>
          <select name="document_type">
            <option value="">Select type</option>
            <?php foreach ($docTypes as $dt): ?>
              <option value="<?= htmlspecialchars($dt) ?>"><?= htmlspecialchars($dt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Document Date</label>
          <input type="date" name="document_date">
        </div>
        <div class="lc-field">
          <label>File</label>
          <input type="file" name="document" required>
          <div class="lc-hint">Allowed: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, txt, csv (max 10MB)</div>
        </div>
        <div class="lc-field">
          <label>Description</label>
          <input type="text" name="description">
        </div>
      </div>
      <button type="submit" class="lc-btn primary" style="margin-top:10px;"><i class="bi bi-upload"></i> Upload Document</button>
    </form>
  </div>

  <!-- Timeline / History -->
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-clock-history"></i> Case Timeline</h3></div>
    <div class="ec-timeline">
      <?php
      $timelineItems = [];
      foreach ($history as $h) {
        $parts = [];
        if ($h['previous_status'] && $h['new_status']) $parts[] = $h['previous_status'] . ' → ' . $h['new_status'];
        if ($h['previous_stage'] && $h['new_stage']) $parts[] = $h['previous_stage'] . ' → ' . $h['new_stage'];
        $changeDesc = $parts ? implode(', ', $parts) : $h['action'];
        $timelineItems[] = [
          'title' => $changeDesc,
          'meta' => date('M d, Y', strtotime($h['created_at'])) . ($h['performed_by_name'] ? ' by ' . $h['performed_by_name'] : ''),
          'text' => $h['remarks'] ?: '',
          'done' => true,
        ];
      }
      foreach ($workflow as $w) {
        $timelineItems[] = [
          'title' => ($w['action'] ?: $w['stage']) . ($w['completed_at'] ? ' (Completed)' : ''),
          'meta' => date('M d, Y', strtotime($w['created_at'])) . ($w['due_date'] ? ' | Due ' . date('M d, Y', strtotime($w['due_date'])) : ''),
          'text' => $w['remarks'] ?: '',
          'done' => !empty($w['completed_at']),
        ];
      }
      foreach ($documents as $d) {
        $timelineItems[] = [
          'title' => 'Document uploaded: ' . $d['document_name'],
          'meta' => date('M d, Y', strtotime($d['created_at'])) . ' by ' . ($d['uploaded_by_name'] ?: 'System'),
          'text' => ($d['document_type'] ?: ''),
          'done' => true,
        ];
      }
      usort($timelineItems, function($a, $b) { return strtotime($b['meta']) - strtotime($a['meta']); });

      if (empty($timelineItems)): ?>
        <div class="lc-empty">No timeline events yet.</div>
      <?php else: ?>
        <?php foreach ($timelineItems as $item): ?>
        <div class="lc-timeline-item">
          <div class="lc-timeline-dot <?= $item['done'] ? 'done' : 'pending' ?>"></div>
          <div class="lc-timeline-body">
            <div class="lc-timeline-title"><?= htmlspecialchars($item['title']) ?></div>
            <div class="lc-timeline-meta"><?= htmlspecialchars($item['meta']) ?></div>
            <?php if ($item['text']): ?>
              <div class="lc-timeline-text"><?= htmlspecialchars($item['text']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Resolution & Close -->
  <?php if (!in_array($case['current_status'], ['Closed','Cancelled','Resolved'], true)): ?>
  <div class="lc-card" id="ecResolutionSection">
    <div class="lc-card-head"><h3><i class="bi bi-check2-all"></i> Record Resolution &amp; Close Case</h3></div>
    <form id="ecResolutionForm" data-ec-action="close">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="lc-form-grid">
        <div class="lc-field">
          <label>Resolution *</label>
          <select name="resolution" required>
            <option value="">Select resolution</option>
            <?php foreach ($resolutionValues as $rv): ?>
              <option value="<?= htmlspecialchars($rv) ?>"><?= htmlspecialchars($rv) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Resolution Date</label>
          <input type="date" name="date_resolved" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Final Remarks</label>
          <textarea name="resolution_remarks" placeholder="Final remarks about the case outcome..."></textarea>
        </div>
      </div>
      <button type="submit" class="lc-btn danger" style="margin-top:10px;"><i class="bi bi-x-circle"></i> Close Case</button>
    </form>
  </div>
  <?php else: ?>
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-check2-all"></i> Resolution</h3></div>
    <div class="ec-info-grid">
      <div class="ec-info-item"><div class="ec-info-label">Resolution</div><div class="ec-info-value"><?= htmlspecialchars($case['resolution'] ?: '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Date Resolved</div><div class="ec-info-value"><?= htmlspecialchars($case['date_resolved'] ?: '—') ?></div></div>
      <div class="ec-info-item"><div class="ec-info-label">Date Closed</div><div class="ec-info-value"><?= htmlspecialchars($case['date_closed'] ?: '—') ?></div></div>
    </div>
  </div>
  <?php endif; ?>
</div>

<style>
.ec-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 18px;
}
.ec-info-item { display: flex; flex-direction: column; gap: 2px; }
.ec-info-label { font-size: 0.66rem; font-weight: 700; color: var(--text-400, #8b93a1); text-transform: uppercase; }
.ec-info-value { font-size: 0.78rem; font-weight: 600; color: var(--text-900, #1b2430); }
@media (max-width: 1100px) {
  .ec-info-grid { grid-template-columns: 1fr; }
}
.ec-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
@media (max-width: 1100px) {
  .ec-form-grid { grid-template-columns: 1fr; }
}
.ec-actions-table-wrap { overflow: auto; }
</style>
