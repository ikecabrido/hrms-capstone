<?php

$pageTitle = 'BIR Monitoring';
$moduleHeaderImage = '/hrms-capstone/modules/compliance/assets/bir.png';

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
    if (!($db instanceof PDO)) {
      throw new RuntimeException('Database connection is unavailable.');
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_payroll_bir') {
    $contributionId = isset($_POST['contribution_id']) ? (int) $_POST['contribution_id'] : 0;
    $response = ['success' => false, 'message' => 'Invalid request.'];

    if ($contributionId > 0) {
        try {
            $stmt = $db->prepare("
                SELECT c.id, c.employee_id, c.status, c.contribution_number, c.created_at, c.updated_at,
                       CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code AS employee_no, e.email AS employee_email
                FROM lc_bir_contributions c
                LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                WHERE c.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $contributionId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $response['message'] = 'BIR contribution record not found.';
            } else {
                $status = strtolower($record['status'] ?? '');
                if (!in_array($status, ['pending', 'rejected'], true)) {
                    $response['message'] = 'Only pending or rejected submissions can be escalated to payroll.';
                } else {
                    $payrollEmployee = $db->query("
                         SELECT COALESCE(CONCAT(e.first_name, ' ', e.last_name),'Payroll Manager') AS full_name, e.employee_id, e.email
                        FROM em_employees e
                        WHERE e.department_id = 3 AND e.employment_status NOT IN ('Resigned','Terminated')
                        LIMIT 1
                    ")->fetch(PDO::FETCH_ASSOC);

                    if (!$payrollEmployee || empty($payrollEmployee['email'])) {
                        $response['message'] = 'Payroll contact not found. Please configure a Finance department employee.';
                    } else {
                        $payrollEmail = $payrollEmployee['email'];
                        $payrollName = $payrollEmployee['full_name'] ?? 'Payroll Manager';
                        $employeeName = $record['full_name'] ?? 'Unknown Employee';
                        $employeeNo = $record['employee_no'] ?? 'N/A';
                        $contributionNumber = $record['contribution_number'] ?? 'N/A';
                        $statusLabel = ucfirst($status);

                        $mailer = null;
                        try {
                            if (file_exists(__DIR__ . '/../lib/services/EmailService.php')) {
                                require_once __DIR__ . '/../lib/services/EmailService.php';
                                $mailer = \App\Services\EmailService::getInstance();
                            }
                        } catch (Throwable $e) {}

                        if (!$mailer || !filter_var($payrollEmail, FILTER_VALIDATE_EMAIL)) {
                            $response['message'] = 'Email service is not available.';
                        } else {
                            $subject = 'BIR Withholding Tax Review Required - ' . $employeeName . ' (' . $employeeNo . ')';
                            $body = '<h2>BIR Withholding Tax Review Required</h2>' .
                                '<p><strong>Employee:</strong> ' . htmlspecialchars($employeeName) . ' (' . htmlspecialchars($employeeNo) . ')</p>' .
                                '<p><strong>BIR Reference No.:</strong> ' . htmlspecialchars($contributionNumber) . '</p>' .
                                '<p><strong>Current Status:</strong> ' . htmlspecialchars($statusLabel) . '</p>' .
                                '<p><strong>Date Submitted:</strong> ' . date('F j, Y', strtotime($record['created_at'])) . '</p>' .
                                '<p><strong>Last Updated:</strong> ' . date('F j, Y', strtotime($record['updated_at'])) . '</p>' .
                                '<p><strong>Required Action:</strong></p>' .
                                '<ul>' .
                                '<li>Review the BIR withholding tax submission for this employee.</li>' .
                                '<li>Verify the tax calculation and withheld amount.</li>' .
                                '<li>Update the status in the BIR Monitoring module.</li>' .
                                '<li>Ensure payroll withholding aligns with the current BIR tax table.</li>' .
                                '</ul>' .
                                '<p><em>This is an automated notice from the HR Legal Compliance Management System.</em></p>';

                            $altBody = strip_tags($body);

                            $mailer->send(
                                [['email' => $payrollEmail, 'name' => $payrollName]],
                                $subject,
                                $body,
                                $altBody
                            );

                            $db->prepare("
                                UPDATE lc_bir_contributions
                                SET payroll_notified = 1, payroll_notified_at = NOW(), updated_at = NOW()
                                WHERE id = :id
                            ")->execute([':id' => $contributionId]);

                            $response = [
                                'success' => true,
                                'message' => 'Email notification sent to Payroll (' . htmlspecialchars($payrollName) . ') successfully.'
                            ];
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $response['message'] = 'Failed to send email: ' . $e->getMessage();
        }
    }

    echo json_encode($response);
    exit;
}

function bir_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function bir_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$totalEmployees = (int) bir_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')");
$submitted      = (int) bir_value($db, "SELECT COUNT(*) FROM lc_bir_contributions WHERE status = 'Submitted'");
$pending        = (int) bir_value($db, "SELECT COUNT(*) FROM lc_bir_contributions WHERE status = 'Pending'");
$rejected       = (int) bir_value($db, "SELECT COUNT(*) FROM lc_bir_contributions WHERE status = 'Rejected'");

$nextDeadline = new DateTime('now');
$nextDeadline->modify('last day of next month');
$daysUntilDeadline = (int) $nextDeadline->diff(new DateTime('now'))->days;
$deadlineLabel = 'Next Filing Deadline';
$deadlineDateStr = $nextDeadline->format('F j');
$deadlineDaysStr = $daysUntilDeadline . ' day' . ($daysUntilDeadline !== 1 ? 's' : '') . ' left';

$filter = trim((string)($_GET['filter'] ?? 'all'));

    $recentQuery = "
        SELECT c.*, 
               CONCAT(e.first_name, ' ', e.last_name) AS full_name, 
               e.employee_code AS employee_no, 
               e.email AS employee_email
        FROM lc_bir_contributions c
        LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    ";

$recentParams = [];
if ($filter === 'submitted') {
    $recentQuery .= " WHERE LOWER(c.status) = 'submitted'";
} elseif ($filter === 'pending') {
    $recentQuery .= " WHERE LOWER(c.status) = 'pending'";
} elseif ($filter === 'rejected') {
    $recentQuery .= " WHERE LOWER(c.status) = 'rejected'";
}

$recentQuery .= " ORDER BY c.created_at DESC LIMIT 100";

$recent = bir_all($db, $recentQuery, $recentParams);
$recentActivity = array_slice($recent, 0, 6);

$birBrackets = [];
try {
    $stmt = $db->query("SELECT min_income, max_income, fixed_tax, tax_rate, is_active FROM pr_tax_tables WHERE is_active = 1 AND pay_frequency = 'monthly' ORDER BY min_income ASC");
    $birBrackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $birBrackets = [];
}

$birBracketsJson = json_encode($birBrackets, JSON_NUMERIC_CHECK);
if ($birBracketsJson === false) {
    $birBracketsJson = '[]';
}
?>
<style>
.bir-module { padding: 4px 2px 24px; }
.bir-breadcrumb { margin-bottom:10px; }
.bir-breadcrumb .breadcrumb { background:transparent; padding:0; margin:0; font-size:0.8rem; }
.bir-breadcrumb .breadcrumb-item a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.bir-breadcrumb .breadcrumb-item a:hover { text-decoration:underline; }
.bir-breadcrumb .breadcrumb-item.active { color:var(--text-500,#6b7280); }

.bir-summary-bar { display:flex; gap:8px; margin-bottom:14px; flex-wrap:nowrap; }
.bir-summary-item { display:flex; align-items:center; gap:6px; padding:20px 14px; border-radius:2px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:auto; text-decoration:none; color:inherit; transition:border-color .1s ease; cursor:pointer; font-family: Arial, sans-serif; }
.bir-summary-item:hover { border-color:#cbd5e1; }
.bir-summary-active { border-color:var(--info-blue,#3b82c4); background:#fafbfc; }
.bir-summary-value { font-size:1.1rem; color:var(--text-900,#1b2430); line-height:1.5; font-family: Arial, sans-serif; }
.bir-summary-label { font-size:0.6rem; color:var(--text-700,#3b4252); margin-top:2px; font-family: Arial, sans-serif; }
.bir-summary-desc { font-size:0.55rem; color:var(--text-400,#8b93a1); margin-top:1px; font-family: Arial, sans-serif; }

.bir-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.bir-col-main { min-width:0; font-family: Arial, sans-serif; }
.bir-col-side { width:380px; flex-shrink:0; }

.bir-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:6px; padding:14px; box-shadow:none; margin-bottom:12px; }
.bir-card-head { margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid var(--border,#e4e8ee); }
.bir-card-head h3 { margin:0; font-size:0.88rem; font-weight:600; color:var(--text-900,#1b2430); letter-spacing:-.01em; }
.bir-empty { padding:14px; text-align:center; color:var(--text-500,#64748b); font-size:10px; line-height:1.4; }
.bir-card-body { display:flex; flex-direction:column; max-height:540px; overflow:hidden; }
.bir-table-wrap { overflow:auto; flex:1 1 auto; min-height:0; }
.bir-table { width:100%; border-collapse:collapse; font-size:11.5px; font-family:Arial, sans-serif; }
.bir-table th { text-align:left; padding:8px 10px; font-size:10px; font-weight:600; text-transform:uppercase; color:var(--text-500,#64748b); border-bottom:1px solid var(--border,#e4e8ee); letter-spacing:.04em; line-height:1.3; }
.bir-table td { padding:9px 10px; border-bottom:1px solid var(--border,#e4e8ee); color:var(--text-800,#1e293b); font-size:11.5px; line-height:1.4; }
.bir-table tbody tr:hover { background:var(--slate-50,#f8fafc); }
.bir-table tr:last-child td { border-bottom:none; }
.bir-stamp { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; white-space:nowrap; letter-spacing:.01em; line-height:1.3; }
.bir-stamp-compliant { background:var(--success-50,#ecfdf5); color:var(--success-700,#047857); }
.bir-stamp-pending { background:var(--warning-50,#fffbeb); color:var(--warning-700,#b45309); }
.bir-stamp-violation { background:var(--danger-50,#fef2f2); color:var(--danger-700,#b91c1c); }
.bir-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; flex-wrap:wrap; font-size:0.75rem; color:var(--text-500,#64748b); }
.bir-pagination-info { font-size:0.75rem; color:var(--text-500,#64748b); white-space:nowrap; }
.bir-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:transparent; border:1px solid var(--border,#e4e8ee); border-radius:6px; overflow:hidden; }
.bir-pagination-nav .bir-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border:0; background:transparent; font-size:0.75rem; color:var(--text-700,#334155); cursor:pointer; text-decoration:none; transition:background-color .1s ease; }
.bir-pagination-nav .bir-page-btn:hover:not(.bir-page-btn--active) { background:var(--slate-100,#f1f5f9); }
.bir-pagination-nav .bir-page-btn[aria-disabled="true"] { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.bir-pagination-nav .bir-page-btn--active { background:#2563eb; color:#fff; font-weight:600; }
.bir-pagination-nav .bir-page-ellipsis { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; color:var(--text-500,#64748b); }
.bir-portal-link { font-size:0.72rem; font-weight:600; color:var(--info-600,#0891b2); text-decoration:none; padding:2px 0; border-bottom:1px solid transparent; transition:border-color .1s ease; }
.bir-portal-link:hover { border-bottom-color:var(--info-600,#0891b2); }
.bir-table-search { position:relative; }
.bir-table-search input { padding:6px 10px; border:1px solid var(--border,#e4e8ee); border-radius:6px; font-size:0.75rem; outline:none; width:200px; transition:border-color .1s ease; background:var(--card-bg,#fff); }
.bir-table-search input:focus { border-color:var(--text-400,#94a3b8); }
.bir-finder-card { border-color:var(--seal-gold-light,#f4e6c9); }
.bir-finder-form { margin-bottom:10px; }
.bir-finder-label { display:block; font-size:0.72rem; font-weight:600; color:var(--text-700,#334155); margin-bottom:4px; }
.bir-finder-input-wrap { display:flex; align-items:center; gap:6px; border:1px solid var(--border,#e4e8ee); border-radius:6px; padding:8px 10px; background:#fff; transition:border-color .1s ease; }
.bir-finder-input-wrap:focus-within { border-color:var(--seal-gold,#a8791f); }
.bir-finder-prefix { font-weight:700; color:var(--text-900,#1b2430); font-size:0.85rem; }
.bir-finder-input { flex:1; border:0; outline:none; font-size:0.9rem; font-weight:700; color:var(--text-900,#1b2430); background:transparent; }
.bir-finder-input::placeholder { color:var(--text-400,#8b93a1); font-weight:500; }
.bir-finder-result { background:var(--paper,#eef1f5); border:1px solid var(--border,#e4e8ee); border-radius:8px; padding:12px; }
.bir-finder-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.bir-finder-key { font-size:0.7rem; font-weight:700; color:var(--text-500,#64748b); text-transform:uppercase; letter-spacing:.4px; }
.bir-finder-val { font-size:0.78rem; font-weight:700; color:var(--text-900,#1b2430); }
.bir-finder-divider { height:1px; background:var(--border,#e4e8ee); margin:8px 0; }
.bir-finder-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.bir-finder-cell { display:flex; flex-direction:column; gap:1px; padding:8px; background:#fff; border-radius:6px; border:1px solid var(--border,#e4e8ee); }
.bir-finder-cell--total { background:rgba(168,121,31,.06); border-color:rgba(168,121,31,.2); }
.bir-finder-cell-label { font-size:0.62rem; font-weight:700; color:var(--text-500,#64748b); text-transform:uppercase; letter-spacing:.3px; }
.bir-finder-cell-value { font-size:0.85rem; font-weight:800; color:var(--text-900,#1b2430); }
.bir-range { color:#1c5a8a; }
.bir-base { color:#1f7a52; }
.bir-rate { color:#8a6318; }
.bir-total { color:#8a6318; }
.bir-finder-empty { display:flex; align-items:center; gap:6px; padding:12px; color:var(--text-500,#64748b); font-size:0.75rem; text-align:center; justify-content:center; }
.bir-view-all { font-size:0.72rem; font-weight:600; color:var(--info-600,#0891b2); text-decoration:none; }
.bir-view-all:hover { text-decoration:underline; }
.bir-activity-list { display:flex; flex-direction:column; }
.bir-activity-item { display:flex; gap:10px; padding:8px 0; border-bottom:1px solid var(--border,#e4e8ee); }
.bir-activity-item:last-child { border-bottom:none; }
.bir-activity-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.bir-activity-dot-compliant { background:#1f7a52; }
.bir-activity-dot-pending { background:#d99a2b; }
.bir-activity-dot-violation { background:#d6484a; }
.bir-activity-body { flex:1; min-width:0; }
.bir-activity-text { font-size:0.78rem; font-weight:600; color:var(--text-900,#1b2430); line-height:1.3; }
.bir-activity-meta { display:flex; gap:8px; margin-top:2px; font-size:0.68rem; color:var(--text-500,#64748b); font-family:Arial, sans-serif; }
.bir-activity-name { font-weight:600; color:var(--text-600,#475569); }
.bir-bracket-placeholder { display:flex; flex-direction:column; align-items:center; text-align:center; gap:6px; padding:24px 12px; }
.bir-bracket-placeholder i { display:none; }
.bir-bracket-title { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.bir-bracket-desc { font-size:0.72rem; color:var(--text-500,#64748b); line-height:1.4; }
.bir-bracket-link { margin-top:4px; font-size:0.72rem; font-weight:600; color:var(--info-600,#0891b2); text-decoration:none; }
.bir-bracket-link:hover { text-decoration:underline; }
@media (max-width:1100px) {
  .bir-row { grid-template-columns:1fr; }
  .bir-col-side { position:static; width:auto; min-width:0; }
}
.bir-module,
.bir-card { box-sizing:border-box; max-width:100%; overflow:hidden; }
.bir-summary-item { min-width:0; flex:1 1 calc(50% - 8px); max-width:calc(50% - 8px); }
.bir-card-head { flex-wrap:wrap; gap:8px; }
.bir-table-search input { max-width:100%; }
@media (max-width:768px) {
  .bir-table-search input { width:100%; max-width:100%; }
  .bir-card-body { max-height:none !important; overflow:visible !important; }
  .bir-table-wrap { overflow:visible !important; flex:none !important; }
  .bir-table,
  .bir-table thead,
  .bir-table tbody,
  .bir-table th,
  .bir-table td,
  .bir-table tr { display:block; width:100%; min-width:0; }
  .bir-table { border-collapse:separate; border-spacing:0; }
  .bir-table thead { display:none; }
  .bir-table tr { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:8px; padding:10px 12px; margin-bottom:10px; box-shadow:none; }
  .bir-table td { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid var(--border,#e4e8ee); text-align:right; min-width:0; overflow-wrap:anywhere; word-break:break-word; }
  .bir-table td:last-child { border-bottom:none; padding-bottom:0; }
  .bir-table td::before { content:attr(data-label); font-weight:600; font-size:0.68rem; text-transform:uppercase; color:var(--text-500,#64748b); text-align:left; flex-shrink:0; margin-right:6px; }
  .bir-table td:last-child { justify-content:flex-end; }
  .bir-stamp { font-size:0.7rem; padding:2px 8px; }
  .bir-portal-link { font-size:0.78rem; }
}
@media (max-width:400px) {
  .bir-finder-grid { grid-template-columns:1fr; }
  .bir-finder-cell--total { order:-1; }
}
.bir-finder-input { min-width:0; }
.bir-finder-val { overflow-wrap:anywhere; word-break:break-word; }
.bir-finder-row { flex-wrap:wrap; gap:6px; }
.bir-finder-key { flex-shrink:0; }
</style>

<section class="bir-module">
  <div class="bir-summary-bar">
    <a class="bir-summary-item <?= $filter === 'all' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=all">
      <div>
        <div class="bir-summary-value"><?= number_format($totalEmployees) ?></div>
        <div class="bir-summary-label">Total Employees</div>
      </div>
    </a>
    <a class="bir-summary-item <?= $filter === 'submitted' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=submitted">
      <div>
        <div class="bir-summary-value"><?= number_format($submitted) ?></div>
        <div class="bir-summary-label">Submitted</div>
      </div>
    </a>
    <a class="bir-summary-item <?= $filter === 'pending' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=pending">
      <div>
        <div class="bir-summary-value"><?= number_format($pending) ?></div>
        <div class="bir-summary-label">Pending</div>
      </div>
    </a>
    <a class="bir-summary-item <?= $filter === 'rejected' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=rejected">
      <div>
        <div class="bir-summary-value"><?= number_format($rejected) ?></div>
        <div class="bir-summary-label">Rejected</div>
      </div>
    </a>
    <div class="bir-summary-item">
      <div>
        <div class="bir-summary-value"><?= htmlspecialchars($deadlineDateStr) ?></div>
        <div class="bir-summary-label"><?= htmlspecialchars($deadlineLabel) ?></div>
        <div class="bir-summary-desc"><?= htmlspecialchars($deadlineDaysStr) ?></div>
      </div>
    </div>
  </div>

  <div class="bir-row">
    <div class="bir-col bir-col-main">
      <div class="bir-card">
        <div class="bir-card-head">
          <h3>Recent BIR Submissions</h3>
        </div>
        <div class="bir-card-body">
          <?php if (empty($recent)): ?>
            <div class="bir-empty">No BIR contributions recorded yet.</div>
          <?php else: ?>
          <div class="bir-table-wrap">
            <table class="bir-table" id="birRecentTable">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Employee No.</th>
                  <th>BIR Reference No.</th>
                  <th>Status</th>
                  <th>Date Submitted</th>
                  <th>Updated</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                 <?php foreach ($recent as $r):
                   $status = strtolower($r['status'] ?? 'pending');
                   if ($status === 'submitted') $stampCls = 'compliant';
                   elseif ($status === 'rejected') $stampCls = 'violation';
                   elseif ($status === 'overdue') $stampCls = 'violation';
                   else $stampCls = 'pending';
                ?>
                <tr>
                  <td data-label="Employee"><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></td>
                  <td data-label="Employee No."><?= htmlspecialchars($r['employee_no'] ?? '—') ?></td>
                  <td data-label="BIR Reference No."><?= !empty($r['contribution_number']) ? htmlspecialchars($r['contribution_number']) : '—' ?></td>
                  <td data-label="Status"><span class="bir-stamp bir-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span></td>
                  <td data-label="Date Submitted"><?= !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : '—' ?></td>
                  <td data-label="Updated"><?= !empty($r['updated_at']) ? date('M d, Y', strtotime($r['updated_at'])) : '—' ?></td>
                  <td data-label="Action">
                    <?php if (in_array($status, ['overdue', 'pending', 'rejected'], true)): ?>
                      <a href="https://www.bir.gov.ph/eServices" target="_blank" rel="noopener noreferrer"
                         class="bir-email-payroll-btn"
                         title="Go to BIR eServices"
                         aria-label="Go to BIR eServices">
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="bir-pagination" id="birRecentPagination" style="display:none;">
            <span class="bir-pagination-info" id="birPaginationInfo"></span>
            <nav class="bir-pagination-nav" id="birPaginationNav" role="navigation" aria-label="BIR table pagination"></nav>
          </div>
           <?php endif; ?>
         </div>
      </div>
    </div>

    <div class="bir-col bir-col-side">
      <div class="bir-card bir-finder-card">
        <div class="bir-card-head">
          <h3>BIR Withholding Tax Reference</h3>
        </div>
        <div class="bir-card-body">
          <div class="bir-finder-form">
            <label class="bir-finder-label" for="birSalaryInput">Monthly Salary</label>
            <div class="bir-finder-input-wrap">
              <span class="bir-finder-prefix">₱</span>
              <input type="number" id="birSalaryInput" class="bir-finder-input" placeholder="25,000" min="0" step="1" inputmode="numeric">
            </div>
          </div>
          <div class="bir-finder-result" id="birFinderResult" style="display:none;">
            <div class="bir-finder-row">
              <span class="bir-finder-key">Tax Status</span>
              <span class="bir-finder-val" id="birFinderStatus">—</span>
            </div>
            <div class="bir-finder-divider"></div>
            <div class="bir-finder-grid">
              <div class="bir-finder-cell">
                <span class="bir-finder-cell-label">Taxable Range</span>
                <span class="bir-finder-cell-value bir-range" id="birFinderRange">₱0.00 – ₱0.00</span>
              </div>
              <div class="bir-finder-cell">
                <span class="bir-finder-cell-label">Base Tax</span>
                <span class="bir-finder-cell-value bir-base" id="birFinderBase">₱0.00</span>
              </div>
              <div class="bir-finder-cell">
                <span class="bir-finder-cell-label">Excess Rate</span>
                <span class="bir-finder-cell-value bir-rate" id="birFinderRate">0%</span>
              </div>
              <div class="bir-finder-cell bir-finder-cell--total">
                <span class="bir-finder-cell-label">Monthly Tax (Est.)</span>
                <span class="bir-finder-cell-value bir-total" id="birFinderTotal">₱0.00</span>
              </div>
            </div>
          </div>
          <div class="bir-finder-empty" id="birFinderEmpty">
            <i class="bi bi-info-circle"></i>
            <span>Enter a monthly salary to see the applicable withholding tax.</span>
          </div>
        </div>
      </div>
      <div class="bir-card" id="birBracketCard">
        <div class="bir-card-head">
          <h3>BIR Tax Brackets</h3>
        </div>
        <div class="bir-card-body">
          <div class="bir-bracket-placeholder">
                        <div class="bir-bracket-title">BIR Withholding Tax Table</div>
            <div class="bir-bracket-desc">View the current BIR withholding tax schedule based on monthly compensation.</div>
            <a href="?page=government-contribution-brackets&type=bir" class="bir-bracket-link">Open BIR Tax Table <i class="bi bi-arrow-right-short"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
(function() {
  var filter = '<?= htmlspecialchars((string)($filter ?? 'all')) ?>';
  var brackets = <?= $birBracketsJson ?>;
  var salaryInput = document.getElementById('birSalaryInput');
  var resultBox = document.getElementById('birFinderResult');
  var emptyBox = document.getElementById('birFinderEmpty');
  var statusLabel = document.getElementById('birFinderStatus');
  var rangeLabel = document.getElementById('birFinderRange');
  var baseLabel = document.getElementById('birFinderBase');
  var rateLabel = document.getElementById('birFinderRate');
  var totalLabel = document.getElementById('birFinderTotal');

  function formatMoney(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function findBracket(monthlySalary) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = parseFloat(b.min_income);
      var max = b.max_income !== null ? parseFloat(b.max_income) : null;
      if (monthlySalary >= min && (max === null || monthlySalary <= max)) {
        return b;
      }
    }
    return null;
  }

  function updateFinder() {
    var raw = salaryInput.value.replace(/[^0-9.]/g, '');
    if (raw === '') {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var monthlySalary = parseFloat(raw);
    if (isNaN(monthlySalary) || monthlySalary < 0) {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }

    if (monthlySalary === 0) {
      statusLabel.textContent = 'No Compensation';
      rangeLabel.textContent = '—';
      baseLabel.textContent = formatMoney(0);
      rateLabel.textContent = '0%';
      totalLabel.textContent = formatMoney(0);
      resultBox.style.display = 'block';
      emptyBox.style.display = 'none';
      return;
    }

    var b = findBracket(monthlySalary);
    if (!b) {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }

    var min = parseFloat(b.min_income);
    var max = b.max_income !== null ? parseFloat(b.max_income) : null;
    var rangeTxt = max !== null
      ? formatMoney(min) + ' – ' + formatMoney(max)
      : formatMoney(min) + ' and above';

    var base = parseFloat(b.fixed_tax) || 0;
    var rate = parseFloat(b.tax_rate) || 0;
    var excessOver = parseFloat(b.min_income) || 0;
    var excess = Math.max(0, monthlySalary - excessOver);
    var totalTax = base + (excess * rate * 100);
    totalTax = Math.max(0, totalTax);

    statusLabel.textContent = totalTax > 0 ? 'Withholding Tax Applicable' : 'No Withholding Tax';
    rangeLabel.textContent = rangeTxt;
    baseLabel.textContent = formatMoney(base);
    rateLabel.textContent = Number(rate * 100).toFixed(2) + '%';
    totalLabel.textContent = formatMoney(totalTax);

    resultBox.style.display = 'block';
    emptyBox.style.display = 'none';
  }

  if (salaryInput) {
    salaryInput.addEventListener('input', updateFinder);
    salaryInput.addEventListener('change', updateFinder);
  }

  /* BIR Recent Table Pagination */
  (function() {
    var table = document.getElementById('birRecentTable');
    if (!table) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    if (rows.length === 0) return;

    var pageSize = 13;
    var totalItems = rows.length;
    var totalPages = Math.ceil(totalItems / pageSize);
    var currentPage = 1;

    var infoEl = document.getElementById('birPaginationInfo');
    var navEl = document.getElementById('birPaginationNav');
    var paginationEl = document.getElementById('birRecentPagination');

    if (filter === 'all') {
      paginationEl.style.display = 'flex';
    }

    function startIdx() { return (currentPage - 1) * pageSize; }
    function endIdx() { return Math.min(startIdx() + pageSize, totalItems); }

    function renderPage() {
      rows.forEach(function(row, i) {
        row.style.display = (i >= startIdx() && i < endIdx()) ? '' : 'none';
      });
      infoEl.textContent = 'Showing ' + (startIdx() + 1) + '–' + endIdx() + ' of ' + totalItems;
      navEl.innerHTML = '';
      renderNav();
    }

    function renderNav() {
      var prevDisabled = currentPage === 1;
      var nextDisabled = currentPage === totalPages;

      var prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'bir-page-btn';
      prevBtn.disabled = prevDisabled;
      prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
      prevBtn.addEventListener('click', function() {
        if (!prevDisabled) { currentPage--; renderPage(); }
      });
      navEl.appendChild(prevBtn);

      var maxVisible = 3;
      var startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
      var endPage = Math.min(totalPages, startPage + maxVisible - 1);
      if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
      }

      if (startPage > 1) {
        appendPageBtn(1);
        if (startPage > 2) appendEllipsis();
      }

      for (var p = startPage; p <= endPage; p++) {
        appendPageBtn(p);
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) appendEllipsis();
        appendPageBtn(totalPages);
      }

      var nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'bir-page-btn';
      nextBtn.disabled = nextDisabled;
      nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
      nextBtn.addEventListener('click', function() {
        if (!nextDisabled) { currentPage++; renderPage(); }
      });
      navEl.appendChild(nextBtn);
    }

    function appendPageBtn(pageNum) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bir-page-btn' + (pageNum === currentPage ? ' bir-page-btn--active' : '');
      btn.textContent = pageNum;
      btn.addEventListener('click', function() {
        currentPage = pageNum;
        renderPage();
      });
      navEl.appendChild(btn);
    }

    function appendEllipsis() {
      var span = document.createElement('span');
      span.className = 'bir-page-ellipsis';
      span.textContent = '…';
      navEl.appendChild(span);
    }

    renderPage();
  })();
})();
</script>

