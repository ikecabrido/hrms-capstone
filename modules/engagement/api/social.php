<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\SocialController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && !in_array($action, ['list', 'feed', 'page_data'], true)) {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

function resolveAuthorFromSession()
{
    $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    $db = Database::getInstance()->getConnection();

    if (!empty($userId)) {
        $stmt = $db->prepare('SELECT e.employee_id
            FROM user_account ua
            INNER JOIN em_employees e ON e.employee_id = ua.employee_id
            WHERE ua.user_id = :user_id
            LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result['employee_id'])) {
            return ['author_id' => (int)$result['employee_id'], 'author_type' => 'employee'];
        }
    }

    foreach ([$_SESSION['user']['employee_id'] ?? null, $_SESSION['employee_id'] ?? null] as $employeeId) {
        if (empty($employeeId)) {
            continue;
        }
        $stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute(['employee_id' => $employeeId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result['employee_id'])) {
            return ['author_id' => (int)$result['employee_id'], 'author_type' => 'employee'];
        }
    }

    if (!empty($userId)) {
        return ['author_id' => (int)$userId, 'author_type' => 'user'];
    }

    $username = $_SESSION['user']['username'] ?? null;
    if (!empty($username)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result['id'])) {
            return ['author_id' => (int)$result['id'], 'author_type' => 'user'];
        }
    }

    return null;
}

$ctrl = new SocialController();
$action = $_GET['action'] ?? 'feed';
$data = inputData();

try {
    switch ($action) {
        case 'page_data':
            $pageData = $ctrl->getPageData();
            $currentAuthor = resolveAuthorFromSession();
            $pageData['current_employee_id'] = ($currentAuthor['author_type'] ?? null) === 'employee'
                ? $currentAuthor['author_id']
                : null;
            jsonResponse(['success' => true, 'data' => $pageData]);
            break;
        case 'feed':
            jsonResponse(['success' => true, 'data' => $ctrl->getPosts()]);
            break;
        case 'post':
            if (empty($data['content'])) jsonResponse(['error' => 'content required'], 400);
            $author = resolveAuthorFromSession();
            if (empty($author)) jsonResponse(['error' => 'Current user is not linked to an employee or user record'], 400);
            $id = $ctrl->createPost($author['author_id'], $data['content'], $author['author_type'], $data['description'] ?? '');
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
        case 'resolve':
            if (empty($data['post_id'])) jsonResponse(['error' => 'post_id required'], 400);
            $ctrl->resolvePost((int)$data['post_id']);
            jsonResponse(['success' => true, 'message' => 'Post marked as resolved'], 200);
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

