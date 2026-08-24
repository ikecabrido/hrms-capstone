<?php
/**
 * get_low_performers.php
 * API Endpoint: Get employees with performance issues
 * 
 * GET /api/wfa/get_low_performers.php
 * Query: ?limit=20&offset=0
 * 
 * Response: {
 *   "success": bool,
 *   "data": [{
 *     "employee_id": 1,
 *     "full_name": "John Doe",
 *     "position": "Developer",
 *     "department": "IT",
 *     "rating": 2.3,
 *     "absences": 5,
 *     "tardiness": 3,
 *     "issues": [...],
 *     "severity": "High",
 *     "recommendation": {...}
 *   }],
 *   "total": 5
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once('../../config/config.php');
require_once('../../config/Database.php');

try {
    // Connect to database
    $db = Database::getInstance()->getConnection();
    
    // Load ActionSystem
    require_once('../../models/ActionSystem.php');
    $action_system = new \WFA\System\ActionSystem($db);
    
    // Get low performers
    $employees = $action_system->getLowPerformanceEmployees();
    
    // Apply pagination
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    
    $total = count($employees);
    $paginated = array_slice($employees, $offset, $limit);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $paginated,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
