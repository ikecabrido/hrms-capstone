<?php
/**
 * Dashboard Metrics API
 * Returns metrics for HR dashboard
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
    
    // Collect all metrics
    $metrics = [
        'employee_metrics' => $analytics->getDashboardMetrics(),
        'attendance_rate' => $analytics->getAttendanceRate(3),
        'attendance_breakdown' => $analytics->getAttendanceBreakdown(30),
        'attendance_trend' => $analytics->getAttendanceTrend(30),
        'performance_distribution' => $analytics->getPerformanceDistribution(),
        'at_risk_employees' => $analytics->getEmployeesAtRisk(),
        'department_distribution' => $analytics->getDepartmentDistribution(),
        'tenure_distribution' => $analytics->getTenureDistribution(),
        'separated_count' => $analytics->getSeparatedCount(),
        'resignation_reasons' => $analytics->getResignationReasons()
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $metrics
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
