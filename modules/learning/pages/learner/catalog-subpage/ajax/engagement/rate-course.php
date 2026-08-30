<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/rating.php';
require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
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

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = isset($_POST['comment']) ? trim((string) $_POST['comment']) : null;

    $database = new Database();
    $pdo = $database->getConnection();

    // Only learners who are (or were) enrolled can rate a course.
    $enrollment = new Enrollment($pdo);
    $enrollmentRow = $enrollment->getByLearnerAndCourse($learnerId, $courseId);

    if (!$enrollmentRow) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only rate courses you are enrolled in.']);
        exit;
    }

    $ratingClass = new Rating($pdo);
    $result = $ratingClass->createOrUpdate([
        'learner_id' => $learnerId,
        'course_id' => $courseId,
        'rating' => $rating,
        'comment' => $comment,
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
        'message' => 'Failed to submit rating.',
        'error' => $e->getMessage(),
    ]);
}
exit;
