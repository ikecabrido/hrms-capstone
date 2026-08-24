<?php
/**
 * At-Risk Employees API
 * Returns employees at risk of turnover
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Analytics.php';

try {
    $analytics = new Analytics();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $riskLevel = isset($_GET['risk_level']) ? $_GET['risk_level'] : null;
    
    $atRiskEmployees = $analytics->getEmployeesAtRisk();
    
    // Filter by risk level if specified
    if ($riskLevel) {
        $atRiskEmployees = array_filter($atRiskEmployees, function($emp) use ($riskLevel) {
            return $emp['risk_level'] === $riskLevel;
        });
    }
    
    // Apply limit
    $atRiskEmployees = array_slice($atRiskEmployees, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $atRiskEmployees,
        'total' => count($atRiskEmployees)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
