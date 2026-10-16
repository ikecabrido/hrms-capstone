<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\EmployeeBadgeController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
engagementApiRequireLogin();
$action = $_GET['action'] ?? 'list';
if ($action === 'create') {
    engagementApiRequireHrAdmin();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$ctrl = new EmployeeBadgeController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            $data = $ctrl->index();
            jsonResponse(['success' => true, 'data' => $data]);
            break;
        case 'view':
            if (empty($data['id'])) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->show((int)$data['id']));
            break;
        case 'create':
            engagementApiRequireHrAdmin();
            if (empty($data['employee_id']) || empty($data['badge_id'])) {
                jsonResponse(['error' => 'employee_id and badge_id required'], 400);
            }
            $id = $ctrl->store($data);
            jsonResponse(['id' => $id], 201);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
