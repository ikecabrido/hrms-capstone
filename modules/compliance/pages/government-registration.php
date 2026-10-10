<?php

$pageTitle = 'Government Registration';

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

// ------------------------------------------------------------------
// CSRF
// ------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (empty($_SESSION['govreg_csrf_token']) || !is_string($_SESSION['govreg_csrf_token'])) {
    $_SESSION['govreg_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['govreg_csrf_token'];

function gov_value(PDO $db, string $sql, $default = 0, array $params = []): int|float|string|null {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        error_log('GovernmentRegistration DB error: ' . $e->getMessage());
        return $default;
    }
}
function gov_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('GovernmentRegistration DB error: ' . $e->getMessage());
        return [];
    }
}

// ------------------------------------------------------------------
// Summary analytics
// ------------------------------------------------------------------
$totalActive = (int) gov_value($db, "SELECT COUNT(*) FROM em_employees WHERE employment_status NOT IN ('Resigned','Terminated')");
$requiredRegistrations = $totalActive * 4;

$completeCount = (int) gov_value($db, "
    SELECT COUNT(*)
    FROM em_employees e
    LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
    WHERE e.employment_status NOT IN ('Resigned','Terminated')
      AND g.gov_id IS NOT NULL
      AND g.sss_no IS NOT NULL AND g.sss_no <> ''
      AND g.philhealth_no IS NOT NULL AND g.philhealth_no <> ''
      AND g.pagibig_no IS NOT NULL AND g.pagibig_no <> ''
      AND g.tin_no IS NOT NULL AND g.tin_no <> ''
");
$partialCount = (int) gov_value($db, "
    SELECT COUNT(*)
    FROM em_employees e
    LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
    WHERE e.employment_status NOT IN ('Resigned','Terminated')
      AND g.gov_id IS NOT NULL
      AND (
          (g.sss_no IS NULL OR g.sss_no = '')
          OR (g.philhealth_no IS NULL OR g.philhealth_no = '')
          OR (g.pagibig_no IS NULL OR g.pagibig_no = '')
          OR (g.tin_no IS NULL OR g.tin_no = '')
      )
");
$missingCount = (int) gov_value($db, "
    SELECT COUNT(*)
    FROM em_employees e
    LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
    WHERE e.employment_status NOT IN ('Resigned','Terminated')
      AND g.gov_id IS NULL
");
$completedRegistrations = $completeCount * 4;
$complianceRate = $requiredRegistrations > 0 ? round(($completedRegistrations / $requiredRegistrations) * 100, 2) : 0;

$agencyStats = [
    'SSS' => ['label' => 'SSS', 'column' => 'sss_no'],
    'PhilHealth' => ['label' => 'PhilHealth', 'column' => 'philhealth_no'],
    'Pag-IBIG' => ['label' => 'Pag-IBIG', 'column' => 'pagibig_no'],
    'TIN' => ['label' => 'TIN', 'column' => 'tin_no'],
];
$agencyLogos = [
    'SSS' => '/modules/compliance/assets/sss.png',
    'PhilHealth' => '/modules/compliance/assets/philhealth.webp',
    'Pag-IBIG' => '/modules/compliance/assets/pagibig.webp',
    'TIN' => '/modules/compliance/assets/bir.png',
];
$agencyCounts = [];
foreach ($agencyStats as $key => $meta) {
    $agencyCounts[$key] = [
        'label' => $meta['label'],
        'completed' => (int) gov_value($db, "
            SELECT COUNT(*)
            FROM em_employees e
            LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
            WHERE e.employment_status NOT IN ('Resigned','Terminated')
              AND (g.{$meta['column']} IS NULL OR g.{$meta['column']} = '')
        "),
        'total' => $totalActive,
    ];
}

// ------------------------------------------------------------------
// Filters
// ------------------------------------------------------------------
$searchTerm = trim((string)($_GET['search'] ?? ''));
$filterDept = trim((string)($_GET['department'] ?? 'all'));
$filterStatus = trim((string)($_GET['status'] ?? 'all'));
$filterAgency = trim((string)($_GET['agency'] ?? ''));

$departments = gov_all($db, "SELECT department_id, department_name FROM em_departments WHERE status = 'Active' ORDER BY department_name ASC");

$agencyColumnMap = [
    'SSS' => 'sss_no',
    'PhilHealth' => 'philhealth_no',
    'Pag-IBIG' => 'pagibig_no',
    'TIN' => 'tin_no',
];

// ------------------------------------------------------------------
// SQL-side filtering + true pagination
// ------------------------------------------------------------------
$perPage = 20;
$page = isset($_GET['gov_page']) ? max(1, (int) $_GET['gov_page']) : 1;

$where = ["e.employment_status NOT IN ('Resigned','Terminated')"];
$params = [];

if ($searchTerm !== '') {
    $where[] = "(CONCAT(e.first_name, ' ', e.last_name) LIKE :s OR e.employee_code LIKE :s OR COALESCE(d.department_name, '') LIKE :s)";
    $params[':s'] = "%$searchTerm%";
}
if ($filterDept !== 'all') {
    $where[] = "d.department_id = :dept";
    $params[':dept'] = (int)$filterDept;
}

$countSql = "
    SELECT COUNT(*) 
    FROM em_employees e
    LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
";

$countWhere = $where;
if ($filterStatus !== 'all') {
    if ($filterStatus === 'complete') {
        $countWhere[] = "g.gov_id IS NOT NULL AND g.sss_no IS NOT NULL AND g.sss_no <> '' AND g.philhealth_no IS NOT NULL AND g.philhealth_no <> '' AND g.pagibig_no IS NOT NULL AND g.pagibig_no <> '' AND g.tin_no IS NOT NULL AND g.tin_no <> ''";
    } elseif ($filterStatus === 'partial') {
        $countWhere[] = "g.gov_id IS NOT NULL AND ((g.sss_no IS NULL OR g.sss_no = '') OR (g.philhealth_no IS NULL OR g.philhealth_no = '') OR (g.pagibig_no IS NULL OR g.pagibig_no = '') OR (g.tin_no IS NULL OR g.tin_no = ''))";
    } elseif ($filterStatus === 'missing') {
        $countWhere[] = "g.gov_id IS NULL";
    }
}
if ($filterAgency !== '' && isset($agencyColumnMap[$filterAgency])) {
    $col = $agencyColumnMap[$filterAgency];
    $countWhere[] = "(g.{$col} IS NULL OR g.{$col} = '')";
}

$countSql .= " WHERE " . implode(' AND ', $countWhere);
$totalRows = (int) gov_value($db, $countSql, 0, $params);
$totalPages = ($perPage > 0 && $totalRows > 0) ? (int) ceil($totalRows / $perPage) : 1;
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$employeeQuery = "
    SELECT 
        e.employee_id,
        e.employee_code,
        e.first_name,
        e.last_name,
        e.middle_name,
        e.employment_status,
        d.department_name,
        g.gov_id,
        g.sss_no,
        g.philhealth_no,
        g.pagibig_no,
        g.tin_no,
        g.created_at AS gov_created,
        g.updated_at AS gov_updated
    FROM em_employees e
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_government_ids g ON g.employee_id = e.employee_id
    WHERE " . implode(' AND ', $where);

if ($filterStatus !== 'all') {
    if ($filterStatus === 'complete') {
        $employeeQuery .= " AND g.gov_id IS NOT NULL AND g.sss_no IS NOT NULL AND g.sss_no <> '' AND g.philhealth_no IS NOT NULL AND g.philhealth_no <> '' AND g.pagibig_no IS NOT NULL AND g.pagibig_no <> '' AND g.tin_no IS NOT NULL AND g.tin_no <> ''";
    } elseif ($filterStatus === 'partial') {
        $employeeQuery .= " AND g.gov_id IS NOT NULL AND ((g.sss_no IS NULL OR g.sss_no = '') OR (g.philhealth_no IS NULL OR g.philhealth_no = '') OR (g.pagibig_no IS NULL OR g.pagibig_no = '') OR (g.tin_no IS NULL OR g.tin_no = ''))";
    } elseif ($filterStatus === 'missing') {
        $employeeQuery .= " AND g.gov_id IS NULL";
    }
}
if ($filterAgency !== '' && isset($agencyColumnMap[$filterAgency])) {
    $col = $agencyColumnMap[$filterAgency];
    $employeeQuery .= " AND (g.{$col} IS NULL OR g.{$col} = '')";
}

$employeeQuery .= " ORDER BY e.employee_id ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($employeeQuery);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$paginatedEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------------
// AJAX: Add government ID
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_government_id') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request.'];
    try {
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['govreg_csrf_token'] ?? '', (string)$_POST['csrf_token'])) {
            $response['message'] = 'Invalid session. Please refresh and try again.';
            echo json_encode($response);
            exit;
        }

        $employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
        $sssNo = trim((string)($_POST['sss_no'] ?? ''));
        $philhealthNo = trim((string)($_POST['philhealth_no'] ?? ''));
        $pagibigNo = trim((string)($_POST['pagibig_no'] ?? ''));
        $tinNo = trim((string)($_POST['tin_no'] ?? ''));

        if ($employeeId <= 0) {
            echo json_encode($response);
            exit;
        }

        $empCheck = gov_value($db, "SELECT employee_id FROM em_employees WHERE employee_id = :eid LIMIT 1", 0, [':eid' => $employeeId]);
        if (!$empCheck) {
            $response['message'] = 'Employee not found.';
            echo json_encode($response);
            exit;
        }

        $errors = [];
        if ($sssNo !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $sssNo)) {
            $errors[] = 'SSS number must be alphanumeric (dashes allowed).';
        }
        if ($sssNo !== '' && strlen($sssNo) > 50) {
            $errors[] = 'SSS number must not exceed 50 characters.';
        }
        if ($philhealthNo !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $philhealthNo)) {
            $errors[] = 'PhilHealth number must be alphanumeric (dashes allowed).';
        }
        if ($philhealthNo !== '' && strlen($philhealthNo) > 50) {
            $errors[] = 'PhilHealth number must not exceed 50 characters.';
        }
        if ($pagibigNo !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $pagibigNo)) {
            $errors[] = 'Pag-IBIG number must be alphanumeric (dashes allowed).';
        }
        if ($pagibigNo !== '' && strlen($pagibigNo) > 50) {
            $errors[] = 'Pag-IBIG number must not exceed 50 characters.';
        }
        if ($tinNo !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $tinNo)) {
            $errors[] = 'TIN number must be alphanumeric (dashes allowed).';
        }
        if ($tinNo !== '' && strlen($tinNo) > 50) {
            $errors[] = 'TIN number must not exceed 50 characters.';
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit;
        }

        $existingGovId = (int) gov_value($db, "SELECT gov_id FROM em_government_ids WHERE employee_id = :eid LIMIT 1", 0, [':eid' => $employeeId]);
        if ($existingGovId > 0) {
            echo json_encode(['success' => false, 'message' => 'A government ID record already exists for this employee.']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO em_government_ids (employee_id, sss_no, philhealth_no, pagibig_no, tin_no, created_at, updated_at)
            VALUES (:eid, :sss, :phil, :pag, :tin, NOW(), NOW())
        ");
        $stmt->execute([
            ':eid' => $employeeId,
            ':sss' => $sssNo !== '' ? $sssNo : null,
            ':phil' => $philhealthNo !== '' ? $philhealthNo : null,
            ':pag' => $pagibigNo !== '' ? $pagibigNo : null,
            ':tin' => $tinNo !== '' ? $tinNo : null,
        ]);
        echo json_encode(['success' => true, 'message' => 'Government ID registered successfully.']);
        exit;
    } catch (Throwable $e) {
        error_log('GovernmentRegistration add_government_id error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to save government ID. Please contact support.']);
        exit;
    }
}

