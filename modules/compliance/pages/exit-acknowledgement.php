<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

$pageTitle = 'Exit Acknowledgement';
$activeGroup = 'Exit Acknowledgement';
$activePage = 'exit-acknowledgement';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    $database = new Database();

    if ($database->hasConnectionError()) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $db = $database->getConnection();
}

require_once __DIR__ . '/../classes/ExitManagementController.php';
$exitController = new ExitManagementController($db);

$exitId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($exitId <= 0) {
    header('Location: ?page=exit-documents&msg=error|Invalid exit ID');
    exit;
}

$exit = $exitController->getExitRequestById($exitId);
if (!$exit) {
    header('Location: ?page=exit-documents&msg=error|Exit record not found');
    exit;
}

$employeeInfo = [];
$employeeNo = 'N/A';
$employmentType = 'N/A';
$hireDateDisplay = '—';
if (!empty($exit['employee_id'])) {
    try {
        $stmt = $db->prepare('SELECT e.employee_code, e.employment_type, e.hire_date, e.email, e.employment_status, u.last_login FROM em_employees e LEFT JOIN user_account u ON u.employee_id = e.employee_id WHERE e.employee_id = :eid LIMIT 1');
        $stmt->execute([':eid' => $exit['employee_id']]);
        $employeeInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $employeeNo = $employeeInfo['employee_code'] ?? ('EMP' . str_pad((string)($exit['employee_id'] ?? 0), 4, '0', STR_PAD_LEFT));
        $employmentType = $employeeInfo['employment_type'] ?? 'N/A';
        if (!empty($employeeInfo['hire_date'])) {
            $hireDateDisplay = date('M d, Y', strtotime($employeeInfo['hire_date']));
        }
    } catch (Exception $e) { $employeeInfo = []; }
}

$approvals      = $exitController->getExitApprovals($exitId);
$clearanceItems = $exitController->getClearanceItems($exitId);
$vacantPositions = $exitController->getVacantPositions();
$vacantPosition  = null;
foreach ($vacantPositions as $vp) {
    if ($vp['exit_request_id'] == $exitId) { $vacantPosition = $vp; break; }
}

$jobPosting = null;
try {
    $stmt = $db->prepare('SELECT * FROM lc_job_posting_requests WHERE exit_request_id = :eid ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':eid' => $exitId]);
    $jobPosting = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Exception $e) { $jobPosting = null; }

$positionJobPosts = [];
try {
    $stmt = $db->prepare('SELECT * FROM lc_job_posting_requests WHERE previous_position = :pos AND status NOT IN ("Filled", "Archived") ORDER BY created_at DESC');
    $stmt->execute([':pos' => $exit['position_name'] ?? '']);
    $positionJobPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $positionJobPosts = []; }

$openRecruitments = [];
try {
    $stmt = $db->prepare('SELECT * FROM lc_recruitment WHERE position = :pos AND status = "Open" ORDER BY created_at DESC');
    $stmt->execute([':pos' => $exit['position_name'] ?? '']);
    $openRecruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $openRecruitments = []; }

$clearanceItems = $exitController->getClearanceItems($exitId);
$clearanceMap = [];
foreach ($clearanceItems as $item) {
    $clearanceMap[$item['item_name']] = (bool)$item['is_completed'];
}

$employeeId = $exit['employee_id'] ?? 0;
$department  = $exit['department_name'] ?? '';

$hasOpenIncident = false;
$hasOpenRiskFlag = false;
$hasInvestigatingRiskFlag = false;
$hasPendingComplianceTask = false;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('resolved', 'closed') AND assigned_to = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $hasOpenIncident = ((int)$stmt->fetchColumn()) > 0;
} catch (Exception $e) {}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lc_risk_flags WHERE status IN ('open', 'investigating') AND employee_id = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $hasOpenRiskFlag = ((int)$stmt->fetchColumn()) > 0;
} catch (Exception $e) {}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lc_risk_flags WHERE status = 'investigating' AND employee_id = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $hasInvestigatingRiskFlag = ((int)$stmt->fetchColumn()) > 0;
} catch (Exception $e) {}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM lc_compliance_tasks WHERE status = 'Pending' AND employee_id = :eid");
    $stmt->execute([':eid' => $employeeId]);
    $hasPendingComplianceTask = ((int)$stmt->fetchColumn()) > 0;
} catch (Exception $e) {}

$complianceItems = [
    ['label' => 'Preclearance Desk Person', 'status' => (!empty($exit['preclearance_desk_person']) && (int)$exit['preclearance_desk_person'] > 0) ? 'completed' : 'pending'],
    ['label' => 'HR Approved', 'status' => (!empty($exit['hr_approved_by']) && (int)$exit['hr_approved_by'] > 0) || !empty($exit['hr_approved_at']) ? 'completed' : 'pending'],
    ['label' => 'Legal Approved', 'status' => !empty($exit['legal_approved_at']) ? 'completed' : 'pending'],
];

