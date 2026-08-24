<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Analytics.php';

try {
    echo "<h2>Updated Analytics Data</h2>\n";
    
    $analytics = new Analytics();
    
    echo "<h3>Attendance Rate (Last 3 Months):</h3>\n";
    $rate = $analytics->getAttendanceRate(3);
    echo "Rate: " . $rate . "%<br>\n";
    
    echo "<h3>Attendance Breakdown (Last 30 Days):</h3>\n";
    $breakdown = $analytics->getAttendanceBreakdown(30);
    echo "<pre>" . json_encode($breakdown, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Attendance Trend (Last 30 Days):</h3>\n";
    $trend = $analytics->getAttendanceTrend(30);
    echo "<pre>" . json_encode($trend, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Performance Distribution:</h3>\n";
    $perf = $analytics->getPerformanceDistribution();
    echo "<pre>" . json_encode($perf, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h3>Employees at Risk:</h3>\n";
    $risk = $analytics->getEmployeesAtRisk();
    echo "Count: " . count($risk) . "<br>\n";
    echo "<pre>" . json_encode($risk, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "\n<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
