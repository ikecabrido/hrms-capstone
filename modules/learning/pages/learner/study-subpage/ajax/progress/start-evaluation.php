<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
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

    $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($evaluationId <= 0 || $courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Evaluation ID and Course ID are required.']);
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

    // Completion gate: Evaluation can only be started once every lesson/quiz
    // in the course has been completed — it is the course's final assessment.
    $progress = new Progress($pdo);
    if (!$progress->hasCompletedAllCourseContent((int) $enrollmentRow['id'], $courseId)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Complete all course content before taking the final evaluation.',
        ]);
        exit;
    }

    $quizSession = new QuizSession($pdo);
    $result = $quizSession->start($learnerId, 'evaluation', $evaluationId);

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
        'message' => 'Failed to start evaluation.',
        'error' => $e->getMessage(),
    ]);
}
exit;