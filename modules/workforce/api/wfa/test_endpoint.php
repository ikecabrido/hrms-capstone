<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../config/config.php');
require_once('../config/Database.php');
require_once('../models/Analytics.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 12;
    
    $analytics = new Analytics();
    $assessment = $analytics->getPerformanceAssessment($employee_id);
    
    echo json_encode($assessment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
