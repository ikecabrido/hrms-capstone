<?php
/**
 * Attrition Metrics API
 * Returns attrition and turnover data
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Prevent any HTML output that would corrupt JSON
ob_clean();

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/Analytics.php';

    $analytics = new Analytics();
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    $data = [
        'attrition_rate' => $analytics->getAttritionRate($year),
        'separated_count' => $analytics->getSeparatedCount($year),
        'resignation_reasons' => $analytics->getResignationReasons($year),
        'separated_employees' => $analytics->getSeparatedEmployees($year)
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

?>
