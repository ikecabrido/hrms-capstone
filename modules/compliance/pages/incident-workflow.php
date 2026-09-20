<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';

$pageTitle = 'Incident Workflow';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$incidentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($incidentId <= 0) {
    header('Location: ?page=incident-reports&msg=error|Invalid incident ID');
    exit;
}

$incident = null;
try {
    $stmt = $db->prepare("SELECT * FROM lc_incident_report WHERE id = :id");
    $stmt->execute([':id' => $incidentId]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $incident = null;
}

if (!$incident) {
    header('Location: ?page=incident-reports&msg=error|Incident not found');
    exit;
}

$reporterEmail = '';
$reporterEmployeeNo = '';
try {
    if (!empty($incident['reporter_id'])) {
        $stmt = $db->prepare("SELECT email, employee_no FROM em_employees WHERE employee_id = :eid LIMIT 1");
        $stmt->execute([':eid' => (int) $incident['reporter_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reporterEmail = (string) ($row['email'] ?? '');
            $reporterEmployeeNo = (string) ($row['employee_no'] ?? '');
        }
    }
} catch (Throwable $e) {}

function ir_status_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['closed', 'resolved'], true)) return 'compliant';
    if (in_array($s, ['under_review', 'investigation'], true)) return 'info';
    return 'pending';
}
function ir_severity_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['critical', 'high'], true)) return 'high';
    if ($s === 'medium') return 'med';
    return 'low';
}
function ir_status_label(string $s): string {
    $map = [
        'submitted'     => 'Received',
        'under_review'  => 'Under Review',
        'investigation' => 'Investigation',
        'escalated'     => 'Corrective Action',
        'resolved'      => 'Compliance Verification',
        'closed'        => 'Closed',
    ];
    return $map[strtolower($s)] ?? ucfirst($s);
}
function ir_category_icon(string $cat): string {
    $cat = strtolower($cat);
    if (str_contains($cat, 'workplace accident') || str_contains($cat, 'occupational')) return 'bi bi-briefcase';
    if (str_contains($cat, 'health incident')) return 'bi bi-heart-pulse';
    if (str_contains($cat, 'medical emergency')) return 'bi bi-hospital';
    if (str_contains($cat, 'environmental') || str_contains($cat, 'safety hazard')) return 'bi bi-tree';
    if (str_contains($cat, 'exposure')) return 'bi bi-droplet';
    if (str_contains($cat, 'near miss')) return 'bi bi-lightning';
    if (str_contains($cat, 'return-to-work') || str_contains($cat, 'return to work')) return 'bi bi-arrow-return-left';
    return 'bi bi-folder2-open';
}

$category = strtolower($incident['incident_type'] ?? '');
$isHazardOrAccident = str_contains($category, 'accident') || str_contains($category, 'environmental') || str_contains($category, 'safety hazard') || str_contains($category, 'exposure') || str_contains($category, 'near miss');

$currentStatus = strtolower($incident['status'] ?? 'submitted');

$workflowSteps = [
    ['key' => 'incident_occurs',          'label' => 'Incident Occurs',                  'icon' => 'bi bi-exclamation-circle'],
    ['key' => 'clinic_responds',          'label' => 'Clinic Responds',                  'icon' => 'bi bi-heart-pulse'],
    ['key' => 'clinic_submits',           'label' => 'Clinic Submits Report',            'icon' => 'bi bi-file-earmark-text'],
    ['key' => 'legal_receives',           'label' => 'Legal & Compliance Receives',      'icon' => 'bi bi-inbox'],
    ['key' => 'review_classify',          'label' => 'Review & Classification',          'icon' => 'bi bi-search'],
    ['key' => 'investigation',            'label' => 'Investigation Conducted',          'icon' => 'bi bi-magnifying-glass'],
    ['key' => 'root_cause',               'label' => 'Root Cause Identified',            'icon' => 'bi bi-diagram-3'],
];

