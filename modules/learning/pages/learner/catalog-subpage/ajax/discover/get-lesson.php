<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

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

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    $moduleId = isset($_GET['module_id'])
        ? (int) $_GET['module_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $lessonModel = new Lesson($pdo);

    /*
     * ---------------------------------------------------------
     * Single lesson
     * ---------------------------------------------------------
     */
    if ($id > 0) {
        $lesson = $lessonModel->getById($id);

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

        echo json_encode([
            'success' => true,
            'item' => $lesson
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Lessons belonging to a module
     * ---------------------------------------------------------
     */
    if ($moduleId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Module ID is required.'
        ]);

        exit;
    }

    $lessons = $lessonModel->getList($moduleId);

    /*
     * Only active lessons are available to learners.
     *
     * Enrollment is deliberately NOT checked here.
     */
    $lessons = array_values(
        array_filter(
            $lessons,
            static function ($lesson): bool {
                return is_array($lesson)
                    && isset($lesson['status'])
                    && $lesson['status'] === 'active';
            }
        )
    );

    echo json_encode([
        'success' => true,
        'items' => $lessons,
        'count' => count($lessons)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load lessons.'
    ]);
}