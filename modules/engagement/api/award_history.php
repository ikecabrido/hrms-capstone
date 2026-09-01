<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\AwardHistoryController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && $action !== 'list') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new AwardHistoryController();
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
            if (empty($data['employee_id']) || empty($data['award_name'])) jsonResponse(['error' => 'employee_id and award_name required'], 400);
            if (empty($data['nominated_by'])) {
                $data['nominated_by'] = $_SESSION['user']['id']
                    ?? $_SESSION['user_id']
                    ?? $_SESSION['employee_id']
                    ?? null;
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

