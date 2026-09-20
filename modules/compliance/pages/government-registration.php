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
    'SSS' => '/hrms-capstone/modules/compliance/assets/sss.png',
    'PhilHealth' => '/hrms-capstone/modules/compliance/assets/philhealth.webp',
    'Pag-IBIG' => '/hrms-capstone/modules/compliance/assets/pagibig.webp',
    'TIN' => '/hrms-capstone/modules/compliance/assets/bir.png',
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
.govreg-module { padding: 4px 2px 24px; }
.govreg-breadcrumb { margin-bottom:10px; }
.govreg-breadcrumb .breadcrumb { background:transparent; padding:0; margin:0; font-size:0.8rem; }
.govreg-breadcrumb .breadcrumb-item a { color:var(--info-blue,#3b82c4); text-decoration:none; }
.govreg-breadcrumb .breadcrumb-item a:hover { text-decoration:underline; }
.govreg-breadcrumb .breadcrumb-item.active { color:var(--text-500,#6b7280); }

.govreg-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:14px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:14px; }
.govreg-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.govreg-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.govreg-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

 .govreg-card-body { display:flex; flex-direction:column; max-height: 540px; overflow: hidden; }
 @media (min-width: 769px) {
   .govreg-card-body { max-height: none; overflow: visible; }
 }
 .govreg-table-wrap { overflow: auto; flex: 1 1 auto; }
 @media (min-width: 769px) {
   .govreg-table-wrap { overflow: visible; }
 }
 .govreg-table { width:100%; border-collapse:collapse; font-size:0.82rem; table-layout: auto; min-width: 900px; }
 .govreg-table thead th { position: sticky; top: 0; z-index: 2; }
 .govreg-table th { text-align:left; padding:10px 12px; font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; }
 .govreg-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:middle; }
 .govreg-table tr:last-child td { border-bottom:none; }
.govreg-stamp { display:inline-block; font-size:0.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.govreg-stamp-complete { background:rgba(47,158,110,.12); color:#1f7a52; }
.govreg-stamp-partial { background:rgba(217,154,43,.14); color:#a86b13; }
.govreg-stamp-missing { background:rgba(214,72,74,.12); color:#a3272a; }

.govreg-filter-bar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
.govreg-filter-bar select { padding:7px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.78rem; outline:none; background:#fff; min-width:140px; }
.govreg-filter-bar select:focus { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.12); }
.govreg-filter-bar .govreg-search-wrap { position:relative; }
.govreg-filter-bar .govreg-search-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-400,#8b93a1); font-size:0.85rem; pointer-events:none; }
.govreg-filter-bar .govreg-search-input { padding:7px 28px 7px 30px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; font-size:0.8rem; min-width:200px; }
.govreg-filter-bar .govreg-search-input:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.1); }
.govreg-filter-bar .govreg-search-clear { position:absolute; right:8px; font-size:1rem; color:var(--text-400,#8b93a1); text-decoration:none; line-height:1; }
.govreg-filter-bar .govreg-search-clear:hover { color:var(--text-900,#1b2430); }

.govreg-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-700, #3b4252);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
    text-decoration: none;
    gap: 6px;
}
.govreg-action-btn:hover:not(:disabled) {
    border-color: var(--seal-gold, #a8791f);
    color: var(--seal-gold, #a8791f);
    box-shadow: 0 0 0 3px rgba(168, 121, 31, 0.08);
}
.govreg-action-btn:disabled {
    background: var(--paper, #eef1f5);
    border-color: var(--hairline, #dde3ea);
    color: var(--text-400, #8b95a4);
    cursor: not-allowed;
    box-shadow: none;
}

.govreg-pagination { display:flex; justify-content:center; align-items:center; gap:6px; margin:16px 0; flex-wrap:wrap; }
.govreg-pagination .govreg-page-info { font-size:0.78rem; color:var(--text-600,#5b6472); }
.govreg-pagination .govreg-page-btn,
.govreg-pagination .govreg-page-link { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.8rem; text-decoration:none; color:var(--text-900,#1b2430); background:#fff; transition:all .15s ease; }
.govreg-pagination .govreg-page-link:hover { background:var(--seal-gold-light,#f4e6c9); border-color:var(--seal-gold,#a8791f); color:var(--seal-gold,#a8791f); }
.govreg-pagination .govreg-page-btn.active { background:var(--seal-gold,#a8791f); color:#fff; border-color:var(--seal-gold,#a8791f); }
.govreg-pagination .govreg-page-btn:hover:not(.active) { background:var(--seal-gold-light,#f4e6c9); border-color:var(--seal-gold,#a8791f); color:var(--seal-gold,#a8791f); }
.govreg-pagination .govreg-page-btn:disabled { background:var(--paper,#eef1f5); border-color:var(--hairline,#dde3ea); color:var(--text-400,#8b95a4); cursor:not-allowed; }

.govreg-id-field { font-size:0.78rem; color:var(--text-600,#5b6472); font-family:monospace; }
.govreg-notice { background:rgba(59,130,196,.06); border:1px solid rgba(59,130,196,.2); border-radius:10px; padding:12px 16px; margin-bottom:14px; font-size:0.82rem; color:var(--text-700,#3b4252); display:flex; align-items:flex-start; gap:8px; }
.govreg-notice i { color:var(--info-blue,#3b82c4); margin-top:2px; }

.govreg-agency-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.govreg-agency-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.govreg-agency-item:hover { border-color:var(--seal-gold,#a8791f); box-shadow:0 0 0 3px rgba(168,121,31,.08); }
.govreg-agency-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.govreg-agency-icon.gray { background:rgba(107,114,128,.12); color:#4b5563; }
.govreg-agency-logo { width:28px; height:28px; object-fit:contain; }
.govreg-agency-value { font-size:1.25rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.govreg-agency-label { font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }
.govreg-rate-bar { height:6px; border-radius:999px; background:#e5e7eb; margin-top:8px; overflow:hidden; }
.govreg-rate-fill { height:100%; border-radius:999px; background:var(--seal-gold,#a8791f); }

@media (max-width: 1100px) {
  .govreg-table-wrap { overflow-x: auto; }
}

/* ============================================
   RESPONSIVE OVERRIDES
   ============================================ */

/* Prevent horizontal overflow */
.govreg-module {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.govreg-card {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
}

/* Agency bar: tablet 2-col, mobile 2-col grid */
@media (max-width: 1100px) {
    .govreg-agency-bar {
        gap: 10px;
    }
    .govreg-agency-item {
        min-width: 0;
        flex: 1 1 calc(50% - 10px);
        max-width: calc(50% - 10px);
    }
    .govreg-agency-item > div {
        min-width: 0;
    }
}

@media (max-width: 768px) {
    .govreg-agency-bar {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .govreg-agency-item {
        min-width: 0;
        max-width: 100%;
        flex: 1 1 auto;
    }
    .govreg-agency-item > div {
        min-width: 0;
    }
    .govreg-agency-value {
        font-size: 1.1rem;
    }
}

@media (max-width: 400px) {
    .govreg-agency-bar {
        grid-template-columns: 1fr;
    }
}

/* Card head wrapping */
.govreg-card-head {
    flex-wrap: wrap;
    gap: 8px;
}

/* Filter bar responsive readiness */
.govreg-filter-bar {
    flex-wrap: wrap;
    gap: 8px;
}
.govreg-filter-bar select,
.govreg-filter-bar .govreg-search-input {
    min-width: 0;
}
@media (max-width: 768px) {
    .govreg-filter-bar select,
    .govreg-filter-bar .govreg-search-input {
        width: 100%;
        max-width: 100%;
        flex: 1 1 auto;
    }
}
@media (min-width: 769px) {
    .govreg-filter-bar select {
        width: auto;
        min-width: 140px;
        flex: 0 0 auto;
    }
    .govreg-filter-bar .govreg-search-input {
        width: auto;
        min-width: 200px;
        flex: 1 1 auto;
        max-width: 400px;
    }
}

/* ============================================
   MOBILE CARD TABLE (max-width: 768px)
   ============================================ */

@media (max-width: 768px) {
    .govreg-card-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .govreg-table-wrap {
        overflow: visible !important;
        flex: none !important;
    }

    .govreg-table,
    .govreg-table thead,
    .govreg-table tbody,
    .govreg-table th,
    .govreg-table td,
    .govreg-table tr {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .govreg-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .govreg-table thead {
        display: none;
    }

    .govreg-table tr {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e4e8ee);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
    }

    .govreg-table td {
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

    .govreg-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .govreg-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-400, #8b93a1);
        text-align: left;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .govreg-table td:last-child {
        justify-content: flex-end;
    }

    .govreg-id-field {
        text-align: right;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .govreg-stamp {
        font-size: 0.72rem;
        padding: 3px 10px;
    }

    .govreg-action-btn {
        height: 40px;
        min-width: 40px;
        padding: 0 12px;
    }
}

/* ============================================
   PAGINATION RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .govreg-pagination {
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }
    .govreg-pagination .govreg-page-link,
    .govreg-pagination .govreg-page-btn {
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        font-size: 0.78rem;
    }
    .govreg-pagination .govreg-page-info {
        width: 100%;
        text-align: center;
        margin-top: 4px;
        font-size: 0.75rem;
    }
}

/* ============================================
   MODAL RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    #govregModal > div {
        width: calc(100% - 24px) !important;
        max-width: 700px !important;
        max-height: calc(100vh - 24px) !important;
        border-radius: 12px !important;
        margin: auto !important;
    }
    #govregModalBody {
        padding: 16px !important;
    }
    #govregModal h5 {
        font-size: 0.95rem;
    }
    #govregModal button[type="button"][onclick="govregCloseModal()"] {
        font-size: 1.1rem;
    }
}

@media (max-width: 400px) {
    #govregModal > div {
        width: calc(100% - 16px) !important;
        max-height: calc(100vh - 16px) !important;
    }
    #govregModalBody {
        padding: 12px !important;
    }
}

/* Modal form inputs */
#govregForm input[type="text"] {
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    #govregForm input[type="text"] {
        min-height: 44px;
        font-size: 0.9rem;
    }
    #govregForm button[type="submit"] {
        width: 100%;
        justify-content: center;
    }
    #govregForm button[type="button"] {
        flex: 1;
        text-align: center;
        justify-content: center;
    }
    #govregForm > div:last-child {
        flex-wrap: wrap;
        gap: 8px;
    }
}
</style>

<section class="govreg-module">

    <div class="govreg-card">
        <div class="govreg-card-head">
            <h3><i class="bi bi-bar-chart"></i> Compliance Overview</h3>
            <div style="font-size:0.82rem; color:var(--text-600,#5b6472);">
                Compliance Rate: <strong><?= number_format($complianceRate, 2) ?>%</strong>
            </div>
        </div>
        <div class="govreg-agency-bar">
            <?php foreach ($agencyCounts as $key => $agency):
                $agencyUrl = '?page=government-registration&agency=' . urlencode($key);
                $missing = $agency['completed'];
                $total = $agency['total'];
                $pct = $total > 0 ? round(($missing / $total) * 100, 2) : 0;
            ?>
                <a class="govreg-agency-item" href="<?= $agencyUrl ?>" style="text-decoration:none; color:inherit;">
                    <div class="govreg-agency-icon gray">
                        <?php if (!empty($agencyLogos[$key])): ?>
                            <img src="<?= htmlspecialchars($agencyLogos[$key]) ?>" alt="<?= htmlspecialchars($agency['label']) ?>" class="govreg-agency-logo">
                        <?php else: ?>
                            <i class="bi bi-building"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1; min-width:140px;">
                        <div class="govreg-agency-value"><?= number_format($missing) ?>/<?= number_format($total) ?></div>
                        <div class="govreg-agency-label"><?= htmlspecialchars($agency['label']) ?> Missing - <?= number_format($pct, 2) ?>%</div>
                        <div class="govreg-rate-bar"><div class="govreg-rate-fill" style="width: <?= (int) $pct ?>%;"></div></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="govreg-card">
        <div class="govreg-card-head">
            <h3><i class="bi bi-person-badge"></i> Employee Government IDs</h3>
        </div>

        <div class="govreg-card-body">
            <?php if (empty($paginatedEmployees)): ?>
                <div class="govreg-empty">No employee records found matching your criteria.</div>
            <?php else: ?>
            <div class="govreg-table-wrap">
                <table class="govreg-table" id="govregEmployeeTable">
                    <thead>
                        <tr>
                            <th>Employee No.</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>SSS No.</th>
                            <th>PhilHealth No.</th>
                            <th>Pag-IBIG No.</th>
                            <th>TIN</th>
                            <th>Status</th>
                            <th>Missing Requirements</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
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
                            $missingText = !empty($missingList) ? implode(', ', $missingList) : 'None';

                            $lastUpdated = !empty($emp['gov_updated']) ? date('M d, Y H:i', strtotime($emp['gov_updated'])) : 'Not yet registered';
                        ?>
                        <tr>
                            <td data-label="Employee No."><?= htmlspecialchars($emp['employee_code'] ?? '—') ?></td>
                            <td data-label="Employee Name"><?= htmlspecialchars($fullName ?: 'Unknown') ?></td>
                            <td data-label="Department"><?= htmlspecialchars($emp['department_name'] ?? '—') ?></td>
                            <td data-label="SSS No."><span class="govreg-id-field"><?= htmlspecialchars($emp['sss_no'] ?? '—') ?></span></td>
                            <td data-label="PhilHealth No."><span class="govreg-id-field"><?= htmlspecialchars($emp['philhealth_no'] ?? '—') ?></span></td>
                            <td data-label="Pag-IBIG No."><span class="govreg-id-field"><?= htmlspecialchars($emp['pagibig_no'] ?? '—') ?></span></td>
                            <td data-label="TIN"><span class="govreg-id-field"><?= htmlspecialchars($emp['tin_no'] ?? '—') ?></span></td>
                            <td data-label="Status"><span class="govreg-stamp govreg-stamp-<?= $overallCls ?>"><?= htmlspecialchars($overall) ?></span></td>
                            <td data-label="Missing Requirements"><?= htmlspecialchars($missingText) ?></td>
                            <td data-label="Last Updated"><?= htmlspecialchars($lastUpdated) ?></td>
                            <td data-label="Action">
                                <?php if ($overallCls !== 'complete'):
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
                                    <a class="govreg-action-btn" href="?mode=reply&notification_id=0&page=notification-compose&to_recipient_no=<?= (int)$emp['employee_id'] ?>&to_recipient_name=<?= urlencode($fullName) ?>&subject=<?= urlencode($grSubject) ?>&body=<?= urlencode($grBody) ?>" title="Send Notification">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
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
                <a href="<?= $prevPage >= 1 ? $baseQs . '&gov_page=' . $prevPage : '#' ?>" class="govreg-page-link" <?= $prevPage < 1 ? 'aria-disabled="true"' : '' ?>>⟨⟨</a>
                <?php
                $range = 2;
                $start = max(1, $page - $range);
                $end = min($totalPages, $page + $range);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="<?= $baseQs . '&gov_page=' . $i ?>" class="govreg-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="<?= $nextPage <= $totalPages ? $baseQs . '&gov_page=' . $nextPage : '#' ?>" class="govreg-page-link" <?= $nextPage > $totalPages ? 'aria-disabled="true"' : '' ?>>⟩⟩</a>
                <span class="govreg-page-info">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRows ?> records)</span>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="govregModal" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,.5); align-items:center; justify-content:center;">
  <div style="background:var(--card-bg,#fff); border-radius:14px; box-shadow:var(--shadow-lg,0 12px 32px rgba(14,28,51,.14)); width:90%; max-width:700px; max-height:85vh; display:flex; flex-direction:column; margin:auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border,#e4e8ee);">
      <h5 style="margin:0; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430);">Add Government ID</h5>
      <button type="button" onclick="govregCloseModal()" style="background:none; border:none; font-size:1.25rem; color:var(--text-400,#8b93a1); cursor:pointer; padding:4px; line-height:1;">&times;</button>
    </div>
    <div id="govregModalBody" style="padding:20px; overflow-y:auto; flex:1 1 auto;">
      <div class="govreg-empty">Loading...</div>
    </div>
  </div>
</div>

<script>
var govregModal = document.getElementById('govregModal');

function govregCloseModal() {
    govregModal.setAttribute('aria-hidden', 'true');
    govregModal.style.display = 'none';
    document.body.style.overflow = '';
}

function govregOpenModal() {
    govregModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    govregModal.setAttribute('aria-hidden', 'false');
    govregLoadForm();
}

function govregLoadForm() {
    document.getElementById('govregModalBody').innerHTML = '<div class="govreg-empty">Loading...</div>';

    var html = '<form id="govregForm">';
    html += '<input type="hidden" name="action" value="add_government_id">';
    html += '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">';

    html += '<div style="margin-bottom:14px;">';
    html += '<label style="font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:4px; display:block;">Employee</label>';
    html += '<input type="text" id="govregEmployeeSearch" placeholder="Search employee name or code..." autocomplete="off" style="width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;" required>';
    html += '<input type="hidden" name="employee_id" id="govregEmployeeId">';
    html += '<div id="govregEmployeeInfo" style="font-size:0.78rem; color:var(--text-400,#8b93a1); margin-top:4px;"></div>';
    html += '<div id="govregEmployeeResults" style="border:1px solid var(--border,#e4e8ee); border-radius:8px; margin-top:4px; background:#fff; display:none; max-height:200px; overflow:auto; position:relative; z-index:10;"></div>';
    html += '</div>';

    html += '<div style="margin-bottom:14px;">';
    html += '<label style="font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:4px; display:block;">SSS Number</label>';
    html += '<input type="text" name="sss_no" placeholder="e.g. 34-1234567-0" style="width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;">';
    html += '</div>';

    html += '<div style="margin-bottom:14px;">';
    html += '<label style="font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:4px; display:block;">PhilHealth Number</label>';
    html += '<input type="text" name="philhealth_no" placeholder="e.g. 100012345678" style="width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;">';
    html += '</div>';

    html += '<div style="margin-bottom:14px;">';
    html += '<label style="font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:4px; display:block;">Pag-IBIG Number</label>';
    html += '<input type="text" name="pagibig_no" placeholder="e.g. 1234-5678-9012" style="width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;">';
    html += '</div>';

    html += '<div style="margin-bottom:14px;">';
    html += '<label style="font-size:0.78rem; font-weight:700; color:var(--text-700,#3b4252); margin-bottom:4px; display:block;">TIN</label>';
    html += '<input type="text" name="tin_no" placeholder="e.g. 123-456-789-000" style="width:100%; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;">';
    html += '</div>';

    html += '<div style="display:flex; justify-content:flex-end; gap:10px;">';
    html += '<button type="button" onclick="govregCloseModal()" style="padding:8px 16px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; font-size:0.85rem; cursor:pointer; color:var(--text-700,#3b4252);">Cancel</button>';
    html += '<button type="submit" class="govreg-action-btn" style="width:auto; padding:8px 16px; font-size:0.85rem;">Save</button>';
    html += '<span id="govregSaveMsg" style="margin-left:10px; font-size:0.85rem;"></span>';
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
                    resultsBox.innerHTML = '<div style="padding:8px 10px; font-size:0.82rem; color:#a3272a;">Unable to load employees.</div>';
                    resultsBox.style.display = 'block';
                    return;
                }
                var items = res.results || [];
                if (!items.length) {
                    resultsBox.innerHTML = '<div style="padding:8px 10px; font-size:0.82rem; color:var(--text-400,#8b93a1);">No matching employees.</div>';
                    resultsBox.style.display = 'block';
                    return;
                }
                var out = '';
                items.forEach(function(item) {
                    out += '<div data-id="' + item.id + '" style="padding:8px 10px; font-size:0.82rem; cursor:pointer; border-bottom:1px solid var(--border,#e4e8ee);">' + item.text + '</div>';
                });
                resultsBox.innerHTML = out;
                resultsBox.style.display = 'block';
                resultsBox.querySelectorAll('[data-id]').forEach(function(el) {
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        var id = this.getAttribute('data-id');
                        employeeIdInput.value = id;
                        searchInput.value = this.textContent;
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
            msgEl.style.color = '#a3272a';
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
                msgEl.style.color = '#1f7a52';
                msgEl.textContent = res.message;
                setTimeout(function() { location.reload(); }, 800);
            } else {
                msgEl.style.color = '#a3272a';
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
</script>
