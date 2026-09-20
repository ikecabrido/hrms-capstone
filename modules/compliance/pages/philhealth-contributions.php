<?php

$pageTitle = 'PhilHealth Monitoring';
$moduleHeaderImage = '/hrms-capstone/modules/compliance/assets/philhealth.webp';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = $db ?? null;
if (!($db instanceof PDO)) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_payroll_philhealth') {
    if (!($db instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Database connection is unavailable.']);
        exit;
    }
    $contributionId = isset($_POST['contribution_id']) ? (int) $_POST['contribution_id'] : 0;
    $response = ['success' => false, 'message' => 'Invalid request.'];

    if ($contributionId > 0) {
        try {
             $stmt = $db->prepare("
                        SELECT c.id, c.employee_id, c.status, c.contribution_number, c.created_at, c.updated_at,
                               CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code AS employee_no, e.email AS employee_email
                 FROM lc_philhealth_contributions c
                 LEFT JOIN em_employees e ON e.employee_id = c.employee_id
                 WHERE c.id = :id AND c.contribution_type = 'philhealth'
                 LIMIT 1
             ");
             $stmt->execute([':id' => $contributionId]);
             $record = $stmt->fetch(PDO::FETCH_ASSOC);

             if (!$record) {
                 $response['message'] = 'PhilHealth contribution record not found.';
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
                             $subject = 'PhilHealth Contribution Review Required - ' . $employeeName . ' (' . $employeeNo . ')';
                             $body = '<h2>PhilHealth Contribution Review Required</h2>' .
                                 '<p><strong>Employee:</strong> ' . htmlspecialchars($employeeName) . ' (' . htmlspecialchars($employeeNo) . ')</p>' .
                                 '<p><strong>Contribution Number:</strong> ' . htmlspecialchars($contributionNumber) . '</p>' .
                                 '<p><strong>Current Status:</strong> ' . htmlspecialchars($statusLabel) . '</p>' .
                                 '<p><strong>Date Submitted:</strong> ' . date('F j, Y', strtotime($record['created_at'])) . '</p>' .
                                 '<p><strong>Last Updated:</strong> ' . date('F j, Y', strtotime($record['updated_at'])) . '</p>' .
                                 '<p><strong>Required Action:</strong></p>' .
                                 '<ul>' .
                                 '<li>Review the PhilHealth contribution submission for this employee.</li>' .
                                 '<li>Verify the contribution details and amount.</li>' .
                                 '<li>Update the status in the PhilHealth Monitoring module.</li>' .
                                 '<li>Ensure payroll deductions are aligned with the PhilHealth contribution schedule.</li>' .
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

 function philhealth_value(?PDO $db, string $sql, $default = 0) {
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
 function philhealth_all(?PDO $db, string $sql, array $params = []): array {
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

 $totalEmployees = 0;
 $submitted = 0;
 $pending = 0;
 $rejected = 0;

 if ($db instanceof PDO) {
     $totalEmployees = (int) philhealth_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')");
     $submitted      = (int) philhealth_value($db, "SELECT COUNT(*) FROM lc_philhealth_contributions WHERE status = 'Submitted'");
     $pending        = (int) philhealth_value($db, "SELECT COUNT(*) FROM lc_philhealth_contributions WHERE status = 'Pending'");
     $rejected       = (int) philhealth_value($db, "SELECT COUNT(*) FROM lc_philhealth_contributions WHERE status = 'Rejected'");
 }

 $nextRemittanceDate = new DateTime('now');
 $nextRemittanceDate->modify('last day of next month');
 $daysUntilRemittance = (int) $nextRemittanceDate->diff(new DateTime('now'))->days;
 $remittanceLabel = 'Next Remittance';
 $remittanceDateStr = $nextRemittanceDate->format('F j');
 $remittanceDaysStr = $daysUntilRemittance . ' day' . ($daysUntilRemittance !== 1 ? 's' : '') . ' left';

 $phBrackets = [];
 if ($db instanceof PDO) {
     try {
          $stmt = $db->query("SELECT min_salary, max_salary, employee_share, employer_share FROM pr_philhealth_rates WHERE is_active = 1 ORDER BY min_salary ASC");
         $phBrackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
     } catch (Throwable $e) {
         try {
              $stmt = $db->query("SELECT min_salary, max_salary, employee_share, employer_share FROM pr_philhealth_rates ORDER BY min_salary ASC");
             $phBrackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (Throwable $e2) {
             $phBrackets = [];
         }
     }
 }

 $phBracketsJson = json_encode($phBrackets, JSON_NUMERIC_CHECK);
 if ($phBracketsJson === false) {
     $phBracketsJson = '[]';
 }

 $filter = trim((string)($_GET['filter'] ?? 'all'));

 $recentQuery = "
      SELECT c.*, CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.employee_code AS employee_no, e.email AS employee_email
      FROM lc_philhealth_contributions c
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

$recent = philhealth_all($db, $recentQuery, $recentParams);
$recentActivity = array_slice($recent, 0, 6);
?>
<style>
.philhealth-module { padding: 4px 2px 24px; }
.philhealth-breadcrumb { margin-bottom:10px; }
.philhealth-breadcrumb .breadcrumb { background:transparent; padding:0; margin:0; font-size:0.8rem; }
.philhealth-breadcrumb .breadcrumb-item a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.philhealth-breadcrumb .breadcrumb-item a:hover { text-decoration:underline; }
.philhealth-breadcrumb .breadcrumb-item.active { color:var(--text-500,#6b7280); }

.philhealth-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.philhealth-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.philhealth-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.philhealth-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
.philhealth-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.philhealth-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.philhealth-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.philhealth-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.philhealth-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.philhealth-summary-icon.seal { background:rgba(168,121,31,.12); color:#8a6318; }
.philhealth-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.philhealth-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }
.philhealth-summary-desc { font-size:0.62rem; color:var(--text-400,#8b93a1); margin-top:2px; font-weight:600; }

.philhealth-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.philhealth-col-main { min-width:0; }
.philhealth-col-side { width:380px; flex-shrink:0; }

.philhealth-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.philhealth-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.philhealth-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.philhealth-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.philhealth-card-body {
  display:flex;
  flex-direction:column;
  max-height: 540px;
  overflow: hidden;
}
.philhealth-table-wrap {
  overflow: auto;
  flex: 1 1 auto;
}
.philhealth-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
.philhealth-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
.philhealth-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); }
.philhealth-table tr:last-child td { border-bottom:none; }
.philhealth-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.philhealth-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.philhealth-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
  .philhealth-stamp-violation { background:rgba(214,72,74,.12); color:#a3272a; }

  /* PhilHealth Pagination */
  .philhealth-pagination { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:14px; flex-wrap:wrap; font-size:0.8rem; color:var(--text-600,#4a505a); }
  .philhealth-pagination-info { font-size:0.8rem; color:var(--text-600,#4a505a); white-space:nowrap; }
  .philhealth-pagination-nav { display:inline-flex; align-items:center; gap:4px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:8px; overflow:hidden; }
  .philhealth-pagination-nav .philhealth-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:none; background:transparent; font-size:0.8rem; color:var(--text-700,#3b4252); cursor:pointer; transition:all .15s ease; }
  .philhealth-pagination-nav .philhealth-page-btn:hover { background:rgba(59,130,196,.08); color:var(--info-blue,#3b82c4); }
  .philhealth-pagination-nav .philhealth-page-btn:disabled { opacity:0.4; cursor:not-allowed; }
  .philhealth-pagination-nav .philhealth-page-btn--active { background:var(--info-blue,#3b82c4); color:#fff; font-weight:600; }
  .philhealth-pagination-nav .philhealth-page-ellipsis { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem; color:var(--text-400,#8b93a1); user-select:none; }

  .philhealth-email-payroll-btn {
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
.philhealth-email-payroll-btn:hover:not(:disabled) {
  border-color: var(--seal-gold, #a8791f);
  color: var(--seal-gold, #a8791f);
  box-shadow: 0 0 0 3px rgba(168, 121, 31, 0.08);
}
.philhealth-email-payroll-btn:disabled {
  background: var(--paper, #eef1f5);
  border-color: var(--hairline, #dde3ea);
  color: var(--text-400, #8b95a4);
  cursor: not-allowed;
  box-shadow: none;
}

.philhealth-finder-card { border-color:var(--seal-gold-light,#f4e6c9); }
.philhealth-finder-form { margin-bottom:14px; }
.philhealth-finder-label { display:block; font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:6px; }
.philhealth-finder-input-wrap { display:flex; align-items:center; gap:8px; border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:10px 12px; background:#fff; transition:border-color .15s ease, box-shadow .15s ease; }
.philhealth-finder-input-wrap:focus-within { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }
.philhealth-finder-prefix { font-weight:700; color:var(--text-900,#1b2430); font-size:0.95rem; }
.philhealth-finder-input { flex:1; border:0; outline:none; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); background:transparent; min-width:0; }
.philhealth-finder-input::placeholder { color:var(--text-400,#8b93a1); font-weight:500; }

.philhealth-finder-result { background:var(--paper,#eef1f5); border:1px solid var(--border,#e4e8ee); border-radius:12px; padding:14px; }
.philhealth-finder-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.philhealth-finder-key { font-size:0.76rem; font-weight:700; color:var(--text-600,#5a6779); text-transform:uppercase; letter-spacing:.4px; }
.philhealth-finder-val { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.philhealth-finder-divider { height:1px; background:var(--border,#e4e8ee); margin:10px 0; }
.philhealth-finder-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.philhealth-finder-cell { display:flex; flex-direction:column; gap:2px; padding:10px; background:#fff; border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.philhealth-finder-cell--total { background:rgba(168,121,31,.08); border-color:rgba(168,121,31,.25); }
.philhealth-finder-cell-label { font-size:0.68rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.3px; }
.philhealth-finder-cell-value { font-size:0.95rem; font-weight:800; color:var(--text-900,#1b2430); }
.philhealth-ee { color:#1c5a8a; }
.philhealth-er { color:#1f7a52; }
.philhealth-total { color:#8a6318; }
.philhealth-finder-empty { display:flex; align-items:center; gap:8px; padding:16px; color:var(--text-400,#8b93a1); font-size:0.82rem; text-align:center; justify-content:center; }
.philhealth-finder-empty i { font-size:1rem; }

.philhealth-bracket-placeholder { display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:28px 16px; }
.philhealth-bracket-placeholder i { font-size:1.6rem; color:var(--text-400,#8b93a1); }
.philhealth-bracket-title { font-size:0.9rem; font-weight:700; color:var(--text-900,#1b2430); }
.philhealth-bracket-desc { font-size:0.78rem; color:var(--text-500,#6b7280); line-height:1.5; }
.philhealth-bracket-link { margin-top:6px; font-size:0.78rem; font-weight:600; color:var(--info-blue,#3b82c4); text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.philhealth-bracket-link:hover { text-decoration:underline; }

@media (max-width: 1100px) {
  .philhealth-row { grid-template-columns:1fr; }
  .philhealth-col-side { position:static; width:auto; min-width:0; }
}

/* ============================================
   PhilHealth RESPONSIVE OVERRIDES
   ============================================ */

/* Prevent horizontal overflow */
.philhealth-module {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.philhealth-card {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
}

/* Summary bar: tablet/mobile responsive */
@media (max-width: 1100px) {
    .philhealth-summary-bar {
        gap: 10px;
    }
    .philhealth-summary-item {
        min-width: 0;
        flex: 1 1 calc(50% - 10px);
        max-width: calc(50% - 10px);
        padding: 14px 16px;
    }
    .philhealth-summary-item > div {
        min-width: 0;
    }
    .philhealth-summary-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    .philhealth-summary-value {
        font-size: 1.1rem;
    }
    .philhealth-summary-label {
        font-size: 0.7rem;
    }
    .philhealth-summary-desc {
        font-size: 0.6rem;
    }
}

@media (max-width: 400px) {
    .philhealth-summary-bar {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .philhealth-summary-item {
        max-width: 100%;
        flex: 1 1 auto;
    }
}

/* Card head wrapping */
.philhealth-card-head {
    flex-wrap: wrap;
    gap: 8px;
}

/* ============================================
   MOBILE CARD TABLE (max-width: 768px)
   ============================================ */
@media (max-width: 768px) {
    .philhealth-card-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .philhealth-table-wrap {
        overflow: visible !important;
        flex: none !important;
    }

    .philhealth-table,
    .philhealth-table thead,
    .philhealth-table tbody,
    .philhealth-table th,
    .philhealth-table td,
    .philhealth-table tr {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .philhealth-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .philhealth-table thead {
        display: none;
    }

    .philhealth-table tr {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .philhealth-table td {
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

    .philhealth-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .philhealth-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        text-align: left;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .philhealth-table td:last-child {
        justify-content: flex-end;
    }

    .philhealth-stamp {
        font-size: 0.72rem;
        padding: 3px 10px;
    }

    .philhealth-email-payroll-btn {
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
    .philhealth-pagination {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .philhealth-pagination-info {
        text-align: center;
        order: 1;
    }
    .philhealth-pagination-nav {
        order: 0;
    }
}

/* ============================================
   FINDER GRID RESPONSIVE
   ============================================ */
@media (max-width: 400px) {
    .philhealth-finder-grid {
        grid-template-columns: 1fr;
    }
    .philhealth-finder-cell--total {
        order: -1;
    }
}

.philhealth-finder-input {
    min-width: 0;
}

/* Finder result value wrapping */
.philhealth-finder-val {
    overflow-wrap: anywhere;
    word-break: break-word;
}

/* Finder row wrapping */
.philhealth-finder-row {
    flex-wrap: wrap;
    gap: 6px;
}
.philhealth-finder-key {
    flex-shrink: 0;
}
</style>

<section class="philhealth-module">
  <div class="philhealth-summary-bar">
    <a class="philhealth-summary-item <?= $filter === 'all' ? 'philhealth-summary-active' : '' ?>" href="?page=philhealth-contributions&filter=all">
      <div class="philhealth-summary-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="philhealth-summary-value"><?= number_format($totalEmployees) ?></div>
        <div class="philhealth-summary-label">Total Employees</div>
      </div>
    </a>
    <a class="philhealth-summary-item <?= $filter === 'submitted' ? 'philhealth-summary-active' : '' ?>" href="?page=philhealth-contributions&filter=submitted">
      <div class="philhealth-summary-icon green"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="philhealth-summary-value"><?= number_format($submitted) ?></div>
        <div class="philhealth-summary-label">Submitted</div>
      </div>
    </a>
    <a class="philhealth-summary-item <?= $filter === 'pending' ? 'philhealth-summary-active' : '' ?>" href="?page=philhealth-contributions&filter=pending">
      <div class="philhealth-summary-icon amber"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="philhealth-summary-value"><?= number_format($pending) ?></div>
        <div class="philhealth-summary-label">Pending</div>
      </div>
    </a>
    <a class="philhealth-summary-item <?= $filter === 'rejected' ? 'philhealth-summary-active' : '' ?>" href="?page=philhealth-contributions&filter=rejected">
      <div class="philhealth-summary-icon red"><i class="bi bi-x-circle"></i></div>
      <div>
        <div class="philhealth-summary-value"><?= number_format($rejected) ?></div>
        <div class="philhealth-summary-label">Rejected</div>
      </div>
    </a>
    <div class="philhealth-summary-item">
      <div class="philhealth-summary-icon seal"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div class="philhealth-summary-value"><?= htmlspecialchars($remittanceDateStr) ?></div>
        <div class="philhealth-summary-label"><?= htmlspecialchars($remittanceLabel) ?></div>
        <div class="philhealth-summary-desc"><?= htmlspecialchars($remittanceDaysStr) ?></div>
      </div>
    </div>
  </div>

  <div class="philhealth-row">
    <div class="philhealth-col-main">
      <div class="philhealth-card">
        <div class="philhealth-card-head">
          <h3><i class="bi bi-list-ul"></i> Recent PhilHealth Submissions</h3>
        </div>
        <div class="philhealth-card-body">
          <?php if (empty($recent)): ?>
            <div class="philhealth-empty">No PhilHealth contributions recorded yet.</div>
          <?php else: ?>
          <div class="philhealth-table-wrap">
            <table class="philhealth-table" id="philhealthRecentTable">
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
                  <td data-label="Status"><span class="philhealth-stamp philhealth-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span></td>
                  <td data-label="Date Submitted"><?= !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : '—' ?></td>
                  <td data-label="Updated"><?= !empty($r['updated_at']) ? date('M d, Y', strtotime($r['updated_at'])) : '—' ?></td>
                  <td data-label="Action">
                    <?php if (in_array($status, ['overdue', 'pending', 'rejected'], true)): ?>
                      <a href="https://www.philhealth.gov.ph/partners/employers/" target="_blank" rel="noopener noreferrer"
                         class="philhealth-email-payroll-btn"
                         title="Go to PhilHealth Employer Portal">
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="philhealth-pagination" id="philhealthRecentPagination" style="display:none;">
            <span class="philhealth-pagination-info" id="philhealthPaginationInfo"></span>
            <nav class="philhealth-pagination-nav" id="philhealthPaginationNav" role="navigation" aria-label="PhilHealth table pagination"></nav>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="philhealth-col-side">
      <div class="philhealth-card philhealth-finder-card">
        <div class="philhealth-card-head">
          <h3><i class="bi bi-cash-coin"></i> PhilHealth Contribution Reference</h3>
        </div>
        <div class="philhealth-card-body">
          <div class="philhealth-finder-form">
            <label class="philhealth-finder-label" for="philhealthSalaryInput">Monthly Salary</label>
            <div class="philhealth-finder-input-wrap">
              <span class="philhealth-finder-prefix">₱</span>
              <input type="number" id="philhealthSalaryInput" class="philhealth-finder-input" placeholder="25,000" min="0" step="1" inputmode="numeric">
            </div>
          </div>
          <div class="philhealth-finder-result" id="philhealthFinderResult" style="display:none;">
            <div class="philhealth-finder-row">
              <span class="philhealth-finder-key">Matching Bracket</span>
              <span class="philhealth-finder-val" id="philhealthFinderBracket">—</span>
            </div>
            <div class="philhealth-finder-divider"></div>
            <div class="philhealth-finder-grid">
              <div class="philhealth-finder-cell">
                <span class="philhealth-finder-cell-label">Employee (EE)</span>
                <span class="philhealth-finder-cell-value philhealth-ee" id="philhealthFinderEE">₱0.00</span>
              </div>
              <div class="philhealth-finder-cell">
                <span class="philhealth-finder-cell-label">Employer (ER)</span>
                <span class="philhealth-finder-cell-value philhealth-er" id="philhealthFinderER">₱0.00</span>
              </div>
              <div class="philhealth-finder-cell philhealth-finder-cell--total">
                <span class="philhealth-finder-cell-label">Total</span>
                <span class="philhealth-finder-cell-value philhealth-total" id="philhealthFinderTotal">₱0.00</span>
              </div>
            </div>
          </div>
          <div class="philhealth-finder-empty" id="philhealthFinderEmpty">
            <i class="bi bi-info-circle"></i>
            <span>Enter a monthly salary to see the applicable contribution.</span>
          </div>
        </div>
      </div>
      <div class="philhealth-card" id="philhealthBracketCard">
        <div class="philhealth-card-head">
          <h3><i class="bi bi-cash-stack"></i> PhilHealth Contribution Brackets</h3>
        </div>
        <div class="philhealth-card-body">
          <div class="philhealth-bracket-placeholder">
            <i class="bi bi-cash-stack"></i>
            <div class="philhealth-bracket-title">PhilHealth Contribution Bracket</div>
            <div class="philhealth-bracket-desc">View the complete PhilHealth contribution schedule by monthly salary credit.</div>
            <a href="?page=government-contribution-brackets&type=philhealth" class="philhealth-bracket-link">Open PhilHealth Contribution Table <i class="bi bi-arrow-right-short"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
(function() {
  var brackets = <?= $phBracketsJson ?>;
  var salaryInput = document.getElementById('philhealthSalaryInput');
  var resultBox = document.getElementById('philhealthFinderResult');
  var emptyBox = document.getElementById('philhealthFinderEmpty');
  var bracketLabel = document.getElementById('philhealthFinderBracket');
  var eeLabel = document.getElementById('philhealthFinderEE');
  var erLabel = document.getElementById('philhealthFinderER');
  var totalLabel = document.getElementById('philhealthFinderTotal');

  function formatMoney(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function findBracket(salary) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = parseFloat(b.min_salary);
      var max = b.max_salary !== null ? parseFloat(b.max_salary) : null;
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
    var min = parseFloat(b.min_salary);
    var max = b.max_salary !== null ? parseFloat(b.max_salary) : null;
    var rangeTxt = max !== null
      ? '₱' + min.toLocaleString('en-PH') + ' – ₱' + max.toLocaleString('en-PH')
      : '₱' + min.toLocaleString('en-PH') + ' and above';
    var ee = salary * parseFloat(b.employee_share);
    var er = salary * parseFloat(b.employer_share);
    var total = ee + er;

    bracketLabel.textContent = rangeTxt;
    eeLabel.textContent = formatMoney(ee);
    erLabel.textContent = formatMoney(er);
    totalLabel.textContent = formatMoney(total);

    resultBox.style.display = 'block';
    emptyBox.style.display = 'none';
  }

  if (salaryInput) {
    salaryInput.addEventListener('input', updateFinder);
    salaryInput.addEventListener('change', updateFinder);
  }

  /* PhilHealth Recent Table Pagination */
  (function() {
    var table = document.getElementById('philhealthRecentTable');
    if (!table) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    if (rows.length === 0) return;

    var pageSize = 15;
    var totalItems = rows.length;
    var totalPages = Math.ceil(totalItems / pageSize);
    var currentPage = 1;

    var infoEl = document.getElementById('philhealthPaginationInfo');
    var navEl = document.getElementById('philhealthPaginationNav');
    var paginationEl = document.getElementById('philhealthRecentPagination');

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
      prevBtn.className = 'philhealth-page-btn';
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
      nextBtn.className = 'philhealth-page-btn';
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
      btn.className = 'philhealth-page-btn' + (pageNum === currentPage ? ' philhealth-page-btn--active' : '');
      btn.textContent = pageNum;
      btn.addEventListener('click', function() {
        currentPage = pageNum;
        renderPage();
      });
      navEl.appendChild(btn);
    }

    function appendEllipsis() {
      var span = document.createElement('span');
      span.className = 'philhealth-page-ellipsis';
      span.textContent = '…';
      navEl.appendChild(span);
    }

    renderPage();
  })();
})();
</script>
