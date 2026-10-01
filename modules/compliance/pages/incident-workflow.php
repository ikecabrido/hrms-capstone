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

$database = new Database();

if ($database->hasConnectionError()) {
    throw new RuntimeException('Database connection unavailable.');
}

$db = $database->getConnection();

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

$category = strtolower($incident['incident_type'] ?? '');
$isHazardOrAccident = str_contains($category, 'accident') || str_contains($category, 'environmental') || str_contains($category, 'safety hazard') || str_contains($category, 'exposure') || str_contains($category, 'near miss');

$currentStatus = strtolower($incident['status'] ?? 'submitted');

$workflowSteps = [
    ['key' => 'incident_occurs',          'label' => 'Incident Occurs'],
    ['key' => 'legal_receives',           'label' => 'Legal & Compliance Receives'],
    ['key' => 'review_classify',          'label' => 'Review & Classification'],
    ['key' => 'investigation',            'label' => 'Investigation Conducted'],
];

if ($isHazardOrAccident) {
    $workflowSteps[] = ['key' => 'hazard_check',             'label' => 'Hazard Found?'];
    $workflowSteps[] = ['key' => 'hazard_remediated_check',  'label' => 'Has the hazard been remediated?'];
    $workflowSteps[] = ['key' => 'corrective_action',        'label' => 'Corrective Action / Fix'];
    $workflowSteps[] = ['key' => 'close_archive',            'label' => 'Close & Archive'];
} else {
    $workflowSteps[] = ['key' => 'compliance_verify',        'label' => 'Compliance Verification'];
    $workflowSteps[] = ['key' => 'close_archive',            'label' => 'Close & Archive'];
}

$currentStepIndex = 0;
$statusStepMap = [
    'submitted'     => 'legal_receives',
    'under_review'  => 'review_classify',
    'investigation' => 'investigation',
    'escalated'     => $isHazardOrAccident ? 'hazard_check' : 'compliance_verify',
    'resolved'      => $isHazardOrAccident ? 'hazard_remediated_check' : 'compliance_verify',
    'hazard_open'   => 'corrective_action',
    'closed'        => 'close_archive',
];

$targetStep = $statusStepMap[$currentStatus] ?? 'legal_receives';
foreach ($workflowSteps as $idx => $step) {
    if ($step['key'] === $targetStep) {
        $currentStepIndex = $idx;
        break;
    }
}

$sevClass = ir_severity_class($incident['severity']);
$statusClass = ir_status_class($incident['status']);
?>

<!-- Evidence Modal -->
<div id="irwfEvidenceModal" class="lc-modal-backdrop" onclick="if(event.target===this)irwfCloseModal('irwfEvidenceModal')">
  <div class="lc-modal" style="max-width:640px;">
    <div class="lc-modal-header">
      <div class="lc-modal-title">Evidence</div>
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
      <div class="lc-modal-title">Add Witness Statement</div>
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

<!-- Confirm Modal -->
<div id="irwfConfirmModal" class="lc-modal-backdrop irwf-confirm-backdrop" onclick="if(event.target===this)irwfConfirmModal(false)">
  <div class="lc-modal irwf-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="irwfConfirmTitle" aria-describedby="irwfConfirmDesc">
    <div class="irwf-confirm-body">
      <p id="irwfConfirmTitle" class="irwf-confirm-title"></p>
      <p id="irwfConfirmDesc" class="irwf-confirm-desc"></p>
      <p id="irwfConfirmTransition" class="irwf-confirm-transition"></p>
      <div class="irwf-confirm-actions">
        <button type="button" class="cc-btn irwf-confirm-cancel" onclick="irwfConfirmModal(false)">Cancel</button>
        <button type="button" class="cc-btn primary irwf-confirm-submit" onclick="irwfConfirmModal(true)">Confirm</button>
      </div>
    </div>
  </div>
</div>

