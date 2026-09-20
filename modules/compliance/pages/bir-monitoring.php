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

.bir-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.bir-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.bir-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.bir-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
.bir-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.bir-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.bir-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.bir-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.bir-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.bir-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
.bir-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.bir-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }
.bir-summary-desc { font-size:0.62rem; color:var(--text-400,#8b93a1); margin-top:2px; font-weight:600; }

.bir-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.bir-col-main { min-width:0; }
.bir-col-side { width:380px; flex-shrink:0; }

.bir-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.bir-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.bir-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.bir-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.bir-card-body {
  display:flex;
  flex-direction:column;
  max-height: 540px;
  overflow: hidden;
}
.bir-table-wrap {
  overflow: auto;
  flex: 1 1 auto;
}
.bir-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.bir-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
.bir-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); }
.bir-table tr:last-child td { border-bottom:none; }
.bir-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.bir-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.bir-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.bir-stamp-violation { background:rgba(214,72,74,.12); color:#a3272a; }

/* BIR Pagination */
.bir-pagination { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:14px; flex-wrap:wrap; font-size:0.8rem; color:var(--text-600,#4a505a); }
.bir-pagination-info { font-size:0.8rem; color:var(--text-600,#4a505a); white-space:nowrap; }
.bir-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:8px; overflow:hidden; }
.bir-pagination-nav .bir-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:none; background:transparent; font-size:0.8rem; color:var(--text-700,#3b4252); cursor:pointer; transition:all .15s ease; }
.bir-pagination-nav .bir-page-btn:hover { background:rgba(59,130,196,.08); color:var(--info-blue,#3b82c4); }
.bir-pagination-nav .bir-page-btn:disabled { opacity:0.4; cursor:not-allowed; }
.bir-pagination-nav .bir-page-btn--active { background:var(--info-blue,#3b82c4); color:#fff; font-weight:600; }
.bir-pagination-nav .bir-page-ellipsis { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem; color:var(--text-400,#8b93a1); user-select:none; }

.bir-email-payroll-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border, #e4e8ee);
  background: #fff;
  color: var(--text-700, #3b4252);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}
.bir-email-payroll-btn:hover:not(:disabled) {
  border-color: var(--seal-gold, #a8791f);
  color: var(--seal-gold, #a8791f);
  box-shadow: 0 0 0 3px rgba(168, 121, 31, 0.08);
}
.bir-email-payroll-btn:disabled {
  background: var(--paper, #eef1f5);
  border-color: var(--hairline, #dde3ea);
  color: var(--text-400, #8b95a4);
  cursor: not-allowed;
  box-shadow: none;
}

.bir-finder-card { border-color:var(--seal-gold-light,#f4e6c9); }
.bir-finder-form { margin-bottom:14px; }
.bir-finder-label { display:block; font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:6px; }
.bir-finder-input-wrap { display:flex; align-items:center; gap:8px; border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:10px 12px; background:#fff; transition:border-color .15s ease, box-shadow .15s ease; }
.bir-finder-input-wrap:focus-within { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }
.bir-finder-prefix { font-weight:700; color:var(--text-900,#1b2430); font-size:0.95rem; }
.bir-finder-input { flex:1; border:0; outline:none; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); background:transparent; min-width:0; }
.bir-finder-input::placeholder { color:var(--text-400,#8b93a1); font-weight:500; }

.bir-finder-result { background:var(--paper,#eef1f5); border:1px solid var(--border,#e4e8ee); border-radius:12px; padding:14px; }
.bir-finder-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.bir-finder-key { font-size:0.76rem; font-weight:700; color:var(--text-600,#5a6779); text-transform:uppercase; letter-spacing:.4px; }
.bir-finder-val { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.bir-finder-divider { height:1px; background:var(--border,#e4e8ee); margin:10px 0; }
.bir-finder-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.bir-finder-cell { display:flex; flex-direction:column; gap:2px; padding:10px; background:#fff; border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.bir-finder-cell--total { background:rgba(168,121,31,.08); border-color:rgba(168,121,31,.25); }
.bir-finder-cell-label { font-size:0.68rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.3px; }
.bir-finder-cell-value { font-size:0.95rem; font-weight:800; color:var(--text-900,#1b2430); }
.bir-range { color:#1c5a8a; }
.bir-base { color:#1f7a52; }
.bir-rate { color:#8a6318; }
.bir-total { color:#8a6318; }
.bir-finder-empty { display:flex; align-items:center; gap:8px; padding:16px; color:var(--text-400,#8b93a1); font-size:0.82rem; text-align:center; justify-content:center; }
.bir-finder-empty i { font-size:1rem; }

.bir-bracket-placeholder { display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:28px 16px; }
.bir-bracket-placeholder i { font-size:1.6rem; color:var(--text-400,#8b93a1); }
.bir-bracket-title { font-size:0.9rem; font-weight:700; color:var(--text-900,#1b2430); }
.bir-bracket-desc { font-size:0.78rem; color:var(--text-500,#6b7280); line-height:1.5; }
.bir-bracket-link { margin-top:6px; font-size:0.78rem; font-weight:600; color:var(--info-blue,#3b82c4); text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.bir-bracket-link:hover { text-decoration:underline; }

@media (max-width: 1100px) {
  .bir-row { grid-template-columns:1fr; }
  .bir-col-side { position:static; width:auto; min-width:0; }
}

/* ============================================
   BIR RESPONSIVE OVERRIDES
   ============================================ */

/* Prevent horizontal overflow */
.bir-module {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.bir-card {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
}

/* Summary bar: tablet/mobile responsive */
@media (max-width: 1100px) {
    .bir-summary-bar {
        gap: 10px;
    }
    .bir-summary-item {
        min-width: 0;
        flex: 1 1 calc(50% - 10px);
        max-width: calc(50% - 10px);
        padding: 14px 16px;
    }
    .bir-summary-item > div {
        min-width: 0;
    }
    .bir-summary-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    .bir-summary-value {
        font-size: 1.1rem;
    }
    .bir-summary-label {
        font-size: 0.7rem;
    }
    .bir-summary-desc {
        font-size: 0.6rem;
    }
}

@media (max-width: 400px) {
    .bir-summary-bar {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .bir-summary-item {
        max-width: 100%;
        flex: 1 1 auto;
    }
}

/* Card head wrapping */
.bir-card-head {
    flex-wrap: wrap;
    gap: 8px;
}

/* ============================================
   MOBILE CARD TABLE (max-width: 768px)
   ============================================ */
@media (max-width: 768px) {
    .bir-card-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .bir-table-wrap {
        overflow: visible !important;
        flex: none !important;
    }

    .bir-table,
    .bir-table thead,
    .bir-table tbody,
    .bir-table th,
    .bir-table td,
    .bir-table tr {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .bir-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .bir-table thead {
        display: none;
    }

    .bir-table tr {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .bir-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid var(--hairline, #dde3ea);
        text-align: right;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .bir-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .bir-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        text-align: left;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .bir-table td:last-child {
        justify-content: flex-end;
    }

    .bir-stamp {
        font-size: 0.72rem;
        padding: 3px 10px;
    }

    .bir-email-payroll-btn {
        height: 40px;
        min-width: 40px;
        width: 40px;
        font-size: 1rem;
    }
}

/* ============================================
   PAGINATION RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .bir-pagination {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .bir-pagination-info {
        text-align: center;
        order: 1;
    }
    .bir-pagination-nav {
        order: 0;
    }
}

/* ============================================
   FINDER GRID RESPONSIVE
   ============================================ */
@media (max-width: 400px) {
    .bir-finder-grid {
        grid-template-columns: 1fr;
    }
    .bir-finder-cell--total {
        order: -1;
    }
}

.bir-finder-input {
    min-width: 0;
}

/* Finder result value wrapping */
.bir-finder-val {
    overflow-wrap: anywhere;
    word-break: break-word;
}

/* Finder row wrapping */
.bir-finder-row {
    flex-wrap: wrap;
    gap: 6px;
}
.bir-finder-key {
    flex-shrink: 0;
}
</style>

<div class="bir-module">
  <div class="bir-summary-bar">
    <a class="bir-summary-item <?= $filter === 'all' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=all">
      <div class="bir-summary-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="bir-summary-value"><?= number_format($totalEmployees) ?></div>
        <div class="bir-summary-label">Total Employees</div>
      </div>
    </a>
    <a class="bir-summary-item <?= $filter === 'submitted' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=submitted">
      <div class="bir-summary-icon green"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="bir-summary-value"><?= number_format($submitted) ?></div>
        <div class="bir-summary-label">Submitted</div>
      </div>
    </a>
    <a class="bir-summary-item <?= $filter === 'pending' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=pending">
      <div class="bir-summary-icon amber"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="bir-summary-value"><?= number_format($pending) ?></div>
        <div class="bir-summary-label">Pending</div>
      </div>
    </a>
     <a class="bir-summary-item <?= $filter === 'rejected' ? 'bir-summary-active' : '' ?>" href="?page=bir-monitoring&filter=rejected">
       <div class="bir-summary-icon red"><i class="bi bi-x-circle"></i></div>
       <div>
         <div class="bir-summary-value"><?= number_format($rejected) ?></div>
         <div class="bir-summary-label">Rejected</div>
       </div>
     </a>
    <div class="bir-summary-item">
      <div class="bir-summary-icon seal"><i class="bi bi-calendar-check"></i></div>
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
          <h3><i class="bi bi-list-ul"></i> Recent BIR Submissions</h3>
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
          <h3><i class="bi bi-cash-coin"></i> BIR Withholding Tax Reference</h3>
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
          <h3><i class="bi bi-cash-stack"></i> BIR Tax Brackets</h3>
        </div>
        <div class="bir-card-body">
          <div class="bir-bracket-placeholder">
            <i class="bi bi-cash-stack"></i>
            <div class="bir-bracket-title">BIR Withholding Tax Table</div>
            <div class="bir-bracket-desc">View the current BIR withholding tax schedule based on monthly compensation.</div>
            <a href="?page=government-contribution-brackets&type=bir" class="bir-bracket-link">Open BIR Tax Table <i class="bi bi-arrow-right-short"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
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

    var pageSize = 15;
    var totalItems = rows.length;
    var totalPages = Math.ceil(totalItems / pageSize);
    var currentPage = 1;

    var infoEl = document.getElementById('birPaginationInfo');
    var navEl = document.getElementById('birPaginationNav');
    var paginationEl = document.getElementById('birRecentPagination');

    paginationEl.style.display = 'flex';

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

