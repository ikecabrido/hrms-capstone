<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
require_once dirname(__FILE__, 6) . '/classes/progress.php';
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

    $sessionId = (int) ($_POST['session_id'] ?? 0);

    if ($sessionId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Session ID is required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * ---------------------------------------------------------
     * Load the session belonging to this learner.
     * ---------------------------------------------------------
     *
     * Do not trust quiz_id or course_id from POST when deciding
     * which learning item should receive completion progress.
     */
    $sessionStmt = $pdo->prepare(
        'SELECT *
         FROM ld_quiz_session
         WHERE id = :session_id
           AND learner_id = :learner_id
         LIMIT 1'
    );

    $sessionStmt->execute([
        ':session_id' => $sessionId,
        ':learner_id' => $learnerId,
    ]);

    $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz session not found.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * This endpoint currently submits quiz attempts.
     * ---------------------------------------------------------
     */
    if (($session['item_type'] ?? '') !== 'quiz') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'This session is not a quiz attempt.'
        ]);

        exit;
    }

    $quizId = (int) $session['reference_id'];

    if ($quizId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid quiz reference.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Verify quiz -> module -> course relationship.
     * ---------------------------------------------------------
     */
    $quizStmt = $pdo->prepare(
        'SELECT
            q.id,
            q.status AS quiz_status,
            m.id AS module_id,
            m.status AS module_status,
            m.course_id,
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

    $courseId = (int) $quiz['course_id'];

    /*
     * ---------------------------------------------------------
     * Verify learner enrollment.
     * ---------------------------------------------------------
     */
    $enrollment = new Enrollment($pdo);

    $enrollmentRow = $enrollment->getByLearnerAndCourse(
        $learnerId,
        $courseId
    );

    if (!$enrollmentRow) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'You are not enrolled in this course.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Finalize the quiz attempt.
     * ---------------------------------------------------------
     */
    $quizSession = new QuizSession($pdo);

    $result = $quizSession->submit(
        $sessionId,
        $learnerId
    );

    if (empty($result['success'])) {
        http_response_code(422);

        echo json_encode($result);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Mark this quiz complete in learner progress.
     * ---------------------------------------------------------
     */
    $progress = new Progress($pdo);

    $progressResult = $progress->markComplete(
        (int) $enrollmentRow['id'],
        'quiz',
        $quizId
    );

    /*
     * Keep the submission successful even if progress reporting
     * returns no useful payload. The quiz itself was already
     * finalized by QuizSession::submit().
     */
    $enrollment->touchLastAccessed(
        (int) $enrollmentRow['id']
    );

    /*
     * Include progress information when available without
     * changing the existing quiz result structure.
     */
    if (is_array($progressResult)) {
        $result['progress'] = $progressResult;
    }

    echo json_encode($result);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit quiz.'
    ]);
}

exit;