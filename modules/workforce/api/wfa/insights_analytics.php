<?php
/**
 * Insights Analytics API
 * Returns HR insights and recommendations
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Analytics.php';
require_once __DIR__ . '/../../models/Employee.php';

try {
    $analytics = new Analytics();
    $employee = new Employee();
    
    $insights = [];
    
    // Get at-risk employees
    $atRiskEmployees = $analytics->getEmployeesAtRisk();
    $insights['at_risk_count'] = count($atRiskEmployees);
    $insights['at_risk_employees'] = array_slice($atRiskEmployees, 0, 5);
    
    // Get attrition rate
    $attritionRate = $analytics->getAttritionRate();
    $insights['attrition_rate'] = $attritionRate;
    
    // Get resignation reasons
    $resignationReasons = $analytics->getResignationReasons();
    $insights['top_resignation_reasons'] = $resignationReasons;
    
    // Get department distribution
    $deptDist = $analytics->getDepartmentDistribution();
    $insights['department_distribution'] = $deptDist;
    
    // Get performance distribution
    $perfDist = $analytics->getPerformanceDistribution();
    $insights['performance_distribution'] = $perfDist;
    
    echo json_encode([
        'success' => true,
        'data' => $insights
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
