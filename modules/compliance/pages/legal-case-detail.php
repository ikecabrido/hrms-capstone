<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/LegalCaseManager.php';
require_once __DIR__ . '/../classes/LaborLawReference.php';

$pageTitle = 'Legal Case Detail';

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

$workflow = $manager->getWorkflow($caseId);
$conferences = $manager->getConferences($caseId);
$documents = $manager->getDocuments($caseId);
$updates = $manager->getExternalUpdates($caseId);

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
$confTypes = ['Internal Conference','HR Meeting','Management Meeting','SEnA Conference','DOLE Conference','Mediation','Other'];
$docTypes = ['DOLE Notice','Request for Assistance','Employee Complaint','HR Response','Employee Statement','Management Response','Payroll Record','Attendance Record','Employment Record','Policy','Labor Law Reference','Conference Minutes','Settlement Agreement','Compliance Document','Supporting Evidence','Other'];
$updateTypes = ['DOLE Notice Received','Response Submitted','Document Submitted','Conference Notice','Follow-up','Compliance Submission','Government Update','Other'];

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

?>


<div class="module-content">
  <?php if ($flash): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="lc-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div id="lcDetailHeader"></div>

  <!-- Case Overview -->
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-info-circle"></i> Case Overview</h3></div>
    <div id="lcDetailOverview"></div>
    <?php if ($relatedComplaint): ?>
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border,#e4e8ee);">
      <div style="font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Related Internal Complaint</div>
      <a href="?page=complaint-workflow&id=<?= (int) $relatedComplaint['id'] ?>" class="lc-btn" style="font-size:0.72rem;">
        <i class="bi bi-link-45deg"></i> Complaint #<?= htmlspecialchars('CMP-' . str_pad($relatedComplaint['id'], 5, '0', STR_PAD_LEFT)) ?> — <?= htmlspecialchars($relatedComplaint['type'] ?? 'Complaint') ?>
      </a>
    </div>
    <?php else: ?>
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border,#e4e8ee);">
      <div style="font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Case Origin</div>
      <span class="lc-type-badge">External Case Origin: Direct External Report</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Progress Tracker / Timeline -->
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-diagram-3"></i> Progress Tracker</h3></div>
    <div id="lcTimeline"></div>
  </div>

  <!-- Case History -->
  <div class="lc-card">
    <div class="lc-card-head"><h3><i class="bi bi-clock-history"></i> Case History</h3></div>
    <div id="lcCaseHistory">
      <div class="lc-empty">Loading history…</div>
    </div>
  </div>

  <!-- Labor Law References -->
  <div class="lc-card">
    <div class="lc-card-head">
      <h3><i class="bi bi-book"></i> Labor Law References</h3>
      <button type="button" class="lc-btn" onclick="lcToggleRefSearch()" style="font-size:0.72rem;">
        <i class="bi bi-search"></i> Find References
      </button>
    </div>
    <div id="lcRefSearch" style="display:none; margin-bottom:12px;">
      <div class="lc-field">
        <label>Search Labor Law References</label>
        <input type="text" id="lcRefSearchInput" placeholder="Search by title, keyword, or authority…" autocomplete="off">
        <div id="lcRefSearchResults" style="position:relative; z-index:10; background:#fff; border:1px solid var(--border,#e4e8ee); border-radius:8px; max-height:200px; overflow:auto; width:100%; box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); display:none; margin-top:4px;"></div>
      </div>
    </div>
    <div id="lcLaborLawList">
      <?php if (empty($laborLawRefs)): ?>
        <div class="lc-empty">No labor law references matched this case yet. Use the search above to find relevant references.</div>
      <?php else: ?>
        <div class="lc-ref-list">
          <?php foreach ($laborLawRefs as $ref): ?>
            <div class="lc-ref-row">
              <div class="lc-ref-text">
                <strong><?= htmlspecialchars($ref['title'] ?? $ref['short_title'] ?? 'Reference') ?></strong>
                <span><?= htmlspecialchars($ref['reference_number'] ?? '') ?> | <?= htmlspecialchars($ref['category_name'] ?? '') ?> | <?= htmlspecialchars($ref['issuing_authority'] ?? '') ?></span>
              </div>
              <a href="<?= htmlspecialchars($ref['source_url'] ?? '#') ?>" class="lc-btn-icon" title="View Reference" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Documents -->
  <div class="lc-card" id="lcUploadSection">
    <div class="lc-card-head"><h3><i class="bi bi-folder2-open"></i> Documents</h3></div>
    <div id="lcDocumentsList"></div>
    <form id="lcDocumentForm" style="margin-top:12px;" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="lc-form-grid">
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

  <!-- Conferences -->
  <div class="lc-card" id="lcConferenceFormSection">
    <div class="lc-card-head"><h3><i class="bi bi-bank"></i> Conferences</h3></div>
    <div id="lcConferencesList"></div>
    <form id="lcConferenceForm" style="margin-top:12px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="lc-form-grid">
        <div class="lc-field">
          <label>Conference Type</label>
          <select name="conference_type">
            <option value="">Select type</option>
            <?php foreach ($confTypes as $ct): ?>
              <option value="<?= htmlspecialchars($ct) ?>"><?= htmlspecialchars($ct) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Conference Date & Time</label>
          <input type="datetime-local" name="conference_date">
        </div>
        <div class="lc-field">
          <label>Location / Mode</label>
          <input type="text" name="location_or_mode">
        </div>
        <div class="lc-field">
          <label>Participants</label>
          <input type="text" name="participants">
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Agenda</label>
          <textarea name="agenda"></textarea>
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Minutes</label>
          <textarea name="minutes"></textarea>
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Outcome</label>
          <textarea name="outcome"></textarea>
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Next Action</label>
          <textarea name="next_action"></textarea>
        </div>
        <div class="lc-field">
          <label>Next Date</label>
          <input type="datetime-local" name="next_date">
        </div>
      </div>
      <button type="submit" class="lc-btn primary" style="margin-top:10px;"><i class="bi bi-plus-lg"></i> Add Conference</button>
    </form>
  </div>

  <!-- External Updates -->
  <div class="lc-card" id="lcExternalUpdateSection">
    <div class="lc-card-head"><h3><i class="bi bi-envelope"></i> External Updates</h3></div>
    <div id="lcExternalUpdatesList"></div>
    <form id="lcExternalUpdateForm" style="margin-top:12px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="lc-form-grid">
        <div class="lc-field">
          <label>Update Type</label>
          <select name="update_type">
            <option value="">Select type</option>
            <?php foreach ($updateTypes as $ut): ?>
              <option value="<?= htmlspecialchars($ut) ?>"><?= htmlspecialchars($ut) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="lc-field">
          <label>Reference No</label>
          <input type="text" name="reference_no">
        </div>
        <div class="lc-field">
          <label>Date Received</label>
          <input type="datetime-local" name="date_received">
        </div>
        <div class="lc-field">
          <label>Date Sent</label>
          <input type="datetime-local" name="date_sent">
        </div>
        <div class="lc-field">
          <label>Sender</label>
          <input type="text" name="sender">
        </div>
        <div class="lc-field">
          <label>Recipient</label>
          <input type="text" name="recipient">
        </div>
        <div class="lc-field" style="grid-column:1/-1;">
          <label>Summary</label>
          <textarea name="summary"></textarea>
        </div>
      </div>
      <button type="submit" class="lc-btn primary" style="margin-top:10px;"><i class="bi bi-plus-lg"></i> Add External Update</button>
    </form>
  </div>

  <!-- Resolution -->
  <div class="lc-card" id="lcResolutionSection">
    <div class="lc-card-head"><h3><i class="bi bi-check2-all"></i> Resolution</h3></div>
    <div id="lcResolutionText"></div>
    <form id="lcResolutionForm" style="margin-top:12px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(hm_csrf_token()) ?>">
      <div class="lc-field">
        <label>Resolution / Closure Reason</label>
        <textarea name="resolution" placeholder="Enter resolution details…" required></textarea>
      </div>
      <button type="submit" class="lc-btn danger"><i class="bi bi-x-circle"></i> Record Resolution & Close Case</button>
    </form>
  </div>
</div>



