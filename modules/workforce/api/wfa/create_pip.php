<?php
/**
 * create_pip.php
 * API Endpoint: Create Performance Improvement Plan
 * 
 * POST /api/wfa/create_pip.php
 * Body: {
 *   "employee_id": 1,
 *   "reason": "Low performance and high absenteeism",
 *   "action_plan": "Detailed action plan...",
 *   "start_date": "2026-04-07",
 *   "end_date": "2026-07-07",
 *   "performance_target": 3.5,
 *   "created_by": 1
 * }
 * 
 * Response: {"success": bool, "pip_id": int, "message": string}
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
    if (empty($data['employee_id']) || empty($data['reason']) || empty($data['action_plan'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: employee_id, reason, action_plan'
        ]);
        exit;
    }
    
    // Validate dates
    if (empty($data['start_date']) || empty($data['end_date'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: start_date, end_date (format: YYYY-MM-DD)'
        ]);
        exit;
    }
    
    // Validate date format and logic
    $start = strtotime($data['start_date']);
    $end = strtotime($data['end_date']);
    
    if (!$start || !$end) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use YYYY-MM-DD'
        ]);
        exit;
    }
    
    if ($end <= $start) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'End date must be after start date'
        ]);
        exit;
    }
    
    // Connect to database
    $db = Database::getInstance()->getConnection();
    
    // Load ActionSystem
    require_once('../../models/ActionSystem.php');
    $action_system = new \WFA\System\ActionSystem($db);
    
    // Create PIP
    $result = $action_system->createPerformanceImprovementPlan([
        'employee_id' => intval($data['employee_id']),
        'reason' => $data['reason'],
        'action_plan' => $data['action_plan'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'performance_target' => floatval($data['performance_target'] ?? 3.0),
        'created_by' => intval($data['created_by'] ?? 1)
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
