<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/learningpath.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    /*
     * Learner authentication.
     *
     * Authentication is required for the learner API,
     * but enrollment is NOT required to access available
     * learning paths.
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

    $learningPathModel = new LearningPath($pdo);

    /*
     * ---------------------------------------------------------
     * Single learning path
     * ---------------------------------------------------------
     */
    if ($id > 0) {
        $learningPath = $learningPathModel->getById($id);

        /*
         * Only active learning paths are available to learners.
         *
         * No enrollment check is performed.
         */
        if (
            !$learningPath ||
            !isset($learningPath['status']) ||
            $learningPath['status'] !== 'active'
        ) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Learning path not found or unavailable.'
            ]);

            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $learningPath
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Learning path list
     * ---------------------------------------------------------
     */
    $learningPaths = $learningPathModel->getList();

    /*
     * Never expose archived learning paths through
     * learner discovery.
     */
    $learningPaths = array_values(
        array_filter(
            $learningPaths,
            static function ($learningPath): bool {
                return is_array($learningPath)
                    && isset($learningPath['status'])
                    && $learningPath['status'] === 'active';
            }
        )
    );

    echo json_encode([
        'success' => true,
        'items' => $learningPaths,
        'count' => count($learningPaths)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load learning paths.'
    ]);
}