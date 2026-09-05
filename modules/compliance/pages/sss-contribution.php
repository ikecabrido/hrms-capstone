<?php

$pageTitle = 'SSS Monitoring';
$moduleHeaderImage = '/hrms-capstone/modules/compliance/assets/sss.png';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db) || !($db instanceof PDO)) {
    try {
        if (class_exists('Database')) {
            $db = (new Database())->getConnection();
        } else {
            require_once __DIR__ . '/../../../database/db.php';
            $db = (new Database())->getConnection();
        }
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_payroll_sss') {
    $contributionId = isset($_POST['contribution_id']) ? (int) $_POST['contribution_id'] : 0;
    $response = ['success' => false, 'message' => 'Invalid request.'];

    if ($contributionId > 0 && $db instanceof PDO) {
        try {
            $stmt = $db->prepare("
                SELECT c.id, c.employee_id, c.status, c.contribution_number, c.created_at, c.updated_at,
                       CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code AS employee_no, e.email AS employee_email
                FROM lc_sss_contributions c
                LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                WHERE c.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $contributionId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $response['message'] = 'SSS contribution record not found.';
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
                            $subject = 'SSS Contribution Review Required - ' . $employeeName . ' (' . $employeeNo . ')';
                            $body = '<h2>SSS Contribution Review Required</h2>' .
                                '<p><strong>Employee:</strong> ' . htmlspecialchars($employeeName) . ' (' . htmlspecialchars($employeeNo) . ')</p>' .
                                '<p><strong>Contribution Number:</strong> ' . htmlspecialchars($contributionNumber) . '</p>' .
                                '<p><strong>Current Status:</strong> ' . htmlspecialchars($statusLabel) . '</p>' .
                                '<p><strong>Date Submitted:</strong> ' . date('F j, Y', strtotime($record['created_at'])) . '</p>' .
                                '<p><strong>Last Updated:</strong> ' . date('F j, Y', strtotime($record['updated_at'])) . '</p>' .
                                '<p><strong>Required Action:</strong></p>' .
                                '<ul>' .
                                '<li>Review the SSS contribution submission for this employee.</li>' .
                                '<li>Verify the contribution details and amount.</li>' .
                                '<li>Update the status in the SSS Monitoring module.</li>' .
                                '<li>Ensure payroll deductions are aligned with the SSS contribution schedule.</li>' .
                                '</ul>' .
                                '<p><em>This is an automated notice from the HR Legal Compliance Management System.</em></p>';

                            $altBody = strip_tags($body);

                            $mailer->send(
                                [['email' => $payrollEmail, 'name' => $payrollName]],
                                $subject,
                                $body,
                                $altBody
                            );

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

function sss_value(?PDO $db, string $sql, $default = 0) {
    if (!$db instanceof PDO) {
        return $default;
    }

    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function sss_all(?PDO $db, string $sql, array $params = []): array {
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

$totalEmployees = (int) sss_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')");
$submitted      = (int) sss_value($db, "SELECT COUNT(*) FROM lc_sss_contributions WHERE status = 'Submitted'");
$pending        = (int) sss_value($db, "SELECT COUNT(*) FROM lc_sss_contributions WHERE status = 'Pending'");
$overdue        = (int) sss_value($db, "SELECT COUNT(*) FROM lc_sss_contributions WHERE status = 'Overdue'");

$nextRemittanceDate = new DateTime('now');
$nextRemittanceDate->modify('last day of next month');
$daysUntilRemittance = (int) $nextRemittanceDate->diff(new DateTime('now'))->days;
$remittanceLabel = 'Next Remittance';
$remittanceDateStr = $nextRemittanceDate->format('F j');
$remittanceDaysStr = $daysUntilRemittance . ' day' . ($daysUntilRemittance !== 1 ? 's' : '') . ' left';

$sssBrackets = [];
if ($db instanceof PDO) {
    try {
        $stmt = $db->query("SELECT min_compensation, max_compensation, employee_rate, employer_rate FROM pr_sss_contribution_rates WHERE is_active = 1 ORDER BY min_compensation ASC");
        $sssBrackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $sssBrackets = [];
    }
}

$sssBracketsJson = json_encode($sssBrackets, JSON_NUMERIC_CHECK);
if ($sssBracketsJson === false) {
    $sssBracketsJson = '[]';
}

$filter = trim((string)($_GET['filter'] ?? 'all'));

$whereClause = '';
if ($filter === 'submitted') {
    $whereClause = " WHERE c.status = 'Submitted'";
} elseif ($filter === 'pending') {
    $whereClause = " WHERE c.status = 'Pending'";
} elseif ($filter === 'overdue') {
    $whereClause = " WHERE c.status = 'Overdue'";
}

$countQuery = "
    SELECT COUNT(*) AS total
    FROM lc_sss_contributions c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    $whereClause
";
$totalRows = (int) sss_value($db, $countQuery, 0);

$perPage = 15;
$totalPages = ($perPage > 0 && $totalRows > 0) ? (int) ceil($totalRows / $perPage) : 1;
$currentPage = isset($_GET['sss_page']) ? max(1, (int) $_GET['sss_page']) : 1;
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

$recentQuery = "
    SELECT c.*,
           CONCAT(e.first_name, ' ', e.last_name) AS full_name,
           e.employee_code AS employee_no,
           e.email AS employee_email
    FROM lc_sss_contributions c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    $whereClause
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$recent = sss_all($db, $recentQuery, []);
?>
<style>
.sss-module { padding: 4px 2px 24px; }
.sss-breadcrumb { margin-bottom:10px; }
.sss-breadcrumb .breadcrumb { background:transparent; padding:0; margin:0; font-size:0.8rem; }
.sss-breadcrumb .breadcrumb-item a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.sss-breadcrumb .breadcrumb-item a:hover { text-decoration:underline; }
.sss-breadcrumb .breadcrumb-item.active { color:var(--text-500,#6b7280); }

.sss-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.sss-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.sss-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.sss-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
.sss-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.sss-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.sss-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.sss-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.sss-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.sss-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
.sss-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.sss-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }
.sss-summary-desc { font-size:0.62rem; color:var(--text-400,#8b93a1); margin-top:2px; font-weight:600; }

.sss-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.sss-col-main { min-width:0; }
.sss-col-side { width:380px; flex-shrink:0; }

.sss-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.sss-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.sss-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.sss-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

  .sss-card-body { 
    display:flex;
    flex-direction:column;
    max-height: 540px;
    overflow: hidden;
  }
  .sss-table-wrap { 
    overflow: auto;
    flex: 1 1 auto;
  }
.sss-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.sss-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
.sss-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); }
.sss-table tr:last-child td { border-bottom:none; }
.sss-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.sss-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.sss-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
  .sss-stamp-violation { background:rgba(214,72,74,.12); color:#a3272a; }

  /* SSS Pagination */
  .sss-pagination { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:14px; flex-wrap:wrap; font-size:0.8rem; color:var(--text-600,#4a505a); }
  .sss-pagination-info { font-size:0.8rem; color:var(--text-600,#4a505a); white-space:nowrap; }
  .sss-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:8px; overflow:hidden; }
  .sss-pagination-nav .sss-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:1px solid var(--border,#e4e8ee); border-radius:6px; background:transparent; font-size:0.8rem; color:var(--text-700,#3b4252); cursor:pointer; text-decoration:none; transition:all .15s ease; }
  .sss-pagination-nav .sss-page-btn:hover { background:rgba(59,130,196,.08); border-color:var(--info-blue,#3b82c4); color:var(--info-blue,#3b82c4); }
  .sss-pagination-nav .sss-page-btn[aria-disabled="true"] { opacity:0.4; cursor:not-allowed; }
  .sss-pagination-nav .sss-page-btn--active { background:var(--info-blue,#3b82c4); color:#fff; border-color:var(--info-blue,#3b82c4); font-weight:600; }
  .sss-pagination-nav .sss-page-ellipsis { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem; color:var(--text-400,#8b93a1); user-select:none; }

.sss-table-search { position:relative; }
.sss-table-search i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-400,#8b93a1); font-size:0.8rem; pointer-events:none; }
.sss-table-search input { padding:7px 10px 7px 30px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.78rem; outline:none; width:220px; transition:border-color .15s ease, box-shadow .15s ease; }
.sss-table-search input:focus { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }

.sss-finder-card { border-color:var(--seal-gold-light,#f4e6c9); }
.sss-finder-form { margin-bottom:14px; }
.sss-finder-label { display:block; font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:6px; }
.sss-finder-input-wrap { display:flex; align-items:center; gap:8px; border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:10px 12px; background:#fff; transition:border-color .15s ease, box-shadow .15s ease; }
.sss-finder-input-wrap:focus-within { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }
.sss-finder-prefix { font-weight:700; color:var(--text-900,#1b2430); font-size:0.95rem; }
.sss-finder-input { flex:1; border:0; outline:none; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); background:transparent; min-width:0; }
.sss-finder-input::placeholder { color:var(--text-400,#8b93a1); font-weight:500; }

.sss-finder-result { background:var(--paper,#eef1f5); border:1px solid var(--border,#e4e8ee); border-radius:12px; padding:14px; }
.sss-finder-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.sss-finder-key { font-size:0.76rem; font-weight:700; color:var(--text-600,#5a6779); text-transform:uppercase; letter-spacing:.4px; }
.sss-finder-val { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.sss-finder-divider { height:1px; background:var(--border,#e4e8ee); margin:10px 0; }
.sss-finder-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.sss-finder-cell { display:flex; flex-direction:column; gap:2px; padding:10px; background:#fff; border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.sss-finder-cell--total { background:rgba(168,121,31,.08); border-color:rgba(168,121,31,.25); }
.sss-finder-cell-label { font-size:0.68rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.3px; }
.sss-finder-cell-value { font-size:0.95rem; font-weight:800; color:var(--text-900,#1b2430); }
.sss-ee { color:#1c5a8a; }
.sss-er { color:#1f7a52; }
.sss-ec { color:#8a6318; }
.sss-total { color:#8a6318; }
.sss-finder-empty { display:flex; align-items:center; gap:8px; padding:16px; color:var(--text-400,#8b93a1); font-size:0.82rem; text-align:center; justify-content:center; }
.sss-finder-empty i { font-size:1rem; }
.sss-view-all { font-size:0.78rem; font-weight:600; color:var(--info-blue,#3b82c4); text-decoration:none; }
.sss-view-all:hover { text-decoration:underline; }

.sss-email-payroll-btn {
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
.sss-email-payroll-btn:hover:not(:disabled) {
  border-color: var(--seal-gold, #a8791f);
  color: var(--seal-gold, #a8791f);
  box-shadow: 0 0 0 3px rgba(168, 121, 31, 0.08);
}
.sss-email-payroll-btn:disabled {
  background: var(--paper, #eef1f5);
  border-color: var(--hairline, #dde3ea);
  color: var(--text-400, #8b95a4);
  cursor: not-allowed;
  box-shadow: none;
}

.sss-activity-list { display:flex; flex-direction:column; }
.sss-activity-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid var(--border,#e4e8ee); }
.sss-activity-item:last-child { border-bottom:none; }
.sss-activity-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:6px; }
.sss-activity-dot-compliant { background:#1f7a52; }
.sss-activity-dot-pending { background:#d99a2b; }
.sss-activity-dot-violation { background:#d6484a; }
.sss-activity-body { flex:1; min-width:0; }
.sss-activity-text { font-size:0.82rem; font-weight:600; color:var(--text-900,#1b2430); line-height:1.4; }
.sss-activity-meta { display:flex; gap:10px; margin-top:2px; font-size:0.72rem; color:var(--text-400,#8b93a1); font-family:var(--font-mono,'IBM Plex Mono',monospace); }
.sss-activity-name { font-weight:600; color:var(--text-600,#5a6779); }

.sss-bracket-placeholder { display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:28px 16px; }
.sss-bracket-placeholder i { font-size:1.6rem; color:var(--text-400,#8b93a1); }
.sss-bracket-title { font-size:0.9rem; font-weight:700; color:var(--text-900,#1b2430); }
.sss-bracket-desc { font-size:0.78rem; color:var(--text-500,#6b7280); line-height:1.5; }
.sss-bracket-link { margin-top:6px; font-size:0.78rem; font-weight:600; color:var(--info-blue,#3b82c4); text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.sss-bracket-link:hover { text-decoration:underline; }

@media (max-width: 1100px) {
  .sss-row { grid-template-columns:1fr; }
  .sss-col-side { position:static; width:auto; min-width:0; }
}

/* ============================================
   SSS RESPONSIVE OVERRIDES
   ============================================ */

/* Prevent horizontal overflow */
.sss-module {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.sss-card {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
}

/* Summary bar: tablet/mobile responsive */
@media (max-width: 1100px) {
    .sss-summary-bar {
        gap: 10px;
    }
    .sss-summary-item {
        min-width: 0;
        flex: 1 1 calc(50% - 10px);
        max-width: calc(50% - 10px);
        padding: 14px 16px;
    }
    .sss-summary-item > div {
        min-width: 0;
    }
    .sss-summary-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    .sss-summary-value {
        font-size: 1.1rem;
    }
    .sss-summary-label {
        font-size: 0.7rem;
    }
    .sss-summary-desc {
        font-size: 0.6rem;
    }
}

@media (max-width: 400px) {
    .sss-summary-bar {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .sss-summary-item {
        max-width: 100%;
        flex: 1 1 auto;
    }
}

/* Card head wrapping */
.sss-card-head {
    flex-wrap: wrap;
    gap: 8px;
}

/* Search input responsive */
.sss-table-search input {
    max-width: 100%;
}
@media (max-width: 768px) {
    .sss-table-search input {
        width: 100%;
        max-width: 100%;
    }
}

/* ============================================
   MOBILE CARD TABLE (max-width: 768px)
   ============================================ */
@media (max-width: 768px) {
    .sss-card-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .sss-table-wrap {
        overflow: visible !important;
        flex: none !important;
    }

    .sss-table,
    .sss-table thead,
    .sss-table tbody,
    .sss-table th,
    .sss-table td,
    .sss-table tr {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .sss-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .sss-table thead {
        display: none;
    }

    .sss-table tr {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .sss-table td {
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

    .sss-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .sss-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        text-align: left;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .sss-table td:last-child {
        justify-content: flex-end;
    }

    .sss-stamp {
        font-size: 0.72rem;
        padding: 3px 10px;
    }

    .sss-email-payroll-btn {
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
    .sss-pagination {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .sss-pagination-info {
        text-align: center;
        order: 1;
    }
    .sss-pagination-nav {
        order: 0;
    }
}

/* ============================================
   FINDER GRID RESPONSIVE
   ============================================ */
@media (max-width: 400px) {
    .sss-finder-grid {
        grid-template-columns: 1fr;
    }
    .sss-finder-cell--total {
        order: -1;
    }
}

.sss-finder-input {
    min-width: 0;
}

/* Finder result value wrapping */
.sss-finder-val {
    overflow-wrap: anywhere;
    word-break: break-word;
}

/* Finder row wrapping */
.sss-finder-row {
    flex-wrap: wrap;
    gap: 6px;
}
.sss-finder-key {
    flex-shrink: 0;
}
</style>

<section class="sss-module">

    <div class="sss-summary-bar">
    <a class="sss-summary-item <?= $filter === 'all' ? 'sss-summary-active' : '' ?>" href="?page=sss-contribution&filter=all">
      <div class="sss-summary-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="sss-summary-value"><?= number_format($totalEmployees) ?></div>
        <div class="sss-summary-label">Total Employees</div>
      </div>
    </a>
    <a class="sss-summary-item <?= $filter === 'submitted' ? 'sss-summary-active' : '' ?>" href="?page=sss-contribution&filter=submitted">
      <div class="sss-summary-icon green"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="sss-summary-value"><?= number_format($submitted) ?></div>
        <div class="sss-summary-label">Submitted</div>
      </div>
    </a>
    <a class="sss-summary-item <?= $filter === 'pending' ? 'sss-summary-active' : '' ?>" href="?page=sss-contribution&filter=pending">
      <div class="sss-summary-icon amber"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="sss-summary-value"><?= number_format($pending) ?></div>
        <div class="sss-summary-label">Pending</div>
      </div>
    </a>
     <a class="sss-summary-item <?= $filter === 'overdue' ? 'sss-summary-active' : '' ?>" href="?page=sss-contribution&filter=overdue">
       <div class="sss-summary-icon red"><i class="bi bi-exclamation-triangle"></i></div>
       <div>
         <div class="sss-summary-value"><?= number_format($overdue) ?></div>
         <div class="sss-summary-label">Overdue</div>
       </div>
     </a>
    <div class="sss-summary-item">
      <div class="sss-summary-icon seal"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div class="sss-summary-value"><?= htmlspecialchars($remittanceDateStr) ?></div>
        <div class="sss-summary-label"><?= htmlspecialchars($remittanceLabel) ?></div>
        <div class="sss-summary-desc"><?= htmlspecialchars($remittanceDaysStr) ?></div>
      </div>
    </div>
  </div>

   <div class="sss-row">
     <div class="sss-col sss-col-main">
       <div class="sss-card">
           <div class="sss-card-head">
            <h3><i class="bi bi-list-ul"></i> Recent SSS Submissions</h3>
          </div>
         <div class="sss-card-body">
           <?php if (empty($recent)): ?>
             <div class="sss-empty">No SSS contributions recorded yet.</div>
           <?php else: ?>
           <div class="sss-table-wrap">
             <table class="sss-table" id="sssRecentTable">
               <thead>
                  <tr>
                   <th>Employee</th>
                   <th>Employee No.</th>
                   <th>Contribution No.</th>
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
                   else $stampCls = 'pending';
                 ?>
                  <tr>
                    <td data-label="Employee"><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></td>
                    <td data-label="Employee No."><?= htmlspecialchars($r['employee_no'] ?? '—') ?></td>
                    <td data-label="Contribution No."><?= !empty($r['contribution_number']) ? htmlspecialchars($r['contribution_number']) : '—' ?></td>
                    <td data-label="Status"><span class="sss-stamp sss-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span></td>
                    <td data-label="Date Submitted"><?= !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : '—' ?></td>
                    <td data-label="Updated"><?= !empty($r['updated_at']) ? date('M d, Y', strtotime($r['updated_at'])) : '—' ?></td>
                    <td data-label="Action">
                      <?php if (in_array($status, ['overdue', 'pending', 'rejected'], true)): ?>
                        <a href="https://www.sss.gov.ph/employer-er/" target="_blank" rel="noopener noreferrer"
                           class="sss-email-payroll-btn"
                           title="Go to SSS Employer Portal">
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                 <?php endforeach; ?>
               </tbody>
            </table>
             </div>
             <?php if ($totalPages > 1): ?>
             <div class="sss-pagination">
               <span class="sss-pagination-info">
                 Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalRows)) ?> of <?= number_format($totalRows) ?> records
               </span>
               <nav class="sss-pagination-nav" role="navigation" aria-label="SSS table pagination">
                 <?php
                 $baseUrl = '?page=sss-contribution';
                 $qs = [];
                 if ($filter !== 'all') $qs[] = 'filter=' . urlencode($filter);
                 $baseQs = $baseUrl . ($qs ? '&' . implode('&', $qs) : '');
                 $prevPage = $currentPage - 1;
                 $nextPage = $currentPage + 1;
                 ?>
                 <a href="<?= $prevPage >= 1 ? $baseQs . '&sss_page=' . $prevPage : '#' ?>"
                    class="sss-page-btn" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>
                   <i class="bi bi-chevron-left"></i>
                 </a>
                 <?php
                 $range = 2;
                 $start = max(1, $currentPage - $range);
                 $end = min($totalPages, $currentPage + $range);
                 for ($i = $start; $i <= $end; $i++):
                 ?>
                 <a href="<?= $baseQs . '&sss_page=' . $i ?>"
                    class="sss-page-btn <?= $i === $currentPage ? 'sss-page-btn--active' : '' ?>"><?= $i ?></a>
                 <?php endfor; ?>
                 <a href="<?= $nextPage <= $totalPages ? $baseQs . '&sss_page=' . $nextPage : '#' ?>"
                    class="sss-page-btn" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>
                   <i class="bi bi-chevron-right"></i>
                 </a>
               </nav>
             </div>
             <?php endif; ?>
             <?php endif; ?>
          </div>
        </div>
      </div>

     <div class="sss-col sss-col-side">
       <div class="sss-card sss-finder-card">
         <div class="sss-card-head">
           <h3><i class="bi bi-cash-coin"></i> SSS Contribution Reference</h3>
         </div>
         <div class="sss-card-body">
           <div class="sss-finder-form">
             <label class="sss-finder-label" for="sssSalaryInput">Monthly Salary</label>
             <div class="sss-finder-input-wrap">
               <span class="sss-finder-prefix">₱</span>
               <input type="number" id="sssSalaryInput" class="sss-finder-input" placeholder="25,000" min="0" step="1" inputmode="numeric">
             </div>
           </div>
           <div class="sss-finder-result" id="sssFinderResult" style="display:none;">
             <div class="sss-finder-row">
               <span class="sss-finder-key">Matching Bracket</span>
               <span class="sss-finder-val" id="sssFinderBracket">—</span>
             </div>
             <div class="sss-finder-divider"></div>
             <div class="sss-finder-grid">
               <div class="sss-finder-cell">
                 <span class="sss-finder-cell-label">Employee (EE)</span>
                 <span class="sss-finder-cell-value sss-ee" id="sssFinderEE">₱0.00</span>
               </div>
               <div class="sss-finder-cell">
                 <span class="sss-finder-cell-label">Employer (ER)</span>
                 <span class="sss-finder-cell-value sss-er" id="sssFinderER">₱0.00</span>
               </div>
               <div class="sss-finder-cell">
                 <span class="sss-finder-cell-label">EC Contribution</span>
                 <span class="sss-finder-cell-value sss-ec" id="sssFinderEC">₱30.00</span>
               </div>
               <div class="sss-finder-cell sss-finder-cell--total">
                 <span class="sss-finder-cell-label">Total</span>
                 <span class="sss-finder-cell-value sss-total" id="sssFinderTotal">₱0.00</span>
               </div>
             </div>
           </div>
           <div class="sss-finder-empty" id="sssFinderEmpty">
             <i class="bi bi-info-circle"></i>
             <span>Enter a monthly salary to see the applicable contribution.</span>
           </div>
         </div>
       </div>
       <div class="sss-card" id="sssBracketCard">
         <div class="sss-card-head">
           <h3><i class="bi bi-cash-stack"></i> SSS Contribution Brackets</h3>
         </div>
         <div class="sss-card-body">
           <div class="sss-bracket-placeholder">
             <i class="bi bi-cash-stack"></i>
             <div class="sss-bracket-title">SSS Contribution Bracket</div>
             <div class="sss-bracket-desc">View the complete SSS contribution schedule by monthly salary credit.</div>
              <a href="?page=government-contribution-brackets" class="sss-bracket-link">Open SSS Contribution Table <i class="bi bi-arrow-right-short"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
 </div>

<script>
(function() {
  var brackets = <?= $sssBracketsJson ?>;
  var salaryInput = document.getElementById('sssSalaryInput');
  var resultBox = document.getElementById('sssFinderResult');
  var emptyBox = document.getElementById('sssFinderEmpty');
  var bracketLabel = document.getElementById('sssFinderBracket');
  var eeLabel = document.getElementById('sssFinderEE');
  var erLabel = document.getElementById('sssFinderER');
  var ecLabel = document.getElementById('sssFinderEC');
  var totalLabel = document.getElementById('sssFinderTotal');

  function formatMoney(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function findBracket(salary) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = parseFloat(b.min_compensation);
      var max = b.max_compensation !== null ? parseFloat(b.max_compensation) : null;
      if (salary >= min && (max === null || salary <= max)) {
        return b;
      }
    }
    return null;
  }

  function updateFinder() {
    var raw = salaryInput.value.replace(/[^0-9]/g, '');
    if (raw === '') {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var salary = parseFloat(raw);
    if (isNaN(salary) || salary <= 0) {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var b = findBracket(salary);
    if (!b) {
      resultBox.style.display = 'none';
      emptyBox.style.display = 'flex';
      return;
    }
    var min = parseFloat(b.min_compensation);
    var max = b.max_compensation !== null ? parseFloat(b.max_compensation) : null;
    var rangeTxt = max !== null
      ? '₱' + min.toLocaleString('en-PH') + ' – ₱' + max.toLocaleString('en-PH')
      : '₱' + min.toLocaleString('en-PH') + ' and above';
    var ee = salary * parseFloat(b.employee_rate);
    var er = salary * parseFloat(b.employer_rate);
    var ecFixed = 30.00;
    var total = ee + er + ecFixed;

    bracketLabel.textContent = rangeTxt;
    eeLabel.textContent = formatMoney(ee);
    erLabel.textContent = formatMoney(er);
    ecLabel.textContent = formatMoney(ecFixed);
    totalLabel.textContent = formatMoney(total);

    resultBox.style.display = 'block';
    emptyBox.style.display = 'none';
  }

  if (salaryInput) {
    salaryInput.addEventListener('input', updateFinder);
    salaryInput.addEventListener('change', updateFinder);
  }

  /* SSS Recent Table Pagination is handled server-side */
})();
</script>

