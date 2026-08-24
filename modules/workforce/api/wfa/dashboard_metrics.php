<?php
/**
 * Dashboard Metrics API
 * Returns comprehensive metrics for HR dashboard
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
    
    // Get all metrics
    $employee_metrics = $analytics->getDashboardMetrics();
    $attendance_rate = $analytics->getAttendanceRate(3);
    $attendance_breakdown = $analytics->getAttendanceBreakdown(30);
    $attendance_trend = $analytics->getAttendanceTrend(30);
    $performance_dist = $analytics->getPerformanceDistribution();
    $at_risk_employees = $analytics->getEmployeesAtRisk();
    $dept_dist = $analytics->getDepartmentDistribution();
    $tenure_dist = $analytics->getTenureDistribution();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_metrics' => $employee_metrics,
            'attendance_rate' => $attendance_rate,
            'attendance_breakdown' => $attendance_breakdown,
            'attendance_trend' => $attendance_trend,
            'performance_distribution' => $performance_dist,
            'at_risk_employees' => $at_risk_employees,
            'department_distribution' => $dept_dist,
            'tenure_distribution' => $tenure_dist
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
