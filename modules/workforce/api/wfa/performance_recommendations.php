<?php
/**
 * Performance Recommendations API
 * Returns root causes and recommended actions for employee performance issues
 * Endpoint: GET /workforce/api/wfa/performance_recommendations.php?employee_id={id}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting but don't output errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering and clean
ob_start();
ob_clean();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Analytics.php';

try {
    // Get employee ID from query parameter
    $employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : null;
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing employee_id parameter'
        ]);
        exit;
    }
    
    // Initialize analytics
    $analytics = new Analytics();
    
    // Get comprehensive assessment
    $assessment = $analytics->getPerformanceAssessment($employee_id);
    
    if (!$assessment) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Employee not found'
        ]);
        exit;
    }
    
    // Get active actions for this employee (handle if table doesn't exist)
    $activeActions = [];
    $activePIP = null;
    
    try {
        $db = Database::getInstance();
        $query = "SELECT action_id, action_type, title, description, priority, status, created_date, start_date, target_date
                 FROM wfa_performance_actions
                 WHERE employee_id = ? AND status IN ('pending', 'ongoing')
                 ORDER BY priority DESC, created_date DESC";
        
        $result = $db->fetchAll($query, [$employee_id], 'i');
        $activeActions = $result ?: [];
    } catch (Exception $e) {
        // Table might not exist yet, continue without actions
        $activeActions = [];
    }
    
    try {
        // Get any active PIP
        $query = "SELECT pip_id, wpa.action_id, wpa.title, pip.start_date, pip.end_date, pip.status, pip.current_progress_percentage
                 FROM wfa_performance_improvement_plans pip
                 LEFT JOIN wfa_performance_actions wpa ON pip.action_id = wpa.action_id
                 WHERE pip.employee_id = ? AND pip.status IN ('active', 'extended')
                 ORDER BY pip.start_date DESC
                 LIMIT 1";
        
        $activePIP = $db->fetchOne($query, [$employee_id], 'i');
    } catch (Exception $e) {
        // Table might not exist yet, continue without PIP
        $activePIP = null;
    }
    
    // Build response
    $response = [
        'status' => 'success',
        'data' => [
            'assessment' => $assessment,
            'active_actions' => $activeActions,
            'active_pip' => $activePIP,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Add action plan if requested
    if (isset($_GET['include_plan']) && $_GET['include_plan'] === 'true') {
        $actionPlan = $analytics->generateActionPlan($employee_id);
        if ($actionPlan) {
            $response['data']['action_plan'] = $actionPlan;
        }
    }
    
    // Calculate overall risk level
    $rootCausesCount = count($assessment['root_causes']);
    $highSeverityCount = count(array_filter($assessment['root_causes'], function($c) {
        return $c['severity'] === 'HIGH';
    }));
    
    if ($highSeverityCount >= 3) {
        $response['data']['overall_risk'] = 'CRITICAL';
    } elseif ($highSeverityCount >= 2 || $rootCausesCount >= 4) {
        $response['data']['overall_risk'] = 'HIGH';
    } elseif ($highSeverityCount >= 1 || $rootCausesCount >= 2) {
        $response['data']['overall_risk'] = 'MEDIUM';
    } else {
        $response['data']['overall_risk'] = 'LOW';
    }
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
