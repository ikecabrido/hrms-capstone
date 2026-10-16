<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\RewardRedemptionController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
engagementApiRequireLogin();
$isHrAdmin = engagementApiIsHrAdmin();
$action = $_GET['action'] ?? 'list';
if (in_array($action, ['view', 'update_status'], true) && !$isHrAdmin) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$adminId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? null;
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$ctrl = new RewardRedemptionController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            $data = $isHrAdmin
                ? $ctrl->index()
                : $ctrl->getRedemptionHistory((int)($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? 0));
            jsonResponse(['success' => true, 'data' => $data]);
            break;
        case 'view':
            if (empty($data['id'])) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->show((int)$data['id']));
            break;
        case 'create':
            $employeeId = (int)($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? 0);
            if ($employeeId <= 0 || empty($data['reward_id']) || empty($data['points_used'])) {
                jsonResponse(['error' => 'reward_id and points_used are required for an authenticated employee'], 400);
            }
            $data['employee_id'] = $employeeId;
            $id = $ctrl->store($data);
            jsonResponse(['id' => $id], 201);
            break;
        case 'update_status':
            if (empty($data['id']) || empty($data['status'])) {
                jsonResponse(['error' => 'id and status are required'], 400);
            }
            $updated = $ctrl->updateStatus((int)$data['id'], $data['status'], $adminId, $data['reason'] ?? null);
            jsonResponse(['success' => $updated > 0]);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
