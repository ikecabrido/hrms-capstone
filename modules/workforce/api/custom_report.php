<?php
/**
 * Custom Report API
 * Generates filtered HR reports
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Analytics.php';

try {
    $analytics = new Analytics();
    
    // Get filters from request
    $filters = [];
    if (isset($_GET['department']) && !empty($_GET['department'])) {
        $filters['department'] = $_GET['department'];
    }
    if (isset($_GET['employment_type']) && !empty($_GET['employment_type'])) {
        $filters['employment_type'] = $_GET['employment_type'];
    }
    if (isset($_GET['hire_date_from']) && !empty($_GET['hire_date_from'])) {
        $filters['hire_date_from'] = $_GET['hire_date_from'];
    }
    if (isset($_GET['hire_date_to']) && !empty($_GET['hire_date_to'])) {
        $filters['hire_date_to'] = $_GET['hire_date_to'];
    }
    
    $report = $analytics->generateCustomReport($filters);
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'filters' => $filters,
        'total_records' => count($report)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
