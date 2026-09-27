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
<section class="salcomp-module">
    <div class="salcomp-card">
        <div class="salcomp-summary-grid">
            <a class="salcomp-summary-item <?= $filter === 'all' ? 'salcomp-summary-active' : '' ?>" href="?page=salary-compliance&filter=all&search=<?= urlencode($searchTerm) ?>">
                <div class="salcomp-summary-body">
                    <div class="salcomp-summary-name">Total Employees</div>
                    <div class="salcomp-summary-meta"><?= number_format($totalEmployees) ?> active records</div>
                    <div class="salcomp-summary-stats"><?= number_format($totalEmployees) ?></div>
                </div>
            </a>
            <a class="salcomp-summary-item <?= $filter === 'valid' ? 'salcomp-summary-active' : '' ?>" href="?page=salary-compliance&filter=valid&search=<?= urlencode($searchTerm) ?>">
                <div class="salcomp-summary-body">
                    <div class="salcomp-summary-name">Compliant Salaries</div>
                    <div class="salcomp-summary-meta"><?= number_format($employeesValid) ?> employees meeting minimum wage</div>
                    <div class="salcomp-summary-stats"><?= number_format($employeesValid) ?> / <?= number_format($totalEmployees) ?></div>
                </div>
            </a>
            <a class="salcomp-summary-item <?= $filter === 'below' ? 'salcomp-summary-active' : '' ?>" href="?page=salary-compliance&filter=below&search=<?= urlencode($searchTerm) ?>">
                <div class="salcomp-summary-body">
                    <div class="salcomp-summary-name">Below Minimum Wage</div>
                    <div class="salcomp-summary-meta"><?= number_format($employeesBelow) ?> employees below threshold</div>
                    <div class="salcomp-summary-stats"><?= number_format($employeesBelow) ?> / <?= number_format($totalEmployees) ?></div>
                </div>
            </a>
            <a class="salcomp-summary-item <?= $filter === 'inactive' ? 'salcomp-summary-active' : '' ?>" href="?page=salary-compliance&filter=inactive&search=<?= urlencode($searchTerm) ?>">
                <div class="salcomp-summary-body">
                    <div class="salcomp-summary-name">Inactive Employees</div>
                    <div class="salcomp-summary-meta"><?= number_format($inactiveCount) ?> employees not active</div>
                    <div class="salcomp-summary-stats"><?= number_format($inactiveCount) ?> / <?= number_format($totalEmployees) ?></div>
                </div>
            </a>
        </div>
    </div>

    <div class="salcomp-card" style="padding: 0; overflow: hidden;">
        <div class="salcomp-table-wrap">
            <table class="salcomp-table" id="salcompEmployeeTable">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Employment Type</th>
                        <th>Negotiated Salary</th>
                        <th>Min. Wage</th>
                        <th>Compliance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 32px; color: var(--sal-text-muted);">
                                No employee records found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
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
                            $fullName     = !empty($e['full_name']) ? $e['full_name'] : '<em style="color:var(--sal-text-muted)">Unknown</em>';
                            $deptName     = !empty($e['department_name']) ? $e['department_name'] : '—';
                            $positionName = !empty($e['position_name']) ? $e['position_name'] : '—';
                            $empMissing   = empty($e['full_name']) && $employeeId > 0;
                        ?>
                        <tr>
                            <td data-label="Employee No." class="salcomp-col-code"><?= htmlspecialchars($employeeNo) ?></td>
                            <td data-label="Employee Name" class="salcomp-col-name"><?= $empMissing ? $fullName . ' <small style="color:var(--sal-text-muted)">(ID: ' . $employeeId . ')</small>' : $fullName ?></td>
                            <td data-label="Department" class="salcomp-col-dept"><?= htmlspecialchars($deptName) ?></td>
                            <td data-label="Position" class="salcomp-col-id"><?= htmlspecialchars($positionName) ?></td>
                            <td data-label="Employment Type" class="salcomp-col-type"><?= htmlspecialchars($empType) ?></td>
                            <td data-label="Negotiated Salary" class="salcomp-col-salary"><?= $salary > 0 ? '₱' . number_format($salary, 2) : '—' ?></td>
                            <td data-label="Minimum Wage" class="salcomp-col-wage"><?= $posMin > 0 ? '₱' . number_format($posMin, 2) : '—' ?></td>
                            <td data-label="Compliance">
                                <?php
                                    $isInactive = !in_array($empStatus, ['active'], true);
                                ?>
                                <?php if ($isInactive): ?>
                                    <span class="salcomp-status salcomp-status-inactive">Inactive</span>
                                <?php elseif ($cls === 'violation' && $compliance === 'Below Minimum Wage'): ?>
                                    <a class="salcomp-status salcomp-status-violation salcomp-status-clickable" href="?page=preview-document&employee_id=<?= $employeeId ?>&document_type=salary_rectification&template=salary_rectification_agreement.php&hr_signatory=&template_code=salary_rectification&original_salary=<?= urlencode($salary) ?>" style="text-decoration:none; display:inline-block;"><?= $compliance ?></a>
                                <?php else: ?>
                                    <span class="salcomp-status salcomp-status-<?= $cls ?>"><?= $compliance ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
