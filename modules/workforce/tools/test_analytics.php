<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Analytics.php';

try {
    echo "<h2>Testing Analytics</h2>\n";
    
    $analytics = new Analytics();
    
    echo "<h3>Dashboard Metrics:</h3>\n";
    $metrics = $analytics->getDashboardMetrics();
    echo "<pre>" . json_encode($metrics, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Department Distribution:</h3>\n";
    $dept = $analytics->getDepartmentDistribution();
    echo "<pre>" . json_encode($dept, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Age Group Distribution:</h3>\n";
    $age = $analytics->getAgeGroupDistribution();
    echo "<pre>" . json_encode($age, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Attrition Data:</h3>\n";
    $attr = $analytics->getAttritionData();
    echo "<pre>" . json_encode($attr, JSON_PRETTY_PRINT) . "</pre>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "\n<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
