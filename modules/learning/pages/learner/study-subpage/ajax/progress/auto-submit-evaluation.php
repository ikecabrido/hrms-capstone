<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
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

    if ($sessionId <= 0 || $courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Session ID and Course ID are required.']);
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
        $enrollment->touchLastAccessed((int) $enrollmentRow['id']);

        if ($result['passed'] === true) {
            $enrollment->markCompleted((int) $enrollmentRow['id']);
        }
    }

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to auto-submit evaluation.',
        'error' => $e->getMessage(),
    ]);
}
exit;