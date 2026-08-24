<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Analytics.php';

header('Content-Type: application/json');

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
    
    $response = [
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
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
