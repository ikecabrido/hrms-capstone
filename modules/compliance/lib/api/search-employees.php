<?php
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();
if (!($db instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Database connection unavailable',
    ]);
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$params = [];
$conds = [];

$conds[] = 'e.first_name LIKE :q OR e.last_name LIKE :q OR e.employee_code LIKE :q OR e.email LIKE :q';
$params[':q'] = '%' . $q . '%';

$sql = 'SELECT e.employee_id, e.first_name, e.last_name, e.employee_code AS employee_no, e.email,
            d.department_name, p.position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        WHERE (' . implode(' OR ', $conds) . ')
        AND e.email IS NOT NULL AND e.email <> \'\'
        ORDER BY e.first_name ASC, e.last_name ASC
        LIMIT 20';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function($r) {
    return [
        'employee_id'   => (int) ($r['employee_id'] ?? 0),
        'full_name'     => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
        'employee_no'   => (string) ($r['employee_no'] ?? ''),
        'email'         => (string) ($r['email'] ?? ''),
        'department'    => (string) ($r['department_name'] ?? ''),
        'position'      => (string) ($r['position_name'] ?? ''),
    ];
}, $rows);

echo json_encode(['success' => true, 'data' => $data]);
