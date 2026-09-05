<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}

if (!function_exists('sic_value')) {
  function sic_value(?PDO $db, string $sql, $default = 0, array $params = []): int|float|string|null {
    if (!$db instanceof PDO) {
      return $default;
    }
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_NUM);
            return $row[0] ?? $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}
if (!function_exists('sic_all')) {
  function sic_all(?PDO $db, string $sql, array $params = []): array {
    if (!$db instanceof PDO) {
      return [];
    }
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

$pageTitle = 'Salary Increase Request Review';

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$adjustmentId = isset($_GET['adjustment_id']) ? (int) $_GET['adjustment_id'] : 0;

if ($employeeId <= 0 && $adjustmentId > 0 && $db) {
    $employeeId = (int) sic_value($db, "SELECT employee_id FROM lc_salary_adjustments WHERE adjustment_id = :id LIMIT 1", 0, [':id' => $adjustmentId]);
}

if ($employeeId <= 0) {
    http_response_code(400);
    echo '<div class="gc-empty">Invalid request. Employee ID is required.</div>';
    exit;
}

$employee = sic_all($db, "SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id LEFT JOIN em_positions p ON p.position_id = e.position_id WHERE e.employee_id = :eid LIMIT 1", [':eid' => $employeeId]);
$employee = $employee[0] ?? null;

if (!$employee) {
    http_response_code(404);
    echo '<div class="gc-empty">Employee not found.</div>';
    exit;
}

$adjustment = null;
if ($adjustmentId > 0) {
    $adjustment = sic_all($db, "SELECT sa.*, CONCAT(e.first_name, ' ', e.last_name) AS approver_name FROM lc_salary_adjustments sa LEFT JOIN em_employees e ON e.employee_id = sa.approved_by WHERE sa.adjustment_id = :id AND sa.employee_id = :eid LIMIT 1", [':id' => $adjustmentId, ':eid' => $employeeId]);
    $adjustment = $adjustment[0] ?? null;
}

$documents = sic_all($db, "SELECT * FROM lc_employee_documents WHERE employee_id = :eid ORDER BY created_at DESC", [':eid' => $employeeId]);

$fullName = htmlspecialchars((string) ($employee['full_name'] ?? 'N/A'), ENT_QUOTES);
$employeeCode = htmlspecialchars((string) ($employee['employee_code'] ?? 'N/A'), ENT_QUOTES);
$department = htmlspecialchars((string) ($employee['department_name'] ?? 'N/A'), ENT_QUOTES);
$position = htmlspecialchars((string) ($employee['position_name'] ?? 'N/A'), ENT_QUOTES);
$currentSalary = (float) ($employee['negotiated_salary'] ?? 0);

if ($adjustment) {
    $prevSalary = (float) ($adjustment['previous_salary'] ?? 0);
    $newSalary = (float) ($adjustment['new_salary'] ?? 0);
    $diff = $newSalary - $prevSalary;
    $pct = $prevSalary > 0 ? round(($diff / $prevSalary) * 100, 2) : 0;
} else {
    $prevSalary = 0;
    $newSalary = 0;
    $diff = 0;
    $pct = 0;
}

$currentUserId = (int) ($_SESSION['employee_id'] ?? 0);
$isAdmin = !empty($user['role']) && in_array(strtolower((string) $user['role']), ['hr', 'admin', 'compliance', 'superadmin'], true);

$baseActionUrl = '?page=salary-request-view&employee_id=' . (int)$employeeId;
if ($adjustmentId > 0) {
    $baseActionUrl .= '&adjustment_id=' . (int)$adjustmentId;
}
?>
<style>
.gc-module { padding: 4px 2px 24px; }
.gc-grid-2 { display:grid; grid-template-columns:1.7fr 1fr; gap:18px; align-items:start; }
@media (max-width:980px){ .gc-grid-2 { grid-template-columns:1fr; } }
.gc-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; margin-bottom:16px; }
.gc-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.gc-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.gc-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }
.gc-table-wrap { overflow-x:auto; }
.gc-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.gc-table th { text-align:left; font-size:0.7rem; text-transform:uppercase; letter-spacing:.03em; color:var(--text-400,#8b93a1); padding:8px 10px; border-bottom:1px solid var(--border,#e4e8ee); }
.gc-table td { padding:10px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:top; }
.gc-table tr:last-child td { border-bottom:none; }
.gc-stamp { font-size:0.66rem; font-weight:700; padding:2px 9px; border-radius:999px; white-space:nowrap; }
.gc-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.gc-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.gc-stamp-overdue { background:rgba(214,72,74,.14); color:#a3272a; }
.gc-stamp-rejected { background:rgba(107,114,128,.12); color:#374151; }
.gc-badge { font-size:0.66rem; font-weight:700; padding:2px 9px; border-radius:999px; white-space:nowrap; }
.gc-badge-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.gc-badge-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.gc-badge-violation { background:rgba(214,72,74,.12); color:#a3272a; }
.gc-badge-expired { background:rgba(139,147,161,.12); color:#5a616d; }
.gc-btn-sm { padding:5px 12px; border-radius:6px; font-size:0.72rem; font-weight:700; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:4px; text-decoration:none; }
.gc-btn-view { background:#6b7280; color:#fff; }
.gc-btn-view:hover { background:#4b5563; color:#fff; }
.gc-btn-verify { background:#2563eb; color:#fff; }
.gc-btn-verify:hover { background:#1d4ed8; color:#fff; }
.gc-btn-reject { background:#dc2626; color:#fff; }
.gc-btn-reject:hover { background:#b91c1c; color:#fff; }
.gc-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; background:transparent; border:1px solid var(--border,#e4e8ee); color:var(--text-700,#3b4252); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; margin-bottom:16px; }
.gc-back:hover { background:var(--paper,#eef1f5); }
.gc-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
@media (max-width:640px){ .gc-info-grid { grid-template-columns:1fr; } }
.gc-info-item { display:flex; flex-direction:column; gap:2px; }
.gc-info-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); }
.gc-info-value { font-size:0.88rem; font-weight:600; color:var(--text-900,#1b2430); }
.gc-currency { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-weight:600; white-space:nowrap; }
@media (max-width: 980px) {
  .gc-grid-2 { grid-template-columns:1fr; }
}
</style>

<section class="gc-module">
  <a href="?page=salary-compliance" class="gc-back">
    <i class="bi bi-arrow-left"></i> Back to Salary Compliance
  </a>

  <div class="gc-grid-2">
    <div>
      <div class="gc-card">
        <div class="gc-card-head">
          <h3><i class="bi bi-person-badge"></i> Employee Information</h3>
        </div>
        <div class="gc-info-grid">
          <div class="gc-info-item">
            <span class="gc-info-label">Full Name</span>
            <span class="gc-info-value"><?= $fullName ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Employee Code</span>
            <span class="gc-info-value"><?= $employeeCode ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Department</span>
            <span class="gc-info-value"><?= $department ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Position</span>
            <span class="gc-info-value"><?= $position ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Current Salary</span>
            <span class="gc-info-value gc-currency">₱<?= number_format($currentSalary, 2) ?></span>
          </div>
        </div>
      </div>

      <?php if ($adjustment): ?>
      <div class="gc-card">
        <div class="gc-card-head">
          <h3><i class="bi bi-cash-stack"></i> Salary Increase Request #<?= (int)$adjustment['adjustment_id'] ?></h3>
          <span class="gc-badge <?= $adjustment['status'] === 'Pending' ? 'gc-badge-pending' : ($adjustment['status'] === 'Approved' ? 'gc-badge-compliant' : ($adjustment['status'] === 'Applied' ? 'gc-badge-compliant' : 'gc-badge-violation')) ?>"><?= htmlspecialchars($adjustment['status']) ?></span>
        </div>
        <div class="gc-info-grid">
          <div class="gc-info-item">
            <span class="gc-info-label">Adjustment Type</span>
            <span class="gc-info-value"><?= htmlspecialchars($adjustment['adjustment_type'] ?? 'N/A') ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Reason</span>
            <span class="gc-info-value"><?= htmlspecialchars($adjustment['reason'] ?? 'N/A') ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Current Salary</span>
            <span class="gc-info-value gc-currency">₱<?= number_format($prevSalary, 2) ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Proposed Salary</span>
            <span class="gc-info-value gc-currency">₱<?= number_format($newSalary, 2) ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Increase Amount</span>
            <span class="gc-info-value gc-currency" style="color:#16a34a;">₱<?= number_format($diff, 2) ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Percentage Increase</span>
            <span class="gc-info-value" style="color:#16a34a; font-weight:700;"><?= $pct ?>%</span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Effective Date</span>
            <span class="gc-info-value"><?= !empty($adjustment['effective_date']) ? date('M d, Y', strtotime($adjustment['effective_date'])) : 'N/A' ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Request Date</span>
            <span class="gc-info-value"><?= !empty($adjustment['created_at']) ? date('M d, Y', strtotime($adjustment['created_at'])) : 'N/A' ?></span>
          </div>
          <div class="gc-info-item">
            <span class="gc-info-label">Approver</span>
            <span class="gc-info-value"><?= !empty($adjustment['approver_name']) ? htmlspecialchars($adjustment['approver_name']) : 'N/A' ?></span>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="gc-card">
        <div class="gc-card-head">
          <h3><i class="bi bi-folder2-open"></i> Supporting Documents</h3>
          <span style="font-size:0.78rem; color:var(--text-400,#8b93a1);"><?= count($documents) ?> document(s) on file</span>
        </div>
        <div class="gc-table-wrap">
          <table class="gc-table">
            <thead>
              <tr>
                <th>Document</th>
                <th>Type</th>
                <th>Issued</th>
                <th>Expiry</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($documents)): ?>
                <tr><td colspan="6"><div class="gc-empty">No supporting documents uploaded for this employee.</div></td></tr>
              <?php else: ?>
                <?php foreach ($documents as $doc):
                  $verificationStatus = strtolower((string) ($doc['verification_status'] ?? 'pending upload'));
                  if ($verificationStatus === 'verified') { $stampCls = 'gc-stamp-compliant'; }
                  elseif ($verificationStatus === 'rejected') { $stampCls = 'gc-stamp-rejected'; }
                  elseif ($verificationStatus === 'requires correction') { $stampCls = 'gc-stamp-overdue'; }
                  elseif ($verificationStatus === 'for verification') { $stampCls = 'gc-stamp-pending'; }
                  else { $stampCls = 'gc-stamp-pending'; }
                ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($doc['document_name'] ?? 'N/A') ?></strong>
                    <?php if (!empty($doc['rejection_reason'])): ?>
                      <br><small style="color:#dc2626;"><?= htmlspecialchars($doc['rejection_reason']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($doc['document_type'] ?? 'N/A') ?></td>
                  <td><?= !empty($doc['issued_date']) ? date('M d, Y', strtotime($doc['issued_date'])) : '—' ?></td>
                  <td><?= !empty($doc['expiry_date']) ? date('M d, Y', strtotime($doc['expiry_date'])) : '—' ?></td>
                  <td><span class="gc-stamp <?= $stampCls ?>"><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $verificationStatus))) ?></span></td>
                  <td>
                    <?php if (!empty($doc['file_path'])): ?>
                      <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="gc-btn-sm gc-btn-view">View</a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                      <?php if ($verificationStatus !== 'verified' && $verificationStatus !== 'rejected'): ?>
                        <button type="button" class="gc-btn-sm gc-btn-verify" onclick="srdVerify(<?= (int)$doc['id'] ?>)">Verify</button>
                        <button type="button" class="gc-btn-sm gc-btn-reject" onclick="srdReject(<?= (int)$doc['id'] ?>)">Reject</button>
                      <?php else: ?>
                        <button type="button" class="gc-btn-sm gc-btn-reject" onclick="srdSetPending(<?= (int)$doc['id'] ?>)">Set Pending</button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function srdVerify(docId) {
    if (!confirm('Verify this supporting document?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= htmlspecialchars($baseActionUrl, ENT_QUOTES) ?>';
    form.innerHTML = '<input type="hidden" name="ajax" value="document_action"><input type="hidden" name="action" value="verify"><input type="hidden" name="document_id" value="' + docId + '">';
    document.body.appendChild(form);
    form.submit();
}

function srdReject(docId) {
    var reason = prompt('Please provide a rejection reason for this document:');
    if (reason === null) return;
    if (reason.trim() === '') { alert('Rejection reason is required.'); return; }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= htmlspecialchars($baseActionUrl, ENT_QUOTES) ?>';
    form.innerHTML = '<input type="hidden" name="ajax" value="document_action"><input type="hidden" name="action" value="reject"><input type="hidden" name="document_id" value="' + docId + '"><input type="hidden" name="reason" value="' + reason.replace(/"/g, '&quot;') + '">';
    document.body.appendChild(form);
    form.submit();
}

function srdSetPending(docId) {
    if (!confirm('Reset this document to pending verification?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= htmlspecialchars($baseActionUrl, ENT_QUOTES) ?>';
    form.innerHTML = '<input type="hidden" name="ajax" value="document_action"><input type="hidden" name="action" value="set_pending"><input type="hidden" name="document_id" value="' + docId + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>