if ($isHazardOrAccident) {
    $workflowSteps[] = ['key' => 'hazard_check',             'label' => 'Hazard Found?',                    'icon' => 'bi bi-question-diamond'];
    $workflowSteps[] = ['key' => 'corrective_request',       'label' => 'Create Corrective Action',         'icon' => 'bi bi-wrench'];
    $workflowSteps[] = ['key' => 'assign_department',        'label' => 'Assign to Department',             'icon' => 'bi bi-people'];
    $workflowSteps[] = ['key' => 'department_repair',        'label' => 'Department Performs Repair',       'icon' => 'bi bi-tools'];
    $workflowSteps[] = ['key' => 'proof_submitted',          'label' => 'Proof of Completion',              'icon' => 'bi bi-paperclip'];
    $workflowSteps[] = ['key' => 'legal_verifies',           'label' => 'Legal Verification',               'icon' => 'bi bi-check-circle'];
    $workflowSteps[] = ['key' => 'hazard_eliminated',        'label' => 'Hazard Eliminated?',               'icon' => 'bi bi-question-diamond'];
    $workflowSteps[] = ['key' => 'close_archive',            'label' => 'Close & Archive',                  'icon' => 'bi bi-archive'];
} else {
    $workflowSteps[] = ['key' => 'compliance_verify',        'label' => 'Compliance Verification',          'icon' => 'bi bi-check-circle'];
    $workflowSteps[] = ['key' => 'close_archive',            'label' => 'Close & Archive',                  'icon' => 'bi bi-archive'];
}

$currentStepIndex = 0;
$statusStepMap = [
    'submitted'     => 'clinic_submits',
    'under_review'  => 'review_classify',
    'investigation' => 'investigation',
    'escalated'     => $isHazardOrAccident ? 'corrective_request' : 'compliance_verify',
    'resolved'      => $isHazardOrAccident ? 'legal_verifies' : 'compliance_verify',
    'closed'        => 'close_archive',
];

$targetStep = $statusStepMap[$currentStatus] ?? 'clinic_submits';
foreach ($workflowSteps as $idx => $step) {
    if ($step['key'] === $targetStep) {
        $currentStepIndex = $idx;
        break;
    }
}

$sevClass = ir_severity_class($incident['severity']);
$statusClass = ir_status_class($incident['status']);
$catIcon = ir_category_icon($incident['incident_type']);
?>

<!-- Evidence Modal -->
<div id="irwfEvidenceModal" class="lc-modal-backdrop" onclick="if(event.target===this)irwfCloseModal('irwfEvidenceModal')">
  <div class="lc-modal" style="max-width:640px;">
    <div class="lc-modal-header">
      <div class="lc-modal-title"><i class="bi bi-eye"></i> Evidence</div>
      <button type="button" class="lc-modal-close" onclick="irwfCloseModal('irwfEvidenceModal')">&times;</button>
    </div>
    <div class="lc-modal-body" id="irwfEvidenceBody">
      <div class="irwf-evidence-loading">Loading...</div>
    </div>
    <div class="lc-modal-body" style="border-top:1px solid var(--hairline); padding-top:12px;">
      <span id="irwfEvidenceStatus" class="irwf-action-status"></span>
    </div>
  </div>
</div>

<!-- Witness Modal -->
<div id="irwfWitnessModal" class="lc-modal-backdrop" onclick="if(event.target===this)irwfCloseModal('irwfWitnessModal')">
  <div class="lc-modal" style="max-width:640px;">
    <div class="lc-modal-header">
      <div class="lc-modal-title"><i class="bi bi-person-lines-fill"></i> Add Witness Statement</div>
      <button type="button" class="lc-modal-close" onclick="irwfCloseModal('irwfWitnessModal')">&times;</button>
    </div>
    <div class="lc-modal-body">
      <textarea id="irwfWitnessText" class="irwf-textarea" rows="6" placeholder="Enter witness statement..."></textarea>
    </div>
    <div class="lc-modal-body" style="border-top:1px solid var(--hairline); padding-top:12px; display:flex; gap:8px; justify-content:flex-end; align-items:center;">
      <span id="irwfWitnessStatus" class="irwf-action-status" style="margin-right:auto;"></span>
      <button type="button" class="cc-btn" onclick="irwfCloseModal('irwfWitnessModal')">Cancel</button>
      <button type="button" class="cc-btn primary" onclick="irwfSaveWitnessStatement()">Save Statement</button>
    </div>
  </div>
</div>

