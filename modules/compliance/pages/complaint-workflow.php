<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';

$pageTitle   = 'Complaint Workflow';
$activeGroup = 'Incident Reporting';
$activePage  = 'case-records';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!($db ?? null) instanceof PDO) {
    $db = (new Database())->getConnection();
}
/** @var PDO $db */

try {
    $cols = $db->query("SHOW COLUMNS FROM lc_complaints WHERE Field IN ('employee_response','employee_response_date')")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff(['employee_response','employee_response_date'], $cols);
    if ($missing !== []) {
        $db->beginTransaction();
        if (in_array('employee_response', $missing, true)) {
            $db->exec("ALTER TABLE lc_complaints ADD COLUMN employee_response TEXT DEFAULT NULL");
        }
        if (in_array('employee_response_date', $missing, true)) {
            $db->exec("ALTER TABLE lc_complaints ADD COLUMN employee_response_date DATETIME DEFAULT NULL");
        }
        $db->commit();
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
}

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function ch_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function ch_row(PDO $db, string $sql): ?array {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}
function ch_q(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
function ch_label(?string $s): string {
    return htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$s)));
}
function ch_date(?string $d, string $fmt = 'M d, Y'): string {
    return !empty($d) ? date($fmt, strtotime($d)) : '';
}
function ch_employee_name(PDO $db, int $employeeId): string {
    if (empty($employeeId)) return '';
      $row = ch_row($db, "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM em_employees WHERE employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row['full_name'] ?? '';
}
function ch_employee_no(PDO $db, int $employeeId): string {
    if (empty($employeeId)) return '';
    $row = ch_row($db, "SELECT employee_code FROM em_employees WHERE employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row['employee_code'] ?? '';
}
function ch_status_class(string $status): string {
    $s = strtolower($status);
    if (in_array($s, ['closed', 'resolved', 'closed_no_violation', 'closed_warning_issued', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved'], true)) return 'cw-stamp cw-stamp-compliant';
    if (in_array($s, ['for_decision', 'pending_employee_response'], true)) return 'cw-stamp cw-stamp-pending';
    if (in_array($s, ['under_investigation', 'under_initial_review'], true)) return 'cw-stamp cw-stamp-info';
    return 'cw-stamp cw-stamp-pending';
}

$complaintTable = 'lc_complaints';

$complaintId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($complaintId <= 0) {
    header('Location: ?page=case-records&msg=error|Invalid complaint ID');
    exit;
}

$case = null;
try {
    $case = ch_row($db, "SELECT * FROM `$complaintTable` WHERE id = " . $complaintId . " LIMIT 1");
} catch (Throwable $e) {
    $case = null;
}

if (!$case) {
    header('Location: ?page=case-records&msg=error|Complaint not found');
    exit;
}

$currentStatus = strtolower($case['status'] ?? 'under_initial_review');

$workflowSteps = [
    ['key' => 'complaint_submitted',  'label' => 'Complaint Submitted',           'icon' => 'bi bi-file-earmark-text'],
    ['key' => 'legal_receives',       'label' => 'Received by Legal & Compliance', 'icon' => 'bi bi-inbox'],
    ['key' => 'assign_officer',       'label' => 'Assign Compliance Officer',     'icon' => 'bi bi-person-badge'],
    ['key' => 'initial_assessment',   'label' => 'Initial Case Assessment',       'icon' => 'bi bi-search'],
    ['key' => 'investigation',        'label' => 'Investigation Conducted',       'icon' => 'bi bi-magnifying-glass'],
    ['key' => 'evidence_collection',  'label' => 'Evidence & Statements',         'icon' => 'bi bi-paperclip'],
    ['key' => 'conference_hearing',   'label' => 'Conference / Hearing',          'icon' => 'bi bi-bank'],
    ['key' => 'findings_prepared',    'label' => 'Findings Prepared',             'icon' => 'bi bi-clipboard-check'],
    ['key' => 'nte_issued',           'label' => 'NTE Issued to Employee',        'icon' => 'bi bi-envelope'],
    ['key' => 'employee_response',    'label' => 'Employee Response Received',    'icon' => 'bi bi-reply'],
    ['key' => 'explanation_reviewed', 'label' => 'Explanation Reviewed',          'icon' => 'bi bi-question-diamond'],
    ['key' => 'decision_made',        'label' => 'Decision Made',                 'icon' => 'bi bi-gavel'],
    ['key' => 'disciplinary_action',  'label' => 'Disciplinary Action Applied',   'icon' => 'bi bi-wrench'],
    ['key' => 'case_closed',          'label' => 'Case Closed',                   'icon' => 'bi bi-archive'],
];

$statusStepMap = [
    'under_initial_review'          => 'initial_assessment',
    'under_investigation'           => 'investigation',
    'pending_employee_response'     => 'employee_response',
    'for_decision'                  => 'decision_made',
    'closed_no_violation'           => 'disciplinary_action',
    'closed_warning_issued'         => 'disciplinary_action',
    'closed_suspension'             => 'disciplinary_action',
    'closed_termination_recommended'=> 'disciplinary_action',
    'closed_resolved'               => 'disciplinary_action',
    'closed'                        => 'case_closed',
];

$targetStep = $statusStepMap[$currentStatus] ?? 'complaint_submitted';
$currentStepIndex = 0;
foreach ($workflowSteps as $idx => $step) {
    if ($step['key'] === $targetStep) {
        $currentStepIndex = $idx;
        break;
    }
}

$investigatorName = !empty($case['assigned_to']) ? ch_employee_name($db, $case['assigned_to']) : '';

$employeeName = ch_employee_name($db, $case['employee_id'] ?? 0);
$employeeNo = ch_employee_no($db, $case['employee_id'] ?? 0);

$statsTotal = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0), 0);
$statsOpen = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status NOT IN ('closed','closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved')", 0);
$statsInvestigation = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status IN ('under_initial_review','under_investigation','pending_employee_response','for_decision')", 0);
$statsDecision = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status = 'for_decision'", 0);
$statsClosed = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status IN ('closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved','closed')", 0);

$disciplinarySummary = ['nte' => 0, 'written_warning' => 0, 'final_warning' => 0, 'suspension' => 0, 'termination' => 0];
try {
    $eid = (int) ($case['employee_id'] ?? 0);
    if ($eid > 0) {
        $disciplinarySummary['nte'] = (int) ch_value($db, "SELECT COUNT(*) FROM lc_disciplinary_actions WHERE employee_id = " . $eid . " AND action_type = 'nte'", 0);
        $disciplinarySummary['written_warning'] = (int) ch_value($db, "SELECT COUNT(*) FROM lc_disciplinary_actions WHERE employee_id = " . $eid . " AND action_type = 'written_warning'", 0);
        $disciplinarySummary['final_warning'] = (int) ch_value($db, "SELECT COUNT(*) FROM lc_disciplinary_actions WHERE employee_id = " . $eid . " AND action_type = 'final_warning'", 0);
        $disciplinarySummary['suspension'] = (int) ch_value($db, "SELECT COUNT(*) FROM lc_disciplinary_actions WHERE employee_id = " . $eid . " AND action_type = 'suspension'", 0);
        $disciplinarySummary['termination'] = (int) ch_value($db, "SELECT COUNT(*) FROM lc_disciplinary_actions WHERE employee_id = " . $eid . " AND action_type IN ('termination','termination_recommended')", 0);
    }
} catch (Throwable $e) {}

$employeeIdParam = urlencode($case['employee_id'] ?? '');
$caseIdParam = (int)($case['id'] ?? 0);
$hrSignatoryParam = rawurlencode($investigatorName ?: '');

$docActionBase = '?page=complaint-document-action&complaint_id=' . $caseIdParam . '&employee_id=' . $employeeIdParam . '&hr_signatory=' . $hrSignatoryParam;

$notificationBaseUrl = '?page=notification-compose&mode=reply&notification_key=warning'
    . '&scenario=general'
    . '&employee_id=' . (int)($case['employee_id'] ?? 0)
    . '&hr_signatory=' . rawurlencode($investigatorName ?: '');

$jsNotificationUrl = json_encode($notificationBaseUrl, ENT_QUOTES);
?>
<section class="cw-module">
   <?php if (!empty($flash)): ?>
     <?php [$fc, $fm] = explode('|', $flash, 2); ?>
     <div class="cw-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

     <div class="cw-row">
      <div class="cw-col cw-col-main">
        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-diagram-3"></i> Case Information</h3>
            <span class="cw-stamp <?= ch_status_class($case['status'] ?? '') ?>"><?= ch_label($case['status'] ?? 'Under Initial Review') ?></span>
          </div>
          <div class="cw-card-body">
            <div class="cw-info-grid">
              <div class="cw-info-item">
                <label>Case Number</label>
                <div><?= htmlspecialchars('CMP-' . str_pad($case['id'], 5, '0', STR_PAD_LEFT)) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Complaint Type</label>
                <div><?= htmlspecialchars($case['type'] ?? '—') ?></div>
              </div>
              <div class="cw-info-item">
                <label>Severity</label>
                <div><?= htmlspecialchars(ucfirst($case['severity'] ?? 'medium')) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Date Filed</label>
                <div><?= ch_date($case['created_at'] ?? null, 'M d, Y g:i A') ?></div>
              </div>
              <div class="cw-info-item">
                <label>Employee</label>
                <div><?= htmlspecialchars($employeeName ?: '—') ?> <?= !empty($employeeNo) ? '<small>(' . htmlspecialchars($employeeNo) . ')</small>' : '' ?></div>
              </div>
              <div class="cw-info-item">
                <label>Assigned Officer</label>
                <div><?= htmlspecialchars($investigatorName ?: 'Unassigned') ?></div>
              </div>
            </div>
            <div class="cw-case-desc">
              <label>Description</label>
              <p><?= nl2br(htmlspecialchars($case['description'] ?? '')) ?></p>
            </div>
            <?php if (!empty($case['mitigation_plan'])): ?>
            <div class="cw-case-desc">
              <label>Mitigation Plan</label>
              <p><?= nl2br(htmlspecialchars($case['mitigation_plan'])) ?></p>
            </div>
            <?php endif; ?>

            <div class="chwf-investigator-select" style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border,#e4e8ee);">
              <label for="chwfInvestigatorSearch" style="display:block; font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px;">Assigned Investigator</label>
              <div class="chwf-investigator-search-wrap" style="display:<?= empty($case['assigned_to']) ? 'block' : 'none' ?>;">
                <input type="text" id="chwfInvestigatorSearch" name="chwf_investigator_search" class="chwf-investigator-search" placeholder="Search HR employee by name, ID, or email…" autocomplete="off" data-complaint-id="<?= (int)($case['id'] ?? 0) ?>" />
                 <div class="chwf-investigator-results" style="display:none; position:absolute; top:100%; left:0; z-index:10; width:100%; max-height:220px; overflow:auto; background:#fff; border:1px solid var(--border,#e4e8ee); border-radius:8px; box-shadow:0 8px 24px rgba(13,27,46,.12); margin-top:4px;"></div>
              </div>
              <div class="chwf-investigator-selected" style="display:<?= empty($case['assigned_to']) ? 'none' : 'flex' ?>; align-items:center; gap:10px; padding:10px; background:rgba(31,122,92,.06); border:1px solid rgba(31,122,92,.18); border-radius:8px;">
                <div style="width:28px; height:28px; border-radius:50%; background:rgba(31,122,92,.12); display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; color:#1f7a5c; flex-shrink:0;">
                  <i class="bi bi-person-check" style="font-size:0.85rem;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                   <div class="chwf-investigator-selected-name" style="font-weight:600; color:var(--text-900,#1b2430); font-size:0.82rem;"><?= htmlspecialchars($investigatorName) ?></div>
                  <div style="font-size:0.72rem; color:var(--text-500,#6b7280);">Investigator assigned</div>
                </div>
                <button type="button" class="cw-btn" style="padding:4px 8px; font-size:0.72rem;" onclick="chwfClearInvestigator(<?= (int)$case['id'] ?>)">
                  <i class="bi bi-x-circle"></i> Remove
                </button>
              </div>
              <div class="chwf-investigator-status" style="font-size:0.78rem; margin-top:6px;"></div>
            </div>
          </div>
        </div>

        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-paperclip"></i> Evidence &amp; Documents</h3>
          </div>
          <div class="cw-card-body">
            <div class="cw-evidence-loading" id="cwEvidenceLoading">Loading evidence...</div>
            <div class="cw-evidence-empty" id="cwEvidenceEmpty" style="display:none;">
              <i class="bi bi-info-circle"></i>
              <span>No evidence uploaded yet.</span>
            </div>
            <div class="cw-evidence-list" id="cwEvidenceList"></div>
            <span id="cwEvidenceStatus" class="cw-action-status"></span>
          </div>
        </div>

        <?php if (!empty($case['employee_response'])): ?>
        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-reply"></i> Employee Response</h3>
            <?php if (!empty($case['employee_response_date'])): ?>
              <span class="cw-stamp cw-stamp-info">Received <?= ch_date($case['employee_response_date'], 'M d, Y g:i A') ?></span>
            <?php endif; ?>
          </div>
          <div class="cw-card-body">
            <p style="margin:0; font-size:0.84rem; color:var(--text-700,#3b4252); line-height:1.6; white-space:pre-wrap;"><?= nl2br(htmlspecialchars($case['employee_response'])) ?></p>
          </div>
        </div>
        <?php endif; ?>

        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-list-check"></i> Workflow Actions</h3>
          </div>
          <div class="cw-card-body">
            <p style="margin:0 0 12px;font-size:0.82rem;color:var(--text-600,#5b6472);">
              Advance, reopen, or route this complaint to a decision. Status changes are recorded in the workflow history.
            </p>
            <div style="margin-bottom:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
              <?php if ($currentStatus === 'under_initial_review'): ?>
                <button class="cw-btn primary" onclick="chSubmitAction('advance', this)">
                  <i class="bi bi-check2-circle"></i> Accept for Review
                </button>
                <button class="cw-btn danger" onclick="chSubmitAction('close', this)">
                  <i class="bi bi-x-circle"></i> Close Complaint
                </button>
              <?php elseif ($currentStatus === 'under_investigation'): ?>
                <button class="cw-btn primary" onclick="chSubmitAction('advance', this)">
                  <i class="bi bi-check2-circle"></i> Complete Investigation
                </button>
                <a class="cw-btn" href="?page=preview-document&employee_id=<?= htmlspecialchars($case['employee_id'] ?? '') ?>&document_type=nte&template_code=nte&hr_signatory=<?= htmlspecialchars($investigatorName ?: '') ?>&policy_violated=<?= urlencode($case['type'] ?? '') ?>&incident_description=<?= urlencode($case['description'] ?? '') ?>" target="_blank" rel="noopener">
                  <i class="bi bi-envelope"></i> Send NTE
                </a>
                <button class="cw-btn" onclick="chSubmitAction('reopen', this)">
                  <i class="bi bi-arrow-counterclockwise"></i> Reopen Review
                </button>
                <button class="cw-btn danger" onclick="chSubmitAction('close', this)">
                  <i class="bi bi-x-circle"></i> Close Complaint
                </button>
              <?php elseif ($currentStatus === 'pending_employee_response'): ?>
                <button class="cw-btn primary" onclick="chSubmitAction('advance', this)">
                  <i class="bi bi-check2-circle"></i> Proceed to Decision
                </button>
                <button class="cw-btn" onclick="chSubmitAction('reopen', this)">
                  <i class="bi bi-arrow-counterclockwise"></i> Reopen Investigation
                </button>
                <button class="cw-btn danger" onclick="chSubmitAction('close', this)">
                  <i class="bi bi-x-circle"></i> Close Complaint
                </button>
                <button class="cw-btn" onclick="chShowResponseForm()" style="background:rgba(59,130,196,.08); border-color:rgba(59,130,196,.25); color:#1c5a8a;">
                  <i class="bi bi-reply"></i> Record Employee Response
                </button>
            <?php elseif ($currentStatus === 'for_decision'): ?>
                <div class="cw-action-grid">
                  <div class="cw-action-cell">
                    <label for="chStatusSelect" style="display:block; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-500,#6b7280); margin-bottom:6px;">Update Case Status</label>
                    <select id="chStatusSelect" class="cw-form-select">
                      <option value="">— Select Status —</option>
                      <option value="closed_no_violation">No Violation</option>
                      <option value="closed_warning_issued">Warning Issued</option>
                      <option value="closed_suspension">Suspension</option>
                      <option value="closed_termination_recommended">Termination Recommended</option>
                      <option value="closed_resolved">Resolved</option>
                    </select>
                  </div>

                  <div class="cw-action-cell">
                    <button class="cw-btn danger" id="chApplyStatusBtn" onclick="chApplyStatus()">
                      <i class="bi bi-check2-circle"></i> Apply
                    </button>
                  </div>

                  <div class="cw-action-cell">
                    <button class="cw-btn primary" onclick="chSubmitDecision('closed_no_violation', this)" style="width:100%;">
                      <i class="bi bi-x-circle"></i> Dismiss Complaint
                    </button>
                  </div>

                  <div class="cw-action-cell">
                    <button class="cw-btn" onclick="chSubmitAction('reopen', this)" style="width:100%;">
                      <i class="bi bi-arrow-counterrefresh"></i> Reopen Investigation
                    </button>
                  </div>

                  <div class="cw-action-cell cw-action-full">
                    <div id="chLetterSelectionWrap" style="display:none;">
                      <label for="chLetterSelect" style="display:block; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-500,#6b7280); margin-bottom:6px;">Select Letter Type</label>
                      <p style="margin:0 0 8px;font-size:0.78rem;color:var(--text-600,#5b6472);">
                        Case status updated. Choose a letter to send:
                      </p>
                      <select id="chLetterSelect" class="cw-form-select">
                        <option value="">— Select Letter —</option>
                        <option value="written_warning">Written Warning Letter</option>
                        <option value="suspension_notice">Suspension Letter</option>
                        <option value="termination_decision">Termination Letter</option>
                      </select>
                      <button class="cw-btn" id="chSendLetterBtn" style="width:100%; margin-top:8px;" onclick="chSendLetter()">
                        <i class="bi bi-envelope"></i> Send Letter
                      </button>
                      <span id="chLetterStatus" class="cw-action-status"></span>
                    </div>
                  </div>

                  <span id="chwfActionStatus" class="cw-action-status"></span>
                </div>
              <?php elseif (in_array($currentStatus, ['closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved'], true)): ?>
                <button class="cw-btn" onclick="chSubmitAction('reopen', this)">
                  <i class="bi bi-arrow-counterclockwise"></i> Reopen Case
                </button>
              <?php endif; ?>
              <span id="chwfActionStatus" class="cw-action-status"></span>
            </div>
          </div>
        </div>

        <div class="cw-card" id="chwfResponseCard" style="display:none;">
          <div class="cw-card-head">
            <h3><i class="bi bi-reply"></i> Record Employee Response</h3>
            <button type="button" class="cw-btn cw-btn--sm" onclick="chHideResponseForm()">
              <i class="bi bi-x"></i> Cancel
            </button>
          </div>
          <div class="cw-card-body">
            <p style="margin:0 0 12px;font-size:0.82rem;color:var(--text-600,#5b6472);">
              Record the employee's written explanation or response to the NTE. This will be attached to the case record and the status will remain as <strong>Pending Employee Response</strong> until you advance it.
            </p>
            <textarea id="chwfResponseText" rows="6" placeholder="Enter the employee's response here..." style="width:100%; box-sizing:border-box; padding:10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.84rem; color:var(--text-900,#1b2430); resize:vertical;"></textarea>
            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
              <button type="button" class="cw-btn" onclick="chHideResponseForm()">Cancel</button>
              <button type="button" class="cw-btn primary" onclick="chSubmitResponse()">
                <i class="bi bi-check2-circle"></i> Save Response
              </button>
            </div>
            <span id="chwfResponseStatus" class="cw-action-status" style="margin-left:8px;"></span>
          </div>
        </div>

        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-journal-text"></i> Decision History</h3>
            <button class="cw-btn cw-btn--sm" id="cwReloadHistoryBtn" title="Refresh">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
          <div class="cw-card-body">
            <div id="cwDecisionHistoryList">
              <div class="cw-evidence-loading"><i class="bi bi-hourglass-split"></i> Loading…</div>
            </div>
          </div>
        </div>

        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-clock-history"></i> Workflow Tracker</h3>
          </div>
          <div class="cw-card-body">
            <div class="cw-table-wrap">
              <table class="cw-table">
                <thead>
                  <tr>
                    <th>Step</th>
                    <th>Status</th>
                    <th>Updated</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($workflowSteps as $idx => $step):
                    $isCompleted = $idx < $currentStepIndex;
                    $isCurrent = $idx === $currentStepIndex;
                    $stampCls = $isCurrent ? 'pending' : ($isCompleted ? 'compliant' : 'info');
                    $stampLabel = $isCurrent ? 'Current' : ($isCompleted ? 'Completed' : 'Pending');
                  ?>
                  <tr>
                    <td>
                      <div style="display:flex; align-items:center; gap:8px;">
                        <span class="cw-step-icon"><i class="<?= $step['icon'] ?>"></i></span>
                        <?= htmlspecialchars($step['label'], ENT_QUOTES) ?>
                      </div>
                    </td>
                    <td><span class="cw-stamp cw-stamp-<?= $stampCls ?>"><?= $stampLabel ?></span></td>
                    <td><?= $isCompleted || $isCurrent ? ch_date($case['updated_at'] ?? null, 'M d, Y g:i A') : '—' ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="cw-col cw-col-side">
        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-person-badge"></i> Employee Profile</h3>
          </div>
          <div class="cw-card-body">
            <div class="cw-profile">
              <div class="cw-profile-avatar"><?= htmlspecialchars(strtoupper(substr($employeeName, 0, 2))) ?></div>
              <div class="cw-profile-name"><?= htmlspecialchars($employeeName ?: 'Unknown Employee') ?></div>
              <div class="cw-profile-no"><?= htmlspecialchars($employeeNo ?: 'EMP-???') ?></div>
            </div>
            <div class="cw-profile-stats">
              <div class="cw-profile-stat">
                <div class="cw-profile-stat-value"><?= number_format($statsTotal) ?></div>
                <div class="cw-profile-stat-label">Total Complaints</div>
              </div>
              <div class="cw-profile-stat">
                <div class="cw-profile-stat-value"><?= number_format($statsOpen) ?></div>
                <div class="cw-profile-stat-label">Open</div>
              </div>
              <div class="cw-profile-stat">
                <div class="cw-profile-stat-value"><?= number_format($statsClosed) ?></div>
                <div class="cw-profile-stat-label">Closed</div>
              </div>
            </div>
            <div class="cw-discipline-section">
              <div class="cw-discipline-title">Disciplinary Summary</div>
              <div class="cw-discipline-list">
                <div class="cw-discipline-item">
                  <span>Notice to Explain</span>
                  <span class="cw-discipline-count"><?= number_format($disciplinarySummary['nte']) ?></span>
                </div>
                <div class="cw-discipline-item">
                  <span>Written Warning</span>
                  <span class="cw-discipline-count"><?= number_format($disciplinarySummary['written_warning']) ?></span>
                </div>
                <div class="cw-discipline-item">
                  <span>Final Written Warning</span>
                  <span class="cw-discipline-count"><?= number_format($disciplinarySummary['final_warning']) ?></span>
                </div>
                <div class="cw-discipline-item">
                  <span>Suspension</span>
                  <span class="cw-discipline-count"><?= number_format($disciplinarySummary['suspension']) ?></span>
                </div>
                <div class="cw-discipline-item">
                  <span>Termination</span>
                  <span class="cw-discipline-count"><?= number_format($disciplinarySummary['termination']) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="cw-card">
          <div class="cw-card-head">
            <h3><i class="bi bi-shield-exclamation"></i> Progressive Discipline Matrix</h3>
          </div>
          <div class="cw-card-body">
            <table class="cw-matrix-table">
              <thead>
                <tr>
                  <th>Previous Record</th>
                  <th>Suggested Action</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>First Offense</td><td>Notice to Explain</td></tr>
                <tr><td>Minor First Offense</td><td>Written Warning</td></tr>
                <tr><td>Second Similar Offense</td><td>Final Written Warning</td></tr>
                <tr><td>Third Similar Offense</td><td>Suspension</td></tr>
                <tr><td>Repeated Serious Offense</td><td>Final Decision</td></tr>
                <tr><td>Gross Misconduct</td><td>Immediate Formal Investigation</td></tr>
              </tbody>
            </table>
            <p class="cw-matrix-note">The matrix is a guide. The Compliance Officer may override the recommendation based on facts, school policies, and applicable labor laws.</p>
          </div>
        </div>
      </div>
    </div>
</section>

<style>
.cw-module { padding: 4px 2px 24px; }

.cw-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.cw-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:160px; transition:all .15s ease; }
.cw-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.cw-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.cw-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.cw-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.cw-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.cw-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
.cw-summary-value { font-size:1.5rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.cw-summary-label { font-size:0.8rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

.cw-row { display:grid; grid-template-columns:1fr 360px; gap:16px; align-items:start; }
.cw-col-main { min-width:0; }
.cw-col-side { width:360px; flex-shrink:0; }
@media (max-width: 1100px) {
  .cw-row { grid-template-columns:1fr; }
  .cw-col-side { position:static; width:auto; }
}

.cw-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.cw-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.cw-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.cw-card-body { display:flex; flex-direction:column; }
.cw-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.cw-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px; }
.cw-info-item label { display:block; font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.cw-info-item div { font-size:0.84rem; font-weight:600; color:var(--text-900,#1b2430); }
.cw-case-desc { margin-top:12px; }
.cw-case-desc label { display:block; font-size:0.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
.cw-case-desc p { margin:0; font-size:0.84rem; color:var(--text-700,#3b4252); line-height:1.5; white-space:pre-wrap; }

.cw-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.cw-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.cw-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.cw-stamp-info { background:rgba(59,130,196,.12); color:#1c5a8a; }

.cw-status-flow { display:flex; flex-wrap:wrap; align-items:center; gap:0; padding:8px 0; }
.cw-status-step { display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; background:#fafbfc; border:1px solid var(--border,#e4e8ee); font-size:.76rem; font-weight:600; color:var(--text-900); white-space:nowrap; }
.cw-status-step--done { background:rgba(47,158,110,.08); border-color:rgba(47,158,110,.25); color:#1f7a52; }
.cw-status-dot { width:8px; height:8px; border-radius:50%; background:var(--text-400,#8b93a1); }
.cw-status-step--done .cw-status-dot { background:#1f7a52; }
.cw-status-arrow { margin:0 6px; color:var(--text-400,#8b93a1); font-size:.75rem; }

.cw-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-700,#3b4252); font-size:0.78rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:all .15s ease; text-decoration:none; }
.cw-btn:hover { border-color:var(--info-blue,#3b82c4); color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.08); }
.cw-btn.primary { background:rgba(59,130,196,.08); border-color:rgba(59,130,196,.25); color:#1c5a8a; }
.cw-btn.primary:hover { background:rgba(59,130,196,.14); }
.cw-btn.danger { background:rgba(214,72,74,.08); border-color:rgba(214,72,74,.25); color:#a3272a; }
.cw-btn.danger:hover { background:rgba(214,72,74,.14); }
.cw-btn--doc { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-900,#1b2430); font-size:.82rem; font-weight:600; text-decoration:none; width:100%; box-sizing:border-box; }
.cw-btn--doc:hover { background:rgba(13,27,46,.03); border-color:var(--text-400,#8b93a1); }
.cw-btn--doc i { color:var(--text-500,#6b7280); }

.cw-action-status { display:inline-block; font-size:.78rem; font-weight:600; margin-left:8px; vertical-align:middle; }
.cw-action-status.success { color:#1f7a5c; }
.cw-action-status.error { color:#a3272a; }

.cw-table-wrap { overflow:auto; }
.cw-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.cw-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
.cw-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); }
.cw-table tr:last-child td { border-bottom:none; }
.cw-step-icon { width:28px; height:28px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; background:rgba(13,27,46,.04); color:var(--text-600,#5b6472); font-size:.85rem; }

.cw-profile { text-align:center; padding:12px 0 16px; border-bottom:1px solid var(--border,#e4e8ee); margin-bottom:12px; }
.cw-profile-avatar { width:56px; height:56px; border-radius:50%; background:rgba(13,27,46,.06); display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:800; color:var(--text-600,#5b6472); margin-bottom:6px; }
.cw-profile-name { font-size:.92rem; font-weight:700; color:var(--text-900,#1b2430); }
.cw-profile-no { font-size:.78rem; color:var(--text-500,#6b7280); }
.cw-profile-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:12px; }
.cw-profile-stat { text-align:center; padding:8px; background:rgba(13,27,46,.02); border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.cw-profile-stat-value { font-size:1.1rem; font-weight:800; color:var(--text-900,#1b2430); }
.cw-profile-stat-label { font-size:.66rem; font-weight:600; color:var(--text-500,#6b7280); text-transform:uppercase; letter-spacing:.04em; margin-top:2px; }

.cw-discipline-section { display:block; }
.cw-discipline-title { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-500,#6b7280); margin-bottom:8px; }
.cw-discipline-list { display:flex; flex-direction:column; gap:6px; }
.cw-discipline-item { display:flex; justify-content:space-between; align-items:center; padding:6px 10px; background:rgba(13,27,46,.02); border-radius:6px; border:1px solid var(--border,#e4e8ee); font-size:.78rem; color:var(--text-700,#3b4252); }
.cw-discipline-count { font-size:.85rem; font-weight:700; color:var(--text-900,#1b2430); }

.cw-matrix-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.cw-matrix-table th { text-align:left; padding:8px; background:rgba(13,27,46,.03); border-bottom:2px solid var(--border,#e4e8ee); font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-500,#6b7280); }
.cw-matrix-table td { padding:8px; border-bottom:1px solid var(--border,#e4e8ee); color:var(--text-700,#3b4252); }
.cw-matrix-table tr:last-child td { border-bottom:none; }
.cw-matrix-note { font-size:.72rem; color:var(--text-500,#6b7280); margin-top:8px; line-height:1.4; }

.cw-routing-note { font-size:.78rem; color:var(--text-600,#5b6472); margin-bottom:10px; }
.cw-routing-buttons { display:flex; flex-direction:column; gap:8px; }

.cw-action-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  align-items: end;
}
.cw-action-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 140px;
}
.cw-action-cell.cw-action-full {
  grid-column: 1 / -1;
}

@media (max-width: 768px) {
  .cw-action-grid {
    grid-template-columns: 1fr;
  }
  .cw-action-cell {
    min-width: 100%;
  }
}

.sr-item:hover { background:rgba(13,27,46,.03); }

.chwf-investigator-select { position:relative; }
.chwf-investigator-search-wrap { position:relative; }
.chwf-investigator-search { width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:.82rem; color:var(--text-900,#1b2430); background:#fff; }
.chwf-investigator-search:focus { outline:none; border-color:var(--focus-ring,#b6c3d6); box-shadow:0 0 0 3px rgba(37,99,235,.08); }
.chwf-investigator-results { position:absolute; z-index:20; top:100%; left:0; }
.chwf-investigator-status { color:var(--text-500,#6b7280); }
.chwf-investigator-status.success { color:#1f7a5c; }
.chwf-investigator-status.error { color:#a3272a; }

.cw-evidence-loading, .cw-evidence-empty { text-align:center; padding:28px 0; color:var(--text-500,#6b7280); }
.cw-evidence-empty i { font-size:32px; color:var(--hairline,#dde3ea); display:block; margin-bottom:8px; }
.cw-evidence-list { display:flex; flex-direction:column; gap:10px; }
.cw-evidence-item { display:flex; align-items:flex-start; gap:12px; padding:12px; border:1px solid var(--border,#e4e8ee); border-radius:10px; background:rgba(13,27,46,.015); }
.cw-evidence-icon { width:36px; height:36px; border-radius:8px; background:rgba(13,27,46,.04); display:inline-flex; align-items:center; justify-content:center; color:var(--text-600,#5b6472); font-size:1.1rem; flex-shrink:0; }
.cw-evidence-details { flex:1 1 auto; min-width:0; }
.cw-evidence-name { font-weight:700; color:var(--text-900,#1b2430); font-size:0.9rem; word-break:break-word; }
.cw-evidence-name a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.cw-evidence-name a:hover { text-decoration:underline; }
.cw-evidence-desc { font-size:0.82rem; color:var(--text-700,#3b4252); margin-top:4px; }
.cw-evidence-meta { font-size:0.72rem; color:var(--text-500,#6b7280); margin-top:4px; }

.cw-dh-list { display:flex; flex-direction:column; gap:0; position:relative; }
.cw-dh-list::before { content:''; position:absolute; left:17px; top:8px; bottom:8px; width:2px; background:var(--border,#e4e8ee); }
.cw-dh-item { display:flex; align-items:flex-start; gap:12px; padding:10px 0; position:relative; }
.cw-dh-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; margin-top:5px; position:relative; z-index:1; border:2px solid #fff; box-shadow:0 0 0 1px var(--border,#e4e8ee); }
.cw-dh-dot.status-change { background:#3b82c4; }
.cw-dh-dot.reopen { background:#f59e0b; }
.cw-dh-dot.close { background:#1f7a52; }
.cw-dh-body { flex:1 1 auto; min-width:0; padding-bottom:6px; border-bottom:1px solid rgba(228,232,238,.5); }
.cw-dh-body:last-child { border-bottom:none; }
.cw-dh-label { font-weight:700; color:var(--text-900,#1b2430); font-size:0.85rem; }
.cw-dh-meta { font-size:0.72rem; color:var(--text-500,#6b7280); margin-top:3px; display:flex; flex-wrap:wrap; gap:6px; }
.cw-dh-badge { display:inline-block; padding:1px 7px; border-radius:4px; font-size:0.7rem; font-weight:600; }
.cw-dh-badge.old { background:#f3f4f6; color:#6b7280; }
.cw-dh-badge.new { background:#d1fae5; color:#1f7a52; }
.cw-dh-arrow { color:var(--text-400,#9ca3af); font-size:0.7rem; }
.cw-dh-empty { text-align:center; padding:20px 0; color:var(--text-500,#6b7280); font-size:0.82rem; }
.cw-dh-error { color:#a3272a; font-size:0.82rem; }
.cw-btn--sm { padding:4px 10px; font-size:0.75rem; border-radius:6px; }
.cw-form-select { width:100%; box-sizing:border-box; padding:7px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.82rem; color:var(--text-900,#1b2430); background:#fff; height:38px; }
.cw-form-select:focus { outline:none; border-color:var(--focus-ring,#b6c3d6); box-shadow:0 0 0 3px rgba(37,99,235,.08); }
</style>

<script>
(function(){
  window.chIssueDocument = function(templateCode, extraParams, btn) {
    var statusEl = document.getElementById('chwfActionStatus');
    if (!statusEl) return;

    var actionLabels = {
      'written_warning':        'Issue Written Warning',
      'suspension_notice':      'Issue Suspension',
      'termination_decision':   'Final Decision'
    };
    var label = actionLabels[templateCode] || ('Submit ' + templateCode);

    if (!confirm(label + '? This will record the document request and open the email composer.')) return;

    statusEl.textContent = 'Saving document request...';
    if (btn) btn.disabled = true;

    var complaintId = '<?= (int)$case['id'] ?>';
    var employeeId  = '<?= (int)($case['employee_id'] ?? 0) ?>';
    var hrSignatory = <?= json_encode($investigatorName ?: '') ?>;

    var formData = new FormData();
    formData.append('action', 'save_document_request');
    formData.append('complaint_id', complaintId);
    formData.append('employee_id', employeeId);
    formData.append('document_type', templateCode);
    formData.append('template_code', templateCode);
    formData.append('hr_signatory', hrSignatory);

    if (extraParams) {
      for (var key in extraParams) {
        if (Object.prototype.hasOwnProperty.call(extraParams, key)) {
          formData.append(key, extraParams[key]);
        }
      }
    }

    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    var apiUrl = '';
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/disciplinary_action_save.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/disciplinary_action_save.php';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      var data;
      try {
        data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      if (data.success) {
        statusEl.textContent = data.message || 'Document request saved.';
        statusEl.className = 'cw-action-status success';
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } else {
        statusEl.textContent = data.message || 'Save failed.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
      }
    };
    xhr.onerror = function() {
      statusEl.textContent = 'Network error. Check console.';
      statusEl.className = 'cw-action-status error';
      if (btn) btn.disabled = false;
    };
     xhr.send(formData);
   };

   window.chApplyStatus = function() {
     var select = document.getElementById('chStatusSelect');
     var statusEl = document.getElementById('chwfActionStatus');
     if (!select || !statusEl) return;

     var newStatus = select.value;
     if (!newStatus) {
       statusEl.textContent = 'Please select a status.';
       statusEl.className = 'cw-action-status error';
       return;
     }

     var statusLabelMap = {
       'closed_no_violation': 'No Violation',
       'closed_warning_issued': 'Warning Issued',
       'closed_suspension': 'Suspension',
       'closed_termination_recommended': 'Termination Recommended',
       'closed_resolved': 'Resolved'
     };
     var label = statusLabelMap[newStatus] || newStatus.replace(/_/g, ' ');

     if (!confirm('Apply status "' + label + '"? This will update the case status and record the decision.')) return;

     statusEl.textContent = 'Updating status...';
     var btn = document.getElementById('chApplyStatusBtn');
     if (btn) btn.disabled = true;

     var apiUrl = '';
     var path = window.location.pathname;
     var parts = path.split('/').filter(Boolean);
     var lcIndex = parts.indexOf('hrms-capstone');
     if (lcIndex !== -1) {
       apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
     } else {
       var dirs = parts.slice(0, -2);
       apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
     }

     var formData = new FormData();
     formData.append('complaint_id', '<?= (int)$case['id'] ?>');
     formData.append('action', 'finalize_decision');
     formData.append('target_status', newStatus);

     var xhr = new XMLHttpRequest();
     xhr.open('POST', apiUrl, true);
     xhr.setRequestHeader('Accept', 'application/json');
     xhr.onreadystatechange = function() {
       if (xhr.readyState !== 4) return;
       if (btn) btn.disabled = false;
       if (xhr.status < 200 || xhr.status >= 300) {
         statusEl.textContent = 'Request failed (' + xhr.status + ').';
         statusEl.className = 'cw-action-status error';
         return;
       }
       try {
         var data = JSON.parse(xhr.responseText);
       } catch (e) {
         statusEl.textContent = 'Invalid server response.';
         statusEl.className = 'cw-action-status error';
         return;
       }
        if (data.success) {
          statusEl.textContent = data.message || 'Status updated successfully.';
          statusEl.className = 'cw-action-status success';
          setTimeout(function() {
            statusEl.textContent = '';
            if (confirm('Case status updated. Would you like to send an email notification for this decision?')) {
              chShowLetterSelection();
            } else {
              var applyBtn = document.getElementById('chApplyStatusBtn');
              if (applyBtn) applyBtn.disabled = false;
            }
          }, 300);
        } else {
          statusEl.textContent = data.message || 'Update failed.';
          statusEl.className = 'cw-action-status error';
        }
      };
      xhr.onerror = function() {
        if (btn) btn.disabled = false;
        statusEl.textContent = 'Network error.';
        statusEl.className = 'cw-action-status error';
      };
      xhr.send(formData);
    };

   window.chShowLetterSelection = function() {
     var wrap = document.getElementById('chLetterSelectionWrap');
     if (wrap) {
       wrap.style.display = 'block';
     }
     var letterSelect = document.getElementById('chLetterSelect');
     if (letterSelect) letterSelect.value = '';
     var letterStatus = document.getElementById('chLetterStatus');
     if (letterStatus) { letterStatus.textContent = ''; letterStatus.className = 'cw-action-status'; }
     var applyBtn = document.getElementById('chApplyStatusBtn');
     if (applyBtn) applyBtn.disabled = false;
   };

   window.chSendLetter = function() {
     var select = document.getElementById('chLetterSelect');
     var statusEl = document.getElementById('chLetterStatus');
     if (!select || !statusEl) return;

     var letterCode = select.value;
     if (!letterCode) {
       statusEl.textContent = 'Please select a letter type.';
       statusEl.className = 'cw-action-status error';
       return;
     }

     var letterLabels = {
       'written_warning': 'Written Warning',
       'suspension_notice': 'Suspension',
       'termination_decision': 'Termination'
     };
     var label = letterLabels[letterCode] || letterCode;

     if (!confirm('Send ' + label + ' letter via email? This will open the email composer with the selected template.')) return;

     statusEl.textContent = 'Redirecting to email composer...';

     var baseUrl = <?= $jsNotificationUrl; ?>;
     var url = baseUrl + '&template_code=' + encodeURIComponent(letterCode) + '&document_type=' + encodeURIComponent(letterCode);

     window.location.href = url;
   };

    window.chSubmitAction = function(action, btn) {
      var statusEl = document.getElementById('chwfActionStatus');
    if (!statusEl) return;
    if (!confirm('Update complaint status? This will advance the workflow accordingly.')) return;

    statusEl.textContent = 'Updating...';
    if (btn) btn.disabled = true;

    var apiUrl = '?page=complaint-workflow&id=<?= (int)$case['id'] ?>';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
    }

    var formData = new FormData();
    formData.append('complaint_id', '<?= (int)$case['id'] ?>');
    formData.append('action', action);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      if (data.success) {
        statusEl.textContent = data.message || 'Status updated successfully.';
        statusEl.className = 'cw-action-status success';
        setTimeout(function() { location.reload(); }, 900);
      } else {
        statusEl.textContent = data.message || 'Update failed.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
      }
    };
    xhr.onerror = function() {
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'cw-action-status error';
      if (btn) btn.disabled = false;
    };
    xhr.send(formData);
  };

  window.chSubmitDecision = function(targetStatus, btn) {
    var statusEl = document.getElementById('chwfActionStatus');
    if (!statusEl) return;
    var decisionLabel = btn ? btn.textContent.trim() : 'Apply decision';
    if (!confirm(decisionLabel + '? This will update the complaint status and record the decision.')) return;

    statusEl.textContent = 'Recording decision...';
    if (btn) btn.disabled = true;

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
    }

    var formData = new FormData();
    formData.append('complaint_id', '<?= (int)$case['id'] ?>');
    formData.append('action', 'finalize_decision');
    formData.append('target_status', targetStatus);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
        return;
      }
      if (data.success) {
        statusEl.textContent = data.message || 'Decision recorded successfully.';
        statusEl.className = 'cw-action-status success';
        setTimeout(function() { location.reload(); }, 900);
      } else {
        statusEl.textContent = data.message || 'Decision failed.';
        statusEl.className = 'cw-action-status error';
        if (btn) btn.disabled = false;
      }
    };
    xhr.onerror = function() {
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'cw-action-status error';
      if (btn) btn.disabled = false;
    };
    xhr.send(formData);
  };

  window.cwLoadDecisionHistory = function() {
    function esc(t) {
      if (t == null) return '';
      return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    var listEl = document.getElementById('cwDecisionHistoryList');
    if (!listEl) return;

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/get_lc_complaint_decision_history.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/get_lc_complaint_decision_history.php';
    }

    listEl.innerHTML = '<div class="cw-dh-empty"><i class="bi bi-hourglass-split"></i><br>Loading…</div>';

    var complaintId = '<?= (int)$case['id'] ?>';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl + '?complaint_id=' + encodeURIComponent(complaintId), true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status < 200 || xhr.status >= 300) {
        listEl.innerHTML = '<div class="cw-dh-error">Failed to load history (' + xhr.status + ').</div>';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        listEl.innerHTML = '<div class="cw-dh-error">Invalid server response.</div>';
        return;
      }
      if (!data.success || !Array.isArray(data.data) || !data.data.length) {
        listEl.innerHTML = '<div class="cw-dh-empty">No decision records yet.</div>';
        return;
      }

      function dotCls(action, ns) {
        if (action === 'reopen') return 'reopen';
        if (ns === 'closed' || ns === 'closed_no_violation' || ns === 'closed_warning_issued' || ns === 'closed_suspension' || ns === 'closed_termination_recommended' || ns === 'closed_resolved') return 'close';
        return 'status-change';
      }

      function fmtDate(d) {
        if (!d) return '—';
        var dt = new Date(d.replace(' ', 'T'));
        if (isNaN(dt.getTime())) return d;
        return dt.toLocaleString('en-PH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
      }

      listEl.innerHTML = '<div class="cw-dh-list">' + data.data.map(function(r) {
        var cls = dotCls(r.action, r.new_status);
        var badges = '';
        if (r.old_status) {
          badges += '<span class="cw-dh-badge old">' + escapeHtml(r.old_status.replace(/_/g,' ')) + '</span>';
        }
        if (r.new_status) {
          badges += '<span class="cw-dh-arrow">→</span><span class="cw-dh-badge new">' + escapeHtml(r.new_status.replace(/_/g,' ')) + '</span>';
        }
        return '<div class="cw-dh-item">'
          + '<div class="cw-dh-dot ' + cls + '"></div>'
          + '<div class="cw-dh-body">'
          + '<div class="cw-dh-label">' + esc(r.decision_label || r.action) + '</div>'
          + '<div class="cw-dh-meta">'
          + '<span><i class="bi bi-person"></i> ' + esc(r.performer_name || 'User #' + r.performed_by) + '</span>'
          + '<span><i class="bi bi-calendar"></i> ' + fmtDate(r.created_at) + '</span>'
          + (badges ? '<span>' + badges + '</span>' : '')
          + '</div>'
          + (r.notes ? '<div style="font-size:0.75rem;color:var(--text-500,#6b7280);margin-top:3px;">' + esc(r.notes) + '</div>' : '')
          + '</div>'
          + '</div>';
      }).join('') + '</div>';
    };
    xhr.onerror = function() {
      listEl.innerHTML = '<div class="cw-dh-error">Network error. Check console for details.</div>';
    };
    xhr.send();
  };

  window.chwfInvestigatorSearch = function() {
    var container = document.querySelector('.chwf-investigator-select');
    if (!container) return;
    var searchEl = container.querySelector('.chwf-investigator-search');
    var resultsEl = container.querySelector('.chwf-investigator-results');
    var statusEl = container.querySelector('.chwf-investigator-status');
    var complaintId = parseInt((searchEl ? searchEl.getAttribute('data-complaint-id') : '0') || '0', 10);
    var debounceT = null;

    function buildItem(emp, idx) {
      var name = emp.full_name || 'Employee';
      var initials = name.split(' ').map(function(n){ return n.charAt(0); }).join('').substring(0,2).toUpperCase();
      var dept = emp.department || '';
      var pos = emp.job_title || emp.position_name || '';
      var sub = [dept, pos].filter(Boolean).join(' · ') || (emp.email || '');
      return '<div class="sr-item" role="option" tabindex="0" data-emp-index="' + idx + '" data-employee-id="' + (emp.employee_id || emp.id || '') + '" data-emp-name="' + name.replace(/"/g, '&quot;') + '" style="display:flex; align-items:center; gap:10px; padding:8px 10px; cursor:pointer;">'
        + '<div style="width:28px; height:28px; border-radius:50%; background:rgba(13,27,46,.06); display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; color:var(--text-600,#5b6472); flex-shrink:0;">' + initials + '</div>'
        + '<div style="flex:1; min-width:0;">'
        + '<div class="sr-item-name" style="font-weight:600; color:var(--text-900,#1b2430); font-size:0.82rem;">' + name + '</div>'
        + '<div style="font-size:0.72rem; color:var(--text-500,#6b7280);">' + (sub || '') + '</div>'
        + '</div>'
        + '</div>';
    }

    function renderResults(items) {
      if (!resultsEl) return;
      if (!items.length) {
        resultsEl.innerHTML = '<div style="padding:10px; font-size:0.82rem; color:var(--text-500,#6b7280);">No em_employees found.</div>';
        resultsEl.style.display = 'block';
        return;
      }
      resultsEl.innerHTML = items.map(buildItem).join('');
      resultsEl.style.display = 'block';
      resultsEl.querySelectorAll('.sr-item').forEach(function(el) {
        el.addEventListener('click', function() {
          var eid = el.getAttribute('data-employee-id');
          var name = el.getAttribute('data-emp-name') || '';
          if (eid) chwfAssignInvestigator(complaintId, eid, name);
        });
      });
    }

    if (searchEl) {
      searchEl.addEventListener('input', function() {
        var q = (searchEl.value || '').trim();
        if (debounceT) clearTimeout(debounceT);
        if (q.length < 2) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; if (statusEl) statusEl.textContent = ''; return; }
        if (statusEl) { statusEl.textContent = 'Searching…'; statusEl.className = 'chwf-investigator-status'; }
        debounceT = setTimeout(function() {
          var apiUrl = '';
          var path = window.location.pathname;
          var parts = path.split('/').filter(Boolean);
          var lcIndex = parts.indexOf('hrms-capstone');
          if (lcIndex !== -1) {
            apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/search_hr_employees.php';
          } else {
            var dirs = parts.slice(0, -2);
            apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/search_hr_employees.php';
          }
          var xhr = new XMLHttpRequest();
          xhr.open('GET', apiUrl + '?q=' + encodeURIComponent(q) + '&department_id=', true);
          xhr.setRequestHeader('Accept', 'application/json');
          xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status < 200 || xhr.status >= 300) {
              if (statusEl) { statusEl.textContent = 'Search failed (' + xhr.status + ').'; statusEl.className = 'chwf-investigator-status error'; }
              return;
            }
            try {
              var data = JSON.parse(xhr.responseText);
            } catch (e) {
              if (statusEl) { statusEl.textContent = 'Search failed.'; statusEl.className = 'chwf-investigator-status error'; }
              return;
            }
            var items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
            renderResults(items);
            if (statusEl && !items.length) { statusEl.textContent = ''; statusEl.className = 'chwf-investigator-status'; }
          };
          xhr.onerror = function() {
            if (statusEl) { statusEl.textContent = 'Network error.'; statusEl.className = 'chwf-investigator-status error'; }
          };
          xhr.send();
        }, 250);
      });
    }
  };

  window.chwfAssignInvestigator = function(complaintId, employeeId, fullName) {
    var container = document.querySelector('.chwf-investigator-select');
    var statusEl = container ? container.querySelector('.chwf-investigator-status') : document.querySelector('.chwf-investigator-status');
    if (!statusEl) return;
    if (!confirm('Assign ' + fullName + ' as investigator for this complaint?')) return;

    statusEl.textContent = 'Assigning…';
    statusEl.className = 'chwf-investigator-status';

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
    }

    var formData = new FormData();
    formData.append('complaint_id', String(complaintId));
    formData.append('action', 'assign_investigator');
    formData.append('employee_id', String(employeeId));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'chwf-investigator-status error';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response. Check console.';
        statusEl.className = 'chwf-investigator-status error';
        console.error('Invalid JSON', xhr.responseText);
        return;
      }
      if (data.success) {
        statusEl.textContent = 'Investigator assigned.';
        statusEl.className = 'chwf-investigator-status success';
        if (container) {
          var wrap = container.querySelector('.chwf-investigator-search-wrap');
          var selected = container.querySelector('.chwf-investigator-selected');
          var nameEl = container.querySelector('.chwf-investigator-selected-name');
          if (wrap) wrap.style.display = 'none';
          if (selected) selected.style.display = 'flex';
          if (nameEl) nameEl.textContent = fullName;
        } else {
          setTimeout(function() { location.reload(); }, 600);
        }
      } else {
        statusEl.textContent = data.message || 'Failed to assign investigator.';
        statusEl.className = 'chwf-investigator-status error';
      }
    };
    xhr.onerror = function() {
      console.error('Assign investigator network error. URL:', apiUrl);
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'chwf-investigator-status error';
    };
    xhr.send(formData);
  };

  window.chwfClearInvestigator = function(complaintId) {
    var container = document.querySelector('.chwf-investigator-select');
    var statusEl = container ? container.querySelector('.chwf-investigator-status') : document.querySelector('.chwf-investigator-status');
    if (!statusEl) return;
    if (!confirm('Remove the assigned investigator?')) return;

    statusEl.textContent = 'Removing…';
    statusEl.className = 'chwf-investigator-status';

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
    }

    var formData = new FormData();
    formData.append('complaint_id', String(complaintId));
    formData.append('action', 'clear_investigator');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'chwf-investigator-status error';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response. Check console.';
        statusEl.className = 'chwf-investigator-status error';
        console.error('Invalid JSON', xhr.responseText);
        return;
      }
      if (data.success) {
        statusEl.textContent = 'Investigator removed.';
        statusEl.className = 'chwf-investigator-status success';
        if (container) {
          var wrap = container.querySelector('.chwf-investigator-search-wrap');
          var selected = container.querySelector('.chwf-investigator-selected');
          if (wrap) wrap.style.display = 'block';
          if (selected) selected.style.display = 'none';
          var searchInput = container.querySelector('.chwf-investigator-search');
          if (searchInput) searchInput.value = '';
          var results = container.querySelector('.chwf-investigator-results');
          if (results) { results.innerHTML = ''; results.style.display = 'none'; }
        } else {
          setTimeout(function() { location.reload(); }, 600);
        }
      } else {
        statusEl.textContent = data.message || 'Failed to remove investigator.';
        statusEl.className = 'chwf-investigator-status error';
      }
    };
    xhr.onerror = function() {
      console.error('Clear investigator network error. URL:', apiUrl);
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'chwf-investigator-status error';
    };
    xhr.send(formData);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', chwfInvestigatorSearch);
  } else {
    chwfInvestigatorSearch();
  }

  window.cwLoadEvidence = function() {
    var listEl = document.getElementById('cwEvidenceList');
    var loadingEl = document.getElementById('cwEvidenceLoading');
    var emptyEl = document.getElementById('cwEvidenceEmpty');
    var statusEl = document.getElementById('cwEvidenceStatus');
    if (!listEl) return;

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_evidence.php?complaint_id=<?= (int)$case['id'] ?>';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_evidence.php?complaint_id=<?= (int)$case['id'] ?>';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status < 200 || xhr.status >= 300) {
        if (loadingEl) loadingEl.style.display = 'none';
        if (listEl) listEl.innerHTML = '<div class="cw-evidence-empty"><i class="bi bi-exclamation-triangle"></i><span>Failed to load evidence.</span></div>';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        if (loadingEl) loadingEl.style.display = 'none';
        if (listEl) listEl.innerHTML = '<div class="cw-evidence-empty"><i class="bi bi-exclamation-triangle"></i><span>Invalid server response.</span></div>';
        return;
      }
      if (loadingEl) loadingEl.style.display = 'none';
      if (!data.success || !data.evidence || !data.evidence.length) {
        if (emptyEl) emptyEl.style.display = '';
        if (listEl) listEl.innerHTML = '';
        return;
      }
      if (emptyEl) emptyEl.style.display = 'none';
      var html = '';
      for (var i = 0; i < data.evidence.length; i++) {
        var ev = data.evidence[i];
        var sizeText = ev.file_size ? Math.round(ev.file_size / 1024) + ' KB' : '';
        var fileUrl = './' + ev.file_path;
        html += '<div class="cw-evidence-item">'
          + '<div class="cw-evidence-icon"><i class="bi bi-file-earmark"></i></div>'
          + '<div class="cw-evidence-details">'
          + '<div class="cw-evidence-name"><a href="' + fileUrl + '" target="_blank" rel="noopener">' + ev.file_name + '</a></div>';
        if (ev.description) html += '<div class="cw-evidence-desc">' + ev.description + '</div>';
        html += '<div class="cw-evidence-meta">' + (ev.file_type || '') + ' &middot; ' + sizeText + ' &middot; Uploaded ' + ev.uploaded_at + '</div>'
          + '</div>'
          + '</div>';
      }
      if (listEl) listEl.innerHTML = html;
    };
    xhr.send();
  };

  cwLoadEvidence();

  cwLoadDecisionHistory();
  var reloadBtn = document.getElementById('cwReloadHistoryBtn');
  if (reloadBtn) {
    reloadBtn.addEventListener('click', function() {
      cwLoadDecisionHistory();
    });
  }

  window.chShowResponseForm = function() {
    var card = document.getElementById('chwfResponseCard');
    if (card) card.style.display = 'block';
    var textarea = document.getElementById('chwfResponseText');
    if (textarea) textarea.focus();
  };

  window.chHideResponseForm = function() {
    var card = document.getElementById('chwfResponseCard');
    var textarea = document.getElementById('chwfResponseText');
    var statusEl = document.getElementById('chwfResponseStatus');
    if (card) card.style.display = 'none';
    if (textarea) textarea.value = '';
    if (statusEl) { statusEl.textContent = ''; statusEl.className = 'cw-action-status'; }
  };

  window.chSubmitResponse = function() {
    var textarea = document.getElementById('chwfResponseText');
    var statusEl = document.getElementById('chwfResponseStatus');
    if (!textarea || !statusEl) return;

    var responseText = textarea.value.trim();
    if (responseText === '') {
      statusEl.textContent = 'Response text is required.';
      statusEl.className = 'cw-action-status error';
      return;
    }

    statusEl.textContent = 'Saving response...';
    statusEl.className = 'cw-action-status';

    var apiUrl = '';
    var path = window.location.pathname;
    var parts = path.split('/').filter(Boolean);
    var lcIndex = parts.indexOf('hrms-capstone');
    if (lcIndex !== -1) {
      apiUrl = window.location.origin + '/' + parts.slice(0, lcIndex + 1).join('/') + '/modules/compliance/lib/api/complaint_workflow_action.php';
    } else {
      var dirs = parts.slice(0, -2);
      apiUrl = window.location.origin + '/' + dirs.join('/') + '/api/complaint_workflow_action.php';
    }

    var formData = new FormData();
    formData.append('complaint_id', '<?= (int)$case['id'] ?>');
    formData.append('action', 'record_response');
    formData.append('employee_response', responseText);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status < 200 || xhr.status >= 300) {
        statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
        statusEl.className = 'cw-action-status error';
        return;
      }
      try {
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        statusEl.textContent = 'Invalid server response.';
        statusEl.className = 'cw-action-status error';
        return;
      }
      if (data.success) {
        statusEl.textContent = data.message || 'Response recorded successfully.';
        statusEl.className = 'cw-action-status success';
        setTimeout(function() { location.reload(); }, 900);
      } else {
        statusEl.textContent = data.message || 'Failed to record response.';
        statusEl.className = 'cw-action-status error';
      }
    };
    xhr.onerror = function() {
      statusEl.textContent = 'Network error. Check console for details.';
      statusEl.className = 'cw-action-status error';
    };
    xhr.send(formData);
  };
  })();
</script>
<?php ob_end_flush(); ?>