$allComplianceClear = true;
foreach ($complianceItems as $item) {
    if ($item['status'] === 'pending') {
        $allComplianceClear = false;
        break;
    }
}

$timeline = [];
if (!empty($exit['created_at'])) {
    $timeline[] = [
        'activity_type' => 'Exit Request Filed',
        'description' => 'Exit Management completed resignation process.',
        'activity_date' => $exit['created_at'],
        'status' => 'Filed'
    ];
}
if (!empty($exit['approved_at'])) {
    $timeline[] = [
        'activity_type' => 'Exit Approved',
        'description' => 'All em_departments approved the exit request.',
        'activity_date' => $exit['approved_at'],
        'status' => 'Approved'
    ];
}
if (!empty($exit['confirmed_at'])) {
    $timeline[] = [
        'activity_type' => 'Compliance Verification Completed',
        'description' => 'Legal & Compliance verified all requirements.',
        'activity_date' => $exit['confirmed_at'],
        'status' => 'Confirmed'
    ];
    $timeline[] = [
        'activity_type' => 'Exit Acknowledged',
        'description' => 'Exit record officially acknowledged by Legal & Compliance.',
        'activity_date' => $exit['confirmed_at'],
        'status' => 'Acknowledged'
    ];
}
if ($exit['recruitment_status'] === 'Notified' || $exit['recruitment_status'] === 'Updated') {
    $timeline[] = [
        'activity_type' => 'Recruitment Notified',
        'description' => 'Recruitment & Onboarding notified of job vacancy.',
        'activity_date' => $exit['updated_at'] ?? date('Y-m-d H:i:s'),
        'status' => 'Notified'
    ];
}
if ($exit['archived']) {
    $timeline[] = [
        'activity_type' => 'Record Archived',
        'description' => 'Exit record included in Audit & Reporting.',
        'activity_date' => $exit['archived_at'] ?? $exit['updated_at'],
        'status' => 'Archived'
    ];
}

$actionMessage = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $officerName = $user['name'] ?? 'Legal Officer';

    if (isset($_POST['verify_compliance'])) {
        $remarks = trim($_POST['legal_remarks'] ?? '');
        if ($exitController->updateExitLegalStatus($exitId, 'Confirmed', $officerName, $remarks)) {
            $actionMessage = 'Compliance verified successfully.';
            $actionType = 'success';
        }
    } elseif (isset($_POST['acknowledge_exit'])) {
        $remarks = trim($_POST['legal_remarks'] ?? '');
        if ($exitController->updateExitLegalStatus($exitId, 'Confirmed', $officerName, $remarks)) {
            $exitController->updateExitRecruitmentStatus($exitId, 'Notified');
            $actionMessage = 'Exit acknowledged successfully. Workforce team has been notified.';
            $actionType = 'success';
        }
    } elseif (isset($_POST['return_exit'])) {
        $remarks = trim($_POST['legal_remarks'] ?? '');
        if ($exitController->updateExitLegalStatus($exitId, 'Returned', $officerName, $remarks)) {
            $actionMessage = 'Exit record returned to Exit Management for clarification.';
            $actionType = 'warning';
        }
    }

    if ($actionMessage) {
        $separator = strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?';
        header('Location: ' . $_SERVER['REQUEST_URI'] . $separator . 'msg=' . urlencode($actionType . '|' . $actionMessage));
        exit;
    }
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

function ea_legal_class(string $s): string {
    $s = strtolower($s);
    if ($s === 'confirmed') return 'acknowledged';
    if ($s === 'returned') return 'returned';
    return 'pending';
}
function ea_legal_label(string $s): string {
    $map = [
        'pending'    => 'Pending Verification',
        'confirmed'  => 'Acknowledged',
        'returned'   => 'Returned',
    ];
    return $map[strtolower($s)] ?? ucfirst($s);
}

$isExitAcknowledged = !empty($exit['confirmed_at']) || strtolower($exit['legal_status'] ?? '') === 'confirmed';
$legalStatus = strtolower($exit['legal_status'] ?? 'pending');
?>
<style>
:root {
    --cw-bg: #f4f5f7;
    --cw-card: #ffffff;
    --cw-border: #e1e4e8;
    --cw-border-light: #e8eaed;
    --cw-text: #2f3439;
    --cw-muted: #737b83;
    --cw-primary: #2f6fa8;
    --cw-primary-hover: #285f91;
    --cw-success: #3f8053;
    --cw-danger: #b34b4b;
    --cw-radius: 6px;
}

.cw-module {
    background: var(--cw-bg);
    padding: 0;
}