// ------------------------------------------------------------------
// AJAX: Search employees
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'search_employees') {
    header('Content-Type: application/json');
    $term = trim((string)($_POST['term'] ?? ''));
    $sql = "
        SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.middle_name, d.department_name
        FROM em_employees e
        LEFT JOIN em_departments d ON d.department_id = e.department_id
        WHERE e.employment_status NOT IN ('Resigned','Terminated')
    ";
    $params = [];
    if ($term !== '') {
        $sql .= " AND (CONCAT(e.first_name, ' ', e.last_name) LIKE :s OR e.employee_code LIKE :s)";
        $params[':s'] = "%$term%";
    }
    $sql .= " ORDER BY e.last_name ASC, e.first_name ASC LIMIT 20";

    $employees = gov_all($db, $sql, $params);
    $results = [];
    foreach ($employees as $emp) {
        $fullName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
        $results[] = [
            'id' => (int)$emp['employee_id'],
            'text' => ($emp['employee_code'] ?? '') . ' - ' . $fullName . ' (' . ($emp['department_name'] ?? 'No Dept') . ')',
            'employee_code' => $emp['employee_code'] ?? '',
            'full_name' => $fullName,
            'department_name' => $emp['department_name'] ?? '',
        ];
    }
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}
?>
<style>
/* ============================================
   Government Registration — Enterprise Styles
   ============================================ */

