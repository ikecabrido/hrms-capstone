<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\RewardRedemptionController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new RewardRedemptionController();
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
            if (empty($data['employee_id']) || empty($data['reward_id']) || empty($data['points_used'])) {
                jsonResponse(['error' => 'employee_id, reward_id, and points_used are required'], 400);
            }
            $id = $ctrl->store($data);
            jsonResponse(['id' => $id], 201);
            break;
        case 'update_status':
            if (empty($data['id']) || empty($data['status'])) {
                jsonResponse(['error' => 'id and status are required'], 400);
            }
            $adminId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? null;
            $updated = $ctrl->updateStatus((int)$data['id'], $data['status'], $adminId, $data['reason'] ?? null);
            jsonResponse(['success' => $updated > 0]);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

