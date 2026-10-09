<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/classes/quiz.php';
require_once dirname(__FILE__, 6) . '/classes/progress.php';
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

    $quizId = isset($_GET['quiz_id'])
        ? (int) $_GET['quiz_id']
        : 0;

    if ($quizId <= 0 && isset($_GET['id'])) {
        $quizId = (int) $_GET['id'];
    }

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

    $quizModel = new Quiz($pdo);
    $enrollmentModel = new Enrollment($pdo);
    $progressModel = new Progress($pdo);

    /*
     * ---------------------------------------------------------
     * Load quiz
     * ---------------------------------------------------------
     */
    $quiz = $quizModel->getById($quizId);

    if (
        !$quiz ||
        !isset($quiz['status']) ||
        $quiz['status'] !== 'active'
    ) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz not found or unavailable.'
        ]);

        exit;
    }

    $moduleId = isset($quiz['module_id'])
        ? (int) $quiz['module_id']
        : 0;

    if ($moduleId <= 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Quiz module could not be determined.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Verify parent module and course
     * ---------------------------------------------------------
     */
    $parentStmt = $pdo->prepare(
        'SELECT
            m.id AS module_id,
            m.course_id,
            m.status AS module_status,
            c.id AS course_id,
            c.status AS course_status
         FROM ld_module m
         LEFT JOIN ld_course c
            ON c.id = m.course_id
         WHERE m.id = :module_id
         LIMIT 1'
    );

    $parentStmt->execute([
        ':module_id' => $moduleId
    ]);

    $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Parent module not found.'
        ]);

        exit;
    }

    if (($parent['module_status'] ?? '') !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Module is unavailable.'
        ]);

        exit;
    }

    if (($parent['course_status'] ?? '') !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Course is unavailable.'
        ]);

        exit;
    }

    $courseId = (int) $parent['course_id'];

    if ($courseId <= 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Parent course could not be determined.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Verify learner enrollment
     * ---------------------------------------------------------
     *
     * This is intentionally enrollment-gated because this
     * endpoint belongs to Study, not Catalog.
     */
    $enrollmentRow = $enrollmentModel->getByLearnerAndCourse(
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
            'message' => 'You are not enrolled in this quiz\'s course.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Existing quiz progress
     * ---------------------------------------------------------
     */
    $quizProgress = $progressModel->getItem(
        (int) $enrollmentRow['id'],
        'quiz',
        $quizId
    );

    $quiz['progress'] = $quizProgress ?: [
        'status' => 'not_started'
    ];

    /*
     * ---------------------------------------------------------
     * Return quiz
     * ---------------------------------------------------------
     */
    echo json_encode([
        'success' => true,
        'item' => $quiz,
        'enrollment' => $enrollmentRow,
        'course_id' => $courseId,
        'module_id' => $moduleId
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load quiz.'
    ]);
}