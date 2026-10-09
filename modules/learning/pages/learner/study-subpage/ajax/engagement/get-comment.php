<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/comment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $lessonId = (int) ($_GET['lesson_id'] ?? 0);

    if ($lessonId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Lesson ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * Study-side comment threads are only visible to learners who are
     * actively (non-withdrawn) enrolled in the lesson's course.
     */
    $accessStmt = $pdo->prepare("
        SELECT e.id
        FROM ld_lesson l
        INNER JOIN ld_module m ON m.id = l.module_id
        INNER JOIN ld_enrollment e ON e.course_id = m.course_id AND e.learner_id = :learner_id
        WHERE l.id = :lesson_id
          AND e.status != 'withdrawn'
        LIMIT 1
    ");

    $accessStmt->execute([
        ':learner_id' => $learnerId,
        ':lesson_id' => $lessonId,
    ]);

    if (!$accessStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this lesson\'s course.']);
        exit;
    }

    $comment = new Comment($pdo);

    // getByLesson() returns ALL comments for the lesson (including replies),
    // so filter down to top-level ones here before nesting replies under each.
    $allComments = $comment->getByLesson($lessonId);
    $topLevel = array_filter($allComments, function ($item) {
        return $item['parent_comment_id'] === null;
    });

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) use ($comment) {
            return [
                'id' => (int) $item['id'],
                'learner_id' => (int) $item['learner_id'],
                'message' => $item['message'],
                'parent_comment_id' => null,
                'created_at' => $item['created_at'],
                'replies' => array_map(function ($reply) {
                    return [
                        'id' => (int) $reply['id'],
                        'learner_id' => (int) $reply['learner_id'],
                        'message' => $reply['message'],
                        'created_at' => $reply['created_at'],
                    ];
                }, $comment->getReplies((int) $item['id'])),
            ];
        }, $topLevel),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load comments.',
    ]);
}
