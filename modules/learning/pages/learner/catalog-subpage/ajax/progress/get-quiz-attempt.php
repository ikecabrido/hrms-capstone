<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quizsession.php';
require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

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

    /*
     * ---------------------------------------------------------
     * Input
     * ---------------------------------------------------------
     *
     * quiz_id is the preferred identifier.
     *
     * course_id is required so Study can verify that the learner
     * is enrolled in the course containing the quiz.
     */
    $quizId = isset($_GET['quiz_id'])
        ? (int) $_GET['quiz_id']
        : 0;

    if ($quizId <= 0 && isset($_GET['id'])) {
        $quizId = (int) $_GET['id'];
    }

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $sessionId = isset($_GET['session_id'])
        ? (int) $_GET['session_id']
        : 0;

    if ($quizId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz ID is required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * ---------------------------------------------------------
     * Verify enrollment
     * ---------------------------------------------------------
     *
     * This endpoint belongs to Study, so quiz attempt data is
     * available only to learners enrolled in the course.
     */
    if ($courseId <= 0) {
        /*
         * If course_id was not supplied, determine the course
         * through the quiz -> module -> course relationship.
         */
        $courseStmt = $pdo->prepare(
            'SELECT
                c.id AS course_id
             FROM ld_quiz q
             INNER JOIN ld_module m
                ON m.id = q.module_id
             INNER JOIN ld_course c
                ON c.id = m.course_id
             WHERE q.id = :quiz_id
             LIMIT 1'
        );

        $courseStmt->execute([
            ':quiz_id' => $quizId
        ]);

        $courseRow = $courseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$courseRow) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Quiz or parent course not found.'
            ]);

            exit;
        }

        $courseId = (int) $courseRow['course_id'];
    }

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
     * Verify quiz belongs to the supplied course
     * ---------------------------------------------------------
     *
     * Do not trust course_id supplied by the browser.
     */
    $quizStmt = $pdo->prepare(
        'SELECT
            q.id,
            q.module_id,
            q.title,
            q.description,
            q.duration_seconds,
            q.question_count,
            q.max_attempts,
            q.passing_score,
            q.show_answers_after_submit,
            q.status,
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

    if ((int) $quiz['course_id'] !== $courseId) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz does not belong to this course.'
        ]);

        exit;
    }

    if (($quiz['status'] ?? '') !== 'active') {
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
     * Active quiz session
     * ---------------------------------------------------------
     *
     * QuizSession intentionally keeps getActiveSession()
     * private, so this endpoint performs the read-only lookup.
     *
     * Ownership is enforced by learner_id.
     */
    $activeStmt = $pdo->prepare(
        "SELECT
            id,
            learner_id,
            item_type,
            reference_id,
            duration_seconds,
            started_at,
            question_order,
            status
         FROM ld_quiz_session
         WHERE id = :session_id
           AND learner_id = :learner_id
           AND item_type = 'quiz'
           AND reference_id = :quiz_id
           AND status = 'in_progress'
         LIMIT 1"
    );

    if ($sessionId > 0) {
        $activeStmt->execute([
            ':session_id' => $sessionId,
            ':learner_id' => $learnerId,
            ':quiz_id' => $quizId
        ]);
    } else {
        /*
         * If no session_id was supplied, find the learner's
         * current active session for this quiz.
         */
        $activeStmt = $pdo->prepare(
            "SELECT
                id,
                learner_id,
                item_type,
                reference_id,
                duration_seconds,
                started_at,
                question_order,
                status
             FROM ld_quiz_session
             WHERE learner_id = :learner_id
               AND item_type = 'quiz'
               AND reference_id = :quiz_id
               AND status = 'in_progress'
             ORDER BY id DESC
             LIMIT 1"
        );

        $activeStmt->execute([
            ':learner_id' => $learnerId,
            ':quiz_id' => $quizId
        ]);
    }

    $activeSession = $activeStmt->fetch(PDO::FETCH_ASSOC);

    if ($activeSession) {
        $questionOrder = json_decode(
            $activeSession['question_order'],
            true
        );

        if (!is_array($questionOrder)) {
            $questionOrder = [];
        }

        /*
         * Calculate remaining time server-side.
         */
        $remainingSeconds = null;

        if ($activeSession['duration_seconds'] !== null) {
            $elapsed = time() - strtotime($activeSession['started_at']);

            $remainingSeconds = max(
                0,
                (int) $activeSession['duration_seconds'] - $elapsed
            );
        }

        $activeSession['id'] = (int) $activeSession['id'];
        $activeSession['learner_id'] = (int) $activeSession['learner_id'];
        $activeSession['reference_id'] = (int) $activeSession['reference_id'];
        $activeSession['duration_seconds'] =
            $activeSession['duration_seconds'] !== null
                ? (int) $activeSession['duration_seconds']
                : null;
        $activeSession['question_order'] = $questionOrder;
        $activeSession['remaining_seconds'] = $remainingSeconds;
    }

    /*
     * ---------------------------------------------------------
     * Latest completed attempt
     * ---------------------------------------------------------
     *
     * ld_quiz_attempt is created by QuizSession::finalizeSession()
     * after a quiz is submitted or auto-submitted.
     */
    $latestStmt = $pdo->prepare(
        'SELECT
            id,
            learner_id,
            quiz_id,
            quiz_session_id,
            score,
            total_items,
            passed,
            attempted_at
         FROM ld_quiz_attempt
         WHERE learner_id = :learner_id
           AND quiz_id = :quiz_id
         ORDER BY attempted_at DESC, id DESC
         LIMIT 1'
    );

    $latestStmt->execute([
        ':learner_id' => $learnerId,
        ':quiz_id' => $quizId
    ]);

    $latestAttempt = $latestStmt->fetch(PDO::FETCH_ASSOC);

    if ($latestAttempt) {
        $latestAttempt['id'] = (int) $latestAttempt['id'];
        $latestAttempt['learner_id'] = (int) $latestAttempt['learner_id'];
        $latestAttempt['quiz_id'] = (int) $latestAttempt['quiz_id'];
        $latestAttempt['quiz_session_id'] =
            $latestAttempt['quiz_session_id'] !== null
                ? (int) $latestAttempt['quiz_session_id']
                : null;
        $latestAttempt['score'] = (float) $latestAttempt['score'];
        $latestAttempt['total_items'] = (int) $latestAttempt['total_items'];
        $latestAttempt['passed'] = (bool) $latestAttempt['passed'];
    }

    /*
     * ---------------------------------------------------------
     * Attempt count
     * ---------------------------------------------------------
     */
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM ld_quiz_session
         WHERE learner_id = :learner_id
           AND item_type = 'quiz'
           AND reference_id = :quiz_id
           AND status IN ('submitted', 'expired')"
    );

    $countStmt->execute([
        ':learner_id' => $learnerId,
        ':quiz_id' => $quizId
    ]);

    $attemptCount = (int) $countStmt->fetchColumn();

    $maxAttempts = (int) ($quiz['max_attempts'] ?? 0);

    /*
     * QuizSession defaults max_attempts to 2 when the database
     * value is null/empty. Keep the API consistent with it.
     */
    if ($maxAttempts <= 0) {
        $maxAttempts = 2;
    }

    $canStart = !$activeSession && $attemptCount < $maxAttempts;

    /*
     * ---------------------------------------------------------
     * Response
     * ---------------------------------------------------------
     */
    echo json_encode([
        'success' => true,

        'quiz' => [
            'id' => (int) $quiz['id'],
            'module_id' => (int) $quiz['module_id'],
            'course_id' => (int) $quiz['course_id'],
            'title' => $quiz['title'],
            'description' => $quiz['description'],
            'duration_seconds' =>
                $quiz['duration_seconds'] !== null
                    ? (int) $quiz['duration_seconds']
                    : null,
            'question_count' =>
                $quiz['question_count'] !== null
                    ? (int) $quiz['question_count']
                    : null,
            'max_attempts' => $maxAttempts,
            'passing_score' =>
                $quiz['passing_score'] !== null
                    ? (float) $quiz['passing_score']
                    : null,
            'show_answers_after_submit' =>
                (bool) $quiz['show_answers_after_submit']
        ],

        'enrollment' => [
            'id' => (int) $enrollmentRow['id'],
            'course_id' => (int) $courseId,
            'status' => $enrollmentRow['status'] ?? null
        ],

        'active_session' => $activeSession ?: null,

        'latest_attempt' => $latestAttempt ?: null,

        'attempts_used' => $attemptCount,
        'attempts_remaining' => max(0, $maxAttempts - $attemptCount),
        'has_active_session' => $activeSession !== false,
        'can_start' => $canStart
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load quiz attempt.',
        'error' => $e->getMessage()
    ]);
}