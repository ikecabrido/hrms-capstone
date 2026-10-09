<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/evaluation.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    /*
     * Learner authentication.
     *
     * Authentication is required for the learner API,
     * but enrollment is NOT required to access an
     * available evaluation.
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

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $evaluationModel = new Evaluation($pdo);

    /*
     * ---------------------------------------------------------
     * Single evaluation
     * ---------------------------------------------------------
     */
    if ($id > 0) {
        $evaluation = $evaluationModel->getById($id);

        /*
         * Only active evaluations are available to learners.
         */
        if (
            !$evaluation ||
            !isset($evaluation['status']) ||
            $evaluation['status'] !== 'active'
        ) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Evaluation not found or unavailable.'
            ]);

            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $evaluation
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Evaluation list
     * ---------------------------------------------------------
     *
     * course_id is optional.
     *
     * Without course_id:
     *     return all active evaluations.
     *
     * With course_id:
     *     return active evaluations belonging to that course.
     */
    $evaluations = $evaluationModel->getList($courseId);

    /*
     * Never expose archived evaluations through learner discovery.
     */
    $evaluations = array_values(
        array_filter(
            $evaluations,
            static function ($evaluation): bool {
                return is_array($evaluation)
                    && isset($evaluation['status'])
                    && $evaluation['status'] === 'active';
            }
        )
    );

    echo json_encode([
        'success' => true,
        'items' => $evaluations,
        'count' => count($evaluations)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load evaluations.'
    ]);
}