/* ============================================
   Salary Compliance — Enterprise Styles
   ============================================ */

.salcomp-module {
    --sal-surface: #ffffff;
    --sal-text: #20252b;
    --sal-text-secondary: #667085;
    --sal-text-muted: #8b93a1;
    --sal-border: #dfe3e8;
    --sal-border-light: #eef1f5;
    --sal-accent: #1b18be;
    --sal-accent-hover: #40b4d1;
    --sal-accent-light: #faf6f0;
    --sal-success: #1f7a52;
    --sal-success-bg: rgba(47,158,110,.08);
    --sal-warning: #a86b13;
    --sal-warning-bg: rgba(217,154,43,.08);
    --sal-danger: #a3272a;
    --sal-danger-bg: rgba(214,72,74,.08);
    --sal-radius: 6px;
    --sal-radius-md: 8px;
    --sal-font: Arial, sans-serif;
    --sal-font-mono: Arial, sans-serif;
    font-family: var(--sal-font);
    color: var(--sal-text);
    padding: 4px 2px 24px;
}

/* Page Header */
.salcomp-page-header {
    margin-bottom: 20px;
}

.salcomp-breadcrumb {
    font-size: 0.78rem;
    color: var(--sal-text-muted);
    margin-bottom: 6px;
}

.salcomp-breadcrumb a {
    color: var(--sal-accent);
    text-decoration: none;
}

.salcomp-breadcrumb a:hover {
    text-decoration: underline;
}

.salcomp-page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--sal-text);
    margin: 0 0 2px;
    letter-spacing: -0.01em;
}

.salcomp-page-subtitle {
    font-size: 0.85rem;
    color: var(--sal-text-secondary);
    margin: 0;
}

/* Cards */
.salcomp-card {
    background: var(--sal-surface);
    border: 1px solid var(--sal-border);
    border-radius: var(--sal-radius);
    padding: 16px;
    margin-bottom: 16px;
}

.salcomp-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.salcomp-card-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--sal-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.salcomp-card-title i {
    font-size: 0.95rem;
    color: var(--sal-accent);
}

.salcomp-card-meta {
    font-size: 0.8rem;
    color: var(--sal-text-secondary);
}

.salcomp-card-meta strong {
    color: var(--sal-text);
    font-weight: 700;
}

/* Summary Grid */
.salcomp-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: var(--sal-border);
    border: 1px solid var(--sal-border);
    border-radius: var(--sal-radius);
    overflow: hidden;
}

.salcomp-summary-item {
    padding: 14px 16px;
    background: var(--sal-surface);
    text-decoration: none;
    color: inherit;
    transition: background 0.12s ease;
}

.salcomp-summary-item:hover {
    background: var(--sal-accent-light);
}

.salcomp-summary-item.salcomp-summary-active {
    background: var(--sal-surface);
    outline: 1px solid var(--sal-accent);
    outline-offset: -1px;
}

.salcomp-summary-body {
    flex: 1;
    min-width: 0;
}

.salcomp-summary-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--sal-text);
    margin-bottom: 2px;
}

.salcomp-summary-meta {
    font-size: 0.75rem;
    color: var(--sal-text-secondary);
    margin-bottom: 6px;
    line-height: 1.3;
}

.salcomp-summary-stats {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--sal-text);
    margin-bottom: 4px;
}

/* Table */
.salcomp-table-wrap {
    border: 1px solid var(--sal-border);
    border-radius: var(--sal-radius);
    overflow: hidden;
}

