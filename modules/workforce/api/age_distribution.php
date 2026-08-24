<?php
/**
 * Age Group Distribution API
 * Returns age diversity metrics
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Analytics.php';

try {
    $analytics = new Analytics();
    $distribution = $analytics->getAgeGroupDistribution();
    
    echo json_encode([
        'success' => true,
        'data' => $distribution
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
