<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $employeeIds = [];

    if (!empty($_GET['employee_ids'])) {
        $rawIds = explode(',', $_GET['employee_ids']);
        foreach ($rawIds as $rawId) {
            $cleanId = trim($rawId);
            if ($cleanId !== '') {
                $employeeIds[] = (int)$cleanId;
            }
        }
    }

    $conditions = [];
    $params = [];
    if (!empty($employeeIds)) {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $conditions[] = "employee_id IN ($placeholders)";
        $params = $employeeIds;
    }

    $query = "
        SELECT action_id, employee_id, action_type, description, status, assigned_to, due_date, completion_date, notes, created_at, updated_at
        FROM wfa_actions
    ";

    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $query .= ' ORDER BY updated_at DESC';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $actions,
        'total' => count($actions)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