.govreg-module {
    --gov-surface: #ffffff;
    --gov-text: #20252b;
    --gov-text-secondary: #667085;
    --gov-text-muted: #8b93a1;
    --gov-border: #dfe3e8;
    --gov-border-light: #eef1f5;
    --gov-accent: #1b18be;
    --gov-accent-hover: #40b4d1;
    --gov-accent-light: #faf6f0;
    --gov-success: #1f7a52;
    --gov-success-bg: rgba(47,158,110,.08);
    --gov-warning: #a86b13;
    --gov-warning-bg: rgba(217,154,43,.08);
    --gov-danger: #a3272a;
    --gov-danger-bg: rgba(214,72,74,.08);
    --gov-radius: 6px;
    --gov-radius-md: 8px;
    --gov-font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    color: var(--gov-text);
    padding: 4px 2px 24px;
}

/* Page Header */
.govreg-page-header {
    margin-bottom: 20px;
}

.govreg-breadcrumb {
    font-size: 10px;
    color: var(--gov-text-muted);
    margin-bottom: 6px;
    font-weight: 500;
    line-height: 1.4;
}

.govreg-breadcrumb a {
    color: var(--gov-accent);
    text-decoration: none;
}

.govreg-breadcrumb a:hover {
    text-decoration: underline;
}

.govreg-page-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--gov-text);
    margin: 0 0 2px;
    letter-spacing: 0.02em;
    line-height: 1.3;
}

.govreg-page-subtitle {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    margin: 0;
    line-height: 1.4;
}

/* Cards */
.govreg-card {
    background: var(--gov-surface);
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius);
    padding: 16px;
    margin-bottom: 16px;
}

.govreg-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.govreg-card-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--gov-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.3;
}

.govreg-card-title i {
    font-size: 15px;
    color: var(--gov-accent);
}

.govreg-card-meta {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    line-height: 1.4;
}

.govreg-card-meta strong {
    color: var(--gov-text);
    font-weight: 600;
}

/* Compliance Overview Grid */
.govreg-agency-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: var(--gov-border);
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius);
    overflow: hidden;
}

.govreg-agency-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: var(--gov-surface);
    text-decoration: none;
    color: inherit;
    transition: background 0.12s ease;
}

.govreg-agency-item:hover {
    background: var(--gov-accent-light);
}

.govreg-agency-item.is-active {
    background: var(--gov-accent-light);
    outline: 1px solid var(--gov-accent);
    outline-offset: -1px;
}

.govreg-agency-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--gov-radius);
    background: var(--gov-border-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid var(--gov-border);
}

.govreg-agency-icon img {
    width: 22px;
    height: 22px;
    object-fit: contain;
}

.govreg-agency-icon i {
    font-size: 1rem;
    color: var(--gov-text-muted);
}

.govreg-agency-body {
    flex: 1;
    min-width: 0;
}

.govreg-agency-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--gov-text);
    margin-bottom: 2px;
    line-height: 1.3;
}

.govreg-agency-meta {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    margin-bottom: 6px;
    line-height: 1.4;
}

.govreg-agency-stats {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--gov-text);
    margin-bottom: 4px;
    line-height: 1.4;
}

.govreg-agency-rate {
    height: 4px;
    border-radius: 2px;
    background: var(--gov-border-light);
    overflow: hidden;
}

.govreg-agency-rate-fill {
    height: 100%;
    border-radius: 2px;
    background: var(--gov-accent);
    transition: width 0.2s ease;
}

/* Filter Bar */
.govreg-filter-bar {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.govreg-search-wrap {
    position: relative;
    flex: 1 1 220px;
    max-width: 320px;
}

.govreg-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gov-text-muted);
    font-size: 0.85rem;
    pointer-events: none;
}

.govreg-search-input {
    width: 100%;
    padding: 7px 10px 7px 30px;
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius-md);
    background: var(--gov-surface);
    font-size: 12.5px;
    font-weight: 500;
    color: var(--gov-text);
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
    line-height: 1.3;
}

.govreg-search-input:focus {
    border-color: var(--gov-accent);
    box-shadow: 0 0 0 3px rgba(168,121,31,.1);
}

.govreg-search-input::placeholder {
    color: var(--gov-text-muted);
}

.govreg-select {
    padding: 7px 28px 7px 10px;
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius-md);
    background: var(--gov-surface);
    font-size: 12.5px;
    font-weight: 500;
    color: var(--gov-text);
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667085' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
    line-height: 1.3;
}