.cw-flash {
    padding: 10px 12px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 600;
    margin-top: 10px;
    display: none;
    align-items: center;
    gap: 8px;
    line-height: 1.4;
}
.cw-flash:first-child { margin-top: 0; }
.cw-flash.success {
    display: flex;
    background: #f6fbf7;
    color: var(--cw-success);
    border: 1px solid #c8e6d0;
}
.cw-flash.error {
    display: block;
    background: #fdf6f6;
    color: var(--cw-danger);
    border: 1px solid #f5c6c6;
}

.cw-row {
    display: grid;
    grid-template-columns: 31% 1fr;
    gap: 16px;
    align-items: start;
}
.cw-col-main { min-width: 0; }
.cw-col-side { min-width: 0; }
@media (max-width: 768px) {
    .cw-row { grid-template-columns: 1fr; }
    .cw-col-side { position: static; width: auto; }
}

.cw-card {
    background: var(--cw-card);
    border: 1px solid var(--cw-border);
    border-radius: var(--cw-radius);
    box-shadow: none;
    overflow: hidden;
    margin-bottom: 14px;
}
.cw-card-head {
    padding: 10px 15px;
    border-bottom: 1px solid var(--cw-border-light);
    display: flex;
    align-items: center;
    gap: 10px;
}
.cw-card-head h3 {
    margin: 0;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 600;
    color: var(--cw-text);
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: Arial, serif;
}
.cw-card-body {
    padding: 12px 15px;
}

