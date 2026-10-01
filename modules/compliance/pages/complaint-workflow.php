<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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
     $cols = $db->query("SHOW COLUMNS FROM lc_complaints WHERE Field IN ('employee_response','employee_response_date','termination_reply','termination_review_status','termination_loi_pdf_path','termination_loi_submitted_at')")->fetchAll(PDO::FETCH_COLUMN);
     $missing = array_diff(['employee_response','employee_response_date','termination_reply','termination_review_status','termination_loi_pdf_path','termination_loi_submitted_at'], $cols);
     if ($missing !== []) {
         $db->beginTransaction();
         if (in_array('employee_response', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN employee_response TEXT DEFAULT NULL");
         }
         if (in_array('employee_response_date', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN employee_response_date DATETIME DEFAULT NULL");
         }
         if (in_array('termination_reply', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN termination_reply TEXT DEFAULT NULL");
         }
         if (in_array('termination_review_status', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN termination_review_status ENUM('pending','rejected','considered') DEFAULT 'pending'");
         }
         if (in_array('termination_recommended_at', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN termination_recommended_at DATETIME DEFAULT NULL");
         }
         if (in_array('termination_loi_pdf_path', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN termination_loi_pdf_path VARCHAR(255) DEFAULT NULL AFTER termination_recommended_at");
         }
         if (in_array('termination_loi_submitted_at', $missing, true)) {
             $db->exec("ALTER TABLE lc_complaints ADD COLUMN termination_loi_submitted_at DATETIME DEFAULT NULL AFTER termination_loi_pdf_path");
         }
         $db->commit();
     }

    $statusCol = $db->query("SHOW COLUMNS FROM lc_complaints WHERE Field = 'status'")->fetch(PDO::FETCH_ASSOC);
    if ($statusCol && stripos($statusCol['Type'] ?? '', 'closed_second_written_warning') === false) {
        $db->exec("ALTER TABLE lc_complaints MODIFY COLUMN status ENUM('under_initial_review','under_investigation','nte_issued','pending_employee_response','for_decision','closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved','closed','closed_second_written_warning','closed_final_written_warning','termination_employee_reply','termination_reviewed') DEFAULT 'under_initial_review'");
    }

    $notifCols = $db->query("SHOW COLUMNS FROM lc_notifications WHERE Field IN ('complaint_id','reply_to_notification_id')")->fetchAll(PDO::FETCH_COLUMN);
    $notifMissing = array_diff(['complaint_id','reply_to_notification_id'], $notifCols);
    if ($notifMissing !== []) {
        $db->beginTransaction();
        if (in_array('complaint_id', $notifMissing, true)) {
            $db->exec("ALTER TABLE lc_notifications ADD COLUMN complaint_id INT(11) DEFAULT NULL AFTER employee_id, ADD KEY idx_complaint_id (complaint_id)");
        }
        if (in_array('reply_to_notification_id', $notifMissing, true)) {
            $db->exec("ALTER TABLE lc_notifications ADD COLUMN reply_to_notification_id INT(11) DEFAULT NULL AFTER complaint_id, ADD KEY idx_reply_to_notification_id (reply_to_notification_id)");
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
function ch_employee_email(PDO $db, int $employeeId): string {
    if (empty($employeeId)) return '';
    $row = ch_row($db, "SELECT email FROM em_employees WHERE employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row['email'] ?? '';
}
function ch_respondent_email(PDO $db, ?int $respondentId, string $respondentName = ''): string {
    if (!empty($respondentId)) {
        $row = ch_row($db, "SELECT email FROM em_employees WHERE employee_id = " . (int)$respondentId . " LIMIT 1");
        return $row['email'] ?? '';
    }
    return '';
}
function ch_respondent_name(PDO $db, ?int $respondentId, string $respondentName = ''): string {
    if (!empty($respondentName)) return $respondentName;
    if (!empty($respondentId)) {
        return ch_employee_name($db, (int)$respondentId);
    }
    return '';
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
    if (in_array($s, ['closed','resolved','closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved','closed_second_written_warning','closed_final_written_warning','termination_reviewed'], true)) return 'irwf-badge status-closed';
    if (in_array($s, ['for_decision','pending_employee_response','termination_employee_reply'], true)) return 'irwf-badge status-escalated';
    if (in_array($s, ['under_investigation','under_initial_review','nte_issued'], true)) return 'irwf-badge status-investigation';
    return 'irwf-badge status-submitted';
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
    ['key' => 'assign_officer',        'label' => 'Assign Compliance Officer'],
    ['key' => 'evidence_check',        'label' => 'Check Evidence and Complainant Testimony'],
    ['key' => 'nte_issued',            'label' => 'Send NTE (Notice to Explain) to the Employee'],
    ['key' => 'employee_hearing',      'label' => 'Hearing (Recording Employee Response)'],
    ['key' => 'decision_made',         'label' => 'Decision'],
];

if (in_array($currentStatus, ['closed_termination_recommended','termination_employee_reply','termination_reviewed'], true)) {
    $workflowSteps[] = ['key' => 'termination_employee_reply', 'label' => 'Letter of Intent'];
    $workflowSteps[] = ['key' => 'termination_review', 'label' => 'Review Action'];
}

$statusStepMap = [
    'under_initial_review'             => 'assign_officer',
    'under_investigation'              => 'evidence_check',
    'nte_issued'                       => 'nte_issued',
    'pending_employee_response'        => 'employee_hearing',
    'for_decision'                     => 'decision_made',
    'closed_no_violation'              => 'decision_made',
    'closed_warning_issued'            => 'decision_made',
    'closed_second_written_warning'    => 'decision_made',
    'closed_final_written_warning'     => 'decision_made',
    'closed_suspension'                => 'decision_made',
    'closed_termination_recommended'   => 'termination_employee_reply',
    'termination_employee_reply'       => 'termination_review',
    'termination_reviewed'             => 'termination_review',
    'closed_resolved'                  => 'decision_made',
    'closed'                           => 'decision_made',
];

$targetStep = $statusStepMap[$currentStatus] ?? 'complaint_submitted';
$currentStepIndex = 0;
foreach ($workflowSteps as $idx => $step) {
    if ($step['key'] === $targetStep) {
        $currentStepIndex = $idx;
        break;
    }
}

if (empty($case['assigned_to']) && $currentStatus === 'under_initial_review') {
    foreach ($workflowSteps as $idx => $step) {
        if ($step['key'] === 'assign_officer') {
            $currentStepIndex = $idx;
            $targetStep = 'assign_officer';
            break;
        }
    }
}

function ch_employee_profile(PDO $db, int $employeeId): array {
    $row = ch_row($db, "SELECT e.first_name, e.middle_name, e.last_name, e.email, e.employee_code, d.department_name, p.position_name FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id LEFT JOIN em_positions p ON p.position_id = e.position_id WHERE e.employee_id = " . (int)$employeeId . " LIMIT 1");
    return $row ?: [];
}

$respondentEmployeeId = (int)($case['respondent_employee_id'] ?? 0);
$respondentProfile = [];
if ($respondentEmployeeId > 0) {
    $respondentProfile = ch_employee_profile($db, $respondentEmployeeId);
}

$previousCases = [];
try {
    $employeeId = (int)($case['employee_id'] ?? 0);
    $respondentId = (int)($case['respondent_employee_id'] ?? 0);
    
    $sql = "SELECT DISTINCT c.id, c.type, c.status, c.created_at, 
                   d.decision_label, d.new_status, d.created_at AS decision_date
            FROM lc_complaints c
            LEFT JOIN lc_complaint_decision_history d ON d.complaint_id = c.id
            WHERE (c.employee_id = :eid OR c.respondent_employee_id = :rid)
              AND c.id != :current_id
            ORDER BY c.created_at DESC
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':eid' => $employeeId,
        ':rid' => $respondentId,
        ':current_id' => $complaintId
    ]);
    $previousCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $previousCases = [];
}

$respondentCaseCounts = [
    'total' => 0,
    'warning_first' => 0,
    'warning_second' => 0,
    'warning_final' => 0,
    'suspension' => 0,
];

if ($respondentEmployeeId > 0) {
    try {
        $countSql = "SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN status IN ('closed_warning_issued','closed_second_written_warning','closed_final_written_warning','closed_suspension','closed_termination_recommended','closed_no_violation','closed_resolved','closed') THEN 1 ELSE 0 END) AS decided,
                        SUM(CASE WHEN status IN ('closed_warning_issued') THEN 1 ELSE 0 END) AS warning_first,
                        SUM(CASE WHEN status IN ('closed_second_written_warning') THEN 1 ELSE 0 END) AS warning_second,
                        SUM(CASE WHEN status IN ('closed_final_written_warning') THEN 1 ELSE 0 END) AS warning_final,
                        SUM(CASE WHEN status IN ('closed_suspension') THEN 1 ELSE 0 END) AS suspension
                    FROM lc_complaints
                    WHERE respondent_employee_id = :rid
                      AND id != :current_id";
        $stmt = $db->prepare($countSql);
        $stmt->execute([
            ':rid' => $respondentEmployeeId,
            ':current_id' => $complaintId,
        ]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($counts) {
            $respondentCaseCounts['total'] = (int)($counts['total'] ?? 0);
            $respondentCaseCounts['warning_first'] = (int)($counts['warning_first'] ?? 0);
            $respondentCaseCounts['warning_second'] = (int)($counts['warning_second'] ?? 0);
            $respondentCaseCounts['warning_final'] = (int)($counts['warning_final'] ?? 0);
            $respondentCaseCounts['suspension'] = (int)($counts['suspension'] ?? 0);
        }
    } catch (Throwable $e) {
        $respondentCaseCounts = [
            'total' => 0,
            'warning_first' => 0,
            'warning_second' => 0,
            'warning_final' => 0,
            'suspension' => 0,
        ];
    }
}

$investigatorName = !empty($case['assigned_to']) ? ch_employee_name($db, $case['assigned_to']) : '';
$employeeName = ch_employee_name($db, $case['employee_id'] ?? 0);
$employeeNo = ch_employee_no($db, $case['employee_id'] ?? 0);
$employeeEmail = ch_employee_email($db, $case['employee_id'] ?? 0);
$respondentName = !empty($case['respondent_name']) ? $case['respondent_name'] : ch_employee_name($db, $case['respondent_employee_id'] ?? 0);
$respondentEmail = ch_respondent_email($db, $case['respondent_employee_id'] ?? 0, $case['respondent_name'] ?? '');
$respondentNo = !empty($case['respondent_employee_id']) ? ch_employee_no($db, $case['respondent_employee_id']) : '';

$statsTotal = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0), 0);
$statsOpen = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status NOT IN ('closed','closed_no_violation','closed_warning_issued','closed_second_written_warning','closed_final_written_warning','closed_suspension','closed_termination_recommended','closed_resolved')", 0);
$statsInvestigation = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status IN ('under_initial_review','under_investigation','nte_issued','pending_employee_response','for_decision')", 0);
$statsDecision = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status = 'for_decision'", 0);
$statsClosed = (int) ch_value($db, "SELECT COUNT(*) FROM `$complaintTable` WHERE employee_id = " . (int)($case['employee_id'] ?? 0) . " AND status IN ('closed_no_violation','closed_warning_issued','closed_second_written_warning','closed_final_written_warning','closed_suspension','closed_termination_recommended','closed_resolved','closed')", 0);

$evidenceCounts = [];
$stepEvidenceItems = [];
$evidenceStepAliases = [
    'under_initial_review' => 'evidence_check',
];
try {
    $row = $case;
    $stepKey = $evidenceStepAliases[$currentStatus] ?? 'evidence_check';
    $item = $row['evidence_item'] ?? $row['evidence_path'] ?? $row['image_path'] ?? '';
    $notes = $row['evidence_notes'] ?? $row['notes'] ?? ($row['workflow_progress'] ?? '');
    $status = $row['evidence_status'] ?? ($row['status'] ?? 'Pending');
    $uploadedAt = $row['evidence_uploaded_at'] ?? ($row['created_at'] ?? '');
    $uploadedBy = !empty($row['evidence_uploaded_by']) ? (int) $row['evidence_uploaded_by'] : (!empty($row['assigned_to']) ? (int) $row['assigned_to'] : null);
    $imagePath = $row['evidence_image_path'] ?? $row['image_path'] ?? $row['evidence_path'] ?? '';
    if (!$item && $imagePath) {
        $item = basename($imagePath);
    }
    if ($item || $imagePath || $notes) {
        $evidenceCounts[$stepKey] = ($evidenceCounts[$stepKey] ?? 0) + 1;
        $stepEvidenceItems[$stepKey][] = [
            'workflow_step_key' => $stepKey,
            'evidence_item' => (string) $item,
            'required' => true,
            'status' => (string) $status,
            'notes' => (string) $notes,
            'image_path' => $imagePath !== '' ? (string) $imagePath : null,
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => $uploadedAt !== '' ? (string) $uploadedAt : null,
            'created_at' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
            'updated_at' => (string) ($row['updated_at'] ?? date('Y-m-d H:i:s')),
        ];
    }
} catch (Throwable $e) {}

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

$assignableOfficers = [];
try {
    $assignableOfficers = ch_q($db, "SELECT employee_id, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name, employee_code FROM em_employees WHERE status IS NULL OR status = 'active' ORDER BY full_name ASC");
} catch (Throwable $e) {}
?>
<!-- Confirm Modal -->
<div id="chwfConfirmModal" class="lc-modal-backdrop irwf-confirm-backdrop" onclick="if(event.target===this)chwfConfirmModal(false)">
  <div class="lc-modal irwf-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="chwfConfirmTitle" aria-describedby="chwfConfirmDesc">
    <div class="irwf-confirm-body">
      <p id="chwfConfirmTitle" class="irwf-confirm-title"></p>
      <p id="chwfConfirmDesc" class="irwf-confirm-desc"></p>
      <p id="chwfConfirmTransition" class="irwf-confirm-transition"></p>
      <div class="irwf-confirm-actions">
        <button type="button" class="cc-btn irwf-confirm-cancel" onclick="chwfConfirmModal(false)">Cancel</button>
        <button type="button" class="cc-btn primary irwf-confirm-submit" onclick="chwfConfirmModal(true)">Confirm</button>
      </div>
    </div>
  </div>
</div>

<!-- Evidence Modal -->
<div id="chwfEvidenceModal" class="lc-modal-backdrop" onclick="if(event.target===this)chwfCloseModal('chwfEvidenceModal')">
  <div class="lc-modal" style="max-width:640px;">
    <div class="lc-modal-header">
      <div class="lc-modal-title">Workflow Evidence</div>
      <button type="button" class="lc-modal-close" onclick="chwfCloseModal('chwfEvidenceModal')">&times;</button>
    </div>
    <div class="lc-modal-body" id="chwfEvidenceBody">
      <div class="irwf-evidence-loading">Loading...</div>
    </div>
    <div class="lc-modal-body" style="border-top:1px solid var(--hairline, #e4e8ee); padding-top:12px;">
      <span id="chwfEvidenceStatus" class="irwf-action-status"></span>
    </div>
  </div>
</div>

<!-- Decision Email Modal -->
<div id="chwfDecisionEmailModal" class="lc-modal-backdrop" onclick="if(event.target===this)chwfCloseModal('chwfDecisionEmailModal')">
  <div class="lc-modal" style="max-width:720px;">
    <div class="lc-modal-header">
      <div class="lc-modal-title">Decision Email</div>
      <button type="button" class="lc-modal-close" onclick="chwfCloseModal('chwfDecisionEmailModal')">&times;</button>
    </div>
    <div class="lc-modal-body">
      <div style="margin-bottom:12px;">
        <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">To</label>
        <input type="text" id="chwfDecisionEmailTo" readonly style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; background:#f6f8fb; font-size:0.85rem; color:#3b4252;" />
      </div>
      <div style="margin-bottom:12px;">
        <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Subject</label>
        <input type="text" id="chwfDecisionEmailSubject" style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; font-size:0.85rem; color:#1b2430;" />
      </div>
      <div style="margin-bottom:12px;">
        <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Message</label>
        <textarea id="chwfDecisionEmailBody" rows="10" style="width:100%; padding:10px 12px; border:1px solid #e4e8ee; font-size:0.85rem; color:#1b2430; font-family:inherit; resize:vertical;"></textarea>
      </div>
      <div id="chwfDecisionEmailStatus" class="irwf-action-status" style="margin-bottom:8px;"></div>
    </div>
    <div class="lc-modal-body" style="border-top:1px solid var(--hairline, #e4e8ee); padding-top:12px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
      <button type="button" class="cc-btn" onclick="chwfCloseModal('chwfDecisionEmailModal')">Cancel</button>
      <button type="button" class="cc-btn" onclick="chRecordDecisionOnly()">Record Decision Only</button>
      <button type="button" class="cc-btn primary" onclick="chSendDecisionEmail()">Send Email & Record Decision</button>
    </div>
  </div>
</div>

<section class="irwf-page">
  <div class="irwf-dashboard">
    <div class="irwf-main">
      <div class="irwf-card">
        <h3>Complaint Information</h3>
        <div class="irwf-grid">
          <div class="irwf-field">
            <div class="irwf-label">Case Number</div>
            <div class="irwf-value mono"><?= htmlspecialchars('CMP-' . str_pad($case['id'], 5, '0', STR_PAD_LEFT)) ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Complaint Type</div>
            <div class="irwf-value"><?= htmlspecialchars($case['type'] ?? '—') ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Severity</div>
            <div class="irwf-value"><?= htmlspecialchars(ucfirst($case['severity'] ?? 'medium')) ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Date Filed</div>
            <div class="irwf-value"><?= ch_date($case['created_at'] ?? null, 'M d, Y g:i A') ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Employee</div>
            <div class="irwf-value"><?= htmlspecialchars($employeeName ?: '—') ?> <?= !empty($employeeNo) ? '<small>(' . htmlspecialchars($employeeNo) . ')</small>' : '' ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Respondent</div>
            <div class="irwf-value"><?= htmlspecialchars($respondentName ?: '—') ?> <?= !empty($respondentNo) ? '<small>(' . htmlspecialchars($respondentNo) . ')</small>' : '' ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Incident Date</div>
            <div class="irwf-value"><?= ch_date($case['incident_date'] ?? null, 'M d, Y') ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Incident Time</div>
            <div class="irwf-value"><?= htmlspecialchars($case['incident_time'] ?? '—') ?></div>
          </div>
          <div class="irwf-field">
            <div class="irwf-label">Assigned Officer</div>
            <div class="irwf-value"><?= htmlspecialchars($investigatorName ?: '—') ?></div>
          </div>
          <?php if (!empty($case['mitigation_plan'])): ?>
          <div class="irwf-field full">
            <div class="irwf-label">Mitigation Plan</div>
            <div class="irwf-value"><?= nl2br(htmlspecialchars($case['mitigation_plan'])) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="irwf-card">
        <h3>Complaint Workflow</h3>
        <?php if (!empty($case['description'])): ?>
        <div class="irwf-workflow-summary">
          <div class="irwf-workflow-summary-label">Description</div>
          <div class="irwf-workflow-summary-value"><?= nl2br(htmlspecialchars($case['description'])) ?></div>
        </div>
        <?php endif; ?>
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
             } else {
               $stepClass = 'pending';
             }
           ?>
            <div class="irwf-flow-step <?= $stepClass ?>" data-step-index="<?= $idx ?>" data-step-key="<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>">
              <div class="irwf-flow-dot"></div>
              <div class="irwf-flow-body">
                <div class="irwf-flow-title">
                  <?= htmlspecialchars($step['label'], ENT_QUOTES) ?>
                  <span class="irwf-flow-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                  <?php $stepEvCount = $evidenceCounts[$step['key']] ?? 0; ?>
                   <?php if ($stepEvCount > 0): ?>
                     <span class="irwf-evidence-badge" title="<?= $stepEvCount ?> evidence item(s)" onclick="chwfToggleStepActions(this, '<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>')"><?= $stepEvCount > 99 ? '99+' : $stepEvCount ?></span>
                   <?php endif; ?>
                 </div>
                  <?php if ($step['key'] === 'evidence_check' && !empty($stepEvidenceItems['evidence_check']) && ($stepClass === 'current' || $stepClass === 'completed')): ?>
                <div class="irwf-step-evidence">
                  <?php foreach ($stepEvidenceItems['evidence_check'] as $ev): ?>
                      <?php
                        $evImgSrc = null;
                        $assetBase = '/modules/compliance/assets/';
                        if (!empty($ev['image_path'])) {
                          $img = $ev['image_path'];
                          if (preg_match('/^[a-zA-Z]:\\|^\//', $img)) {
                            $img = str_replace('\\', '/', $img);
                            $serverRoot = 'C:/xampp/htdocs/hrms-capstone/';
                            if (stripos($img, $serverRoot) === 0) {
                              $img = substr($img, strlen($serverRoot));
                            }
                          }
                          if ($img) {
                            if (stripos($img, 'modules/compliance/assets/') === 0) {
                               $evImgSrc = $assetBase . substr($img, strlen('modules/compliance/assets/'));
                            } else {
                               $evImgSrc = $img;
                            }
                          }
                        }
                         if (!$evImgSrc && !empty($ev['notes'])) {
                           if (preg_match('/(modules\/compliance\/assets\/[^\s"\']+)/', $ev['notes'], $m)) {
                             $evImgSrc = $assetBase . substr($m[1], strlen('modules/compliance/assets/'));
                           }
                         }
                         if (!$evImgSrc && !empty($ev['evidence_item'])) {
                           $item = $ev['evidence_item'];
                           if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $item)) {
                             $evImgSrc = $assetBase . ltrim($item, '/');
                           } elseif (strpos($item, 'modules/compliance/') === 0) {
                             $evImgSrc = $assetBase . substr($item, strlen('modules/compliance/assets/'));
                           }
                         }
                      ?>
                    <div class="irwf-step-evidence-item">
                      <div class="irwf-step-evidence-name"><?= htmlspecialchars($ev['evidence_item'] ?? 'Evidence') ?></div>
                      <?php
                        $evNotes = trim((string)($ev['notes'] ?? ''));
                        $isWorkflowJson = false;
                        if ($evNotes !== '') {
                            $decodedNotes = json_decode($evNotes, true);
                            $isWorkflowJson = is_array($decodedNotes)
                                && isset($decodedNotes['stage'])
                                && isset($decodedNotes['progress']);
                        }
                      ?>
                      <?php if ($evNotes !== '' && !$isWorkflowJson): ?>
                        <div class="irwf-step-evidence-notes"><?= nl2br(htmlspecialchars($evNotes)) ?></div>
                      <?php endif; ?>
                       <?php if ($evImgSrc): ?>
                         <div class="irwf-step-evidence-img">
                           <a href="<?= htmlspecialchars($evImgSrc) ?>" target="_blank" rel="noopener noreferrer" title="View full image">
                             <img src="<?= htmlspecialchars($evImgSrc) ?>" alt="<?= htmlspecialchars($ev['evidence_item'] ?? 'Evidence') ?>" onerror="this.style.display='none'" />
                           </a>
                         </div>
                       <?php endif; ?>
                    </div>
                   <?php endforeach; ?>
                   </div>
                    <?php endif; ?>

                  <div class="irwf-step-actions" data-step-index="<?= $idx ?>" data-step-key="<?= htmlspecialchars($step['key'], ENT_QUOTES) ?>">
                  <button type="button" class="irwf-step-actions-toggle" aria-expanded="<?= ($idx === $currentStepIndex || $stepClass === 'completed' || ($step['key'] === 'employee_hearing' && empty($case['employee_response']))) ? 'true' : 'false' ?>">
                    <span class="irwf-step-actions-toggle-text">Actions</span>
                    <span class="irwf-step-actions-toggle-icon" aria-hidden="true">▸</span>
                  </button>
                    <div class="irwf-step-actions-panel" <?= ($idx === $currentStepIndex || $stepClass === 'completed' || !empty($evidenceCounts[$step['key']]) || $step['key'] === 'employee_hearing') ? '' : 'hidden' ?>>
                       <?php if ($idx === $currentStepIndex || ($step['key'] === 'employee_hearing' && $stepClass === 'completed')): ?>
                         <?php if ($step['key'] === 'evidence_check'): ?>

                           <button class="cc-btn primary" onclick="chSubmitAction('advance', this)">Advance</button>
                           <button class="cc-btn danger" onclick="chSubmitAction('close', this)">Close Complaint</button>
                         <?php elseif ($step['key'] === 'assign_officer'): ?>
                           <?php if (empty($case['assigned_to'])): ?>
                             <div class="chwf-officer-search" data-complaint-id="<?= (int)$case['id'] ?>">
                               <input type="text" class="chwf-officer-search-input" placeholder="Search employees by name, code, or position..." autocomplete="off">
                               <div class="chwf-officer-results"></div>
                               <div class="chwf-officer-status"></div>
                             </div>
                            <?php else: ?>
                             <button class="cc-btn primary" onclick="chSubmitAction('advance', this)">Advance</button>
                            <?php endif; ?>
                          <?php elseif ($step['key'] === 'nte_issued'): ?>
                            <button class="cc-btn" onclick="window.location.href='?page=notification-compose&mode=forward&notification_key=warning&to_recipient_email=<?= urlencode($respondentEmail) ?>&to_recipient_name=<?= urlencode($respondentName) ?>&template_code=nte&scenario=general&employee_id=<?= (int)($case['employee_id'] ?? 0) ?>&complaint_id=<?= (int)($case['id'] ?? 0) ?>&incident_date=<?= urlencode($case['incident_date'] ?? '') ?>&incident_time=<?= urlencode($case['incident_time'] ?? '') ?>&incident_location=<?= urlencode($case['location'] ?? '') ?>&policy_violated=&incident_description=<?= urlencode($case['description'] ?? '') ?>&hr_signatory=<?= rawurlencode($investigatorName ?: '') ?>'">Send Email NTE</button>
                            <button class="cc-btn primary" onclick="chSubmitAction('advance', this)">Advance</button>
                            <button class="cc-btn danger" onclick="chSubmitAction('close', this)">Close Complaint</button>
                            <?php elseif ($step['key'] === 'employee_hearing'): ?>
                              <?php $hearingDisabled = $stepClass === 'completed' ? 'disabled' : ''; ?>
                              <div class="irwf-hearing-grid">
                                <div class="irwf-hearing-actions">
                                  <button type="button" class="cc-btn primary" onclick="chShowInlineResponseForm()" <?= $hearingDisabled ?>>Record Response</button>
                                  <button type="button" class="cc-btn" onclick="chSubmitAction('advance', this)" <?= $hearingDisabled ?>>Advance to Review</button>
                                  <button type="button" class="cc-btn danger" onclick="chSubmitAction('close', this)" <?= $hearingDisabled ?>>Close Complaint</button>
                                </div>
                                <div class="irwf-hearing-response">
                                  <div id="chwfInlineResponseCard" class="irwf-response-panel">
                                    <div class="irwf-response-panel-header">
                                       <span class="irwf-response-panel-title">Respondent Explanation</span>
                                      <button type="button" class="irwf-response-panel-close" onclick="chHideInlineResponseForm()" aria-label="Close response form">&times;</button>
                                    </div>
                                    <div class="irwf-response-panel-body">
                                       <h3 style="display:none;">Recorded Respondent Explanation</h3>
                                      <?php if (!empty($case['employee_response'])): ?>
                                       <div class="irwf-response-saved">
                                         <div class="irwf-response-saved-meta">Recorded on <?= htmlspecialchars(date('M d, Y g:i A', strtotime($case['employee_response_date'] ?? 'now'))) ?></div>
                                         <div class="irwf-response-saved-text"><?= nl2br(htmlspecialchars($case['employee_response'])) ?></div>
                                       </div>
                                      <?php endif; ?>
                                      <div class="irwf-response-form" <?= !empty($case['employee_response']) ? 'style="display:none;"' : '' ?> id="chwfResponseForm">
                                         <label class="irwf-form-label" for="chwfInlineResponseText">Respondent Explanation</label>
                                          <textarea id="chwfInlineResponseText" placeholder="Enter or update the respondent explanation here..."><?= htmlspecialchars($case['employee_response'] ?? '') ?></textarea>
                                        <div class="irwf-response-form-actions">
                                           <button class="cc-btn primary" onclick="chSubmitInlineResponse()"><?= !empty($case['employee_response']) ? 'Update Respondent Explanation' : 'Submit Respondent Explanation' ?></button>
                                          <span id="chwfInlineResponseStatus" class="irwf-action-status"></span>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                         <?php elseif ($step['key'] === 'decision_made'): ?>
                            <div class="irwf-decision-actions">
                              <button class="cc-btn" data-decision-status="closed_warning_issued" onclick="chHandleDecision('closed_warning_issued', this)">Written Warning</button>
                              <button class="cc-btn" data-decision-status="closed_second_written_warning" onclick="chHandleDecision('closed_second_written_warning', this)">Second Written Warning</button>
                              <button class="cc-btn" data-decision-status="closed_final_written_warning" onclick="chHandleDecision('closed_final_written_warning', this)">Final Written Warning</button>
                              <button class="cc-btn danger" data-decision-status="closed_termination_recommended" onclick="chHandleDecision('closed_termination_recommended', this)">Termination</button>
                            </div>
                          <?php elseif ($step['key'] === 'termination_employee_reply'): ?>
                             <?php $loiSubmitted = !empty($case['termination_reply']) || !empty($case['termination_loi_submitted_at']); ?>
                             <?php if ($loiSubmitted): ?>
                               <div class="irwf-loi-saved">
                                 <div style="font-size:0.75rem; color:#6b7280; margin-bottom:4px;">Letter of Intent received on <?= htmlspecialchars(date('M d, Y g:i A', strtotime($case['termination_loi_submitted_at'] ?? 'now'))) ?></div>
                                 <div style="white-space:pre-wrap; word-break:break-word; font-size:0.85rem; color:#1b2430; border:1px solid #e4e8ee; padding:10px; background:#f6f8fb;"><?= nl2br(htmlspecialchars($case['termination_reply'] ?? '')) ?></div>
                                 <?php if (!empty($case['termination_loi_pdf_path'])): ?>
                                   <div style="margin-top:6px;"><a href="<?= htmlspecialchars($case['termination_loi_pdf_path']) ?>" target="_blank" class="cc-btn" style="text-decoration:none;">View Saved Letter</a></div>
                                 <?php endif; ?>
                               </div>
                             <?php else: ?>
                               <button class="cc-btn primary" onclick="chShowLOIForm()">Record Letter of Intent</button>
                             <?php endif; ?>
                             <button class="cc-btn" onclick="chSubmitAction('advance', this)" <?= $loiSubmitted ? '' : 'disabled' ?> title="<?= $loiSubmitted ? '' : 'Record Letter of Intent first' ?>">Advance to Review</button>
                             <button class="cc-btn danger" onclick="chSubmitAction('close', this)">Close Complaint</button>
                         <?php elseif ($step['key'] === 'termination_review'): ?>
                            <?php $hasLOI = !empty($case['termination_reply']); ?>
                            <?php if (!$hasLOI): ?>
                              <span class="irwf-action-status" style="color:#a3272a;">Letter of Intent required before review.</span>
                            <?php else: ?>
                              <div class="irwf-loi-saved" style="margin-bottom:8px;">
                                <div style="font-size:0.75rem; color:#6b7280; margin-bottom:4px;">Letter of Intent submitted on <?= htmlspecialchars(date('M d, Y g:i A', strtotime($case['termination_loi_submitted_at'] ?? 'now'))) ?></div>
                                <div style="white-space:pre-wrap; word-break:break-word; font-size:0.85rem; color:#1b2430; border:1px solid #e4e8ee; padding:10px; background:#f6f8fb; max-height:120px; overflow:auto;"><?= nl2br(htmlspecialchars($case['termination_reply'] ?? '')) ?></div>
                              </div>
                              <button class="cc-btn primary" onclick="chShowReconsiderationEmailModal()">Considered</button>
                              <button class="cc-btn danger" onclick="chShowRejectionWarningModal()">Rejected</button>
                            <?php endif; ?>
                         <?php else: ?>
                          <button class="cc-btn primary" onclick="chSubmitAction('advance', this)">Advance</button>
                          <button class="cc-btn danger" onclick="chSubmitAction('close', this)">Close Complaint</button>
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

          <div id="chwfLOICard" style="display:none;">
            <div class="irwf-card">
              <h3>Record Letter of Intent</h3>
              <div class="irwf-response-form">
                <div style="margin-bottom:8px;">
                  <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Received From</label>
                  <input type="text" id="chwfLOIFrom" value="<?= htmlspecialchars($respondentName ?: $respondentEmail ?: '') ?>" readonly style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; background:#f6f8fb; font-size:0.85rem; color:#3b4252;" />
                </div>
                <div style="margin-bottom:8px;">
                  <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Notes / Content</label>
                  <textarea id="chwfLOIText" rows="5" placeholder="Paste or summarize the Letter of Intent content received from the respondent..."></textarea>
                </div>
                <div style="margin-bottom:8px;">
                  <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Attach PDF / Screenshot Copy</label>
                  <input type="file" id="chwfLOIFile" accept=".pdf,image/*" style="width:100%; padding:6px 0; font-size:0.85rem; color:#3b4252;" />
                </div>
                 <div style="margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                   <button class="cc-btn primary" onclick="chSubmitLOI()">Save Letter of Intent</button>
                   <button class="cc-btn" onclick="chHideLOIForm()">Cancel</button>
                   <span id="chwfLOIStatus" class="irwf-action-status"></span>
                 </div>
              </div>
            </div>
          </div>

         <div id="chwfReconsiderationEmailModal" class="lc-modal-backdrop" onclick="if(event.target===this)chwfCloseModal('chwfReconsiderationEmailModal')">
           <div class="lc-modal" style="max-width:720px;">
             <div class="lc-modal-header">
               <div class="lc-modal-title">Send Reconsideration Email</div>
               <button type="button" class="lc-modal-close" onclick="chwfCloseModal('chwfReconsiderationEmailModal')">&times;</button>
             </div>
             <div class="lc-modal-body">
               <div style="margin-bottom:12px;">
                 <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">To</label>
                 <input type="text" id="chwfReconsiderationEmailTo" style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; font-size:0.85rem; color:#3b4252;" />
               </div>
               <div style="margin-bottom:12px;">
                 <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Subject</label>
                 <input type="text" id="chwfReconsiderationEmailSubject" style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; font-size:0.85rem; color:#3b4252;" />
               </div>
               <div style="margin-bottom:12px;">
                 <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Message</label>
                 <textarea id="chwfReconsiderationEmailBody" rows="10" style="width:100%; padding:10px 12px; border:1px solid #e4e8ee; font-size:0.85rem; color:#3b4252; font-family:inherit; resize:vertical;"></textarea>
               </div>
               <div id="chwfReconsiderationEmailStatus" class="irwf-action-status" style="margin-bottom:8px;"></div>
             </div>
             <div class="lc-modal-body" style="border-top:1px solid var(--hairline, #e4e8ee); padding-top:12px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
               <button type="button" class="cc-btn" onclick="chwfCloseModal('chwfReconsiderationEmailModal')">Cancel</button>
               <button type="button" class="cc-btn primary" onclick="chSendReconsiderationEmail()">Send Email & Close Case</button>
             </div>
           </div>
         </div>

         <div id="chwfRejectionWarningModal" class="lc-modal-backdrop" onclick="if(event.target===this)chwfCloseModal('chwfRejectionWarningModal')">
           <div class="lc-modal" style="max-width:520px;">
             <div class="lc-modal-header">
               <div class="lc-modal-title" style="color:#a3272a;">Confirm Rejection</div>
               <button type="button" class="lc-modal-close" onclick="chwfCloseModal('chwfRejectionWarningModal')">&times;</button>
             </div>
             <div class="lc-modal-body">
               <p style="font-size:0.85rem; color:#1b2430; margin-bottom:12px;">You are about to <strong>reject</strong> the Letter of Intent. This action will return the case to the decision stage.</p>
               <p style="font-size:0.8rem; color:#a3272a; margin-bottom:12px;">This is a <strong>final, firm decision</strong>. Please confirm that you wish to proceed with the rejection.</p>
               <div style="margin-bottom:12px;">
                 <label style="font-size:0.8rem; font-weight:600; color:#1b2430; display:block; margin-bottom:4px;">Rejection Notes (optional)</label>
                 <textarea id="chwfRejectionNotes" rows="3" placeholder="Add any notes for the rejection..." style="width:100%; padding:8px 10px; border:1px solid #e4e8ee; font-size:0.85rem; color:#3b4252; font-family:inherit; resize:vertical;"></textarea>
               </div>
               <div id="chwfRejectionWarningStatus" class="irwf-action-status" style="margin-bottom:8px;"></div>
             </div>
             <div class="lc-modal-body" style="border-top:1px solid var(--hairline, #e4e8ee); padding-top:12px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
               <button type="button" class="cc-btn" onclick="chwfCloseModal('chwfRejectionWarningModal')">Cancel</button>
               <button type="button" class="cc-btn danger" onclick="chSubmitTerminationReviewDecision('rejected')">Confirm Rejection</button>
             </div>
           </div>
         </div>
      </div>

     <div class="irwf-sidebar">
       <div class="irwf-card">
         <h3>Respondent Profile</h3>
          <div class="irwf-grid-stack">
           <div class="irwf-field">
             <div class="irwf-label">Name</div>
             <div class="irwf-value"><?= htmlspecialchars($respondentProfile['first_name'] ?? '') ?> <?= htmlspecialchars($respondentProfile['middle_name'] ?? '') ?> <?= htmlspecialchars($respondentProfile['last_name'] ?? '') ?></div>
           </div>
           <div class="irwf-field">
             <div class="irwf-label">Employee No.</div>
             <div class="irwf-value mono"><?= htmlspecialchars($respondentProfile['employee_code'] ?? '—') ?></div>
           </div>
           <div class="irwf-field">
             <div class="irwf-label">Email</div>
             <div class="irwf-value"><?= htmlspecialchars($respondentProfile['email'] ?? '—') ?></div>
           </div>
           <div class="irwf-field">
             <div class="irwf-label">Department</div>
             <div class="irwf-value"><?= htmlspecialchars($respondentProfile['department_name'] ?? '—') ?></div>
           </div>
             <div class="irwf-field">
               <div class="irwf-label">Position</div>
               <div class="irwf-value"><?= htmlspecialchars($respondentProfile['position_name'] ?? '—') ?></div>
             </div>
           </div>
         </div>

          <div class="irwf-card">
            <h3>Case History</h3>
            <div class="irwf-grid-stack">
              <div class="irwf-field">
                <div class="irwf-label">Total Cases</div>
                <div class="irwf-value mono"><?= (int)($respondentCaseCounts['total'] ?? 0) ?></div>
              </div>
              <div class="irwf-field">
                <div class="irwf-label">1st Warning</div>
                <div class="irwf-value mono"><?= (int)($respondentCaseCounts['warning_first'] ?? 0) ?></div>
              </div>
              <div class="irwf-field">
                <div class="irwf-label">2nd Warning</div>
                <div class="irwf-value mono"><?= (int)($respondentCaseCounts['warning_second'] ?? 0) ?></div>
              </div>
              <div class="irwf-field">
                <div class="irwf-label">Final Warning</div>
                <div class="irwf-value mono"><?= (int)($respondentCaseCounts['warning_final'] ?? 0) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>

<script>
window.CHWF_CONFIG = {
    complaintId: <?= (int)$case['id'] ?>,
    currentStepKey: <?= json_encode($targetStep) ?>,
    status: <?= json_encode($case['status'] ?? 'under_initial_review') ?>,
    investigatorName: <?= json_encode($investigatorName ?: '') ?>,
    employeeName: <?= json_encode($employeeName ?: '') ?>,
    employeeId: <?= json_encode($case['employee_id'] ?? '') ?>,
    respondentEmployeeId: <?= json_encode($case['respondent_employee_id'] ?? '') ?>,
    respondentName: <?= json_encode($respondentName ?: '') ?>,
    respondentEmail: <?= json_encode($respondentEmail ?: '') ?>,
    caseNumber: <?= json_encode('CMP-' . str_pad($case['id'], 5, '0', STR_PAD_LEFT)) ?>,
    complaintType: <?= json_encode($case['type'] ?? '') ?>,
    severity: <?= json_encode($case['severity'] ?? '') ?>,
    description: <?= json_encode($case['description'] ?? '') ?>,
    assignedTo: <?= json_encode($case['assigned_to'] ?? '') ?>,
    assetBaseUrl: '/modules/compliance/assets/',
    assignableOfficers: <?= json_encode(array_values(array_map(function($o) {
        return ['id' => (int)$o['employee_id'], 'name' => $o['full_name'], 'code' => $o['employee_code']];
    }, $assignableOfficers)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    terminationReply: <?= json_encode($case['termination_reply'] ?? '') ?>,
    terminationLoiSubmittedAt: <?= json_encode($case['termination_loi_submitted_at'] ?? '') ?>,
    terminationLoiPdfPath: <?= json_encode($case['termination_loi_pdf_path'] ?? '') ?>,
};
</script>
<script src="js/pages/complaint-workflow-interactive.js?v=<?= time() ?>"></script>

<style>
  .irwf-page { padding: 0; font-family: Arial, serif; }
  .irwf-dashboard { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
  .irwf-main { min-width: 0; }
  .irwf-sidebar { width: 320px; flex-shrink: 0; }
  .irwf-card { background: #fff; border: 1px solid #e4e8ee; border-radius: 0; padding: 16px; margin-bottom: 16px; }
  .irwf-card h3 { margin: 0 0 12px; font-size: 0.95rem; font-weight: 400; color: #1b2430; }
  .irwf-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .irwf-grid-stack { display: grid; grid-template-columns: 1fr; gap: 10px; }
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
  .irwf-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 0; font-size: 0.7rem; font-weight: 400; }
  .irwf-badge.severity-critical { background: rgba(178,58,58,.08); color: #b23a3a; }
  .irwf-badge.severity-high { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-badge.severity-medium { background: rgba(43,122,142,.08); color: #2b7a8e; }
  .irwf-badge.severity-low { background: rgba(107,125,158,.08); color: #6b7d9e; }
  .irwf-badge.status-submitted { background: rgba(107,125,158,.08); color: #6b7d9e; }
  .irwf-badge.status-under_initial_review { background: rgba(59,130,196,.08); color: #3b82c4; }
  .irwf-badge.status-under_investigation { background: rgba(107,79,158,.08); color: #6b4f9e; }
  .irwf-badge.status-for_decision { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-badge.status-pending_employee_response { background: rgba(201,127,29,.08); color: #c97f1d; }
  .irwf-badge.status-closed { background: rgba(31,122,92,.1); color: #145a42; }
  .irwf-badge.status-closed_no_violation { background: rgba(31,122,92,.1); color: #145a42; }
  .irwf-badge.status-closed_warning_issued { background: rgba(31,122,92,.1); color: #145a42; }
  .irwf-badge.status-closed_suspension { background: rgba(31,122,92,.1); color: #145a42; }
  .irwf-badge.status-closed_termination_recommended { background: rgba(31,122,92,.1); color: #145a42; }
  .irwf-badge.status-closed_resolved { background: rgba(31,122,92,.1); color: #145a42; }

  .irwf-decision { border-left: 1px solid #c97f1d; padding-left: 12px; margin: 6px 0; }
  .irwf-decision-title { font-size: 0.75rem; color: #c97f1d; font-weight: 400; }
  .irwf-decision-options { display: flex; gap: 8px; margin-top: 4px; flex-wrap: wrap; }
  .irwf-decision-opt { font-size: 0.75rem; color: #6b7280; font-weight: 400; }
  .irwf-decision-opt.active { color: #1f7a5c; font-weight: 600; }
  .irwf-section-title { font-size: 0.9rem; color: #1b2430; margin-bottom: 12px; font-weight: 400; }

  .cc-btn { font-size: 0.72rem; font-weight: 400; padding: 4px 10px; border-radius: 0; border: 1px solid #e4e8ee; background: #fff; color: #5b6472; cursor: pointer; text-decoration: none; white-space: nowrap; transition: background 150ms ease, border-color 150ms ease, color 150ms ease; display: inline-flex; align-items: center; gap: 6px; height: 32px; }
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
  .irwf-step-actions-panel > .chwf-officer-search {
     flex: 1 1 100%;
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

  .chwf-assign-select { box-sizing:border-box; padding:8px 12px; border:1px solid #e4e8ee; border-radius:6px; font-size:0.85rem; color:#1b2430; background:#fff; height:38px; transition: border-color 0.15s ease, box-shadow 0.15s ease; }

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

  .lc-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1100;
    opacity: 0;
    pointer-events: none;
    transition: opacity .15s ease;
  }
  .lc-modal-backdrop.open {
    opacity: 1;
    pointer-events: auto;
  }
  .lc-modal {
    background: #fff;
    border: 1px solid #e4e8ee;
    border-radius: 10px;
    box-shadow: 0 12px 40px rgba(0,0,0,.12);
    width: calc(100% - 32px);
    max-width: 520px;
    max-height: calc(100vh - 32px);
    overflow: auto;
  }
  .lc-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #e4e8ee;
  }
  .lc-modal-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1b2430;
  }
  .lc-modal-close {
    background: transparent;
    border: none;
    font-size: 1.4rem;
    line-height: 1;
    color: #6b7280;
    cursor: pointer;
  }
  .lc-modal-body {
    padding: 14px 16px;
  }

  .chwf-officer-search { position: relative; margin-bottom: 8px; }
  .chwf-officer-search-input { box-sizing: border-box; width: 100%; padding: 8px 12px; border: 1px solid #e4e8ee; border-radius: 6px; font-size: 0.85rem; color: #1b2430; background: #fff; height: 38px; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
  .chwf-officer-results { position: absolute; left: 0; right: 0; top: calc(100% + 6px); border: 1px solid #e4e8ee; border-radius: 8px; max-height: 220px; overflow-y: auto; background: #fff; z-index: 20; display: none; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.10); }
  .chwf-officer-result-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f1f4f9; font-size: 0.82rem; color: #1b2430; transition: background 0.1s ease; }
  .chwf-officer-result-item:hover { background: #f3f6fb; }
  .chwf-officer-result-item.is-selected { background: #eef2ff; }
  .chwf-officer-result-item:focus-visible { outline: 2px solid #4f6ef7; outline-offset: -2px; }
  .chwf-officer-result-item:last-child { border-bottom: none; border-radius: 0 0 8px 8px; }
  .chwf-result-name { font-weight: 600; font-size: 0.86rem; color: var(--text-900, #1b2430); line-height: 1.3; }
  .chwf-result-id { font-size: 0.76rem; color: var(--text-500, #64748b); line-height: 1.3; margin-top: 1px; }
  .chwf-result-details { font-size: 0.76rem; color: var(--text-500, #64748b); line-height: 1.3; margin-top: 1px; }
  .chwf-officer-status { font-size: 0.75rem; color: #6b7280; margin-top: 4px; }
  .chwf-officer-status.error { color: #a3272a; }
  .chwf-officer-status.success { color: #1f7a5c; }

  .irwf-evidence-loading, .irwf-evidence-empty { text-align: center; padding: 20px 0; color: #6b7280; }
  .irwf-evidence-list { display: flex; flex-direction: column; gap: 8px; }
  .irwf-evidence-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid #e4e8ee; background: transparent; }
  .irwf-evidence-details { flex: 1 1 auto; min-width: 0; }
  .irwf-evidence-name { color: #1b2430; font-size: 0.85rem; word-break: break-word; font-weight: 600; }
  .irwf-evidence-name a { color: #3b82c4; text-decoration: none; }
  .irwf-evidence-name a:hover { text-decoration: underline; }
  .irwf-evidence-desc { font-size: 0.8rem; color: #3b4252; margin-top: 3px; }
  .irwf-evidence-meta { font-size: 0.7rem; color: #6b7280; margin-top: 3px; }

  .irwf-evidence-item img { display: block; }

  .irwf-evidence-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    margin-left: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    color: #fff;
    background: #2563eb;
    border-radius: 999px;
    cursor: pointer;
    vertical-align: middle;
  }
  .irwf-evidence-badge:hover { background: #1d4ed8; }

   .irwf-workflow-summary { margin-bottom: 12px; padding: 10px 12px; border: 1px solid #e4e8ee; background: rgba(31,122,92,.02); }
    .irwf-workflow-summary-label { font-size: 0.7rem; color: #6b7280; margin-bottom: 2px; }
    .irwf-workflow-summary-value { font-size: 0.82rem; color: #1b2430; line-height: 1.45; }

  .irwf-response-form textarea {
    box-sizing: border-box;
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e4e8ee;
    border-radius: 0;
    font-size: 0.85rem;
    color: #1b2430;
    background: #fff;
    font-family: inherit;
    resize: vertical;
    flex: 1;
    min-height: 112px;
  }
  .irwf-response-form textarea:focus {
    outline: none;
    border-color: #3b82c4;
    box-shadow: 0 0 0 2px rgba(59,130,196,.15);
  }

  .irwf-actions-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    background: #fafbfc;
    border: 1px solid #e4e8ee;
    border-radius: 6px;
    margin-top: 10px;
  }
  .irwf-actions-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .irwf-step-actions-panel {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    align-items: stretch;
  }

  .irwf-hearing-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 10px;
    align-items: stretch;
    width: 100%;
  }
  .irwf-hearing-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    height: 100%;
    align-self: stretch;
  }
  .irwf-hearing-actions .cc-btn {
    width: 100%;
    justify-content: center;
  }
  .irwf-hearing-response {
    min-width: 0;
    width: 100%;
    height: 100%;
    align-self: stretch;
    display: flex;
  }
  .irwf-hearing-actions .irwf-action-status {
    margin-top: 4px;
    text-align: center;
  }

  .irwf-response-panel {
    border: 1px solid #e4e8ee;
    border-radius: 6px;
    background: #fff;
    overflow: hidden;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  .irwf-response-panel-body {
    padding: 12px;
    width: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .irwf-response-form textarea {
    border-radius: 4px;
    width: 100%;
  }
  .irwf-response-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid #e4e8ee;
    background: #fafbfc;
  }
  .irwf-response-panel-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #1b2430;
  }
  .irwf-response-panel-close {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 150ms ease, color 150ms ease;
  }
  .irwf-response-panel-close:hover {
    background: #f3f5f9;
    color: #1b2430;
  }
  .irwf-response-panel-body {
    padding: 12px;
  }
  .irwf-response-saved {
    margin-bottom: 10px;
    padding: 10px;
    background: #f6f8fb;
    border: 1px solid #e4e8ee;
  }
  .irwf-response-saved-meta {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .irwf-response-saved-text {
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.85rem;
    color: #1b2430;
    line-height: 1.5;
  }
  .irwf-form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 400;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .irwf-response-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
  }
  .irwf-response-form textarea {
    border-radius: 4px;
    width: 100%;
  }
  .irwf-response-form-actions {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .irwf-flow-step .irwf-step-evidence {
    margin-top: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .irwf-flow-step .irwf-step-evidence-item {
    padding: 6px 8px;
    border: 1px solid #e4e8ee;
    background: #fff;
  }
  .irwf-flow-step .irwf-step-evidence-name {
    font-size: 0.72rem;
    font-weight: 600;
    color: #1b2430;
  }
  .irwf-flow-step .irwf-step-evidence-notes {
    font-size: 0.7rem;
    color: #3b4252;
    margin-top: 2px;
    line-height: 1.35;
  }
  .irwf-flow-step .irwf-step-evidence-img {
    margin-top: 4px;
  }
  .irwf-flow-step .irwf-step-evidence-img img {
    max-width: 100%;
    max-height: 120px;
    display: block;
    border: 1px solid #e4e8ee;
    background: #f3f5f9;
    cursor: pointer;
  }
  .irwf-flow-step .irwf-step-evidence-img img:hover {
    opacity: 0.85;
  }

  .irwf-sidebar .irwf-table-wrap {
    max-height: 420px;
    overflow-y: auto;
    border: 1px solid #e4e8ee;
    border-radius: 6px;
    background: #fff;
  }
  .irwf-sidebar .irwf-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
  }
  .irwf-sidebar .irwf-table th {
    text-align: left;
    padding: 8px 10px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    border-bottom: 1px solid #e4e8ee;
    background: #fafbfc;
    position: sticky;
    top: 0;
    z-index: 1;
  }
  .irwf-sidebar .irwf-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f4f9;
    vertical-align: middle;
  }
  .irwf-sidebar .irwf-table tr:last-child td {
    border-bottom: none;
  }
  .irwf-sidebar .irwf-table tr:hover td {
    background: #fafbfc;
  }
  .irwf-sidebar .irwf-empty {
    padding: 16px;
    text-align: center;
    color: #6b7280;
    font-size: 0.8rem;
  }
  .irwf-loi-saved {
    margin-bottom: 10px;
    padding: 10px;
    background: #f6f8fb;
    border: 1px solid #e4e8ee;
  }
  .irwf-loi-saved a {
    font-size: 0.8rem;
    color: #3b82c4;
    text-decoration: none;
  }
  .irwf-loi-saved a:hover {
    text-decoration: underline;
  }
 </style>
<?php ob_end_flush(); ?>

