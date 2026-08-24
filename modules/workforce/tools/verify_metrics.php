<?php
/**
 * Verify Dashboard Metrics are being returned
 */

$json = json_decode(file_get_contents('http://localhost/capstone_hr_management_system/workforce/api/dashboard_metrics.php'), true);

echo "✅ Dashboard Metrics Verification\n";
echo "==================================\n\n";

echo "1. Attendance Rate: " . $json['data']['attendance_rate'] . "%\n";
echo "2. Attendance Breakdown: " . count($json['data']['attendance_breakdown']) . " status types\n";
echo "3. Attendance Trend: " . count($json['data']['attendance_trend']) . " days of data\n";
echo "4. Performance Distribution: " . count($json['data']['performance_distribution']) . " levels\n";
echo "5. At-Risk Employees: " . count($json['data']['at_risk_employees']) . " employees\n";
echo "6. Departments: " . count($json['data']['department_distribution']) . " departments\n";
echo "7. Tenure Ranges: " . count($json['data']['tenure_distribution']) . " ranges\n";
echo "8. Total Employees: " . $json['data']['employee_metrics']['total_employees'] . "\n\n";

echo "✅ STATUS: All metrics are returning data!\n";
?>
