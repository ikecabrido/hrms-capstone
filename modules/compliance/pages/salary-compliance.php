<?php
// =============================================================================
// Salary Compliance Validation
// =============================================================================

$pageTitle = 'Salary Compliance Validation';

require_once __DIR__ . '/../../../database/db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db) || !($db instanceof PDO)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}
if (!($db instanceof PDO)) {
  throw new RuntimeException('Unable to establish a database connection.');
}

// ------------------------------------------------------------------
// DB helper functions
// ------------------------------------------------------------------
function sc_value(PDO $db, string $sql, $default = 0, array $params = []): int|float|string|null {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable) {
        return $default;
    }
}
function sc_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return [];
    }
}

// ------------------------------------------------------------------
// Minimum wage lookup
// ------------------------------------------------------------------
$minWage = (float) sc_value($db, "SELECT COALESCE(MAX(minimum_wage), 0) FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'Yes'");
$minWage = $minWage > 0 ? $minWage : 15000.00;

$positionMinWages = [];
$stmt = $db->query("
    SELECT position_id, minimum_wage
    FROM lc_minimum_wage
    WHERE status = 'Active' AND is_global = 'No' AND position_id IS NOT NULL
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $positionMinWages[(int)$row['position_id']] = (float)$row['minimum_wage'];
}

// ------------------------------------------------------------------
// Summary counts
// ------------------------------------------------------------------
$totalEmployees = (int) sc_value($db, "SELECT COUNT(*) FROM em_employees WHERE is_archived = 0 AND employment_status = 'Active'");
$employeesValid = (int) sc_value($db, "
    SELECT COUNT(*) FROM em_employees e
    WHERE e.is_archived = 0 AND e.employment_status = 'Active' AND e.negotiated_salary > 0
      AND e.negotiated_salary >= COALESCE(
          (SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = e.position_id LIMIT 1),
          " . ($minWage > 0 ? $db->quote($minWage) : '0') . ")
");
$employeesBelow = (int) sc_value($db, "
    SELECT COUNT(*) FROM em_employees e
    WHERE e.is_archived = 0 AND e.employment_status = 'Active' AND e.negotiated_salary > 0
      AND e.negotiated_salary < COALESCE(
          (SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = e.position_id LIMIT 1),
          " . ($minWage > 0 ? $db->quote($minWage) : '999999999') . ")
");
$inactiveCount   = (int) sc_value($db, "SELECT COUNT(*) FROM em_employees WHERE is_archived = 0 AND employment_status != 'Active'");

// ------------------------------------------------------------------
// Search and filter
// ------------------------------------------------------------------
$searchTerm = trim((string)($_GET['search'] ?? ''));
$filter     = trim((string)($_GET['filter'] ?? 'below'));

$employeeQuery = "
    SELECT
        e.employee_id,
        e.employee_code AS employee_no,
        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
        e.employment_status,
        e.employment_type,
        e.position_id,
        e.negotiated_salary,
        COALESCE(d.department_name, 'N/A') AS department_name,
        COALESCE(p.position_name, 'N/A') AS position_name,
        COALESCE(
            (SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = e.position_id LIMIT 1),
            " . ($minWage > 0 ? $db->quote($minWage) : '0') . "
        ) AS position_minimum_wage
    FROM em_employees e
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    WHERE e.is_archived = 0
      AND e.negotiated_salary > 0
    ";

$params = [];

if ($filter === 'valid') {
    $employeeQuery .= " AND e.employment_status = 'Active' AND e.negotiated_salary >= COALESCE(
        (SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = e.position_id LIMIT 1),
        " . ($minWage > 0 ? $db->quote($minWage) : '0') . ")";
} elseif ($filter === 'below') {
    $employeeQuery .= " AND e.employment_status = 'Active' AND e.negotiated_salary < COALESCE(
        (SELECT minimum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = e.position_id LIMIT 1),
        " . ($minWage > 0 ? $db->quote($minWage) : '999999999') . ")";
} elseif ($filter === 'inactive') {
    $employeeQuery .= " AND e.employment_status != 'Active'";
}

if ($searchTerm !== '') {
    $employeeQuery .= " AND (
        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) LIKE :s 
        OR e.employee_code LIKE :s 
        OR d.department_name LIKE :s 
        OR p.position_name LIKE :s 
    )";
    $params[':s'] = "%$searchTerm%";
}

