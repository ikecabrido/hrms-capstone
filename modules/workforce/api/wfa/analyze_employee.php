<?php
/**
 * analyze_employee.php
 * API Endpoint: Analyze single employee and get recommendations
 * 
 * GET /api/wfa/analyze_employee.php?employee_id=1
 * 
 * Response: {
 *   "success": bool,
 *   "employee_id": 1,
 *   "analysis": {
 *     "issues": [...],
 *     "severity": "High",
 *     "recommendation": {
 *       "recommended_action": "Create PIP",
 *       "confidence_score": 0.90,
 *       "action_rationale": "..."
 *     }
 *   }
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once('../../config/config.php');
require_once('../../config/Database.php');

try {
    // Validate employee_id parameter
    if (empty($_GET['employee_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing employee_id parameter'
        ]);
        exit;
    }
    
    $employee_id = intval($_GET['employee_id']);
    
    // Connect to database
    $db = Database::getInstance()->getConnection();
    
    // Load ActionSystem
    require_once('../../models/ActionSystem.php');
    $action_system = new \WFA\System\ActionSystem($db);
    
    // Analyze employee
    $analysis = $action_system->detectPerformanceIssues($employee_id);
    
    // Get recommendation
    $recommendation = $action_system->recommendAction($analysis);
    
    // Store recommendation for audit trail
    $action_system->storeRecommendation($employee_id, $recommendation);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'analysis' => [
            'issues' => $analysis['issues'],
            'severity' => $analysis['severity'],
            'metrics' => $analysis['metrics'],
            'recommendation' => $recommendation
        ]
    ]);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
