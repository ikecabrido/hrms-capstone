<?php
require_once __DIR__ . '/../controller/PerformanceReportController.php';

$reportController = new PerformanceReportController();
$reportFilters = [
	'search' => $_GET['search'] ?? '',
	'department' => $_GET['department'] ?? '',
	'status' => $_GET['status'] ?? '',
	'school_year' => $_GET['school_year'] ?? '',
	'employee_id' => $_GET['employee_id'] ?? '',
];
if (!empty($_GET['export'])) $reportController->export($reportFilters);
$reportData = $reportController->getData($reportFilters);
include __DIR__ . '/../view/performance-report-view.php';
