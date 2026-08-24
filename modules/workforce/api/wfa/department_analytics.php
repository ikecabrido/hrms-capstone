<?php
/**
 * Department Analytics API
 * Returns department-wise analytics
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
    
    $data = [
        'department_distribution' => $analytics->getDepartmentDistribution(),
        'salary_statistics' => $analytics->getSalaryStatistics()
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
