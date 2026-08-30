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

    $lessonId = (int) ($_POST['lesson_id'] ?? 0);
    $message = trim((string) ($_POST['message'] ?? ''));
    $parentCommentId = !empty($_POST['parent_comment_id']) ? (int) $_POST['parent_comment_id'] : null;

    if ($lessonId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Lesson ID is required.']);
        exit;
    }

    if ($message === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Question message is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * Study-side questions may only be asked on lessons that belong to
     * a course the learner is actively (non-withdrawn) enrolled in.
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

    $result = $comment->create([
        'learner_id' => $learnerId,
        'lesson_id' => $lessonId,
        'message' => $message,
        'parent_comment_id' => $parentCommentId,
    ]);

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
        'message' => 'Failed to post question.',
    ]);
}
exit;
