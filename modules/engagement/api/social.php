<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\SocialController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && !in_array($action, ['list', 'feed'], true)) {
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

$ctrl = new SocialController();
$action = $_GET['action'] ?? 'feed';
$data = inputData();

try {
    switch ($action) {
        case 'feed':
            jsonResponse(['success' => true, 'data' => $ctrl->getPosts()]);
            break;
        case 'post':
            if (empty($data['content'])) jsonResponse(['error' => 'content required'], 400);
            $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
            $employeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;
            if (empty($employeeId) && empty($userId)) jsonResponse(['error' => 'user_id or employee_id required'], 400);
            $authorId = !empty($employeeId) ? $employeeId : $userId;
            $authorType = !empty($employeeId) ? 'employee' : 'user';
            $id = $ctrl->createPost($authorId, $data['content'], $authorType, $data['description'] ?? '');
            jsonResponse(['success' => true, 'data' => ['post_id' => $id]], 201);
            break;
        case 'like':
            if (empty($data['post_id'])) jsonResponse(['error' => 'post_id required'], 400);
            $employeeId = $_SESSION['user']['employee_id'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;
            if (empty($employeeId) && empty($userId)) jsonResponse(['error' => 'employee_id or user_id required'], 400);
            $ctrl->addReaction((int)$data['post_id'], $employeeId, $userId, 'like');
            jsonResponse(['success' => true, 'message' => 'Post liked successfully'], 200);
            break;
        case 'comment':
            foreach (['post_id', 'comment'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $author = resolveAuthorFromSession();
            if (empty($author)) jsonResponse(['error' => 'employee_id or user_id required'], 400);
            $id = $ctrl->addComment((int)$data['post_id'], $author['author_id'], $data['comment'], $author['author_type']);
            jsonResponse(['success' => true, 'data' => ['comment_id' => $id]], 201);
            break;
        case 'delete':
            if (empty($data['post_id'])) jsonResponse(['error' => 'post_id required'], 400);
            $ctrl->deletePost((int)$data['post_id']);
            jsonResponse(['message' => 'Post deleted successfully'], 200);
            break;
        case 'edit':
            foreach (['post_id', 'content'] as $f) {
                if (empty($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $ctrl->editPost((int)$data['post_id'], $data['content']);
            jsonResponse(['message' => 'Post updated successfully'], 200);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

