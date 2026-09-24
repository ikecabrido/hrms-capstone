<?php

require_once '../autoload.php';

use App\Controllers\GroupController;

header('Content-Type: application/json');

$groupCtrl = new GroupController();

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $name = $_POST['name'] ?? $_POST['group_name'] ?? null;
        $description = $_POST['description'] ?? $_POST['group_description'] ?? null;
        $createdBy = $_SESSION['user']['employee_id']
            ?? $_SESSION['employee_id']
            ?? $_SESSION['user']['id']
            ?? $_SESSION['user_id']
            ?? null;

        if ($name) {
            $groupId = $groupCtrl->createGroup($name, $description, $createdBy);
            echo json_encode(['success' => true, 'group_id' => $groupId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Group name is required.']);
        }

    } elseif ($method === 'GET') {
        $groups = $groupCtrl->getGroups();
        echo json_encode(['success' => true, 'data' => $groups]);

    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents('php://input'), $deleteVars);
        $groupId = $deleteVars['group_id'] ?? null;

        if ($groupId) {
            $groupCtrl->deleteGroup($groupId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Group ID is required.']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