.govreg-select:focus {
    border-color: var(--gov-accent);
    box-shadow: 0 0 0 3px rgba(168,121,31,.1);
}

.govreg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--gov-radius-md);
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.12s ease;
    text-decoration: none;
    white-space: nowrap;
    line-height: 1.2;
}

.govreg-btn-primary {
    background: var(--gov-accent);
    color: #fff;
    border-color: var(--gov-accent);
}

.govreg-btn-primary:hover {
    background: var(--gov-accent-hover);
    border-color: var(--gov-accent-hover);
}

.govreg-btn-secondary {
    background: var(--gov-surface);
    color: var(--gov-text);
    border-color: var(--gov-border);
}

.govreg-btn-secondary:hover {
    background: var(--gov-border-light);
    border-color: var(--gov-text-muted);
}

/* Table */
.govreg-table-wrap {
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius);
    overflow: hidden;
}

.govreg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
    table-layout: auto;
}

.govreg-table thead {
    background: var(--gov-border-light);
}

.govreg-table th {
    text-align: left;
    padding: 9px 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--gov-text-muted);
    border-bottom: 1px solid var(--gov-border);
    white-space: nowrap;
    user-select: none;
    line-height: 1.3;
}

.govreg-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gov-border-light);
    vertical-align: middle;
    color: var(--gov-text);
    font-weight: 500;
    line-height: 1.4;
}

.govreg-table tbody tr:last-child td {
    border-bottom: none;
}

.govreg-table tbody tr:hover {
    background: rgba(168,121,31,.02);
}

.govreg-table tbody tr {
    transition: background 0.08s ease;
}

/* Table column specific */
.govreg-col-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 12px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    white-space: nowrap;
    line-height: 1.4;
}

.govreg-col-name {
    font-weight: 500;
    font-size: 11.5px;
    color: var(--gov-text);
    line-height: 1.4;
}

.govreg-col-dept {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    line-height: 1.4;
}

.govreg-col-id {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 12px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    white-space: nowrap;
    line-height: 1.4;
}

.govreg-col-date {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-muted);
    white-space: nowrap;
    line-height: 1.4;
}

/* Status */
.govreg-status {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    line-height: 1.3;
    white-space: nowrap;
}

.govreg-status-complete {
    background: rgba(59,130,196,.08);
    color: #1c5a8a;
}

.govreg-status-partial {
    background: var(--gov-warning-bg);
    color: var(--gov-warning);
}

.govreg-status-missing {
    background: var(--gov-danger-bg);
    color: var(--gov-danger);
}

/* Missing requirements */
.govreg-missing {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    line-height: 1.4;
}

.govreg-missing--has-missing {
    color: var(--gov-danger);
    font-weight: 600;
}

.govreg-missing-icons {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.govreg-missing-icon {
    width: 16px;
    height: 16px;
    object-fit: contain;
    opacity: 0.9;
    transition: opacity 0.12s ease;
}

.govreg-missing-icon:hover {
    opacity: 1;
}

/* Action buttons */
.govreg-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.govreg-action-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: var(--gov-radius);
    color: var(--gov-text-secondary);
    text-decoration: none;
    transition: all 0.12s ease;
    border: 1px solid transparent;
}

.govreg-action-link:hover {
    background: var(--gov-border-light);
    color: var(--gov-accent);
    border-color: var(--gov-border);
}

.govreg-action-link i {
    font-size: 0.9rem;
}

.govreg-action-link--disabled {
    color: var(--gov-text-muted);
    cursor: not-allowed;
    pointer-events: none;
    opacity: 0.5;
    background: transparent;
    border-color: transparent;
}

/* Pagination */
.govreg-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.govreg-page-info {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    margin-right: 8px;
    line-height: 1.4;
}

.govreg-page-link,
.govreg-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius);
    font-size: 11.5px;
    font-weight: 600;
    text-decoration: none;
    color: var(--gov-text);
    background: var(--gov-surface);
    transition: all 0.12s ease;
    line-height: 1.2;
}

.govreg-page-link:hover,
.govreg-page-btn:hover {
    background: var(--gov-accent-light);
    border-color: var(--gov-accent);
    color: var(--gov-accent);
}

.govreg-page-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
    font-weight: 600;
}

.govreg-page-link[aria-disabled="true"],
.govreg-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.govreg-page-ellipsis {
    padding: 0 4px;
    color: var(--gov-text-muted);
    font-size: 11px;
    font-weight: 500;
    line-height: 1.4;
}

/* Modal */
#govregModal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(0,0,0,.4);
    align-items: center;
    justify-content: center;
    padding: 24px;
}

#govregModal.govreg-modal-open {
    display: flex;
}

.govreg-modal-overlay {
    background: var(--gov-surface);
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius);
    box-shadow: 0 8px 24px rgba(14,28,51,.12);
    width: 100%;
    max-width: 640px;
    max-height: calc(100vh - 48px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.govreg-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--gov-border);
}

.govreg-modal-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--gov-text);
    margin: 0;
    line-height: 1.3;
}

.govreg-modal-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: var(--gov-text-muted);
    cursor: pointer;
    padding: 2px 6px;
    line-height: 1.2;
    border-radius: var(--gov-radius);
    transition: all 0.12s ease;
}

.govreg-modal-close:hover {
    background: var(--gov-border-light);
    color: var(--gov-text);
}

.govreg-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1 1 auto;
}

.govreg-modal-description {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--gov-text-secondary);
    margin: 0 0 16px;
    line-height: 1.4;
}

.govreg-form-group {
    margin-bottom: 14px;
}

.govreg-form-label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: var(--gov-text);
    margin-bottom: 4px;
    line-height: 1.3;
}

.govreg-form-label .govreg-required {
    color: var(--gov-danger);
    margin-left: 2px;
}

.govreg-form-input,
.govreg-form-select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius-md);
    background: var(--gov-surface);
    font-size: 12.5px;
    font-weight: 500;
    color: var(--gov-text);
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
    line-height: 1.3;
}

