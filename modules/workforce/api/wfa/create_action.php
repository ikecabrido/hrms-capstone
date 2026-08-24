<?php
/**
 * create_action.php
 * API Endpoint: Create Action/Intervention
 * 
 * POST /api/wfa/create_action.php
 * Body: {
 *   "employee_id": 1,
 *   "action_type": "Training|Warning|PIP|Mentoring",
 *   "description": "...",
 *   "pip_id": null,
 *   "assigned_to": null,
 *   "due_date": "2026-05-15"
 * }
 * 
 * Response: {"success": bool, "action_id": int, "message": string}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once('../../config/config.php');
require_once('../../config/Database.php');

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['employee_id']) || empty($data['action_type']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: employee_id, action_type, description'
        ]);
        exit;
    }
    
    // Validate action type
    $valid_types = ['Training', 'Warning', 'PIP', 'Mentoring', 'Counseling', 'Suspension'];
    if (!in_array($data['action_type'], $valid_types)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action_type. Must be one of: ' . implode(', ', $valid_types)
        ]);
        exit;
    }
    
    // Connect to database
    $db = Database::getInstance()->getConnection();
    
    // Load ActionSystem
    require_once('../../models/ActionSystem.php');
    $action_system = new \WFA\System\ActionSystem($db);
    
    // Create action
    $result = $action_system->createAction([
        'employee_id' => intval($data['employee_id']),
        'action_type' => $data['action_type'],
        'description' => $data['description'],
        'pip_id' => $data['pip_id'] ?? null,
        'assigned_to' => $data['assigned_to'] ?? null,
        'due_date' => $data['due_date'] ?? null
    ]);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(500);
    }
    
    echo json_encode($result);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
