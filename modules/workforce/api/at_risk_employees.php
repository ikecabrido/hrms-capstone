<?php
/**
 * Employees at Risk API
 * Returns employees at risk of turnover
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Analytics.php';

try {
    $analytics = new Analytics();
    $atRiskEmployees = $analytics->getEmployeesAtRisk();
    
    // Group by risk level
    $grouped = [];
    foreach ($atRiskEmployees as $employee) {
        $risk = $employee['risk_level'];
        if (!isset($grouped[$risk])) {
            $grouped[$risk] = [];
        }
        $grouped[$risk][] = $employee;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $grouped,
        'total_at_risk' => count($atRiskEmployees)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
