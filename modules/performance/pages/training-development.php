<?php
require_once __DIR__ . '/../controller/TrainingDevelopmentController.php';

$controller = new TrainingDevelopmentController();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'priority' => trim((string) ($_GET['priority'] ?? '')),
    'employee_id' => trim((string) ($_GET['employee_id'] ?? '')),
    'from_date' => trim((string) ($_GET['from_date'] ?? '')),
    'to_date' => trim((string) ($_GET['to_date'] ?? '')),
];

$dashboard = $controller->getDashboardData($filters);
$messages = $controller->getMessages();

$data = [
    'stats' => $dashboard['stats'] ?? [],
    'recommendations' => $dashboard['recommendations'] ?? [],
    'employees' => $dashboard['employees'] ?? [],
    'programs' => $dashboard['programs'] ?? [],
    'upcoming' => $dashboard['upcoming'] ?? [],
    'categorySummary' => $dashboard['categorySummary'] ?? [],
    'messages' => $messages,
    'filters' => $filters,
];

require_once __DIR__ . '/../view/training-development-view.php';
TrainingDevelopmentView::render($data);
