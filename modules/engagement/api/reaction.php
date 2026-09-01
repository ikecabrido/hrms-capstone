<?php
require_once '../autoload.php';
require_once __DIR__ . '/../../../database/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

use App\Controllers\ReactionController;

function resolveEmployeeIdFromSession(): ?string
{
    if (!empty($_SESSION['user']['employee_id'])) {
        return $_SESSION['user']['employee_id'];
    }
    if (!empty($_SESSION['employee_id'])) {
        return $_SESSION['employee_id'];
    }

    $sessionUserId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
    if (empty($sessionUserId)) {
        return null;
    }

    $db = Database::getInstance()->getConnection();
    $columnCheck = $db->prepare("SHOW COLUMNS FROM users LIKE 'user_id'");
    $columnCheck->execute();
    $hasUserIdColumn = (bool) $columnCheck->fetch(PDO::FETCH_ASSOC);

    if ($hasUserIdColumn) {
        $stmt = $db->prepare('SELECT employee_id FROM users WHERE id = :user_id OR user_id = :user_id LIMIT 1');
    } else {
        $stmt = $db->prepare('SELECT employee_id FROM users WHERE id = :user_id LIMIT 1');
    }

    $stmt->execute(['user_id' => $sessionUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($row['employee_id'])) {
        return $row['employee_id'];
    }

    return null;
}

$reactionCtrl = new ReactionController();

header('Content-Type: application/json');

if (!isset($_SERVER) || !is_array($_SERVER)) {
    $_SERVER = [];
}
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

try {
    if ($method === 'POST') {
        $input = $_POST;
        if (empty($input)) {
            $rawInput = file_get_contents('php://input');
            $decodedInput = json_decode($rawInput, true);
            if (is_array($decodedInput)) {
                $input = $decodedInput;
            }
        }

        $postId = $input['post_id'] ?? null;
        $employeeId = $input['employee_id'] ?? resolveEmployeeIdFromSession();
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
        $type = $input['type'] ?? 'like';

        if ($postId && $type) {
            $result = $reactionCtrl->addReaction($postId, $employeeId, $userId, $type);
            echo json_encode(['success' => true, 'result' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        }
    } elseif ($method === 'GET') {
        $postId = $_GET['post_id'] ?? null;

        if ($postId) {
            $reactions = $reactionCtrl->getReactionsByPost($postId);
            echo json_encode(['success' => true, 'data' => $reactions]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Post ID is required.']);
        }
    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents('php://input'), $deleteVars);
        $reactionId = $deleteVars['reaction_id'] ?? null;

        if ($reactionId) {
            $reactionCtrl->removeReaction($reactionId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Reaction ID is required.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
