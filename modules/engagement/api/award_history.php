<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\AwardHistoryController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
engagementApiRequireLogin();
$action = $_GET['action'] ?? 'list';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
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
            if (($data['award_type'] ?? '') === 'employee_of_month') {
                jsonResponse(['error' => 'Employee of the Month awards are created only through HR-approved Top 5 selections.'], 410);
            }
            $data['nominated_by'] = $_SESSION['employee_id']
                ?? $_SESSION['user']['employee_id']
                ?? null;
            if (empty($data['nominated_by'])) {
                $userId = $_SESSION['user']['id']
                    ?? $_SESSION['user_id']
                    ?? $_SESSION['user']['user_id']
                    ?? null;
                if ($userId) {
                    $db = \Database::getInstance()->getConnection();
                    $stmt = $db->prepare('SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1');
                    $stmt->execute(['user_id' => $userId]);
                    $data['nominated_by'] = $stmt->fetchColumn() ?: null;
                }
            }
            if (empty($data['nominated_by'])) {
                jsonResponse(['error' => 'Current user is not linked to an employee record'], 400);
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
