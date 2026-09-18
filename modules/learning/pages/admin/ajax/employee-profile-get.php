<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 6) . '/database/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$employeeId = (int)($input['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Employee ID is required']));
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.employee_code, e.first_name, e.middle_name, e.last_name, e.suffix,
               e.email, e.mobile_no, e.phone_no, e.gender, e.birth_date, e.birth_place,
               e.civil_status, e.citizenship, e.religion, e.current_address, e.permanent_address,
               e.department_id, e.position_id, e.hire_date, e.regular_date,
               e.employment_status, e.employment_type, e.unit_load, e.graduate_level,
               e.ranking, e.credentials, e.faculty_notes, e.negotiated_salary,
               e.created_at,
               d.department_name, p.position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON d.department_id = e.department_id
        LEFT JOIN em_positions p ON p.position_id = e.position_id
        WHERE e.employee_id = :eid
        LIMIT 1
    ");
    $stmt->execute([':eid' => $employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Employee not found']);
    } else {
        echo json_encode(['success' => true, 'data' => $row]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
