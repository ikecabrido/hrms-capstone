<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 6) . '/database/db.php';

$employeeId = (int)($_GET['employee_id'] ?? $_POST['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Employee ID is required']));
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        SELECT ed.document_id, ed.document_name, ed.document_type, ed.category,
               ed.file_path, ed.file_name, ed.file_size, ed.mime_type,
               ed.expiry_date, ed.created_at, ed.uploaded_by,
               CONCAT(emp.first_name, ' ', IFNULL(CONCAT(emp.middle_name, ' '), ''), emp.last_name) AS uploaded_by_name
        FROM employee_documents ed
        LEFT JOIN em_employees emp ON emp.employee_id = ed.uploaded_by
        WHERE ed.employee_id = :eid
        ORDER BY ed.created_at DESC
    ");
    $stmt->execute([':eid' => $employeeId]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $docs, 'total' => count($docs)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