.govreg-form-input:focus,
.govreg-form-select:focus {
    border-color: var(--gov-accent);
    box-shadow: 0 0 0 3px rgba(168,121,31,.1);
}

.govreg-form-input::placeholder {
    color: var(--gov-text-muted);
}

.govreg-form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

.govreg-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
    padding-top: 14px;
    border-top: 1px solid var(--gov-border-light);
}

.govreg-form-message {
    font-size: 10px;
    font-weight: 500;
    margin-right: 8px;
    line-height: 1.4;
}

.govreg-form-message--success {
    color: var(--gov-success);
}

.govreg-form-message--error {
    color: var(--gov-danger);
}

/* Employee search results */
.govreg-employee-search-wrap {
    position: relative;
}

.govreg-employee-results {
    border: 1px solid var(--gov-border);
    border-radius: var(--gov-radius-md);
    margin-top: 4px;
    background: var(--gov-surface);
    display: none;
    max-height: 220px;
    overflow: auto;
    position: absolute;
    left: 0;
    right: 0;
    z-index: 20;
    box-shadow: 0 4px 12px rgba(14,28,51,.08);
}

.govreg-employee-result {
    padding: 8px 12px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 1px solid var(--gov-border-light);
    transition: background 0.08s ease;
    line-height: 1.4;
}

.govreg-employee-result:last-child {
    border-bottom: none;
}

.govreg-employee-result:hover {
    background: var(--gov-border-light);
}

.govreg-employee-result-name {
    font-weight: 600;
    color: var(--gov-text);
}

.govreg-employee-result-meta {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-muted);
    margin-top: 1px;
    line-height: 1.4;
}

.govreg-employee-info {
    font-size: 11px;
    font-weight: 500;
    color: var(--gov-text-muted);
    margin-top: 4px;
    line-height: 1.4;
}

/* Empty state */
.govreg-empty {
    padding: 32px 24px;
    text-align: center;
    color: var(--gov-text-muted);
    font-size: 11.5px;
    font-weight: 500;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 1100px) {
    .govreg-agency-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .govreg-module {
        padding: 4px 2px 20px;
    }

    .govreg-page-title {
        font-size: 15px;
    }

    .govreg-card {
        padding: 12px;
        border-radius: var(--gov-radius-md);
    }

    .govreg-filter-bar {
        flex-wrap: wrap;
        gap: 6px;
    }

    .govreg-search-wrap {
        flex: 1 1 100%;
        max-width: none;
    }

    .govreg-select {
        flex: 1 1 auto;
        width: auto;
    }

    .govreg-agency-grid {
        grid-template-columns: 1fr;
    }

    .govreg-agency-item {
        padding: 12px 14px;
    }

    /* Mobile table cards */
    .govreg-table-wrap {
        border: none;
        border-radius: 0;
        overflow: visible;
    }

    .govreg-table,
    .govreg-table thead,
    .govreg-table tbody,
    .govreg-table th,
    .govreg-table td,
    .govreg-table tr {
        display: block;
        width: 100%;
    }

    .govreg-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .govreg-table thead {
        display: none;
    }

    .govreg-table tbody tr {
        background: var(--gov-surface);
        border: 1px solid var(--gov-border);
        border-radius: var(--gov-radius-md);
        padding: 12px 14px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(13,27,46,.03);
    }

    .govreg-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px solid var(--gov-border-light);
        text-align: right;
    }

    .govreg-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .govreg-table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--gov-text-muted);
        text-align: left;
        flex-shrink: 0;
        line-height: 1.3;
    }

    .govreg-table td:last-child {
        justify-content: flex-end;
    }

    .govreg-col-code,
    .govreg-col-id,
    .govreg-col-date {
        font-size: 12px;
    }

    .govreg-status {
        font-size: 11px;
        padding: 2px 7px;
    }

    .govreg-pagination {
        gap: 4px;
    }

    .govreg-page-link,
    .govreg-page-btn {
        min-width: 34px;
        height: 34px;
        font-size: 11.5px;
    }

    .govreg-page-info {
        width: 100%;
        text-align: center;
        margin-top: 6px;
        font-size: 11px;
    }

    /* Modal responsive */
    #govregModal {
        padding: 16px;
        align-items: flex-end;
    }

    @media (min-width: 769px) {
        #govregModal {
            align-items: center;
        }
    }

    .govreg-modal-overlay {
        max-height: calc(100vh - 32px);
        border-radius: var(--gov-radius-md);
    }

    .govreg-modal-header {
        padding: 12px 16px;
    }

    .govreg-modal-body {
        padding: 16px;
    }

    .govreg-form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .govreg-form-actions {
        flex-wrap: wrap;
        gap: 6px;
    }

    .govreg-form-message {
        width: 100%;
        margin-right: 0;
        margin-bottom: 4px;
        text-align: center;
    }
}

@media (max-width: 400px) {
    .govreg-modal-overlay {
        max-height: calc(100vh - 16px);
    }

    .govreg-modal-body {
        padding: 14px;
    }
}

/* GOVREG AGENCY INLINE LAYOUT FIX */
.govreg-agency-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 1px !important;
}

.govreg-agency-item {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 12px !important;
    text-align: left !important;
}

.govreg-agency-icon {
    flex: 0 0 44px !important;
    width: 44px !important;
    height: 44px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
}

.govreg-agency-icon img,
.govreg-agency-logo {
    display: block !important;
    width: 36px !important;
    height: 36px !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
}

.govreg-agency-body {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    text-align: left !important;
}

.govreg-agency-name {
    color: #000 !important;
    text-align: left !important;
    font-weight: 700 !important;
}

.govreg-agency-meta,
.govreg-agency-rate {
    text-align: left !important;
}

.govreg-agency-stats {
    flex: 0 0 auto !important;
    margin-left: auto !important;
}