.cw-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px 15px;
}
.cw-info-grid-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
@media (max-width: 600px) {
    .cw-info-grid, .cw-info-grid-3 { grid-template-columns: 1fr; }
}
.cw-info-item label {
    display: block;
    margin-bottom: 2px;
    font-size: 9.5px;
    line-height: 1.2;
    font-weight: 500;
    color: var(--cw-muted);
    opacity: 0.85;
}
.cw-info-item div {
    font-size: 11px;
    line-height: 1.3;
    font-weight: 400;
    color: var(--cw-text);
    opacity: 0.92;
}
.cw-info-item {
    border-bottom: 1px solid var(--cw-border-light);
    padding-bottom: 8px;
}
.cw-info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.cw-info-item:nth-child(odd) {
    border-right: 1px solid var(--cw-border-light);
    padding-right: 12px;
}
.cw-info-item:last-child:nth-child(odd) {
    border-right: none;
    padding-right: 0;
}
.cw-case-desc { margin-top:12px; }
.cw-case-desc label { display:block; font-size:0.72rem; font-weight:600; color:#6b7280; margin-bottom:6px; }
.cw-case-desc p { margin:0; font-size:0.84rem; color:#374151; line-height:1.5; white-space:pre-wrap; }

.cw-stamp { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 4px; white-space: nowrap; }
.cw-stamp-compliant { background: #f6fbf7; color: var(--cw-success); border: 1px solid #c8e6d0; }
.cw-stamp-pending { background: #fffbf0; color: #8a6d1a; border: 1px solid #f0e4a8; }
.cw-stamp-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

.cw-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 33px;
    padding: 6px 11px;
    border: 1px solid var(--cw-border);
    border-radius: 4px;
    background: var(--cw-card);
    color: var(--cw-text);
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    white-space: nowrap;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
    text-decoration: none;
}
.cw-btn:hover {
    border-color: var(--cw-primary);
    color: var(--cw-primary);
}
.cw-btn:active {
    background: #f3f4f6;
}
.cw-btn:focus-visible {
    outline: 2px solid var(--cw-primary);
    outline-offset: 2px;
}
.cw-btn.primary {
    background: var(--cw-primary);
    border-color: var(--cw-primary);
    color: #ffffff;
}
.cw-btn.primary:hover {
    background: var(--cw-primary-hover);
    border-color: var(--cw-primary-hover);
    color: #ffffff;
}
.cw-btn.danger {
    background: #fdf6f6;
    border-color: #f5c6c6;
    color: #991b1b;
}
.cw-btn.danger:hover {
    background: #fef2f2;
    border-color: #f87171;
    color: #991b1b;
}

.cw-action-status { display:inline-block; font-size:.78rem; font-weight:400; margin-left:8px; vertical-align:middle; }
.cw-action-status.success { color:#065f46; }
.cw-action-status.error { color:#991b1b; }

.cw-profile { text-align:center; padding:10px 0 12px; border-bottom:1px solid var(--cw-border-light); margin-bottom:10px; }
.cw-profile-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #eef1f5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 800;
    color: #5b6472;
    margin-bottom: 6px;
}
.cw-profile-name {
    margin-top: 6px;
    font-size: 15px;
    line-height: 1.3;
    font-weight: 600;
    color: var(--cw-text);
    word-break: break-word;
}
.cw-profile-no {
    margin-top: 2px;
    font-size: 11px;
    color: var(--cw-muted);
}
.cw-profile-meta {
    margin-top: 6px;
    font-size: 11.5px;
    line-height: 1.5;
    color: var(--cw-muted);
}
.cw-profile-status {
    margin-top: 8px;
}
.cw-profile-stats {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--cw-border-light);
}
.cw-profile-stat {
    margin-bottom: 10px;
}
.cw-profile-stat:last-child { margin-bottom: 0; }
.cw-profile-stat-value {
    font-size: 11.5px;
    color: #41484f;
    line-height: 1.4;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.cw-profile-stat-label {
    font-size: 10px;
    color: var(--cw-muted);
    margin-bottom: 3px;
    font-weight: 600;
}

.cw-dh-list { display:flex; flex-direction:column; gap:0; position:relative; }
.cw-dh-list::before { content:''; position:absolute; left:17px; top:8px; bottom:8px; width:2px; background:var(--cw-border); }
.cw-dh-item { display:flex; align-items:flex-start; gap:12px; padding:10px 0; position:relative; }
.cw-dh-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; margin-top:5px; position:relative; z-index:1; border:2px solid #fff; background:var(--cw-border); }
.cw-dh-dot.status-change { background:#3b82c6; }
.cw-dh-dot.reopen { background:#f59e0b; }
.cw-dh-dot.close { background:#065f46; }
.cw-dh-dot.pending { background:#92400e; }
.cw-dh-body { flex:1 1 auto; min-width:0; padding-bottom:6px; border-bottom:1px solid var(--cw-border-light); }
.cw-dh-body:last-child { border-bottom:none; }
.cw-dh-label { font-weight:400; color:#111827; font-size:0.85rem; }
.cw-dh-meta { font-size:0.72rem; color:#6b7280; margin-top:3px; display:flex; flex-wrap:wrap; gap:6px; }
.cw-dh-badge { display:inline-block; padding:1px 7px; border-radius:4px; font-size:0.7rem; font-weight:400; }
.cw-dh-badge.old { background:#f3f4f6; color:#6b7280; }
.cw-dh-badge.new { background:#d1fae5; color:#065f46; }
.cw-dh-arrow { color:#9ca3af; font-size:0.7rem; }
.cw-dh-empty { text-align:center; padding:20px 0; color:#6b7280; font-size:0.82rem; }

.cw-textarea {
    width: 100%;
    border: 1px solid var(--cw-border);
    border-radius: 4px;
    padding: 12px;
    font-family: inherit;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 140px;
}
.cw-textarea:focus {
    outline: none;
    border-color: var(--cw-primary);
    box-shadow: 0 0 0 2px rgba(47, 111, 168, 0.08);
}

.cw-info-box {
    padding: 14px;
    border-radius: 6px;
    border: 1px solid var(--cw-border-light);
    background: #f9fafb;
}
.cw-info-box.compact {
    padding: 4px 10px;
    margin-top: 10px;
}
.cw-info-box.compact p {
    margin: 0;
    line-height: 1.25;
    font-size: 0.78rem;
}
.cw-info-box.green {
    background: #f6fbf7;
    border-color: #c8e6d0;
}
.cw-info-box.blue {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.cw-info-box p {
    margin: 0;
    font-size: 0.82rem;
    color: #374151;
    font-weight: 400;
    line-height: 1.5;
}
.cw-info-box.green p { color: #065f46; }
.cw-info-box.blue p { color: #1e40af; }

.cw-modal-overlay { position:fixed; inset:0; background:rgba(15, 23, 42, 0.45); z-index:1050; display:none; align-items:center; justify-content:center; padding:16px; }
.cw-modal-overlay.active { display:flex; }
.cw-modal { background:#fff; border-radius:6px; box-shadow:0 4px 20px rgba(15, 23, 42, 0.12); max-width:480px; width:100%; max-height:calc(100vh - 32px); overflow-y:auto; }
.cw-modal-head { display:flex; align-items:center; justify-content:space-between; padding:12px 15px; border-bottom:1px solid var(--cw-border-light); }
.cw-modal-head h3 { margin:0; font-size:15px; font-weight:600; color:var(--cw-text); }
.cw-modal-close { background:none; border:none; font-size:1.1rem; cursor:pointer; color:var(--cw-muted); line-height:1; padding:3px 5px; border-radius:4px; }
.cw-modal-close:hover { color:var(--cw-text); background:#f3f4f6; }
.cw-modal-body { padding:14px 15px; }
.cw-modal .profile-field { margin-bottom:10px; }
.cw-modal .profile-field:last-child { margin-bottom:0; }
.cw-modal .profile-field label { display:block; font-size:10.5px; font-weight:600; color:var(--cw-text); margin-bottom:4px; }
.cw-modal .profile-field input { width:100%; box-sizing:border-box; padding:6px 9px; border:1px solid var(--cw-border); border-radius:4px; font-size:12.5px; outline:none; background:var(--cw-card); color:var(--cw-text); }
.cw-modal .profile-field input:focus { border-color:var(--cw-primary); box-shadow:0 0 0 2px rgba(47, 111, 168, 0.08); }
.cw-modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:10px 15px; border-top:1px solid var(--cw-border-light); }
.cw-modal-progress { height:3px; background:var(--cw-border); border-radius:2px; margin-top:14px; overflow:hidden; }
.cw-modal-progress-bar { height:100%; background:#1f7f52; width:100%; transform-origin:left; transform:scaleX(1); }

@media (max-width: 900px) {
    .cw-row { grid-template-columns: 1fr; }
    .cw-col-side { position: static; width: auto; }
}
@media (max-width: 600px) {
    .cw-row { grid-template-columns: 1fr; }
    .cw-col-side { position: static; width: auto; }
    .cw-info-grid, .cw-info-grid-3 { grid-template-columns: 1fr; }
    .cw-card-head { padding: 10px 12px; }
    .cw-card-body { padding: 10px 12px; }
    .cw-profile-avatar { width: 50px; height: 50px; font-size: 15px; }
}
@media (max-width: 480px) {
    .cw-card-actions { flex-direction: column; }
    .cw-card-actions .cw-btn { width: 100%; justify-content: center; }
}
</style>

<section class="cw-module">
  <?php if (!empty($flash)): ?>
    <?php [$fc, $fm] = explode('|', $flash, 2); ?>
    <div class="cw-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
  <?php endif; ?>

  <div class="cw-row">
    <div class="cw-col cw-col-side">
      <!-- Employee Profile -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Employee Profile</h3>
        </div>
        <div class="cw-card-body">
          <div class="cw-profile">
            <div class="cw-profile-avatar"><?= htmlspecialchars(strtoupper(substr($exit['employee_name'] ?? 'UN', 0, 2))) ?></div>
            <div class="cw-profile-name"><?= htmlspecialchars($exit['employee_name'] ?: 'Unknown Employee') ?></div>
            <div class="cw-profile-no"><?= htmlspecialchars($employeeNo, ENT_QUOTES) ?></div>
          </div>
          <div class="cw-info-grid" style="margin-bottom:0;">
            <div class="cw-info-item">
              <label>Department</label>
              <div><?= htmlspecialchars($exit['department_name'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Position</label>
              <div><?= htmlspecialchars($exit['position_name'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Employment Type</label>
              <div><?= htmlspecialchars($employmentType, ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Date Hired</label>
              <div><?= $hireDateDisplay ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Exit Summary -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Exit Summary</h3>
        </div>
        <div class="cw-card-body">
          <div class="cw-info-grid" style="margin-bottom:0;">
            <div class="cw-info-item">
              <label>Exit Type</label>
              <div><?= htmlspecialchars($exit['type_of_separation'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Last Working Day</label>
              <div><?= htmlspecialchars($exit['last_working_day'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Legal Status</label>
              <div><?= $isExitAcknowledged ? 'Acknowledged' : 'Pending' ?></div>
            </div>
            <div class="cw-info-item">
              <label>Request Date</label>
              <div><?= !empty($exit['created_at']) ? date('M d, Y', strtotime($exit['created_at'])) : 'N/A' ?></div>
            </div>
          </div>
          <div class="cw-info-box compact <?= $isExitAcknowledged ? 'green' : '' ?>" style="margin-top:12px;">
            <p>
              <?php if ($isExitAcknowledged): ?>
                Exit has been acknowledged and recorded.
              <?php else: ?>
                Awaiting compliance verification and acknowledgement.
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="cw-col cw-col-main">
      <!-- Employee Information -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Employee Information</h3>
        </div>
        <div class="cw-card-body">
          <div class="cw-info-grid">
            <div class="cw-info-item">
              <label>Employee No</label>
              <div><?= htmlspecialchars($employeeNo, ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Employee Name</label>
              <div><?= htmlspecialchars($exit['employee_name'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Department</label>
              <div><?= htmlspecialchars($exit['department_name'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Position</label>
              <div><?= htmlspecialchars($exit['position_name'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Employment Type</label>
              <div><?= htmlspecialchars($employmentType, ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Date Hired</label>
              <div><?= $hireDateDisplay ?></div>
            </div>
            <div class="cw-info-item">
              <label>Last Working Day</label>
              <div><?= htmlspecialchars($exit['last_working_day'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Exit Type</label>
              <div><?= htmlspecialchars($exit['type_of_separation'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
            <div class="cw-info-item">
              <label>Exit Reason</label>
              <div><?= htmlspecialchars($exit['separation_notes'] ?? 'N/A', ENT_QUOTES) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Compliance Verification -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Compliance Verification</h3>
        </div>
        <div class="cw-card-body">
          <div class="cw-info-grid cw-info-grid-3" style="margin-top:10px;">
            <?php foreach ($complianceItems as $item): ?>
              <?php
                $stampCls = 'cw-stamp-pending';
                $stampLabel = 'Pending';
                if ($item['status'] === 'completed') { $stampCls = 'cw-stamp-compliant'; $stampLabel = 'Completed'; }
                else if ($item['status'] === 'none') { $stampCls = 'cw-stamp-info'; $stampLabel = 'N/A'; }
              ?>
              <div class="cw-info-item">
                <label><?= htmlspecialchars($item['label'], ENT_QUOTES) ?></label>
                <div><span class="cw-stamp <?= $stampCls ?>"><?= $stampLabel ?></span></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="cw-info-box compact <?= $allComplianceClear ? 'green' : '' ?>" style="<?= !$allComplianceClear ? ' background:rgba(217,154,43,.06); border-color:rgba(217,154,43,.18);' : '' ?>">
            <p style="<?= !$allComplianceClear ? 'color:#a86b13;' : '' ?>">
              <?php if ($allComplianceClear): ?>
                All compliance requirements completed. Employee is eligible for Exit Acknowledgement.
              <?php else: ?>
                Some compliance requirements are pending. Please resolve before acknowledging.
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Activity Timeline</h3>
        </div>
        <div class="cw-card-body">
          <?php if (empty($timeline)): ?>
            <div class="cw-dh-empty">No activity records found.</div>
          <?php else: ?>
            <div class="cw-dh-list">
              <?php foreach ($timeline as $act): ?>
                <?php
                  $dateObj = new DateTime($act['activity_date']);
                  $dateStr = $dateObj->format('M d, Y g:i A');
                  $dotCls = 'pending';
                  $badgeCls = 'cw-stamp-pending';
                  $badgeText = 'Pending';
                  if (in_array($act['status'], ['Approved', 'Published', 'Open', 'Confirmed', 'Acknowledged', 'Archived'])) {
                    $dotCls = 'close';
                    $badgeCls = 'cw-stamp-compliant';
                    $badgeText = 'Completed';
                  } else if (in_array($act['status'], ['Pending', 'Filed', 'Notified'])) {
                    $dotCls = 'pending';
                    $badgeCls = 'cw-stamp-info';
                    $badgeText = 'Current';
                  }
                ?>
                <div class="cw-dh-item">
                  <div class="cw-dh-dot <?= $dotCls ?>"></div>
                  <div class="cw-dh-body">
                    <div class="cw-dh-label">
                      <?= htmlspecialchars($act['activity_type'], ENT_QUOTES) ?>
                      <span class="cw-stamp <?= $badgeCls ?>" style="margin-left:8px;"><?= $badgeText ?></span>
                    </div>
                    <div class="cw-dh-meta"><?= htmlspecialchars($act['description'], ENT_QUOTES) ?></div>
                    <div class="cw-dh-meta"><?= $dateStr ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Job Opening Update -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Job Opening Update</h3>
        </div>
        <div class="cw-card-body">
          <?php
            $vacancyDate = !empty($exit['confirmed_at']) ? date('Y-m-d', strtotime($exit['confirmed_at'])) : date('Y-m-d');
            $hasJobRecords = !empty($vacantPosition) || !empty($jobPosting) || !empty($positionJobPosts) || !empty($openRecruitments);
          ?>
          <?php if (!$hasJobRecords && !$isExitAcknowledged): ?>
            <div class="cw-dh-empty">No vacant position information available.</div>
          <?php endif; ?>

          <?php if ($isExitAcknowledged): ?>
            <div class="cw-info-grid">
              <div class="cw-info-item">
                <label>Position</label>
                <div><?= htmlspecialchars($exit['position_name'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Department</label>
                <div><?= htmlspecialchars($exit['department_name'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Vacancy Date</label>
                <div><?= htmlspecialchars($vacancyDate, ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Employment Type</label>
                <div><?= htmlspecialchars($employmentType, ENT_QUOTES) ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($vacantPosition): ?>
            <div class="cw-info-grid">
              <div class="cw-info-item">
                <label>Position</label>
                <div><?= htmlspecialchars($vacantPosition['position'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Department</label>
                <div><?= htmlspecialchars($vacantPosition['department'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Vacancy Date</label>
                <div><?= htmlspecialchars($vacantPosition['vacancy_date'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Employment Type</label>
                <div><?= htmlspecialchars($vacantPosition['employment_type'] ?? 'Open', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Status</label>
                <div><?= htmlspecialchars($vacantPosition['status'] ?? 'Open', ENT_QUOTES) ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($positionJobPosts): ?>
            <h4 style="margin:16px 0 10px;font-size:0.85rem;font-weight:700;color:var(--cw-text,#3b4252);">Job Posting Requests for this Position</h4>
            <?php foreach ($positionJobPosts as $jp): ?>
              <div class="cw-info-grid" style="margin-bottom:10px;">
                <div class="cw-info-item">
                  <label>Request #</label>
                  <div><?= htmlspecialchars($jp['request_number'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Position</label>
                  <div><?= htmlspecialchars($jp['previous_position'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Department</label>
                  <div><?= htmlspecialchars($jp['department'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Status</label>
                  <div><?= htmlspecialchars($jp['status'] ?? 'Draft', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Vacancy Date</label>
                  <div><?= htmlspecialchars($jp['vacancy_date'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Employment Type</label>
                  <div><?= htmlspecialchars($jp['employment_type'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($openRecruitments): ?>
            <h4 style="margin:16px 0 10px;font-size:0.85rem;font-weight:700;color:var(--cw-text,#3b4252);">Open Recruitment Postings</h4>
            <?php foreach ($openRecruitments as $rec): ?>
              <div class="cw-info-grid" style="margin-bottom:10px;">
                <div class="cw-info-item">
                  <label>Position</label>
                  <div><?= htmlspecialchars($rec['position'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Department</label>
                  <div><?= htmlspecialchars($rec['department'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Status</label>
                  <div><?= htmlspecialchars($rec['status'] ?? 'Open', ENT_QUOTES) ?></div>
                </div>
                <div class="cw-info-item">
                  <label>Application Deadline</label>
                  <div><?= htmlspecialchars($rec['application_deadline'] ?? 'N/A', ENT_QUOTES) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($jobPosting): ?>
            <div class="cw-info-grid" style="margin-bottom:10px;">
              <div class="cw-info-item">
                <label>Request #</label>
                <div><?= htmlspecialchars($jobPosting['request_number'] ?? 'N/A', ENT_QUOTES) ?></div>
              </div>
              <div class="cw-info-item">
                <label>Recruitment Status</label>
                <div><?= htmlspecialchars($jobPosting['status'] ?? 'Draft', ENT_QUOTES) ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($hasJobRecords || $isExitAcknowledged): ?>
            <div class="cw-info-box blue" style="margin-top:10px;">
              <p>Workforce team has been notified to update the job opening.</p>
            </div>
            <?php if ($isExitAcknowledged): ?>
              <div class="cw-info-box green" style="margin-top:10px;">
          <p>Exit has been confirmed.</p>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Acknowledgement Actions -->
      <div class="cw-card">
        <div class="cw-card-head">
          <h3>Exit Acknowledgement</h3>
        </div>

        <div class="cw-card-body">
          <p style="margin:0 0 12px;font-size:0.82rem;color:var(--cw-muted,#5b6472);">
            Review the clearance requirements before acknowledging the employee’s exit.
          </p>

          <form method="POST" action="" id="eaActionForm"
                data-api-url="/modules/compliance/lib/api/exit_acknowledgement_action.php"
                data-skip>

            <input type="hidden" name="exit_id" value="<?= (int)$exit['id'] ?>">

            <?php if ($legalStatus !== 'confirmed'): ?>
              <button type="submit"
                      name="acknowledge_exit"
                      class="cw-btn primary"
                      id="eaBtnAcknowledge"
                      style="background:rgba(47,158,110,.08);border-color:rgba(47,158,110,.25);color:#1f7a52;">
                Acknowledge Exit
              </button>
            <?php endif; ?>

            <?php if ($legalStatus !== 'returned'): ?>
              <button type="submit"
                      name="return_exit"
                      class="cw-btn danger"
                      id="eaBtnReturn">
                Return to Exit Management
              </button>

              <button type="button"
                      class="cw-btn"
                      id="eaToggleRemarks"
                      style="margin-left:1px;">
                Add Remarks
              </button>
            <?php endif; ?>

            <span id="eaActionStatus"
                  class="cw-action-status"
                  style="margin-left:10px;"></span>

            <div id="eaRemarksWrap" style="display:none; margin-top:12px;">
              <label style="font-size:0.78rem;font-weight:600;color:var(--cw-text,#3b4252);">
                Remarks
              </label>

              <textarea name="legal_remarks"
                        class="cw-textarea"
                        rows="2"
                        placeholder="Enter remarks..."
                        style="font-size:0.82rem;"><?= htmlspecialchars($exit['legal_remarks'] ?? '', ENT_QUOTES) ?></textarea>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="cw-modal-overlay" id="cwJobOpeningModal" role="dialog" aria-modal="true" aria-labelledby="cwModalTitle">
    <div class="cw-modal">
      <div class="cw-modal-head">
        <h3 id="cwModalTitle">Job Opening Update</h3>
        <button type="button" class="cw-modal-close" id="cwModalClose" aria-label="Close"></button>
      </div>
      <div class="cw-modal-body">
        <div class="cw-info-grid">
          <div class="cw-info-item">
            <label>Position</label>
            <div id="cwModalPosition"><?= htmlspecialchars($exit['position_name'] ?? 'N/A', ENT_QUOTES) ?></div>
          </div>
          <div class="cw-info-item">
            <label>Department</label>
            <div id="cwModalDepartment"><?= htmlspecialchars($exit['department_name'] ?? 'N/A', ENT_QUOTES) ?></div>
          </div>
          <div class="cw-info-item">
            <label>Vacancy Date</label>
            <div id="cwModalVacancyDate"><?= htmlspecialchars(($vacancyDate ?? date('Y-m-d')), ENT_QUOTES) ?></div>
          </div>
          <div class="cw-info-item">
            <label>Employment Type</label>
            <div id="cwModalEmploymentType"><?= htmlspecialchars($employmentType, ENT_QUOTES) ?></div>
          </div>
        </div>
        <div class="cw-info-box green" style="margin-top:10px;">
          <p>Exit has been confirmed. This position is now open for job posting.</p>
        </div>
        <div class="cw-info-box blue" style="margin-top:10px;">
          <p>Workforce team has been notified to update the job opening.</p>
        </div>
        <div class="cw-modal-progress"><div class="cw-modal-progress-bar" id="cwModalProgressBar"></div></div>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  var statusEl = document.getElementById('eaActionStatus');
  window.eaShowJobOpeningModal = function() {
    var modal = document.getElementById('cwJobOpeningModal');
    if (!modal) return;
    var bar = document.getElementById('cwModalProgressBar');
    if (bar) {
      bar.style.transition = 'none';
      bar.style.transform = 'scaleX(1)';
    }
    modal.classList.add('active');
    if (bar) {
      bar.offsetHeight;
      bar.style.transition = 'transform 3s linear';
      bar.style.transform = 'scaleX(0)';
    }
    setTimeout(function() {
      modal.classList.remove('active');
      setTimeout(function(){ window.location.reload(); }, 300);
    }, 3000);
  };

  document.getElementById('cwModalClose')?.addEventListener('click', function() {
    var modal = document.getElementById('cwJobOpeningModal');
    if (modal) modal.classList.remove('active');
    setTimeout(function(){ window.location.reload(); }, 300);
  });

  document.getElementById('cwJobOpeningModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('active');
      setTimeout(function(){ window.location.reload(); }, 300);
    }
  });

  var eaRemarksWrap = document.getElementById('eaRemarksWrap');
  var eaToggleRemarks = document.getElementById('eaToggleRemarks');
  if (eaToggleRemarks && eaRemarksWrap) {
    eaToggleRemarks.addEventListener('click', function() {
      var isHidden = eaRemarksWrap.style.display === 'none';
      eaRemarksWrap.style.display = isHidden ? 'block' : 'none';
      eaToggleRemarks.textContent = isHidden ? 'Hide Remarks' : 'Add Remarks';
    });
  }

  window.eaSubmitAction = function(action, btn) {
    if (!confirm('Update exit status? This action will be recorded in the activity log.')) return;

    if (statusEl) {
      statusEl.textContent = 'Updating...';
      statusEl.className = 'cw-action-status';
    }
    if (btn) btn.disabled = true;

    var form = document.getElementById('eaActionForm');
    var apiUrl = form.dataset.apiUrl;
    var formData = new FormData(form);
    formData.append('action', action);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (statusEl) {
        statusEl.textContent = 'Server responded: ' + xhr.status + ' ' + xhr.statusText;
      }
      if (xhr.status < 200 || xhr.status >= 300) {
        if (statusEl) {
          statusEl.textContent = 'Request failed (' + xhr.status + '). Check console.';
          statusEl.className = 'cw-action-status error';
        }
        if (btn) btn.disabled = false;
        return;
      }
      try {
        console.debug('[ea] apiUrl=' + apiUrl, 'status=' + xhr.status, 'response=', xhr.responseText);
        var data = JSON.parse(xhr.responseText);
      } catch (e) {
        if (statusEl) {
          statusEl.textContent = 'Invalid server response. Check console.';
          statusEl.className = 'cw-action-status error';
        }
        console.error('[ea] JSON parse failed:', e);
        console.error('[ea] Raw response (first 500 chars):', xhr.responseText.substring(0, 500));
        if (btn) btn.disabled = false;
        return;
      }
      if (data.success) {
        if (statusEl) {
          statusEl.textContent = data.message || 'Action completed successfully.';
          statusEl.className = 'cw-action-status success';
        }
        if (action === 'acknowledge') {
          window.eaShowJobOpeningModal();
        } else {
          setTimeout(function(){ window.location.reload(); }, 1200);
        }
      } else {
        if (statusEl) {
          statusEl.textContent = data.message || 'Action failed.';
          statusEl.className = 'cw-action-status error';
        }
        if (btn) btn.disabled = false;
      }
    };
    xhr.send(formData);
  };

  document.getElementById('eaBtnAcknowledge')?.addEventListener('click', function(e){ e.preventDefault(); window.eaSubmitAction('acknowledge', this); });
  document.getElementById('eaBtnReturn')?.addEventListener('click', function(e){ e.preventDefault(); window.eaSubmitAction('return', this); });
})();
</script>
<?php ob_end_flush(); ?>




