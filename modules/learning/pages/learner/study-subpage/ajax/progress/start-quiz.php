<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id'])
        ? (int) $_SESSION['employee_id']
        : 0;

    if ($learnerId <= 0) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);

        exit;
    }

    $quizId = (int) ($_POST['quiz_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($quizId <= 0 || $courseId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz ID and Course ID are required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * ---------------------------------------------------------
     * Verify quiz -> module -> course relationship
     * ---------------------------------------------------------
     */
    $quizStmt = $pdo->prepare(
        'SELECT
            q.id,
            q.module_id,
            q.status AS quiz_status,
            m.course_id,
            m.status AS module_status,
            c.status AS course_status
         FROM ld_quiz q
         INNER JOIN ld_module m
            ON m.id = q.module_id
         INNER JOIN ld_course c
            ON c.id = m.course_id
         WHERE q.id = :quiz_id
         LIMIT 1'
    );

    $quizStmt->execute([
        ':quiz_id' => $quizId
    ]);

    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz not found.'
        ]);

        exit;
    }

    /*
     * Never trust the course_id supplied by the browser.
     */
    if ((int) $quiz['course_id'] !== $courseId) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz does not belong to this course.'
        ]);

        exit;
    }

    if (($quiz['quiz_status'] ?? '') !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz is unavailable.'
        ]);

        exit;
    }

    if (($quiz['module_status'] ?? '') !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz module is unavailable.'
        ]);

        exit;
    }

    if (($quiz['course_status'] ?? '') !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz course is unavailable.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Verify learner enrollment
     * ---------------------------------------------------------
     */
    $enrollment = new Enrollment($pdo);

    $enrollmentRow = $enrollment->getByLearnerAndCourse(
        $learnerId,
        $courseId
    );

    if (
        !$enrollmentRow ||
        ($enrollmentRow['status'] ?? '') === 'withdrawn'
    ) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not enrolled in this course.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Start/resume quiz session
     * ---------------------------------------------------------
     *
     * QuizSession::start() remains responsible for its own
     * session and attempt rules.
     */
    $quizSession = new QuizSession($pdo);

    $result = $quizSession->start(
        $learnerId,
        'quiz',
        $quizId
    );

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
        'message' => 'Failed to start quiz.'
    ]);
}

exit;