@media (max-width: 1100px) {
    .govreg-agency-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 768px) {
    .govreg-agency-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    .govreg-agency-item {
        gap: 9px !important;
    }

    .govreg-agency-icon {
        flex-basis: 36px !important;
        width: 36px !important;
        height: 36px !important;
    }
}
/* END GOVREG AGENCY INLINE LAYOUT FIX */

</style>

<section class="govreg-module">
    <!-- Compliance Overview -->
<div class="govreg-card">
    <div class="govreg-agency-grid">
        <?php foreach ($agencyCounts as $key => $agency):
            $agencyUrl = '?page=government-registration&agency=' . urlencode($key);
            $missing = $agency['completed'];
            $total = $agency['total'];
            $pct = $total > 0 ? round(($missing / $total) * 100, 2) : 0;
        ?>
            <a class="govreg-agency-item" href="<?= $agencyUrl ?>" aria-label="<?= htmlspecialchars($agency['label']) ?> missing">
                <div class="govreg-agency-icon">
                    <?php if (!empty($agencyLogos[$key])): ?>
                        <img src="<?= htmlspecialchars($agencyLogos[$key]) ?>" alt="<?= htmlspecialchars($agency['label']) ?>" class="govreg-agency-logo">
                    <?php else: ?>
                        <i class="bi bi-building"></i>
                    <?php endif; ?>
                </div>
                        
                <div class="govreg-agency-body">
                    <div class="govreg-agency-name"><?= htmlspecialchars($agency['label']) ?></div>
                    <div class="govreg-agency-meta"><?= number_format($missing) ?> of <?= number_format($total) ?> employees missing</div>
                    <div class="govreg-agency-rate">
                        <div class="govreg-agency-rate-fill" style="width: <?= (int) $pct ?>%;"></div>
                    </div>
                        
                </div>
                        
                <div class="govreg-agency-stats"><?= number_format($missing) ?>/<?= number_format($total) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
                        
</div>
                        

<!-- Employee Table -->
<div class="govreg-card" style="padding: 0; overflow: hidden;">
    <div class="govreg-table-wrap">
        <table class="govreg-table" id="govregEmployeeTable">
            <thead>
                <tr>
                    <th>Employee No.</th>
                    <th>Employee Name</th>
                    <th>SSS</th>
                    <th>PhilHealth</th>
                    <th>Pag-IBIG</th>
                    <th>TIN</th>
                    <th>Status</th>
                    <th>Missing</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paginatedEmployees)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 32px; color: var(--gov-text-muted);">
                            No employee records found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginatedEmployees as $emp):
                        $fullName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
                        $hasSss = !empty($emp['sss_no']);
                        $hasPhilhealth = !empty($emp['philhealth_no']);
                        $hasPagibig = !empty($emp['pagibig_no']);
                        $hasTin = !empty($emp['tin_no']);
                        $allFilled = $hasSss && $hasPhilhealth && $hasPagibig && $hasTin;
                        $anyFilled = $hasSss || $hasPhilhealth || $hasPagibig || $hasTin;
                        if ($allFilled) {
                            $overall = 'Complete';
                            $overallCls = 'complete';
                        } elseif ($anyFilled) {
                            $overall = 'Partial';
                            $overallCls = 'partial';
                        } else {
                            $overall = 'Missing';
                            $overallCls = 'missing';
                        }

                        $missingList = [];
                        if (!$hasSss) $missingList[] = 'SSS';
                        if (!$hasPhilhealth) $missingList[] = 'PhilHealth';
                        if (!$hasPagibig) $missingList[] = 'Pag-IBIG';
                        if (!$hasTin) $missingList[] = 'TIN';
                        $missingCls = !empty($missingList) ? 'govreg-missing govreg-missing--has-missing' : 'govreg-missing';

                    ?>
                    <tr>
                        <td data-label="Employee No." class="govreg-col-code"><?= htmlspecialchars($emp['employee_code'] ?? '—') ?></td>
                        <td data-label="Employee Name" class="govreg-col-name"><?= htmlspecialchars($fullName ?: 'Unknown') ?></td>
                        <td data-label="SSS" class="govreg-col-id"><?= $hasSss ? htmlspecialchars($emp['sss_no']) : '<span style="color: var(--gov-text-muted);">—</span>' ?></td>
                        <td data-label="PhilHealth" class="govreg-col-id"><?= $hasPhilhealth ? htmlspecialchars($emp['philhealth_no']) : '<span style="color: var(--gov-text-muted);">—</span>' ?></td>
                        <td data-label="Pag-IBIG" class="govreg-col-id"><?= $hasPagibig ? htmlspecialchars($emp['pagibig_no']) : '<span style="color: var(--gov-text-muted);">—</span>' ?></td>
                        <td data-label="TIN" class="govreg-col-id"><?= $hasTin ? htmlspecialchars($emp['tin_no']) : '<span style="color: var(--gov-text-muted);">—</span>' ?></td>
                        <td data-label="Status">
                            <span class="govreg-status govreg-status-<?= $overallCls ?>">
                                <?= htmlspecialchars($overall) ?>
                            </span>
                        </td>
                        <td data-label="Missing" class="<?= $missingCls ?>">
                            <?php if (!empty($missingList)): ?>
                                <div class="govreg-missing-icons">
                                    <?php foreach ($missingList as $missingAgency): ?>
                                        <?php if (!empty($agencyLogos[$missingAgency])): ?>
                                            <img src="<?= htmlspecialchars($agencyLogos[$missingAgency]) ?>" alt="<?= htmlspecialchars($missingAgency) ?>" class="govreg-missing-icon" title="<?= htmlspecialchars($missingAgency) ?> missing">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </td>
                        <td data-label="Action">
                            <div class="govreg-actions">
                                <?php if ($overallCls === 'complete'): ?>
                                    <span class="govreg-action-link govreg-action-link--disabled" aria-disabled="true" title="No action required">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                <?php else: ?>
                                    <?php
                                        $grSubject = 'Action Required: Government Registration Documents - ' . $fullName;
                                        $missingBodyList = [];
                                        if (!$hasSss) $missingBodyList[] = '- SSS Number (Missing)';
                                        if (!$hasPhilhealth) $missingBodyList[] = '- PhilHealth Number (Missing)';
                                        if (!$hasPagibig) $missingBodyList[] = '- Pag-IBIG Number (Missing)';
                                        if (!$hasTin) $missingBodyList[] = '- TIN (Missing)';
                                        $missingBodyText = !empty($missingBodyList) ? implode("\n", $missingBodyList) : 'None listed.';
                                        $grBody = "Dear {$fullName},\n\n";
                                        $grBody .= "This is a reminder regarding your government registration documents. Our records indicate that your registration is currently marked as \"{$overall}\".\n\n";
                                        $grBody .= "Employee Information:\n";
                                        $grBody .= "- Employee No.: " . ($emp['employee_code'] ?? '—') . "\n";
                                        $grBody .= "- Department: " . ($emp['department_name'] ?? '—') . "\n";
                                        $grBody .= "- Overall Status: {$overall}\n\n";
                                        $grBody .= "Government IDs Requiring Attention:\n{$missingBodyText}\n\n";
                                        $grBody .= "Please submit the required documents to the HR department at your earliest convenience. If you have any questions or need assistance, feel free to reach out to us.\n\n";
                                        $grBody .= "Best regards,\nHR Department";
                                    ?>
                                    <a class="govreg-action-link" href="?mode=reply&notification_id=0&page=notification-compose&to_recipient_no=<?= (int)$emp['employee_id'] ?>&to_recipient_name=<?= urlencode($fullName) ?>&subject=<?= urlencode($grSubject) ?>&body=<?= urlencode($grBody) ?>" title="Send Notification" aria-label="Send notification to <?= htmlspecialchars($fullName) ?>">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
                        </td>
