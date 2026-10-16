<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\SurveyTargetController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
engagementApiRequireHrAdmin();
$action = $_GET['action'] ?? 'list';

$ctrl = new SurveyTargetController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            jsonResponse($ctrl->index());
            break;
        case 'view':
            if (empty($data['id'])) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->show((int)$data['id']));
            break;
        case 'create':
            if (empty($data['survey_id']) || empty($data['employee_id']) || empty($data['status'])) {
                jsonResponse(['error' => 'survey_id, employee_id, and status required'], 400);
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
