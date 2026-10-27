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

$employeeId = (int)($_POST['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Employee ID is required']));
}

// Build update data
$data = [];
$fields = [
    'first_name', 'middle_name', 'last_name', 'suffix',
    'email', 'mobile_no', 'phone_no',
    'gender', 'birth_date', 'birth_place',
    'civil_status', 'citizenship', 'religion',
    'current_address', 'permanent_address',
    'department_id', 'position_id',
    'hire_date', 'regular_date',
    'employment_status', 'employment_type',
    'unit_load', 'graduate_level',
    'ranking', 'credentials', 'faculty_notes', 'negotiated_salary',
];

foreach ($fields as $f) {
    if (isset($_POST[$f]) && $_POST[$f] !== '') {
        $data[$f] = $_POST[$f];
    }
}

if (empty($data)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'No fields to update']));
}

try {
    $pdo = (new Database())->getConnection();

    $setParts = [];
    $params = [];
    foreach ($data as $key => $value) {
        $setParts[] = "$key = :$key";
        $params[":$key"] = $value;
    }
    $params[':eid'] = $employeeId;

    $sql = "UPDATE em_employees SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE employee_id = :eid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Employee not found']));
    }

    echo json_encode([
        'success' => true,
        'id' => $employeeId,
        'message' => 'Employee profile updated successfully',
        'updated_fields' => array_keys($data),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
