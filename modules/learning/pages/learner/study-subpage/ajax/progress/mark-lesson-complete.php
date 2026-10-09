<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/progress.php';
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
    $lessonId = (int) ($_POST['lesson_id'] ?? 0);

    if ($courseId <= 0 || $lessonId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Course ID and Lesson ID are required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $enrollmentRow = $enrollment->getByLearnerAndCourse($learnerId, $courseId);

    if (!$enrollmentRow) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course.']);
        exit;
    }

    $progress = new Progress($pdo);
    $result = $progress->markComplete((int) $enrollmentRow['id'], 'lesson', $lessonId);

    $enrollment->touchLastAccessed((int) $enrollmentRow['id']);

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
        'message' => 'Failed to mark lesson as complete.',
        'error' => $e->getMessage(),
    ]);
}
exit;