<section class="irwf-page">
  <div class="irwf-dashboard">
    <div class="irwf-main">
    <div class="irwf-card">
      <h3>Incident Information</h3>
     <div class="irwf-grid">
       <div class="irwf-field">
         <div class="irwf-label">Incident Number</div>
         <div class="irwf-value mono"><?= htmlspecialchars($incident['incident_id'], ENT_QUOTES) ?></div>
       </div>
       <div class="irwf-field">
         <div class="irwf-label">Category</div>
         <div class="irwf-value"><?= htmlspecialchars($incident['incident_type'] ?? 'Other', ENT_QUOTES) ?></div>
       </div>
       <div class="irwf-field">
         <div class="irwf-label">Type</div>
         <div class="irwf-value"><?= htmlspecialchars($incident['type'] ? str_replace('_', ' ', $incident['type']) : 'Other', ENT_QUOTES) ?></div>
       </div>
       <div class="irwf-field">
         <div class="irwf-label">Severity</div>
         <div class="irwf-value"><?= htmlspecialchars(ucfirst($incident['severity']), ENT_QUOTES) ?></div>
       </div>
       <div class="irwf-field">
         <div class="irwf-label">Current Status</div>
         <div class="irwf-value"><?= htmlspecialchars(ir_status_label($incident['status']), ENT_QUOTES) ?></div>
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
         <div class="irwf-value" id="irwfAssignedOfficerName"><?= htmlspecialchars($incident['assigned_name'] ?? 'Unassigned', ENT_QUOTES) ?></div>
       </div>
       <div class="irwf-field full">
         <div class="irwf-label">Description</div>
         <div class="irwf-value"><?= nl2br(htmlspecialchars($incident['description'] ?? '', ENT_QUOTES)) ?></div>
       </div>
     </div>
    </div>

    <div class="irwf-card">
     <h3>Incident Reporting Workflow</h3>
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
         <div class="irwf-flow-step <?= $stepClass ?>" data-step-index="<?= $idx ?>" data-step-key="<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>">
           <div class="irwf-flow-dot"></div>
           <div class="irwf-flow-body">
             <div class="irwf-flow-title">
               <?= htmlspecialchars($step['label'], ENT_QUOTES) ?>
               <span class="irwf-flow-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
             </div>
             <?php if ($step['key'] === 'hazard_check'): ?>
               <div class="irwf-decision">
                 <div class="irwf-decision-title">Decision Point</div>
                 <div class="irwf-decision-options">
                   <span class="irwf-decision-opt <?= $currentStatus === 'closed' || $currentStatus === 'resolved' || $currentStatus === 'hazard_open' ? 'active' : '' ?>">No → Case Closed</span>
                   <span class="irwf-decision-opt <?= in_array($currentStatus, ['escalated', 'investigation', 'under_review', 'submitted'], true) ? '' : 'active' ?>">Yes → Check Remediation</span>
                 </div>
               </div>
             <?php elseif ($step['key'] === 'hazard_remediated_check'): ?>
               <div class="irwf-decision">
                 <div class="irwf-decision-title">Decision Point</div>
                 <div class="irwf-decision-options">
                   <span class="irwf-decision-opt <?= $currentStatus === 'hazard_open' ? '' : 'active' ?>">Not Remediated — File Fix</span>
                   <span class="irwf-decision-opt <?= $currentStatus === 'closed' ? 'active' : '' ?>">Remediated — Close Case</span>
                 </div>
               </div>
             <?php endif; ?>
               <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['updated_at'])) ?></div>
               <div class="irwf-step-actions" data-step-index="<?= $idx ?>" data-step-key="<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>">
                  <button type="button" class="irwf-step-actions-toggle" aria-expanded="<?= $idx === $currentStepIndex || $stepClass === 'completed' ? 'true' : 'false' ?>">
                    <span class="irwf-step-actions-toggle-text">Actions</span>
                    <span class="irwf-step-actions-toggle-icon" aria-hidden="true">▸</span>
                  </button>
                  <div class="irwf-step-actions-panel" <?= $idx === $currentStepIndex || $stepClass === 'completed' ? '' : 'hidden' ?>>
                   <?php if ($idx === $currentStepIndex): ?>
                     <?php if ($step['key'] === 'hazard_check'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('hazard_yes', this)">Yes — Hazard Found</button>
                       <button class="cc-btn" onclick="irwfSubmitAction('hazard_no', this)">No — No Hazard</button>
                     <?php elseif ($step['key'] === 'hazard_remediated_check'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('remediated_yes', this)">Yes — Remediated / Close</button>
                       <button class="cc-btn" onclick="irwfSubmitAction('remediated_no', this)">No — Not Remediated / File Fix</button>
                     <?php elseif ($currentStatus === 'submitted'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">Accept for Review</button>
                       <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                     <?php elseif ($currentStatus === 'under_review'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">Start Investigation</button>
                       <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                     <?php elseif ($currentStatus === 'investigation'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">Escalate to Corrective Action</button>
                       <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                     <?php elseif ($currentStatus === 'escalated'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">Verify Completion</button>
                       <button class="cc-btn" onclick="irwfSubmitAction('reopen', this)">Reopen Investigation</button>
                       <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                     <?php elseif ($currentStatus === 'resolved'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                       <button class="cc-btn" onclick="irwfSubmitAction('reopen', this)">Reopen Investigation</button>
                     <?php elseif ($currentStatus === 'hazard_open'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('advance', this)">Complete Corrective Action</button>
                       <button class="cc-btn danger" onclick="irwfSubmitAction('close', this)">Close Incident</button>
                     <?php elseif ($currentStatus === 'closed'): ?>
                       <button class="cc-btn primary" onclick="irwfSubmitAction('reopen', this)">Reopen Case</button>
                     <?php endif; ?>
                     <span class="irwf-action-status" data-action-status-for="<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>"></span>
                   <?php else: ?>
                     <span class="irwf-step-actions-empty">No actions for this step.</span>
                   <?php endif; ?>
                 </div>
               </div>
             </div>
         </div>
         <?php endforeach; ?>
       </div>
      </div>
    </div>

    <div class="irwf-sidebar">
     <div class="irwf-card">
     <h3>Process Summary</h3>
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
        <div class="irwf-value"><?= htmlspecialchars(ucfirst($incident['severity']), ENT_QUOTES) ?></div>
      </div>
      <div class="irwf-field">
        <div class="irwf-label">Report Source</div>
        <div class="irwf-value"><?= htmlspecialchars($incident['reporter_department'] ?? '—', ENT_QUOTES) ?></div>
      </div>
    </div>
  </div>

  <div class="irwf-card">
    <h3>Key Milestones</h3>
    <div class="irwf-flow">
      <div class="irwf-flow-step completed">
        <div class="irwf-flow-dot"></div>
        <div class="irwf-flow-body">
          <div class="irwf-flow-title">
            Incident Reported
          </div>
          <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['created_at'])) ?></div>
        </div>
      </div>
      <div class="irwf-flow-step completed">
        <div class="irwf-flow-dot"></div>
        <div class="irwf-flow-body">
          <div class="irwf-flow-title">
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
            Closure Date
          </div>
          <div class="irwf-flow-meta"><?= date('M d, Y g:i A', strtotime($incident['updated_at'])) ?></div>
        </div>
      </div>
       <?php endif; ?>
     </div>
   </div>
  </div>
 </div>
</section>

<script>
window.IRWF_CONFIG = {
    incidentId: <?= (int)$incident['id'] ?>,
    currentStepKey: <?= json_encode($targetStep) ?>,
    isHazardOrAccident: <?= $isHazardOrAccident ? 'true' : 'false' ?>,
    reporterName: <?= json_encode($incident['reporter_name'] ?? '') ?>,
    reporterEmail: <?= json_encode($reporterEmail) ?>,
    reporterEmployeeNo: <?= json_encode($reporterEmployeeNo) ?>,
    incidentIdNumber: <?= json_encode($incident['incident_id']) ?>,
    incidentType: <?= json_encode($incident['incident_type'] ?? '') ?>,
    incidentDate: <?= json_encode($incident['incident_date'] ?? '') ?>,
    incidentTime: <?= json_encode($incident['incident_time'] ?? '') ?>,
    location: <?= json_encode($incident['location'] ?? '') ?>,
    severity: <?= json_encode($incident['severity'] ?? '') ?>
};
</script>
<script src="js/pages/incident-workflow-interactive.js?v=<?= time() ?>"></script>

<style>
  .irwf-page { padding: 0; font-family: Arial, serif; }
  .irwf-dashboard { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
  .irwf-main { min-width: 0; }
  .irwf-sidebar { width: 320px; flex-shrink: 0; }
  .irwf-card { background: #fff; border: 1px solid #e4e8ee; border-radius: 0; padding: 16px; margin-bottom: 16px; }
  .irwf-card h3 { margin: 0 0 12px; font-size: 0.95rem; font-weight: 400; color: #1b2430; }
  .irwf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
  .irwf-field { border: 1px solid #e4e8ee; padding: 10px 12px; }
  .irwf-field.full { grid-column: 1 / -1; }
  .irwf-label { font-size: 0.7rem; color: #6b7280; margin-bottom: 2px; }
  .irwf-value { font-size: 0.85rem; color: #1b2430; word-break: break-word; font-weight: 400; }
  .irwf-value.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

  .irwf-flow { display: flex; flex-direction: column; gap: 0; position: relative; padding-left: 24px; }
  .irwf-flow-step { position: relative; padding-bottom: 20px; }
  .irwf-flow-step:last-child { padding-bottom: 0; }
  .irwf-flow-step::before { content: ''; position: absolute; left: -18px; top: 0; bottom: 0; width: 1px; background: #dde3ea; }
  .irwf-flow-step:last-child::before { bottom: 50%; }
  .irwf-flow-step.completed::before { background: #1f7a5c; }
  .irwf-flow-step.completed:not(:last-child)::before { background: #1f7a5c; }
  .irwf-flow-step.current::before { background: repeating-linear-gradient(180deg, #c97f1d 0 6px, #dde3ea 6px 12px); }
  .irwf-flow-dot { position: absolute; left: -22px; top: 2px; width: 10px; height: 10px; border-radius: 50%; background: #dde3ea; border: 1px solid #fff; box-shadow: 0 0 0 1px #dde3ea; z-index: 1; }
  .irwf-flow-step.completed .irwf-flow-dot { background: #1f7a5c; box-shadow: 0 0 0 1px #1f7a5c; }
  .irwf-flow-step.current .irwf-flow-dot { background: #c97f1d; box-shadow: 0 0 0 1px #c97f1d, 0 0 0 4px rgba(201,127,29,.15); }
  .irwf-flow-body { padding-left: 6px; }
  .irwf-flow-title { font-size: 0.85rem; color: #1b2430; display: flex; align-items: center; gap: 8px; font-weight: 400; }
  .irwf-flow-meta { font-size: 0.75rem; color: #6b7280; margin-top: 2px; padding-left: 0; }
  .irwf-flow-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 0; font-size: 0.65rem; font-weight: 400; margin-left: auto; }
  .irwf-flow-badge.badge-completed { background: rgba(31,122,92,.08); color: #1f7a5c; }
  .irwf-flow-badge.badge-current { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-flow-badge.badge-pending { background: rgba(107,125,158,.08); color: #6b7d9e; }
  .irwf-flow-badge.badge-skip { background: transparent; color: #8b93a1; }
  .irwf-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 0; font-size: 0.7rem; font-weight: 400; }
  .irwf-badge.severity-critical { background: rgba(178,58,58,.08); color: #b23a3a; }
  .irwf-badge.severity-high { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-badge.severity-medium { background: rgba(43,122,142,.08); color: #2b7a8e; }
  .irwf-badge.severity-low { background: rgba(107,125,158,.08); color: #6b7d9e; }
  .irwf-badge.status-submitted { background: rgba(107,125,158,.08); color: #6b7d9e; }
  .irwf-badge.status-under_review { background: rgba(59,130,196,.08); color: #3b82c4; }
  .irwf-badge.status-investigation { background: rgba(107,79,158,.08); color: #6b4f9e; }
  .irwf-badge.status-escalated { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-badge.status-resolved { background: rgba(31,122,92,.08); color: #1f7a5c; }
  .irwf-badge.status-closed { background: rgba(31,122,92,.1); color: #145a42; }

  .irwf-decision { border-left: 1px solid #c97f1d; padding-left: 12px; margin: 6px 0; }
  .irwf-decision-title { font-size: 0.75rem; color: #c97f1d; font-weight: 400; }
  .irwf-decision-options { display: flex; gap: 8px; margin-top: 4px; flex-wrap: wrap; }
  .irwf-decision-opt { font-size: 0.75rem; color: #6b7280; font-weight: 400; }
  .irwf-decision-opt.active { color: #1f7a5c; font-weight: 600; }
  .irwf-section-title { font-size: 0.9rem; color: #1b2430; margin-bottom: 12px; font-weight: 400; }

  .irwf-evidence-list { display: flex; flex-direction: column; gap: 8px; }
  .irwf-evidence-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid #dde3ea; border-radius: 0; background: transparent; }
  .irwf-evidence-icon { display: none; }
  .irwf-evidence-details { flex: 1 1 auto; min-width: 0; }
  .irwf-evidence-name { color: #1b2430; font-size: 0.85rem; word-break: break-word; font-weight: 400; }
  .irwf-evidence-name a { color: #2563eb; text-decoration: none; }
  .irwf-evidence-name a:hover { text-decoration: underline; }
  .irwf-evidence-desc { font-size: 0.8rem; color: #3b4252; margin-top: 3px; }
  .irwf-evidence-meta { font-size: 0.7rem; color: #6b7280; margin-top: 3px; }
  .irwf-evidence-empty, .irwf-evidence-loading { text-align: center; padding: 20px 0; color: #6b7280; }
  .irwf-textarea { width: 100%; border: 1px solid #dde3ea; border-radius: 0; padding: 10px; font-family: inherit; font-size: 0.85rem; resize: vertical; min-height: 120px; }
  .irwf-textarea:focus { outline: none; border-color: #b6c3d6; }

  .cc-btn { font-size: 0.75rem; font-weight: 400; padding: 5px 12px; border-radius: 0; border: 1px solid #e4e8ee; background: #fff; color: #5b6472; cursor: pointer; text-decoration: none; white-space: nowrap; transition: background 150ms ease, border-color 150ms ease, color 150ms ease; display: inline-flex; align-items: center; gap: 6px; }
  .cc-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .cc-btn:hover { background: #f3f5f9; border-color: #d3d9e2; }
  .cc-btn.primary { background: #3b82c4; color: #fff; border-color: #3b82c4; }
  .cc-btn.primary:hover { background: #1c5a8a; border-color: #1c5a8a; color: #fff; }
  .cc-btn.danger { background: #fff; color: #a3272a; border-color: #f5c6cb; }
  .cc-btn.danger:hover { background: #fff5f5; border-color: #a3272a; }
  .irwf-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
  .irwf-action-status { font-size: 0.75rem; margin-left: 6px; font-weight: 400; }
  .irwf-action-status.success { color: #1f7a5c; }
  .irwf-action-status.error { color: #a3272a; }

  .irwf-step-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 320px;
    max-width: calc(100vw - 24px);
    background: #ffffff;
    border: 1px solid #e4e8ee;
    border-radius: 6px;
    padding: 12px 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    font-family: Arial, sans-serif;
    z-index: 1050;
    opacity: 0;
    transform: translateX(18px);
    transition: opacity 250ms ease, transform 250ms ease;
    pointer-events: none;
  }
  .irwf-step-notification.is-visible {
    opacity: 1;
    transform: translateX(0);
    pointer-events: auto;
  }
  .irwf-step-notification-title {
    font-size: 13px;
    font-weight: 700;
    color: #1b2430;
    margin: 0 0 4px;
    padding-left: 14px;
    position: relative;
  }
  .irwf-step-notification-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 3px;
    bottom: 3px;
    width: 3px;
    border-radius: 2px;
    background: #3b82c4;
  }
  .irwf-step-notification-message {
    font-size: 12px;
    color: #4b5563;
    margin: 0;
    line-height: 1.4;
  }
  @media (max-width: 480px) {
    .irwf-step-notification {
      left: 12px;
      right: 12px;
      width: auto;
      top: 12px;
    }
  }
   @media (prefers-reduced-motion: reduce) {
     .irwf-step-notification {
       transform: none;
       transition: opacity 200ms ease;
     }
     .irwf-step-notification.is-visible {
       transform: none;
     }
   }
   @media (max-width: 1024px) {
     .irwf-dashboard { grid-template-columns: 1fr; }
     .irwf-sidebar { width: auto; }
   }

   .irwf-step-actions {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e4e8ee;
  }
  .irwf-step-actions-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: none;
    padding: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: #3b82c4;
    cursor: pointer;
    font-family: inherit;
  }
  .irwf-step-actions-toggle:hover {
    text-decoration: underline;
  }
  .irwf-step-actions-toggle[aria-expanded="true"] .irwf-step-actions-toggle-icon {
    display: inline-block;
    transform: rotate(90deg);
  }
  .irwf-step-actions-toggle-icon {
    display: inline-block;
    font-size: 0.65rem;
    transition: transform 150ms ease;
  }
  .irwf-step-actions-panel {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }
  .irwf-step-actions-panel[hidden] {
    display: none;
  }
  .irwf-flow-step:not(.current) .irwf-decision {
    display: none;
  }
   .irwf-step-actions-empty {
      font-size: 0.75rem;
      color: #6b7280;
    }

  html {
    scroll-behavior: smooth;
  }
  .irwf-flow-step.current {
    scroll-margin-top: 24px;
    scroll-margin-bottom: 24px;
  }
  .irwf-flow-step.current .irwf-flow-dot {
    outline: none;
  }
  @media (prefers-reduced-motion: reduce) {
    html {
      scroll-behavior: auto;
    }
  }

  .irwf-flow-step.completed .irwf-flow-body,
  .irwf-flow-step.pending .irwf-flow-body {
    opacity: 0.85;
  }
  .irwf-flow-step.completed .irwf-flow-title,
  .irwf-flow-step.pending .irwf-flow-title {
    color: #6b7280;
  }
  .irwf-flow-step.completed .irwf-decision,
  .irwf-flow-step.pending .irwf-decision {
    opacity: 0.75;
  }
  .irwf-flow-step.completed .irwf-decision-opt,
  .irwf-flow-step.pending .irwf-decision-opt {
    cursor: default;
  }

  .irwf-evidence-upload {
    margin-bottom: 10px;
  }
  .irwf-evidence-preview {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 6px;
  }
  .irwf-evidence-item {
    font-size: 0.8rem;
    color: #3b4252;
    padding: 4px 8px;
    border: 1px solid #e4e8ee;
    background: #fff;
  }
  .irwf-step-summary {
    border: 1px solid #1f7a5c;
    padding: 14px;
    background: rgba(31,122,92,.04);
  }
  .irwf-summary-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f7a5c;
    margin-bottom: 10px;
  }
  .irwf-summary-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 10px;
  }
  .irwf-summary-item {
    font-size: 0.8rem;
    color: #1b2430;
  }
  .irwf-summary-check {
    color: #1f7a5c;
    margin-right: 6px;
  }
  .irwf-summary-missing {
    font-size: 0.8rem;
    color: #a3272a;
    padding: 8px;
    border: 1px solid #f5c6cb;
    background: #fff5f5;
  }

  .irwf-confirm-modal {
    max-width: 220px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.10), 0 0 1px rgba(0, 0, 0, 0.06);
    margin-left: calc(var(--sidebar-width, 252px) / 2 + 60px);
    margin-right: 80px;
    opacity: 0;
    transform: translateY(6px) scale(0.98);
    transition: opacity 180ms ease, transform 180ms ease;
  }

  .lc-modal-backdrop.open .irwf-confirm-modal {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

  .irwf-confirm-body {
    padding: 22px 14px 16px;
    text-align: center;
  }

  .irwf-confirm-title {
    margin: 0;
    font-family: Arial, serif;
    font-size: 1.05rem;
    font-weight: 600;
    line-height: 1.35;
    color: #1f2937;
    text-align: center;
  }

  .irwf-confirm-desc {
    margin: 8px 0 0;
    font-family: Arial, serif;
    font-size: 0.94rem;
    line-height: 1.45;
    color: #6b7280;
    text-align: center;
  }

  .irwf-confirm-transition {
    margin: 12px 0 0;
    font-size: 0.90rem;
    color: #6b7280;
    text-align: center;
    display: none;
  }

  .irwf-confirm-transition .irwf-confirm-status-current {
    font-weight: 500;
    color: #6b7280;
  }

  .irwf-confirm-transition .irwf-confirm-status-arrow {
    margin: 0 4px;
    color: #9ca3af;
  }

  .irwf-confirm-transition .irwf-confirm-status-next {
    font-weight: 500;
    color: #3b82c4;
  }

  .irwf-confirm-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 14px;
  }

  .irwf-confirm-actions .cc-btn {
    min-width: auto;
    padding: 7px 14px;
    font-size: 0.84rem;
    transition: all 150ms ease;
  }

  .irwf-confirm-submit {
    position: relative;
  }

  .irwf-confirm-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }

  .irwf-confirm-submit::after {
    content: '';
    display: none;
    width: 10px;
    height: 10px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: irwf-spin 0.6s linear infinite;
    margin-left: 5px;
    vertical-align: middle;
  }

  .irwf-confirm-submit.is-loading::after {
    display: inline-block;
  }

  .irwf-confirm-submit.is-success {
    background: #1f7a5c !important;
    border-color: #1f7a5c !important;
    color: #fff !important;
  }

  .irwf-confirm-submit.is-error {
    background: #fff !important;
    border-color: #a3272a !important;
    color: #a3272a !important;
  }

  .irwf-confirm-cancel:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  @keyframes irwf-spin {
    to { transform: rotate(360deg); }
  }

  @media (prefers-reduced-motion: reduce) {
    .irwf-confirm-modal {
      transition: none;
    }
    .irwf-confirm-submit::after {
      animation: none;
    }
  }
</style>
<?php ob_end_flush(); ?>

