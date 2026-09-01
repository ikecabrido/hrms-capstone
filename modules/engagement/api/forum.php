<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../autoload.php';

use App\Controllers\ForumController;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    exit(0);
}

$forumController = new ForumController();

$action = $_GET['action'] ?? 'list';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                try {
                    $forums = $forumController->getForums();
                    echo json_encode(['success' => true, 'data' => $forums]);
                } catch (Exception $e) {
                    error_log("Forum list error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $forum = $forumController->getForum($_GET['id']);
                if ($forum) {
                    echo json_encode(['success' => true, 'data' => $forum]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Forum not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'POST':
            if ($action === 'create') {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    $data = $_POST;
                }

                // Debug logging
                error_log("Forum create data: " . json_encode($data));
                error_log("User session: " . json_encode($_SESSION['user'] ?? 'No session'));

                $createdBy = $_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? null;
                if (empty($createdBy)) {
                    echo json_encode(['success' => false, 'message' => 'User not logged in']);
                    break;
                }

                foreach (['title', 'description', 'category'] as $field) {
                    if (empty(trim((string)($data[$field] ?? '')))) {
                        echo json_encode(['success' => false, 'message' => $field . ' is required']);
                        break 2;
                    }
                }

                $result = $forumController->createForum($data['title'], $data['description'], $data['category'], (int)$createdBy);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Forum created successfully', 'data' => [
                        'eer_forum_id' => $result,
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'created_by_employee_id' => $createdBy,
                        'created_at' => date('Y-m-d H:i:s')
                    ]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create forum']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'PUT':
            if ($action === 'update' && isset($_GET['id'])) {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $forumController->updateForum($_GET['id'], $data);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Forum updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update forum']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'DELETE':
            if ($action === 'delete' && isset($_GET['id'])) {
                $result = $forumController->deleteForum($_GET['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Forum deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete forum']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