</div>
                        </td>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="govreg-pagination" role="navigation" aria-label="Employee table pagination">
    <?php
    $baseUrl = '?page=government-registration';
    $qs = [];
    if ($filterStatus !== 'all') $qs[] = 'status=' . urlencode($filterStatus);
    if ($filterDept !== 'all') $qs[] = 'department=' . urlencode($filterDept);
    if ($searchTerm !== '') $qs[] = 'search=' . urlencode($searchTerm);
    if ($filterAgency !== '') $qs[] = 'agency=' . urlencode($filterAgency);
    $baseQs = $baseUrl . ($qs ? '&' . implode('&', $qs) : '');
    $prevPage = $page - 1;
    $nextPage = $page + 1;
    ?>
    <span class="govreg-page-info">Showing <?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $totalRows)) ?> of <?= number_format($totalRows) ?> employees</span>
    <a href="<?= $prevPage >= 1 ? $baseQs . '&gov_page=' . $prevPage : '#' ?>" class="govreg-page-link" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>&laquo; Previous</a>
    <?php
    $range = 2;
    $start = max(1, $page - $range);
    $end = min($totalPages, $page + $range);
    if ($start > 1): ?>
        <a href="<?= $baseQs . '&gov_page=1' ?>" class="govreg-page-btn">1</a>
        <?php if ($start > 2): ?>
            <span class="govreg-page-ellipsis">…</span>
        <?php endif; ?>
    <?php endif;
    for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="<?= $baseQs . '&gov_page=' . $i ?>" class="govreg-page-btn <?= $i === $page ? 'active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor;
    if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?>
            <span class="govreg-page-ellipsis">…</span>
        <?php endif; ?>
        <a href="<?= $baseQs . '&gov_page=' . $totalPages ?>" class="govreg-page-btn"><?= $totalPages ?></a>
    <?php endif; ?>
    <a href="<?= $nextPage <= $totalPages ? $baseQs . '&gov_page=' . $nextPage : '#' ?>" class="govreg-page-link" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>Next &raquo;</a>
</nav>
<?php endif; ?>
</section>

<!-- Modal -->
<div id="govregModal" role="dialog" aria-modal="true" aria-labelledby="govregModalTitle">
    <div class="govreg-modal-overlay">
        <div class="govreg-modal-header">
            <h5 class="govreg-modal-title" id="govregModalTitle">Add Government ID</h5>
            <button type="button" class="govreg-modal-close" onclick="govregCloseModal()" aria-label="Close modal">&times;</button>
        </div>
                        </td>
        <div id="govregModalBody" class="govreg-modal-body">
            <div class="govreg-empty">Loading...</div>
        </div>
                        </td>
    </div>
                        </td>
</div>
                        </td>

<script>
var govregModal = document.getElementById('govregModal');

