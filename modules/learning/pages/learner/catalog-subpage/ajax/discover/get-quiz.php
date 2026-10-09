<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/quiz.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
        exit;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $moduleId = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;

    $database = new Database();
    $pdo = $database->getConnection();
    $quizModel = new Quiz($pdo);

    if ($id > 0) {
        $quiz = $quizModel->getById($id);

        if (!$quiz || $quiz['status'] !== 'active') {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Quiz not found.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $quiz
        ]);
        exit;
    }

    if ($moduleId <= 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Module ID is required.'
        ]);
        exit;
    }

    $quizzes = $quizModel->getList($moduleId);

    $quizzes = array_values(array_filter(
        $quizzes,
        static function (array $quiz): bool {
            return isset($quiz['status']) && $quiz['status'] === 'active';
        }
    ));

    echo json_encode([
        'success' => true,
        'items' => $quizzes,
        'count' => count($quizzes)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load quizzes.'
    ]);
}