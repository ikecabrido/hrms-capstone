<?php

require_once '../autoload.php';

use App\Controllers\GroupMemberController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}
$groupMemberCtrl = new GroupMemberController();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'POST':
            // Support both JSON and form-data
            $input = json_decode(file_get_contents('php://input'), true);

            $groupId = $input['group_id'] ?? $_POST['group_id'] ?? null;
            $employeeId = $input['employee_id'] ?? $_POST['employee_id'] ?? null;

            if ($groupId && $employeeId && is_numeric($groupId)) {
                $groupMemberId = $groupMemberCtrl->addMember((int)$groupId, $employeeId);

                echo json_encode([
                    'success' => true,
                    'group_member_id' => $groupMemberId
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Valid Group ID and Employee ID are required.'
                ]);
            }

            break;

        case 'GET':
            if (isset($_GET['group_id']) && is_numeric($_GET['group_id'])) {
                $groupId = (int) $_GET['group_id'];
                $members = $groupMemberCtrl->getMembersByGroup($groupId);

                if (!empty($members)) {
                    echo json_encode([
                        'success' => true,
                        'data' => $members
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No members found for the given group ID.'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Group ID is required.'
                ]);
            }

            break;

        case 'DELETE':
            // Accept JSON input for DELETE
            $input = json_decode(file_get_contents('php://input'), true);

            $groupMemberId = $input['group_member_id'] ?? null;

            if ($groupMemberId) {
                $groupMemberCtrl->removeMember($groupMemberId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Member removed successfully.'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Group Member ID is required.'
                ]);
            }

            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
