<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/course.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    /*
     * Learner authentication.
     *
     * Authentication is required for the learner API,
     * but enrollment is NOT required to access available courses.
     */
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

    $database = new Database();
    $pdo = $database->getConnection();

    $courseModel = new Course($pdo);

    /*
     * ---------------------------------------------------------
     * Single course
     * ---------------------------------------------------------
     */
    if ($id > 0) {
        $course = $courseModel->getById($id);

        /*
         * Only active courses are available to learners.
         *
         * No enrollment check is performed here.
         */
        if (
            !$course ||
            !isset($course['status']) ||
            $course['status'] !== 'active'
        ) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Course not found or unavailable.'
            ]);

            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $course
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Course list
     * ---------------------------------------------------------
     */
    $courses = $courseModel->getList();

    /*
     * The learner catalog must never expose inactive courses,
     * even if the Course model returns them.
     */
    $courses = array_values(
        array_filter(
            $courses,
            static function ($course): bool {
                return is_array($course)
                    && isset($course['status'])
                    && $course['status'] === 'active';
            }
        )
    );

    echo json_encode([
        'success' => true,
        'items' => $courses,
        'count' => count($courses)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load courses.'
    ]);
}