<section class="irwf-page">
   <div class="irwf-card">
    <h3><i class="bi bi-info-circle"></i> Incident Information</h3>
    <div class="irwf-grid">
      <div class="irwf-field">
        <div class="irwf-label">Incident Number</div>
        <div class="irwf-value mono"><?= htmlspecialchars($incident['incident_id'], ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Category</div>
        <div class="irwf-value"><i class="bi <?= $catIcon ?>"></i> <?= htmlspecialchars($incident['incident_type'] ?? 'Other', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Type</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['type'] ? str_replace('_', ' ', $incident['type']) : 'Other', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Severity</div>
        <div class="irwf-value"><span class="irwf-badge severity-<?= htmlspecialchars($incident['severity'], ENT_QUOTES) ?>"><?= htmlspecialchars(ucfirst($incident['severity']), ENT_QUOTES) ?></span></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Current Status</div>
        <div class="irwf-value"><span class="irwf-badge status-<?= htmlspecialchars($incident['status'], ENT_QUOTES) ?>"><?= htmlspecialchars(ir_status_label($incident['status']), ENT_QUOTES) ?></span></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Date & Time</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['incident_date'] ?? '—', ENT_QUOTES) ?> <?= htmlspecialchars($incident['incident_time'] ?? '', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Location</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['location'] ?? '—', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Report Source</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['reporter_department'] ?? '—', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Reported By</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['reporter_name'] ?? 'Unassigned', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Assigned Officer</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['assigned_name'] ?? 'Unassigned', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field full">
        <div class="irwf-label">Description</div>
        <div class="irwf-value"><?= nl2br(htmlspecialchars($incident['description'] ?? '', ENT_QUOTES)) ?></div>
      </div>
    </div>
  </div>

  <div class="irwf-card">
    <h3><i class="bi bi-diagram-3"></i> Incident Reporting Workflow</h3>
    <div class="irwf-flow">
      <?php foreach ($workflowSteps as $idx => $step):
        $stepClass = '';
        $badgeClass = 'badge-pending';
        $badgeText = 'Pending';
        if ($idx < $currentStepIndex) {
          $stepClass = 'completed';
          $badgeClass = 'badge-completed';
          $badgeText = 'Completed';
        } elseif ($idx === $currentStepIndex) {
          $stepClass = 'current';
          $badgeClass = 'badge-current';
          $badgeText = 'Current';
        }
      ?>
        <div class="irwf-flow-step <?= $stepClass ?>">
          <div class="irwf-flow-dot"></div>
          <div class="irwf-flow-body">
            <div class="irwf-flow-title">
              <span class="irwf-flow-icon"><i class="<?= $step['icon'] ?>"></i></span>
              <?= htmlspecialchars($step['label'], ENT_QUOTES) ?>
              <span class="irwf-flow-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
            </div>
            <?php if ($step['key'] === 'hazard_check'): ?>
              <div class="irwf-decision">
                <div class="irwf-decision-title">Decision Point</div>
                <div class="irwf-decision-options">
                  <span class="irwf-decision-opt <?= $currentStatus === 'closed' || $currentStatus === 'resolved' ? 'active' : '' ?>">No → Case Closed</span>
                  <span class="irwf-decision-opt <?= in_array($currentStatus, ['escalated', 'investigation', 'under_review', 'submitted'], true) ? '' : 'active' ?>">Yes → Corrective Action</span>
                </div>
              </div>
            <?php elseif ($step['key'] === 'hazard_eliminated'): ?>
              <div class="irwf-decision">
                <div class="irwf-decision-title">Decision Point</div>
                <div class="irwf-decision-options">
                  <span class="irwf-decision-opt <?= $currentStatus === 'closed' ? 'active' : '' ?>">Yes → Close Incident</span>
                  <span class="irwf-decision-opt <?= $currentStatus !== 'closed' ? '' : 'active' ?>">No → Return for Correction</span>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($idx <= $currentStepIndex && $step['key'] !== 'hazard_check' && $step['key'] !== 'hazard_eliminated'): ?>
              <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['updated_at'])) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="irwf-card">
    <h3><i class="bi bi-journal-text"></i> Process Summary</h3>
    <div class="irwf-grid">
      <div class="irwf-field">
        <div class="irwf-label">Current Phase</div>
        <div class="irwf-value"><?= htmlspecialchars(ir_status_label($incident['status']), ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Incident Type</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['incident_type'] ?? 'Other', ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Severity</div>
        <div class="irwf-value"><span class="irwf-badge severity-<?= htmlspecialchars($incident['severity'], ENT_QUOTES) ?>"><?= htmlspecialchars(ucfirst($incident['severity']), ENT_QUOTES) ?></span></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Report Source</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['reporter_department'] ?? '—', ENT_QUOTES) ?></div>
      </div>
    </div>
  </div>

  <div class="irwf-card">
    <h3><i class="bi bi-list-check"></i> Workflow Actions</h3>
    <p style="margin:0 0 12px;font-size:0.82rem;color:var(--text-600,#5b6472);">Advance, reopen, or close this incident. Status changes are recorded in the workflow history.</p>
    <div class="irwf-actions" id="irwfActions" style="margin-bottom:12px;">
      <?php if ($currentStatus === 'submitted'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">
          <i class="bi bi-check2-circle"></i> Accept for Review
        </button>
        <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">
          <i class="bi bi-x-circle"></i> Close Incident
        </button>
      <?php elseif ($currentStatus === 'under_review'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">
          <i class="bi bi-check2-circle"></i> Start Investigation
        </button>
        <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">
          <i class="bi bi-x-circle"></i> Close Incident
        </button>
      <?php elseif ($currentStatus === 'investigation'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">
          <i class="bi bi-check2-circle"></i> Escalate to Corrective Action
        </button>
        <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">
          <i class="bi bi-x-circle"></i> Close Incident
        </button>
      <?php elseif ($currentStatus === 'escalated'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">
          <i class="bi bi-check2-circle"></i> Verify Completion
        </button>
        <button class="cc-btn" onclick="irwfSubmitAction('reopen', this)">
          <i class="bi bi-arrow-counterclockwise"></i> Reopen Investigation
        </button>
        <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">
          <i class="bi bi-x-circle"></i> Close Incident
        </button>
      <?php elseif ($currentStatus === 'resolved'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('close', this)">
          <i class="bi bi-check2-circle"></i> Close Incident
        </button>
        <button class="cc-btn" onclick="irwfSubmitAction('reopen', this)">
          <i class="bi bi-arrow-counterclockwise"></i> Reopen Investigation
        </button>
      <?php elseif ($currentStatus === 'closed'): ?>
        <button class="cc-btn primary" onclick="irwfSubmitAction('reopen', this)">
          <i class="bi bi-arrow-counterclockwise"></i> Reopen Case
        </button>
      <?php endif; ?>
      <span id="irwfActionStatus" class="irwf-action-status"></span>
    </div>
  <div class="irwf-card">
    <h3><i class="bi bi-clock-history"></i> Key Milestones</h3>
    <div class="irwf-flow">
      <div class="irwf-flow-step completed">
        <div class="irwf-flow-dot"></div>
        <div class="irwf-flow-body">
          <div class="irwf-flow-title">
            <span class="irwf-flow-icon"><i class="bi bi-calendar-check"></i></span>
            Incident Reported
          </div>
          <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['created_at'])) ?></div>
        </div>
      </div>
      <div class="irwf-flow-step completed">
        <div class="irwf-flow-dot"></div>
        <div class="irwf-flow-body">
          <div class="irwf-flow-title">
            <span class="irwf-flow-icon"><i class="bi bi-calendar-check"></i></span>
            Last Updated
          </div>
          <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['updated_at'])) ?></div>
        </div>
      </div>
      <?php if (in_array($currentStatus, ['resolved', 'closed'], true)): ?>
      <div class="irwf-flow-step completed">
        <div class="irwf-flow-dot"></div>
        <div class="irwf-flow-body">
          <div class="irwf-flow-title">
            <span class="irwf-flow-icon"><i class="bi bi-calendar-check"></i></span>
            Closure Date
          </div>
          <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['updated_at'])) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(function(){
  window.irwfSubmitAction = function(action, btn) {
    var statusEl = document.getElementById('irwfActionStatus');
    if (!statusEl) return;
    if (!confirm('Update incident status? This will advance the workflow accordingly.')) return;

    statusEl.textContent = 'Updating...';
    if (btn) btn.disabled = true;

    var apiUrl = './lib/api/incident_workflow_action.php';

    var formData = new FormData();
    formData.append('incident_id', '<?= (int)$incident['id'] ?>');
    formData.append('action', action);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'irwf-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response. Check console.';
        statusEl.className = 'irwf-action-status error';
        console.error('Invalid JSON', xhr.responseText);
        if (btn) btn.disabled = false;
        return;
      }
      if (data.success) {
        statusEl.textContent = 'Updated. Reloading...';
        statusEl.className = 'irwf-action-status success';
        setTimeout(function() { location.reload(); }, 600);
      } else {
        statusEl.textContent = data.message || 'Update failed.';
        statusEl.className = 'irwf-action-status error';
        if (btn) btn.disabled = false;
      }
    };
    xhr.onerror = function() {
      console.error('Workflow action network error. URL:', apiUrl);
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'irwf-action-status error';
      if (btn) btn.disabled = false;
    };
    xhr.send(formData);
  }

  window.irwfCloseModal = function(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
  };

  window.irwfOpenModal = function(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
  };

  window.irwfViewEvidence = function() {
    var statusEl = document.getElementById('irwfEvidenceStatus');
    var bodyEl = document.getElementById('irwfEvidenceBody');
    if (!bodyEl) return;
    
    statusEl.textContent = '';
    statusEl.className = 'irwf-action-status';
    bodyEl.innerHTML = '<div class="irwf-evidence-loading">Loading...</div>';
    irwfOpenModal('irwfEvidenceModal');
    
    var apiUrl = './lib/api/incident_evidence.php';
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl + '?incident_id=<?= (int)$incident['id'] ?>', true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (statusEl) {
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      }
      if (xhr.status < 200 || xhr.status >= 300) {
        if (statusEl) statusEl.className = 'irwf-action-status error';
        bodyEl.innerHTML = '<div class="irwf-evidence-empty"><i class="bi bi-exclamation-triangle"></i> Failed to load evidence.</div>';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        if (statusEl) {
          statusEl.textContent = 'Invalid server response.';
          statusEl.className = 'irwf-action-status error';
        }
        bodyEl.innerHTML = '<div class="irwf-evidence-empty"><i class="bi bi-exclamation-triangle"></i> Invalid server response.</div>';
        return;
      }
      if (data.success && data.evidence && data.evidence.length > 0) {
        var baseUrl = 'modules/compliance/';
        var html = '<div class="irwf-evidence-list">';
        for (var i = 0; i < data.evidence.length; i++) {
          var ev = data.evidence[i];
          var fileUrl = baseUrl + ev.file_path;
          html += '<div class="irwf-evidence-item">';
          html += '<div class="irwf-evidence-icon"><i class="bi bi-file-earmark"></i></div>';
          html += '<div class="irwf-evidence-details">';
          html += '<div class="irwf-evidence-name"><a href="' + fileUrl + '" target="_blank">' + ev.file_name + '</a></div>';
          if (ev.description) html += '<div class="irwf-evidence-desc">' + ev.description + '</div>';
          html += '<div class="irwf-evidence-meta">' + (ev.file_type || '') + ' &middot; ' + (ev.file_size ? Math.round(ev.file_size / 1024) + ' KB' : '') + ' &middot; Uploaded ' + ev.uploaded_at + '</div>';
          html += '</div></div>';
        }
        html += '</div>';
        bodyEl.innerHTML = html;
        if (statusEl) {
          statusEl.textContent = '';
          statusEl.className = 'irwf-action-status';
        }
      } else {
        if (statusEl) {
          statusEl.textContent = '';
          statusEl.className = 'irwf-action-status';
        }
        bodyEl.innerHTML = '<div class="irwf-evidence-empty" title="No evidence uploaded by complainant/reported"><i class="bi bi-info-circle"></i> No evidence uploaded. The complainant/reported person has not uploaded any supporting documents yet.</div>';
      }
    };
    xhr.onerror = function() {
      if (statusEl) {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
      }
      bodyEl.innerHTML = '<div class="irwf-evidence-empty"><i class="bi bi-exclamation-triangle"></i> Network error.</div>';
    };
    xhr.send();
  };

  window.irwfRequestAdditionalEvidence = function() {
    var reporterName = <?= json_encode($incident['reporter_name'] ?? '') ?>;
    var reporterEmail = <?= json_encode($reporterEmail) ?>;
    var reporterNo = <?= json_encode($reporterEmployeeNo) ?>;
    var incidentId = <?= json_encode($incident['incident_id']) ?>;
    var incidentType = <?= json_encode($incident['incident_type'] ?? '') ?>;
    
    var subject = encodeURIComponent('Request for Additional Evidence - ' + incidentId + ' (' + incidentType + ')');
    var body = encodeURIComponent("Dear " + (reporterName || 'Employee') + ",\n\nWe need additional evidence regarding incident " + incidentId + " (" + incidentType + ").\n\nPlease provide the following at your earliest convenience:\n- Any supporting documents or evidence\n- Additional details regarding the incident\n- Names of any additional witnesses\n\nYour prompt response is appreciated.\n\nThank you.");
    var name = encodeURIComponent(reporterName);
    var email = encodeURIComponent(reporterEmail);
    var no = encodeURIComponent(reporterNo);
    
    window.location.href = '?page=notification-compose&mode=new&to_recipient_name=' + name + '&to_recipient_email=' + email + '&to_recipient_no=' + no + '&subject=' + subject + '&body=' + body;
  };

  window.irwfOpenWitnessModal = function() {
    var textarea = document.getElementById('irwfWitnessText');
    if (textarea) textarea.value = '';
    irwfOpenModal('irwfWitnessModal');
    var statusEl = document.getElementById('irwfWitnessStatus');
    if (statusEl) {
      statusEl.textContent = '';
      statusEl.className = 'irwf-action-status';
    }
  };

  window.irwfSaveWitnessStatement = function() {
    var textarea = document.getElementById('irwfWitnessText');
    var statement = textarea ? textarea.value.trim() : '';
    var statusEl = document.getElementById('irwfWitnessStatus');
    if (!statement) {
      if (statusEl) {
        statusEl.textContent = 'Please enter a witness statement.';
        statusEl.className = 'irwf-action-status error';
      }
      return;
    }
    
    var apiUrl = './lib/api/incident_witness_statement.php';
    
    var formData = new FormData();
    formData.append('incident_id', '<?= (int)$incident['id'] ?>');
    formData.append('statement', statement);
    
    var btn = document.querySelector('#irwfWitnessModal .cc-btn.primary');
    if (btn) btn.disabled = true;
    if (statusEl) {
      statusEl.textContent = 'Saving...';
      statusEl.className = 'irwf-action-status';
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (btn) btn.disabled = false;
      if (statusEl) {
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      }
      if (xhr.status < 200 || xhr.status >= 300) {
        if (statusEl) {
          statusEl.textContent = 'Request failed (' + xhr.status + ').';
          statusEl.className = 'irwf-action-status error';
        }
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        if (statusEl) {
          statusEl.textContent = 'Invalid server response.';
          statusEl.className = 'irwf-action-status error';
        }
        return;
      }
      if (data.success) {
        if (statusEl) {
          statusEl.textContent = 'Statement saved successfully.';
          statusEl.className = 'irwf-action-status success';
        }
        if (textarea) textarea.value = '';
        setTimeout(function() {
          irwfCloseModal('irwfWitnessModal');
          if (statusEl) {
            statusEl.textContent = '';
            statusEl.className = 'irwf-action-status';
          }
        }, 800);
      } else {
        if (statusEl) {
          statusEl.textContent = data.message || 'Save failed.';
          statusEl.className = 'irwf-action-status error';
        }
      }
    };
    xhr.onerror = function() {
      if (btn) btn.disabled = false;
      if (statusEl) {
        statusEl.textContent = 'Network error.';
        statusEl.className = 'irwf-action-status error';
      }
    };
    xhr.send(formData);
  };
  })();
  </script>