.salcomp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
    table-layout: auto;
}

.salcomp-table thead {
    background: var(--sal-border-light);
}

.salcomp-table th {
    text-align: left;
    padding: 9px 12px;
    font-size: 10px;
    font-weight: 700;
    font-family: Arial, sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--sal-text-muted);
    border-bottom: 1px solid var(--sal-border);
    white-space: nowrap;
    user-select: none;
}

.salcomp-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--sal-border-light);
    vertical-align: middle;
    color: var(--sal-text);
    line-height: 1.3;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}

.salcomp-table tbody tr:last-child td {
    border-bottom: none;
}

.salcomp-table tbody tr:hover {
    background: rgba(168,121,31,.02);
}

.salcomp-table tbody tr {
    transition: background 0.08s ease;
}

/* Table column specific */
.salcomp-col-code {
    font-family: Arial, sans-serif;
    font-size: 11.5px;
    color: var(--sal-text-secondary);
    white-space: nowrap;
}

.salcomp-col-name {
    font-weight: 500;
    font-size: 11.5px;
    color: var(--sal-text);
}

.salcomp-col-dept {
    font-size: 11.5px;
    color: var(--sal-text-secondary);
}

.salcomp-col-id {
    font-size: 11.5px;
    color: var(--sal-text-secondary);
    white-space: nowrap;
}

.salcomp-col-type {
    font-size: 11.5px;
    color: var(--sal-text-secondary);
    white-space: nowrap;
}

.salcomp-col-salary,
.salcomp-col-wage {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--sal-text);
    white-space: nowrap;
}

/* Status */
.salcomp-status {
    display: inline-flex;
    align-items: center;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    white-space: nowrap;
}

.salcomp-status-compliant {
    background: var(--sal-success-bg);
    color: var(--sal-success);
}

.salcomp-status-pending {
    background: var(--sal-warning-bg);
    color: var(--sal-warning);
}

.salcomp-status-violation {
    background: var(--sal-danger-bg);
    color: var(--sal-danger);
    cursor: pointer;
    transition: all 0.15s ease;
}

.salcomp-status-clickable:hover {
    transform: translateY(-1px);
    box-shadow: 0 0 0 3px rgba(214,72,74,.15);
}

.salcomp-status-inactive {
    background: rgba(139,147,161,.08);
    color: #5a616d;
}

/* Empty state */
.salcomp-empty {
    padding: 32px 24px;
    text-align: center;
    color: var(--sal-text-muted);
    font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 1100px) {
    .salcomp-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .salcomp-module {
        padding: 4px 2px 20px;
    }

    .salcomp-page-title {
        font-size: 1.25rem;
    }

    .salcomp-card {
        padding: 12px;
        border-radius: var(--sal-radius-md);
    }

    .salcomp-summary-grid {
        grid-template-columns: 1fr;
    }

    .salcomp-summary-item {
        padding: 12px 14px;
    }

    /* Mobile table cards */
    .salcomp-table-wrap {
        border: none;
        border-radius: 0;
        overflow: visible;
    }

    .salcomp-table,
    .salcomp-table thead,
    .salcomp-table tbody,
    .salcomp-table th,
    .salcomp-table td,
    .salcomp-table tr {
        display: block;
        width: 100%;
    }

    .salcomp-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .salcomp-table thead {
        display: none;
    }

    .salcomp-table tbody tr {
        background: var(--sal-surface);
        border: 1px solid var(--sal-border);
        border-radius: var(--sal-radius-md);
        padding: 12px 14px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(13,27,46,.03);
    }

    .salcomp-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px solid var(--sal-border-light);
        text-align: right;
    }

    .salcomp-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .salcomp-table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--sal-text-muted);
        text-align: left;
        flex-shrink: 0;
    }

    .salcomp-table td:last-child {
        justify-content: flex-end;
    }

    .salcomp-col-code,
    .salcomp-col-id,
    .salcomp-col-type,
    .salcomp-col-salary,
    .salcomp-col-wage {
        font-size: 0.8rem;
    }

    .salcomp-status {
        font-size: 0.72rem;
        padding: 2px 7px;
    }
}

@media (max-width: 400px) {
    .salcomp-modal-overlay {
        max-height: calc(100vh - 16px);
    }

    .salcomp-modal-body {
        padding: 14px;
    }
}
</style>
