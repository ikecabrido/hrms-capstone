<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

try {
    $database = TimeDatabase::getInstance();
    $db = $database->getConnection();

    $id = $_GET['employee_id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'employee_id required']);
        exit;
    }

        $sql = "SELECT e.employee_id, e.employee_no, e.full_name, COALESCE(d.department_name, '') AS department, COALESCE(p.position_name, '') AS position, u.profile_pic
            FROM employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            LEFT JOIN em_users u ON u.employee_id = e.employee_id
            WHERE e.employee_id = :id OR e.employee_no = :id
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'profile' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