$employeeQuery .= " ORDER BY e.employee_code ASC LIMIT 100";

$employees = sc_all($db, $employeeQuery, $params);

?>
<section class="scv-module">

      <div class="scv-summary-bar">
        <a class="scv-summary-item <?= $filter === 'all' ? 'scv-summary-active' : '' ?>" href="?page=salary-compliance&filter=all&search=<?= urlencode($searchTerm) ?>">
          <div class="scv-summary-icon blue"><i class="bi bi-people"></i></div>
          <div>
            <div class="scv-summary-value"><?= number_format($totalEmployees) ?></div>
            <div class="scv-summary-label">Total Employees</div>
          </div>
        </a>
      <a class="scv-summary-item <?= $filter === 'valid' ? 'scv-summary-active' : '' ?>" href="?page=salary-compliance&filter=valid&search=<?= urlencode($searchTerm) ?>">
          <div class="scv-summary-icon green"><i class="bi bi-check-circle"></i></div>
          <div>
            <div class="scv-summary-value"><?= number_format($employeesValid) ?></div>
            <div class="scv-summary-label">Compliant Salaries</div>
          </div>
        </a>
        <a class="scv-summary-item <?= $filter === 'below' ? 'scv-summary-active' : '' ?>" href="?page=salary-compliance&filter=below&search=<?= urlencode($searchTerm) ?>">
          <div class="scv-summary-icon amber"><i class="bi bi-cash-coin"></i></div>
          <div>
            <div class="scv-summary-value"><?= number_format($employeesBelow) ?></div>
            <div class="scv-summary-label">Below Minimum Wage</div>
          </div>
        </a>
        <a class="scv-summary-item <?= $filter === 'inactive' ? 'scv-summary-active' : '' ?>" href="?page=salary-compliance&filter=inactive&search=<?= urlencode($searchTerm) ?>">
          <div class="scv-summary-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div>
            <div class="scv-summary-value"><?= number_format($inactiveCount) ?></div>
            <div class="scv-summary-label">Inactive Employees</div>
          </div>
        </a>
      </div>

    <div class="scv-card">
      <div class="scv-card-head">
        <h3><i class="bi bi-person-badge"></i> Employee Salary Validation</h3>
        <form class="scv-search-form" method="get" action="" data-skip>
          <input type="hidden" name="page" value="salary-compliance">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
          <div class="scv-search-wrap">
            <i class="bi bi-search scv-search-icon" aria-hidden="true"></i>
            <input type="text" name="search" class="scv-search-input" placeholder="Search employee, no., dept., position..." value="<?= htmlspecialchars($searchTerm) ?>" aria-label="Search employees">
            <button type="submit" class="scv-search-submit" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
            <?php if ($searchTerm !== ''): ?>
              <a class="scv-search-clear" href="?page=salary-compliance&filter=<?= urlencode($filter) ?>" title="Clear search" aria-label="Clear search">&times;</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="scv-card-body">
        <?php if (empty($employees)): ?>
          <div class="scv-empty">No employee records found.</div>
        <?php else: ?>
        <div class="scv-table-wrap">
          <table class="scv-table">
            <thead>
              <tr>
                <th>Employee No.</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Employment Type</th>
                <th>Employment Status</th>
                <th>Negotiated Salary</th>
                <th>Min. Wage</th>
                <th>Compliance</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($employees as $e):
                $employeeId = (int)($e['employee_id'] ?? 0);
                $salary = (float)($e['negotiated_salary'] ?? 0);
                $posMin = (float)($e['position_minimum_wage'] ?? $minWage);
                $empStatus = strtolower($e['employment_status'] ?? 'none');
                $empType = !empty($e['employment_type']) ? $e['employment_type'] : '—';
                if ($salary > 0 && $posMin > 0 && $salary < $posMin) {
                  $compliance = 'Below Minimum Wage';
                  $cls = 'violation';
                } elseif ($salary <= 0) {
                    $compliance = 'Pending Review';
                    $cls = 'pending';
                } else {
                    $compliance = 'Compliant';
                    $cls = 'compliant';
                }

                $employeeNo   = !empty($e['employee_no']) ? $e['employee_no'] : '—';
                $fullName     = !empty($e['full_name']) ? $e['full_name'] : '<em style="color:var(--text-400,#8b93a1)">Unknown</em>';
                $deptName     = !empty($e['department_name']) ? $e['department_name'] : '—';
                $positionName = !empty($e['position_name']) ? $e['position_name'] : '—';
                $empMissing   = empty($e['full_name']) && $employeeId > 0;
              ?>
              <tr>
                <td data-label="Employee No."><?= htmlspecialchars($employeeNo) ?></td>
                <td data-label="Employee Name"><?= $empMissing ? $fullName . ' <small style="color:var(--text-400,#8b93a1)">(ID: ' . $employeeId . ')</small>' : $fullName ?></td>
                <td data-label="Department"><?= htmlspecialchars($deptName) ?></td>
                <td data-label="Position"><?= htmlspecialchars($positionName) ?></td>
                <td data-label="Employment Type"><?= htmlspecialchars($empType) ?></td>
                <td data-label="Employment Status"><span class="scv-stamp"><?= htmlspecialchars($e['employment_status'] ?? 'Unknown') ?></span></td>
                <td data-label="Negotiated Salary"><?= $salary > 0 ? '₱' . number_format($salary, 2) : '—' ?></td>
                <td data-label="Minimum Wage"><?= $posMin > 0 ? '₱' . number_format($posMin, 2) : '—' ?></td>
                <td data-label="Compliance">
                <?php
                    $isInactive = !in_array($empStatus, ['active'], true);
                ?>
                <?php if ($isInactive): ?>
                    <span class="scv-stamp scv-stamp-expired">Inactive</span>
                <?php elseif ($cls === 'violation' && $compliance === 'Below Minimum Wage'):
                    $salaryInput = number_format($posMin, 2, '.', '');
                ?>
                    <a class="scv-stamp scv-stamp-violation scv-stamp-clickable" href="?page=preview-document&employee_id=<?= $employeeId ?>&document_type=salary_rectification&template=salary_rectification_agreement.php&hr_signatory=&template_code=salary_rectification&original_salary=<?= urlencode($salary) ?>" style="text-decoration:none; display:inline-block;"><?= $compliance ?></a>
                <?php else: ?>
                    <span class="scv-stamp scv-stamp-<?= $cls ?>"><?= $compliance ?></span>
                <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <style>
  * { box-sizing: border-box; }
  .scv-module { padding: 4px 2px 24px; max-width: 100%; }
  .scv-summary-bar { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 16px; }
  .scv-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
  .scv-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
  .scv-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
  .scv-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
  .scv-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
  .scv-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
  .scv-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
  .scv-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
  .scv-summary-value { font-size:1.5rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
  .scv-summary-label { font-size:0.8rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

  .scv-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); max-width: 100%; }
  .scv-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
  .scv-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
  .scv-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }
  .scv-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; max-height:520px; overflow-y:auto; }
  .scv-table { width:100%; border-collapse:collapse; font-size:0.82rem; min-width: 0; }
  .scv-table thead th { position:sticky; top:0; background:#fafbfc; z-index:1; }
  .scv-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
  .scv-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); overflow-wrap: anywhere; word-break: break-word; }
  .scv-table tr:last-child td { border-bottom:none; }
  .scv-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
  .scv-stamp-clickable { cursor:pointer; transition: all 0.15s ease; }
  .scv-stamp-clickable:hover { transform: translateY(-1px); box-shadow: 0 0 0 3px rgba(214,72,74,.15); }
  .scv-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
  .scv-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
  .scv-stamp-violation { background:rgba(214,72,74,.12); color:#a3272a; }
  .scv-stamp-expired { background:rgba(139,147,161,.12); color:#5a616d; }
  .scv-search-form { margin-left:auto; }
  .scv-search-wrap { position:relative; display:inline-flex; align-items:center; }
  .scv-search-icon { position:absolute; left:10px; font-size:0.85rem; color:var(--text-400,#8b93a1); pointer-events:none; }
  .scv-search-input { padding:7px 70px 7px 30px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; font-size:0.8rem; min-width:0; width:100%; }
  .scv-search-input:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.1); }
  .scv-search-submit { position:absolute; right:8px; display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; border:none; background:transparent; color:var(--text-700,#3b4252); font-size:0.9rem; cursor:pointer; transition:all .15s ease; }
  .scv-search-submit:hover { background:var(--paper,#eef1f5); color:var(--info-blue,#3b82c4); }
  .scv-search-clear { position:absolute; right:42px; font-size:1rem; color:var(--text-400,#8b93a1); text-decoration:none; line-height:1; }
  .scv-search-clear:hover { color:var(--text-900,#1b2430); }

  /* Large tablet */
  @media (max-width: 1100px) {
    .scv-summary-value { font-size: 1.3rem; }
    .scv-summary-label { font-size: 0.78rem; }
    .scv-summary-item { padding: 14px 16px; }
    .scv-summary-icon { width: 40px; height: 40px; font-size: 1.1rem; }
    .scv-card { padding: 16px; }
    .scv-table { font-size: 0.8rem; }
    .scv-table th, .scv-table td { padding: 8px 10px; }
  }

  /* Tablet */
  @media (max-width: 768px) {
    .scv-module { padding: 2px 0 20px; }
    .scv-summary-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .scv-summary-item { padding: 14px; gap: 10px; }
    .scv-summary-icon { width: 38px; height: 38px; font-size: 1rem; }
    .scv-summary-value { font-size: 1.25rem; }
    .scv-summary-label { font-size: 0.75rem; }
    .scv-card { padding: 14px; border-radius: 12px; }
    .scv-card-head { flex-direction: column; align-items: stretch; gap: 10px; }
    .scv-card-head h3 { width: 100%; font-size: 0.92rem; }
    .scv-search-form { width: 100%; margin-left: 0; }
    .scv-search-wrap { width: 100%; }
    .scv-search-input { min-width: 0; width: 100%; font-size: 0.85rem; }
    .scv-search-submit { width: 36px; height: 36px; }
    .scv-table { font-size: 0.82rem; }
    .scv-stamp { font-size: 0.7rem; padding: 4px 10px; }
  }

  /* Mobile card transformation */
  @media (max-width: 767px) {
    .scv-table-wrap { overflow-x: visible; max-height: none; overflow-y: visible; }

    .scv-table thead { display: none; }

    .scv-table tbody tr {
      display: block;
      background: var(--card-bg, #fff);
      border: 1px solid var(--border, #e4e8ee);
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 12px;
      box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .scv-table tbody tr:last-child {
      margin-bottom: 0;
    }

    .scv-table tbody td {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 8px 0;
      border-bottom: 1px solid var(--border, #e4e8ee);
      text-align: right;
      gap: 10px;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .scv-table tbody td:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .scv-table tbody td::before {
      content: attr(data-label);
      font-weight: 700;
      font-size: 0.72rem;
      text-transform: uppercase;
      color: var(--text-400, #8b93a1);
      white-space: nowrap;
      flex-shrink: 0;
      margin-right: 10px;
      text-align: left;
      letter-spacing: 0.02em;
    }

    .scv-table tbody td:nth-child(1) { order: 2; }
    .scv-table tbody td:nth-child(2) { order: 1; font-weight: 600; font-size: 0.95rem; color: var(--text-900, #1b2430); }
    .scv-table tbody td:nth-child(3) { order: 4; }
    .scv-table tbody td:nth-child(4) { order: 5; }
    .scv-table tbody td:nth-child(5) { order: 7; }
    .scv-table tbody td:nth-child(6) { order: 8; }
    .scv-table tbody td:nth-child(7) { order: 9; }
    .scv-table tbody td:nth-child(8) { order: 10; }
    .scv-table tbody td:nth-child(9) { order: 3; }

    .scv-table tbody td:nth-child(9) {
      justify-content: flex-start;
    }

    .scv-stamp {
      white-space: normal;
      text-align: center;
      min-height: 40px;
      min-width: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 12px;
    }
  }

  /* Small mobile */
  @media (max-width: 380px) {
    .scv-summary-bar { gap: 8px; grid-template-columns: 1fr; }
    .scv-summary-item { padding: 12px; gap: 8px; }
    .scv-summary-icon { width: 34px; height: 34px; font-size: 0.95rem; border-radius: 10px; }
    .scv-summary-value { font-size: 1.15rem; }
    .scv-summary-label { font-size: 0.72rem; }
    .scv-card { padding: 12px; border-radius: 10px; }
    .scv-table tbody tr { padding: 12px; margin-bottom: 10px; }
    .scv-table tbody td { padding: 6px 0; font-size: 0.8rem; }
    .scv-search-input { font-size: 0.82rem; padding: 6px 60px 6px 28px; }
    .scv-search-submit { width: 32px; height: 32px; right: 6px; }
    .scv-search-clear { right: 38px; }
  }
  </style>
