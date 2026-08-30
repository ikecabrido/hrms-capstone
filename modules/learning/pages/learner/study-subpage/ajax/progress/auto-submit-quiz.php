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

    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $quizId = (int) ($_POST['quiz_id'] ?? 0);

    if ($sessionId <= 0 || $courseId <= 0 || $quizId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Session ID, Course ID, and Quiz ID are required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $quizSession = new QuizSession($pdo);
    $result = $quizSession->autoSubmit($sessionId, $learnerId);

    if (empty($result['success'])) {
        http_response_code(422);
        echo json_encode($result);
        exit;
    }

    $enrollment = new Enrollment($pdo);
    $enrollmentRow = $enrollment->getByLearnerAndCourse($learnerId, $courseId);

    if ($enrollmentRow) {
        $progress = new Progress($pdo);
        $progress->markComplete((int) $enrollmentRow['id'], 'quiz', $quizId);
        $enrollment->touchLastAccessed((int) $enrollmentRow['id']);
    }

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to auto-submit quiz.',
        'error' => $e->getMessage(),
    ]);
}
exit;