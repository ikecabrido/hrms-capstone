<?php

$employeeClass = $employeeClass ?? new Employee();
$allEmployees = $employeeClass->getEmployees();
$employees = $allEmployees;
$employeeCount = count($employees);
$employeeStatuses = [];
foreach ($allEmployees as $employee) {
    $employeeStatus = trim((string) ($employee['status'] ?? ''));
    if ($employeeStatus !== '') {
        $employeeStatuses[strtoupper($employeeStatus)] = $employeeStatus;
    }
}
asort($employeeStatuses);
$analytics = $employeeClass->getDashboardAnalytics();
$dashboardMetrics = $analytics['metrics'] ?? [];

$attendanceSummary = [
    'present_days' => (int) ($dashboardMetrics['present_days'] ?? 0),
    'late_arrivals' => (int) ($dashboardMetrics['late_arrivals'] ?? 0),
    'absent_days' => (int) ($dashboardMetrics['absent_days'] ?? 0),
    'leave_days' => (int) ($dashboardMetrics['leave_days'] ?? 0),
    'recorded_days' => (int) ($dashboardMetrics['attendance_recorded_days'] ?? 0),
];
$attendanceRate = (float) ($dashboardMetrics['attendance_rate'] ?? 0);
$attendanceAbsentRate = $attendanceSummary['recorded_days']
    ? round(($attendanceSummary['absent_days'] / $attendanceSummary['recorded_days']) * 100, 1)
    : 0;
$attendanceLeaveRate = $attendanceSummary['recorded_days']
    ? round(($attendanceSummary['leave_days'] / $attendanceSummary['recorded_days']) * 100, 1)
    : 0;
$attendanceDonutPresent = min(max($attendanceRate, 0), 100);
$attendanceDonutAbsent = min(max($attendanceAbsentRate, 0), 100);
$attendanceDonutLeave = min(max($attendanceLeaveRate, 0), 100);

$attrition = $employeeClass->getAttritionAnalytics();
$diversity = $employeeClass->getDiversityAnalytics();
$diversityMetrics = $diversity['metrics'] ?? [];

$employeeFilter = trim((string) ($_GET['employee_id'] ?? ''));
$statusFilter = strtoupper(trim($_GET['status'] ?? ''));
if ($employeeFilter !== '' || $statusFilter !== '') {
    $employees = array_values(array_filter($employees, function ($employee) use ($employeeFilter, $statusFilter) {
        $matchesStatus = $statusFilter === '' || strtoupper($employee['status'] ?? '') === $statusFilter;
        $matchesEmployee = $employeeFilter === '' || (string) ($employee['employee_id'] ?? '') === $employeeFilter;
        return $matchesEmployee && $matchesStatus;
    }));
}
