<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';

$pageTitle = 'Create Legal Case';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$manager = new LegalCaseManager($db);

$caseTypes = [
    'SEnA Request for Assistance','DOLE Complaint','DOLE Compliance Matter','DOLE Inspection',
    'DOLE Notice/Order','Labor Standards Concern','Labor Relations Concern','Government Referral','Other External Legal Matter'
];
$caseSources = [
    'Internal HR Complaint','Employee Directly Reported to DOLE','DOLE Notice',
    'SEnA Request for Assistance','DOLE Inspection','Government Referral',
    'Management Referral','Other External Source'
];
$agencies = ['DOLE','NLRC','SSS','PhilHealth','Pag-IBIG','BIR','Other Government Agency'];
$priorities = ['Low','Medium','High','Critical'];
$statuses = ['Draft','Open','Under Assessment','Under Investigation','Awaiting Documents','Awaiting External Action','Conference Scheduled','In Conference','Settlement Reached','Referred','Compliance Action Required','Monitoring','Resolved','Closed','Cancelled'];
$stages = ['Case Intake','Initial Assessment','Document Collection','Investigation','Fact Finding','External Coordination','Conference','Mediation','Compliance Action','Settlement','Resolution','Monitoring','Case Closure'];

$officers = [];
try {
    $stmt = $db->query("SELECT employee_id, first_name, last_name FROM em_employees WHERE employment_status = 'Active' ORDER BY first_name, last_name");
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}
?>



<div class="module-content">
  <?php if ($flash): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="lc-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-plus-lg"></i> Create Legal Case</h3></div>
    <form id="lcCreateForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">

      <h4 style="margin:0 0 10px;font-size:0.82rem;font-weight:700;color:var(--text-900,#1b2430);">Case Information</h4>
      <div class="lc-form-grid">
        <div class="lc-field">
          <label>Case Title *</label>
          <input type="text" name="case_title" required>
        </div>
        <div class="lc-field">
          <label>Case Type *</label>
          <select name="case_type" required>
            <option value="">Select type</option>
            <?php foreach ($caseTypes as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Case Source *</label>
          <select name="case_source" required>
            <option value="">Select source</option>
            <?php foreach ($caseSources as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>External Agency</label>
          <select name="external_agency">
            <option value="">None</option>
            <?php foreach ($agencies as $a): ?>
              <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>External Reference No</label>
          <input type="text" name="external_reference_no">
        </div>
        <div class="lc-field">
          <label>Docket No</label>
          <input type="text" name="docket_no">
        </div>
        <div class="lc-field">
          <label>Date Received</label>
          <input type="date" name="date_received">
        </div>
        <div class="lc-field">
          <label>Date Filed</label>
          <input type="date" name="date_filed">
        </div>
        <div class="lc-field">
          <label>Priority</label>
          <select name="priority">
            <?php foreach ($priorities as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Initial Status</label>
          <select name="current_status">
            <?php foreach ($statuses as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Initial Stage</label>
          <select name="current_stage">
            <?php foreach ($stages as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
            <?php endforeach ?>
          </select>
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
      </div>

      <h4 style="margin:18px 0 10px;font-size:0.82rem;font-weight:700;color:var(--text-900,#1b2430);">Employee / Complainant</h4>
      <div class="lc-form-grid">
        <div class="lc-field">
          <label>Employee</label>
          <input type="hidden" name="employee_id" id="lcEmployeeId" value="">
          <input type="text" id="lcEmployeeSearch" placeholder="Search employee…" autocomplete="off">
          <div id="lcEmployeeSearchResults" style="position:relative;z-index:10;background:#fff;border:1px solid var(--border,#e4e8ee);border-radius:8px;max-height:200px;overflow:auto;width:100%;box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08));display:none;"></div>
          <div class="lc-hint" id="lcEmployeeDept"></div>
          <div class="lc-hint" id="lcEmployeePos"></div>
        </div>
      </div>

      <h4 style="margin:18px 0 10px;font-size:0.82rem;font-weight:700;color:var(--text-900,#1b2430);">Related Complaint</h4>
      <div class="lc-field">
        <select name="complaint_id" id="lcComplaintId">
          <option value="">No Internal Complaint</option>
        </select>
      </div>

      <h4 style="margin:18px 0 10px;font-size:0.82rem;font-weight:700;color:var(--text-900,#1b2430);">Case Details</h4>
      <div class="lc-form-grid">
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Description</label>
          <textarea name="description" placeholder="Case description…"></textarea>
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Alleged Concern</label>
          <textarea name="notes" placeholder="Alleged concern or violation…"></textarea>
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
         <a href="?page=dashboard-overview" class="lc-btn ghost">Cancel</a>
        <button type="submit" class="lc-btn primary"><i class="bi bi-check"></i> Create Case</button>
      </div>
    </form>
  </div>
</div>

