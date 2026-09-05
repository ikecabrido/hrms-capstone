<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

$q = trim((string) ($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if (!($db instanceof PDO)) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $searchTerm = '%' . $q . '%';

    $sql = "
        SELECT
            e.employee_id     AS employee_id,
            e.employee_code   AS employee_no,
            CONCAT(e.first_name, ' ', e.last_name) AS full_name,
            e.email           AS email,
            e.employment_status AS employment_status,
            COALESCE(d.department_name, 'N/A') AS department_name,
            COALESCE(p.position_name, 'N/A') AS position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON d.department_id = e.department_id
        LEFT JOIN em_positions p ON p.position_id = e.position_id
        WHERE (CONCAT(e.first_name, ' ', e.last_name) LIKE :q1 OR e.employee_code LIKE :q2 OR e.email LIKE :q3)
          AND e.employment_status NOT IN ('Resigned', 'Terminated')
        ORDER BY full_name ASC
        LIMIT 20
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':q1' => $searchTerm,
        ':q2' => $searchTerm,
        ':q3' => $searchTerm,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $data = [];
    foreach ($rows as $r) {
        $fullName = trim((string) ($r['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string) ($r['employee_id'] ?? 'Employee');
        }

        $data[] = [
            'employee_id'      => $r['employee_id']      ?? '',
            'employee_no'      => $r['employee_no']      ?? '',
            'full_name'        => $fullName,
            'email'            => $r['email']            ?? '',
            'job_title'        => $r['position_name']    ?? '',
            'department'       => $r['department_name']  ?? '',
            'employment_status' => $r['employment_status'] ?? '',
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'data' => []]);
}