<style>
  .irwf-page { padding: 0; }
  .irwf-card { background: var(--card-bg, #fff); border: 1px solid var(--border, #e4e8ee); border-radius: 14px; padding: 18px; box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04)); margin-bottom: 16px; }
  .irwf-card h3 { margin: 0 0 14px; font-size: 0.98rem; font-weight: 700; color: var(--text-900, #1b2430); display: flex; align-items: center; gap: 8px; }
  .irwf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
  .irwf-field { background: rgba(13,27,46,.02); border: 1px solid var(--border, #e4e8ee); border-radius: 10px; padding: 12px 14px; }
  .irwf-field.full { grid-column: 1 / -1; }
  .irwf-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-500, #6b7280); letter-spacing: .04em; margin-bottom: 4px; }
  .irwf-value { font-size: 0.88rem; font-weight: 600; color: var(--text-900, #1b2430); word-break: break-word; }
  .irwf-value.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

  .irwf-flow { display: flex; flex-direction: column; gap: 0; position: relative; padding-left: 28px; }
  .irwf-flow-step { position: relative; padding-bottom: 24px; }
  .irwf-flow-step:last-child { padding-bottom: 0; }
  .irwf-flow-step::before { content: ''; position: absolute; left: -20px; top: 0; bottom: 0; width: 2px; background: var(--hairline, #dde3ea); }
  .irwf-flow-step:last-child::before { bottom: 50%; }
  .irwf-flow-step.completed::before { background: #1f7a5c; }
  .irwf-flow-step.completed:not(:last-child)::before { background: #1f7a5c; }
  .irwf-flow-step.current::before { background: repeating-linear-gradient(180deg, #c97f1d 0 6px, var(--hairline, #dde3ea) 6px 12px); }
  .irwf-flow-dot { position: absolute; left: -26px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: var(--hairline, #dde3ea); border: 2px solid #fff; box-shadow: 0 0 0 1px var(--hairline, #dde3ea); z-index: 1; transition: all .15s ease; }
  .irwf-flow-step.completed .irwf-flow-dot { background: #1f7a5c; box-shadow: 0 0 0 1px #1f7a5c; }
  .irwf-flow-step.current .irwf-flow-dot { background: #c97f1d; box-shadow: 0 0 0 1px #c97f1d, 0 0 0 5px rgba(201,127,29,.18); }
  .irwf-flow-body { padding-left: 8px; }
  .irwf-flow-title { font-size: 0.88rem; font-weight: 700; color: var(--text-900, #1b2430); display: flex; align-items: center; gap: 8px; }
  .irwf-flow-icon { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(13,27,46,.04); color: var(--text-600, #5b6472); font-size: 0.85rem; }
  .irwf-flow-step.completed .irwf-flow-icon { background: rgba(31,122,92,.1); color: #1f7a5c; }
  .irwf-flow-step.current .irwf-flow-icon { background: rgba(201,127,29,.1); color: #c97f1d; }
  .irwf-flow-meta { font-size: 0.78rem; color: var(--text-500, #6b7280); margin-top: 2px; padding-left: 36px; }
  .irwf-flow-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 0.68rem; font-weight: 700; margin-left: auto; }
  .irwf-flow-badge.badge-completed { background: rgba(31,122,92,.1); color: #1f7a5c; }
  .irwf-flow-badge.badge-current { background: rgba(201,127,29,.1); color: #c97f1d; }
  .irwf-flow-badge.badge-pending { background: rgba(107,125,158,.1); color: #6b7d9e; }
  .irwf-flow-badge.badge-skip { background: rgba(107,125,158,.06); color: var(--text-400, #8b93a1); }
  .irwf-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
  .irwf-badge.severity-critical { background: rgba(178,58,58,.1); color: #b23a3a; border: 1px solid rgba(178,58,58,.2); }
  .irwf-badge.severity-high { background: rgba(201,127,29,.1); color: #c97f1d; border: 1px solid rgba(201,127,29,.2); }
  .irwf-badge.severity-medium { background: rgba(43,122,142,.1); color: #2b7a8e; border: 1px solid rgba(43,122,142,.2); }
  .irwf-badge.severity-low { background: rgba(107,125,158,.1); color: #6b7d9e; border: 1px solid rgba(107,125,158,.2); }
  .irwf-badge.status-submitted { background: rgba(107,125,158,.1); color: #6b7d9e; }
  .irwf-badge.status-under_review { background: rgba(59,130,196,.1); color: #3b82c4; }
  .irwf-badge.status-investigation { background: rgba(107,79,158,.1); color: #6b4f9e; }
  .irwf-badge.status-escalated { background: rgba(201,127,29,.1); color: #c97f1d; }
  .irwf-badge.status-resolved { background: rgba(31,122,92,.1); color: #1f7a5c; }
  .irwf-badge.status-closed { background: rgba(31,122,92,.15); color: #145a42; }

  .irwf-decision { border-left: 3px solid #c97f1d; padding-left: 14px; margin: 8px 0; }
  .irwf-decision-title { font-size: 0.78rem; font-weight: 700; color: #c97f1d; text-transform: uppercase; letter-spacing: .04em; }
  .irwf-decision-options { display: flex; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
  .irwf-decision-opt { font-size: 0.78rem; color: var(--text-700, #3b4252); background: rgba(13,27,46,.02); border: 1px solid var(--border, #e4e8ee); padding: 4px 10px; border-radius: 6px; }
  .irwf-decision-opt.active { border-color: #1f7a5c; color: #1f7a5c; background: rgba(31,122,92,.04); }
  .irwf-section-title { font-size: 0.92rem; font-weight: 700; color: var(--text-900, #1b2430); display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }

  .irwf-evidence-list { display: flex; flex-direction: column; gap: 10px; }
  .irwf-evidence-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; border: 1px solid var(--hairline, #dde3ea); border-radius: 10px; background: rgba(13,27,46,.015); }
  .irwf-evidence-icon { width: 36px; height: 36px; border-radius: 8px; background: rgba(13,27,46,.04); display: inline-flex; align-items: center; justify-content: center; color: var(--text-600, #5b6472); font-size: 1.1rem; flex-shrink: 0; }
  .irwf-evidence-details { flex: 1 1 auto; min-width: 0; }
  .irwf-evidence-name { font-weight: 700; color: var(--text-900, #1b2430); font-size: 0.9rem; word-break: break-word; }
  .irwf-evidence-name a { color: var(--link-color, #2563eb); text-decoration: none; }
  .irwf-evidence-name a:hover { text-decoration: underline; }
  .irwf-evidence-desc { font-size: 0.82rem; color: var(--text-700, #3b4252); margin-top: 4px; }
  .irwf-evidence-meta { font-size: 0.72rem; color: var(--text-500, #6b7280); margin-top: 4px; }
  .irwf-evidence-empty, .irwf-evidence-loading { text-align: center; padding: 28px 0; color: var(--text-500, #6b7280); }
  .irwf-evidence-empty i, .irwf-evidence-loading i { font-size: 32px; color: var(--hairline, #dde3ea); display: block; margin-bottom: 8px; }
  .irwf-evidence-empty { color: var(--text-500, #6b7280); }
  .irwf-textarea { width: 100%; border: 1px solid var(--hairline, #dde3ea); border-radius: 10px; padding: 12px; font-family: inherit; font-size: 0.9rem; resize: vertical; min-height: 140px; }
  .irwf-textarea:focus { outline: none; border-color: var(--focus-ring, #b6c3d6); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }

  .cc-btn { font-size: 0.76rem; font-weight: 600; padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border, #e4e8ee); background: var(--card-bg, #fff); color: var(--text-600, #5b6472); cursor: pointer; text-decoration: none; white-space: nowrap; transition: background 150ms ease, border-color 150ms ease, color 150ms ease; display: inline-flex; align-items: center; gap: 6px; }
  .cc-btn:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); }
  .cc-btn.primary { background: var(--info-blue, #3b82c4); color: #fff; border-color: var(--info-blue, #3b82c4); }
  .cc-btn.primary:hover { background: #1c5a8a; border-color: #1c5a8a; color: #fff; }
  .cc-btn.danger { background: #fff; color: #a3272a; border-color: #f5c6cb; }
  .cc-btn.danger:hover { background: #fff5f5; border-color: #a3272a; }
  .irwf-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
  .irwf-action-status { font-size: 0.78rem; font-weight: 600; margin-left: 8px; }
  .irwf-action-status.success { color: #1f7a5c; }
  .irwf-action-status.error { color: #a3272a; }
</style>
<?php ob_end_flush(); ?>

