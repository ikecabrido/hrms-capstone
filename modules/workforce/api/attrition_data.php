<?php
/**
 * Attrition Data API
 * Returns attrition and turnover data
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent any HTML output that would corrupt JSON
ob_clean();

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../models/Analytics.php';

    $analytics = new Analytics();
    
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    $data = [
        'attrition_data' => $analytics->getAttritionData($year),
        'attrition_rate' => $analytics->getAttritionRate($year),
        'total_separated' => $analytics->getSeparatedCount($year),
        'top_reasons' => $analytics->getResignationReasons($year),
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
        'message' => $e->getMessage()
    ]);
}

