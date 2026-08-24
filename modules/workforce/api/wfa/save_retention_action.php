<?php
/**
 * save_retention_action.php
 * Lightweight persistence endpoint for the attrition retention tracker.
 *
 * POST body:
 * {
 *   "employee_id": 12,
 *   "action_type": "Counseling",
 *   "description": "Retention action",
 *   "status": "In Progress",
 *   "assigned_to": null,
 *   "due_date": "2026-08-10",
 *   "notes": "Manager follow-up"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON payload');
    }

    if (empty($input['employee_id'])) {
        throw new Exception('Missing required field: employee_id');
    }

    $validActionTypes = ['Training', 'Warning', 'PIP', 'Mentoring', 'Counseling', 'Suspension'];
    $actionType = $input['action_type'] ?? 'Counseling';
    if (!in_array($actionType, $validActionTypes)) {
        $actionType = 'Counseling';
    }

    $validStatuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
    $status = $input['status'] ?? 'Pending';
    if (!in_array($status, $validStatuses)) {
        $status = 'Pending';
    }

    $db = Database::getInstance()->getConnection();

    $query = "
        INSERT INTO wfa_actions
        (employee_id, action_type, description, status, assigned_to, due_date, completion_date, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?, NOW(), NOW())
    ";

    $stmt = $db->prepare($query);
    $description = $input['description'] ?? 'Retention follow-up action';
    $owner = $input['assigned_to'] ?? null;
    $dueDate = $input['due_date'] ?? null;
    $notes = $input['notes'] ?? null;

    $result = $stmt->execute([
        (int)$input['employee_id'],
        $actionType,
        $description,
        $status,
        $owner,
        $dueDate,
        $notes
    ]);

    if (!$result) {
        throw new Exception('Unable to save retention action');
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Retention action saved successfully',
        'data' => [
            'employee_id' => (int)$input['employee_id'],
            'status' => $status,
            'action_type' => $actionType
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
