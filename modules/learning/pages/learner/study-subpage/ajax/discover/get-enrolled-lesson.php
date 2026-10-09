<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/classes/lesson.php';
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

    $lessonId = isset($_GET['lesson_id'])
        ? (int) $_GET['lesson_id']
        : 0;

    if ($lessonId <= 0 && isset($_GET['id'])) {
        $lessonId = (int) $_GET['id'];
    }

    if ($lessonId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Lesson ID is required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $lessonModel = new Lesson($pdo);
    $enrollmentModel = new Enrollment($pdo);

    /*
     * ---------------------------------------------------------
     * Load lesson
     * ---------------------------------------------------------
     */
    $lesson = $lessonModel->getById($lessonId);

    if (
        !$lesson ||
        !isset($lesson['status']) ||
        $lesson['status'] !== 'active'
    ) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Lesson not found or unavailable.'
        ]);

        exit;
    }

    $moduleId = isset($lesson['module_id'])
        ? (int) $lesson['module_id']
        : 0;

    if ($moduleId <= 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Lesson module could not be determined.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Verify parent module and course
     * ---------------------------------------------------------
     *
     * We query the relationship directly because the Lesson model
     * intentionally only handles lesson records.
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
            'message' => 'You are not enrolled in this course.'
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Return lesson
     * ---------------------------------------------------------
     */
    echo json_encode([
        'success' => true,
        'item' => $lesson,
        'enrollment' => $enrollmentRow,
        'course_id' => $courseId,
        'module_id' => $moduleId
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load lesson.'
    ]);
}