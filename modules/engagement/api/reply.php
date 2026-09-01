<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\ReplyController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$action = $_GET['action'] ?? ($requestMethod === 'POST' ? 'add' : 'list');
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && $action !== 'list') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

function resolveAuthorFromSession()
{
    $employeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;
    $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    if (!empty($employeeId)) {
        return ['author_id' => $employeeId, 'author_type' => 'employee'];
    }
    if (!empty($userId)) {
        return ['author_id' => $userId, 'author_type' => 'user'];
    }
    $username = $_SESSION['user']['username'] ?? null;
    if (!empty($username)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result['id'])) {
            return ['author_id' => $result['id'], 'author_type' => 'user'];
        }
        return ['author_id' => null, 'author_type' => 'user'];
    }
    return null;
}

$ctrl = new ReplyController();
$data = inputData();

try {
    switch ($action) {
        case 'list':
            if (!empty($data['comment_id'])) {
                $replies = $ctrl->getRepliesByComment((int)$data['comment_id']);
            } elseif (!empty($data['post_id'])) {
                $replies = $ctrl->getRepliesByPost((int)$data['post_id']);
            } else {
                $replies = $ctrl->getAllReplies();
            }
            jsonResponse(['success' => true, 'data' => $replies]);
            break;

        case 'add':
            foreach (['comment_id', 'post_id', 'content'] as $field) {
                if (empty($data[$field])) {
                    jsonResponse(['error' => "$field is required"], 400);
                }
            }

            $author = resolveAuthorFromSession();
            if (empty($author)) {
                jsonResponse(['error' => 'employee_id or user_id is required'], 400);
            }

            $parentReplyId = isset($data['parent_reply_id']) ? (int)$data['parent_reply_id'] : null;
            $mentionedUserId = isset($data['mentioned_user_id']) ? (int)$data['mentioned_user_id'] : null;

            $replyId = $ctrl->addReply(
                (int)$data['comment_id'],
                (int)$data['post_id'],
                $author['author_id'],
                trim($data['content']),
                $author['author_type'],
                $parentReplyId,
                $mentionedUserId
            );

            jsonResponse(['success' => true, 'data' => ['reply_id' => $replyId]], 201);
            break;

        case 'delete':
            if (empty($data['reply_id'])) {
                jsonResponse(['error' => 'reply_id is required'], 400);
            }
            $ctrl->deleteReply((int)$data['reply_id']);
            jsonResponse(['success' => true, 'message' => 'Reply deleted successfully'], 200);
            break;

        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
