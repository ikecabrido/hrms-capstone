<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\CommunicationController;
use App\Controllers\MessageController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

function resolveCommunicationEmployeeId()
{
    $employeeId = $_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? null;
    if (!empty($employeeId)) {
        return (int)$employeeId;
    }

    $userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
    if (empty($userId)) {
        return null;
    }

    $db = \Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    return !empty($row['employee_id']) ? (int)$row['employee_id'] : null;
}

$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new CommunicationController();
$apiUser = $_SESSION['user'] ?? [];
$apiRole = strtolower(trim((string)($apiUser['role_name'] ?? $apiUser['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
$apiRoleId = (int)($apiUser['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isHrAdmin = in_array($apiRoleId, [1, 12], true)
    || preg_match('/(^|[^a-z])(admin|hr|human resources|human resource|employee relations|engagement)([^a-z]|$)/', $apiRole) === 1;
$action = $_GET['action'] ?? 'announcements';
$data = inputData();

if ($action === 'messages' && empty($data['employee_id'])) {
    $data['employee_id'] = $_GET['employee_id'] ?? null;
}

try {
    switch ($action) {
        case 'announcements':
            jsonResponse($ctrl->getAnnouncements());
            break;
        case 'post':
            foreach (['title','content'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }

            $employeeId = resolveCommunicationEmployeeId();

            if (empty($employeeId)) {
                jsonResponse(['error' => 'Current user is not linked to an employee record'], 400);
            }
            $id = $ctrl->postAnnouncement($data['title'], $data['content'], (int)$employeeId);
            jsonResponse(['id' => $id], 201);
            break;
        case 'post_department_update':
            foreach (['title', 'content', 'department'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $employeeId = resolveCommunicationEmployeeId();
            if (empty($employeeId)) {
                jsonResponse(['error' => 'Current user is not linked to an employee record'], 400);
            }
            $id = $ctrl->postDepartmentUpdate(
                $data['title'],
                $data['content'],
                $data['department'],
                $data['priority'] ?? 'normal',
                (int)$employeeId
            );
            jsonResponse(['id' => $id], 201);
            break;
        case 'send_message':
            foreach (['receiver_id', 'message'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $senderId = resolveCommunicationEmployeeId();
            if (empty($senderId)) {
                jsonResponse(['error' => 'Current user is not linked to an employee record'], 400);
            }
            $id = $ctrl->sendMessage((int)$senderId, (int)$data['receiver_id'], $data['message']);
            jsonResponse(['id' => $id], 201);
            break;
        case 'messages':
            jsonResponse(['error' => 'Use /api/index.php?resource=message&action=threads instead'], 400);
            break;
        case 'send-message':
            jsonResponse(['error' => 'Use /api/index.php?resource=message&action=send instead'], 400);
            break;
        case 'notifications':
            jsonResponse($ctrl->getNotifications());
            break;
        case 'post_event':
            foreach (['title', 'date', 'description'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $eventId = $ctrl->postEvent($data['title'], $data['date'], $data['description']);
            jsonResponse(['event_id' => $eventId], 201);
            break;
        case 'shared_files':
            jsonResponse(['success' => true, 'data' => $ctrl->getSharedFiles()]);
            break;
        case 'share_file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            if (empty($_FILES['shared_file']) || $_FILES['shared_file']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['success' => false, 'message' => 'File missing or upload error'], 400);
            }

            $file = $_FILES['shared_file'];
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'xlsx', 'xls'];
            $maxSize = 10 * 1024 * 1024;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                jsonResponse(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)], 400);
            }

            if ($file['size'] > $maxSize) {
                jsonResponse(['success' => false, 'message' => 'File too large. Max size is 10MB.'], 400);
            }

            $uploadDir = __DIR__ . '/../../uploads/social_files/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
            $targetFileName = time() . '_' . $safeName;
            $targetFile = $uploadDir . $targetFileName;

            if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                jsonResponse(['success' => false, 'message' => 'Failed to store uploaded file.'], 500);
            }

            $employeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;
            $createdBy = $employeeId
                ?? $_SESSION['user']['id']
                ?? $_SESSION['user']['user_id']
                ?? $_SESSION['user_id']
                ?? null;
            $authorType = $employeeId ? 'employee' : 'user';
            if (empty($createdBy)) {
                unlink($targetFile);
                jsonResponse(['success' => false, 'message' => 'Current user is not identified.'], 400);
            }

            $description = trim((string)($_POST['description'] ?? $data['description'] ?? ''));
            $content = trim((string)($_POST['content'] ?? $data['content'] ?? ''));
            $fileId = $ctrl->shareFile($createdBy, $safeName, 'uploads/social_files/' . $targetFileName, $file['size'], $ext, $description, $content, $authorType);

            if (!$fileId) {
                unlink($targetFile);
                jsonResponse(['success' => false, 'message' => 'Failed to save file information.'], 500);
            }

            $fileUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']) . '/../../uploads/social_files/' . $targetFileName);

            jsonResponse([
                'success' => true,
                'message' => 'File shared successfully.',
                'file' => [
                    'id' => $fileId,
                    'name' => $safeName,
                    'path' => 'uploads/social_files/' . $targetFileName,
                    'url' => $fileUrl,
                    'size' => $file['size'],
                    'type' => $ext
                ]
            ], 201);
            break;
        case 'delete_shared_file':
            if (empty($data['id'])) {
                jsonResponse(['error' => 'File ID is required'], 400);
            }
            $file = $ctrl->getSharedFileById((int)$data['id']);
            if (!$file) {
                jsonResponse(['error' => 'File not found'], 404);
            }
            $filePath = __DIR__ . '/../../' . ltrim($file['file_path'], '/\\');
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $ctrl->deleteSharedFile((int)$data['id']);
            jsonResponse(['success' => true, 'message' => 'File deleted successfully'], 200);
            break;
        case 'policy_updates':
            jsonResponse($ctrl->getPolicyUpdates());
            break;
        case 'lcm_policies':
            jsonResponse($ctrl->getLcmPolicies());
            break;
        case 'share_lcm_policy':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            if (!$isHrAdmin) jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            foreach (['source_module','source_policy_id'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $sharedBy = $_SESSION['user']['id'] ?? null;
            $targetType = $data['target_type'] ?? 'all';
            if ($targetType === 'department') {
                $targetAudience = 'department_id:' . (int)($data['department_id'] ?? 0);
            } elseif ($targetType === 'employees') {
                $employeeIds = array_filter(array_map('intval', (array)($data['employee_ids'] ?? [])));
                $targetAudience = 'employees:' . implode(',', array_unique($employeeIds));
            } else {
                $targetAudience = 'all';
            }
            $shareId = $ctrl->shareLcmPolicy($data['source_module'], (string)$data['source_policy_id'], $targetAudience, $sharedBy, trim((string)($data['announcement'] ?? '')));
            jsonResponse(['share_id' => $shareId], 201);
            break;
        case 'mark_notification_read':
            if (empty($data['notification_id'])) {
                jsonResponse(['error' => 'Notification ID is required'], 400);
            }
            $ctrl->markNotificationAsRead($data['notification_id']);
            jsonResponse(['success' => true], 200);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