function govregCloseModal() {
    govregModal.classList.remove('govreg-modal-open');
    govregModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function govregOpenModal() {
    govregModal.classList.add('govreg-modal-open');
    document.body.style.overflow = 'hidden';
    govregModal.setAttribute('aria-hidden', 'false');
    govregLoadForm();
}

function govregLoadForm() {
    document.getElementById('govregModalBody').innerHTML = '<div class="govreg-empty">Loading...</div>';

    var html = '<form id="govregForm">';
    html += '<input type="hidden" name="action" value="add_government_id">';
    html += '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">';
    html += '<p class="govreg-modal-description">Update an employee\'s government registration information.</p>';

    html += '<div class="govreg-form-group">';
    html += '<label class="govreg-form-label" for="govregEmployeeSearch">Employee <span class="govreg-required">*</span></label>';
    html += '<div class="govreg-employee-search-wrap">';
    html += '<input type="text" id="govregEmployeeSearch" placeholder="Search employee name or code..." autocomplete="off" class="govreg-form-input" required>';
    html += '<input type="hidden" name="employee_id" id="govregEmployeeId">';
    html += '<div id="govregEmployeeInfo" class="govreg-employee-info"></div>';
    html += '<div id="govregEmployeeResults" class="govreg-employee-results"></div>';
    html += '</div>';
    html += '</div>';

    html += '<div class="govreg-form-row">';
    html += '<div class="govreg-form-group">';
    html += '<label class="govreg-form-label" for="govregSss">SSS Number</label>';
    html += '<input type="text" name="sss_no" id="govregSss" placeholder="e.g. 34-1234567-0" class="govreg-form-input">';
    html += '</div>';
    html += '<div class="govreg-form-group">';
    html += '<label class="govreg-form-label" for="govregPhil">PhilHealth Number</label>';
    html += '<input type="text" name="philhealth_no" id="govregPhil" placeholder="e.g. 100012345678" class="govreg-form-input">';
    html += '</div>';
    html += '</div>';

    html += '<div class="govreg-form-row">';
    html += '<div class="govreg-form-group">';
    html += '<label class="govreg-form-label" for="govregPag">Pag-IBIG Number</label>';
    html += '<input type="text" name="pagibig_no" id="govregPag" placeholder="e.g. 1234-5678-9012" class="govreg-form-input">';
    html += '</div>';
    html += '<div class="govreg-form-group">';
    html += '<label class="govreg-form-label" for="govregTin">TIN</label>';
    html += '<input type="text" name="tin_no" id="govregTin" placeholder="e.g. 123-456-789-000" class="govreg-form-input">';
    html += '</div>';
    html += '</div>';

    html += '<div class="govreg-form-actions">';
    html += '<span id="govregSaveMsg" class="govreg-form-message"></span>';
    html += '<button type="button" class="govreg-btn govreg-btn-secondary" onclick="govregCloseModal()">Cancel</button>';
    html += '<button type="submit" class="govreg-btn govreg-btn-primary">Save Government ID</button>';
    html += '</div>';

    html += '</form>';

    document.getElementById('govregModalBody').innerHTML = html;

    var searchInput = document.getElementById('govregEmployeeSearch');
    var resultsBox = document.getElementById('govregEmployeeResults');
    var infoEl = document.getElementById('govregEmployeeInfo');
    var employeeIdInput = document.getElementById('govregEmployeeId');
    var debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var term = this.value.trim();
        if (term.length < 2) {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                var res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    resultsBox.innerHTML = '<div class="govreg-employee-result" style="color:var(--gov-danger);">Unable to load employees.</div>';
                    resultsBox.style.display = 'block';
                    return;
                }
                var items = res.results || [];
                if (!items.length) {
                    resultsBox.innerHTML = '<div class="govreg-employee-result" style="color:var(--gov-text-muted);">No matching employees.</div>';
                    resultsBox.style.display = 'block';
                    return;
                }
                var out = '';
                items.forEach(function(item) {
                    out += '<div class="govreg-employee-result" data-id="' + item.id + '">';
                    out += '<div class="govreg-employee-result-name">' + item.text + '</div>';
                    out += '<div class="govreg-employee-result-meta">' + item.employee_code + ' &middot; ' + item.department_name + '</div>';
                    out += '</div>';
                });
                resultsBox.innerHTML = out;
                resultsBox.style.display = 'block';
                resultsBox.querySelectorAll('.govreg-employee-result').forEach(function(el) {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        var id = this.getAttribute('data-id');
                        employeeIdInput.value = id;
                        var nameEl = this.querySelector('.govreg-employee-result-name');
                        searchInput.value = nameEl ? nameEl.textContent : this.textContent;
                        infoEl.textContent = 'Selected employee ID: ' + id;
                        resultsBox.style.display = 'none';
                    });
                });
            };
            xhr.send('action=search_employees&term=' + encodeURIComponent(term));
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && resultsBox !== e.target && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });

    document.getElementById('govregForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!employeeIdInput.value) {
            var msgEl = document.getElementById('govregSaveMsg');
            msgEl.className = 'govreg-form-message govreg-form-message--error';
            msgEl.textContent = 'Please select an employee.';
            return;
        }
        var formData = new URLSearchParams(new FormData(this));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.onload = function() {
            var res = JSON.parse(xhr.responseText);
            var msgEl = document.getElementById('govregSaveMsg');
            if (res.success) {
                msgEl.className = 'govreg-form-message govreg-form-message--success';
                msgEl.textContent = res.message;
                setTimeout(function() { location.reload(); }, 800);
            } else {
                msgEl.className = 'govreg-form-message govreg-form-message--error';
                msgEl.textContent = res.message;
            }
        };
        xhr.send(formData.toString());
    });
}

govregModal.addEventListener('click', function(e) {
    if (e.target === govregModal) {
        govregCloseModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        govregCloseModal();
    }
});

(() => {
    const grid = document.querySelector('.govreg-agency-grid');
    if (!grid) return;

    const ACTIVE_CLASS = 'is-active';
    const DOUBLE_CLICK_DELAY = 300;
    let lastClickedItem = null;
    let lastClickTime = 0;
    let clickTimer = null;

    grid.addEventListener('click', (e) => {
        const card = e.target.closest('.govreg-agency-item');
        if (!card) return;

        const now = Date.now();

        if (lastClickedItem === card && (now - lastClickTime) < DOUBLE_CLICK_DELAY) {
            clearTimeout(clickTimer);
            clickTimer = null;
            lastClickedItem = null;
            lastClickTime = 0;
            window.location.href = '/modules/compliance/index.php?page=government-registration';
            return;
        }

        clearTimeout(clickTimer);
        lastClickedItem = card;
        lastClickTime = now;

        clickTimer = setTimeout(() => {
            clickTimer = null;
            if (card.classList.contains(ACTIVE_CLASS)) {
                card.classList.remove(ACTIVE_CLASS);
            } else {
                grid.querySelectorAll(`.${ACTIVE_CLASS}`).forEach((el) => el.classList.remove(ACTIVE_CLASS));
                card.classList.add(ACTIVE_CLASS);
            }
            lastClickedItem = null;
            lastClickTime = 0;
        }, DOUBLE_CLICK_DELAY);
    });
})();
</script>

