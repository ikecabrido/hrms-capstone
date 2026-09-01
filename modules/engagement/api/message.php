<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../auth/User.php';

use App\Controllers\MessageController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
$data = inputData();
$employeeParam = trim((string)($data['employee_id'] ?? $_GET['employee_id'] ?? ''));
$allowThreadsNoSession = $action === 'threads' && $employeeParam !== '';
if (!isset($_SESSION['user']) && !$allowThreadsNoSession && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new MessageController();
$action = $_GET['action'] ?? 'threads';

function resolveCurrentEmployeeId(): ?int
{
    if (!empty($_SESSION['user']['employee_id'])) {
        return (int) $_SESSION['user']['employee_id'];
    }

    $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
    if (!empty($userId)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($row['employee_id'])) {
            return (int) $row['employee_id'];
        }
    }

    if (!empty($_SESSION['user']['username'])) {
        $userModel = new User();
        $userRow = $userModel->findByUsername($_SESSION['user']['username']);
        if (!empty($userRow['employee_id'])) {
            return (int) $userRow['employee_id'];
        }
    }

    return null;
}

try {
    switch ($action) {
        case 'send':
            foreach (['receiver_id','message'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }

            $senderId = resolveCurrentEmployeeId();
            if ($senderId === null) {
                jsonResponse(['error' => 'Current user is not linked to an employee record'], 400);
            }

            $receiverId = trim((string)($data['receiver_id'] ?? ''));
            if ($receiverId === '' || !ctype_digit($receiverId)) {
                jsonResponse(['error' => 'receiver_id must be a valid employee ID'], 400);
            }

            $id = $ctrl->sendMessage((int)$senderId, (int)$receiverId, $data['message']);
            jsonResponse(['id' => $id], 201);
            break;
        case 'threads':
            $requestedEmployeeId = trim((string)($data['employee_id'] ?? $_GET['employee_id'] ?? ''));
            $empId = $requestedEmployeeId !== '' ? $requestedEmployeeId : (string)(resolveCurrentEmployeeId() ?? '');

            if ($empId === '') {
                if (!isset($_SESSION['user'])) {
                    jsonResponse(['error' => 'Unauthorized'], 401);
                }
                jsonResponse([], 200);
            }

            if (!ctype_digit($empId)) {
                jsonResponse(['error' => 'employee_id must be numeric'], 400);
            }

            $messages = $ctrl->messageThreads((int)$empId);
            jsonResponse($messages);
            break;
        case 'history':
            foreach (['sender_id','receiver_id'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $history = $ctrl->getMessageHistory($data['sender_id'], $data['receiver_id']);
            jsonResponse($history);
            break;
        case 'unread':
            $empId = trim((string)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user']['employee_id'] ?? ''));
            $unread = $ctrl->getUnreadMessages($empId);
            jsonResponse($unread);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
