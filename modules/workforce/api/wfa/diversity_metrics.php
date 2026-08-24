<?php
/**
 * Diversity Metrics API
 * Returns diversity and inclusion analytics
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
    $category = isset($_GET['category']) ? $_GET['category'] : 'gender';
    
    $data = [];
    
    // Get different distribution based on category
    switch($category) {
        case 'gender':
            $data['distribution'] = $analytics->getGenderDistribution();
            break;
        case 'age':
            $data['distribution'] = $analytics->getAgeGroupDistribution();
            break;
        case 'tenure':
            $data['distribution'] = $analytics->getTenureDistribution();
            break;
        default:
            $data['distribution'] = $analytics->getGenderDistribution();
    }
    
    $data['category'] = $category;
    
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
