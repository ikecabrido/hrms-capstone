<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/comment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $commentId = (int) ($_POST['comment_id'] ?? 0);

    if ($commentId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Comment ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $comment = new Comment($pdo);

    $existing = $comment->getById($commentId);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comment not found.']);
        exit;
    }

    if ((int) $existing['learner_id'] !== $learnerId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments.']);
        exit;
    }

    // Per the no-delete-if-ever-reported rule, comments that were flagged
    // must be archived permanently, not hard-deleted.
    if (!empty($existing['was_ever_reported'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'This comment was previously reported and cannot be deleted.',
        ]);
        exit;
    }

    $result = $comment->archive($commentId);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete comment.',
        'error' => $e->getMessage(),
    ]);
}
exit;
